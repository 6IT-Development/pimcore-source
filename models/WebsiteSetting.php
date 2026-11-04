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
use Pimcore\Config;
use Pimcore\Event\Model\WebsiteSettingEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Event\WebsiteSettingEvents;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\WebsiteSetting\Dao;

/**
 * @method Dao getDao()
 */
final class WebsiteSetting extends AbstractModel
{
    use RecursionBlockingEventDispatchHelperTrait;

    protected ?int $id = null;

    protected string $name = '';

    protected string $language = '';

    protected ?string $type = null;

    protected mixed $data = null;

    protected ?int $siteId = null;

    protected ?int $creationDate = null;

    protected ?int $modificationDate = null;

    /**
     * RuntimeCache key of the small per request cache to know which website setting is which, used in self::getByName().
     * It lives in the RuntimeCache (not in a static property), so it is cleared together with it, e.g. in workers.
     */
    private const string NAME_ID_MAPPING_CACHE_KEY = 'website_setting_name_id_mapping';

    /**
     * The values of the website_settings.type enum column.
     */
    private const array TYPES = ['text', 'document', 'asset', 'object', 'bool'];

    private const array ELEMENT_TYPES = ['document', 'asset', 'object'];

    protected static function getCacheKey(string $name, ?int $siteId = null, ?string $language = null, ?string $fallbackLanguage = null): string
    {
        return $name . '~~~' . $siteId . '~~~' . $language . '~~~' . $fallbackLanguage;
    }

    /**
     * Ranks a setting for a lookup on a site and language; the highest rank wins, the lowest id breaks ties.
     * A site specific setting beats a language specific one: site + language > site + fallback language > site >
     * language > fallback language > neutral.
     * Settings without site (`null` or the legacy `0`) apply to all sites, settings without language to all languages.
     *
     * @return int|null null when the setting does not apply to the site or language
     *
     * @internal
     */
    public static function getOverrideRank(
        ?int $settingSiteId,
        string $settingLanguage,
        ?int $siteId,
        ?string $language,
        ?string $fallbackLanguage = null
    ): ?int {
        if ($settingSiteId) {
            if ($settingSiteId !== $siteId) {
                return null;
            }
            $rank = 3;
        } else {
            $rank = 0;
        }

        if ($settingLanguage !== '') {
            if ($settingLanguage === $language) {
                $rank += 2;
            } elseif ($settingLanguage === $fallbackLanguage) {
                $rank++;
            } else {
                return null;
            }
        }

        return $rank;
    }

    public static function getById(int $id): ?WebsiteSetting
    {
        $cacheKey = 'website_setting_' . $id;

        if (RuntimeCache::isRegistered($cacheKey)) {
            return RuntimeCache::get($cacheKey);
        }

        $setting = new self();

        try {
            $setting->getDao()->getById($id);
        } catch (NotFoundException) {
            return null;
        }

        RuntimeCache::set($cacheKey, $setting);

        return $setting;
    }

    /**
     * @param string $name name of the config
     * @param int|null $siteId site ID
     * @param string|null $language language, if there is no setting for it, the one for $fallbackLanguage and then the
     *                              one without language is returned (a site specific setting comes first, see
     *                              self::getOverrideRank())
     * @param string|null $fallbackLanguage fallback language
     *
     * @throws Exception
     */
    public static function getByName(string $name, ?int $siteId = null, ?string $language = null, ?string $fallbackLanguage = null): ?WebsiteSetting
    {
        $nameCacheKey = WebsiteSetting::getCacheKey($name, $siteId, $language, $fallbackLanguage);

        $nameIdMapping = RuntimeCache::isRegistered(self::NAME_ID_MAPPING_CACHE_KEY)
            ? RuntimeCache::get(self::NAME_ID_MAPPING_CACHE_KEY)
            : [];

        // check if pimcore already knows the id for this $name, if yes just return it
        if (array_key_exists($nameCacheKey, $nameIdMapping)) {
            if ($setting = self::getById($nameIdMapping[$nameCacheKey])) {
                return $setting;
            }

            // the setting was deleted in the meantime, resolve the name again
            unset($nameIdMapping[$nameCacheKey]);
            RuntimeCache::set(self::NAME_ID_MAPPING_CACHE_KEY, $nameIdMapping);
        }

        // create a tmp object to obtain the id
        $setting = new self();

        try {
            $setting->getDao()->getByName($name, $siteId, $language, $fallbackLanguage);
        } catch (NotFoundException) {
            return null;
        }

        // to have a singleton in a way. like all instances of Element\ElementInterface do also, like DataObject\AbstractObject
        if ($setting->getId() > 0) {
            // add it to the mini-per request cache
            $nameIdMapping[$nameCacheKey] = $setting->getId();
            RuntimeCache::set(self::NAME_ID_MAPPING_CACHE_KEY, $nameIdMapping);

            return self::getById($setting->getId());
        }

        return $setting;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setId(int $id): WebsiteSetting
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return $this
     */
    public function setName(string $name): WebsiteSetting
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return $this
     */
    public function setCreationDate(int $creationDate): WebsiteSetting
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    /**
     * @return $this
     */
    public function setData(mixed $data): WebsiteSetting
    {
        if ($data instanceof ElementInterface) {
            $this->setType(Service::getElementType($data));
            $data = $data->getId();
        }

        $this->data = $data;

        return $this;
    }

    public function getData(): mixed
    {
        // lazy-load data of type asset, document, object
        if (in_array($this->getType(), self::ELEMENT_TYPES, true) && !$this->data instanceof ElementInterface && is_numeric($this->data)) {
            return Element\Service::getElementById($this->getType(), (int)$this->data);
        }

        return $this->data;
    }

    /**
     * @return $this
     */
    public function setModificationDate(int $modificationDate): WebsiteSetting
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    /**
     * @param int|null $siteId null (or 0, stored as null) for all sites
     *
     * @return $this
     */
    public function setSiteId(?int $siteId): WebsiteSetting
    {
        $this->siteId = $siteId ?: null;

        return $this;
    }

    public function getSiteId(): ?int
    {
        return $this->siteId;
    }

    /**
     * enum('text','document','asset','object','bool')
     *
     * @return $this
     *
     * @throws InvalidArgumentException for any other type; '' (an invalid value stored before) is read as null
     */
    public function setType(?string $type): WebsiteSetting
    {
        if ($type === '') {
            $type = null;
        }

        if ($type !== null && !in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid website setting type "%s", allowed are: %s',
                $type,
                implode(', ', self::TYPES)
            ));
        }

