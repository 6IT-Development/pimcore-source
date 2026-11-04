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

namespace Pimcore\Tests\Unit\DataCollector;

use Pimcore\Bundle\CoreBundle\DataCollector\PimcoreDataCollector;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PimcoreDataCollectorTest extends TestCase
{
    /**
     * B-34: the getters return null instead of failing when there is no data.
     */
    public function testGettersReturnNullAfterReset(): void
    {
        $collector = new PimcoreDataCollector();
        $collector->collect($this->createRequest('admin'), new Response());
        $collector->reset();

        $this->assertNull($collector->getContext());
        $this->assertNull($collector->getVersion());
        $this->assertNull($collector->getRevision());
        $this->assertNull($collector->getPlatformVersion());
    }

    public function testCollectsTheContextOfTheRequest(): void
    {
        $collector = new PimcoreDataCollector();
        $collector->collect($this->createRequest('admin'), new Response());

        $this->assertSame('admin', $collector->getContext());
        $this->assertSame('pimcore', $collector->getName());
    }

    public function testDoesNotWriteAMissingContextToTheRequest(): void
    {
        $request = Request::create('/');
        $collector = new PimcoreDataCollector();
        $collector->collect($request, new Response());

        $this->assertNull($collector->getContext());
        $this->assertFalse($request->attributes->has(PimcoreContextResolver::ATTRIBUTE_PIMCORE_CONTEXT));
    }

    public function testTemplateIsProvidedByTheCollector(): void
    {
        $this->assertSame('@PimcoreCore/Profiler/data_collector.html.twig', PimcoreDataCollector::getTemplate());
    }

    private function createRequest(string $context): Request
    {
        $request = Request::create('/');
        $request->attributes->set(PimcoreContextResolver::ATTRIBUTE_PIMCORE_CONTEXT, $context);

        return $request;
    }
}
