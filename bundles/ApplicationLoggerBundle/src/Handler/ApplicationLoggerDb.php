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

namespace Pimcore\Bundle\ApplicationLoggerBundle\Handler;

use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Pimcore\Db;

class ApplicationLoggerDb extends AbstractProcessingHandler
{
    final public const string TABLE_NAME = 'application_logs';

    final public const string TABLE_ARCHIVE_PREFIX = 'application_logs_archive';

    /**
     * Column definitions of the log table, shared by the archive tables so both always have the same schema.
     *
     * @internal
     */
    final public const array COLUMNS = [
        'id' => 'BIGINT(20) UNSIGNED NOT NULL',
        'pid' => 'INT(11) NULL DEFAULT NULL',
        'timestamp' => 'DATETIME NOT NULL',
        'message' => 'TEXT NULL',
        'priority' => "ENUM('emergency','alert','critical','error','warning','notice','info','debug') DEFAULT NULL",
        'fileobject' => 'VARCHAR(1024) DEFAULT NULL',
        'info' => 'VARCHAR(1024) DEFAULT NULL',
        'component' => 'VARCHAR(190) DEFAULT NULL',
        'source' => 'VARCHAR(190) DEFAULT NULL',
        'relatedobject' => 'INT(11) UNSIGNED DEFAULT NULL',
        'relatedobjecttype' => "ENUM('object','document','asset') DEFAULT NULL",
        'maintenanceChecked' => 'TINYINT(1) DEFAULT NULL',
    ];

    public function __construct(
        private readonly Connection $db,
        int|string|Level            $level = Level::Debug,
        bool                        $bubble = true
    )
    {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        // a log call must not turn into an application error, e.g. when the bundle is not installed,
        // the database is gone or a value does not fit its column in strict mode
        try {
            $this->db->insert(self::TABLE_NAME, [
                'pid' => getmypid(),
                'priority' => $record->level->toPsrLogLevel(),
                // TEXT holds 65535 bytes
                'message' => mb_strcut($record->message, 0, 65535, 'UTF-8'),
                'timestamp' => $record->datetime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
                'component' => $this->truncate($record->context['component'] ?? $record->channel, 190),
                'fileobject' => $this->truncate($record->context['fileObject'] ?? null, 1024),
                'relatedobject' => $record->context['relatedObject'] ?? null,
                'relatedobjecttype' => $record->context['relatedObjectType'] ?? null,
                'source' => $this->truncate($record->context['source'] ?? null, 190),
            ]);
        } catch (Exception $e) {
            error_log(sprintf(
                'Could not write to %s: %s. Log entry: [%s] %s',
                self::TABLE_NAME,
                $e->getMessage(),
                $record->level->toPsrLogLevel(),
                $record->message
            ));
        }
    }

    private function truncate(mixed $value, int $length): mixed
    {
        return is_string($value) ? mb_substr($value, 0, $length, 'UTF-8') : $value;
    }

    /**
     * @return string[]
     */
    public static function getComponents(): array
    {
        return Db::get()->fetchFirstColumn('SELECT component FROM ' . self::TABLE_NAME . ' WHERE NOT ISNULL(component) GROUP BY component;');
    }

    /**
     * @return string[]
     */
    public static function getPriorities(): array
    {
        $priorities = [];
        $priorityNames = [
            'debug' => 'DEBUG',
            'info' => 'INFO',
            'notice' => 'NOTICE',
            'warning' => 'WARN',
            'error' => 'ERR',
            'critical' => 'CRIT',
            'alert' => 'ALERT',
            'emergency' => 'EMERG',
        ];

        foreach (Db::get()->fetchFirstColumn('SELECT priority FROM ' . self::TABLE_NAME . ' WHERE NOT ISNULL(priority) GROUP BY priority;') as $priorityNumber) {
            $priorities[$priorityNumber] = $priorityNames[$priorityNumber];
        }

        return $priorities;
    }
}
