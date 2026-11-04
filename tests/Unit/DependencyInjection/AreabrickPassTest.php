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

namespace Pimcore\Tests\Unit\DependencyInjection;

use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\AreabrickPass;
use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;
use Pimcore\Extension\Document\Areabrick\AreabrickManager;
use Pimcore\Extension\Document\Areabrick\Attribute\AsAreabrick;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AreabrickPassTest extends TestCase
{
    /**
     * B-30: a brick with a tag from the service config and one added for #[AsAreabrick] is registered once.
     */
    public function testTagAndAttributeWithTheSameIdRegisterTheBrickOnce(): void
    {
        $container = $this->createContainer();
        $container->setDefinition('app.brick', new Definition(AreabrickPassTestBrick::class))
            ->addTag('pimcore.area.brick', ['id' => 'teaser'])
            ->addTag('pimcore.area.brick', ['id' => null]);

        new AreabrickPass()->process($container);

        $this->assertEquals(
            [['registerService', ['teaser', 'app.brick']]],
            $container->getDefinition(AreabrickManager::class)->getMethodCalls()
        );
        $this->assertEquals(
            ['teaser' => new Reference('app.brick')],
            $container->getDefinition('pimcore.document.areabrick.brick_locator')->getArgument(0)
        );
    }

    public function testTagAndAttributeWithDifferentIdsFail(): void
    {
        $container = $this->createContainer();
        $container->setDefinition('app.brick', new Definition(AreabrickPassTestBrick::class))
            ->addTag('pimcore.area.brick', ['id' => 'other'])
            ->addTag('pimcore.area.brick', ['id' => null]);

        $this->expectException(InvalidConfigurationException::class);
        new AreabrickPass()->process($container);
    }

    public function testBrickIdIsGeneratedFromTheClassName(): void
    {
        $container = $this->createContainer();
        $container->setDefinition('app.brick', new Definition(AreabrickPassTestGeneratedIdBrick::class))
            ->addTag('pimcore.area.brick');

        new AreabrickPass()->process($container);

        $this->assertEquals(
            [['registerService', ['areabrick-pass-test-generated-id-brick', 'app.brick']]],
            $container->getDefinition(AreabrickManager::class)->getMethodCalls()
        );
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(AreabrickManager::class, new Definition(AreabrickManager::class));
        $container->setDefinition('pimcore.document.areabrick.brick_locator', new Definition())->setArguments([[]]);

        return $container;
    }
}

#[AsAreabrick(id: 'teaser')]
final class AreabrickPassTestBrick extends AbstractTemplateAreabrick
{
}

final class AreabrickPassTestGeneratedIdBrick extends AbstractTemplateAreabrick
{
}
