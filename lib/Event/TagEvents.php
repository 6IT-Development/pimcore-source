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

final class TagEvents
{
    /**
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    public const string PRE_ADD = 'pimcore.tag.preAdd';

    /**
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    public const string POST_ADD = 'pimcore.tag.postAdd';

    /**
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    public const string PRE_UPDATE = 'pimcore.tag.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    public const string POST_UPDATE = 'pimcore.tag.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    public const string PRE_DELETE = 'pimcore.tag.preDelete';

    /**
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    public const string POST_DELETE = 'pimcore.tag.postDelete';

    /**
     * Arguments:
     *  - elementType
     *  - elementId
     *
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    public const string PRE_ADD_TO_ELEMENT = 'pimcore.tag.preAddToElement';

    /**
     * Arguments:
     *  - elementType
     *  - elementId
     *
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    public const string POST_ADD_TO_ELEMENT = 'pimcore.tag.postAddToElement';

    /**
     * Arguments:
     *  - elementType
     *  - elementId
     *
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    public const string PRE_REMOVE_FROM_ELEMENT = 'pimcore.tag.preRemoveFromElement';

    /**
     * Arguments:
     *  - elementType
     *  - elementId
     *
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    public const string POST_REMOVE_FROM_ELEMENT = 'pimcore.tag.postRemoveFromElement';
}
