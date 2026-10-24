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

use Pimcore\Tests\Support\Test\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * exit/die in a command ends the process with status 0 (bare exit) and skips the console TERMINATE/ERROR events,
 * e.g. the global --maintenance-mode flag then leaves maintenance mode active. Return an exit code or throw instead.
 */
class NoExitInCommandsTest extends TestCase
{
    public function testCommandsDoNotCallExit(): void
    {
        $directory = dirname(__DIR__, 3) . '/bundles/CoreBundle/src/Command';
        $violations = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            foreach (token_get_all(file_get_contents($file->getPathname())) as $token) {
                if (is_array($token) && $token[0] === T_EXIT) {
                    $violations[] = $file->getPathname() . ':' . $token[2];
                }
            }
        }

        $this->assertSame([], $violations, 'exit/die found in commands');
    }
}
