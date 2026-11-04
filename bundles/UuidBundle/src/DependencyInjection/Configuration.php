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

namespace Pimcore\Bundle\UuidBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Length of the `uuids`.`instanceIdentifier` column
     */
    public const int INSTANCE_IDENTIFIER_MAX_LENGTH = 50;

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_uuid');
        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('instance_identifier')
            ->defaultNull()
            ->info('UUID instance identifier. Has to be unique throughout multiple Pimcore instances, at most ' . self::INSTANCE_IDENTIFIER_MAX_LENGTH . ' characters, without "~". UUID generation will be automatically enabled if a Instance identifier is provided (do not change the instance identifier afterwards - this will cause invalid UUIDs)')
                // an explicit null keeps the feature disabled, like an omitted key
                ->beforeNormalization()->ifNull()->thenUnset()->end()
                // YAML parses a numeric identifier as int; its string form gives the same UUIDs as before
                ->beforeNormalization()->ifTrue(static fn (mixed $value): bool => is_int($value))->then(static fn (int $value): string => (string)$value)->end()
                ->cannotBeEmpty()
                ->validate()
                    ->ifTrue(static fn (mixed $value): bool => !is_string($value))
                    ->thenInvalid('The UUID instance identifier must be a string, %s given.')
                ->end()
                ->validate()
                    ->ifTrue(static fn (string $value): bool => self::getInstanceIdentifierError($value) !== null)
                    ->then(static fn (string $value) => throw new InvalidConfigurationException(self::getInstanceIdentifierError($value)))
                ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * Returns why the identifier cannot be used, or null when it is valid. Also called at runtime, because an
     * identifier from an env variable is only known then.
     *
     * @internal
     */
    public static function getInstanceIdentifierError(string $identifier): ?string
    {
        if ($identifier === '') {
            return 'The UUID instance identifier must not be empty.';
        }

        if (mb_strlen($identifier) > self::INSTANCE_IDENTIFIER_MAX_LENGTH) {
            return sprintf('The UUID instance identifier "%s" is longer than %d characters.', $identifier, self::INSTANCE_IDENTIFIER_MAX_LENGTH);
        }

        // "~" separates identifier, type and id in the name the v5 UUID is derived from
        if (preg_match('/[~\x00-\x1F\x7F]/', $identifier) === 1) {
            return sprintf('The UUID instance identifier "%s" must not contain "~" or control characters.', $identifier);
        }

        return null;
    }
}
