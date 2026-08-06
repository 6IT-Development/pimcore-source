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

namespace Pimcore\Bundle\ApplicationLoggerBundle\Controller;

use Carbon\Carbon;
use DateMalformedStringException;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Pimcore\Bundle\AdminBundle\Helper\QueryParams;
use Pimcore\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;
use Pimcore\Controller\KernelControllerEventInterface;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Pimcore\Tool\Storage;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class LogController extends UserAwareController implements KernelControllerEventInterface
{
    use JsonHelperTrait;

    private const string PERMISSION = 'application_logging';

    private const int DEFAULT_LIMIT = 50;

    private const string DATE_TIME_PATTERN = '/^(?P<date>\d{4}\-\d{2}\-\d{2})T(?P<time>\d{2}:\d{2}:\d{2})$/';

    public function onKernelControllerEvent(ControllerEvent $event): void
    {
        if (!$this->getPimcoreUser()->isAllowed(self::PERMISSION)) {
            throw new AccessDeniedHttpException(sprintf("Permission denied, user needs '%s' permission.", self::PERMISSION));
        }
    }

    #[Route('/log/show', name: 'pimcore_admin_bundle_applicationlogger_log_show', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function showAction(
        Request    $request,
        Connection $db
    ): JsonResponse
    {
        $this->checkPermission(self::PERMISSION);

        $requestSource = $request->isMethod(Request::METHOD_GET) ? $request->query : $request->request;

        $qb = $db->createQueryBuilder();
        $qb
            ->select('*')
            ->from(ApplicationLoggerDb::TABLE_NAME)
            ->setFirstResult($requestSource->getInt('start'))
            ->setMaxResults($requestSource->getInt('limit', self::DEFAULT_LIMIT))
            ->orderBy('id', 'DESC');

        if (class_exists(QueryParams::class)) {
            $sortingSettings = QueryParams::extractSortingSettings(array_merge(
                $request->request->all(),
                $request->query->all()
            ));

            if ($sortingSettings['orderKey']) {
                $qb->orderBy($db->quoteIdentifier($sortingSettings['orderKey']), $sortingSettings['order']);
            }
        }

        if ('' !== $priority = $requestSource->getString('priority')) {
            $qb->andWhere($qb->expr()->eq('priority', ':priority'));
            $qb->setParameter('priority', $priority);
        }

        if ($fromDate = $this->parseDateObject($requestSource->getString('fromDate'), $requestSource->getString('fromTime'))) {
            $qb->andWhere('timestamp > :fromDate');
            $qb->setParameter('fromDate', $fromDate, Types::DATETIME_MUTABLE);
        }

        if ($toDate = $this->parseDateObject($requestSource->getString('toDate'), $requestSource->getString('toTime'))) {
            $qb->andWhere('timestamp <= :toDate');
            $qb->setParameter('toDate', $toDate, Types::DATETIME_MUTABLE);
        }

        if ('' !== $component = $requestSource->getString('component')) {
            $qb->andWhere('component = ' . $qb->createNamedParameter($component));
        }

        if ('' !== $relatedObject = $requestSource->getString('relatedobject')) {
            $qb->andWhere('relatedobject = ' . $qb->createNamedParameter($relatedObject));
        }

        if ('' !== $message = $requestSource->getString('message')) {
            $qb->andWhere('message LIKE ' . $qb->createNamedParameter('%' . $message . '%'));
        }

        if (0 !== $pid = $requestSource->getInt('pid')) {
            $qb->andWhere('pid LIKE ' . $qb->createNamedParameter('%' . $pid . '%'));
        }

        $totalQb = clone $qb;
        $total = (int)$totalQb
            ->select('COUNT(id)')
            ->setMaxResults(null)
            ->setFirstResult(0)
            ->fetchOne();

        return $this->jsonResponse([
            'p_totalCount' => $total,
            'p_results' => array_map(
                static fn(array $row): array => [
                    'id' => $row['id'],
                    'pid' => $row['pid'],
                    'message' => $row['message'],
                    'date' => $row['timestamp'],
                    'timestamp' => new Carbon($row['timestamp'], 'UTC')->getTimestamp(),
                    'priority' => $row['priority'],
                    'fileobject' => $row['fileobject'] ? str_replace(PIMCORE_PROJECT_ROOT, '', $row['fileobject']) : null,
                    'relatedobject' => $row['relatedobject'],
                    'relatedobjecttype' => $row['relatedobjecttype'],
                    'component' => $row['component'],
                    'source' => $row['source'],
                ],
                $qb->fetchAllAssociative()
            ),
        ]);
    }

    #[Route('/log/priority-json', name: 'pimcore_admin_bundle_applicationlogger_log_priorityjson', methods: [Request::METHOD_GET])]
    public function priorityJsonAction(): JsonResponse
    {
        $this->checkPermission(self::PERMISSION);

        $priorities = [['key' => '', 'value' => '-']];
        foreach (ApplicationLoggerDb::getPriorities() as $key => $priority) {
            $priorities[] = ['key' => $key, 'value' => $priority];
        }

        return $this->jsonResponse(['priorities' => $priorities]);
    }

    #[Route('/log/component-json', name: 'pimcore_admin_bundle_applicationlogger_log_componentjson', methods: [Request::METHOD_GET])]
    public function componentJsonAction(): JsonResponse
    {
        $this->checkPermission(self::PERMISSION);

        $components = [['key' => '', 'value' => '-']];
        foreach (ApplicationLoggerDb::getComponents() as $component) {
            $components[] = ['key' => $component, 'value' => $component];
        }

        return $this->jsonResponse(['components' => $components]);
    }

    #[Route('/log/show-file-object', name: 'pimcore_admin_bundle_applicationlogger_log_showfileobject', methods: [Request::METHOD_GET])]
    public function showFileObjectAction(Request $request): StreamedResponse
    {
        $this->checkPermission(self::PERMISSION);

        $filePath = $request->query->getString('filePath');
        $storage = Storage::get('application_log');

        if (!$storage->fileExists($filePath)) {
            throw new FileNotFoundException($filePath);
        }

        $fileData = $storage->readStream($filePath);

        return new StreamedResponse(
            static function () use ($fileData): void {
                fpassthru($fileData);
                fclose($fileData);
            },
            Response::HTTP_OK,
            ['Content-Type' => 'text/plain']
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    private function parseDateObject(string $date, string $time): ?DateTime
    {
        if (!preg_match(self::DATE_TIME_PATTERN, $date, $dateMatches)) {
            return null;
        }

        if ('' !== $time && preg_match(self::DATE_TIME_PATTERN, $time, $timeMatches)) {
            return new DateTime(sprintf('%sT%s', $dateMatches['date'], $timeMatches['time']));
        }

        return new DateTime($date);
    }
}
