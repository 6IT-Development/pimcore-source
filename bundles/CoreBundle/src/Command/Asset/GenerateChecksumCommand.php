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
use Pimcore\Db\Helper;
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
        $missingOnly = (bool) $input->getOption('missing-only');

        $conditions = ["`type` != 'folder'"];

        if ($missingOnly) {
            // customSettings is a PHP-serialized array; skip rows that already have a non-empty "checksum" string
            $key = Helper::escapeLike(serialize('checksum'));
            $conditions[] = 'customSettings IS NULL OR customSettings NOT LIKE ? OR customSettings LIKE ?';
            $conditionVariables[] = '%' . $key . 's:%';
            $conditionVariables[] = '%' . $key . 's:0:""%';
        }

        $this->addAssetFilterConditions($input, $conditions, $conditionVariables);

        $progressBar = $this->io->createProgressBar($this->countAssets($conditions, $conditionVariables));

        $generated = 0;
        $errors = 0;

        foreach ($this->iterateAssets($conditions, $conditionVariables) as $asset) {
            // the SQL filter is only a pre-selection, the custom setting decides
            if (!$missingOnly || !$asset->getCustomSetting('checksum')) {
                try {
                    if ($this->output->isVerbose()) {
                        // clear the bar before writing, otherwise the line is printed over it
                        $progressBar->clear();
                        $this->output->writeln('generating checksum for asset: ' . $asset->getRealFullPath() . ' | ' . $asset->getId());
                        $progressBar->display();
                    }
                    $asset->generateChecksum();
                    $generated++;
                } catch (Exception $e) {
                    $errors++;
                    $progressBar->clear();
                    $this->io->error($asset->getRealFullPath() . ' | ' . $asset->getId() . ': ' . $e->getMessage());
                    $progressBar->display();
                }
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->io->newLine(2);

        if ($errors) {
            $this->io->error(sprintf('Generated %d checksums, %d failed.', $generated, $errors));

            return self::FAILURE;
        }

        $this->io->success(sprintf('Generated %d checksums.', $generated));

        return self::SUCCESS;
    }
}
