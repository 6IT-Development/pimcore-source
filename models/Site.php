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

namespace Pimcore\Model;

use Exception;
use InvalidArgumentException;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Event\Model\SiteEvent;
use Pimcore\Event\SiteEvents;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Logger;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @method Site\Dao getDao()
 */
final class Site extends AbstractModel
{
    use RecursionBlockingEventDispatchHelperTrait;

    protected static ?Site $currentSite = null;

    protected ?int $id = null;

    protected array $domains = [];

    /**
     * Contains the ID to the Root-Document
     */
    protected ?int $rootId = null;

    protected ?Document\Page $rootDocument = null;

    protected ?string $rootPath = null;

    protected string $mainDomain = '';

    protected string $errorDocument = '';

    protected array $localizedErrorDocuments = [];

    protected bool $redirectToMainDomain = false;

    protected ?int $creationDate = null;

    protected ?int $modificationDate = null;

    /**
     * @throws Exception
     */
    public static function getById(int $id): ?Site
    {
        $cacheKey = 'site_id_' . $id;

        if (RuntimeCache::isRegistered($cacheKey)) {
            $site = RuntimeCache::get($cacheKey);
        } elseif (!$site = Cache::load($cacheKey)) {
            try {
                $site = new self();
                $site->getDao()->getById($id);
            } catch (NotFoundException) {
                $site = 'failed';
            }

            Cache::save($site, $cacheKey, ['system', 'site'], null, 999);
        }

        if ($site === 'failed' || !$site) {
            $site = null;
        }

        RuntimeCache::set($cacheKey, $site);

        return $site;
    }

