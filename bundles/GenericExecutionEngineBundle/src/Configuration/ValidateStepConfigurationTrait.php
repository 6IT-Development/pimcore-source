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

use Exception;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @internal
 */
trait ValidateStepConfigurationTrait
{
    protected OptionsResolver $stepConfiguration;

    private bool $stepConfigurationConfigured = false;

    public function configurationIsValid(array $config): bool
    {
        try {
            $this->resolveStepConfiguration($config);
        } catch (Exception) {
            return false;
        }

        return true;
    }

    /**
     * Defines the options of the step on $this->stepConfiguration; called once per instance, so options, normalizers
     * and allowed values/types do not pile up in long-running workers
     */
    protected function configureStep(): void
    {
        // not configured should be configured in the usage.
    }

    /**
     * The resolver of the step configuration, configured by configureStep() on first use
     */
    protected function getStepConfigurationResolver(): OptionsResolver
    {
        if ($this->stepConfigurationConfigured) {
            return $this->stepConfiguration;
        }

        $this->stepConfiguration ??= new OptionsResolver();

        try {
            $this->configureStep();
        } catch (Exception $exception) {
            // do not keep a half-configured resolver, the next call configures it again
            $this->stepConfiguration = new OptionsResolver();

            throw $exception;
        }
        $this->stepConfigurationConfigured = true;

        return $this->stepConfiguration;
    }

    /**
     * @throws Exception
     */
    private function resolveStepConfiguration(array $config): array
    {
        return $this->getStepConfigurationResolver()->resolve($config);
    }
}
