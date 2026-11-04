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

use InvalidArgumentException;
use Pimcore\Maintenance\Executor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers the services tagged "pimcore.maintenance.task" (or with the #[AsMaintenanceTask] attribute) at the
 * maintenance executor. A service can carry several tags to run under several types.
 *
 * @internal
 */
final class RegisterMaintenanceTaskPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(Executor::class)) {
            return;
        }

        $definition = $container->getDefinition(Executor::class);

        foreach ($container->findTaggedServiceIds('pimcore.maintenance.task') as $id => $tags) {
            foreach ($tags as $tag) {
                if (!isset($tag['type'])) {
                    throw new InvalidArgumentException(sprintf('Tagged maintenance task "%s" needs a "type" attribute.', $id));
                }

                $definition->addMethodCall('registerTask', [$tag['type'], new Reference($id), $tag['messengerMessageClass'] ?? null]);
            }
        }
    }
}
