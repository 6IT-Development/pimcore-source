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

namespace Pimcore\Model\Asset\WebDAV;

use Pimcore\Model\Asset;
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Tool\Serialize;
use Throwable;

/**
 * @internal
 */
class Service
{
    private const string DELETE_LOG_PREFIX = 'webdav-delete-';

    private const string DELETE_LOG_TAG = 'webdav-delete';

    /**
     * Seconds a deleted asset can be restored by a following MOVE onto its path
     */
    private const int DELETE_LOG_LIFETIME = 30;

    /**
     * The entries are per user, only the user who deleted the asset can restore it
     */
    public static function addToDeleteLog(Asset $asset, int $userId): void
    {
        $asset->setInDumpState(true);
        $data = Serialize::serialize($asset);
        $asset->setInDumpState(false);

        TmpStore::set(self::getDeleteLogId($asset->getRealFullPath(), $userId), $data, self::DELETE_LOG_TAG, self::DELETE_LOG_LIFETIME);
    }

    public static function getFromDeleteLog(string $path, int $userId): ?Asset
    {
        $item = TmpStore::get(self::getDeleteLogId($path, $userId));
        if (!$item) {
            return null;
        }

        try {
            $asset = Serialize::unserialize($item->getData());
        } catch (Throwable) {
            // e.g. a class of the serialized asset no longer exists
            return null;
        }

        return $asset instanceof Asset ? $asset : null;
    }

    private static function getDeleteLogId(string $path, int $userId): string
    {
        return self::DELETE_LOG_PREFIX . $userId . '-' . md5($path);
    }
}
