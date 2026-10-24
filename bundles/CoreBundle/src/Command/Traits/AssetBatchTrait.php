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

namespace Pimcore\Bundle\CoreBundle\Command\Traits;

use Pimcore;
use Pimcore\Model\Asset;

/**
 * @internal
 */
trait AssetBatchTrait
{
    /**
     * Iterates the assets matching all conditions in batches, ordered by ID.
     *
     * Uses keyset pagination (`id > :lastId ORDER BY id`) instead of OFFSET: without a stable order, OFFSET pages can
     * overlap or leave gaps, and assets that are created, deleted or stop matching the conditions during the run
     * shift the following pages.
     *
     * @param string[] $conditions SQL conditions, combined with AND
     *
     * @return iterable<Asset>
     */
    protected function iterateAssets(array $conditions, array $conditionVariables = [], int $batchSize = 10): iterable
    {
        $conditions[] = 'id > ?';
        $condition = implode(' AND ', array_map(static fn (string $condition) => '(' . $condition . ')', $conditions));
        $lastId = 0;

        do {
            $list = new Asset\Listing();
            $list->setCondition($condition, [...$conditionVariables, $lastId]);
            $list->setOrderKey('id');
            $list->setOrder('ASC');
            $list->setLimit($batchSize);
            $assets = $list->load();

            foreach ($assets as $asset) {
                $lastId = $asset->getId();

                yield $asset;
            }

            Pimcore::collectGarbage();
        } while (count($assets) === $batchSize);
    }
}
