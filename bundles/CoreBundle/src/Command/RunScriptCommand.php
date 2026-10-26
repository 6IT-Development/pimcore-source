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
use Pimcore\Console\Traits\DryRun;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:run-script',
    description: 'Run a specific PHP script in an initialized Pimcore environment'
)]
class RunScriptCommand extends AbstractCommand
{
    use DryRun;

    protected function configure(): void
    {
        $this
            ->addArgument(
                'script',
                InputArgument::REQUIRED,
                'Path to PHP script which should run'
            );

        $this->configureDryRunOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $script = $input->getArgument('script');

        if (!(preg_match('/\.php$/', $script) && file_exists($script))) {
            $output->writeln(sprintf(
                '<error>Script %s does not exist or doesn\'t have a .php extension</error>',
                $script
            ));

            return 1;
        }

        $output->writeln($this->dryRunMessage(sprintf('Running script <info>%s</info>', $script)));

        if ($this->isDryRun()) {
            $output->writeln($this->dryRunMessage('Done'));

            return self::SUCCESS;
        }

        $obLevelBeforeScript = ob_get_level();
        ob_start();

        try {
            $result = include $script;
        } finally {
            // collect the output from buffers the script opened and left open as well
            $scriptOutput = '';
            while (ob_get_level() > $obLevelBeforeScript) {
                $scriptOutput = ob_get_clean() . $scriptOutput;
            }

            $scriptOutput = trim($scriptOutput);
            if ($scriptOutput !== '') {
                $output->writeln("\n" . $scriptOutput . "\n");
            }
        }

        $output->writeln('Done');

        // include returns 1 when the script has no return statement, so 1 can't be told apart from success
        if (false === $result) {
            return self::FAILURE;
        }
        if (is_int($result) && $result !== 1 && $result >= 0 && $result <= 255) {
            return $result;
        }

        return self::SUCCESS;
    }
}
