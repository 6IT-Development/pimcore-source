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

namespace Pimcore\Bundle\ApplicationLoggerBundle;

/**
 * The values of the relatedobjecttype column
 */
enum RelatedObjectType: string
{
    case Object = 'object';
    case Document = 'document';
    case Asset = 'asset';

    /**
     * An ID without a valid type is treated as the ID of a data object
     */
    public static function fromContext(mixed $type): self
    {
        if ($type instanceof self) {
            return $type;
        }

        return (is_string($type) ? self::tryFrom($type) : null) ?? self::Object;
    }
}
