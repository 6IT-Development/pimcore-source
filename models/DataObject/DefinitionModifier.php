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

namespace Pimcore\Model\DataObject;

use InvalidArgumentException;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Layout;

/**
 * Edits layout definitions of classes, field collections and object bricks.
 *
 * Pass the definition itself (ClassDefinition, Fieldcollection\Definition, Objectbrick\Definition) to have its field
 * list updated after the change. When only a Layout is passed, the owning definition still has the old field list:
 * call setLayoutDefinitions($definition->getLayoutDefinitions()) before saving it, or the save generates columns and
 * getters from the old fields.
 *
 * Layouts, Localizedfields and Block are containers: fields are found inside them and can be inserted into them.
 */
final class DefinitionModifier
{
    /**
     * Containers above the one that the running callback of findField() edits, outermost first
     *
     * @var array<Data\Localizedfields|Data\Block|Layout>
     */
    private array $ancestors = [];

    /**
     * appends valid $fieldsToAdd to a $layoutDefinition element with $nameToFind
     *
     * @param Data|Data[]|Layout|Layout[] $fieldsToAdd
     *
     * @throws InvalidArgumentException when $fieldsToAdd would give the definition duplicate field names or nested
     *                                  Localizedfields
     */
    public function appendFields(ClassDefinitionInterface|Fieldcollection\Definition|Layout $layoutDefinition, string $nameToFind, array|Data|Layout $fieldsToAdd): bool
    {
        $callable = function () use ($fieldsToAdd) {
            return $this->add($fieldsToAdd, true, func_get_args());
        };

        return $this->modify($layoutDefinition, $nameToFind, $callable);
    }

    /**
     * prepends valid $fieldsToAdd to a $layoutDefinition element with $nameToFind
     *
     * @param Data|Data[]|Layout|Layout[] $fieldsToAdd
     *
     * @throws InvalidArgumentException when $fieldsToAdd would give the definition duplicate field names or nested
     *                                  Localizedfields
     */
    public function prependFields(ClassDefinitionInterface|Fieldcollection\Definition|Layout $layoutDefinition, string $nameToFind, array|Data|Layout $fieldsToAdd): bool
    {
        $callable = function () use ($fieldsToAdd) {
            return $this->add($fieldsToAdd, false, func_get_args());
        };

        return $this->modify($layoutDefinition, $nameToFind, $callable);
    }

    /**
     * inserts valid $fieldsToAdd into a $layoutDefinition element of type Layout, Localizedfields or Block that is
     * given by $nameToFind
     *
     * @param Data|Data[]|Layout|Layout[] $fieldsToInsert
     *
     * @throws InvalidArgumentException when $fieldsToInsert would give the definition duplicate field names or nested
     *                                  Localizedfields
     */
    public function insertFieldsFront(ClassDefinitionInterface|Fieldcollection\Definition|Layout $layoutDefinition, string $nameToFind, array|Data|Layout $fieldsToInsert): bool
    {
        $callable = function () use ($fieldsToInsert) {
            return $this->insert($fieldsToInsert, false, func_get_args());
        };

        return $this->modify($layoutDefinition, $nameToFind, $callable);
    }

    /**
     * inserts valid $fieldsToAdd into a $layoutDefinition element of type Layout, Localizedfields or Block that is
     * given by $nameToFind
     *
     * @param Data|Data[]|Layout|Layout[] $fieldsToInsert
     *
     * @throws InvalidArgumentException when $fieldsToInsert would give the definition duplicate field names or nested
     *                                  Localizedfields
     */
    public function insertFieldsBack(ClassDefinitionInterface|Fieldcollection\Definition|Layout $layoutDefinition, string $nameToFind, array|Data|Layout $fieldsToInsert): bool
    {
        $callable = function () use ($fieldsToInsert) {
            return $this->insert($fieldsToInsert, true, func_get_args());
        };

        return $this->modify($layoutDefinition, $nameToFind, $callable);
    }

