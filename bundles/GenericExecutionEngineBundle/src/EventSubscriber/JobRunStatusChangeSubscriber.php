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

namespace Pimcore\Bundle\GenericExecutionEngineBundle\EventSubscriber;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Collects state changes of job runs while the entity manager flushes and dispatches
 * JobRunStateChangedEvent once the flush has been written, so listeners never see a state that is rolled back
 * and may use the entity manager themselves.
 *
 * @internal
 */
final class JobRunStatusChangeSubscriber
{
    private const string STATE_FIELD = 'state';

    /**
     * @var JobRunStateChangedEvent[]
     */
    private array $pendingEvents = [];

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    )
    {
    }

    public function preFlush(): void
    {
        // events of a flush that failed before postFlush must not be dispatched with the next one
        $this->pendingEvents = [];
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof JobRun || !$args->hasChangedField(self::STATE_FIELD)) {
            return;
        }

        $oldState = $this->stateToString($args->getOldValue(self::STATE_FIELD));
        $newState = $this->stateToString($args->getNewValue(self::STATE_FIELD));

        if ($oldState === $newState) {
            return;
        }

        $this->pendingEvents[] = new JobRunStateChangedEvent(
            jobRunId: $entity->getId(),
            jobName: $entity->getJob()?->getName(),
            jobRunOwnerId: $entity->getOwnerId(),
            oldState: $oldState,
            newState: $newState
        );
    }

    public function postFlush(): void
    {
        $events = $this->pendingEvents;
        // listeners may flush again, which collects and dispatches its own events
        $this->pendingEvents = [];

        foreach ($events as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }

    private function stateToString(mixed $state): string
    {
        return $state instanceof JobRunStates ? $state->value : (string) $state;
    }
}
