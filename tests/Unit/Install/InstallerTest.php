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

use InvalidArgumentException;
use Pimcore\Bundle\InstallBundle\Installer;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;

class InstallerTest extends TestCase
{
    private function createInstaller(): Installer
    {
        return new Installer(new NullLogger(), new EventDispatcher());
    }

    public function testRunsAllStepsByDefault(): void
    {
        $this->assertSame(Installer::INSTALL_STEPS, $this->createInstaller()->getRunInstallSteps());
    }

    public function testAcceptsKnownSteps(): void
    {
        $installer = $this->createInstaller();
        $installer->setRunInstallSteps(['setup_database', 'install_bundles', 'setup_database']);

        $this->assertSame(['setup_database', 'install_bundles'], $installer->getRunInstallSteps());
    }

    public function testRejectsUnknownSteps(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown install step(s): "setup_db"');

        $this->createInstaller()->setRunInstallSteps(['setup_db', 'install_bundles']);
    }

    public function testRejectsAnEmptyStepList(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createInstaller()->setRunInstallSteps([]);
    }
}
