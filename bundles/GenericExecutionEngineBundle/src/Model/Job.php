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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Model;

use InvalidArgumentException;
use Pimcore\Model\Element\ElementDescriptor;

final class Job
{
    /**
     * @var JobStep[] list, the agent addresses steps by their position
     */
    private readonly array $steps;

    /**
     * The selection the job was created with, steps may replace $selectedElements while the job runs.
     *
     * @var ElementDescriptor[]
     */
    private readonly array $initialSelectedElements;

    /**
     * @param JobStep[] $steps
     * @param ElementDescriptor[] $selectedElements
     * @param ElementDescriptor[]|null $initialSelectedElements only set when the job is restored from a job run,
     *                                                          defaults to $selectedElements
     */
    public function __construct(
        private readonly string $name,
        array                   $steps,
        private array           $selectedElements = [],
        private readonly array  $environmentData = [],
        ?array                  $initialSelectedElements = null
    )
    {
        if (empty($steps)) {
            throw new InvalidArgumentException('Job must have at least one step');
        }

        foreach ($steps as $key => $step) {
            if (!$step instanceof JobStepInterface) {
                throw new InvalidArgumentException(
                    "Step $key of job $name must implement " . JobStepInterface::class . ', got ' .
                    get_debug_type($step) . '.'
                );
            }
        }

        $this->steps = array_values($steps);

        $this->initialSelectedElements = $initialSelectedElements ?? $selectedElements;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return JobStep[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * @return ElementDescriptor[] $selectedElements
     */
    public function getSelectedElements(): array
    {
        return $this->selectedElements;
    }

    public function getEnvironmentData(): array
    {
        return $this->environmentData;
    }

    /**
     * @return ElementDescriptor[]
     */
    public function getInitialSelectedElements(): array
    {
        return $this->initialSelectedElements;
    }

    /**
     * A copy of the job with the selection it was created with, used to rerun it.
     */
    public function withInitialSelection(): self
    {
        return new self($this->name, $this->steps, $this->initialSelectedElements, $this->environmentData);
    }

    /**
     * @param ElementDescriptor[] $selectedElements
     */
    public function setSelectedElements(array $selectedElements): void
    {
        $this->selectedElements = $selectedElements;
    }
}
