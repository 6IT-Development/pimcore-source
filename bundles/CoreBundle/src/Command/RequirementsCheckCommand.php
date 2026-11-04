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

use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Pimcore\Tool\Requirements;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:system:requirements:check',
    description: 'Check system requirements',
    aliases: ['system:requirements:check']
)]
class RequirementsCheckCommand extends AbstractCommand
{
    private const MIN_LEVELS = ['warning', 'error'];

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption(
                'min-level',
                'l',
                InputOption::VALUE_REQUIRED,
                "Minimum status level to report: 'warning' or 'error'",
                null,
                self::MIN_LEVELS
            )
            ->addOption('strict', null, InputOption::VALUE_NONE, 'Also return a failure exit code if any check reports a warning');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $minLevel = $input->getOption('min-level');

        $levelsToDisplay = match ($minLevel) {
            null => [Requirements\Check::STATE_OK, Requirements\Check::STATE_WARNING, Requirements\Check::STATE_ERROR],
            'warning', 'warnings' => [Requirements\Check::STATE_WARNING, Requirements\Check::STATE_ERROR],
            'error', 'errors' => [Requirements\Check::STATE_ERROR],
            default => null,
        };

        if ($levelsToDisplay === null) {
            $this->io->error(sprintf('Invalid min-level "%s", allowed values are: %s', $minLevel, implode(', ', self::MIN_LEVELS)));

            return self::INVALID;
        }

        $allChecks = Requirements::checkAll(Db::get());

        $this->display($allChecks['checksPHP'], $levelsToDisplay, 'PHP');
        $this->display($allChecks['checksMySQL'], $levelsToDisplay, 'MySQL');
        $this->display($allChecks['checksFS'], $levelsToDisplay, 'Filesystem');
        $this->display($allChecks['checksApps'], $levelsToDisplay, 'CLI Tools & Applications');

        $states = array_map(static fn (Requirements\Check $check) => $check->getState(), array_merge(...array_values($allChecks)));

        if (in_array(Requirements\Check::STATE_ERROR, $states, true)) {
            $this->io->error('Some system requirements are not met.');

            return self::FAILURE;
        }

        if ($input->getOption('strict') && in_array(Requirements\Check::STATE_WARNING, $states, true)) {
            $this->io->error('Some system requirements reported warnings (--strict).');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param Requirements\Check[] $checks
     * @param int[] $levelsToDisplay
     */
    protected function display(array $checks, array $levelsToDisplay, string $title = ''): void
    {
        $checksTab = [];

        foreach ($checks as $check) {
            if (in_array($check->getState(), $levelsToDisplay, true)) {
                $checksTab[] = [$check->getName(), $this->displayState($check->getState())];
            }
        }

        if (!empty($checksTab)) {
            $this->io->table(["<options=bold>$title</>", ''], $checksTab);
        }
    }

    protected function displayState(int $state): string
    {
        return match ($state) {
            Requirements\Check::STATE_OK => '<fg=green>ok</>',
            Requirements\Check::STATE_WARNING => '<fg=yellow>warning</>',
            default => '<fg=red>error</>',
        };
    }
}
