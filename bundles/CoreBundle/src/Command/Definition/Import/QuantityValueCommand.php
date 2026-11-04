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

namespace Pimcore\Bundle\CoreBundle\Command\Definition\Import;

use InvalidArgumentException;
use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Traits\DryRun;
use Pimcore\Model\DataObject\QuantityValue\Service;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:definition:import:units',
    description: 'Import quantity value units from a JSON export',
    aliases: ['definition:import:units']
)]
class QuantityValueCommand extends AbstractCommand
{
    use DryRun;

    public function __construct(private readonly Service $service)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Path to quantity value unit JSON export file')
            ->addOption(
                'override',
                'o',
                InputOption::VALUE_NEGATABLE,
                'Override the existing unit definition'
            );

        $this->configureDryRunOption();
    }

    /**
     * Returns the path argument after checking that the file can be read
     */
    private function getPath(): string
    {
        $path = $this->input->getArgument('path');
        if (!file_exists($path) || !is_readable($path)) {
            throw new InvalidArgumentException('File does not exist');
        }

        return $path;
    }

    /**
     * Load JSON data from file
     */
    private function getJson(string $path): string
    {
        $content = file_get_contents($path);

        // validate the JSON here as we want to fail early if the file is no valid JSON
        if (!json_validate($content)) {
            throw new InvalidArgumentException('JSON could not be decoded');
        }

        return $content;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->getPath();
        $json = $this->getJson($path);
        $override = $this->input->getOption('override') ?? false;

        if ($this->isDryRun()) {
            $this->output->writeln($this->prefixDryRun(sprintf('Skipping the unit definition import from %s', $path)));
            $result = true;
        } else {
            $this->output->writeln(sprintf('Importing quantity value unit definitions from %s', $path));
            $result = $this->service->importDefinitionFromJson($json, $override);
        }

        if ($result) {
            $this->output->writeln('Successfully imported definitions');

            return self::SUCCESS;
        } else {
            $this->output->writeln('<error>ERROR:</error> Failed to import definitions');

            return self::FAILURE;
        }
    }
}
