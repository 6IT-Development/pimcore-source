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

use DateTime;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Tool\Email;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:email:cleanup',
    description: 'Cleanup email logs',
    aliases: ['email:cleanup']
)]
class EmailLogsCleanupCommand extends AbstractCommand
{
    private const BATCH_SIZE = 500;

    protected function configure(): void
    {
        $this
            ->addOption(
                'older-than-days',
                'd',
                InputOption::VALUE_REQUIRED,
                'Older than X Days to delete email logs'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $daysAgo = filter_var(
            $input->getOption('older-than-days'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($daysAgo === false) {
            $this->writeError('The "--older-than-days" option is required and must be a positive integer');

            return self::INVALID;
        }

        $date = new DateTime("-$daysAgo days");
        $dateTimestamp = $date->getTimestamp();

        // delete in batches, keyed by ID, so big tables do not have to be loaded into memory at once
        $lastId = 0;
        do {
            $emailLogs = new Email\Log\Listing();
            $emailLogs->setCondition('sentDate < ? AND id > ?', [$dateTimestamp, $lastId]);
            $emailLogs->setOrderKey('id');
            $emailLogs->setOrder('ASC');
            $emailLogs->setLimit(self::BATCH_SIZE);
            $batch = $emailLogs->load();

            foreach ($batch as $emailLog) {
                // the row may have been deleted since the IDs were fetched
                if (!$emailLog) {
                    continue;
                }

                $lastId = $emailLog->getId();
                $emailLog->delete();
            }
        } while (count($batch) === self::BATCH_SIZE);

        $this->output->writeln('Email logs cleanup done!');

        return self::SUCCESS;
    }
}
