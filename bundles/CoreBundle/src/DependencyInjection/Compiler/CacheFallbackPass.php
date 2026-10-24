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

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Must run before Symfony's CachePoolPass (priority 32), which sets the namespace and default_lifetime of each pool
 * based on the parent chain set here.
 *
 * @internal
 */
final class CacheFallbackPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('pimcore.cache.pool')) {
            $alias = new Alias('pimcore.cache.adapter.doctrine_dbal_tag_aware', true);
            $container->setAlias('pimcore.cache.pool', $alias);
        }

        // set default cache.app to Pimcore default cache, if not configured differently
        $appCache = $container->findDefinition('cache.app');
        if ($appCache instanceof ChildDefinition && $appCache->getParent() === 'cache.adapter.filesystem') {
            $this->replaceCacheDefinition($appCache);

            foreach ($container->findTaggedServiceIds('cache.pool') as $id => $arguments) {
                $cacheDef = $container->findDefinition($id);
                if ($cacheDef instanceof ChildDefinition && $cacheDef->getParent() === 'cache.app') {
                    $this->replaceCacheDefinition($cacheDef);
                }
            }
        }
    }

    private function replaceCacheDefinition(ChildDefinition $cacheDef): void
    {
        // arguments of the previous parent don't match the ProxyAdapter, CachePoolPass sets them afterwards
        $cacheDef->setArguments([]);
        $cacheDef->setParent('pimcore.cache.pool.app');
    }
}
