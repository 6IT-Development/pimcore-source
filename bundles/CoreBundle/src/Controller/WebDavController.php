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

namespace Pimcore\Bundle\CoreBundle\Controller;

use Pimcore\Bundle\CoreBundle\WebDav\WebDavServerFactory;
use Pimcore\Controller\Controller;
use Pimcore\Tool\Admin;
use Sabre\HTTP;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @internal
 */
class WebDavController extends Controller
{
    public function webdavAction(Request $request, WebDavServerFactory $serverFactory): Response
    {
        // fail closed when the project has no (working) `pimcore_webdav` firewall
        if (!Admin::getCurrentUser()) {
            throw new UnauthorizedHttpException('Basic realm="Pimcore WebDAV"', 'Authentication required');
        }

        // setup errors are left to Symfony (logged, error page) instead of an empty 200 response
        $server = $serverFactory->create($request);
        $server->start();

        return $this->createResponse($server->httpResponse);
    }

    private function createResponse(HTTP\Response $sabreResponse): Response
    {
        $body = $sabreResponse->getBody();
        $contentLength = $sabreResponse->getHeader('Content-Length');

        return new StreamedResponse(
            static function () use ($body, $contentLength) {
                if (is_callable($body)) {
                    $body();
                } elseif (is_resource($body)) {
                    $output = fopen('php://output', 'wb');
                    stream_copy_to_stream($body, $output, $contentLength !== null ? (int) $contentLength : null);
                    fclose($output);
                    fclose($body);
                } elseif ($body !== null) {
                    echo $body;
                }
            },
            $sabreResponse->getStatus(),
            $sabreResponse->getHeaders()
        );
    }
}
