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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Middleware;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Model\Exception\NotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * @internal
 */
final readonly class StepConditionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private JobExecutionAgentInterface $jobExecutionAgent,
        private JobRunExtractorInterface   $jobRunExtractor,
        private LoggerInterface            $genericExecutionEngineLogger,
    )
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        if ($message instanceof GenericExecutionEngineMessageInterface) {
            if ($envelope->last(ReceivedStamp::class) !== null && !$this->isForRunningStep($message)) {
                // the job run was cancelled, failed or left the step: do not run the handler for queued messages
                return $envelope;
            }

            if ($this->jobRunExtractor->checkCondition($message)) {
                return $stack->next()->handle($envelope, $stack);
            }

            $this->logToJobRun(
                $message,
            );

            $this->jobExecutionAgent->continueJobMessageExecution($message);

            return $envelope;
        }

        return $stack->next()->handle($envelope, $stack);
    }

    private function isForRunningStep(GenericExecutionEngineMessageInterface $message): bool
    {
        try {
            $jobRun = $this->jobRunExtractor->getJobRun($message, true);
        } catch (NotFoundException) {
            $this->genericExecutionEngineLogger->info(
                "[JobRun {$message->getJobRunId()}]: Skipping message, the job run does not exist any more."
            );

            return false;
        }

        if ($jobRun->getState() !== JobRunStates::RUNNING
            || $jobRun->getCurrentStep() !== $message->getCurrentJobStep()) {
            $this->genericExecutionEngineLogger->info(
                "[JobRun {$jobRun->getId()}]: Skipping message of step {$message->getCurrentJobStep()}," .
                " the job run is {$jobRun->getState()?->value} on step {$jobRun->getCurrentStep()}."
            );

            return false;
        }

        return true;
    }

    private function logToJobRun(
        GenericExecutionEngineMessageInterface $message
    ): void
    {
        $jobRun = $this->jobRunExtractor->getJobRun($message);
        $jobName = $jobRun->getJob()?->getName();
        $stepId = $message->getCurrentJobStep();
        $stepName = $jobRun->getJob()?->getSteps()[$stepId]->getName();
        $params['%jobName%'] = $jobName;
        $params['%stepId%'] = $stepId;
        $params['%stepName%'] = $stepName;

        $this->jobRunExtractor->logMessageToJobRun(
            $jobRun,
            'gee_middleware_step_condition_not_met',
            $params
        );

        $this->genericExecutionEngineLogger->info(
            "[JobRun {$jobRun->getId()}]:
            Skipping step $stepName with id $stepId of Job '$jobName', job condition not met."
        );
    }
}
