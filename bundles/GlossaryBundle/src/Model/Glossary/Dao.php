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

namespace Pimcore\Bundle\GlossaryBundle\Model\Glossary;

use Exception;
use InvalidArgumentException;
use Pimcore\Bundle\GlossaryBundle\Model\Glossary;
use Pimcore\Cache;
use Pimcore\Model\Dao\AbstractDao;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @internal
 *
 * @property Glossary $model
 */
class Dao extends AbstractDao
{
    /**
     * Get the data for the object from database for the given id, or from the ID which is set in the object
     *
     *
     * @throws NotFoundException
     */
    public function getById(?int $id = null): void
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchAssociative('SELECT * FROM glossary WHERE id = ?', [$this->model->getId()]);

        if (!$data) {
            throw new NotFoundException(sprintf('Unable to load glossary item with ID `%s`', $this->model->getId()));
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * @throws InvalidArgumentException if the link uses a scheme that is not allowed
     * @throws Exception
     */
    public function save(): void
    {
        if (!Glossary::isAllowedLink($this->model->getLink())) {
            throw new InvalidArgumentException(sprintf(
                'Glossary links may only use the schemes %s or be relative.',
                implode(', ', Glossary::ALLOWED_LINK_SCHEMES)
            ));
        }

        if (!$this->model->getId()) {
            $this->create();
        } else {
            $this->update();
        }

        // after the write, so a concurrent request cannot cache the old entries again
        Cache::clearTag('glossary');
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete('glossary', ['id' => $this->model->getId()]);

        Cache::clearTag('glossary');
    }

    /**
     * @throws Exception
     */
    public function update(): void
    {
        $this->model->setModificationDate(time());

        $this->db->update('glossary', $this->getTableData(), ['id' => $this->model->getId()]);
    }

    /**
     * Create a new record for the object in database, in one INSERT, so no empty row is left behind when the data
     * cannot be written
     */
    public function create(): void
    {
        $ts = time();
        $this->model->setModificationDate($ts);
        $this->model->setCreationDate($ts);

        $data = $this->getTableData();
        unset($data['id']);

        $this->db->insert('glossary', $data);

        $this->model->setId((int)$this->db->lastInsertId());
    }

    private function getTableData(): array
    {
        $data = [];
        $columns = $this->getValidTableColumns('glossary');

        foreach ($this->model->getObjectVars() as $key => $value) {
            if (in_array($key, $columns)) {
                if (is_bool($value)) {
                    $value = (int)$value;
                }
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
