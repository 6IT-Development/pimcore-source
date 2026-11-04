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

namespace Pimcore\Model;

use Pimcore;
use Pimcore\Logger;
use Pimcore\Messenger\SanityCheckMessage;
use Throwable;

/**
 * @internal
 *
 * @method Dependency\Dao getDao()
 * @method void save()
 */
class Dependency extends AbstractModel
{
    /**
     * The ID of the object to get dependencies for, 0 until it is set
     *
     */
    protected int $sourceId = 0;

    /**
     * The type of the object to get dependencies for, empty until it is set
     *
     */
    protected string $sourceType = '';

    /**
     * Contains the ID/type of objects which are required for the given source object (sourceId/sourceType)
     *
     */
    protected array $requires = [];

    /**
     * Sanity checks of the sources of deleted elements, keyed by type and id. They are dispatched once the outermost
     * element delete is committed, `null` while no delete collects them.
     *
     * @var array<string, array{type: string, id: int}>|null
     */
    private static ?array $pendingSanityChecks = null;

    /**
     * Static helper to get the dependencies for the given sourceId & type
     *
     *
     */
    public static function getBySourceId(int $id, string $type): Dependency
    {
        $d = new self();
        $d->getDao()->getBySourceId($id, $type);

        return $d;
    }

    /**
     * Add a requirement to the source object
     *
     */
    public function addRequirement(int $id, string $type): void
    {
        $this->requires[] = [
            'type' => $type,
            'id' => $id,
        ];
    }

    /**
     * Used when element gets deleted. Removes entries (by source or target = element) and
     * schedules a sanity check for the sources that required the element.
     *
     * Inside an element delete the sanity checks are dispatched after its commit, see collectSanityChecks().
     */
    public function cleanAllForElement(Element\ElementInterface $element): void
    {
        foreach ($this->getDao()->cleanAllForElement($element) as $source) {
            if (self::$pendingSanityChecks === null) {
                self::dispatchSanityCheck($source['type'], $source['id']);
            } else {
                self::$pendingSanityChecks[$source['type'] . '-' . $source['id']] = $source;
            }
        }
    }

    /**
     * Collects the sanity checks of cleanAllForElement() until dispatchSanityChecks() or discardSanityChecks().
     * A sanity check handled before the delete is committed (sync or external transport) would re-save the source
     * while the deleted element still exists and restore its rows.
     *
     * @internal
     *
     * @return bool false if an outer delete collects them already, the caller must then neither dispatch nor discard
     */
    public static function collectSanityChecks(): bool
    {
        if (self::$pendingSanityChecks !== null) {
            return false;
        }

        self::$pendingSanityChecks = [];

        return true;
    }

    /**
     * @internal
     */
    public static function dispatchSanityChecks(): void
    {
        $pendingSanityChecks = self::$pendingSanityChecks ?? [];
        self::$pendingSanityChecks = null;

        foreach ($pendingSanityChecks as $source) {
            self::dispatchSanityCheck($source['type'], $source['id']);
        }
    }

    /**
     * @internal
     */
    public static function discardSanityChecks(): void
    {
        self::$pendingSanityChecks = null;
    }

    private static function dispatchSanityCheck(string $type, int $id): void
    {
        try {
            Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(new SanityCheckMessage($type, $id));
        } catch (Throwable $e) {
            // the delete is committed, a failed sanity check must not make it look failed
            Logger::error(sprintf('Dispatching the sanity check of %s %d failed: %s', $type, $id, $e));
        }
    }

    /**
     * Cleanup the dependencies for current source id.
     * Can be used for updating the dependencies.
     */
    public function clean(): void
    {
        $this->requires = [];
        $this->getDao()->clear();
    }

    public function getSourceId(): int
    {
        return $this->sourceId;
    }

    public function getRequires(?int $offset = null, ?int $limit = null): array
    {
        if ($offset !== null) {
            return array_slice($this->requires, $offset, $limit);
        }

        return $this->requires;
    }

    public function getFilterRequiresByPath(
        ?int    $offset = null,
        ?int    $limit = null,
        ?string $value = null,
        ?string $orderBy = null,
        ?string $orderDirection = null
    ): array
    {
        return $this->getDao()->getFilterRequiresByPath($offset, $limit, $value, $orderBy, $orderDirection);
    }

    public function getFilterRequiredByPath(
        ?int    $offset = null,
        ?int    $limit = null,
        ?string $value = null,
        ?string $orderBy = null,
        ?string $orderDirection = null
    ): array
    {
        return $this->getDao()->getFilterRequiredByPath($offset, $limit, $value, $orderBy, $orderDirection);
    }

    public function getRequiredBy(?int $offset = null, ?int $limit = null): array
    {
        return $this->getDao()->getRequiredBy($offset, $limit);
    }

    public function getRequiredByWithPath(
        ?int    $offset = null,
        ?int    $limit = null,
        ?string $orderBy = null,
        ?string $orderDirection = null
    ): array
    {
        return $this->getDao()->getRequiredByWithPath($offset, $limit, $orderBy, $orderDirection);
    }

    public function setSourceId(int $sourceId): static
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    public function setRequires(array $requires): static
    {
        $this->requires = $requires;

        return $this;
    }

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    public function setSourceType(string $sourceType): static
    {
        $this->sourceType = $sourceType;

        return $this;
    }

    public function getRequiresTotalCount(): int
    {
        return count($this->requires);
    }

    public function getRequiredByTotalCount(): int
    {
        return $this->getDao()->getRequiredByTotalCount();
    }

    /**
     * Check if the source object is required by an other object (an other object depends on this object)
     *
     */
    public function isRequired(): bool
    {
        return $this->getRequiredByTotalCount() > 0;
    }
}
