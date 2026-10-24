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
use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Pimcore\Model\Document;
use Pimcore\Model\Exception\NotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'pimcore:documents:cleanup',
    description: 'Cleans up unused document types. Removes type from enums and tables if exist'
)]
class CleanupCommand extends AbstractCommand
{
    protected const array STANDARD_DOCUMENT_ENUM_TYPES = [
        'page',
        'link',
        'snippet',
        'folder',
        'hardlink',
        'email',
    ];

    private const array PROTECTED_DOCUMENT_TYPES = ['page', 'link', 'snippet', 'folder', 'hardlink', 'email'];

    protected function configure(): void
    {
        $this
            ->addArgument('documentTypes',
                InputArgument::IS_ARRAY,
                'Which types do you want to clean up')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Clean up without asking for confirmation');
    }

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

        if (!$input->getOption('force') && $input->isInteractive()) {
            $confirmed = $this->io->confirm(sprintf(
                'This deletes %d document(s) of type(s) %s including their children, removes the type(s) from the documents table and drops their tables. This cannot be undone. Continue?',
                count($documentIds),
                implode(', ', $filteredDocumentTypes)
            ), false);

            if (!$confirmed) {
                return Command::SUCCESS;
            }
        }

        // delete through the model, so children, properties, dependencies, versions, translations, caches etc. are
        // removed as well (parents first, their children are deleted with them)
        $failed = false;
        foreach ($documentIds as $documentId) {
            try {
                $this->loadDocument((int)$documentId)?->delete();
            } catch (Throwable $e) {
                $output->writeln(sprintf('<error>Could not delete document %d: %s</error>', $documentId, $e->getMessage()));
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

    private function getCurrentEnumTypes(): array
    {
        $db = Db::get();

        try {
            $result = $db->executeQuery("SHOW COLUMNS FROM `documents` LIKE 'type'");
            $typeColumn = $result->fetchAllAssociative();

            return explode("','", preg_replace("/(enum)\('(.+?)'\)/", '\\2', $typeColumn[0]['Type']));
        } catch (Exception) {
            // nothing to do here if it does not work we return the standard types
        }

        return self::STANDARD_DOCUMENT_ENUM_TYPES;
    }

    private function modifyEnumTypes(array $enums): void
    {
        $db = Db::get();
        $db->executeQuery(
            'ALTER TABLE documents MODIFY COLUMN `type` ENUM(:enums);',
            ['enums' => $enums],
            ['enums' => ArrayParameterType::STRING]
        );
    }
}
