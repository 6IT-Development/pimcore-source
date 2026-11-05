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

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Note;
use Pimcore\Model\Element\Recyclebin;
use Pimcore\Model\Schedule\Task;
use Pimcore\Model\Schedule\Task\Listing;
use Pimcore\Model\User;
use Pimcore\Model\Version;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Throwable;

/**
 * @internal
 */
class ScheduledTasksTask implements TaskInterface
{
    private const string SKIP_ELEMENT_MISSING = 'the element does not exist';

    private LoggerInterface $logger;

    private LockFactory $lockFactory;

    public function __construct(LoggerInterface $logger, LockFactory $lockFactory)
    {
        $this->logger = $logger;
        $this->lockFactory = $lockFactory;
    }

    public function execute(): void
    {
        // runs must not overlap: the due tasks of an element have to run in date order
        $lock = $this->lockFactory->createLock(self::class, 3600);
        if (!$lock->acquire()) {
            $this->logger->info('Scheduled tasks are skipped, another run is still executing them');

            return;
        }

        try {
            $list = new Listing();
            $list->setCondition('active = 1 AND date < ?', time());
            $list->setOrderKey(['date', 'id']);
            $list->setOrder('ASC');
            $tasks = $list->load();

            foreach ($tasks as $task) {
                $lock->refresh();
                // every error is caught per task, so one broken task cannot block all tasks behind it
                try {
                    $this->executeTask($task);
                } catch (Throwable $e) {
                    $this->logger->error('There was a problem with the scheduled task ID: ' . $task->getId());
                    $this->logger->error((string)$e);
                }
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * @throws Throwable
     */
    private function executeTask(Task $task): void
    {
        $taskUser = null;
        if ($task->getUserId()) {
            $taskUser = User::getById($task->getUserId());
            // without the user the permission checks below would fall back to the full rights of the CLI
            if (!$taskUser instanceof User || !$taskUser->isActive()) {
                $this->logger->warning(sprintf(
                    'Scheduled task ID %d (%s %d) is cancelled: user ID %d does not exist or is inactive',
                    $task->getId(),
                    $task->getCtype(),
                    $task->getCid(),
                    $task->getUserId()
                ));
                $task->setActive(false);
                $task->save();

                return;
            }
        }

        // deactivate before the action: a task that was already claimed by another run is not executed twice
        if (!$task->claim()) {
            return;
        }

        try {
            $skipReason = $this->executeAction($task, $taskUser);
        } catch (Throwable $e) {
            // retried in the next run, as before
            $task->setActive(true);
            $task->save();

            throw $e;
        }

        if ($skipReason !== null) {
            $this->reportSkippedTask($task, $skipReason);
        }
    }

    /**
     * @return string|null why the task did nothing, null when the action ran
     *
     * @throws Throwable
     */
    private function executeAction(Task $task, ?User $taskUser): ?string
    {
        $cid = (int)$task->getCid();
        [$element, $class] = match ($task->getCtype()) {
            'document' => [Document::getById($cid), Document::class],
            'asset' => [Asset::getById($cid), Asset::class],
            'object' => [DataObject::getById($cid), DataObject\Concrete::class],
            default => [null, null],
        };

        if (!$element instanceof ElementInterface) {
            return self::SKIP_ELEMENT_MISSING;
        }
        if (!$element instanceof $class) {
            return 'only data objects, not folders, can be scheduled';
        }

        // assets have no published state
        $publishable = $element instanceof Document || $element instanceof DataObject\Concrete;

        switch ($task->getAction()) {
            case 'publish-version':
                if (!$task->getVersion()) {
                    return 'the task has no version';
                }
                if (!$element->isAllowed('publish', $taskUser) || !$element->isAllowed('versions', $taskUser)) {
                    return 'the user is not allowed to publish versions';
                }
                $version = $this->getVersionOfTask($task);
                if (!$version) {
                    return 'version ' . $task->getVersion() . ' does not exist or does not belong to the element';
                }
                $element = $version->getData();
                if (!$element instanceof $class) {
                    return 'the element could not be restored from version ' . $task->getVersion();
                }
                if ($publishable) {
                    $element->setPublished(true);
                }
                $element->save();

                return null;
            case 'publish':
            case 'unpublish':
                if (!$publishable) {
                    return 'assets cannot be published or unpublished';
                }
                if (!$element->isAllowed($task->getAction(), $taskUser)) {
                    return 'the user is not allowed to ' . $task->getAction();
                }
                $element->setPublished($task->getAction() === 'publish');
                $element->save();

                return null;
            case 'delete':
                if (!$element->isAllowed('delete', $taskUser)) {
                    return 'the user is not allowed to delete';
                }
                $this->deleteElement($element, $taskUser);

                return null;
            default:
                return 'unknown action "' . $task->getAction() . '"';
        }
    }

    /**
     * Without this, a skipped task disappears from the pending tasks as if it had run
     */
    private function reportSkippedTask(Task $task, string $reason): void
    {
        $this->logger->warning(sprintf(
            'Scheduled task ID %d (%s %s %d) did nothing: %s',
            $task->getId(),
            (string)$task->getAction(),
            (string)$task->getCtype(),
            (int)$task->getCid(),
            $reason
        ));

        if ($reason === self::SKIP_ELEMENT_MISSING) {
            return;
        }

        try {
            $note = new Note();
            $note->setCid((int)$task->getCid());
            $note->setCtype((string)$task->getCtype());
            $note->setType('notice');
            $note->setTitle('Scheduled task not executed');
            $note->setDate(time());
            $note->setDescription(sprintf(
                'The "%s" scheduled for %s was skipped: %s',
                (string)$task->getAction(),
                date('Y-m-d H:i', (int)$task->getDate()),
                $reason
            ));
            if ($task->getUserId()) {
                $note->setUser($task->getUserId());
            }
            $note->save();
        } catch (Throwable $e) {
            $this->logger->error('Could not add a note about skipped scheduled task ID ' . $task->getId() . ': ' . $e->getMessage());
        }
    }

    /**
     * The version must belong to the element of the task: the permission checks ran on that element, the element
     * that is saved is the one inside the version.
     */
    private function getVersionOfTask(Task $task): ?Version
    {
        $version = Version::getById($task->getVersion());

        if ($version && $version->getCid() === $task->getCid() && $version->getCtype() === $task->getCtype()) {
            return $version;
        }

        return null;
    }

    /**
     * A failed deletion is retried in the next run: remove its recycle bin item again, so every failed run does not
     * leave another full dump of the element tree behind.
     *
     * @throws Throwable
     */
    private function deleteElement(ElementInterface $element, ?User $user): void
    {
        $item = new Recyclebin\Item();
        $item->setElement($element);
        $item->save($user);

        try {
            $element->delete();
        } catch (Throwable $e) {
            $item->delete();

            throw $e;
        }
    }
}
