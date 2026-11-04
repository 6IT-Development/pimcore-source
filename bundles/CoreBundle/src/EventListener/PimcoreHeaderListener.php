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

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
final class PimcoreHeaderListener implements EventSubscriberInterface
{
    /**
     * @param bool $poweredByHeader whether to send `X-Powered-By: pimcore`, off by default as it reveals the platform
     */
    public function __construct(
        #[Autowire(param: 'pimcore.general.powered_by_header')]
        private readonly bool $poweredByHeader = false,
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($this->poweredByHeader && $event->isMainRequest()) {
            $event->getResponse()->headers->set('X-Powered-By', 'pimcore');
        }
    }
}
