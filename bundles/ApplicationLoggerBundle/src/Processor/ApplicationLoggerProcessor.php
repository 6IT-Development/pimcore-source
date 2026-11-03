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

namespace Pimcore\Bundle\ApplicationLoggerBundle\Processor;

use Monolog\LogRecord;
use Pimcore\Bundle\ApplicationLoggerBundle\FileObject;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;

/**
 * Make sure you add this processor when using the ApplicationLoggerDb handler as it
 * prepares data to be written by the handler. This replicates the functionality implemented
 * in ApplicationLogger but makes it available when using the ApplicationLoggerDb handler
 * in a pure PSR-3 handler context configured as monolog channel handler instead of
 * the ApplicationLogger class.
 */
class ApplicationLoggerProcessor
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $record = $this->processFileObject($record);
        $record = $this->processRelatedObject($record);

        return $this->processLoggingSource($record);
    }

    private function processFileObject(LogRecord $record): LogRecord
    {
        $context = $record->context;

        if (isset($context['fileObject'])) {
            if (is_string($context['fileObject'])) {
                $context['fileObject'] = str_replace(PIMCORE_PROJECT_ROOT, '', $context['fileObject']);
            } elseif ($context['fileObject'] instanceof FileObject) {
                $filename = $context['fileObject']->getFilename();
                $context['fileObject'] = null === $filename ? null : str_replace(PIMCORE_PROJECT_ROOT, '', $filename);
            } else {
                // DBAL cannot bind it and a log call must not turn into an application error
                error_log('Application Logger dropped the fileObject: it must be a file path or an instance of FileObject, got ' . get_debug_type($context['fileObject']));
                unset($context['fileObject']);
            }
        }

        return $record->with(context: $context);
    }

    private function processRelatedObject(LogRecord $record): LogRecord
    {
        $context = $record->context;
        if (!isset($context['relatedObject'])) {
            // remove related object type if no object is set
            if (isset($context['relatedObjectType'])) {
                unset($context['relatedObjectType']);
            }

            return $record->with(context: $context);
        }

        $relatedObject = $context['relatedObject'];
        $relatedObjectType = $context['relatedObjectType'] ?? null;

        if ($relatedObject instanceof ElementInterface) {
            $relatedObjectType = Service::getElementType($relatedObject);
            $relatedObject = $relatedObject->getId();
        } elseif (is_numeric($relatedObject) && (int)$relatedObject > 0) {
            $relatedObject = (int)$relatedObject;
            $relatedObjectType = in_array($relatedObjectType, ['object', 'document', 'asset'], true) ? $relatedObjectType : 'object';
        } else {
            error_log('Application Logger dropped the relatedObject: it must be an element or an ID, got ' . get_debug_type($relatedObject));
            $relatedObject = null;
        }

        // also true for an element which has not been saved yet
        if (null === $relatedObject || null === $relatedObjectType) {
            unset($context['relatedObject'], $context['relatedObjectType']);

            return $record->with(context: $context);
        }

        $context['relatedObject'] = $relatedObject;
        $context['relatedObjectType'] = $relatedObjectType;

        return $record->with(context: $context);
    }

    private function processLoggingSource(LogRecord $record): LogRecord
    {
        $context = $record->context;
        $source = $context['source'] ?? null;
        if ($source) {
            return $record;
        }

        $extra = $record->extra;
        if (!isset($extra['file']) || !isset($extra['line'])) {
            $context['source'] = null;

            return $record->with(context: $context);
        }

        if (isset($extra['class']) && isset($extra['function'])) {
            // called from a class method
            // ClassName->methodName():line
            $source = sprintf(
                '%s::%s:%d',
                $extra['class'],
                $extra['function'],
                $extra['line']
            );
        } elseif (isset($extra['function'])) {
            // called from a function
            // filename.php::functionName():line
            $source = sprintf(
                '%s::%s:%d',
                $this->normalizeFilename($extra['file']),
                $extra['function'],
                $extra['line']
            );
        } else {
            // we don't have a previous call when the logger was directly called
            // from a standalone PHP file (e.g. from a CLI script)
            // filename.php:line
            $source = sprintf(
                '%s:%d',
                $this->normalizeFilename($extra['file']),
                $extra['line']
            );
        }

        $context['source'] = $source;

        return $record->with(context: $context);
    }

    private function normalizeFilename(string $filename): string
    {
        return str_replace(PIMCORE_PROJECT_ROOT . '/', '', $filename);
    }
}
