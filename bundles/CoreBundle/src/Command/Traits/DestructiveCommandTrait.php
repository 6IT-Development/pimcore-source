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

namespace Pimcore\Bundle\CoreBundle\Command\Traits;

use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Traits\DryRun;
use Symfony\Component\Console\Input\InputOption;

/**
 * Common safety rules for commands that delete or move data that can't be restored:
 *
 * - `--dry-run` (`-N`) lists the planned actions and changes nothing
 * - otherwise the command asks for confirmation; `--force` (`-f`) or `--no-interaction` (`-n`) skip the question
 * - the command ends with a summary of what it did (each command prints its own counts)
 *
 * @internal
 *
 * @phpstan-require-extends AbstractCommand
 */
trait DestructiveCommandTrait
{
    use DryRun;

    /**
     * Adds the `--force` and `--dry-run` options
     *
     * @return $this
     */
    protected function configureDestructiveOptions(?string $dryRunDescription = null): static
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Do not ask for confirmation');

        return $this->configureDryRunOption($dryRunDescription ?? 'List the planned changes without changing anything');
    }

    /**
     * Asks whether the destructive action described by $question should run.
     *
     * Returns true without asking in dry-run mode (nothing is changed), with `--force` and in non-interactive mode.
     */
    protected function confirmDestructiveAction(string $question): bool
    {
        if ($this->isDryRun() || $this->input->getOption('force') || !$this->input->isInteractive()) {
            return true;
        }

        return $this->io->confirm($question . ' This cannot be undone. Continue?', false);
    }
}
