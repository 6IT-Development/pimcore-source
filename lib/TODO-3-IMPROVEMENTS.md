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
**(15, partial)** `lib/Http/**` – scanned so far: `RequestHelper`, `Response/CodeInjector`, `Request/Resolver/EditmodeResolver`.
**Not yet scanned:** `ClientFactory`, `RequestMatcherFactory`, `ResponseHelper`, `ResponseStack`, `Context/PimcoreContextGuesser`,
`Exception/ResponseException`, `Request/Resolver/{Abstract,Document,OutputTimestamp,PimcoreContext,ResponseHeader,Site,StaticPage,Template}Resolver`

Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-2-BUGS.md](TODO-2-BUGS.md),
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
stated. IDs are stable – refer to them as `I-xx` in later sessions. Line numbers are from commit `765372d338` (Scope 2–15: `765372d338`).
Grouped by area: **Core cache handler** I-01–I-08, **RuntimeCache** I-09, **Warming** I-10,
**CacheClearer / SessionStatus** I-11, **Tests** I-12,
**Composer** I-13–I-15, **Config** I-16–I-22, **Console** I-23–I-29,
**Controller** I-30–I-37, **DataObject** I-38–I-45,
**Db** I-46–I-48, **DependencyInjection** I-49, **Document** I-50–I-57,
**Element** I-58–I-59, **Event** I-60–I-64,
**Exception** I-65, **Extension** I-66–I-71,
**Helper** I-72–I-77,
**Http** I-78 (partial scope).

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

## Http (partial – see scope note)

### [ ] I-78 – `RequestHelper` / `CodeInjector` / `EditmodeResolver` cleanup (partial scope)

**File:** `lib/Http/RequestHelper.php`, `lib/Http/Response/CodeInjector.php`, `lib/Http/Request/Resolver/EditmodeResolver.php`

- Constructor promotion with `private readonly` in all three; `CodeInjector` static arrays → constants; `in_array(…, true)`.
- `RequestHelper::detectFrontendRequest()` has `TODO use pimcore context here?` – use `PimcoreContextResolver`.
  `Pimcore::inAdmin()` static call → injected context. The hard-coded `/admin/document_tag/renderlet` path belongs to the
  admin bundle's route config.
- `CodeInjector::injectIntoPresetSelector()` uses regex on HTML; `<body` inside a script or comment can match. With the
  C-09 fix, use one DOM-based path for all selectors.
- Remaining `lib/Http` files are not reviewed yet; continue Scope 15 in the next session.
