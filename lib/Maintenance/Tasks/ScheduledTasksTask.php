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
use Pimcore\Model\Element\Recyclebin;
use Pimcore\Model\Schedule\Task;
use Pimcore\Model\Schedule\Task\Listing;
use Pimcore\Model\User;
use Pimcore\Model\Version;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @internal
 */
class ScheduledTasksTask implements TaskInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $list = new Listing();
        $list->setCondition('active = 1 AND date < ?', time());
        $list->setOrderKey(['date', 'id']);
        $list->setOrder('ASC');
        $tasks = $list->load();

        foreach ($tasks as $task) {
            // every error is caught per task, so one broken task cannot block all tasks behind it
            try {
                $this->executeTask($task);
            } catch (Throwable $e) {
                $this->logger->error('There was a problem with the scheduled task ID: ' . $task->getId());
                $this->logger->error((string)$e);
            }
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

        if ($task->getCtype() === 'document') {
            $document = Document::getById($task->getCid());
            if ($document instanceof Document) {
                if ($task->getAction() === 'publish-version' && $task->getVersion() && $document->isAllowed('publish', $taskUser) && $document->isAllowed('versions', $taskUser)) {
                    if ($version = $this->getVersionOfTask($task)) {
                        $document = $version->getData();
                        if ($document instanceof Document) {
                            $document->setPublished(true);
                            $document->save();
                        } else {
                            $this->logger->error('Schedule\\Task\\Executor: Could not restore document from version data.');
                        }
                    } else {
                        $this->logger->error('Schedule\\Task\\Executor: Version [ ' . $task->getVersion() . ' ] does not exist or does not belong to the element of the task.');
                    }
                } elseif ($task->getAction() === 'publish' && $document->isAllowed('publish', $taskUser)) {
                    $document->setPublished(true);
                    $document->save();
                } elseif ($task->getAction() === 'unpublish' && $document->isAllowed('unpublish', $taskUser)) {
                    $document->setPublished(false);
                    $document->save();
                } elseif ($task->getAction() === 'delete' && $document->isAllowed('delete', $taskUser)) {
                    $this->deleteElement($document, $taskUser);
                }
            }
        } elseif ($task->getCtype() === 'asset') {
            $asset = Asset::getById($task->getCid());

            if ($asset instanceof Asset) {
                if ($task->getAction() === 'publish-version' && $task->getVersion() && $asset->isAllowed('publish', $taskUser) && $asset->isAllowed('versions', $taskUser)) {
                    if ($version = $this->getVersionOfTask($task)) {
                        $asset = $version->getData();
                        if ($asset instanceof Asset) {
                            $asset->save();
                        } else {
                            $this->logger->error('Schedule\\Task\\Executor: Could not restore asset from version data.');
                        }
                    } else {
                        $this->logger->error('Schedule\\Task\\Executor: Version [ ' . $task->getVersion() . ' ] does not exist or does not belong to the element of the task.');
                    }
                } elseif ($task->getAction() === 'delete' && $asset->isAllowed('delete', $taskUser)) {
                    $this->deleteElement($asset, $taskUser);
                }
            }
        } elseif ($task->getCtype() === 'object') {
            $object = DataObject::getById($task->getCid());

            if ($object instanceof DataObject\Concrete) {
                if ($task->getAction() === 'publish-version' && $task->getVersion() && $object->isAllowed('publish', $taskUser) && $object->isAllowed('versions', $taskUser)) {
                    if ($version = $this->getVersionOfTask($task)) {
                        $object = $version->getData();
                        if ($object instanceof DataObject\Concrete) {
                            $object->setPublished(true);
                            $object->save();
                        } else {
                            $this->logger->error('Schedule\\Task\\Executor: Could not restore object from version data.');
                        }
                    } else {
                        $this->logger->error('Schedule\\Task\\Executor: Version [ ' . $task->getVersion() . ' ] does not exist or does not belong to the element of the task.');
                    }
                } elseif ($task->getAction() === 'publish' && $object->isAllowed('publish', $taskUser)) {
                    $object->setPublished(true);
                    $object->save();
                } elseif ($task->getAction() === 'unpublish' && $object->isAllowed('unpublish', $taskUser)) {
                    $object->setPublished(false);
                    $object->save();
                } elseif ($task->getAction() === 'delete' && $object->isAllowed('delete', $taskUser)) {
                    $this->deleteElement($object, $taskUser);
                }
            }
        }

        $task->setActive(false);
        $task->save();
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