    /**
     * replaces a $layoutDefinition element, that is specified by $nameToFind, with $field
     *
     * @param Data|Data[]|Layout|Layout[] $fieldReplacements
     *
     * @throws InvalidArgumentException when $fieldReplacements would give the definition duplicate field names or
     *                                  nested Localizedfields
     */
    public function replaceField(ClassDefinitionInterface|Fieldcollection\Definition|Layout $layoutDefinition, string $nameToFind, array|Data|Layout $fieldReplacements): bool
    {
        $callable = function () use ($fieldReplacements) {
            return $this->replace($fieldReplacements, func_get_args());
        };

        return $this->modify($layoutDefinition, $nameToFind, $callable);
    }

    public function removeField(ClassDefinitionInterface|Fieldcollection\Definition|Layout $layoutDefinition, string $nameToFind): bool
    {
        $callable = function () {
            return $this->remove(func_get_args());
        };

        return $this->modify($layoutDefinition, $nameToFind, $callable);
    }

    /**
     * looks for a name in a $layoutDefinition. This may be a Panel or Data Attribute. If there is such a name a
     * callback is executed - passing the parent, its child which was found by name and the child-index it was found at
     * to edit upon. Each level is searched before the containers in it, the first match wins.
     *
     *
     */
    public function findField(Data\Localizedfields|Data\Block|Layout $layoutDefinition, string $nameToFind, callable $callback): bool
    {
        $children = $layoutDefinition->getChildren();

        // try to find field
        foreach ($children as $index => $child) {
            if ($child->getName() === $nameToFind) {
                return $callback($layoutDefinition, $child, $index);
            }
        }

        //if not found, call recursive
        $this->ancestors[] = $layoutDefinition;

        try {
            foreach ($children as $child) {
                if (self::isContainer($child) && $this->findField($child, $nameToFind, $callback)) {
                    return true;
                }
            }
        } finally {
            array_pop($this->ancestors);
        }

        return false;
    }

    /**
     * Runs findField() on the layout of $definition and rebuilds the definition's field list from the changed layout
     */
    private function modify(ClassDefinitionInterface|Fieldcollection\Definition|Layout $definition, string $nameToFind, callable $callback): bool
    {
        if ($definition instanceof Layout) {
            return $this->findField($definition, $nameToFind, $callback);
        }

        $layout = $definition->getLayoutDefinitions();
        if (!$layout || !$this->findField($layout, $nameToFind, $callback)) {
            return false;
        }

        // Layout::setChildren() does not touch the field list of the definition
        $definition->setLayoutDefinitions($layout);

        return true;
    }

    /**
     * appends/prepends $fieldsToAdd to a $layoutDefinition element at a given index
     *
     * @param bool $append if set the element gets appended. Otherwise it will be prepended
     * @param Data|Data[]|Layout|Layout[] $fieldsToAdd
     */
    private function add(array|Data|Layout $fieldsToAdd, bool $append, array $args): bool
    {
        $fieldsToAdd = is_array($fieldsToAdd) ? $fieldsToAdd : [$fieldsToAdd];
        $layoutDefinition = $args[0];
        $this->validate($fieldsToAdd, $layoutDefinition, $this->ancestors);
        $children = $layoutDefinition->getChildren();
        $insertIndex = $append ? $args[2] + 1 : $args[2];
        array_splice($children, $insertIndex, 0, $fieldsToAdd);
        $layoutDefinition->setChildren($children);

        return true;
    }

    /**
     * inserts valid $fieldsToAdd into a $layoutDefinition element at a given index
     *
     * @param bool $append if set the element gets appended. Otherwise it will be prepended
     * @param Data|Data[]|Layout|Layout[] $fieldsToInsert
     */
    private function insert(array|Data|Layout $fieldsToInsert, bool $append, array $args): bool
    {
        $fieldsToInsert = is_array($fieldsToInsert) ? $fieldsToInsert : [$fieldsToInsert];
        $child = $args[1];
        if (!self::isContainer($child)) {
            return false;
        }

        $this->validate($fieldsToInsert, $child, [...$this->ancestors, $args[0]]);
        $nodeChildren = $child->getChildren();
        $insertIndex = $append ? count($nodeChildren) : 0;
        array_splice($nodeChildren, $insertIndex, 0, $fieldsToInsert);
        $child->setChildren($nodeChildren);

        return true;
    }

