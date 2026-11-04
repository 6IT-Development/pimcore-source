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

namespace Pimcore\Model\Element\Recyclebin;

use DeepCopy\Matcher\PropertyMatcher;
use DeepCopy\TypeFilter\ReplaceFilter;
use DeepCopy\TypeMatcher\TypeMatcher;
use Exception;
use League\Flysystem\StorageAttributes;
use Pimcore;
use Pimcore\Cache;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Element\DeepCopy\PimcoreClassDefinitionMatcher;
use Pimcore\Model\Element\DeepCopy\PimcoreClassDefinitionReplaceFilter;
use Pimcore\Tool\Admin;
use Pimcore\Tool\Serialize;
use Pimcore\Tool\Storage;
use Throwable;

/**
 * @internal
 *
 * @method \Pimcore\Model\Element\Recyclebin\Item\Dao getDao()
 */
class Item extends Model\AbstractModel
{
    protected int $id;

    protected string $path;

    protected string $type;

    protected string $subtype;

    protected int $amount = 0;

    protected Element\ElementInterface $element;

    protected int $date;

    protected string $deletedby;

    /**
     * Tag IDs of the element and its children, by element type and ID. The tag assignments are removed when an
     * element is deleted, so they are kept here to restore them.
     *
     * @var array<string, array<int, int[]>>
     */
    protected array $tags = [];

    /**
     * Definitions of the sites whose root document is the element or one of its children, by document ID. Deleting
     * the root document deletes the site, so it is kept here to restore it.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $sites = [];

    /**
     * The elements {@see loadChildren()} put into dump state, to reset them once they are serialized.
     *
     * @var Element\ElementDumpStateInterface[]
     */
    private array $dumpStateElements = [];

    public static function create(Element\ElementInterface $element, ?Model\User $user = null): void
    {
        $item = new self();
        $item->setElement($element);
        $item->save($user);
    }

    public static function getById(int $id): ?Item
    {
        try {
            $item = new self();
            $item->getDao()->getById($id);

            return $item;
        } catch (Model\Exception\NotFoundException) {
            return null;
        }
    }

    /**
     * @throws Exception
     */
    public function restore(?Model\User $user = null): void
    {
        $dummy = null;
        $raw = Storage::get('recycle_bin')->read($this->getStorageFile());
        $element = Serialize::unserialize($raw);

        // check the permission before anything is written
        if ($user || Admin::getCurrentUser()) {
            $parent = $element->getParent();
            if ($parent && !$parent->isAllowed('publish', $user)) {
                throw new Exception('Not sufficient permissions');
            }
        }

        $this->renameOnCollision($element);

        $this->tags = $this->loadTags();
        $this->sites = $this->loadSites();

        $isDirtyDetectionDisabled = DataObject::isDirtyDetectionDisabled();

        try {
            if ($element instanceof DataObject\AbstractObject) {
                // create an empty object first and clone it
                // see https://github.com/pimcore/pimcore/issues/4219
                $versionsEnabled = Model\Version::isEnabled();
                Model\Version::disable();

                try {
                    $className = get_class($element);
                    /** @var AbstractObject $dummy */
                    $dummy = Pimcore::getContainer()->get('pimcore.model.factory')->build($className);
                    $dummy->setId($element->getId());
                    $dummy->setParentId($element->getParentId() ?: 1);
                    $dummy->setKey($element->getKey());
                    if ($dummy instanceof DataObject\Concrete) {
                        $dummy->setOmitMandatoryCheck(true);
                    }
                    $dummy->save(['isRecycleBinRestore' => true]);
                } finally {
                    if ($versionsEnabled) {
                        Model\Version::enable();
                    }
                }
            }

            DataObject::disableDirtyDetection();

            $this->doRecursiveRestore($element);
        } catch (Throwable $e) {
            Logger::error((string)$e);

            try {
                // the dummy has the id of the element even if its save failed, remove it only when it is in the tree
                if ($dummy && DataObject::getById($dummy->getId(), ['force' => true])) {
                    $dummy->delete();
                }
            } catch (Throwable $deleteException) {
                Logger::error('Unable to remove the placeholder of the restored object: ' . $deleteException);
            }

            throw $e;
        } finally {
            DataObject::setDisableDirtyDetection($isDirtyDetectionDisabled);
        }

        $this->delete();
    }

