# CustomReportsBundle – TODO 3: Improvements

Scope: `bundles/CustomReportsBundle/src/**` plus the dependent config (`config/**`), translations, the related docs
(`doc/18_Tools_and_Features/29_Custom_Reports.md`) and `phpstan-baseline.neon`
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-2-BUGS.md](TODO-2-BUGS.md)

Code quality, readability, performance, modernisation (Symfony 7.4 / PHP 8.4 idioms) and logical clean-ups. Nothing
here is broken on its own. IDs are stable – refer to them as `I-xx` in later sessions. Line numbers are from commit
`c9ae84b8cf`.

---

## Symfony 7.4 modernisation

### [ ] I-01 – Simplify the extension (or migrate to `AbstractBundle`)
**Files:** `src/PimcoreCustomReportsBundle.php`, `src/DependencyInjection/PimcoreCustomReportsExtension.php`
`AbstractPimcoreBundle` extends `Bundle`, so a direct switch to `AbstractBundle` needs a core variant (same situation
as GlossaryBundle I-01). Until then: create the `YamlFileLoader` once (duplicated at `:38-41` and `:53-56`), add
`#[\Override]` to `loadInternal()`/`prepend()`, and move the private helper `configureAdapterFactories()` below the
public methods. `getPath()` (`PimcoreCustomReportsBundle.php:57-60`) disappears with `AbstractBundle`.

### [ ] I-02 – Modernise the config tree
**File:** `src/DependencyInjection/Configuration.php`
- Replace `prototype('array')` / `prototype('variable')` / `prototype('scalar')` with `arrayPrototype()`,
  `variablePrototype()`, `scalarPrototype()`.
- Remove the `/** @var ArrayNodeDefinition $rootNode */` cast (typed in 7.4) – phpstan baseline entry for this file
  can then be dropped.
- Indent the builder chain by nesting level (currently flat, `:33-75` is very hard to read/review).
- Add `->info()` to `definitions` and `adapters` so `config:dump-reference pimcore_custom_reports` is self-documenting.
- Use `stringNode()` / `booleanNode()->defaultTrue()` etc. aligned with the model (fixes B-25).
- `useAttributeAsKey('name')` on `adapters` is fine; consider `->requiresAtLeastOneElement()` + default `sql` there
  instead of `config/pimcore/config.yaml`.

### [ ] I-03 – Register admin-classic integration declaratively
**Files:** `config/admin-classic.yaml`, `src/EventListener/IndexSettingsListener.php`,
`src/DependencyInjection/PimcoreCustomReportsExtension.php:50-62`
- Split `admin-classic.yaml` into config (prepended via `prependExtensionConfig`, B-26) and services
  (`config/services_admin_classic.yaml`, loaded in `loadInternal()`).
- Use `#[AsEventListener(event: AdminEvents::INDEX_ACTION_SETTINGS)]` (or the constant from the admin bundle) instead
  of the hard-coded string `pimcore.admin.indexAction.settings` and the manual `tags:` entry.

### [ ] I-04 – Adapter factories via tagged service locator, inject instead of `Pimcore::getContainer()`
**Files:** `config/services.yaml:17-25`, `src/DependencyInjection/PimcoreCustomReportsExtension.php:30-34`,
`src/Tool/Config.php:128-150`, `src/Tool/Config/Dao.php:32-45`, `src/Controller/Reports/CustomReportController.php`
- `Config::getAdapter()` is `@deprecated` but still the only call path used by the controller (5 call sites). Inject the
  locator into the controller (`#[AutowireLocator('pimcore.custom_report.adapter_factory', indexAttribute: 'type')]`
  or `#[Autowire(service: 'pimcore.custom_report.adapter.factories')] ContainerInterface $adapterFactories`) and add
  a `trigger_deprecation()` to the static method.
- Allow factories to be registered by tag (`#[AsTaggedItem('sql')]` / `autoconfigure` on
  `CustomReportAdapterFactoryInterface`) in addition to the `adapters` config map; then the
  `pimcore.custom_report.adapter.factories` service no longer needs `public: true`.
