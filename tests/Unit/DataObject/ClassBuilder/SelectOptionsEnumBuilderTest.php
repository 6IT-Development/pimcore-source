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

namespace Pimcore\Tests\Unit\DataObject\ClassBuilder;

use Pimcore\DataObject\ClassBuilder\SelectOptionsEnumBuilder;
use Pimcore\Model\DataObject\SelectOptions\Config;
use Pimcore\Model\DataObject\SelectOptions\Data\SelectOption;
use Pimcore\Tests\Support\Test\TestCase;

class SelectOptionsEnumBuilderTest extends TestCase
{
    /**
     * C-07: values and labels with backslashes and quotes are emitted as literals that PHP reads back unchanged.
     */
    public function testBackslashesAndQuotesRoundTrip(): void
    {
        $options = [
            ['C:\\', 'Trailing backslash \\', 'TrailingBackslash'],
            ['a\\\\b', 'Double \\\\ backslash', 'DoubleBackslash'],
            ["it's", "Quote ' label", 'Quote'],
            ["\\'", "\\' escaped quote", 'EscapedQuote'],
            ["x'; } system('id'); //", 'Payload', 'Payload'],
        ];

        $enumName = 'SelectOptionsEnumBuilderTest' . bin2hex(random_bytes(4));
        $config = new Config();
        $config->setId($enumName);
        $config->setSelectOptions(...array_map(
            static fn (array $option) => new SelectOption($option[0], $option[1], $option[2]),
            $options
        ));

        $code = new SelectOptionsEnumBuilder()->buildEnum($config);

        // must parse, and evaluating it must only declare the enum
        eval('?>' . $code);

        $enumClass = $config->getEnumName(true);
        foreach ($options as [$value, $label, $name]) {
            $case = constant($enumClass . '::' . $name);
            $this->assertSame($value, $case->value);
            $this->assertSame($label, $case->getLabel());
            $this->assertSame($case, $enumClass::from($value));
        }
    }
}
