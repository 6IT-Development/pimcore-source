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
 * http_error_log.uri was utf8mb3, so 4-byte characters got mangled on insert and the row was not found again. utf8mb4
 * no longer fits into a full index on the column, the lookup therefore moves to a hash of the stored URI.
 */
final class Version20261103000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add uri_hash to http_error_log and switch uri to utf8mb4';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('http_error_log') || $schema->getTable('http_error_log')->hasColumn('uri_hash')) {
            return;
        }

        $dropIndex = $schema->getTable('http_error_log')->hasIndex('uri') ? 'DROP INDEX `uri`, ' : '';

        $this->addSql(
            'ALTER TABLE `http_error_log` ' . $dropIndex .
            'MODIFY `uri` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL, ' .
            'ADD COLUMN `uri_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL AFTER `uri`, ' .
            'ADD KEY `uri_hash` (`uri_hash`)'
        );
        $this->addSql('UPDATE `http_error_log` SET `uri_hash` = SHA2(`uri`, 256)');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('http_error_log') || !$schema->getTable('http_error_log')->hasColumn('uri_hash')) {
            return;
        }

        $this->addSql(
            'ALTER TABLE `http_error_log` DROP INDEX `uri_hash`, DROP COLUMN `uri_hash`, ' .
            'MODIFY `uri` varchar(1024) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL, ADD KEY `uri` (`uri`)'
        );
    }
}
