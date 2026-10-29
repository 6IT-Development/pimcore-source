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

namespace Pimcore\Bundle\ApplicationLoggerBundle\Maintenance;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Pimcore\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;
use Pimcore\Config;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Tool\Storage;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
readonly class LogArchiveTask implements TaskInterface
{
    public function __construct(
        private Connection      $db,
        private Config          $config,
        private LoggerInterface $logger
    )
    {
    }

    public function execute(): void
    {
        $db = $this->db;
        $storage = Storage::get('application_log');

        $date = new DateTime('now');
        $archiveTableName = ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX . '_' . $date->format('Y') . '_' . $date->format('m');
        $tablename = $archiveTableName;

        if (!empty($this->config['applicationlog']['archive_alternative_database'])) {
            $tablename = $db->quoteIdentifier($this->config['applicationlog']['archive_alternative_database']) . '.' . $tablename;
        }

        $archive_threshold = (int)($this->config['applicationlog']['archive_treshold'] ?? 30);

        $timestamp = time();
        $sql = 'SELECT %s FROM ' . ApplicationLoggerDb::TABLE_NAME . ' WHERE `timestamp` < DATE_SUB(FROM_UNIXTIME(' . $timestamp . '), INTERVAL ' . $archive_threshold . ' DAY)';

        if ($db->fetchOne(sprintf($sql, 'COUNT(*)')) > 0) {
            $columns = [];
            $columnDefinitions = [];
            foreach (ApplicationLoggerDb::COLUMNS as $name => $definition) {
                $columns[] = $db->quoteIdentifier($name);
                $columnDefinitions[] = $db->quoteIdentifier($name) . ' ' . $definition;
            }

            $db->executeQuery('CREATE TABLE IF NOT EXISTS ' . $tablename . ' (' . implode(', ', $columnDefinitions) . ') ENGINE = ARCHIVE ROW_FORMAT = DEFAULT DEFAULT CHARSET=utf8mb4;');
            $this->widenLegacyArchiveTable($archiveTableName, $tablename);

            // explicit column lists, so a column added to the log table cannot shift values into the wrong archive column
            $db->executeQuery('INSERT INTO ' . $tablename . ' (' . implode(', ', $columns) . ') ' . sprintf($sql, implode(', ', $columns)));

            $this->logger->debug('Deleting referenced FileObjects of application_logs which are older than ' . $archive_threshold . ' days');

            $fileObjectPaths = $db->fetchAllAssociative(sprintf($sql, 'fileobject'));
            foreach ($fileObjectPaths as $objectPath) {
                $filePath = $objectPath['fileobject'];
                if ($filePath !== null) {
                    if ($storage->fileExists($filePath)) {
                        $storage->delete($filePath);
                    }
                }
            }

            $db->executeQuery('DELETE FROM ' . ApplicationLoggerDb::TABLE_NAME . ' WHERE `timestamp` < DATE_SUB(FROM_UNIXTIME(' . $timestamp . '), INTERVAL ' . $archive_threshold . ' DAY);');
        }

        $archiveTables = $db->fetchFirstColumn(
            'SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = ?
                AND table_name LIKE ?',
            [
                $this->config['applicationlog']['archive_alternative_database'] ?: $db->getDatabase(),
                ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX . '_%',
            ]
        );

        foreach ($archiveTables as $archiveTable) {
            if (preg_match('/^' . ApplicationLoggerDb::TABLE_ARCHIVE_PREFIX . '_(\d{4})_(\d{2})$/', $archiveTable, $matches)) {
                // "!" resets day and time, otherwise they are taken from now and e.g. 2026/02 becomes 2026-03-03 on the 31st
                $archiveLogDate = DateTimeImmutable::createFromFormat('!Y/m', $matches[1] . '/' . $matches[2]);
                if ($archiveLogDate === false) {
                    continue;
                }

                $deleteArchiveLogDate = $archiveLogDate->add(new DateInterval('P' . (int)($this->config['applicationlog']['delete_archive_threshold'] ?? 6) . 'M'));
                if ($deleteArchiveLogDate < new DateTimeImmutable()) {
                    $db->executeStatement('DROP TABLE IF EXISTS `' . ($this->config['applicationlog']['archive_alternative_database'] ?: $db->getDatabase()) . '`.' . $archiveTable);

                    // the folder of the archived month, not of the month the archive expires
                    $folderName = $archiveLogDate->format('Y/m');

                    if ($storage->directoryExists($folderName)) {
                        $storage->deleteDirectory($folderName);
                    }
                }
            }
        }
    }

    /**
     * Archive tables created by earlier versions have message VARCHAR(1024), so in strict mode a single longer message
     * made every archive run fail. Their other columns are at least as wide as the log table's.
     */
    private function widenLegacyArchiveTable(string $archiveTableName, string $quotedTableName): void
    {
        $messageType = $this->db->fetchOne(
            'SELECT DATA_TYPE FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?',
            [
                $this->config['applicationlog']['archive_alternative_database'] ?: $this->db->getDatabase(),
                $archiveTableName,
                'message',
            ]
        );

        if (is_string($messageType) && strtolower($messageType) === 'varchar') {
            $this->logger->info('Changing column message of ' . $archiveTableName . ' to TEXT');
            $this->db->executeStatement('ALTER TABLE ' . $quotedTableName . ' MODIFY `message` ' . ApplicationLoggerDb::COLUMNS['message']);
        }
    }
}
