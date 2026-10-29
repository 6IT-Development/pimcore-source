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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Moving a root-level tag used to rewrite every slash of the idPath of its subtree (REPLACE('/', …)),
 * this recomputes the idPath of every tag from the parentId chain.
 */
final class Version20261029000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Repair tags.idPath values corrupted by moving root-level tags';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('tags')) {
            return;
        }

        $tags = [];
        foreach ($this->connection->fetchAllAssociative('SELECT id, parentId, idPath, name FROM tags') as $tag) {
            $tags[(int)$tag['id']] = $tag;
        }

        // the (idPath, name) pairs after the repair, to skip updates that would violate the unique index
        $targets = [];
        $updates = [];
        foreach ($tags as $id => $tag) {
            $idPath = $this->computeIdPath($id, $tags);
            if ($idPath === null) {
                $this->warnIf(true, sprintf('Tag %d has a missing parent or a parent cycle, its idPath is left unchanged', $id));
                $idPath = (string)$tag['idPath'];
            }

            $targetKey = $idPath . "\0" . $tag['name'];
            if (isset($targets[$targetKey])) {
                $this->warnIf(true, sprintf('Tag %d would get the same idPath and name as tag %d, its idPath is left unchanged', $id, $targets[$targetKey]));

                continue;
            }
            $targets[$targetKey] = $id;

            if ($idPath !== $tag['idPath']) {
                $updates[$id] = $idPath;
            }
        }

        // two passes: a row may get the current (corrupt) value of a row that is updated later
        foreach (array_keys($updates) as $id) {
            $this->addSql('UPDATE tags SET idPath = ? WHERE id = ?', ['#repair-' . $id, $id]);
        }
        foreach ($updates as $id => $idPath) {
            $this->addSql('UPDATE tags SET idPath = ? WHERE id = ?', [$idPath, $id]);
        }
    }

    public function down(Schema $schema): void
    {
        // do nothing, the old values were corrupt
    }

    /**
     * @param array<int, array<string, mixed>> $tags
     */
    private function computeIdPath(int $id, array $tags): ?string
    {
        $parentIds = [];
        $parentId = (int)$tags[$id]['parentId'];
        while ($parentId) {
            if (!isset($tags[$parentId]) || in_array($parentId, $parentIds, true) || $parentId === $id) {
                return null;
            }
            $parentIds[] = $parentId;
            $parentId = (int)$tags[$parentId]['parentId'];
        }

        return $parentIds ? '/' . implode('/', array_reverse($parentIds)) . '/' : '/';
    }
}
