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

use Doctrine\DBAL\Exception;
use LogicException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Configuration\ExecutionContextInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\JobNotFoundException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\PermissionException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunErrorLogRepositoryInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Security\PermissionServiceInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums\ErrorHandlingMode;
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Enums\SelectionProcessingMode;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\User;
use Pimcore\Translation\Translator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Throwable;

/**
 * @internal
 */
final class JobExecutionAgent implements JobExecutionAgentInterface
{
    private const string LOG_JOB_RUN_ID_KEY = '%job_run_id%';

    private const string LOG_JOB_RUN_NAME_KEY = '%job_run_name%';

    private bool $isDev;

    public function __construct(
        string                                             $environment,
        private readonly string                            $errorHandlingMode,
        private readonly ExecutionContextInterface         $executionContext,
        private readonly JobRunRepositoryInterface         $jobRunRepository,
        private readonly JobRunErrorLogRepositoryInterface $jobRunErrorLogRepository,
        private readonly LoggerInterface                   $genericExecutionEngineLogger,
        private readonly MessageBusInterface               $executionEngineBus,
        private readonly Translator                        $translator,
        private readonly PermissionServiceInterface        $permissionService
    )
    {
        $this->isDev = $environment === 'dev';
    }

    /**
     * @throws Exception
     */
    public function startJobExecution(
        Job    $job,
        ?int   $ownerId,
        string $executionContext = 'default'
    ): JobRun
    {
        $jobRun = $this->jobRunRepository->createFromJob($job, $ownerId);
        $jobRun->setExecutionContext($executionContext);
        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setCurrentStep(0);

        $this->jobRunRepository->updateLogLocalizedWithDomain(
            $jobRun,
            'gee_job_started',
            $this->getLogParams($jobRun)
        );
        $this->jobRunRepository->update($jobRun);

        $this->dispatchStepMessage($jobRun);

        return $jobRun;
    }

    /**
     * @throws Exception
     */
    public function continueJobMessageExecution(
        GenericExecutionEngineMessageInterface $message,
        ?Throwable                             $throwable = null
    ): void
    {
        $jobRun = $this->jobRunRepository->getJobRunById($message->getJobRunId(), true);
        if ($jobRun->getState() !== JobRunStates::RUNNING) {
            return;
        }

        $step = $message->getCurrentJobStep();
        $processedElements = $this->jobRunRepository->incrementProcessedElementsForStep($jobRun, $step);
        if ($processedElements === null) {
            $this->genericExecutionEngineLogger->info(
                "[JobRun {$jobRun->getId()}]: Ignoring message of step $step," .
                " the job run is on step {$jobRun->getCurrentStep()}."
            );

            return;
        }

        if (!$throwable) {
            $this->handleNextMessage($jobRun, $step, $processedElements);

            return;
        }

        $this->handleJobExecutionError($jobRun, $message, $throwable, $processedElements);
    }

    public function isInteractionAllowed(int $jobRunId, int $ownerId): bool
    {
        $jobRun = $this->jobRunRepository->getJobRunById($jobRunId, true);
        $user = User::getById($ownerId);

        return $user instanceof User
            && $user->isActive()
            && $this->permissionService->isUserAllowedToInteractWithJobRun($user, $jobRun);
    }

    /**
     * @throws Exception
     */
    public function cancelJobRun(int $jobRunId): void
    {
        $jobRun = $this->jobRunRepository->getJobRunById($jobRunId, true);
        $this->denyInteractionUnlessAllowed($jobRun);

        // finished, failed and cancelled runs keep their state
        if (!in_array($jobRun->getState(), [JobRunStates::RUNNING, JobRunStates::NOT_STARTED], true)) {
            $this->genericExecutionEngineLogger->info(
                "[JobRun {$jobRun->getId()}]: Not cancelled, the JobRun is already {$jobRun->getState()?->value}."
            );

            return;
        }

        $this->genericExecutionEngineLogger->info("[JobRun {$jobRun->getId()}]: JobRun cancelled.");
        $jobRun->setState(JobRunStates::CANCELLED);
        $this->jobRunRepository->updateLogLocalizedWithDomain(
            $jobRun,
            'gee_job_cancelled',
            $this->getLogParams($jobRun)
        );
    }

