# models – TODO 2: Bugs

Scope: **(1)** `models/Asset.php` and `models/Asset/**` (Dao, Listing, Service, Folder, Image, Image/Thumbnail/**, Thumbnail/**,
Video, Video/**, Document, Document/**, MetaData/**, WebDAV/**, Enum, Archive/Audio/Text/Unknown), plus the helpers they
directly call where a defect originates there (`lib/Video/Adapter/Ffmpeg.php`, `lib/Messenger/Handler/AssetUpdateTasksHandler.php`)
**(2)** `models/WebsiteSetting.php` and `models/WebsiteSetting/**` (Dao, Listing, Listing/Dao), plus the direct consumer
`lib/Config.php::getWebsiteConfig()` (`:173-262`), the `website_settings` schema (`bundles/InstallBundle/dump/install.sql:603-615`)
and `tests/Model/WebsiteSetting/WebsiteSettingTest.php`
**(3)** `models/Dependency.php` and `models/Dependency/Dao.php`, plus the direct consumers where a defect originates
there (`lib/Messenger/Handler/ElementDependenciesHandler.php`, `models/Element/AbstractElement.php::getDependencies()`,
the dependency helpers in `models/Element/Service.php:163-263`), the `dependencies` schema
(`bundles/InstallBundle/dump/install.sql:63-73`) and `tests/Model/Element/DependenciesTest.php`
**(4)** `models/Translation.php` and `models/Translation/**` (Dao, Listing, Listing/Dao), plus the direct consumers where
a defect originates in the model interplay (`lib/Translation/TranslationEntriesDumper.php`,
`lib/Translation/Translator.php::lazyInitialize()` / `checkForEmptyTranslation()` (`:138-298`),
`models/DataObject/QuantityValue/Service.php::generateDefinitionJson()` (`:66-90`)), the `translations_messages` schema
(`bundles/InstallBundle/dump/install.sql:432-444`) and `tests/Unit/Translation/TranslatorTest.php`
**(5)** `models/Site.php` and `models/Site/**` (Dao, Listing, Listing/Dao), plus the direct consumers where a defect
originates in the model interplay (`lib/Tool/Frontend.php::getSiteMapping()` (`:70-95`),
`bundles/CoreBundle/src/EventListener/Frontend/RoutingListener.php::resolveSite()` (`:101-127`),
`lib/Document/Renderer/DocumentRenderer.php:95-103`, `models/Document.php:690-830` (site path rewriting)), the `sites`
schema (`bundles/InstallBundle/dump/install.sql:370-383`) and `tests/Model/Tool/FrontendTest.php`
**(6)** `models/DataObject.php`, `models/Document.php`, `models/Document/**` (Dao, Service, PageSnippet, Page, Snippet,
Email, Link, Hardlink/**, Folder, DocType/**, Listing/**, Editable/**), `models/Element/**` (AbstractElement, Dao,
Service, Recyclebin/**, Editlock/**, Note/**, Tag/**, WorkflowState/**, PermissionChecker, AdminStyle, DeepCopy/**,
Traits/**, Data/**) and `models/DataObject/**` (AbstractObject/**, Concrete/** incl. InheritanceHelper,
Localizedfield/**, Fieldcollection/**, Objectbrick/**, Classificationstore/**, ClassDefinition/** incl. Data/**,
Data/**, Listing/**, QuantityValue/**, SelectOptions/**, Service), plus the direct consumers where a defect
originates in the model interplay (`bundles/CoreBundle/config/documents.yaml:49-50`,
`lib/Routing/Dynamic/DataObjectRouteHandler.php:91`) and the related schema
(`bundles/InstallBundle/dump/install.sql` – `documents*`, `edit_lock`, `tags`, `recyclebin`)
**(7)** `models/Metadata/**` (Predefined, Predefined/Dao, Predefined/Listing, Predefined/Listing/Dao), `models/Notification.php`
and `models/Notification/**` (Dao, Listing, Listing/Dao, Service/NotificationService, Service/NotificationServiceFilterParser,
Service/UserService), plus the direct consumers where a defect originates in the model interplay
(`lib/Workflow/Notification/PimcoreNotificationService.php`, `lib/Workflow/Manager.php::applyWithAdditionalData()`
(`:222-250`), `models/Asset/Service.php::expandMetadataForEditmode()` (`:291-316`),
`lib/Model/Dao/PimcoreLocationAwareConfigDao.php`, the `assets.metadata.predefined` config node
(`bundles/CoreBundle/src/DependencyInjection/Configuration.php:667-695`)), the `notifications` schema
(`bundles/InstallBundle/dump/install.sql:801-817`, migration `Version20240813085200`) and
`tests/Unit/Notification/Service/NotificationServiceTest.php`
**(8)** `models/Property.php`, `models/Property/**` (Dao, Predefined, Predefined/Dao, Predefined/Listing,
Predefined/Listing/Dao) and `models/Schedule/**` (Task, Task/Dao, Task/Listing, Task/Listing/Dao), plus the direct
consumers where a defect originates in the model interplay (`lib/Maintenance/Tasks/ScheduledTasksTask.php`,
`lib/Maintenance/Executor.php::executeTask()` (`:48-70`), `models/Element/Traits/ScheduledTasksTrait.php`,
`models/Element/Traits/ScheduledTasksDaoTrait.php`, the `getProperties()` hydration in `Asset/Dao.php`,
`DataObject/AbstractObject/Dao.php` and `Document/Dao.php`, `models/Element/Service.php::minimizePropertiesForEditmode()`
(`:516-560`), `models/Element/Recyclebin/Item.php`, the `properties.predefined` config node
(`bundles/CoreBundle/src/DependencyInjection/Configuration.php:1844-1873`)), the `properties` and `schedule_tasks`
schemas (`bundles/InstallBundle/dump/install.sql:300-310`, `:326-340`) and `tests/Support/Test/AbstractPropertiesTest.php`
**(9)** `models/Version.php` and `models/Version/**` (Dao, Listing, Listing/Dao, SetDumpStateFilter,
Adapter/VersionStorageAdapterInterface, Adapter/FileSystemVersionStorageAdapter, Adapter/DatabaseVersionStorageAdapter,
Adapter/DelegateVersionStorageAdapter, Adapter/ProxyVersionStorageAdapter), plus the direct consumers where a defect
originates in the model interplay (`lib/Maintenance/Tasks/VersionsCleanupTask.php`, `lib/Messenger/Handler/VersionDeleteHandler.php`,
`lib/Messenger/Handler/SanityCheckHandler.php::performSanityCheck()` (`:69-95`),
`models/Element/AbstractElement.php::doSaveVersion()` (`:573-611`), `models/Asset.php::saveVersion()` (`:839-890`), the
`Version::disable()` / `enable()` callers, `lib/Helper/LongRunningHelper.php::cleanUp()`), the `versions` schema
(`bundles/InstallBundle/dump/install.sql:576-600`), the `versionsData` table from
`doc/18_Tools_and_Features/01_Versioning.md:79-87` and `tests/Service/Element/VersionTest.php`
**(10)** `models/User.php`, `models/UserInterface.php` and `models/User/**` (AbstractUser, AbstractUser/Dao,
AbstractUserInterface, UserRole, UserRole/Dao, UserRole/Folder, UserRoleInterface, Dao, Folder, Role, Role/Dao,
Role/Folder/**, Role/Listing/**, Listing/**, Service, Permission/Definition/**, Workspace/**), plus the direct consumers
where a defect originates in the model interplay (`lib/Security/User/User.php`, `lib/Security/User/UserProvider.php`,
`lib/Security/User/UserChecker.php`, `lib/Tool/Authentication.php`, `models/Element/PermissionChecker.php:196-210`,
`models/Notification/Service/NotificationService.php:98-102`), the `users`, `users_permission_definitions` and
`users_workspaces_*` schemas (`bundles/InstallBundle/dump/install.sql:457-572`) and `config/dao-classmap.php:155-168`
**(11)** `models/Tool/**` (SettingsStore, SettingsStore/Dao, TmpStore, TmpStore/Dao, Email/Log, Email/Log/Dao,
Email/Log/Listing/**, Email/Blocklist, Email/Blocklist/Dao, Email/Blocklist/Listing/**), plus the direct consumers where
a defect originates in the model interplay (`lib/Mail.php::sendWithoutRendering()` / `filterLogAddresses()` /
`getDebugMailRecipients()` (`:462-588`), `lib/Helper/Mail.php::logEmail()` (`:146-197`),
`lib/Maintenance/Tasks/TmpStoreCleanupTask.php`, `lib/Maintenance/Executor.php:114-128`,
`lib/Config/LocationAwareConfigRepository.php:180-190`, `lib/Extension/Bundle/Installer/SettingsStoreAwareInstaller.php:95-135`,
`bundles/CoreBundle/src/Command/EmailLogsCleanupCommand.php:60-67`), the `email_blocklist`, `email_log`, `tmp_store` and
`settings_store` schemas (`bundles/InstallBundle/dump/install.sql:202-232`, `:409-430`) and
`tests/Model/Tool/SettingsStoreTest.php`
**(12)** the `models/DataObject/*.php` root files not processed in Scope 6 (`Classificationstore.php`,
`DefinitionModifier.php`, `Folder.php`, `Listing.php`, `ClassDefinitionInterface.php`, `ImportDataServiceInterface.php`,
`LazyLoadedFieldsInterface.php`, `ObjectAwareFieldInterface.php`, `OwnerAwareFieldInterface.php`,
`PreGetValueHookInterface.php`, `SelectOptionsInterface.php`), plus the direct consumers where a defect originates in the
model interplay (`models/DataObject/Classificationstore/Dao.php`, `ClassDefinition/Data/Classificationstore.php::getDataFromEditmode()`
(`:300-350`), `ClassDefinition/Data/Password.php`, `lib/Model/Listing/Dao/QueryBuilderHelperTrait.php:52-81`) and
`tests/Model/DataObject/DefinitionModifierTest.php`
**(13)** `models/DataObject/Folder/Dao.php` (the only file in `models/DataObject/AbstractObject/**`, `Concrete/**` and
`Folder/**` not already processed in Scope 6), with `config/dao-classmap.php:57`
**(14)** the not yet processed files of `models/DataObject/Listing/**`, `Fieldcollection/**` and `Localizedfield/**`
(`Listing/Dao.php`, `Fieldcollection/Definition.php`, `Fieldcollection/Definition/Dao.php`,
`Fieldcollection/Definition/Listing.php`, `Fieldcollection/Data/AbstractData.php`, `Fieldcollection/Data/Dao.php`,
`Localizedfield/LanguageTableDoesNotExistException.php`), plus the direct consumers where a defect originates in the
model interplay (`lib/DataObject/ClassBuilder/FieldCollectionClassBuilder.php:67-71`,
`Localizedfield/Dao.php::createUpdateTable()` (`:796-850`), `Fieldcollection/Dao.php::load()` (`:44-63`),
`bundles/CoreBundle/src/Command/ClassesRebuildCommand.php:155-164`)
**(15)** the not yet processed files of `models/DataObject/Classificationstore/**` (`KeyConfig.php`, `KeyConfig/Dao.php`,
`KeyConfig/Listing/**`, `GroupConfig.php`, `GroupConfig/Dao.php`, `GroupConfig/Listing/**`, `CollectionConfig.php`,
`CollectionConfig/Dao.php`, `CollectionConfig/Listing/**`, `StoreConfig.php`, `StoreConfig/Dao.php`, `StoreConfig/Listing/**`,
`KeyGroupRelation.php`, `KeyGroupRelation/Dao.php`, `KeyGroupRelation/Listing/**`, `CollectionGroupRelation.php`,
`CollectionGroupRelation/Dao.php`, `CollectionGroupRelation/Listing/**`, `Group.php`, `Key.php`), plus the direct consumers
where a defect originates in the model interplay (`ClassDefinition/Data/Classificationstore.php::denormalize()` (`:1066-1090`),
`lib/Cache/RuntimeCache.php`, `lib/Model/AbstractModel.php::setValue()` (`:175-190`),
`bundles/CoreBundle/src/Command/DeleteClassificationStoreCommand.php`) and the `classificationstore_*` schema
(`bundles/InstallBundle/dump/install.sql:617-694`)
**(16)** the not yet processed files of `models/DataObject/ClassDefinition/**` (CustomLayout/**, Service, Helper/**,
Layout/**, DynamicOptionsProvider/**, Data/**; details in the Scope 16 notes of TODO-1)
**(17)** the not yet processed files of `models/DataObject/Data/**` (field value objects: quantity / range values,
`BlockElement`, `CalculatedValue`, `Consent`, `Link`, `Hotspotimage`, `ImageGallery`, `Video`, `ExternalImage`, geo
values, `RgbaColor`, `StructuredTable`, `ElementMetadata` / `ObjectMetadata` and the three metadata DAOs; `UrlSlug.php`
and `EncryptedField.php` were processed in Scope 6), plus the direct consumers where a defect originates in the model
interplay (advanced relation metadata handling in `ClassDefinition/Data/AdvancedManyToMany(Object)Relation.php`,
`Concrete::getValueForFieldName()`, `lib/Cache/Core/CoreCacheHandler.php` marshalling, `Asset/Image/Thumbnail.php`
config resolution)
**(18)** `models/DataObject/QuantityValue/**` (Unit, Unit/Dao, Unit/Listing, Unit/Listing/Dao, Service, DefaultConverter,
UnitConversionService, QuantityValueConverterInterface; listed under Scope 6 but not processed there), plus the direct
consumers where a defect originates in the model interplay (`Data/AbstractQuantityValue.php::convertTo()`,
`ClassDefinition/Data/AbstractQuantityValue.php::getFilterCondition()`, `ClassDefinition/Helper/UnitConverterResolver.php`,
`bundles/CoreBundle/src/Command/Definition/Import/QuantityValueCommand.php`), the `quantityvalue_units` schema
(`bundles/InstallBundle/dump/install.sql:695-709`) and `doc/05_Objects/01_Object_Classes/01_Data_Types/55_Number_Types.md:86-147`
**(19)** `models/DataObject/Objectbrick/**` (Definition, Dao, Definition/Dao, Definition/Listing, Data/AbstractData,
Data/Dao) and `models/DataObject/SelectOptions/**` (Config, Config/Dao, Config/Listing, Config/Listing/Dao,
Data/SelectOption, Traits/EnumGetValuesTrait, Traits/EnumTryFromNullableTrait; both listed under Scope 6 but not processed
there), plus the direct consumers where a defect originates in the model interplay (`models/DataObject/Objectbrick.php`,
`ClassDefinition/Data/Objectbricks.php`, the advanced relations' `delete()`, `Concrete/Dao/InheritanceHelper.php`,
`lib/DataObject/ClassBuilder/ObjectBrick*` / `SelectOptionsEnumBuilder.php`,
`bundles/CoreBundle/src/OptionsProvider/SelectOptionsOptionsProvider.php`, `ClassesRebuildCommand.php`)
**(20)** the `models/Document/*.php` root files not processed in Scope 6 (`Email.php`, `Folder.php`, `Snippet.php`;
listed under Scope 6 but not processed there), plus the direct consumers where a defect originates in the model
interplay (`lib/Mail.php::setDocumentSettings()` / `setDocument()` (`:379-415`, `:729-747`),
`lib/Helper/Mail.php::parseEmailAddressField()` (`:371-395`), `models/Document.php::correctPath()` (`:415-463`), the
`documents.type_definitions` config (`bundles/CoreBundle/config/pimcore/default.yaml:285-318`)) and the
`documents_email` schema (`bundles/InstallBundle/dump/install.sql:109-122`)
**(21)** the `models/Document/*/` sub-directory classes (`Email/Dao.php`, `Folder/Dao.php`, `Link/Dao.php`,
`Listing/Dao.php`, `Page/Dao.php`, `PageSnippet/Dao.php`, `Service/Dao.php`, `Snippet/Dao.php`; every one is already
named by an earlier item, so this scope is a gap-fill that adds only points not yet recorded), plus the direct consumers
where a defect originates in the model interplay (`models/Document/Service.php::copyAsChild()` (`:196-206`),
`models/Document.php::doDelete()` / `getById()` (`:621-646`, `:187-258`), `lib/Routing/Dynamic/DocumentRouteHandler.php`
(`:120-133`, `:260-295`), `bundles/XliffBundle/src/ExportDataExtractorService/DataExtractor/DocumentDataExtractor.php`
(`:81`, `:133`)) and the `documents_translations` schema (`bundles/InstallBundle/dump/install.sql:175-184`)
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md),
[CoreBundle TODO-2](../bundles/CoreBundle/TODO-2-BUGS.md) (B-58 lock backends, B-65/B-69 thumbnail route – not repeated;
B-43 push/pop listener balance is the listener side of B-158)

Functional defects: wrong results, crashes on reachable input, swallowed errors, misleading output.
IDs are stable – refer to them as `B-xx` in later sessions. Line numbers are from commit `1b0711d927` (Scope 2:
`7f0331cab9`, Scope 3: `2d382456c7`, Scope 4: `3e2bf1c4d8`, Scope 5: `0b659e9ce0`, Scope 6: `990568d287`,
Scope 7: `7d51be3297`, Scope 8: `d527536468`, Scope 9: `718bf5020c`,
Scope 10: `70fc218e66`, Scope 11: `a5cc6dc56a`, Scope 12: `d78291fed7`, Scope 13: `1650c79550`, Scope 14: `6bde85085f`,
Scope 15: `205ed92381`, Scope 16: `765372d338`, Scope 17: `d5ca47d13d`,
Scope 18: `bcac4499f6`, Scope 19: `87799e51e6`, Scope 20: `47897412ea`, Scope 21: `86f089cf1a`).
Grouped by area, roughly ordered by impact inside each group: **Asset.php** B-01–B-19, **Dao / Listing / Service /
Folder / WebDAV** B-20–B-36, **Image thumbnails** B-37–B-60, **Video / Document / MetaData** B-61–B-82,
**WebsiteSetting** B-83–B-91, **Dependency** B-92–B-106, **Translation** B-107–B-125, **Site** B-126–B-137,
**Document** B-138–B-155, **Document editables** B-156–B-164, **Element** B-165–B-174, **DataObject** B-175–B-187,
**Metadata** B-188–B-192, **Notification** B-193–B-200, **Property** B-201–B-205, **Predefined property**
B-206–B-209, **Schedule** B-210–B-214, **Version** B-215–B-221, **Version storage / maintenance** B-222–B-225,
**User** B-226–B-237, **Tool** B-238–B-244, **DataObject root files** B-245–B-251,
**Field collection / listing** B-252–B-256,
**Classification store configuration** B-257–B-265, **ClassDefinition data types** B-266–B-299,
**DataObject `Data/**` value objects** B-300–B-315,
**QuantityValue units / conversion** B-316–B-323,
**Object bricks** B-324–B-330, **Select options** B-331–B-333,
**Document `Email` / `Snippet` / `Folder`** B-334–B-337,
**Document translation links** B-338–B-340.

---

## Asset.php

## [ ] B-01 – Update-task lock is released immediately: every save enqueues another `AssetUpdateTasksMessage`

**File:** `models/Asset.php:1828-1840`; `lib/Messenger/Handler/AssetUpdateTasksHandler.php:63`

- `createLock()` defaults to `autoRelease: true`; `Lock::__destruct()` releases it when `$lock` goes out of scope at
  the end of `triggerUpdateTask()`. The lock deduplicates nothing; bulk imports enqueue one message per save.
- The handler's `createLock(...)->release()` uses a new key token, so it is a no-op on token-checked stores (see also
  CoreBundle B-58: two lock backends).

**Fix direction:** `createLock($id, $ttl, autoRelease: false)`; pass the serialized `Key` in the message, or let the TTL
expire instead of releasing from a different key.

## [ ] B-02 – `relocateThumbnails()` fallback works on the asset path instead of the thumbnail directory

**File:** `models/Asset.php:1788`

- For non-folder assets thumbnails live in `dirname($oldPath) . '/' . $id` (`:1763-1764`), but the fallback calls
  `updateChildPaths($storage, $oldPath, null, true)` with the asset file path. Nothing is found; thumbnails are orphaned
  and never cleaned up.

**Fix direction:** pass `$oldThumbnailsPath` / `$newThumbnailsPath`.

## [ ] B-03 – `updateChildPaths()` never deletes the old directory when it has subdirectories

**File:** `models/Asset.php:1710`, `:1723-1726`, `:1745-1746`

- `$totalChildren` counts `DirectoryAttributes` too, `$movedCount` only files → any subfolder makes them differ;
  `deleteDirectory($oldPath)` is skipped and empty trees remain, with a misleading log message. The rollback loop swaps
  `$src`/`$dest` naming relative to `:1719` (correct, but confusing). Fix together with C-02.

**Fix direction:** count only `FileAttributes`.

## [ ] B-04 – `PRE_GET_METADATA` listener result is written into the model and then persisted

**File:** `models/Asset.php:1489-1492`; `models/Asset/Dao.php:138`

- `$this->metadata = $preEvent->getArgument('metadata');` overwrites the model state. `Dao::update()` reads metadata
  through `getMetadata(null, null, false, true)`, which fires the event, so display-only metadata added by a listener is
  saved to `assets_metadata` and compounds on repeated calls.

**Fix direction:** apply the event result only to the returned value; let the Dao read raw `$this->metadata`.

## [ ] B-05 – `getMetadataByName()` returns the first match, not the localized one

**File:** `models/Asset.php:1563-1571`

- The loop breaks on the first entry whose language matches **or is empty**. With `[title(''), title('en')]`,
  `getMetadata('title', 'en')` returns the language-neutral value.

**Fix direction:** exact language match first, empty-language entry as fallback.

## [ ] B-06 – Metadata names containing `~` cannot be read back

**File:** `models/Asset.php:1430`, `:1471` vs `:1564`

- `addMetadata()` / `removeMetadata()` map `~` to `---`; `getMetadataByName()` compares the raw name, so
  `addMetadata('a~b', …)` → `getMetadata('a~b')` is `null`.

**Fix direction:** normalise the name in one helper used by all three methods.

## [ ] B-07 – `__sleep()` condition is inverted: every cached asset re-queries `customSettings`

**File:** `models/Asset.php:193-201`

- `parent::__sleep()` returns the vars that **will** be serialized (`AbstractElement.php:649`), not the blocked ones.
  `customSettingsNeedRefresh = true` is therefore set exactly when `customSettings` is cached, so every cache hit runs an
  extra `SELECT customSettings`. The blocked case is already handled in `__wakeup()` (`:1628`).

**Fix direction:** remove the override (or invert to `!in_array(...)`).

## [ ] B-08 – `CUSTOM_SETTING_PROCESSING_FAILED` is removed only in memory, after the DB write

**File:** `models/Asset.php:613-617`

- `removeCustomSetting()` runs after `Dao::update()` and `commit()`; the DB keeps the flag. Other processes
  (`Video::getDuration()` `Video.php:197/222`, `AddToUpdateTaskQueueCommand`) see a stale failure flag.

**Fix direction:** remove the flag before `update()`.

## [ ] B-09 – `getChecksum()` writes the whole in-memory `customSettings` to DB on a read

**File:** `models/Asset.php:1163-1189`

- `generateChecksum()` calls `Dao::updateCustomSettings()`, persisting unsaved caller changes; inside `update()` it also
  double-writes `customSettings`.

**Fix direction:** separate computing from persisting; inside `update()` only set the value in memory.

## [ ] B-10 – `delete()`: file-system errors after `commit()` roll back nothing and fire `POST_DELETE_FAILURE`

**File:** `models/Asset.php:1024-1053`

- Physical delete and `clearThumbnails()` run after `commit()` inside the same `try`. A `FilesystemException` calls
  `rollBack()` with no active transaction, dispatches the failure event and rethrows although the DB rows are gone.

**Fix direction:** move post-commit storage cleanup out of the transactional `try`; log its errors.

## [ ] B-11 – `Asset::create()` leaks its temp file when `save()` throws

**File:** `models/Asset.php:405-411`

- `unlink($tmpFile)` is skipped on exception; files accumulate in `PIMCORE_SYSTEM_TEMP_DIRECTORY`.

**Fix direction:** `try/finally` (together with C-03 / C-04).

## [ ] B-12 – `Asset::create()` passes `null`/`false` into typed functions

**File:** `models/Asset.php:358`, `:370`, `:375-377`, `:382`

- `guessMimeType()` returns `?string`; `null` reaches `getTypeFromMimeMapping(string …)` → `TypeError`.
- Unreadable `sourcePath`: `fopen()` → `false`; `setStream(false)` silently ignores it and the asset is saved without a
  binary.
- `fopen($tmpFile)` failing at `:358` → `stream_copy_to_stream(…, false)` `TypeError`.

**Fix direction:** validate each result and throw a meaningful exception.

## [ ] B-13 – Legacy-file deletion passes `null` from a failed DB lookup to Flysystem

**File:** `models/Asset.php:757-759`; `models/Asset/Dao.php:299-310`

- `getCurrentFullPath()` returns `null` on DB error (and `''` for a missing row, B-33). `fileExists(null)` throws
  `TypeError` inside the transaction (C-05).

**Fix direction:** `if ($dbPath && $dbPath !== $path && …)`.

## [ ] B-14 – `ValidationException` message embeds the raw upload filename in HTML

**File:** `models/Asset.php:435-437`

- `{$data['filename']}` is inserted into `<em>…</em>`. If the admin UI renders the message as HTML (it renders the other
  tags), a filename like `<img src=x onerror=…>.jpg` is reflected XSS. (Rendering side not verified – admin UI is
  outside this repo.)

**Fix direction:** `htmlspecialchars($data['filename'])`.

## [ ] B-15 – Storage writes / moves are not compensated when the transaction fails

**File:** `models/Asset.php:533-567`, TODO comment at `:634`

- Binary write (`update()`), thumbnail relocation and `storage->move()` run before `saveVersion()` / `commit()`. If a
  later step fails, the DB rolls back but storage keeps the new location/content; the DB path points to a missing file.

**Fix direction:** record storage operations and undo them in the catch, or perform moves after commit with
compensating handling.

## [ ] B-16 – `getStream()` silently substitutes an empty stream for a missing binary

**File:** `models/Asset.php:1142-1158`

- `catch (Exception) { $this->stream = tmpfile(); }` hides a missing/unreadable file. With `setDataChanged(true)` and no
  new stream, `update()` rewrites the asset as a 0-byte file. When rewind fails (`:1147`) the old handle is dropped
  without `fclose()`.

**Fix direction:** log (or rethrow outside read-only contexts) and close the old handle.

## [ ] B-17 – Version deletion dispatched inside the delete transaction

**File:** `models/Asset.php:1007-1009`

- `VersionDeleteMessage` is dispatched before `commit()`. With the default `doctrine://` transport it rolls back with the
  transaction; with AMQP/Redis/sync transports versions are deleted even when the asset delete rolls back. (Transport
  dependency unverified per project.)

**Fix direction:** dispatch after `commit()`.

## [ ] B-18 – `setParent()` keeps a stale `parentId` for `null` / non-asset parents

**File:** `models/Asset.php:1602-1611`

- Accepts any `ElementInterface`; passing `null` or a document leaves the old `parentId` while `$this->parent` changes,
  so `save()` uses the stale id.

**Fix direction:** narrow to `?Asset` and set `parentId` consistently.

## [ ] B-19 – `delete()` traversal guard is ineffective

**File:** `models/Asset.php:1029-1030`

- `!strpos($fullPath, '/../')` treats a match at position 0 as "not found". Keys are validated elsewhere, so impact is
  low, but the guard does not do what it claims.

**Fix direction:** `!str_contains(...)`, or remove the check and rely on key validation + Flysystem path normalisation.

---

## Dao / Listing / Service / Folder / WebDAV

## [ ] B-20 – WebDAV `MOVE` to another folder ignores the new filename

**File:** `models/Asset/WebDAV/Tree.php:73-79`

- The cross-directory branch only calls `setPath()` / `setParentId()`, never `setFilename(basename($destinationPath))`.
  `MOVE /a/x.jpg` → `Destination: /b/y.jpg` yields `/b/x.jpg`; a duplicate is swallowed (C-08). The override also skips
  Sabre's `markDirty()`, so the node cache is stale for the rest of the request.

**Fix direction:** set the filename in both branches; call `markDirty()` on source and destination directories.

## [ ] B-21 – `Service::getUniqueKey()` throws `TypeError` for names without an extension and mangles repeated stems

**File:** `models/Asset/Service.php:331-333` (`strict_types`)

```php
$keypart = substr($key, 0, strrpos($key, '.'));   // strrpos() === false → TypeError
$extension = str_replace($keypart, '', $key);     // 'data.data' → stem 'data', ext '.'
```

- Any name collision of `README`, `LICENSE`, … crashes; `data.data` becomes `data_1.`. Public API via
  `Element\Service::getUniqueKey()`.

**Fix direction:** `pathinfo()` with `PATHINFO_FILENAME` / `PATHINFO_EXTENSION`; append `.ext` only when present.

## [ ] B-22 – Public thumbnail route: type/asset mismatch → uncaught `TypeError` (anonymous 500)

**File:** `models/Asset/Service.php:373-374`, `:420`, `:431`, `:468`

- `$config['type']` comes from the URL and is never checked against the asset class.
  `/…/video-thumb__<imageId>__<videoThumbName>/x.jpg` makes `Image::getThumbnail(Video\Thumbnail\Config)` throw; a
  Document with type `video` fails at `:431`. The controller's `catch (Exception)` (CoreBundle B-65) does not catch it.

**Fix direction:** return `null` when `type === 'video'` and the asset is not `Asset\Video` (and vice versa).

## [ ] B-23 – Undefined `status_cache` key for video thumbnails

**File:** `models/Asset/Service.php:390`

- `…['assets'][$config['type']]['thumbnails']['status_cache']` exists only for images (`Configuration.php:510`). Every
  anonymous request for an unknown video thumbnail name raises "Undefined array key" (`ErrorException` in debug).

**Fix direction:** `?? false`, or only for `type === 'image'`.

## [ ] B-24 – Unknown thumbnail name → DB `DELETE` and status-cache wipe on every anonymous request

**File:** `models/Asset/Service.php:390-395`; `models/Asset/Dao.php:588`

- With no TmpStore item, each request runs `DELETE FROM assets_image_thumbnail_cache …` and drops the whole static
  status cache for that asset. Unauthenticated DB writes per request (separate cost from CoreBundle C-15).

**Fix direction:** delete only when a cache row exists (`getCachedThumbnail()`), or only for `auto_*` names.

## [ ] B-25 – Video branch of `getStreamedResponseFromImageThumbnail()`: undefined format key and metadata on missing file

**File:** `models/Asset/Service.php:511-517`

- `$thumbnail['formats'][$ext]` is undefined for a non-generated extension or while converting → `urldecode(null)`
  `TypeError`. `mimeType()` / `fileSize()` are called even when `fileExists()` was false → `UnableToRetrieveMetadata`.
- (unverified) `Video::enrichThumbnailPath()` adds the frontend prefix, so with a configured prefix `fileExists()` is
  always false.

**Fix direction:** return `null` on missing key/file; read metadata only for existing files; strip the prefix as at `:569`.

## [ ] B-26 – Thumbnail responses: `Expires` in local time, cache headers differ between paths, streams not closed

**File:** `models/Asset/Service.php:527`, `:532`, `:535`, `:578-585`

- `date('D, d M Y H:i:s T')` → e.g. `CEST`; RFC 9110 requires GMT.
- `NO_AUTO_CACHE_CONTROL_HEADER` is set only on the "generated" path; for an existing thumbnail Symfony's
  `AbstractSessionListener` rewrites the response to `private, no-cache` whenever a session is active.
- The `StreamedResponse` closures never `fclose()` the stream; `:504-508` leaves the original stream open; `:540`
  returns `$thumbnailStream` where `null` is meant.

**Fix direction:** one helper: `setPublic()->setMaxAge()->setExpires(new \DateTimeImmutable("+$lifetime seconds"))`,
header on both paths, `fclose` in the closures.

## [ ] B-27 – `Asset\Dao::$thumbnailStatusCache` is a never-reset static (unbounded growth, stale in workers)

**File:** `models/Asset/Dao.php:43`, `:549-570`; only reset in `Image/Thumbnail/Config/Dao.php:157`

- No `ResetInterface` / `kernel.reset`. In messenger consumers / FrankenPHP / RoadRunner it grows by every asset touched
  and goes stale: after `pimcore:thumbnails:clear` or a config edit in another process, `Processor.php:181-194` returns
  a path that no longer exists – image broken and never regenerated.

**Fix direction:** move it into a service implementing `ResetInterface`; reset in the long-running helper.

## [ ] B-28 – Listing permission SQL uses unanchored prefix matching (`/foo` matches `/foobar`)

**Files:** `models/Asset/Listing.php:71-85`, `models/Asset/Dao.php:337-351`, `:393-407`; root `models/Element/Dao.php:88`

- `LOCATE(CONCAT(path,filename), cpath)=1`: a user with a workspace on `/foobar` sees folder `/foo` listed, and
  `hasChildren()` / `getChildAmount()` report its children.
- `InheritingPermission()` uses `LOCATE(cpath, ?)=1` while workspace `cpath` is stored without trailing slash, so a
  workspace on `/foo` grants inherited `list` on children of `/foobar`.
- `isAllowed()` (`Dao.php:467`) is correct, so only listings leak (e.g. via WebDAV `getChildren()` after CoreBundle C-13).

**Fix direction:** compare with `'/'` appended on both sides, or use `cid IN (parentIds)`.

## [ ] B-29 – WebDAV upload writes the body to disk before the permission check and leaks temp files on errors

**Files:** `models/Asset/WebDAV/Folder.php:112-133`; `models/Asset/WebDAV/File.php:110-123`

- `createFile()` writes the whole body to `PIMCORE_SYSTEM_TEMP_DIRECTORY` **before** `isAllowed('create')` (`:120`).
- If `Asset::create()` / `save()` throws, `unlink()` is skipped; the files are not registered for shutdown cleanup. In
  `File::put()` the `fopen()` handle stays open. `file_put_contents()` / `fopen()` results are unchecked.

**Fix direction:** check permission first; `try/finally` with `fclose`/`unlink`, or pass a `php://temp` stream
directly to `setStream()`.

## [ ] B-30 – WebDAV delete log: lost entries, cross-user restore, unrestricted `unserialize()`

**Files:** `models/Asset/WebDAV/Service.php:36`, `:54`; `File.php:80-91`; `Tree.php:59-66`
(CoreBundle C-13 only mentions the inner `Serialize::unserialize`.)

- Read-modify-write of one shared file without lock: concurrent DELETEs lose entries; the editor "delete then move"
  restore then creates a new asset ID and references break.
- The log is global: user B's `MOVE` onto a path user A deleted within 30 s restores **A's** serialized asset.
- Outer `unserialize()` at `:36` has no `allowed_classes` and no error handling.

**Fix direction:** TmpStore / DB keyed by user + path; `allowed_classes` restricted; at minimum `LOCK_EX` / `flock`.

## [ ] B-31 – Folder preview collage caches partial images and fatals on corrupt tiles

**File:** `models/Asset/Folder.php:353-366`, `:375-390`

- One tile without stream/dimensions hits `break` (`:356`, `:362`), aborting the loop; the partial (black) collage is
  stored and served until a descendant changes.
- `imagecreatefromstring()` → `false` for a corrupt thumbnail; `imagecopyresampled($collage, false, …)` → `TypeError`,
  breaking the preview permanently. Tile streams (`:353`) are never closed.

**Fix direction:** `continue` instead of `break`; check `false`; size the canvas by tiles actually drawn.

## [ ] B-32 – Folder preview freshness check does not match the collage contents

**File:** `models/Asset/Folder.php:303` vs `:309-313`

- `SELECT MAX(modificationDate) … ORDER BY filename LIMIT 42` limits the single aggregate row, i.e. MAX over **all**
  descendants, while the collage uses the first 42 by `id`. Any change anywhere in a deep tree regenerates the collage.

**Fix direction:** `SELECT MAX(modificationDate) FROM (SELECT … ORDER BY id LIMIT 42) t`.

## [ ] B-33 – `Dao::getCurrentFullPath()` / `getCustomSettings()` return `''` instead of `null` for a missing row

**File:** `models/Asset/Dao.php:299-310`, `:509-512`

- `fetchOne()` → `false`; without `strict_types` it is coerced to `''` for `?string`. `Asset.php:682` then builds
  `'' . '/'` for a concurrently deleted parent.

**Fix direction:** `return $v === false ? null : $v;` and add `strict_types`.

## [ ] B-34 – `__isBasedOnLatestData()` reads offsets on `false`

**File:** `models/Asset/Dao.php:516-517`

- For a deleted/unsaved asset `fetchAssociative()` is `false` → warning (`ErrorException` in debug).

**Fix direction:** `if (!$data) { return false; }`.

## [ ] B-35 – WebDAV `getContentType()` / `getLastModified()` are non-nullable, model getters are nullable

**File:** `models/Asset/WebDAV/File.php:97-100`, `:158-161`

- `getMimeType(): ?string`, `getModificationDate(): ?int`; returning `null` → `TypeError` that breaks PROPFIND for the
  whole folder (assets created via API without data).

**Fix direction:** return `?string` (Sabre allows null) / fall back to `0`.

## [ ] B-36 – `Dao::unlockPropagate()` builds invalid SQL for unsaved assets

**File:** `models/Asset/Dao.php:433-434`

- With `getId() === null` the SQL ends in `OR id = ` (syntax error); large trees build huge `IN()` lists.

**Fix direction:** guard on null id; single `DELETE tl FROM tree_locks tl JOIN assets a …`.

---

## Image thumbnails (Image, Image/Thumbnail/**, Thumbnail/**)

## [ ] B-37 – Uppercase file extensions break the `source` format (JPEG bytes in `.png`, or full PNG of a photo)

**File:** `models/Asset/Image/Thumbnail/Processor.php:114`, `:119`, `:129`, `:338`; `models/Asset/Service.php:399-405`

- `pathinfo()` without lowercasing (regression from commit `85d6471de3`, which replaced `File::getFileExtension()`).
  `IMG_1234.JPG` misses the mapping and falls back to `png`. Eager path writes optimised `pjpeg` content into `….png`
  (wrong MIME); deferred path writes an unoptimised full PNG to the **same** path (hash ignores format, B-38). First
  writer wins. Same for `.TIF` (the `tifforiginal` bypass never triggers).

**Fix direction:** `strtolower()` the extension; include the resolved format in filename/hash.

## [ ] B-38 – `Config::getHash()` omits output-affecting fields

**File:** `models/Asset/Image/Thumbnail/Config.php:836-846`

- Not hashed: `format`, `forceProcessICCProfiles`, `rasterizeSVG`, `medias`. Different outputs collide on one path;
  changing `forceProcessICCProfiles` keeps serving stale files unless `auto_clear_temp_files` runs.
- Related: `Service.php:444-446` overrides `quality` for non-jpg formats, changing the hash so the file is written to a
  different path than the requested URL – every request for that URL goes through PHP.

**Fix direction:** hash every output-affecting property.

## [ ] B-39 – Runtime-modified named configs are not stored for deferred generation

**File:** `models/Asset/Image/Thumbnail/Processor.php:224`; `models/Asset/Thumbnail/ImageThumbnailTrait.php:423-434`

- `if (!Config::exists($config->getName())) TmpStore::add(...)`: a cloned & modified named config (`getAsFormat('webp')`,
  user `setItems()`) is not stored; the deferred controller rebuilds it from YAML and writes `…jpg` to a different
  path, so the `.webp` URL never resolves.

**Fix direction:** store when the config differs from the persisted definition (compare hashes), not by name.

## [ ] B-40 – `getAsFormat()` returns a clone with a stale MIME type

**File:** `models/Asset/Thumbnail/ImageThumbnailTrait.php:122-129`, `:425-431`

- `reset()` does not clear `$mimetype`: `$t->getMimeType(); $t->getAsFormat('webp')->getMimeType()` → `image/jpeg`.

**Fix direction:** `$this->mimetype = null` in `reset()`.

## [ ] B-41 – `existsOnStorage()` without argument always returns `false`

**File:** `models/Asset/Thumbnail/ImageThumbnailTrait.php:371-373`

- Default is `[]`, so `$pathReference ??= …` never fires.

**Fix direction:** default to `null` (update `ImageThumbnailInterface` too).

## [ ] B-42 – Empty / `'0'` thumbnail name → `TypeError` instead of fallback

**File:** `models/Asset/Image/Thumbnail.php:430-438`

- `getByName('')` → `null`; `empty('')` skips the `NotFoundException`; `return null` violates the return type. Twig
  `image.thumbnail(emptyField)` → 500.

**Fix direction:** treat `''` like `null`, or throw `NotFoundException` for any null result.

## [ ] B-43 – `Thumbnail::generate()` catches only `Exception`

**File:** `models/Asset/Image/Thumbnail.php:129-134`; `Processor.php:343-344`

- `TypeError` / `DivisionByZeroError` crash page rendering, e.g. legacy `['cover' => true, 'width' => 300]`
  (`Config.php:518-524` reads missing `height` → `null` → `Adapter::cover(int …)`), or `fopen()` → `false` after a
  failed save → `writeStream(false)`.

**Fix direction:** catch `\Throwable`; check `fopen` / save results in the Processor.

## [ ] B-44 – `DivisionByZeroError` in `getEstimatedDimensions()` during HTML rendering

**File:** `models/Asset/Image/Thumbnail/Config.php:619`, `:624`, `:628-629`

- `getHtml()` → … → `getEstimatedDimensions()`; legacy `['contain' => true, 'width' => 300]` → `$h / null`.

**Fix direction:** guard missing/zero arguments (skip the step or return `[]`).

## [ ] B-45 – Hi-res width/height attributes wrong when the source is smaller than requested

**File:** `models/Asset/Thumbnail/ImageThumbnailTrait.php:239-245`; `Processor.php:438-454`; `Config.php:669-670`

- Processor lowers the factor (500 px source, `scaleByWidth 400 @2` → effective 1.25), trait divides by the configured 2
  → reports 250 instead of 400; attributes change after generation. At @1.5, cast-before-multiply gives off-by-one.

**Fix direction:** cache the effective factor or derive CSS size from the estimate; round after multiplying.

## [ ] B-46 – Hi-res `goto` restart applies the factor inconsistently across steps

**File:** `models/Asset/Image/Thumbnail/Processor.php:417-454`

- When a later step lowers `$highResFactor`, already-applied steps keep the old factor (`resize` at 2×, `crop` at 1.67×
  → misaligned crop). The size check compares against the original source, not the current image.

**Fix direction:** compute the effective factor in a first pass, apply in a second.

## [ ] B-47 – Unknown transformation method → warning / `TypeError`

**File:** `models/Asset/Image/Thumbnail/Processor.php:423`, `:427`

- `self::$argumentMapping[$method]` undefined (e.g. `tifforiginal` on a non-TIFF) → warning; with arguments
  `array_search($key, null)` → `TypeError`.

**Fix direction:** `?? null` and `continue` with a log entry.

## [ ] B-48 – Valid thumbnails < 50 bytes are deleted and regenerated forever

**File:** `models/Asset/Image/Thumbnail/Processor.php:378-386`

- The "corrupt PNG" workaround deletes 1×1 GIFs / tiny WebPs; it also runs for existing files (2 extra remote calls) and
  may delete a file another process is still writing (unverified).

**Fix direction:** only check PNGs generated in this call; write to a temp key and move.

## [ ] B-49 – Focal point at `0` ignored; `null` → `0` does not clear thumbnails

**File:** `models/Asset/Image/Thumbnail/Processor.php:461`; `models/Asset/Image.php:335`

- Truthiness check / loose `!=` (`null != 0` is false).

**Fix direction:** `!== null` and strict comparison.

## [ ] B-50 – Case-sensitive SVG / vector detection

**File:** `models/Asset/Image.php:346`; `models/Asset/Image/Thumbnail.php:105`

- `LOGO.SVG` is rasterised despite `rasterizeSVG = false`; `isVectorGraphic()` false → wrong estimates.

**Fix direction:** add the `i` flag (or compare lowercased MIME type).

## [ ] B-51 – Stale thumbnail config in the same process after save/delete

**File:** `models/Asset/Image/Thumbnail/Config/Dao.php:84-114`, `:138-149`; `Config.php:200-212`, `:227-233`

- RuntimeCache key `imagethumb_<crc32>` is never invalidated: `getByName()` returns the old definition after `save()`,
  `exists()` is true after `delete()` (affects Processor TmpStore decisions `:224`, `:354`). Worker runtimes keep it
  until reset.

**Fix direction:** remove the key in `Dao::save()` / `delete()`.

## [ ] B-52 – `Image\Thumbnail\Config\Dao::exists(string $name)` ignores its argument

**File:** `models/Asset/Image/Thumbnail/Config/Dao.php:74-77`

- Uses `$this->model->getName()`.

**Fix direction:** use `$name` (or remove – no callers found).

## [ ] B-53 – `clearTempFiles()` deletes the other config type's directories with the same name; full recursive listing

**File:** `models/Asset/Thumbnail/ClearTempFilesTrait.php:30-37` (regex at `:32`)

- `(image|video|pdf)-thumb__…__<name>`: clearing image config `foo` also deletes `video-thumb__*__foo` and vice versa.
  Lists the entire thumbnail storage recursively (very slow on S3).

**Fix direction:** pass the type prefix per config class; list only the relevant subtree or keep an index.

## [ ] B-54 – Image config listing can contain `null`s and non-list arrays

**File:** `models/Asset/Image/Thumbnail/Config/Listing/Dao.php:33`, `:36`

- `getByName()` can return `null` → typed filter/order callbacks throw; `array_filter` keeps keys → JSON object.

**Fix direction:** drop nulls and `array_values()`.

## [ ] B-55 – `getImageTag()` cannot remove or customise `srcset`

**File:** `models/Asset/Image/Thumbnail.php:372-385`

- `srcset` / `data-srcset` is added after `$removeAttributes` and `imgCallback` run.

**Fix direction:** add `srcset` before remove/callback.

## [ ] B-56 – Cache buster corrupts data URIs and protocol-relative URLs

**File:** `models/Asset/Image/Thumbnail.php:162-171`, `:461`

- `'/cache-buster-N' . $path` is prefixed to `data:image/gif…` (`1x1_pixel`) and `//cdn…`.

**Fix direction:** skip for `^(data:|//|https?:)`.

## [ ] B-57 – Numeric media keys break the default `<source>` in `<picture>`

**File:** `models/Asset/Image/Thumbnail.php:267-272`

- Key `"1024"` becomes int; the appended default gets key `1025` → `media="1025"`. Works only because the file lacks
  `strict_types` (int passed to `string $mediaQuery`). (Admin UI allowing numeric keys unverified.)

**Fix direction:** keep the default config out of the media loop.

## [ ] B-58 – Throwaway orphan thumbnail generated per media query during HTML rendering

**File:** `models/Asset/Image/Thumbnail.php:177-197`; `ImageThumbnailTrait.php:231`

- `$thumb` uses media items without `filenameSuffix` → path matches neither variant; for `rotate`/`trim` configs
  `getWidth()` synchronously generates that orphan file. `$thumb->reset()` at `:181` does nothing.

**Fix direction:** build `$thumb` via `selectMedia()` like `getSrcset()`.

## [ ] B-59 – Media overlays keep stale paths after clone

**File:** `models/Asset/Image/Thumbnail/Config.php:778-787`

- `__clone` rebuilds `addOverlay` paths only in `$this->items`, not `$this->medias`.

**Fix direction:** iterate media item lists too.

## [ ] B-60 – Infinite recursion when `assets.preview_image_thumbnail` = `pimcore-system-treepreview`; inconsistent status-cache writes

**Files:** `models/Asset/Image/Thumbnail/Config.php:195-196`, `:253-254`; `Processor.php:349` vs `ImageThumbnailTrait.php:182`

- `getPreviewConfig()` ↔ `getByName()` recurse until stack overflow.
- Status-cache rows: Processor writes only for `Asset\Image`; the trait writes for any asset even when `status_cache` is
  disabled (impact unverified).

**Fix direction:** skip custom lookup when equal to the system name; one guarded helper for status-cache writes.

---

## Video / Document / MetaData

## [ ] B-61 – Video `ImageThumbnail::getMedia()` throws `TypeError` for normal videos and ignores frame / `imageAsset`

**File:** `models/Asset/Video/ImageThumbnail.php:233-240`

- `getById($this->asset->getCustomSetting('image_thumbnail_asset'))` with `null` → `TypeError` (strict). Ignores
  `$this->imageAsset` and the time offset, so media thumbnails never match `getPath()`; `$this->asset` may be null
  (`Video.php:147`).

**Fix direction:** reuse `generate()`'s source resolution; null-guard.

## [ ] B-62 – Exceptions during video conversion leave status `finished` → endless re-conversion on page views

**File:** `models/Asset/Video/Thumbnail/Processor.php:288-290`, `:93-106`

- `catch (Exception)` only logs; status stays `finished` with the format missing; `process()` dispatches a new job on
  every page view. `Error`s are not caught; lock not released in `finally` (see C-11).

**Fix direction:** set `error` in the catch, catch `\Throwable`, release lock in `finally`.

## [ ] B-63 – Temp files leak in the video conversion worker

**File:** `models/Asset/Video/Thumbnail/Processor.php:119`, `:259`, `:262-274`, `:283-284`

- `File::getLocalTempFilePath()` registers cleanup in the **web** request that called `process()`, not in the worker.
  Destination is unlinked only on success; DASH segments (`*-stream*.mp4`) are uploaded but never unlinked;
  `fopen()` → `false` passed to `writeStream()`.

**Fix direction:** unlink in `finally`; check `fopen`.

## [ ] B-64 – DASH (`mpd`) conversion drops main-config transformations/bitrates and mangles media options

**File:** `lib/Video/Adapter/Ffmpeg.php:89-93`, `:123`, `:136-143`

- `$command = [];` discards `$this->arguments` (`-vb`, `-ab`, `-t`, `-vf`); sub-converters contribute only `arguments`,
  never `videoFilter`; a single-element `-an` breaks the `% 2` pairing; `:137` duplicates `:136`.

**Fix direction:** build mpd arguments explicitly per sub-converter (`-filter:v:N`), keep global args, add flags without
pairing.

## [ ] B-65 – `cut` start time is silently reset

**File:** `lib/Video/Adapter/Ffmpeg.php:167-170`

- `$this->getDuration() * 100`: `null * 100 = 0` resets every offset; the `*100` factor is wrong (seconds vs. 100×).

**Fix direction:** compare with the real duration, only when non-null.

## [ ] B-66 – Concurrent `process()` calls start duplicate conversions; frontend GETs write to DB

**File:** `models/Asset/Video/Thumbnail/Processor.php:86-165`

- Check-then-act on the `thumbnails` custom setting without lock: two renders both save the asset and dispatch; the
  second overwrites `processId`.

**Fix direction:** per-(asset, config) lock around check, save and dispatch.

## [ ] B-67 – Global blocking `video-transcoding` lock with fixed 2 h TTL

**File:** `models/Asset/Video/Thumbnail/Processor.php:232-233`

- `acquire(true)` serialises all conversions system-wide and blocks every worker that picks up a video message (starving
  other `pimcore-core` messages). Conversions > 2 h lose the lock (no `refresh()`).

**Fix direction:** per-asset / semaphore lock or dedicated transport; `refresh()` during long runs.

## [ ] B-68 – Video getters save the asset and permanently flag processing as failed when ffmpeg is unavailable on the web node

**File:** `models/Asset/Video.php:197-205`, `:222-233`

- `getDuration()` / `getDimensions()` / `getWidth()` / `getHeight()` call `$this->save()` – persisting unsaved caller
  changes and firing save events during frontend GETs.
- If ffmpeg is missing on that host, `CUSTOM_SETTING_PROCESSING_FAILED` is saved permanently, disabling later processing
  on workers that do have ffmpeg. `getDuration()` never sets the flag, so broken videos spawn ffmpeg on every call.

**Fix direction:** no saves in getters; set the failed flag only when the backend actually ran and failed.

## [ ] B-69 – Document thumbnail returns the error placeholder under concurrency

**File:** `models/Asset/Document/ImageThumbnail.php:141-142`, `:164-166`; `models/Asset/Document.php:106`

- Non-blocking `$lock->acquire()` with `deferred = false` → concurrent requests get the "filetype-not-supported"
  placeholder. Video blocks (`Video/ImageThumbnail.php:135-136`) – inconsistent.

**Fix direction:** blocking acquire with timeout, or re-check `fileExists` after a wait.

## [ ] B-70 – Video image thumbnail with format `original`/`source` writes PNG data under `.mp4`

**File:** `models/Asset/Video/ImageThumbnail.php:94`; `ImageThumbnailTrait.php:436-460`; `Image/Thumbnail/Processor.php:166-168`

- `checkAllowedFormats` accepts `original`, `source` and `mp4`; `fileExtension` becomes `mp4` while PNG is saved → wrong
  MIME type. `Document\ImageThumbnail::createConfig` (`:193-198`) normalises to PNG; Video does not. (Inferred, not run.)

**Fix direction:** map these to PNG in Video `createConfig()`.

## [ ] B-71 – Video thumbnail filename regex is unanchored and case-sensitive

**File:** `models/Asset/Video/Thumbnail/Processor.php:117`

- Removes every `.mp4` occurrence (`a.mp4.final.mp4` → `a.final`); `foo.` strips all dots.

**Fix direction:** `pathinfo($filename, PATHINFO_FILENAME)`.

## [ ] B-72 – Video config listing ignores filter and order

**File:** `models/Asset/Video/Thumbnail/Config/Listing/Dao.php:28-36`

- `FilterListingTrait` / `OrderListingTrait` are never applied; `getTotalCount()` counts unfiltered ids.

**Fix direction:** mirror `Image/Thumbnail/Config/Listing/Dao.php`.

## [ ] B-73 – `Video\Thumbnail\Config::getByName()` returns the shared cached instance

**File:** `models/Asset/Video/Thumbnail/Config.php:113-132`

- Caller mutations (`setItems`, `selectMedia`) leak into the RuntimeCache; missing configs are not negatively cached.

**Fix direction:** `return clone $thumbnail;` (as the Image config does).

## [ ] B-74 – Listfolder-grid unmarshal of relation metadata throws `TypeError` on `null`/numeric data

**File:** `models/Asset/MetaData/ClassDefinition/Data/Asset.php:133`, `Document.php:133`, `DataObject.php:133`; caller
`models/Asset/Service.php:274`

- `getByPath(string)` receives `$item['data'] ?? null`; clearing a cell or sending an id crashes.

**Fix direction:** `is_string($data) && $data !== ''`; accept numeric ids.

## [ ] B-75 – Relation metadata passes string ids to `Element\Service::getElementById` (deprecation on every load)

**File:** `MetaData/ClassDefinition/Data/Asset.php:42`, `:91`; `Document.php:42`, `:91`; `DataObject.php:42`, `:91`

- `is_numeric("123")` is true; `getElementById` triggers a deprecation for string ids (`models/Element/Service.php:442-447`).

**Fix direction:** `(int)$value`.

## [ ] B-76 – Inconsistent "empty" values in relation metadata

**File:** `MetaData/ClassDefinition/Data/Asset.php:76`, `Document.php:76`, `DataObject.php:76`

- `getDataFromEditMode()` returns `''`, `normalize()` returns `null`; affects `isEmpty` and the search index.

**Fix direction:** return `null`.

## [ ] B-77 – Document/DataObject metadata grid round-trip uses `getFullPath()`

**File:** `MetaData/ClassDefinition/Data/Document.php:107`, `DataObject.php:107`

- Asset uses `getRealFullPath()`; a site-relative `getFullPath()` would not resolve in `getByPath()` (unverified in admin
  context).

**Fix direction:** `getRealFullPath()`.

## [ ] B-78 – Invalid date metadata stored as `false`

**File:** `MetaData/ClassDefinition/Data/Date.php:32-34`

- `strtotime()` → `false` is persisted.

**Fix direction:** return `null` on `false` / validate in `checkValidity()`.

## [ ] B-79 – Embedded metadata marked as extracted even when extraction failed

**File:** `models/Asset/MetaData/EmbeddedMetaDataTrait.php:66-98`

- exiftool `Process` uses the default 60 s timeout, exit code ignored; on any exception the flag is set with `[]` and
  never retried (`:48`).

**Fix direction:** set the flag only on success; explicit timeout; `isSuccessful()`.

## [ ] B-80 – `Video::getThumbnail()` enriches a never-written `medias` key; by-reference loop leaks reference

**File:** `models/Asset/Video.php:101-111`

- Processor writes only `formats[...]` (`Processor.php:277`); the `medias` branch is dead; `foreach (&$path)` leaves a
  dangling reference; missing `formats` key → warning.

**Fix direction:** drop `medias`, `array_map` without references, `?? []`.

## [ ] B-81 – Spherical metadata reader closes the asset's shared stream; extension check is case-sensitive

**File:** `models/Asset/Video.php:276`, `:278`, `:324`

- `fclose($this->getStream())` invalidates the cached stream; `in_array(ext, ['mp4','webm'])` skips `.MP4`.

**Fix direction:** open a dedicated stream via `Storage::readStream`; `strtolower()`.

## [ ] B-82 – `Video\Thumbnail\Processor::applyTransformations()` crashes on an unknown method

**File:** `models/Asset/Video/Thumbnail/Processor.php:175`

- `self::$argumentMapping[$method]` → `null` → `array_search` / `count` `TypeError`.

**Fix direction:** validate the method and skip with a log entry (same as B-47 for images).

---

## WebsiteSetting (Scope 2)

## [ ] B-83 – `pimcore_website_config()` and `WebsiteSetting::getByName()` resolve overrides differently; result depends on row id order

**Files:** `lib/Config.php:207-237` vs `models/WebsiteSetting/Dao.php:50-78`; `models/WebsiteSetting/Listing/Dao.php:32`

- `getWebsiteConfig()` walks **all** rows in unspecified order (the listing has no `ORDER BY`, so it is effectively
  primary-key order) and:
  - skips a row without language if the key is already set (`:225`);
  - lets any language row overwrite.
- Consequences (`a` = global neutral, `b` = site 1 neutral, `c` = global `en`; site 1, language `en`):
  - `getByName('x', 1, 'en')` → `b` (site beats language, `ORDER BY CONCAT(siteId, language) DESC`).
  - `pimcore_website_config('x')` → `a` is set, `b` is **skipped** because `a` already set the key, then `c` overwrites
    → `c`.
  - If `b` had a lower id than `a`, the result would be `b`. The value shown on the frontend depends on creation order.
- A site-specific neutral override is ignored by `pimcore_website_config()` whenever a global neutral row with a lower id
  exists – the most common use case of site overrides.

**Fix direction:** implement one precedence rule (site+language > site > language > global, matching
`WebsiteSettingTest`) in a shared resolver used by both APIs; order the listing explicitly. Extend the test for
`getWebsiteConfig()` parity (I-40).

## [ ] B-84 – `siteId = 0` and `siteId IS NULL` are ranked differently in `getByName()`

**File:** `models/WebsiteSetting/Dao.php:59-69` (`ORDER BY CONCAT(siteId, language) DESC, siteId DESC, language DESC`)

- Both values mean "all sites" (the `WHERE` accepts both, `lib/Config.php:213` treats `0` as global), but
  `CONCAT(NULL, …)` is `NULL` and sorts last, while `CONCAT(0, '')` is `'0'`.
- With a legacy row (`siteId = 0`, neutral; migrated by `Version20210323082921`) and a newer global language row
  (`siteId NULL`, `en`): `getByName('x', null, 'en')` returns the **neutral** row, although the language-specific one
  should win (it wins when both have `siteId NULL`).
- Ranking is also done by **string** comparison of `CONCAT(siteId, language)`. It happens to work because only one real
  site id can match, but the intent is opaque.

**Fix direction:** explicit ranking, e.g.
`ORDER BY (siteId = :site) DESC, (language = :lang) DESC, id ASC`, and normalise `0` → `NULL` on save (plus a data
migration).

## [ ] B-85 – `$fallbackLanguage` is never used when a language-neutral row exists

**File:** `models/WebsiteSetting.php:95-115`; `models/WebsiteSetting/Dao.php:64-68`

- The query for `$language` already matches neutral rows (`language = ''`), so `NotFoundException` – the only path to
  the fallback – is thrown only when **no** neutral row exists either.
- Rows `neutral = 'x'`, `en = 'y'`: `getByName('n', null, 'de', 'en')` → `'x'`, not `'y'`. The docblock ("if property
  cannot be found the value of property without language is returned") documents neither order clearly; callers who pass
  a fallback language expect `de → en → neutral`.

**Fix direction:** decide and document the order. If it is `language → fallback → neutral`, query with
`language IN (:lang, :fallback, '')` and rank in that order.

## [ ] B-86 – No uniqueness for `(name, siteId, language)`; empty names accepted

**Files:** `bundles/InstallBundle/dump/install.sql:603-615` (only non-unique `INDEX name`, `INDEX siteId`);
`models/WebsiteSetting.php:291-301`

- Duplicate rows for the same name/site/language are allowed (API, imports, double-click in admin). `getByName()` then
  returns an arbitrary one (no id tie-breaker in `ORDER BY`); `getWebsiteConfig()` returns the last or first by id
  (B-83). Editing one of them in the admin may have no visible effect.
- `save()` does not validate that `name` is non-empty (default `''`).

**Fix direction:** validate in `save()` (non-empty name, no duplicate for the same site/language) and add a unique key.
`siteId NULL` defeats a plain unique index, so normalise to `0` first or use a generated column.

## [ ] B-87 – `type` is not validated; an unknown type silently makes the setting disappear

**Files:** `models/WebsiteSetting.php:228-248`; `install.sql:606` (`ENUM('text','document','asset','object','bool')`);
`lib/Config.php:229-234`

- `setType('number')` (or a typo) is stored as `''` without error under Pimcore's `sql_mode = ''` (see CoreBundle
  TODO-1 notes). `getWebsiteConfig()` then maps it to `default => null`, and `isset($data)` drops the key, so the setting
  silently vanishes from `pimcore_website_config()`. `getData()` returns the raw value instead of lazy-loading an element.
- `setData(ElementInterface)` sets the type automatically, but `setData()` with a scalar never resets a previous element
  type, so `setType('document')->setData('hello')` stores text under an element type (`getData()` returns `'hello'`
  because it is not numeric, while `getWebsiteConfig()` returns it as an "element").

**Fix direction:** backed enum for the type (I-33); reject unknown values in `setType()`; validate data against the
type in `save()`.

## [ ] B-88 – `save()` with a manually set id that does not exist silently does nothing; add/update detection differs from the DAO

**Files:** `models/WebsiteSetting.php:136-141`, `:291-301`; `models/WebsiteSetting/Dao.php:80-87`, `:95-102`

- `(new WebsiteSetting())->setId(999)->setName('x')->save()`: the DAO sees a truthy id → `update()` → 0 rows affected,
  no error. `PRE_UPDATE`/`POST_UPDATE` are dispatched for a write that never happened.
- `save()` decides "add" by `$this->id === null`, the DAO by `if ($this->model->getId())`. With id `0` the model fires
  `PRE_UPDATE`/`POST_UPDATE` while the DAO inserts.

**Fix direction:** use one `isNew()` check in both places; make `update()` throw `NotFoundException` when no row is
affected (or make `setId()` `@internal`).

## [ ] B-89 – `Dao::create()` is a non-atomic INSERT + UPDATE

**File:** `models/WebsiteSetting/Dao.php:104-115`

- The INSERT writes only `name` and `siteId`. If the following `update()` fails (DB error, invalid data), a half row
  remains with `type NULL`, `data NULL`, `language ''`, `creationDate 0`. That row is a valid match for `getByName()` (and
  shadows lower-ranked rows), and its value is `null`.
- No transaction; `POST_ADD` is not dispatched and the caller gets an exception, so nobody cleans up.

**Fix direction:** single `INSERT` with all valid columns (see I-36), or wrap both statements in a transaction.

## [ ] B-90 – Website setting listing can contain `null` entries (concurrent delete)

**Files:** `models/WebsiteSetting/Listing/Dao.php:32-43`; consumer `lib/Config.php:210-212`

- `load()` selects ids and then calls `WebsiteSetting::getById()` per id. A row deleted between both steps yields
  `null` in the `WebsiteSetting[]` array. `getWebsiteConfig()` then calls `$item->getSiteId()` on `null` → `Error`. This
  breaks every page that uses `pimcore_website_config()` until the cache is rebuilt.

**Fix direction:** skip `null`s (or hydrate from a single `SELECT *`, see I-35).

## [ ] B-91 – `clearDependentCache()` does not invalidate the full-page output cache

**File:** `models/WebsiteSetting.php:268-271`; `lib/Config.php:205`

- Only the tag `website_config` is cleared. Full-page cache entries (`FullPageCacheListener`, tag `output`) that rendered
  the old value stay cached until their lifetime ends, so changes to texts, tracking IDs or feature switches stored as
  website settings do not reach cached pages. (The reverse works: clearing `output` drops the config cache, because the
  config is tagged `output`.) Needs a decision on whether this is intended; document it if so.

**Fix direction:** clear `output` as well (as document saves do), or document the expected delay in the admin UI.

---

## Dependency (Scope 3)

## [ ] B-92 – `ElementDependenciesHandler` leaks `hideUnpublished = false` / `getInheritedValues = false` into the rest of the process on any exception

**File:** `lib/Messenger/Handler/ElementDependenciesHandler.php:47-75`

- `showUnpublished()` (`:49`) and `setGetInheritedValues(false)` (`:51`) change process-wide statics. They are restored
  at `:70-71` with no `try/finally`. An exception from `resolveDependencies()` (broken relation, missing class
  definition, a field getter throwing) skips the restore.
- The handler runs **synchronously inside the web request / CLI command** by default (see TODO-1 Scope 3 notes). If the
  caller catches the save exception (importers, REST APIs, `try { $obj->save(); } catch …`), every later listing and getter
  in that process sees **unpublished documents/objects** and **no inherited values**. In messenger workers and
  long-running imports this lasts until restart. Unpublished content can end up in frontend output, feeds, sitemaps or
  search indexes.
- `Dao::save()` (`:73`) runs after the restore, so its errors do not leak state. The resolve step is the risk.

**Fix direction:** wrap the resolve loop in `try { … } finally { restore }`, or run it through the existing scoped helper
`DataObject\Service::useInheritedValues(false, fn () => …)` (`models/DataObject/Service.php:1962`) plus an equivalent
helper for `hideUnpublished`.

## [ ] B-93 – `getFilterRequiredByPath()` joins on `targettype` instead of `sourcetype`: wrong or missing "required by" results

**File:** `models/Dependency/Dao.php:152`, `:157`, `:162`

```sql
INNER JOIN objects o ON o.id = d.sourceid AND d.targettype = 'object'
WHERE d.targettype = '<targetType>' …
```

- The join condition should restrict `d.sourcetype`. Combined with `WHERE d.targettype = :targetType`, only the branch
  whose literal equals the **target** type can return rows. That branch joins **every** source id (any type) against
  that one table.
- Example: asset 10 is required by object 5 and document 7. Filtering the asset's "required by" list only runs the
  `assets` branch. It matches `assets.id = 5` and `assets.id = 7` (unrelated assets, or none), filters on **their**
  paths and returns `{id: 5, type: 'object'}` when the unrelated asset 5's path matches. Real sources whose own path
  matches are never found.
- `getRequiredByWithPath()` (`:343`, `:348`, `:353`) has the correct condition (`d.sourceType = …`). Compare with it.

**Fix direction:** `AND d.sourcetype = 'object'` / `'document'` / `'asset'` in the three joins. Add a test with mixed
source types (I-50).

## [ ] B-94 – Filter value is an unescaped, case-sensitive regex: uppercase never matches, `.`/`(`/`[` misbehave, empty value errors

**File:** `models/Dependency/Dao.php:94-104`, `:153-163`; schema `install.sql` (`objects`/`documents`/`assets`
`path`, `key`, `filename` are `utf8_bin`)

- `LOWER(CONCAT(path, key)) RLIKE '<value>'` lowercases only the column side. The columns are `utf8_bin`, so the regex
  comparison is case-sensitive: searching `Logo` never matches `/Logo.png`, unless the caller lowercases the value.
- The value is a regex. `image.jpg` also matches `imageXjpg`. `file(1)` matches `file1` but not `file(1)`. An
  unbalanced `[`, `(` or a trailing `\` raises a MySQL regex error → 500. Editors type file names, not regexes.
- `$value` defaults to `null` (`Dependency.php:105`, `:112`) → `RLIKE ''`. MySQL 8 (ICU) rejects an empty pattern
  ("Illegal argument to a regular expression", not verified at runtime); MariaDB matches everything.

**Fix direction:** treat the value as a literal: `LIKE :value` with `'%' . Helper::escapeLike(mb_strtolower($value)) . '%'`
(and `LOWER()` on both sides, or a `_ci` collation in the expression). Reject or short-circuit an empty value.

## [ ] B-95 – Search value goes through `sprintf()`: `%` in the filter text throws or rewrites the query

**File:** `models/Dependency/Dao.php:108-110`, `:167-169`

```php
$query = sprintf($query . ' LIMIT %d,%d', $offset, $limit);
```

- `$query` already contains the raw `$value` (C-16). Any `%` in it is parsed as a format specifier: `100%` makes the
  following characters into a specifier (`%'`), `50%s` consumes an argument. Results are `ValueError` /
  `ArgumentCountError` (PHP 8) or a silently changed pattern / `LIMIT`. Searching for `%` only works without pagination.
- `getRequiredBy()` (`:302`) uses the same pattern. It is safe today (no user input in the query) but fragile.

**Fix direction:** append `' LIMIT ' . (int)$offset . ', ' . (int)$limit` (as `getRequiredByWithPath()` does,
`:357-359`), or use DBAL `setFirstResult()` / `setMaxResults()` (I-43).

## [ ] B-96 – `getFilterRequires*ByPath()` whitelist `orderBy = 'path'`, but the queries select no `path` column

**File:** `models/Dependency/Dao.php:79`, `:88-106`, `:138`, `:147-165`

- Both filter queries return `SELECT id, type` only. `orderBy: 'path'` passes the whitelist and fails with
  "Unknown column 'path' in 'order clause'".
- Not reachable through the model wrappers today (`Dependency.php:105-117` do not forward `$orderBy` /
  `$orderDirection`), so sorting a filtered list by path is impossible and calling the DAO directly crashes.

**Fix direction:** select `CONCAT(…) AS path` in each `UNION` branch (as in `getRequiredByWithPath()`) and forward
`$orderBy` / `$orderDirection` from the model (I-43).

## [ ] B-97 – Rows pointing to deleted elements are never removed for several element types; counts and `isRequired()` include them

**Files:** `models/Dependency/Dao.php:185-203`, `:368-371`; `models/Dependency.php:160-177`;
`lib/Messenger/Handler/SanityCheckHandler.php` (`performSanityCheck()`)

- On delete, `cleanAllForElement()` removes only rows where the element is the **source**. Rows where it is the
  **target** are expected to vanish when the sanity check re-saves each source. `performSanityCheck()` returns early
  for everything that is not `PageSnippet`, `Concrete` or `Asset`: document folders, links and hardlinks, and data object
  folders (e.g. a property on a folder, or a link target). Their rows to the deleted element stay forever. The same
  happens for all elements while `pimcore.dependency.enabled` is `false`.
- Effects:
  - `getRequiresTotalCount()` (in-memory list) and `getRequiredByTotalCount()` / `isRequired()` (SQL `COUNT(*)`) count
    rows whose element no longer exists.
  - `Element\Service::get*DependenciesForFrontend()` silently drops them (`getDependedElement()` → `null`), so pages are
    shorter than `$limit` and the total does not match the rows shown.
  - `getBySourceId()` / `getRequiredBy()` `LEFT JOIN` and keep orphans; `getRequiredByWithPath()` / filter queries use
    `INNER JOIN` and hide them. The same element shows different counts in different tabs.

**Fix direction:** in `cleanAllForElement()` also delete rows where the element is the **target**. The sanity check is
still needed to clean the sources' data, but the graph must not depend on it. Count with the same join the list uses.

## [ ] B-98 – `cleanAllForElement()` dispatches sanity checks before deleting and swallows DB errors inside the element's delete transaction

**File:** `models/Dependency/Dao.php:185-203`; callers `models/Asset.php:1018`, `models/Document.php:640`,
`models/DataObject/AbstractObject.php:438` (all inside `beginTransaction()` … `commit()`)

- `SanityCheckMessage`s are dispatched **before** the rows are deleted and before the element delete is committed.
  This is safe with the default `pimcore_core` transport (`doctrine://default`, same connection, enqueued inside the
  transaction). With a `sync://`, AMQP or Redis transport (a common production change) the message is handled before
  commit. The source is re-saved while the target still exists, its dependency rows to the target are recreated, and
  they stay (B-97). If the delete is rolled back, the sources still get a needless "Sanity Check" version.
- `catch (Exception $e) { Logger::error(...) }` (`:200-202`) hides DB failures. The element delete then **commits**
  with its dependency rows intact. No caller or `POST_DELETE_FAILURE` listener is told.

**Fix direction:** delete first, dispatch after commit (collect messages and dispatch them from the caller, or use
`DispatchAfterCurrentBusStamp` / a post-commit hook). Let DB exceptions propagate so the element transaction rolls
back.

## [ ] B-99 – Uninitialized typed properties and `null` ids crash `Dependency` (`Error` / `TypeError`)

**Files:** `models/Dependency.php:32`, `:38`, `:51-57`, `:91-94`, `:148-151`; `models/Dependency/Dao.php:41-46`;
`models/Element/AbstractElement.php:613-620`

- `protected int $sourceId; protected string $sourceType;` have no default. `new Dependency()` followed by any getter
  or DAO method except `cleanAllForElement()` throws "must not be accessed before initialization".
- `Dependency::getBySourceId(0, 'asset')`: `Dao::getBySourceId()` skips the setters when `$id` is falsy
  (`if ($id && $type)`, `:43`) and then reads `getSourceId()` → `Error`.
- `AbstractElement::getDependencies()` on an **unsaved** element passes `getId()` (`null`) to
  `getBySourceId(int $id, …)` → `TypeError`.
- `addRequirement(int $id, …)` (`Dependency.php:63`) receives the ids from `resolveDependencies()`. A relation to an
  unsaved element (`getId()` `null`, e.g. `Property::resolveDependencies()`, `models/Property.php:270`) throws
  `TypeError` in the strict handler. Combined with B-92 and C-17 this leaks state and leaves the graph incomplete.

**Fix direction:** constructor with required `int $sourceId, string|ElementType $sourceType` (I-41/I-42), or nullable
properties with explicit checks. Return an empty `Dependency` for unsaved elements in `getDependencies()`. Skip
requirements with a `null`/`0` id in the handler.

## [ ] B-100 – `AbstractElement::getDependencies()` is cached on the element and never reset after save or delete

**File:** `models/Element/AbstractElement.php:47`, `:613-620` (reset only in `__clone()`, `:675`)

- Elements are shared through `RuntimeCache`. After `$element->save()` the sync handler rewrites the rows, but
  `$element->getDependencies()` keeps returning the `requires` list loaded before the save. This lasts for the rest of the request, or for the whole worker in messenger consumers and
  long-running imports.
- `DependenciesTest` reloads with `['force' => true]` before asserting, so the test hides this.
- The cached object also keeps the full `requires` array (I-44) in memory for the element's lifetime.

**Fix direction:** set `$this->dependencies = null` in `save()` / `delete()` (or in `addToDependenciesQueue()`), or do not
cache at all (the getter is cheap once I-44 lands).

## [ ] B-101 – Pagination: `$limit` without `$offset` is ignored in `getRequires()` and the SQL getters

**Files:** `models/Dependency.php:96-103`; `models/Dependency/Dao.php:108`, `:167`, `:301`, `:357`

- `getRequires(null, 25)` returns **all** rows. `array_slice()` runs only when `$offset !== null`. The DAO getters
  apply `LIMIT` only when both are non-null. Callers that pass only a limit ("first 25") get everything.

**Fix direction:** treat `null` offset as `0` when a limit is given, in all getters.

## [ ] B-102 – No unique tie-breaker in `ORDER BY`: pages overlap or skip rows

**File:** `models/Dependency/Dao.php:55`, `:106`, `:165`, `:298`, `:355`

- `getRequiredByWithPath()` / filter queries sort by `type` or `path` only. Many rows share a type, and `id` is not
  unique across types (object 5 and asset 5). MySQL may return ties in a different order per query, so `LIMIT`-based
  paging in the admin shows duplicates and misses rows.
- `getBySourceId()` / `getRequiredBy()` sort by the `LEFT JOIN`ed path columns. `NULL`s sort first, so the result is
  "all assets, then documents, then objects" by accident, with no final tie-breaker (e.g. two rows with the same
  `path`/`key` after a broken move).

**Fix direction:** always append `, type, id` (or `d.id`) to the `ORDER BY`. Sort the mixed list by one computed `path`
expression if a global path order is wanted.

## [ ] B-103 – Concurrent / out-of-order dependency saves for the same source: the older state can win

**Files:** `models/Dependency/Dao.php:222-283`; `lib/Messenger/Handler/ElementDependenciesHandler.php:39-45`

- The handler re-loads the element and recomputes the rows. With an async `pimcore_dependencies` transport and several
  consumers, or two web requests saving the same element, two `save()` calls interleave (read existing → delete →
  insert). The one that **finishes** last wins even if it computed from an older element state. Rows the newer state
  needs can be deleted.
- Duplicate entries in `requires` (two properties pointing to the same element) are inserted twice. The second insert
  hits the `combi` unique key, and the exception is swallowed (`:279-280`).

**Fix direction:** serialise per source (row lock on the element with `SELECT … FOR UPDATE` inside the C-17
transaction, or a `LockFactory` lock keyed by `type-id`). Deduplicate `requires` before the delta (I-45).

## [ ] B-104 – Invalid source types: HTTP exception from the model layer, silently stored elsewhere

**Files:** `models/Dependency/Dao.php:73-77`, `:132-136`, `:323-327`; `models/Dependency.php:153-158`;
`install.sql:66`, `:68` (`ENUM('document','asset','object')`)

- Only the three path getters validate the type, and they throw `Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException`.
  Symfony turns it into an HTTP **400** and logs it as a security event. In CLI / messenger it is an unexpected HTTP type.
- `setSourceType()`, `addRequirement()`, `getBySourceId()`, `getRequiredBy()` and `save()` accept any string.
  `addRequirement(5, 'Object')` or `'dataobject'` is stored as `''` (empty enum value) under Pimcore's `sql_mode = ''`
  (see B-87). The row is never found again and blocks the slot in the `combi` key.

**Fix direction:** validate once in the setters / `addRequirement()` with an `ElementType` enum (I-42) and throw
`\InvalidArgumentException` (or `\ValueError` from `ElementType::from()`).

## [ ] B-105 – Filter helpers in `Element\Service` omit the `requires` / `requiredBy` key when nothing matches

**File:** `models/Element/Service.php:207-242`

- `getRequiredByDependenciesForFrontend()` / `getRequiresDependenciesForFrontend()` initialise the list key to `[]`.
  The filter variants `getFilterRequiresForFrontend()` / `getFilterRequiredByPathForFrontend()` do not. An empty search
  returns `{hasHidden: false}` without `requires` / `requiredBy`. The admin store then has to handle a missing root, and
  `$dependencies[...]` in PHP consumers raises "Undefined array key".

**Fix direction:** initialise the key in both, or merge the four helpers into one (I-47).

## [ ] B-106 – Sync dependency handler errors make a committed element save look failed and skip `POST_*` events

**Files:** `models/Asset.php:611` (before `POST_UPDATE`/`POST_ADD` at `:622-629`, failure events `:633-644`), `models/Document.php:386`,
`models/DataObject/AbstractObject.php:609`; `lib/Messenger/Handler/ElementDependenciesHandler.php`

- With the default sync routing, `addToDependenciesQueue()` runs the handler immediately. Any exception from it
  (C-17, B-92, B-99, B-103) is wrapped in `HandlerFailedException` and escapes from `save()` **after** the element
  transaction was committed.
- The caller (admin UI, importer) reports "save failed" although the data is stored. `Asset::save()` catches it and
  dispatches `POST_UPDATE_FAILURE` / `POST_ADD_FAILURE` for a committed write, while `POST_ADD` / `POST_UPDATE`
  listeners (search index, cache warmers, workflows) never run. Retrying creates another version.

**Fix direction:** catch and log dependency failures in `addToDependenciesQueue()` (the graph can be rebuilt), or move
the dispatch after the `POST_*` event. Long term, route `ElementDependenciesMessage` async by default.

---

## Translation (Scope 4)

## [ ] B-107 – `getByKey()` runtime cache: the key ignores `$create` / `$returnIdIfEmpty`, one shared mutable instance, never invalidated

**File:** `models/Translation.php:248-255`, `:283-294`, `:354-373`

- Cache key is `translation_<id>_<domain>[_<languages>]`. A call with `$returnIdIfEmpty = true` stores an object whose
  empty languages were replaced by the key. A later plain `getByKey($id, $domain)` in the same request gets that
  object, so the caller sees the key as the "translation", and saving it persists that (see C-18).
- The cached object is returned **by reference**. Any caller that changes it (`addTranslation()`, import, Translator)
  changes it for all later callers, including the copy in the dumper queue.
- `save()` / `delete()` never update or remove the entry. Within the request (and in worker runtimes, where
  `RuntimeCache` survives, see C-15) `getByKey()` keeps returning a deleted translation or outdated texts.
- `$returnIdIfEmpty` uses `empty()` (`:286`): a real translation `"0"` is replaced by the key.
- The key format is ambiguous: id `a_b` + domain `c` and id `a` + domain `b_c` share a key. Domains are config values, so
  this is unlikely, but the separator should not be a valid character.

**Fix direction:** cache the persisted state only (no presentation flags), return a clone or treat the object as
immutable, and update/remove the entry in `save()` / `delete()`. Use `=== ''` instead of `empty()`.

## [ ] B-108 – `getByKey()` swallows every `Exception`, but not `TypeError`; DB failures look like "not found"

**File:** `models/Translation.php:262-281`; `models/Translation/Listing/Dao.php:117-123`

- `catch (Exception $e)` turns connection loss, lock timeouts, unknown columns and SQL syntax errors into `null`, or into
  a fake translation made from the key when `$returnIdIfEmpty` is set. Nothing is logged. The data-loss path is C-22.
- `TypeError` from hydration (`NULL` text/dates, C-19, B-109) is an `Error` and is **not** caught, so the method can
  crash and silently return `null` for the same broken key, depending on which column is broken.
- `NotFoundResourceException` is a Symfony **translation loader** exception ("resource file not found"), used here as a
  "row not found" signal. `@throws Exception` is documented but almost never thrown.
- `Listing\Dao::load()` drops keys for which `getByKey()` returns `null` (e.g. no row in the listing's `languages`
  subset). Pages are shorter than `limit`, and `getTotalCount()` does not match (B-118).

**Fix direction:** catch only the "not found" case (an own `NotFoundException` from the DAO, plus
`TableNotFoundException` during the transition). Let other DB errors propagate. Log the rest.

## [ ] B-109 – Hydration keeps the newest `creationDate` and the last row's owner/type; `save()` writes them to every language; `NULL` dates crash

**Files:** `models/Translation/Dao.php:53-73`, `:112-122`; `models/Translation/Listing/Dao.php:71-88`; `lib/Db/Helper.php:38-53`

- `getByKey()` orders rows by `creationDate` ascending and calls `setCreationDate()`, `setModificationDate()`,
  `setType()`, `setUserOwner()`, `setUserModification()` for **each** row. The last row wins: the model gets the
  **newest** creation date and the owner of the most recently created language, not the oldest one.
- `Dao::save()` writes these values into **every** language row (`Helper::upsert()` updates all columns). Each save
  moves the creation date and owner of older languages forward. The history of who created what, and when, is lost.
- `creationDate` / `modificationDate` are `DEFAULT NULL`. A `NULL` reaches `setCreationDate(int)` /
  `setModificationDate(int)` and throws a `TypeError` (not caught, see B-108). `Listing\Dao::getAllTranslations()` has
  the same problem and also calls `setDate(null)` through the "legacy support" branch (`:82-84`), which is dead code
  anyway: `setModificationDate()` overwrites it two lines later.
- Different `type` values per language (possible through direct SQL or old data) are flattened to the last row's type
  on the next save.

**Fix direction:** hydrate key-level fields once with `MIN(creationDate)`, `MAX(modificationDate)`, and the owner of the
first row. Do not update `creationDate` / `userOwner` on existing rows (`ON DUPLICATE KEY UPDATE` only `text`, `type`,
`modificationDate`, `userModification`). Treat `NULL` dates as `null` (make the setters nullable).

## [ ] B-110 – `Dao::save()`: rewrites every language on every save, is not atomic, and does not validate key or type

**File:** `models/Translation/Dao.php:82-125`; `models/Translation.php:84-112`

- Every save upserts **all** languages in the model, with a new `modificationDate` and `userModification`, even if
  only one language changed. The modification info shown in the admin is wrong for the untouched languages, and
  concurrent editors of different languages overwrite each other (see C-22).
- One `insert`/`update` round trip per language, without a transaction. A failure in the middle leaves the key
  partially saved, and `POST_SAVE` is not dispatched.
- `if ($this->model->getKey() !== '')` lets `null` through (a `new Translation()` without `setKey()`). The insert then
  fails with "Column 'key' cannot be null", or writes `''` in non-strict SQL mode. An empty key is skipped silently and
  still dispatches `POST_SAVE`.
- No length check: keys longer than 190 characters fail with "Data too long" (strict mode), or are **silently
  truncated** in non-strict mode and then collide with, and overwrite, another key with the same 190-character prefix.
  Only the Translator checks the length (B-123).
- `type` is not validated: any string is accepted. Longer than 10 characters fails or is truncated. Anything other
  than `simple` is treated as ICU by the Translator (`Translator.php:172-176`). A typo makes the message go through
  `MessageFormatter`, so plain texts with `{`/`}` throw at render time.

**Fix direction:** validate key (non-empty, ≤ 190 **characters**, `mb_strlen`) and type (`TranslationType` enum,
I-52) in the model. Save only changed languages, in one transaction and with one multi-row statement (I-55).

## [ ] B-111 – Disallowed languages are skipped silently: `save()` reports success, `POST_SAVE` fires, the in-memory object keeps the rejected text

**File:** `models/Translation/Dao.php:92-105`; `models/Translation.php:354-363`

- Languages the user may not edit are skipped with a `Logger::warning` (`:102`). The caller (admin grid, import)
  is not told: `save()` returns normally, `POST_SAVE` listeners (search index, sync to external systems) receive the
  model **with** the rejected texts, and the `RuntimeCache` copy (B-107) keeps them for the rest of the request.
- `importTranslationsFromFile()` counts those rows as imported and does not add them to `$delta`.
- For a user with language restrictions who browses the frontend, the Translator's auto-create (C-22) writes only
  the allowed languages. The other locales keep missing the key, so it is queued and saved again on **every**
  request.
- The permission is checked against the user resolved by `updateModificationInfos()`, i.e. whoever is logged in
  when the dumper runs at `kernel.terminate`, not the code that queued the entry.

**Fix direction:** throw an access-denied exception for disallowed languages (or return the skipped list), and do not
dispatch `POST_SAVE` for data that was not written. Auto-create entries (system writes) should bypass the editor
permission check explicitly. See C-20 for the bypass in the same check.

## [ ] B-112 – `createOrUpdateTable()` runs DDL on every save: implicit commit of the caller's transaction

**File:** `models/Translation/Dao.php:82-86`, `:191-211`

- `save()` always runs `CREATE TABLE IF NOT EXISTS …`. In MySQL/MariaDB, `CREATE TABLE` causes an **implicit commit**,
  also when the table already exists. If `Translation::save()` is called inside a transaction (installers, migrations,
  element save listeners, imports wrapped in `transactional()`), everything before it is committed, and a later
  `rollBack()` cannot undo it. DBAL still thinks a transaction is open and fails with "There is no active transaction".
- It takes a metadata lock on every save. Under load, or while a long query or `ALTER` runs on that table, saves wait.
- The name says "update", but the method never updates an existing table. Schema changes (e.g. a new column) are not
  applied to existing domain tables.
- This DDL is a second copy of the schema (`install.sql:432-444`); the copies already differ (`ENGINE=InnoDB` only here).

**Fix direction:** create the domain tables at install/migration time, or when the domain list changes, plus a
console command (I-62). If lazy creation must stay, run it only after `TableNotFoundException`, never inside an open
transaction.

## [ ] B-113 – `delete()`: stale runtime cache, a queued auto-create re-creates the key, inconsistent cache/event order

**File:** `models/Translation.php:354-373`; `models/Translation/Dao.php:130-133`; `TranslationEntriesDumper.php:29-40`

- `delete()` does not remove the `RuntimeCache` entry (B-107), so `getByKey()` keeps returning the deleted key in the
  same request.
- If the key is also in the dumper queue (Translator saw it missing earlier in the request, or `getByKey(create: true)`
  queued it), `kernel.terminate` saves it again: the delete is undone.
- `save()` clears the translator cache **after** `POST_SAVE`, `delete()` clears it **before** `POST_DELETE`. Listeners
  on `POST_SAVE` that render or translate get the old texts.
- Deleting a key in a domain whose table does not exist yet throws a `TableNotFoundException`.

**Fix direction:** remove the key from `RuntimeCache` and from the dumper queue in `delete()`. Clear caches before the
`POST_*` event in both methods.

## [ ] B-114 – `getByKeyLocalized()`: explicit `$language` is ignored for the admin domain; session lookup in the model; unknown language returns `null`

**File:** `models/Translation.php:313-345`

- For the admin domain, a logged-in user's language **overwrites** the `$language` argument (`:316-320`). Code that
  asks for a specific admin language (e.g. to send a notification in the recipient's language) gets the current user's
  language instead.
- The fallback `Tool\Authentication::authenticateSession()` reads the session and unserialises the security token on
  every call when no token user is set (CLI, frontend requests). It also calls `setLastLoginDate()` on the user object
  as a side effect. Model code should not depend on the HTTP session.
- If the resolved language is not one of `getValidLanguages($domain)` (e.g. `de_AT` with only `de` configured), the
  method returns `null`, even with `$returnIdIfEmpty = true`, because that flag fills only the valid languages.
- `SystemSettingsConfig::get()['general']` without `?? []` emits a warning if the key is missing.

**Fix direction:** only resolve a language when `$language === null`. Get the user from the token storage only.
Return `$id` when `$returnIdIfEmpty` is set and the language has no row.

## [ ] B-115 – `TranslationEntriesDumper`: queue keyed by key only (domains overwrite each other); one failing save aborts the dump and keeps a poison queue

**File:** `lib/Translation/TranslationEntriesDumper.php:27-40`

- `self::$translations[$translation->getKey()]`: the same key missing in `messages` and in `admin` (or a custom domain)
  in one request keeps only the last one. The other domain never gets the entry.
- `dumpToDb()` saves in a loop and resets the queue only after the loop. If one save throws (key > 190 chars, B-110;
  table missing; permission exception after B-111), the rest of the queue is lost for this request. In long-running
  processes (worker runtimes) the static queue is kept, so the same failing entry is retried, and throws, on every
  later `kernel.terminate`.
- The exception is thrown from a `kernel.terminate` listener, after the response was sent. It is only visible in logs.

**Fix direction:** key by `domain . '|' . key`. Save each entry in its own `try/catch` (log and drop failures) and
clear the queue first. See CoreBundle I-52 for flushing in Messenger workers.

## [ ] B-116 – CSV import: shared fixed temp files (concurrent imports mix data), temp files never removed, silent no-ops, lossy comparisons

**File:** `models/Translation.php:386-496`

- Temp files are always `PIMCORE_SYSTEM_TEMP_DIRECTORY/import_translations` and `…_original`. Two imports at the same
  time (two admins, or two domains) overwrite each other's file between `dumpFile()` and `fopen()`. One import then
  writes **the other user's CSV into its own domain**. The files are never deleted, so the translation export remains
  in the temp directory.
- Both files have the same content; the second one is only used for dialect detection.
- `file_get_contents()` returning `false` is not checked (`TypeError` in `str_replace` under strict types).
- No `key` column (wrong delimiter guess, renamed header) → every row is skipped, nothing is reported, and `[]` is
  returned as if the file matched the DB.
- A row with more columns than the header raises "Undefined array key" warnings and maps values to key `''`.
  `str_replace('&quot;', '"', …)` changes texts that really contain the entity.
- Loose comparisons: `$currentTranslation != $value` treats `"1.0"` and `"1"`, or `"1e3"` and `"1000"`, as equal → the
  change is not imported. `if (!$currentTranslation)` treats `"0"` as empty → overwritten when
  `$replaceExistingTranslations` is `false`.
- `getByKey($textKey, $domain, true)` queues every **new** key for the dumper with empty texts, even if the row is not
  `$dirty` (e.g. only empty CSV values). The import then saves it (`$t->save()`) and the dumper saves it again at
  terminate.
- `trim($value, '﻿"')` uses a byte-wise character list that contains the three UTF-8 BOM bytes. Header names that end
  in one of those bytes (any non-ASCII header ending in U+xxBB/BF/EF) are cut.

**Fix direction:** read the uploaded file directly as a stream (no temp copy), or use `tempnam()` and remove the file
in `finally`. Fail on a missing `key` column. Use strict comparisons (`!==`, `=== ''`). See I-57.

## [ ] B-117 – Listing persistent cache key ignores bound condition values and `languages`: wrong results

**File:** `models/Translation/Listing/Dao.php:62-96`, `:107-133`

- `load()` and `getAllTranslations()` cache by `md5((string) $queryBuilder)`. That string contains the **placeholders**
  (`key LIKE ?`), not the values. The cache is skipped only when `addConditionParam()` was used. A listing built with
  `setCondition('`key` LIKE ?', ['%foo%'])` caches its result, and the next search with `['%bar%']` returns the
  **foo** results.
- `load()` also ignores `$this->model->getLanguages()` in the key. A listing for `['de']` and one for `['en', 'fr']`
  share the cache and return objects with the wrong set of languages.
- The cached value is an array of `Translation` objects. They carry the HTML sanitizer service (if
  `getTranslationSanitizer()` was called) and the event-recursion state (B-125) into the persistent cache.
- `Listing::$cacheLimit` / `Listing\Dao::isCacheable()` are never called in this repo, so the 5000-row limit has no
  effect, and huge domains are cached as one item.

**Fix direction:** include the bound values, types and languages in the cache key (or do not cache filtered listings
at all), and cache rows (arrays), not model objects. Either use `isCacheable()` or remove it.

## [ ] B-118 – Listing counts: `getTotalCount()` changes after `load()`, counts rows instead of keys, and does not match the loaded page

**File:** `models/Translation/Listing/Dao.php:38-60`, `:107-133`; `lib/Model/Listing/AbstractListing.php:467-470`

- `load()` calls `$this->model->setGroupBy(<table>.key)`, i.e. it **changes the listing**. Before `load()`,
  `getTotalCount()` / `count($listing)` counts **rows** (keys × languages). After `load()`, it counts **keys**. The same
  listing returns e.g. 600 before and 100 after.
- `load()` drops keys that have no row in the listing's `languages` (B-108), but the SQL count includes them.
- `getCount()` calls `load()` twice when the result is not empty (two full loads with N+1 queries without cache).

**Fix direction:** always count `DISTINCT key` (and apply the `languages` filter in SQL, I-58). Do not mutate the
model in `load()`; build the grouped query locally.

## [ ] B-119 – `Listing::isValidOrderKey()`: `TypeError` without languages, real columns rejected, `type` breaks under `ONLY_FULL_GROUP_BY`

**File:** `models/Translation/Listing.php:56-59`; `models/Translation/Listing/Dao.php:109-111`

- `in_array($key, $this->getLanguages())` with `languages = null` (the default) throws a `TypeError`. So
  `setOrderKey('creationDate')` crashes instead of throwing the documented `InvalidArgumentException`.
- Real columns (`creationDate`, `modificationDate`, `language`, `text`) are rejected. Language codes are accepted,
  but there is no column with that name unless the caller adds one through `onCreateQueryBuilder()` → "Unknown column".
- `load()` groups by `key` only. `ORDER BY type` (accepted) is not functionally dependent on `key` (the PK is
  `key, language`), so MySQL with the default `ONLY_FULL_GROUP_BY` fails.

**Fix direction:** `in_array($key, [...columns], true) || in_array($key, $this->languages ?? [], true)`; order by
`MIN(type)` / `MAX(modificationDate)` in the grouped query.

## [ ] B-120 – Domain checks: `isAValidDomain()` swallows DB errors; the `admin` table is missing on a fresh install; swapped message arguments

**Files:** `models/Translation/Dao.php:175-189`; `models/Translation/Listing.php:66-79`; `install.sql:432-444`

- `isAValidDomain()` returns `false` on **any** exception. During a DB outage, the Translator silently stops loading
  DB translations (frontend shows keys, and the empty result may be cached), and `Listing::setDomain()` throws
  "domain is not registered" – a misleading error.
- `install.sql` creates only `translations_messages`. `admin` is a default domain (`default.yaml:329-331`), but its table
  is created only by the first `save()` (B-112). Until then `isAValidDomain('admin')` is `false`: the Translator never
  records missing admin keys (`Translator.php:240`), and `new Listing()->setDomain('admin')` throws.
- The `NotFoundException` message swaps its arguments: it says domain `translations_<domain>` / table `<domain>`
  (`Listing.php:71-73`).

**Fix direction:** check the table with `createSchemaManager()->tablesExist()` (or `SHOW TABLES LIKE ?`) and let
connection errors propagate. Create tables for all configured domains at install/migration time (I-62). Fix the
message.

## [ ] B-121 – `Dao::getAvailableLanguages()` fails under `ONLY_FULL_GROUP_BY`; `getAvailableDomains()` uses `_` as a LIKE wildcard

**File:** `models/Translation/Dao.php:139-168`

- `SELECT * FROM … GROUP BY language` selects non-aggregated columns. MySQL ≥ 5.7 with the default `sql_mode` fails
  with "Expression #1 of SELECT list is not in GROUP BY clause". No caller in this repo; the admin bundle may use it.
- `SHOW TABLES LIKE 'translations_%'`: `_` matches any character (`translationsX…`). `str_replace('translations_', '', …)`
  removes **every** occurrence, not only the prefix. Both are harmless today only because `isAValidDomain()` filters
  the result afterwards.

**Fix direction:** `SELECT DISTINCT language`; `LIKE 'translations\_%'` and strip the prefix with `substr()`. Better:
derive available domains from the config plus a table-exists check.

## [ ] B-122 – `Listing::cleanup()` does not invalidate caches and fires no events

**File:** `models/Translation/Listing/Dao.php:146-160`

- The deleted keys stay in the cached `load()` / `getAllTranslations()` results (tag `translator`) and in the
  `RuntimeCache` until something else clears the tags. The admin grid keeps showing them.
- No `PRE_DELETE` / `POST_DELETE` events, so listeners that sync translations (search, external TMS) miss the deletes.
- The keys are put into one `IN (…)` list with `quote()`. Thousands of empty keys can exceed `max_allowed_packet`.

**Fix direction:** call `Translation::clearDependentCache()` after the delete; dispatch one bulk event (or document
that cleanup is silent); delete in chunks with bound parameters (I-56).

## [ ] B-123 – Translator auto-create: wrong language list for non-default domains; the 190 check counts bytes and throws during rendering

**File:** `lib/Translation/Translator.php:240-267`

- A new key gets `Translation::getValidLanguages()` **without** the domain (`:261`), i.e. website languages. For the
  `admin` domain (and any domain whose languages differ) rows are created for the wrong languages, and the admin
  languages stay missing.
- `strlen($id) > 190` counts **bytes**; the column is `varchar(190)` in **characters** (utf8mb4). Valid keys with
  non-ASCII characters are rejected although they fit (100 Cyrillic characters = 200 bytes).
- The check **throws** `Exception` from `trans()`. One long text used as a key in a template turns the page into a
  500, but only while the key is missing from the catalogue, so it depends on DB state.
- `Translation::IsAValidLanguage()` uses loose `in_array()`.

**Fix direction:** `getValidLanguages($domain)`; `mb_strlen()`; log and return the id instead of throwing.

## [ ] B-124 – Translator DB catalogues never refresh in long-running processes (Messenger workers, worker runtimes)

**File:** `lib/Translation/Translator.php:138-212`

- `lazyInitialize()` adds the DB catalogue to the decorated translator's in-memory catalogue once per process and
  remembers it in `$initializedCatalogues`. `resetCache()` / `resetInitialization()` exist, but nothing calls them, and
  the class does not implement `ResetInterface`.
- After an editor changes a translation, the persistent cache is cleared (`clearDependentCache()`), but a Messenger
  worker (mails, PDFs, exports rendered in the worker) or a FrankenPHP/RoadRunner worker keeps the old texts until it
  is restarted. Keys added to the catalogue at runtime (`:271`, `:290`) also stay.
- Same pattern as C-15 (website settings) and CoreBundle C-08.

**Fix direction:** implement `ResetInterface` (tag `kernel.reset`) and reset the in-memory catalogues. Messenger
already calls `kernel.reset` between messages via `ResetServicesListener`.

## [ ] B-125 – Event recursion guard stays set after a listener exception: later saves of the same instance skip events silently

**File:** `lib/Event/Traits/RecursionBlockingEventDispatchHelperTrait.php:34-42`; `models/Translation.php:354-373`

- `dispatchEvent()` sets `$activeDispatchingEvents[$name] = true`, dispatches, then unsets it, with no `try/finally`. If
  a `PRE_SAVE` listener throws, the flag stays set on the object.
- Translations are shared through `RuntimeCache` (B-107). A caller that catches the exception and saves again (import
  loop, retry) saves **without** `PRE_SAVE` (validation, normalisation listeners) and `POST_SAVE` events, without any
  error.
- The flag is a model property, so it is also stored in the persistent listing cache (B-117).

**Fix direction:** `try { … } finally { unset(...) }` in the trait (shared helper, also affects other models that use it).

---

## Site (Scope 5)

## [ ] B-126 – `Site::$currentSite` cannot be unset: CLI / Messenger rendering keeps the first document's site for every later document

**Files:** `models/Site.php:38`, `:166-193`; `lib/Document/Renderer/DocumentRenderer.php:95-103`;
`models/Document.php:696-764`, `:812-822`

- `setCurrentSite(Site $site)` accepts no `null` and there is no reset method. Once set, `isSiteRequest()` is `true`
  for the rest of the process.
- Outside an HTTP request `DocumentRenderer` calls `Site::setCurrentSite($site)` for the rendered document's site and
  never restores the previous value. A Messenger worker or CLI command that renders documents of several sites (mails,
  PDFs, static page generation, sitemaps) renders the **second document with the first document's site**:
  `Document::getPath()` strips the wrong root, `getFullPath()` builds links with the wrong main domain, and a document
  from the main tree (no site) still gets site paths.
- `MessengerClearRuntimeCacheListener` clears `RuntimeCache` only, not this static (also listed in CoreBundle TODO-2).
- HTTP worker runtimes: CoreBundle **C-08** (not repeated).

**Fix direction:** add `Site::resetCurrentSite()` (or make `setCurrentSite(?Site)`), restore the previous site in
`DocumentRenderer` with `try/finally`, reset it on `kernel.reset`. Long term, replace the static with the
`SiteResolver` / request attribute (I-68).

## [ ] B-127 – `Site::getBy()` throws `TypeError` for a numeric string

**File:** `models/Site.php:139-152`

- `Site.php` is `strict_types`. `is_numeric('5')` is `true`, then `self::getById('5')` is called with a string for an
  `int` parameter → `TypeError`. Same for `5.0`. Route parameters (`StaticRoutesBundle Router.php:120`, `url()` helper
  with `site: '3'`) and request values are strings, so the documented "id or domain" use crashes.
- Any other type (array, `null`, a `Document`) silently returns `null`.

**Fix direction:** type the parameter as `int|string|Site`, cast with `(int)` after an `ctype_digit()` check, and throw
`InvalidArgumentException` for unsupported values.

## [ ] B-128 – Domains are stored un-normalised: IDN domains never match, uppercase causes redirect loops, wildcards are case-sensitive

**Files:** `models/Site.php:228-251`, `:322-328`; `models/Site/Dao.php:60-95`;
`bundles/CoreBundle/src/EventListener/Frontend/RoutingListener.php:160` (see CoreBundle **B-41**)

- `setDomains()` / `setMainDomain()` validate with `idn_to_ascii()` but store the **original** value: no `trim()`, no
  lower-casing, no punycode conversion.
- `Request::getHost()` is lower-case and, for IDN hosts, punycode (`xn--bcher-kva.de`). A site configured as
  `bücher.de` is **never found** by `getByDomain()`.
- `mainDomain = ?` / `LIKE` use the case-insensitive table collation, but the wildcard check (`Dao.php:85`) uses
  `preg_match()` without `i`: `*.Example.com` never matches. Exact and wildcard matching behave differently.
- `RoutingListener` compares `getMainDomain() != getHost()` case-sensitively: a main domain `Example.com` with
  "redirect to main domain" gives an **endless 301 loop** (the browser lower-cases the host). The root cause is the
  missing normalisation here; CoreBundle B-41 covers the listener side.
- `getByDomain()` callers that do not go through `getHost()` (e.g. `Tool::getHostname()`, redirects controller) produce
  separate cache entries for `Example.com` and `example.com`.

**Fix direction:** normalise once in the setters: `trim()`, `mb_strtolower()`, `idn_to_ascii()` (store ASCII, show
Unicode in the UI); remove empty and duplicate entries with `array_values(array_unique())`. Normalise the lookup
input in `getByDomain()` the same way. Migrate existing rows.

## [ ] B-129 – Loading a site validates and unserializes stored data: one legacy or corrupt row throws on every load

**Files:** `models/Site.php:228-251`, `:307-315`; `lib/Model/AbstractModel.php:163-190`; `lib/Tool/Serialize.php:33-40`;
`lib/Tool/Frontend.php:70-95`

- DB hydration calls the same `setDomains()` that validates input. A domain stored before validation existed, or one
  that `FILTER_FLAG_HOSTNAME` rejects (an underscore in a label such as `my_shop.example.com`, a label longer than 63
  characters, a value with a port), throws `InvalidArgumentException` **while reading**. `getById()` / `getByDomain()`
  only catch `NotFoundException`, so the routing listener answers **500 on every request for that host**, and
  `Frontend::getSiteMapping()` (which loads **all** sites) fails for every site that needs the mapping.
- `Serialize::unserialize()` on a string that is not valid serialized data returns `false` with an `E_WARNING`:
  - `setDomains()` turns that into `[]` silently: the site loses all its domains without any error;
  - `setLocalizedErrorDocuments()` assigns `false` (or `''` for an empty string) to the `array` property →
    **`TypeError`** on load.
- Both setters also accept the string form from `setValues()` / `Site::create()` callers. A plain domain string
  (`'example.com'`) is not serialized data → warning + domains silently cleared.

**Fix direction:** separate hydration from input validation (hydrate through a dedicated `fromDbRow()` / DAO mapping
without validation; validate in `save()`, I-69). Decode defensively: `unserialize($v, ['allowed_classes' => false])`,
check `is_array()`, log and fall back to `[]`. Remove the string branch from the public setters (or accept only
arrays and a list of domains).

## [ ] B-130 – `getByDomain()` matching: unescaped `LIKE` wildcards, empty host matches, arbitrary winner for duplicates and overlapping wildcards

**File:** `models/Site/Dao.php:60-95`

- `domains LIKE '%"' . $domain . '"%'` does not escape `_` / `%`. `Request::getHost()` allows `_`, so a host
  `exampl_.com` resolves to the site of `example.com` (and is cached under its own key). Harmless for routing but wrong,
  and it makes B-131 cheaper.
- An empty domain (`''`, e.g. `Tool::getHostname()` without a configured system domain, `PublicServicesController`)
  matches `mainDomain = ''`. `Dao::update()` writes `''` (not `NULL`) for every site without a main domain, so an
  **arbitrary site** is returned.
- Neither query has `ORDER BY`. Nothing prevents two sites from claiming the same domain (no uniqueness check, no
  unique index), so the winner depends on the storage order.
- Wildcards: `$wildcardDomains[$siteDomain] = $site['id']` overwrites duplicates, and the match loop does not `break`,
  so the **last** matching pattern wins, not the most specific one (`*.example.com` vs `shop.*.example.com`).
- `strpos($site['domains'], '*')` is used as a boolean; it works only because a serialized string never starts with
  `*`.

**Fix direction:** reject empty input; escape with `addcslashes($domain, '%_\\')` or (better) match on a normalised
domain table (I-65); enforce unique domains across sites in `save()` (I-69); sort wildcard candidates by specificity
(longest literal part first) and stop at the first match.

## [ ] B-131 – Every unknown `Host` header writes a permanent cache entry and scans the whole `sites` table

**Files:** `models/Site.php:109-134`; `models/Site/Dao.php:60-95`;
`bundles/CoreBundle/src/EventListener/Frontend/RoutingListener.php:111` (see CoreBundle **B-67**)

- `RoutingListener` calls `getByDomain($request->getHost())` on **every** frontend request. For a host without a site
  the DAO runs the `LIKE '%…%'` query (full scan), then loads **all** sites and unserializes their domains, and
  `Site::getByDomain()` stores `'failed'` under `site_domain_<md5(host)>` **without lifetime** and with priority 999.
- Without `framework.trusted_hosts`, any client can send arbitrary hosts (scanners do): one DB scan and one permanent
  cache item per distinct value. On Redis this grows until eviction, which then evicts useful entries. CoreBundle B-67
  mentions this for `robots.txt`; the cause is here and it affects all frontend URLs.

**Fix direction:** resolve against one cached domain → site map (all sites, one entry, I-66/I-67) and do not cache
misses per host; or cache misses only in `RuntimeCache` / with a short lifetime. Document `trusted_hosts` as required
for multi-site setups.

## [ ] B-132 – Runtime caches are never updated on `save()` / `delete()`: deleted sites are returned, new sites are `null`, listings contain `null`

**Files:** `models/Site.php:68-92`, `:109-134`, `:348-356`; `models/Site/Listing/Dao.php:33-45`;
`lib/Tool/Frontend.php:70-95`

- `clearDependentCache()` clears the persistent `site` tag only. `RuntimeCache` keys `site_id_<id>`,
  `site_domain_<md5>` and `sites_path_mapping` keep their values for the rest of the process (request, CLI command,
  Messenger message; in worker runtimes even longer, CoreBundle C-08).
- After `delete()`, `getById()` / `getByDomain()` return the deleted object. A lookup **before** creating a site caches
  `null` under `site_id_<id>`; after the site is created with that id, `getById()` still returns `null`.
- `Listing\Dao::load()` maps ids through `Site::getById()` and appends the result without a check, so the list can
  contain `null`. `Frontend::getSiteMapping()` then calls `$site->getRootPath()` on `null` → `Error`.
- All callers get the **same mutable instance** from `RuntimeCache`; a caller that changes it (e.g. `setRootPath()`
  for a preview) changes it for everyone.

**Fix direction:** in `save()` / `delete()` remove the runtime entries for the id, all domain keys and the path mapping
(or use one registry service that is reset as a whole, I-67). Skip (and log) `null` results in `Listing\Dao::load()`.

## [ ] B-133 – `Dao::save()` is not atomic and does not validate: half-created rows, raw DBAL errors, "saved" sites that do not exist

**Files:** `models/Site/Dao.php:97-147`; `models/Site.php:388-397`

- A new site is written as `INSERT (rootId)` followed by `UPDATE` with all columns, without a transaction. If the
  update fails (serialization error, connection loss), a row with only `rootId` stays, and `rootId` is `UNIQUE`: the
  next attempt fails with a duplicate-key error while the half row has no domains.
- There is no validation before the write: `rootId` may be `null` (a site without root, B-134), point to a non-page
  document or to a page that is already a site root (raw `UniqueConstraintViolationException` instead of a domain
  error), domains may be used by another site (B-130).
- `update()` ignores the affected row count: saving a `Site` object whose row was deleted in the meantime does nothing,
  but `POST_SAVE` fires and callers assume success.
- `PRE_SAVE` runs before any validation, `POST_SAVE` after the cache clear; there is no `PRE_ADD` / `POST_ADD`, so
  listeners cannot tell a new site from an update.

**Fix direction:** one `INSERT` with all columns for new sites (or a transaction around both statements); validate in
`Site::save()` before `PRE_SAVE` (I-69); throw `NotFoundException` when `update()` affects 0 rows and the row does not
exist.

## [ ] B-134 – A site whose root is missing or not a page: the domain serves the main tree, and site path code fatals

**Files:** `models/Site.php:205-213`, `:256-273`, `:285-292`;
`bundles/CoreBundle/src/EventListener/Frontend/RoutingListener.php:118-124`; `models/Document.php:698-704`, `:744`,
`:757-764`

- `setRootId()` uses `Document\Page::getById()`, which returns `null` when the document is not a `Page` (converted
  to a link/snippet/hardlink, or root id `null`). `rootDocument` is then `null` without any error, and
  `getRootPath()` returns `null`.
- `RoutingListener` builds `$site->getRootPath() . $path` = `$path`: requests for the site's domains are resolved
  against the **main document tree**, i.e. the site serves the wrong content without any error.
- `Document::getFullPath()` calls `$site->getRootDocument()->getId()` without a null check (`:701`, `:760`, also
  `:744`). That is an `Error`, not an `Exception`, so the surrounding `catch (Exception)` does not catch it → 500 when
  rendering links.

**Fix direction:** validate the root on save (B-133); on load, treat a site without a valid root page as disabled
(log it, `getByDomain()` returns `null`); null-safe access in `Document`, or make `getRootDocument()` throw a clear
exception.

## [ ] B-135 – `delete()`: not atomic, leaves references to the deleted site, keeps runtime caches

**Files:** `models/Site.php:399-408`; `models/Site/Dao.php:149-159`;
`bundles/InstallBundle/dump/install.sql:603-615` (`website_settings.siteId`)

- The `sites` row and the URL slugs are deleted in two statements without a transaction. A failure after the first
  leaves slugs for a site id that no longer exists.
- Only URL slugs are cleaned up. `website_settings.siteId` (Scope 2), SeoBundle redirects / robots.txt entries
  (SeoBundle TODO-2 B-27) and static route `siteId` lists keep pointing to the deleted id (static routes at least
  skip unknown ids at runtime, `StaticRoutesBundle/src/Model/Staticroute.php:329`). With no `SiteEvents` listeners in the
  repo, nothing else reacts.
- `delete()` on an unsaved site (`id = null`) matches no row, but still fires both events and clears the cache.
- Runtime caches: B-132. FK-cascade deletes bypass all of this: C-24.

**Fix direction:** transaction around the delete and the cleanup; move the cleanup of dependent data into
`SiteEvents::POST_DELETE` listeners in the owning bundles (or document it); throw when `id` is `null`.

## [ ] B-136 – Error documents are stored as unvalidated paths: renaming or deleting the error page silently breaks error handling

**Files:** `models/Site.php:294-320`; `bundles/CoreBundle/src/EventListener/ResponseExceptionListener.php:163-185`

- `errorDocument` and `localizedErrorDocuments` are free strings (paths). Nothing checks that the document exists, and
  they are not part of the dependency graph, so the error page shows as "not used" and can be deleted, and a rename of
  the page or of an ancestor folder (path change) breaks it without warning. The visitor then gets the generic error
  output.
- `setLocalizedErrorDocuments()` accepts any array shape (non-string values, non-locale keys).
- A site with an empty error document replaces the system default error page with `''` instead of falling back to
  it (`ResponseExceptionListener:165-170`).

**Fix direction:** store document ids (or resolve paths to ids on save and track them as dependencies); validate keys
against the valid languages and values as existing pages; fall back to the system error page when the site value is
empty.

## [ ] B-137 – Small defects: `getByRootId()` error message, `getTotalCount()` swallows errors, `getRootPath()` override

**Files:** `models/Site.php:94-104`, `:278-292`; `models/Site/Dao.php:47-53`; `models/Site/Listing/Dao.php:47-54`

- `Dao::getByRootId()` reports "Unable to load site with ID `<rootId>`": the value is a document id, which misleads
  debugging.
- `Listing\Dao::getTotalCount()` catches every `Exception` and returns `0`. A broken condition (SQL error) looks like
  "no sites", while `load()` with the same condition throws.
- `setRootPath()` sets a value that overrides the root document's path in `getRootPath()`, is never persisted and has
  no caller in this repo. On the shared cached instance (B-132) it changes the routing prefix for everyone.

**Fix direction:** fix the message; let `getTotalCount()` throw (or log at error level); deprecate `setRootPath()`
(I-70).


## Document (Scope 6)

## [ ] B-138 – `Document::save()` after a move/rename clears the wrong runtime-cache path keys

**Files:** `models/Document.php:328-341`, `:368-378`; `models/Document/Dao.php:196-199`

- `updateChildPaths()` selects `CONCAT(path, key)` **before** the UPDATE, i.e. the old paths. `save()` treats them as
  new paths: `substr_replace($doc['path'], $oldPath, 0, strlen($newPath))`. When old and new path differ in length
  (`/a` → `/abc`), the computed "old path" is garbage, so `document_path_<md5(old)>` runtime entries survive and
  `Document::getByPath('/a/x')` keeps returning the moved document in the same process (CLI imports, workers).
- The moved document's own old path key is never cleared either.
- The SELECT uses `LIKE '<old>%'` without `/`: siblings such as `/ab…` are included (needless flush, wrong old path).

**Fix direction:** select `LIKE '<old>/%'`, use the selected value as the old path and compute the new one; clear the
document's own old path key.

## [ ] B-139 – Recursive delete fires the children's `POST_DELETE` and side effects before the parent's transaction commits

**Files:** `models/Document.php:621-690`; `models/DataObject/AbstractObject.php:429-492`;
`models/Document/PageSnippet.php:220-231`; `models/DataObject/Concrete.php:216-226`

- Every child `delete()` opens a nested transaction (only the nesting level changes), then clears caches and the
  RuntimeCache, dispatches `POST_DELETE` and a `VersionDeleteMessage`. When a later sibling or the parent fails, the
  outer `rollBack()` restores the rows, but listeners (search index, Data Hub, workflows, caches) already treated the
  children as deleted, and versions are gone if the message transport is not the same DB connection (see B-17).
- `Document::doDelete()` switches `hideUnpublished` off and back without `finally` (`:626-633`).
- The whole subtree is loaded into memory through recursive `getChildren(true)`.

**Fix direction:** collect the subtree ids, delete in one transaction, dispatch events / messages after commit
(DBAL after-commit callback or `DispatchAfterCurrentBusStamp`); `finally` for the flag.

## [ ] B-140 – `missingRequiredEditable` is never re-evaluated once it is `true`

**Files:** `models/Document/PageSnippet.php:126-136`, `:581-594`, `:610-644`; column
`documents_page.missingRequiredEditable`

- The check runs only while the property is `null`. The value is persisted and loads as `true`, so after the editable
  has been filled `$page->setPublished(true)->save()` still throws "Prevented publishing document" unless the caller
  resets the flag (only `Document\Service::copyAsChild()` sets `false`). Publishing from code, imports and workflows
  stays blocked.
- The check renders a clone of the document on every save (controller, Twig, sub-requests), also in CLI / Messenger,
  and catches only `Exception` – a Twig `\Error` makes the save fail.

**Fix direction:** reset to `null` (recompute) before every publish check, or compute on demand; catch `\Throwable`.

## [ ] B-141 – `getEditables()` with inherited values stores the content-main editables as the document's own

**Files:** `models/Document/PageSnippet.php:462-477`, `:138-157`

- With `PageSnippet::$getInheritedValues = true` (dependency handler, rendering helpers) the main document's editable
  **instances** (not cloned, `inherited = false`) are merged into `$this->editables` and stay on the cached instance
  after the flag is reset.
- A later `save()` saves them as own editables and calls `setDocumentId(<child>)` on the main document's objects →
  content inheritance is flattened silently, and the main document's in-memory editables now point to the child.

**Fix direction:** keep inherited editables out of `$this->editables`; use clones flagged `inherited` (as
`getEditable()` does).

## [ ] B-142 – Content-main cycles are accepted: the chain is only checked with `$validate = true`

**Files:** `models/Document/PageSnippet.php:393-424`, `:359-385`, `:431-438`

- The loop condition ends with `&& $validate`; the default (`false`, used by `setValues()` from the admin UI) only
  checks the direct self-reference. `A → B`, `B → A` is accepted and `getEditable()` recurses A → B → A … → stack
  overflow on render. Chains longer than 20 are accepted silently.

**Fix direction:** always walk the full chain; guard `getEditable()` with a visited set.

## [ ] B-143 – One unknown or corrupt editable row breaks the whole document

**Files:** `models/Document/PageSnippet/Dao.php:45-63`; `models/Document/Editable.php:713-728`

- `EditableLoader::build()` throws for a type that no longer exists (bundle removed), and `getUnserializedData()`
  throws for a corrupt / legacy serialized value. `getEditables()` then fails for the whole document: frontend 500,
  the document cannot be opened in the admin to fix it, versions and the recycle bin fail as well.

**Fix direction:** skip unknown / broken rows with a warning (keep the raw row so a re-save does not lose it).

## [ ] B-144 – `Document\Service\Dao::getTranslations()` loses links and can fatal

**Files:** `models/Document/Service/Dao.php:41-88`

- In the `'open'` task `$linkedData` is reassigned per loop iteration (`:61-64`): only the last linked set is merged.
- `Document::getById()` can return `null` (source removed, race) → `->getProperty()` on `null`; a missing `language`
  property produces an `''` key. `getTranslationSourceId()` returns `mixed` (a DB string).

**Fix direction:** accumulate `$linkedData`, null checks, `int` casts.

## [ ] B-145 – `Document\Link::getHtml()`: parameters and anchor twice, no escaping, `TypeError` without `navigation_name`

**File:** `models/Document/Link.php:177-192`, `:310-334`

- `getLink()` already appends `?<parameters>#<anchor>`; `getHtml()` appends both again (`:322`) → `/x?a=1#top` becomes
  `/x?a=1#topa=1top`.
- `href` and the `navigation_*` attribute values are concatenated without escaping (markup breaks on `"`, stored XSS
  by editors).
- `htmlspecialchars($this->getProperty('navigation_name'))` with a missing property → `TypeError` (strict).

**Fix direction:** remove the second append, escape `href` and attribute values, cast to string.

## [ ] B-146 – `Document\Link`: object targets are neither dependencies nor cache tags; a lookup error wipes the target

**Files:** `models/Document/Link.php:81-115`, `:259-304`; `models/Document/Link/Dao.php:34-52`

- `resolveDependencies()` and `getCacheTags()` handle documents and assets only. Links to data objects are invisible
  in the dependency graph (the object can be deleted as unused) and the link-generator href is not invalidated when
  the object changes.
- `setObjectFromId()` catches every `Exception` and sets `internalType = ''`, `internal = null`; the next save
  persists the empty link.
- `Dao::getById()` calls `getHref()` on every load (target load and link generator for a plain document load).

**Fix direction:** add objects; never change persistent fields on read errors; compute the href lazily.

## [ ] B-147 – Hardlinks: no source validation, `null` dereferences after the source is deleted, custom types crash

**Files:** `models/Document/Hardlink.php:51-58`, `:102-107`, `:156-184`; `models/Document/Hardlink/Service.php:29-74`;
`models/Document/Hardlink/Wrapper.php:108-128`; `models/Document/Hardlink/Wrapper/Link.php:30-60`

- `sourceId` may be the hardlink itself, one of its ancestors, or a hardlink pointing back → with
  `childrenFromSource` navigation and sitemaps recurse without end.
- `Wrapper::getProperty()` and `Wrapper\Link::getHref()` call
  `getHardLinkSource()->getSourceDocument()->getRealFullPath()` without a `null` check → `Error` once the source was
  deleted (frontend 500).
- `upperCastDocument()` builds `Wrapper\<Type>` from `getType()`. Custom document types (`type_definitions`) have no
  wrapper → `__PHP_Incomplete_Class` → `TypeError`.
- `Hardlink::hasChildren()` loads and wraps every source child just to count them.

**Fix direction:** validate the source on save (not self, not an ancestor, no hardlink cycle); null-safe wrappers; a
configurable wrapper map with a generic fallback.

## [ ] B-148 – Document type DAOs: a missing type row overwrites `id` with `NULL`

**Files:** `models/Document/Page/Dao.php:35-52`, `Snippet/Dao.php:34-51`, `Email/Dao.php:35-52`, `Link/Dao.php:34-52`,
`Hardlink/Dao.php:34-51`

- `SELECT documents.*, documents_<type>.* … LEFT JOIN`: without a type row the second `id` is `NULL` and wins in
  `fetchAssociative()` → the model has `id = null`. `save()` then tries an INSERT and fails with
  `DuplicateFullPathException`; documents imported or converted without the type row cannot be saved.

**Fix direction:** list the type columns explicitly (without `id`) or assign `documents.id` last.

## [ ] B-149 – Document / object keys: `null` key gives a `TypeError`, the auto-key path is unreachable

**Files:** `models/Document.php:415-443`, `:475-478`; `models/DataObject/AbstractObject.php:644-671`;
`models/Document/Dao.php:82-97`; `models/DataObject/AbstractObject/Dao.php:73-84`;
`models/Element/Service.php:972-975`

- `correctPath()` calls `isValidKey($this->getKey())` with a `?string` → `TypeError` for a new element without key
  instead of the descriptive exception.
- A document with key `''` is rejected ("Document requires key, generated key automatically") before
  `Dao::create()` could set `key = id` → dead code and a misleading message.
- The first-level key check (`install`, `admin`, `plugin`) is case-sensitive (`Admin` passes).

**Fix direction:** explicit `null` check and message; decide whether auto keys are supported; compare
case-insensitively.

## [ ] B-150 – `Element\Service::createFolderByPath()` returns non-folders and does not survive concurrent creation

**File:** `models/Element/Service.php:820-894`

- If the full path exists, `$type::getByPath()` is returned: a `Page` (the document root `/` is a page), a `Concrete`
  object or an image → `TypeError` on the `Asset\Folder|DataObject\Folder|Document\Folder|null` return type.
  Non-folder elements in the middle of the path are used as parents silently.
- Two workers importing into the same path → `DuplicateFullPathException` / unique violation instead of returning the
  folder.
- `userOwner` is hard-coded to `1`, `userModification` to `0`; `$options` are applied after parent and path and can
  override them.

**Fix direction:** throw a clear exception for non-folders (or widen the return type); catch the duplicate and reload.

## [ ] B-151 – `Element\Service::updateChildren()` resolves the copy by subtype: `null` ends up in the target's children

**File:** `models/Element/Service.php:569-586`; callers `models/Document/Service.php:138`, `:202`,
`models/DataObject/Service.php:173`, `:198`

- `getElementById($new->getType(), …)` passes `page`, `snippet`, `folder`, `variant`, `image` … instead of the element
  type → `null` for everything except objects of type `object`. The target's cached children listing gets a `null`
  entry; iterating `$target->getChildren()` later in the same request (recursive copy, navigation) hits `null`.

**Fix direction:** append `$new` directly or use `getElementType($new)`.

## [ ] B-152 – `Element\Service::correctPath()` URL-decodes stored keys: object paths with `%xx` resolve to another element

**File:** `models/Element/Service.php:772-787`

- Object keys may contain `%` (`getValidKey()` only strips `<>` for objects). `/shop/50%25` is decoded to `/shop/50%`
  and `/a%20b` to `/a b` → `getByPath()` / `pathExists()` find a different object or none; `createFolderByPath()`
  creates duplicates. `rtrim($path, '/ ')` also strips trailing spaces of keys.

**Fix direction:** decode only in request routing, not in model lookups.

## [ ] B-153 – Pretty URLs are not validated: duplicates and collisions resolve arbitrarily

**Files:** `models/Document/Page.php:96-108`; `models/Document/Dao.php:61-80`; `models/Document/Service/Dao.php:31-39`

- No uniqueness check across pages or against real paths. `Dao::getByPath()` falls back to
  `SELECT id FROM documents_page WHERE prettyUrl = ?` with no order and no site restriction → an arbitrary page is
  served. `prettyUrl` has no index, so every unknown path scans `documents_page`.

**Fix direction:** validate on save (unique per site, no clash with real paths); add an index.

## [ ] B-154 – Document tree counts and listings: published flag and unqualified column

**Files:** `models/Document/Dao.php:329-389`; `models/Document/Listing.php:85-98`

- `getChildAmount()` ignores `published` / `hideUnpublished`, `hasChildren()` applies it → child counts and "has
  children" disagree.
- `Listing::getCondition()` appends an unqualified `published = 1`; with custom-view joins
  (`Element\Service::addTreeFilterJoins()`) to a table that also has `published` the query fails (ambiguous column).

**Fix direction:** same filter in both methods; `documents.published`.

## [ ] B-155 – `DocType::create()` persists an invalid definition; lookups hide errors

**Files:** `models/Document/DocType.php:95-122`; `models/Document/DocType/Dao.php:52-95`

- `create()` saves immediately, without `name` / `type` (typed and uninitialised, skipped by `getObjectVars()`) →
  later `getName()` / `getType()` throw `Error`.
- `getById()` swallows every `Exception`; the not-found message prints `$this->model->getId()` (still `null`).

**Fix direction:** validate in `save()`, return an unsaved instance from `create()`, catch only `NotFoundException`.

## Document editables (Scope 6)

## [ ] B-156 – Scheduled block: wrong index in the frontend, stale active entry in long-running processes

**File:** `models/Document/Editable/Scheduledblock.php:43-56`, `:70-104`, `:171-212`, `:239-243`

- `getCurrentIndex()` and `blockStart()` read `$this->indices[...]` (unfiltered) while `blockConstruct()` pushes the
  key of the filtered (active) element → in the frontend the iterator yields the first entry's key, not the active one.
- `cachedCurrentElement` is reset only in `__wakeup()`: an instance kept in RuntimeCache (workers, CLI rendering)
  never switches to the next scheduled entry.
- `setDataFromEditmode()` assigns unvalidated data and `usort()`s it (`TypeError` for non-arrays).

**Fix direction:** iterate the filtered list; reset the cache per render; validate input.

## [ ] B-157 – Areablock: brick limits are counted across renders; positional and pointer-based iteration are mixed

**File:** `models/Document/Editable/Areablock.php:130-182`, `:211-234`, `:254-271`, `:583-586`, `:611-626`

- `brickTypeUsageCounter` is never reset (`end()` resets only `current`). Rendering the same instance a second time
  (template prints it twice, instance kept in RuntimeCache, clones keep the counter) disables bricks above
  `config.limits`.
- `loop()` advances with `current()` / `next()`, but `blockConstruct()` and `blockStart()` use
  `$this->indices[$this->current]`. Non-list indices (editmode JSON object, removed entries) → undefined offset →
  `pushIndex(null)` `TypeError`.
- `setDataFromEditmode()` accepts anything (brick type not checked against `allowed`, keys not unique);
  `getElement()` passes a possibly `null` document to `Areablock\Item` (`TypeError`).

**Fix direction:** reset the counter in `start()`; `array_values()` the indices; validate editmode data.

## [ ] B-158 – Block-type editables leave `BlockStateStack` unbalanced when a child throws

**Files:** `models/Document/Editable/Block.php:105-145`, `:197-221`; `Areablock.php:304-347`; `Area.php:147-209`;
`Scheduledblock.php:124-169`

- `pushBlock()` / `pushIndex()` have no `try/finally`. `Block::getIterator()` in editmode opens `ob_start()` and
  stashes the editable collector without restoring either on exception → every following editable gets a wrong name
  (and is saved under that name) and the output buffer leaks. Model side of CoreBundle **B-43**.

**Fix direction:** `try/finally` around every push/pop pair and around `ob_start()` / `stashPush()`.

## [ ] B-159 – `Editable\Link::frontend()` mutates its config, emits internal data as attributes, does not escape `href`

**File:** `models/Document/Editable/Link.php:70-127`, `:129-164`

- `textPrefix`, `textSuffix`, `noText`, `disabledFields` are `unset()` from `$this->config`; a second render of the
  same instance loses them.
- Every string/numeric entry of `$this->data` becomes an attribute: `path`, `text`, `linktype`, `internalType`,
  `internalId`, `parameters`, `anchor` … → invalid markup and leaked internal ids (the allowlist was removed upstream,
  #16025, without a replacement filter).
- `href="' . $url . '"`: the path / direct URL is not escaped (only parameters and anchor are).
- `checkValidity()` logs the document id as the missing target id (`:138`, `:147`, `:156`).

**Fix direction:** work on a local copy of the config; skip the internal keys (or allowlist + `data-*` / `aria-*`);
`htmlspecialchars()` the href.

## [ ] B-160 – Relation / Snippet editables keep the old target after `rewriteIds()`; Snippet skips lazy loading

**Files:** `models/Document/Editable/Relation.php:273-278`; `models/Document/Editable/Snippet.php:69-79`, `:180-194`,
`:217-223`

- `rewriteIds()` changes the id but keeps `$this->element` / `$this->snippet` → after a copy with inheritance the new
  document renders and depends on the source's target until reloaded (`Renderlet` resets via `setO(null)`).
- `Snippet::resolveDependencies()` and `getDataEditmode()` do not call `load()`: on an instance from the persistent
  cache (`snippet` is excluded in `__sleep()`) the dependency is missing.

**Fix direction:** reset the cached element in `rewriteIds()`; call `load()` first.

## [ ] B-161 – `Relations` iterator lets unpublished elements through

**File:** `models/Document/Editable/Relations.php:50-63`, `:248-262`

- `valid()` skips at most one unpublished element; with two consecutive unpublished targets the second is returned in
  the frontend.
- `setElements()` re-queries on every call when all targets are missing (`empty()` check).

**Fix direction:** loop until a published element or the end; use a `null` "not loaded" sentinel.

## [ ] B-162 – Image editable: hotspot / marker element links never become dependencies or cache tags

**File:** `models/Document/Editable/Image.php:460-539` (`:477-486`, `:519-529`)

- The metadata entries are `MarkerHotspotItem` objects that store the **id** (`offsetSet()`). `getCacheTags()` and
  `resolveDependencies()` convert them with `get_object_vars()`, so `value` is an int, never an `ElementInterface` →
  no dependency rows (linked assets, documents and objects look unused and can be deleted) and no cache tags.

**Fix direction:** read through `$metaData['value']` (`offsetGet()` resolves the element) or resolve by type/id.

## [ ] B-163 – Editables assign unnormalised values to typed properties: `TypeError` while loading or saving

**Files:** `models/Document/Editable/Image.php:332-344`; `models/Element/Data/MarkerHotspotItem.php:31-70`;
`Snippet.php:159-167`; `Renderlet.php:204-214`; `Relation.php:120-129`; `Date.php` (`setDataFromEditmode()`,
`setDateFromTimestamp()` `:120-124`); `Input.php` / `Textarea.php` (`setDataFromEditmode()`, `frontend()`);
`Video.php:343-378`; `Embed.php`, `Pdf.php` (`setDataFromEditmode()`)

- `Image::setData()`: legacy rows with `cropPercent` `0/1`, string crop values or a string id → `TypeError` on the
  `bool` / `float` / `?int` properties → the whole document cannot load (B-143).
- `MarkerHotspotItem::setName()` / `setType()` with `null` → `TypeError`.
- Snippet / Renderlet / Relation assign editmode ids as strings to `?int`.
- `Date::setDataFromEditmode()`: `strtotime()` returns `false` → `setDateFromTimestamp(int)` `TypeError`.
- `Input` / `Textarea`: `html_entity_decode(null)`; `Textarea::frontend()` calls `htmlspecialchars(null)` for a new
  empty editable → `TypeError`, swallowed by `__toString()` and logged as an error on every render.
- `Video`: `Asset::getByPath($data['poster'])` with a missing key → `TypeError`; `Embed` / `Pdf` read
  `$data['url']` / `$data['id']` unchecked.

**Fix direction:** normalise in the setters (`(int)` / `(float)` / `(bool)` after validation), null-safe input handling.

## [ ] B-164 – Editor-supplied values are output without escaping

**Files:** `models/Document/Editable/Video.php:537-566`, `:640-663`, `:750-812`, `:822-924`; `Numeric.php`,
`Select.php`, `Multiselect.php`, `Table.php`, `Pdf.php` (`frontend()`)

- Video: a YouTube "id" that is not a URL is used verbatim in the iframe `src`; Dailymotion ids match `[^_]+`;
  attributes are emitted as `key="value"` without escaping; the JSON-LD block is `json_encode()`d without
  `JSON_HEX_TAG`, so a title / description containing `</script>` breaks out.
- Numeric / Select / Multiselect: values are not validated (numeric, allowed options) in `setDataFromEditmode()` and
  are returned raw.
- Table: cells are output raw (document it as HTML or escape).
- Editors are semi-trusted, but this is stored XSS on public pages.

**Fix direction:** escape attribute/URL/JSON output; validate against the configured options / numeric format.

## Element (Scope 6)

## [ ] B-165 – Properties of unsaved elements are cached under a shared key

**Files:** `models/Element/AbstractElement.php:229-248`; `models/Element/Service.php:1416-1423`

- With `id = null` the cache key is `document_properties_` / `object_properties_` / `asset_properties_`. The first
  unsaved element that reads properties stores its parent's inherited properties; every other unsaved element of that
  type (other parent) gets them until the next save clears the `*_properties` tag (imports that build several
  elements before saving; `setProperty()` triggers the read).

**Fix direction:** do not use the persistent cache when the id is `null`.

## [ ] B-166 – `getUserPermissions()` and `isAllowed()` disagree for admins and workflow denials

**File:** `models/Element/AbstractElement.php:439-515`

- `getUserPermissions()` returns `1` for every permission of an admin without the workflow check; `isAllowed()` denies
  what the workflow denies → the UI offers actions that then fail.
- Without a user: CLI gets all `1`, HTTP all `0` – undocumented.

**Fix direction:** apply `isDeniedInWorkflow()` per permission type in both paths.

## [ ] B-167 – Workspace path matching is not anchored for documents and objects (B-28 extended)

**Files:** `models/Element/Service.php:624-688` (`:642-646`, `:671`); `models/Document/Dao.php:344-346`, `:381-383`;
`models/DataObject/AbstractObject/Dao.php:306-310`, `:405-407`, `:611-632`; `models/Element/Dao.php:81-95`

- `findForbiddenPaths()` uses `str_contains($findPath, $path)`: a forbidden `/foo` treats `/foobar` and `/x/foo` as
  its children (exception list) → tree listings hide or allow the wrong subtrees.
- The role query groups by `cpath` with a non-aggregated `userid` (fails when `sql_mode` is not overridden by the
  default `SET sql_mode = ''`).
- `hasChildren()` / `getChildAmount()` use `LOCATE(CONCAT(path, key), cpath) = 1` and `getChildPermissions()` uses
  `LIKE '<path>%'` without `/` → a workspace on `/foobar` lists `/foo`, children permissions of `/foobar` show up for
  `/foo`.
- `InheritingPermission()` uses `getPath()`, which is site-rewritten for documents in the frontend.

**Fix direction:** compare with `/` appended on both sides (`str_starts_with($findPath, $path . '/')`); use
`getRealPath()`.

## [ ] B-168 – Recycle bin: global state leaks, permission check after side effects, swallowed errors

**Files:** `models/Element/Recyclebin/Item.php:87-148`, `:150-189`, `:191-206`, `:208-240`;
`models/Element/Recyclebin/Item/Dao.php:51-63`; `Item/Listing/Dao.php:33-45`

- `restore()`: `Version::disable()` / `enable()` (`:112-123`) and the dirty-detection switch (`:134-139`) are not in
  `finally` → a failing dummy save disables versioning for the rest of the process (no versions in workers).
- The `publish` permission check (`:126-131`) runs **after** the dummy object with the deleted id was saved and
  outside the `try` → the empty dummy object stays in the tree.
- Name collisions are handled for the root only and only once (`_restore` may exist too).
- `save()`: `Item\Dao::save()` swallows insert errors and returns `true` → `getStorageFile()` reads the
  uninitialised typed `$id` → `Error`; the DB row is written before the storage file (orphans on storage errors).
- `loadChildren()` sets dump state on the **live** elements; if the delete fails afterwards they stay in dump state.
- `delete()` lists the whole `<type>` storage directory for every item (slow on remote storage, quadratic when many
  items are removed); the listing can contain `null` entries.

**Fix direction:** `finally` blocks, permission check first, propagate DB errors, one storage directory per item.

## [ ] B-169 – Edit locks: several rows per element, unlock removes one, uninitialised `cpath`

**Files:** `models/Element/Editlock.php:47-112`, `:202-205`; `models/Element/Editlock/Dao.php:33-68`;
`bundles/InstallBundle/dump/install.sql:188-198`

- No unique `(cid, ctype)`: `lock()` inserts a new row every time, `getByElement()` returns an arbitrary row,
  `unlock()` deletes one → the element stays locked by a stale row (until the 1 h expiry).
- `isLocked()` + `lock()` is not atomic → two editors can both "own" the lock.
- `getCpath()` throws `Error` when the element no longer exists (typed, uninitialised).

**Fix direction:** unique index + upsert; atomic insert-if-free; nullable `cpath`.

## [ ] B-170 – Notes: `POST_ADD` never fires; element ids are loaded as strings

**Files:** `models/Element/Note.php:102-119`; `models/Element/Note/Dao.php:39-143`

- `if (!$this->getId())` is evaluated after `Dao::save()` has set the id → `NoteEvents::POST_ADD` is never dispatched.
- `notes_data.data` is text: `Document::getById('12')`, `Asset::getById('12')` … trigger the "Passing id as string"
  deprecation for every value.
- `save()` / `delete()` are not atomic (`notes` vs `notes_data`).

**Fix direction:** remember "is new" before saving; `(int)` casts; transaction.

## [ ] B-171 – Tags: moving a tag below itself hangs; batch assignment is not atomic

**Files:** `models/Element/Tag.php:238-245`, `:325-339`; `models/Element/Tag/Dao.php:184-199`

- `correctPath()` walks `getParent()` without a cycle guard: moving a tag below itself or one of its descendants loops
  forever (memory exhaustion).
- `batchAssignTagsToElement()` deletes and inserts without a transaction (N×M upserts).
- Cached `children` of a tag are never invalidated after adding / deleting children.

**Fix direction:** reject a new parent inside the own subtree; transaction + multi-row insert.

## [ ] B-172 – `WorkflowState\Dao::delete()` deletes the states of every workflow on the element

**File:** `models/Element/WorkflowState/Dao.php:58-64`

- The `WHERE` misses `workflow` (part of the primary key) → removing the state of one workflow removes all.

**Fix direction:** add `'workflow' => $this->model->getWorkflow()`.

## [ ] B-173 – `PermissionChecker::check()`: child permissions written to the wrong array, undefined columns, deleted roles

**File:** `models/Element/PermissionChecker.php:117-139`, `:156-171`, `:186-219`

- `$result[$columnName] = …` instead of `$userPermission[$columnName]` → "list allowed through a child workspace"
  is never shown. The query selects only `list` but reads `type`, `name`, `cpath` (undefined → `null` details).
- `User\Role::getById()` returns `null` for a deleted role → `Error`.
- `collectParentIds()` has no loop guard; one query per column per user.

**Fix direction:** write to `$userPermission`, select the needed columns, null-check roles.

## [ ] B-174 – `Element\Service` small defects

**File:** `models/Element/Service.php:94-134`, `:394-428`, `:1392-1411`

- `getTypePath()` / `getSortIndexPath()` call `getElementById($type, null)` for unsaved elements → `TypeError`.
- `getSafeCopyName()` interpolates the file extension unquoted into a regex (`preg_replace('/\.' . $ext . '$/i')`).
- `escapeCsvRecord()` uses `= - + @` only (League's default also covers `\t` and `\r`) and escapes negative numbers;
  `unEscapeCsvRecord()` strips a leading `'` from legitimate values (lossy round trip).

**Fix direction:** null checks, `preg_quote()`, League defaults and a reversible escaping scheme.

## DataObject (Scope 6)

## [ ] B-175 – Global static flags are not restored on exceptions (dirty detection, inherited values, fallback values, `hideUnpublished`, inheritance runtime cache)

**Files:** `models/DataObject/AbstractObject.php:500-507`, `:621`; `models/DataObject/Concrete.php:136-143`;
`models/DataObject/Localizedfield.php:194-205`, `:371-380`; `models/DataObject/Localizedfield/Dao.php:129-141`,
`:436-440`; `models/DataObject/Fieldcollection.php:249-252`; `models/DataObject/Objectbrick.php:286-288`;
`models/DataObject/ClassDefinition/Data/Classificationstore.php:619-621`, `Objectbricks.php:546`,
`Localizedfields.php:661`; `models/DataObject/Service.php:182-221`; generated setters
`models/DataObject/ClassDefinition/Data.php:579-590` (also `:706`, `:825`, `:933`); `models/Document.php:626-633`;
`models/Element/Recyclebin/Item.php:112-139`

- All pairs of `set…(x)` / `set…(previous)` run without `try/finally`:
  - `AbstractObject::save()` disables dirty detection for **new** objects and restores it only on success → after a
    failed create every later save in the process writes all fields and all relation deltas.
  - `Concrete::update()` sets `getInheritedValues = true` for the inherited re-check and restores it only when that
    check passes – i.e. a **validation failure** (the normal reason for this path) leaves it `true`: later reads in
    the process return inherited values where callers expect own values.
  - `Localizedfield\Dao::save()` leaves `getFallbackValues = true` and the InheritanceHelper runtime cache enabled
    with stale trees (B-186) → later saves propagate inherited values with an outdated tree.
  - The generated setters of every class switch `hideUnpublished` off around the dirty check.
- PHP-FPM ends the effect with the request; Messenger workers, CLI imports and worker runtimes keep it.

**Fix direction:** one scoped helper (`DataObject\Service::useInheritedValues()` already does it right) for every
flag, used everywhere, plus a `kernel.reset` reset (I-77). Related: B-92, B-125.

## [ ] B-176 – `Localizedfield::__sleep()` mutates the live object when it is written to the cache

**File:** `models/DataObject/Localizedfield.php:600-619`

- For cache serialization it removes the lazy-loaded values from `$this->items` and unmarks their lazy keys **on the
  live instance**. When the cache handler serializes the object before it is saved (write queue flushed at its size
  limit, immediate-write mode in CLI), in-memory changes of lazy localized relations are dropped, the fields become
  "untouchable" and are not saved; the next read reloads the old values.

**Fix direction:** `__serialize()` that returns a filtered copy and leaves the instance alone.

## [ ] B-177 – Every object load reads the store row with `FOR UPDATE`

**File:** `models/DataObject/Concrete/Dao.php:133-136`

- `SELECT * FROM object_store_<id> WHERE oo_id = ? FOR UPDATE` runs in `getData()` for every load. Inside any
  surrounding transaction (saving A loads related B, workflows, sync dependency handler, imports wrapped in a
  transaction) it row-locks every loaded object → lock waits and deadlocks in parallel imports.

**Fix direction:** a plain `SELECT`; lock explicitly only where needed (the inheritance parent lock is separate).

## [ ] B-178 – Listing locale changes after the first query are ignored

**Files:** `models/DataObject/Listing/Concrete.php:85-106`; `models/DataObject/Listing/Concrete/Dao.php:60-81`,
`:113-158`

- The DAO caches `tableName` (`object_localized_<class>_<lang>`) on first use. `setLocale()` and
  `setIgnoreLocalizedFields()` reset the data but not that cache → `count()` then `setLocale('de')` still queries the
  first language.
- `exceptionHandler()` string-matches driver messages to create missing tables, and only for `loadIdList()`.

**Fix direction:** reset the DAO table name in both setters; create tables in class/language management.

## [ ] B-179 – Localized fields: deferred table creation rolls back the caller's transaction; `delete()` swallows errors

**File:** `models/DataObject/Localizedfield/Dao.php:226-285`, `:447-519`, `:547-583`

- On `TableNotFoundException` the DAO calls `$this->db->rollBack()` itself, runs DDL (implicit commit) and throws a
  `RetryableException`. If the object save runs inside an outer transaction (a listener of a document save, an import
  using `transactional()`), the nested rollback only lowers the nesting level, the DDL commits the outer work, and the
  DBAL nesting level no longer matches MySQL → later `commit()` / `rollBack()` fail ("no active transaction").
- `delete()` catches every other exception and only logs → failed deletes of store / query rows continue into the
  upserts (stale languages remain).
- `ownername LIKE '/fieldcollection~<name>/<index>/%'`: `_` in field names is not escaped.

**Fix direction:** create language tables when languages / classes change, never during a data save; propagate errors;
escape `LIKE` values.

## [ ] B-180 – Field collections: a read error loads an empty collection that the next save persists; DDL inside the save transaction

**File:** `models/DataObject/Fieldcollection/Dao.php:44-60`, `:129-160`

- `load()` turns every DB exception into `$results = []` → the collection is empty in memory; the save path deletes all
  rows of the field and re-inserts the in-memory items → items wiped.
- `delete()` calls `createUpdateTable()` (DDL → implicit commit of the object save) for **any** SELECT error, not only
  a missing table.
- Ids and field names are concatenated into the SELECTs.

**Fix direction:** catch `TableNotFoundException` only; create tables in definition save; bound parameters.

## [ ] B-181 – Classification store save: `Error`s for empty groups and deleted keys

**File:** `models/DataObject/Classificationstore/Dao.php:52-183`, `:185-197`

- `$firstKey = reset($groupKeys)` on a group without keys → `false` → `->getKeyId()` `Error`;
  `DefinitionCache::get($keyId)` returns `null` for a deleted key config → `->getType()` `Error`. Both inside the
  object save transaction (C-26).
- `in_array(array_keys($groupKeys), $alreadySavedKeyIds)` compares an array with ints → always `false`.
- `delete()` ignores `fieldname` (removes the data of every classification store field of the object).

**Fix direction:** skip empty groups / unknown keys with a warning; `array_intersect()`; filter by field name.

## [ ] B-182 – `DataObject\Service::copyContents()` moves the source's scheduled tasks to the target

**Files:** `models/DataObject/Service.php:252-288` (`:279`); `models/Element/Traits/ScheduledTasksTrait.php:52-67`;
`models/Schedule/Task/Dao.php:42-49`

- `setScheduledTasks($source->getScheduledTasks())` shares the `Task` objects (with ids). `saveScheduledTasks()` sets
  `cid = <target>` and saves by id → the tasks now belong to the target and are gone from the source.
- `Element\Service::cloneMe()` deep-copies already loaded tasks with their ids too (copy after the source's tasks were
  read in the same request).

**Fix direction:** clone tasks with `id = null`.

## [ ] B-183 – `getObjectsReferencingUser()` combines user fields with `AND`

**File:** `models/DataObject/Service.php:96-130`

- Objects that reference the user in only one of several user fields are not found → user deletion / reassignment
  checks miss references.

**Fix direction:** `OR`.

## [ ] B-184 – Encrypted field value object loses falsy values and its delegate in serialization

**File:** `models/DataObject/Data/EncryptedField.php:58-110`

- `__sleep()` returns `[]` when `plain` is falsy (`'0'`, `0`, `false`, `[]`) → the value is lost after a cache /
  version round trip. `delegate` is never serialized → uninitialised typed property after wakeup (`getDelegate()`
  `Error`). `_setOwner(null)` is applied to the live value.
- A key problem on wakeup throws "could not load key": a cache read crashes instead of being a miss.

**Fix direction:** `__serialize()` / `__unserialize()` with explicit fields and `!== null` checks.

## [ ] B-185 – Class definition lifecycle: destructive order and swallowed errors

**Files:** `models/DataObject/ClassDefinition.php:217-247`, `:283-291`, `:348-352`, `:488-550`

- `rename()` deletes the generated PHP classes before `save()`; a failing save leaves the class without PHP files →
  every object of the class fatals until the classes are rebuilt.
- `getById()`: `@include` plus broad catches → any error in a definition file (deploy race, OPcache) returns `null`
  and is logged at info level. Callers treat `null` as "class deleted" (C-31, `PimcoreClassDefinitionMatcher`).
- New ids are `MAX(CAST(id AS SIGNED)) + 1` without a lock → concurrent creations get the same id.
- `delete()` loads and deletes every object of the class in one request (memory / time limits), then drops tables.

**Fix direction:** regenerate / rename files after a successful save; distinguish "missing" from "broken"; lock or use
the DB for id generation; batch deletion.

## [ ] B-186 – InheritanceHelper: missing rows and a static runtime cache

**File:** `models/DataObject/Concrete/Dao/InheritanceHelper.php:157-248`, `:255-...`, `:354-430`

- `doUpdate()`: `$result` is `false` when the store row is missing (bricks / localized contexts) → `$result['id']` is
  `null` → `buildTree(null)` `TypeError`; `Concrete::getById($oo_id)` can be `null` → `->getClassId()` `Error`. Both
  inside the save transaction (C-26).
- `static $runtimeCache` is enabled by `Localizedfield\Dao::save()` and not reset on exceptions (B-175) → stale trees
  for later saves (children not updated).
- `doDelete()` is marked "NOT FINISHED - NEEDS TO BE COMPLETED".

**Fix direction:** null checks; scoped cache; finish or remove `doDelete()`.

## [ ] B-187 – DataObject small defects

**Files:** `models/DataObject/AbstractObject.php:229-231`, `:1034-1076`; `models/DataObject/AbstractObject/Dao.php:324-326`,
`:365-367`, `:394-396`; `models/DataObject/Localizedfield.php:709-726`

- `getById()` puts the new object into RuntimeCache **before** `Dao::getById()`; a `NotFoundException` (concurrent
  delete) leaves a half-initialised object for later calls in the same process.
- `__callStatic()` `getBy<Column>()`: `$limit['condition']` is appended without `AND` / parentheses.
- `hasChildren()` / `hasSiblings()` / `getChildAmount()` interpolate `$objectTypes` into `IN ('…')` – public API,
  injectable if callers pass request input.
- `Localizedfield::markLanguageAsDirty($lang, false)` never unmarks a language; with "all languages dirty" (`[]`) it
  resets the map to `null`, i.e. "nothing dirty".

**Fix direction:** cache after a successful load; bound parameters; fix the dirty map logic.

---

## Metadata (Scope 7)

## [ ] B-188 – Predefined metadata listing can contain `null`s → `Error` in every lookup (id list from all stores, data from the read target only)

**Files:** `models/Metadata/Predefined/Listing/Dao.php:35-51`; `lib/Model/Dao/PimcoreLocationAwareConfigDao.php:54-79`;
`lib/Config/LocationAwareConfigRepository.php:70-96`, `:263-269`; `models/Metadata/Predefined/Listing.php:74-124`;
`models/Metadata/Predefined/Dao.php:73-94`; `models/Asset/Service.php:308`

- `loadList()` takes ids from `loadIdList()` → `fetchAllKeys()`, which merges the settings-store ids **and** the
  container-config keys whatever the read target is. `getById()` loads through `loadConfigByKey()`, which honours the
  read target. Ids from the other store, and definitions whose data is empty, give `getById() === null`, and the
  `null` goes into the list.
- Typical trigger: switching `config_location.predefined_asset_metadata` (settings-store ↔ symfony-config) leaves the
  old entries behind.
- Consequences: `getByTargetType()` (`$item->getTargetSubtype()`), `getByKeyAndLanguage()` (`$item->getName()`) and the
  filter closure in `Dao::getByNameAndLanguage()` call methods on `null` → `Error`. `Asset\Service::
  expandMetadataForEditmode()` calls `getByName()` for every metadata item, so opening **any** asset with metadata in
  the admin fails. User filter callbacks (`setFilter()`) receive `null`s too.
- `loadIdListByReadTargets()` exists for this case and is unused here.

**Fix direction:** use `loadIdListByReadTargets()`, skip `null` results (log them), and return `list<Predefined>`.

## [ ] B-189 – `Predefined::minimize()` / `expand()` are not idempotent and crash on a missing type

**Files:** `models/Metadata/Predefined.php:258-280`; `models/Asset/MetaData/ClassDefinition/Data/Asset.php:66-97` (same
pattern in `Document.php`, `DataObject.php`, `Date.php`)

- The model does not know whether `$data` is in storage form (element id, timestamp) or edit-mode form (path, date
  string):
  - `minimize()` on already minimized data: `getDataFromEditMode(42)` – not a string, not an element → returns `''`
    and **the reference is wiped**;
  - `expand()` on already expanded data: `getDataForEditMode('/path')` – not numeric → `''`;
  - saving after `expand()` without `minimize()` stores the path; the next `expand()` returns `''`.
  Which sequence happens depends on the caller (admin bundle, imports); the model gives no protection.
- `Date::normalize()` stores `false` when `strtotime()` fails.
- `$this->type === null` (a definition saved without a type, which `save()` allows, B-191) → `$loader->build(null)` →
  `TypeError` (strict file). Only `UnsupportedException` is caught.

**Fix direction:** keep the storage form in the model and convert only at the edges (`toEditmode()` returns a copy), or
let the converters accept both forms; validate `type` on save; catch `\Throwable` from the loader with context logging
(I-87).

## [ ] B-190 – Name / language lookups: names with `~` are never found, `''` language matches any language

**Files:** `models/Metadata/Predefined.php:75-86`, `:111-116`; `models/Metadata/Predefined/Dao.php:73-94`;
`models/Metadata/Predefined/Listing.php:103-124`; `models/Asset/Service.php:308-311`

- `getByName()` calls `setName()` (which turns `~` into `---`) but `getByNameAndLanguage()` compares the **raw** `$name`
  with the stored, normalised names → a definition named `a~b` (stored as `a---b`) is never found. Asset counterpart: B-06.
- `getByName($name, $language = '')`: `''` is falsy, so the language filter is skipped and the first definition with
  that name in **any** language is returned (order of the config keys). `Asset\Service::expandMetadataForEditmode()` uses
  it for every item regardless of `$item['language']` → with per-language definitions of different `config` (select
  options) the editor gets the options of another language. `Listing::getByKeyAndLanguage()` behaves the same for
  `null` / `''`.
- `getByNameAndLanguage(null, …)` returns an arbitrary first definition.
- Loose `!=` comparisons: numeric-string names compare numerically (`'1e1' != '10'` is `false`).

**Fix direction:** normalise the name before comparing; treat `''` / `null` language as "language-neutral" and match it
exactly (with an explicit "any language" option where needed); strict comparisons; one shared lookup (I-87).

## [ ] B-191 – `Predefined::save()` ignores writeability, validates nothing and accepts any id

**Files:** `models/Metadata/Predefined/Dao.php:99-129`; `lib/Model/Dao/PimcoreLocationAwareConfigDao.php:27`, `:100-142`;
`lib/Config/LocationAwareConfigRepository.php:124-140`, `:177-193`, `:236-261`

- `save()` never checks `isWriteable()` (`delete()` does, inside `deleteData()`):
  - write target `symfony-config` with debug off (`isWriteable()` is `false`): the YAML file is still written into
    `var/config/predefined_asset_metadata`. It is not loaded until the container is rebuilt, so the editor sees the old
    values, and the change appears unexpectedly at the next cache clear / deploy;
  - write target `disabled`: `saveConfig()` writes nothing and `save()` returns normally ("saved").
- No validation: `name` and `type` can be empty, `type` need not be a registered metadata type (→ B-189),
  `targetSubtype` need not be an asset type.
- `setId()` accepts any string; the id becomes the YAML file name (`getVarConfigFile()`: `$directory . '/' . $key .
  '.yaml'`) and the settings-store key. Same path traversal as C-09; not reachable through the admin (UUID ids), only
  through code / imports. The fix belongs to the repository (C-09).
- `delete()` on a definition that was never loaded (`dataSource === null`) does nothing and reports nothing.
- `PimcoreLocationAwareConfigDao::$cache` is a process-wide static. `save()` / `delete()` invalidate only the current
  process; in worker runtimes other workers keep serving the old definition (Messenger workers are stopped by
  `saveConfig()`, HTTP workers are not).

**Fix direction:** check `isWriteable()` in `save()` and throw; a `validate()` step (name, type, subtype, id pattern
`^[A-Za-z0-9_-]+$`); throw from `delete()` when there is nothing to delete; reset the static cache via `kernel.reset`
(I-87).

## [ ] B-192 – Predefined metadata small defects

**Files:** `models/Metadata/Predefined/Dao.php:50-67`; `models/Metadata/Predefined/Listing.php:74-101`;
`bundles/CoreBundle/src/DependencyInjection/Configuration.php:667-695`

- `Dao::getById('0')`: `$id != null` is `false` for `'0'`, so the model's current id (`null`) is used →
  `getDataByName(null)` → `TypeError`.
- `getByTargetType()` returns the `array_filter()` result with its original keys → a JSON object instead of an array
  when the list is serialised for the admin store. Declared `?array`, but it is never `null`.
- The config tree accepts `inheritable` (`:682-689`), but the model has no such property; the value is dropped on load
  and removed from the file on the next save.
- `data` is a `scalarNode` in the config tree while the model stores `mixed`. A custom metadata type whose storage form
  is an array would be written to YAML and **break the next container compilation** (only with the `symfony-config` write
  target; no core type does this today).

**Fix direction:** `$id !== null`; `array_values()`; remove `inheritable` or model it; validate `is_scalar($data)` on
save, or make `data` a `variableNode`.

---

## Notification (Scope 7)

## [ ] B-193 – One invalid row breaks the whole notification list (and `deleteAll()`): the model writes what the DAO refuses to read

**Files:** `models/Notification/Dao.php:92-142`; `models/Notification.php:107-124`, `:324-329`;
`models/Notification/Listing/Dao.php:54-77`; `models/User/AbstractUser/Dao.php:153-158`

- `assignVariablesToModel()` throws `UnexpectedValueException` for a missing recipient, an empty title and an empty
  message. `Notification::getById()` only catches `NotFoundException`, so `Listing\Dao::load()` fails, and with it
  `findAll()`, `findLastUnread()` and `deleteAll()` → the user's notification panel and unread polling fail, and the
  rows cannot even be cleared (inside an open transaction, C-34).
- Such rows are easy to write: `Notification::save()` validates nothing; `title` defaults to `''` in the model and the
  schema; `message = ''` is accepted. Only `NotificationService::sendToUser()` validates, and other writers (custom code,
  the Studio bundle's service, payload-only notifications) do not go through it.
- Deleting a user deletes only the `users` row (`AbstractUser\Dao::delete()`): the user's notifications stay (no FK)
  and any listing not filtered by that recipient throws.
- `creationDate` and `modificationDate` both `NULL` → `setCreationDate(null)` → `TypeError`.

**Fix direction:** one `validate()` in `Notification::save()`; when loading, skip broken rows with a warning instead of
throwing; delete or cascade a user's notifications (I-90).

## [ ] B-194 – `NotificationService::format()` timestamps are shifted by the PHP timezone offset

**Files:** `models/Notification/Dao.php:62`; `models/Notification/Service/NotificationService.php:231-255`, `:257-268`;
`bundles/InstallBundle/dump/install.sql:810-811`

- `Dao::save()` writes `date('Y-m-d H:i:s')` in the PHP default timezone; the `TIMESTAMP` column returns the same local
  string. `format()` parses it with `new Carbon(…, 'UTC')`, so `timestamp` is off by the PHP offset (1–2 h in
  `Europe/Vienna`), and `date` and `timestamp` disagree.
- `findLastUnread()` converts `$lastUpdate` back with the local `date()`. A client that uses `format()['timestamp']` as
  its next `lastUpdate` gets notifications repeated or missed (admin-ui-classic polling not verified).
- Local wall-clock storage is ambiguous in the DST fall-back hour (ordering by `creationDate` is wrong for that hour), and
  a MySQL session timezone that differs from PHP shifts the stored values.

**Fix direction:** store UTC explicitly (`DateTimeImmutable` in UTC, `DATETIME` column, I-90) and format with the same
timezone that was used for writing.

## [ ] B-195 – `findAll()` filters: unquoted keys, conditions without parentheses, colliding keys, ignored offset, swallowed count errors

**Files:** `models/Notification/Service/NotificationService.php:181-226`;
`models/Notification/Service/NotificationServiceFilterParser.php:66-96`; `models/Notification/Listing/Dao.php:32-48`;
`lib/Model/Listing/Dao/AbstractDao.php:70-81`

- `findAll(['read' => 0])` → `read = :read` → SQL syntax error (`READ` is a reserved word); keys are never quoted.
- Conditions are joined with a bare `' AND '`. A caller condition that contains `OR` widens the result beyond the
  `recipient` restriction, even for trusted callers (request input: C-35).
- `parse()` keys its result by `<property>_<operator>`: two filters on the same property and operator (two `like` on
  the title) collapse into the last one.
- `offset` without `limit` is ignored (`getOffsetLimit()` only emits `LIMIT offset, limit`); a negative string limit
  (`'-1'`) becomes `LIMIT -1` → SQL error.
- `Listing\Dao::count()` catches every `\Exception` and returns `0`, so the UI shows "0 total" while `getItems()`
  throws, or shows data with a total of `0`.

**Fix direction:** typed filter object (I-88); quote identifiers; parenthesise each condition; let `count()` throw.

## [ ] B-196 – Filter parser crashes on malformed input (500 instead of 400) and the date operators overlap

**File:** `models/Notification/Service/NotificationServiceFilterParser.php:66-181`

- Invalid JSON → `json_decode()` returns `null` → `foreach` over `null` (warning; `ErrorException` in debug). A
  non-string scalar `filter` (`filter=1`) → `TypeError` in `json_decode()` (strict file). Missing `type` / `operator` /
  `property` → undefined-key warnings and a `TypeError` from `getDbProperty(): string`. Unknown operator →
  `throw new Exception()` without a message. Invalid date → Carbon `InvalidFormatException`. Unknown `type` is
  silently ignored.
- Date semantics: `gt` → `> <day> 00:00:00` and `lt` → `< <day> 23:59:59`, so both include the selected day (and `lt`
  misses its last second), while `eq` covers the whole day. "Before / on / after" overlap (the intended semantics of the
  admin date filter should be confirmed).
- `like` values are not escaped: `%` and `_` in the search text act as wildcards.

**Fix direction:** `getString()` + `JSON_THROW_ON_ERROR`, validate each item and throw a dedicated exception that the
controller maps to 400; `gt` → `>= next day 00:00`, `lt` → `< day 00:00`; escape `LIKE` wildcards.

## [ ] B-197 – `findAndMarkAsRead()` / `delete()`: inconsistent authorization contracts

**File:** `models/Notification/Service/NotificationService.php:155-171`, `:303-314`

- `findAndMarkAsRead($id)` with the default `$recipientId = null` **always** throws `AccessDeniedHttpException` (the
  recipient id is never `null`), so the optional parameter is effectively required. A foreign recipient throws.
- `delete($id)` with a foreign or `null` recipient silently does nothing and commits; the caller reports success.
- An already read notification is saved again (modification date bumped, `PRE_SAVE` / `POST_SAVE` fired).
- An HTTP exception (`symfony/http-kernel`) is thrown from a model service (I-88).

**Fix direction:** make `$recipientId` required (or a separate admin method without the check); throw the same domain
exception from both methods; skip the save when nothing changed.

## [ ] B-198 – Deleted notifications stay loadable and can be resurrected; events fire before commit

**Files:** `models/Notification.php:107-124`, `:324-339`; `models/Notification/Dao.php:60-78`; `lib/Db/Helper.php:38-53`

- `delete()` does not remove `notification_<id>` from `RuntimeCache`: `getById()` after a delete in the same process
  returns the deleted object (in worker runtimes until the runtime cache is reset; Messenger clears it per message).
- `Dao::save()` uses `Helper::upsert()` with the explicit id: saving a notification whose row was deleted concurrently
  (mark-as-read racing "delete all") inserts it again.
- `POST_SAVE` / `POST_DELETE` are dispatched inside the service transaction (C-34); listeners that push to clients see
  data that is not committed yet (or never will be).

**Fix direction:** remove the runtime-cache entry in `delete()`; `update()` with an affected-rows check for existing
ids; dispatch post events after commit.

## [ ] B-199 – Notification small defects

**Files:** `models/Notification.php:66`, `:201-214`; `models/Notification/Service/NotificationService.php:59-74`,
`:90-133`; `bundles/CoreBundle/config/pimcore/default.yaml:148`; `bundles/InstallBundle/dump/install.sql:804-805`

- `$title` is a non-nullable `string`, but `setTitle(?string)` / `getTitle(): ?string` → `setTitle(null)` throws
  `TypeError` ("Cannot assign null to property").
- `sendToUser()` rejects the title or message `'0'` (`empty()`).
- `sendToUser()` with a non-existent `$fromUser` stores no sender without any notice.
- With `sql_mode = ''`, titles over 250 characters and types over 20 characters are truncated silently (workflow titles
  contain the full element path).
- `sendToGroup()` is not atomic: an error at user *n* leaves users before *n* notified and the rest not, and the
  transaction open (C-34).

**Fix direction:** `?string` → `string` consistently; `=== ''` checks; validate the sender and the lengths in the model.

## [ ] B-200 – Notification tests assert nothing; `testSendToGroup` expects a count that cannot happen

**File:** `tests/Unit/Notification/Service/NotificationServiceTest.php:83-158`

- `$this->equalTo($count, $notifications['total'])` only builds a PHPUnit constraint and discards it. No assertion runs,
  so `testSendToUser`, `testSendToUserWithElement` and `testSendToGroup` pass whatever the result is.
- `testSendToGroup` expects `3` for one recipient. That user has neither the `notifications` permission nor a role
  with it, so `filterUsersWithPermission()` removes them and **0** notifications are sent; a real assertion fails.
- `testSendToNoExistUser` leaves a DB transaction open for the following tests (C-34).

**Fix direction:** `assertSame()`; give the group the permission and expect `1`; assert the transaction nesting level
(I-92).

---

## Property (Scope 8)

## [ ] B-201 – Properties of type `date` cannot be stored: the `properties.type` enum has no `date`

**Files:** `models/Property.php:81-98` (`:85-86`); `models/Property/Dao.php:33-68`;
`bundles/InstallBundle/dump/install.sql:305`; `bundles/CoreBundle/config/pimcore/default.yaml:148`

- `setDataFromResource()` has a `date` branch that unserializes the stored value, so the model supports date
  properties (`setProperty('launch', 'date', Carbon::now())`). The column is
  `enum('text','document','asset','object','bool','select')`, and no migration adds `date`.
- With `sql_mode = ''` the invalid enum value is stored as `''` without an error. `Dao::save()` serialized the Carbon
  object (`:44-46`). On load, type `''` takes the plain-text branch: `getData()` returns the **serialized string**
  (`O:13:"Carbon\Carbon":…`) and `getType()` returns `''`. The date is lost for templates and editmode, and the next
  save keeps the broken type.
- With a strict SQL mode, the same save fails inside the element save (the element cannot be saved).

**Fix direction:** decide whether `date` is supported. If it is, add it to the enum by migration and store a
timestamp or ISO string instead of a serialized object. If not, remove the branch and reject the type in the
model (I-93 enum).

## [ ] B-202 – One property row with `type` (or `name`) `NULL` makes the element and all its descendants unloadable

**Files:** `models/Asset/Dao.php:234-286`; `models/DataObject/AbstractObject/Dao.php:222-273`;
`models/Document/Dao.php:256-315`; `models/Property.php:184-202`; `models/Property/Dao.php:33-68`;
`bundles/InstallBundle/dump/install.sql:305`

- The three `getProperties()` hydrators call `$property->setType($propertyRaw['type'])` and `setName(...)`. Both
  parameters are non-nullable `string`; `null` throws `TypeError` even without `strict_types`. The per-row
  `catch (Exception)` does not catch `TypeError`, so `getProperties()` fails.
- `properties.type` is nullable, and `Property::save()` / `Dao::save()` validate nothing: a `new Property()` with only
  `setName()` / `setData()` passed to `setProperties()` (imports, custom code) is saved with `type = NULL`.
- If the row is `inheritable`, every descendant reads it too: the element, its children and the whole subtree fail in
  `getProperty()`, in editmode (`minimizePropertiesForEditmode()`), on frontend rendering (navigation and
  `$document->getProperty()` in templates) and on save. Removing the row requires SQL.

**Fix direction:** validate `name`, `type` (allowed list), `ctype` and `cid` in `Property::save()` / `Dao::save()`;
catch `\Throwable` per row and skip it with a log entry; `type` `NOT NULL` by migration (I-99).

## [ ] B-203 – Array / object values are serialized on save but returned as the serialized string on load

**Files:** `models/Property/Dao.php:44-46`; `models/Property.php:81-98`, `:166-179`

- `Dao::save()` serializes every array or object (`Serialize::serialize()`). `setDataFromResource()` unserializes
  only for `date`, so `text` / `select` properties come back as the raw `a:1:{…}` string.
  `setProperty('tags', 'text', ['a', 'b'])` reads back as `'a:2:{i:0;s:1:"a";…}'`.
- An object that is not an element (e.g. a value object) is stored serialized and never restored. Accepting it at all
  widens the PHP object-injection surface of `properties.data` (I-75).

**Fix direction:** accept only scalar data for `text` / `select` / `bool` (`TypeError` / `InvalidArgumentException` in
`setData()`), or store JSON and decode for every type.

## [ ] B-204 – Relation properties are silently wiped: dangling targets, unknown paths and missing id mappings become `NULL`

**Files:** `models/Property/Dao.php:35-42`; `models/Property.php:53-74` (`:57-62`), `:120-128`, `:292-303`

- `Dao::save()` stores the id only when `getData()` resolves to an element; otherwise `data = NULL`. `getData()` loads
  the element by id. If the target was deleted (or is not yet saved, id `null`), **any** save of the owner with
  dirty properties replaces the stored id with `NULL`. The dependency system would have shown the dangling reference;
  afterwards it is gone without a trace.
- `setDataFromEditmode()` with a path that does not resolve (typo, element renamed concurrently) stores `NULL`
  without an error.
- `rewriteIds()` replaces the target with `getElementById(...)` of the mapped id; if that element is missing the
  property becomes `NULL`.
- Every save loads every relation target only to read its id back (one element load per relation property per save).

**Fix direction:** keep the raw id in the model and save it as is (`is_numeric($this->data)`); resolve the element only
in `getData()`. Throw (or report to the caller) on unknown paths. In `rewriteIds()`, keep the old id when the mapping
target is missing.

## [ ] B-205 – Property small defects

**Files:** `models/Property.php:100-105`, `:166-179`; `models/Property/Dao.php:49-55`, `:67`;
`models/Asset/Dao.php:249-251`; `models/Document/Dao.php:278-280`; `bundles/InstallBundle/dump/install.sql:300-310`

- `Dao::save()` "fills" an empty `cpath` (`:49-55`): it computes `$element->getRealFullPath()` and discards the result.
  A property saved directly via `Property::save()` (not through the element save loop, which sets `cpath`) gets
  `cpath = NULL`. `getProperties()` sorts by `cpath`, so the `NULL` row sorts first and the element's own property is
  **overridden by an inherited parent property of the same name**. `strcmp(null, …)` in `Asset/Dao.php` /
  `Document/Dao.php` also emits a PHP 8.1+ deprecation. Path moves (`REPLACE(cpath, …)`) never update the row.
- `Property::save()` removes only `<ctype>_properties_<cid>`. Descendants cache their inherited properties under
  their own keys, so after a direct save of an inheritable property the children keep serving the old value until
  the `<ctype>_properties` tag is cleared by an element save.
- `name` is `VARCHAR(190)`. With `sql_mode = ''` longer names are truncated; two names with the same 190-character
  prefix collide on the primary key `(cid, ctype, name)` and `Helper::upsert()` silently overwrites the first.
  `cpath` is `utf8` (3-byte): keys with 4-byte characters (emoji) are stored with `?`, which breaks the ordering and
  the move rewrite for that subtree.
- `setData(ElementInterface)` silently overrides an explicitly set type: `setProperty('x', 'document', $asset)` saves an
  `asset` property.
- `ctype` is never validated. An unknown value is stored as `''` (enum + `sql_mode = ''`) and the property is lost.
- Schema default `inheritable = 1` vs. model default `false`: rows inserted outside the model are inheritable.

**Fix direction:** set `cpath` from the element in `Dao::save()` (or require it); clear the `<ctype>_properties` tag on
direct saves; validate the name length, the type / data pair and `ctype`; align the defaults (I-99).

---

## Predefined property (Scope 8)

## [ ] B-206 – Predefined property listing can contain `null`s → opening any element with properties in the admin fails

**Files:** `models/Property/Predefined/Listing/Dao.php:34-51` (`:38-40`); `models/Property/Predefined/Dao.php:76-98`
(`:85-88`); `lib/Model/Dao/PimcoreLocationAwareConfigDao.php:54-84`; `lib/Config/LocationAwareConfigRepository.php:263-278`;
`models/Element/Service.php:516-560` (`:550`)

- Same root cause as B-188: `loadList()` takes ids from `loadIdList()` → `fetchAllKeys()` (settings store **and**
  container config), but `getById()` loads through the read target only. Ids from the other store, or definitions with
  empty data, give `getById() === null`, which is appended to the list.
- Typical trigger: switching `config_location.predefined_properties` between settings-store and symfony-config, or a
  definition left in the settings store after moving to YAML.
- `Predefined::getByKey()` → `Dao::getByKey()` filters the list with `$item->getKey()` → `Error` on `null`.
  `Element\Service::minimizePropertiesForEditmode()` calls `getByKey()` for **every** property of the element being
  opened, so every document / asset / object that has (or inherits) a property can no longer be opened in the admin.
- `getTotalCount()` counts the `null`s; user `setFilter()` / `setOrder()` callbacks receive `null`.

**Fix direction:** `loadIdListByReadTargets()`, skip `null` results with a log entry, return `list<Predefined>`; shared
fix with B-188.

## [ ] B-207 – `Predefined::getCtype(): string` throws for definitions without `ctype`

**Files:** `models/Property/Predefined.php:49`, `:189-192`, `:174-187`, `:91-97`;
`bundles/CoreBundle/src/DependencyInjection/Configuration.php:1852-1870`

- The property is `?string $ctype = null`, the getter returns `string` → `TypeError` when the definition has no
  `ctype`. The config tree does not require it, and `Predefined::create()` saves a definition without it.
- The upstream admin predefined-properties store filters definitions by `getCtype()` for the element type being
  edited (not verified locally): one incomplete YAML definition breaks the property panel for every element.
- `setConfig(string)` vs. `?string $config`; `setKey()` / `setName()` / `setType()` cannot reset to `null` although
  the getters return `?string`.

**Fix direction:** `getCtype(): ?string` (or default `''` and validate on save); consistent nullability for all
setters / getters.

## [ ] B-208 – `Predefined::getByKey()`: stale cache, uncached misses, duplicate keys win by listing order

**Files:** `models/Property/Predefined.php:69-89`; `models/Property/Predefined/Dao.php:76-98`, `:103-133`;
`models/Element/Service.php:548-557`

- The `RuntimeCache` entry `property_predefined_<key>` is never removed by `save()` / `delete()`. In the same process
  (CLI, worker runtimes, CoreBundle C-08) a renamed or deleted definition is still returned; `getById()` has the same
  issue through the static `PimcoreLocationAwareConfigDao::$cache` (B-191).
- Misses are not cached: `getByKey()` for a property without a definition (most properties) loads and hydrates the
  **whole** listing each time. `minimizePropertiesForEditmode()` does this for every property, so editmode load cost
  is O(properties × definitions).
- Keys are not unique (no check in `save()`). With two definitions sharing a key the first in listing order wins.
  `minimizePropertiesForEditmode()` then silently drops the config (select options, description) whenever the types
  differ (`$predefined->getType() == $p->getType()`).
- Loose `==` key comparison (`Dao.php:86`): numeric-string keys compare numerically (`'1e1' == '10'`).

**Fix direction:** one key index built per listing load (and reset with the config cache); cache misses too;
invalidate on save / delete; reject duplicate keys on save; `===` (I-96).

## [ ] B-209 – `Predefined::save()` / `create()` / `getById()` defects (mirror of B-191 / B-192)

**Files:** `models/Property/Predefined.php:57-67`, `:91-97`; `models/Property/Predefined/Dao.php:50-70`, `:103-133`;
`lib/Model/Dao/PimcoreLocationAwareConfigDao.php:100-142`

- `save()` never checks `isWriteable()`: with the `symfony-config` write target and debug off the YAML file is written
  but not loaded until the next container build; with the `disabled` write target nothing is written and `save()`
  reports success.
- No validation: `key`, `name` and `type` may be empty; `type` need not be one of the property types (B-201,
  `install.sql:305`); `ctype` may contain unknown element types.
- `create()` persists an **empty** definition immediately (no key, no type). With the symfony-config target this
  writes a YAML file full of `null`s; until it is edited, every `getByKey()` scans it.
- `setId()` accepts any string; the id becomes the YAML file name (C-09 path traversal, only reachable through code /
  imports – admin ids are UUIDs).
- `Dao::getById('0')`: `$id != null` is `false` for `'0'` → `getDataByName(null)` → `TypeError`. Legacy numeric ids
  from `predefined-properties.php` (migration `Version20220829132224`) can reach `getById(string)` as `int` from
  `array_keys()` (strict `Listing.php` → `Listing/Dao.php` is not strict, coerced today, I-93).

**Fix direction:** same as B-191 / B-192: check writeability, `validate()` (key, name, type, ctype, id pattern),
`$id !== null`, cast legacy ids; `create()` should not save (or take the required fields).

---

## Schedule (Scope 8)

## [ ] B-210 – Due tasks run in id order (publish / unpublish swapped) and concurrent runs execute tasks twice

**Files:** `lib/Maintenance/Tasks/ScheduledTasksTask.php:43-135` (`:45-47`, `:128-129`);
`lib/Maintenance/Executor.php:72-98`; `bundles/CoreBundle/config/pimcore/default.yaml:50`

- The listing has no `ORDER BY`; InnoDB returns the rows in primary-key (creation) order. When several tasks of one
  element are due in the same run (maintenance was down, the queue was blocked by C-36, or an editor scheduled in the
  past), creation order decides: "unpublish 18:00" created before "publish 09:00" runs first, and the document ends
  **published** although its last scheduled state is unpublished.
- No lock: every maintenance run dispatches a new `ScheduledTaskMessage`. With more than one consumer on
  `pimcore_scheduled_tasks`, or a run that takes longer than the interval, two runs load the same due tasks.
  `publish-version` is saved twice (two versions); `delete` creates two recycle-bin items and the second delete
  fails.
- The task is deactivated only **after** the action (`:128-129`); a crash between the save and `$task->save()` repeats
  the action in the next run.

**Fix direction:** `ORDER BY date, id`; claim each task atomically (`UPDATE … SET active = 0 WHERE id = ? AND active = 1`
and check the affected rows) or use a `LockFactory` lock like `LowQualityImagePreviewTask`; deactivate in the same
transaction as the action.

## [ ] B-211 – Tasks that did nothing are reported as done

**Files:** `lib/Maintenance/Tasks/ScheduledTasksTask.php:53-129`; `models/Schedule/Task.php:41`, `:158-163`;
`bundles/InstallBundle/dump/install.sql:331`

- After any outcome without an exception the task is set `active = 0`, also when nothing happened:
  - the user lacks the permission (`isAllowed()` `false`), with no log entry;
  - the element no longer exists, or a data object is not `Concrete`;
  - the action does not apply to the element type (`publish` / `unpublish` for assets);
  - the action is unknown (`''` – the enum column stores invalid values as `''` with `sql_mode = ''`);
  - `publish-version` with `version = NULL` falls through all branches.
- The editor sees the task vanish from the pending list and assumes it ran. Only the missing version and the failed
  restore are logged (`:63`, `:66`).

**Fix direction:** log every skip with the reason and add an element note; validate `action` per `ctype` and require
`version` for `publish-version` when saving the task (I-93 enum).

## [ ] B-212 – Scheduled tasks are lost on recycle-bin restore and orphaned when links / hardlinks are deleted

**Files:** `models/Document.php:97-107`, `:621-646`; `models/DataObject/AbstractObject.php:131-145`;
`models/Asset.php:177-190`, `:1015`; `models/Document/PageSnippet.php:220-231`; `models/DataObject/Concrete.php:216-223`;
`models/Document/Link.php:32`, `:340`; `models/Document/Hardlink.php:29`, `:189`;
`models/Element/Recyclebin/Item.php:87-123`, `:208-216`; `models/Schedule/Task/Dao.php:42-49`

- `getBlockedVars()` always excludes `scheduledTasks`, also in dump state, so recycle-bin dumps never contain tasks.
  `Item::loadChildren()` calls `$element->getScheduledTasks()` (`:216`) for nothing. Page snippets, objects and assets
  delete their tasks in `doDelete()` / `delete()`, so a restore brings the element back **without** its scheduled
  publication / unpublication.
- `Document\Link` and `Document\Hardlink` use `ScheduledTasksTrait` and save tasks, but no `doDelete()` removes them
  (`Document::doDelete()` does not call `deleteAllTasks()`). The rows stay `active = 1`; they are only deactivated when
  they fall due. If the link is restored from the recycle bin with the same id, its old tasks come back, unlike for
  every other type.
- `Task\Dao::save()` with an id whose row no longer exists runs only `update()` → 0 rows, no error. Tasks carried over
  with their ids (restore code, B-182 copies) disappear silently.

**Fix direction:** store tasks explicitly in the dump and re-create them with `id = null` on restore; delete tasks in
`Document::doDelete()` for every type; `save()` should insert when the update affects no row (or throw).

## [ ] B-213 – `Task\Listing` returns `null` entries and cached tasks; `getTotalCount()` hides errors

**Files:** `models/Schedule/Task/Listing/Dao.php:33-54`; `models/Schedule/Task.php:49-69`;
`models/Element/Traits/ScheduledTasksTrait.php:39-77` (`:70`)

- `load()` selects ids and calls `Task::getById()` per id. A row deleted in between gives `null` in the list: it
  crashes the maintenance run (C-36) and `ScheduledTasksTrait::saveScheduledTasks()` (`$task->setDao(null)` on
  `null` → `Error`, the element save fails).
- `Task::getById()` returns the `RuntimeCache` instance. Task `delete()` / `update()` do not touch the cache, and
  `saveScheduledTasks()` mutates the shared instance (`setDao(null)`, `setCid()`). In long CLI processes (imports that
  save many elements) a listing returns tasks with the state of an earlier load.
- `getTotalCount()` returns `0` on any SQL error (`catch (Exception)`), so a broken condition looks like "no tasks".

**Fix direction:** hydrate rows directly from one `SELECT *` (no per-id lookup, no `null`s); do not share cached
instances with listings; let SQL errors propagate (I-97).

## [ ] B-214 – Schedule small defects

**Files:** `models/Schedule/Task.php:71-83`; `models/Schedule/Task/Dao.php:42-81`;
`models/Element/Traits/ScheduledTasksTrait.php:64-77`; `lib/Maintenance/Tasks/ScheduledTasksTask.php:60-61`, `:87`,
`:107-108`; `models/Element/AbstractElement.php` (`updateModificationInfos()`)

- `Dao::save()` for a new task runs `INSERT … () VALUES ()` and then `update()`. When `update()` fails, an all-`NULL`
  row (`active = 0`) is left behind. Not atomic.
- `Task::create(array $data)` rejects `id` (`checkCreateData()`), but the constructor `new Task($data)` accepts it.
  `saveScheduledTasks()` saves tasks by id and only overwrites `cid` / `ctype`. A client-supplied task id (upstream
  admin builds tasks with `new Task($taskData)`, not verified locally) re-binds **another element's task** to the
  element being saved, and `deleteAllTasks($ignoreIds)` then keeps it there.
- `Task::create()` does not save, unlike every other model `create()`.
- A task with `active = 1` and `date = NULL` is never due (`date < ?` is `NULL`) and is never cleaned up.
- Scheduled saves run without an admin user, so `updateModificationInfos()` records `userModification = 0` ("system")
  instead of the task's user. Version history and audit show the system user for every scheduled publication.
- `cid`, `ctype`, `action` and `date` are not validated in the model; invalid enum values are stored as `''`.

**Fix direction:** one `INSERT` with all columns (or a transaction); ignore / reject client ids in `saveScheduledTasks()`
unless the row belongs to the element; set `userModification` to the task user before saving; validate on save (I-97).

---

## Version (Scope 9)

## [ ] B-215 – `Version::disable()` / `enable()` is a global flag that does not nest and is never reset: one exception switches versioning off for the rest of a worker

**Files:** `models/Version.php:78`, `:106-127`, `:129-136`; `lib/Messenger/Handler/AssetUpdateTasksHandler.php:66-72`;
`models/Asset/Video.php:202-204`, `:231-233`; `models/Asset/Video/Thumbnail/Processor.php:157-159`, `:214-216`,
`:311-313`; `models/Element/AbstractElement.php:293-297`

- None of the `disable(); save(); enable();` sequences use `try` / `finally`. If the `save()` in between throws, `$disabled`
  stays `true`. This affects Messenger consumers (`AssetUpdateTasksHandler`, video conversion) and worker runtimes
  (CoreBundle C-08; e.g. a frontend `Video::getDuration()` auto-save fails). From then on, **every** save in that
  process creates no version, and `AbstractElement` stops incrementing `versionCount` (`:293-297`). There is no error
  and no log entry. Editors find no history for changes made in that worker.
- The flag does not nest. An importer calls `Version::disable()`, then saves a video. `Video::getDuration()` /
  `getDimensions()` call `disable()` / `enable()` themselves, and versioning is back on for the rest of the import.
- `public static bool $disabled` can be written from anywhere and is not reset on `kernel.reset`.
- `save()` dispatches `PRE_SAVE` **before** checking the flag (`:131-136`). Listeners react to a version that is never
  written, and there is no `POST_SAVE`.
- `save()` also returns silently for **existing** versions. A metadata update (`SanityCheckHandler` re-orders the
  draft with `setVersionCount()` / `setNote()`, B-219) is dropped while versioning is disabled.

**Fix direction:** a counter-based `Version::withoutVersioning(callable $fn)` with `try` / `finally` that replaces all
call sites; reset the counter through `ResetInterface`; check the flag before `PRE_SAVE`; do not apply the flag to
metadata updates of existing versions (B-219, I-102).

## [ ] B-216 – `Version::loadData()` calls `Pimcore::collectGarbage()` on every load: idle DB connections and Monolog handlers are closed and the runtime cache is cleared mid-request

**Files:** `models/Version.php:280-283`; `lib/Pimcore.php:142-150`; `lib/Helper/LongRunningHelper.php:37-44`, `:62-105`

- `collectGarbage()` runs `LongRunningHelper::cleanUp()`. It closes every DBAL connection that has no active
  transaction, closes all registered Monolog handlers, clears the `RuntimeCache` except six protected keys
  (`Config_system`, `pimcore_admin_user`, `Config_website`, `pimcore_error_document`, `pimcore_site`, `Pimcore_Db`) and
  runs `gc_collect_cycles()`.
- This happens on **every** version load, in web requests too: admin preview / diff / restore, frontend `?v=` and
  `pimcore_version` (CoreBundle B-35), scheduled `publish-version`, `SanityCheckHandler`, and every re-save (B-219).
  Effects:
  - Elements loaded earlier in the request drop out of the runtime cache, so a later `getById()` returns a
    **different instance**. Changes made to the first instance are not visible to code that reloads the element (e.g.
    "publish version" loads the version, then saves).
  - A listing preview of N versions reconnects to the DB N times. Connection-scoped state (user variables,
    `GET_LOCK()` locks, temporary tables) is lost. Buffered log handlers are flushed and closed.
- The comment ("avoid dealing with marshalled data") points at the real cause. `__wakeup()` in dump state calls
  `getById()` for the original element (`AbstractElement.php:652-670`, `Asset.php:1613-1633`), which caches it while the
  unserialized copy is being built.

**Fix direction:** remove the call. If the cached originals are a problem, load them without caching (`force`) or
remove only the affected keys after `unmarshalData()`.

## [ ] B-217 – Uninitialised typed properties: a version without `setDate()`, with the system user, a deleted user or `NULL` columns throws `Error`

**Files:** `models/Version.php:48-60`, `:139-141`, `:323-346`, `:391-399`, `:435-438`; `models/Version/Dao.php:36-48`;
`lib/Model/AbstractModel.php:175-190`; `bundles/InstallBundle/dump/install.sql:578-583`

- `$cid`, `$ctype`, `$userId` and `$date` are typed non-nullable and have no default. `save()` reads `$this->date`
  (`if (!$this->date)`) to default it to `time()`. Without `setDate()` this throws "must not be accessed before
  initialization" instead.
- `setUserId()` only assigns when `User::getById()` finds a user. With `userModification = 0` ("system", documented in
  `01_Versioning.md:132-135` and used by `SanityCheckHandler.php:82`) or a deleted user, `$userId` stays uninitialised.
  `get_object_vars()` skips the property, so `userId` is stored as `NULL`, and `getUserId()` throws `Error`.
- On load, `Dao::getById()` hydrates through `setValues($data, ignoreEmptyValues: true)`: `NULL` columns are skipped,
  and `setUserId()` looks up the user again. Versions of deleted users therefore have an uninitialised `$userId`
  after every load. The same applies to `cid`, `ctype` and `date`, which are nullable in the schema: `getCid()` /
  `getCtype()` / `getDate()` throw (FS paths, `Element\Service::getSafeVersionInfo()`, cleanup).
- `setUserId()` runs `User::getById()` for every hydrated version (a listing of N versions does N user lookups).

**Fix direction:** `?int` / `?string` with `null` defaults (or required constructor arguments for new versions);
`setUserId()` stores the id unconditionally and `getUser()` loads lazily; `NOT NULL` columns where the model requires
values (I-108).

## [ ] B-218 – `getData()` / `loadData()`: failures reload on every call, errors return `null`, and a missing asset binary is silently replaced by the current file

**Files:** `models/Version.php:269-311`, `:401-408`

- `getData()` reloads whenever `!$this->data`. Failed loads (`null`) and falsy payloads are reloaded on **every**
  call, each time with storage I/O, `unserialize()`, `collectGarbage()` (B-216) and an error log line.
- `loadData()` treats empty or `'0'` metadata as unreadable (`if (!$data)`).
- Asset branch (`:297-302`): when `loadBinaryData()` returns `null` (`.bin` missing after C-39, deleted manually, or
  stored under another storage type, B-222), the asset is returned **without** a stream. `Asset::getStream()` then
  opens the **current** stored file. Preview shows the current binary, and restoring / publishing the version writes
  the old metadata with the current binary. There is no warning although `binaryFileHash` is set.
- Errors are reported as `null` plus a log entry. Not every caller checks the result: `ElementListener::handleVersion()`
  returns it as `Document` (`ElementListener.php:121`, `TypeError` → 500), and code like `$version->getData()->…` fails with
  a generic `Error` on `null` instead of a clear "version data missing" message.
- `unmarshalData(ElementInterface)` receives whatever `unserialize()` returns. Non-element payloads throw `TypeError`
  (`save()` marks arrays as serialized too, B-220).

**Fix direction:** track "loaded" in a separate flag; throw a dedicated `VersionDataNotFoundException` (also when
`binaryFileHash` is set but the binary is missing); compare the restored binary with `binaryFileHash` (I-103).

## [ ] B-219 – Re-saving an existing version rewrites its whole payload: data reloaded and re-marshalled, stack trace replaced, storage re-chosen

**Files:** `models/Version.php:129-192`, `:401-408`; `models/Version/Dao.php:56-79`;
`lib/Messenger/Handler/SanityCheckHandler.php:85-94`; `models/Element/AbstractElement.php:577-611`

- There is no metadata-only update. `SanityCheckHandler` (`setVersionCount()` + `setNote()` + `save()` on the latest
  draft), the auto-save reuse in `doSaveVersion()`, and the upstream admin update of `public` / `note` (not verified
  locally) all run the full `save()`.
  - `getData()` → `loadData()`: unserialize, unmarshal, `collectGarbage()` (B-216), `renewReferences()`. `__wakeup()`
    in dump state copies key and path from the **current** element.
  - The data is marshalled and written again. The stored draft changes: current key / path, and a round trip through
    `CustomVersionMarshalInterface` fields.
  - `stackTrace` is replaced by the trace of the re-save. Assets are re-hashed (full binary read).
- With the Delegate adapter the storage type can change (the size moved across the threshold). The new payload goes
  to the other adapter, and the old one is never deleted. With the DB adapter the binary is wiped (C-38).
- If `loadData()` fails (incompatible class, missing file), `$data` is `null` and `strlen(null)` throws `TypeError`
  after `PRE_SAVE`. `SanityCheckHandler` nacks the message, and it is retried.

**Fix direction:** `Version::updateMetadata()` / `Dao::updateMetadata()` for `note`, `public`, `versionCount` without
touching storage; `save()` only creates. For auto-save reuse, explicitly delete the old payload in the old storage
type before writing the new one (I-102).

## [ ] B-220 – `Version::save()` defects

**Files:** `models/Version.php:129-192`, `:194-221`; `models/Asset.php:1580-1593`

- `is_object($data) || is_array($data)` routes arrays and non-element objects into `marshalData(ElementInterface)` →
  `TypeError`. `method_exists($data, '__wakeup')` on an array is a `TypeError` too. Only elements work, but the
  signature is `setData(mixed)`.
- "Revert all changes made by `__sleep()`" calls `__wakeup()` on the **original** element (`:163-166`). `__sleep()`
  only ran on the deep copy (`marshalData()`), so this is a leftover with side effects on the live element:
  `Asset::__wakeup()` sets `customSettingsNeedRefresh` when `customSettingsCanBeCached === false` (in-memory custom
  settings are re-read from the DB), and `Concrete::__wakeup()` re-binds the object-aware fields.
- For assets the whole binary is hashed on every save (`hash_update_stream()`), also when it did not change (I-104).
  A pending stream that is not seekable cannot be rewound by the second `getStream()` (`:189`).
  `Asset::getStream()` then opens the **stored** file, so the hash belongs to the new data but the stored binary is
  the old one (related C-07).
- `$data->getfileSize()` (wrong case) returns the size of the **stored** file through a storage call, not of the
  pending stream. The Delegate threshold for a new upload is decided on the old size.
- The stack trace contains argument values when `zend.exception_ignore_args = Off` (the development default). Every
  version then stores truncated arguments of the whole call stack in `versions.stackTrace`.
- `setId($id)` after `Dao::save()` repeats what the DAO already did.

**Fix direction:** accept only `ElementInterface` (`setData(?ElementInterface)`); remove the `__wakeup()` call; take
hash and size from the pending stream (or from the asset checksum when the binary is unchanged); build the stack
trace with `debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)` (I-104).

## [ ] B-221 – Version small defects

**Files:** `models/Version/Dao.php:56-79`, `:106-110`; `models/Element/AbstractElement.php:577-582`;
`models/Version.php:465-473`, `:485-488`

- `Dao::save(): int` returns `$this->model->getId()`, which is `?int`; nothing guarantees an id when `lastInsertId()`
  is empty. The `@todo: $data could be undefined` comment is stale.
- The auto-save lookup in `doSaveVersion()` uses `userId = ?` with `getUserModification()`. For `null` it never
  matches, so API saves with `isAutoSave` create a new auto-save version every time instead of reusing one.
- `isBinaryHashInUse()` compares the `fetchOne()` string with `> 1` (works only through coercion) and runs for every
  delete, including versions without a hash (`binaryFileHash = NULL` never matches).
- `getPublic()` / `isPublic()` duplicate each other; `getVersionCount()` returns `$this->versionCount ?: 0` for a
  non-nullable `int`.

**Fix direction:** see I-102 / I-105.

---

## Version storage / maintenance (Scope 9)

## [ ] B-222 – Storage adapters: `storageType` is ignored by the plain adapters, binary-in-use checks span storage types, hardlinks share the live inode

**Files:** `models/Version/Adapter/FileSystemVersionStorageAdapter.php:85-127`; `DatabaseVersionStorageAdapter.php:124-128`;
`DelegateVersionStorageAdapter.php:33-53`, `:65-76`; `models/Version/Dao.php:96-110`; `models/Version.php:253-262`;
`doc/18_Tools_and_Features/01_Versioning.md:44-114`

- Only the Delegate adapter dispatches by the stored `storageType`. Switching the service alias from the FS to the DB
  adapter, as the documentation describes, makes every existing `fs` version unreadable ("cannot read version data
  with storage type: fs"). `delete()` then removes nothing from the old storage, so the files are orphaned. The same
  happens the other way round.
- Delegate: both adapters are registered by `getStorageType()`. Two adapters of the same type (e.g. two DB connections)
  collide in `$adapters`, and reads go to the fallback connection. `getAdapter()` throws a generic `\Exception`.
  `empty($this->fallbackAdapter)` is always `false` (non-nullable typed property).
- `getBinaryFileIdForHash()` filters by `storageType`, but `isBinaryHashInUse()` does not. With the Delegate adapter, a
  `.bin` in FS is kept forever when the only remaining version with that hash is stored in the DB.
- FS `save()` opens `getBinaryFileStream()` twice (`:100-101`) and never closes either handle. That leaks two handles
  per asset version, and on Windows the following `delete()` of the probe file fails.
- Hardlinks (`use_hardlinks`, default `true`): the version `.bin` shares the inode with the live asset file. Pimcore's
  own `Asset::update()` replaces the file (delete + move, C-06 keeps rename semantics), but anything that writes the
  asset file **in place** (external tools, `rsync --inplace`, a Flysystem `write()` to the same path, which truncates
  through `file_put_contents()`) silently changes every linked version. `chmod` / `chown` are shared too.
- FS `delete()` removes empty directories only when the metadata file existed, so `.bin`-only directories stay.
- Storage on a separate connection (documented for the DB adapter) or in FS is not part of the element transaction:
  after a rollback, `versionsData` rows and files remain, and no job removes orphans.

**Fix direction:** resolve the adapter per version from `storageType` through a registry of all configured adapters
(keyed by a unique name, not the type); scope `isBinaryHashInUse()` by storage type; close probe streams; document
the hardlink trade-off or verify `binaryFileHash` on restore (C-39); add an orphan cleanup command (I-106).

## [ ] B-223 – `VersionsCleanupTask`: one failing delete stalls the cleanup for good; unbounded `NOT IN` lists and `days` result; auto-save cleanup crashes on `null`

**Files:** `lib/Maintenance/Tasks/VersionsCleanupTask.php:46-62` (`:59-60`), `:98-173` (`:99`, `:105-106`, `:157`, `:165`);
`models/Version/Dao.php:118-169` (`:120-123`, `:132-136`, `:139-158`)

- `$version->delete()` is not wrapped in `try` / `catch`. One storage error (permissions, S3 outage, a `TypeError` from
  C-38) aborts the task. The next run returns the same version first (no `ORDER BY`), so the cleanup **never
  progresses** again and version storage grows without limit.
- `doAutoSaveVersionCleanup()`: `Version::getById($id)->delete()` on `null` (deleted in between, e.g. by
  `VersionDeleteHandler`) → `Error`, which aborts the run before `doVersionCleanup()` is retried.
- `$ignoredIds` starts with **all** auto-save ids and grows with every public, scheduled or "newer than the element"
  version. It is `implode()`d into `NOT IN (…)` of every query in every loop iteration, so the query grows without
  bound (statement size, `max_allowed_packet`, full scans).
- The `days` branch has no `LIMIT`. After enabling `days` on an old installation it returns millions of ids in one
  array; the `> 1000` stop only exists in the `steps` branch.
- The `steps` branch keeps `steps + 1` non-public versions (`HAVING COUNT(*) > steps + 1`, `LIMIT steps + 1, …`). Check
  this against the label of the system setting.
- `Logger::info()` once per element and `Logger::debug()` once per version flood the maintenance log.

**Fix direction:** catch `\Throwable` per version, log it and skip the version; null-check; iterate with an id cursor
(`id > :lastId ORDER BY id LIMIT n`) instead of a growing `NOT IN`; bound the `days` query; check public / scheduler
state in SQL instead of loading every version (I-107).

## [ ] B-224 – `Version\Listing` returns `null` entries and hides SQL errors; deleting an element leaves its auto-save versions

**Files:** `models/Version/Listing/Dao.php:29-41`, `:48-60`, `:72-79`; `lib/Messenger/Handler/VersionDeleteHandler.php:42-65`;
`models/Element/Traits/VersionDaoTrait.php:31-47`

- `load()` selects ids and calls `Version::getById()` per id (N+1). A row deleted in between (cleanup, delete handler)
  puts `null` into the list, which then breaks `VersionDeleteHandler` (`$version->delete()` on `null` → `Error` →
  `catch (Exception)` misses it → the whole message is nacked and retried), the admin version list and
  `getSafeVersionInfo()` (same pattern as B-213).
- `getTotalCount()` returns `0` on any SQL error.
- `VersionDeleteHandler` uses the default listing, which adds `autoSave = 0`. Auto-save versions of a deleted element
  and their storage stay until the 72-hour cleanup. If the element is restored from the recycle bin in the meantime,
  `getLatestVersion()` (which includes the user's auto-save) offers the editor the stale auto-save draft.

**Fix direction:** hydrate from one `SELECT *` (no per-id lookup, no `null`); let SQL errors propagate;
`setLoadAutoSave(true)` in the delete handler and catch `\Throwable` per version (I-105).

## [ ] B-225 – `VersionTest` cannot run on PHPUnit 10 / 11 and never loads, deletes or re-saves a version

**Files:** `tests/Service/Element/VersionTest.php:38-79`, `:294-300`, `:324-333`; `composer.json:154`

- `MockBuilder::setMethods()` was removed in PHPUnit 10, and `composer.json` requires `^10.0 || ^11.0`. Every storage
  test calls it through `mock*StorageAdapter()`, so they error before asserting (not run locally, `vendor/` is not
  installed). The mocks mock nothing (`setMethods(null)`); real instances would do.
- The tests only inspect rows after saving. No test loads a version (C-38 would fail at once), deletes versions that
  share a binary (C-38), re-saves a version (B-219), restores an asset version, or checks that `disable()` is
  re-enabled after an exception (B-215).
- `tearDown()` drops `versionsData` unconditionally; SQL is built by string concatenation (`:70-74`, `:90`, `:327`).

**Fix direction:** `onlyMethods([])` or plain instances; add the missing cases (I-109).

---

## User (Scope 10)

## [ ] B-226 – A stale role id in `users.roles` crashes `isAllowed()`, the merged-perspective / translation-language helpers and `PermissionChecker`

**Files:** `models/User.php:265-316`, `:332-343`, `:529-546`, `:586-599`, `:627-641`; `models/User/AbstractUser.php:203-265`;
`models/Element/PermissionChecker.php:200-210`

- Every role lookup dereferences the result without a check: `User\Role::getById($roleId)->getPermission()` /
  `->getClasses()` / `->getDocTypes()` (`:276`, `:288`, `:301`), `User\UserRole::getById($role)->getPerspectives()` /
  `->getWebsiteTranslationLanguages*()` (`:535`, `:592`, `:633`) and `PermissionChecker.php:203-204`. A `null` →
  `Error: Call to a member function … on null`.
- `null` is reachable without C-40:
  - a role deleted outside `AbstractUser::delete()` (SQL, migration, an import that rewrites `users`), or a failure in
    the middle of `cleanupUserRoleRelations()`, which is not transactional (B-229);
  - `setRoles()` accepts any integer: a role **folder** id or a user id (the admin role tree sends folder ids when a
    folder node is checked; not verified locally) → `Role::getById()` filters by `type = 'role'` → `null`;
  - `setRoles('1,,2')` / a trailing comma → `intval('')` = `0` (the system user) → `null`.
- The affected (non-admin) user gets a 500 on nearly every admin request until an admin removes the role id; there is
  no log entry that names the bad id.

**Fix direction:** skip unknown roles (log a warning) in all lookups; filter `setRoles()` input (`> 0`, unique, only ids
of type `role`); load all roles once per user with one `Role\Listing` query (I-112).

## [ ] B-227 – Merged role data is cached on the user object and never invalidated; the perspective helpers return undefined, duplicate or no longer allowed perspectives

**Files:** `models/User.php:75-85`, `:310-313`, `:332-343`, `:510-517`, `:529-546`, `:553-556`, `:567-579`, `:586-599`,
`:627-641`; `models/User/UserRole.php:314-365`

- `mergedPerspectives`, `mergedWebsiteTranslationLanguagesEdit` and `…View` are computed once per object. `setRoles()`,
  `setPerspectives()` and `setWebsiteTranslationLanguages*()` do not reset them, and a role save does not reach the
  users that are already loaded. The user object is shared through the runtime cache (`user_<id>`), so a role change in
  a Messenger worker / long-running process (and in the same request, e.g. "save user, then check access") is not
  seen until the process ends. `isAllowed('…', 'class' | 'docType')` re-reads the roles on every call, so the same
  user gets fresh class permissions but stale perspectives / translation languages.
- `getMergedPerspectives()` does not `array_unique()` (the translation helpers do) and does not intersect with the
  defined perspectives (`getDefinedPerspectiveNames()` is only used when nothing is assigned). A perspective that was
  deleted or renamed in the config stays "allowed": `getFirstAllowedPerspective()` can return a name that no longer
  exists, and the admin UI then loads an undefined perspective.
- `getActivePerspective()` returns the stored value without checking `isAllowed($p, 'perspective')`. A user whose
  perspective was taken away keeps opening it. The getter also writes `'default'` into the model (side effect), and
  `'default'` may not be allowed or defined.
- Admins are allowed every perspective in `isAllowed()` (`:267-269`), but `getFirstAllowedPerspective()` for an admin
  with assigned perspectives returns only the first assigned one; for an admin with none, the first defined one.

**Fix direction:** reset the merged caches in the setters (or compute them in a resolver service, I-112); intersect with
the defined perspectives and `array_unique()`; validate `activePerspective` against the allowed list and fall back to
`getFirstAllowedPerspective()`.

## [ ] B-228 – `setPassword()` silently ignores `null`, `''` and every value of ≤ 4 characters; `setUsername(null)` throws

**Files:** `models/User.php:106-113`, `:147-152`; `models/UserInterface.php:24-40`; `models/User/AbstractUser.php:144-149`;
`lib/Tool/Authentication.php:160-184`

- `setPassword()` keeps the old value when `strlen((string) $password) <= 4`. The value is a **hash** (callers pass
  `Authentication::getPasswordHash()`), so the check is not a password policy. Its only effect: a password can never
  be removed. `setPassword(null)` / `setPassword('')`, which `verifyPassword()` / `isValidUser()` treat as "login
  disabled" (`Authentication.php:162-165`, `:183`), is a silent no-op, and the user can still log in.
- Code that hands in a plain short password by mistake also gets no error.
- `UserInterface::setUsername(?string)` accepts `null`, but forwards to `setName(string)` → `TypeError` under
  `strict_types`.

**Fix direction:** accept `null` / `''` as "no password"; reject non-hash values explicitly (e.g. `password_get_info()`)
instead of ignoring them; make `setUsername()` non-nullable or handle `null` (I-110, I-117).

## [ ] B-229 – `AbstractUser::delete()` is not transactional and leaves user data behind

**Files:** `models/User/AbstractUser.php:203-265`; `models/User/Dao.php:30-52`; `models/User.php:416-448`;
`bundles/InstallBundle/dump/install.sql:192`, `:248`, `:334`, `:440`, `:723-796`

- `delete()` deletes children recursively, rewrites every user that has the role (`cleanupUserRoleRelations()`, one full
  `save()` per user), deletes the row and resets element ownership (`User\Dao::delete()`, 7 `UPDATE`s) **without** a
  transaction. A failure in the middle (e.g. one related user that cannot be saved, B-230) leaves half the subtree
  deleted and `roles` pointing at deleted roles (B-226).
- Not cleaned up on user delete: the avatar files `/user-image/user-<id>.png` and `user-thumbnail-<id>.png` in the
  `admin` storage; `translations_messages.userOwner` / `userModification`; `notes.user`; `edit_lock.userId` (locks stay
  until they expire); `gridconfigs` / `importconfigs` / `gridconfig_favourites` / `gridconfig_shares` /
  `importconfig_shares` (`ownerId`, `sharedWithUserId`). Only the workspace tables have a foreign key. Notifications
  and scheduled tasks are B-193 / C-37.
- The parent-cache reset uses `$parentUserId > 1` (`:234`): a parent with id `1` keeps its cached `children`.
- `delete()` on an unsaved object (`id === null`) throws "Deleting the system user is not allowed!".

**Fix direction:** wrap `delete()` in `beginTransaction()` / `commit()` / `rollBack()` like `save()`; clear the other
user references in `User\Dao::delete()` (or add FKs, I-120); delete the avatar files after commit; fix `> 1` → `> 0`.

## [ ] B-230 – `AbstractUser::save()`: a failed insert leaves a phantom id; name validation is incomplete and blocks logins of legacy users

**Files:** `models/User/AbstractUser.php:161-198`; `models/User/AbstractUser/Dao.php:95-103`, `:123-148`;
`lib/Tool/Authentication.php:124-158`, `:160-179`, `:234-245`; `bundles/InstallBundle/dump/install.sql:461`, `:489`

- `create()` sets the new id on the model before `update()`. If `update()` throws, the transaction is rolled back but
  the model keeps the id. A retry (`save()` again) takes the update path, `UPDATE … WHERE id = <rolled-back id>`
  matches no row, and the call "succeeds" without writing anything.
- `PRE_ADD` / `PRE_UPDATE` are dispatched before validation; listeners act on users that are then rejected.
- Name rules are split: the regex (`[a-zA-Z0-9\-\.~_@]+`) in the model, `strlen >= 2` in the DAO **after** the
  `INSERT`, no maximum (the column is `varchar(50)`: longer names fail with a DB error or are truncated in non-strict
  SQL mode, and `type_name` uniqueness is then checked on the truncated value). `preg_match()` on a `null` name →
  `TypeError` (strict).
- Rows that do not match the regex (users created before the check, by SQL or by an SSO provider with e.g. `+` in the
  e-mail-style name) can no longer be saved at all. `Authentication::verifyPassword()` saves the user when the hash
  needs a rehash (`:172-176`), and `authenticateToken()` / `generateTokenByUser()` save to set / clear the recovery
  token (`:136-137`, `:241-242`). For such users login after an algorithm change and password recovery end in a 500.
- The system user (id `0`) is treated as new by `if (!$this->getId())`: `save()` tries to `INSERT` a second `system`
  row and fails on `type_name`.

**Fix direction:** validate (regex, 2–50 characters, non-null) before events and before `create()`; reset the id on
rollback; use `getId() === null` for "new"; update password hash / token / last login with targeted DAO updates
instead of a full `save()` (I-114).

## [ ] B-231 – `User::getImage()` ignores the requested size after the first call; the fallback avatar depends on the admin bundle; `setImage()` crashes on unreadable input

**Files:** `models/User.php:416-427`, `:429-448`, `:454-485`, `:735-746`; `models/UserInterface.php:136-142`, `:210`

- The thumbnail is stored under one path per user (`user-thumbnail-<id>.png`) regardless of `$width` / `$height`. The
  first call fixes the size forever; `getImage(200, 200)` after `getImage()` returns the 46×46 thumbnail.
- `getFallbackImage()` points to `PIMCORE_WEB_ROOT/bundles/pimcoreadmin/img/avatar.png`, i.e. the installed assets of
  admin-ui-classic-bundle. Without them (Studio-only / headless setups, assets not installed) `fopen()` returns
  `false` with a warning, although the method documents `resource`.
- If the stored original is not a loadable image, every call reads the original into a temp file, fails and returns
  the fallback (no negative cache, no error).
- `setImage()`: `fopen($path, 'rb')` → `false` for a missing / unreadable path → `writeStream(false)` `TypeError`. The
  old original and thumbnail are deleted **before** the new file is read, so a failing upload removes the existing
  avatar. The content is not checked (any file is stored as `.png`). On an unsaved user the paths are `user-.png` /
  `user-thumbnail-.png`, shared by all unsaved users.

**Fix direction:** per-size thumbnail paths (`user-thumbnail-<id>-<w>x<h>.png`); ship the fallback in core or return
`null`; validate the upload (Symfony Mime) and write before deleting; require a saved user (I-116).

## [ ] B-232 – `setTwoFactorAuthentication('<key>', null)` wipes the whole 2FA configuration, including `required`

**Files:** `models/User.php:685-702`, `:709-723`; `models/User/AbstractUser/Dao.php:140-142`;
`lib/Security/User/User.php:93-131`

```php
if (is_string($key) && $value === null && strlen($key) > 3) {
    $this->twoFactorAuthentication = json_decode($key, true);   // 'secret' → null
}
```

- The method is overloaded: a string with a `null` value is treated as a JSON document. Every real key is longer than
  3 characters (`required`, `enabled`, `secret`, `type`), so the natural way to clear a value,
  `setTwoFactorAuthentication('secret', null)`, decodes `"secret"` → `null` and drops the whole array. The next
  `getTwoFactorAuthentication()` restores the defaults: `required = false`, `enabled = false`. A user who was forced
  to use 2FA is no longer forced, without any error. (Whether admin-ui-classic-bundle calls it this way was not
  verified locally.)
- The same happens for a stored value that is not valid JSON: `json_decode()` returns `null` and the 2FA settings
  silently reset. `json_decode()` of a JSON scalar (`"1"`) stores a non-array and the next `[$key]` access fails.
- `json_encode(null)` is written as the string `'null'` for users without 2FA data (`Dao.php:141`).

**Fix direction:** split the API (`setTwoFactorAuthenticationConfig(array)` / `setTwoFactorAuthenticationValue(string,
mixed)`), decode JSON only in the DAO with `JSON_THROW_ON_ERROR`, and always merge onto the defaults (I-117).

## [ ] B-233 – Permission definitions and lists: `generatePermissionList()` returns `null`, `Definition::create()` reports success after a failed save

**Files:** `models/User/UserRole.php:129-195`; `models/User/Permission/Definition.php:75-114`;
`models/User/Permission/Definition/Dao.php:31-41`; `models/User/Permission/Definition/Listing/Dao.php:48-55`;
`models/User/Listing/AbstractListing/Dao.php:33-73`

- `generatePermissionList()` starts with `$permissionInfo = null` and declares `: array`. With no definitions (fresh
  schema, failed install, test DB) it throws a `TypeError` (the `@todo` in the docblock).
- `setPermissions('')` stores `['']`; `setPermissions()` does not dedupe, and `setPermission()` / `getPermission()` use
  loose `in_array()`.
- `Definition\Dao::save()` catches every exception and logs a warning. `Definition::create()` then returns the
  definition as if it was stored (bundle installers rely on it to register their permissions).
- Both listings return `0` from `getTotalCount()` on any SQL error, and `User\Listing\…\Dao::load()` silently drops
  rows whose `getById()` fails, so `count(load())` and `getTotalCount()` disagree.

**Fix direction:** initialise `[]`; normalise permission lists like `prepareArray()`; let DB errors propagate (I-118).

## [ ] B-234 – Workspaces: uninitialised typed properties, `NULL` `cpath` breaks loading, duplicate entries abort the whole user save

**Files:** `models/User/Workspace/AbstractWorkspace.php:34-46`, `:240-280`; `models/User/Workspace/Dao.php:29-54`;
`models/User/UserRole/Dao.php:55-85`; `models/User/UserRole.php:92-111`; `bundles/InstallBundle/dump/install.sql:503-572`

- `int $userId`, `int $cid` and `string $cpath` have no default. `getCid()` / `getUserId()` / `getCpath()` on a new
  workspace → `Error: must not be accessed before initialization`.
- `cpath` is nullable in all three tables. Hydration skips `NULL` (`setValues($row, true)`), so a row with `cpath IS
  NULL` (inserted by SQL / an older version, or a workspace saved without a path) leaves `$cpath` uninitialised and
  the first `getCpath()` (admin user editor, permission export) throws.
- `UserRole::update()` deletes all workspaces and re-inserts them with a plain `INSERT`. Two entries for the same `cid`
  (or the same `cpath`, unique key `cpath_userId`) → duplicate-key error → the whole user save is rolled back, with a
  DB error message instead of a validation error.
- `Workspace\Dao::save()` builds the table name with `instanceof`; any other subclass gets `''` and fails in
  `getValidTableColumns('')`.

**Fix direction:** defaults / nullable types; dedupe by `cid` before insert (or upsert); a table name per workspace class
(I-119); `cpath NOT NULL` (I-120).

## [ ] B-235 – `getById()` / `getByName()` / listings return different instances; stale or partially hydrated user objects

**Files:** `models/User/AbstractUser.php:44-104`; `models/User/AbstractUser/Dao.php:35-81`, `:164-168`;
`models/User/UserRole/Dao.php:33-53`; `lib/Security/User/UserChecker.php:38-51`; `lib/Tool/Authentication.php:36-68`,
`:251-262`

- `getById()` uses the runtime cache, `getByName()` does not. Login loads the user by name (`UserProvider`), the
  rest of the request by id: two objects, and changes on one (e.g. `setLastLogin()`, roles) are invisible to the
  other.
- `setLastLoginDate()` writes the DB but not the model. `UserChecker::checkPostAuth()` throttles by
  `getLastLogin()` (`:45-47`); with the stale value every auth in the same process writes again, and
  `authenticateSession()` writes on every call without any throttle (side effect noted in B-114).
- `getByPasswordRecoveryToken()` is defined only on `AbstractUser\Dao`, so the token user is hydrated **without**
  workspaces (`UserRole\Dao` only adds them for `getById()` / `getByName()`). `authenticateToken()` reloads by id
  before saving, but any other caller that saves this object deletes all of the user's workspaces.
- Abstract-class lookups hydrate any row type into `User` (C-40); `create()` passes `$values` without
  `ignoreEmptyValues`, so `['parentId' => null]` → `TypeError` in `setParentId(int)`.

**Fix direction:** one cached hydration path for id, name and token; update the model in `setLastLoginDate()`; load
workspaces in every user lookup (I-115).

## [ ] B-236 – `Authentication` token / password helpers built on the user model

**Files:** `lib/Tool/Authentication.php:124-158`, `:160-179`, `:224-262`; `models/User.php:118-133`

- `authenticateToken()` clears the recovery token with a full `save()` (events, all columns, all workspaces deleted
  and re-inserted) **before** it checks expiry and `$adminRequired`; the timestamp check switches the process time zone
  (`date_default_timezone_set('UTC')`) although `time()` is time-zone independent.
- `generateToken(string $username)` passes `User::getByName()` (nullable) to `generateTokenByUser(User)` → `TypeError`
  for an unknown name instead of a defined error.
- The token is stored in plain text (`passwordRecoveryToken`, no index → full table scan per lookup). A DB read
  (backup, replica, SQL injection elsewhere) gives working login links for 24 hours.

**Fix direction:** targeted DAO updates for token and password; `null` check in `generateToken()`; store a hash of a
random token plus an expiry column (I-117, I-120).

## [ ] B-237 – User model small defects

**Files:** `models/User.php:212-219`, `:265-316`, `:318-325`, `:490-508`, `:748-758`; `models/User/AbstractUser/Dao.php:164-168`;
`models/User/UserRole/Folder.php:44-78`; `models/User/Listing/AbstractListing.php:22-29`

- `isAllowed()` does not check `isActive()`: a deactivated admin passes every check. Code that acts "as" a stored user
  id (scheduled tasks C-37, the `Translation\Dao` editor check via `userModification`) treats inactive users as
  allowed.
- `User::getPermission()` returns only the user's own permissions (roles are only merged in `isAllowed()`); callers
  that use `getPermission()` for a check get `false` for role-granted permissions (I-112).
- `setLanguage()` ignores `'0'` / `''` without error and accepts any string (not checked against the admin languages).
- `setContentLanguages()` does not trim, dedupe or validate against `Tool::getValidLanguages()`; `'de,,en'` yields an
  empty language entry.
- `setDatetimeLocale()` returns `User` (not `static`) and is missing from `UserInterface`.
- `setLastLoginDate()` builds `$data` by assigning to an undefined variable.
- `UserRole\Folder::getChildren()` caches the children forever and does not update `hasChildren`; `hasChildren()`
  caches independently, so after `setChildren([])` + `getChildren()` they can disagree.
- `AbstractListing` documents `@method Model\User|false current()` for role listings too.

---

## Tool (Scope 11)

## [ ] B-238 – `SettingsStore::set()` and `TmpStore` writes swallow every DB error: configs, installer state and admin session data are lost silently

**Files:** `models/Tool/SettingsStore/Dao.php:32-46`; `models/Tool/TmpStore/Dao.php:30-52`;
`lib/Config/LocationAwareConfigRepository.php:186-189`; `lib/Extension/Bundle/Installer/SettingsStoreAwareInstaller.php:98`,
`:103`; `models/Element/Service.php:1318`; `bundles/InstallBundle/dump/install.sql:409-430`

- Both DAOs wrap the upsert in `catch (Exception) { return false; }` without logging. No caller checks the `bool`:
  - `LocationAwareConfigRepository::saveConfig()` (thumbnails, perspectives, custom views, … when the write target is
    `settings-store`) → the admin UI reports "saved", the config is unchanged, and `stopMessengerWorkers()` still
    runs;
  - `SettingsStoreAwareInstaller::markInstalled()` / `markUninstalled()` → a bundle is installed (migrations ran) but
    stays "not installed", and the next install re-runs the install steps;
  - `Element\Service::saveElementToSession()` (`TmpStore::set()`) → the editor's unsaved element state is lost; the
    next request loads the stored version.
- Reachable failures: `id` / `scope` / `tag` longer than 190 characters (`varchar(190)`, strict SQL mode error;
  `LocationAwareConfigRepository` keys are user-defined config names), `max_allowed_packet` for large serialized
  elements, deadlocks, a lost connection in a worker.

**Fix direction:** let the exception propagate (or log it and throw a domain exception); validate id / scope / tag
length up front; change the return type to `void` (I-123).

## [ ] B-239 – `TmpStore`: scalar types are lost, `add()` is not atomic, `unserialize()` failures become `false`, expired ids are returned

**Files:** `models/Tool/TmpStore.php:84-134`, `:196-203`; `models/Tool/TmpStore/Dao.php:30-80`;
`lib/Maintenance/Tasks/TmpStoreCleanupTask.php:35-38`; `lib/Maintenance/Executor.php:114-128`;
`lib/Tool/MaintenanceModeHelper.php:77-100`

- Only arrays and objects are serialized. `int`, `float` and `bool` go to the `longtext` column as strings and come
  back as `string`: `TmpStore::set('x', false)` → `getData() === ''`, `true` → `'1'`, `5` → `'5'`. The model
  property is `mixed`, so nothing tells the caller (the maintenance executor and `LogCleanupTask` cast with `(int)`
  as a workaround).
- `add()` is "insert if absent" implemented as `get()` + upsert. Two processes calling `add()` for the same id both
  write, and the second overwrites the first (used as a marker by `MaintenanceModeHelper::addEntry()` and the
  deferred-thumbnail / video-job code). `add()` also returns `true` without storing when the id exists, so activating
  maintenance mode for a new session keeps the old session id while an entry exists.
- `unserialize()` runs without `allowed_classes`. A row whose class was renamed or removed (upgrade, uninstalled
  bundle; e.g. a stored video `Processor` or thumbnail `Config`) unserialises to `__PHP_Incomplete_Class` or `false`
  with a warning; `get()` still returns the item, and callers (`Video\Thumbnail\Processor::execute()`,
  `Asset\Service.php:382-383`) use its data as a config / processor object.
- `getIdsByTag()` returns expired ids (only `get()` checks `expiryDate`). `update()` defaults to **1 day** while
  `add()` / `set()` default to **7 days**; `lifetime = 0` means "default", negative lifetimes store an already expired
  item.
- `date` / `expiryDate` are nullable in the schema; a `NULL` is skipped on hydration and `get()` →
  `getExpiryDate()` → `Error` (uninitialised `int`).

**Fix direction:** serialize every non-string value (or store a type column like `SettingsStore`); `INSERT … ON
DUPLICATE KEY` free "add" via `INSERT IGNORE` + affected rows; `unserialize($data, ['allowed_classes' => …])` and treat
failures as "not found"; filter `expiryDate` in `getIdsByTag()`; one default lifetime (I-124).

## [ ] B-240 – `SettingsStore`: `NULL` data crashes `get()`, `getScope()` is never `null`, `settype()` coerces silently

**Files:** `models/Tool/SettingsStore.php:146-182`; `models/Tool/SettingsStore/Dao.php:59-74`;
`tests/Model/Tool/SettingsStoreTest.php:45`

- `getById()` hydrates with `assignVariablesToModel()` and then calls `setData($item['data'] ?? null)`. `data` is a
  nullable `longtext`; for a `NULL` row (migrations and manual SQL write the table directly) `setData(null)` →
  `TypeError` (`float|bool|int|string`), so `SettingsStore::get()` throws instead of returning `null` / the item.
- `setData()` runs twice during hydration (once from `setValues()` before `type` is known, depending on column order,
  then again from the DAO); the first call stores the raw string.
- `setScope(null)` stores `''`, so `getScope(): ?string` never returns `null`; the test compares with `assertEquals()`
  and does not notice.
- `settype()` never fails: `'abc'` as `int` → `0`, `'false'` as `bool` → `true`, `'1.5e3'` as `int` → `1500`.
  `SettingsStore::set()` does not check that `$data` matches `$type` (`set('x', 'abc', TYPE_INTEGER)` stores `abc`, and
  reading returns `0`).
- An invalid `type` in the DB (`enum` prevents it, but a schema without the enum from older installs does not) throws a
  generic `Exception` from `setType()` during hydration, which `get()` does not catch.

**Fix direction:** treat `NULL` data as "not set" or as the type's empty value; hydrate in one step (type first);
validate `$data` against `$type` on write; keep `null` scope or change the getter to `string` (I-123).

## [ ] B-241 – Email log rows end up empty: field overflow and DB errors are swallowed after an empty `INSERT`

**Files:** `models/Tool/Email/Log/Dao.php:59-115`; `models/Tool/Email/Log.php:381-402`; `lib/Helper/Mail.php:146-197`;
`bundles/InstallBundle/dump/install.sql:211-232`

- `save()` first runs `INSERT INTO email_log () VALUES ()` (`create()`), then one `UPDATE` with all fields. The
  `UPDATE` is wrapped in `catch (Exception) { Logger::emerg(...) }`, so any failure leaves a row with only an id
  (no recipients, subject, date) that the admin grid shows as an empty entry, while the HTML / text bodies are still
  written to storage under that id.
- Failures are easy to hit with strict SQL mode: `params` is `TEXT` (64 KB) and holds the JSON of all mail params
  (objects, arrays, rendered snippets); `subject` and `from` are `varchar(500)`, `replyTo` `varchar(255)`.
- `json_encode()` of the params can fail (invalid UTF-8 in a param, depth) and returns `false`, which is written as
  `''`.
- `requestUri` is stored HTML-escaped (`htmlspecialchars()` in `logEmail()`), so the admin shows `&amp;` and any
  search on the URI must escape too.

**Fix direction:** one `INSERT` with all columns; `JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE`; truncate or
widen the columns (`MEDIUMTEXT` for params, I-127); let the exception reach `Mail::sendWithoutRendering()`, which already
catches and logs it; store the raw URI.

## [ ] B-242 – Loaded email logs throw on most getters; `getParams()` returns a different structure and re-saving double-wraps it

**Files:** `models/Tool/Email/Log.php:47-117`, `:142-145`, `:178-181`, `:186-202`, `:225-232`, `:251-254`, `:273-276`,
`:297-300`, `:321-368`, `:471-474`; `models/Tool/Email/Log/Dao.php:117-159`

- `requestUri`, `from`, `subject`, `sentDate`, `params`, `modificationDate`, `emailLogExistsHtml/Text` are non-nullable
  typed properties without defaults. Hydration skips `NULL` columns:
  - `modificationDate` has **no column**, so `getModificationDate()` throws on every loaded log;
  - `requestUri` is `NULL` for every mail sent from CLI, cron, maintenance or a Messenger worker (`logEmail()` sets it
    only when `$_SERVER['REQUEST_URI']` exists) → `getRequestUri()` throws;
  - `from` is `NULL` when the message had no From (before the default is applied in some paths) → `getFrom()` throws.
  On a new, unsaved log `getHtmlLog()` / `getTextLog()` / `getParams()` throw too.
- `getParams()` documents "Returns the dynamic parameter", but the stored JSON is the **tree-grid format** produced by
  `prepareLoggingData()` (`[{key, data|children}]`), not the original params. Calling `save()` on a loaded log runs
  that array through `prepareLoggingData()` again, nesting every entry one level deeper (`key` → `0`, `data` → child
  `data`). `json_decode()` failure → `null` → `TypeError` (`: array`).
- `prepareLoggingData()` drops `bool` and `float` values and objects without `getId()` (enums, `Stringable`, value
  objects): the key is logged with no value. `createJsonLoggingObject()` has a dead branch that returns a JSON string
  which `save()` would encode a second time.
- `getBodyHtml(): ?string` / `setReplyTo(string)` / `getReplyTo(): ?string` disagree on nullability.

**Fix direction:** nullable properties with `null` defaults; drop `modificationDate` or add the column; separate
`getParamsTree()` from the raw params and never re-encode a loaded tree; log scalars of all types and `Stringable` /
`BackedEnum` values (I-125).

## [ ] B-243 – Email log storage and cleanup: orphaned body files, `null` items in listings, the whole history loaded at once

**Files:** `models/Tool/Email/Log.php:186-202`, `:283-340`, `:373-379`; `models/Tool/Email/Log/Listing/Dao.php:33-66`;
`bundles/CoreBundle/src/Command/EmailLogsCleanupCommand.php:60-67`; `bundles/InstallBundle/dump/install.sql:231`

- `email_log.documentId` has `ON DELETE CASCADE`: deleting an email document deletes its log rows in the DB, but the
  `email-<id>-html.log` / `-txt.log` files stay in the `email_log` storage forever (nothing iterates files). These
  files contain full message bodies (personal data).
- `Listing\Dao::load()` adds the result of `Log::getById()` unconditionally; a row deleted in between (cleanup command,
  cascade) puts `null` into the list. `EmailLogsCleanupCommand` then calls `null->delete()` → `Error`.
- `EmailLogsCleanupCommand` calls `load()` for all matching rows at once; each `getById()` adds two storage
  `fileExists()` calls (S3 / remote storage round trips). On a large backlog this runs out of memory or time, and
  there is no maintenance task, so logs and bodies grow without bound unless the command is scheduled manually.
- `getTotalCount()` returns `0` on SQL errors (both email listings).
- File names are built from `getId()`; for an unsaved log they are `email--html.log` / `email--txt.log`.

**Fix direction:** delete files in a `POST_DELETE` path that also covers document deletion (or drop the cascade and
clean up in code); skip `null` in `load()`; batch the cleanup (`LIMIT` + loop) and add a retention maintenance task
(I-126).

## [ ] B-244 – Email blocklist: addresses are not normalised; listing and getters have side effects

**Files:** `models/Tool/Email/Blocklist.php:38-86`; `models/Tool/Email/Blocklist/Dao.php:33-76`;
`models/Tool/Email/Blocklist/Listing/Dao.php:33-56`

- `setAddress()` stores the string as given; `getByAddress()` compares it with `=`. Case is ignored only through the
  column collation; leading / trailing spaces, `mailto:` prefixes or display-name forms (`"Name <a@b.c>"`) never
  match. No validation that the value is an email address at all.
- `getCreationDate()` / `getModificationDate()` write `time()` into the model when empty (getter side effect); the DAO
  relies on it, so `if (!$this->model->getCreationDate())` in `save()` is always false.
- `Listing\Dao::load()` re-queries every row by address (N+1) and silently drops vanished rows; `getTotalCount()`
  hides SQL errors.
- `@method void delete()*` in the listing docblock (stray `*`); the listing has no `delete()`.

**Fix direction:** normalise (`trim`, lower-case, `Address::create()->getAddress()`) on write and lookup and reject
invalid addresses; hydrate listings from one `SELECT *` (I-125).

---

## DataObject root files (Scope 12)

## [ ] B-245 – Classification store fallback lookup: unbounded recursion on mutual fallback languages, and the last fallback chain wins

**Files:** `models/DataObject/Classificationstore.php:299-322`, `:356-359`, `:406-409`; `lib/Tool.php:136-151`

```php
foreach ($fallbackLanguages as $l) { /* direct hit → return */ }
foreach ($fallbackLanguages as $l) {
    $data = $this->getFallbackValue($groupId, $keyId, $l, $fielddefinition);   // no visited set, result overwritten
}
return $data;
```

- The second loop recurses into every fallback language's own fallbacks with no visited set. With a mutual or
  self-referencing configuration (`de → en`, `en → de`, or `de → de`), every read of a key that is empty in both
  languages recurses until "Maximum call stack size reached" (`zend.max_allowed_stack_size`, PHP ≥ 8.3) or a segfault.
  Empty keys are the normal case, so a valid-looking language setting breaks every page and admin view that reads
  the store. `Tool::getFallbackLanguagesFor()` does not prevent cycles.
- `doGetFallbackValues()` is hard-coded to `true` (`:406-409`); unlike `Localizedfield` the lookup cannot be switched
  off, so the admin and exports are affected too.
- Wrong result even without a cycle: the loop keeps the value of the **last** fallback chain. For `de → [en, fr]`,
  `en → [it]` with only `it` filled, the `it` value is found for `en` and then overwritten by the empty result for
  `fr` → `null`.
- The recursion also runs when the group or key has no data at all (`array_key_exists()` only guards the first loop).

**Fix direction:** iterate breadth-first over `[language => true]` visited, return the first non-empty value, stop on
revisits; make `doGetFallbackValues()` follow `Localizedfield::doGetFallbackValues()` (or a dedicated flag).
`Localizedfield::getLocalizedValue()` (`:472-483`) has the same cycle when both languages exist; it is guarded only by
`languageExists()`, so it should get the same visited set.

## [ ] B-246 – `Classificationstore::getItems()` with inherited values: parent array values replace the child's own values element by element

**Files:** `models/DataObject/Classificationstore.php:97-110`, `:452-463`, `:494-509`

```php
$a2[$key] = $this->mergeArrays($a2[$key], $value);   // arguments swapped on every recursion level
```

- `mergeArrays($child, $parent)` is meant to let the child win. The recursive call swaps the arguments, so the winner
  alternates by depth: group level child, key level parent, language level child, **value level parent**.
- Every array-valued key type (multiselect, countrymultiselect, languagemultiselect, table, …) is merged index by index with the parent's value, the parent winning: child `['a']`, parent `['b', 'c']`
  → `['b', 'c']`; child `['a', 'b', 'c']`, parent `['x']` → `['x', 'b', 'c']`. An explicitly emptied child multiselect
  (`[]`, stored on purpose, `:212-214`) shows the parent's values.
- When a key's type changed (child value array, parent value scalar or the other way round) the recursion receives a
  non-array → `TypeError` under `strict_types`.
- `getItems()` with inheritance is what exports, the frontend and `doGetDataForEditMode()` (`ClassDefinition/Data/Classificationstore.php:214`)
  read. `getLocalizedKeyValue()` resolves inheritance separately and returns the right value, so the two APIs disagree.

**Fix direction:** merge only down to the language level (`[group][key][language]`) and treat the value as opaque
(`$a2[$key] = $value` below that level); better, build the inherited view from `getLocalizedKeyValue()` so one rule
applies.

## [ ] B-247 – Classification store calculated-value keys: the getter returns the edit-mode string, and the setter throws

**Files:** `models/DataObject/Classificationstore.php:339-345`, `:178-206`; `models/DataObject/Service.php:1458-1507`, `:1509-1530`;
`models/DataObject/ClassDefinition/Data/CalculatedValue.php:28`

- `getLocalizedKeyValue()` returns `Service::getCalculatedFieldValueForEditMode()` (`?string`, admin representation).
  Object, localized and brick getters use `getCalculatedFieldValue()` (`Concrete.php:319-321`, `Localizedfield.php:404-407`,
  `Objectbrick/Data/AbstractData.php:173-177`). In frontend / API code, numeric or array results come back as strings,
  calculator classes' `compute()` is not used (only `getCalculatedValueForEditMode()`), and expression results are
  cast to `string` (`:1498`). (Both variants return a syntax error's message as the value, `:1499`, `:1556`; that
  part belongs to `Service`, Scope 6.)
- `$this->getObject()` is passed without a null check → `TypeError` for a store without object.
- `setLocalizedKeyValue()` for a calculated-value key: `CalculatedValue` does not implement
  `ResourcePersistenceAwareInterface`, so `$dataDefinition->getDataForResource()` (`:188`) is an undefined method
  `Error` when the store is clean and has an object. Admin data for calculated keys is added to the edit-mode payload
  (`ClassDefinition/Data/Classificationstore.php:183-196`); whether the UI sends them back on save was not verified
  locally.

**Fix direction:** use `getCalculatedFieldValue()` in the getter; reject or ignore calculated keys in
`setLocalizedKeyValue()`.

## [ ] B-248 – Classification store changes that are not saved, and a dirty check that is wrong both ways

**Files:** `models/DataObject/Classificationstore.php:183`, `:185-206`, `:216-226`, `:235-238`, `:427-437`;
`models/DataObject/Classificationstore/Dao.php:54-56`

- `removeGroupData()`, `setGroupCollectionMappings()` and `setGroupCollectionMapping()` do not mark the store dirty.
  `Dao::save()` returns early for a clean store, so on their own these changes are silently lost (API / import code);
  they only reach the DB when another change happens to make the store dirty.
- `removeGroupData()` leaves the group in `activeGroups` and in the collection mapping.
- `setLocalizedKeyValue()` activates the group (`:183`) even when the call **removes** a value (`null` / `''`): clearing
  a key of an inactive group switches the group on.
- Dirty comparison: `serialize(getDataForResource())` of old vs new value. For `EncryptedField` (random IV) and
  `Password` (a new hash vs the stored hash) the values always differ → always dirty. For values whose resource form
  is not a full representation the check can miss changes. Only the first key is compared; once dirty, nothing is.
- `Dao::load()` hydrates every row through `setLocalizedKeyValue()` (`Dao.php:267`), i.e. it runs the dirty check
  (two `getDataForResource()` calls and `serialize()`) and the group activation for every loaded value, and relies on
  `resetDirtyMap()` at the end.

**Fix direction:** mark `_self` dirty in every mutator (and clean up `activeGroups` / mappings in `removeGroupData()`);
activate the group only for non-empty values; compare with `EqualComparisonInterface::isEqual()` where available; give
the DAO a raw hydration method that bypasses dirty detection (I-130).

## [ ] B-249 – Classification store: `null` key configurations, stores without object and uninitialised state end in `Error`s

**Files:** `models/DataObject/Classificationstore.php:48`, `:59`, `:124-127`, `:178-180`, `:339-347`, `:357-393`,
`:452-463`; `models/DataObject/Classificationstore/DefinitionCache.php:27`;
`models/DataObject/Classificationstore/Service.php:50-77`

- `DefinitionCache::get($keyId)` returns `null` for a deleted or unknown key: `getLocalizedKeyValue()` calls
  `->getType()` on it (`:340`), `setLocalizedKeyValue()` passes it to `getFieldDefinitionFromKeyConfig()` (not nullable
  → `TypeError`). Reading a key that was deleted while objects still reference it (templates, exports) crashes instead
  of returning `null`. `Dao::load()` already skips such rows with a log line (`Dao.php:238-243`).
- `getFieldDefinitionFromKeyConfig()` returns `null` for a key with an empty definition → `->isEmpty()` / `->preGetData()`
  `Error`s in the getter.
- No validation that `$keyId` belongs to `$groupId` (any key can be stored under any group).
- Inheritance branch: `$object = $this->getObject(); $object->getClass()` (`:368-369`) without a null check; parent store
  compared through `$classificationStore->object->getId()` (`:385`, direct property access).
- `getAllDataFromField()` calls the parent's getter and reads `->items` / `->activeGroups` from the result without a
  type check; a getter that returns `null` (custom `preGetValue()` hook, field removed from the parent's class
  version) gives a warning and then a `TypeError` in the merge function.
- `$fieldname` is a typed property without default: `getFieldname()` / the getter's inheritance branch throw for a
  store that was never given a field name (e.g. `new Classificationstore($items)`).
- `$object` admits `ElementDescriptor` (`:48`) but `getObject(): ?Concrete` would throw a `TypeError` for it;
  `Localizedfield::setObject()` (`:225-229`) handles the descriptor, this class does not.

**Fix direction:** return `null` (and log) for unknown keys / empty definitions; validate the group-key relation in the
setter; guard the inheritance paths; give `$fieldname` a default or require it in the constructor; narrow `$object` to
`?Concrete`.

## [ ] B-250 – `DataObject\Listing`: the object-type filter is ANDed to an unparenthesised condition – folders and variants leak through `OR` conditions

**Files:** `models/DataObject/Listing.php:37`, `:63-75`; `lib/Model/Listing/Dao/QueryBuilderHelperTrait.php:56-74`

```php
$condition .= ' AND ';
$condition .= ' ' . $tableName . ".type IN ('" . implode("','", $objectTypes) . "')";
// "key = 'a' OR key = 'b'"  →  "key = 'a' OR key = 'b' AND objects.type IN ('object')"
```

- `AND` binds tighter than `OR`: everything matched by the first `OR` branch is returned **regardless of type**. A listing
  restricted to `[OBJECT_TYPE_OBJECT]` (the usual way to hide variants or folders) returns variants / folders whenever
  the condition contains a top-level `OR`. The published filter right below wraps the condition in parentheses
  (`:69-70`); the type filter does not.
- The published filter then wraps the already broken string, so `published = 1` still applies to all rows; only the
  type restriction is lost.
- `setObjectTypes()` accepts any array; the values are concatenated into SQL unescaped (public API, injectable when
  callers pass request input, e.g. a type filter in a custom endpoint). `setObjectTypes([])` silently removes the type
  filter.
- Same pattern for every class listing (`DataObject\<Class>\Listing` inherits it).

**Fix direction:** `'(' . $condition . ') AND type IN (:objectTypes)'` with `ArrayParameterType::STRING`; validate the
values against `DataObject::$types` in `setObjectTypes()`; add a test with an `OR` condition (I-134).

## [ ] B-251 – `DefinitionModifier`: modified layouts are out of sync with the definition's field list; `Localizedfields` and `Block` containers are half-supported

**Files:** `models/DataObject/DefinitionModifier.php:116-150`, `:176-190`; `models/DataObject/ClassDefinition.php:337`,
`:708-760`; `models/DataObject/ClassDefinition/Dao.php:159`; `models/DataObject/ClassDefinition/Layout.php:239-244`;
`models/DataObject/ClassDefinition/Data/Localizedfields.php:347-353`; `models/DataObject/ClassDefinition/Data/Block.php:493-505`

- The modifier edits the layout tree in place via `setChildren()`. `ClassDefinition` (and the brick / collection
  definitions) keep a separate `fieldDefinitions` map that is rebuilt only by `setLayoutDefinitions()`.
  `Layout::setChildren()` invalidates nothing. A modifier call on `$class->getLayoutDefinitions()` followed by
  `$class->save()` validates (`ClassDefinition.php:337`), generates DDL (`ClassDefinition/Dao.php:159`) and PHP code
  from the **old** field list: added fields get no column and no getter, removed fields keep both. The caller has to
  know to call `setLayoutDefinitions($class->getLayoutDefinitions())` again; neither the class nor the docs say so
  (no `doc/` page mentions `DefinitionModifier`).
- `findField()` descends into `Data\Localizedfields` and treats it as a parent (append / prepend / replace / remove work
  inside it), but `insert()` refuses every `Data` instance (`:180-182`), so `insertFieldsFront/Back(…, 'localizedfields', …)`
  returns `false` although `Localizedfields` is a container with `setChildren()`.
- `Data\Block` also has `getChildren()` / `setChildren()`, but `findField()` does not descend into it: fields inside a
  block are not found (`removeField()` returns `false`).
- No validation despite the docblocks ("appends **valid** `$fieldsToAdd`"): duplicate field names, data fields inside
  data fields, or a `Localizedfields` inside `Localizedfields` are inserted silently and only fail on class save or at
  runtime.
- The name match is loose (`==`) and stops at the first hit in breadth-first-per-level order; a layout name equal to a
  field name hits whichever comes first.

**Fix direction:** accept the owning definition (or return the modified layout) and call `setLayoutDefinitions()` at
the end; treat every type with children (`Layout`, `Localizedfields`, `Block`) uniformly through one interface;
validate names and nesting; `===`.

---

# Scope 13 – DataObject `AbstractObject/**`, `Concrete/**`, `Folder/**`

> Notes
> - `AbstractObject/Dao.php`, `Concrete/Dao.php` and `Concrete/Dao/InheritanceHelper.php` were processed in Scope 6
>   (C-25, C-26, B-175, B-177, B-186, B-187, I-73, I-76, I-78) and are skipped.
> - `Folder/Dao.php` is an empty subclass of `AbstractObject\Dao` with no behaviour of its own: no bug item. Its
>   improvement is I-135.

---

# Scope 14 – DataObject `Listing/**`, `Fieldcollection/**`, `Localizedfield/**`

> Notes
> - Scanned and skipped files: see the Scope 14 notes in [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md) (C-44, C-45).
> - `Listing/Dao.php`, `Fieldcollection/Data/Dao.php` and `Localizedfield/LanguageTableDoesNotExistException.php` have
>   no functional defect of their own beyond what Scope 6 / 12 already lists (B-178, B-179, B-180, B-250). Their
>   improvements are I-138–I-140.

## Field collection / listing (Scope 14)

## [ ] B-252 – Field-collection definitions go stale after save and delete: runtime cache and OPcache are not refreshed

**Files:** `models/DataObject/Fieldcollection/Definition.php:95-121` (`:111-112`), `:126-182`, `:205-215`, `:231-258`;
`models/DataObject/Objectbrick/Definition.php:196-198` (for comparison); `lib/File.php:48-56`;
`models/DataObject/ClassDefinition.php:451`; `models/DataObject/Localizedfield/Dao.php:834-840`

- `getByKey()` caches the included definition in `RuntimeCache` under `fieldcollection_<key>`. `save()` and `delete()`
  never update that entry. `Objectbrick\Definition::save()` does it explicitly ("for localized fields getting a fresh
  copy").
  - During `save()`, `createUpdateTable()` → `Localizedfields::classSaved()` → `Localizedfield\Dao::createUpdateTable()`
    reads the container through `Fieldcollection\Definition::getByKey()`. If another instance was cached earlier in
    the process, the localized table is rebuilt from the **old** field list. New localized fields get no column, and
    columns of fields that the old definition does not have are dropped (`removeUnusedColumns()`). Saving a
    `new Definition()` for an existing key does this: `ClassManager::setupFieldcollection()` in tests, custom
    importers, and `ClassesRebuildCommand` (listing instances are separate from `getByKey()` instances, see C-45).
  - After `delete()`, `getByKey()` in the same process still returns the deleted definition. A class save in the same
    process (`Fieldcollections::classSaved()`) then re-creates the tables that were just dropped. `Fieldcollection::save()`
    keeps accepting items of the deleted type.
- The definition file is written with `Filesystem::dumpFile()`. `ClassDefinition` uses `File::putPhpFile()`, which
  calls `opcache_invalidate()`. With `opcache.validate_timestamps=0` (the usual production setting) or within
  `revalidate_freq`, other FPM workers and Messenger consumers keep `include`-ing the old compiled definition.
  `Fieldcollection\Data\Dao::save()` then writes the old field list: new fields are not persisted, and an insert of a
  removed column fails with "Unknown column", inside the object save transaction.

**Fix direction:** `RuntimeCache::set($cacheKey, $this)` at the end of `save()`, `RuntimeCache::set($cacheKey, null)` in
`delete()`, and write the definition through `File::putPhpFile()`. The generated PHP class dumpers
(`PHPFieldCollectionClassDumper`) need the same OPcache invalidation.

## [ ] B-253 – `Definition::delete()` leaves tables and allowed-type references behind: re-creating the key brings back old rows as ghost items

**Files:** `models/DataObject/Fieldcollection/Definition.php:231-258`; `models/DataObject/Fieldcollection/Definition/Dao.php:43-47`;
`models/DataObject/Localizedfield/Dao.php:48-57`; `models/DataObject/ClassDefinition/Data/Fieldcollections.php:618-638`;
`models/DataObject/Fieldcollection/Dao.php:44-63`; `models/DataObject/Objectbrick/Definition.php:480-531` (for comparison)

- `Definition\Dao::delete()` drops only `object_collection_<key>_<classId>`. The localized table
  `object_collection_<key>_localized_<classId>` (created by `Localizedfield\Dao::createUpdateTable()`) is never dropped.
- `Definition::delete()` drops tables only for classes whose `Fieldcollections` field **currently** allows the key.
  `Fieldcollections::classSaved()` never drops anything when a type is removed from `allowedTypes`, so tables of
  classes that removed the type earlier stay forever.
- The classes keep the deleted key in `allowedTypes`, and their definition files are not re-saved.
  `Objectbrick\Definition::delete()` removes the key and saves the class.
- When a collection with the same key is created again, `createUpdateTable()` reuses the leftover tables
  (`CREATE TABLE IF NOT EXISTS`, columns added / dropped to match the new fields). `Fieldcollection\Dao::load()`
  then returns every old row (`id`, `fieldname`, `index`) as an item of the new type, with empty or converted values,
  in every object that had items. The next object save persists them. The leftover allowed-type entry silently makes
  the new type allowed again in all those classes.
- The order makes it worse: both files are unlinked first (`@unlink`, errors ignored), and `DROP TABLE` runs after
  that. A failing drop leaves tables without a definition, and no `POST_DELETE` event is sent.
- A table created by `Definition::save()` for a class that already allows the key (the re-create case above) is not
  encrypted for an encrypted class. Only `Definition\Dao::classSaved()` calls `handleEncryption()`, and only during a
  class save.

**Fix direction:** drop the store and localized tables for **every** class (`SHOW TABLES` with escaped exact names, or
iterate all class ids), remove the key from `allowedTypes` and save the affected classes as the brick does. Drop the
tables before removing the files, and call `handleEncryption()` in `createUpdateTable()`. Add a test (I-141).

## [ ] B-254 – `Definition::save()`: missing validations, and files are written before the DDL runs

**Files:** `models/DataObject/Fieldcollection/Definition.php:126-182`, `:302-313`;
`models/DataObject/Objectbrick/Definition.php:97-142`, `:221-242` (for comparison);
`models/DataObject/Fieldcollection/Definition/Dao.php:33-41`, `:49-104`

- No table-name length check. The key is limited only by `^[a-zA-Z]\w*$`, and `object_collection_<key>_localized_<classId>`
  has 29 fixed characters. A key of about 30 or more characters makes `CREATE TABLE` fail with "Identifier name …
  is too long" (MySQL limit 64). Bricks run `checkTablenames()` before writing anything.
- No container rules: bricks reject a `localizedfields` nested in a `block` (`enforceBlockRules()`), collections
  accept it, and it fails only at runtime.
- `generateClassFiles()` writes the definition file and the PHP class **before** `createUpdateTable()` runs for the
  classes. A failing DDL (the length limit above, a column type change that MySQL refuses for existing data, a lock
  timeout) leaves the new definition and class active on top of the old tables. `PRE_*` has been dispatched,
  `POST_*` is not. Every following object save fails with "Unknown column" until someone saves the collection again
  successfully.
- `DataContainerAwareInterface::preSave()` runs for the fields before validation is complete: the forbidden-name
  loop and `preSave()` are interleaved, so `preSave()` side effects have already happened for earlier fields when a
  later field fails the check.

**Fix direction:** validate everything first (key, names, table-name length per class, nesting – share the brick
implementation, I-136), then run the DDL, then write the files. If writing fails, the tables are a superset of what
is needed, so that order is safe.

## [ ] B-255 – `Definition::getByKey()` builds an `include` path from an unvalidated key

**Files:** `models/DataObject/Fieldcollection/Definition.php:95-121`, `:271-274`; `models/DataObject/Traits/LocateFileTrait.php:25-34`;
`models/DataObject/Listing/Concrete.php:123-131`; `models/DataObject/Fieldcollection/Data/AbstractData.php:74-77`

- The key is validated only in `save()` (`isForbiddenName()`, `^[a-zA-Z]\w*$`). `getByKey()` passes any string to
  `sprintf('%s/fieldcollections/%s.php', …)` and `include`s the file if it exists. A key such as `../../public/foo`
  executes any `*.php` file reachable on disk. The return value is discarded unless it is a `Definition`, but the side
  effects of the file (and "cannot redeclare" fatals) happen anyway. The result is also cached in `RuntimeCache`
  whatever its type.
- Public entry points in this repo: `DataObject\Listing\Concrete::addFieldCollection($type, …)`, and the item `type`
  of unserialized data (`AbstractData::getDefinition()`). The classic admin passes request ids to
  `Fieldcollection\Definition::getByKey()`, which was **not verified locally**. The users who can reach it there can
  usually also edit definitions (C-32), so this is hardening rather than a privilege escalation.
- `Objectbrick\Definition::getByKey()` has the same pattern.

**Fix direction:** return `null` from `getByKey()` for keys that do not match the save regex (and log it). Do not cache
results that are not a `Definition`.

## [ ] B-256 – Field-collection small defects

**Files:** `models/DataObject/Fieldcollection/Data/AbstractData.php:74-77`, `:110-123`, `:135-153`;
`models/DataObject/Fieldcollection/Definition/Listing.php:27-37`, `:57-77`;
`models/DataObject/Fieldcollection/Definition/Dao.php:33-41`; `models/DataObject/Listing/Dao.php:61-76`

- `AbstractData::getDefinition()` is declared `: Definition`, but `getByKey()` returns `?Definition`. When the definition
  file is missing (deleted collection while its PHP class still exists, a node without the custom-config file, a failed
  `@unlink()`), you get a `TypeError` instead of a clear message. This happens in `__sleep()` (`getLazyLoadedFieldNames()`),
  i.e. during a **cache write** of the owning object, and in `Data\Dao::save()` inside the save transaction.
- `Definition\Listing::load()` returns whatever each file returns (`include`). A file that is not a definition (an
  editor backup `foo.php`, a broken export) produces `int` / `false` entries in a `Definition[]`, and consumers call
  `->getKey()` / `->save()` on them. `glob()` returns `false` on error, and `foreach (false)` emits a warning.
- `Definition\Dao::getTableName()` / `getLocalizedTableName()` accept a definition without a key and return
  `object_collection__<classId>`.
- `DataObject\Listing\Dao::load()` silently skips ids whose `getById()` returns `null` (deleted concurrently, broken
  class). A page then has fewer items than `setLimit()`, and `count($list)` after `load()` differs from
  `getTotalCount()`. Nothing is logged.

**Fix direction:** `getDefinition(): ?Definition` or throw a dedicated `DefinitionNotFoundException` with the type;
type-check the listing results, and skip and log anything that is not a `Definition`. Guard `glob()`. Assert a key in
the DAO. Log skipped ids in the listing.

---

# Scope 15 – DataObject `Classificationstore/**` configuration models

> Notes
> - Scanned and skipped files: see the Scope 15 notes in [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md) (no critical item).
> - `Classificationstore/Dao.php`, `DefinitionCache.php` and `Service.php` were processed before (B-181, C-43, B-248,
>   B-249) and are skipped.
> - `AbstractModel::setValues()` is called in two ways here: `assignVariablesToModel()` skips `null` columns (the property
>   keeps its default or stays **uninitialised**), plain `setValues($row)` passes `null` to the setter (`TypeError` for a
>   non-nullable parameter, also without `strict_types`). Several items below follow from that difference.

## Classification store configuration (Scope 15)

## [ ] B-257 – `CollectionConfig::save()` / `delete()` cache `null`: `getById()`, `getByName()` and the collection listing return `null` for the rest of the process

**Files:** `models/DataObject/Classificationstore/CollectionConfig.php:55-79` (`:60-62`), `:86-113` (`:91-93`), `:172-206`,
`:267-276` (`:270-271`); `models/DataObject/Classificationstore/CollectionConfig/Listing/Dao.php:34-47` (`:41`);
`lib/Cache/RuntimeCache.php:112-152`

- `removeCache()` "removes" the runtime entries with `RuntimeCache::set($key, null)`. `RuntimeCache::isRegistered()` is
  `ArrayObject::offsetExists()`, which returns `true` for a `null` value. `getById()` / `getByName()` then return the
  cached `null` until the process ends. `KeyConfig` and `GroupConfig` use `offsetUnset()` and are not affected.
- Effect after **updating** a collection (`save()` with an id): `CollectionConfig::getById($id)` and
  `getByName($name, $storeId)` return `null` in the same request / CLI run / worker. Import scripts that do
  "`getByName() ?? new CollectionConfig()`" create a **duplicate** collection on the next iteration; code that calls
  `getById()->…` crashes with `Error`.
- `CollectionConfig\Listing::load()` hydrates through `getById()` and stores the result unchecked, so the listing
  contains `null` entries (typed `CollectionConfig[]`); consumers that call `->getName()` crash.
- In a long-running worker (Messenger consumer, RoadRunner / FrankenPHP worker mode) the `null` stays until the
  worker restarts.

**Fix direction:** unset the runtime entries (`offsetUnset()`, as `KeyConfig` does) – best through the shared helper of
I-142; skip `null` in the listing (B-265). Add a test (I-148).

## [ ] B-258 – Config caches go stale: the old name stays cached after a rename, and invalidation runs before the write

**Files:** `models/DataObject/Classificationstore/KeyConfig.php:100-127`, `:184-229` (`:202`, `:213-217`), `:314-327`;
`GroupConfig.php:97-123`, `:192-226`, `:287-300`; `CollectionConfig.php:86-113`, `:172-206`, `:267-276`;
`models/DataObject/ClassDefinition/Data/Classificationstore.php:1066-1090` (`:1072`, `:1078`)

- `getByName($name, $storeId)` caches the config in the runtime **and the persistent cache** under
  `cs_<type>config_<storeId>_<md5(name)>`, without tags and without lifetime. `removeCache()` computes the keys from the
  **current** (already changed) name and store id. After `setName('colour')->save()` on the key loaded as `color`, the
  entry for `color` is never removed: `KeyConfig::getByName('color')` keeps returning the stale object (old name, same
  id) on every node until the whole cache is cleared. Moving a config to another store has the same effect.
- When a new config is later created with the old name, `getByName()` still returns the renamed one. Consumers that
  resolve by name write to the wrong key / group: `Classificationstore::denormalize()` (`:1072`, `:1078`), import
  scripts and project code. The values end up under another key id in `object_classificationstore_data_*`.
- `save()` / `delete()` invalidate **before** the DAO write and before `PRE_UPDATE` / `PRE_DELETE`. A listener of those
  events, or a concurrent request, that calls `getById()` / `getByName()` (or `DefinitionCache::get()`) in that window
  re-populates the persistent cache with the old row, which then stays after the write. `KeyConfig::save()` clears
  `DefinitionCache` at the same early point.
- `getById()` and `getByName()` cache separate instances of the same row, so a change through one is not visible
  through the other in the same process.

**Fix direction:** remember the loaded name and store id (or read the old row) and invalidate both the old and the new
name key; invalidate **after** the DAO write (and after `POST_*` listeners could have re-read it, i.e. at the very end);
tag the persistent entries (`classificationstore_config`) so that `Cache::clearTags()` can drop them. Share the logic
(I-142). Add a test (I-148).

## [ ] B-259 – `CollectionGroupRelation\Dao::getById()` joins the group table on `colId`: wrong group name, or an uninitialised one

**Files:** `models/DataObject/Classificationstore/CollectionGroupRelation/Dao.php:38-61` (`:45`, `:56`);
`models/DataObject/Classificationstore/CollectionGroupRelation.php:37-42`, `:51-61`;
`models/DataObject/Classificationstore/CollectionGroupRelation/Listing/Dao.php:42-43` (correct join, for comparison)

- The query is `… LEFT JOIN classificationstore_groups ON collectionrelations.colId = groups.id`. It must join on
  `groupId`. `name` and `description` therefore come from the group whose **id equals the collection id** – an
  unrelated group – and `getByGroupAndColId()` returns a relation with the wrong name / description.
- If no group has that id, the joined columns are `NULL`, `assignVariablesToModel()` skips them, and `$name` stays
  uninitialised: `getName()` throws "must not be accessed before initialization".
- The listing DAO joins on `groupId` correctly, so the same relation looks different depending on how it was loaded.
- The `NotFoundException` message says "KeyGroupRelation".

**Fix direction:** join on `` `%1$s`.`groupId` = `%2$s`.`id` `` and select explicit columns; fix the message. Add a test
(I-148).

## [ ] B-260 – Saving a disabled key silently deletes all its group relations; new keys are disabled by default

**Files:** `models/DataObject/Classificationstore/KeyConfig/Dao.php:102-134` (`:112-119`);
`models/DataObject/Classificationstore/KeyConfig.php:68`, `:200-229`;
`models/DataObject/Classificationstore/KeyGroupRelation.php:63`; `bundles/InstallBundle/dump/install.sql:662-673`

- `KeyConfig\Dao::update()` runs `DELETE FROM classificationstore_relations WHERE keyId = ?` whenever `enabled` is false –
  on **every** save of a disabled key, not only when it changes from enabled to disabled. The delete is a side effect
  hidden in the column-mapping loop, runs before the key row is updated, outside a transaction, and dispatches no
  event.
- `KeyConfig::$enabled` defaults to `false`. A key created through the API without `setEnabled(true)` is hidden from
  the default listing (`enabled = 1`, B-261) and loses every group assignment on its next save, e.g. when a script
  creates the key, assigns it to groups, and then saves the key again to change the definition.
- Disabling a key in the admin for a moment (to hide it) removes it from all groups permanently. Enabling it again
  does not restore the relations (sorter, mandatory flag); they must be re-created by hand in every group. The values
  stored in `object_classificationstore_data_*` stay but are no longer shown or validated, because the key is not in
  any active group.
- `KeyGroupRelation::$enabled` defaults to `true`, the opposite of the key default.

**Fix direction:** decide whether disabling should remove memberships at all (the relation listing already exposes the
key's `enabled` flag, and `ClassDefinition\Data\Classificationstore` skips disabled keys at `:853-855`). If it should,
do it only on the transition enabled → disabled, explicitly in `KeyConfig::save()` inside a transaction, with an event
and a log entry. Default new keys to `enabled = true` (BC note) or require the flag. Add a test (I-148).

## [ ] B-261 – `KeyConfig\Listing::setIncludeDisabled(true)` returns **only** disabled keys

**Files:** `models/DataObject/Classificationstore/KeyConfig/Listing.php:30`, `:50-58`;
`models/DataObject/Classificationstore/KeyConfig/Listing/Dao.php:66-76` (`:68`)

- The condition is `includeDisabled ? '(enabled is null or enabled = 0)' : 'enabled = 1'`. "Include disabled" therefore
  **excludes** every enabled key. Nothing in this repo calls it; the classic admin is **not verified locally**.
- There is no way to list all keys through the listing: callers have to run two listings or bypass the model.
- The filter is put in front of the user condition without a table prefix, so a condition with a join or subquery that
  also has an `enabled` column becomes ambiguous.

**Fix direction:** make the flag mean what it says (`includeDisabled` → no `enabled` filter) and add an explicit
`onlyDisabled` / enum filter for the current behaviour (BC: deprecate the old meaning with `trigger_deprecation()`).
Qualify the column (`classificationstore_keys.enabled`).

## [ ] B-262 – Config creation is `INSERT` of an empty row + `UPDATE`: incomplete rows break the key and group listings

**Files:** `models/DataObject/Classificationstore/KeyConfig/Dao.php:82-89`, `:136-145`;
`models/DataObject/Classificationstore/KeyConfig.php:129-135`, `:45`, `:60`;
`models/DataObject/Classificationstore/KeyConfig/Listing/Dao.php:34-50` (`:43`);
`models/DataObject/Classificationstore/GroupConfig/Dao.php:87-94`, `:131-140`; `GroupConfig/Listing/Dao.php:34-49` (`:42`);
`CollectionConfig/Dao.php:75-82`, `:119-128`; `StoreConfig/Dao.php:77-84`, `:118-122`; `lib/Model/AbstractModel.php:163-190`;
`bundles/InstallBundle/dump/install.sql:621-660`

- Every config DAO creates a row with `insert($table, [])` and then runs `update()`. The two statements are not in a
  transaction. If the update fails (lost connection, lock timeout, a project trigger / stricter `sql_mode`), the empty
  row stays: `name = ''`, `type = NULL`, `storeId = NULL`, `definition = NULL`.
- `update()` writes only **initialised** properties (`getObjectVars()`). `KeyConfig::create()` saves a key whose `name`
  and `type` were never set, so the row has `type = NULL` even when the update succeeds.
- `KeyConfig\Listing\Dao::load()` and `GroupConfig\Listing\Dao::load()` hydrate with `setValues($row)` (nulls are passed
  on): `setType(null)`, `setStoreId(null)`, `setDefinition(null)` throw `TypeError`. One such row breaks **every**
  key listing (the admin key grid, project code), and every group listing for a group row with `storeId = NULL`.
  `getById()` uses `assignVariablesToModel()` (nulls skipped), so the same row loads there but `getType()` / `getName()`
  then fail on an uninitialised property.
- The `PRE_ADD` event is dispatched with `id = null`, `POST_ADD` after both statements; listeners cannot distinguish a
  half-created row.
- `KeyConfig::title` is written as `NULL` into `title NOT NULL` when the definition has no title, and `GroupConfig::parentId`
  as `NULL` into `parentId NOT NULL`. Both work only because the connection sets `sql_mode = ''`
  (`bundles/CoreBundle/config/pimcore/default.yaml:148`); with a strict mode the update fails and leaves the empty row
  described above.

**Fix direction:** one `insert()` with all columns (and `lastInsertId()` afterwards), or both statements in a
transaction; write schema-conform values (`''` for `title`, `0` for `parentId`); validate `name` / `type` before saving
(`KeyConfig::create()` without arguments cannot produce a valid key → deprecate it or give it parameters); hydrate the
listings through `assignVariablesToModel()` with explicit casts (I-144). Add a test (I-148).

## [ ] B-263 – `StoreConfig::delete()` and `CollectionConfig::delete()` leave orphaned configuration behind

**Files:** `models/DataObject/Classificationstore/StoreConfig.php:115-120`; `StoreConfig/Dao.php:89-92`;
`models/DataObject/Classificationstore/CollectionConfig.php:172-181`; `CollectionConfig/Dao.php:87-90`;
`bundles/CoreBundle/src/Command/DeleteClassificationStoreCommand.php` (`execute()`);
`bundles/InstallBundle/dump/install.sql:621-694`

- `StoreConfig::delete()` deletes only the `classificationstore_stores` row. There is no foreign key from groups, keys or
  collections to the store, so all of them stay, still referenced by class definitions (`storeId` of the
  `Classificationstore` field) and by object data. `getByName($name, $deletedStoreId)` keeps finding them; the admin
  can no longer show them under any store. The `pimcore:classificationstore:delete-store` command does the full
  cleanup (data rows, keys, groups, collections, `Cache::clearAll()`), the model method does none of it.
- `classificationstore_collectionrelations` has a foreign key on `groupId` only. `CollectionConfig::delete()` leaves all
  relation rows of the collection (`colId`) behind. They are invisible as long as the id is not reused, but are
  returned by `CollectionGroupRelation\Listing` conditions that do not filter by an existing collection, and they
  come back if ids are reused (table copied / truncated, `AUTO_INCREMENT` reset).
- Neither method removes the persisted object data / group-collection mappings that reference the deleted ids.

**Fix direction:** `CollectionConfig::delete()` deletes its relations (and add `FOREIGN KEY (colId) … ON DELETE CASCADE`
via migration, I-147). `StoreConfig::delete()` either refuses to delete a store that still has configs or runs the
same cleanup as the command (move it into a service used by both), in a transaction.

## [ ] B-264 – `KeyGroupRelation\Listing::setResolveGroupName(true)` never fills the group name, and makes plain conditions ambiguous

**Files:** `models/DataObject/Classificationstore/KeyGroupRelation.php:56-59`;
`models/DataObject/Classificationstore/KeyGroupRelation/Listing/Dao.php:33-66` (`:41`, `:58`), `:78-110`;
`lib/Model/AbstractModel.php:175-190`

- With the flag the DAO joins `classificationstore_groups` and selects `… .name AS groupName`. `KeyGroupRelation` has
  a `$groupName` property but neither a setter nor a getter. `setValue('groupName', …)` only calls setters, so the
  value is dropped and the property stays uninitialised (also invisible in `getObjectVars()`). The feature never
  worked through the model; whether the classic admin uses `getDataArray()` instead is **not verified locally**.
- The listing uses a comma join (`FROM relations, keys[, groups]`) with the user condition in front. With the groups
  table joined, a condition on `name`, `description`, `id`, `storeId`, `creationDate` or `modificationDate` (all present
  in keys and groups) fails with "Column … is ambiguous". The same condition works without the flag.
- `getDataArray()` selects `*` from the comma join: the duplicate column names overwrite each other in the
  associative row (`id`, `name`, `description` of the group replace those of the key).

**Fix direction:** add `getGroupName(): ?string` / `setGroupName()`, rewrite the query with explicit `INNER JOIN … ON`,
aliases and an explicit column list (I-144).

## [ ] B-265 – Classification store configuration small defects

**Files:** `models/DataObject/Classificationstore/Group.php:89-92`; `Key.php:61-64`; `GroupConfig.php:257-262`;
`CollectionConfig.php:237-242`; `CollectionGroupRelation.php:44`, `:51-61`; `CollectionGroupRelation/Listing/Dao.php:65-77`;
`KeyGroupRelation/Listing/Dao.php:54-56`; `StoreConfig/Listing/Dao.php:34-47`, `:54-61`; `KeyConfig/Listing/Dao.php:57-64`;
`GroupConfig/Listing/Dao.php:56-63`; `CollectionConfig/Listing/Dao.php:54-61`

- `Group::getKeyConfigById(): KeyConfig` returns `KeyConfig::getById()`, which is `?KeyConfig` → `TypeError` for a key
  deleted after the relation listing was read (or a stale cache, B-258). `Key::getFieldDefinition(): Data` returns
  `Service::getFieldDefinitionFromKeyConfig()`, which is `?Data` (empty definition, B-249) → `TypeError`.
- `GroupConfig::getRelations()` / `CollectionConfig::getRelations()` concatenate `$this->id`: for an unsaved config the
  condition is `groupId = ` → SQL syntax error instead of an empty list.
- `CollectionGroupRelation::$sorter` has no default: a relation created without `setSorter()` throws on `getSorter()`,
  and a row with `sorter = NULL` (the column is nullable) loads with an uninitialised sorter
  (`ClassDefinition\Data\Classificationstore.php:908` calls `getSorter()` on every relation).
  `getByGroupAndColId(?int $groupId = null, ?int $colId = null)` casts `null` to `0` and queries for id 0.
- `CollectionGroupRelation\Listing\Dao`: `load()` joins the groups table, `getTotalCount()` and `getDataArray()` do not.
  A condition on a group column works in `load()` and fails (→ `0`, see below) in `getTotalCount()`, so paging is
  wrong.
- `KeyGroupRelation\Listing\Dao::load()` runs `json_decode($row['definition'], true)` on a nullable JSON column:
  `json_decode(null)` is deprecated since PHP 8.1, and the key's definition becomes `{"mandatory":…}` only; a
  definition `{}` round-trips to `[]`.
- `StoreConfig\Listing` and `CollectionConfig\Listing` add the result of `getById()` unchecked → `null` entries for rows
  deleted concurrently (and always after B-257).
- `getTotalCount()` of the key, group, collection, store and collection-relation listings catches every `Exception` and
  returns `0`: a broken condition looks like an empty result. The key-relation listing lets the exception through.

**Fix direction:** nullable returns or a clear `NotFoundException` in `Group` / `Key`; return `[]` from `getRelations()`
for unsaved configs and bind the id; default `sorter = 0` and required ids in `getByGroupAndColId()`; one FROM/JOIN
builder for load / count / data array; `json_decode($row['definition'] ?? '[]', true) ?: []`; skip `null` entries; let
count exceptions surface (or log them) consistently.

---

# Scope 16 – DataObject `ClassDefinition/**` (CustomLayout, Service, Helper, Layout, DynamicOptionsProvider, Data/**)

> Notes
> - Scanned and skipped files: see the Scope 16 notes in [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md) (C-46–C-51).
> - Generated setters call `isEqual()` and mark the field dirty only when it returns `false`
>   (`ClassDefinition/Data.php:592-596`); `Localizedfield::setLocalizedValue()` does the same per language
>   (`Localizedfield.php:572-579`, `Localizedfield/Dao.php:142-148`). Every too-loose `isEqual()` therefore means a lost
>   change (B-266).
> - `Concrete::save()` passes `$omitMandatoryCheck = true` for unpublished objects (`Concrete.php:428-432`). Several
>   types gate *value* checks on that flag, not just the mandatory check (B-272).

## [ ] B-266 – Too-loose `isEqual()` implementations: real changes are not marked dirty and are not saved

**Files:** `models/DataObject/ClassDefinition/Data/Select.php:404-407`; `Datetime.php:323-329`; `DateRange.php:278-306`;
`ManyToOneRelation.php:475-481`; `Table.php:452-455`; `StructuredTable.php:407-413`; `Video.php:398-429`;
`Link.php:304-327`; `QuantityValue.php:328-341`; `Numeric.php:433-440`; `Slider.php:243-252`; `NumericRange.php:464-465`;
`models/DataObject/Traits/SimpleComparisonTrait.php:30-40`

- `Select`: `==` → `'1'`/`'01'`/`'1.0'`/`'1e1'`≙`'10'` are equal.
- `Datetime`: compares `format('Y-m-d H:i:s')` without offset → the same wall-clock time in another timezone (a
  different instant, a different stored timestamp) is "equal". `DateRange`: compares only `Y-m-d`, although full
  timestamps are stored → changing the time of day is never persisted.
- `ManyToOneRelation`: key is `getType() . getId()`; `getType()` is the subtype, so asset folder 5, document folder 5
  and object folder 5 are all `folder5`.
- `Table` / `StructuredTable` / `Link`: `isEqualArray()` uses `==` (and `Link` filters `''`/`'0'` away) → `"10"`→`"1e1"`,
  `0`→`null`, `''`→`'0'` are not detected.
- `Video`: for YouTube/Vimeo/Dailymotion only the id is compared; title, description and poster changes are ignored.
- `QuantityValue` / `Numeric`: `toNumeric(null)` is `0`; `Slider`: `(float)null` and a fixed epsilon `1e-5` (ignores
  `decimalPrecision`); `NumericRange`: `abs(null - x)` → `null`↔`0` is "equal".
- Concrete-level setters mark dirty for `Table`/`StructuredTable` unconditionally (`Data.php:604-605`), so for those two
  only localized / encrypted paths lose the change; for the other types every path is affected.

**Fix direction:** strict, null-aware comparisons: `getTimestamp()` for dates, element type + id for relations,
`===` on normalised arrays, all properties for `Video`, `(string)$a === (string)$b` for `Select`. Add one dirty-detection
test per type.

## [ ] B-267 – `Datetime` with column type `datetime`: DST-dependent round-trip, broken filter condition, invalid default values

**Files:** `models/DataObject/ClassDefinition/Data/Datetime.php:58-91`, `:190-201`, `:280-302`, `:309-321`

- Storage uses `date('Y-m-d H:i:s', $ts)` and `strtotime()` in the PHP default timezone: a timestamp in the repeated
  autumn DST hour comes back one hour off; changing `date.timezone` shifts every stored value.
- `getFilterConditionExt()`, `datetime` branch: `$params['brickPrefix']` read without `isset` (warning), field name in
  hand-written backticks instead of `quoteIdentifier()`, no fallback from `$params['name']` to `getName()`;
  `date('Y-m-d', $value)` with a string value → `TypeError` (strict types). The bigint branch ignores `brickPrefix`
  (ambiguous column in brick listings) and does not normalise the value to the start of the day.
- `setDefaultValue()`: `strtotime()` returning `false` is assigned to `?int` → `TypeError`; `''` does not clear an
  existing default. `doGetDefaultValue()` treats the timestamp `0` as "no default".

**Fix direction:** convert with an explicit timezone (UTC), build the filter like `Data::getFilterConditionExt()`,
validate and clear the default value properly.

## [ ] B-268 – `DateRange`: preview `TypeError` for an open range, `normalize()` / CSV export enumerate every day

**Files:** `models/DataObject/ClassDefinition/Data/DateRange.php:170-182`, `:189-200`, `:212-228`, `:146-157`

- `getVersionPreview()` passes `getEndDate()` (`null` for an open range, see C-49) to
  `UserTimezone::applyTimezone(DateTimeInterface)` (`lib/Tool/UserTimezone.php:45`) → `TypeError`.
- `normalize()` uses `CarbonPeriod::toArray()` and `getForCsvExport()` maps every day: a multi-year range creates
  thousands of `Carbon` instances / a huge CSV cell; `denormalize()` only uses the first and last element. With an open
  end the iteration is unbounded (Carbon behaviour **not verified locally**).
- `getDataFromGridEditor()` reads `start_date` / `end_date` without `isset`.

**Fix direction:** null-safe preview; normalise to `['start' => ts, 'end' => ?ts]` (keep reading the old list format);
export start/end only.

## [ ] B-269 – `Multiselect` / `Select`: values are not validated against options, commas, `maxItems` or column length

**Files:** `models/DataObject/ClassDefinition/Data/Multiselect.php:153-180`, `:243-246`, `:275-284`, `:476-500`;
`Select.php:81-88`, `:221-226`; `bundles/CoreBundle/config/pimcore/default.yaml:148`

- `Multiselect::preSave()` forbids commas only in configured option values (#5010). Values from API/import are not
  checked: `['a,b']` is stored as `a,b` and reloads as `['a', 'b']`.
- `Multiselect::checkValidity()` only checks "is array": `maxItems` and option membership are enforced by the admin UI
  only (**not verified locally**). `getDataFromEditmode()` returns the raw input (a string is not split).
- `Select::checkValidity()` accepts any string; strings longer than `columnLength` are truncated by MySQL under
  `sql_mode = ''`. `setColumnLength(0)` is silently ignored and there is no upper bound before the value reaches DDL.

**Fix direction:** validate in `checkValidity()` (scalar strings, no comma, `count() <= maxItems`, value in options when
`useConfiguredOptions()`, `mb_strlen() <= columnLength`).

## [ ] B-270 – `synchronizeWithMainDefinition()` misses settings: custom layouts keep stale options, providers, columns and restrictions

**Files:** `models/DataObject/ClassDefinition/Data/Multiselect.php:398-402` (compare `Select.php:240-248`);
`Table.php:443-450`; `Hotspotimage.php` (no override, `Image.php:213-216` does sync `uploadPath`);
`ManyToManyObjectRelation.php:401-405`; `ManyToManyRelation.php:568-578`; `ManyToOneRelation.php:437-441`;
caller `models/DataObject/Service.php:1031-1047`

- `Multiselect` copies only `maxItems` and `options` – not `optionsProviderType/Class/Data`, `defaultValue`,
  `renderType` (`Select` copies the provider settings).
- `Table` does not copy `columnConfigActivated` / `columnConfig` → stale column headers/keys.
- `Hotspotimage` does not sync `uploadPath`, `ratioX`, `ratioY`.
- Relations: `ManyToManyRelation` does not copy `classes`; `ManyToOneRelation` copies none of the allowed flags, types or
  classes; `ManyToManyObjectRelation` copies neither `classes` nor `visibleFields`. (Server validation uses the main
  definition, so this affects the UI only – rendering **not verified locally**.)

**Fix direction:** copy every configuration property that is not layout-specific; ideally one generic helper that copies
everything except an explicit list of layout properties (title, tooltip, width, …).

## [ ] B-271 – Unescaped HTML in diff / version previews of `Multiselect`, `Textarea`, `Wysiwyg`, `QuantityValueRange`

**Files:** `models/DataObject/ClassDefinition/Data/Multiselect.php:366-393` (`:378-379`); `Textarea.php:135-150`;
`Wysiwyg.php:238-249` (compare `:323-326`); `QuantityValueRange.php:215-222` (compare `AbstractQuantityValue.php:157`)

- `Multiselect::getDiffVersionPreview()` concatenates option labels into `<li>` unescaped (labels from a dynamic options
  provider can be object data).
- `Textarea::getDiffVersionPreview()` returns the raw text with `\n` → `<br>` as `type = html` → an editor stores
  `<img src=x onerror=…>` that runs in another user's diff view.
- `Wysiwyg::getDiffVersionPreview()` returns the HTML unsanitized while `getVersionPreview()` sanitizes it. The object
  property keeps the unsanitized input until reload (sanitizing happens only in `getDataForResource()` `:108-119`).
- `QuantityValueRange::getVersionPreview()` returns `__toString()` (translated unit abbreviation) unescaped.
- The diff previews are consumed by the object-merger bundle (not in this repo – **not verified locally**); the core
  version-preview XSS is C-47.

**Fix direction:** `htmlspecialchars()` (labels, Textarea before `nl2br()`, QuantityValueRange), sanitizer for the
Wysiwyg diff preview.

## [ ] B-272 – Value constraints are skipped when `$omitMandatoryCheck` is set (unpublished saves); `Numeric` integer check can never fire

**Files:** `models/DataObject/ClassDefinition/Data/Email.php:26-36`; `Textarea.php:161-170`; `Time.php:80-105`;
`Wysiwyg.php:54`, `:93-101`, `:199-211`; `Numeric.php:296-297`, `:349-371`, `:427-431`; `NumericRange.php:397-450`;
`models/DataObject/Concrete.php:120-124`, `:428-432`

- E-mail format (`Email.php:28`), `maxLength` (`Textarea.php:163`), `minValue`/`maxValue` (`Time.php:92`), and range /
  unsigned / integer checks of `Numeric` (`:349`) and `NumericRange` (`:397`) only run when `$omitMandatoryCheck` is
  false. Unpublished objects (and new objects with default values) persist invalid e-mails, overlong texts,
  out-of-range and negative values; they fail only later on publish. `QuantityValue` does it right (`:303-325`).
- `Numeric::toNumeric()` casts to `int` first in integer mode, so `str_contains((string)$data, '.')` (`:356`) is never
  true: `1.5` passes; `getDataFromEditmode('1.5')` silently returns `1`; a raw `1.5` set through a localized field is
  written to `bigint(20)` and rounded by MySQL to `2`.
- `Email`, `Textarea`, `Time` call `parent::checkValidity($data, $omitMandatoryCheck)` without `$params`.
  `Textarea`: `mb_strlen($data)` on a non-string → `TypeError`. `Wysiwyg::$maxCharacters` is never validated server-side.

**Fix direction:** gate only the mandatory check on `$omitMandatoryCheck`; check "is integer" on the raw input; forward
`$params`; add a `maxCharacters` check.

## [ ] B-273 – `User` data type: one user query per object load, all users reloaded on every save/wakeup, dangling ids silently nulled

**Files:** `models/DataObject/ClassDefinition/Data/User.php:39-48`, `:53-81`, `:111-123`, `:141-153`

- `getDataFromResource()` calls `checkValidity()` → `Model\User::getById()` for every loaded object and field (N+1 on
  listings).
- `getDataForResource()` calls `init()` → `configureOptions()` loads **all** users whenever in admin or options are
  empty, i.e. on every save; `__wakeup()` does the same on every unserialization of the definition.
- A reference to a deleted user becomes `NULL` on the next save without log or validation error; `checkValidity()`
  casts `(int)$data`, so `'abc'` is looked up as id `0`.

**Fix direction:** validate only in `checkValidity()`, load options lazily for layout enrichment only, log dangling
references.

## [ ] B-274 – `Select` / `Multiselect` small defects: null options loop, return-type mismatch, shared options mutation, LIKE escaping

**Files:** `models/DataObject/ClassDefinition/Data/Select.php:147-150`, `:193-219`, `:276-304`, `:312-335`;
`Multiselect.php:225-226`, `:248-256`, `:328-355`, `:404-420`

- `Select::getDiffDataForEditMode()` iterates `$this->options` without a null check → warning when provider options were
  removed (`jsonSerialize()` / `Service::doRemoveDynamicOptions()`).
- `Select::getDataForQueryResource(): ?string` returns `getDataForResource(): null|string|int` → `TypeError` for int
  values (strict types).
- `getDataForGrid()` (both types) writes provider options into the shared field definition (`setOptions()`) per row →
  options of the last row leak into later rows / requests.
- `Select::getFilterConditionExt()` `LIKE` does not escape `%`/`_`; `Multiselect` `=` branch neither escapes nor
  handles an array value (`Array` + warning).
- `Multiselect::getDiffDataFromEditmode()` reads `$data[0]['data']` unchecked; `appendData()` / `removeData()` return
  non-list arrays (`array_unique()` / `array_diff()` keep keys).

**Fix direction:** null-safe loops, cast to string, keep provider options local to the call, `Helper::escapeLike()`,
`array_values()`.

## [ ] B-275 – Class / collection / brick / custom-layout JSON export mutates the live, runtime-cached definition objects

**Files:** `models/DataObject/ClassDefinition/Service.php:54-58`, `:68-96`, `:157-160`, `:199`, `:215-216`, `:226-240`;
`Data/Gender.php:37-59`; `Data/Select.php:55`; `Helper/VarExport.php:25`

- `clone $class` / `$fieldCollection` / `$objectBrick` are shallow (no `__clone()` anywhere); `generateCustomLayoutJson()`
  does not clone at all. `removeDynamicOptionsFromLayoutDefinition()` therefore works on the layout tree that
  `ClassDefinition::getById()` keeps in the runtime cache.
- `:71-76` `unset()`s every blocked var on `VarExporterInterface` nodes: after one export the cached `Gender` (blocked
  var `options`) has an uninitialised `public ?array $options` → any later `getOptions()` in the process throws "must not
  be accessed before initialization". `:89` sets `$child->options = null` on live `Select` definitions.
- `:78-79` is dead: `blockedVarsForExport` is `protected`, so `isset()` from outside is always false.
- Which admin endpoints export and then reuse the definition in the same request/worker is **not verified locally**.

**Fix direction:** deep-copy the layout tree first (`DeepCopy`, as `DataObject/Service.php:1197-1199`) or strip the
blocked keys from the decoded JSON array instead of the objects; remove the dead branch.

## [ ] B-276 – `ClassResolver`: one cache for all resolvers/modes ignores the validation callback; rejected classes are re-instantiated; errors escape

**Files:** `models/DataObject/ClassDefinition/Helper/ClassResolver.php:29-59`; `Helper/OptionsProviderResolver.php:34-51`;
`DynamicOptionsProvider/SelectionProviderTrait.php:34-37`, `:56-57`; `MultiSelectOptionsProviderInterface.php:25-37`;
`models/DataObject/Service.php:1486-1488`

- `private static array $cache` is keyed only by the class string: the first resolved object is returned to every
  resolver/mode without running the current callback (e.g. a legacy `MultiSelectOptionsProviderInterface` provider
  cached in multiselect mode is returned for `MODE_SELECT`; a class first resolved by `DynamicTextResolver` is returned
  to `LinkGeneratorResolver`).
- `??=` does not cache `null`: a rejected class is instantiated twice per call and again on every call.
- With `$showError = true` the first `new $class()` / `container->get()` is outside the `try` → a wrong class name throws
  to the caller (the "Class does not exist or is not valid" handling in `DataObject/Service.php` is never reached);
  `catch (Error)` misses `ServiceNotFoundException`.
- `SelectionProviderTrait` calls `getDefaultValue()` on a provider that may only implement the deprecated multiselect
  interface (which does not declare it) → "Call to undefined method". A provider returning an array default (allowed by
  `SelectOptionsProviderInterface`) makes `Select::setDefaultValue(?string)` throw `TypeError`.
- Hardening: any autoloadable class named in a definition is instantiated before validation (admins only).

**Fix direction:** key the cache by resolver + mode (or validate on every hit), cache negative results, check
`is_a($class, Interface, true)` before instantiating, catch `Throwable`, guard `getDefaultValue()`.

## [ ] B-277 – `Layout\Text`: renderer output is compiled as Twig source, the result overwrites the template, non-security Twig errors break the layout

**Files:** `models/DataObject/ClassDefinition/Layout/Text.php:52`, `:105-144` (`:119-120`, `:127`, `:133-141`);
`lib/Templating/TwigDefaultDelegatingEngine.php:72-76`

- The `DynamicTextLabelInterface::renderLayoutText()` result is written to `$this->html` and then passed to
  `createTemplate()`. Renderer output usually contains object data → an editor can inject `{{ … }}` / `{% … %}` that runs
  as Twig (limited by the sandbox policy, hence bug, not critical).
- Only `SecurityError` is caught: a `SyntaxError` (literal `{{` in admin HTML or renderer output) or `RuntimeError`
  breaks the whole layout response.
- `$this->html` is overwritten with rendered output; on a shared (runtime-cached) definition the next object renders the
  previous object's output and the original template is lost (whether admin callers deep-copy first is **not verified
  locally**).
- `public string $renderingData` has no default; `Layout::setValues()` skips `null`, so a definition with
  `renderingClass` and no `renderingData` throws on access.

**Fix direction:** render the admin template first and pass renderer output as data (escaped), catch
`Twig\Error\Error`, write to a separate output property, default `$renderingData = ''`.

## [ ] B-278 – JSON import functions accept invalid JSON as success and pass `false` to typed setters

**Files:** `models/DataObject/ClassDefinition/Service.php:98-153` (`:106`, `:108-115`), `:170-195` (`:172-177`),
`:242-287` (`:244`, `:265-268`); `models/DataObject/Traits/FieldcollectionObjectbrickDefinitionTrait.php:97`

- `json_decode()` without error check: invalid JSON → `null` → the class/collection/brick is saved unchanged with a new
  modification date and the function returns `true`. A missing `layoutDefinitions` key warns; a non-array value is a
  `TypeError` in `generateLayoutTreeFromArray(array …)`.
- `importFieldCollectionFromJson()` / `importObjectBrickFromJson()` do not check `$layout === false` (the class import
  does) → `setLayoutDefinitions(false)` → `TypeError` with `$throwException = false`.

**Fix direction:** `JSON_THROW_ON_ERROR`, validate `layoutDefinitions` as array, handle `false` identically in all three.

## [ ] B-279 – `Service::generateLayoutTreeFromArray()`: bypassable HTML check on title/name, unvalidated `datatype` / `fieldtype`

**Files:** `models/DataObject/ClassDefinition/Service.php:296-365` (`:299-308`, `:311`, `:313-315`);
`lib/Loader/ImplementationLoader/PrefixLoader.php:94-101`; `bundles/CoreBundle/src/Command/Definition/Import/CustomLayoutCommand.php:106-118`

- `/<.+?>/` without the `s` modifier: `"<img\nsrc=x onerror=…>"` and an unclosed `<svg/onload=…` pass (checked with
  `php -r`); only `title` and `name` are checked. A non-string `title`/`name` → `TypeError` in `preg_match()`. Where the
  values are rendered in the admin UI is **not verified locally**.
- `datatype` is concatenated into a container service id: a missing or unexpected value raises warnings /
  `ServiceNotFoundException` instead of the documented `false` / `Exception('Could not add child')`.
- `fieldtype` is resolved by the prefix loader and built with `new`: `abstractQuantityValue` resolves to the abstract
  class → "Cannot instantiate abstract class" (`Error`, not caught by callers catching `Exception`). The built item is
  never checked against `Data|Layout`.

**Fix direction:** reject `<` in all label-like keys (or an anchored check with `s`), allowlist `datatype`, check
`instanceof` and `ReflectionClass::isInstantiable()` before use.

## [ ] B-280 – Generated `FIELD_*` constants collide for distinct field names: the generated class does not compile

**Files:** `models/DataObject/ClassDefinition/Service.php:500-516`, `:521-526`, `:531-536`

- `camelCaseToUpperSnakeCase()` maps `myUrl`, `my_url` and `myURL` to `MY_URL` (and `urlA` / `url_a` to `URL_A`;
  checked with `php -r`). Fields `myUrl` and `my_url` have distinct getters and columns but both generate
  `public const FIELD_MY_URL` → compile-time fatal for the generated DataObject class. Normal and localized fields are in
  one list, so collisions also occur across `localizedfields`.
- No server-side check exists (`ClassDefinition.php` validates only class name and id). Distinct from C-32 (valid names,
  non-injective mapping).

**Fix direction:** detect duplicate constant names on class save with a clear error (or use a collision-free mapping).

## [ ] B-281 – `CustomLayout` caching and name lookup return wrong or stale layouts

**Files:** `models/DataObject/ClassDefinition/CustomLayout.php:61-126`, `:185-193`, `:239-251`;
`CustomLayout/Dao.php:83-123`; `bundles/CoreBundle/src/Command/Definition/Import/CustomLayoutCommand.php:77`

- `getById()` and `getByName()` share the runtime-cache prefix `'customlayout_' . $id|$name` → a layout whose name equals
  another layout's id is returned for the wrong lookup.
- `save()` / `delete()` only call `Cache::clearTag('customlayout_<id>')` (nothing is stored under that tag) and never
  clear `RuntimeCache` → after `delete()` / rename, `getById()` / `getByName(oldName)` still return the layout in the same
  process.
- `Dao::getByName()` searches across all classes and takes the first match; `getByNameAndClassId()` then returns `null`
  when another class owns a layout with that name; `CustomLayoutCommand` may update another class's layout.
- An empty name or id matches every layout (`getByName('')`, `exists('')` is true).

**Fix direction:** distinct cache keys (`customlayout_id_…`, `customlayout_name_<classId>_…`), clear `RuntimeCache` in
`save()` / `delete()`, filter by `classId` in the DAO, reject empty keys.

## [ ] B-282 – Typed definition properties without default: getters, custom-layout sync and loaders throw "must not be accessed before initialization"

**Files:** `models/DataObject/ClassDefinition/CustomLayout.php:51-55`, `:128-153`; `Layout/Iframe.php:38`, `:44`;
`Layout/Fieldcontainer.php:42`; `Layout/Text.php:52`; `Data/AdvancedManyToManyRelation.php:41`, `:48`, `:151`, `:694-707`,
`:751`; `Data/ManyToManyRelation.php:59`, `:571`; `Data/ManyToOneRelation.php:51`, `:439`; `Data/ReverseObjectRelation.php:46`;
`Data/ImageTrait.php` (`$uploadPath`); `Data/Image.php:213-216`; `Data/Hotspotimage.php:38-80`; `Data/ImageGallery.php:37-73`;
`Data/StructuredTable.php:40-44`, `:399-405`; `Data/BooleanSelect.php:74-86`, `:166-176`

- `AbstractModel::setValues()` / `Layout::setValues()` skip `null` / absent keys, and definitions built in code or
  imported from older JSON omit them, so these properties stay uninitialised: `CustomLayout::$userOwner`,
  `$userModification`, `$classId`; `Iframe::$iframeUrl`, `$renderingData`; `Fieldcontainer::$fieldLabel`;
  `AdvancedManyToManyRelation::$columns` / `$columnKeys` (the object variant has `= []`); `$assetUploadPath`
  (ManyToMany/ManyToOne); `ReverseObjectRelation::$ownerFieldName`; `$uploadPath` (Image/Hotspotimage/ImageGallery);
  `Hotspotimage::$ratioX/$ratioY/$predefinedDataTemplates`; `StructuredTable::$labelFirstCell` (as in
  `tests/Support/Helper/Model.php:537-547`); `BooleanSelect::$yesLabel/$noLabel/$emptyLabel`.
- Consequences: `AdvancedManyToManyRelation::loadData()` throws on every load; `synchronizeWithMainDefinition()` throws
  for every custom layout reading the main definition's property (`DataObject/Service.php:1046`);
  `BooleanSelect::getVersionPreview()` throws; `CustomLayout::getByNameAndClassId()` throws.
- Related: `ImageGallery::getRatioX()/getRatioY(): int` return a `?int` property → `TypeError` when unset;
  `CustomLayout::getFieldDefinition()` passes `?Layout` into a closure typed `Data|Layout`;
  `BooleanSelect::setYesLabel(?string)` assigns `null` to `string` and calls `setOptionsEntry(int, string)`.

**Fix direction:** defaults (`''`, `[]`, `0`) or nullable types for all of these; nullable getters where `null` is valid.

## [ ] B-283 – `AdvancedManyToManyObjectRelation::delete()`: broken LIKE pattern (fieldcollection) and exception (objectbrick) for localized sub-containers

**Files:** `models/DataObject/ClassDefinition/Data/AdvancedManyToManyObjectRelation.php:536-549` (`:538-541`, `:547`);
compare `:458` and `AdvancedManyToManyRelation.php:635-648`; caller `models/DataObject/Localizedfield/Dao.php:487-503`

- `"$index . /%"` is inside a double-quoted string → the pattern is `'/fieldcollection~fc/3 . /%'` (checked with
  `php -r`); no `ownername` ever matches → orphaned `object_metadata_*` rows for fieldcollection → localizedfield.
- For objectbrick → localizedfield the method throws `Exception('deletemeta not implemented')`. `Localizedfield\Dao::delete()`
  catches and only logs it (B-179), so every following field in the loop is not deleted. The element variant implements
  this branch.

**Fix direction:** build the pattern as in `save()` (`'/' . $type . '~' . $name . '/' . $index . '/%'`, with
`Helper::escapeLike()`), implement the objectbrick branch like `AdvancedManyToManyRelation`.

## [ ] B-284 – Advanced relation metadata items without a resolvable target crash validation, save and normalize

**Files:** `models/DataObject/ClassDefinition/Data/AdvancedManyToManyObjectRelation.php:361-369`, `:494-501`, `:670-677`,
`:814-817`; `AdvancedManyToManyRelation.php:468-469`, `:589-595`, `:735-736`, `:780-783`;
`models/DataObject/Data/ObjectMetadata/Dao.php:38`; `models/DataObject/Data/ElementMetadata.php:98-99`

- `ObjectMetadata::getObject()` / `ElementMetadata::getElement()` return `null` for a deleted target or after
  `setObject(null)`; `rewriteIds()` creates such items when the mapped id does not load (a folder id gives a `TypeError`
  in `setObject(?Concrete)`).
- `prepareDataForPersistence()` skips them, but `checkValidity()` calls `$o->getClassName()` on `null`, `save()` calls
  `$meta->save()` → `getElement()->getId()` on `null` / `getElementType(null)` `TypeError`, and `normalize()` does the same.
  `AdvancedManyToManyRelation::checkValidity()` even lets empty items through.

**Fix direction:** skip unresolvable items in `save()` / `normalize()`; `instanceof` checks in `checkValidity()`
returning a `ValidationException`.

## [ ] B-285 – Grid filter conditions of relation types and `Checkbox`: undefined keys, ignored prefix/name, `TypeError`

**Files:** `models/DataObject/ClassDefinition/Data/Relations/ManyToManyRelationTrait.php:94-96`;
`ManyToOneRelation.php:543-556`; `Extension/RelationFilterConditionParser.php:33`; `Checkbox.php:153-179`;
compare `ClassDefinition/Data.php:398-403`

- `ManyToManyRelationTrait::getFilterConditionExt()` reads `$params['brickPrefix']` (and `$params['name']`) without
  fallback → "Undefined array key" on every non-brick filter (an exception in debug mode).
- `ManyToOneRelation::getFilterConditionExt()`: `preg_match()` on `mixed $value` → `TypeError` for `null` ("is empty"
  filter) or `int`; without a `type|` prefix the fallback drops `$prefix` (unqualified column inside bricks); for
  operators other than `=` it builds `` `brick`.(…) `` (invalid SQL) and a `LIKE` on an int column.
- `Checkbox::getFilterConditionExt()` reads `brickPrefix` without `??`, always uses `$this->name` (ignores
  `$params['name']` for localized / `cskey_` fields) and quotes the prefix with `quoteIdentifier()` while
  `Data::getFilterConditionExt()` treats it as already formatted – one of the two produces broken SQL (caller format
  **not verified locally**).

**Fix direction:** `?? ''` / `?: $this->name` everywhere, cast/validate values (`null` → `IS NULL`), handle the prefix
like `Data.php`, support only `=` where only `=` makes sense.

## [ ] B-286 – `QuantityValue` filters: base unit excluded, broken `in` operator, numeric filter on `InputQuantityValue` varchar columns

**Files:** `models/DataObject/ClassDefinition/Data/AbstractQuantityValue.php:226-277` (`:240-243`, `:247-256`, `:261-266`);
`QuantityValue.php:404-418`; `InputQuantityValue.php:159-165`; `models/DataObject/QuantityValue/DefaultConverter.php:73`

- `$baseUnit = $filterUnit->getBaseunit() ?? $filterUnit` is followed by `baseunit = <base> OR id = <filter unit>`: when
  filtering in `cm` (base `m`) the base unit `m` itself is not in the list → objects stored in metres never match.
- `QuantityValue::getFilterConditionExt()` lacks the `in` branch of the parent: `` `f__value` in '1,2' `` (syntax error).
  `foreach ($values as $value)` overwrites the parameter → from the second unit on, `explode(',', $value[0])` reads one
  character (`"10,20"` → `"2"`). An empty or non-numeric value → `TypeError` in `DefaultConverter`. The `cskey_` branch
  puts `$value[0][0]` into SQL unquoted (reachability with request data **not verified locally**).
- `InputQuantityValue` (`varchar(255)` value column) inherits the arithmetic filter: non-numeric text → `TypeError`;
  `<` / `>` compare lexicographically, `=` misses `'1.50'` for `1.5`.

**Fix direction:** add `OR id = <baseUnit>`, separate loop variable, `IN (…)` with numeric casting, quote/bind the
`cskey_` value, string semantics (or not filterable) for `InputQuantityValue`.

## [ ] B-287 – Relation small defects: folder rows in `ManyToManyRelation` editmode, `ReverseObjectRelation::getClasses()` with legacy definitions

**Files:** `models/DataObject/ClassDefinition/Data/ManyToManyRelation.php:284-285`, `:632-657`;
`ReverseObjectRelation.php:71-89`, `:125`, `:144-148`, `:207-214`; `Extension/Relation.php:85`

- For an object folder `getDataForEditmode()` emits `[id, path, 'object', 'variant', 'folder']`: subtype `variant` and
  the string `'folder'` in the published slot; the diff editor copies both into `gridrow` and the unique key.
- `ReverseObjectRelation::getClasses()` checks `getOwnerClassId()` but passes the raw `$this->ownerClassName`; for a
  legacy id-only definition that is `null` → `ucfirst(null)` `TypeError` during class generation. The id → name fallback
  in `getOwnerClassName()` is bypassed. `checkValidity()` passes items to `allowObjectRelation(AbstractObject)` without a
  type check (`TypeError` instead of `ValidationException`).

**Fix direction:** `[id, path, 'object', 'folder', true]` for folders; use `getOwnerClassName()` and return `[]` when
unresolvable; `instanceof Concrete` before `allowObjectRelation()`.

## [ ] B-288 – `Table`: no shape validation, a single `"0"` is treated as empty, broken diff header, CSV `TypeError`

**Files:** `models/DataObject/ClassDefinition/Data/Table.php:239-243`, `:261-277`, `:306-324`, `:337-345`, `:347-386`,
`:397-415`

- `getDataFromEditmode()` concatenates all cells and tests `empty($checkData)` → a table containing only `"0"` becomes
  `null` (value lost); only spaces are stripped. Otherwise the input is returned unchanged; `checkValidity()` only checks
  `is_array` (ignores `rows`/`cols`/fixed dimensions, nested or 1-D arrays) → "Array to string conversion" in
  `implode()` (`:268`, `:377`) and warnings in the column-config loop.
- After an admin save the in-memory value is positional, after a reload it is keyed (see C-51): the getter shape depends
  on how the object was obtained.
- `getDiffVersionPreview()`: `<th>` closed with `</td>`; labels read by index from `columnConfig` → missing label →
  `htmlspecialchars(null)` `TypeError`; `getVersionPreview(mixed)` passes non-arrays to `getDiffVersionPreview(?array)`.
- `getForCsvExport(): string` returns `json_encode()` → `false` for invalid UTF-8 → `TypeError`.

**Fix direction:** validate a list of scalar rows matching the fixed dimensions, strict emptiness per cell, normalise
the shape in `getDataFromEditmode()`, iterate `columnConfig` for the header, `JSON_THROW_ON_ERROR |
JSON_INVALID_UTF8_SUBSTITUTE`.

## [ ] B-289 – `StructuredTable` row/column keys, `type` and `length` reach column names and DDL unvalidated

**Files:** `models/DataObject/ClassDefinition/Data/StructuredTable.php:96-160`, `:326-365`;
`models/DataObject/ClassDefinition/Dao.php:164-167`, `:181-184`; `ClassDefinition/Helper/Dao.php:92-110`;
`models/DataObject/Data/StructuredTable.php:62`

- Column name is `{field}__{row}#{col}`; `setRows()` / `setCols()` only `strtolower()` the key. Row `a#b` + col `c` and
  row `a` + col `b#c` map to the same column; keys differing in case collapse. A backtick in a key goes unescaped into
  `ALTER TABLE … ADD COLUMN` (new concrete instance of C-32 / I-76 – the key is a nested config value, not the field
  name). No length check (64-char identifier limit → class save fails mid-DDL). `__` in a key breaks the magic
  `getRow__Col()` accessor. Missing `key` → `strtolower(null)` `TypeError`; missing `position` → warnings in
  `PositionSortTrait::sort()`.
- `typeMapper()`: an unknown/missing `type` returns `null` → `ADD COLUMN \`…\`  NULL` (SQL error); `length` passed
  unchanged (numeric string → `TypeError`, no upper bound → `varchar(100000)` / 65,535-byte row limit). Docblock says
  default 255, code uses 190.

**Fix direction:** validate keys against `^[a-z0-9_]+$` (no `#`, no `__`, unique, required `key`/`position`) and the
resulting identifier length; allowlist `type`, cast and bound `length`.

## [ ] B-290 – `StructuredTable`: no per-cell type validation, missing cells warn, `0` / `false` count as empty

**Files:** `models/DataObject/ClassDefinition/Data/StructuredTable.php:145-160`, `:202-242`, `:270-309`, `:367-374`;
`models/DataObject/Data/StructuredTable.php:105-116`; generated getter `ClassDefinition/Data.php:518`

- `getDataFromEditmode()` trusts `__row_identifyer` and cell keys (unknown rows create extra keys, missing keys warn).
  `checkValidity()` never validates cells against `type` (`"abc"` in a `double` column fails in strict SQL mode or
  becomes `0`) and calls `$data->getData()` before the `instanceof` check.
- `getDataForResource()`, `getDataForEditmode()`, `getForCsvExport()` read `$data[$row][$col]` without `??` (only
  `getDataFromResource()` uses `?? null`) → warnings for partial data from the API or older versions (exceptions in
  debug mode).
- `isEmpty()` / `checkValidity()` use `empty($col)`: a table with only `0` / `false` cells fails the mandatory check,
  shows as empty in editmode and is treated as empty by the inheritance check (parent values returned).

**Fix direction:** accept only known keys, validate by type, `?? null` everywhere, emptiness = `null` / `''` only.

## [ ] B-291 – `Hotspotimage::getDataForEditmode()` rewrites the live `MarkerHotspotItem` values to path strings

**Files:** `models/DataObject/ClassDefinition/Data/Hotspotimage.php:193-228` (`:203-209`); `ImageGallery.php:212-224`;
`models/Element/Data/MarkerHotspotItem.php:87-112`; `lib/Cache/Core/CoreCacheHandler.php:323-346`

- The closure copies the marker/hotspot arrays, but the `data` entries are `MarkerHotspotItem` objects shared with the
  field value. `$metaData['value'] = $path` calls `offsetSet()` and stores the path into the live item; `offsetGet()`
  then calls `getElementById($type, '/path')` → element `null`. `getCacheTags()`, `resolveDependencies()` lose the links
  and a later `getDataForResource()` serializes the path.
- Trigger: `getDataForEditmode()` / `getDataForGrid()` (also via `ImageGallery`) on an object that is saved later in the
  same process or sits in the cache save queue (serialized only at `writeSaveQueue()`); whether admin GET requests flush
  the queue is **not verified locally**.

**Fix direction:** build new arrays / clone the items instead of writing through `ArrayAccess` on shared objects.

## [ ] B-292 – `Hotspotimage::getDataFromEditmode()` / `getDataFromGridEditor()` throw `TypeError` on reachable inputs

**Files:** `models/DataObject/ClassDefinition/Data/Hotspotimage.php:235-275` (`:243-248`, `:257`, `:265-266`);
`ImageGallery.php:237-238`; `models/DataObject/Data/Hotspotimage.php:47`

- `array_key_exists('marker', $data)` runs before any array check → `TypeError` for `null` (which `:265` expects).
  `ImageGallery::getDataFromEditmode()` passes items through unchecked; scalar elements fail at `:243-248`.
- `getDataFromGridEditor(): Hotspotimage` returns `getDataFromEditmode()`, which returns `null` without id → `TypeError`.
- `new Hotspotimage($data['id'], …)` with a string id (`"12"`) → `TypeError` under strict types (whether the admin JSON
  sends strings is **not verified locally**).

**Fix direction:** `is_array()` guards, `(int)` cast, nullable grid-editor return type, skip non-array elements.

## [ ] B-293 – Configured restrictions are client-side only: `Video`, `Link`, `Slider`, Geo types

**Files:** `models/DataObject/ClassDefinition/Data/Video.php:90-105`, `:211-241`, `:268`; `Link.php:37-47`, `:140-150`,
`:165-194`; `Slider.php:210-219`; `Geopoint.php:191-207`; `Geobounds.php:55-69`; `Geopolygon.php:40-69`; `Geopolyline.php`
(`checkValidity()`)

- `Video`: `Asset::getByPath()` accepts any asset type as video and as poster; `setType()` accepts any string;
  `allowedTypes` / provider ids are never validated; `type = asset` without `data` → `Asset::getByPath(null)` `TypeError`.
- `Link`: `getDataFromEditmode()` calls `setValues($data)` with every posted key; `allowedTypes`, `allowedTargets` and
  `disabledFields` are never read server-side (grep) → a crafted request stores any target/type/field (including
  `attributes`, see C-47). Non-array `$data` → `TypeError`.
- `Slider`: `minValue`, `maxValue`, `increment` not enforced.
- Geo: no latitude/longitude range checks, no NE/SW order check, polygons with < 3 / polylines with < 2 points accepted;
  `[]` is stored as `a:0:{}`.

**Fix direction:** enforce these settings in `checkValidity()` and strip disabled fields in `getDataFromEditmode()`.

## [ ] B-294 – `Link` to a deleted object keeps `internal` with `internalType = null`; `getCacheTags()` then throws `TypeError`

**Files:** `models/DataObject/ClassDefinition/Data/Link.php:62-68`, `:96-97`, `:165-194`, `:235-245`;
`models/Element/Service.php:1416`; `models/DataObject/Concrete.php:332-336`

- `checkValidity()` validates and resets only `document` and `asset` targets, never `object` (and the asset branch says
  "referenced document").
- After the object target is deleted and the owner re-saved, `getDataForResource()` nulls `linktype` and `internalType`
  but keeps `internal`. On the next load `getCacheTags()` calls `getElementCacheTag(null, $id)` (`string $type`) →
  `TypeError` from `Concrete::getCacheTags()`, which runs on cache save and object save.
- `getDataFromResource()` runs `checkValidity()` (target lookup) on every load: N+1 on listings.

**Fix direction:** reset `internal` together with `internalType`, validate `object` targets, guard `getCacheTags()` on
`getInternalType() !== null`, validate on save only.

## [ ] B-295 – `Geopoint` / `Geobounds`: coordinate `0` loads as "no value", half-filled points, nullable corners dereferenced

**Files:** `models/DataObject/ClassDefinition/Data/Geopoint.php:56-62`, `:112-113`, `:191-207`;
`Geobounds.php:40-43`, `:79-82`, `:121-124`, `:143`, `:162`, `:172`, `:192-193`; `Geopolygon.php:127`; `Geopolyline.php:127`

- `getDataFromResource()` uses truthiness on latitude/longitude → a point on the equator or the Greenwich meridian (or
  a bounds corner at 0) loads as `null` and is wiped on the next save.
- `Geopoint::getDataFromEditmode()` accepts a point when *either* value is set (`||`, missing key warns);
  `checkValidity()` treats a half-filled point as empty → saved with one `NULL` column, loads as `null`.
- `Geobounds::getDataFromEditmode()` tests `!== null`: `''` reaches `GeoCoordinates(?float)` → `TypeError`; string
  numbers in `Geopoint`, `Geopolygon`, `Geopolyline` likewise (**not verified locally** whether the UI sends strings).
- `DataObject\Data\Geobounds` allows `null` corners (`isEqual()` uses `?->`), but `getDataForResource()`,
  `getDataForEditmode()`, `getVersionPreview()`, `getForCsvExport()` and `normalize()` call `getNorthEast()->…`
  directly → fatal on save of `new Geobounds($ne)`.

**Fix direction:** `!== null` / `is_numeric()` checks and `(float)` casts; reject half-filled points and incomplete
bounds in `checkValidity()` (or write `NULL` columns null-safely).

## [ ] B-296 – Decimal / bigint precision loss through float casts; `PHP_INT_MAX` guard rejects valid `DECIMAL` values

**Files:** `models/DataObject/ClassDefinition/Data/QuantityValue.php:247`, `:306-308`; `NumericRange.php:247-253`,
`:401-403`; `Numeric.php:352-354`, `:446-453`

- `QuantityValue::getDataFromResource()` always casts `(float)`, also for `DECIMAL(64,x)` and `bigint(20)` columns;
  `NumericRange` likewise → values beyond ~15–17 significant digits or above 2^53 are rounded and written back rounded.
- `Numeric::preSetData()` rounds decimal-mode values via `round((float)$data, …)`: `"1234567890123456789.99"` becomes
  `1.2345678901234568E+18` (checked) – silent corruption on every set.
- `>= PHP_INT_MAX` is checked regardless of column type: `DECIMAL(65,0)` is allowed by `buildDecimalColumnType()`, but
  values ≥ 9.22e18 are rejected ("use an input data type"); the float comparison makes the cut-off imprecise.

**Fix direction:** keep decimals as strings (`toNumeric()` already does for `Numeric`), cast to int in integer mode,
round with bcmath/string logic; apply the `PHP_INT_MAX` guard only in integer mode and validate precision/scale for
decimals.

## [ ] B-297 – `0` treated as empty in quantity types: lost defaults, unwanted inheritance, dropped values

**Files:** `models/DataObject/ClassDefinition/Data/AbstractQuantityValue.php:301-305`; `QuantityValue.php:303`, `:345`,
`:366`; `InputQuantityValue.php:52`, `:73`, `:100`; `models/DataObject/Traits/DefaultValueTrait.php:49`

- `AbstractQuantityValue::isEmpty()` reports `QuantityValue(0, null)` as empty (`empty(0)`) → an explicit 0 without unit
  is replaced by the default on create and inherits from the parent when inheritance is on. `QuantityValue::checkValidity()`
  uses the same check, so `0` skips min/max/unsigned (`:303`).
- `QuantityValue::doGetDefaultValue()` ignores a configured default `0` without default unit (`:345`).
- `InputQuantityValue` drops `"0"` without unit on load (`:52`), from editmode (`:73`) and as default (`:100`).
- (The `isEqual()` null↔0 part is in B-266.)

**Fix direction:** `=== null` / `=== ''` instead of truthiness for values.

## [ ] B-298 – `QuantityValue` CSV export is locale-formatted, rounded to 3 decimals and uses the translated abbreviation

**Files:** `models/DataObject/ClassDefinition/Data/AbstractQuantityValue.php:163-171`; `QuantityValueRange.php:229-245`;
`models/DataObject/Data/QuantityValue.php:50-75`

- `getForCsvExport()` returns `(string)$data` = `NumberFormatter::DECIMAL` in the current locale (grouping, max 3
  fraction digits) plus the translated abbreviation: `1.23456 kg` → `1.235 kg`, in `de` `1.234,5` style. Not stable
  across locales; precision lost. `QuantityValueRange` exports raw values with the untranslated abbreviation.

**Fix direction:** export the raw value plus unit id/abbreviation without locale formatting, consistently for both types.

## [ ] B-299 – Input normalisation defects: `Checkbox` default, `BooleanSelect` `-1`, `Slider` `''`, `RgbaColor` short hex

**Files:** `models/DataObject/ClassDefinition/Data/Checkbox.php:36`, `:46-54`; `BooleanSelect.php:146-158`, `:186-204`;
`Slider.php:142-163`, `:192-200`; `RgbaColor.php:108-117`

- `Checkbox::setDefaultValue()`: `is_numeric("1")` → the string is assigned to `?int` (strict types) → `TypeError`;
  `true` is not numeric → silently `null`.
- `BooleanSelect::getDataForResource()`: `is_numeric(-1)` → `(bool)-1` → `YES_VALUE`, although `-1` is the editmode/storage
  value for "no"; `0` (editmode "empty") becomes `NO_VALUE`. `getDiffDataForEditMode()` compares with `==`, so `false`
  matches the `empty` option.
- `Slider`: the `!= null` guard lets `''` / `false` through uncast → `?float` return `TypeError` (`getDataFromEditmode('')`,
  `getDataFromGridEditor('')`).
- `RgbaColor::getDataFromEditmode()`: `sscanf('fff', '%02x%02x%02x%02x')` → `#ff0f00ff` instead of white; `'#'` or
  non-hex text → opaque black; no validation error.

**Fix direction:** normalise with `filter_var(…, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)` / explicit maps,
strict comparison, `''`/`false` → `null`, accept only 6/8-digit hex (`ctype_xdigit`).

---

# Scope 17 – DataObject `Data/**` (field value objects)

> Notes
> - Scanned and skipped files: see the Scope 17 notes in [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md) (C-52).
> - Dirty detection is only consulted for relations, localized fields and URL slugs (`supportsDirtyDetection()`,
>   `Concrete/Dao.php:195-196`). For value objects it matters inside localized fields: `Localizedfield\Dao::save()`
>   skips every language that is not dirty on an update (`Localizedfield/Dao.php:142-149`), and the dirty maps are reset
>   after each save (`Service.php:1575-1600`). A value object can only report its own changes through
>   `OwnerAwareFieldTrait::markMeDirty()`, i.e. when `_owner` / `_fieldname` / `_language` are set.

## [ ] B-300 – `ObjectMetadata::setObjectId()` does not mark the relation dirty: retargeting a metadata item is not saved

**Files:** `models/DataObject/Data/ObjectMetadata.php:57-70`, `:197-205`; `ElementMetadata.php:54-59` (for comparison);
`models/DataObject/ClassDefinition/Data/Relations/AbstractRelations.php:119-128`;
`AdvancedManyToManyObjectRelation.php:151-169`

- Relation fields support dirty detection: `save()` skips the field when it is not dirty. The loaded items have an owner
  (`AdvancedManyToManyObjectRelation.php:151-152`), so `setObject()`, `setData()` and the magic column setters mark the
  field dirty – `setObjectId()` does not (`ElementMetadata::setElementTypeAndId()` does).
- `$item->setObjectId(42); $object->save();` keeps the old target in `object_relations_*` and `object_metadata_*` when no
  other change marked the field. The in-memory object (and the cache entry written from it) shows the new target until
  the next load.
- `getObjectId(): int` turns a `null` id into `0` (`:197-200`), so callers cannot tell "no target" from an id.

**Fix direction:** `markMeDirty()` in `setObjectId()` when the id changes; `getObjectId(): ?int`.

## [ ] B-301 – In-place changes of value objects in localized fields are lost when the value has no owner

**Files:** `models/DataObject/Traits/OwnerAwareFieldTrait.php:89-97`; `models/DataObject/Localizedfield/Dao.php:142-149`;
`models/DataObject/Service.php:1575-1600`; `models/DataObject/ClassDefinition/Data.php:592-605` (generated setter);
`ClassDefinition/Data/ImageGallery.php:166-178`; `Geopolygon.php:127`; `Geopolyline.php:132`;
`models/DataObject/Data/ImageGallery.php:78-83`; `Hotspotimage.php:74-126`; `GeoCoordinates.php:52-78`

- Owners are attached only in `getDataFromResource()` (a loaded `ImageGallery` passes its params to the items). They are
  never attached by generated setters / `setLocalizedValue()` / `ImageGallery::setItems()`, and never to the
  `GeoCoordinates` points of `Geopolygon` / `Geopolyline`, not even on load.
- Sequence: `$o->setGallery($g, 'en'); $o->save();` (the language is dirty → saved; the maps are reset) →
  `$g->getItems()[0]->setHotspots([...]); $o->save();` → `markMeDirty()` is a no-op without owner → `en` is not dirty →
  `Localizedfield\Dao` skips the language → the change is silently not persisted, although the in-memory object and its
  cache entry show it. Same for `$o->getArea('en')[0]->setLatitude(…)` and for any value set through a setter and
  edited later in the same process (imports, workers).
- Not executed: the conclusion follows from the code paths above; the admin UI always posts complete values and is not
  affected.

**Fix direction:** attach owner, field name and language in the generated setters / `setLocalizedValue()` (and
propagate to nested items in `ImageGallery::setItems()` / the geo types), or mark the language dirty in
`Localizedfield::getLocalizedValue()` for mutable value objects. Add a test for the two-save sequence.

## [ ] B-302 – `BlockElement::marshalForCache()` mutates the live instance: after a cache write, `getData()` swaps the value for a deep copy

**Files:** `models/DataObject/Data/BlockElement.php:97-105`, `:113-169`, `:210-237` (`:212`);
`lib/Cache/Core/CoreCacheHandler.php:494-509`

- `CoreCacheHandler` applies its `ReplaceFilter` to the **original** value (DeepCopy type filters run before copying),
  so `marshalForCache()` runs on the live `BlockElement` and sets `$this->needsRenewReferences = true` on it before
  copying itself.
- The next `getData()` on the live object runs `renewReferences()`: `$this->data` is replaced by a deep copy. Value
  objects (`Hotspotimage`, `Video`, `Link`, …) and elements inside the block become new instances (the matcher keeps
  their properties but still clones the containers) → objects obtained before the cache write are detached: edits made
  through them are no longer part of the object that is saved, and identity checks fail. For descriptors, the element is
  also evicted from the runtime cache (`:124-129`), so two instances of the same element coexist.
- When the save queue is flushed during a request (long-running imports, `Cache::save()` with immediate write, worker
  processes) this happens mid-process; **not verified locally** which commands force immediate writes.
- `setName()` / `setType()` compare with `!=` (`:78`, `:91`): `'1e1'` → `'10'` is not applied (PHP 8 numeric-string
  comparison).

**Fix direction:** set `needsRenewReferences` only on the copy (`$copy = $copier->copy($this); $copy->needsRenewReferences
= true; return $copy;`); use `!==` in the setters.

## [ ] B-303 – `Consent`: `setNote()` does not set `noteId` (the note link is not saved), `setNoteId()` keeps a stale cached note

**Files:** `models/DataObject/Data/Consent.php:32`, `:59-65`, `:67-74`, `:76-80`, `:82-90`;
`models/DataObject/ClassDefinition/Data/Consent.php:45`, `:104`, `:141`, `:320`

- Persistence, editmode and normalisation read only `getNoteId()`. `setNote($note)` stores the object but leaves
  `noteId` unchanged → `new Consent(true); $c->setNote($note); $o->save()` stores `__note = NULL`; after reload
  `getNote()` is `null` and the consent proof is gone.
- `setNoteId()` does not reset `$note`: after `getNote()` was called once, `getNote()` / `getSummaryString()` keep
  returning the old note. `setNoteId(int)` cannot clear the id.
- The `Note` object is serialized with the value (versions, cache) → frozen copy of the note.

**Fix direction:** `setNote(?Note)` sets `noteId` from the note; `setNoteId(?int)` resets `$note` when the id changes;
exclude `$note` from serialization.

## [ ] B-304 – `RgbaColor` accepts components outside 0–255: corrupt hex is stored, the colour changes on reload

**Files:** `models/DataObject/Data/RgbaColor.php:40-91`, `:116-128`, `:134-153`, `:155-162`;
`models/DataObject/ClassDefinition/Data/RgbaColor.php:45-57`, `:66-68`

- `setR()` … `setA()` and `setRgba()` accept any `int`. `getHex()` uses `%02x`: `300` → `12c`, `-1` →
  `ffffffffffffffff` (checked). `getDataForResource()` stores these strings in `__rgb` / `__a`; depending on the column
  length and SQL mode the save fails or the value is truncated, and `getDataFromResource()` (`sscanf('%02x…')`) reads a
  different colour.
- `setHex()` checks only the length: `'gg0000'` → `sscanf()` yields `null` components → silently `0` (black), no
  exception. (Editmode short-hex handling is in B-299.)

**Fix direction:** validate `0 ≤ x ≤ 255` in every setter (`InvalidArgumentException`), and `ctype_xdigit()` in
`setHex()`.

## [ ] B-305 – `CalculatedValue::getIndex()` throws "must not be accessed before initialization" for values built by `Concrete::getValueForFieldName()`

**Files:** `models/DataObject/Data/CalculatedValue.php:33`, `:47-51`, `:58-68`, `:75-78`;
`models/DataObject/Concrete.php:312-324` (`:320`); `models/DataObject/Service.php:1440-1451`, `:1509-1560`

- `protected string|int|null $index;` has no default and is only assigned in `setContextualData()`. Every other
  construction site calls `setContextualData()`; `Concrete::getValueForFieldName()` does not (`:320`).
- A calculator that reads `$context->getIndex()` (a natural thing for code shared between object, field-collection and
  brick fields) or an expression using `data.getIndex()` throws `Error` for object-level calculated fields whenever the
  value is read through `getValueForFieldName()` (grid / export / `Service::gridObjectData()` paths), while the generated
  getter works.

**Fix direction:** default `$index = null`; call `setContextualData('object', null, null, null)` in
`Concrete::getValueForFieldName()` like the generated getter does.

## [ ] B-306 – `InputQuantityValue` / `QuantityValueRange::__toString()` throw `TypeError` for units without abbreviation

**Files:** `models/DataObject/Data/InputQuantityValue.php:48-57` (`:53`); `QuantityValueRange.php:93-117` (`:113`);
`QuantityValue.php:62-70` (guarded); `models/DataObject/QuantityValue/Unit.php:36`, `:136`;
`lib/Translation/Translator.php:63`

- `Unit::getAbbreviation()` is `?string`; Pimcore's `Translator::trans(string $id, …)` is not nullable. `QuantityValue`
  falls back to the unit id, the two siblings pass `null` → `TypeError` in strict mode whenever the value is cast to
  string (version preview, CSV export, Twig `{{ value }}`, `implode()`), for any unit created without abbreviation.

**Fix direction:** share `QuantityValue`'s fallback (`abbreviation ?? id`) in one helper used by all three types.

## [ ] B-307 – `0` is rendered as empty or infinite: `QuantityValue(0)` → `''`, range bound `0` → `-∞` / `+∞`

**Files:** `models/DataObject/Data/QuantityValue.php:50-75` (`:74`); `QuantityValueRange.php:97-98`;
`NumericRange.php:76-82` (`:78-79`)

- `QuantityValue::__toString()` ends with `return $value ? (string)$value : ''` → a value `0` without unit renders as
  `''` (checked); `NumberFormatter::format()` returning `false` becomes `''` or `' kg'`.
- `QuantityValueRange` / `NumericRange` use `?:` for the open-bound marker: `[0, 10]` renders as `[-∞, 10]`, `[-5, 0]` as
  `[-5, +∞]` – a different, wrong range in previews, grids and exports.

**Fix direction:** `=== null` checks instead of truthiness; return `(string)$value` for numeric values.

## [ ] B-308 – `NumericRange::getRange()` / `QuantityValueRange::getRange()` with an open bound: `TypeError` or a fake `[0]`

**Files:** `models/DataObject/Data/NumericRange.php:63-66`; `QuantityValueRange.php:68-82`

- Open bounds are a valid, stored state (C-49). `NumericRange::getRange()` calls `range($min, $max, $step)` without a
  null check → `range(): Argument #2 ($end) must be of type string|int|float, null given` in strict mode (checked).
  `QuantityValueRange::getRange()` returns `[0]` for any open range – a fabricated value; `getValue()` returns the same.
- No guard on the size (C-48 for the persistence paths) and `$step = 0` → `ValueError`.

**Fix direction:** return `[]` (or throw a domain exception) for open bounds, validate `$step > 0`, and cap / document
the size; deprecate `QuantityValueRange::getValue()` returning a range (I-163).

## [ ] B-309 – `AbstractQuantityValue::setUnitId()` drops the current unit before failing; unit id `"0"` is treated as "no unit"

**Files:** `models/DataObject/Data/AbstractQuantityValue.php:42-56` (`:46`), `:61-74` (`:63-64`, `:71`), `:81-88`

- `setUnitId('unknown')` sets `unitId` / `unit` to `null` **before** throwing `NotFoundException`; a caller that catches
  the exception keeps a value without unit, and the next save writes `__unit = NULL`.
- Constructor `elseif ($unit)` and `getUnit()` `!empty($this->unitId)` treat the id `"0"` as missing (ids are free
  strings in `quantityvalue_units`).

**Fix direction:** look the unit up first and assign only on success; compare with `null` / `''`.

## [ ] B-310 – `Link::setPath()` leaves the previous target behind; `getElement()` ignores `linktype`

**Files:** `models/DataObject/Data/Link.php:290-326`, `:328-340`, `:373-388`, `:433-444`;
`models/DataObject/ClassDefinition/Data/Link.php:96-97`, `:141-150`

- Switching to a direct URL (`setPath('https://…')`) sets `linktype = 'direct'` and `internalType = null` but keeps
  `internal`; switching to an internal target keeps `direct`. `getElement()` checks only `internal` / `internalType`,
  and an internal path whose type matches no element falls through to the other types.
- With an existing internal link, `setPath('/foo')` first tries the configured `internalType`, then any document, asset
  or object with that path, so the same string can silently retarget the link to another element type.
- `setValues()` calls setters in input order: `['path' => '/x', 'linktype' => 'internal', 'internalType' => 'asset']`
  resolves `path` before the type is known (`getDataFromEditmode()` passes the posted array, B-293).
- `__unserialize()` (`:460-465`) assigns raw values to typed properties from a strict file: a legacy row with a string
  `internal` id throws `TypeError` (checked with a reduced class); whether such rows exist from Pimcore ≤ 10 is
  **not verified locally**.

**Fix direction:** reset the fields of the other link type in `setPath()` / `setLinktype()`; `getElement()` returns
`null` unless `linktype === 'internal'`; apply `linktype` / `internalType` before `path` in `setValues()`; cast `internal`
in `__unserialize()`.

## [ ] B-311 – `Link::getHref()` / `getHtml()` / `isEmpty()`: broken query strings, dropped `tabindex="0"`, owned empty links are not empty

**Files:** `models/DataObject/Data/Link.php:346-371` (`:363-368`), `:402-423` (`:407`), `:425-428`;
`models/DataObject/Traits/ObjectVarTrait.php:31-44`

- `getHref()` appends `'?' . str_replace('?', '', $parameters)`: a direct URL that already has a query gives
  `https://x?a=1?b=2`; a URL with a fragment gets the parameters after `#`; every `?` / `#` inside parameter values or
  the anchor is deleted.
- `getHtml()` emits an attribute only when it is truthy → `tabindex="0"` (the common "focusable" value) is dropped.
- `isEmpty()` checks all object vars except `_owner`; `_fieldname` and `_language` are included → every link loaded
  with an owner (and every localized link) is "not empty" even when all link fields are empty.
  `ClassDefinition\Data\Link::getDataForResource()` resets the owner fields first, so persistence is fine; API callers
  get the wrong answer.

**Fix direction:** build the URL with `parse_url()` / `http_build_query()`-aware merging; test `!== ''`; let `isEmpty()`
check the link fields explicitly.

## [ ] B-312 – `Hotspotimage::getThumbnail()` mutates a caller-supplied `Thumbnail\Config`: crops accumulate across images

**Files:** `models/DataObject/Data/Hotspotimage.php:141-182` (`:152`, `:157-178`); `models/Asset/Image/Thumbnail.php:44-49`,
`:430-439`; `models/Asset/Image/Thumbnail/Config.php:160-184`, `:218-222`

- Named configs are cloned by `Config::getByName()`, but a `Config` **instance** passed in is returned as-is by
  `getByAutoDetect()`. `getThumbnail($config)` then calls `addItemAt(0, 'cropPercent', …)` (per media, too) and
  `generateAutoName()` on the caller's object.
- `$cfg = Config::getByName('teaser'); foreach ($gallery as $img) { echo $img->getThumbnail($cfg); }` → the second
  image gets two crop operations (its own and the first image's), the third three, …; the caller's config is renamed.
- A crop array without `cropWidth` / `cropHeight` / `cropTop` / `cropLeft` (API data) → "Undefined array key" warnings
  and a `null` crop.

**Fix direction:** `clone` the config before adding the crop; validate the crop keys in `setCrop()`.

## [ ] B-313 – `ImageGallery`: nested iteration breaks, a gallery of broken items passes the mandatory check

**Files:** `models/DataObject/Data/ImageGallery.php:42-65`, `:97-100`; `models/DataObject/ClassDefinition/Data/ImageGallery.php:330-336`

- The `Iterator` uses the array's internal pointer: an inner `foreach` over the same gallery (Twig macros, nested
  loops) exhausts the outer loop after its first item; `getItems()` callers that use `current()` / `next()` share the
  state.
- `hasValidImages()` counts any `Hotspotimage`, also one whose image is `null` (a deleted asset loads as `Hotspotimage` with
  `image = null`, `ClassDefinition/Data/Hotspotimage.php:163`; `__wakeup()` does the same for cached values) → a mandatory gallery containing only empty items validates.

**Fix direction:** implement `IteratorAggregate` (`new ArrayIterator($this->items)`); check `$item?->getImage()
instanceof Asset\Image`.

## [ ] B-314 – `StructuredTable`: HTML ignores the column definitions, magic setters cannot fill a new table, unknown methods return `null`

**Files:** `models/DataObject/Data/StructuredTable.php:57-103`, `:118-135`, `:137-162` (`:149`, `:153`)

- `getHtmlTable()` prints the cells of each row in **data** order, not in `$colDefs` order, and does not pad missing
  cells → for rows set through `setData()` in another order, or after a column was added, values appear under the wrong
  header. A row missing in the data → "Undefined array key" + `foreach()` over `null` warning. (Escaping: C-47.)
- The magic setter only writes existing cells: `(new StructuredTable())->setRow1__col1(5)` throws "Requested data …
  not available", so API code cannot fill an empty or partial table cell by cell.
- `__call()` returns `null` for any method that does not start with `get`/`set` (typos such as `$t->isRow1__col1()`
  silently yield `null`); `strtolower()` of the key makes mixed-case keys set through `setData()` unreachable.

**Fix direction:** iterate `$colDefs` / `$rowDefs` with `?? null`; allow the setter for configured (or any) row/column
pair; throw `BadMethodCallException` for unknown methods.

## [ ] B-315 – `ObjectMetadata` / `ElementMetadata`: `__toString()` fatals for a deleted target; `ElementMetadata::getFieldname()` `TypeError`

**Files:** `models/DataObject/Data/ObjectMetadata.php:192-195`; `ElementMetadata.php:37`, `:47-52`, `:122-125`, `:197-200`;
`ElementMetadata.php:202-212`, `ObjectMetadata.php:207-221`

- `__toString()` calls `getObject()->__toString()` / `getElement()->__toString()` without a null check → `Error` for an
  item whose target was deleted (the relation row stays until the owner is saved, B-284) whenever the item is cast to
  string (Twig `{{ item }}`, `implode()`, logs).
- `ElementMetadata::getFieldname(): string` returns the `?string` property; `new ElementMetadata()` (all constructor
  arguments optional) → `TypeError` on `getFieldname()` and therefore on `save()`. `ObjectMetadata` declares `?string`.
- Both `__unserialize()` cast the id with `(int)`, so a serialized `null` id becomes `0`.

**Fix direction:** `return (string)$this->getObject()?->…` / `''`; make `fieldname` required (or `getFieldname():
?string`); cast only non-null ids.

---

# Scope 18 – DataObject `QuantityValue/**` (units and conversion)

> Notes
> - Scanned and skipped files: see the Scope 18 notes in [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md) (C-53).
> - Conversion semantics documented in `doc/05_Objects/01_Object_Classes/01_Data_Types/55_Number_Types.md:93`: "Factor
>   and offset define how to convert the current unit to the base unit", i.e. `base = value * factor - offset`.

## [ ] B-316 – `DefaultConverter` formula is not the inverse of its own "to base" step: wrong values for units with factor **and** offset

**Files:** `models/DataObject/QuantityValue/DefaultConverter.php:73`; documented in
`doc/05_Objects/01_Object_Classes/01_Data_Types/55_Number_Types.md:129-138`; consumer
`models/DataObject/ClassDefinition/Data/AbstractQuantityValue.php:226-277` (`:248-261`)

```php
$convertedValue = ($value * $from->getFactor() - $from->getConversionOffset()) / $to->getFactor() + $to->getConversionOffset();
```

- The first half converts to the base unit (`base = v·f − o`). The inverse is `v = (base + o) / f`; the code computes
  `base / f + o`. Both agree only when the target factor is `1` or its offset is `0`.
- With the documentation's own example (°C: factor `1.8`, offset `-32`; °F: base °C): °C → °F is right, but
  212 °F → °C gives `85.8` instead of `100`, and °C → °C changes the value (`v + 17.78 − 32`). With the "per base"
  setup (°F: factor `5/9`, offset `-255.372`, base K) K → °F gives `236.3` for 273.15 K and °F → °F turns `32` into
  `236.3` (checked).
- The quantity-value filter converts the filter value into **every** unit of the base group, the filter unit itself
  included. For such units the condition for the filter unit compares against a wrong number, so objects stored in
  exactly the unit the editor filters by are not found. `convertTo()` results are silently wrong.
- Units with only a factor (length, weight) or only an offset (°C ↔ K) are not affected.

**Fix direction:** `($value * $from->factor - $from->offset + $to->offset) / $to->factor` (works for both setups and
for the documented example), short-circuit `from === to`, and fix the formula in the documentation. Add data-provider
tests for °C / °F / K in both setups, same-unit conversion and round trips.

## [ ] B-317 – Unit definitions are never validated: factor `0` → `DivisionByZeroError`, base-unit chains / cycles are stored but cannot be converted

**Files:** `models/DataObject/QuantityValue/Unit.php:91-110`, `:144-161`; `DefaultConverter.php:41-73` (`:41`, `:55`,
`:69-71`, `:73`); `UnitConversionService.php:32-36`; callers `Data/AbstractQuantityValue.php:95-113`,
`ClassDefinition/Data/AbstractQuantityValue.php:226-277`

- `Unit::save()` accepts any values. A unit with `factor = 0` as conversion target → `DivisionByZeroError` (checked).
  It is an `Error`, so callers that catch `Exception` (`QuantityValue` grid/filter code, custom importers) do not catch
  it; a filter on such a unit group fails the whole listing request.
- `baseunit` may point to a unit that itself has a base unit (`mm → cm → m`). Both converters only look one level up
  (`getBaseunit()`), so `mm` and `m` have different "base" ids and `convertTo()` throws "must have same base unit",
  while the filter (`baseunit = <filter base>`) silently skips part of the group. A unit whose base is itself is accepted;
  longer cycles (`a → b → a`) make `a` and `b` mutually non-convertible.
- A `baseunit` id that does not exist (possible through the cache race in B-322 or a manual DB edit) → `getBaseunit()`
  returns `null` and the unit behaves like an independent base unit.

**Fix direction:** add `Unit::validate()` called from `save()` (and from the importer, C-53): `factor` `null` or
`!= 0`, `baseunit` must exist, must not be the unit itself and must not have a base unit of its own (or resolve the
chain to its root everywhere and reject cycles). Make the converter throw a domain exception for a `0` factor.

## [ ] B-318 – `DefaultConverter` side effects: mutates the shared cached `Unit` objects, converts an empty value to `0`, may return a value without unit

**Files:** `models/DataObject/QuantityValue/DefaultConverter.php:36-75` (`:41-53`, `:55-67`, `:73`, `:75`);
`Unit.php:76-81`; `Service.php:98-133`

- The units are cloned only when they have **no** base unit (`:42-45`, `:56-59`). A derived unit with
  `factor = null` / `conversionOffset = null` is modified in place (`setFactor(1)`, `setConversionOffset(0)`). That
  object is the shared instance from the unit table in `RuntimeCache` (`Unit::getById()`), so for the rest of the
  request every reader sees `1` / `0` instead of `null`, and a unit edited and saved in the same request persists them.
- `QuantityValue(null, 'cm')->convertTo('m')` returns `QuantityValue(0.0, 'm')`: `null * factor` is `0` in PHP 8.4
  (checked) – an empty value becomes a real measurement. A non-numeric string value → `TypeError` (B-286 covers the
  filter side only).
- The result is built from `$toUnit->getId()`; for an unsaved target unit (`new Unit()`, id `null`) the value silently
  has no unit.

**Fix direction:** never write to the unit objects – read `$unit->getFactor() ?? 1.0` / `getConversionOffset() ?? 0.0`
into locals and drop the clones; return a value with `null` unchanged (or throw for an empty value); require a saved
target unit (or pass the `Unit` object to the constructor).

## [ ] B-319 – `Unit::save()`: add / update decided by "has an id"; a preset id silently overwrites another unit; concurrent creates get the same id

**Files:** `models/DataObject/QuantityValue/Unit.php:91-110` (`:94`); `Unit/Dao.php:75-112` (`:90-95`, `:111`);
`Service.php:43-44`, `:56-58`

- Unit ids are free strings that the caller may set. `save()` fires `UNIT_PRE_ADD` / `UNIT_POST_ADD` only when the id
  is empty, so every unit created with a preset id – all units created by `importDefinitionFromJson()` – fires the
  **update** events and never an add event. The id `"0"` counts as empty and is replaced by a generated id.
- `Dao::update()` is an upsert on the primary key: `(new Unit())->setId('kg')->save()` overwrites an existing unit `kg`
  without a check, again announced as an update.
- Id generation reads `MAX(numeric suffix) + 1` and then upserts, without a lock. Two concurrent creates (two admins,
  parallel imports) compute the same id; the second save overwrites the first unit. The query also scans and sorts the
  whole table on every create.

**Fix direction:** decide add / update by existence in the DB (load once, or `INSERT` and catch the duplicate-key
error), reject a preset id that already exists unless the caller explicitly updates, and generate ids inside a
transaction with `SELECT … FOR UPDATE` (or use UUIDs for new units). Compare ids with `null` / `''`.

## [ ] B-320 – `importDefinitionFromJson()`: invalid input escapes as `Error`, deeper base-unit chains violate the foreign key, failures are not reported

**Files:** `models/DataObject/QuantityValue/Service.php:27-64` (`:30-31`, `:36`, `:44`, `:48-54`, `:59-61`);
`bundles/CoreBundle/src/Command/Definition/Import/QuantityValueCommand.php:78-89`, `:91-114`

- Only `Exception` is caught, but malformed input raises `Error`s: valid JSON that is not a list of objects (`"x"`,
  `5`, `false`, a single unit object) → `array_column()` `TypeError` or "Cannot access offset of type string on string"
  (checked); a numeric `"id": 5` → `TypeError` in `Unit::getById(string)` (`Service.php` is strict); a missing
  `baseunit` key → warning plus `null`; unknown keys reach any setter through `setValues()` (`"dao": "x"` →
  `TypeError`). The command only pre-checks `json_decode() !== null`, so it ends with a stack trace instead of its
  error message – after part of the units may already be deleted / written (C-53).
- The ordering puts units without base first, units that are referenced as base by others at the front of the rest,
  then the others. For chains deeper than two levels the order depends on the file order, and a unit can be inserted
  before its base → `fk_baseunit` violation → `false`, partial import. A `baseunit` that is neither in the file nor
  in the DB fails the same way.
- `catch (Exception) { return false; }` drops the reason; the command prints "Failed to import definitions" without
  any detail, and nothing is logged.

**Fix direction:** decode with `JSON_THROW_ON_ERROR`, validate the structure (list of arrays with string `id`, known
keys only) before writing, sort topologically, and return / throw a result that names the failing unit; catch
`Throwable` around the write phase inside the transaction from C-53.

## [ ] B-321 – `generateDefinitionJson()` exports translated texts instead of the stored keys: export → import is not a round trip

**Files:** `models/DataObject/QuantityValue/Service.php:66-91` (`:76-83`, `:85-87`); `models/Translation.php:313-343`

- Abbreviation and long name are replaced by `Translation::getByKeyLocalized(…, 'admin')` before export, in the
  language of the current admin user (or the session / locale / system fallback). The export therefore contains
  `"longname": "Millimeter"` (German admin) instead of the stored key, and the result differs per user. Importing it
  into another system stores the translated text as the key; `Unit::getByAbbreviation()` / `convertTo('<abbr>')` with
  the original abbreviation no longer finds the unit, and the original admin translations no longer apply.
- When no language can be resolved (CLI without locale) `getByKeyLocalized()` returns `null` and the abbreviation /
  long name are exported as `null`.
- One translation exception aborts the whole export (`return false`); the side effect of writing the keys as
  translations (C-18) happens on every export.

**Fix direction:** export the raw model values (`getObjectVars()` without translation) and translate only in the
admin presentation layer; do not create translations during export. If a "translated" export is wanted, add it as a
separate, explicitly named option.

## [ ] B-322 – Unit table cache: any error turns into "unit not found"; a concurrent save can leave a stale table cached indefinitely

**Files:** `models/DataObject/QuantityValue/Service.php:98-133` (`:106-111`, `:113-125`, `:128-132`); `Unit.php:76-81`,
`:101-103`, `:115-117`; consumers `ClassDefinition/Data/QuantityValue.php:234-260` (`:256`),
`Data/AbstractQuantityValue.php:42-55`

- `getQuantityValueUnitsTable()` catches every `Exception` (DB, cache backend, unserialize) and returns `null`;
  `Unit::getById()` then returns `null` for every id. During a transient DB / cache failure an object load therefore
  takes the C-50 paths: `QuantityValue` values load as `null` (and are wiped if the object is saved in the same
  request, e.g. by an importer), `QuantityValueRange` / `InputQuantityValue` throw a misleading "Unit … was not found".
  `Error`s are not caught at all.
- `Unit::save()` / `delete()` clear the shared cache **after** the DB write. A request that read the old listing just
  before that write saves its table afterwards (`Cache::save(…, lifetime null, force true)`); the stale table (deleted
  unit still present, changed factor not visible) stays until the next unit change or a full cache clear. The cache
  entry has no tags either (I-82), so tag-based clears do not help.
- In long-running processes the `RuntimeCache` copy is only refreshed if the process itself saved a unit.

**Fix direction:** let infrastructure errors propagate (log and rethrow) instead of returning `null`; invalidate by
version (store a `unitsVersion` counter / timestamp written in the same transaction as the unit, and include it in the
cache key) or give the entry a tag plus a finite lifetime.

## [ ] B-323 – Unit lookups and display: `getByAbbreviation()` picks an arbitrary unit, `__toString()` changes unit symbols

**Files:** `models/DataObject/QuantityValue/Unit.php:52-74`, `:121-124`; `Unit/Dao.php:50-70`;
`bundles/InstallBundle/dump/install.sql:699`, `:704`; caller `Data/AbstractQuantityValue.php:95-104`

- `abbreviation` and `reference` have no unique index; `SELECT * … WHERE abbreviation = …` without `ORDER BY` returns
  any matching row. Units that share a symbol (e.g. `"` for inch and for arc second, or the same abbreviation in two
  imported unit sets) make `convertTo('<abbr>')` pick a unit of the
  wrong group (exception "must have same base unit") or a different unit of the same group (wrong value).
- Both lookups bypass the cached unit table: one DB query per call (`convertTo('m')` in a loop is N queries) and a
  new instance that is not identical to `Unit::getById()`'s.
- `__toString()` returns `ucfirst("<abbreviation> (<id>)")`; unit symbols are case-sensitive: `mW` becomes `MW`
  (megawatt), `mm` → `Mm`, `kg` → `Kg` (checked). It is used in the converter's exception messages
  (`DefaultConverter.php:70`) and wherever a `Unit` is cast to string. A `null` abbreviation renders as `" (5)"`.

**Fix direction:** resolve both lookups from the cached table (index by abbreviation / reference), throw or log when
an abbreviation is ambiguous (or add unique indexes after a data check); drop `ucfirst()` and fall back to the id when
the abbreviation is empty (as in B-306).

---

# Scope 19 – DataObject `Objectbrick/**` and `SelectOptions/**`

> Notes
> - Scanned and skipped files: see the Scope 19 notes in [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md) (C-54–C-56).
> - The field-collection counterparts B-252–B-256 describe the shared lifecycle; the items below list only what is
>   different or additionally broken for bricks.

## Object bricks (Scope 19)

## [ ] B-324 – Removing a brick deletes the advanced-relation metadata of every relation field with the same name in the object

**Files:** `models/DataObject/Objectbrick/Data/Dao.php:296-315` (`:312`);
`models/DataObject/ClassDefinition/Data/AdvancedManyToManyRelation.php:627-672` (`:652-671`);
`AdvancedManyToManyObjectRelation.php:533-569` (`:550-568`)

```php
if ($fd instanceof CustomResourcePersistingInterface) {
    $fd->delete($object);             // the Concrete, no $params, no context
}
```

- `Data\Dao::delete()` calls the field's `delete()` with the **object** and without `context`. Both advanced relation
  types then build `DELETE FROM object_metadata_<classId> WHERE id = ? AND fieldname = ?`: no `ownertype`, no
  `ownername`.
- Every metadata row of a relation field with that name is deleted: the class-level field, fields with the same name
  in field collections, in other bricks and in other `Objectbricks` fields. Example: a brick `Accessories` with an
  advanced relation `parts` (column `quantity`) and a class-level `parts` field. Removing the brick from one object
  deletes the `quantity` values of the object's own `parts` relations. The relations themselves stay, so nothing looks
  wrong until someone reads the metadata.
- The opposite gap: custom-persisting fields are deleted only when they are also `QueryResourcePersistenceAwareInterface`
  (the call is nested inside that `if`), so a custom-persisting brick field without query columns is never cleaned
  up.

**Fix direction:** call `$fd->delete($this->model, ['context' => ['containerType' => 'objectbrick', 'containerKey' => $type,
'fieldname' => $fieldname], 'owner' => $this->model, 'fieldname' => $key])` like `save()` does, and move the call out
of the query-aware branch. Add a test with a class field and a brick field of the same name.

## [ ] B-325 – Lazy-loaded brick relations in edit mode show the relations of other `Objectbricks` fields and of every brick with an empty position

**Files:** `models/DataObject/Objectbrick/Data/Dao.php:323-368` (`:343-347`, `:352-356`, `:361-365`);
`models/DataObject/ClassDefinition/Data/Objectbricks.php:186-199`; compare `Relations/AbstractRelations.php:318`

- `getRelationData()` (used by `Objectbricks::getDataForField()` for lazy relations in the editor) filters by
  `fieldname`, `ownertype = 'objectbrick'`, `src_id` and `position = '<type>' OR position IS NULL OR position = ''`,
  but not by `ownername` (the `Objectbricks` field). The runtime loader filters by `ownername` and `position`
  (`AbstractRelations.php:318`).
- With two `Objectbricks` fields that allow the same brick (see C-54), the editor shows the union of both fields'
  relations. Rows with an empty `position` (older data) appear in every brick type that has a relation field with that
  name. When the editor saves the field, the wrong list is persisted into this brick.
- The SQL also concatenates the type (validated key, so not injectable) and repeats the three `UNION` blocks.

**Fix direction:** add `r.ownername = ?` (the brick's field name); decide whether the empty-position fallback is still
needed (migration) and limit it to the brick type it belongs to; build the query with bound parameters (I-177).

## [ ] B-326 – Every brick table update drops and re-creates all indexes: unique constraints disappear for the duration, and re-adding them can fail

**Files:** `models/DataObject/Objectbrick/Definition/Dao.php:84-95`, `:115-131`, `:159-170`;
`models/DataObject/ClassDefinition/Helper/Dao.php:25-110` (`:53`), `:173-185`; callers
`models/DataObject/ClassDefinition/Data/Objectbricks.php:761-779` (every class save), `Objectbrick/Definition.php:351-364`

- `createUpdateTable()` passes **all** existing columns to `removeIndices()` while only `id` / `fieldname` are
  protected. The brick override drops `u_index_<col>` on the store table and `p_index_<col>` on the query table for
  every column (errors ignored, `Helper::queryIgnoreError()`), then `addIndexToField()` adds them again because
  they no longer exist.
- This runs on every brick save, in `classes-rebuild`, and on **every save of every class** that allows the brick
  (`Objectbricks::classSaved()`). Each index is a separate `ALTER TABLE`: on large brick tables a class save becomes
  minutes of DDL with metadata locks that block object saves.
- Between the drop and the re-add there is no unique constraint. A concurrent object save can insert a duplicate;
  adding the `UNIQUE` index then fails with "Duplicate entry", and the class save fails half-way (B-185 for the
  class side).
- The shared helper and `Fieldcollection\Definition\Dao` / `Localizedfield\Dao` do the same for `u_index_` (all
  existing columns are passed), which was not listed in Scope 14.

**Fix direction:** compute the target index set from the field definitions and drop only indexes whose field was
removed or whose `index` / `unique` flag was switched off; never drop an index that is about to be re-created. Use
one `ALTER TABLE` per table.

## [ ] B-327 – `Objectbrick\Definition::delete()`: localized tables survive, the runtime cache keeps the definition, files go first

**Files:** `models/DataObject/Objectbrick/Definition.php:480-531` (`:486-487`, `:517-528`);
`Objectbrick/Definition/Dao.php:52-59`; `models/DataObject/Localizedfield/Dao.php:55-76`;
`lib/Maintenance/Tasks/DataObject/CleanupBrickTablesTaskHelper.php:48-72`

- `Definition\Dao::delete()` drops only `object_brick_store_<key>_<classId>` and `object_brick_query_<key>_<classId>`.
  `object_brick_localized_<key>_<classId>` and the per-language `object_brick_localized_query_<key>_<classId>_<lang>`
  tables stay. When a brick with the same key is created again, the old localized rows (`ooo_id`, `fieldname`,
  `language`) are loaded again as soon as an object gets the brick back (the B-253 "ghost data" effect for bricks).
  The maintenance cleanup only logs "Brick … not found" for them.
- Tables are dropped only for classes in `classDefinitions`. A class that still allows the key without being listed
  there (an assignment removed with `save(false)`, a hand-edited class, C-55) keeps the key in `allowedTypes` and keeps
  its tables.
- The definition and PHP class files are unlinked first (`@unlink`), the drops and class saves follow. A failing class
  save leaves a deleted brick still allowed by classes, and no `POST_DELETE` is sent.
- `RuntimeCache` keeps `objectbrick_<key>`: in the same process `getByKey()` still returns the deleted definition, and a
  class save afterwards (`Objectbricks::classSaved()`) re-creates the dropped tables.
- The "update classes" loop at the end (`:517-528`) loads every class definition and does nothing (it only `break`s).

**Fix direction:** drop the localized store / query tables (names from one builder shared with `Localizedfield\Dao`,
I-179), iterate all classes whose `Objectbricks` fields allow the key, drop tables and update classes **before**
removing the files, clear the runtime cache entry, and remove the dead loop.

## [ ] B-328 – `Objectbrick\Definition::save()` changes classes and files before the remaining steps can fail

**Files:** `models/DataObject/Objectbrick/Definition.php:144-214`, `:244-277`, `:292-346`, `:413-449`, `:97-139`

- Order: validation → `PRE_*` event → `preSave()` per field (interleaved with the forbidden-name check, as in B-254) →
  `generateClassFiles()` → `createContainerClasses()` → `updateDatabase()`. Inside `generateClassFiles()`,
  `cleanupOldFiles()` already **saves classes**: for every changed `classname-fieldname` pair (removed **and** newly
  added) it removes the key from the `allowedTypes` of **all** `Objectbricks` fields of that class, deletes the
  container class file and saves the class. Then the definition file and brick class are written.
- `createContainerClasses()` throws "Could not load class" / "Could not resolve field definition" for an assignment
  whose class or field does not exist (a typo in an import, a class deleted meanwhile). At that point classes have
  been saved without the key and the new definition is on disk: objects of those classes stop showing the brick until
  the brick is saved successfully again. `checkTablenames()` only logs a missing class, so this is not caught earlier.
- Each affected class is saved twice (key removed, then re-added): class `PRE/POST_UPDATE` listeners and the generated
  class code see an intermediate state in which the field does not allow the brick.
- `checkTablenames()` checks the current valid languages only. Adding a language later is not checked, and the class
  save that then creates `object_brick_localized_query_<key>_<classId>_<lang>` fails on the 64-character limit.

**Fix direction:** validate everything first (assignments resolvable, names, table-name length for all configured
languages), compute the old / new assignment diff once, update each affected class once (remove the key only from the
fields that lost it), run the DDL, then write files. Re-check the table length when languages change.

## [ ] B-329 – `Objectbrick\Dao::load()` turns any read error into "no bricks"; with inheritance the next save then empties existing bricks

**Files:** `models/DataObject/Objectbrick/Dao.php:33-123` (`:49-53`, `:95`, `:104`); `models/DataObject/Objectbrick.php:138-183`
(`:145-167`, `:179-181`); `models/DataObject/Objectbrick/Data/Dao.php:58-69`, `:116-117`

- `load()` catches **every** exception of the `SELECT` (deadlock, lost connection, lock timeout, a missing column after
  a half-applied deployment) and continues with `$results = []`. The object is built without the brick and written to
  the object cache.
- `Objectbrick::save()` treats a `null` brick as "not set" and calls `$createParentBrick()`. In a class with
  inheritance whose parent has the brick, a new **empty** brick is created and saved. `Data\Dao::save()` finds the
  existing row and runs `UPDATE … WHERE id = ?` with all values `NULL`: the brick's data is wiped (and with C-54 the row
  may belong to another field).
- Without inheritance the data survive, but the cached object has no bricks until the next save or cache clear.
- `$result[$key]` / `$result[$key . '__' . $fkey]` are read without `??`: a column that is not there yet raises a
  warning and loads `null`.

**Fix direction:** catch only `TableNotFoundException` (table not created yet), rethrow everything else; read columns
with `array_key_exists()` and log missing ones. In `Objectbrick::save()`, only create an empty inherited brick when the
store row does not exist.

## [ ] B-330 – Object-brick small defects

**Files:** `models/DataObject/Objectbrick/Data/AbstractData.php:76-79`, `:110-121`, `:128-146`, `:165-181`;
`models/DataObject/Objectbrick/Definition/Listing.php:27-37`, `:57-77`; `Objectbrick/Definition/Dao.php:34-50`;
`models/DataObject/Objectbrick/Definition.php:547-550`

- `AbstractData::getDefinition()` is declared `: Definition`, but `getByKey()` returns `?Definition` → `TypeError`
  when the definition file is missing (deleted brick whose class still exists, missing custom-config file on one
  node, cached object). It is called in `__sleep()` (cache write), in `Data\Dao::save()` (inside the object save
  transaction) and in `getValueForFieldName()`. Same as B-256 for collections.
- `flushContainer()` and `getValueFromParent()` call `ucfirst($this->fieldname)` while `fieldname` is `?string` and the
  file is strict: a brick created with `new Brick($object)` and attached with the generated container setter has no
  field name (the setter does not set it), so `setDoDelete(true)` or any inherited getter on such a brick throws
  `TypeError: ucfirst(): Argument #1 ($string) must be of type string, null given` (checked).
- `getValueForFieldName()` uses `if ($this->$key)`: values `0`, `0.0`, `'0'`, `false`, `''` are treated as missing and
  `null` is returned; an unknown key raises "Undefined property". `Concrete::getValueForFieldName()` uses `isset()`.
- `Definition\Listing::load()` returns whatever each file returns (`int`, `false`, an array) inside a `Definition[]`;
  `glob()` can return `false` (B-256 for collections).
- `Definition\Dao::getTableName()` / `getLocalizedTableName()` accept a definition without key and return
  `object_brick_store__<classId>`.
- `isWritable()` casts `$_SERVER['PIMCORE_CLASS_DEFINITION_WRITABLE']` with `(bool)`: the string `'false'` is `true`
  (checked), so `PIMCORE_CLASS_DEFINITION_WRITABLE=false` makes definitions writable. Classes and collections use the
  same expression (`ClassDefinition.php:570`, `Fieldcollection/Definition.php:265`).

**Fix direction:** `getDefinition()` throws a dedicated "brick definition not found" exception (or returns `?Definition`
and callers handle it); set the field name in the container setter and guard `null`; `array_key_exists()` /
`isset()` in `getValueForFieldName()`; type-check listing results and guard `glob()`; assert a key in the DAO; parse
the env value with `filter_var(…, FILTER_VALIDATE_BOOL)`.

## Select options (Scope 19)

## [ ] B-331 – `SelectOptions\Config` lifecycle: stale runtime cache, `exists()` ignores its argument, `null` entries in the listing

**Files:** `models/DataObject/SelectOptions/Config.php:57`, `:190-217`, `:240-257`; `SelectOptions/Config/Dao.php:47-86`,
`:110-142`; `SelectOptions/Config/Listing/Dao.php:30-45`; `SelectOptions/Config/Listing.php:79-93`;
`bundles/CoreBundle/src/Command/ClassesRebuildCommand.php:169-176`, `ClassesDefinitionsBuildCommand.php:95-98`

- `getById()` caches the instance in `RuntimeCache` (`selectoptions_<id>`); `save()` and `delete()` never update or
  remove that entry (they only clear the DAO's static cache). In the same process, `getById()` returns the deleted
  config, and the old options after a save made through another instance (e.g. one built with `createFromData()`).
  Workers and imports keep the stale copy for their whole lifetime.
- `Dao::exists(string $name)` ignores `$name` and checks the model's own id.
- `protected string $id` has no default: `validateId()` (its `empty($id)` check), `jsonSerialize()`, `getEnumName()`
  and `getPhpClassFile()` throw "must not be accessed before initialization" on a new config instead of the intended
  `InvalidArgumentException`.
- `Dao::getById($id)` calls `setId()`, which throws `InvalidArgumentException` for a reserved word, so
  `Config::getById('List')` throws instead of returning `null`.
- `Listing\Dao::loadList()` appends `Config::getById($id)` without a `null` check (an id from the read targets whose
  data cannot be loaded). `hasConfig()` then calls `->getId()` on `null`, and both class commands call
  `generateEnumFiles()` / `dumpPHPEnum()` on it → `Error` during deployment.

**Fix direction:** set / clear the runtime cache entry in `save()` / `delete()`; use `$name` in `exists()`; initialise
`$id` to `''` (or make it a constructor argument); catch `InvalidArgumentException` in `getById()`; skip and log
`null`s in the listing.

## [ ] B-332 – `getFieldsUsedIn()` misses usages, and the options provider resolves references differently

**Files:** `models/DataObject/SelectOptions/Config.php:262-360` (`:331-340`, `:346-360`); `SelectOptions/Config/Dao.php:137-160`;
`bundles/CoreBundle/src/OptionsProvider/SelectOptionsOptionsProvider.php:28-46`;
`models/DataObject/ClassDefinition/Data/Block.php:306`

- The usage check (run before every delete) only looks at top-level fields and the top-level `localizedfields`.
  Select fields inside a `Block` (and localized fields nested there) are not found, so a config that is still used in
  a block can be deleted. After that, `SelectOptionsOptionsProvider` throws "Missing select options configuration"
  whenever an object of that type is opened or its options are read.
- `isConfiguredAsOptionsProvider()` accepts the short name **and** the fully qualified enum name, trimmed of `\` and
  spaces. The provider calls `Config::getById($fieldDefinition->getOptionsProviderData())` with the raw value: a field
  configured with `Pimcore\Model\DataObject\SelectOptions\Colors` or `Colors ` counts as "used" but cannot load its
  options.
- The provider uses the raw label (`'key' => $selectOption->getLabel()`); the generated enum's `getLabel()` falls back
  to the value. Options without a label show as empty entries in the editor while code shows the value.
- The definitions come from the listings with the shadowing problem of C-45 / C-55, so the same class can be
  reported twice.

**Fix direction:** walk the field tree recursively (layouts, blocks, localized fields at any level); add one
`Config::resolveReference(string): ?string` normaliser used by both the check and the provider; use the value as label
fallback in the provider.

## [ ] B-333 – Select-options data from config is not normalised: one hand-edited YAML value breaks every select-options lookup

**Files:** `models/DataObject/SelectOptions/Config.php:113-141`, `:175-183`, `:219-238`;
`SelectOptions/Config/Dao.php:47-73`; `SelectOptions/Data/SelectOption.php:30-36`, `:118-125`

- Config arrives from YAML, the settings store or the Symfony config tree (`pimcore.objects.select_options.definitions`).
  All setters and the `SelectOption` constructor are strictly typed `string`, and nothing casts:
  `value: 1`, `label: 2024`, `useTraits: ~` or `group: 5` in a hand-written YAML file raise `TypeError` inside
  `getById()` (only `NotFoundException` is caught).
- The listing calls `getById()` for every id, so one such entry breaks the whole listing: the admin list, both class
  commands and the usage check of **every** delete.
- The Symfony config node accepts any scalar (`scalarNode`, `bundles/CoreBundle/src/DependencyInjection/Configuration.php:783-800`),
  so it does not catch this. Pimcore's own writer quotes these values; the problem appears with configuration
  written by hand or by other tools (e.g. definitions committed under `config/`).

**Fix direction:** normalise in `createFromData()` / `Dao::getById()` (`(string)` for scalars, `''` for `null`), reject
non-scalars with a message that names the config id and the key, and log instead of failing the whole listing.

---

# Scope 20 – Document root files not processed in Scope 6 (`Email.php`, `Folder.php`, `Snippet.php`)

> Notes
> - Scanned and skipped files: see the Scope 20 notes in [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md) (no critical item).
> - `Folder.php` has no bug of its own. B-148 (type DAO `id = NULL`) also applies to `Email/Dao.php` and
>   `Snippet/Dao.php` and is not repeated.

## Document `Email` / `Snippet` / `Folder` (Scope 20)

## [ ] B-334 – Email address fields are `varchar(255)`: a longer recipient list makes the save fail or is cut off

**Files:** `bundles/InstallBundle/dump/install.sql:113-118`; `models/Document/Email.php:34-69`, `:79-203`;
`models/Document/Dao.php:102-147` (`update()` writes the type columns as they are)

- `to`, `from`, `replyTo`, `cc`, `bcc` and `subject` are `varchar(255)`. The model accepts any string length and nothing
  checks it before the `UPDATE`.
- A list of 6 entries like `Firstname Lastname <firstname.lastname@company-name.com>` is already over 255 characters.
  With MySQL / MariaDB in strict mode (default since MySQL 5.7 / MariaDB 10.2.4) the save fails with
  `Data too long for column 'to'`; `Document::save()` rolls back and the editor gets a SQL error instead of a
  validation message.
- Without strict mode the value is silently cut at 255 characters: the last address is cut in the middle and
  `Mail::setDocumentSettings()` then throws on it (B-335), or a shortened but still valid address receives the mail.
- No migration changed these columns (`Version20220120121803` only adds the foreign key).

**Fix direction:** migration to `TEXT` (or `varchar(1000)`) for `to` / `cc` / `bcc` / `replyTo`, same in
`install.sql`; keep `subject` / `from` at 255 but check the length in a model `validate()` (B-335) and throw a
`ValidationException` that names the field.

## [ ] B-335 – Email addresses are only parsed when the mail is sent: a typo or a display name with a comma breaks every send of the document

**Files:** `models/Document/Email.php:101-203`; `lib/Mail.php:379-415`, `:729-747`;
`lib/Helper/Mail.php:371-395`

```php
$emailArray = preg_split('/,|;/', ($emailString ?? ''));   // splits inside "Doe, John" <john@example.com>
…
$this->addTo(new Address($toEntry['email'], $toEntry['name'])); // throws RfcComplianceException
```

- The email document stores free text; `setTo()` / `setCc()` / … do not validate, and nothing in `save()` does.
- `parseEmailAddressField()` splits at every `,` and `;`, also inside a quoted display name. `"Doe, John" <john@example.com>`
  becomes `"Doe` and `John" <john@example.com>`; `new Address('"Doe')` throws `RfcComplianceException`
  (`egulias/email-validator` is required by `composer.json:55`, so the address is always validated).
- The exception is thrown inside `Mail::setDocument()`, i.e. as soon as code calls `new Mail(['document' => …])` /
  `setDocument()`. Every send of that document fails (password reset, order confirmation, form mails) until an editor
  fixes the field; the editor was never told, because the save succeeded.
- More than one `from` address is accepted and stored; `setDocumentSettings()` silently uses only the first (`:407-413`).

**Fix direction:** one parser that returns `list<Address>` (quote-aware split, `Address::create()` per entry), used both
by `Mail` and by a new `Email::validate()` that runs on save and rejects invalid entries and more than one `from`
address with a field-specific message. Add typed accessors (I-183) so `Mail` does not parse strings itself.

## [ ] B-336 – `children_supported: false` (snippet, email) is enforced nowhere in the model

**Files:** `bundles/CoreBundle/config/pimcore/default.yaml:297-302`, `:312-318`;
`bundles/CoreBundle/src/DependencyInjection/Configuration.php:1064`; `models/Document.php:415-463`

- The type definitions declare that snippets and emails have no children. The only code that reads
  `children_supported` is the config tree; `correctPath()` loads the parent but never checks its type.
- `Document::create()`, `setParentId()` + `save()`, `Document\Service::copyAsChild()` and imports can put any document
  under a snippet or an email. The child gets a path below the snippet (`/snippets/footer/child`) and is routable.
- Whether the admin tree shows such children is **not verified** (admin UI not in this repo); if it treats the parent as
  a leaf, the child can neither be found nor edited there, and it is deleted together with the parent.

**Fix direction:** in `correctPath()` read `type_definitions.map.<parentType>.children_supported` and throw a
`ValidationException` when it is `false`; add a `Document::supportsChildren(): bool` helper so the admin and API use the
same rule.

## [ ] B-337 – `Email` accepts a content-main document that is never stored, but is still used in the same process

**Files:** `models/Document/Email.php:71`; `models/Document/PageSnippet.php:84`, `:245-262`, `:431-452`, `:462-477`;
`models/Document/Service.php:341-360`; `install.sql:109-122` (no `contentMainDocumentId` column)

- `Email` sets `$supportsContentMain = false`, but `setContentMainDocument()` / `setContentMainDocumentId()` accept a value
  anyway. Only `getEditables()` checks `supportsContentMain()`.
- `resolveDependencies()` adds the main document as a dependency of the email during `save()`, so the dependency row is
  written although the relation itself is dropped (`documents_email` has no column). After a reload the id is `null`;
  until the next save of the email the main document shows the email as a user and cannot be deleted without a
  warning.
- `Document\Service::rewriteIds()` with `enableInheritance` copies rewritten editables from that main document into the
  email in the same request.

**Fix direction:** make `setContentMainDocumentId()` throw (or ignore and log) when `supportsContentMain()` is `false`,
and check `supportsContentMain()` in `resolveDependencies()` and `rewriteIds()` as `getEditables()` does.

---

# Scope 21 – Document sub-directory classes (`Email/`, `Folder/`, `Link/`, `Listing/`, `Page/`, `PageSnippet/`, `Service/`, `Snippet/`)

> Notes
> - Gap-fill scope: scanned and skipped files, and the earlier items that are not repeated, are listed in the
>   Scope 21 notes in [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md) (no critical item).
> - `Email/`, `Folder/`, `Link/`, `Page/`, `PageSnippet/`, `Snippet/` and `Listing/` have no bug beyond B-143, B-146,
>   B-148, B-154 and I-79. All new bugs are in the translation-link part of `Document\Service\Dao`.
> - Translation links are stored as a star: one row `(id = translation, sourceId = source, language)` per member,
>   primary key `(sourceId, language)`, foreign key only on `id` (`install.sql:175-184`). The source document has
>   no row; its language is read from its `language` property at read time.

## Document translation links (Scope 21)

## [ ] B-338 – `addTranslation()` validates nothing: a document joins two groups, links replace each other silently, a missing language is a SQL error

**Files:** `models/Document/Service/Dao.php:41-49`, `:90-103`; `models/Document/Service.php:196-206`;
`lib/Db/Helper.php:38-53`; `bundles/InstallBundle/dump/install.sql:175-184`

```php
Helper::upsert($this->db, 'documents_translations', ['id' => $translation->getId(), 'sourceId' => $sourceId,
    'language' => $language], $this->getPrimaryKey('documents_translations'));   // PK = (sourceId, language)
```

- **Second group:** if `$translation` is already a member of another group, a second row with the same `id` is
  added. `getTranslationSourceId()` then reads `SELECT sourceId … WHERE id = ?` without `ORDER BY`, so which group the
  document belongs to depends on the row order. The language switcher and the XLIFF export
  (`DocumentDataExtractor.php:81`, `:133`) can show the other group.
- **Silent replace:** if the group already has a member for `$language`, the insert hits the primary key and
  `upsert()` falls back to `UPDATE … SET id = <new>`. The previous translation is unlinked without notice.
- **Source language:** `$language` equal to the source document's own language is accepted. `getTranslations()`
  adds the source last (`:81-85`) and overwrites that member, so the link exists but is never shown.
- **Self / reverse links:** `addTranslation($doc, $doc)` or `addTranslation($member, $source)` writes
  `(id = S, sourceId = S)`. This self-row is what corrupts the group on delete (B-339).
- **No language:** without the `$language` argument, `getProperty('language')` can be `null`. Inserting `NULL` into
  `language NOT NULL` fails with "Column 'language' cannot be null", also with `sql_mode = ''` (single-row insert).
  The caller gets a `NotNullConstraintViolationException` instead of a message. `''` is accepted and becomes an
  empty-string key in `getTranslations()`.
- `copyAsChild()` passes a language, so it only hits the silent replace. The admin "link translation" action (not in
  this repo) can reach every case.

**Fix direction:** in `addTranslation()` reject (domain exception with a message) a translation that equals the
source, is the source of another group or already belongs to a group; reject an empty language and the source's
own language; on a primary-key conflict throw instead of replacing, unless the caller asks for `replace: true`.
Optionally add `UNIQUE (id)` to enforce one group per document (needs a data clean-up migration first).

## [ ] B-339 – Deleting a translation source: random successor, the delete can fail, and a self-link leaves an orphaned group

**Files:** `models/Document/Service/Dao.php:105-113`; `models/Document.php:621-646` (`:645`), `:648-690`

```php
$newSourceId = $this->db->fetchOne('SELECT id FROM documents_translations WHERE sourceId = ?', [$document->getId()]);
$this->db->update('documents_translations', ['sourceId' => $newSourceId], ['sourceId' => $document->getId()]);
$this->db->delete('documents_translations', ['id' => $newSourceId]);
```

- The successor is the first row found (no `ORDER BY`). Which translation becomes the new source changes between
  runs and database versions.
- `DELETE … WHERE id = <successor>` deletes **every** row of the successor. If it is also linked into another group
  (B-338), that membership is lost too.
- If the successor is already the source of rows with the same language (possible after B-338), the `UPDATE` breaks
  the primary key `(sourceId, language)`. The `UniqueConstraintViolationException` rolls back `Document::delete()`:
  the document, and any folder that contains it (recursive delete), cannot be deleted.
- With a self-row `(id = S, sourceId = S)` (B-338) the successor can be `S` itself. The update is a no-op, the delete
  removes only the self-row, and all other rows keep `sourceId = S`. `sourceId` has no foreign key, so they survive
  the deletion of `S`. Afterwards `getTranslations()` on any member calls `Document::getById(S)` → `null` → `Error`
  (B-144), and `addTranslation()` keeps adding to the dead group.

**Fix direction:** select the successor with a deterministic order (`ORDER BY language, id`) and exclude
`id = sourceId`. Move only the rows of this group: `UPDATE … WHERE sourceId = :old AND id <> :new`, then
`DELETE … WHERE id = :new AND sourceId = :old`. Check language conflicts first and resolve them (drop the duplicate)
instead of failing the delete. Delete orphaned rows (`sourceId` not in `documents`) in a migration, and add the
foreign key from I-84 (`ON DELETE CASCADE` only after this successor logic has run).

## [ ] B-340 – The link stores a language snapshot: changing or inheriting a different `language` property leaves the translation map wrong

**Files:** `models/Document/Service/Dao.php:55-88`, `:90-103`; `models/Document/Service.php:196-206`;
`bundles/XliffBundle/src/ExportDataExtractorService/DataExtractor/DocumentDataExtractor.php:81`, `:133`

- `documents_translations.language` is written once at link time. Nothing updates it when the document's `language`
  property changes, or when the document is moved below another language root and inherits a different `language`
  (the property is usually inherited). Only `documents_translations` code is in `Service\Dao`
  (`grep documents_translations`).
- `getTranslations()` builds the map from two sources: **members** by the stored row language, the **source** by its
  current property (`:81-85`). After a change the map has the wrong key (`de` → page that is now French), or two
  documents claim the same key and one silently wins.
- The stale value is still part of the primary key: linking a real `de` page later replaces the wrong document
  (B-338 silent replace) or is rejected.

**Fix direction:** treat the row language as derived: refresh it in a `POST_UPDATE` listener when the `language`
property or the parent changes (and in `addTranslation()` take it from the property when no argument is given). Or
drop the column and read the effective property of every member at read time (`Service::getTranslations()` loads
the documents anyway). Decide once and document it.
