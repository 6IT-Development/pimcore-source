# CoreBundle – TODO 2: Bugs

Scope: **(1)** `bundles/CoreBundle/src/Command/**`, `bundles/CoreBundle/config/commands.yaml` (+ `config/extensions.yaml:33`)
**(2)** `src/DataCollector/**`, `src/PimcoreCoreBundle.php` and related: the compiler passes it registers
(`src/DependencyInjection/Compiler/*`), `src/DependencyInjection/PimcoreCoreExtension.php`, `config/profiler.yaml`,
`config/routing_dev.yaml`, `config/services_symfony_workflow.yaml`, `config/cache.yaml`, `templates/Profiler/*`
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

Functional defects: wrong results, crashes on reachable input, swallowed errors, misleading output.
IDs are stable – refer to them as `B-xx` in later sessions. Line numbers are from commit `7cb7841b76`.
Items are roughly ordered by impact, highest first.

---

## [ ] B-01 – `ClassesRebuildCommand` swallows object brick save errors and returns 0 (deployment)

**File:** `src/Command/ClassesRebuildCommand.php:137-178`

- Exceptions from `$brickDefinition->save(false)` are caught and dumped with `$output->write((string)$e)`: a full
  trace, no newline, not marked as an error. The command still returns `0`, so a deployment pipeline continues with
  a broken DB schema.
- Field collection (`:162`) and select-option (`:175`) saves are **not** guarded, so the error handling is
  inconsistent. One failing field collection aborts the rest of the run.
- `'%s saved'` is printed **before** the save happens (`:141`, `:159`, `:172`).

**Fix direction:** collect failures and print them with `$this->io->error()`. Continue with the other definitions,
then return `Command::FAILURE` if anything failed. Print "saved" after the save succeeds.

---

## [ ] B-02 – OFFSET batching without `ORDER BY` (assets skipped or processed twice)

**Files:** `Asset/AddToUpdateTaskQueueCommand.php:105-119`, `Asset/GenerateChecksumCommand.php:94-121`,
`LowQualityImagePreviewCommand.php:103-126` (and `RemoveCustomSettingCommand`, see C-02)

- `Asset\Listing` with `setLimit()`/`setOffset()` and no order key gives no stable order in MySQL. Pages can overlap
  or leave gaps, especially when the condition uses a secondary index (`path LIKE`, `type IN`). Assets created or
  deleted during the run shift the pages as well.

**Fix direction:** `setOrderKey('id')` plus keyset pagination (`id > :lastId`). Put it in the shared filter/batching
helper (I-11).

---

## [ ] B-03 – `--id` option values concatenated into SQL unvalidated

**Files:** `Asset/AddToUpdateTaskQueueCommand.php:90`, `Asset/GenerateChecksumCommand.php:86`,
`Asset/RemoveCustomSettingCommand.php:78`, `LowQualityImagePreviewCommand.php:93`, `ThumbnailsImageCommand.php:153`

```php
$conditions[] = sprintf('id in (%s)', implode(',', $ids));
```

- Any non-numeric value (`--id=abc`, `--id="1 2"`) causes a SQL error. Arbitrary SQL can be injected from the
  command line or from scripts that build the command line from other input.
- The help text says "comma-separated". That only works by accident because the raw string goes into the SQL.

**Fix direction:** split on `,`, cast with `(int)`/`filter_var(FILTER_VALIDATE_INT)`, reject invalid values with
`Command::INVALID`, and bind as `ArrayParameterType::INTEGER` (or `implode` of ints).

---

## [ ] B-04 – `RemoveCustomSettingCommand`: argument mode uses an `InputOption` constant

**File:** `src/Command/Asset/RemoveCustomSettingCommand.php:41`

```php
->addArgument('name', InputOption::VALUE_REQUIRED, 'Name of the custom setting to remove')
```

- `InputOption::VALUE_REQUIRED === 2 === InputArgument::OPTIONAL`, so the "required" argument is **optional**.
- Without it the condition becomes `customSettings LIKE '%""%'`, which matches nearly every asset with custom settings.
  The first `removeCustomSetting(null)` then throws a `TypeError` (`string $key`).
