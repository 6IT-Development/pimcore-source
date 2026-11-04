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

use Symfony\Component\Console\Input\InputInterface;

/**
 * Reads options that take comma-separated values, either as a single value (`--locales=en,de`) or repeated
 * (`--tag=foo --tag=bar,baz`).
 *
 * @internal
 */
trait CsvOptionTrait
{
    /**
     * @return string[] the trimmed, non-empty, unique values; empty if the option was not passed
     */
    protected function getCsvOptionValues(InputInterface $input, string $name): array
    {
        return self::splitCsvValues($input->getOption($name));
    }

    /**
     * Splits values at commas, trims them and drops empty and duplicate values.
     *
     * `['foo', 'bar, baz', ' ']` and `'foo,bar,,baz'` both become `['foo', 'bar', 'baz']`.
     *
     * @param string|string[]|null $values
     *
     * @return string[]
     */
    protected static function splitCsvValues(string|array|null $values): array
    {
        $result = [];
        foreach ((array) $values as $value) {
            foreach (explode(',', (string) $value) as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $result[] = $part;
                }
            }
        }

        return array_values(array_unique($result));
    }
}
