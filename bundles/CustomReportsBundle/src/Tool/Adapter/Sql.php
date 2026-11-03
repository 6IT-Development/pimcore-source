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

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Exception;
use PDO;
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

    /**
     * The filter operators of the report grid and their SQL operators.
     */
    private const array FILTER_OPERATORS = ['like' => 'LIKE', 'lt' => '<', 'gt' => '>', 'eq' => '=', '=' => '='];

    public function getData(?array $filters, ?string $sort, ?string $dir, ?int $offset, ?int $limit, ?array $fields = null, ?array $drillDownFilters = null): array
    {
        $db = $this->getDb();

        $baseQuery = $this->getBaseQuery($filters ?? [], $fields ?? [], false, $drillDownFilters ?? []);

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

        return ['data' => $data, 'total' => $total];
    }

    public function getColumns(?stdClass $configuration): array
    {
        $sql = '';
        if ($configuration) {
            $sql = $this->buildQueryString($configuration);
        }

        $this->validateQuery($sql);

        $sql = 'SELECT * FROM (' . $sql . ') AS somerandxyz LIMIT 0';

        return $this->runReadOnly(fn (Connection $db): array => $this->fetchColumnNames($db, $sql));
    }

    /**
     * Reads the column names from the result metadata, so they are also known for a result without rows. DBAL 3 has
     * no API for this, the native connection is a PDO or mysqli instance on the supported drivers.
     *
     * @return list<string>
     */
    private function fetchColumnNames(Connection $db, string $sql): array
    {
        $native = $db->getNativeConnection();

        if ($native instanceof PDO) {
            $statement = $native->query($sql);
            $names = [];
            for ($i = 0, $count = $statement->columnCount(); $i < $count; $i++) {
                $names[] = $statement->getColumnMeta($i)['name'];
            }

            return $names;
        }

        $result = $native->query($sql);
        if ($result === false) {
            throw new InvalidReportQueryException($native->error);
        }

        return array_column($result->fetch_fields(), 'name');
    }

    /**
     * Rejects everything but a single SELECT statement.
     *
     * @throws InvalidReportQueryException
     */
    protected function validateQuery(string $sql): void
    {
        // check the query with and without backslash escapes in string literals, so the result does not depend on
        // the NO_BACKSLASH_ESCAPES SQL mode
        foreach ([true, false] as $backslashEscapes) {
            $code = $this->stripLiteralsAndComments($sql, $backslashEscapes);

            if (str_contains($code, ';')) {
                throw new InvalidReportQueryException('Only a single SELECT statement is allowed!');
            }

            if (preg_match('/\\b(' . implode('|', self::FORBIDDEN_KEYWORDS) . ')\\b/i', $code, $matches)) {
                throw new InvalidReportQueryException("Only 'SELECT' statements are allowed! You've used '" . $matches[1] . "'");
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

    protected function buildQueryString(stdClass $config, bool $ignoreSelectAndGroupBy = false, ?string $selectField = null): string
    {
        $config = (array)$config;
        $sql = '';
        if (!empty($config['sql']) && !$ignoreSelectAndGroupBy) {
            if (!preg_match('/^\s*SELECT\b/i', $config['sql'])) {
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
            if (!preg_match('/^\s*FROM\b/i', $config['from'])) {
                $sql .= "\n" . 'FROM ';
            }
            $sql .= "\n" . $config['from'];
        }

        if (!empty($config['where'])) {
            $sql .= "\n" . 'WHERE (' . preg_replace('/^\s*WHERE\b\s*/i', '', $config['where']) . ')';
        }

        if (!empty($config['groupby']) && !$ignoreSelectAndGroupBy) {
            if (!preg_match('/^\s*(GROUP\s+BY|HAVING|ORDER\s+BY|LIMIT)\b/i', $config['groupby'])) {
                $sql .= ' GROUP BY ';
            }
            $sql .= "\n" . $config['groupby'];
        }

        return $sql;
    }

    protected function getBaseQuery(array $filters, array $fields, bool $ignoreSelectAndGroupBy = false, ?array $drillDownFilters = null, ?string $selectField = null): array
    {
        $db = $this->getDb();
        $condition = ['1 = 1'];

        $sql = $this->buildQueryString($this->config, $ignoreSelectAndGroupBy, $selectField);

        // the clauses of the report are free text and may end with ORDER BY or LIMIT, so filters go on the derived table
        foreach ($drillDownFilters ?? [] as $field => $value) {
            if ($value !== '' && $value !== null) {
                $condition[] = $db->quoteIdentifier((string) $field) . ' = ' . $db->quote($this->normalizeFilterValue($value));
            }
        }

        $extractAllFields = empty($fields);
        foreach ($filters as $filter) {
            [$property, $type, $operator, $value] = $this->normalizeFilter($filter);
            $column = $db->quoteIdentifier($property);
            $fields[] = $property;

            if ($type === 'date' && $operator !== 'like') {
                $condition[] = $this->buildDateCondition($db, $column, $operator, $this->parseFilterDate($value));

                continue;
            }

            if ($operator === 'like') {
                $value = '%' . $value . '%';
            }
            $condition[] = $column . ' ' . self::FILTER_OPERATORS[$operator] . ' ' . $db->quote($value);
        }

        $this->validateQuery($sql);

        $condition = implode(' AND ', $condition);

        $total = 'SELECT COUNT(*) FROM (' . $sql . ') AS somerandxyz WHERE ' . $condition;

        if ($fields && !$extractAllFields) {
            $select = implode(',', array_map($db->quoteIdentifier(...), $fields));
            $data = 'SELECT ' . $select . ' FROM (' . $sql . ') AS somerandxyz WHERE ' . $condition;
        } else {
            $data = 'SELECT * FROM (' . $sql . ') AS somerandxyz WHERE ' . $condition;
        }

        return [
            'data' => $data,
            'count' => $total,
        ];
    }

    /**
     * @return array{string, string|null, string, string} property, type, operator and value of a grid filter
     *
     * @throws InvalidReportFilterException
     */
    private function normalizeFilter(mixed $filter): array
    {
        $property = is_array($filter) ? ($filter['property'] ?? null) : null;
        $operator = is_array($filter) ? ($filter['operator'] ?? null) : null;

        if (!is_string($property) || !is_string($operator) || !isset(self::FILTER_OPERATORS[$operator])) {
            throw new InvalidReportFilterException('Invalid report filter');
        }

        $type = $filter['type'] ?? null;

        return [$property, is_string($type) ? $type : null, $operator, $this->normalizeFilterValue($filter['value'] ?? null)];
    }

    /**
     * @throws InvalidReportFilterException
     */
    private function parseFilterDate(string $value): DateTimeImmutable
    {
        try {
            $date = new DateTimeImmutable($value);
        } catch (Exception) {
            throw new InvalidReportFilterException('Invalid report filter date');
        }

        // an overflowing date like 02/30/2026 is only a warning of the parser
        if ($value === '' || DateTimeImmutable::getLastErrors()) {
            throw new InvalidReportFilterException('Invalid report filter date');
        }

        return $date;
    }

    /**
     * Builds the condition of a date filter. "eq" means the whole calendar day. The type of the column is not known
     * here, it can hold unix timestamps or be a DATE, DATETIME or date string, so the type is decided per value.
     */
    private function buildDateCondition(Connection $db, string $column, string $operator, DateTimeImmutable $date): string
    {
        if ($operator === 'eq' || $operator === '=') {
            $startOfDay = $date->setTime(0, 0);

            return $this->compareDate($db, $column, '>=', $startOfDay) . ' AND ' . $this->compareDate($db, $column, '<', $startOfDay->modify('+1 day'));
        }

        return $this->compareDate($db, $column, self::FILTER_OPERATORS[$operator], $date);
    }

    private function compareDate(Connection $db, string $column, string $comparison, DateTimeImmutable $date): string
    {
        return 'IF(CAST(' . $column . " AS CHAR) REGEXP '^[0-9]+$'"
            . ', CAST(' . $column . ' AS SIGNED) ' . $comparison . ' ' . $date->getTimestamp()
            . ', CAST(' . $column . ' AS DATETIME) ' . $comparison . ' ' . $db->quote($date->format('Y-m-d H:i:s'))
            . ')';
    }

    /**
     * @throws InvalidReportFilterException
     */
    private function normalizeFilterValue(mixed $value): string
    {
        if (!is_scalar($value)) {
            throw new InvalidReportFilterException('Invalid report filter value');
        }

        return is_bool($value) ? (string) (int) $value : (string) $value;
    }

    public function getAvailableOptions(array $filters, string $field, array $drillDownFilters): array
    {
        $db = $this->getDb();
        $baseQuery = $this->getBaseQuery($filters, [$field], false, $drillDownFilters);
        $sql = $baseQuery['data'] . ' GROUP BY ' . $db->quoteIdentifier($field);
        $data = $this->runReadOnly(static fn (Connection $db): array => $db->fetchAllAssociative($sql));

        $filteredData = [];
        foreach ($data as $d) {
            if ($d[$field] !== null && $d[$field] !== '') {
                $filteredData[] = ['name' => $d[$field], 'value' => $d[$field]];
            }
        }

        return [
            'data' => array_merge(
                [
                    // no value means no drill-down filter on this column
                    ['name' => 'all', 'value' => null],
                ],
                $filteredData
            ),
        ];
    }
}
