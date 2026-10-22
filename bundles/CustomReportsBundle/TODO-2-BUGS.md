# CustomReportsBundle – TODO 2: Bugs

Scope: `bundles/CustomReportsBundle/src/**` plus the dependent config (`config/services.yaml`, `config/admin-classic.yaml`,
`config/pimcore/config.yaml`, `config/pimcore/routing.yaml`), the core classes involved
(`lib/Config/LocationAwareConfigRepository.php`, `lib/Model/Dao/PimcoreLocationAwareConfigDao.php`) and the admin JS
only where it defines the request contract (`public/js/pimcore/report/custom/*.js`)
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

Functional defects that produce wrong output, stale data, crashes (`TypeError` / `500`) in specific situations or broken
admin UX. IDs are stable – refer to them as `B-xx` in later sessions. Line numbers are from commit `c9ae84b8cf`.

> `Sql.php`, `CustomReportController.php` and `Config.php` declare `strict_types=1`, so every type mismatch listed
> below is a hard `TypeError`, not a silent coercion.

---

## SQL adapter (`src/Tool/Adapter/Sql.php`)

### [ ] B-01 – Keyword blacklist produces false positives and fails silently
**File:** `src/Tool/Adapter/Sql.php:64`, `:185-197`, `:37`
The regex has no word boundary: column/table names like `last_update `, `backdrop `, `price_alter `, or a string
literal `'… DELETE '` are rejected. In `getColumns()` this throws a misleading "Only 'SELECT' statements are allowed"
error; in `getBaseQuery()` it returns `null`, so `getData()` / `getAvailableOptions()` return an **empty grid with
`total = 0`** without any error message.
**Fix:** word-boundary keyword check (`/\b(…)\b/i`) as UX-only check (security handled by C-01); throw a domain
exception in both paths instead of returning `null`.

### [ ] B-02 – Clause prefix detection is case-sensitive / has no word boundary
**File:** `src/Tool/Adapter/Sql.php:83`, `:94`, `:101-102`, `:108`
- `where` is detected case-insensitively (`strtoupper`) but stripped case-sensitively
  (`preg_replace('/^\s*WHERE\s*/', …)`): `where a = 1` becomes `WHERE (where a = 1)` → SQL syntax error.
- Prefix checks use `str_starts_with()` without a word boundary: a select list starting with `selection_id`, a from
  clause `fromage_table`, a condition `where_used = 1` or `GROUP  BY` (two spaces / newline) are treated as if the
  keyword was already present → keyword missing or duplicated.
**Fix:** `preg_match('/^\s*WHERE\b/i')` / `preg_replace('/^\s*WHERE\b\s*/i', '')` (same for `SELECT`, `FROM`,
`GROUP\s+BY`).

### [ ] B-03 – Appended clauses assume the `groupby` text is the last clause
**File:** `src/Tool/Adapter/Sql.php:65`, `:107-126`
The `groupby` field is free text, so users put `ORDER BY …` / `LIMIT …` there (the UI has a separate order-by field,
but nothing prevents it). Then:
- drill-down filters append ` HAVING …` **after** `ORDER BY`/`LIMIT` → syntax error as soon as a drill-down value is
  selected;
- `getColumns()` appends ` LIMIT 0,1` → `… LIMIT 10 LIMIT 0,1` → syntax error in the column configuration dialog.
**Fix:** build the drill-down filter as an outer `WHERE` on the derived table (like the grid filters in
`getBaseQuery()`), and fetch columns via `SELECT * FROM (<sql>) AS t LIMIT 0` (see B-04).

### [ ] B-04 – `getColumns()` returns no columns when the query has no rows
**File:** `src/Tool/Adapter/Sql.php:65-72`
Columns are derived from `array_keys()` of the **first row**. A valid report whose result is currently empty (new
table, restrictive `WHERE`) yields `[]` – the column configuration dialog shows nothing and the report cannot be
configured.
**Fix:** `SELECT * FROM (<sql>) AS t LIMIT 0` and read the column names from the result metadata
(`Result::columnCount()` + `getColumnMeta()` on the native statement, or DBAL `Result::columnName($i)` where
available).

