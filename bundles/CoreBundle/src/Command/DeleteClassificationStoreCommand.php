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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Bundle\CoreBundle\Command\Traits\DestructiveCommandTrait;
use Pimcore\Cache;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:classificationstore:delete-store',
    description: 'Delete Classification Store',
    aliases: ['classificationstore:delete-store']
)]
class DeleteClassificationStoreCommand extends AbstractCommand
{
    use DestructiveCommandTrait;

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('storeId', InputArgument::REQUIRED, 'The store ID to delete');

        $this->configureDestructiveOptions();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storeId = filter_var($input->getArgument('storeId'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($storeId === false) {
            $this->io->error('Invalid store ID');

            return self::INVALID;
        }

        $store = StoreConfig::getById($storeId);
        if (!$store) {
            $this->io->error(sprintf('Classification store with ID %d does not exist', $storeId));

            return self::INVALID;
        }

        $affectedClasses = $this->getClassesUsingStore($storeId);
        if ($affectedClasses) {
            $this->io->warning(sprintf(
                'The store is still used by these classes, their classification store fields will be empty: %s',
                implode(', ', $affectedClasses)
            ));
        }

        $counts = [];
        foreach ([
            'keys' => KeyConfig\Dao::TABLE_NAME_KEYS,
            'groups' => GroupConfig\Dao::TABLE_NAME_GROUPS,
            'collections' => CollectionConfig\Dao::TABLE_NAME_COLLECTIONS,
        ] as $type => $table) {
            $counts[] = count($store->getDao()->getConfigIds($table)) . ' ' . $type;
        }

        if ($this->isDryRun()) {
            $this->output->writeln($this->prefixDryRun(sprintf(
                'Would delete classification store "%s" (ID %d) with %s and their object data.',
                $store->getName(),
                $storeId,
                implode(', ', $counts)
            )));

            return self::SUCCESS;
        }

        if (!$this->confirmDestructiveAction(sprintf(
            'This deletes all keys, groups, collections and object data of classification store "%s" (ID %d).',
            $store->getName(),
            $storeId
        ))) {
            return self::SUCCESS;
        }

        // deletes the collections, groups and keys through their models; FK cascades remove the relations and object data
        $store->delete();

        // cached objects of these classes still hold the deleted classification store values
        Cache::clearTags([
            'output',
            ...array_map(static fn (int|string $classId): string => 'class_' . $classId, array_keys($affectedClasses)),
        ]);

        $this->io->success(sprintf('Classification store "%s" (ID %d) deleted with %s.', $store->getName(), $storeId, implode(', ', $counts)));

        return self::SUCCESS;
    }

    /**
     * @return array<int|string, string> class names by class ID
     */
    private function getClassesUsingStore(int $storeId): array
    {
        $classes = [];
        foreach (new ClassDefinition\Listing()->getClasses() as $class) {
            foreach ($class->getFieldDefinitions() as $fieldDefinition) {
                if ($fieldDefinition instanceof ClassDefinition\Data\Classificationstore
                    && $fieldDefinition->getStoreId() === $storeId) {
                    $classes[$class->getId()] = $class->getName();

                    break;
                }
            }
        }

        return $classes;
    }
}
