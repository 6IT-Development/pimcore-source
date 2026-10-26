# lib – TODO 3: Improvements

Scope: **(1)** `lib/Cache/**` (`Core/CoreCacheHandler`, `Core/WriteLock`, `Core/CacheQueueItem`,
`Core/CacheMarshallerInterface`, `Core/Exception/InvalidArgumentException`, `RuntimeCache`, `Tool/Warming`,
`Symfony/CacheClearer`, `FullPage/SessionStatus`), plus the direct consumers where the change has to be made together
(`lib/Cache.php` facade, `bundles/CoreBundle/config/cache.yaml`, `bundles/CoreBundle/config/services.yaml:36-37`,
`bundles/CoreBundle/config/event_listeners.yaml:110-112`) and `tests/Cache/Core/*`
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
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-2-BUGS.md](TODO-2-BUGS.md),
[CoreBundle TODO-1/2](../bundles/CoreBundle/TODO-1-CRITICAL.md) (Scope 15: C-08 `ResponseStack` / `OutputTimestampResolver` never reset,
B-50 empty matcher in the debug-toolbar exclusions, I-49 per-request work – not repeated),
[CoreBundle TODO-2](../bundles/CoreBundle/TODO-2-BUGS.md) / [SeoBundle TODO-3](../bundles/SeoBundle/TODO-3-IMPROVEMENTS.md)
(Scope 13: CoreBundle B-30 duplicate areabrick registration, SeoBundle I-16 `isInstalled()` query cost – not repeated),
[models TODO-1](../models/TODO-1-CRITICAL.md) (Scope 6: C-32 unvalidated class / field / select-option identifiers in generated
code, C-44 field-collection property collisions; I-83 code generator – not repeated),
[CoreBundle TODO-1/2/3](../bundles/CoreBundle/TODO-1-CRITICAL.md) (Scope 4: C-03 `pimcore:maintenance-mode --disable`,
C-06 / B-26 `exit` in commands, I-29 `lib/Console` helpers – not repeated),
[models TODO-3](../models/TODO-3-IMPROVEMENTS.md) (I-123 `SettingsStore` JSON type – not repeated),
[CoreBundle TODO-3](../bundles/CoreBundle/TODO-3-IMPROVEMENTS.md) (I-63 one loader for `config_location` imports – not
repeated),
[CoreBundle TODO-3](../bundles/CoreBundle/TODO-3-IMPROVEMENTS.md) (I-43 move `SessionStatus` wiring, I-52 worker
state reset – not repeated)

Code quality, readability, modernization (Symfony 7.4 / PHP 8.4) and logic simplifications. No behaviour change unless
stated. IDs are stable – refer to them as `I-xx` in later sessions. Line numbers are from commit `765372d338` (Scope 2–15: `765372d338`, Scope 16–33: `033aa183ef`).
Grouped by area: **Core cache handler** I-01–I-08, **RuntimeCache** I-09, **Warming** I-10,
**CacheClearer / SessionStatus** I-11, **Tests** I-12,
**Composer** I-13–I-15, **Config** I-16–I-22, **Console** I-23–I-29,
**Controller** I-30–I-37, **DataObject** I-38–I-45,
**Db** I-46–I-48, **DependencyInjection** I-49, **Document** I-50–I-57,
**Element** I-58–I-59, **Event** I-60–I-64,
**Exception** I-65, **Extension** I-66–I-71,
**Helper** I-72–I-77,
**Http** I-78–I-80,
**HttpKernel** I-81–I-87,
**Image** I-88–I-95,
**Loader** I-96–I-99,
**Localization** I-100–I-104,
**Mail** I-105–I-110,
**Maintenance** I-111–I-116,
**Marshaller** I-117–I-118,
**Messenger** I-119–I-122,
**Migrations** I-123–I-124,
**Model** I-125–I-129,
**Navigation** I-130–I-133,
**Normalizer** I-134–I-135,
**Request** I-136,
**Resolver** I-137,
**Routing** I-138–I-139,
**SearchClient** I-140,
**Security** I-141–I-142,
**Serializer** I-143.

---

## Core cache handler

### [ ] I-01 – `CoreCacheHandler`: PHP 8.4 cleanup and dead code

**File:** `lib/Cache/Core/CoreCacheHandler.php`

- Constructor promotion with `private readonly` for `$dispatcher`, `$writeLock` (`:53-57`, `:132-137`); `$pool` stays
  mutable because of `setPool()` (`:143`, used by `Cache::init()` for `pimcore_nocache`).
- `protected Closure $emptyCacheItemClosure` (`:130`) is never assigned or read (uninitialized typed property);
  remove it and the `Closure` import (`:20`).
- Add `@var string[]` / `array<string, true>` to `$clearedTags`, `$tagsIgnoredOnSave`, `$tagsIgnoredOnClear`,
  `$tagsClearedOnShutdown`, `$shutdownTags` (`:82-114`); store the ignore lists as `array<string, true>` sets instead
  of `array_unique()` / `in_array()` / `array_filter()` (`:428`, `:656`, `:666`, `:714-764`).
- `getLogger(): LoggerInterface` (`:157`) returns the nullable trait property – type is a lie if `setLogger()` was
  not called; inject `LoggerInterface` via the constructor (`#[Target('cache')]` / `monolog.logger` channel) and drop
  `LoggerAwareTrait` here and in `WriteLock`.
- `isCli()` (`:879-882`): `PHP_SAPI` instead of `php_sapi_name()`; `phpdbg` is not treated as CLI (see I-05).
- `addClearedTags(array|string)` (`:677-688`): the only caller passes an array → `array` only.
- Empty doc blocks (`/** * * */`) and `@codeCoverageIgnore` on trivial getters can go; mark the class `final` or
  keep `@internal` and stop using `protected` everywhere (only the test mock extends it).
- `storeCacheData()` contains `// TODO return true here …` (`:450`) – decide and remove.

### [ ] I-02 – Split `storeCacheData()` into one element-preparation step and one write step

**File:** `lib/Cache/Core/CoreCacheHandler.php:310-329`, `:336-350`, `:371-387`, `:443-544`, `:814-819`

- In the deferred path the result of `prepareCacheData()` (`:338`) is only used as a filter and the raw data is
  queued; the immediate and the deferred path check different things (B-05), and tags are computed in a different
  place than the stored data (C-02).
- After the re-fetch (`:466`), `__isBasedOnLatestData()` (`:468`) can only fail on a race; the check that was meant to
  detect a modified caller instance no longer does anything.
- Suggested shape: `save()` → validate → `CacheQueueItem`; one private `write(CacheQueueItem)` used by both paths:
  `resolveData()` (hardlink wrapper / no-id check, element re-fetch, deep copy with dump-state / marshaller filters)
  → `resolveTags()` from the resolved data → `cleared/ignored` checks → `pool->save()`. Then C-02, C-03 and B-05 are
  fixed in one place.
- The deep-copy setup (`:490-508`) is rebuilt for every item; build the filters once (they are stateless).

### [ ] I-03 – Simplify the save queue

**File:** `lib/Cache/Core/CoreCacheHandler.php:336-364`, `:786-830`, `:126`

- `$processedKeys` + `in_array()` in `writeSaveQueue()` (`:803-812`, O(n²)) cannot trigger once the queue is keyed by
  string (B-08) – remove.
- `cleanupQueue()` with `uasort` + `array_splice` → `uasort` + `array_slice(…, preserve_keys: true)`; the sort is
  stable since PHP 8.0, document that equal priorities keep insertion order.
- The trigger "cleanup when > 3 × max" (`:342`) and the hard-coded `50` (`:126`) should be bundle configuration
  (`pimcore.cache.max_write_to_cache_items`) instead of `setMaxWriteToCacheItems()` which nothing calls.
- `writeSaveQueue()` keeps the queue when the lock is active (`:790-799`) and returns `false`; document whether
  items should be dropped (they are discarded at process end anyway).

### [ ] I-04 – Replace `WriteLock` with the Symfony Lock component

**File:** `lib/Cache/Core/WriteLock.php`; `bundles/CoreBundle/config/cache.yaml:56-62`

- The class re-implements a distributed lock on top of a cache pool, with the defects of C-01 and B-01. Symfony 7.4
  `LockFactory` (already used in core, e.g. `models/Asset.php`, `lib/Document/StaticPageGenerator.php`) gives token ownership, TTL,
  `refresh()` and `isAcquired()`; a shared lock that *readers* only test (`$lock->isAcquired()` is per-instance, so use
  a `SharedLockInterface` / a dedicated "clearing in progress" key with token) is a cleaner model.
- Keep the lock key out of the data pool: today `pool->clear()` deletes it (`CoreCacheHandler.php:570-573`), and
  `pimcore.cache.pool.app` (`ProxyAdapter` on the same pool, `cache.yaml:31-35`) shares the backend, so
  `Cache::clearAll()` also wipes the application's `app` cache – document this or give the app pool its own backend.
- Constructor promotion, `readonly` pool, `private const string CACHE_KEY`, `int $lifetime` from config.

### [ ] I-05 – Make CLI handling and immediate-write mode explicit configuration

**File:** `lib/Cache/Core/CoreCacheHandler.php:69`, `:120`, `:196-232`, `:299-308`, `:847-862`, `:879-882`

- `setHandleCli()` is never called in the code base; CLI processes (including messenger workers that render
  documents, generate thumbnails, build redirects) never write non-forced items, which is why so many callers use
  `force: true` (B-03). Make it a bundle option (`pimcore.cache.handle_cli`) and inject it, and inject the "is CLI"
  decision (`PHP_SAPI` / Symfony Runtime) so tests do not need a partial mock (B-14).
- Worker runtimes (FrankenPHP worker mode, RoadRunner) are neither "CLI" nor short-lived FPM; with B-02 fixed they
  should be treated as HTTP with a reset per request – document it.

### [ ] I-06 – `load()` returns `false` for "miss", which is also a valid cached value

**File:** `lib/Cache/Core/CoreCacheHandler.php:246-261`; `lib/Cache.php::load()`

- A cached `false` cannot be distinguished from a miss, and every caller writes `if (!$x = Cache::load(…))`, which
  also recomputes on cached `0`, `''`, `[]` (and then re-queues them – where B-05 drops them).
- Add `get(string $key, callable $compute, array $tags = [], …)` in the style of
  `Symfony\Contracts\Cache\CacheInterface::get()` (stampede protection, no ambiguity), or `getItem()`-based
  `has()`/`load(): mixed` returning `null` on miss, and migrate callers gradually.

### [ ] I-07 – Events and the static `Pimcore\Cache` facade

**File:** `lib/Cache/Core/CoreCacheHandler.php:162-190`; `lib/Cache.php`

- `dispatchStatusEvent()` dispatches a bare `Symfony\Contracts\EventDispatcher\Event` under string names and fires
  even when the state did not change (`enable()` on an enabled cache). Dispatch only on change; a small final event
  class (`CoreCacheStatusEvent` with `isEnabled()`) is more useful to listeners.
- `Cache::init()` dispatches a `GenericEvent` for `CoreCacheEvents::INIT` and reads `$_REQUEST['pimcore_nocache']`
  directly – move the nocache switch to a request listener (works in worker mode, testable).
- `Cache::$handler` is a static that survives container rebuilds (tests that boot several kernels, `kernel.reset`);
  resolve from the container each time or reset it in `Kernel::shutdown()`.
- `Cache::save()` has `int $priority`, the handler `?int $priority` (`:285`) → `int` everywhere.

### [ ] I-08 – Small Core classes: `CacheQueueItem`, `CacheMarshallerInterface`, `Core\Exception\InvalidArgumentException`

**File:** `lib/Cache/Core/CacheQueueItem.php`, `lib/Cache/Core/CacheMarshallerInterface.php`,
`lib/Cache/Core/Exception/InvalidArgumentException.php`

- `CacheQueueItem` → `final` with promoted `public readonly` properties (or PHP 8.4 `public private(set)` for
  `$data`, which has a setter that nobody calls – `setData()` can likely go); `?int $priority` + `(int)` cast →
  `int $priority = 0`; the `@param` doc block on a property (`:36-39`) is wrong.
- `Core\Exception\InvalidArgumentException` is not thrown or caught anywhere (keys are validated by
  `CacheItem::validateKey()`, which throws Symfony's exception). Either throw it (wrapping the Symfony exception,
  implementing `Psr\Cache\InvalidArgumentException`) or deprecate it.
- `CacheMarshallerInterface` is `@internal` but implemented by `models/DataObject/Data/BlockElement.php`; add a doc
  block that describes the contract (returned value is stored instead of the object, must be serializable, no
  reverse step).

---

## RuntimeCache

### [ ] I-09 – `RuntimeCache`: typed API, no double lookups, reset support

**File:** `lib/Cache/RuntimeCache.php`

- `private const string SERVICE_ID = __CLASS__` → `self::class`; `__construct($array = [], …)` (`:161`) and
  `offsetSet($index, $value)` (`:166`) untyped → `array|object $array`, `mixed $key, mixed $value` with `#[\Override]`.
- Callers do `isRegistered()` + `get()` (two lookups, `get()` throws a generic `Exception`, `:112-121`) – see
  `models/DataObject/Classificationstore/*Config.php`, `QuantityValue/Service.php`. Add
  `getOrNull(string $key): mixed` / `getOr(string $key, callable $default)` and a typed exception.
- `$disabled` is static while the data is per instance; `isEnabled()` / `disable()` are process-global. Document it, or
  move the flag onto the instance.
- `ARRAY_AS_PROPS` (`:161`) lets `RuntimeCache::getInstance()->foo` access entries – nothing in core uses it; consider
  dropping the `ArrayObject` inheritance in favour of a small final class with an `array` property (faster, no
  magic), keeping `ArrayAccess` for BC.
- Implement `ResetInterface` (clear with protected items) so the messenger / worker reset does not depend on
  `MessengerClearRuntimeCacheListener`, which today calls `clear()` without the protected items of
  `LongRunningHelper` (CoreBundle I-52).
- The temp-instance handover (`:56-63`) and `clear()` (`:195-209`) both replace the synthetic service; extract one
  private `replaceInstance()` (and fix B-10 there).

---

## Warming

### [ ] I-10 – `Warming`: turn into a service, remove double loading and fixed sleeps

**File:** `lib/Cache/Tool/Warming.php`; `bundles/CoreBundle/src/Command/CacheWarmingCommand.php`

- Static mutable configuration (`$perIteration`, `$timoutBetweenIteration`, `:33-35`) → injectable service with
  constructor arguments; fix the typo `timout` → `timeout` (keep deprecated aliases for the public static methods).
- `Cache::save(…, force: true)` → `storeCacheData()` re-loads every element from DB (B-04), so each element is loaded
  twice; with I-02, allow passing an element that is known to be fresh (just loaded by the listing).
- `sleep()` also runs after the last page (`:106`); skip it, and log progress with `$i + 1` / iterations.
- Default type lists are hard-coded (`:40`, `:69`) and duplicated in `CacheWarmingCommand::$valid*Types`; use
  `Document::getTypes()` / `Asset::getTypes()` / `DataObject::$types` minus a documented exclusion list (why `email`
  and `hardlink` documents are not warmed).
- `ceil()` returns `float` (`:90`) → `intdiv` / loop until empty (B-12).

---

## CacheClearer / SessionStatus

### [ ] I-11 – `CacheClearer` and `SessionStatus` modernization

**File:** `lib/Cache/Symfony/CacheClearer.php`; `lib/Cache/FullPage/SessionStatus.php`

- `CacheClearer`:
  - uses the global constant `PIMCORE_PROJECT_ROOT` (`:127`) and the relative `bin/console` (`:121`); inject
    `%kernel.project_dir%` (`#[Autowire('%kernel.project_dir%')]`) and make the console path configurable;
  - `array $options` + `OptionsResolver` in the constructor for a single int → `int $processTimeout = 300`;
  - `ansi` and `no-ansi` can both be `true` (`:62-63`, `:79-80`) – make them mutually exclusive
    (`setNormalizer` or one `?bool $ansi`);
  - `setRunCallback()` stores a callback on a shared public service; pass it as an argument of `clear()` /
    `warmup()` instead;
  - mark `final`, constructor promotion.
- `SessionStatus`:
  - constructor promotion with `private readonly` (`:33-44`), fix the formatting of the parameter list (`:37-41`);
  - the ignored-keys event is dispatched on every call (up to twice per request); compute once per request;
  - strict `in_array` (B-13).

---

## Tests

### [ ] I-12 – Tests

**Files:** `tests/Cache/Core/*`, new tests for `WriteLock`, `RuntimeCache`, `Warming`, `SessionStatus`

- Two handlers with separate `WriteLock` instances on one pool: B locks, A's `writeSaveQueue()` writes nothing (C-01);
  A's `removeLock()` does not delete B's lock; lock expires after the TTL in a long-running process (B-01).
- Element with an in-memory relation change is cached with the tags of the stored (DB) data (C-02).
- An exception in the deep copy / `tag()` does not disable later saves; the lock is removed at shutdown (C-03).
- After `reset()`: `clearedTags`, `cacheCleared`, shutdown tags, queue and lock state are empty (B-02).
- `forceImmediateWrite` respects the write lock; immediate write inside a transaction is deferred (B-03).
- Immediate element write leaves the caller's instance in `RuntimeCache` and does not dispatch `POST_LOAD` (B-04).
- Deferred save of `[]` / `0` is written (B-05); `clearTag('output')` returns `true` without warning (B-06);
  `addTagIgnoredOnClear('output')` prevents the shutdown clear (B-07); numeric keys in the queue (B-08).
- `RuntimeCache::clear(['x'])` keeps `x` while disabled (B-10).
- `Warming` with a quote in a class name does not break the query; all elements are visited exactly once with
  inserts between pages (B-11, B-12).
- `SessionStatus` with `MockArraySessionStorage` and with an unstarted session + session cookie (B-13).
- Replace `setMethods()` / `PHPUnit_Framework_MockObject_MockObject` so the suite runs on PHPUnit 10/11 (B-14).

---

## Composer

### [ ] I-13 – `PackageInfo`: PHP 8.4 cleanup, typed API, injected vendor dir

**File:** `lib/Composer/PackageInfo.php`; `bundles/CoreBundle/config/extensions.yaml:29`,
`bundles/InstallBundle/config/services.yaml:11`

- Mark it `final`. Inject the vendor dir in the constructor (`private readonly string $vendorDir`, with a default
  from `ClassLoader::getRegisteredLoaders()`, see B-15) instead of reading the global `PIMCORE_COMPOSER_PATH`.
  This also makes the class testable with a fixture file.
- `getInstalledPackages(array|string|null $type = null)` (`:35`) → `getInstalledPackages(string ...$types): array`.
  Keep the old signature as a BC wrapper if needed, since it is `@internal` and has only two callers.
- `array_filter()` keeps the original keys (`:44`), so the result is not a list. Wrap it in `array_values()`. Add
  `@return list<array{name: string, type: string, description?: string, extra?: array, install-path?: string}>`
  (and the same `@var` on `$installedPackages`, `:28`).
- Remove the empty doc block (`:30-34`). Replace the `is_file()` + `is_readable()` + `file_get_contents()` race
  (`:66-68`) with a single read and a result check (B-16).
- Put the JSON error in the exception message as well
  (`sprintf('Failed to parse composer file %s: %s', $path, $e->getMessage())`). Today it is only in `previous`.
- The service is defined twice (CoreBundle `extensions.yaml:29` and InstallBundle `services.yaml:11`). Keep one
  definition, or note why the installer kernel needs its own.

### [ ] I-14 – Use the Composer runtime API where possible; stop re-parsing `installed.json` in `PackageVersionTrait`

**File:** `lib/Composer/PackageInfo.php`; `lib/Extension/Bundle/Traits/PackageVersionTrait.php:32-71`;
`lib/Extension/Bundle/PimcoreBundleLocator.php:108-133`

- `composer-runtime-api ^2.0` is already required (`composer.json:46`).
  `Composer\InstalledVersions::getInstalledPackagesByType('pimcore-bundle')` and `::getInstallPath($name)` give the
  package list and real install paths without parsing JSON. Only `extra.pimcore.bundles` and `description` still
  need `installed.json`, so `PackageInfo` can be reduced to a `getExtra()` / `getDescription()` lookup by name.
- `PackageVersionTrait::getDescription()` does `new PackageInfo()` on every call (`:60`). This bypasses the shared
  service and its memoization, so the whole `installed.json` (often several MB) is read and decoded once per bundle
  each time the bundle list is shown. Use a shared static cache, or resolve the service.
- `PackageVersionTrait::getComposerPackageName()` loops over all packages and calls `realpath()` on each
  `install_path` on every call. `realpath()` returns `false` for a missing path, and `str_starts_with(__DIR__, false)`
  then throws a `TypeError` under `strict_types`. Use `InstalledVersions` plus a static cache. A full review belongs
  to the later `lib/Extension` scope.

### [ ] I-15 – Tests

**Files:** new `tests/Unit/Composer/PackageInfoTest.php` with fixture `installed.json` files

- No test covers `PackageInfo` today.
- Composer 2 format (`{"packages": [...]}`) and the legacy flat list. Filter by one type and by several types. The
  result is a list (I-13).
- A missing file logs a warning or throws in debug, and does not silently return `[]` (B-15). A custom vendor dir is
  found.
- Invalid JSON, scalar JSON and a non-array entry all throw the `RuntimeException` with the file name (B-16).
- `PimcoreBundleLocator` finds a bundle installed through `installer-paths` outside `vendor/<name>` (B-15).

---

## Config

### [ ] I-16 – `LocationAwareConfigRepository`: PHP 8.4 / Symfony 7.4 cleanup

