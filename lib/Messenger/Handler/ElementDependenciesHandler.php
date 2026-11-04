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

namespace Pimcore\Messenger\Handler;

use Pimcore\Db;
use Pimcore\Messenger\ElementDependenciesMessage;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Dependency;
use Pimcore\Model\Document;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\Service;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
class ElementDependenciesHandler
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    public function __invoke(ElementDependenciesMessage $message): void
    {
        // Saves of the same element (several consumers, several requests) are serialised by the row lock that the
        // element save takes as well. The rows are computed from the latest committed state under that lock, so a
        // handler that loaded an older state cannot overwrite them because it finished last.
        Db::get()->transactional(function () use ($message): void {
            $element = Service::getElementById($message->getType(), $message->getId());
            if (!$element instanceof AbstractElement) {
                return;
            }

            if (!$this->isLatestCommittedState($element)) {
                $element = Service::getElementById($message->getType(), $message->getId(), ['force' => true]);
                if (!$element instanceof AbstractElement) {
                    return;
                }
            }

            $this->saveDependencies($element);
        });
    }

    /**
     * Locks the element row until the rows are saved. An instance from the cache of a consumer can be older than
     * the committed element, in-memory changes of the current process are committed already when the message is handled.
     */
    private function isLatestCommittedState(AbstractElement $element): bool
    {
        $table = match (Service::getElementType($element)) {
            'asset' => 'assets',
            'document' => 'documents',
            'object' => 'objects',
        };

        $row = Db::get()->fetchAssociative(
            'SELECT modificationDate, versionCount FROM ' . $table . ' WHERE id = ? FOR UPDATE',
            [$element->getId()]
        );

        return $row
            && (int)$row['modificationDate'] === $element->getModificationDate()
            && (int)$row['versionCount'] === $element->getVersionCount();
    }

    private function saveDependencies(AbstractElement $element): void
    {
        $id = $element->getId();
        $type = Service::getElementType($element);

        $this->logger->debug(sprintf('Processing dependencies of %s with ID %s ', $type, $id));

        $d = new Dependency();
        $d->setSourceType($type);
        $d->setSourceId($id);

        // the flags are process wide, they must be restored even if resolving fails
        $hideUnpublished = $this->showUnpublished($element);
        $getInheritedValues = AbstractObject::getGetInheritedValues();
        AbstractObject::setGetInheritedValues(false);

        try {
            foreach ($element->resolveDependencies() as $requirement) {
                if (!$requirement['id']) {
                    // a relation to an unsaved element, there is nothing to depend on yet
                    continue;
                }

                if (!Dependency::isValidType((string)$requirement['type'])) {
                    // one broken relation (e.g. of a custom data type) must not drop the other dependencies
                    $this->logger->warning(sprintf('Skipping dependency of %s %d on %s %d: invalid element type', $type, $id, $requirement['type'], $requirement['id']));

                    continue;
                }

                if ($requirement['id'] == $id && $requirement['type'] == $type) {
                    // dont't add a reference to yourself
                    continue;
                }

                $d->addRequirement($requirement['id'], $requirement['type']);
            }
        } finally {
            $this->resetHideUnpublished($element, $hideUnpublished);
            AbstractObject::setGetInheritedValues($getInheritedValues);
        }

        $d->save();

    }

    private function showUnpublished(AbstractElement $element): ?bool
    {
        $hideUnpublished = null;
        if ($element instanceof AbstractObject) {
            $hideUnpublished = AbstractObject::getHideUnpublished();
            AbstractObject::setHideUnpublished(false);
        } elseif ($element instanceof Document) {
            $hideUnpublished = Document::doHideUnpublished();
            Document::setHideUnpublished(false);
        }

        return $hideUnpublished;
    }

    private function resetHideUnpublished(AbstractElement $element, ?bool $hideUnpublished): void
    {
        if ($element instanceof AbstractObject) {
            AbstractObject::setHideUnpublished($hideUnpublished);
        } elseif ($element instanceof Document) {
            Document::setHideUnpublished($hideUnpublished);
        }
    }
}
