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

namespace Pimcore\Model\Notification\Service;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use JsonException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException as SymfonyBadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @internal
 */
class NotificationServiceFilterParser
{
    public const string KEY_FILTER = 'filter';

    public const string KEY_TYPE = 'type';

    public const string KEY_PROPERTY = 'property';

    public const string KEY_OPERATOR = 'operator';

    public const string KEY_VALUE = 'value';

    public const string TYPE_STRING = 'string';

    public const string TYPE_DATE = 'date';

    public const string OPERATOR_LIKE = 'like';

    public const string OPERATOR_EQ = 'eq';

    public const string OPERATOR_GT = 'gt';

    public const string OPERATOR_LT = 'lt';

    private Request $request;

    /**
     * The filterable properties and their columns, anything else is rejected (the property goes into SQL)
     */
    private array $properties = [
        'title' => 'title',
        'message' => 'message',
        'type' => 'type',
        'timestamp' => 'creationDate',
        'creationDate' => 'creationDate',
        'modificationDate' => 'modificationDate',
    ];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return array<string, array{condition: string, conditionVariables: array<string, mixed>}>
     *
     * @throws BadRequestHttpException for a malformed filter
     */
    public function parse(): array
    {
        $result = [];

        try {
            $filter = $this->request->request->getString(self::KEY_FILTER, '[]');
            $items = $filter === '' ? [] : json_decode($filter, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException|SymfonyBadRequestException) {
            throw new BadRequestHttpException('Invalid filter');
        }

        if (!is_array($items)) {
            throw new BadRequestHttpException('Invalid filter');
        }

        foreach (array_values($items) as $index => $item) {
            if (!is_array($item) || !is_string($item[self::KEY_OPERATOR] ?? null)) {
                throw new BadRequestHttpException('Invalid filter item');
            }

            // the index keeps keys and placeholders of several filters on the same property and operator apart
            [$key, $condition, $conditionVariables] = match ($item[self::KEY_TYPE] ?? null) {
                self::TYPE_STRING => $this->parseString($item, $index),
                self::TYPE_DATE => $this->parseDate($item, $index),
                default => throw new BadRequestHttpException('Invalid filter type'),
            };

            $result[$key] = [
                'condition' => $condition,
                'conditionVariables' => $conditionVariables,
            ];
        }

        return $result;
    }

    /**
     * @return array{0: string, 1: string, 2: array<string, mixed>}
     */
    private function parseString(array $item, int $index): array
    {
        $property = $this->getDbProperty($item);
        $value = $item[self::KEY_VALUE] ?? '';

        if (!is_scalar($value)) {
            throw new BadRequestHttpException('Invalid filter value');
        }

        if ($item[self::KEY_OPERATOR] !== self::OPERATOR_LIKE) {
            throw new BadRequestHttpException('Invalid filter operator');
        }

        $key = $property . '_like_' . $index;
        // the search text is matched literally, % and _ are no wildcards
        $value = addcslashes((string)$value, '\\%_');

        return [
            $key,
            "`$property` LIKE :$key",
            [$key => "%$value%"],
        ];
    }

    /**
     * Compares by whole days: "eq" is the selected day, "gt" after it and "lt" before it, without overlaps
     *
     * @return array{0: string, 1: string, 2: array<string, mixed>}
     */
    private function parseDate(array $item, int $index): array
    {
        $property = $this->getDbProperty($item);
        $value = $item[self::KEY_VALUE] ?? null;

        if (!is_string($value) && !is_int($value)) {
            throw new BadRequestHttpException('Invalid filter value');
        }

        try {
            $day = new Carbon($value)->startOfDay();
        } catch (InvalidFormatException) {
            throw new BadRequestHttpException('Invalid filter date');
        }
        $nextDay = $day->copy()->addDay();

        switch ($item[self::KEY_OPERATOR]) {
            case self::OPERATOR_EQ:
                $key = $property . '_eq_' . $index;

                return [
                    $key,
                    "`$property` >= :{$key}_start AND `$property` < :{$key}_end",
                    [
                        $key . '_start' => $day->toDateTimeString(),
                        $key . '_end' => $nextDay->toDateTimeString(),
                    ],
                ];
            case self::OPERATOR_GT:
                $key = $property . '_gt_' . $index;

                return [
                    $key,
                    "`$property` >= :$key",
                    [$key => $nextDay->toDateTimeString()],
                ];
            case self::OPERATOR_LT:
                $key = $property . '_lt_' . $index;

                return [
                    $key,
                    "`$property` < :$key",
                    [$key => $day->toDateTimeString()],
                ];
        }

        throw new BadRequestHttpException('Invalid filter operator');
    }

    private function getDbProperty(array $item): string
    {
        $property = $item[self::KEY_PROPERTY] ?? null;

        if (!is_string($property) || !isset($this->properties[$property])) {
            throw new BadRequestHttpException('Invalid filter property');
        }

        return $this->properties[$property];
    }
}
