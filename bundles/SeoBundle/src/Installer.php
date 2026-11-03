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

namespace Pimcore\Bundle\SeoBundle;

use Pimcore\Db;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

class Installer extends SettingsStoreAwareInstaller
{
    protected const string USER_PERMISSIONS_CATEGORY = 'Pimcore Seo Bundle';

    protected const array USER_PERMISSIONS = [
        'robots.txt',
        'seo_document_editor',
        'http_errors',
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
            // the permission can already exist after a partially failed install or when it was added by hand
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

        // users and roles keep their granted permissions as a comma separated list
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
        $this->executeSqlFile(__DIR__ . '/Resources/install/install.sql');
    }

    private function uninstallDatabaseTable(): void
    {
        $this->executeSqlFile(__DIR__ . '/Resources/uninstall/uninstall.sql');
    }

    /**
     * Runs the statements one by one; the files only use CREATE/DROP ... IF (NOT) EXISTS, so a re-run after a partial
     * install is safe. DDL commits implicitly in MySQL, a transaction would not roll it back.
     */
    private function executeSqlFile(string $path): void
    {
        $db = Db::get();

        foreach (preg_split('/;\s*(?:\R|$)/', (string)file_get_contents($path)) as $statement) {
            if (trim($statement) !== '') {
                $db->executeStatement($statement);
            }
        }
    }
}
