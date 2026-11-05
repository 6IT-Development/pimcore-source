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

namespace Pimcore\Tool;

use Doctrine\DBAL\Connection;
use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Event\SystemEvents;
use Pimcore\Model\Tool\TmpStore;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class MaintenanceModeHelper implements MaintenanceModeHelperInterface
{
    protected const string ENTRY_ID = 'maintenance_mode';

    /**
     * The MaintenancePageListener asks on every request, even for full page cache hits. With APCu the entry is kept
     * per server for this many seconds, so activating or deactivating from another process (e.g. the console)
     * takes effect after at most this delay.
     */
    private const int LOCAL_CACHE_TTL = 5;

    private const string LOCAL_CACHE_KEY = 'pimcore_maintenance_mode_entry';

    public function __construct(protected RequestStack $requestStack, protected Connection $db)
    {
    }

    public function activate(string $sessionId): void
    {
        if (empty($sessionId)) {
            $sessionId = $this->requestStack->getSession()->getId();
        }

        if (empty($sessionId)) {
            throw new InvalidArgumentException('Pass sessionId to activate the maintenance mode');
        }

        $this->addEntry($sessionId);

        Pimcore::getEventDispatcher()->dispatch(new GenericEvent(), SystemEvents::MAINTENANCE_MODE_ACTIVATE);
    }

    public function deactivate(): void
    {
        $this->removeEntry();

        Pimcore::getEventDispatcher()->dispatch(new GenericEvent(), SystemEvents::MAINTENANCE_MODE_DEACTIVATE);
    }

    public function isActive(?string $matchSessionId = null): bool
    {
        $useLocalCache = self::isLocalCacheAvailable();
        $maintenanceModeEntry = $useLocalCache ? apcu_fetch(self::LOCAL_CACHE_KEY, $cached) : null;

        if (!$useLocalCache || !$cached) {
            try {
                if (!$this->db->isConnected()) {
                    $this->db->connect();
                }
            } catch (Exception) {
                return false;
            }

            $maintenanceModeEntry = $this->getEntry();

            if ($useLocalCache) {
                // an empty string for "not active", false can't be told apart from a miss
                apcu_store(self::LOCAL_CACHE_KEY, (string) $maintenanceModeEntry, self::LOCAL_CACHE_TTL);
            }
        }

        if ($maintenanceModeEntry) {
            if ($matchSessionId === null || $matchSessionId !== $maintenanceModeEntry) {
                return true;
            }
        }

        return false;
    }

    protected function addEntry(string $sessionId): void
    {
        // set, not add: activating again must store the new session id
        TmpStore::set(self::ENTRY_ID, $sessionId);
        self::clearLocalCache();
    }

    protected function getEntry(): ?string
    {
        try {
            $tmpStore = TmpStore::get(self::ENTRY_ID);
        } catch (Exception) {
            //nothing to log as the tmp doesn't exist
            return null;
        }

        return $tmpStore instanceof TmpStore ? $tmpStore->getData() : null;
    }

    protected function removeEntry(): void
    {
        try {
            TmpStore::delete(self::ENTRY_ID);
        } catch (Exception) {
            //nothing to log as the tmp doesn't exist
        }

        self::clearLocalCache();
    }

    private static function isLocalCacheAvailable(): bool
    {
        return function_exists('apcu_enabled') && apcu_enabled();
    }

    private static function clearLocalCache(): void
    {
        if (self::isLocalCacheAvailable()) {
            apcu_delete(self::LOCAL_CACHE_KEY);
        }
    }
}
