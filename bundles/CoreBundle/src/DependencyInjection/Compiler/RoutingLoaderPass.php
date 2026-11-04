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

use Pimcore\Routing\Loader\AttributeRouteControllerLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Replaces the class of the attribute route loader with AttributeRouteControllerLoader, which normalizes the
 * generated default route names of admin controllers: the prefix pimcore_admin_admin_ becomes pimcore_admin_.
 *
 * @internal
 */
final class RoutingLoaderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('routing.loader.attribute')) {
            return;
        }

        $definition = $container->getDefinition('routing.loader.attribute');
        $definition->setClass(AttributeRouteControllerLoader::class);
    }
}
