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

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore\Event\DataObjectClassificationStoreEvents;
use Pimcore\Event\Model\DataObject\ClassificationStore\StoreConfigEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\StoreConfig\Dao getDao()
 */
final class StoreConfig extends Model\AbstractModel
{
    use RecursionBlockingEventDispatchHelperTrait;

    protected ?int $id = null;

    /**
     * The store name.
     *
     */
    protected ?string $name = null;

    /**
     * The store description.
     *
     */
    protected ?string $description = null;

    public static function getById(int $id): ?StoreConfig
    {
        try {
            $config = new self();
            $config->getDao()->getById($id);

            return $config;
        } catch (Model\Exception\NotFoundException) {
            return null;
        }
    }

    public static function getByName(string $name): ?StoreConfig
    {
        try {
            $config = new self();
            $config->getDao()->getByName($name);

            return $config;
        } catch (Model\Exception\NotFoundException) {
            return null;
        }
    }

    public static function create(): StoreConfig
    {
        $config = new self();
        $config->save();

        return $config;
    }

    public function setName(string $name): StoreConfig
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Returns the description.
     *
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Sets the description.
     *
     *
     * @return Model\DataObject\Classificationstore\StoreConfig
     */
    public function setDescription(string $description): StoreConfig
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Deletes the store with its collections, groups and keys
     *
     * The configs are deleted through their models, so their caches are cleared and their delete events are
     * dispatched. ON DELETE CASCADE foreign keys remove the group/key and collection/group relations and the object
     * data of the deleted groups (`object_classificationstore_groups_*` / `object_classificationstore_data_*`).
     * The store row is deleted last, so a run that fails halfway can be repeated.
     */
    public function delete(): void
    {
        $this->dispatchEvent(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_PRE_DELETE);

        $dao = $this->getDao();
        foreach ($dao->getConfigIds(CollectionConfig\Dao::TABLE_NAME_COLLECTIONS) as $collectionId) {
            CollectionConfig::getById($collectionId, true)?->delete();
        }
        foreach ($dao->getConfigIds(GroupConfig\Dao::TABLE_NAME_GROUPS) as $groupId) {
            GroupConfig::getById($groupId, true)?->delete();
        }
        foreach ($dao->getConfigIds(KeyConfig\Dao::TABLE_NAME_KEYS) as $keyId) {
            KeyConfig::getById($keyId, true)?->delete();
        }

        $dao->delete();
        $this->dispatchEvent(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_POST_DELETE);
    }

    /**
     * Saves the store config
     */
    public function save(): void
    {
        $isUpdate = false;

        if ($this->getId()) {
            $isUpdate = true;
            $this->dispatchEvent(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_PRE_UPDATE);
        } else {
            $this->dispatchEvent(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_PRE_ADD);
        }

        $this->getDao()->save();

        if ($isUpdate) {
            $this->dispatchEvent(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_POST_UPDATE);
        } else {
            $this->dispatchEvent(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_POST_ADD);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
