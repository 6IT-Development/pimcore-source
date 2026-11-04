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

namespace Pimcore\Model\Site\Listing;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Site\Listing;

/**
 * @internal
 *
 * @property Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of thumanils for the specicifies parameters, returns an array of Thumbnail elements
     *
     */
    public function load(): array
    {
        $sites = [];
        $sitesData = $this->db->fetchFirstColumn('SELECT id FROM sites' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());

        foreach ($sitesData as $siteId) {
            // a site deleted between the query and the lookup
            $site = Model\Site::getById((int) $siteId);
            if (!$site) {
                Logger::warning(sprintf('Site %s could not be loaded and is skipped in the listing', $siteId));

                continue;
            }
            $sites[] = $site;
        }

        $this->model->setSites($sites);

        return $sites;
    }

    /**
     * A broken condition throws, as in load(), instead of counting 0 sites
     */
    public function getTotalCount(): int
    {
        return (int)$this->db->fetchOne('SELECT COUNT(*) FROM sites ' . $this->getCondition(), $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
    }
}
