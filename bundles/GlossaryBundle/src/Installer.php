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

namespace Pimcore\Bundle\GlossaryBundle;

use Pimcore\Db;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

/**
 * @internal
 */
class Installer extends SettingsStoreAwareInstaller
{
    protected const string USER_PERMISSIONS_CATEGORY = 'Pimcore Glossary Bundle';

    protected const array USER_PERMISSIONS = [
        'glossary',
    ];

    public function install(): void
    {
        $this->installDatabaseTable();
        $this->addUserPermission();
        parent::install();
    }

    public function uninstall(): void
    {
        $this->uninstallDatabaseTable();
        $this->removeUserPermission();
        parent::uninstall();
    }

    private function addUserPermission(): void
    {
        $db = Db::get();

        foreach (self::USER_PERMISSIONS as $permission) {
            // the permission can already exist after a partially failed install, from Pimcore 10 or when it was
            // added by hand
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

        // users and roles keep their granted permissions as a comma separated list; without this, a re-install would
        // grant the permission again
        $rows = $db->fetchAllAssociative("SELECT `id`, `permissions` FROM `users` WHERE `permissions` IS NOT NULL AND `permissions` <> ''");
        foreach ($rows as $row) {
            $permissions = explode(',', (string)$row['permissions']);
            $remaining = array_values(array_diff($permissions, self::USER_PERMISSIONS));
            if (count($remaining) !== count($permissions)) {
                $db->update('users', ['permissions' => implode(',', $remaining)], ['id' => $row['id']]);
            }
        }
    }

    private function installDatabaseTable(): void
    {
        $sqlPath = __DIR__ . '/Resources/install/';
        $sqlFileNames = ['install.sql'];
        $db = Db::get();

        foreach ($sqlFileNames as $fileName) {
            $statement = file_get_contents($sqlPath . $fileName);
            $db->executeQuery($statement);
        }
    }

    private function uninstallDatabaseTable(): void
    {
        $sqlPath = __DIR__ . '/Resources/uninstall/';
        $sqlFileNames = ['uninstall.sql'];
        $db = Db::get();

        foreach ($sqlFileNames as $fileName) {
            $statement = file_get_contents($sqlPath . $fileName);
            $db->executeQuery($statement);
        }
    }
}
