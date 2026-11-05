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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\DependencyInjection;

use Exception;
use LogicException;
use phpDocumentor\Reflection\DocBlockFactory;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Configuration\ExecutionContextInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums\ErrorHandlingMode;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

class PimcoreGenericExecutionEngineExtension extends Extension
{
    /**
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->checkSerializerDependencies();

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $definition = $container->getDefinition(ExecutionContextInterface::class);
        $definition->setArgument('$contexts', $config['execution_context'] ?? []);

        $definition = $container->getDefinition(JobExecutionAgentInterface::class);
        $definition->setArgument(
            '$errorHandlingMode',
            $config['error_handling'] ?? ErrorHandlingMode::CONTINUE_ON_ERROR->value
        );
    }

    /**
     * Job runs read the step and selection types of their job from its docblocks. Both packages are only suggested by
     * pimcore/pimcore, without them every job run load would fail, so the bundle refuses to boot instead.
     */
    private function checkSerializerDependencies(): void
    {
        if (!class_exists(PropertyInfoExtractor::class) || !class_exists(DocBlockFactory::class)) {
            throw new LogicException(
                'The PimcoreGenericExecutionEngineBundle requires "symfony/property-info" and ' .
                '"phpdocumentor/reflection-docblock". Try running ' .
                '"composer require symfony/property-info phpdocumentor/reflection-docblock".'
            );
        }
    }
}
