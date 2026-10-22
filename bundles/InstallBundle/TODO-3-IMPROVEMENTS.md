# InstallBundle – TODO 3: Improvements

Scope: `bundles/InstallBundle/src/**` plus the dependent config (`config/config.yaml`, `config/services.yaml`),
`dump/install.sql`, `bin/pimcore-install`, the root `composer.json` and the installer docs
(`doc/01_Getting_Started/02_Advanced_Installation_Topics/README.md`)
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-2-BUGS.md](TODO-2-BUGS.md)

Code quality, readability, performance, modernisation (Symfony 7.4 / PHP 8.4 idioms) and logical clean-ups. Nothing
here is broken on its own. IDs are stable – refer to them as `I-xx` in later sessions. Line numbers are from commit
`e3feff4d48`.

---

## Symfony 7.4 modernisation

### [ ] I-01 – Bundle / extension / config tree
**Files:** `src/PimcoreInstallBundle.php`, `src/DependencyInjection/PimcoreInstallExtension.php`, `src/DependencyInjection/Configuration.php`
- `PimcoreInstallBundle extends Bundle` → `AbstractBundle` with `configure(DefinitionConfigurator)` +
  `loadExtension()`; `Configuration` and `PimcoreInstallExtension` can then be removed (keep `getPath()` – the class
  lives in `src/`).
- `Configuration.php:35-57` lost its nesting indentation in `14a090c5ae` ("pre ai upg") – restore it.
- `info_message` (`:37-40`) is a leftover of the removed web installer: nothing reads it (grep over `lib/`, `bundles/`,
  `config/`). Mark it `->setDeprecated('pimcore/pimcore', '12.x')` and remove later.
- Extension: `?? []` (`:45`, `:48`) are dead – the tree has `addDefaultsIfNotSet()`. `fn($value) => !empty($value)`
  (`:61`) → `static fn (mixed $v): bool => null !== $v && '' !== $v` (see B-03). Add `->info()` to the credentials
  node, `->integerNode('port')` is not possible with env placeholders – validate in `->validate()` instead.
- Add `server_version`, `driver_options`/SSL keys (B-03).

### [ ] I-02 – Clean up `services.yaml`, stop `new`-ing collaborators
**File:** `config/services.yaml`, `src/Installer.php:471`, `:596`, `:639`, `:658`, `:695`, `:698`
- `public: true` and `tags: ['console.command']` on the command resource are redundant with `autoconfigure` +
  `#[AsCommand]` (and make the command non-lazy).
- `Pimcore\Composer\PackageInfo: ~` is not used by anything in the installer container – verify and drop.
- Register `BundleWriter`, `ConfigWriter`, `Filesystem` as services and inject them; inject project dir / cache dir via
  `#[Autowire('%kernel.project_dir%')]` instead of the global constants `PIMCORE_PROJECT_ROOT` /
  `PIMCORE_SYMFONY_CACHE_DIRECTORY` (`BundleWriter.php:25`, `ConfigWriter.php:39`, `Installer.php:515`, `:522`, `:649`,
  `:740`) – makes the classes testable (I-22).

### [ ] I-03 – PHP 8.4 idioms: promotion, readonly, final, `#[\Override]`, strict types
**Files:** all of `src/**`
- Constructor property promotion (+ `readonly` where never reassigned): `InstallCommand.php:48-65`,
  `Installer.php:94-96`, `:181-188`, `InstallerKernel.php:35-42`, `InstallerStepEvent.php:27-46`.
- `ConfigWriter` is `final` but has a `protected` property (`:28`) – make it `private readonly` (or inject).
- Missing `declare(strict_types=1)`: `Event/BundleSetupEvent.php`, `Event/InstallEvents.php`,
  `BundleConfig/BundleWriter.php`.
- `final`: `InstallEvents` (constants holder – `final` + private constructor), `BundleWriter`, `InstallerStepEvent`,
  `InstallCommand`.
- `#[\Override]` on `InstallerKernel::getProjectDir/getLogDir/getCacheDir/getBuildDir/registerBundles`,
  `InstallCommand::configure/initialize/interact/execute`, `Configuration::getConfigTreeBuilder`,
  `PimcoreInstallExtension::loadInternal`, `PimcoreInstallBundle::getPath`.
