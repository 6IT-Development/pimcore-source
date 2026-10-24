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

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Pimcore\Extension\Document\Areabrick\AreabrickInterface;
use Pimcore\Extension\Document\Areabrick\AreabrickManager;
use Pimcore\Extension\Document\Areabrick\Attribute\AsAreabrick;
use Pimcore\Templating\Renderer\EditableRenderer;
use ReflectionClass;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
final class AreabrickPass implements CompilerPassInterface
{
    private readonly Inflector $inflector;

    public function __construct()
    {
        $this->inflector = InflectorFactory::create()->build();
    }

    public function process(ContainerBuilder $container): void
    {
        $config = $container->getParameter('pimcore.config');

        $areabrickManager = $container->getDefinition(AreabrickManager::class);
        $areabrickLocator = $container->getDefinition('pimcore.document.areabrick.brick_locator');

        $taggedServices = $container->findTaggedServiceIds('pimcore.area.brick');

        // keep a list of areas loaded via tags - those classes won't be autoloaded
        $taggedAreas = [];

        // the service mapping for the service locator
        $locatorMapping = [];

        foreach ($taggedServices as $id => $tags) {
            $definition = $container->getDefinition($id);
            $class = $definition->getClass();
            $reflector = new ReflectionClass($class);
            $taggedAreas[] = $class;

            foreach ($this->resolveBrickIds($id, $tags, $reflector) as $brickId) {
                $this->assertBrickIdIsUnique($brickId, $id, $locatorMapping);

                // add the service to the locator
                $locatorMapping[$brickId] = new Reference($id);

                // register the brick with its ID on the areabrick manager
                $areabrickManager->addMethodCall('registerService', [$brickId, $id]);
            }

            // handle bricks implementing ContainerAwareInterface
            $this->handleEditableRendererCall($definition, $reflector);
        }

        // autoload areas if not yet defined via service config
        if ($config['documents']['areas']['autoload']) {
            $locatorMapping = $this->autoloadAreabricks($container, $areabrickManager, $locatorMapping, $taggedAreas);
        }

        $areabrickLocator->setArgument(0, $locatorMapping);
    }

    /**
     * To be autoloaded, an area must fulfill the following conditions:
     *
     *  - implement AreabrickInterface
     *  - be in the sub-namespace Document\Areabrick (can be nested into a deeper namespace)
     *  - the class is not yet defined as an areabrick through manual config (not included in the tagged results above)
     *
     * Valid examples:
     *
     *  - App\Document\Areabrick\Foo
     *  - MyBundle\Document\Areabrick\Foo\Bar\Baz
     */
    private function autoloadAreabricks(
        ContainerBuilder $container,
        Definition $areaManagerDefinition,
        array $locatorMapping,
        array $excludedClasses
    ): array
    {
        $bundles = $container->getParameter('kernel.bundles_metadata');
        //Find bricks from /src since AppBundle is removed
        $bundles['App'] = [
            'path' => PIMCORE_PROJECT_ROOT . '/src',
            'namespace' => 'App',
        ];

        foreach ($bundles as $bundleName => $bundleMetadata) {
            $bundleAreas = $this->findBundleBricks($container, $bundleName, $bundleMetadata, $excludedClasses);

            foreach ($bundleAreas as $bundleArea) {
                /** @var ReflectionClass $reflector */
                $reflector = $bundleArea['reflector'];

                $this->assertBrickIdIsUnique($bundleArea['brickId'], $bundleArea['serviceId'], $locatorMapping);

                $definition = new Definition($reflector->getName());
                $definition
                    ->setPublic(false)
                    ->setAutowired(true)
                    ->setAutoconfigured(true);

                // add brick definition to container
                $container->setDefinition($bundleArea['serviceId'], $definition);

                // add the service to the locator
                $locatorMapping[$bundleArea['brickId']] = new Reference($bundleArea['serviceId']);

                // register brick on the areabrick manager
                $areaManagerDefinition->addMethodCall('registerService', [
                    $bundleArea['brickId'],
                    $bundleArea['serviceId'],
                ]);

                // handle bricks implementing ContainerAwareInterface
                $this->handleEditableRendererCall($definition, $reflector);
            }
        }

        return $locatorMapping;
    }

    /**
     * A service can have several pimcore.area.brick tags, e.g. one from the service config and one added by
     * autoconfiguration for the #[AsAreabrick] attribute. Each brick ID is only registered once.
     *
     * @return string[]
     */
    private function resolveBrickIds(string $serviceId, array $tags, ReflectionClass $reflector): array
    {
        $brickIds = [];
        foreach ($tags as $tag) {
            // tags may define the id which will be used to register the brick
            // e.g. { name: pimcore.area.brick, id: blockquote }
            // if they don't, it will be read from the #[AsAreabrick] attribute or auto-generated from the class name
            $brickIds[] = $tag['id'] ?? $this->generateBrickId($reflector);
        }

        $brickIds = array_values(array_unique($brickIds));

        if (count($brickIds) > 1 && $reflector->getAttributes(AsAreabrick::class)) {
            throw new InvalidConfigurationException(sprintf(
                'Areabrick service "%s" (%s) has the #[AsAreabrick] attribute and a pimcore.area.brick tag with different IDs (%s). Define the ID in one place only, e.g. remove the tag from the service config.',
                $serviceId,
                $reflector->getName(),
                implode(', ', $brickIds)
            ));
        }

        return $brickIds;
    }

