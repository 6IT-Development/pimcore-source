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

    public function testMergesConfiguredDbCredentialsWithParameters(): void
    {
        $installer = $this->createInstaller();
        $installer->setDbCredentials(['host' => 'db.example.com', 'port' => '', 'user' => null]);

        $this->assertTrue($installer->isDbCredentialConfigured('host', 'unix_socket'));
        $this->assertFalse($installer->isDbCredentialConfigured('port'));
        $this->assertFalse($installer->isDbCredentialConfigured('user', 'password', 'dbname'));

        $dbConfig = $installer->resolveDbConfig([
            'mysql_username' => 'pimcore',
            'mysql_password' => 'secret',
            'mysql_database' => 'pimcore_db',
            'mysql_ssl_cert_path' => '',
        ]);

        $this->assertSame('db.example.com', $dbConfig['host']);
        $this->assertSame('pimcore', $dbConfig['user']);
        $this->assertSame('secret', $dbConfig['password']);
        $this->assertSame('pimcore_db', $dbConfig['dbname']);
    }

    public function testConfiguredDbCredentialsTakePrecedence(): void
    {
        $installer = $this->createInstaller();
        $installer->setDbCredentials(['user' => 'configured', 'dbname' => 'configured_db']);

        $dbConfig = $installer->resolveDbConfig([
            'mysql_host_socket' => 'db.example.com',
            'mysql_port' => '3307',
            'mysql_password' => '',
        ]);

        $this->assertSame('configured', $dbConfig['user']);
        $this->assertSame('configured_db', $dbConfig['dbname']);
        $this->assertSame('db.example.com', $dbConfig['host']);
    }

    public function testRejectsReservedAndInvalidAdminUsernamesBeforeConnecting(): void
    {
        $params = [
            'admin_password' => 'secret-password',
            'mysql_host_socket' => 'db.example.com',
            'mysql_username' => 'pimcore',
            'mysql_password' => '',
            'mysql_database' => 'pimcore',
        ];

        $this->assertStringContainsString(
            '"system" is reserved',
            implode("\n", $this->createInstaller()->install($params + ['admin_username' => 'System']))
        );
        $this->assertStringContainsString(
            'longer than 50 characters',
            implode("\n", $this->createInstaller()->install($params + ['admin_username' => str_repeat('a', 51)]))
        );
        $this->assertStringContainsString(
            'invalid characters',
            implode("\n", $this->createInstaller()->install($params + ['admin_username' => 'ad min']))
        );
    }

    public function testUsesOnlyRealSocketsAsUnixSocket(): void
    {
        $directory = sys_get_temp_dir() . '/pimcore-installer-test-' . uniqid();
        mkdir($directory);
        $socketPath = $directory . '/mysqld.sock';
        $server = stream_socket_server('unix://' . $socketPath);

        try {
            $params = ['mysql_username' => 'pimcore', 'mysql_password' => '', 'mysql_database' => 'pimcore', 'mysql_port' => '3307'];

            // a directory is not a socket
            $dbConfig = $this->createInstaller()->resolveDbConfig($params + ['mysql_host_socket' => $directory]);
            $this->assertArrayNotHasKey('unix_socket', $dbConfig);
            $this->assertSame($directory, $dbConfig['host']);
            $this->assertSame(3307, $dbConfig['port']);

            $dbConfig = $this->createInstaller()->resolveDbConfig($params + ['mysql_host_socket' => $socketPath]);
            $this->assertSame($socketPath, $dbConfig['unix_socket']);
            $this->assertArrayNotHasKey('port', $dbConfig);
        } finally {
            fclose($server);
            @unlink($socketPath);
            rmdir($directory);
        }
    }
}
