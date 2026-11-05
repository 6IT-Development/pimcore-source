<?php

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

namespace Pimcore\Model\Property\Predefined;

use Exception;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Config;
use Pimcore\Model;
use Pimcore\Model\Property\Predefined;
use Symfony\Component\Uid\Uuid as Uid;

/**
 * @internal
 *
 * @property Predefined $model
 */
class Dao extends Model\Dao\PimcoreLocationAwareConfigDao
{
    private const string CONFIG_KEY = 'predefined_properties';

    private const string KEY_INDEX_CACHE_KEY = 'property_predefined_key_index';

    public function configure(): void
    {
        $config = Config::getSystemConfiguration();

        $storageConfig = $config['config_location'][self::CONFIG_KEY];

        parent::configure([
            'containerConfig' => $config['properties']['predefined']['definitions'],
            'settingsStoreScope' => 'pimcore_predefined_properties',
            'storageConfig' => $storageConfig,
        ]);
    }

    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(?string $id = null): void
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->getDataByName($this->model->getId());

        if ($data && $id != null) {
            $data['id'] = $id;
        }

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException(sprintf(
                'Predefined property with ID "%s" does not exist.',
                $this->model->getId()
            ));
        }
    }

    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByKey(?string $key = null): void
    {
        if ($key !== null) {
            $this->model->setKey($key);
        }
        $key = $this->model->getKey();

        $property = $key !== null ? (self::getKeyIndex()[$key] ?? null) : null;

        if ($property && $property->getId()) {
            $this->assignVariablesToModel($property->getObjectVars());
        } else {
            throw new Model\Exception\NotFoundException(sprintf(
                'Predefined property with key "%s" does not exist.',
                $this->model->getKey()
            ));
        }
    }

    /**
     * The definitions by key, built once per runtime cache and reset on save / delete, so that lookups (also those
     * without a definition) do not load the whole listing
     *
     * @return array<string, Predefined>
     */
    private static function getKeyIndex(): array
    {
        $index = RuntimeCache::isRegistered(self::KEY_INDEX_CACHE_KEY) ? RuntimeCache::get(self::KEY_INDEX_CACHE_KEY) : null;
        if (!is_array($index)) {
            $index = [];
            foreach ((new Listing())->getProperties() as $property) {
                $key = $property->getKey();
                // keys saved before duplicates were rejected: the first in listing order wins
                if ($key !== null && !isset($index[$key])) {
                    $index[$key] = $property;
                }
            }
            RuntimeCache::set(self::KEY_INDEX_CACHE_KEY, $index);
        }

        return $index;
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        $key = $this->model->getKey();
        $existing = $key !== null ? (self::getKeyIndex()[$key] ?? null) : null;
        if ($existing && $existing->getId() !== $this->model->getId()) {
            throw new \InvalidArgumentException(sprintf('There is already a predefined property with key "%s".', $key));
        }

        if (!$this->model->getId()) {
            $this->model->setId((string)Uid::v4());
        }
        $ts = time();
        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate($ts);
        }
        $this->model->setModificationDate($ts);

        $dataRaw = $this->model->getObjectVars();
        $data = [];
        $allowedProperties = ['name', 'description', 'key', 'type', 'data',
            'config', 'ctype', 'inheritable', 'creationDate', 'modificationDate',];

        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $allowedProperties)) {
                $data[$key] = $value;
            }
        }
        $this->saveData($this->model->getId(), $data);
        RuntimeCache::set(self::KEY_INDEX_CACHE_KEY, null);
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->deleteData($this->model->getId());
        RuntimeCache::set(self::KEY_INDEX_CACHE_KEY, null);
    }

    protected function prepareDataStructureForYaml(string $id, mixed $data): array
    {
        return [
            'pimcore' => [
                'properties' => [
                    'predefined' => [
                        'definitions' => [
                            $id => $data,
                        ],
                    ],
                ],
            ],
        ];
    }
}
