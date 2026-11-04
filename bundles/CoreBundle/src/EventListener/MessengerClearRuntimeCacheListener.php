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

use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;

/**
 * Static state set while handling one message (caches, the current site, admin mode, unpublished and inheritance
 * flags) must not leak into the next one. Messenger's ResetServicesListener only resets services.
 *
 * @internal
 */
final class MessengerClearRuntimeCacheListener implements EventSubscriberInterface
{
    /**
     * The static flags before the first message, i.e. the defaults of the worker process
     *
     * @var array{adminMode: bool, documentHideUnpublished: bool, objectHideUnpublished: bool, inheritedValues: bool, fallbackValues: bool}|null
     */
    private ?array $defaultFlags = null;

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageReceivedEvent::class => 'handle',
        ];
    }

    public function handle(WorkerMessageReceivedEvent $event): void
    {
        RuntimeCache::clear();
        Asset\Dao::resetThumbnailStatusCache();
        // a message must not run inside the site of a document rendered by a previous one
        Site::resetCurrentSite();
        $this->restoreDefaultFlags();
    }

    private function restoreDefaultFlags(): void
    {
        if ($this->defaultFlags === null) {
            $this->defaultFlags = [
                'adminMode' => Pimcore::inAdmin(),
                'documentHideUnpublished' => Document::doHideUnpublished(),
                'objectHideUnpublished' => DataObject::doHideUnpublished(),
                'inheritedValues' => DataObject::getGetInheritedValues(),
                'fallbackValues' => DataObject\Localizedfield::getGetFallbackValues(),
            ];

            return;
        }

        $this->defaultFlags['adminMode'] ? Pimcore::setAdminMode() : Pimcore::unsetAdminMode();
        Document::setHideUnpublished($this->defaultFlags['documentHideUnpublished']);
        DataObject::setHideUnpublished($this->defaultFlags['objectHideUnpublished']);
        DataObject::setGetInheritedValues($this->defaultFlags['inheritedValues']);
        DataObject\Localizedfield::setGetFallbackValues($this->defaultFlags['fallbackValues']);
    }
}
