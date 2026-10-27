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

use DateInterval;
use DateTime;
use DateTimeInterface;
use Exception;
use Pimcore;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\StaticPageContextAwareTrait;
use Pimcore\Cache;
use Pimcore\Cache\FullPage\SessionStatus;
use Pimcore\Config;
use Pimcore\Event\Cache\FullPage\CacheResponseEvent;
use Pimcore\Event\Cache\FullPage\PrepareResponseEvent;
use Pimcore\Event\Cache\FullPage\PrepareTagsEvent;
use Pimcore\Event\FullPageCacheEvents;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Logger;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;

class FullPageCacheListener implements ResetInterface
{
    use PimcoreContextAwareTrait;
    use StaticPageContextAwareTrait;

    /**
     * Vary headers that don't change the cached content: the body is stored uncompressed and XHR requests have their
     * own cache key.
     */
    private const array IGNORED_VARY_HEADERS = ['accept-encoding', 'x-requested-with'];

    protected bool $enabled = true;

    protected bool $stopResponsePropagation = false;

    protected ?int $lifetime = null;

    protected bool $addExpireHeader = true;

    protected ?string $disableReason = null;

    protected ?string $defaultCacheKey = null;

    public function __construct(
        protected SessionStatus            $sessionStatus,
        protected EventDispatcherInterface $eventDispatcher,
        protected Config                   $config
    )
    {
    }

    /**
     * All state is per request, reset it before the next request (worker runtimes): otherwise one cache hit stops the
     * response propagation of every following request, one POST disables the cache for good and the cache key of the
     * previous request can be reused.
     */
    public function reset(): void
    {
        $this->enabled = true;
        $this->stopResponsePropagation = false;
        $this->lifetime = null;
        $this->addExpireHeader = true;
        $this->disableReason = null;
        $this->defaultCacheKey = null;
    }

    public function disable(?string $reason = null): bool
    {
        if ($reason) {
            $this->disableReason = $reason;
        }

        $this->enabled = false;

        return true;
    }

