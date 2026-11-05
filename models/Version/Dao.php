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

namespace Pimcore\Model\Version;

use InvalidArgumentException;
use Pimcore\Db\Helper;
use Pimcore\Model;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\Version;
use RuntimeException;

/**
 * @internal
 *
 * @property Version $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     *
     * @throws NotFoundException
     */
    public function getById(int $id): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM versions WHERE id = ?', [$id]);

        if (!$data) {
            throw new NotFoundException('version with id ' . $id . ' not found');
        }

        $this->assignRow($data);
    }

    /**
     * @internal
     *
     * @param array<string, mixed> $data a row of the versions table
     */
    public function assignRow(array $data): void
    {
        $data['public'] = (bool)$data['public'];
        $data['serialized'] = (bool)$data['serialized'];
        $data['autoSave'] = (bool)$data['autoSave'];
        $data['note'] = (string)$data['note'];
        // not assignVariablesToModel(): it skips NULL and 0, the system user (userId 0) would be lost
        $this->model->setValues($data);
    }

    /**
     * Save object to database
     */
    public function save(): int
    {
        // the storage files are named by the element
        if (!$this->model->getCid() || !$this->model->getCtype()) {
            throw new InvalidArgumentException('A version needs the id and the type of its element');
        }

        $version = $this->model->getObjectVars();
        $data = [];

        foreach ($version as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('versions'))) {
                if (is_bool($value)) {
                    $value = (int)$value;
                }

                $data[$key] = $value;
            }
        }

        Helper::upsert($this->db, 'versions', $data, $this->getPrimaryKey('versions'));

        if (!$this->model->getId()) {
            $lastInsertId = (int)$this->db->lastInsertId();
            if (!$lastInsertId) {
                throw new RuntimeException('The id of the new version could not be determined');
            }
            $this->model->setId($lastInsertId);
        }

        return $this->model->getId();
    }

    public function updateMetadata(): void
    {
        $this->db->update('versions', [
            'note' => $this->model->getNote(),
            'public' => (int)$this->model->isPublic(),
            'versionCount' => $this->model->getVersionCount(),
        ], ['id' => $this->model->getId()]);
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete('versions', ['id' => $this->model->getId()]);
    }

    public function isVersionUsedInScheduler(Version $version): bool
    {
        $exists = $this->db->fetchOne('SELECT id FROM schedule_tasks WHERE active = 1 AND version = ?', [$version->getId()]);

        return (bool)$exists;
    }

    public function getBinaryFileIdForHash(string $hash): ?int
    {
        $id = $this->db->fetchOne('SELECT IFNULL(binaryFileId, id) FROM versions WHERE binaryFileHash = ? AND cid = ? AND storageType = ? ORDER BY id ASC LIMIT 1', [$hash, $this->model->getCid(), $this->model->getStorageType()]);
        if (!$id) {
            return null;
        }

        return (int)$id;
    }

    /**
     * Whether another version of the element still uses the binary with this hash in the given storage type; a
     * binary is shared only within one storage (see getBinaryFileIdForHash())
     */
    public function isBinaryHashInUse(?string $hash, ?string $storageType = null): bool
    {
        // versions without a binary (folders, documents, objects) share nothing
        if ($hash === null) {
            return false;
        }

        $sql = 'SELECT count(*) FROM versions WHERE binaryFileHash = ? AND cid = ? AND id != ?';
        $params = [$hash, $this->model->getCid(), (int)$this->model->getId()];
        $storageType ??= $this->model->getStorageType();
        if ($storageType !== null) {
            $sql .= ' AND storageType = ?';
            $params[] = $storageType;
        }

        $count = (int)$this->db->fetchOne($sql, $params);

        return $count > 0;
    }

    /**
     * Non-public, non-auto-save versions older than the deadline, in batches after $lastId
     *
     * @return int[]
     */
    public function maintenanceGetVersionIdsOlderThan(string $elementType, int $deadline, int $lastId, int $limit): array
    {
        $ids = $this->db->fetchFirstColumn(
            'SELECT id FROM versions WHERE ctype = ? AND date < ? AND public = 0 AND autoSave = 0 AND id > ? ORDER BY id LIMIT ' . $limit,
            [$elementType, $deadline, $lastId]
        );

        return array_map('intval', $ids);
    }

    /**
     * Elements with more than $keep non-public, non-auto-save versions, in batches after $lastCid
     *
     * @return int[]
     */
    public function maintenanceGetElementIdsWithMoreVersionsThan(string $elementType, int $keep, int $lastCid, int $limit): array
    {
        $cids = $this->db->fetchFirstColumn(
            'SELECT cid FROM versions WHERE ctype = ? AND public = 0 AND autoSave = 0 AND cid > ? GROUP BY cid HAVING COUNT(*) > ? ORDER BY cid LIMIT ' . $limit,
            [$elementType, $lastCid, $keep]
        );

        return array_map('intval', $cids);
    }

    /**
     * The non-public, non-auto-save versions of an element except the newest $keep
     *
     * @return int[]
     */
    public function maintenanceGetVersionIdsBeyond(string $elementType, int $cid, int $keep): array
    {
        $ids = $this->db->fetchFirstColumn(
            'SELECT id FROM versions WHERE cid = ? AND ctype = ? AND public = 0 AND autoSave = 0 ORDER BY id DESC LIMIT ' . $keep . ', ' . PHP_INT_MAX,
            [$cid, $elementType]
        );

        return array_map('intval', $ids);
    }
}
