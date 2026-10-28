# lib – TODO 2: Bugs

Scope: **(1)** `lib/Cache/**` (`Core/CoreCacheHandler`, `Core/WriteLock`, `Core/CacheQueueItem`,
`Core/CacheMarshallerInterface`, `Core/Exception/InvalidArgumentException`, `RuntimeCache`, `Tool/Warming`,
`Symfony/CacheClearer`, `FullPage/SessionStatus`), plus the direct consumers where a defect originates in the
interplay (`lib/Cache.php` facade, `lib/Pimcore.php::shutdown()`, `lib/Helper/LongRunningHelper.php`,
`models/Document.php::getById()` and the other element `getById()` methods, `bundles/CoreBundle/config/cache.yaml`,
`bundles/CoreBundle/src/EventListener/Frontend/FullPageCacheListener.php`,
`bundles/CoreBundle/src/Command/CacheWarmingCommand.php`) and `tests/Cache/Core/*`
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
**(34)** `lib/Session/**` (`Attribute/LockableAttributeBag`, `Attribute/LockableAttributeBagInterface`,
`Attribute/Exception/AttributeBagLockedException`) – no consumer exists in this repository; checked against `lib/Tool/Session.php`,
`doc/19_Development_Tools_and_Details/35_Working_with_Sessions.md` and Symfony's session storage (`Session::invalidate()`,
`NativeSessionStorage::clear()`, `MockArraySessionStorage::clear()`)
**(35)** `lib/Templating/**` (`TwigDefaultDelegatingEngine`, `Renderer/ActionRenderer`, `Renderer/IncludeRenderer`, `Renderer/EditableRenderer`),
plus the direct consumers where a defect originates in the interplay (`bundles/CoreBundle/config/templating.yaml`,
`lib/Twig/Extension/Templating/Inc.php` (`pimcore_inc()`), `lib/Controller/Controller.php:27-72`, `lib/Mail.php::renderParams()` (`:593-624`),
`models/DataObject/ClassDefinition/Layout/Text.php:118-145`, `models/Document/Editable/Snippet.php:100-145`, `lib/Tool/DomCrawler.php`,
`lib/Tool/Frontend.php::isOutputCacheEnabled()`, Symfony `HttpKernelRuntime::renderFragment()` / `FragmentHandler` / `InlineFragmentRenderer` /
`FragmentUriGenerator`, Twig `SandboxExtension`)
**(36)** `lib/Test/**` (`KernelTestCase`, `WebTestCase`) – no consumer in this repository (Pimcore's own suites use Codeception,
`tests/Support/Helper/Pimcore.php`); checked against `lib/Pimcore.php` (`setKernel()` / `getContainer()` / `shutdown()`), `lib/Bootstrap.php::kernel()`
(`:232-268`), `lib/Kernel.php::initializeContainer()` (`:155-178`), `lib/Cache.php::getHandler()`, `lib/Event/TestEvents.php`,
`doc/19_Development_Tools_and_Details/29_Testing/01_Application_Testing.md` and Symfony `KernelTestCase` / `WebTestCase`
**(37)** `lib/Tool/**` (`Admin`, `ArrayNormalizer`, `AssetsInstaller`, `Authentication`, `ClassUtils`, `Console`, `DeviceDetector`, `DomCrawler`,
`Frontend`, `HtmlUtils`, `MaintenanceModeHelper(Interface)`, `Requirements`, `Requirements/Check`, `Serialize`, `Session`, `Storage`, `Text`,
`Text/Csv`, `Transliteration`, `UserTimezone`), plus the direct consumers where a defect originates in the interplay (`lib/File.php::getValidFilename()`,
`lib/helper-functions.php::array_to_html_attribute_string()`, `lib/Config.php::getSystemConfiguration()`, `lib/Extension/Bundle/PimcoreBundleLocator.php:90`,
`models/Translation.php:208-213`, `:326`, `:402`, `models/Document/Editable.php:128-131` (and the other `HtmlUtils` callers in `models/Document/Editable/*`),
`models/DataObject/ClassDefinition/Data/Datetime.php:158-172`, `:402-409`, `bundles/CoreBundle/src/EventListener/Frontend/FullPageCacheListener.php:239-241`,
`:338-341`, `bundles/SeoBundle/src/Redirect/Csv.php:133`, `tests/Unit/Tool/*`, `tests/Model/Tool/*`)
**(38)** `lib/Translation/**` (`Translator`, `TranslationEntriesDumper`), plus the direct consumers where a defect originates in the interplay
(`bundles/CoreBundle/config/l10n.yaml:10-15`, `bundles/CoreBundle/config/services.yaml:106`, `bundles/CoreBundle/src/EventListener/DumpTranslationEntriesListener.php`,
`TranslationDebugListener.php`, `models/Translation.php` (`getByKey()`, `getValidLanguages()`), `lib/Tool/Text.php::wysiwygText()`,
`doc/06_Multi_Language_i18n/04_Shared_Translations.md`, `tests/Unit/Translation/TranslatorTest.php`)
**(39)** `lib/Twig/**` (`TwigEnvironmentConfigurator`, `Sandbox/SecurityPolicy`, `TokenParser/*`, `Node/*`, `Options/*`, `Extension/*` incl.
`Extension/Templating/**` – head / placeholder helpers, `Inc`, `Navigation`, `PimcoreUrl`, traits, exceptions), plus the direct consumers where a
defect originates in the interplay (`bundles/CoreBundle/config/templating_twig.yaml`, `config/pimcore/default.yaml:334-338` (sandbox defaults),
`lib/Mail.php::renderParams()` (`:593-624`), `models/DataObject/ClassDefinition/Layout/Text.php:118-145`,
`bundles/CoreBundle/src/EventListener/Frontend/DocumentRendererListener.php:43-52`, `lib/Templating/Renderer/EditableRenderer.php`,
`tests/Twig/*`, `tests/Unit/Twig/Options/BlockOptionsTest.php`)
**(40)** `lib/ValueObject/**` (`Collection/ArrayOfBoolean`, `ArrayOfIntegers`, `ArrayOfPositiveIntegers`, `ArrayOfStrings`, `Integer/PositiveInteger`,
`String/Path`) – only consumer in this repository: `lib/Twig/TokenParser/CacheParser.php:77`; public API announced in
`doc/23_Installation_and_Upgrade/09_Upgrade_Notes/README.md:139-141`; tests `tests/Unit/ValueObject/**`
**(41)** `lib/Video/**` (`Adapter`, `Adapter/Ffmpeg`) and the `lib/Video.php` facade, plus the direct consumers where a defect originates in the
interplay (`models/Asset/Video/Thumbnail/Processor.php:118-145`, `:244`, `:287`, `models/Asset/Video.php:158-255`,
`lib/Messenger/Handler/AssetUpdateTasksHandler.php:105-118`, `lib/Tool/Console.php`)
**(42)** `lib/Workflow/**` (`Manager`, `Service`, `ExpressionService`, `Transition`, `GlobalAction`, `WorkflowConfig`, `MarkingStore/*`,
`SupportStrategy/*`, `EventSubscriber/*`, `Notification/*`, `Notes/*`, `Place/*`, `Dumper/*`), plus the direct consumers where a defect
originates in the interplay (`models/Element/AbstractElement.php:480-515` (`isAllowed()`), `models/Element/WorkflowState.php:37-60`,
`models/DataObject/Concrete.php:428`, `doc/07_Workflow_Management/04_Permissions.md:30-41`)

Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md),
[CoreBundle TODO-1/2](../bundles/CoreBundle/TODO-1-CRITICAL.md) (Scope 15: C-08 `ResponseStack` / `OutputTimestampResolver` never reset,
B-50 empty matcher in the debug-toolbar exclusions, I-49 per-request work – not repeated),
[CoreBundle TODO-2](../bundles/CoreBundle/TODO-2-BUGS.md) / [SeoBundle TODO-3](../bundles/SeoBundle/TODO-3-IMPROVEMENTS.md)
(Scope 13: CoreBundle B-30 duplicate areabrick registration, SeoBundle I-16 `isInstalled()` query cost – not repeated),
[models TODO-2](../models/TODO-2-BUGS.md) (Scope 11: B-125 recursion guard seen from `Translation` – root cause is B-69 here),
[models TODO-1](../models/TODO-1-CRITICAL.md) (Scope 6: C-32 unvalidated class / field / select-option identifiers in generated
code, C-44 field-collection property collisions; I-83 code generator – not repeated),
[CoreBundle TODO-1/2/3](../bundles/CoreBundle/TODO-1-CRITICAL.md) (Scope 4: C-03 `pimcore:maintenance-mode --disable`,
C-06 / B-26 `exit` in commands, I-29 `lib/Console` helpers – not repeated),
[models TODO-2](../models/TODO-2-BUGS.md) (B-188 / B-206 listing `null`s from `fetchAllKeys()`, B-191 `save()` without
`isWriteable()`, B-238 `SettingsStore::set()` swallowing errors – not repeated; see C-04 / B-18 before applying them),
[CoreBundle TODO-1/2](../bundles/CoreBundle/TODO-2-BUGS.md) (C-11 config directory prefix, B-55 shallow merge of
`config_location` – not repeated),
[CoreBundle TODO-3](../bundles/CoreBundle/TODO-3-IMPROVEMENTS.md) (I-52 messenger worker static state reset, I-20
`CacheWarmingCommand` – not repeated)

Functional defects: wrong results, crashes on reachable input, swallowed errors, misleading output.
IDs are stable – refer to them as `B-xx` in later sessions. Line numbers are from commit `765372d338` (Scope 2–15: `765372d338`, Scope 16–42: `033aa183ef`).
Grouped by area, roughly ordered by impact inside each group: **Core cache handler / write lock** B-01–B-09,
**RuntimeCache** B-10, **Warming** B-11–B-12, **FullPage / SessionStatus** B-13, **Tests** B-14,
**Composer** B-15–B-16, **Config** B-17–B-25,
**Console** B-26–B-35, **Controller** B-36–B-42,
**DataObject** B-43–B-52, **Db** B-53–B-57,
**DependencyInjection** B-58,
**Document** B-59–B-68, **Element** – (no bug findings),
**Event** B-69–B-72, **Exception** – (no bug findings),
**Extension** B-73–B-77,
**Helper** B-78–B-87,
**Http** B-88–B-94,
**HttpKernel** B-95–B-101,
**Image** B-102–B-113,
**Loader** B-114–B-116,
**Localization** B-117–B-122,
**Mail** B-123–B-131,
**Maintenance** B-132–B-138,
**Marshaller** B-139,
**Messenger** B-140–B-143,
**Migrations** B-144–B-147,
**Model** B-148–B-154,
**Navigation** B-155–B-160,
**Normalizer** B-161,
**Request** – (no new bugs; see CoreBundle B-62, B-63),
**Resolver** B-162,
**Routing** B-163–B-166,
**SearchClient** – (no bugs),
**Security** B-167–B-170,
**Serializer** B-171,
**Session** B-172–B-173,
**Templating** B-174–B-177,
**Test** B-178–B-179,
**Tool** B-180–B-193,
**Translation** B-194–B-196,
**Twig** B-197–B-203,
**ValueObject** B-204,
**Video** B-205–B-208,
**Workflow** B-209–B-214.

---

## Core cache handler / write lock

## [ ] B-01 – Write-lock ownership by second-resolution timestamp: a process adopts and later deletes another process's lock

**File:** `lib/Cache/Core/WriteLock.php:73-89`, `:96-120`, `:162-197`, `:153-156`

- `initializeLock()` copies a valid foreign lock timestamp into `$this->timestamp` (`:83-85`). `lock()` then sees
  `$this->timestamp` set and does **not** write its own lock (`:104`). The process now "owns" the foreign lock.
- `removeLock()` deletes the lock when `$lock <= $this->timestamp` (`:174`). With the adopted timestamp this is
  always true → process B removes A's lock at B's shutdown while A is still clearing (fix C-01 first, otherwise this
  is hidden). Two processes locking in the same second have the same timestamp and remove each other's lock too.
- Read-then-delete (`:169-180`) is not atomic; a lock written between `getItem()` and `deleteItem()` is deleted.
- The in-memory check `if ($this->timestamp && $this->timestamp > 0) return true;` (`:132`) never expires: a
  long-running process that locked once considers the lock active forever, while the storage item expired after
  30 s (`$lifetime`, `:37`) and other processes write again. The lock is also not renewed while a long clear runs.
- `isLockValid(int $lockTime)` (`:153`) gets the raw cached value (`:81`, `:138`); under `strict_types` a non-int
  value (foreign writer, other serializer, corrupted entry) throws `TypeError` in every request.
- The lock key lives in the same pool as the data (`cache.yaml:57-58`): `pool->clear()` deletes it (`clearAll()`
  re-locks with `force`, `CoreCacheHandler.php:573`), and an adapter error makes both fail at once.

**Fix direction:** use a random token per process (`bin2hex(random_bytes(8))`) as the lock value and delete only
when the stored token is ours; never adopt a foreign lock as our own; check expiry in `hasLock()`. Preferably the
Symfony Lock component with a shared store (I-04).

---

## [ ] B-02 – Per-process state of `CoreCacheHandler` / `WriteLock` is never reset in long-running processes

**File:** `lib/Cache/Core/CoreCacheHandler.php:76`, `:82`, `:100`, `:107`, `:576`, `:684`; `lib/Cache/Core/WriteLock.php:46-48`;
`lib/Pimcore.php:165-177`; `bundles/CoreBundle/config/cache.yaml:45-62`

- `shutdown()` (write queue, clear shutdown tags, remove lock) only runs from `register_shutdown_function`
  (`Pimcore::shutdown()`). Nothing resets the handler between messages of a messenger worker, between requests of a
  FrankenPHP / RoadRunner worker (Symfony Runtime worker mode) or between iterations of a long CLI import.
  `MessengerClearRuntimeCacheListener` only clears `RuntimeCache`.
- `$clearedTags` grows without bound and blocks every later save of an item with a cleared tag – also forced saves
  (`prepareCacheTags()` `:419-425` runs in the immediate path too). After a worker saved `object_5` once, it can
  never cache anything tagged `object_5` again (redirect table, thumbnails config, rendered snippets, …).
- `$cacheCleared = true` (`:576`) is never reset → after one `clearAll()` the process never writes non-forced
  items again.
- `$tagsClearedOnShutdown` (`output`) is only invalidated at process end → the full-page cache is cleared hours
  after the change in a worker that runs for hours.
- `$saveQueue` items and `WriteLock::$timestamp` / `$lockInitialized` survive as well (see B-01).

**Fix direction:** implement `Symfony\Contracts\Service\ResetInterface` on `CoreCacheHandler` (call `shutdown()`
logic: clear shutdown tags, write/drop queue, remove lock, reset `clearedTags`, `cacheCleared`) and `WriteLock`;
the services are autoconfigured, so the `kernel.reset` tag is added automatically and the `services_resetter`
runs it after each messenger message / worker request. Keep `Pimcore::shutdown()` for classic FPM.

---

## [ ] B-03 – `forceImmediateWrite` bypasses the write lock, and immediate writes inside a DB transaction cache uncommitted data

**File:** `lib/Cache/Core/CoreCacheHandler.php:310-323`, `:443-457`, `:466`; `:790-799`

- The write-lock check exists only in `writeSaveQueue()` (`:790`). With `Cache::setForceImmediateWrite(true)` (not
  `force`) every `save()` goes directly to `storeCacheData()`, which checks `writeInProgress`, `enabled` and
  `cacheCleared` but not `hasLock()`. The documented exception (`testForceCacheIsWrittenWhenWriteLockIsDisabled`)
  is for `force` only.
- Immediate writes run while the caller may be inside a transaction. `storeCacheData()` re-reads the element on the
  same connection (`:466`) and caches the uncommitted state; non-element forced saves (`RedirectHandler.php:261`,
  `QuantityValue/Service.php:123`, `Asset/Document.php:133`) do the same with data they computed. If the
  transaction is rolled back, the cache keeps data that never existed in DB.

**Fix direction:** check `$this->writeLock->hasLock()` in `storeCacheData()` unless `$force`; when
`Db::get()->isTransactionActive()`, put the item into the save queue instead of writing (or register it for after
commit).

---

## [ ] B-04 – Storing an element replaces the caller's instance in `RuntimeCache` and dispatches `POST_LOAD`

**File:** `lib/Cache/Core/CoreCacheHandler.php:461-511`; `models/Document.php:211-256` (same in `Asset::getById()`,
`AbstractObject::getById()`)

- `Service::getElementById($type, $id, ['force' => true])` (`:466`) runs the full `getById()`: it loads from DB,
  calls `RuntimeCache::set($cacheKey, $fresh)` (`Document.php:236`) and dispatches `DocumentEvents::POST_LOAD` /
  `AssetEvents::POST_LOAD` / `DataObjectEvents::POST_LOAD`.
- In the immediate path (`force` / `forceImmediateWrite`, e.g. `Warming`, imports) this happens in the middle of the
  process: the next `getById()` returns the fresh object, not the instance the caller holds → two instances of the
  same element, unsaved changes on one are invisible on the other, `save()` on the old one overwrites changes made
  through the new one.
- `POST_LOAD` listeners run once more per cached element, also in `shutdown()` after the response was sent (listener
  side effects such as logging, counters, lazy-load hydration).
- The fresh copy's `__isBasedOnLatestData()` check (`:468`) is always true (it was just loaded) – the check no
  longer detects anything (see I-02).

**Fix direction:** load the fresh copy without the runtime cache and without events (DAO-level load, or a
`getById()` param that skips `RuntimeCache::set()` / `POST_LOAD`), or store the caller's instance again when
`__isBasedOnLatestData()` is true and re-fetch only otherwise.

---

## [ ] B-05 – Queued saves of falsy values (`[]`, `0`, `''`, `'0'`, `false`) are silently dropped

**File:** `lib/Cache/Core/CoreCacheHandler.php:336-350` (`:339`); compare `:311-315`

```php
$data = $this->prepareCacheData($item->getData());
if ($data) {        // prepareCacheData() signals "do not cache" with null, not with falsy
```

- The immediate path checks `null === $data` (`:312`); the queue path checks truthiness. `Cache::save([], 'x')`
  returns `false` and is not queued, `Cache::save([], 'x', force: true)` is stored. Empty results (empty listing,
  no redirects, empty translation domain, `0` counters) are recomputed in every request.
- `save()` returns `false` → callers that log or retry on `false` treat a valid empty value as an error.

**Fix direction:** `if (null !== $data)`; add a test for `[]` / `0` in the deferred path.

---

## [ ] B-06 – `clearTags(['output'])` returns `false` and logs a warning although the tag was scheduled

**File:** `lib/Cache/Core/CoreCacheHandler.php:590-618`, `:646-669`

- `normalizeClearTags()` moves shutdown tags (`output`) to `tagsClearedOnShutdown` and removes them from the list.
  When the list contained only `output` (e.g. `Cache::clearTag('output')` from the admin / full-page cache), the
  result is empty → warning *"Could not clear tags as tag list is empty after normalization"* and return `false`
  (`:609-617`), although the clear is scheduled correctly. Monitoring on warnings in the `cache` channel gets noise.
- Same result when all tags are in `tagsIgnoredOnClear` – there the warning is correct, but the two cases are not
  distinguished.

**Fix direction:** return `true` when at least one tag was moved to the shutdown list; log the warning only for
tags removed by `tagsIgnoredOnClear`.

---

## [ ] B-07 – Shutdown tag clear ignores `tagsIgnoredOnClear`

**File:** `lib/Cache/Core/CoreCacheHandler.php:624-637`, `:655-660`, `:698-706`

- `addTagIgnoredOnClear('output')` (used to keep the full-page cache during bulk operations) does not prevent the
  clear: `normalizeClearTags()` adds `output` to `tagsClearedOnShutdown` *before* applying the blocklist
  (`:655-660`), `addTagClearedOnShutdown()` has no check either, and `clearTagsOnShutdown()` invalidates the list
  as is.

**Fix direction:** filter `tagsIgnoredOnClear` in `addTagClearedOnShutdown()` (or in `clearTagsOnShutdown()`) and
document which list wins.

---

## [ ] B-08 – `cleanupQueue()` renumbers numeric cache keys: the queue then holds duplicates of the same key

**File:** `lib/Cache/Core/CoreCacheHandler.php:340`, `:355-364`, `:803-812`

- `$this->saveQueue[$item->getKey()]` with a numeric-string key (`'123'`) is stored under int `123`.
  `array_splice()` (`:363`) re-indexes integer keys to `0, 1, …`, so a later `save('123', …)` no longer overwrites
  the queued entry; both stay in the queue (the `processedKeys` check `:808` only hides this) and the older value
  may win the priority sort. Integer keys are also silently renumbered onto other items' slots.

**Fix direction:** keep the queue keyed by string and truncate with `array_slice($queue, 0, $max, true)`; then the
`processedKeys` check can go (I-03).

---

## [ ] B-09 – Failed-save log measures the item size wrongly and may exhaust memory

**File:** `lib/Cache/Core/CoreCacheHandler.php:521-538`

- `mb_strlen()` on `serialize()` output counts UTF-8 characters, not bytes; the size reported for binary / multibyte
  data is too small (adapter limits such as Redis / MySQL `max_allowed_packet` are in bytes).
- The serialize runs only to produce a log line; for the large items that typically fail (deep object graphs) it
  doubles memory at the moment the save just failed, and `serialize()` differs from the adapter's marshaller
  (igbinary / `DefaultMarshaller`), so the number is not the stored size anyway.

**Fix direction:** `strlen()`; better, log the type / class and let the adapter's own `CacheItem::METADATA` /
marshaller error be the source; skip the size calculation when not in debug.

---

## RuntimeCache

## [ ] B-10 – `RuntimeCache::clear()` loses the kept items while caching is disabled, and crashes without a container

**File:** `lib/Cache/RuntimeCache.php:195-209`, `:166-174`, `:56-63`; `lib/Helper/LongRunningHelper.php:94-105`

- `clear($keepItems)` copies kept items with `$newInstance->offsetSet()` (`:203`), which is a no-op while
  `self::$disabled` is true (`:169-171`). Importers call `RuntimeCache::disable()` and then `Pimcore::collectGarbage()`
  → `Config_system`, `pimcore_admin_user`, `pimcore_site`, `Pimcore_Db`, … are all dropped, which is exactly what
  `LongRunningHelper::$pimcoreRuntimeCacheProtectedItems` should prevent. Same for the temp-instance copy in
  `getInstance()` (`:58-60`).
- `clear()` calls `Pimcore::getContainer()->set(…)` (`:207`) without `hasContainer()`; before the kernel is booted
  (`getInstance()` returns the temp instance, `:71-75`) this is a call on `null` → `Error`. The temp instance is
  not cleared in that case either.

**Fix direction:** copy kept items with `parent::offsetSet()` (or `exchangeArray()`), guard the container access,
and reset `$tempInstance` in `clear()`.

---

## Warming

## [ ] B-11 – `Warming` builds SQL by string concatenation of unescaped types and class names

**File:** `lib/Cache/Tool/Warming.php:44`, `:57`, `:61`, `:73`; `bundles/CoreBundle/src/Command/CacheWarmingCommand.php`
(`--classes` is not validated)

- `"`type` IN ('" . implode("','", $types) . "')"` and `"className IN ('" . implode("','", $classes) . "')"`.
  A class name or type with `'` breaks the query; `--classes` from the console command goes in unvalidated
  (SQL injection from the CLI, and from any code that calls the public static API with user input).

**Fix direction:** bind the values (`setCondition()` accepts condition variables: one `?` per value, or quote each
value with `Db::get()->quote()`), same for `className`; validate class names against `ClassDefinition` in the
command.

---

## [ ] B-12 – Offset pagination without `ORDER BY`: elements are skipped or warmed twice

**File:** `lib/Cache/Tool/Warming.php:87-108` (`:97-99`)

- `setLimit()` / `setOffset()` without an order: MySQL does not guarantee a stable order across queries, so pages
  overlap or leave gaps. With the 2 s sleep between pages (`:106`) plus concurrent inserts / deletes, the offset
  shifts further. `getTotalCount()` is computed once at the start.

**Fix direction:** `setOrderKey('id')` + `setOrder('asc')`, better keyset pagination (`id > :lastId LIMIT n`);
loop until a page is empty instead of a precomputed iteration count.

---

## FullPage / SessionStatus

## [ ] B-13 – `SessionStatus` reads `$_SESSION`: no effect before the session is started, with non-native storage or in worker mode

**File:** `lib/Cache/FullPage/SessionStatus.php:46-63` (`:48`, `:53`, `:62`);
`bundles/CoreBundle/src/EventListener/Frontend/FullPageCacheListener.php:206`, `:315`

- At `kernel.request` (`FullPageCacheListener.php:206`) the session is usually not started yet:
  `NativeSessionStorage::getId()` returns `session_id()`, which is `''` before `session_start()` → `false` at `:48`,
  even when the visitor has a session cookie with data (cart, frontend login, flash). The cached anonymous page is
  served to that visitor. The check only works on the response side (`:315`), when the session happened to be started.
- `$_SESSION` is only filled by `NativeSessionStorage`. With `MockArraySessionStorage` / `MockFileSessionStorage`
  (functional tests), or in a FrankenPHP / RoadRunner worker where `$_SESSION` is not reset per request, the result
  is wrong or belongs to the previous request.
- `in_array($key, $ignoredSessionKeys)` is not strict (`:62`); `!empty($value)` treats `'0'` / `0` values as "no data".

**Fix direction:** decide on the Symfony session API: on the request side, `$request->hasPreviousSession()` (cookie
present) → disable, or start the session only if a session cookie exists; on the response side iterate the session
bags (`$session->getBag()`/`all()`, `getFlashBag()->peekAll()`) instead of `$_SESSION`; `in_array(…, true)`.
(The static page generator side is CoreBundle C-09.)

---

## Tests

## [ ] B-14 – Cache handler tests use `setMethods()`, which was removed in PHPUnit 10

**File:** `tests/Cache/Core/AbstractCoreHandlerTest.php:120-133`, `:40`; `composer.json` (`phpunit/phpunit ^10.0 || ^11.0`)

- `getMockBuilder(CoreCacheHandler::class)->setMethods(['isCli'])` → `Error: Call to undefined method
  MockBuilder::setMethods()`; every test of `DoctrineDbalCoreHandlerTest` / `RedisCoreHandlerTest` errors in setUp,
  so the whole cache suite gives no coverage. `PHPUnit_Framework_MockObject_MockObject` (`:40`, `:120`, `:124`) is the
  PHPUnit 5 class name.

**Fix direction:** `->onlyMethods(['isCli'])` and `MockObject&CoreCacheHandler`; better make `isCli()` injectable
(I-05) so no partial mock is needed.

---

## Composer

## [ ] B-15 – Bundle discovery reads `installed.json` from a guessed vendor path and fails silently

**File:** `lib/Composer/PackageInfo.php:50-62`, `:64-75`; `lib/Bootstrap.php:199`;
`lib/Extension/Bundle/PimcoreBundleLocator.php:110`, `:125`

- The path is `PIMCORE_COMPOSER_PATH . '/composer/installed.json'` (`:56`). The constant defaults to
  `PIMCORE_PROJECT_ROOT . '/vendor'` (or a `$_SERVER` value), not to Composer's real vendor dir. With a custom
  `config.vendor-dir` / `COMPOSER_VENDOR_DIR` the file is not found.
- A missing or unreadable file returns `null` (`:66`, `:74`) → `[]` (`:61`) without a log line. Every
  `pimcore-bundle` package then disappears from `PimcoreBundleLocator` (bundle manager, `pimcore:bundle:*`
  commands, installer), and `PackageVersionTrait::getDescription()` returns `''`. Nothing tells the user why.
- `PimcoreBundleLocator::findComposerBundles()` builds the package directory as `PIMCORE_COMPOSER_PATH . '/' . name`
  (`:125`) and ignores the `install-path` from `installed.json`. Packages installed outside `vendor/<name>`
  (`composer/installers` `installer-paths`, custom installers) are scanned in the wrong directory, so their bundles
  are not found.
- The result is memoized per instance (`:28`, `:52-54`), so after `composer require` a long-running worker keeps the
  old list. This is acceptable, but should be documented.

**Fix direction:** find the vendor dir from Composer itself:
`array_key_first(\Composer\Autoload\ClassLoader::getRegisteredLoaders())`, or inject it as a constructor argument.
Log a warning (or throw in debug) when `installed.json` is missing. Return the package's `install-path` resolved
against `vendor/composer`, and use it in the locator (see I-14).

---

## [ ] B-16 – Unexpected `installed.json` content raises `TypeError` instead of the documented `RuntimeException`

**File:** `lib/Composer/PackageInfo.php:44`, `:57-58`, `:64-72`

- `file_get_contents()` can return `false` (file deleted / permissions changed between `is_readable()` and the read,
  `:66-68`). Under `strict_types` this gives `json_decode(false)` → `TypeError`.
- Valid JSON that is not an object or array (`"x"`, `1`) decodes to a scalar. The `?array` return type (`:64`) then
  throws `TypeError`. Only `JsonException` is converted to the `RuntimeException` with the file name (`:69-70`).
- `$json['packages'] ?? $json` (`:58`) accepts any object. If an entry is not a package array, the typed closure
  `static fn(array $package)` (`:44`) throws `TypeError`. A package without `type` gives an undefined-key warning
  (`:44`).

**Fix direction:** check the `file_get_contents()` result. Require `is_array($decoded)` and
`is_array($decoded['packages'] ?? $decoded)`, otherwise throw the same `RuntimeException`. Filter the entries
with `is_array($p) && isset($p['name'])` and use `($package['type'] ?? 'library')`.

---

## Config

## [ ] B-17 – `deleteData()` checks writeability without the data source: deletes from the wrong location or crashes on a missing file

**File:** `lib/Config/LocationAwareConfigRepository.php:247-261`, `:124-140`

- `deleteData($key, $dataSource)` calls `isWriteable($key)` **without** `$dataSource` (`:249`), so the two
  data-source checks in `isWriteable()` (`:133-136`) never run:
  - write target `settings-store`, config still found in a left-over `var/config/<type>/<key>.yaml`
    (`dataSource = symfony-config`): the YAML file is deleted in production although it is not the write target. The
    compiled container still holds the config, so the item "comes back" until the next cache clear and then
    disappears;
  - config defined in `config/pimcore/*.yaml` (not in `var/config`) with write target `symfony-config` in debug:
    `unlink()` on a file that does not exist (`:254`) raises a warning, which becomes an `ErrorException` → HTTP 500
    in the admin.
- `$dataSource === null` (never loaded) deletes nothing and reports success; `stopMessengerWorkers()` still runs
  (`:260`).

**Fix direction:** `isWriteable($key, $dataSource)`; check `is_file()` before `unlink()` (or use
`Filesystem::remove()`); throw when there is nothing to delete.

---

## [ ] B-18 – `saveConfig()` writes even when the target is not writeable, and reports success for `disabled`

**File:** `lib/Config/LocationAwareConfigRepository.php:177-193`, `:129-131`

- This is the root cause of models B-191, which covers the `Predefined` symptom (not repeated): the repository
  itself does not enforce `isWriteable()`.
  - `symfony-config` with debug off: the YAML is written to `var/config` and only takes effect at the next container
    build, where it becomes active by surprise (and can break it, C-05);
  - `disabled`: nothing is written, the method returns normally, and `stopMessengerWorkers()` still restarts every
    worker (`:192`).
- The data source of the existing entry is not checked either. Saving a config that comes from the settings store
  while the write target is `symfony-config` creates a second copy, and from then on the read order decides which one
  wins.

**Fix direction:** `if (!$this->isWriteable($key, $dataSource)) throw …` in `saveConfig()` (pass the data source
from the DAO). Stop the workers only after a write happened.

---

## [ ] B-19 – `json_encode()` failures store an empty config: invalid UTF-8 wipes the settings-store entry

**File:** `lib/Config/LocationAwareConfigRepository.php:188-189`; `lib/Config/ReportConfigWriter.php:58-63`;
`models/Tool/SettingsStore.php:98`

- `json_encode($data)` without `JSON_THROW_ON_ERROR` returns `false` for invalid UTF-8 (e.g. a Latin-1 string from
  an import, a binary value), `INF` / `NAN`, or recursion. `SettingsStore::set()` accepts `bool`
  (`float|bool|int|string`), so `false` is stored as the string value (`''`).
- The next read (`getDataFromSettingsStore()` `:112` / `Config::getReportConfig()` `:310`) decodes `''` to `null`.
  The whole config entry (a thumbnail, a perspective, or **all** report settings) is gone, and the admin had been
  told "saved".

**Fix direction:** `json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION)`
in both places, and let the exception reach the controller. See models I-123 for a JSON type in `SettingsStore`.

---

## [ ] B-20 – `loadConfigByKey()`: empty YAML config falls through to the settings store; corrupt JSON is reported as found

**File:** `lib/Config/LocationAwareConfigRepository.php:70-117`

- With the default read target (`null`), `if (!$data)` (`:81`) also falls back for an existing but empty or falsy
  container config (`[]`, `''`, `0`). The settings-store value then wins, and `$dataSource` is overwritten.
- `getDataFromContainerConfig()` uses `isset()` (`:100`): a key with value `null` is "not found", but
  `$this->containerConfig[$key] ?? null` returns `null` anyway, so the method can return data with a `null`
  data source and vice versa.
- `getDataFromSettingsStore()` sets `$dataSource = settings-store` even when `json_decode()` fails (`:112-113`). The
  DAO then has `data === null` with a data source. `isWriteable()` uses that source, so a corrupt entry can be
  neither loaded nor overwritten from the other location.
- A read target of `disabled` matches neither branch (`:85-89`) and returns `[null, null]` without a log line. The
  config schema does not allow `disabled` for reads (`ConfigurationHelper.php:73`), but `getReadTargets()`
  (`:166`) accepts it.

**Fix direction:** check `array_key_exists()` and `null !== $data` instead of truthiness, use `JSON_THROW_ON_ERROR`
(log the key), and remove `disabled` from the accepted read targets (or handle it explicitly).

---

## [ ] B-21 – Container invalidation by touching `config/services.yaml`: new YAML configs are not picked up when the file is missing

**File:** `lib/Config/LocationAwareConfigRepository.php:280-287`, `:204`, `:255`; `:311-320`

- The debug container is rebuilt only when a tracked resource changes. New files in `var/config/<type>` are not
  tracked: `YamlFileLoader::load()` adds a `FileResource` per **existing** file only. The workaround is a `touch()`
  on `PIMCORE_PROJECT_ROOT . '/config/services.yaml'`.
- Projects without that file (Symfony Flex skeletons that split services, bundles-only setups, tests) never see a
  newly created config until a manual `cache:clear`. The admin shows "saved", the list does not contain the item.
- On a read-only project directory (container images, where only `var/` is writable) `touch()` fails with a warning,
  which becomes an `ErrorException` in debug, so the save request fails after the file was written.
- It modifies a tracked project file (mtime). On shared / network file systems this triggers rebuilds on every node.

**Fix direction:** in `loadSymfonyConfigFiles()` and in the Kernel closure (`Kernel.php:87-131`) register
`new DirectoryResource($configDir, '/\.ya?ml$/')` (or a `GlobResource`) on the container, and remove the `touch()`.
Shared loader: CoreBundle I-63.

---

## [ ] B-22 – `loadSymfonyConfigFiles()` loads YAML for read target `disabled` and crashes on a missing write target

**File:** `lib/Config/LocationAwareConfigRepository.php:289-321`

- The skip condition (`:298-300`) returns only for `settings-store`, or for "neither read nor write is
  `symfony-config`". With read `null` and write `settings-store` it still loads the write directory. This is
  consistent with `loadConfigByKey()`, but it is undocumented and differs from `fetchAllKeysByReadTargets()` (C-04).
- `[self::WRITE_TARGET]` is read without `??` (`:294`). An unknown `$configKey` or a bundle whose config tree has no
  `config_location` gives an undefined-key warning, which fails the container build in debug.
- `$readTargetConf[self::OPTIONS][self::DIRECTORY]` (`:304`): the default is `['directory' => null]`
  (`ConfigurationHelper.php:76-77`), which correctly falls back to the write directory (`:307-309`). But
  `options: {}` or options without `directory` in the project config give an undefined-key warning, which fails the
  container build in debug.
- It is duplicated with `Kernel.php:87-131` (CoreBundle B-55 / I-63, not repeated): both copies need the fix.

**Fix direction:** one `resolveSymfonyConfigDirectory()` helper (CoreBundle I-63) with explicit rules per
(read, write) pair. Use `??` with a clear exception naming the config key. Fall back to the write directory when the
read options have no directory.

---

## [ ] B-23 – Missing `config_location` keys give warnings / `TypeError` instead of a clear error

**File:** `lib/Config/LocationAwareConfigRepository.php:57-68`, `:147-156`, `:164`, `:236-241`;
`lib/Model/Dao/PimcoreLocationAwareConfigDao.php:44-48`

- `PimcoreLocationAwareConfigDao::configure()` passes `$params['storageConfig'] ?? null` to a constructor parameter
  typed `array $storageConfig` (`:62`). A DAO without storage config fails with a `TypeError`.
- `getWriteTarget()` reads `[WRITE_TARGET][TYPE]` (`:149`), `getReadTargets()` reads `[READ_TARGET][TYPE]` (`:164`),
  and `getVarConfigFile()` reads `[WRITE_TARGET][OPTIONS][DIRECTORY]` (`:238`), all without `??`. `options` is a
  `variablePrototype`, so `options: {}` in the project config removes the directory and gives `rtrim(null)` → `''`.
  The file is then written to `/<key>.yaml` at the file system root, or the write fails.
- `in_array()` without `strict` (`:151`, `:166`).

**Fix direction:** validate the storage config once in the constructor (required keys and a non-empty directory) and
throw an `InvalidConfigurationException` that names the config key. Keep `?array` / `array` consistent (I-16).

---

## [ ] B-24 – `BundleConfigLocator` searches recursively, in undefined order, and can return `false`

**File:** `lib/Config/BundleConfigLocator.php:58-95`; `lib/Kernel.php:78-83`; `lib/Routing/Loader/BundleRoutingLoader.php`

- `Finder::in($dir)` is recursive by default (`:87-92`). A `config.yaml` / `routing.yaml` in any subdirectory of
  `config/pimcore` (examples, `packages/`, a vendored copy) is loaded as a bundle config too.
- The Finder order depends on the file system. When a bundle ships `config.php` **and** `config.yaml` (allowed by
  the class doc block, `:29-30`), the merge order (later wins) differs between systems.
- `getRealPath()` returns `false` for a broken symlink. `$result[] = false` then reaches `$loader->load(false)`
  (`Kernel.php:81`) → `TypeError` during boot.
- `Resources/config/pimcore` is checked first and `config/pimcore` is then ignored (`:63`). A bundle that has both
  (during a migration to the Symfony ≥ 5 `config/` layout) loads only the legacy folder, silently.

**Fix direction:** `->files()->depth(0)->sortByName()`. Skip `false` paths with a log entry. Check both directories
(or deprecate `Resources/config`), and document the order.

---

## [ ] B-25 – `ReportConfigWriter`: same-process reads stay stale; `mergeConfig()` replaces nested sections

**File:** `lib/Config/ReportConfigWriter.php:48-74`; `lib/Config.php:298-319`

- `write()` stores the settings, but the runtime-cache entry `pimcore_config_report` set by
  `Config::getReportConfig()` (`Config.php:301-316`) is not updated. A later `getReportConfig()` in the same request
  or worker message returns the old config (e.g. the admin "save and reload" response).
- `mergeConfig()` uses `array_merge()` (`:71`). Merging `['analytics' => ['sites' => …]]` drops every other key of
  `analytics`. It is unused in core, but public for bundles.
- The comment *"the config returned from getReportConfig is readonly"* (`:68-69`) is outdated: it returns a plain
  array.

**Fix direction:** after the save, call `Config::setReportConfig($settings)` or remove the runtime-cache key. Use
`array_replace_recursive()` in `mergeConfig()` (or deprecate it), and remove the comment.

---

## Console

## [ ] B-26 – `--maintenance-mode` uses a fixed session ID and switches off maintenance mode that someone else turned on

**File:** `lib/Console/Application.php:86-93`, `:101-114`; `lib/Tool/MaintenanceModeHelper.php`;
`models/Tool/TmpStore.php:84-97`; `bundles/CoreBundle/src/EventListener/MaintenancePageListener.php`

- Activation always uses the constant ID `'cache-warming-dummy-session-id'` (`:88`). `MaintenancePageListener` lets
  through every request whose session ID equals the stored ID. So the "bypass" session is the same, publicly known
  value on every installation. Exploiting it needs a session with that ID: it is blocked by Symfony's default
  `use_strict_mode`, but possible with a custom handler or with strict mode disabled.
- `TmpStore::add()` does not overwrite an existing entry (`TmpStore.php:92-94`). If an admin (or a deployment script)
  already enabled maintenance mode, the command keeps the admin's entry. The TERMINATE listener then calls
  `deactivate()` (`:110-112`) and the legacy `Admin::deactivateMaintenanceMode()` (`:106-108`) unconditionally. A cron
  job such as `pimcore:cache:warming --maintenance-mode --ignore-maintenance-mode` that runs during a deployment ends
  the deployment's maintenance mode early.
- If the process is killed (SIGTERM / SIGKILL from a scheduler, OOM, `exit` – CoreBundle C-06), TERMINATE does not run.
  The site stays in maintenance mode until the TmpStore entry expires.
- When maintenance mode is already active, `--maintenance-mode` alone fails in the check at `:73-75` before the
  activation. The two flags interact in a way that is not documented.

**Fix direction:**
- Generate a random ID (`bin2hex(random_bytes(16))`).
- Remember whether *this* process activated maintenance mode, and deactivate only then, and only if the stored ID is
  still ours.
- Register `pcntl_signal` / `ConsoleEvents::SIGNAL` handling for SIGTERM / SIGINT to deactivate.
- Document the flag combination.

---

## [ ] B-27 – Every `new Application()` adds two more listeners to the shared event dispatcher (`stopMessengerWorkers()` creates one per config save)

**File:** `lib/Console/Application.php:50-115`; `lib/Helper/StopMessengerWorkersTrait.php:31-49`

- The constructor dispatches `SystemEvents::CONSOLE_INIT` and adds a `COMMAND` and a `TERMINATE` closure to
  `Pimcore::getEventDispatcher()` (`:61-114`), which is the container's shared `event_dispatcher`. Nothing removes them.
- `StopMessengerWorkersTrait::stopMessengerWorkers()` runs `new Application(Pimcore::getKernel())` on **every**
  `LocationAwareConfigRepository::saveConfig()` / `deleteData()` and the other config writes, inside
  HTTP requests and messenger handlers. After N saves in one process:
  - every console command run in that process (each further `messenger:stop-workers`) executes N copies of the
    maintenance check, the `wait_timeout` query and the `--prefix` handling;
  - `CONSOLE_INIT` listeners (plugins that register commands) run again each time;
  - the closures keep each `Application` and kernel reference alive, so memory leaks in workers;
  - `setlocale(LC_ALL, 'en…')` (`:58`) changes the locale of the **PHP-FPM / worker process** for all later requests.
    This affects `strtoupper()` / `ctype_*` / `strftime`-style code and anything else that relies on a locale set by
    the project.

**Fix direction:**
- Move the listeners into one event subscriber service (registered once by the container), and keep the
  constructor free of side effects.
- Call `setlocale()` only in `bin/console` / CLI boot.
- In `StopMessengerWorkersTrait` call the `messenger:stop-workers` logic directly: inject the
  `cache.messenger.restart_workers_signal` pool and set the restart timestamp, which is what `StopWorkersCommand` does.
  No new `Application` is then needed.

---

## [ ] B-28 – `addCommand()` crashes for invokable commands (Symfony 7.4) and can collide on `--prefix`

**File:** `lib/Console/Application.php:126-150`

- Symfony 7.4 `Application::addCommand(callable|Command $command)` accepts invokable commands (a callable that is
  wrapped into a `Command`). Pimcore's override keeps the `callable|Command` signature, but then calls
  `isDoctrineCommand(Command $command)` (`:142`, typed `Command`). Any callable gives a `TypeError` before
  `parent::addCommand()` can wrap it.
- `$definition->addOption(new InputOption('prefix', …))` (`:146`) has no `hasOption('prefix')` check. A `doctrine:*`
  command (DoctrineBundle, a third-party bundle, a future DoctrineMigrations version) that already defines `--prefix`
  makes the whole console fail with `LogicException: An option named "prefix" already exists`.
- The option is added to **every** `doctrine:*` command (`doctrine:database:create`, `doctrine:query:sql`, …), but it
  only means something for migrations.

**Fix direction:**
- `if (!$command instanceof Command) { return parent::addCommand($command); }` (the parent wraps it, and the doctrine
  check is then done on the returned command).
- Guard the option with `hasOption()`.
- Limit it to `Doctrine\Migrations\Tools\Console\Command\DoctrineCommand`.

---

## [ ] B-29 – Maintenance check blocks `list`, `help` and shell completion

**File:** `lib/Console/Application.php:69-75`

- The `COMMAND` listener throws for every command while maintenance mode is on, including `list`, `help`, `_complete`
  and `completion`. During a deployment (the typical maintenance window) `bin/console` without arguments and shell tab
  completion fail with *"In maintenance mode – set the flag --ignore-maintenance-mode …"*.
- The check also connects to the DB (`MaintenanceModeHelper::isActive()`) for every command, including ones that must
  work without a database (`cache:clear`, `list`, `completion`). The connection error is swallowed (`isActive()`
  returns `false`), so on a broken DB the maintenance check is skipped silently.
- `pimcore:maintenance-mode --disable` cannot run either (CoreBundle C-03, not repeated).

**Fix direction:** keep an allowlist (`list`, `help`, `_complete`, `completion`, `pimcore:maintenance-mode`) or a
command attribute / interface (`MaintenanceModeAwareInterface`) that opts out.

---

## [ ] B-30 – `--time-limit` `wait_timeout` adjustment is lost after the first reconnect

**File:** `lib/Console/Application.php:77-84`; `lib/Messenger/Middleware/CollectGarbageMiddleware.php:29`;
`lib/Helper/LongRunningHelper.php::cleanupDoctrine()`

- `SET SESSION wait_timeout` changes only the current connection. `CollectGarbageMiddleware` →
  `Pimcore::collectGarbage()` → `LongRunningHelper::cleanupDoctrine()` closes the connection after each message, and
  the reconnect uses the server default again. The setting therefore only covers the time until the first handled
  message, and the comment's promise (*"working database connection over the entire lifetime"*) does not hold.
- Only the default connection is changed. Other DBAL connections used by handlers keep the server default.
- `fetchAssociative()` returns `false` when the variable is not visible (ProxySQL, MaxScale, managed MySQL with
  restricted `SHOW VARIABLES`). `false['Value']` gives `null`, and `null < $timeLimit` is `true`, so `SET SESSION` is
  sent anyway, and its error aborts the worker start.
- Values above the server maximum (31536000 on Linux) make the `SET` fail.

**Fix direction:**
- Use a DBAL middleware / `postConnect` hook that sets the session variable on **every** connect, for all Pimcore
  connections.
- Clamp the value, and log instead of failing when the variable cannot be read.

---

## [ ] B-31 – `ConsoleOutputDecorator`: `section()` bypasses the decorated output; settings are not applied to the error output

**File:** `lib/Console/ConsoleOutputDecorator.php:53-96`, `:118-124`; `bundles/InstallBundle/src/Command/InstallCommand.php:401`

- `section()` opens a new `php://stdout` handle for each call (`:121`) and writes the section there, not to the
  decorated `$output`. The class exists to buffer installer output, but progress bars and sections from code that calls
  `$output->section()` (e.g. `ProgressBar` with a `ConsoleOutputInterface`) go straight to stdout and are missing from
  the buffer.
- `$sections = []` is a fresh local array each time (`:120`). `ConsoleSectionOutput` relies on a shared, by-reference
  list of all sections to compute line heights, so `clear()` / `overwrite()` erase the wrong lines. The file handle is
  never closed.
- `setVerbosity()`, `setDecorated()` and `setFormatter()` (`:53-96`) only change `$output`. Symfony's own
  `ConsoleOutput` passes them on to the error output as well. With the decorator, `-q` does not silence errors, and
  `--no-ansi` leaves colour codes in the error buffer.

**Fix direction:**
- Keep one `private array $sections = []` property, and build sections on the decorated stream (`StreamOutput`
  stream), or throw `LogicException` when the inner output has no stream.
- Apply the three setters to both outputs.

---

## [ ] B-32 – `AbstractCommand::writeError()` writes errors to stdout, and `-q` hides them

**File:** `lib/Console/AbstractCommand.php:82-100`

- `writeError()` uses `$this->output->writeln()`. For a `ConsoleOutput` that is stdout, not stderr, so cron / CI
  setups that capture stderr (or pipe stdout to a file / to `jq`) do not see the errors, and the JSON / CSV output of a
  command is mixed with error lines.
- With `-q` / `--quiet` `writeln()` writes nothing, so errors disappear completely. Symfony 7.2 added `--silent` and
  changed `--quiet` so that errors written through the error output stay visible (verify against the 7.4 vendor tree).
- CoreBundle I-29 proposes deprecating these helpers (not repeated). Until then they should at least use the error
  output.

**Fix direction:** `($this->output instanceof ConsoleOutputInterface ? $this->output->getErrorOutput() : $this->output)`
for `writeError()`, or delegate to `$this->io->getErrorStyle()->error()`.

---

## [ ] B-33 – Parallelization lock: 24 h TTL without refresh, one lock per command name

**File:** `lib/Console/Traits/Parallelization.php:81-103`, `:50-56`, `:68-71`

- `createLock($this->getName(), 86400)` (`:83`) is released only in `runAfterLastCommand()`. If the process dies
  without destructors (fatal error, OOM, SIGKILL), the release does not happen. With a persistent store (DBAL, Redis,
  the default `lock.factory` of most Pimcore setups) the command then reports *"The command is already running."* for
  up to **24 hours**.
- The lock is never refreshed. A run longer than 24 h (full thumbnail generation of a big asset tree) loses the lock in
  the middle, and a second run can start in parallel.
- The key is only the command name. `pimcore:thumbnails:image --thumbnails=a` blocks
  `pimcore:thumbnails:image --thumbnails=b`, even though they do not conflict.
- The fallback `ParallelizationBase::execute()` (`ParallelizationBase.php:40-63`) has no `try/finally`, so an
  exception in one item skips `runAfterLastCommand()` and the release.
- `exit(1)` when locked (`:54`) is CoreBundle C-06 / B-26 (not repeated).

**Fix direction:**
- Use a short TTL (e.g. 300 s) and call `$lock->refresh()` in `runAfterBatch()` / per item.
- Release in `finally`, or in a `ConsoleEvents::TERMINATE` listener.
- Add an overridable `getLockKey(InputInterface)` that includes the relevant arguments.

---

## [ ] B-34 – Without `webmozarts/console-parallelization` (the production default) memory is never freed between items

**File:** `lib/Console/Traits/ParallelizationBase.php:37-70`; `lib/Console/Traits/Parallelization.php:58-66`;
`composer.json` (`webmozarts/console-parallelization` is only in `require-dev` / `suggest`)

- Production installs (`composer install --no-dev`) use the fallback trait. It calls `runAfterBatch()` **once**, after
  all items (`:56-58`), instead of after each batch.
- `Parallelization::runAfterBatch()` only collects garbage when the command has a `processes` option (`:60`). The
  fallback `configureCommand()` adds no options (`:65-68`), so `hasOption('processes')` is `false` and
  `Pimcore::collectGarbage()` is **never** called.
- Result: `pimcore:thumbnails:image` / `pimcore:thumbnails:video` (and every other command that uses the trait) keep
  every loaded asset in `RuntimeCache` for the whole run, so memory grows with the number of items until the
  `memory_limit` is reached on large installations.
- The fallback also returns `0` even when items failed (CoreBundle I-29, not repeated).

**Fix direction:**
- In the fallback, process items in chunks (e.g. 50), call `runAfterBatch()` per chunk, and call
  `Pimcore::collectGarbage()` unconditionally there.
- Better: one sequential executor with the same hooks as the webmozarts one (I-27).

---

## [ ] B-35 – `Timeout`: calling `handleTimeout()` without `initTimeout*()` aborts immediately

**File:** `lib/Console/Traits/Timeout.php:82-100`, `:106-122`

- `setTimeout()` is public and sets only `$timeout`. If a caller uses `setTimeout(10)` + `handleTimeout()` without
  `initTimeoutInMinutes()`, `$this->startTime` is `null`. `time() - null` is then the full Unix time (about 29 million
  minutes), so the very first call throws *"Timeout … has been reach"*.
- `time()` is wall-clock time. An NTP correction or a DST-unaware container clock change shortens or lengthens the
  timeout. Use `hrtime(true)`.
- The message has typos (*"has been reach"*, *"exeption"* in the comment), and the default throws a plain `\Exception`
  that callers cannot catch on its own.
- The trait is not used anywhere in core (see I-28).

**Fix direction:** set `$startTime` in `setTimeout()` when it is `null`, use `hrtime()`, and throw a dedicated
`TimeoutReachedException`.

---

## Controller

## [ ] B-36 – `checkPermissionsHasOneOf()` crashes with `Error` (HTTP 500 instead of 403) when no user is logged in

**File:** `lib/Controller/UserAwareController.php:83-106`; caller `bundles/CustomReportsBundle/src/Controller/Reports/CustomReportController.php:145`

- The loop calls `$this->getPimcoreUser()->isAllowed($permission)` (`:88`) **before** the null check at `:95`. Without
  a Pimcore user (expired admin session, a firewall that lets the request through, a call from a frontend route) this
  is *"Call to a member function isAllowed() on null"* → HTTP 500 and an error log entry instead of a clean 403. The
  log call (`:99`) has the same problem (`->getName()` without `?->`).
- With an empty `$permissions` array, `$permission` stays `null`. The message becomes *"Attempt to access , but has no
  permission to do so."* and the log context has `permission: null`.
- The 403 message names one permission, the last one checked, although the user lacks all of them. It also differs
  from `checkPermission()`, which uses `createAccessDeniedHttpException()` with a generic message.

**Fix direction:** resolve the user once (`$user = $this->getPimcoreUser()`). If `$user === null` or `$permissions
=== []`, deny right away. Use `array_any($permissions, $user->isAllowed(...))` (PHP 8.4) and
`createAccessDeniedHttpException()`, and log the whole list.

---

## [ ] B-37 – `DocumentValueResolver` only matches the exact type `Document`: `Document\Page $document` is not resolved

**File:** `lib/Controller/ArgumentValueResolver/DocumentValueResolver.php:40-55`

- `$argument->getType() !== Document::class` (`:42`) compares class names as strings. An action declared as
  `public function defaultAction(Document\Page $document)` (or `Snippet`, `Email`, `Link`, a custom document class)
  gets `[]`. Symfony then reports *"Controller … requires the "$document" argument that could not be resolved"*,
  although the current document is a page.
- Typing the action to the concrete document class is the natural way to get `getEditable()` / `getTitle()` without
  `instanceof` checks, and it is what static analysis asks for.
- The resolver does not check that the resolved document matches the declared subtype either. With `is_a()` added, a
  snippet passed to a `Page`-typed argument would give a `TypeError`. Return `[]` in that case, so Symfony's
  "could not be resolved" message stays meaningful.

**Fix direction:** `is_a($argument->getType() ?? '', Document::class, true)`, and return the document only if
`$document instanceof $type`. Optionally drop the `$document` name requirement (`:46`) when the type is a document
subclass.

---

## [ ] B-38 – `FrontendController` magic properties: `isset()` / `empty()` / `??` are always "not set"

**File:** `lib/Controller/FrontendController.php:30-33`, `:56-80`, `:86-93`

- `$this->document` and `$this->editmode` only exist through `__get()`. There is no `__isset()`, so:
  - `isset($this->document)` is always `false`;
  - `$this->document ?? $fallback` always returns `$fallback`;
  - `empty($this->editmode)` is always `true`, so `if (!empty($this->editmode))` never runs.
  Code that looks correct and passes static analysis (the `@property` tags at `:30-33` tell PHPStan the properties
  exist) silently takes the wrong branch.
- `__set()` (`:69-80`) throws for **any** undeclared property, not only `document` / `editmode`. This includes PHP's
  own writes during unserialization and test doubles. That is intended as a guard, but the message is the same as for
  a typo.
- `addResponseHeader()` (`:86-93`) passes `getCurrentRequest()` to a `Request` parameter. Without a current request
  (CLI rendering, a controller called from a message handler) this is a `TypeError`.

**Fix direction:** add `__isset(string $name): bool` that returns `true` for `document` (when it resolves) and
`editmode`. Throw a clear `LogicException` when there is no request. Long term, deprecate the magic properties in
favour of the argument value resolvers (I-30).

---

## [ ] B-39 – `Controller::render()` with the delegating engine skips Symfony's form handling; `renderBlock()` is not delegated

**File:** `lib/Controller/Controller.php:27-72`

- The delegate path (`:30-39`) renders the template and sets the content, but skips what
  `AbstractController::render()` does in Symfony 7.4. If one of the parameters is a submitted, invalid `FormInterface`,
  Symfony sets status **422** and passes forms through `createView()`. Pimcore always returns 200, so Turbo / htmx /
  JS form handlers that rely on 422 treat invalid submissions as success.
- `renderBlock()` / `renderBlockView()` (Symfony ≥ 6.4) are not overridden, so they bypass the delegating engine. In a
  Pimcore controller, `render()` and `renderBlock()` can use different Twig environments (sandbox, Pimcore
  extensions).
- `'pimcore.templating'` is subscribed as optional (`'?'`, `:80`). If the service is not available,
  `$this->container->get()` throws `ServiceNotFoundException` (`:29`, `:46`, `:66`), where the optional marker
  suggests a fallback to `parent::render()`.

**Fix direction:** in the delegate path, call the parent's form handling (move it into a protected helper that runs
`createView()` and sets 422 on invalid submitted forms), and override `renderBlock*()` the same way. Make the service
required, or check `$this->container->has('pimcore.templating')` and fall back to the parent.

---

## [ ] B-40 – `ControllerDataProvider::getControllerReferences()` lists the wrong methods and can fatal the admin

**File:** `lib/Controller/Config/ControllerDataProvider.php:82-136`;
`bundles/CoreBundle/src/DependencyInjection/Compiler/ServiceControllersPass.php:41-47`

- `getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC)` (`:93`, `:122`) is an **OR** filter. It
  returns all public methods **and** all static methods, including `private static` / `protected static` ones. Static
  `*Action` helpers show up as selectable controllers, and selecting one gives *"Controller … is not callable"* on the
  document.
- `/^(.*)Action$/` (`:94`, `:123`) also matches non-actions (`setAction()`, `getDefaultAction()`, `handleAction()` in
  traits). Since Symfony 4, actions need no `Action` suffix, so attribute-routed and invokable (`__invoke`) controllers
  are missing from the list.
- `class_exists($fullClassName)` (`:119`) autoloads **every** `*Controller.php` of every non-core bundle in the admin
  request. A file whose dependencies are missing (an optional integration, a dev-only controller) fatals with a
  class-not-found error inside the autoload, and the document settings panel fails with 500.
- `str_replace(['.php', '/'], …)` (`:116`) also removes `.php` in the middle of a path, and keeps `\` on Windows.
- `ServiceControllersPass` stores `$definition->getClass()`, which is `null` for services whose class is the id or comes
  from a factory or a parent definition. `isValidNamespace(null)` (`:88`) is a `TypeError` under `strict_types`.

**Fix direction:**
- Filter with `$method->isPublic() && !$method->isStatic() && !$method->isAbstract()`, plus a declaring-class check
  that excludes the base controllers.
- List actions from the router (`RouteCollection` controllers) and from `#[Route]` attributes, in addition to the
  suffix rule.
- Wrap the reflection in `try { … } catch (\Throwable)` per class and log failures.
- Resolve null classes in the pass (`$container->findDefinition($id)->getClass() ?? $id`).

---

## [ ] B-41 – `ControllerDataProvider::getTemplates()` misses configured Twig paths and the modern bundle layout

**File:** `lib/Controller/Config/ControllerDataProvider.php:142-191`

- Only `PIMCORE_PROJECT_ROOT . '/templates'` is scanned (`:150`). `twig.default_path` and the extra `twig.paths`
  namespaces from the project config are ignored, so templates there cannot be selected in the document / static-route
  settings.
- `Resources/views` **or** `templates` per bundle (`:155`): a bundle that has both (during a migration to the modern
  layout) only shows the legacy folder.
- The result has no order. Finder order depends on the file system, so the dropdown order changes between servers.

**Fix direction:** read the paths and namespaces from the Twig loader (`twig.loader.native_filesystem` →
`getNamespaces()` / `getPaths($namespace)`), scan each, and `sort()` the result.

---

## [ ] B-42 – `ElementEditLockHelperTrait::getEditLockResponse()` crashes when the lock disappeared

**File:** `lib/Controller/Traits/ElementEditLockHelperTrait.php:29-46`

- `Editlock::getByElement()` returns `?Editlock`. If the lock was released or expired between the caller's `isLocked()`
  check and this call (another tab closed the element, or the cleanup task ran), `$editLock->getUserId()` (`:32`) is a
  call on `null` → HTTP 500 instead of "not locked".
- The trait calls `$this->adminJson()` (`:43`), which is not defined in core (it comes from the admin bundle). A class
  that uses the trait without it fails at runtime only.

**Fix direction:** return a "no lock" response (or let the caller re-check) when `null`. Declare
`abstract protected function adminJson(mixed $data): JsonResponse;` in the trait, or use `JsonHelperTrait::jsonResponse()`.

---

## DataObject

## [ ] B-43 – Encrypted field inside a block: date / datetime delegates are lost after save; array-shaped delegates are stored as `"Array"`

**File:** `lib/DataObject/BlockDataMarshaller/EncryptedField.php:48-82`, `:90-115`;
`lib/DataObject/BlockDataMarshaller/Date.php:28-47`; `models/DataObject/ClassDefinition/Data/Block.php:128-140`, `:197-203`

- Save path in `Block`: `normalize()` → block marshaller `marshal()` → `Serialize::serialize()`. For an encrypted field
  `EncryptedField::marshal()` first runs the **delegate's** block marshaller and then `encrypt()`, which does
  `Crypto::encrypt((string)$data, …)` (`:111`).
- Delegate `date` / `datetime`: `Date::marshal()` turns the timestamp into a `Carbon` object, and `(string)$carbon` is
  `"Y-m-d H:i:s"` in the server's timezone. On load, `decrypt()` returns that string, and `Date::unmarshal()` accepts
  only `Carbon` instances (`Date.php:42`), so it returns `null`. The encrypted date in the block is empty after the
  next load, and the next save overwrites the stored value with `null`. The data is lost without any error.
- Any delegate whose marshalled or normalized value is an **array** and that does not implement
  `BeforeEncryptionMarshallerInterface` gives `(string)[]` → `"Array"` + warning (Geo and RgbaColor implement the
  interface; check the other delegate types allowed for encrypted fields).
- Verify by reproduction: class with a block containing an `encryptedField` with delegate `date`, save, reload.

**Fix direction:** serialize the delegate's marshalled value in a format-stable way before encrypting
(`json_encode`, or `Serialize::serialize()` like `AbstractGeo::marshalBeforeEncryption()`), and decode it after
decryption, for **all** delegates. Then `Date::unmarshal()` gets back what `Date::marshal()` produced. Throw on
non-scalar data instead of casting.

---

## [ ] B-44 – Classification-store `EncryptedField`: `marshal()` and `unmarshal()` branch on different conditions → `TypeError` on save and lost values

**File:** `lib/DataObject/ClassificationstoreDataMarshaller/EncryptedField.php:48-103`, `:111-136`;
`Multiselect.php`, `BooleanSelect.php`, `Table.php` (same folder)

- `marshal()` branches on `is_array($value)` (`:54`), `unmarshal()` on `supportsFielddefinition()` (`:87`). They do not
  match:
  - **single-value delegate marshallers** (`Multiselect`, `BooleanSelect`, `Table` return only `['value' => …]`):
    `$encodedData['value2']` is undefined → `encrypt(null)`. `encrypt()` is declared `: string` but returns its `null`
    input (`:113`, `:135`) → **`TypeError: Return value must be of type string, null returned`**, so saving an object
    with an encrypted multiselect / table key fails;
  - **scalar input with a supported marshaller** (`BooleanSelect`, normalized `true` / `false`): `marshal()` takes the
    `else` branch and encrypts `(string)true` = `"1"` / `(string)false` = `""`. On load `decrypt("")` returns `null`
    (`if ($data)`, `:145`), so `false` ("No") becomes `null`. `"1"` only works by accident through the loose `==` in
    `BooleanSelect::unmarshal()`;
  - **array input without a classification-store marshaller**: neither branch sets `$encryptedValue`, so
    `['value' => null, 'value2' => null]` is stored and the value is lost silently.
- The decrypt path treats the string `"0"` as empty (`if ($data)`), so an encrypted numeric `0` is read back as `null`.

**Fix direction:** one rule for both directions: "if a delegate marshaller exists, use it, then encrypt each non-null
part; else encrypt the scalar". Make `encrypt()` return `?string`, and use `$data !== null && $data !== ''` checks.
Add tests per delegate type (I-45).

---

## [ ] B-45 – Classification-store `Multiselect` marshaller joins values with `,`: options that contain a comma are split on load

**File:** `lib/DataObject/ClassificationstoreDataMarshaller/Multiselect.php:27-43`

- `implode(',', $value)` / `explode(',', …)`. A select-option value such as `"red, dark"` or `"1,5 l"` comes back as two
  values, which do not match any option. The admin then shows them as invalid, and the next save stores the broken
  values.
- `[]` and `null` are both stored as `''` and both read back as `null`.

**Fix direction:** store `json_encode($value)` (with a fallback reader for old comma-joined values), or validate in the
data type that option values for classification-store multiselects contain no comma.

---

## [ ] B-46 – Select-options enum: invalid case names pass the regex; duplicate values produce a fatal enum

**File:** `lib/DataObject/ClassBuilder/SelectOptionsEnumBuilder.php:109-142`, `:187-205`

- The pattern `/^[A-Z-a-z_][A-Za-z0-9_]*$/` (`:192`) has a stray `-` after the `A-Z` range, which PCRE treats as a
  literal hyphen. A configured name such as `-Foo` passes and generates `case -Foo = …` → syntax error, so the enum
  and every class using it fatal.
- `class` (any case) is accepted as a case name, but PHP reserves `Enum::class` → compile error.
- Duplicate case **names** are detected (`:116`), duplicate **values** are not. Two options with the same value
  generate `case A = 'x'; case B = 'x';` → *"Duplicate value in enum"* fatal.
- The checks only run during generation. `Config::save()` accepts the config, writes the YAML or settings-store entry,
  and then fails in `dumpPHPEnum()`, which leaves config and generated file out of sync.

**Fix direction:** `/^[A-Za-z_][A-Za-z0-9_]*$/`, reject `class` (case-insensitive), check value uniqueness, and run
all checks in a `validate()` called before the config is persisted.

---

## [ ] B-47 – Generated class files are not invalidated in OPcache: class changes take effect late or not at all

**File:** `lib/DataObject/ClassBuilder/PHPClassDumper.php:34-53`, `PHPFieldCollectionClassDumper.php:32-38`,
`PHPObjectBrickClassDumper.php:32-38`, `PHPObjectBrickContainerClassDumper.php:83-88`, `PHPSelectOptionsEnumDumper.php:32-38`;
compare `lib/File.php:53-54`

- All five dumpers write with `Filesystem::dumpFile()`, which does not call `opcache_invalidate()`.
  `Pimcore\File::putPhpFile()` does (`File.php:53-54`), but the dumpers do not use it.
- With `opcache.validate_timestamps=0`, the usual production setting, a class / brick / collection / enum change
  saved in the admin (or applied by `pimcore:deployment:classes-rebuild` in a running FPM pool) is invisible until FPM
  is reloaded. New fields have no getter (`Call to undefined method`), and removed fields still have one. Meanwhile
  the DB schema was already changed by the same save. With timestamps on, the change shows up only after
  `revalidate_freq`.
- `PHPClassDumper` checks `file_exists()` after `dumpFile()` (`:40`, `:48`). `dumpFile()` already throws
  `IOException` on failure, so the check is dead code, and the generic `Exception` hides the real cause.

**Fix direction:** one shared writer (I-40) that calls `dumpFile()` and then `opcache_invalidate($path, true)` when
the function exists. Remove the `file_exists()` checks.

---

## [ ] B-48 – Object-brick container: duplicate brick assignments generate a duplicate property (compile fatal)

**File:** `lib/DataObject/ClassBuilder/PHPObjectBrickContainerClassDumper.php:40-55`;
`ObjectBrickContainerClassBuilder.php:30-46`

- `$containerDefinition[$class][$field][] = $key` appends without de-duplication. If a brick definition lists the
  same class / field pair twice (the admin grid allows adding a row twice, imports and merges of definition files can
  produce duplicates; not verified in the admin UI), the container gets
  `protected …\Data\<Brick>|null $<brick> = null;` and `get<Brick>()` twice → *"Cannot redeclare"* compile fatal for
  every object of that class.
- `$brickGetters` (`ObjectBrickContainerClassBuilder.php:42`) then lists the brick twice as well.

**Fix direction:** `array_values(array_unique($brickKeys))` before building, and reject duplicates in
`Objectbrick\Definition::save()`.

---

## [ ] B-49 – Class field names `allLazyKeysMarkedAsLoaded` / `activeDispatchingEvents` generate a class that is a compile-time fatal

**File:** `lib/DataObject/ClassBuilder/FieldDefinitionPropertiesBuilder.php:30-35`; `models/DataObject/ClassDefinition/Data.php:85-97`;
`models/DataObject/Concrete.php` (`$allLazyKeysMarkedAsLoaded`), `lib/Event/Traits/RecursionBlockingEventDispatchHelperTrait.php`
(`$activeDispatchingEvents`)

- This is the class-level twin of models C-44 (field collections). The builder declares `protected $<field>;`
  **untyped**. PHP refuses to redeclare an inherited typed property without the same type.
- `FORBIDDEN_NAMES` covers most typed properties of `Concrete` / `AbstractObject` / `AbstractElement` / traits, but
  not `allLazyKeysMarkedAsLoaded` (Concrete) and `activeDispatchingEvents` (trait used by `AbstractElement`). Both are
  unlikely names, but nothing prevents them, and a custom `parentClass` can add more.
- Better than extending the list by hand: check the generated properties against the parent class with reflection
  before writing the file.

**Fix direction:** in `ClassDefinition::save()` (or the builder), reflect on the parent class (`Concrete` or the
configured `parentClass`) and reject field names that match an existing property case-insensitively. Same for
collections and bricks (models C-44).

---

## [ ] B-50 – Block `Localizedfields` marshaller: unknown child key → `Error` on save

**File:** `lib/DataObject/BlockDataMarshaller/Localizedfields.php:47-61`

- `unmarshal()` skips keys that are not in the class definition (`:82-84`), `marshal()` does not.
  `$childDefs[$key]` (`:51`) is then `null` (warning), and `$childDef->getFieldtype()` (`:53`) is a call on `null` →
  `Error`. This happens when a localized field was removed from the block definition while objects still hold
  values for it (the object is loaded, then saved), or when code sets a value for an unknown key.

**Fix direction:** `if (!isset($childDefs[$key])) { continue; }`, the same as in `unmarshal()`.

---

## [ ] B-51 – `Consent\Service::giveConsent()` / `revokeConsent()`: the note is saved before the object, without a transaction

**File:** `lib/DataObject/Consent/Service.php:84-120`

- `insertConsentNote()` saves the note (`:49`), then `$object->save()` (`:94`). If the save fails (validation,
  mandatory field, DB error, a listener throws), a *"Consent given"* note exists for an object whose consent field was
  never set. For GDPR audits the note history is the evidence, so it then records a consent that was not stored.
- The reverse failure (object saved, note insert failed) cannot happen today. But the object refers to the note id,
  so the order cannot simply be swapped.
- The note has no user and no request context (IP, user agent). The caller has to pass them through `$metaData`
  under keys it makes up.

**Fix direction:** wrap note + object save in `Db::get()->transactional(…)`. Optionally record the current user /
request data in documented, fixed metadata keys.

---

## [ ] B-52 – Marshaller robustness: undefined keys, unchecked `json_decode()`, `hexdec(null)`

**File:** `lib/DataObject/BlockDataMarshaller/Consent.php:30`, `Geobounds.php:31-32`, `:42-43`, `Geopolygon.php:31-33`;
`ClassificationstoreDataMarshaller/QuantityValue.php:41`, `QuantityValueRange.php:47`;
`FielddefinitionMarshaller/Traits/RgbaColorTrait.php:28-29`, `:43-48`

- `$value['consent']`, `$value['noteId']`, `$value['northEast']['latitude']`, `$value['value2']` are read without
  `??`. A partial value (bounds with only one corner, a consent without note, a quantity value stored before
  `value2` existed) gives warnings, and in debug an `ErrorException` → 500 on save or load.
- `Geobounds::unmarshal()` / `Geopolygon::unmarshal()` call `json_decode()` without checking the result. Corrupt JSON
  gives `null` corners instead of an error.
- `RgbaColorTrait::unmarshal()`: `hexdec($value['value2'])` with a missing alpha is `hexdec(null)` → deprecation
  (*"Passing null to parameter #1 … is deprecated"*), which becomes a `TypeError` in PHP 9. `sscanf()` on a short
  hex string returns `null` components. `marshal()` with values outside 0–255 writes 3-digit hex, so `value` no longer
  fits the `%02x%02x%02x` pattern.

**Fix direction:** `??` defaults per key, `json_decode(…, flags: JSON_THROW_ON_ERROR)` wrapped in a clear exception,
`hexdec($value['value2'] ?? 'ff')`, clamp RGBA components to 0–255 in `marshal()`.

---

## Db

## [ ] B-53 – `Helper::fetchPairs()` always returns `[]`: it checks the wrong `Result` class

**File:** `lib/Db/Helper.php:21`, `:55-66`

```php
use Doctrine\DBAL\Driver\Result;            // driver-level interface
$stmt = $db->executeQuery($sql, $params, $types);   // returns Doctrine\DBAL\Result (wrapper class)
if ($stmt instanceof Result) { … }          // never true
```

- `Connection::executeQuery()` returns `Doctrine\DBAL\Result`. In DBAL 3 (`composer.json`: `doctrine/dbal ^3.8`) that is
  a plain `class Result` that **wraps** a `Driver\Result` and does not implement it. This was checked against the DBAL
  3.x source in the local Composer cache. The `instanceof` is always `false`, so the loop never runs, and every call
  returns an empty array, without error.
- The bug has been there since the helper was introduced (`fbdd5f1ce0`, 2022). No caller exists in this repository,
  so core is not affected. But it is a public static helper (the replacement for the removed
  `ConnectionInterface::fetchPairs()`), so projects and bundles that migrated to it silently get empty key/value maps
  (option lists, id→name lookups).

**Fix direction:** remove the check and use DBAL's built-in `$db->fetchAllKeyValue($sql, $params, $types)` (DBAL ≥ 2.11
/ 3.x / 4.x). Keep `fetchPairs()` as a deprecated one-line wrapper.

---

## [ ] B-54 – `Helper::upsert()` (insert, then update on duplicate): deadlock-prone, and silently updates nothing when another unique key fired

**File:** `lib/Db/Helper.php:38-53`; callers include `objects`, `assets`, `documents`, `versions`, `properties`,
`tags_assignment`, `edit_lock`, `tmp_store`, `search_backend_data` (about 25 call sites)

- **Every update of an existing row starts with a failing `INSERT`.** On InnoDB a duplicate-key error on `INSERT`
  sets a **shared** lock on the duplicate index record (MySQL manual, *"Locks Set by Different SQL Statements"*). The
  following `UPDATE` needs an exclusive lock. Two transactions saving the same element (admin + messenger worker,
  two workers) both hold S and wait for X → deadlock. Element saves retry deadlocks, but each retry repeats the whole
  save. Under load this is a source of `Deadlock found when trying to get lock` in logs.
- The `UniqueConstraintViolationException` may come from a **different** unique index than `$keys` (e.g. `objects`
  has a primary key **and** a unique `(path, key)`). Then `UPDATE … WHERE id = :id` matches no row (new id) and
  `upsert()` returns `0`. No caller checks the return value, so the row is not stored, and nothing reports it.
- `$data[$key] ?? throw new LogicException('… not found in data')` (`:48`) also throws when the key **is** in `$data`
  with value `null`. The message is then wrong.
- Two round trips for every update of hot tables (`tmp_store`, `edit_lock`, `search_backend_data`).

**Fix direction:** MySQL / MariaDB `INSERT … ON DUPLICATE KEY UPDATE col = VALUES(col)` (MariaDB) / `AS new …
new.col` (MySQL 8.0.19+), built with quoted identifiers and bound parameters. It is atomic, takes one lock and makes
one round trip. If keeping the fallback: check `update()`'s affected rows and throw when it is `0`. Use
`array_key_exists()` for the key check.

---

## [ ] B-55 – `Helper::escapeLike()` does not escape the backslash itself

**File:** `lib/Db/Helper.php:112-115`

- `str_replace(['_', '%'], ['\\_', '\\%'], $like)` leaves `\` as is. In MySQL's default `LIKE`, `\` is the escape
  character, so user input ending in `\` escapes the wildcard the caller appends:
  `escapeLike('foo\\') . '%'` → `'foo\%'` matches the literal `foo%` instead of "starts with `foo\`". Input
  `a\_b` becomes `a\\_b`, where `\\` is a literal backslash and `_` is a wildcard again.
- With `sql_mode` `NO_BACKSLASH_ESCAPES` there is no default escape character, and `\_` / `\%` are not escapes at
  all. The escaped pattern then requires a literal backslash.

**Fix direction:** `str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $like)` (backslash first). Document that
callers should add `ESCAPE '\\'` (or offer a `likeExpression()` helper that does) to be independent of `sql_mode`.

---

## [ ] B-56 – `Helper::queryIgnoreError()` swallows every DB error without logging

**File:** `lib/Db/Helper.php:87-101`; caller `models/DataObject/Objectbrick/Definition/Dao.php:165`

- `catch (Exception $e)` catches everything: connection lost, lock wait timeout, deadlock, missing privileges, syntax
  errors in the generated DDL. Unless the exception class is listed in `$exclusions`, the method returns `null`, and
  no log line is written (`:97`).
- It is used for schema changes (`ALTER TABLE … DROP INDEX`). A failing DDL statement for any other reason than "index
  does not exist" (e.g. a metadata lock timeout during a deploy) leaves the schema in a state the code assumes it is
  not in, with no trace.
- `$exclusions` actually means "rethrow these". They are rethrown as `Pimcore\Model\Element\ValidationException`, an
  element-validation type, for a DB error.

**Fix direction:** catch only the expected cases (or take a list of *ignored* error codes / exception classes, e.g.
`[1091 => 'index does not exist']`), log everything else at warning level, and rethrow the original exception.
Rename the parameter.

---

## [ ] B-57 – `quoteInto()` turns `null` into `''`; `selectAndDeleteWhere()` quotes the id column in one statement only

**File:** `lib/Db/Helper.php:103-110`, `:68-85`

- `quoteInto($db, 'parentId = ?', null)` → `$db->quote(null)` → `''`, so the condition becomes `parentId = ''`, which
  never matches `NULL` and silently matches `0` / `''` under MySQL's loose comparison. Arrays (`IN (?)`) are not
  supported and fail in the driver's `quote()`.
- Every `?` in `$text` is replaced, including one inside a string literal of the surrounding SQL (`"note LIKE '%?%'
  AND id = ?"`).
- The declared return type is `array|string` (`:103`), but the method always returns `string`.
- `selectAndDeleteWhere()` quotes `$idColumn` in the `SELECT` (`:70`) but not in the `DELETE` (`:82`). `$table` is never
  quoted.

**Fix direction:** throw on `null` / array values (or emit `IS NULL` / `IN (…)`), set the return type to `string`,
and quote `$idColumn` / `$table` with `quoteIdentifier()` in both statements. Long term, see I-46 (deprecate string
building in favour of bound parameters).

---

## DependencyInjection

## [ ] B-58 – `CollectionServiceLocator::getIterator()` breaks the Symfony 7.1+ `ServiceCollectionInterface` contract (list keys instead of service ids)

**File:** `lib/DependencyInjection/CollectionServiceLocator.php:29-52`

- Since Symfony 7.1, `ServiceLocator` implements `ServiceCollectionInterface` (iterable + countable, `@return
  \Traversable<string, T>`). Its own `getIterator()` yields `$id => $service`. Verify against the 7.4 vendor tree
  (not available locally).
- The subclass overrides `getIterator()` with a generator **without keys** (`:47-52`), so iteration yields `0, 1, 2 …`
  instead of the service ids. Code that receives the locator as `ServiceCollectionInterface` / `ServiceLocator` and
  does `foreach ($locator as $id => $service)` or `iterator_to_array($locator)` gets integer keys, and
  `$locator->get($id)` with those keys fails.
- `implements IteratorAggregate` (`:29`) is redundant now. `$ids` (`:31`, `:35`) duplicates
  `getProvidedServices()`.
- Impact is low today: the class has no user in this repository. It is `@internal`, but bundles may use it.

**Fix direction:** delete the override, or deprecate the class entirely (I-49). The parent's iterator already keeps
the factory order and yields ids.

---

## Document

## [ ] B-59 – PDF-to-text temp file uses `uniqid()`: concurrent extractions collide, and the file leaks on failure

**File:** `lib/Document/Adapter/Ghostscript.php:254-271`

- `$textFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/pdf-text-extract-' . uniqid() . '.txt'`. `uniqid()` is time-based and
  not collision-safe: two workers extracting text at the same microsecond write to the same file, and one reads the
  other's output.
- If `Process::mustRun()` throws (timeout, gs error) between creating `$textFile` and `unlink($textFile)` (`:271`), the
  file is never removed. It is not registered in `$this->tmpFiles`, so `removeTmpFiles()` / `__destruct()` does not
  catch it either. Over time these accumulate in the system temp dir.

**Fix direction:** use `File::getLocalTempFilePath('txt')` (random, registered for cleanup) or `tempnam()`, and remove
it in a `finally`.

---

## [ ] B-60 – LibreOffice conversion: one shared log file for all conversions, kept on failure; all conversions serialized behind one blocking lock

**File:** `lib/Document/Adapter/LibreOffice.php:112-161` (`:115`, `:129`, `:135-151`)

- `$logFile = PIMCORE_LOG_DIRECTORY . '/libreoffice-pdf-convert.log'` (`:135`) is the same path for every conversion and
  is opened in append mode. Concurrent conversions (several workers) interleave their output in one file, and it is
  deleted only on success (`:151`), so after a failure the log that explains it is mixed with other runs, and the
  next successful conversion deletes it.
- `createLock('soffice')` + `acquire(true)` (`:115`, `:129`) blocks **every** conversion in the installation behind
  one lock (LibreOffice cannot share a profile directory, so serialization is intended). The default lock TTL is
  300 s while the process timeout is 240 s plus the wait time of queued conversions, so with a slow queue the lock can
  expire while a conversion is still running and a second `soffice` starts on the same profile.
- Note: the lock and the log handle are local variables (`autoRelease` lock, plain resource), so they **are** released
  when the method exits, also on exceptions. Only the explicit release order is implicit.

**Fix direction:** per-conversion log (or log through the Pimcore logger with the asset id), keep it on failure, and
use a per-process `-env:UserInstallation` profile directory so conversions can run in parallel without the global
lock; if the lock stays, set a TTL above the process timeout and `refresh()` it.

---

## [ ] B-61 – `StaticPageGenerator::getStoragePath()` crashes when a path segment is not a document

**File:** `lib/Document/StaticPageGenerator.php:58-74` (`:65-66`)

- With `documents.static_page_generator.use_main_domain = true`, the method walks each ancestor path segment:
  `$doc = Document::getByPath($pathString); $site = Site::getByRootId($doc->getId());`. `getByPath()` returns `null`
  when a segment does not resolve to a document (hardlink children, virtual / routed paths, a pretty URL whose
  directory has no matching document). `$doc->getId()` is then a call on `null` → `Error`.
- `getStoragePath()` is used by `generate()`, `remove()`, `pageExists()` and `getLastModified()`, so the whole
  static-page feature (generation **and** cleanup on publish/delete) can fail for such documents.

**Fix direction:** skip segments where `getByPath()` returns `null` (treat as a non-site directory), and guard the
`Site::getByRootId()` lookup. Add a test with a pretty URL and with a hardlink child.

---

## [ ] B-62 – `StaticPageGenerator::generate()`: the `services_resetter` reset is skipped when rendering fails

**File:** `lib/Document/StaticPageGenerator.php:82-120` (`:106-116`)

- On the CLI path the method resets all resettable services after a successful generation (`:114-117`). When
  `render()` / `$storage->write()` throws, the `catch` returns `false` (`:106-110`) before the reset. In a long CLI run
  that generates many pages, the state left by the failed render (request stack, locale, block state, runtime caches
  of resettable services) leaks into the next page.
- The lock is a local, auto-releasing lock, so it **is** released when the method returns (also on the failure path);
  only the explicit `release()` at `:115` is skipped.
- The exception is logged at `debug` level only (`:107`), so failed static pages are invisible in normal logs.

**Fix direction:** run release + reset in a `finally`; log failures at `warning`.

---

## [ ] B-63 – `DocumentRenderer::render()`: locale is not restored when rendering throws

**File:** `lib/Document/Renderer/DocumentRenderer.php:118-148`

- `$tempLocale` is saved (`:119`), the document locale is set (`:120-123`), and the original is restored only at
  `:132`, after `fragmentRenderer->render()`. If rendering throws, `LocaleService` (and the request locale) keep the
  document's locale for the rest of the process. In a worker or a batch (`EditableUsageResolver`, static-page
  generation, search reindex) this silently changes the locale of subsequent, unrelated work.
- The `finally` block (`:141-146`) only pops CLI requests; it does not restore the locale.

**Fix direction:** move the `setLocale($tempLocale)` into the `finally` so it runs on both success and failure.

---

## [ ] B-64 – `EditableUsageResolver`: the usage subscriber stays registered on the shared dispatcher when rendering throws

**File:** `lib/Document/Editable/EditableUsageResolver.php:43-73`

- `registerEventSubscriber()` adds a `UsageRecorderSubscriber` to the shared `event_dispatcher` (`:62-63`).
  `unregisterEventSubscriber()` runs only if `render()` returns normally (`:49-54`). If rendering throws, the
  subscriber is left attached, and `$this->subscriber` stays set.
- Consequences: a later `getUsedEditableNames()` reuses the still-registered subscriber (the `if (!$this->subscriber)`
  guard at `:61` skips re-adding, but the old one keeps accumulating names from **every** document rendered in the
  process via `EDITABLE_NAME`), and `array_unique($names)` returns names from unrelated renders. In a worker this
  leaks memory and corrupts the export's editable list.

**Fix direction:** wrap the render in `try { ... } finally { $this->unregisterEventSubscriber(); }`. Reset
`recordedEditableNames` on register.

---

## [ ] B-65 – `EditmodeEditableDefinitionCollector::getHtml()` inlines JSON into `<script>` without the HTML-safe JSON flags

**File:** `lib/Document/Editable/EditmodeEditableDefinitionCollector.php:103-119`

- `json_encode(..., JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)` is inlined into `<script> var editableDefinitions = …;
  </script>`. `json_encode()` escapes `/` by default, so a literal `</script>` becomes `<\/script>` and cannot close the
  tag. But `<!--` and `<script` are **not** escaped: in a script element they switch the HTML parser into the
  "script data escaped" states, where a later `</script>` inside the page can be parsed differently (the editor page
  fails to start for that document).
- Low impact (editmode only, values come from editable / brick configuration), but the fix is one flag set.

**Fix direction:** `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` (what Symfony's `JsonResponse` uses),
or a `<script type="application/json">` payload read by the editor JS.

---

## [ ] B-66 – `BlockStateStack::loadArray()` assumes well-formed input: missing keys crash, bad types corrupt the stack

**File:** `lib/Document/Editable/Block/BlockStateStack.php:93-110`; `BlockState.php:89-101`

- `loadArray()` reads `$blockStateData['blocks']` and `['indexes']` (`:100`, `:104`) and
  `$blockData['name']`/`['realName']` without `isset`/type checks. Malformed serialized block state (an older schema,
  a corrupted editable, a manually edited document) gives undefined-key warnings → `ErrorException` in debug, or a
  partially built stack in prod.
- `BlockState::pushIndex(int $index)` is typed, so a non-int index in the stored data throws `TypeError`.
- This data is produced by `jsonSerialize()` and round-tripped through requests/sub-requests, so a version skew
  between serialized state and code breaks rendering of any page with blocks.

**Fix direction:** validate the shape (`is_array`, `array_key_exists`), cast indexes, and throw a clear
`InvalidArgumentException` naming the bad entry, or skip malformed states with a logged warning.

---

## [ ] B-67 – `EditableHandler::renderAction()` pops the wrong request / leaves the stack unbalanced on CLI render failure

**File:** `lib/Document/Editable/EditableHandler.php:311-332` (`:325-328`)

- In the no-current-request branch, `requestStack->push($request)` (`:326`) is followed by `fragmentRenderer->render()`
  and `requestStack->pop()` (`:328`). If `render()` throws, `pop()` never runs, so the pushed request stays on the
  stack. Later code then sees the wrong "current request" (wrong locale, host, document), and the imbalance persists
  for the rest of a CLI / worker run (search reindex is the documented trigger, `:324`).

**Fix direction:** `try { return $this->fragmentRenderer->render(...); } finally { $this->requestStack->pop(); }`.

---

## [ ] B-68 – `EditableHandler::buildBrickTemplateReference()` relies on the loop to define `$templateReference` (latent)

**File:** `lib/Document/Editable/EditableHandler.php:278-309` (`:293-308`)

- `$templateReference` is assigned only inside `foreach (['areas', 'Areas'] …)` (`:294`) and returned after the loop
  (`:308`). With today's constant two-element array it is always defined, so there is **no current failure**; listed
  only because changing the folder list (or making it configurable) turns it into an undefined-variable warning.

**Fix direction:** initialize the variable before the loop and return an explicit "not found" reference.

---

## Element

No bug findings in Scope 10 (`lib/Element/MarshallerService.php`). Every marshaller key in
`bundles/CoreBundle/config/marshaller.yaml` was checked against the data types' `getFieldType()`; all but one map to an
existing type, and the extra one is dead configuration, not a runtime defect (I-59).

---

## Event

## [ ] B-69 – `RecursionBlockingEventDispatchHelperTrait`: the guard is not cleared when a listener throws (affects every model that uses it)

**File:** `lib/Event/Traits/RecursionBlockingEventDispatchHelperTrait.php:34-42`; used by `models/Element/AbstractElement.php`
(assets, documents, objects), `ClassDefinition`, `Fieldcollection\Definition`, `Objectbrick\Definition`, `CustomLayout`,
`Classificationstore\*Config`, `QuantityValue\Unit`, `User\AbstractUser`, `Tag`, `Site`, `Version`, `Translation`,
`WebsiteSetting`, `Notification`, `UrlSlug`, SeoBundle `Redirect`, SimpleBackendSearchBundle `Data` (~120 calls)

```php
$this->activeDispatchingEvents[$eventName] = true;
Pimcore::getEventDispatcher()->dispatch($event, $eventName);
unset($this->activeDispatchingEvents[$eventName]);      // skipped when a listener throws
```

- This is the root cause of models B-125 (reported there from the `Translation` side, not repeated). The impact is wider
  than translations: every element save dispatches `PRE_UPDATE` / `PRE_ADD` / `POST_*` through it
  (`Asset.php:504`, `:847`, `Document.php:296`, `AbstractObject.php:504`).
- A listener that throws, which is the **documented way** to veto a save (validation, permission checks,
  `ValidationException` in `PRE_UPDATE`), leaves the flag set on that instance. Every later `dispatchEvent()` with the
  same event name on the same instance is then skipped **silently**: no listener runs, and no error is raised.
- Elements come from `RuntimeCache`, so within one request / message / import loop the next `getById()` returns the
  **same** instance. A batch import that catches the validation error, fixes the value and saves again saves without
  `PRE_UPDATE` (validation, normalization, permission listeners) and without `POST_UPDATE` (search index, cache
  invalidation side effects, workflow / notification listeners).

**Fix direction:** `try { $dispatcher->dispatch(...); } finally { unset($this->activeDispatchingEvents[$eventName]); }`.
Also keep the guard out of serialized model data (models I-61). Add a regression test:
a throwing `PRE_UPDATE` listener, then a second save on the same instance still dispatches `PRE_UPDATE`.

---

## [ ] B-70 – `DocumentEvents::EDITABLE_RENDERLET_PRE_RENDER` is documented but never dispatched

**File:** `lib/Event/DocumentEvents.php:174-183`; `models/Document/Editable/Renderlet.php`

- No code in core (`lib`, `models`, `bundles`) dispatches `pimcore.document.editable.renderlet.pre_render`. Listeners
  written against the constant are never called, without any error.
- Its doc block is copied from a mail event (*"element | \Pimcore\Mail | the pimcore mail instance"*), so even the
  documented payload is wrong.

**Fix direction:** dispatch it from `Renderlet` before rendering (with a dedicated event class carrying the editable,
document and params), or deprecate the constant with a note.

---

## [ ] B-71 – `ResolveUploadTargetEvent`: the deprecated `context` BC layer only works in one direction

**File:** `lib/Event/Model/Asset/ResolveUploadTargetEvent.php`

- The constructor's `$context` (deprecated) is stored only in `$this->context`, not in `$this->arguments`. A listener
  that followed the deprecation message and switched to `getArgument('context')` gets *"Argument "context" not found"*
  (`InvalidArgumentException`) whenever the dispatcher still uses the constructor argument.
- `setContext()` also writes only `$this->context`, while `setArgument('context', …)` writes both. So whether the old
  and the new API see the same value depends on which one the **dispatcher** used.
- The deprecation messages contain a line break plus indentation inside the string literal, which shows up in logs.

**Fix direction:** in the constructor and `setContext()` also set `$this->arguments['context']`, and in `getContext()` read
from the arguments. Single-line deprecation messages.

---

## [ ] B-72 – Event value objects: getters on uninitialized typed properties, non-list path arrays

**File:** `lib/Event/Model/DataObjectImportEvent.php` (`$object`), `lib/Event/Traits/ResponseAwareTrait.php:32-41`,
`lib/Event/Traits/RequestAwareTrait.php:24-29`, `lib/Event/BundleManager/PathsEvent.php`

- `DataObjectImportEvent::getObject()` reads `protected Concrete $object`, which is only set by `setObject()`.
  `ResponseAwareTrait::getResponse()` reads `protected Response $response`, which is only set by `setResponse()`.
  Calling the getter first is *"must not be accessed before initialization"* (`Error`) instead of `null` or a clear
  exception. `hasResponse()` exists, `hasObject()` does not.
- `RequestAwareTrait` declares `protected Request $request` but no way to set it. Each user must assign it in its own
  constructor, otherwise `getRequest()` is the same `Error`.
- `PathsEvent::addPaths()` uses `array_unique()`, which keeps the original keys. After a duplicate, `getPaths()` is not
  a list (`@return string[]`). `json_encode()` then produces an object, and `$paths[0]`-style access can miss entries.

**Fix direction:** nullable properties with `null` defaults (and `has…()` checks), a constructor parameter or setter for
`$request`, and `array_values(array_unique(…))`.

---

## Exception

No bug findings in Scope 12 (`lib/Exception/ImageOptimizationFailedException.php` is an empty marker class). The only
related defect is in a consumer: `lib/Image/Optimizer.php:53` catches it and discards it without logging. That belongs
to the `lib/Image` scope and is listed there as B-111.

---

## Extension

## [ ] B-73 – `SettingsStoreAwareInstaller`: migration bookkeeping matches the bundle namespace as a plain string prefix and leaves the prefix set on shared services

**File:** `lib/Extension/Bundle/Installer/SettingsStoreAwareInstaller.php:73-117`;
`lib/Migrations/FilteredMigrationsRepository.php:55-79` (`:79`), `lib/Migrations/FilteredTableMetadataStorage.php:57-76` (`:76`)

- `markInstalled()` / `markUninstalled()` call `setPrefix($this->bundle->getNamespace())` and the filters use
  `str_starts_with(<class or version>, $prefix)` **without a trailing `\`**. A bundle in namespace `App\Bundle\Shop`
  therefore also matches `App\Bundle\ShopExtras\Migrations\Version…`. A bundle whose namespace is a short parent
  (`App`) matches **every** `App\…` migration of the project.
- `markUninstalled()` (`:101-117`) completes a `Direction::DOWN` result for **every** executed migration that passes
  the filter, i.e. it **deletes the executed-migration records** of those other bundles / of the project. The next
  `doctrine:migrations:migrate` then tries to run them again: duplicate DDL errors at best, re-applied data migrations
  (duplicate rows, overwritten values) at worst.
- `markInstalled()` (`:73-99`) marks the matching migrations of the other bundle as executed without running them.
- If `getLastMigrationVersionClassName()` names a class that is not in the filtered list (renamed / moved), the
  `break` at `:92-94` is never reached and **all** matching migrations, including ones added in later releases, are
  marked as executed, so they never run.
- The prefixes are set on the shared `FilteredMigrationsRepository` / `FilteredTableMetadataStorage` services and never
  reset. Later migration operations in the same process (the installer installs several bundles in one run, then
  runs migrations) see only the last bundle's migrations. `markUninstalled()` sets only the metadata-storage prefix,
  so repository and storage can disagree.
- No core bundle overrides `getLastMigrationVersionClassName()`, so this hits third-party / project bundles that use
  the documented feature.

**Fix direction:** match on `rtrim($namespace, '\\') . '\\'`. Reset both prefixes in a `finally`. Throw when the
configured last migration is not found. Only touch migrations from the bundle's own migrations namespace
(`$this->bundle->getNamespace() . '\\Migrations\\'`).

---

## [ ] B-74 – Bundle install / uninstall state is not written atomically, and a failed flag write is silent

**File:** `lib/Extension/Bundle/Installer/SettingsStoreAwareInstaller.php:73-129`; `models/Tool/SettingsStore.php::set()`

- `markUninstalled()` first sets the settings-store flag to `false` (`:103`), then deletes the migration records
  (`:105-116`). `markInstalled()` does the reverse order. A failure halfway (DB error, the B-73 not-found case after
  the fix) leaves "uninstalled" with migration records still present, or the other way round. Re-running the
  install / uninstall then misbehaves (`canBeInstalled()` / `canBeUninstalled()` are based on the flag only).
- `SettingsStore::set()` swallows DB errors (models B-238, not repeated), so the flag can stay unchanged while
  `install()` reports success.
- `markUninstalled()` stores `false` instead of deleting the key, but `bundles/InstallBundle/src/Installer.php:911-914`
  (`isBundleInstalled()`) tests `null !== SettingsStore::get(…)`. When the installer runs against an existing database,
  a bundle that was uninstalled earlier counts as installed and is skipped (`:577`). Fresh installs are not affected.
  `SettingsStoreAwareInstaller::isInstalled()` reads the value correctly (`(bool) getData()`), so the two disagree.

**Fix direction:** wrap flag and migration bookkeeping in one DB transaction (`$connection->transactional(…)`), and
check `SettingsStore::set()`'s return value (or fix B-238).

---

## [ ] B-75 – `PimcoreBundleLocator`: one broken `*Bundle.php` breaks bundle discovery; an empty search-path list throws

**File:** `lib/Extension/Bundle/PimcoreBundleLocator.php:46-58`, `:79-97`, `:137-153`;
`bundles/CoreBundle/config/pimcore/default.yaml:175-177`

- Every `*Bundle.php` file under the search paths (default `src`, `vendor/pimcore/pimcore/bundles`) and every
  `pimcore-bundle` package without `extra.pimcore.bundles` is loaded with `class_exists()` (`:143`). A file whose
  dependencies are missing (an optional bundle, a dev-only bundle in `src`, a test fixture named `*Bundle.php`) fatals
  in the autoloader. `pimcore:bundle:list`, the bundle manager and the installer then fail for **all** bundles.
- `Finder::in([])` followed by iteration throws `LogicException: You must call one of in() or append() methods …`.
  `setPaths()` drops non-existing paths (`:55`), so a project that overrides `pimcore.bundles.search_paths` with paths
  that do not exist (or `[]`) and has no composer paths gets that exception from `findBundles()`.
- The Finder has no `->files()` and no excludes, so it also walks `node_modules`, `tests`, `var` etc. below `src`.
- Package paths are built as `PIMCORE_COMPOSER_PATH . '/' . name` (`:125`), already listed as Composer B-15 (not
  repeated).

**Fix direction:** skip the Finder when the path list is empty. Add `->files()->exclude(['node_modules', 'tests',
'var', 'vendor'])`. Wrap the reflection per class in `try { … } catch (\Throwable $e)` and log the class name.

---

## [ ] B-76 – `AbstractPimcoreBundle::isInstalled()` throws instead of returning `false` when the bundle is not active

**File:** `lib/Extension/Bundle/AbstractPimcoreBundle.php` (`isInstalled()`); `lib/Extension/Bundle/PimcoreBundleManager.php:94-103`

- `getActiveBundle(static::class, false)` throws `BundleNotFoundException` when the bundle is not registered in the
  current kernel. Examples: a bundle enabled only for some environments (`config/bundles.php` `['dev' => true]`),
  code in another bundle that asks "is X installed?", or a subclassed bundle (the identifier is the runtime class).
  The answer should be `false`.
- It calls `Pimcore::getContainer()` statically, which is `null` before the kernel is booted (container compilation,
  early bootstrap code) → `Error`.
- Each call iterates all kernel bundles and queries the settings store (SeoBundle I-16 covers the per-request cost,
  not repeated).

**Fix direction:** return `false` when the bundle is not active (`$bundleManager->exists(static::class)` check or catch
`BundleNotFoundException`), and guard the container access.

---

## [ ] B-77 – `AbstractAreabrick`: uninitialized id and renderer give `TypeError` / `Error` outside the compiler-pass path

**File:** `lib/Extension/Document/Areabrick/AbstractAreabrick.php:29-60`, `:98-116`

- `getId(): string` returns `?string $this->id`. Before `setId()` (a brick instantiated by hand, in a test, or via
  `AreabrickManager::register()` before the id is set) this is a `TypeError`.
- `$editableRenderer` is set only by the `setEditableRenderer()` method call that `AreabrickPass` adds
  (`AreabrickPass.php:154-155`). A brick registered with `register()` at runtime, or a service brick whose definition
  was built without the pass, gets *"must not be accessed before initialization"* in `getDocumentEditable()`.
- `getHtmlTagOpen()` concatenates `getOpenTagCssClass()` (`?string`) and leaves a trailing space in the `class`
  attribute when it returns `null`.

**Fix direction:** throw a clear `LogicException` when the id or renderer is missing, and build the class list with
`implode(' ', array_filter([...]))`.

---

## Helper

## [ ] B-78 – `LongRunningHelper::removePimcoreRuntimeCacheProtectedItems()` ignores its argument and removes every protected item

**File:** `lib/Helper/LongRunningHelper.php:113-121`

```php
foreach ($this->pimcoreRuntimeCacheProtectedItems as $item) {               // iterates the protected list,
    $key = array_search($item, $this->pimcoreRuntimeCacheProtectedItems);    // not $items
    if ($key !== false) { unset($this->pimcoreRuntimeCacheProtectedItems[$key]); }
}
```

- `$items` is never read. Every item finds itself, so **any** call (`remove(['foo'])`) empties the whole list.
- The next `Pimcore::collectGarbage()` / `cleanUp()` then also drops `Config_system`, `Config_website`,
  `pimcore_admin_user`, `pimcore_site`, `pimcore_error_document` and `Pimcore_Db` from `RuntimeCache`. Long CLI
  imports that rely on them (admin user context for permission checks, current site) lose them, and the configs are
  reloaded on every iteration.
- No core caller exists; it is public API for projects.

**Fix direction:** `$this->pimcoreRuntimeCacheProtectedItems = array_values(array_diff($this->pimcoreRuntimeCacheProtectedItems, $items));`

---

## [ ] B-79 – Temp files registered with `LongRunningHelper` are not deleted by `cleanUp()`: workers accumulate files and shutdown closures

**File:** `lib/Helper/LongRunningHelper.php:150-165`, `:62-68`; `lib/File.php:78-98`;
`lib/Messenger/Middleware/CollectGarbageMiddleware.php:29`

- `addTmpFilePath()` says *"will be deleted on next call of cleanUp()"*, but `cleanUp()` does not call
  `deleteTemporaryFiles()`. Files are removed only where code calls `Pimcore::deleteTemporaryFiles()` explicitly
  (`AssetUpdateTasksHandler`, `LowQualityImagePreviewTask`, the search reindex command), or at process exit.
- `File::getLocalTempFilePath()` (non-`keep`) also registers **one `register_shutdown_function()` closure per temp file**.
- In a messenger worker that handles other messages (thumbnail generation, document previews, video, PDF text
  extraction, imports), `CollectGarbageMiddleware` → `collectGarbage()` → `cleanUp()` runs after each message but
  deletes nothing. Temp files pile up in `PIMCORE_SYSTEM_TEMP_DIRECTORY` (full disk), and the shutdown-function list
  grows without bound (memory).

**Fix direction:** call `deleteTemporaryFiles()` from `cleanUp()`. Register one shutdown function in
`LongRunningHelper` (or rely on `kernel.reset`) instead of one closure per file.

---

## [ ] B-80 – `SystemConfig::getConfigDataByKey()` reports `writeable` without the key / data source

**File:** `lib/Helper/SystemConfig.php:27-38`; caller `lib/SystemSettingsConfig.php:72`

- `$config['writeable'] = $repository->isWriteable()` asks only "is the write target writeable", with a random key
  and no data source (see `LocationAwareConfigRepository::isWriteable()`, Config scope). System settings that come
  from a YAML file outside `var/config` (e.g. `config/pimcore/system.yaml`), or from a different storage than the
  write target, are reported as writeable. The admin form is then editable, and the save either fails or writes a
  copy that the read target never uses (B-17 / B-18).
- `$configKey[0]` is the loaded data, but the `[data, dataSource]` tuple's data source (`[1]`) is thrown away. It is
  exactly what `isWriteable()` needs.

**Fix direction:** `[$data, $source] = $repository->loadConfigByKey($key); $data['writeable'] = $repository->isWriteable($key, $source);`

---

## [ ] B-81 – `Mail::setAbsolutePaths()`: the "skip absolute URLs" check is a character class; many relative links are never rewritten

**File:** `lib/Helper/Mail.php:204-270` (`:223`, `:231`, `:245`, `:248-267`)

```php
"@(href|src)\s*=[\"']([^(http|mailto|javascript|data:|#)].*?(css|jpe?g|gif|png)?)[\"']@is"
```

- `[^(http|mailto|javascript|data:|#)]` is a negated **character class** (`(`, `h`, `t`, `p`, `|`, `m`, `a`, `i`, `l`,
  `o`, `j`, `v`, `s`, `c`, `r`, `d`, `:`, `#`, `)`), not "does not start with these schemes". Relative links whose
  first character is one of those letters (`images/…`, `assets/…`, `css/…`, `static/…`, `js/…`, `media/…`,
  `docs/…`) are not matched, so they stay relative in the sent mail and are broken in every mail client.
  Root-relative paths (`/…`) work, because `/` is not in the class.
- `'@^' . $replacePrefix . '(/(.*))?$@'` (`:231`): the site root path is not `preg_quote()`d, so a root path with `+`,
  `(`, `.` changes the pattern.
- The replacement `'\\1' . $absolutePath . '\\2'` (`:245`): `$absolutePath` is not escaped for `preg_replace()`, so a
  URL containing `$1` or `\1` (query strings) is mangled.
- `srcset` (`:248-267`): relative candidates get `$hostUrl . $v` without a `/`; `data:` URIs are not skipped;
  `explode(',')` splits `data:` URIs that contain commas.

**Fix direction:** parse with an HTML parser (`DOMDocument` / Masterminds HTML5) and resolve each `href` / `src` /
`srcset` candidate with a URI resolver against the document URL. As a minimal fix:
`(?!(?:https?:|mailto:|javascript:|data:|#|//))`, `preg_quote()`, and a `preg_replace_callback()` for the replacement.

---

## [ ] B-82 – `Mail::parseEmailAddressField()` splits on commas inside quoted display names

**File:** `lib/Helper/Mail.php:371-395`

- `preg_split('/,|;/', …)` splits `"Doe, John" <john@example.com>` into `"Doe` and `John" <john@example.com>`. The first
  part has no `<…>`, so `"Doe` is taken as an **e-mail address**. Building `Symfony\Component\Mime\Address` from it
  throws `RfcComplianceException`, so the whole mail (document e-mail "To", newsletter / notification recipients from
  the admin) fails.
- Quotes around names are kept (`"John"`), and the `(comment)` form is parsed as `address (name)`, but
  `Name (comment) <address>` mixes both.

**Fix direction:** parse with `Symfony\Component\Mime\Address::create()` per mailbox, after splitting with a
quote-aware splitter (or use `egulias/email-validator`'s / Symfony Mime's header parser). Strip the surrounding quotes
from names.

---

## [ ] B-83 – `Mail::embedAndModifyCss()` fetches any absolute stylesheet URL server-side, and breaks `data:` URLs in CSS

**File:** `lib/Helper/Mail.php:275-342` (`:295-299`, `:321-342`, `:351`)

- Every `<link href="http…">` in the rendered mail HTML is downloaded with `Tool::getHttpData($path)` (`:296`), from any
  host, at send time. Users who can edit e-mail documents (or templates that render user-supplied values into a
  `<link>`) can make the server request arbitrary URLs, including internal network addresses. There is also no
  timeout, so a slow host blocks the send.
- `normalizeCssContent()` rewrites every `url(…)`: `url(data:image/png;base64,…)` becomes
  `<dirname>/data:image/png…`, which breaks inline images. An empty `url()` gives `$path[0]` on an empty string
  (warning → `ErrorException` in debug). Root-relative URLs in CSS loaded from **another** host get the Pimcore host.
- `getNormalizedFileInfo()` has the same `$path[0]` on an empty `href` (`:351`).

**Fix direction:** only inline stylesheets from the own host(s) / web root (allowlist), with a timeout. Skip `data:`
and absolute `url()` values in `normalizeCssContent()`, and guard empty paths.

---

## [ ] B-84 – `Mail::getDebugInformation()` builds HTML from address names without escaping

**File:** `lib/Helper/Mail.php:46-67`, `:127-144`

- In debug mode the HTML debug table is appended to the mail. `formatDebugReceivers()` concatenates display names
  and addresses (`$name . ' <' . $mail . '>'`, `Address::toString()`) and the result is inserted into the `<td>`
  without `htmlspecialchars()`. A display name with `<`, `>` or `&` (very common, because the `<address>` itself
  contains `<` and `>`) breaks the table. A name from a contact form can inject HTML into the debug copy.

**Fix direction:** `htmlspecialchars(…, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` on each formatted receiver in the HTML
branch only.

---

## [ ] B-85 – `GotenbergHelper::isAvailable()` sends a full conversion request on every call while Gotenberg is down

**File:** `lib/Helper/GotenbergHelper.php:36-69`; callers `lib/Document/Adapter/Gotenberg.php:64-67`, adapter selection

- Only success is cached (`self::$validPing`). When Gotenberg is unreachable, every call (adapter discovery per
  document preview, `HtmlToImage`, the requirements check) runs a real Chromium conversion request again, with the
  HTTP client's default timeout (often none). Requests that only want to know "is Gotenberg there?" hang or slow
  down the admin while Gotenberg is down.
- The static positive cache is never cleared, so in a long-running worker Gotenberg stays "available" after it went
  away (acceptable, but then every conversion fails instead of falling back to LibreOffice).
- `Config::getSystemConfiguration('gotenberg')['base_url']` without `??` (`:49`).

**Fix direction:** cache the result both ways with a short TTL (PSR-6 pool, e.g. 60 s), use Gotenberg's `/health`
endpoint with a small timeout instead of a conversion, and reset via `kernel.reset`.

---

## [ ] B-86 – `EncoreHelper::getBuildPathsFromEntrypoints()` returns shared chunks once per entrypoint

**File:** `lib/Helper/EncoreHelper.php:24-39`

- Webpack Encore lists shared chunks (`runtime.js`, `vendors~…js`) in **every** entrypoint of `entrypoints.json`.
  `array_merge(...$paths)` keeps duplicates, so bundles that use the helper for `getJsPaths()` load the same runtime
  chunk two or more times in the admin. Loading the runtime twice can initialize shared modules twice
  (this is why Encore's own `encore_entry_script_tags()` de-duplicates files).
- A file without an `entrypoints` key gives `null` → `foreach` on `null` (warning), and an empty result is not
  distinguished from a missing build.

**Fix direction:** `array_values(array_unique(array_merge(...$paths)))`, and throw when `entrypoints` is missing.

---

## [ ] B-87 – Small helper correctness issues

**File:** `lib/Helper/TemporaryFileHelperTrait.php:57-79`; `lib/Helper/ContrastColor.php:30-64`;
`lib/Helper/LongRunningHelper.php:70-91`

- `TemporaryFileHelperTrait::getTemporaryFileFromStream()`: for a string source `fopen($stream, 'rb')` (`:60`) is not
  checked (`false` → `TypeError` in `stream_copy_to_stream()`), and the handle it opened is never closed. For URLs
  the extension includes the query string (`https://x/a.pdf?v=2` → `pdf?v=2`), which breaks extension-based tool
  selection.
- `ContrastColor::getContrastColor()` assumes `#rrggbb`. `#fff`, `fff`, `rgb(…)` or named colors give wrong
  components (`hexdec('')` = 0, `hexdec` on invalid characters gives a deprecation), so workflow place labels get the
  wrong text color.
- `LongRunningHelper::cleanupDoctrine()` throws a `LogicException` inside its own `try`, which the `catch (Exception)`
  swallows (`:72-83`). `triggerPhpGarbageCollector()` calls `gc_enable()` unconditionally, overriding a project
  that disabled GC on purpose, and uses `$this->logger`, which is `null` when the logger was not set.

**Fix direction:** check `fopen()` and close it, take the extension from `parse_url(…, PHP_URL_PATH)`, normalize short
hex in `ContrastColor`, move the type check out of the `try`, and use `$this->logger?->debug(…)`.

---

## Http

## [ ] B-88 – `RequestHelper::createRequestWithContext()` permanently changes the shared `RequestContext` host

**File:** `lib/Http/RequestHelper.php:192-211` (`:196-198`)

- `$this->requestContext->setHost($host)` mutates the router's shared `RequestContext` service. After one CLI render
  for a site (DocumentRenderer, static pages, search reindex), every later absolute URL generated in that process uses
  that site's host, also for documents of other sites. Nothing restores it.

**Fix direction:** build the URI from local variables (clone the context, or `Request::create()` with the host
directly) and leave the shared context untouched.

## [ ] B-89 – `RequestHelper::getAnonymizedClientIp()` breaks for IPv6 and for a missing client IP

**File:** `lib/Http/RequestHelper.php:169-185`

- `anonymizeIp()` assumes IPv4: for `2001:db8::1` `strrpos($ip, '.')` is `false`, so the result is the first character
  plus `255` (`"2255"`) – not anonymized in a meaningful way and not a valid address.
- `getClientIp()` returns `?string`; `null` (CLI-created requests) is a `TypeError` on the `string` parameter.

**Fix direction:** use `Symfony\Component\HttpFoundation\IpUtils::anonymize()` (handles IPv4 and IPv6) and guard `null`.

## [ ] B-90 – `RequestHelper::detectFrontendRequest()` treats every path starting with `/admin` as backend

**File:** `lib/Http/RequestHelper.php:110-121` (`:116`)

- `preg_match('@^/admin.*@', …)` also matches frontend pages such as `/administration`, `/admins-team`, `/admin-tips`,
  so they are not "frontend requests" (full-page cache, targeting, frontend listeners skip them).
- `isFrontendRequestByAdmin()` (`:141-160`) returns `true` for any request that carries `?pimcore_editmode=1` /
  `pimcore_preview` / … – it is a parameter check, not an authentication check, despite its name. Callers must check the
  user separately (EditmodeResolver does).

**Fix direction:** match `^/admin(/|$)` (or use the Pimcore context resolver), and rename / document the by-admin check.

## [ ] B-91 – `EditmodeResolver`: force flag is never reset, logger may be null

**File:** `lib/Http/Request/Resolver/EditmodeResolver.php:37-60`, `:68-70`

- `setForceEditmode(true)` stores the flag on the shared service; nothing resets it, so in a worker / long CLI run every
  later `isEditmode()` returns `true` (verify callers).
- `$this->logger->debug()` (`:57`, `:101`) fails when the logger was not injected (`LoggerAwareTrait`, nullable).
- The cached request attribute is returned without a `bool` cast (`:69`).

**Fix direction:** reset the flag via `ResetInterface` (or scope it with try/finally at the caller), `$this->logger?->`,
and `(bool)` the attribute.


## [ ] B-92 – `ClientFactory`: default timeout of one hour, proxy credentials not URL-encoded

**File:** `lib/Http/ClientFactory.php:37-64`

- `RequestOptions::TIMEOUT => 3600` (`:40`) is the default for **every** client created through the factory
  (`Tool::getHttpData()`, mail CSS inlining B-83, Gotenberg B-85, remote asset downloads, …). A slow or hanging remote
  host blocks a PHP-FPM worker or a messenger worker for up to an hour, and there is no `connect_timeout` either.
- The proxy URI is concatenated from `proxy_user`, `proxy_pass`, `proxy_host`, `proxy_port` (`:46-58`) without
  `rawurlencode()`. A password containing `@`, `:`, `/` or `#` produces an invalid or different URI (the part after
  `@` is taken as the host), so the proxy fails, or credentials end up in error messages as part of the "host". An
  empty port gives `host:`.
- `== 'Proxy'` loose comparison (`:44`).

**Fix direction:** sane defaults (`connect_timeout` 5 s, `timeout` 30 s, overridable per call), `rawurlencode()` user and
password, and omit `:port` when empty.

## [ ] B-93 – An empty `pimcore.context.*.routes` entry puts **every** request into that context

**File:** `lib/Http/RequestMatcherFactory.php:49-71`; `lib/Http/Context/PimcoreContextGuesser.php:53-66`;
`bundles/CoreBundle/src/DependencyInjection/Configuration.php:1217-1237`

- `addRoutesChild()` explicitly normalizes a `null` entry to `[]` (`ifNull()->then(fn () => [])`), and all criteria
  default to `false` / `[]`. `buildRequestMatcher()` then returns an **empty** matcher list, and `new
  ChainRequestMatcher([])` matches every request (`guess()`, `:58-61`).
- One empty or `~` line under `pimcore.context.admin.routes` (a YAML indentation slip, or a commented-out criterion
  that leaves the dash) makes **every** request, frontend included, resolve to the `admin` context. Frontend
  listeners (full-page cache, targeting, document routing helpers, `isFrontendRequest`-based logic) then treat the
  public site as admin.
- Same root cause as CoreBundle B-50 (debug-toolbar exclusions, not repeated). The fix belongs here, because both
  use this factory.

**Fix direction:** in `buildRequestMatcher()`, return `null` (or throw) for an entry without criteria, and skip it in the
callers. Validate "at least one of path / route / host / methods" in `Configuration`.

## [ ] B-94 – `ResponseHelper::isHtmlResponse()` sniffs the body for `<html`: JSON and other non-HTML responses get code injected

**File:** `lib/Http/ResponseHelper.php:59-70`; users: `CodeInjector::inject()` (`lib/Http/Response/CodeInjector.php:61`) and the
CoreBundle response listeners

- The check ignores `Content-Type`. Any response whose body contains the text `<html`, for example a JSON API response
  carrying an HTML snippet, a `text/plain` export, an XML feed with embedded HTML, is treated as HTML. `CodeInjector`
  then injects tracking / snippet code into it, which corrupts JSON (invalid syntax) or plain-text downloads.
- Real HTML whose tag is uppercase (`<HTML>`) or a fragment without `<html` is **not** detected.

**Fix direction:** decide on the `Content-Type` header (`text/html` / `application/xhtml+xml`) first, then (optionally)
confirm with a case-insensitive `<html` check. CoreBundle I-49 covers the scan cost (not repeated).

---

## HttpKernel

> Scope 16 notes: all 13 files in `lib/HttpKernel/**` were read, plus the consumers listed in the scope header. No hard
> Symfony 7.4 / PHP 8.4 incompatibility was found. `vendor/` is not installed, so statements about Symfony internals
> (`Preloader`, `CacheWarmerAggregate`, `cache:clear`) are **not verified locally**. The `'false'`-string problem of
> `PIMCORE_CLASS_DEFINITION_WRITABLE` is tracked in [models TODO-2](../models/TODO-2-BUGS.md) B-330 (not repeated); the
> field-collection fatal that the warmer can also hit is [models TODO-1](../models/TODO-1-CRITICAL.md) C-44 (not repeated).

## [ ] B-95 – `PimcoreCoreCacheWarmer` puts the runtime-generated DataObject classes on the OPcache preload list, but skips the definitions in `config/pimcore/classes`

**File:** `lib/HttpKernel/CacheWarmer/PimcoreCoreCacheWarmer.php:36-45`, `:87-117`;
`doc/23_Installation_and_Upgrade/03_System_Setup_and_Hosting/09_Performance_Guide.md:105-118`;
`models/DataObject/Traits/LocateFileTrait.php:25-45`

- The class names returned by `warmUp()` are appended by Symfony to `var/cache/<env>/App_Kernel<Env>Container.preload.php`
  (`cache:warmup`, and `cache:clear`, which enables optional warmers). The Performance Guide tells users to set
  `opcache.preload` to exactly that file.
- `dataObjectClasses()` adds `Pimcore\Model\DataObject\<Class>`, `<Class>\Listing`, every object brick and every field
  collection data class. These are generated files in `var/classes/DataObject/**` that change at runtime when a class is
  edited in the admin.
- A preloaded class stays in memory until PHP-FPM restarts, and the autoloader is never asked again. After an admin adds,
  renames or removes a field, web requests keep using the old class (missing getters / setters, removed fields still there).
  CLI processes, which are not preloaded, use the new class, so workers and web requests disagree.
- At the same time the glob only covers `PIMCORE_CLASS_DEFINITION_DIRECTORY` (`:89-90`). Definitions kept in
  `PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY` (`config/pimcore/classes`, the read-only deployment where classes
  only change with a deploy + restart, so preloading would be safe) are not listed. The brick and collection listings do read
  both folders. The result is the opposite of what is wanted: classes are preloaded when they can change, and not when they
  are fixed.

**Fix direction:** do not return generated DataObject / brick / collection classes when definitions are writable
(`PIMCORE_CLASS_DEFINITION_WRITABLE` unset or true, or definitions under `var/classes`); include the
`config/pimcore/classes` definitions when they are read-only. Mention in the Performance Guide that class changes need an
FPM restart when preloading is on. See I-85.

## [ ] B-96 – `PimcoreCoreCacheWarmer`: malformed or missing class names in the preload list

**File:** `lib/HttpKernel/CacheWarmer/PimcoreCoreCacheWarmer.php:67-85`, `:89-97`; `lib/Image/Chromium.php:20`

- `glob()` can return `false` on error (`:90`); `foreach` over `false` is a warning, which Symfony's debug error handler may
  turn into an exception that fails the warm-up (**not verified locally**).
- Every `*.php` file directly in `var/classes` is taken as a definition. A file that does not match `definition_*.php` keeps
  its full name, so `Pimcore\Model\DataObject\foo.php` is added (`:93`).
- The class name comes from the definition name without `ucfirst()` (`:93`). The generated class is
  `ucfirst($name)` (`ClassDefinition::getPhpClassFile()`), and PSR-4 autoloading is case sensitive on Linux, so a class whose
  name starts with a lowercase letter is never found. Bricks and collections use `ucfirst()` (`:104`, `:113`).
- `getClassesFromDirectory()` normalises `$file` to `/` (`:72`) but not `$dir`. On Windows the prefix replacement at `:74`
  never matches and `str_replace(DIRECTORY_SEPARATOR, …)` (`:76`) no longer finds anything, so the list is filled with paths.
- `lib/Image/Chromium.php` (deprecated alias file) is on the list. Loading it during preload calls `trigger_deprecation()`
  at top level, so every FPM start logs a deprecation.

**Fix direction:** `glob(...) ?: []`, match `^definition_(.+)\.php$` and skip other files, `ucfirst()` the name, normalise
`$dir` like `$file`, and exclude the deprecated alias files. I-85 replaces the path guessing as a whole.

## [ ] B-97 – A bundle cannot be overridden with a lower priority or a narrower environment list: the dependency wins, unlike what the docs say

**File:** `lib/HttpKernel/BundleCollection/BundleCollection.php:35-56`; `lib/Kernel.php:186-206`;
`doc/20_Extending_Pimcore/13_Bundle_Developers_Guide/04_Bundle_Collection.md` ("Overriding collection items")

- `add()` keeps the existing item only when its priority is `>=` the new one (`:43`). Otherwise the new item replaces it
  **including its environments**.
- The docs say: add the item before it is loaded as a dependency and "the dependency is simply ignored". That is only true
  when your priority is at least the dependency's. Registering `FooBundle` with priority 5, or with `['prod']` at a lower
  priority than the dependency, is silently replaced by the dependency's item, so the bundle loads in every environment.
- `Kernel::registerBundles()` adds the `config/bundles.php` bundles first, with priority 0 and no environments, then the
  core bundles, then `registerBundlesToCollection()`. Dependencies of a bundle listed in `config/bundles.php` (for example
  `PrestaSitemapBundle` from `PimcoreSeoBundle`) are therefore already registered at priority 0 before the user's
  override runs. An override with priority 0 and an environment restriction is ignored (`0 >= 0`). The only way to restrict
  them is a higher priority, which also changes the load order.

**Fix direction:** let an explicitly registered item win over one registered by a dependency, independent of priority (for
example an `ItemInterface::SOURCE_DEPENDENCY` source, or remember which items came from `registerDependencies()`). Fix the
docs to describe the actual rule. Add tests (I-87).

## [ ] B-98 – Bundle identifiers are not normalised: a leading `\` or a different case registers the same bundle twice and the kernel fails to boot

**File:** `lib/HttpKernel/BundleCollection/LazyLoadedItem.php:37-56`; `Item.php:40-43`; `BundleCollection.php:37-48`

- `LazyLoadedItem` stores the class name exactly as given. `class_exists()` accepts `'\Foo\BarBundle'` and
  `'foo\barbundle'` (checked with `php -r`), while `Item::getBundleIdentifier()` returns `get_class()` (`Foo\BarBundle`).
- `addBundle('\Foo\BarBundle')` next to `new BarBundle()` (from `config/bundles.php`) creates two items. Symfony's
  `Kernel::initializeBundles()` then throws `LogicException: Trying to register two bundles with the same name "BarBundle"`.
- The constructor accepts any existing class (`:44`). A class that is not a bundle, or a bundle whose constructor needs
  arguments, fails only later in `getBundle()` with a `TypeError` / `ArgumentCountError`, and only in the matching
  environment, so the mistake can pass dev and fail in prod.

**Fix direction:** in the constructor resolve the real name (`(new ReflectionClass($className))->getName()`), and check
`is_a($className, BundleInterface::class, true)` with a clear `InvalidArgumentException`.

## [ ] B-99 – Environment restriction `['all']` (the Symfony Flex convention) never matches

**File:** `lib/HttpKernel/BundleCollection/AbstractItem.php:51-58`

- `matchesEnvironment()` treats an empty list as "all" and otherwise compares strictly. `config/bundles.php` uses
  `['all' => true]`, so users copying that convention write `addBundle(X::class, 0, ['all'])`. The bundle is then never
  loaded, with no error. The same applies to differently cased names (`'Prod'`).

**Fix direction:** treat `'all'` as a wildcard (or reject it with an exception), and validate the list (non-empty strings).

## [ ] B-100 – `BundleLocator` can return the wrong bundle, and throws `ReflectionException` instead of `NotFoundException`

**File:** `lib/HttpKernel/BundleLocator/BundleLocator.php:49-84`; `BundleLocatorInterface.php:29`, `:40`; callers
`lib/Document/Editable/EditableHandler.php:129`, `:288`

- `new ReflectionClass($class)` (`:68`) throws `ReflectionException` for an unknown class name. The interface documents only
  `NotFoundException`, so callers that catch `NotFoundException` miss it.
- The first bundle whose namespace is a prefix wins (`:74-78`), in kernel order. With nested bundle namespaces
  (`Acme` and `Acme\BlogBundle`) a class of the inner bundle can resolve to the outer one. The longest match should win.
- A class directly in the bundle's root namespace (for example the bundle class itself) is not found, because the check
  needs `<namespace>\` as prefix (`:75`).
- If no bundle owns the class, the loop walks up to the parent class (`:80`). An application areabrick
  `App\Document\Areabrick\Teaser extends Vendor\FooBundle\Areabrick\Teaser` resolves to `FooBundle`, so
  `EditableHandler` looks up the icon and the bundle-location template (`:288`) in the vendor bundle instead of failing
  clearly.

**Fix direction:** catch `ReflectionException` and throw `NotFoundException`; match `namespace === bundleNamespace` or the
prefix; choose the longest matching namespace; make the parent-class fallback explicit (or drop it for areabricks).

## [ ] B-101 – `MkdirCacheWarmer`: the `PIMCORE_CLASS_DEFINITION_WRITABLE` check has no effect in the default setup

**File:** `lib/HttpKernel/CacheWarmer/MkdirCacheWarmer.php:42-65`; `lib/Bootstrap.php:224-225`

- The comment says that with `PIMCORE_CLASS_DEFINITION_WRITABLE=0` the warmer must not create the class definition folder
  (`:52-55`). But `PIMCORE_CLASS_DEFINITION_DIRECTORY` defaults to `PIMCORE_CLASS_DIRECTORY` (`Bootstrap.php:225`), and
  that folder is always in the list (`:46`). So `var/classes` is created anyway; the condition only matters for a custom
  constant.
- The flag is read from `$_SERVER` with PHP truthiness, so `'false'` means writable (same root cause as models B-330).
- The warmer only runs when the container is rebuilt. A folder deleted later (for example `var/log` in a container image
  that cleans `var/`) is not recreated until the next `cache:clear`.

**Fix direction:** decide what "not writable" means for this warmer (skip `PIMCORE_CLASS_DIRECTORY` too, or drop the
condition and the misleading comment); read the flag through one shared helper (models B-330).

---

## Image

> Scope 17 notes: all files in `lib/Image/**` and `lib/Image.php` were read, plus the consumers listed in the scope header.
> Checked locally with PHP 8.2.32, ext-gd, imagick 3.8.1 / ImageMagick 6.9.11 (scratch scripts only, no project code
> changed): the GD `TypeError` / `ValueError` cases (B-106), `constant()` on a missing name (B-108), the duplicated GIF
> frame (B-103), the Imagick crop size (B-105), the discarded `mergeImageLayers()` result (B-108) and the scan timing
> (B-102). ImageMagick 7 behaviour (B-109) is **not verified locally**.
> Not repeated: CoreBundle B-33 (shared custom adapter alias), CoreBundle I-34 (`tagged_iterator` for optimizers), I-65
> (exception context), [models TODO-3](../models/TODO-3-IMPROVEMENTS.md) I-13 (`Asset\Image::isAnimated*()` file reads).

## [ ] B-102 – `getContentOptimizedFormat()` scans every pixel in PHP: thumbnails of opaque PNGs with an alpha channel take seconds to minutes

**File:** `lib/Image/Adapter/Imagick.php:175-183`, `:315-335`; `lib/Image/Adapter/GD.php:58-66`, `:109-128`;
caller `models/Asset/Image/Thumbnail/Processor.php:117-119`, `:336-339`

- The thumbnail format `SOURCE` (the default) sets `$optimizedFormat = true`, so `getContentOptimizedFormat()` runs for
  every such thumbnail. If the image *can* have alpha (`isAlphaPossible`, true for any PNG/GIF/WebP saved with an alpha
  channel), `hasAlphaChannel()` walks every pixel with `getImagePixelColor()` (one `ImagickPixel` object per pixel) until
  it finds a transparent one.
- For a fully opaque image with an alpha channel (very common: PNG exports, screenshots) it visits **all** pixels.
  Measured: 4.1 s per megapixel with Imagick, 1.3 s per megapixel with GD. A 2560×1440 high-res thumbnail takes ~15 s,
  a thumbnail at original size of a 6000×4000 PNG ~100 s, which is more than typical PHP-FPM / proxy timeouts. The
  request holds the thumbnail lock during that time.
- The loop is column-major (`$i` = x outside), which is the slowest order for both libraries.
- ImageMagick answers the same question in ~1 ms: `$im->identifyFormat('%[opaque]')` (measured), or
  `getImageChannelRange(\Imagick::CHANNEL_ALPHA)`.

**Fix direction:** Imagick: use `%[opaque]` (or the alpha channel range). GD: downscale to a small sample first, or check
the original via Imagick, or treat "alpha possible" as "alpha used". Add a test with a large opaque RGBA image (I-95).

## [ ] B-103 – Animated GIFs: the first frame is read twice, so every animated thumbnail shows frame 1 twice

**File:** `lib/Image/Adapter/Imagick.php:84-90`, `:124-129`

- `load()` reads `"$imagePath[0]"` (first frame only) into `$i`, sets `$this->resource = $i`, and when animation is
  preserved calls `$this->resource->readImage($imagePath)` again (`:125`). `readImage()` **appends** to the wand, so the
  resource holds frame 0 plus all frames. Checked: a 3-frame GIF gives 4 images, still 4 after `coalesceImages()`.
- The first frame is shown for twice its delay, and the colour / clipping handling at `:92-105` was applied to the extra
  copy only.

**Fix direction:** read the full file into a fresh `\Imagick` (or `clear()` first) when animation is preserved, then apply
the colour handling to the coalesced sequence.

## [ ] B-104 – `getContentOptimizedFormat()` ignores "preserve animation": with format `SOURCE` animated GIFs become static JPEG / PNG

**File:** `lib/Image/Adapter/Imagick.php:175-183`, `:285-304`; `models/Asset/Image/Thumbnail/Processor.php:117-122`,
`:239`, `:336-339`

- For `SOURCE`, the processor picks `gif` and then replaces it with `getContentOptimizedFormat()`, which returns only
  `pjpeg` or `png32`. `save()` writes all frames only when the format is `gif` / `original` / `auto`
  (`checkPreserveAnimation()`, `:299`), so the animation is dropped although `preserveAnimation` is set.
- Users must know to pick `GIF` explicitly; the config option suggests otherwise.

**Fix direction:** return `gif` from `getContentOptimizedFormat()` when `checkPreserveAnimation()` is true and the resource
has more than one frame.

## [ ] B-105 – Imagick `crop()` stores the requested size, not the real one: later steps use wrong dimensions

**File:** `lib/Image/Adapter/Imagick.php:534-554`; `lib/Image/Adapter.php:144-208` (`cover()`), `:260-277` (`cropPercent()`)

- `cropImage()` clips the region to the image. The adapter still calls `setWidth($width)` / `setHeight($height)` and
  `setImagePage($width, $height, 0, 0)` with the requested values. Checked: an 8×8 crop at (5,5) of a 10×10 image is
  5×5, but the adapter reports 8×8.
- Reachable from config: `cover()` with `forceResize = false` on an image smaller than the target crops at negative
  offsets; `cropPercent()` with `x + width > 100`. Following steps (`frame()`, overlay positions, `roundCorners()`)
  use the wrong size, and the page geometry is larger than the image (transparent / offset canvas in GIF / PNG output).
- GD clamps the region (`GD.php:169-172`), so the two adapters give different results.

**Fix direction:** clamp like GD, or read `getImageWidth()` / `getImageHeight()` after the crop and use those for
`setImagePage()` and the stored size.

## [ ] B-106 – GD adapter crashes with `TypeError` / `ValueError` on reachable input

**File:** `lib/Image/Adapter/GD.php` (`declare(strict_types=1)`)

- `save()` passes `$quality` straight to `imagejpeg()` / `imagewebp()` (`:100-101`). With `null` (the default, and what
  `Adapter::reinitializeImage()` passes, `Adapter.php:356`) PHP throws `TypeError: imagejpeg(): Argument #3 ($quality)
  must be of type int, null given` (checked). Reached when a JPEG / WebP is processed with "preserve colour / metadata /
  animation" (format `original`) and more than one transformation, or by any caller that saves JPEG without quality.
- `addOverlay()` with origin `center` computes `$x` / `$y` with `round()` (float) (`:307-308`) and passes them to
  `imagecopyresampled()`: `TypeError … ($dst_x) must be of type int, float given` (checked). Every centred overlay fails.
- `crop()` with an x / y at or beyond the edge gives a width or height of 0 (`:169-173`); `imagecreatetruecolor(0, …)`
  throws `ValueError` (checked). Reachable via `cropPercent()` with `x = 100`.
- `save()` ignores the return value of the `image*()` writer (`:100-104`). A failed write leaves no file; the processor
  then `fopen()`s a missing file and passes `false` to `writeStream()`.
- `setBackgroundImage()` / `addOverlay()` do not check `imagecreatefromstring()` (`:240`, `:311`), so a broken file gives a
  `TypeError` in the next GD call.

**Fix direction:** `$quality ?? -1`, `(int) round(...)`, return early (or throw a clear exception) for an empty crop,
check writer / loader results and throw. Add GD tests (I-95).

## [ ] B-107 – GD adapter silently ignores overlays given as asset paths, and misses alpha for uppercase extensions

**File:** `lib/Image/Adapter/GD.php:44-51`, `:289-319`; compare `lib/Image/Adapter/Imagick.php:736-750`

- Imagick resolves an overlay path as an asset path first (`Asset\Image::getByPath()`) and treats a file path relative to
  the project as deprecated since 10.3. GD only supports the deprecated file path (`PIMCORE_PROJECT_ROOT . '/' . $image`).
  An overlay configured the recommended way (asset path) is skipped by `is_file()` without a log line.
- `$alpha` and `$composite` are ignored by GD, and `addOverlayFit()`, `applyMask()`, `trim()`, `roundCorners()`,
  `gaussianBlur()`, `brightnessSaturation()` fall through to the no-op base methods. The thumbnail looks different from the
  Imagick result and nobody is told.
- `load()` decides "alpha possible" with a case-sensitive `in_array(pathinfo(…), ['png', 'gif'])` (`:48`). `LOGO.PNG` is
  treated as opaque, so `getContentOptimizedFormat()` returns `pjpeg` and transparency turns black. WebP / AVIF alpha is
  never considered.

**Fix direction:** share the overlay path resolution between both adapters (I-88), log once per unsupported operation,
decide alpha from the image (`imagecolortransparent()` / the pixel format) or at least `strtolower()` the extension.

## [ ] B-108 – Imagick adapter: small defects

**File:** `lib/Image/Adapter/Imagick.php`

- `addOverlay()`: `is_null(constant('Imagick::' . $composite))` (`:730`) is meant as a fallback, but `constant()` throws
  `Error: Undefined constant` for an unknown name (checked). An invalid composite value from the thumbnail config breaks
  the thumbnail instead of falling back to `COMPOSITE_DEFAULT`. Use `defined()`.
- `save()`: `$i->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN)` returns a **new** object (checked) and the result is
  discarded (`:219`). Multi-layer sources (PSD, layered TIFF) saved as JPEG are not flattened.
- `save()`: the configured quality is ignored when `preserveColor` is set (`:235`), although colour preservation has
  nothing to do with compression.
- `sharpen()` calls `normalizeImage()` before `unsharpMaskImage()` (`:848`). A "sharpen" step also stretches the contrast
  of the whole image, which is not what the setting says.
- `getVectorFormatEmbeddedRasterDimensions()` opens the file (`:953`) and returns from inside the loop without `fclose()`;
  `fopen()` is not checked there or in `has8BIMClippingPath()` (`:161`), so an unreadable file gives a `TypeError`.
- `getCMYKColorProfile()` / `getRGBColorProfile()` return `string` but return `null` when neither the configured nor the
  bundled profile exists (`:435-440`, `:465-470`) → `TypeError`. The profile is cached in a static property, so a changed
  `icc_*_profile` setting is not seen by a running worker.
- `resize()` for vector graphics re-reads `$this->imagePath` without `[0]` (`:506`). A multi-page PDF / AI / EPS is
  rasterised in full at the target density (CPU, memory), and only one image is removed afterwards (`:525-527`).
  Effect on multi-page files **not verified locally**.
- `save()` to a non-local path: the second `stream_is_local($path)` (`:262`) is always true after `$path` was replaced by a
  local temp path (`:256-259`), so the blob branch is dead, and `Filesystem::rename()` from a local file to a stream
  wrapper path fails (PHP `rename()` cannot cross wrapper types). The temp file is left behind when `writeImage()` throws.

**Fix direction:** see each point; most are one-line changes. Add tests (I-95).

## [ ] B-109 – ImageMagick 7 builds of imagick: `IMGTYPE_GRAYSCALEMATTE` (and possibly `setImageMatte()`) are not available

**File:** `lib/Image/Adapter/Imagick.php:830` (`grayscale()`), `:813` (`applyMask()`)

- imagick registers the type constants depending on the ImageMagick version: with ImageMagick 6 (local) only
  `IMGTYPE_GRAYSCALEMATTE` exists, `IMGTYPE_GRAYSCALEALPHA` does not (checked). With ImageMagick 7 it is the other way round,
  so `grayscale()` would throw `Error: Undefined constant` on current distributions / Docker images that ship IM7.
  `setImageMatte()` is deprecated in imagick and may be missing in IM7 builds. **Not verified locally** (no IM7).

**Fix direction:** `defined('Imagick::IMGTYPE_GRAYSCALEALPHA') ? … : …` (or `transformImageColorspace(COLORSPACE_GRAY)`),
and `setImageAlphaChannel(\Imagick::ALPHACHANNEL_SET)` instead of `setImageMatte(true)`. Run the image tests against IM6
and IM7 in CI.

## [ ] B-110 – `Adapter` base class: division by zero, float → int deprecations, missing focal-point key

**File:** `lib/Image/Adapter.php` (no `declare(strict_types=1)`)

- `contain()` / `cover()` / `scaleBy*()` divide by the target or current size (`:112`, `:122`, `:131-132`, `:149-151`).
  A width or height of 0 (empty field in a thumbnail config, or an image whose size could not be read) throws
  `DivisionByZeroError`.
- `getVectorRasterDimensions()` returns floats (`:375-384`); `cropPercent()` passes them to `resize(int, int)` (`:265`).
  With a fraction PHP 8.1+ logs "Implicit conversion from float … to int loses precision" (deprecation, and a
  `TypeError` once the file gets strict types).
- `cover()` with a focal point reads `$orientation['y']` without checking it (`:187`); only `x` is checked (`:184`).
- `colorhex2colorarray()` (`:95-102`) assumes `#rrggbb`. `#fff`, `fff` or `rgb(…)` give wrong colours without an error.
- `scaleByWidth()` scales when `$width <= getWidth()`, `scaleByHeight()` only when `$height < getHeight()`
  (`:111` vs `:121`).

**Fix direction:** guard against 0 (return unchanged or throw `InvalidArgumentException`), `(int) round()` the raster
dimensions, `isset($orientation['x'], $orientation['y'])`, a validated colour parser (I-92).

## [ ] B-111 – `Optimizer`: failures are swallowed without a log line, and a result larger than the original is written back

**File:** `lib/Image/Optimizer.php:33-76`

- `catch (ImageOptimizationFailedException) {}` (`:53-54`) discards the failure: no log, no context. A missing binary or a
  broken optimizer is invisible (announced in the Scope 12 notes above).
- The smallest **optimised** file is written back (`:68-70`) without comparing it with the original size. If every optimizer
  produced a larger file, the thumbnail gets bigger. When no binary is installed, Spatie's chain just copies the input, so
  the identical file is written back to storage (an extra write per thumbnail).

**Fix direction:** log with optimizer class and path (I-65), compare with `filesize($workingPath)` and only write when smaller.

## [ ] B-112 – `SpatieImageOptimizer` re-encodes lossy and ignores the thumbnail quality

**File:** `lib/Image/Optimizer/SpatieImageOptimizer.php:29-50`; `bundles/CoreBundle/src/DependencyInjection/Configuration.php:488-491`

- Image optimizers are enabled by default (`canBeDisabled()`), and the optimisation runs after the thumbnail was written
  with the configured quality.
- `Cwebp` is configured with only `-pass 10 -mt`, so `cwebp` uses its default quality 75. A WebP thumbnail configured with
  quality 90 is re-encoded at 75; it is smaller, so it wins (B-111) and replaces the thumbnail.
- `Pngquant` is lossy (quantises to a 256-colour palette) and is used with Spatie's defaults. PNG thumbnails, chosen exactly
  because they need full colour / alpha, lose colours.

**Fix direction:** pass the configured quality (`-q`) to `cwebp`, make `pngquant` opt-in or set `--quality=min-max`, and
make the optimizer options configurable.

## [ ] B-113 – `HtmlToImage`: failed detection is cached for the process lifetime, Gotenberg errors are swallowed, session is ignored

**File:** `lib/Image/HtmlToImage.php:36-79`, `:108-131`

- `getSupportedAdapter()` caches `''` in a static property (`:49`). If Gotenberg or the Chromium endpoint is down once, a
  messenger worker reports "not supported" until it restarts (`isSupported()`, requirements check, document previews).
- `convertGotenberg()` catches `Exception` with an empty body (`:126-128`). A wrong base URL, a timeout or a Gotenberg error
  makes `convert()` return `false` without any log line; `convertChromium()` at least logs at debug level.
- `$sessionName` / `$sessionId` are used by the Chromium path (Cookie header, `:162-176`) but ignored by the Gotenberg
  path, so the same call renders a logged-out page with Gotenberg. Core calls do not pass a session today
  (`models/Document/Service.php:553`), but the public signature promises it.
- The Gotenberg temp file is moved with `rename()` without checking the result's existence or cleaning up on failure.

**Fix direction:** cache only a positive result (or with a short TTL), log the exception at warning level, pass the cookie
through Gotenberg's `cookies()` / `extraHttpHeaders()`. See I-93.

---

## Loader

> Scope 18 notes: all 8 files in `lib/Loader/**` were read, plus the consumers listed in the scope header. No Symfony 7.4 /
> PHP 8.4 incompatibility was found. `vendor/` is not installed, so `doctrine/inflector` behaviour is taken from its
> documented `classify()` rule. The admin UI bundle is not in this repo, so which admin requests pass a user-supplied
> editable type is **not verified locally**. Not repeated: [models TODO-2](../models/TODO-2-BUGS.md) B-279 (abstract
> `fieldtype` in a class-definition import), CoreBundle I-61 (documenting the "last added wins" order).

## [ ] B-114 – Loaders report "supported" for classes they cannot build: abstract classes, unrelated classes in sub-namespaces → `Error` / `TypeError` instead of `UnsupportedException`

**File:** `lib/Loader/ImplementationLoader/PrefixLoader.php:62-101`; `AbstractClassNameLoader.php:29-39`;
`lib/Model/Factory/FallbackBuilder.php:27-30`; `lib/Model/Document/Editable/Loader/PrefixLoader.php:27-30`

- `PrefixLoader::supports()` is "prefix + normalised name is an existing class" (`Tool::classExists()`), and
  `FallbackBuilder::supports()` is `class_exists($name)`. Neither checks that the class is instantiable or of the type
  the loader is for. `build()` then runs `new $className(...)`.
- The name is not restricted to one segment: `classify()` / `ucfirst()` keep `\`, so a name can reach any class below the
  prefix namespace. Examples with the default config (`\Pimcore\Model\Document\Editable\`):
  - `Area\AbstractArea` → `Error: Cannot instantiate abstract class`;
  - `Loader\EditableLoader` → builds an `EditableLoader`, and `EditableLoader::build(): Editable` throws `TypeError`.
- Callers only catch `UnsupportedException` (`models/Asset.php:1446-1452`, `models/Asset/Dao.php`,
  `models/Metadata/Predefined.php`, `Relation::resolveClassName()`). The `Error` / `TypeError` ends the request with a 500,
  and `supports()` already said "yes" to code that checks first.
- The editable type comes from templates, from `documents_editables.type` and from editmode save data; the object data /
  layout type from class-definition JSON (models B-279).

**Fix direction:** in `AbstractClassNameLoader` check `(new ReflectionClass($class))->isInstantiable()` and an optional
expected base type (`is_a($class, $this->baseType, true)`) in `supports()`, and reject names containing `\` in
`PrefixLoader`. Throw `UnsupportedException` otherwise.

## [ ] B-115 – `ImplementationLoader::addLoader()` does not clear the lookup cache: a loader added later is ignored for names already resolved

**File:** `lib/Loader/ImplementationLoader/ImplementationLoader.php:57-79`

- Lookup is "last added wins" (`array_reverse`, `:70`), and the result index is cached per name (`:65-67`, `:72`).
  `setLoaders()` clears the cache, `addLoader()` does not. A loader registered at runtime (a bundle, a test, a
  `kernel.request` listener adding a class override to `Pimcore\Model\Factory`) has no effect for every name that was
  already built through another loader.
- In the container the method calls run before first use, so the default wiring is not affected.

**Fix direction:** reset `$this->loaderCache` in `addLoader()`.

## [ ] B-116 – Class names are normalised differently per loader: leading `\` and letter case decide whether an override applies

**File:** `lib/Loader/ImplementationLoader/ClassMapLoader.php:29-57`; `PrefixLoader.php:94-97`;
`ImplementationLoader.php:62-79`; `bundles/CoreBundle/config/pimcore/default.yaml:278`, `:283`, `:321`

- `ClassMapLoader` strips a leading `\` from keys and values. `PrefixLoader` keeps it: the default prefixes start with `\`,
  so `getClassNameFor()` returns `\Pimcore\Model\…\Input` from one loader and `Pimcore\Model\…\Input` from another. Code
  that compares the result with `::class` gets a different answer depending on the configuration.
- The caches (`ImplementationLoader::$loaderCache`, `PrefixLoader::$cache`) are keyed by the raw name, so `Foo` and `\Foo`
  are looked up and cached twice.
- PHP class names are case-insensitive, but `ClassMapLoader` compares keys case-sensitively. A
  `pimcore.models.class_overrides` key written as `Pimcore\Model\DataObject\news` does not override `…\News`, and the
  `FallbackBuilder` silently builds the original class.

**Fix direction:** one normaliser for all loaders (`ltrim('\\')`, and for the class-map key a case-insensitive lookup or a
validation that the key is the declared class name via `ReflectionClass::getName()` at container build time).

---

## Localization

> Scope 19 notes: all 3 files in `lib/Localization/**` were read, plus the consumers listed in the scope header. No Symfony
> 7.4 / PHP 8.4 incompatibility was found (`IntlFormatter` already uses PHP 8.3 typed class constants, so the file does not
> parse on PHP < 8.3; this is fine for the 8.4 target). The intl checks below ran on PHP 8.2.32 with ICU 70.1 against a copy
> of `IntlFormatter` with the constant types removed; PHP 8.4 with a newer ICU is **not verified locally**. `vendor/` is not
> installed, so `Request::getLocale()` behaviour (falls back to the default locale) and the `umpirsky/country-list` data
> layout are taken from the libraries' documented behaviour. `IntlFormatter` has no consumer inside this repo; it is a
> public service (`l10n.yaml:26`) for project code.

## [ ] B-117 – `LocaleService::getLocale()` caches the first locale it sees: stale within a request and across requests in long-running processes

**File:** `lib/Localization/LocaleService.php:93-100`; `bundles/CoreBundle/config/l10n.yaml:21-23`

- `getLocale()` stores the main request's locale in `$this->locale` on the first call and never reads the request again
  (only `setLocale()` changes it).
- Within one request: any code that calls `getLocale()` before Symfony's `LocaleListener` (priority 16) or Pimcore's
  document/site listeners set the request locale freezes the default locale (`%locale%`, usually `en`) for the rest of the
  request. `Localizedfield::getLanguage()` (`models/DataObject/Localizedfield.php:277`) and generated `CalculatedValue`
  getters (`CalculatedValue.php:241`) then read the wrong language.
- Across requests: the service is shared and does not implement `ResetInterface`, so under a worker runtime
  (FrankenPHP/RoadRunner worker mode, `messenger:consume` handling several messages) the locale of the first request or
  message stays for every later one. **Not verified locally** (no worker runtime set up).
- Sub-requests: the value comes from the main request only, so an ESI/fragment sub-request with its own `_locale` reports
  the main request's locale.

**Fix direction:** do not cache the request value. Keep `$this->locale` only for an explicit `setLocale()` override and
otherwise read `RequestStack::getCurrentRequest()` each time; implement `ResetInterface` (`reset()` clears the override) and
tag it `kernel.reset`.

## [ ] B-118 – `findLocale()` / `hasLocale()` cannot tell "no locale" from "default locale" in HTTP context; `setLocale(null)` does not restore anything

**File:** `lib/Localization/LocaleService.php:45-57`, `:59-70`, `:102-117`, `:119-122`; `lib/Document/Renderer/DocumentRenderer.php:118-132`

- `Request::getLocale()` returns the request's default locale when none was set, never `null`. So in any HTTP request
  `hasLocale()` is always `true`, and `findLocale()` never reaches `Tool::getDefaultLanguage()` (`:51`): the Pimcore
  "default language" from the system settings is ignored whenever a request exists, and `%locale%` (framework
  `default_locale`, `default.yaml:15`) wins instead. It is used only on the CLI.
- `setLocale(null)` (and `setLocale('')`) only clears the field (`:104-106`); the translator and the request keep the last
  locale. `DocumentRenderer::render()` saves `getLocale()` (`null` on the CLI) and "restores" it with `setLocale(null)`
  (`DocumentRenderer.php:119`, `:132`), so after rendering a document in a command (static page generator, mail rendering)
  the translator stays on the document's language for the rest of the command. `tests/Support/Util/TestHelper.php:286`
  uses the same save/restore pattern.
- `DocumentRenderer` has no `try`/`finally` around the render (`:129-132`), so an exception in the sub-request also skips
  the restore. (Listed here because the fix is in the `LocaleService` contract; no Document TODO covers it.)

**Fix direction:** make the "no explicit locale" state explicit (override field + separate "effective locale"); let
`hasLocale()` mean "an explicit or request-attribute `_locale` exists". Restore with Symfony's `LocaleSwitcher::runWithLocale()`
(see I-101), which resets all locale-aware services and runs in `try`/`finally`.

## [ ] B-119 – `getDisplayRegions()`: unvalidated locale in an `include` path, wrong fallback, result type not checked

**File:** `lib/Localization/LocaleService.php:77-91`; `models/DataObject/ClassDefinition/DynamicOptionsProvider/CountryOptionsProvider.php:36`

- `$locale` (from the argument or the request locale) is concatenated into `PIMCORE_COMPOSER_PATH . '/umpirsky/country-list/data/'
  . $locale . '/country.php'` and passed to `include` (`:83-85`). Nothing checks that it is a locale. Any reachable file named
  `country.php` (`../`) is executed. The request locale usually comes from a document property or a `{_locale}` route
  parameter; the default route requirement blocks `/`, so this needs a route with a looser requirement or a `\` on Windows.
  Exploitability **not verified locally**.
- The fallback goes straight to `en` (`:87`). If the package has no folder for e.g. `de_LI`, the country list is English
  instead of German. There is no `Locale::getPrimaryLanguage()` step. (Which locale folders exist in `umpirsky/country-list`
  is **not verified locally**.)
- `include` runs on every call (no memoisation), and its return value is returned as `array` without a check: a missing
  `en/country.php` (package not installed) gives a warning + `false` → `TypeError`.

**Fix direction:** validate with `isLocale()` / `Locale::canonicalize()` and a `^[A-Za-z]{2,3}(_[A-Za-z0-9]+)*$` check; walk
`de_LI` → `de` → `en`; memoise per locale. Better: replace the data package with `Symfony\Component\Intl\Countries::getNames()`
(I-102).

## [ ] B-120 – `IntlFormatter` locale is frozen on first use and does not follow `LocaleService`

**File:** `lib/Localization/IntlFormatter.php:48`, `:76-83`, `:85-93`; `bundles/CoreBundle/config/l10n.yaml:26-27`

- `getLocale()` copies `findLocale()` into `$this->locale` once. The formatters built from it are cached too. Later
  `LocaleService::setLocale()` calls (`DocumentRenderer` rendering a snippet in another language, a command looping over
  languages) do not reach the formatter. Verified with a stub service: after switching the service from `de_DE` to `en_US`,
  `formatNumber(1234.5)` still returned `1.234,5` and `formatDateTime(0, 'date_long')` returned `1. Januar 1970`.
- The service is shared and has no `reset()`, so under a worker runtime the first request's locale stays (same as B-117).

**Fix direction:** read the locale from the `LocaleService` on each call and key the formatter caches by
`[locale][format]`; or implement `Symfony\Contracts\Translation\LocaleAwareInterface` (then `LocaleSwitcher` updates it) and
`ResetInterface`.

## [ ] B-121 – `formatDateTime()`: string input fails silently, the value's own timezone is ignored, unknown languages throw `Error`

**File:** `lib/Localization/IntlFormatter.php:110-160`, `:167-172`

- The signature accepts `string`, but `IntlDateFormatter::format()` only takes numeric strings. Verified:
  `formatDateTime('2024-01-01')` returns `false` ("string '…' is not numeric").
- No timezone is passed to `IntlDateFormatter::create()`, so the PHP default timezone is used for every value. A
  `DateTimeInterface` in another zone is converted. Verified: `2024-01-01 10:00 America/New_York` with default
  `Europe/Budapest` prints `16:00:00`.
- For a locale whose language ICU does not know (`zz`, `xx_YY`; `de_XX` is fine), `create()` returns an object whose
  methods throw `Error: Found unconstructed IntlDateFormatter` (verified on ICU 70.1). It is stored in `$dateFormatters`
  first, so every later call throws too. The `@throws RuntimeException` doc (`:108`) only covers an unknown format name.
- The return type `bool|string` hides that `true` is impossible; callers have to check for `false`.

**Fix direction:** accept `DateTimeInterface|int|float` (parse strings with `new DateTimeImmutable()` if strings stay);
pass `$dateTime->getTimezone()` (or an explicit `?DateTimeZone $tz` parameter) to the formatter; use `new IntlDateFormatter()`
(throws `IntlException`) instead of `create()` and do not cache a failed formatter; return `string` and throw on `false`.

## [ ] B-122 – Currency formatting: `setCurrencyFormat()` is ignored after first use, bad patterns are silently dropped, `getCurrencyFormat()` throws `TypeError`

**File:** `lib/Localization/IntlFormatter.php:95-103`, `:191-206`

- The `'default'` formatter is cached in `$currencyFormatters['default']` (`:193`, `:202`). A later `setCurrencyFormat()`
  for the current locale is not applied. Verified: `1.234,50 €` before and after `setCurrencyFormat('de_DE', '#,##0.00 ¤¤')`.
- `setPattern()` returns `false` on a syntax error (verified: `U_PATTERN_SYNTAX_ERROR`); the result is not checked
  (`:197`, `:199`), so a typo in a configured pattern silently falls back to the locale default.
- `getCurrencyFormat()` for a locale without a configured format reads an undefined key: warning + `TypeError` ("Return
  value must be of type string, null returned"). Verified.
- `formatCurrency()` is declared `: string`, but `NumberFormatter::formatCurrency()` returns `string|false`; with
  `strict_types` a `false` becomes a `TypeError`. No input that returns `false` was found locally (unknown currency codes
  print as `12,50 XYZ`, `''` as `XXX`), so this is a latent issue.
- Money as `float` only: binary rounding (`0.1 + 0.2`) reaches the output. (Design point, see I-103.)

**Fix direction:** invalidate `$currencyFormatters` in `setCurrencyFormat()` (or key by locale + pattern); check
`setPattern()` and throw `InvalidArgumentException` with `intl_get_error_message()`; make `getCurrencyFormat()` return
`?string`; throw on `false` from the formatter.

---

## Mail

> Scope 20 notes: `lib/Mail/**` (2 files) and `lib/Mail.php` were read, plus the consumers listed in the scope header.
> `vendor/` is not installed. Symfony Mime / Mailer behaviour was checked against **symfony/mime 6.4.18** and Twig
> **3.20 / 3.22** from other local projects. The 7.4 versions are **not verified locally**. No Symfony 7.4 / PHP 8.4
> incompatibility was found: `MailerInterface::send(RawMessage, ?Envelope): void` and the `Email` method signatures
> match. Not repeated: [models TODO-1](../models/TODO-1-CRITICAL.md) C-42 (blocklist never applied), [models
> TODO-2](../models/TODO-2-BUGS.md) B-335 (address parsing in `setDocumentSettings()`) and B-241 (email log rows),
> B-81–B-84 above (`lib/Helper/Mail.php`, incl. unescaped debug info), CoreBundle C-12 (Twig sandbox).

## [ ] B-123 – The debug-mode redirect can be bypassed: explicit envelope, other mailers, `forceDebugMode`

**Files:** `lib/Mail/Mailer.php:39-54`; `lib/Mail.php:207-214`, `:489-517`, `:519-522`; `lib/Event/MailEvents.php:24`

- With an explicit `Envelope`, `Mailer::send($message, $envelope)` runs `beforeSendPerformed()` and rewrites the
  `To` / `Cc` / `Bcc` headers, but the transport delivers to the **envelope** recipients. In debug mode the mail still
  goes to the real recipients, and only the headers show the debug address.
- The redirect only exists inside the `Pimcore\Mail\Mailer` decorator. `Mail::send($mailer)` with any other
  `MailerInterface`, or a `PRE_SEND` listener that replaces the `mailer` argument (documented as an extension point in
  `MailEvents.php:24`), sends to the real recipients in debug mode. A plain Symfony `Email` sent through the
  decorated `mailer` service (Notifier, password reset bundles) is never redirected (`:41`, `:51`).
- `doRedirectMailsToDebugMailAddresses()` honours `setForceDebugMode(true)` (`:209-211`), but the logging branch
  checks only `Pimcore::inDebugMode()` (`:520`). With forced debug mode outside debug mode, the log records the debug
  addresses as recipients. The `X-Pimcore-Debug-*` headers are then never removed (`getDebugMailRecipients()` is
  skipped) and stay on the object.

**Fix direction:** do the redirect at the transport level, where the envelope is final: a `MessageEvent` listener sets
`$event->setEnvelope()` and the headers (I-106). Use `doRedirectMailsToDebugMailAddresses()` in the logging branch too.
Document that only `Pimcore\Mail` is redirected, or redirect every message.

## [ ] B-124 – Debug redirect leaves the message modified: recipients are not restored, headers pile up, resend in debug mode throws `TypeError` after the mail was sent

**File:** `lib/Mail/Plugins/RedirectingPlugin.php:74-110`, `:162-191`; `lib/Mail.php:244-254`, `:569-591`, `:776-792`

- `beforeSendPerformed()` calls `clearRecipients()` and adds the debug addresses. `sendPerformed()` restores html, text
  and subject, but never the original `To` / `Cc` / `Bcc` / `Reply-To`. After `send()` the object holds the debug
  recipients, and `recipientsCleared` stays `true`.
- The `X-Pimcore-Debug-*` headers are added with `Headers::add()` each time. Verified with Mime 6.4: a second add gives
  two `X-Pimcore-Debug-To` headers. They are only removed in the logging branch, so with `disableLogging()` or on a
  resend they pile up, and the second set lists the debug address as the "original" recipient.
- If the transport throws, `sendPerformed()` is skipped (`Mailer.php:49-53`). The message keeps the subject
  `Debug email: …` and the appended debug block. A retry of the same object gives `Debug email: Debug email: …` and
  appends the block twice.
- With `preventDebugInformationAppending()` (used when resending from the email log), `appendDebugInformation()` stores
  nothing, so `getOriginalData()` is `null`. `setSenderAndReceiversParams()` then passes `$originalData[$k]` to
  `formatDebugReceivers(array)`: a warning and a `TypeError` (verified pattern). This happens **after** the transport
  has sent the mail. `TypeError` is not an `Exception`, so `sendWithoutRendering()` neither catches nor logs it
  (`:514`). The caller sees a failure for a mail that was delivered, and no log entry is written.

**Fix direction:** redirect a clone (or in a `MessageEvent` on the transport's copy, I-106) so the caller's object is
never changed; otherwise save and restore all four address headers in `try`/`finally`, `set()` instead of `add()` for
the debug headers, and use `$originalData[$k] ?? []`.

## [ ] B-125 – `appendDebugInformation()`: `$`/`\` in the debug block are treated as regex back-references; no `</body>` means no debug block

**File:** `lib/Mail/Plugins/RedirectingPlugin.php:115-157` (`:129-130`)

- `preg_replace('!(</\s*body\s*>)!i', "$debugInformation\\1", $html)` uses the debug block as the replacement string.
  It contains display names and addresses. `$1`, `\0` or `${1}` in a name are replaced by the captured `</body>`.
  Verified: `Price $1 \0 Joe` becomes `Price </body> </body> Joe`, which ends the body early in the debug mail.
- HTML without a `</body>` (fragments set with `html()`, which is common) gets no debug block at all, and without a
  `<head>` it gets no style. The original recipients are then invisible in the debug mail.
- `preg_replace()` returns `null` on a PCRE error, and `$message->html(null)` sends an empty body.
- `getHtmlBody()` / `getTextBody()` can return a resource (`Email::html($resource)`). `preg_replace()` on a resource is a
  `TypeError`, and `.=` on the text resource gives `Resource id #…`.
- The escaping of names inside the block is lib B-84 (not repeated).

**Fix direction:** `preg_replace_callback()` (or `str_ireplace()` on the last `</body>`); append at the end when there is
no `</body>`; check for `null`; convert resources with `(new TextPart($body))->getBody()` first.

## [ ] B-126 – `addTo('a@x', 'b@x')` adds **one** recipient named `b@x`: the BC shim breaks Symfony's variadic API

**File:** `lib/Mail.php:852-920`; callers `lib/Workflow/Notification/NotificationEmailService.php:130`, `:144`

```php
if (isset($addresses[1]) && is_string($addresses[1])) {
    return [new Address($addresses[0], $addresses[1])];   // 2nd string = display name, 3rd+ dropped
}
```

- `addTo()`, `addCc()`, `addBcc()`, `addFrom()` and `addReplyTo()` keep the old Zend/Swift `($address, $name)` call
  style. In Symfony, `addTo('a@example.com', 'b@example.com', 'c@example.com')` adds three recipients. In `Pimcore\Mail`
  it adds one address with the display name `b@example.com`, and `c@` is dropped silently. Verified with a copy of the
  method.
- `addTo(...$listOfAddresses)` from an array of strings therefore loses all recipients except the first.
- `formatAddress()` accepts `array` in its signature, but no caller can pass one.

**Fix direction:** deprecate the two-argument form: trigger a deprecation when the second string has no `@`, and treat
it as a name only in that case. Otherwise pass the list on unchanged. Then remove the shim and make callers use
`new Address($email, $name)`.

## [ ] B-127 – `Mail` properties are lost on serialisation; with async mail the log says "sent" before delivery

**Files:** `lib/Mail.php:38-108`, `:442-447`, `:509-531`

- Symfony's `Email::__serialize()` returns a fixed list (text, html, charsets, attachments and parent data). Verified
  with Mime 6.4.18 and a subclass: `params` and `documentId` come back as `[]` and `null`. Every `Pimcore\Mail`
  property (`params`, `documentId`, `loggingEnable`, `ignoreDebugMode`, `hostUrl`, `originalData`, …) is dropped when
  the message goes through Messenger. The comment at `:442-443` ("remove the document … to serialize the Mail object")
  is therefore misleading.
- When `SendEmailMessage` is routed to an async transport (project config, not Pimcore's default), `mailer->send()`
  only queues the message. `sendWithoutRendering()` then logs the mail as sent with no error, and a later failure in
  the worker never reaches the email log. `MessageEvent` / `SentMessageEvent` listeners in the worker get a `Mail` with
  empty params.

**Fix direction:** override `__serialize()` / `__unserialize()` in `Mail` and append its own fields (except the
document). Write the log from `SentMessageEvent` / `FailedMessageEvent` (I-108), or store "queued" as a separate status.

## [ ] B-128 – `setContentType()` / the `$contentType` constructor argument add a second `Content-Type` header

**File:** `lib/Mail.php:129`, `:154-156`, `:840-845`

- `addTextHeader('Content-Type', …)` adds a header to the message headers, and the body part writes its own
  `Content-Type`. Verified with Mime 6.4: the raw message has **two** `Content-Type:` lines, one at the top and one
  from the part. Mail clients and spam filters handle this differently: a `multipart/alternative` mail with a top-level
  `text/html` header shows the raw MIME structure in some clients.
- Each call adds another header (`Content-Type` is not in the unique-header list).

**Fix direction:** deprecate `setContentType()` and the constructor argument. Symfony derives the content type from the
body. If a charset is needed, use `html($body, $charset)`.

## [ ] B-129 – Error handling in `sendWithoutRendering()` / `renderParams()`: exception types lost, `TypeError` instead of the friendly message, `getLastLogEntry()` throws

**Files:** `lib/Mail.php:108`, `:488-537`, `:612-616`, `:829-832`

- Transport errors are wrapped in a plain `new Exception($e->getMessage(), 0, $e)` (`:515`). Callers can no longer catch
  `TransportExceptionInterface`, and `getDebug()` of the transport exception is only reachable via `getPrevious()`.
- Only `Exception` is caught (`:514`). A `TypeError` / `Error` from the mailer stack (see B-124) skips the log entry.
- If the container lookup of the mailer fails, a `PRE_SEND` listener that provides its own mailer is ignored (the send is
  skipped because of `!$sendingFailedException`, `:509`). A listener that **removes** `mailer` to cancel the send leads
  to a log entry with no error, so the log shows the mail as sent.
- `renderParams()`: the friendly sandbox message cuts the Twig message at `strpos(..., ' in "__string')`. Because a name
  is passed to `createTemplate()`, Twig 3 names the template `pimcore_email_<context> (string template <hash>)`
  (verified in Twig 3.20 / 3.22), so the marker is never found. `substr($msg, 0, false)` under `strict_types` is a
  `TypeError` (verified), which replaces the intended message.
- `getLastLogEntry()` reads the uninitialised typed property `$lastLogEntry` before the first send, with logging
  disabled, or when logging failed (`:528-530`). The result is `Error: must not be accessed before initialization`.

**Fix direction:** rethrow the original exception, or wrap it in a Pimcore exception that keeps its type in `getPrevious()`
and document it. Catch `Throwable` for the log entry and rethrow. Record "cancelled by listener" as its own status. Use
`$e->getRawMessage()` (Twig `Error`) instead of string cutting. Make `getLastLogEntry(): ?Log` with a `null` default.

## [ ] B-130 – `setDocument()`: an unknown id or path is silently accepted; calling it twice duplicates recipients

**File:** `lib/Mail.php:379-416`, `:729-748`; `:141-143`

- `setDocument(123)` or `setDocument('/mails/typo')` resolves to `null` when the document does not exist. `null` is an
  accepted value (`:739`), so the mail is built without a body and without the document's recipients. The send then
  fails later with Symfony's "An email must have a To, Cc, or Bcc header", or goes out with an empty body.
- A path to a document that is not an email document also gives `null` (`Email::getByPath()` filters by type), with the
  same result instead of the exception at `:744`.
- Each `setDocument()` call runs `setDocumentSettings()` again and **adds** `To` / `Cc` / `Bcc` / `Reply-To`. Calling
  it twice, or setting the document via the constructor option and again later, duplicates every recipient. The
  `From` address is overwritten.
- `send()` sets the document to `null` (`:444-448`), so a second `send()` of the same object can no longer render it.
  It reuses the already-rendered html, and with C-10 renders it once more.

**Fix direction:** throw `InvalidArgumentException` when an id or path resolves to nothing. Apply the document's
addresses once with `to(...)` / `cc(...)` (set, not add), or only when the headers are empty. Keep `documentId` and
reload the document on demand.

## [ ] B-131 – Smaller `Mail` defects

**File:** `lib/Mail.php`; `lib/Mail/Plugins/RedirectingPlugin.php:41-49`

- `sendWithoutRendering()` writes the filtered list back with `getHeaders()->get(strtolower($key))`. For `ReplyTo` that is
  `replyto`, not `reply-to` (verified: `null`). The Reply-To filtering never applies, which matters once models C-42 is
  fixed (`:472-482`).
- `getBodyTextRendered()` removes only the first `<style>` inside `<body>` (`:703-706`). Further `<style>` blocks, for
  example from snippets or the CSS embedding, end up as text in the plain-text part.
- `getParam()` on an unknown key gives a warning and returns `null` (`:332-335`). Add `?? null` or `hasParam()`.
- `init()` treats `[]` like `null` and reloads the system config (`:169-171`). `new Address($email, $name)` gets the name
  from config, which defaults to `''`, but a `null` written by a config UI would be a `TypeError` (`:174`, `:178`).
- `RedirectingPlugin` reads `SystemSettingsConfig::get()` in its constructor (`:43`). The settings are loaded when the
  `mailer` service is created, even if no mail is sent. A worker keeps the old debug addresses until it restarts. The
  addresses are not trimmed; `Address` trims them, but an entry of only spaces becomes `''` and fails.
- `sendPerformed()` checks `$message instanceof Mail` although the parameter is typed `Mail` (`:106`).

**Fix direction:** `reply-to` header name (map the key); remove all `<style>` nodes; `getParam(..., mixed $default = null)`;
`$config ??= …`; read the debug addresses lazily per send and `array_filter(array_map('trim', …))`.

---

## Maintenance

> Scope 21 notes: all 21 files in `lib/Maintenance/**` were read, plus the consumers listed in the scope header. No Symfony
> 7.4 / PHP 8.4 incompatibility was found. Symfony Lock (`Lock::__destruct()`, `DoctrineDbalStore::save()`) and
> DependencyInjection (`AbstractRecursivePass::getConstructor()`) were checked in the 6.4 sources of another local project;
> 7.4 is **not verified locally**. Already covered and not repeated: `ScheduledTasksTask` → models C-36, C-37, B-210–B-214;
> `VersionsCleanupTask` → models B-223 (incl. the auto-save `null` crash at `:57-61`); `TmpStoreCleanupTask` and
> `Executor::get/setLastExecution()` TmpStore typing → models B-238 / B-239; `RegisterMaintenanceTaskPass` / `MaintenanceCommand`
> help → CoreBundle TODO-3 I-26 and its compiler-pass item.

## [ ] B-132 – `DbCleanupBrokenViewsTask` drops views that are only inaccessible or not Pimcore's; dropped views are never recreated

**File:** `lib/Maintenance/Tasks/DbCleanupBrokenViewsTask.php:40-63`; `models/DataObject/ClassDefinition/Dao.php:209-210`;
`models/DataObject/Localizedfield/Dao.php:766`; `models/DataObject/Listing/Concrete/Dao.php:117`

- Every view in the database (`SHOW FULL TABLES`) is probed with `SHOW FIELDS FROM <name>`. On an error whose message contains
  `references invalid table`, the view is dropped (`:52-56`).
- That text belongs to MySQL/MariaDB error 1356, whose full message is "... references invalid table(s) or column(s) or
  function(s) **or definer/invoker of view lack rights to use them**". The same error is raised when the view's `DEFINER` exists
  but lacks privileges, e.g. after a dump is restored under another DB user or after a privilege change. Then the task drops
  **all** Pimcore views: `object_<classId>` (the table name every `DataObject\Listing\Concrete` queries, `Concrete/Dao.php:117`)
  and `object_localized_<classId>_<lang>`. Listings fail until each class is saved again or
  `pimcore:deployment:classes-rebuild` runs; nothing in maintenance recreates them.
- Views of other applications in a shared schema are dropped too; there is no name filter (`object_%`, `documents_editables`,
  `translations_website`, …).
- Matching the message text depends on the server's `lc_messages`: with a non-English server locale broken views are never
  removed. The error code (`1356`, via `DriverException::getCode()` / SQLSTATE) is the stable check.
- Names are concatenated unquoted into `SHOW FIELDS FROM` / `DROP VIEW` (`:50`, `:56`); a view name with `-` or a reserved word
  fails every run.
- The live-environment definer scenario is **not verified locally** (no DB server); the message text is MySQL's documented
  `ER_VIEW_INVALID`.

**Fix direction:** limit the task to Pimcore-owned name patterns; check the driver error code 1356 and additionally that a
referenced base table is really missing (`information_schema.VIEW_TABLE_USAGE` / `TABLES`) before dropping; quote with
`quoteIdentifier()`; for Pimcore class views, recreate (`ClassDefinition::save()` view part / `Localizedfield\Dao::createUpdateTable()`)
instead of only dropping; log at `warning`.

## [ ] B-133 – `LowQualityImagePreviewTask`: the 48 h lock never blocks a re-run, so all image assets are scanned on every maintenance run between 00:00 and 04:59

**File:** `lib/Maintenance/Tasks/LowQualityImagePreviewTask.php:38-84` (`:41`, `:50`, `:54-80`, `:82`);
`bundles/CoreBundle/config/services.yaml:120-129`; `models/Asset/Image.php:149-160`

- The lock is created once in the constructor with TTL 172 800 s and `autoRelease = true` (default), backed by
  `DoctrineDbalStore`.
- In a long-running `messenger:consume` worker the same `Lock` object is reused: `acquire()` → `DoctrineDbalStore::save()` hits
  the existing row and "puts off" the expiration for the **same token**, so it returns `true` again on every run.
- In a fresh process per run, the previous process released the lock in `Lock::__destruct()` (autoRelease), so `acquire()`
  succeeds as well. Only a still-running parallel scan is blocked.
- Result: with the documented 5-minute cron, the scan runs ~60 times per night instead of once. Each run walks **all** image
  assets with `OFFSET` paging (10 per page, `ORDER BY id DESC`) and calls `getLowQualityPreviewDataUri()`, which **reads the SVG
  from the thumbnail storage** for every image (`Image.php:156`) – one remote read per asset per run on S3-like storages.
- Assets whose generation fails (unsupported format, broken file) are retried and logged at `error` on every run.
- `date('H') <= 4` uses the PHP default timezone (often UTC in containers), so the "low-load" window can fall into local
  business hours. The `else` branch logs "was done within the last 24 hours" also when the hour simply is > 4 (`:82`).
- Lock semantics come from the Symfony Lock 6.4 source; a live run is **not verified locally**.

**Fix direction:** store "last completed run" (TmpStore / SettingsStore with date) and check it instead of a lock, or create the
lock with `autoRelease: false` and never re-acquire in the same process; keep a short lock only for mutual exclusion; select only
images without an LQIP (flag or custom-setting column) with keyset paging; remember failed ids; use the configured timezone.

## [ ] B-134 – `StaticPagesGenerationTask`: pages without a lifetime are regenerated on every run, failures are logged at `debug` only

**File:** `lib/Maintenance/Tasks/StaticPagesGenerationTask.php:43-83` (`:62-75`, `:76-78`);
`doc/19_Development_Tools_and_Details/20_Static_Page_Generator.md:8`

- `$generate = true` unless a lifetime is set and not yet reached. The doc calls the lifetime **optional** ("regenerates static page
  after lifetime"), but a page without one is re-rendered on **every** maintenance run (every 5 minutes by default), although it is
  already regenerated on save by the listener.
- A failed generation is logged with `debug` (`:77`); production log levels drop it, so stale or missing static files go unnoticed.
- Every run loads all `page` documents 10 at a time with `OFFSET` paging and checks `getStaticGeneratorEnabled()` in PHP although
  `documents_page.staticGeneratorEnabled` is a column (`install.sql:157`).
- `getLastModified()` performs a storage `fileExists()` + `lastModified()` per page per run.

**Fix direction:** without lifetime, skip (generation on save only) unless the file is missing; log failures at `error` with the
document id; query `staticGeneratorEnabled = 1` in SQL with keyset paging.

## [ ] B-135 – `LogCleanupTask` loses log lines: archives files that long-running processes still write to, deletes the original after a failed compression

**File:** `lib/Maintenance/Tasks/LogCleanupTask.php:28-73` (`:31`, `:44-51`, `:65-71`); `lib/helper-functions.php:30-58`

- Once a day each `var/log/*.log` is `rename()`d to `*-archive-<date>.log` (`:47`). Processes that keep the file open (Messenger
  workers, long CLI commands, Monolog `StreamHandler`) keep writing to the **renamed** inode. In the same run the archive is
  gzip-compressed and `unlink()`ed (`:68-70`), so every line written by those processes after the compression is lost, until they
  restart.
- `gzcompressfile()` checks neither `fread()` nor `gzwrite()` results and returns the target path even when writing failed (disk
  full); `LogCleanupTask` ignores even a `false` return and unlinks the source (`:69-70`) → the day's log is gone.
- The archive name uses the date of the **previous** rotation (`$lastTime`), while the file contains everything since then – after
  maintenance downtime one "day" file holds several days.
- The first `glob()` (`:31`) is not checked for `false` (the later ones are, `:57`, `:61`); `foreach` over `false` emits a warning.
- Two concurrent runs (no overlap protection, B-137) race on `rename()` / compression of the same file.

**Fix direction:** rotate by copy-and-truncate (or leave rotation to logrotate / Monolog `RotatingFileHandler` and only prune),
compress archives only when they are older than one run, check the compression result (`gzcompressfile()` must return `false` on
short writes) before deleting; name the archive by the period it covers.

## [ ] B-136 – Collection-table cleanups split table names at the first `_`: keys with underscores are misparsed

**Files:** `lib/Maintenance/Tasks/DataObject/CleanupFieldcollectionTablesTaskHelper.php:61-82`;
`lib/Maintenance/Tasks/DataObject/CleanupBrickTablesTaskHelper.php:49-71`; `lib/Maintenance/Tasks/DataObject/DataObjectTaskHelper.php:52-85`;
`lib/Maintenance/Tasks/CleanupClassificationstoreTablesTask.php:37-69`; `models/DataObject/Fieldcollection/Definition.php:308`

- Field-collection and brick keys are validated with `^[a-zA-Z]\w*$` (bricks inherit it), so `Order_Item` is a valid key. The
  helpers take everything up to the first `_` as the key. Verified: `object_collection_Order_Item_1` → key `Order`, class
  `Item_1`; `object_brick_store_Tech_Specs_2` → `Tech` / `Specs_2`.
- Usual effect: "Fieldcollection 'Order' not found" is logged at `error` on **every** run and the table is never cleaned.
- With a key-prefix collision (`Address` and `Address_Extra`) and a class whose id matches the remainder (class ids allow `_`,
  `ClassDefinition.php:359`), the rows of `Address_Extra` are compared with the **wrong** class and every fieldname that class
  does not have is deleted.
- The brick and classification-store queries use `SHOW TABLES LIKE 'object_brick_store_%'` with unescaped `_` wildcards; the
  field-collection helper escapes them (`'object\_collection\_%'`). Table names are concatenated unquoted into
  `SELECT fieldname FROM …` (`DataObjectTaskHelper.php:65`, `CleanupClassificationstoreTablesTask.php:56`).
- `CleanupBrickTablesTaskHelper` calls `cleanupTable()` without `$isLocalized`, so the default `true` also searches the class's
  localized fields for brick store/query tables.

**Fix direction:** resolve the key by matching the known key list (`$collectionNames`, longest prefix first) and the class id
by matching existing class ids, instead of `strpos('_')`; escape `_` in `LIKE` patterns; `quoteIdentifier()`; pass
`$isLocalized` explicitly per table type.

## [ ] B-137 – `Executor`: no overlap protection, "last execution" set at dispatch time, unknown job names silently ignored

**Files:** `lib/Maintenance/Executor.php:48-99`, `:114-117`; `bundles/CoreBundle/src/Command/MaintenanceCommand.php`;
`lib/Messenger/Handler/MaintenanceTaskHandler.php`

- Every `pimcore:maintenance` run dispatches one message per task (~14) regardless of whether the previous messages were
  consumed. When a task takes longer than the cron interval the `pimcore_maintenance` queue grows without bound; with more than one
  consumer the same task runs concurrently (log rotation, version cleanup, static page generation, LQIP scan). models B-210
  describes this for scheduled tasks only.
- `setLastExecution()` is written **before** dispatching (`:75`), so "last maintenance run" reports success even when no worker
  consumes `pimcore_maintenance` and nothing is executed. (The reader of `getLastExecution()` is the admin UI bundle, not in this
  repo – **not verified locally**.)
- Names passed with `--job` / `--excludedJobs` are not validated: a typo or wrong case (`versionCleanup`) dispatches nothing and the
  command logs "All maintenance-jobs finished!" although jobs are only queued at that point.
- `executeTask()` swallows every `Exception` (message acknowledged, no retry) but lets `Error` / `TypeError` through (Messenger
  retries and moves it to the failed transport) – inconsistent failure semantics (`:65`). A queued message for a task that no longer
  exists (bundle removed after deploy) throws a generic `Exception` (`:51`) outside the `try` and also ends in retries.

**Fix direction:** deduplicate per task name (Symfony 7.3+ `DeduplicateStamp` / a lock in the handler) (I-112); record
start/finish/failure per task when it actually runs; validate job names against `getTaskNames()` and fail the command for unknown
ones; catch `Throwable`, log, and decide explicitly whether to retry; treat unknown task names as "skip + warning".

## [ ] B-138 – Housekeeping options without effect: tmp-file cleanup setting is ignored, cache pruning targets `cache.app`

**Files:** `lib/Maintenance/Tasks/HousekeepingTask.php:31-84`; `bundles/CoreBundle/src/DependencyInjection/Configuration.php:178-185`;
`lib/Maintenance/Tasks/PruningCacheItemsTask.php:24-35`; `bundles/CoreBundle/config/maintenance.yaml`

- `pimcore.maintenance.housekeeping.cleanup_tmp_files_atime_older_than` (default 90 days, documented "Integer value in seconds") is
  injected as `$tmpFileTime` and never used; only the `dev` profiler directory is cleaned (`:43`). Projects relying on it get no
  temp-file cleanup.
- Deleting profiler files one by one leaves the profiler's `index.csv` entries; the profiler list then links to tokens that 404.
  Files are chosen by `atime` (`:60`), which is not updated on `noatime` mounts.
- `PruningCacheItemsTask` autowires `CacheItemPoolInterface`, which Symfony aliases to `cache.app` – Pimcore's own
  `pimcore.cache.pool` (e.g. a `doctrine_dbal` adapter, where expired rows are only removed by `prune()`) is not pruned. Which pool
  autowiring resolves to in an installed app is **not verified locally**.

**Fix direction:** either implement the tmp cleanup for `PIMCORE_SYSTEM_TEMP_DIRECTORY` (by `mtime`) or deprecate the option;
remove profiler entries via `Profiler::purge()` / the storage API; inject `pimcore.cache.pool` explicitly (and optionally a list of
pools) into `PruningCacheItemsTask`.

---

## Marshaller

> Scope 22 notes: `lib/Marshaller/**` contains only `MarshallerInterface` (37 lines). It was checked against all 20
> implementations and both call sites (`Block`, `Classificationstore\Dao`). No Symfony 7.4 / PHP 8.4 incompatibility was found:
> every implementation keeps `(mixed $value, array $params = [])` and only narrows the return type (allowed covariance).
> Already covered and not repeated: the direction rule and objects in storage (I-42), the encrypted-field defects (B-43, B-44),
> partial input (B-52), `MarshallerService` typo / double lookup / untyped locator (I-58), `marshaller.yaml` wiring (I-59).

## [ ] B-139 – The `$params` contract is not defined, and the encrypted-field marshallers call their delegate without `object`

**Files:** `lib/Marshaller/MarshallerInterface.php:24-36`; `lib/DataObject/BlockDataMarshaller/EncryptedField.php:56`, `:75`;
`lib/DataObject/ClassificationstoreDataMarshaller/EncryptedField.php:61`, `:93`; `models/DataObject/ClassDefinition/Data/Block.php:140`,
`:200`; `models/DataObject/Classificationstore/Dao.php:139`, `:255`; `lib/DataObject/BlockDataMarshaller/Localizedfields.php:56`, `:91`

- The interface documents neither the keys of `$params` nor whether `null` must be accepted. The callers pass
  `['object' => $object, 'fieldDefinition' => $fd, 'format' => 'block'|'classificationstore']` (`Block.php:140`, `:200`,
  `Classificationstore/Dao.php:139`, `:255`), and the nested `Localizedfields` marshaller forwards the same three keys (`:56`, `:91`).
- The two `EncryptedField` marshallers call the delegate's marshaller with only `fieldDefinition` and `format`
  (`BlockDataMarshaller/EncryptedField.php:56`, `:75`; `ClassificationstoreDataMarshaller/EncryptedField.php:61`, `:93`), although
  they read `$params['object']` themselves (`:93`, `:125` / `:114`, `:146`). A marshaller that depends on the owning object (e.g. a
  project marshaller registered under `block_<type>` / `classificationstore_<type>` for a type that is also used as encrypted
  delegate) gets the object in a plain block or store and `null` inside an encrypted field.
- None of the built-in delegate marshallers reads `object`, so core types are not affected today; the impact is on project
  marshallers and on future core marshallers.
- `format` is still passed "only for BC reasons (localizedfields) … remove it as soon as marshal is gone" (`Block.php:139`, `:199`),
  but no marshaller reads `$params['format']` any more and the legacy `$fd->marshal()` call is commented out (`Block.php:125`).

**Fix direction:** document the params as an array shape on the interface
(`@param array{object?: ?Concrete, fieldDefinition?: Data, format?: 'block'|'classificationstore'} $params`), forward the caller's
`$params` (with `fieldDefinition` replaced) in the encrypted-field marshallers, and either keep `format` documented or drop the stale
TODOs and stop passing it.

---

## Messenger

> Scope 23 notes: all 24 files in `lib/Messenger/**` were read, plus the consumers listed in the scope header. No Symfony
> 7.4 / PHP 8.4 incompatibility was found (`BatchHandlerInterface` / `BatchHandlerTrait` / `Acknowledger` are unchanged in 7.4;
> `symfony/doctrine-messenger ^7.4` is required in `composer.json:86`). Checked and **not** a defect: workers do not reuse stale
> elements across messages, because `MessengerClearRuntimeCacheListener` clears `RuntimeCache` on every
> `WorkerMessageReceivedEvent`; `ElementDependenciesHandler` is not registered twice (its `#[AsMessageHandler]` is ignored because
> `message_handler.yaml` has no `autoconfigure`). Already covered and not repeated: the update-queue lock release
> (models B-01, CoreBundle B-58), `Version::disable()` without `finally` (models B-215), video processing (models C-11, C-12),
> PDF scan / page count (models TODO-1), `ElementDependenciesHandler` state leak (models B-92, I-48), `SanityCheckHandler` version
> re-save (models C-38, B-219), `VersionDeleteHandler` listing / auto-save (models B-224), maintenance handlers (B-137, models C-36),
> `cleanUp()` not deleting temp files (B-79), lost `wait_timeout` after reconnect (B-30), GEE middleware on the dispatch side
> (GenericExecutionEngineBundle C-05).

## [ ] B-140 – `CollectGarbageMiddleware` also runs when a message is **dispatched**: DB connections and log handlers are closed and the runtime cache is cleared in the middle of a request

**File:** `lib/Messenger/Middleware/CollectGarbageMiddleware.php:25-31`; `lib/Helper/LongRunningHelper.php:62-130`;
`bundles/GenericExecutionEngineBundle/config/pimcore/config.yaml:10-15`

- Bus middleware runs for `dispatch()` (sending side) and again in the worker (receiving side). The middleware calls
  `Pimcore::collectGarbage()` unconditionally → `LongRunningHelper::cleanUp()`: closes every DBAL connection without an active
  transaction, closes all Monolog handlers, clears `RuntimeCache` and runs `gc_collect_cycles()`.
- It is configured on `generic.execution.engine.bus`. `JobExecutionAgent::startJobExecution()` dispatches on that bus from admin
  requests and commands, so starting a job in a request reconnects to the DB afterwards (session variables set at connect time are
  lost, see B-30), flushes/closes log handlers early and drops all cached element instances: code that continues after the dispatch
  gets **different** instances from `getById()` than the ones it already holds.
- It runs **before** the handler, so the memory of the handled message is only released when the next message arrives; the last
  message's objects stay in memory while the worker idles.
- On the receiving side, clearing `RuntimeCache` duplicates `MessengerClearRuntimeCacheListener`.

**Fix direction:** act only on received envelopes (`$envelope->last(ReceivedStamp::class) !== null`) and clean up **after**
`$stack->next()->handle()` in a `finally`; or replace it with a `WorkerMessageHandledEvent` / `WorkerMessageFailedEvent` listener for
all Pimcore buses (I-121).

## [ ] B-141 – Failed messages are discarded by default, and handlers disagree on retry semantics

**Files:** `bundles/CoreBundle/config/pimcore/default.yaml:31-54`; `lib/Messenger/Handler/GeneratePagePreviewHandler.php:35-42`;
`lib/Messenger/Handler/VersionDeleteHandler.php:52-58`; `lib/Messenger/Handler/AssetUpdateTasksHandler.php:42-64`, `:138-170`

- Pimcore's default config defines no `failure_transport`. After the default retry strategy (3 retries) a failed message is
  **dropped**; the docs only mention an opt-in `pimcore_failed_jobs` transport. A failed `AssetUpdateTasksMessage` leaves the asset
  without dimensions / page count / video metadata, and nothing re-queues it.
- `GeneratePagePreviewHandler` catches `Exception` and only logs (message acknowledged, never retried), via the static
  `Logger::err()` although a `LoggerInterface` is injected and unused (`:31`, `:40`). `Error` / `TypeError` escape and are retried.
- `VersionDeleteHandler` logs and skips each failing `delete()` and then acks the message (`:55-60`): versions that failed once are
  never retried and remain orphaned.
- `AssetUpdateTasksHandler` lets exceptions through, so a failure **after** `saveAsset()` (e.g. preview thumbnail of a corrupt image,
  `:163`) retries the whole message: the asset is saved again on every retry (save events, search reindex, cache clears).
- The batch handlers `nack()` per job, the maintenance handlers swallow everything (B-137): four different failure policies.

**Fix direction:** ship a default failure transport (`pimcore_failed`, doctrine) wired to the Pimcore transports; define one policy
per handler (transient → rethrow / `RecoverableMessageHandlingException`, permanent → `UnrecoverableMessageHandlingException` + log);
use the injected logger; in `AssetUpdateTasksHandler` generate derived files before the single final save or make the post-save
steps idempotent and non-fatal.

## [ ] B-142 – `AssetUpdateTasksHandler`: the "processing failed" flag can stay in the database; version note is ignored

**File:** `lib/Messenger/Handler/AssetUpdateTasksHandler.php:52`, `:74-102`; `models/Asset.php:1819-1824`

- `removeCustomSetting(PROCESSING_FAILED)` (`:52`) only changes the in-memory object. For documents the asset is saved only when the
  PDF needs a JS scan note or the page count must be (re)processed (`:78-93`). If page-count processing was switched off after a
  failure, the flag is removed in memory, the thumbnail is generated, and the flag stays in the DB.
  `Asset::addToUpdateTaskQueue()` refuses to queue assets with that flag (`Asset.php:1821`), so the document is never processed
  again by later saves.
- `saveParams['versionNote'] = 'PDF scan result'` (`:80`) is passed to a save that runs with `Version::disable()` (`:68-71`), so
  the note is never stored.
- Debug log "Asset with ID %s not found" for a deleted asset is fine, but the update-queue lock for that id is then not released
  either (`:45-48` returns before `:63`; release itself is models B-01).

**Fix direction:** save when the flag was present before (`$hadFailedFlag`); drop the dead version note or log the scan result
instead; release (or let expire) the queue lock in a `finally` once B-01 is fixed.

## [ ] B-143 – Folder preview messages are not de-duplicated: every tree render can queue another full composite generation

**Files:** `models/Asset/Folder.php:150-160`; `lib/Messenger/Handler/AssetPreviewImageHandler.php:45-72`;
`lib/Messenger/Handler/HandlerHelperTrait.php`

- `Folder::getPreviewImage()` dispatches an `AssetPreviewImageMessage` each time it finds a missing tile thumbnail, i.e. on every call
  until the preview exists. The callers (admin tree / grid previews) live in the admin UI bundle, not in this repo; the call frequency
  is **not verified locally**.
- `AssetPreviewImageHandler` processes every message, without the `filterUnique()` that `CleanupThumbnailsHandler` and
  `SanityCheckHandler` use. Each one calls `getPreviewImage(true)`, which renders all tile thumbnails and the composite image
  synchronously. Duplicates of the same folder in a batch (size 5) and across batches repeat that work.
- The handler also contains branches for images, documents and videos (`:51-56`) that no core code dispatches.

**Fix direction:** `filterUnique()` by asset id; skip when the composite file is already newer than the folder's last change; in
`Folder::getPreviewImage()` guard the dispatch with a short lock / `DeduplicateStamp` (Symfony 7.3+) per folder id.

---

## Migrations

> Scope 24 notes: all 4 files in `lib/Migrations/**` were read, plus the consumers listed in the scope header. `vendor/` is not
> installed; Doctrine Migrations behaviour (`Rollup`, `TableMetadataStorage`, `DbalExecutor`, the `MetadataStorage` / `Comparator`
> interfaces) was checked in **doctrine/migrations 3.8.2 and 3.9.5** from other local projects. The four classes match the 3.x
> interfaces; `doctrine/doctrine-migrations-bundle` is pinned to 3.4.0 (`composer.json:134`), which supports Symfony 7.4. No
> Symfony 7.4 / PHP 8.4 incompatibility was found.

## [ ] B-144 – `doctrine:migrations:rollup --prefix=…` deletes the executed-migration rows of every bundle

**Files:** `lib/Migrations/FilteredTableMetadataStorage.php:89-92`; `lib/Migrations/FilteredMigrationsRepository.php:70-85`;
`lib/Console/Application.php:94-98`

- `--prefix` is applied to every Doctrine command (`isDoctrineCommand()`), so the repository and the storage only see the
  migrations of that namespace.
- Doctrine's `Rollup::rollup()` requires exactly one available migration, then calls `$metadataStorage->reset()` and records that
  one version. `TableMetadataStorage::reset()` runs `DELETE FROM migration_versions WHERE 1 = 1` (doctrine/migrations 3.9.5).
- Without a prefix Pimcore has hundreds of migrations, so rollup refuses. With a prefix whose namespace has a single migration
  (a small bundle), rollup is allowed, and `FilteredTableMetadataStorage::reset()` delegates **unfiltered**: the rows of the core and
  every other bundle are deleted. The next `doctrine:migrations:migrate` tries to run all core migrations again against an
  up-to-date schema.
- `getExecutedMigrations()` is filtered, `reset()` is not – the class is inconsistent with its own purpose.

**Fix direction:** when a prefix is set, `reset()` must delete only rows whose `version` starts with the prefix (`DELETE … WHERE
version LIKE :prefix ESCAPE …`); or throw from `reset()` while a prefix is active.

## [ ] B-145 – `DirectoryAwareVersionComparator` is not a consistent ordering when a migration class no longer exists

**File:** `lib/Migrations/DirectoryAwareVersionComparator.php:35-63`

- Normal case: `[namespaceOrder, shortClassName]`. If either class cannot be reflected (executed migration of a removed bundle or a
  deleted file), the pair is compared as plain strings (`:44-46`).
- Mixing the two rules is not transitive. Verified with a simulation: core namespace (order 0), `App\Migrations` (order 1) and an
  executed `Pimcore\Bundle\AdminBundle\Migrations\…` whose class is gone give core < app, app < admin (string), admin < core
  (string). `usort()` then returns different orders depending on input order, and the last element (Doctrine's "current" version)
  changes.
- `TableMetadataStorage::getExecutedMigrations()` sorts with this comparator (`uasort`), and the aliases `current`, `prev`, `latest`
  of `doctrine:migrations:migrate` / `status` are resolved from that order. `doctrine:migrations:migrate prev` can therefore revert an
  unexpected migration. Executed rows of removed bundles are common after Pimcore 10 → 11 (bundles moved out of core).
- Unknown namespaces get order `0`, the same as the first registered namespace (`:60`).
- A `ReflectionClass` is created twice per comparison (`O(n log n)` reflections, autoloading every migration class).

**Fix direction:** derive the namespace from the version string (`substr($version, 0, strrpos($version, '\\'))`) without reflection,
give unknown namespaces a fixed order after all registered ones (`PHP_INT_MAX`), and compare `[order, shortName, fullName]` – always
the same rule. Cache per version string.

## [ ] B-146 – Prefix filtering is shared mutable state: never reset, applied to one service but not the other, matched without a separator

**Files:** `lib/Migrations/FilteredMigrationsRepository.php:55-68`, `:79`; `lib/Migrations/FilteredTableMetadataStorage.php:57-60`, `:76`;
`lib/Extension/Bundle/Installer/SettingsStoreAwareInstaller.php:73-118`; `bundles/InstallBundle/src/Installer.php:560`

- Both classes are shared services and the Doctrine factories return the same instance (`__invoke()` returns `$this`). The installer
  sets the prefix in `markInstalled()` / `markUninstalled()` and never clears it. Any later use of the `DependencyFactory` in the same
  process (a project command that installs several bundles via the installer API and then runs migrations, the admin request that
  installed a bundle and then reads migration status) sees only that bundle's migrations.
- `markUninstalled()` sets the prefix on the storage only (`:108`), so repository and storage are filtered differently in that
  process.
- `str_starts_with($version, $prefix)` without a trailing `\`: the prefix `Pimcore\Bundle\CoreBundle` (used by the installer,
  `Installer.php:560`) also matches `Pimcore\Bundle\CoreBundleExtras\…`.
- `hasMigration()` / `getMigration()` ignore the prefix (`:60-68`), so `doctrine:migrations:execute --prefix=A 'B\VersionX'` runs B's
  migration although the filtered `status` does not list it.
- `FilteredTableMetadataStorage::$storage` and `FilteredMigrationsRepository::$filesystemRepo` are uninitialised until `__invoke()`
  runs; `InternalMigrationHelpersCommand` calls `__invoke()` by hand (`:58`) to work around it.

**Fix direction:** pass the prefix per operation (immutable filtered views: `withPrefix(string): self`) or reset it in a `finally`
inside the installer; normalise the prefix to end with `\`; filter `hasMigration()` / `getMigration()` as well; initialise the inner
services lazily in a private getter instead of relying on `__invoke()`.

## [ ] B-147 – `BundleAwareMigration`: unregistered bundles throw instead of skipping; skipped migrations stay pending forever

**File:** `lib/Migrations/BundleAwareMigration.php:29-50`

- `Pimcore::getKernel()->getBundle($name)` throws `InvalidArgumentException` when the bundle is not registered (removed from
  `config/bundles.php` while its migrations path is still configured, e.g. in `config/packages/doctrine_migrations.yaml`), so
  `migrate` aborts instead of skipping.
- `checkBundleInstalled()` always returns `true`; the return value is meaningless. A bundle without installer (`getInstaller()`
  returns `null`) runs the migration.
- `skipIf()` throws `SkipMigration` in `preUp()`. Doctrine (3.9.5 `DbalExecutor`) records the version only after a successful
  `up()`, so a skipped migration stays "new" and is attempted again on every deployment. `doctrine:migrations:up-to-date` therefore
  fails as long as such a bundle is enabled but not installed (CI checks).

**Fix direction:** check `Kernel::getBundles()` with `isset()` and skip when missing; make `checkBundleInstalled(): void` (or
`isBundleInstalled(): bool` without side effects plus `skipIf()` in `preUp()`); document the "pending until installed" behaviour, or
let the installer's `markInstalled()` cover these versions (it already does when `getLastMigrationVersionClassName()` is set).

---

## Model

> Scope 25 notes: all 29 files in `lib/Model/**` were read, plus the consumers listed in the scope header. The behaviours marked
> "verified" were reproduced with plain `php -r` scripts that copy the relevant method bodies (PHP 8.2 CLI). No Symfony 7.4 /
> PHP 8.4 incompatibility was found; `doctrine/dbal` is `^3.8` (`composer.json:49`), so `Connection::quote($value, $type)` in
> `AbstractListing::quote()` still works – its DBAL 4 removal is part of models I-58 (DBAL 4 readiness), not repeated. Also not
> repeated: the `Loader` subclasses and `Factory` / `FallbackBuilder` (Scope 18: B-114–B-116, I-96–I-99), the object-type filter in
> `QueryBuilderHelperTrait` (models B-250), `LocationAwareConfigRepository` construction (B-23), the notification listing's
> `getOffsetLimit()` use (models B-195), `isValidOrderKey()` in the translation / unit listings (models B-119, I-171).

## [ ] B-148 – `AbstractModel::__call()` guard does not work: non-public model methods are silently delegated to the DAO

**File:** `lib/Model/AbstractModel.php:206-220`; affected pairs `models/Asset.php` / `Asset/Dao.php` (`update`, `updateChildPaths`),
`models/Document.php` / `Document/Dao.php` (`update`), `models/DataObject/AbstractObject.php` / `AbstractObject/Dao.php` (`update`),
`models/DataObject/Concrete.php` / `Concrete/Dao.php` (`update`, `saveChildData`), `models/User/AbstractUser.php` / `AbstractUser/Dao.php` (`update`)

- The comment says "protected / private methods shouldn't be delegated to the dao -> this can have dangerous effects", and the
  guard is `if (!is_callable([$this, $method]))`. Inside `__call()` the check runs in the class scope, where the model's own
  protected/private methods **are** callable, so the guard never throws.
- Verified: a model with `private function secret()` and a DAO with a public `secret()` → `$model->secret()` from outside returns
  the DAO's result.
- Real cases: `$asset->update()` (protected in the model) runs `Asset\Dao::update()` directly – a raw DB write of the current
  object without `PRE_UPDATE` / `POST_UPDATE` events, versioning, cache clearing, dependency update or path checks. The same for
  `Document`, `AbstractObject`, `Concrete` (`update`, `saveChildData`), `AbstractUser` (`update`) and `Asset::updateChildPaths()`.
- Twig resolves `{{ asset.update() }}` through `__call()`, so with the open sandbox (CoreBundle C-12) template authors reach these
  DAO methods as well (`save()` is reachable there anyway; the delegation additionally skips the model's logic).

**Fix direction:** reject when the model itself declares the method: `if (method_exists($this, $method)) throw new
BadMethodCallException(...)` (a public method would never reach `__call()`), then delegate only DAO methods from an explicit
allowlist (I-125).

## [ ] B-149 – `setValue()` "o_" compatibility branch calls the wrong method and always throws

**File:** `lib/Model/AbstractModel.php:175-190` (`:183-185`)

- For a key like `o_id` the branch checks `method_exists($this, 'setid')` but then calls `$this->$method()` with the **original**
  `$method` (`seto_id`). That goes to `__call()` → no DAO method → `BadMethodCallException: Call to undefined method seto_id`.
  Verified.
- Any hydration or import that still uses the Pimcore ≤ 10 `o_` column names (old exports, custom SQL with `o_*` aliases) fails
  instead of mapping the value.

**Fix direction:** call `'set' . preg_replace('/^o_/', '', $key)` in that branch, or drop the `o_` compatibility (Pimcore 11 renamed
the columns) with a deprecation first.

## [ ] B-150 – `AbstractModel::__sleep()` drops private properties of subclasses

**File:** `lib/Model/AbstractModel.php:192-199`

- `get_object_vars($this)` runs in `AbstractModel`'s scope and does not return `private` properties declared in subclasses. Those
  names are missing from the `__sleep()` list, so the values are **not serialized** (Pimcore cache, session bags, Messenger
  payloads, deep copies via `serialize()`). Verified: `class B extends AbstractModel { private int $priv = 1; }` serializes without
  `priv`.
- In core only transient flags are affected (`Asset\Image::$clearThumbnailsOnSave`, `ClassDefinition::$deletedDataComponents`,
  `Objectbrick\Definition::$oldClassDefinitions`); `Document\Editable` has its own `__sleep()`. Project models that extend a Pimcore
  model and use `private` properties silently lose them after a cache round trip.

**Fix direction:** `__serialize()` / `__unserialize()` built from `(array) $this` (includes mangled private names) minus the blocked
keys, or document that subclasses must use `protected` properties.

## [ ] B-151 – Listing conditions: duplicate `addConditionParam()` conditions overwrite each other, `setCondition()` keeps stale variables, array types guessed from the first element

**File:** `lib/Model/Listing/AbstractListing.php:189-207`, `:226-288`, `:295-309`, `:384-391`

- `addConditionParam()` stores the condition in a map **keyed by its SQL text** (`:200`). Two calls with the same text and
  different values – `addConditionParam('id = ?', 1, 'OR')`, `addConditionParam('id = ?', 2, 'OR')` – keep only the last one
  (verified with the same keying logic). The query silently returns fewer rows.
- `setCondition($sql)` without variables leaves the variables of an earlier `setCondition($sql, $vars)` in place (`:302-306`); they
  are merged into the parameters of the new condition (`:259`) → "Invalid parameter number" or wrong binding when a listing object is
  reused (e.g. paging code that switches conditions).
- Array parameters get `ArrayParameterType::STRING` only if the **first** element is a string, otherwise `INTEGER` (`:264-269`):
  `[1.5, 2.5]` is bound as integers (truncated to `1`, `2`) and `[1, 'abc']` binds `'abc'` as an integer. Exact driver behaviour for
  floats bound as `PARAM_INT` is **not verified locally**.
- `getConditionVariables()` / `getConditionVariableTypes()` recompute only when the stored variables are empty (`:66-70`, `:386-390`);
  after a later `addConditionParam()` / `setCondition()` an external caller gets stale values. The DAOs call `getCondition()` first,
  so the built-in load path is not affected.

**Fix direction:** store condition params as a list; reset `conditionVariablesFromSetCondition` to `null` in `setCondition()` when no
variables are given; determine array types from all elements (strings → `STRING`, ints → `INTEGER`, floats → `STRING`); always
recompute in the getters or invalidate the cached values in every setter.

## [ ] B-152 – `setLimit(0)` and `setOffset()` without limit behave differently in string-built and QueryBuilder listings

**Files:** `lib/Model/Listing/Dao/AbstractDao.php:70-81`; `lib/Model/Listing/Dao/QueryBuilderHelperTrait.php:125-129`;
`lib/Model/Listing/AbstractListing.php:101-124`

- String-built listings (`getOffsetLimit()`): `limit = 0` is falsy → **no limit** (all rows); an offset without limit is **ignored**.
- QueryBuilder listings (`applyLimitToQueryBuilder()`): `setMaxResults(0)` → `LIMIT 0` → **no rows**; an offset without limit is
  applied (DBAL emits a large limit).
- The same code (`$list->setLimit($perPage)` with a computed `0`, or `setOffset()` only) gives opposite results depending on which
  listing type is used. Negative values reach the SQL unchecked (models B-195 for one string-built listing).
- `setLimit(?int)` has an `is_numeric()` check that is dead for a typed `?int` parameter (`:105-109`).

**Fix direction:** one rule for both builders (`null` = no limit, `0` = no rows, reject negatives with `InvalidArgumentException`),
implemented once (I-127).

## [ ] B-153 – Column cache: `getValidTableColumns($table, false)` can return cached columns; resetting one table clears the whole `system` cache

**File:** `lib/Model/Dao/AbstractDao.php:64-105`

- `$cache = false` is only honoured on the persistent-cache path; if the runtime cache already holds the table (`:68-69`), the stale
  entry is returned. Code that ALTERs a table and re-reads its columns in the same process with `$cache = false` gets the old list.
- `resetValidTableColumnsCache($table)` removes the runtime entry and then calls `Cache::clearTags(['system', 'resource'])` (`:104`):
  every item tagged `system` (all table column lists and every other system cache entry) is cleared, instead of the one key
  `system_resource_columns_<table>`. Class / brick / field-collection saves call it per table, so a `classes-rebuild` of N classes
  clears the system cache N × (tables per class) times.
- The table name is concatenated unquoted into `SHOW COLUMNS FROM` (`:76`); internal callers only, but a table name with `-` fails.

**Fix direction:** skip the runtime cache when `$cache === false`; `Cache::remove($cacheKey)` for the single table; `quoteIdentifier()`.

## [ ] B-154 – `PimcoreLocationAwareConfigDao` keeps a static per-process config cache that other processes never invalidate

**File:** `lib/Model/Dao/PimcoreLocationAwareConfigDao.php:27`, `:54-74`, `:91-94`, `:129-132`; `models/Asset/Image/Thumbnail/Config.php:191-215`

- `self::$cache[$scope][$id]` lives for the process lifetime and is invalidated only by `saveData()` / `deleteData()` **in the same
  process**. Messenger workers and other long-running processes keep the old data of image / video thumbnail configs, predefined
  properties and metadata, custom layouts, select options, document types, static routes and custom reports after an admin edit.
- Example: `Thumbnail\Config::getByName()` checks `RuntimeCache` (cleared per message by `MessengerClearRuntimeCacheListener`) and
  then the DAO, which answers from the static cache. After a thumbnail config change, the admin clears the thumbnails
  (`CleanupThumbnailsMessage`) and a worker regenerates them with the **old** settings until it restarts. End-to-end run **not verified
  locally**; with the `symfony-config` write target the compiled container config in the worker is stale too (B-22 / B-23 area).
- `isWriteable()` uses `$this->id` / `$this->dataSource` of the last loaded config (`:131`); before any load both are `null`.
- Only truthy data is cached (`:66`); a config whose data is an empty array is re-read on every call.

**Fix direction:** move the cache into a service that implements `ResetInterface` (reset per message / request) or key it by a version
stamp from the settings store; pass the id explicitly to `isWriteable(string $id)`.

---

## Navigation

> Scope 26 notes: all 10 files in `lib/Navigation/**` were read, plus the consumers listed in the scope header. "Verified" items were run
> with the real `Container`, `Page`, `Page\Url` and `PrefixRecursiveFilterIterator` classes on the local PHP 8.2 CLI (with a polyfill for
> PHP 8.4's `array_any()`, which the code already uses – `composer.json:31` requires `~8.4.0`). No Symfony 7.4 / PHP 8.4 incompatibility
> was found. Not repeated: chained hardlinks dropped from the navigation (models B-341), `Document\Link::getHtml()` escaping
> (models B-145), `NavigationRendererPass` (CoreBundle TODO-3).

## [ ] B-155 – A navigation loaded from the cache has stale page keys: active-branch menus, `hasPage()`, `removePage()`, `addPage()` and `setParent()` misbehave

**Files:** `lib/Navigation/Page.php:1044-1047` (`hashCode()` = `spl_object_id($this)`), `:763-786`; `lib/Navigation/Container.php:118-145`,
`:195-226`, `:245-258`; `lib/Navigation/Renderer/Menu.php:752`, `:767`, `:770`; `lib/Navigation/Builder.php:135-178`

- `Container` keys `$_pages` / `$_index` by `spl_object_id()` of each page. `Builder::getNavigation()` caches the container (default
  `cache: true`) and later requests get it back via `unserialize()`. The objects then have **new** ids, but the array keys are the ids of
  the writing process; nothing (`__wakeup()` / `__unserialize()`) rebuilds them.
- Verified with the real classes: after a serialize/unserialize round trip, `$parent->hasPage($child)` returns `false` for its own child
  and `$parent->removePage($child)` returns `false` (child stays). In a simulation, new pages added after the load were silently dropped
  when their fresh id equalled a stale key (`addPage()` returns early, `:131-134`).
- `Menu::_renderMenu()` decides `onlyActiveBranch` and `expandSiblingNodesOfActiveBranch` with `$foundPage->hasPage($page)` and
  `$foundPage->getParent()->hasPage($page)` (`:752`, `:767`, `:770`). With a cached navigation these checks fail, so the children and
  siblings of the active page are not rendered (only the first, uncached request renders correctly). The renderer effect is derived from
  the code; the `hasPage()` result itself was verified.
- `Page::setParent()` removes the page from its old parent via `removePage($this)` (`:775`); with stale keys it stays there and is also
  added to the new parent → rendered twice.

**Fix direction:** give each page a stable identifier that survives serialization (an incrementing id assigned on construction and stored
in a property, or the document id + position), or implement `__unserialize()` in `Container` that re-keys `$_pages` / `$_index` from the
live objects. Add a cache round-trip test (I-133).

## [ ] B-156 – Navigation cache key collisions: `rootCallback` is ignored, `closureHash()` gives the same hash for different captured values

**Files:** `lib/Navigation/Builder.php:135-158`, `:170-178`; `lib/helper-functions.php:414-429`

- The cache key contains the root id, HTML prefix, root class, site, custom key, `pageCallback` hash and `maxDepth` (`:137-156`), but not
  the `rootCallback`. Two navigations of the same root with different `rootCallback`s (e.g. extra entries per area) share one cache entry;
  whichever is built first is served to both.
- `closureHash()` hashes the closure's source lines plus `json_encode($ref->getStaticVariables())`. Verified:
  - captured objects are encoded with their **public** properties only, so `function ($page) use ($user)` for two users with private
    fields gives the same hash;
  - captured strings with invalid UTF-8 make `json_encode()` return `false`, so all such closures hash to `md5('…false…')` – the same value;
  - `$this` of a bound closure and captured closures are not represented.
  Different `pageCallback` behaviour (per user, per locale object, per filter object) is then served from one cache entry.
- Whether editmode / preview requests (unpublished documents visible) can write the shared entry depends on whether the admin bundle
  disables the core cache there – **not verified locally** (admin UI not in this repo).

**Fix direction:** include a `rootCallback` hash; make `closureHash()` hash `serialize()`-able static variables with a fallback to
`spl_object_id()` (no caching) for non-serializable ones, and document that callbacks capturing request-specific state need an explicit
`cache: '<key>'`.

## [ ] B-157 – Active page detection by URI never finds pages below folders or below pages whose URI is not a prefix of their children's

**Files:** `lib/Navigation/Iterator/PrefixRecursiveFilterIterator.php:47-71`; `lib/Navigation/Builder.php:191-255`, `:266-273`, `:370-380`

- `findActivePages()` walks the tree with `PrefixRecursiveFilterIterator`, which only descends into a page if that page's property is a
  string prefix of the searched value (`:58`). Folder pages never get a URI (`Builder.php:378-380`), so their whole subtree is skipped. The
  same applies below a page whose URI carries `navigation_parameters` / `navigation_anchor`, and below `Link` documents pointing elsewhere.
- Verified: `/en/products/shoes` under a folder and `/en/about/team` under `/en/about?x=1` are not found; a top-level `/en/contact` is.
- The `realFullPath` pass (`:208-211`) only runs when the caller passes `active`; the option defaults to `null`, so a template that only
  calls `pimcore_build_nav({root: …})` gets no active state for those pages.
- The fallback trail (`:236-254`) compares the document's **real** path with page URIs, which are site-relative in site requests; it also
  ignores pages from `rootCallback` that are not `DocumentPage`s, and `addActiveCssClasses()` stops at the first non-`DocumentPage`
  parent (`:288-292`).

**Fix direction:** search the whole tree (a plain `RecursiveIteratorIterator` with an exact-match filter; the tree is small) or treat a
`null` property as "descend"; compare path-only URIs (strip query / fragment); use `getFullPath()` consistently in site requests.

## [ ] B-158 – `Builder` keeps per-call state on a shared service; small build defects

**File:** `lib/Navigation/Builder.php:41-50`, `:111-186`, `:346-420`

- `$htmlMenuIdPrefix`, `$currentLevel` and `$navCacheTags` are instance properties of a shared service. A `pageCallback` or `rootCallback`
  that builds another navigation (mega-menus, footer from inside the main nav) resets `navCacheTags` and overwrites `htmlMenuIdPrefix` for
  the outer build: the outer navigation is cached with the inner tags only (no invalidation on save of its own documents) and the
  remaining items get the wrong id prefix.
- `Document::getById(1)` can return `null` (`:130`); the next line calls `getId()` on it.
- `setPageClass()` / the constructor accept any string; `new $this->pageClass()` is assumed to be a `DocumentPage` (`:377`).
- `navigation_parameters` is appended to the path as is (`:379`): a link href that already has a query string plus parameters starting with
  `?` gives `…?a=1?b=2`.
- Property values are passed to typed setters (`setLabel(?string)`, `setTarget(?string)`, `setTabindex(?string)`, …); a property of
  another type with one of these names (e.g. a `document` property `navigation_name`) is a `TypeError` that breaks the whole navigation
  (`strict_types`).

**Fix direction:** move build state into a local context object passed through `buildNextLevel()`; guard the root; validate the page class
(`is_a($class, DocumentPage::class, true)`); merge query strings with `parse_str()` / `http_build_query()`; cast or validate property
values.

## [ ] B-159 – Renderer defects: tabindex / rel are never rendered, `null` labels crash breadcrumbs, link targets are not scheme-checked

**Files:** `lib/Navigation/Renderer/Menu.php:439-470`, `:739-741`; `lib/Navigation/Renderer/AbstractRenderer.php:266-286`;
`lib/Navigation/Renderer/Breadcrumbs.php:200-207`; `lib/Navigation/Page/Document.php:24-72`; `lib/Navigation/Builder.php:387-389`

- The builder copies `navigation_tabindex` and `navigation_relation` into every page (`setTabindex()`, `setRelation()`), but no renderer
  outputs `tabindex` or `rel` (`Menu::htmlify()` renders id, title, class, href, target, accesskey only). These document settings have no
  effect.
- `AbstractRenderer::htmlify()` (used by breadcrumbs) calls `htmlspecialchars($label, …)` without a cast (`:284`), and
  `Breadcrumbs::renderStraight()` does the same with `$active->getLabel()` (`:205-206`). A page without label (custom `Url` page,
  `renderInvisible`) → `TypeError` under `strict_types`. `Menu::htmlify()` casts (`:468`).
- `$ulClasses[$depth] ?? $ulClasses['default']` (`:741`) emits an "undefined array key" warning when an array of classes has no `default`.
- `href` values are escaped but not scheme-checked: a `Link` document with a direct `javascript:` target is rendered as a clickable
  `javascript:` link in menus and breadcrumbs (stored XSS by editors; whether the admin UI accepts such targets is **not verified
  locally**; the related `Link::getHtml()` issue is models B-145).

**Fix direction:** render `tabindex` / `rel` from `DocumentPage` (or drop the properties); cast labels to string in all renderers; use
`$ulClasses['default'] ?? ''`; allow only `http(s):`, `mailto:`, `tel:`, relative and fragment URLs in `href` (configurable).

## [ ] B-160 – `Container` / `Page` small defects

**Files:** `lib/Navigation/Container.php:195-226`, `:282-450`, `:457-464`; `lib/Navigation/Page.php:170-205`, `:411-421`;
`lib/Navigation/Page/Url.php:75-89`; `lib/Navigation/Page/Document.php:50-55`

- `removePage(int $order, true)` passes the int to `hasPage(Page …)` in the recursive branch (`:216`) → `TypeError`.
- `findOneBy()` / `findAllBy()` with `$useRegex = true` call `preg_match($value, $pageProperty)` also when the property is `null`
  (folders have no URI) → `TypeError` under `strict_types`. Non-regex matching uses loose `==` (`null == ''`, `'1e1' == '10'`).
- `Container::__call()` reads `$arguments[0]` without a check (`$nav->findByLabel()` → warning) and suppresses `preg_match()` errors with `@`.
- `Url::getHref()` calls `str_ends_with($uri, '#')` when a fragment is set and the URI is `null` → `TypeError` (verified with plain PHP).
- `Page::factory()` instantiates the class from `$options['type']` before checking that it extends `Page` (`:181-189`): any class
  constructor runs with the options array.
- `Page::setAccesskey()` checks `strlen() === 1` (bytes), so a single multibyte character (`ü`) is rejected; `Page\Document::setAccesskey()`
  overrides it without any validation, so the builder can set a multi-character access key.

**Fix direction:** type-check `$page` in the recursive branch; skip non-string properties for regex matching and use `===`; validate
arguments in `__call()`; `$uri ?? ''` in `getHref()`; check `is_subclass_of($type, Page::class)` before `new`; `mb_strlen()` and one
validation in both setters.

---

## Normalizer

> Scope 27 notes: `lib/Normalizer/**` contains only `NormalizerInterface` (28 lines). It was checked against all 44 implementing classes
> (every core field type implements it directly or through its parent – `Country` / `Language` via `Select`, `Time` / `Email` via
> `Input`, the advanced relations via their base relation) and the six callers in the scope header. No Symfony 7.4 / PHP 8.4
> incompatibility was found. The per-type `normalize()` / `denormalize()` defects are tracked in the models TODOs and not repeated
> (e.g. models B-268 `DateRange`, `Date::normalize()` storing `false`, the `Password` hashing gap in models TODO-1, the classification-store
> name resolution in models TODO-2).

## [ ] B-161 – A field type without `NormalizerInterface` is handled differently by every container: thrown, silently dropped, stored as `NULL`, or passed through raw

**Files:** `lib/Normalizer/NormalizerInterface.php:20-28`; `models/DataObject/ClassDefinition/Data/Block.php:127-148`, `:193-208`;
`models/DataObject/ClassDefinition/Data/Localizedfields.php:979-982`, `:1013-1016`; `models/DataObject/ClassDefinition/Data/Objectbricks.php:831-836`;
`models/DataObject/Classificationstore/Dao.php:126-146`, `:248-264`; `models/DataObject/ClassDefinition/Data/Classificationstore.php:1050-1052`;
`models/DataObject/ClassDefinition/Data/EncryptedField.php:389-403`

- All core field types implement the interface, but project field types (custom `Data` classes registered via
  `pimcore.objects.class_definitions.data.map`) often do not, because nothing requires it. Their values are then treated like this:
  - **`Objectbricks::normalize()`** throws `"<field> does not implement NormalizerInterface"` (`:835`) – explicit.
  - **`Block::getDataForResource()`** and **`Localizedfields::normalize()`** skip the element without a log entry (`Block.php:127-148`,
    `Localizedfields.php:979-982`): the value of that field inside a block / a block's localized fields is **not saved**, and the editor
    is not told.
  - **`Classificationstore\Dao::save()`** writes a row with `value = NULL` (`:145-146`); on load it passes the raw row array
    `['value' => …, 'value2' => …]` as the field value (`:248-264` only unpacks for normalizer types). The stored value is lost and the
    loaded value has the wrong shape.
  - **`Classificationstore::normalize()`** (export / API) and **`EncryptedField`** pass the raw value through (`:1050-1052`, `:389-403`).
- The interface does not document which `$params` keys callers pass: `Block` passes `['object', 'fieldDefinition']`, `Localizedfields`,
  `Objectbricks` and `Classificationstore` forward their own `$params`, `EncryptedField` forwards whatever it got. Implementations cannot
  rely on `object` being present.
- Nothing is verified at class-definition save time; the problem only shows up as missing data.

**Fix direction:** require the interface for every field type that can be placed in a block, localized field, brick, field collection or
classification store – validate in the class / brick / field-collection definition save (`ClassDefinition::save()` and the container
`Data::validate…` hooks) and throw a `ValidationException` naming the field; or fall back to `SimpleNormalizerTrait` semantics (identity)
consistently everywhere and log a deprecation. Document the `$params` keys (I-134).

---

## Request

> Scope 28 notes: `lib/Request/**` contains only the `DataObjectParam` attribute (35 lines). No Symfony 7.4 / PHP 8.4 incompatibility was
> found. No new bug: every defect of the attribute shows up in its only consumer and is already tracked in
> [CoreBundle TODO-2](../bundles/CoreBundle/TODO-2-BUGS.md) – B-62 (folder-typed / `AbstractObject` arguments) and B-63 (`$options` is never
> read, `class` is not checked against the argument type, nullable handling, string-ID deprecation) – and in CoreBundle TODO-3 I-66 (targeted
> resolver, typed attribute lookup). Those points are not repeated; the attribute-side improvements are in TODO-3 I-136.

---

## Resolver

> Scope 29 notes: both files in `lib/Resolver/**` were read (59 lines), plus the consumers listed in the scope header. No Symfony 7.4 / PHP 8.4
> incompatibility was found. Not repeated: the unusable generic `Pimcore\Resolver\ClassResolver: ~` service definition (CoreBundle TODO-3,
> `class_resolvers.yaml`), class-name normalisation of leading `\` (B-116), and the unrelated `ClassDefinition\Helper\ClassResolver`
> (models B-276).

## [ ] B-162 – `ClassResolver::resolve()` returns `null` for an unknown type, and `Document::getById()` / `Asset::getById()` / `Asset::save()` pass it on → `TypeError`

**Files:** `lib/Resolver/ClassResolver.php:30-33`; `models/Document.php:226-229`; `models/Asset.php:287-289`, `:779-781`;
`bundles/CoreBundle/src/DependencyInjection/PimcoreCoreExtension.php:215-219`, `:278-291`

- `resolve()` returns `?string` – `null` when the type is not in `documents.type_definitions` / `assets.type_definitions`.
- `Document::getById()` and `Asset::getById()` call `$factory->build($className)` with that value; `Factory::build(string $name)` gets `null`
  → `TypeError` (also in `Asset.php`, which is not `strict_types`, because `null` is never coerced for userland parameters).
  `Asset::save()` calls `supports($className)` with it (`:779-781`), so the intended "unable to resolve asset implementation" exception is
  never reached.
- When it happens: a row whose type is no longer configured – an asset / document type registered by a bundle or project config that was
  later removed, or a `documents.type` stored as `''` (the column is an `ENUM`; with a non-strict `sql_mode` unknown values become `''`,
  see models TODO-3 I-84). Every `getById()` of such an element (tree, relations, link targets, listings that call `getById()`) then fails
  with a 500 instead of a clear error or a fallback.
- The other callers handle `null`: `Asset::create()` throws `InvalidArgumentException` (`:396-397`), the relation trait skips the type
  (`Relation.php:147-153`).

**Fix direction:** in the two `getById()` methods keep the base instance (or `Asset\Unknown`) and log a warning when the type cannot be
resolved, or throw a dedicated `UnknownElementTypeException` with id and type; in `save()` check for `null` before `supports()`. Long term
`resolve()` could throw and a `tryResolve()` return `?string` (I-137).

---

## Routing

> Scope 30 notes: all 12 files in `lib/Routing/**` were read, plus the consumers listed in the scope header. No Symfony 7.4 / PHP 8.4
> incompatibility was found (`symfony-cmf/routing-bundle ^3.0`, `composer.json:78`; the loaders extend the Symfony 7 `AttributeRouteControllerLoader`
> / `Loader` APIs). Symfony routing behaviour was checked with **symfony/routing 6.4.18** from another local project (same compiler rules in
> 7.4, **not verified locally**). Checked and not a defect: the case / trailing-slash redirect compares decoded paths on both sides
> (`rawurldecode()` in `DynamicRouteProvider.php:68` and `DocumentRouteHandler.php:289`), so non-ASCII keys do not loop. Not repeated:
> `getNearestDocumentByPath()` cache key (models TODO-1), slug deletion in `UrlSlug::getAction()` (models C-31), chained hardlinks (models
> B-341), bundle routing file location (B-24), the admin route-name replacement (CoreBundle TODO-3).

## [ ] B-163 – Document keys and URL slugs containing `{…}` are used as Symfony route patterns: 500 on match, broken URL generation

**Files:** `lib/Routing/Dynamic/DocumentRouteHandler.php:169-175`; `lib/Routing/Dynamic/DataObjectRouteHandler.php:86`;
`models/Element/Service.php:954-956`

- `new DocumentRoute($document->getFullPath())` and `$route->setPath($context->getOriginalPath())` put the document path into the route
  **pattern**; `new DataObjectRoute($slug->getSlug())` does the same with the slug. In a Symfony route pattern `{name}` is a placeholder.
- `getValidKey()` for documents replaces `#?*:\<>|"%&@=;+` but keeps `{` and `}`; slugs are not restricted either.
- Verified with symfony/routing 6.4: path `/en/offer-{2024}` → `DomainException: Variable name "2024" cannot start with a digit` on
  **matching** (the page answers 500) and on generation; `/en/{special}-offer` matches (the placeholder accepts the literal text) but
  generating the `document_<id>` route throws `MissingMandatoryParametersException`.

**Fix direction:** keep the real path out of the pattern – e.g. a fixed pattern `/{documentPath}` with requirement `.*` and the path as
default / parameter – or reject `{` / `}` in document keys and slugs (`getValidKey()`, slug validation) with a migration that renames existing
keys.

## [ ] B-164 – Unpublished link documents keep redirecting anonymous visitors

**File:** `lib/Routing/Dynamic/DocumentRouteHandler.php:180-201`, `:221-227`

- The publish check exists only for direct-route documents (`handleDirectRouteDocument()`, `:223-227`). Link documents go to
  `handleLinkDocument()` (`:183-186`), which always builds the `urlRedirectAction` route. Unpublishing a link document in the admin hides it
  from navigations, but its URL still redirects every visitor to the target.
- `Document::getByPath()` (`:107`) does not filter unpublished documents, so nothing else stops it.

**Fix direction:** apply the same `isPublished()` / admin / `forceHandleUnpublishedDocuments` check to link documents before building the
redirect route.

## [ ] B-165 – `DynamicRouteProvider::getRouteByName()` returns `null` from the document handler → `TypeError` instead of `RouteNotFoundException`

**Files:** `lib/Routing/DynamicRouteProvider.php:82-93`; `lib/Routing/Dynamic/DocumentRouteHandler.php:92-103`, `:158-167`, `:223-226`;
`lib/Routing/Dynamic/DynamicRouteHandlerInterface.php:37`

- The handler interface allows `?Route`, and `DocumentRouteHandler::getRouteByName()` returns `buildRouteForDocument()` as is – `null` for an
  unpublished document outside admin mode and for a hardlink whose source cannot be wrapped.
- `DynamicRouteProvider::getRouteByName(): SymfonyRoute` returns that value directly (`:86`) → `TypeError: Return value must be of type Route,
  null returned`. Generating `path('document_<id>')` for such a document (or reusing `app.request.attributes.get('_route')`, which is
  `document_<id>` on document pages) gives a 500 instead of the normal "route not found" handling of the chain router.

**Fix direction:** throw `RouteNotFoundException` in the handler when the build returns `null`, tighten the interface to `Route` (throwing),
or check for `null` in the provider and continue with the next handler.

## [ ] B-166 – `Element\Router` / object routes: query string and port of absolute asset URLs dropped, wrong site on generated object routes, empty CMF content

**Files:** `lib/Routing/Element/Router.php:69-110`; `lib/Routing/Dynamic/DataObjectRouteHandler.php:46-60`, `:83-100`;
`lib/Routing/DataObjectRoute.php:82-85`; `lib/Routing/Dynamic/DocumentRouteHandler.php:78-90`

- For absolute element paths (asset frontend prefix / CDN, `ASSET_PATH` event listeners), `generate()` keeps only scheme, host and
  path from `parse_url()` (`:83-87`): a query string (cache-busting `?v=…`, signed CDN URLs) and a fragment are dropped, and the port is taken
  from the request context instead of the URL. Outside the frontend (`Tool::isFrontend()` false: CLI mails, static page generation) assets
  get their raw, un-encoded path without the configured prefix (`Asset::getFullPath()`).
- `DataObjectRouteHandler::getRouteByName()` resolves the slug for the site id encoded in the route name, but `buildRouteForFromSlug()` sets
  the **current** request's site on the route (`:85`, `:90`), so the generated route's key and site differ from the requested one.
- `DataObjectRoute::getContent()` returns `null` although the route carries the object (`:82-85`), so CMF content-aware features
  (`_content` / `contentDocument` request attributes) are never set for object routes, unlike document routes.
- `getDirectRouteDocumentTypes()` caches only a non-empty result (`:80`); with no direct-route types configured it re-reads the system config
  on every call.

**Fix direction:** keep query / fragment / port from the parsed URL; use `getFrontendFullPath()` for URL generation; pass the slug's site
to `buildRouteForFromSlug()`; return the object from `getContent()`; cache the direct types with `??=` including the empty array.

---

## SearchClient

> Scope 31 notes: both files in `lib/SearchClient/**` were read (an interface with 27 methods and a `final` exception, 192 lines). Nothing in this
> repository implements or calls `SearchClientInterface`; the implementations live in external packages and were **not verified locally**. No
> Symfony 7.4 / PHP 8.4 incompatibility and no bug was found; the contract-level points are improvements (TODO-3 I-140).

---

## Security

> Scope 32 notes: all 18 files in `lib/Security/**` were read, plus the consumers listed in the scope header. Symfony password-hasher
> behaviour was checked with **symfony/password-hasher 6.4** from another local project (**not verified locally** against 7.4). The admin
> `User` / `UserProvider` / `UserChecker` defects are in the models TODOs (C-40, C-41, B-232, B-235) and not repeated. The critical
> worker-runtime identity leak is TODO-1 C-11.

## [ ] B-167 – User-aware hashers are cached per identifier with the first user object: stale passwords accepted, stale objects saved, identifier collisions

**Files:** `lib/Security/Hasher/Factory/UserAwarePasswordHasherFactory.php:50-79`; `lib/Security/Hasher/PasswordFieldHasher.php:64-97`;
`lib/Security/Hasher/PimcoreUserPasswordHasher.php:44-51`; `lib/Security/Hasher/AbstractUserAwarePasswordHasher.php:31-47`;
`models/DataObject/ClassDefinition/Data/Password.php:232-253`

- `UserAwarePasswordHasherFactory` keeps `$this->hashers[$userIdentifier]` for the lifetime of the (shared) service, and each hasher holds the
  user object it was built for (`setUser()` refuses to replace it).
- `PasswordFieldHasher::verify()` / `isPasswordValid()` and `PimcoreUserPasswordHasher::verify()` ignore the `$hashedPassword` that Symfony
  passes and verify against **the cached object** (`getFieldDefinition()->verifyPassword($plain, $this->getUser())`,
  `Authentication::verifyPassword($this->getUser()->getUser(), …)`).
- In a long-running process (worker runtime, a CLI import that authenticates several times) a password change is therefore ignored for that
  identifier: the old password keeps working and the new one fails until the process restarts.
- `Password::verifyPassword()` re-hashes and calls `$object->save()` when the hash parameters changed (`:248-252`) – on the **cached, stale**
  object, overwriting every change made to that user object since it was first loaded.
- One factory service mapped to several user classes (`password_hasher_factories`) shares one cache: user B of class 2 with the same
  identifier as user A of class 1 is verified against A's object. Users without a name share the key `''` (`User::getUserIdentifier()`
  returns `?? ''`).

**Fix direction:** build a hasher per call (they are cheap) or key the cache by `spl_object_id($user)` with a `WeakMap`; verify against the
`$hashedPassword` argument (`password_verify()`) and use the object only for the optional re-hash; reload the object before saving a new hash.

## [ ] B-168 – `PasswordFieldHasher::hash()` returns the plaintext password; `PimcoreUserPasswordHasher` checks the length of the hash instead of the password

**Files:** `lib/Security/Hasher/PasswordFieldHasher.php:31-62`; `lib/Security/Hasher/AbstractUserAwarePasswordHasher.php:27`;
`lib/Security/Hasher/PimcoreUserPasswordHasher.php:44-51`; `models/DataObject/ClassDefinition/Data/Password.php:135-170`

- The user-aware hashers extend Symfony's `PlaintextPasswordHasher`. `PasswordFieldHasher` implements the legacy encoder method
  `hashPassword($raw, $salt)` but not `hash()`, so `hash()` is the inherited plaintext implementation. Verified with symfony/password-hasher
  6.4: `hash('secret')` returns `secret`, `needsRehash()` returns `false`.
- Symfony's `UserPasswordHasherInterface::hashPassword($objectUser, $plain)` – the standard way to hash in registration / reset-password
  controllers – thus returns the plaintext. Saved through the `Password` field it is hashed on save (`getDataForResource()`), but with the
  deprecated non-`password_hash` algorithms a plaintext that looks like a hex digest (32/40/64/… hex chars) is treated as "already hashed" and
  stored as is (`:146-163`); any other use of the returned value (a custom field, an API payload, logs) carries the plaintext.
- `needsRehash()` is always `false`, so Symfony's password-migration listener never upgrades object users (Pimcore's own re-hash in
  `verifyPassword()` partly compensates).
- `PimcoreUserPasswordHasher::verify()` calls `isPasswordTooLong($hashedPassword)` (`:46`) – the stored hash, which is never longer than 4096
  characters – so the length guard against very long submitted passwords never triggers.

**Fix direction:** implement `hash()` (delegating to `calculateHash()`) and `needsRehash()` in `PasswordFieldHasher`, stop extending
`PlaintextPasswordHasher` (implement `PasswordHasherInterface` directly); check `$plainPassword` in `PimcoreUserPasswordHasher::verify()`.

## [ ] B-169 – `ObjectUserProvider`: deleted users cause a `TypeError`, unpublished users stay logged in, class overrides break the session

**File:** `lib/Security/User/ObjectUserProvider.php:78-103`

- `refreshUser()` returns `getById($user->getId())` as is (`:97`). For a deleted object that is `null` → `TypeError` (return type
  `UserInterface`) on every request of that session instead of `UserNotFoundException`, which Symfony would turn into a logout.
- `getById()` ignores the published state and nothing re-checks the user on refresh (there is no `UserChecker` for object users), so
  unpublishing a customer object does not end their session.
- `supportsClass()` compares with `===` (`:100-103`). With a model override (`pimcore.models.class_overrides`) or a subclass, the logged-in user
  is an instance of another class; Symfony's `ContextListener` finds no provider that supports it and throws "There is no user provider for user
  …", so every request after login fails. `refreshUser()` itself accepts subclasses (`instanceof`), the two methods disagree.
- `loadUserByIdentifier()` calls `getBy<Field>($username, 1)`: if the username field is not unique, the first matching object is used and its
  password is checked – login as an arbitrary one of the duplicates.

**Fix direction:** throw `UserNotFoundException` when the refreshed object is missing or unpublished; `is_a($class, $this->className, true)` in
`supportsClass()`; document (and ideally validate) that the username field must be unique.

## [ ] B-170 – Smaller hasher / user defects

**Files:** `lib/Security/Hasher/PasswordHasherFactory.php:60-89`; `lib/Security/Hasher/PasswordHasherFactoryAwareInterface.php`;
`lib/Security/User/User.php:28-36`, `:94-120`

- A configured factory that is not a `UserAwarePasswordHasherFactory` is matched but then ignored (`:83-85` return `null`), so the framework
  default hasher is used silently.
- `getHasherFactoryName(): string` – the doc says "If the method returns null, the standard way … will be used", but the return type does not
  allow `null`; the `null !==` check in the factory (`:64`) is dead and an implementing user cannot opt out.
- String user classes are compared with `$user == $class` (`:72`) – loose comparison.
- `User::getGoogleAuthenticatorUsername(): string` returns `getName()`, which is `?string` → `TypeError` for a user without a name.

**Fix direction:** support any `PasswordHasherFactoryInterface` in the delegation (or reject other types at container build); make
`getHasherFactoryName(): ?string`; `===`; `?? ''` for the 2FA username.

---

## Serializer

> Scope 33 notes: both files in `lib/Serializer/**` were read (78 lines), plus the consumers listed in the scope header. The "verified" points
> were run with the real `ReferenceLoopNormalizer` and `Tool\Serialize` against **symfony/serializer 6.4** from another local project
> (**not verified locally** against 7.4). Not repeated: `JsonHelperTrait` cleanup (I-33), `SerializerPass` / `'@?'` placeholders (CoreBundle
> TODO-3), `Asset::__clone()` closing the stream (models C-07).

## [ ] B-171 – `ReferenceLoopNormalizer`: clones every object (with `__clone()` side effects), drops non-public properties, misses loops in `jsonSerialize()` output

**Files:** `lib/Serializer/Normalizer/ReferenceLoopNormalizer.php:29-52`; `lib/Tool/Serialize.php:61-101`; `lib/Controller/Traits/JsonHelperTrait.php:45-71`

- It is the only normalizer of `pimcore.serializer`, which `JsonHelperTrait::jsonResponse()` / `encodeJson()` use by default for controller
  JSON responses.
- `Serialize::removeReferenceLoops()` **clones every object** in the payload (`:76`) to replace cycles. `__clone()` runs on the originals'
  copies (verified): for Pimcore models that resets the DAO, and `Asset::__clone()` closes the original asset's pending stream (models C-07) –
  serializing an asset that has unsaved binary data drops that data.
- Objects are reduced to `get_object_vars()` from outside the class, i.e. **public properties only** (`:37-38`, and `Serialize.php:87`).
  Verified: an object with `protected int $id` and `public string $name` serializes to `{"name":"x","self":null}`. Pimcore models (protected
  properties) become empty objects unless they implement `JsonSerializable`. The `array_map(fn ($v) => $v, …)` is an identity function.
- For `JsonSerializable` the loop filter runs **before** `jsonSerialize()`; cycles inside the returned data are not removed. Verified:
  `jsonSerialize()` returning a self-referencing object → `NotEncodableValueException: Recursion detected`.
- The return type is `array|string|int|float|bool|ArrayObject|null`; a `jsonSerialize()` that returns any other object (e.g. `stdClass`) is a
  `TypeError`.

**Fix direction:** use Symfony's `JsonSerializableNormalizer` + `ObjectNormalizer` (or `PropertyNormalizer`) with
`AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER` instead of cloning; if the custom normalizer stays, walk the graph without `clone` (track
visited objects in an `SplObjectStorage` and build arrays), apply the loop filter to the `jsonSerialize()` result, and widen/handle object
results.

---

## Session

> Scope 34 notes: all 3 files in `lib/Session/**` were read (142 lines). `LockableAttributeBag` / `LockableAttributeBagInterface` /
> `AttributeBagLockedException` have **no consumer** in this repository (`bundles/`, `models/`, `lib/`, `tests/`) – the last in-repo user went away with the AdminBundle extraction (`c6ca4331a8`, #14908, 2023-04-12); the admin session bags
> (`pimcore_admin`, see `lib/Tool/Session.php`) are registered by the external admin bundle, which is not part of this checkout – whether it
> still uses the lockable bag was **not verified**. Both findings are therefore latent: they hit any project or third-party bundle that
> registers the bag. Symfony behaviour is described from the 7.x source (`Session::invalidate()` → `$storage->clear()` →
> `$bag->clear()` for every registered bag); there is no `vendor/` in this checkout, so it was **not verified locally** against 7.4.

## [ ] B-172 – A locked bag makes `Session::invalidate()` throw: logout / session invalidation fails with a 500 and the session is not cleared

**Files:** `lib/Session/Attribute/LockableAttributeBag.php:63-68`, `:72-79`; Symfony `Session::invalidate()`,
`NativeSessionStorage::clear()`, `MockArraySessionStorage::clear()`, `SessionLogoutListener`

```php
public function clear(): mixed
{
    $this->checkLock();              // throws while locked
    return parent::clear();
}
```

- `clear()` is not only a user-facing API: the session **storage** calls `clear()` on every registered bag in `NativeSessionStorage::clear()`
  (and `MockArraySessionStorage::clear()` in tests), which `Session::invalidate()` runs before `migrate(true)`.
- `invalidate()` is used by Symfony's `SessionLogoutListener` (logout with `invalidate_session: true`, the default), by
  `SessionAuthenticationStrategy::INVALIDATE`, and by application code after a password change / privilege change.
- If a bag is still locked at that moment, `AttributeBagLockedException` aborts the loop: the remaining bags are not cleared, `$_SESSION` is not
  reset and the session id is not regenerated. Result: a 500 on logout and an authenticated session that survives the attempt to destroy it
  (security relevant once a consumer exists – that is why it would be critical with a consumer, and a bug while there is none).
- The lock is also inconsistent: `initialize()` is **not** guarded, so the storage may rebind a "locked" bag to new data
  (`loadSession()` after `migrate()` / `start()`), but may not clear it.

**Verify:** unit test with `MockArraySessionStorage` + `Session`: register a `LockableAttributeBag`, `lock()`, call `$session->invalidate()` →
per code `AttributeBagLockedException`; expected `true` and an empty session.

**Fix direction:** the lock must protect against application writes, not against the session lifecycle. Either do not guard `clear()` (only
`set()` / `replace()` / `remove()`), or let the storage path through (e.g. `clear()` unlocks first – the bag is emptied anyway). Document that
`invalidate()` / `clear()` always wins over the lock.

## [ ] B-173 – The lock is shallow and per-object: stored objects stay mutable, the lock is lost between requests

**Files:** `lib/Session/Attribute/LockableAttributeBag.php:25`, `:42-61`; `lib/Session/Attribute/LockableAttributeBagInterface.php:24-37`

- The interface promises "Lock the attribute bag (disallow modifications)", but only the four mutator methods are guarded. `get()` returns
  objects by handle: `$bag->get('cart')->addItem(…)` modifies a locked bag, and the change is written to the session at `save()` (the whole
  `$_SESSION` is serialised). Arrays are returned by value, so the behaviour differs by value type.
- `AttributeBag::initialize(array &$attributes)` binds `$this->attributes` to the storage array by reference; any code that writes through
  the storage (`$_SESSION[$storageKey]`, another bag sharing the storage key, `MockArraySessionStorage::setSessionData()`) bypasses the lock.
- `$locked` lives on the PHP object only; it is not stored in the session. A bag locked in request A is unlocked in request B (new bag
  instance), but a bag instance that is reused across requests (registered once on a long-lived session in a worker runtime / tests) keeps the
  lock of a previous request – there is no `ResetInterface`/reset hook. The state model is not documented either way.
- `lock()` / `unlock()` are not paired or nested: two components that lock and unlock independently unlock each other (the first `unlock()` wins).

**Fix direction:** document the lock as "guards the bag API only, per instance, per request" or make it real: return clones / read-only views
from `get()` while locked, keep a lock counter (or a scoped `withLock(callable)` that restores the previous state in `finally`), and reset
the lock when the bag is (re)initialised (`initialize()` → `$this->locked = false`).

---

## Templating

> Scope 35 notes: all 4 files in `lib/Templating/**` were read (474 lines), plus the consumers listed in the scope header. Symfony
> (`InlineFragmentRenderer` forwarding `_locale` / `_format`, `HttpKernelRuntime::renderFragment()` reading `strategy`, `FragmentUriGenerator`
> rejecting non-scalar attributes) and Twig behaviour are described from the 7.x / 3.x source; there is no `vendor/` in this checkout, so
> nothing was executed and it is **not verified locally**. Not repeated: the open sandbox policy (CoreBundle C-12), `ignore_errors` /
> sub-request exception handling (CoreBundle B-37, B-43), `Controller::render()` delegate path (B-39 – see I-147:
> the path is dead), `defaultValue` `TypeError` through `EditableRenderer` (models B-348), the mail subject message cut (Mail scope).

## [ ] B-174 – `pimcore_inc()` output cache key ignores site and locale: the include rendered for one site / language is served on all others

**Files:** `lib/Templating/Renderer/IncludeRenderer.php:80-106`, `:119-124`; compare `models/Document/Editable/Snippet.php:124-126`

```php
$cacheParams = $params;
$cacheParams['~~include-document'] = $originalInclude;
...
$cacheKey = 'tag_inc__' . md5(serialize($cacheParams));   // no site, no locale, no host
```

- The key is built only from the template parameters and the include reference. The rendered content of the same snippet usually depends on
  the request: `Site::getCurrentSite()` (navigation, links, `pimcore_url()` with site prefixes), the locale (translations, `app.request.locale`),
  the host.
- `InlineFragmentRenderer` forwards the parent's `_locale` to the sub-request when the attribute is not set; `ActionRenderer` sets `_locale`
  only if the **included** document has a `language` property (`ActionRenderer.php:90-92`). A shared snippet without that property therefore
  renders in the parent page's language – and the first language rendered is cached for all languages.
- The `pimcore_snippet` editable adds `siteId` to its key for exactly this reason (`Snippet.php:124-126`); `IncludeRenderer` does not.
- With the output cache enabled, the first site / language that renders `{{ pimcore_inc('/shared/footer') }}` fills the cache entry for all
  sites and languages until the `output` tag is cleared (or, with a lifetime, until it expires – the `output_lifetime` tag is not cleared by
  document saves).

**Verify:** two sites (or two languages) whose layout includes the same snippet that prints the current site id / locale; enable the full-page
cache; request site A, then site B → per code B shows A's value.

**Fix direction:** add `Site::isSiteRequest() ? Site::getCurrentSite()->getId() : 0`, the request locale and the host to `$cacheParams`;
better: one shared key builder for `IncludeRenderer` and the `Snippet` editable (I-149) so both stay consistent.

## [ ] B-175 – Building the include cache key can throw or serialise whole object graphs (500 only when the output cache is on)

**Files:** `lib/Templating/Renderer/IncludeRenderer.php:86-105`

- `array_walk()` only normalises **top-level** values: elements become ids, `__toString()` objects become strings. Nested values stay as they
  are: `{{ pimcore_inc('/x', {'items': products}) }}` (array / listing of elements) serialises every element including its loaded relations;
  a closure, generator, `PDO`, `\Dom\…` or any non-serialisable object anywhere in the params → `Exception: Serialization of 'Closure' is not
  allowed` from `serialize()` (`:102`).
- Top-level objects that are neither elements nor `Stringable` (DTOs, `Request`) are serialised too – expensive and
  fragile (private state, lazy proxies).
- Because this code runs only when `Frontend::isOutputCacheEnabled()` is truthy, the same template works in dev / editmode / for logged-in
  users and fails in production for anonymous visitors.
- `if ($content = Cache::load($cacheKey))` (`:103`) treats a cached `''` or `'0'` as a miss: empty includes (unpublished / missing document,
  swallowed fragment errors) are re-rendered and re-saved on every request.

**Fix direction:** normalise recursively (elements → `type:id`, `Stringable` → string, scalars / null as-is, arrays recursively, everything
else → `get_debug_type()` or "not cacheable": skip caching for that call); hash with `hash('xxh128', …)` over a `json_encode()`d normalised
array; compare the load result with `false` (`Cache::load()` returns `false` on a miss).

## [ ] B-176 – Include params are passed to Symfony as fragment *options*: `strategy`, `ignore_errors`, `alt`, `standalone` keys change rendering; non-inline strategies always fail

**Files:** `lib/Templating/Renderer/IncludeRenderer.php:108-112`, `:129-132`; `lib/Templating/Renderer/ActionRenderer.php:46-53`, `:60-65`

```php
$params = array_merge($params, ['document' => $include]);
$content = $this->actionRenderer->render($include, $params);        // $params = template params
// ActionRenderer::render():
$uri = $this->createDocumentReference($uri, $options);              // options become controller attributes …
return $this->httpKernelRuntime->renderFragment($uri, $options);    // … and fragment options at the same time
```

- The same array is used as controller **attributes** and as `FragmentHandler` **options**. `HttpKernelRuntime::renderFragment()` takes
  `strategy` out of it, and the renderers read `ignore_errors`, `alt`, `standalone`, `comment`, `absolute_uri`. A template parameter with one
  of these names (`{{ pimcore_inc('/x', {'alt': 'Banner'}) }}`, `{'strategy': 'big'}`) changes how the include is rendered, or throws
  `InvalidArgumentException: The "big" renderer does not exist.`
- The attributes always contain objects (`document`, `contentDocument` = the `PageSnippet`). Every non-inline strategy (`esi`, `ssi`,
  `hinclude`) generates a fragment URI and `FragmentUriGenerator` throws `LogicException: Controller attributes cannot contain non-scalar/non-null
  values` – so passing `strategy: 'esi'` (the only way to get ESI for an include) can never work.
- `DocumentRenderer` (`lib/Document/Renderer/DocumentRenderer.php`) calls `ActionRenderer::render()` with its own `$attributes` + options mix;
  same overlap.

**Fix direction:** separate the two: `ActionRenderer::render(PageSnippet|string|ControllerReference $uri, array $attributes = [], array $options = [])`
(I-150); `IncludeRenderer` passes template params only as attributes and an explicit, documented options array (e.g. a reserved
`pimcore_inc(…, {'_fragment_options': {…}})` or a separate argument). For non-inline strategies pass the document **id** and resolve it in
the controller instead of the object.

## [ ] B-177 – Editmode include highlighting: text-only includes are never marked, and the include HTML is re-serialised by libxml

**Files:** `lib/Templating/Renderer/IncludeRenderer.php:141-166`; `lib/Tool/DomCrawler.php:30-50`

- The fallback wrapper `<div class="pimcore_editable pimcore_editable_inc" …>` is added only in `catch (Exception)`. `Pimcore\Tool\DomCrawler`
  wraps every fragment in `<pimcore-fragment-wrapper>` and does not throw for plain text, so for a snippet that renders only text (or text
  around inline elements) `filterXPath('//pimcore-fragment-wrapper/*')` finds nothing / only the inline elements: no `pimcore_id` /
  `pimcore_type` is set on the text and the include cannot be opened from the editmode context menu. The `catch` branch is practically
  unreachable.
- Each top-level element of the include gets the attributes, i.e. an include with three sibling blocks shows three separate include targets.
- The include HTML is parsed and serialised again (`$html->html()`). On Symfony 7.4 + PHP 8.4 `Crawler` uses the native `\Dom\HTMLDocument`
  parser (reproduced in [GlossaryBundle TODO-2](../bundles/GlossaryBundle/TODO-2-BUGS.md) B-08): entities in untouched nodes are written as raw
  characters and the markup is normalised, so the editmode HTML of an include differs from the frontend HTML (snapshot / diff tooling, JS that
  matches on the original markup).

**Fix direction:** always wrap in editmode (one `<div class="pimcore_editable pimcore_editable_inc" data-…>` around the whole include, or
`display: contents`), or mark only when exactly one element child and no non-whitespace text exists; or avoid re-parsing
altogether (a wrapper element needs no parsing). Escape the attribute values with `htmlspecialchars()` (they are ints / internal types today, but
the method builds HTML by concatenation).

---

## Test

> Scope 36 notes: both files in `lib/Test/**` were read (2 × 33 lines, identical except the parent class), plus the files listed in the scope
> header. Nothing in this repository extends them; the only reference is the testing doc. Symfony `KernelTestCase` behaviour (`bootKernel()` →
> `ensureKernelShutdown()` → `createKernel()`; `tearDown()` shuts the kernel down and sets `static::$kernel = null`; `Kernel::getContainer()`
> throws `LogicException` on a non-booted kernel) is described from the 7.x source; there is no `vendor/` in this checkout, so nothing was
> executed (**not verified locally**). Not repeated: `Cache::$handler` static surviving container rebuilds (I-07), the static
> `Wysiwyg` sanitizer (models I-199), `Pimcore::getContainer()` on a `null` kernel (Pimcore facade, not in this scope).

## [ ] B-178 – Test base classes register the kernel globally but never unregister it: stale kernel, stale statics and one shutdown closure per test

**Files:** `lib/Test/KernelTestCase.php:25-32`, `lib/Test/WebTestCase.php:25-32`; `lib/Pimcore.php:108-122`, `:165-177`;
`lib/Kernel.php:164-177`; `lib/Cache.php:31-46`

```php
protected static function createKernel(array $options = []): KernelInterface
{
    $kernel = parent::createKernel($options);
    Pimcore::setKernel($kernel);      // never reset when Symfony shuts the kernel down
    return $kernel;
}
```

- Symfony's `tearDown()` shuts the kernel down and drops `static::$kernel`; `Pimcore::$kernel` still points to the shut-down kernel.
  Between tests (`tearDown()` code after `parent::tearDown()`, `tearDownAfterClass()`, data providers of the next class, destructors)
  `Pimcore::hasKernel()` is `true` but `Pimcore::getContainer()` throws `LogicException: Cannot retrieve the container from a non-booted kernel`
  – every static model call (`DataObject::getById()`, `Cache::…`, `Tool::…`) fails there.
- `createKernel()` is also called without booting (tests that build a second kernel with other options to inspect it): it replaces the
  global kernel with an **un-booted** one while the booted kernel / test client is still in use.
- Statics that cache services of the first kernel are never reset: `Cache::$handler` (the facade keeps writing through the first
  container's `CoreCacheHandler`, its pool, write lock and queue), `Pimcore::$adminMode`, `Tool::$currentRequest`, and `RuntimeCache::$instance` (`lib/Cache/RuntimeCache.php:38-75`): `getInstance()` returns the first
  instance early, so test A's loaded objects are returned in test B and the new container's synthetic runtime-cache service is never set.
  Tests become order-dependent.
- `Kernel::initializeContainer()` registers a new `register_shutdown_function()` closure on **every** boot, capturing `$this`. With
  `bootKernel()` / `createClient()` per test, a suite of N tests keeps N kernel objects alive until the process ends and then runs N
  `Pimcore::shutdown()` calls (only the last kernel's container is still booted; the others return early).

**Verify:** a test class with two tests that each call `static::bootKernel()`, and `tearDownAfterClass()` calling `\Pimcore::getContainer()` →
per code `LogicException`; a test that `Cache::save()`s in test 1 and asserts `Cache::getHandler()` identity against the new container in test 2.

**Fix direction:** override `ensureKernelShutdown()` (static, called by `tearDown()` and before every boot) in a shared trait: after
`parent::ensureKernelShutdown()` reset the Pimcore statics (a new `@internal Pimcore::resetKernel()` / `unsetKernel()`, `Cache::$handler =
null`, `RuntimeCache::clear()`, `Pimcore::unsetAdminMode()`); set the global kernel only after a successful boot (override `bootKernel()`
instead of `createKernel()`). In `Kernel`, register the shutdown function once per process (static flag) and look the current kernel up via
`Pimcore::getKernel()` instead of capturing `$this`.

## [ ] B-179 – Kernels booted through the test base classes skip Pimcore's boot steps: wrong timezone, no kernel-class check, no `TestEvents::KERNEL_BOOTED`

**Files:** `lib/Test/KernelTestCase.php:25-32`, `lib/Test/WebTestCase.php:25-32`; `lib/Bootstrap.php:232-268`; `lib/Event/TestEvents.php`;
`tests/Support/Helper/Pimcore.php:110-128`

- `Bootstrap::kernel()` – the path used by `bin/console`, `public/index.php` and Pimcore's own Codeception helper – does more than
  `new Kernel()` + `boot()`: after booting it applies `pimcore.general.timezone` with `date_default_timezone_set()` (`:260-265`) and it
  rejects kernel classes that do not extend `Pimcore\Kernel` (`:252-254`). Kernels booted by `Pimcore\Test\KernelTestCase` / `WebTestCase`
  skip both: tests run with the `php.ini` timezone (date / `Carbon` assertions, scheduled tasks, versions differ from production), and a
  `KERNEL_CLASS` that is a plain Symfony kernel boots and then fails later with unrelated errors.
- `TestEvents::KERNEL_BOOTED` is documented as the hook "used from services which need to reset state between tests", but only the
  Codeception helper dispatches it (`tests/Support/Helper/Pimcore.php:128`). Listeners that a project registers for it never run in PHPUnit
  tests that use `Pimcore\Test\*`.

**Fix direction:** move the post-boot steps of `Bootstrap::kernel()` into one reusable method (e.g. `Bootstrap::afterKernelBoot(KernelInterface)`),
call it from the test base classes after `parent::bootKernel()`, and dispatch `TestEvents::KERNEL_BOOTED` there; validate the kernel class in
`getKernelClass()` (I-153).

---

## Tool

> Scope 37 notes: all 21 files in `lib/Tool/**` were read (3 533 lines), plus the consumers listed in the scope header. PHP / Symfony / ICU
> behaviour (`transliterator_transliterate()` returning `false` on invalid UTF-8, `iconv()` keeping a UTF-8 BOM, `Session::getBag()` throwing for
> unknown bags, `Crawler::filter()` requiring `symfony/css-selector`) is described from the documentation / source; there is no `vendor/` and no
> `composer.lock` in this checkout, so nothing was executed (**not verified locally**). Existing tests: `tests/Unit/Tool/{ClassUtils,HtmlUtils,
> ArrayNormalizer}Test`, `tests/Model/Tool/{Text,Frontend}Test` – none covers the defects below. Items already in other TODOs are listed in
> TODO-1 (Tool section) and not repeated.

## [ ] B-180 – `Authentication::authenticateSession()` dereferences `null` when the session user cannot be refreshed; `getPasswordHash()` fallback misses `Error`

**Files:** `lib/Tool/Authentication.php:52-64`, `:105-122`, `:193-212`; `lib/Config.php:138-145`

```php
$token = static::refreshUser($token, Pimcore::getContainer()->get(UserProvider::class));
$user = $token->getUser();          // refreshUser() returns ?TokenInterface
```

- `refreshUser()` returns `null` when the provider does not support the token's user class (`:109-111`) or throws `UserNotFoundException`
  (`:117-121`). `authenticateSession()` then calls `->getUser()` on `null` → `Error`.
- `authenticateSession()` runs for **frontend** requests that carry an admin session cookie (`UserLoader` / `RequestHelper::isFrontendRequestByAdmin()`,
  see C-11). A user that was deleted – or deactivated, once models C-41 makes `refreshUser()` throw `UserNotFoundException` as proposed – gets a
  500 on every frontend page until the cookie expires; so does a session whose token holds a user class from another provider.
- `getPasswordHash()` (`:197-205`) catches `Exception` to fall back to defaults "in case kernel is not booted yet". Without any kernel
  `Pimcore::getContainer()` calls `getContainer()` on `null` → `Error`, which is not caught; only the "kernel set but not booted" case
  (`LogicException`) reaches the fallback.

**Fix direction:** `if (!$token = static::refreshUser(...)) { return null; }`; catch `\Throwable` (or check `Pimcore::hasContainer()`) in
`getPasswordHash()`; add a test with a deleted user in the session.

## [ ] B-181 – `Console`: a disabled executable is still found on the `PATH`; background commands are built with unescaped shell strings

**Files:** `lib/Tool/Console.php:59-131`, `:133-147`, `:201-222`, `:248-301`

- `pimcore_executable_<name>: false` is documented in the code as "disabled manually". `getExternalExecutable()` throws only when
  `$throwException` is `true`; otherwise it returns `false`, `getExecutable()` treats that as "not configured" (`:76`) and continues with the
  `PATH` search – so `getExecutable('ffmpeg')` / `getExecutable('nice')` still return the binary the admin disabled.
- A parameter value that is not a string or `false` (`~` / `null` in YAML) is returned from a `string|false` method → `TypeError`.
- `execInBackgroundUnix()` builds `$nohup . $nice . $cmd . ' > ' . $outputFile . ' 2>&1 & echo $!'` and runs it with `shell_exec()` (`:278-280`):
  `$outputFile` and the binary paths are not escaped (a path with a space breaks the command, a path with shell metacharacters is executed);
  the Windows variant does the same through `COM` (`:293-297`). `execInBackground()` is public and takes an arbitrary string command.
- `runPhpScript()` does not check `fopen($outputFile, 'a')` (`:212`) → `fwrite(false, …)` `TypeError` inside the process callback.

**Fix direction:** treat `false` from the parameter as final (cache `false`, never search the `PATH`); validate the parameter type;
`escapeshellarg()` every path (or use `Process` with an argument array and `Process::start()` without waiting); check `fopen()`.

## [ ] B-182 – `DeviceDetector`: process-wide singleton state, `setDeviceType()` works on the wrong instance, inconsistent device names

**Files:** `lib/Tool/DeviceDetector.php:40-54`, `:93-111`, `:129-213`; `bundles/CoreBundle/src/EventListener/Frontend/FullPageCacheListener.php:239-241`, `:338-341`;
`lib/Templating/Renderer/IncludeRenderer.php:120`, `models/Document/Editable/Snippet.php:137`

- The detected type is cached in the static singleton (`determinedDeviceType`) and read from superglobals (`$_SERVER`, `$_REQUEST`, `$_COOKIE`).
  In a worker runtime (FrankenPHP worker mode / RoadRunner with `symfony/runtime`) the first visitor's device is returned for all later requests
  of that worker, and the full-page cache key suffix (`FullPageCacheListener.php:339-341`) uses it → phone visitors get the cached desktop
  page or the other way round. `wasUsed` is only reset by `FullPageCacheListener::onKernelRequest()`, i.e. not in CLI / sub-request paths.
- `setDeviceType()` writes to `self::$instance` instead of `$this` (`:95`): on an instance created with `new DeviceDetector()` it changes the
  singleton – or fails with "Attempt to assign property on null" if `getInstance()` was never called. It also does not set
  `determinedDeviceType`, so the next `isX()` call runs detection and sets a **second** flag (`isPhone` and `isDesktop` both `true`).
- The constructor accepts `'mobile'` as default (`:51`) but not `'phone'`; with default `'mobile'` the detector writes the undeclared property
  `$this->isMobile` (`:211`, dynamic property – deprecated since PHP 8.2) and `isPhone()` stays `false`.
- `setcookie('forceDeviceType', $type)` (`:206`) bypasses the Symfony `Response` (lost for cached / streamed responses, not testable) and is
  set without `path` (defaults to the current directory, so the forced type does not apply to other sections of the site), `secure`,
  `samesite`.

**Fix direction:** turn it into a request-scoped service (read the `Request`, reset via `kernel.reset` / `ResetInterface`); `setDeviceType()`
on `$this` and mark as determined; one name per device (`phone`, accept `mobile` as alias and map it); set the cookie on the response
with `Cookie::create(...)->withPath('/')`.

## [ ] B-183 – `Text::detectEncoding()` never detects UTF-32 and reports UTF-32LE as UTF-16LE; `convertToUTF8()` keeps the BOM and can return `false`

**Files:** `lib/Tool/Text.php:326-369`; callers `models/Translation.php:402` (translation CSV import), `bundles/SeoBundle/src/Redirect/Csv.php:133` (redirect import)

```php
$first3bytes = substr($text, 0, 3);
$first4bytes = substr($text, 0, 3);   // 3 bytes, compared with 4-byte BOMs
```

- `$first4bytes` holds 3 bytes, so both UTF-32 comparisons are always false. A UTF-32LE file starts with `FF FE 00 00` and matches the
  UTF-16LE BOM `FF FE` → converted as UTF-16LE → garbage (every second character `\0`).
- For a UTF-8 file with BOM `convertToUTF8()` runs `iconv('UTF-8', 'UTF-8', …)`, which keeps the BOM: the first CSV header cell becomes
  `"\xEF\xBB\xBFkey"`, so the import does not find its first column (translations: the `key` column).
- `iconv()` returns `false` for an illegal sequence (detector guessed wrong) → `TypeError` against the `string` return type; no `//IGNORE`
  / `//TRANSLIT`, no error message for the user.

**Fix direction:** `substr($text, 0, 4)`; check the 4-byte BOMs before the 2-byte ones; strip the BOM after detection; use
`mb_convert_encoding()` or check the `iconv()` result and throw a domain exception.

## [ ] B-184 – `Text::wysiwygText()` inserts paths as regex replacements and unescaped attributes; double-escapes alt / title; matches `data-*` attributes

**Files:** `lib/Tool/Text.php:41-202`, `:204-211`; `lib/helper-functions.php:174-187`

```php
$replacement = $linkAttr . '="' . $path . '"';
$newTag = preg_replace($pattern, $replacement, $newTag);   // $path is a replacement string
```

- `$path` (element full path, link-generator output, query / fragment of the old `href`, thumbnail path) is used as the **replacement** of
  `preg_replace()`: `$1`, `${1}`, `\0` or a backslash in a path (asset filenames are user-chosen, link generators are project code) are
  interpreted as back-references and silently removed / changed (`/files/price$10.pdf` → `/files/price.pdf`).
- The value is written into `href="…"` / `src="…"` without `htmlspecialchars()`: a `"` (link-generator output, query string) breaks out of
  the attribute. The rest of the HTML is sanitized on save (models C-60), this rewrite happens **after** that on output.
- `alt` / `title` are cut out of `getImageTag()` output (already HTML-escaped) and passed to `array_to_html_attribute_string()`, which escapes
  again (`htmlspecialchars()` with `double_encode`): `Tom &amp; Jerry` becomes `Tom &amp;amp; Jerry` in the page.
- `'/href="[^"]*"/'` / `'/src="[^"]*"/'` also match `data-href="…"` / `data-src="…"` (lazy-load markup) – whichever comes first in the tag
  is replaced. `addAttributeToTag()` uses `str_contains($oldTag, 'src')`, which is true for `srcset` / `data-src`, so a missing `src` is not
  added back.
- A broken link is only unwrapped when its content contains no tag (`'([^\<]+)\</a\>'`, `:195`); `<a pimcore_id=…><strong>x</strong></a>`
  keeps the dead internal link.

**Fix direction:** rewrite with a DOM parser (`\Dom\HTMLDocument::createFromString()` in PHP 8.4) and set attributes through the DOM API, or
at least use `preg_replace_callback()` and `htmlspecialchars()` for every inserted value, use `(?<![-\w])` before the attribute name, and do not
re-escape values taken from generated HTML (take alt / title from the thumbnail / asset metadata directly).

## [ ] B-185 – Multibyte / invalid UTF-8 in string helpers: `cutStringRespectingWhitespace()` splits characters, `Transliteration::toASCII()` returns `false`

**Files:** `lib/Tool/Text.php:380-391`; `lib/Tool/Transliteration.php:25-32`; `lib/File.php:37-45`

- `cutStringRespectingWhitespace()` uses byte functions (`strlen` / `substr` / `strrpos`). For text without a space before the limit it cuts in
  the middle of a multibyte character → invalid UTF-8 (`json_encode()` fails, `htmlspecialchars()` returns `''`); `$length` counts bytes, so
  non-Latin text is cut much shorter than requested.
- `transliterator_transliterate()` returns `false` for invalid UTF-8 input; `toASCII(): string` in a strict file → `TypeError`.
  `File::getValidFilename()` (asset / document keys, uploaded file names) passes user input straight in, so an upload whose name is not valid
  UTF-8 (Latin-1 from older clients, ZIP imports) fails with a 500 instead of a cleaned name.
- The rule `[^\u001F-\u007f] remove` keeps `U+001F` and `U+007F` (control characters) – the printable ASCII range is ` -~`.

**Fix direction:** `mb_strlen()` / `mb_substr()` / `mb_strrpos()` (or `grapheme_*`); check the transliterator result and fall back to
`mb_convert_encoding($value, 'UTF-8', 'UTF-8')` first; fix the range.

## [ ] B-186 – `HtmlUtils::assembleAttributeString()` does not escape attribute names or values

**Files:** `lib/Tool/HtmlUtils.php:30-49`; callers `models/Document/Editable.php:130`, `Editable/Block.php:203`, `:245`, `:393`,
`Editable/Scheduledblock.php:164`, `:192-193`, `Editable/Area.php:77`, `:184`, `Editable/Areablock.php:320`; `tests/Unit/Tool/HtmlUtilsTest.php`

- `sprintf('%s="%s"', $key, $value)`: a `"`, `<` or `&` in a value breaks the markup. The editmode attributes include names and
  configuration values from templates (`class`, editable names with block indices, dialog configuration), so a template option such as
  `{'class': 'a" onmouseover="…'}` or a translated label ends up as live markup in the admin editmode page.
- `true` / `false` become `"1"` / `""`, arrays become `"Array"` with a warning; the sister helper `array_to_html_attribute_string()`
  (`lib/helper-functions.php:174`) escapes values and skips non-scalars – two helpers with different safety for the same job.

**Fix direction:** escape values with `htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE)`, validate names (`/^[a-zA-Z_:][-a-zA-Z0-9_:.]*$/`),
handle `true` as boolean attribute and `false` as omitted; make one helper delegate to the other; extend `HtmlUtilsTest` with quotes.

## [ ] B-187 – `Admin`: CSV dialect sniffing reads the file ten times and defeats its own checks; `getLanguages()` depends on admin-bundle parameters

**Files:** `lib/Tool/Admin.php:41-85`, `:100-125`; `lib/Tool/Text/Csv.php:33-60`; `models/Translation.php:208-213`, `:326`

- `determineCsvDialect()` calls `file($file)` **inside** a 10-iteration loop and appends the same first 11 lines ten times. Besides reading
  the whole file ten times (models I-56 lists the performance side), the sample is the same 11 lines repeated: `Csv::detect()`'s "at least ten
  lines" check always passes (a 1-line file "passes"), and every per-line frequency is identical, so the deviation filter (`Csv.php:191-199`)
  never removes a wrong delimiter. `file()` returning `false` → `array_slice(false)` `TypeError`.
- `getLanguages()` reads `pimcore_admin.translations.path` / `pimcore_admin.admin_languages` – parameters defined only by the admin UI bundle
  (not in this repository). Without it `Translation::getValidLanguages(DOMAIN_ADMIN)` and the admin-domain branch of
  `Translation::getByKeyLocalized()` (`:326`) throw `ParameterNotFoundException`.
- `$parts[1]` is read for every file name (`:63`); a file without a dot (`README`, `LICENSE`) → "Undefined array key 1" warning (an
  `ErrorException` in debug mode). The directories are scanned on every call (`Translation.php:326` calls it per lookup).

**Fix direction:** read the first ~20 lines once with `SplFileObject` / `fgets()`; skip the sample padding and let the sniffer fall back to
defaults for short files; check `hasParameter()` (or move `getLanguages()` into the admin bundle) and cache the result per request;
`$parts[1] ?? null`.

## [ ] B-188 – `UserTimezone::applyTimezone()` mutates the caller's `DateTime`; the user timezone is process-wide static state

**Files:** `lib/Tool/UserTimezone.php:29-52`; `models/DataObject/ClassDefinition/Data/Datetime.php:158-172`, `:402-409`, `Data/Date.php:393-398`

- For mutable dates (`DateTime`, `Carbon`) `$date->setTimezone()` changes the passed object. `Datetime::getForCsvExport()` passes the object's own
  field value (`getDataFromObjectParam()`), so a CSV export / grid preview changes the timezone of the loaded object's `Carbon` in place; later
  `format()` calls in the same process (indexing, other exports, templates) use the admin user's timezone, and for `Date` fields the date part
  can shift by one day.
- `self::$userTimezone` is static and is never reset (set by the admin UI bundle per user): in a worker runtime or a messenger worker the
  previous user's timezone is applied to the next request / message.
- `timezone_identifiers_list(DateTimeZone::ALL_WITH_BC)` is built on every `setUserTimezone()` call; `method_exists($date, 'setTimezone')` is
  always true for `DateTimeInterface` implementations in core.

**Fix direction:** work on a copy: `DateTimeImmutable::createFromInterface($date)->setTimezone(...)` (or `$date instanceof DateTime ? clone $date`);
keep the timezone in a request-scoped service (reset via `kernel.reset`); validate with `new DateTimeZone()` in a `try`.

## [ ] B-189 – `Requirements`: global `setlocale()` side effect, a leftover check table makes every later run fail, unguarded `scandir()` / `convert`

**Files:** `lib/Tool/Requirements.php:44-78`, `:133-334`, `:606-615`, `:624-647`, `:708-729`

- The `locales-utf8` check calls `setlocale(LC_ALL, [...])` and never restores the previous locale: running the requirements check (admin system
  page, installer, `pimcore:system:requirements:check`) switches the process locale for everything that follows (`strtolower()` /
  `sprintf('%f')` / `escapeshellarg()` behaviour; in a worker for all later requests).
- `checkMysql()` creates `__pimcore_req_check` / `__pimcore_req_check_view` and drops them at the end. If a run is interrupted (timeout,
  `Error`, missing privilege for `DROP`), the objects stay; every later `CREATE TABLE` fails and the check reports "CREATE TABLE" as an
  error although the privilege exists. No `IF NOT EXISTS` / pre-cleanup / `finally`.
- `rscandir()` uses `scandir()` without checking for `false` (unreadable directory) → `array_diff(false, …)` `TypeError`; `checkFilesystem()`
  catches only `Exception`, so the whole requirements page fails with a 500 exactly when the filesystem is the problem.
- Imagick check: `Console::getExecutable('convert')` returns `false` without the CLI (and ImageMagick 7 ships `magick`, `convert` is deprecated)
  → `Process::fromShellCommandline(false . ' -list configure')` runs ` -list configure`; the path is not escaped.

**Fix direction:** read the current locale with `setlocale(LC_ALL, 0)` and restore it (or test with `\Locale` / `localeconv()` only); drop
leftovers first (`DROP VIEW IF EXISTS` / `DROP TABLE IF EXISTS`) and clean up in `finally`; check `scandir()` and catch `\Throwable` per
check; skip the LCMS check when no binary is found, try `magick` first, pass arguments as an array.

## [ ] B-190 – `Tool\Session::getSessionBag()` promises `null` for a missing bag but throws

**Files:** `lib/Tool/Session.php:29-51`

- Symfony's `Session::getBag()` throws `InvalidArgumentException` ("The SessionBagInterface "x" is not registered") for an unknown name; it
  never returns `null` / a non-bag. `getSessionBag(): ?AttributeBagInterface` therefore throws instead of returning `null`, and
  `useBag()`'s own `InvalidArgumentException` is unreachable for that case.
- The default namespace `pimcore_admin` is registered only by the admin UI bundle; without it every default call throws.

**Fix direction:** check `$session->isStarted()` / catch `InvalidArgumentException` and return `null` (or remove the nullable contract);
document that the bag comes from the admin bundle (docs: I-146).

## [ ] B-191 – `DomCrawler::html()` relies on CSS selectors, but `symfony/css-selector` is not a dependency

**Files:** `lib/Tool/DomCrawler.php:44-53`; `composer.json` (`require`)

- For wrapped fragments `html()` calls `$this->filter(self::FRAGMENT_WRAPPER_TAG)`. `Crawler::filter()` needs `symfony/css-selector` and throws
  `LogicException: To filter with a CSS selector, install the CssSelector component` without it.
- `composer.json` requires `symfony/dom-crawler` but not `symfony/css-selector`; whether it is installed transitively could not be checked (no
  `composer.lock`). If it is missing, every fragment round trip fails: wysiwyg `rewriteIds()`, editmode includes (B-177), glossary.
- **Verify:** `composer why symfony/css-selector` in a production install (`--no-dev`).

**Fix direction:** use `filterXPath('//' . self::FRAGMENT_WRAPPER_TAG)` (no extra component) or add `symfony/css-selector` to `require`.

## [ ] B-192 – `AssetsInstaller`: non-string `symfony-assets-install` causes a `TypeError`; the env variable cannot force a hard copy

**Files:** `lib/Tool/AssetsInstaller.php:105-149`, `:151-171`

- `$json['extra']['symfony-assets-install']` is assigned to `?string $composerJsonSetting` unchecked (`:165`): `true` / an array in
  `composer.json` → `TypeError` in the installer and in `pimcore:bundle:install` (`PostStateChange`).
- `SYMFONY_ASSETS_INSTALL` can only switch symlinks **on** (`:136-142`); `SYMFONY_ASSETS_INSTALL=hard` (Symfony Flex's documented value for a
  copy) does not override a `symlink` setting from `composer.json`, unlike Flex.
- The `env` option has no allowed-type definition; `ansi` and `no-ansi` can both be `true`; the 240 s timeout is fixed.

**Fix direction:** accept only `'symlink' | 'relative' | 'hard'`; let the env variable win in both directions (`hard` → `symlink = false`);
`setAllowedTypes('env', ['bool', 'string'])`; mutually exclusive `ansi` / `no-ansi`; configurable timeout.

## [ ] B-193 – `ClassUtils::findClassName()` takes the token after `::class` and ignores enums / interfaces / traits

**Files:** `lib/Tool/ClassUtils.php:49-105`; `lib/Extension/Bundle/PimcoreBundleLocator.php:90`; `tests/Unit/Tool/ClassUtilsTest.php`

- `T_CLASS` is also the token of the `::class` constant. A file that uses `Foo::class` before its class declaration (attribute arguments such as
  `#[Autoconfigure(tags: [Foo::class])]` on the class, or a `const X = Foo::class;` before it) sets `$gettingClass` and takes the **next**
  `T_STRING` – e.g. a constant or method name – as the class name. `PimcoreBundleLocator` then registers a non-existing class.
- `T_ENUM`, `T_INTERFACE`, `T_TRAIT` are not handled → `RuntimeException` for such files; an anonymous `new class` before the declaration is
  also picked up.

**Fix direction:** use `PhpToken::tokenize()` and accept `T_CLASS` only when the previous meaningful token is not `T_DOUBLE_COLON` / `T_NEW`;
handle the other type tokens; or use Composer's `ClassMapGenerator::createMap()` (`composer/class-map-generator`). Add the `::class` and enum
cases to `ClassUtilsTest`.

---

## Translation

> Scope 38 notes: both files in `lib/Translation/**` were read (437 lines), plus the consumers listed in the scope header. Symfony translator
> behaviour (pipe plural selection with `%count%`, `MessageCatalogue::has()` checking the `+intl-icu` domain) is described from the 7.x source;
> there is no `vendor/` in this checkout, so nothing was executed (**not verified locally**). The pipe-plural path was checked against
> `TranslatorTest::testTranslatePluralizationWithCountParam()` (the pipe id is a list of *keys*, one per plural form) and is not a defect.
> Already tracked and not repeated: models C-18, C-19, C-22, B-115 (dumper queue keyed by key only, poison queue), B-123 (wrong language list,
> 190-byte check), B-124 (catalogues never refresh in long-running processes), CoreBundle C-08 (`TranslationDebugListener` flag on the shared
> translator, never reset), CoreBundle I-52 (dumper in Messenger workers), B-184 (`wysiwygText()` on translated texts).

## [ ] B-194 – `vsprintf()` on every term called with a positional parameter: a literal `%` or a placeholder-count mismatch is a 500

**Files:** `lib/Translation/Translator.php:97-100`

```php
// check for an indexed array, that used the ZF1 vsprintf() notation for parameters
if (isset($parameters[0])) {
    $term = vsprintf($term, $parameters);
}
```

- Runs after Symfony has already replaced its own placeholders, on **any** term (simple or ICU) as soon as the parameter array has a key `0`.
- PHP 8 throws instead of warning: `ValueError: Unknown format specifier` for a literal `%` followed by a letter (`"50% off"` → `% o`),
  `ArgumentCountError` / `ValueError` when the text has more `%s` than parameters. A translator (admin user) who writes `100% Bio` into a
  text that a template calls with a positional parameter turns that page into a 500 – the text is data, the crash is in rendering.
- `TranslatorInterface::trans()` parameters are documented as a placeholder map; the ZF1 notation is undocumented in this repository's docs.

**Fix direction:** deprecate the positional notation (`trigger_deprecation()` when `isset($parameters[0])`), wrap `vsprintf()` in a
`try/catch (\ValueError|\ArgumentCountError)` that logs and returns the unformatted term, and escape `%` that is not followed by a valid
specifier; remove the branch in the next major.

## [ ] B-195 – `lazyInitialize()` marks a catalogue as initialised before loading it: one failed load leaves the domain untranslated for the rest of the process

**Files:** `lib/Translation/Translator.php:138-193`, `:240-272`

```php
$this->initializedCatalogues[$cacheKey] = true;     // set first
if (Translation::isAValidDomain($domain)) {
    if (!$catalogue = Cache::load($cacheKey)) {
        ... $list->loadRaw() ...                     // may throw (DB, C-19 TypeError)
```

- If loading throws (lost DB connection, lock timeout, the `NULL` row of models C-19), the flag is already `true`. Every later `trans()` for that
  domain / locale in the same process skips loading: in a Messenger worker or a worker runtime, the whole domain shows raw keys until the
  process restarts (combines with B-124: nothing resets the flag).
- With the DB catalogue missing, `checkForEmptyTranslation()` treats **every** key as missing: one `Translation::getByKey()` query per key per
  process and entries in the save queue – the path that can blank or overwrite existing texts (models C-22).

**Fix direction:** set the flag only after a successful load (or in `finally` only on success); on failure log, return and let the next call
retry; do not auto-create keys while the domain's catalogue is not loaded.

## [ ] B-196 – Empty-text handling: `"0"` counts as untranslated, the `debug_admin_translations` marker leaks into website domains, fallback languages need an empty row

**Files:** `lib/Translation/Translator.php:156-177`, `:217-298`; `bundles/CoreBundle/src/DependencyInjection/Configuration.php:261-262`

- `empty($translationTerm)` (`:164`, `:167`) and `empty($translated)` (`:231`, `:297`) treat the text `"0"` as missing: a translation whose
  value is `0` (counts, flags, "0 %" short labels) is replaced by the file catalogue value or the key, and the fallback search runs for it.
- The `+key+` marker of `debug_admin_translations` ("Debug Admin-Translations", config `:261-262`) is applied in `lazyInitialize()` for **every**
  domain, including `messages` and custom website domains: enabling admin-translation debugging shows `+key+` on the public frontend for every
  empty website translation. The marker is baked into the persistently cached catalogue, so toggling the setting has no effect until the
  `translator` cache tag is cleared.
- Fallback languages (`:276-295`) are only consulted when the current locale has an **empty row** (`$translated === ''`). If the locale has no
  row at all (language added later, partial import), Symfony returns the key, `lookForFallback` is `false`, and the visitor sees the raw key
  although a fallback text exists; only after the auto-create queue has inserted an empty row does the next request use the fallback.
- The fallback is restricted to the literal `'messages'` (`:276`) instead of `Translation::DOMAIN_DEFAULT`.

**Fix direction:** compare with `''` / `null` instead of `empty()`; apply the debug marker only for `Translation::DOMAIN_ADMIN` and include the
flag in the cache key (or apply it at `trans()` time); look for fallbacks whenever the key is not defined for the locale
(`!$catalogue->defines($id, $domain)`), not only for empty texts; use the constant.

---

## Twig

> Scope 39 notes: see TODO-1 (Twig section). Existing tests: `tests/Twig/{PimcoreDate,PimcorePlaceholder}Test`,
> `tests/Unit/Twig/Options/BlockOptionsTest` – none covers the tag nodes, the sandbox policy or the head helpers' output. Not repeated:
> CoreBundle B-59 (`symfony/web-link` not required), B-43 (placeholder index after a throwing sub-request), models I-202 (editable interfaces).

## [ ] B-197 – Custom tags paste template literals into generated PHP without escaping: a `'` breaks compilation, `$` / `{$…}` is evaluated

**Files:** `lib/Twig/Node/CacheNode.php:54-79`; `lib/Twig/Node/BlockNode.php:53-68`; `lib/Twig/Node/ManualBlockNode.php:69-90`;
`lib/Twig/Options/BlockOptions.php:93-112`; `lib/Twig/TokenParser/CacheParser.php:47`, `:112-121`

```php
\$key = '$key';                       // CacheNode – raw template string inside single quotes
\$tags = $tags;                       // json_encode() output = double-quoted PHP strings
\$block = ...renderEditable(\$context, 'block', '$this->blockName', $optionsString);
$options .= "'class' => \"" . $this->getClass() . '",';   // BlockOptions – double quotes
```

- The compiled template is built by string concatenation instead of `Compiler::string()` / `repr()`.
- `{% pimcorecache "today's-news" %}` or `{% pimcoreblock "team's" %}` → `ParseError` in the compiled cache file (500 on every page using that
  template, only visible after a cache clear / deploy).
- Tags and the block `class` option end up in **double-quoted** PHP strings: `$name` / `{$expr}` / `${expr}` inside them are interpolated at
  runtime (`class('price-{$x}')` → "Undefined variable"; `{${system('id')}}`-style payloads execute). A `'` in a cache key closes the string and
  appends arbitrary PHP. Template sources are normally trusted, so this is a bug; it becomes code execution wherever a project allows these
  tags in the sandbox (`sandbox_security_policy.tags`) or renders templates from the database.

**Fix direction:** emit everything through the compiler: `$compiler->write('$key = ')->string($this->key)->raw(";\n")`, `->repr($this->tags)`,
`->repr($optionsArray)`; let `BlockOptions` return an array instead of PHP source.

## [ ] B-198 – Nested `pimcorecache` / `pimcoreblock` tags overwrite each other's generated variables

**Files:** `lib/Twig/Node/CacheNode.php:62-77`; `lib/Twig/Node/BlockNode.php:57-66`; `lib/Twig/Node/ManualBlockNode.php:74-88`

- All nodes compile into the same PHP scope with fixed variable names: `$cacheExtension`, `$key`, `$tags`, `$ttl`, `$force`, `$content`,
  `$editableExtension`, `$block`, `$config`.
- Nested `{% pimcorecache 'outer' %}…{% pimcorecache 'inner' %}…{% endpimcorecache %}…{% endpimcorecache %}`: after the inner tag, `$key`,
  `$tags`, `$ttl` hold the inner values → `endBuffering($key, …)` stores the **outer** fragment under the **inner** key (the inner cache entry
  is overwritten with the outer HTML, the outer key is never filled and re-renders every time).
- Nested `pimcoreblock`: the inner tag assigns `$block` = inner block. From the second iteration on, the outer loop calls
  `$block->setCurrent($key)` on the **inner** block (`BlockNode.php:61`) and `$context['_block']` is not restored after the inner block, so the rest
  of the outer body sees the inner block. Editables of the outer block get wrong names / indices; editmode data is saved under the wrong keys.
- A `pimcorecache` inside a `pimcoreblock` overwrites the block's `$key` loop variable as well.

**Fix direction:** use `$compiler->getVarName()` for every generated variable (Twig's own nodes do), restore `$context['_block']` after the loop;
add tests with nested tags.

## [ ] B-199 – `{% pimcorecache %}`: constant key only (no locale / site / device), unclear errors for non-constant options, empty content never cached

**Files:** `lib/Twig/TokenParser/CacheParser.php:42-121`; `lib/Twig/Extension/CacheTagExtension.php:40-75`; `lib/Twig/Options/HasBlockOptionsTrait.php:33-72`

- The key must be a string literal (`expect(Token::STRING_TYPE)`), and `ttl` / `tags` / `force` must be constants. The deprecated
  `pimcore_cache(name)` accepted an expression, so templates migrating to the tag lose the ability to vary the key: a navigation or teaser cached
  with `{% pimcorecache 'main-nav' %}` is served in the first rendered language / site / device to all others (same pattern as B-174). Nothing
  is added to the key automatically.
- A non-constant option (`ttl(cacheTtl)`, `tags([tagVar])`, block `limit(maxItems)`) calls `getAttribute('value')` on a non-constant node →
  `LogicException: Attribute "value" does not exist` instead of a `SyntaxError` with line number (`CacheParser.php:64`, `:89`;
  `HasBlockOptionsTrait.php:47`). Unknown block options (`limt`) are silently ignored (`HasBlockOptionsTrait.php:66-67`).
- `if (!$content)` in the generated code treats a cached `''` / `'0'` as a miss → re-rendered and re-saved every time.
- The cache ignores `DeviceDetector::wasUsed()` (unlike includes / snippets).

**Fix direction:** accept an expression for the key (`parseExpression()`) and compile it; optionally append site id + locale by default;
raise `SyntaxError` for non-constant options; compare with `false`.

## [ ] B-200 – `{% pimcoreassetcompress %}` removes every newline: `//` comments swallow the rest of the script, ASI-dependent code breaks

**Files:** `lib/Twig/Node/AssetCompressNode.php:33-40`; `lib/Twig/TokenParser/AssetCompressParser.php:28-30`

- The node does `str_replace("\n", '', ob_get_clean())`. In JavaScript a `// comment` line then comments out **everything after it**; code that
  relies on automatic semicolon insertion (`return\nvalue`, lines without `;`) changes meaning; template literals and `<pre>` lose line breaks.
  `\r` is kept, so CRLF files become `\r`-separated.
- The parser comment says it is "suited for a simple minification of CSS/JS"; CSS survives, JS often does not.

**Fix direction:** document "CSS only" or remove the tag in favour of a real asset pipeline (AssetMapper / Encore); at minimum keep newlines
inside `<script>` and only collapse whitespace between tags.

## [ ] B-201 – Placeholder / head helper state lives on shared services: content leaks across requests (worker runtimes) and across rendered documents

**Files:** `lib/Twig/Extension/Templating/Placeholder/ContainerService.php:50-146`; `lib/Twig/Extension/Templating/Placeholder.php:64-79`;
`HeadMeta.php:80`, `:93-97`, `:391-396`; `HeadTitle.php:60`, `:95-108`; `HeadScript.php:84-119`; `Placeholder/AbstractExtension.php:86`;
`CacheBusterAware.php:25`; `Traits/WebLinksTrait.php:35`; `bundles/CoreBundle/src/EventListener/Frontend/DocumentRendererListener.php:43-52`

- `ContainerService` keeps all containers of index 0 for the lifetime of the service and has no reset. In a worker runtime (FrankenPHP worker
  mode / RoadRunner) every request **appends** to the previous request's head title, meta tags, scripts, styles and placeholders: page B gets
  page A's `<title>` parts, canonical / OG meta and inline scripts (possibly with the previous visitor's data); memory grows per request.
- `Placeholder::__invoke()` caches containers in `$this->containers` by name (`:74-76`). After `DocumentRendererListener` pushes a new index
  (rendering an email / PDF / static page during a request), `pimcore_placeholder('x')` still returns the container of the **outer** index –
  the isolation that `pushIndex()` exists for does not apply to `pimcore_placeholder`, and after `popIndex()` the cached object is detached
  from the service.
- Per-helper settings are properties of the shared service, not of the indexed container: `HeadMeta::$rawItems` (raw meta tags of a rendered
  email end up in the page), `HeadTitle::$_defaultAttachOrder`, `_autoEscape`, `cacheBuster`, `webLinksEnabled`, `useCdata`,
  `_arbitraryAttributes`. The `PHP_EOL` separator is set in the constructors on the index-0 container only, so containers of pushed indices
  render without separators.

**Fix direction:** implement `ResetInterface` on `ContainerService` and the helpers (reset to a fresh index 0); drop the `$containers` cache
in `Placeholder`; move per-helper settings into the container (or a per-index state object); set separators when a container is created.

## [ ] B-202 – Head helpers produce invalid or unsafe markup: JSON-LD / module scripts wrapped in `//<!--`, media queries dropped, unescaped attribute names and conditionals

**Files:** `lib/Twig/Extension/Templating/HeadScript.php:428-495`, `:502-525`; `HeadStyle.php:69-78`, `:327-379`, `:410-423`; `HeadLink.php:290-323`,
`:439-448`; `HeadMeta.php:209-212`, `:306-341`

- `HeadScript` wraps every inline script in `//<!--` … `//-->` unless the item has `noescape`. For `application/ld+json` the result is not valid
  JSON (search engines ignore the structured data); for `importmap` / `speculationrules` the browser rejects it. The type is escaped twice
  (`:432` then again in the attribute loop `:450`).
- `HeadStyle` keeps a `media` value only if it is one of the CSS2 media types (`all`, `screen`, `print`, …). A media query
  (`(max-width: 600px)`, `screen and (min-width: 40em)`) is **silently removed**, so the CSS applies to all devices. Styles without `media`
  get `media="screen"` by default and do not apply in print.
- Attribute **names** from `extras` (`HeadLink`) or arbitrary attributes (`HeadScript::setAllowArbitraryAttributes(true)`) are not validated or
  escaped; the IE `conditional` / `conditionalStylesheet` value is inserted unescaped into `<!--[if …]>` (`HeadScript.php:489`,
  `HeadLink.php:319`, `HeadStyle.php:375`) while `HeadMeta` escapes it.
- `HeadLink::_isDuplicateStylesheet()` reads `$item->rel` on items created with `createData()` that have no `rel` → "Undefined property" warning
  (`ErrorException` in debug). `HeadMeta::_isValid()` always returns `true`, so `pimcore_head_meta().append('<meta …>')` stores a string and
  `itemToString(stdClass)` throws a `TypeError` that the `catch (Exception)` in `toString()` does not catch.
- Conditional comments are ignored by every browser since IE 10 – dead output.

**Fix direction:** no comment wrapping for non-JavaScript types (or drop it entirely – it is an HTML 4 relic); allow any media query (escape it,
do not filter); validate attribute names (`/^[a-zA-Z_:][-a-zA-Z0-9_:.]*$/`) and escape conditionals (or drop IE conditionals); `?->`/`isset` for
`rel`; validate `stdClass` in `HeadMeta::_isValid()`.

## [ ] B-203 – `pimcore_url()` copies every query parameter of the main request into generated URLs by default

**Files:** `lib/Twig/Extension/Templating/PimcoreUrl.php:39-47`, `:52-103`

- Unless `reset` is `true`, `array_replace($mainRequest->query->all(), $urlOptions)` merges **all** current query parameters into every
  generated link (ZF1 behaviour). Tracking parameters (`utm_*`, `gclid`, `fbclid`), pagination / filter state and one-time tokens
  (`?token=…` of a password-reset or double-opt-in page) are repeated in every link on the page – sent to the next page, logged, cached by the
  full-page cache as separate URLs (duplicate content).
- Reserved parameters from the query string take effect: `_fragment` adds a fragment, `encode=0` is passed to the router (Pimcore's static
  route generator then skips URL-encoding of the values that came from the query string), `_locale` overrides the route's locale when the
  template did not set it.

**Fix direction:** change the default to `reset: true` in a new major (deprecation now when `reset` is not passed explicitly and the query is
non-empty); never copy parameters starting with `_` or `encode`; document the behaviour.

---

## ValueObject

> Scope 40 notes: all 6 files were read (374 lines) together with their unit tests (`tests/Unit/ValueObject/**`). Inside this repository only
> `CacheParser` uses `ArrayOfStrings`; the classes are public API for other Pimcore bundles (upgrade notes), which were not checked. Nothing was
> executed.

## [ ] B-204 – `Path` accepts `..` / `.` segments, NUL bytes, control characters and a trailing slash

**Files:** `lib/ValueObject/String/Path.php:40-49`; `tests/Unit/ValueObject/String/PathTest.php`

- Validation only requires a leading `/` and no `//`. `/../../etc/passwd`, `/a/./b`, `/foo\0bar`, `/foo\nbar`, `/folder/` and `/` with
  trailing whitespace are all valid `Path` objects.
- A type named `Path` reads as "already validated"; a consumer that builds a storage path, an element path or a redirect target from it gets
  no protection against traversal or header / log injection, although the object suggests it. The class does not say whether it models an
  element path (`/folder/key`), a filesystem path or a URL path – the rules differ.

**Fix direction:** decide and document what the type represents; for element paths reject `.` / `..` segments, NUL and control characters
(`/[\x00-\x1F\x7F]/`) and a trailing slash except for the root `/`; add the cases to `PathTest`. If other bundles rely on the lax behaviour, add a
stricter type (`ElementPath`) instead of changing `Path`.

---

## Video

> Scope 41 notes: all 3 files were read (647 lines), plus the consumers listed in the scope header. ffmpeg behaviour (output format of
> `ffmpeg -i`, rotation side data, `libfdk_aac` availability) is described from the ffmpeg documentation; nothing was executed (**not verified
> locally**). No test covers `lib/Video`. Already tracked and not repeated: models C-10 (`null` bitrates), B-64 (DASH drops main-config arguments,
> `-an` pairing, duplicate `-c:v`), B-65 (`cut` start reset by `getDuration() * 100`), I-12 (`ffmpeg -codecs` run and discarded,
> unvalidated `colorchannelmixer` filter).

## [ ] B-205 – `Video\Adapter`: uninitialised typed properties and a non-nullable `$medias` turn expected errors into `Error`s

**Files:** `lib/Video/Adapter.php:25-41`, `:75-83`, `:104-107`; `lib/Video/Adapter/Ffmpeg.php:81`, `:205-206`, `:305-311`, `:330-333`;
`models/Asset/Video/Thumbnail/Processor.php:287`

- All state is declared as non-nullable typed properties without defaults (`int $videoBitrate`, `string $format`, `array $medias`,
  `string $destinationFile`, `string $storageFile`, `int $length`). Every getter called before its setter throws
  `Error: Typed property … must not be accessed before initialization`.
- `save()` starts with `if ($this->getDestinationFile())` to throw "There is no destination file for video converter" (`Ffmpeg.php:205-206`) –
  unreachable, the getter throws an `Error` first, which `catch (Exception)` callers do not handle.
- `setMedias(?array $medias)` assigns to `array $medias` → `TypeError` for `null`; `getMedias(): ?array` promises `null` it can never return. A
  `mpd` converter without medias fails in `array_keys(null)` (`:122`) instead of with a clear message.
- `destroy()` / `getConversionLogFile()` use `getFormat()`: an adapter instance used only for `getDuration()` / `getDimensions()`
  (`models/Asset/Video.php:165-184`) has no format → `Error` if `destroy()` is ever called on it.

**Fix direction:** nullable properties with `null` defaults (`?string $destinationFile = null`, `array $medias = []`), getters returning
`?type`, explicit checks with domain exceptions in `save()`.

## [ ] B-206 – Video dimensions ignore rotation and come back as strings; metadata parsing scrapes `ffmpeg -i` stderr twice

**Files:** `lib/Video/Adapter/Ffmpeg.php:239-303`; `lib/Messenger/Handler/AssetUpdateTasksHandler.php:108-113`; `models/Asset/Video.php:211-255`

- `getDimensions()` takes the first ` WxH` match in the human-readable `ffmpeg -i` output. Rotation metadata (`displaymatrix: rotation of -90.00
  degrees` / `rotate: 90`, standard for phone videos) is ignored: a portrait video recorded as 1920x1080 is stored as **landscape**
  (`videoWidth` 1920, `videoHeight` 1080). The video editable / player and image thumbnails of the video then use the wrong aspect ratio.
  The sample aspect ratio (`SAR`) is ignored as well, although `saveImage()` corrects for it (`scale=iw*sar:ih`).
- The values are returned as **strings** (`explode()` result); `AssetUpdateTasksHandler` stores them unchanged in `videoWidth` / `videoHeight`,
  while `Video::getDimensions()` stores `(int)` casts – the same custom setting has two types depending on the code path.
- `getDuration()` and `getDimensions()` each start a separate ffmpeg process and write its output to a temp file (`:241-257`) instead of
  reading `getErrorOutput()`; the format of `ffmpeg -i` is not a stable interface (localised builds, extra streams such as attached cover art
  appearing first in some containers).

**Fix direction:** use `ffprobe -v error -print_format json -show_streams -show_format` once per file, pick the first real video stream
(`disposition.attached_pic = 0`), swap width / height for ±90° rotation, apply SAR, return `int`s.

## [ ] B-207 – Codec defaults: DASH needs the non-free `libfdk_aac`, WebM uses a 44 000 Hz sample rate, MP4 still passes `-strict experimental`

**Files:** `lib/Video/Adapter/Ffmpeg.php:96-157`

- The `mpd` branch hard-codes `-c:a libfdk_aac` (`:129`). `libfdk_aac` is only present in ffmpeg builds compiled with `--enable-nonfree`; the
  ffmpeg packages of Debian / Ubuntu / Alpine and the official Docker images do not have it, so every DASH thumbnail fails with "Unknown encoder"
  (the error only lands in the `.error.log` file). The native `aac` encoder is the portable choice.
- WebM: `-ar 44000` (`:118`) – almost certainly meant 44 100 Hz; 44 000 Hz is a non-standard rate that forces resampling and is rejected by
  some players. VP8 + Vorbis (`libvpx` / `libvorbis`) are the legacy WebM codecs; VP9 / Opus are supported by all current browsers (the code
  even contains a disabled VP9 switch).
- MP4: `-strict experimental` (`:97`, `:114`) has not been needed for the native AAC encoder since ffmpeg 3.0; `-vcodec` / `-acodec` are legacy
  aliases of `-c:v` / `-c:a`.

**Fix direction:** `-c:a aac` for DASH; `-ar 44100` (or omit and keep the source rate); move codec choices into the thumbnail config with sane
defaults (`libvpx-vp9` + `libopus` for WebM); drop `-strict experimental`.

## [ ] B-208 – `Ffmpeg::save()`: no timeout, unchecked file handles, error logs that are never cleaned up

**Files:** `lib/Video/Adapter/Ffmpeg.php:41-54`, `:163-204`, `:243-257`

- `$process->setTimeout(null)` (`:181`): a stuck ffmpeg (corrupt input, network file system) blocks the messenger worker that runs the
  conversion forever; the asset stays "in progress". No idle timeout either.
- `fopen()` for the conversion log (`:184`) and the info temp file (`:248`) is not checked; a full / read-only log directory turns into
  `fwrite(false)` `TypeError`s inside the process callback.
- Failed conversions copy the log to `ffmpeg-<id>-<format>.error.log` in `PIMCORE_LOG_DIRECTORY` (`:198-202`); nothing removes those files.
- `realpath($this->file)` returns `false` for a missing file and is passed as the `-i` argument (`:164`, `:219`, `:243`) → ffmpeg error
  instead of a clear "source file not found".
- `isAvailable()` also requires a PHP CLI binary (`Console::getPhpCli()`, `:45`), which the adapter does not use since conversions run in
  messenger workers – on systems where the PHP binary cannot be located, video support is reported as unavailable.

**Fix direction:** `setTimeout()` based on the source duration (e.g. 10× duration, minimum some minutes) plus `setIdleTimeout()`; check
`fopen()`; rotate / delete `.error.log` files (maintenance task) or log through Monolog; validate the source file; drop the PHP CLI check.

---

## Workflow

> Scope 42 notes: see TODO-1 (Workflow section).

## [ ] B-209 – `Manager`: notes data leaks to the next transition after an exception; global actions skip their guard; `ensureInitialPlace()` assumes data objects

**Files:** `lib/Workflow/Manager.php:222-248`, `:255-295`, `:327-363`; `lib/Workflow/GlobalAction.php:79-91`

- `applyWithAdditionalData()` / `applyGlobalAction()` put the form data on the **shared** `NotesSubscriber` and reset it only on the success
  path (`:230-234`, `:268-288`). If `apply()` throws (guard blocked, `ValidationException` for a required note field, save error), the data stays
  on the service: in a batch run, a messenger worker or a worker runtime the next transition of any element writes the previous user's comment
  and additional fields into its note / object fields.
- `applyGlobalAction()` never calls `GlobalAction::isGuardValid()` and writes the marking directly through the marking store, so neither the
  global action's `guard` expression nor Symfony's guard events are applied. Whether the admin UI checks the guard before calling it could not be
  verified (admin bundle not in this repo); every other caller bypasses it.
- `ensureInitialPlace()` calls `$subject->getOmitMandatoryCheck()` / `setOmitMandatoryCheck()` (`:353-356`) for every marking store except
  `StateTableMarkingStore`; only `DataObject\Concrete` has these methods → `Error` for documents / assets that use a property-based marking store.

**Fix direction:** reset in `finally` – better, drop the shared state and read the data from the workflow **context**, which `apply()` already
receives (`$event->getContext()`, I-178); check the guard (and dispatch a guard event) in `applyGlobalAction()`; guard the omit-mandatory calls
with `instanceof Concrete`.

## [ ] B-210 – Notes, state-table marking and notifications are persisted before the subject is saved: a failed save leaves a transition that never happened

**Files:** `lib/Workflow/Manager.php:222-248`; `lib/Workflow/EventSubscriber/NotesSubscriber.php:68-80`, `:141-169`;
`EventSubscriber/NotificationSubscriber.php:66-95`; `MarkingStore/StateTableMarkingStore.php:59-73`

- Symfony's `apply()` stores the marking and dispatches `workflow.completed` **before** `Manager::applyWithAdditionalData()` saves the subject
  (`:239-245`). In that window:
  - `StateTableMarkingStore::setMarking()` writes the new place to `element_workflow_state` immediately;
  - `NotesSubscriber::onWorkflowCompleted()` saves the action note;
  - `NotificationSubscriber` sends e-mails / Pimcore notifications ("… was moved to *approved*").
- If the following `$subject->save()` fails (mandatory field, validation, version conflict, DB error), the element data is unchanged but the
  workflow place, the note and the notifications say otherwise. There is no transaction around the whole operation (models C-34 covers the
  notification service's own broken transaction).

**Fix direction:** wrap transition + save in one DB transaction and send notifications after commit (e.g. on `postUpdate` of the element or via
a messenger message dispatched after commit); create the note after the save.

## [ ] B-211 – `StateTableMarkingStore` fails for unsaved elements and splits place names on commas

**Files:** `lib/Workflow/MarkingStore/StateTableMarkingStore.php:36-73`; `models/Element/WorkflowState.php:37`, `:57`; `lib/Workflow/Manager.php:389-393`

- `WorkflowState::getByPrimary(int $cid, …)` is called with `$subject->getId()`, which is `null` for a new, unsaved element → `TypeError`
  (strict file). `Manager::getWorkflowUserPermissions()` catches only `LogicException` around `getMarking()`, so `AbstractElement::isAllowed()` on
  a new element (e.g. a permission check in an importer or a custom controller before the first save) throws instead of returning a result.
- `setMarking()` for an unsaved element passes `null` to `setCid(int)` → `TypeError`; with an id of `0` all new elements would share one row.
- Places are stored as `implode(',', …)` and read back with `explode(',', …)`: a place name containing a comma is split into two places.

**Fix direction:** return an empty marking / skip persistence while `getId()` is `null`; validate place names (no `,`) at config time or store JSON.

## [ ] B-212 – Notes additional fields: undefined index for omitted fields, `empty()`-based required check, date conversion `TypeError`s

**Files:** `lib/Workflow/EventSubscriber/NotesSubscriber.php:113-139`, `:210-221`; `lib/Workflow/Service.php:35-76`

- `getAdditionalDataForField()` reads `$additional[$fieldConfig['name']]` without `isset` (`:214`): an optional field that the client does not send
  (unchecked checkbox, API call, global action without the form) → "Undefined array key" warning, an `ErrorException` in debug mode that aborts the
  transition.
- The required check uses `empty($data)` (`:123`): `0`, `"0"` and `false` count as missing – a required numeric field cannot be `0`, a required
  checkbox must be ticked.
- `Service::createNoteData()` for `date` / `datetime`: `setTimestamp($value / 1000)` gets a `float` whenever the milliseconds are not a multiple of
  1000, and the non-millisecond branch passes the raw request **string** – both are `TypeError`s in this strict file. An empty optional date
  becomes `1970-01-01` (`setTimestamp(0)`) or a `TypeError` (`''`).

**Fix direction:** `$additional[$name] ?? null`; required check `=== null || === ''`; `(int) round((float) $value / 1000)` with an explicit
"no value" branch that skips the note row.

## [ ] B-213 – Workflow notifications: wrong element's note text, all recipients in one `To:` header, failures swallowed

**Files:** `lib/Workflow/Notification/AbstractNotificationService.php:26-92`; `NotificationEmailService.php:57-155`; `PimcoreNotificationService.php:43-79`

- `getNoteInfo()` filters notes by `cid` only (`:29`), not by `ctype`: the e-mail / notification for object 5 contains the latest note of
  **document 5 or asset 5** if that one is newer – note text leaks to recipients who may have no access to that element.
- `sendTemplateMail()` / `sendPimcoreDocumentMail()` add all recipients of one language as `To:` (`:129-131`, `:143-145`): every recipient sees the
  others' e-mail addresses; one invalid address can fail the whole mail.
- Users without a language end up under the key `''`: `%_locale%` in the mail path becomes empty and the translator is called with locale `''`.
- Errors are swallowed: `PimcoreNotificationService` logs "Error sending Workflow change notification." without the exception; the deep link
  needs the admin-bundle route `pimcore_admin_login_deeplink` – without it `RouteNotFoundException` aborts **all** e-mails of that transition
  and only a log line remains. The subject / message translations use positional parameters, i.e. the `vsprintf()` path of B-194 (a `%` in the
  translated text or in an element path → `ValueError`, which `catch (Exception)` does not catch).

**Fix direction:** filter by `ctype` too (`Service::getElementType($subject)`); one mail per recipient (or `Bcc`); fall back to the system
language; log the exception; check `$router->getRouteCollection()->get(...)` before generating; switch the translations to named placeholders.

## [ ] B-214 – `StatusInfo` freezes the first user's language; `OptionsProvider` / dumpers build markup and DOT without escaping

**Files:** `lib/Workflow/Place/StatusInfo.php:35-43`; `lib/Workflow/Place/OptionsProvider.php:81-94`; `lib/Workflow/Dumper/GraphvizDumper.php:63-106`, `:129-150`;
`Dumper/StateMachineGraphvizDumper.php:38-49`, `:76-87`

- `StatusInfo` reads `Admin::getCurrentUser()->getLanguage()` in its **constructor**. The service is shared: the language of whoever caused the
  first instantiation (or `en` when created before authentication) is used for every later user of the process (worker runtimes, admin
  requests served by one long-running PHP worker).
- `OptionsProvider::generatePlaceLabel()` returns HTML with the configured colour and the translated label inserted unescaped (the comment says
  "disabled for the moment", but the code is active in admin context).
- Dumpers insert place / transition labels into DOT strings as `label="%s"` without escaping (`"` or `\` in a label breaks the graph); `dump()`
  reads `$options['workflowName']` **before** merging the defaults (undefined key when omitted); `findPlaces()` calls `getLabel()` on a `null`
  `PlaceConfig` when a place has no Pimcore place configuration; `dotize()` maps `a-b` and `a_b` to the same node id.

**Fix direction:** resolve the user language per call; `htmlspecialchars()` for the label and validate the colour (`/^#[0-9a-f]{3,8}$/i`);
escape DOT strings (`addcslashes($label, '"\\')`), merge options first, null-safe labels, collision-free ids (e.g. `md5`).