    public function enable(): bool
    {
        $this->enabled = true;

        return true;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return $this
     */
    public function setLifetime(?int $lifetime): static
    {
        $this->lifetime = $lifetime;

        return $this;
    }

    public function getLifetime(): ?int
    {
        return $this->lifetime;
    }

    public function disableExpireHeader(): void
    {
        $this->addExpireHeader = false;
    }

    public function enableExpireHeader(): void
    {
        $this->addExpireHeader = true;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // in case the kernel.reset was not triggered since the last main request
        $this->stopResponsePropagation = false;
        $this->defaultCacheKey = null;

        if (!$this->isEnabled()) {
            return;
        }

        $request = $event->getRequest();

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if (!Tool::useFrontendOutputFilters()) {
            return;
        }

        $requestUri = $request->getRequestUri();
        $excludePatterns = [];
        $ignoredQueryParameters = [];

        // disable the output-cache if the client sends an authorization header
        if ($request->headers->has('authorization')) {
            $this->disable('authorization header in use');

            return;
        }

        // only enable GET method
        if (!$request->isMethodCacheable()) {
            $this->disable();

            return;
        }

        // disable the output-cache if browser wants the most recent version (e.g. a hard reload)
        if ($request->headers->hasCacheControlDirective('no-cache')) {
            $this->disable('HTTP Header Cache-Control: no-cache was sent');

            return;
        }

        if (str_contains(strtolower((string) $request->headers->get('Pragma')), 'no-cache')) {
            $this->disable('HTTP Header Pragma: no-cache was sent');

            return;
        }

        try {
            if ($conf = $this->config['full_page_cache']) {
                if (empty($conf['enabled'])) {
                    $this->disable();

                    return;
                }

                if (Pimcore::inDebugMode()) {
                    $this->disable('Debug flag DISABLE_FULL_PAGE_CACHE is enabled');

                    return;
                }

                if (!empty($conf['lifetime'])) {
                    $this->setLifetime((int)$conf['lifetime']);
                }

                if (!empty($conf['exclude_patterns'])) {
                    $excludePatterns = $this->parseExcludePatterns((string) $conf['exclude_patterns']);
                }

                if (!empty($conf['ignore_query_parameters'])) {
                    $ignoredQueryParameters = (array) $conf['ignore_query_parameters'];
                }

                if (!empty($conf['exclude_cookie'])) {
                    $cookies = explode(',', (string)$conf['exclude_cookie']);

                    foreach ($cookies as $cookie) {
                        $cookie = trim($cookie);
                        if ($cookie !== '' && $request->cookies->has($cookie)) {
                            $this->disable('exclude cookie in system-settings matches');

                            return;
                        }
                    }
                }

                if ($this->sessionStatus->isDisabledBySession($request)) {
                    $this->disable('Session in use');

                    return;
                }

                // output-cache is always disabled when logged in at the admin ui
                if (null !== Tool\Authentication::authenticateSession($request)) {
                    $this->disable('backend user is logged in');

                    return;
                }
            } else {
                $this->disable();

                return;
            }
        } catch (Exception $e) {
            Logger::error((string)$e);

            $this->disable('ERROR: Exception (see log files in /var/log)');

            return;
        }

        foreach ($excludePatterns as $pattern) {
            if (preg_match($pattern, $requestUri)) {
                $this->disable('exclude path pattern in system-settings matches');

                return;
            }
        }

        $deviceDetector = Tool\DeviceDetector::getInstance();
        $device = $deviceDetector->getDevice();
        $deviceDetector->setWasUsed(false);

        $appendKey = '';
        // this is for example for the image-data-uri plugin
        $tags = $request->query->all()['pimcore_cache_tag_suffix'] ?? null;
        if (is_array($tags)) {
            $appendKey = '_' . implode('_', $tags);
        }

        if ($request->isXmlHttpRequest()) {
            $appendKey .= 'xhr';
        }

        $appendKey .= $request->getMethod();

        $this->defaultCacheKey = 'output_' . md5(
            $request->getScheme() . '://' . Tool::getHostname() . $this->getNormalizedUri($request, $ignoredQueryParameters) . $appendKey
        );
        $cacheKeys = [
            $this->defaultCacheKey . '_' . $device,
            $this->defaultCacheKey,
        ];

        foreach ($cacheKeys as $cacheKey) {
            $cacheItem = Cache::load($cacheKey);
            if ($cacheItem) {
                break;
            }
        }

        if ($cacheItem instanceof Response) {
            $response = $cacheItem;
            $response->headers->set('X-Pimcore-Output-Cache-Tag', $cacheKey);

            // a PREPARE_RESPONSE listener may have removed the date header
            $cacheItemDate = strtotime((string) $response->headers->get('X-Pimcore-Cache-Date'));
            if ($cacheItemDate !== false) {
                $response->headers->set('Age', (string) max(0, time() - $cacheItemDate));
            }

            $event->setResponse($response);
            $this->stopResponsePropagation = true;
        }
    }

    /**
     * The patterns are a comma separated list, but a regex can contain commas itself (e.g. a quantifier like {1,3}),
     * so parts are joined again until they form a valid regex.
     *
     * @return string[]
     */
    private function parseExcludePatterns(string $patterns): array
    {
        $result = [];
        $buffer = null;

        foreach (explode(',', $patterns) as $part) {
            $buffer = $buffer === null ? $part : $buffer . ',' . $part;
            $pattern = trim($buffer);

            if ($pattern === '') {
                $buffer = null;

                continue;
            }

            if (@preg_match($pattern, '') !== false) {
                $result[] = $pattern;
                $buffer = null;
            }
        }

        if ($buffer !== null && trim($buffer) !== '') {
            Logger::warning(sprintf('Full page cache: ignoring invalid exclude pattern "%s"', trim($buffer)));
        }

        return $result;
    }

    /**
     * Path plus the query string with sorted parameters, so the order of the parameters doesn't create separate cache
     * entries. Parameters in $ignoredParameters (a trailing * matches a prefix, e.g. utm_*) are not part of the key.
     */
    private function getNormalizedUri(Request $request, array $ignoredParameters): string
    {
        $uri = $request->getBaseUrl() . $request->getPathInfo();

        if ($ignoredParameters) {
            $query = $request->query->all();
            foreach (array_keys($query) as $name) {
                foreach ($ignoredParameters as $ignored) {
                    $ignored = (string) $ignored;
                    if ($name === $ignored || (str_ends_with($ignored, '*') && str_starts_with((string) $name, substr($ignored, 0, -1)))) {
                        unset($query[$name]);

                        break;
                    }
                }
            }

            $queryString = Request::normalizeQueryString(http_build_query($query, '', '&', PHP_QUERY_RFC3986));
        } else {
            $queryString = $request->getQueryString() ?? '';
        }

        return $queryString !== '' ? $uri . '?' . $queryString : $uri;
    }

    public function stopPropagationCheck(KernelEvent $event): void
    {
        if ($this->stopResponsePropagation) {
            $event->stopPropagation();
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!Tool::isFrontend() || Tool::isFrontendRequestByAdmin($request)) {
            return;
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if ($this->matchesStaticPageContext($request)) {
            $this->disable('Response can\'t be cached for static pages');
        }

        $response = $event->getResponse();

        if (!$this->responseCanBeCached($response)) {
            $this->disable('Response can\'t be cached');
        }

        if ($this->enabled && $this->sessionStatus->isDisabledBySession($request)) {
            $this->disable('Session in use');
        }

        if ($this->disableReason) {
            $response->headers->set('X-Pimcore-Output-Cache-Disable-Reason', $this->disableReason);
        }

        if ($this->enabled && $response->getStatusCode() == 200 && $this->defaultCacheKey) {
            try {
                if ($this->lifetime && $this->addExpireHeader) {
                    // add cache control for proxies and http-caches like varnish, ...
                    $response->headers->set('Cache-Control', 'public, max-age=' . $this->lifetime);

                    // add expire header
                    $date = new DateTime('now');
                    $date->add(new DateInterval('PT' . $this->lifetime . 'S'));
                    $response->headers->set('Expires', $date->format(DateTimeInterface::RFC1123));
                }

                $now = new DateTime('now');
                $response->headers->set('X-Pimcore-Cache-Date', $now->format(DateTimeInterface::ATOM));

                $cacheKey = $this->defaultCacheKey;
                $deviceDetector = Tool\DeviceDetector::getInstance();
                if ($deviceDetector->wasUsed()) {
                    $cacheKey .= '_' . $deviceDetector->getDevice();
                }

                $event = new PrepareResponseEvent($request, $response);
                $this->eventDispatcher->dispatch($event, FullPageCacheEvents::PREPARE_RESPONSE);
                $cacheItem = $event->getResponse();

                $event = new PrepareTagsEvent($request, $response);
                $this->eventDispatcher->dispatch($event, FullPageCacheEvents::PREPARE_TAGS);
                $tags = $event->getTags();

                $tags[] = $this->lifetime ? 'output_lifetime' : 'output';

                Cache::save($cacheItem, $cacheKey, $tags, $this->lifetime, 1000, true);
            } catch (Exception $e) {
                Logger::error((string)$e);

                return;
            }
        } else {
            // output-cache was disabled, add "output" as cleared tag to ensure that no other "output" tagged elements
            // like the inc and snippet cache get into the cache
            Cache::addIgnoredTagOnSave('output_inline');
        }
    }

    private function responseCanBeCached(Response $response): bool
    {
        $cache = true;

        // do not cache when the application indicated the 'no-store' directives in the response Cache-Control header
        if ($response->headers->hasCacheControlDirective('no-store')) {
            $cache = false;
        }

        // the cached response is replayed to every visitor, so it must not set cookies (session, CSRF, tracking, ...)
        if ($response->headers->getCookies()) {
            $cache = false;
        }

        // the cache key doesn't contain the request headers the content depends on (Accept-Language, Cookie, ...)
        foreach ($response->getVary() as $header) {
            if (!in_array(strtolower(trim($header)), self::IGNORED_VARY_HEADERS, true)) {
                $cache = false;
            }
        }

        // do not cache common responses
        if ($response instanceof BinaryFileResponse) {
            $cache = false;
        }

        if ($response instanceof StreamedResponse) {
            $cache = false;
        }

        // fire an event to allow full customozations
        $event = new CacheResponseEvent($response, $cache);
        $this->eventDispatcher->dispatch($event, FullPageCacheEvents::CACHE_RESPONSE);

        return $event->getCache();
    }
}
