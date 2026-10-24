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

namespace Pimcore\Tests\Unit\Command;

use Pimcore\Bundle\CoreBundle\Command\Traits\IdOptionTrait;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

/**
 * The --id values of the asset commands end up in an `id IN (...)` SQL condition, so they must be integers.
 */
class IdOptionTraitTest extends TestCase
{
    private function parse(array $ids): array
    {
        $input = new ArrayInput(
            $ids === [] ? [] : ['--id' => $ids],
            new InputDefinition([new InputOption('id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)])
        );

        $parser = new class() {
            use IdOptionTrait {
                getIdOptionValues as public;
            }
        };

        return $parser->getIdOptionValues($input);
    }

    public function testNotGiven(): void
    {
        $this->assertSame([], $this->parse([]));
    }

    public function testRepeatedAndCommaSeparated(): void
    {
        $this->assertSame([1, 2, 3], $this->parse(['1, 2', '3', '2']));
    }

    /**
     * @dataProvider invalidProvider
     */
    public function testInvalidValuesAreRejected(array $ids): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->parse($ids);
    }

    public static function invalidProvider(): array
    {
        return [
            'SQL' => [['1) OR (1=1']],
            'non-numeric' => [['abc']],
            'space-separated' => [['1 2']],
            'zero' => [['0']],
            'negative' => [['-1']],
            'float' => [['1.5']],
            'empty' => [[',']],
        ];
    }
}
