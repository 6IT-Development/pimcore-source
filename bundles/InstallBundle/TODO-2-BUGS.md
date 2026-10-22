# InstallBundle – TODO 2: Bugs

Scope: `bundles/InstallBundle/src/**` plus the dependent config (`config/config.yaml`, `config/services.yaml`),
`dump/install.sql`, `bin/pimcore-install`, the root `composer.json` and the installer docs
(`doc/01_Getting_Started/02_Advanced_Installation_Topics/README.md`)
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

Wrong behaviour in specific (but realistic) situations: option combinations, configuration paths, edge-case input,
forward-compat with DBAL 4. IDs are stable – refer to them as `B-xx` in later sessions. Line numbers are from commit
`e3feff4d48`.

---

## Install steps / kernel

### [ ] B-01 – `--only-steps` breaks for valid step combinations and accepts unknown step names silently
**Files:** `src/Installer.php:171-179`, `:452-459`, `:494-497`, `:911-914`, `:921-924`, `src/Command/InstallCommand.php:176-181`, `:195-198`
- The App kernel is only set (`Pimcore::setKernel()`) and booted when `clear_cache` **or** `install_assets` is in the step
  list (`:456-459`). `--only-steps=install_bundles` (or `setup_database,install_bundles`) therefore runs
  `isBundleInstalled()` → `SettingsStore::get()` → `Db::get()` against the **InstallerKernel** container (set in
  `bin/pimcore-install:73`), which has no `doctrine.dbal.default_connection` → exception → caught in `install()` and
  returned as a single cryptic error after the previous steps already ran.
- `clear_cache` alone boots the full App kernel although nothing needs it.
- Step names are not validated: `--only-steps=setup_db` (typo) runs nothing, emits `boot_kernel`/`complete` events and
  reports **success**. Whitespace-only/empty entries are also accepted.
- The two step vocabularies differ (`write_database_config` ↔ event `create_config_files`, `mark_migrations_as_done` ↔
  `migrations`), so users reading the progress output cannot derive the `--only-steps` values (see I-05).

**Fix direction:** boot/set the App kernel whenever a step needs the container (`install_assets`, `install_bundles`,
`clear_cache` only for clearing), validate `--only-steps` against the known list (`InvalidOptionException`), unify names.

### [ ] B-02 – `clearKernelCacheDir()` fails on a mounted `var/cache` and runs while the booted kernel uses it
**File:** `src/Installer.php:646-671`, `:452-459`, `:499-501`
- `rename(var/cache, var/cach~)` throws `IOException` ("Device or resource busy") when `var/cache` is a Docker volume /
  bind mount – common in the official docker setups. The exception is **not** caught (only the final `remove()` is),
  so the whole install aborts at the very first step after `boot_kernel` – before the DB is set up – with a generic
  error.
- `PIMCORE_SYMFONY_CACHE_DIRECTORY` is `var/cache` (all environments, `lib/Bootstrap.php:222`), not the env cache dir;
  the second call (`:499-501`) deletes the container of the kernel that is still booted in this process
  (lazy-loaded service files are gone → `include(...): Failed to open stream` if anything touches the container
  afterwards, e.g. a `complete` step listener).
- The `$kernel` parameter is unused.

**Fix direction:** fall back to emptying the directory contents when `rename()` fails; shut down the kernel
(`$kernel->shutdown()`) before the final clear; drop the unused parameter.

## Database / credentials

### [ ] B-03 – Partially configured `pimcore_install.parameters.database_credentials` disables **all** DB prompts
**Files:** `src/DependencyInjection/PimcoreInstallExtension.php:43-62`, `src/Installer.php:190-193`, `:215-218`, `:360-374`, `src/Command/InstallCommand.php:311-322`
- `needsDbCredentials()` returns `false` as soon as **any** credential is configured. Configuring only
  `host: '%env(DB_HOST)%'` / `port` (the doc example mixes static and env values) skips the `mysql-username`,
  `mysql-password`, `mysql-database` options/prompts entirely, and `resolveDbConfig()` returns the partial config → DBAL
  connects as `''@host` without database → "Access denied" with no hint that the config is incomplete.
- `normalizeDbCredentials()` filters with `!empty()` at **compile time**; an `%env(DB_PORT)%` that resolves to `''` at
  runtime is passed through and overrides the default port (`port: ''`).
- The config path has no SSL / `server_version` / `driverOptions` support while the CLI path has `--mysql-ssl-cert-path`.
- `private array $dbCredentials;` has no default; it only works because `empty()` tolerates uninitialized typed
  properties – any future direct read (`$this->dbCredentials['host']`) throws `Error`.

