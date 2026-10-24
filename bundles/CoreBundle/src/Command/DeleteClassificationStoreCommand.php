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

use Doctrine\DBAL\Connection;
use Pimcore\Cache;
use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
    protected function configure(): void
    {
        $this
            ->addArgument('storeId', InputArgument::REQUIRED, 'The store ID to delete')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Delete without asking for confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storeId = filter_var($input->getArgument('storeId'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($storeId === false) {
            $this->writeError('Invalid store ID');

            return self::INVALID;
        }

        $store = StoreConfig::getById($storeId);
        if (!$store) {
            $this->writeError(sprintf('Classification store with ID %d does not exist', $storeId));

            return self::INVALID;
        }

        $affectedClasses = $this->getClassesUsingStore($storeId);
        if ($affectedClasses) {
            $this->io->warning(sprintf(
                'The store is still used by these classes, their classification store fields will be empty: %s',
                implode(', ', $affectedClasses)
            ));
        }

        if (!$input->getOption('force') && $input->isInteractive()) {
            $confirmed = $this->io->confirm(sprintf(
                'This deletes all keys, groups, collections and object data of classification store "%s" (ID %d). This cannot be undone. Continue?',
                $store->getName(),
                $storeId
            ), false);

            if (!$confirmed) {
                return self::SUCCESS;
            }
        }

        $db = Db::get();
        $db->transactional(function (Connection $db) use ($storeId, $output): void {
            foreach ($db->fetchFirstColumn("show tables like 'object_classificationstore_data_%'") as $table) {
                $this->executeDelete($db, $output, 'delete from ' . $db->quoteIdentifier($table) . ' where keyId In (select id from classificationstore_keys where storeId = ?)', $storeId);
            }

            foreach ($db->fetchFirstColumn("show tables like 'object_classificationstore_groups_%'") as $table) {
                $this->executeDelete($db, $output, 'delete from ' . $db->quoteIdentifier($table) . ' where groupId In (select id from classificationstore_groups where storeId = ?)', $storeId);
            }

            $this->executeDelete($db, $output, 'delete from classificationstore_keys where storeId = ?', $storeId);
            $this->executeDelete($db, $output, 'delete from classificationstore_groups where storeId = ?', $storeId);
            $this->executeDelete($db, $output, 'delete from classificationstore_collections where storeId = ?', $storeId);
            $this->executeDelete($db, $output, 'delete from classificationstore_stores where id = ?', $storeId);
        });

        Cache::clearAll();

        $output->writeln(sprintf('Classification store "%s" (ID %d) deleted.', $store->getName(), $storeId));

        return self::SUCCESS;
    }

    private function executeDelete(Connection $db, OutputInterface $output, string $sql, int $storeId): void
    {
        if ($output->isVerbose()) {
            $output->writeln($sql . ' [' . $storeId . ']');
        }

        $db->executeStatement($sql, [$storeId]);
    }

    /**
     * @return string[]
     */
    private function getClassesUsingStore(int $storeId): array
    {
        $classes = [];
        foreach (new ClassDefinition\Listing()->getClasses() as $class) {
            foreach ($class->getFieldDefinitions() as $fieldDefinition) {
                if ($fieldDefinition instanceof ClassDefinition\Data\Classificationstore
                    && $fieldDefinition->getStoreId() === $storeId) {
                    $classes[] = $class->getName();

                    break;
                }
            }
        }

        return $classes;
    }
}
