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

namespace Pimcore\Tests\Unit\Command;

use Pimcore;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Smoke tests for the definitions and the option parsing of the CoreBundle commands. They change no data: the executed
 * commands either only read or fail on their input validation before they touch anything.
 */
class CommandDefinitionTest extends TestCase
{
    private const string NAMESPACE = 'Pimcore\\Bundle\\CoreBundle\\Command\\';

    private static ?Application $application = null;

    private function getApplication(): Application
    {
        if (null === self::$application) {
            self::$application = new Application(Pimcore::getKernel());
            self::$application->setAutoExit(false);
        }

        return self::$application;
    }

    /**
     * @return array<string, Command>
     */
    private function getCoreCommands(): array
    {
        $commands = [];
        foreach ($this->getApplication()->all() as $command) {
            // lazy commands are wrapped until they are used
            if ($command instanceof LazyCommand) {
                $command = $command->getCommand();
            }
            if (str_starts_with($command::class, self::NAMESPACE)) {
                $commands[$command->getName()] = $command;
            }
        }

        return $commands;
    }

    public function testCoreCommandsAreFound(): void
    {
        $commands = $this->getCoreCommands();

        $this->assertArrayHasKey('pimcore:maintenance', $commands);
        $this->assertArrayHasKey('pimcore:assets:remove-custom-setting', $commands);
    }

    /**
     * Building the definition, synopsis and help runs configure() and the help placeholders of every command
     */
    public function testDefinitionsCanBeBuilt(): void
    {
        foreach ($this->getCoreCommands() as $name => $command) {
            $this->assertNotSame('', $command->getDescription(), $name . ' has no description');
            $this->assertNotSame('', $command->getSynopsis(), $name);
            $command->getProcessedHelp();
        }
    }

    /**
     * I-05: options are kebab-case; deprecated camelCase aliases are described as "Deprecated, ..."
     */
    public function testOptionNamesAreKebabCase(): void
    {
        $violations = [];
        foreach ($this->getCoreCommands() as $name => $command) {
            foreach ($command->getDefinition()->getOptions() as $option) {
                if (str_starts_with($option->getDescription(), 'Deprecated')) {
                    continue;
                }
                if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $option->getName())) {
                    $violations[] = $name . ' --' . $option->getName();
                }
            }
        }

        $this->assertSame([], $violations);
    }

    /**
     * B-04: an InputOption constant as argument mode made a required argument optional
     *
     * @dataProvider requiredArgumentProvider
     */
    public function testRequiredArguments(string $commandName, string $argumentName): void
    {
        $definition = $this->getApplication()->find($commandName)->getDefinition();

        $this->assertTrue($definition->getArgument($argumentName)->isRequired());
    }

    public static function requiredArgumentProvider(): array
    {
        return [
            'remove-custom-setting' => ['pimcore:assets:remove-custom-setting', 'name'],
            'documents:cleanup' => ['pimcore:documents:cleanup', 'documentTypes'],
        ];
    }

    /**
     * @dataProvider invalidInputProvider
     */
    public function testInvalidInputIsRejected(string $commandName, array $input): void
    {
        $tester = new CommandTester($this->getApplication()->find($commandName));

        $this->assertSame(Command::INVALID, $tester->execute($input, ['interactive' => false]));
    }

    public static function invalidInputProvider(): array
    {
        return [
            // I-21
            'maintenance-mode --enable --disable' => ['pimcore:maintenance-mode', ['--enable' => true, '--disable' => true]],
            'maintenance-mode --status --enable' => ['pimcore:maintenance-mode', ['--status' => true, '--enable' => true]],
            // I-28
            'mysql-tools without mode' => ['pimcore:mysql-tools', []],
            'mysql-tools unknown mode' => ['pimcore:mysql-tools', ['mode' => 'repair']],
            'mysql-tools argument and option differ' => ['pimcore:mysql-tools', ['mode' => 'optimize', '--mode' => 'warmup']],
            'requirements unknown format' => ['pimcore:system:requirements:check', ['--format' => 'xml']],
            'requirements unknown min-level' => ['pimcore:system:requirements:check', ['--min-level' => 'info']],
        ];
    }

    /**
     * B-07: the table/JSON switch and multibyte descriptions broke the JSON output
     */
    public function testBundleListJsonIsValid(): void
    {
        $tester = new CommandTester($this->getApplication()->find('pimcore:bundle:list'));

        $this->assertSame(Command::SUCCESS, $tester->execute(['--json' => true]));
        $this->assertIsArray(json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR));
    }

    /**
     * B-22: the deprecation of --generator fired on every run, because hasOption() is always true for a defined option
     */
    public function testUnusedDeprecatedOptionDoesNotWarn(): void
    {
        $tester = new CommandTester($this->getApplication()->find('pimcore:image:low-quality-preview'));

        // an ID that does not exist: no image is processed
        $tester->execute(['--id' => [(string) PHP_INT_MAX]]);

        $this->assertStringNotContainsString('--generator', $tester->getDisplay());
    }
}