**Fix direction:** merge configured credentials with CLI/env values per field, only skip prompts for configured
fields, validate required keys (`user`, `dbname`, `host`|`unix_socket`) and default the property to `[]`.

### [ ] B-04 – `--skip-database-structure` (and dumps containing `users` data) always fail on the system user insert
**File:** `src/Installer.php:702-725`, `:898-909`
- With `--skip-database-structure` the existing `users` table is kept, but `insertSystemUser()` still inserts
  `('user','system')` → `UNIQUE KEY type_name (type, name)` (`dump/install.sql`, `users`) → `UniqueConstraintViolation`
  → caught at `:721` → **the complete data import is skipped** and only one error is reported.
- The system user is inserted **before** the project dumps are imported (`:707` vs `:714-717`); a dump that contains
  `INSERT INTO users …` for `system` (without `DROP TABLE`) fails the same way.

**Fix direction:** `INSERT … ON DUPLICATE KEY UPDATE` / existence check, and insert the system user after the dumps if
they do not provide one.

### [ ] B-05 – Admin username `system` (or a role/folder name) deletes the system user / other users
**File:** `src/Installer.php:743-763`
```php
$db->delete('users', ['name' => $settings['username']]);   // no type filter
```
- Choosing `system` as admin username deletes the system user with **id 0** (inserted just before) and re-creates it as
  a normal admin with an auto-increment id → core code relying on user 0 (versions / notes / maintenance with
  `userModification = 0`, `User::getById(0)`) breaks.
- On dump-based installs the delete also removes roles / user folders that happen to have the same name (`type` is part
  of the unique key, `name` alone is not unique).
- No length check: `users.name` is `varchar(50)` – longer usernames fail with a DB error (strict mode) or are truncated
  (non-strict) so the user cannot log in with the name they typed.

**Fix direction:** reject reserved names (`system`), filter `delete()`/upsert by `type = 'user'`, validate length/charset
in `install()` before connecting (see I-14).

### [ ] B-06 – Server version detection uses deprecated DBAL API (fatal on DBAL 4) and the guard is dead code
**File:** `src/Installer.php:22`, `:466-476`
```php
if ($db instanceof Connection) {                       // always true – parameter is typed Connection
    $connection = $db->getWrappedConnection();         // deprecated in DBAL 3.x, removed in 4.0 → fatal
    if ($connection instanceof ServerInfoAwareConnection) {   // interface deprecated 3.x, removed in 4.0
```
- Works on the pinned `doctrine/dbal ^3.8` but logs a deprecation on every install; on DBAL 4 it becomes a fatal
  `Call to undefined method` – and `instanceof` on the missing interface would silently skip writing `server_version`.
- If the interface check ever fails, `database.yaml` is written without `server_version` and DBAL has to connect just to
  detect the platform on every request.

**Fix direction:** DBAL 3: `$db->getNativeConnection()->getAttribute(PDO::ATTR_SERVER_VERSION)`; DBAL 4:
`$db->getServerVersion()`; drop the `instanceof Connection` check and the `ServerInfoAwareConnection` import.

### [ ] B-07 – Socket detection is fragile and the port is not omitted for sockets
**File:** `src/Installer.php:360-391`, `src/Command/InstallCommand.php:110-115`
- `file_exists($hostSocketValue)` is evaluated relative to the CWD and is `true` for **directories** too: a host named
  `db` / `mysql` with an equally named directory in the project root (`docker/`, `db/` folders are common) is treated as
  a unix socket.
- `--mysql-port` says "(will be omitted if socket is set)", but `$dbConfig` starts with `'port' => 3306` (`:364`), so
  `database.yaml` always contains `port` next to `unix_socket`.
- `mysql_port` from env/CLI is a string, the default is `int 3306` → written to YAML inconsistently (`'3306'` vs `3306`).

**Fix direction:** detect sockets with `filetype($v) === 'socket'` (or an absolute path ending in `.sock`), unset `port`
for sockets, cast port to `int`.

## CLI options / env vars

### [ ] B-08 – `PIMCORE_INSTALL_*` env vars defined in `.env` / `.env.local` are ignored
**Files:** `src/Command/InstallCommand.php:254-259`, `lib/Bootstrap.php:156-157`, docs `README.md:29-37`
- `initialize()` reads `getenv($config['env'])`. `Bootstrap::bootstrap()` loads dotenv via `new Dotenv()->bootEnv()`,
  which (since Symfony 5) does **not** call `putenv()` – values land only in `$_SERVER`/`$_ENV`. Only real process env
  vars work; `.env.local` values silently don't.
