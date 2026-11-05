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

namespace Pimcore\Model\Property\Predefined\Listing;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Property;
use Pimcore\Model\Property\Predefined\Listing;

/**
 * @internal
 *
 * @property Listing $model
 */
class Dao extends Model\Property\Predefined\Dao
{
    /**
     * Loads a list of predefined properties for the specicifies parameters, returns an array of Property\Predefined elements
     *
     * @return list<Model\Property\Predefined>
     */
    public function loadList(): array
    {
        $properties = [];

        // the ids of the store that is read, those of the other store (e.g. after switching the location) do not load
        foreach ($this->loadIdListByReadTargets() as $id) {
            $definition = Model\Property\Predefined::getById((string)$id);
            if (!$definition) {
                Logger::warning('Predefined property definition ' . $id . ' could not be loaded, it is skipped');

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

        $this->model->setProperties($properties);

        return $properties;
    }

    public function getTotalCount(): int
    {
        return count($this->loadList());
    }
}
