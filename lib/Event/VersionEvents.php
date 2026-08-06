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

namespace Pimcore\Event;

final class VersionEvents
{
    /**
     * @Event("Pimcore\Event\Model\VersionEvent")
     *
     * @var string
     */
    public const string PRE_SAVE = 'pimcore.version.preSave';

    /**
     * @Event("Pimcore\Event\Model\VersionEvent")
     *
     * @var string
     */
    public const string POST_SAVE = 'pimcore.version.postSave';

    /**
     * @Event("Pimcore\Event\Model\VersionEvent")
     *
     * @var string
     */
    public const string PRE_DELETE = 'pimcore.version.preDelete';

    /**
     * @Event("Pimcore\Event\Model\VersionEvent")
     *
     * @var string
     */
    public const string POST_DELETE = 'pimcore.version.postDelete';
}
