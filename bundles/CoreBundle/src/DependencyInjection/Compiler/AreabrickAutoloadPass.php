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
use Pimcore\Extension\Document\Areabrick\Attribute\AsAreabrick;
use ReflectionClass;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Finder\Finder;

/**
 * Registers the bricks in the Document\Areabrick sub-namespace of each bundle (and App) as services tagged with
 * `pimcore.area.brick`. AreabrickPass then registers them on the areabrick manager like any other tagged brick.
 *
 * Runs before the autoconfiguration passes (priority 100), so the autoloaded bricks are autoconfigured
 * (`_instanceof`, `#[AsEventListener]`, `#[AutoconfigureTag]`, ...).
 *
 * @internal
 */
final class AreabrickAutoloadPass implements CompilerPassInterface
{
    private readonly Inflector $inflector;

    public function __construct()
    {
        $this->inflector = InflectorFactory::create()->build();
    }

    /**
     * To be autoloaded, an area must fulfill the following conditions:
     *
     *  - implement AreabrickInterface
     *  - be in the sub-namespace Document\Areabrick (can be nested into a deeper namespace)
     *  - the class is not yet defined as an areabrick through manual config
     *
     * Valid examples:
     *
     *  - App\Document\Areabrick\Foo
     *  - MyBundle\Document\Areabrick\Foo\Bar\Baz
     */
    public function process(ContainerBuilder $container): void
    {
        $config = $container->getParameter('pimcore.config');
        if (!$config['documents']['areas']['autoload']) {
            return;
        }

        $bundles = $container->getParameter('kernel.bundles_metadata');
        //Find bricks from /src since AppBundle is removed
        $bundles['App'] = [
            'path' => PIMCORE_PROJECT_ROOT . '/src',
            'namespace' => 'App',
        ];

        $bricks = [];
        foreach ($bundles as $bundleName => $bundleMetadata) {
            $bricks += $this->findBundleBricks($container, $bundleName, $bundleMetadata);
        }

        // do not autoload areas which were already defined as service via config
        $configuredClasses = $this->findConfiguredBrickClasses($container, $bricks);

        foreach ($bricks as $serviceId => $reflector) {
            if (!isset($configuredClasses[$reflector->getName()])) {
                $definition = new Definition($reflector->getName());
                $definition
                    ->setPublic(false)
                    ->setAutowired(true)
                    ->setAutoconfigured(true)
                    // the brick ID is read from #[AsAreabrick] or generated from the class name by AreabrickPass
                    ->addTag('pimcore.area.brick');

                $container->setDefinition($serviceId, $definition);
            }
        }
    }

    /**
     * Brick classes of services that are (or will be, through autoconfiguration) tagged as areabrick: they are defined
     * as areabrick through manual config and won't be autoloaded.
     *
     * @param array<string, ReflectionClass> $bricks
     *
     * @return array<string, true>
     */
    private function findConfiguredBrickClasses(ContainerBuilder $container, array $bricks): array
    {
        $brickReflectors = [];
        foreach ($bricks as $reflector) {
            $brickReflectors[ltrim($reflector->getName(), '\\')] = $reflector;
        }

        $autoconfiguredInstanceof = $container->getAutoconfiguredInstanceof();

        $classes = [];
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->isAbstract()) {
                continue;
            }

            // the class is resolved by ResolveClassPass (priority 100) for services named after their class
            $class = $container->getParameterBag()->resolveValue($definition->getClass() ?? $id);
            if (!is_string($class) || !$reflector = $brickReflectors[ltrim($class, '\\')] ?? null) {
                continue;
            }

            $conditionals = $definition->getInstanceofConditionals();
            if ($definition->isAutoconfigured()) {
                $conditionals += $autoconfiguredInstanceof;
            }

            if ($definition->hasTag('pimcore.area.brick')
                || ($definition->isAutoconfigured() && $reflector->getAttributes(AsAreabrick::class))
                || $this->conditionalsAddBrickTag($reflector, $conditionals)) {
                $classes[$reflector->getName()] = true;
            }
        }

        return $classes;
    }

    /**
     * @param array<string, ChildDefinition> $conditionals
     */
    private function conditionalsAddBrickTag(ReflectionClass $reflector, array $conditionals): bool
    {
        foreach ($conditionals as $interface => $conditional) {
            if ($conditional->hasTag('pimcore.area.brick') && is_a($reflector->getName(), $interface, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Look for classes implementing AreabrickInterface in each bundle's Document\Areabrick sub-namespace
     *
     * @return array<string, ReflectionClass> service ID => brick class
     */
    private function findBundleBricks(ContainerBuilder $container, string $name, array $metadata): array
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

            if (class_exists($className)) {
                $reflector = new ReflectionClass($className);
                if ($reflector->isInstantiable() && $reflector->implementsInterface(AreabrickInterface::class)) {
                    $areas[$this->generateServiceId($name, $subNamespace, $shortClassName)] = $reflector;
                }
            }
        }

        return $areas;
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