- The setting name goes into the `LIKE` pattern without `escapeLike()` (`_` and `%` act as wildcards). The pattern
  also matches assets where the name appears as a **value**. That only costs time because `removeCustomSetting()` is
  a no-op there.
- `$asset->getFullPath()` is printed instead of `getRealFullPath()`.

**Fix direction:** `InputArgument::REQUIRED`, escape the LIKE pattern, and/or filter on `JSON_CONTAINS_PATH` when the
column is JSON.

---

## [ ] B-05 – `pimcore:bundle:install` / `uninstall` hide installer output and turn real failures into exit 0

**Files:** `src/Command/Bundle/AbstractBundleCommand.php:73-84,113-121`, `Bundle/InstallCommand.php:59-68`,
`Bundle/UninstallCommand.php:52-61`

- The comment says "sets up installer with console output writer", but `setupInstaller()` only fetches the installer
  and its return value is discarded. `InstallerInterface::getOutput()` (a `BufferedOutput`) is never printed, so
  installer messages and migration output are lost.
- **Any** `Exception` from `install()`/`uninstall()` goes to `handlePrerequisiteError()`. With
  `--fail-without-error` a broken migration or SQL error becomes a warning and exit code `0`. The stack trace is
  dropped even with `-vvv`.
- `UninstallCommand` does not check `isInstalled()` first, unlike `InstallCommand`. It relies on the exception message.

**Fix direction:** after install/uninstall, print `$installer->getOutput()->fetch()`. Only apply
`--fail-without-error` to "already (un)installed / cannot be (un)installed" states (`canBeInstalled()` /
`canBeUninstalled()` checked before running). Rethrow or report other exceptions with `FAILURE`.

---

## [ ] B-06 – `PostStateChange` swallows failed `assets:install` / `cache:clear`

**File:** `src/Command/Bundle/Helper/PostStateChange.php:91-118`

- `ProcessFailedException` is caught with `// noop - output should be enough`. The install/uninstall command still
  prints success and returns `0` even when the cache could not be cleared, which leaves the container stale after a
  bundle state change.

**Fix direction:** return a bool/result from `runPostStateChangeCommands()` and let the caller return `FAILURE`, or at
least print `$io->error()` with the exit code.

---

## [ ] B-07 – `pimcore:bundle:list`: wrong headers/legend, multibyte truncation, possible crash

**File:** `src/Command/Bundle/ListCommand.php:60-61,78,85,121-126`

- `$input->hasOption('json')` is **always true** because the option is defined. The table therefore always shows
  `Installable`/`Uninstallable` while the legend explains `I?`/`UI?`. It should be `getOption('json')`.
- The legend lists `UP?: Can be updated?`, a column that does not exist.
- `substr($bundle->getDescription(), 0, 30)` can cut a UTF-8 character in half. `--json` then fails
  (`json_encode` returns `false` on invalid UTF-8 and `$output->write(false)` prints nothing).
- `getManuallyRegisteredBundleState()` throws `InvalidArgumentException` (not `BundleNotFoundException`) for an
  active bundle that is not in the manually registered list. That aborts the whole listing. Needs verification for
  bundles registered via `Kernel::registerBundles()`.

**Fix direction:** `getOption('json')`, `mb_strimwidth($desc, 0, 33, '...')`, `JSON_THROW_ON_ERROR`, catch
`InvalidArgumentException` and fall back to priority `0`/`n/a`, fix the legend.

---

## [ ] B-08 – Definition import: `createDefinition()` returning `null` causes a `TypeError`

**Files:** `src/Command/Definition/Import/AbstractStructureImportCommand.php:85-102`, `CustomLayoutCommand.php:80-96`

- `CustomLayoutCommand::createDefinition()` returns `null` when `--class-name` is missing or unknown. The abstract
  command then calls `$this->import($definition, $json)` with `null`, which throws
  `TypeError: import(): Argument #1 must be of type ModelInterface, null given` instead of a clear error.