- `Config\Dao::configure()` pulls two parameters from the global container on every `new Config()` / listing load;
  pass them via a small configured service or cache them statically.

### [ ] I-05 – `services.yaml` / `routing.yaml` cleanup
**Files:** `config/services.yaml`, `config/pimcore/routing.yaml`
- `public: true` on the controller resource is redundant with `controller.service_arguments` + autoconfigure.
- `Installer` argument `@=service('kernel').getBundle('PimcoreCustomReportsBundle')` – expression language at runtime
  just to get the bundle; consider `#[Autowire(expression: …)]` in the constructor or a factory, and drop
  `public: true` once `getInstaller()` gets the installer injected (core change).
- Route resource name `_pimcore_reports` is generic and may collide; rename to `_pimcore_custom_reports`.

---

## PHP 8.4 idioms / typing

### [ ] I-06 – Add `declare(strict_types=1)` everywhere and clean the phpstan baseline
**Files:** `src/DependencyInjection/Configuration.php`, `src/EventListener/IndexSettingsListener.php`,
`src/Tool/Config/Dao.php`, `src/Tool/Config/Listing/Dao.php`, `phpstan-baseline.neon` (7 entries for this bundle)
Four files lack `strict_types`; the baseline suppresses exactly these findings. Remove the entries after the fix.

### [ ] I-07 – Constructor promotion, `readonly`, `#[\Override]`, `final`
**Files:** `src/Tool/Adapter/AbstractAdapter.php:25-33`, `src/Tool/Adapter/DefaultCustomReportAdapterFactory.php:25-35`,
all `@internal` classes
- `AbstractAdapter`: promote `$config` / `$fullConfig`; drop the three abstract re-declarations (`:35-39`) – they are
  already defined by `CustomReportAdapterInterface`.
- `DefaultCustomReportAdapterFactory`: `public function __construct(private readonly string $className)` with
  `@param class-string<CustomReportAdapterInterface>` and an `is_a($className, CustomReportAdapterInterface::class, true)`
  check (fail early at container build / construction instead of at first report view).
- `#[\Override]` on interface/parent implementations; `final` on `@internal` classes (`Sql`, controller, DAOs,
  listener) where no extension point is intended.

### [ ] I-08 – Typed contracts for adapters (array shapes, value objects)
**Files:** `src/Tool/Adapter/CustomReportAdapterInterface.php`, `src/Tool/Adapter/Sql.php`
Document/return precise shapes: `getData(): array{data: list<array<string, mixed>>, total: int}`,
`getAvailableOptions(): array{data: list<array{name: string|null, value: mixed}>}`, `getColumns(): list<string>`.
Remove the empty `/** … */` doc blocks (`CustomReportAdapterFactoryInterface.php:25-29`,
`CustomReportAdapterInterface.php:33-44`). Long-term replace the `stdClass` data-source config with a typed
`DataSourceConfig` value object (needs a BC layer for third-party adapters).

### [ ] I-09 – `Config` model cleanup
**File:** `src/Tool/Config.php`
- `getDataSourceConfig()` (`:237-251`): `return isset($this->dataSourceConfig[0]) ? (object) $this->dataSourceConfig[0] : null;`
- `getByName(): ?self` → `?static`; remove the empty doc block (`:85-89`).
- `getReportsList()` (`:105-126`) returns UI tree nodes (`cls => 'pimcore_treenode_disabled'`) – presentation logic in
  the model; move to the controller.
- `getSharedUserIds()` / `getSharedRoleIds()`: `array_filter(array_map(...))`, see I-17 for performance.
- Normalise `yAxis` to `list<string>` (B-25) and drop the `null|string|array` union.
- `__clone()` (`:420-426`) overrides `AbstractModel::__clone()` (which nulls the DAO) with the opposite behaviour –
  add a comment why, or align.

---

## SQL adapter

### [ ] I-10 – Rewrite query building with DBAL `QueryBuilder` / bound parameters
**File:** `src/Tool/Adapter/Sql.php`
- Inject `Doctrine\DBAL\Connection` (or the dedicated read-only connection from C-01) instead of calling `Db::get()` in
  four places.
