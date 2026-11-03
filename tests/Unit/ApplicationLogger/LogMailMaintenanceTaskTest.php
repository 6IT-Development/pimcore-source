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

namespace Pimcore\Tests\Unit\ApplicationLogger;

use Pimcore\Bundle\ApplicationLoggerBundle\Maintenance\LogMailMaintenanceTask;
use Pimcore\Tests\Support\Test\TestCase;

class LogMailMaintenanceTaskTest extends TestCase
{
    public function testEscapesLogEntries(): void
    {
        $html = LogMailMaintenanceTask::renderEntries([['message' => '<script>alert(1)</script>']]);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
