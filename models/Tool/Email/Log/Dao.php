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

namespace Pimcore\Model\Tool\Email\Log;

use DateTimeInterface;
use Exception;
use Pimcore\Db\Helper;
use Pimcore\Model;
use Pimcore\Model\Tool\Email\Log;
use stdClass;

/**
 * @internal
 *
 * @property Log $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * Name of the db table
     *
     */
    protected static string $dbTable = 'email_log';

    private const array VARCHAR_COLUMN_LENGTHS = ['requestUri' => 500, 'from' => 500, 'replyTo' => 255, 'subject' => 500];

    private const int TEXT_COLUMN_BYTES = 65535;

    /**
     * Get the data for the object from database for the given id, or from the ID which is set in the object
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(?int $id = null): void
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchAssociative('SELECT * FROM email_log WHERE id = ?', [$this->model->getId()]);
        if (!$data) {
            throw new Model\Exception\NotFoundException('email log with id ' . $id . ' not found');
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * Save document to database, a new log is inserted with all columns at once
     *
     * @throws Exception
     */
    public function save(): void
    {
        $data = [];

        $emailLog = $this->model->getObjectVars();

        foreach ($emailLog as $key => $value) {
            if (in_array($key, $this->getValidTableColumns(self::$dbTable))) {
                // check if the getter exists
                $getter = 'get' . ucfirst($key);
                if (!method_exists($this->model, $getter)) {
                    continue;
                }

                // get the value from the getter
                $value = $this->model->$getter();

                if (is_bool($value)) {
                    $value = (int)$value;
                } elseif (is_array($value)) {
                    //converts the dynamic params to a basic json string
                    $preparedData = self::createJsonLoggingObject($value);
                    $value = json_encode($preparedData, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
                }

                $data[$key] = $this->fitColumn($key, $value);
            }
        }

        $data = Helper::quoteDataIdentifiers($this->db, $data);

        if ($this->model->getId()) {
            $this->db->update(self::$dbTable, $data, ['id' => $this->model->getId()]);
        } else {
            $this->db->insert(self::$dbTable, $data);
            $this->model->setId((int)$this->db->lastInsertId());
            $this->model->setModificationDate(time());
        }
    }

    /**
     * Shortens values that do not fit their column, so strict SQL mode does not reject the whole log entry
     */
    private function fitColumn(string $column, mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if (isset(self::VARCHAR_COLUMN_LENGTHS[$column])) {
            return mb_substr($value, 0, self::VARCHAR_COLUMN_LENGTHS[$column]);
        }

        if (in_array($column, ['params', 'error'], true) && strlen($value) > self::TEXT_COLUMN_BYTES) {
            if ($column === 'params') {
                // cut JSON would be unreadable, so the params are replaced by a note
                return json_encode([self::prepareLoggingData('params', sprintf('Not logged, the params have %d bytes', strlen($value)))]);
            }

            return mb_strcut($value, 0, self::TEXT_COLUMN_BYTES);
        }

        return $value;
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete(self::$dbTable, ['id' => $this->model->getId()]);
    }

    protected function createJsonLoggingObject(array|string $data): array|string
    {
        if (!is_array($data)) {
            return json_encode(new stdClass());
        } else {
            $loggingData = [];
            foreach ($data as $key => $value) {
                $loggingData[] = self::prepareLoggingData($key, $value);
            }

            return $loggingData;
        }
    }

    /**
     * Creates the basic logging for the treeGrid in the backend
     * Data will be enhanced with live-data in the backend
     *
     *
     */
    protected function prepareLoggingData(string $key, mixed $value): stdClass
    {
        $class = new stdClass();
        $class->key = $key; // key has to be a string otherwise the treeGrid won't work

        if (is_string($value) || is_int($value) || is_null($value)) {
            $class->data = ['type' => 'simple',
                'value' => $value,];
        } elseif ($value instanceof DateTimeInterface) {
            $class->data = ['type' => 'simple',
                'value' => $value->format('Y-m-d H:i'),];
        } elseif (is_object($value) && method_exists($value, 'getId')) {
            $class->data = ['type' => 'object',
                'objectId' => $value->getId(),
                'objectClass' => get_class($value),];
        } elseif (is_array($value)) {
            foreach ($value as $entryKey => $entryValue) {
                $class->children[] = self::prepareLoggingData($entryKey, $entryValue);
            }
        }

        return $class;
    }
}