**File:** `lib/Config/LocationAwareConfigRepository.php`

- Constructor promotion with `private readonly array $containerConfig`, `?string $settingsStoreScope`,
  `array $storageConfig` (`:53-68`). Today the property is `?array` while the parameter is `array`. Fix the
  parameter-list formatting (`:59-64`).
- Turn the three location constants into a backed enum `ConfigLocation: string { SymfonyConfig, SettingsStore,
  Disabled }`, with `ConfigLocation::from()` replacing the `in_array()` validation (`:151`, `:166`). Keep the
  constants as aliases for BC.
- `getReadTargets(): array` always returns zero or one element. Add `getReadTarget(): ?ConfigLocation` and
  deprecate the plural.
- The by-reference out-parameter `?string &$dataSource` (`:98`, `:107`) and the `[$data, $dataSource]` tuple
  (`:92-95`) → a `final readonly class LoadedConfig { mixed $data; ?ConfigLocation $source; }`.
- `isWriteable()` uses `uniqid('pimcore_random_key_', true)` (`:126`) only to check the write target. Split it into
  `isWriteTargetWriteable(): bool` and `isWriteable(string $key, ?ConfigLocation $source)`.
- Hidden static dependencies: `Pimcore::getKernel()->isDebug()` (`:129`), `Pimcore::getContainer()` (`:209`),
  `PIMCORE_PROJECT_ROOT` (`:283`), `new Filesystem()` (`:201`), the static `SettingsStore`. Inject
  `%kernel.debug%`, a `ParameterBagInterface`, `Filesystem` and a `SettingsStore` service through a factory. Today
  every DAO instantiates the repository with `new` (`PimcoreLocationAwareConfigDao.php:44`).
- Throw specific exceptions (`InvalidConfigurationException`, `ConfigNotWriteableException`) instead of
  `\Exception` (`:152`, `:167`, `:250`). Remove the empty doc blocks (`:119-123`, `:173-176`, `:243-246`).
- `writeYaml(string $key, array $data)` is called with `mixed` (`:186`). Type `saveConfig()`'s `$data` as `array`,
  and the callback as `?Closure(string, array): array`.
- The class is neither `final` nor `@internal`, but is used by bundles (`PimcoreStaticRoutesExtension`,
  `PimcoreCustomReportsExtension`). Decide on the public API and mark the rest `@internal`.

### [ ] I-17 – Replace `searchAndReplaceMissingParameters()` with full escaping

**File:** `lib/Config/LocationAwareConfigRepository.php:207-234`

- Fix for C-05. Use one recursive `escapeForContainer(array $data): array` that escapes keys and string values
  (`str_replace('%', '%%', $v)`) with no parameter lookup, so no container access is needed at write time.
- If parameters in admin-edited configs are a requested feature, make it explicit: a per-config-type allowlist of
  parameter names, resolved with `preg_replace_callback()` per placeholder, never `env()`.
- Return the new array instead of modifying it by reference (`array &$data`, `foreach … as &$value` leaves a dangling
  reference after the loop).

### [ ] I-18 – Strict JSON handling in the settings-store paths

**File:** `lib/Config/LocationAwareConfigRepository.php:112`, `:188`; `lib/Config/ReportConfigWriter.php:60`

- Use `JSON_THROW_ON_ERROR` on decode and encode (B-19, B-20), and `JSON_UNESCAPED_UNICODE` /
  `JSON_PRESERVE_ZERO_FRACTION` so values keep their type on round trip (`1.0` stays a float in thumbnail
  settings).
- Move the encode / decode into one private pair `encodeSettingsStoreData()` / `decodeSettingsStoreData()` that
  both classes use, or a JSON type in `SettingsStore` (models I-123).

### [ ] I-19 – Container resource tracking for `var/config` instead of the `touch()` hack

**File:** `lib/Config/LocationAwareConfigRepository.php:280-321`; `lib/Kernel.php:87-131`

- Register `DirectoryResource` / `GlobResource` for each `config_location` directory (B-21), so create / delete / rename
  of YAML files invalidates the debug container by itself.
- Document that in production (`kernel.debug = false`) YAML-stored configs need a deploy step
  (`cache:clear` / `cache:warmup`). The admin should show this instead of a silent "saved" (with B-18).
- The YAML files are loaded with a full `YamlFileLoader` (`:311-320`), so a file in `var/config` can also contain
  `services:` / `imports:` / `parameters:`. Since `var/` is writable at runtime, restrict the loader to the expected
  extension key (parse with `Yaml::parseFile()` and `prependExtensionConfig()` / validate the top-level key) as
  hardening.

### [ ] I-20 – `BundleConfigLocator` cleanup

**File:** `lib/Config/BundleConfigLocator.php`

- Mark it `final` and use constructor promotion with `private readonly KernelInterface $kernel` (`:46-51`).
- `->files()->depth(0)->sortByName()` (B-24). Build one Finder with all names instead of two Finder runs per bundle
  (`:68-71`): collect the env and generic matches in one pass and choose afterwards.
- Since Symfony 5 the recommended bundle layout is `config/` (`AbstractBundle`), while `Resources/config` is legacy.
  Check `config/pimcore` first and trigger a deprecation for `Resources/config/pimcore`.
- The `.yml` extension (`:90`) is still accepted. Symfony recommends `.yaml`, so deprecate `.yml`. The doc block
  (`:32-42`) does not mention `.yml`.
- Memoize the result per `$name`. It is called for `config` during the container build and for `routing` by
  `BundleRoutingLoader`, each time scanning every bundle directory.

### [ ] I-21 – `ReportConfigWriter` cleanup

**File:** `lib/Config/ReportConfigWriter.php`

- Constructor promotion with `private readonly EventDispatcherInterface $eventDispatcher` (`:38-43`).
- `@throws Exception` (`:45-47`) is not accurate (nothing throws today). After B-19 it becomes `@throws JsonException`.
- `ReportEvents::SAVE_SETTINGS` is dispatched **before** the save, and listeners can change the settings. Document
  that, or add a post-save event so listeners (cache clear, external sync) know the save happened.
- `mergeConfig()` is unused in core (B-25). Deprecate it or make it a recursive merge.

### [ ] I-22 – Tests

**Files:** new `tests/Unit/Config/LocationAwareConfigRepositoryTest.php`, `BundleConfigLocatorTest.php`,
`ReportConfigWriterTest.php`

- No test covers any class in `lib/Config` today.
- `fetchAllKeysByReadTargets()` / `loadConfigByKey()` return the same key set for read target `null`,
  `symfony-config` and `settings-store`, and a node without `read_target` gives no warning (C-04). Select-option sets
  stored as YAML appear in `SelectOptions\Config\Listing` (C-04).
- Values and keys with `%env(APP_SECRET)%`, `%kernel.debug% %foo%`, `%foo% %kernel.debug%` and `100%` round-trip
  unchanged through `writeYaml()` + `YamlFileLoader` (C-05).
- `deleteData()` with every (write target, data source) pair, including a missing file (B-17). `saveConfig()` refuses
  a non-writeable target (B-18).
- Invalid UTF-8 data → exception, the existing entry is unchanged (B-19). Empty container config and corrupt settings
  JSON (B-20).
- Saving a new YAML config invalidates the debug container without `config/services.yaml` (B-21).
- `BundleConfigLocator`: env-specific file wins, subdirectories are ignored, the order is stable, a broken symlink is
  skipped (B-24).
- `ReportConfigWriter::write()` followed by `Config::getReportConfig()` in the same process returns the new values
  (B-25).

---

## Console

### [ ] I-23 – `Application`: move logic out of the constructor into subscribers; Symfony 7.4 command API

**File:** `lib/Console/Application.php`

- Split the two closures (`:69-114`) into `final` event subscriber services (`MaintenanceModeConsoleSubscriber`,
  `DoctrineMigrationsPrefixSubscriber`, `DbWaitTimeoutSubscriber`) with `#[AsEventListener]`. They are then
  registered once, testable, and also active when the console is started through `Symfony\Bundle\FrameworkBundle\
  Console\Application` (`bin/pimcore-install`, `InstallCommand`). Fix for B-27, B-29, B-30.
- `SystemEvents::CONSOLE_INIT` is a pre-autoconfiguration way to register commands. With `#[AsCommand]` and the
  `console.command` tag it is obsolete: deprecate it.
- The `add()` override (`:131-134`) exists only to route into `addCommand()`. In Symfony 7.4 `add()` is deprecated
  and is removed in 8.0. Mark Pimcore's override `@deprecated` too, so callers move to `addCommand()`.
- `getInputOption()` (`:117-124`) can be `$input->hasOption($name) ? $input->getOption($name) : null` inline, or go
  away once the listeners are typed services.
- `@internal param string $name` (`:47-49`) documents a parameter that does not exist.
- `Pimcore::getEventDispatcher()` (`:61`): use `$kernel->getContainer()->get('event_dispatcher')`, which is what the
  parent uses in `doRun()`.

### [ ] I-24 – `AbstractCommand`: typed state, correct `getApplication()` doc, invokable-command path

**File:** `lib/Console/AbstractCommand.php`

- `protected PimcoreStyle $io`, `InputInterface $input` and `OutputInterface $output` (`:35-39`) are uninitialized
  until `initialize()`. A subclass that overrides `initialize()` without `parent::` call, or that uses them in
  `configure()`, gets *"must not be accessed before initialization"*. Document it, or make them private with getters
  that throw a clear `LogicException`.
- `@method Application getApplication()` (`:31`) promises Pimcore's `Application`, but commands also run under
  FrameworkBundle's `Application` (`bin/pimcore-install`). Use `@method \Symfony\Bundle\FrameworkBundle\Console\
  Application getApplication()`.
- The custom `dump()` / `dumpVerbose()` (`:54-80`) re-implement VarDumper wiring. Symfony 7.4 offers the same through
  `$this->io` / `dump()` with the `VarDumper` CLI handler. Keep them as thin wrappers or deprecate them.
- Symfony 7.3+ supports invokable commands (`#[AsCommand]` + `__invoke(#[Argument] …, #[Option] …, SymfonyStyle $io)`)
  without extending `Command`. Document how Pimcore commands should use the Pimcore helpers in that style: a
  `PimcoreStyle` value resolver, and traits that take `InputInterface` as a parameter (I-26).
- The `write*()` helpers: CoreBundle I-29 (not repeated) and B-32.

### [ ] I-25 – `ConsoleOutputDecorator`: PHP 8.4 cleanup and Symfony 8 forward compatibility

**File:** `lib/Console/ConsoleOutputDecorator.php`

- Mark it `final`. Use constructor promotion (`private OutputInterface $output`, `private OutputInterface $errorOutput`;
  `$errorOutput` cannot be `readonly` because of `setErrorOutput()`).
- Symfony 7.2 added `isSilent()` (with `VERBOSITY_SILENT`) as a `@method` annotation on `OutputInterface`. It becomes a
  real interface method in 8.0, and DebugClassLoader reports the missing method as a deprecation in 7.4. Implement
  `isSilent(): bool` and delegate it.
- `implements OutputInterface, ConsoleOutputInterface` (`:31`): `ConsoleOutputInterface` already extends
  `OutputInterface`, so list only `ConsoleOutputInterface`.
- `section()` (B-31): keep the shared `$sections` property and use the inner stream.

### [ ] I-26 – `PimcoreStyle` and `DryRun` cleanup

**File:** `lib/Console/Style/PimcoreStyle.php`; `lib/Console/Traits/DryRun.php`

- `PimcoreStyle`: constructor promotion with `private readonly InputInterface $input`, `OutputInterface $output`
  (`:30-40`). Remove the empty doc block lines (`:52-55`).
- `DryRun::isDryRun()` reads `$this->input` (`:57`), so it only works in subclasses of `AbstractCommand`. Accept
  `?InputInterface $input = null` (falling back to `$this->input`), so the trait also works in invokable commands and
  services. The `@var Input` cast is CoreBundle I-29.
- `configureDryRunOption()`: `$description ??= 'Simulate only (do not change anything)'`. The `@var Command $command =
  $this` dance (`:37-38`) can become an `@phpstan-require-extends Command` annotation on the trait.
- Remove the empty doc block lines (`:29-34`, `:62-66`, `:76-80`).

### [ ] I-27 – Parallelization: one real sequential fallback, injected lock factory

**File:** `lib/Console/Traits/Parallelization.php`; `lib/Console/Traits/ParallelizationBase.php`

- `ParallelizationBase` declares the same trait twice inside an `if (trait_exists(...))` (`:25-70`). This is invisible
  to static analysis (PHPStan / IDE see only one branch), does not work with opcache preloading, and makes the
  behaviour depend on whether a **dev** dependency is installed. Options:
  - move `webmozarts/console-parallelization` to `require` (it is used by core commands); or
  - ship a small `SequentialExecutor` with the same hooks as webmozarts: before-first, per-batch after hook, after-last,
    item errors, exit code, `try/finally`. Then B-33 / B-34 / CoreBundle I-29 are fixed in one place.
- `Pimcore::getContainer()->get(LockFactory::class)` (`:83`): inject `LockFactory` through a setter with `#[Required]`,
  or pass it through `configureCommand()`.
- `getParallelExecutableFactory()` (`:35-48`) forwards `func_get_args()`: spell out the six arguments so renames in
  webmozarts are caught by static analysis.
- `runAfterBatch()` reads `$this->input` (`:60`) although `$input` is passed as a parameter. Use the parameter.
- The trait property `private ?LockInterface $lock` (`:31`) has a generic name that collides with a `$lock` property in
  the using class (fatal "definition differs"). Rename it to `$parallelizationLock`.

### [ ] I-28 – `Timeout` trait: deprecate or modernize

**File:** `lib/Console/Traits/Timeout.php`

- The trait is not used anywhere in core. If it stays:
  - use `hrtime(true)` (B-35);
  - `--timeout` as `VALUE_REQUIRED` with a positive integer validated in `initTimeout()`;
  - keep the result `int` (`floor()` returns `float`, `:86`);
  - a typed `TimeoutReachedException`;
  - fix the typos;
  - the minute-change debug log based on `date('i', …)` (`:96`) can become "log once per elapsed minute" from the
    elapsed seconds.
- `configureTimeout()` is `static` while `configureDryRunOption()` is an instance method. Use one style for all
  option-adding traits.

### [ ] I-29 – Tests

**Files:** new `tests/Unit/Console/*`

- No test covers `lib/Console` today.
- `Application`:
  - two `new Application($kernel)` calls do not add duplicate listeners, and the locale of the process is unchanged
    (B-27);
  - `addCommand(fn () => 0)` works, and a doctrine command with its own `--prefix` does not crash (B-28);
  - `list` / `help` / `_complete` work in maintenance mode (B-29).
- `--maintenance-mode`: maintenance mode set by another session survives the command, and the ID is random (B-26).
- `wait_timeout` is still raised after a reconnect (B-30).
- `ConsoleOutputDecorator`: `section()` output lands in the decorated buffer, two sections overwrite correctly, and
  `setVerbosity()` applies to both outputs (B-31).
- `writeError()` goes to the error output and is visible with `-q` (B-32).
- Parallelization fallback:
  - `collectGarbage()` is called per chunk (B-34);
  - the lock is released after an item throws, and two runs with different arguments can run at the same time (B-33).
- `Timeout` without `initTimeout*()` does not abort immediately (B-35).

---

## Controller

### [ ] I-30 – `Controller` / `FrontendController`: required services, deprecate magic properties and legacy helpers

**File:** `lib/Controller/Controller.php`; `lib/Controller/FrontendController.php`;
`lib/Controller/KernelControllerEventInterface.php`, `KernelResponseEventInterface.php`

- All subscribed services are optional (`'?' . Class`, `Controller.php:80`, `FrontendController.php:42-45`), but the
  code assumes they exist. Make them required (`SubscribedService` / plain class names), so a missing service fails
  at container compile time instead of at runtime.
- Deprecate `__get()` / `__set()` / `@property` (`FrontendController.php:30-80`) in favour of the action arguments
  `Document $document` and `bool $editmode`, which the value resolvers already support (B-37, B-38).
- `renderTemplate()` (`:114-117`) is an alias of `render()`: deprecate it.
- `getDocumentEditable()` (`:102-112`) is `public` on a controller, which makes it look like an action. Make it
  `protected`, and throw a typed exception instead of `\Exception`.
- `KernelControllerEventInterface` / `KernelResponseEventInterface` make every controller a listener through
  `EventedControllerListener`. Document them as the supported way, or point users to
  `#[AsEventListener]` / controller attributes (Symfony 6.2+ `ControllerEvent::getAttributes()`), which
  `ResponseHeaderListener` already uses.

### [ ] I-31 – `UserAwareController` cleanup and Symfony security integration

**File:** `lib/Controller/UserAwareController.php`

- Add `declare(strict_types=1);` (missing, `:1-17`).
- `checkPermission()` calls `getPimcoreUser()` up to four times (`:56-60`), and each call resolves the token again.
  Resolve it once.
- `getPimcoreUser(bool $proxyUser = false)` returns two different types depending on a flag (`:40-47`). Split it into
  `getPimcoreUser(): ?User` and `getPimcoreUserProxy(): ?UserProxy`.
- The comment *"$headers parameter not supported by Symfony 3.4"* (`:76`) is outdated. `createAccessDeniedHttpException()`
  can call `new AccessDeniedHttpException(...)` directly, or use `$this->createAccessDeniedException()`.
- A denied permission is logged as **error** through the static `Logger` (`:57`, `:96`). Use a warning / notice
  through an injected `LoggerInterface` (security channel): a user clicking a disabled feature is not an application
  error.
- `in_array($actionName, $unrestrictedActions)` (`:120`): add `true` for strict mode.
- Long term: a Pimcore permission voter, so controllers can use `#[IsGranted('pimcore.permission.reports')]` /
  `denyAccessUnlessGranted()`. Symfony 7.4 `#[IsGranted]` also works on controller classes.
- `$tokenResolver` is uninitialized when the controller is not autowired (no `#[Required]` call). Constructor
  injection is not possible because of BC, but the getter should throw a clear `LogicException`.

### [ ] I-32 – Argument value resolvers: targeted resolvers, promotion

**File:** `lib/Controller/ArgumentValueResolver/*.php`; `bundles/CoreBundle/config/argument_resolvers.yaml`

- Constructor promotion with `private readonly` (`DocumentValueResolver.php:33-38`: the property is `protected` in a
  `final` class; `EditmodeValueResolver.php:30-35`).
- All three run for every argument of every controller and match on the argument **name** (`document`, `editmode`,
  `websiteConfig`). Symfony 6.3+ supports `#[AsTargetedValueResolver('pimcore_document')]` together with
  `#[ValueResolver('pimcore_document')]` on the argument. Keep the name-based default for BC, and offer the explicit
  attribute for other argument names (`Document\Page $page`).
- `WebsiteConfigValueResolver` calls the static `Config::getWebsiteConfig()`. Inject a website config provider when one
  exists (lib `Config` scope).
- Set an explicit priority on the tag: today all three have the default `0`, and their position relative to Symfony's
  resolvers is implicit.

### [ ] I-33 – `JsonHelperTrait` cleanup

**File:** `lib/Controller/Traits/JsonHelperTrait.php`

- Add `declare(strict_types=1);` (missing, `:1-17`).
- The serializer selection is duplicated (`:61-65`, `:81-85`): use one private `getSerializer(bool $pimcore)`.
- `decodeJson()` calls `decode()` on a `SerializerInterface` and silences PHPStan (`:91-92`). Type the dependency as
  `SerializerInterface&DecoderInterface`, or decode with `json_decode(..., JSON_THROW_ON_ERROR)` directly. Then the
  `@phpstan-ignore` can go.
- `jsonResponse()`, `encodeJson()`, `decodeJson()` are `public` (`:45`, `:59`, `:79`), so every controller exposes them
  as public methods. Make them `protected` (BC: deprecate first).
- `@property ContainerInterface $container` (`:23-25`): use `@phpstan-require-extends AbstractController` to state the
  real requirement.

### [ ] I-34 – `ResponseHeader` attribute: immutable, class target

**File:** `lib/Controller/Attribute/ResponseHeader.php`

- Attributes are metadata. Make the properties `public readonly` (or `private readonly` with getters), and deprecate the
  setters (`:46-69`).
- Add `Attribute::TARGET_CLASS`, so a header can be declared once for all actions of a controller.
  `ResponseHeaderListener` then needs to merge class and method attributes (Symfony's `ControllerEvent::getAttributes()`
  already returns both).
- Validate the header name (non-empty, no `:` / newline) in the constructor. The default `$values = ''` sends an empty
  header.
- Rename `getReplace()` to `isReplace()` (bool getter naming), keeping an alias for BC.

### [ ] I-35 – `ControllerDataProvider` cleanup

**File:** `lib/Controller/Config/ControllerDataProvider.php`

- Mark it `final`. Use `private readonly KernelInterface $kernel` (it is typed `?KernelInterface` at `:35` but is
  never null) and `private readonly array $serviceControllers` with a `@param array<string, class-string>` doc.
- `PIMCORE_PROJECT_ROOT` (`:150`): inject `%kernel.project_dir%` or the Twig loader (B-41).
- `getControllerReferences()` is not memoized (`getTemplates()` is). Both scan the file system on each admin request.
  Cache them in a PSR-6 pool, invalidated on container rebuild (key from `kernel.container_class`), or build the
  controller list in the compiler pass.
- `preg_match('/^(.*)Action$/', …)` → `str_ends_with($name, 'Action')`. Use `$file->getFilenameWithoutExtension()`
  instead of `str_replace('.php', …)`.
