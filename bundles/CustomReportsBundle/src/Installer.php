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

namespace Pimcore\Bundle\CustomReportsBundle;

use Pimcore\Db;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

class Installer extends SettingsStoreAwareInstaller
{
    protected const string USER_PERMISSIONS_CATEGORY = 'Pimcore Custom Reports Bundle';

    protected const array USER_PERMISSIONS = [
        'reports',
        'reports_config',
    ];

    public function install(): void
    {
        $this->addUserPermission();
        parent::install();
    }

    public function uninstall(): void
    {
        $this->removeUserPermission();
        parent::uninstall();
    }

    private function addUserPermission(): void
    {
        $db = Db::get();

        foreach (self::USER_PERMISSIONS as $permission) {
            // systems upgraded from Pimcore 10 already have these permissions
            $db->executeStatement(
                'INSERT INTO `users_permission_definitions` (`key`, `category`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `category` = VALUES(`category`)',
                [$permission, self::USER_PERMISSIONS_CATEGORY]
            );
        }
    }

    private function removeUserPermission(): void
    {
        $db = Db::get();

        foreach (self::USER_PERMISSIONS as $permission) {
            $db->delete('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
            ]);
        }
    }
}
