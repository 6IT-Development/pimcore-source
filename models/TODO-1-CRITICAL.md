# models – TODO 1: Critical errors

Scope: **(1)** `models/Asset.php` and `models/Asset/**` (Dao, Listing, Service, Folder, Image, Image/Thumbnail/**, Thumbnail/**,
Video, Video/**, Document, Document/**, MetaData/**, WebDAV/**, Enum, Archive/Audio/Text/Unknown), plus the helpers they
directly call where a defect originates there (`lib/Video/Adapter/Ffmpeg.php`, `lib/Config/LocationAwareConfigRepository.php`,
`lib/Messenger/Handler/AssetUpdateTasksHandler.php`)
**(2)** `models/WebsiteSetting.php` and `models/WebsiteSetting/**` (Dao, Listing, Listing/Dao), plus the direct consumer
`lib/Config.php::getWebsiteConfig()` (`:173-262`), `lib/Twig/Extension/WebsiteConfigExtension.php`, the
`website_settings` schema (`bundles/InstallBundle/dump/install.sql:603-615`) and `tests/Model/WebsiteSetting/WebsiteSettingTest.php`
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
**(22)** the not yet processed files of `models/Document/Hardlink/**` (`Wrapper/Email.php`, `Wrapper/Folder.php`,
`Wrapper/Hardlink.php`, `Wrapper/Page.php`, `Wrapper/Snippet.php`, `Wrapper/WrapperInterface.php`; `Hardlink.php`,
`Dao.php`, `Service.php`, `Wrapper.php` and `Wrapper/Link.php` were processed in Scope 6 and are skipped), plus the
direct consumers where a defect originates in the model interplay (`lib/Document/StaticPageGenerator.php`,
`bundles/CoreBundle/src/EventListener/Frontend/StaticPageGeneratorListener.php`,
`lib/Routing/Dynamic/DocumentRouteHandler.php::buildRouteForDocument()` (`:158-188`),
`lib/Navigation/Builder.php::buildNextLevel()` (`:346-420`), `lib/Twig/Extension/PimcoreObjectExtension.php:48`)
**(23)** the not yet processed files of `models/DataObject/Traits/**` (`ClassSavedTrait`, `CompositeIndexTrait`,
`ContextPersistenceTrait`, `DataHeightTrait`, `ElementWithMetadataComparisonTrait`, `FieldDefinitionEnrichmentDataTrait`,
`FieldDefinitionEnrichmentModelTrait`; the other nine traits are already named by earlier items and are skipped), plus
the direct consumers where a defect originates in the model interplay (`ClassDefinition.php::save()` /
`setCompositeIndices()`, `ClassDefinition/Dao.php::update()`, `Localizedfield/Dao.php::createUpdateTable()`,
`ClassDefinition/Data/Relations/AbstractRelations.php`, `ClassDefinition/Data/{Block,Localizedfields}.php`,
`DynamicOptionsProvider/SelectionProviderTrait.php`, `Concrete/Dao.php::getData()`)
**(24)** the not yet processed files of `models/Document/Editable/**` (`Wysiwyg`, `Checkbox`, `Dao`, `Area/AbstractArea`,
`Area/Info`, `Block/AbstractBlockItem`, `Block/Item`, `Areablock/Item`, `BlockInterface`, `EditableInterface`,
`EditmodeDataInterface`, `IdRewriterInterface`, `LazyLoadingInterface`; the other twenty editables are already named by
Scope 6 items and are skipped), plus the direct consumers where a defect originates in the model interplay
(`PageSnippet::update()` / `saveVersion()`, `Editable::getDocument()`, `lib/Tool/Text.php::wysiwygText()`,
`lib/Tool/DomCrawler.php`, `lib/Templating/Renderer/EditableRenderer.php::getEditable()`,
`lib/Document/Editable/EditableHandler.php::renderAreaFrontend()`, the `pimcore.wysiwyg_sanitizer` config in
`bundles/CoreBundle/config/pimcore/default.yaml:62-67`)
**(25)** the not yet processed files of `models/Document/DocType/**` (`Listing.php`, `Listing/Dao.php`; `DocType.php`
and `DocType/Dao.php` are already named by B-155 and are skipped), plus the direct consumers where a defect originates
in the model interplay (`lib/Model/Dao/PimcoreLocationAwareConfigDao.php`, `lib/Config/LocationAwareConfigRepository.php`
(`loadConfigByKey()`, `fetchAllKeys()`, `fetchAllKeysByReadTargets()`), the `documents.doc_types.definitions` config
node (`bundles/CoreBundle/src/DependencyInjection/Configuration.php:845-870`))
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-2-BUGS.md](TODO-2-BUGS.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md),
[CoreBundle TODO-1](../bundles/CoreBundle/TODO-1-CRITICAL.md) (C-13, C-14, C-15 already cover WebDAV permissions/CSRF and
the public thumbnail route; they are not repeated here; C-08 covers request state on shared services in worker runtimes)

These items cause **data loss or corruption** (tree paths, binaries, versions), **source files deleted outside Pimcore**,
**open DB transactions in workers**, **crashes in core asset flows** (create, folder move, video/PDF processing, frontend
thumbnail rendering) and **path traversal / false security status**, and in Scope 2 **deleted or outdated website
settings served indefinitely**, and in Scope 3 **SQL injection in the dependency filter** and **silently lost
dependency rows** (editors are told an element is unused and delete it), and in Scope 4 **translation texts
overwritten or replaced by their key**, **site-wide 500s from one `NULL` row**, **a translation-language permission
bypass** and **SQL identifier injection through the domain name**, and in Scope 5 **a whole site answering 404 after
its root page is renamed or moved** and **site definitions permanently lost when the root page is deleted**, and in
Scope 6 **document / object / tag tree paths corrupted on move**, **open DB transactions after an `Error` in document
and object saves**, **frontend 500s from scheduled blocks**, **a path-less nearest-document cache serving the wrong
document**, **encrypted data and URL slugs silently destroyed**, **code / SQL injection through class definitions**
and **other classes' tables dropped on class delete**, and in Scope 7 **element / workflow writes silently lost
after a notification error leaves a DB transaction open** and **SQL injection in the notification filter exposing every
user's notifications**, and in Scope 8 **all scheduled publishing / unpublishing / deletion stalled by one
failing task** and **scheduled tasks executed without a permission check (deleted users, versions of other
elements)**, and in Scope 9 **asset versions unreadable and their shared binaries deleted with the database version
storage** and **asset version binaries silently replaced by a 1-byte placeholder after an aborted write**, and in
Scope 10 **admin permission checks crashing and Symfony roles silently dropped once a role was loaded through
`UserRole::getById()`** and **deactivated, deleted or re-passworded users keeping their admin sessions**, and in
Scope 11 **the email blocklist never being applied (blocklisted recipients still receive every mail)**, and in
Scope 12 **classification store passwords saved in plain text**, and in Scope 14 **a plausible
field-collection field name making the generated class a PHP fatal** and **`classes-rebuild` dropping field-collection
columns (data loss) when a definition exists in both definition directories**, and in Scope 17
**advanced-relation metadata crashing or being stored under another field name when a column key matches a model
method** (`value`, `data`, `fieldname`, …), and in Scope 18 **an override unit import deleting units
before re-creating them (derived units outside the file permanently lose their base unit; a failure leaves units
missing)**, and in Scope 19 **object-brick rows addressed by object id only (two
`Objectbricks` fields sharing a brick overwrite, move and delete each other's data)**, **`classes-rebuild` dropping
object-brick columns for shadowed definitions** and **PHP code injection / broken enums through select-option
values**, and in Scope 22 **hardlinked pages written into the static-page storage: the hardlink URL is frozen
(served after source changes, unpublish or delete) and the source's pretty URL is overwritten with the hardlink's
rendering**, and in Scope 23 **SQL injection through composite-index definitions** and **localized relations of
one brick type deleted or taken over when another brick type with the same field name is saved**, and in Scope 24
**stored XSS through wysiwyg editables that are rendered from drafts, versions or previews without passing the
sanitizer**.
IDs are stable – refer to them as `C-xx` in later sessions. Line numbers are from commit `1b0711d927` (Scope 2:
`7f0331cab9`, Scope 3: `2d382456c7`, Scope 4: `3e2bf1c4d8`, Scope 5: `0b659e9ce0`, Scope 6: `990568d287`,
Scope 7: `7d51be3297`, Scope 8: `d527536468`, Scope 9: `718bf5020c`,
Scope 10: `70fc218e66`, Scope 11: `a5cc6dc56a`, Scope 12: `d78291fed7`, Scope 13: `1650c79550`, Scope 14: `6bde85085f`,
Scope 15: `205ed92381`, Scope 16: `765372d338`, Scope 17: `d5ca47d13d`,
Scope 18: `bcac4499f6`, Scope 19: `87799e51e6`, Scope 20: `47897412ea`, Scope 21: `86f089cf1a`,
Scope 22: `c9ae84b8cf`, Scope 23: `bad4feee5b`, Scope 24: `e3feff4d48`,
Scope 25: `77fe3d674a`).

> Notes
> - `models/Asset.php`, `models/Asset/Dao.php`, `Asset/Listing/Dao.php`, `Asset/Image/Thumbnail.php` and both thumbnail
>   `Config/Dao.php` / `Config/Listing/Dao.php` files have **no** `declare(strict_types=1)`. Scalar coercion applies there,
>   but `null` to a non-nullable parameter still throws `TypeError`. The rest of `models/Asset/**` is strict.
> - No hard Symfony 7.4 / PHP 8.4 incompatibility (removed API, implicit nullable parameter) was found. The remaining
>   compatibility work is modernisation and is listed in TODO-3.
> - Scope 6: 65 files have **no** `declare(strict_types=1)` – nearly all DAOs, plus `Document/Editable/Areablock.php`,
>   `DataObject/ClassDefinition/Data.php`, `ClassDefinition/Data/Input.php`, `Consent.php`, `QuantityValue/Unit.php`
>   (I-73). No implicit nullable parameter was found; DBAL 4 blockers (`QueryBuilder::getQueryPart()` / `add()`) are in
>   I-76. The project pins `doctrine/dbal: ^3.8`, so they are not critical today.

---

## [x] C-01 – Folder rename/move rewrites every occurrence of the old path in DB (assets, workspaces, and *other element types'* properties)

**File:** `models/Asset/Dao.php:218`, `:221`, `:224`

```php
'update assets set `path` = replace(`path`,' . quote($oldPath . '/') . ',' . quote($new . '/') . ") ... where `path` like '<old>/%'"
```

- MySQL `REPLACE()` replaces **all** occurrences. The `WHERE … LIKE '<old>/%'` only selects rows; it does not anchor the
  replacement. Renaming `/a` → `/b` turns `/a/x/a/photo.jpg` into `/b/x/b/photo.jpg`, while `Asset::save()` moves the
  physical tree to `/b/x/a/…` (`Asset.php:553`). DB row and binary no longer match: the asset appears lost, thumbnails
  break, the next save writes to the wrong place.
- Same corruption for `users_workspaces_asset.cpath` (`:221`) → permissions move to the wrong folder.
- `properties` (`:224`) holds rows for documents, assets and objects (`ctype enum`, install.sql:302) but the UPDATE has
  no `AND ctype = 'asset'`. Renaming asset folder `/products` rewrites `cpath` of every document/object property under
  `/products/`. `cpath` is `utf8_general_ci`, so different casing matches too. Property inheritance (ordered by `cpath`)
  silently changes.
- `Document/Dao.php:214` and `DataObject/AbstractObject/Dao.php:173` use the same pattern (Scope 6: **C-25**).

**Fix direction:** anchor the replacement with bound parameters:
`` SET `path` = CONCAT(:new, SUBSTRING(`path`, CHAR_LENGTH(:old) + 1)) WHERE `path` LIKE :oldLike ``. Same for both
`cpath` updates, and add `AND ctype = 'asset'` on `properties`.

---

## [ ] C-02 – `Asset::updateChildPaths()` (storage fallback) always fails: listing generator consumed by `iterator_count()`

**File:** `models/Asset.php:1709-1714` (called from `save()` `:556` on `UnableToMoveFile`, and `relocateThumbnails()` `:1788`)

```php
$children = $storage->listContents($oldPath, true);
$totalChildren = iterator_count($children);
...
foreach ($children as $child) {   // "Cannot traverse an already closed generator"
```

- Flysystem `listContents()` returns a `DirectoryListing` wrapping a single `Generator` (`pipeListing()`);
  `getIterator()` returns the same generator. `iterator_count()` exhausts it.
- This fallback is the normal path for folder moves on S3/GCS/Azure adapters (directory `move` of a non-existent key
  throws `UnableToMoveFile`). Every folder rename/move there throws; DB rolls back, but thumbnails already moved by
  `relocateThumbnails()` (`:547`) stay moved.
- Once the generator is fixed, `:1717` `str_replace($oldPath, $newPath, '/' . $src)` hits the same "all occurrences /
  prefix collision" problem as C-01 (`/foo/x/foo/y.jpg` → `/bar/x/bar/y.jpg`, `/foo/x/foobar` → `/bar/x/barbar`), so
  files are moved to wrong locations. Fix both together.

**Fix direction:** `$children = $storage->listContents($oldPath, true)->toArray();`, count/iterate that array. Build the
destination with `$newPath . substr('/' . $src, strlen($oldPath))` after asserting the `$oldPath . '/'` prefix.

---

## [ ] C-03 – `Asset::create()` with a rewindable non-local stream always crashes (temp file deleted before MIME guess)

**File:** `models/Asset.php:355-370`

```php
$mimeTypeGuessData = $tmpFile;
if (!$isRewindable) { $data['stream'] = $dest; } else { fclose($dest); unlink($tmpFile); }
...
$mimeType = MimeTypes::getDefault()->guessMimeType($mimeTypeGuessData);   // file no longer exists
```

- `FileinfoMimeTypeGuesser::guessMimeType()` throws `InvalidArgumentException` for a missing file.
- `Asset::create(1, ['filename' => 'a.jpg', 'stream' => fopen('php://temp', 'r+')])` (and `php://memory`, HTTP
  streams that are rewindable, PSR-7 stream wrappers) always fails. `checkMaxPixels()` (`:385`) would use the deleted
  file as well.

**Fix direction:** run the MIME guess and `checkMaxPixels()` before unlinking; clean up `$tmpFile` in a `finally` at the
end of `create()` (see also B-11).

---

## [ ] C-04 – `checkMaxPixels()` deletes the caller's original source file

**File:** `models/Asset.php:425-439` (`unlink($localPath)` at `:433`), inputs from `:354` and `:373`

- `$mimeTypeGuessData` is the caller's own file when `sourcePath` is used (`:373`) or when the passed stream is a local
  file (`:354`). For an image over `assets.image.max_pixels`, `:433` unlinks it.
- `Asset::create(1, ['filename' => 'x.jpg', 'sourcePath' => '/import/huge.jpg'])` permanently deletes
  `/import/huge.jpg` – data loss outside Pimcore storage (import directories, NFS mounts, user uploads kept elsewhere).
- The stream opened at `:375` is leaked on this path.
- The exception message embeds `{$data['filename']}` unescaped in HTML (`:435`) – see B-14.

**Fix direction:** `checkMaxPixels()` only validates; `create()` unlinks only files it created itself (`$tmpFile`), in a
`finally`.

---

## [ ] C-05 – `save()` / `delete()` catch only `Exception`: an `Error` leaves the DB transaction open (silent data loss in workers)

**File:** `models/Asset.php:520-575` (inner retry loop catches `Exception` at `:570`, `:573`), `:633`; `delete()` `:994-1047`
(`:1040`, `:1043`)

- Any `\Error` thrown between `beginTransaction()` and `commit()` skips `rollBack()` and the `POST_*_FAILURE` events.
  Reachable triggers inside the transaction:
  - `resolve($type)` returns `?string`, then `supports(null)` (`:781`) → `TypeError`;
  - `getDao()->getCurrentFullPath()` returns `null`/`''`, then `$storage->fileExists(null)` (`:758`) → `TypeError`
    (B-13);
  - `TypeError`s from listeners, metadata data types, `Video`/`Image` `update()` overrides.
- In PHP-FPM the connection is reset at request end. In messenger workers / FrankenPHP / RoadRunner the connection is
  long-lived, so every following `beginTransaction()` nests inside the dead transaction and **nothing is committed again**
  until the worker restarts.

**Fix direction:** catch `\Throwable` in the inner retry loop and the outer handlers (rethrow after rollback and failure
event). Same in `delete()`.

---

## [ ] C-06 – Binary replacement is non-atomic (delete, then move) – content lost if the move fails

**File:** `models/Asset.php:738-750`

```php
$storage->writeStream($tempFilePath, $src);
$storage->delete($path);
$storage->move($tempFilePath, $path);
```

- Between `delete` and `move` the binary does not exist; concurrent readers (thumbnail workers, downloads, WebDAV GET)
  get "not found".
- If `move()` fails, the original is already gone, the DB transaction rolls back to the old metadata, and an orphan
  `temp_xxxx.ext` is left **inside the asset's folder** (`$this->getRealPath() . uniqid('temp_')`), visible to storage
  listings and `updateChildPaths()`.
- The `delete()` is unnecessary: Flysystem local `move()` is `rename()`, which atomically replaces the directory entry
  and keeps the old inode for version hardlinks (the reason given in the comment at `:738-741`).

**Fix direction:** drop the `delete()` and rely on an overwriting move; write the temp file to a hidden/temp prefix, not
next to the asset; on failure remove the temp file.

---

## [ ] C-07 – `__clone()` closes the original asset's stream: pending new data is dropped and the version records the old binary

**File:** `models/Asset.php:1671-1679` (`closeStream()` at `:1678`)

- `clone` copies the resource handle; `closeStream()` on the clone `fclose`s the handle the original also holds.
- `Version::marshalData()` clones the element through DeepCopy. So
  `$asset->setStream($new); $asset->saveVersion();` (and `save()` with versioning) closes the pending stream;
  `getStream()` then reopens the **stored** file. The version stores the old binary/hash and the new data is silently
  lost (admin "upload new version" with versioning, API imports that clone before save).
- Any user-land `clone $asset` while data is pending has the same effect.

**Fix direction:** in `__clone()` set `$this->stream = null` without `fclose()` (or copy the stream into a fresh
`php://temp` when `dataChanged` is set).

---

## [ ] C-08 – WebDAV `MOVE` reports success after a failed move, and deletes the source before the target is saved

**File:** `models/Asset/WebDAV/Tree.php:39-87` (catch-all at `:84-86`; overwrite branches `:51-56`, `:59-66`)
(Related to CoreBundle **C-13** – that item is the missing permission check; this one is error handling / ordering.)

- Sabre `CorePlugin::httpMove()` deletes an existing destination first (`File::delete()`), then calls `Tree::move()`,
  then answers `201`/`204`. `Tree::move()` catches every `Exception` and only logs it (e.g.
  `DuplicateFullPathException`, storage errors) – the client is told the move succeeded after its destination was
  already deleted.
- In the same-directory overwrite branches, `$sourceAsset->delete()` runs **before** `$asset->save()`; if save throws,
  the source is gone and the target was not updated.
- `$sourceAsset` / `$parent` (`:53`, `:63`, `:75`) can be `null` (e.g. `getValidKey()` sanitised the name), giving an
  `Error` that `catch (Exception)` does not catch → 500 after part of the work is done.

**Fix direction:** run the move in a DB transaction; save the target before deleting the source; throw
`Sabre\DAV\Exception\NotFound` / `Conflict` for null lookups and rethrow failures as `Sabre\DAV\Exception`.

---

## [ ] C-09 – Thumbnail config name is unvalidated and goes into YAML file paths (path traversal on write and delete)

**Files:** `models/Asset/Image/Thumbnail/Config.php:391-396` (`setName()`), `Image/Thumbnail/Config/Dao.php:104`
(`saveData`), `:140` (`deleteData`) → `lib/Config/LocationAwareConfigRepository.php:236-240` (`$directory . '/' . $key . '.yaml'`,
`dumpFile`), `:255` (`unlink`). Same for `Video/Thumbnail/Config.php` / `Video/Thumbnail/Config/Dao.php`.

- Neither model nor DAO restricts the name. A config named `../../../config/packages/evil` writes a YAML file Symfony
  loads on next cache warmup (service/parameter injection); a delete with a crafted name unlinks any writable `*.yaml`.
- The same name is concatenated into the thumbnail storage directory (`Image/Thumbnail/Processor.php:154`) and into the
  `clearTempFiles()` regex.
- Exploitability depends on the admin UI (admin-ui-classic, not in this repo) validating names; if it does, this is
  defense-in-depth – but the model API (`Config::setName()->save()`, imports, migrations) is unguarded either way.

**Fix direction:** validate in `setName()` / `Dao::save()` (`^[A-Za-z0-9_\-]+$`); in
`LocationAwareConfigRepository::getVarConfigFile()` assert the resolved path stays inside the config directory.

---

## [ ] C-10 – Video thumbnail config without bitrates crashes with `TypeError` (frontend 500)

**Files:** `models/Asset/Video/Thumbnail/Processor.php:122-123`, `models/Asset/Video.php:115`;
`lib/Video/Adapter/Ffmpeg.php:350`, `:363`

```php
$converter->setAudioBitrate($config->getAudioBitrate());   // ?int, default null
$converter->setVideoBitrate($config->getVideoBitrate());   // Ffmpeg::set*Bitrate(int)
```

- Both bitrates are optional in the config tree (`Configuration.php:542-543`) and default to `null`
  (`Video/Thumbnail/Config.php:80`, `:86`). Processor is `strict_types` → `TypeError`.
- `Video::getThumbnail()` catches only `Exception` (`:115`), so the `Error` reaches frontend rendering
  (`Document/Editable/Video.php:430`) → page 500. Trigger: any YAML video thumbnail that only defines `items`.

**Fix direction:** call the setters only for non-null values (or make the adapter accept `?int`); catch `\Throwable` in
`Video::getThumbnail()`.

---

## [ ] C-11 – `Video\Thumbnail\Processor::execute()` crashes on a missing job or deleted asset; the global lock and TmpStore entry are never released

**File:** `models/Asset/Video/Thumbnail/Processor.php:200-221` (job-not-found branch), `:231-236`, `:288-293`

- Job-not-found branch: `$instance` is a fresh `new self()`; `$instance->getConfig()` reads the uninitialized typed
  property → `Error`. `Model\Asset::getById($assetId)` can be `null` → `getCustomSetting()` on null. The branch meant to
  mark the job as `error` can never work.
- Main path: the global `video-transcoding` lock is acquired (`:232-233`) **before** `getById()`; a deleted asset makes
  `$asset->getTemporaryFile()` (`:236`) fail on null. Neither lock nor TmpStore entry is released in a `finally`.
- The messenger handler fails, retries, and ends in the failure transport; until the lock TTL (7200 s) expires every
  other video conversion blocks (B-67).

**Fix direction:** load the job before using its config (or pass the config name in the message); return early on a
null asset; wrap lock and temp-file handling in `try/finally`.

---

## [ ] C-12 – Crafted/corrupt video aborts asset processing (spherical XMP reader: `TypeError` / `RuntimeException`)

**File:** `models/Asset/Video.php:270-325` (`:313`, `:320-321`); caller `lib/Messenger/Handler/AssetUpdateTasksHandler.php:125`

```php
$xml = @simplexml_load_string($buffer);
$data = object2array($xml);      // object2array(object $node): false → TypeError
```

- Malformed XML between `<rdf:SphericalVideo` and `</rdf:SphericalVideo>` → `TypeError`; a missing close tag →
  `RuntimeException` (`:313`), leaving the stream unclosed. Neither is caught in `processVideo()`.
- `handleEmbeddedMetaData()`, `saveAsset()` and preview generation in the handler are skipped: duration/dimensions are
  never persisted and the message retries. Any `.mp4`/`.webm` upload containing that byte sequence triggers it.

**Fix direction:** check `$xml instanceof \SimpleXMLElement`; catch, log and return `[]`; close the stream in `finally`.

---

## [ ] C-13 – Video image thumbnail crashes `getPath()` when frame extraction fails (frontend 500)

**Files:** `models/Asset/Video/ImageThumbnail.php:140-153`; `models/Asset/Thumbnail/ImageThumbnailTrait.php:292-315`

- The early `return`s exit `generate()` before the error-placeholder fallback (`:181-186`) and the event dispatch.
  `$this->pathReference` stays `[]`. `getPath()` then calls `convertToWebPath([], …)`: frontend →
  `urlencode_ignore_slash(null)` `TypeError`; backend → returns `null` from `getPath(): string` → `TypeError`. Other
  trait methods read the undefined `$pathReference['type']`.
- Trigger: corrupt video, or ffmpeg `saveImage()` returning `false`. Any template calling
  `$video->getImageThumbnail('x')` then returns 500. The log messages say "document" (copy-paste); the lock is released
  only by the destructor.

**Fix direction:** replace the `return`s with a flag / `try/finally` so control always reaches the placeholder fallback;
release the lock in `finally`.

---

## [ ] C-14 – PDF JavaScript scan is trivially bypassable and always returns `true` (false `SAFE` security status)

**File:** `models/Asset/Document.php:146-176`; caller `lib/Messenger/Handler/AssetUpdateTasksHandler.php:78`

- The file is scanned in independent 1024-byte chunks with **no overlap**: `/JS` or `/JavaScript` straddling a chunk
  boundary is never seen. PDF name escapes (`/J#53`) and compressed object streams also evade it. The loop also stops
  at the first chunk `<= 3` bytes. The asset is then marked `PdfScanStatus::SAFE`, which the admin UI uses to decide
  whether opening the PDF inline is safe.
- The method returns `true` for both UNSAFE (`:166`) and SAFE (`:175`). The name implies "contains JS"; any consumer
  reading it literally treats every PDF as unsafe.

**Fix direction:** keep an overlap buffer between chunks (as `EmbeddedMetaDataTrait::getXMPData` does) and treat the
result as `UNKNOWN`/unsafe unless the PDF is actually parsed; return the `PdfScanStatus` enum (or rename the method).

---

# Scope 2 – WebsiteSetting

> Notes
> - `WebsiteSetting.php` and `WebsiteSetting/Listing.php` are `strict_types`; `WebsiteSetting/Dao.php` and
>   `WebsiteSetting/Listing/Dao.php` are not. No hard Symfony 7.4 / PHP 8.4 incompatibility was found (no implicit
>   nullable parameters, no removed APIs). Modernisation is listed in TODO-3 (I-32–I-40).
> - DB hydration uses `setValues($data, true)` (`lib/Model/Dao/DaoTrait.php:42`), so `NULL` columns are skipped and
>   non-nullable setters (`setCreationDate(int)`) do not crash on legacy rows.

---

## [ ] C-15 – Website settings are cached by id/name and never invalidated: deleted or overridden settings keep being served (same request, messenger workers, and every later request in worker runtimes)

**Files:** `models/WebsiteSetting.php:59`, `:66-85`, `:95-126`, `:273-289`; `lib/Config.php:157-165`, `:173-262`, `:270-273`
(related: CoreBundle **C-08** – request state on shared services in worker runtimes)

- `getByName()` stores `name~~~siteId~~~language` (the **requested** site/language) → id in the static
  `$nameIdMappingCache`. `delete()` (`:275-280`) removes only the key built from the setting's **own**
  name/site/language. Example: `getByName('foo', 5, 'de')` resolves to the global neutral row 7 and caches
  `foo~~~5~~~de → 7`; deleting row 7 removes `foo~~~~~~` only.
- `delete()` never unregisters `RuntimeCache` key `website_setting_<id>` (`:68`, `:82`), so `getById(7)` and the stale
  name mapping keep returning the **deleted object** for the rest of the request.
- Creating a more specific override (site/language row) is invisible to any name already resolved: the mapping keeps
  pointing to the old id.
- `$nameIdMappingCache` is a plain static array that nothing ever clears – not `MessengerClearRuntimeCacheListener`, not
  `LongRunningHelper` (`RuntimeCache::clear()` only). In messenger consumers: once `RuntimeCache` is cleared, the stale id
  is reloaded from DB; if that row was deleted, `getById()` returns `null` and `getByName()` returns `null` even though
  another matching row (e.g. the neutral fallback) exists.
- `lib/Config.php`: `getWebsiteConfig()` stores the resolved config in `RuntimeCache` under a key built from
  **language only** (`getWebsiteConfigRuntimeCacheKey()`, `:157-165`), not the site, and `clearDependentCache()`
  (`WebsiteSetting.php:268-271`) clears only the persistent cache tag, not this runtime entry.
- `RuntimeCache` is a container service without `kernel.reset` / `ResetInterface`, so in FrankenPHP / RoadRunner /
  Swoole worker mode it survives across HTTP requests (no reset found in the code; not verified at runtime). Then:
  - the first site rendered by a worker fixes `pimcore_website_config()` for every later request in that worker,
    **including requests for other sites** (site A's settings served on site B);
  - admin edits and deletes never become visible in that worker until restart.
- Website settings are commonly used for feature switches, contact addresses, tracking IDs and legal texts, so stale or
  cross-site values are a correctness and compliance problem, not just cosmetic.

**Fix direction:**
- Replace the static mapping with a request-scoped service that implements `ResetInterface` (tag `kernel.reset`), or drop
  it and rely on the persistent cache.
- In `save()` / `delete()`: clear `website_setting_<id>`, flush the whole name mapping (mapping keys are by requested
  site/language, so per-key removal cannot be correct), and clear the `getWebsiteConfig` runtime entries.
- Include the site id in `getWebsiteConfigRuntimeCacheKey()`.
- Reset `RuntimeCache` per main request in worker runtimes (together with CoreBundle C-08).

---

# Scope 3 – Dependency

> Notes
> - `models/Dependency.php` and `ElementDependenciesHandler.php` are `strict_types`; `models/Dependency/Dao.php` is not.
>   No hard Symfony 7.4 / PHP 8.4 incompatibility was found (no implicit nullable parameters, no removed APIs; DBAL is
>   `^3.8`). DBAL 4 / modernisation items are in TODO-3 (I-41–I-50).
> - Dependency resolving is **synchronous** by default (`ElementDependenciesMessage` routing is commented out in
>   `bundles/CoreBundle/config/pimcore/default.yaml:38-51`). The handler runs inside `Asset/Document/DataObject::save()`,
>   **after** the element transaction is committed and **before** `POST_ADD` / `POST_UPDATE` (e.g. `Asset.php:611`).
> - The admin controllers that call `getFilterRequiresByPath()` / `getFilterRequiredByPath()` / `getRequiredByWithPath()`
>   live in `pimcore/admin-ui-classic-bundle` (not in this repo, `vendor/` not installed). How they sanitise the filter
>   value was **not verified**.

---

## [ ] C-16 – Dependency filter concatenates the raw search value into SQL (SQL injection; any `'` breaks the query)

**Files:** `models/Dependency/Dao.php:94`, `:99`, `:104` (`getFilterRequiresByPath()`), `:153`, `:158`, `:163`
(`getFilterRequiredByPath()`); public wrappers `models/Dependency.php:105-117`

```php
"... AND LOWER(CONCAT(o.path, o.key)) RLIKE '" . $value . "'"
```

- `$value` is put between single quotes with **no** escaping or binding, three times per query. Source type, id and
  `ORDER BY` are whitelisted/cast; the search value is not. `Dependency` is `@internal`, but it is returned from the
  public `ElementInterface::getDependencies()`, and the filter value comes straight from the admin "dependencies" search
  field.
- Unless every caller escapes the value (not verifiable here, see notes), an authenticated user can inject SQL
  (`x' UNION SELECT … -- `) and read any table (users, password hashes, API keys). This bypasses the per-element
  `isAllowed('list')` check that `Element\Service::getFilterRequiresForFrontend()` applies later.
- Even with honest input: a search for `o'brien` or `kid's` throws a DBAL syntax error (500 in the admin).
- The value is also passed through `sprintf()` (B-95) and interpreted as a regex (B-94).

**Fix direction:** bind the value as a parameter (`… RLIKE :value` / `LIKE :value`, three times or once in a CTE), and
decide literal vs. regex matching (B-94). Build the three near-identical queries in one place (I-43). Add a test with
`'` and `%` in the value (I-50).

---

## [ ] C-17 – `Dependency\Dao::save()` is not atomic: a failure mid-way silently drops dependency rows ("not used" → editor deletes a referenced element)

**Files:** `models/Dependency/Dao.php:222-283`; caller `lib/Messenger/Handler/ElementDependenciesHandler.php:73`;
`models/Asset.php:611`, `models/Document.php:386`, `models/DataObject/AbstractObject.php:609`

- `save()` runs a `DELETE … WHERE id IN (…)` (`:265-268`) and then one `INSERT` per new target (`:270-282`) without a
  transaction. The caller has already committed the element transaction (see notes), so nothing wraps it.
- Any error after the `DELETE` (deadlock on the `combi` unique index with a concurrent save, lost connection,
  `max_allowed_packet`, a `TypeError` for a bad target, B-99) leaves the source with **fewer** rows than it really
  requires. Only `UniqueConstraintViolationException` is caught (and ignored); everything else propagates.
- The `dependencies` table drives the admin "Required by" tab, `isRequired()` / `getRequiredByTotalCount()` and the
  "element is still used" warnings on delete. Missing rows make a referenced asset/document/object look unused. Editors
  delete it and break live pages and relations. Nothing repairs the rows until the source element is saved again.
- With the sync default, the exception also escapes from the element's `save()` after commit, see B-106.

**Fix direction:** wrap the delta in a transaction (`$this->db->transactional(...)`), retry on deadlock
(`RetryableException`), and insert in bulk (I-45). Optionally add a maintenance command that rebuilds dependencies
for all elements, so a damaged graph can be repaired.

---

# Scope 4 – Translation

> Notes
> - `models/Translation.php`, `Translation/Listing.php`, `TranslationEntriesDumper.php` and `Translator.php` are
>   `strict_types`; `models/Translation/Dao.php` and `Translation/Listing/Dao.php` are not. Even without strict types,
>   `null` to a non-nullable parameter throws a `TypeError`, and `Translation::getByKey()` catches only `Exception`.
> - No hard Symfony 7.4 / PHP 8.4 incompatibility was found (no implicit nullable parameters, no removed APIs; the
>   `fgetcsv()` `$escape` argument is passed explicitly, so the PHP 8.4 deprecation does not trigger). DBAL 4 readiness and
>   modernisation are in TODO-3 (I-51–I-63).
> - `Translation::getByKey()` keeps the loaded object in `RuntimeCache`. The **same instance** is returned to every
>   later caller in the request and is the one put into `TranslationEntriesDumper`'s save queue, which is written at
>   `kernel.terminate` / `console.terminate` (`bundles/CoreBundle/src/EventListener/DumpTranslationEntriesListener.php`).
> - The admin translation controllers (grid, import, export) live in `pimcore/admin-ui-classic-bundle` (not in this repo,
>   `vendor/` not installed). How they validate the domain or check permissions was **not verified**.

---

## [ ] C-18 – `getByKey(…, create: true, returnIdIfEmpty: true)` saves the key itself as the translation text in every language

**Files:** `models/Translation.php:262-294`; `lib/Translation/TranslationEntriesDumper.php:29-40`; caller
`models/DataObject/QuantityValue/Service.php:77-82`

- When the key does not exist and `$create` is set, `getByKey()` fills every language with `''` and puts the object into
  the dumper queue (`:273-280`). Then the `$returnIdIfEmpty` block (`:283-291`) replaces every empty language with
  `$idOriginal` **on the same object**. At `kernel.terminate` the dumper saves that object, so the key is written as
  the text of every language.
- This is reached in core: `QuantityValue\Service::generateDefinitionJson()` calls
  `getByKeyLocalized($abbreviation, 'admin', true, true)` for every unit. Each unit abbreviation and long name that has
  no admin translation yet is stored as "translated" with the key in all admin languages (~20 rows per key).
- Effects: these keys never show up as untranslated in the admin, the `debug_admin_translations` `+key+` marker is
  lost, and an editor who later translates one language does not see that the others are only the key. Because the
  object is also in `RuntimeCache`, every later `getByKey($id, 'admin')` in the request returns the same corrupted
  object (B-107).
- Same for any custom code that calls `getByKey()` / `getByKeyLocalized()` with both flags.

**Fix direction:** never mutate the queued model for presentation. Queue a separate object (or clone before the
`$returnIdIfEmpty` step), or better: let `getByKeyLocalized()` return `$translation?->getTranslation($lang) ?: $id`
and drop `$returnIdIfEmpty` from `getByKey()` (I-53). Add a cleanup migration for rows where `text = key` was written by
this path (only rows whose `creationDate = modificationDate` and `userModification = 0`, to keep real edits).

---

## [ ] C-19 – One `NULL` in `text` breaks every `|trans` for that domain and locale (frontend 500); `Dao::save()` can write the `NULL`

**Files:** `lib/Translation/Translator.php:158-161`; `models/Translation/Dao.php:64-66`, `:100-122`;
`models/Translation/Listing/Dao.php:79`; schema `install.sql:437` (`text` is `DEFAULT NULL`),
`Translation/Dao.php:203`

- `Translator::lazyInitialize()` builds the catalogue from `loadRaw()` and calls
  `Tool\Text::removeLineBreaks($translation['text'])`. That parameter is `string`, and `Translator.php` is strict. One
  row with `text IS NULL` throws a `TypeError`. The catalogue is not cached, so **every request** that translates
  anything in that domain/locale fails until the row is fixed. In practice that is the whole frontend of one language.
- `Translation\Dao::getByKey()` (`:66`) and `Listing\Dao::getAllTranslations()` (`:79`) call
  `addTranslation(string $language, string $text)` with the raw column. The `TypeError` is an `Error`, so the
  `catch (Exception)` in `Translation::getByKey()` does not catch it. The admin grid and `Listing::load()` crash for that key.
- The schema allows `NULL`, and `Listing\Dao::cleanup()` (`:149`) checks `text IS NULL`, so `NULL`s are expected.
  Pimcore itself can write one: `setTranslations(array)` does not type its values. `Dao::save()` then compares
  `$text != strip_tags($text)` (`null == ''`, no sanitising) and upserts `text = NULL`. Old data, migrations, direct
  SQL and integrations can also contain `NULL`s.

**Fix direction:** treat `NULL` as `''` when reading (`(string) $row['text']` / `?? ''`) in all three places. Validate
`setTranslations()` / `addTranslation()` values as `string` (I-51). Make the column `NOT NULL` after a data migration
(`UPDATE … SET text = '' WHERE text IS NULL`, I-62).

---

## [ ] C-20 – View-only translation users can save every language: `Dao::save()` treats "no edit languages" as "no restriction"

**Files:** `models/Translation/Dao.php:92-105`; `models/User.php:601-620`

```php
if ($user = User::getById($this->model->getUserModification())) {
    $editableLanguages = $user->getAllowedLanguagesForEditingWebsiteTranslations();
}
…
if (count($editableLanguages) && !in_array($language, $editableLanguages)) { continue; }
```

- `getAllowedLanguagesForEditingWebsiteTranslations()` says (docblock `User.php:602-603`): "If only edit languages are
  empty (but view languages not) **empty array is returned**". In that case the user may edit **no** language.
- The DAO uses `count($editableLanguages)` as "restriction active". `[]` turns the check off, so a user who may only
  **view** some languages can write **all** languages, including languages they cannot even view.
- The model layer is the last line of defence for every save path (admin grid, CSV import, REST/GraphQL bundles,
  custom code). Whether the admin controllers check again before calling `save()` was not verified (see notes).
- `in_array()` is loose, and the check runs only for non-admin domains (by design). Custom domains use the
  website-translation permissions, which is not documented.

**Fix direction:** separate "unrestricted" (`null`) from "nothing allowed" (`[]`). Change the user API to return
`null` for "all languages", or check `$user->isAdmin()` / the view list explicitly in the DAO. Reject disallowed
languages with an exception instead of skipping them silently (B-111). Move the check from the DAO into the model or
a voter (I-55).

---

## [ ] C-21 – The translation domain is never validated in the model and goes into SQL identifiers and DDL (`translations_<domain>`)

**Files:** `models/Translation.php:179-182`, `:246-263`, `:386-444`; `models/Translation/Dao.php:40-43`, `:53-56`,
`:132`, `:191-210`; `models/Translation/Listing/Dao.php:33-36`

- `Translation::setDomain()` accepts any string. `Dao::getDatabaseTableName()` concatenates it into the table name, and
  every query uses that name **unquoted** (`SELECT * FROM translations_<domain> WHERE …`, `:53`, `:56`, `:141`). Only
  `Listing::setDomain()` and the Translator check `isAValidDomain()` first. `Translation::getByKey()`,
  `getByKeyLocalized()`, `importTranslationsFromFile()`, `save()` and `delete()` do not.
- If a caller passes a request value as the domain (the admin import/grid controllers pass a `domain` request parameter;
  not verified, see notes), a value like `messages WHERE 1=0 UNION SELECT … -- ` injects SQL into the `SELECT`. The
  `catch (Exception)` in `getByKey()` hides the error, so blind injection works without visible errors.
- With `$create = true`, a `TableNotFoundException` for an **unregistered** domain queues the entry. `save()` then runs
  `CREATE TABLE IF NOT EXISTS \`translations_<domain>\`` (`:199`). Any string creates a new table, and a backtick in the
  domain breaks out of the quoted identifier. This also bypasses `pimcore.translations.domains`: the table exists,
  but `isAValidDomain()` still says `false` and the entries cannot be listed.
- `isAValidDomain()` (`:183`) uses `sprintf('… translations_%s …')` too. It is only safe because it checks the config
  list first.

**Fix direction:** validate the domain in one place (`setDomain()` and every static entry point): it must be in
`getRegisteredDomains()` and match `/^[a-z0-9_]+$/i` (also validate the config node in
`CoreBundle/DependencyInjection/Configuration.php:78-81`). Quote the table name with `quoteIdentifier()` everywhere.
Never create tables as a side effect of a lookup; create them for the configured domains at install/migration time
(I-62).

---

## [ ] C-22 – Missing-translation auto-create overwrites existing texts: a stale snapshot of all languages is saved at `kernel.terminate`, and after a swallowed DB error a blank one

**Files:** `lib/Translation/Translator.php:240-267`; `models/Translation.php:246-297`;
`models/Translation/Dao.php:99-123`; `lib/Translation/TranslationEntriesDumper.php:34-40`

- When a key is missing from the catalogue of the current locale, `checkForEmptyTranslation()` loads the key with
  `Translation::getByKey($id, $domain)` and queues it for saving. That is the normal case when the key exists in the DB
  but not for this locale (a language was added in the system settings, or the key was imported for only some
  languages).
- **Stale snapshot:** the dumper saves the object at `kernel.terminate`. `Dao::save()` upserts **every language** in
  the object, not just the new empty one. An editor's change to another language of that key between the load and
  `kernel.terminate` (or in a long CLI / worker run, much longer) is overwritten with the old text. Admin users get no
  warning.
- **Blank overwrite:** `getByKey()` returns `null` for **any** `Exception` (`:262-267`): lost connection ("MySQL server
  has gone away" in long-running workers), lock wait timeout, a missing column. The Translator then treats the key as
  new and queues `new Translation()` with `''` for **all** website languages (`:255-264`). If the connection works again
  at terminate (DBAL reconnects, or the error was transient), the upsert **blanks the existing texts in every
  language** of that key. Nothing is logged.
- The same applies to `importTranslationsFromFile()` (`getByKey($textKey, $domain, true)`, `:444`) after a transient
  error: the import continues with an empty object and writes only the CSV columns, but the dumper also writes the
  blank object for every language.

**Fix direction:** when auto-creating, **insert only the missing language rows** (`INSERT IGNORE` /
`INSERT … ON DUPLICATE KEY UPDATE key = key`), never upsert the full snapshot. Let `getByKey()` swallow only "not
found" (B-108) and rethrow everything else. Save only changed languages in `Dao::save()` (B-110, I-55).

---

# Scope 5 – Site

> Notes
> - `models/Site.php` and `Site/Listing.php` are `strict_types`; `Site/Dao.php` and `Site/Listing/Dao.php` are not.
>   Hydration goes through the non-strict `AbstractModel::setValue()`, so DB strings/ints are coerced into the typed
>   setters. No hard Symfony 7.4 / PHP 8.4 incompatibility was found (no implicit nullable parameters, no removed APIs,
>   DBAL is `^3.8`, `ext-intl` for `idn_to_ascii()` is required in `composer.json`). DBAL 4 readiness and modernisation
>   are in TODO-3 (I-64–I-72).
> - `Site::$currentSite` never being reset between HTTP requests in worker runtimes is already **CoreBundle C-08**; it
>   is not repeated here. The CLI / Messenger side is B-126.
> - The admin site editor (`DocumentController` "use as site" / "remove site") lives in
>   `pimcore/admin-ui-classic-bundle` (not in this repo, `vendor/` not installed). Whether it clears caches beyond
>   `Site::save()` / `delete()` was **not verified**.
> - No code in this repo listens to `SiteEvents::*`.

---

## [ ] C-23 – The persistent site cache stores a serialized copy of the root page and is only cleared by the `site` tag: renaming or moving a site root makes the whole site answer 404

**Files:** `models/Site.php:68-92`, `:109-134`, `:256-262`, `:285-292`, `:348-356`; `lib/Tool/Frontend.php:70-95`;
`bundles/CoreBundle/src/EventListener/Frontend/RoutingListener.php:111-124`; `models/Document.php:516-526`

```php
Cache::save($site, $cacheKey, ['system', 'site'], null, 999);   // $site->rootDocument is a full Document\Page
```

- Hydration calls `setRootId()` (`:256-262`), which loads `Document\Page::getById()` into `$rootDocument`. `Site` has
  no `__sleep()` / `__serialize()` of its own, so `AbstractModel::__sleep()` keeps `rootDocument`, and the cache entries
  `site_id_<id>` / `site_domain_<md5(host)>` contain a **frozen copy of the root page** (path, key, properties, …).
- `CoreCacheHandler::prepareCacheTags()` adds element tags only when the cached value itself is an element. The entry
  is tagged `system` + `site` only, so `Document::clearDependentCache()` (`document_<id>`, `document_properties`,
  `output`) never clears it.
- `getRootPath()` (`:285-292`) is derived from that copy. After the root page is renamed (`/en` → `/english`) or moved,
  `RoutingListener::resolveSite()` keeps prefixing the **old** root path to every request path on the site's domains,
  so **every page of the site returns 404**. `Document::getPath()` / `getFullPath()` (`Document.php:696-764`, `:812-822`)
  strip the old prefix, so links on other sites are wrong too. It only recovers after a full cache clear (or any other
  `site` / `system` tag clear).
- The same applies to every other root-page change that the site logic reads (published flag, properties, type
  conversion – B-134).
- `Frontend::getSiteMapping()` caches `root path → site id` under `sites_path_mapping` with tags `system`, `resource`.
  `Site::clearDependentCache()` clears only `site`, so **creating, deleting or re-rooting a site** leaves the mapping
  stale: `getSiteForDocument()` / `getSiteIdForDocument()` return no site or the wrong one (cross-site links, static
  page paths, preview host).
- The cached object is also big: every site lookup unserializes a full page document.

**Fix direction:**
- Never serialize the root document: add `__serialize()` / `__sleep()` that drops `rootDocument` (keep `rootId`), and
  load it lazily in `getRootDocument()` through `Document\Page::getById()`, which has its own correctly tagged cache.
  Compute `getRootPath()` from that lazy document.
- Tag the site entries with `document_<rootId>` as well (or clear `site` from a document save/move listener when the
  document is a site root), and tag `sites_path_mapping` with `site` so `clearDependentCache()` covers it.
- Long term: one cached "site registry" (I-67) instead of per-key entries.

---

## [ ] C-24 – Deleting a site's root page silently deletes the site through the FK cascade: the site definition is lost for good, the domain keeps routing to the deleted path

**Files:** `bundles/InstallBundle/dump/install.sql:381-382`; `models/Site.php:399-408`; `models/Site/Dao.php:149-159`;
`models/Document.php:648`; `models/Element/Recyclebin/**`

```sql
CONSTRAINT `fk_sites_documents` FOREIGN KEY (`rootId`) REFERENCES `documents` (`id`) ON DELETE CASCADE
```

- When the root page (or any ancestor folder) is deleted, MySQL removes the `sites` row. `Document::delete()` and
  `Document\Dao` know nothing about sites, so `Site::delete()` never runs:
  - no `SiteEvents::PRE_DELETE` / `POST_DELETE`;
  - `UrlSlug::handleSiteDeleted()` is skipped → `object_url_slugs` rows for that site id stay;
  - `Site::clearDependentCache()` is skipped → `site_domain_<md5>` / `site_id_<id>` keep returning the deleted site.
    `RoutingListener` still sets it as the current site and prefixes the deleted root path: the domain answers **404 for
    every URL** instead of falling back to the main site, until a full cache clear (C-23).
- **Data loss:** the recycle bin stores the document tree only. Restoring the page brings back the documents but **not
  the site** (domains, main domain, error pages, redirect flag). An accidental delete of a site root – or of a folder
  that contains one – loses the site configuration permanently; editors usually notice only when the domain is down.
- Admin UI behaviour (warning before deleting a site root) was not verified (see notes).

**Fix direction:**
- Change the FK to `ON DELETE RESTRICT` (or `SET NULL` with a disabled-site state) in a migration, and in
  `Document::delete()` (or a `DocumentEvents::PRE_DELETE` listener in the model layer) refuse to delete a site root
  unless the site is removed first, or call `Site::delete()` explicitly inside the same transaction.
- Store the site definition in the recycle bin item of its root page and restore it together with the page.
- Clear the site caches and run the slug cleanup on every deletion path (B-135).

---

## Document / Element / DataObject (Scope 6)

## [ ] C-25 – Document and object move/rename rewrites every occurrence of the old path in DB (children, workspaces, and *other element types'* properties)

**Files:** `models/Document/Dao.php:196-217` (`:208`, `:211`, `:214`); `models/DataObject/AbstractObject/Dao.php:154-179`
(`:167`, `:170`, `:173`); callers `models/Document.php:328-341`, `models/DataObject/AbstractObject.php:540-548`

- Same pattern as C-01: `` `path` = replace(`path`, '<old>/', '<new>/') `` with a `WHERE … LIKE '<old>/%'`. MySQL
  `REPLACE()` replaces **all** occurrences; the `WHERE` only selects the rows.
  - Renaming document `/en` → `/de` turns `/en/products/en/teaser` into `/de/products/de/teaser`. The child row now
    points to a parent path that does not exist: `getByPath()` fails, the `(path, key)` unique index can collide, and
    the next save of the child (`correctPath()` re-reads the parent path) moves it somewhere else again.
  - Same for objects (`/products/shoes/products/x`).
- `users_workspaces_document.cpath` / `users_workspaces_object.cpath` (`:211`, `:170`) get the same corruption →
  permissions move to the wrong subtree.
- `properties` (`:214`, `:173`) has no `AND ctype = 'document'` / `'object'`: moving document folder `/products`
  rewrites the `cpath` of **asset and object** properties below `/products/…` (and with `utf8_general_ci`, below
  `/Products/…`). Property inheritance (sorted by `cpath`) silently changes for the other element types.
- C-01 already noted these two places as "outside scope, same fix"; they are tracked here.

**Fix direction:** one helper in `Element\Dao` used by all three element types:
`` SET `path` = CONCAT(:new, SUBSTRING(`path`, CHAR_LENGTH(:old) + 1)) WHERE `path` LIKE :oldLike `` with bound
parameters; the same for both `cpath` updates, plus `AND ctype = :ctype` on `properties`.

---

## [ ] C-26 – Document / DataObject `save()` and `delete()` catch only `Exception`: an `Error` leaves the DB transaction open (and global flags changed) in workers

**Files:** `models/Document.php:310-366` (inner retry `catch (Exception $e)` at `:346`), `:399`, `delete()` `:648-690`
(`:672`); `models/DataObject/AbstractObject.php:518-590` (`:557`), `:626`, `delete()` `:448-492` (`:468`);
`models/Document/PageSnippet.php:208`; `models/DataObject/Concrete.php:235-286` (`:277`)

- Same defect as C-05 (Asset). Any `\Error` between `beginTransaction()` and `commit()` skips `rollBack()` and the
  `POST_*_FAILURE` events. Reachable `Error`s inside the transaction (the files are `strict_types`):
  - `Classificationstore\Dao::save()`: `reset([])->getKeyId()` for an empty group, `DefinitionCache::get()` `null` for a
    deleted key (B-181);
  - `InheritanceHelper::doUpdate()`: `$result['id']` on `false`, `Concrete::getById()` `null` (B-186);
  - `PageSnippet::update()` → `Editable::save()` / `getDataForResource()` with typed-property `TypeError`s (B-163);
  - listeners of version events (`saveVersion()` runs inside the transaction).
- `AbstractObject::save()` additionally sets `hideUnpublished = false` before `beginTransaction()` (`:520-521`) and
  restores it only on success and in `catch (Exception)` (`:552`, `:566`). After an `Error` the flag stays `false`
  for the process: frontend listings and relation getters start returning unpublished objects. Dirty detection stays
  disabled for new objects as well (B-175).
- In PHP-FPM the connection is reset at request end. In Messenger workers / FrankenPHP / RoadRunner the connection is
  long-lived: every later `beginTransaction()` nests inside the dead transaction and **nothing is committed again**
  until the worker restarts (silent data loss).
- `Document::delete()` calls `$this->rollBack()` unguarded in its `catch` (`:673`): a failing rollback replaces the
  original exception (AbstractObject guards it).

**Fix direction:** catch `\Throwable` in the inner retry loop and in the outer handlers of `save()`, `delete()` and
`saveVersion()` (rethrow after rollback + failure event); restore `hideUnpublished` / dirty detection in `finally`.

---

## [ ] C-27 – `Document\Service::getNearestDocumentByPath()` caches its result without the path: the shared service returns the first document for every later lookup

**Files:** `models/Document/Service.php:461-526` (`:467`, `:470-471`, `:488`, `:502`); service definition
`bundles/CoreBundle/config/documents.yaml:49-50` (shared, public, no reset); callers
`bundles/CoreBundle/src/EventListener/Frontend/DocumentFallbackListener.php:109`,
`bundles/CoreBundle/src/EventListener/ResponseExceptionListener.php:147`,
`lib/Routing/Dynamic/DocumentRouteHandler.php:138`

```php
$cacheKey = $ignoreHardlinks . implode('-', $types);   // path and site are not part of the key
if (isset($this->nearestPathCache[$cacheKey])) { $document = $this->nearestPathCache[$cacheKey]; }
```

- `nearestPathCache` lives on the container service. The key contains only the hardlink flag and the type list.
- PHP-FPM: within one request every further call with a different path (sub-requests, hardlink resolution in
  `DocumentRouteHandler`, the 404 handler after a fallback lookup for another path) gets the first result.
- Worker runtimes (FrankenPHP, RoadRunner, Swoole): the container survives the request, so **every following request**
  gets the nearest document of the first request. `DocumentFallbackListener` sets `_locale`, the content document and
  the site context from it → wrong language, wrong site, wrong error page and wrong hardlink target for all visitors
  until the worker restarts.
- A result found through the site pretty-URL branch (`:492-505`) is reused for requests of other sites.

**Fix direction:** key by `[path, site id, ignoreHardlinks, types]` (small bounded array) or drop the cache;
implement `ResetInterface` and tag the service with `kernel.reset`.

---

## [ ] C-28 – Scheduled block: frontend 500 as soon as no entry lies in the future (`updateOutputCacheLifetime()` receives `null`)

**File:** `models/Document/Editable/Scheduledblock.php:70-122` (`:83`, `:87-89`, `:95`, `:110`)

```php
$nextElement = null;
foreach ($this->indices as $element) { if ($element['date'] <= $outputTimestamp) { $currentElement = $element; } elseif (empty($nextElement)) { $nextElement = $element; } … }
$this->updateOutputCacheLifetime($outputTimestamp, $nextElement);   // private function …(int $outputTimestamp, array $nextElement)
```

- The file is `strict_types`. When every entry date is in the past, `$nextElement` stays `null` → `TypeError`.
- This is the normal state of every scheduled block once its last scheduled change has passed. A new block gets one
  default entry with `date => time()` (`setDefault()`, `:58-68`), which is in the past on the next request.
- The call happens before the full-page-cache check, so it fails with or without output cache. Editmode returns all
  entries (`:72-73`) and never reaches the call, so editors do not see the error; only frontend visitors do (Twig
  `pimcore_iterate_block` → page 500).
- Logic error behind it: "no future entry" must leave the cache lifetime alone; the old untyped code computed a negative
  lifetime from `null['date']`.

**Fix direction:** `?array $nextElement`, return early for `null`, only lower the lifetime for a positive value.

---

## [ ] C-29 – Moving a root-level tag corrupts the `idPath` of its whole subtree

**Files:** `models/Element/Tag/Dao.php:51-87` (`:62-65`, `:75-77`); `models/Element/Tag.php:238-245` (`setParentId()`),
`:325-339` (`correctPath()`)

```php
// tag 5 at root: idPath '/', child 7: '/5/'. Move 5 under 3 → new idPath '/3/'
UPDATE tags SET idPath = REPLACE(idPath, '/', '/3/') WHERE idPath LIKE '/5/%'   -- '/5/' → '/3/5/3/', '/5/7/' → '/3/5/3/7/3/'
```

- The original `idPath` of a top-level tag is `'/'` (truthy), so `REPLACE()` rewrites **every slash** of every
  descendant path. Deeper moves are only safe by accident (ids are unique path segments).
- Consequences: descendants get paths that do not match their parents. As soon as any tag in the chain is saved again
  (rename, move), `correctPath()` recomputes the correct prefix, the subtree `REPLACE`/`LIKE` no longer matches, and:
  - `getElementsForTag(…, considerChildTags: true)` misses child tags and their elements;
  - `Tag::delete()` (`LIKE '<fullIdPath>%'`, `:99`) leaves orphaned child tags and their `tags_assignment` rows;
  - `idPath` grows three times per move → `varchar(190)` overflow → save errors in deeper trees; the
    `(idPath, name)` unique index can collide.
- Dragging a root tag under another tag is a normal admin action.

**Fix direction:** anchored `CONCAT(:new, SUBSTRING(idPath, CHAR_LENGTH(:old) + 1))` with bound parameters; a repair
migration that recomputes every `idPath` from `parentId`.

---

## [ ] C-30 – Encrypted fields: a decryption failure loads `null`, and the next save overwrites the ciphertext

**Files:** `models/DataObject/ClassDefinition/Data/EncryptedField.php:112-150` (`:119-125`, `:139-144`), `:66-86`,
strict-mode default `:42-50`, `:315-323`; write path `models/DataObject/Concrete/Dao.php:230-259`

- In the default non-strict mode a key that cannot be loaded (missing / rotated / different
  `pimcore.encryption.secret`, e.g. after a deploy or on a copied database) and any `Crypto::decrypt()` failure only log
  and return `null`.
- The object loads with the field empty. Store-table columns are written on **every** save (they are never
  "untouchable"), so the next save by an editor, workflow, import, scheduled task or maintenance job writes
  `getDataForResource(null)` → `NULL`: the encrypted value is destroyed permanently, with only a log line.
- Same in localized fields, bricks and field collections.

**Fix direction:** never turn a failed decryption into `null`. Return a value object that keeps the raw ciphertext and
is written back unchanged (or skip the column on save), and refuse edits of that field; consider making strict mode
the default.

---

## [ ] C-31 – Frontend slug resolution deletes slug rows when the field definition lookup fails; the field-collection branch always inspects item 0

**Files:** `models/DataObject/Data/UrlSlug.php:249-346` (`:318-336`, `:340-344`); callers
`lib/Routing/Dynamic/DataObjectRouteHandler.php:91`, `models/DataObject/ClassDefinition/Data/UrlSlug.php:207-212`

- `getAction()` runs on every slug route match (anonymous public requests). If it cannot find a `UrlSlug` field
  definition it calls `$this->delete()` and throws.
- Field collections: the slug row stores the item index in `position` (`ClassDefinition/Data/UrlSlug.php:275`), but
  `getAction()` reads `$fcValue->get(0)` (`:330`). In a collection with mixed types, where item 0 is a type without
  this slug field, **the first request for the slug of item n deletes it** → the URL answers 404 for good, until the
  object is saved again.
- `ClassDefinition::getById()` returns `null` for any include error of the definition file (B-185): during a deploy
  window or a broken definition every requested slug of that class is deleted.
- `ClassDefinition\Data\UrlSlug::checkValidity()` calls `getAction()` for an existing slug while saving another
  object (`:207-212`), so the same deletion happens from admin saves.
- Deleting data from a GET handler is also racy (two requests, cache entries keep the deleted slug, `:345`).

**Fix direction:** resolve with `get((int)$this->getPosition())`; never delete during request handling (log and
answer 404); move the cleanup to class save / a maintenance task; cache the resolved action per
`(classId, ownertype, ownername, fieldname)`.

---

## [ ] C-32 – Class names, class ids, field names and select-option ids are not validated with anchored patterns: they reach generated PHP code, file paths and DDL

**Files:** `models/DataObject/ClassDefinition.php:331-367` (`:350`, `:355`, `:359`), `getDefinitionFile()` `:576-579`,
`getPhpClassFile()` `:584-587`; `models/DataObject/ClassDefinition/Data.php:1297-1312` (`isForbiddenName()`, no
character check); `models/DataObject/ClassDefinition/Dao.php:96-222` (name and id inside DDL strings, `:117-118`);
`models/DataObject/ClassDefinition/Helper/Dao.php:25-110` (field names in backticks, not escaped);
`models/DataObject/SelectOptions/Config.php:78-91`, `:372-375`; generators in `lib/DataObject/ClassBuilder/**`

```php
if (!preg_match('/[a-zA-Z]\w+/', $this->getName())) …                 // matches anywhere in the string
if (!preg_match('/[a-zA-Z0-9]([a-zA-Z0-9_]+)?/', $this->getId())) …     // same
```

- `Foo-Bar`, `../../public/ab`, `X extends Concrete {} … class Y` all pass.
  - The name becomes the PHP file path `DataObject/<Name>.php` / `definition_<name>.php` (path traversal → a PHP file
    written outside `var/classes`, e.g. into the web root) and the class name in generated code (code injection, or a
    syntax error that fatals every request autoloading the class).
  - The id becomes the table names `object_store_<id>`, `object_query_<id>`, `object_relations_<id>` that are
    concatenated into DDL and DML across the model (`ClassDefinition/Dao.php:104-150`, InheritanceHelper, listings), and is written as
    `DEFAULT '<id>'` / `DEFAULT '<name>'` into DDL → SQL injection / broken schema.
- Field names are only checked against the forbidden-name list: any character is accepted and ends up in generated
  getters/setters (`get<Name>`) and in backtick-quoted column names without escaping (a backtick breaks out).
- Select-options ids are only checked against reserved words and become enum class names and file names.
- Field-collection and object-brick keys are already validated with `^[a-zA-Z]\w*$`
  (`Fieldcollection/Definition.php:308`, inherited by bricks); the gap is classes, fields and select options.
- Who can trigger it: users with the `classes` permission (not necessarily admins), definition imports
  (`pimcore:definition:import:class`, admin JSON import), custom tooling. Validation in the admin UI bundle was not
  checked here; the model accepts everything.

**Fix direction:** anchored patterns (`^[A-Za-z][A-Za-z0-9_]*$` plus length limits and PHP reserved words) for class
name, class id, field names and select-option ids, checked in the setters and in `save()`; quote identifiers with
`quoteIdentifier()` everywhere; generate code with a code generator instead of string concatenation (I-83).

---

## [ ] C-33 – Deleting a class drops tables of other classes: unescaped `SHOW TABLES LIKE` patterns

**File:** `models/DataObject/ClassDefinition/Dao.php:240-296` (`:260`, `:267`, `:273`, `:282`)

```php
"SHOW TABLES LIKE 'object\_collection\_%\_" . $id . "'"      // suffix match
"SHOW TABLES LIKE 'object\_brick\_%\_" . $id . "'"           // suffix match
"SHOW TABLES LIKE 'object\_localized\_query\_" . $id . "\_%'" // prefix match
```

- The class id is not escaped (an `_` in it is a wildcard) and `%` spans `_`. Custom ids (`[A-Za-z0-9_]` is allowed):
  - deleting class `archive` drops `object_brick_store_<key>_news_archive` and `object_collection_<key>_news_archive`,
    i.e. the brick and field-collection **store** tables of class `news_archive` → data loss;
  - deleting class `news` drops `object_localized_query_news_archive_<lang>` and the `object_localized_news_archive_*`
    views.
- The numeric default ids are not affected; projects with readable class ids are.

**Fix direction:** build the exact table names from the known brick / field-collection keys and valid languages (or
query `information_schema.tables` with escaped `_` / `%` and the exact `_<key>_<id>` structure); better, track the
tables owned by a class.

---

# Scope 7 – Metadata / Notification

> Notes
> - `models/Notification.php`, `Notification/Listing.php` and `Notification/Service/**` are `strict_types`;
>   `Notification/Dao.php`, `Notification/Listing/Dao.php`, `Metadata/Predefined/Dao.php` and
>   `Metadata/Predefined/Listing/Dao.php` are not (I-86). `Metadata/Predefined.php` and `Predefined/Listing.php` are strict.
> - No hard Symfony 7.4 / PHP 8.4 incompatibility was found (no implicit nullable parameters, no removed APIs; Carbon is
>   `^3.0`, DBAL `^3.8`). Modernisation is in TODO-3 (I-86–I-92).
> - The admin controllers (`NotificationController`, predefined-metadata settings) live in
>   `pimcore/admin-ui-classic-bundle`, which is not in this repo (`vendor/` is not installed). Statements about how they
>   call the services are based on the upstream bundle source and were **not verified locally**.
> - `bundles/CoreBundle/config/pimcore/default.yaml:148` sets `sql_mode = ''`: over-long values are truncated silently
>   instead of failing (relevant for B-199).
> - No Scope 7 metadata item is critical. The predefined-metadata id goes into a YAML file path like the thumbnail
>   config name in C-09; it is only reachable through code and imports (the admin creates UUIDs), so it is listed as B-191
>   and covered by the repository-level fix of C-09.

---

## [ ] C-34 – `NotificationService` opens DB transactions and never rolls them back: after any error, later writes in the same request / worker are silently lost

**Files:** `models/Notification/Service/NotificationService.php:59-84` (`sendToUser()`), `:155-171`
(`findAndMarkAsRead()`), `:216-223` (`findAll()`), `:245-252` (`findLastUnread()`), `:303-314` (`delete()`), `:324-330`
(`deleteAll()`), `:336-347`; `lib/Workflow/Notification/PimcoreNotificationService.php:72-78`;
`lib/Workflow/Manager.php:232-243`

```php
$this->beginTransaction();
$sender = User::getById($fromUser);
$recipient = User::getById($userId);
if (!$recipient instanceof User) {
    throw new UnexpectedValueException(...);   // transaction stays open
}
```

- Every public method calls `Db::getConnection()->beginTransaction()` and `commit()` with no `try` / `rollBack()`. Exits
  that skip `commit()`:
  - `sendToUser()`: unknown recipient, empty title or message (validated **after** the transaction is opened), any DB
    error, any exception from a `NotificationEvents::PRE_SAVE` / `POST_SAVE` listener;
  - `findAndMarkAsRead()`: unknown id (`find()` throws), foreign notification (`AccessDeniedHttpException`), and without an
    exception when `$recipientId` is `0` (the second `if` is falsy, nothing commits);
  - `delete()`: unknown id; `findAll()` / `findLastUnread()` / `deleteAll()`: DB or hydration errors (B-193).
- The shared DBAL connection then stays at nesting level ≥ 1. Later `beginTransaction()` / `commit()` pairs (element
  saves, versions, notes, dependencies) only change the nesting level, so nothing is committed. When the request ends
  the connection closes and MySQL **rolls back everything written after the failure**, although every save reported
  success.
- Concrete path: a workflow transition with `notificationSettings` → `Manager::applyWithAdditionalData()` →
  `$workflow->apply()` → `NotificationSubscriber` → `PimcoreNotificationService::sendPimcoreNotification()` →
  `sendToUser()` throws → caught and only logged (`PimcoreNotificationService.php:76-78`) → `$subject->save()`
  (`Manager.php:243`) runs inside the orphaned transaction → the element data, new workflow place, version and note are
  lost, and the UI shows success.
- Worker runtimes (CoreBundle C-08) and long CLI processes keep the connection: every later request in that process
  writes into the never-committed transaction and holds row locks, so other processes hit lock wait timeouts. When
  the process ends, all of it is rolled back. A single "mark as read" on a notification that was already deleted
  elsewhere (not found) is enough to trigger it from the admin UI.
- `tests/Unit/Notification/Service/NotificationServiceTest.php:53-66` (`testSendToNoExistUser`) leaves the transaction
  open, so the following tests in the same process run inside it (B-200).
- The transactions protect nothing: each method writes a single row (or deletes rows one by one). Only `findAll()` gains
  a consistent `count` / `select` snapshot.

**Fix direction:** remove the transactions from single-statement operations; where one is needed use
`$db->transactional(fn () => …)` (it rolls back on any `Throwable`); validate before opening a transaction; never catch
and continue around a failed transaction (`PimcoreNotificationService`). Add a test that asserts
`getTransactionNestingLevel() === 0` after every failing service call (I-92).

---

## [ ] C-35 – Notification filter: the request `property` (and `findAll()` filter keys) go into SQL unvalidated (SQL injection, every user's notifications readable)

**Files:** `models/Notification/Service/NotificationServiceFilterParser.php:66-96`, `:103-123`, `:130-174`, `:176-181`;
`models/Notification/Service/NotificationService.php:181-200`

```php
$property = $this->properties[$property] ?? $property;          // raw request value
"$property LIKE :$key"                                           // $key = $property . '_like'
$condition = implode(' AND ', $conditions);                      // findAll(): no parentheses
```

- `getDbProperty()` maps `title` and `timestamp` and returns every other value unchanged. That value becomes both the
  SQL expression and the placeholder name in `parseString()` / `parseDate()` (`LIKE`, `BETWEEN`, `>`, `<`).
  `findAll()` joins all conditions with a bare `' AND '` and, in the `else` branch, writes the filter keys unquoted
  (`$key . ' = :' . $key`).
- The parser reads the raw admin request (`$request->request->get('filter')`). Upstream admin-ui-classic
  `NotificationController::findAllAction()` merges `parse()` into `['recipient' => <current user id>]` and passes it to
  `findAll()` (not verified locally, see notes).
- Example: `filter=[{"type":"string","operator":"like","property":"isStudio OR 1=1 OR @a","value":"x"}]` produces
  `isStudio OR 1=1 OR @a LIKE :isStudio OR 1=1 OR @a_like`. The only placeholder, `:isStudio`, is bound by `findAll()`'s
  own `isStudio` filter; the unused named parameter is ignored (DBAL 3 expands only the placeholders found in the SQL –
  not verified locally, `vendor/` missing); `@a` / `@a_like` are MySQL user variables. Because `AND` binds tighter than
  `OR`, the `WHERE` is true for every row: the caller gets **every user's notifications** (title, message, payload,
  linked elements). With a subquery instead of `1=1`, it becomes a boolean-blind read of the whole database
  (for example `users.password`).
- Any other non-identifier property causes an SQL error (500). Who can trigger it: every backend user with the
  `notifications` permission.

**Fix direction:** allowlist the filterable properties (`title`, `timestamp` → `creationDate`, …) and reject everything
else with a 400; use fixed placeholder names; quote identifiers; wrap each condition in parentheses; replace the
`condition` strings with a typed filter object that the DAO turns into SQL (I-88). Related: B-195 (`findAll()` keys),
B-196 (parser robustness).

---

# Scope 8 – Property / Schedule

> Notes
> - `models/Property.php`, `Property/Predefined.php`, `Property/Predefined/Listing.php`, `Schedule/Task.php` and
>   `Schedule/Task/Listing.php` are `strict_types`; `Property/Dao.php`, `Property/Predefined/Dao.php`,
>   `Property/Predefined/Listing/Dao.php`, `Schedule/Task/Dao.php` and `Schedule/Task/Listing/Dao.php` are not (I-93).
>   `lib/Maintenance/Tasks/ScheduledTasksTask.php` is strict, so `null` to an `int` parameter throws there.
> - No hard Symfony 7.4 / PHP 8.4 incompatibility was found (no implicit nullable parameters, no removed APIs;
>   `Predefined\Dao` already uses a PHP 8.3 typed class constant). Modernisation is in TODO-3 (I-93–I-100).
> - The admin controllers that create scheduled tasks and predefined properties (`ElementControllerBase` /
>   `DocumentController` task saving, `SettingsController` predefined properties) live in `pimcore/admin-ui-classic-bundle`,
>   which is not in this repo (`vendor/` is not installed). Statements about client-supplied task data are based on
>   the upstream bundle source and were **not verified locally**.
> - Maintenance runs in the CLI (`pimcore:maintenance` cron and the `pimcore_scheduled_tasks` Messenger consumer,
>   `bundles/CoreBundle/config/pimcore/default.yaml:50`), so `php_sapi_name() === 'cli'` is always true for
>   `ScheduledTasksTask` (relevant for C-37).
> - `bundles/CoreBundle/config/pimcore/default.yaml:148` sets `sql_mode = ''`: invalid enum values (`properties.type`,
>   `schedule_tasks.action`) are stored as `''` and over-long names are truncated silently (B-201, B-205, B-211).
> - Already covered elsewhere and not repeated: C-25 (move rewrites `properties.cpath` of other element types), B-165
>   (properties of unsaved elements cached under a shared key), B-182 (`copyContents()` moves the source's scheduled
>   tasks), I-75 (`properties.data` PHP serialization), I-76 (`ScheduledTasksDaoTrait::deleteAllTasks()` concatenated SQL).

---

## [ ] C-36 – One failing scheduled task stalls all scheduled publishing, unpublishing and deletion, permanently

**Files:** `lib/Maintenance/Tasks/ScheduledTasksTask.php:43-135` (`:45-47`, `:50`, `:52`, `:74-76`, `:130`);
`lib/Maintenance/Executor.php:48-70` (`:65`); `models/Schedule/Task/Listing/Dao.php:33-45` (`:39`);
`models/Schedule/Task.php:47`, `:190-193`

```php
foreach ($tasks as $task) {
    $taskUser = User::getById($task->getUserId());   // outside the try, strict_types
    try {
        ...
    } catch (Exception $e) {                         // \Error / \TypeError not caught
```

- `User::getById(int $id)` receives `$task->getUserId()`, which is `?int` (column `userId` nullable, default `NULL`).
  A task created through the API without `setUserId()` (imports, custom code, `new Task([...])`) gives a `TypeError`
  **before** the `try`. It leaves `execute()`, and `Executor::executeTask()` catches only `Exception` (`:65`), so the
  Messenger message fails, is retried and ends in the failed transport. Nothing is written to the admin.
- `Task\Listing\Dao::load()` runs `SELECT id` and then `Task::getById()` per row. A row deleted in between (an editor
  saves the element and `deleteAllTasks()` removes the task, or the element is deleted) puts `null` into the list →
  `$task->getUserId()` on `null` → `Error`, same exit.
- Inside the `try`, only `Exception` is caught. A `TypeError` / `Error` from `save()` / `delete()` (event listeners,
  typed-property errors while restoring version data, B-163) aborts the loop the same way.
- The listing has no `ORDER BY`, so the same broken task is reached first in every run (every maintenance interval,
  default every 5 minutes). Every task behind it – timed publication, embargo unpublishing, "delete at" for legal
  takedowns – **never runs**, and the editor sees the tasks still listed as pending.
- A task that fails with an `Exception` stays `active = 1` and is retried in every run forever. For `delete`,
  `Recyclebin\Item::create()` (`:75`, `:95`, `:122`) runs **before** `delete()`: every failed run writes another full
  recycle-bin dump of the element tree (DB row + storage file), so the recycle bin and its storage grow without bound.

**Fix direction:** resolve the user inside a per-task `try { … } catch (\Throwable)`; skip `null` list entries; record
failures (attempt counter or `lastError` column, deactivate after N attempts, log with task id and element);
`ORDER BY date, id`; create the recycle-bin item and delete the element in one transaction; dispatch one Messenger
message per due task so one failure cannot block the queue (I-98). Related: C-37, B-210, B-213.

---

## [ ] C-37 – Scheduled tasks run without a permission check for deleted users, and `publish-version` saves any version id (other elements overwritten)

**Files:** `lib/Maintenance/Tasks/ScheduledTasksTask.php:50-126` (`:50`, `:56-58`, `:83-85`, `:103-105`);
`models/Element/AbstractElement.php:480-492`; `models/User/AbstractUser.php:44-48`;
`models/Schedule/Task.php:43`, `:110-113`, `:168-173`

```php
$taskUser = User::getById($task->getUserId());   // null for a deleted user
$document->isAllowed('publish', $taskUser)       // null → Admin::getCurrentUser() → null → CLI → true
if ($version = Version::getById($task->getVersion())) {
    $document = $version->getData();              // any element of that type
    $document->setPublished(true);
    $document->save();
```

- `isAllowed($type, null)` falls back to `Admin::getCurrentUser()`. There is no admin session in maintenance, and
  `php_sapi_name() === 'cli'` returns `true` (`AbstractElement.php:486-489`). Tasks of a **deleted** user therefore run
  with full rights: deleting or offboarding an editor does not cancel their pending publish / unpublish / delete tasks,
  and the per-element permission check is skipped completely. Deactivated users (`active = 0`) are not checked
  either; `isAllowed()` never looks at `isActive()`.
- `publish-version` loads `Version::getById($task->getVersion())` and saves `$version->getData()` without checking
  `$version->getCid() === $task->getCid()` and the version's element type. The permission check ran on the task's
  element, but the element that is saved is the one inside the version. With the version id of **another** document /
  object / asset of the same type, that other element is overwritten with the old version and published. The
  permission check never sees it. The `version` id of a task comes from the admin request (upstream task saving
  passes the client's task data to `new Task($data)`; not verified locally, see notes). The model accepts any id.
**Fix direction:** load the user. If they are missing or inactive, deactivate the task with a log entry / element note
and never call `isAllowed()` with `null`. Pass the user explicitly through a permission check that has no CLI
fallback. Verify that the version belongs to the task's element (`cid` + `ctype`) both on save (validate in
`Task\Dao::save()`) and on execution. Related: C-36, B-211, B-214.

---

# Scope 9 – Version

> Notes
> - `models/Version.php`, `Version/Listing.php`, `Version/SetDumpStateFilter.php` and all `Version/Adapter/*.php` are
>   `strict_types`; `Version/Dao.php` and `Version/Listing/Dao.php` are not (I-101). `lib/Model/AbstractModel.php`
>   (which calls the setters during hydration) is not strict either.
> - One hard PHP 8.x type defect was found: `DatabaseVersionStorageAdapter::getStream(): ?false` (C-38). It was
>   reproduced with the local PHP 8.4.26 CLI (`TypeError: … Return value must be of type ?false, resource returned`).
>   `tests/Service/Element/VersionTest.php` uses `MockBuilder::setMethods()`, which PHPUnit 10 removed (`composer.json`
>   requires `^10.0 || ^11.0`). `vendor/` is not installed, so the suite was **not run locally** (B-225).
> - The default adapter is `FileSystemVersionStorageAdapter` (`bundles/CoreBundle/config/services.yaml:150-154`). The
>   DB and Delegate adapters are opt-in, but `doc/18_Tools_and_Features/01_Versioning.md:61-114` documents them as
>   supported. The `versionsData` table exists only in that doc and in the test; no installer or migration creates it.
> - `assets.versions.use_hardlinks` defaults to `true` (`Configuration.php:620-627`): FS version binaries are hardlinks of
>   the live asset file whenever both storages are local.
> - The admin version endpoints (preview, diff, "publish this version", public flag and note update) live in
>   `pimcore/admin-ui-classic-bundle`, which is not in this repo. Statements about them are **not verified locally**.
> - Already covered elsewhere and not repeated: C-07 (`__clone()` closes the pending asset stream during
>   `marshalData()`), C-37 (`publish-version` tasks with a version of another element), CoreBundle B-35 (`?v=` /
>   `pimcore_version` load any version without an ownership check), CoreBundle B-23 (`MigrateElementsCommand` null
>   version), B-17 (`VersionDeleteMessage` dispatched inside the delete transaction), TODO-2 `Recyclebin\Item::restore()`
>   `Version::disable()` without `finally` (Scope 6).

---

## [ ] C-38 – `DatabaseVersionStorageAdapter`: every asset version is unreadable (`TypeError`), and deleting the oldest version deletes the binary that all later versions share

**Files:** `models/Version/Adapter/DatabaseVersionStorageAdapter.php:35-57`, `:64-77`, `:79-82`, `:84-94`, `:96-101`,
`:103-110`, `:112-122`; `models/Version.php:171-189`, `:297-302`; `models/Version/Dao.php:96-110`;
`lib/Messenger/Handler/SanityCheckHandler.php:85-94`; `doc/18_Tools_and_Features/01_Versioning.md:61-114`

```php
protected function getStream(string $data): ?false   // null|false only
{
    if ($data) {
        $fileName = tmpfile();
        fwrite($fileName, $data);
        return $fileName;                             // resource → TypeError
```

- `getStream()` is declared `?false` but returns a `tmpfile()` resource, so every call with data throws `TypeError`.
  `loadBinaryData()`, `getBinaryFileStream()` and `getFileStream()` all go through it. `Version::loadData()` calls
  `loadBinaryData()` for **every** `Asset`, so no asset version stored in the DB can be loaded at all: no preview, no
  diff, no restore, no scheduled `publish-version` (the `TypeError` is not caught there either, C-36), no sanity check.
  Document / object versions break only on `getFileStream()`.
- The tests save through the DB adapter but never load (B-225), so this was not caught.
- If the return type is fixed, the stream is still at EOF after `fwrite()` (no `rewind()`). `Asset::getStream()` rewinds,
  but every other caller of `getFileStream()` / `getBinaryFileStream()` reads `''`.
- `fetchOne()` returns `false` for a missing row and `null` for `binaryData = NULL`. `getStream(string)` then throws
  `TypeError` under `strict_types`, and `loadMetaData(): ?string` throws on `false`. The intended "cannot read version
  data" path in `Version::loadData()` (`:273-278`) is never reached.
- **Shared binaries are deleted:** `Version::save()` sets `binaryFileId` to the oldest version of the same asset with
  the same hash (`Dao::getBinaryFileIdForHash()`). The DB adapter then saves `binaryData = NULL`, and later reads go to
  the row of `binaryFileId`. `delete()` ignores `$isBinaryHashInUse` and deletes that whole `versionsData` row, binary
  included. The FS adapter keeps the `.bin` in this case (`FileSystemVersionStorageAdapter.php:124`). Maintenance
  cleanup deletes the oldest versions first. So once an asset has more than `steps + 1` versions with an unchanged
  binary (every metadata-only edit), **every remaining version references a deleted binary**.
- **Re-saving a version wipes its own binary:** `SanityCheckHandler` (`:89-94`) and every `setNote()` / `setPublic()` +
  `save()` re-run the full `save()` (B-219). `getBinaryFileIdForHash()` now finds the version itself (the oldest with
  that hash), so `binaryFileId` = its own id, and `ON DUPLICATE KEY UPDATE binaryData = NULL` deletes its binary and
  the binary of every version that references it. Today the `TypeError` in `getData()` hits first; fixing only the type
  exposes this.
- `save()` runs `executeQuery()` for an `INSERT` (should be `executeStatement()`) and repeats the parameters for the
  `ON DUPLICATE KEY UPDATE` part instead of using `VALUES()` / an alias.

**Fix direction:** return `resource|null` (native `mixed` plus phpdoc) and `rewind()` the stream; treat `false` / `null`
as "not found" with a dedicated exception. In `delete()`, keep `binaryData` while the hash is in use. Better: store
binaries in a content-addressed table keyed by `(cid, hash)` with a reference count. Never overwrite `binaryData` with
`NULL` on update (`COALESCE(VALUES(binaryData), binaryData)`). Ship a migration or command for `versionsData`, and add
load / restore / delete tests for every adapter (I-109). Related: C-39, B-219, B-222, B-223.

---

## [ ] C-39 – Version storage writes are not atomic: an aborted binary write leaves a 1-byte placeholder `.bin` that later versions reuse

**Files:** `models/Version/Adapter/FileSystemVersionStorageAdapter.php:85-110` (`:91-92`, `:99`, `:102-107`);
`models/Version.php:171-189`; `models/Version/Dao.php:96-104`; `models/Asset.php:839-890`

```php
$this->storage->write($binaryStoragePath, '1'); // temp file to determine if stream is local or not
if ($useHardlinks && stream_is_local(...) && stream_is_local($binaryDataStream)) { ... delete(); @link(...); }
if (!$linked) {
    $this->storage->writeStream($binaryStoragePath, $binaryDataStream);   // may throw / be aborted
}
```

- `save()` first writes the string `'1'` to the version's `.bin` path (only to probe whether the stream is local),
  then hardlinks or copies the real binary. The placeholder stays if `writeStream()` throws (S3 / network error,
  disk full, quota) or the process dies while copying. Example: `max_execution_time` while copying a multi-GB video in
  a web request, OOM, a worker killed on deploy.
- `Version::save()` has already inserted the `versions` row with `binaryFileHash` (`Version.php:186`). Without a
  surrounding transaction the row stays committed. That is the case for `Asset::saveVersion()` (`:839-890`, no
  transaction), for re-saved versions (B-219) and for custom code.
- The next version with an unchanged binary (typical: a metadata edit) gets that row from `getBinaryFileIdForHash()`.
  FS `save()` sees that the `.bin` exists (`:91-92`) and skips the write. From then on **every** version with that
  content points to the placeholder. Restoring or publishing any of them writes a 1-byte file containing `1` as the
  asset binary. The stored hash says otherwise, and nothing checks it.
- In the hardlink branch the placeholder is deleted before `link()`. If `link()` fails and `writeStream()` then throws,
  no `.bin` exists at all. `loadData()` then returns the asset without a stream, and restoring it keeps the **current**
  binary next to the old metadata, without an error (B-218).
- Inside the element transaction (`Asset::save()`) the row is rolled back, but the files stay (ids are not reused, so
  this only leaks storage; nothing cleans up orphaned version files).

**Fix direction:** decide on hardlinks from the adapter configuration (both storages are `LocalFilesystemAdapter`)
instead of writing a probe file. Write to a temporary name and `move()` it into place. Write storage before the
`versions` row, or insert the row in a transaction and delete the files when the storage write fails. Before reusing
an existing `.bin` / binary row, check at least its size, and verify `binaryFileHash` on restore. Related: C-07,
C-38, B-218.

---

# Scope 10 – User

> Notes
> - `models/User.php`, `UserInterface.php` and all non-DAO files in `models/User/**` are `strict_types`. The 11 DAO files
>   are not (`AbstractUser/Dao.php`, `Dao.php`, `UserRole/Dao.php`, `Role/Dao.php`, `Role/Folder/Dao.php`,
>   `Listing/Dao.php`, `Listing/AbstractListing/Dao.php`, `Role/Listing/Dao.php`, `Workspace/Dao.php`,
>   `Permission/Definition/Dao.php`, `Permission/Definition/Listing/Dao.php`) (I-110).
> - Hydration goes through `AbstractModel::setValues($data, true)` (`lib/Model/Dao/DaoTrait.php:40-43`): `NULL` columns
>   are **skipped**, so non-nullable setters (`setParentId(int)`, `setLastLogin(int)`, …) do not throw on load, but the
>   property keeps its default or stays uninitialised (B-234).
> - No hard Symfony 7.4 / PHP 8.4 incompatibility was found. `Security\User\User::eraseCredentials()` (deprecated since
>   Symfony 7.3) already carries `#[\Deprecated]`; `AbstractUser\Dao::setLastLoginDate()` already uses the PHP 8.4
>   `new DateTime()->…` syntax. The remaining modernisation is in TODO-3 (I-110–I-121).
> - The admin firewall (`security.yaml`, `_security_pimcore_admin`), the user / role editor and the 2FA setup controllers
>   live in `pimcore/admin-ui-classic-bundle`, which is not in this repo. Statements about them are **not verified
>   locally**. `vendor/` is not installed; nothing was executed.
> - `User::getDefinedPerspectiveNames()` was added in this fork (`77d25eed5a`, "User perspectives without admin bundle
>   dependency"); it reads `pimcore.perspectives.definitions` and the `pimcore_perspectives` settings-store scope.
> - Already covered elsewhere and not repeated: C-20 (translation edit languages `[]` treated as "unrestricted"),
>   C-37 (scheduled tasks of deleted users), B-114 (`authenticateSession()` from the model, `setLastLoginDate()` side
>   effect), B-193 (notifications of deleted users), CoreBundle B-19 (`ResetPasswordCommand`: non-interactive `TypeError`,
>   no password policy).

---

## [ ] C-40 – `UserRole::getById()` / `AbstractUser::getById()` cache a `User` object for role ids: afterwards `Role::getById()` returns `null`, permission checks crash and roles silently disappear

**Files:** `models/User/AbstractUser.php:44-82`, `:288-296`; `models/User.php:276`, `:288`, `:301`, `:535`, `:592`,
`:633`; `models/Element/PermissionChecker.php:203-204`; `lib/Security/User/User.php:63-67`;
`models/Notification/Service/NotificationService.php:98-102`; `config/dao-classmap.php:155`

```php
$reflectionClass = new ReflectionClass(static::class);
if ($reflectionClass->isAbstract()) {           // AbstractUser, UserRole
    $user = new Model\User();                   // always a User, whatever the row is
    $user->setType('');
}
$user->getDao()->getById($id);                  // no type filter → loads a role / folder row into a User
$className = Service::getClassNameForType($user->getType());
if (get_class($user) !== $className && is_a($className, Model\Element\ElementInterface::class, true)) {
    $user = $className::getById($user->getId());   // never runs: no user class is an ElementInterface
}
RuntimeCache::set($cacheKey, $user);            // 'user_<id>' now holds a User with type 'role'
```

- `UserRole` is abstract. `User::getMergedPerspectives()` and both `getMergedWebsiteTranslationLanguages*()` call
  `User\UserRole::getById($roleId)` (`User.php:535`, `:592`, `:633`). For a role id this builds a `Model\User`, fills
  it with the role row (type `role`, workspaces loaded) and caches it under `user_<roleId>`. The `is_a(…,
  ElementInterface)` guard was meant to re-load the right class, but `Role` / `Folder` are not elements, so it is
  dead code.
- Every later `Role::getById($roleId)` in the same request / process gets the cached `User`. `typeMatch()` rejects it
  (`User` is not `instanceof Role`) and returns `null`:
  - `User::isAllowed($key)` for a non-admin who lacks `$key` directly → `$role->getPermission()` on `null` → `Error`
    (`User.php:276`); the same for `class` / `docType` checks (`:288`, `:301`). Example: the admin UI resolves the
    start perspective (`getFirstAllowedPerspective()`) and then checks a permission in the same request → 500.
  - `PermissionChecker::getUserPermissions()` (`PermissionChecker.php:203-204`) → `Error`.
  - `Security\User\User::getRoles()` (`User.php:63-67`) silently skips the role: the `ROLE_<NAME>` Symfony roles of the
    user are missing, so `access_control` / `isGranted('ROLE_…')` deny access without an error.
  - `NotificationService::sendToGroup()` throws "No group found with the ID …" for an existing role.
- The outcome depends on call order: if `Role::getById()` ran first, the cache holds a `Role` and everything works.
  Worker runtimes and Messenger consumers keep the poisoned entry until the runtime cache is cleared, so the failure
  shows up in unrelated later requests.
- The same path returns a `User` object for **folder** ids (`AbstractUser::getById($userFolderId)`), and
  `User::getById($folderId)` then returns that cached object as a "user" with type `userfolder` (it passes
  `typeMatch()`), while a fresh `User::getById($folderId)` returns `null`.
- A deleted or stale role id makes the same call sites fail with `null` independent of the cache (B-226).

**Fix direction:** resolve the class from the row before instantiating (one `SELECT`, then `Service::getClassNameForType()`
→ `new $className()` → hydrate), and cache only the correctly typed object. Replace `UserRole::getById()` in `User` with
`Role::getById()`. Make all role lookups null-safe (B-226). Add a regression test that calls `UserRole::getById()` and
then `Role::getById()` for the same id (I-121). Related: I-115.

---

## [ ] C-41 – Deactivating a user, changing their password or roles does not end their admin sessions: `isEqualTo()` compares only the id

**Files:** `lib/Security/User/User.php:53-70`, `:84-87`; `lib/Security/User/UserProvider.php:37-48`;
`lib/Security/User/UserChecker.php:31-60`; `lib/Tool/Authentication.php:36-68`, `:181-184`

```php
public function isEqualTo(UserInterface $user): bool
{
    return $user instanceof self && $user->getId() === $this->getId();
}
```

- Symfony's `ContextListener::hasUserChanged()` delegates **completely** to `EquatableInterface::isEqualTo()` when the
  user implements it. The built-in checks (password hash changed, roles changed, identifier changed) are skipped.
- `UserChecker` (which calls `Authentication::isValidUser()`: `active`, password set) runs only on login, not on
  session refresh. `refreshUser()` reloads the model but nobody checks it again.
- Result: an admin who deactivates a compromised account, resets its password or removes its roles / admin flag does
  **not** log out the existing sessions. They keep working until the session expires. The `ROLE_*` names stored in the
  token are also never refreshed, so role-based `access_control` keeps the old roles.
- Only `Authentication::authenticateSession()` (frontend requests by an admin) checks `isValidUser()` after
  refreshing (`:58-63`). The admin firewall itself (in admin-ui-classic-bundle, not verified locally) relies on the
  Symfony listener.
- A **deleted** user is not handled either: `refreshUser()` passes the `null` from `PimcoreUser::getById()` to
  `buildUser(PimcoreUser)` → `TypeError` instead of `UserNotFoundException`. Symfony would log out on
  `UserNotFoundException`; with the `TypeError` every request of that session ends in a 500.

**Fix direction:** in `refreshUser()` throw `UserNotFoundException` for a missing user and for `!isValidUser()`. In
`isEqualTo()` also compare the password hash, `isActive()`, `isAdmin()` and the role ids (or drop `EquatableInterface`
and let Symfony compare password and roles; Symfony 7.3+ compares a hash of the password via `__serialize()`, I-117).
Add a functional test: log in, deactivate the user, next request is unauthenticated (I-121).

---

# Scope 11 – Tool (SettingsStore / TmpStore / Email log / Email blocklist)

> Notes
> - `SettingsStore.php`, `TmpStore.php`, `Email/Log.php`, `Email/Blocklist.php` and both `Listing.php` files are
>   `strict_types`; all six DAOs are not (`SettingsStore/Dao.php`, `TmpStore/Dao.php`, `Email/Log/Dao.php`,
>   `Email/Log/Listing/Dao.php`, `Email/Blocklist/Dao.php`, `Email/Blocklist/Listing/Dao.php`) (I-122).
> - No hard Symfony 7.4 / PHP 8.4 incompatibility was found (typed class constants `public const string` are already
>   used in `SettingsStore`). The remaining modernisation is in TODO-3 (I-122–I-128).
> - Hydration skips `NULL` columns (`AbstractModel::setValues($data, true)`), and `getObjectVars()` is
>   `get_object_vars()` (uninitialised typed properties are left out on save). Both matter for B-240 / B-242.
> - The admin email-log / blocklist UI lives in `pimcore/admin-ui-classic-bundle` (not in this repo); statements about
>   it are **not verified locally**. `vendor/` is not installed; nothing was executed.
> - Already covered elsewhere and not repeated: C-11 (video job TmpStore entry not released), CoreBundle C-12 (Twig
>   sandbox for mail rendering, `lib/Mail.php:593-624`), CoreBundle B-18 (`EmailLogsCleanupCommand` option parsing),
>   SeoBundle B-27 (`robots.txt` SettingsStore keys).

---

## [ ] C-42 – The email blocklist is never applied: blocklisted recipients still receive every email

**Files:** `lib/Mail.php:462-487`, `:545-562`, `:576-588`; `models/Tool/Email/Blocklist.php:38-48`;
`models/Tool/Email/Blocklist/Dao.php:33-41`

```php
foreach ($addresses as $addrKey => $address) {
    if ($address instanceof Address) {
        if (Model\Tool\Email\Blocklist::getByAddress($address->getAddress())) {
            unset($addrKey);                       // unsets the loop variable, not $addresses[$addrKey]
        }
    } else {
        if (Model\Tool\Email\Blocklist::getByAddress($addrKey)) {   // looks up the array key, not the address
            unset($addresses[$addrKey]);
        }
    }
}
```

- `sendWithoutRendering()` (also used by `send()`) filters `To`, `Cc`, `Bcc` and `ReplyTo` through
  `filterLogAddresses()` and writes the result back into the headers. Symfony's `Email::getTo()` / `getCc()` /
  `getBcc()` always return `Address[]`, so only the first branch runs, and `unset($addrKey)` removes nothing. Every
  blocklisted address keeps getting mail, and the email log records it as a normal recipient.
- The blocklist is the only opt-out mechanism Pimcore core offers for document emails / newsletters sent through
  `Pimcore\Mail` (e.g. hard bounces or unsubscribes added by custom code or the admin UI). Mail to opted-out recipients
  is a GDPR / CAN-SPAM problem and hurts sender reputation (repeated hard bounces).
- The second branch is wrong too: for string entries it looks up the numeric array key (`'0'`, `'1'`), never the
  address, so it can only remove an entry by accident.
- Nothing in the repo tests the blocklist.
- After the fix, a message whose recipients are **all** blocklisted ends with empty address headers; Symfony Mailer
  then throws ("An email must have a "To", "Cc", or "Bcc" header"), which `sendWithoutRendering()` reports as a send
  failure. That case needs an explicit "skipped" result instead.

**Fix direction:** `unset($addresses[$addrKey])` in the `Address` branch and look up `$address` (the value) in the string
branch; `array_values()` the result; normalise addresses (trim + lower-case) on blocklist write and lookup (B-244); skip
sending (and log as "suppressed") when no recipient is left; add a test (I-128).

---

# Scope 12 – DataObject root files (not covered by Scope 6)

> Notes
> - Scanned: `models/DataObject/Classificationstore.php`, `DefinitionModifier.php`, `Folder.php`, `Listing.php`,
>   `ClassDefinitionInterface.php`, `ImportDataServiceInterface.php`, `LazyLoadedFieldsInterface.php`,
>   `ObjectAwareFieldInterface.php`, `OwnerAwareFieldInterface.php`, `PreGetValueHookInterface.php`,
>   `SelectOptionsInterface.php`. Skipped because Scope 6 already processed them: `AbstractObject.php`, `Concrete.php`,
>   `ClassDefinition.php`, `Localizedfield.php`, `Fieldcollection.php`, `Objectbrick.php`, `Service.php`.
>   `Listing.php` and `Folder.php` were only mentioned in passing before (I-76 operator allowlist, B-150 return type);
>   those points are not repeated.
> - Collaborators read where a defect originates in the interplay: `Classificationstore/Dao.php` (`save()` `:52-160`,
>   `load()` `:200-272`), `ClassDefinition/Data/Classificationstore.php::getDataFromEditmode()` (`:300-350`),
>   `ClassDefinition/Data/Password.php`, `Service.php::getCalculatedFieldValueForEditMode()` / `getCalculatedFieldValue()`
>   (`:1458-1530`), `lib/Model/Listing/Dao/QueryBuilderHelperTrait.php::applyConditionsToQueryBuilder()` (`:52-81`),
>   `ClassDefinition.php::setLayoutDefinitions()` (`:708-760`).
> - All eleven files are `strict_types`. No hard Symfony 7.4 / PHP 8.4 incompatibility was found (no implicit nullable
>   parameter, typed class constant `public const string` already used in `LazyLoadedFieldsInterface`). Modernisation
>   is in TODO-3 (I-129–I-134).
> - The classic admin UI (`pimcore/admin-ui-classic-bundle`) is not in this repo; statements about what it sends are
>   **not verified locally**. Nothing was executed.

---

## [ ] C-43 – Classification store `password` keys are saved in plain text unless the key is the first changed value of a clean, loaded store

**Files:** `models/DataObject/Classificationstore.php:178-206` (`:183`, `:185`, `:194-195`), `:216-217`;
`models/DataObject/Classificationstore/Dao.php:126-147` (save), `:267` (load); `models/DataObject/ClassDefinition/Data/Password.php:135-160`;
`models/DataObject/Traits/SimpleNormalizerTrait.php:25-28`; `models/DataObject/ClassDefinition/Data/Classificationstore.php:300-350`

```php
$this->setActiveGroups($this->activeGroups + [$groupId => true]);    // can mark the store dirty first
if (!$this->isFieldDirty('_self')) {
    if ($this->object) {
        …
        $newData = $dataDefinition->getDataForResource($value, $this->object, ['owner' => $this]);
        if ($dataDefinition instanceof Password) { $value = $newData; }   // the only place a password is hashed
        …
    }
}
$this->items[$groupId][$keyId][$language] = $value;                   // plain text in every other case
```

- Hashing is a side effect of the dirty check. The dirty check is skipped as soon as the store is dirty, and when it
  has no object. The DAO never hashes: `Password` uses `SimpleNormalizerTrait::normalize()` (returns the value
  unchanged), and there is no `classificationstore_password` marshaller, so the value is written as-is to
  `object_classificationstore_data_<classId>.value`.
- A password is stored **in plain text** when:
  - another key was changed earlier in the same request (admin save: `getDataFromEditmode()` calls
    `setLocalizedKeyValue()` for every submitted key, `:322-330`);
  - its group is activated in the same call (`setActiveGroups()` at `:183` runs before the check and marks the store
    dirty), i.e. the normal "add group, fill password" flow;
  - the object is new or the store was created with `new Classificationstore()` (no object → no hashing);
  - API / import code calls `setLocalizedKeyValue()` for two password keys (only the first is hashed).
- The plain text also goes into versions, the recycle bin and the cache, and `getDataForEditmode()` returns it to the
  admin UI (`Password.php:290-293`). Later it gets hashed only by accident (a later save where that key happens to be the
  first one compared), so DB contents are inconsistent: some rows hashed, some plain.
- `Password` is a valid key type: `Classificationstore\Service::getFieldDefinitionFromKeyConfig()` builds any registered
  data type from the key's `type`. Whether the classic admin offers `password` in the key editor was not verified
  locally; API- and import-created keys can use it either way.

**Fix direction:** hash in the persistence path, independently of dirty detection: in `setLocalizedKeyValue()` always
run `Password::getDataForResource()` for password keys (it already skips existing hashes), or add a
`classificationstore_password` marshaller / `normalize()` that hashes. Add a migration that hashes the existing
plain-text rows (`type = 'password'` and `password_get_info(value)['algo'] === null`), and a test (I-134).

---

# Scope 13 – DataObject `AbstractObject/**`, `Concrete/**`, `Folder/**`

> Notes
> - `AbstractObject/Dao.php`, `Concrete/Dao.php` and `Concrete/Dao/InheritanceHelper.php` were processed in Scope 6
>   (C-25, C-26, B-175, B-177, B-186, B-187, I-73, I-76, I-78) and are skipped.
> - `Folder/Dao.php` is an empty subclass of `AbstractObject\Dao` with no behaviour of its own: no critical item. Its
>   improvement is I-135.

---

# Scope 14 – DataObject `Listing/**`, `Fieldcollection/**`, `Localizedfield/**`

> Notes
> - Scanned: `Listing/Dao.php`, `Fieldcollection/Definition.php`, `Fieldcollection/Definition/Dao.php`,
>   `Fieldcollection/Definition/Listing.php`, `Fieldcollection/Data/AbstractData.php`, `Fieldcollection/Data/Dao.php`,
>   `Localizedfield/LanguageTableDoesNotExistException.php`.
> - Skipped because Scope 6 already processed them: `Listing/Concrete.php`, `Listing/Concrete/Dao.php` (B-178),
>   `Fieldcollection/Dao.php` (B-180, I-76), `Localizedfield/Dao.php` (B-175, B-179, I-76, I-82, I-83) and the root file
>   `Localizedfield.php` (B-175, B-176, B-187). Points made in passing before (`declare(strict_types=1)` in the DAOs –
>   I-73; `Listing\Dao::getCount()` – I-79; `@unlink()` in `Fieldcollection\Definition::delete()` and DDL quoting – I-83;
>   key regex – C-32; `QueryBuilderHelperTrait` DBAL 4 blockers – I-58; object-type condition – B-250) are not repeated.
> - Collaborators read: `lib/DataObject/ClassBuilder/FieldCollectionClassBuilder.php`, `Objectbrick/Definition.php` and
>   `Objectbrick/Definition/Dao.php` (for comparison), `ClassDefinition/Data/Fieldcollections.php`, `Fieldcollection.php`,
>   `Fieldcollection/Dao.php`, `Localizedfield/Dao.php`, `ClassDefinition/Helper/Dao.php`, `lib/File.php::putPhpFile()`,
>   `lib/HttpKernel/CacheWarmer/PimcoreCoreCacheWarmer.php`, `ClassesRebuildCommand`, `ClassesDefinitionsBuildCommand`.
> - `doctrine/dbal` is `^3.8`, PHP `~8.4.0`. No hard Symfony 7.4 / PHP 8.4 incompatibility was found in the seven
>   files (no implicit nullable parameter, `protected const array` already typed). `vendor/` is not installed and the
>   classic admin UI bundle is not in this repo: statements about what they do are marked **not verified locally**.
>   The PHP redeclaration fatal in C-44 was reproduced with plain `php -r`. Nothing else was executed.

---

## [ ] C-44 – A field-collection field named `objectId` or `loadedLazyKeys` generates a class that is a PHP compile-time fatal: every object using the collection crashes

**Files:** `lib/DataObject/ClassBuilder/FieldCollectionClassBuilder.php:67-71`;
`models/DataObject/Fieldcollection/Data/AbstractData.php:41`; `models/DataObject/Traits/LazyLoadedRelationTrait.php:27`;
`models/DataObject/Fieldcollection/Definition.php:149-158`; `models/DataObject/ClassDefinition/Data.php:85-97`, `:1297-1312`

```php
// AbstractData (base of every generated collection class)
protected ?int $objectId = null;          // + LazyLoadedRelationTrait: protected array $loadedLazyKeys = [];
// generated class for a collection with a field "objectId"
protected $objectId;                      // PHP Fatal error: Type of …\Data\X::$objectId must be ?int (as in class AbstractData)
```

- The builder declares one **untyped** `protected $<field>;` per field. PHP refuses to redeclare an inherited typed
  property without the same type, at compile time (reproduced: `php -r 'class A { protected ?int $objectId = null; }
  class B extends A { protected $objectId; }'` → fatal).
- `Definition::save()` checks only `Data::isForbiddenName()`. Its list covers `object`, `index`, `fieldname`, `type`,
  `dirtyfields`, but not `objectid` or `loadedlazykeys`, which are the remaining typed properties of `AbstractData`.
  `objectId` is a very plausible field name (an external / legacy object id).
- The save succeeds and the class file is written. After that, autoloading `Fieldcollection\Data\<Key>` is an
  uncatchable fatal:
  - `Fieldcollection\Dao::load()` builds an item for every stored row, so every object with an item of that type fatals
    in frontend, admin, API and workers;
  - `ClassesRebuildCommand`, `ClassesDefinitionsBuildCommand` and any `class_exists()` over all collections fatal.
    `PimcoreCoreCacheWarmer::dataObjectClasses()` also puts the class on the preload list. Whether `cache:warmup`
    autoloads it was **not verified locally** (no `vendor/`).
- A custom `parentClass` can add more typed properties that collide the same way. Nothing checks for that either.
- Recovery means editing the definition file by hand: the admin cannot load the collection, and objects cannot be
  opened.

**Fix direction:** in `Definition::save()`, reject field names that match a property or method of the base class
(`ReflectionClass` of `AbstractData`, or of the configured `parentClass`), compared case-insensitively. At minimum,
add `objectid` and `loadedlazykeys` to a collection-specific forbidden list. Make the generated properties typed or
`mixed`, so that a collision is reported as a definition error and not as a fatal at runtime. Bricks
(`Objectbrick\Data\AbstractData`) use the same builder pattern and need the same check. Add a test (I-141).

---

## [ ] C-45 – `classes-rebuild` drops field-collection columns (data loss) when a definition exists in both definition directories

**Files:** `models/DataObject/Fieldcollection/Definition/Listing.php:27-37`, `:57-77`;
`models/DataObject/Traits/LocateFileTrait.php:25-34`; `bundles/CoreBundle/src/Command/ClassesRebuildCommand.php:155-164`;
`models/DataObject/Fieldcollection/Definition.php:160-175`; `models/DataObject/Fieldcollection/Definition/Dao.php:49-104`
(`:102-103`); `models/DataObject/ClassDefinition/Helper/Dao.php:117-131`

- `getByKey()` resolves a key with `locateDefinitionFile()`: the file in `PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY`
  (`config/pimcore/classes`) wins over `PIMCORE_CLASS_DEFINITION_DIRECTORY` (`var/classes`). `Listing::loadFileNames()`
  instead returns **every** `*.php` from both folders, and `array_unique()` removes only identical real paths. A key
  that exists in both folders is returned twice, the `var/classes` copy first.
- `ClassesRebuildCommand` (`pimcore:deployment:classes-rebuild`, usually run on every deployment) calls
  `$fc->save(false)` for each listed definition. Saving the shadowed `var/classes` copy runs `createUpdateTable()` for
  every class that allows the key. `removeUnusedColumns()` then **drops every column** that the stale copy does not
  know about (`ALTER TABLE … DROP COLUMN`). The custom definition is saved afterwards and adds the columns back, but
  empty.
- Typical trigger: a project moves its definitions from `var/classes` to `config/pimcore/classes` (to version them)
  and leaves the old files behind, or a writable `var/classes` copy is created next to the committed one. The admin
  and `getByKey()` show the custom definition, so the shadow copy goes unnoticed until data is gone.
- The same listing feeds `ClassesDefinitionsBuildCommand` (two PHP class dumps per key; the last one wins) and the
  migrations `Version20211018104331`, `Version20231127124738`, `Version20240708083500`.

**Fix direction:** make the listing resolve keys the same way as `getByKey()`. Collect the keys from both folders and
load each one once through `getByKey()`, so the custom file wins and the result shares the runtime cache (I-137). Log
a warning for shadowed files. Check whether `Objectbrick\Definition\Listing` and `ClassDefinition\Listing` behave the
same way. Add a test (I-141).

---

# Scope 15 – DataObject `Classificationstore/**` configuration models

> Notes
> - Scanned: `KeyConfig.php`, `KeyConfig/Dao.php`, `KeyConfig/Listing.php`, `KeyConfig/Listing/Dao.php`, `GroupConfig.php`,
>   `GroupConfig/Dao.php`, `GroupConfig/Listing.php`, `GroupConfig/Listing/Dao.php`, `CollectionConfig.php`,
>   `CollectionConfig/Dao.php`, `CollectionConfig/Listing.php`, `CollectionConfig/Listing/Dao.php`, `StoreConfig.php`,
>   `StoreConfig/Dao.php`, `StoreConfig/Listing.php`, `StoreConfig/Listing/Dao.php`, `KeyGroupRelation.php`,
>   `KeyGroupRelation/Dao.php`, `KeyGroupRelation/Listing.php`, `KeyGroupRelation/Listing/Dao.php`,
>   `CollectionGroupRelation.php`, `CollectionGroupRelation/Dao.php`, `CollectionGroupRelation/Listing.php`,
>   `CollectionGroupRelation/Listing/Dao.php`, `Group.php`, `Key.php`.
> - Skipped because earlier scopes already processed them: `Classificationstore/Dao.php` (B-181, C-43, B-248, I-76,
>   I-82), `Classificationstore/DefinitionCache.php` (B-181, B-249) and
>   `Classificationstore/Service.php` (C-43, B-249). Their points are not repeated.
> - Collaborators read: `lib/Cache/RuntimeCache.php` (`isRegistered()` is `ArrayObject::offsetExists()`, which is
>   `true` for a `null` value – checked with plain `php -r`), `lib/Model/AbstractModel.php::setValue()` / `setValues()`,
>   `lib/Model/Dao/DaoTrait.php::assignVariablesToModel()` (skips `null` values), `ClassDefinition/Data/Classificationstore.php`
>   (`:180-190`, `:836-915`, `:1066-1090`), `DeleteClassificationStoreCommand`, the `classificationstore_*` schema and
>   `bundles/CoreBundle/config/pimcore/default.yaml:148` (`SET sql_mode = ''`).
> - **No critical item.** The worst defects – a config renamed while its old name is cached (`getByName()` then returns
>   the renamed config, B-258), `CollectionConfig` lookups returning `null` after a save in the same process (B-257) and
>   group relations deleted when a disabled key is saved (B-260) – lead to wrong configuration or duplicates, but need a
>   specific sequence and are recoverable. They are in TODO-2 (B-257–B-265).
> - 13 of the 26 files (all DAOs) have no `declare(strict_types=1)` – already covered by I-73. No hard Symfony 7.4 /
>   PHP 8.4 incompatibility was found (no implicit nullable parameter; `public const string` is already used for the
>   table names). Modernisation is in TODO-3 (I-142–I-148). The classic admin UI bundle is not in this repo: what it
>   sends or reads is **not verified locally**. Nothing was executed apart from the `ArrayObject` check.

---

# Scope 16 – DataObject `ClassDefinition/**` (CustomLayout, Service, Helper, Layout, DynamicOptionsProvider, Data/**)

> Notes
> - Scanned (114 files, read in full): `CustomLayout.php`, `CustomLayout/Dao.php`, `CustomLayout/Listing.php`,
>   `CustomLayout/Listing/Dao.php`, `Service.php`, the root interfaces (`CalculatorClassInterface`,
>   `DefaultValueGeneratorInterface`, `LinkGeneratorInterface`, `PathFormatterAwareInterface`, `PathFormatterInterface`,
>   `PreviewGeneratorInterface`), `Helper/*` (except `Helper/Dao.php`), `DynamicOptionsProvider/*`, `Layout/*` subclasses
>   and `Layout/Traits/*`, and in `Data/`: all relation types (`AdvancedManyToMany(Object)Relation`,
>   `ManyToMany(Object)Relation`, `ManyToOneRelation`, `ReverseObjectRelation`, `Relations/*Trait`, `Extension/Relation`,
>   `Extension/RelationFilterConditionParser`, `Extension/PositionSortTrait`), the numeric types (`AbstractQuantityValue`,
>   `QuantityValue`, `QuantityValueRange`, `InputQuantityValue`, `Numeric`, `NumericRange`, `Slider`, `RgbaColor`,
>   `Checkbox`, `BooleanSelect`), the select/text/date types (`Select`, `Multiselect`, `Country`, `Countrymultiselect`,
>   `Language`, `Languagemultiselect`, `Gender`, `Firstname`, `Lastname`, `Email`, `User`, `Textarea`, `Wysiwyg`,
>   `Extension/Text`, `OptionsProviderTrait`, `OptionsProviderInterface`, `Datetime`, `DateRange`, `Time`), the media/geo
>   types (`Image`, `ImageTrait`, `ImageGallery`, `Hotspotimage`, `ExternalImage`, `Video`, `Link`, `Geo/AbstractGeo`,
>   `Geobounds`, `Geopoint`, `Geopolygon`, `Geopolyline`), `Table`, `StructuredTable` and all `Data/*Interface.php`.
> - Skipped because earlier scopes already process or name them: `ClassDefinition/Dao.php`, `Data.php`, `Layout.php`,
>   `Listing.php`, `Listing/Dao.php` (C-45/I-141 note), `Helper/Dao.php`, `Data/Block.php`, `CalculatedValue.php`,
>   `Classificationstore.php`, `ClassSavedInterface.php`, `Consent.php`, `Date.php`, `EncryptedField.php`,
>   `Fieldcollections.php`, `Input.php`, `Localizedfields.php`, `Password.php`, `UrlSlug.php`, `Objectbricks.php` (B-175)
>   and `Relations/AbstractRelations.php` (I-82). Files that are only named in the cross-cutting list of I-75 (Table,
>   Geo*, Link, Video, ImageGallery, Hotspotimage) were scanned; the `unserialize()` point is not repeated.
> - Stack: `composer.json` requires PHP `~8.4.0`, Symfony `^7.4`, `doctrine/dbal ^3.8`. There is no `vendor/` /
>   `composer.lock` in the checkout, so Carbon / DBAL behaviour could not be executed; plain PHP behaviour was checked
>   with `php -r` (PHP 8.4). The classic admin UI (ExtJS) and the object-merger bundle are not in this repo: how they
>   render preview HTML or which values they post is **not verified locally**.
> - Only two files lack `declare(strict_types=1)`: `CustomLayout/Dao.php`, `CustomLayout/Listing/Dao.php` (I-73).
> - Critical items: C-46–C-51. Bugs: B-266–B-299. Improvements: I-149–I-160.

## [ ] C-46 – `ClassDefinition::getLinkGenerator()` throws `TypeError` for every class without a link generator

**Files:** `models/DataObject/ClassDefinition/Helper/LinkGeneratorResolver.php:27`; `PreviewGeneratorResolver.php:27`;
`DefaultValueGeneratorResolver.php:27`; caller `models/DataObject/ClassDefinition.php:1048-1054` (`:167`);
`models/DataObject/Data/Link.php:353`; `models/Document/Link.php:130`; `models/Document/Editable/Link.php:210`

- `resolveGenerator(string $generatorClass)` does not accept `null`, but `ClassDefinition::$linkGeneratorReference`
  defaults to `null` and `getLinkGenerator()` passes it without a guard (`ClassDefinition.php:1051`). Passing `null` to a
  non-nullable `string` parameter is a `TypeError` in strict and coercive mode alike.
- Common path: `DataObject\Data\Link::getHref()`, `Document\Link` and `Document\Editable\Link` call `getLinkGenerator()`
  for every internal link to an object. Rendering a link to an object whose class has no link generator fails fatally.
- The base `ClassResolver::resolve()` already takes `?string` and returns `null` for an empty value
  (`Helper/ClassResolver.php:32`, `:37-39`); only the subclass signatures are narrower. `getPreviewGenerator()` guards
  the call (`ClassDefinition.php:1072`), `getLinkGenerator()` does not.

**Fix direction:** make `resolveGenerator(?string)` nullable in all generator resolvers (as the base class) and/or guard
the call in `getLinkGenerator()`. Add a test that renders a link to an object of a class without a link generator.

## [ ] C-47 – Stored XSS in the core version preview: StructuredTable cells, advanced-relation metadata, ExternalImage URL, Link

**Files:** `models/DataObject/ClassDefinition/Data/StructuredTable.php:261-268`, `:384-394`;
`models/DataObject/Data/StructuredTable.php:137-162`; `Data/AdvancedManyToManyObjectRelation.php:324-341`, `:876`;
`Data/AdvancedManyToManyRelation.php:424-441`, `:867`; `Data/ExternalImage.php:162-169`, `:190-197`, `:213-218`;
`Data/Link.php:160-194`; `models/DataObject/Data/Link.php:346-371`, `:402-423`

- `StructuredTable::getVersionPreview()` returns `Data\StructuredTable::getHtmlTable()`, which concatenates row/column
  labels and every cell (`"<td>$c</td>"`) without `htmlspecialchars()`; `getDiffDataForEditMode()` sends the same string
  as `type = html`. An editor types `<img src=x onerror=…>` into a text cell.
- `AdvancedManyToMany(Object)Relation::getVersionPreview()` builds `<span class="preview-metadata">[key: value | …]</span>`
  from free-text metadata column values without escaping; `processDiffDataForEditMode()` sends it as `value.html`.
- `ExternalImage::getVersionPreview()` puts the raw URL into `src="…"`, `href="…"` and the link text (and closes the
  link with `</>`); `getDataFromEditmode()` / `checkValidity()` accept any string (`x" onerror="…`, `javascript:…`).
- `Link::getVersionPreview()` returns `Data\Link::getHtml()`: `href` (direct, parameters, anchor), `rel`, `title`,
  `target`, `class` are unescaped and `attributes` is appended verbatim; `Link::checkValidity()` validates none of them.
- Element paths are safe (`Element\Service::getValidKey()` strips `<>`); sibling types escape (`Select.php:182`,
  `Table.php:410`, `Extension/Text.php:47`). Any user with object save permission can plant script that runs for every
  admin who opens the Versions tab / compare view (rendering via `innerHTML` **not verified locally**, but the payload
  is explicitly typed as HTML).
- Diff-only previews used by the object-merger bundle are in B-271.

**Fix direction:** escape every interpolated value with `htmlspecialchars(…, ENT_QUOTES)` in these previews (or make
`getHtmlTable()` / `Data\Link::getHtml()` escape), and validate URLs (`http(s)`/relative only) and `attributes` in
`checkValidity()` of `ExternalImage` and `Link`.

## [ ] C-48 – `QuantityValueRange::isEmpty()` / `isEqual()` materialise `range(min, max)` on every save: memory fatal or `ValueError`

**Files:** `models/DataObject/ClassDefinition/Data/QuantityValueRange.php:353-369`, `:391-398`;
`models/DataObject/Data/QuantityValueRange.php:79-82` (`getValue()` → `getRange()`); `models/DataObject/Concrete/Dao.php:294`;
`models/DataObject/Localizedfield.php:575`

- Both methods call `$data->getValue()`, i.e. `range($min, $max, 1)`. `Concrete\Dao` calls `isEmpty()` for every
  query-persisted field on every save (`Concrete/Dao.php:294`), independent of dirty detection.
- A valid range `0 … 1e9` (e.g. millimetres) tries to allocate ~17 GB → memory fatal; `0 … 1e20` throws
  `ValueError: The supplied range exceeds the maximum array size` (both checked with `php -r`). The object can no longer
  be saved.
- The comparison is also wrong: `range(0.5, 1.7, 1) === range(0.5, 1.6, 1)`; `[null, 5]` and `[null, 7]` both give
  `[0]`, so changes count as equal and are not saved in localized fields (`Localizedfield/Dao.php:142-148`).
  `isEmpty()` can never be true for an instance (`range()` always returns ≥ 1 element).

**Fix direction:** compare / test emptiness on `getMinimum()`, `getMaximum()` and `getUnitId()`; never call
`getValue()` in persistence paths.

## [ ] C-49 – Range values with an open bound (or without unit) are stored but load as `null` and are wiped on the next save

**Files:** `models/DataObject/ClassDefinition/Data/NumericRange.php:243`, `:299-300`, `:397`, `:439`;
`QuantityValueRange.php:137`, `:190-199`, `:309-320`; `DateRange.php:54-97` (`:65`, `:84`, `:89`), `:134-144`

- `NumericRange::getDataFromResource()` hydrates only when both `__minimum` and `__maximum` are non-null (`isset`,
  `:243`), while `getDataFromEditmode()` accepts a single bound. `[null, 5]` passes `checkValidity()` (`null > 5` is
  false), is persisted with `__minimum = NULL`, loads as `null` and the next save writes `NULL, NULL`. `[5, null]` is
  persisted for unpublished objects (all checks are skipped with `$omitMandatoryCheck`, see B-272) and lost the same way.
- `QuantityValueRange::getDataFromResource()` additionally requires `__unit`; a non-mandatory range without unit
  (`unit = -1` / empty → `null`, `:191-193`) is saved and disappears on load.
- `DateRange::getDataForResource()` explicitly writes an open end (`end_date => null`, `:65`), but
  `getDataFromResource()` requires both columns (`isset(…, …)`, `:84`) → an open-ended `CarbonPeriod` is lost on reload;
  the "no end" branch `:89` is dead (`getDateFromTimestamp()` always returns `Carbon`).
- `Data\NumericRange::__toString()` renders `-∞` / `+∞`, i.e. open bounds are an intended state.

**Fix direction:** hydrate when any bound column is non-null (as `QuantityValue.php:239` does) and decide explicitly
whether open bounds / a missing unit are valid; reject them in `checkValidity()` otherwise. Add round-trip tests.

## [ ] C-50 – Deleting a unit makes objects with `QuantityValueRange` / `InputQuantityValue` fields unloadable; `QuantityValue` silently drops the value

**Files:** `models/DataObject/ClassDefinition/Data/QuantityValueRange.php:137-142`, `:190-193`; `InputQuantityValue.php:52-53`,
`:125-128`; `QuantityValue.php:246-258`; `models/DataObject/Data/AbstractQuantityValue.php:42-55`;
`models/DataObject/QuantityValue/Unit/Dao.php:117-120`

- The `Data\AbstractQuantityValue` constructor throws `NotFoundException` when `Unit::getById()` returns `null`.
  `Unit\Dao::delete()` deletes the unit without a usage check; object columns keep the id (`varchar`, no foreign key).
- `QuantityValueRange` / `InputQuantityValue::getDataFromResource()` construct the value without `try/catch` → every
  object that references the deleted unit throws on load (cannot be opened, saved or listed with that field).
- `QuantityValue::getDataFromResource()` catches the exception and returns `null` → the next save writes `NULL` to both
  `__value` and `__unit`; the number is silently lost.
- Also reachable from editmode: `QuantityValueRange::getDataFromEditmode()` compares `=== -1`, so the string `"-1"` is
  looked up as a unit id.

**Fix direction:** handle a missing unit consistently in all three types (keep the value and the raw unit id, log),
and refuse or cascade unit deletion while the unit is referenced.

## [ ] C-51 – `Table` with column config stores keyed rows by position: values swap or shift on reload

**Files:** `models/DataObject/ClassDefinition/Data/Table.php:182-194`, `:201-214`, `:222-254` (`:239-251`)

- With `columnConfigActivated`, `getDataFromResource()` returns rows keyed by `columnConfig[$i]['key']`, so API users
  write keyed rows back (`setTbl([['price' => 1, 'name' => 'x']])`).
- `getDataForResource()` → `convertDataToValueArray()` drops the keys and keeps insertion order. On reload position 0
  maps to `columnConfig[0]['key']`: with config `[name, price]` the row reloads as `name = 1, price = 'x'`; a row missing
  one key shifts the following cells into the wrong columns. Silent data corruption.
- Two column configs with the same `key` collapse into one array key on load (`:245`).

**Fix direction:** when column config is active, order and pad cells by the configured keys in `getDataForResource()`;
reject duplicate / empty column keys when the definition is saved. Add a round-trip test with a keyed, reordered row.

---

# Scope 17 – DataObject `Data/**` (field value objects)

> Notes
> - Scanned (22 files, read in full): `AbstractQuantityValue.php`, `QuantityValue.php`, `InputQuantityValue.php`,
>   `QuantityValueRange.php`, `NumericRange.php`, `BlockElement.php`, `CalculatedValue.php`, `Consent.php`, `Link.php`,
>   `Hotspotimage.php`, `ImageGallery.php`, `Video.php`, `ExternalImage.php`, `Geobounds.php`, `GeoCoordinates.php`,
>   `RgbaColor.php`, `StructuredTable.php`, `ElementMetadata.php`, `ObjectMetadata.php`, `AbstractMetadata/Dao.php`,
>   `ElementMetadata/Dao.php`, `ObjectMetadata/Dao.php`.
> - Skipped because earlier scopes already process them: `UrlSlug.php` (C-31) and `EncryptedField.php` (B-184, I-75).
>   The other files were only named in passing before (C-46/C-47 `Link` / `StructuredTable` HTML, C-48/C-50 quantity
>   values, B-284 metadata targets, B-289/B-290 `StructuredTable`, B-291/B-292 `Hotspotimage`, B-295 `Geobounds`,
>   B-298 `QuantityValue::__toString()`, I-73 DAO strictness, I-75 `unserialize()`, I-82 metadata N+1). Those points are
>   not repeated.
> - Collaborators read where a defect originates in the interplay: `ClassDefinition/Data/AdvancedManyToManyObjectRelation.php`
>   / `AdvancedManyToManyRelation.php` (load `:135-170`, editmode `:218-282` / `:365-380`, `setColumns()`),
>   `lib/Model/AbstractModel.php` (`setValue()` / `setValues()` / `setDao()`), `Traits/OwnerAwareFieldTrait.php`,
>   `Traits/ObjectVarTrait.php`, `Localizedfield/Dao.php:142-149`, `Service.php:1440-1600`, `Concrete.php:312-324`,
>   `lib/Cache/Core/CoreCacheHandler.php:486-509`, `Asset/Image/Thumbnail.php:44-49`, `:430-439`,
>   `ClassDefinition/Data/RgbaColor.php`, `Link.php`, `Consent.php`, `ImageGallery.php`.
> - Only the three metadata DAOs lack `declare(strict_types=1)` (already in I-73). No implicit nullable parameter and no
>   hard Symfony 7.4 / PHP 8.4 incompatibility was found; modernisation is in TODO-3 (I-161–I-169).
> - `vendor/` is not installed and the classic admin UI is not in this repo. Plain PHP behaviour (`range()` with `null`,
>   `sprintf('%02x')` with out-of-range ints, typed-property assignment in `__unserialize()`, `NumberFormatter` with `0`)
>   was checked with `php -r` on PHP 8.4.27. Nothing else was executed.
> - Critical items: C-52. Bugs: B-300–B-315. Improvements: I-161–I-169.

## [ ] C-52 – Advanced-relation metadata columns named like a real method bypass `__call`: crashes on save/load, or all metadata of the item silently stored under another field name

**Files:** `models/DataObject/Data/ObjectMetadata.php:78-105` (`__call`), `:123-134`, `:57-70`, `:153-158`, `:186-205`;
`models/DataObject/Data/ElementMetadata.php:67-94`, `:114-125`, `:130-144`, `:186-195`;
`models/DataObject/Data/ObjectMetadata/Dao.php:33-53`, `:60-81`; `ElementMetadata/Dao.php:29-49`, `:51-76`;
`lib/Model/AbstractModel.php:56`, `:163`, `:175`; callers `ClassDefinition/Data/AdvancedManyToManyObjectRelation.php:225-228`,
`:272-281`, `:611-626`; `AdvancedManyToManyRelation.php:314`, `:374-380`, `:677-692`

```php
$getter = 'get' . ucfirst($column);   // Dao::save()       – a real method wins over __call()
$setter = 'set' . ucfirst($row['column']);
$this->model->$setter($row['data']);  // Dao::load(), and the editmode setter loop in the ClassDefinition
```

- Column values are only reachable through `__call()`. PHP calls `__call()` only when **no** method of that name
  exists, so a column key that matches a real method of the item (or of `AbstractModel`) hits that method instead.
  `setColumns()` accepts any `key` (no reserved-name check).
- Crashes (the object cannot be saved from editmode or, once rows exist, cannot be loaded):
  - `value` → `AbstractModel::setValue(string $key, mixed $value)` with one argument → `ArgumentCountError`;
  - `values` / `data` / `columns` / `dao` → `setValues(array)` / `setData(array)` / `setColumns(array)` /
    `setDao(?AbstractDao)` with a string → `TypeError`; on save `getData()` / `getColumns()` / `getDao()` return an
    array / object as the column value (DBAL conversion error);
  - `object` / `element` → `setObject(?Concrete)` / `setElement()` with a string → `TypeError`.
- Silent corruption / data loss:
  - `fieldname`: the editmode setter loop calls the real `setFieldname($value)`, so the **relation field name of the
    item** becomes the user's value. `Dao::save()` then writes every column of that item with `fieldname = <value>`;
    `load()` queries by the real field name and finds nothing → all metadata of the item is gone after the reload, and
    the rows are never deleted (the delete statements filter by the real field name). A missing value (`?? null`) →
    `setFieldname(null)` `TypeError`.
  - `objectId` / `elementId` / `elementType`: the getter returns the target id / type, so that is stored instead of the
    user's value; the editmode setter `setObjectId("text")` throws (`ClassDefinition` files are strict).
- Same class of defect as C-44 (reserved field names); the generated class compiles, so nothing warns at class save.

**Fix direction:** stop dispatching by method name: give both item classes explicit `getColumnValue(string $key)` /
`setColumnValue(string $key, mixed $value)` methods (keep `__call()` as a BC proxy that only accepts configured
columns) and use them in the DAOs and in the `ClassDefinition` getter/setter loops. Until then, reject column keys that
collide with a method of `ObjectMetadata` / `ElementMetadata` (case-insensitive, `get_class_methods()`) in
`setColumns()` / on class save. Add a round-trip test with columns `value`, `data` and `fieldname`.

---

# Scope 18 – DataObject `QuantityValue/**` (units and conversion)

> Notes
> - Scanned (8 files, read in full): `Unit.php`, `Unit/Dao.php`, `Unit/Listing.php`, `Unit/Listing/Dao.php`,
>   `Service.php`, `DefaultConverter.php`, `UnitConversionService.php`, `QuantityValueConverterInterface.php`.
> - None of them was processed before: the folder is listed in the Scope 6 header, but the earlier items only name
>   these files in passing (C-18 translation side effect of `generateDefinitionJson()`, C-50 unit deletion without usage
>   check, B-286 `TypeError` in `DefaultConverter` from filter input, B-306 `?string` abbreviation, I-73 strictness,
>   I-82 cache without tags, I-154 / I-163 value-object side). Those points are not repeated.
> - Collaborators read where a defect originates in the interplay: `Data/AbstractQuantityValue.php:39-113`,
>   `ClassDefinition/Data/AbstractQuantityValue.php:199-277`, `ClassDefinition/Data/QuantityValue.php:234-260`,
>   `ClassDefinition/Helper/UnitConverterResolver.php`, `ClassResolver.php`, `lib/Model/AbstractModel.php:163-190`,
>   `lib/Model/Listing/AbstractListing.php:163-184`, `models/Translation.php:313-343`,
>   `bundles/CoreBundle/src/Command/Definition/Import/QuantityValueCommand.php`, `bundles/CoreBundle/config/services.yaml:108-114`,
>   `install.sql:695-709` (`fk_baseunit … ON DELETE SET NULL`), the conversion chapter of `55_Number_Types.md`.
> - `Unit.php` and `Unit/Dao.php` have no `declare(strict_types=1)` (already in I-73); the other six files are strict.
>   No hard Symfony 7.4 / PHP 8.4 incompatibility was found; modernisation is in TODO-3 (I-170–I-174).
> - The unit admin grid and the export button live in `pimcore/admin-ui-classic-bundle` (not in this repo, `vendor/` not
>   installed); how they create units (preset id or not) and call `generateDefinitionJson()` was **not verified**.
>   The conversion formula, `array_column(null)`, string offsets on strings, `null` / non-numeric arithmetic, division
>   by `0.0` and `ucfirst()` were checked with `php -r` on PHP 8.4.27. Nothing else was executed.
> - Critical items: C-53. Bugs: B-316–B-323. Improvements: I-170–I-174.

## [ ] C-53 – Unit import with `--override` deletes units before re-creating them: derived units outside the file lose their base unit, any failure leaves units missing

**Files:** `models/DataObject/QuantityValue/Service.php:27-64` (`:36-42`, `:56-58`, `:59-61`); `Unit.php:112-119`;
`Unit/Dao.php:117-120`; `bundles/InstallBundle/dump/install.sql:708`; caller
`bundles/CoreBundle/src/Command/Definition/Import/QuantityValueCommand.php:91-103` (`pimcore:definition:import:units -o`)

```php
if ($unit = Unit::getById($unitArray['id'])) {
    if ($override) { $unit->delete(); } else { continue; }   // first loop: delete
}
// …
foreach (array_merge($baseUnits, $units) as $unit) { $unit->save(); }   // second loop: re-insert, no transaction
```

- "Override" is implemented as delete + insert. Every unit of the file that already exists is deleted in the first
  loop; the rows are written again only in the second loop. No transaction, no validation of the file beforehand.
- `fk_baseunit … ON DELETE SET NULL`: deleting an existing **base** unit sets `baseunit = NULL` on every unit that
  references it. Units contained in the file get their base unit back, but every derived unit that is **not** in the
  file permanently becomes a base unit of its own. Typical trigger: importing a partial export (one group, only the
  base units) or an older export. Effects: `convertTo()` throws "must have same base unit", the quantity-value filter
  stops including those units (`ClassDefinition/Data/AbstractQuantityValue.php:240-243`), automatic conversion when an
  editor switches the unit stops. Nothing reports it.
- Any failure in the second loop leaves the deleted units missing: a FK violation for a base-unit chain deeper than
  two levels (B-320), a DB error, or an `Error` from bad data (B-320). The method returns `false` (or crashes), and
  objects that reference those unit ids hit C-50: `QuantityValueRange` / `InputQuantityValue` objects can no longer be
  loaded, `QuantityValue` values are written as `NULL` on the next save. Other processes see the gap even on success:
  every `delete()` clears the unit cache, so requests running during the import load objects while the units are
  missing (same C-50 effects).
- Units that are only updated fire `UNIT_PRE_DELETE` / `UNIT_POST_DELETE` followed by `UNIT_PRE_UPDATE` /
  `UNIT_POST_UPDATE` (B-319), so delete listeners (cleanup, sync to external systems) act on units that still exist.

**Fix direction:** never delete for override: load the existing unit, `setValues()`, `save()` (upsert). Validate the
whole file first (list of objects, `id` present, every `baseunit` resolvable in the file or the DB, no cycles), sort
topologically, run all writes in one transaction and clear the unit cache once at the end. Add a test: override import
of a base unit whose derived units are not in the file keeps their `baseunit`, and a failing import leaves the table
unchanged.

---

# Scope 19 – DataObject `Objectbrick/**` and `SelectOptions/**`

> Notes
> - Scanned (13 files, read in full): `Objectbrick/Definition.php`, `Objectbrick/Dao.php`, `Objectbrick/Definition/Dao.php`,
>   `Objectbrick/Definition/Listing.php`, `Objectbrick/Data/AbstractData.php`, `Objectbrick/Data/Dao.php`,
>   `SelectOptions/Config.php`, `SelectOptions/Config/Dao.php`, `SelectOptions/Config/Listing.php`,
>   `SelectOptions/Config/Listing/Dao.php`, `SelectOptions/Data/SelectOption.php`,
>   `SelectOptions/Traits/EnumGetValuesTrait.php`, `SelectOptions/Traits/EnumTryFromNullableTrait.php`.
> - None of them was processed before. Both folders are listed in the Scope 6 header, but the earlier items only name
>   these files in passing. Those points are not repeated: select-options id validation – C-32; brick fields named
>   `objectId` / `loadedLazyKeys` – C-44; the open question in C-45 about `Objectbrick\Definition\Listing` is answered
>   by C-55; `getByKey()` include path and caching – B-255; `@unlink()` – I-83; missing `declare(strict_types=1)` in
>   `Objectbrick/Dao.php`, `Definition/Dao.php`, `Data/Dao.php` – I-73; brick calculated values via `getCalculatedFieldValue()` – B-247
>   (comparison); the field-collection side of the shared lifecycle – I-136 / B-252–B-254.
> - Collaborators read where a defect originates in the interplay: `models/DataObject/Objectbrick.php`,
>   `Fieldcollection/Definition.php`, `Fieldcollection/Dao.php`, `Traits/FieldcollectionObjectbrickDefinitionTrait.php`,
>   `Traits/LocateFileTrait.php`, `ClassDefinition/Data/Objectbricks.php`, `ClassDefinition/Data/AdvancedManyToManyRelation.php:627-672`,
>   `ClassDefinition/Data/Relations/AbstractRelations.php:302-318`, `ClassDefinition/Helper/Dao.php`,
>   `Concrete/Dao/InheritanceHelper.php`, `Localizedfield/Dao.php:48-80`,
>   `lib/DataObject/ClassBuilder/ObjectBrick*Builder.php`, `PHPObjectBrickContainerClassDumper.php`,
>   `SelectOptionsEnumBuilder.php`, `PHPSelectOptionsEnumDumper.php`, `lib/Model/Dao/PimcoreLocationAwareConfigDao.php`,
>   `lib/Maintenance/Tasks/DataObject/CleanupBrickTablesTaskHelper.php`,
>   `bundles/CoreBundle/src/OptionsProvider/SelectOptionsOptionsProvider.php`, `ClassesRebuildCommand.php:136-178`,
>   `ClassesDefinitionsBuildCommand.php:95-98`, `doc/05_Objects/01_Object_Classes/01_Data_Types/60_Object_Bricks.md`.
> - No hard Symfony 7.4 / PHP 8.4 incompatibility was found (the files already use typed class constants and
>   `new X()->method()` without parentheses). Modernisation is in TODO-3 (I-175–I-182).
> - Checked with `php` 8.4.27: the enum code injection and the parse error of C-56, `Error` on `tryFrom()` for duplicate
>   enum values, `ucfirst(null)` `TypeError` under strict types (B-330), `(bool)'false' === true` (B-330). The admin UI
>   (`pimcore/admin-ui-classic-bundle`) is not in this repo and `vendor/` is not installed: which permission guards the
>   select-options editor and how the editor sends values was **not verified**. Nothing else was executed.
> - Critical items: C-54–C-56. Bugs: B-324–B-333. Improvements: I-175–I-182.

## [ ] C-54 – Brick rows are addressed by object id only: two `Objectbricks` fields that allow the same brick overwrite, move and delete each other's data

**Files:** `models/DataObject/Objectbrick/Data/Dao.php:39-248` (`:62-69` exists check, `:116-120` store write, `:131`
old query row, `:143` parent row), `:250-321` (`:254`, `:259-260`); `models/DataObject/Concrete/Dao/InheritanceHelper.php:71-90`,
`:232-244`, `:315`, `:337` (constructed with `id` as the only key, `Data/Dao.php:47`, `:270`);
`models/DataObject/Objectbrick/Definition/Dao.php:61-82` (primary key `(id, fieldname)`); docs
`60_Object_Bricks.md:49`, `:61` (a brick is assigned per class **and field**)

```php
$existsResult = $this->db->fetchOne('SELECT id FROM ' . $storetable . ' WHERE id = ? LIMIT 1', [$object->getId()]);
// …
$this->db->update($storetable, Helper::quoteDataIdentifiers($this->db, $data), ['id' => $object->getId()]); // $data contains 'fieldname'
// delete():
$this->db->delete($storeTable, ['id' => $object->getId()]);
$this->db->delete($queryTable, ['id' => $object->getId()]);
```

- The brick tables are shared by every `Objectbricks` field of a class (`object_brick_store_<key>_<classId>`, primary key
  `(id, fieldname)`); the definition explicitly supports assigning one brick to several fields
  (`classDefinitions` = list of `classname` + `fieldname`, one container class per field). Loading filters by
  `fieldname` (`Objectbrick/Dao.php:50`), but every write path in `Data\Dao` filters by `id` only.
- Example: class `Product` has fields `specs` and `specsOverride`, both allowing brick `Engine`.
  - The object has `Engine` in `specs`; an editor adds `Engine` to `specsOverride` and saves. The existence check finds
    the `specs` row, so the new brick is written with `UPDATE … SET fieldname = 'specsOverride', … WHERE id = ?`. The
    `specs` row is **moved** to the other field and its values are replaced; the `specs` brick is gone.
  - Both rows exist (both bricks added while the object was new, which uses `INSERT`): the next save runs
    `UPDATE … SET fieldname = 'specs' WHERE id = ?` on both rows → duplicate primary key → the object save fails and
    keeps failing until someone removes a brick by hand.
  - Removing `Engine` from one field deletes the store **and** query rows of both fields.
- The query table is affected too: `oldData` and the parent row used for inheritance (`FOR UPDATE`) are read
  `WHERE id = ?`, i.e. an arbitrary row of either field, so the change detection and the inherited values mix the two
  fields. `InheritanceHelper` updates and deletes children's query rows by `id` only, so a change in one field is
  propagated into the other field's rows of all children.
- Nothing rejects the configuration; the admin allows it and the docs describe per-field assignment.

**Fix direction:** add `fieldname` to every condition (`exists`, `update`, `delete`, the old / parent query rows) and
pass `fieldname` as an additional key column to `InheritanceHelper` (it needs a composite-key mode for brick and
collection tables). Until that ships, reject a brick definition that is assigned to two fields of the same class
(`Definition::save()`), and add a test with two fields sharing a brick (add, update, remove, inherit).

---

## [ ] C-55 – `classes-rebuild` also drops object-brick columns for definitions shadowed in the other definition directory (C-45 for bricks)

**Files:** `models/DataObject/Objectbrick/Definition/Listing.php:57-77`; `models/DataObject/Objectbrick/Definition.php:66-92`,
`:144-214`, `:351-387`, `:413-449`; `Objectbrick/Definition/Dao.php:61-149` (`:147-148`);
`bundles/CoreBundle/src/Command/ClassesRebuildCommand.php:136-149`;
`lib/DataObject/ClassBuilder/PHPObjectBrickContainerClassDumper.php:44-55`

- Answers the open question in C-45: `Objectbrick\Definition\Listing::loadFileNames()` returns every `*.php` from
  `var/classes/objectbricks` **and** `config/pimcore/classes/objectbricks`, while `getByKey()` lets the custom file
  win. A key present in both folders is listed twice, the `var/classes` copy first.
- `ClassesRebuildCommand` calls `save(false)` for each listed definition. For the stale copy this:
  - runs `createUpdateTable()` for every class it lists, and `removeUnusedColumns()` drops every store / query column
    the stale copy does not know (**data loss**; the custom copy saved afterwards re-adds them empty);
  - adds the key to the `allowedTypes` of classes and fields that only the stale copy lists and **saves those
    classes** (`createContainerClasses()`); the later save of the custom copy runs with `save(false)`, which skips
    `cleanupOldFiles()`, so those assignments stay for good and the brick shows up in classes nobody configured;
  - puts the stale instance into the runtime cache (`:198`), so class saves triggered in between rebuild tables of
    other bricks against it.
- Unlike the field-collection loop, the brick loop catches every exception and only prints it (`:145-148`): the
  deployment goes on and reports success.
- The same listing feeds `PHPObjectBrickContainerClassDumper` (containers are built from both copies' assignments,
  `:44-55`), `ClassesDefinitionsBuildCommand` and `SelectOptions\Config::getFieldsUsedIn()`.

**Fix direction:** same fix as C-45: build the key list from both folders, load each key once through `getByKey()`,
log shadowed files; let `ClassesRebuildCommand` fail (non-zero exit) when a brick cannot be saved. Add a test with a
key in both folders.

---

## [ ] C-56 – Select-option values and labels are written unescaped into the generated enum: a backslash breaks out of the string (PHP code injection), duplicate values break every `from()`

**Files:** `models/DataObject/SelectOptions/Config/Dao.php:80-86`, `:110-120`; `models/DataObject/SelectOptions/Config.php:165-183`,
`:367-370`; `models/DataObject/SelectOptions/Data/SelectOption.php:30-36`, `:118-125`;
`lib/DataObject/ClassBuilder/SelectOptionsEnumBuilder.php:109-142` (`:111`, `:136`), `:144-165`, `:182-185`, `:192`;
`lib/DataObject/ClassBuilder/PHPSelectOptionsEnumDumper.php:32-38`

```php
$template = 'case %caseName% = \'%optionValue%\';';
protected function escapeSingleQuote(string $value): string { return str_replace("'", "\\'", $value); } // "\" is not escaped
```

- `Config\Dao::save()` stores the options and writes `var/classes/DataObject/SelectOptions/<Id>.php` without any
  validation of the option values or labels. The builder escapes `'` but not `\`, so a value containing `\'` turns into
  `\\'`: an escaped backslash followed by a closing quote. Checked: the value
  `\'; public function getLabel(): string { return ""; } } echo "…"; enum Zz: string { //` (with any label set)
  produces a file that parses and runs the top-level code each time the enum is included. The generated enum is
  meant to be used by project code (`Colors::tryFrom($object->getColor())`), so the code runs in web requests and
  workers.
- Who can do it: whoever can edit select options in the admin, plus anything that writes the YAML / settings-store
  config. That permission is normally given to fewer people than "system settings", but it is not "can deploy PHP".
- Without malice:
  - a value or label that ends in `\` (`C:\`, a regex, `\n` typed literally) makes the file a `ParseError` → every
    request that touches the enum fails (checked);
  - two options with the same value (two empty values, a copy-and-paste row) generate valid code, but every
    `from()` / `tryFrom()` / `tryFromNullable()` then throws `Error: Duplicate value in enum …` (checked; `cases()`
    still works, so the admin list looks fine);
  - the case-name check `^[A-Z-a-z_]` accepts a leading `-` (the `-` in the character class is literal), which
    generates `case -foo` → `ParseError` (checked with `preg_match`).
- The configuration is written **before** the enum is generated: when the builder throws (unknown trait, case-name
  collision), the broken config is already stored and `ClassesRebuildCommand` / `ClassesDefinitionsBuildCommand` fail
  on it on every deployment.
- C-32 covers the select-options **id**; values, labels, names and the save order are not covered there.

**Fix direction:** validate in the model before saving (`Config::validate()`, called from `Dao::save()`): unique
non-empty values, case names matching `^[A-Za-z_][A-Za-z0-9_]*$`, no PHP reserved case names (`class`, …), traits /
interfaces resolvable. Generate code with `var_export()` for every string (or a code generator, I-83) instead of
hand-made escaping. Build the enum first (into a temp file, `php -l` / `token_get_all()` check), then save the
config, then move the file into place. Add tests for `\`, `'`, duplicate values and a leading `-`.

---

# Scope 20 – Document root files not processed in Scope 6 (`Email.php`, `Folder.php`, `Snippet.php`)

> Notes
> - Scanned (read in full): `Document/Email.php`, `Document/Folder.php`, `Document/Snippet.php`, plus their DAOs
>   `Email/Dao.php`, `Folder/Dao.php`, `Snippet/Dao.php` for context.
> - Skipped, already processed in Scope 6: `Dao.php`, `DocType.php`, `Editable.php`, `Hardlink.php`, `Link.php`,
>   `Listing.php`, `Page.php`, `PageSnippet.php`, `Service.php` (C-25–C-28, B-138–B-155, I-73–I-85). Points already
>   made there are not repeated: the type-DAO `id = NULL` overwrite for `Snippet/Dao.php` / `Email/Dao.php` – B-148;
>   one generic type DAO – I-78; missing `declare(strict_types=1)` in the three DAOs – I-73; `Editable\Snippet`
>   dependencies – B-163.
> - Collaborators read where a defect originates in the interplay: `models/Document.php::correctPath()`,
>   `PageSnippet.php` (content-main handling `:84`, `:245-262`, `:431-452`, `:462-477`, `:600-603`),
>   `Document/Service.php::rewriteIds()` (`:341-360`), `lib/Mail.php`, `lib/Helper/Mail.php`,
>   `lib/Routing/Dynamic/DocumentRouteHandler.php:78-90`, `bundles/CoreBundle/config/pimcore/default.yaml:285-318`,
>   `bundles/CoreBundle/src/DependencyInjection/Configuration.php:1061-1070`, `install.sql` (`documents_email`,
>   `documents_snippet`).
> - `Folder.php` and `Snippet.php` only set `$type`; `Email.php` is a set of plain string accessors. None of them has
>   behaviour that can lose data, corrupt state or open a security hole on its own: **no critical item**.
> - No Symfony 7.4 / PHP 8.4 incompatibility: the three root files are `declare(strict_types=1)`, fully typed and use
>   no removed API. The `documents_email` columns can be `NULL` (`create()` inserts only the id), but
>   `assignVariablesToModel()` → `setValues($data, true)` skips `null`, so the `string` setters do not throw.
> - The admin UI (`pimcore/admin-ui-classic-bundle`) is not in this repo and `vendor/` is not installed: how the admin
>   tree handles `children_supported` and whether the admin preview needs `direct_route` was **not verified**.
>   Nothing was executed.
> - Critical items: none. Bugs: B-334–B-337. Improvements: I-183–I-185.

---

# Scope 21 – Document sub-directory classes (`Email/`, `Folder/`, `Link/`, `Listing/`, `Page/`, `PageSnippet/`, `Service/`, `Snippet/`)

> Notes
> - Scanned (read in full): `Document/Email/Dao.php`, `Folder/Dao.php`, `Link/Dao.php`, `Listing/Dao.php`,
>   `Page/Dao.php`, `PageSnippet/Dao.php`, `Service/Dao.php`, `Snippet/Dao.php` (604 lines; these are the only files in
>   the eight sub-directories).
> - All eight classes are already named by earlier items, so this scope is a **gap-fill**: only points not recorded
>   yet are added. Not repeated: type-DAO `id = NULL` overwrite – B-148 (`Email`, `Link`, `Page`, `Snippet`);
>   `Link\Dao` resolving the href on load and lost object targets – B-146; unknown / corrupt editable rows in
>   `PageSnippet\Dao::getEditables()` – B-143; `getTranslations()` overwriting `$linkedData` and `null` source
>   documents – B-144; pretty-URL duplicates and the unordered lookup – B-153; document listing `published` column
>   – B-154; missing `declare(strict_types=1)` – I-73; `getTranslationSourceId(): mixed` – I-74; DBAL 4
>   `getQueryPart()` in the listing helper – I-76; one generic type DAO – I-78; `Listing::getCount()` loading all
>   ids – I-79; duplicate `deleteAllProperties()` in `Email\Dao` / `Page\Dao` – I-183; empty `Folder\Dao` – I-184.
> - Collaborators read where a defect originates in the interplay: `Document.php::doDelete()` (`:645` calls
>   `removeTranslation()`), `Document.php::getById()` (two DAO loads per uncached document), `Document/Dao.php`
>   (`getById()`, `getByPath()`, `create()`, `delete()`), `Document/Service.php::copyAsChild()` and
>   `getNearestDocumentByPath()`, `Document/Listing.php`, `lib/Model/Listing/Dao/QueryBuilderHelperTrait.php`,
>   `lib/Db/Helper.php::upsert()`, `lib/Routing/Dynamic/DocumentRouteHandler.php`, `install.sql` (`documents`,
>   `documents_page`, `documents_translations`, `tree_locks`), `bundles/CoreBundle/config/pimcore/default.yaml:148`
>   (`sql_mode = ''`).
> - The translation-link DAO has bugs that make translations point to the wrong document or block a delete
>   (B-338–B-340). Nothing loses content or opens a security hole: **no critical item**.
> - Corrections to earlier items (found while reading the schema): `documents_page.prettyUrl` **has** an index
>   (`install.sql:160`, `KEY prettyUrl`), so the "no index" remark in B-153 and I-84 is out of date. The rest of
>   B-153 still applies. `documents_translations.sourceId` is the leading column of the primary key
>   (`sourceId`, `language`), so it is indexed; only the foreign key in I-84 is missing.
> - Pretty URLs are compared with the table's default collation (case- and, with `utf8mb4_0900_ai_ci` /
>   `utf8mb4_general_ci`, accent-insensitive). `DocumentRouteHandler::handleDirectRouteRedirect()` (`:260-295`)
>   redirects other spellings to the stored URL, so this is **not** duplicate content. `/uber` and `/über` on two pages
>   do collide, but that is the B-153 duplicate case.
> - No Symfony 7.4 / PHP 8.4 incompatibility: no removed API and no implicit nullable parameter. With PHP ≥ 8.1
>   `pdo_mysql` returns native `int`s, so `Document::getById()` gets no string ids (no deprecation per listing row).
>   The `"source"` string literal in `getTranslations()` breaks only under `ANSI_QUOTES` (I-186).
> - The admin UI (`pimcore/admin-ui-classic-bundle`), which calls `addTranslation()` / `removeTranslationLink()`, is
>   not in this repo and `vendor/` is not installed. What the admin validates before linking was **not verified**.
>   Nothing was executed.
> - Critical items: none. Bugs: B-338–B-340. Improvements: I-186–I-189.

---

# Scope 22 – Document `Hardlink/**` files not processed in Scope 6 (`Wrapper/Email`, `Folder`, `Hardlink`, `Page`, `Snippet`, `WrapperInterface`)

> Notes
> - Scanned (read in full): `Document/Hardlink/Wrapper/Email.php`, `Wrapper/Folder.php`, `Wrapper/Hardlink.php`,
>   `Wrapper/Page.php`, `Wrapper/Snippet.php`, `Wrapper/WrapperInterface.php` (183 lines). The five wrapper classes are
>   empty `extends <Type> implements WrapperInterface { use Hardlink\Wrapper; }` shells, so the defects show up through
>   the inherited type behaviour and the shared trait.
> - Read for context, skipped because Scope 6 already processed them: `Hardlink.php`, `Hardlink/Dao.php`,
>   `Hardlink/Service.php`, `Hardlink/Wrapper.php`, `Hardlink/Wrapper/Link.php`. Not repeated: no source validation /
>   hardlink cycles, `null` source dereferences in `Wrapper::getProperty()` and `Wrapper\Link::getHref()`, custom
>   document types without a wrapper, `Hardlink::hasChildren()` loading every child – B-147; type-DAO `id = NULL`
>   overwrite in `Hardlink\Dao` – B-148; scheduled tasks of hardlinks not deleted – B-212; `func_get_args()` cache
>   key in `Hardlink::getChildren()` – I-74; class-name rewrite in serialized data in `upperCastDocument()` – I-75;
>   one generic type DAO – I-78; `Snippet` / `Email` / `Folder` cannot be `final` because the wrappers extend them –
>   I-184. Also not repeated from other TODO files: the `use_main_domain` crash in `StaticPageGenerator::getStoragePath()`
>   for hardlink children ([lib TODO-2](../lib/TODO-2-BUGS.md) B-61), stale static files after move / rename
>   ([CoreBundle TODO-2](../bundles/CoreBundle/TODO-2-BUGS.md) B-38) and the `TypeError` in
>   `HardlinkCanonicalListener` for a missing source (CoreBundle B-52).
> - Collaborators read where a defect originates in the interplay: `Document.php::getById()` / `getBlockedVars()`
>   (`:97-107`, `:187-258`), `Element/AbstractElement.php::__sleep()` / `__wakeup()` (`:642-670`),
>   `Document/Page.php::getFullPath()` (`:79-93`), `lib/Document/StaticPageGenerator.php`,
>   `StaticPageGeneratorListener.php`, `lib/Maintenance/Tasks/StaticPagesGenerationTask.php`,
>   `lib/Routing/Dynamic/DocumentRouteHandler.php` (`:136-189`, `:269`, `:308-331`), `lib/Navigation/Builder.php`
>   (`:328-420`), `lib/Navigation/Page/Document.php:75-95`, `lib/Cache/Core/CoreCacheHandler.php:367-376`,
>   `HardlinkCanonicalListener.php`, `lib/Twig/Extension/PimcoreObjectExtension.php:48`,
>   `doc/02_MVC/02_Template/README.md:101`, `default.yaml:285-318` (`type_definitions`).
> - A wrapper is a field-by-field copy of the **source** document (same id, same class fields, source DAO via
>   `initDao(get_class($source))`) with only `path` / `key` changed to the hardlink location. Every behaviour that the
>   copied type keys on its own fields (pretty URL, static generator flags, controller, cache tag, id) therefore acts
>   as if it were the source document. C-57 is the case where this writes wrong content to storage.
> - No Symfony 7.4 / PHP 8.4 incompatibility: all six files have `declare(strict_types=1)`, no implicit nullable
>   parameter, no removed API. Trait-over-parent method precedence (B-341) is unchanged in PHP 8.4.
> - Nothing was executed; there are no hardlink tests in `tests/` (I-191). The admin UI that lets editors choose a
>   hardlink source (`pimcore/admin-ui-classic-bundle`) is not in this repo, so whether it rejects a hardlink as source
>   (B-341) was **not verified**.
> - Critical items: C-57. Bugs: B-341–B-342. Improvements: I-190–I-191.

## [ ] C-57 – Hardlinked pages are written into the static-page storage: the hardlink URL is frozen forever, and the source's pretty URL is overwritten with the hardlink rendering

**Files:** `models/Document/Hardlink/Wrapper/Page.php:26-29`; `models/Document/Hardlink/Service.php:29-74` (copy of all
fields); `lib/Document/StaticPageGenerator.php:43-80`, `:82-115`, `:126-132`;
`bundles/CoreBundle/src/EventListener/Frontend/StaticPageGeneratorListener.php:71-121`, `:125-153`, `:155-172`;
`lib/Maintenance/Tasks/StaticPagesGenerationTask.php:44-48`; `lib/Routing/Dynamic/DocumentRouteHandler.php:158-189`

```php
// StaticPageGeneratorListener::onKernelResponse()
$document = $this->documentResolver->getDocument();            // the Wrapper\Page of the hardlink request
if ($document instanceof Page && $document->getStaticGeneratorEnabled()) {   // flag copied from the source page
    $this->staticPageGenerator->generate($document, ['response' => $response]);
}
// StaticPageGenerator::getStoragePath()
$path = $document->getRealFullPath();                           // hardlink path
if ($document instanceof Document\Page && $document->getPrettyUrl()) {
    $path = $document->getPrettyUrl();                          // the SOURCE page's pretty URL (copied field)
}
```

- `Wrapper\Page` is an `instanceof Page` and carries the source page's `staticGeneratorEnabled`, `prettyUrl` and
  `staticGeneratorLifetime`. A request to the hardlink URL (direct hardlink or a child below a `childrenFromSource`
  hardlink) is routed with the wrapper as the request document, so `onKernelResponse()` writes the hardlink's HTML
  (hardlink navigation, the hardlink tree's inherited `language` and other properties merged in by the wrapper) into
  the static storage.
- **Source has no pretty URL:** the file is `<hardlink path>.html`. The static router (`onKernelRequest()`, priority
  510, before routing) or the web-server rule serves this file for every later request to the hardlink URL, so the
  page is never rendered again. Nothing refreshes or removes it:
  - saving the source page removes only `<source path>.html` (`onPostAddUpdateDeleteDocument()`);
  - saving, unpublishing or deleting the **hardlink** does nothing (`Hardlink` is not a `PageSnippet`);
  - `StaticPagesGenerationTask` regenerates only real `type = 'page'` rows; `staticGeneratorLifetime` is checked
    there only.
  → Content edits, unpublishing or deleting the source, changing / deleting the hardlink, and turning the static
  generator off are all ignored at the hardlink URL until someone deletes the file by hand. A deleted or unpublished
  page stays publicly reachable.
- **Source has a pretty URL:** the wrapper writes to the **source's** pretty-URL file. Visitors of the source URL get
  the hardlink's rendering (other language tree, other navigation, other properties) until the next source save or
  maintenance run, and the next request to the hardlink overwrites it again (flip-flop between the two renderings).
  `generate()` uses one lock key per document id, so the two writers do not even serialize against different paths
  in a predictable way.
- With `use_main_domain = true`, deeper hardlink children make `getStoragePath()` crash instead (lib B-61); fixing
  B-61 alone makes this item reachable for them as well.

**Fix direction:** never generate static files for hardlink wrappers: in `StaticPageGeneratorListener::onKernelResponse()`
skip `WrapperInterface` documents (the same check `Page::getFullPath()` and `DocumentRouteHandler.php:269` already
make), and add a guard in `StaticPageGenerator::generate()` / `getStoragePath()` so CLI or custom callers cannot do it
either. If hardlink static pages are wanted, make it an explicit hardlink option with its own storage path (the
hardlink path, never the source pretty URL) and invalidate those files on source save, hardlink save and delete. Add a
one-off cleanup (or documentation) for existing `<hardlink path>.html` files and pretty-URL files written by wrappers.
Tests in I-191.

---

# Scope 23 – DataObject `Traits/**` files not yet named by an item (`ClassSaved`, `CompositeIndex`, `ContextPersistence`, `DataHeight`, `ElementWithMetadataComparison`, `FieldDefinitionEnrichmentData`, `FieldDefinitionEnrichmentModel`)

> Notes
> - Scanned (read in full): `DataObject/Traits/ClassSavedTrait.php`, `CompositeIndexTrait.php`,
>   `ContextPersistenceTrait.php`, `DataHeightTrait.php`, `ElementWithMetadataComparisonTrait.php`,
>   `FieldDefinitionEnrichmentDataTrait.php`, `FieldDefinitionEnrichmentModelTrait.php` (624 lines).
> - Skipped because earlier items already name them (a class already in the TODOs is not processed again):
>   `DataWidthTrait` (I-156), `DefaultValueTrait` (B-297), `FieldcollectionObjectbrickDefinitionTrait` (Scope 19 notes,
>   B-278, I-136), `LazyLoadedRelationTrait` (C-44), `LocateFileTrait` (C-45, B-255), `ObjectVarTrait` (B-311, I-163,
>   I-169), `OwnerAwareFieldTrait` (B-301, I-169), `SimpleComparisonTrait` (B-266), `SimpleNormalizerTrait` (C-43).
>   `DataWidthTrait` is only mentioned as the twin of `DataHeightTrait` in I-196.
> - Not repeated: `DataContainerAwareInterface::preSave/postSave(mixed)` narrowing and the
>   `FieldDefinitionEnrichmentModelInterface` / `suppressEnrichment()` interface gaps (I-160); DDL through
>   `executeQuery()` → `executeStatement()` (I-76); identifier validation of class ids / field names (C-32); brick index
>   drop / re-create (B-326); unescaped `ownername LIKE` in `Localizedfield\Dao` (B-179); brick metadata deletes without
>   `ownername` (B-324) and the brick-row addressing (C-54).
> - Collaborators read where a defect originates in the interplay: `ClassDefinition.php::save()` (`:330-410`),
>   `setCompositeIndices()` (`:1111-1125`), `doEnrichFieldDefinition()` (`:690-698`), `cleanupForExport()`,
>   `generateClassFiles()`; `ClassDefinition/Dao.php:196-216`; `ClassDefinition/Service.php:98-150`
>   (JSON import); `Localizedfield/Dao.php:160-190`, `:640-665`, `:925-952`; `Localizedfield.php:535-546`;
>   `Concrete.php::retrieveRelationData()` (`:770-803`); `Concrete/Dao.php:133-150`;
>   `ClassDefinition/Data/Relations/AbstractRelations.php:110-330`; `ClassDefinition/Data/{Block,Localizedfields,
>   Fieldcollections,Objectbricks,Select,Multiselect,AdvancedManyToMany(Object)Relation}.php`;
>   `DynamicOptionsProvider/SelectionProviderTrait.php`; `Data/ElementMetadata.php`, `Data/ObjectMetadata.php`;
>   `Objectbrick/Data/Dao.php:70-100`; `Fieldcollection/Definition/Dao.php`, `Objectbrick/Definition/Dao.php`;
>   `doc/05_Objects/01_Object_Classes/05_Class_Settings/05_Composite_Indices.md`,
>   `doc/05_Objects/01_Object_Classes/01_Data_Types/30_Dynamic_Select_Types.md`.
> - No Symfony 7.4 / PHP 8.4 incompatibility: all seven files have `declare(strict_types=1)`, no implicit nullable
>   parameter, no removed API; `composer.json` pins `doctrine/dbal ^3.8`, so `quoteIdentifier()` /
>   `fetchAllAssociative()` are fine. PHP 8.4 options (property hooks, `#[\Override]`) are in TODO-3.
> - The admin UI (`pimcore/admin-ui-classic-bundle`), which builds the composite-index and class-definition JSON and
>   calls the enrichment in the editor, is not in this repo; what it validates or sends was **not verified**.
>   Nothing was executed.
> - Critical items: C-58, C-59. Bugs: B-343–B-347. Improvements: I-192–I-198.

## [ ] C-58 – Composite-index definitions go unescaped into `ALTER TABLE`: SQL injection through the class definition (C-32 for composite indices)

**Files:** `models/DataObject/Traits/CompositeIndexTrait.php:34-90` (`:63`, `:81`, `:87`);
`models/DataObject/ClassDefinition.php:1111-1125` (`setCompositeIndices()`, no validation);
`models/DataObject/ClassDefinition/Dao.php:215-216`; `models/DataObject/Localizedfield/Dao.php:944`, `:950`;
`models/DataObject/ClassDefinition/Service.php:120-147` (`compositeIndices` in the JSON import)

```php
$newIndicesMap['c_' . $key] = implode(',', $columns);                    // index_key / index_columns: raw config
$this->db->executeQuery('ALTER TABLE `' . $table . '` DROP INDEX `' . $key . '`;');
$this->db->executeQuery('ALTER TABLE `' . $table . '` ADD INDEX `' . $key . '` (' . $columnName . ');');
```

- `index_columns` are joined with `,` and placed **unquoted** inside the parentheses; `index_key` is placed inside
  hand-written backticks without escaping. Neither is validated anywhere in the model (`setCompositeIndices()` only
  maps many-to-one fields to `__id` / `__type`).
- A column entry such as `oo_id), DROP COLUMN \`name\`, ADD INDEX c_y (oo_id` stays inside one `ALTER TABLE` statement,
  so it works even without stacked statements: columns of the store / query / localized tables are dropped (data
  loss) or changed on the next class save. With `pdo_mysql`'s default multi-statement support, `; …` after the list
  runs arbitrary SQL (users, password hashes, other tables). Not executed.
- Who can do it: everyone with the `classes` permission and every class-definition import
  (`pimcore:definition:import:class`, admin JSON import via `Service::importClassDefinitionFromJson()`). An imported
  third-party / shared class export is enough. C-32 lists class names, ids and field names, not composite indices.
- The same SQL runs on every `classes-rebuild` from the definition file, so a once-saved payload is re-applied on
  every deployment.

**Fix direction:** validate in `setCompositeIndices()` and again in `save()`: `index_key` `^[A-Za-z0-9_]{1,62}$`,
`index_type` in `store|query|localized_store|localized_query`, `index_columns` a non-empty list of names that are real
columns of the target table (field names of the class plus `oo_id` / `ooo_id` / `language` …, after the
`__id` / `__type` mapping). In the trait quote every identifier with `quoteIdentifier()` and build one statement per
table (I-192). Robustness gaps without malice are B-347. Tests in I-198.

## [ ] C-59 – Localized relation fields inside object bricks: the current-relations and load filters ignore the brick type, so saving one brick type deletes or takes over the relations of another

**Files:** `models/DataObject/Traits/ContextPersistenceTrait.php:46-73` (`:55`), `:112-121` (`:119-120`);
`models/DataObject/ClassDefinition/Data/Relations/AbstractRelations.php:132`, `:195-226`, `:285-287`,
`:303-312` (`:310`); `models/DataObject/Concrete.php:770-803`; context set in
`models/DataObject/Objectbrick/Data/Dao.php:76-97` and `Localizedfield/Dao.php:166-186`

```php
// write side (enrichDataRow): one ownername per brick type
$row['ownername'] = '/objectbrick~' . $fieldname . '/' . $containerKey . '/localizedfield~localizedfield';
// read side (prepareMyCurrentRelations) and AbstractRelations::load(): no brick type
$filter = '/' . $context['containerType'] . '~' . $fieldname . '/%';   // prefix "/objectbrick~bricks/"
```

Setup: an `Objectbricks` field `bricks` allows brick types `A` and `B`; both have a `localizedfields` block with a
relation field of the same name (`parts`; a common pattern for shared names like `relatedDocuments`). An object has
`A.parts[en] = [X]` and `B.parts[en] = [Y]`.

- `retrieveRelationData()` treats a trailing `%` as a prefix match (`str_starts_with`). The prefix
  `/objectbrick~bricks/` matches the rows of **every** brick type in that field (`.../A/localizedfield~…` and
  `.../B/localizedfield~…`).
- **Load:** `AbstractRelations::load()` uses the same filter → `A.parts[en]` and `B.parts[en]` both load as `[X, Y]`.
  The editor and the API show the other brick's relations.
- **Save of `A` (language dirty):** current = `{X(A), Y(B)}` (`prepareMyCurrentRelations()`), new = `{X(A), Y(A)}`
  (the loaded union, re-written with `A`'s ownername by `enrichDataRow()`). `Y(A)` is inserted as new, `Y(B)` does not
  match any new row (ownername differs) → it is in `removedRelations` and **deleted by id** (`:285-287`). `B` has lost
  its relation; after the next save of `B` the two lists are permanently merged into whichever brick saved last.
- Even a save that changes nothing in `A` deletes `B`'s rows as soon as the language is dirty. Field collections are
  not affected (their filter contains the item index, and they are re-inserted in full).

**Fix direction:** build the brick filter from the same parts as the write side:
`'/objectbrick~' . $fieldname . '/' . $context['containerKey'] . '/localizedfield~'` (prefix), in the trait **and** in
`AbstractRelations::load()`, through one shared ownername builder (I-193) so read, load and write can never drift
again. Data repair: for each object with two brick types sharing a localized relation name, compare
`object_relations_<classId>` rows per ownername with the version history; document it in the upgrade notes. Tests in
I-198.

---

# Scope 24 – Document `Editable/**` files not yet named by an item (`Wysiwyg`, `Checkbox`, `Dao`, `Area/*`, `Block/*`, `Areablock/Item`, the five interfaces)

> Notes
> - Scanned (read in full): `Document/Editable/Wysiwyg.php`, `Checkbox.php`, `Dao.php`, `Area/AbstractArea.php`,
>   `Area/Info.php`, `Block/AbstractBlockItem.php`, `Block/Item.php`, `Areablock/Item.php`, `BlockInterface.php`,
>   `EditableInterface.php`, `EditmodeDataInterface.php`, `IdRewriterInterface.php`, `LazyLoadingInterface.php`
>   (1034 lines).
> - Skipped because Scope 6 items already name them (a class already in the TODOs is not processed again): `Area`
>   (B-158, I-78), `Areablock` (B-157, B-158, I-73, I-78), `Block` (B-158), `Date`, `Input`, `Textarea`, `Embed`,
>   `Renderlet` (B-163), `Image` (B-162, B-163, I-78, I-80), `Link` (C-46, B-159, I-80), `Multiselect`, `Numeric`,
>   `Select`, `Table`, `Pdf` (B-163, B-164), `Relation`, `Snippet` (B-160, B-163), `Relations` (B-161),
>   `Scheduledblock` (C-28, B-156, B-158), `Video` (C-10, B-163, B-164, I-80). The `Wysiwyg` / `Checkbox` *data types* of
>   `DataObject\ClassDefinition\Data` are different classes (B-271) and are not repeated.
> - Not repeated: missing `declare(strict_types=1)` in `Editable/Dao.php` (I-73, "nearly all DAOs"); the `__sleep()`
>   copies and duplicated `renderDialogBoxEditables()` (I-78); container look-ups in editables (I-80); `Areablock`
>   passing a `null` document to `Areablock\Item` (B-157); unknown editable types / corrupt data aborting
>   `getEditables()` (B-143); `unserialize()` without `allowed_classes` (I-75).
> - Collaborators read where a defect originates in the interplay: `Document/Editable.php:146`, `:286-318`,
>   `:422-466`, `:477-482`, `:538-590`, `:645-660`, `:713-728`; `Document/PageSnippet.php:138-200`, `:359-385`;
>   `Document/Service.php:286-296`, `:345-375`; `Document/Editable/{Areablock,Block,Scheduledblock,Area}.php`
>   (`getElements()` / `buildInfoObject()`); `lib/Tool/Text.php:41-120`, `:295-324`; `lib/Tool/DomCrawler.php`;
>   `lib/Tool/Serialize.php`; `lib/Db/Helper.php:38-53`; `lib/Templating/Renderer/EditableRenderer.php:52-99`;
>   `lib/Document/Editable/EditableHandler.php:167-226`; `bundles/CoreBundle/config/pimcore/default.yaml:62-120`;
>   `bundles/InstallBundle/dump/install.sql:98-106`; `doc/03_Documents/01_Editables/06_Block.md`,
>   `02_Areablock/02_Bricks.md:228-242`.
> - No hard Symfony 7.4 / PHP 8.4 incompatibility: `symfony/dom-crawler` / `symfony/html-sanitizer` `^7.4` APIs used
>   here (`Crawler::filter()` / `html()`, `HtmlSanitizer::sanitizeFor()`) are unchanged; no implicit nullable parameter;
>   all files except `Dao.php` are strict. PHP 8.4 options (`#[\Override]`, promoted readonly properties, typed
>   returns on `BlockInterface`) are in TODO-3.
> - The admin UI (`pimcore/admin-ui-classic-bundle`), which sends the editmode data, saves drafts and renders version /
>   session previews, is not in this repo; which of its paths render a document without `save()` was **not
>   verified**. Nothing was executed.
> - Critical items: C-60. Bugs: B-348–B-350. Improvements: I-199–I-204.

## [ ] C-60 – Wysiwyg editable: the HTML sanitizer runs only in `save()`, so drafts, versions, previews and pre-sanitizer content are rendered unsanitized (stored XSS)

**Files:** `models/Document/Editable/Wysiwyg.php:71-93`, `:134-141`; `models/Document/PageSnippet.php:138-158`
(`update()` → `$editable->save()`), `:164-200` (`saveVersion()` – no editable `save()`); `lib/Tool/Text.php:41-46`
(`wysiwygText()` does no sanitizing); sanitizer config `bundles/CoreBundle/config/pimcore/default.yaml:62-67`

```php
public function setDataFromEditmode(mixed $data): static { $this->text = $data; ... }   // raw editor HTML
public function frontend() { return Text::wysiwygText($this->text, [...]); }             // raw output
public function save(): void { if (is_string($this->text)) { $this->text = $helper->sanitizeFor('body', $this->text); } ... }
```

- The `pimcore.wysiwyg_sanitizer` (the fix for the wysiwyg XSS advisories) is applied in exactly one place: when the
  editable row is written by `PageSnippet::update()`. Every other way the text reaches HTML output skips it:
  - **Drafts / versions:** `PageSnippet::saveVersion()` (admin "save draft", autosave, `saveOnlyVersion`) serialises
    the document with the editables as set by `setDataFromEditmode()`; no editable `save()` runs. Rendering that
    version (version preview, editmode of the latest draft, `pimcore_preview`) outputs `<img src=x onerror=…>` /
    `<a href="javascript:…">` unchanged.
  - **Session / preview documents:** documents built from editmode data and rendered without saving.
  - **Old content:** rows written before the sanitizer existed (or by imports that bypass `save()`, e.g. raw
    `documents_editables` inserts, `setDataFromResource()` + `saveVersion()`) are never sanitized on output.
- Impact: an editor with save-draft rights (no publish right) stores script in a draft; an administrator who opens the
  version or draft preview runs it in the admin origin (session riding, user / permission changes). Classified
  critical because it bypasses the security control the sanitizer was introduced for. Which admin paths render
  without `save()` was not verified (admin bundle not in repo); the model paths above are.

**Fix direction:** sanitize on input, not on persist: run the sanitizer in `setDataFromEditmode()` (and in
`setDataFromResource()` for values that did not come from the database, or once on load with a cheap "already
sanitized" check), keep the `save()` call as a second line of defence. Optionally sanitize in `frontend()` /
`getDataEditmode()` behind a config flag for legacy content, and provide a one-off command that re-sanitizes existing
`documents_editables` rows of type `wysiwyg` and the serialized versions. Type handling of the setters is B-348;
structure in I-199; tests in I-204.

---

# Scope 25 – Document `DocType/**` files not yet named by an item (`Listing.php`, `Listing/Dao.php`)

> Notes
> - Scanned (read in full): `Document/DocType/Listing.php`, `Document/DocType/Listing/Dao.php` (136 lines).
> - Skipped because earlier items already name them (a class already in the TODOs is not processed again):
>   `Document/DocType.php` and `Document/DocType/Dao.php` (B-155). They were read for context only.
> - Not repeated: missing `declare(strict_types=1)` in the DAOs (I-73); the process-wide static
>   `PimcoreLocationAwareConfigDao::$cache` (B-191); `DocType::create()` saving a definition without `name` / `type` and
>   `getById()` swallowing every exception (B-155). The id-list / read-target mismatch is the same root cause as
>   B-188 (predefined metadata) and B-206 (predefined properties); it is recorded for doc types as B-351 because the
>   listing is a different class.
> - Collaborators read where a defect originates in the interplay: `Document/DocType.php:30-122`;
>   `Document/DocType/Dao.php:33-95`; `lib/Model/Dao/PimcoreLocationAwareConfigDao.php:37-106`;
>   `lib/Config/LocationAwareConfigRepository.php:70-105`, `:158-171`, `:263-278`;
>   `lib/Model/Listing/Traits/FilterListingTrait.php`, `OrderListingTrait.php`;
>   `bundles/CoreBundle/src/DependencyInjection/Configuration.php:140`, `:845-870`.
> - No class in this repo instantiates `DocType\Listing`; its consumers (admin doc-type panel, "add document" menus)
>   are in `pimcore/admin-ui-classic-bundle`, which is not in this repo – the admin impact was **not verified**.
> - No Symfony 7.4 / PHP 8.4 incompatibility: no removed API, no implicit nullable parameter; `Listing.php` is strict,
>   `Listing/Dao.php` is not (I-73). Nothing in the two files can lose data or open a security hole on its own:
>   **no critical item**. Nothing was executed.
> - Critical items: none. Bugs: B-351–B-352. Improvements: I-205–I-207.
