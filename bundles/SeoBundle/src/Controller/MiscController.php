<?php

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

use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Pimcore\Db;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Attribute\Route;

class MiscController extends UserAwareController
{
    use JsonHelperTrait;

    #[Route('/http-error-log', name: 'pimcore_bundle_seo_misc_httperrorlog', methods: [Request::METHOD_POST])]
    public function httpErrorLogAction(Request $request): JsonResponse
    {
        $this->checkPermission('http_errors');

        $db = Db::get();

        $limit = max(1, min($request->request->getInt('limit') ?: 20, 1000));
        $offset = max(0, $request->request->getInt('start'));
        $sortInfo = json_decode($request->request->getString('sort'), true);
        $sortInfo = is_array($sortInfo) ? ($sortInfo[0] ?? null) : null;
        $sort = is_array($sortInfo) ? ($sortInfo['property'] ?? null) : null;
        $dir = is_array($sortInfo) ? ($sortInfo['direction'] ?? null) : null;
        $filter = $request->request->getString('filter');
        if (!in_array($sort, ['code', 'uri', 'date', 'count'], true)) {
            $sort = 'count';
        }
        if (!in_array($dir, ['DESC', 'ASC'], true)) {
            $dir = 'DESC';
        }

        $condition = '';
        if ($filter) {
            $filter = $db->quote('%' . $filter . '%');

            $conditionParts = [];
            foreach (['uri', 'code', 'parametersGet'] as $field) {
                $conditionParts[] = $field . ' LIKE ' . $filter;
            }
            $condition = ' WHERE ' . implode(' OR ', $conditionParts);
        }

        $logs = $db->fetchAllAssociative('SELECT code,uri,`count`,date FROM http_error_log ' . $condition . ' ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit);
        $total = $db->fetchOne('SELECT count(*) FROM http_error_log ' . $condition);

        return $this->jsonResponse([
            'items' => $logs,
            'total' => $total,
            'success' => true,
        ]);
    }

    #[Route('/http-error-log-detail', name: 'pimcore_bundle_seo_misc_httperrorlogdetail', methods: [Request::METHOD_GET])]
    public function httpErrorLogDetailAction(
        Request   $request,
        ?Profiler $profiler
    ): Response
    {
        $this->checkPermission('http_errors');

        $profiler?->disable();

        $db = Db::get();
        $data = $db->fetchAssociative('SELECT * FROM http_error_log WHERE uri_hash = ?', [hash('sha256', $request->query->getString('uri'))]);

        if (!$data) {
            throw $this->createNotFoundException();
        }

        $data['parametersGet'] = $this->decodeParameters($data['parametersGet']);

        return $this->render('@PimcoreSeo/misc/http_error_log_detail.html.twig', ['data' => $data]);
    }

    #[Route('/http-error-log-flush', name: 'pimcore_bundle_seo_misc_httperrorlogflush', methods: [Request::METHOD_DELETE])]
    public function httpErrorLogFlushAction(Request $request): JsonResponse
    {
        $this->checkPermission('http_errors');

        $db = Db::get();
        $db->executeQuery('TRUNCATE TABLE http_error_log');

        return $this->jsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @return array<string, scalar|null>
     */
    private function decodeParameters(?string $parameters): array
    {
        if (!$parameters) {
            return [];
        }

        $decoded = json_decode($parameters, true);
        if (!is_array($decoded)) {
            // entries from before the switch to JSON, the maintenance task purges them after a few days
            $decoded = @unserialize($parameters, ['allowed_classes' => false]);
        }

        if (!is_array($decoded)) {
            return [];
        }

        // nested parameters like a[b]=c cannot be rendered as a table cell
        return array_map(
            static fn (mixed $value): mixed => is_array($value) ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $value,
            $decoded
        );
    }
}
