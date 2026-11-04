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
use Pimcore\Tool\Admin;
use Pimcore\Tool\MaintenanceModeHelperInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:maintenance-mode',
    description: 'Enable, disable or show the maintenance mode',
    help: <<<'HELP'
        Without an option, <info>%command.name%</info> enables the maintenance mode.
        While it is active, other commands run only with <comment>--ignore-maintenance-mode</comment>.
        HELP
)]
class MaintenanceModeCommand extends AbstractCommand
{
    /**
     * Stands in for the session ID of the admin user who enabled the maintenance mode in the UI.
     * The global --maintenance-mode option of Pimcore\Console\Application uses its own ID for the same purpose.
     */
    public const string SESSION_ID = 'command-line-dummy-session-id';

    public function __construct(private readonly MaintenanceModeHelperInterface $maintenanceModeHelper)
    {
        parent::__construct();
    }

    public function __invoke(
        #[Option('Enable maintenance mode (default)')]
        bool $enable = false,
        #[Option('Disable maintenance mode')]
        bool $disable = false,
        #[Option('Only show whether maintenance mode is active')]
        bool $status = false,
    ): int {
        $selectedModes = array_keys(array_filter([
            '--enable' => $enable,
            '--disable' => $disable,
            '--status' => $status,
        ]));

        if (count($selectedModes) > 1) {
            $this->io->error('The options ' . implode(', ', $selectedModes) . ' cannot be combined.');

            return self::INVALID;
        }

        if ($disable) {
            //BC Layer for Admin::activateMaintenanceMode, if the maintenance file already exists
            if (Admin::isInMaintenanceMode()) {
                Admin::deactivateMaintenanceMode();
            }
            $this->maintenanceModeHelper->deactivate();
        } elseif (!$status) {
            $this->maintenanceModeHelper->activate(self::SESSION_ID);
        }

        if ($this->maintenanceModeHelper->isActive() || Admin::isInMaintenanceMode()) {
            $this->io->writeln('Maintenance mode is <comment>enabled</comment>.');
            $this->io->writeln('Other commands run only with the --ignore-maintenance-mode option.', OutputInterface::VERBOSITY_VERBOSE);
        } else {
            $this->io->writeln('Maintenance mode is <info>disabled</info>.');
        }

        return self::SUCCESS;
    }
}
