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

namespace Pimcore\Bundle\ApplicationLoggerBundle;

use Doctrine\DBAL\Connection;
use Monolog\Level;
use Pimcore\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;

/**
 * @internal
 */
final readonly class ApplicationLogRepository
{
    public function __construct(private Connection $db)
    {
    }

    /**
     * @return string[]
     */
    public function getComponents(): array
    {
        return $this->db->fetchFirstColumn('SELECT component FROM ' . ApplicationLoggerDb::TABLE_NAME . ' WHERE NOT ISNULL(component) GROUP BY component;');
    }

    /**
     * @return array<string, string> labels by priority
     */
    public function getPriorities(): array
    {
        $priorities = [];

        foreach ($this->db->fetchFirstColumn('SELECT priority FROM ' . ApplicationLoggerDb::TABLE_NAME . ' WHERE NOT ISNULL(priority) GROUP BY priority;') as $priority) {
            $priorities[$priority] = match (Level::fromName($priority)) {
                Level::Debug => 'DEBUG',
                Level::Info => 'INFO',
                Level::Notice => 'NOTICE',
                Level::Warning => 'WARN',
                Level::Error => 'ERR',
                Level::Critical => 'CRIT',
                Level::Alert => 'ALERT',
                Level::Emergency => 'EMERG',
            };
        }

        return $priorities;
    }
}