- Build the outer query (`SELECT … FROM (<user sql>) AS t WHERE … ORDER BY … LIMIT`) with `QueryBuilder`,
  `setParameter()` and `setFirstResult()/setMaxResults()` instead of string concatenation + `quote()`.
- Model operators as a backed enum (`FilterOperator: string { case Like = 'like'; case Lt = 'lt'; … }`) and map with
  `match` instead of `switch` + inline `$compMapping` array (`:151-182`); the `'='` and `'eq'` branches are duplicates.
- Validate requested columns against the report's known columns (C-02).
- Rename alias `somerandxyz` (`:188-193`) to something meaningful (`report_source`), build the shared
  `FROM (…) AS … WHERE …` part once.

### [ ] I-11 – Remove dead parameters and unify error handling
**File:** `src/Tool/Adapter/Sql.php:78`, `:131`, `:208`
`$ignoreSelectAndGroupBy` and `$selectField` of `buildQueryString()`/`getBaseQuery()` are never set by any caller
(always `false`/`null`) – remove the branches (`:87-90`, `:107`). `getColumns()` throws while `getBaseQuery()` returns
`null` for the same condition – use one dedicated exception (`InvalidReportQueryException`) in both.

---

## Controller

### [ ] I-12 – Request DTOs instead of manual parameter parsing and setter loops
**File:** `src/Controller/Reports/CustomReportController.php`
Use `#[MapRequestPayload]` / `#[MapQueryParameter]` / `#[MapQueryString]` with validated DTOs
(`ReportDataQuery`, `ReportFilter`, `UpdateReportRequest`) – removes the repeated `json_decode` /
`getString()` code, fixes B-06/B-13/B-14 and C-04 structurally. Replace the two dynamic setter loops (`:130-135`,
`:177-182`) by an explicit mapper.

### [ ] I-13 – Deduplicate report loading + authorization; split the controller
**File:** `src/Controller/Reports/CustomReportController.php`
`Tool\Config::getByName(...)` + `if (!$report) throw $this->createNotFoundException();` is repeated 9×. Introduce
`private function loadReport(string $name, bool $forWrite = false): Config` (404 / 403 / `ConfigWriteException`), or an
argument value resolver. Split into `CustomReportConfigController` (`reports_config`: tree/add/delete/clone/get/update/
column-config) and `CustomReportDataController` (`reports`: list/data/chart/drill-down/csv) – permissions become
class-level (`#[IsGranted]`-like helper) and easier to audit. Make `isValidConfigName()` `private` and return `bool`.
Use proper HTTP exceptions (`BadRequestHttpException`, `ConflictHttpException`, `NotFoundHttpException`) instead of
generic `Exception`.

### [ ] I-14 – Admin-classic dependency check in one place
**File:** `src/Controller/Reports/CustomReportController.php:269-271`, `:446`
`class_exists(QueryParams::class)` is checked in `dataAction` and again (with a leading-backslash string literal
`'\Pimcore\Bundle\AdminBundle\Helper\QueryParams'`) in `getSortAndFilters()`; `chartAction` silently ignores sorting
without it. Either declare `pimcore/admin-ui-classic-bundle` as a hard requirement for the controller or move the
sort-parameter parsing into the bundle (it is ~10 lines) and drop the dependency.

### [ ] I-15 – Stream the CSV export instead of polling temp files
**File:** `src/Controller/Reports/CustomReportController.php:344-428`
Replace `create-csv` (5000-row chunks, `set_time_limit(300)`, temp files) + `download-csv` with a single
`StreamedResponse` that iterates `Connection::iterateAssociative()` and writes to `php://output` – fixes C-05, B-09,
B-13 and removes the temp-file lifecycle. If progress reporting must stay, move the export to Messenger and store the
file per user. Further details:
- `fputcsv(..., escape: '\\')` keeps the non-RFC-4180 escape behaviour; PHP 8.4 deprecates relying on the default, the
  RFC-compliant choice is `escape: ''`.
- The header row uses raw column names; use the configured column `label` when present, and run the header through
  `Service::escapeCsvRecord()` too.
