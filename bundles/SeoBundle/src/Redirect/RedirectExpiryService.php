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

namespace Pimcore\Bundle\SeoBundle\Redirect;

use Doctrine\DBAL\Connection;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Cache;

/**
 * Handles expired redirects in bulk and clears the redirect cache once.
 *
 * Redirect model events are not dispatched for these rows.
 *
 * @internal
 */
final readonly class RedirectExpiryService
{
    public function __construct(private Connection $db)
    {
    }

    /**
     * Maintenance: deletes expired auto-created redirects, they only exist for a limited time, and deactivates
     * expired manual redirects, so an editor can still see and extend them.
     *
     * @return int number of affected redirects
     */
    public function cleanup(): int
    {
        $now = time();

        $affected = (int)$this->db->executeStatement(
            'DELETE FROM redirects WHERE `type` = :typeAuto AND expiry IS NOT NULL AND expiry < :now',
            ['typeAuto' => Redirect::TYPE_AUTO_CREATE, 'now' => $now]
        );

        $affected += (int)$this->db->executeStatement(
            'UPDATE redirects SET active = 0, modificationDate = :now
                WHERE active = 1 AND expiry IS NOT NULL AND expiry < :now',
            ['now' => $now]
        );

        $this->clearCache($affected);

        return $affected;
    }

    /**
     * Deletes all expired redirects (manual cleanup in the admin).
     *
     * @return int number of deleted redirects
     */
    public function deleteExpired(): int
    {
        $affected = (int)$this->db->executeStatement(
            'DELETE FROM redirects WHERE expiry IS NOT NULL AND expiry < :now',
            ['now' => time()]
        );

        $this->clearCache($affected);

        return $affected;
    }

    private function clearCache(int $affected): void
    {
        if ($affected > 0) {
            Cache::clearTag('redirect');
        }
    }
}
