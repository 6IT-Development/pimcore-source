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

namespace Pimcore\Model\DataObject\Listing\Concrete;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Exception;
use Pimcore;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Listing\Concrete;
use Pimcore\Tool;

/**
 * @internal
 *
 * @property Concrete $model
 */
class Dao extends Model\DataObject\Listing\Dao
{
    protected bool $firstException = true;

    private ?string $tableName = null;

    protected int $totalCount = 0;

    /**
     * @return int[]
     *
     * @throws Exception
     */
    public function loadIdList(): array
    {
        try {
            return parent::loadIdList();
        } catch (Exception $e) {
            return $this->exceptionHandler($e);
        }
    }

    /**
     * @throws Exception
     */
    public function getTotalCount(): int
    {
        try {
            return parent::getTotalCount();
        } catch (Exception $e) {
            if ($this->createMissingLocalizedTables($e)) {
                return parent::getTotalCount();
            }

            throw $e;
        }
    }

    /**
     *
     * @return int[]
     *
     * @throws Exception
     */
    protected function exceptionHandler(Exception $e): array
    {
        if ($this->createMissingLocalizedTables($e)) {
            return $this->loadIdList();
        }

        throw $e;
    }

    /**
     * Creates the localized tables and views once, when the table of the listing is missing (e.g. for a language
     * added after the class was saved).
     */
    private function createMissingLocalizedTables(Exception $e): bool
    {
        if (!$e instanceof TableNotFoundException || !$this->firstException) {
            return false;
        }
        $this->firstException = false;

        $localizedFields = new DataObject\Localizedfield();
        $localizedFields->setClass(DataObject\ClassDefinition::getById($this->model->getClassId()));
        $localizedFields->createUpdateTable();

        return true;
    }

    /**
     * Forgets the table resolved from the locale, so it is resolved again for the next query.
     *
     * @internal
     */
    public function resetTableName(): void
    {
        $this->tableName = null;
    }

    /**
     *
     * @throws Exception
     */
    public function getLocalizedBrickLanguage(): ?string
    {
        $language = null;

        // check for a localized field and if they should be used for this list

        if ($this->model->getLocale()) {
            if (Tool::isValidLanguage((string)$this->model->getLocale())) {
                $language = (string)$this->model->getLocale();
            }
        }

        if (!$language) {
            $locale = Pimcore::getContainer()->get(LocaleServiceInterface::class)->findLocale();
            if (Tool::isValidLanguage((string)$locale)) {
                $language = (string)$locale;
            }
        }

        if (!$language) {
            $language = Tool::getDefaultLanguage();
        }

        return $language;
    }

    /**
     *
     * @throws Exception
     */
    public function getTableName(): string
    {
        if (empty($this->tableName)) {
            // default
            $this->tableName = 'object_' . $this->model->getClassId();

            if (!$this->model->getIgnoreLocalizedFields()) {
                $language = null;
                // check for a localized field and if they should be used for this list
                if (property_exists('\\Pimcore\\Model\\DataObject\\' . ucfirst($this->model->getClassName()), 'localizedfields')) {
                    if ($this->model->getLocale()) {
                        if (Tool::isValidLanguage((string)$this->model->getLocale())) {
                            $language = (string)$this->model->getLocale();
                        }
                        if (!$language && DataObject\Localizedfield::isStrictMode()) {
                            throw new Exception('could not resolve locale: ' . $this->model->getLocale());
                        }
                    }

                    if (!$language) {
                        $locale = Pimcore::getContainer()->get(LocaleServiceInterface::class)->findLocale();
                        if (Tool::isValidLanguage((string)$locale)) {
                            $language = (string)$locale;
                        }
                    }

                    if (!$language) {
                        $language = Tool::getDefaultLanguage();
                    }

                    if (!$language) {
                        throw new Exception('No valid language/locale set. Use $list->setLocale() to add a language to the listing, or register a global locale');
                    }
                    $this->tableName = 'object_localized_' . $this->model->getClassId() . '_' . $language;
                }
            }
        }

        return $this->tableName;
    }

    /**
     *
     * @return $this
     *
     * @throws Exception
     */
    protected function applyJoins(DoctrineQueryBuilder $queryBuilder): static
    {
        // add fielcollection's
        $fieldCollections = $this->model->getFieldCollections();
        if (!empty($fieldCollections)) {
            foreach ($fieldCollections as $fc) {
                // join info
                $table = 'object_collection_' . $fc['type'] . '_' . $this->model->getClassId();
                $name = $fc['type'];
                if (!empty($fc['fieldname'])) {
                    $name .= '~' . $fc['fieldname'];
                }

                // set join condition
                $condition = <<<CONDITION
1
 AND {$this->db->quoteIdentifier($name)}.id = {$this->db->quoteIdentifier($this->getTableName())}.id
CONDITION;

                if (!empty($fc['fieldname'])) {
                    $condition .= <<<CONDITION
 AND {$this->db->quoteIdentifier($name)}.fieldname = "{$fc['fieldname']}"
CONDITION;
                }

                // add join
                $queryBuilder->leftJoin($this->getTableName(), $table, $this->db->quoteIdentifier($name), $condition);
            }
        }

        // add brick's
        $objectbricks = $this->model->getObjectbricks();
        if (!empty($objectbricks)) {
            foreach ($objectbricks as $ob) {
                $brickDefinition = DataObject\Objectbrick\Definition::getByKey($ob);
                if (!$brickDefinition instanceof DataObject\Objectbrick\Definition) {
                    continue;
                }

                // join info
                $table = 'object_brick_query_' . $ob . '_' . $this->model->getClassId();
                $name = $ob;

                // add join
                $queryBuilder->leftJoin($this->getTableName(), $table, $this->db->quoteIdentifier($name),
                    <<<CONDITION
1
AND {$this->db->quoteIdentifier($name)}.id = {$this->db->quoteIdentifier($this->getTableName())}.id
CONDITION
                );

                if ($brickDefinition->getFieldDefinition('localizedfields')) {
                    $langugage = $this->getLocalizedBrickLanguage();
                    //TODO wrong pattern
                    $localizedTable = 'object_brick_localized_query_' . $ob . '_' . $this->model->getClassId() . '_' . $langugage;
                    $name = $ob . '_localized';

                    // add join
                    $queryBuilder->leftJoin($this->getTableName(), $localizedTable, $this->db->quoteIdentifier($name),
                        <<<CONDITION
1
AND {$this->db->quoteIdentifier($name)}.ooo_id = {$this->db->quoteIdentifier($this->getTableName())}.id
CONDITION
                    );
                }
            }
        }

        return $this;
    }
}
