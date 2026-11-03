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

namespace Pimcore\Bundle\SeoBundle\Controller;

use Pimcore\Bundle\SeoBundle\Config;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Pimcore\Model\Site;
use Pimcore\Model\Tool\SettingsStore;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

class SettingsController extends UserAwareController
{
    use JsonHelperTrait;

    private const string ROBOTS_SCOPE = 'robots.txt';

    private const string ROBOTS_ID_PREFIX = 'robots.txt-';

    #[Route('/robots-txt', name: 'pimcore_bundle_seo_settings_robotstxtget', methods: [Request::METHOD_GET])]
    public function robotsTxtGetAction(): JsonResponse
    {
        $this->checkPermission('robots.txt');

        $config = Config::getRobotsConfig();

        return $this->jsonResponse([
            'success' => true,
            'data' => $config,
            'onFileSystem' => file_exists(PIMCORE_WEB_ROOT . '/robots.txt'),
        ]);
    }

    #[Route('/robots-txt', name: 'pimcore_bundle_seo_settings_robotstxtput', methods: [Request::METHOD_PUT])]
    public function robotsTxtPutAction(Request $request): JsonResponse
    {
        $this->checkPermission('robots.txt');

        $values = $request->request->all('data');

        // 0 is the main site, the same key the robots.txt route looks up
        $validSiteIds = ['0' => true];
        foreach (new Site\Listing()->load() as $site) {
            $validSiteIds[(string)$site->getId()] = true;
        }

        foreach ($values as $siteId => $robotsContent) {
            if (!isset($validSiteIds[(string)$siteId])) {
                throw new BadRequestHttpException(sprintf('Unknown site "%s"', $siteId));
            }

            if (!is_string($robotsContent)) {
                throw new BadRequestHttpException(sprintf('The robots.txt of site "%s" must be a string', $siteId));
            }
        }

        foreach ($values as $siteId => $robotsContent) {
            SettingsStore::set(self::ROBOTS_ID_PREFIX . $siteId, $robotsContent, SettingsStore::TYPE_STRING, self::ROBOTS_SCOPE);
        }

        // entries of deleted sites and invalid keys stored earlier
        foreach (SettingsStore::getIdsByScope(self::ROBOTS_SCOPE) as $id) {
            if (!isset($validSiteIds[substr($id, strlen(self::ROBOTS_ID_PREFIX))])) {
                SettingsStore::delete($id, self::ROBOTS_SCOPE);
            }
        }

        Config::resetRobotsConfig();

        return $this->jsonResponse([
            'success' => true,
        ]);
    }
}
