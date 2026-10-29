# ApplicationLoggerBundle – TODO 1: Critical errors

Scope: `bundles/ApplicationLoggerBundle/config`, `bundles/ApplicationLoggerBundle/src`
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-2-BUGS.md](TODO-2-BUGS.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

These items cause **data loss, crashes in calling code, permanently broken maintenance, or a security issue**.
IDs are stable – refer to them as `C-xx` in later sessions. Line numbers are from commit `7162d16812`.

---

## [x] C-01 – LogArchiveTask deletes the FileObject folder of the wrong (recent) month → data loss

**File:** `src/Maintenance/LogArchiveTask.php:107-115`

```php
$deleteArchiveLogDate = Carbon::createFromFormat('Y/m', ...);
if ($deleteArchiveLogDate->add(new DateInterval('P6M')) < new DateTimeImmutable()) {
    ...
    $folderName = $deleteArchiveLogDate->format('Y/m');
```

- `Carbon\Carbon` is **mutable**: `->add()` modifies `$deleteArchiveLogDate` in place. The folder name is therefore
  computed from *archive month + `delete_archive_threshold` months* instead of the archive month.
- Verified: archive table `application_logs_archive_2026_03` → folder deleted is `2026/09`, i.e. FileObjects of logs that
  are **still live** in `application_logs` (or only recently archived) are wiped.
- Additionally, `createFromFormat('Y/m', ...)` without `!`/`|` takes day + time from *now* → on the 29th–31st the date
  overflows into the next month (e.g. `2026/02` on the 31st → `2026-03-03`), shifting the retention calculation.

**Fix direction:** use `CarbonImmutable` / `DateTimeImmutable::createFromFormat('!Y/m', ...)`, compute the expiry into a
separate variable, and derive the folder name from the original (unmodified) archive date.

---

## [x] C-02 – Archive table schema does not match `application_logs` → archiving fails permanently

**Files:** `src/Maintenance/LogArchiveTask.php:62-77`, `src/Installer.php:49-68`

- Live table: `message TEXT`; archive table: `message VARCHAR(1024)`.
  With MySQL/MariaDB strict mode (`STRICT_TRANS_TABLES`, default) `INSERT INTO archive SELECT * ...` fails with
  *"Data too long for column 'message'"* as soon as **one** row in the range has a message > 1024 chars.
  The exception aborts the task before the `DELETE` → nothing is ever archived again, `application_logs` grows unbounded,
  and the error repeats on every maintenance run.
- Other drifts: `id` signed `BIGINT` vs `BIGINT UNSIGNED`, `component`/`source` `VARCHAR(255)` vs `190`,
  `relatedobject BIGINT` vs `INT UNSIGNED`.
- `INSERT ... SELECT *` relies on **identical column order** between both tables – any future column added to the live
  table (or a differently migrated legacy table) silently shifts data into wrong columns or fails.

**Fix direction:** define the archive schema from one source of truth (same types as the live table), and use an
explicit column list in both `INSERT INTO archive (cols...)` and `SELECT cols...`. Existing archive tables created with
`VARCHAR(1024)` need a migration (or the task must create a new table version).

---

## [x] C-03 – PSR-3 level methods throw `TypeError` for `Stringable` messages

**File:** `src/ApplicationLogger.php:212-252`

`emergency()`…`debug()` accept `string|Stringable $message` (PSR-3 contract) but forward it to
`handleLog(mixed $level, string $message, array $params)`. The file has `declare(strict_types=1)`, so passing any
`Stringable` object throws `TypeError` (verified on PHP 8.x). Any code doing `$logger->error($exception)` or logging a
`TranslatableMessage`/value object crashes **in the caller**, not just in the logger.

**Fix direction:** type `handleLog()`'s `$message` as `string|Stringable` (and `log()` already accepts it).

---

## [x] C-04 – HTML injection in log summary e-mails

**File:** `src/Maintenance/LogMailMaintenanceTask.php:89-93`

```php
$html = var_export($entries, true);
$html = "<pre>$html</pre>";
$mail->html($html);
```

Log messages, components, sources and file paths frequently contain user-controlled input (request data, product
names, imported content). They are injected **unescaped** into an HTML mail sent to administrators → arbitrary markup,
phishing links, tracking pixels, CSS-based content spoofing in a trusted "Error Log <host>" mail.

**Fix direction:** `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE)` the whole dump, or send it as a text part
(`$mail->text(...)`), or render a Twig template with auto-escaping.

---

## [x] C-05 – A failing DB write in the handler crashes the calling code

**File:** `src/Handler/ApplicationLoggerDb.php:42-55`

`write()` calls `$this->db->insert()` without any guard. Monolog does not swallow handler exceptions, so every one of
the following turns a *log call* into an *application error*:

- bundle enabled but **not installed** (`application_logs` table missing) → every log throws `TableNotFoundException`;
- value too long in strict mode: `component`/`source` > 190 chars (long FQCN + method names are realistic for
  `source`), `fileobject`/`info` > 1024, `message` > 64 KB (`TEXT`);
- connection is in a rollback-only state (logging inside a failed transaction, which is exactly when errors are logged);
- connection lost / DB down.

**Fix direction:** truncate values to column sizes (`mb_substr`) before insert, and catch `Doctrine\DBAL\Exception`
inside `write()` (fall back to `error_log()` / the default Monolog channel). Consider a dedicated DBAL connection (see
B-20).
