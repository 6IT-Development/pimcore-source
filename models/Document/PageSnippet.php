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

namespace Pimcore\Model\Document;

use Exception;
use Pimcore;
use Pimcore\Config;
use Pimcore\Document\Editable\EditableUsageResolver;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Http\RequestHelper;
use Pimcore\Logger;
use Pimcore\Messenger\VersionDeleteMessage;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Editable\Loader\EditableLoaderInterface;
use Pimcore\SystemSettingsConfig;
use Pimcore\Tool;
use Pimcore\Tool\Frontend;
use Throwable;

/**
 * @method Model\Document\PageSnippet\Dao getDao()
 * @method Model\Version|null getLatestVersion(?int $userId = null, bool $includingPublished = false)
 */
abstract class PageSnippet extends Model\Document
{
    use Model\Element\Traits\ScheduledTasksTrait;

    /**
     * @internal
     *
     */
    protected ?string $controller = null;

    /**
     * @internal
     *
     */
    protected ?string $template = null;

    /**
     * Contains all content-editables of the document
     *
     * @internal
     *
     *
     */
    protected ?array $editables = null;

    /**
     * Contains all versions of the document
     *
     * @internal
     *
     */
    protected ?array $versions = null;

    /**
     * @internal
     *
     */
    protected ?int $contentMainDocumentId = null;

    protected ?int $contentMasterDocumentId;

    /**
     * @internal
     */
    protected bool $supportsContentMain = true;

    /**
     * @internal
     *
     */
    protected ?bool $missingRequiredEditable = null;

    /**
     * @internal
     */
    protected ?bool $staticGeneratorEnabled = null;

    /**
     * @internal
     *
     */
    protected ?int $staticGeneratorLifetime = null;

    /**
     * @internal
     *
     */
    protected array $inheritedEditables = [];

    /**
     * Names of the stored editables that could not be loaded (unknown type, unreadable data); their rows are kept
     * on save unless an editable of the same name replaces them
     *
     * @internal
     *
     * @var string[]
     */
    protected array $unloadableEditableNames = [];

    private static bool $getInheritedValues = false;

    /**
     * Ids of the documents whose editables are being inherited from their content-main document, so that a cycle
     * stored before the chain was validated ends the inheritance instead of recursing without end
     *
     * @var array<int, true>
     */
    private static array $contentMainResolution = [];

    public function __construct()
    {
        $this->contentMasterDocumentId = &$this->contentMainDocumentId;
    }

    public static function setGetInheritedValues(bool $getInheritedValues): void
    {
        self::$getInheritedValues = $getInheritedValues;
    }

    public static function getGetInheritedValues(): bool
    {
        return self::$getInheritedValues;
    }

    public function save(array $parameters = []): static
    {
        // checking the required editables renders the document, so this needs to be
        // before the database transaction, see also https://github.com/pimcore/pimcore/issues/8992
        $this->checkMissingRequiredEditable();
        if ($this->getMissingRequiredEditable() && $this->getPublished()) {
            throw new Model\Element\ValidationException('Prevented publishing document - missing values for required editables');
        }

        return parent::save($parameters);
    }

    protected function update(array $params = []): void
    {
        // update elements
        $editables = $this->getEditables();
        $this->getDao()->deleteAllEditables(
            array_diff($this->unloadableEditableNames, array_keys($this->editables ?? []))
        );

        parent::update($params);

        foreach ($editables as $editable) {
            if (!$editable->getInherited()) {
                $editable->setDao(null);
                $editable->setDocumentId($this->getId());
                $editable->save();
            }
        }

        // scheduled tasks are saved in $this->saveVersion();
        // save version if needed
        $this->saveVersion(false, false, $params['versionNote'] ?? null);
    }