- Remove `{@inheritdoc}` (`InstallCommand.php:266`), empty docblocks (`Installer.php:98-101`, `:106-135`, `:248-251`,
  `:295-298`, `:765-768`), `@var array|string[]` → `array<string, class-string>`.
- Strict comparisons: `in_array(..., true)` (`Installer.php:256`, `:408`, `:452`, `:456`, `:461`, `:466`, `:479`,
  `:484`, `:489`, `:494`, `:499`, `:577`; `InstallerKernel.php:72`; `BundleWriter.php:33`),
  `array_search(..., true)` (`Installer.php:286`, `BundleSetupEvent.php:57`, `InstallCommand.php:450`).
- `php_sapi_name()` → `PHP_SAPI` (`Installer.php:261`); `array_values(array_map(function …))` → arrow fn /
  `array_column($options, 'env')` (`InstallCommand.php:143-145`).

### [ ] I-04 – Modernise `InstallCommand`
**File:** `src/Command/InstallCommand.php`
- Return `Command::SUCCESS` / `Command::FAILURE` / `Command::INVALID` instead of `0/1/2` (`:336`, `:362`, `:372`,
  `:418`, `:423`). Aborting at the confirm should arguably be `FAILURE` for scripts.
- The description is duplicated (`:44` and `:147`) – use `$this->getDescription()`.
- The option metadata array (`:73-128`, keys `insecure`, `hidden-input`, `group`, `env`, `prompt`, `choices`) is
  stringly-typed; `prompt` / `choices` / `shortcut` are never set. Replace with a small readonly value object or an
  enum `InstallOption` with methods (`env()`, `isSensitive()`, `allowsEmpty()` – B-12).
- `getOptions()` is called in `configure()` (twice, `:141`, `:183`) – `generateBundleDescription()` therefore uses the
  static `INSTALLABLE_BUNDLES`, not the event-modified list (help is wrong when a subscriber adds bundles).
- `BundleSetupEvent` is dispatched twice (`:216`, `:327`) – dispatch once, keep the event on the command.
- `$this->io` is initialised in `initialize()`; make it a local/nullable to avoid "must not be accessed before
  initialization" when `execute()` is called directly (tests with `CommandTester` call `initialize()`, but
  `Application::doRunCommand` subclasses may not).
- Consider Symfony 7.3+ invokable command style (`__invoke` + `#[Option]`) once the option list is a value object.

### [ ] I-05 – Install steps as a backed enum
**File:** `src/Installer.php:157-179`, `:279-293`, `:403-506`
- Two independent string lists with different names (`$stepEvents` vs `$runInstallSteps`, see B-01). Introduce
  `enum InstallStep: string` (`WriteDatabaseConfig`, `SetupDatabase`, …) with `label()`; `--only-steps` validates via
  `InstallStep::tryFrom()`; events carry the enum (keep `getType(): string` for BC).
- `getStepEventCount()` always returns 11 although `install_bundles`, `create_config_files`, … may not run → progress
  bar ends at e.g. `8/11`. Compute the max from the steps that will actually run.
- `dispatchStepEvent()` computes the step number with `array_search(array_keys())` on every call – trivial with enum
  `cases()`.

### [ ] I-06 – Split the `Installer` god class (925 lines)
**File:** `src/Installer.php`
- Responsibilities: option state (9 setters), DB connection + prerequisite checks, schema creation, dump import, seed
  data, user creation, config file writing, kernel bootstrapping, cache clearing, sub-process execution, bundle
  installation. Suggested split: `InstallOptions` (readonly VO replacing the setters `:152-230`, `:921-924`),
  `DatabaseInstaller` (schema + dumps, C-03/C-04), `DataSeeder` (system/admin user, root elements, permissions),
  `ConsoleProcessRunner` (one error-collecting implementation – `runCommand()` `:508-550` and `installAssets()`
  `:600-635` contain the identical 15-line error block), `BundleInstaller` (`:573-598`, `:911-914`).
- `setupDatabase(Connection $db, array $userCredentials, array $errors = [])` passes the error array in and out –
  return a result object or throw.
- `$doctrineConfig` is defined inside one `if` (`:424`) and used inside another (`:472-473`) – correct only because both
  conditions contain `write_database_config`; make the dependency explicit.

