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

use DateTimeImmutable;
use Pimcore\Bundle\SeoBundle\Config;
use Pimcore\Bundle\SeoBundle\PimcoreSeoBundle;
use Pimcore\Controller\Controller;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Model\Asset;
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
    private const array CUSTOM_ADMIN_ENTRY_POINT_PARAMS = ['token', 'reset', 'deeplink', 'perspective'];

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

    public function robotsTxtAction(Request $request, SiteResolver $siteResolver): Response
    {
        // the site the routing layer resolved for this request
        $siteId = $siteResolver->getSite($request)?->getId() ?? 0;

        // the SEO bundle may be installed but not enabled
        $config = [];
        if (in_array(PimcoreSeoBundle::class, $this->getParameter('kernel.bundles'), true)) {
            $config = Config::getRobotsConfig();
        }

        // check for configured robots.txt in pimcore, an empty one is served as is
        $content = $config[$siteId] ?? null;

        if ($content === null) {
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
        // the parameter (and the login routes) are only defined by pimcore/admin-ui-classic-bundle
        if (!$this->container->get('parameter_bag')->has('pimcore_admin.custom_admin_path_identifier')) {
            throw $this->createNotFoundException('The custom admin entry point needs the admin bundle.');
        }

        // only forward the parameters of the login routes, see LoginController of the admin bundle
        $params = array_filter(
            $request->query->all(),
            fn (mixed $value, string|int $key) => is_scalar($value) && (
                in_array($key, self::CUSTOM_ADMIN_ENTRY_POINT_PARAMS, true)
                || preg_match('/^(document|asset|object)_\d+_[a-z]+$/', (string) $key) // deeplink target
            ),
            ARRAY_FILTER_USE_BOTH
        );

        $url = match (true) {
            isset($params['token']) => $this->generateUrl('pimcore_admin_login_check', $params),
            isset($params['deeplink']) => $this->generateUrl('pimcore_admin_login_deeplink', $params),
            default => $this->generateUrl('pimcore_admin_login', $params)
        };

        $redirect = new RedirectResponse($url);

        $customAdminPathIdentifier = (string) $this->getParameter('pimcore_admin.custom_admin_path_identifier');
        if ($customAdminPathIdentifier !== '' && $request->cookies->get('pimcore_custom_admin') !== $customAdminPathIdentifier) {
            $redirect->headers->setCookie(
                Cookie::create('pimcore_custom_admin', $customAdminPathIdentifier)->withExpires(new DateTimeImmutable('+1 year'))
            );
        }

        return $redirect;
    }
}
