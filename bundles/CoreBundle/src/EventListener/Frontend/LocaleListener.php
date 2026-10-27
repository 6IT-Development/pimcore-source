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

use Locale;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class LocaleListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    private const string ATTRIBUTE_CONTENT_LANGUAGE = '_pimcore_content_language';

    private const string ATTRIBUTE_PARENT_LOCALE = '_pimcore_parent_setlocale';

    /**
     * Locale last passed to setlocale(), which is process-wide
     */
    protected ?string $lastLocale = null;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1], // need to be after ElementListener
            KernelEvents::RESPONSE => ['onKernelResponse'], // need to be after ElementListener
            KernelEvents::FINISH_REQUEST => ['onKernelFinishRequest'],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $locale = $request->getLocale();

        if ($locale && $event->isMainRequest()) {
            // per request: $lastLocale may come from a previous request (worker runtimes) or a sub-request
            $request->attributes->set(self::ATTRIBUTE_CONTENT_LANGUAGE, $locale);
        }

        if ($locale && $locale != $this->lastLocale) {
            if (!$event->isMainRequest() && $this->lastLocale) {
                // setlocale() is process-wide, restore the parent's locale when the sub-request is finished
                $request->attributes->set(self::ATTRIBUTE_PARENT_LOCALE, $this->lastLocale);
            }

            $this->applyLocale($locale);
        }
    }

    public function onKernelFinishRequest(FinishRequestEvent $event): void
    {
        $request = $event->getRequest();
        $parentLocale = $request->attributes->get(self::ATTRIBUTE_PARENT_LOCALE);

        if ($parentLocale) {
            $request->attributes->remove(self::ATTRIBUTE_PARENT_LOCALE);

            if ($parentLocale !== $this->lastLocale) {
                $this->applyLocale($parentLocale);
            }
        }
    }

    private function applyLocale(string $locale): void
    {
        $this->lastLocale = $locale;

        // now we prepare everything for setlocale()
        $localeList = [$locale . '.utf8'];
        $primaryLanguage = Locale::getPrimaryLanguage($locale);

        if (Locale::getRegion($locale)) {
            // add only the language to the list as a fallback
            $localeList[] = $primaryLanguage . '.utf8';
        } else {
            // try to get a list of territories for this language
            // usually OS have no "language only" locale, only the combination language-territory (eg. Debian)
            $languageRegionMapping = include PIMCORE_PATH . '/bundles/CoreBundle/public/misc/cldr-language-territory-mapping.php';
            if (isset($languageRegionMapping[$primaryLanguage])) {
                foreach ($languageRegionMapping[$primaryLanguage] as $territory) {
                    $localeList[] = $primaryLanguage . '_' . $territory . '.utf8';
                }
            }
        }

        setlocale(LC_ALL, $localeList);
        setlocale(LC_NUMERIC, 'C');
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $response = $event->getResponse();
        if (!$this->isHtmlResponse($response)) {
            return;
        }

        $locale = $request->attributes->get(self::ATTRIBUTE_CONTENT_LANGUAGE);
        if ($locale) {
            $response->headers->set('Content-Language', strtolower(str_replace('_', '-', $locale)));
        }
    }

    private function isHtmlResponse(Response $response): bool
    {
        if ($response->isRedirection() || $response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }

        // the content type is set by Response::prepare() after kernel.response if it is missing, HTML is the default
        $contentType = $response->headers->get('Content-Type');

        return $contentType === null || str_contains(strtolower($contentType), 'html');
    }
}