    /**
     * A restored element whose path is taken gets the suffix "_restore", or "_restore_2", "_restore_3" ... while that
     * is taken too. Its children are restored below it, so only the element itself can collide.
     */
    private function renameOnCollision(Element\ElementInterface $element): void
    {
        $type = Element\Service::getElementType($element);
        $key = (string)$element->getKey();

        for ($i = 1; Element\Service::getElementByPath($type, $element->getRealFullPath()); $i++) {
            $element->setKey($key . '_restore' . ($i > 1 ? '_' . $i : ''));
        }
    }

    public function save(?Model\User $user = null): void
    {
        $this->setType(Element\Service::getElementType($this->getElement()));
        $this->setSubtype($this->getElement()->getType());
        $this->setPath($this->getElement()->getRealFullPath());
        $this->setDate(time());

        try {
            $this->loadChildren($this->getElement());

            if ($user instanceof Model\User) {
                $this->setDeletedby($user->getName());
            }

            // serialize data
            Element\Service::loadAllFields($this->element);

            $condensedData = $this->marshalData($this->getElement());
            $data = Serialize::serialize($condensedData);
        } finally {
            // the live elements only need the dump state while they are serialized
            foreach ($this->dumpStateElements as $element) {
                $element->setInDumpState(false);
            }
            $this->dumpStateElements = [];
        }

        // the storage files are named by the id of the row
        $this->getDao()->save();

        try {
            $storage = Storage::get('recycle_bin');
            $storage->write($this->getStorageFile(), $data);

            $saveBinaryData = function ($element, $rec, self $scope) use ($storage) {
                // assets are kind of special because they can contain massive amount of binary data which isn't serialized, we create separate files for them
                if ($element instanceof Asset) {
                    if ($element->getType() != 'folder') {
                        $storage->writeStream($scope->getStorageFileBinary($element), $element->getStream());
                    }

                    $children = $element->getChildren();
                    foreach ($children as $child) {
                        $rec($child, $rec, $scope);
                    }
                }
            };

            $saveBinaryData($this->getElement(), $saveBinaryData, $this);

            if ($this->tags) {
                $storage->write($this->getStorageFileTags(), json_encode($this->tags, JSON_THROW_ON_ERROR));
            }

            if ($this->sites) {
                $storage->write($this->getStorageFileSites(), json_encode($this->sites, JSON_THROW_ON_ERROR));
            }
        } catch (Throwable $e) {
            // no row without its files
            try {
                $this->delete();
            } catch (Throwable $deleteException) {
                Logger::error('Unable to remove the incomplete recycle bin item ' . $this->getId() . ': ' . $deleteException);
            }

            throw $e;
        }
    }

    /**
     * Deletes the row and the storage files. The files besides the serialized element are kept in a directory per
     * item; items stored before that have them next to it, named "<id>_...".
     */
    public function delete(): void
    {
        $storage = Storage::get('recycle_bin');
        $storage->delete($this->getStorageFile());

        $directory = $this->getStorageDirectory();
        $hasDirectory = $storage->directoryExists($directory);
        if ($hasDirectory) {
            $storage->deleteDirectory($directory);
        }

        foreach (['tags.json', 'sites.json'] as $name) {
            $storage->delete($this->getLegacyStorageFile($name));
        }

        // only legacy asset items have binary files that are not in the directory
        if (!$hasDirectory && $this->getType() === 'asset') {
            $prefix = $this->getType() . '/' . $this->getId() . '_';
            $files = $storage->listContents($this->getType())->filter(
                fn (StorageAttributes $item) => $item->isFile() && str_starts_with($item->path(), $prefix)
            );

            /** @var StorageAttributes $item */
            foreach ($files as $item) {
                $storage->delete($item->path());
            }
        }

        $this->getDao()->delete();
    }

