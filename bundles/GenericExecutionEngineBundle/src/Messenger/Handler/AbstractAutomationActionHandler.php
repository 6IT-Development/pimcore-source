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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Handler;

use Exception;
use InvalidArgumentException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Configuration\ValidateStepConfigurationTrait;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\InvalidStepConfigurationException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStepInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Exception\NotFoundException;
use Psr\Log\LoggerInterface;
use Stringable;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

abstract class AbstractAutomationActionHandler
{
    use ValidateStepConfigurationTrait;

    protected ?LoggerInterface $genericExecutionEngineLogger = null;

    protected ?JobRunRepositoryInterface $jobRunRepository = null;

    private ?JobRunExtractorInterface $jobRunExtractor = null;

    private ?JobExecutionAgentInterface $jobExecutionAgent = null;

    private ?TranslatorInterface $translator = null;

    public function __construct()
    {
        $this->stepConfiguration = new OptionsResolver();
    }

    #[Required]
    public function setJobRunRepository(JobRunRepositoryInterface $jobRunRepository): void
    {
        if (!$this->jobRunRepository) {
            $this->jobRunRepository = $jobRunRepository;
        }
    }

    #[Required]
    public function setJobRunExtractor(JobRunExtractorInterface $jobRunExtractor): void
    {
        if (!$this->jobRunExtractor) {
            $this->jobRunExtractor = $jobRunExtractor;
        }
    }

    #[Required]
    public function setJobExecutionAgent(JobExecutionAgentInterface $jobExecutionAgent): void
    {
        if (!$this->jobExecutionAgent) {
            $this->jobExecutionAgent = $jobExecutionAgent;
        }
    }

    #[Required]
    public function setLogger(LoggerInterface $genericExecutionEngineLogger): void
    {
        if (!$this->genericExecutionEngineLogger) {
            $this->genericExecutionEngineLogger = $genericExecutionEngineLogger;
        }
    }

    #[Required]
    public function setTranslator(TranslatorInterface $translator): void
    {
        if (!$this->translator) {
            $this->translator = $translator;
        }
    }

    /**
     * Throws an exception with the translated message; the default domain is `admin`, where the GEE translations are
     *
     * @template exceptionClassName of Throwable
     *
     * @param class-string<exceptionClassName> $exceptionClassName
     *
     * @throws exceptionClassName
     * @throws InvalidArgumentException when $exceptionClassName is no throwable class
     */
    public function abortAction(
        string $translationKey,
        array  $translationParams = [],
        string $translationDomain = 'admin',
        string $exceptionClassName = Exception::class
    ): never
    {
        if (!is_a($exceptionClassName, Throwable::class, true)) {
            throw new InvalidArgumentException("$exceptionClassName is no throwable class.");
        }

        $errorMessage = $this->translator->trans($translationKey, $translationParams, $translationDomain);

        throw new $exceptionClassName($errorMessage);
    }

    /**
     * Logs a translation key to the job run which can be viewed in the job run overview
     * Translation key then can be translated check pimcore_job_execution.en.yaml
     *
     */
    protected function logMessageToJobRun(
        JobRun $jobRun,
        string $translationKey,
        array  $params = []
    ): void
    {
        $this->jobRunExtractor->logMessageToJobRun($jobRun, $translationKey, $params);
    }

    protected function getJobRun(GenericExecutionEngineMessageInterface $message, bool $forceReload = false): JobRun
    {
        return $this->jobRunExtractor->getJobRun($message, $forceReload);
    }

    protected function getJobStep(GenericExecutionEngineMessageInterface $message): JobStepInterface
    {
        return $this->jobRunExtractor->getJobStep($message);
    }

    protected function getCurrentJobStepConfig(GenericExecutionEngineMessageInterface $message): array
    {
        $jobStep = $this->getJobStep($message);

        $config = $jobStep->getConfig();

        try {
            $config = $this->resolveStepConfiguration($config);
        } catch (Exception $e) {
            throw new InvalidStepConfigurationException($e->getMessage());
        }

        return $config;
    }

