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
use Pimcore\Bundle\CoreBundle\Command\Traits\OlderThanDaysOptionTrait;
use Pimcore\Console\AbstractCommand;
use Pimcore\Logger;
use Pimcore\Model\Element\Recyclebin;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:recyclebin:cleanup',
    description: 'Cleanup recyclebin entries'
)]
class RecyclebinCleanupCommand extends AbstractCommand
{
    use OlderThanDaysOptionTrait;

    private const int BATCH_SIZE = 500;

    #[\Override]
    protected function configure(): void
    {
        $this->addOlderThanDaysOption('recycle bin entries');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateTimestamp = $this->getOlderThanDaysTimestamp($input);

        // delete in batches, keyed by ID, so big tables do not have to be loaded into memory at once
        // and items that failed to delete are not fetched again
        $lastId = 0;
        $deleted = 0;
        $failed = 0;
        do {
            $recyclebinItems = new Recyclebin\Item\Listing();
            $recyclebinItems->setCondition('date < ? AND id > ?', [$dateTimestamp, $lastId]);
            $recyclebinItems->setOrderKey('id');
            $recyclebinItems->setOrder('ASC');
            $recyclebinItems->setLimit(self::BATCH_SIZE);
            $batch = $recyclebinItems->load();

            foreach ($batch as $recyclebinItem) {
                // the row may have been deleted since the IDs were fetched
                if (!$recyclebinItem) {
                    continue;
                }

                $lastId = $recyclebinItem->getId();

                try {
                    $recyclebinItem->delete();
                    $deleted++;
                } catch (Exception $e) {
                    $failed++;
                    $msg = "Could not delete {$recyclebinItem->getPath()} ({$recyclebinItem->getId()}) because of: {$e->getMessage()}";
                    Logger::error($msg);
                    $this->io->error($msg);
                }
            }
        } while (count($batch) === self::BATCH_SIZE);

        if ($failed) {
            $this->io->error(sprintf('Deleted %d recycle bin entries, %d failed.', $deleted, $failed));

            return self::FAILURE;
        }

        $this->io->success(sprintf('Deleted %d recycle bin entries.', $deleted));

        return self::SUCCESS;
    }
}
