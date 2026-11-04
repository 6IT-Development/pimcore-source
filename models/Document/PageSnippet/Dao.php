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

namespace Pimcore\Model\Document\PageSnippet;

use Doctrine\DBAL\ArrayParameterType;
use Pimcore;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Document\PageSnippet;
use Throwable;

/**
 * @internal
 *
 * @property PageSnippet $model
 */
abstract class Dao extends Model\Document\Dao
{
    use Model\Element\Traits\VersionDaoTrait;

    /**
     * Delete all editables containing the content from the database
     *
     * @param string[] $keepNames names of the rows to keep, e.g. rows that could not be loaded
     */
    public function deleteAllEditables(array $keepNames = []): void
    {
        if (!$keepNames) {
            $this->db->delete('documents_editables', ['documentId' => $this->model->getId()]);

            return;
        }

        $this->db->executeStatement(
            'DELETE FROM documents_editables WHERE documentId = :documentId AND name NOT IN (:keepNames)',
            ['documentId' => $this->model->getId(), 'keepNames' => array_values($keepNames)],
            ['keepNames' => ArrayParameterType::STRING]
        );
    }

    /**
     * Get all editables containing the content from the database
     *
     * A row whose type no longer exists or whose data cannot be read is skipped with a warning instead of failing
     * the whole document; its name is recorded on the document, so that saving it does not delete the row.
     *
     * @return Document\Editable[]
     */
    public function getEditables(): array
    {
        $editablesRaw = $this->db->fetchAllAssociative('SELECT * FROM documents_editables WHERE documentId = ?', [$this->model->getId()]);

        $editables = [];
        $unloadableNames = [];
        $loader = Pimcore::getContainer()->get(Document\Editable\Loader\EditableLoader::class);

        foreach ($editablesRaw as $editableRaw) {
            try {
                /** @var Document\Editable $editable */
                $editable = $loader->build($editableRaw['type']);
                $editable->setName($editableRaw['name']);
                $editable->setDocument($this->model);
                $editable->setDataFromResource($editableRaw['data']);
            } catch (Throwable $e) {
                Logger::warning(sprintf(
                    'Skipping editable "%s" of type "%s" of document %d: %s',
                    $editableRaw['name'],
                    $editableRaw['type'],
                    $this->model->getId(),
                    $e->getMessage()
                ));
                $unloadableNames[] = $editableRaw['name'];

                continue;
            }

            $editables[$editableRaw['name']] = $editable;
        }

        $this->model->setUnloadableEditableNames($unloadableNames);

        return $editables;
    }
}
