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

namespace Pimcore\Model\Element\Editlock;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Pimcore\Model;
use Pimcore\Model\Element\Editlock;

/**
 * @internal
 *
 * @property Editlock $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByElement(int $cid, string $ctype): void
    {
        // the newest lock, in case the unique index is not there yet
        $data = $this->db->fetchAssociative('SELECT * FROM edit_lock WHERE cid = ? AND ctype = ? ORDER BY id DESC LIMIT 1', [$cid, $ctype]);

        if (!$data) {
            throw new Model\Exception\NotFoundException('Lock with cid ' . $cid . ' and ctype ' . $ctype . ' not found');
        }

        $this->assignVariablesToModel($data);

        // add elements path
        $element = Model\Element\Service::getElementById($ctype, $cid);
        if ($element) {
            $this->model->setCpath($element->getRealFullPath());
        }
    }

    /**
     * Save object to database
     *
     *
     * @todo: not all save methods return a boolean, why this one?
     */
    public function save(): bool
    {
        // one row per element: the lock of the element is replaced, LAST_INSERT_ID(id) returns the id of that row
        $this->db->executeStatement(
            'INSERT INTO edit_lock (cid, ctype, userId, sessionId, date) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), userId = VALUES(userId), sessionId = VALUES(sessionId), date = VALUES(date)',
            [$this->model->getCid(), $this->model->getCtype(), $this->model->getUserId(), $this->model->getSessionId(), $this->model->getDate()]
        );

        $this->model->setId((int)$this->db->lastInsertId());

        return true;
    }

    /**
     * Removes an expired lock or the session's own lock, then inserts the new one; the unique index on (cid, ctype)
     * rejects the insert while another session holds the lock.
     */
    public function tryLock(int $cid, string $ctype, int $userId, string $sessionId, int $expiredBefore): bool
    {
        $this->db->executeStatement(
            'DELETE FROM edit_lock WHERE cid = ? AND ctype = ? AND (sessionId = ? OR date IS NULL OR date < ?)',
            [$cid, $ctype, $sessionId, $expiredBefore]
        );

        try {
            $this->db->insert('edit_lock', ['cid' => $cid, 'ctype' => $ctype, 'userId' => $userId, 'sessionId' => $sessionId, 'date' => time()]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }

    public function deleteByElement(int $cid, string $ctype): void
    {
        $this->db->delete('edit_lock', ['cid' => $cid, 'ctype' => $ctype]);
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete('edit_lock', ['id' => $this->model->getId()]);
    }

    public function clearSession(string $sessionId): void
    {
        $this->db->delete('edit_lock', ['sessionId' => $sessionId]);
    }
}
