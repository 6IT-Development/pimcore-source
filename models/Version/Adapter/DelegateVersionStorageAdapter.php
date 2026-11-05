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

use InvalidArgumentException;
use Pimcore\Model\Version;
use RuntimeException;

/**
 * @internal
 */
class DelegateVersionStorageAdapter implements VersionStorageAdapterInterface
{
    /**
     * @var array<string, VersionStorageAdapterInterface>
     */
    private array $adapters = [];

    public function __construct(protected int                            $byteThreshold,
                                protected VersionStorageAdapterInterface $defaultAdapter,
                                protected VersionStorageAdapterInterface $fallbackAdapter)
    {
        // versions only store the type: two adapters of one type (e.g. two database connections) cannot be told
        // apart when a version is read or deleted
        if ($defaultAdapter !== $fallbackAdapter && $defaultAdapter->getStorageType() === $fallbackAdapter->getStorageType()) {
            throw new InvalidArgumentException(sprintf(
                'The default and the fallback version storage adapter both use storage type "%s"',
                $defaultAdapter->getStorageType()
            ));
        }

        $this->adapters[$defaultAdapter->getStorageType()] = $defaultAdapter;
        $this->adapters[$fallbackAdapter->getStorageType()] = $fallbackAdapter;
    }

    /**
     * @throws RuntimeException
     */
    protected function getAdapter(?string $storageType = null): VersionStorageAdapterInterface
    {
        if ($storageType === null || $storageType === '') {
            return $this->defaultAdapter;
        }

        return $this->adapters[$storageType]
            ?? throw new RuntimeException(sprintf('No version storage adapter for storage type "%s" is configured', $storageType));
    }

    public function loadMetaData(Version $version): ?string
    {
        return $this->getAdapter($version->getStorageType())->loadMetaData($version);
    }

    public function loadBinaryData(Version $version): mixed
    {
        return $this->getAdapter($version->getStorageType())->loadBinaryData($version);
    }

    public function getStorageType(?int $metaDataSize = null,
                                   ?int $binaryDataSize = null): string
    {
        if ($metaDataSize > $this->byteThreshold ||
            $binaryDataSize > $this->byteThreshold) {
            return $this->fallbackAdapter->getStorageType($metaDataSize, $binaryDataSize);
        }

        return $this->defaultAdapter->getStorageType($metaDataSize, $binaryDataSize);
    }

    public function save(Version $version, string $metaData, mixed $binaryDataStream): void
    {
        $this->getAdapter($version->getStorageType())->save($version, $metaData, $binaryDataStream);
    }

    public function delete(Version $version, bool $isBinaryHashInUse): void
    {
        $this->getAdapter($version->getStorageType())->delete($version, $isBinaryHashInUse);
    }

    public function getBinaryFileStream(Version $version): mixed
    {
        return $this->getAdapter($version->getStorageType())->getBinaryFileStream($version);
    }

    public function getFileStream(Version $version): mixed
    {
        return $this->getAdapter($version->getStorageType())->getFileStream($version);
    }
}
