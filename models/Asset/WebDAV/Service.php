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

    public static function addToDeleteLog(Asset $asset): void
    {
        $asset->setInDumpState(true);
        $data = Serialize::serialize($asset);
        $asset->setInDumpState(false);

        TmpStore::set(self::getDeleteLogId($asset->getRealFullPath()), $data, self::DELETE_LOG_TAG, self::DELETE_LOG_LIFETIME);
    }

    public static function getFromDeleteLog(string $path): ?Asset
    {
        $item = TmpStore::get(self::getDeleteLogId($path));
        if (!$item) {
            return null;
        }

        $asset = Serialize::unserialize($item->getData());

        return $asset instanceof Asset ? $asset : null;
    }

    private static function getDeleteLogId(string $path): string
    {
        return self::DELETE_LOG_PREFIX . md5($path);
    }
}