- `isValidNamespace()` (`:198-205`): the regex `^(Symfony|Doctrine|Pimcore|Sensio)` also excludes project namespaces
  such as `PimcoreDemo\…` or `SymfonyCasts\…`. Match on `\` boundaries (`^(Symfony|Doctrine|Pimcore|Sensio)\\`), and
  make the list configurable.
- Remove the empty doc blocks (`:37-40`, `:78-81`, `:193-197`).

### [ ] I-36 – `ElementEditLockHelperTrait`: declare the dependency, explicit response shape

**File:** `lib/Controller/Traits/ElementEditLockHelperTrait.php`

- It is not used in core (only by the admin bundle). Either move it there or declare the `adminJson()` dependency (B-42).
- `getObjectVars()` + `unset('sessionId')` (`:34-35`) is a deny-list: a property added to `Editlock` later is exposed
  automatically. Build the response from an explicit allow-list (`id`, `cid`, `ctype`, `userId`, `date`, `cpath`).

### [ ] I-37 – Tests

**Files:** new `tests/Unit/Controller/*`

- No test covers `lib/Controller` today.
- `checkPermissionsHasOneOf()` without a user → 403, and with an empty list → 403 (B-36).
- `DocumentValueResolver` resolves `Document\Page $document`, and returns `[]` for a snippet when `Page` is required
  (B-37).
- `isset($this->document)` / `empty($this->editmode)` in a `FrontendController` subclass (B-38).
- `render()` with an invalid submitted form returns 422 in delegate mode (B-39).
- `ControllerDataProvider`:
  - no static / private `*Action` methods are listed, and invokable / attribute-routed controllers are (B-40);
  - a controller file with a missing dependency does not break the list (B-40);
  - templates from `twig.paths` are listed, in a stable order (B-41).
- `getEditLockResponse()` after the lock was removed (B-42).

---

## DataObject

### [ ] I-38 – Generate PHP with proper literal escaping (or a code generator) instead of string concatenation

**File:** `lib/DataObject/ClassBuilder/*Builder.php`

- Every builder concatenates user-controlled strings into PHP source, each with its own escaping:
  - the class description, with a broken comment filter (C-06);
  - select-option values and labels, with `'` only (C-07);
  - `"$classId"` / `"$className"` / `"$type"` inside **double-quoted** literals, where `$` and `{` interpolate
    (`FieldDefinitionPropertiesBuilder.php:27-28`, `ListingClassBuilder.php:61-62`,
    `FieldCollectionClassBuilder.php:67`, `ObjectBrickClassBuilder.php:71`);
  - field names in `getValueFromParent("…")` (`ObjectBrickContainerClassBuilder.php:57`, `:60`, `:64`);
  - field constants (`ClassDefinition\Service::buildFieldConstantCode()`).
  Models C-32 covers the input validation side.
- Use `var_export($value, true)` for every string literal. Better: a code generator (`nette/php-generator`, as
  proposed in models I-83) that handles literals, doc-comment escaping and identifiers in one place.
- Emit `declare(strict_types=1);` in generated files, and single-quoted literals for constant strings.
- `ClassBuilder::buildClass()` calls `ucfirst($classDefinition->getName())` ten times (`:73-109`). Compute
  `$className` once, and build the `@method static getBy…` lines with one helper instead of three copies (`:77-99`).

### [ ] I-39 – ClassBuilder services: PHP 8.4 cleanup

**File:** `lib/DataObject/ClassBuilder/*.php`

- All builders / dumpers use `protected` promoted constructor properties and are not `final`. Make them
  `final` + `private readonly`; they are replaced through their interfaces (DI aliases), not by inheritance.
- `FieldDefinitionDocBlockBuilder` uses `method_exists($fieldDefinition, 'getFieldDefinitions')` (`:28`). Check
  against the interface that declares it (`Data\Localizedfields`, `Data\Block`, … implement
  `getFieldDefinitions()`; add a `FieldDefinitionsAwareInterface` if none exists). `str_pad('', $level, '-')` →
  `str_repeat('-', $level)`.
- `FieldDefinitionPropertiesBuilder` emits untyped `protected $field;` (`:33`). Typed properties (`protected ?string
  $name = null;`) from the field definition's PHP type would catch the B-49 collisions at build time and give static
  analysis real types. This changes generated code, so it needs a deprecation path.
- The 14 single-method `*Interface` files (25 lines each) carry no doc on what an implementation must guarantee. Add
  one line per interface, or group them in one namespace doc.

### [ ] I-40 – Dumpers: one shared writer; object-brick container dumper cleanup

**File:** `lib/DataObject/ClassBuilder/PHP*Dumper.php`

- Five dumpers repeat `build → dumpFile`. A `GeneratedPhpFileWriter` (dumpFile + `opcache_invalidate()` + optional
  `php -l` in debug) fixes B-47 and removes the dead `file_exists()` checks (`PHPClassDumper.php:40-52`).
- `PHPObjectBrickContainerClassDumper`:
  - `$classId` (`:59`) actually holds the class **name** (the brick definition stores `classname`, and the definition
    file is `definition_<name>.php`). Rename it;
  - `include $file` (`:73`) of the raw definition file bypasses `ClassDefinition::getByName()` and its cache /
    events. Load the class through the model;
  - `mkdir()` before `dumpFile()` (`:83-85`) is redundant, because `dumpFile()` creates directories;
  - `array_filter(array_unique(array_map('realpath', …)))` (`:34-37`) plus the `$includedFiles` realpath map
    (`:66-72`) do the same de-duplication twice;
  - it loads **all** brick definitions (`:44-55`) on every save of one brick. Fine for a handful, slow with many: cache
    the `class → field → bricks` map.

### [ ] I-41 – `SelectOptionsEnumBuilder` cleanup

**File:** `lib/DataObject/ClassBuilder/SelectOptionsEnumBuilder.php`

- Use `var_export()` for literals (C-07), and validate before persisting (B-46).
- `generateCaseName()` runs twice per option (`:115`, `:158`). Compute the names once into a map.
- `parseClasses()` / `generateInterfaces()` / `generateTraits()` duplicate `ClassDefinition\Service::
  buildImplementsInterfacesCode()` / `buildUseTraitsCode()`. Share them.
- `throw new Exception(…, 1676878234790)` with numeric codes (`:56`, `:89`, `:117`, `:193`, `:222`): use a dedicated
  `InvalidSelectOptionsConfigException`, keeping the codes for BC.
- The comment *"Use prefix to assure the case doesn't start with a numeric value"* (`:203`) does not match the code
  (no prefix is added). Fix or remove it.
- `strtr()` templating of a whole PHP file (`:242-260`): with I-38 (code generator) the template goes away.

### [ ] I-42 – Data marshallers: document the direction, drop serialized objects from storage

**File:** `lib/DataObject/BlockDataMarshaller/*.php`, `lib/DataObject/ClassificationstoreDataMarshaller/*.php`

- `marshal()` = normalized value → storage format, `unmarshal()` = storage → normalized (see `Block.php:128-203`).
  Several block marshallers return **objects** from `marshal()` (`Date` → `Carbon`, `Consent`, `ExternalImage`,
  `StructuredTable`). Block storage is `Serialize::serialize()`, so the DB holds serialized `Carbon` / model objects:
  - storage depends on Carbon's internal properties (a Carbon major upgrade can break `unserialize()` of old rows);
  - `Serialize::unserialize()` (`lib/Tool/Serialize.php:33-40`) runs `unserialize()` without `allowed_classes`, so
    anyone who can write the column gets PHP object injection.
  Long term, store scalars / arrays only (timestamp, `['consent' => …]`) with a migration, and unserialize with
  `['allowed_classes' => false]` (or JSON).
- `ClassificationstoreDataMarshaller\Table` / `QuantityValueRange` store `serialize()` output for plain arrays. Use
  JSON.
- Use imports instead of inline FQCNs (`\Pimcore\Model\DataObject\Data\Consent`, …). Use constructor promotion
  (`Localizedfields`, both `EncryptedField`s), and fix copy-paste doc blocks (*"Localizedfields constructor."* in
  `ClassificationstoreDataMarshaller/EncryptedField.php:40`).
- Mark marshallers `final` (they are `@internal` and wired by key in `bundles/CoreBundle/config/marshaller.yaml`).

### [ ] I-43 – Encrypted-field marshallers: one shared encryptor service

**File:** `lib/DataObject/BlockDataMarshaller/EncryptedField.php`, `lib/DataObject/ClassificationstoreDataMarshaller/EncryptedField.php`

- `encrypt()` / `decrypt()` are copied in both classes (~60 lines each).
- They call `Pimcore::getContainer()->getParameter('pimcore.encryption.secret')` and `Key::loadFromAsciiSafeString()`
  on **every** value (`:95-101`, `:130-136`), which is slow for blocks / stores with many encrypted values.
- They swallow the real cause: `throw new Exception('could not load key')` without `previous`, and then
  `throw new Exception('encrypted field … cannot be decoded')`.
- Extract a `FieldEncryptor` service that is constructed with the `Key` (loaded once from the injected parameter).
  Throw a `FieldDecryptionException` with `previous`, and mark the key and plaintext parameters
  `#[\SensitiveParameter]` (PHP 8.2+) so they do not appear in stack traces / logs.
- `Logger::error((string)$e)` (`:148` / `:169`) logs the full exception, including the Defuse message, per value. Log
  once per field and object id instead.
- The `skipDecryption` param (`:138` / `:159`) changes semantics silently. Document it, or split it into a separate
  method.

### [ ] I-44 – `Consent\Service`: injectable, typed, transactional

**File:** `lib/DataObject/Consent/Service.php`

- Accept `Concrete` instead of `AbstractObject` (folders have no fields). Replace the dynamic setter
  `'set' . ucfirst($fieldname)` + `method_exists()` with `$object->set($fieldname, …)` plus a check that the field
  definition is a `Data\Consent`. Today any setter name works, so `giveConsent($o, 'published', …)` calls
  `setPublished(Consent)` → `TypeError`.
- Throw typed exceptions instead of `\Exception` (`:88`, `:111`).
- Mark it `final`, register it as a service with a DB connection for the transaction (B-51), and fix the doc blocks
  (*"based to give object"*).

### [ ] I-45 – Tests

**Files:** new `tests/Unit/DataObject/ClassBuilder/*`, `tests/Unit/DataObject/Marshaller/*`

- No unit test covers `lib/DataObject` today. The class builders are only exercised indirectly through model tests.
- `ClassBuilder`: descriptions `**//`, `*/*/`, `/***/`, multi-line → the generated file passes `php -l` and contains
  no executable statement outside the class (C-06).
- `SelectOptionsEnumBuilder`:
  - values / labels `\`, `\\`, `'`, `\'` round-trip exactly through `from()` / `getLabel()` (C-07);
  - names `-Foo`, `class` and duplicate values are rejected (B-46).
- `PHP*Dumper`s call `opcache_invalidate()` (B-47). A brick container with a duplicate assignment compiles (B-48).
  Field name `allLazyKeysMarkedAsLoaded` is rejected (B-49).
- Encrypted fields, block and classification store: one round-trip test per allowed delegate type, including
  `date`, `booleanSelect` with `false`, `multiselect`, `numeric 0` (B-43, B-44).
- Classification-store multiselect with an option value containing `,` (B-45). Block localized field with a removed
  child key (B-50).
- `Consent\Service::giveConsent()` with a failing object save leaves no note (B-51).
- Marshallers with partial input do not warn (B-52).

---

## Db

### [ ] I-46 – `Db\Helper`: prefer bound parameters, DBAL 4 readiness, cleanup

**File:** `lib/Db/Helper.php`

- `quoteInto()` and `selectAndDeleteWhere(string $where)` build SQL strings with quoted values (used by
  `Dependency\Dao` and others). Deprecate both in favour of `executeStatement($sql, $params, $types)` /
  `QueryBuilder`, which are safe by construction and work with array parameters (`ArrayParameterType::INTEGER`).
- DBAL 4 readiness (Symfony 7.4 / DoctrineBundle work with DBAL 3 **and** 4, and DBAL 3 will reach end of life):
  - `Connection::quote()` in DBAL 4 is `quote(string $value): string`, without a `$type`. `quoteInto(…, $type)` and
    non-string values (`int` ids) then break under `strict_types`;
  - the `Driver\Result` import (B-53) points to an internal API;
  - check the ~117 helper call sites with `rector/rector` + `doctrine/dbal` DBAL 4 sets.
- Make the class `final`, with a private constructor (static utility). Fix the typo `$critera` (`:45`), remove the
  empty doc lines (`:30-31`), and type the arrays (`@param array<string, mixed> $data`, `@param list<string> $keys`).
- `upsert()` with `ON DUPLICATE KEY UPDATE` (B-54). Return the affected row count as `int`. `int|string` comes from
  DBAL 3's `insert()` (string only on 32-bit overflow).

### [ ] I-47 – `Pimcore\Db` static facade

**File:** `lib/Db.php`

- `get()` / `getConnection()` / `reset()` / `close()` go through `Pimcore::getContainer()`. Without a booted kernel
  this is a call on `null` → `Error`. Throw a clear `LogicException`.
- `getConnection()` is a pure alias of `get()`, so deprecate one of them. `reset()` returns the **same** connection
  object after `close()` (DBAL reconnects lazily). The name suggests a new connection. Document this, or rename it to
  `closeAndGet()`.
- Prefer constructor injection of `Doctrine\DBAL\Connection` in new code. Mark the facade `@deprecated` for services
  and keep it for models / static contexts.
- `$db` is fetched from the container twice in `close()` / `get()`, and `/** @var Connection $db */` is unnecessary
  once the return type is enforced. Simplify to `return Pimcore::getContainer()->get('doctrine.dbal.default_connection');`.

### [ ] I-48 – Tests

**Files:** new `tests/Unit/Db/HelperTest.php` (SQLite or the test MySQL connection)

- No test covers `lib/Db` today.
- `fetchPairs()` returns the pairs (B-53).
- `upsert()`:
  - inserts, then updates;
  - a violation of a second unique key is reported, not swallowed;
  - a `null` key value gives a clear error;
  - two concurrent transactions upserting the same row do not deadlock (MySQL only, B-54).
- `escapeLike()` with `\`, `_`, `%`, and combinations, used in a real `LIKE` query (B-55).
- `queryIgnoreError()` logs and rethrows unexpected errors (B-56). `quoteInto()` with `null` / arrays (B-57).

---

## DependencyInjection

### [ ] I-49 – Replace `CollectionServiceLocator` / `ServiceCollection` with Symfony's iterable `ServiceLocator`

**File:** `lib/DependencyInjection/CollectionServiceLocator.php`, `lib/DependencyInjection/ServiceCollection.php`;
`bundles/SeoBundle/src/DependencyInjection/PimcoreSeoExtension.php:77-84`

- Both classes predate Symfony 7.1, where `ServiceLocator` became iterable and countable in factory order
  (`ServiceCollectionInterface`).
  - `CollectionServiceLocator` is unused in core (B-58), so deprecate it.
  - `ServiceCollection` is used once: SeoBundle wraps a `ServiceLocator` in it only to iterate in the configured key
    order. The locator itself now does that, so pass the `container.service_locator` definition directly
    (`$listener->setArgument('$generators', $locator)`). For new code, use `#[AutowireLocator]` /
    `#[AutowireIterator]` / `!tagged_iterator` with priorities.
- If kept for BC until the next major:
  - mark both `final` and `@deprecated`;
  - `ServiceCollection`: constructor promotion `private readonly ContainerInterface $container, private readonly array
    $ids` with `@param list<string> $ids`, implement `Countable`, add `@implements \IteratorAggregate<string, object>`,
    and yield `$id => $service` like Symfony does;
  - `CollectionServiceLocator::__construct($factories)` is untyped (`:33`). Use `array $factories` with
    `@param array<string, \Closure> $factories`. `all()` (`:40-45`) instantiates every service. Document that, or
    return a lazy iterator.
- Tests: none exist. A small unit test for order, keys and laziness would cover both until they are removed.

---

## Document

### [ ] I-50 – Document conversion adapters: PHP 8.4 cleanup and shared base

**File:** `lib/Document/Adapter.php`, `lib/Document/Adapter/Ghostscript.php`, `LibreOffice.php`, `Gotenberg.php`,
`GetTextConversionHelperTrait.php`

- `Adapter` properties and the three subclasses use untyped / loosely typed members. Make the base `abstract`
  methods' return types explicit: `getPdf()` has no return type (`Adapter.php:58`, `Ghostscript.php:103`) and returns
  a resource — type it `/** @return resource */` consistently, or wrap streams in a small value object.
- `Ghostscript::getText()` returns `mixed` but actually returns `string|false` (`:194-216`); `convertPdfToText()`
  returns `string`. Narrow the signatures. `false` on failure (`:215`) is a magic value — prefer throwing.
- `removeTmpFiles()` + `__destruct()` (`Adapter.php:32-47`) is a manual tmp-file registry. None of the subclasses
  actually populate `$this->tmpFiles` anymore (they use `getLocalFileFromStream()` / `File::getLocalTempFilePath()`),
  so the registry is dead. Either use it or remove it.
- `LibreOffice` and `Gotenberg` both `extends Ghostscript` only to reuse `getPageCount()`/`convertPdfToText()` and the
  "already a PDF?" delegation. Their `isAvailable()`/`isFileTypeSupported()`/`load()`/`getPdf()` are near-duplicates
  (compare `LibreOffice.php:36-95` with `Gotenberg.php:36-95`). Extract an `AbstractPdfConversionAdapter` with the
  shared logic and a single `convertToPdf()` abstract step.
- `set_time_limit(250)` in `load()` (`Ghostscript.php:87`, `LibreOffice.php:73`, `Gotenberg.php:73`) is duplicated and
  magic. Centralize and make configurable.
- Constructor-promote and type-declare; mark the adapters `final` where not extended.

### [ ] I-51 – Build external-tool commands as argv arrays, not strings; always `-dSAFER`

**File:** `lib/Document/Adapter/Ghostscript.php:142-157`, `:176-192`, `:222-274`

- `buildPageCountCommand()` is the only command built as a shell string (`Process::fromShellCommandline`,
  `:126`). `saveImage()` and `convertPdfToText()` already use argv arrays (`new Process([...])`), which avoids both
  shell and PostScript-string injection. Convert the page-count path to the same approach (see C-08).
- Add `-dSAFER` explicitly to every `gs` invocation instead of relying on the version default, and drop the
  version-gated `--permit-file-read` special case once the path is no longer interpolated.
- `Console::addLowProcessPriority()` mutates a string or an array depending on the call site — document/normalize it.

### [ ] I-52 – `DocumentRenderer`: constructor promotion, locale safety, request lifecycle

**File:** `lib/Document/Renderer/DocumentRenderer.php`

- Promote the six constructor args to `private readonly` (`:39-66`); drop the separate property declarations.
- The `setContainerService()` `#[Required]` hack (`:68-75`) forces eager init of `ContainerService` so its listener
  is registered. A compiler pass / explicit listener registration would be clearer than an empty required setter.
- Locale restore belongs in `finally` (B-63). The manual CLI request push/pop (`:125-146`) duplicates logic also
  present in `EditableHandler` and `StaticPageGenerator` — a small `RequestContextScope` helper (push on enter, pop in
  `finally`) would remove three copies.

### [ ] I-53 – `StaticPageGenerator`: inject config, extract the domain/path resolution, lock in `finally`

**File:** `lib/Document/StaticPageGenerator.php`

- `Config::getSystemConfiguration('documents')` is read statically inside `getStoragePath()`/`generate()`
  (`:53`, `:112`); inject the relevant config (or `SystemSettingsConfig` is already injected) so the class is
  testable.
- `getStoragePath()` mixes pretty-url handling, `%home` special-casing and the main-domain walk in one method
  (`:43-80`). Split out `resolveMainDomainPath()` and make it null-safe (B-61).
- Release + `services_resetter` reset in `generate()` should move to `finally` (B-62) and use the injected `LockFactory` consistently
  (it already is). Running `services_resetter->reset()` inside a library method (`:116`) is a surprising side effect —
  document why, or move it to the CLI command that drives generation.

### [ ] I-54 – `EditmodeEditableDefinitionCollector`: safe JSON embedding, typed stash

**File:** `lib/Document/Editable/EditmodeEditableDefinitionCollector.php`

- Use the HTML-safe JSON flags (B-65) and consider a `<script type="application/json">` payload read by the editor JS,
  which removes the escaping concern entirely.
- `$stash` (`:33`, `:69-78`) is an untyped array of arrays used as a manual stack; `stashPull()` does
  `array_pop($this->stash)` with no empty-guard → `null` assigned to `editableDefinitions` (typed `array`) is a
  `TypeError` if pulled without a matching push. Add a guard.
- `clearConfig()` (`:80-91`) recursively nulls non-scalars; document that this intentionally drops objects from the
  editmode payload.

### [ ] I-55 – Block state + `EditableHandler` cleanup

**File:** `lib/Document/Editable/Block/*.php`, `lib/Document/Editable/EditableHandler.php`

- `BlockName`/`BlockState`/`BlockStateStack` are already `final` value objects; promote their constructors and add
  generics to the `@var`/`@return` arrays (`BlockName[]`, `int[]`). `BlockStateStack::loadArray()` should validate
  input (B-66) and could become a named constructor `fromArray()` returning a new instance.
- `EditableHandler` has 11 constructor args assigned to properties (`:82-107`) — promote them to `readonly`. The
  class is `@internal`; mark it `final`.
