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
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\PathTraversalDetected;
use Pimcore\Bundle\ApplicationLoggerBundle\ApplicationLogRepository;
use Pimcore\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;
use Pimcore\Bundle\ApplicationLoggerBundle\Request\LogGridRequest;
use Pimcore\Controller\KernelControllerEventInterface;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class LogController extends UserAwareController implements KernelControllerEventInterface
{
    use JsonHelperTrait;

    private const string PERMISSION = 'application_logging';

    private const array SORTABLE_COLUMNS = [
        'id', 'pid', 'timestamp', 'priority', 'component', 'relatedobject', 'source', 'message', 'fileobject',
    ];

    /**
     * The only permission check, it runs for every action of this controller and before their arguments are resolved
     */
    #[\Override]
    public function onKernelControllerEvent(ControllerEvent $event): void
    {
        $this->checkPermission(self::PERMISSION);
    }

    #[Route('/log/show', name: 'pimcore_admin_bundle_applicationlogger_log_show', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function showAction(
        Request         $request,
        Connection      $db,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        ?LogGridRequest $query = null,
        #[MapRequestPayload]
        ?LogGridRequest $payload = null
    ): JsonResponse
    {
        $params = ($request->isMethod(Request::METHOD_GET) ? $query : $payload) ?? new LogGridRequest();

        $qb = $db->createQueryBuilder();
        $qb
            ->select('*')
            ->from(ApplicationLoggerDb::TABLE_NAME)
            ->setFirstResult($params->start)
            ->setMaxResults($params->limit)
            ->orderBy('id', 'DESC');

        // ExtJS sends the sorters as JSON: [{"property": "...", "direction": "ASC|DESC"}]
        $sorters = json_decode($query?->sort ?: $payload?->sort ?? '', true);
        if (is_array($sorters) && in_array($sorters[0]['property'] ?? null, self::SORTABLE_COLUMNS, true)) {
            $direction = 'DESC' === strtoupper((string)($sorters[0]['direction'] ?? '')) ? 'DESC' : 'ASC';
            $qb->orderBy($db->quoteIdentifier($sorters[0]['property']), $direction);
        }

        if ('' !== $params->priority) {
            $qb->andWhere($qb->expr()->eq('priority', ':priority'));
            $qb->setParameter('priority', $params->priority);
        }

        $timeZone = $this->resolveTimeZone($params->timezone);

        if ($fromDate = $this->parseDateObject($params->fromDate, $params->fromTime, $timeZone)) {
            $qb->andWhere('timestamp > :fromDate');
            $qb->setParameter('fromDate', $fromDate, Types::DATETIME_IMMUTABLE);
        }

        if ($toDate = $this->parseDateObject($params->toDate, $params->toTime, $timeZone)) {
            $qb->andWhere('timestamp <= :toDate');
            $qb->setParameter('toDate', $toDate, Types::DATETIME_IMMUTABLE);
        }

        if ('' !== $params->component) {
            $qb->andWhere('component = ' . $qb->createNamedParameter($params->component));
        }

        if ('' !== $params->relatedobject) {
            $qb->andWhere('relatedobject = ' . $qb->createNamedParameter($params->relatedobject));
        }

        if ('' !== $params->message) {
            $qb->andWhere('message LIKE ' . $qb->createNamedParameter('%' . addcslashes($params->message, '%_\\') . '%'));
        }

        if ($params->pid) {
            $qb->andWhere('pid = ' . $qb->createNamedParameter($params->pid, ParameterType::INTEGER));
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
    public function priorityJsonAction(ApplicationLogRepository $repository): JsonResponse
    {
        $priorities = [['key' => '', 'value' => '-']];
        foreach ($repository->getPriorities() as $key => $priority) {
            $priorities[] = ['key' => $key, 'value' => $priority];
        }

        return $this->jsonResponse(['priorities' => $priorities]);
    }

    #[Route('/log/component-json', name: 'pimcore_admin_bundle_applicationlogger_log_componentjson', methods: [Request::METHOD_GET])]
    public function componentJsonAction(ApplicationLogRepository $repository): JsonResponse
    {
        $components = [['key' => '', 'value' => '-']];
        foreach ($repository->getComponents() as $component) {
            $components[] = ['key' => $component, 'value' => $component];
        }

        return $this->jsonResponse(['components' => $components]);
    }

    #[Route('/log/show-file-object', name: 'pimcore_admin_bundle_applicationlogger_log_showfileobject', methods: [Request::METHOD_GET])]
    public function showFileObjectAction(
        Request            $request,
        #[Autowire(service: 'pimcore.application_log.storage')]
        FilesystemOperator $storage
    ): StreamedResponse
    {
        $filePath = $request->query->getString('filePath');
        if ('' === $filePath) {
            throw new BadRequestHttpException('Parameter "filePath" is missing.');
        }

        try {
            if (!$storage->fileExists($filePath)) {
                throw new NotFoundHttpException(sprintf('File object "%s" not found.', $filePath));
            }
        } catch (PathTraversalDetected $e) {
            throw new BadRequestHttpException(sprintf('Invalid file object path "%s".', $filePath), $e);
        } catch (FilesystemException $e) {
            throw new NotFoundHttpException(sprintf('File object "%s" could not be read.', $filePath), $e);
        }

        // basename() only splits at "/", makeDisposition() rejects both separators and a non-ASCII or "%" fallback
        $fileName = basename(str_replace('\\', '/', $filePath));

        return new StreamedResponse(
            static function () use ($storage, $filePath): void {
                $fileData = $storage->readStream($filePath);
                fpassthru($fileData);
                fclose($fileData);
            },
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Disposition' => HeaderUtils::makeDisposition(
                    HeaderUtils::DISPOSITION_INLINE,
                    $fileName,
                    preg_replace('/[^\x20-\x7e]|%/', '_', $fileName)
                ),
            ]
        );
    }

    /**
     * The date/time fields send the browser's local time without an offset, the client sends its time zone along
     */
    private function resolveTimeZone(string $timeZone): DateTimeZone
    {
        if (in_array($timeZone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
            return new DateTimeZone($timeZone);
        }

        return new DateTimeZone(date_default_timezone_get());
    }

    /**
     * Returns the date in UTC, the time zone the log entries are stored in, or null if it is not a valid date
     */
    private function parseDateObject(string $date, string $time, DateTimeZone $timeZone): ?DateTimeImmutable
    {
        if (!preg_match(LogGridRequest::DATE_TIME_PATTERN, $date, $dateMatches)) {
            return null;
        }

        if (preg_match(LogGridRequest::DATE_TIME_PATTERN, $time, $timeMatches)) {
            $date = sprintf('%sT%s', $dateMatches['date'], $timeMatches['time']);
        }

        $dateTime = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $date, $timeZone);

        // createFromFormat() only warns about values like 2026-02-31 and moves them to the next month
        if (false === $dateTime || false !== DateTimeImmutable::getLastErrors()) {
            return null;
        }

        return $dateTime->setTimezone(new DateTimeZone('UTC'));
    }
}
