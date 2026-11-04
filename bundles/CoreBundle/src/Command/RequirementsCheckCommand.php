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
    private const array MIN_LEVELS = ['warning', 'error'];

    private const array FORMATS = ['txt', 'json'];

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
            ->addOption('strict', null, InputOption::VALUE_NONE, 'Also return a failure exit code if any check reports a warning')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_REQUIRED,
                'Output format: txt or json (e.g. for CI)',
                'txt',
                self::FORMATS
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $minLevel = $input->getOption('min-level');
        $format = $input->getOption('format');

        if (!in_array($format, self::FORMATS, true)) {
            $this->io->error(sprintf('Invalid format "%s", allowed values are: %s', $format, implode(', ', self::FORMATS)));

            return self::INVALID;
        }

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
        $groups = [
            'PHP' => $allChecks['checksPHP'],
            'MySQL' => $allChecks['checksMySQL'],
            'Filesystem' => $allChecks['checksFS'],
            'CLI Tools & Applications' => $allChecks['checksApps'],
        ];

        $states = array_map(static fn (Requirements\Check $check) => $check->getState(), array_merge(...array_values($allChecks)));
        $failure = match (true) {
            in_array(Requirements\Check::STATE_ERROR, $states, true) => 'Some system requirements are not met.',
            $input->getOption('strict') && in_array(Requirements\Check::STATE_WARNING, $states, true) => 'Some system requirements reported warnings (--strict).',
            default => null,
        };

        if ($format === 'json') {
            $this->displayJson($groups, $levelsToDisplay, $failure);
        } else {
            foreach ($groups as $title => $checks) {
                $this->display($checks, $levelsToDisplay, $title);
            }

            if (null !== $failure) {
                $this->io->error($failure);
            }
        }

        return null === $failure ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Writes the checks as one JSON document to stdout, without any other output
     *
     * @param array<string, Requirements\Check[]> $groups
     * @param int[] $levelsToDisplay
     */
    private function displayJson(array $groups, array $levelsToDisplay, ?string $failure): void
    {
        $data = ['success' => null === $failure, 'message' => $failure, 'checks' => []];

        foreach ($groups as $title => $checks) {
            $data['checks'][$title] = [];
            foreach ($checks as $check) {
                if (in_array($check->getState(), $levelsToDisplay, true)) {
                    $data['checks'][$title][] = [
                        'name' => $check->getName(),
                        'state' => $this->getStateName($check->getState()),
                        'message' => $check->getMessage(),
                        'link' => $check->getLink(),
                    ];
                }
            }
        }

        $this->output->writeln(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            OutputInterface::OUTPUT_RAW
        );
    }

    /**
     * @param Requirements\Check[] $checks
     * @param int[] $levelsToDisplay
     */
    private function display(array $checks, array $levelsToDisplay, string $title = ''): void
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

    private function displayState(int $state): string
    {
        return match ($state) {
            Requirements\Check::STATE_OK => '<fg=green>ok</>',
            Requirements\Check::STATE_WARNING => '<fg=yellow>warning</>',
            default => '<fg=red>error</>',
        };
    }

    private function getStateName(int $state): string
    {
        return match ($state) {
            Requirements\Check::STATE_OK => 'ok',
            Requirements\Check::STATE_WARNING => 'warning',
            default => 'error',
        };
    }
}