    /**
     * replaces a $layoutDefinition element at a given index with valid $fieldsToAdd
     *
     * @param Data|Data[]|Layout|Layout[] $fieldReplacements
     */
    private function replace(array|Data|Layout $fieldReplacements, array $args): bool
    {
        $fieldReplacements = is_array($fieldReplacements) ? $fieldReplacements : [$fieldReplacements];
        $layoutDefinition = $args[0];
        $this->validate($fieldReplacements, $layoutDefinition, $this->ancestors, $args[1]);
        $children = $layoutDefinition->getChildren();
        array_splice($children, $args[2], 1, $fieldReplacements);
        $layoutDefinition->setChildren($children);

        return true;
    }

    /**
     * removes a given field
     */
    private function remove(array $args): bool
    {
        $layoutDefinition = $args[0];
        $children = $layoutDefinition->getChildren();
        array_splice($children, $args[2], 1);
        $layoutDefinition->setChildren($children);

        return true;
    }

    private static function isContainer(mixed $element): bool
    {
        return $element instanceof Layout || $element instanceof Data\Localizedfields || $element instanceof Data\Block;
    }

    /**
     * Checks that $newFields are layout or data elements, that their data field names are unique where they end up and
     * that no Localizedfields ends up inside another one. The fields inside Localizedfields share the names of the
     * definition (the Localizedfields container itself may occur several times), a Block has names of its own.
     *
     * @param array<Data|Layout> $newFields
     * @param array<Data\Localizedfields|Data\Block|Layout> $ancestors the containers above $container, outermost first
     * @param Data|Layout|null $replaced the element that $newFields replace, its names are free again
     *
     * @throws InvalidArgumentException
     */
    private function validate(array $newFields, Data\Localizedfields|Data\Block|Layout $container, array $ancestors, Data|Layout|null $replaced = null): void
    {
        foreach ($newFields as $field) {
            if (!$field instanceof Data && !$field instanceof Layout) {
                throw new InvalidArgumentException(sprintf('Only %s and %s elements can be added, got %s', Data::class, Layout::class, get_debug_type($field)));
            }
        }

        $insideLocalizedfields = false;
        $scope = $ancestors[0] ?? $container;
        foreach ([...$ancestors, $container] as $element) {
            $insideLocalizedfields = $insideLocalizedfields || $element instanceof Data\Localizedfields;
            if ($element instanceof Data\Block) {
                $scope = $element;
            }
        }

        $newNames = [];
        foreach ($newFields as $field) {
            $this->collectDataNames($field, $newNames, null, $insideLocalizedfields);
        }

        $existingNames = [];
        foreach ($scope->getChildren() as $child) {
            $this->collectDataNames($child, $existingNames, $replaced);
        }

        foreach ($newNames as $name => $count) {
            if ($count > 1 || isset($existingNames[$name])) {
                throw new InvalidArgumentException(sprintf('The definition already has a field named "%s"', $name));
            }
        }
    }

    /**
     * Counts the data field names in $element; the fields inside a Block are not counted, they have names of their own
     *
     * @param array<string, int> $names data field name => number of occurrences
     * @param bool|null $insideLocalizedfields when given, a Localizedfields inside another one throws
     *
     * @throws InvalidArgumentException
     */
    private function collectDataNames(Data|Layout $element, array &$names, Data|Layout|null $skip = null, ?bool $insideLocalizedfields = null): void
    {
        if ($element === $skip) {
            return;
        }

        if ($element instanceof Data\Localizedfields) {
            if ($insideLocalizedfields) {
                throw new InvalidArgumentException('Localizedfields cannot be nested in Localizedfields');
            }
            $insideLocalizedfields = $insideLocalizedfields === null ? null : true;
        } elseif ($element instanceof Data) {
            $names[$element->getName()] = ($names[$element->getName()] ?? 0) + 1;
        }

        if ($element instanceof Layout || $element instanceof Data\Localizedfields) {
            foreach ($element->getChildren() as $child) {
                $this->collectDataNames($child, $names, $skip, $insideLocalizedfields);
            }
        }
    }
}
