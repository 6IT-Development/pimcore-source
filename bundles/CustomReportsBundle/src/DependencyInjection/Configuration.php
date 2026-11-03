<?php

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

namespace Pimcore\Bundle\CustomReportsBundle\DependencyInjection;

use Pimcore\Bundle\CoreBundle\DependencyInjection\ConfigurationHelper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_custom_reports');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();

        $rootNode
            ->children()
            ->arrayNode('definitions')
            ->normalizeKeys(false)
            ->prototype('array')
            ->children()
            ->scalarNode('id')
            ->setDeprecated('pimcore/pimcore', '11.5', 'The option "%node%" at "%path%" is not used, the key of the definition is the name of the report.')
            ->end()
            ->append($this->createStringNode('name'))
            ->append($this->createStringNode('niceName'))
            ->append($this->createStringNode('sql'))
            ->append($this->createStringNode('group'))
            ->append($this->createStringNode('groupIconClass'))
            ->append($this->createStringNode('iconClass'))
            ->booleanNode('menuShortcut')->end()
            ->append($this->createStringNode('reportClass'))
            ->append($this->createStringNode('chartType'))
            ->append($this->createStringNode('pieColumn'))
            ->append($this->createStringNode('pieLabelColumn'))
            ->append($this->createStringNode('xAxis'))
            ->append($this->createStringListNode('yAxis'))
            ->integerNode('modificationDate')->end()
            ->integerNode('creationDate')->end()
            ->booleanNode('shareGlobally')->end()
            ->append($this->createStringListNode('sharedUserNames'))
            ->append($this->createStringListNode('sharedRoleNames'))
            ->arrayNode('dataSourceConfig')
            ->prototype('variable')
            ->end()
            ->end()
            ->arrayNode('columnConfiguration')
            ->prototype('variable')
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('adapters')
            ->useAttributeAsKey('name')
            ->prototype('scalar')
            ->end()
            ->end()
            ->integerNode('chart_row_limit')
            ->info('Maximum number of rows loaded for a report chart.')
            ->min(1)
            ->defaultValue(10000)
            ->end()
            ->scalarNode('connection')
            ->info('Name of the Doctrine DBAL connection SQL reports run on, ideally a DB user with SELECT grants only. Defaults to the Pimcore connection.')
            ->defaultNull()
            ->end()
            ->end()
            ->end();

        ConfigurationHelper::addConfigLocationWithWriteTargetNodes($rootNode, ['custom_reports' => PIMCORE_CONFIGURATION_DIRECTORY . '/custom_reports']);

        return $treeBuilder;
    }

    private function createStringNode(string $name): ScalarNodeDefinition
    {
        $node = new ScalarNodeDefinition($name);
        $this->configureStringNode($node);

        return $node;
    }

    /**
     * A later config file replaces the list instead of adding to it. Definitions saved earlier can hold null or a single value.
     */
    private function createStringListNode(string $name): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition($name);
        $node
            ->performNoDeepMerging()
            ->beforeNormalization()
            ->ifTrue(static fn (mixed $value): bool => is_scalar($value))
            ->then(static fn (mixed $value): array => $value === '' ? [] : [$value])
            ->end();
        $this->configureStringNode($node->scalarPrototype());

        return $node;
    }

    /**
     * Report setters take strings, while YAML reads an unquoted `2024` as a number. Null means not set.
     */
    private function configureStringNode(ScalarNodeDefinition $node): void
    {
        $node
            ->beforeNormalization()
            ->ifTrue(static fn (mixed $value): bool => is_int($value) || is_float($value))
            ->then(static fn (int|float $value): string => (string) $value)
            ->end()
            ->validate()
            ->ifTrue(static fn (mixed $value): bool => is_bool($value))
            ->thenInvalid('Expected a string, but got %s.')
            ->end();
    }
}
