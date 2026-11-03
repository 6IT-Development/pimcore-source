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

namespace Pimcore\Tests\Unit\Install;

use Pimcore\Bundle\InstallBundle\Sql\SqlStatementSplitter;
use Pimcore\Tests\Support\Test\TestCase;

class SqlStatementSplitterTest extends TestCase
{
    public function testSplitsAtDelimitersOutsideOfStringsAndIdentifiers(): void
    {
        $sql = "INSERT INTO a VALUES ('x;\ny', 'it''s', 'b\\'c;', \"q;\", `we;ird`);\nSELECT 1;";

        $this->assertSame([
            1 => "INSERT INTO a VALUES ('x;\ny', 'it''s', 'b\\'c;', \"q;\", `we;ird`)",
            2 => 'SELECT 1',
        ], iterator_to_array(SqlStatementSplitter::splitString($sql)));
    }

    public function testSplitsCrlfScriptsAndStatementsOnOneLine(): void
    {
        $this->assertSame(
            [1 => 'SELECT 1', 2 => 'SELECT 2', 3 => 'SELECT 3'],
            iterator_to_array(SqlStatementSplitter::splitString("SELECT 1;\r\nSELECT 2; SELECT 3"))
        );
    }

    public function testKeepsCommentsInsideStringsUntouched(): void
    {
        $this->assertSame(
            [1 => "INSERT INTO a VALUES ('a /* b */ c', '-- d', '# e')"],
            iterator_to_array(SqlStatementSplitter::splitString("INSERT INTO a VALUES ('a /* b */ c', '-- d', '# e');"))
        );
    }

    public function testIgnoresDelimitersInCommentsAndSkipsCommentOnlyStatements(): void
    {
        $sql = "-- a; b\n# c; d\n/* e; *f* */;\nSELECT 1 - -1, 2--3;\n";

        $this->assertSame(
            [1 => 'SELECT 1 - -1, 2--3'],
            iterator_to_array(SqlStatementSplitter::splitString($sql))
        );
    }

    public function testKeepsExecutableComments(): void
    {
        $this->assertSame(
            [1 => '/*!40101 SET NAMES utf8mb4 */', 2 => 'SELECT /*+ MAX_EXECUTION_TIME(1) */ 1'],
            iterator_to_array(SqlStatementSplitter::splitString("/*!40101 SET NAMES utf8mb4 */;\nSELECT /*+ MAX_EXECUTION_TIME(1) */ 1;"))
        );
    }

    public function testSupportsDelimiterBlocks(): void
    {
        $sql = "DELIMITER ;;\nCREATE TRIGGER t BEFORE INSERT ON a FOR EACH ROW BEGIN SET @x = 1; SET @y = 2; END;;\nDELIMITER ;\nSELECT 1;";

        $this->assertSame([
            1 => 'CREATE TRIGGER t BEFORE INSERT ON a FOR EACH ROW BEGIN SET @x = 1; SET @y = 2; END',
            2 => 'SELECT 1',
        ], iterator_to_array(SqlStatementSplitter::splitString($sql)));
    }
}
