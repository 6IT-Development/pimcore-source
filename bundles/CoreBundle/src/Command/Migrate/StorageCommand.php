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

use Exception;
use League\Flysystem\StorageAttributes;
use Pimcore\Console\AbstractCommand;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:migrate:storage',
    description: 'Migrate data from one storage to another'
)]
class StorageCommand extends AbstractCommand
{
    public function __construct(private readonly ContainerInterface $locator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'storage',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'A list of storages to be migrated'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errors = [];
        $storages = $input->getArgument('storage');

        foreach ($storages as $storageName) {
            $storageSourceName = $this->getStorageName($storageName, 'source');
            $storageTargetName = $this->getStorageName($storageName, 'target');

            try {
                $sourceStorage = $this->locator->get($storageSourceName);
                $targetStorage = $this->locator->get($storageTargetName);
            } catch (NotFoundExceptionInterface) {
                $message = sprintf('Skipped migrating storage "%s": please make sure "%s" and "%s" configuration exists.', $storageName, $storageSourceName, $storageTargetName);
                $this->io->warning($message);
                $errors[] = $message;

                continue;
            }

            $this->io->newLine();
            $this->io->info(sprintf('Migrating storage "%s"', $storageName));

            $progressBar = new ProgressBar($output);
            $progressBar->setFormat('%current% [%bar%] %message%');
            $progressBar->start();

            /** @var StorageAttributes $item */
            foreach ($sourceStorage->listContents('/', true) as $item) {
                if ($item->isFile()) {
                    $path = $item->path();

                    $stream = null;

                    try {
                        if (!$targetStorage->fileExists($path)) {
                            $stream = $sourceStorage->readStream($path);
                            $targetStorage->writeStream($path, $stream);

                            $progressBar->setMessage(sprintf('Migrating %s: %s', $storageName, $path));
                        } else {
                            $progressBar->setMessage(sprintf('Skipping %s: %s', $storageName, $path));
                        }
                    } catch (Exception $e) {
                        $progressBar->setMessage(sprintf('Skipping %s: %s', $storageName, $path));
                        $errors[] = sprintf('%s: %s: %s', $storageName, $path, $e->getMessage());
                    } finally {
                        if (is_resource($stream)) {
                            fclose($stream);
                        }
                    }
                    $progressBar->advance();
                }
            }

            $progressBar->finish();
        }

        if ($errors) {
            $this->io->newLine();
            $this->io->error('Some errors occurred while migrating storage:');
            $this->io->writeln(implode("\n", $errors));

            return self::FAILURE;
        }

        $this->io->success('Finished Migrating Storage!');

        return self::SUCCESS;
    }

    public function getStorageName(string $name, string $type): string
    {
        return sprintf('pimcore.%s.storage.%s', $name, $type);
    }
}
