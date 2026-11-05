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

use __PHP_Incomplete_Class;
use DeepCopy\Matcher\PropertyMatcher;
use Exception;
use Pimcore;
use Pimcore\Event\Model\VersionEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Event\VersionEvents;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\DeepCopy\PimcoreClassDefinitionMatcher;
use Pimcore\Model\Element\DeepCopy\PimcoreClassDefinitionReplaceFilter;
use Pimcore\Model\Element\ElementDumpStateInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\Version\Adapter\VersionStorageAdapterInterface;
use Pimcore\Model\Version\Dao;
use Pimcore\Model\Version\SetDumpStateFilter;
use Pimcore\Tool\Serialize;
use Throwable;

/**
 * @method Dao getDao()
 */
final class Version extends AbstractModel
{
    use RecursionBlockingEventDispatchHelperTrait;

    protected ?int $id = null;

    protected ?int $cid = null;

    protected ?string $ctype = null;

    /**
     * 0 is the system user
     */
    protected ?int $userId = null;

    protected ?User $user = null;

    protected string $note = '';

    protected ?int $date = null;

    protected mixed $data = null;

    /**
     * Whether $data was set or loaded, also when loading failed: a failed load is not repeated on every getData()
     */
    protected bool $dataLoaded = false;

    /**
     * Whether setData() was called since the version was loaded or saved, only then save() writes the data
     */
    protected bool $dataChanged = false;

    protected bool $public = false;

    protected bool $serialized = false;

    protected ?string $stackTrace = null;

    protected bool $generateStackTrace = true;

    protected int $versionCount = 0;

    protected ?string $binaryFileHash = null;

    protected ?int $binaryFileId = null;

    public static bool $disabled = false;

    protected bool $autoSave = false;

    protected ?string $storageType = null;

    protected VersionStorageAdapterInterface $storageAdapter;

    public function __construct()
    {
        $this->storageAdapter = Pimcore::getContainer()->get(VersionStorageAdapterInterface::class);
    }

