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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Sets a pimcore.service_controllers parameter which contains all controllers registered as service
 * as an id => class mapping. Controllers are recognized by the "controller.service_arguments" DI tag, which
 * autoconfiguration adds to AbstractController subclasses and #[AsController] classes.
 *
 * @internal
 */
final class ServiceControllersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $serviceControllers = [];

        foreach ($container->findTaggedServiceIds('controller.service_arguments') as $id => $tags) {
            $definition = $container->findDefinition($id);
            if ($definition->isAbstract()) {
                continue;
            }

            // services named after their class have no class until ResolveClassPass, class parameters are resolved
            $class = $container->getParameterBag()->resolveValue($definition->getClass() ?? $id);
            if (is_string($class)) {
                $serviceControllers[$id] = $class;
            }
        }

        ksort($serviceControllers);

        $container->setParameter('pimcore.service_controllers', $serviceControllers);
    }
}