### [ ] I-07 – DBAL usage
**File:** `src/Installer.php:306-313`, `:675`, `:691`, `:695-699`, `:727`
- `new Configuration()` without `setSchemaManagerFactory(new DefaultSchemaManagerFactory())` → DBAL 3.x deprecation
  when `DoctrineDbalAdapter::createTable()` / messenger `setup()` call `createSchemaManager()`.
- DDL / `SET` statements via `executeQuery()` → `executeStatement()`.
- `registerDoctrineTypeMapping('enum', 'string')` duplicates the `mapping_types` written to `database.yaml` (`:419-422`)
  – define once.
- Close the connection when done (`$db->close()`), see B-19.
- DBAL 4 preparation: B-06, `Doctrine\DBAL\Exception` vs global `\Exception` catch (`:322`, `:721`).

### [ ] I-08 – SSL option handling (PHP 8.4 / 8.5)
**File:** `src/Installer.php:393-398`, `:414-417`
- `PDO::MYSQL_ATTR_SSL_CA` → PHP 8.4 driver-specific constant `Pdo\Mysql::ATTR_SSL_CA` (the `PDO::MYSQL_*` aliases are
  deprecated as of PHP 8.5).
- The constant's **integer value** is written as a YAML key into `database.yaml`; the value differs between mysqlnd and
  libmysqlclient builds → the file is not portable between PHP builds/containers. Write a PHP config
  (`config/local/database.php` using the constant) or `options: { !php/const Pdo\Mysql::ATTR_SSL_CA: … }`.
- Only the CA is supported; add `--mysql-ssl-key`, `--mysql-ssl-cert`, `--mysql-ssl-verify-server-cert`.

### [ ] I-09 – Better `database.yaml` output
**File:** `src/SystemConfig/ConfigWriter.php:35-42`
- `Yaml::dump($config, 6, 4)` for a readable block-style file.
- Prefer writing `DATABASE_*` / `DATABASE_URL` into `.env.local` (Symfony convention, keeps secrets out of `config/`)
  and `url: '%env(resolve:DATABASE_URL)%'` in `database.yaml` – or at least document it; permissions see B-15.
- `writeDbConfig(array $config = [])` silently does nothing for `[]` – make the argument required.

## Logic / robustness

### [ ] I-10 – Stream dump files instead of loading them into memory
**File:** `src/Installer.php:769-805`
- `file_get_contents('compress.zlib://…')` + `preg_replace` + `explode` keep 3+ copies of the full (decompressed) dump
  in memory – multi-GB demo/project dumps exhaust `memory_limit`. Read with `gzopen()`/`SplFileObject` line by line and
  feed the statement splitter from C-03. Log progress per file/statement count.

### [ ] I-11 – `install.sql` splitting relies on luck
**File:** `src/Installer.php:678-693`, `dump/install.sql`
- `explode(';', …)` works only because `install.sql` currently has no `;` inside literals/comments (verified) and the
  comment regex (C-04) removes the `/* … */` comments (one of which contains `Installer::setupDatabase`). Use the same
  tokenizer as for dumps, or add a unit test that asserts the statement count (54 `CREATE TABLE`, 56 `DROP TABLE`).
- The comment at `:686` ("seems to be a PDO bug after some googling") – replace with the actual reason (unbuffered
  multi-statement results).
- `install.sql` drops `cache_items` / `migration_versions` (`:52`, `:239`) but does not create them – document in the
  file header which tables are created elsewhere.

### [ ] I-12 – Cache / messenger tables are created with hard-coded defaults
**File:** `src/Installer.php:695-699`, `bundles/CoreBundle/config/cache.yaml:18-23`, `bundles/CoreBundle/config/pimcore/default.yaml:33-37`
- `new DoctrineDbalAdapter($db)` and `new Connection([], $db)` create `cache_items` / `messenger_messages` with the
  library defaults. If a project configures another `db_table` / `table_name` (or a non-doctrine transport), the
  installer creates the wrong table and the real one is auto-created later (or not at all with `auto_setup=false`).
- Better: after the App kernel is booted, run `messenger:setup-transports` and create the table via the configured
  `pimcore.cache.adapter.doctrine_dbal` service.

