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

namespace Pimcore\Request\Attribute;

use Attribute;

/**
 * Argument to resolve a DataObject.
 *
 * - class: the class to load, instead of the argument type (must be a subtype of it)
 * - unpublished: also resolve unpublished objects (otherwise only admins previewing the object get them)
 * - options: params passed to getById(), e.g. ['force' => true]
 *
 * An empty route value resolves to null for nullable arguments. A non-numeric or unknown ID is a 404, also for
 * nullable arguments.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class DataObjectParam
{
    public function __construct(
        public ?string $class = null,
        public ?bool   $unpublished = null,
        public ?array  $options = null
    )
    {
    }
}
