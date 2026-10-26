# lib – TODO 1: Critical errors

Scope: **(1)** `lib/Cache/**` (`Core/CoreCacheHandler`, `Core/WriteLock`, `Core/CacheQueueItem`,
`Core/CacheMarshallerInterface`, `Core/Exception/InvalidArgumentException`, `RuntimeCache`, `Tool/Warming`,
`Symfony/CacheClearer`, `FullPage/SessionStatus`), plus the direct consumers where a defect originates in the
interplay (`lib/Cache.php` facade, `lib/Pimcore.php::shutdown()`, `lib/Helper/LongRunningHelper.php`,
`models/Document.php::getById()` and the other element `getById()` methods, `bundles/CoreBundle/config/cache.yaml`)
and `tests/Cache/Core/*`
**(2)** `lib/Composer/**` (`PackageInfo`), plus the direct consumers where a defect originates in the interplay
(`lib/Extension/Bundle/PimcoreBundleLocator.php::findComposerBundles()` (`:108-133`),
`lib/Extension/Bundle/Traits/PackageVersionTrait.php`, the `PIMCORE_COMPOSER_PATH` constant (`lib/Bootstrap.php:199`),
the service definitions `bundles/CoreBundle/config/extensions.yaml:29`, `bundles/InstallBundle/config/services.yaml:11`)
**(3)** `lib/Config/**` (`LocationAwareConfigRepository`, `BundleConfigLocator`, `ReportConfigWriter`), plus the
direct consumers where a defect originates in the interplay (`lib/Model/Dao/PimcoreLocationAwareConfigDao.php`,
`models/DataObject/SelectOptions/Config/Listing/Dao.php`, `bundles/CoreBundle/src/Command/ClassesRebuildCommand.php:169`,
`ClassesDefinitionsBuildCommand.php:95`, `bundles/CoreBundle/src/DependencyInjection/ConfigurationHelper.php:29-135`,
`lib/Kernel.php:78-131`, `lib/Config.php::getReportConfig()` (`:298-319`), `models/Tool/SettingsStore.php::set()` (`:98-104`))
**(4)** `lib/Console/**` (`Application`, `AbstractCommand`, `ConsoleOutputDecorator`, `Style/PimcoreStyle`,
`Traits/DryRun`, `Traits/Parallelization`, `Traits/ParallelizationBase`, `Traits/Timeout`), plus the direct consumers
where a defect originates in the interplay (`lib/Helper/StopMessengerWorkersTrait.php`, `lib/Tool/MaintenanceModeHelper.php`,
`models/Tool/TmpStore.php::add()`, `lib/Messenger/Middleware/CollectGarbageMiddleware.php`,
`bundles/InstallBundle/src/Command/InstallCommand.php:401`, `bundles/CoreBundle/src/Command/Thumbnails*Command.php`,
`composer.json` `require-dev` / `suggest`)
**(5)** `lib/Controller/**` (`Controller`, `FrontendController`, `UserAwareController`, `Kernel*EventInterface`,
`ArgumentValueResolver/*`, `Attribute/ResponseHeader`, `Config/ControllerDataProvider`, `Traits/*`), plus the direct
consumers where a defect originates in the interplay (`bundles/CoreBundle/config/argument_resolvers.yaml`,
`bundles/CoreBundle/src/DependencyInjection/Compiler/ServiceControllersPass.php`,
`bundles/CoreBundle/src/EventListener/ResponseHeaderListener.php`, `EventedControllerListener.php`,
`bundles/CustomReportsBundle/src/Controller/Reports/CustomReportController.php:145`)
**(6)** `lib/DataObject/**` (`ClassBuilder/*` builders, dumpers and interfaces, `BlockDataMarshaller/*`,
`ClassificationstoreDataMarshaller/*`, `FielddefinitionMarshaller/Traits/RgbaColorTrait`, `Consent/Service`), plus the
direct consumers where a defect originates in the interplay (`models/DataObject/ClassDefinition.php::save()` /
`setDescription()`, `models/DataObject/ClassDefinition/Data.php::FORBIDDEN_NAMES`, `models/DataObject/ClassDefinition/Data/Block.php`
(marshal flow), `models/DataObject/SelectOptions/Config.php::save()`, `bundles/CoreBundle/config/marshaller.yaml`,
`lib/File.php::putPhpFile()`, `lib/Tool/Serialize.php`)
**(7)** `lib/Db/**` (`Helper`) and the `lib/Db.php` facade, plus the direct consumers where a defect originates in the
interplay (`models/Dependency/Dao.php:199-212`, `models/DataObject/Objectbrick/Definition/Dao.php:165`, the `Helper::upsert()`
call sites in `models/**`, `composer.json` `doctrine/dbal ^3.8`)
**(8)** `lib/DependencyInjection/**` (`CollectionServiceLocator`, `ServiceCollection`), plus the direct consumer
`bundles/SeoBundle/src/DependencyInjection/PimcoreSeoExtension.php:77-84`
**(9)** `lib/Document/**` (`Adapter` + `Adapter/Ghostscript|LibreOffice|Gotenberg|GetTextConversionHelperTrait`,
`Renderer/DocumentRenderer` + interface, `StaticPageGenerator`, `Editable/EditableHandler`, `Editable/EditableUsageResolver`,
`Editable/UsageRecorderSubscriber`, `Editable/EditmodeEditableDefinitionCollector`, `Editable/Block/*`,
`Editable/Exception/NotFoundException`), plus the direct consumers where a defect originates in the interplay
(`lib/Helper/TemporaryFileHelperTrait.php`, `models/Element/Service.php::getValidKey()`, `models/Asset/Document.php`,
`lib/Tool/Console.php`)
**(10)** `lib/Element/**` (`MarshallerService`), plus the direct consumers where a change originates in the interplay
(`bundles/CoreBundle/config/marshaller.yaml`, the marshaller callers in `models/DataObject/ClassDefinition/Data/Block.php`
and `lib/DataObject/*DataMarshaller/*`)
**(11)** `lib/Event/**` (event-name constant classes `*Events.php`, event classes in `Model/**`, `Cache/**`, `Workflow/*`,
`System/*`, `BundleManager/*`, `Report/*`, and `Traits/*`), plus the direct consumers where a defect originates in the
interplay (the `RecursionBlockingEventDispatchHelperTrait` users in `models/**`, `models/Document/Editable/Renderlet.php`,
`tests/Support/Helper/Pimcore.php`)
**(12)** `lib/Exception/**` (`ImageOptimizationFailedException`), plus its thrower / catcher `lib/Image/Optimizer.php`,
`lib/Image/Optimizer/SpatieImageOptimizer.php`, `lib/Image/Optimizer/OptimizerInterface.php`
**(13)** `lib/Extension/**` (`Bundle/*`: `PimcoreBundleManager`, `PimcoreBundleLocator`, `AbstractPimcoreBundle`,
interfaces, `Installer/*`, `Traits/*`, `Exception/*`; `Document/Areabrick/*`: `AreabrickManager`, `AbstractAreabrick`,
`AbstractTemplateAreabrick`, `EditableDialogBoxConfiguration`, interfaces, `Attribute/AsAreabrick`, `Exception/*`), plus
the direct consumers where a defect originates in the interplay (`lib/Migrations/FilteredMigrationsRepository.php`,
`lib/Migrations/FilteredTableMetadataStorage.php`, `bundles/CoreBundle/src/DependencyInjection/Compiler/AreabrickPass.php`,
`bundles/CoreBundle/config/pimcore/default.yaml:175-177`, `lib/HttpKernel/BundleCollection/ItemInterface.php`)
**(14)** `lib/Helper/**` (`LongRunningHelper`, `Mail`, `GotenbergHelper`, `EncoreHelper`, `ContrastColor`, `ReservedWordsHelper`,
`SystemConfig`, `TemporaryFileHelperTrait`, `StopMessengerWorkersTrait`, `SymfonyExpression/*`), plus the direct consumers where a
defect originates in the interplay (`lib/File.php::getLocalTempFilePath()`, `lib/SystemSettingsConfig.php:72`,
`lib/Messenger/Middleware/CollectGarbageMiddleware.php`, `bundles/GenericExecutionEngineBundle/src/Extractor/JobRunExtractor.php`)
**(15)** `lib/Http/**` (`RequestHelper`, `ClientFactory`, `RequestMatcherFactory`, `ResponseHelper`, `ResponseStack`,
`Response/CodeInjector`, `Context/PimcoreContextGuesser`, `Exception/ResponseException`, `Request/Resolver/*`), plus the direct
consumers where a defect originates in the interplay (`bundles/CoreBundle/src/EventListener/ResponseStackListener.php`,
`bundles/CoreBundle/src/DependencyInjection/Configuration.php::addRoutesChild()` (`:1217-1237`),
`bundles/CoreBundle/src/DependencyInjection/PimcoreCoreExtension.php:254-261`)
**(16)** `lib/HttpKernel/**` (`BundleCollection/*`, `Bundle/DependentBundleInterface`, `BundleLocator/*`, `CacheWarmer/*`,
`WebPathResolver`), plus the direct consumers where a defect originates in the interplay (`lib/Kernel.php::registerBundles()`
(`:186-206`), `lib/Document/Editable/EditableHandler.php:124-139`, `:288`, `bundles/CoreBundle/config/services.yaml:28-34`,
`bundles/CoreBundle/config/extensions.yaml:23-27`, `bundles/SeoBundle/src/PimcoreSeoBundle.php:61`,
`doc/20_Extending_Pimcore/13_Bundle_Developers_Guide/04_Bundle_Collection.md`,
`doc/23_Installation_and_Upgrade/03_System_Setup_and_Hosting/09_Performance_Guide.md:105-118`) and `tests/Unit/HttpKernel/*`
**(17)** `lib/Image/**` (`Adapter`, `AdapterInterface`, `Adapter/GD`, `Adapter/Imagick`, `HtmlToImage`, `Chromium`, `Optimizer`,
`ImageOptimizerInterface`, `Optimizer/OptimizerInterface`, `Optimizer/SpatieImageOptimizer`, `icc-profiles/*`) and the `lib/Image.php`
facade, plus the direct consumers where a defect originates in the interplay (`models/Asset/Image/Thumbnail/Processor.php:102-124`,
`:239`, `:336-345`, `models/Document/Service.php:553-557`, `lib/File.php::getLocalTempFilePath()`,
`bundles/CoreBundle/src/DependencyInjection/Configuration.php:478-491`)
**(18)** `lib/Loader/**` (`ImplementationLoader/ImplementationLoader`, `AbstractClassNameLoader`, `ClassMapLoader`, `PrefixLoader`,
`LoaderInterface`, `ClassNameLoaderInterface`, `Exception/*`), plus the direct consumers where a defect originates in the interplay
(`lib/Model/Factory.php`, `lib/Model/Factory/FallbackBuilder.php`, `lib/Model/Document/Editable/Loader/*`,
`bundles/CoreBundle/src/DependencyInjection/PimcoreCoreExtension.php:148-212`, `bundles/CoreBundle/config/implementation_factories.yaml:34-45`,
`bundles/CoreBundle/config/pimcore/default.yaml:185-192`, `:277-284`, `:320-321`, `lib/Tool.php::classExists()` (`:524-580`))
**(19)** `lib/Localization/**` (`LocaleService`, `LocaleServiceInterface`, `IntlFormatter`), plus the direct consumers where a
defect originates in the interplay (`bundles/CoreBundle/config/l10n.yaml`, `bundles/CoreBundle/config/aliases.yaml:21-33`,
`bundles/CoreBundle/src/EventListener/Frontend/LocaleListener.php`, `lib/Document/Renderer/DocumentRenderer.php:118-132`,
`lib/Tool.php::getSupportedLocales()` / `getSupportedJSLocales()` (`:178-235`), `lib/SystemSettingsConfig.php:165`,
`lib/Tool/Admin.php:64`, `models/DataObject/ClassDefinition/DynamicOptionsProvider/CountryOptionsProvider.php`)
**(20)** `lib/Mail/**` (`Mailer`, `Plugins/RedirectingPlugin`) and `lib/Mail.php`, plus the direct consumers where a defect
originates in the interplay (`bundles/CoreBundle/config/services.yaml:96-104`, `bundles/CoreBundle/config/pimcore/default.yaml:27-30`,
`lib/Helper/Mail.php` (`getDebugInformation()`, `formatDebugReceivers()`), `lib/Templating/TwigDefaultDelegatingEngine.php:70-90`,
`lib/Workflow/Notification/NotificationEmailService.php:124-154`, `lib/Tool.php::getMail()` (`:457-470`), `tests/Unit/Mail/MailTest.php`)
**(21)** `lib/Maintenance/**` (`Executor`, `ExecutorInterface`, `TaskInterface`, `Tasks/*`, `Tasks/DataObject/*`), plus the
direct consumers where a defect originates in the interplay (`bundles/CoreBundle/config/maintenance.yaml`,
`bundles/CoreBundle/src/DependencyInjection/Compiler/RegisterMaintenanceTaskPass.php`, `bundles/CoreBundle/src/Command/MaintenanceCommand.php`,
`lib/Messenger/Handler/MaintenanceTaskHandler.php`, `ScheduledTaskHandler.php`, `bundles/CoreBundle/config/services.yaml:120-129` (lock store),
`bundles/CoreBundle/config/pimcore/default.yaml:49-50`, `lib/helper-functions.php::gzcompressfile()` (`:30-58`), `models/Asset/Image.php:139-160`,
`lib/Document/StaticPageGenerator.php:142-152`, `models/DataObject/Fieldcollection/Definition.php:308`)
**(22)** `lib/Marshaller/**` (`MarshallerInterface` – the only file), checked against its implementations and callers
(`lib/DataObject/BlockDataMarshaller/*`, `lib/DataObject/ClassificationstoreDataMarshaller/*`,
`lib/DataObject/FielddefinitionMarshaller/Traits/RgbaColorTrait.php`, `lib/Element/MarshallerService.php`,
`models/DataObject/ClassDefinition/Data/Block.php:124-205`, `models/DataObject/Classificationstore/Dao.php:139`, `:255`,
`models/DataObject/ClassDefinition/Data/BeforeEncryptionMarshallerInterface.php`, `bundles/CoreBundle/config/marshaller.yaml`)
**(23)** `lib/Messenger/**` (11 message classes, `Handler/*` incl. `HandlerHelperTrait`, `Middleware/CollectGarbageMiddleware`),
plus the direct consumers where a defect originates in the interplay (`bundles/CoreBundle/config/message_handler.yaml`,
`bundles/CoreBundle/config/pimcore/default.yaml:31-54` (transports / routing / bus), `bundles/GenericExecutionEngineBundle/config/pimcore/config.yaml:8-21`,
`bundles/CoreBundle/src/EventListener/MessengerClearRuntimeCacheListener.php`, `lib/Helper/LongRunningHelper.php::cleanUp()` (`:62-130`),
`models/Asset.php:1819-1847`, `models/Asset/Folder.php:150-160`, `doc/01_Getting_Started/02_Advanced_Installation_Topics/01_Symfony_Messenger.md`)
**(24)** `lib/Migrations/**` (`BundleAwareMigration`, `DirectoryAwareVersionComparator`, `FilteredMigrationsRepository`,
`FilteredTableMetadataStorage`), plus the direct consumers where a defect originates in the interplay
(`bundles/CoreBundle/config/pimcore/doctrine_migrations.yaml`, `bundles/CoreBundle/config/migrations.yaml`, `lib/Console/Application.php:94-98`,
`lib/Extension/Bundle/Installer/SettingsStoreAwareInstaller.php:73-118`, `bundles/CoreBundle/src/Command/InternalMigrationHelpersCommand.php`,
`bundles/InstallBundle/src/Installer.php:552-562`)
**(25)** `lib/Model/**` (`AbstractModel`, `ModelInterface`, `Factory`, `FactoryInterface`, `Factory/FallbackBuilder`, `Dao/*`
(`AbstractDao`, `DaoInterface`, `DaoTrait`, `PimcoreLocationAwareConfigDao`), `Listing/*` (`AbstractListing`, `Dao/AbstractDao`,
`Dao/QueryBuilderHelperTrait`, `Traits/*`, `Callable*ListingInterface`), `Paginator/*`, `DataObject/ClassDefinition/ClassDefinitionManager`,
`DataObject/ClassDefinition/Loader/*`, `Asset/Metadata/Loader/*`, `Document/Editable/Loader/*`), plus the direct consumers where a defect
originates in the interplay (`models/Asset.php`, `models/Document.php`, `models/DataObject/AbstractObject.php`, `models/DataObject/Concrete.php`,
`models/User/AbstractUser.php` and their `Dao.php` (`update()` / `updateChildPaths()` / `saveChildData()`), `models/Asset/Image/Thumbnail/Config.php:191-215`,
the nine `PimcoreLocationAwareConfigDao` subclasses, `models/DataObject/Traits/LocateFileTrait.php`, `bundles/CoreBundle/src/Command/ClassesRebuildCommand.php:75-110`,
`tests/Model/DataObject/ListingTest.php`)
**(26)** `lib/Navigation/**` (`Builder`, `Container`, `Page`, `Page/Url`, `Page/Document`, `Iterator/PrefixRecursiveFilterIterator`,
`Renderer/AbstractRenderer`, `Renderer/Menu`, `Renderer/Breadcrumbs`, `Renderer/RendererInterface`), plus the direct consumers where a defect
originates in the interplay (`lib/helper-functions.php::closureHash()` (`:414-429`), `doc/03_Documents/03_Navigation.md`,
`models/Document/Link.php`, `models/Document/Hardlink/Service.php`)
**(27)** `lib/Normalizer/**` (`NormalizerInterface` – the only file), checked against its 44 implementations (`models/DataObject/ClassDefinition/Data/*`,
`models/Asset/MetaData/ClassDefinition/Data/Data.php`, `models/DataObject/Traits/SimpleNormalizerTrait.php`) and its callers
(`models/DataObject/ClassDefinition/Data/Block.php:120-215`, `:995-1035`, `Localizedfields.php:965-1025`, `Objectbricks.php:820-865`,
`Classificationstore.php:1040-1090`, `EncryptedField.php:385-405`, `models/DataObject/Classificationstore/Dao.php:120-150`, `:240-268`)
**(28)** `lib/Request/**` (`Attribute/DataObjectParam` – the only file), plus its consumer
`bundles/CoreBundle/src/Request/ParamResolver/DataObjectParamResolver.php` and `doc/02_MVC/04_Routing_and_URLs/02_Custom_Routes.md:95-103`
**(29)** `lib/Resolver/**` (`ResolverInterface`, `ClassResolver`), plus the direct consumers where a defect originates in the interplay
(`bundles/CoreBundle/config/class_resolvers.yaml`, `bundles/CoreBundle/src/DependencyInjection/PimcoreCoreExtension.php:215-219`, `:278-291`,
`models/Document.php:219-234`, `models/Asset.php:281-296`, `:392-399`, `:775-783`, `models/DataObject/ClassDefinition/Data/Extension/Relation.php:36-70`, `:145-155`)
**(30)** `lib/Routing/**` (`DynamicRouteProvider`, `DocumentRoute`, `DataObjectRoute`, `RouteReference(Interface)`, `Dynamic/*`
(`DocumentRouteHandler`, `DataObjectRouteHandler`, `DynamicRequestContext`, `DynamicRouteHandlerInterface`), `Element/Router`,
`Loader/AttributeRouteControllerLoader`, `Loader/BundleRoutingLoader`), plus the direct consumers where a defect originates in the interplay
(`models/Element/Service.php::getValidKey()` (`:938-970`), `models/Asset.php::getFullPath()` / `getFrontendFullPath()` (`:892-925`),
`lib/Document/Renderer/DocumentRenderer.php:91`, `doc/05_Objects/01_Object_Classes/05_Class_Settings/30_Link_Generator.md`)
**(31)** `lib/SearchClient/**` (`SearchClientInterface`, `Exception/ClientException`) – no implementation or consumer exists in this repository
(the interface is implemented by the external search-client packages, e.g. the OpenSearch / Elasticsearch clients; `composer.json:164`
only suggests `elasticsearch/elasticsearch`)
**(32)** `lib/Security/**` (`SecurityHelper`, `Exception/*`, `Hasher/*` (`AbstractUserAwarePasswordHasher`, `PasswordFieldHasher`,
`PimcoreUserPasswordHasher`, `PasswordHasherFactory`, `Factory/*`, interfaces), `User/*` (`User`, `UserProvider`, `ObjectUserProvider`,
`UserChecker`, `UserLoader`, `TokenStorageUserResolver`, `Exception/*`)), plus the direct consumers where a defect originates in the interplay
(`bundles/CoreBundle/config/pimcore/security.yaml`, `bundles/CoreBundle/config/services.yaml:17-20`, `:169`,
`models/DataObject/ClassDefinition/Data/Password.php:135-318`, `lib/Http/RequestHelper.php:141-160`, `lib/Http/Request/Resolver/EditmodeResolver.php:75-99`,
`bundles/CoreBundle/src/EventListener/Frontend/ElementListener.php:76-100`, `doc/19_Development_Tools_and_Details/10_Security_Authentication/01_Authenticate_Pimcore_Objects.md`)
**(33)** `lib/Serializer/**` (`Serializer`, `Normalizer/ReferenceLoopNormalizer`), plus the direct consumers where a defect originates in the
interplay (`bundles/CoreBundle/config/serializer.yaml`, `bundles/CoreBundle/src/DependencyInjection/Compiler/SerializerPass.php`,
`lib/Tool/Serialize.php::removeReferenceLoops()` (`:61-101`), `lib/Controller/Traits/JsonHelperTrait.php`)

Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-2-BUGS.md](TODO-2-BUGS.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md),
[models TODO-1](../models/TODO-1-CRITICAL.md) (Scope 6: C-32 unvalidated class / field / select-option identifiers in generated
code, C-44 field-collection property collisions; I-83 code generator – not repeated),
[CoreBundle TODO-1/2/3](../bundles/CoreBundle/TODO-1-CRITICAL.md) (Scope 4: C-03 `pimcore:maintenance-mode --disable`,
C-06 / B-26 `exit` in commands, I-29 `lib/Console` helpers – not repeated),
[models TODO-1](../models/TODO-1-CRITICAL.md) (C-09 path traversal through `getVarConfigFile()` – the repository
fix is tracked there, not repeated),
[models TODO-1](../models/TODO-1-CRITICAL.md) (C-23 site cache tagging – not repeated),
[CoreBundle TODO-1](../bundles/CoreBundle/TODO-1-CRITICAL.md) (C-09 static page generator / session check – not repeated)