### [ ] I-13 – Hard-coded seed data drifts from core
**File:** `src/Installer.php:807-896`
- The permission list (`:853-889`) duplicates core knowledge; bundles add their own permissions via their installers,
  but core permissions added later (migrations) must be kept in sync manually. Move to a data file next to
  `install.sql` (or derive from the CoreBundle migrations/permission registry).
- `App\Controller\DefaultController::defaultAction` (`:835`) assumes the skeleton – make it configurable
  (`pimcore_install.root_document_controller`).
- `time()` is called 6× – compute once so all root elements share one timestamp.
- `Helper::quoteDataIdentifiers()` is used for 4 inserts but `quoteIdentifier('key')` manually for the 5th (`:893`) –
  be consistent.

### [ ] I-14 – Admin credential validation
**File:** `src/Installer.php:328-338`
- Minimum length 4 for the admin password is very weak for a full admin account; use ≥ 8–12 and optionally Symfony's
  `PasswordStrength` constraint (Validator is available) – at least warn.
- Validate **before** connecting to MySQL and running prerequisite checks (cheap checks first).
- Validate username charset/length (`users.name varchar(50)`, reserved `system` – B-05).
- `createOrUpdateUser()` default config with `bin2hex(random_bytes(16))` (`:745-748`) is dead – `install()` always
  passes both values (validated). `array_replace_recursive()` on a flat array → `[...$defaults, ...$config]`.

### [ ] I-15 – Kernel class resolution and debug flag
**File:** `src/Installer.php:439-450`
- `\App\Kernel::class` hard-coded; override only via `$_ENV['PIMCORE_KERNEL_CLASS']` (not `$_SERVER`, inconsistent with
  `Config::getEnvironment()` which reads `$_SERVER['APP_ENV']`). Validate that the class exists and is a
  `Pimcore\Kernel`/`KernelInterface`, otherwise return a clear error instead of `Error: Class "App\Kernel" not found`.
- Debug is always `true` (`new $kernel($environment, true)`), ignoring `--no-debug` / `APP_DEBUG` handled in
  `bin/pimcore-install:63-70`.
- The comment at `:439-441` mentions `app.php` (Symfony 3 era) – update.

### [ ] I-16 – Sub-process execution / error hints
**File:** `src/Installer.php:508-550`, `:600-635`
- The manual-fix hint mangles the command line: `str_replace(["'", '\\'], ['', '\\\\'], $process->getCommandLine())`
  strips all quotes (paths with spaces become wrong) and doubles every backslash. Print the escaped command line as is.
- On failure both `$errorOutput` (trimmed) **and** `getErrorOutput()` are written (`:540-546`, `:625-631`) → stderr is
  printed twice.
- `Console::getPhpCli()` + `bin/console`: pass `--env`/`--no-debug` of the current run explicitly so the sub-processes
  use the same environment as the booted kernel.
- `'-q'` on `doctrine:migrations:*` hides the information needed when it fails (C-02); keep output in the log instead.

### [ ] I-17 – Progress / UX
**File:** `src/Command/InstallCommand.php:375-423`
- On failure the progress bar is neither finished nor cleared (`:406-408`) – the error listing is printed under a
  half-drawn bar. Use `$progressBar->clear()` / `finish()` in both cases.
- The listener closure is added to the shared dispatcher and never removed.
- Show the resolved target (`user@host:port/dbname` or socket, bundles to install, steps to run) in the final confirm
  (relates to C-01).
- `checkPrerequisites()` runs twice (`:365` without DB, `Installer.php:317` with DB) → filesystem/PHP checks executed
  and potentially reported twice; run once with DB. The HTML `<a href>` branch in `formatPrerequisiteMessages()`
  (`Installer.php:263-265`) is dead since the web installer was removed.

### [ ] I-18 – `BundleSetupEvent` API
**File:** `src/Event/BundleSetupEvent.php`
- Constructor promotion; typed docblocks (`array<string, class-string<BundleInterface>>` for bundles,
  `list<string>` for recommendations).
- `removeInstallableBundle()` leaves holes in `$recommendations` (`unset` on a list) → `array_values()`; it does not touch
  `$required`/`$excludeFromBundlesPhp` (document or add `removeRequiredBundle()`).
