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

namespace Pimcore\Bundle\CoreBundle\DataCollector;

use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Version;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * @internal
 */
final class PimcoreDataCollector extends AbstractDataCollector
{
    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $this->data = [
            'version' => Version::getVersion(),
            'revision' => Version::getRevision(),
            'platform_version' => Version::getPlatformVersion(),
            // read the attribute instead of PimcoreContextResolver::getPimcoreContext(), which guesses a missing
            // context and writes it to the request
            'context' => $request->attributes->get(PimcoreContextResolver::ATTRIBUTE_PIMCORE_CONTEXT),
        ];
    }

    public function reset(): void
    {
        $this->data = [];
    }

    public function getName(): string
    {
        return 'pimcore';
    }

    public static function getTemplate(): string
    {
        return '@PimcoreCore/Profiler/data_collector.html.twig';
    }

    // the getters use ?? null: data is empty after reset() and older stored profiles may lack a key

    public function getContext(): ?string
    {
        return $this->data['context'] ?? null;
    }

    public function getVersion(): ?string
    {
        return $this->data['version'] ?? null;
    }

    public function getRevision(): ?string
    {
        return $this->data['revision'] ?? null;
    }

    public function getPlatformVersion(): ?string
    {
        return $this->data['platform_version'] ?? null;
    }
}
