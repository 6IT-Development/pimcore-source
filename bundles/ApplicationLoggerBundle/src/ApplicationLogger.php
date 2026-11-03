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

use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\Logger;
use Pimcore;
use Pimcore\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Psr\Log\LoggerInterface;
use Stringable;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException as ContainerInvalidArgumentException;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;
use const PIMCORE_PROJECT_ROOT;

class ApplicationLogger implements LoggerInterface, ResetInterface
{
    protected ?string $component = null;

    protected string|null|FileObject $fileObject = null;

    protected AbstractObject|Document|int|Asset|null $relatedObject = null;

    protected string $relatedObjectType = RelatedObjectType::Object->value;

    protected array $loggers = [];

    protected static array $instances = [];

    /**
     * Without $initDbHandler the logger has no writer until one is added with addWriter()
     */
    public static function getInstance(string $component = 'default', bool $initDbHandler = true): ApplicationLogger
    {
        $container = Pimcore::getContainer();
        $containerId = 'pimcore.app_logger.' . $component;
        $service = $container->has($containerId) ? $container->get($containerId) : null;

        if ($service instanceof self) {
            if ($service->component === $component) {
                return $service;
            }

            // the service is shared, setting the component on it would change it for every other consumer
            $logger = clone $service;
            $logger->setComponent($component);

            return $logger;
        }

        if ($service instanceof LoggerInterface) {
            // e.g. a Monolog channel registered under this id
            $logger = new self();
            $logger->setComponent($component);
            $logger->addWriter($service);

            return $logger;
        }

        if (null === $service && isset(self::$instances[$containerId])) {
            return self::$instances[$containerId];
        }

        $logger = new self();
        $logger->setComponent($component);
        if ($initDbHandler) {
            $logger->addWriter($container->get(ApplicationLoggerDb::class));
        }

        // do not replace a foreign service registered under this id
        if (null === $service) {
            try {
                $container->set($containerId, $logger);
            } catch (ContainerInvalidArgumentException) {
                // the id belongs to a private or removed service of the compiled container
                self::$instances[$containerId] = $logger;
            }
        }

        return $logger;
    }

    public function addWriter(object $writer): void
    {
        if ($writer instanceof HandlerInterface) {
            if (!isset($this->loggers['default-monolog'])) {
                // auto init Monolog logger
                $this->loggers['default-monolog'] = new Logger('app');
            }
            $this->loggers['default-monolog']->pushHandler($writer);
        } elseif ($writer instanceof LoggerInterface) {
            $this->loggers[] = $writer;
        }
    }

    public function setComponent(string $component): void
    {
        $this->component = $component;
    }

    public function setFileObject(FileObject|string $fileObject): void
    {
        $this->fileObject = $fileObject;
    }

    public function setRelatedObject(Asset|int|Document|AbstractObject $relatedObject): void
    {
        $this->relatedObject = $relatedObject;
        $this->relatedObjectType = is_int($relatedObject)
            ? RelatedObjectType::Object->value
            : (string)Service::getElementType($relatedObject);
    }

    /**
     * Clears the file object and related object set on the instance, also called by the kernel between requests
     * and messages
     */
    public function reset(): void
    {
        $this->fileObject = null;
        $this->relatedObject = null;
        $this->relatedObjectType = RelatedObjectType::Object->value;
    }

    public function __clone()
    {
        // what was set on the original is meant for the next log call of the original
        $this->reset();
    }

    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        // the file object and related object set on the instance apply to the next log call only,
        // the logger is a shared service
        $instanceFileObject = $this->fileObject;
        $instanceRelatedObject = $this->relatedObject;
        $instanceRelatedObjectType = $this->relatedObjectType;
        $this->reset();

        if (!isset($context['component'])) {
            $context['component'] = $this->component;
        }

        if (!isset($context['fileObject']) && $instanceFileObject) {
            $context['fileObject'] = $instanceFileObject;
        }

        if (isset($context['fileObject'])) {
            if (is_string($context['fileObject'])) {
                $context['fileObject'] = preg_replace('/^' . preg_quote(PIMCORE_PROJECT_ROOT, '/') . '/', '', $context['fileObject']);
            } elseif ($context['fileObject'] instanceof FileObject) {
                $context['fileObject'] = $context['fileObject']->getFilename();
            } else {
                // a log call must not turn into an application error
                error_log('Application Logger dropped the fileObject: it must be a file path or an instance of FileObject, got ' . get_debug_type($context['fileObject']));
                unset($context['fileObject']);
            }
        }

        $relatedObject = $context['relatedObject'] ?? null;
        $relatedObjectType = $context['relatedObjectType'] ?? null;

        // the instance type belongs to the instance object only
        if (!$relatedObject && $instanceRelatedObject) {
            $relatedObject = $instanceRelatedObject;
            $relatedObjectType = $instanceRelatedObjectType;
        }

