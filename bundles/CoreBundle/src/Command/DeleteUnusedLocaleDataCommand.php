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

use Pimcore\Bundle\CoreBundle\Command\Traits\CsvOptionTrait;
use Pimcore\Bundle\CoreBundle\Command\Traits\DestructiveCommandTrait;
use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Pimcore\Tool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:locale:delete-unused-tables',
    description: 'Delete unused locale(invalid language) tables & views'
)]
class DeleteUnusedLocaleDataCommand extends AbstractCommand
{
    use CsvOptionTrait;
    use DestructiveCommandTrait;

    private int $statementCount = 0;

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption(
                'skip-locales',
                's',
                InputOption::VALUE_REQUIRED,
                'Do not delete specified locale tables (comma separated eg.: en, en_AT)'
            );

        $this->configureDestructiveOptions('Just output the delete localized queries to be executed.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = Db::get();
        $skipLocales = $this->getCsvOptionValues($input, 'skip-locales');

        $validLanguages = Tool::getValidLanguages();
        if (!$validLanguages) {
            $this->io->error('No valid languages are configured, aborting to not delete the data of all languages');

            return self::FAILURE;
        }

        if (!$this->confirmDestructiveAction(sprintf(
            'This deletes the localized data, views and query tables of all languages except %s.',
            implode(', ', array_merge($validLanguages, $skipLocales))
        ))) {
            return self::SUCCESS;
        }

        $languageList = [];
        foreach ($validLanguages as $language) {
            $languageList[] = $db->quote($language);
        }

        $tables = $db->fetchAllAssociative("SHOW TABLES LIKE 'object\_localized\_data\_%'");

        foreach ($tables as $table) {
            $printLine = false;
            $table = current($table);
            $classId = str_replace('object_localized_data_', '', $table);

            $result = $db->fetchAllAssociative('SELECT DISTINCT `language` FROM ' . $table . ' WHERE `language` NOT IN(' . implode(',', $languageList) . ')');
            $result = ($result ?: []);

            //delete data from object_localized_data_classID tables
            foreach ($result as $res) {
                $language = $res['language'];
                if (!in_arrayi($language, $skipLocales) && !in_arrayi($language, $validLanguages)) {
                    $sqlDeleteData = 'Delete FROM object_localized_data_' . $classId . ' WHERE `language` = ' . $db->quote($language);
                    $printLine = true;
                    $this->runOrPrint($sqlDeleteData);
                }
            }

            //drop unused localized view e.g. object_localized_classId_*
            $existingViews = $db->fetchAllAssociative("SHOW TABLES LIKE 'object\_localized\_$classId\_%'");
            foreach ($existingViews as $existingView) {
                $localizedView = current($existingView);
                $existingLanguage = str_replace('object_localized_' . $classId . '_', '', $localizedView);

                if (!in_arrayi($existingLanguage, $skipLocales) && !in_arrayi($existingLanguage, $validLanguages)) {
                    $sqlDropView = 'DROP VIEW IF EXISTS object_localized_' . $classId . '_' . $existingLanguage;
                    $printLine = true;

                    $this->runOrPrint($sqlDropView);
                }
            }

            //drop unused localized table e.g. object_localized_query_classId_*
            $existingTables = $db->fetchAllAssociative("SHOW TABLES LIKE 'object\_localized\_query\_$classId\_%'");
            foreach ($existingTables as $existingTable) {
                $localizedTable = current($existingTable);
                $existingLanguage = str_replace('object_localized_query_' . $classId . '_', '', $localizedTable);

                if (!in_arrayi($existingLanguage, $skipLocales) && !in_arrayi($existingLanguage, $validLanguages)) {
                    $sqlDropTable = 'DROP TABLE IF EXISTS object_localized_query_' . $classId . '_' . $existingLanguage;
                    $printLine = true;

                    $this->runOrPrint($sqlDropTable);
                }
            }

            if ($printLine) {
                $output->writeln('------------');
            }
        }

        $this->io->success(sprintf(
            $this->isDryRun() ? '%d statement(s) would be executed.' : 'Executed %d statement(s).',
            $this->statementCount
        ));

        return self::SUCCESS;
    }

    /**
     * Prints the statement and executes it, or in dry-run mode only prints it with the DRY-RUN prefix
     */
    private function runOrPrint(string $sql): void
    {
        $this->output->writeln($this->dryRunMessage($sql));
        $this->statementCount++;

        if (!$this->isDryRun()) {
            Db::get()->executeQuery($sql);
        }
    }
}
