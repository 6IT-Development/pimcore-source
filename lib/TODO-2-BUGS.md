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
**(15, partial)** `lib/Http/**` – scanned so far: `RequestHelper`, `Response/CodeInjector`, `Request/Resolver/EditmodeResolver`.
**Not yet scanned:** `ClientFactory`, `RequestMatcherFactory`, `ResponseHelper`, `ResponseStack`, `Context/PimcoreContextGuesser`,
`Exception/ResponseException`, `Request/Resolver/{Abstract,Document,OutputTimestamp,PimcoreContext,ResponseHeader,Site,StaticPage,Template}Resolver`

Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md),
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
IDs are stable – refer to them as `B-xx` in later sessions. Line numbers are from commit `765372d338` (Scope 2–15: `765372d338`).
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
**Http** B-88–B-91 (partial scope).

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
to the `lib/Image` scope and will be listed there.

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

## Http (partial – see scope note)

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
