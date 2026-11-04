<?php

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

namespace Pimcore\Bundle\UuidBundle;

use Pimcore\Bundle\UuidBundle\Migrations\Version20230203160742;
use Pimcore\Bundle\UuidBundle\Model\Tool\UUID\Dao;
use Pimcore\Db;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

class Installer extends SettingsStoreAwareInstaller
{
    public function install(): void
    {
        $this->installDatabaseTable();
        parent::install();
    }

    /**
     * installDatabaseTable() creates the final schema, so installing marks all bundle migrations up to this one as
     * executed.
     */
    public function getLastMigrationVersionClassName(): ?string
    {
        return Version20230203160742::class;
    }

    public function uninstall(): void
    {
        $this->uninstallDatabaseTable();
        parent::uninstall();
    }

    /**
     * Keeps an existing table, so a re-install does not lose UUIDs imported from other instances.
     */
    protected function installDatabaseTable(): void
    {
        Db::get()->executeStatement(
            'CREATE TABLE IF NOT EXISTS `' . Dao::TABLE_NAME . '` (
                `uuid` CHAR(36) NOT NULL,
                `itemId` VARCHAR(50) NOT NULL,
                `type` VARCHAR(25) NOT NULL,
                `instanceIdentifier` VARCHAR(50) NOT NULL,
                PRIMARY KEY (`uuid`, `itemId`, `type`)
            ) DEFAULT CHARSET=utf8mb4'
        );
    }

    protected function uninstallDatabaseTable(): void
    {
        Db::get()->executeStatement('DROP TABLE IF EXISTS `' . Dao::TABLE_NAME . '`');
    }
}
