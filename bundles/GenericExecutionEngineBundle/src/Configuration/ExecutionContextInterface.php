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

use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\ExecutionContextNotDefinedException;

/**
 * @internal
 */
interface ExecutionContextInterface
{
    /**
     * Checks if the context is configured, without falling back to the default context
     */
    public function hasContext(string $context): bool;

    /**
     * Uses the default context when the given one is not configured
     *
     * @throws ExecutionContextNotDefinedException when neither the given nor the default context is configured
     */
    public function getTranslationDomain(string $context): string;

    /**
     * Uses the default context when the given one is not configured
     *
     * @throws ExecutionContextNotDefinedException when neither the given nor the default context is configured
     */
    public function getErrorHandlingFromContext(string $context): ?string;
}
