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

use Exception;
use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Pimcore\Logger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:mysql-tools',
    description: 'Optimize and warmup mysql database',
    aliases: ['mysql-tools']
)]
class MysqlToolsCommand extends AbstractCommand
{
    private const array MODES = ['optimize', 'warmup'];

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument(
                'mode',
                // optional only while the deprecated --mode option is supported, execute() requires one of them
                InputArgument::OPTIONAL,
                'optimize or warmup',
                null,
                self::MODES
            )
            ->addOption(
                'mode',
                'm',
                InputOption::VALUE_REQUIRED,
                'Deprecated, use the mode argument instead',
                null,
                self::MODES
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mode = $input->getArgument('mode');

        if (null !== $modeOption = $input->getOption('mode')) {
            trigger_deprecation('pimcore/pimcore', '11.5', 'The "--mode" option of "pimcore:mysql-tools" is deprecated and will be removed in Pimcore 12, pass the mode as argument instead.');
            $this->io->warning(sprintf('The --mode option is deprecated, use "pimcore:mysql-tools %s" instead.', $modeOption));

            if (null !== $mode && $mode !== $modeOption) {
                $this->io->error('The mode argument and the --mode option differ.');

                return self::INVALID;
            }
            $mode ??= $modeOption;
        }

        if (!$mode) {
            $this->io->error(sprintf('Please specify the mode: %s', implode(', ', self::MODES)));

            return self::INVALID;
        }

        if (!in_array($mode, self::MODES, true)) {
            $this->io->error(sprintf('Invalid mode "%s", allowed modes are: %s', $mode, implode(', ', self::MODES)));

            return self::INVALID;
        }

        $db = Db::get();

        // views are skipped: OPTIMIZE fails on them and COUNT(*) on object views is expensive
        $tables = $db->fetchFirstColumn("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");

        $errors = [];

        foreach ($this->io->progressIterate($tables) as $table) {
            $quotedTable = $db->quoteIdentifier($table);

            try {
                if ($mode === 'optimize') {
                    Logger::debug('Running: OPTIMIZE TABLE ' . $quotedTable);
                    // OPTIMIZE TABLE reports problems as result rows instead of throwing
                    foreach ($db->fetchAllAssociative('OPTIMIZE TABLE ' . $quotedTable) as $row) {
                        if (strtolower((string) ($row['Msg_type'] ?? '')) === 'error') {
                            $errors[$table] = (string) $row['Msg_text'];
                        }
                    }
                } else {
                    Logger::debug('Running: SELECT COUNT(*) FROM ' . $quotedTable);
                    $res = $db->fetchOne('SELECT COUNT(*) FROM ' . $quotedTable);
                    Logger::debug('Result: ' . $res);
                }
            } catch (Exception $e) {
                Logger::error((string) $e);
                $errors[$table] = $e->getMessage();
            }
        }

        if ($errors) {
            $this->io->error(sprintf('%d of %d tables failed:', count($errors), count($tables)));
            $this->io->table(['Table', 'Error'], array_map(null, array_keys($errors), array_values($errors)));

            return self::FAILURE;
        }

        $this->io->success(sprintf('Ran %s on %d tables.', $mode, count($tables)));

        return self::SUCCESS;
    }
}
