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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Configuration\ExecutionContextInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\CurrentMessage\CurrentMessageProviderInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\JobNotFoundException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\PermissionException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Security\PermissionServiceInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Constants\PermissionConstants;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Constants\TableConstants;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\UserInterface;
use Pimcore\Translation\Translator;
use Psr\Log\LoggerInterface;

final readonly class JobRunRepository implements JobRunRepositoryInterface
{
    public function __construct(
        private Connection                      $db,
        private CurrentMessageProviderInterface $currentMessageProvider,
        private EntityManagerInterface          $pimcoreEntityManager,
        private ExecutionContextInterface       $executionContext,
        private LoggerInterface                 $genericExecutionEngineLogger,
        private PermissionServiceInterface      $permissionService,
        private Translator                      $translator,
    )
    {
    }

    public function createFromJob(Job $job, ?int $ownerId = null): JobRun
    {
        $jobRun = new JobRun($ownerId);

        $jobRun->setJob($job);

        $this->pimcoreEntityManager->persist($jobRun);
        $this->pimcoreEntityManager->flush();

        return $jobRun;
    }

    public function update(JobRun $jobRun): JobRun
    {
        // the job is no mapped field: changing it (e.g. the selection) alone would not trigger PreUpdate, which
        // serializes it, so the flush would skip the run
        $jobRun->serializeJob();

        $this->pimcoreEntityManager->persist($jobRun);
        $this->pimcoreEntityManager->flush();

        return $jobRun;
    }

    /**
     * @throws Exception
     */
    public function incrementProcessedElementsForStep(JobRun $jobRun, int $step): ?int
    {
        $processedElements = $this->db->transactional(function (Connection $db) use ($jobRun, $step): ?int {
            $affectedRows = $db->executeStatement(
                'UPDATE ' . TableConstants::JOB_RUN_TABLE .
                ' SET processedElementsForStep = processedElementsForStep + 1' .
                ' WHERE id = :id AND currentStep = :step',
                ['id' => $jobRun->getId(), 'step' => $step]
            );

            if ($affectedRows === 0) {
                return null;
            }

            // the row stays locked by the update until the commit, so this reads our own increment
            return (int) $db->fetchOne(
                'SELECT processedElementsForStep FROM ' . TableConstants::JOB_RUN_TABLE . ' WHERE id = :id',
                ['id' => $jobRun->getId()]
            );
        });

        $this->pimcoreEntityManager->refresh($jobRun);

        return $processedElements;
    }

    /**
     * @throws Exception
     */
    public function switchStep(JobRun $jobRun, int $fromStep, ?int $toStep): bool
    {
        $affectedRows = $this->db->executeStatement(
            'UPDATE ' . TableConstants::JOB_RUN_TABLE .
            ' SET currentStep = :toStep, processedElementsForStep = 0' .
            ' WHERE id = :id AND currentStep = :fromStep',
            ['id' => $jobRun->getId(), 'fromStep' => $fromStep, 'toStep' => $toStep]
        );

        $this->pimcoreEntityManager->refresh($jobRun);

        return $affectedRows > 0;
    }

    /**
     * @throws Exception
     *
     * @internal
     */
    public function updateLogLocalizedWithDomain(
        JobRun $jobRun,
        string $message,
        array  $params = [],
        bool   $updateCurrentMessage = true,
        string $defaultLocale = 'en',
        string $domain = 'admin'
    ): void
    {
        if ($updateCurrentMessage) {
            $jobRun->setCurrentMessageLocalized(
                $this->currentMessageProvider->getTranslationMessages($message, $params, $domain)
            );
            $this->update($jobRun);
        }

        $translatedMessage = $this->translator->trans($message, $params, $domain, $defaultLocale);
        $this->updateLog($jobRun, $translatedMessage);
    }

    public function updateLogLocalized(
        JobRun $jobRun,
        string $message,
        array  $params = [],
        bool   $updateCurrentMessage = true,
        string $defaultLocale = 'en'
    ): void
    {
        $domain = $this->executionContext->getTranslationDomain($jobRun->getExecutionContext());

        $this->updateLogLocalizedWithDomain(
            $jobRun,
            $message,
            $params,
            $updateCurrentMessage,
            $defaultLocale,
            $domain
        );
    }

    /**
     * @throws Exception
     */
    public function updateLog(JobRun $jobRun, string $message): void
    {

        $this->db->executeStatement(
            'UPDATE ' .
            TableConstants::JOB_RUN_TABLE .
            ' SET log = IF(ISNULL(log),:message,CONCAT(log, "\n", :message)) WHERE id = :id',
            [
                'id' => $jobRun->getId(),
                'message' => new DateTimeImmutable()->format('c') . ': ' . trim($message),
            ]
        );

        $this->genericExecutionEngineLogger->info("[JobRun {$jobRun->getId()}]: " . $message);

        $this->pimcoreEntityManager->refresh($jobRun);
    }

    public function getJobRunById(int $id, bool $forceReload = false, ?int $ownerId = null): JobRun
    {
        $params = ['id' => $id];

        // an owner passed explicitly means a lookup for a user: a null owner (unknown user) must not see every run
        if (func_num_args() >= 3 && !$this->permissionService->isAllowedToSeeAllJobRuns()) {
            if ($ownerId === null) {
                throw new NotFoundException("JobRun with id $id not found.");
            }
            $params['ownerId'] = $ownerId;
        }

        return $this->findJobRun($params, $forceReload);
    }

    public function getJobRunByIdForUser(int $id, UserInterface $user, bool $forceReload = false): JobRun
    {
        return $this->findJobRun(['id' => $id, ...$this->getUserCriteria($user)], $forceReload);
    }

    /**
     * Get all job runs by user id. If the current user has permission to see all job runs, all job runs will be
     * returned. Without owner id only users allowed to see all job runs get a result.
     *
     * @return JobRun[]
     *
     */
    public function getJobRunsByUserId(
        ?int  $ownerId = null,
        array $orderBy = [],
        int   $limit = 100,
        int   $offset = 0
    ): array
    {
        $params = [];
        if (!$this->permissionService->isAllowedToSeeAllJobRuns()) {
            if ($ownerId === null) {
                return [];
            }
            $params['ownerId'] = $ownerId;
        }

        return $this->pimcoreEntityManager->getRepository(JobRun::class)->findBy(
            $params,
            $orderBy,
            $limit,
            $offset
        );
    }

    public function getJobRunsForUser(
        UserInterface $user,
        array         $orderBy = [],
        int           $limit = 100,
        int           $offset = 0
    ): array
    {
        try {
            $criteria = $this->getUserCriteria($user);
        } catch (PermissionException) {
            return [];
        }

        return $this->pimcoreEntityManager->getRepository(JobRun::class)->findBy(
            $criteria,
            $orderBy,
            $limit,
            $offset
        );
    }

    public function getAllJobRuns(array $orderBy = [], int $limit = 100, int $offset = 0): array
    {
        return $this->pimcoreEntityManager->getRepository(JobRun::class)->findBy([], $orderBy, $limit, $offset);
    }

    public function getTotalCount(): int
    {
        return $this->pimcoreEntityManager->getRepository(JobRun::class)->count([]);
    }

    public function getRunningJobsByUserId(
        int   $ownerId,
        array $orderBy = [],
        int   $limit = 10,
    ): array
    {
        return $this->pimcoreEntityManager
            ->getRepository(JobRun::class)
            ->findBy(
                ['ownerId' => $ownerId, 'state' => JobRunStates::RUNNING],
                $orderBy,
                $limit
            );
    }

    public function getLastJobRunByName(string $name): ?JobRun
    {
        $result = $this->pimcoreEntityManager->getRepository(JobRun::class)
            ->createQueryBuilder('JobRun')
            ->where('JobRun.serializedJob LIKE :name')
            ->setParameter('name', '%name":"' . $name . '"%')
            ->orderBy('JobRun.modificationDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if (empty($result)) {
            return null;
        }

        return $result[0];
    }

    /**
     * @throws PermissionException when the user may not see job runs at all
     */
    private function getUserCriteria(UserInterface $user): array
    {
        if (!$user->isAllowed(PermissionConstants::GEE_JOB_RUN)) {
            throw new PermissionException('You are not allowed to see job runs.');
        }

        if ($user->isAllowed(PermissionConstants::GEE_SEE_ALL_JOB_RUNS)) {
            return [];
        }

        return ['ownerId' => $user->getId()];
    }

    private function findJobRun(array $criteria, bool $forceReload): JobRun
    {
        $jobRun = $this->pimcoreEntityManager->getRepository(JobRun::class)->findOneBy($criteria);
        if (!$jobRun) {
            throw new NotFoundException("JobRun with id {$criteria['id']} not found.");
        }

        if ($forceReload) {
            $this->pimcoreEntityManager->refresh($jobRun);
        }

        return $jobRun;
    }

    /**
     * @throws Exception
     */
    public function updateSelectedElements(JobRun $jobRun, array $selectedElements): void
    {
        $job = $jobRun->getJob();
        if (!$job) {
            throw new JobNotFoundException('Job not found for JobRun with id: ' . $jobRun->getId());
        }
        $currentlySelectedElements = $job->getSelectedElements();
        $job->setSelectedElements($selectedElements);
        $this->update($jobRun);
        $this->updateLogLocalizedWithDomain(
            $jobRun,
            'gee_updated_selected_elements',
            [
                '%fromCount%' => count($currentlySelectedElements),
                '%toCount%' => count($selectedElements),
            ]
        );
    }
}
