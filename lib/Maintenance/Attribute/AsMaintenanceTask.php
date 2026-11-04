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

namespace Pimcore\Maintenance\Attribute;

use Attribute;

/**
 * Registers an autoconfigured service as maintenance task, like the "pimcore.maintenance.task" tag.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class AsMaintenanceTask
{
    /**
     * @param string $type the task name, e.g. for `pimcore:maintenance --job=<type>`
     * @param class-string|null $messengerMessageClass message dispatched instead of running the task in the maintenance process
     */
    public function __construct(
        public string $type,
        public ?string $messengerMessageClass = null,
    ) {
    }
}
