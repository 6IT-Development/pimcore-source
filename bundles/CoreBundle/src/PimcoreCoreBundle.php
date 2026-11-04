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

namespace Pimcore\Bundle\CoreBundle;

use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\AreabrickAutoloadPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\AreabrickPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\CacheFallbackPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\HtmlSanitizerPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\ImageAdapterAliasPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\LongRunningHelperPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\MessageBusPublicPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\MonologPsrLogMessageProcessorPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\MonologPublicLoggerPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\ProfilerAliasPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\RegisterMaintenanceTaskPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\RoutingLoaderPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\SerializerPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\ServiceControllersPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\TranslationSanitizerPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\WorkflowPass;
use Pimcore\Bundle\CoreBundle\DependencyInjection\PimcoreCoreExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @internal
 */
class PimcoreCoreBundle extends Bundle
{
    /**
     * Overridden because the extension alias is "pimcore", not the "pimcore_core" that Bundle expects.
     */
    public function getContainerExtension(): ExtensionInterface
    {
        if (!$this->extension instanceof ExtensionInterface) {
            $this->extension = new PimcoreCoreExtension();
        }

        return $this->extension;
    }

    /**
     * All passes have an explicit type and priority: with the defaults they would depend on the bundle registration
     * order (FrameworkBundle and MonologBundle are registered before this bundle). In TYPE_BEFORE_OPTIMIZATION,
     * autoconfiguration (ResolveInstanceofConditionalsPass, AttributeAutoconfigurationPass) runs at 100 and most
     * FrameworkBundle/MonologBundle passes at 0.
     */
    public function build(ContainerBuilder $container): void
    {
        $beforeOptimization = PassConfig::TYPE_BEFORE_OPTIMIZATION;

        // before the autoconfiguration passes (100), so the autoloaded bricks are autoconfigured
        $container->addCompilerPass(new AreabrickAutoloadPass(), $beforeOptimization, 101);
        // before CachePoolPass (32), see CacheFallbackPass
        $container->addCompilerPass(new CacheFallbackPass(), $beforeOptimization, 33);
        // before Monolog's LoggerChannelPass and FrameworkBundle's WorkflowGuardListenerPass (both 0): they process the
        // monolog.logger tag of the audit trail listener and the workflow.has_guard_listeners parameter set by this pass
        $container->addCompilerPass(new WorkflowPass(), $beforeOptimization, 1);

        // after autoconfiguration (100), which adds the tags or attributes these passes read
        $container->addCompilerPass(new AreabrickPass(), $beforeOptimization, 0);
        $container->addCompilerPass(new RegisterMaintenanceTaskPass(), $beforeOptimization, 0);
        $container->addCompilerPass(new SerializerPass(), $beforeOptimization, 0);
        $container->addCompilerPass(new ServiceControllersPass(), $beforeOptimization, 0);

        // after Monolog's LoggerChannelPass (0), which creates the monolog.logger.<channel> services
        $container->addCompilerPass(new MonologPublicLoggerPass(), $beforeOptimization, -1);
        // after Monolog's extension, which registers the processor only if a handler uses it
        $container->addCompilerPass(new MonologPsrLogMessageProcessorPass(), $beforeOptimization, 0);
        // after Monolog's extension, which defines the monolog.handler.* services
        $container->addCompilerPass(new LongRunningHelperPass(), $beforeOptimization, 0);

        // services and aliases defined by the extensions; before RemoveUnusedDefinitionsPass (TYPE_REMOVE) drops private ones
        $container->addCompilerPass(new RoutingLoaderPass(), $beforeOptimization, 0);
        $container->addCompilerPass(new ProfilerAliasPass(), $beforeOptimization, 0);
        $container->addCompilerPass(new MessageBusPublicPass(), $beforeOptimization, 0);
        $container->addCompilerPass(new HtmlSanitizerPass(), $beforeOptimization, 0);
        $container->addCompilerPass(new TranslationSanitizerPass(), $beforeOptimization, 0);
        $container->addCompilerPass(new ImageAdapterAliasPass(), $beforeOptimization, 0);
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
