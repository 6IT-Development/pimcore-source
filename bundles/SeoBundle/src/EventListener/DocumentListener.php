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

use Doctrine\DBAL\Connection;
use Pimcore;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Bundle\SeoBundle\PimcoreSeoBundle;
use Pimcore\Bundle\SeoBundle\Redirect\AutoRedirectCreator;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Hardlink;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Site;
use Pimcore\Tool\Frontend;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

class DocumentListener implements EventSubscriberInterface
{
    private const array REDIRECT_TARGET_TYPES = ['page', 'hardlink', 'link'];

    public function __construct(
        private readonly AutoRedirectCreator $autoRedirectCreator,
        private readonly Connection $db,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DocumentEvents::POST_DELETE => 'onDocumentDelete',
            DocumentEvents::PAGE_POST_SAVE_ACTION => 'onPagePostSaveAction',
            DocumentEvents::POST_MOVE_ACTION => 'onPostMoveAction',
        ];
    }

    public function onDocumentDelete(DocumentEvent $event): void
    {
        if (!PimcoreSeoBundle::isInstalled()) {
            return;
        }

        $document = $event->getDocument();
        if ($document instanceof Page || $document instanceof Hardlink) {
            // check for redirects pointing to this document, and delete them too
            $redirects = new Redirect\Listing();
            $redirects->setCondition('target = ?', $document->getId());
            $redirects->load();

            foreach ($redirects->getRedirects() as $redirect) {
                $redirect->delete();
            }
        }
    }

    public function onPagePostSaveAction(DocumentEvent $event): void
    {
        if (!PimcoreSeoBundle::isInstalled()) {
            return;
        }

        $page = $event->getDocument();
        $pimcore_seo_redirects = Pimcore::getContainer()->getParameter('pimcore_seo.redirects');
        if ($page instanceof Page && $pimcore_seo_redirects['auto_create_redirects']) {
            $oldPage = $event->getArgument('oldPage');
            // an unpublished page has no live URL to redirect to
            if ($event->getArgument('task') === 'publish') {
                $oldPrettyUrl = $oldPage->getPrettyUrl();
                $prettyUrl = $page->getPrettyUrl();
                if ($oldPrettyUrl && $prettyUrl && $prettyUrl !== $oldPrettyUrl) {
                    // pretty URLs are paths on the domain of the page's site
                    $siteId = Frontend::getSiteForDocument($page)?->getId();
                    $this->autoRedirectCreator->createOrUpdate($oldPrettyUrl, $prettyUrl, $siteId, $siteId);
                }
            }
        }
    }

    public function onPostMoveAction(DocumentEvent $event): void
    {
        if (!PimcoreSeoBundle::isInstalled()) {
            return;
        }

        $document = $event->getDocument();
        $oldDocument = $event->getArgument('oldDocument');
        $oldPath = $event->getArgument('oldPath');
        $this->createRedirectForFormerPath($document, $oldPath, $oldDocument);
    }

    private function createRedirectForFormerPath(Document $document, string $oldPath, Document $oldDocument): void
    {
        $pimcore_seo_redirects = Pimcore::getContainer()->getParameter('pimcore_seo.redirects');
        if (!$pimcore_seo_redirects['auto_create_redirects']) {
            return;
        }

        // an admin user needs the redirects permission, moves without a user (CLI, API, system) create redirects too
        $user = Pimcore\Tool\Admin::getCurrentUser();
        if ($user && !$user->isAllowed('redirects')) {
            return;
        }

        if (!$document instanceof Document\Page && !$document instanceof Document\Hardlink) {
            return;
        }

        $sourceSite = Frontend::getSiteForDocument($oldDocument);
        $targetSite = Frontend::getSiteForDocument($document);

        $redirects = [$this->stripSiteRootPath($oldPath, $sourceSite) => $document->getId()];

        if ($document->hasChildren()) {
            $newPath = $document->getRealFullPath();
            $formerPath = $oldDocument->getRealFullPath();

            // only routable documents, folders, snippets and emails cannot be the target of a redirect
            $list = new Document\Listing();
            $list->setCondition('`path` LIKE :path AND `type` IN (:types)', [
                'path' => $list->escapeLike($newPath) . '/%',
                'types' => self::REDIRECT_TARGET_TYPES,
            ]);

            foreach ($list->loadIdPathList() as $child) {
                $childPath = (string)$child['path'];
                if (!str_starts_with($childPath, $newPath . '/')) {
                    continue;
                }

                $source = $formerPath . substr($childPath, strlen($newPath));
                $redirects[$this->stripSiteRootPath($source, $sourceSite)] = (int)$child['id'];
            }
        }

        // the move itself is already done, a failing redirect must not report it as failed
        try {
            $this->db->transactional(function () use ($redirects, $sourceSite, $targetSite): void {
                $count = 0;
                foreach ($redirects as $source => $targetId) {
                    $source = (string)$source;
                    if ($source === '') {
                        continue;
                    }

                    $this->autoRedirectCreator->createOrUpdate(
                        $source,
                        (string)$targetId,
                        $sourceSite?->getId(),
                        $targetSite?->getId()
                    );

                    if (++$count % 10 === 0) {
                        Pimcore::collectGarbage();
                    }
                }
            });
        } catch (Throwable $e) {
            $this->logger->error('Could not create the redirects for the former path {path} of document {document}', [
                'path' => $oldPath,
                'document' => $document->getId(),
                'exception' => $e,
            ]);
        }
    }

    private function stripSiteRootPath(string $path, ?Site $site): string
    {
        $rootPath = (string)$site?->getRootPath();
        if ($rootPath !== '' && str_starts_with($path, $rootPath)) {
            return substr($path, strlen($rootPath));
        }

        return $path;
    }
}
