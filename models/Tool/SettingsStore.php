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

use Exception;
use InvalidArgumentException;
use Pimcore\Model;
use Pimcore\Model\Tool\SettingsStore\Dao;

/**
 * @method Dao getDao()
 */
final class SettingsStore extends Model\AbstractModel
{
    public const string TYPE_BOOLEAN = 'bool';

    public const string TYPE_FLOAT = 'float';

    public const string TYPE_INTEGER = 'int';

    public const string TYPE_STRING = 'string';

    protected const array ALLOWED_TYPES = [
        self::TYPE_BOOLEAN,
        self::TYPE_FLOAT,
        self::TYPE_INTEGER,
        self::TYPE_STRING,
    ];

    /**
     * @internal
     *
     */
    protected string $id;

    /**
     * @internal
     *
     */
    protected ?string $scope = null;

    /**
     * @internal
     *
     */
    protected string $type = '';

    /**
     * @internal
     *
     */
    protected mixed $data = null;

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
     * @throws Exception
     */
    private static function validateType(string $type): void
    {
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new Exception(sprintf('Invalid type `%s`, allowed types are %s', $type, implode(',', self::ALLOWED_TYPES)));
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function validateData(float|bool|int|string $data, string $type): void
    {
        $valid = match ($type) {
            self::TYPE_INTEGER => is_int($data) || (is_string($data) && preg_match('/^[+-]?\d+$/', $data)),
            self::TYPE_FLOAT => is_float($data) || is_int($data) || (is_string($data) && is_numeric($data)),
            self::TYPE_BOOLEAN => is_bool($data) || in_array($data, [0, 1, '0', '1', ''], true),
            default => true,
        };

        if (!$valid) {
            throw new InvalidArgumentException(sprintf('The value %s is not of the settings store type `%s`', var_export($data, true), $type));
        }
    }

    /**
     * The value must fit the type, e.g. an int or a string of digits for TYPE_INTEGER
     *
     * @throws Exception
     */
    public static function set(string $id, float|bool|int|string $data, string $type = 'string', ?string $scope = null): bool
    {
        self::validateType($type);
        self::validateData($data, $type);
        $instance = self::getInstance();

        return $instance->getDao()->set($id, $data, $type, $scope);
    }

    public static function delete(string $id, ?string $scope = null): int|string
    {
        $instance = self::getInstance();

        return $instance->getDao()->delete($id, $scope);
    }

    public static function get(string $id, ?string $scope = null): ?SettingsStore
    {
        try {
            $item = new self();
            $item->getDao()->getById($id, $scope);

            return $item;
        } catch (Model\Exception\NotFoundException) {
            return null;
        }
    }

    /**
     *
     * @return string[]
     */
    public static function getIdsByScope(string $scope): array
    {
        $instance = self::getInstance();

        return $instance->getDao()->getIdsByScope($scope);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    /**
     * The empty scope is stored as '' and read as null
     */
    public function setScope(?string $scope): void
    {
        $this->scope = $scope === '' ? null : $scope;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     *
     * @throws Exception
     */
    public function setType(string $type): void
    {
        self::validateType($type);
        $this->type = $type;
    }

    public function getData(): float|bool|int|string
    {
        return $this->data;
    }

    public function setData(float|bool|int|string $data): void
    {
        if (!empty($this->getType())) {
            settype($data, $this->getType());
        }
        $this->data = $data;
    }
}
