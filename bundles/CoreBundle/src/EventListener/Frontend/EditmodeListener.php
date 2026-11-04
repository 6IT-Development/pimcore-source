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

use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Document\Editable\EditmodeEditableDefinitionCollector;
use Pimcore\Document\Editmode\EditmodeAssetProviderInterface;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Document;
use Pimcore\Security\User\UserLoader;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Injects the editmode assets into editmode responses. The assets come from the EditmodeAssetProviderInterface
 * implementation of the admin UI; without one, responses are not changed.
 *
 * @internal
 */
class EditmodeListener implements EventSubscriberInterface
{
    use PimcoreContextAwareTrait;

    protected array $contentTypes = [
        'text/html',
    ];

    public function __construct(
        protected EditmodeResolver                           $editmodeResolver,
        protected DocumentResolver                           $documentResolver,
        protected UserLoader                                 $userLoader,
        private readonly EditmodeEditableDefinitionCollector $editableConfigCollector,
        private readonly LoggerInterface                     $logger,
        private readonly ?EditmodeAssetProviderInterface     $assetProvider = null
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return; // only resolve editmode in frontend
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        // trigger this once to make sure it is resolved properly
        // TODO is this needed?
        $this->editmodeResolver->isEditmode($request);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$event->isMainRequest()) {
            return; // only main requests inject editmode assets
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if (!$this->assetProvider || !$this->editmodeResolver->isEditmode($request)) {
            return;
        }

        if (!$this->contentTypeMatches($response)) {
            return;
        }

        $document = $this->documentResolver->getDocument($request);
        if (!$document) {
            return;
        }

        $this->logger->info('Injecting editmode assets into request {request}', [
            'request' => $request->getPathInfo(),
        ]);

        $this->addEditmodeAssets($document, $response);

        // set sameorigin header for editmode responses
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
    }

    protected function contentTypeMatches(Response $response): bool
    {
        if (!$contentType = $response->headers->get('Content-Type')) {
            return true;
        }

        // check for substring as the content type could define attributes (e.g. charset)
        return array_any($this->contentTypes, fn($ct) => str_contains($contentType, $ct));
    }

    /**
     * Inject editmode assets into response HTML
     *
     */
    protected function addEditmodeAssets(Document $document, Response $response): void
    {
        if ($this->assetProvider && Document\Service::isValidType($document->getType())) {
            $html = $response->getContent();

            if (!$html) {
                return;
            }

            $user = $this->userLoader->getUser();

            $htmlElement = preg_match('/<html[^a-zA-Z]?( [^>]+)?>/', $html);
            $headElement = preg_match('/<head[^a-zA-Z]?( [^>]+)?>/', $html);
            $bodyElement = preg_match('/<body[^a-zA-Z]?( [^>]+)?>/', $html);

            $skipCheck = false;

            // if there's no head and no body, create a wrapper including these elements
            // add html headers for snippets in editmode, so there is no problem with javascript
            if (!$headElement && !$bodyElement && !$htmlElement) {
                $html = "<!DOCTYPE html>\n<html>\n<head></head><body>" . $html . '</body></html>';
                $skipCheck = true;
            }

            if ($skipCheck || ($headElement && $bodyElement && $htmlElement)) {
                // there is no session user when editmode is forced (EditmodeResolver::setForceEditmode())
                $headHtml = $this->assetProvider->getHeadHtml($document, $user?->getLanguage() ?: 'en');
                $bodyHtml = "\n\n" . $this->editableConfigCollector->getHtml() . "\n\n";
                $bodyHtml .= $this->assetProvider->getBodyHtml($document);

                $html = $this->insertBefore('</head>', $html, $headHtml);
                $html = $this->insertBefore('</body>', $html, $bodyHtml);

                $response->setContent($html);
            } else {
                $response->setContent('<div style="font-size:30px; font-family: Arial; font-weight:bold; color:red; text-align: center; margin: 40px 0">You have to define a &lt;html&gt;, &lt;head&gt;, &lt;body&gt;<br />HTML-tag in your view/layout markup!</div>');
            }
        }
    }

    private function insertBefore(string $search, string $code, string $insert): string
    {
        $endPosition = strripos($code, $search);

        if (false !== $endPosition) {
            $code = substr_replace($code, $insert . "\n\n" . $search, $endPosition, strlen($search));
        }

        return $code;
    }
}