    public function loadChildren(Element\ElementInterface $element): void
    {
        $this->amount++;

        Element\Service::loadAllFields($element);

        // for all
        $element->getProperties();
        $element->getScheduledTasks();

        $elementType = Element\Service::getElementType($element);
        $tagIds = array_map(fn (Element\Tag $tag) => $tag->getId(), Element\Tag::getTagsForElement($elementType, $element->getId()));
        if ($tagIds) {
            $this->tags[$elementType][$element->getId()] = $tagIds;
        }

        if ($element instanceof Document && $site = Model\Site::getByRootId($element->getId())) {
            $this->sites[$element->getId()] = [
                'domains' => $site->getDomains(),
                'mainDomain' => $site->getMainDomain(),
                'errorDocument' => $site->getErrorDocument(),
                'localizedErrorDocuments' => $site->getLocalizedErrorDocuments(),
                'redirectToMainDomain' => $site->getRedirectToMainDomain(),
            ];
        }

        if ($element instanceof Element\ElementDumpStateInterface && !$element->isInDumpState()) {
            $element->setInDumpState(true);
            $this->dumpStateElements[] = $element;
        }

        // we need to add the tag of each item to the cache cleared stack, so that the item doesn't gets into the cache
        // with the dump state set to true, because this would cause major issues in wakeUp()
        Cache::addIgnoredTagOnSave($element->getCacheTag());

        if (method_exists($element, 'getChildren')) {
            if ($element instanceof DataObject\AbstractObject) {
                // because we also want variants
                $children = $element->getChildren(DataObject::$types, true);
            } elseif ($element instanceof Document) {
                $children = $element->getChildren(true);
            } else {
                $children = $element->getChildren();
            }

            foreach ($children as $child) {
                $this->loadChildren($child);
            }
        }
    }

    /**
     * @return array<string, array<int, int[]>>
     */
    private function loadTags(): array
    {
        $storage = Storage::get('recycle_bin');
        $file = $this->findStorageFile($this->getStorageFileTags(), $this->getLegacyStorageFile('tags.json'));

        // items created before the tags were stored don't have this file
        if ($file === null) {
            return [];
        }

        $tags = json_decode($storage->read($file), true);

        return is_array($tags) ? $tags : [];
    }