        if (is_numeric($relatedObject)) {
            $context['relatedObject'] = $relatedObject;
            $context['relatedObjectType'] = RelatedObjectType::fromContext($relatedObjectType)->value;
        } elseif ($relatedObject instanceof ElementInterface) {
            $context['relatedObject'] = $relatedObject->getId();
            $context['relatedObjectType'] = Service::getElementType($relatedObject);
        } else {
            if (null !== $relatedObject) {
                error_log('Application Logger dropped the relatedObject: it must be an element or an ID, got ' . get_debug_type($relatedObject));
            }
            unset($context['relatedObject'], $context['relatedObjectType']);
        }

        if (!isset($context['source'])) {
            $context['source'] = $this->resolveLoggingSource();
        }

        foreach ($this->loggers as $logger) {
            if ($logger instanceof LoggerInterface) {
                $logger->log($level, $message, $context);
            }
        }
    }

    /**
     * Resolve logging source
     */
    protected function resolveLoggingSource(): string
    {
        $validMethods = [
            'log', 'logException', 'emergency', 'critical', 'error',
            'alert', 'warning', 'notice', 'info', 'debug',
        ];

        // look for the first call to this class calling one of the logging methods,
        // is_a() also matches subclasses overriding them
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $index = 0;
        for ($i = count($backtrace) - 1; $i >= 0; $i--) {
            if (isset($backtrace[$i]['class'])
                && is_a($backtrace[$i]['class'], self::class, true)
                && in_array($backtrace[$i]['function'], $validMethods, true)
            ) {
                $index = $i;

                break;
            }
        }

        // a logging method called by PHP internals (array_map(), call_user_func(), ...) has no file and line,
        // the next frame is the internal function called from the statement we are looking for
        while (!isset($backtrace[$index]['file']) && isset($backtrace[$index + 1])) {
            $index++;
        }

        $logCall = $backtrace[$index];
        $previousCall = $backtrace[$index + 1] ?? null;
        $file = isset($logCall['file']) ? $this->normalizeFilename($logCall['file']) : '[internal]';
        $line = $logCall['line'] ?? 0;

        if (null === $previousCall) {
            // we don't have a previous call when the logger was directly called
            // from a standalone PHP file (e.g. from a CLI script)
            // filename.php:line
            return sprintf('%s:%d', $file, $line);
        }

        if (isset($previousCall['class'])) {
            // called from a class method
            // ClassName->methodName():line
            return sprintf('%s::%s:%d', $previousCall['class'], $previousCall['function'], $line);
        }

        // called from a function
        // filename.php::functionName():line
        return sprintf('%s::%s:%d', $file, $previousCall['function'], $line);
    }

    private function normalizeFilename(string $filename): string
    {
        return str_replace(PIMCORE_PROJECT_ROOT . '/', '', $filename);
    }

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function logException(
        string                    $message,
        Throwable                 $exceptionObject,
        ?string                   $priority = 'alert',
        ElementInterface|int|null $relatedObject = null,
        ?string                   $component = null
    ): void
    {
        if (is_null($priority)) {
            $priority = 'alert';
        }

        $message .= ' : ' . $exceptionObject->getMessage();

        $fileObject = self::createExceptionFileObject($exceptionObject);

        $this->log($priority, $message, [
            'relatedObject' => $relatedObject,
            'fileObject' => $fileObject,
            'component' => $component,
        ]);
    }

    /**
     * Logs a throwable to a given logger. This can be used to format an exception in the same format
     * as the logException method to any PSR/monolog logger (e.g. when consumed via DI)
     */
    public static function logExceptionObject(
        LoggerInterface           $logger,
        string                    $message,
        Throwable                 $exception,
        int|string|Level          $level = Level::Alert,
        ElementInterface|int|null $relatedObject = null,
        array                     $context = []
    ): void
    {
        $message .= ' : ' . $exception->getMessage();

        $fileObject = self::createExceptionFileObject($exception);

        $logger->log($level, $message, array_merge([
            'relatedObject' => $relatedObject,
            'fileObject' => $fileObject,
        ], $context));
    }

    private static function exceptionToString(Throwable $exceptionObject, bool $includeStackTrace, bool $includePrevious = false): string
    {
        $data = [
            $exceptionObject->getMessage(),
            'File: ' . $exceptionObject->getFile(),
            'Line: ' . $exceptionObject->getLine(),
            'Code: ' . $exceptionObject->getCode(),
        ];

        if ($includeStackTrace) {
            $data[] = "Trace:\n" . $exceptionObject->getTraceAsString();
        }

        if ($includePrevious && $exceptionObject->getPrevious()) {
            $data[] = "\nPrevious:\n" . self::exceptionToString($exceptionObject->getPrevious(), $includeStackTrace, true);
        }

        return implode("\n", $data);
    }

    private static function createExceptionFileObject(Throwable $exceptionObject): FileObject
    {
        $data = self::exceptionToString($exceptionObject, true, true);

        return new FileObject($data);
    }
}
