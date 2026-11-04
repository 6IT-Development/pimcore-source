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

use Pimcore\Bundle\CoreBundle\EventListener\Frontend\EditmodeListener;
use Pimcore\Bundle\CoreBundle\EventListener\Frontend\RoutingListener;
use Pimcore\Bundle\CoreBundle\EventListener\MaintenancePageListener;
use Pimcore\Bundle\CoreBundle\EventListener\PimcoreHeaderListener;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\MaintenanceModeHelperInterface;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Headers the CoreBundle listeners add to responses (I-53) and the host comparison of the main domain redirect (B-41).
 */
class ResponseHeadersTest extends TestCase
{
    public function testPoweredByHeaderIsOptIn(): void
    {
        $off = $this->createResponseEvent();
        (new PimcoreHeaderListener())->onKernelResponse($off);
        $this->assertFalse($off->getResponse()->headers->has('X-Powered-By'));

        $on = $this->createResponseEvent();
        (new PimcoreHeaderListener(true))->onKernelResponse($on);
        $this->assertSame('pimcore', $on->getResponse()->headers->get('X-Powered-By'));

        $subRequest = $this->createResponseEvent(HttpKernelInterface::SUB_REQUEST);
        (new PimcoreHeaderListener(true))->onKernelResponse($subRequest);
        $this->assertFalse($subRequest->getResponse()->headers->has('X-Powered-By'));
    }

    public function testEditmodeFrameAncestorsPolicyIsMergedIntoAnExistingPolicy(): void
    {
        $response = new Response();
        $this->addFrameAncestorsPolicy($response);
        $this->assertSame("frame-ancestors 'self'", $response->headers->get('Content-Security-Policy'));

        $response = new Response('', 200, ['Content-Security-Policy' => "default-src 'self';"]);
        $this->addFrameAncestorsPolicy($response);
        $this->assertSame("default-src 'self'; frame-ancestors 'self'", $response->headers->get('Content-Security-Policy'));

        // a policy that restricts framing itself is kept as it is
        $policy = "default-src 'self'; frame-ancestors https://admin.example.com";
        $response = new Response('', 200, ['Content-Security-Policy' => $policy]);
        $this->addFrameAncestorsPolicy($response);
        $this->assertSame($policy, $response->headers->get('Content-Security-Policy'));
    }

    public function testMaintenancePageIsNotStoredAndAsksForARetry(): void
    {
        $helper = $this->createMock(MaintenanceModeHelperInterface::class);
        $helper->method('isActive')->willReturn(true);

        $listener = new MaintenancePageListener($this->createMock(KernelInterface::class), $helper);

        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.5']);
        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $listener->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $this->assertSame('300', $response->headers->get('Retry-After'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertNotSame('', $response->getContent());
    }

    public function testMainDomainComparisonIgnoresCaseAndHonoursPorts(): void
    {
        $listener = (new ReflectionClass(RoutingListener::class))->newInstanceWithoutConstructor();
        $isRequestHost = new ReflectionMethod(RoutingListener::class, 'isRequestHost');

        $request = Request::create('http://example.com/page');
        $this->assertTrue($isRequestHost->invoke($listener, $request, 'Example.COM'));
        $this->assertFalse($isRequestHost->invoke($listener, $request, 'www.example.com'));

        $request = Request::create('http://example.com:8080/page');
        $this->assertTrue($isRequestHost->invoke($listener, $request, 'example.com:8080'));
        $this->assertFalse($isRequestHost->invoke($listener, $request, 'example.com:9090'));
    }

    private function addFrameAncestorsPolicy(Response $response): void
    {
        $listener = (new ReflectionClass(EditmodeListener::class))->newInstanceWithoutConstructor();
        (new ReflectionMethod(EditmodeListener::class, 'addFrameAncestorsPolicy'))->invoke($listener, $response);
    }

    private function createResponseEvent(int $requestType = HttpKernelInterface::MAIN_REQUEST): ResponseEvent
    {
        return new ResponseEvent($this->createMock(HttpKernelInterface::class), new Request(), $requestType, new Response());
    }
}
