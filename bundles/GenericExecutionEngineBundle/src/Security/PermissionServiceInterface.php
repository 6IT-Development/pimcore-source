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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Security;

use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Exception\PermissionException;

/**
 * @internal
 */
interface PermissionServiceInterface
{
    /**
     * @throws PermissionException
     */
    public function allowedToSeeJobRuns(): void;

    /**
     * @throws PermissionException
     */
    public function allowedToSeeAllJobRuns(): void;

    public function isAllowedToSeeJobRuns(): bool;

    public function isAllowedToSeeAllJobRuns(): bool;

    /**
     * Whether the current user may cancel or rerun the job run: it needs the job run permission and must own the run
     * or be allowed to see all job runs. Outside of a request (CLI, messenger worker) the caller is system code and
     * everything is allowed; inside a request without Pimcore user nothing is.
     */
    public function isAllowedToInteractWithJobRun(JobRun $jobRun): bool;
}
