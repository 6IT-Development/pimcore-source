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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Exception;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Environment;

/**
 * @internal
 */
class GlobalTemplateVariablesListener implements EventSubscriberInterface, LoggerAwareInterface, ResetInterface
{
    use LoggerAwareTrait;
    use PimcoreContextAwareTrait;

    private const ATTRIBUTE_PUSHED = '_pimcore_template_globals_pushed';

    protected array $globalsStack = [];

    public function __construct(
        protected DocumentResolver $documentResolver,
        protected EditmodeResolver $editmodeResolver,
        protected Environment      $twig
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 15], // has to be after DocumentFallbackListener
            // kernel.finish_request is dispatched even if the sub-request throws, kernel.response is not
            KernelEvents::FINISH_REQUEST => 'onKernelFinishRequest',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $globals = $this->twig->getGlobals();

        try {
            // it could be the case that the Twig environment is already initialized at this point
            // then it's not possible anymore to add globals
            $this->twig->addGlobal('document', $this->documentResolver->getDocument($request));
            $this->twig->addGlobal('editmode', $this->editmodeResolver->isEditmode($request));
            $this->globalsStack[] = $globals;
        } catch (Exception) {
            $this->globalsStack[] = false;
        }

        $request->attributes->set(self::ATTRIBUTE_PUSHED, true);
    }

    public function onKernelFinishRequest(FinishRequestEvent $event): void
    {
        // only pop the entry pushed for this request, never the one of the parent request
        $request = $event->getRequest();
        if (!$request->attributes->get(self::ATTRIBUTE_PUSHED)) {
            return;
        }

        $request->attributes->remove(self::ATTRIBUTE_PUSHED);

        if (count($this->globalsStack)) {
            $this->restoreGlobals(array_pop($this->globalsStack));
        }
    }

    /**
     * Restores the globals from before the first request if a request ended without kernel.finish_request (e.g. a
     * fatal error), so the document of that request is not used by the next one
     */
    public function reset(): void
    {
        if (count($this->globalsStack)) {
            $this->restoreGlobals($this->globalsStack[0]);
        }

        $this->globalsStack = [];
    }

    private function restoreGlobals(array|false $globals): void
    {
        if ($globals !== false) {
            $this->twig->addGlobal('document', $globals['document'] ?? null);
            $this->twig->addGlobal('editmode', $globals['editmode'] ?? false);
        }
    }
}
