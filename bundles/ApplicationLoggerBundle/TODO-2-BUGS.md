# ApplicationLoggerBundle – TODO 2: Bugs

Scope: `bundles/ApplicationLoggerBundle/config`, `bundles/ApplicationLoggerBundle/src`
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

Functional defects: wrong results, wrong HTTP status codes, edge-case crashes, lost/misattributed data.
IDs are stable – refer to them as `B-xx` in later sessions. Line numbers are from commit `7162d16812`.

---

## Controller (`src/Controller/LogController.php`)

### [x] B-01 – Date filters ignore time zones (stored UTC vs. server/browser local time)
**Lines:** 91-99, 198-209
Rows are written in **UTC** (`ApplicationLoggerDb::write()`), but `parseDateObject()` builds `new DateTime(...)` in the
server's default time zone and binds it with `Types::DATETIME_MUTABLE` without converting to UTC. The values come from
ExtJS date/time fields (browser local time, no offset). Result: the from/to filter is shifted by the TZ offset (e.g.
1–2 h in Europe/Budapest). The response converts back with `new Carbon($row['timestamp'], 'UTC')`, so only the filter is
wrong.
**Fix direction:** interpret the input in the admin user's/browser TZ (send offset from JS or use the user's TZ setting),
convert to UTC before binding.

### [x] B-02 – PID filter uses `LIKE '%pid%'`
**Lines:** 113-115
Filtering for PID `12` also returns `112`, `1203`, … and cannot use an index. PID is an integer → use `pid = :pid`.

### [x] B-03 – Null user causes a 500 instead of 403
**Lines:** 50-55
`getPimcoreUser()` returns `User|UserProxy|null`; `->isAllowed()` on `null` throws `Error`. `checkPermission()` in the
parent already handles `null` correctly – the event listener should use it (see also I-21).

### [x] B-04 – `show-file-object` returns 500 for missing/invalid paths
**Lines:** 171-193
- `Symfony\Component\Filesystem\Exception\FileNotFoundException` is not an `HttpExceptionInterface` → 500 instead of 404.
- `filePath` containing traversal (`../`) makes Flysystem throw `PathTraversalDetected` → uncaught 500.
- Empty `filePath` (`''`) is passed to `fileExists()` unchecked.
**Fix direction:** throw `NotFoundHttpException`, catch `FilesystemException` → 404/400.

### [x] B-05 – Paging parameters not validated
**Lines:** 71-72
Negative `start` produces `OFFSET -n` → SQL syntax error (500). `limit` is unbounded (`limit=10000000` loads the
whole table into memory). Clamp `start >= 0` and `1 <= limit <= max` (e.g. 1000).

### [x] B-06 – Sorting by arbitrary/unknown column produces SQL errors; silently disabled without AdminBundle
**Lines:** 75-84
`orderKey` comes straight from the request; it is quoted (no injection) but any unknown column → SQL error 500. Should be
whitelisted against the known grid columns (`id`, `pid`, `timestamp`, `priority`, `component`, `relatedobject`,
`source`, `message`, `fileobject`). Additionally, when `Pimcore\Bundle\AdminBundle\Helper\QueryParams` is not installed
the sort request is ignored without notice (relevant since the branch is removing the admin bundle dependency – see I-22).

---

## ApplicationLogger (`src/ApplicationLogger.php`)

### [x] B-07 – Wrong / stale `relatedObjectType` for numeric related objects
**Lines:** 130-145
When `$context['relatedObject']` is numeric, the type is taken from `$this->relatedObjectType`, which belongs to the
*instance-level* related object set earlier via `setRelatedObject()` (possibly `'asset'` from a previous call).
An explicit `$context['relatedObjectType']` passed by the caller is ignored and overwritten.
**Fix direction:** respect `$context['relatedObjectType']` if given; only use the instance type when the instance-level
object is actually used.

