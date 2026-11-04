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

/**
 * Kernel event priorities of the CoreBundle listeners, in one place so their order relative to each other and to the
 * Symfony listeners (in brackets, Symfony 7.4) stays visible. Higher runs first. Listeners not listed use priority 0.
 *
 * kernel.request:
 *   512 ROUTING, 256 [ValidateRequestListener], 128 [SessionListener], 126 MAINTENANCE_PAGE, 120 FULL_PAGE_CACHE,
 *   100 [LocaleListener::setDefaultLocale], 32 [RouterListener], 24 PIMCORE_CONTEXT, 20 DOCUMENT_FALLBACK,
 *   16 [LocaleListener], 8 [Firewall], 7 STATIC_PAGE_GENERATOR, 1 LOCALE, 0 block state / translation debug /
 *   output timestamp, -118 WEB_DEBUG_TOOLBAR
 * kernel.controller:
 *   50 DOCUMENT_FALLBACK_CONTROLLER, 30 ELEMENT, 15 GLOBAL_TEMPLATE_VARIABLES, 0 evented controllers
 * kernel.controller_arguments:
 *   10 RESPONSE_HEADER_ATTRIBUTES
 * kernel.view:
 *   16 CONTENT_TEMPLATE, -128 [TemplateAttributeListener]
 * kernel.response:
 *   100 FULL_PAGE_CACHE_STOP_PROPAGATION, 40 RESPONSE_STACK, 32 RESPONSE_HEADER, 0 editmode / hardlink canonical /
 *   WYSIWYG attribute filter / locale / Pimcore header, -100 [ProfilerListener], -120 FULL_PAGE_CACHE_STORE and
 *   STATIC_PAGE_GENERATOR_STORE, -128 [WebDebugToolbarListener], -1000 [SessionListener]
 *
 * @internal
 */
final class ListenerPriority
{
    /**
     * Resolves the site and redirects to the main domain before anything else (site-specific cache keys, static
     * pages and routing depend on the site).
     */
    public const int ROUTING = 512;

    /**
     * After the SessionListener, so the session ID can be compared with the one that activated the maintenance mode,
     * and before the full page cache, so a cached page is not served during maintenance.
     */
    public const int MAINTENANCE_PAGE = 126;

    /**
     * After the session and maintenance listeners, before the RouterListener: a cache hit skips routing and the
     * controller.
     */
    public const int FULL_PAGE_CACHE = 120;

    /**
     * After the RouterListener, so the context can be matched by the `_route` attribute. Listeners with a higher
     * priority (routing, maintenance page, full page cache) get a context guessed from the path.
     */
    public const int PIMCORE_CONTEXT = 24;

    /**
     * After the Pimcore context, before Symfony's LocaleListener, so the locale of the fallback document is used.
     */
    public const int DOCUMENT_FALLBACK = 20;

    /**
     * After the firewall, so access_control applies to static pages, and after the maintenance page.
     */
    public const int STATIC_PAGE_GENERATOR = 7;

    /**
     * After Symfony's LocaleListener (16), which sets the request locale from the `_locale` attribute.
     */
    public const int LOCALE = 1;

    /**
     * Late, so the toolbar is disabled for the request that is actually handled (frontend requests of the admin UI,
     * excluded routes).
     */
    public const int WEB_DEBUG_TOOLBAR = -118;

    /**
     * Before ELEMENT, which uses the fallback document.
     */
    public const int DOCUMENT_FALLBACK_CONTROLLER = 50;

    /**
     * After DOCUMENT_FALLBACK_CONTROLLER: checks access to the resolved document and replaces it with its editmode,
     * preview or version state.
     */
    public const int ELEMENT = 30;

    /**
     * After ELEMENT, so the Twig globals get the final document.
     */
    public const int GLOBAL_TEMPLATE_VARIABLES = 15;

    public const int RESPONSE_HEADER_ATTRIBUTES = 10;

    /**
     * Before Symfony's TemplateAttributeListener (-128), so the document template wins over #[Template].
     */
    public const int CONTENT_TEMPLATE = 16;

    /**
     * Stops all other response listeners for a response served from the full page cache.
     */
    public const int FULL_PAGE_CACHE_STOP_PROPAGATION = 100;

    /**
     * Before RESPONSE_HEADER, so the #[ResponseHeader] headers are added to the response that is actually sent.
     */
    public const int RESPONSE_STACK = 40;

    public const int RESPONSE_HEADER = 32;

    /**
     * After the listeners that inject code into the response, so the stored page is the one that is sent.
     */
    public const int FULL_PAGE_CACHE_STORE = -120;

    /**
     * Same as FULL_PAGE_CACHE_STORE.
     */
    public const int STATIC_PAGE_GENERATOR_STORE = -120;
}