    /**
     * @param array<string, Reference> $locatorMapping brick ID => service, of the bricks registered so far
     */
    private function assertBrickIdIsUnique(string $brickId, string $serviceId, array $locatorMapping): void
    {
        if (isset($locatorMapping[$brickId])) {
            throw new InvalidConfigurationException(sprintf(
                'Areabrick ID "%s" of service "%s" is already used by service "%s".',
                $brickId,
                $serviceId,
                (string) $locatorMapping[$brickId]
            ));
        }
    }

    private function handleEditableRendererCall(Definition $definition, ReflectionClass $reflector): void
    {
        if ($reflector->hasMethod('setEditableRenderer')) {
            $definition->addMethodCall('setEditableRenderer', [new Reference(EditableRenderer::class)]);
        }
    }

    /**
     * Look for classes implementing AreabrickInterface in each bundle's Document\Areabrick sub-namespace
     */
    private function findBundleBricks(ContainerBuilder $container, string $name, array $metadata, array $excludedClasses = []): array
    {
        $sourcePath = is_dir($metadata['path'] . '/src') ? $metadata['path'] . '/src' : $metadata['path'];
        $directory = $sourcePath . DIRECTORY_SEPARATOR . 'Document' . DIRECTORY_SEPARATOR . 'Areabrick';

        // update cache when directory is added/removed
        $container->addResource(new FileExistenceResource($directory));

        if (!is_dir($directory)) {
            return [];
        }

        // update container cache when areabricks are added/changed
        $container->addResource(new DirectoryResource($directory, '/\.php$/'));

        $finder = new Finder();
        $finder
            ->files()
            ->in($directory)
            ->name('*.php');

        $areas = [];
        foreach ($finder as $classPath) {
            $shortClassName = $classPath->getBasename('.php');

            // relative path in bundle path
            $relativePath = str_replace($sourcePath, '', (string)$classPath->getPathInfo());
            $relativePath = trim($relativePath, DIRECTORY_SEPARATOR);

            // namespace starting from bundle path
            $relativeNamespace = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

            // sub-namespace in Document\Areabrick
            $subNamespace = str_replace('Document\\Areabrick', '', $relativeNamespace);
            $subNamespace = trim($subNamespace, '\\');

            // fully qualified class name
            $className = $metadata['namespace'] . '\\' . $relativeNamespace . '\\' . $shortClassName;

            // do not autoload areas which were already defined as service via config
            if (in_array($className, $excludedClasses)) {
                continue;
            }

            if (class_exists($className)) {
                $reflector = new ReflectionClass($className);
                if ($reflector->isInstantiable() && $reflector->implementsInterface(AreabrickInterface::class)) {
                    $brickId = $this->generateBrickId($reflector);
                    $serviceId = $this->generateServiceId($name, $subNamespace, $shortClassName);

                    $areas[] = [
                        'brickId' => $brickId,
                        'serviceId' => $serviceId,
                        'reflector' => $reflector,
                    ];
                }
            }
        }

        return $areas;
    }

    /**
     * Tries to read the ID from the `AsAreabrick` attribute and falls back to auto-generation if not defined:
     * GalleryTeaserRow -> gallery-teaser-row
     */
    private function generateBrickId(ReflectionClass $reflector): string
    {
        $attribute = $reflector->getAttributes(AsAreabrick::class)[0] ?? null;

        return $attribute?->newInstance()->id
            ?? str_replace('_', '-', $this->inflector->tableize($reflector->getShortName()));
    }

    /**
     * Generate service ID from bundle name and sub-namespace
     *
     *  - MyBundle\Document\Areabrick\Foo         -> my.area.brick.foo
     *  - MyBundle\Document\Areabrick\Foo\Bar\Baz -> my.area.brick.foo.bar.baz
     */
    private function generateServiceId(string $bundleName, string $subNamespace, string $className): string
    {
        $bundleName = str_replace('Bundle', '', $bundleName);
        $bundleName = $this->inflector->tableize($bundleName);

        if (!empty($subNamespace)) {
            $subNamespaceParts = [];
            foreach (explode('\\', $subNamespace) as $subNamespacePart) {
                $subNamespaceParts[] = $this->inflector->tableize($subNamespacePart);
            }

            $subNamespace = implode('.', $subNamespaceParts) . '.';
        } else {
            $subNamespace = '';
        }

        $brickName = $this->inflector->tableize($className);

        return sprintf('%s.area.brick.%s%s', $bundleName, $subNamespace, $brickName);
    }
}
