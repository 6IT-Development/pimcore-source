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

namespace Pimcore\Model\Version\Adapter;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Pimcore\Config;
use Pimcore\File;
use Pimcore\Model\Version;
use Pimcore\Tool\Storage;
use Throwable;

/**
 * @internal
 */
class FileSystemVersionStorageAdapter implements VersionStorageAdapterInterface
{
    protected FilesystemOperator $storage;

    public function __construct()
    {
        $this->storage = Storage::get('version');
    }

    public function loadMetaData(Version $version): ?string
    {
        try {
            return $this->storage->read($this->getStorageFilename($version->getId(), $version->getCid(), $version->getCtype()));
        } catch (UnableToReadFile) {
            return null;
        }
    }

    public function loadBinaryData(Version $version): mixed
    {
        $binaryStoragePath = $this->getBinaryStoragePath($version);

        if ($this->storage->fileExists($binaryStoragePath)) {
            return $this->getBinaryFileStream($version);
        }

        return null;
    }

    public function getBinaryFileStream(Version $version): mixed
    {
        return $this->storage->readStream($this->getBinaryStoragePath($version));
    }

    public function getFileStream(Version $version): mixed
    {
        return $this->storage->readStream($this->getStorageFilename($version->getId(), $version->getCid(), $version->getCtype()));
    }

    public function getStorageFilename(int    $id,
                                       int    $cId,
                                       string $cType): string
    {
        $group = floor($cId / 10000) * 10000;

        return $cType . '/g' . $group . '/' . $cId . '/' . $id;
    }

    public function getBinaryStoragePath(Version $version): string
    {
        $binaryFileId = $version->getBinaryFileId() ?? $version->getId();

        return $this->getStorageFilename($binaryFileId, $version->getCid(), $version->getCtype()) . '.bin';
    }

    public function save(Version $version, string $metaData, mixed $binaryDataStream): void
    {
        $this->storage->write($this->getStorageFilename($version->getId(), $version->getCid(), $version->getCtype()), $metaData);
        $binaryStoragePath = $this->getBinaryStoragePath($version);

        // assets are kinda special because they can contain massive amount of binary data which isn't serialized, we append it to the data file
        if (isset($binaryDataStream) === true && !$this->isBinaryStored($binaryStoragePath, $binaryDataStream)) {
            // the binary is written under a temporary name and moved into place, so an aborted write never leaves
            // an incomplete file that later versions with the same hash would reuse
            $tmpPath = $binaryStoragePath . '.' . bin2hex(random_bytes(8)) . '.tmp';

            try {
                $linked = false;

                // we always try to create a hardlink onto the original file, the asset ensures that not the actual
                // inodes get overwritten but creates new inodes if the content changes. This is done by deleting the
                // old file first before opening a new stream -> see Asset::update()
                $useHardlinks = Config::getSystemConfiguration('assets')['versions']['use_hardlinks'];
                if ($useHardlinks && stream_is_local($binaryDataStream)) {
                    $this->storage->write($tmpPath, '1'); // temp file to determine if the storage is local or not
                    $probeStream = $this->storage->readStream($tmpPath);
                    $probeUri = stream_is_local($probeStream) ? stream_get_meta_data($probeStream)['uri'] : null;
                    fclose($probeStream);
                    $this->storage->delete($tmpPath);

                    if ($probeUri) {
                        $linkPath = dirname($probeUri) . '/' . basename($binaryStoragePath);
                        if (is_file($linkPath)) {
                            // an incomplete binary of an earlier attempt
                            unlink($linkPath);
                        }
                        $linked = @link(stream_get_meta_data($binaryDataStream)['uri'], $linkPath);
                    }
                }

                if (!$linked) {
                    $this->storage->writeStream($tmpPath, $binaryDataStream);
                    $this->storage->move($tmpPath, $binaryStoragePath);
                }
            } catch (Throwable $e) {
                if ($this->storage->fileExists($tmpPath)) {
                    $this->storage->delete($tmpPath);
                }

                throw $e;
            }
        }
    }

    /**
     * An existing binary is reused only if its size matches, which also replaces the 1-byte placeholders that
     * aborted writes of earlier releases left behind.
     *
     * @param resource $binaryDataStream
     */
    private function isBinaryStored(string $binaryStoragePath, mixed $binaryDataStream): bool
    {
        if (!$this->storage->fileExists($binaryStoragePath)) {
            return false;
        }

        $size = fstat($binaryDataStream)['size'] ?? null;

        return $size === null || $this->storage->fileSize($binaryStoragePath) === $size;
    }

    public function delete(Version $version,
                           bool    $isBinaryHashInUse): void
    {
        $binaryStoragePath = $this->getBinaryStoragePath($version);
        $storageFileName = $this->getStorageFilename($version->getId(), $version->getCid(), $version->getCtype());

        $storagePath = dirname($storageFileName);
        if ($this->storage->fileExists($storageFileName)) {
            $this->storage->delete($storageFileName);
            File::recursiveDeleteEmptyDirs($this->storage, $storagePath);
        }

        if ($this->storage->fileExists($binaryStoragePath) && !$isBinaryHashInUse) {
            $this->storage->delete($binaryStoragePath);
        }
    }

    public function getStorageType(?int $metaDataSize = null,
                                   ?int $binaryDataSize = null): string
    {
        return 'fs';
    }
}
