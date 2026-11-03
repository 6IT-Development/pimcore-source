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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Result as DriverResult;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Pimcore\Bundle\ApplicationLoggerBundle\Controller\LogController;
use Pimcore\Bundle\ApplicationLoggerBundle\Request\LogGridRequest;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validation;

class LogControllerTest extends TestCase
{
    /** @var array<array{0: string, 1: array}> */
    private array $queries = [];

    public function testFiltersTheDatesInUtc(): void
    {
        $this->show(new LogGridRequest(timezone: 'Europe/Budapest', fromDate: '2026-07-01T00:00:00', fromTime: '2026-07-01T10:30:15'));

        [$sql, $params] = $this->queries[1];

        $this->assertStringContainsString('timestamp > :fromDate', $sql);
        $this->assertSame('2026-07-01 08:30:15', $params['fromDate']->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $params['fromDate']->getTimezone()->getName());
    }

    public function testIgnoresDatesThatDoNotExist(): void
    {
        $this->show(new LogGridRequest(fromDate: '2026-02-31T00:00:00'));

        $this->assertStringNotContainsString('fromDate', $this->queries[1][0]);
    }

    public function testFiltersThePidExactly(): void
    {
        $this->show(new LogGridRequest(pid: 12));

        $this->assertStringContainsString('pid = :', $this->queries[1][0]);
        $this->assertContains(12, $this->queries[1][1]);
    }

    public function testSearchesWildcardsInTheMessageLiterally(): void
    {
        $this->show(new LogGridRequest(message: '100%_'));

        $this->assertContains('%100\%\_%', $this->queries[1][1]);
    }

    public function testSortsByKnownColumns(): void
    {
        $this->show(new LogGridRequest(sort: '[{"property": "pid", "direction": "desc"}]'));

        $this->assertStringContainsString('ORDER BY `pid` DESC', $this->queries[1][0]);
    }

    public function testIgnoresUnknownSortColumns(): void
    {
        $this->show(new LogGridRequest(sort: '[{"property": "unknown", "direction": "ASC"}]'));

        $this->assertStringContainsString('ORDER BY id DESC', $this->queries[1][0]);
        $this->assertStringNotContainsString('unknown', $this->queries[1][0]);
    }

    public function testCountsWithoutSortingAndPaging(): void
    {
        $response = $this->show(new LogGridRequest(start: 5, limit: 2));

        $this->assertSame(4, json_decode($response->getContent(), true)['p_totalCount']);
        $this->assertStringNotContainsString('ORDER BY', $this->queries[0][0]);
        $this->assertStringNotContainsString('LIMIT', $this->queries[0][0]);
        $this->assertStringContainsString('LIMIT 2 OFFSET 5', $this->queries[1][0]);
    }

    /**
     * @dataProvider invalidRequestProvider
     */
    public function testValidatesThePagingParameters(LogGridRequest $request): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

        $this->assertGreaterThan(0, count($validator->validate($request)));
    }

    public static function invalidRequestProvider(): array
    {
        return [
            'negative start' => [new LogGridRequest(start: -1)],
            'limit too small' => [new LogGridRequest(limit: 0)],
            'limit too large' => [new LogGridRequest(limit: 1001)],
        ];
    }

    private function show(LogGridRequest $params): JsonResponse
    {
        $this->queries = [];

        $db = $this->createMock(Connection::class);
        $db->method('createQueryBuilder')->willReturnCallback(static fn (): QueryBuilder => new QueryBuilder($db));
        $db->method('getExpressionBuilder')->willReturnCallback(static fn (): ExpressionBuilder => new ExpressionBuilder($db));
        $db->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
        $db->method('quoteIdentifier')->willReturnCallback(static fn (string $name): string => '`' . $name . '`');
        $db->method('executeQuery')->willReturnCallback(function (string $sql, array $queryParams = []) use ($db): Result {
            $this->queries[] = [$sql, $queryParams];

            $result = $this->createStub(DriverResult::class);
            $result->method('fetchOne')->willReturn(4);
            $result->method('fetchAllAssociative')->willReturn([]);

            return new Result($result, $db);
        });

        return $this->createController()->showAction(new Request(), $db, $params);
    }

    private function createController(): LogController
    {
        return new class() extends LogController {
            public function __construct()
            {
            }

            public function jsonResponse(mixed $data, int $status = 200, array $headers = [], array $context = [], bool $usePimcoreSerializer = true): JsonResponse
            {
                return new JsonResponse($data, $status, $headers);
            }
        };
    }
}
