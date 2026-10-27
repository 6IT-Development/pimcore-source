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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Event\AssetEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Element\Tag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
class ElementTagsListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::POST_COPY => 'onPostCopy',
            DocumentEvents::POST_COPY => 'onPostCopy',
            AssetEvents::POST_COPY => 'onPostCopy',

            // tags_assignment has no foreign keys, so the assignments of deleted elements are removed here.
            // The recycle bin keeps the tags and restores them.
            DataObjectEvents::POST_DELETE => ['onPostDelete', -9999],
            DocumentEvents::POST_DELETE => ['onPostDelete', -9999],
            AssetEvents::POST_DELETE => ['onPostDelete', -9999],
        ];
    }

    public function onPostCopy(ElementEventInterface $e): void
    {
        if (!$e->hasArgument('base_element')) {
            return;
        }

        $baseElement = $e->getArgument('base_element');
        if (!$baseElement instanceof ElementInterface) {
            return;
        }

        $elementType = Service::getElementType($e->getElement());
        $copiedElement = $e->getElement();
        Tag::setTagsForElement(
            $elementType,
            $copiedElement->getId(),
            Tag::getTagsForElement($elementType, $baseElement->getId())
        );
    }

    public function onPostDelete(ElementEventInterface $e): void
    {
        $element = $e->getElement();
        Tag::setTagsForElement(Service::getElementType($element), $element->getId(), []);
    }
}
