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
 * 300 Multiple Choices was offered as a redirect status code, but clients do not follow it, so these redirects never
 * worked. The status code was removed from the SeoBundle defaults, existing redirects are switched to 302 Found.
 */
final class Version20261029000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Switch redirects with status code 300 to 302';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('redirects')) {
            return;
        }

        $this->addSql("UPDATE redirects SET statusCode = '302' WHERE statusCode = '300'");
    }

    public function down(Schema $schema): void
    {
        // do nothing, the old value did not redirect
    }
}