- `renderAction()` request push/pop should be `try/finally` (B-67); share the `RequestContextScope` helper from I-52.
- `getAvailableAreablockAreas()` (`:109-165`) does icon path resolution, translation and option mapping in one long
  loop — extract `resolveBrickIcon()` and build a small `AreablockAreaDefinition` DTO instead of the associative
  array.
- `buildBrickTemplateReference()`: initialize `$templateReference` before the loop (B-68).

### [ ] I-56 – `UsageRecorderSubscriber` / `EditableUsageResolver` ergonomics

**File:** `lib/Document/Editable/UsageRecorderSubscriber.php`, `lib/Document/Editable/EditableUsageResolver.php`

- `UsageRecorderSubscriber` is a mutable collector attached to the global dispatcher. A scoped, per-resolve instance
  with register/unregister in `finally` (B-64) avoids cross-render leakage; `setRecordedEditableNames()` (`:48-51`) is
  only needed for the reset and can be replaced by a `reset()` method.
- Promote constructors and mark both classes `final`.
- `getUsedEditableNames()` renders the document in editmode purely for the side effect of collecting names; document
  that this is a full render (cost) and that it must run with a request context.

### [ ] I-57 – Tests

**Files:** new `tests/Unit/Document/*`, `tests/Unit/Document/Editable/*`

