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

namespace Pimcore\Bundle\CustomReportsBundle\Tool\Adapter;

use Doctrine\DBAL\Connection;
use Exception;
use Pimcore;
use Pimcore\Db;
use stdClass;

/**
 * @internal
 */
class Sql extends AbstractAdapter
{
    /**
     * Statements, clauses and functions a report query must not contain. This check only gives early feedback, the
     * protection against writes is the read-only transaction (and the optional read-only connection) in runReadOnly().
     */
    private const array FORBIDDEN_KEYWORDS = [
        'ALTER', 'CREATE', 'DROP', 'RENAME', 'TRUNCATE', 'UPDATE', 'DELETE', 'INSERT', 'INTO', 'GRANT', 'REVOKE',
        'CALL', 'HANDLER', 'LOAD', 'LOCK', 'UNLOCK', 'PREPARE', 'EXECUTE', 'DEALLOCATE', 'FLUSH', 'KILL', 'SHUTDOWN',
        'OUTFILE', 'DUMPFILE', 'LOAD_FILE', 'SLEEP', 'BENCHMARK', 'GET_LOCK',
    ];

    public function getData(?array $filters, ?string $sort, ?string $dir, ?int $offset, ?int $limit, ?array $fields = null, ?array $drillDownFilters = null): array
    {
        $db = $this->getDb();

        $baseQuery = $this->getBaseQuery($filters ?? [], $fields ?? [], false, $drillDownFilters ?? []);
        $data = [];
        $total = 0;

        if ($baseQuery) {

            $order = '';
            if ($sort && $dir) {
                $dir = ((strtoupper($dir) === 'ASC') ? 'ASC' : 'DESC');
                $order = ' ORDER BY ' . $db->quoteIdentifier($sort) . ' ' . $dir;
            }

            $sql = $baseQuery['data'] . $order;
            if ($offset !== null && $limit) {
                $sql .= " LIMIT $offset,$limit";
            }

            [$total, $data] = $this->runReadOnly(static fn (Connection $db): array => [
                $db->fetchOne($baseQuery['count']),
                $db->fetchAllAssociative($sql),
            ]);
        }

        return ['data' => $data, 'total' => $total];
    }

    public function getColumns(?stdClass $configuration): array
    {
        $sql = '';
        if ($configuration) {
            $sql = $this->buildQueryString($configuration);
        }

        $this->validateQuery($sql);

        $sql .= ' LIMIT 0,1';
        $res = $this->runReadOnly(static fn (Connection $db): array|false => $db->fetchAssociative($sql));
        if ($res) {
            return array_keys($res);
        }

        return [];
    }

    /**
     * Rejects everything but a single SELECT statement.
     *
     * @throws Exception
     */
    protected function validateQuery(string $sql): void
    {
        // check the query with and without backslash escapes in string literals, so the result does not depend on
        // the NO_BACKSLASH_ESCAPES SQL mode
        foreach ([true, false] as $backslashEscapes) {
            $code = $this->stripLiteralsAndComments($sql, $backslashEscapes);

            if (str_contains($code, ';')) {
                throw new Exception('Only a single SELECT statement is allowed!');
            }

            if (preg_match('/\\b(' . implode('|', self::FORBIDDEN_KEYWORDS) . ')\\b/i', $code, $matches)) {
                throw new Exception("Only 'SELECT' statements are allowed! You've used '" . $matches[1] . "'");
            }
        }
    }

    /**
     * Replaces string literals, quoted identifiers and comments by a space. The content of executable comments
     * (/*! ... *\/, /*M! ... *\/) is kept, as MySQL and MariaDB run it.
     */
    private function stripLiteralsAndComments(string $sql, bool $backslashEscapes): string
    {
        // a character inside a quoted literal: anything but the quote and, with escapes, a backslash plus any character
        $char = static fn (string $quote): string => $backslashEscapes ? '[^' . $quote . '\\\\]|\\\\.' : '[^' . $quote . ']';
        $pattern = '~'
            . "'(?:" . $char("'") . "|'')*'"
            . '|"(?:' . $char('"') . '|"")*"'
            . '|`(?:[^`]|``)*`'
            . '|/\\*(M?!\\d*)?(.*?)\\*/'
            . '|(?:--\\s|#)[^\\n]*'
            . '~s';

        return (string) preg_replace_callback(
            $pattern,
            static fn (array $match): string => ($match[1] ?? '') !== '' ? ' ' . $match[2] . ' ' : ' ',
            $sql
        );
    }

    /**
     * Runs the report queries inside a read-only transaction, so a query that slips through validateQuery() cannot
     * change data.
     */
    protected function runReadOnly(callable $callback): mixed
    {
        $db = $this->getDb();

        if ($db->isTransactionActive()) {
            // START TRANSACTION would implicitly commit the transaction of the caller
            return $callback($db);
        }

        $db->executeStatement('START TRANSACTION READ ONLY');

        try {
            return $callback($db);
        } finally {
            $db->executeStatement('ROLLBACK');
        }
    }

    /**
     * The connection the report queries run on: the one configured in pimcore_custom_reports.connection (ideally a DB
     * user with SELECT grants only), otherwise the default connection.
     */
    protected function getDb(): Connection
    {
        $container = Pimcore::getContainer();
        $connectionName = $container->getParameter('pimcore_custom_reports.connection');

        if ($connectionName) {
            return $container->get('doctrine')->getConnection($connectionName);
        }

        return Db::get();
    }

