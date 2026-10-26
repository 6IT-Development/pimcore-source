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

namespace Pimcore\Bundle\CoreBundle\Command\Document;

use Exception;
use Pimcore;
use Pimcore\Console\AbstractCommand;
use Pimcore\Db\Helper;
use Pimcore\Model\Document;
use Pimcore\Tool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:documents:generate-page-previews',
    description: 'Generates the previews shown in the tree on hover'
)]
class GeneratePagePreviews extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'urlPrefix',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Prefix for the document path, eg. https://example.com, if not specified, Pimcore will try use the main domain from system settings.'
            )
            ->addOption(
                'parent',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Only create preview of documents in parent hierarchy (ID|path).'
            )
            ->addOption(
                'exclude-patterns',
                'x',
                InputOption::VALUE_OPTIONAL,
                'Excludes all documents whose path property matches the regex pattern.'
            );
    }

    /**
     * Iterates the pages matching the options in batches, ordered by ID (keyset pagination, see AssetBatchTrait).
     *
     * @return iterable<Document\Page>
     */
    protected function fetchItems(InputInterface $input, int $batchSize = 10): iterable
    {
        $conditions = ["type = 'page'"];
        $conditionVariables = [];

        $parentIdOrPath = $input->getOption('parent');
        if ($parentIdOrPath) {
            if (is_numeric(($parentIdOrPath))) {
                $parent = Document::getById((int)$parentIdOrPath);
            } else {
                $parent = Document::getByPath($parentIdOrPath);
            }
            if ($parent instanceof Document) {
                // trailing "/" so that e.g. /en does not match the sibling trees /en-gb and /enterprise
                $conditions[] = '(`path` LIKE ? OR id = ?)';
                $conditionVariables[] = Helper::escapeLike(rtrim($parent->getRealFullPath(), '/')) . '/%';
                $conditionVariables[] = $parent->getId();
            } else {
                // no exit(): it would skip the console TERMINATE event (e.g. --maintenance-mode would stay active)
                throw new InvalidArgumentException($parentIdOrPath . ' is not a valid id or path!');
            }
        }

        $regex = $input->getOption('exclude-patterns');
        if ($regex) {
            $conditions[] = '`path` NOT REGEXP ?';
            $conditionVariables[] = $regex;
        }

        $conditions[] = 'id > ?';
        $condition = implode(' AND ', $conditions);
        $lastId = 0;

        do {
            $docs = new Document\Listing();
            $docs->setCondition($condition, [...$conditionVariables, $lastId]);
            $docs->setOrderKey('id');
            $docs->setOrder('ASC');
            $docs->setLimit($batchSize);
            $pages = $docs->load();

            foreach ($pages as $page) {
                $lastId = $page->getId();

                yield $page;
            }

            Pimcore::collectGarbage();
        } while (count($pages) === $batchSize);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $hostUrl = $input->getOption('urlPrefix');
        if (!$hostUrl) {
            $hostUrl = Tool::getHostUrl();
        }

        if (!$hostUrl) {
            $this->io->error('Unable to determine URL prefix, please use option -u or specify a main domain in system settings');

            return 1;
        }

        $failed = 0;
        foreach ($this->fetchItems($input) as $doc) {
            try {
                Document\Service::generatePagePreview($doc->getId(), null, $hostUrl);
            } catch (Exception $e) {
                $failed++;
                $this->io->error($doc->getRealFullPath() . ' (ID ' . $doc->getId() . '): ' . $e->getMessage());
            }
        }

        if ($failed > 0) {
            $this->io->error(sprintf('Failed to generate %d page preview(s).', $failed));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
