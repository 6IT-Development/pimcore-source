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
use Pimcore\Bundle\GenericExecutionEngineBundle\Utils\Constants\PermissionConstants;
use Pimcore\Model\UserInterface;
use Pimcore\Security\User\TokenStorageUserResolver;
use Pimcore\Tool\Authentication;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Checks the permissions of the user of the current request. The user is resolved on every call and never kept,
 * so long-running processes (worker runtimes, messenger workers) do not evaluate one request with the user of another.
 *
 * @internal
 */
final readonly class PermissionService implements PermissionServiceInterface
{
    public function __construct(
        private TokenStorageUserResolver $userResolver,
        private RequestStack             $requestStack
    )
    {
    }

    public function allowedToSeeJobRuns(): void
    {
        if (!$this->isAllowedToSeeJobRuns()) {
            throw new PermissionException('You are not allowed to see job run.');
        }
    }

    public function allowedToSeeAllJobRuns(): void
    {
        if (!$this->isAllowedToSeeAllJobRuns()) {
            throw new PermissionException(
                'You are not allowed to see all job runs. You can just see your own job runs.'
            );
        }
    }

    public function isAllowedToSeeJobRuns(): bool
    {
        return $this->getUser()?->isAllowed(PermissionConstants::GEE_JOB_RUN) ?? false;
    }

    public function isAllowedToSeeAllJobRuns(): bool
    {
        return $this->getUser()?->isAllowed(PermissionConstants::GEE_SEE_ALL_JOB_RUNS) ?? false;
    }

    public function isAllowedToInteractWithJobRun(JobRun $jobRun): bool
    {
        if ($this->requestStack->getCurrentRequest() === null) {
            return true;
        }

        $user = $this->getUser();
        if (!$user?->isAllowed(PermissionConstants::GEE_JOB_RUN)) {
            return false;
        }

        return $jobRun->getOwnerId() === $user->getId()
            || $user->isAllowed(PermissionConstants::GEE_SEE_ALL_JOB_RUNS);
    }

    /**
     * The user of the current firewall token, or the Pimcore admin session user when the request has no such token.
     */
    private function getUser(): ?UserInterface
    {
        return $this->userResolver->getUser() ?? Authentication::authenticateSession();
    }
}
