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

namespace Pimcore\Tests\Unit\OptionsProvider;

use LogicException;
use Pimcore\Bundle\CoreBundle\OptionsProvider\SelectOptionsOptionsProvider;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Tests\Support\Test\TestCase;

class SelectOptionsOptionsProviderTest extends TestCase
{
    public function testSelectDefaultValueIsKept(): void
    {
        $select = new Select();
        $select->setDefaultValue('a');

        $this->assertSame('a', (new SelectOptionsOptionsProvider())->getDefaultValue([], $select));
    }

    public function testMultiselectDefaultValueIsKept(): void
    {
        $multiselect = new Multiselect();
        $multiselect->setDefaultValue(['a', 'b']);

        $this->assertSame(['a', 'b'], (new SelectOptionsOptionsProvider())->getDefaultValue([], $multiselect));
    }

    public function testOtherFieldsHaveNoDefaultValue(): void
    {
        $this->assertNull((new SelectOptionsOptionsProvider())->getDefaultValue([], new Input()));
    }

    public function testMissingConfigurationIdNamesTheField(): void
    {
        $select = new Select();
        $select->setName('color');
        $select->setOptionsProviderData(' ');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('field "color"');

        (new SelectOptionsOptionsProvider())->getOptions([], $select);
    }
}
