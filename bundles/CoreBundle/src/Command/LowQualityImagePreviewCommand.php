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
use Pimcore\Bundle\CoreBundle\Command\Traits\AssetBatchTrait;
use Pimcore\Bundle\CoreBundle\Command\Traits\AssetFilterOptionsTrait;
use Pimcore\Bundle\CoreBundle\Command\Traits\DeprecatedOptionNameTrait;
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
    name: self::NAME,
    description: 'Regenerates low-quality image previews for all image assets',
    aliases: [self::DEPRECATED_ALIAS]
)]
class LowQualityImagePreviewCommand extends AbstractCommand
{
    use AssetBatchTrait;
    use AssetFilterOptionsTrait;
    use DeprecatedOptionNameTrait;

    public const string NAME = 'pimcore:image:low-quality-preview';

    public const string DEPRECATED_ALIAS = 'pimcore:image:svg-preview';

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addAssetFilterOptions('images')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'generate preview regardless if it already exists or not'
            )
            ->addOption('generator', 'g', InputOption::VALUE_REQUIRED, 'Deprecated since 11.2 and ignored, will be removed in Pimcore 12');

        $this->addDeprecatedOptionName('pathPattern', 'path-pattern');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (null !== $input->getOption('generator')) {
            trigger_deprecation('pimcore/pimcore', '11.2.0', 'Using the "generator" option is deprecated and will be removed in Pimcore 12.');
            $this->io->warning('The --generator option is deprecated and ignored, it will be removed in Pimcore 12.');
        }

        if ($input->getFirstArgument() === self::DEPRECATED_ALIAS) {
            trigger_deprecation('pimcore/pimcore', '11.5', 'The "%s" command alias is deprecated, use "%s" instead.', self::DEPRECATED_ALIAS, self::NAME);
            $this->io->warning(sprintf('The %s alias is deprecated, use %s instead.', self::DEPRECATED_ALIAS, self::NAME));
        }

        $conditionVariables = [];

        // get only images
        $conditions = ["type = 'image'"];

        $this->addAssetFilterConditions($input, $conditions, $conditionVariables);

        $force = $input->getOption('force');

        $progressBar = $this->io->createProgressBar($this->countAssets($conditions, $conditionVariables));

        $generated = 0;
        $errors = 0;

        /** @var Asset\Image $image */
        foreach ($this->iterateAssets($conditions, $conditionVariables) as $image) {
            if ($force || !$image->getLowQualityPreviewDataUri()) {
                try {
                    if ($this->output->isVerbose()) {
                        // clear the bar before writing, otherwise the line is printed over it
                        $progressBar->clear();
                        $this->output->writeln('generating low-quality preview for image: ' . $image->getRealFullPath() . ' | ' . $image->getId());
                        $progressBar->display();
                    }
                    $image->generateLowQualityPreview();
                    $generated++;
                } catch (Exception $e) {
                    $errors++;
                    $progressBar->clear();
                    $this->io->error($image->getRealFullPath() . ' | ' . $image->getId() . ': ' . $e->getMessage());
                    $progressBar->display();
                }
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->io->newLine(2);

        if ($errors) {
            $this->io->error(sprintf('Generated %d low-quality previews, %d failed.', $generated, $errors));

            return self::FAILURE;
        }

        $this->io->success(sprintf('Generated %d low-quality previews.', $generated));

        return self::SUCCESS;
    }
}
