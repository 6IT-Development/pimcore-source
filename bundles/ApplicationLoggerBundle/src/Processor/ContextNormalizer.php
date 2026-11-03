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

use Pimcore\Bundle\ApplicationLoggerBundle\FileObject;
use Pimcore\Bundle\ApplicationLoggerBundle\RelatedObjectType;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use const PIMCORE_PROJECT_ROOT;

/**
 * Prepares the special context values for the writers. Shared by ApplicationLogger, which also writes to other PSR-3
 * loggers, and ApplicationLoggerProcessor, which is used without ApplicationLogger when the handler is configured for a
 * monolog channel.
 *
 * A log call must not turn into an application error, so unsupported values are dropped.
 *
 * @internal
 */
final class ContextNormalizer
{
    public static function normalize(array $context): array
    {
        return self::normalizeRelatedObject(self::normalizeFileObject($context));
    }

    public static function relativeFilename(string $filename): string
    {
        return str_replace(PIMCORE_PROJECT_ROOT . '/', '', $filename);
    }

    private static function normalizeFileObject(array $context): array
    {
        if (!isset($context['fileObject'])) {
            return $context;
        }

        $fileObject = $context['fileObject'];

        if ($fileObject instanceof FileObject) {
            $context['fileObject'] = $fileObject->getFilename();
        } elseif (is_string($fileObject)) {
            $context['fileObject'] = preg_replace('/^' . preg_quote(PIMCORE_PROJECT_ROOT, '/') . '/', '', $fileObject);
        } else {
            error_log('Application Logger dropped the fileObject: it must be a file path or an instance of FileObject, got ' . get_debug_type($fileObject));
            unset($context['fileObject']);
        }

        return $context;
    }

    private static function normalizeRelatedObject(array $context): array
    {
        $relatedObject = $context['relatedObject'] ?? null;
        $relatedObjectType = null;

        if ($relatedObject instanceof ElementInterface) {
            $relatedObjectType = Service::getElementType($relatedObject);
            $relatedObject = $relatedObject->getId();
        } elseif (is_numeric($relatedObject) && (int)$relatedObject > 0) {
            $relatedObject = (int)$relatedObject;
            $relatedObjectType = RelatedObjectType::fromContext($context['relatedObjectType'] ?? null)->value;
        } elseif (null !== $relatedObject) {
            error_log('Application Logger dropped the relatedObject: it must be an element or an ID, got ' . get_debug_type($relatedObject));
            $relatedObject = null;
        }

        // also true for an element which has not been saved yet
        if (null === $relatedObject || null === $relatedObjectType) {
            unset($context['relatedObject'], $context['relatedObjectType']);

            return $context;
        }

        $context['relatedObject'] = $relatedObject;
        $context['relatedObjectType'] = $relatedObjectType;

        return $context;
    }
}
