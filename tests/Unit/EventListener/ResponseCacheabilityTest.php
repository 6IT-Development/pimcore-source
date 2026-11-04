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

namespace Pimcore\Tests\Unit\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\Frontend\FullPageCacheListener;
use Pimcore\Bundle\CoreBundle\EventListener\Frontend\StaticPageGeneratorListener;
use Pimcore\Cache\FullPage\FullPageCacheState;
use Pimcore\Cache\FullPage\SessionStatus;
use Pimcore\Config;
use Pimcore\Document\StaticPageGenerator;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Responses that are replayed to every visitor (full page cache, static pages) must not contain visitor specific
 * content (B-36, C-09).
 */
class ResponseCacheabilityTest extends TestCase
{
    public function testFullPageCacheSkipsCookiesAndVaryingResponses(): void
    {
        $this->assertTrue($this->canBeCached(new Response('page')));

        $withCookie = new Response('page');
        $withCookie->headers->setCookie(Cookie::create('csrf', 'token'));
        $this->assertFalse($this->canBeCached($withCookie));

        $this->assertFalse($this->canBeCached(new Response('page', 200, ['Vary' => 'Accept-Language'])));
        $this->assertFalse($this->canBeCached(new Response('page', 200, ['Vary' => 'Accept-Encoding, Cookie'])));
        $this->assertTrue($this->canBeCached(new Response('page', 200, ['Vary' => 'Accept-Encoding, X-Requested-With'])));

        $this->assertFalse($this->canBeCached(new Response('page', 200, ['Cache-Control' => 'no-store'])));
        $this->assertFalse($this->canBeCached(new StreamedResponse(static function (): void {})));
    }

    public function testStaticPagesAreOnlyStoredForPublicResponses(): void
    {
        $this->assertTrue($this->canBeStored(new Request(), new Response('page')));

        $this->assertFalse($this->canBeStored(new Request(), new Response('error', Response::HTTP_INTERNAL_SERVER_ERROR)));
        $this->assertFalse($this->canBeStored(new Request(), new Response('not found', Response::HTTP_NOT_FOUND)));

        $withCookie = new Response('page');
        $withCookie->headers->setCookie(Cookie::create('session', 'abc'));
        $this->assertFalse($this->canBeStored(new Request(), $withCookie));

        $this->assertFalse($this->canBeStored(new Request(['pimcore_override_output_timestamp' => '1']), new Response('page')));
        $this->assertFalse($this->canBeStored(new Request(), new Response('page'), sessionInUse: true));
    }

    private function canBeCached(Response $response): bool
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnArgument(0);

        $listener = new FullPageCacheListener(
            $this->createMock(SessionStatus::class),
            $dispatcher,
            new Config(),
            new FullPageCacheState()
        );

        return (new ReflectionMethod(FullPageCacheListener::class, 'responseCanBeCached'))->invoke($listener, $response);
    }

    private function canBeStored(Request $request, Response $response, bool $sessionInUse = false): bool
    {
        $sessionStatus = $this->createMock(SessionStatus::class);
        $sessionStatus->method('isDisabledBySession')->willReturn($sessionInUse);

        $listener = new StaticPageGeneratorListener(
            $this->createMock(StaticPageGenerator::class),
            $this->createMock(DocumentResolver::class),
            $this->createMock(RequestHelper::class),
            new Config(),
            $sessionStatus
        );

        return (new ReflectionMethod(StaticPageGeneratorListener::class, 'responseCanBeStored'))
            ->invoke($listener, $request, $response);
    }
}
