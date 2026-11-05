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

namespace Pimcore\Cache\Core;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;

/**
 * @internal
 */
class WriteLock implements LoggerAwareInterface, ResetInterface
{
    use LoggerAwareTrait;

    protected bool $enabled = true;

    protected TagAwareAdapterInterface $itemPool;

    protected string $cacheKey = 'system_cache_write_lock';

    protected int $lifetime = 30;

    /**
     * Token identifying the locks of this process (generated on the first lock)
     *
     * The stored lock value maps the tokens of all processes holding the lock to the time they acquired or renewed it,
     * so removeLock() only removes the entry of this process and never the lock of another process.
     */
    protected ?string $token = null;

    /**
     * Time this process acquired or last renewed its lock, null when this process holds no lock
     */
    protected ?int $timestamp = null;

    public function __construct(TagAwareAdapterInterface $itemPool)
    {
        $this->itemPool = $itemPool;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Set a write lock (prevents items being written to cache)
     *
     * A lock of another process is never adopted: this process adds its own entry to the stored lock. An existing own
     * lock is renewed once half of its lifetime has passed (or always with $force, e.g. after the pool was cleared), so
     * it does not expire while a long clear is running.
     *
     * The stored value is read, modified and written back without an atomic operation of the pool; two processes
     * locking at the same moment can overwrite each other's entry.
     *
     * @return bool true when the lock was written to the storage
     */
    public function lock(bool $force = false): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $now = time();

        if (!$force && $this->timestamp !== null && $this->timestamp > $now - intdiv($this->lifetime, 2)) {
            return false;
        }

        $this->token ??= bin2hex(random_bytes(8));
        $this->timestamp = $now;

        $this->logger->debug(
            'Setting write lock {token} with timestamp {timestamp}',
            ['token' => $this->token, 'timestamp' => $this->timestamp]
        );

        $item = $this->itemPool->getItem($this->cacheKey);
        $holders = $this->getValidHolders($item->isHit() ? $item->get() : null, $now);
        $holders[$this->token] = $now;

        $item->set($holders);
        $item->expiresAfter($this->lifetime);

        return $this->itemPool->save($item);
    }

    /**
     * Check if a write lock is active - either set by this process or by any other process sharing the storage
     *
     * The own lock counts only until its lifetime has passed, as the storage item expires then as well. Otherwise the
     * storage is read, so a lock set by another process after this one started is seen.
     */
    public function hasLock(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $now = time();

        if ($this->timestamp !== null && $this->isLockValid($this->timestamp)) {
            return true;
        }

        $item = $this->itemPool->getItem($this->cacheKey);

        return $item->isHit() && $this->getValidHolders($item->get(), $now) !== [];
    }

    protected function isLockValid(int $lockTime): bool
    {
        return $lockTime > (time() - $this->lifetime);
    }

    /**
     * Returns the unexpired entries of a stored lock value as token => timestamp
     *
     * Values of another shape (corrupted entry, other writer) are ignored. A plain timestamp as written by older
     * versions counts as a lock of an unknown process.
     *
     * @return array<string, int>
     */
    protected function getValidHolders(mixed $value, int $now): array
    {
        if (is_int($value)) {
            $value = ['legacy' => $value];
        }

        if (!is_array($value)) {
            return [];
        }

        $holders = [];
        foreach ($value as $token => $timestamp) {
            if (is_string($token) && is_int($timestamp) && $timestamp > $now - $this->lifetime) {
                $holders[$token] = $timestamp;
            }
        }

        return $holders;
    }

    /**
     * Remove the write lock of this process from instance and from cache
     *
     * Entries of other processes stay; the storage item is deleted only when no other process holds the lock.
     */
    public function removeLock(): bool
    {
        if (!$this->enabled || $this->timestamp === null || $this->token === null) {
            return true;
        }

        $this->timestamp = null;

        $item = $this->itemPool->getItem($this->cacheKey);
        if (!$item->isHit()) {
            return true;
        }

        $holders = $this->getValidHolders($item->get(), time());
        if (!isset($holders[$this->token])) {
            $this->logger->debug('Not removing write lock as it does not contain the lock {token} of this process', [
                'token' => $this->token,
            ]);

            return false;
        }

        unset($holders[$this->token]);

        $this->logger->debug('Removing write lock {token}', ['token' => $this->token]);

        if ($holders === []) {
            return $this->itemPool->deleteItem($this->cacheKey);
        }

        $item->set($holders);
        $item->expiresAfter($this->lifetime);

        return $this->itemPool->save($item);
    }

    /**
     * Removes the lock of this process between messages of a messenger worker or requests of a worker runtime
     * (kernel.reset)
     *
     * @internal
     */
    public function reset(): void
    {
        try {
            $this->removeLock();
        } catch (Throwable $e) {
            // an exception here would stop the worker; the lock expires after its lifetime
            $this->logger->error('Failed to remove the write lock on reset: {error}', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
        } finally {
            $this->timestamp = null;
        }
    }
}
