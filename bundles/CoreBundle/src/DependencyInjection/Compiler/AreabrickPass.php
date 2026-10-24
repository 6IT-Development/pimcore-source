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
use Pimcore\Extension\Document\Areabrick\AreabrickManager;
use Pimcore\Extension\Document\Areabrick\Attribute\AsAreabrick;
use Pimcore\Templating\Renderer\EditableRenderer;
use ReflectionClass;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

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

    /**
     * Registers the services tagged with `pimcore.area.brick` (including the autoloaded bricks, see
     * AreabrickAutoloadPass) on the areabrick manager.
     */
    public function process(ContainerBuilder $container): void
    {
        $areabrickManager = $container->getDefinition(AreabrickManager::class);
        $areabrickLocator = $container->getDefinition('pimcore.document.areabrick.brick_locator');

        $taggedServices = $container->findTaggedServiceIds('pimcore.area.brick');

        // the service mapping for the service locator
        $locatorMapping = [];

        foreach ($taggedServices as $id => $tags) {
            $definition = $container->getDefinition($id);
            $reflector = $this->getBrickReflectionClass($container, $id, $definition);

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

        $areabrickLocator->setArgument(0, $locatorMapping);
    }

    private function getBrickReflectionClass(ContainerBuilder $container, string $serviceId, Definition $definition): ReflectionClass
    {
        // child definitions are resolved after this pass, take the class of the parent
        $class = $definition->getClass();
        while ($class === null && $definition instanceof ChildDefinition && $container->hasDefinition($definition->getParent())) {
            $definition = $container->getDefinition($definition->getParent());
            $class = $definition->getClass();
        }

        $class = $container->getParameterBag()->resolveValue($class);
        if (!is_string($class) || $class === '') {
            throw new InvalidConfigurationException(sprintf(
                'Areabrick service "%s" has no class. Set the class of the service, the brick ID is generated from it.',
                $serviceId
            ));
        }

        $reflector = $container->getReflectionClass($class, false);
        if (!$reflector) {
            throw new InvalidConfigurationException(sprintf(
                'Class "%s" of areabrick service "%s" does not exist.',
                $class,
                $serviceId
            ));
        }

        return $reflector;
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
     * Tries to read the ID from the `AsAreabrick` attribute and falls back to auto-generation if not defined:
     * GalleryTeaserRow -> gallery-teaser-row
     */
    private function generateBrickId(ReflectionClass $reflector): string
    {
        $attribute = $reflector->getAttributes(AsAreabrick::class)[0] ?? null;

        return $attribute?->newInstance()->id
            ?? str_replace('_', '-', $this->inflector->tableize($reflector->getShortName()));
    }
}
