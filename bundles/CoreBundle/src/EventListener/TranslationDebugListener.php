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
use Pimcore\Tool\Authentication;
use Pimcore\Translation\Translator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
class TranslationDebugListener implements EventSubscriberInterface, ResetInterface
{
    private bool $translationsDisabled = false;

    public function __construct(
        private readonly Translator $translator,
        private readonly string     $parameterName
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::FINISH_REQUEST => 'onKernelFinishRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (empty($this->parameterName)) {
            return;
        }

        if ($event->getRequest()->query->get($this->parameterName)) {
            if (Pimcore::inDebugMode() || Authentication::authenticateSession($event->getRequest())) {
                $this->translator->setDisableTranslations(true);
                $this->translationsDisabled = true;
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
     * The translator is shared, so translations must only be disabled for the current request
     */
    public function reset(): void
    {
        if ($this->translationsDisabled) {
            $this->translator->setDisableTranslations(false);
            $this->translationsDisabled = false;
        }
    }
}
