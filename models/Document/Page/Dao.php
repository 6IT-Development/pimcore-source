<?php

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

namespace Pimcore\Model\Document\Page;

use Exception;
use Pimcore\Model;
use Pimcore\Model\Document\Page;

/**
 * @internal
 *
 * @property Page $model
 */
class Dao extends Model\Document\PageSnippet\Dao
{
    /**
     * Get the data for the object by the given id, or by the id which is set in the object
     *
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(?int $id = null): void
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        // documents.id comes last: without a type row the NULL documents_page.id would win and the save would insert again
        $data = $this->db->fetchAssociative("SELECT documents.*, documents_page.*, tree_locks.locked, documents.id FROM documents
            LEFT JOIN documents_page ON documents.id = documents_page.id
            LEFT JOIN tree_locks ON documents.id = tree_locks.id AND tree_locks.type = 'document'
                WHERE documents.id = ?", [$this->model->getId()]);

        if ($data) {
            $data['published'] = (bool)$data['published'];
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException('Page with the ID ' . $this->model->getId() . " doesn't exists");
        }
    }

    /**
     * The pretty URL and the real full path of the page as currently stored, or null if the page has no type row
     *
     * @return array{prettyUrl: string|null, fullPath: string}|null
     */
    public function getStoredPrettyUrlAndFullPath(): ?array
    {
        $data = $this->db->fetchAssociative(
            'SELECT documents_page.prettyUrl, CONCAT(documents.path, documents.`key`) AS fullPath FROM documents
            INNER JOIN documents_page ON documents.id = documents_page.id
            WHERE documents.id = ?',
            [$this->model->getId()]
        );

        return $data ?: null;
    }

    /**
     * Ids of the other pages using the given pretty URL, compared like the routing lookups do (collation of the column)
     *
     * @return int[]
     */
    public function getOtherPageIdsByPrettyUrl(string $prettyUrl): array
    {
        return array_map('intval', $this->db->fetchFirstColumn(
            'SELECT id FROM documents_page WHERE prettyUrl = ? AND id != ? ORDER BY id',
            [$prettyUrl, (int) $this->model->getId()]
        ));
    }

    /**
     * Id of another document whose real full path is the given path, or null
     */
    public function getOtherDocumentIdByRealFullPath(string $fullPath): ?int
    {
        $params = $this->extractKeyAndPath($fullPath);
        $params['id'] = (int) $this->model->getId();
        $id = $this->db->fetchOne(
            'SELECT id FROM documents WHERE `path` = BINARY :path AND `key` = BINARY :key AND id != :id',
            $params
        );

        return $id === false ? null : (int) $id;
    }

    public function create(): void
    {
        parent::create();

        $this->db->insert('documents_page', [
            'id' => $this->model->getId(),
        ]);
    }

    /**
     * @throws Exception
     */
    public function delete(): void
    {
        $this->deleteAllProperties();

        parent::delete();
    }
}
