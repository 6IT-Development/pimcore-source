# models – TODO 3: Improvements

Scope: **(1)** `models/Asset.php` and `models/Asset/**` (Dao, Listing, Service, Folder, Image, Image/Thumbnail/**, Thumbnail/**,
Video, Video/**, Document, Document/**, MetaData/**, WebDAV/**, Enum, Archive/Audio/Text/Unknown)
**(2)** `models/WebsiteSetting.php` and `models/WebsiteSetting/**` (Dao, Listing, Listing/Dao), plus the direct consumer
`lib/Config.php::getWebsiteConfig()` (`:173-262`) and `tests/Model/WebsiteSetting/WebsiteSettingTest.php`
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
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-2-BUGS.md](TODO-2-BUGS.md),
[CoreBundle TODO-3](../bundles/CoreBundle/TODO-3-IMPROVEMENTS.md) (I-67 WebDAV server service, I-71 WebDAV/thumbnail security model)

Code quality, readability, logic simplification and Symfony 7.4 / PHP 8.4 modernisation. Nothing here is broken today.
IDs are stable – refer to them as `I-xx` in later sessions. Line numbers are from commit `1b0711d927` (Scope 2:
`7f0331cab9`, Scope 3: `2d382456c7`, Scope 4: `3e2bf1c4d8`, Scope 5: `0b659e9ce0`, Scope 6: `990568d287`,
Scope 7: `7d51be3297`, Scope 8: `d527536468`, Scope 9: `718bf5020c`,
Scope 10: `70fc218e66`, Scope 11: `a5cc6dc56a`, Scope 12: `d78291fed7`, Scope 13: `1650c79550`, Scope 14: `6bde85085f`,
Scope 15: `205ed92381`, Scope 16: `765372d338`, Scope 17: `d5ca47d13d`,
Scope 18: `bcac4499f6`, Scope 19: `87799e51e6`, Scope 20: `47897412ea`, Scope 21: `86f089cf1a`,
Scope 22: `c9ae84b8cf`).
Grouped: **A** PHP 8.4 / types / strictness, **B** Symfony 7.4 / DBAL / runtime, **C** shared logic / duplication,
**D** per-file logic & readability, **E** tests; Scope 2 is in its own section **F** WebsiteSetting (I-32–I-40), Scope 3 in **G** Dependency (I-41–I-50),
Scope 4 in **H** Translation (I-51–I-63), Scope 5 in **I** Site (I-64–I-72), Scope 6 in **J** Document / Element /
DataObject (I-73–I-85), Scope 7 in **K** Metadata / Notification (I-86–I-92), Scope 8 in **L** Property / Schedule (I-93–I-100),
Scope 9 in **M** Version (I-101–I-109), Scope 10 in **N** User (I-110–I-121),
Scope 11 in **O** Tool (I-122–I-128), Scope 12 in **P** DataObject root files (I-129–I-134), Scope 13 in **Q** DataObject
`Folder/Dao` (I-135), Scope 14 in **R** DataObject `Listing` / `Fieldcollection` / `Localizedfield` (I-136–I-141),
Scope 15 in **S** DataObject `Classificationstore/**` configuration models (I-142–I-148), Scope 16 in **T**
DataObject `ClassDefinition/**` (I-149–I-160), Scope 17 in **U** DataObject `Data/**` value objects (I-161–I-169),
Scope 18 in **V** DataObject `QuantityValue/**` units and conversion (I-170–I-174),
Scope 19 in **W** DataObject `Objectbrick/**` and `SelectOptions/**` (I-175–I-182),
Scope 20 in **X** Document `Email` / `Folder` / `Snippet` (I-183–I-185),
Scope 21 in **Y** Document sub-directory DAOs (I-186–I-189),
Scope 22 in **Z** Document hardlink wrappers (I-190–I-191).

---

## A – PHP 8.4 / types / strictness

### [ ] I-01 – Add `declare(strict_types=1)` where missing

- `models/Asset.php`, `models/Asset/Dao.php`, `Asset/Listing/Dao.php`, `Asset/Image/Thumbnail.php`,
  `Asset/Image/Thumbnail/Config/Dao.php`, `Config/Listing/Dao.php`, `Asset/Video/Thumbnail/Config/Dao.php`,
  `Config/Listing/Dao.php`.
- Prerequisite fixes that currently rely on coercion: B-33 (`false` → `''`), B-57 (int media key),
  `Image\Thumbnail\Config::getByArrayConfig()` (`Config.php:483`, `:490`, `:575` – string `quality`/`highResolution`
  from YAML/legacy arrays). Cast/validate in the factories first.

### [ ] I-02 – Add native types to untyped members

- `Asset.php:104` `protected $stream` → `protected mixed $stream = null` (resource|null); `getStream()` (`:1142`) return
  type, `setStream($stream)` (`:1196`) parameter type.
- `Asset::setData(mixed $data)` (`:1130`) → `string` (arrays give a `TypeError` in `fwrite`); check `tmpfile()` for `false`.
- `Asset::getData(): bool|string` (`:1117`) → `string` and throw, or `string|false`.
- `Asset::setCustomSettings(mixed)` (`:1331`) → `array|string|null` with explicit handling.
- `Asset::getByPath()` (`:218`) `static|null` → `?static`; `@method getChildAmount($user = null)` (`:70`) typed.
- `Image::getImageTransformInstance(): ?Adapter` (`Image.php:200`) never returns null → `Adapter`.
- WebDAV `File::getContentType()` / `getLastModified()` → nullable (B-35).

### [ ] I-03 – `#[\Override]` on overridden / interface methods

- `Asset.php`: `save`, `delete`, `getById`, `getFullPath`, `__clone`, `__wakeup`, `__sleep`, `resolveDependencies`,
  `getParent`, `setParent`, `getChildren`, `hasChildren`, …
- `Video.php` (`update`, `clearThumbnails`), `Document.php` (`update`), `Image.php`, `Folder.php`.
- `MetaData/ClassDefinition/Data/*` subclasses.
- WebDAV `Tree::move`, `File::put/get/delete/getSize/…`, `Folder::getChildren/getChild/createFile/…`.
- Thumbnail interface implementations in `Image/Thumbnail.php`, `Video/ImageThumbnail.php`, `Document/ImageThumbnail.php`.

### [ ] I-04 – Enums and constants for string literals

- Asset types (`'folder'`, `'image'`, `'video'`, `'document'`, …) → backed enum or class constants; replaces the
  hard-coded list at `Asset.php:615` (`in_array($this->getType(), ['image','video','document'])`) with a polymorphic
  `supportsUpdateTasks()`.
- Video conversion status `'inprogress'` / `'finished'` / `'error'` (`Video/Thumbnail/Processor.php:89-110`, `:151`,
  `:229`, `:284`) → backed enum.
- Thumbnail formats (`'source'`, `'pjpeg'`, `'webp'`, `'avif'`, …) and transformation method names
  (`Image/Thumbnail/Config.php:609-647` `in_array` chains) → enums.
- Custom-setting keys repeated as literals (`'duration'`, `'videoWidth'`, `'videoHeight'`, `'thumbnails'`,
  `'image_thumbnail_time'`, `'image_thumbnail_asset'`, `'document_page_count'`) → class constants.
- `customSettingsCanBeCached` limit `10e6` (`Asset.php:1334`, = 10 000 000; reads like a typo for `1e6`) → named constant.
- `Image\Thumbnail\Processor::$argumentMapping` and the video counterpart → `private const`.

### [ ] I-05 – Constructor promotion, `readonly`, visibility

- WebDAV `File` / `Folder`: `public function __construct(private readonly Asset $asset)`.
- `Asset\Service::$_user` promoted + readonly; `$_copyRecursiveIds` → `private`.
- `Video\Thumbnail\Processor`: `processId`, `assetId`, `config` readonly; unused `$status` (`:58`) removed.
- `Video\Thumbnail\Config`: `public $medias`, `public $filenameSuffix` → `private` (getters exist).
- `Asset\Dao::$thumbnailStatusCache` `public static` → private + `resetThumbnailStatusCache()` (see B-27).
- `Image\Thumbnail::$hasListenersCache` is `protected static` in a `final` class → `private`.

### [ ] I-06 – PHP 8.4 functions / idioms

- `Asset::getTypeFromMimeMapping()` (`:472-489`): `array_find_key()` + `array_any()` instead of nested loops.
- `Asset::typeMatch()` (`:244-254`): `return static::class === Asset::class || $asset instanceof static;`.
- Replace `rand()` (`Asset.php:581`) with `random_int()`.
- Replace by-reference `foreach` (`Asset.php:1519-1528`, `Service.php:252`, `Video.php:101-111`) with `array_map` /
  `array_values()`.
- Strict comparisons `===`/`!==` for ids, types and paths (`Asset.php:538`, `:563`, `:656`, `:729`, `:773`, `:979`;
  `Video/Thumbnail/Config/Dao.php:52`, `:58` `$id != null` treats `"0"` as null).
- Replace `goto` in `Image/Thumbnail/Processor::applyTransformations()` (two-pass, see B-46).

### [ ] I-07 – Serialization API

- `Asset::__sleep()` / `__wakeup()` → `__serialize()` / `__unserialize()` (soft-deprecated direction in PHP 8.5; do it
  together with B-07). Same in `AbstractElement` (outside scope).

---

## B – Symfony 7.4 / DBAL / runtime

### [ ] I-08 – Bound parameters instead of concatenated SQL (DBAL 4 readiness)

- `Asset/Dao.php:209-224`, `:422`, `:433-434`, `:467`; `Listing.php:71-85`; `Dao.php:337-351`, `:393-407`.
- Use `ArrayParameterType::INTEGER` for id lists; one shared helper for the workspace permission fragment (also fixes
  the duplication in I-14 and the anchoring in B-28).

### [ ] I-09 – `ResetInterface` for static caches (worker runtimes)

- `Asset\Dao::$thumbnailStatusCache`, `Image\Thumbnail::$hasListenersCache`, Image/Video config RuntimeCache entries.
- Register in a `kernel.reset`-tagged service or clear in `LongRunningHelper` (see B-27, B-51).

### [ ] I-10 – Temp-file lifecycle

- `File::getLocalTempFilePath()` registers one shutdown closure per file (`lib/File.php:87`) – grows in workers and
  cleans up in the wrong process (B-63).
- Unlink promptly with `try/finally` in `Image/Thumbnail/Processor.php:272`, `:341`, `Image.php:94`, WebDAV (B-29),
  `Asset::create()` (B-11).
- Explicit `$lock->release()` in `finally` in all thumbnail generators.

### [ ] I-11 – Lock handling

- `Image/Thumbnail/Processor.php:257-259`: default 300 s TTL without `refresh()` → slow TIFF/PDF generations can be
  duplicated. Pass a TTL or refresh.
- Document a single lock strategy for image/video/document thumbnails (blocking vs. non-blocking, see B-69).

### [ ] I-12 – Process / external tools

- `EmbeddedMetaDataTrait::readEmbeddedMetaData()` (`:58`) resolves the `exiftool` executable even when `$useExifTool`
  is false – resolve lazily.
- `lib/Video/Adapter/Ffmpeg.php:105-112` runs `ffmpeg -codecs` and discards the result – remove.
- `Ffmpeg::colorChannelMixer()` (`:422-426`) appends a config string verbatim into `-vf` (admin-defined
  `x,movie=/etc/passwd` injects filters). Validate against `^[0-9.:=a-z]+$`.

### [ ] I-13 – Remote-storage efficiency

- `Asset.php:733`: `stream_is_local($storage->readStream($path))` opens a full read stream (HTTP GET on S3) just to test
  locality and never closes it – decide by adapter/config, cache the result.
- `ClearTempFilesTrait` lists the whole thumbnail storage recursively (B-53).
- `Image/Thumbnail/Processor.php:241-253`: redundant second staleness check (extra remote `lastModified`, and uses
  `getModificationDate()` while `:194` uses `getDataModificationDate()`) – remove or unify.
- `Image::isAnimated*()` (`:385`, `:411`) loads the full file via `getData()` – read a stream prefix.
- Folder preview (`Folder.php:388`): `file_get_contents()` + `write()` → `writeStream()`.
- Deferred path (`Processor.php:153`, `:224-225`): adapter created even on early return; `Config::exists()` and
  `TmpStore::add()` hit config repo / DB on every render – memoise per request.

---

## C – Shared logic / duplication

### [ ] I-14 – One permission-SQL builder for `hasChildren()` / `getChildAmount()` / `Listing`

- `Asset/Dao.php:331-410` (two near-identical methods, `LIMIT 1` vs `COUNT(*)`) and `Listing.php:71-85`.
- `Dao::isAllowed()` (`:450-461`) re-implements `getParentIds()` without the loop guard and loads every ancestor;
  `empty($permissionsParent)` at `:474` is always true there.

### [ ] I-15 – Merge `Video\ImageThumbnail` and `Document\ImageThumbnail` into the trait / an abstract base

- Duplicated: `getPath()`, `__toString()`, `createConfig()` (three variants with different nullability, incl.
  `Image/Thumbnail.php:430`), lock + cache-file generation, error-placeholder block
  (Video `:64-80`/`:108-114`/`:131-193`/`:203-219` vs Document `:59-75`/`:112-124`/`:129-171`/`:173-201`).
- A `generateSourceFrame()` hook is enough. The drift has already produced C-13, B-69, B-70; Video also skips
  `checkMaxScalingFactor` and ignores `$deferred` (`Video/ImageThumbnail.php:125-161` runs ffmpeg synchronously).

### [ ] I-16 – One XMP/XML block scanner

- `Video.php:272-343` and `EmbeddedMetaDataTrait.php:139-228` contain near-identical chunked tag scanners, prefix
  stripping and namespace cleanup. Extract `extractXmlBlock($stream, $openTag, $closeTag)` (and reuse the overlap logic
  for C-14).

### [ ] I-17 – Merge `Service::copyRecursive()` / `copyAsChild()`

- `Asset/Service.php:72-172` (~40 duplicated lines; `setObjectVar('id', null)` vs `setId(null)`).
- `copyContents()` (`:177-201`) copies stream, custom settings and properties but not **metadata** – verify intent.

### [ ] I-18 – Image / Video thumbnail config & listing parity

- Video listing Dao lacks filter/order (B-72); Video `getByName()` lacks `clone` (B-73); both use `crc32($name)`
  RuntimeCache keys (`Image/Thumbnail/Config.php:227`, `Video/Thumbnail/Config.php:113`) – collisions return another
  config. Use the name or `xxh3`.
- `Image/Thumbnail/Config/Listing/Dao.php:26` extends `Config\Dao` and so inherits `save()`/`delete()`;
  `getTotalCount()` (`:47-50`) reloads and mutates the listing. Extend the location-aware DAO directly.
- Shared base for both listing DAOs and both config DAOs.

### [ ] I-19 – One `addMetadata`/`getMetadata` name normaliser and one status-cache writer

- See B-06 (`~` → `---`) and B-60 (status-cache writes in Processor vs. trait).

---

## D – Per-file logic & readability

### [ ] I-20 – `Asset.php`: dead and redundant code

- `:343-345` `str_starts_with($tmpFile, PIMCORE_SYSTEM_TEMP_DIRECTORY)` is always true.
- `:669-671` and the `.htaccess` check at `:692` are unreachable (`getValidKey(…, 'asset')` trims dots, `isValidKey`
  at `:657` already rejects).
- `:683` `elseif ($this->getId() == 1)` → `else`.
- `:595` `isset($updatedChildren)` always true – initialise before the loop.
- `:1498` `getObjectVar('metadata')` = `$this->metadata`.
- `:894-898` `getFullPath()` builds `$path` before the frontend early return.
- `:230-233` `prepareGetByIdParams` applied twice.

### [ ] I-21 – `Asset::create()` writes `data` payloads twice

- `:347-349` dumps to `$tmpFile`, then `setData()` copies into `tmpfile()` again. Open `$tmpFile` and pass it as stream.

### [ ] I-22 – `Asset.php` smaller issues

- `:1808` `getRealPath() . '/' . getId()` yields `//` (unlike `clearThumbnails()` `:1686`) and swallows every exception.
- `setStream()` (`:1210-1214`) leaks the original non-rewindable stream; `fopen` result unchecked.
- `self::` vs `static::` for temp-file helpers (`:1211` vs `:1275`/`:1285`) – pick one.
- `customSettingsCanBeCached` (`:1334`) never resets to `true` once false.
- `POST_LOAD` fires on every `getById()` incl. runtime-cache hits, and mid-transaction from `update()` (`:820`) –
  document or guard (`:311-315`).
- Misleading docblock `:1653` (`$elementData` annotated as `ElementInterface`).

### [ ] I-23 – `Asset/Dao.php` & `Listing/Dao.php`

- `:132-135` `SELECT … LIMIT 1` before `DELETE` – just `DELETE`.
- `:123` `getValidTableColumns()` per property – `array_intersect_key` once; `:65`, `:147` (and `Service.php:267`,
  `:296`) fetch the metadata loader from the container inside a loop – hoist.
- `:209` `LIKE '<old>%'` (no slash) over-selects siblings `/foobar` for cache clearing – use `'<old>/%'`.
- `Listing/Dao.php:25-38` N+1 `Asset::getById()` per row; `if ($assetData['type'])` always true; `getCount()`
  (`:72-81`) loads all ids instead of `COUNT(*)`.

### [ ] I-24 – `Asset/Service.php` smaller issues

- `pathExists()` (`:203`) unused `$type` parameter.
- `minimizeMetadata()` / `expandMetadataForEditmode()` read `$item['type']` / `$item['language']` without `??`.
- `getStreamedResponseByUri()` (`:550`) `if ($config)` is dead.
- `:519` says "see logs" but nothing is logged; `@throws` lists only `FilesystemException`.

### [ ] I-25 – WebDAV readability / efficiency

- `File::setName()` / `Folder::setName()` (`File.php:53-66`, `Folder.php:180-190`) are never reached because
  `Tree::move()` overrides Sabre's base implementation – the `rename` permission check is dead (root of CoreBundle C-13).
  `Folder::setName()` does not set `userModification`. Route `Tree::move()` through a shared guarded helper.
- `Tree.php:54`, `:64` `setData($sourceAsset->getData())` reads whole files into memory → `setStream()`.
- `Folder::getChildren()` (`:54-58`) dead try/catch; loads every child fully (N+1) – `loadIdList()` with lazy nodes.
- `WebDAV/Service.php:42`, `:54` duplicate the 30 s filter → `pruneLog()`; fixed shared temp path (see B-30).

### [ ] I-26 – `Folder.php` preview

- `:324` `$colums` typo; tile streams (`:353`) never closed; see B-31/B-32 for logic.

### [ ] I-27 – Image thumbnail dead code

- `Image/Thumbnail.php:121-127`, `:480`: `$this->config` is never null → "use original image" branch unreachable;
  `$this->asset` checks at `:121`, `:480`; `$thumb->reset()` at `:181`.
- `Image.php:221-225` `'unknown'` return in `getFormat()`.
- `ImageThumbnailTrait.php:358-360`, `:427` `new Config()` fallbacks.
- Make `getConfig()` non-nullable for image thumbnails.

### [ ] I-28 – Image thumbnail smaller issues

- `Image.php:96` `generateLowQualityPreview`: `file_get_contents()` → `false` unchecked.
- `Image.php:237-241` `getDimensions()` returns stored settings without casting (possible `TypeError` in `getWidth(): int`).
- `Image/Thumbnail.php:461`: commas encoded only in srcset; admin paths don't encode spaces → `rawurlencode` segments.
- `Config.php:643-644` `cropPercent` estimate uses `ceil` while `Adapter::cropPercent(int …)` truncates – align
  rounding (accept floats in the adapter).
- Remove empty `@internal` docblock boilerplate in `Image/Thumbnail/Config.php`.

### [ ] I-29 – Video / Document smaller issues

- `Document/ImageThumbnail.php:93` calls `$config->setFilenameSuffix()` before the null check at `:102`, which can never
  fail (`createConfig()` non-nullable) – remove the check.
- `Document/ImageThumbnail.php:43` uses `TemporaryFileHelperTrait`, already used by `ImageThumbnailTrait` (`:33`).
- `Video/Thumbnail/Config.php:210-229` `selectMedia()` never called for video configs; `filenameSuffix` (`:104`,
  `:279-287`) unused by the video processor.
- `Document::processPageCount(?string $path)` (`Document.php:52`) – unused `$path`.
- `Document::getText()` (`:130`) `!$text = Cache::load(...)` never serves empty text from cache – compare `=== false`
  and store `''` on failure.
- Fix empty docblocks / stray `*` lines (`Video.php:85-89`, `:153-157`; `Video/Thumbnail/Processor.php:60-64`) and the
  "backwards compatility" typo in both thumbnail `__toString()` docblocks.
- Log messages in `Video/ImageThumbnail.php:144`, `:150` say "document".

### [ ] I-30 – `Archive` / `Audio` / `Text` / `Unknown` / `Enum\PdfScanStatus`

- Trivial and correct. Optional: `PdfScanStatus` gains an `UNKNOWN` case for C-14; type classes could become a single
  generic class keyed by type (low value – only if the enum from I-04 lands).

---

## E – Tests

### [ ] I-31 – Unit / integration tests for the fixed defects

- `Asset\Dao::updateChildPaths()` with repeated segments and mixed element types (C-01).
- Folder move on a non-local Flysystem adapter (in-memory adapter) – C-02, B-02, B-03.
- `Asset::create()` with `php://temp`, `sourcePath`, oversize images (C-03, C-04, B-11, B-12).
- `__clone()` + `saveVersion()` with pending stream (C-07).
- `Service::getUniqueKey()` edge cases (`README`, `data.data`) – B-21.
- WebDAV MOVE across folders with rename / overwrite / failure (C-08, B-20).
- Thumbnail config name validation (C-09); `getHash()` coverage (B-38); uppercase extensions (B-37).
- Video processor with missing bitrates, missing job, deleted asset (C-10, C-11); malformed spherical XMP (C-12).
- PDF scan with boundary-straddling `/JS` and `/J#53` (C-14).
- Metadata `getMetadataByName()` localisation and `~` names (B-05, B-06).

---

## F – WebsiteSetting (Scope 2)

### [ ] I-32 – Strictness and native types

- Add `declare(strict_types=1)` to `models/WebsiteSetting/Dao.php` and `WebsiteSetting/Listing/Dao.php`.
- `Dao::getById(?int $id = null)` / `getByName(?string $name = null, …)` (`Dao.php:32-34`, `:50-54`): the optional
  parameters plus loose `!= null` checks make `0` / `''` behave like "use the model value". Callers always pass the
  value, so make them required and drop the checks.
- `setData(mixed $data)` (`WebsiteSetting.php:176`) → `string|int|float|bool|ElementInterface|null`. Arrays and objects
  currently reach DBAL and fail there, or are converted to `"Array"`.
- `setId(int)` / `setCreationDate(int)` / `setModificationDate(int)` against `?int` properties: mark the setters
  `@internal`, or use PHP 8.4 asymmetric visibility (`public private(set) ?int $id`) for DB-managed fields once the DAO
  hydrates through a dedicated method.

### [ ] I-33 – `WebsiteSettingType` backed enum and one typed value accessor

- The type set is documented three times (`WebsiteSetting.php:229`, `:242`, `install.sql:606`) and switched on in
  `lib/Config.php:229-234` and `WebsiteSetting::getData()` (`:191`).
- Introduce `enum WebsiteSettingType: string { case Text = 'text'; case Document = 'document'; case Asset = 'asset';
  case Object = 'object'; case Bool = 'bool'; }` with an `isElement()` helper. Accept `WebsiteSettingType|string` in
  `setType()` for BC.
- Add `getValue(): mixed`, which performs the same casting as `getWebsiteConfig()` (bool → `bool`, text → `string`,
  element → `?ElementInterface`). Today `getByName('flag')->getData()` returns the raw DB string `'1'`/`''` while
  `pimcore_website_config('flag')` returns `bool`. Use `getValue()` in `lib/Config.php` so the cast logic lives in one place.
- `setData()` with an unsaved element stores a `null` id while changing the type; reject it with an exception.

### [ ] I-34 – Class-level clean-up in `WebsiteSetting.php`

- `final class` with `protected` properties and `protected static` methods → `private`.
- Fluent setters return `WebsiteSetting` with `@return $this` docblocks → `: static` and drop the docblocks.
- Mixed `WebsiteSetting::getCacheKey()` (`:97`) and `self::getCacheKey()` (`:275`) → `self::`.
- Mixed `Service::getElementType()` (`:179`, imported alias) and `Element\Service::getElementById()` (`:192`) → one style.
- `getByName()` `@throws Exception` (`:93`) is never thrown; the comment "which setting is which is" (`:55`) has a
  doubled phrase; the empty docblock lines at `:228-244` can go.
- `getByName()` `if ($setting->getId() > 0) … return $setting;` (`:118-125`): the `return $setting` branch is
  unreachable (rows always have an auto-increment id).
- `getById()` returns the **shared** RuntimeCache instance. Unsaved in-memory changes by one caller leak into
  `getByName()` / `getWebsiteConfig()` for the rest of the request; document this or return clones for read paths.
- `delete()` on an unsaved setting dispatches events and clears the cache tag for nothing; return early when `id === null`.
- `#[\Override]` wherever a parent method is overridden (none today; add it if `save()` / `delete()` move into an
  interface).

### [ ] I-35 – Query and caching efficiency

- `getByName()` does not cache negative results or fallback-language results (the mapping key for the requested
  language is stored only on a direct hit, `:118-122`). The common Twig/PHP pattern
  `WebsiteSetting::getByName('optional')?->getData()` runs a DB query on every call. Cache misses too, and invalidate
  them together with C-15.
- `getWebsiteConfig()` loads **all** settings of all sites and languages through `Listing::load()`, which runs one
  `getById()` query per row (`Listing/Dao.php:38-40`, N+1). Hydrate from a single
  `SELECT * … WHERE (siteId IS NULL OR siteId IN (0, :site)) AND language IN ('', :lang)`.
- `Config::getWebsiteConfig()` treats an empty config `[]` as a cache miss (`if (!$config)`, `lib/Config.php:203`), so
  installations without website settings rebuild it on every request. Compare against `false`.

### [ ] I-36 – DAO simplification

- `Dao::create()` + `update()` (`Dao.php:95-115`): one `INSERT` with all valid columns (also fixes B-89). Compute the
  column whitelist once with `array_intersect_key($vars, array_flip($this->getValidTableColumns(...)))` instead of calling
  `getValidTableColumns()` inside the `array_filter` callback for every key (`:99`).
- Rewrite the `getByName()` ranking as an explicit priority expression with an `id` tie-breaker (see B-84). This also
  lets MySQL use a composite `(name, siteId, language)` index (I-37).
- `Listing/Dao.php:49`: `'SELECT COUNT(*) as amount FROM website_settings ' . getCondition()` – stray space and unused
  alias; align with `load()`.

### [ ] I-37 – Schema

- Add a composite (unique, see B-86) index on `(name, siteId, language)`. Today the query uses `INDEX name` only and
  filters/sorts the rest.
- `creationDate` / `modificationDate` are nullable with default `0`, and `siteId` mixes `NULL` and `0` (B-84). Normalise
  in a migration.

### [ ] I-38 – `Listing`

- `getSettings()` (`Listing.php:48-55`) relies on `load()` having set `$settings`; add `@return list<WebsiteSetting>` and
  a non-null guarantee (`?? []`).
- `setSettings()` → fluent `: static` for consistency with other listings.
- Default order by `id` (makes `getWebsiteConfig()` deterministic until B-83 is fixed).

### [ ] I-39 – Site detection parity and events

- `getByName()` requires the caller to pass `$siteId`, while `getWebsiteConfig()` detects the current site (including
  admin preview via the main request document, `lib/Config.php:181-196`). Offer the same detection (for example
  `getByNameForCurrentSite()`), or document the difference. Templates using `getByName()` silently ignore site overrides.
- `save()` / `delete()` have no transaction and no failure events. `PRE_*` listeners cannot veto cleanly, and a failing
  DAO call leaves listeners without a `POST_*` or `*_FAILURE` signal. Align with the element models
  (`POST_ADD_FAILURE` etc.) if listeners need it.

### [ ] I-40 – Tests

Extend `tests/Model/WebsiteSetting/WebsiteSettingTest.php`. Today it covers only `getByName()` precedence. Add cases for:
- delete then `getByName()` / `getById()` in the same request, and an override created after a lookup (C-15);
- `getWebsiteConfig()` parity with `getByName()` for every case already in the test, including reversed insert order
  (B-83);
- `siteId = 0` vs `NULL` (B-84);
- fallback language with and without a neutral row (B-85);
- duplicate rows, empty name, invalid type (B-86, B-87);
- `save()` with a non-existent id (B-88);
- listing with a concurrently deleted row (B-90).

---

## G – Dependency (Scope 3)

### [ ] I-41 – Strictness, native types and a typed target value object

- Add `declare(strict_types=1)` to `models/Dependency/Dao.php`. It currently coerces DB strings into
  `addRequirement(int …)` (`Dao.php:59`). Once strict, cast explicitly, or rely on PDO native ints (PHP 8.1+).
- `protected array $requires` and all `array` returns are untyped shapes. Introduce
  `final readonly class DependencyTarget { public function __construct(public ElementType $type, public int $id) {} }`
  (or at least `@return list<array{id: int, type: string}>`) for `getRequires()`, `getRequiredBy()`, `addRequirement()`
  and `setRequires()`. `getRequiredByWithPath()` / filters return a different shape (`id`, `type`, `path`); document both.
- `Dao::getBySourceId(?int $id = null, ?string $type = null)` (`Dao.php:41`): the model always passes both. Make them
  required and drop the falsy check (see B-99).
- Constructor promotion for `sourceId` / `sourceType` (fixes the uninitialized state, B-99). `getBySourceId()` →
  `: static` with `new static()`.
- PHP 8.4: `getSourceId()` / `getSourceType()` + setters could become `public private(set)` properties once callers
  stop using the setters. Keep the setters as deprecated BC shims.

### [ ] I-42 – `ElementType` enum for `sourceType` / `targetType`

- `'object' | 'document' | 'asset'` is hard-coded in `Dao.php:73`, `:132`, `:323`, every SQL branch, the schema enum,
  `Element\Service::getDependedElement()` (`Service.php:257-262`) and `Element\Service::getElementType()`. No shared enum
  exists in `models/Element`.
- Add `enum ElementType: string { case Asset = 'asset'; case Document = 'document'; case DataObject = 'object'; }` (shared
  with the rest of `models/`). Accept `ElementType|string` in `setSourceType()` / `addRequirement()` for BC and validate
  there. This replaces the three `in_array(…) / SuspiciousOperationException` blocks (B-104).

### [ ] I-43 – One query builder for the three path queries; bound parameters; consistent API

- `getFilterRequiresByPath()`, `getFilterRequiredByPath()` and `getRequiredByWithPath()` (`Dao.php:63-119`, `:121-178`,
  `:319-362`) are about 90 % identical: the same validation, the same `ORDER BY` whitelist, three `UNION` branches that
  differ only by table / path expression, and three different `LIMIT` styles (`sprintf`, `sprintf`, concatenation).
  Extract one private method `fetchWithPath(direction: requires|requiredBy, ?string $filter, …)` that builds the branches
  from a map `['object' => ['objects', 'CONCAT(o.path, o.key)'], …]`. This also fixes C-16, B-93, B-95 and B-96
  structurally.
- Bind every value (`:sourceId`, `:sourceType`, `:value`) – DBAL 4 readiness, same as I-08. Use
  `setFirstResult()` / `setMaxResults()` (DBAL QueryBuilder) instead of string `LIMIT`.
- Use single quotes for SQL string literals: `dependencies.targettype="object"` (`Dao.php:51-53`, `:294-296`) breaks
  under `ANSI_QUOTES` (double quotes become identifiers).
- `if (count($x) > 0) return $x; else return [];` (`:114-118`, `:173-177`) → `return $this->db->fetchAllAssociative(...)`.
- Model wrappers: `getFilterRequiresByPath()` / `getFilterRequiredByPath()` (`Dependency.php:105-117`) do not forward
  `$orderBy` / `$orderDirection`, while `getRequiredByWithPath()` does. There is no `getRequiresWithPath()` counterpart at
  all. Make the four public methods symmetrical, and give the filter queries a matching `…TotalCount()`; the admin
  cannot page a filtered list correctly without it.

### [ ] I-44 – Efficiency

- `Asset::delete()` (`Asset.php:1018`) and `Document::doDelete()` (`Document.php:640`) call `$this->getDependencies()`
  only to call `cleanAllForElement()`. That loads **all** `requires` rows with three `LEFT JOIN`s and a filesort first.
  Use `new Dependency()` like `AbstractObject::doDelete()` (`:438`), or a static `Dependency::cleanAllForElement()`.
- `getBySourceId()` always loads and path-sorts the full `requires` list, then `getRequires($offset, $limit)` slices it in
  PHP (`Dependency.php:96-103`) and `getRequiresTotalCount()` counts it in PHP. Objects with thousands of relations pay
  this on every tab open. Make `requires` lazy and add SQL-side `getRequires($offset, $limit)` and `COUNT(*)`, mirroring
  `getRequiredBy()` / `getRequiredByTotalCount()`. Keep the in-memory list only for building a new set in `save()`.
- `getRequiredBy()` / `getBySourceId()` sort on columns of three `LEFT JOIN`ed tables, so MySQL always filesorts the
  whole result before `LIMIT`. Sorting by `(type, id)` for paging, or by one computed path in a derived table, is
  cheaper.
- `Element\Service::get*ForFrontend()` loads every listed element fully (`getById()` per row, N+1) just to read id,
  path, type, subtype and the `list` permission. Use `getRequiredByWithPath()`-style rows plus a batch permission check.
- `cleanAllForElement()` / `clear()`: `Helper::selectAndDeleteWhere()` with `quoteInto()` string building. For one
  `(sourcetype, sourceid)` prefix of the unique `combi` index a plain
  `$this->db->delete('dependencies', ['sourcetype' => $type, 'sourceid' => $id])` is enough. If the select-then-delete
  is there on purpose (avoids gap locks), document the reason.

### [ ] I-45 – `Dao::save()` simplification

- Typo `$existingDepencies` (`Dao.php:228-236`, `:252`, `:259`).
- Deduplicate `requires` by `type . '_' . id` before computing the delta (B-103). Then the
  `catch (UniqueConstraintViolationException) {}` (`:279`) is only needed for concurrency and should log, not hide.
- `if ($targetType && $targetId)` (`:246`) drops invalid entries silently. Validate in `addRequirement()` instead
  (I-42, B-99).
- Bulk statements: one multi-row `INSERT` (or `INSERT IGNORE`) instead of one round trip per target, and the delete via
  `executeStatement('DELETE … WHERE id IN (?)', [$ids], [ArrayParameterType::INTEGER])` instead of `implode()` into SQL
  (`:265-268`).
- Wrap in a transaction (C-17).
- `Helper::quoteInto()` passes a second `$type` argument to `Connection::quote()` (`lib/Db/Helper.php:106`). DBAL 4
  removes that parameter and accepts only `string`. `clear()` / `cleanAllForElement()` pass int ids. Bound parameters
  avoid the issue (DBAL 4 readiness).

### [ ] I-46 – Docblocks and readability in `Dependency.php` / `Dao.php`

- Wrong docs:
  - `Dependency::cleanAllForElement()` (`:71-75`) says "schedules a sanity check for the affected **targets**". It
    schedules the **sources** that require the element.
  - `Dao::cleanAllForElement()` (`Dao.php:180-184`) says "Clear all relations in the database". It clears only rows
    where the element is the source (see B-97).
  - `Dao::getRequiredBy()` "Loads the relations that need the given source element" – the term "source" is used for
    the element in both directions throughout the class. Rename the model fields (`elementId` / `elementType`) or add
    one class docblock that explains requires vs. required-by.
- Empty docblock lines (`Dependency.php:28-45`, `:47-50`, `:59-62`, `:170-173`; `Dao.php:36-40`, `:180-184`, `:218-221`,
  `:285-289`, `:364-367`), the blank lines inside `getFilterRequiresByPath()` / `getFilterRequiredByPath()`
  (`Dependency.php:106-109`, `:113-116`; `Dao.php:69`, `:128`), and the column-aligned parameter style
  (`?int    $offset`, `Dependency.php:125-128`; `Dao.php:64-68`, `:122-126`) differ from the rest of `models/`.
- `@method void save()` (`Dependency.php:24`): document that it writes the delta for `requires` only. `clean()`
  (`:85-89`) has no caller in this repo; deprecate or document it.
- The class is `@internal`, but `ElementInterface::getDependencies(): Dependency` exposes it publicly. Either drop
  `@internal` from the read API or return an interface / read-only view.
- `isRequired()` docblock: "an other" → "another".

### [ ] I-47 – `Element\Service` dependency helpers: one implementation

- `getRequiredByDependenciesForFrontend()`, `getRequiresDependenciesForFrontend()`, `getFilterRequiresForFrontend()` and
  `getFilterRequiredByPathForFrontend()` (`Service.php:163-242`) are four copies of the same loop. They differ only in
  the result key and in whether the rows come from the model or from an array. Extract one
  `buildDependenciesForFrontend(iterable $rows, string $key): array` (fixes B-105).
- `getDependedElement()` (`:255-263`) duplicates `Service::getElementById()`; use it.

### [ ] I-48 – `ElementDependenciesHandler` clean-up

- `try/finally` for the global state (B-92), using the existing `DataObject\Service::useInheritedValues()`.
- `showUnpublished()` returns the previous value and has the opposite meaning of its name. Rename to
  `disableHideUnpublished()` and `restoreHideUnpublished()`. `$hideUnpublished` holds the **previous** state.
- Self-reference skip uses loose `==` (`:63`). With typed ids (I-41) use `===`.
- `Service::getElementById($type, $id)` (`:41`) uses the cached instance. For async transports, pass `['force' => true]`
  so the dependencies are computed from the committed state, not a stale cached element.
- Skip the work when `pimcore.dependency.enabled` was switched off after the message was queued.
- `#[\Override]` does not apply; mark the class `final` (it is `@internal`).

### [ ] I-49 – Schema and maintenance

- `dependencies.id` is `BIGINT` while `sourceid` / `targetid` are `INT UNSIGNED`. The surrogate key is only used for
  the delta delete in `save()`. The unique `combi` key could be the primary key, making `id` unnecessary and deletes
  simpler. Check `install.sql:64-73` and the migration impact first.
- There is no command that rebuilds the dependency graph for all elements, or one that purges rows whose source or
  target no longer exists (B-97, C-17). Add a maintenance task or a console command
  (`pimcore:dependencies:rebuild` / `:cleanup`).

### [ ] I-50 – Tests

Extend `tests/Model/Element/DependenciesTest.php`. Today it covers only basic `getRequires()` / `getRequiredBy()` after a
forced reload. Add cases for:
- filter values with `'`, `%`, uppercase letters, regex metacharacters and an empty value (C-16, B-94, B-95);
- `getFilterRequiredByPath()` with sources of mixed types (B-93);
- `orderBy: 'path'` on all path queries (B-96);
- deleting a target whose source is a folder / link, then checking counts and `isRequired()` (B-97);
- an exception in `resolveDependencies()` leaving `hideUnpublished` / `getInheritedValues` unchanged (B-92);
- `getDependencies()` on an unsaved element and after a save without forced reload (B-99, B-100);
- `limit` without `offset`, and stable paging with ties (B-101, B-102);
- the filter helpers returning the list key when nothing matches (B-105).

---

## H – Translation (Scope 4)

### [ ] I-51 – Strictness and native types

- Add `declare(strict_types=1)` to `models/Translation/Dao.php` and `Translation/Listing/Dao.php`. Before that, cast the
  DB values explicitly in hydration (`(int)` dates, `(string)` text, see C-19 / B-109).
- `Translation::$translations` is `string[]` only in the docblock. Use `array<string, string>`, and validate in
  `setTranslations()` (today any value, including `null` and arrays, can be stored and saved).
- `protected ?string $type = 'simple'` with `getType(): string` and `setType(?string)`: make it non-nullable
  (`TranslationType`, I-52). `setCreationDate(int)` / `setModificationDate(int)` vs `?int` getters: make the setters
  nullable or the properties non-nullable with a default.
- Fluent setters are typed `: Translation` with `@return $this`. Use `: static`. `setDomain()`, `setType()`,
  `setUserOwner()`, `setUserModification()` return `void` – the API is inconsistent; pick one style.
- `Translation` is `final`, so `protected` members can be `private`.
- `Listing\Dao::getQueryBuilder(...$columns)` → `getQueryBuilder(string ...$columns)`. `getByKey(?array $languages)` →
  `@param list<string>|null`. `getAvailableLanguages()` / `getAvailableDomains()` → `@return list<string>`.
- `/** @var string */` on the typed `public const string TABLE_PREFIX` (`Dao.php:35-38`) is redundant.
- `Listing::getTranslations(): array` returns `getData(): ?array`; add the `@return Translation[]` guarantee or a
  `?? []`.

### [ ] I-52 – `TranslationType` enum and one domain validator

- `'simple'` (and "everything else is ICU") is hard-coded in `Translation.php:65`, `:86`, `Translator.php:172`. Add
  `enum TranslationType: string { case Simple = 'simple'; case Custom = 'custom'; }` (check the admin bundle for the real
  second value first). `setType(TranslationType|string)` for BC, validated with `TranslationType::from()` (fixes the
  type part of B-110).
- Domain validation exists in three variants (`Listing::setDomain()`, `Translator::lazyInitialize()`, none in
  `Translation`). Add one `TranslationDomain` check (config list + identifier pattern, C-21), used by all entry points,
  and memoised (I-56).
- `DOMAIN_DEFAULT` / `DOMAIN_ADMIN` and the hard-coded `'messages'` in `Translator.php:276`: use the constant.

### [ ] I-53 – API naming and deprecations (PHP 8.4 `#[\Deprecated]`)

- `IsAValidLanguage()` → `isValidLanguage()`; `isAValidDomain()` → `isValidDomain()`. Keep the old names as
  `#[\Deprecated(since: '…')]` aliases (PHP 8.4 attribute; triggers `E_USER_DEPRECATED` natively).
- `setDate()` is only used by the dead legacy branch in `Listing\Dao::getAllTranslations()` (B-109). Deprecate.
- `Dao::createOrUpdateTable()` never updates → `createTableIfNotExists()` (and move it out of `save()`, B-112).
- `getByKey()` mixes three jobs: find, create-if-missing, and "fallback to the key" presentation (C-18). Split into
  `find(string $key, string $domain, ?array $languages = null): ?self`, `findOrCreate(...)`, and let
  `getByKeyLocalized()` handle the key fallback. Keep `getByKey()` as a deprecated wrapper.
- The `translations_` prefix is hard-coded in `Dao.php:157`, `:161`, `:183` and `Listing.php:72`; use `TABLE_PREFIX`.

### [ ] I-54 – Runtime caching through a service instead of `RuntimeCache` + statics

- The per-request translation cache (`RuntimeCache`, B-107), the save queue (`TranslationEntriesDumper::$translations`,
  static) and the Translator's `$initializedCatalogues` (B-124) are three separate pieces of process state with no reset.
  Put them in one `TranslationRepository` service that implements `ResetInterface` (`kernel.reset`). It clears the
  cache on `save()` / `delete()` and returns immutable values or clones.
- Make the dumper queue an instance property of the (shared) dumper service instead of a static array.

### [ ] I-55 – `Dao::save()` simplification

- One `INSERT … VALUES (…), (…) ON DUPLICATE KEY UPDATE text = VALUES(text), …` for all **changed** languages, inside
  `$this->db->transactional()`. Do not update `creationDate` / `userOwner` (B-109). Track dirty languages in the model
  (`addTranslation()` marks the language dirty).
- Move the editor permission check (C-20, B-111) out of the DAO into the model or a security voter, and throw on
  violation. The DAO should not call `User::getById()`.
- Move the HTML sanitising into the model, **before** `PRE_SAVE`, so listeners see the final text. Today the DAO
  mutates the model in the middle of the save (`Dao.php:107-110`), and only for texts that contain tags.
- `Logger::warning(sprintf(...))` with `@phpstan-ignore-line` → PSR-3 placeholders and a typed `$user`.
- `Helper::upsert()` (insert, catch duplicate, update) costs two round trips per existing row. A real upsert costs one.

### [ ] I-56 – Efficiency

- `isAValidDomain()` runs `SELECT * FROM translations_<domain> LIMIT 1` on **every** call. The Translator calls it for
  every missing key on every request (`Translator.php:240`), `Listing::setDomain()` for every listing. Memoise per
  request (I-54), and use `SELECT 1`.
- `Listing\Dao::load()` runs one `getByKey()` query **per key** (N+1). `getAllTranslations()` already loads everything
  with one query and groups in PHP. Do the same for the page of keys: one query for the keys, one
  `WHERE key IN (:keys) AND language IN (:languages)` for the texts.
- `getCount()` loads the list twice (B-118).
- `cleanup()`: the correlated subqueries are O(n²). Use
  `SELECT key FROM t GROUP BY key HAVING SUM(COALESCE(text, '') <> '') = 0`, then delete in chunks with
  `ArrayParameterType::STRING`.
- CSV import: every `$t->save()` calls `clearDependentCache()` (`Cache::clearTags()`) – 5000 rows = 5000 tag clears plus
  cache stampedes on the frontend. Clear once at the end (the method already does), and skip it inside the loop.
- `getByKeyLocalized()` loads all languages of the key to return one. Pass `[$language]`.
- `Admin::determineCsvDialect()` (`lib/Tool/Admin.php:104-106`) calls `file($file)` **ten times** to build the sample
  (helper outside scope, called by the import).

### [ ] I-57 – CSV import as a service

- Move `importTranslationsFromFile()` (110 lines, 7 nesting levels, `@internal`) to a
  `Pimcore\Translation\Import\CsvTranslationImporter` service. Return a typed result
  (`final readonly class TranslationImportResult { public function __construct(public int $imported, public array $conflicts, public array $skipped) {} }`)
  instead of the untyped `$delta` array.
- Use `league/csv` (already a dependency, `^9.11`, used for `EscapeFormula`) with `Reader::createFromPath()`, header
  offset 0 and `getRecords()`. That streams the file, removes the BOM (`skipInputBOM()`), maps columns to the header,
  and replaces the temp-file copies and the manual `array_slice` / `for` loop.
- PHP 8.4: `mb_trim($value, "\u{FEFF}\"")` instead of the byte-wise `trim()` with an invisible BOM in the source
  (B-116).
- Guard clauses instead of `if (is_readable) { … if (count > 1) { … } else throw } else throw`.
- Use `===` / `!==` (B-116). Validate the header (`key` column required, unknown language columns reported).
- Wrap the import in a transaction or batch it (e.g. 500 keys per transaction) so a failure does not leave half a file
  imported.

### [ ] I-58 – Listing modernisation and DBAL 4 readiness

- Apply the `languages` filter in SQL (`language IN (:languages)`), so count and page agree (B-118).
- `$queryBuilder->executeQuery()->fetchAllAssociative()` instead of
  `fetchAllAssociative($queryBuilder->getSql(), getParameters(), getParameterTypes())` (`Listing/Dao.php:69`, `:104`,
  `:116`).
- `sprintf('SELECT COUNT(*) … FROM (%s) AS a', $queryBuilder)` relies on `QueryBuilder::__toString()`; use
  `getSQL()` explicitly. `QueryBuilderHelperTrait::prepareQueryBuilderForTotalCount()` (shared) uses `getQueryPart()` /
  `resetQueryParts()`, which DBAL 4 removes; the Translation listing does not use it, so keep it that way.
- `Dao::getByKey()` builds two SQL strings for `languages` / no `languages`, but `Translation::getByKey()` always passes
  an array. Drop the `null` branch, and avoid passing an unused `languages` parameter to DBAL.
- `Dao::delete()` passes `quoteIdentifier('key')` as the criteria key. Fine in DBAL 3/4, but document it (or use
  `executeStatement('DELETE FROM … WHERE `key` = ?', [$key])`).
- Remove `$cacheLimit` / `isCacheable()` or use them (B-117).

### [ ] I-59 – Symfony 7.4: dependency injection instead of the service locator

- `Translation::getTranslationSanitizer()` (`Pimcore::getContainer()->get(...)`), `getByKeyLocalized()`
  (`LocaleServiceInterface`, `Admin::getCurrentUser()`, `authenticateSession()`), `Dao::save()` (`User`, `Admin`) all pull
  services from the container. The sanitizer is also stored on the model and ends up in the cache (B-117).
- With the service from I-54, inject `HtmlSanitizerInterface` (`#[Autowire(service: Text::PIMCORE_TRANSLATION_SANITIZER_ID)]`),
  `LocaleServiceInterface`, `Security` and the DBAL connection. The model stays a plain data object; keep the static
  methods as thin deprecated wrappers.
- `Translator` / dumper: implement `ResetInterface` (B-124, B-115).

### [ ] I-60 – Docblocks and readability

- Empty or broken docblocks: `Translation.php:204-208` (`@internal` after `@return` with an empty line), `:241-245`;
  `Dao.php:45-49`, `:135-138`, `:151-154`, `:170-174`; `Listing.php:24-33`, `:43-46`; `Listing/Dao.php:162-165`.
- Wrong or outdated texts: `Dao::getAvailableLanguages()` "a array"; comment "make" → "made" (`Translation.php:73`);
  `TranslationEvent` constructor says "AssetEvent constructor" and mentions a "versionNote"
  (`lib/Event/Model/TranslationEvent.php:30-34`); "Imports translations from a csv file … an Pimcore".
- `Translation::` and `self::` are mixed inside the class (`Translation.php:96`, `:260`, `:392`, `:444`, `:487`). Use
  `self::`. `$this->model::getRegisteredDomains()` (`Dao.php:178`) → `Translation::getRegisteredDomains()`.
- One-letter / cryptic names: `$t`, `$rd`, `$l`, `$d`, `$transObj`, `$idOriginal`.
- `Dao::getByKey()`: `if (!empty($data)) { … } else { throw }` → guard clause; set key-level fields once, outside the
  loop (B-109).
- `getRegisteredDomains()` indexes `['domains']` without a fallback.
- `importTranslationsFromFile()` takes an untyped `stdClass $dialect`. Use a small readonly `CsvDialect` value object.

### [ ] I-61 – Events and model lifecycle

- `save()` and `delete()` clear the cache at different points relative to the `POST_*` event (B-113). Use one order.
- Add events (or one bulk event) for `Listing::cleanup()` and the import (B-122), so integrations can react.
- `PRE_SAVE` listeners cannot cancel cleanly. Document "throw to cancel", or add a `stopPropagation`-based veto.
- Keep the recursion guard state (`RecursionBlockingEventDispatchHelperTrait`) out of serialised model data (`__serialize()`
  or a `WeakMap` in the dispatcher helper), see B-117 / B-125.

### [ ] I-62 – Schema and table lifecycle

- One schema definition for domain tables, used by the installer, the migrations and the lazy creation (today
  `install.sql:432-444` and `Dao.php:199-210` differ). Create tables for **all configured domains** (incl. `admin`) at
  install time, in a migration, and through a console command (`pimcore:translations:create-domain-tables`); remove DDL
  from `save()` (B-112, B-120).
- `text` nullable → after `UPDATE … SET text = '' WHERE text IS NULL`, make it `NOT NULL` (C-19). `creationDate` /
  `modificationDate` likewise (B-109).
- `language` uses the table's default collation (case-insensitive) while `key` is `utf8mb4_bin`. `de` and `DE` are the
  same PK. Use `ascii_bin` or `utf8mb4_bin` for `language`.
- `type varchar(10)` → `ENUM` or a check constraint that matches `TranslationType` (I-52).

### [ ] I-63 – Tests

`tests/Unit/Translation/TranslatorTest.php` covers basic translation, parameters, plurals, case sensitivity, a list load,
cache invalidation on save and sanitising. There is no model-level test for `Translation`, its DAO or the listing.
Add cases for:
- `getByKeyLocalized(…, create: true, returnIdIfEmpty: true)` followed by `TranslationEntriesDumper::dumpToDb()` – the stored
  text must be `''` (C-18);
- a row with `text = NULL` / `creationDate = NULL` in the catalogue build, `getByKey()` and `getAllTranslations()` (C-19, B-109);
- `save()` as a user with view-only languages (C-20) and with restricted edit languages (B-111);
- a domain value that is not registered / contains a backtick (C-21);
- auto-create for a key that exists in other languages: the other languages must be unchanged (C-22);
- `getByKey()` with and without `$returnIdIfEmpty` in the same request, and after `delete()` (B-107, B-113);
- the same key missing in two domains in one request (B-115);
- import: missing `key` column, `"1"` → `"1.0"`, `"0"`, two imports with different files (B-116);
- two listings with the same `setCondition()` SQL and different values, and with different `languages` (B-117);
- `count()` before and after `load()`, and `setOrderKey('creationDate')` (B-118, B-119);
- `getAvailableLanguages()` under `ONLY_FULL_GROUP_BY` (B-121); `cleanup()` followed by a cached listing (B-122);
- a `PRE_SAVE` listener that throws once, followed by a second save (B-125).

---

## I – Site (Scope 5)

### [ ] I-64 – Strictness and native types (PHP 8.4)

- Add `declare(strict_types=1)` to `models/Site/Dao.php` and `Site/Listing/Dao.php`. Before that, make the hydration
  casts explicit (`(int)` ids/dates, `(bool)` `redirectToMainDomain`), because hydration relies on the non-strict
  `AbstractModel::setValue()` today.
- Fluent API is inconsistent: `setId()`, `setDomains()`, `setRootId()`, … return `Site` (`@return $this`), while
  `setErrorDocument()`, `setMainDomain()`, `setRedirectToMainDomain()` return `void`. Use `: static` everywhere.
- `Site` is `final`: `protected static $currentSite` and the `protected` properties can be `private`.
- Precise array types: `@var list<string>` for `$domains`, `@var array<string, string>` (locale → path/id) for
  `$localizedErrorDocuments`; `Listing::getSites()` → `@return list<Site>` and `?? []` (`getData()` is nullable).
- `setCreationDate(int)` / `setModificationDate(int)` vs `?int` getters: pick one (non-nullable with `0` default matches
  the schema).
- PHP 8.4 asymmetric visibility fits the read-mostly fields: `public private(set) ?int $id`, and a property hook on
  `$domains` / `$mainDomain` can hold the normalisation from B-128 instead of setter logic.
- `getBy(mixed $mixed)` → `getBy(int|string|Site $site)` (B-127).

### [ ] I-65 – Storage format and DBAL 4 readiness

- `domains` and `localizedErrorDocuments` are PHP-`serialize()`d text. Use JSON columns (`json_encode` / `json_decode`
  with `JSON_THROW_ON_ERROR`) in a migration. Until then, `unserialize(..., ['allowed_classes' => false])` everywhere
  (`Site/Dao.php:70` calls raw `unserialize()`, `Serialize::unserialize()` too).
- Better: move domains to a `sites_domains (domain PK, siteId, isWildcard)` table. That gives an indexed exact lookup,
  a unique constraint across sites (B-130) and a simple wildcard query, and removes the `LIKE` on serialized text.
- `Dao::update()` writes every property that happens to match a column name (`getObjectVars()` + `in_array()`
  filter), including `id`. Use an explicit column map.
- DBAL 4: `lastInsertId()` returns `int|string`; use `Types::*` parameter types for the `IN` / int parameters; avoid
  `SELECT *`.

### [ ] I-66 – Domain resolution as one service

- Extract `getByDomain()` matching into a `SiteDomainResolver` (or `SiteRepository::findByHost()`): normalise input
  (B-128), exact match first, then wildcards ordered by specificity, `str_contains()` instead of `strpos()` as a boolean,
  no regex rebuilding per request (compile once and cache), resolve the `@TODO: refactor this to be more clear`
  (`Site/Dao.php:65`).
- Use it from `RoutingListener`, `PublicServicesController`, `RedirectHandler`, `RedirectsController` and
  `Document\Service::getByUrl()` (`models/Document/Service.php:404-421`, which duplicates the domain check and
  concatenates a `Document` object into a path string).
- Fix the exception text `` `…´ `` (acute accent instead of a backtick, `Site/Dao.php:91`).

### [ ] I-67 – Caching: one site registry instead of `RuntimeCache` + per-key entries + a sentinel string

- `getById()` and `getByDomain()` duplicate the same 20 lines; the `RuntimeCache::set()` after a runtime hit is
  redundant; the `'failed'` string sentinel mixes types in one variable.
- The `sites` table is tiny. Load **all** sites once into one cached registry (id → data, domain → id, root path → id),
  tagged `site` + the root documents' tags, reset via `ResetInterface` / `kernel.reset`. This replaces
  `site_id_*`, `site_domain_*`, `sites_path_mapping` (`lib/Tool/Frontend.php:70-95`) and the uncached
  `getByRootId()` (called twice per page node by `models/Element/AdminStyle.php:87`, `:101`, each a query plus a root
  page load), and fixes C-23, B-131, B-132 in one place.
- Do not cache objects with embedded documents (C-23); cache plain data and hydrate.
- `Listing\Dao::load()` is N+1 (`SELECT id` then `getById()` per row, each loading the root page on a cold cache). Fetch
  the rows in one query and hydrate them, with the root page loaded lazily.

### [ ] I-68 – Current site: request state instead of a static

- Deprecate `Site::setCurrentSite()` / `getCurrentSite()` / `isSiteRequest()` with PHP 8.4 `#[\Deprecated]` in favour
  of `Pimcore\Http\Request\Resolver\SiteResolver` (request attribute), as CoreBundle TODO-3 already suggests for the
  listeners. For CLI rendering, pass the site explicitly (B-126).
- `getCurrentSite()` throws a generic `\Exception` for a normal condition; callers wrap it in `try/catch` and check
  `instanceof Site` on a non-nullable return. Offer `?Site` (`findCurrentSite()`) or a dedicated exception.
- `isSiteRequest()` → `return self::$currentSite !== null;`.

### [ ] I-69 – Validation and lifecycle

- One `validate()` called by `save()` before `PRE_SAVE`: root id set and a `Document\Page`, root not used by another
  site, domains valid and unique across sites, main domain valid (and optionally part of the domains), error documents
  exist (B-129, B-130, B-133, B-134, B-136). Throw a `ValidationException`, not `InvalidArgumentException` from setters
  during hydration.
- Wrap `Dao::save()` / `delete()` in a transaction; dispatch `PRE_ADD` / `POST_ADD` for new sites (B-133).
- Error documents by id and part of the dependency graph (B-136).
- `create(array $data)` returns an unsaved site; name it clearly or add `createAndSave()`; document that `domains`
  must be a list of strings.
- Event dispatch uses `RecursionBlockingEventDispatchHelperTrait` without `try/finally` (shared helper, see B-125).

### [ ] I-70 – Readability and dead code

- `setDomains()` uses `array_map()` for its side effect (throwing). Use `foreach`, or PHP 8.4
  `array_any($domains, fn (string $d): bool => !self::isValidDomain($d))` and one shared `isValidDomain()` for
  `setDomains()` and `setMainDomain()` (the `idn_to_ascii()` + `FILTER_VALIDATE_DOMAIN` logic is duplicated, and the
  `'anystring'` placeholder trick deserves a named constant/comment).
- `array_filter($domains)` keeps the keys → a non-list array is serialized; add `array_values()`.
- Remove or deprecate `$rootPath` / `setRootPath()` (no caller in the repo, never persisted, B-137).
- `getRootDocument()` returns a possibly stale embedded object; with lazy loading (C-23) the `$rootDocument` property
  can go.
- Docblocks: `Listing\Dao::load()` "Loads a list of thumanils for the specicifies parameters, returns an array of
  Thumbnail elements"; `@method Model\Site|false current()` on the listing; `Dao` comments ("it's a good idea to use
  save() instead") on `@internal` methods; empty `/** * @throws */` blocks. `Site::clearDependentCache()` comment "this
  is mostly called in Site\Dao not here" describes nothing useful.
- `getById()` / `getByDomain()` declare `@throws Exception` but only rethrow DB errors; document the real contract.

### [ ] I-71 – Schema

- `redirectToMainDomain tinyint(1) DEFAULT NULL` → `NOT NULL DEFAULT 0`; `mainDomain` / `errorDocument` `NULL` vs `''`
  (the model writes `''`, the schema defaults to `NULL`): pick one (`NOT NULL DEFAULT ''` matches the model).
- `creationDate` / `modificationDate` are `int(11) unsigned` (2106 limit); fine, but align with the other models
  (`bigint` / `datetime` if those move).
- Index (or unique) on `mainDomain`, or the domain table from I-65.
- Reconsider `fk_sites_documents … ON DELETE CASCADE` (C-24).
- `domains` / `localizedErrorDocuments` `text` → `json` (I-65).

### [ ] I-72 – Tests

There is no model test for `Site`; `tests/Model/Tool/FrontendTest.php` only covers `isDocumentInSite()`. Add cases for:
- renaming / moving the root page, then `getByDomain()` + `getRootPath()` with a warm persistent cache (C-23);
- deleting the root page, then `getByDomain()`, slugs and recycle-bin restore (C-24);
- rendering two documents of different sites in one CLI process (B-126);
- `getBy('5')`, `getBy(5)`, `getBy('example.com')` (B-127);
- `bücher.de`, `Example.com`, `*.Example.com` against lower-case / punycode hosts (B-128);
- loading a row with an underscore domain and with corrupt serialized data (B-129);
- `exampl_.com`, an empty host, two sites with the same domain, overlapping wildcards (B-130);
- `getById()` before create / after delete in the same process, listing after delete (B-132);
- save with a missing / non-page / already-used root, save of a deleted site (B-133, B-134);
- delete with dependent website settings and slugs (B-135).

---

## J – Document / Element / DataObject (Scope 6)

### [ ] I-73 – Strictness: `declare(strict_types=1)` in the 65 files without it

- Missing in almost every DAO of the scope (`Document/Dao.php`, all `Document/*/Dao.php`, `Element/Dao.php`,
  `Element/*/Dao.php`, `DataObject/AbstractObject/Dao.php`, `Concrete/Dao.php`, `Localizedfield/Dao.php`,
  `Fieldcollection/**/Dao.php`, `Objectbrick/**/Dao.php`, `Classificationstore/**/Dao.php`,
  `ClassDefinition/**/Dao.php`, `QuantityValue/Unit/**/Dao.php`, `Listing/**/Dao.php`, `Data/*Metadata/Dao.php`) and
  in `Document/Editable/Areablock.php`, `DataObject/ClassDefinition/Data.php`, `ClassDefinition/Data/Input.php`,
  `ClassDefinition/Data/Consent.php`, `ClassDefinition/Data/ClassSavedInterface.php`, `QuantityValue/Unit.php`
  (`grep -rL 'strict_types' …`).
- The DAOs rely on coercion during hydration (`assignVariablesToModel()` with DB strings, `lastInsertId()` as string,
  `(bool)` only for `published`). Add explicit casts first (ids, dates, `index`, `versionCount`, booleans), then enable
  strict types per directory.
- Strict editables and data types currently fail on legacy data (B-163); normalise in the setters before tightening.

### [ ] I-74 – PHP 8.4 language features and typing

- Untyped / loosely typed members: `ClassDefinition\Dao::$model`, `Editable::admin()` / several `frontend()`
  implementations returning `string|void`, `Areablock::start()/end()` returning `$this|string|void`,
  `Renderlet::frontend()`, `Document\Service\Dao::getTranslationSourceId(): mixed`, `Note`/`Tag`/`Editlock` setters
  returning the concrete class instead of `static`.
- `func_get_args()` as cache key (`Document::getChildren()`, `getSiblings()`, `AbstractObject::getChildren()`,
  `Hardlink::getChildren()`) and as optional argument (`Video::frontend()` `$inAdmin`) → explicit parameters.
- Enums / constants for string literals: element type (`document`/`asset`/`object`), document type, object type
  (`folder`/`object`/`variant` constants exist, string literals still used), link type (`internal`/`direct`), internal
  type, tree lock (`self`/`propagate`), note data types, video types, relation owner types, `ROOT_ID = 1` (magic `1`
  in ~20 places).
- `#[\Override]` on all overridden model methods (`save()`, `update()`, `delete()`, `getCacheTags()`,
  `resolveDependencies()`, Dao `getById()` overrides with different nullability).
- `#[\Deprecated]` (PHP 8.4) for `Recyclebin\Item::getStoreageFile()`, the `contentMasterDocumentId` BC alias in
  `PageSnippet`, `DataObject\Service::getInheritedData()`, `gridObjectData()` / `gridElementData()`.
- Small idioms: `(bool)strpos(…)` → `str_contains()` (`Recyclebin\Item.php:197`), `@usort` (`Tag\Dao.php:130`),
  nested `if/else` type dispatch → `match` (`PermissionChecker::check()`, `Tag\Dao::getElementsForTag()` map,
  `Note\Dao`), `array_any()` / `array_find()` where loops search (`updateChildren()`, `Relations::setElements()`).
- PHP 8.4 asymmetric visibility for read-mostly ids (`public private(set) ?int $id`) on `Note`, `Tag`, `Editlock`,
  `Recyclebin\Item`, `WorkflowState`.

### [ ] I-75 – Serialization formats and object injection hardening

- `documents_editables.data`, `properties.data`, `notes_data`, `schedule_tasks.data`, recycle-bin and
  `Hardlink\Service::upperCastDocument()` use PHP `serialize()`. Editable data are arrays only → `unserialize(…,
  ['allowed_classes' => false])` in `Editable::getUnserializedData()`; same for `ClassDefinition\Data\*`
  `getDataFromResource()` (Table, Geo*, Link, Video, ImageGallery, Hotspotimage, Block) where possible; JSON for new
  rows.
- `Hardlink\Service::upperCastDocument()` rewrites the class name inside a serialized string → replace with
  composition (wrapper holding the source document) or an explicit copy constructor.
- `ClassDefinition\Data\Block::getDataFromResource()` repairs string lengths in serialized data with
  `preg_replace_callback()` → migrate the stored format.
- `Localizedfield::__sleep()`, `Data\EncryptedField::__sleep()`, `AbstractElement::__sleep()` mutate the instance
  (B-176, B-184) → `__serialize()` / `__unserialize()`.

### [ ] I-76 – Bound parameters and DBAL 4 readiness

- Concatenated SQL to replace with parameters / `ArrayParameterType::INTEGER`: `Document\Dao` (`hasChildren()`,
  `getChildAmount()`, `isLocked()`, `unlockPropagate()`, `isAllowed()`, `getProperties()`), `Element\Dao`
  (`InheritingPermission()`, `permissionByTypes()`), `AbstractObject\Dao` (object types `IN ('…')`, permission
  queries, `getChildPermissions()`), `ScheduledTasksDaoTrait::deleteAllTasks()`, `Tag\Dao` (`delete()`,
  `batchAssignTagsToElement()`, `getElementsForTag()`), `Element\Service::filterUnpublishedAdvancedElements()` /
  `findForbiddenPaths()`, `PermissionChecker`, `Fieldcollection\Dao`, `Classificationstore\Dao`,
  `Localizedfield\Dao` (`$language`, object id), `InheritanceHelper` (CTE with `$currentParentId`, `$fields`).
- `QueryBuilder::getQueryPart()` and `add()` (`Element\Service::addTreeFilterJoins()`, `:906-934`) are deprecated in
  DBAL 3 and removed in DBAL 4.
- `executeQuery()` used for DML/DDL → `executeStatement()`; `lastInsertId()` returns `int|string`;
  `fetchAssociative()` of `SELECT *` joins with duplicate columns (B-148).
- Generated `filterBy<Field>($data, $operator)` and `DataObject\Listing::addFilterByField()` concatenate the operator
  into SQL → allowlist operators (`=`, `!=`, `<`, `<=`, `>`, `>=`, `LIKE`, `IN (?)`, `NOT IN (?)`, `IS NULL`).

### [ ] I-77 – Worker runtimes: replace static process state

- Static flags with process lifetime: `Document::$hideUnpublished`, `AbstractObject::$hideUnpublished`,
  `$getInheritedValues`, `$disableDirtyDetection`, `PageSnippet::$getInheritedValues`,
  `Localizedfield::$getFallbackValues`, `$strictMode`, `EncryptedField::$strictMode`,
  `AbstractElement::$doNotRestoreKeyAndPath`, `InheritanceHelper::$useRuntimeCache` / `$runtimeCache`,
  `Model\Version::$disabled` (via Recyclebin).
- Introduce one request-scoped context service (`ElementRuntimeContext`, `ResetInterface`, `kernel.reset`) with scoped
  helpers (`withUnpublished(fn)`, `withInheritedValues(fn)`, `withoutDirtyDetection(fn)`) and deprecate the static
  setters (fixes B-175 structurally, see also B-92 / B-125).
- Shared services with per-request caches: `Document\Service::$nearestPathCache` (C-27); RuntimeCache entries of
  tags (`tags_<id>`), class definitions (`class_<id>`), unit table, object-brick definitions (cleared only by
  `MessengerClearRuntimeCacheListener`).

### [ ] I-78 – Shared logic and duplication between the element DAOs and services

- `Document\Dao` and `AbstractObject\Dao` duplicate `hasChildren()` / `getChildAmount()` (permission SQL, ties to I-14),
  `isLocked()`, `unlockPropagate()`, `getProperties()`, `updateChildPaths()` (C-25), `updateWorkspaces()`,
  `isAllowed()`, `getVersionCountForUpdate()`, `__isBasedOnLatestData()` → move to `Element\Dao` with the table /
  ctype as configuration; one permission-SQL builder for all three element types.
- `Document::save()`, `AbstractObject::save()` and `Asset::save()` copy the deadlock retry loop with different
  exception types (`DeadlockException` vs `RetryableException`) and random sources (`rand()` vs `random_int()`) →
  one `TransactionRetry` helper.
- The five document type DAOs (`Page`, `Snippet`, `Email`, `Link`, `Hardlink`) differ only in the table name →
  one generic DAO driven by `type_definitions` (also fixes B-148).
- `Document\Service::copyRecursive()` / `copyAsChild()` and `DataObject\Service::copyRecursive()` / `copyAsChild()` /
  `copy()` share 80 % of their code; `getUniqueKey()` exists three times and issues one query per attempt (recursive)
  → one query `key REGEXP '^<key>(_[0-9]+)?$'`.
- `Area::renderDialogBoxEditables()` and `Areablock::renderDialogBoxEditables()` are near copies; the
  `rewritePath` closures in `Editable\Image` exist three times; the `__sleep()` "blocked vars" loop is copied into
  ~10 editables and documents → helper or `__serialize()`.

### [ ] I-79 – `Document.php` and `Document\Service` readability

- `getFullPath()` (`Document.php:692-788`, "dirtiest hack" comments) mixes site rewriting, hardlink path resolution and
  cross-domain links → extract a `DocumentPathResolver` service (ties to I-66), keep `getFullPath()` a thin wrapper.
- `correctPath()` / `update()` validation (key, parent, first-level keys, path length) → one `validate()` called before
  the transaction; reuse for `AbstractObject`.
- `getTypes()` / `getTypesConfiguration()` read the system configuration on every call.
- `Document\Service` mixes rendering, copying, translations (via `__call` to the DAO), URL lookup, preview generation
  and nearest-path routing → split; `generatePagePreview()` uses a fixed temp file name per document (`:548`) and does
  not delete it on failure.
- `Document\Listing::getCount()` / `DataObject\Listing\Dao::getCount()` load the whole id list to count → `COUNT(*)`.

### [ ] I-80 – Editable architecture

- Editables pull services from `Pimcore::getContainer()` in ~20 places (`EditableHandler`, `BlockStateStack`,
  `AreabrickManagerInterface`, `EditableRenderer`, `FullPageCacheListener`, `OutputTimestampResolver`,
  `DocumentTargetingConfigurator`, `RequestHelper`) → inject through the `EditableLoader` / a factory.
- Output via `echo` / `outputEditmode()` (`Block`, `Areablock::content()`, `Area::frontend()`) → return strings, let
  the renderer echo; removes the need for `ob_start()` in `Block::getIterator()`.
- `Editable\Video` (1076 lines) → one renderer per provider with Twig templates; the `'url'` branch in `frontend()` and
  `getUrlCode()` are dead (`'url'` is not in `ALLOWED_TYPES`).
- Non-deterministic markup: `Pdf::frontend()` uses `uniqid()` ids (breaks full-page-cache diffs / tests).
- `Embed::frontend()` caches by `crc32(serialize(...))` → collisions serve another URL's embed → `xxh128`.
- Side effects in getters: `Document\Link::getHref()` writes `$this->href`; `Editable\Image::getConfig()` writes
  `$this->config`; `Editable\Link::getData()` mutates `$this->data`.
- `Editable::buildEditableName()` dispatches an event and reads the container per editable render → cache the block
  state and dispatcher.

### [ ] I-81 – `Element\**` clean-up

- `Element\Service` (1424 lines) → split into path utilities, copy/clone, session storage, CSV escaping, permission
  paths; `cloneMe()` creates an anonymous `Matcher` class per call.
- N+1 loads in listings: `Tag\Listing\Dao`, `Note\Listing\Dao`, `Recyclebin\Item\Listing\Dao`,
  `WorkflowState\Listing\Dao` (`SELECT id` then `getById()` per row), `Tag\Dao::getTagsForElement()`,
  `Tag\Dao::getByPath()` (one query per segment), `Tag\Dao::getElementsForTag()` (`getById()` per row).
- `PermissionChecker`: one query per column per user, `createDetail()` with positional `a`…`f` parameters and HTML
  (`<b>`) in data → a DTO and markup in the UI.
- `Editlock::lock()` should upsert by `(cid, ctype)` (B-169); `Recyclebin::flush()` deletes the DB rows before the
  storage (`Recyclebin.php:29-33`) – reverse order, or a transactional outbox.
- `AdminStyle` builds HTML (`'<br>' . …`) in model code and calls `Site::getByRootId()` twice per page node (I-67).
- `ValidationException::getAggregatedMessage()` uses only `contextStack[0]`.

### [ ] I-82 – `DataObject\**` clean-up

- `DataObject\Service` (1973 lines) → split (grid / CSV export, layouts, copy, calculated values, permissions).
- `evaluateExpression()` creates a new `ExpressionLanguage` per calculated value (`Service.php:1440-1451`) → one cached
  instance (compiled expressions).
- `Localizedfield`: `getFieldDefinition()`, `getFieldDefinitions()` and `setLocalizedValue()` resolve the container
  definition three different ways → one resolver; `Localizedfield\Dao::save()` is a ~350-line method.
- `AbstractRelations::calculateDelta()` inserts rows for field collections – a "calculate" method with side effects.
- `ObjectMetadata\Dao::load()` / `ElementMetadata\Dao::load()` run one query per relation item (N+1).
- `Concrete::save()` disables dirty detection for every object older than the last class save
  (`Concrete.php:619-626`) → every save after any class edit writes all fields; store a "schema version" instead.
- `Classificationstore\Dao::save()` deletes and re-inserts the whole store on every dirty save.
- QuantityValue unit table cache (`QuantityValue/Service.php:98-128`) has no tags → `Cache::clearTags()` never clears
  it.

### [ ] I-83 – Class / brick / collection definitions and code generation

- Generated PHP code is built by string concatenation in `ClassDefinition\Data::getGetterCode()` / `getSetterCode()`
  and the class builders → a code generator (e.g. `nette/php-generator`) that escapes identifiers (C-32).
- Definitions are written with `var_export()` and loaded with `@include` (`ClassDefinition.php:238`, `:1218`) → load
  errors are silent (B-185); use `Symfony\Component\VarExporter` and explicit error handling.
- `@unlink()` (`ClassDefinition.php:555-558`, `Fieldcollection\Definition.php:239-240`,
  `Objectbrick\Definition.php:329`, `:486-487`, `SelectOptions\Config\Dao.php:141`) → `Filesystem::remove()` with
  logging.
- DDL is concatenated with backticks (`ClassDefinition\Dao`, `Helper\Dao`, `Localizedfield\Dao::createUpdateTable()`)
  → `quoteIdentifier()`; `SHOW TABLES LIKE` → explicit names (C-33).
- `ClassDefinition::getById()` uses exceptions for control flow (`throw new Exception('Forced load')`).

### [ ] I-84 – Schema

- `edit_lock`: unique `(cid, ctype)`, `ctype` `NOT NULL`, `sessionId` index (B-169).
- `documents.type` is an `enum('page','link','snippet','folder','hardlink','email')` while document types are
  configurable (`type_definitions`) → `varchar` + validation.
- `documents_page.prettyUrl`: index (and uniqueness per site, B-153).
- `documents_translations`: no FK / index on `sourceId` for the "source deleted" cleanup.
- `tags.idPath varchar(190)` limits the tree depth (~20 levels with 8-digit ids); `recyclebin.amount int(3)`;
  `recyclebin.deletedby varchar(50)` vs user name length.
- `notes.date` / `notes.user` nullable while the model types them as `int`.

### [ ] I-85 – Tests

Add model tests for:
- renaming / moving a document and an object whose path contains the old segment twice; properties of other element
  types below the same path (C-25); `getByPath()` of old paths after a move in the same process (B-138);
- an `\Error` thrown inside `Document::save()` / `DataObject::save()` followed by a second save in the same process
  (C-26, B-175);
- `getNearestDocumentByPath()` for two different paths on the same service instance (C-27);
- a scheduled block whose entries are all in the past, with and without full-page cache (C-28, B-156);
- moving a root tag with children and grandchildren, then renaming a child and deleting the root (C-29, B-171);
- loading and saving an object with an encrypted field under a wrong key (C-30, B-184);
- slug resolution for a mixed-type field collection, and with an unloadable class definition (C-31);
- class save with names / ids / field names containing `-`, `../`, spaces, backticks (C-32);
- deleting class `archive` while class `news_archive` with bricks and localized fields exists (C-33);
- recursive delete where a grandchild fails (B-139); `missingRequiredEditable` publish via API (B-140);
  inherited-editables save (B-141); content-main cycle (B-142); unknown editable type in DB (B-143);
- link document `getHtml()` with parameters / anchor / no name (B-145); hardlink to itself / deleted source (B-147);
- image editable hotspots with element links → dependencies (B-162); legacy image data types (B-163);
- unsaved elements under different parents reading properties (B-165); workspace `/foo` vs `/foobar` for documents and
  objects (B-167);
- recycle-bin restore without publish permission (B-168); two locks for one element (B-169); note `POST_ADD`
  (B-170); workflow state delete with two workflows (B-172);
- `copyContents()` scheduled tasks (B-182); user referenced in one of two user fields (B-183); listing
  `count()` then `setLocale()` (B-178).

---

## K – Metadata / Notification (Scope 7)

### [ ] I-86 – Strictness and native types (PHP 8.4)

- Add `declare(strict_types=1)` to `Metadata/Predefined/Dao.php`, `Predefined/Listing/Dao.php`, `Notification/Dao.php`
  and `Notification/Listing/Dao.php`. Prerequisites that rely on coercion today:
  - legacy predefined definitions migrated from `predefined-asset-metadata.php` have numeric ids; `array_keys()` in
    `fetchAllKeys()` returns them as `int`, and `Predefined::getById(string)` is called with them in
    `Listing\Dao::loadList()` → cast to `string` first;
  - `User::getById($data['sender'])` / `Service::getElementById(…, $data['linkedElement'])` → `(int)` casts.
- `Predefined` setters declare `: Predefined`; use `: static` like the rest of the codebase. `Listing::getByTargetType():
  ?array` is never `null` → `array`, `@return list<Predefined>`; `$subTypes` → `string|list<string>|null`.
- Notification: `public private(set) ?int $id` (PHP 8.4 asymmetric visibility) instead of a public `setId()` used only by
  the DAO; `creationDate` / `modificationDate` → `?\DateTimeImmutable` (B-194); `type` → a backed enum with the values
  the UI supports (column `VARCHAR(20)`, default `info`); `linkedElementType` → the existing element-type strings as an
  enum; the `@internal` tags on the properties of a public model are misleading → remove.
- Constructor property promotion + `readonly` in `NotificationService`, `UserService`,
  `NotificationServiceFilterParser` (and `PimcoreNotificationService`).
- `findAll()` string `offset` / `limit`: replace the two `//TODO: Trigger deprecation` comments
  (`NotificationService.php:207-214`) with `trigger_deprecation('pimcore/pimcore', …)`.
- `isStudio` ("Remove with end of Classic-UI"): mark `isStudio()` / `setIsStudio()` with PHP 8.4 `#[\Deprecated]` so
  the removal is tracked by tooling.
- Strict comparisons everywhere (`!=` / `==` in `Predefined/Dao.php:52-82`, `Listing.php:109-117`, `:255-263`,
  parser `:109`).

### [ ] I-87 – `Metadata\Predefined` clean-up

- Three lookups with different semantics: `Predefined::getByName()` → `Dao::getByNameAndLanguage()`,
  `Listing::getByKeyAndLanguage()` and `getByTargetType()`. Build **one** index (`name|language|subtype` → definition)
  per listing load and use PHP 8.4 `array_find()` / `array_filter()` on it (B-190).
- Every lookup loads and hydrates the whole listing. `Asset\Service::expandMetadataForEditmode()` calls `getByName()`
  per metadata item → O(items × definitions) per asset. Cache the index per request (reset via `kernel.reset`).
- `minimize()` / `expand()` duplicate the loader lookup and use the service locator
  (`Pimcore::getContainer()->get(...)`). Move them into a small service with the implementation loader injected and
  explicit `toStorage()` / `toEditmode()` methods (B-189).
- `Predefined::__clone()` keeps and rebinds the DAO while `AbstractModel::__clone()` drops it. Pick one behaviour and
  document it.
- `Predefined\Listing\Dao` extends the **model** DAO and inherits `save()`, `delete()`, `getById()`,
  `getByNameAndLanguage()`, which operate on a `Listing` model (`$listing->save()` → `Error` via `__call()`). Use
  composition or a shared base for `configure()` only. Same pattern in `Property\Predefined\Listing\Dao`.
- `Listing::getTotalCount()` (via the DAO) reloads and filters the whole list and overwrites `definitions` as a side
  effect.
- `Dao::save()`: `$allowedProperties` + `in_array()` loop → `array_intersect_key($vars,
  array_flip(self::PERSISTED_FIELDS))`.
- `getByTargetType()` throws a generic `Exception` for every type except `asset`. Use `InvalidArgumentException`, or
  drop the parameter (only assets are supported).
- Config tree (`Configuration.php:667-695`): remove the unused `inheritable` node, make `data` match the model (B-192).
- Docblocks: "specicified" (`Listing/Dao.php:30`); `Dao::delete()` "Deletes object from database" (it is a config
  entry); `@method void save()` / `delete()` on the model hide the DAO contract → real methods with events
  (`PRE_SAVE` / `POST_SAVE` like other config models).
- New ids: `Uuid::v4()` → `Uuid::v7()` (time-ordered, Symfony 7.4) is optional.

### [ ] I-88 – Notification service API and layering

- Replace `AccessDeniedHttpException` (HttpKernel) in the model service with a domain exception that the controllers map
  to 403, and `UnexpectedValueException` for "not found" with `NotFoundException`.
- Validation lives in three places (service `sendToUser()`, DAO hydration, nowhere in `save()`). Move it into
  `Notification::validate()`, called by `save()` (B-193).
- Replace `array<string, mixed>` filters with raw `condition` strings by a `NotificationFilter` value object (recipient,
  read, type, title contains, date range) that the listing DAO turns into SQL with the QueryBuilder. This removes the
  C-35 / B-195 class of problems.
- Parser: take an `array` / `InputBag` instead of the whole `Request`; `getString()`; `json_decode(…,
  flags: JSON_THROW_ON_ERROR)`; `match` instead of `switch`; the `KEY_*` / `TYPE_*` / `OPERATOR_*` constants → backed
  enums; return the filter object directly.
- `findAll()` returns `array{total, data}` → a small result DTO (or `Countable` + `IteratorAggregate`).
- Events are dispatched through `RecursionBlockingEventDispatchHelperTrait` → `Pimcore::getEventDispatcher()`; inject
  the dispatcher into the service and dispatch post events after commit (B-198).
- `NotificationService` is `public: true` only for `NotificationServiceTest` (`services.yaml:185-186`); the test container
  can access private services → make it private.
- Carbon is used only for parsing and `getTimestamp()` → native `DateTimeImmutable`.
- The class comment "for Studio Bundle you have to use the service bundled there" plus `isStudio = 0` hard-coded in
  three queries → one constant / scope method until the Classic-UI removal.

### [ ] I-89 – Notification persistence and performance

- `Listing\Dao::load()` is N+1: `SELECT id`, then `getById()` per row, and each hydration loads the sender, the recipient
  and the **full linked element** (a data object with all fields). A page of 40 notifications costs 120+ queries plus 40
  element loads, although `format()` only needs the element id. Fetch the rows in one query, hydrate directly, and load
  the linked element lazily (store id + type, resolve in `getLinkedElement()`).
- `count()` and `getTotalCount()` are duplicates; `findLastUnread()` runs `count()` + `load()` for one row.
- `deleteAll()` loads and deletes rows one by one. Use one `DELETE` (document that per-row events are not fired, or fire
  a bulk event); add a bulk "mark all as read".
- `Listing::isValidOrderKey()` is identical to the parent method → remove, or restrict it to real columns.
- `Notification::getById()`: `try { RuntimeCache::get() } catch (Exception)` → `RuntimeCache::isRegistered()`.
- `Helper::upsert()` for an auto-increment model → explicit `insert()` / `update()` (B-198).
- DBAL 4: `lastInsertId()` already cast; avoid `SELECT *` in `Dao::getById()`; bind `read` / `isStudio` as
  `ParameterType::INTEGER`.

### [ ] I-90 – Notification schema

- No FK: `recipient` → `users.id ON DELETE CASCADE`, `sender` → `ON DELETE SET NULL` (B-193), or clean up in
  `User::delete()`.
- Only `recipient` is indexed. The unread polling (`countAllUnread()`, `findLastUnread()`) runs every few seconds per
  open admin; add `(recipient, read, isStudio, creationDate)`.
- `TIMESTAMP` → `DATETIME` storing UTC (2038 limit, session-timezone conversion, B-194).
- Schema drift: migration `Version20240813085200` adds `isStudio` as nullable `TINYINT DEFAULT 0` while `install.sql`
  has `NOT NULL`; `payload` is declared as `text` with `columnDefinition LONGTEXT`. Align with a migration.
- `payload LONGTEXT` holds free-form strings → `JSON` column with validated encoding.
- No retention: nothing purges old or read notifications, so the table grows without bound. Add a configurable
  maintenance task.

### [ ] I-91 – `Notification\Service\UserService`

- Role ids are concatenated into SQL (`UserService.php:38`; ints, safe today). Use `FIND_IN_SET(?, roles)` with bound
  values. The same `roles = ? OR LIKE … OR LIKE … OR LIKE …` pattern in `NotificationService::sendToGroup()`
  (`:105-122`) → `FIND_IN_SET(?, roles)` through one shared helper.
- `findAll()` returns users **and** roles merged into one untyped array → `findRecipientUsers(): list<User>` and
  `findRecipientRoles(): list<User\Role>`.
- `filterUsersWithPermission()` → `array_filter()` with `list<User>` types; the permission name `'notifications'` →
  a constant.

### [ ] I-92 – Tests

- Fix B-200 (`assertSame()`, a group with the permission).
- C-34: after every failing call (`sendToUser()` with an unknown user, `findAndMarkAsRead()` with a foreign or unknown
  id, `delete()` with an unknown id) assert `Db::get()->getTransactionNestingLevel() === 0`; a workflow transition whose
  notification fails must still persist the element.
- C-35 / B-196: the injection payload from C-35 returns 400 or only the caller's notifications; invalid JSON, missing
  keys, unknown operator; `gt` / `lt` / `eq` day boundaries.
- B-195: `findAll(['read' => 0])`; a condition with `OR`. B-193: listing with an empty-title row and a deleted recipient.
  B-194: `format()` under a non-UTC PHP timezone. B-198: `delete()` then `getById()`.
- `Metadata\Predefined` has no tests. Add: listing with a stale settings-store id (B-188); `minimize()` / `expand()`
  twice for the asset, document, object and date types (B-189); `getByName()` with `~` and with per-language
  definitions (B-190); save with a `disabled` / read-only write target (B-191); legacy numeric ids under `strict_types`
  (I-86).

---

## L – Property / Schedule (Scope 8)

### [ ] I-93 – Strictness, native types and enums (PHP 8.4)

- Add `declare(strict_types=1)` to `Property/Dao.php`, `Property/Predefined/Dao.php`, `Property/Predefined/Listing/Dao.php`,
  `Schedule/Task/Dao.php` and `Schedule/Task/Listing/Dao.php`. Prerequisites that rely on coercion today:
  - `fetchFirstColumn()` ids in `Task\Listing\Dao::load()` → `(int)` before `Task::getById(int)`;
  - legacy numeric predefined-property ids from `array_keys()` → `(string)` before `Predefined::getById(string)` (same
    as I-86);
  - `Predefined\Dao::getById(?string $id = null)` / `getByKey(?string $key = null)` → required `string` (B-209).
- Setters typed `: Property` / `: Predefined` (with `@return $this`) → `: static`, as `Schedule\Task` already does.
  `Task::getById(): ?Task`, `Predefined::getById() / getByKey() / create()` → `?static` / `static`.
- Backed enums instead of free strings checked against DB enums:
  `PropertyType` (`text`, `document`, `asset`, `object`, `bool`, `select`, and `date` if kept, B-201) with an
  `isRelation()` method; `ScheduledTaskAction` (`publish`, `unpublish`, `delete`, `publish-version`) with
  `appliesTo(string $elementType)` (B-211); one shared element-type enum for `ctype` (same as I-86 `linkedElementType`).
- `Task::$date` (unix timestamp) → keep the column, add `getDateTime(): ?\DateTimeImmutable`.
- PHP 8.4 asymmetric visibility: `public private(set) ?int $id` on `Schedule\Task` (only the DAO sets it).
- `final class Property` / `final class Predefined` with `protected` members → `private`.
- Strict comparisons: `==` in `Property.php:63`, `:85`, `:87`, `Property/Dao.php:37`, `Predefined/Dao.php:52`, `:58`,
  `:78`, `:86`; `in_array()` without `true` (`Property.php:57`, `:123`, `Predefined/Dao.php:120`,
  `Task/Dao.php:69`).

### [ ] I-94 – `Property` model clean-up

- `in_array($type, ['document', 'asset', 'object'])` appears three times (`setDataFromEditmode()`, `getData()`,
  `Dao::save()`) → `PropertyType::isRelation()` (I-93).
- `bool` branches (`$this->data = false; if (!empty($data)) { $this->data = true; }`) → `(bool)$data` (careful with
  `'0'`, document the intended semantics).
- The "IMPORTANT: the type must be set first" comments in `setDataFromEditmode()` / `setDataFromResource()` describe a
  temporal coupling → named constructors `Property::fromRow(array $row)` and `Property::create(string $name,
  PropertyType $type, mixed $data)`.
- `resolveDependencies()` calls `getData()` three times (each may load the element) → local variable;
  `rewriteIds()` three nested `if`s → early returns.
- `serialize()` has no caller in this repo (it returns the loaded element for relation types, not the id) → verify the
  upstream admin bundle and remove or return the id.
- `getInherited()` / `isInherited()` alias pair; `@method void save()` in the class doc duplicates the real method;
  empty `@return $this` / blank doc-block lines.
- `Dao::save()`: remove the dead `cpath` block (B-205) and the element load per relation (B-204); bind `inheritable` as
  `ParameterType::INTEGER`.

### [ ] I-95 – Property loading and saving are duplicated in three DAOs and three models

- `getProperties()` is implemented three times (`Asset/Dao.php:234-286`, `DataObject/AbstractObject/Dao.php:222-273`,
  `Document/Dao.php:256-315`) with the same hydration, the same `catch (Exception)` and the same "faster than mysql"
  `usort()`. The save loop exists three times too (`Asset.php:795-808`, `DataObject/AbstractObject.php:703-717`,
  `Document.php:485-498`).
- Move both into `Property\Dao`: `loadForElement(int $id, string $ctype, list<int> $parentIds, bool $onlyInherited,
  bool $onlyDirect)` and `replaceForElement(...)`. Use one multi-row insert instead of N upserts, bound `IN (?)` with
  `ArrayParameterType::INTEGER` instead of `implode()`, `ORDER BY CHAR_LENGTH(cpath)` in SQL, and one row
  factory (`Property::fromRow()`, I-94).
- Load only the needed columns (`Asset/Dao` and `Document/Dao` use `SELECT *`; `AbstractObject/Dao` already lists them).

### [ ] I-96 – `Property\Predefined` clean-up

- `getByKey()`: `try { RuntimeCache::get() } catch (Exception)` used as control flow → `RuntimeCache::isRegistered()`;
  better, one per-request index `key → Predefined` built from a single listing load and reset via `kernel.reset`
  (B-208), with PHP 8.4 `array_find()` instead of `array_values(array_filter(...))[0]`.
- `Predefined\Listing\Dao` extends the **model** DAO and inherits `save()`, `delete()`, `getById()`, `getByKey()`
  working on a `Listing` model (same as I-87) → composition or a shared base for `configure()` only.
  `getTotalCount()` loads and hydrates everything to count.
- `Property\Predefined` and `Metadata\Predefined` are the same kind of location-aware config model (same `getById()`,
  `save()` with an allow-list, `delete()`, `prepareDataStructureForYaml()`, `__clone()` rebinding the DAO) → one
  abstract base (`AbstractLocationAwareConfigModel`), so B-188 / B-206 and B-191 / B-209 are fixed once.
- `save()`: the `$allowedProperties` loop → `array_intersect_key($vars, array_flip(self::PERSISTED_FIELDS))`.
- `__clone()` keeps and rebinds the DAO while `AbstractModel::__clone()` drops it → align (I-87).
- Doc typos: "specicifies" (`Predefined/Listing/Dao.php:30`).

### [ ] I-97 – `Schedule\Task` model and DAO clean-up

- `Task::getById()`: `RuntimeCache` try/catch as control flow → `isRegistered()`; reconsider caching tasks at all
  (B-213).
- `Dao::save()`: `create()` (empty insert) + `update()` → one `insert()` with all columns for new tasks and one
  `update()` for existing ones; `lastInsertId()` cast once (DBAL 4 returns `int|string`).
- `Dao::update()`: `getValidTableColumns('schedule_tasks')` is called inside the loop → once; the array / object
  serialization branch is dead (no such columns) → remove. I-75 lists `schedule_tasks.data`, which does not exist →
  correct I-75.
- Copy-paste leftovers: variable `$site` in `Task/Dao.php:65`; "Loads a list of thumanils for the specicifies
  parameters" in `Task/Listing/Dao.php:30`.
- `Listing\Dao::load()` N+1 (`SELECT id` + `getById()` per row) → hydrate from one `SELECT *`.
- `Task::create()` does not persist although other model `create()` methods do → rename (`Task::fromArray()`) or save
  (B-214). Add `@method void delete()` (the DAO has it).
- `ScheduledTasksDaoTrait::deleteAllTasks()` → bound parameters (already in I-76) and move it into `Task\Dao`
  (`deleteForElement(int $cid, string $ctype, list<int> $keepIds)`).

### [ ] I-98 – `ScheduledTasksTask` restructuring

- The three branches per element type (`:53-126`) repeat the same publish / unpublish / delete / publish-version logic →
  one `ScheduledTaskRunner` service with `match ($task->getAction())` and element-type-specific capability checks
  (`ScheduledTaskAction::appliesTo()`, I-93).
- Dispatch one Messenger message per due task (`ExecuteScheduledTaskMessage(taskId)`), so retries and failures are per
  task and one broken task cannot block the queue (C-36); keep the maintenance job as a scheduler that only selects due
  ids.
- Constructor property promotion + `readonly` (the class has a single `private LoggerInterface $logger` assigned in the
  constructor); inject `LockFactory` (B-210).
- Process due tasks in batches (`LIMIT`) and reset runtime caches between tasks
  (`Pimcore::collectGarbage()`), so a large backlog does not exhaust memory.
- Make scheduled changes traceable: set `userModification` to the task user (B-214) and add an element note / version
  note "published by scheduled task #id"; dispatch pre / post events so projects can react (notifications, cache
  warm-up).
- Log with context arrays (`['task' => $id, 'element' => …, 'exception' => $e]`) instead of string concatenation; the
  "Schedule\Task\Executor" prefix in the messages names a class that no longer exists.

### [ ] I-99 – Schema: `properties` and `schedule_tasks`

- `schedule_tasks`: only single-column indexes (`cid`, `ctype`, `active`, `version`). The due query
  (`active = 1 AND date < ?`) and the element lookup (`cid = ? AND ctype = ?`) need composite indexes
  `(active, date)` and `(cid, ctype)`.
- `schedule_tasks`: `cid`, `ctype`, `date`, `action`, `active` are nullable although a task without them is useless
  (B-214) → `NOT NULL`; a `lastError` / `attempts` column for C-36.
- `properties.type`: add `date` (or remove support, B-201), `NOT NULL` (B-202).
- `properties.cpath` is `utf8` (3-byte) while element keys are `utf8mb4` (B-205) → `utf8mb4` with a shorter index
  prefix on the `getall` key.
- `properties.inheritable` default `1` vs. model default `false` → align.
- A strict `sql_mode` (or at least `STRICT_TRANS_TABLES`) would turn the silent enum / length truncations of B-201,
  B-205 and B-211 into errors. This is a CoreBundle-wide decision (`default.yaml:148`).

### [ ] I-100 – Tests

- Properties (`tests/Support/Test/AbstractPropertiesTest.php` covers CRUD, inheritance and a relation). Add: `date`
  property round trip (B-201); a row with `type = NULL` does not break the element or its children (B-202); array data
  on a `text` property (B-203); saving an element whose relation property points to a deleted element keeps the id
  (B-204); a direct `Property::save()` of an inheritable property is visible to cached children, and `cpath` is set
  (B-205).
- Predefined properties have no tests. Add: listing with a stale settings-store id (B-206); a definition without
  `ctype` (B-207); `getByKey()` after `save()` / `delete()` in the same process and with duplicate keys (B-208);
  `getById('0')` and `save()` with a read-only / disabled write target (B-209).
- Scheduled tasks have **no tests at all**. Add `ScheduledTasksTask` tests for: a task with `userId = NULL`, a deleted
  user and an inactive user (C-36, C-37); a `publish-version` task with a version of another element (C-37); a
  throwing listener on one task does not block the others (C-36); publish / unpublish due in the same run (B-210);
  skipped tasks are logged (B-211); recycle-bin restore keeps the tasks, and deleting a link removes them (B-212);
  `DataObject\Service::copyContents()` keeps the source's tasks (B-182).

---

## M – Version (Scope 9)

### [ ] I-101 – Strictness and native types (PHP 8.4)

- Add `declare(strict_types=1)` to `Version/Dao.php` and `Version/Listing/Dao.php`. Prerequisites that rely on
  coercion today: `isBinaryHashInUse()` compares the `fetchOne()` string (`(int)` cast); the `Listing/Dao.php` /
  `Dao.php` ids from `fetchFirstColumn()` are already `intval`ed.
- Setters typed `: Version` with `@return $this` → `: static`. Mixed `void` / fluent setters (`setVersionCount()`,
  `setBinaryFileHash()`, `setBinaryFileId()`, `setGenerateStackTrace()`, `setStackTrace()`, `setStorageType()`) →
  all fluent. `getById(): ?Version` → `?self`.
- `final class Version` with `protected` members → `private`. `SetDumpStateFilter` (final) has a `protected bool
  $state` → `private readonly` via constructor promotion.
- PHP 8.4 asymmetric visibility for instance properties: `public private(set) ?int $id` (only the DAO assigns it).
  The static `$disabled` cannot use it (asymmetric visibility for static properties arrives in PHP 8.5) → make it
  `private static` behind `disable()` / `enable()` / `isEnabled()` (B-215).
- Backed enums: `ElementType` for `ctype` (shared with I-86 / I-93) and `VersionStorageType` (`fs`, `db`) instead of
  free strings.
- Adapter streams are typed `mixed`; add `@param` / `@return resource|null` everywhere (only `save()` has it) and fix
  `getStream(): ?false` (C-38).
- Strict comparisons: `in_array()` without `true` (`Dao.php:62`), `!= 'folder'` (`Version.php:171`), `$count % 100 == 0`
  (`Dao.php:149`).

### [ ] I-102 – `Version` model API

- Separate creation from metadata updates: `Version::create(ElementInterface $element, …)` for new versions and
  `updateMetadata(note, public, versionCount)` for existing ones (B-219). `save()` currently does both.
- Replace the global `disable()` / `enable()` with `Version::withoutVersioning(callable)` (counter + `finally`), deprecate
  the pair, and migrate the eight call sites in `lib/`, `models/` and `bundles/` (B-215).
- The constructor pulls the storage adapter from the container (`Pimcore::getContainer()->get(...)`). Every
  `new Version()` needs a booted kernel, and `VersionsCleanupTask` builds a full model only to reach the DAO. Inject
  it (model factory / a `VersionRepository` service) and keep the static accessors as BC facades.
- `getPublic()` / `isPublic()` duplicates → keep `isPublic()`; `getVersionCount()` `?: 0` → plain return;
  `getSerialized()` → `isSerialized()`.
- `setUserId()` loads the user as a side effect (B-217) → store the id; `getUser()` resolves lazily.
- Empty doc blocks (`/** * * @internal */` at `:264-268`) and "see @ self::disable()" → real `@see` tags.

### [ ] I-103 – Loading and (un)marshalling

- `marshalData()` / `unmarshalData()` are 90 % identical → one `copyForVersion(ElementInterface, 'marshal'|'unmarshal')`
  with the filter callback built once.
- `loadData()` mixes storage access, unserialization, unmarshalling, asset stream restore and reference renewal → split
  into `readPayload()`, `hydrate()` and `restoreBinary()`; throw instead of returning `null` (B-218).
- `Serialize::unserialize()` without `allowed_classes`: whoever can write to version storage (a separate DB for the DB
  adapter, a shared NFS / S3 bucket) gets PHP object injection on the next version load. Prefer a format without
  arbitrary object instantiation for new versions, or at least sign the payload (HMAC with the kernel secret) and
  verify it before `unserialize()`. Same family as I-75.
- The `__PHP_Incomplete_Class` check only looks at the top-level value; nested incomplete objects are handled later
  by `renewReferences()` with a log entry. Report both through one result object.

### [ ] I-104 – `Version::save()` clean-up and asset hashing cost

- For assets, `save()` reads the whole binary for `sha3-512` on every save (2 GB video → 2 GB read per metadata edit),
  plus a storage `fileSize()` call. Reuse the hash when the binary is unchanged (`dataChanged` flag, or the asset
  `checksum` custom setting when its algorithm matches), and take the size from the stream (`fstat()`) (B-220).
- Remove the `__wakeup()` leftover, the `setId()` duplication and the misspelled `getfileSize()` (B-220).
- Stack trace via `debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)` and a length cap (the column is `TEXT` and has a
  `stackTrace(1)` index, I-108).
- `$isAsset ? $data->… : null` is evaluated three times → compute `$binaryStream` / `$binarySize` once.

### [ ] I-105 – `Version\Dao` and `Version\Listing\Dao`

- `Dao::save()`: `getValidTableColumns('versions')` is called inside the loop → once; `upsert()` + `lastInsertId()` →
  `insert()` for new and `update()` for existing versions; `lastInsertId()` returns `int|string` in DBAL 4 → cast once.
- `isVersionUsedInScheduler()` queries `schedule_tasks` → belongs in `Schedule\Task\Dao` (I-97).
- `maintenanceGetOutdatedVersions()` (50 lines, nested loops, `$stop` flag, logging, garbage collection) → a dedicated
  maintenance query service with two methods (`findOutdatedByDays()`, `findOutdatedBySteps()`), bound parameters
  (`ArrayParameterType::INTEGER`) instead of `implode()`, and no `Logger` / `collectGarbage()` in the DAO (B-223).
- `Listing\Dao::load()` N+1 → hydrate rows directly (B-224); `getCondition()` string juggling → add `autoSave = 0` as a
  regular condition part; typo "specicified" (`:44`).

### [ ] I-106 – Storage adapter design

- The interface has overlapping methods: `loadBinaryData()` vs. `getBinaryFileStream()` (identical in DB, existence
  check in FS) and `loadMetaData()` vs. `getFileStream()`. Reduce it to `readMetadata()`, `readBinary()` (stream or
  `null`), `write()`, `delete()` and `getStorageType()`, and document the stream contract (rewound, caller closes).
- `FileSystemVersionStorageAdapter` calls `Storage::get('version')` and `Config::getSystemConfiguration()` itself →
  inject the `pimcore.version.storage` Flysystem operator and the `use_hardlinks` flag. Replace the `'1'` probe write
  with a configuration check (C-39). `floor($cId / 10000) * 10000` → `intdiv()`. Make `getStorageFilename()` /
  `getBinaryStoragePath()` private or part of an explicit path strategy.
- `DatabaseVersionStorageAdapter`: `const string versionsTableName` → `TABLE_NAME` (or a constructor argument);
  `executeQuery()` for DML → `executeStatement()`; `isset(...) === true` / `empty(...) === true` style → plain checks;
  ship the `versionsData` DDL as a migration / `pimcore:versions:install-db-storage` command instead of only in the
  docs; `binaryData` in its own table (C-38).
- `DelegateVersionStorageAdapter`: dead `empty($this->fallbackAdapter)` check; adapters keyed by name instead of storage
  type (B-222); dedicated exception for unknown types.
- `ProxyVersionStorageAdapter` is only used by the CI config (`.github/ci/files/config/services.yaml:49-51`) but is not
  `@internal` and accepts only the FS adapter in its constructor → mark `@internal`, type the constructor with the
  interface, or move it to `tests/Support`.
- All adapters: constructor promotion with `readonly`; one parameter per line formatting (`loadData()` / `getStorageType()`
  use column-aligned parameter lists unlike the rest of the code base); `final` where not extended.

### [ ] I-107 – Version maintenance

- `VersionsCleanupTask` ("Not very pretty and should be solved using a repository....", `:101`) → a
  `VersionCleanupService` that streams candidate ids with a cursor, checks `public` / scheduler / element modification
  date in SQL, and deletes in batches with one transaction per batch (B-223).
- The auto-save cleanup builds its condition by string concatenation (`'`date` < ' . $timestamp`) → bound parameter; the
  72-hour retention is hard-coded → configuration.
- `SanityCheckHandler` re-orders the draft by re-saving it (B-219) → `updateMetadata()`.
- Add an orphan cleanup for version files / `versionsData` rows without a `versions` row (C-39, B-222).

### [ ] I-108 – Schema: `versions` (and `versionsData`)

- `cid`, `ctype`, `date` are nullable although the model requires them (B-217) → `NOT NULL`; `userId` stays nullable
  for "system" but the model must accept `null`.
- Index support for the hot queries: auto-save lookup (`cid, ctype, autoSave, userId`), binary dedup
  (`cid, binaryFileHash, storageType`), latest-version lookup (`cid, ctype, versionCount`). Today there are only
  single-column indexes plus `ctype_cid`.
- `KEY stackTrace (stackTrace(1))` (a 1-character prefix index on a `TEXT` column) only serves the
  `stackTrace IS NOT NULL` filter of `VersionsCleanupStackTraceDbTask.php:31` → replace with an index on `date` (already
  there) or a generated boolean column.
- `storageType VARCHAR(5) NOT NULL` without default; values constrained only by code → enum or check constraint
  (I-101 `VersionStorageType`).
- `versionsData.id` is `AUTO_INCREMENT` although ids always come from `versions.id`; `cid` / `ctype` are nullable and
  part of every lookup → `NOT NULL`, primary key `id` only (drop `AUTO_INCREMENT`).

### [ ] I-109 – Tests

- Fix the PHPUnit 10 incompatibility first (B-225).
- Per adapter (FS, DB, Delegate): save + load round trip for an object, a document and an asset (C-38); asset
  versions with an unchanged binary share it, and deleting the oldest keeps the binary readable (C-38); re-saving a
  version keeps payload, stack trace and binary (B-219); a failing binary write leaves no reusable `.bin` (C-39);
  switching adapters keeps old versions readable (B-222).
- Model: `save()` without `setDate()`, with `userModification = 0` and with a deleted user (B-217); `getData()` on a
  missing payload throws once and does not reload (B-218); a restored asset version with a missing binary fails
  (B-218); `disable()` is reset after an exception and nested calls keep it disabled (B-215); `loadData()` does not
  close the DB connection or clear unrelated runtime-cache keys (B-216).
- Maintenance: a version whose delete throws does not block the others; the auto-save cleanup survives a concurrently
  deleted version; `steps` keeps the configured number of versions (B-223); deleting an element removes its auto-save
  versions (B-224).

---

## N – User (Scope 10)

### [ ] I-110 – Strictness and native types (PHP 8.4)

- Add `declare(strict_types=1)` to the 11 DAO files listed in the TODO-1 Scope 10 notes. Prerequisites that rely on
  coercion today: `hasChildren()` (`(bool)` of a `fetchOne()` string), `getTotalCount()` (already `(int)`), the
  `getValidTableColumns()` / `in_array()` loops in `AbstractUser\Dao::update()` and `Workspace\Dao::save()`.
- Return types: `setPasswordRecoveryToken(): User` and `setDatetimeLocale(): User` → `static`; the `void` setters
  (`setContentLanguages()`, `setActivePerspective()`, `setKeyBindings()`, `setTwoFactorAuthentication()`,
  `setProvider()`, `setImage()`, `Workspace\DataObject::setLEdit()` / `setLView()` / `setLayouts()`) → fluent
  `static` like the rest; `getImage()` has no return type → `mixed` with `@return resource` (or a typed stream, I-116);
  `getById(): static|null` → `?static`; `Definition::create(): self|static` → `static`; `getByKey(): ?Definition`
  → `?static`.
- Contract mismatches: `setUsername(?string)` vs `setName(string)` (B-228); `setParentId(int)` vs `getParentId(): ?int`
  and the nullable column; `setKeyBindings(string)` vs `getKeyBindings(): ?string`; `setLastLogin(int)` vs `?int`.
- `final class User` has only `protected` members → `private` where not used by the parents. The parents are not
  final; `Role` / `Folder` / `Role\Folder` could be `final`.
- PHP 8.4 asymmetric visibility: `public private(set) ?int $id` and `public private(set) string $type` (only the DAO /
  constructor assign them) replace `setId()` / `setType()` as public API; `type` should not be mutable at all (C-40).
- PHP 8.4 property hooks for the eleven boolean workspace flags and the user preference flags (`welcomescreen`,
  `closeWarning`, `memorizeTabs`, `allowDirtyClose`) instead of 30+ getter/setter pairs, keeping the methods as
  deprecated aliases.
- Strict comparisons: `==` in `isAllowed()` (`:271`, `:284`, `:297`, `:310`), loose `in_array()` / `array_search()`
  in `isAllowed()`, `setPermission()`, `getPermission()`, `cleanupUserRoleRelations()`, `UserRole\Dao::getById()`.
- Add `#[\Override]` to the overriding methods (`User::getPermission()`, `UserRole::update()`, `Folder::getChildren()`,
  the DAO `getById()` / `getByName()` / `delete()` overrides).
- `AbstractUser\Dao::setLastLoginDate()`: `new DateTime()->getTimestamp()` → `time()` and an initialised `$data`.

### [ ] I-111 – Backed enums for user types and permission check types

- `enum UserType: string { case User = 'user'; case UserFolder = 'userfolder'; case Role = 'role'; case RoleFolder =
  'rolefolder'; }` with `className()` replaces `Service::getClassNameForType()`, the `protected string $type`
  defaults, the `['user', 'role']` checks in `UserRole\Dao`, the `role`/`rolefolder` branch in `delete()` and the
  string concatenation in `AbstractListing\Dao::getCondition()` (`"… IN ('" . implode("','", $types) . "')"`).
- `enum PermissionCheckType: string { Permission, Class, DocType, Perspective }` for `isAllowed(string $key, string
  $type = 'permission')`; an unknown type silently returns `false` today.
- Element type for workspaces (`asset` / `document` / `object`) shares the `ElementType` enum proposed in I-86 / I-93 /
  I-101; `UserRole\Dao::loadWorkspaces()` builds the class name by string concatenation and calls
  `setWorkspaces{Type}()` dynamically.

### [ ] I-112 – Permission resolution as a service

- `User::isAllowed()`, `getPermission()`, `getMergedPerspectives()`, `getMergedWebsiteTranslationLanguages*()` and
  `PermissionChecker::getUserPermissions()` each re-load the roles and merge lists by hand (four copies of the same
  loop). Move this into one `UserPermissionResolver` service: load all roles of a user once (`Role\Listing` with
  `id IN (…)`), compute an immutable `EffectivePermissions` value object (permissions, classes, docTypes,
  perspectives, translation languages), cache it per user id and invalidate it on `UserRoleEvents::POST_UPDATE` /
  `POST_DELETE` of the user **or** one of its roles; implement `ResetInterface` for worker runtimes.
- Decide and document the merge semantics. Permissions are a union (more roles = more rights). For `classes`,
  `docTypes`, perspectives and translation languages "empty = everything" is applied **after** merging, so adding a
  restricted role to an unrestricted user narrows their rights, and an unrestricted role contributes nothing when
  another role is restricted. Either treat "empty" per source as "everything" or name it explicitly
  (`null` = unrestricted, `[]` = nothing; see C-20).
- `getPermission()` (own permissions only) vs `isAllowed()` (with roles) is easy to misuse (B-237): rename to
  `hasOwnPermission()` and keep `getPermission()` as a deprecated alias, or make it role-aware.
- Symfony 7.4: expose the checks as a security voter (`Voter<string, User>` for `PIMCORE_PERMISSION_<key>`), so
  controllers can use `#[IsGranted]` and `isGranted()` instead of calling the model.

### [ ] I-113 – CSV columns and list parsing

- `roles`, `permissions`, `classes`, `docTypes`, `perspectives`, `websiteTranslationLanguagesEdit` / `View` and
  `contentLanguages` are comma-separated strings. `cleanupUserRoleRelations()` needs `FIND_IN_SET()` (full table
  scan), the workspace permission SQL in the element DAOs does the same, and nothing enforces referential integrity
  (B-226). Long term: a `users_roles` join table (with FK + `ON DELETE CASCADE`) and JSON columns for the other lists.
- Short term: one `CsvList::parse(array|string|null): list<string>` helper (trim, drop empty, unique, `array_values`)
  for `prepareArray()`, `setPermissions()`, `setRoles()` (+ `intval` / `> 0`), `setContentLanguages()` and
  `getContentLanguages()`; one `CsvList::format()` for `AbstractUser\Dao::update()` instead of the hard-coded key list.

### [ ] I-114 – DAO structure and persistence

- `AbstractUser\Dao::update()` writes every column on every save, driven by `getObjectVars()` + `getValidTableColumns()`
  + a hard-coded list of CSV keys + a `twoFactorAuthentication` special case. Use an explicit column map per type.
  Password, recovery token and last login need targeted updates (`updatePasswordHash()`, `updatePasswordRecoveryToken()`,
  `updateLastLogin()`) so that login / recovery do not run a full save (B-230, B-236).
- `castUserDataToBoolean()` and the boolean block in `loadWorkspaces()` → one hydrator per table (or Doctrine DBAL type
  conversion).
- `UserRole::update()` deletes and re-inserts all workspaces on every save, even when only `lastLogin` or a preference
  changed → write workspaces only when they were set (dirty flag).
- The `@method void setLastLoginDate()` magic on `AbstractUserInterface` / `AbstractUser` relies on
  `AbstractModel::__call()` → an explicit model method that also updates `$lastLogin` (B-235).
- Wrong / misleading docblocks and DAO mapping: `User\Folder` documents `@method Dao getDao()` and
  `config/dao-classmap.php:157` really maps it to `User\Dao`, so deleting a user folder runs the seven element-
  ownership `UPDATE`s of `User\Dao::delete()`; `UserRole\Folder` is mapped to `UserRole\Dao` (`:165`) while
  `Role\Folder` uses `Role\Folder\Dao extends AbstractUser\Dao`; `UserRole\Dao` documents `@property Folder $model`.
  Map all folders to `AbstractUser\Dao` and fix the docblocks.
- Four empty DAO / listing DAO subclasses exist only for DAO resolution (`Role\Dao`, `Role\Folder\Dao`,
  `Listing\Dao`, `Role\Listing\Dao`); with the static classmap they can map to the parent directly.

### [ ] I-115 – Lookup, caching and listings

- Replace the reflection-based `getById()` with one factory: fetch the row, pick the class from `UserType`, hydrate,
  cache (C-40). Route `getByName()` and the recovery-token lookup through the same path and cache (B-235).
- The runtime-cache key `user_<id>` is a shared namespace; prefix it with the model class or use a dedicated
  identity map service (resettable).
- `User\Listing\…\Dao::load()` selects `id, type` and then calls `getById()` per row: one query plus four per user
  (row + three workspace tables). Hydrate from one `SELECT *` and load workspaces for all ids with one query per table.
- `AbstractListing::$type` + `getType()` → a class constant / `UserType`; `AbstractListing\Dao::getCondition()` →
  bound parameters.
- `Listing::getUsers()` / `Role\Listing::getRoles()` aliases plus `getItems()` / `getData()` → keep one name.

### [ ] I-116 – Avatar handling out of the model

- Move `setImage()` / `getImage()` / `hasImage()` / path helpers into a `UserAvatarService` with the `pimcore.admin.storage`
  Flysystem operator injected (no `Tool\Storage::get('admin')` service location, no `TemporaryFileHelperTrait` in the
  model).
- Per-size thumbnails, an upload check with Symfony Mime (`image/*`), write-then-delete, cleanup on user delete
  (`UserRoleEvents::POST_DELETE` listener), a fallback avatar shipped by core (B-231).
- Return a `resource` with a documented `@return resource` or a small value object (`mimeType`, `stream`), so
  controllers can build a `StreamedResponse` without guessing.

### [ ] I-117 – Credentials, 2FA and the security user (Symfony 7.4)

- Split `setTwoFactorAuthentication(array|string $key, mixed $value = null)` into explicit methods and a readonly
  `TwoFactorSettings` value object (`required`, `enabled`, `secret`, `type`); decode JSON in the DAO with
  `JSON_THROW_ON_ERROR`; store it in a `JSON` column (B-232, I-120).
- Passwords: `setPassword()` takes a hash → rename to `setPasswordHash()`; hash through Symfony PasswordHasher
  (`PasswordHasherFactoryInterface`, `MigratingPasswordHasher` for the legacy `md5(username:pimcore:password)`
  pre-hash in `Authentication::preparePlainTextPassword()`); implement `PasswordUpgraderInterface` on
  `Security\User\UserProvider` instead of the rehash + full `save()` in `verifyPassword()`. Note: the md5 pre-hash ties
  the hash to the user name, so renaming a user invalidates their password; document it or drop the pre-hash in the
  migration.
- Recovery tokens: `random_bytes()` token, store `hash('sha256', $token)` plus an expiry column, single use; drop the
  encrypted `timestamp|name` format and the time-zone switching in `authenticateToken()` (B-236).
- `Security\User\User`: implement `__serialize()` / `__unserialize()` so the session token holds the user id, the
  identifier, the role names and a `crc32c` / `hash()` of the password hash (Symfony 7.3+ pattern), instead of the
  whole `Model\User` (password hash, 2FA secret, recovery token, workspaces, merged caches) in the session store.
  `eraseCredentials()` is already `#[\Deprecated]`; remove it once the Symfony 8 upgrade happens.
- `isEqualTo()` / `refreshUser()` per C-41; `getUserIdentifier()` returns `''` for a nameless user → throw instead.
- `Pimcore\Model\UserInterface` shares its short name with Symfony's `UserInterface` and declares `getRoles(): array`
  with role **ids** (`int[]`), while Symfony's returns role **names**. Document it or rename (`getRoleIds()`) with a
  deprecated alias, before anyone implements Symfony's interface on the model.

### [ ] I-118 – Permission definitions

- `Definition::getByKey()` builds a listing for a single primary-key lookup → direct `fetchAssociative()`.
- `create()`: typos ("permisson", "allready"); no way to pass the category; returns an unsaved definition if
  `save()` failed (B-233). Add `delete()` so bundles can remove permissions on uninstall.
- `generatePermissionList()` → `array_combine($keys, array_map($this->getPermission(...), $keys))` with
  `array<string, bool>`; remove the `@todo`.
- The installer inserts the core permission keys with raw SQL (`bundles/InstallBundle/src/Installer.php:853-895`), next
  to the model. Use `Definition::create()` (or a single upsert) and keep the core keys as constants in one place.

### [ ] I-119 – Folder and workspace models

- `UserRole\Folder` is instantiable but has no type of its own → `abstract`. `User\Folder::getChildren()` duplicates
  the parent except for the listing class → template method `createChildListing()`. Keep `children` and
  `hasChildren` consistent (B-237).
- `AbstractWorkspace`: `@internal` on every property and the boilerplate accessors → property hooks or public
  properties with asymmetric visibility (I-110); constructor with `int $cid, string $cpath`.
- `Workspace\DataObject::$lEdit` / `$lView` / `$layouts` are CSV strings with "empty = all" semantics (`@TODO` in
  `setLEdit()`) → `?array` with explicit `null` (all) vs `[]` (none).
- A `getTableName()` per workspace class instead of the `instanceof` chain in `Workspace\Dao::save()` (B-234).

### [ ] I-120 – Schema: `users`, `users_permission_definitions`, `users_workspaces_*`

- `users.name` is nullable but required → `NOT NULL`; `parentId` uses both `0` (installer) and `NULL` (`Dao::create()`)
  for "no parent" → pick one.
- Defaults disagree with the model: `welcomescreen` / `closeWarning` / `memorizeTabs` `DEFAULT NULL` (model:
  `false` / `true` / `true`), `allowDirtyClose` `DEFAULT 1` (model: `false`), `datetimeLocale` `DEFAULT ''` (model:
  `null`). Rows created by `Dao::create()` get the DB defaults until the following `update()`.
- `KEY password (password)` indexes the password hash (no query uses it); `KEY name`
  is redundant next to `type_name`; `passwordRecoveryToken` has no index (lookup by token scans the table) → index the
  token hash (I-117).
- `twoFactorAuthentication varchar(255)` holds JSON → `JSON`; `keyBindings` is already `JSON`.
- No foreign keys from `edit_lock`, `notes`, `schedule_tasks`, `translations_messages`, `gridconfigs`, `importconfigs`,
  `gridconfig_*` / `importconfig_shares`, `notifications` to `users` (B-229).
- `users_workspaces_*.cpath` nullable → `NOT NULL` (B-234).

### [ ] I-121 – Tests

- There is no test for the user model itself; `tests/Model/Permissions/*` cover element workspaces only.
- Add: `UserRole::getById()` followed by `Role::getById()` for the same id (C-40); a session after deactivation,
  password change, role removal and delete (functional, C-41); stale / folder / `0` role ids (B-226); role change
  visible in `isAllowed('…', 'perspective')` on the same object (B-227); `setPassword(null)` (B-228); delete
  cleanup and rollback (B-229); retry after a failed save, legacy names (B-230); `getImage()` with two sizes
  (B-231); `setTwoFactorAuthentication('secret', null)` keeps `required` (B-232); `generatePermissionList()` with no
  definitions (B-233); workspace with `NULL` `cpath` and duplicate `cid` (B-234).

---

## O – Tool (Scope 11)

### [ ] I-122 – Strictness and native types (PHP 8.4)

- Add `declare(strict_types=1)` to the six DAOs. Prerequisites that rely on coercion today: `Email\Log\Dao::getById()`
  (`$id != null`), `prepareLoggingData(string $key, …)` called with integer array keys, the `in_array()` column loops,
  `Log\Listing\Dao::load()` passing `fetchFirstColumn()` strings to `Log::getById(int)` (a `TypeError` once the DAO is
  strict → cast with `(int)`).
- `Email\Log` / `Email\Blocklist` setters: mix of `void` and fluent `static` → all `static`; `SettingsStore` /
  `TmpStore` setters are `void` (internal hydration only) → `static` or `private`.
- `SettingsStore::get(): ?SettingsStore` / `TmpStore::get(): ?TmpStore` / `Log::getById(): ?Log` /
  `Blocklist::getByAddress(): ?Blocklist` → `?self` / `?static`; `SettingsStore::delete(): int|string` leaks the DBAL
  return type → `bool` ("something was deleted") or `void`.
- `final class SettingsStore` / `TmpStore` with `protected` members and a `protected static ?self $instance` →
  `private`; the static singleton exists only to reach the DAO (I-123).
- `Email\Log::$emailLogExistsHtml` / `Text` are `int` 0/1 with "Contains 1 if …" docblocks → `bool` with
  `hasHtmlLog()` / `hasTextLog()`; `setEmailLogExistsHtml()` takes no argument (a "setter" that queries storage) →
  rename `refreshLogFileFlags()`.
- PHP 8.4 asymmetric visibility for `Email\Log::$id` (`public private(set) ?int`) and `SettingsStore::$id` / `$scope`.
- Remove the one-line "Returns the …" docblocks that repeat the signature; keep the ones that explain units
  (timestamps).

### [ ] I-123 – `SettingsStore` API

- Backed enum `SettingsStoreType: string { Bool = 'bool'; Int = 'int'; Float = 'float'; String = 'string'; }` replaces
  the four `TYPE_*` constants, `ALLOWED_TYPES` and `validateType()`; keep the constants as deprecated aliases
  (`public const string TYPE_BOOLEAN = SettingsStoreType::Bool->value`).
- Typed accessors instead of `getData(): float|bool|int|string`: `getString()`, `getInt()`, `getBool()`, `getFloat()`
  that throw on a type mismatch; `set()` validates `$data` against `$type` (B-240) and returns `void` (B-238).
- Replace the static facade + singleton with an injectable `SettingsStoreRepository` service (Symfony autowiring,
  `ResetInterface` if it caches), keeping `SettingsStore::get()` / `set()` as thin static wrappers for BC. Callers
  that read the same key on every request (`SettingsStoreAwareInstaller::isInstalled()`, SeoBundle) can then use a
  request-scoped cache (see SeoBundle I-items).
- `JSON` type for structured values: `LocationAwareConfigRepository` stores `json_encode($data)` as `string` and
  decodes it itself; a `TYPE_JSON` (`json` enum value) with `JSON_THROW_ON_ERROR` removes that duplication.
- `getById()` hydrates through `setValues()` and then calls `setData()` again → one explicit hydration
  (`type` first, then `data`).

### [ ] I-124 – `TmpStore` API and cleanup

- Same singleton / static facade as `SettingsStore` → `TmpStoreRepository` service; keep the static methods for BC.
- Consider replacing it with Symfony Cache (`TagAwareCacheInterface` on a `DoctrineDbalAdapter` / `PdoAdapter` pool):
  it already provides lifetimes, tags, atomic `get()` with a callback, and pruning (`PruneableInterface`, run by
  `cache:pool:prune`). The remaining TmpStore-specific use is "list ids by tag" (`getIdsByTag()`), used by the
  video / thumbnail processors.
- If kept: a `type` column (or always serialize) so scalars round-trip (B-239); `add()` as one atomic `INSERT IGNORE`;
  `unserialize()` with `allowed_classes` (each caller passes the classes it expects, e.g. `[Video\Thumbnail\Processor::class]`)
  — today any object graph stored in `tmp_store` is revived on read (object-injection gadget if a row can be written
  by an attacker, e.g. via a SQL injection elsewhere); one default lifetime constant; `get()` filters
  `expiryDate > NOW` in SQL instead of loading and deleting.
- `TmpStoreCleanupTask` deletes all expired rows in one statement; on large tables (session-stored elements are large
  serialized blobs) → delete in batches (`LIMIT 1000` loop).
- `getDefaultLifetime()` is a method returning a constant → `private const int DEFAULT_LIFETIME = 604800`.

### [ ] I-125 – Email log model and DAO

- Split the persistence of the log row (DB) from the bodies (storage) into an `EmailLogRepository` service with the
  `pimcore.email_log.storage` Flysystem operator injected (no `Storage::get('email_log')` service location in the
  model, called four times in `Log`).
- `Dao::save()`: replace "`getObjectVars()` + `getValidTableColumns()` + getter lookup by name + `quoteIdentifier()`"
  with an explicit column map; one `INSERT` for new logs (B-241).
- `prepareLoggingData()` / `createJsonLoggingObject()` are presentation logic for the admin tree grid (stdClass with
  `key` / `data` / `children`) inside the DAO. Move it into a `MailParamsNormalizer` (Symfony Serializer normalizer)
  and store a stable schema (`list<array{key: string, type: string, value?: scalar|null, id?: int, class?: string,
  children?: list<…>}>`). Call it as `$this->…` instead of `self::` on instance methods.
- `Log::getById()` checks two files in storage per load; listings and the cleanup command pay for it N times → load
  the flags lazily (`hasHtmlLog()` on first use) or store them in the row (`hasHtml TINYINT`).
- `Email\Log\Listing\Dao::load()` → hydrate from one `SELECT *` (like `getDataArray()` already does).
- Blocklist: the same N+1 listing pattern; `Blocklist` is `@internal` but used by `Pimcore\Mail` and the admin UI → make
  it public API or move the check into a `MailRecipientFilter` service with a `isBlocked(string $address): bool`
  method that can be backed by one `WHERE address IN (…)` query per mail instead of one query per recipient (C-42).

### [ ] I-126 – Email log retention and privacy

- Logs (recipients, rendered bodies, params incl. personal data from objects) are kept forever unless someone runs
  `pimcore:email:cleanup` by hand. Add a maintenance task with a configurable retention
  (`pimcore.email.log.retention_days`, default e.g. 30/90), batched deletes and file cleanup (B-243).
- Optional per-mail opt-out of body logging (`Mail::disableBodyLogging()`), and masking of `Bcc` in the log.
- `requestUri`, `from`, `to`, … are stored as formatted strings (`formatDebugReceivers()`); store structured JSON
  (`[{address, name}]`) so the admin can search / link without parsing.

### [ ] I-127 – Schema: `email_log`, `email_blocklist`, `tmp_store`, `settings_store`

- `email_log.params TEXT` → `MEDIUMTEXT` / `JSON`; `subject` / `from` `varchar(500)` and `replyTo varchar(255)`
  inconsistent with `to` / `cc` / `bcc` `LONGTEXT` (B-241).
- `email_log.sentDate` nullable although always set; no `modificationDate` column although the model has the property
  (B-242) → drop the property or add the column.
- The `FULLTEXT` index includes `params` (JSON of the tree format: keys like `"type":"simple"` pollute the index).
- `email_log.documentId` `ON DELETE CASCADE` deletes history together with the document (B-243) → `ON DELETE SET NULL`
  keeps the audit trail.
- `email_blocklist.address varchar(190)` — RFC 5321 allows 254 characters; `creationDate` / `modificationDate`
  nullable but always set.
- `tmp_store.date` / `expiryDate` nullable but required (B-239); `serialized tinyint(2)` → `tinyint(1)` / a `type`
  column (I-124).
- `settings_store.data longtext` nullable → `NOT NULL` or explicit `NULL` semantics (B-240).

### [ ] I-128 – Tests

- Only `tests/Model/Tool/SettingsStoreTest.php` exists. Its `getScope()` check uses `assertEquals()` (passes for
  `''` vs `null`); use `assertSame()`.
- Add: blocklist filtering in `Mail::sendWithoutRendering()` for `To` / `Cc` / `Bcc`, mixed-case and padded addresses,
  "all recipients blocked" (C-42, B-244); `SettingsStore` with a `NULL` data row, type mismatch on `set()`, DB error
  on `set()` surfaces (B-238, B-240); `TmpStore` round trip of `int` / `bool` / `float` / array / object, concurrent
  `add()`, expired ids in `getIdsByTag()`, class-not-found on unserialize (B-239); email log with oversized params, a
  CLI-sent mail (no request URI) loaded back and all getters called, `save()` of a loaded log keeps `params`
  unchanged, document delete leaves no body files, cleanup with a concurrently deleted row (B-241–B-243).

---

## P – DataObject root files (Scope 12)

### [ ] I-129 – Strictness and native types (PHP 8.4)

- All eleven files are `strict_types`; the remaining loose spots: `!=` / `==` id and type comparisons
  (`Classificationstore.php:115`, `:340`, `:375`, `:379`, `:381`, `:385`; `Folder.php:50`; `DefinitionModifier.php:129`)
  → `===`; `Folder::delete()` magic `1` → a `ROOT_ID` constant (I-74).
- `Folder::create(array $values): Folder` builds `new static()` → return `static`; `#[\Override]` on
  `Folder::update()` / `delete()`; `Folder::$type` → `DataObject::OBJECT_TYPE_FOLDER`.
- `Classificationstore`: `@method Dao createUpdateTable()` (returns `void`), `@method Classificationstore load()`
  (returns `void`) are wrong; `setFieldname()`, `setActiveGroups()`, `setGroupCollectionMapping(s)()` return `void`
  while the other setters are fluent → `static`; `getLanguage()` is a public helper returning `'default'` → private,
  with a `DEFAULT_LANGUAGE` constant; exceptions `Exception('groupId not valid')` → `InvalidArgumentException`;
  `setObject(Concrete)` vs `ObjectAwareFieldInterface::setObject(?Concrete)` → implement the interface.
- `Listing`: `@method Model\DataObject|false current()`, `@method onCreateQueryBuilder(?callable)` (no return type)
  → correct docblocks; `addDistinct(): bool` that always returns `false` → document why (the DAO selects by id) or drop.
- PHP 8.4 asymmetric visibility for read-mostly state (`Classificationstore::$fieldname`, `$object`).
- Empty or half docblocks (`/** Returns an array of */` at `Classificationstore.php:240`, empty `@internal` blocks,
  `DefinitionModifier` blocks with stray blank `*` lines) → complete or remove.

### [ ] I-130 – `Classificationstore` structure and logic

- The class mixes value storage, dirty detection, language fallback, inheritance (two implementations:
  `getLocalizedKeyValue()` walks parents itself `:366-393`, `getAllDataFromField()` uses
  `Service::hasInheritableParentObject()`), calculated values and group config lookup → extract an inheritance /
  fallback resolver and keep the model a plain value holder.
- `getItems()`, `getActiveGroups()`, `getGroupCollectionMappings()` walk the whole ancestor chain on every call;
  `getGroupCollectionMapping(int)` does it once per group (called in loops) → compute once per read and memoise until
  the next mutation.
- `getGroups()` runs `GroupConfig::getById()` per active group (N queries) → one listing by ids;
  `getActiveGroupsWithConfig()` is a private **static** method taking `$this` as argument → instance method;
  `createGroup(Classificationstore $classificationstore, …)` takes the instance it is called on → drop the parameter.
- Dirty detection by `serialize(getDataForResource())` (B-248) → `EqualComparisonInterface::isEqual()`, which most data
  types already implement.
- The `BooleanSelect` / `Multiselect` "non-empty" workarounds (`:175-176` `@TODO`, `:208-214`) and
  `if ($nonEmpty || $value)` (redundant `|| $value`) → ask the field definition (`isEmpty()`), fix the data types that
  report wrong emptiness.
- The DAO hydrates through `setLocalizedKeyValue()` (B-248) → an `@internal` raw setter.
- `getFallbackValue()` / `getLocalizedKeyValue()` use `array_key_exists()` chains → `$this->items[$g][$k][$l] ?? null`
  (values are never `null`, empty values are unset).

### [ ] I-131 – `DefinitionModifier` API

- Five public methods build closures that forward `func_get_args()` and the private helpers index `$args[0]` /
  `$args[2]` → pass a typed callback `callable(Layout|Data\Localizedfields $parent, Data|Layout $child, int $index): bool`
  and named parameters.
- `@var Layout $child` inside `findField()` is wrong (children are `Data|Layout`).
- Return `bool` hides *why* nothing happened (name not found vs target is not a container) → throw a dedicated
  exception or return a result enum; document first-match semantics.
- `final class` without state or dependencies → static helpers or a service registered in the container (then it
  can dispatch an event / validate through the definition).
- Ties to B-251: one `ChildrenAwareDefinitionInterface` (`getChildren()` / `setChildren()`) for `Layout`,
  `Localizedfields`, `Block` replaces the `instanceof` lists.

### [ ] I-132 – `DataObject\Listing`

- Object types as bound `ArrayParameterType::STRING` parameter and validated against `DataObject::$types` (B-250);
  `addFilterByField()` quotes `$field` by hand with backticks → `quoteIdentifier()` (the operator allowlist is already
  I-76).
- `getItems(int $offset, int $itemCountPerPage)` (pagination) mutates the listing's offset / limit and loaded data
  permanently → work on a clone, or document it.
- `getObjects()` / `setObjects()` duplicate `getData()` / `setData()` → keep one pair, deprecate the other.
- The object-type and unpublished filters are implemented in the shared `QueryBuilderHelperTrait` behind
  `if ($this instanceof DataObject\Listing\Dao)` → move them into `DataObject\Listing\Dao` (a hook such as
  `applyListingConditions()`), so the shared trait has no type switch.

### [ ] I-133 – Root interfaces

- `ClassDefinitionInterface`:
  - inconsistent setter returns: `setPreviewGeneratorReference(): void`, `setEnableGridLocking(): void`,
    `setDeletedDataComponents(): ClassDefinitionInterface` vs `static` everywhere else;
  - `setModificationDate(int)` vs `setCreationDate(?int)` and nullable getters;
  - `getByIdIgnoreCase(): ClassDefinitionInterface|null` → `?static`; `getCompositeIndices(): array` without an array
    shape; `getUseTraits()` / `getImplementsInterfaces()` comma-separated strings → `list<class-string>`;
  - eight `@internal` members (`rename()`, `cleanupForExport(mixed &$data)` static by reference, `generateClassFiles()`,
    `isWritable()`, `getDefinitionFile()`, `getPhpClassFile()`, …) in a public interface → split into a public read
    interface and an internal writer interface.
- `SelectOptionsInterface` is implemented only by generated enums (`lib/DataObject/ClassBuilder/SelectOptionsEnumBuilder.php:51`)
  → `extends \BackedEnum` so `from()` / `tryFrom()` / `cases()` are part of the contract; `getValues()` returns
  `string[]` → `list<string>`.
- `OwnerAwareFieldInterface`: `_setOwner(mixed)` / `_getOwner(): mixed` → a union of the real owner types
  (`Concrete|Localizedfield|Fieldcollection\Data\AbstractData|Objectbrick\Data\AbstractData|…`); the underscore
  prefix exists to avoid clashing with generated getters → keep it, but document that.
- `PreGetValueHookInterface::preGetValue()`: generated getters treat `null` as "no override" (`ClassDefinition/Data.php:475-485`),
  so a hook cannot force `null`, and the hook is skipped in admin (`\Pimcore::inAdmin()`) → document both in the
  interface, or return a sentinel / `?callable`.
- `ImportDataServiceInterface` (`stdClass $resolverConfig`) has no implementer or caller in this repo → deprecate (with a
  `trigger_deprecation()` note; PHP 8.4's `#[\Deprecated]` does not apply to classes or interfaces), or type the config
  as a DTO / array shape.
- `LazyLoadedFieldsInterface`: `isAllLazyKeysMarkedAsLoaded()` is `@internal` in a public interface; lazy keys are
  built by string concatenation with `LAZY_KEY_SEPARATOR` → a small value object.

### [ ] I-134 – Tests

- Classification store: password key hashed for the second changed key, for a newly activated group and for a new
  object (C-43); mutual fallback languages and the "last chain wins" case (B-245); inherited multiselect / table values
  through `getItems()` (B-246); calculated-value key read in the frontend context (B-247); `removeGroupData()` /
  `setGroupCollectionMapping()` alone followed by save and reload (B-248); deleted key config read (B-249).
- `DataObject\Listing` with `setObjectTypes([OBJECT_TYPE_OBJECT])` and an `OR` condition (B-250).
- `DefinitionModifierTest`: `insertFieldsFront()` into `localizedfields`, a field inside a `block`, and
  `getFieldDefinitions()` after `appendFields()` / `removeField()` on a class (B-251).

---

## Q – DataObject `AbstractObject/**`, `Concrete/**`, `Folder/**` (Scope 13)

> `AbstractObject/Dao.php`, `Concrete/Dao.php` and `Concrete/Dao/InheritanceHelper.php` were processed in Scope 6
> (I-73, I-76, I-78) and are skipped.

### [ ] I-135 – `DataObject\Folder\Dao`: strictness and an empty subclass

**File:** `models/DataObject/Folder/Dao.php:1-28`; `config/dao-classmap.php:57`

- No `declare(strict_types=1)`, unlike `DataObject/Folder.php`. It has no code of its own, so it can be enabled right
  away, independently of the `AbstractObject\Dao` prerequisites in I-73. Note that strictness is per file, so this
  does not make the inherited methods strict.
- The class exists only so the class map / `@property Folder $model` hint resolve a folder-specific DAO. It is
  `@internal` → `final class Dao`, so no project can rely on extending it, and folder-only persistence logic (if any is
  ever needed) has one obvious place.
- Alternative: map `DataObject\Folder` to `AbstractObject\Dao` in `config/dao-classmap.php` and deprecate the empty
  class. That is not worth the BC risk while `Folder::getDao()` is documented to return `Folder\Dao` (`Folder.php:24`
  `@method Dao getDao()`), so keep the class and make it final.

---

## R – DataObject `Listing/**`, `Fieldcollection/**`, `Localizedfield/**` (Scope 14)

> Scanned and skipped files: see the Scope 14 notes in [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md). The `strict_types`
> gap of `Listing/Dao.php`, `Fieldcollection/Data/Dao.php` and `Fieldcollection/Definition/Dao.php` is part of I-73;
> `Listing\Dao::getCount()` is in I-79; `@unlink()` and DDL quoting are in I-83. They are not repeated.

### [ ] I-136 – `Fieldcollection\Definition`: share the definition lifecycle with `Objectbrick\Definition`

**File:** `models/DataObject/Fieldcollection/Definition.php:47-313`

- `getByKey()` uses an exception for control flow (`throw new Exception('FieldCollection in registry is not valid')`
  and catching it right away) → `RuntimeCache::isRegistered()` / `get()` plus an `instanceof` check, and an early return.
- `save()` / `delete()` duplicate the brick code: the parent-class regex, the "for each class, for each field
  definition, if `Fieldcollections` and allowed" loop (twice in this file), event dispatching for add / update, and
  `isWritable()` / `getDefinitionFile()` / `getPhpClassFile()`. Move them into `FieldcollectionObjectbrickDefinitionTrait`
  (or an abstract `AbstractContainerDefinition`) with hooks for the table names. That also gives collections the
  brick validations they lack (B-254).
- The class-loop → a private `getClassesUsingThisCollection(): iterable<ClassDefinition>` that is used by `save()`
  and `delete()`.
- `extractDataDefinitions()`: `method_exists($existing, 'addReferencedField')` → `instanceof Data\Localizedfields`
  (the only implementer). The `@internal` tag on a protected method has no effect.
- `isWritable()` reads `$_SERVER['PIMCORE_CLASS_DEFINITION_WRITABLE']` directly → an injected parameter (container
  env). The same applies to classes and bricks.
- `isForbiddenName()` compares with `in_array()` without strict mode → `in_array(…, true)`. The key regex is
  duplicated in the brick and in C-32's proposal → one constant (`public const string KEY_PATTERN`).
- `generateClassFiles()` clones with DeepCopy, `unset()`s the typed `fieldDefinitions`, and exports with `var_export()`.
  I-83 covers the exporter; also note that the `unset()` leaves an uninitialised typed property on the clone.
- PHP 8.4: `getByKey()` returns `?static`/`?self`; `isWritable()` could be a property hook. Not required.

### [ ] I-137 – `Fieldcollection\Definition\Dao` and `Definition\Listing`

**Files:** `models/DataObject/Fieldcollection/Definition/Dao.php:26-110`; `models/DataObject/Fieldcollection/Definition/Listing.php:22-78`

- `Dao`: typo `$protectedColums`; superfluous parentheses `([$table])`; `$columnsToRemove = $existingColumns` is a
  plain alias. `createUpdateTable()` mixes the store table and `Localizedfields::classSaved()` → split into
  `createStoreTable()` / `updateStoreColumns()` / `notifyChildren()`. `getTableName()` and `getLocalizedTableName()` repeat
  the prefix, and `Localizedfield\Dao::getTableName()` (`:48-57`) builds the same localized name again → one static
  name builder used by both DAOs (that removes the risk that they diverge, and B-253 needs it for the drop).
- `Dao` is `@internal` and not extended in core → `final`.
- `Listing` has no `@internal`, no interface and loads with a bare `include` → load through `getByKey()` so that the
  custom-folder precedence, the runtime cache and validation are applied once (C-45, B-252, B-256). Return `list<Definition>`.
- `loadNames()` does not need to touch the files twice; `loadFileNames()` → `Symfony\Component\Finder\Finder` or a
  guarded `glob()`. A `\Generator` would avoid loading all definitions at once in the commands.

### [ ] I-138 – `Fieldcollection\Data\AbstractData` and `Data\Dao`

**Files:** `models/DataObject/Fieldcollection/Data/AbstractData.php:29-161`; `models/DataObject/Fieldcollection/Data/Dao.php:29-97`

- `AbstractData`:
  - `$object` is typed `Concrete|ElementDescriptor|null`, but `setObject()` / `getObject()` accept and return only
    `?Concrete`. Unlike `Localizedfield::setObject()` (`:225-229`), there is no descriptor handling. Narrow the property
    to `?Concrete`, or handle the descriptor in both.
  - `__wakeup()` is dead code: `object` is always excluded in `__sleep()`, so `$this->object` is `null` on wake-up.
    Remove it, and move `__sleep()` to `__serialize()` / `__unserialize()` with an explicit field list (I-75).
  - `isAllLazyKeysMarkedAsLoaded()` calls `getObject()` twice → `return $this->getObject()?->isAllLazyKeysMarkedAsLoaded() ?? true;`.
  - `get()` / `set()` build method names dynamically and return `mixed` (the setter returns `static`). Validate the
    field against the definition (clear exception instead of "Call to undefined method"), `set(): static`.
  - `in_array($key, $blockedVars)` → strict; `getLazyLoadedFieldNames()` loads the definition on every serialization →
    memoise per type.
  - `protected string $type = ''` on the abstract class: an empty type can never resolve → `abstract public function getType(): string`
    or a `const TYPE` in the generated class.
- `Data\Dao::save()`:
  - `$params = array_merge($params, […])` inside the field loop rebinds the method argument. Once one relation sets
    `forceSave`, every following field gets it too → build a fresh `$fieldParams` per field.
  - The custom-persisting fields get no `owner` / `fieldname` / `isUpdate`, and the column fields get no `context`. The
    brick DAO passes them (`Objectbrick/Data/Dao.php:75-98`), and `Fieldcollection\Dao::load()` passes
    `context.object`. Use one helper that builds the context for load and save.
  - `getDefinition()` (→ `getByKey()`) is called twice per save; `$this->model instanceof DirtyIndicatorInterface` is
    always true (`AbstractData` implements it); `$getter` is computed for fields that do not use it.
  - The value is read through the generated getter (`preGetData()`, `EncryptedField` unwrapping) and written back
    through `set()` after the resource round trip – the same pattern as `Concrete\Dao` (`:212-247`). Document why the
    round trip is needed, or read with `getObjectVar()` the way the brick DAO does for its object vars (`:273`).
  - `@internal` and not extended in core → `final`.

### [ ] I-139 – `DataObject\Listing\Dao`

**File:** `models/DataObject/Listing/Dao.php:30-116`

- `load()` calls `DataObject::getById()` per id (N+1; every miss of the runtime cache is a full object load with its
  own queries) → fetch the rows of one page in one query and hydrate from them, or at least warm the cache for the whole
  id list in one call. Return `list<DataObject>`; log skipped ids (B-256).
- `loadIdList()` selects `objects.type AS type` and then reads only the first column → select only the id (the extra
  column makes `GROUP BY` / `DISTINCT` listings harder to reason about), or document why it is there.
- `getQueryBuilder(...$columns)`: the docblock says `string|string[]|null`, but the method is variadic → `string ...$columns`.
  The `// apply joins` comment, the `else` after `return` in `getCount()`, and "specicifies" / "document ids" in the
  docblocks (copied from the document listing) → clean up.
- `applyJoins()` is a hook for `Listing\Concrete\Dao` that returns `static` and is never chained → `void`, `abstract`
  hook name or an explicit `@internal` extension point.

### [ ] I-140 – `Localizedfield\LanguageTableDoesNotExistException`

**File:** `models/DataObject/Localizedfield/LanguageTableDoesNotExistException.php:19-25`;
`models/DataObject/Localizedfield/Dao.php:244`, `:283`, `:516`; `models/DataObject/AbstractObject.php:557-589`

- The class implements Doctrine's `RetryableException` marker so that the retry loop in `AbstractObject::save()`
  restarts the transaction. It therefore also gets the random 100–500 ms back-off meant for deadlocks, and its
  semantics depend on a DBAL marker interface meant for driver errors. Once B-179 moves table creation out of the
  save path, the exception has no purpose → deprecate it (`trigger_deprecation()`), or give it its own
  `RetryableSaveException` contract that `AbstractObject::save()` handles explicitly without sleeping.
- Until then: `final`, `@internal`, a constructor that takes the table name and language and builds the message
  (`'missing table created, start next run ... ;-)'` is repeated in three places).

### [ ] I-141 – Tests

- Collection with a field `objectId` / `loadedLazyKeys`: the save must be rejected (C-44).
- The same key in `var/classes` and `config/pimcore/classes` with different fields: `classes-rebuild` must keep the
  columns and data of the custom definition (C-45).
- `new Definition()` saved for an existing key with an added localized field after `getByKey()` in the same process:
  the localized column exists; `getByKey()` after `delete()` returns `null` (B-252).
- Delete a collection that has localized fields and was removed from one class earlier, re-create it with the same
  key: no tables or items left over, `allowedTypes` cleaned (B-253).
- Key of 40 characters, and a `localizedfields` inside a `block`: rejected before any file is written (B-254).
- `getByKey('../x')` returns `null` without including anything (B-255).
- `Listing\Dao::load()` with a deleted id in the middle of the page (B-256).

---

## S – DataObject `Classificationstore/**` configuration models (Scope 15)

> Scanned and skipped files: see the Scope 15 notes in [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md). The `strict_types`
> gap of the 13 DAOs is part of I-73; `Classificationstore\Dao` bound parameters are in I-76 and its delete / re-insert
> in I-82; `Classificationstore::getGroups()` (N queries) is in I-130. They are not repeated.

### [ ] I-142 – `KeyConfig` / `GroupConfig` / `CollectionConfig` / `StoreConfig`: one config lifecycle

**Files:** `models/DataObject/Classificationstore/KeyConfig.php:31-328`; `GroupConfig.php:32-301`;
`CollectionConfig.php:31-277`; `StoreConfig.php:28-154`

- `getById()`, `getByName()`, `save()`, `delete()`, `getCacheKey()` and `removeCache()` are copied three times, and
  already diverged (`offsetUnset()` vs `set(null)` – B-257; `KeyConfig` clears `DefinitionCache`, the others do not;
  `StoreConfig` has no cache and no `getByName()` store argument). Move them into a trait or an abstract
  `AbstractConfig` with `protected const string CACHE_PREFIX` and event-class hooks; the cache helper fixes B-257 /
  B-258 once.
- The save / delete event pairs (`PRE_ADD` / `PRE_UPDATE` / `POST_*`) are repeated in every `save()` → one
  `dispatchSaveEvents(bool $isUpdate, callable $write)` helper.
- Signatures: `getById(int $id, ?bool $force = false)` → `bool $force = false`; return `?self` → `?static` not needed
  (classes are `final`) but the declared return types use the class name (`?KeyConfig`) → `?self`; fluent setters return
  the class name → `static`. Setters are inconsistent: `setName()` / `setDescription()` / `setId()` are fluent,
  `setType()`, `setStoreId()`, `setEnabled()`, `setCreationDate()` (`KeyConfig`) return `void`, `StoreConfig::setId()` returns
  `void` → all fluent (`static`).
- Nullability mismatches between property, setter and schema: `KeyConfig::$title` is `?string`, `setTitle(string)`, column
  `NOT NULL DEFAULT ''`; `StoreConfig::$name` / `$description` are `?string`, the setters take `string`;
  `GroupConfig::$parentId` is `?int`, `setParentId(int)`, column `NOT NULL DEFAULT 0` → `int $parentId = 0`;
  `CollectionConfig::$description` defaults to `''`, the others to `null`. `$name` / `$type` are uninitialised typed
  properties → `''` defaults or constructor parameters (B-262).
- `KeyConfig::save()` derives `title` from the definition JSON (`json_decode($this->definition, true)` on every save)
  → `json_decode(…, flags: JSON_THROW_ON_ERROR)` with a clear exception for invalid JSON, and document that `setTitle()`
  is overwritten on save (or remove the setter).
- `getEnabled(): bool` → `isEnabled()` (keep the old name with `#[\Deprecated]`), consistent with `KeyGroupRelation`.
- `removeCache()` is private but called as `self::removeCache()` → `$this->removeCache()`.
- `hasChildren()` (`GroupConfig`) is only available through `@method` / `__call` → a real method.
- PHP 8.4: `public private(set) ?int $id`, `creationDate`, `modificationDate` (set by the DAO only); `#[\Override]` where
  `AbstractModel` methods are overridden.
- Docblocks: `/** The key */` on one line, `Pseudo column for title`, empty `*` lines, "Deletes the key value group
  configuration" on `StoreConfig::delete()`, `@throws Exception` on methods that cannot throw it → clean up.

### [ ] I-143 – Config DAOs (`KeyConfig\Dao`, `GroupConfig\Dao`, `CollectionConfig\Dao`, `StoreConfig\Dao`)

**Files:** `models/DataObject/Classificationstore/KeyConfig/Dao.php:28-146`; `GroupConfig/Dao.php:28-141`;
`CollectionConfig/Dao.php:28-129`; `StoreConfig/Dao.php:28-123`

- Four copies of `getById()` / `getByName()` / `save()` / `create()` / `update()` / `delete()` → one abstract
  `AbstractConfigDao` with `TABLE` constant and a column map.
- `update()` calls `getValidTableColumns()` inside the loop for every property → hoist it. The `is_array()` /
  `is_object()` → `Serialize::serialize()` / `json_encode()` branches are dead (no config property is an array or object;
  the `type == 'select'` branch in `KeyConfig\Dao` can never run) → remove.
- `save()` = `create()` (`INSERT []`) + `update()` → one `insert()` with all columns (B-262), `lastInsertId()` cast once.
- `KeyConfig\Dao::getByName()` concatenates `quote($name)` and `$storeId` into the SQL → bound parameters, like the other
  DAOs (I-76 lists only `Classificationstore\Dao`).
- `getById(?int $id = null)` with `$id != null` (`0` is treated as "not given") → `?int $id = null` and `$id !== null`.
- `KeyConfig\Dao::update()` deletes relations as a side effect of mapping the `enabled` column → an explicit
  `KeyGroupRelation\Dao::deleteByKeyId()` called from the model (B-260).
- Booleans are cast by hand per DAO (`(bool)$data['enabled']`, `(bool)$data['mandatory']`) → one hydration helper with
  a cast map (ids, dates, booleans) – prerequisite for `strict_types` (I-73).
- `@internal` and not extended in core → `final class Dao`.

### [ ] I-144 – The six listings and their DAOs

**Files:** `models/DataObject/Classificationstore/{KeyConfig,GroupConfig,CollectionConfig,StoreConfig,KeyGroupRelation,CollectionGroupRelation}/Listing.php`;
the six `Listing/Dao.php`

- The six `Listing` classes are identical except for the item type (`getList()` / `setList()` + docblocks) → a shared
  trait or `@template T` base; `@method …|false current()` → correct type.
- Hydration differs per DAO: `setValues($row)` (keys, groups – nulls passed on), `assignVariablesToModel()` (relations –
  nulls skipped), `getById()` per id (collections, stores – N+1 queries and persistent-cache round trips, `null`
  entries) → one way: hydrate from the selected row with the cast helper of I-143.
- `load()` docblocks say "group configs" in the key-relation and collection-relation DAOs → fix; return `list<…>`.
- Comma joins with the join predicate appended to the user condition (`KeyGroupRelation`, `CollectionGroupRelation`) →
  `INNER JOIN … ON` with aliases and qualified columns; one FROM/JOIN builder shared by `load()`, `getTotalCount()`,
  `getDataArray()` (B-264, B-265).
- `getTotalCount()` swallows exceptions in five of six DAOs (B-265) → remove the `try/catch`.
- `KeyConfig\Listing`: `includeDisabled` → an enum filter (`EnabledFilter::Enabled|Disabled|All`, B-261).
- Consider porting the listings to `QueryBuilderHelperTrait` like the other model listings, which also gives bound
  `setCondition()` parameters a single code path.

### [ ] I-145 – `KeyGroupRelation` and `CollectionGroupRelation`

**Files:** `models/DataObject/Classificationstore/KeyGroupRelation.php:28-171`; `KeyGroupRelation/Dao.php:29-101`;
`CollectionGroupRelation.php:28-112`; `CollectionGroupRelation/Dao.php:30-106`

- The models mix the persisted relation columns (`groupId`, `keyId` / `colId`, `sorter`, `mandatory`) with denormalised
  read-only data of the joined key / group (`name`, `description`, `definition`, `type`, `enabled`, `groupName`).
  Setting `setName()` on a relation and saving it does nothing. Split into the relation entity and a read model, or at
  least mark the joined fields read-only (`public private(set)`) and document them.
- Uninitialised typed properties (`name`, `definition`, `type`, `groupName`, `CollectionGroupRelation::$sorter`) →
  defaults (B-264, B-265).
- `save()` / `delete()` exist only through `@method` / `__call` → real methods; relation changes dispatch no events
  (config changes do) → `KEY_GROUP_RELATION_*` / `COLLECTION_GROUP_RELATION_*` events, or document why not.
- Argument order differs between the factory and the DAO (`getByGroupAndKeyId($groupId, $keyId)` →
  `Dao::getById($keyId, $groupId)`; `getByGroupAndColId($groupId, $colId)` → `Dao::getById($colId, $groupId)`) → same
  order everywhere, named arguments at the call sites.
- `KeyGroupRelation` is passed to `Service::getFieldDefinitionFromKeyConfig(KeyConfig|KeyGroupRelation)` as a duck-typed
  `KeyConfig` → a small `KeyDefinitionInterface` (`getName()`, `getType()`, `getDefinition()`, `getDescription()`)
  implemented by both.
- `create(): self` returns `new self()` without arguments → `create(int $groupId, int $keyId, …)` that returns a
  complete relation.
- The DAOs `SELECT *` from a join (duplicate columns, see I-76 / B-148) → explicit column lists; `final` DAOs.

### [ ] I-146 – `Group` and `Key` value objects

**Files:** `models/DataObject/Classificationstore/Group.php:22-93`; `Key.php:22-65`

- Constructor property promotion with `readonly` (`public function __construct(private readonly Classificationstore
  $classificationStore, private readonly GroupConfig $configuration)`), the classes are `final` → `protected` members /
  methods → `private`.
- `Group::getKeys()` loads the relations (which already contain every key column from the join) and then calls
  `KeyConfig::getById()` per relation (N lookups) → build the `KeyConfig` from the relation row or load all keys with
  one listing by ids.
- `array_map([$this, 'getKeyByKeyGroupRelation'], …)` → first-class callable `$this->getKeyByKeyGroupRelation(...)`;
  spreading the relation array into a variadic just to type-check it → `array_map()` over the list.
- `'groupId = ' . $this->configuration->getId()` → bound parameter; `getKeyGroupRelationListing()` exists only as a test
  seam → inject a factory or keep it `private`.
- `Key::getValue(?string $language = 'default', …)`: `null` has no meaning → `string $language = 'default'`; the return
  type of `getFieldDefinition()` must follow `Service` (B-265).

### [ ] I-147 – Schema (`classificationstore_*`)

**File:** `bundles/InstallBundle/dump/install.sql:617-694` (plus a migration)

- No unique index on `(storeId, name)` for keys, groups and collections, nor on `stores.name`: `getByName()` returns an
  arbitrary row for duplicates, and nothing in the models prevents creating them → unique indexes after a
  duplicate-check migration, and a validation in `save()` with a clear exception.
- `classificationstore_collectionrelations.colId` has no foreign key (B-263) → `FOREIGN KEY (colId) REFERENCES
  classificationstore_collections (id) ON DELETE CASCADE`; groups / keys / collections have no foreign key to the
  store.
- `classificationstore_collections.name` is `VARCHAR(255)` without index (the other tables use `VARCHAR(190)` with an
  index) although `getByName()` queries it.
- Nullability mismatches with the models: `storeId INT NULL` (model `int`), `keys.type` / `definition` `NULL` (model
  non-nullable), `keys.title NOT NULL` (model writes `null`), `relations.sorter` / `collectionrelations.sorter` `NULL`
  (model `int`) → make the columns `NOT NULL` with defaults after a data migration, so that B-262 cannot recur.
- Mixed id signedness: `stores.id` is signed `INT`, `storeId` columns are signed, group / key ids unsigned → align.

### [ ] I-148 – Tests

- Update a collection, then `CollectionConfig::getById()` / `getByName()` and the collection listing in the same process
  return the collection (B-257).
- `KeyConfig::getByName('a')`, rename to `b`, save: `getByName('a')` returns `null`; a `PRE_UPDATE` listener that reads
  the config does not leave a stale cache entry (B-258).
- `CollectionGroupRelation::getByGroupAndColId()` returns the name of the related group when collection id ≠ group id
  (B-259).
- Save a disabled key that is in a group: the documented behaviour (relations kept or removed on the transition only)
  holds (B-260); `setIncludeDisabled(true)` returns enabled and disabled keys (B-261).
- `KeyConfig::create()` / a key without type does not break `KeyConfig\Listing::load()` (B-262).
- Delete a collection: its relation rows are gone; delete a store with configs: refused or fully cleaned (B-263).
- `KeyGroupRelation\Listing` with `setResolveGroupName(true)` and a condition on `name`: group name filled, no
  ambiguous-column error (B-264).
- The existing tests (`tests/Model/DataType/ClassificationStore/*`, `tests/Model/Inheritance/ClassificationstoreTest.php`)
  only use the configs as fixtures; none covers the config models themselves.

---

## T – DataObject `ClassDefinition/**` (Scope 16)

> Notes
> - Scanned and skipped files: see the Scope 16 notes in [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md) (C-46–C-51,
>   B-266–B-299).
> - Missing `declare(strict_types=1)`: `ClassDefinition/CustomLayout/Dao.php`, `CustomLayout/Listing/Dao.php` → add to
>   I-73. All other scanned files already declare it.
> - Applies to every scanned data type and not repeated per item: add `#[\Override]` on the many overridden `Data`
>   methods (`getDataForResource()`, `checkValidity()`, `isEqual()`, `synchronizeWithMainDefinition()`, …).

### [ ] I-149 – `CustomLayout` persistence: dead code, missing events, O(n²) lookups

- `getInfoDocBlock()` (`CustomLayout.php:199-212`) is unused – custom layouts generate no PHP code.
- `getIdentifier()` / `Dao::getLatestIdentifier()` ignore `$classId` and return a random UUID v4: rename or document.
- Only `PRE_ADD` / `PRE_UPDATE` events exist (`lib/Event/DataObjectCustomLayoutEvents.php:27-34`); add post-save and
  delete events, `delete()` dispatches nothing.
- `exists()` (on every `save()`), `getByName()`, `getNameById()` load the whole listing and call `getById()` per id →
  look up by id / name directly (`getDataByName()`), `array_find()` in the DAO.
- `Listing\Dao::load()` and `getTotalCount()` duplicate the loading loop; `getTotalCount()` swallows every exception
  as `0`.
- `Dao::getById()` still `unserialize()`s a string `layoutDefinitions` (`Dao.php:69-70`) without `allowed_classes`;
  `save()` only writes arrays → remove the legacy branch (I-75 family). Error message "Predefined metadata config"
  (`Dao.php:99`) is a copy-paste; `Dao.php:141` has a Hungarian TODO comment to resolve or translate.

### [ ] I-150 – Resolvers, `ClassDefinition\Service` helpers, layout traits, `CountryOptionsProvider`

- `OptionsProviderResolver::$providerCache` and `PathFormatterResolver::$formatterCache` are unused public statics;
  `OptionsProviderResolver` compares modes with `==`; only `CalculatorClassResolver` is `final`.
- Resolvers return `?object` instead of the interface type → `@var` casts at every caller; narrow the return types.
- `Service::removeDynamicOptionsFromLayoutDefinition(mixed &$layout)` / `removeDynamicOptionsFromArray()` take
  unnecessary references; the `setDoRemoveDynamicOptions(true/false)` pairs (`Service.php:60-62`, `:162-164`, `:218-220`,
  `:231-237`) lack `try/finally` (same pattern as B-175); `updateTableDefinitions()` concatenates `show columns from`
  (I-76).
- `LabelTrait::setLabelAlign('')` silently ignores the value instead of resetting it.
- `CountryOptionsProvider::getOptions()` does not `trim()` the `restrictTo` items (`"DE, AT"` drops `AT`); use a readonly
  promoted constructor (`:27-32`).

### [ ] I-151 – Advanced relations: shared base, stored index, source lookup, LIKE escaping

- `AdvancedManyToManyObjectRelation` / `AdvancedManyToManyRelation` duplicate `save()`, `delete()`, `preGetData()`,
  `setColumns()`, `enrichLayoutDefinition()` and the diff handling line for line → shared trait / base class.
- The object variant loads metadata with `$index = $key + 1` (`:158`), the element variant uses the stored
  `$element['index']`; both rely on gap-free indexes → use the stored index in both.
- `DataObject::getById($relation['src_id'])` per row – the source is always the owning object (resolve once).
- Dead variables (`AdvancedManyToManyRelation.php:176`, `:387`); `$context['containerType']` read without `isset()`
  (`AdvancedManyToManyObjectRelation.php:477`, `:561`); duplicate id in the `checkValidity()` message (`:363-369`).
- `ownername LIKE` patterns from container names without `Helper::escapeLike()` (`_` acts as wildcard).
- `denormalize()` uses `new ObjectMetadata` / `new ElementMetadata` instead of the `pimcore.model.factory` → overrides
  bypassed.
- `getColumnKeys()` rewrites `$this->columnKeys` inside a getter → derive from `columns` / sync in `setColumns()`.

### [ ] I-152 – Relation helpers, listing filters and performance

- `addListingFilter()` (`ManyToManyObjectRelation.php:699-712`, `ManyToManyRelation.php:744-764`) binds
  `'%,' . $data . ',%'` without `escapeLike()` / `(int)` cast; array input → "Array to string conversion"; other
  operators fall back to the generic filter on a comma-list column.
- Exception messages with `print_r($data, true)` (`ManyToManyObjectRelation.php:162`) – large, leaks data into logs.
- `ManyToOneRelation.php:549` quotes the type column with hand-written backticks → `quoteIdentifier()`.
- Allow-relation traits: `!$document instanceof Document` is dead (typed parameter); `Logger::debug('allowed:' .
  $allowed)` concatenates a bool on every validation; `AllowDocumentRelationTrait` / `AllowAssetRelationTrait` compute
  allowed types differently → unify with `array_filter(array_column(…))` + `in_array(…, true)`.
- `PositionSortTrait::sort()` reads `position` without `?? 0`; make it a static callable.
- `ReverseObjectRelation::preGetData()` queries and loads every owner on every getter call (no lazy-key caching);
  `loadData()` of the many-to-many types calls `getById()` per relation (N+1) → batch prefetch;
  `filterUnpublishedElements()` → `array_filter()` / `array_any()`.

### [ ] I-153 – Decimal / numeric configuration duplicated three times; `Numeric` / `QuantityValue` validation cleanup

- `isDecimalType()`, `buildDecimalColumnType()`, the `DECIMAL_*` constants, decimal setters and `toNumeric()` are
  copied in `QuantityValue`, `Numeric`, `NumericRange` → one trait.
- Unreachable "scale > precision" message (`QuantityValue.php:224`, `Numeric.php:267`; `NumericRange.php:197-205` is
  correct); `is_numeric()` on already typed `?int` in `setDecimalSize()` / `setDecimalPrecision()`.
- `getColumnType()` ignores `unsigned` although validation enforces it → consider `UNSIGNED` columns.
- `QuantityValue::checkValidity()` repeats the non-numeric check (`:296-301`) with another message; remove.
- `Numeric::setDefaultValue('')` keeps the previous value (unlike `QuantityValue.php:94-101`); redundant `?? null`
  (`Numeric.php:462`, `Checkbox.php:198`).
- `NumericRange`: integer detection via `(string)$data` (object `__toString()`, `:405`); messages format float thresholds
  with `%d` (`:415`, `:421`, `:429`).
- `Numeric` / `Slider` render floats in scientific notation in `getVersionPreview()` / `getForCsvExport()`.

### [ ] I-154 – `QuantityValueRange` should extend `AbstractQuantityValue`; float unit conversion in filters

- `QuantityValueRange.php:27-108`, `:326-351` copy the unit properties, `configureOptions()` and `__set_state()` from
  `AbstractQuantityValue`; `unitWidth` is typed differently (`string|int = 0` vs `string|int|null = null`).
- `decimalPrecision` is stored but has no effect on the `double` columns; `getDataFromEditmode()` casts `''` to `0.0`
  (only `is_null()` is checked).
- `AbstractQuantityValue` filters compare converted floats with `=` / `!=` (`0.30000000000000004` never matches) → compare
  in the base unit or with a tolerance window; `Unit::getById($value[1])` with request data in a strict file (numeric
  JSON id → `TypeError`, sender format **not verified locally**).

### [ ] I-155 – `RgbaColor`, `Checkbox`, `BooleanSelect`, `InputQuantityValue` small items

- `RgbaColor`: `parent::checkValidity($data, $omitMandatoryCheck)` drops `$params`; `getFilterConditionExt()` puts an array
  into SQL as `Array`, uses `$name` unquoted in the `cskey_` branch and does not escape `LIKE`;
  `marshalBeforeEncryption()` / `unmarshalAfterDecryption()` use `Serialize::unserialize()` without `allowed_classes` (new
  instance of I-75) – reuse `normalize()` / `denormalize()`.
- `Checkbox::getVersionPreview(false)` returns `''` (indistinguishable from `null`); dead commented type check (`:121-124`).
- `BooleanSelect::setOptions()` is a silent no-op; stale `@TODO` (`:216`); `match` in `getDataForEditmode()` /
  `getDataFromResource()`; mandatory semantics belong with I-130.
- `InputQuantityValue` unit column `varchar(50)` vs `QuantityValue` `varchar(64)` → align; the class-level TODO about
  duplication with the parent is still open.

### [ ] I-156 – Select-like types: duplicated "dynamic options" logic, enums, unenforced restrictions

- `jsonSerialize()` / `resolveBlockedVars()` with the "remove dynamic options" logic are copy-pasted 7 times (`Select`,
  `Multiselect`, `Language`, `Languagemultiselect`, `Gender`, `User`, …); `configureOptions()` is identical in
  `Language` / `Languagemultiselect`; `restrictTo` getter/setter identical in `Country` / `Countrymultiselect` → traits.
  `Country` re-uses `DataWidthTrait` already used by `Select`.
- `restrictTo` (Country*) and `onlySystemLanguages` (Language*) are not enforced server-side (see B-269); `Gender` uses
  hard-coded labels instead of translation keys.
- `Country::setRestrictTo()` returns `void` (others `static`); `Country(multiselect)::getOptionsProviderClass(): string`
  narrows the trait's `?string` and ignores the stored property.
- `OptionsProviderTrait`: validate `optionsProviderType` against `OptionsProviderInterface::TYPES` or introduce a backed
  enum; PHP 8.4 `public private(set)` for the provider properties.
- `Multiselect::preSave()` throws a generic `Exception` with HTML in the message; `array_reduce()` → `array_any()`.

### [ ] I-157 – Date/time types and `Wysiwyg`: column types, duplication, simpler comparisons

- `Datetime::setColumnType()` / `DateRange::setColumnType()` accept any string that later reaches `ALTER TABLE` →
  restrict to `bigint(20)` / `datetime` (enum or constants) and compare with constants instead of `== 'datetime'`.
- `getDateFromTimestamp()` duplicated in `Datetime` / `DateRange` → `Carbon::createFromTimestamp()`.
- `Time`: `isEarlier()` / `isLater()` / `toTimestamp()` parse `H:i` via `strtotime()` relative to `time()` → a plain
  string comparison of validated `HH:MM` values; `isEmpty()` treats invalid strings as empty (misleading "mandatory"
  message).
- `Wysiwyg::checkValidity()` loads every linked element on each save and blocks saving any object whose text links to a
  deleted element → dependency-table check or warning; `getDataForQueryResource()` keeps `<a>` / `<img>` tags in the
  query table → plain text; `preGetData()` reads `$params['data']` without `isset`.

### [ ] I-158 – `Table` / `StructuredTable` code quality

- `Table::getGetterCode*()` (4 methods, ~160 lines, `:477-635`) copy `Data::getGetterCode*()` only to append `?? []` and
  have already drifted (no `getPreGetValueHookCode()` in the brick variant) → protected fallback hook in the parent;
  `$this instanceof TypeDeclarationSupportInterface` is always true.
- `Table::$data` (default data) is never read server-side; query/search formats (`implode('|')`, `"\n"`) do not escape
  delimiters; `setColumnConfigActivated()` / `setColumnConfig()` return `void`; type `$columnConfig` as
  `array<int, array{key: string, label: string}>`; `getDataForEditmode()` returns non-array data under `?array`.
- `StructuredTable`: redeclares `TypeDeclarationSupportInterface`; `setCols()` / `setRows()` duplicated; uses `$this->cols`
  in one place and getters elsewhere; `calculateDbColumns()` builds `stdClass` only to unpack it; `typeMapper()` → `match`
  with a default throw; document the `__row_identifyer` wire-format typo; `getDiffDataForEditMode()` assumes
  `$defaultData[0]`; stray docblock `v` on `$labelFirstCell`.

### [ ] I-159 – Media and Geo types: walkers, `rewriteIds()`, duplication

- `Hotspotimage`: five near-identical walkers over `marker` / `hotspots` (`:141-158`, `:198-214`, `:237-255`, `:314-332`,
  `:354-373`) → one private iterator; `doRewriteIds()` wipes hotspots, markers and crop when the image id is remapped
  (`:403-408`) – after a tree copy the image is the same picture; `rewriteIdsInDataEntries()` reads `['data']` without
  `isset`.
- `ImageGallery::getDataFromEditmode()` keeps `null` items (persisted as empty slots) → `array_filter()`; redundant
  `array_unique()` in `getCacheTags()`; `array_merge(...$list)` instead of merging in a loop.
- `Image::rewriteIds()` / `Video::rewriteIds()` return any asset type (`Asset::getById()`) into image/video setters and
  clear the field when the mapped id does not exist → typed lookup, keep the original on failure.
- `getFilterConditionExt()` reads `$params['name']` without fallback (`Hotspotimage.php:570`, `Image.php:279`).
- `ExternalImage`: `getDataFromResource()` always returns an object (never `null`, `''` URL persisted instead of `NULL`);
  `getDataFromEditmode()` passes mixed input to `?string`; `getDiffVersionPreview(string $data)` differs from siblings.
  No server-side URL fetch exists (no SSRF).
- `Geopolygon` / `Geopolyline` are identical apart from class name and field type → shared base; `Geobounds::isEqual()`
  compares floats with `===` and carries a Hungarian `// TODO float kezelése` while `Geopoint` uses an epsilon → unify;
  `AbstractGeo::$mapType` has no setter (dead or silently dropped config); `getDataForEditmode()` /
  `getDiffVersionPreview()` call `->getLatitude()` without `instanceof GeoCoordinates`; `getVersionPreview(mixed)` →
  `getDiffVersionPreview(?array)` `TypeError` for non-arrays.
- `Video.php:141`, `:215`: use `TYPE_ASSET` instead of the `'asset'` literal.

### [ ] I-160 – Data interface contracts and Scope 16 tests

- `OptimizedAdminLoadingInterface` is implemented (`ManyToManyRelation`, `ManyToManyObjectRelation`) but never checked
  in this repo; `CustomDataCopyInterface` and `CustomVersionMarshalInterface` are checked but have no core implementer →
  document as extension points (`@api`) or remove the dead dispatch.
- `DataContainerAwareInterface::preSave/postSave(mixed $containerDefinition)` → narrow to
  `ClassDefinition|Objectbrick\Definition|Fieldcollection\Definition`; `PreGetDataInterface` / `PreSetDataInterface` /
  `IdRewriterInterface` `mixed $container` → the container union already used by `CustomResourcePersistingInterface`
  (BC break → next major).
- `FieldDefinitionEnrichmentModelInterface::doEnrichFieldDefinition()` is public on the interface but `@internal` /
  `abstract protected` elsewhere; `suppressEnrichment()` is missing from the interface; the
  `FieldDefinitionEnrichmentInterface` docblock omits the `suppressEnrichment` case; `LazyLoadingSupportInterface` is
  called a "marker interface" but declares a method; garbled docblocks in `EqualComparisonInterface`,
  `ResourcePersistenceAwareInterface`, `QueryResourcePersistenceAwareInterface`.
- Tests to add:
  - Link to an object of a class without link generator renders (C-46).
  - Version preview of StructuredTable, advanced relations, ExternalImage and Link escapes `<`/`"` (C-47).
  - `QuantityValueRange` `0 … 1e9` saves; open-bound `NumericRange` / `QuantityValueRange` / `DateRange` round-trip
    (C-48, C-49); object with a deleted unit loads (C-50); keyed, reordered `Table` row round-trips (C-51).
  - One dirty-detection test per type for B-266; unpublished save with invalid e-mail / out-of-range number is
    rejected (B-272); `Geopoint(0, 0)` round-trips (B-295); `DECIMAL` value with 19+ digits round-trips (B-296).
  - Export a class with a `Gender` field, then call `getOptions()` in the same process (B-275); two fields `myUrl` /
    `my_url` are rejected on class save (B-280).

---

## U – DataObject `Data/**` field value objects (Scope 17)

> Notes
> - Scanned and skipped files: see the Scope 17 notes in [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md) (C-52,
>   B-300–B-315).
> - Missing `declare(strict_types=1)`: only `AbstractMetadata/Dao.php`, `ElementMetadata/Dao.php`, `ObjectMetadata/Dao.php`
>   (already listed in I-73).
> - Applies to every scanned value object and is not repeated per item: declare `Stringable` explicitly where
>   `__toString()` exists, add `#[\Override]` to the overridden `AbstractModel` / interface methods, and call
>   `markMeDirty()` only when a setter actually changes the value (today some setters compare, most do not).

### [ ] I-161 – Metadata items: explicit column API instead of `__call`, dead state, duplicated DAO code

- Replace the magic `get<Column>()` / `set<Column>()` dispatch with `getColumnValue()` / `setColumnValue()` (C-52); the
  current `__call()` implementations are copy-pasted between `ObjectMetadata` and `ElementMetadata`, return `mixed|void`,
  read `$args[0]` without a check and silently return `null` for any non-`get`/`set` method.
- `ObjectMetadata::$object` is never written (`setObject()` stores only the id) and only exists for `__sleep()` /
  `__unserialize()` BC → remove it together with the `__sleep()` override; use one `__serialize()` / `__unserialize()`
  pair in both classes (the mix of `__sleep()` + `__unserialize()` is hard to reason about, I-75).
- `setElement()` / `setObject()` call `markMeDirty()` twice; `ObjectMetadata::setElement()` exists only as an alias.
- Constructor asymmetry: `ObjectMetadata(?string $fieldname, …)` (required) vs `ElementMetadata(?string $fieldname = null,
  …)`; `ObjectMetadata::getFieldname(): ?string` vs `ElementMetadata::getFieldname(): string` (B-315).
- `AbstractMetadata\Dao::save()` / `load()` throw "Needs to be implemented by child class" → make the class and the two
  methods `abstract`. `ObjectMetadata\Dao` re-declares the `Helper\Dao` trait, `$tableDefinitions` and `getTablename()`
  identically to its parent → remove. Both child `save()` / `load()` bodies are the same apart from the type filter → one
  implementation in the parent with a `$destinationType` parameter.
- `Dao::save()` does one upsert per column and item → one multi-row insert per item (the field rows are deleted before,
  `AdvancedManyToManyObjectRelation.php:484`, `:498`). Load-side N+1 is I-82.

### [ ] I-162 – `object_metadata_*` schema

- `createOrUpdateTable()` only runs `CREATE TABLE IF NOT EXISTS`: nothing is ever updated → rename to `createTable()` or
  add a column/index diff like `ClassDefinition\Dao::update()`.
- `dest_id int(11) NOT NULL` is signed while `id` and every element id are `int(11) UNSIGNED`; `data text` (64 KB) for
  free-text metadata; the default charset is set without collation; DDL indentation mixes tabs and spaces.
- Seven single-column indexes (`fieldname`, `column`, `ownertype`, `ownername`, `position`, `index`, `dest_id`) next to a
  nine-column unique key whose prefix already serves the lookups → keep `dest_id` (reverse lookups) and measure the rest.
- The table name `'object_metadata_' . $classId` is built in three DAO places and in both advanced relation types → one
  helper.

### [ ] I-163 – Quantity and range value objects

- `QuantityValue` / `InputQuantityValue` / `QuantityValueRange::__toString()` and `AbstractQuantityValue::convertTo()`
  pull services from `Pimcore::getContainer()` (locale service, translator, `UnitConversionService`) inside value
  objects → one formatter service (also fixes B-298, B-306, B-307) and keep `__toString()` locale-free or delegating.
- `QuantityValueRange` creates two `NumberFormatter` instances per call; uses `ObjectVarTrait` again although the parent
  already does; calls `markMeDirty()` twice in the constructor; its `$unit` constructor parameter has no default
  unlike the siblings; its typed `$minimum` / `$maximum` have no default.
- `QuantityValueRange::getValue(int $step = 1): array` returns `range()` – surprising for a "value" and the root cause of
  C-48 → deprecate (`#[\Deprecated]`) in favour of `toArray()` / `getRange()`.
- `NumericRange` and `QuantityValueRange` duplicate the min/max accessors, `toArray()` and `__toString()` → shared trait.
- The resolved `Unit` object is serialized with every value (versions, cache, recycle bin) → stale abbreviation / factor
  after a unit change; serialize `unitId` only and resolve lazily (`getUnit()` already does).

### [ ] I-164 – `BlockElement`

- `renewReferences()` builds a new `DeepCopy` with two closures per call; the anonymous `Matcher` ignores its
  `$property` argument and decides per object type (`Video` / `Hotspotimage` / `ExternalImage` hard-coded) → a named
  matcher class with typed parameters and a list of descriptor-carrying properties, or let the value objects resolve
  their own descriptors (they already do in `__wakeup()`).
- The `<key>_blockElementRenewed` runtime-cache markers are never removed in long-running workers; document or scope them.
- `setLanguage()` duplicates `_setOwnerLanguage()` without marking dirty → deprecate.
- `getData()` has a hidden side effect (deep copy on first read after unserialize); expose `renewReferences()` as an
  explicit step called by `ClassDefinition\Data\Block` instead (see B-302).

### [ ] I-165 – `Link`

- `linktype` (`internal` / `direct`) and `internalType` (`document` / `asset` / `object`) are string literals → backed
  enums or class constants (I-74 family); `setPath()` repeats the assignment block four times.
- `getHref()` calls `getElement()` up to four times (`:349-354`) → one local variable; `getElement()` uses
  `Concrete::getById()` for objects, so links to object folders resolve to `null` → `Service::getElementById()`.
- `setValues()` dispatches to any `set*` method by input key (also `setPath`, `setElement`, `setObjectVar`) → an explicit
  allowlist of link fields.
- `getHtml()` / `__toString()` build HTML in the model → move rendering to a Twig helper (also the escaping in C-47).
- The `__unserialize()` BC shim is marked "Remove in Pimcore 12": track it with `#[\Deprecated]`-style notice or a
  migration that rewrites old rows.

### [ ] I-166 – Media and geo value objects

- `Hotspotimage::__wakeup()` / `Video::__wakeup()` resolve element descriptors eagerly: unserializing an object with a
  50-image gallery from the cache runs 50 element lookups even if the gallery is never read → resolve lazily in the
  getters. `Video::getData()` / `getPoster()` declare return types that exclude the `ElementDescriptor` the property may
  hold.
- `Hotspotimage`: the `@var array[]|null $crop` docblock is wrong (a flat `cropWidth` / `cropHeight` / `cropTop` /
  `cropLeft` map) → a small crop value object; the `cropPercent` block is duplicated (`:162-176`) and `$mediaConfig` is
  unused; the constructor copies arrays with loops (`array_values()`); `setCrop()` returns `void` while the sibling
  setters return `static`.
- `ImageGallery`: `IteratorAggregate` + `Countable` (B-313); validate `Hotspotimage|null` items in `setItems()`.
- `GeoCoordinates::__toString()` renders `"; "` and `Geobounds::__toString()` `" - "`-less halves for empty values → `''`;
  the constructor order (`latitude`, `longitude`) differs from the property order; consider `readonly` value semantics
  with `with*()` methods for coordinates.
- `ExternalImage::__toString()` → `$this->url ?? ''`.

### [ ] I-167 – `RgbaColor`, `StructuredTable`, `Consent`, `CalculatedValue`

- `RgbaColor`: `setHex()` / `setRgba()` / the constructor call `markMeDirty()` five times; `setHex()` throws a generic
  `Exception` → `InvalidArgumentException`; typed `$r`/`$g`/`$b`/`$a` have no defaults; `getCssRgba()` mixes ints and a
  float in an untyped array (document with `@return array{int,int,int,float}`).
- `StructuredTable`: `__toString()` and `getHtmlTable()` duplicate the table rendering; `substr($name, 3, strlen($name) -
  3)` → `substr($name, 3)`; `@return mixed|void` → `mixed` plus `BadMethodCallException` (B-314); offer explicit
  `getCell(string $row, string $col)` / `setCell()`.
- `Consent`: `getSummaryString()` formats with `date('r')` in the server timezone; `setConsent()` / `setNoteId()` use
  `!=` → `!==`.
- `CalculatedValue::setContextualData()` takes seven positional parameters (`'objectbrick'` callers pass the brick type
  as `$index` and the field name as `$position`) → named arguments at the call sites or a context value object; document
  the meaning of `index` / `position` per owner type.

### [ ] I-168 – Tests for Scope 17

- Advanced relation with metadata columns `value`, `data`, `fieldname`: save/reload keeps the values (C-52).
- `ObjectMetadata::setObjectId()` on a loaded object is persisted (B-300); localized `Geopolygon` point edited in place
  after a first save is persisted (B-301); a block value read after a cache write is the same instance (B-302).
- `Consent::setNote()` round-trip (B-303); `RgbaColor` rejects `256` / `-1` / `'gg0000'` (B-304); a calculator reading
  `getIndex()` through `getValueForFieldName()` (B-305).
- `(string)` of quantity values with an abbreviation-less unit and with `0` (B-306, B-307); `NumericRange(null, 5)
  ->getRange()` (B-308); failed `setUnitId()` keeps the old unit (B-309).
- `Link`: direct ↔ internal switching, `getHref()` with existing query / fragment, `tabindex="0"`, `isEmpty()` with owner
  (B-310, B-311); `Hotspotimage::getThumbnail()` with a shared `Config` instance (B-312); nested `foreach` over an
  `ImageGallery` (B-313); `StructuredTable::getHtmlTable()` with reordered columns (B-314).

### [ ] I-169 – `OwnerAwareFieldTrait` / `ObjectVarTrait` contracts used by all value objects

- `ObjectVarTrait::getObjectVars()` removes `dao` and `_owner` but keeps `_fieldname` / `_language` (root of the `Link`
  part of B-311) → strip all owner-trait fields, or provide a `getValueVars()` used by `isEmpty()` / comparisons.
- `setObjectVar()` writes any declared property with no type or invariant checks (e.g. `unit` / `unitId` out of sync
  on quantity values) → restrict to an allowlist or mark `@internal`.
- `markMeDirty()` / the owner fields are public `@internal` API (`_setOwner()` & co.) that every type wires up by
  hand in `getDataFromResource()` → one helper (`attachOwner($value, $params)`) used by all types (see B-301).
- PHP 8.4 asymmetric visibility (`public private(set)`) would remove most trivial getters of `CalculatedValue`,
  `GeoCoordinates`, `ExternalImage` once BC allows (next major).

---

## V – DataObject `QuantityValue/**` units and conversion (Scope 18)

> Notes
> - Scanned and skipped files: see the Scope 18 notes in [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md) (C-53,
>   B-316–B-323).
> - Missing `declare(strict_types=1)`: `Unit.php`, `Unit/Dao.php` (already listed in I-73). Cache tags for the unit
>   table: I-82. Value-object side (`convertTo()`, container access in `__toString()`): I-163.

### [ ] I-170 – `Unit` model: typing, validation hook, PHP 8.4 idioms

- Declare `Stringable` for `__toString()`.
- `setBaseunit(Unit|string|null)` silently stores `null` for an unsaved `Unit` (id `null`) → throw; `getBaseunit()`
  re-resolves on every call → fine with the table, but document that it returns the shared cached instance (B-318).
- `create(array $values)` passes all keys to `setValues()` without `ignoreEmptyValues`, `Service` / `Listing\Dao` pass
  `true` → one factory used by the importer and the DAO.
- Add a `validate()` method (B-317) and call it from `save()`; group the event dispatch (`PRE_*` / `POST_*` pairs are
  written out four times) into one helper.
- `$factor` / `$conversionOffset` are `?float` where `null` means `1` / `0` everywhere → expose
  `getEffectiveFactor(): float` / `getEffectiveOffset(): float` so converters do not re-implement the defaults.
- Next major: asymmetric visibility (`public private(set)`) or readonly value semantics for the conversion fields;
  `Unit` is effectively configuration and should not be mutated by consumers.

### [ ] I-171 – `Unit\Dao` / `Unit\Listing` / `Unit\Listing\Dao` cleanup

- `getByAbbreviation()` / `getByReference()` build SQL with `quote()` concatenation → bound parameters; `SELECT *` →
  explicit columns; both methods are identical apart from the column → one private `loadBy(string $column, string $value)`.
- `create()` and `save()` only call `update()`, and `update()` also inserts → keep one `save()`.
- `update()`: the `serialize()` / `(int)` bool branch is dead (no array, object or bool properties); the id query uses
  the literal `quantityvalue_units` instead of `self::TABLE_NAME`; `getPrimaryKey()` queries the schema on every save
  for a fixed `['id']`. Empty docblocks (`Contains all valid columns…`, blank `@throws`) → remove or type the property
  (`/** @var list<string> */`).
- `Listing::isValidOrderKey()` omits `reference`, `converter`, `conversionOffset` → `setOrderKey('reference')` throws
  `InvalidArgumentException`; derive the list from the table columns. The `@method Unit|false current()` annotation is
  stale.
- `Listing\Dao::getTotalCount()` swallows every exception and returns `0` while `load()` lets them through → let both
  throw; the two queries also differ in spacing before `getCondition()`.

### [ ] I-172 – `QuantityValue\Service`: split responsibilities, explicit errors

- One class mixes three concerns: the static unit-table cache used by models (`getQuantityValueUnitsTable()`), the
  JSON importer and the JSON exporter. Split into an injectable `UnitRepository` (cached table, lookups by id /
  abbreviation / reference, invalidation) and a `UnitDefinitionImporter` / `Exporter`; keep the static method as a
  deprecated proxy (`#[\Deprecated]`) for BC.
- Importer: `json_decode(…, JSON_THROW_ON_ERROR)`, a validated input DTO, one transaction, a result object (created /
  updated / skipped / errors) instead of `bool`; the command can then print details and support a real dry run
  (today `--dry-run` skips everything, including validation).
- Exporter: no `foreach ($units as &$unit)` reference (left dangling after the loop), `JSON_THROW_ON_ERROR`, return
  `string`; translation does not belong here (B-321).
- Cache: the magic priority `995` and `force: true` → named constants with a comment; `Logger::error((string)$e)` →
  `Logger::error($e->getMessage(), ['exception' => $e])` (B-322).

### [ ] I-173 – Conversion API: base-unit check, converter lookup, exceptions, precision

- `UnitConversionService::convert()` chooses the converter from the **target** unit's base only; the "same base unit"
  check is left to each converter, so a custom converter (e.g. currency) is called for a length value and must repeat
  the check. Move the check (and the `from === to` short-circuit) into the service.
- Converter services are fetched through `UnitConverterResolver` / `ClassResolver` from the public container
  (`@service` string) → register converters with a tag and inject a `ServiceLocator`
  (`#[AutowireLocator('pimcore.quantity_value_converter')]`), which removes the need for public services and the
  static resolver cache.
- Throw domain exceptions (`UnitConversionException extends \RuntimeException`, `UnsupportedException`) instead of
  plain `\Exception`; declare them in `QuantityValueConverterInterface::convert()` (`@throws`) and document that only
  `QuantityValue` is supported by the default converter.
- Promote `$defaultConverter` as `private readonly`; make `DefaultConverter` `final` (it has no extension points).
- Precision: chained float arithmetic (`0.1 * 3`, `/ 0.0254`) produces values like `0.30000000000000004` that end up in
  filters and in saved values when custom code persists conversion results → round to the field's `decimalPrecision`
  where the target field is known, or offer a `bcmath` based converter (I-154 covers the filter comparison).
- Update the conversion chapter of `55_Number_Types.md` together with B-316 (the documented formula and the °C / °F
  example are wrong in one direction).

### [ ] I-174 – Tests for Scope 18

- No test covers conversion, import or export (only test helpers call `Unit::getByAbbreviation()`).
- `DefaultConverter`: factor-only, offset-only and factor + offset units in both documented setups, same-unit
  conversion, round trips, `null` value, factor `0` (B-316–B-318); the shared cached unit is unchanged after a
  conversion (B-318).
- `Unit::save()`: add vs update events with a preset id, existing id is not overwritten silently (B-319); validation
  rejects factor `0`, self / chained base units (B-317).
- Importer: invalid structures return an error without writing, override keeps `baseunit` of derived units outside the
  file, a failing import leaves the table unchanged (C-53, B-320). Export → import round trip is lossless (B-321).
- `getByAbbreviation()` with a duplicate abbreviation, `Unit::__toString()` with `mW` and with `null` abbreviation
  (B-323); unit-table cache returns fresh data after a save (B-322).

---

## W – DataObject `Objectbrick/**` and `SelectOptions/**` (Scope 19)

> Notes
> - Scanned and skipped files: see the Scope 19 notes in [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md) (C-54–C-56,
>   B-324–B-333).
> - Missing `declare(strict_types=1)` in `Objectbrick/Dao.php`, `Objectbrick/Definition/Dao.php`,
>   `Objectbrick/Data/Dao.php` is part of I-73; `@unlink()` is in I-83; the shared definition lifecycle seen from the
>   field-collection side is I-136, its DAO / listing I-137, its data classes I-138. Points made there are not repeated.

### [ ] I-175 – `Objectbrick\Definition`: one assignment model, one class-update helper, no dead code

**File:** `models/DataObject/Objectbrick/Definition.php:43-567`

- The "for each `Objectbricks` field: remove the key from `allowedTypes`" block is written three times
  (`cleanupOldFiles()` `:331-340`, `updateDatabase()` `:372-381`, `delete()` `:500-509`) → one private
  `removeKeyFromClass(ClassDefinition $class, ?string $fieldname = null): bool` that returns whether something changed,
  so the class is saved only then.
- Assignments are untyped arrays (`public array $classDefinitions`, keys `classname` / `fieldname` / `deleted`) and the
  diff encodes them as `"classname-fieldname"` strings and splits them again with `explode('-')`
  (`buildClassList()` / `getClassesToCleanup()`) → a small `final readonly class BrickAssignment` (or at least
  `@var list<array{classname: string, fieldname: string, deleted?: bool}>`) and `array_udiff()` on tuples. The
  `deleted` flag is a UI transport detail → filter it in the controller / importer, not in `save()`.
- `getByKey()` uses an exception for control flow and returns whatever the file returned (B-255) → `instanceof self`
  check, early return; return type `?self`.
- `delete()` ends with a loop over all classes that does nothing (B-327) → remove.
- `enforceBlockRules()` uses `method_exists($fd, 'getFieldDefinitions')` → an interface check (`Layout` / `Block` /
  `Localizedfields` expose children); `checkContainerRestrictions()` only forwards → inline.
- `checkTablenames()` sorts with `array_multisort()` to find the longest name → `max(array_map('strlen', $tables))`;
  the inner loop adds the same `object_brick_localized_<key>_<id>` name once per language; build names with the
  shared name builder (I-179).
- `getContainerClassName()` / `getContainerNamespace()` take parameters they do not use (`$classname` /
  `$fieldname`) → drop them or document them as extension points; `getContainerClassFolder()` mixes
  `PIMCORE_CLASS_DEFINITION_DIRECTORY` with a PHP class folder → `PIMCORE_CLASS_DIRECTORY` (same value today,
  `lib/Bootstrap.php:224`, but different meaning).
- `unset($clone->oldClassDefinitions)` / `unset($clone->fieldDefinitions)` before `var_export()` leave uninitialised
  typed properties on the clone → export an explicit array (I-83).
- `in_array($this->key, $allowedTypes)` and `array_search()` without strict mode → strict; `$this->key` and
  `$this->getKey()` are mixed in the same method.
- `@method` annotations for DAO calls (`getTableName()`, `createUpdateTable()`) → typed methods that delegate to the
  DAO, so static analysis sees them.

### [ ] I-176 – `Objectbrick\Dao` (container DAO): stop inheriting from the field-collection DAO

**File:** `models/DataObject/Objectbrick/Dao.php:31-144`

- It extends `Fieldcollection\Dao` only to reuse `AbstractDao`, but inherits `save()`, which calls `delete($object, true)`
  and would remove the query rows of all bricks; the signatures already drift (`load(…, array $params = [])`) →
  extend `Model\Dao\AbstractDao` directly and make the class `final`.
- `delete()`: the `$saveMode` parameter is unused and the method returns an always-empty `array` → `void`.
- `load()`:
  - builds the brick with `new $brickClass($object)`, while `Fieldcollection\Dao` uses `pimcore.model.factory`, so
    class overrides configured in the model factory do not apply to bricks → use the factory;
  - rebuilds the same context array for every field of every row, and mutates the caller's `$params` → build
    `$context` once per row, use a fresh `$fieldParams`;
  - nested `if ($fd instanceof LazyLoadingSupportInterface) { if (…) }` → one condition; `if ($value === 0 ||
    !empty($value))` silently skips `'0'`, `0.0`, `false` → `$value !== null`;
  - `getFieldDefinition($fieldname)` without `suppressEnrichment` (the collection DAO passes it).
- Error handling: see B-329 (`TableNotFoundException` only).

### [ ] I-177 – `Objectbrick\Data\Dao`: split `save()`, shared inheritance logic, query builder

**File:** `models/DataObject/Objectbrick/Data/Dao.php:31-369`

- `save()` is ~210 lines: store row, query row and inheritance detection → `writeStoreRow()`, `buildQueryRow()`,
  `collectInheritanceChanges()`. The four nearly identical "old value vs new value vs parent value" branches
  (`:177-231`) differ only in `addRelationToCheck()` / `addFieldToCheck()` and array vs scalar → one helper over a
  normalised `column => value` map.
- `DataObject::setGetInheritedValues(false)` with a `finally` "HACK" comment → `DataObject\Service::useInheritedValues(false, fn () => …)`
  (B-175 pattern).
- `$this->model->getDefinition()` is resolved three times in `save()` and once per object var in `delete()` → local
  variable; `$getter` and `$method` are two names for the same thing; the commented-out `$tableName` line is dead.
- `$this->inheritanceHelper` is a property only used inside one call → local variable.
- `save()` passes the same `context` array to custom-persisting and column fields, but builds it twice → one
  `$fieldParams` helper shared with `delete()` (B-324) and the container DAO (I-176).
- `getRelationData()`: three `UNION` blocks with concatenated table names and the brick type → a query builder with
  bound parameters (`position`, `ownername`, B-325); `"null" as published` returns the **string** `"null"` for assets,
  which callers cast with `(bool)` → `NULL`.
- `@internal` and not extended in core → `final`.

### [ ] I-178 – `Objectbrick\Data\AbstractData`: typing and serialization

**File:** `models/DataObject/Objectbrick/Data/AbstractData.php:34-248`

- `$object` is typed `Concrete|ElementDescriptor|null`, but `setObject()` / `getObject()` only accept / return
  `?Concrete` (same as I-138 for collections) → narrow the property or handle the descriptor in both.
- `__wakeup()` is dead: `object` is always excluded in `__sleep()`. Move to `__serialize()` / `__unserialize()` with
  an explicit field list (I-75); `in_array($key, $blockedVars)` → strict; memoise `getLazyLoadedFieldNames()` per type
  (the definition is loaded on every cache write).
- `isAllLazyKeysMarkedAsLoaded()` → `return $this->getObject()?->isAllLazyKeysMarkedAsLoaded() ?? true;`.
- `get()` / `set()` build method names dynamically and return `mixed` → validate the field name against the
  definition (clear exception instead of "Call to undefined method"), `set(): static`.
- `getValueFromParent()` calls the parent container getter and brick getter twice → local variables.
- `protected string $type = ''` on the abstract class can never resolve → `abstract public function getType(): string`
  generated per brick, or a `TYPE` constant.
- Empty docblocks (`/** * * @throws … */`, "Will be overriden") → remove or fix the wording.

### [ ] I-179 – `Objectbrick\Definition\Dao` and `Definition\Listing`: one table-name builder, explicit DDL

**Files:** `models/DataObject/Objectbrick/Definition/Dao.php:28-171`; `models/DataObject/Objectbrick/Definition/Listing.php:22-78`

- Brick table names are built in five places: `Definition::checkTablenames()`, `Definition\Dao::getTableName()` /
  `getLocalizedTableName()`, `Localizedfield\Dao::getTableName()` / `getQueryTableName()` (`:55-76`),
  `Listing\Concrete\Dao.php:214`, `ClassDefinition/Data/Block.php:752` and the maintenance cleanup → one static
  `BrickTableName` helper (store / query / localized / localized query + language), so they cannot diverge and
  B-327 can drop all of them.
- `createUpdateTable()`: the two `CREATE TABLE` statements are identical apart from the name → one private method;
  the store / query column loops are the same algorithm with different getters → one loop over
  `[table, getter, protected columns]`; `([$tableStore, $tableQuery])` has superfluous parentheses.
- The `removeIndices()` override changes the meaning of the helper method of the same name (drops `p_index_` too,
  ignores errors) → give it its own name; fix B-326 there.
- DDL through `executeQuery()` → `executeStatement()` (I-76); identifiers with `quoteIdentifier()` (I-83).
- `@internal` and not extended → `final`.
- `Listing`: `include` per file without validation and without the custom-folder precedence (C-55, B-330) → resolve
  keys and load through `getByKey()`; `loadFileNames()` → `Symfony\Component\Finder\Finder` or a guarded `glob()`;
  return `list<Definition>`; `loadNames()` should not need `realpath()` on every file. Mark the class `@internal` or
  give it an interface.

### [ ] I-180 – `SelectOptions\Config` and `Config\Dao`: validation in one place, cheaper usage check

**Files:** `models/DataObject/SelectOptions/Config.php:43-394`; `models/DataObject/SelectOptions/Config/Dao.php:30-161`

- Validation is spread over `setId()` (reserved words), `Dao::validateId()` (unanchored regex, C-32) and the enum
  builder (case names, traits, interfaces, after the config is saved, C-56) → one `Config::validate(): void` (or a
  Symfony Validator constraint set) that runs before anything is written and reports all problems at once.
- `final class` with `new static()` (`createFromData()`) and setters returning `Config` with `@return $this`
  docblocks → `new self()`, return type `static` or `self` consistently, drop the redundant docblocks.
- `getById()` uses an exception for control flow (`throw new RuntimeException(…)` caught right away) → check
  `RuntimeCache::isRegistered()` / `instanceof` and return early.
- `getFieldsUsedIn()` loads every class, field collection, brick and **every classification-store key** (one
  `getFieldDefinitionFromKeyConfig()` per key) for each delete → query the classification store for select keys only,
  stop at the first usage when only a yes / no answer is needed, and walk the field tree recursively (B-332).
- `match (get_class($definition))` falls back to `'Unknown'` for subclasses → `instanceof` checks;
  `method_exists($definition, 'getName')` → `$definition instanceof ClassDefinition`.
- Reference parsing (trim `\` and spaces, short vs fully qualified name) is duplicated between
  `isConfiguredAsOptionsProvider()` and the provider → one normaliser (B-332).
- `Dao::saveConfiguration()` filters `getObjectVars()` with an allow-list loop → `array_intersect_key()` with the
  `PROPERTY_*` constants, or simply build the array from the getters.
- `Dao::getById(?string $id = null): void` (optional id, side effects on the model) → `load(string $id): void`.
- `Dao::delete()`: `@unlink()` (I-83) and no runtime-cache update (B-331).
- PHP 8.4: the five `PROPERTY_*` constants could be a backed enum; `$group` / `$useTraits` / `$implementsInterfaces`
  are plain getter / setter pairs → property hooks or asymmetric visibility when BC allows (next major).

### [ ] I-181 – `SelectOptions` listing, value object and enum traits

**Files:** `models/DataObject/SelectOptions/Config/Listing.php:28-94`; `Config/Listing/Dao.php:22-46`;
`SelectOptions/Data/SelectOption.php:22-126`; `SelectOptions/Traits/EnumGetValuesTrait.php:20-29`;
`SelectOptions/Traits/EnumTryFromNullableTrait.php:20-30`

- `Listing\Dao` extends `Config\Dao`, so the listing DAO inherits `save()`, `delete()`, `getById()` and
  `validateId()`, all of which operate on `$this->model` – a `Listing` here → extend `PimcoreLocationAwareConfigDao`
  and keep only `configure()` + `loadList()`.
- `getTotalCount()` loads and hydrates every config to count them → `count($this->loadIdListByReadTargets())`.
- `Listing::hasConfig()` → `array_any()` (PHP 8.4) with `strcasecmp()`; document why the comparison is
  case-insensitive (enum file names on case-insensitive filesystems) and use the same rule when saving.
- `Listing::setSelectOptions()` / the `@internal` property are public mutation of a read model → keep them internal
  to the DAO.
- `SelectOption` is mutable (`setValue()` / `setLabel()` / `setName()`) while instances are shared through the runtime
  cache: changing an option of a cached config changes it for every caller in the process → `final readonly class`
  with `with*()` methods; `static::PROPERTY_*` / `new static()` in a class nobody extends → `self::` / `new self()`.
- Traits: `EnumGetValuesTrait` / `EnumTryFromNullableTrait` only work in backed enums → `@phpstan-require-implements
  \BackedEnum`; `getValues()` → `@return list<string>` and `array_map(static fn (self $case) => $case->value,
  self::cases())` (clearer than `array_column()` on enum objects, which works but relies on public property access).
- The enum builder (`lib/DataObject/ClassBuilder/SelectOptionsEnumBuilder.php`) builds code with `strtr()` templates
  and ad-hoc escaping → code generator (I-83, C-56); its case-name regex has a stray `-`.

### [ ] I-182 – Tests for Scope 19

- Existing brick tests cover one inheritance case and one relation case (`tests/Model/Inheritance/ObjectbrickTest.php`,
  `tests/Model/Relations/ObjectbrickTest.php`); nothing covers two `Objectbricks` fields, brick removal, definition
  delete or the select-options model (only a unit test mentions it).
- Bricks: two fields sharing a brick (add, update, remove, inherit – C-54); `classes-rebuild` with a key in both
  definition folders keeps columns (C-55); removing a brick keeps the metadata of a class field with the same name
  (B-324); edit-mode relation data per field (B-325); a class save does not drop / re-add indexes (B-326); delete drops
  localized tables and clears the runtime cache (B-327); a failing assignment leaves classes unchanged (B-328); a read
  error is not turned into "no bricks" (B-329); a new brick without field name, falsy values in
  `getValueForFieldName()` (B-330).
- Select options: values / labels with `\`, `'`, duplicates and a leading `-` are rejected and nothing is written
  (C-56); `getById()` after save / delete in the same process, `exists()`, reserved-word ids (B-331); usage inside a
  `Block` and with a fully qualified reference blocks the delete (B-332); integer / `null` values from YAML (B-333).

---

## X – Document `Email` / `Folder` / `Snippet` (Scope 20)

> Notes
> - Scanned and skipped files: see the Scope 20 notes in [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md) (B-334–B-337).
> - Missing `declare(strict_types=1)` in `Email/Dao.php`, `Folder/Dao.php`, `Snippet/Dao.php` is part of I-73; one
>   generic type DAO is I-78; `#[\Override]` on DAO / model overrides is I-74. Points made there are not repeated.

### [ ] I-183 – `Document\Email`: typed address accessors, shorter docblocks, no duplicate property delete

**Files:** `models/Document/Email.php:29-203`; `models/Document/Email/Dao.php:63-71`;
`models/Document/Page/Dao.php:63-71`; `models/Document.php:637`; `lib/Mail.php:379-415`

- The address fields are comma-separated strings, and `Mail::setDocumentSettings()` repeats the same
  parse-and-add loop four times → add `getToAddresses()`, `getCcAddresses()`, `getBccAddresses()`,
  `getReplyToAddresses(): list<Address>` and `getFromAddress(): ?Address`, built by the shared parser of B-335; `Mail` then
  calls `->to(...$document->getToAddresses())`. Keep the string getters/setters as the persisted form.
- Docblocks are copy-paste noise: the setter of `subject` says "Contains the email subject", every method has empty
  ` *` lines and `@return $this` next to a native `static` return type, and the property comments repeat the property
  names → remove them, keep only the ones that say something (separator `,` / `;`, "first `from` wins" until B-335 is
  fixed).
- `Email\Dao::delete()` and `Page\Dao::delete()` call `deleteAllProperties()`, which `Document::doDelete()` already does
  (`Document.php:637`) before `getDao()->delete()` → remove both overrides (one query less per delete, one copy less
  for I-78).
- Name the type once: `public const string TYPE = 'email';` (typed class constant, PHP 8.3+) and
  `protected string $type = self::TYPE;` – same for `Snippet`, `Folder` and the other document types, so `typeMatch()`,
  `Service` and the hardlink wrappers can use the constant instead of string literals (ties to the document-type enum
  in I-74).
- `$supportsContentMain = false` is a mutable instance property used as a class trait → `protected const bool
  SUPPORTS_CONTENT_MAIN` or an overridden `supportsContentMain(): bool` in `Email` (see B-337).

### [ ] I-184 – `Folder` / `Snippet` marker classes and the `direct_route` of snippets / emails

**Files:** `models/Document/Folder.php:22-28`; `models/Document/Folder/Dao.php`; `models/Document/Snippet.php:18-26`;
`bundles/CoreBundle/config/pimcore/default.yaml:288-318`; `lib/Routing/Dynamic/DocumentRouteHandler.php:78-90`

- `Folder\Dao` is an empty subclass of `Document\Dao` (like `DataObject\Folder\Dao`, I-135); it exists only for the
  `@method` annotation and the DAO class map → map `Folder` directly to `Document\Dao` or keep it with a short comment
  that it is a typed extension point.
- `Snippet`, `Email` and `Folder` cannot be `final` (`Hardlink\Wrapper\Snippet` / `Email` / `Folder` extend them), but
  nothing else should → mark them `@final` in the docblock so static analysis flags project subclasses that the
  type map would not instantiate anyway.
- `direct_route: true` for `snippet` and `email`: every published snippet and email is rendered at its own URL for
  anonymous visitors (`/snippets/footer`, `/emails/password-reset`), with the layout of a fragment and without SEO
  data. Check whether the admin preview needs this (**not verified**); if not, set it to `false` by default or send
  `X-Robots-Tag: noindex` for these types, and document the option.

### [ ] I-185 – Tests for Scope 20

- `tests/Model/Document/` has no test for `Email`, `Snippet` or `Folder` specifics.
- `Email`: a `to` list longer than 255 characters saves and loads unchanged (B-334); an invalid address, a display name
  with a comma and two `from` addresses are handled on save, not on send (B-335); the typed address accessors return
  `Address` objects (I-183).
- Creating or moving a document below a snippet or an email is rejected (B-336).
- `Email::setContentMainDocument()` is rejected / has no effect on dependencies and `rewriteIds()` (B-337).

---

## Y – Document sub-directory DAOs (Scope 21)

> Notes
> - Gap-fill scope: scanned and skipped files are listed in the Scope 21 notes in
>   [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md) (B-338–B-340).
> - Not repeated: `declare(strict_types=1)` in all eight files (I-73), `getTranslationSourceId(): mixed` and
>   `#[\Override]` (I-74), `getQueryPart()` in `prepareQueryBuilderForTotalCount()` (I-76), one generic type DAO
>   with the copied `published` cast and SQL (I-78), `Listing::getCount()` → `COUNT(*)` and splitting
>   `Document\Service` (I-79), the duplicate `deleteAllProperties()` overrides (I-183), the empty `Folder\Dao` (I-184).

### [ ] I-186 – `Document\Service\Dao`: one translation query, clear parameters, a deterministic pretty-URL lookup

**Files:** `models/Document/Service/Dao.php:31-39`, `:55-88`, `:90-103`, `:115-132`

- `getTranslations()` marks the source row by putting the string `"source"` into the `language` column of a
  `UNION`. Double quotes are a string literal only without `ANSI_QUOTES`; a project that sets
  `sql_mode = 'ANSI_QUOTES,…'` (the PDO init command in `default.yaml:148` can be overridden) gets an
  "unknown column" error. → `'source'`, or better a separate `is_source` column alias, so a real language code can
  never be mistaken for the marker.
- The method runs two `UNION` queries, one more query per row for `task = 'open'` (B-144), and loads the source
  document twice with `Document::getById()` (`:74`, `:83`) just to read its `language` property → one query for the
  group (`WHERE sourceId = :source OR id = :source`), load the source once, after B-340 read the language the same
  way for all members.
- `string $task = 'open'`: only `'open'` changes the behaviour, and every other string silently means "do not
  follow". → `bool $includeLinkedGroups = true` or a small backed enum. Once B-338 keeps groups flat, remove the
  option entirely.
- `removeTranslationLink()`: `$newSourceId` is only an existence check (`:123`) → `$isDirectLink = (bool) …`.
  Document the three cases (source ↔ member, member ↔ source, member ↔ member). The reverse delete (`:131`) can
  only match rows that B-338 should forbid; remove it with that fix.
- `addTranslation()` asks `getPrimaryKey('documents_translations')` (`SHOW COLUMNS`, cached) for a key that is fixed
  in the schema → pass `['sourceId', 'language']`. After B-338 it no longer upserts at all.
- `getDocumentIdByPrettyUrlInSite()`: `LEFT JOIN documents_page` with a `WHERE` on `documents_page.prettyUrl` is an
  inner join → write `INNER JOIN` (or query `documents_page` and join `documents` for the path). Add
  `ORDER BY documents.id LIMIT 1` until B-153 makes pretty URLs unique, and return `?int` instead of `0` for "not
  found" (both callers only test truthiness: `DocumentRouteHandler.php:122`, `Service.php:499`).
- Translation links are a separate concern from documents (`Service` reaches them through `__call`, `@method`
  lines `Service.php:42-46`) → a small `DocumentTranslationRepository` service with typed methods; keep the
  `Service` methods as deprecated proxies.

### [ ] I-187 – `Document\Listing\Dao` / `Listing`: batch loading, consistent counts, dead code

**Files:** `models/Document/Listing/Dao.php:33-111`; `models/Document/Listing.php:25-48`;
`models/Document.php:187-258`

- `load()` is N+1: one `Document::getById()` per row. An uncached document costs two DAO loads (base
  `Document\Dao`, then the type DAO after the class is resolved, `Document.php:219-234`), plus the cache lookups. A
  tree page with 100 children can run 200+ queries. → select `documents.id, documents.type` (already done), group
  the ids by type and load each type table with one `WHERE id IN (…)` query (with the generic type DAO from I-78),
  then build the models; check `RuntimeCache` / `Cache` first for ids already loaded.
- `if ($documentData['type'])` silently skips rows with an empty type. With `sql_mode = ''` an unknown value written
  to the `documents.type` enum is stored as `''` (custom `type_definitions` types, I-84). `loadIdList()`,
  `getCount()` (when not loaded) and `getTotalCount()` still count these rows, so paginator totals and the
  number of loaded items differ. → log the skipped ids, and filter `documents.type <> ''` in the query so that all
  methods agree.
- `Listing::$objectTypeDocument` (`Listing.php:42`, "return all documents as type `Document`") is never read →
  remove it, or implement it as the cheap mode of the batch load above (no type tables).
- `getQueryBuilder(...$columns)`: the docblock `string|string[]|null $columns` does not describe a variadic.
  Without arguments the query is `SELECT  FROM documents` (`getTotalCount()` only works because it replaces the
  select) → `string ...$columns` with the default `documents.*`.
- Small items: `getSql()` / `getSQL()` mixed in one class; typos "an return", "specicifies"; the
  `@method onCreateQueryBuilder(?callable $callback)` annotation has no return type (`Listing.php:31`); empty
  ` *` docblock lines on the two properties.

### [ ] I-188 – Type DAOs and `PageSnippet\Dao`: matching `getById()` signatures, no service locator

**Files:** `models/Document/Page/Dao.php:35-52`, `Snippet/Dao.php:34-51`, `Email/Dao.php:35-52`,
`Link/Dao.php:34-52`; `models/Document/Dao.php:42-54`; `models/Document/PageSnippet/Dao.php:45-63`

- The base method is `getById(int $id)`; the four type DAOs widen it to `getById(?int $id = null)` and fall back to
  the model id with a loose `if ($id != null)` (`0` also falls back). The only caller passes an id
  (`Document.php:220`, `:233`). With no model id the query becomes `WHERE documents.id = NULL` and the message is
  "Page with the ID  doesn't exists". → keep the base signature `getById(int $id)`, drop the fallback, and use one
  message format ("document with id %d not found", as in the base DAO). Until I-78 is done, keep the four copies
  consistent.
- `PageSnippet\Dao::getEditables()` fetches `EditableLoader` through `Pimcore::getContainer()` (`:50`) → move the
  loading into a service that gets the loader injected (the DAO only returns the raw rows), or at least use a
  typed static accessor in one place. The `/** @var Document\Editable $editable */` hint is redundant:
  `EditableLoader::build()` already returns `Editable` (`lib/Model/Document/Editable/Loader/EditableLoader.php:28`).
- The `create()` overrides insert only `id` and rely on the `update()` that `Document::save()` runs next. Add a
  one-line comment so nobody "fixes" it by writing the type columns twice.

### [ ] I-189 – Tests for Scope 21

- `tests/Model/Document/` has no test for translation links.
- `addTranslation()`: the same document into two groups, a second document for an existing language, the source's
  own language, a self-link and a translation without `language` are each rejected with a clear exception
  (B-338).
- Delete the source of a group with three members: the successor is deterministic, the other members stay linked,
  no row points to the deleted id; the same with a successor that is also linked elsewhere (B-339).
- Change a member's `language` property, and move a member below another language root: `getTranslations()`
  returns the effective languages (B-340).
- `getTranslations()` with `ANSI_QUOTES` in `sql_mode`; `getDocumentIdByPrettyUrlInSite()` returns `null` when
  nothing matches (I-186).
- Document listing with 100 children of mixed types: the query count is bounded, and `getTotalCount()` equals
  `count(load())` with an empty-type row present (I-187).

---

## Z – Document hardlink wrappers (Scope 22)

> Notes
> - Scanned and skipped files are listed in the Scope 22 notes in [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md)
>   (C-57, B-341–B-342).
> - Not repeated: class-name rewriting in serialized data / composition instead of upper-casting (I-75), the
>   `func_get_args()` cache key and `#[\Override]` (I-74), the configurable wrapper map with a generic fallback
>   (B-147), the `@final` note on `Snippet` / `Email` / `Folder` (I-184).

### [ ] I-190 – Hardlink wrapper classes: correct DAO annotations, a usable interface, `final`, one type map

**Files:** `models/Document/Hardlink/Wrapper/Email.php:23-29`, `Folder.php:23-29`, `Hardlink.php:23-29`,
`Page.php:23-29`, `Snippet.php:23-29`; `models/Document/Hardlink/Wrapper/WrapperInterface.php:23-38`;
`models/Document/Hardlink/Service.php:37`, `:45`, `:61`; `lib/Navigation/Builder.php:332-333`

- All five classes declare `@method Dao getDao()` with `Dao` = `Document\Hardlink\Dao`. At runtime `wrap()` calls
  `initDao(get_class($source))`, so a `Wrapper\Page` has a `Page\Dao`, a `Wrapper\Email` an `Email\Dao`, a
  `Wrapper\Folder` a `Folder\Dao`. The annotation makes PHPStan / IDEs flag valid calls (`getDao()->getEditables()`)
  and hide wrong ones → `@method Page\Dao getDao()` etc., or drop the annotation and inherit the parent's.
- `WrapperInterface extends ElementInterface` only. Every consumer needs `Document` methods that the interface does
  not declare: `Builder::getChildren()` calls `getChildren()` on a `WrapperInterface` (`:332-333`), `wrap()` callers
  call `setKey()` / `initDao()`. → declare the methods consumers rely on (`getChildren()`, `hasChildren()`), or
  type the wrapper as `Document&WrapperInterface` in `Service::wrap()` and in the consumers. `wrap()`'s return type
  `Wrapper\WrapperInterface|Wrapper\Hardlink|null` is redundant: `Wrapper\Hardlink` implements the interface.
- `@return $this` docblocks on `setHardLinkSource()` / `setSourceDocument()` duplicate the native `static` return type
  → remove. Add a class docblock to the interface that says what a wrapper is (a read-only copy of the source with
  the hardlink path, same id) and what it is not (not cacheable – `CoreCacheHandler.php:373-376`, not saveable).
- Nothing extends the wrapper classes, and `upperCastDocument()` would never instantiate a subclass → make all five
  `final`.
- The five classes differ only in the parent type; the class is picked by `'Wrapper\\' . ucfirst($doc->getType())`
  (`Service.php:61`). Replace the string building with an explicit `const array WRAPPERS = ['page' => Page::class,
  …]` (or a `match`) next to the classes, so a missing wrapper is a clear error and the list is greppable. Delete
  `Wrapper\Hardlink` with B-341.
- `Wrapper\Email` and `Wrapper\Snippet`: both types have `direct_route: true` (I-184), so a `childrenFromSource`
  hardlink on an e-mail / snippet folder publishes every e-mail and snippet a second time under the hardlink path.
  Decide whether hardlinks to these types are needed at all; if not, return `null` from `wrap()` for them and remove
  the two classes.

### [ ] I-191 – Tests for Scope 22 (and the Scope 6 hardlink items)

- `tests/` has no hardlink test at all (`grep -rl Hardlink tests` is empty).
- `Service::wrap()` for each document type: same id, hardlink path / key, source DAO class, `save()` / `delete()`
  throw; a wrapper is not written to the cache (`CoreCacheHandler`).
- Static pages: a request to a hardlinked static-enabled page (with and without a source pretty URL) writes no file,
  and the source's pretty-URL file is unchanged (C-57).
- Hardlink → hardlink → page: the route resolves to the page or the save is rejected, depending on the B-341
  decision; the navigation contains the entry; `getSourceDocument()` and `getSourceId()` agree.
- `pimcore_document_wrap_hardlink()` / `Service::wrap()` with a normal page does not end in an uninitialised-property
  `Error` (B-342).
- Regression cases from Scope 6: a hardlink to itself / an ancestor / a deleted source (B-147), the type row missing
  (B-148), scheduled tasks removed on hardlink delete (B-212).
