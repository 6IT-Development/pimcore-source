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

namespace Pimcore\Tests\Unit\Install;

use Pimcore\Bundle\InstallBundle\BundleConfig\BundleWriter;
use Pimcore\Tests\Support\Test\TestCase;
use RuntimeException;

class BundleWriterTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = sys_get_temp_dir() . '/pimcore-bundles-' . uniqid() . '.php';
    }

    protected function tearDown(): void
    {
        @unlink($this->file);

        parent::tearDown();
    }

    private function addBundles(string $contents, array $bundles): string
    {
        file_put_contents($this->file, $contents);
        (new BundleWriter())->addBundlesToConfig($bundles, $bundles, $this->file);

        return (string) file_get_contents($this->file);
    }

    public function testAppendsMissingBundlesAndKeepsTheFile(): void
    {
        $contents = <<<'PHP'
            <?php

            // project bundles
            return [
                Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
                Foo\EmptyEnvBundle::class => [],
                Foo\DevBundle::class => ['dev' => true, 'test' => true],
                Foo\SeoBundle::class => ['all' => true]
            ];

            PHP;

        $result = $this->addBundles($contents, ['Foo\SeoBundle', 'Foo\NewBundle']);

        $this->assertStringContainsString('// project bundles', $result);
        $this->assertStringContainsString("Foo\\EmptyEnvBundle::class => [],\n", $result);
        $this->assertStringContainsString(
            "    Foo\\SeoBundle::class => ['all' => true],\n    Foo\\NewBundle::class => ['all' => true],\n];",
            $result
        );
        $this->assertSame(1, substr_count($result, 'Foo\SeoBundle::class'));

        $bundles = include $this->file;
        $this->assertSame(
            ['Symfony\Bundle\FrameworkBundle\FrameworkBundle', 'Foo\EmptyEnvBundle', 'Foo\DevBundle', 'Foo\SeoBundle', 'Foo\NewBundle'],
            array_keys($bundles)
        );
    }

    public function testAddsBundlesToAnEmptyArray(): void
    {
        $this->addBundles("<?php\n\nreturn [];\n", ['Foo\NewBundle']);

        $this->assertSame(['Foo\NewBundle' => ['all' => true]], include $this->file);
    }

    public function testRejectsFilesNotReturningAnArray(): void
    {
        $this->expectException(RuntimeException::class);

        $this->addBundles("<?php\n\nreturn null;\n", ['Foo\NewBundle']);
    }
}