    /**
     * @throws Exception
     */
    public function rerunJobRun(int $jobRunId, ?int $ownerId): void
    {
        $jobRun = $this->jobRunRepository->getJobRunById($jobRunId, true);
        $this->denyInteractionUnlessAllowed($jobRun);

        if ($jobRun->getState() === JobRunStates::RUNNING) {
            throw new LogicException("Job run $jobRunId is still running and cannot be rerun.");
        }

        $job = $jobRun->getJob();
        if (!$job instanceof Job) {
            throw new JobNotFoundException("Job not found for JobRun with id: $jobRunId");
        }

        $this->startJobExecution($job->withInitialSelection(), $ownerId, $jobRun->getExecutionContext());
    }

    public function isRunning(int $jobRunId): bool
    {
        $jobRun = $this->jobRunRepository->getJobRunById($jobRunId, true);

        return $jobRun->getState() === JobRunStates::RUNNING;
    }

    /**
     * @throws PermissionException
     */
    private function denyInteractionUnlessAllowed(JobRun $jobRun): void
    {
        if (!$this->permissionService->isAllowedToInteractWithJobRun($jobRun)) {
            throw new PermissionException("You are not allowed to interact with job run {$jobRun->getId()}.");
        }
    }

    private function getSelectionProcessingModeFromJobRun(
        JobRun $jobRun,
        ?int   $stepIndex = null
    ): SelectionProcessingMode
    {
        $steps = $jobRun->getJob()?->getSteps();
        if ($steps !== null) {
            $step = $steps[$stepIndex ?? $jobRun->getCurrentStep()] ?? null;
            if ($step) {
                return $step->getSelectionProcessingMode();
            }
        }

        return SelectionProcessingMode::FOR_EACH;
    }

    /**
     * Continues with the next step once the handled message was the last one of its step.
     * totalElements is the size of the selection the step was dispatched with (see dispatchStepMessage()),
     * an empty selection is dispatched as one message.
     *
     * @throws Exception
     */
    private function handleNextMessage(JobRun $jobRun, int $step, int $processedElements): void
    {
        if ($this->getSelectionProcessingModeFromJobRun($jobRun, $step) === SelectionProcessingMode::ONCE ||
            $processedElements >= max(1, $jobRun->getTotalElements())) {
            $this->continueJobStepExecution($jobRun, $step);
        }
    }

    /**
     * @throws Exception
     */
    private function continueJobStepExecution(JobRun $jobRun, int $finishedStep): void
    {
        $job = $jobRun->getJob();
        if (!$job instanceof Job) {
            $this->setJobRunError(
                $jobRun,
                'gee_error_no_job_definition',
                $this->getLogParams($jobRun)
            );

            return;
        }

        $this->genericExecutionEngineLogger->info(
            "[JobRun {$jobRun->getId()}]:" .
            " Job step $finishedStep of Job '{$job->getName()}' finished."
        );

        if ($jobRun->getState() === JobRunStates::CANCELLED) {
            $this->genericExecutionEngineLogger->info(
                "[JobRun {$jobRun->getId()}]: Cancel stop execution due to JobRun was cancelled."
            );

            return;
        }

        $nextStep = $finishedStep + 1;
        $isLastStep = count($job->getSteps()) <= $nextStep;

        // only the worker that moves the run away from the finished step continues, all others stop here
        if (!$this->jobRunRepository->switchStep($jobRun, $finishedStep, $isLastStep ? null : $nextStep)) {
            return;
        }

        if ($isLastStep) {
            $this->setCompletionState($jobRun, $finishedStep);
            $this->jobRunRepository->update($jobRun);

            $this->genericExecutionEngineLogger->info("[JobRun {$jobRun->getId()}]: Job '{$job->getName()}' finished.");
        } else {
            $this->dispatchStepMessage($jobRun);
        }
    }