    private function restoreTags(Element\ElementInterface $element): void
    {
        $elementType = Element\Service::getElementType($element);
        $tagIds = $this->tags[$elementType][$element->getId()] ?? [];
        if (!$tagIds) {
            return;
        }

        // tags deleted since the element was deleted are skipped
        $tags = array_filter(array_map(fn ($tagId) => Element\Tag::getById((int) $tagId), $tagIds));
        Element\Tag::setTagsForElement($elementType, $element->getId(), $tags);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadSites(): array
    {
        $storage = Storage::get('recycle_bin');
        $file = $this->findStorageFile($this->getStorageFileSites(), $this->getLegacyStorageFile('sites.json'));

        // items created before the sites were stored don't have this file
        if ($file === null) {
            return [];
        }

        $sites = json_decode($storage->read($file), true);

        return is_array($sites) ? $sites : [];
    }

    private function restoreSite(Element\ElementInterface $element): void
    {
        $siteData = $element instanceof Document ? $this->sites[$element->getId()] ?? null : null;
        if (!$siteData || Model\Site::getByRootId($element->getId())) {
            return;
        }

        // the document is restored anyway, a site that cannot be restored (e.g. invalid domains) is only logged
        try {
            $site = Model\Site::create($siteData + ['rootId' => $element->getId()]);
            $site->save();
        } catch (Exception $e) {
            Logger::error(sprintf('Unable to restore the site of document %s: %s', $element->getId(), $e));
        }
    }

    /**
     * @throws Exception
     */
    protected function doRecursiveRestore(Element\ElementInterface $element): void
    {
        $storage = Storage::get('recycle_bin');
        $restoreBinaryData = function (Element\ElementInterface $element, self $scope) use ($storage) {
            // assets are kinda special because they can contain massive amount of binary data which isn't serialized, we create separate files for them
            if ($element instanceof Asset) {
                $binFile = $scope->findStorageFile(
                    $scope->getStorageFileBinary($element),
                    $scope->getLegacyStorageFile(sprintf('%s-%s.bin', Element\Service::getElementType($element), $element->getId()))
                );
                if ($binFile !== null) {
                    $element->setStream($storage->readStream($binFile));
                }
            }
        };

        $element = $this->unmarshalData($element);
        $restoreBinaryData($element, $this);

        if ($element instanceof DataObject\Concrete) {
            $element->markAllLazyLoadedKeysAsLoaded();
            $element->setOmitMandatoryCheck(true);
        }
        $element->save(['isRecycleBinRestore' => true]);
        $this->restoreTags($element);
        $this->restoreSite($element);

        if (method_exists($element, 'getChildren')) {
            if ($element instanceof DataObject\AbstractObject) {
                $children = $element->getChildren(DataObject::$types, true);
            } elseif ($element instanceof Document) {
                $children = $element->getChildren(true);
            } else {
                $children = $element->getChildren();
            }
            foreach ($children as $child) {
                $child->setParentId($element->getId());
                $this->doRecursiveRestore($child);
            }
        }
    }

    public function marshalData(Element\ElementInterface $data): Element\ElementInterface|array
    {
        //for full dump of relation fields in container types
        $context = [
            'source' => __METHOD__,
            'default' => true,
        ];
        $copier = Element\Service::getDeepCopyInstance($data, $context);

        $copier->addTypeFilter(
            new ReplaceFilter(
                function ($currentValue) {
                    $elementType = Element\Service::getElementType($currentValue);
                    return new Element\ElementDescriptor($elementType, $currentValue->getId());
                }
            ),
            new class((string)$this->element) extends TypeMatcher {
                public function matches(mixed $element): bool
                {
                    //compress only elements with full_dump_state = false
                    return $element instanceof Element\ElementInterface && $element instanceof Element\ElementDumpStateInterface && !($element->isInDumpState());
                }
            }
        );

        //filter for marshaling custom data-types which implements CustomRecyclingMarshalInterface
        if ($data instanceof Concrete) {
            $copier->addFilter(
                new PimcoreClassDefinitionReplaceFilter(
                    function (Concrete $object, Data $fieldDefinition, $property, $currentValue) {
                        if ($fieldDefinition instanceof Data\CustomRecyclingMarshalInterface) {
                            return $fieldDefinition->marshalRecycleData($object, $currentValue);
                        }

                        return $currentValue;
                    }
                ), new PimcoreClassDefinitionMatcher(Data\CustomRecyclingMarshalInterface::class)
            );
        }
        $copier->addFilter(new Model\Version\SetDumpStateFilter(true), new PropertyMatcher(Element\ElementDumpStateInterface::class, Element\ElementDumpStateInterface::DUMP_STATE_PROPERTY_NAME));

        return $copier->copy($data);
    }

    public function unmarshalData(Element\ElementInterface $data): Element\ElementInterface
    {
        $context = [
            'source' => __METHOD__,
            'conversion' => 'unmarshal',
        ];
        $copier = Element\Service::getDeepCopyInstance($data, $context);

        //filter for unmarshaling custom data-types which implements CustomRecyclingMarshalInterface
        if ($data instanceof Concrete) {
            $copier->addFilter(
                new PimcoreClassDefinitionReplaceFilter(
                    function (Concrete $object, Data $fieldDefinition, $property, $currentValue) {
                        if ($fieldDefinition instanceof Data\CustomRecyclingMarshalInterface) {
                            return $fieldDefinition->unmarshalRecycleData($object, $currentValue);
                        }

                        return $currentValue;
                    }
                ), new PimcoreClassDefinitionMatcher(Data\CustomRecyclingMarshalInterface::class)
            );
        }

        return $copier->copy($data);
    }

    public function getStorageFile(): string
    {
        return sprintf('%s/%s.psf', $this->getType(), $this->getId());
    }

    /**
     * @deprecated since pimcore 11.3 and will be removed in 12.0
     * @see Item::getStorageFile()
     */
    public function getStoreageFile(): string
    {
        return $this->getStorageFile();
    }

    private function getStorageDirectory(): string
    {
        return sprintf('%s/%s', $this->getType(), $this->getId());
    }

    private function getStorageFileTags(): string
    {
        return $this->getStorageDirectory() . '/tags.json';
    }

    private function getStorageFileSites(): string
    {
        return $this->getStorageDirectory() . '/sites.json';
    }

    protected function getStorageFileBinary(Element\ElementInterface $element): string
    {
        return sprintf('%s/%s-%s.bin', $this->getStorageDirectory(), Element\Service::getElementType($element), $element->getId());
    }

    /**
     * The name of a file of an item stored before the files got a directory per item.
     */
    private function getLegacyStorageFile(string $name): string
    {
        return sprintf('%s/%s_%s', $this->getType(), $this->getId(), $name);
    }

    private function findStorageFile(string ...$files): ?string
    {
        $storage = Storage::get('recycle_bin');
        foreach ($files as $file) {
            if ($storage->fileExists($file)) {
                return $file;
            }
        }

        return null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSubtype(): string
    {
        return $this->subtype;
    }

    public function setSubtype(string $subtype): static
    {
        $this->subtype = $subtype;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDate(): int
    {
        return $this->date;
    }

    public function setDate(int $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getElement(): Element\ElementInterface
    {
        return $this->element;
    }

    public function setElement(Element\ElementInterface $element): static
    {
        $this->element = $element;

        return $this;
    }

    public function setDeletedby(string $username): static
    {
        $this->deletedby = $username;

        return $this;
    }

    public function getDeletedby(): string
    {
        return $this->deletedby;
    }
}