- No unit tests cover `lib/Document` today.
- `Ghostscript`: an asset filename containing `) ( \` and spaces produces a correct, injection-safe command and a
  correct page count (C-08); the text temp file is unique and cleaned up on failure (B-59).
- `LibreOffice`: one log per conversion, kept on failure; lock TTL above the process timeout (B-60).
- `StaticPageGenerator::getStoragePath()` with a pretty URL, a hardlink child and a non-document segment (B-61); lock
  services reset after a render failure (B-62).
- `DocumentRenderer`: locale restored after a render exception (B-63).
- `EditableUsageResolver`: subscriber unregistered after a render exception (B-64).
- `EditmodeEditableDefinitionCollector`: values containing `<!--` / `<script` are emitted HTML-safe (B-65);
  `stashPull()` without a push is guarded.
- `BlockStateStack::loadArray()` with missing keys / wrong types (B-66).
- `EditableHandler::renderAction()` keeps the request stack balanced after a render exception (B-67).

---

## Element

### [ ] I-58 – `MarshallerService`: fix the method-name typo, type the locator, one lookup per call

**File:** `lib/Element/MarshallerService.php`; callers in `models/DataObject/ClassDefinition/Data/Block.php`,
`Classificationstore.php`, `lib/DataObject/BlockDataMarshaller/*`, `lib/DataObject/ClassificationstoreDataMarshaller/*`

- `buildFieldefinitionMarshaller()` (`:32`) is misspelled (missing `d`). It is the public API used by every block /
  classification-store marshaller. Add `buildFieldDefinitionMarshaller()` and deprecate the old name
  (`#[\Deprecated]`, PHP 8.4) so callers can move without a BC break.
- Callers always do `supportsFielddefinition()` + `buildFieldefinitionMarshaller()`, i.e. two locator lookups per field
  and value (`Block.php:137-140`, `:197-200`, `Localizedfields.php:53-54`, `:88-89`). Add
  `findFieldDefinitionMarshaller(string $format, string $name): ?MarshallerInterface` that returns `null` when absent.
- Constructor promotion with `private readonly`. Type the dependency as
  `ServiceProviderInterface<MarshallerInterface>` (or `Psr\Container\ContainerInterface`) instead of the concrete
  `ServiceLocator`, which makes the service unit-testable with a plain array container.
- `get()` returns `mixed`. A tagged service that does not implement `MarshallerInterface` is only detected at runtime
  as a `TypeError` on the return type. Validate the tag in a compiler pass or with `#[AutoconfigureTag]` on
  `MarshallerInterface`.
- Building the key as `$format . '_' . $name` (`:34`, `:39`) is ambiguous if a format ever contains `_`. Document the
  key format next to the constant formats (`block`, `classificationstore`).
- Tests: none exist. A unit test with a small in-memory locator covers supported / unsupported lookups.

### [ ] I-59 – Marshaller wiring (`marshaller.yaml`): dead key, unused tag attribute, public services

**File:** `bundles/CoreBundle/config/marshaller.yaml`

- `classificationstore_inputQuantityValueRange` (`:82`) does not match any data type: there is no
  `InputQuantityValueRange` class, and no `getFieldType()` returns `inputQuantityValueRange`. Remove the tag, or add a
  comment if it is reserved for a planned type.
- Every tag carries an `id: fielddefinition_marshaller_…` attribute that nothing reads. The locator is indexed by
  `key` (`:86`, `!tagged_locator { …, index_by: 'key' }`). Drop the attribute.
- `_defaults: public: true` (`:5`) makes all marshallers public container services. They are only reached through the
  locator, so they can be private.
- Symfony 7.4 alternative: `#[AsTaggedItem(index: 'block_consent')]` on each marshaller class (it accepts one index per
  tag, so the classes serving two keys, `Date` and `QuantityValue*`, keep explicit tags) and
  `#[AutowireLocator('pimcore.dataobject.fielddefinition.marshaller', indexAttribute: 'key')]` on the
  `MarshallerService` constructor.

---

## Event

### [ ] I-60 – Event classes: PHP 8.4 cleanup and copy-paste doc blocks

**File:** `lib/Event/Model/*.php`, `lib/Event/Model/DataObject/**`, `lib/Event/Cache/**`, `lib/Event/Workflow/*`,
`lib/Event/System/*`, `lib/Event/BundleManager/*`, `lib/Event/Report/*`

- Most event classes are plain property + constructor + getter / setter. Use constructor promotion (`PrepareTagsEvent`
  already does) and mark the classes `final` where they are not meant to be extended (only `WebsiteSettingEvent` is
  `final` today).
- Decide which subjects listeners may **replace**. `setAsset()`, `setDocument()`, `setObject()`, `setElement()`,
  `setClassDefinition()`, `setUserRole()`, `setVersion()` … let a listener swap the object being saved, which the
  dispatching code mostly ignores (it keeps using its own `$this`). Where it is not intended, make the property
  `readonly` and deprecate the setter.
- Fix the copy-paste doc blocks: *"DocumentEvent constructor."* in `ClassDefinitionEvent`, `CustomLayoutEvent`,
  `UserRoleEvent`, `VersionEvent`, `CollectionConfigEvent`, `GroupConfigEvent`, `KeyConfigEvent`, `StoreConfigEvent`,
  `GlobalActionEvent`; *"DataObjectEvent constructor."* in `NotificationEvent`; *"AssetEvent constructor … versionNote"*
  in `TranslationEvent`; *"ElementEvent constructor."* in `ResolveElementEvent`; *"Class DataObjectImportEvent @package"*.
- `ResolveElementEvent::$id` is `string`, while element ids are `int` everywhere else. Document it, or accept `int|string`.

### [ ] I-61 – Extract `ArgumentsAwareInterface`; type the traits' requirements

**File:** `lib/Event/Model/ElementEventInterface.php`, `lib/Event/Model/ModelEventInterface.php`, `lib/Event/Traits/*.php`

- `ElementEventInterface` and `ModelEventInterface` each repeat the five `ArgumentsAwareTrait` methods with identical doc
  blocks. Extract `ArgumentsAwareInterface` and let both extend it. Events that use the trait without either interface
  (`MailEvent`, `NotificationEvent`, `SiteEvent`, `TagEvent`, `TranslationEvent`, `ResolveElementEvent`,
  `ResolveUploadTargetEvent`, `GlobalActionEvent`) can then implement it, so listeners can type against it.
- `ResponseAwareTrait::setResponse()` calls `$this->stopPropagation()` behind a `/** @var Event $this */` cast (`:51-52`).
  Use `@phpstan-require-extends \Symfony\Contracts\EventDispatcher\Event` on the trait (PHPStan 1.10+) so misuse is
  caught statically.
- `RecursionBlockingEventDispatchHelperTrait` calls `Pimcore::getEventDispatcher()` statically. Allow an injected
  dispatcher (`?EventDispatcherInterface` property with fallback) for testability. The `finally` fix is B-69.

### [ ] I-62 – Event-name constants: consistency and documentation

**File:** `lib/Event/*Events.php`

- All 183 constants were checked: no two share a value, and names match values. But the value style is mixed:
  camelCase (`pimcore.asset.preUpdate`) next to snake_case (`pimcore.document.renderer.pre_render`,
  `pimcore.cache.full_page.prepare_tags`, `pimcore.system.maintenance_mode.activate`) and one PascalCase segment
  (`pimcore.document.IncludeRenderer.pre_render`). The values cannot change (BC), but new events should follow one
  documented convention.
- Coverage is uneven: `DataObjectCustomLayoutEvents` has only `PRE_ADD` / `PRE_UPDATE` (no `POST_*`, no delete), while
  every other definition type has the full set of six.
- Several events still use `GenericEvent` (`@Event("Symfony\Component\EventDispatcher\GenericEvent")`). Dedicated typed
  event classes give listeners autocompletion and static analysis. Add them for new events, and offer them as
  subclasses of `GenericEvent` for existing ones to stay BC.
- Make the constants classes `final` with a private constructor. They are pure name holders.

### [ ] I-63 – Events dispatched only outside core, and dead events

**File:** `lib/Event/AssetEvents.php`, `DataObjectEvents.php`, `DocumentEvents.php`, `SystemEvents.php`, `TestEvents.php`;
`lib/Event/Cache/Core/ResultEvent.php`, `lib/Event/Model/DataObjectImportEvent.php`, `lib/Event/Model/ResolveElementEvent.php`

- Not referenced anywhere in core: `AssetEvents::DELETE_INFO`, `AssetEvents::RESOLVE_UPLOAD_TARGET`,
  `DataObjectEvents::DELETE_INFO`, `DocumentEvents::DELETE_INFO`, `SystemEvents::CACHE_CLEAR_FULLPAGE_CACHE`,
  `SystemEvents::CACHE_CLEAR_TEMPORARY_FILES`. Their `@Event` annotations point at admin-bundle classes, so they are
  most likely dispatched by `pimcore/admin-ui-classic-bundle` (not checked: not in this repository). Say so in each doc
  block (*"dispatched by the admin UI bundle"*), so readers do not assume core dispatches them.
- Event classes never instantiated in core: `Cache\Core\ResultEvent`, `Model\DataObjectImportEvent` (the CSV importer
  it served is gone), `Model\ResolveElementEvent`. Deprecate them, or document their external dispatcher.
- `TestEvents` (`KERNEL_BOOTED`) is used only by `tests/Support/Helper/Pimcore.php`. Move it to the test namespace /
  autoload-dev instead of shipping it in `lib/`.
- `EDITABLE_RENDERLET_PRE_RENDER` is B-70.

### [ ] I-64 – Tests

**Files:** new `tests/Unit/Event/*`

- No test covers `lib/Event` today.
- `RecursionBlockingEventDispatchHelperTrait`: after a throwing listener, the same event is dispatched again on the next
  call (B-69); nested dispatch of the same event name on the same instance is still blocked (intended behaviour).
- `ResolveUploadTargetEvent`: `getArgument('context')` and `getContext()` agree, whichever API set the value (B-71).
- `PathsEvent::addPaths()` returns a list after duplicates (B-72); `ArgumentsAwareTrait::getArgument()` throws for an
  unknown key.

---

## Exception

### [ ] I-65 – `ImageOptimizationFailedException`: `final`, context, and a common Pimcore exception marker

**File:** `lib/Exception/ImageOptimizationFailedException.php`; thrown in `lib/Image/Optimizer/SpatieImageOptimizer.php:49`,
caught in `lib/Image/Optimizer.php:53`, documented in `lib/Image/Optimizer/OptimizerInterface.php:27`

- The class is an empty `RuntimeException` subclass. Mark it `final`. Optionally carry context (optimizer class, input
  path) through a named constructor such as `forOptimizer(string $optimizer, string $path, ?\Throwable $previous = null)`,
  so the log line the catcher should write (see the `lib/Image` scope) can say **which** optimizer failed on **which**
  file.
- The thrower discards the cause: `SpatieImageOptimizer` throws when the output file is missing or empty, but the
  underlying tool failure is not passed as `previous`.
- `lib/Exception` holds only this one class, while Pimcore exceptions live in about 15 other `Exception` folders
  (`models/Exception`, `models/DataObject/Exception`, `lib/Cache/Core/Exception`, `lib/Document/Editable/Exception`,
  `lib/Http/Exception`, `lib/Security/Exception`, …), with no common type. Add a marker interface
  `Pimcore\Exception\ExceptionInterface` (the pattern Symfony components use) and let Pimcore's own exceptions
  implement it, so applications can `catch (Pimcore\Exception\ExceptionInterface)` without listing every class. This is
  additive, so there is no BC break.
- Tests: none needed for the empty class itself; covered by the optimizer tests in the `lib/Image` scope.

---

## Extension

### [ ] I-66 – `PimcoreBundleManager`: memoize installed state, remove dead branches, PHP 8.4 cleanup

**File:** `lib/Extension/Bundle/PimcoreBundleManager.php`

- `getActiveBundles(true)` calls `isInstalled()` for every bundle on every call (`:78-92`). That means a settings-store
  query per `SettingsStoreAwareInstaller` bundle. Each admin page load calls it four times (`getJsPaths()`,
  `getCssPaths()`, `getEditmodeJsPaths()`, `getEditmodeCssPaths()` → `resolvePaths()`, `:409-491`), and
  `getActiveBundle()` / `isValidBundleIdentifier()` call it again. Memoize the installed state per process (reset
  after `install()` / `uninstall()` and via `ResetInterface`). Related: SeoBundle I-16.
- `if ($item->getSource() === ItemInterface::SOURCE_EXTENSION_MANAGER_CONFIG)` (`:180-182`): nothing in core creates
  items with that source any more (the extension-manager config was removed), so the branch is dead. Deprecate the
  constant in the `lib/HttpKernel` scope.
- `getInstaller()` is a one-line wrapper of `loadBundleInstaller()` (`:289-310`). Keep one of them.
- Exception message with an embedded line break, indentation and an unbalanced quote (`:133-134`:
  `… or "Kernel::registerBundles()?`). Use a single line.
- Constructor promotion with `private readonly`. `in_array(…, true)` (`:116`, `:185`, `:261`, `:286`). Remove the
  empty doc blocks (`:232-236`, `:276-279`, `:302-306`, `:346-350`, …).
- The static `OptionsResolver` (`:197-230`) validates data that comes from Pimcore's own `BundleCollection` items.
  A typed value object (`final readonly class BundleState { bool $enabled; int $priority; list<string> $environments; }`)
  is simpler and gives the documented array shape a real type.
- Depends on the concrete `Pimcore\Kernel` (`:42`, `:60`) only for `getBundleCollection()`. Inject a small
  `BundleCollectionProviderInterface` instead, which makes the manager unit-testable.

### [ ] I-67 – Installers: naming, return types, explicit dependencies

**File:** `lib/Extension/Bundle/Installer/AbstractInstaller.php`, `InstallerInterface.php`, `SettingsStoreAwareInstaller.php`

- `AbstractInstaller` is not `abstract`, and its defaults (`isInstalled() === true`, `canBeInstalled() === false`)
  mean "nothing to install". Document that in the class doc block, or make it `abstract`.
- `getOutput(): BufferedOutput|NullOutput` (`InstallerInterface.php:64`, `AbstractInstaller.php:61`) always returns a
  `BufferedOutput`. Narrow it to `OutputInterface` (more useful for implementers) or `BufferedOutput`.
- `SettingsStoreAwareInstaller` gets three collaborators through `#[Required]` setters (`:45-61`), so an installer
  created with `new` (tests, custom wiring) fails late with uninitialized properties. Use constructor injection for
  new code and keep the setters for BC.
- Type `$bundle` as `PimcoreBundleInterface` instead of `BundleInterface` (`:31`, `:39`).
- `$metadataStorage->ensureInitialized()` runs inside the loop for every migration (`:88`, `:113`). Call it once.
- `'BUNDLE_INSTALLED__' . $namespace . '\\' . $name` (`:65`): make the prefix a class constant and document it as a
  stable key (it is read by other code, e.g. migrations of `pimcore/pimcore`).

### [ ] I-68 – `PimcoreBundleLocator`: cache, inject paths, use Composer's runtime API

**File:** `lib/Extension/Bundle/PimcoreBundleLocator.php`

- `findBundles()` scans the file system and parses every `*Bundle.php` (`ClassUtils::findClassName()`) on each call.
  Cache the result (PSR-6 pool keyed by the container build, or compute it in a compiler pass).
- Uses the global constants `PIMCORE_PROJECT_ROOT` (`:52`) and `PIMCORE_COMPOSER_PATH` (`:125`). Inject
  `%kernel.project_dir%` and the vendor dir.
- Composer packages: use `InstalledVersions::getInstalledPackagesByType('pimcore-bundle')` and
  `getInstallPath()` (Composer I-14, not repeated).
- Mark the class `final`; use constructor promotion; type the arrays (`list<string>`, `array<class-string,
  class-string>`).

### [ ] I-69 – Areabrick API cleanup

**File:** `lib/Extension/Document/Areabrick/*.php`

- `AreabrickManager`:
  - constructor promotion with `private readonly ContainerInterface $container` (the brick service locator);
  - document that `registerService($id, $serviceId)` only works for ids that exist in the locator built by
    `AreabrickPass`, because `loadServiceBrick()` looks up `$id` in the locator, not `$serviceId` (`:131-139`), so a
    runtime call with any other id fails with *"Definition … does not exist"*. The duplicate registration case is
    CoreBundle B-30 (not repeated).
- `AbstractAreabrick`: the property `$id` is declared between methods (`:40`). Move it to the top, and add
  `@phpstan-require` / a clear exception for the renderer (B-77).
- `EditableDialogBoxConfiguration::jsonSerialize()` returns `get_object_vars($this)` (`:119-122`), so every property a
  subclass adds is sent to the editor JS. Return an explicit shape. `$width` / `$height` are nullable with non-null
  defaults: document what `null` means.
- The doc blocks of `TemplateAreabrickInterface` (`areas` only) and `AbstractTemplateAreabrick` (`[A|a]reas`) describe
  the lookup differently. `EditableHandler::buildBrickTemplateReference()` tries both (Document scope I-55).
- Exceptions: `BrickNotFoundException` extends `Bundle\Exception\RuntimeException`, `ConfigurationException` extends
  `\RuntimeException` directly. Align them and add the common Pimcore marker interface (I-65).
- `#[AsAreabrick]` is already a `final readonly` attribute. Keep it as the documented way, and mention it in
  `AreabrickInterface`'s doc block.

### [ ] I-70 – Bundle base classes and interfaces

**File:** `lib/Extension/Bundle/AbstractPimcoreBundle.php`, `PimcoreBundleInterface.php`, `PimcoreBundleAdminClassicInterface.php`,
`Traits/BundleAdminClassicTrait.php`, `Traits/PackageVersionTrait.php`

- `AbstractPimcoreBundle` extends `Bundle`. Symfony recommends `AbstractBundle` (6.1+) for new bundles (`configure()`,
  `loadExtension()`, `prependExtension()` in the bundle class). Offer an `AbstractPimcoreBundle` variant on top of
  `AbstractBundle` (or a trait implementing `PimcoreBundleInterface`), so bundles do not have to choose between
  Pimcore's interface and Symfony's modern base. ApplicationLoggerBundle / SeoBundle TODOs already ask for this.
- `protected static ?PimcoreBundleManager $bundleManager` is `@deprecated` and only written, never read
  (`@TODO: compatibility layer to be removed in Pimcore 12`). Remove it in 12.0, and use `#[\Deprecated]` meanwhile.
- The comments *"as shown in extension manager"* (`PimcoreBundleInterface`, `AreabrickInterface`) refer to a UI that
  no longer exists. Update the wording.
- `PackageVersionTrait` issues (a `realpath()` `false` `TypeError`, re-parsing `installed.json` per call) are Composer
  I-14 (not repeated).

### [ ] I-71 – Tests

**Files:** new `tests/Unit/Extension/*`

- No unit test covers `lib/Extension` today.
- `SettingsStoreAwareInstaller` with two bundles `App\Bundle\Shop` and `App\Bundle\ShopExtras`:
  - uninstalling `Shop` keeps `ShopExtras`' executed-migration records;
  - prefixes are reset afterwards;
  - an unknown last-migration class is reported (B-73).
- Install / uninstall where the migration bookkeeping fails leaves a consistent state (B-74).
- `PimcoreBundleLocator` with a broken `*Bundle.php` in a search path, and with no existing search path (B-75).
- `AbstractPimcoreBundle::isInstalled()` for a bundle that is not active returns `false` (B-76).
- `AbstractAreabrick` built by hand gives a clear exception for a missing id / renderer (B-77).
- `PimcoreBundleManager::getJsPaths()` runs a bounded number of settings-store queries (I-66).

---

## Helper

### [ ] I-72 – `LongRunningHelper`: tighten visibility, explicit options, align with Symfony's resetter

**File:** `lib/Helper/LongRunningHelper.php`

- The class is `final` but all members are `protected`. Make them `private`, and use constructor promotion
  (`private readonly ConnectionRegistry $connectionRegistry`).
- `resolveOptions()` derives the option key from `__METHOD__` with a regex (`:139-148`). Use explicit option keys
  (`$options['pimcoreRuntimeCache']['keepItems']`) and a typed value object.
- `LoggerAwareTrait` leaves `$logger` nullable (B-87). Inject `LoggerInterface` (with `NullLogger` default).
- The default protected items are a property literal (`:37-44`). Make them a class constant, so projects can see
  and extend the list.
- `cleanUp()` partly duplicates Symfony's `services_resetter` (`kernel.reset`), which is what Symfony 7.4 workers and
  the Runtime component use. Let `cleanUp()` call the resetter, and move Pimcore's own state into `ResetInterface`
  services (see B-02, I-09), so there is one reset mechanism.
- Monolog handlers are registered by a compiler pass via `addMonologHandler()`. Use a tagged iterator instead of
  the `@internal` method.

### [ ] I-73 – `Helper\Mail`: split into services, parse HTML instead of regex

**File:** `lib/Helper/Mail.php`

- One static class mixes debug formatting, logging, URL absolutizing, CSS inlining and address parsing. Split it into
  injectable services (`MailDebugInformationFormatter`, `MailUrlAbsolutizer`, `MailCssInliner`, `MailAddressParser`),
  keeping the static methods as deprecated wrappers.
- Use an HTML parser for links, `srcset` and `<link>` (B-81, B-83), Symfony Mime for addresses (B-82), and a URI
  library (`league/uri` or Symfony's own resolution) instead of the PEAR `Net_URL2` dependency.
- `logEmail()` stores `htmlspecialchars($_SERVER['REQUEST_URI'])` (`:154-156`), i.e. it escapes at storage time
  (double escaping in any UI that escapes on output) and reads the superglobal (wrong or stale in worker runtimes).
  Store the raw URI from the `RequestStack`, and escape on output.
- `logEmail()` temporarily changes `mb_internal_encoding()` (`:162-167`). `mb_decode_mimeheader()` does not need
  that, or use `iconv_mime_decode($subject, 0, 'UTF-8')`.
- Remove the empty doc blocks (`:199-203`), and fix the typo `$formatedReceiversArray`.

### [ ] I-74 – Small static helpers: make them services or `final`, fix the color math

**File:** `lib/Helper/GotenbergHelper.php`, `EncoreHelper.php`, `ContrastColor.php`, `ReservedWordsHelper.php`, `SystemConfig.php`

- `GotenbergHelper`: a static class with static state. Make it a service with an injected HTTP client and a cache
  (B-85), and inject the base URL instead of `Config::getSystemConfiguration()`.
- `ContrastColor`: the luminance uses `pow(x, 2.2)` (an approximation), compares only against black, and truncates
  the ratio with `(int)`. Use the WCAG 2.x formula (sRGB linearization, contrast against both black and white, pick
  the higher one). Remove the `//////////` banner comments and the constant black-luminance calculation (it is
  always 0).
- `ReservedWordsHelper`: the doc block points at `bundles/AdminBundle/public/js/…/reservedWords.js`, which is no longer
  in this repository (the admin UI is a separate package). It uses `static::` constants in a non-final class with
  instance methods. Make it `final`, with static methods or as a service. `yield_from` is not a PHP keyword (`yield
  from` is two tokens). Harmless, but misleading.
- `SystemConfig`: `loadConfigByKey(($key))` has redundant parentheses. Use list destructuring (B-80), and mark it
  `final`.
- `EncoreHelper`: read the file with `JSON_THROW_ON_ERROR` (already done) and validate the shape (B-86).

### [ ] I-75 – `SymfonyExpression\ExpressionService`: reuse the language instance, restrict functions explicitly

**File:** `lib/Helper/SymfonyExpression/ExpressionService.php`; user `bundles/GenericExecutionEngineBundle/src/Extractor/JobRunExtractor.php:96`

- A new `ExpressionLanguage` is created per `evaluate()` call (`:33`), so its parsed-expression cache is lost every
  time. Keep one instance (or inject a `CacheItemPoolInterface` for the parser cache).
- Only `constant()` is overridden. Symfony 7.x also ships built-in `enum()` (and `min()` / `max()` since 7.1). Decide
  on an explicit allowlist with an `ExpressionFunctionProviderInterface`, and document that expressions can call
  methods and read properties of the variables passed in. Today the variables are arrays (`context`,
  `environmentData`), but nothing enforces that.
- `(bool)` cast of the result (`:41`): a typo in a condition that evaluates to a non-empty string is silently `true`.
  Consider requiring a real boolean.

### [ ] I-76 – `TemporaryFileHelperTrait`: rewind seekable streams, safe names

**File:** `lib/Helper/TemporaryFileHelperTrait.php`

- `stream_copy_to_stream()` copies from the **current position** (`:75`). A caller that passes a stream it already
  read from (or a `php://temp` stream it just wrote) gets a truncated or empty temp file. No such caller was found in
  core, but the helper should `rewind()` seekable streams to be safe.
- Generate the temp name from a sanitized extension only (B-87). For local streams, `getLocalFileFromStream()`
  returns the original asset path (C-08). Offer a `forceCopy` option for consumers that pass the path to external
  tools.
- Type the parameter as `string|resource` in the doc block and document that `$keep = true` files are never cleaned
  up.

### [ ] I-77 – Tests

**Files:** new `tests/Unit/Helper/*`

- No unit test covers `lib/Helper` today.
- `LongRunningHelper`:
  - `removePimcoreRuntimeCacheProtectedItems(['x'])` keeps the other items (B-78);
  - `cleanUp()` deletes registered temp files (B-79).
- `SystemConfig::getConfigDataByKey()` with a YAML-only config reports `writeable: false` (B-80).
- `Mail`:
  - `setAbsolutePaths()` with `images/a.png`, `css/x.css`, `mailto:`, `data:`, `//cdn`, and a `srcset` (B-81);
  - `parseEmailAddressField('"Doe, John" <j@x.org>; a@b.c')` (B-82);
  - `embedAndModifyCss()` with a foreign-host `<link>` and a `data:` `url()` (B-83);
  - debug information with `<`/`&` in names (B-84).
- `GotenbergHelper` with an unreachable base URL makes one short request per TTL (B-85). `EncoreHelper` de-duplicates
  shared chunks (B-86).
- `ContrastColor` with `#fff`, `#ffffff`, `#000` (B-87).

---

## Http

### [ ] I-78 – `RequestHelper` / `CodeInjector` / `EditmodeResolver` cleanup

**File:** `lib/Http/RequestHelper.php`, `lib/Http/Response/CodeInjector.php`, `lib/Http/Request/Resolver/EditmodeResolver.php`

- Constructor promotion with `private readonly` in all three; `CodeInjector` static arrays → constants; `in_array(…, true)`.
- `RequestHelper::detectFrontendRequest()` has `TODO use pimcore context here?` – use `PimcoreContextResolver`.
  `Pimcore::inAdmin()` static call → injected context. The hard-coded `/admin/document_tag/renderlet` path belongs to the
  admin bundle's route config.
- `CodeInjector::injectIntoPresetSelector()` uses regex on HTML; `<body` inside a script or comment can match. With the
  C-09 fix, use one DOM-based path for all selectors.


### [ ] I-79 – Remaining `lib/Http` classes: modern HTTP client, Symfony 7.4 request matchers, resettable state

**File:** `lib/Http/ClientFactory.php`, `RequestMatcherFactory.php`, `ResponseHelper.php`, `ResponseStack.php`,
`Context/PimcoreContextGuesser.php`, `Exception/ResponseException.php`, `Request/Resolver/*.php`

- `ClientFactory` builds Guzzle clients. Symfony 7.4 ships `symfony/http-client` (scoped clients, retry, timeouts,
  proxy and CA config in `framework.http_client`) and PSR-18 adapters. Offer a scoped HttpClient (`pimcore.http_client`)
  configured from the same settings, and keep the Guzzle factory for BC.
- `RequestMatcherFactory`: the `TODO add support for IPs, attributes and schemes` (`:51`) is easy with Symfony 7.x:
  `IpsRequestMatcher`, `SchemeRequestMatcher`, `PortRequestMatcher`, `ExpressionRequestMatcher`,
  `AttributesRequestMatcher`. Return a ready `ChainRequestMatcher` per entry instead of an array.
- `PimcoreContextGuesser::guess()` builds a new `ChainRequestMatcher` for every entry on every request (`:58`). Build
  them once in `getMatchers()`.
- `ResponseHelper::disableCache()` adds `no-transform` twice (`:38`, `:47`) and the IE-era `post-check` / `pre-check`
  directives. `no-store` alone is enough for modern clients. `Pragma` / `Expires` can stay for HTTP/1.0 proxies.
- `ResponseStack` and `OutputTimestampResolver::$timestampWasQueried` keep request state on shared services. Implement
  `ResetInterface` (CoreBundle C-08, not repeated), and let `ResponseStackListener` `pop()` the response it uses.
- `ResponseException`: empty message, and `?Exception $previous` instead of `?\Throwable` (`:27`). Pass a message
  (`'Response returned via exception'`) and widen the type.
- Resolvers: constructor promotion; mark them `final` where not extended; `SiteResolver::isSiteRequest()` checks
  `null !== $site && $site instanceof Site` (the second test suffices); the attribute getters return untyped request
  attributes. Cast or validate (`ResponseHeaderResolver::getResponseHeaders()` assumes an array).
- `AbstractRequestResolver::getCurrentRequest()` / `getMainRequest()` call the stack twice. Use a local variable.

### [ ] I-80 – Tests

**Files:** new `tests/Unit/Http/*`

- Only `CodeInjector` has a unit test, and it sits in `tests/Unit/HttpKernel/Response/` (move it, see I-87).
- `CodeInjector` with a custom selector keeps `&lt;script&gt;` escaped in the rest of the page (C-09).
- `RequestHelper`:
  - `createRequestWithContext()` does not change the shared context (B-88);
  - IPv6 anonymization (B-89);
  - `/administration` is a frontend request (B-90).
- `EditmodeResolver` force flag is reset (B-91).
- `ClientFactory` with a proxy password containing `@:` (B-92).
- `PimcoreContextGuesser` with an empty route entry does not match everything (B-93).
- `ResponseHelper::isHtmlResponse()` for JSON containing `<html`, and for `<HTML>` with `text/html` (B-94).

---

## HttpKernel

### [ ] I-81 – `BundleCollection` items: PHP 8.4 cleanup, fix the constant typo, drop the interface cache

**File:** `lib/HttpKernel/BundleCollection/AbstractItem.php`, `Item.php`, `LazyLoadedItem.php`, `ItemInterface.php`,
`BundleCollection.php`

- Constructor promotion with `private readonly` in `AbstractItem` (`:22-39`), `Item` (`:26-38`), `LazyLoadedItem`
  (`:27-51`; `$bundle` stays mutable for the lazy instance). Remove the empty doc block `LazyLoadedItem constructor.`
  (`:33-36`) and the odd column alignment in the constructor parameters.
- `SOURCE_PROGRAMATICALLY` is misspelled (`ItemInterface.php:24`). Add `SOURCE_PROGRAMMATICALLY` with the same value and
  deprecate the old name. Deprecate `SOURCE_EXTENSION_MANAGER_CONFIG` (`:26`, no producer left; see I-66).
- `LazyLoadedItem::implementsInterface()` with its static `class_implements()` cache and non-strict `in_array()`
  (`:88-95`) is `is_a($this->className, $interface, true)` / `is_subclass_of()`. No cache needed.
- `get_class($this->bundle)` → `$this->bundle::class` (`Item.php:42`).
- Type the environments as `list<string>` in PHPDoc on the constructors and `ItemInterface::getEnvironments()`.
- `BundleCollection::getItems()` filters and sorts on every call (`:83-94`). It is cheap, but `PimcoreBundleManager`
  calls it per request. Sort once when the collection is frozen (after `registerBundles()`), or memoize until `add()`.
- The `@return $this` + `: static` doc blocks and `@throws` blocks with blank lines (`:122-128`) can go.
- `BundleCollection` is a mutable object that is kept after boot (`Kernel::getBundleCollection()`). Consider exposing a
  read-only view (only `getItems()` / `hasItem()`) to consumers like `PimcoreBundleManager`.

### [ ] I-82 – Dependent bundles: let dependencies inherit the parent's environments

**File:** `lib/HttpKernel/Bundle/DependentBundleInterface.php:28-36`; `BundleCollection.php:50-53`;
`bundles/SeoBundle/src/PimcoreSeoBundle.php:61-64`; bundle collection docs

- `registerDependentBundles()` is static and gets only the collection. It cannot know the priority or environments the
  parent was registered with, so a parent restricted to `['dev']` still loads its dependencies everywhere. The interface
  doc and the docs warn about it, but every bundle author has to work around it.
- Option: a second interface (`DependentBundleWithContextInterface::registerDependentBundles(BundleCollection, ItemInterface $parent)`),
  or have `BundleCollection::add()` apply the parent's environments to items added during `registerDependencies()` when
  they were added without environments. Keep the current interface for BC.
- Combine with B-97 (track items that came from a dependency).

### [ ] I-83 – `BundleLocator`: precomputed namespace map, cleanup

**File:** `lib/HttpKernel/BundleLocator/BundleLocator.php`, `BundleLocatorInterface.php`, `InvalidArgumentException.php`

- Constructor promotion (`private readonly KernelInterface $kernel`), `$class::class` instead of `get_class()` (`:51-53`).
- Build a namespace → bundle map once, sorted by namespace length (longest first), instead of looping over all bundles per
  class (`:69-81`). Fixes the order part of B-100.
- `BundleLocator\InvalidArgumentException` is not used anywhere (grep over the repo). Deprecate it.
- The interface docs still use the Symfony 3 example `AppBundle\Controller\FooController` → `src/AppBundle` and have empty
  lines (`:24-41`). In Symfony 7 the `App\` code is not a bundle at all, so `getBundle()` throws for it. Say so in the docs.
- The comment `see TemplateGuesser from SensioFrameworkExtraBundle` (`:67`) refers to an abandoned bundle. Remove it.

### [ ] I-84 – `WebPathResolver`: use the Asset component, share the public-dir logic

**File:** `lib/HttpKernel/WebPathResolver.php`; `lib/Document/Editable/EditableHandler.php:131-136`

- `getPrefix()` returns a root-relative `/bundles/<name>` (`:50-53`). It ignores the request base path, the
  `framework.assets` base URLs (CDN) and the version strategy. Build the URL with
  `Symfony\Component\Asset\Packages::getUrl('bundles/…')`.
- `EditableHandler` repeats the "`Resources/public` or `public`" check (`:132`) to test that the icon exists. Add
  `WebPathResolver::getPublicDir(BundleInterface): ?string` and use it in both places.
- `$type` and `$resource` are put into the URL without encoding (`:69`). Use `rawurlencode()` per path segment.
- The exception message mentions only `Resources/public` (`:45`), while `public` is accepted too.
- The Stack Overflow `@see` link (`:26`) and the doc blocks that only repeat the signature (`:32-40`, `:56-64`) can go.

### [ ] I-85 – `PimcoreCoreCacheWarmer`: build the preload list from Composer's class map

**File:** `lib/HttpKernel/CacheWarmer/PimcoreCoreCacheWarmer.php`

- The exclude patterns contain 10 paths that no longer exist: `lib/Sitemap`, `lib/Translation/Import`,
  `lib/Translation/Export`, `lib/DataObject/GridColumnConfig`, `lib/DataObject/Import`, `lib/Tool/Transliteration`,
  `models/GridConfig`, `models/ImportConfig`, `models/Tool/CustomReport`, `models/Workflow` (checked with `ls`;
  `lib/Tool/Transliteration.php` still matches the prefix by accident). Clean them up.
- Guessing class names from file paths (`:67-85`, with the `/[_.-]/` filter and `rscandir()` over non-PHP files such as the
  TODO markdowns) is fragile (B-96). Use `Composer\ClassMapGenerator\ClassMapGenerator::createMap()` (or Composer's
  generated class map) for `lib/` and `models/`, and filter by namespace.
- Consider whether a hand-made list is still needed at all: Symfony already preloads container services and classes tagged
  `container.preload`. A smaller, explicit list (hot-path classes: `Model\Element`, `Document`, `Asset`, `DataObject\Concrete`,
  cache, routing) is easier to reason about than "everything except some folders".
- Generated DataObject classes: see B-95. The brick / collection names come from `new …\Listing()->loadNames()`; inject the
  listings or a definition provider instead of `new` to make the warmer testable.
- Add `@return list<class-string>` and return `array_values(array_unique(...))`.

### [ ] I-86 – `MkdirCacheWarmer`: inject the directories

**File:** `lib/HttpKernel/CacheWarmer/MkdirCacheWarmer.php`; `bundles/CoreBundle/config/services.yaml:28-30`

- Constructor promotion (`private readonly int $mode = 0775`).
- Inject the directory list (and the writable flag) as container parameters instead of reading constants and `$_SERVER`
  inside `warmUp()` (`:44-55`). That makes the B-101 decision visible in config and the class testable.
- `Filesystem::mkdir()` already skips existing directories; the `exists()` check (`:59`) is redundant.
- Directories that must exist at runtime (logs, tmp) are better created where they are used (or once in the install /
  deploy step), because warmers only run on a container rebuild.

### [ ] I-87 – Tests

**Files:** `tests/Unit/HttpKernel/*`

- Existing: `BundleCollectionTest`, `ItemTest`, `LazyLoadedItemTest` (30 tests). Missing:
  - override of a dependency with a lower priority / narrower environments, and the `config/bundles.php` → dependency
    order from `Kernel::registerBundles()` (B-97);
  - `addBundle('\Foo\BarBundle')` next to `new BarBundle()`, and a non-bundle class (B-98);
  - `['all']` environment (B-99).
- New: `BundleLocatorTest` (unknown class, nested namespaces, root namespace, parent fallback – B-100),
  `WebPathResolverTest` (`Resources/public` vs `public`, missing folder), `PimcoreCoreCacheWarmerTest` (lowercase class name,
  stray PHP file in `var/classes`, writable vs read-only definitions – B-95, B-96), `MkdirCacheWarmerTest` (B-101).
- `tests/Unit/HttpKernel/Response/CodeInjectorTest.php` tests `Pimcore\Http\Response\CodeInjector`. Move it to
  `tests/Unit/Http/Response/` (I-80 says no test covers `lib/Http`; this one does). It also types a property with the
  PHPUnit 5 class `PHPUnit_Framework_MockObject_MockObject` (does not exist; use `MockObject`), and the helper
  `private function testInject()` is named like a test method. Rename it (`assertInjection()`).

---

## Image

### [ ] I-88 – Adapter API: one real contract instead of interface + abstract class

**File:** `lib/Image/AdapterInterface.php`, `lib/Image/Adapter.php`, `lib/Image.php`; consumers
`models/Asset/Image.php:200-213`, `models/Asset/Image/Thumbnail/Processor.php`

- `AdapterInterface` is what the container alias exposes, but the processor and `Asset\Image::getImageTransformInstance()`
  require the abstract `Adapter` (they call `setPreserveColor()`, `setColorspace()`, `setForceProcessICCProfiles()`,
  `preModify()`, …). A custom adapter that only implements the interface is rejected at runtime.
- The abstract methods in `Adapter` carry `@deprecated Provided by AdapterInterface::…` (`:309-331`). They are not
  deprecated in any useful sense (the class must declare them abstract or implement them). Remove the tags.
- Move the option setters into the interface (or a second `ConfigurableAdapterInterface`), type the processor against it,
  and drop the `instanceof Adapter` check.
- The `@return $this` doc blocks on `static` return types (`AdapterInterface.php`, 30×) and `@return $this|false` can go;
  `load(): static|false` would be clearer as `static` + exception.
- `getResource(): mixed` → a generic `@template TResource` (GD: `GdImage`, Imagick: `\Imagick`).
- `Pimcore\Image::getInstance()` is a static container lookup. Inject an `ImageAdapterFactory` service (it can still return
  a non-shared adapter per call; see CoreBundle B-33).
- Overlay / background / mask path resolution (asset path → project path → web root) is implemented differently in each
  method and adapter (`GD.php:236-237` web root, `:293-294` project root; `Imagick.php:690-691`, `:736-749`, `:783-795`,
  `:809-810`). Put it in one protected method of the base class (fixes B-107).

### [ ] I-89 – `preModify()` writes and re-reads a full PNG for every transformation after the first

**File:** `lib/Image/Adapter.php:333-362`

- Every transformation calls `preModify()`; once the image is modified, `reinitializeImage()` saves it as `png32` (or the
  original format) to a temp file, destroys the resource and loads the file again. A thumbnail with five steps does four
  full encode / decode / disk round trips, and with Imagick the load also re-runs colour-profile handling checks.
- The temp file name uses `uniqid()` (`:347`) instead of `File::getLocalTempFilePath()`, and stays until the adapter is
  destroyed.
- Find out which operation actually needed the reset (historically: page geometry after crop, colour space after
  composite) and do that in memory (`$imagick->setImagePage(0, 0, 0, 0)`, `clone`), or keep the round trip only for the
  cases that need it.

### [ ] I-90 – `Imagick` adapter cleanup

**File:** `lib/Image/Adapter/Imagick.php`

- Commented-out code (`:136-137`, `:146`, `:392-410`) and the HHVM comment (`:203`) can go.
- The vector format list exists twice with different content (`:903-922` vs `:949`, which contains `'EPT'` twice). Use
  one `private const array VECTOR_FORMATS`.
- `defined('Imagick::ALPHACHANNEL_REMOVE')` (`:212-215`) has been true since imagick 3.2; use the constant.
- `Config::getSystemConfiguration()` is read inside `load()` (`:131`) and the profile getters (`:430`, `:460`). Inject the
  settings (constructor / setter from the service definition) and drop the static profile cache (B-108).
- Static properties `$RGBColorProfile` / `$CMYKColorProfile` → camelCase.
- `load()`: `unset($this->resource); $this->resource = null;` (`:65-68`) without `clear()`; use `destroy()`.
  `$i->setColorspace()` after `readImage()` (`:93`) only affects later reads and has no effect here.
- `supportsFormat()` stores the result in the shared cache with no lifetime (`:993`). A delegate installed later stays
  "unsupported" until a cache clear. Use a TTL. `checkFormatSupport()` leaves a `tmpfile()` open (`:1040`).
- `Logger::err()` / `Logger::warn()` / `Logger::info()` static calls → injected PSR logger.
- Throw a dedicated exception (`ImageWriteException`) instead of `\Exception` in `save()` (`:275`).
- `isVectorGraphic(?string $imagePath)` widens the interface method with a parameter; make it a private helper
  `isVectorPath()` and keep `isVectorGraphic()` parameterless.

### [ ] I-91 – `GD` adapter cleanup and PHP 8.5 readiness

**File:** `lib/Image/Adapter/GD.php`

- `imagedestroy()` (`:133`) does nothing since PHP 8.0 (`GdImage` objects) and is deprecated in PHP 8.5. Remove it
  (set `$this->resource = null`).
- `getimagesize($this->path)` (`:40`) re-reads the file; use `imagesx()` / `imagesy()` on the loaded image.
- `supportsFormat()` has no `avif` mapping although `imageavif()` exists since PHP 8.1 (`AVIF Support` in `gd_info()`).
- `save()`: the format → function mapping (`:70-104`) is clearer as a `match` that returns a closure per format; unknown
  formats should throw instead of silently writing PNG (`:91-93`).
- Log (once) when a transformation is not supported by GD instead of silently returning `$this` (B-107).
- `@var resource|GdImage|false|null` (`:27-29`): resources are gone since PHP 8.0 → `?GdImage`.

### [ ] I-92 – `Adapter` base class: PHP 8.4 cleanup

**File:** `lib/Image/Adapter.php`

- Add `declare(strict_types=1)` (it is the only PHP file in `lib/Image` without it), after fixing the float paths (B-110).
- `cover()`: the ten-branch `if/elseif` on orientation strings (`:157-199`) → a backed enum `CoverPosition` with a method
  that returns the crop offset, or at least a `match`.
- `colorhex2colorarray()` → a small validated parser (accept `#rgb`, `#rrggbb`, `#rrggbbaa`), and a value object instead of
  an array with mixed int / string keys.
- `removeTmpFiles()` (`:80-93`): the `@todo duplication` with `Document\Adapter` still holds; use
  `TemporaryFileHelperTrait` or `File::getLocalTempFilePath()` and reset the array after cleanup.
- `getVectorRasterDimensions()` → `array{width: int, height: int}` shape and `(int) round()`.
- Typed property `$tmpFiles` → `/** @var list<string> */`.

### [ ] I-93 – `HtmlToImage`: service instead of static class, remove the deprecated Chromium path in 12.x

**File:** `lib/Image/HtmlToImage.php`, `lib/Image/Chromium.php`

- Static class with static state (`$supportedAdapter`). Make it a service with the Gotenberg / Chromium settings injected and
  implement `ResetInterface` (or no cache at all, see B-113).
- `convert()` forwards with `...func_get_args()` (`:100`, `:102`). Pass the named parameters explicitly.
- `new Connection($chromiumUri)->connect()` (`:64`) opens a socket only to test it and never closes it.
- `convertChromium()` closes the page but not a browser it started itself (`:154-159`, `:188-190`). Call `$browser->close()`
  in `finally` when it was created locally (**not verified locally** whether the library kills the process on destruct).
- The Chromium path and the `Chromium` alias file are deprecated since 11.2 (`:138`, `Chromium.php:20`); remove both in 12.x,
  together with the `chromium` config node (CoreBundle I-57) and `getChromiumBinary()`.
- `$windowSize` as `'1280,1024'` string → two `int` parameters.

### [ ] I-94 – Image optimizers: tagged services, streams, configurable chain

**File:** `lib/Image/Optimizer.php`, `ImageOptimizerInterface.php`, `Optimizer/SpatieImageOptimizer.php`, `Optimizer/OptimizerInterface.php`

- Register optimizers via `!tagged_iterator pimcore.image.optimizer` (CoreBundle I-34) and drop `registerOptimizer()`
  with its non-strict object `in_array()` (`:80`).
- `$storage->read()` / `file_get_contents()` load the whole image into memory (`:39`, `:69`). Use `readStream()` /
  `writeStream()`.
- `$storage->mimeType($path)` is called once per optimizer (`:42`). Read it once.
- The hand-written `usort()` comparator (`:59-65`) → `usort(..., fn ($a, $b) => $a['filesize'] <=> $b['filesize'])`, or
  just track the smallest result in the loop.
- `SpatieImageOptimizer` builds the chain on every call; build it once in the constructor, make the options configurable
  (B-112), and consider `Gifsicle` / `Avifenc` for GIF and AVIF thumbnails (`supports()` covers only JPEG / PNG / WebP).
  Remove the leftover IDE comment `//  Implement supports() method.` (`:54`).
