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

namespace Pimcore\Bundle\ApplicationLoggerBundle;

use Pimcore\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;
use Pimcore\Db;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

class Installer extends SettingsStoreAwareInstaller
{
    protected const string USER_PERMISSIONS_CATEGORY = 'Pimcore Application Logger Bundle';

    protected const array USER_PERMISSIONS = ['application_logging'];

    public function install(): void
    {
        $this->addUserPermission();
        $this->createApplicationLogTable();

        parent::install();
    }

    public function uninstall(): void
    {
        $this->removeUserPermission();
        $this->dropApplicationLogTable();

        parent::uninstall();
    }

    private function createApplicationLogTable(): void
    {
        $db = Db::get();

        $columns = [];
        foreach (ApplicationLoggerDb::COLUMNS as $name => $definition) {
            $columns[] = $db->quoteIdentifier($name) . ' ' . $definition . ($name === 'id' ? ' AUTO_INCREMENT' : '');
        }

        $db->executeQuery('CREATE TABLE IF NOT EXISTS ' . $db->quoteIdentifier(ApplicationLoggerDb::TABLE_NAME) . ' (
          ' . implode(",\n          ", $columns) . ',
          PRIMARY KEY (`id`),
          KEY `component` (`component`),
          KEY `timestamp` (`timestamp`),
          KEY `relatedobject` (`relatedobject`),
          KEY `priority` (`priority`),
          KEY `maintenanceChecked` (`maintenanceChecked`)
        ) DEFAULT CHARSET=utf8mb4;');
    }

    private function dropApplicationLogTable(): void
    {
        $db = Db::get();

        $db->executeQuery('DROP TABLE IF EXISTS `application_logs`;');
    }

    private function addUserPermission(): void
    {
        $db = Db::get();

        foreach (self::USER_PERMISSIONS as $permission) {
            $db->insert('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
                $db->quoteIdentifier('category') => self::USER_PERMISSIONS_CATEGORY,
            ]);
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