Errors that cause data corruption, stale data that is never invalidated, or a whole subsystem silently not working.
IDs are stable – refer to them as `C-xx` in later sessions. Line numbers are from commit `765372d338` (Scope 2–15: `765372d338`, Scope 16–33: `033aa183ef`).
Grouped by area: **Core cache handler / write lock** C-01–C-03, **Composer** – (no critical findings),
**Config** C-04–C-05, **Console** – (no critical findings),
**Controller** – (no critical findings),
**DataObject** C-06–C-07, **Db** – (no critical findings),
**DependencyInjection** – (no critical findings),
**Document** C-08, **Element** – (no critical findings),
**Event** – (no critical findings),
**Exception** – (no critical findings),
**Extension** – (no critical findings),
**Helper** – (no critical findings),
**Http** C-09,
**HttpKernel** – (no critical findings),
**Image** – (no critical findings),
**Loader** – (no critical findings),
**Localization** – (no critical findings),
**Mail** C-10,
**Maintenance** – (no critical findings),
**Marshaller** – (no critical findings),
**Messenger** – (no critical findings),
**Migrations** – (no critical findings),
**Model** – (no critical findings),
**Navigation** – (no critical findings),
**Normalizer** – (no critical findings),
**Request** – (no critical findings),
**Resolver** – (no critical findings),
**Routing** – (no critical findings),
**SearchClient** – (no critical findings),
**Security** C-11,
**Serializer** – (no critical findings).

