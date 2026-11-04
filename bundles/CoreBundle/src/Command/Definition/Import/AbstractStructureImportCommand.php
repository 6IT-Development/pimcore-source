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
use JsonException;
use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Traits\DryRun;
use Pimcore\Model\ModelInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
abstract class AbstractStructureImportCommand extends AbstractCommand
{
    use DryRun;

    #[\Override]
    protected function configure(): void
    {
        $type = $this->getType();

        $this
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                sprintf('Path to %s JSON export file', $type)
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                sprintf('Force import (do not ask for confirmation when %s already exists)', $type)
            );

        $this->configureDryRunOption();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->getPath();
        $type = $this->getType();

        $name = $this->getDefinitionName(basename($path));
        if (null === $name) {
            throw new InvalidArgumentException('File name does not match expected format');
        }

        $json = $this->getJson($path);
        $force = $this->input->getOption('force');

        $logName = sprintf('(%s) <comment>%s</comment>', $type, $name);

        $definition = $this->loadDefinition($name);
        if (null !== $definition) {
            if ($force) {
                $this->output->writeln(sprintf('%s already exists', $logName));
            } elseif (!$this->input->isInteractive()) {
                $this->io->error(sprintf('%s already exists, use --force to overwrite it', $logName));

                return self::FAILURE;
            } elseif (!$this->askConfirmation($name)) {
                $this->output->writeln(sprintf('Skipping import of %s', $logName));

                return self::SUCCESS;
            }
        } else {
            $this->output->writeln(sprintf('%s was not found', $logName));

            if ($this->isDryRun()) {
                $this->output->writeln($this->prefixDryRun(sprintf('Skipping creation of %s', $logName)));
            } else {
                $this->output->writeln(sprintf('Creating %s', $logName));
                $definition = $this->createDefinition($name);
                if (null === $definition) {
                    $this->io->error(sprintf('Could not create %s', $logName));

                    return self::INVALID;
                }
            }
        }

        if ($this->isDryRun()) {
            $this->output->writeln($this->prefixDryRun(sprintf('Skipping import of %s from %s', $logName, $path)));
            $result = true;
        } else {
            $this->output->writeln(sprintf('Importing %s from %s', $logName, $path));
            $result = $this->import($definition, $json);
        }

        if ($result) {
            $this->output->writeln(sprintf('Successfully imported %s', $logName));

            return self::SUCCESS;
        } else {
            $this->output->writeln(sprintf('<error>ERROR:</error> Failed to import %s', $logName));

            return self::FAILURE;
        }
    }

    /**
     * Returns the path argument after checking that the file can be read
     */
    protected function getPath(): string
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
    protected function getJson(string $path): string
    {
        $content = file_get_contents($path);

        // validate the JSON here as we want to fail early if the file is no valid JSON
        if (!json_validate($content)) {
            throw new JsonException(json_last_error_msg());
        }

        // return string content as service import
        // methods decode JSON by their own
        return $content;
    }

    /**
     * Asks whether the existing definition $name should be overwritten
     */
    protected function askConfirmation(string $name): bool
    {
        return $this->io->confirm(
            sprintf('(%s) <comment>%s</comment> already exists. Overwrite?', $this->getType(), $name),
            false
        );
    }

    /**
     * Name of the definition type, used in the argument and option descriptions and in messages (e.g. "Class")
     */
    abstract protected function getType(): string;

    /**
     * Extracts the definition name from the export file name (e.g. class_Customer_export.json -> Customer)
     *
     * @return string|null null if the file name does not match the expected format
     */
    abstract protected function getDefinitionName(string $filename): ?string;

    /**
     * Loads the existing definition, null if there is none
     */
    abstract protected function loadDefinition(string $name): ?ModelInterface;

    /**
     * Creates a new, unsaved definition, null if it can not be created
     */
    abstract protected function createDefinition(string $name): ?ModelInterface;

    /**
     * Imports the JSON export into the definition and saves it
     *
     * @return bool false if the import failed
     */
    abstract protected function import(ModelInterface $definition, string $json): bool;
}
