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

namespace Pimcore\Bundle\GlossaryBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Content inside these elements is never linked.
     */
    public const array DEFAULT_BLOCKED_TAGS = [
        'a', 'script', 'style', 'code', 'pre', 'textarea', 'acronym',
        'abbr', 'option', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    ];

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_glossary');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();

        $rootNode
            ->children()
                ->arrayNode('blocked_tags')
                    ->info('Elements whose content is never linked. A configured list replaces the default list; existing links and abbreviations are never nested either way.')
                    ->performNoDeepMerging()
                    ->beforeNormalization()
                        ->ifArray()
                        ->then(static fn (array $tags): array => array_map(
                            static fn (mixed $tag): mixed => is_string($tag) ? strtolower(trim($tag)) : $tag,
                            $tags
                        ))
                    ->end()
                    ->scalarPrototype()->end()
                    ->defaultValue(self::DEFAULT_BLOCKED_TAGS)
                ->end()
            ->end();

        return $treeBuilder;
    }
}
