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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Constants\TableConstants;

final class Version20261105000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the log of job runs as LONGTEXT, a TEXT column overflows for large jobs';
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
        if (!$schema->hasTable(TableConstants::JOB_RUN_TABLE)) {
            return;
        }

        $this->addSql('ALTER TABLE `' . TableConstants::JOB_RUN_TABLE . '` MODIFY `log` LONGTEXT NULL');
    }

    /**
     * Logs longer than 64 KB would be cut or rejected by a TEXT column.
     */
    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(TableConstants::JOB_RUN_TABLE)) {
            return;
        }

        $this->throwIrreversibleMigrationException(
            'The log of job runs may exceed the 64 KB of a TEXT column and cannot be changed back without losing lines.'
        );
    }
}
