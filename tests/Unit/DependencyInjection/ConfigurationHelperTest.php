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

use Pimcore\Bundle\CoreBundle\DependencyInjection\ConfigurationHelper;
use Pimcore\Bundle\CoreBundle\DependencyInjection\PimcoreCoreExtension;
use Pimcore\Config\LocationAwareConfigRepository;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfigurationHelperTest extends TestCase
{
    private const string PROJECT_ROOT = '/var/www/html';

    private const array BASE_DIRECTORIES = [self::PROJECT_ROOT, '/mnt/shared/var', '/mnt/shared/var/config'];

    /**
     * @dataProvider provideConfigDirectories
     */
    public function testResolveConfigDirectory(string $folder, string $expected): void
    {
        $this->assertSame(
            $expected,
            ConfigurationHelper::resolveConfigDirectory($folder, self::PROJECT_ROOT, self::BASE_DIRECTORIES)
        );
    }

    public static function provideConfigDirectories(): array
    {
        return [
            'inside project root' => ['/var/www/html/var/config/image_thumbnails', '/var/www/html/var/config/image_thumbnails'],
            'config directory outside project root' => ['/mnt/shared/var/config/image_thumbnails', '/mnt/shared/var/config/image_thumbnails'],
            'relative' => ['var/config/foo', '/var/www/html/var/config/foo'],
            'project relative with leading slash (BC)' => ['/var/config/foo', '/var/www/html/var/config/foo'],
            'other absolute path is project relative (BC), path test not string test' => ['/var/www/html-shared/config', '/var/www/html/var/www/html-shared/config'],
        ];
    }

    /**
     * C-11: the default directory of a config_location node is resolved like an explicitly configured one.
     */
    public function testConfigLocationNodeResolvesTheDefaultDirectory(): void
    {
        $treeBuilder = new TreeBuilder('test');
        ConfigurationHelper::addConfigLocationWithWriteTargetNodes($treeBuilder->getRootNode(), [
            'inside' => PIMCORE_CONFIGURATION_DIRECTORY . '/inside',
            'relative' => 'var/config/relative',
        ]);

        $config = (new Processor())->process($treeBuilder->buildTree(), []);
        $locations = $config[LocationAwareConfigRepository::CONFIG_LOCATION];

        $this->assertSame(PIMCORE_CONFIGURATION_DIRECTORY . '/inside', $locations['inside']['write_target']['options']['directory']);
        $this->assertSame(PIMCORE_PROJECT_ROOT . '/var/config/relative', $locations['relative']['write_target']['options']['directory']);
        $this->assertArrayNotHasKey('read_target', $locations['inside']);
    }

    /**
     * B-55: config_location split across two config files is deep merged, like the extension does.
     */
    public function testConfigNodeFromSymfonyTreeDeepMergesConfigFiles(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new PimcoreCoreExtension());
        $container->loadFromExtension('pimcore', ['config_location' => ['image_thumbnails' => ['write_target' => ['type' => 'settings-store']]]]);
        $container->loadFromExtension('pimcore', ['config_location' => ['video_thumbnails' => ['write_target' => ['type' => 'disabled']]]]);

        $locations = ConfigurationHelper::getConfigNodeFromSymfonyTree($container, 'pimcore')['config_location'];

        $this->assertSame('settings-store', $locations['image_thumbnails']['write_target']['type']);
        $this->assertSame('disabled', $locations['video_thumbnails']['write_target']['type']);
        $this->assertSame('symfony-config', $locations['document_types']['write_target']['type']);
    }

    /**
     * I-63: the directory the kernel and the bundles import YAML files from.
     *
     * @dataProvider provideConfigLocations
     */
    public function testResolveSymfonyConfigDirectory(array $location, ?string $expected): void
    {
        $this->assertSame(
            $expected,
            LocationAwareConfigRepository::resolveSymfonyConfigDirectory(['config_location' => ['key' => $location]], 'key')
        );
    }

    public static function provideConfigLocations(): array
    {
        $write = static fn (string $type, string $directory = '/write/') => ['type' => $type, 'options' => ['directory' => $directory]];
        $read = static fn (?string $type, ?string $directory = null) => ['type' => $type, 'options' => ['directory' => $directory]];

        return [
            'write target only' => [['write_target' => $write('symfony-config')], '/write'],
            'write target only, settings store (definitions may still exist as YAML)' => [['write_target' => $write('settings-store')], '/write'],
            'read target not set' => [['write_target' => $write('symfony-config'), 'read_target' => $read(null)], '/write'],
            'read target not set, write to settings store' => [['write_target' => $write('settings-store'), 'read_target' => $read(null)], null],
            'read from settings store' => [['write_target' => $write('symfony-config'), 'read_target' => $read('settings-store', '/read')], null],
            'read from symfony config' => [['write_target' => $write('settings-store'), 'read_target' => $read('symfony-config', '/read/')], '/read'],
            'read from symfony config without directory' => [['write_target' => $write('settings-store'), 'read_target' => $read('symfony-config')], '/write'],
            'directory of an unset read target is ignored' => [['write_target' => $write('symfony-config'), 'read_target' => $read(null, '/read')], '/write'],
        ];
    }
}
