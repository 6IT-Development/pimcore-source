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

use Pimcore\Tool\Admin;
use Pimcore\Tool\MaintenanceModeHelperInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 */
final class MaintenancePageListener implements EventSubscriberInterface
{
    /**
     * used when no template could be loaded, so the maintenance page is never empty
     */
    private const string FALLBACK_TEMPLATE_CODE = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Maintenance</title></head>'
        . '<body><h1>Temporarily down for maintenance</h1><p>Please try again later.</p></body></html>';

    private ?string $templateCode = null;

    private bool $defaultTemplateLoaded = false;

    /**
     * @param string[] $excludedIps client IPs or CIDR ranges that never get the maintenance page
     * @param string $defaultTemplateResource loaded on first use, unless a template was set before
     */
    public function __construct(
        private readonly KernelInterface                $kernel,
        private readonly MaintenanceModeHelperInterface $maintenanceModeHelper,
        #[Autowire(param: 'pimcore.maintenance_mode.excluded_ips')]
        private readonly array                   $excludedIps = ['127.0.0.1', '::1'],
        private readonly string                  $defaultTemplateResource = '@PimcoreCoreBundle/public/misc/maintenance.html'
    )
    {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            // after Symfony's SessionListener (128), before the full page cache
            KernelEvents::REQUEST => ['onKernelRequest', ListenerPriority::MAINTENANCE_PAGE],
        ];
    }

    public function setTemplateCode(string $code): void
    {
        $this->templateCode = $code;
    }

    public function getTemplateCode(): ?string
    {
        // only read the default template when the maintenance page is actually shown, not on every request
        if (null === $this->templateCode && !$this->defaultTemplateLoaded) {
            $this->defaultTemplateLoaded = true;
            $this->loadTemplateFromResource($this->defaultTemplateResource);
        }

        return $this->templateCode;
    }

    public function loadTemplateFromPath(string $path): void
    {
        $this->loadTemplateFromFile($this->kernel->getProjectDir() . $path);
    }

    public function loadTemplateFromResource(string $path): void
    {
        try {
            $templateFile = $this->kernel->locateResource($path);
        } catch (InvalidArgumentException) {
            // a missing template must not break the request, the fallback template is used instead
            return;
        }

        $this->loadTemplateFromFile($templateFile);
    }

    private function loadTemplateFromFile(string $templateFile): void
    {
        if (is_file($templateFile) && ($code = file_get_contents($templateFile)) !== false) {
            $this->setTemplateCode($code);
        }
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $maintenance = false;
        // getSession() throws when sessions are disabled (e.g. headless setups)
        $requestSessionId = $request->hasSession() ? $request->getSession()->getId() : null;

        if ($this->maintenanceModeHelper->isActive($requestSessionId)) {
            $maintenance = true;
        } else {
            $file = Admin::getMaintenanceModeFile();

            if (!is_file($file)) {
                return;
            }

            $conf = include($file);
            if (isset($conf['sessionId'])) {
                $maintenance = $conf['sessionId'] !== $requestSessionId;
            } else {
                @unlink($file);
            }
        }

        // do not activate the maintenance for the server itself
        // this is to avoid problems with monitoring agents
        $clientIp = $request->getClientIp();
        if (!$maintenance || ($clientIp !== null && IpUtils::checkIp($clientIp, $this->excludedIps))) {
            return;
        }

        $response = new Response($this->getTemplateCode() ?: self::FALLBACK_TEMPLATE_CODE, Response::HTTP_SERVICE_UNAVAILABLE);
        $event->setResponse($response);
    }
}
