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

use Monolog\Handler\TestHandler;
use Pimcore\Bundle\ApplicationLoggerBundle\ApplicationLogger;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use RuntimeException;
use Stringable;

class ApplicationLoggerTest extends TestCase
{
    private ApplicationLogger $logger;

    private TestHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new TestHandler();
        $this->logger = new ApplicationLogger();
        $this->logger->addWriter($this->handler);
    }

    public function testAcceptsStringableMessages(): void
    {
        $this->logger->error(new class() implements Stringable {
            public function __toString(): string
            {
                return 'stringable message';
            }
        });

        $this->assertTrue($this->handler->hasErrorThatContains('stringable message'));
    }

    public function testResolvesSourceForCallersWithoutFileAndLine(): void
    {
        array_map($this->logger->info(...), ['called by array_map']);

        $this->assertStringContainsString(__FUNCTION__, $this->handler->getRecords()[0]->context['source']);
    }

    public function testInstanceObjectsOnlyApplyToTheNextCall(): void
    {
        $this->logger->setRelatedObject(5);
        $this->logger->setFileObject('/file');
        $this->logger->info('first');
        $this->logger->info('second');

        [$first, $second] = $this->handler->getRecords();

        $this->assertSame(5, $first->context['relatedObject']);
        $this->assertSame('/file', $first->context['fileObject']);
        $this->assertArrayNotHasKey('relatedObject', $second->context);
        $this->assertArrayNotHasKey('fileObject', $second->context);
    }

    public function testTakesTheTypeOfANumericRelatedObjectFromTheCall(): void
    {
        $this->logger->info('message', ['relatedObject' => 5, 'relatedObjectType' => 'asset']);

        $this->assertSame('asset', $this->handler->getRecords()[0]->context['relatedObjectType']);
    }

    public function testDropsUnsupportedContextValues(): void
    {
        $errorLog = ini_set('error_log', '/dev/null');
        $this->logger->info('message', ['relatedObject' => 'not an id', 'fileObject' => 123]);
        ini_set('error_log', (string)$errorLog);

        $context = $this->handler->getRecords()[0]->context;

        $this->assertArrayNotHasKey('relatedObject', $context);
        $this->assertArrayNotHasKey('fileObject', $context);
    }

    public function testFailingWriterDoesNotAffectTheCallerOrOtherWriters(): void
    {
        $logger = new ApplicationLogger();
        $logger->addWriter(new class() extends AbstractLogger {
            public function log($level, string|Stringable $message, array $context = []): void
            {
                throw new RuntimeException('writer failed');
            }
        });
        $logger->addWriter($this->handler);

        $errorLog = ini_set('error_log', '/dev/null');
        $logger->error('message');
        ini_set('error_log', (string)$errorLog);

        $this->assertTrue($this->handler->hasErrorThatContains('message'));
    }

    public function testUnknownLevelIsReportedToTheCaller(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logger->log('unknown', 'message');
    }
}