    public static function getById(int $id): ?Version
    {
        try {
            /**
             * @var self $version
             */
            $version = self::getModelFactory()->build(Version::class);
            $version->getDao()->getById($id);

            return $version;
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * disables the versioning for the current process, this is useful for importers, ...
     * There are no new versions created, the read continues to operate normally
     *
     * Prefer {@see withoutVersioning()}: this flag does not nest, and stays set when the code before enable() throws
     * (until the next kernel.reset).
     */
    public static function disable(): void
    {
        self::$disabled = true;
    }

    /**
     * see @ self::disable()
     * just enabled the creation of versioning in the current process
     */
    public static function enable(): void
    {
        self::$disabled = false;
    }

    public static function isEnabled(): bool
    {
        return !self::$disabled;
    }

    /**
     * Runs the callback without creating versions and restores the previous state afterwards, also when it throws.
     * Calls nest: a disabled state of the caller is kept.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public static function withoutVersioning(callable $callback): mixed
    {
        $disabled = self::$disabled;
        self::$disabled = true;

        try {
            return $callback();
        } finally {
            self::$disabled = $disabled;
        }
    }

    /**
     * Creates the version, or, for an existing version, writes its metadata and - only when new data was set with
     * setData() - its data again
     */
    public function save(): void
    {
        if ($this->getId() && !$this->dataChanged) {
            $this->updateMetadata();

            return;
        }

        // check if versioning is disabled for this process, it only prevents new versions
        if (self::$disabled && !$this->getId()) {
            return;
        }

        $this->dispatchEvent(new VersionEvent($this), VersionEvents::PRE_SAVE);

        // the payload is rewritten, it may move to another storage type
        $previous = $this->getId() ? clone $this : null;

        if (!$this->date) {
            $this->setDate(time());
        }

        // get stack trace, if enabled; without the arguments, they would be stored with every version
        if ($this->getGenerateStackTrace()) {
            $this->stackTrace = self::getStackTraceAsString();
        }

        $data = $this->getData();
        if (!$data instanceof ElementInterface) {
            throw new Exception('The version has no element to save');
        }

        // this is because of lazy loaded element inside documents and objects (eg: relational data-types, fieldcollections, ...)
        Element\Service::loadAllFields($data);

        $this->setSerialized(true);

        // __sleep() only runs on the copy made by marshalData(), the element itself is unchanged
        $dataString = Serialize::serialize($this->marshalData($data));

        $binaryStream = null;
        $binarySize = null;
        if ($data instanceof Asset && $data->getType() !== 'folder') {
            // hash, size and stored binary are all taken from the same stream: the pending one of a new upload
            $binaryStream = $this->getRewindableBinaryStream($data);
            $ctx = hash_init('sha3-512');
            hash_update_stream($ctx, $binaryStream);
            $this->setBinaryFileHash(hash_final($ctx));
            rewind($binaryStream);
            $binarySize = (fstat($binaryStream) ?: [])['size'] ?? $data->getFileSize();
        }

        $this->setStorageType($this->storageAdapter->getStorageType(strlen($dataString), $binarySize));

        if ($binaryStream) {
            $this->setBinaryFileId($this->getDao()->getBinaryFileIdForHash($this->getBinaryFileHash()));
        }

        $isNew = !$this->getId();
        $this->getDao()->save();

        try {
            $this->storageAdapter->save($this, $dataString, $binaryStream);
        } catch (Throwable $e) {
            // a version row without its data would be offered for restore, and its binary hash reused by later versions
            if ($isNew) {
                $this->getDao()->delete();
            }

            throw $e;
        }
        $this->dataChanged = false;

        if ($previous && $previous->getStorageType() && $previous->getStorageType() !== $this->getStorageType()) {
            // the old payload would stay in the storage it was written to
            try {
                $this->storageAdapter->delete($previous, $this->getDao()->isBinaryHashInUse($previous->getBinaryFileHash()));
            } catch (Throwable $e) {
                Logger::error(sprintf('Version %s: unable to delete the data in storage type %s: %s', $this->getId(), $previous->getStorageType(), $e));
            }
        }

        $this->dispatchEvent(new VersionEvent($this), VersionEvents::POST_SAVE);
    }

    /**
     * The pending stream of an upload may not be seekable, a second read would open the stored (old) file instead
     *
     * @return resource
     */
    private function getRewindableBinaryStream(Asset $asset)
    {
        $stream = $asset->getStream();
        if (!is_resource($stream)) {
            throw new Exception(sprintf('The binary data of asset %s cannot be read', $asset->getId()));
        }

        if (stream_get_meta_data($stream)['seekable'] && rewind($stream)) {
            return $stream;
        }

        $copy = fopen('php://temp', 'r+b');
        stream_copy_to_stream($stream, $copy);
        rewind($copy);

        return $copy;
    }

    private static function getStackTraceAsString(): string
    {
        $lines = [];
        // without the frame of this method, like the trace of an exception created by the caller
        foreach (array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1) as $i => $frame) {
            $lines[] = sprintf(
                '#%d %s%s: %s%s%s()',
                $i,
                $frame['file'] ?? '[internal function]',
                isset($frame['line']) ? '(' . $frame['line'] . ')' : '',
                $frame['class'] ?? '',
                $frame['type'] ?? '',
                $frame['function']
            );
        }
        $lines[] = '#' . count($lines) . ' {main}';

        return implode("\n", $lines);
    }

    /**
     * Writes note, public flag and version count of an existing version, without loading or rewriting its data
     */
    public function updateMetadata(): void
    {
        if (!$this->getId()) {
            throw new Exception('Only the metadata of an existing version can be updated');
        }

        $this->getDao()->updateMetadata();
    }

    private function marshalData(ElementInterface $data): ElementInterface
    {
        $context = [
            'source' => __METHOD__,
            'conversion' => 'marshal',
            'defaultFilters' => true,
        ];

        $copier = Service::getDeepCopyInstance($data, $context);

        if ($data instanceof Concrete) {
            $copier->addFilter(
                new PimcoreClassDefinitionReplaceFilter(
                    function (Concrete $object, Data $fieldDefinition, $property, $currentValue) {
                        if ($fieldDefinition instanceof Data\CustomVersionMarshalInterface) {
                            return $fieldDefinition->marshalVersion($object, $currentValue);
                        }

                        return $currentValue;
                    }
                ),
                new PimcoreClassDefinitionMatcher(Data\CustomVersionMarshalInterface::class)
            );
        }

        $copier->addFilter(new SetDumpStateFilter(true), new PropertyMatcher(ElementDumpStateInterface::class, ElementDumpStateInterface::DUMP_STATE_PROPERTY_NAME));
        return $copier->copy($data);
    }

    private function unmarshalData(ElementInterface $data): ElementInterface
    {
        $context = [
            'source' => __METHOD__,
            'conversion' => 'unmarshal',
            'defaultFilters' => false,
        ];
        $copier = Service::getDeepCopyInstance($data, $context);

        if ($data instanceof Concrete) {
            $copier->addFilter(
                new PimcoreClassDefinitionReplaceFilter(
                    function (Concrete $object, Data $fieldDefinition, $property, $currentValue) {
                        if ($fieldDefinition instanceof Data\CustomVersionMarshalInterface) {
                            return $fieldDefinition->unmarshalVersion($object, $currentValue);
                        }

                        return $currentValue;
                    }
                ),
                new PimcoreClassDefinitionMatcher(Data\CustomVersionMarshalInterface::class)
            );
        }

        return $copier->copy($data);
    }

    /**
     * Delete this Version
     */
    public function delete(): void
    {
        $this->dispatchEvent(new VersionEvent($this), VersionEvents::PRE_DELETE);

        $this->storageAdapter->delete($this,
            $this->getDao()->isBinaryHashInUse($this->getBinaryFileHash()));

        $this->getDao()->delete();
        $this->dispatchEvent(new VersionEvent($this), VersionEvents::POST_DELETE);
    }

    /**
     * Returns null when the data cannot be read; the failure is logged once and not retried by getData()
     *
     * @internal
     */
    public function loadData(bool $renewReferences = true): mixed
    {
        $data = $this->readData();

        if ($data !== null && $renewReferences) {
            $data = Element\Service::renewReferences($data);
        }

        // not setData(): the loaded data is not written again by save()
        $this->data = $data;
        $this->dataLoaded = true;

        return $data;
    }

    private function readData(): mixed
    {
        $data = $this->storageAdapter->loadMetaData($this);

        // '' and '0' are valid unserialized data
        if ($data === null || ($this->getSerialized() && $data === '')) {
            Logger::err(sprintf('Version %s: cannot read version data with storage type: %s', $this->getId(), $this->getStorageType()));

            return null;
        }

        if ($this->getSerialized()) {
            // no Pimcore::collectGarbage(): it closed idle DB connections and log handlers and dropped the elements
            // loaded earlier in the request from the runtime cache; the dumps no longer register themselves there
            $data = Serialize::unserialize($data);
            if ($data instanceof __PHP_Incomplete_Class) {
                Logger::err(sprintf('Version %s: cannot read version data from file system because of incompatible class.', $this->getId()));

                return null;
            }
            if (!$data instanceof ElementInterface) {
                Logger::err(sprintf('Version %s: the version data is not an element.', $this->getId()));

                return null;
            }

            $data = $this->unmarshalData($data);
        }

        if ($data instanceof Concrete) {
            $data->markAllLazyLoadedKeysAsLoaded();
        }

        if ($data instanceof Asset) {
            $binaryStream = $this->storageAdapter->loadBinaryData($this);
            if ($binaryStream) {
                $data->setStream($binaryStream);
            } elseif ($this->getBinaryFileHash()) {
                // without a stream the asset would return its current file, and a restore would store it as this version
                Logger::err(sprintf('Version %s: the binary data of the asset is missing.', $this->getId()));

                return null;
            }
        }

        return $data;
    }

    public function getFileStream(): mixed
    {
        return $this->storageAdapter->getFileStream($this);
    }

    public function getBinaryFileStream(): mixed
    {
        return $this->storageAdapter->getBinaryFileStream($this);
    }

    public function getCid(): ?int
    {
        return $this->cid;
    }

    public function getDate(): ?int
    {
        return $this->date;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @return $this
     */
    public function setCid(?int $cid): Version
    {
        $this->cid = $cid;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDate(?int $date): Version
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return $this
     */
    public function setId(int $id): Version
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return $this
     */
    public function setNote(string $note): Version
    {
        $this->note = $note;

        return $this;
    }

    /**
     * @return $this
     */
    public function setUserId(?int $userId): Version
    {
        // kept for the system user and deleted users too, the user is loaded by getUser()
        if ($userId !== $this->userId) {
            $this->user = null;
        }
        $this->userId = $userId;

        return $this;
    }

    public function getData(): mixed
    {
        if (!$this->dataLoaded) {
            $this->loadData();
        }

        return $this->data;
    }

    /**
     * @return $this
     */
    public function setData(?ElementInterface $data): Version
    {
        $this->data = $data;
        $this->dataLoaded = true;
        $this->dataChanged = true;

        return $this;
    }

    public function getSerialized(): bool
    {
        return $this->serialized;
    }

    /**
     * @return $this
     */
    public function setSerialized(bool $serialized): Version
    {
        $this->serialized = $serialized;

        return $this;
    }

    public function getCtype(): ?string
    {
        return $this->ctype;
    }

    /**
     * @return $this
     */
    public function setCtype(?string $ctype): Version
    {
        $this->ctype = $ctype;

        return $this;
    }

    public function getUser(): ?User
    {
        if (!$this->user && $this->userId) {
            $this->user = User::getById($this->userId);
        }

        return $this->user;
    }

    /**
     * @return $this
     */
    public function setUser(?User $user): Version
    {
        $this->user = $user;

        return $this;
    }

    public function getPublic(): bool
    {
        return $this->public;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    /**
     * @return $this
     */
    public function setPublic(bool $public): Version
    {
        $this->public = $public;

        return $this;
    }

    public function getVersionCount(): int
    {
        return $this->versionCount ?: 0;
    }

    public function setVersionCount(int $versionCount): void
    {
        $this->versionCount = $versionCount;
    }

    public function getBinaryFileHash(): ?string
    {
        return $this->binaryFileHash;
    }

    public function setBinaryFileHash(?string $binaryFileHash): void
    {
        $this->binaryFileHash = $binaryFileHash;
    }

    public function getBinaryFileId(): ?int
    {
        return $this->binaryFileId;
    }

    public function setBinaryFileId(?int $binaryFileId): void
    {
        $this->binaryFileId = $binaryFileId;
    }

    public function getGenerateStackTrace(): bool
    {
        return $this->generateStackTrace;
    }

    public function setGenerateStackTrace(bool $generateStackTrace): void
    {
        $this->generateStackTrace = $generateStackTrace;
    }

    public function setStackTrace(?string $stackTrace): void
    {
        $this->stackTrace = $stackTrace;
    }

    public function getStackTrace(): ?string
    {
        return $this->stackTrace;
    }

    public function isAutoSave(): bool
    {
        return $this->autoSave;
    }

    /**
     * @return $this
     */
    public function setAutoSave(bool $autoSave): Version
    {
        $this->autoSave = $autoSave;

        return $this;
    }

    public function getStorageType(): ?string
    {
        return $this->storageType;
    }

    public function setStorageType(string $storageType): void
    {
        $this->storageType = $storageType;
    }
}
