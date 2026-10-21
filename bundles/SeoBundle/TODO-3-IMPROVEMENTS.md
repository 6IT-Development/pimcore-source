# SeoBundle – TODO 3: Improvements

Scope: `bundles/SeoBundle/config`, `bundles/SeoBundle/src`
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-2-BUGS.md](TODO-2-BUGS.md)

Code quality, readability, performance, modernisation (Symfony 7.4 / PHP 8.4 idioms) and logical clean-ups. Nothing
here is currently broken. IDs are stable – refer to them as `I-xx` in later sessions. Line numbers are from commit
`7162d16812`.

---

## Symfony 7.4 modernisation

### [ ] I-01 – Migrate bundle to `AbstractBundle`
**Files:** `src/PimcoreSeoBundle.php`, `src/DependencyInjection/PimcoreSeoExtension.php`,
`src/DependencyInjection/Configuration.php`
Merge extension + configuration into `configure()` / `loadExtension()` / `prependExtension()` of an
`AbstractBundle`-based bundle (check compatibility with `AbstractPimcoreBundle` first – if it cannot extend
`AbstractBundle`, keep the extension but simplify it). `getPath()` override becomes unnecessary.

### [ ] I-02 – Inject `pimcore_seo.redirects` instead of `Pimcore::getContainer()->getParameter()`
**Files:** `src/EventListener/DocumentListener.php:69,104`, `src/EventListener/UrlSlugUpdateListener.php:43`,
`src/Model/Redirect.php:405-408`
Use `#[Autowire(param: 'pimcore_seo.redirects')] private readonly array $redirectsConfig` (or a small
`SeoConfig` value object). The model static `getStatusCodes()` can delegate to a service; also rename
`$pimcore_seo_redirects` (snake_case local var) to camelCase.

### [ ] I-03 – Attribute-based / leaner service configuration
**Files:** `config/maintenance.yaml`, `config/services.yaml`, `config/event_listeners.yaml`,
`config/sitemap_services.yaml`, `config/redirect_services.yaml`
- `maintenance.yaml`: `RedirectCleanupTask` gets `'@logger'` as positional argument although it has **no
  constructor** (dead, misleading – would silently bind to whatever first param is added later). The explicit
  `'@doctrine.dbal.default_connection'` for `LogErrorCleanupTask` is redundant with autowiring.
- Consider `#[AsTaggedItem]`/`#[AutoconfigureTag('pimcore.maintenance.task', ['type' => …])]` on the tasks and a
  single `resource:` import for `src/` with excludes instead of five YAML files listing services one by one.
- `Installer` `$bundle` via expression language (`service('kernel').getBundle(...)`) – could be `#[Autowire(expression:
  …)]` or a plain factory.

### [ ] I-04 – Logger channels and no static `Pimcore\Logger`
**Files:** `src/Redirect/RedirectHandler.php:65`, `src/Controller/RedirectsController.php:250`,
`src/Sitemap/Document/DocumentTreeGenerator.php:95`, `src/Model/Redirect.php:258`
- `LoggerInterface $routingLogger` only resolves to the `routing` channel because **StaticRoutesBundle** registers
  that channel (`bundles/StaticRoutesBundle/config/services.yaml:34`); without it, autowiring silently falls back to
  the default logger. Use `#[WithMonologChannel('routing')]`/explicit `monolog.logger` tag, or `#[Target]`.
- Replace static `Logger::error/crit` with injected loggers (testability, channel routing, context instead of
  string concatenation).

### [ ] I-05 – Inject `Connection` instead of `Db::get()`
**Files:** `src/Controller/MiscController.php:36,88,105`, `src/EventListener/UrlSlugUpdateListener.php:50`,
`src/Installer.php:49,61,74,86`
Constructor/action injection of `Doctrine\DBAL\Connection` (already done in `ResponseExceptionListener` and
`LogErrorCleanupTask`).

### [ ] I-06 – `ClockInterface` instead of `time()`
**Files:** `ResponseExceptionListener`, `RedirectsController::cleanupAction`, `RedirectCleanupTask`,
`LogErrorCleanupTask`, `DocumentListener`, `UrlSlugUpdateListener`, `Model/Redirect/Dao.php:136`,
`RedirectHandler.php:286`
Inject `Psr\Clock\ClockInterface` (Symfony Clock) – makes expiry/cleanup logic testable.

