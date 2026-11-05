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

namespace Pimcore\Model\Metadata\Predefined\Listing;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Metadata\Predefined;
use Pimcore\Model\Metadata\Predefined\Listing;

/**
 * @internal
 *
 * @property Listing $model
 */
class Dao extends Model\Metadata\Predefined\Dao
{
    /**
     * Loads a list of predefined metadata definitions for the specicified parameters, returns an array of
     * Metadata\Predefined elements
     *
     * @return list<Predefined>
     */
    public function loadList(): array
    {
        $properties = [];
        // the ids of the store that is read, those of the other store (e.g. after switching the location) do not load
        foreach ($this->loadIdListByReadTargets() as $id) {
            $definition = Predefined::getById((string)$id);
            if (!$definition) {
                Logger::warning('Predefined asset metadata definition ' . $id . ' could not be loaded, it is skipped');

                continue;
            }
            $properties[] = $definition;
        }
        if ($this->model->getFilter()) {
            $properties = array_values(array_filter($properties, $this->model->getFilter()));
        }
        if ($this->model->getOrder()) {
            usort($properties, $this->model->getOrder());
        }

        $this->model->setDefinitions($properties);

        return $properties;
    }

    public function getTotalCount(): int
    {
        return count($this->loadList());
    }
}
