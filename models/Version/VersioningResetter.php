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

namespace Pimcore\Model\Version;

use Pimcore\Model\Version;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Enables the versioning again on kernel.reset (e.g. between Messenger messages): a Version::disable() without its
 * enable() would otherwise switch off the versioning for the rest of the worker.
 *
 * @internal
 */
final class VersioningResetter implements ResetInterface
{
    public function reset(): void
    {
        Version::enable();
    }
}
