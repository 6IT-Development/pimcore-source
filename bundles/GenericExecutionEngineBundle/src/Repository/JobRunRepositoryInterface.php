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

use Doctrine\DBAL\Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\JobNotFoundException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\PermissionException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\UserInterface;

interface JobRunRepositoryInterface
{
    public function createFromJob(Job $job, ?int $ownerId = null): JobRun;

    public function update(JobRun $jobRun): JobRun;

    /**
     * Atomically counts one handled message of the given step.
     * Returns the new count, or null when the job run is no longer on that step (the message is stale).
     * The entity is refreshed from the database afterwards.
     *
     * @throws Exception
     */
    public function incrementProcessedElementsForStep(JobRun $jobRun, int $step): ?int;

    /**
     * Moves the job run from $fromStep to $toStep (null = no step, job finished) and resets the step counter.
     * Returns false when the job run is not on $fromStep any more, i.e. another worker already switched it.
     * The entity is refreshed from the database afterwards.
     *
     * @throws Exception
     */
    public function switchStep(JobRun $jobRun, int $fromStep, ?int $toStep): bool;

    public function updateLogLocalized(
        JobRun $jobRun,
        string $message,
        array  $params = [],
        bool   $updateCurrentMessage = true,
        string $defaultLocale = 'en'
    ): void;

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
    ): void;

    /**
     * @throws Exception
     */
    public function updateLog(JobRun $jobRun, string $message): void;

    /**
     * Without $ownerId this is an unrestricted lookup for system code (agent, handlers, workers).
     * When $ownerId is passed, the run must belong to that owner unless the current user may see all job runs; a
     * passed null owner then finds nothing. For lookups on behalf of a user prefer getJobRunByIdForUser().
     *
     * @throws NotFoundException
     */
    public function getJobRunById(int $id, bool $forceReload = false, ?int $ownerId = null): JobRun;

    /**
     * Returns the job run when the user has the job run permission and owns the run or may see all job runs.
     *
     * @throws NotFoundException when the run does not exist or belongs to another user
     * @throws PermissionException when the user may not see job runs at all
     */
    public function getJobRunByIdForUser(int $id, UserInterface $user, bool $forceReload = false): JobRun;

    /**
     * Job runs of $ownerId, or all job runs when the current user may see all job runs.
     * Without $ownerId only users allowed to see all job runs get a result; prefer getJobRunsForUser().
     *
     * @return JobRun[]
     */
    public function getJobRunsByUserId(
        ?int  $ownerId = null,
        array $orderBy = [],
        int   $limit = 100,
        int   $offset = 0
    ): array;

    /**
     * Job runs the user may see: none without the job run permission, all with "see all job runs", otherwise the
     * user's own runs.
     *
     * @return JobRun[]
     */
    public function getJobRunsForUser(
        UserInterface $user,
        array         $orderBy = [],
        int           $limit = 100,
        int           $offset = 0
    ): array;

    /**
     * All job runs without any permission check, for system code only.
     *
     * @return JobRun[]
     */
    public function getAllJobRuns(array $orderBy = [], int $limit = 100, int $offset = 0): array;

    public function getTotalCount(): int;

    public function getRunningJobsByUserId(
        int   $ownerId,
        array $orderBy = [],
        int   $limit = 10,
    ): array;

    /**
     * Returns the run created last for a job with this name (compared case-sensitively, names are cut to
     * JobRun::JOB_NAME_LENGTH characters)
     */
    public function getLastJobRunByName(string $name): ?JobRun;

    /**
     * @param ElementDescriptor[] $selectedElements
     *
     * @throws JobNotFoundException
     */
    public function updateSelectedElements(JobRun $jobRun, array $selectedElements): void;
}
