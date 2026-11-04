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

namespace Pimcore\Bundle\UuidBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230203160742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modify `itemId` column type in `uuids` db table';
    }

    /**
     * MySQL commits DDL implicitly, so a wrapping transaction would only fail on commit.
     */
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        // the migration path is registered even when the bundle is not installed; the installer creates the final schema
        if (!$schema->hasTable('uuids')) {
            return;
        }

        $this->addSql('ALTER TABLE `uuids` MODIFY COLUMN `itemId` VARCHAR(50) NOT NULL;');
    }

    /**
     * Class definition rows store string ids (e.g. `news`), which an INT column would reject or truncate to 0.
     */
    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('uuids')) {
            return;
        }

        $this->throwIrreversibleMigrationException('`uuids`.`itemId` holds string ids of class definitions and cannot be changed back to INT.');
    }
}
