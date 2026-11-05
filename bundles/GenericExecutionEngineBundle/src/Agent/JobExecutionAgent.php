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
use Pimcore\Bundle\GenericExecutionEngineBundle\Configuration\ExecutionContextInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
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
use Pimcore\Translation\Translator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
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

        return $ownerId === $jobRun->getOwnerId();
    }

    /**
     * @throws Exception
     */
    public function cancelJobRun(int $jobRunId): void
    {
        $jobRun = $this->jobRunRepository->getJobRunById($jobRunId, true);
        $this->denyInteractionUnlessAllowed($jobRun);
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
        $jobRun = $this->jobRunRepository->getJobRunById($jobRunId);
        $this->denyInteractionUnlessAllowed($jobRun);
        $this->startJobExecution($jobRun->getJob(), $ownerId);
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
            $this->setCompletionState($jobRun);
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
                $message->getCurrentJobStep(),
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
     * @throws Exception
     */
    private function continueJobExecutionOnError(
        JobRun $jobRun,
        int    $step,
        int    $processedElements,
        string $errorMessage
    ): void
    {
        $this->setJobRunError(
            $jobRun,
            $errorMessage,
            [],
            false,
            JobRunStates::RUNNING
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
        JobRun        $jobRun,
        string        $errorMessage,
        array         $params = [],
        bool          $translate = true,
        ?JobRunStates $status = null
    ): void
    {
        $jobRun->setState($status ?? JobRunStates::FAILED);
        if ($translate) {
            $translatedMessage = $this->translator->trans($errorMessage, $params);
            $this->genericExecutionEngineLogger->info(
                "[JobRun {$jobRun->getId()}]: " . $translatedMessage . ' --> Job execution failed.'
            );
            $this->jobRunRepository->updateLogLocalizedWithDomain(
                $jobRun,
                $errorMessage,
                $params
            );
        } else {
            $this->genericExecutionEngineLogger->info(
                "[JobRun {$jobRun->getId()}]: " . $errorMessage . ' --> Job execution failed.'
            );
            $jobRun->setCurrentMessage($errorMessage);
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
                ['%job_id%' => $jobRun->getId()]
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
     * @throws Exception
     */
    private function setCompletionState(JobRun $jobRun): void
    {
        $message = '';

        $logs = $this->jobRunErrorLogRepository->getLogsByJobRunId(
            $jobRun->getId(),
            $jobRun->getCurrentStep()
        );

        if (count($logs) === $jobRun->getTotalElements()) {
            $jobRun->setState(JobRunStates::FAILED);
            $message = 'gee_job_failed';
        } elseif (count($logs) > 0) {
            $jobRun->setState(JobRunStates::FINISHED_WITH_ERRORS);
            $message = 'gee_job_finished_with_errors';
        }

        if (empty($logs)) {
            $jobRun->setCurrentMessage(null);
            $jobRun->setState(JobRunStates::FINISHED);
            $message = 'gee_job_finished';
        }

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
            $this->executionEngineBus->dispatch(new $messageString(
                    $jobRunId,
                    $currentStepId
                )
            );

            return;
        }

        foreach ($selectedElements as $selectedElement) {
            $this->executionEngineBus->dispatch(new $messageString(
                    $jobRunId,
                    $currentStepId,
                    $selectedElement
                )
            );
        }
    }
}