    protected function buildQueryString(stdClass $config, bool $ignoreSelectAndGroupBy = false, ?array $drillDownFilters = null, ?string $selectField = null): string
    {
        $config = (array)$config;
        $sql = '';
        if (!empty($config['sql']) && !$ignoreSelectAndGroupBy) {
            if (!str_starts_with(strtoupper(trim($config['sql'])), 'SELECT')) {
                $sql .= 'SELECT';
            }
            $sql .= "\n" . $config['sql'];
        } elseif ($selectField) {
            $db = $this->getDb();
            $sql .= 'SELECT ' . $db->quoteIdentifier($selectField);
        } else {
            $sql .= 'SELECT *';
        }
        if (!empty($config['from'])) {
            if (!str_starts_with(strtoupper(trim($config['from'])), 'FROM')) {
                $sql .= "\n" . 'FROM ';
            }
            $sql .= "\n" . $config['from'];
        }

        if (!empty($config['where'])) {
            if (str_starts_with(strtoupper(trim($config['where'])), 'WHERE')) {
                $config['where'] = preg_replace('/^\s*WHERE\s*/', '', $config['where']);
            }
            $sql .= "\n" . 'WHERE (' . $config['where'] . ')';
        }

        if (!empty($config['groupby']) && !$ignoreSelectAndGroupBy) {
            if (!str_starts_with(strtoupper(trim($config['groupby'])), 'GROUP BY')) {
                $sql .= ' GROUP BY ';
            }
            $sql .= "\n" . $config['groupby'];
        }

        if ($drillDownFilters) {
            $havingParts = [];
            $db = $this->getDb();
            foreach ($drillDownFilters as $field => $value) {
                if ($value !== '' && $value !== null) {
                    $havingParts[] = ($db->quoteIdentifier($field) . ' = ' . $db->quote($value));
                }
            }

            if ($havingParts) {
                $sql .= ' HAVING ' . implode(' AND ', $havingParts);
            }
        }

        return $sql;
    }

    protected function getBaseQuery(array $filters, array $fields, bool $ignoreSelectAndGroupBy = false, ?array $drillDownFilters = null, ?string $selectField = null): ?array
    {
        $db = $this->getDb();
        $condition = ['1 = 1'];

        $sql = $this->buildQueryString($this->config, $ignoreSelectAndGroupBy, $drillDownFilters, $selectField);

        $extractAllFields = empty($fields);
        foreach ($filters as $filter) {
            $value = $filter['value'] ?? null;
            $type = $filter['type'];
            $operator = $filter['operator'];
            $maxValue = null;
            if ($type == 'date') {
                if ($operator == 'eq') {
                    $maxValue = strtotime($value . '+23 hours 59 minutes');
                }
                $value = strtotime($value);
            }

            switch ($operator) {
                case 'like':
                    $fields[] = $filter['property'];
                    $condition[] = $db->quoteIdentifier($filter['property']) . ' LIKE ' . $db->quote('%' . $value . '%');

                    break;
                case 'lt':
                case 'gt':
                case 'eq':
                    $compMapping = [
                        'lt' => '<',
                        'gt' => '>',
                        'eq' => '=',
                    ];

                    if ($type == 'date') {
                        if ($operator == 'eq') {
                            $condition[] = $db->quoteIdentifier($filter['property']) . ' BETWEEN ' . $db->quote($value) . ' AND ' . $db->quote($maxValue);

                            break;
                        }
                    }
                    $fields[] = $filter['property'];
                    $condition[] = $db->quoteIdentifier($filter['property']) . ' ' . $compMapping[$operator] . ' ' . $db->quote($value);

                    break;
                case '=':
                    $fields[] = $filter['property'];
                    $condition[] = $db->quoteIdentifier($filter['property']) . ' = ' . $db->quote($value);

                    break;
            }
        }

        try {
            $this->validateQuery($sql);
        } catch (Exception) {
            return null;
        }

        $condition = implode(' AND ', $condition);

        $total = 'SELECT COUNT(*) FROM (' . $sql . ') AS somerandxyz WHERE ' . $condition;

        if ($fields && !$extractAllFields) {
            $data = 'SELECT `' . implode('`,`', $fields) . '` FROM (' . $sql . ') AS somerandxyz WHERE ' . $condition;
        } else {
            $data = 'SELECT * FROM (' . $sql . ') AS somerandxyz WHERE ' . $condition;
        }

        return [
            'data' => $data,
            'count' => $total,
        ];
    }

    public function getAvailableOptions(array $filters, string $field, array $drillDownFilters): array
    {
        $db = $this->getDb();
        $baseQuery = $this->getBaseQuery($filters, [$field], false, $drillDownFilters);
        $data = [];
        if ($baseQuery) {
            $sql = $baseQuery['data'] . ' GROUP BY ' . $db->quoteIdentifier($field);
            $data = $this->runReadOnly(static fn (Connection $db): array => $db->fetchAllAssociative($sql));
        }

        $filteredData = [];
        foreach ($data as $d) {
            if (!empty($d[$field]) || $d[$field] === 0) {
                $filteredData[] = ['name' => $d[$field], 'value' => $d[$field]];
            }
        }

        return [
            'data' => array_merge(
                [
                    ['name' => 'empty', 'value' => null],
                ],
                $filteredData
            ),
        ];
    }
}
