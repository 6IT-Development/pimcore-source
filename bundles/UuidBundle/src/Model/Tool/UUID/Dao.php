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

namespace Pimcore\Bundle\UuidBundle\Model\Tool\UUID;

use Doctrine\DBAL\Types\Types;
use Exception;
use Pimcore\Bundle\UuidBundle\Model\Tool\UUID;
use Pimcore\Db\Helper;
use Pimcore\Model;

/**
 * @internal
 *
 * @property UUID $model
 */
class Dao extends Model\Dao\AbstractDao
{
    public const string TABLE_NAME = 'uuids';

    public function save(): void
    {
        $data = $this->getValidObjectVars();

        Helper::upsert($this->db, self::TABLE_NAME, $data, $this->getPrimaryKey(self::TABLE_NAME));
    }

    /**
     * Inserts the row unless it exists already, in one statement, so concurrent creates of the same UUID do not
     * collide. The no-op ON DUPLICATE KEY UPDATE keeps strict-mode errors, which INSERT IGNORE would turn into warnings.
     */
    public function create(): void
    {
        $data = $this->getValidObjectVars();
        $columns = array_map($this->db->quoteIdentifier(...), array_keys($data));

        $this->db->executeStatement(
            'INSERT INTO ' . $this->db->quoteIdentifier(self::TABLE_NAME)
            . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($data), '?')) . ')'
            . ' ON DUPLICATE KEY UPDATE `uuid` = `uuid`',
            array_values($data)
        );
    }

    private function getValidObjectVars(): array
    {
        $data = $this->model->getObjectVars();

        foreach ($data as $key => $value) {
            if (!in_array($key, $this->getValidTableColumns(static::TABLE_NAME))) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * @throws Exception
     */
    public function delete(): void
    {
        $uuid = $this->model->getUuid();
        if (!$uuid) {
            throw new Exception("Couldn't delete UUID - no UUID specified.");
        }

        $itemId = $this->model->getItemId();
        $type = $this->model->getType();

        $this->db->delete(self::TABLE_NAME, ['itemId' => $itemId, 'type' => $type, 'uuid' => $uuid]);
    }

    /**
     * Deletes every UUID row of the model's item, whatever instance identifier it was written under.
     */
    public function deleteByItem(): void
    {
        $this->db->delete(self::TABLE_NAME, ['itemId' => $this->model->getItemId(), 'type' => $this->model->getType()]);
    }

    /**
     * Loads the row of the given UUID into the model.
     *
     * @return bool false when no row matches the UUID
     */
    public function getByUuid(string $uuid): bool
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where('uuid = :uuid')
            ->setParameter('uuid', $uuid, Types::STRING);

        $data = $queryBuilder
            ->executeQuery()
            ->fetchAssociative();

        if ($data === false) {
            return false;
        }

        $this->model->setValues($data);

        return true;
    }
}