        $this->type = $type;

        return $this;
    }

    /**
     * enum('text','document','asset','object','bool')
     *
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @return $this
     */
    public function setLanguage(string $language): WebsiteSetting
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @internal
     */
    public function clearDependentCache(): void
    {
        // 'output': full page cache entries rendered with the old value, as a document save does
        Cache::clearTags(['website_config', 'output']);

        $runtimeCache = RuntimeCache::getInstance();
        if ($this->getId() && $runtimeCache->offsetExists('website_setting_' . $this->getId())) {
            $runtimeCache->offsetUnset('website_setting_' . $this->getId());
        }

        // the mapping is keyed by the requested site/language, so a changed, added or deleted setting can affect
        // any of its entries: flush it as a whole
        if ($runtimeCache->offsetExists(self::NAME_ID_MAPPING_CACHE_KEY)) {
            $runtimeCache->offsetUnset(self::NAME_ID_MAPPING_CACHE_KEY);
        }

        Config::clearWebsiteConfigRuntimeCache();
    }

    public function delete(): void
    {
        $event = new WebsiteSettingEvent($this);

        $this->dispatchEvent($event, WebsiteSettingEvents::PRE_DELETE);

        $this->getDao()->delete();

        $this->dispatchEvent($event, WebsiteSettingEvents::POST_DELETE);
    }

    /**
     * @throws ValidationException when the name is empty, a setting with the same name, site and language exists or
     *                             the data of an element type is no element id
     * @throws NotFoundException when the id is set but there is no setting with it
     */
    public function save(): void
    {
        $event = new WebsiteSettingEvent($this);
        // the same check as in Dao::save(), id 0 is never stored
        $isAdd = !$this->id;

        $this->dispatchEvent($event, $isAdd ? WebsiteSettingEvents::PRE_ADD : WebsiteSettingEvents::PRE_UPDATE);

        $this->validate();

        $this->getDao()->save();

        $this->dispatchEvent($event, $isAdd ? WebsiteSettingEvents::POST_ADD : WebsiteSettingEvents::POST_UPDATE);
    }

    /**
     * @throws ValidationException
     */
    private function validate(): void
    {
        if (trim($this->name) === '') {
            throw new ValidationException('Website setting name must not be empty');
        }

        // e.g. setType('document')->setData('hello'): getData() would return the text, the website config an "element"
        if (in_array($this->type, self::ELEMENT_TYPES, true)
            && !in_array($this->data, [null, ''], true)
            && !(is_int($this->data) || (is_string($this->data) && ctype_digit($this->data)))
        ) {
            throw new ValidationException(sprintf(
                'Website setting "%s" of type %s needs an element id as data, got %s',
                $this->name,
                $this->type,
                get_debug_type($this->data)
            ));
        }

        if ($this->getDao()->hasDuplicate()) {
            throw new ValidationException(sprintf(
                'Website setting "%s" already exists for %s and %s',
                $this->name,
                $this->siteId ? 'site ' . $this->siteId : 'all sites',
                $this->language !== '' ? 'language "' . $this->language . '"' : 'all languages'
            ));
        }
    }
}
