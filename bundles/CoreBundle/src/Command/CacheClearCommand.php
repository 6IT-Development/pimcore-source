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

use Pimcore\Bundle\CoreBundle\Command\Traits\CsvOptionTrait;
use Pimcore\Cache;
use Pimcore\Console\AbstractCommand;
use Pimcore\Event\SystemEvents;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:cache:clear',
    description: 'Clear caches'
)]
class CacheClearCommand extends AbstractCommand
{
    use CsvOptionTrait;

    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct();
    }

    public function __invoke(
        #[Option('Only specific tags (csv list of tags)', shortcut: 't')]
        array $tags = [],
        #[Option('Only output cache', name: 'output', shortcut: 'o')]
        bool $outputOnly = false,
        #[Option('Clear all (default when no other option is given)', shortcut: 'a')]
        bool $all = false,
    ): int {
        $this->io->newLine();

        $selectedModes = array_keys(array_filter([
            '--tags' => [] !== $tags,
            '--output' => $outputOnly,
            '--all' => $all,
        ]));

        if (count($selectedModes) > 1) {
            $this->io->error('The options ' . implode(', ', $selectedModes) . ' cannot be combined.');

            return self::INVALID;
        }

        if ([] !== $tags) {
            // -t foo -t bar,baz results in [foo, bar, baz]
            $tags = self::splitCsvValues($tags);
            if ([] === $tags) {
                $this->io->error('No valid tags given.');

                return self::INVALID;
            }

            Cache::clearTags($tags);
            $this->io->success('Pimcore data cache cleared tags: ' . implode(',', $tags));
        } elseif ($outputOnly) {
            Cache::clearTag('output');
            $this->io->success('Pimcore output cache cleared successfully');
        } else {
            Cache::clearAll();

            $this->eventDispatcher->dispatch(new GenericEvent(), SystemEvents::CACHE_CLEAR);

            $this->io->success('Pimcore data cache cleared successfully');
        }

        return self::SUCCESS;
    }
}