    /**
     * @throws Exception
     */
    private function handleJobExecutionError(
        JobRun                                 $jobRun,
        GenericExecutionEngineMessageInterface $message,
        Throwable                              $throwable,
        int                                    $processedElements
    ): void
    {
        $this->genericExecutionEngineLogger->error("[JobRun {$jobRun->getId()}]: " . $throwable);
        $errorMessage = $throwable->getMessage();
        if ($this->isDev) {
            $errorMessage .= ' Stack trace: ' . str_replace("\n", '', $throwable->getTraceAsString());
        }

        $this->jobRunErrorLogRepository->createFromJobRun(
            $jobRun,
            $message->getElement()?->getId(),
            $errorMessage,
        );

        match (
        $this->getErrorHandlingMode(
            $jobRun,
            $this->getSelectionProcessingModeFromJobRun($jobRun, $message->getCurrentJobStep())
        )
        ) {
            ErrorHandlingMode::STOP_ON_FIRST_ERROR =>
            $this->stopJobExecutionOnError(
                $jobRun,
                $errorMessage
            ),
            ErrorHandlingMode::CONTINUE_ON_ERROR =>
            $this->continueJobExecutionOnError(
                $jobRun,
                $message,
                $processedElements,
                $errorMessage
            )
        };
    }

    private function getErrorHandlingMode(
        JobRun                  $jobRun,
        SelectionProcessingMode $selectionProcessingMode
    ): ErrorHandlingMode
    {
        if ($selectionProcessingMode === SelectionProcessingMode::ONCE) {
            return ErrorHandlingMode::STOP_ON_FIRST_ERROR;
        }

        $errorHandling = $this->executionContext->getErrorHandlingFromContext($jobRun->getExecutionContext());
        if ($errorHandling === null) {
            $errorHandling = $this->errorHandlingMode;
        }

        return ErrorHandlingMode::from($errorHandling);
    }

    /**
     * Only the element failed: the error goes to the run log, the run stays running and keeps its current message.
     *
     * @throws Exception
     */
    private function continueJobExecutionOnError(
        JobRun                                 $jobRun,
        GenericExecutionEngineMessageInterface $message,
        int                                    $processedElements,
        string                                 $errorMessage
    ): void
    {
        $step = $message->getCurrentJobStep();
        $element = $message->getElement();

        $this->jobRunRepository->updateLogLocalizedWithDomain(
            $jobRun,
            'gee_job_element_failed',
            [
                ...$this->getLogParams($jobRun),
                '%step%' => $step,
                '%element%' => $element ? $element->getType() . ' ' . $element->getId() : '-',
                '%error%' => $errorMessage,
            ],
            false
        );

        $this->handleNextMessage($jobRun, $step, $processedElements);
    }

    /**
     * @throws Exception
     */
    private function stopJobExecutionOnError(
        JobRun $jobRun,
        string $errorMessage
    ): void
    {
        // queued messages of the run are skipped by StepConditionMiddleware once the run is failed
        $this->setJobRunError($jobRun, $errorMessage, [], false);
        $this->genericExecutionEngineLogger->info("[JobRun {$jobRun->getId()}]: JobRun cancelled due to errors.");
        $this->jobRunRepository->updateLogLocalizedWithDomain(
            $jobRun,
            'gee_job_failed',
            $this->getLogParams($jobRun)
        );
    }

    /**
     * @throws Exception
     */
    private function setJobRunError(
        JobRun $jobRun,
        string $errorMessage,
        array  $params = [],
        bool   $translate = true
    ): void
    {
        $jobRun->setState(JobRunStates::FAILED);
        if ($translate) {
            // same domain and locale as the run log written by updateLogLocalizedWithDomain()
            $translatedMessage = $this->translator->trans($errorMessage, $params, 'admin', 'en');
            $this->genericExecutionEngineLogger->info(
                "[JobRun {$jobRun->getId()}]: " . $translatedMessage . ' --> Job execution failed.'
            );
            $this->jobRunRepository->updateLogLocalizedWithDomain(
                $jobRun,
                $errorMessage,
                $params
            );
        } else {
            // raw error texts only go to the log, the current message is set by the caller as translation message
            $this->genericExecutionEngineLogger->info(
                "[JobRun {$jobRun->getId()}]: " . $errorMessage . ' --> Job execution failed.'
            );
            $this->jobRunRepository->update($jobRun);
            $this->jobRunRepository->updateLog($jobRun, $errorMessage);
        }
    }

