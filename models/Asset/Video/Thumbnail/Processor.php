<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license GPLv3 and PCL
 */

namespace Pimcore\Model\Asset\Video\Thumbnail;

use Exception;
use Pimcore;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Messenger\VideoConvertMessage;
use Pimcore\Model;
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Tool\Serialize;
use Pimcore\Tool\Storage;
use Pimcore\Video;
use Pimcore\Video\Adapter;
use Symfony\Component\Lock\LockFactory;
use Throwable;

/**
 * @internal
 */
class Processor
{
    private const TRANSCODING_LOCK_TTL = 600;

    protected static array $argumentMapping = [
        'resize' => ['width', 'height'],
        'scaleByWidth' => ['width'],
        'scaleByHeight' => ['height'],
        'cut' => ['start', 'duration'],
        'setFramerate' => ['fps'],
        'colorChannelMixer' => ['effect'],
        'mute' => [],
    ];

    /**
     * @var Adapter[]
     */
    protected array $queue = [];

    protected string $processId;

    protected int $assetId;

    protected Config $config;

    protected int $status;

    /**
     *
     *
     * @throws Exception
     */
    public static function process(Model\Asset\Video $asset, Config $config, array $onlyFormats = []): ?Processor
    {
        if (!Video::isAvailable()) {
            throw new Exception('No ffmpeg executable found, please configure the correct path in the system settings');
        }

        $instance = new self();
        $formats = empty($onlyFormats) ? ['mp4'] : $onlyFormats;
        $instance->setProcessId(uniqid());
        $instance->setAssetId($asset->getId());
        $instance->setConfig($config);

        //create dash file(.mpd), if medias exists
        $medias = $config->getMedias();
        if (count($medias) > 0) {
            $formats[] = 'mpd';
        }

        $existingFormats = [];
        if (self::getFormatsToConvert($asset, $config, $instance, $formats, $existingFormats) === null) {
            return null;
        }

        // two renders of the same thumbnail would otherwise both save the asset and dispatch a conversion
        $lock = Pimcore::getContainer()->get(LockFactory::class)->createLock(
            'video-thumbnail-process-' . $asset->getId() . '-' . $config->getName()
        );
        if (!$lock->acquire()) {
            // another request is starting this conversion right now
            return null;
        }

        try {
            // check again with the stored state, the conversion may have been started since the asset was loaded
            $storedSettings = Serialize::unserialize($asset->getDao()->getCustomSettings());
            $asset->setCustomSetting('thumbnails', is_array($storedSettings) ? ($storedSettings['thumbnails'] ?? null) : null);

            $existingFormats = [];
            $formats = self::getFormatsToConvert($asset, $config, $instance, $formats, $existingFormats);
            if ($formats === null) {
                return null;
            }

            foreach ($formats as $format) {
                $thumbDir = $asset->getRealPath() . '/' . $asset->getId() . '/video-thumb__' . $asset->getId() . '__' . $config->getName();
                $filename = preg_replace('/\.' . preg_quote(pathinfo($asset->getFilename(), PATHINFO_EXTENSION), '/') . '/', '', $asset->getFilename()) . '.' . $format;
                $storagePath = $thumbDir . '/' . $filename;
                // not removed at the end of this request: the worker writes the file later and removes it
                $tmpPath = File::getLocalTempFilePath($format, true);

                if ($converter = Video::getInstance()) {
                    // both bitrates are optional in the config, the adapter keeps its defaults then
                    if ($config->getAudioBitrate() !== null) {
                        $converter->setAudioBitrate($config->getAudioBitrate());
                    }
                    if ($config->getVideoBitrate() !== null) {
                        $converter->setVideoBitrate($config->getVideoBitrate());
                    }
                    $converter->setFormat($format);
                    $converter->setDestinationFile($tmpPath);
                    $converter->setStorageFile($storagePath);

                    //add media queries for mpd file generation
                    if ($format == 'mpd') {
                        $medias = $config->getMedias();
                        foreach ($medias as $media => $transformations) {
                            //used just to generate arguments for medias
                            if ($subConverter = Video::getInstance()) {
                                self::applyTransformations($subConverter, $transformations);
                                $medias[$media]['converter'] = $subConverter;
                            }
                        }
                        $converter->setMedias($medias);
                    }

                    $transformations = $config->getItems();
                    self::applyTransformations($converter, $transformations);

                    $instance->queue[] = $converter;
                }
            }

            $customSetting = $asset->getCustomSetting('thumbnails');
            $customSetting = is_array($customSetting) ? $customSetting : [];
            $customSetting[$config->getName()] = [
                'status' => 'inprogress',
                'formats' => $existingFormats,
                'processId' => $instance->getProcessId(),
            ];
            $asset->setCustomSetting('thumbnails', $customSetting);

            Model\Version::disable();
            $asset->save();
            Model\Version::enable();

            $instance->save();

            Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(
                new VideoConvertMessage($instance->getProcessId(), $asset->getId())
            );

            return $instance;
        } finally {
            $lock->release();
        }
    }

