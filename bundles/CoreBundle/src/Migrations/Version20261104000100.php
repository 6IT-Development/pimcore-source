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
 * website_settings.siteId = 0 (from Version20210323082921) and NULL both mean "all sites". WebsiteSetting stores NULL
 * now, the legacy rows are converted to it.
 */
final class Version20261104000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert website_settings.siteId = 0 to NULL';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('website_settings')) {
            return;
        }

        $this->addSql('UPDATE `website_settings` SET `siteId` = NULL WHERE `siteId` = 0');
    }

    /**
     * Nothing to restore, 0 and NULL have the same meaning.
     */
    public function down(Schema $schema): void
    {
    }
}