- The JS double-`encodeURIComponent`s the filter and PHP `urldecode()`s it (`:353`, `report.js:569`) – fragile
  contract; send plain JSON.

### [ ] I-16 – Log swallowed exceptions
**File:** `src/Controller/Reports/CustomReportController.php:224-226`
`columnConfigAction` turns every `Exception` into `errorMessage` without logging; DB driver messages (incl. SQL text) go
straight to the UI. Log with context and return a sanitised message.

---

## Data / sharing / listing

### [ ] I-17 – Avoid N+1 lookups in sharing resolution
**Files:** `src/Tool/Config.php:343-377`, `src/Tool/Config/Listing/Dao.php:48-68`
For every report and every request `getSharedUserIds()` / `getSharedRoleIds()` run one `User::getByName()` /
`Role::getByName()` query per shared name (and `jsonSerialize()` does it again). Compare names directly against the
current user (`$user->getName()`, role names of `$user->getRoles()` resolved once) or migrate to ids (B-24).

### [ ] I-18 – `Listing\Dao` should not extend `Config\Dao`
**File:** `src/Tool/Config/Listing/Dao.php:27`
The listing DAO inherits `getByName()`, `save()`, `delete()` (which operate on a `Config` model) while its model is a
`Listing` (`@property Listing $model`). Extend `PimcoreLocationAwareConfigDao` and share only `configure()` (trait or
small config provider). `loadForGivenUser()` belongs to the listing/service layer, not to the DAO.

---

## Installer / permissions

### [ ] I-19 – Shared, idempotent permission installer + permission constants
**Files:** `src/Installer.php`, `src/Controller/Reports/CustomReportController.php`
- `self::USER_PERMISSIONS` / `self::USER_PERMISSIONS_CATEGORY` on `protected` constants defeats overriding – use
  `static::`.
- Same add/remove permission code exists in Seo, Glossary, StaticRoutes and WordExport installers – extract a core
  trait (`UserPermissionInstallerTrait`) with idempotent `INSERT … ON DUPLICATE KEY UPDATE` (fixes B-27 everywhere).
- Permission names `'reports'` / `'reports_config'` are repeated as string literals across the controller (12 permission checks) and the installer – introduce constants (e.g.
  `Installer::PERMISSION_REPORTS`) or an enum.

---

## Docs / translations / tests

### [ ] I-20 – Update the custom report documentation
**File:** `doc/18_Tools_and_Features/29_Custom_Reports.md:36`
The doc still names `Pimcore\Model\Tool\CustomReport\Adapter\AbstractAdapter` / `CustomReportAdapterInterface`
(pre-bundle namespace); correct is `Pimcore\Bundle\CustomReportsBundle\Tool\Adapter\…`. The SQL adapter is referenced
as the example implementation but marked `@internal` (`src/Tool/Adapter/Sql.php:24-26`); mark the public extension
points (`AbstractAdapter`, both interfaces, `DefaultCustomReportAdapterFactory`) explicitly as API. Document the
security model (C-01: `reports_config` = DB read access).

### [ ] I-21 – Translation coverage
**Directory:** `translations/`
`admin.*` exists for 19 locales, `admin_ext.*` for 17, with different sets (`admin_ext` lacks `ja`, `pt`, `ru`, `tr`,
`uk`; `admin` lacks `ro`, `sk`). Some locales contain untranslated English values (`admin.ru.yaml`:
`keybinding_customReports`, `keybinding_reports`, `mandatory_fields_missing`). `mandatory_fields_missing`,
`reports` and `keybinding_reports` are generic keys that may collide with other bundles' `admin` domain – prefix them.

### [ ] I-22 – Add tests
There are no tests for this bundle. Minimum set:
- Unit: `Sql::buildQueryString()` / `getBaseQuery()` (clause handling B-02/B-03, filters B-05…B-08, identifier
  quoting C-02), `Config::getDataSourceConfig()`, sharing filter `loadForGivenUser()`.
- Functional: every controller action for both permissions (`reports` vs `reports_config`) incl. unshared reports (C-03),
  rename via payload (C-04), CSV export across users (C-05), invalid payloads → 4xx instead of 500.