    /**
     * @param string[] $formats
     * @param array<string, string> $existingFormats filled with the formats that are already converted
     *
     * @return string[]|null the formats to convert, null when there is nothing to do
     *
     * @throws Exception when an earlier conversion failed
     */
    private static function getFormatsToConvert(
        Model\Asset\Video $asset,
        Config $config,
        self $instance,
        array $formats,
        array &$existingFormats
    ): ?array {
        $storage = Storage::get('thumbnail');

        // check for running or already created thumbnails
        $customSetting = $asset->getCustomSetting('thumbnails');
        if (is_array($customSetting) && array_key_exists($config->getName(), $customSetting)) {
            if ($customSetting[$config->getName()]['status'] == 'inprogress') {
                if (TmpStore::get($instance->getJobStoreId($customSetting[$config->getName()]['processId']))) {
                    return null;
                }
            } elseif ($customSetting[$config->getName()]['status'] == 'finished') {
                // check if the files are there
                $formatsToConvert = [];
                foreach ($formats as $f) {
                    $format = $customSetting[$config->getName()]['formats'][$f] ?? null;
                    if (!$storage->fileExists($asset->getRealPath() . $format)) {
                        $formatsToConvert[] = $f;
                    } else {
                        $existingFormats[$f] = $customSetting[$config->getName()]['formats'][$f];
                    }
                }

                if (empty($formatsToConvert)) {
                    return null;
                }

                return $formatsToConvert;
            } elseif ($customSetting[$config->getName()]['status'] == 'error') {
                throw new Exception('Unable to convert video, see logs for details.');
            }
        }

        return $formats;
    }

    private static function applyTransformations(Adapter $converter, array $transformations): void
    {
        foreach ($transformations as $transformation) {
            if (!empty($transformation)) {
                $arguments = [];
                $mapping = self::$argumentMapping[$transformation['method']];

                if (is_array($transformation['arguments'])) {
                    foreach ($transformation['arguments'] as $key => $value) {
                        $position = array_search($key, $mapping);
                        if ($position !== false) {
                            $arguments[$position] = $value;
                        }
                    }
                }

                ksort($arguments);
                if (count($mapping) == count($arguments)) {
                    call_user_func_array([$converter, $transformation['method']], $arguments);
                } else {
                    $message = 'Video Transform failed: cannot call method `' . $transformation['method'] . '´ with arguments `' . implode(',', $arguments) . '´ because there are too few arguments';
                    Logger::error($message);
                }
            }
        }
    }

