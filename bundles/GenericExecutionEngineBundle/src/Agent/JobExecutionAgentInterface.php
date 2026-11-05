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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Agent;

use LogicException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\ExecutionContextNotDefinedException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\JobNotFoundException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\PermissionException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Throwable;

interface JobExecutionAgentInterface
{
    /**
     * Start new Job Run based on a job definition
     *
     * @throws ExecutionContextNotDefinedException when the execution context is not configured
     */
    public function startJobExecution(
        Job    $job,
        ?int   $ownerId,
        string $executionContext = 'default'
    ): JobRun;

    /**
     * Continue execution when a message is finished.
     */
    public function continueJobMessageExecution(
        GenericExecutionEngineMessageInterface $message,
        ?Throwable                             $throwable = null
    ): void;

    /**
     * Checks if the active user with the given id may cancel or rerun the job run: it needs the job run permission and
     * must own the run or be allowed to see all job runs
     */
    public function isInteractionAllowed(int $jobRunId, int $ownerId): bool;

    /**
     * Cancel given job run, only a running or not started job run is changed
     *
     * @throws PermissionException when called in a request whose user may not interact with the job run
     */
    public function cancelJobRun(int $jobRunId): void;

    /**
     * Start a new job run with the job, selection and execution context the given job run was started with
     *
     * @throws PermissionException when called in a request whose user may not interact with the job run
     * @throws JobNotFoundException when the job run has no job
     * @throws ExecutionContextNotDefinedException when the execution context of the job run is no longer configured
     * @throws LogicException when the job run is still running
     */
    public function rerunJobRun(int $jobRunId, ?int $ownerId): void;

    /**
     * Checks if job run is running
     */
    public function isRunning(int $jobRunId): bool;
}