### [ ] I-07 – Typed request handling in controllers
**Files:** `src/Controller/MiscController.php`, `src/Controller/RedirectsController.php`,
`src/Controller/Document/DocumentController.php`
Use `#[MapQueryParameter]` / `#[MapRequestPayload]` DTOs (with validation constraints) for paging, sort, filter and
the redirect payload instead of manual `$request->request->getXxx()` + `json_decode`. Covers B-05, B-13, B-14, B-28.

### [ ] I-08 – Consistent controller routing
**Files:** `src/Controller/MiscController.php`, `src/Controller/SettingsController.php`
`RedirectsController`/`DocumentController` have a class-level `#[Route('/redirects')]`/`#[Route('/document')]`
prefix and `@internal`; `MiscController`/`SettingsController` don't. Add prefixes (keep route names → JS unaffected),
mark them `@internal`/`final`. `httpErrorLogFlushAction(Request $request)` has an unused parameter.

---

## PHP 8.4 idioms

### [ ] I-09 – Missing `declare(strict_types=1)`
**Files:** `src/Controller/MiscController.php`, `src/Controller/Document/DocumentController.php`,
`src/Controller/Traits/DocumentTreeConfigWrapperTrait.php`, `src/Model/Redirect/Dao.php`,
`src/Model/Redirect/Listing/Dao.php`
All other files have it. Check call sites for implicit coercions before enabling (e.g. `Redirect::getById($redirectData)`
in `Listing/Dao.php:40` receives a **string** id from `fetchFirstColumn` → must become `(int)`).

### [ ] I-10 – Constructor property promotion + `readonly`
**Files:** `RedirectHandler`, `RedirectUrlPartResolver`, `GeneratorContext`, `DocumentGeneratorContext`,
`AbstractElementGenerator`, `DocumentTreeGenerator`, `DocumentUrlGenerator`, `UrlGenerator`, `LogErrorCleanupTask`,
`RedirectEvent`, `SitemapGeneratorListener`
E.g. `SitemapGeneratorListener::__construct(private readonly iterable $generators)` instead of
`array|IteratorAggregate` + a wrong `@var IteratorAggregate|GeneratorInterface[]` docblock (`iterable<GeneratorInterface>`).
`RedirectHandler::$lock` is typed `?LockInterface` but is never null.

### [ ] I-11 – `match`, `str_contains`, arrow functions, spaceship
- `src/Redirect/RedirectUrlPartResolver.php:49-65`: `switch` → `match` (throwing default arm), `??=` for the cache.
- `src/Redirect/RedirectHandler.php:211`: `if (strpos($url, '?'))` → `str_contains($url, '?')`.
- `src/DependencyInjection/PimcoreSeoExtension.php:59-65`: `uasort(..., fn (array $a, array $b) => $b['priority']
  <=> $a['priority'])`; `isset($config['generators']) && !empty(...)` → `$config['generators'] ?? []`.
- Closures in `Csv`, `SiteRootFilter`, `RedirectHandler::getRegexFilteredRedirects()`, `Configuration` → `static fn`.

### [ ] I-12 – Backed enum for redirect type
**Files:** `src/Model/Redirect.php:42-55, 182-198`, `src/Redirect/RedirectUrlPartResolver.php`,
`src/Model/Redirect/Dao.php`, `src/Redirect/Csv.php:277-278`
`enum RedirectType: string { case EntireUri = 'entire_uri'; … }` replacing the four constants + `TYPES` +
manual validation in `setType()`. Keep the string constants as deprecated aliases for BC if needed.

### [ ] I-13 – Small type/precision fixes in sitemap filters/processors
- `PublishedFilter.php:28`: `method_exists($element, 'isPublished')` → explicit
  `$element instanceof Document || $element instanceof DataObject\Concrete` (type-safe, analysable).
- `DocumentTypeFilter.php:57,66`: `in_array(..., true)`; typed `@var string[]`; could be `readonly` promoted props.
- `ModificationDateProcessor`/`PropertiesProcessor` return type `Url|UrlConcrete|null` → `?Url` (UrlConcrete is a Url).
- `SiteRootFilter.php:63`: `array_map(fn (Site $s) => $s->getRootId(), …)`.