---

## Core cache handler / write lock

## [ ] C-01 – The cross-process write lock is never read: `hasLock()` only returns `true` for the process that set it

**File:** `lib/Cache/Core/WriteLock.php:126-151`, `:73-89`; `lib/Cache/Core/CoreCacheHandler.php:790-799`

```php
public function hasLock(): bool
{
    if (!$this->enabled || !$this->lockInitialized) {
        return false;            // lockInitialized is only set by lock() → initializeLock()
    }
```

- The write lock exists so that process B does not write its save queue while process A is clearing tags / the
  whole cache (`remove()`, `clearTags()`, `clearAll()`, `addTagClearedOnShutdown()` all call `lock()`).
- `lockInitialized` is set only in `initializeLock()`, which is called only from `lock()`. A process that only
  *reads* and queues items (a normal frontend request) never calls `lock()`, so `hasLock()` returns `false` at
  `:128` without looking at the storage. `writeSaveQueue()` (`CoreCacheHandler.php:790`) always writes.
- Result: request B loads data that A is about to invalidate (listings, navigation, redirect table, config arrays,
  rendered snippets – everything that is not an element, because elements are re-fetched in `storeCacheData()`),
  A invalidates the tags, B writes the old data at shutdown. The entry stays stale until the next clear of one of
  its tags or the TTL (`DoctrineDbalAdapter` default: 1 year, `cache.yaml:23`).
