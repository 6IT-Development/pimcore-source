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

final class Version20251217000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove parametersPost, cookies and serverVars from http_error_log';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('http_error_log')) {
            return;
        }

        $table = $schema->getTable('http_error_log');
        $drops = [];
        foreach (['parametersPost', 'cookies', 'serverVars'] as $column) {
            if ($table->hasColumn($column)) {
                $drops[] = sprintf('DROP COLUMN `%s`', $column);
            }
        }

        if ($drops) {
            $this->addSql('ALTER TABLE `http_error_log` ' . implode(', ', $drops));
        }
    }

    public function down(Schema $schema): void
    {
        // do nothing
    }
}
