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

use InvalidArgumentException;
use Pimcore\Bundle\CoreBundle\Editmode\AdminUiClassicEditmodeAssetProvider;
use Pimcore\Bundle\CoreBundle\EventListener\TranslationDebugListener;
use Pimcore\Extension\Document\Areabrick\Attribute\AsAreabrick;
use Pimcore\Document\Editmode\EditmodeAssetProviderInterface;
use Pimcore\Http\Context\PimcoreContextGuesser;
use Pimcore\Loader\ImplementationLoader\ClassMapLoader;
use Pimcore\Loader\ImplementationLoader\PrefixLoader;
use Pimcore\Maintenance\Attribute\AsMaintenanceTask;
use Pimcore\Model\Document\Editable\Loader\EditableLoader;
use Pimcore\Model\Document\Editable\Loader\PrefixLoader as DocumentEditablePrefixLoader;
use Pimcore\Model\Factory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * @internal
 */
final class PimcoreCoreExtension extends ConfigurableExtension
{
    /**
     * The service files in config/, loaded in this order.
     */
    private const array SERVICE_FILES = [
        'services.yaml',
        'services_routing.yaml',
        'services_workflow.yaml',
        'extensions.yaml',
        'request_response.yaml',
        'l10n.yaml',
        'argument_resolvers.yaml',
        'class_resolvers.yaml',
        'implementation_factories.yaml',
        'documents.yaml',
        'event_listeners.yaml',
        'templating.yaml',
        'templating_twig.yaml',
        'profiler.yaml',
        'migrations.yaml',
        'aliases.yaml',
        'image_optimizers.yaml',
        'maintenance.yaml',
        'commands.yaml',
        'cache.yaml',
        'marshaller.yaml',
        'message_handler.yaml',
        'class_builder.yaml',
        'serializer.yaml',
    ];

    public function getAlias(): string
    {
        return 'pimcore';
    }

