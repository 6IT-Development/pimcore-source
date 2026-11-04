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

namespace Pimcore\Tests\Unit\DependencyInjection;

use Pimcore\Bundle\CoreBundle\DependencyInjection\Configuration;
use Pimcore\Bundle\CoreBundle\DependencyInjection\ConfigurationHelper;
use Pimcore\Bundle\CoreBundle\DependencyInjection\PimcoreCoreExtension;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The config tree processed with a plain Processor or a ContainerBuilder, without a kernel.
 */
class ConfigurationTest extends TestCase
{
    /**
     * B-54: boolean strings (settings store, quoted YAML values) are converted by their meaning, not by PHP truthiness.
     *
     * @dataProvider provideBooleanStrings
     */
    public function testBooleanStringsAreConvertedByTheirMeaning(string $value, bool $expected): void
    {
        $config = $this->process([['general' => ['redirect_to_maindomain' => $value]]]);

        $this->assertSame($expected, $config['general']['redirect_to_maindomain']);
    }

    public static function provideBooleanStrings(): array
    {
        return [
            'false' => ['false', false],
            'off' => ['off', false],
            'no' => ['no', false],
            '0' => ['0', false],
            'empty' => ['', false],
            'true' => ['true', true],
            'on' => ['on', true],
            '1' => ['1', true],
        ];
    }

    public function testInvalidBooleanStringIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->process([['general' => ['redirect_to_maindomain' => 'maybe']]]);
    }

    /**
     * B-54, B-70: a "%env(bool:X)%" placeholder is kept, so the env var is read at runtime.
     */
    public function testBooleanEnvPlaceholderIsResolvedFromTheEnvVar(): void
    {
        $container = $this->createContainer([['general' => ['redirect_to_maindomain' => '%env(bool:PIMCORE_TEST_REDIRECT)%']]]);

        $config = ConfigurationHelper::getConfigNodeFromSymfonyTree($container, 'pimcore');
        $value = $config['general']['redirect_to_maindomain'];
        $this->assertIsString($value);

        $_ENV['PIMCORE_TEST_REDIRECT'] = '0';

        try {
            $this->assertFalse($container->resolveEnvPlaceholders($value, true));
        } finally {
            unset($_ENV['PIMCORE_TEST_REDIRECT']);
        }
    }

    /**
     * B-54: comma separated languages are split and trimmed; a csv env placeholder is split at runtime.
     */
    public function testCsvStringsAreSplitAndTrimmed(): void
    {
        $config = $this->process([['general' => ['valid_languages' => 'en, de,,fr ']]]);

        $this->assertSame(['en', 'de', 'fr'], $config['general']['valid_languages']);
    }

    public function testCsvEnvPlaceholderIsResolvedFromTheEnvVar(): void
    {
        $container = $this->createContainer([['general' => ['valid_languages' => '%env(csv:PIMCORE_TEST_LANGUAGES)%']]]);

        $config = ConfigurationHelper::getConfigNodeFromSymfonyTree($container, 'pimcore');
        $_ENV['PIMCORE_TEST_LANGUAGES'] = 'en,de';

        try {
            $this->assertSame(['en', 'de'], $container->resolveEnvPlaceholders($config['general']['valid_languages'], true));
        } finally {
            unset($_ENV['PIMCORE_TEST_LANGUAGES']);
        }
    }

    /**
     * B-56: placeholders apply to the whole workflow they are defined in, whatever the key order, and not to other
     * workflows.
     */
    public function testWorkflowPlaceholdersApplyOnlyToTheirWorkflow(): void
    {
        $first = $this->createWorkflow('%%place%% (first)');
        // places before placeholders
        $first['placeholders'] = ['%%place%%' => 'Draft'];
        $first['transitions']['publish']['options']['label'] = 'Publish %%place%%';

        $config = $this->process([['workflows' => [
            'first' => $first,
            'second' => $this->createWorkflow('%%place%% (second)'),
        ]]]);

        $this->assertSame('Draft (first)', $config['workflows']['first']['places']['draft']['label']);
        $this->assertSame('Publish Draft', $config['workflows']['first']['transitions']['publish']['options']['label']);
        $this->assertSame('%%place%% (second)', $config['workflows']['second']['places']['draft']['label']);
    }

    public function testWorkflowPlaceholdersAreMergedAcrossConfigFiles(): void
    {
        $config = $this->process([
            ['workflows' => ['first' => $this->createWorkflow('%%place%%')]],
            ['workflows' => ['first' => ['placeholders' => ['%%place%%' => 'Draft']]]],
        ]);

        $this->assertSame('Draft', $config['workflows']['first']['places']['draft']['label']);
    }

    /**
     * B-57: label is optional, global actions accept the same field types as transitions.
     */
    public function testWorkflowWithoutLabelAndNumericGlobalActionField(): void
    {
        $workflow = $this->createWorkflow('Draft');
        $workflow['globalActions'] = [
            'reset' => [
                'label' => 'Reset',
                'to' => 'draft',
                'notes' => ['additionalFields' => [['name' => 'amount', 'fieldType' => 'numeric']]],
            ],
        ];

        $config = $this->process([['workflows' => ['first' => $workflow]]]);

        $this->assertNull($config['workflows']['first']['label']);
        $this->assertSame(
            'numeric',
            $config['workflows']['first']['globalActions']['reset']['notes']['additionalFields'][0]['fieldType']
        );
    }

    /**
     * I-62: transitions may only reference defined places.
     */
    public function testWorkflowTransitionToUnknownPlaceIsRejected(): void
    {
        $workflow = $this->createWorkflow('Draft');
        $workflow['transitions']['publish']['to'] = ['archived'];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('archived');
        $this->process([['workflows' => ['first' => $workflow]]]);
    }

    /**
     * I-63: the tree has a config_location node for every key the kernel imports.
     */
    public function testConfigLocationNodesMatchTheKernelKeys(): void
    {
        $config = $this->process([]);

        $this->assertSame(array_keys(Configuration::CONFIG_LOCATION_KEYS), array_keys($config['config_location']));
        foreach (Configuration::CONFIG_LOCATION_KEYS as $key => $hasReadTarget) {
            $this->assertSame($hasReadTarget, isset($config['config_location'][$key]['read_target']), $key);
            $this->assertSame(
                PIMCORE_CONFIGURATION_DIRECTORY . '/' . $key,
                $config['config_location'][$key]['write_target']['options']['directory']
            );
        }
    }

    private function process(array $configs): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }

    private function createContainer(array $configs): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new PimcoreCoreExtension());
        foreach ($configs as $config) {
            $container->loadFromExtension('pimcore', $config);
        }

        return $container;
    }

    private function createWorkflow(string $placeLabel): array
    {
        return [
            'supports' => ['Pimcore\Model\DataObject\Concrete'],
            'places' => [
                'draft' => ['label' => $placeLabel],
                'published' => ['label' => 'Published'],
            ],
            'transitions' => [
                'publish' => ['from' => 'draft', 'to' => 'published'],
            ],
        ];
    }
}