- `OptimizerInterface`: empty doc-block lines (`:24-28`).

### [ ] I-95 – Tests

**Files:** new `tests/Unit/Image/*` (no test covers `lib/Image` today)

- Adapters (run for GD and Imagick with small generated images):
  - `getContentOptimizedFormat()` on an opaque RGBA image finishes quickly (B-102);
  - animated GIF keeps its frame count (B-103) and stays animated with `SOURCE` (B-104);
  - `cover()` on a smaller image and `cropPercent()` over the edge report the real size (B-105);
  - GD: JPEG save without quality, centred overlay, crop at the edge (B-106); uppercase `.PNG` keeps alpha (B-107);
  - invalid composite name falls back (B-108); `grayscale()` on IM7 (B-109); size 0 / `#fff` (B-110).
- `Optimizer`: a failing optimizer is logged, a larger result is not written (B-111).
- `HtmlToImage`: detection is retried after a failure (B-113), with a fake Gotenberg client.

---

## Loader

### [ ] I-96 – `ImplementationLoader` family: PHP 8.4 cleanup and typed results

**File:** `lib/Loader/ImplementationLoader/*.php`; subclasses `lib/Model/Factory.php`, `lib/Model/Document/Editable/Loader/EditableLoader.php`,
`lib/Model/DataObject/ClassDefinition/Loader/*`, `lib/Model/Asset/Metadata/Loader/DataLoader.php`

- Add `@template T` to `LoaderInterface` (`build(): T`) and to `ImplementationLoader`. The subclasses then only declare the
  type in PHPDoc and can drop their one-line `build()` overrides that exist only to narrow the return type.
- `get_class($loader)` → `$loader::class` (`ImplementationLoader.php:117`, `:126`).
- `array_reverse($this->loaders, true)` on every uncached lookup (`:70`): store the loaders in lookup order (prepend in
  `addLoader()`), or replace "last added wins" with explicit priorities (I-97).
- `protected array $loaders` is read directly by `Model\Factory::getClassMap()` (`Factory.php:31`). Make it private and give
  `Factory` a method, or let `ClassMapLoader` maps be merged at container build time.
- `supportsClassName()` / `getClassNameFor()` repeat the lookup and the "not a `ClassNameLoaderInterface`" checks
  (`:96-131`). Merge into one private helper.
- `AbstractClassNameLoader::build()` calls `array_values($params)` (`:35`), so string keys are dropped and the values are
  passed by position. Since PHP 8.1 `new $class(...$params)` supports named arguments; keep the keys (or document that
  params are positional only).
- Empty doc-block lines in `LoaderInterface` (`:25-36`), `@var LoaderInterface $loader` inside the loop (`:69`).
- Constructor promotion / `private readonly` in `ClassMapLoader` and `PrefixLoader` (the arrays are only set in the
  constructor).
- `Exception\InvalidArgumentException` is only used by `PrefixLoader`; keep it but mark the exceptions `final`.

### [ ] I-97 – Loader wiring: explicit priorities, drop the Pimcore 4 prefixes

**File:** `bundles/CoreBundle/src/DependencyInjection/PimcoreCoreExtension.php:148-212`;
`bundles/CoreBundle/config/implementation_factories.yaml:34-45`; `bundles/CoreBundle/config/pimcore/default.yaml:277-284`

- Precedence is defined by registration order (class-map loader added after the prefix loader, `FallbackBuilder` added in
  YAML before the class-map loader from the extension). Use a priority argument (or tagged loaders with `priority`)
  so the order is visible.
- `\Object_Class_Data_` and `\Object_Class_Layout_` are Pimcore 4 class prefixes (`default.yaml:279`, `:284`). No such
  classes exist; every unknown name costs one more autoload attempt. Remove them (deprecation note for projects that
  still define such classes).
- User-configured `prefixes` are appended after the defaults (array prototype merging), so a user prefix cannot replace a
  built-in type with the same name; only `map` can. Document this next to the config node.
- `setPublic(false)` on the new definitions is the default (`:155`, `:194`, `:204`; also listed in CoreBundle I-61).

### [ ] I-98 – `PrefixLoader`: validation and simpler class lookup

**File:** `lib/Loader/ImplementationLoader/PrefixLoader.php`; `lib/Tool.php:524-580`

