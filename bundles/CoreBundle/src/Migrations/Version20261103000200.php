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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Concurrent hits of the same URI could insert several http_error_log rows. They are merged here, the unique index on
 * the hash lets the listener count hits with a single upsert.
 */
final class Version20261103000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Merge duplicate http_error_log rows and make uri_hash unique';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('http_error_log')) {
            return;
        }

        $this->addSql('DELETE FROM `http_error_log` WHERE `uri_hash` IS NULL');
        $this->addSql(
            'UPDATE `http_error_log` `log` INNER JOIN (
                SELECT MIN(`id`) AS `id`, SUM(`count`) AS `total`, MAX(`date`) AS `lastDate`
                FROM `http_error_log` GROUP BY `uri_hash` HAVING COUNT(*) > 1
            ) `merged` ON `log`.`id` = `merged`.`id`
            SET `log`.`count` = `merged`.`total`, `log`.`date` = `merged`.`lastDate`'
        );
        $this->addSql(
            'DELETE `log` FROM `http_error_log` `log` INNER JOIN (
                SELECT `uri_hash`, MIN(`id`) AS `id` FROM `http_error_log` GROUP BY `uri_hash`
            ) `kept` ON `log`.`uri_hash` = `kept`.`uri_hash` AND `log`.`id` <> `kept`.`id`'
        );
        $this->addSql(
            'ALTER TABLE `http_error_log` DROP INDEX `uri_hash`, ' .
            'MODIFY `uri_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, ' .
            'ADD UNIQUE KEY `uri_hash` (`uri_hash`)'
        );
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('http_error_log')) {
            return;
        }

        $this->addSql(
            'ALTER TABLE `http_error_log` DROP INDEX `uri_hash`, ' .
            'MODIFY `uri_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL, ' .
            'ADD KEY `uri_hash` (`uri_hash`)'
        );
    }
}
