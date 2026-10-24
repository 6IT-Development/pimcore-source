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

namespace Pimcore\Tests\Unit\HttpKernel;

use Pimcore\Bundle\CoreBundle\EventListener\Frontend\FullPageCacheListener;
use Pimcore\Bundle\CoreBundle\EventListener\Frontend\LocaleListener;
use Pimcore\Bundle\CoreBundle\EventListener\ResponseStackListener;
use Pimcore\Cache\FullPage\SessionStatus;
use Pimcore\Config;
use Pimcore\Http\ResponseStack;
use Pimcore\Model\Site;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Services are shared between requests in worker runtimes (FrankenPHP, RoadRunner, Swoole):
 * per-request state must not leak into the next request.
 */
class LongRunningProcessStateTest extends TestCase
{
    public function testResponseStackResponseIsOnlySentOnce(): void
    {
        $stack = new ResponseStack();
        $listener = new ResponseStackListener($stack);

        $redirect = new RedirectResponse('/thank-you');
        $stack->push($redirect);

        $first = $this->createResponseEvent(new Request());
        $listener->onKernelResponse($first);
        $this->assertSame($redirect, $first->getResponse());
        $this->assertFalse($stack->hasResponses());

        $page = new Response('page');
        $second = $this->createResponseEvent(new Request(), $page);
        $listener->onKernelResponse($second);
        $this->assertSame($page, $second->getResponse());
    }

    public function testContentLanguageIsTakenFromTheCurrentRequest(): void
    {
        $listener = new LocaleListener();

        $first = new Request();
        $first->setLocale('de');
        $listener->onKernelRequest(new RequestEvent($this->createMock(HttpKernelInterface::class), $first, HttpKernelInterface::MAIN_REQUEST));
        $firstEvent = $this->createResponseEvent($first);
        $listener->onKernelResponse($firstEvent);
        $this->assertSame('de', $firstEvent->getResponse()->headers->get('Content-Language'));

        // e.g. a response set by a request listener with higher priority, onKernelRequest() didn't run
        $secondEvent = $this->createResponseEvent(new Request());
        $listener->onKernelResponse($secondEvent);
        $this->assertNull($secondEvent->getResponse()->headers->get('Content-Language'));
    }

    public function testFullPageCacheStateIsReset(): void
    {
        $listener = new FullPageCacheListener(
            $this->createMock(SessionStatus::class),
            $this->createMock(EventDispatcherInterface::class),
            new Config()
        );

        $listener->disable('POST request');
        $listener->setLifetime(60);
        $listener->disableExpireHeader();

        $listener->reset();

        $this->assertTrue($listener->isEnabled());
        $this->assertNull($listener->getLifetime());
    }

    public function testCurrentSiteCanBeReset(): void
    {
        Site::setCurrentSite(new Site());
        $this->assertTrue(Site::isSiteRequest());

        Site::resetCurrentSite();
        $this->assertFalse(Site::isSiteRequest());
    }

    private function createResponseEvent(Request $request, ?Response $response = null): ResponseEvent
    {
        return new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response ?? new Response()
        );
    }
}