### [ ] I-14 – `final`/visibility clean-up
- `RedirectHandler` is `final` but has a `protected` method (`buildRedirectResponse`) → `private`.
- `Redirect` model is `final` – setters return mixed `Redirect`/`void` (`setType`, `setUserOwner`,
  `setUserModification` return `void`, others `Redirect`) → unify to `static`; `setUserModification(int)` vs
  `setUserOwner(?int)`.
- Internal services (`Csv`, `RedirectUrlPartResolver`, listeners, tasks) can be `final`.

### [ ] I-15 – `Config` static helper
**File:** `src/Config.php`
- Static `RuntimeCache` + SettingsStore access; `catch (Exception) {}` silently swallows DB errors (robots.txt then
  served empty without a trace). Turn into an injectable `RobotsTxtConfig` service, log failures.
- `preg_replace('/^robots\.txt\-/', '', …)` → `substr($id, strlen('robots.txt-'))` / `str_replace` with a constant
  shared with `SettingsController` (`'robots.txt-' . $siteId`, `'robots.txt'` scope are duplicated literals).

---

## Performance

### [ ] I-16 – `PimcoreSeoBundle::isInstalled()` costs a DB query on every listener call
**Files:** all listeners (`RoutingListener:50,63`, `ResponseExceptionListener:55`, `DocumentListener`,
`UrlSlugUpdateListener`, `SitemapGeneratorListener`)
`isInstalled()` → `SettingsStoreAwareInstaller::isInstalled()` → uncached `SettingsStore::get()` query. Every
frontend request pays at least one extra query (two on a 404). Memoize per request/process (e.g. a small
`InstallationState` service with a property cache, reset via `ResetInterface`) or check once at container build /
use the settings-store cache.

### [ ] I-17 – Exact-match redirect lookup on every request; index does not fit the query
**Files:** `src/EventListener/RoutingListener.php:48-59`, `src/Model/Redirect/Dao.php:56-95`,
`src/Resources/install/install.sql:34`
- `onKernelRequest` runs `getByExactMatch()` (DB query) for **every** request incl. `/admin` XHR, profiler, assets
  served through PHP. Skip non-default Pimcore contexts / admin requests early (only `isFrontendRequestByAdmin` is
  checked), or cache the exact-match table like the regex redirects (tag `redirect`, cleared on save).
- `INDEX routing_lookup (active, regex, sourceSite, source, type, expiry, priority)` – the `regex IS NULL OR
  regex = 0` OR-condition stops index usage after `active`; the selective column is `source`. Add
  `INDEX (source(191), sourceSite)` (or a hash column) and make `regex` `NOT NULL DEFAULT 0` (I-24).
- `sourceSite = ' . $siteId` is concatenated (int, safe) – use a bound parameter for consistency.

### [ ] I-18 – N+1 queries in `Redirect\Listing\Dao::load()` and double write in `Dao::save()`
**Files:** `src/Model/Redirect/Listing/Dao.php:36-41`, `src/Model/Redirect/Dao.php:100-124`
- `load()` selects ids, then `Redirect::getById()` per id (1 + N queries; the regex warm-up and admin grid hit this).
  Select `*` once and hydrate.
- `save()` inserts an empty row then updates it (2 queries, empty row visible in between, relies on ENUM implicit
  default); `getValidTableColumns()` called inside the loop. Insert the full row once for new records.

### [ ] I-19 – N+1 count queries in SEO panel tree
**File:** `src/Controller/Document/DocumentController.php:106-116`
For each child a new `Document\Listing` + `getTotalCount()` (`path LIKE … AND type = 'page'`). Use one grouped query
or `EXISTS` subquery for all children. Also the inner `$list` **shadows** the outer `$list` variable – rename
(`$descendantPages`).

### [ ] I-20 – CSV export: stream instead of buffering; header clean-up; league/csv API
**Files:** `src/Controller/RedirectsController.php:185-208`, `src/Redirect/Csv.php:70-117, 135-140`
- Whole listing loaded + written to `php://temp` + `toString()` → `StreamedResponse` writing row by row.
- `Content-Encoding: none` is not a valid content-coding – remove.
- league/csv: `EncloseField::addTo()`, `Writer::BOM_UTF8` constant, `new Statement()` have newer replacements
  (`Writer::forceEnclosure()`, `League\Csv\Bom::Utf8`, `Statement::create()`) and are deprecated in recent 9.x
  releases – verify against the installed version and migrate. `Reader::setOutputBOM()` is meaningless for reading
  (BOM is skipped on input by default) – remove.
