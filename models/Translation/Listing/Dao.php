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

namespace Pimcore\Model\Translation\Listing;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Pimcore\Cache;
use Pimcore\Model;
use Pimcore\Model\Listing\Dao\QueryBuilderHelperTrait;
use Pimcore\Model\Translation\Listing;

/**
 * @internal
 *
 * @property Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    use QueryBuilderHelperTrait;

    public function getDatabaseTableName(): string
    {
        return Model\Translation\Dao::TABLE_PREFIX . $this->model->getDomain();
    }

    public function getTotalCount(): int
    {
        $queryBuilder = $this->getQueryBuilder($this->getDatabaseTableName() . '.key');
        $queryBuilder->resetOrderBy();
        $queryBuilder->setMaxResults(null);
        $queryBuilder->setFirstResult(0);

        $query = sprintf('SELECT COUNT(*) as amount FROM (%s) AS a', $queryBuilder);
        return (int)$this->db->fetchOne($query, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
    }

    public function getCount(): int
    {
        if (count($this->model->load()) > 0) {
            return count($this->model->load());
        }

        $queryBuilder = $this->getQueryBuilder($this->getDatabaseTableName() . '.key');

        $query = sprintf('SELECT COUNT(*) as amount FROM (%s) AS a', $queryBuilder);

        return (int)$this->db->fetchOne($query, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
    }

    public function getAllTranslations(): array
    {
        $queryBuilder = $this->getQueryBuilder('*');
        $queryBuilder->setMaxResults(null); //retrieve all results

        $cacheKey = $this->getCacheKey($queryBuilder);
        $rows = $this->loadRowsFromCache($cacheKey);
        if ($rows === null) {
            $rows = $this->db->fetchAllAssociative($queryBuilder->getSql(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());
            $this->saveRowsToCache($cacheKey, $rows);
        }

        $rowsByKey = [];
        foreach ($rows as $row) {
            $rowsByKey[$row['key']][] = $row;
        }

        $translations = [];
        foreach ($rowsByKey as $keyRows) {
            $translations[$keyRows[0]['key']] = $this->createTranslation($keyRows);
        }

        return $translations;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function loadRaw(): array
    {
        $queryBuilder = $this->getQueryBuilder('*');
        return $this->db->fetchAllAssociative($queryBuilder->getSql(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());
    }

    public function load(): array
    {
        $this->model->setGroupBy($this->getDatabaseTableName() . '.key', false);

        $queryBuilder = $this->getQueryBuilder($this->getDatabaseTableName() . '.key');

        // the same languages as Translation::getByKey(): the requested ones that are valid for the domain
        $validLanguages = Model\Translation::getValidLanguages($this->model->getDomain());
        $languages = $this->model->getLanguages() ? array_values(array_intersect($validLanguages, $this->model->getLanguages())) : $validLanguages;

        $cacheKey = $this->getCacheKey($queryBuilder, $languages);
        $rows = $this->loadRowsFromCache($cacheKey);
        if ($rows === null) {
            $keys = $this->db->fetchFirstColumn($queryBuilder->getSql(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());

            // one query for the texts of the whole page, ordered like the keys
            $rows = [];
            if ($keys && $languages) {
                $positions = array_flip(array_map('strval', $keys));
                $rows = $this->db->fetchAllAssociative(
                    'SELECT * FROM ' . $this->db->quoteIdentifier($this->getDatabaseTableName()) . ' WHERE `key` IN (:keys) AND `language` IN (:languages) ORDER BY `creationDate`',
                    ['keys' => $keys, 'languages' => $languages],
                    ['keys' => ArrayParameterType::STRING, 'languages' => ArrayParameterType::STRING]
                );
                usort($rows, fn (array $a, array $b) => ($positions[(string)$a['key']] ?? PHP_INT_MAX) <=> ($positions[(string)$b['key']] ?? PHP_INT_MAX));
            }

            $this->saveRowsToCache($cacheKey, $rows);
        }

        $rowsByKey = [];
        foreach ($rows as $row) {
            $rowsByKey[$row['key']][] = $row;
        }

        // keys without a row in the languages are left out, like Translation::getByKey() does not find them
        $translations = [];
        foreach ($rowsByKey as $keyRows) {
            $translations[] = $this->createTranslation($keyRows);
        }

        $this->model->setTranslations($translations);

        return $translations;
    }

    /**
     * @param non-empty-list<array<string, mixed>> $rows
     */
    private function createTranslation(array $rows): Model\Translation
    {
        $translation = new Model\Translation();
        $translation->setDomain($this->model->getDomain());
        $translation->getDao()->assignRows($rows);

        return $translation;
    }

    /**
     * The SQL holds placeholders, not their values: a listing with bound values is not cached, so two searches with
     * different values cannot share an entry
     *
     * @param string[] $languages
     */
    private function getCacheKey(DoctrineQueryBuilder $queryBuilder, array $languages = []): ?string
    {
        if ($queryBuilder->getParameters()) {
            return null;
        }

        return $this->getDatabaseTableName() . '_rows_' . md5($queryBuilder->getSql() . '|' . implode(',', $languages));
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function loadRowsFromCache(?string $cacheKey): ?array
    {
        if ($cacheKey === null) {
            return null;
        }

        $rows = Cache::load($cacheKey);

        return is_array($rows) ? $rows : null;
    }

    /**
     * Rows instead of model objects: they carry no services or event state into the cache
     *
     * @param list<array<string, mixed>> $rows
     */
    private function saveRowsToCache(?string $cacheKey, array $rows): void
    {
        if ($cacheKey !== null && count($rows) <= Listing::getCacheLimit()) {
            Cache::save($rows, $cacheKey, ['translator', 'translate'], null, 999);
        }
    }

    public function isCacheable(): bool
    {
        $count = $this->db->fetchOne('SELECT COUNT(*) FROM ' . $this->getDatabaseTableName());
        $cacheLimit = Listing::getCacheLimit();
        if ($count > $cacheLimit) {
            return false;
        }

        return true;
    }

    public function cleanup(): void
    {
        $keysToDelete = $this->db->fetchFirstColumn('SELECT `key` FROM ' . $this->getDatabaseTableName() . ' as tbl1 WHERE
               (SELECT count(*) FROM ' . $this->getDatabaseTableName() . " WHERE `key` = tbl1.`key` AND (`text` IS NULL OR `text` = ''))
               = (SELECT count(*) FROM " . $this->getDatabaseTableName() . ' WHERE `key` = tbl1.`key`) GROUP BY `key`;');

        if ($keysToDelete) {
            $preparedKeys = [];
            foreach ($keysToDelete as $value) {
                $preparedKeys[] = $this->db->quote($value);
            }

            $this->db->executeStatement('DELETE FROM ' . $this->getDatabaseTableName() . ' WHERE ' . '`key` IN (' . implode(',', $preparedKeys) . ')');
        }
    }

    /**
     * @param string|string[]|null $columns
     *
     */
    public function getQueryBuilder(...$columns): DoctrineQueryBuilder
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select(...$columns)->from($this->getDatabaseTableName());

        $this->applyListingParametersToQueryBuilder($queryBuilder);

        return $queryBuilder;
    }
}
