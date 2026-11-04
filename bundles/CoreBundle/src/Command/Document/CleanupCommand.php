<?php

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

namespace Pimcore\Bundle\CoreBundle\Command\Document;

use Doctrine\DBAL\ArrayParameterType;
use Exception;
use Pimcore\Bundle\CoreBundle\Command\Traits\DestructiveCommandTrait;
use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Pimcore\Model\Document;
use Pimcore\Model\Exception\NotFoundException;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:documents:cleanup',
    description: 'Cleans up unused document types. Removes type from enums and tables if exist'
)]
class CleanupCommand extends AbstractCommand
{
    use DestructiveCommandTrait;

    /**
     * The document types of the core, they cannot be removed
     */
    private const array PROTECTED_DOCUMENT_TYPES = ['page', 'link', 'snippet', 'folder', 'hardlink', 'email'];

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument(
                'documentTypes',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Which types do you want to clean up'
            );

        $this->configureDestructiveOptions();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $documentTypes = $input->getArgument('documentTypes');
        $currentEnumTypes = $this->getCurrentEnumTypes();

        $filteredDocumentTypes = [];
        foreach ($documentTypes as $documentType) {
            if (in_array($documentType, self::PROTECTED_DOCUMENT_TYPES)) {
                $this->output->writeln('<comment>Cannot remove protected document type: ' . $documentType . '</comment>');

                continue;
            }
            if (!in_array($documentType, $currentEnumTypes, true)) {
                $this->output->writeln('<comment>Unknown document type: ' . $documentType . '</comment>');

                continue;
            }
            $filteredDocumentTypes[] = $documentType;
        }

        if (empty($filteredDocumentTypes)) {
            return Command::SUCCESS;
        }

        $db = Db::get();
        $documentIds = $db->fetchFirstColumn(
            'SELECT id FROM documents WHERE type IN (:types) ORDER BY LENGTH(`path`) ASC, id ASC',
            ['types' => $filteredDocumentTypes],
            ['types' => ArrayParameterType::STRING]
        );

        if ($this->isDryRun()) {
            $this->output->writeln($this->prefixDryRun(sprintf(
                'Would delete %d document(s) of type(s) %s including their children, remove the type(s) from the documents table and drop the tables %s.',
                count($documentIds),
                implode(', ', $filteredDocumentTypes),
                implode(', ', array_map(static fn (string $type): string => 'documents_' . $type, $filteredDocumentTypes))
            )));

            return Command::SUCCESS;
        }

        if (!$this->confirmDestructiveAction(sprintf(
            'This deletes %d document(s) of type(s) %s including their children, removes the type(s) from the documents table and drops their tables.',
            count($documentIds),
            implode(', ', $filteredDocumentTypes)
        ))) {
            return Command::SUCCESS;
        }

        // delete through the model, so children, properties, dependencies, versions, translations, caches etc. are
        // removed as well (parents first, their children are deleted with them)
        $failed = false;
        foreach ($documentIds as $documentId) {
            try {
                $this->loadDocument((int)$documentId)?->delete();
            } catch (Throwable $e) {
                $output->writeln(sprintf('<error>Could not delete document %d: %s</error>', $documentId, $e->getMessage()));
                $this->writeTrace($e);
                $failed = true;
            }
        }

        $remaining = (int)$db->fetchOne(
            'SELECT COUNT(*) FROM documents WHERE type IN (:types)',
            ['types' => $filteredDocumentTypes],
            ['types' => ArrayParameterType::STRING]
        );

        // with sql_mode = '' removing a type from the ENUM would silently set the type of remaining rows to ''
        if ($failed || $remaining > 0) {
            $output->writeln(sprintf(
                '<error>%d document(s) of the given type(s) could not be deleted, the document types were not removed.</error>',
                $remaining
            ));

            return Command::FAILURE;
        }

        $this->modifyEnumTypes(array_values(array_diff($currentEnumTypes, $filteredDocumentTypes)));

        // drop tables if exist
        foreach ($filteredDocumentTypes as $filteredDocumentType) {
            $tableName = 'documents_' . $filteredDocumentType;

            try {
                $db->executeQuery('DROP TABLE IF EXISTS ' . $db->quoteIdentifier($tableName));
            } catch (Exception $ex) {
                $output->writeln(sprintf('<error>Could not drop table %s: %s</error>', $tableName, $ex->getMessage()));
                $this->writeTrace($ex);
                $failed = true;
            }
        }

        if ($failed) {
            return Command::FAILURE;
        }

        $output->writeln(sprintf('Removed %d document(s) and the document type(s) %s.', count($documentIds), implode(', ', $filteredDocumentTypes)));

        return Command::SUCCESS;
    }

    /**
     * Loads the document with its type specific class, or as plain Document if the class of the type does not exist
     * anymore (e.g. the bundle providing it was removed)
     */
    private function loadDocument(int $id): ?Document
    {
        try {
            return Document::getById($id, ['force' => true]);
        } catch (Throwable) {
            $document = new Document();

            try {
                $document->getDao()->getById($id);
            } catch (NotFoundException) {
                return null;
            }

            return $document;
        }
    }

    /**
     * Prints the stack trace of $e with -vvv
     */
    private function writeTrace(Throwable $e): void
    {
        if ($this->output->isDebug()) {
            $this->output->writeln($e->getTraceAsString());
        }
    }

    /**
     * Reads the values of the ENUM column `documents`.`type`
     *
     * @return string[]
     *
     * @throws RuntimeException if the column definition cannot be read or is no ENUM
     */
    private function getCurrentEnumTypes(): array
    {
        $columnType = Db::get()->fetchOne(
            'SELECT COLUMN_TYPE FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['documents', 'type']
        );

        // COLUMN_TYPE is e.g. enum('page','link'); quotes inside a value are doubled
        if (!is_string($columnType) || !preg_match('/^enum\((.*)\)$/is', $columnType, $match)
            || !preg_match_all("/'((?:[^']|'')*)'/", $match[1], $values)) {
            throw new RuntimeException('Could not read the ENUM values of the column documents.type.');
        }

        return array_map(static fn (string $value): string => str_replace("''", "'", $value), $values[1]);
    }

    /**
     * Redefines the column `documents`.`type` as ENUM of $enums
     *
     * @param string[] $enums
     */
    private function modifyEnumTypes(array $enums): void
    {
        $db = Db::get();

        // quote explicitly: DDL cannot use bound parameters without the emulated prepares of pdo_mysql
        $db->executeStatement(sprintf(
            'ALTER TABLE documents MODIFY COLUMN `type` ENUM(%s)',
            implode(',', array_map($db->quote(...), $enums))
        ));
    }
}