### [x] B-08 – Sticky / leaking state on a shared service
**Lines:** 38-46, 89-107, 115-118
- `relatedObject` is **never** reset → every later log line of the same (shared!) service is linked to that element.
- `fileObject` is reset only if it was used; when the context contains its own `fileObject`, the instance one leaks into
  the next call.
- `getInstance()` calls `setComponent()` on the **shared DI service** `pimcore.app_logger.default`, so the component of
  every other consumer of that service changes too.
- In long-running processes (Messenger workers, `pimcore:maintenance`), this state survives across messages/requests.
**Fix direction:** reset per-call state after `log()`, implement `ResetInterface` + `kernel.reset` tag (see I-08), avoid
mutating shared instances in `getInstance()`.

### [ ] B-09 – `getInstance()` edge cases
**Lines:** 50-69
- If a user defined `pimcore.app_logger.<component>` as a non-`ApplicationLogger` service (e.g. a Monolog channel, as
  the docs suggest for handlers), `->setComponent()` → fatal error, and the return type `ApplicationLogger` → `TypeError`.
- Default `$initDbHandler = false` creates a logger **without any writer** → all messages are silently dropped.
- `$container->set()` on the compiled container is a runtime mutation (fails for private/removed ids).

### [ ] B-10 – `resolveLoggingSource()` reports wrong file and can crash
**Lines:** 161-205
- Function branch prints `$previousCall['file']`, which is the file **where the enclosing function was called**, not
  the file containing the log statement (`$logCall['file']`).
- Frames invoked by PHP internals (closures passed to `array_map`, `usort`, `call_user_func`, …) have **no `file`/`line`
  key** → "Undefined array key" warning, then `normalizeFilename(null)` → `TypeError` under `strict_types` → logging
  crashes for plain-function/closure callers.
- `$logCall['class'] === __CLASS__` does not match subclasses overriding the methods → falls through to the innermost
  frame and reports the logger itself as source.

### [ ] B-11 – Legacy call signature is half-supported and throws `TypeError`
**Lines:** 252-278
`handleLog()` still parses the Pimcore-legacy form `error($msg, $relatedObject, $fileObject, $component)` via
`func_get_args()`, but the public signatures type `$context` as `array`, so passing an `ElementInterface` as 2nd argument
throws `TypeError` before reaching this code. The `instanceof ElementInterface` branch is dead; positions 2/3 are
reachable only as extra args. Decide: drop the legacy path (and document) or support it properly.

### [ ] B-12 – Only the first `previous` exception is included in the file object
**Lines:** 326-344
The recursive call passes `$includePrevious` as default `false`, so chains deeper than one level are lost
(e.g. `HandlerFailedException → RuntimeException → PDOException` loses the root cause).

### [ ] B-13 – `logException()` / `logExceptionObject()` only accept data objects as related element
**Lines:** 280-324
`?AbstractObject $relatedObject` rejects `Asset`/`Document`/`int` with `TypeError`, although the rest of the class
supports all element types. Should be `ElementInterface|int|null`.

### [ ] B-14 – Unsupported context values reach the DB insert
**Files:** `src/ApplicationLogger.php:139-145`, `src/Processor/ApplicationLoggerProcessor.php:47-53, 73-87`
A non-numeric string or a non-element object as `relatedObject`, or a non-string/non-`FileObject` `fileObject` (in the
processor path) are passed through unchanged → DBAL cannot bind an object → exception (see C-05). Behaviour is also
inconsistent: `ApplicationLogger` throws `InvalidArgumentException` for a bad `fileObject`, the processor does not.
**Fix direction:** normalise or drop invalid values (and never throw from a logger, per PSR-3 spirit).

---

## FileObject (`src/FileObject.php`)

### [ ] B-15 – Failed write still produces a log row pointing to a non-existent file
**Lines:** 37-43
The write error is only logged as a warning; the object keeps `filename`, so the log row links to a missing file
(→ B-04 500 in the UI). Expose the failure (e.g. `filename = null`) so callers/handler store `NULL`.

