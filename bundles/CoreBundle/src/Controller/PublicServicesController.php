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

use Pimcore\Bundle\SeoBundle\Config;
use Pimcore\Controller\Controller;
use Pimcore\Model\Asset;
use Pimcore\Model\Site;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
class PublicServicesController extends Controller
{
    public function thumbnailAction(Request $request): StreamedResponse
    {
        $filename = $request->attributes->getString('filename');
        $requestedFileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $config = [
            'prefix' => $request->attributes->getString('prefix'),
            'type' => $request->attributes->getString('type'),
            'asset_id' => $request->attributes->getInt('assetId'),
            'thumbnail_name' => $request->attributes->getString('thumbnailName'),
            'filename' => $filename,
            'file_extension' => $requestedFileExtension,
        ];

        // generation errors are left to Symfony (logged with context, 500 without caching headers)
        $response = Asset\Service::getStreamedResponseForThumbnail($config, $request->getPathInfo());
        if (!$response) {
            // unknown asset or thumbnail config, prefix not matching the asset path, unsupported variant
            throw $this->createNotFoundException(sprintf('Unable to find the %s thumbnail "%s".', $config['type'], $request->getPathInfo()));
        }

        return $response;
    }

    public function robotsTxtAction(Request $request): Response
    {
        // check for site
        $domain = Tool::getHostname();
        $site = Site::getByDomain($domain);

        $config = [];

        if (class_exists(Config::class)) {
            $config = Config::getRobotsConfig();
        }

        $siteId = 0;
        if ($site instanceof Site) {
            $siteId = $site->getId();
        }

        // check for configured robots.txt in pimcore
        $content = '';
        if (array_key_exists($siteId, $config)) {
            $content = $config[$siteId];
        }

        if (empty($content)) {
            // default behavior, allow robots to index everything
            $content = "User-agent: *\nDisallow:";
        }

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function commonFilesAction(Request $request): Response
    {
        return new Response("HTTP/1.1 404 Not Found\nFiltered by common files filter", 404);
    }

    public function customAdminEntryPointAction(Request $request): RedirectResponse
    {
        $params = $request->query->all();

        $url = match (true) {
            isset($params['token']) => $this->generateUrl('pimcore_admin_login_check', $params),
            isset($params['deeplink']) => $this->generateUrl('pimcore_admin_login_deeplink', $params),
            default => $this->generateUrl('pimcore_admin_login', $params)
        };

        $redirect = new RedirectResponse($url);

        $customAdminPathIdentifier = $this->getParameter('pimcore_admin.custom_admin_path_identifier');
        if (!empty($customAdminPathIdentifier) && $request->cookies->get('pimcore_custom_admin') != $customAdminPathIdentifier) {
            $redirect->headers->setCookie(new Cookie('pimcore_custom_admin', $customAdminPathIdentifier, strtotime('+1 year')));
        }

        return $redirect;
    }
}
