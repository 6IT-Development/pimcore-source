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
use Pimcore\Model\Translation;

/**
 * The table of a translation domain was created only by its first save, so a registered domain without one (e.g.
 * `admin` on a fresh install) was not a valid domain: the Translator never recorded its missing keys and a listing of
 * it threw. Saves no longer run DDL up front either.
 */
final class Version20261104000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the missing translation tables of the registered domains';
    }

    public function up(Schema $schema): void
    {
        foreach (Translation::getRegisteredDomains() as $domain) {
            if (!preg_match(Translation::DOMAIN_PATTERN, $domain)
                || $schema->hasTable(Translation\Dao::TABLE_PREFIX . $domain)) {
                continue;
            }

            $this->addSql(Translation\Dao::getCreateTableSql($domain));
        }
    }

    /**
     * The tables may hold translations saved since, they are kept.
     */
    public function down(Schema $schema): void
    {
    }
}
