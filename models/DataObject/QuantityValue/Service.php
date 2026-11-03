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

namespace Pimcore\Model\DataObject\QuantityValue;

use Exception;
use Pimcore\Cache;
use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model\Translation;
use Throwable;

class Service
{
    /**
     * Imports a unit export. With $override, units that already exist are updated in place (never deleted, so
     * units outside the file keep their base unit); without it they are skipped. The whole file is validated
     * first and written in one transaction, so a failed import leaves the units unchanged.
     */
    public function importDefinitionFromJson(string $json, bool $override = false): bool
    {
        $db = Db::get();

        try {
            $unitsArray = json_decode($json, true);
            if (!is_array($unitsArray) || !array_is_list($unitsArray)) {
                throw new Exception('Unit definition must be a list of units');
            }

            $existingUnits = self::getQuantityValueUnitsTable();
            if ($existingUnits === null) {
                throw new Exception('Existing units could not be loaded');
            }

            /** @var array<string, Unit> $units */
            $units = [];
            /** @var array<string, string|null> $baseUnitIds */
            $baseUnitIds = [];
            foreach ($unitsArray as $unitArray) {
                if (!is_array($unitArray) || !is_scalar($unitArray['id'] ?? null) || (string)$unitArray['id'] === '') {
                    throw new Exception('Every unit needs an id');
                }
                $id = (string)$unitArray['id'];
                if (isset($units[$id])) {
                    throw new Exception(sprintf('Unit %s is defined twice', $id));
                }
                $unitArray['id'] = $id;
                if (isset($unitArray['baseunit']) && $unitArray['baseunit'] !== '') {
                    $unitArray['baseunit'] = (string)$unitArray['baseunit'];
                } else {
                    $unitArray['baseunit'] = null;
                }

                $unit = new Unit();
                $unit->setValues($unitArray, true);
                $units[$id] = $unit;
                $baseUnitIds[$id] = $unitArray['baseunit'];
            }

            foreach ($baseUnitIds as $id => $baseUnitId) {
                if ($baseUnitId !== null && !isset($units[$baseUnitId]) && !isset($existingUnits[$baseUnitId])) {
                    throw new Exception(sprintf('Base unit %s of unit %s does not exist', $baseUnitId, $id));
                }
            }

            if (!$override) {
                $units = array_diff_key($units, $existingUnits);
            }

            // base units are written before the units that reference them (foreign key)
            $sortedUnits = [];
            $visiting = [];
            $visit = function (string $id) use (&$visit, &$sortedUnits, &$visiting, $units, $baseUnitIds): void {
                if (isset($sortedUnits[$id]) || !isset($units[$id])) {
                    return;
                }
                if (isset($visiting[$id])) {
                    throw new Exception(sprintf('Base unit cycle at unit %s', $id));
                }
                $visiting[$id] = true;
                if ($baseUnitIds[$id] !== null) {
                    $visit($baseUnitIds[$id]);
                }
                $sortedUnits[$id] = $units[$id];
            };
            foreach (array_keys($units) as $id) {
                $visit((string)$id);
            }

            $db->beginTransaction();

            try {
                foreach ($sortedUnits as $unit) {
                    $unit->save();
                }
                $db->commit();
            } catch (Throwable $e) {
                $db->rollBack();

                throw $e;
            } finally {
                Cache\RuntimeCache::set(Unit::CACHE_KEY, null);
                Cache::remove(Unit::CACHE_KEY);
            }
        } catch (Throwable $e) {
            Logger::error('Quantity value unit import failed: ' . $e->getMessage());

            return false;
        }

        return true;
    }

    public function generateDefinitionJson(): string|false
    {
        $list = new Unit\Listing();
        $list->setOrderKey(['baseunit', 'factor', 'abbreviation']);
        $list->setOrder(['ASC', 'ASC', 'ASC']);

        $result = [];
        $units = $list->getUnits();
        foreach ($units as &$unit) {
            try {
                if ($unit->getAbbreviation()) {
                    $unit->setAbbreviation(Translation::getByKeyLocalized($unit->getAbbreviation(), Translation::DOMAIN_ADMIN,
                        true, true));
                }
                if ($unit->getLongname()) {
                    $unit->setLongname(Translation::getByKeyLocalized($unit->getLongname(), Translation::DOMAIN_ADMIN, true,
                        true));
                }
                $result[] = $unit->getObjectVars();
            } catch (Exception) {
                return false;
            }
        }

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * @return array<string, Unit>|null
     * @internal
     *
     */
    public static function getQuantityValueUnitsTable(): ?array
    {
        try {
            $table = null;
            if (Cache\RuntimeCache::isRegistered(Unit::CACHE_KEY)) {
                $table = Cache\RuntimeCache::get(Unit::CACHE_KEY);
            }

            if (!is_array($table)) {
                $table = Cache::load(Unit::CACHE_KEY);
                if (is_array($table)) {
                    Cache\RuntimeCache::set(Unit::CACHE_KEY, $table);
                }
            }

            if (!is_array($table)) {
                $table = [];
                $list = new Unit\Listing();
                $list->setOrderKey(['baseunit', 'factor', 'abbreviation']);
                $list->setOrder(['ASC', 'ASC', 'ASC']);

                foreach ($list->getUnits() as $item) {
                    $table[$item->getId()] = $item;
                }

                Cache::save($table, Unit::CACHE_KEY, [], null, 995, true);
                Cache\RuntimeCache::set(Unit::CACHE_KEY, $table);
            }

            return $table;
        } catch (Exception $e) {
            Logger::error((string)$e);

            return null;
        }
    }
}
