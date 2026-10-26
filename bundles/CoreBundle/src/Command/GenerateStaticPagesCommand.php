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
use InvalidArgumentException;
use Pimcore;
use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Pimcore\Db\Helper;
use Pimcore\Document\StaticPageGenerator;
use Pimcore\Model\Document;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:documents:generate-static-pages',
    description: 'Regenerate static pages'
)]
class GenerateStaticPagesCommand extends AbstractCommand
{
    public function __construct(protected StaticPageGenerator $staticPageGenerator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_REQUIRED,
                'Document path prefix to create the static pages from'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getOption('path');

        $db = Db::get();

        if ($path) {
            $parent = Document::getByPath(rtrim($path, '/'));

            if (!$parent) {
                throw new InvalidArgumentException(sprintf('Document with path %s not found', $path));
            }

            // trailing "/" so that e.g. /en does not match the sibling trees /en-gb and /enterprise
            $ids = $db->fetchFirstColumn('SELECT documents.id FROM `documents_page` INNER JOIN documents ON documents_page.id = documents.id WHERE `staticGeneratorEnabled` = 1 AND (documents.id = :id OR documents.`path` LIKE :path)', [
                'id' => $parent->getId(),
                'path' => Helper::escapeLike(rtrim($parent->getRealFullPath(), '/')) . '/%',
            ]);
        } else {
            $ids = $db->fetchFirstColumn('SELECT id FROM `documents_page` WHERE `staticGeneratorEnabled` = 1');
        }

        $total = count($ids);
        $errors = [];

        if ($total) {
            $progressBar = new ProgressBar($output, $total);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

            foreach ($ids as $id) {
                $page = Document\Page::getById($id);
                if (!$page instanceof Document\Page) {
                    // orphaned documents_page row or the document is no longer a page
                    $errors[] = sprintf('Document with ID %d not found or not a page, skipped', $id);
                } else {
                    try {
                        if ($page->getStaticGeneratorEnabled()) {
                            $progressBar->setMessage(sprintf('Generate for document "%s"', $page->getFullPath()));

                            $this->staticPageGenerator->generate($page, ['is_cli' => true]);
                        } else {
                            $progressBar->setMessage(sprintf('Skipping for document "%s" cause static generation is disabled', $page->getFullPath()));

                            $this->staticPageGenerator->remove($page);
                        }
                    } catch (Exception $e) {
                        $errors[] = $page->getRealFullPath() . ' (ID ' . $page->getId() . '): ' . $e->getMessage();
                    }
                }

                $progressBar->advance();

                if ($progressBar->getProgress() % 10 === 0) {
                    Pimcore::collectGarbage();
                }
            }

            $progressBar->finish();

            $output->writeln('');

            // reported after the progress bar so the messages do not get mixed into it
            foreach ($errors as $error) {
                $this->io->error($error);
            }

            if ($errors) {
                $this->io->error(sprintf('Failed to generate %d static page(s).', count($errors)));

                return self::FAILURE;
            }

            $output->writeln('<info>Finished generating static pages</info>');
        } else {
            $output->writeln('No static generation pages found');
        }

        return self::SUCCESS;
    }
}
