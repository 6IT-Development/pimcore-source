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

namespace Pimcore\Bundle\CoreBundle\Command\Document;

use Pimcore;
use Pimcore\Bundle\CoreBundle\Command\Traits\DestructiveCommandTrait;
use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Pimcore\Model\Version;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:documents:migrate-elements',
    description: 'Migrates document elements to editables',
    help: 'Migrates document elements to editables. See issue https://github.com/pimcore/pimcore/issues/7384 first.'
)]
class MigrateElementsCommand extends AbstractCommand
{
    use DestructiveCommandTrait;

    #[\Override]
    protected function configure(): void
    {
        $this->configureDestructiveOptions('List the versions to re-save without changing anything');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->isDryRun()) {
            $this->io->warning('This command is potentially dangerous. Please use with caution and make sure you have a proper backup! '
                . 'See issue https://github.com/pimcore/pimcore/issues/7384 first');
        }

        if (!$this->confirmDestructiveAction('This re-saves all versions of pages and snippets.')) {
            return self::SUCCESS;
        }

        $db = Db::get();
        $versionsRaw = $db->iterateAssociative("SELECT v.id AS vId, d.id as dId, d.key as `dKey` FROM versions v, documents d WHERE ctype = 'document' AND v.cid = d.`id` AND (d.`type`  = 'snippet' OR d.`type` = 'page')");

        $migrated = 0;
        $failed = 0;

        foreach ($versionsRaw as $versionRaw) {
            if ($this->isDryRun()) {
                $output->writeln($this->prefixDryRun(sprintf('re-save version %d, document id: %d, document key: %s', $versionRaw['vId'], $versionRaw['dId'], $versionRaw['dKey'])));
                $migrated++;

                continue;
            }

            try {
                $this->processVersionRow($versionRaw);
                $migrated++;
            } catch (Throwable $e) {
                $failed++;
                $this->io->error(sprintf('version %d, document id: %d: %s', $versionRaw['vId'], $versionRaw['dId'], $e->getMessage()));
            }

            if (($migrated + $failed) % 100 === 0) {
                Pimcore::collectGarbage();
            }
        }

        $this->io->newLine();

        if ($failed) {
            $this->io->error(sprintf('Migrated %d versions, %d failed.', $migrated, $failed));

            return self::FAILURE;
        }

        $this->io->success(sprintf($this->isDryRun() ? '%d version(s) would be migrated.' : 'Migrated %d versions.', $migrated));

        return self::SUCCESS;
    }

    private function processVersionRow(array $row): void
    {
        $vId = (int) $row['vId'];
        $documentId = $row['dId'];
        $dKey = $row['dKey'];
        $this->output->writeln(sprintf('processing version %d, document id: %d, document key: %s', $vId, $documentId, $dKey));
        $version = Version::getById($vId);
        if (!$version) {
            throw new RuntimeException('version not found');
        }
        // loadData() returns null instead of throwing when the stored data is missing or unreadable;
        // saving in that case would overwrite the version with empty data
        if (null === $version->loadData()) {
            throw new RuntimeException('version data cannot be loaded');
        }
        $version->save();
        $this->output->writeln(sprintf('saved version %d, document id: %d, document key: %s', $vId, $documentId, $dKey));
    }
}
