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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Configuration;

use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\ExecutionContextNotDefinedException;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final readonly class ExecutionContext implements ExecutionContextInterface
{
    public function __construct(
        private array           $contexts,
        private LoggerInterface $genericExecutionEngineLogger
    )
    {
    }

    public function hasContext(string $context): bool
    {
        return isset($this->contexts[$context]);
    }

    public function getTranslationDomain(string $context): string
    {
        return $this->getContextConfig($context)['translations_domain'];
    }

    public function getErrorHandlingFromContext(string $context): ?string
    {
        return $this->getContextConfig($context)['error_handling'] ?? null;
    }

    /**
     * Job runs keep the name of their context, which may have been removed from the configuration since: those runs
     * use the default context, so they can still be listed and finished.
     */
    private function getContextConfig(string $context): array
    {
        if (isset($this->contexts[$context])) {
            return $this->contexts[$context];
        }

        if (isset($this->contexts[JobRun::DEFAULT_EXECUTION_CONTEXT])) {
            $this->genericExecutionEngineLogger->warning(
                sprintf(
                    'Execution context "%s" is not defined, the "%s" execution context is used instead.',
                    $context,
                    JobRun::DEFAULT_EXECUTION_CONTEXT
                )
            );

            return $this->contexts[JobRun::DEFAULT_EXECUTION_CONTEXT];
        }

        throw new ExecutionContextNotDefinedException(sprintf('Execution context "%s" is not defined.', $context));
    }
}