    public static function execute(string $processId, ?int $assetId = null): void
    {
        $instance = new self();
        $instance->setProcessId($processId);

        $instanceItem = TmpStore::get($instance->getJobStoreId($processId));

        if (!$instanceItem) {
            Logger::error('Video conversion job with processId "' . $processId . '" not found in TmpStore.');
            $asset = $assetId ? Model\Asset::getById($assetId) : null;
            if ($asset) {
                // the job (and with it the config) is gone, find the thumbnail entries by their process id
                $customSetting = $asset->getCustomSetting('thumbnails');
                $customSetting = is_array($customSetting) ? $customSetting : [];
                $changed = false;
                foreach ($customSetting as $configName => $thumbnailSetting) {
                    if (is_array($thumbnailSetting) && ($thumbnailSetting['processId'] ?? null) === $processId) {
                        $customSetting[$configName]['status'] = 'error';
                        $changed = true;
                    }
                }

                if ($changed) {
                    $asset->setCustomSetting('thumbnails', $customSetting);

                    Model\Version::disable();

                    try {
                        $asset->save();
                    } finally {
                        Model\Version::enable();
                    }
                }
            }

            return;
        }

        /**
         * @var self $instance
         */
        $instance = $instanceItem->getData();

        $formats = [];
        $conversionStatus = 'finished';

        $asset = Model\Asset::getById($instance->getAssetId());
        if (!$asset) {
            Logger::warning('Video conversion job with processId "' . $processId . '" skipped, asset ' . $instance->getAssetId() . ' does not exist anymore.');
            TmpStore::delete($instance->getJobStoreId());

            return;
        }

        // only one conversion per thumbnail at a time, how many run in parallel is up to the number of workers
        $lock = Pimcore::getContainer()->get(LockFactory::class)->createLock(
            'video-transcoding-' . $asset->getId() . '-' . $instance->getConfig()->getName(),
            self::TRANSCODING_LOCK_TTL
        );
        $lock->acquire(true);

        // ffmpeg reports progress continuously, so a long conversion keeps the lock
        $lastRefresh = time();
        $keepLockAlive = static function () use ($lock, &$lastRefresh): void {
            if (time() - $lastRefresh >= 60) {
                $lock->refresh();
                $lastRefresh = time();
            }
        };

        $workerSourceFile = null;

        try {
            $workerSourceFile = $asset->getTemporaryFile();

            // start converting
            foreach ($instance->queue as $converter) {
                try {
                    $converter->setProgressCallback($keepLockAlive);
                    $converter->load($workerSourceFile, ['asset' => $asset]);

                    Logger::info('start video ' . $converter->getFormat() . ' to ' . $converter->getDestinationFile());
                    $success = $converter->save();
                    Logger::info('finished video ' . $converter->getFormat() . ' to ' . $converter->getDestinationFile());

                    if ($success) {
                        $uploaded = self::writeToStorage($converter->getDestinationFile(), $converter->getStorageFile());

                        if ($uploaded && $converter->getFormat() === 'mpd') {
                            $parentPath = dirname($converter->getStorageFile());

                            foreach (self::getDashStreamFiles($converter) as $stream) {
                                if (!self::writeToStorage($stream, $parentPath . '/' . basename($stream))) {
                                    $uploaded = false;
                                }
                            }
                        }

                        if ($uploaded) {
                            $formats[$converter->getFormat()] = preg_replace(
                                '/' . preg_quote($asset->getRealPath(), '/') . '/',
                                '',
                                $converter->getStorageFile(),
                                1
                            );
                        } else {
                            $conversionStatus = 'error';
                        }
                    } else {
                        $conversionStatus = 'error';
                    }
                } catch (Throwable $e) {
                    // a missing format would otherwise look finished and be converted again on every page view
                    $conversionStatus = 'error';
                    Logger::error((string)$e);
                } finally {
                    $converter->setProgressCallback(null);
                    $converter->destroy();

                    // process() created these paths with $keep, so the worker removes the files
                    foreach ([$converter->getDestinationFile(), ...self::getDashStreamFiles($converter)] as $file) {
                        if (is_file($file)) {
                            @unlink($file);
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            $conversionStatus = 'error';
            Logger::error((string)$e);
        } finally {
            $lock->release();

            if ($workerSourceFile) {
                @unlink($workerSourceFile);
            }
        }

        try {
            $customSetting = $asset->getCustomSetting('thumbnails');
            $customSetting = is_array($customSetting) ? $customSetting : [];

            if (array_key_exists($instance->getConfig()->getName(), $customSetting)
                && array_key_exists('formats', $customSetting[$instance->getConfig()->getName()])
                && is_array($customSetting[$instance->getConfig()->getName()]['formats'])) {
                $formats = array_merge($customSetting[$instance->getConfig()->getName()]['formats'], $formats);
            }

            $customSetting[$instance->getConfig()->getName()] = [
                'status' => $conversionStatus,
                'formats' => $formats,
            ];
            $asset->setCustomSetting('thumbnails', $customSetting);

            Model\Version::disable();

            try {
                $asset->save();
            } finally {
                Model\Version::enable();
            }
        } finally {
            TmpStore::delete($instance->getJobStoreId());
        }
    }

    private static function writeToStorage(string $localFile, string $storagePath): bool
    {
        $source = fopen($localFile, 'rb');
        if (false === $source) {
            Logger::error('could not open stream resource at path "' . $localFile . '" for Video conversion.');

            return false;
        }

        try {
            Storage::get('thumbnail')->writeStream($storagePath, $source);
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }
        }

        return true;
    }

    /**
     * @return string[] the local segment files ffmpeg writes next to a DASH manifest
     */
    private static function getDashStreamFiles(Adapter $converter): array
    {
        if ($converter->getFormat() !== 'mpd') {
            return [];
        }

        return glob(str_replace('.mpd', '-stream*.mp4', $converter->getDestinationFile())) ?: [];
    }

    public function save(): bool
    {
        TmpStore::add($this->getJobStoreId(), $this, 'video-job');

        return true;
    }

    protected function getJobStoreId(?string $processId = null): string
    {
        if (!$processId) {
            $processId = $this->getProcessId();
        }

        return 'video-job-' . $processId;
    }

    public function setProcessId(string $processId): static
    {
        $this->processId = $processId;

        return $this;
    }

    public function getProcessId(): string
    {
        return $this->processId;
    }

    public function setAssetId(int $assetId): static
    {
        $this->assetId = $assetId;

        return $this;
    }

    public function getAssetId(): int
    {
        return $this->assetId;
    }

    public function setConfig(Config $config): static
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function setQueue(array $queue): static
    {
        $this->queue = $queue;

        return $this;
    }

    public function getQueue(): array
    {
        return $this->queue;
    }
}
