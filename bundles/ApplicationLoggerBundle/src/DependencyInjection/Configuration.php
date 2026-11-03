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

namespace Pimcore\Bundle\ApplicationLoggerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Scalar nodes, env placeholders would not pass integer nodes. The services get typed values through their parameters.
 *
 * @internal
 */
class Configuration implements ConfigurationInterface
{
    private const int DEFAULT_ARCHIVE_THRESHOLD = 30;

    private const int DEFAULT_DELETE_ARCHIVE_THRESHOLD = 6;

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_application_logger');

        $treeBuilder->getRootNode()
            ->addDefaultsIfNotSet()
            ->beforeNormalization()
                ->ifTrue(static fn (mixed $v): bool => is_array($v) && array_key_exists('archive_treshold', $v))
                ->then(static function (array $v): array {
                    $v['archive_threshold'] ??= $v['archive_treshold'];
                    unset($v['archive_treshold']);

                    return $v;
                })
            ->end()
            ->children()
                ->arrayNode('mail_notification')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('send_log_summary')
                            ->info('Send log summary via email')
                            ->beforeNormalization()
                                ->ifString()
                                ->then(static fn (string $v): bool|string => filter_var($v, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $v)
                            ->end()
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('filter_priority')
                            ->info('Filter threshold for email summary, choose one of: 7 (debug), 6 (info), 5 (notice), 4 (warning), 3 (error), 2 (critical), 1 (alert), 0 (emerg)')
                            ->beforeNormalization()
                                ->ifTrue(static fn (mixed $v): bool => '' === $v)
                                ->thenUnset()
                            ->end()
                            ->defaultNull()
                        ->end()
                        ->scalarNode('mail_receiver')
                            ->info('Log summary receivers. Separate multiple email receivers by using ;')
                            ->treatNullLike('')
                            ->defaultValue('')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('archive_threshold')
                    ->info('Archive threshold in days')
                    ->beforeNormalization()
                        ->ifTrue(static fn (mixed $v): bool => '' === $v)
                        ->then(static fn (): int => self::DEFAULT_ARCHIVE_THRESHOLD)
                    ->end()
                    ->treatNullLike(self::DEFAULT_ARCHIVE_THRESHOLD)
                    ->defaultValue(self::DEFAULT_ARCHIVE_THRESHOLD)
                ->end()
                ->scalarNode('archive_alternative_database')
                    ->info('Archive database name (optional). Tables will get archived to a different database, recommended when huge amounts of logs will be generated')
                    ->treatNullLike('')
                    ->defaultValue('')
                ->end()
                ->scalarNode('delete_archive_threshold')
                    ->info('Threshold for deleting application log archive tables (in months)')
                    ->beforeNormalization()
                        ->ifTrue(static fn (mixed $v): bool => '' === $v)
                        ->then(static fn (): int => self::DEFAULT_DELETE_ARCHIVE_THRESHOLD)
                    ->end()
                    ->treatNullLike(self::DEFAULT_DELETE_ARCHIVE_THRESHOLD)
                    ->defaultValue(self::DEFAULT_DELETE_ARCHIVE_THRESHOLD)
                ->end()
            ->end();

        return $treeBuilder;
    }
}
