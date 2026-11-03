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

namespace Pimcore\Bundle\SeoBundle\EventListener;

use Pimcore;
use Pimcore\Bundle\SeoBundle\PimcoreSeoBundle;
use Pimcore\Bundle\SeoBundle\Redirect\AutoRedirectCreator;
use Pimcore\Event\Model\DataObject\ClassDefinition\UrlSlugEvent;
use Pimcore\Event\UrlSlugEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UrlSlugUpdateListener implements EventSubscriberInterface
{
    public function __construct(private readonly AutoRedirectCreator $autoRedirectCreator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UrlSlugEvents::POST_SAVE => 'onURLSlugUpdate',
        ];
    }

    public function onURLSlugUpdate(UrlSlugEvent $event): void
    {
        if (!PimcoreSeoBundle::isInstalled()) {
            return;
        }

        $pimcore_seo_redirects = Pimcore::getContainer()->getParameter('pimcore_seo.redirects');
        $data = $event->getData();
        // check for previous slugs and create redirects
        if (!$pimcore_seo_redirects['auto_create_redirects']) {
            return;
        }

        foreach ($data as $slug) {
            $previousSlug = $slug->getPreviousSlug();
            $newSlug = $slug->getSlug();
            // without a new slug the previous one is kept for the next save
            if (!$previousSlug || !$newSlug) {
                continue;
            }

            if ($previousSlug !== $newSlug) {
                $siteId = $slug->getSiteId() ?: null;

                // the new slug is live again, an older auto redirect away from it would shadow it or loop
                $this->autoRedirectCreator->deleteForSource($newSlug, $siteId);
                $this->autoRedirectCreator->createOrUpdate($previousSlug, $newSlug, $siteId, $siteId);
            }

            $slug->setPreviousSlug(null);
        }
    }
}
