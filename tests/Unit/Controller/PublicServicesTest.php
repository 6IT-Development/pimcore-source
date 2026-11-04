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

namespace Pimcore\Tests\Unit\Controller;

use Pimcore\Bundle\CoreBundle\Controller\PublicServicesController;
use Pimcore\Bundle\SeoBundle\Config;
use Pimcore\Bundle\SeoBundle\Controller\RobotsTxtController;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Model\Site;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * robots.txt (SEO bundle and core fallback) and the common files filter; the configuration is set in the runtime cache.
 */
class PublicServicesTest extends TestCase
{
    protected function tearDown(): void
    {
        Config::resetRobotsConfig();

        parent::tearDown();
    }

    /**
     * B-67: the robots.txt of the site the request was resolved to.
     */
    public function testRobotsTxtOfTheSite(): void
    {
        Config::setRobotsConfig([0 => "User-agent: *\nDisallow: /main", 3 => "User-agent: *\nDisallow: /site"]);

        $this->assertSame("User-agent: *\nDisallow: /site", $this->robotsTxt($this->createRequest(3))->getContent());
        $this->assertSame("User-agent: *\nDisallow: /main", $this->robotsTxt($this->createRequest(null))->getContent());
    }

    public function testRobotsTxtFallsBackToAllowAll(): void
    {
        Config::setRobotsConfig([0 => '']);

        // an empty robots.txt is served as is, a site without one gets "allow all"
        $this->assertSame('', $this->robotsTxt($this->createRequest(null))->getContent());
        $this->assertSame("User-agent: *\nDisallow:", $this->robotsTxt($this->createRequest(5))->getContent());
    }

    /**
     * I-69: cacheable, conditional requests are answered with 304.
     */
    public function testRobotsTxtIsCacheable(): void
    {
        Config::setRobotsConfig([0 => "User-agent: *\nDisallow: /private"]);

        $response = $this->robotsTxt($this->createRequest(null));
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertTrue($response->headers->hasCacheControlDirective('public'));
        $this->assertSame(3600, $response->getMaxAge());

        $request = $this->createRequest(null);
        $request->headers->set('If-None-Match', (string) $response->getEtag());
        $this->assertSame(Response::HTTP_NOT_MODIFIED, $this->robotsTxt($request)->getStatusCode());
    }

    public function testCoreRobotsTxtFallbackAllowsAll(): void
    {
        $response = new PublicServicesController()->robotsTxtAction();

        $this->assertSame("User-agent: *\nDisallow:", $response->getContent());
        $this->assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    /**
     * I-68: a plain 404 without a status line in the body.
     */
    public function testCommonFilesAreNotFound(): void
    {
        $response = new PublicServicesController()->commonFilesAction();

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertStringNotContainsString('HTTP/1.1', (string) $response->getContent());
        $this->assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    private function robotsTxt(Request $request): Response
    {
        return (new RobotsTxtController(new SiteResolver(new RequestStack())))($request);
    }

    private function createRequest(?int $siteId): Request
    {
        $request = Request::create('/robots.txt');
        if ($siteId !== null) {
            $site = new Site();
            $site->setId($siteId);
            $request->attributes->set(SiteResolver::ATTRIBUTE_SITE, $site);
        }

        return $request;
    }
}
