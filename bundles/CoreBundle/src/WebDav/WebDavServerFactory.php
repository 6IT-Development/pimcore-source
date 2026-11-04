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

namespace Pimcore\Bundle\CoreBundle\WebDav;

use Doctrine\DBAL\Connection;
use PDO;
use Pimcore\Model\Asset;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Sabre\DAV;
use Sabre\DAV\Browser\Plugin as BrowserPlugin;
use Sabre\DAV\Server;
use Sabre\HTTP;
use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\Sapi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

/**
 * Builds the Sabre WebDAV server for the asset tree. The server does not send the response itself:
 * the caller runs it and returns `$server->httpResponse` through the kernel.
 *
 * @internal
 */
final readonly class WebDavServerFactory
{
    /**
     * Table of the lock backend, created by the migration Version20210505093841
     */
    public const string LOCKS_TABLE = 'webdav_locks';

    public function __construct(
        private Connection $db,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
    ) {
    }

    public function create(Request $request): Server
    {
        $homeDir = Asset::getById(1);
        if (!$homeDir instanceof Asset\Folder) {
            throw new RuntimeException('WebDAV: the asset root folder (ID 1) cannot be loaded.');
        }

        $server = new Server(new Asset\WebDAV\Tree(new Asset\WebDAV\Folder($homeDir)), new class() extends Sapi {
            public static function sendResponse(ResponseInterface $response): void
            {
            }
        });
        $server->httpRequest = $this->createSabreRequest($request);
        $server->setBaseUri($this->urlGenerator->generate('pimcore_webdav', ['path' => '/']));

        // Sabre turns exceptions into DAV error responses, log the unexpected ones
        $server->on('exception', function (Throwable $e): void {
            if (!$e instanceof DAV\Exception) {
                $this->logger->error('WebDAV request failed: {message}', ['message' => $e->getMessage(), 'exception' => $e]);
            }
        });

        $server->addPlugin(new DAV\Locks\Plugin($this->createLockBackend()));

        // browser plugin, read-only: its HTML forms (mkcol, upload) have no CSRF protection and browsers
        // re-send cached HTTP Basic credentials on cross-site POSTs
        $server->addPlugin(new BrowserPlugin(false));

        return $server;
    }

    /**
     * The PDO backend needs a PDO connection (pdo_mysql driver); with another driver (e.g. mysqli) the locks are kept
     * in a file
     */
    private function createLockBackend(): DAV\Locks\Backend\BackendInterface
    {
        $connection = $this->db->getNativeConnection();
        if ($connection instanceof PDO) {
            $backend = new DAV\Locks\Backend\PDO($connection);
            $backend->tableName = self::LOCKS_TABLE;

            return $backend;
        }

        return new DAV\Locks\Backend\File(PIMCORE_SYSTEM_TEMP_DIRECTORY . '/webdav-locks.dat');
    }

    private function createSabreRequest(Request $request): HTTP\Request
    {
        $sabreRequest = Sapi::createFromServerArray($request->server->all());
        $sabreRequest->setBody($request->getContent(true));
        $sabreRequest->setPostData($request->request->all());

        return $sabreRequest;
    }
}