- The only case where `hasLock()` works is the process that set the lock itself – which already knows, because it
  also has the tags in `$clearedTags`. The cross-process function of the class is dead.
- The tests (`testWriteLockIsSetOn*`) only check that the lock is set in the same handler; none checks that a second
  handler with a separate `WriteLock` instance sees it.

**Fix direction:** in `hasLock()` call `initializeLock()` (or read the item) instead of returning early; re-read the
storage item on every `hasLock()` call (it is called once per shutdown). Better: replace with the Symfony Lock
component (I-04). Add a two-handler test (I-12). Fix together with B-01.

---

## [ ] C-02 – Element cache tags are computed from the caller's instance, but a different (fresh) instance is stored

**File:** `lib/Cache/Core/CoreCacheHandler.php:814`, `:819`, `:461-511`; `:310-323`

- `writeSaveQueue()` computes tags with `prepareCacheTags($key, $queueItem->getData(), …)` → `getCacheTags()` of the
  instance that was passed to `save()` (`:814`, `:401`). This instance may have been modified in memory after
  loading (setters for rendering, unsaved relations, inherited values resolved differently).
- `storeCacheData()` then throws that instance away and stores a fresh copy from DB
  (`Service::getElementById($type, $id, ['force' => true])`, `:466`). The stored entry carries the tags of the
  *modified* instance, not of the stored data.
- Example: an object has a relation to `object_7` in DB. A request loads it, replaces the relation in memory with
  `object_9` (no save) and the object is queued (`getById()` → `Cache::save()`). The cache entry contains the
  relation to `object_7` but is tagged `object_9`. Saving `object_7` later does not invalidate the entry → the
  cached object keeps showing the old data of `object_7` until a full clear / TTL. Same in the immediate path
  (`:318` before `:323`).
- The original design (check `__isBasedOnLatestData()` on the caller's instance, store the caller's instance) was
  replaced by the re-fetch, but the tag calculation was not moved with it.

**Fix direction:** compute the tags inside `storeCacheData()` from the fresh copy (after `:466`) and run the
`clearedTags` / `tagsIgnoredOnSave` checks there. Keep `prepareCacheTags()` before the store only for non-element
data. Add a test (I-12).

---

## [ ] C-03 – `writeInProgress` is never reset on an exception: all later cache writes of the process are silently dropped

**File:** `lib/Cache/Core/CoreCacheHandler.php:459`, `:466`, `:494-510`, `:513-517`, `:541`; `:287-289`, `:445-447`

- `storeCacheData()` sets `$this->writeInProgress = true` (`:459`) and resets it only on the two normal exit paths
  (`:482`, `:541`). Any exception in between leaves it `true`:
  - `Service::getElementById()` (`:466`) – DB error, `POST_LOAD` listener throws, class definition missing;
  - `getDeepCopyInstance()` / `$copier->copy()` (`:494-510`) – deep copy of closures, resources, `marshalForCache()`
    of a broken `BlockElement`;
  - `$item->tag($tags)` (`:516`) – Symfony throws `InvalidArgumentException` for a tag with reserved characters
    (`{}()/\@:`), e.g. a tag built from a user-defined key;
  - `$this->pool->save()` – adapter / marshaller exceptions that are not caught by the adapter.
- From then on `save()` (`:287`) and `storeCacheData()` (`:445`) return `false` without logging. In a messenger
  worker, a FrankenPHP / RoadRunner worker or a long CLI import with `handleCli`, cache writing is off for the rest of
  the process lifetime, and nobody sees it.
- The exception also escapes `writeSaveQueue()` → `shutdown()` → `Pimcore::shutdown()` (no try/catch in
  `lib/Pimcore.php:165-177`): the save queue is not reset and `WriteLock::removeLock()` (`:870`) never runs, so the
  lock stays in storage until its TTL.

**Fix direction:** `try { … } finally { $this->writeInProgress = false; }` around the body of `storeCacheData()`;
catch `Throwable` per item in `writeSaveQueue()` (log with key, continue with the next item); in `shutdown()` put
`removeLock()` in a `finally`.

---

## Composer

