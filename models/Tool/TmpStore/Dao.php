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

namespace Pimcore\Model\Tool\TmpStore;

use __PHP_Incomplete_Class;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use InvalidArgumentException;
use Pimcore\Db\Helper;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Tool\TmpStore;

/**
 * @internal
 *
 * @property TmpStore $model
 */
class Dao extends Model\Dao\AbstractDao
{
    private const int KEY_MAX_LENGTH = 190;

    /**
     * Always returns true, a failed write throws
     *
     * @throws InvalidArgumentException for an id or tag longer than the column
     * @throws Exception
     */
    public function add(string $id, mixed $data, ?string $tag = null, ?int $lifetime = null): bool
    {
        Helper::upsert($this->db, 'tmp_store', $this->buildRow($id, $data, $tag, $lifetime), $this->getPrimaryKey('tmp_store'));

        return true;
    }

    /**
     * Stores the item unless an unexpired one with this id exists; the unique id makes it atomic
     *
     * @return bool true if stored, false if the id is taken
     *
     * @throws InvalidArgumentException for an id or tag longer than the column
     * @throws Exception
     */
    public function insertIfAbsent(string $id, mixed $data, ?string $tag = null, ?int $lifetime = null): bool
    {
        $row = $this->buildRow($id, $data, $tag, $lifetime);
        $this->db->executeStatement('DELETE FROM tmp_store WHERE id = ? AND (expiryDate IS NULL OR expiryDate < ?)', [$id, time()]);

        try {
            $this->db->insert('tmp_store', Helper::quoteDataIdentifiers($this->db, $row));
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }

    /**
     * Every value except a string is serialized, so int, float, bool and null keep their type
     *
     * @return array<string, mixed>
     */
    private function buildRow(string $id, mixed $data, ?string $tag, ?int $lifetime): array
    {
        foreach (['id' => $id, 'tag' => (string)$tag] as $column => $value) {
            if (mb_strlen($value) > self::KEY_MAX_LENGTH) {
                throw new InvalidArgumentException(sprintf('The tmp store %s must not be longer than %d characters', $column, self::KEY_MAX_LENGTH));
            }
        }

        $serialized = !is_string($data);

        return [
            'id' => $id,
            'data' => $serialized ? serialize($data) : $data,
            'tag' => $tag,
            'date' => time(),
            'expiryDate' => (time() + $lifetime),
            'serialized' => (int)$serialized,
        ];
    }

    public function delete(string $id): void
    {
        $this->db->delete('tmp_store', ['id' => $id]);
    }

    public function getById(string $id): bool
    {
        $item = $this->db->fetchAssociative('SELECT * FROM tmp_store WHERE id = ?', [$id]);

        if ($item) {
            if ($item['serialized']) {
                $data = @unserialize((string)$item['data']);
                // a class that was renamed or removed since the item was stored, or a broken value
                if (($data === false && $item['data'] !== serialize(false)) || $data instanceof __PHP_Incomplete_Class) {
                    Logger::warning(sprintf('Tmp store item %s cannot be unserialized and is ignored', $id));

                    return false;
                }
                $item['data'] = $data;
            }

            $item['serialized'] = (bool)$item['serialized'];
            $this->assignVariablesToModel($item);

            return true;
        }

        return false;
    }

    public function getIdsByTag(string $tag): array
    {
        return $this->db->fetchFirstColumn('SELECT id FROM tmp_store WHERE tag = ? AND expiryDate >= ?', [$tag, time()]);
    }
}
