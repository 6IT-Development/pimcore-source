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
 * Every Editlock::lock() inserted another edit_lock row, unlock() removed one of them, so an element stayed locked by
 * a stale row. The newest lock of each element is kept, the unique index lets lock() replace it with an upsert.
 */
final class Version20261104000400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Keep the newest edit lock per element and make (cid, ctype) of edit_lock unique';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'DELETE `lock` FROM `edit_lock` `lock` INNER JOIN (
                SELECT `cid`, `ctype`, MAX(`id`) AS `id` FROM `edit_lock` GROUP BY `cid`, `ctype`
            ) `kept` ON `lock`.`cid` = `kept`.`cid` AND `lock`.`ctype` <=> `kept`.`ctype` AND `lock`.`id` <> `kept`.`id`'
        );
        $this->addSql('ALTER TABLE `edit_lock` DROP INDEX `cidtype`, ADD UNIQUE KEY `cidtype` (`cid`, `ctype`)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `edit_lock` DROP INDEX `cidtype`, ADD KEY `cidtype` (`cid`, `ctype`)');
    }
}
