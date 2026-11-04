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

use League\Flysystem\StorageAttributes;
use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Traits\DryRun;
use Pimcore\Image\ImageOptimizerInterface;
use Pimcore\Image\Optimizer;
use Pimcore\Tool\Storage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:thumbnails:optimize-images',
    description: 'Optimize filesize of all thumbnails',
    aliases: ['thumbnails:optimize-images']
)]
class OptimizeImageThumbnailsCommand extends AbstractCommand
{
    use DryRun;

    public function __construct(
        #[Autowire(service: Optimizer::class)]
        private readonly ImageOptimizerInterface $optimizer
    )
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->configureDryRunOption('Only list the thumbnails that would be optimized');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storage = Storage::get('thumbnail');
        $dryRun = $this->isDryRun();
        $files = 0;
        $failed = 0;
        $savedBytesTotal = 0;

        $items = $storage->listContents('/', true)->filter(static fn (StorageAttributes $item): bool => $item->isFile());

        foreach ($items as $item) {
            $path = $item->path();
            $files++;

            if ($dryRun) {
                $this->output->writeln($this->prefixDryRun('Would optimize image: ' . $path));

                continue;
            }

            // one broken file must not abort the whole run
            try {
                $originalFilesize = $storage->fileSize($path);
                $this->optimizer->optimizeImage($path);
                $savedBytes = $originalFilesize - $storage->fileSize($path);
            } catch (Throwable $e) {
                $failed++;
                $this->io->error('Could not optimize image ' . $path . ': ' . $e->getMessage());

                continue;
            }

            $savedBytesTotal += $savedBytes;
            $this->output->writeln('Optimized image: ' . $path . ' saved ' . formatBytes($savedBytes));
        }

        if ($dryRun) {
            $this->io->success(sprintf('Would optimize %d thumbnail file(s).', $files));

            return self::SUCCESS;
        }

        $summary = sprintf(
            'Optimized %d of %d thumbnail file(s), saved %s in total.',
            $files - $failed,
            $files,
            formatBytes($savedBytesTotal)
        );

        if ($failed) {
            $this->io->error($summary . sprintf(' %d failed.', $failed));

            return self::FAILURE;
        }

        $this->io->success($summary);

        return self::SUCCESS;
    }
}
