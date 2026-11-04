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

namespace Pimcore\Bundle\CoreBundle\Command\Migrate;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Pimcore\Bundle\CoreBundle\Command\Traits\DestructiveCommandTrait;
use Pimcore\Console\AbstractCommand;
use Pimcore\Tool\Storage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:migrate:thumbnails-folder-structure',
    description: 'Moves thumbnails to the folder structure with the asset ID',
    help: <<<'EOT'
        Changes the thumbnail folder structure to

          \<asset storage>/\<path to asset>/\<asset id>/image-thumb__\<asset id>__\<thumbnail name>/\<asset filename>

        instead of

          \<asset storage>/\<path to asset>/image-thumb__\<asset id>__\<thumbnail name>/\<asset filename>
        EOT
)]
class ThumbnailsFolderStructureCommand extends AbstractCommand
{
    use DestructiveCommandTrait;

    private int $movedCount = 0;

    private int $deletedCount = 0;

    #[\Override]
    protected function configure(): void
    {
        $this->configureDestructiveOptions('List the files to move or delete without changing anything');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->confirmDestructiveAction(
            'This moves all thumbnail and asset cache files to the new folder structure and deletes files whose target already exists.'
        )) {
            return self::SUCCESS;
        }

        $thumbnailStorage = Storage::get('thumbnail');

        $output->writeln('Migrating thumbnails ...');
        $this->doMigrateStorage($output, $thumbnailStorage);
        $output->writeln('');

        $assetCacheStorage = Storage::get('asset_cache');

        $output->writeln('Migrating asset cache (document previews, etc.) ...');
        $this->doMigrateStorage($output, $assetCacheStorage);
        $output->writeln('');

        $this->io->success(sprintf(
            $this->isDryRun() ? '%d file(s) would be moved, %d file(s) deleted.' : 'Moved %d file(s), deleted %d file(s) whose target already existed.',
            $this->movedCount,
            $this->deletedCount
        ));

        return self::SUCCESS;
    }

    protected function doMigrateStorage(OutputInterface $output, FilesystemOperator $storage): void
    {
        $thumbnailFiles = $storage->listContents('/', true)->filter(function (StorageAttributes $attributes) {
            if ($attributes->isDir()) {
                return false;
            }

            $matches = [];
            preg_match('/(image-thumb|video-thumb|pdf-thumb)__(\d+)__/', $attributes->path(), $matches);

            return count($matches) > 2 && !str_contains('/' . $attributes->path(), '/' . $matches[2] . '/' . $matches[1] . '__' . $matches[2] . '__');
        });

        $iterator = $thumbnailFiles->toArray();
        $progressBar = $this->isDryRun() ? null : $this->io->createProgressBar(count($iterator));

        $progressBar?->start();

        /** @var StorageAttributes $thumbnailFile */
        foreach ($iterator as $thumbnailFile) {
            $targetPath = preg_replace('/(image-thumb|video-thumb|pdf-thumb)__(\d+)__(.+)$/', '$2/$1__$2__$3', $thumbnailFile->path());

            if (!$storage->fileExists($targetPath)) {
                $this->movedCount++;
                if ($this->isDryRun()) {
                    $output->writeln($this->prefixDryRun(sprintf('move %s to %s', $thumbnailFile->path(), $targetPath)));
                } else {
                    $storage->move($thumbnailFile->path(), $targetPath);
                }
            } else {
                $this->deletedCount++;
                if ($this->isDryRun()) {
                    $output->writeln($this->prefixDryRun(sprintf('delete %s (%s exists)', $thumbnailFile->path(), $targetPath)));
                } elseif ($thumbnailFile->isDir()) {
                    $storage->deleteDirectory($thumbnailFile->path());
                } else {
                    $storage->delete($thumbnailFile->path());
                }
            }

            $progressBar?->advance();
        }
        $progressBar?->finish();
    }
}
