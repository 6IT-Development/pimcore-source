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

/**
 * Creates the redirects that are added automatically when a document or URL slug changes its path.
 *
 * @internal
 */
final readonly class AutoRedirectCreator
{
    /**
     * Auto redirects expire after a year and are then removed by the maintenance task.
     */
    public const int TTL = 86400 * 365;

    public function __construct(private Connection $db)
    {
    }

    /**
     * Updates the auto redirect with the same source and source site, or creates one.
     *
     * @param string $target path or document id
     */
    public function createOrUpdate(string $source, string $target, ?int $sourceSite, ?int $targetSite): Redirect
    {
        $id = $this->db->fetchOne(
            'SELECT id FROM redirects WHERE source = :source AND `type` = :typeAuto AND sourceSite <=> :sourceSite
                ORDER BY id LIMIT 1',
            ['source' => $source, 'typeAuto' => Redirect::TYPE_AUTO_CREATE, 'sourceSite' => $sourceSite]
        );

        $redirect = $id !== false ? Redirect::getById((int)$id) : null;
        $redirect ??= new Redirect();

        $redirect->setType(Redirect::TYPE_AUTO_CREATE);
        $redirect->setRegex(false);
        $redirect->setSource($source);
        $redirect->setSourceSite($sourceSite);
        $redirect->setTarget($target);
        $redirect->setTargetSite($targetSite);
        $redirect->setStatusCode(301);
        $redirect->setActive(true);
        $redirect->setExpiry(time() + self::TTL);
        $redirect->save();

        return $redirect;
    }
}
