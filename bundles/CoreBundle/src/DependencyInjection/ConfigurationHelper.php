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

namespace Pimcore\Bundle\CoreBundle\DependencyInjection;

use Pimcore\Config\LocationAwareConfigRepository;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

/**
 * Builds the `config_location` nodes of configuration trees and reads their YAML files. Used by Pimcore bundles
 * (e.g. CustomReports, StaticRoutes) for their own admin-editable configurations.
 *
 * @api
 */
final class ConfigurationHelper
{
    public static function addConfigLocationWithWriteTargetNodes(ArrayNodeDefinition $rootNode, array $nodes, array $additionalNodes = []): NodeBuilder
    {
        $storageNode = $rootNode
            ->children()
            ->arrayNode(LocationAwareConfigRepository::CONFIG_LOCATION)
            ->addDefaultsIfNotSet()
            ->children();

        foreach ($nodes as $node => $dir) {
            self::addConfigLocationTargetNode($storageNode, $node, $dir, $additionalNodes);
        }

        return $storageNode;
    }

    public static function addConfigLocationTargetNode(NodeBuilder $node, string $name, string $folder, array $additionalNodes = []): void
    {
        $folder = self::resolveConfigDirectory($folder);

        $targets = $node
            ->arrayNode($name)
                ->addDefaultsIfNotSet()
                ->children();

        $targets
            ->arrayNode(LocationAwareConfigRepository::WRITE_TARGET)
                ->addDefaultsIfNotSet()
                ->children()
                    ->enumNode(LocationAwareConfigRepository::TYPE)
                        ->values([LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG, LocationAwareConfigRepository::LOCATION_SETTINGS_STORE, LocationAwareConfigRepository::LOCATION_DISABLED])
                        ->defaultValue(LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG)
                    ->end()
                    ->arrayNode(LocationAwareConfigRepository::OPTIONS)
                        ->defaultValue([LocationAwareConfigRepository::DIRECTORY => $folder])
                        ->variablePrototype()->end()
                    ->end()
                ->end()
            ->end();

        if (in_array(LocationAwareConfigRepository::READ_TARGET, $additionalNodes, true)) {
            $targets
                ->arrayNode(LocationAwareConfigRepository::READ_TARGET)
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode(LocationAwareConfigRepository::TYPE)
                            ->values([LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG, LocationAwareConfigRepository::LOCATION_SETTINGS_STORE])
                            ->defaultNull()
                        ->end()
                        ->arrayNode(LocationAwareConfigRepository::OPTIONS)
                            ->defaultValue([LocationAwareConfigRepository::DIRECTORY => null])
                            ->variablePrototype()->end()
                        ->end()
                    ->end()
                ->end();
        }
    }

    /**
     * Resolves a config directory against the project root, unless it is an absolute path inside the project root,
     * PIMCORE_PRIVATE_VAR or PIMCORE_CONFIGURATION_DIRECTORY (which may be located outside the project root).
     *
     * @param string[]|null $absoluteBaseDirectories
     *
     * @internal
     */
    public static function resolveConfigDirectory(string $folder, ?string $projectRoot = null, ?array $absoluteBaseDirectories = null): string
    {
        $projectRoot ??= PIMCORE_PROJECT_ROOT;
        $absoluteBaseDirectories ??= [$projectRoot, PIMCORE_PRIVATE_VAR, PIMCORE_CONFIGURATION_DIRECTORY];

        if (Path::isAbsolute($folder)) {
            foreach ($absoluteBaseDirectories as $baseDirectory) {
                if (Path::isBasePath($baseDirectory, $folder)) {
                    return $folder;
                }
            }
        }

        // relative paths, and for BC reasons project relative paths with a leading slash (e.g. "/var/config/foo")
        return Path::join($projectRoot, $folder);
    }

    public static function getSymfonyConfigFiles(string $configPath, array $params = []): array
    {
        if (!is_dir($configPath)) {
            return [];
        }

        $finder = (new Finder())
            ->files()
            ->in($configPath)
            ->name(['*.yml', '*.yaml'])
            ->sortByName();

        $result = [];
        $relativePath = $params['relativePath'] ?? false;
        if ($relativePath) {
            trigger_deprecation('pimcore/pimcore', '11.5', 'The "relativePath" option of "%s()" is deprecated and will be removed in Pimcore 12.', __METHOD__);
        }

        foreach ($finder as $file) {
            $result[] = $relativePath ? $file->getRelativePathname() : $file->getRealPath();
        }

        return $result;
    }

    public static function getConfigNodeFromSymfonyTree(ContainerBuilder $container, string $nodeName): array
    {
        // one config array per file - let the processor deep merge them, like the extension does
        $containerConfigs = $container->getExtensionConfig($nodeName);

        $configuration = $container->getExtension($nodeName)->getConfiguration($containerConfigs, $container);
        if (!$configuration instanceof ConfigurationInterface) {
            throw new LogicException(sprintf('The extension "%s" does not provide a configuration.', $nodeName));
        }

        $processor = new Processor();
        $containerConfig = $processor->processConfiguration($configuration, $containerConfigs);

        return $container->getParameterBag()->resolveValue($containerConfig);
    }
}