### [ ] B-05 – Drill-down options drop the value `'0'`; "empty" option is misleading
**File:** `src/Tool/Adapter/Sql.php:215-229`, `:117-121`
`!empty($d[$field]) || $d[$field] === 0` – PDO returns strings, so `'0'` is `empty()` and `=== 0` is never true →
`0` (and `'0'`) can never be selected as drill-down value. The prepended option `['name' => 'empty', 'value' => null]`
does not filter for `NULL`/empty values – `buildQueryString()` skips `null`/`''`, so it actually means "no filter".
**Fix:** keep every non-`null`, non-`''` value (`$v !== null && $v !== ''`); rename the first option to
"all"/"no filter" (translation) or implement a real `IS NULL` filter.

### [ ] B-06 – Non-scalar or incomplete filter input causes `TypeError` / warnings
**File:** `src/Tool/Adapter/Sql.php:139-183`, `:117-120`, `src/Controller/Reports/CustomReportController.php:296-297`,
`:353-357`, `:453-454`
- `filter` is decoded from user JSON without validation: missing `type`/`operator`/`property` keys →
  "Undefined array key" warnings (turned into exceptions in debug); `filter` that decodes to a scalar → `foreach`
  over non-array.
- A filter `value` or a `drillDownFilters[x][]` value that is an array → `$db->quote(array)` → `TypeError` (500).
- Unknown operators are silently ignored (filter not applied, no error).
**Fix:** validate/normalise filters into a typed value object (see I-10/I-12) and return `400` for malformed input.

### [ ] B-07 – Date filters: invalid dates crash, "equals" range misses the last minute
**File:** `src/Tool/Adapter/Sql.php:144-149`, `:166-171`
- `strtotime()` returns `false` for unparsable input → `$db->quote(false)` → `TypeError` (strict types).
- `eq` uses `strtotime($value . '+23 hours 59 minutes')` + `BETWEEN` → records in the last 59 seconds of the day are
  excluded; DST days are 23/25 h long.
- The comparison is always on unix timestamps, so it only works for integer timestamp columns – `DATE`/`DATETIME`
  columns are compared against an integer.
- Uses the PHP default timezone, not the admin user's timezone.
**Fix:** parse with `\DateTimeImmutable::createFromFormat()` (reject invalid), use `>= startOfDay AND < startOfNextDay`,
and document/handle the column type (timestamp vs. datetime).

### [ ] B-08 – Filtered columns are added to the CSV select list → rows do not match the header
**File:** `src/Tool/Adapter/Sql.php:153`, `:173`, `:178`, `:190-191`, `src/Controller/Reports/CustomReportController.php:365-371`, `:391-398`
Every filter appends its `property` to `$fields`. For the CSV export (`$fields` = exported columns) a filter on a
non-exported column adds an extra column to every data row while the header row (`fputcsv($fp, $fields)`) does not
contain it → shifted/misaligned CSV. Filtering on an exported column selects it twice (harmless for
`fetchAllAssociative`, but wasted).
**Fix:** the outer `WHERE` on the derived table does not need the column in the select list – stop appending filter
properties to `$fields` (or `array_unique` + keep header in sync).

### [ ] B-09 – Paged reads without a deterministic `ORDER BY` → CSV export may duplicate or skip rows
**File:** `src/Tool/Adapter/Sql.php:40-49`, `src/Controller/Reports/CustomReportController.php:377-380`
The CSV export fetches 5000-row chunks with `LIMIT offset,limit` in separate HTTP requests. Without `ORDER BY` (no
sort given and no `orderby` in the data source) MySQL does not guarantee a stable order between requests → rows can
appear twice or be missing; the same applies to grid paging.
**Fix:** always append a deterministic order (configured order + all selected columns as tie breaker), or export in a
single streamed pass (I-15).

### [ ] B-10 – `limit`/`start` are not validated; chart endpoint is unbounded
**File:** `src/Tool/Adapter/Sql.php:47-49`, `src/Controller/Reports/CustomReportController.php:272-273`, `:325`
- `limit=0` → `if ($offset !== null && $limit)` is false → **no LIMIT at all** (full result set in one JSON response).
- negative `start`/`limit` → `LIMIT -5,40` → SQL syntax error (500).
- `chartAction` always calls `getData(…, null, null, …)` → the whole result set is loaded into memory and serialised;
  large reports exhaust `memory_limit`.
**Fix:** clamp `start >= 0`, `1 <= limit <= N` (e.g. 1000) in the controller; cap chart rows (configurable).

