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

use Pimcore;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
final class PimcoreContextListener implements EventSubscriberInterface
{
    public const string ATTRIBUTE_PIMCORE_CONTEXT_FORCE_RESOLVING = '_pimcore_context_force_resolving';

    public function __construct(
        private readonly PimcoreContextResolver $resolver,
        private readonly RequestStack           $requestStack,
        private readonly LoggerInterface $logger
    )
    {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            // listeners with a higher priority get a context guessed from the path, see ListenerPriority
            KernelEvents::REQUEST => ['onKernelRequest', ListenerPriority::PIMCORE_CONTEXT],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($event->isMainRequest() || $event->getRequest()->attributes->has(self::ATTRIBUTE_PIMCORE_CONTEXT_FORCE_RESOLVING)) {
            $context = $this->resolver->getPimcoreContext($request);

            if ($context) {
                $this->logger->debug('Resolved pimcore context for path {path} to {context}', [
                    'path' => $request->getPathInfo(),
                    'context' => $context,
                ]);
            } else {
                $this->logger->debug('Could not resolve a pimcore context for path {path}', [
                    'path' => $request->getPathInfo(),
                ]);
            }

            $this->initializeContext($context, $request);
        }
    }

    /**
     * Do context specific initialization
     *
     * An unresolved (null) context is initialized like a non-admin context.
     */
    private function initializeContext(?string $context, Request $request): void
    {
        if ($context === PimcoreContextResolver::CONTEXT_ADMIN) {
            Pimcore::setAdminMode();
            Document::setHideUnpublished(false);
            DataObject::setHideUnpublished(false);
            DataObject\Localizedfield::setGetFallbackValues(false);
        } else {
            Pimcore::unsetAdminMode();
            Document::setHideUnpublished(true);
            DataObject::setHideUnpublished(true);
            DataObject::setGetInheritedValues(true);
            DataObject\Localizedfield::setGetFallbackValues(true);
        }
    }
}
