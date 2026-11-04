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

use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Model\DataObject\Fieldcollection\Definition;
use Pimcore\Model\ModelInterface;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:definition:import:fieldcollection',
    description: 'Import FieldCollection definition from a JSON export',
    aliases: ['definition:import:fieldcollection']
)]
class FieldCollectionCommand extends AbstractStructureImportCommand
{
    protected function getType(): string
    {
        return 'FieldCollection';
    }

    protected function getDefinitionName(string $filename): ?string
    {
        $parts = [];
        if (1 === preg_match('/^fieldcollection_(.*)_export\.json$/', $filename, $parts)) {
            return $parts[1];
        }

        return null;
    }

    protected function loadDefinition(string $name): ?ModelInterface
    {
        return Definition::getByKey($name);
    }

    protected function createDefinition(string $name): Definition
    {
        $definition = new Definition();
        $definition->setKey($name);

        return $definition;
    }

    protected function import(ModelInterface $definition, string $json): bool
    {
        if (!$definition instanceof Definition) {
            return false;
        }

        return Service::importFieldCollectionFromJson($definition, $json);
    }
}
