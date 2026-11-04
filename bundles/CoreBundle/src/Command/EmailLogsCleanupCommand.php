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

use Pimcore\Bundle\CoreBundle\Command\Traits\OlderThanDaysOptionTrait;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Tool\Email;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
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
    use OlderThanDaysOptionTrait;

    private const int BATCH_SIZE = 500;

    #[\Override]
    protected function configure(): void
    {
        $this->addOlderThanDaysOption('email logs');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateTimestamp = $this->getOlderThanDaysTimestamp($input);

        // delete in batches, keyed by ID, so big tables do not have to be loaded into memory at once. Each log is deleted
        // through the model, not with one DELETE statement, because Log::delete() also removes its html/text files
        $lastId = 0;
        $deleted = 0;
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
                $deleted++;
            }
        } while (count($batch) === self::BATCH_SIZE);

        $this->io->success(sprintf('Deleted %d email log(s).', $deleted));

        return self::SUCCESS;
    }
}
