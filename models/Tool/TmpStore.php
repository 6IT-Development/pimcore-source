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

namespace Pimcore\Model\Tool;

use InvalidArgumentException;
use Pimcore\Model;
use Pimcore\Model\Tool\TmpStore\Dao;

/**
 * @method bool getById(string $id)
 * @method Dao getDao()
 */
final class TmpStore extends Model\AbstractModel
{
    /**
     * @internal
     *
     */
    protected string $id;

    /**
     * @internal
     */
    protected ?string $tag = null;

    /**
     * @internal
     *
     */
    protected mixed $data = null;

    /**
     * @internal
     *
     */
    protected int $date = 0;

    /**
     * @internal
     *
     */
    protected int $expiryDate = 0;

    /**
     * @internal
     *
     */
    protected bool $serialized = false;

    /**
     * @internal
     *
     */
    protected static ?self $instance = null;

    private static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * null and 0 mean the default of 7 days
     */
    private static function getLifetime(?int $lifetime): int
    {
        if ($lifetime < 0) {
            throw new InvalidArgumentException('The tmp store lifetime must not be negative');
        }

        return $lifetime ?: 86400 * 7;
    }

    /**
     * Stores the item unless an unexpired one with this id exists
     *
     * @return bool true if stored, false if an item with this id already exists
     */
    public static function add(string $id, mixed $data, ?string $tag = null, ?int $lifetime = null): bool
    {
        return self::getInstance()->getDao()->insertIfAbsent($id, $data, $tag, self::getLifetime($lifetime));
    }

    public static function set(string $id, mixed $data, ?string $tag = null, ?int $lifetime = null): bool
    {
        return self::getInstance()->getDao()->add($id, $data, $tag, self::getLifetime($lifetime));
    }

    public static function delete(string $id): void
    {
        $instance = self::getInstance();
        $instance->getDao()->delete($id);
    }

    public static function get(string $id): ?TmpStore
    {
        $item = new self();
        if ($item->getById($id)) {
            if ($item->getExpiryDate() < time()) {
                self::delete($id);
            } else {
                return $item;
            }
        }

        return null;
    }

    public static function getIdsByTag(string $tag): array
    {
        $instance = self::getInstance();
        return $instance->getDao()->getIdsByTag($tag);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(?string $tag): void
    {
        $this->tag = $tag;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function setData(mixed $data): void
    {
        $this->data = $data;
    }

    public function getDate(): int
    {
        return $this->date;
    }

    public function setDate(int $date): void
    {
        $this->date = $date;
    }

    public function isSerialized(): bool
    {
        return $this->serialized;
    }

    public function setSerialized(bool $serialized): void
    {
        $this->serialized = $serialized;
    }

    public function getExpiryDate(): int
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(int $expiryDate): void
    {
        $this->expiryDate = $expiryDate;
    }

    public function update(?int $lifetime = null): bool
    {
        return $this->getDao()->add($this->getId(), $this->getData(), $this->getTag(), self::getLifetime($lifetime));
    }
}
