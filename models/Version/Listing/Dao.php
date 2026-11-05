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

namespace Pimcore\Model\Version\Listing;

use Pimcore\Model;
use Pimcore\Model\Version\Listing;

/**
 * @internal
 *
 * @property Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    protected function getCondition(): string
    {
        $condition = parent::getCondition();
        if (!$this->model->isLoadAutoSave()) {
            if (trim($condition)) {
                $condition .= ' AND autoSave = 0';
            } else {
                $condition = ' WHERE autoSave = 0';
            }
        }

        return $condition;
    }

    /**
     * Loads a list of versions for the specified parameters, returns an array of Version elements
     *
     * @return Model\Version[]
     */
    public function load(): array
    {
        $versions = [];
        $rows = $this->db->fetchAllAssociative('SELECT * FROM versions' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        // hydrated from the rows: one query, and no null entry for a version deleted in between
        foreach ($rows as $row) {
            $version = new Model\Version();
            $version->getDao()->assignRow($row);
            $versions[] = $version;
        }

        $this->model->setVersions($versions);

        return $versions;
    }

    /**
     * @return int[]
     */
    public function loadIdList(): array
    {
        $versionIds = $this->db->fetchFirstColumn('SELECT id FROM versions' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        return array_map('intval', $versionIds);
    }

    public function getTotalCount(): int
    {
        return (int)$this->db->fetchOne('SELECT COUNT(*) FROM versions ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
    }
}
