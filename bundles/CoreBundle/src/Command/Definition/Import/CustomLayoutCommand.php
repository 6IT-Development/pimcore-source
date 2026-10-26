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

namespace Pimcore\Bundle\CoreBundle\Command\Definition\Import;

use Exception;
use Pimcore\Logger;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;
use Pimcore\Model\ModelInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class CustomLayoutCommand extends AbstractStructureImportCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->addOption(
            'class-name',
            'c',
            InputOption::VALUE_REQUIRED,
            'Object class name which is used for custom definition'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $className = $input->getOption('class-name');
        if ($className) {
            if (!DataObject\ClassDefinition::getByName($className)) {
                $this->writeError(sprintf('Class "%s" does not exist', $className));

                return self::INVALID;
            }
        } else {
            $name = $this->getDefinitionName(basename($input->getArgument('path')));
            if (null !== $name && null === $this->loadDefinition($name)) {
                $this->writeError('--class-name is required to create a new custom layout');

                return self::INVALID;
            }
        }

        return parent::execute($input, $output);
    }

    /**
     * Get type.
     *
     */
    protected function getType(): string
    {
        return 'Customlayout';
    }

    /**
     * Get definition name from filename (e.g. custom_definition_Customer_export.json -> Customer).
     *
     *
     */
    protected function getDefinitionName(string $filename): ?string
    {
        $parts = [];
        if (preg_match('/^custom_definition_(.*)_export\.json$/', $filename, $parts) === 1) {
            return $parts[1];
        }

        return null;
    }

    /**
     * Try to load definition by name.
     *
     *
     *
     * @throws Exception
     */
    protected function loadDefinition(string $name): ?ModelInterface
    {
        $className = $this->input->getOption('class-name');
        if ($className) {
            $class = DataObject\ClassDefinition::getByName($className);

            return $class ? CustomLayout::getByNameAndClassId($name, $class->getId()) : null;
        }

        return CustomLayout::getByName($name);
    }

    protected function createDefinition(string $name): ?ModelInterface
    {
        $className = $this->input->getOption('class-name');
        if ($className) {
            $class = DataObject\ClassDefinition::getByName($className);
            if ($class) {
                return CustomLayout::create(
                    [
                        'classId' => $class->getId(),
                        'name' => $name,
                    ]
                );
            }
        }

        return null;
    }

    protected function import(ModelInterface $definition, string $json): bool
    {
        if (!$definition instanceof CustomLayout) {
            return false;
        }

        $importData = json_decode($json, true);
        if (!is_array($importData) || !is_array($importData['layoutDefinitions'] ?? null)) {
            $this->writeError('Invalid export file: "layoutDefinitions" is missing');

            return false;
        }

        try {
            $layout = DataObject\ClassDefinition\Service::generateLayoutTreeFromArray($importData['layoutDefinitions'], true);
            if (!$layout instanceof DataObject\ClassDefinition\Layout) {
                $this->writeError('Invalid export file: "layoutDefinitions" does not describe a layout');

                return false;
            }

            $definition->setLayoutDefinitions($layout);
            $definition->setDescription((string) ($importData['description'] ?? ''));
            $definition->setUserModification(0);
            $definition->setUserOwner(0);

            $definition->save();

            return true;
        } catch (Exception $e) {
            Logger::error($e->getMessage());
            $this->writeError($e->getMessage());
        }

        return false;
    }
}
