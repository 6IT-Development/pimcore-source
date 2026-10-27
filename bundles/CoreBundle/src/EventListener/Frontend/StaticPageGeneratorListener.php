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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use DateTimeInterface;
use Exception;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\StaticPageContextAwareTrait;
use Pimcore\Cache\FullPage\SessionStatus;
use Pimcore\Config;
use Pimcore\Document\StaticPageGenerator;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Logger;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Site;
use Pimcore\Tool\Storage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @internal
 */
class StaticPageGeneratorListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;
    use StaticPageContextAwareTrait;

    public function __construct(
        protected StaticPageGenerator $staticPageGenerator,
        protected DocumentResolver    $documentResolver,
        protected RequestHelper       $requestHelper,
        private readonly Config       $config,
        private readonly SessionStatus $sessionStatus,
        private readonly ?TokenStorageInterface $tokenStorage = null
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DocumentEvents::POST_ADD => 'onPostAddUpdateDeleteDocument',
            DocumentEvents::POST_DELETE => 'onPostDeleteDocument',
            DocumentEvents::POST_UPDATE => 'onPostAddUpdateDeleteDocument',
            // this must run after the firewall (8) so access_control applies, and after the maintenance page
            // listener (126), but before the targeting listener
            KernelEvents::REQUEST => ['onKernelRequest', 7],
            KernelEvents::RESPONSE => ['onKernelResponse', -120], //this must run after code injection listeners
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->checkValidRequest($request)
            || !$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $config = $this->config['documents'];
        if (!$config['static_page_router']['enabled']) {
            return;
        }

        $routePattern = $config['static_page_router']['route_pattern'];
        if (!empty($routePattern) && !@preg_match($routePattern, $request->getPathInfo())) {
            return;
        }

        $storage = Storage::get('document_static');

        try {
            $path = '';
            $filename = urldecode($request->getPathInfo());

            if (Site::isSiteRequest()) {
                if ($request->getPathInfo() === '/') {
                    $filename = '/' . Site::getCurrentSite()->getRootDocument()->getKey();
                } else {
                    $path = Site::getCurrentSite()->getRootPath();
                }
            }
            $filename = $path . $filename . '.html';

            if ($storage->fileExists($filename)) {
                $content = $storage->read($filename);
                $date = date(DateTimeInterface::ATOM, $storage->lastModified($filename));

                $reponse = new Response(
                    $content, Response::HTTP_OK, [
                        'Content-Type' => 'text/html',
                        'X-Pimcore-Static-Page-Last-Modified' => $date,
                    ]
                );

                $event->setResponse($reponse);
            }
        } catch (Exception $e) {
            Logger::error($e->getMessage());
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->checkValidRequest($request)) {
            return;
        }

        //return if request is from StaticPageGenerator
        if ($request->attributes->has('pimcore_static_page_generator')) {
            return;
        }

        // only inject analytics code on non-admin requests
        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)
            && !$this->matchesStaticPageContext($request)) {
            return;
        }

        $document = $this->documentResolver->getDocument();

        if ($document instanceof Page && $document->getStaticGeneratorEnabled()
            && $this->responseCanBeStored($request, $event->getResponse())) {
            $response = $event->getResponse()->getContent();
            $this->staticPageGenerator->generate($document, ['response' => $response]);
        }
    }

    /**
     * The stored file is served to every visitor, so only store responses that contain no visitor specific
     * content: no errors (the error page would be stored for the requested document), no cookies, no session
     * data, no authenticated user and no query parameters (e.g. output timestamp override or preview).
     */
    private function responseCanBeStored(Request $request, Response $response): bool
    {
        if ($response->getStatusCode() !== Response::HTTP_OK
            || $response instanceof StreamedResponse
            || $response instanceof BinaryFileResponse
            || $response->getContent() === false
            || $response->headers->hasCacheControlDirective('no-store')
            || $response->headers->getCookies()) {
            return false;
        }

        if ($request->query->count() > 0) {
            return false;
        }

        if ($this->sessionStatus->isDisabledBySession($request)) {
            return false;
        }

        if ($this->tokenStorage?->getToken()?->getUser()) {
            return false;
        }

        return true;
    }

    public function onPostAddUpdateDeleteDocument(DocumentEvent $e): void
    {
        $document = $e->getDocument();

        if ($e->hasArgument('saveVersionOnly') || $e->hasArgument('autoSave')) {
            return;
        }

        // after a move/rename the pages of the document and all its descendants are still stored under the old
        // path and would be served at the old URLs (no event is dispatched for the descendants)
        $oldPath = $e->hasArgument('oldPath') ? $e->getArgument('oldPath') : null;
        if (is_string($oldPath)) {
            try {
                $this->staticPageGenerator->removeTree($oldPath);
            } catch (Exception $exception) {
                Logger::error((string)$exception);
            }
        }

        if ($document instanceof PageSnippet) {
            try {
                if ($document->getStaticGeneratorEnabled()
                    || $this->staticPageGenerator->pageExists($document)) {
                    $this->staticPageGenerator->remove($document);
                }
            } catch (Exception $e) {
                Logger::error((string)$e);

                return;
            }
        }
    }

    public function onPostDeleteDocument(DocumentEvent $e): void
    {
        $this->onPostAddUpdateDeleteDocument($e);

        // descendants are deleted together with the document, without their own events
        try {
            $this->staticPageGenerator->removeTree($e->getDocument()->getRealFullPath());
        } catch (Exception $exception) {
            Logger::error((string)$exception);
        }
    }

    private function checkValidRequest(Request $request): bool
    {
        if ($this->requestHelper->isFrontendRequestByAdmin($request)
            || $request->isXmlHttpRequest()
            || $request->getMethod() !== 'GET'
            || !in_array('text/html', $request->getAcceptableContentTypes())) {
            return false;
        }

        return true;
    }
}
