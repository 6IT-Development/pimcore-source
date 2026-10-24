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
use Pimcore\Tests\Support\Test\TestCase;

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
}
