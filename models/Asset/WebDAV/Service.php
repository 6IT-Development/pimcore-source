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
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
class Service
{
    public static function getDeleteLogFile(): string
    {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . '/webdav-delete.dat';
    }

    public static function getDeleteLog(): array
    {
        if (file_exists(self::getDeleteLogFile())) {
            $log = unserialize(file_get_contents(self::getDeleteLogFile()));
            if (!is_array($log)) {
                return [];
            } else {
                // clean up old entries
                // remove 30 seconds old entries
                return array_filter($log, fn($data) => $data['timestamp'] > (time() - 30));
            }
        }

        return [];
    }

    public static function saveDeleteLog(array $log): void
    {
        // clean up old entries
        // remove 30 seconds old entries

        new Filesystem()->dumpFile(Asset\WebDAV\Service::getDeleteLogFile(), serialize(array_filter($log, fn($data) => $data['timestamp'] > (time() - 30))));
    }
}
