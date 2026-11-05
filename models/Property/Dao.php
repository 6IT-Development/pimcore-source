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

namespace Pimcore\Model\Property;

use InvalidArgumentException;
use Pimcore\Db\Helper;
use Pimcore\Model;
use Pimcore\Model\Property;

/**
 * @internal
 *
 * @property Property $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * Save object to database
     */
    public function save(): void
    {
        $name = $this->model->getName();
        if ($name === null || $name === '') {
            throw new InvalidArgumentException('Cannot save a property without a name');
        }
        if (mb_strlen($name) > 190) {
            // longer names would be truncated by the VARCHAR(190) column and collide on the primary key
            throw new InvalidArgumentException(sprintf('The name of property "%s" is longer than 190 characters', $name));
        }

        // the column is an enum: an unknown type (e.g. "date") would be stored as '' and its data lost,
        // a missing one as NULL, which makes the element and its descendants unloadable
        $type = $this->model->getType();
        if (!in_array($type, Property::TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported type "%s" of property "%s", supported are: %s', $type, $name, implode(', ', Property::TYPES)));
        }

        if (!in_array($this->model->getCtype(), ['document', 'asset', 'object'], true) || $this->model->getCid() === null) {
            throw new InvalidArgumentException(sprintf('Property "%s" is not assigned to an element', $name));
        }

        if ($type === 'object' || $type === 'asset' || $type === 'document') {
            // the stored id, also when the target is missing: the dependencies show the dangling reference
            $data = $this->model->getRelationId();
        } else {
            $data = $this->model->getData();
            if ($data !== null && !is_scalar($data)) {
                // a serialized array or object would be read back as the serialized string
                throw new InvalidArgumentException(sprintf('Property "%s" of type "%s" accepts only scalar data, %s given', $name, $type, get_debug_type($data)));
            }
        }

        // getProperties() orders by cpath: without it, an inherited property of the same name would win
        $cpath = $this->model->getCpath();
        if ($cpath === null || $cpath === '') {
            $element = Model\Element\Service::getElementById($this->model->getCtype(), $this->model->getCid());
            if (!$element instanceof Model\Element\ElementInterface) {
                throw new InvalidArgumentException(sprintf('Property "%s" belongs to %s %d, which does not exist', $name, $this->model->getCtype(), $this->model->getCid()));
            }
            $this->model->setCpath($element->getRealFullPath());
        }

        $saveData = [
            'cid' => $this->model->getCid(),
            'ctype' => $this->model->getCtype(),
            'cpath' => $this->model->getCpath(),
            'name' => $this->model->getName(),
            'type' => $this->model->getType(),
            'inheritable' => (int)$this->model->getInheritable(),
            'data' => $data,
        ];

        Helper::upsert($this->db, 'properties', $saveData, $this->getPrimaryKey('properties'));
    }
}