    /**
     *
     *
     * @throws Exception
     */
    public function saveVersion(bool $setModificationDate = true, bool $saveOnlyVersion = true, ?string $versionNote = null, bool $isAutoSave = false): ?Model\Version
    {
        try {
            // hook should be also called if "save only new version" is selected
            if ($saveOnlyVersion) {
                $preUpdateEvent = new DocumentEvent($this, [
                    'saveVersionOnly' => true,
                    'isAutoSave' => $isAutoSave,
                ]);
                Pimcore::getEventDispatcher()->dispatch($preUpdateEvent, DocumentEvents::PRE_UPDATE);
            }

            // set date
            if ($setModificationDate) {
                $this->setModificationDate(time());
            }

            // scheduled tasks are saved always, they are not versioned!
            $this->saveScheduledTasks();

            // create version
            $version = null;

            // only create a new version if there is at least 1 allowed
            // or if saveVersion() was called directly (it's a newer version of the object)
            $documentsConfig = SystemSettingsConfig::get()['documents'];
            if ((is_null($documentsConfig['versions']['days'] ?? null) && is_null($documentsConfig['versions']['steps'] ?? null))
                || (!empty($documentsConfig['versions']['steps']))
                || !empty($documentsConfig['versions']['days'])
                || $setModificationDate) {
                $saveStackTrace = !($documentsConfig['versions']['disable_stack_trace'] ?? false);
                $version = $this->doSaveVersion($versionNote, $saveOnlyVersion, $saveStackTrace, $isAutoSave);
            }

            // hook should be also called if "save only new version" is selected
            if ($saveOnlyVersion) {
                $postUpdateEvent = new DocumentEvent($this, [
                    'saveVersionOnly' => true,
                    'isAutoSave' => $isAutoSave,
                ]);
                Pimcore::getEventDispatcher()->dispatch($postUpdateEvent, DocumentEvents::POST_UPDATE);
            }

            return $version;
        } catch (Throwable $e) {
            $postUpdateFailureEvent = new DocumentEvent($this, [
                'saveVersionOnly' => true,
                'exception' => $e,
                'isAutoSave' => $isAutoSave,
            ]);
            Pimcore::getEventDispatcher()->dispatch($postUpdateFailureEvent, DocumentEvents::POST_UPDATE_FAILURE);

            throw $e;
        }
    }

    protected function doDelete(): void
    {
        // Dispatch Symfony Message Bus to delete versions, after the commit: a non-transactional transport would not
        // roll back with it
        self::afterDeleteCommit(fn () => Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(
            new VersionDeleteMessage(Service::getElementType($this), $this->getId())
        ));

        parent::doDelete();
    }

    public function getCacheTags(array $tags = []): array
    {
        $tags = parent::getCacheTags($tags);

        foreach ($this->getEditables() as $editable) {
            $tags = $editable->getCacheTags($this, $tags);
        }

        return $tags;
    }

    public function resolveDependencies(): array
    {
        $dependencies = [parent::resolveDependencies()];

        foreach ($this->getEditables() as $editable) {
            $dependencies[] = $editable->resolveDependencies();
        }

        if ($this->getContentMainDocument() instanceof Document) {
            $mainDocumentId = $this->getContentMainDocument()->getId();
            $dependencies[] = [
                'document_' . $mainDocumentId => [
                    'id' => $mainDocumentId,
                    'type' => 'document',
                ],
            ];
        }

        return array_merge(...$dependencies);
    }

