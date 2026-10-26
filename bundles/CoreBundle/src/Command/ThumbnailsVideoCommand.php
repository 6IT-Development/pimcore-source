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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore;
use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Traits\Parallelization;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Version;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:thumbnails:video',
    description: 'Generate video thumbnails, useful to pre-generate thumbnails in the background',
    aliases: ['thumbnails:video']
)]
class ThumbnailsVideoCommand extends AbstractCommand
{
    use Parallelization;

    private const DEFAULT_MAX_WAIT = 1800;

    private const MAX_POLL_INTERVAL = 30;

    protected function configure(): void
    {
        parent::configure();
        self::configureCommand($this);

        $this
            ->addOption(
                'parent',
                null,
                InputOption::VALUE_OPTIONAL,
                'only create thumbnails of images in this folder (ID)'
            )
            ->addOption(
                'thumbnails',
                't',
                InputOption::VALUE_OPTIONAL,
                'only create specified thumbnails (comma separated eg.: thumb1,thumb2)'
            )->addOption(
                'system',
                's',
                InputOption::VALUE_NONE,
                'create system thumbnails (used for tree-preview, ...)'
            )->addOption(
                'max-wait',
                null,
                InputOption::VALUE_REQUIRED,
                'maximum time in seconds to wait for a single thumbnail to be finished',
                (string)self::DEFAULT_MAX_WAIT
            );
    }

    private function getMaxWait(InputInterface $input): int
    {
        $maxWait = (string)$input->getOption('max-wait');
        if (!ctype_digit($maxWait) || (int)$maxWait < 1) {
            throw new InvalidArgumentException('--max-wait must be a positive number of seconds, "' . $maxWait . '" given.');
        }

        return (int)$maxWait;
    }

    protected function fetchItems(InputInterface $input, OutputInterface $output): array
    {
        // validate early in the main process instead of failing in every child process
        $this->getMaxWait($input);

        $list = new Asset\Listing();

        $conditionVariables = [];

        // get only videos
        $conditions = ["type = 'video'"];
        if ($parentId = $input->getOption('parent')) {
            $parent = Asset::getById((int)$parentId);
            if ($parent instanceof Asset\Folder) {
                $conditions[] = 'path LIKE ?';
                $conditionVariables[] = $list->escapeLike($parent->getRealFullPath()) . '/%';
            } else {
                // no exit(): it would skip the console TERMINATE event (e.g. --maintenance-mode would stay active)
                throw new InvalidArgumentException($input->getOption('parent') . ' is not a valid asset folder ID!');
            }
        }

        $list->setCondition(implode(' AND ', $conditions), $conditionVariables);
        $assetIdsList = $list->loadIdList();

        // get all thumbnails
        $videoThumbnailList = new Asset\Video\Thumbnail\Config\Listing();

        $allowedThumbs = [];
        if ($input->getOption('thumbnails')) {
            $allowedThumbs = explode(',', $input->getOption('thumbnails'));
        }

        $items = [];
        foreach ($assetIdsList as $assetId) {
            foreach ($videoThumbnailList->getThumbnails() as $thumbnailConfig) {
                $thumbName = $thumbnailConfig->getName();
                if (empty($allowedThumbs) || in_array($thumbName, $allowedThumbs)) {
                    $items[] = $assetId . '~~~' . $thumbName;
                }
            }
        }

        return $items;
    }

    protected function runSingleCommand(string $item, InputInterface $input, OutputInterface $output): void
    {
        // not in runBeforeFirstCommand(): that only runs in the main process, not in the child processes
        $versioningEnabled = Version::isEnabled();
        Version::disable();

        try {
            $this->generateThumbnails($item, $input, $output);
        } finally {
            if ($versioningEnabled) {
                Version::enable();
            }
        }
    }

    private function generateThumbnails(string $item, InputInterface $input, OutputInterface $output): void
    {
        [$assetId, $thumbnailConfigName] = explode('~~~', $item, 2);

        $video = Asset\Video::getById((int)$assetId);
        if (!$video) {
            $this->writeError('No video with ID=' . $assetId . ' found. Has the video been deleted or is the asset of another type?');

            return;
        }

        $maxWait = $this->getMaxWait($input);

        $thumbnail = Asset\Video\Thumbnail\Config::getByName($thumbnailConfigName);
        if (!$thumbnail) {
            $this->writeError('Video thumbnail config "' . $thumbnailConfigName . '" not found. Has it been deleted?');
        } else {
            if ($output->isVerbose()) {
                $this->output->writeln(' generating thumbnail for video: ' . $video->getRealFullPath() . ' | ' . $video->getId() . ' | Thumbnail: ' . $thumbnailConfigName . ' : ' . formatBytes(memory_get_usage()));
            }
            $video->getThumbnail($thumbnail);
            $this->waitTillFinished($video->getId(), $thumbnail, $maxWait);
        }

        if ($input->getOption('system')) {
            if ($output->isVerbose()) {
                $this->output->writeln(' generating thumbnail for video: ' . $video->getRealFullPath() . ' | ' . $video->getId() . ' | Thumbnail: System Preview : ' . formatBytes(memory_get_usage()));
            }
            $thumbnail = Asset\Video\Thumbnail\Config::getPreviewConfig();
            $video->getThumbnail($thumbnail);
            $this->waitTillFinished($video->getId(), $thumbnail, $maxWait);
        }
    }

    /**
     * Polls the thumbnail status with exponential backoff (1s, 2s, 4s, ... up to MAX_POLL_INTERVAL) until it is no
     * longer "inprogress" or $maxWait seconds have passed.
     */
    protected function waitTillFinished(int $videoId, string|Asset\Video\Thumbnail\Config $thumbnail, int $maxWait = self::DEFAULT_MAX_WAIT): void
    {
        $thumbnailName = is_string($thumbnail) ? $thumbnail : $thumbnail->getName();
        $deadline = time() + $maxWait;
        $interval = 1;

        while (true) {
            Pimcore::collectGarbage();

            // force: the status is written by the messenger worker, the cached asset would never change
            $video = Asset\Video::getById($videoId, ['force' => true]);
            if (!$video instanceof Asset\Video) {
                $message = 'video [' . $videoId . '] could not be found. Skipping ...';
                Logger::error($message);
                $this->writeError($message);

                return;
            }

            $thumb = $video->getThumbnail($thumbnail);

            if ($thumb === null) {
                $message = 'video [' . $videoId . '] with thumbnail [' . $thumbnailName . '] is invalid. Skipping ...';
                Logger::error($message);
                $this->output->writeln($message);

                return;
            }

            if ($thumb['status'] === 'finished') {
                Logger::debug('video [' . $videoId . '] FINISHED');

                return;
            }

            if ($thumb['status'] !== 'inprogress') {
                // error
                Logger::debug('video [' . $videoId . "] has status: ['" . $thumb['status'] . "'] -> skipping ...");

                return;
            }

            $remaining = $deadline - time();
            if ($remaining <= 0) {
                $message = 'video [' . $videoId . '] with thumbnail [' . $thumbnailName . '] is still in progress after ' . $maxWait . 's. Is a messenger worker running? Skipping ...';
                Logger::warning($message);
                $this->writeError($message);

                return;
            }

            Logger::debug('video [' . $videoId . '] in progress ...');
            sleep(min($interval, $remaining));
            $interval = min($interval * 2, self::MAX_POLL_INTERVAL);
        }
    }

    protected function getItemName(?int $count): string
    {
        return $count === 1 ? 'thumbnail' : 'thumbnails';
    }
}