### [ ] B-11 – `total` is returned as string (or `false`)
**File:** `src/Tool/Adapter/Sql.php:35`, `:38`, `:54`
`fetchOne()` returns `string|false` for `COUNT(*)` with `pdo_mysql` → the JSON contains `"total": "123"`; the ExtJS
store copes, but the documented contract (`CustomReportAdapterInterface::getData()`) is unclear and third-party
consumers break. **Fix:** `(int) $db->fetchOne(…)`.

---

## Controller (`src/Controller/Reports/CustomReportController.php`)

### [ ] B-12 – `dataAction`/`chartAction` crash for reports without data source
**File:** `src/Controller/Reports/CustomReportController.php:278-280`, `:322-324`, `:441`, `:455`
`Config::getDataSourceConfig()` returns `null` when `dataSourceConfig` is empty (new report, never saved), but
`getSortAndFilters(Request, stdClass $configuration)` is non-nullable → `TypeError` (500). Additionally
`$configuration->orderbydir` is accessed without `property_exists()`/`??` (only `orderby` is checked) → "Undefined
property" warning for data sources saved without that key.
**Fix:** `404`/`400` (or empty result) when the data source is missing; use `$configuration->orderbydir ?? ''`.

### [ ] B-13 – `createCsvAction`: `TypeError` without `drillDownFilters`, unchecked keys and file handle
**File:** `src/Controller/Reports/CustomReportController.php:354-357`, `:367-369`, `:379`, `:389-400`
- `getString('drillDownFilters')` returns `''` when the parameter is absent; `''` is passed to
  `getData(?array $drillDownFilters)` → `TypeError`. Same when the JSON decodes to a non-array. (The admin UI always
  sends `{}`, so only direct/API calls are affected.)
- `$column['export']` → "Undefined array key" for column configs saved before the `export` flag existed.
- `fopen()` result is not checked (`false` → `fputcsv(false, …)` `TypeError`), temp dir not writable is not reported.
- No exported columns → `$fields = []` → adapter falls back to `SELECT *`, but the header row is empty.
- `@unlink($exportFile)` on a freshly generated unique name is dead code.
**Fix:** `$drillDownFilters = json_decode(…, true) ?: []` with `is_array` check; `$column['export'] ?? false`;
check `fopen`; when no column is exported return `400` or export all columns with a matching header.

### [ ] B-14 – `updateAction`: warnings and `TypeError`s on incomplete or invalid payloads
**File:** `src/Controller/Reports/CustomReportController.php:171-182`
- `!is_array($data['yAxis'])` reads an undefined key when `yAxis` is not sent → warning (exception in debug).
- `decodeJson()` on invalid JSON returns `null` → `foreach (null …)`.
- Setters are strictly typed (`setSql(string)`, `setMenuShortcut(bool)`, `setXAxis(?string)`, …); `null` or a wrong
  type from the client → `TypeError` (500) after part of the setters were already applied to the in-memory object.
**Fix:** request DTO / explicit mapping with defaults (see C-04, I-12); `$data['yAxis'] ?? null`.

### [ ] B-15 – `cloneAction` ignores write target / writeability and copies metadata
**File:** `src/Controller/Reports/CustomReportController.php:108-140`
- No `isWriteable()` check (unlike `add`/`update`/`delete`). With write target `disabled`,
  `LocationAwareConfigRepository::saveConfig()` silently does nothing and the action returns `success: true`.
- `creationDate` of the source report is copied to the clone (the encode→decode→setter loop applies it), so the clone
  claims to be as old as the original.
- The source object is mutated and re-saved under the new name – works, but any future logic in `save()` that uses
  the original data source / id (e.g. `isWriteable($this->id, $this->dataSource)` in the base DAO) evaluates the
  **source** report.
- "report already exists" is thrown as a plain `Exception` → 500 instead of `409`.
**Fix:** create a fresh `Config`, copy only the definition fields, reset dates, check `isWriteable()`; throw
`ConflictHttpException`.

