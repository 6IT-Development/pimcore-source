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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Closure;
use Pimcore\Controller\KernelControllerEventInterface;
use Pimcore\Controller\KernelResponseEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use WeakMap;

/**
 * @internal
 */
class EventedControllerListener implements EventSubscriberInterface
{
    /**
     * Controller of each request, kept out of the request attributes so the profiler doesn't clone and dump it
     *
     * @var WeakMap<Request, object>
     */
    private WeakMap $controllers;

    public function __construct()
    {
        $this->controllers = new WeakMap();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $callable = $event->getController();

        // [$controller, 'action'] or an invokable controller service; closures have no hooks
        if (is_array($callable)) {
            $controller = $callable[0];
        } elseif (is_object($callable) && !$callable instanceof Closure) {
            $controller = $callable;
        } else {
            return;
        }

        if (!is_object($controller)) {
            return;
        }

        $this->controllers[$event->getRequest()] = $controller;

        if ($controller instanceof KernelControllerEventInterface) {
            $controller->onKernelControllerEvent($event);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $eventController = $this->controllers[$request] ?? null;
        unset($this->controllers[$request]);

        if ($eventController instanceof KernelResponseEventInterface) {
            $eventController->onKernelResponseEvent($event);
        }
    }
}
