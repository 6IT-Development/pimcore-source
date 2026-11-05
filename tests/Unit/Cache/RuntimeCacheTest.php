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

namespace Pimcore\Tests\Unit\Cache;

use Pimcore\Cache\RuntimeCache;
use Pimcore\Tests\Support\Test\TestCase;

class RuntimeCacheTest extends TestCase
{
    public function testClearKeepsItems(): void
    {
        RuntimeCache::set('runtime_cache_test_kept', 'kept');
        RuntimeCache::set('runtime_cache_test_dropped', 'dropped');

        RuntimeCache::clear(['runtime_cache_test_kept']);

        $this->assertTrue(RuntimeCache::isRegistered('runtime_cache_test_kept'));
        $this->assertSame('kept', RuntimeCache::get('runtime_cache_test_kept'));
        $this->assertFalse(RuntimeCache::isRegistered('runtime_cache_test_dropped'));
    }

    public function testClearKeepsItemsWhileCachingIsDisabled(): void
    {
        RuntimeCache::set('runtime_cache_test_kept', 'kept');
        RuntimeCache::set('runtime_cache_test_dropped', 'dropped');

        RuntimeCache::disable();

        try {
            RuntimeCache::clear(['runtime_cache_test_kept']);

            $this->assertTrue(RuntimeCache::isRegistered('runtime_cache_test_kept'));
            $this->assertFalse(RuntimeCache::isRegistered('runtime_cache_test_dropped'));

            // new items are still not cached while disabled
            RuntimeCache::set('runtime_cache_test_new', 'new');
            $this->assertFalse(RuntimeCache::isRegistered('runtime_cache_test_new'));
        } finally {
            RuntimeCache::enable();
        }
    }
}