    protected function loadInternal(array $config, ContainerBuilder $container): void
    {
        // bundle manager/locator config
        $container->setParameter('pimcore.extensions.bundles.search_paths', $config['bundles']['search_paths']);
        $container->setParameter('pimcore.extensions.bundles.handle_composer', $config['bundles']['handle_composer']);

        // a pimcore.encryption.secret parameter defined by the project (e.g. from an env var) takes precedence over
        // pimcore.encryption.secret in the config tree
        if (!$container->hasParameter('pimcore.encryption.secret')) {
            $container->setParameter('pimcore.encryption.secret', $config['encryption']['secret']);
        }

        $container->setParameter('pimcore.translations.admin_translation_mapping', $config['translations']['admin_translation_mapping']);

        $container->setParameter('pimcore.web_profiler.toolbar.excluded_routes', $config['web_profiler']['toolbar']['excluded_routes']);

        $container->setParameter('pimcore.maintenance.housekeeping.cleanup_tmp_files_atime_older_than', $config['maintenance']['housekeeping']['cleanup_tmp_files_atime_older_than']);
        $container->setParameter('pimcore.maintenance.housekeeping.cleanup_profiler_files_atime_older_than', $config['maintenance']['housekeeping']['cleanup_profiler_files_atime_older_than']);
        $container->setParameter('pimcore.maintenance_mode.excluded_ips', $config['maintenance_mode']['excluded_ips']);
        $container->setParameter('pimcore.general.powered_by_header', $config['general']['powered_by_header']);
        $container->setParameter('pimcore.assets.thumbnails.on_demand_generation', $config['assets']['thumbnails']['on_demand_generation']);
        $container->setParameter('pimcore.assets.webdav.enabled', $config['assets']['webdav']['enabled']);

        $container->setParameter('pimcore.documents.default_controller', $config['documents']['default_controller']);

        //twig security policy allowlist config
        $container->setParameter('pimcore.templating.twig.sandbox_security_policy.tags', $config['templating_engine']['twig']['sandbox_security_policy']['tags']);
        $container->setParameter('pimcore.templating.twig.sandbox_security_policy.filters', $config['templating_engine']['twig']['sandbox_security_policy']['filters']);
        $container->setParameter('pimcore.templating.twig.sandbox_security_policy.functions', $config['templating_engine']['twig']['sandbox_security_policy']['functions']);
        $container->setParameter('pimcore.templating.twig.sandbox_security_policy.methods', $config['templating_engine']['twig']['sandbox_security_policy']['methods']);

        // the whole config tree: Config::getSystemConfiguration() returns it (public API), so it stays a parameter.
        // Services and passes should use a dedicated parameter like the ones above. Note that it includes secrets
        // (encryption.secret, httpclient.proxy_pass), which debug:container --parameters shows.
        $container->setParameter('pimcore.config', $config);
        $container->setParameter('pimcore.documents.areas.autoload', $config['documents']['areas']['autoload']);

        // set default domain for router to main domain if configured
        // this will be overridden from the request in web context but is handy for CLI scripts
        $domain = $config['general']['domain'];
        if ($domain) {
            // when not an env variable, check if the domain is valid
            // idn_to_ascii() (ext-intl, a requirement) returns false for an invalid international domain
            $asciiDomain = str_starts_with($domain, 'env_') ? $domain : idn_to_ascii($domain);
            if (
                !str_starts_with($domain, 'env_') &&
                ($asciiDomain === false || !filter_var($asciiDomain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
            ) {
                throw new InvalidArgumentException(sprintf('Invalid main domain name "%s"', $domain));
            }
            $container->setParameter('router.request_context.host', $domain);
        }

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );

        foreach (self::SERVICE_FILES as $file) {
            $loader->load($file);
        }

        $this->configureImplementationLoaders($container, $config);
        $this->configureModelFactory($container, $config);
        $container->setParameter('pimcore.documents.classes.map', $this->flattenConfigurationForClassResolver($config['documents']['type_definitions']));
        $container->setParameter('pimcore.assets.classes.map', $this->flattenConfigurationForClassResolver($config['assets']['type_definitions']));
        $container->setParameter('pimcore.routing.static.locale_params', $config['routing']['static']['locale_params']);
        $this->configureTranslations($container, $config['translations']);
        $this->configurePasswordHashers($container, $config);
        $this->configureEditmodeAssets($container);

        $container->setParameter('pimcore.workflow', $config['workflows']);

        $this->addContextRoutes($container, $config['context']);

        $container->registerAttributeForAutoconfiguration(
            AsAreabrick::class,
            static function (ChildDefinition $definition, AsAreabrick $attribute): void {
                $definition->addTag('pimcore.area.brick', ['id' => $attribute->id]);
            },
        );

        $container->registerAttributeForAutoconfiguration(
            AsMaintenanceTask::class,
            static function (ChildDefinition $definition, AsMaintenanceTask $attribute): void {
                $definition->addTag('pimcore.maintenance.task', array_filter([
                    'type' => $attribute->type,
                    'messengerMessageClass' => $attribute->messengerMessageClass,
                ]));
            },
        );
    }

    /**
     * ImplementationLoader asks its loaders last-added first, so the class overrides added here win over the
     * FallbackBuilder added in implementation_factories.yaml.
     */
    private function configureModelFactory(ContainerBuilder $container, array $config): void
    {
        $service = $container->getDefinition(Factory::class);

        $classMapLoader = new Definition(ClassMapLoader::class, [$config['models']['class_overrides']]);

        $classMapLoaderId = 'pimcore.model.factory.classmap_builder';
        $container->setDefinition($classMapLoaderId, $classMapLoader);

        $service->addMethodCall('addLoader', [new Reference($classMapLoaderId)]);
    }

    /**
     * Configure implementation loaders from config
     */
    private function configureImplementationLoaders(ContainerBuilder $container, array $config): void
    {
        $services = [
            EditableLoader::class => [
                'config' => $config['documents']['editables'],
                'prefixLoader' => DocumentEditablePrefixLoader::class,
            ],
            'pimcore.implementation_loader.object.data' => [
                'config' => $config['objects']['class_definitions']['data'],
                'prefixLoader' => PrefixLoader::class,
            ],
            'pimcore.implementation_loader.object.layout' => [
                'config' => $config['objects']['class_definitions']['layout'],
                'prefixLoader' => PrefixLoader::class,
            ],
            'pimcore.implementation_loader.asset.metadata.data' => [
                'config' => $config['assets']['metadata']['class_definitions']['data'],
                'prefixLoader' => PrefixLoader::class,
            ],
        ];

        // read config and add map/prefix loaders if configured - makes sure only needed objects are built
        // loaders are private services (the default) as we don't need them outside the main type loader.
        // ImplementationLoader asks its loaders last-added first: the class map is added after the prefix loader, so
        // an explicitly mapped type wins over a class found by prefix
        foreach ($services as $serviceId => $cfg) {
            $loaders = [];

            if ($cfg['config']['prefixes']) {
                $prefixLoader = new Definition($cfg['prefixLoader'], [$cfg['config']['prefixes']]);

                $prefixLoaderId = $serviceId . '.prefix_loader';
                $container->setDefinition($prefixLoaderId, $prefixLoader);

                $loaders[] = new Reference($prefixLoaderId);
            }

            if ($cfg['config']['map']) {
                $classMapLoader = new Definition(ClassMapLoader::class, [$cfg['config']['map']]);
                $classMapLoaderId = $serviceId . '.class_map_loader';
                $container->setDefinition($classMapLoaderId, $classMapLoader);
                $loaders[] = new Reference($classMapLoaderId);
            }

            $service = $container->getDefinition($serviceId);
            $service->setArguments([$loaders]);
        }
    }

    private function configureTranslations(ContainerBuilder $container, array $config): void
    {
        $parameter = $config['debugging']['parameter'];

        // remove the listener as it isn't needed at all if it is disabled or the parameter is empty
        if (!$config['debugging']['enabled'] || empty($parameter)) {
            $container->removeDefinition(TranslationDebugListener::class);
        } else {
            $definition = $container->getDefinition(TranslationDebugListener::class);
            $definition->setArgument('$parameterName', $parameter);
        }
    }

    /**
     * The default editmode assets and the routes they use belong to the classic admin UI
     */
    private function configureEditmodeAssets(ContainerBuilder $container): void
    {
        if (!isset($container->getParameter('kernel.bundles')['PimcoreAdminBundle'])) {
            $container->removeAlias(EditmodeAssetProviderInterface::class);
            $container->removeDefinition(AdminUiClassicEditmodeAssetProvider::class);
        }
    }

    /**
     * Handle pimcore.security.password_hasher_factories mapping
     */
    private function configurePasswordHashers(ContainerBuilder $container, array $config): void
    {
        $definition = $container->findDefinition('pimcore.security.password_hasher_factory');
        $definition->setArgument(
            '$passwordHasherFactories',
            array_map(static fn (array $factoryConfig): Reference => new Reference($factoryConfig['id']), $config['security']['password_hasher_factories'])
        );
    }

    /**
     * Add context specific routes to context guesser
     */
    private function addContextRoutes(ContainerBuilder $container, array $config): void
    {
        $guesser = $container->getDefinition(PimcoreContextGuesser::class);

        foreach ($config as $context => $contextConfig) {
            $guesser->addMethodCall('addContextRoutes', [$context, $contextConfig['routes']]);
        }
    }

    /**
     * The type => class map of document or asset type definitions
     *
     * @return array<string, string>
     */
    private function flattenConfigurationForClassResolver(array $configuration): array
    {
        return array_filter(array_map(static fn (array $type): ?string => $type['class'] ?? null, $configuration['map'] ?? []));
    }
}
