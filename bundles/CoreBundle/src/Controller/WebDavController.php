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

use PDO;
use Pimcore\Controller\Controller;
use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Tool\Admin;
use RuntimeException;
use Sabre\DAV;
use Sabre\DAV\Browser\Plugin;
use Sabre\DAV\Server;
use Sabre\HTTP;
use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\Sapi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

/**
 * @internal
 */
class WebDavController extends Controller
{
    public function webdavAction(Request $request): Response
    {
        // fail closed when the project has no (working) `pimcore_webdav` firewall
        if (!Admin::getCurrentUser()) {
            throw new UnauthorizedHttpException('Basic realm="Pimcore WebDAV"', 'Authentication required');
        }

        // setup errors are left to Symfony (logged, error page) instead of an empty 200 response
        $homeDir = Asset::getById(1);
        if (!$homeDir instanceof Asset\Folder) {
            throw new RuntimeException('WebDAV: the asset root folder (ID 1) cannot be loaded.');
        }

        $pdo = Db::get()->getNativeConnection();
        if (!$pdo instanceof PDO) {
            throw new RuntimeException(sprintf(
                'WebDAV locks need a PDO database connection (pdo_mysql driver), got "%s".',
                get_debug_type($pdo)
            ));
        }

        $publicDir = new Asset\WebDAV\Folder($homeDir);
        $objectTree = new Asset\WebDAV\Tree($publicDir);

        // Sabre must not send the response itself, it is returned to Symfony below
        $server = new Server($objectTree, new class() extends Sapi {
            public static function sendResponse(ResponseInterface $response): void
            {
            }
        });
        $server->httpRequest = $this->createSabreRequest($request);
        $server->setBaseUri($this->generateUrl('pimcore_webdav', ['path' => '/']));

        // Sabre turns exceptions into DAV error responses, log the unexpected ones
        $server->on('exception', function (Throwable $e) {
            if (!$e instanceof DAV\Exception) {
                Logger::error('WebDAV request failed: {message}', ['message' => $e->getMessage(), 'exception' => $e]);
            }
        });

        // lock plugin
        $lockBackend = new DAV\Locks\Backend\PDO($pdo);
        $lockBackend->tableName = 'webdav_locks';

        $lockPlugin = new DAV\Locks\Plugin($lockBackend);
        $server->addPlugin($lockPlugin);

        // browser plugin, read-only: its HTML forms (mkcol, upload) have no CSRF protection and browsers
        // re-send cached HTTP Basic credentials on cross-site POSTs
        $server->addPlugin(new Plugin(false));

        $server->start();

        return $this->createResponse($server->httpResponse);
    }

    private function createSabreRequest(Request $request): HTTP\Request
    {
        $sabreRequest = Sapi::createFromServerArray($request->server->all());
        $sabreRequest->setBody($request->getContent(true));
        $sabreRequest->setPostData($request->request->all());

        return $sabreRequest;
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
