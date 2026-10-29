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

class TranslationEntriesDumper
{
    /**
     * @var Translation[]
     */
    private static array $translations = [];

    public static function addToSaveQueue(Translation $translation): void
    {
        self::$translations[$translation->getKey()] = $translation;
    }

    public function dumpToDb(): void
    {
        foreach (self::$translations as $translation) {
            // the queued objects are snapshots taken when the key was missing in the catalogue,
            // texts saved in the meantime (or not loaded due to a DB error) must not be overwritten
            $translation->saveMissingLanguages();
        }
        self::$translations = [];
    }
}