- Validate prefixes once: non-empty, ends with `\`, normalise the leading `\` (B-116). `gettype()` in the error message
  (`:53`) → `get_debug_type()`.
- Accept `list<string>` and use `array_values(array_unique(...))` (`:59` keeps gaps in the keys).
- `Tool::classExists()` installs a custom error handler around `class_exists()` to hide old `include_once` warnings
  (`Tool.php:568-575`). Composer's autoloader does not warn for missing classes; plain `class_exists()` is enough here.
- Cache misses too (`$this->cache[$name] = null` with `array_key_exists()`), so repeated unknown names do not walk all
  prefixes again.
- Build the `Inflector` once and share it (static factory or injected service) instead of per loader instance (`:40`).

### [ ] I-99 – Tests

**Files:** new `tests/Unit/Loader/ImplementationLoader/*` (no test covers `lib/Loader` today)

- `ImplementationLoader`: last-added loader wins; `addLoader()` after a lookup takes effect (B-115); `getClassNameFor()` with
  a loader that is not a `ClassNameLoaderInterface`.
- `PrefixLoader`: abstract class, name with `\`, wrong base type → `UnsupportedException` (B-114); several prefixes; empty
  prefix list throws.
- `ClassMapLoader`: leading `\` and case of keys (B-116).
- `AbstractClassNameLoader::build()` with positional and named params (I-96).

---

## Localization

### [ ] I-100 – PHP 8.4 cleanup of `LocaleService` / `IntlFormatter`

**Files:** `lib/Localization/LocaleService.php`, `lib/Localization/IntlFormatter.php`, `lib/Localization/LocaleServiceInterface.php`

- Constructor property promotion with `readonly` for `$requestStack`, `$translator` (`LocaleService.php:27-37`) and
  `$localeService` (`IntlFormatter.php:50`, `:71-74`; the parameter is named `$locale` but is the service – rename).
- The nine `DATE_*` / `DATETIME_*` / `TIME_*` string constants (`IntlFormatter.php:30-46`) + the 50-line `match`
  (`:110-160`) → a backed enum `DateTimeFormat: string` with `dateType()` / `timeType()` methods; keep the constants as
  deprecated aliases. The `match` collapses to one `new IntlDateFormatter($locale, $f->dateType(), $f->timeType())`.
- Return types: `bool|string` → `string` (with B-121/B-122); empty and stale docblocks (`:105-108`, `:186-190`) removed.
- `LocaleServiceInterface`: add `@return list<string>` for `getLocaleList()` and `@return array<string, string>` for
  `getDisplayRegions()`; document what `findLocale()` returns when nothing is known (`''` today, `:56`).
- `isLocale()` builds `array_flip()` over ~786 ICU locales on every call (`:39-43`); `SystemSettingsConfig.php:165` and
  `Tool/Admin.php:64` call it in loops. Memoise the flipped list in a property.
- `getLocaleList()` returns `ResourceBundle::getLocales('')` (`:74`), which is `array|false`; handle `false`. The list
  depends on the ICU version (e.g. includes `en_US_POSIX`), so the admin language list and the cached
  `system_supported_locales_*` entries (`Tool.php:178-235`) change with ICU upgrades; consider filtering or documenting it.
- Depend on `Symfony\Contracts\Translation\LocaleAwareInterface` instead of the concrete `Pimcore\Translation\Translator`
  (`:21`, `:31`).

### [ ] I-101 – Use Symfony's `LocaleSwitcher` for `setLocale()` and save/restore

**Files:** `lib/Localization/LocaleService.php:102-117`; `lib/Document/Renderer/DocumentRenderer.php:118-132`;
`tests/Support/Util/TestHelper.php:285-300`

- Symfony 7.4 ships `Symfony\Component\Translation\LocaleSwitcher` (`translation.locale_switcher`): `setLocale()` updates
  `\Locale::setDefault()`, the router request context and every `LocaleAwareInterface` service; `runWithLocale($locale, fn)`
  restores everything in `finally`.
- `LocaleService::setLocale()` updates only the main/current request and the Pimcore translator, so the router context
  (generated URLs with `{_locale}`), Twig's intl filters (`\Locale::getDefault()`) and other locale-aware services keep the
  old locale.
- Delegate to the switcher and add `runWithLocale()` to the interface; replace the manual save/restore in `DocumentRenderer`
  and `TestHelper` (fixes the restore part of B-118).

### [ ] I-102 – Country names from `symfony/intl` instead of `umpirsky/country-list`

**Files:** `lib/Localization/LocaleService.php:77-91`; `composer.json:130`

- `symfony/intl` (`Countries::getNames($locale)`) has the same CLDR data, handles the locale fallback chain itself and needs no
  file `include` (removes B-119). It would replace a data-only dependency. Whether `symfony/intl` is already installed as a
  transitive dependency is **not verified locally** (`vendor/` missing; it is not in the root `composer.json`).
- Check the output keys first: `CountryOptionsProvider` keeps only 2-letter keys of the umpirsky list (whether that list has
  other keys is **not verified locally**) with
  `strlen($short) === 2` (`CountryOptionsProvider.php:49`); `Countries::getNames()` returns ISO 3166-1 alpha-2 only, so the
  filter becomes unnecessary.

### [ ] I-103 – `IntlFormatter` API: timezone, precision, money

**File:** `lib/Localization/IntlFormatter.php`

- Optional `?DateTimeZone $timezone` and an optional custom ICU pattern for `formatDateTime()` (B-121).
- `formatNumber()` uses `NumberFormatter::DECIMAL` defaults: at most 3 fraction digits, so `1.23456` prints `1,235`
  (verified). Add `?int $minFractionDigits`, `?int $maxFractionDigits`.
- `formatCurrency()`: accept `int|float|string` (or a minor-unit `int` / Brick Money style object) to avoid float rounding.
- Reuse: Twig already has `format_currency` / `format_date` in `twig/intl-extra`; document when to use which, or expose
  `IntlFormatter` as a Twig extension so templates and PHP use the same locale source.

### [ ] I-104 – Tests

**Files:** new `tests/Unit/Localization/*` (no test covers `lib/Localization` today)

- `LocaleService`: request locale vs. default language (B-118); `getLocale()` after the request locale changes and after
  `reset()` (B-117); `setLocale(null)` restore; `isLocale('de_AT')` true, `isLocale('de-AT')` false; `getDisplayRegions()`
  with `../`, an unknown locale and a regional locale (B-119).
- `IntlFormatter`: locale change on the service (B-120); `DateTimeImmutable` in a non-default zone, numeric and non-numeric
  strings, unknown language `zz` (B-121); `setCurrencyFormat()` after first use, invalid pattern, missing
  `getCurrencyFormat()` key (B-122).

---

## Mail

### [ ] I-105 – PHP 8.4 cleanup of `Mail`, `Mailer`, `RedirectingPlugin`

**Files:** `lib/Mail.php`, `lib/Mail/Mailer.php`, `lib/Mail/Plugins/RedirectingPlugin.php`

- Constructor property promotion with `readonly` (`Mailer.php:29-37`). Make `Mailer` `final` or `@internal` (it is
  only a decorator). `RedirectingPlugin` is already `final`.
- `#[\Override]` on `Mailer::send()` and on `Mail::addTo()` / `addCc()` / `addBcc()` / `addFrom()` / `addReplyTo()`
  (`:867-920`), so a renamed parent method is caught.
- Type the constructor's first parameter (`$headers`, `:129`) as `array|Headers|null`, and describe the options array with
  an `array{subject?: string, document?: …, params?: array, hostUrl?: string}` shape.
- Docblock noise: empty ` *` lines, `@return $this` next to native `static`, `@return void Provides fluent interface`
  on a `void` method (`:377`), "Create a new RedirectingPlugin." Remove the dead commented-out code (`:464-468`).
- The dynamic getters `$this->$getterName()` (`:474-475`, `:484`, `:573`) → a `match` or an explicit
  `['To' => $this->getTo(), …]` map that static analysis can follow.
- `setDocumentSettings()` has two consecutive `if ($document instanceof Model\Document\Email)` blocks (`:383`, `:407`);
  merge them. (The four parse-and-add loops are models I-183, not repeated.)
- `throw new Exception(...)` (`:615`, `:744`, `RedirectingPlugin.php:79`) → specific exceptions
  (`MailRenderingException`, `InvalidArgumentException`, `DebugRecipientsMissingException`).
- `private static bool $forceDebugMode` (`:40`) is global mutable state across a worker's lifetime → move it to a
  service or config flag.

### [ ] I-106 – Replace the decorator + plugin with a Symfony Mailer `MessageEvent` listener (or `framework.mailer.envelope.recipients`)

**Files:** `lib/Mail/Mailer.php`, `lib/Mail/Plugins/RedirectingPlugin.php`, `bundles/CoreBundle/config/services.yaml:96-104`

- Symfony Mailer dispatches `Symfony\Component\Mailer\Event\MessageEvent` right before the transport sends, with access
  to a **copy** of the message and the final envelope (`$event->getEnvelope()`, `setEnvelope()`, `setMessage()`). A
  listener there covers explicit envelopes, every mailer and async workers, and never changes the caller's object.
  This fixes B-123 and B-124.
- For a plain "send everything to these addresses" in dev, Symfony already has `framework.mailer.envelope.recipients`.
  Pimcore could map the debug addresses to it, and keep only the debug block and subject prefix in the listener.
- Read the debug addresses and the debug flag lazily (not in the constructor), and implement `ResetInterface`.
- The `X-Transport: main` default (`Mailer.php:45-47`) only works while a transport named `main` exists. Pimcore's
  `default.yaml:30` defines it, and Symfony names a single `framework.mailer.dsn` `main` as well. Document it next to the
  config, or use `X-Transport` only when the project configured several transports.

### [ ] I-107 – Separate rendering from the `Mail` data object

**File:** `lib/Mail.php:167-180`, `:427-451`, `:492`, `:593-721`

- `Mail` pulls services from the static container (`Pimcore::getContainer()->get(...)` at `:492`, `:595`), reads the system
  config in its constructor (`:158`, `:170`), renders documents, inlines CSS and converts html to text. Move this into a
  `MailRenderer` service (Twig environment, `DocumentRenderer`, `Helper\Mail`) with explicit dependencies. Then `Mail`
  is a plain `Email` with params and a document id, and `send()` delegates to it. This is also the natural place for the
  C-10 "render once" flag.
- Default sender and reply-to: Symfony 7.4 supports global headers via `framework.mailer.headers` (e.g. `From`). Pimcore
  could map `pimcore.email.sender` / `return` to it instead of `init()` on every `new Mail()`. The exact option name and
  version should be checked against the 7.4 docs; **not verified locally**.
- Twig's `twig/twig-bridge` `BodyRenderer` / `TemplatedEmail` already render a template with a context and generate the
  text part. Consider building on `TemplatedEmail` for non-document mails.

### [ ] I-108 – Logging and result reporting

**Files:** `lib/Mail.php:503-537`; `lib/Helper/Mail.php::logEmail()`

- Log from Symfony's `SentMessageEvent` / `FailedMessageEvent`, which are dispatched where the mail is actually sent,
  also in a Messenger worker. Store the transport's `Message-ID` and the `SentMessage::getDebug()` output on failure (B-127).
- Return a result object (`sent` / `queued` / `suppressed` / `failed`) or at least make `getLastLogEntry(): ?Log`
  (B-129). The "all recipients blocklisted" case from models C-42 needs the `suppressed` status.
- Keep the `PRE_SEND` / `PRE_LOG` events but make "cancel sending" an explicit API (`$event->cancel()`) instead of
  removing the `mailer` argument.

### [ ] I-109 – Plain-text part: use Symfony's html-to-text converter

**File:** `lib/Mail.php:71-73`, `:222-237`, `:688-721`, `:794-809`

- `html2Text()` uses `league/html-to-markdown`, so the "plain text" part is Markdown (`# Heading`, `**bold**`,
  `[link](url)`). The option names `html2textOptions` / `setHtml2TextOptions()` are legacy from the old `html2text`
  library.
- Symfony Mime has `HtmlToTextConverterInterface` with `DefaultHtmlToTextConverter` and `LeagueHtmlToMarkdownConverter`
  (Symfony 6.1+). Inject a converter, so projects can choose, and drop the hand-made `DomCrawler` `<style>` stripping (B-131).
- A new `HtmlConverter` is created per mail; one shared instance is enough.

### [ ] I-110 – Tests

**Files:** `tests/Unit/Mail/MailTest.php` (covers construction, init, recipients, param rendering; no send, no debug redirect)

- Send with a `null://` transport (or an in-memory `MailerInterface` spy):
  - a param containing `{{ 7*7 }}` with no text body must not be evaluated (C-10);
  - debug redirect with an explicit `Envelope`, with a custom mailer, and with `setForceDebugMode(true)` (B-123);
  - the object is unchanged after a debug send, also after a transport exception, and a resend with
    `preventDebugInformationAppending()` does not throw (B-124);
  - a debug block with `$1` in a display name and html without `</body>` (B-125).
- `addTo('a@x', 'b@x')` gives two recipients (B-126). `serialize()` / `unserialize()` keeps params and the document id
  (B-127). `setContentType()` results in exactly one `Content-Type` header (B-128).
- `setDocument()` with an unknown id or path throws, and calling it twice does not duplicate recipients (B-130).
  `getLastLogEntry()` works before send and with logging disabled (B-129).

---

## Maintenance

### [ ] I-111 – PHP 8.4 cleanup of `lib/Maintenance`

**Files:** `lib/Maintenance/**`; `bundles/CoreBundle/config/maintenance.yaml`

- Constructor property promotion with `readonly` (`Executor.php:32-46`, `DbCleanupBrokenViewsTask`, `LowQualityImagePreviewTask`,
  `HousekeepingTask`, `ScheduledTasksTask`, `CleanupClassificationstoreTablesTask`, `TmpStoreCleanupTask`,
  `StaticPagesGenerationTask`); `final` where nothing extends them (all are `@internal`).
- `Executor::$tasks` is an array of arrays; `getTasks()` is documented as `TaskInterface[]` (`:106-112`) but returns
  `array{taskClass: TaskInterface, messengerMessageClass: ?string}[]` → a small `readonly` value object; validate
  `messengerMessageClass` (`class_exists`, constructor takes the name) at registration instead of `new $className($name)`.
- `ExecutorInterface::getTaskNames(): array` → `@return list<string>`; `getLastExecution()` / `setLastExecution()` belong to a separate
  status service (I-112).
- `throw new Exception(...)` (`Executor.php:51`) → a specific exception; `strpos(...)` used as boolean
  (`HousekeepingTask.php:54`) → `str_contains()`; `preg_match('/\.gz$/')` / `preg_replace('/\.log$/')` → `str_ends_with()` /
  `substr()` (`LogCleanupTask.php:46`, `:68`); `reset()/current()/next()` over a row (`DbCleanupBrokenViewsTask.php:44-46`) →
  `[$name, $type] = array_values($table)`.
- Log messages built by concatenation → PSR-3 placeholders with context (`VersionsCleanupTask`, `CleanupClassificationstoreTablesTask`,
  `DataObjectTaskHelper`).
- `Db::get()` static access (`CleanupClassificationstoreTablesTask.php:39`, `VersionsCleanupStackTraceDbTask.php:30`) → inject
  `Connection` like the other tasks. `maintenance.yaml` passes `@logger` to `VersionsCleanupStackTraceDbTask`, which has no
  constructor (dead argument; Symfony ignores positional args there).
- `TmpStoreCleanupTask` uses `executeQuery()` for a `DELETE` (`:37`) → `executeStatement()`.
- Tag `type` names mix styles (`cleanupBrickTables`, `VersionsCleanupStackTraceDb`, `low_quality_image_preview`,
  `documents_static_page_generate`); they are user-facing (`--job`). Document them or add normalised aliases.
- `CleanupBrickTablesTask` and `CleanupFieldcollectionTablesTask` are identical apart from the injected helper → one generic task
  class configured twice.

### [ ] I-112 – Scheduling on Symfony 7.4: deduplication, per-task schedules, real status

**Files:** `lib/Maintenance/Executor.php`; `bundles/CoreBundle/src/Command/MaintenanceCommand.php`; `lib/Messenger/Handler/*TaskHandler.php`

- Symfony Messenger 7.3+ has `DeduplicateStamp` (lock-backed): dispatch each task with `new DeduplicateStamp('pimcore-maintenance-' . $name)`
  so a task is queued at most once (B-137).
- Symfony Scheduler (`RecurringMessage::every()/cron()`, `#[AsPeriodicTask]` / `#[AsCronTask]`) can replace "cron runs everything every
  5 minutes": LQIP nightly, version cleanup hourly, log rotation daily, static pages per lifetime. Keep `pimcore:maintenance` as a manual
  trigger.
- Store per-task `lastStart`, `lastSuccess`, `lastError`, `duration` when the handler runs, and expose them in a health check instead of
  the single "dispatched at" timestamp.
- (`#[AsMaintenanceTask]` attribute registration is in CoreBundle TODO-3, not repeated.)

### [ ] I-113 – Batch processing in the scanning tasks

**Files:** `lib/Maintenance/Tasks/LowQualityImagePreviewTask.php:54-80`; `lib/Maintenance/Tasks/StaticPagesGenerationTask.php:45-82`

- `OFFSET` paging over a changing table is O(n²) and skips/duplicates rows when items are added or deleted during the run → keyset
  paging (`id < :lastId ORDER BY id DESC LIMIT n`).
- Push the filters into SQL (`staticGeneratorEnabled = 1`; an "LQIP missing" flag for images) so unchanged items are not loaded at all.
- Page size 10 with `collectGarbage()` per page is very small for listings that only need ids; load ids in bigger chunks and hydrate per
  item.

### [ ] I-114 – Log rotation and retention

**File:** `lib/Maintenance/Tasks/LogCleanupTask.php`; `lib/Maintenance/Tasks/VersionsCleanupStackTraceDbTask.php:30-33`

- Retention is hard-coded: 7 days for log archives (`LogCleanupTask.php:39`, `:50`, `:66`) and 7 days for version stack traces
  (`VersionsCleanupStackTraceDbTask.php:32`) → configuration (`pimcore.maintenance.log_retention_days`, …).
- Recommend (and document) logrotate or Monolog's `RotatingFileHandler` / `max_files` for `var/log`; keep `LogCleanupTask` as a
  pruning-only fallback, skipping files modified in the last minutes (B-135).
- `VersionsCleanupStackTraceDbTask` updates all matching rows in one statement; on large `versions` tables batch it (`LIMIT` loop) to
  avoid long locks.

### [ ] I-115 – Safer destructive cleanups

**Files:** `lib/Maintenance/Tasks/DbCleanupBrokenViewsTask.php`; `lib/Maintenance/Tasks/CleanupClassificationstoreTablesTask.php`;
`lib/Maintenance/Tasks/DataObject/*`

- These tasks delete data or drop schema objects every run, based on string parsing and current class definitions. Add a dry-run /
  report mode (console command `pimcore:maintenance:cleanup-tables --dry-run`), log at `warning` with table, fieldname and row count
  before deleting, and allow disabling them via config.
- Share one table-name parser for bricks, field collections and classification stores (B-136) and one quoting helper.
- Consider a grace period: only delete fieldnames that have been missing from the class definition for N runs/days, so a temporarily
  inconsistent deployment (definition files and DB from different releases) does not remove data.

### [ ] I-116 – Tests

**Files:** new `tests/Unit/Maintenance/*` / `tests/Model/Maintenance/*` (no test covers `lib/Maintenance` today)

- `Executor`: unknown task name, `Error` from a task, `validJobs` / `excludedJobs` filtering and unknown names, custom message class
  (B-137).
- `LogCleanupTask` with a temp `PIMCORE_LOG_DIRECTORY`: rotation once per day, compression failure keeps the source, `glob()` false
  (B-135).
- Table-name parsing for keys and class ids with `_` (B-136); `DbCleanupBrokenViewsTask` keeps views whose error is not 1356 or that
  are not Pimcore-owned (B-132).
- `LowQualityImagePreviewTask` runs at most once per window across two `execute()` calls on the same instance (B-133);
  `StaticPagesGenerationTask` without lifetime does not regenerate an existing file (B-134); `HousekeepingTask` honours the tmp option
  or the option is removed (B-138).

---

## Marshaller

### [ ] I-117 – `MarshallerInterface`: document and type the contract (PHP 8.4)

**File:** `lib/Marshaller/MarshallerInterface.php`

- The doc blocks are empty except for `@throws Exception` (`:24-28`, `:31-35`, with a double space in `@throws  Exception`). State the
  rule from I-42 here, where implementers look: `marshal()` = normalized value → storage value, `unmarshal()` = storage value →
  normalized value; `null` in → `null` out; `unmarshal()` must accept legacy / partial storage values (B-52).
- Add generics so static analysis can check the implementations and callers:
  `@template TNormalized`, `@template TStored`, `@param TNormalized|null $value` / `@return TStored|null`, and the `$params` shape (B-139).
- Replace the generic `@throws Exception` with a dedicated `MarshallingException` (or document that invalid input returns `null`),
  so callers in `Block` / `Classificationstore\Dao` can catch one type.
- Implementations: add `#[\Override]` to `marshal()` / `unmarshal()` in all 20 classes and `RgbaColorTrait` so signature drift is caught
  by PHP 8.3+.
- The interface is the public extension point for project marshallers (registered by tag key); mark that explicitly in the doc block
  (it is not `@internal`, unlike the implementations) and point to the tag name `pimcore.dataobject.fielddefinition.marshaller` and the
  key format `<format>_<fieldtype>` (I-59). Symfony 7.4: `#[AutoconfigureTag('pimcore.dataobject.fielddefinition.marshaller')]` on the
  interface only works together with an index attribute per class (see I-59).
- Naming: three unrelated "marshaller" contracts exist – `Pimcore\Marshaller\MarshallerInterface` (storage format),
  `Pimcore\Cache\Core\CacheMarshallerInterface` (cache, I-08) and `ClassDefinition\Data\BeforeEncryptionMarshallerInterface` /
  `AfterDecryptionUnmarshallerInterface` (encryption). Cross-reference them in the doc blocks, or rename the data one to
  `FieldDataMarshallerInterface` with a BC alias in a major release.

### [ ] I-118 – One contract test for all registered marshallers

**Files:** new `tests/Unit/Marshaller/MarshallerContractTest.php` (complements I-45)

- A data-provider test that loads every service tagged `pimcore.dataobject.fielddefinition.marshaller` and checks the interface rules:
  `marshal(null) === null`, `unmarshal(null) === null`, `unmarshal(marshal($sample))` equals `$sample` for a sample normalized value
  per type, and the result of `marshal()` survives `Serialize::serialize()` / `unserialize()` with `allowed_classes => false` once I-42
  is done.
- Run the encrypted-field marshallers with a delegate marshaller spy and assert that `object` is forwarded (B-139).

---

## Messenger

### [ ] I-119 – Message classes: `final readonly`, enums for type strings, routing via `#[AsMessage]`

**Files:** `lib/Messenger/*Message.php`; `bundles/CoreBundle/config/pimcore/default.yaml:41-51`

- All 11 messages are mutable classes with `protected` promoted properties and getters; `VideoConvertMessage` mixes `protected` and
  `private readonly`. → `final readonly class` (they are `@internal` DTOs).
- **BC note:** messages in the doctrine transport are stored with `PhpSerializer`. Changing property visibility or names changes the
  serialized property keys (`\0*\0id` → `\0Class\0id`) and breaks messages that are still queued. Keep `protected` names or drain
  the queues / add `__unserialize()` during the switch.
- String "type" fields (`'image'|'video'` in `CleanupThumbnailsMessage`, element types in `ElementDependenciesMessage`,
  `SanityCheckMessage`, `VersionDeleteMessage`) → backed enums; `CleanupThumbnailsHandler` builds a class name from it
  (`'Pimcore\Model\Asset\\' . ucfirst($type) . '\Thumbnail\Config'`, `:49`) → a `match`.
- Symfony 7.2+ `#[AsMessage(transport: 'pimcore_core')]` on each message keeps routing next to the class; YAML routing still overrides
  it for projects.
- `MaintenanceTaskMessage` and `ScheduledTaskMessage` are identical (only the routing differs) – keep both for routing, but share one
  handler (I-120).

### [ ] I-120 – Handlers: one registration style, typed batch jobs, shared helpers

**Files:** `lib/Messenger/Handler/*`; `bundles/CoreBundle/config/message_handler.yaml`

- Registration: YAML tags for all handlers, plus an `#[AsMessageHandler]` on `ElementDependenciesHandler` that is ignored (no
  `autoconfigure` in that file). Pick one: attributes with `autoconfigure: true`, or remove the attribute.
- `final` + constructor promotion with `readonly` (`AssetPreviewImageHandler`, `AssetUpdateTasksHandler`, `OptimizeImageHandler`,
  `GeneratePagePreviewHandler`, `ElementDependenciesHandler` use `protected` promoted properties).
- Replace `// @phpstan-ignore-next-line` on `process()` / `shouldFlush()` with `@param list<array{0: XMessage, 1: Acknowledger}> $jobs`.
  `HandlerHelperTrait::filterUnique(array $jobs, callable $callback)` → `Closure(object): string`, mark it `@internal`, and use it in
  `AssetPreviewImageHandler` (B-143) and `OptimizeImageHandler` (the same path can be queued twice).
- Batch sizes (5 / 100) are hard-coded → `BatchHandlerTrait::getBatchSize()` style constant or config. With the doctrine transport
  a batch of 100 keeps 100 messages delivered-but-unacked; document that `redeliver_timeout` must exceed the batch duration.
- Static service access inside handlers (`Storage::get('thumbnail')` per job, `Logger::err()`, `Asset\Image\Thumbnail\Config::getPreviewConfig()`)
  → injected dependencies.
- `MaintenanceTaskHandler` and `ScheduledTaskHandler` are identical → one handler with `#[AsMessageHandler]` methods for both
  messages.

### [ ] I-121 – Bus configuration for Symfony 7.4

**Files:** `bundles/CoreBundle/config/pimcore/default.yaml:31-54`; `lib/Messenger/Middleware/CollectGarbageMiddleware.php`;
`bundles/CoreBundle/src/EventListener/MessengerClearRuntimeCacheListener.php`

- One worker-cleanup mechanism for all Pimcore buses: a receive-side middleware or `WorkerMessageHandledEvent` listener that clears
  `RuntimeCache`, deletes registered temp files (B-79) and resets the core cache handler (B-02), replacing both `MessengerClearRuntimeCacheListener` and `CollectGarbageMiddleware` (B-140). Keep the middleware class for
  BC and move its service definition from the GEE bundle into core.
- Default `failure_transport` and explicit `retry_strategy` per Pimcore transport (B-141).
- `DeduplicateStamp` (Symfony 7.3+) for `AssetUpdateTasksMessage` (instead of the DB lock of models B-01), `AssetPreviewImageMessage`,
  `GeneratePagePreviewMessage`, `ElementDependenciesMessage`.
- Messages dispatched while the caller holds a DB transaction are inserted in the same transaction with the doctrine transport, but
  with the recommended RabbitMQ transport a worker can receive them before the commit. Dispatch after commit
  (models B-17 for `VersionDeleteMessage`) or document it for project code that wraps saves in transactions.

### [ ] I-122 – Tests

**Files:** new `tests/Unit/Messenger/*` (no test references `Pimcore\Messenger` today)

- `CollectGarbageMiddleware`: no cleanup on dispatch, cleanup after handling on receive (B-140).
- Batch handlers with an in-memory `Acknowledger`: duplicates are acked once and processed once; a throwing job is nacked and the
  others are acked (B-143).
- `AssetUpdateTasksHandler`: document with stored `PROCESSING_FAILED` and page count disabled → flag removed in DB (B-142).
- Message serialization round trip through `PhpSerializer` for every message class, so a visibility change (I-119) is caught.

---

## Migrations

### [ ] I-123 – PHP 8.4 / Doctrine Migrations 3.x cleanup

**Files:** `lib/Migrations/**`; `bundles/CoreBundle/config/migrations.yaml`; `bundles/CoreBundle/config/pimcore/doctrine_migrations.yaml`

- `FilteredTableMetadataStorage` and `FilteredMigrationsRepository` are both a factory (`__invoke(DependencyFactory)`) and the product.
  Split them: a small factory service returns a decorator built with constructor-injected inner services (`readonly` promotion), so
  there is no uninitialised typed property and no setter-based state (B-146).
- `@return $this` on `__invoke()` with a concrete return type (`FilteredTableMetadataStorage.php:35-38`) → `static`;
  `get_class($migration->getMigration())` → `$migration->getMigration()::class` (`FilteredMigrationsRepository.php:79`).
- The filtering loops (`FilteredMigrationsRepository.php:77-84`, `FilteredTableMetadataStorage.php:74-81`) → `array_filter()` with one
  shared `matchesPrefix()` helper (and the separator fix of B-146).
- `DirectoryAwareVersionComparator` doc block says "@throws ErrorException if the migration file is not found" – Composer's autoloader
  does not throw; only Symfony's `DebugClassLoader` / error handler can turn a stale classmap include warning into it. Remove the
  reflection altogether (B-145).
- `BundleAwareMigration` is public API for bundle authors: add a doc block with an example, mark `getBundleName()` as returning the
  kernel bundle name (`PimcoreXyzBundle`), and use the kernel from the migration's container instead of `Pimcore::getKernel()`.
- `all_or_nothing: false` and `check_database_platform: false` (`doctrine_migrations.yaml`) are deliberate; document why next to them
  (Pimcore migrations contain DDL, which MySQL cannot roll back).
- `migration_versions.version` has length 1024 – fine for FQCNs; the Doctrine 3 default table name differs, so mention the custom
  table in the upgrade notes for projects that run `doctrine:migrations:*` with their own config.

### [ ] I-124 – Tests

**Files:** new `tests/Unit/Migrations/*` (no test covers `lib/Migrations` today)

- Comparator: ordering by registration, unknown namespace, missing class – result is the same for every input permutation (B-145).
- Filtered storage: `getExecutedMigrations()` and `reset()` with and without prefix, using an in-memory SQLite connection (B-144).
- Filtered repository: prefix with and without trailing `\`, `hasMigration()` honours the prefix (B-146).
- `BundleAwareMigration` with an unregistered bundle, a bundle without installer, and a not-installed bundle (B-147).

---

## Model

### [ ] I-125 – `AbstractModel` / `ModelInterface`: PHP 8.4 cleanup and an explicit DAO delegation API

**Files:** `lib/Model/AbstractModel.php`, `lib/Model/ModelInterface.php`

- `__call()` magic delegation to the DAO (`:206-220`) is the reason for B-148 and for the long `@method` blocks on every model. Long
  term: explicit, typed proxy methods (or a `getDao()->…` call at the call site) and an allowlist for the remaining magic.
- `get_class($this)` → `$this::class` (`:68`, `:210`, `:219`); `call_user_func_array([$dao, $method], $args)` → `$dao->$method(...$args)`;
  `str_contains($key, '\\') !== false` → `str_contains()` (`:87`); `strpos($class, '\\')` as boolean → `str_contains()` (`:123`).
- Legacy lookups: the `_` delimiter (Pimcore 4 class names), the `…_Resource` / `…\Resource` DAO suffix and `forbiddenClassNames`
  (`:86-91`, `:116-150`) and the `o_` compatibility (B-149) can go with a deprecation period.
- `include(__DIR__ . '/../../config/dao-classmap.php')` (`:75`) is retried on every `initDao()` if the file is missing (warning each
  time); load it once and fall back to `[]`.
- `setDao(?AbstractDao)` / `ModelInterface::setDao(Dao\AbstractDao)` do not accept `PimcoreLocationAwareConfigDao` (a `DaoInterface`
  only) → type against `DaoInterface`. `ModelInterface::setValues(array $data = [])` lacks the `$ignoreEmptyValues` parameter that
  `AbstractModel` and `DaoTrait` rely on.
- `__sleep()` → `__serialize()` / `__unserialize()` (fixes B-150; `__sleep` is soft-deprecated in the PHP 8.5 direction, see models
  I-07 for `Asset::__sleep()`).
- `getModelFactory()` and the DAO's `Db::get()` pull services from the static container; keep them, but document them as BC and inject
  where models are built by services.

### [ ] I-126 – DAO base classes: typed model, no `func_get_arg()`, resettable caches

**Files:** `lib/Model/Dao/AbstractDao.php`, `lib/Model/Dao/DaoTrait.php`, `lib/Model/Dao/PimcoreLocationAwareConfigDao.php`

- `DaoTrait::$model` is untyped (`/** @var AbstractModel */ protected $model`) and every DAO re-declares it with a narrower doc type →
  `@template TModel of AbstractModel` on the trait / base class and `@extends AbstractDao<Asset>` in subclasses.
- `AbstractDao::$db` is a `public` property (`:31`) → `protected` with a getter; it is assigned in `configure()` via `Db::get()`.
- `PimcoreLocationAwareConfigDao::configure()` reads its options with `func_get_arg(0)` (`:39`) although `DaoInterface::configure()`
  has no parameter; called without arguments it throws `ArgumentCountError`. → an explicit `configureLocationAware(array $params)`
  method or constructor arguments.
- The static config cache → resettable service (B-154). The column cache → one helper with `Cache::remove()` per table (B-153).
- `getForeignKeyName()` (`:107-115`) is a pure helper → `Db\Helper`.

### [ ] I-127 – Listings: one query builder path, safe iteration, clear count semantics

**Files:** `lib/Model/Listing/AbstractListing.php`, `lib/Model/Listing/Dao/AbstractDao.php`, `lib/Model/Listing/Dao/QueryBuilderHelperTrait.php`,
`lib/Model/Listing/Traits/*`, `lib/Model/Listing/Callable*ListingInterface.php`

- ORDER BY / LIMIT / GROUP BY are built twice: as strings in `Listing\Dao\AbstractDao` and on a `QueryBuilder` in
  `QueryBuilderHelperTrait` (`isset($order[$c])` vs `!empty($order[$c])`, B-152). Move the remaining string-built listings to the
  QueryBuilder helper.
- `Iterator` on the listing itself uses the internal pointer of `$this->data`: nested `foreach` over the same listing or a `foreach`
  inside a `current()` call breaks. → `IteratorAggregate` with `yield from $this->getData()` (keep `Iterator` methods deprecated).
- `count()` returns `getTotalCount()` (a separate `COUNT(*)` ignoring limit/offset) while `foreach` yields only the loaded page. This
  is what the paginator needs, but it surprises callers; document it and add `getLoadedCount()`.
- `isValidOrderKey()` returns `true` for everything (`:78-81`) and `setOrderKey($key, false)` passes raw SQL. Default to the table's
  columns (`getValidTableColumns()`) and document the raw variant as "trusted input only".
- `setGroupBy(string $groupBy, bool $qoute = true)` – parameter typo (`$qoute`, `:324`).
- `OrderListingTrait::$order` (a callable) shares its name with `AbstractListing::$order` (an array) – the two "order" APIs are
  unrelated; rename to `$orderCallback`. `callable` properties → `?Closure` (`FilterListingTrait`, `OrderListingTrait`,
  `QueryBuilderHelperTrait::$onCreateQueryBuilderCallback`).
- `quote(mixed $value, ?int $type)` / `quoteIdentifier()` call `Db::get()` per call; keep, but prefer bound parameters in docs.
  (DBAL 4 `quote(string)` change: models I-58.)

### [ ] I-128 – `ClassDefinitionManager` and the paginator subscriber

**Files:** `lib/Model/DataObject/ClassDefinition/ClassDefinitionManager.php`; `lib/Model/Paginator/EventSubscriber/PaginateListingSubscriber.php`

- `ClassDefinitionManager`: `glob()` can return `false` (`:83`); the result tuples `list<array{string, string, string}>` → a small
  `readonly` value object plus an enum for `SAVED` / `CREATED` / `SKIPPED` / `DELETED`; inject the connection instead of `Db::get()`.
- `saveClass()` compares `definitionModificationDate` from `fetchOne()` with `getModificationDate()` using `!==` (`:128`). It works
  because `pdo_mysql` on PHP 8.1+ returns native ints; with another driver or `ATTR_STRINGIFY_FETCHES` every class is re-saved on
  every rebuild. Cast both sides to `int`.
- `cleanUpDeletedClassDefinitions()` deletes every object of a class whose definition file is missing (`ClassDefinition::delete()`
  iterates all objects). The command asks for confirmation; add a dry-run listing and a check that the definition directory itself is
  readable (an unmounted volume would otherwise look like "all classes deleted").
- `PaginateListingSubscriber` throws when the target is not a `PaginateListingInterface` at priority `-5` (`:38-41`); subscribers
  registered with a lower priority for custom targets never run. Only set items for Pimcore listings and let KNP report unsupported
  targets. `@internal` on `getSubscribedEvents()` is misplaced.

### [ ] I-129 – Tests

**Files:** `tests/Model/DataObject/ListingTest.php` (covers `setCondition()` / `addConditionParam()` happy paths); new `tests/Unit/Model/*`

- `AbstractModel`: calling a protected model method from outside throws (B-148); `setValue('o_id', …)` (B-149); private subclass
  property survives `serialize()` (B-150).
- Listing: two identical `addConditionParam()` conditions with different values; `setCondition()` with and then without variables;
  float array parameter (B-151); `setLimit(0)` / offset-only on a string-built and a QueryBuilder listing (B-152); nested `foreach`
  (I-127).
- `AbstractDao::getValidTableColumns($t, false)` after an `ALTER TABLE` (B-153); config DAO cache reset between two simulated worker
  messages (B-154).

---

## Navigation

### [ ] I-130 – PHP 8.4 cleanup of the ZF1 port

**Files:** `lib/Navigation/**`

- ZF1 naming: `_`-prefixed properties (`$_pages`, `$_index`, `$_dirtyIndex`, `$_label`, `$_uri`, `$_accesskey`, …) and `_sort()`,
  `_htmlAttribs()`, `_normalizeId()`, `_getWhitespace()` → normal names (keep `protected` BC where subclasses exist).
- Magic `__get()` / `__set()` / `__isset()` / `__unset()` on `Page` and the finder `__call()` on `Container` (`findByLabel()` …) → keep
  for BC but add explicit methods; static analysis cannot see the magic.
- Long ZF1 doc blocks with `@return $this fluent interface, returns self` next to native `static`, and the copied Zend license header in
  `Page/Url.php` – shorten.
- `Page::$_defaultPageType` is `@deprecated` for Pimcore 12 – remove with the major.
- `setOrder(int|string|null)` accepts numeric strings; with typed callers `?int` is enough.
- Constructor promotion / `readonly` where applicable (`Builder::$requestHelper`, `PrefixRecursiveFilterIterator::$property` / `$value`,
  `AbstractRenderer::$templatingEngine`); `get_class($this)` → `$this::class`.

### [ ] I-131 – `Builder`: options object, explicit cache key, cache plain data

**File:** `lib/Navigation/Builder.php`

- Replace the array options + `OptionsResolver` with a `NavigationOptions` value object (named arguments in PHP 8.4), so the cache key can be
  derived from all fields (B-156).
- Cache a plain array (`Container::toArray()` / a DTO tree) and rebuild the objects on load: no serialized object graph, no stale keys
  (B-155), smaller cache entries, and no `force` write needed to avoid caching the active state (`:174-178`).
- A build context object for level, tags and id prefix instead of instance state (B-158).
- `getChildren()` is documented as an extension point; document `getChildren(true)` semantics for unpublished documents and make the
  hardlink branch (`:332-334`) explicit (`Hardlink::getChildren()` returns a listing whose `getData()` is already loaded).

### [ ] I-132 – Renderers: Twig templates as the default, safer attribute escaping

**Files:** `lib/Navigation/Renderer/*`

- The string-concatenation renderers (`Menu::_renderMenu()` is ~140 lines of nested state, `renderDeepestMenu()`, breadcrumbs) duplicate
  what a Twig partial does more readably; `renderTemplate()` / `renderPartial()` already exist. Ship default Twig partials for menu and
  breadcrumbs and keep the PHP renderers as fallback.
- `_htmlAttribs()` uses `ENT_COMPAT` and switches to single quotes when a value contains `"` – after escaping that can only happen for
  `on*` attributes. Use `ENT_QUOTES | ENT_SUBSTITUTE` and always double quotes; reject `on*` keys from page data instead of special-casing
  them.
- The constructor depends on the concrete `TwigDefaultDelegatingEngine` (`AbstractRenderer.php:88`) → depend on `Twig\Environment`.
- `RendererInterface` has no generics / return documentation for `render()` options; document the options per renderer.

### [ ] I-133 – Tests

**Files:** new `tests/Unit/Navigation/*` (no test covers `lib/Navigation` today)

- Container cache round trip: `hasPage()`, `removePage()`, `addPage()` and an `onlyActiveBranch` menu render after `unserialize()` (B-155).
- `closureHash()` with captured objects / invalid UTF-8; two `rootCallback`s on the same root (B-156).
- Active detection below a folder and below a page with parameters, with and without `active` (B-157).
- Renderers: `tabindex` / `rel` output, breadcrumb with a label-less page, `javascript:` href (B-159); `Url::getHref()` with fragment and
  `null` URI, regex finder on `null` properties (B-160).

---

## Normalizer

### [ ] I-134 – `NormalizerInterface`: correct doc, typed contract, no name clash with Symfony's serializer

**File:** `lib/Normalizer/NormalizerInterface.php`; `models/DataObject/Traits/SimpleNormalizerTrait.php`

- The doc block calls it a "Marker interface" (`:21`) although it declares two methods. Describe the contract instead: `normalize()` turns
  the field's runtime value into a plain, JSON-/serialize-safe structure (scalars and arrays, no objects), `denormalize()` reverses it;
  `null` in → `null` out; `denormalize(normalize($v))` must equal `$v`.
- Document `$params` as an array shape (`@param array{object?: ?Concrete, fieldDefinition?: Data, …} $params`) and make all callers pass
  the same keys (B-161).
- Generics for static analysis: `@template TValue`, `@template TNormalized`, `@param TValue|null $value` / `@return TNormalized|null`, and the
  reverse for `denormalize()`.
- `Pimcore\Normalizer\NormalizerInterface` has the same short name as Symfony's `Symfony\Component\Serializer\Normalizer\NormalizerInterface`
  (different signature: `normalize(mixed $data, ?string $format, array $context)`). Files that need both must alias one of them, and
  autocompletion picks the wrong one easily. Consider a clearer name (`FieldValueNormalizerInterface`) with a BC alias in a major release.
- `#[\Override]` on the 44 implementations (PHP 8.3+) so signature drift is caught; `SimpleNormalizerTrait` could become the documented
  default for scalar field types.
- Asset metadata types (`models/Asset/MetaData/ClassDefinition/Data/Data.php`) implement the same interface with different semantics
  (`Date` calls `normalize()` from its own getter, `Date.php:27`); document the two use cases or split the interface.

### [ ] I-135 – One round-trip contract test for all field types

**Files:** new `tests/Unit/Normalizer/FieldTypeNormalizerContractTest.php`

- A data-provider test over every core field type: `normalize(null)` / `denormalize(null)` return `null`, `denormalize(normalize($sample))`
  equals `$sample` (per type sample values: dates, ranges, relations as element references, quantity values, geo types, tables, links,
  image galleries, block / localized / brick containers), and the normalized value contains no objects (`json_encode()` succeeds).
- A custom field type without the interface inside a block / localized field / classification store must either throw at definition
  save or round-trip (B-161).

---

## Request

### [ ] I-136 – `DataObjectParam`: immutable attribute, explicit options, documented semantics

**File:** `lib/Request/Attribute/DataObjectParam.php`; `doc/02_MVC/04_Routing_and_URLs/02_Custom_Routes.md:95-103`

- `final readonly class` (attribute instances are value objects; today the promoted properties are public and mutable).
- `?bool $unpublished = null` is a tri-state where `null` behaves like `false` → `bool $unpublished = false`.
- `?array $options` ("options for the loader") is never read (CoreBundle B-63). Either remove it with a deprecation, or replace it with
  explicit, typed options that the resolver passes to `getById()` (`bool $force = false`), so users get an error for unknown keys.
- `class` → `@param class-string<\Pimcore\Model\DataObject\AbstractObject>|null $class`, so static analysis catches typos (the resolver
  silently skips unknown classes, CoreBundle B-63).
- Missing options that Symfony's `#[MapEntity]` offers and users ask for: the route parameter name when it differs from the argument
  (`id: 'newsId'`), and `nullIfNotFound` for nullable arguments (the current behaviour – 404 for an unknown id even on a nullable argument –
  is undocumented).
- Symfony 6.3+ / 7.4: let the attribute extend `Symfony\Component\HttpKernel\Attribute\ValueResolver` (pointing at the Pimcore resolver)
  so that an annotated argument is always handled by it, independent of resolver priorities (complements CoreBundle I-66).
- Document the security meaning of `unpublished: true` on the attribute and in `02_Custom_Routes.md`: it serves unpublished objects to
  **every** visitor, not only to admins (admins already see unpublished objects via `Tool::isElementRequestByAdmin()` without it). The current
  doc sentence ("working with unpublished objects") does not say so.
- Tests: covered by the resolver tests planned in CoreBundle TODO-3 (`DataObjectParamResolver` unit tests); add cases for `id` mapping and
  `nullIfNotFound` once introduced.

---

## Resolver

### [ ] I-137 – `ClassResolver`: typed map, explicit failure mode, injected instead of fetched from the container

**Files:** `lib/Resolver/ClassResolver.php`, `lib/Resolver/ResolverInterface.php`; `bundles/CoreBundle/config/class_resolvers.yaml`;
callers in `models/Document.php`, `models/Asset.php`, `models/DataObject/ClassDefinition/Data/Extension/Relation.php`

- `final readonly class ClassResolver` with `@param array<string, class-string> $map` (today `protected array $map`, untyped content).
  The interface doc says "Returns FQCN of class" but the method returns `null` for unknown names – document it (`@return class-string|null`).
- Add `resolveOrFail(string $name): string` (throws a dedicated exception) next to `resolve()`, and use it where the result is passed to the
  factory (B-162); keep `resolve()` for callers that want to skip unknown types (relations).
- Validate the configured classes at container build time (`class_exists()` / subclass of `Document` / `Asset` in
  `PimcoreCoreExtension::configureClassResolvers()`), so a typo in a project's `type_definitions` fails on `cache:clear` instead of at
  runtime.
- The map values keep the leading `\` from the YAML (`\Pimcore\Model\Document\Folder`); `Relation.php:48-50`, `:67-69` add one when it
  is missing. Normalise once in `flattenConfigurationForClassResolver()` (`ltrim($class, '\\')`).
- Callers fetch the two resolvers from the container (`Pimcore::getContainer()->get('pimcore.class.resolver.document')`, five places);
  inject them where the caller is a service, and register typed aliases (`#[Autowire(service: 'pimcore.class.resolver.asset')]`) for
  project code.
- Tests: unit test for `resolve()` / `resolveOrFail()`; model test that loads an element whose type is not configured (B-162).

---

## Routing

### [ ] I-138 – PHP 8.4 / Symfony 7.4 cleanup of the routing classes

**Files:** `lib/Routing/**`

- Constructor property promotion with `readonly` (`DocumentRouteHandler`, `DataObjectRouteHandler`, `DynamicRouteProvider`, `RouteReference`,
  `Element\Router`); `protected` properties in `final` classes → `private` (`DynamicRouteProvider`, `RouteReference`, `DocumentRoute`,
  `DataObjectRoute`).
- `RouteReference` → `final readonly class`; `DynamicRequestContext` has setters only used for the site pretty-URL case (`:131`) – make it
  immutable with a `withPath()` method.
- Controller strings `'Symfony\Bundle\FrameworkBundle\Controller\RedirectController::urlRedirectAction'` (twice) → `RedirectController::class
  . '::urlRedirectAction'`.
- `DocumentRouteHandler` reads the documents config twice in different ways: `Config::getSystemConfiguration('documents')` (`:81`) and
  `$this->config['documents']` (`:282`) → one injected source; `in_array()` without `strict` (`:325`); `setForceHandleUnpublishedDocuments()`
  is mutable state on a shared service (maintenance scripts set it and never reset it) → pass it per call or reset it in `finally`.
- `DynamicRouteProvider::getRoutesByNames(null)` returns `[]` with two TODO comments (`:97-98`); the CMF contract says "all routes" for `null`
  – document the deliberate deviation (there is no finite list of document routes).
- `DynamicRouteProvider::getRouteCollectionForRequest()` builds a `DynamicRequestContext` per handler; build it once (site path resolution
  is the same).
- `Element\Router` implements `RouterInterface` only to satisfy the chain router; `match()` / `matchRequest()` always throw and
  `getRouteCollection()` is empty – document it as a generator-only router (CMF `VersatileGeneratorInterface`), and type the `element`
  parameter (`ElementInterface`) in the docs of `pimcore_element`.
- `AttributeRouteControllerLoader` exists only for the admin bundle's route names (`pimcore_admin_admin_` → `pimcore_admin_`); keep, but
  document it and use `str_replace()` with a single pair instead of the replacement map.

### [ ] I-139 – Tests

**Files:** new `tests/Unit/Routing/*` (no test covers `lib/Routing` today)

- Document routes: key with `{2024}` / `{name}` matches and generates (B-163); unpublished link document → no route for anonymous requests
  (B-164); `getRouteByName('document_<id>')` for an unpublished document throws `RouteNotFoundException` (B-165).
- Redirect rules: pretty URL, trailing slash (`allow_trailing_slash: no`), case correction, non-GET requests not redirected, query string
  kept by `urlRedirectAction` for relative targets.
- `Element\Router::generate()` with a CDN-prefixed asset path including query string and port (B-166); object route generated by name for
  another site keeps that site.

---

## SearchClient

### [ ] I-140 – `SearchClientInterface`: document the contract, allow an exception hierarchy, avoid name clashes

**Files:** `lib/SearchClient/SearchClientInterface.php`, `lib/SearchClient/Exception/ClientException.php`

- Every method takes and returns an untyped `array $params` that mirrors the raw Elasticsearch / OpenSearch request body. Document the
  expected shape per method (`@param array{index: string, id?: string|int, body?: array<string, mixed>} $params`, `@return array<string, mixed>`),
  or at least link to the corresponding client API, so implementations for both engines behave the same.
- `ClientException` is `final` and has no structure: callers cannot tell "index not found" from "conflict" or "connection failed" without
  parsing messages. Make it non-final (or add `NotFoundException`, `ConflictException`, `ConnectionException` subclasses) and carry the HTTP
  status / engine error type.
- `count(array $params): array` has the same name as `Countable::count(): int`; a class implementing both interfaces is impossible. Prefer
  `countDocuments()` in a future major (keep `count()` deprecated).
- Inconsistent defaults: `refreshIndex()` / `flushIndex()` accept `$params = []`, all others require it; `exists*()` return `bool` while the
  rest return the raw response – document that `exists*()` must not throw for "not found".
- Missing operations used by typical reindex flows are only reachable through `updateIndexAliases()` (put/remove alias) and `reIndex()`; document
  the alias-swap pattern or add `putIndexAlias()`.
- The interface is public API without a stability marker: adding methods breaks every external implementation. Mark it `@api` and add new
  operations via separate, optional interfaces (or default methods in an abstract base class).
- Tests: none possible here without an implementation; provide a shared contract test (abstract PHPUnit test case) that the client packages
  can extend.

---

## Security

### [ ] I-141 – Modernise the security layer for Symfony 7.4 / PHP 8.4

**Files:** `lib/Security/**`

- Stateless hashers: replace the "one hasher per user" design (`setUser()` / `getUser()`, `UserAwarePasswordHasherFactory`) with a hasher that
  receives everything it needs through `hash()` / `verify()` (B-167). Remove the legacy encoder API (`hashPassword()`, `isPasswordValid()`,
  `$updateHash` flag) from `PasswordFieldHasher` and stop extending `PlaintextPasswordHasher` (B-168).
- Mark plaintext password parameters with `#[\SensitiveParameter]` (PHP 8.2+), as Symfony does, so they do not appear in stack traces:
  `PasswordFieldHasher::hashPassword()` / `isPasswordValid()` / `verify()`, `PimcoreUserPasswordHasher::hash()` / `verify()`.
- Constructor property promotion with `readonly` (`UserLoader`, `TokenStorageUserResolver`, `User`, `ObjectUserProvider`, `AbstractHasherFactory`);
  `final` where nothing extends; `call_user_func_array([$this->className, $getter], …)` → `$this->className::$getter(…)`.
- `UserChecker` extends `InMemoryUserChecker` (meant for in-memory users) → implement `UserCheckerInterface` directly.
- `User::getRoles()` loads every role with `Role::getById()` on each call (`:46-50`); it is called several times per request by the security
  component → compute once per request / cache on the proxy. `eraseCredentials()` (deprecated in Symfony 7.3, marked `#[\Deprecated]`) with two
  TODO comments → remove the TODOs.
- Duplicate interfaces: `PasswordHasherFactoryAwareUserInterface` is identical to `PasswordHasherFactoryAwareInterface` and unused → deprecate
  and remove.
- `SecurityHelper` (static `htmlspecialchars()` helpers) is `@internal` but escaping is the template engine's job; check the callers and move
  them to Twig escaping or `Symfony\Component\HtmlSanitizer`.
- `AbstractHasherFactory::$arguments` is typed `mixed` with a `@var array` doc → `array`.

### [ ] I-142 – Tests

**Files:** new `tests/Unit/Security/*` (no test covers `lib/Security` today)

- `UserLoader` reset between two requests through one kernel (C-11).
- Hasher: password change between two verifications in one process; re-hash does not save a stale object; two classes with the same
  identifier (B-167); `hash()` never returns the plaintext, long plaintext rejected (B-168).
- `ObjectUserProvider`: deleted / unpublished user on refresh, subclass support, duplicate usernames (B-169).

---

## Serializer

### [ ] I-143 – Pimcore serializer: named service instead of an empty subclass, cacheable normalizer support

**Files:** `lib/Serializer/Serializer.php`, `lib/Serializer/Normalizer/ReferenceLoopNormalizer.php`; `bundles/CoreBundle/config/serializer.yaml`

- `Pimcore\Serializer\Serializer` is an empty subclass of Symfony's `Serializer` that only exists to get a separate autowiring type. Symfony 7.4
  offers named serializers (`framework.serializer.named_serializers`, 7.2+) and `#[Autowire(service: 'pimcore.serializer')]` / `#[Target]`; a
  named `pimcore` serializer with its own normalizers would remove the subclass, the custom compiler pass and the `'@?'` placeholders.
- `getSupportedTypes()` returns `['*' => false]` (not cacheable), although `supportsNormalization()` only depends on the format → `['*' => true]`
  lets Symfony cache the decision (performance; Symfony docs: `true` = depends on type and format only).
- `supportsNormalization()` accepts every value for JSON, so the normalizer also runs for scalars and arrays; `final` class; remove the identity
  `array_map()`.
- Document what the Pimcore serializer is for (admin-style JSON with loop protection) and that models need `JsonSerializable` / normalizers to be
  serialized with their data (B-171).
- Tests: none exist for `lib/Serializer` – add cases for nested cycles, `JsonSerializable` returning a cycle, protected properties and an asset
  with pending stream data (B-171).
