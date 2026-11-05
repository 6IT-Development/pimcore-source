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

final class Version20261105000300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the indexed job name column to job runs and fill it from the serialized job';
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

        $table = $schema->getTable(TableConstants::JOB_RUN_TABLE);
        if (!$table->hasColumn('jobName')) {
            $this->addSql(
                'ALTER TABLE `' . TableConstants::JOB_RUN_TABLE . '`' .
                ' ADD `jobName` VARCHAR(' . JobRun::JOB_NAME_LENGTH . ') CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL' .
                ' AFTER `serializedJob`'
            );
        }

        if (!$table->hasIndex('idx_job_name')) {
            $this->addSql('ALTER TABLE `' . TableConstants::JOB_RUN_TABLE . '` ADD INDEX `idx_job_name` (`jobName`)');
        }

        // the name is a top-level property of the serialized job
        $this->addSql(
            'UPDATE `' . TableConstants::JOB_RUN_TABLE . '`' .
            ' SET `jobName` = LEFT(JSON_UNQUOTE(JSON_EXTRACT(`serializedJob`, \'$.name\')), ' .
            JobRun::JOB_NAME_LENGTH . ')' .
            ' WHERE `jobName` IS NULL AND `serializedJob` IS NOT NULL AND JSON_VALID(`serializedJob`)'
        );
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(TableConstants::JOB_RUN_TABLE)) {
            return;
        }

        $table = $schema->getTable(TableConstants::JOB_RUN_TABLE);
        if ($table->hasIndex('idx_job_name')) {
            $this->addSql('ALTER TABLE `' . TableConstants::JOB_RUN_TABLE . '` DROP INDEX `idx_job_name`');
        }

        if ($table->hasColumn('jobName')) {
            $this->addSql('ALTER TABLE `' . TableConstants::JOB_RUN_TABLE . '` DROP COLUMN `jobName`');
        }
    }
}