- `if ($env = getenv(...))` also drops the legitimate value `"0"` (e.g. password `0`).

**Fix direction:** `$_SERVER[$name] ?? $_ENV[$name] ?? getenv($name)` and compare with `!== false`/`!== ''`.

### [ ] B-09 – `PIMCORE_INSTALL_INSTALL_BUNDLES` is advertised in `--help` but never read
**File:** `src/Command/InstallCommand.php:122-132`, `:149-152`, `:214-226`, `:230-260`, `:317-319`
- Every option gets an `env` key and is listed in the help text, but `installerNeedsOption()` returns `false` for the
  `bundles` group, so the env loop skips it; and `--install-bundles` is evaluated (`:214`) **before** the env loop
  (`:230`) anyway.

**Fix direction:** resolve the bundle option from env before `:214`, or remove it from the env list/help.

### [ ] B-10 – `--install-bundles` parsing: no trimming, unknown names silently dropped, empty value re-triggers the prompt
**Files:** `src/Command/InstallCommand.php:214-226`, `:329-333`, `src/Installer.php:220-230`
- `explode(',', $bundleOption)` without `trim()` → `--install-bundles="PimcoreSeoBundle, PimcoreUuidBundle"` installs
  only the first bundle.
- `setBundlesToInstall()` maps unknown keys to `null` and `array_filter()`s them away – a typo produces **no warning**.
- `--install-bundles` without value (documented as `--install-bundles[=bundleList]`) → option is `null` → in
  `execute()` `!$input->getOption('install-bundles')` is `true` → in interactive mode the user is asked again and the
  answer overrides the CLI decision (required bundles chosen in `initialize()` are replaced).

**Fix direction:** trim + filter empty entries, report unknown keys as an error (list valid keys), check
`false !== $input->getOption('install-bundles')` in `execute()`.

### [ ] B-11 – Interactive bundle prompt breaks with empty/stale recommendations
**Files:** `src/Command/InstallCommand.php:329-333`, `:445-454`, `src/Event/BundleSetupEvent.php:46-60`
- `getRecommendBundles()` uses `array_search()` (loose, may return `false`) → `implode(',', [0, false, 3])` = `"0,,3"`
  → Symfony's multiselect `ChoiceQuestion` rejects the default → "Value "0,,3" is invalid", the user cannot just press
  Enter as the prompt promises.
- After `clearBundlesAndRecommendations()` (documented for demos) + `addInstallableBundle(..., recommend: false)`, the
  default is `''` → also rejected; the confirm reads "We recommend ." 
- `addInstallableBundle(..., true)` twice adds the key twice to `$recommendations`.

**Fix direction:** strict `array_search`, filter `false`, `array_unique()` recommendations; pass `null` as default when
nothing is recommended and adapt the confirm text.

### [ ] B-12 – Interactive prompts reject an empty MySQL password and the value `"0"`
**File:** `src/Command/InstallCommand.php:268-309`, `:349-350`
- The validator throws for `empty($answer)` on **every** option, including `mysql-password` – although `execute()`
  explicitly allows an empty password (`:349`). Local MySQL/MariaDB with socket auth or passwordless dev users cannot be
  installed interactively (the prompt loops forever).
- `empty('0')` is `true` → `0` is rejected as username/password/database name.

**Fix direction:** per-option `allow-empty` flag (mysql-password, mysql-ssl-cert-path), validate with
`'' === trim((string) $answer)` / `null === $answer`.

### [ ] B-13 – Required bundles are skipped in non-interactive mode without `--install-bundles` and when the user declines
**Files:** `src/Command/InstallCommand.php:214-226`, `:327-333`, docs `README.md:50-52`, `:88`
- `setBundlesToInstall()` is only called when `--install-bundles` is given or the user answers "yes" to "Do you want to
  install bundles?". Bundles added via `addRequiredBundle()` (e.g. the skeleton's `PimcoreAdminBundle`) are therefore
  **not** installed in `-n` mode without `--install-bundles`, and not when the confirm is declined or `getBundles()` is
  empty (demo case: `clearBundlesAndRecommendations()` → the prompt is not even shown).
- The docs describe this, but it contradicts the term "required" and makes `-n` installs without the flag produce a
  project whose required bundles are missing.

**Fix direction:** always install required bundles; the flag/prompt should only control the optional ones. Update docs.

## Files written by the installer