- `use League\Csv\Exception;` shadows the global `Exception` name – alias it (`CsvException`).

### [ ] I-21 – CSV import reads the file twice into memory
**File:** `src/Redirect/Csv.php:124-140`
Existing in-code TODO: `file_get_contents` + `Text::convertToUTF8` + `Admin::determineCsvDialect($filename)` (re-reads
the file). Use a stream + charset stream filter; wrap the import in a transaction and clear the `redirect` cache tag
once (each `save()` clears it now).

---

## Logic / consistency

### [ ] I-22 – One service for expiry handling
**Files:** `src/Controller/RedirectsController.php:233-254`, `src/Maintenance/RedirectCleanupTask.php`
Duplicated expiry queries with interpolated `time()` (`"expiry < $now"`, `'expiry < ' . time()`), `expiry != ''`
on an int column. Extract a `RedirectExpiryService` (bound parameters, bulk `UPDATE`/`DELETE`, one cache clear) used by
controller and maintenance task. Resolves B-16.

### [ ] I-23 – Shared auto-redirect factory
**Files:** `src/EventListener/DocumentListener.php:62-165`, `src/EventListener/UrlSlugUpdateListener.php:37-85`,
`src/Controller/RedirectsController.php:69-128`
Three places build auto redirects with different defaults (expiry yes/no, regex null/false, site yes/no, duplicate
check yes/no), and the controller duplicates the create/update target-resolution block. Introduce an
`AutoRedirectCreator` service (upsert by source/site/type, site resolution, configurable TTL replacing the magic
`86400 * 365` → e.g. `pimcore_seo.redirects.auto_create_redirects_ttl`). Resolves B-18, B-19, B-21.

### [ ] I-24 – Schema modernisation via migration
**Files:** `src/Resources/install/install.sql`, `src/Installer.php`
- `CHARACTER SET utf8` (= utf8mb3, deprecated alias in MySQL 8) → `utf8mb4` (`uri` column; requires hash/prefix
  index because of the 3072-byte key limit).
- Integer display widths (`int(11)`, `int(3)`, `int(2)`, `bigint(20)`) are deprecated since MySQL 8.0.17.
- `statusCode varchar(3)` → `SMALLINT UNSIGNED`; `priority int(2) DEFAULT '0'` vs model default `1`; `regex`/`active`/
  `passThroughParameters` nullable → `NOT NULL DEFAULT 0/1`.
- Define tables via Doctrine `Schema` in the installer (and Doctrine migrations for upgrades) instead of raw SQL.

### [ ] I-25 – `LogErrorCleanupTask` comment vs. behaviour, configurable retention
**File:** `src/Maintenance/LogErrorCleanupTask.php:37-43`
Comment says "keep the history for max. 7 days (=> exactly 144h) … allowed to store the IP" – the code keeps **6
days** (144h) and no IP is stored in `http_error_log`. Fix the comment, make retention a config option
(`pimcore_seo.http_error_log.retention_days`), consider also disabling logging entirely via config.

### [ ] I-26 – Regex redirects: silent errors, logging format
**File:** `src/Redirect/RedirectHandler.php:126, 228`
- `@preg_match()` suppresses invalid patterns silently → log `preg_last_error_msg()` once per redirect id and validate
  the pattern on save (controller + CSV import).
- `$this->redirectLogger->info(<client ip>, ['Custom-Redirect ID: … -> …'])` – message and context are swapped (the
  context is a list, not key/value). Use `info('Redirect {id}: {source} -> {target}', ['id' => …, 'ip' => …])`.
- `X-Pimcore-Redirect-ID` is sent publicly on every redirect – consider sending it only for admin/debug requests
  (internal ids leak; the admin grid "test URL" filter can call the handler internally).

### [ ] I-27 – Event docblocks reference a non-existent class
**File:** `src/Event/RedirectEvents.php:22-55`
`@Event("Pimcore\Event\Model\RedirectEvent")` – actual class is `Pimcore\Bundle\SeoBundle\Event\Model\RedirectEvent`.
The `@var string` tags are redundant on typed constants. `RedirectEvent` docblock mentions "versionNote", which does
not apply to redirects.

### [ ] I-28 – `Redirect` model semantics
**File:** `src/Model/Redirect.php`
- `setPriority(0)` / `setStatusCode(0)` are silently ignored (`if ($priority)`) – callers cannot tell; validate and
  throw, or document.
