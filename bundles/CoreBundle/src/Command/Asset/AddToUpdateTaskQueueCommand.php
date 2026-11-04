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

namespace Pimcore\Bundle\CoreBundle\Command\Asset;

use Pimcore\Bundle\CoreBundle\Command\Traits\AssetBatchTrait;
use Pimcore\Bundle\CoreBundle\Command\Traits\AssetFilterOptionsTrait;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:assets:add-to-update-task-queue',
    description: 'Adds assets to update task queue for re-processing (previews, meta-data, ...)',
)]
class AddToUpdateTaskQueueCommand extends AbstractCommand
{
    use AssetBatchTrait;
    use AssetFilterOptionsTrait;

    private const array TYPES = ['image', 'video', 'document'];

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addAssetFilterOptions()
            ->addOption(
                'retry-failed',
                'f',
                InputOption::VALUE_NONE,
                'retry assets that previously failed to be processed'
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conditionVariables = [];

        $conditions = [
            "`type` IN ('" . implode("','", self::TYPES) . "')",
        ];

        $this->addAssetFilterConditions($input, $conditions, $conditionVariables);

        if ($input->getOption('retry-failed')) {
            $conditions[] = sprintf(
                "customSettings LIKE '%%%s%%'",
                '"' . Asset::CUSTOM_SETTING_PROCESSING_FAILED . '":true'
            );
        }

        foreach ($this->iterateAssets($conditions, $conditionVariables) as $asset) {
            $asset->triggerUpdateTask();
        }

        return self::SUCCESS;
    }
}
