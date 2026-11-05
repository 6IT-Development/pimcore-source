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

namespace Pimcore\Tests\Unit\Document\Adapter;

use Pimcore\Document\Adapter\Ghostscript;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class GhostscriptTest extends TestCase
{
    /**
     * C-08: the local path is embedded in a PostScript string literal, its metacharacters must be escaped.
     */
    public function testEscapePostScriptString(): void
    {
        $this->assertSame('/tmp/a.pdf', Ghostscript::escapePostScriptString('/tmp/a.pdf'));
        $this->assertSame('/tmp/x\\) \\(y\\\\.pdf', Ghostscript::escapePostScriptString('/tmp/x) (y\\.pdf'));
    }

    public function testEscapedPathKeepsThePageCountWorking(): void
    {
        $gs = new ExecutableFinder()->find('gs');
        if ($gs === null) {
            $this->markTestSkipped('Ghostscript is not installed');
        }

        $directory = sys_get_temp_dir() . '/pimcore-gs-test-' . bin2hex(random_bytes(4));
        mkdir($directory);
        $file = $directory . '/we(ird) \\name).pdf';

        try {
            new Process([$gs, '-q', '-dNOPAUSE', '-dBATCH', '-sDEVICE=pdfwrite', '-o', $file, '-c', 'showpage showpage'])->mustRun();

            $process = new Process([
                $gs, '-dNODISPLAY', '-q', '-dSAFER', '--permit-file-read=' . $file,
                '-c', '(' . Ghostscript::escapePostScriptString($file) . ') (r) file runpdfbegin pdfpagecount = quit',
            ]);
            $process->mustRun();

            $this->assertSame('2', trim($process->getOutput()));
        } finally {
            @unlink($file);
            @rmdir($directory);
        }
    }
}
