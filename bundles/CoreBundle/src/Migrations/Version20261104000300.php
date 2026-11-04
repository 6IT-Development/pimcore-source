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
use Pimcore\Cache;
use Pimcore\Model\Site;

/**
 * Site domains were stored as entered, while lookups use the lower-case punycode host of the request: IDN domains were
 * never found and an upper-case main domain redirected to itself. The rows are read raw, a row that does not load as a
 * site must not stop the migration.
 */
final class Version20261104000300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalise the domains and main domains of the sites (trimmed, lower case, punycode)';
    }

    public function up(Schema $schema): void
    {
        $rows = $this->connection->fetchAllAssociative('SELECT id, domains, mainDomain FROM sites');
        foreach ($rows as $row) {
            $domains = is_string($row['domains']) && $row['domains'] !== ''
                ? @unserialize($row['domains'], ['allowed_classes' => false])
                : [];
            if (!is_array($domains)) {
                $this->warnIf(true, sprintf('Site %d: the stored domains are not readable and were left unchanged', $row['id']));

                continue;
            }

            $normalized = array_values(array_unique(array_filter(array_map(
                static fn (mixed $domain): string => Site::normalizeDomain((string) $domain),
                $domains
            ))));
            $mainDomain = Site::normalizeDomain((string) $row['mainDomain']);

            if ($normalized !== $domains || $mainDomain !== (string) $row['mainDomain']) {
                $this->addSql(
                    'UPDATE sites SET domains = ?, mainDomain = ? WHERE id = ?',
                    [serialize($normalized), $mainDomain, $row['id']]
                );
            }
        }
    }

    public function postUp(Schema $schema): void
    {
        // cached sites and domain lookups were made with the old values
        Cache::clearTag('site');
    }

    /**
     * The original spelling is not kept, the normalised domains match the same hosts.
     */
    public function down(Schema $schema): void
    {
    }
}
