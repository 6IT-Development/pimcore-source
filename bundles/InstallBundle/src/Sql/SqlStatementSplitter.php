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

namespace Pimcore\Bundle\InstallBundle\Sql;

use Generator;
use RuntimeException;

/**
 * Splits an SQL script into single statements like the mysql client does: delimiters inside
 * quoted strings, quoted identifiers and comments are ignored and `DELIMITER` lines are supported.
 *
 * Comments are kept in the statements (the server ignores them, executable comments like
 * `/*!40101 ... *\/` are evaluated by the server). Statements that consist of plain comments only
 * are skipped.
 *
 * @internal
 */
final class SqlStatementSplitter
{
    private const int STATE_NONE = 0;

    private const int STATE_QUOTED = 1;

    private const int STATE_BLOCK_COMMENT = 2;

    /**
     * @return Generator<int, string> Statements without the trailing delimiter, keyed by their 1-based number
     */
    public static function splitString(string $sql): Generator
    {
        return self::split(self::linesOfString($sql));
    }

    /**
     * Reads the file line by line, so large dumps are not loaded into memory at once
     *
     * @param string $file File path or stream URL (e.g. compress.zlib://...)
     *
     * @return Generator<int, string> Statements without the trailing delimiter, keyed by their 1-based number
     */
    public static function splitFile(string $file): Generator
    {
        return self::split(self::linesOfFile($file));
    }

    /**
     * @param iterable<string> $lines Lines of the script including their line breaks
     *
     * @return Generator<int, string> Statements without the trailing delimiter, keyed by their 1-based number
     */
    public static function split(iterable $lines): Generator
    {
        $delimiter = ';';
        $buffer = '';
        $hasContent = false;
        $state = self::STATE_NONE;
        $quote = '';
        $number = 0;

        foreach ($lines as $line) {
            if ($state === self::STATE_NONE && !$hasContent
                && preg_match('/^\s*DELIMITER\s+(\S+)/i', $line, $matches)) {
                $delimiter = $matches[1];
                $buffer = '';

                continue;
            }

            $length = strlen($line);
            $i = 0;

            while ($i < $length) {
                if ($state === self::STATE_QUOTED) {
                    // backticks have no backslash escapes
                    $stop = $quote === '`' ? $quote : $quote . '\\';
                    $chunkLength = strcspn($line, $stop, $i);
                    $buffer .= substr($line, $i, $chunkLength);
                    $i += $chunkLength;

                    if ($i >= $length) {
                        break;
                    }

                    if ($line[$i] === '\\') {
                        $buffer .= substr($line, $i, 2);
                        $i += 2;
                    } elseif (($line[$i + 1] ?? '') === $quote) {
                        // doubled quote inside the string
                        $buffer .= $quote . $quote;
                        $i += 2;
                    } else {
                        $buffer .= $quote;
                        $i++;
                        $state = self::STATE_NONE;
                    }

                    continue;
                }

                if ($state === self::STATE_BLOCK_COMMENT) {
                    $end = strpos($line, '*/', $i);
                    if ($end === false) {
                        $buffer .= substr($line, $i);

                        break;
                    }

                    $buffer .= substr($line, $i, $end + 2 - $i);
                    $i = $end + 2;
                    $state = self::STATE_NONE;

                    continue;
                }

                $chunkLength = strcspn($line, '\'"`#-/' . $delimiter[0], $i);
                $chunk = substr($line, $i, $chunkLength);
                $buffer .= $chunk;
                $hasContent = $hasContent || trim($chunk) !== '';
                $i += $chunkLength;

                if ($i >= $length) {
                    break;
                }

                $char = $line[$i];

                if (substr_compare($line, $delimiter, $i, strlen($delimiter)) === 0) {
                    if ($hasContent) {
                        yield ++$number => trim($buffer);
                    }

                    $buffer = '';
                    $hasContent = false;
                    $i += strlen($delimiter);

                    continue;
                }

                if ($char === '\'' || $char === '"' || $char === '`') {
                    $state = self::STATE_QUOTED;
                    $quote = $char;
                    $buffer .= $char;
                    $hasContent = true;
                    $i++;

                    continue;
                }

                if ($char === '#' || ($char === '-' && self::isDashComment($line, $i))) {
                    // line comment: keep the rest of the line
                    $buffer .= substr($line, $i);

                    break;
                }

                if ($char === '/' && ($line[$i + 1] ?? '') === '*') {
                    // executable comments (/*! ... */, optimizer hints /*+ ... */) are evaluated by the server
                    $next = $line[$i + 2] ?? '';
                    $hasContent = $hasContent || $next === '!' || $next === '+';
                    $state = self::STATE_BLOCK_COMMENT;
                    $buffer .= '/*';
                    $i += 2;

                    continue;
                }

                $buffer .= $char;
                $hasContent = true;
                $i++;
            }
        }

        if ($hasContent) {
            yield ++$number => trim($buffer);
        }
    }

    /**
     * `--` starts a comment only if it is followed by whitespace, a control character or the end of the line
     */
    private static function isDashComment(string $line, int $i): bool
    {
        if (($line[$i + 1] ?? '') !== '-') {
            return false;
        }

        $next = $line[$i + 2] ?? '';

        return $next === '' || ord($next) <= 32;
    }

    /**
     * @return Generator<int, string>
     */
    private static function linesOfFile(string $file): Generator
    {
        $handle = @fopen($file, 'rb');
        if ($handle === false) {
            throw new RuntimeException(sprintf('Unable to open SQL file %s', $file));
        }

        try {
            while (($line = fgets($handle)) !== false) {
                yield $line;
            }

            if (!feof($handle)) {
                throw new RuntimeException(sprintf('Unable to read SQL file %s', $file));
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return Generator<int, string>
     */
    private static function linesOfString(string $sql): Generator
    {
        $offset = 0;
        $length = strlen($sql);

        while ($offset < $length) {
            $end = strpos($sql, "\n", $offset);
            if ($end === false) {
                yield substr($sql, $offset);

                return;
            }

            yield substr($sql, $offset, $end + 1 - $offset);
            $offset = $end + 1;
        }
    }
}