    protected function getEnvironmentVariables(
        GenericExecutionEngineMessageInterface $message
    ): array
    {
        $jobRun = $this->getJobRun($message);
        $job = $jobRun->getJob();

        return $job === null ? [] : $job->getEnvironmentData();
    }

    /**
     * A value that is a single job_env('<name>') placeholder becomes the raw environment value (any type); placeholders
     * inside a longer string are replaced with the string form of their values.
     *
     * @throws NotFoundException when a referenced environment variable is missing
     */
    protected function replaceConfigValueWithEnvVariable(
        string $value,
        array  $variables
    ): mixed
    {
        $placeholder = "job_env\\('([^']*)'\\)";

        if (preg_match("/^$placeholder\$/D", $value, $match)) {
            return $this->getEnvVariable($match[1], $variables);
        }

        return preg_replace_callback(
            "/$placeholder/",
            fn(array $match): string => $this->stringifyEnvVariable(
                $match[1],
                $this->getEnvVariable($match[1], $variables)
            ),
            $value
        );
    }

    private function getEnvVariable(string $key, array $variables): mixed
    {
        if (!array_key_exists($key, $variables)) {
            throw new NotFoundException("Missing environment variable $key");
        }

        return $variables[$key];
    }

    private function stringifyEnvVariable(string $key, mixed $value): string
    {
        if (is_scalar($value) || $value === null || $value instanceof Stringable) {
            return (string) $value;
        }

        throw new InvalidStepConfigurationException(
            "Environment variable $key is no scalar value and cannot be used inside a configuration string"
        );
    }

    protected function extractConfigFieldFromJobStepConfig(
        GenericExecutionEngineMessageInterface $message,
        string                                 $key
    ): mixed
    {
        $config = $this->getCurrentJobStepConfig($message);
        if (!array_key_exists($key, $config)) {
            throw new NotFoundException("Missing configuration $key");
        }

        $value = $config[$key];

        return $this->recursivelyReplaceConfigValuesWithEnvVariables($message, $value);
    }

    protected function recursivelyReplaceConfigValuesWithEnvVariables(
        GenericExecutionEngineMessageInterface $message,
        mixed                                  $configValue
    ): mixed
    {

        if (is_string($configValue)) {
            return $this->replaceConfigValueWithEnvVariable(
                $configValue,
                $this->getEnvironmentVariables($message)
            );
        }

        if (is_array($configValue)) {
            return array_map(fn($value) => $this->recursivelyReplaceConfigValuesWithEnvVariables($message, $value), $configValue);
        }

        return $configValue;
    }

    protected function updateJobRunContext(
        JobRun $jobRun,
        string $key,
        mixed  $value,
    ): void
    {
        $context = $jobRun->getContext();
        $context[$key] = $value;
        $jobRun->setContext($context);
        $this->jobRunRepository->update($jobRun);
    }

    protected function throwUnRecoverableException(Throwable $exception): void
    {
        throw new UnrecoverableMessageHandlingException($exception->getMessage(), 0, $exception);
    }

    protected function isRunning(
        JobRun $jobRun
    ): bool
    {
        return $this->jobExecutionAgent->isRunning($jobRun->getId());
    }

    protected function getSubjectFromMessage(
        GenericExecutionEngineMessageInterface $message,
        array                                  $types = [JobRunExtractorInterface::OBJECT_TYPE, JobRunExtractorInterface::ASSET_TYPE]
    ): ?AbstractElement
    {
        /** @var AbstractElement $subject */
        $subject = $this->jobRunExtractor->getElementFromMessage($message, $types);

        return $subject;
    }

    /**
     * @return AbstractElement[]
     */
    protected function getSubjectsFromMessage(
        GenericExecutionEngineMessageInterface $message,
        array                                  $types = [JobRunExtractorInterface::OBJECT_TYPE, JobRunExtractorInterface::ASSET_TYPE]
    ): array
    {
        /** @var AbstractElement[] $subjects */
        $subjects = $this->jobRunExtractor->getElementsFromMessage($message, $types);

        return $subjects;
    }

    protected function setSelectedElementsForNextJobStep(JobRun $jobRun, array $selectedElements): void
    {
        $this->jobRunRepository->updateSelectedElements($jobRun, $selectedElements);
    }
}
