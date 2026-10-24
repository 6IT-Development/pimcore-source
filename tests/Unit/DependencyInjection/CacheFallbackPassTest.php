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

use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\CacheFallbackPass;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\DependencyInjection\CachePoolPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\CacheClearer\Psr6CacheClearer;

class CacheFallbackPassTest extends TestCase
{
    public function testChildPoolsOfCacheAppKeepNamespaceAndDefaultLifetime(): void
    {
        $container = $this->createContainer();
        $container->setDefinition('foo', new ChildDefinition('cache.app'))
            ->addTag('cache.pool', ['default_lifetime' => 60]);
        $container->setDefinition('bar', new ChildDefinition('cache.app'))
            ->addTag('cache.pool');

        // same order as in the kernel: CacheFallbackPass (33) before CachePoolPass (32)
        new CacheFallbackPass()->process($container);
        new CachePoolPass()->process($container);

        $foo = $container->getDefinition('foo');
        $bar = $container->getDefinition('bar');
        $app = $container->getDefinition('cache.app');

        $this->assertInstanceOf(ChildDefinition::class, $foo);
        $this->assertSame('pimcore.cache.pool.app', $foo->getParent());
        $this->assertSame('pimcore.cache.pool.app', $app->getParent());

        $this->assertEquals(new Reference('pimcore.cache.pool'), $foo->getArgument(0));
        $this->assertSame(60, $foo->getArgument(2));

        $namespaces = [$foo->getArgument(1), $bar->getArgument(1), $app->getArgument(1)];
        foreach ($namespaces as $namespace) {
            $this->assertIsString($namespace);
            $this->assertNotSame('', $namespace);
        }
        $this->assertCount(3, array_unique($namespaces), 'Each pool needs its own namespace');
    }

    public function testCustomCacheAppAdapterIsNotReplaced(): void
    {
        $container = $this->createContainer();
        $container->setDefinition('cache.app', new ChildDefinition('cache.adapter.array'))
            ->addTag('cache.pool');

        new CacheFallbackPass()->process($container);

        $this->assertSame('cache.adapter.array', $container->getDefinition('cache.app')->getParent());
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('cache.prefix.seed', 'test');

        // the relevant parts of FrameworkBundle's cache.php
        $container->setDefinition('cache.default_clearer', new Definition(Psr6CacheClearer::class, [[]]));
        $container->setDefinition('cache.app_clearer', new Definition(Psr6CacheClearer::class, [[]]));
        $container->setDefinition('cache.adapter.filesystem', new Definition(FilesystemAdapter::class, ['', 0, null]))
            ->setAbstract(true)
            ->addTag('cache.pool', ['clearer' => 'cache.default_clearer', 'reset' => 'reset']);
        $container->setDefinition('cache.adapter.array', new Definition(ArrayAdapter::class, [0]))
            ->setAbstract(true)
            ->addTag('cache.pool', ['clearer' => 'cache.default_clearer', 'reset' => 'reset']);
        $container->setDefinition('cache.app', new ChildDefinition('cache.adapter.filesystem'))
            ->setPublic(true)
            ->addTag('cache.pool', ['clearer' => 'cache.app_clearer']);

        $container->setDefinition('pimcore.cache.pool', new Definition(ArrayAdapter::class));

        new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 3) . '/bundles/CoreBundle/config'))
            ->load('cache.yaml');

        return $container;
    }
}
