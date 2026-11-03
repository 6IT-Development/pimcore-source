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
 * redirects.source/target were varchar(255), document paths, pretty URLs and slugs can be longer and were truncated
 * silently. A utf8mb4 varchar(1024) does not fit into the routing_lookup key, which therefore indexes a prefix of source.
 */
final class Version20261103000300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enlarge redirects.source and redirects.target to varchar(1024)';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('redirects')) {
            return;
        }

        $table = $schema->getTable('redirects');
        if ($table->getColumn('source')->getLength() >= 1024 && $table->getColumn('target')->getLength() >= 1024) {
            return;
        }

        $dropIndex = $table->hasIndex('routing_lookup') ? 'DROP INDEX `routing_lookup`, ' : '';

        $this->addSql(
            'ALTER TABLE `redirects` ' . $dropIndex .
            'MODIFY `source` varchar(1024) DEFAULT NULL, ' .
            'MODIFY `target` varchar(1024) DEFAULT NULL, ' .
            'ADD INDEX `routing_lookup` (`active`, `regex`, `sourceSite`, `source`(255), `type`, `expiry`, `priority`)'
        );
    }

    /**
     * Values longer than 255 characters are truncated (or rejected in strict SQL mode).
     */
    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('redirects')) {
            return;
        }

        $this->addSql(
            'ALTER TABLE `redirects` DROP INDEX `routing_lookup`, ' .
            'MODIFY `source` varchar(255) DEFAULT NULL, ' .
            'MODIFY `target` varchar(255) DEFAULT NULL, ' .
            'ADD INDEX `routing_lookup` (`active`, `regex`, `sourceSite`, `source`, `type`, `expiry`, `priority`)'
        );
    }
}