**Fix direction:** check for `null` after `createDefinition()`, print a clear error ("--class-name is required to
create a new custom layout"), and return `Command::INVALID`. Validate `--class-name` up front in `CustomLayoutCommand`.

---

## [ ] B-09 – Definition import: non-interactive run without `--force` silently skips and returns 0

**File:** `src/Command/Definition/Import/AbstractStructureImportCommand.php:77-84,149-163`

- If the definition exists and `--force` is not given, `askConfirmation()` is called. With `-n` (CI/deploy) the
  `ConfirmationQuestion` returns its default `false`. The command then returns `0` without printing anything, so the
  import silently did not happen.

**Fix direction:** in non-interactive mode without `--force`, print an error ("already exists, use --force") and return
`FAILURE`. Use `$this->io->confirm()`.

---

## [ ] B-10 – `CustomLayoutCommand`: lookup ignores the class, errors are only logged

**File:** `src/Command/Definition/Import/CustomLayoutCommand.php:75-78,98-122`

- `CustomLayout::getByName($name)` finds a layout by name across **all** classes. If two classes have a layout with
  the same name, the import may overwrite the layout of the other class even when `--class-name` is given.
  `CustomLayout::getByNameAndClassId()` exists (`models/DataObject/ClassDefinition/CustomLayout.php:111`).
- `json_decode($json, true)` without validation: a missing `layoutDefinitions`/`description` key causes a warning and
  a `TypeError`. The exception is only sent to `Logger::error()`. The console just prints "Failed to import" with no
  reason.
- The signature `?string $json = null` differs from the abstract `string $json`.

**Fix direction:** use `getByNameAndClassId()` when `--class-name` is given. Validate keys and print the exception
message to the console.

---

## [ ] B-11 – `ClassesDefinitionsBuildCommand`: includes every `*.php` file and does not restore the cache state on errors

**File:** `src/Command/ClassesDefinitionsBuildCommand.php:55-104`

- `glob($dir . '/*.php')` picks up **any** PHP file in the definition directories. The description says
  `definition_*.php`. A stray file that does not return a `ClassDefinition` causes a `TypeError` in
  `dumpPHPClasses()`. `glob()` returning `false` is not handled.
- `Cache::disable()` is not reverted in a `finally`. After an exception the cache stays disabled for the rest of the
  process, which matters when the command is called via `Application::run()` from other code or tests.

**Fix direction:** `glob($dir . '/definition_*.php') ?: []`, an `instanceof ClassDefinitionInterface` guard with a
warning, and `try/finally` for the cache state.

---

## [ ] B-12 – `DeleteUnusedLocaleDataCommand`: `--skip-locales` is not trimmed and only partly applied

**File:** `src/Command/DeleteUnusedLocaleDataCommand.php:57-59,80,98,117`

- The help text example is `en, en_AT`, but the values are not trimmed, so `" en_AT"` never matches and **en_AT data is
  deleted**.
- The skip list is only used for the `DELETE` of localized data. The `DROP VIEW object_localized_*` and
  `DROP TABLE object_localized_query_*` loops ignore it, so the query tables/views of "skipped" locales are dropped
  anyway.
- If `Tool::getValidLanguages()` returns an empty list (misconfigured settings), the data query becomes `NOT IN()`.
  That throws only by luck. Without the throw, the following loops would drop **all** localized query tables and
  views.
- Destructive (DROP TABLE) and unconfirmed. Only `--dry-run` protects.

**Fix direction:** `array_map('trim', ...)` + `array_filter`, apply the skip list in all three loops, abort when the
valid language list is empty, ask for confirmation (see I-16).

---

## [ ] B-13 – `GeneratePagePreviews`: parent filter matches sibling trees, errors give exit 0

**File:** `src/Command/Document/GeneratePagePreviews.php:66-93,111-122`

- `'path' LIKE <parentPath>%` without a trailing `/`: `--parent=/en` also matches `/en-gb/...` and `/enterprise/...`.
  `_`/`%` in the path are not escaped.
- `$conditions` is used without being initialised (only works because of `isset()`).
- Errors per document are printed but the command returns `0`.
- The whole page list is loaded into memory, with no batching and no `collectGarbage()`.

**Fix direction:** `path LIKE ?` with `escapeLike($realFullPath) . '/%'` (plus `OR id = parentId` if the parent itself
should be included). Initialise `$conditions = []`, batch, and return `FAILURE` when any preview failed.

---

## [ ] B-14 – `GenerateStaticPagesCommand`: `null` page crashes, LIKE wildcards not escaped

**File:** `src/Command/GenerateStaticPagesCommand.php:70-101`

- The query uses `LEFT JOIN documents`, and `Document\Page::getById($id)` can return `null` (orphaned `documents_page`
  row, or a type mismatch). `$page->getStaticGeneratorEnabled()` then throws a fatal `Error`.
- `$parent->getFullPath() . '/%'` is not escaped with `escapeLike()`. It also uses `getFullPath()` instead of
  `getRealFullPath()`, which differs for hardlinked/site-rooted paths.
- One exception from `generate()` aborts the whole run (no per-page error handling, no summary).

**Fix direction:** `INNER JOIN`, a null-check with a warning, `escapeLike(getRealFullPath())`, a per-page try/catch
with failure count, and return `FAILURE` if any page failed.

---

## [ ] B-15 – `ThumbnailsVideoCommand::waitTillFinished()` can hang forever

**File:** `src/Command/ThumbnailsVideoCommand.php:140-187`

- The loop exits only on `finished`, `null` or an unknown status. If the status stays `inprogress` (no messenger
  worker running, ffmpeg killed, stale status in the asset cache), the command **never terminates**. That blocks cron
  and keeps the Parallelization lock for up to 24h.
- Every item pays an unconditional `sleep(20)` when the thumbnail is not finished yet. That is very slow for large
  libraries.
- `Asset\Video::getById()` inside the loop (`:162`) can return `null` (asset deleted meanwhile), causing a fatal
  `Error`. `Config::getByName()` (`:122`) can return `null`, which passes `null` to `getThumbnail()` → `TypeError`.
- `Version::disable()` is called for every item and never re-enabled. Harmless in a separate process, but it is a
  global side effect.

**Fix direction:** maximum wait time (option, default e.g. 30 min) with a warning on timeout, exponential backoff
instead of a fixed `sleep(20)`, null checks, and `Version::disable()` once in `runBeforeFirstCommand()`.

---

## [ ] B-16 – `ThumbnailsImageCommand`: invalid `--last-modified-since` and missing configs cause fatal errors

**File:** `src/Command/ThumbnailsImageCommand.php:141-145,134,226-229`

- `DateTime::createFromFormat()` returns `false` for any input not in exactly `Y-m-d H:i:s`. `->getTimestamp()` on
  `false` throws a fatal `Error`.
- The invalid parent error prints the whole `--parent` string instead of the failing ID.
- `Image\Thumbnail\Config::getByName()` can return `null` (config deleted or renamed between `fetchItems()` and the
  child process). `$thumbnailConfig->getMedias()` on `null` throws a fatal `Error`. The `@var` annotation hides this
  from PHPStan.
- A `--thumbnails` filter naming a non-existing config yields "0 items" with no hint.

**Fix direction:** validate the date (`createFromFormat(...) ?: throw new InvalidArgumentException(...)`), report the
specific invalid ID, null-check the config, and warn about unknown thumbnail names.

---

## [ ] B-17 – `Migrate/StorageCommand`: stream handle leak, errors still give exit 0

**File:** `src/Command/Migrate/StorageCommand.php:79-112`

- `readStream()` is opened **before** the `fileExists()` check and is never closed, neither on skip nor after
  `writeStream()`. On large storages this leads to `Too many open files` and may keep remote connections (S3) open.
- With per-file errors the command prints "Finished Migrating Storage!" (success box) and returns `0`.
- With no `storage` arguments (the argument is optional `IS_ARRAY`) it does nothing and still prints success.
- `catch (Exception)` around the locator lookup also hides real configuration errors. Catch
  `NotFoundExceptionInterface` instead.

**Fix direction:** check `fileExists()` first, then `readStream()` → `writeStream()` → `fclose()` in `finally`. Make
the argument `REQUIRED | IS_ARRAY` (or default to all `pimcore.*.storage.source` pairs), and return `FAILURE` when
`$errors` is not empty.

---

## [ ] B-18 – `EmailLogsCleanupCommand` / `RecyclebinCleanupCommand`: unsafe day parsing, unusable shortcut

**Files:** `src/Command/EmailLogsCleanupCommand.php:42-71`, `src/Command/RecyclebinCleanupCommand.php:41-77`

- `EmailLogsCleanupCommand` uses the multi-character shortcut `'days'`. Short options are single characters, so
  `-days` is parsed as `-d -a -y -s` and the shortcut can never work.
- `is_numeric()` accepts `-5`, `1.5`, `1e3`. The string is fed into `new DateTime("-$daysAgo days")`. Negative values
  produce `--5 days`, which may resolve to a **future** date, and then **all** email logs / recycle bin items are
  deleted.
- Both load all matching rows into memory and delete them one by one, which runs out of memory on big tables.
- Missing option → generic `Exception` instead of a proper `INVALID` exit.

**Fix direction:** `filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])`, shortcut `d` or none,
batched deletion (see I-12).

---

## [ ] B-19 – `ResetPasswordCommand`: crashes in non-interactive mode, password leaks to shell history

**File:** `src/Command/ResetPasswordCommand.php:73-105`

- With `-n` and no `--password`, `QuestionHelper::ask()` returns the default `null` (validators do not run in
  non-interactive mode). `Authentication::getPasswordHash(string, string)` then throws a `TypeError`.
- The validator calls `trim($value)` where `$value` can be `null` (empty hidden input), which triggers a PHP 8.1+
  deprecation.
- `--password=...` ends up in shell history and in `ps` output. The help only says "(recommended)" for the prompt.
- There is no confirmation (repeat) prompt and no minimum length or password policy. `User::setPassword()` silently
  ignores values ≤ 4 characters, but here it always gets a hash.
- An unknown user ends with `exit;` (see C-06).

**Fix direction:** in non-interactive mode without `--password`, error and return `INVALID`. Use
`$this->io->askHidden()` twice and compare. Print a warning when `--password` is used. Apply the same password rules
as the admin UI.

---

## [ ] B-20 – `RunScriptCommand`: output buffer not cleaned up on exceptions

**File:** `src/Command/RunScriptCommand.php:66-73`

- `ob_start()` → `include` → `ob_end_clean()` has no `try/finally`. If the script throws, the buffer stays open. The
  script's output is lost or flushed in the wrong place, and the error output gets mixed into it.
- The script's return value is ignored, so a script cannot report failure through the exit code.

**Fix direction:** `try { include $script; } finally { $scriptOutput = ob_get_clean(); }`. If the script returns an
int, use it as the exit code.

---

## [ ] B-21 – `MysqlToolsCommand`: unknown mode is a silent no-op, identifiers unquoted, views included

**File:** `src/Command/MysqlToolsCommand.php:50-90`

- `--mode=foo` does nothing and returns `0`. A missing `--mode` uses `exit;` (C-06).
- Table names are concatenated unquoted (`OPTIMIZE TABLE $t`, `SELECT COUNT(*) FROM $t`). They break on reserved
  words or special characters.
- `SHOW TABLES` also returns **views**. `OPTIMIZE` on a view errors (only logged). `COUNT(*)` on Pimcore's object views
  can be very expensive, which defeats the "warmup" purpose.
- All results and errors go only to the logger. The console shows nothing.

**Fix direction:** validate mode (`suggestedValues: ['optimize', 'warmup']`, return `INVALID`). Use
`SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'` and `$db->quoteIdentifier()` (DBAL 3; in DBAL 4.3+ use `quoteSingleIdentifier()`). Show a progress bar and an
error summary.

---

## [ ] B-22 – `LowQualityImagePreviewCommand`: deprecation always triggered, output corrupts the progress bar

**File:** `src/Command/LowQualityImagePreviewCommand.php:73-75,107-128`

- `$input->hasOption('generator')` is always `true` (the option is defined), so the "generator option is deprecated"
  deprecation fires on **every** run. The option value is never used.
- `writeln()` for each image while a `ProgressBar` is active garbles the terminal output.
- The progress bar is never followed by a newline after `finish()`.

**Fix direction:** `null !== $input->getOption('generator')` (or remove the option, see I-18). Use
`$this->io->progressIterate()` and print per-item messages only with `-v` via `$progressBar->clear()/display()`.

---

## [ ] B-23 – `MigrateElementsCommand`: `null` version crashes, one broken version aborts the run

**File:** `src/Command/Document/MigrateElementsCommand.php:44-63`

- `Version::getById($vId)` returns `?Version`. `->loadData()` on `null` throws a fatal `Error`.
- A version with corrupt or missing serialized data throws and aborts the whole migration with no summary.
- `fetchAllAssociative()` loads all version rows at once.

**Fix direction:** null-check, per-row try/catch with failure count, `iterateAssociative()`, and `collectGarbage()`
every n rows.

---

## [ ] B-24 – `RequirementsCheckCommand` always returns 0

**File:** `src/Command/RequirementsCheckCommand.php:47-63`

- Even when checks report `STATE_ERROR` the exit code is `0`, so the command cannot gate CI or a deploy.
- An unknown `--min-level` value silently falls back to "show all".

**Fix direction:** return `FAILURE` if any check is `STATE_ERROR` (maybe `--strict` to also fail on warnings). Validate
`--min-level` with `suggestedValues`.

---

## [ ] B-25 – `CacheClearCommand`: `--all` option does nothing, tag `"0"` dropped

**File:** `src/Command/CacheClearCommand.php:60-65,107`

- `--all` / `-a` is declared but never read. "Clear all" is simply the default branch, so the option is misleading.
  `--tags` and `--output` together silently ignore `--output`.
- `!empty($tagEntry)` drops a legitimate tag `"0"`.

**Fix direction:** remove `--all` or make it explicit (and reject conflicting options). Use `'' !== $tagEntry`.

---

## [ ] B-26 – `ThumbnailsImageCommand` / `ThumbnailsVideoCommand`: `exit(1)` inside `fetchItems()` after the lock is taken

**Files:** `src/Command/ThumbnailsImageCommand.php:135`, `src/Command/ThumbnailsVideoCommand.php:80`,
related `lib/Console/Traits/Parallelization.php:37-43`

- Without `webmozarts/console-parallelization`, `ParallelizationBase::execute()` calls `runBeforeFirstCommand()`
  (which acquires the lock) **before** `fetchItems()`. The `exit(1)` then skips `runAfterLastCommand()`. The lock is
  only released through `Lock::__destruct()` during shutdown, which is not guaranteed for every store.
- `Parallelization::runBeforeFirstCommand()` itself uses `exit(1)` when the lock is held (outside scope, same pattern).

**Fix direction:** throw instead of `exit` (C-06) and release the lock in a `finally`/`ConsoleEvents::TERMINATE`
listener.

---

## [ ] B-27 – Internal generator commands crash on missing file or network error

**Files:** `src/Command/InternalModelDaoMappingGeneratorCommand.php:69-70`,
`src/Command/InternalUnicodeCldrLanguageTerritoryGeneratorCommand.php:44-46`

- `realpath(__DIR__ . '/../../../../config/dao-classmap.php')` returns `false` if the file does not exist yet.
  `File::putPhpFile(false, ...)` then throws a `TypeError`.
- `file_get_contents()` from GitHub and `simplexml_load_string()` have no error handling. A network failure gives a
  fatal error on `$xml->territoryInfo`. The URL points at the `master` branch of `unicode-org/cldr`, whose default
  branch is `main`. Verify the URL still resolves, and better pin a release tag.

**Fix direction:** build paths from `PIMCORE_PATH`, check the download/parse results, use `HttpClient` with a timeout,
and pin the CLDR tag.

---

# Scope 2 – `DataCollector` / `PimcoreCoreBundle` (+ compiler passes, extension, related config)

Same rules: IDs continue from B-27 and are stable. Line numbers are from commit `7cb7841b76`. Items are ordered by
impact. For the implicit pass ordering behind B-29 / B-31 see the note at the top of Scope 2 in TODO-1, and I-35.

---

## [ ] B-28 – `WorkflowPass`: a transition's `customHtml` service is applied to all earlier transitions

**File:** `src/DependencyInjection/Compiler/WorkflowPass.php:73-106`

- `$transitions = []` is initialised **once per workflow** (line 73). The `customHtml` block (lines 96-105) then loops
  over the whole `$transitions` array, not only the transition(s) built for the current `$transitionName`.
- With transitions `A` (customHtml X) and `B` (customHtml Y), `A` gets `setCustomHtmlService(X)` and then
  `setCustomHtmlService(Y)`. The last call wins, so **A shows B's custom HTML**. The service is also constructed with
  `B`'s transition name.
- A transition without its own `customHtml` gets the service of the next transition that has one.
- For `state_machine` the same happens across all from×to expansions of earlier transitions.

**Fix direction:** collect the definitions of the current transition in a local `$current = []`, apply customHtml
to `$current` only, then `array_push($transitions, ...$current)`.

---

## [ ] B-29 – `WorkflowPass` runs after Monolog's and Symfony's workflow passes: audit trail on wrong channel, guard check skipped

**Files:** `src/DependencyInjection/Compiler/WorkflowPass.php:222-281`, `src/PimcoreCoreBundle.php:60`

- `MonologBundle` (bundle priority 100 in `lib/Kernel.php:234-248`) registers `LoggerChannelPass` at the same type and
  priority. It runs **before** `WorkflowPass` (`PimcoreCoreBundle` has priority -10).
  - The `monolog.logger` tag with `channel: workflow`, which `WorkflowPass` adds to `AuditTrailListener` (line 226), is
    never processed. The listener keeps the plain `@logger`, so **audit trail entries go to the `app` channel**.
  - A `monolog.logger.workflow` channel is not created. Handlers configured with `channels: [workflow]` or
    `["!workflow"]` do not work as expected.
- FrameworkBundle's `WorkflowGuardListenerPass` also runs before `WorkflowPass`. The
  `workflow.has_guard_listeners` parameter set at line 280 is never read.
  - The check that the security services exist is skipped. A guard without SecurityBundle security services fails
    later with a generic "service not found" error.
  - The temporary parameter stays in the compiled container.

**Fix direction:** register `WorkflowPass` with a priority above 0 (it only needs extension-loaded definitions).
Document the reason (see I-35). Optionally assert in a test that the listener definition gets
`monolog.logger.workflow`.

---

## [ ] B-30 – `AreabrickPass`: the same brick is registered twice when it has both a YAML tag and `#[AsAreabrick]`

**Files:** `src/DependencyInjection/Compiler/AreabrickPass.php:62-83`,
`src/DependencyInjection/PimcoreCoreExtension.php:142-147`, `lib/Extension/Document/Areabrick/AreabrickManager.php:68-85`

- Autoconfiguration adds a second `pimcore.area.brick` tag (`['id' => <attr id or null>]`) to a service that already
  has an explicit `{ name: pimcore.area.brick }` tag. The dedupe in `ResolveInstanceofConditionalsPass` only compares
  identical attribute arrays.
- The pass loops over **every** tag, so `registerService($brickId, $id)` is added twice:
  - The same ID (tag without `id`, both resolve via the attribute/class name):
    `AreabrickManager::registerService()` throws `ConfigurationException` ("already registered") when the manager is
    first instantiated. **Every editmode and areablock render fails.** This is a natural state during a migration from
    YAML tags to the attribute.
  - Different IDs (YAML `id: foo`, attribute `id: bar`): the brick appears twice in the editor.

**Fix direction:** resolve the brick IDs per service, `array_unique()` them, and throw a clear compile-time
`InvalidConfigurationException` when a tag ID and an attribute ID differ. Also detect duplicate IDs across services at
compile time instead of at runtime.

---

## [ ] B-31 – `AreabrickPass`: autoloaded bricks are not autoconfigured; tagged services with no/param class crash

**File:** `src/DependencyInjection/Compiler/AreabrickPass.php:62-66`, `105-150`

- Autoloaded bricks get `new Definition(...)->setAutoconfigured(true)` (lines 126-130). The pass runs at priority 0,
  after `RegisterAutoconfigureAttributesPass`, `AttributeAutoconfigurationPass` and `ResolveInstanceofConditionalsPass`
  (priority 100), so the flag **does nothing**:
  - `_instanceof` rules, `ResetInterface` → `kernel.reset`, `EventSubscriberInterface`, `ServiceSubscriberInterface`,
    `#[AsEventListener]`, `#[AutoconfigureTag]` … are not applied to autoloaded bricks.
  - `_defaults.bind` of the app config is not applied either. Scalar constructor arguments fail to autowire, and the
    error does not explain why.
- Line 64-65: `new ReflectionClass($definition->getClass())`:
  - a ChildDefinition or factory service without a class gives `null`, which fails with `ReflectionException`
    (`Class "" does not exist`);
  - a class given as a parameter (`%foo.class%`) is not resolved.
- Raw `ReflectionClass` adds no container resources. Use `$container->getReflectionClass()`.

**Fix direction:** split the pass. Register the autoloaded definitions in a pass with priority > 100 (or document that
autoloaded bricks are only autowired, not autoconfigured). Resolve the class with
`$container->getParameterBag()->resolveValue()` + `$container->getReflectionClass()` and fail with a clear message.

---

## [ ] B-32 – `CacheFallbackPass` overwrites a user-defined `pimcore.cache.pool` **alias**

**File:** `src/DependencyInjection/Compiler/CacheFallbackPass.php:32-35`

- `hasDefinition('pimcore.cache.pool')` is `false` for an alias, e.g. `services: { pimcore.cache.pool: '@my.redis.pool' }`
  or `alias:`. The pass then silently replaces the user's alias with the DBAL tag-aware adapter.
- The configured cache backend (Redis, …) is ignored without any error.

**Fix direction:** `if (!$container->has('pimcore.cache.pool'))`. Do it together with C-07.

---

## [ ] B-33 – `ImageAdapterAliasPass`: a custom adapter configured as an alias stays a shared service

**Files:** `src/DependencyInjection/Compiler/ImageAdapterAliasPass.php:35-39`, `lib/Image.php:32`

- The definition branch sets `setShared(false)`. The alias branch only sets `setPublic(true)`, and the alias target is
  shared by default.
- `Image::getInstance()` then returns **the same stateful adapter** on every call. The loaded image resource and the
  applied transformations leak between thumbnail jobs. Nested image work, e.g. a watermark loading a second image,
  corrupts the first.
- The default aliases to `Imagick`/`GD` are fine because those services are `shared: false` (`config/services.yaml:138-148`).

**Fix direction:** resolve the alias (`$container->findDefinition()`) and set `setShared(false)` on the target, or
throw if the target is shared.

---

## [ ] B-34 – `PimcoreDataCollector`: `TypeError`/warnings from non-nullable version getters and missing keys

**Files:** `src/DataCollector/PimcoreDataCollector.php:39-77`, `lib/Version.php:39-47`,
`templates/Profiler/data_collector.html.twig:24,64`

- `Version::getVersion(): string` / `getRevision(): string` return `InstalledVersions::getPrettyVersion()` /
  `getReference()`. Both are `?string`. `getReference()` is `null` e.g. for a path/artifact install without a
  reference, and `getPrettyVersion()` is `null` for a replaced/provided package. Both **throw `OutOfBoundsException`**
  if `six-it/pimcore-source` is not installed under that name.
  `getPlatformVersion()` already catches this; the other two do not.
- `collect()` runs on every profiled request. One `TypeError` there makes **every dev page return a 500** through the
  `kernel.response` profiler listener.
- The getters read `$this->data['…']` without `??`. After `reset()`, or for a stored profile created before a field
  existed, you get an "Undefined array key" warning plus a `TypeError` for the non-nullable `getVersion()` /
  `getRevision()`.
- The template prints `build {{ collector.revision }}` without a null fallback.

**Fix direction:** make the `Version` getters nullable or give them fallbacks and catch `OutOfBoundsException`. Use
`$this->data[...] ?? null` with nullable return types in the collector. Add `?: '~'` in the template.
