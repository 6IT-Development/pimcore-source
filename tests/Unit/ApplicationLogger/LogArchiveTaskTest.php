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

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Bundle\ApplicationLoggerBundle\Maintenance\LogArchiveTask;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\MockClock;

class LogArchiveTaskTest extends TestCase
{
    private Connection&MockObject $db;

    private FilesystemOperator&MockObject $storage;

    /** @var string[] */
    private array $statements = [];

    private string|false $engineSupport = 'YES';

    protected function setUp(): void
    {
        parent::setUp();

        $this->statements = [];
        $this->engineSupport = 'YES';

        $this->storage = $this->createMock(FilesystemOperator::class);

        $this->db = $this->createMock(Connection::class);
        $this->db->method('getDatabase')->willReturn('pimcore');
        $this->db->method('quoteIdentifier')->willReturnCallback(static fn (string $name): string => '`' . $name . '`');
        $this->db->method('executeStatement')->willReturnCallback(function (string $sql): int {
            $this->statements[] = $sql;

            return 1;
        });
    }

    public function testDeletesTheFolderOfTheExpiredMonthOnly(): void
    {
        $this->db->method('fetchOne')->willReturn(0);
        $this->db->method('fetchFirstColumn')->willReturn([
            'application_logs_archive_2026_01',
            'application_logs_archive_2026_10',
        ]);
        $this->storage->method('directoryExists')->willReturn(true);
        $this->storage->expects($this->once())->method('deleteDirectory')->with('2026/01');

        $this->createTask()->execute();

        $this->assertCount(1, $this->statements);
        $this->assertStringContainsString('`application_logs_archive_2026_01`', $this->statements[0]);
    }

    public function testNamesTheColumnsAndUsesTextForTheMessage(): void
    {
        $this->mockRowsToArchive();

        $this->createTask()->execute();

        $this->assertStringContainsString('`message` TEXT NULL', $this->statements[0]);
        $this->assertStringContainsString('ENGINE = ARCHIVE', $this->statements[0]);
        $this->assertMatchesRegularExpression('/^INSERT INTO \S+ \(`id`, `pid`, /', $this->findStatement('INSERT'));
    }

    public function testDeletesRowsAfterCopyingAndFilesLast(): void
    {
        $this->mockRowsToArchive();
        $this->storage->method('fileExists')->willReturn(true);
        $this->storage->expects($this->once())->method('delete')->with('/file')->willReturnCallback(function (): void {
            $this->assertNotSame('', $this->findStatement('DELETE'));
        });

        $this->createTask()->execute();

        $this->assertLessThan(
            array_search($this->findStatement('DELETE'), $this->statements, true),
            array_search($this->findStatement('INSERT'), $this->statements, true)
        );
    }

    public function testFallsBackToInnoDbWithoutArchiveEngine(): void
    {
        $this->engineSupport = 'NO';
        $this->mockRowsToArchive();

        $this->createTask()->execute();

        $this->assertStringContainsString('ENGINE = InnoDB', $this->statements[0]);
    }

    private function mockRowsToArchive(): void
    {
        $this->db->method('fetchOne')->willReturnCallback(fn (string $sql): int|string|false => match (true) {
            str_contains($sql, 'information_schema.ENGINES') => $this->engineSupport,
            str_contains($sql, 'COUNT(*)') => 1,
            default => false,
        });
        $this->db->method('fetchAllAssociative')->willReturnOnConsecutiveCalls([['id' => 1, 'fileobject' => '/file']], []);
        $this->db->method('fetchFirstColumn')->willReturn([]);
    }

    private function findStatement(string $prefix): string
    {
        foreach ($this->statements as $statement) {
            if (str_starts_with($statement, $prefix)) {
                return $statement;
            }
        }

        return '';
    }

    private function createTask(): LogArchiveTask
    {
        return new LogArchiveTask(
            $this->db,
            new NullLogger(),
            new MockClock(new DateTimeImmutable('2026-11-03 12:00:00')),
            $this->storage,
            30,
            '',
            6
        );
    }
}
