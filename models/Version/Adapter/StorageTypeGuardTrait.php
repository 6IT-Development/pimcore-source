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

use Pimcore\Logger;
use Pimcore\Model\Version;
use RuntimeException;

/**
 * For adapters that handle a single storage type: a version written by another adapter (e.g. before the adapter
 * service was switched) is not in this storage. Reading it would find nothing and deleting it would orphan its data,
 * only DelegateVersionStorageAdapter dispatches by the stored type.
 *
 * @internal
 */
trait StorageTypeGuardTrait
{
    private function isStoredHere(Version $version): bool
    {
        $storageType = $version->getStorageType();

        return $storageType === null || $storageType === $this->getStorageType();
    }

    private function getForeignStorageTypeMessage(Version $version): string
    {
        return sprintf(
            'Version %s is stored in storage type "%s", but %s only handles "%s". Configure %s with an adapter for each storage type in use to keep these versions readable.',
            $version->getId(),
            $version->getStorageType(),
            static::class,
            $this->getStorageType(),
            DelegateVersionStorageAdapter::class
        );
    }

    private function logIfStoredElsewhere(Version $version): bool
    {
        if ($this->isStoredHere($version)) {
            return false;
        }

        Logger::error($this->getForeignStorageTypeMessage($version));

        return true;
    }

    /**
     * @throws RuntimeException
     */
    private function assertStoredHere(Version $version): void
    {
        if (!$this->isStoredHere($version)) {
            throw new RuntimeException($this->getForeignStorageTypeMessage($version));
        }
    }
}
