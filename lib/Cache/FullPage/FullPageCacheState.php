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

namespace Pimcore\Cache\FullPage;

use Symfony\Contracts\Service\ResetInterface;

/**
 * Full page cache settings of the current request. Code that renders the page (controllers, editables, ...) disables
 * the cache or changes its lifetime here, and the FullPageCacheListener reads it when the response is stored.
 *
 * The state belongs to one request and is reset before the next one (kernel.reset).
 */
final class FullPageCacheState implements ResetInterface
{
    public private(set) bool $enabled = true;

    /**
     * Reason of the last disable() call that gave one, sent as X-Pimcore-Output-Cache-Disable-Reason header
     */
    public private(set) ?string $disableReason = null;

    /**
     * Lifetime in seconds, null for the cache default
     */
    public private(set) ?int $lifetime = null;

    /**
     * Whether Cache-Control and Expires headers are added to a cached response with a lifetime
     */
    public private(set) bool $expireHeaderEnabled = true;

    public function disable(?string $reason = null): void
    {
        if ($reason) {
            $this->disableReason = $reason;
        }

        $this->enabled = false;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function setLifetime(?int $lifetime): void
    {
        $this->lifetime = $lifetime;
    }

    public function disableExpireHeader(): void
    {
        $this->expireHeaderEnabled = false;
    }

    public function enableExpireHeader(): void
    {
        $this->expireHeaderEnabled = true;
    }

    public function reset(): void
    {
        $this->enabled = true;
        $this->disableReason = null;
        $this->lifetime = null;
        $this->expireHeaderEnabled = true;
    }
}
