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

use League\Flysystem\FilesystemException;
use Pimcore\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;
use Pimcore\Db;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Pimcore\Tool\Storage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class Installer extends SettingsStoreAwareInstaller
{
    protected const string USER_PERMISSIONS_CATEGORY = 'Pimcore Application Logger Bundle';

    protected const array USER_PERMISSIONS = ['application_logging'];

    public function __construct(
        BundleInterface $bundle,
        #[Autowire(param: 'pimcore_application_logger.archive_alternative_database')]
        private readonly string $archiveAlternativeDatabase
    )
    {
        parent::__construct($bundle);
    }

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
        $this->dropArchiveTables();
        $this->deleteFileObjects();

        parent::uninstall();
    }

    private function createApplicationLogTable(): void
    {
        $db = Db::get();

        $columns = [];
        foreach (ApplicationLoggerDb::COLUMNS as $name => $definition) {
            $columns[] = $db->quoteIdentifier($name) . ' ' . $definition . ($name === 'id' ? ' AUTO_INCREMENT' : '');
        }

        $db->executeStatement('CREATE TABLE IF NOT EXISTS ' . $db->quoteIdentifier(ApplicationLoggerDb::TABLE_NAME) . ' (
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

        $db->executeStatement('DROP TABLE IF EXISTS `application_logs`;');
    }

    /**
     * Also looks in the default database, the alternative one may have been configured after the first tables were archived
     */
    private function dropArchiveTables(): void
    {
        $db = Db::get();

        foreach (array_unique(array_filter([$db->getDatabase(), $this->archiveAlternativeDatabase])) as $database) {
            $tables = $db->fetchFirstColumn(
                'SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name LIKE ?',
                [$database, ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX . '_%']
            );

            foreach ($tables as $table) {
                if (preg_match('/^' . ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX . '_\d{4}_\d{2}$/', $table)) {
                    $db->executeStatement('DROP TABLE IF EXISTS ' . $db->quoteIdentifier($database) . '.' . $db->quoteIdentifier($table));
                }
            }
        }
    }

    private function deleteFileObjects(): void
    {
        $storage = Storage::get('application_log');

        try {
            foreach ($storage->listContents('/') as $item) {
                if ($item->isDir()) {
                    $storage->deleteDirectory($item->path());
                } else {
                    $storage->delete($item->path());
                }
            }
        } catch (FilesystemException $e) {
            // the bundle is gone at this point, leftover files must not make the uninstall fail
            $this->output->writeln('Could not delete all file objects of the application logger: ' . $e->getMessage());
        }
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
    }
}
