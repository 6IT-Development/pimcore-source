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

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Pimcore;
use Pimcore\Bundle\CoreBundle\EventListener\ListenerPriority;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\StaticPageContextAwareTrait;
use Pimcore\Cache;
use Pimcore\Cache\FullPage\FullPageCacheState;
use Pimcore\Cache\FullPage\SessionStatus;
use Pimcore\Config;
use Pimcore\Event\Cache\FullPage\CacheResponseEvent;
use Pimcore\Event\Cache\FullPage\PrepareResponseEvent;
use Pimcore\Event\Cache\FullPage\PrepareTagsEvent;
use Pimcore\Event\FullPageCacheEvents;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Tool;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Serves pages from and stores them in the full page cache. Whether the current page may be cached, and for how long,
 * is kept in FullPageCacheState; enable(), disable() and the lifetime methods here only forward to it.
 */
class FullPageCacheListener implements ResetInterface
{
    use PimcoreContextAwareTrait;
    use StaticPageContextAwareTrait;

    /**
     * Vary headers that don't change the cached content: the body is stored uncompressed and XHR requests have their
     * own cache key.
     */
    private const array IGNORED_VARY_HEADERS = ['accept-encoding', 'x-requested-with'];

    protected bool $stopResponsePropagation = false;

    protected ?string $defaultCacheKey = null;

    /**
     * @var array{0: string, 1: string[]}|null configured exclude patterns and their parsed form
     */
    private ?array $parsedExcludePatterns = null;

    public function __construct(
        protected SessionStatus            $sessionStatus,
        protected EventDispatcherInterface $eventDispatcher,
        protected Config                   $config,
        private readonly FullPageCacheState $state,
        private readonly LoggerInterface $logger = new NullLogger()
    )
    {
    }

    /**
     * All state is per request, reset it before the next request (worker runtimes): otherwise one cache hit stops the
     * response propagation of every following request and the cache key of the previous request can be reused.
     * FullPageCacheState is reset on its own.
     */
    public function reset(): void
    {
        $this->stopResponsePropagation = false;
        $this->defaultCacheKey = null;
    }

    public function disable(?string $reason = null): bool
    {
        $this->state->disable($reason);

        return true;
    }

    public function enable(): bool
    {
        $this->state->enable();

        return true;
    }

    public function isEnabled(): bool
    {
        return $this->state->enabled;
    }

    /**
     * @return $this
     */
    public function setLifetime(?int $lifetime): static
    {
        $this->state->setLifetime($lifetime);

        return $this;
    }

    public function getLifetime(): ?int
    {
        return $this->state->lifetime;
    }

    public function disableExpireHeader(): void
    {
        $this->state->disableExpireHeader();
    }

    public function enableExpireHeader(): void
    {
        $this->state->enableExpireHeader();
    }

    #[AsEventListener(KernelEvents::REQUEST, priority: ListenerPriority::FULL_PAGE_CACHE)]
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

        $conf = [];

        try {
            $conf = $this->config['full_page_cache'] ?: [];
            $disableReason = $this->getDisableReason($request, $conf);
        } catch (Exception $e) {
            $this->logger->error('Full page cache: checking the request failed', ['exception' => $e]);
            $disableReason = 'ERROR: Exception (see log files in /var/log)';
        }

        if ($disableReason !== null) {
            $this->disable($disableReason !== '' ? $disableReason : null);

            return;
        }

        if (!empty($conf['lifetime'])) {
            $this->setLifetime((int) $conf['lifetime']);
        }

