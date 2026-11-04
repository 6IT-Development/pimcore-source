<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\UuidBundle\Model\Tool;

use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Bundle\UuidBundle\DependencyInjection\Configuration;
use Pimcore\Bundle\UuidBundle\Model\Tool\UUID\Dao;
use Pimcore\Model;
use Symfony\Component\Uid\Uuid as Uid;

/**
 * @method Dao getDao()
 * @method void delete()
 * @method void save()
 */
final class UUID extends Model\AbstractModel
{
    /**
     * @internal
     *
     */
    protected int|string $itemId;

    /**
     * @internal
     *
     */
    protected string $type;

    /**
     * @internal
     *
     */
    protected string $uuid;

    /**
     * @internal
     *
     */
    protected string $instanceIdentifier;

    /**
     * @internal
     *
     */
    protected Model\Element\ElementInterface|Model\DataObject\ClassDefinitionInterface|null $item = null;

    /**
     * @return $this
     */
    public function setInstanceIdentifier(string $instanceIdentifier): UUID
    {
        $this->instanceIdentifier = $instanceIdentifier;

        return $this;
    }

    public function getInstanceIdentifier(): string
    {
        return $this->instanceIdentifier;
    }

    /**
     * @return $this
     *
     * @throws Exception
     * @internal
     *
     */
    public function setSystemInstanceIdentifier(): UUID
    {
        $instanceIdentifier = Pimcore::getKernel()->getContainer()->getParameter('pimcore_uuid.instance_identifier');
        if ($instanceIdentifier === null || $instanceIdentifier === '') {
            throw new Exception('No instance identifier set in system config!');
        }

        $instanceIdentifier = (string)$instanceIdentifier;
        if ($error = Configuration::getInstanceIdentifierError($instanceIdentifier)) {
            throw new Exception($error);
        }

        $this->setInstanceIdentifier($instanceIdentifier);

        return $this;
    }

    /**
     * @return $this
     */
    public function setItemId(int|string $id): UUID
    {
        $this->itemId = $id;

        return $this;
    }

    public function getItemId(): int|string
    {
        return $this->itemId;
    }

    /**
     * @return $this
     */
    public function setType(string $type): UUID
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Computes the deterministic v5 UUID from instance identifier, type and item id, without touching the database.
     *
     * @throws Exception
     * @internal
     *
     */
    public function computeUuid(): string
    {
        if (!$this->getInstanceIdentifier()) {
            throw new Exception('No instance identifier specified.');
        }

        // namespace originally used from \Ramsey\Uuid\Uuid::NAMESPACE_DNS
        $namespace = Uid::fromString('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $uuid = Uid::v5($namespace, $this->getInstanceIdentifier() . '~' . $this->getType() . '~' . $this->getItemId());
        $this->uuid = $uuid->toRfc4122();

        return $this->uuid;
    }

    /**
     * Computes the UUID and stores its row unless it exists already.
     *
     * @throws Exception
     * @internal
     *
     */
    public function createUuid(): string
    {
        $this->computeUuid();
        $this->getDao()->create();

        return $this->uuid;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * @return $this
     *
     * @throws InvalidArgumentException when the item has no id yet or its element type is unknown
     */
    public function setItem(Model\Element\ElementInterface|Model\DataObject\ClassDefinitionInterface $item): UUID
    {
        $id = $item->getId();
        if ($id === null || $id === '') {
            throw new InvalidArgumentException(sprintf('Cannot get the UUID of an unsaved %s, it has no id.', get_debug_type($item)));
        }

        $type = $item instanceof Model\Element\ElementInterface ? Model\Element\Service::getElementType($item) : 'class';
        if ($type === null) {
            throw new InvalidArgumentException(sprintf('Cannot get the UUID of %s, its element type is unknown.', get_debug_type($item)));
        }

        $this->setItemId($id);
        $this->setType($type);

        $this->item = $item;

        return $this;
    }

    /**
     * Returns the element or class definition the UUID belongs to, resolved from type and item id when the model was
     * loaded from the database; null when it no longer exists.
     */
    public function getItem(): Model\Element\ElementInterface|Model\DataObject\ClassDefinitionInterface|null
    {
        return $this->item ??= $this->resolveItem();
    }

    private function resolveItem(): Model\Element\ElementInterface|Model\DataObject\ClassDefinitionInterface|null
    {
        if (!isset($this->type, $this->itemId)) {
            return null;
        }

        if ($this->type === 'class') {
            return Model\DataObject\ClassDefinition::getById((string)$this->itemId);
        }

        if (!is_numeric($this->itemId)) {
            return null;
        }

        return Model\Element\Service::getElementById($this->type, (int)$this->itemId);
    }

    /**
     * Returns the UUID of the given item. With $create (the default) a missing row is stored, so that getByUuid()
     * finds items created before the instance identifier was configured; pass false for a read without DB writes.
     *
     * @throws Exception
     */
    public static function getByItem(Model\Element\ElementInterface|Model\DataObject\ClassDefinitionInterface $item, bool $create = true): UUID
    {
        $self = new self();
        $self->setSystemInstanceIdentifier()->setItem($item);

        if ($create) {
            $self->createUuid();
        } else {
            $self->computeUuid();
        }

        return $self;
    }

    /**
     * Deletes all UUIDs of the given item, also those written under a previous instance identifier.
     */
    public static function deleteByItem(Model\Element\ElementInterface|Model\DataObject\ClassDefinitionInterface $item): void
    {
        $self = new self();
        $self->setItem($item);
        $self->getDao()->deleteByItem();
    }

    /**
     * Returns the UUID model stored for the given UUID, or null when the UUID is unknown.
     */
    public static function getByUuid(string $uuid): ?UUID
    {
        $self = new self();

        return $self->getDao()->getByUuid($uuid) ? $self : null;
    }

    /**
     *
     *
     * @throws Exception
     */
    public static function create(Model\Element\ElementInterface|Model\DataObject\ClassDefinitionInterface $item): UUID
    {
        $uuid = new UUID();
        $uuid->setSystemInstanceIdentifier()->setItem($item);
        $uuid->setUuid($uuid->createUuid());

        return $uuid;
    }
}
