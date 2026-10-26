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
    private const MODES = ['optimize', 'warmup'];

    protected function configure(): void
    {
        $this
            ->addOption(
                'mode',
                'm',
                InputOption::VALUE_REQUIRED,
                'optimize or warmup',
                null,
                self::MODES
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mode = $input->getOption('mode');

        // display error message
        if (!$mode) {
            $this->writeError('Please specify the mode!');

            return self::INVALID;
        }

        if (!in_array($mode, self::MODES, true)) {
            $this->writeError(sprintf('Invalid mode "%s", allowed modes are: %s', $mode, implode(', ', self::MODES)));

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