    public function getController(): ?string
    {
        if (empty($this->controller)) {
            $this->controller = Pimcore::getContainer()->getParameter('pimcore.documents.default_controller');
        }

        return $this->controller;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @return $this
     */
    public function setController(?string $controller): static
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * @return $this
     */
    public function setTemplate(?string $template): static
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Set raw data of an editable (eg. for editmode)
     *
     * @return $this
     * @internal
     *
     */
    public function setRawEditable(string $name, string $type, mixed $data): static
    {
        try {
            if ($type) {
                /** @var EditableLoaderInterface $loader */
                $loader = Pimcore::getContainer()->get(Document\Editable\Loader\EditableLoader::class);
                $editable = $loader->build($type);

                $this->editables = $this->editables ?? [];
                $this->editables[$name] = $editable;
                $this->editables[$name]->setDataFromEditmode($data);
                $this->editables[$name]->setName($name);
                $this->editables[$name]->setDocument($this);
            }
        } catch (Exception) {
            Logger::warning("can't set element " . $name . ' with the type ' . $type . ' to the document: ' . $this->getRealFullPath());
        }

        return $this;
    }

    /**
     * Set an element with the given key/name
     *
     *
     * @return $this
     */
    public function setEditable(Editable $editable): static
    {
        $this->getEditables();
        $this->editables[$editable->getName()] = $editable;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeEditable(string $name): static
    {
        $this->getEditables();
        if (isset($this->editables[$name])) {
            unset($this->editables[$name]);
        }

        return $this;
    }

    /**
     * Get an editable with the given key/name
     *
     *
     */
    public function getEditable(string $name): ?Editable
    {
        $editables = $this->getEditables();
        if (isset($this->editables[$name])) {
            return $editables[$name];
        }

        $inheritedEditable = null;
        if (array_key_exists($name, $this->inheritedEditables)) {
            $inheritedEditable = $this->inheritedEditables[$name];
        }

        if (!$inheritedEditable) {
            // check for content main document (inherit data)
            $contentMainDocument = $this->getContentMainDocument();
            if ($contentMainDocument instanceof self && $contentMainDocument->getId() != $this->getId()) {
                $inheritedEditable = $this->inheritFromContentMain(
                    $contentMainDocument,
                    fn (self $document) => $document->getEditable($name)
                );
                if ($inheritedEditable) {
                    $inheritedEditable = clone $inheritedEditable;
                    $inheritedEditable->setInherited(true);
                    $this->inheritedEditables[$name] = $inheritedEditable;
                }
            }
        }

        return $inheritedEditable;
    }

    /**
     * Runs $resolve on the content-main document unless that document is already resolving its own inheritance
     * further up the call stack (a content-main cycle), in which case nothing is inherited
     *
     * @template T
     *
     * @param callable(self): T $resolve
     *
     * @return T|null
     */
    private function inheritFromContentMain(self $contentMainDocument, callable $resolve): mixed
    {
        if (isset(self::$contentMainResolution[(int) $contentMainDocument->getId()])) {
            return null;
        }

        $id = (int) $this->getId();
        self::$contentMainResolution[$id] = true;

        try {
            return $resolve($contentMainDocument);
        } finally {
            unset(self::$contentMainResolution[$id]);
        }
    }

    /**
     * The whole content-main chain is checked whatever $validate is: a cycle would make every inherited editable
     * lookup recurse without end.
     *
     * @return $this
     *
     * @throws Exception
     */
    public function setContentMainDocumentId(int|string|null $contentMainDocumentId, bool $validate = false): static
    {
        // this is that the path is automatically converted to ID => when setting directly from admin UI
        if (!is_numeric($contentMainDocumentId) && !empty($contentMainDocumentId)) {
            if ($contentMainDocument = Document\PageSnippet::getByPath($contentMainDocumentId)) {
                $contentMainDocumentId = $contentMainDocument->getId();
            } else {
                // Content main document was deleted or don't exist
                $contentMainDocumentId = null;
            }
        }

        // Don't set the content main document if the document is already part of the main document chain
        if ($contentMainDocumentId) {
            if ($currentContentMainDocument = Document\PageSnippet::getById($contentMainDocumentId)) {
                // the visited ids also end a cycle stored further up the chain, which does not contain this document
                $visited = [];
                do {
                    if ($currentContentMainDocument->getId() === $this->getId()) {
                        throw new Exception('This document is already part of the main document chain, please choose a different one.');
                    }
                    $visited[$currentContentMainDocument->getId()] = true;
                    $currentContentMainDocument = $currentContentMainDocument->getContentMainDocument();
                } while ($currentContentMainDocument && !isset($visited[$currentContentMainDocument->getId()]));
            } else {
                // Content main document was deleted or don't exist
                $contentMainDocumentId = null;
            }
        }

        $this->contentMainDocumentId = ($contentMainDocumentId ? (int)$contentMainDocumentId : null);

        return $this;
    }

    public function getContentMainDocumentId(): ?int
    {
        return $this->contentMainDocumentId;
    }

    public function getContentMainDocument(): ?PageSnippet
    {
        if ($mainDocumentId = $this->getContentMainDocumentId()) {
            return Document\PageSnippet::getById($mainDocumentId);
        }

        return null;
    }

    /**
     * @return $this
     */
    public function setContentMainDocument(?PageSnippet $document): static
    {
        if ($document instanceof self) {
            $this->setContentMainDocumentId($document->getId(), true);
        } else {
            $this->setContentMainDocumentId(null);
        }

        return $this;
    }

    public function hasEditable(string $name): bool
    {
        return $this->getEditable($name) !== null;
    }

    /**
     * @return Editable[]
     */
    public function getEditables(): array
    {
        if ($this->editables === null) {
            $this->setEditables($this->getDao()->getEditables());
        }

        if (self::getGetInheritedValues() && $this->supportsContentMain() && $contentMainDocument = $this->getContentMainDocument()) {
            $contentMainEditables = $this->inheritFromContentMain(
                $contentMainDocument,
                fn (self $document) => $document->getEditables()
            ) ?? [];

            // the inherited editables are clones flagged as inherited, as in getEditable(), and are not kept as the
            // document's own: save() would store them for this document and point the main document's instances to it
            $editables = [];
            foreach ($contentMainEditables as $name => $contentMainEditable) {
                $editables[$name] = $this->editables[$name]
                    ?? ($this->inheritedEditables[$name] ??= (clone $contentMainEditable)->setInherited(true));
            }

            return $editables + $this->editables;
        }

        return $this->editables;
    }

    /**
     * @internal
     *
     * @param string[] $names
     */
    public function setUnloadableEditableNames(array $names): void
    {
        $this->unloadableEditableNames = $names;
    }

    /**
     * @return $this
     */
    public function setEditables(?array $editables): static
    {
        $this->editables = $editables;

        return $this;
    }

    /**
     * @return Model\Version[]
     */
    public function getVersions(): array
    {
        if ($this->versions === null) {
            $this->setVersions($this->getDao()->getVersions());
        }

        return $this->versions;
    }

    /**
     * @return $this
     */
    public function setVersions(?array $versions): static
    {
        $this->versions = $versions;

        return $this;
    }

    /**
     * @see Document::getFullPath
     *
     */
    public function getHref(): string
    {
        return $this->getFullPath();
    }

    public function __sleep(): array
    {
        $finalVars = [];
        $parentVars = parent::__sleep();

        $blockedVars = ['inheritedEditables'];

        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function getUrl(?string $hostname = null, ?string $scheme = null): string
    {
        if (!$scheme) {
            $scheme = 'http://';

            /** @var RequestHelper $requestHelper */
            $requestHelper = Pimcore::getContainer()->get(RequestHelper::class);
            if ($requestHelper->hasMainRequest()) {
                $scheme = $requestHelper->getMainRequest()->getScheme() . '://';
            }
        }

        if (!$hostname) {
            $hostname = Config::getSystemConfiguration('general')['domain'];
            if (empty($hostname)) {
                if (!$hostname = Tool::getHostname()) {
                    throw new Exception('No hostname available');
                }
            }
        }

        $url = $scheme . $hostname;
        if ($this instanceof Page && $this->getPrettyUrl()) {
            $url .= $this->getPrettyUrl();
        } else {
            $url .= $this->getFullPath();
        }

        $site = Frontend::getSiteForDocument($this);
        if ($site instanceof Model\Site && $site->getMainDomain()) {
            $url = $scheme . $site->getMainDomain() . preg_replace('@^' . $site->getRootPath() . '/?@', '/', $this->getRealFullPath());
        }

        return $url;
    }

    /**
     * checks if the document is missing values for required editables
     *
     */
    public function getMissingRequiredEditable(): ?bool
    {
        return $this->missingRequiredEditable;
    }

    /**
     * @return $this
     */
    public function setMissingRequiredEditable(?bool $missingRequiredEditable): static
    {
        $this->missingRequiredEditable = $missingRequiredEditable;

        return $this;
    }

    /**
     * @internal
     *
     */
    public function supportsContentMain(): bool
    {
        return $this->supportsContentMain;
    }

    /**
     * Validates if there is a missing value for required editable
     *
     * Recomputed on every save: a stored true must not block publishing once the editables are filled. The result is
     * null (unknown) when the document cannot be rendered.
     *
     * @internal
     */
    protected function checkMissingRequiredEditable(): void
    {
        $this->setMissingRequiredEditable(null);

        // load data which must be requested
        $this->getProperties();
        $this->getEditables();

        //Allowed tags for required check
        $allowedTypes = ['input', 'wysiwyg', 'textarea', 'numeric'];

        /** @var EditableUsageResolver $editableUsageResolver */
        $editableUsageResolver = Pimcore::getContainer()->get(EditableUsageResolver::class);

        try {
            $documentCopy = Service::cloneMe($this);
            if ($documentCopy instanceof self) {
                // rendering could fail if the controller/action doesn't exist, in this case we can skip the required check
                $editableNames = $editableUsageResolver->getUsedEditableNames($documentCopy);
                $missingRequiredEditable = false;
                foreach ($editableNames as $editableName) {
                    $editable = $documentCopy->getEditable($editableName);
                    if ($editable instanceof Editable && in_array($editable->getType(), $allowedTypes)) {
                        $editableConfig = $editable->getConfig();
                        if ($editable->isEmpty() && isset($editableConfig['required']) && $editableConfig['required']) {
                            $missingRequiredEditable = true;

                            break;
                        }
                    }
                }
                $this->setMissingRequiredEditable($missingRequiredEditable);
            }
        } catch (Throwable) {
            // noting to do, as rendering the document failed for whatever reason (also a \Error in a template)
        }
    }

    public function getStaticGeneratorEnabled(): ?bool
    {
        return $this->staticGeneratorEnabled;
    }

    public function setStaticGeneratorEnabled(?bool $staticGeneratorEnabled): void
    {
        $this->staticGeneratorEnabled = $staticGeneratorEnabled;
    }

    public function getStaticGeneratorLifetime(): ?int
    {
        return $this->staticGeneratorLifetime;
    }

    public function setStaticGeneratorLifetime(?int $staticGeneratorLifetime): void
    {
        $this->staticGeneratorLifetime = $staticGeneratorLifetime;
    }

    public function __wakeup(): void
    {
        $propertyMappings = [
            'contentMasterDocumentId' => 'contentMainDocumentId',
        ];

        foreach ($propertyMappings as $oldProperty => $newProperty) {
            if ($this->$newProperty === null) {
                $this->$newProperty = $this->$oldProperty;
                $this->$oldProperty = &$this->$newProperty;
            }
        }

        parent::__wakeup();
    }
}