        $this->defaultCacheKey = $this->buildCacheKey($request, (array) ($conf['ignore_query_parameters'] ?? []));
        $this->serveCachedResponse($event, $this->defaultCacheKey);
    }

    /**
     * Why the request can't be served from (or stored in) the full page cache
     *
     * @return string|null null if it can be cached, an empty string to disable the cache without a reason header
     */
    private function getDisableReason(Request $request, array $conf): ?string
    {
        if ($request->headers->has('authorization')) {
            return 'authorization header in use';
        }

        // only GET and HEAD
        if (!$request->isMethodCacheable()) {
            return '';
        }

        // the browser wants the most recent version (e.g. a hard reload)
        if ($request->headers->hasCacheControlDirective('no-cache')) {
            return 'HTTP Header Cache-Control: no-cache was sent';
        }

        if (str_contains(strtolower((string) $request->headers->get('Pragma')), 'no-cache')) {
            return 'HTTP Header Pragma: no-cache was sent';
        }

        if (empty($conf['enabled'])) {
            return '';
        }

        if (Pimcore::inDebugMode()) {
            return 'Debug flag DISABLE_FULL_PAGE_CACHE is enabled';
        }

        foreach (explode(',', (string) ($conf['exclude_cookie'] ?? '')) as $cookie) {
            $cookie = trim($cookie);
            if ($cookie !== '' && $request->cookies->has($cookie)) {
                return 'exclude cookie in system-settings matches';
            }
        }

        if ($this->sessionStatus->isDisabledBySession($request)) {
            return 'Session in use';
        }

        // output-cache is always disabled when logged in at the admin ui
        if (null !== Tool\Authentication::authenticateSession($request)) {
            return 'backend user is logged in';
        }

        $requestUri = $request->getRequestUri();
        foreach ($this->getExcludePatterns((string) ($conf['exclude_patterns'] ?? '')) as $pattern) {
            if (preg_match($pattern, $requestUri)) {
                return 'exclude path pattern in system-settings matches';
            }
        }

        return null;
    }

    private function buildCacheKey(Request $request, array $ignoredQueryParameters): string
    {
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

        return 'output_' . md5(
            $request->getScheme() . '://' . Tool::getHostname() . $this->getNormalizedUri($request, $ignoredQueryParameters) . $appendKey
        );
    }

    /**
     * Pages that used the device detector are stored with the device in the key, the others without
     */
    private function serveCachedResponse(RequestEvent $event, string $defaultCacheKey): void
    {
        $deviceDetector = Tool\DeviceDetector::getInstance();
        $device = $deviceDetector->getDevice();
        $deviceDetector->setWasUsed(false);

        foreach ([$defaultCacheKey . '_' . $device, $defaultCacheKey] as $cacheKey) {
            $response = Cache::load($cacheKey);
            if (!$response instanceof Response) {
                continue;
            }

            $response->headers->set('X-Pimcore-Output-Cache-Tag', $cacheKey);

            // a PREPARE_RESPONSE listener may have removed the date header
            $cacheItemDate = strtotime((string) $response->headers->get('X-Pimcore-Cache-Date'));
            if ($cacheItemDate !== false) {
                $response->headers->set('Age', (string) max(0, time() - $cacheItemDate));
            }

            $event->setResponse($response);
            $this->stopResponsePropagation = true;

            return;
        }
    }

    /**
     * The configuration is the same for most requests, parse the patterns only when it changes
     *
     * @return string[]
     */
    private function getExcludePatterns(string $patterns): array
    {
        if (trim($patterns) === '') {
            return [];
        }

        if ($this->parsedExcludePatterns === null || $this->parsedExcludePatterns[0] !== $patterns) {
            $this->parsedExcludePatterns = [$patterns, $this->parseExcludePatterns($patterns)];
        }

        return $this->parsedExcludePatterns[1];
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
            $this->logger->warning('Full page cache: ignoring invalid exclude pattern "{pattern}"', ['pattern' => trim($buffer)]);
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

    #[AsEventListener(KernelEvents::RESPONSE, priority: ListenerPriority::FULL_PAGE_CACHE_STOP_PROPAGATION)]
    public function stopPropagationCheck(KernelEvent $event): void
    {
        if ($this->stopResponsePropagation) {
            $event->stopPropagation();
        }
    }

    #[AsEventListener(KernelEvents::RESPONSE, priority: ListenerPriority::FULL_PAGE_CACHE_STORE)]
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

        if ($this->state->enabled && $this->sessionStatus->isDisabledBySession($request)) {
            $this->disable('Session in use');
        }

        if ($this->state->disableReason) {
            $response->headers->set('X-Pimcore-Output-Cache-Disable-Reason', $this->state->disableReason);
        }

        if (!$this->state->enabled || $response->getStatusCode() !== Response::HTTP_OK || !$this->defaultCacheKey) {
            // output-cache was disabled: ignore the "output_inline" tag on save, so that no other "output_inline" tagged
            // elements like the inc and snippet cache get into the cache
            Cache::addIgnoredTagOnSave('output_inline');

            return;
        }

        try {
            $this->storeResponse($request, $response, $this->defaultCacheKey);
        } catch (Exception $e) {
            $this->logger->error('Full page cache: storing the response failed', ['exception' => $e]);
        }
    }

    private function storeResponse(Request $request, Response $response, string $cacheKey): void
    {
        $lifetime = $this->state->lifetime;

        if ($lifetime && $this->state->expireHeaderEnabled) {
            // cache control for proxies and http-caches like varnish, ...
            $response->setPublic();
            $response->setMaxAge($lifetime);
            $response->setExpires(new DateTimeImmutable('+' . $lifetime . ' seconds'));
        }

        $response->headers->set('X-Pimcore-Cache-Date', (new DateTimeImmutable())->format(DateTimeInterface::ATOM));

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

        $tags[] = $lifetime ? 'output_lifetime' : 'output';

        Cache::save($cacheItem, $cacheKey, $tags, $lifetime, 1000, true);
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
