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

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 */
trait IdOptionTrait
{
    /**
     * Parses a repeatable, comma-separated ID option (e.g. `--id=1,2 --id=3`) into a list of positive integers.
     *
     * An empty array means the option was not passed. If it was passed, the result is never empty, so a typo like
     * `--id=,` can't silently drop the filter and widen the command to all elements.
     *
     * @return int[]
     *
     * @throws InvalidArgumentException if a value is not a positive integer or the option contains no ID
     */
    protected function getIdOptionValues(InputInterface $input, string $name = 'id'): array
    {
        $values = (array) $input->getOption($name);
        if ($values === []) {
            return [];
        }

        $ids = [];
        foreach ($values as $value) {
            foreach (explode(',', (string) $value) as $id) {
                $id = trim($id);
                if ($id === '') {
                    continue;
                }

                $intId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($intId === false) {
                    throw new InvalidArgumentException(sprintf('Invalid value "%s" for option --%s: expected a positive integer ID.', $id, $name));
                }

                $ids[] = $intId;
            }
        }

        if ($ids === []) {
            throw new InvalidArgumentException(sprintf('Option --%s was given without an ID.', $name));
        }

        return array_values(array_unique($ids));
    }
}
