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

use Exception;
use Pimcore\Bundle\CoreBundle\Command\Traits\AssetBatchTrait;
use Pimcore\Bundle\CoreBundle\Command\Traits\AssetFilterOptionsTrait;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:assets:generate-checksums',
    description: 'Re-generates checksum for specific or all assets',
)]
class GenerateChecksumCommand extends AbstractCommand
{
    use AssetBatchTrait;
    use AssetFilterOptionsTrait;

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addAssetFilterOptions()
            ->addOption(
                'missing-only',
                'm',
                InputOption::VALUE_NONE,
                'only generate checksum for assets which have no checksum yet'
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $conditionVariables = [];
        $missingOnly = $input->getOption('missing-only');

        $conditions = [];

        $this->addAssetFilterConditions($input, $conditions, $conditionVariables);

        foreach ($this->iterateAssets($conditions, $conditionVariables) as $asset) {
            try {
                if ($missingOnly && $asset->getCustomSetting('checksum')) {
                    continue;
                }

                if ($asset->getType() === 'folder') {
                    continue;
                }

                $this->output->writeln(' generating checksum for asset: ' . $asset->getRealFullPath() . ' | ' . $asset->getId());
                $asset->generateChecksum();
            } catch (Exception) {
                $this->output->writeln(' error generating checksum for asset: ' . $asset->getRealFullPath() . ' | ' . $asset->getId());
            }
        }

        return self::SUCCESS;
    }
}