---

## Maintenance

### [ ] B-16 – Mail task crashes on missing/empty receivers
**File:** `src/Maintenance/LogMailMaintenanceTask.php:39-44, 94-96`
`mail_receiver` has no default → `preg_split(null)` (PHP 8.1+ deprecation, returns `['']`). Empty entries (also from a
trailing `;`/`,`) → `new Address('')` throws `RfcComplianceException` → the whole maintenance task fails on every run
and the rows are never flagged. Filter empty entries; skip sending if no valid receiver.

### [ ] B-17 – Invalid `filter_priority` produces broken SQL
**File:** `src/Maintenance/LogMailMaintenanceTask.php:55-68`
`filter_priority > 7` → undefined array index → `null` → `IN('emergency',...,,)` SQL syntax error. Negative values →
empty `IN()` → syntax error. Clamp to `0..7`.

### [ ] B-18 – Mail task race condition and failure handling
**File:** `src/Maintenance/LogMailMaintenanceTask.php:70-112`
- Rows inserted between the `SELECT` and the final `UPDATE` are flagged as checked **without being mailed**.
  Restrict the update to `id <= max(selected id)`.
- If `send()` throws, the `UPDATE` never runs → next run re-sends all batches already delivered (duplicates).
- All matching rows are loaded with `SELECT *` into memory (unbounded on first activation / after a flood).

### [ ] B-19 – Archive task is not atomic
**File:** `src/Maintenance/LogArchiveTask.php:61-92`
`INSERT INTO archive … SELECT`, file deletions and `DELETE FROM application_logs` are separate statements (the ARCHIVE
engine is non-transactional). A failure after the insert → rows are copied again on the next run (duplicates); a failure
after file deletion → live rows reference deleted files. Process in id-bounded chunks (`id <= :maxId`) so reruns are
idempotent.
Also logical: FileObjects are deleted **at archive time**, so archived rows always reference missing files, and the
later per-month folder deletion (C-01) is redundant.

### [ ] B-20 – Log rows are lost when the business transaction rolls back
**File:** `src/Handler/ApplicationLoggerDb.php:33-55`, `config/services.yaml:41-46`
The handler writes through the **default** connection. Logs written inside a transaction that is later rolled back
(typical: `catch` → log error → rollback) disappear together with the business data – exactly the entries that matter.
**Fix direction:** dedicated DBAL connection for logging (configurable), or buffer + write after commit.

### [ ] B-21 – Inconsistent identifier quoting with `archive_alternative_database`
**File:** `src/Maintenance/LogArchiveTask.php:52-54, 109`
`CREATE` uses `$db->quoteIdentifier($altDb)`, `DROP` uses manual `` `...` `` without escaping. DB names with special
chars (`-`, backtick) break the `DROP`; behaviour differs between statements.

---

## Config / DI

### [ ] B-22 – `prepend()` appends config → overrides user key bindings
**File:** `src/DependencyInjection/PimcoreApplicationLoggerExtension.php:38-48`, `config/admin-classic.yaml`
`YamlFileLoader::load()` inside `prepend()` calls `loadFromExtension()`, which **appends** the config after the
application's own config. The bundle's `default_key_bindings.application_logger` therefore wins over a project-level
override. Symfony 7.1+ supports `new YamlFileLoader($container, $locator, prepend: true)` or
`$container->prependExtensionConfig()` (or `AbstractBundle::prependExtension()` with `import(..., prepend: true)`).

### [ ] B-23 – Installer is not idempotent / uninstall leaves leftovers
**File:** `src/Installer.php:29-99`
- `addUserPermission()` uses plain `insert` → reinstall after a partially failed install / after a manual permission
  insert fails with a duplicate-key exception. Check existence first (or `INSERT IGNORE`/upsert).
- `uninstall()` drops `application_logs` but leaves all `application_logs_archive_*` tables (also in the alternative
  archive DB) and the `var/application-logger` storage files.