### [ ] B-16 – `isValidConfigName()` accepts a trailing newline and has no length limit
**File:** `src/Controller/Reports/CustomReportController.php:433-438`
`/^[a-zA-Z0-9_\-]+$/` – `$` also matches before a final `\n`, so `"report\n"` passes and is used as settings-store id /
YAML file name (`report\n.yaml`). There is no maximum length (file-name limits, `settings_store.id` column length).
The error is a generic `Exception` → 500 instead of `400`, and the message says "customer report".
**Fix:** `/^[a-zA-Z0-9_-]{1,190}\z/` (or `D` modifier), `BadRequestHttpException('The custom report name is invalid')`.

### [ ] B-17 – `columnConfigAction`: uncaught `Error` for non-list JSON
**File:** `src/Controller/Reports/CustomReportController.php:200-201`, `:212-213`
`json_decode()` without `assoc` returns `stdClass` for a JSON object → `$configuration[0]` throws
"Cannot use object of type stdClass as array" **outside** the `try` block → 500. `$item['name']` is read without
`??` for column configs without a name.
**Fix:** validate `is_array($configuration) && isset($configuration[0])`; `$item['name'] ?? null`.

### [ ] B-18 – Download/temp-file errors surface as 500 with wrong exception types
**File:** `src/Controller/Reports/CustomReportController.php:334-342`, `:413-428`
- `getTemporaryFileFromFileName()` throws `Symfony\Component\Security\Core\Exception\InvalidArgumentException`
  (security component – wrong layer, not mapped to `400`).
- A non-existing (already downloaded/deleted) file → `BinaryFileResponse` throws
  `HttpFoundation\File\Exception\FileNotFoundException` → 500.
- Empty `exportFile` → `Filesystem\Exception\FileNotFoundException` → 500.
**Fix:** `BadRequestHttpException` / `NotFoundHttpException`; `is_file()` check before creating the response.

### [ ] B-19 – `getReportConfigAction` HTML-encodes JSON values (double encoding, inconsistent API)
**File:** `src/Controller/Reports/CustomReportController.php:245-255`
`htmlspecialchars()` is applied server-side to values returned as JSON. Values with `&`, `<`, `"` (e.g. nice name
`Sales & Marketing`) are shown as `Sales &amp; Marketing` wherever the UI renders them as text, and `reportClass`
(a JS class name) is altered. Other endpoints (`get`, `tree`, `portlet-report-list`) return the same fields raw,
so the escaping strategy is inconsistent and does not protect them.
**Fix:** return raw values everywhere and escape in the ExtJS templates/renderers (`Ext.util.Format.htmlEncode`);
validate `iconClass`/`groupIconClass`/`reportClass` server-side with the same regex the UI uses
(`/^[_a-zA-Z]+[_a-zA-Z0-9-.\s]*$/`, `item.js`).

### [ ] B-20 – `create-csv` route is `GET`-only while the UI sends params via `Ext.Ajax.request` *(verify)*
**File:** `src/Controller/Reports/CustomReportController.php:344`, `public/js/pimcore/report/custom/report.js:563-572`
`Ext.Ajax.request()` without `method` defaults to `POST` when `params` are present (ExtJS `Connection`), but the route
only allows `GET`. If the admin UI does not override the default method globally, the CSV export answers `405`.
Not verifiable here (`vendor/` with admin-ui-classic-bundle missing) – test in a running instance; fix by setting
`method: 'GET'` in `report.js` or allowing both methods. Also: a state-changing action (writes a file) on `GET`.

---

## Model / DAO (`src/Tool/Config*.php`)

### [ ] B-21 – Report listing can contain `null` → fatal error in sharing filter
**File:** `src/Tool/Config/Listing/Dao.php:36-41`, `:57-64`, `src/Tool/Config.php:116-123`
`loadList()` adds `Config::getByName($name)` unconditionally; `getByName()` returns `null` when the data for an id is
empty/falsy (e.g. a YAML definition `my_report: {}` – listed by `fetchAllKeys()` but `if ($data)` fails in
`Dao::getByName()`), or a settings-store row was removed between listing and loading. `loadForGivenUser()` /
`getReportsList()` then call methods on `null` → fatal error for the whole report menu.
**Fix:** skip `null` results (`if ($config = Config::getByName($name)) { … }`).

