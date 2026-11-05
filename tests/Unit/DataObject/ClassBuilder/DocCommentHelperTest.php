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

namespace Pimcore\Tests\Unit\DataObject\ClassBuilder;

use Pimcore\DataObject\ClassBuilder\DocCommentHelper;
use Pimcore\Tests\Support\Test\TestCase;

class DocCommentHelperTest extends TestCase
{
    /**
     * C-06: user text placed in a generated doc comment cannot close the comment.
     *
     * @dataProvider provideCommentBreakouts
     */
    public function testTextStaysInsideDocComment(string $text): void
    {
        $code = "<?php\n\n/**\n * " . DocCommentHelper::escapeText($text) . "\n */\n\nnamespace Pimcore\\Model\\DataObject;\n";

        $tokens = array_values(array_filter(
            token_get_all($code),
            static fn ($token) => !is_array($token) || !in_array($token[0], [T_OPEN_TAG, T_WHITESPACE], true)
        ));

        $this->assertIsArray($tokens[0]);
        $this->assertSame(T_DOC_COMMENT, $tokens[0][0]);
        $this->assertSame(T_NAMESPACE, $tokens[1][0]);
        // doc comment, namespace, qualified name, ';' - nothing injected in between
        $this->assertCount(4, $tokens);
    }

    public static function provideCommentBreakouts(): array
    {
        return [
            'stripping creates a terminator' => ["**// namespace X; system(\$_GET['c']); /*"],
            'two terminators' => ['*/*/'],
            'terminator inside opener' => ['/***/'],
            'plain terminator' => ['foo */ bar'],
            'line breaks' => ["first\n*/ second\r\nthird\r*/"],
        ];
    }

    public function testLinesAreContinued(): void
    {
        $this->assertSame("a\n * b\n * c", DocCommentHelper::escapeText("a\r\nb\nc"));
        $this->assertSame('a *\/ b', DocCommentHelper::escapeText('a */ b'));
    }
}
