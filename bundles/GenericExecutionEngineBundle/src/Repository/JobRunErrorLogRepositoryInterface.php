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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Repository;

use InvalidArgumentException;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRunErrorLog;

interface JobRunErrorLogRepositoryInterface
{
    /**
     * @param int|null $stepNumber step the error happened in, defaults to the current step of the job run
     *
     * @throws InvalidArgumentException when no step number is given and the job run is on no step
     */
    public function createFromJobRun(
        JobRun  $jobRun,
        ?int    $elementId = null,
        ?string $message = null,
        ?int    $stepNumber = null
    ): void;

    public function update(JobRunErrorLog $jobRunErrorLog): void;

    /**
     * @return JobRunErrorLog[]
     */
    public function getLogsByJobRunId(
        int   $jobRunId,
        ?int  $step = null,
        array $orderBy = [],
        int   $limit = 100,
        int   $offset = 0
    ): array;

    public function getTotalCount(): int;

    /**
     * Number of error logs of the job run, restricted to one step when given. A failed message writes one log.
     */
    public function getTotalCountByJobRunId(int $jobRunId, ?int $step = null): int;
}
