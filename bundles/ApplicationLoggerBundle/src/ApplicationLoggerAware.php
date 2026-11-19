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

namespace Pimcore\Bundle\ApplicationLoggerBundle;

use LogicException;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Element\ValidationException;
use Psr\Log\LogLevel;
use ReflectionClass;
use Stringable;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Throwable;

/**
 * Requires the ApplicationLogger to be injected by the container (setter injection with #[Required]).
 */
trait ApplicationLoggerAware
{
    private readonly ApplicationLogger $applicationLogger;

    protected function logAlert(
        Throwable              $exceptionObject,
        null|string|Stringable $message = null,
        ?AbstractObject        $relatedObject = null,
        ?string                $component = null
    ): void
    {
        $this->logException(LogLevel::ALERT, $exceptionObject, $message, $relatedObject, $component);
    }

    protected function logException(
        string                 $level,
        Throwable              $exceptionObject,
        null|string|Stringable $message = null,
        ?AbstractObject        $relatedObject = null,
        ?string                $component = null
    ): void
    {
        if (mb_trim((string)$message) === '') {
            $message = sprintf('%s EXCEPTION', mb_strtoupper($level));
        }

        $this->getApplicationLogger()->logException((string)$message, $exceptionObject, $level, $relatedObject, $this->resolveComponent($component));
    }

    /**
     * Logs a caught exception: client-caused ones (4xx HTTP, validation) go to notice,
     * everything else to the given level.
     */
    protected function logCaughtException(
        Throwable              $exceptionObject,
        null|string|Stringable $message = null,
        ?AbstractObject        $relatedObject = null,
        ?string                $component = null,
        string                 $level = LogLevel::ERROR
    ): void
    {
        if (($exceptionObject instanceof HttpExceptionInterface && $exceptionObject->getStatusCode() < 500) ||
            $exceptionObject instanceof ValidationException
        ) {
            $level = LogLevel::NOTICE;
        }

        $this->logException($level, $exceptionObject, $message, $relatedObject, $component);
    }

    protected function logCritical(
        Throwable              $exceptionObject,
        null|string|Stringable $message = null,
        ?AbstractObject        $relatedObject = null,
        ?string                $component = null
    ): void
    {
        $this->logException(LogLevel::CRITICAL, $exceptionObject, $message, $relatedObject, $component);
    }

    protected function logDebug(
        string|Stringable $message,
        ?AbstractObject   $relatedObject = null,
        ?FileObject       $fileObject = null,
        ?string           $component = null
    ): void
    {
        $this->log(LogLevel::DEBUG, $message, $relatedObject, $fileObject, $component);
    }

    protected function log(
        string            $level,
        string|Stringable $message,
        ?AbstractObject   $relatedObject = null,
        ?FileObject       $fileObject = null,
        ?string           $component = null
    ): void
    {
        $context = ['component' => $this->resolveComponent($component)];

        if ($relatedObject instanceof AbstractObject) {
            $context['relatedObject'] = $relatedObject;
        }

        if ($fileObject instanceof FileObject) {
            $context['fileObject'] = $fileObject;
        }

        $this->getApplicationLogger()->log($level, $message, $context);
    }

    protected function getApplicationLogger(): ApplicationLogger
    {
        if (!isset($this->applicationLogger)) {
            throw new LogicException(sprintf('%s: the ApplicationLogger was not injected, create the service with the container', static::class));
        }

        return $this->applicationLogger;
    }

    private function resolveComponent(?string $component): string
    {
        return mb_trim((string)$component) !== '' ? $component : (new ReflectionClass($this))->getShortName();
    }

    #[Required]
    public function setApplicationLogger(ApplicationLogger $applicationLogger): void
    {
        $this->applicationLogger = $applicationLogger;
    }

    protected function logEmergency(
        Throwable              $exceptionObject,
        null|string|Stringable $message = null,
        ?AbstractObject        $relatedObject = null,
        ?string                $component = null
    ): void
    {
        $this->logException(LogLevel::EMERGENCY, $exceptionObject, $message, $relatedObject, $component);
    }

    protected function logError(
        Throwable              $exceptionObject,
        null|string|Stringable $message = null,
        ?AbstractObject        $relatedObject = null,
        ?string                $component = null
    ): void
    {
        $this->logException(LogLevel::ERROR, $exceptionObject, $message, $relatedObject, $component);
    }

    protected function logInfo(
        string|Stringable $message,
        ?AbstractObject   $relatedObject = null,
        ?FileObject       $fileObject = null,
        ?string           $component = null
    ): void
    {
        $this->log(LogLevel::INFO, $message, $relatedObject, $fileObject, $component);
    }

    protected function logNotice(
        string|Stringable $message,
        ?AbstractObject   $relatedObject = null,
        ?FileObject       $fileObject = null,
        ?string           $component = null
    ): void
    {
        $this->log(LogLevel::NOTICE, $message, $relatedObject, $fileObject, $component);
    }

    protected function logWarningException(
        Throwable              $exceptionObject,
        null|string|Stringable $message = null,
        ?AbstractObject        $relatedObject = null,
        ?string                $component = null
    ): void
    {
        $this->logException(LogLevel::WARNING, $exceptionObject, $message, $relatedObject, $component);
    }

    protected function logWarning(
        string|Stringable $message,
        ?AbstractObject   $relatedObject = null,
        ?FileObject       $fileObject = null,
        ?string           $component = null
    ): void
    {
        $this->log(LogLevel::WARNING, $message, $relatedObject, $fileObject, $component);
    }
}