No critical findings in Scope 2 (`lib/Composer/PackageInfo.php`). Its defects are listed in
[TODO-2-BUGS.md](TODO-2-BUGS.md) B-15–B-16 and [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-13–I-15.

---

## Config

## [ ] C-04 – `fetchAllKeysByReadTargets()` ignores YAML configs with the default settings: select options defined in YAML are missing from the admin and from the class rebuild

**File:** `lib/Config/LocationAwareConfigRepository.php:271-278`; `models/DataObject/SelectOptions/Config/Listing/Dao.php:33`;
`bundles/CoreBundle/src/Command/ClassesRebuildCommand.php:169`, `ClassesDefinitionsBuildCommand.php:95`;
`bundles/CoreBundle/src/DependencyInjection/ConfigurationHelper.php:69-74`; `bundles/CoreBundle/src/DependencyInjection/Configuration.php:137-160`

```php
if ($this->storageConfig[self::READ_TARGET][self::TYPE] === self::LOCATION_SYMFONY_CONFIG) {
    return array_keys($this->containerConfig);
}
return array_unique(SettingsStore::getIdsByScope($this->settingsStoreScope));
```

- The default for `read_target.type` is `null` (`ConfigurationHelper.php:74`). `loadConfigByKey()` (`:75-83`) treats
  `null` as "container config first, then settings store". `fetchAllKeysByReadTargets()` treats `null` as
  "settings store only".
- `select_options` uses the defaults (write target `symfony-config`, read target `null`), so select-option sets
  saved by the admin are written as YAML to `var/config/select_options`. `SelectOptions\Config\Listing\Dao::loadList()`
  takes its ids from `fetchAllKeysByReadTargets()` (`Listing/Dao.php:33`) and never sees them:
  - the admin select-options tree does not list them;
  - `pimcore:deployment:classes-rebuild` (`ClassesRebuildCommand.php:169`) and `pimcore:build:classes`
    (`ClassesDefinitionsBuildCommand.php:95`) iterate the same listing, so the generated enum / helper classes for
    YAML-stored select options are not built. On a fresh deployment (empty `var/classes`) every class that uses them
    fails with "class not found".
- Nodes without a `read_target` (`image_thumbnails`, `predefined_properties`, `predefined_asset_metadata`, …,
  `Configuration.php:137-146`) give an undefined array key at `:273`. In debug this is an `ErrorException`, in
  prod a warning followed by a silent fallback to settings-store ids. The fix direction of models B-188 / B-206
  (switch those listings to `loadIdListByReadTargets()`) would therefore break those listings too. Fix this first.

**Fix direction:** use the same rule as `loadConfigByKey()`:
`match ($this->getReadTargets()[0] ?? null) { SYMFONY_CONFIG => container keys, SETTINGS_STORE => settings-store ids,
null => merged + unique, DISABLED => [] }`. Use `getReadTargets()` instead of reading the array directly. Add a test
for each read-target value (I-22).

---

## [ ] C-05 – Admin-entered config values are not escaped for the container: `%param%` / `%env(…)%` are resolved (secret disclosure) or break the container build

**File:** `lib/Config/LocationAwareConfigRepository.php:195-205`, `:207-234`; `:289-321`; `lib/Kernel.php:78-131`

- In the `symfony-config` write target, `writeYaml()` dumps the admin data to `var/config/<type>/<key>.yaml`. The
  Kernel / bundle extensions then load these files with `YamlFileLoader`, so every value goes through container
  parameter resolution.
- `searchAndReplaceMissingParameters()` escapes `%name%` only when the parameter **does not** exist, and skips
  `%env(…)%` on purpose (`:225-231`). A user who can edit any location-aware config can therefore write
  `%env(APP_SECRET)%`, `%env(DATABASE_URL)%` or `%kernel.secret%`. Examples are a thumbnail, a perspective, a custom
  view, a predefined property, a select option label, or a custom report / static route through the bundle
  extensions. After the next container build the config holds the resolved secret, and the admin UI shows it back
  to that user.
- It only checks the **first** placeholder per value (`preg_match()`, `:222`), then rewrites **all** placeholders
  (`preg_replace()`, `:230`):
  - `"%kernel.debug% %foo%"`: the first parameter exists, so nothing is escaped. `%foo%` remains, and the next
    container build fails with *"You have requested a non-existent parameter"*. The whole application (frontend,
    admin, console) stays down until the file is fixed by hand;
  - `"%env(X)% %foo%"`: the `continue` at `:226` skips the rest of the value, with the same failure;
  - `"%foo% %kernel.debug%"`: both are escaped, so the existing parameter is silently turned into literal text.
- Array **keys** are never processed (`:211`). A key such as a select-option value or a custom-layout field name
  containing `%x%` is resolved or breaks the build the same way.
- In production (debug off) the `symfony-config` target is "not writeable", but `saveConfig()` does not check this
  (B-18), so the file is written anyway and takes effect at the next deployment / cache clear.

**Fix direction:** treat admin data as literal. Escape every `%` in keys and values (`str_replace('%', '%%', …)`,
recursively). Allow parameters only through an explicit, documented allowlist, if at all. Add a test that
round-trips `%env(APP_SECRET)%`, `%kernel.debug% %foo%` and `100%` unchanged (I-22). See I-17.

---

## Console

No critical findings in Scope 4 (`lib/Console/**`). Its defects are listed in [TODO-2-BUGS.md](TODO-2-BUGS.md)
B-26–B-35 and [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-23–I-29. The `exit` calls that leave maintenance mode
on (`Traits/Parallelization.php:54`) are CoreBundle C-06 / B-26 (not repeated).

---

## Controller

No critical findings in Scope 5 (`lib/Controller/**`). Its defects are listed in [TODO-2-BUGS.md](TODO-2-BUGS.md)
B-36–B-42 and [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-30–I-37.

---

## DataObject

## [ ] C-06 – Class description breaks out of the generated doc block: PHP code injection into `var/classes/DataObject/<Class>.php`

**File:** `lib/DataObject/ClassBuilder/ClassBuilder.php:47-55`; `models/DataObject/ClassDefinition.php:961` (`setDescription()`,
no validation); `:331-367` (`save()` validates name / id / parent class / traits, not the description)

```php
$description = str_replace(['/**', '*/', '//', "\n"], ['', '', '', "\n * "], $description);
$cd .= ' * ' . $description . "\n";
```

- `str_replace()` with arrays runs **one pass per search string**, and each pass can create the next match.
  `"**//"` → the `*/` pass removes the middle `*/` and leaves `"*/"` → the doc block is closed.
- Example description (one line):
  `**// namespace X; system($_GET['c']); /*`
  produces `* */ namespace X; system($_GET['c']); /*` inside the header. The attacker's `/*` is closed by the builder's
  own ` */`. The next `namespace Pimcore\Model\DataObject;` is a second unbracketed namespace, which PHP allows. The
  file compiles, and `system()` runs **every time the class is autoloaded**: any object load in frontend, admin, CLI
  or workers.
- Reproduced with PHP 8.4 CLI on a copy of the builder's header logic: the injected statement runs when the file is
  executed.
- A variant without a working payload (`**//` alone) gives a syntax error. Every request that touches the class then
  fatals (DoS), until the file is fixed by hand.
- Who can do it: every user with the `classes` permission (not necessarily an admin), class-definition JSON import
  (admin UI and `pimcore:definition:import:class`), and any code that calls `setDescription()`. The definition file
  itself (`var_export`) is safe. Only the generated PHP class is affected.
- Models C-32 (class name / id / field names reaching generated code) is related but does not cover the description.
  Not repeated there.

**Fix direction:** do not try to sanitize comment terminators. Either drop the description from the generated file, or
emit it with `str_replace('*/', '*\/', …)` **after** stripping all `*` / `/` sequences in a loop until stable. Best:
generate the doc block with a code generator that escapes comments (I-38). Add a regression test with `**//`,
`*/*/`, `/***/` (I-45).

---

## [ ] C-07 – `SelectOptionsEnumBuilder` escapes only `'`: a trailing backslash in a value or label breaks out of the string (code injection / fatal enum)

**File:** `lib/DataObject/ClassBuilder/SelectOptionsEnumBuilder.php:109-142`, `:144-165`, `:182-185`;
`models/DataObject/SelectOptions/Config.php:240-243`, `:369` (no validation before generation)

```php
protected function escapeSingleQuote(string $value): string { return str_replace("'", "\\'", $value); }
// case %caseName% = '%optionValue%';      self::%caseName% => '%optionLabel%',
```

- In a single-quoted PHP string `\'` is an escaped quote and `\\` is one backslash. A value or label ending in `\`
  (`C:\`, `path\`) becomes `'C:\'`: the closing quote is escaped, so the string continues into the next line of the
  template.
  - With a second, crafted option the text after it is parsed as code (code injection into
    `var/classes/DataObject/SelectOptions/<Id>.php`, executed whenever the enum is autoloaded: every object of every
    class that uses the select options).
  - Without a payload it is a syntax error, and every class whose fields use the enum fatals.
- Reproduced: a value `C:\` followed by a second case gives *"syntax error, unexpected identifier"* (`php -l`).
- `\\` in a value is written unchanged and read back by PHP as `\`. The enum's backing value then differs from the
  value stored in the object tables. `tryFrom()` returns `null` / `from()` throws for existing data, silently.
- Labels are often free text written by editors (the select-options editor needs only the `selectoptions` /
  `classes` permission), so a trailing backslash is not far-fetched.
- `Config::save()` does not validate values or labels. Generation runs during save (`Config.php:369`) and in
  `pimcore:build:classes`, so a broken config also breaks deployments.

**Fix direction:** emit literals with `var_export($value, true)`, which handles both `'` and `\`. Validate values in
`Config::save()` (non-empty, unique, max length) so errors appear in the editor instead of in the generated file.
Regression tests with `\`, `\\`, `'`, `\'` (I-45).

---

## Db

No critical findings in Scope 7 (`lib/Db/**`, `lib/Db.php`). Its defects are listed in [TODO-2-BUGS.md](TODO-2-BUGS.md)
B-53–B-57 and [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-46–I-48.

---

## DependencyInjection

No critical findings in Scope 8 (`lib/DependencyInjection/**`). See [TODO-2-BUGS.md](TODO-2-BUGS.md) B-58 and
[TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-49.

---

## Document

## [ ] C-08 – Ghostscript page-count command embeds the asset's local path inside a PostScript string literal

**File:** `lib/Document/Adapter/Ghostscript.php:142-157` (`:152`); `lib/Helper/TemporaryFileHelperTrait.php:37-49`;
asset key sanitizer `models/Element/Service.php:938-970` (`:957-963`)

- `buildPageCountCommand()` builds:
  `... -c '(<localFile>) (r) file runpdfbegin pdfpagecount = quit'`.
  The whole PostScript program is passed through `escapeshellarg()`, so the **shell** layer is safe. The problem is
  one layer deeper: `<localFile>` is placed inside a PostScript `(...)` string **without** escaping the PostScript
  metacharacters `(`, `)` and `\`.
- For an asset stored on a **local** flysystem, `getLocalFileFromStream()` returns the asset's own path, whose last
  segment is the asset key. `Element\Service::getValidKey(..., 'asset')` (`:957-963`) strips Windows-reserved and
  URL-reserved characters but **keeps** `(`, `)` and spaces. So an asset filename may contain `(` / `)`.
- A filename containing `)` ends the PostScript string early, and the remaining text is interpreted as PostScript by
  `gs`. This is reachable by uploading / renaming an asset (users with asset edit permission), then triggering a page
  count (document thumbnail, preview, `getPageCount()`).
- Risk is bounded by Ghostscript's SAFER sandbox (default since 9.50; the command also adds
  `--permit-file-read` only for the exact temp path). SAFER blocks file and pipe access, so this is primarily a way to
  break the page count / crash the worker, not a confirmed command execution. It must still be treated as a code-build
  injection and fixed. The same `(...)` pattern is not used in `saveImage()` / `convertPdfToText()`, which pass the
  path as its own argv element.
- `getText()` with the pure-ghostscript fallback writes to
  `PIMCORE_SYSTEM_TEMP_DIRECTORY . '/pdf-text-extract-' . uniqid() . '.txt'` (`:254`); `uniqid()` is predictable and
  not unique across processes, so concurrent extractions can collide (see B-59).

**Fix direction:** do not interpolate the path into PostScript. Run the page count from a PostScript file / `-sFile=`
argv argument, or escape `\ ( )` for the PostScript string. Always keep `-dSAFER` (do not rely on the default). As
defence in depth, operate on a temp copy with a generated safe name (no `( ) \` space), as `saveImage()` effectively
does. Add a regression test with an asset key containing `)` and `\`. Related: models C-32 (identifier validation).

---

## Element

No critical findings in Scope 10 (`lib/Element/**`). See [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-58–I-59.

---

## Event

No critical findings in Scope 11 (`lib/Event/**`). See [TODO-2-BUGS.md](TODO-2-BUGS.md) B-69–B-72 and
[TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-60–I-64.

---

## Exception

No critical findings in Scope 12 (`lib/Exception/**`). See [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-65.

---

## Extension

No critical findings in Scope 13 (`lib/Extension/**`). The most severe item is B-73 (bundle uninstall can delete other
bundles' migration records); it needs a namespace-prefix relationship between bundles, so it is listed as a bug. See
[TODO-2-BUGS.md](TODO-2-BUGS.md) B-73–B-77 and [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-66–I-71.

---

## Helper

No critical findings in Scope 14 (`lib/Helper/**`). See [TODO-2-BUGS.md](TODO-2-BUGS.md) B-78–B-87 and
[TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-72–I-77. `StopMessengerWorkersTrait` is covered by B-27, and the
local-path behaviour of `TemporaryFileHelperTrait` by C-08 (not repeated).

---

## Http

## [ ] C-09 – `CodeInjector::injectIntoDomSelector()` runs `html_entity_decode()` over the whole page: escaped content becomes live HTML

**File:** `lib/Http/Response/CodeInjector.php:124-143` (`:130-138`)

- For any selector other than `head` / `body`, the code is written via `$node->textContent = …` (so the existing markup
  of the target node *and* the injected code are stored as escaped text), and then the **entire** document is passed
  through `html_entity_decode($dom->outerHtml())`.
- That decode is not limited to the injected node: every entity in the page is decoded. Text that templates escaped on
  purpose (user-supplied names, search terms, comments rendered as `&lt;script&gt;`) is turned back into real markup.
  Any page that gets a code injection with a custom CSS selector therefore loses its output escaping (XSS exposure),
  and pages with legitimate entities (`&amp;` in URLs, `&lt;` in code samples) render wrong.
- `$charset` is accepted but unused; the DOM round trip and `trim()` also reformat the whole response.
- Verify which features call `inject()` with a custom selector (code snippets / tag manager configs) to size the impact.
- `ResponseHelper::isHtmlResponse()` decides when injection runs; it sniffs the body (B-94).

**Fix direction:** never decode the whole document. Parse the code as a fragment (`DOMDocument::createDocumentFragment()`
+ `appendXML()` / HTML5 parser) and insert the nodes before / after / instead of the target's children, then serialize
without entity decoding.

---

## HttpKernel

No critical findings in Scope 16 (`lib/HttpKernel/**`). The most severe item is B-95: with OPcache preloading set up as the
Performance Guide describes, the cache warmer preloads the generated DataObject classes, so class changes made in the admin
are not picked up by PHP-FPM until a restart. It depends on that setup, so it is listed as a bug. See
[TODO-2-BUGS.md](TODO-2-BUGS.md) B-95–B-101 and [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-81–I-87.
No hard Symfony 7.4 / PHP 8.4 incompatibility was found in the 13 files (`CacheWarmerInterface::warmUp(string, ?string): array`
matches 7.4, typed interface constants are already used, no implicit nullable parameters). `vendor/` is not installed, so
statements about Symfony internals (`Preloader`, `cache:clear` running optional warmers) are **not verified locally**.

---

## Image

No critical findings in Scope 17 (`lib/Image/**`, `lib/Image.php`). The most severe items are B-102 (the pixel-by-pixel
alpha scan can block a thumbnail request for seconds to minutes) and B-106 (several `TypeError` / `ValueError` crashes in
the GD adapter). See [TODO-2-BUGS.md](TODO-2-BUGS.md) B-102–B-113 and [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)
I-88–I-95. No hard Symfony 7.4 / PHP 8.4 incompatibility was found (`HtmlToImage.php:64` already uses the PHP 8.4
`new Foo()->bar()` syntax). `imagedestroy()` is a no-op since PHP 8.0 and is deprecated in PHP 8.5 (I-91).

---

## Loader

No critical findings in Scope 18 (`lib/Loader/**`). See [TODO-2-BUGS.md](TODO-2-BUGS.md) B-114–B-116 and
[TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-96–I-99. No Symfony 7.4 / PHP 8.4 incompatibility was found in the 8 files.

---

## Localization

No critical findings in Scope 19 (`lib/Localization/**`). See [TODO-2-BUGS.md](TODO-2-BUGS.md) B-117–B-122 and
[TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-100–I-104. The closest to security-relevant is B-119 (the locale is
put into an `include` path without validation); with Symfony's default route requirement (`[^/]+`) a `/` cannot reach it,
so it is filed as a bug/hardening item, not as critical. No Symfony 7.4 / PHP 8.4 incompatibility was found in the 3 files.

---

## Mail

> Scope 20 notes: `vendor/` is not installed. Symfony Mime / Mailer behaviour was checked against **symfony/mime 6.4.18**
> and Twig **3.20 / 3.22** from other local projects (same code paths as in 7.4 / current Twig, but the 7.4 versions are
> **not verified locally**). Not repeated: [models TODO-1](../models/TODO-1-CRITICAL.md) C-42 (the blocklist is never
> applied, `Mail::filterLogAddresses()`), [CoreBundle TODO-1](../bundles/CoreBundle/TODO-1-CRITICAL.md) C-12 (the Twig
> sandbox allows every method and exposes `container`).

## [ ] C-10 – `Pimcore\Mail` runs its own rendered output through Twig a second time: user data passed as a param is executed as a template

**Files:** `lib/Mail.php:427-451` (`send()`), `:593-625` (`renderParams()`), `:654-679` (`getBodyHtmlRendered()`),
`:688-721` (`getBodyTextRendered()`); `lib/Workflow/Notification/NotificationEmailService.php:141-153`

```php
$bodyHtmlRendered = $this->getBodyHtmlRendered();   // pass 1: Twig(html) with params
$this->html($bodyHtmlRendered);                      // rendered output becomes the html body
$bodyTextRendered = $this->getBodyTextRendered();    // no text body → calls getBodyHtmlRendered() again
                                                     // → getHtmlBody() is now the rendered html → pass 2: Twig(rendered html)
```

- Passing user input as a **param** is the documented safe way (`$mail->setParams(['name' => $form['name']])`), and
  the html body uses `{{ name }}`. Pass 1 inserts the value. Because no text body was set, `getBodyTextRendered()`
  calls `getBodyHtmlRendered()` again. That method reads the html body that `send()` has just replaced with the rendered
  output and passes it to `renderParams()` again. A value such as `{{ 7*7 }}` is evaluated in pass 2, and the result is
  in the text part of the mail.
- Pass 2 also runs on mails without a document (`html()` set in code), and it re-runs CSS inlining and the absolute-path
  rewriting on the already-processed html.
- Every string set with `html()`, `text()` or `subject()` is treated as a Twig template too (`:633-646`, `:688-695`),
  although these are Symfony `Email` methods for literal content. Code that builds the body or subject itself is affected,
  for example `NotificationEmailService::sendTemplateMail()`: the subject contains the element's full path and the body
  is a rendered Twig template (`:141-153`). Code such as `->html('<p>' . htmlspecialchars($message) . '</p>')` is also
  affected, because `htmlspecialchars()` does not escape `{{`.
- The rendering uses the sandboxed Twig environment. As long as CoreBundle C-12 is open, the sandbox allows every
  method call and exposes `container`. A public contact form that sends a confirmation mail to the submitter, with the
  name passed as a param, would then return `{{ container.getParameter('kernel.secret') }}` to the attacker in the text
  part. Without C-12 the sandbox still allows the configured tags, filters and functions, and a stray `{{` or `{%` in
  user text makes the text part empty: the `Twig\Error\SyntaxError` is caught at `:714-717`.
- Each distinct rendered body is a new string-template source, so Twig compiles and caches a new template class per
  mail (`createTemplate()` names it `pimcore_email_body (string template <hash>)`). The Twig cache grows with every
  personalised mail. Whether the compiled string templates are written to the file cache is **not verified locally**.
- The end-to-end exploit was **not run locally** (no installed app). The double-render path follows from the code above.
  The template naming was checked in Twig 3.20 / 3.22.

**Verify:** `MailTest::testHtmlBodyRenderedWithParams()` with a param value `{{ 7*7 }}` and no text body; call `send()`
with a `null://` transport; the text part contains `49`.

**Fix direction:**
- Render once. `send()` renders html and subject once and marks the mail as rendered. The text part is derived from the
  rendered html with html-to-text only, without `renderParams()`. `getBodyTextRendered()` must not call
  `getBodyHtmlRendered()` again.
- Separate literal content from templates. Keep `html()`, `text()` and `subject()` literal, and add explicit
  `htmlTemplate()`, `textTemplate()` and `subjectTemplate()` methods, or a `setRenderContentAsTemplate(bool)` switch
  with a deprecation period. Document content stays a template.
- Escape params for the html context (Twig auto-escape already does this) and never feed rendered output back into Twig.
- Fix CoreBundle C-12 as well.

---

## Maintenance

> Scope 21 notes: all 21 files in `lib/Maintenance/**` were read, plus the consumers listed in the scope header. No Symfony 7.4 /
> PHP 8.4 incompatibility was found (typed class constants and `readonly` classes need PHP 8.3 / 8.2, fine for the target).
> `vendor/` is not installed; Symfony Lock / DependencyInjection behaviour was checked against the 6.4 sources of another
> local project (same logic in 7.4, **not verified locally**). Not repeated: [models TODO-1](../models/TODO-1-CRITICAL.md)
> C-36 / C-37 and [models TODO-2](../models/TODO-2-BUGS.md) B-210–B-214 (`ScheduledTasksTask`: stalled queue, deleted-user
> permissions, `publish-version` of another element, order, double execution), models B-223 / I-107
> (`VersionsCleanupTask`: stalled cleanup, unbounded `NOT IN`, auto-save `null` crash), models B-238 / B-239 / I-124 TmpStore
> (`TmpStoreCleanupTask`, `Executor::get/setLastExecution()` type loss), CoreBundle TODO-3 I-26 and the
> `RegisterMaintenanceTaskPass` item (`$tags[0]` only, typo, attribute).

No critical findings in Scope 21. The most severe items are B-132 (the broken-views cleanup also drops views that are only
inaccessible, including the `object_<classId>` views that DataObject listings query) and B-133 (the "once per night" lock of
the LQIP task never blocks a re-run). See [TODO-2-BUGS.md](TODO-2-BUGS.md) B-132–B-138 and
[TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-111–I-116.

---

## Marshaller

No critical findings in Scope 22 (`lib/Marshaller/MarshallerInterface.php`). The implementations were scanned in Scope 6
(`lib/DataObject/**`) and the locator service in Scope 10 (`lib/Element/MarshallerService.php`); their items are not repeated.
See [TODO-2-BUGS.md](TODO-2-BUGS.md) B-139 and [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-117–I-118. No Symfony 7.4 /
PHP 8.4 incompatibility was found.

---

## Messenger

No critical findings in Scope 23 (`lib/Messenger/**`). See [TODO-2-BUGS.md](TODO-2-BUGS.md) B-140–B-143 and
[TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-119–I-122. No Symfony 7.4 / PHP 8.4 incompatibility was found.

---

## Migrations

No critical findings in Scope 24 (`lib/Migrations/**`). The most severe item is B-144 (`doctrine:migrations:rollup --prefix=…`
deletes the executed-migration records of **all** bundles); it needs a manual command, so it is listed as a bug. See
[TODO-2-BUGS.md](TODO-2-BUGS.md) B-144–B-147 and [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-123–I-124.

---

## Model

No critical findings in Scope 25 (`lib/Model/**`). The most severe item is B-148 (the `__call()` guard in `AbstractModel` does
not work, so `$asset->update()` / `$document->update()` / `$user->update()` from outside run the DAO's raw `update()`); its impact
depends on who can call model methods (project code, and Twig templates – see CoreBundle C-12), so it is listed as a bug. See
[TODO-2-BUGS.md](TODO-2-BUGS.md) B-148–B-154 and [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-125–I-129.

---

## Navigation

No critical findings in Scope 26 (`lib/Navigation/**`). Labels and attribute values are HTML-escaped by the renderers. The most
severe items are B-155 (a navigation loaded from the cache – the default – has stale page keys, so `onlyActiveBranch` /
`expandSiblingNodesOfActiveBranch` menus and `hasPage()` / `removePage()` / `addPage()` misbehave) and B-156 (cache key collisions
between different callbacks). See [TODO-2-BUGS.md](TODO-2-BUGS.md) B-155–B-160 and [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)
I-130–I-133.

---

## Normalizer

No critical findings in Scope 27 (`lib/Normalizer/NormalizerInterface.php`). See [TODO-2-BUGS.md](TODO-2-BUGS.md) B-161 and
[TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-134–I-135. No Symfony 7.4 / PHP 8.4 incompatibility was found.

---

## Request

No critical findings in Scope 28 (`lib/Request/Attribute/DataObjectParam.php`). See [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-136.

---

## Resolver

No critical findings in Scope 29 (`lib/Resolver/**`). See [TODO-2-BUGS.md](TODO-2-BUGS.md) B-162 and [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)
I-137. No Symfony 7.4 / PHP 8.4 incompatibility was found.

---

## Routing

No critical findings in Scope 30 (`lib/Routing/**`). The most severe item is B-163 (a document key or URL slug containing `{…}` makes
Symfony's route compiler throw – a 500 for that page – or breaks URL generation); it needs an editor to create such a key, so it is
listed as a bug. See [TODO-2-BUGS.md](TODO-2-BUGS.md) B-163–B-166 and [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-138–I-139.

---

## SearchClient

No critical findings in Scope 31 (`lib/SearchClient/**`). See [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md) I-140.

---

## Security

> Scope 32 notes: all 18 files in `lib/Security/**` were read, plus the consumers listed in the scope header. Symfony behaviour was checked
> with **symfony/password-hasher 6.4** from another local project (same `PlaintextPasswordHasher` in 7.4, **not verified locally**). Not
> repeated: admin session refresh / `isEqualTo()` / deleted admin user in `UserProvider::refreshUser()` ([models TODO-1](../models/TODO-1-CRITICAL.md)
> C-41), role-id cache poisoning (models C-40), last-login throttling and user instance mismatch (models B-235), 2FA config wipe (models B-232),
> the other worker-runtime state (CoreBundle C-08).

## [ ] C-11 – `UserLoader` caches the admin user on a shared service: in worker runtimes anonymous visitors get admin preview / version / unpublished access

**Files:** `lib/Security/User/UserLoader.php:27-72`; `bundles/CoreBundle/config/services.yaml:169`; `lib/Http/RequestHelper.php:141-160`;
`bundles/CoreBundle/src/EventListener/Frontend/ElementListener.php:76-100`; `lib/Http/Request/Resolver/EditmodeResolver.php:75-99`;
`bundles/CoreBundle/src/EventListener/Frontend/EditmodeListener.php:56`

```php
public function getUser(): ?UserModel
{
    if (null === $this->user) {
        $user = $this->loadUser();          // token storage or Authentication::authenticateSession()
        if ($user) { $this->user = $user; } // kept for the lifetime of the service
    }
    return $this->user;
}
```

- `UserLoader` is a shared service without `ResetInterface` / `kernel.reset`. The first request in which an admin is found (an editor
  previewing a page) stores that `Pimcore\Model\User` in `$this->user` for the rest of the process.
- Under classic PHP-FPM the container lives for one request, so this is harmless. Under a worker runtime (FrankenPHP worker mode, RoadRunner,
  Swoole – `symfony/runtime ^7.4` is required) the same object answers every later request of that worker (see CoreBundle C-08 for the
  general problem).
- `RequestHelper::isFrontendRequestByAdmin()` is `true` for **any** request that carries `pimcore_preview`, `pimcore_editmode`,
  `pimcore_version`, `pimcore_admin` or `pimcore_object_preview` as a query / body parameter (`:145-153`) – anyone can add them.
  `ElementListener` then only asks `$this->userLoader->getUser()` (`:82-84`): with the cached admin, an anonymous visitor gets unpublished
  documents (`:87`) and `handleAdminUserDocumentParams()` (preview, any `pimcore_version`, editmode parameters); `EditmodeResolver` uses the same
  user (`:93`).
- The path follows from the code; an end-to-end worker run was **not done locally**.

**Verify:** two requests through one kernel (`$kernel->handle()` twice): first an authenticated admin preview, then an anonymous request with
`?pimcore_preview=1` to an unpublished document. Expected 403; per code the second request renders the document.

**Fix direction:** do not cache across requests – keep the user in a request attribute (`$request->attributes->set('_pimcore_admin_user', …)`)
or implement `ResetInterface` (autoconfigured `kernel.reset`) and clear `$user` there; never treat the query parameters alone as an admin
signal before the session check succeeded.

---

## Serializer

No critical findings in Scope 33 (`lib/Serializer/**`). See [TODO-2-BUGS.md](TODO-2-BUGS.md) B-171 and [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)
I-143. No Symfony 7.4 / PHP 8.4 incompatibility was found.