- `getHttpStatus()` is unused in the repo and builds a status line with an undefined-key risk for unconfigured codes –
  deprecate/remove.
- `getById()` / `getByExactMatch()` construct + catch `NotFoundException` – fine, but `Dao::getById(?int $id = null)`
  with `$id != null` loose comparison → `?int` + `null !==`.
- `clearDependentCache()` comment "mostly called in Redirect\Dao not here" is wrong (only called here).

### [ ] I-29 – Conditional trait declaration and runtime admin-bundle checks
**Files:** `src/Controller/Traits/DocumentTreeConfigWrapperTrait.php`, `src/Controller/RedirectsController.php:130-132`,
`src/Controller/Document/DocumentController.php:71-73`
- Declaring the same trait twice inside `if (trait_exists(...)) … else …` breaks static analysis / IDEs and
  opcache preloading. Replace with a small `DocumentTreeNodeConfigProvider` service (with a no-admin fallback).
- The bundle implements `PimcoreBundleAdminClassicInterface` and its controllers are admin-only; the scattered
  `class_exists(QueryParams::class)` / `class_exists(AdminEvents::class)` checks could become one check (or a
  composer `suggest`/conditional route loading).

### [ ] I-30 – Configuration tree readability
**File:** `src/DependencyInjection/Configuration.php:39-95`
The fluent builder chains are flat (no indentation per nesting level), making `->end()` matching hard; use
`arrayPrototype()` instead of `prototype('array')`, indent per level, add `->info()` for `generators`/`priority`,
`isRequired()` for `generator_id` (B-25). `status_codes` could validate keys are 3xx integers.

### [ ] I-31 – Query building and LIKE escaping in admin grids
**Files:** `src/Controller/MiscController.php:57-69`, `src/Controller/RedirectsController.php:160`
- Manual SQL concatenation (`' WHERE ' . implode(' OR ', …)`, `' ORDER BY ' . $sort . ' ' . $dir`) → DBAL
  `QueryBuilder` with whitelisted sort and bound params.
- LIKE filters don't escape `%`/`_` (`$list->escapeLike()` exists); `code LIKE` on an int column; searching inside
  the serialized `parametersGet` matches serialization syntax (`s:3:`).

### [ ] I-32 – Long-running process safety
**Files:** `src/Sitemap/Document/Filter/SiteRootFilter.php:33,60-66`, `src/Redirect/RedirectHandler.php:59`,
`src/Sitemap/Document/DocumentTreeGenerator.php:38`
Per-instance caches (`$siteRoots`, `$redirects`, `$currentBatchCount`) live for the service lifetime → stale data in
workers (Messenger, FrankenPHP/RoadRunner). Implement `Symfony\Contracts\Service\ResetInterface` (`kernel.reset`).

### [ ] I-33 – Config file hygiene
- Missing trailing newline: `config/sitemap_services.yaml`, `config/redirect_services.yaml`, `config/maintenance.yaml`,
  `config/services.yaml`, `config/event_listeners.yaml`, `config/pimcore/config.yaml`, `config/pimcore/routing.yaml`,
  `src/Resources/install/install.sql`.
- `config/pimcore/routing.yaml`: `options.expose: true` exposes **all** bundle routes to FOSJsRouting; prefer
  per-route `options: ['expose' => true]` in `#[Route]` (only routes the JS needs).
- `config/services.yaml`: controllers are `public: true` + `controller.service_arguments` – `public` is not needed
  for controllers registered via the tag (verify Pimcore's `UserAwareController` container needs first).
- `config/pimcore/default.yaml`: `presta_sitemap.defaults` set to `~` – document why (bundle sets its own via
  processors).

### [ ] I-34 – Tests
No tests exist for this bundle. Minimum set: `RedirectHandler` (exact/regex/site/override/pass-through/status codes –
C-02, B-06), `RedirectUrlPartResolver`, `PropertiesProcessor` with invalid values (C-01), `ModificationDateProcessor`
(B-22), `DocumentTreeGenerator` sections (B-23), `UrlGenerator` context changes (B-24), `Configuration` validation
(B-25), `Csv` import/export round trip, auto-redirect creation for move/pretty URL/slug (B-17 … B-21),
`ResponseExceptionListener` with long URIs (B-01, B-02).
