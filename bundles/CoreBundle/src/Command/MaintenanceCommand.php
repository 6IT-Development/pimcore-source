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

use Pimcore\Bundle\CoreBundle\Command\Traits\DeprecatedOptionNameTrait;
use Pimcore\Console\AbstractCommand;
use Pimcore\Maintenance\Executor;
use Pimcore\Maintenance\ExecutorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:maintenance',
    description: 'Asynchronous maintenance jobs of pimcore (needs to be set up as cron job)',
    aliases: ['maintenance']
)]
class MaintenanceCommand extends AbstractCommand
{
    use DeprecatedOptionNameTrait;

    public function __construct(
        #[Autowire(service: Executor::class)]
        private readonly ExecutorInterface $maintenanceExecutor,
        private readonly LoggerInterface $logger
    )
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $help = $this->getDescription() . '. Valid jobs are: ' . "\n\n";
        $help .= '  <comment>*</comment> any bundle class name handling maintenance (e.g. <comment>PimcoreEcommerceFrameworkBundle</comment>)' . "\n";

        foreach ($this->maintenanceExecutor->getTaskNames() as $taskName) {
            $help .= '  <comment>*</comment> ' . $taskName . "\n";
        }

        $this
            ->setHelp($help)
            ->addOption(
                'job',
                'j',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Call just a specific job(s) (see <comment>--help</comment> for a list of valid jobs)',
                null,
                fn () => $this->maintenanceExecutor->getTaskNames()
            )
            ->addOption(
                'excluded-jobs',
                'J',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Exclude specific job(s) (see <comment>--help</comment> for a list of valid jobs)',
                null,
                fn () => $this->maintenanceExecutor->getTaskNames()
            );

        $this->addDeprecatedOptionName('excludedJobs', 'excluded-jobs');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $validJobs = $this->getArrayOptionValue($input, 'job');
        $excludedJobs = $this->getArrayOptionValue($input, 'excluded-jobs');

        $this->maintenanceExecutor->executeMaintenance(
            $validJobs,
            $excludedJobs
        );

        $this->logger->info('All maintenance-jobs finished!');

        return self::SUCCESS;
    }

    /**
     * Get an array option value, but still support the value being comma-separated for backwards compatibility
     */
    private function getArrayOptionValue(InputInterface $input, string $name): array
    {
        $value = $input->getOption($name);
        $result = [];

        if (!empty($value)) {
            foreach ($value as $val) {
                foreach (explode(',', $val) as $part) {
                    $part = trim($part);
                    if (!empty($part)) {
                        $result[] = $part;
                    }
                }
            }
        }

        return array_unique($result);
    }
}
