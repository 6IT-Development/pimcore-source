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

use Monolog\Handler\BufferHandler;
use Monolog\Handler\FingersCrossedHandler;
use Pimcore\Helper\LongRunningHelper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers the buffering Monolog handlers (buffer, fingers crossed) on the LongRunningHelper, which flushes them
 * in long-running processes.
 *
 * @internal
 */
final class LongRunningHelperPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $helperDefinition = $container->getDefinition(LongRunningHelper::class);
        foreach ($container->getDefinitions() as $serviceId => $definition) {
            if (str_starts_with($serviceId, 'monolog.handler.')) {
                // MonologBundle defines its handlers with a class; a handler without one (e.g. a child definition
                // inheriting it) is skipped
                $class = $container->getParameterBag()->resolveValue($definition->getClass());
                if (is_string($class)
                    && (is_a($class, BufferHandler::class, true) || is_a($class, FingersCrossedHandler::class, true))) {
                    $helperDefinition->addMethodCall('addMonologHandler', [new Reference($serviceId)]);
                }
            }
        }
    }
}
