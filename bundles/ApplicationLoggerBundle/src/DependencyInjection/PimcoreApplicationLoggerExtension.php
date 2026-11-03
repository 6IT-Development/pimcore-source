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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class PimcoreApplicationLoggerExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );

        $loader->load('services.yaml');

        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('pimcore_application_logger.send_log_summary', $config['mail_notification']['send_log_summary']);
        $container->setParameter('pimcore_application_logger.filter_priority', $config['mail_notification']['filter_priority']);
        $container->setParameter('pimcore_application_logger.mail_receiver', $config['mail_notification']['mail_receiver']);
        $container->setParameter('pimcore_application_logger.archive_threshold', $config['archive_threshold']);
        $container->setParameter('pimcore_application_logger.archive_alternative_database', $config['archive_alternative_database']);
        $container->setParameter('pimcore_application_logger.delete_archive_threshold', $config['delete_archive_threshold']);
    }

    public function prepend(ContainerBuilder $container): void
    {
        // the former location of the settings, prepended so that the bundle's own config wins
        $legacyConfig = [];
        foreach ($container->getExtensionConfig('pimcore') as $config) {
            $legacyConfig = array_replace_recursive($legacyConfig, $config['applicationlog'] ?? []);
        }

        if ($legacyConfig) {
            $container->prependExtensionConfig('pimcore_application_logger', $legacyConfig);
        }

        if ($container->hasExtension('pimcore_admin')) {
            // without prepend the loader appends the config, which then wins over the application's own key bindings
            $loader = new YamlFileLoader(
                $container,
                new FileLocator(__DIR__ . '/../../config'),
                prepend: true
            );

            $loader->load('admin-classic.yaml');
        }
    }
}
