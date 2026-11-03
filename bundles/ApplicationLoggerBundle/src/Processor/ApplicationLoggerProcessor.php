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
use Monolog\Processor\ProcessorInterface;

/**
 * Make sure you add this processor when using the ApplicationLoggerDb handler as it
 * prepares data to be written by the handler. This replicates the functionality implemented
 * in ApplicationLogger but makes it available when using the ApplicationLoggerDb handler
 * in a pure PSR-3 handler context configured as monolog channel handler instead of
 * the ApplicationLogger class.
 */
class ApplicationLoggerProcessor implements ProcessorInterface
{
    #[\Override]
    public function __invoke(LogRecord $record): LogRecord
    {
        return $this->processLoggingSource($record->with(context: ContextNormalizer::normalize($record->context)));
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
                ContextNormalizer::relativeFilename($extra['file']),
                $extra['function'],
                $extra['line']
            );
        } else {
            // we don't have a previous call when the logger was directly called
            // from a standalone PHP file (e.g. from a CLI script)
            // filename.php:line
            $source = sprintf(
                '%s:%d',
                ContextNormalizer::relativeFilename($extra['file']),
                $extra['line']
            );
        }

        $context['source'] = $source;

        return $record->with(context: $context);
    }
}
