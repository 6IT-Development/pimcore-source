# InstallBundle – TODO 1: Critical errors

Scope: `bundles/InstallBundle/src/**` plus the dependent config (`config/config.yaml`, `config/services.yaml`),
`dump/install.sql`, the entry script `bin/pimcore-install`, the root `composer.json` constraints the bundle relies on and
the installer docs (`doc/01_Getting_Started/02_Advanced_Installation_Topics/README.md`) where they define the contract
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-2-BUGS.md](TODO-2-BUGS.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

These items **destroy existing data, silently corrupt imported data, or report a broken installation as successful**.
IDs are stable – refer to them as `C-xx` in later sessions. Line numbers are from commit `e3feff4d48`.

> Notes
> - No hard PHP 8.4 / Symfony 7.4 incompatibility (removed API, implicit nullable parameter, removed Symfony class, …)
>   was found. Typed class constants (`public const string`/`array`, PHP 8.3) are used in `InstallEvents` / `Installer`,
>   `bin/pimcore-install` → `Bootstrap` uses `new Dotenv()->bootEnv()` (PHP 8.4 syntax) – matches `"php": "~8.4.0"`.
> - Forward-compat blockers that are **not** broken today (root `composer.json` pins `doctrine/dbal: ^3.8`) are tracked
>   as bugs/improvements: DBAL 4 (B-06), Symfony 7.4 XML-config deprecation (I-20), PHP 8.5 `PDO::MYSQL_*` (I-08).
> - The installer has **no tests at all** (I-22) – every fix below needs a functional install test against MySQL/MariaDB.
> - C-02 and C-03 share the root cause "errors are logged/written to the buffered output but never returned to
>   `install()`"; fix them together with I-06 (split `Installer`, one error-collecting process runner).

---

## [ ] C-01 – Installer drops every Pimcore table in the target database without any guard → data loss

**Files:** `src/Installer.php:673-700`, `dump/install.sql` (56× `DROP TABLE IF EXISTS`), `src/Command/InstallCommand.php:335-337`

```php
if ($this->createDatabaseStructure) {
    $mysqlInstallScript = file_get_contents(__DIR__ . '/../dump/install.sql'); // DROP TABLE IF EXISTS `assets`; …
    ...
    $db->executeQuery($sql);
```

- `setupDatabase()` runs the full `install.sql` (56 `DROP TABLE IF EXISTS` + `CREATE TABLE`) against whatever database
  the credentials point to. There is **no check** whether the database already contains a Pimcore installation (e.g.
  `users` / `settings_store` / `migration_versions` exist) or any tables at all.
- The only safety net is the generic "This will install Pimcore with the given settings. Do you want to continue?"
  confirm (`InstallCommand.php:335`), which does not name the target host/database and is **skipped completely with
  `-n`/`--no-interaction`** – the documented automation mode (`doc/.../README.md:8-36`).
- Typical failure: a CI / Docker entrypoint re-runs `vendor/bin/pimcore-install -n …` on container restart, or a developer
  points `PIMCORE_INSTALL_MYSQL_DATABASE` at the wrong schema → all assets/documents/objects/users metadata are gone.
  `SET FOREIGN_KEY_CHECKS=0` (`:675`) makes the drops succeed even with FK-linked custom tables.

**Fix direction:** before structure creation, query `information_schema.TABLES` for the target schema; if Pimcore core
tables (or any tables) exist, abort with a clear error unless an explicit `--force-drop-existing` (name TBD) option is
given. Show `user@host/dbname` in the interactive confirm. Keep `--skip-database-structure` behaviour (see B-04).

---

## [ ] C-02 – Failing sub-steps are swallowed → "Pimcore was successfully installed", exit code 0, half-installed system

**Files:** `src/Installer.php:508-550` (`runCommand()`), `:600-635` (`installAssets()`), `:552-584`, `:403-506`
(`runInstall()`), `src/Command/InstallCommand.php:404-423`

```php
} catch (ProcessFailedException $e) {
    $this->logger->error($e->getMessage());
    if (null === $io) { return; }
    ...
    $stdErr->note($taskName . ' failed. Please run the following command manually:');
}
// nothing is added to $errors – runInstall() returns [] → execute() prints success and returns 0
```

- `runCommand()` / `installAssets()` return `void`; a failed `pimcore:deployment:classes-rebuild`,
  `doctrine:migrations:sync-metadata-storage`, `doctrine:migrations:version --all --add`, `pimcore:bundle:install <X>`
  or `assets:install` only writes a note into the **buffered** error output. `runInstall()` returns the (empty) `$errors`
  array → `InstallCommand::execute()` prints `Pimcore was successfully installed` and returns `0`.
- Worst case is the migrations step: if marking all core migrations as done fails, the next deploy's
  `doctrine:migrations:migrate` tries to run **every** core migration against the freshly created (already up-to-date)
  schema and fails / corrupts it. A failed bundle install leaves the bundle registered in `config/bundles.php` (written
  beforehand, `:575`) but without its tables → the application crashes on first request.
- Automated installs (`-n`) rely on the exit code only – they cannot detect any of this.

**Fix direction:** let `runCommand()` / `installAssets()` return (or throw) an error; collect them into `$errors` in
`runInstall()` and decide per step whether to abort (classes, migrations) or continue (assets, single bundle). Return a
non-zero exit code (`Command::FAILURE`) when any step failed and print the manual-fix hints in the error listing.

---

## [ ] C-03 – SQL dump import batches up to 501 statements into one multi-query → errors after the first statement are lost, packet size logic is inverted

**File:** `src/Installer.php:769-805` (`insertDatabaseDump()`), `:709-720`

```php
// execute queries in bulk mode to prevent max_packet_size errors
$batchQueries = [];
foreach ($singleQueries as $m) {
    ...
    if (count($batchQueries) > 500) {
        $db->executeStatement(implode("\n", $batchQueries));   // one multi-statement query
```

- `executeStatement()` without parameters ends up in `PDO::exec()`. With pdo_mysql multi-statements, only the result /
  error of the **first** statement is reported; failures of statements 2…501 are not raised (or surface later as
  "Cannot execute queries while other unbuffered queries are active" on an unrelated query). A dump that fails half-way
  (unknown column, duplicate key, wrong charset) is reported as imported successfully.
- The comment claims batching prevents `max_allowed_packet` errors – it does the **opposite**: 501 mysqldump
  extended-INSERT lines (each often several MB) are concatenated into one packet. Single statements are the smallest
  possible packets.
- Off-by-one: `> 500` flushes at 501 statements.
- `*atomic*` dumps (`:780-781`) send the whole file as a single packet → same two problems, unbounded.
- `explode(";\n", …)` (`:784`) breaks on `;\n` inside string literals (text/HTML/JSON data in `documents_editables`,
  `objects_*`, `translations_*`), does not split CRLF dumps (`;\r\n`) at all (→ whole file = one batch), and cannot handle
  `DELIMITER` blocks (triggers / procedures).

**Verify:** import a dump with an invalid statement at position 2 of a batch and check that `install()` returns no error.

**Fix direction:** execute statements one by one (a streaming splitter that respects quotes, comments and `DELIMITER`),
or delegate to the `mysql` CLI client (`Process(['mysql', …])` with stdin = dump), or use mysqli `multi_query()` and
iterate `next_result()` checking errors. Report the failing statement number / file in the returned errors.

---

## [ ] C-04 – Comment-stripping regex corrupts dump data (and is broken itself)

**Files:** `src/Installer.php:681` (install.sql), `src/Installer.php:778` (user dumps in `PIMCORE_PROJECT_ROOT/dump/`)

```php
$dumpFile = preg_replace("/\s*(?!<\")\/\*(?![!+])[^\*]+\*\/(?!\")\s*/", '', $dumpFile);
```

- `(?!<\")` is a typo for the negative look-behind `(?<!\")` – as written it is a meaningless look-ahead ("not followed
  by `<"`"), so there is **no** protection for string contents at all; the trailing `(?!\")` only checks one character.
- The regex therefore removes every `/* … */` sequence **inside string literals** of the imported data – CSS/JS in
  `documents_editables`, WYSIWYG HTML, serialized class definitions, code snippets in objects – together with the
  surrounding whitespace (`\s*` on both sides): `'a /* b */ c'` becomes `'ac'`. This is silent data corruption of the
  user's demo/project dump.
- `[^\*]+` cannot match comments that contain a `*` (e.g. `/* see *notes* */`) → those comments stay in the SQL.
- Applied to the whole dump in memory (see I-10) – for large dumps this is also a PCRE backtracking/memory risk
  (`preg_replace` returns `null` on `PREG_BACKTRACK_LIMIT_ERROR`, which is then passed to `str_contains()` /
  `explode()` → `TypeError` under `strict_types`).

**Fix direction:** do **not** pre-process user dumps (MySQL parses comments itself); if splitting is needed, use a
tokenizer that skips quoted strings (C-03). For `install.sql`, drop the trailing `/* … */` comments from the file (use
`-- ` comments) or strip them with the same tokenizer. Check `preg_*` return values.
