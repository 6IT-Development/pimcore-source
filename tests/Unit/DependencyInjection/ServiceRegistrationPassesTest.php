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

use InvalidArgumentException;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\PublicServicesPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\RegisterMaintenanceTaskPass;
use Pimcore\Maintenance\Executor;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ServiceRegistrationPassesTest extends TestCase
{
    public function testMaintenanceTaskPassRegistersEveryTag(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(Executor::class, new Definition(Executor::class));
        $container->setDefinition('app.task', new Definition('App\Task'))
            ->addTag('pimcore.maintenance.task', ['type' => 'first'])
            ->addTag('pimcore.maintenance.task', ['type' => 'second', 'messengerMessageClass' => 'App\Message']);

        new RegisterMaintenanceTaskPass()->process($container);

        $this->assertEquals(
            [
                ['registerTask', ['first', new Reference('app.task'), null]],
                ['registerTask', ['second', new Reference('app.task'), 'App\Message']],
            ],
            $container->getDefinition(Executor::class)->getMethodCalls()
        );
    }

    public function testMaintenanceTaskPassRequiresAType(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(Executor::class, new Definition(Executor::class));
        $container->setDefinition('app.task', new Definition('App\Task'))->addTag('pimcore.maintenance.task');

        $this->expectException(InvalidArgumentException::class);
        new RegisterMaintenanceTaskPass()->process($container);
    }

    public function testPublicServicesPassHandlesDefinitionsAliasesAndMissingServices(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('html_sanitizer.sanitizer.pimcore.wysiwyg_sanitizer', new Definition('stdClass'));
        $container->setDefinition('app.bus', new Definition('stdClass'));
        $container->setAlias('messenger.bus.pimcore-core', 'app.bus');
        // html_sanitizer.sanitizer.pimcore.translation_sanitizer is missing

        new PublicServicesPass()->process($container);

        $this->assertTrue($container->getDefinition('html_sanitizer.sanitizer.pimcore.wysiwyg_sanitizer')->isPublic());
        $this->assertTrue($container->getAlias('messenger.bus.pimcore-core')->isPublic());
        $this->assertFalse($container->getDefinition('app.bus')->isPublic());
    }
}
