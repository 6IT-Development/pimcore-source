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

namespace Pimcore\Translation;

use Pimcore\Model\Translation;
use Psr\Log\LoggerInterface;
use Throwable;

class TranslationEntriesDumper
{
    /**
     * Keyed by domain and key, the same key can be missing in several domains in one request
     *
     * @var array<string, Translation>
     */
    private static array $translations = [];

    public function __construct(private readonly ?LoggerInterface $logger = null)
    {
    }

    public static function addToSaveQueue(Translation $translation): void
    {
        self::$translations[self::getQueueKey($translation)] = $translation;
    }

    /**
     * Drops a queued auto-create of the key, e.g. because the key was deleted after it was queued
     */
    public static function removeFromSaveQueue(Translation $translation): void
    {
        unset(self::$translations[self::getQueueKey($translation)]);
    }

    /**
     * The domain is a plain identifier (Translation::DOMAIN_PATTERN), so the `|` separator keeps the pairs apart
     */
    private static function getQueueKey(Translation $translation): string
    {
        return $translation->getDomain() . '|' . $translation->getKey();
    }

    /**
     * Runs at kernel.terminate, after the response was sent: a failing entry is logged and dropped, it must neither
     * stop the other entries nor stay in the queue of a long-running process and fail again on every request
     */
    public function dumpToDb(): void
    {
        $translations = self::$translations;
        self::$translations = [];

        foreach ($translations as $translation) {
            try {
                // the queued objects are snapshots taken when the key was missing in the catalogue,
                // texts saved in the meantime (or not loaded due to a DB error) must not be overwritten
                $translation->saveMissingLanguages();
            } catch (Throwable $e) {
                $this->logger?->error('Could not save the missing translation "{key}" of domain "{domain}": {message}', [
                    'key' => $translation->getKey(),
                    'domain' => $translation->getDomain(),
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ]);
            }
        }
    }
}