    /**
     * @throws Exception
     */
    private function dispatchStepMessage(JobRun $jobRun): void
    {
        $job = $jobRun->getJob();
        if (!$job instanceof Job) {
            $this->setJobRunError(
                $jobRun,
                'gee_error_no_job_definition',
                $this->getLogParams($jobRun)
            );

            return;
        }

        $this->genericExecutionEngineLogger->info(
            "[JobRun {$jobRun->getId()}]:" .
            " Starting job step {$jobRun->getCurrentStep()} of Job '{$job->getName()}'."
        );

        $jobStep = $job->getSteps()[$jobRun->getCurrentStep()];
        $messageString = $jobStep->getMessageFQCN();

        if (!class_exists($messageString)) {
            $this->setJobRunError(
                $jobRun,
                'gee_error_missing_message_implementation',
                ['%message%' => $messageString, '%job_run_name%' => $job->getName()]
            );

            return;
        }

        // a previous step may have changed the selection, the step is complete after one message per element
        $jobRun->setTotalElements(count($job->getSelectedElements()));
        $this->jobRunRepository->update($jobRun);

        $this->dispatchSelectedElements(
            $jobRun->getId(),
            $jobRun->getCurrentStep(),
            $messageString,
            $this->getSelectionProcessingModeFromJobRun($jobRun),
            $job->getSelectedElements()
        );
    }

    private function getLogParams(JobRun $jobRun): array
    {
        return [
            self::LOG_JOB_RUN_ID_KEY => $jobRun->getId(),
            self::LOG_JOB_RUN_NAME_KEY => $jobRun->getJob()?->getName(),
        ];
    }

    /**
     * Failed when every message of the last step failed, finished with errors when any message of the run failed.
     * Elements that failed in an earlier step are still dispatched to the following steps (continue on error),
     * so only the last step tells whether the run produced anything.
     *
     * @throws Exception
     */
    private function setCompletionState(JobRun $jobRun, int $lastStep): void
    {
        $jobRunId = $jobRun->getId();
        $failedMessages = $this->jobRunErrorLogRepository->getTotalCountByJobRunId($jobRunId, $lastStep);
        $dispatchedMessages = $this->getSelectionProcessingModeFromJobRun($jobRun, $lastStep) ===
            SelectionProcessingMode::ONCE ? 1 : max(1, $jobRun->getTotalElements());

        [$state, $message] = match (true) {
            $failedMessages >= $dispatchedMessages => [JobRunStates::FAILED, 'gee_job_failed'],
            $failedMessages > 0,
            $this->jobRunErrorLogRepository->getTotalCountByJobRunId($jobRunId) > 0 => [
                JobRunStates::FINISHED_WITH_ERRORS,
                'gee_job_finished_with_errors',
            ],
            default => [JobRunStates::FINISHED, 'gee_job_finished'],
        };

        $jobRun->setState($state);
        $this->jobRunRepository->updateLogLocalizedWithDomain(
            $jobRun,
            $message,
            $this->getLogParams($jobRun)
        );
    }

    /**
     * @param ElementDescriptor[] $selectedElements
     */
    private function dispatchSelectedElements(
        int                     $jobRunId,
        int                     $currentStepId,
        string                  $messageString,
        SelectionProcessingMode $selectionProcessingMode,
        array                   $selectedElements = []
    ): void
    {
        if (empty($selectedElements) || $selectionProcessingMode === SelectionProcessingMode::ONCE) {
            $this->assertSentToTransport($jobRunId, $this->executionEngineBus->dispatch(new $messageString(
                    $jobRunId,
                    $currentStepId
                )
            ));

            return;
        }

        foreach ($selectedElements as $selectedElement) {
            $this->assertSentToTransport($jobRunId, $this->executionEngineBus->dispatch(new $messageString(
                    $jobRunId,
                    $currentStepId,
                    $selectedElement
                )
            ));
        }
    }

    /**
     * Job runs only advance through messenger worker events, a message handled synchronously leaves the run hanging.
     */
    private function assertSentToTransport(int $jobRunId, Envelope $envelope): void
    {
        if ($envelope->last(SentStamp::class) !== null) {
            return;
        }

        $this->genericExecutionEngineLogger->error(
            "[JobRun $jobRunId]: Message " . $envelope->getMessage()::class . ' was not sent to a transport.' .
            ' Route GenericExecutionEngineMessageInterface to the pimcore_generic_execution_engine transport' .
            ' and consume it with a messenger worker, otherwise the job run does not continue.'
        );
    }
}
