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

namespace Pimcore\Model\Schedule\Task;

use InvalidArgumentException;
use Pimcore\Model;
use Pimcore\Model\Schedule\Task;
use Pimcore\Tool\Serialize;

/**
 * @internal
 *
 * @property Task $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(int $id): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM schedule_tasks WHERE id = ?', [$id]);
        if (!$data) {
            throw new Model\Exception\NotFoundException('there is no task for the requested id');
        }
        $this->assignVariablesToModel($data);
    }

    public function save(): void
    {
        if (!$this->model->getCid() || !in_array($this->model->getCtype(), ['document', 'asset', 'object'], true)) {
            throw new InvalidArgumentException('A scheduled task needs the id and the type of its element');
        }

        // a pending task the maintenance cannot execute would be deactivated without running
        if ($this->model->getActive()) {
            $action = $this->model->getAction();
            if (!in_array($action, ['publish', 'unpublish', 'delete', 'publish-version'], true)) {
                throw new InvalidArgumentException(sprintf('Unknown action "%s" of scheduled task', (string)$action));
            }
            if ($this->model->getCtype() === 'asset' && ($action === 'publish' || $action === 'unpublish')) {
                throw new InvalidArgumentException('Assets cannot be published or unpublished by a scheduled task');
            }
            if ($action === 'publish-version' && !$this->model->getVersion()) {
                throw new InvalidArgumentException('A scheduled "publish-version" task needs a version');
            }
            // never due, the maintenance compares the date
            if (!$this->model->getDate()) {
                throw new InvalidArgumentException('A pending scheduled task needs a date');
            }
        }

        if ($this->model->getVersion()) {
            // a version of another element would be published in place of the task's element
            $version = $this->db->fetchAssociative('SELECT cid, ctype FROM versions WHERE id = ?', [$this->model->getVersion()]);
            if ($version && ((int)$version['cid'] !== $this->model->getCid() || $version['ctype'] !== $this->model->getCtype())) {
                throw new InvalidArgumentException(sprintf(
                    'Version %d does not belong to %s %d',
                    $this->model->getVersion(),
                    (string)$this->model->getCtype(),
                    (int)$this->model->getCid()
                ));
            }
        }

        // a task carried over with the id of a row that is gone would update nothing
        if (!$this->model->getId() || !$this->db->fetchOne('SELECT id FROM schedule_tasks WHERE id = ?', [$this->model->getId()])) {
            $this->create();
        } else {
            $this->update();
        }
    }

    /**
     * Create a new record for the object in database
     */
    public function create(): void
    {
        // one statement, a failing update after an empty insert left a row of NULLs behind
        $data = $this->getTaskData();
        if (!$this->model->getId()) {
            unset($data['id']);
        }
        $this->db->insert('schedule_tasks', $data);
        if (!$this->model->getId()) {
            $this->model->setId((int)$this->db->lastInsertId());
        }
    }

    /**
     * Save changes to database, it's an good idea to use save() instead
     */
    public function update(): void
    {
        $this->db->update('schedule_tasks', $this->getTaskData(), ['id' => $this->model->getId()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getTaskData(): array
    {
        $site = $this->model->getObjectVars();
        $data = [];

        foreach ($site as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('schedule_tasks'))) {
                if (is_array($value) || is_object($value)) {
                    $value = Serialize::serialize($value);
                } elseif (is_bool($value)) {
                    $value = (int)$value;
                }
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Deactivates the task unless another run already did, so that concurrent runs execute it only once
     *
     * @return bool whether this call deactivated the task
     */
    public function claim(): bool
    {
        $claimed = $this->db->executeStatement('UPDATE schedule_tasks SET active = 0 WHERE id = ? AND active = 1', [$this->model->getId()]) === 1;
        if ($claimed) {
            $this->model->setActive(false);
        }

        return $claimed;
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete('schedule_tasks', ['id' => $this->model->getId()]);
    }
}
