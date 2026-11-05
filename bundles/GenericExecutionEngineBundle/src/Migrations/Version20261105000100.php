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
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Constants\TableConstants;

final class Version20261105000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make the execution context of job runs NOT NULL, like the JobRun entity maps it';
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

        $this->addSql(
            'UPDATE `' . TableConstants::JOB_RUN_TABLE . '` SET `executionContext` = :context' .
            ' WHERE `executionContext` IS NULL',
            ['context' => JobRun::DEFAULT_EXECUTION_CONTEXT]
        );
        $this->addSql(
            'ALTER TABLE `' . TableConstants::JOB_RUN_TABLE . '` MODIFY `executionContext` VARCHAR(255) NOT NULL' .
            ' DEFAULT ' . $this->connection->quote(JobRun::DEFAULT_EXECUTION_CONTEXT)
        );
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(TableConstants::JOB_RUN_TABLE)) {
            return;
        }

        $this->addSql(
            'ALTER TABLE `' . TableConstants::JOB_RUN_TABLE . '` MODIFY `executionContext` VARCHAR(255) NULL' .
            ' DEFAULT ' . $this->connection->quote(JobRun::DEFAULT_EXECUTION_CONTEXT)
        );
    }
}