### [ ] B-14 – `BundleWriter` rewrites `config/bundles.php` destructively and can produce invalid PHP
**File:** `src/BundleConfig/BundleWriter.php:23-62`, `src/Installer.php:586-598`
- `include`s the existing file and regenerates it from the returned array: comments, conditional entries
  (`class_exists(...) ? ... : ...`, `$_SERVER['APP_ENV']` checks) and any non-array code are lost.
- An entry with an empty env array (`Foo::class => []`) makes `substr($contents, 0, -2)` cut off `=> [` →
  `    Foo::class =],` → **parse error, the whole project is broken** (no lint after writing).
- `array_merge($bundlesToInstall, $enabledBundles)` puts the newly installed bundles **before** all existing ones
  (incl. `FrameworkBundle`, `PimcoreCoreBundle`) – bundle order defines template/translation override and
  `prepend()`/compiler-pass order.
- `include` of a file that returns a non-array (or throws) is not handled; `in_array()` is loose (`:33`).

**Fix direction:** append new entries after existing ones, keep the original file content and only insert missing
lines (or use a `config/bundles.local.php`-style include), `var_export`/`php -l` the result, handle empty env arrays.

### [ ] B-15 – `config/local/database.yaml` with the plain DB password is written world-readable
**File:** `src/SystemConfig/ConfigWriter.php:35-42`, `src/Installer.php:424-434`, `:471-473`
- `Filesystem::dumpFile()` creates the file with `0666 & ~umask` (usually `0644`) – readable by every local user /
  other containers sharing the volume. The password is stored in clear text.
- `Yaml::dump($config)` uses inline level 2 → `connections: { default: { host: …, password: … } }` on one line
  (hard to review/edit; see I-09).

**Fix direction:** `chmod 0600` (or `0640`) after writing; prefer writing `DATABASE_URL`/env references (I-09).

### [ ] B-16 – `getDataFiles()` picks up unintended files and can return `false`
**File:** `src/Installer.php:738-741`, `:780`
- `glob(PIMCORE_PROJECT_ROOT . '/dump/*.sql*')` matches `*.sql.bak`, `*.sql~`, `*.sql.zip`, `*.sql.orig` – they are
  imported (zip as raw bytes → SQL error, and see C-03/C-04).
- `glob()` returns `false` on error → `TypeError` against the `array` return type (`strict_types`).
- Order is plain `glob()` sort, not natural (`10_x.sql` before `2_y.sql`).
- `str_contains($file, 'atomic')` (`:780`) tests the **full path** – a project located in `/srv/atomic-shop/` imports
  every dump as one atomic statement.

**Fix direction:** match `*.sql` and `*.sql.gz` explicitly, `?: []`, `natsort()`, test `basename($file)`.

## Seed data / smaller issues

### [ ] B-17 – Seed rows reference a non-existent user id 1
**File:** `src/Installer.php:807-852`, `:898-909`, `:743-763`
- Root asset/document/object are inserted with `userOwner = userModification = 1`. The system user is inserted first
  (gets id 1) and is then moved to id 0; `AUTO_INCREMENT` stays at 2, so the admin created afterwards gets **id 2**.
  User 1 does not exist → owner shown as unknown, "my elements" filters / owner-based permissions don't match.

**Verify:** fresh install → `SELECT id, name FROM users;` and `SELECT userOwner FROM documents WHERE id = 1;`.

**Fix direction:** create the admin first (or use `lastInsertId()`) and use its id, or use `0` (system) consistently.

### [ ] B-18 – `formatPrerequisiteMessages()` with an empty filter returns nothing
**File:** `src/Installer.php:252-272`
- `if (empty($filterStates) || !in_array(...)) continue;` – passing `[]` (the natural "no filter") skips **every**
  check. Public method of an `@internal` class, but used as API by demos/tests. `in_array()` is loose.

**Fix direction:** `if ($filterStates !== [] && !in_array($check->getState(), $filterStates, true)) continue;`.

### [ ] B-19 – Only `Exception` is caught around connection/prerequisite checks
**File:** `src/Installer.php:306-326`
- `Requirements::checkMysql()` and the DBAL platform detection can throw `Error`/`TypeError` (e.g. unexpected
  `SHOW VARIABLES` results) → escapes `install()` as an uncaught exception with stack trace instead of an error list;
  the progress bar is left half-drawn.
- The connection opened here is never closed (also not on the early `return $errors` paths).

**Fix direction:** catch `Throwable` (log full trace), `$db->close()` in `finally`.