- `getInstallableBundles(array $bundles)` has a misleading name (it returns *keys* of required + given bundles);
  `getExcludeBundlesFromPhpBundles()` → `getBundlesExcludedFromBundlesPhp()` (keep BC aliases).
- `getAvailableBundles()` uses `array_unique()` on class names – two keys for the same class are collapsed silently.
- Validate `class_exists($class)` / `is_subclass_of(BundleInterface::class)` in `add*Bundle()`.
- `InstallerStepEvent`: promote to `public readonly` props / keep getters; `InstallEvents`: `final`, docblock typo
  "gets fire".

### [ ] I-19 – `InstallerKernel` / installer config
**Files:** `src/InstallerKernel.php`, `config/config.yaml`
- `uniqid('installer-', true)` as `framework.secret` (`:81`) is predictable – use `bin2hex(random_bytes(16))` (only
  relevant for the cached installer container, but cheap to fix).
- Override `getShareDir()` (new `kernel.share_dir` in Symfony 7.4 – verify) to `var/installer/share` so the installer
  does not write into the application's share dir.
- Monolog handler logs at `debug` level in all environments (`config.yaml:13`) – fine for an installer, but consider
  `info` for `prod` to keep `var/installer/log` small.
- Verify the installer log for Symfony 7.3/7.4 framework-config deprecations (e.g. unset
  `framework.property_info.with_constructor_extractor` when property-info is installed) and set the options explicitly.

### [ ] I-20 – Symfony 7.4: XML config for `config/installer.*`
**File:** `src/InstallerKernel.php:84-91`, docs `README.md:151-152`
- The installer imports `config/installer.xml` and the docs advertise XML. Symfony 7.4 deprecates the XML
  configuration format (removal in 8.0) – verify, then drop `xml` from the list (or emit a deprecation) and update the
  docs to YAML/PHP only.
- The loop imports **all** existing formats (`installer.yaml` and `installer.php` both loaded) – document or stop at the
  first match.

### [ ] I-21 – `bin/pimcore-install`
**File:** `bin/pimcore-install`
- `ArgvInput` is created twice (`:51`, `:85`); `@ini_set` with error suppression; `Debug::enable()` unconditionally
  (also for `--env=prod`).
- `APP_ENV` from `--env` is applied **after** `Bootstrap::bootstrap()` (`:46-61`) – verify that nothing in bootstrap
  already resolved env-dependent values (e.g. `.env.<env>` files are loaded for the old env).
- `Pimcore::setKernel($kernel)` registers the (non-Pimcore) InstallerKernel globally – root cause of B-01; document or
  avoid.

## Tests / docs

### [ ] I-22 – Add tests
No tests exist for the InstallBundle (`tests/` has no InstallBundle suite). Minimum:
- Unit: `resolveDbConfig()` (socket/host/port/SSL, config credentials – B-03, B-07), `BundleWriter::buildContents()`
  (empty env arrays, ordering – B-14), `BundleSetupEvent` (B-11, I-18), statement splitter / comment handling (C-03,
  C-04, I-11), step filtering (B-01, I-05), `formatPrerequisiteMessages()` (B-18).
- Command: `CommandTester` for option/env resolution (B-08–B-13).
- Functional: full `pimcore:install -n` against MySQL 8 and MariaDB 10.11/11 in CI, asserting exit code and DB state
  (C-01, C-02, B-04, B-05, B-17).

### [ ] I-23 – Docs drift
**File:** `doc/01_Getting_Started/02_Advanced_Installation_Topics/README.md`
- "Default Recommended Bundles" (`:56-67`) lists `PimcoreQuillBundle` (not in `INSTALLABLE_BUNDLES`) and misses
  `PimcoreGenericExecutionEngineBundle`; since `2e079263bd`/`b589f240a1` **all** installable bundles are recommended –
  update the list (and `Installer::RECOMMENDED_BUNDLES` could simply be `array_keys(self::INSTALLABLE_BUNDLES)`).
- The env-var section (`:29-37`) is only true for real process env vars (B-08) and not for `install-bundles` (B-09).
- Required-bundle semantics (`:50-52`, `:88`) – update after B-13.
- `%env()%` link points to the Symfony 3.2 blog post (`:165`).
