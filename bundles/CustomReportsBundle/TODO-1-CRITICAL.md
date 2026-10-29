# CustomReportsBundle – TODO 1: Critical errors

Scope: `bundles/CustomReportsBundle/src/**` plus the dependent config (`config/services.yaml`, `config/admin-classic.yaml`,
`config/pimcore/config.yaml`, `config/pimcore/routing.yaml`) and the core classes the bundle relies on where the defect
originates in the interplay (`lib/Config/LocationAwareConfigRepository.php`, `lib/Model/Dao/PimcoreLocationAwareConfigDao.php`,
`lib/Model/AbstractModel.php`)
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-2-BUGS.md](TODO-2-BUGS.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

These items cause **SQL injection / unrestricted database access, authorization bypass, arbitrary file writes on the
server, or cross-user data leakage**.
IDs are stable – refer to them as `C-xx` in later sessions. Line numbers are from commit `c9ae84b8cf`.

> Notes
> - Permission model of the bundle: `reports_config` = may create/edit report definitions (incl. raw SQL),
>   `reports` = may only *view* reports. Several items below are reachable with the low-privileged `reports`
>   permission, which makes them more severe than "admin can run SQL by design".
> - No hard PHP 8.4 / Symfony 7.4 incompatibility (removed API, implicit nullable parameter, removed Symfony class, …)
>   was found in the bundle. `new Config()->isWriteable()` (`src/EventListener/IndexSettingsListener.php:25`) already
>   uses the PHP 8.4 "new without parentheses" syntax, so the bundle now hard-requires PHP ≥ 8.4.
> - The fix for C-01 and C-02 should be done together with the query-builder rewrite proposed in **I-10**.

---

## [x] C-01 – SQL keyword blacklist is trivially bypassable → arbitrary SQL (incl. writes) against the Pimcore DB

**Files:** `src/Tool/Adapter/Sql.php:64`, `:185`, `src/Controller/Reports/CustomReportController.php:189-233`

```php
if (!preg_match('/(ALTER|CREATE|DROP|RENAME|TRUNCATE|UPDATE|DELETE) /i', $sql, $matches)) {
```

The only protection against non-`SELECT` statements is a blacklist regex that requires a **literal space** after the
keyword:

- `DELETE\tFROM users`, `DROP\nTABLE x`, `UPDATE/**/users SET …` → not matched (tab, newline, comment instead of space).
- `INSERT`, `REPLACE`, `GRANT`, `SET`, `CALL`, `LOCK`, `HANDLER`, `LOAD DATA`, `SELECT … INTO OUTFILE/DUMPFILE`,
  `LOAD_FILE()`, `SLEEP()`/`BENCHMARK()` (DoS) are not listed at all.
- `getColumns()` appends `' LIMIT 0,1'` to the **unwrapped** SQL (`:65`) and `getData()`/`getAvailableOptions()` run it
  as sub-select; with `pdo_mysql` (emulated prepares, multi-statements enabled by default) stacked statements such
  as `1; INSERT INTO users …` are executed – *verify against the project's DBAL connection options*.
- `columnConfigAction()` (`CustomReportController.php:200-210`) executes SQL taken **directly from the request**
  (`configuration` parameter) – the SQL does not even need to be saved first.

Impact: any user with `reports_config` can read every table (e.g. `users.password`, `settings_store`, API keys in
`website_settings`), create an admin user (privilege escalation to full admin), modify/delete data or exhaust the DB.

**Fix direction:** do not rely on string matching. Execute report SQL through a **dedicated read-only DBAL connection**
(DB user with `SELECT` grants on an explicit allow-list of tables/views, `PDO::MYSQL_ATTR_MULTI_STATEMENTS => false`) or
at minimum inside `START TRANSACTION READ ONLY` + `ROLLBACK` with multi-statements disabled, plus a
`max_execution_time` hint. Keep a (word-boundary based) keyword check only as UX feedback, not as a security control.
Document clearly that `reports_config` is equivalent to DB read access.

---

## [x] C-02 – SQL injection via unquoted column identifiers – reachable with the `reports` (view-only) permission

**File:** `src/Tool/Adapter/Sql.php:190-191`, `:153`, `:173`, `:178`, `:208`, `:211`

```php
if ($fields && !$extractAllFields) {
    $data = 'SELECT `' . implode('`,`', $fields) . '` FROM (' . $sql . ') AS somerandxyz WHERE ' . $condition;
```

`$fields` is concatenated inside backticks **without escaping**. It is fed from request data:

- `drillDownOptionsAction()` → `getAvailableOptions($filters, $field, …)` → `getBaseQuery($filters, [$field], …)`:
  `$field` is `$request->request->getString('field')` (`CustomReportController.php:295`).
- every filter with operator `like|lt|gt|eq|=` appends `$filter['property']` to `$fields` (`Sql.php:153`, `:173`,
  `:178`); `createCsvAction()` passes a non-empty `$fields` list together with request filters
  (`CustomReportController.php:353`, `:379`), so `filter[].property` lands raw in the select list.

A `field`/`property` containing a backtick (e.g. ``x`,(SELECT password FROM users LIMIT 1) AS `y``) breaks out of the
identifier. The other usages of the same value are correctly quoted (`quoteIdentifier()`), which makes exploitation
less trivial, but it is still an injection sink controlled by a view-only user. In combination with C-01 (no read-only
connection) this is a full DB compromise from the lowest bundle permission.

**Fix direction:** quote every identifier with `$db->quoteIdentifier()` (or the DBAL `QueryBuilder`), and additionally
validate requested field/filter/sort names against the column list of the report (`columnConfiguration` names) –
reject unknown columns with `400`.

---

## [x] C-03 – Missing per-report authorization: sharing settings are only applied to the menu list (IDOR)

**File:** `src/Controller/Reports/CustomReportController.php:142-155`, `:265-288`, `:290-312`, `:314-332`, `:344-411`

`shareGlobally` / `sharedUserNames` / `sharedRoleNames` are only evaluated in `Listing\Dao::loadForGivenUser()`
(`src/Tool/Config/Listing/Dao.php:48-68`), i.e. only for `portletReportListAction()` and `getReportConfigAction()`.
All data endpoints load the report purely by the `name` parameter:

- `dataAction`, `chartAction`, `drillDownOptionsAction`, `createCsvAction` – any `reports` user can read the data of a
  report that was explicitly **not** shared with them by guessing/enumerating the name.
- `getAction` (permission `reports` **or** `reports_config`) returns `getObjectVars()` – the complete definition
  including the raw SQL, data-source config, and the list of shared user/role names – to a view-only user.

**Fix direction:** introduce one guard (e.g. `Config::isVisibleFor(User $user): bool` or a Symfony Voter
`CustomReportVoter`) and call it in every action that takes a report `name`; return `403`/`404`. Restrict `getAction`
to `reports_config`, or return a reduced view model (no SQL, no sharing data) for `reports`-only users.

---

## [x] C-04 – Mass assignment in `updateAction` allows renaming the report → path traversal file write

**Files:** `src/Controller/Reports/CustomReportController.php:157-187`, `src/Tool/Config/Dao.php:77-97`,
`lib/Config/LocationAwareConfigRepository.php:195-205`, `:236-241`

```php
$data = $this->decodeJson($request->request->getString('configuration'));
foreach ($data as $key => $value) {
    $setter = 'set' . ucfirst($key);
    if (method_exists($report, $setter)) {
        $report->$setter($value);
    }
}
$report->save();
```

`isValidConfigName()` is only applied to the top-level `name` parameter. The JSON `configuration` payload is applied
to **every** setter, including `setName()`. The UI does not send `name` (field is `disabled`), but a crafted request
with `configuration={"name":"../../../config/services", …}` is accepted:

- `Dao::save()` → `saveData($this->model->getName(), …)` → `LocationAwareConfigRepository::getVarConfigFile($key)`
  builds `"{dir}/{$key}.yaml"` with no sanitising → `Filesystem::dumpFile()` writes **any `*.yaml` file the PHP user
  can write**, creating directories as needed. Overwriting e.g. `config/services.yaml` or dropping a file into
  `config/packages/` takes the application down or injects container configuration (the written root key is
  `pimcore_custom_reports`, the rest is attacker-controlled data).
- With the settings-store write target the same request silently creates a second report (rename = copy).
- Other setters are reachable too: `setCreationDate`/`setModificationDate` (audit tampering), `setDao` (`dao` key →
  `TypeError` 500), `setValues` (recursive mass assignment).

**Fix direction:** never map arbitrary request keys to setters. Use an explicit allow-list / DTO (see I-12) that
excludes `name`, `creationDate`, `modificationDate`. Additionally harden the sink: validate the key in
`LocationAwareConfigRepository::getVarConfigFile()` (core) with the same strict regex (or `basename()` + equality check)
so no bundle can trigger a traversal.

---

## [x] C-05 – CSV export temp files are shared between all users: read/append/delete other users' exports

**File:** `src/Controller/Reports/CustomReportController.php:334-342`, `:382-389`, `:413-428`

```php
$exportFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/report-export-' . uniqid() . '.csv';
...
return PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $exportFileName;   // only basename() + ".csv" check
```

- The `exportFile` parameter accepts **any** `*.csv` basename inside the global system temp dir. A `reports` user can
  download (and thereby delete – `deleteFileAfterSend()`) every CSV in that directory, including exports of other
  users/reports they are not allowed to see (C-03) and CSVs created by other bundles/features in the same temp dir.
- `uniqid()` is time based (microseconds, hex) and therefore guessable; it is not a secret token.
- `createCsvAction` opens the supplied file in append mode (`fopen($exportFile, 'a')`) → a user can append rows to
  someone else's in-progress export.

**Fix direction:** bind export files to the current user/session (e.g. sub-directory per user id, or store the
generated file name in the session and accept only that), generate names with `bin2hex(random_bytes(16))`, use a
dedicated sub-directory (`PIMCORE_SYSTEM_TEMP_DIRECTORY/custom-reports/`), and verify the report permission (C-03)
again on download. Long-term: replace the polling/temp-file approach by a streamed response (I-15).