### [ ] B-22 – `Listing` filter/order API is silently ignored
**File:** `src/Tool/Config/Listing.php:32-35`, `src/Tool/Config/Listing/Dao.php:32-42`
`Listing` uses `FilterListingTrait`/`OrderListingTrait` and implements the callable filter/order interfaces, but
`Dao::loadList()` never applies `$this->model->getFilter()` / `getOrder()` → `setFilter()`/`setOrder()` have no effect.
**Fix:** apply `array_filter($configs, $filter)` and `usort($configs, $order)` in `loadList()` (as other location-aware
listings do), or drop the interfaces.

### [ ] B-23 – `Dao::getByName()` breaks when called without id
**File:** `src/Tool/Config/Dao.php:51-71`
With `$id === null` (allowed by the signature) `$data['id']` is never set, so `setName($data['id'])` reads an undefined
key → `setName(null)` → `TypeError`. `$id != null` is a loose comparison (`''` → treated as "no id").
**Fix:** use `$id ?? $this->model->getName()` once and `setName($name)`; `!== null` comparisons.

### [ ] B-24 – Sharing is stored by user/role **name** and resolved to ids on every request
**File:** `src/Tool/Config.php:343-377`, `src/Tool/Config/Listing/Dao.php:58-64`
- Renaming a user/role silently removes their access; deleting a user and creating a new one with the same name
  silently grants the new account access.
- `in_array($user->getId(), …)` is a loose comparison.
**Fix:** decide on one identity (ids are stable – migrate `sharedUserNames`→`sharedUserIds`), or at least compare
names directly (`in_array($user->getName(), $this->sharedUserNames, true)`), see also I-17.

### [ ] B-25 – Config tree and model types are out of sync → `TypeError` when loading hand-written YAML
**Files:** `src/DependencyInjection/Configuration.php:39-65`, `src/Tool/Config.php:39-83`, `:273-291`
- `xAxis` is a `variableNode` in the tree, but `Config::setXAxis(?string)` → an array/number in YAML fails on load.
- `scalarNode` for `name`, `sql`, `niceName`, `group`, `pieColumn`, … accepts `int`/`float`/`bool` (e.g.
  `pieColumn: 2024`, `group: 1`), while the setters require `string` → `TypeError` in `assignVariablesToModel()`.
- `yAxis` may be `string|array|null` in the model; the UI (`report.js:345`) assumes an array.
- `id` node exists in the tree but is never written by `Dao::save()`.
**Fix:** use `stringNode()` (Symfony 7.2+) / `scalarNode()->validate()` for string fields, `arrayNode()->scalarPrototype()`
for `yAxis`, normalise `yAxis` to `array` in the model, and remove or use `id`.

---

## DI / config / installer

### [ ] B-26 – `admin-classic.yaml` is loaded in `prepend()` with a file loader → overrides app config
**Files:** `src/DependencyInjection/PimcoreCustomReportsExtension.php:50-62`, `config/admin-classic.yaml:1-13`
`YamlFileLoader::load()` registers the `pimcore_admin` section via `$container->loadFromExtension()`, which **appends**
the bundle's config after the already loaded application config. Later configs win when merging, so a project that
customises `pimcore_admin.user.default_key_bindings.reports` / `custom_reports` is overridden by the bundle defaults.
The same call also registers the `IndexSettingsListener` service during the *prepend* phase.
**Fix:** `$container->prependExtensionConfig('pimcore_admin', [...])` (or `PrependExtensionInterface` +
`Yaml::parseFile()` + `prependExtensionConfig()`), and load the listener service in `loadInternal()` guarded by
`hasExtension('pimcore_admin')` / the `AdminBundle` class (see I-03).

### [ ] B-27 – Installer fails on systems that already have the permissions
**Files:** `src/Installer.php:44-54`, `bundles/CoreBundle/src/Migrations/Version20221228101109.php:38`
`users_permission_definitions.key` is the primary key. Systems upgraded from Pimcore 10 still have `reports` /
`reports_config` (the core migration only re-categorised them), so `pimcore:bundle:install PimcoreCustomReportsBundle`
aborts with a duplicate-key exception. Uninstall removes the definitions but leaves the permission names in
`users.permissions` / roles, and leaves stored report definitions (settings store scope `pimcore_custom_reports`,
`var/config/custom_reports/*.yaml`) behind – document or clean up.
**Fix:** `INSERT IGNORE` / `ON DUPLICATE KEY UPDATE category = …` (same pattern needed in Seo, Glossary, StaticRoutes,
WordExport installers – see I-19).
