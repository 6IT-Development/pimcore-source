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

use Pimcore\Http\RequestHelper;
use Pimcore\Http\RequestMatcherFactory;
use Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener as SymfonyWebDebugToolbarListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Disables the web debug toolbar for frontend requests by admins (iframes inside admin interface)
 *
 * @internal
 */
class WebDebugToolbarListener implements EventSubscriberInterface, ResetInterface
{
    protected ?array $excludeMatchers = null;

    /**
     * Mode of the Symfony toolbar listener before it was disabled for the current request
     */
    private ?int $previousMode = null;

    public function __construct(
        protected RequestHelper                   $requestHelper,
        protected RequestMatcherFactory           $requestMatcherFactory,
        protected ?SymfonyWebDebugToolbarListener $debugToolbarListener,
        protected EventDispatcherInterface        $eventDispatcher,
        protected array                           $excludeRoutes
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelResponse', -118],
            KernelEvents::FINISH_REQUEST => 'onKernelFinishRequest',
        ];
    }

    public function onKernelResponse(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // do not show toolbar on frontend-admin requests
        if ($this->requestHelper->isFrontendRequestByAdmin($request)) {
            $this->disableWebDebugToolbar();
        }

        // do not show toolbar on excluded routes (pimcore.web_profiler.toolbar.excluded_routes config entry)
        /** @var array $excludeMatcher */
        foreach ($this->getExcludeMatchers() as $excludeMatcher) {
            $chainRequestMatcher = new ChainRequestMatcher($excludeMatcher);
            if ($chainRequestMatcher->matches($request)) {
                $this->disableWebDebugToolbar();
            }
        }
    }

    public function onKernelFinishRequest(FinishRequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            $this->reset();
        }
    }

    /**
     * Re-enables the toolbar for the next request
     */
    public function reset(): void
    {
        if ($this->debugToolbarListener && null !== $this->previousMode) {
            $this->debugToolbarListener->setMode($this->previousMode);
        }

        $this->previousMode = null;
    }

    protected function getExcludeMatchers(): array
    {
        if (null === $this->excludeMatchers) {
            $this->excludeMatchers = $this->requestMatcherFactory->buildRequestMatchers($this->excludeRoutes);
        }

        return $this->excludeMatchers;
    }

    protected function disableWebDebugToolbar(): void
    {
        // only for the current request: removing the subscriber would disable the toolbar for all following requests
        // handled by this process (worker runtimes)
        if ($this->debugToolbarListener && null === $this->previousMode) {
            $this->previousMode = $this->debugToolbarListener->isEnabled()
                ? SymfonyWebDebugToolbarListener::ENABLED
                : SymfonyWebDebugToolbarListener::DISABLED;
            $this->debugToolbarListener->setMode(SymfonyWebDebugToolbarListener::DISABLED);
        }
    }
}
