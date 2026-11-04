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

namespace Pimcore\Bundle\SeoBundle\Controller;

use Pimcore\Bundle\SeoBundle\Config;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the robots.txt configured per site in the admin UI (route `pimcore_bundle_seo_robots_txt`, imported before
 * the "allow all" fallback of CoreBundle).
 *
 * @internal
 */
final class RobotsTxtController
{
    /**
     * Served when no robots.txt is configured for the site: allow robots to index everything
     */
    private const string DEFAULT_CONTENT = "User-agent: *\nDisallow:";

    /**
     * Clients and proxies may keep the file this long; a saved change is visible after at most this time. Conditional
     * requests (ETag) are answered with 304.
     */
    private const int MAX_AGE = 3600;

    public function __construct(private readonly SiteResolver $siteResolver)
    {
    }

    public function __invoke(Request $request): Response
    {
        // the site the routing layer resolved for this request, 0 is the main site
        $siteId = $this->siteResolver->getSite($request)?->getId() ?? 0;

        // an empty robots.txt configured for the site is served as is
        $content = Config::getRobotsConfig()[$siteId] ?? self::DEFAULT_CONTENT;

        $response = new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
        $response->setPublic();
        $response->setMaxAge(self::MAX_AGE);
        $response->setEtag(hash('xxh128', $content));
        $response->isNotModified($request);

        return $response;
    }
}