    public static function getByRootId(int $id): ?Site
    {
        try {
            $site = new self();
            $site->getDao()->getByRootId($id);

            return $site;
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * Called for the host of every frontend request: the host is resolved against one cached map of all site domains,
     * so an unknown host (any client can send one) costs neither a query nor a cache entry of its own
     *
     * @throws Exception
     */
    public static function getByDomain(string $domain): ?Site
    {
        $domain = self::normalizeDomain($domain);
        $cacheKey = 'site_domain_' . md5($domain);

        if (RuntimeCache::isRegistered($cacheKey)) {
            return RuntimeCache::get($cacheKey);
        }

        $siteId = Site\Dao::matchDomain(self::getDomainMap(), $domain);
        $site = $siteId !== null ? self::getById($siteId) : null;

        RuntimeCache::set($cacheKey, $site);

        return $site;
    }

    /**
     * @return array{exact: array<string, int>, wildcards: array<string, int>}
     */
    private static function getDomainMap(): array
    {
        $cacheKey = 'site_domain_map';

        if (RuntimeCache::isRegistered($cacheKey)) {
            return RuntimeCache::get($cacheKey);
        }

        $map = Cache::load($cacheKey);
        if (!is_array($map)) {
            $map = (new self())->getDao()->getDomainMap();
            Cache::save($map, $cacheKey, ['system', 'site'], null, 999);
        }

        RuntimeCache::set($cacheKey, $map);

        return $map;
    }

    /**
     * Brings a domain into the form of Request::getHost(): trimmed, lower case and with the labels of an IDN in
     * punycode. A label containing a wildcard is only lower-cased, a part of a label has no punycode of its own.
     *
     * @internal
     */
    public static function normalizeDomain(string $domain): string
    {
        $domain = mb_strtolower(trim($domain));
        if (!preg_match('/[^\x00-\x7F]/', $domain)) {
            return $domain;
        }

        $labels = explode('.', $domain);
        foreach ($labels as &$label) {
            if (!str_contains($label, '*') && preg_match('/[^\x00-\x7F]/', $label)) {
                $label = idn_to_ascii($label, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $label;
            }
        }
        unset($label);

        return implode('.', $labels);
    }

    /**
     * Decodes a list stored as serialized array, without objects; null if the value is no such array
     *
     * @internal
     */
    public static function unserializeList(string $value): ?array
    {
        if ($value === '') {
            return [];
        }

        $list = @unserialize($value, ['allowed_classes' => false]);

        return is_array($list) ? $list : null;
    }

    /**
     * Resolves a site by its ID (also as a string of digits, e.g. a route parameter), its domain or the site itself
     *
     * @throws Exception
     */
    public static function getBy(int|string|Site $mixed): ?Site
    {
        if ($mixed instanceof Site) {
            return $mixed;
        }

        if (is_int($mixed)) {
            return self::getById($mixed);
        }

        if (ctype_digit($mixed)) {
            return self::getById((int) $mixed);
        }

        return self::getByDomain($mixed);
    }

    public static function create(array $data): Site
    {
        $site = new self();
        self::checkCreateData($data);
        $site->setValues($data);

        return $site;
    }

    /**
     * returns true if the current process/request is inside a site
     */
    public static function isSiteRequest(): bool
    {
        if (null !== self::$currentSite) {
            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public static function getCurrentSite(): Site
    {
        if (null !== self::$currentSite) {
            return self::$currentSite;
        }

        throw new Exception('This request/process is not inside a subsite');
    }

    /**
     * Register the current site, null unregisters it (e.g. to restore the state before a site was set)
     */
    public static function setCurrentSite(?Site $site): void
    {
        self::$currentSite = $site;
    }

    /**
     * Unregister the current site, e.g. at the start of a new request in a long-running process
     *
     * @internal
     */
    public static function resetCurrentSite(): void
    {
        self::$currentSite = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomains(): array
    {
        return $this->domains;
    }

    public function getRootId(): ?int
    {
        return $this->rootId;
    }

    public function getRootDocument(): ?Document\Page
    {
        // loaded lazily: the site is cached without it, the page has its own (correctly tagged) cache
        if (!$this->rootDocument && $this->rootId) {
            $this->rootDocument = Document\Page::getById($this->rootId);
        }

        return $this->rootDocument;
    }

    /**
     * @return $this
     */
    public function setId(int $id): Site
    {
        $this->id = $id;

        return $this;
    }

    /**
     * The domains are normalised (normalizeDomain()) and validated by save(), so loading a site never fails on a
     * stored value. A string is decoded as serialized array, any other string is taken as a single domain.
     *
     * @return $this
     */
    public function setDomains(array|string $domains): Site
    {
        if (is_string($domains)) {
            $domains = self::unserializeList($domains) ?? [$domains];
        }

        $this->domains = array_values(array_unique(array_filter(array_map(
            static fn (mixed $domain): string => is_scalar($domain) ? self::normalizeDomain((string) $domain) : '',
            $domains
        ))));

        return $this;
    }

    /**
     * @return $this
     */
    public function setRootId(int $rootId): Site
    {
        if ($this->rootDocument?->getId() !== $rootId) {
            $this->rootDocument = null;
        }
        $this->rootId = $rootId;

        return $this;
    }

    /**
     * @return $this
     */
    public function setRootDocument(?Document\Page $rootDocument): Site
    {
        $this->rootDocument = $rootDocument;
        $this->rootId = $rootDocument?->getId();

        return $this;
    }

    /**
     * @return $this
     */
    public function setRootPath(?string $path): Site
    {
        $this->rootPath = $path;

        return $this;
    }

    public function getRootPath(): ?string
    {
        if (!$this->rootPath && $this->getRootDocument()) {
            return $this->getRootDocument()->getRealFullPath();
        }

        return $this->rootPath;
    }

    public function setErrorDocument(string $errorDocument): void
    {
        $this->errorDocument = $errorDocument;
    }

    public function getErrorDocument(): string
    {
        return $this->errorDocument;
    }

    /**
     * @return $this
     */
    public function setLocalizedErrorDocuments(array|string $localizedErrorDocuments): Site
    {
        if (is_string($localizedErrorDocuments)) {
            $localizedErrorDocuments = self::unserializeList($localizedErrorDocuments)
                ?? throw new InvalidArgumentException('The localized error documents are not a serialized array');
        }
        $this->localizedErrorDocuments = $localizedErrorDocuments;

        return $this;
    }

    public function getLocalizedErrorDocuments(): array
    {
        return $this->localizedErrorDocuments;
    }

    /**
     * The domain is normalised (normalizeDomain()) and validated by save()
     */
    public function setMainDomain(string $mainDomain): void
    {
        $this->mainDomain = self::normalizeDomain($mainDomain);
    }

    public function getMainDomain(): string
    {
        return $this->mainDomain;
    }

    public function setRedirectToMainDomain(bool $redirectToMainDomain): void
    {
        $this->redirectToMainDomain = $redirectToMainDomain;
    }

    public function getRedirectToMainDomain(): bool
    {
        return $this->redirectToMainDomain;
    }

    public function __sleep(): array
    {
        // never cache a copy of the root page (path, key, properties, ...), it would outlive renames and moves
        return array_diff(parent::__sleep(), ['rootDocument']);
    }

    /**
     * @internal
     */
    public function clearDependentCache(): void
    {
        // this is mostly called in Site\Dao not here
        try {
            Cache::clearTag('site');
        } catch (Exception $e) {
            Logger::crit((string)$e);
        }

        self::clearRuntimeCache();
    }

    /**
     * Removes all site lookups of this process: a saved or deleted site can change the result of any ID, any domain
     * (also cached misses) and the path mapping of Tool\Frontend
     */
    private static function clearRuntimeCache(): void
    {
        $runtimeCache = RuntimeCache::getInstance();
        foreach (array_keys($runtimeCache->getArrayCopy()) as $key) {
            $key = (string) $key;
            if (str_starts_with($key, 'site_id_') || str_starts_with($key, 'site_domain_') || $key === 'sites_path_mapping') {
                $runtimeCache->offsetUnset($key);
            }
        }
    }

    /**
     * @return $this
     */
    public function setModificationDate(int $modificationDate): Site
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    /**
     * @return $this
     */
    public function setCreationDate(int $creationDate): Site
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    /**
     * New sites fire PRE_ADD / POST_ADD around PRE_SAVE / POST_SAVE
     *
     * @throws InvalidArgumentException if the root is no page or the root of another site, or a domain or the main
     *                                  domain is not a valid host name
     * @throws NotFoundException when the id is set but there is no site with it
     */
    public function save(): void
    {
        // the same check as in Dao::save(), id 0 is never stored
        $isAdd = !$this->id;

        $this->validate();

        if ($isAdd) {
            $this->dispatchEvent(new SiteEvent($this), SiteEvents::PRE_ADD);
        }
        $preSaveEvent = new SiteEvent($this);
        $this->dispatchEvent($preSaveEvent, SiteEvents::PRE_SAVE);

        // the listeners may have changed the site
        $this->validate();

        $this->getDao()->save();

        $postSaveEvent = new SiteEvent($this);
        $this->dispatchEvent($postSaveEvent, SiteEvents::POST_SAVE);
        if ($isAdd) {
            $this->dispatchEvent(new SiteEvent($this), SiteEvents::POST_ADD);
        }
    }

    /**
     * Runs on save() and not in the setters, which also load the stored values: a domain saved before the validation
     * existed must not make the site unloadable (and its host answer 500).
     *
     * @throws InvalidArgumentException
     */
    private function validate(): void
    {
        if (!$this->rootId || !Document\Page::getById($this->rootId)) {
            throw new InvalidArgumentException(sprintf('The root document of a site must be a page, got ID "%s"', $this->rootId));
        }

        $siteOfRoot = self::getByRootId($this->rootId);
        if ($siteOfRoot && $siteOfRoot->getId() !== $this->id) {
            throw new InvalidArgumentException(sprintf('Document %s is already the root of site %s', $this->rootId, $siteOfRoot->getId()));
        }

        foreach ($this->domains as $domain) {
            // a wildcard stands for any label characters, also inside a label that normalizeDomain() kept in Unicode
            $wildcardLessDomain = idn_to_ascii(str_replace('*', 'anystring', $domain), IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if (!$wildcardLessDomain || !filter_var($wildcardLessDomain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                throw new InvalidArgumentException(sprintf('Invalid domain name "%s"', $domain));
            }
        }

        if ($this->mainDomain !== '' && !filter_var($this->mainDomain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new InvalidArgumentException(sprintf('Invalid main domain name "%s"', $this->mainDomain));
        }
    }

    public function delete(): void
    {
        $preDeleteEvent = new SiteEvent($this);
        $this->dispatchEvent($preDeleteEvent, SiteEvents::PRE_DELETE);

        $this->getDao()->delete();

        $postDeleteEvent = new SiteEvent($this);
        $this->dispatchEvent($postDeleteEvent, SiteEvents::POST_DELETE);
    }
}
