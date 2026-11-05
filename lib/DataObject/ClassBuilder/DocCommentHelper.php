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

namespace Pimcore\DataObject\ClassBuilder;

/**
 * @internal
 */
final class DocCommentHelper
{
    /**
     * Makes user-provided text safe to be placed inside a generated `/** ... *\/` comment
     *
     * Every comment terminator is escaped in a single pass (`*\/` cannot form a new terminator), instead of removing
     * character sequences, which can create a new terminator from the remaining characters. Line breaks are
     * normalized and continued with $linePrefix, so every line stays inside the comment.
     */
    public static function escapeText(string $text, string $linePrefix = ' * '): string
    {
        $text = str_replace('*/', '*\/', $text);

        return str_replace(["\r\n", "\r", "\n"], "\n" . $linePrefix, $text);
    }
}
