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

namespace Pimcore\Tests\Unit\Config;

use Exception;
use Pimcore\Config\LocationAwareConfigRepository;
use Pimcore\Tests\Support\Test\TestCase;

class LocationAwareConfigRepositoryTest extends TestCase
{
    private const array CONTAINER_CONFIG = ['foo' => ['a' => 1], 'bar' => ['b' => 2]];

    private function createRepository(?array $readTarget): LocationAwareConfigRepository
    {
        $storageConfig = [
            LocationAwareConfigRepository::WRITE_TARGET => [
                LocationAwareConfigRepository::TYPE => LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG,
                LocationAwareConfigRepository::OPTIONS => [LocationAwareConfigRepository::DIRECTORY => '/tmp/config'],
            ],
        ];
        if ($readTarget !== null) {
            $storageConfig[LocationAwareConfigRepository::READ_TARGET] = $readTarget;
        }

        return new LocationAwareConfigRepository(self::CONTAINER_CONFIG, 'pimcore_test_scope', $storageConfig);
    }

    /**
     * C-04: the key listing follows the same read-target rule as loadConfigByKey().
     */
    public function testSymfonyConfigReadTargetListsContainerKeys(): void
    {
        $repository = $this->createRepository([
            LocationAwareConfigRepository::TYPE => LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG,
        ]);

        $this->assertSame(['foo', 'bar'], $repository->fetchAllKeysByReadTargets());
    }

    public function testDisabledReadTargetListsNoKeys(): void
    {
        $repository = $this->createRepository([
            LocationAwareConfigRepository::TYPE => LocationAwareConfigRepository::LOCATION_DISABLED,
        ]);

        $this->assertSame([], $repository->fetchAllKeysByReadTargets());
    }

    public function testInvalidReadTargetThrows(): void
    {
        $repository = $this->createRepository([LocationAwareConfigRepository::TYPE => 'foo']);

        $this->expectException(Exception::class);
        $repository->fetchAllKeysByReadTargets();
    }
}
