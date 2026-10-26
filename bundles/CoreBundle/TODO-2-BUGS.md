# CoreBundle – TODO 2: Bugs

Scope: **(1)** `bundles/CoreBundle/src/Command/**`, `bundles/CoreBundle/config/commands.yaml` (+ `config/extensions.yaml:33`)
**(2)** `src/DataCollector/**`, `src/PimcoreCoreBundle.php` and related: the compiler passes it registers
(`src/DependencyInjection/Compiler/*`), `src/DependencyInjection/PimcoreCoreExtension.php`, `config/profiler.yaml`,
`config/routing_dev.yaml`, `config/services_symfony_workflow.yaml`, `config/cache.yaml`, `templates/Profiler/*`
**(3)** `src/EventListener/**` (incl. `Traits/`) and related: `config/event_listeners.yaml`,
`PimcoreCoreExtension.php:230-240` (TranslationDebugListener wiring), and the helpers the listeners depend on
(`lib/Http/ResponseStack.php`, `lib/Document/Renderer/DocumentRenderer.php`, `lib/Tool/MaintenanceModeHelper.php`, `models/Site.php`)
**(4)** `src/DependencyInjection/**` (`Configuration`, `ConfigurationHelper`, `Config/Processor/PlaceholderProcessor`,
`PimcoreCoreExtension`, re-scan of `Compiler/*`) and related config: `config/pimcore/*.yaml` and every `config/*.yaml`
loaded by the extension, plus the direct consumers of the helper (`lib/Kernel.php:87-131`,
`lib/Config/LocationAwareConfigRepository.php:289-329`)
**(5)** `src/Controller/**`, `src/OptionsProvider/**`, `src/Request/**` and related config: `config/routing.yaml`,
`config/services.yaml:58-62,116-118`, plus the model code they drive (`models/Asset/WebDAV/*`,
`models/Asset/Service.php` thumbnail delivery, `SelectionProviderTrait`, `AbstractObject::getById()`)
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

Functional defects: wrong results, crashes on reachable input, swallowed errors, misleading output.
IDs are stable – refer to them as `B-xx` in later sessions. Line numbers are from commit `7cb7841b76`.
Items are roughly ordered by impact, highest first.

---

## [x] B-01 – `ClassesRebuildCommand` swallows object brick save errors and returns 0 (deployment)

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

## [x] B-02 – OFFSET batching without `ORDER BY` (assets skipped or processed twice)

**Files:** `Asset/AddToUpdateTaskQueueCommand.php:105-119`, `Asset/GenerateChecksumCommand.php:94-121`,
`LowQualityImagePreviewCommand.php:103-126` (and `RemoveCustomSettingCommand`, see C-02)

- `Asset\Listing` with `setLimit()`/`setOffset()` and no order key gives no stable order in MySQL. Pages can overlap
  or leave gaps, especially when the condition uses a secondary index (`path LIKE`, `type IN`). Assets created or
  deleted during the run shift the pages as well.

**Fix direction:** `setOrderKey('id')` plus keyset pagination (`id > :lastId`). Put it in the shared filter/batching
helper (I-11).

---

## [x] B-03 – `--id` option values concatenated into SQL unvalidated

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

## [x] B-04 – `RemoveCustomSettingCommand`: argument mode uses an `InputOption` constant

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

## [x] B-05 – `pimcore:bundle:install` / `uninstall` hide installer output and turn real failures into exit 0

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

## [x] B-06 – `PostStateChange` swallows failed `assets:install` / `cache:clear`

**File:** `src/Command/Bundle/Helper/PostStateChange.php:91-118`

- `ProcessFailedException` is caught with `// noop - output should be enough`. The install/uninstall command still
  prints success and returns `0` even when the cache could not be cleared, which leaves the container stale after a
  bundle state change.

**Fix direction:** return a bool/result from `runPostStateChangeCommands()` and let the caller return `FAILURE`, or at
least print `$io->error()` with the exit code.

---

## [x] B-07 – `pimcore:bundle:list`: wrong headers/legend, multibyte truncation, possible crash

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

## [x] B-08 – Definition import: `createDefinition()` returning `null` causes a `TypeError`

**Files:** `src/Command/Definition/Import/AbstractStructureImportCommand.php:85-102`, `CustomLayoutCommand.php:80-96`

- `CustomLayoutCommand::createDefinition()` returns `null` when `--class-name` is missing or unknown. The abstract
  command then calls `$this->import($definition, $json)` with `null`, which throws
  `TypeError: import(): Argument #1 must be of type ModelInterface, null given` instead of a clear error.

**Fix direction:** check for `null` after `createDefinition()`, print a clear error ("--class-name is required to
create a new custom layout"), and return `Command::INVALID`. Validate `--class-name` up front in `CustomLayoutCommand`.

---

## [x] B-09 – Definition import: non-interactive run without `--force` silently skips and returns 0

**File:** `src/Command/Definition/Import/AbstractStructureImportCommand.php:77-84,149-163`

- If the definition exists and `--force` is not given, `askConfirmation()` is called. With `-n` (CI/deploy) the
  `ConfirmationQuestion` returns its default `false`. The command then returns `0` without printing anything, so the
  import silently did not happen.

**Fix direction:** in non-interactive mode without `--force`, print an error ("already exists, use --force") and return
`FAILURE`. Use `$this->io->confirm()`.

---

## [x] B-10 – `CustomLayoutCommand`: lookup ignores the class, errors are only logged

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

## [x] B-28 – `WorkflowPass`: a transition's `customHtml` service is applied to all earlier transitions

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

## [x] B-29 – `WorkflowPass` runs after Monolog's and Symfony's workflow passes: audit trail on wrong channel, guard check skipped

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

## [x] B-30 – `AreabrickPass`: the same brick is registered twice when it has both a YAML tag and `#[AsAreabrick]`

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

## [x] B-31 – `AreabrickPass`: autoloaded bricks are not autoconfigured; tagged services with no/param class crash

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

## [x] B-32 – `CacheFallbackPass` overwrites a user-defined `pimcore.cache.pool` **alias**

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

---

# Scope 3 – `EventListener` (+ `config/event_listeners.yaml`)

Same rules: IDs continue from B-34 and are stable. Line numbers are from commit `43948f45d7` (code identical to
`7cb7841b76`). Items are ordered by impact. For the long-running-runtime state leaks see C-08. For the REQUEST
priority table see the note at the top of Scope 3 in TODO-1.

---

## [x] B-35 – `ElementListener`: `?v=` / `pimcore_version` load any version without an ownership check; preview has no permission check

**File:** `src/EventListener/Frontend/ElementListener.php:110-132, 134-186, 218-233`

- `handleVersion()` (any visitor): `Version::getById($request->query->getInt('v'))` is used if it is `public`. Nothing
  checks `getCtype() === 'document'` and `getCid() === $document->getId()`.
  - A public version of document X is rendered at the URL of document Y (content spoofing).
  - A public version of an **object or asset**: `getData()` returns a `DataObject` / `Asset`. That violates the
    `: Document` return type, so **any anonymous visitor can trigger a 500** with `?v=<id>`.
- `pimcore_version` (backend users) has the same missing cid/ctype check, plus a `TypeError` for non-document versions
  (`?Document` return type).
- Any logged-in backend user, **regardless of document permissions**, can see unpublished documents (line 87), version
  previews, session-stored preview documents and object previews (`pimcore_object_preview`). There is no
  `$document->isAllowed('view')` / `isAllowed('versions')` and no `$object->isAllowed('view')`.

**Fix direction:** require `$version->getCtype() === 'document' && $version->getCid() === $document->getId()` and
`getData() instanceof Document`. Otherwise 404. Check `isAllowed('view')` / `isAllowed('versions')` for the user.
Check the object permission in `handleObjectParams()`.

---

## [x] B-36 – `FullPageCacheListener` caches and replays `Set-Cookie` headers, ignores `Vary`

**File:** `src/EventListener/Frontend/FullPageCacheListener.php:311-354, 367-390`

- `responseCanBeCached()` only rejects `no-store`, `BinaryFileResponse` and `StreamedResponse`. The whole `Response`
  object is serialised into the cache, **including any `Set-Cookie` header** the controller set (A/B bucket, CSRF
  double-submit cookie, consent/tracking IDs). Every later cache hit sends **the first visitor's cookies to everyone**.
- A `Vary` header (`Accept-Language`, `Accept`, custom) is ignored. The key is host + URI + method + device only
  (line 258). Content negotiated for one client is served to all.
- `Cache-Control: private` cannot be used as an opt-out, because Symfony's default for a response without cache
  headers is `no-cache, private`. This makes `no-store` the only opt-out, and that is not documented.

**Fix direction:** reject responses with `$response->headers->getCookies()` (or strip them before saving). Reject or
key by `Vary`. Document `no-store` (or a dedicated `X-Pimcore-Output-Cache: off` header) as the opt-out. Do it together
with the shared "is publicly cacheable" helper from C-09.

---

## [ ] B-37 – `ResponseExceptionListener`: `TypeError` instead of the error page, and handling of sub-request exceptions

**File:** `src/EventListener/ResponseExceptionListener.php:64-131`, related `lib/Document/Renderer/DocumentRenderer.php:77`

- `Document\Page::getById(1)` (line 115) returns `null` when the root is not a `Page`, or for an error-page path that
  points to a snippet/link. `DocumentRenderer::render(Document\PageSnippet $document)` then throws a **`TypeError`**.
  `catch (Exception $e)` (line 123) does not catch `Error`, so the original exception is replaced by a `TypeError` and
  the visitor gets Symfony's generic 500.
- The fallback text is `'Page not found. 🦄'`, even for status 500 / 403 / 503.
- **Sub-request exceptions:** Twig `render()` / `render_esi()` go through `FragmentHandler`, which sets
  `ignore_errors = !kernel.debug`. `InlineFragmentRenderer::render()` (http-kernel 7.4) then dispatches
  `KernelEvents::EXCEPTION` with the **parent** request and `SUB_REQUEST` type, and **throws the response away**. This
  listener does not check `isMainRequest()`. In prod, every failing fragment therefore renders a **complete error page
  document** for nothing and logs the exception (line 100).
  - If the error page's layout contains the same failing fragment (e.g. a broken footer snippet rendered via
    `render()`), each nested error-page render triggers another one: **unbounded recursion** until memory or stack is
    exhausted.
  - **Verify** with a layout that renders a throwing controller via `{{ render(controller(...)) }}`, in prod mode.

**Fix direction:** `catch (Throwable)`. Check `$document instanceof Document\PageSnippet` before rendering, otherwise
leave the event alone. Return early for `!$event->isMainRequest()`. Guard against re-entry: return if the request
already carries the `exception` attribute, or keep a re-entrancy flag. Use a neutral fallback text with the real
status.

---

## [ ] B-38 – `StaticPageGeneratorListener`: stale static files after move/rename; children of a moved parent are never cleaned up

**File:** `src/EventListener/Frontend/StaticPageGeneratorListener.php:155-175`

- On `POST_UPDATE`, `remove($document)` computes the file path from the document's **new** path. After a rename or
  move, the old `<old-path>.html` stays in storage, and `onKernelRequest()` (priority 510, before routing) **keeps
  serving the old content at the old URL** (and the old URL never 404s or redirects).
- Moving or renaming a parent changes the paths of all descendants, but no event fires for them. All their static
  files are orphaned and still served.
- The same applies to site root changes and `Site` main-domain changes.

**Fix direction:** `Document::save()` already puts `oldPath` on the `POST_UPDATE` event when the path changed
(`models/Document.php:391`). Remove `<oldPath>.html` and the whole `<oldPath>/` prefix in storage, or regenerate.

---

## [ ] B-39 – `MaintenancePageListener`: proxy/IPv6 bypass, crash without sessions, blank page

**File:** `src/EventListener/MaintenancePageListener.php:63-115`, related `lib/Tool/MaintenanceModeHelper.php:59-76`

- Only `127.0.0.1` is exempt (line 109).
  - `::1` (IPv6 localhost, the default for `localhost` on many systems) gets the 503.
  - **Behind a reverse proxy without `framework.trusted_proxies`**, `getClientIp()` is the proxy IP. If the proxy runs
    on the same host (Varnish/nginx → Apache), every client looks like `127.0.0.1` and **maintenance mode never
    shows**.
  - Make the list configurable and use `IpUtils::checkIp()`.
- `$request->getSession()` (line 88) throws `SessionNotFoundException` when `framework.session` is disabled (headless
  or API-only setups), which makes **every request a 500**. Use `$request->hasSession()`.
- `getTemplateCode()` is `null` when the resource cannot be located or read, so the page is an empty 503.
  `locateResource()` throws instead of returning a missing path, which crashes container warm-up/first use. Ship a
  hard-coded fallback text.
- No `Retry-After` header. The legacy `maintenance.php` file branch (`include` on every request, `@unlink`) still
  exists next to the `TmpStore` mechanism.
- Static pages bypass maintenance mode (C-09). If the admin who enabled maintenance mode loses their session
  (logout / session id migration on login), only the CLI can turn it off, and that is broken (C-03).

---

## [ ] B-40 – `ContentTemplateListener` diverges from Symfony 7.4 `TemplateAttributeListener`

**File:** `src/EventListener/Frontend/ContentTemplateListener.php:59-108`
(reference: `symfony/twig-bridge` 7.4 `EventListener/TemplateAttributeListener.php`)

- `#[Template(vars: ['post'])]`: Symfony treats `vars` as a **list of controller argument names** to pass. Here
  `array_keys($vars)` / `array_merge(..., $vars)` adds the **values** under numeric keys (`0 => 'post'`). The argument
  is never passed, and integer-keyed junk lands in the template context.
- `vars: []` ("pass nothing") is not honoured.
- Symfony uses the controller result **or** (when it is `null`) the resolved arguments. This listener always merges
  all named controller arguments into the template context, including `Request`, services and the `Document`, **and
  throws a generic `Exception` on any duplicate key**. The very common
  `public function x(Request $request, string $slug) { return ['slug' => $slug]; }` therefore ends in a **500**
  ("Duplicate keys found: slug").
- `#[Template(block: …)]` (Symfony ≥ 7.2) is ignored, so the whole template is rendered.
- A `Template` instance in the `_template` request attribute (supported by Symfony) is ignored.

**Fix direction:** reuse Symfony's semantics: `$parameters = $event->getControllerResult() ?? resolveParameters($args,
$attribute?->vars)`, with `array_intersect_key(..., array_flip($vars))`. Support `block` via
`$twig->load()->renderBlock()` / `displayBlock()`. Drop the duplicate-key exception.

---

## [ ] B-41 – `RoutingListener` main-domain redirect: redirect loops and lost POST bodies

**File:** `src/EventListener/Frontend/RoutingListener.php:149-195`

- `$gc['domain'] !== $request->getHost()` (line 190) and `$site->getMainDomain() != $request->getHost()` (line 160)
  compare case-sensitively and without the port. `getHost()` is lower-case and port-less. A configured domain
  `Example.com`, or `example.com:8080`, never matches, which gives an **endless 301 loop** (cached by browsers).
- It always answers `301`, also for `POST` / `PUT` (admin context too, line 73). Clients turn those into `GET` and the
  **request body is lost**. Use `308`, or only redirect safe methods.
- PSR-3 misuse at line 178: the message is the anonymised IP and the "context" is a list with the actual text, so
  log lines read like `127.0.0.xxx`. Swap them: `'Host redirect {from} -> {to}', ['ip' => …]`.

---

## [ ] B-42 – `WorkflowManagementListener`: `disable()` has no effect

**Files:** `src/EventListener/WorkflowManagementListener.php:41, 65-102, 136-149`, `config/event_listeners.yaml:75-76`

- The service is made `public: true  # can be disabled`, and it has `enable()` / `disable()` / `isEnabled()`. Neither
  handler reads `$this->enabled`. Code that disables the listener (e.g. for bulk imports, to skip initial-place
  marking) still runs workflow marking and still deletes workflow states.

**Fix direction:** `if (!$this->enabled) { return; }` in both handlers. Or remove the flag and the public flag, and
document the event-dispatcher way of disabling it.

---

## [ ] B-43 – Push/pop listeners lose balance when a sub-request throws (block state, Twig globals, placeholder index)

**Files:** `src/EventListener/Frontend/BlockStateListener.php:52-90`,
`src/EventListener/Frontend/GlobalTemplateVariablesListener.php:59-92`,
`src/EventListener/Frontend/DocumentRendererListener.php:43-52` + `lib/Document/Renderer/DocumentRenderer.php:82-137`

- They push on `kernel.request` / `kernel.controller` / `RENDERER_PRE_RENDER` and pop on `kernel.response` /
  `RENDERER_POST_RENDER`.
  - `InlineFragmentRenderer` handles sub-requests with `catch = false`. A throwing sub-request therefore gets **no**
    `kernel.response`.
  - `DocumentRenderer::render()` dispatches `POST_RENDER` outside any `try/finally`.
- With `ignore_errors` (prod, B-37) the parent page keeps rendering with:
  - the **sub-request's block state** still on top of `BlockStateStack`, so block/areablock indices and editable names
    are wrong;
  - the sub-request's `document` / `editmode` Twig globals;
  - the placeholder container index of the failed document, so `headTitle` / `headScript` content goes missing or
    lands in the wrong place.
- The static page generator renders many documents in one CLI process, so one failing document corrupts all later
  ones.
- `GlobalTemplateVariablesListener` can also pop the **parent's** entry when a sub-request gets a response from a
  request listener (no controller event, but a response event).
- `DocumentRenderer::render()` also does not restore `$this->localeService->setLocale($tempLocale)` on exception.

**Fix direction:** pop in `kernel.finish_request`, which Symfony always dispatches, even on exceptions. Or store the
pushed depth in a request attribute and pop only when it is set. Wrap the render in `DocumentRenderer` in
`try/finally` for `POST_RENDER` and the locale restore.

---

## [ ] B-44 – `FullPageCacheListener` request side: superglobals, comma-split regexes, unchecked cache item

**File:** `src/EventListener/Frontend/FullPageCacheListener.php:155-169, 189-237, 243-280`

- `$_SERVER['HTTP_CACHE_CONTROL']`, `$_SERVER['HTTP_PRAGMA']`, `$_COOKIE` and `$_REQUEST` are read instead of the
  `Request`. Sub-requests, `Request::create()` in tests and worker runtimes see the wrong values.
  - `no-cache` is only honoured on **non-HTTPS** requests ("unfortunately only Chrome + Firefox if not using SSL",
    line 156, an outdated assumption). A `Cache-Control: no-cache, max-age=0` (the real browser value) never matches
    `=== 'no-cache'`.
- `exclude_patterns` is split on `,` (line 190). A regex with a quantifier like `{1,3}` is cut in two.
  `@preg_match()` (line 232) hides the warning, so an invalid pattern silently **excludes nothing**.
- Cache hit (lines 271-279): `/** @var Response */` without `instanceof`. A non-`Response` value under that key gives a
  `TypeError` in `setResponse()`. `strtotime($response->headers->get('X-Pimcore-Cache-Date'))` with a missing header
  passes `null` (deprecated in PHP 8.1+) and computes `Age` from `false`. A listener on `PREPARE_RESPONSE` can remove
  that header.
- Cache key (line 258): host + raw `getRequestUri()`.
  - The scheme is not part of it, so http and https share entries (mixed-content / wrong absolute URLs).
  - The query string is not normalised: `?a=1&b=2` and `?b=2&a=1` are different keys.
  - Any random `?x=<n>` creates a new entry, so anonymous clients can **flood the cache backend**.
  - Consider an allowlist/ignore list of query parameters (`utm_*`, `gclid`, `fbclid`).

---

## [ ] B-45 – `EditmodeListener`: duplicate script, malformed CSS URL, wrong meta attribute, possible `null` user

**File:** `src/EventListener/Frontend/EditmodeListener.php:139-315`

- `helper.js` is listed twice in `getEditmodeScripts()` (lines 270 and 298). It is loaded/concatenated twice, and all
  of its global functions and listeners are defined twice.
- `editmode.css?_dc=` . `time()` (line 311), and then `buildHeadHtml()` appends `?_dc=<revision>` (line 203). The
  result is `editmode.css?_dc=1700000000?_dc=abc` (malformed), and the stylesheet is never cached.
- `<meta name="google" value="notranslate">` (line 198) has the wrong attribute (`content=`), so Google Translate is
  not disabled in editmode.
- `$user->getLanguage()` (line 166): `$user` comes from `UserLoader::getUser()` and is `null` when editmode is forced
  (`EditmodeResolver::$forceEditmode`) without a session user. That is an `Error` on `null`.
- `file_get_contents(PIMCORE_WEB_ROOT . $scriptUrl)` (line 224): bundle editmode JS paths
  (`getEditmodeJsPaths()`) can be absolute URLs or routes. That gives a warning, `false` concatenated, and a broken
  minified bundle. It runs on every editmode request.
- `insertBefore()` hard-codes length `7` (line 185). That only works because both callers pass a 7-character search
  string. Use `strlen($search)`.

---

## [ ] B-46 – `ResponseStackListener` replaces the response after `#[ResponseHeader]` headers were applied

**Files:** `src/EventListener/ResponseStackListener.php:37, 47-49`, `src/EventListener/ResponseHeaderListener.php:40, 44-58`

- `ResponseHeaderListener` (RESPONSE 32) applies the controller's `#[ResponseHeader]` attributes. Then
  `ResponseStackListener` (RESPONSE 24) swaps in the areabrick response. **The configured headers are lost**
  (CSP, cache headers, …).
- The stack is never popped (C-08).

**Fix direction:** give `ResponseStackListener` a higher priority than `ResponseHeaderListener` (e.g. 40), and pop
the response.

---

## [ ] B-47 – `ElementTagsListener`: tag cleanup only for assets (orphans for documents/objects; asset tags lost on restore)

**File:** `src/EventListener/ElementTagsListener.php:35-63`, related `bundles/InstallBundle/dump/install.sql:399-406`

- `tags_assignment` has no foreign keys. Only `AssetEvents::POST_DELETE` clears assignments. Deleted **documents and
  objects leave orphan rows** forever, and nothing else removes them (grep: no other `setTagsForElement` callers).
- For assets the cleanup happens on delete, so **restoring an asset from the recycle bin loses its tags**. Documents
  and objects keep them. The behaviour is inconsistent.
- `onPostCopy()` calls `$e->getArgument('base_element')` without `hasArgument()`. A third-party dispatch of
  `POST_COPY` without it throws `InvalidArgumentException` inside the copy.

**Fix direction:** decide on one policy. Either clear on delete for all three types and restore tags from the recycle
bin item, or never clear and add a maintenance task that removes orphans. Use the event's typed getter or
`hasArgument()`.

---

## [ ] B-48 – `LocaleListener`: `Content-Language` from the last sub-request, set on every response

**File:** `src/EventListener/Frontend/LocaleListener.php:44-82`

- `onKernelRequest()` runs for **all** requests and contexts, including sub-requests and admin. `$lastLocale` is
  overwritten by each sub-request (a snippet in another language, an `action()` call). The **main** response then
  gets the sub-request's `Content-Language`.
- `setlocale(LC_ALL, …)` is process-wide and is not restored after a sub-request finishes, so the rest of the parent
  page renders with the sub-request's locale.
- `Content-Language` is added to admin JSON, binary and redirect responses too.

**Fix direction:** read the locale from the main request in `onKernelResponse()`. Restore the parent's locale on
`kernel.finish_request` for sub-requests. Limit it to the default context and HTML responses.

---

## [ ] B-49 – `EventedControllerListener` ignores invokable and closure controllers

**File:** `src/EventListener/EventedControllerListener.php:40-55`

- `if (!is_array($callable)) return;`: a service controller with `__invoke()` (the Symfony-recommended single-action
  style) is an **object**, not an array. Its `KernelControllerEventInterface` / `KernelResponseEventInterface` hooks
  are never called.
- `_event_controller` stores the whole controller object in the request attributes. The profiler's request collector
  then clones and dumps it (large dumps, and it pulls in services).

**Fix direction:** `$controller = is_array($callable) ? $callable[0] : (is_object($callable) && !$callable instanceof
Closure ? $callable : null)`. Keep the object in a private `WeakMap<Request, object>` instead of a request attribute.

---

## [ ] B-50 – `WebDebugToolbarListener`: an empty `excluded_routes` entry disables the toolbar everywhere

**Files:** `src/EventListener/WebDebugToolbarListener.php:69-75`, `lib/Http/RequestMatcherFactory.php:49-70`,
`src/DependencyInjection/Configuration.php:1200-1211`

- An entry without `path` / `host` / `methods` / `route` builds an **empty** matcher list. `new
  ChainRequestMatcher([])` matches **every** request, so the toolbar is removed on all pages.
- The default config ships an `excluded_routes` example `['path' => '^/test/path']` as a **real default**.
- The loop keeps going after a match and builds a `ChainRequestMatcher` per entry on every request.

**Fix direction:** validate in `Configuration` that an entry has at least one criterion. Drop the sample default.
Build the chain matchers once and `break` after the first match.

---

## [ ] B-51 – `ResponseInjectionTrait` and HTML filters: `getContent()` can be `false`

**Files:** `src/EventListener/Traits/ResponseInjectionTrait.php:42-55`,
`src/EventListener/Frontend/InternalWysiwygHtmlAttributeFilterListener.php:67-70`,
`src/EventListener/Frontend/StaticPageGeneratorListener.php:150-151`

- `Response::getContent()` returns `string|false`. Under `strict_types`:
  - `injectBeforeHeadEnd()` passes it to `strripos()`, which gives a `TypeError` for a `StreamedResponse`;
  - `InternalWysiwygHtmlAttributeFilterListener` passes it to `preg_replace()`. It is only protected by
    `isHtmlResponse()` excluding streamed responses;
  - `StaticPageGeneratorListener` passes `false` on as page content.
- Today these are only reachable from custom response classes. Add an explicit `if (!is_string($content)) return;`.

---

## [ ] B-52 – `HardlinkCanonicalListener`: `TypeError` when the hardlink source cannot be loaded

**File:** `src/EventListener/Frontend/HardlinkCanonicalListener.php:85-97`

- `Document::getById($document->getId())` can return `null` (source deleted while the wrapper is still cached, or
  unpublished with `hideUnpublished`). `Frontend::isDocumentInCurrentSite(Document $document)` then throws a
  **`TypeError` on every response** for that hardlink.

**Fix direction:** `if (!$source) return;`. Consider `$document->getHardLinkSource()` instead of a second load.

---

## [ ] B-53 – `PimcoreContextListener::initializeContext(string)` receives `?string`

**File:** `src/EventListener/PimcoreContextListener.php:62-75, 83`

- `PimcoreContextResolver::getPimcoreContext()` is declared `?string`. The `else` branch at line 69 is written for
  `null` and then still calls `initializeContext($context, …)` with a non-nullable `string`.
- Today the guesser always returns a default, so this is latent: it breaks as soon as a custom resolver returns
  `null`. PHPStan level ≥ 7 flags it.

**Fix direction:** make the parameter `?string` and treat `null` like the default context, or return early.

---

# Scope 4 – `DependencyInjection` (+ `config/pimcore/*.yaml`, extension-loaded `config/*.yaml`)

Same rules: IDs continue from B-53 and are stable. Line numbers are from commit `402145a525`. Items are ordered by
impact. For the verification setup (symfony/config 7.4.17 `Processor` script) see the note at the top of Scope 4 in
TODO-1. Related critical items: C-11 (config directory prefix), C-12 (Twig sandbox).

---

## [x] B-54 – `beforeNormalization()` casts break env placeholders: `'%env(bool:X)%'` and `"false"` both become `true`

**File:** `src/DependencyInjection/Configuration.php`. Nodes with `->beforeNormalization()->ifString()->then(fn => (bool)$v)`:
`general.redirect_to_maindomain` (208-216), `general.disable_usage_statistics` (252-260),
`general.debug_admin_translations` (261-270), `applicationlog.mail_notification.send_log_summary` (323-332),
`assets.image.thumbnails.clip_auto_support` (478-487), `…auto_clear_temp_files` (514-523),
`assets.versions.use_hardlinks` (620-628), `assets.versions.disable_stack_trace` (629-637),
`assets.metadata.predefined.definitions.*.inheritable` (682-689), `objects.ignore_localized_query_fallback` (726-734),
`objects.versions.disable_stack_trace` (745-753), `documents.doc_types.definitions.*.staticGeneratorEnabled`
(861-867, `always()`), `documents.versions.disable_stack_trace` (883-891), `documents.generate_preview` (918-926),
`documents.areas.autoload` (948-956), `properties.predefined.definitions.*.inheritable` (1860-1866).
Same root cause, CSV variant: `general.valid_languages` / `required_languages` (221-237, `explode(',', $v)`).

- `BaseNode::normalize()` (symfony/config 7.4) runs the `beforeNormalization` closures **before** it resolves env
  placeholders. During container build `'%env(bool:REDIRECT)%'` is the string
  `env_<hash>_bool_REDIRECT_<hash>`, and `(bool)` turns it into `true`. The env var is **never read**, and the option is
  permanently on. Reproduced: env placeholder → `bool(true)`.
- Any YAML/settings value that arrives as a string is cast with PHP truthiness: `"false"`, `"no"`, `"off"` → `true`
  (reproduced). Only `""` and `"0"` give `false`.
- Real effects: `redirect_to_maindomain` forced on (all hosts redirected to the main domain, multi-site broken),
  `areas.autoload` cannot be switched off per environment, `generate_preview` / `clip_auto_support` /
  `use_hardlinks` ignore their env value.
- CSV variant: `explode(',', 'env_…_LANGS_…')` gives a one-element list containing the placeholder. The env value
  `"en,de"` is then **one** language `"en,de"`. A plain YAML string `"en, de"` keeps the space (`" de"`, no trim).

**Verify:** `REDIRECT=0` + `pimcore.general.redirect_to_maindomain: '%env(bool:REDIRECT)%'`, then
`bin/console debug:config pimcore general` (shows `true`), or the test from I-64.

**Fix direction:** remove the casting closures. `BooleanNode` already accepts real booleans and `%env(bool:…)%`. If
string input from the settings store must be supported, use
`->ifString()->then(fn (string $v) => filter_var($v, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $v)` and **skip**
env placeholders (`str_starts_with($v, 'env_')` is not enough; prefer converting the settings-store values in
`SystemSettingsConfig` before they reach the tree). For CSV, use `%env(csv:LANGS)%` in docs and `array_map('trim', …)`
for literal strings.

---

## [ ] B-55 – `ConfigurationHelper::getConfigNodeFromSymfonyTree()` shallow-merges config files (`config_location` lost; Kernel and runtime disagree)

**Files:** `src/DependencyInjection/ConfigurationHelper.php:103-150`, `lib/Kernel.php:87-131`,
`lib/Config/LocationAwareConfigRepository.php:289-329`

```php
$containerConfig = $container->getExtensionConfig($nodeName);   // one array per file
$containerConfig = array_merge(...$containerConfig);             // shallow!
$containerConfig = $processor->processConfiguration($configuration, [$nodeName => $containerConfig]);
```

- `array_merge()` replaces whole **top-level** keys. If `pimcore.config_location` appears in two files (CoreBundle
  `default.yaml:343-346` already defines `config_location.select_options`; projects usually add their own in
  `config/packages/*.yaml`, often split per environment), only the last file's `config_location` survives. The same
  happens for any other subtree. Reproduced: `{loc:{a}}` + `{loc:{b}}` → `a` falls back to its default.
- The real container config (`pimcore.config`) is merged correctly by the extension. So **the Kernel decides with a
  different config than the runtime**:
  - `Kernel::registerContainerConfiguration()` imports `var/config/<key>/*.yaml` although the project switched that key
    to `settings-store` in another file, so stale YAML definitions are loaded. Or it imports from the default directory
    although a custom `options.directory` was set in another file, so the definitions are missing.
  - The same applies to `LocationAwareConfigRepository::loadSymfonyConfigFiles()` (custom reports, static routes).
- Further defects in the same helper:
  - `getSymfonyConfigFiles()` returns Finder results **unsorted**. When two YAML files define the same key, the winner
    depends on the filesystem. The Kernel path imports via `$loader->import($dir)`, which sorts, so the two paths can
    even disagree.
  - `getExtension($nodeName)->getConfiguration()` can return `null`. This is hidden by `@phpstan-ignore-next-line`
    (line 143), and `getExtension()` throws for an unknown alias.
  - `resolveValue()` leaves env placeholders as `env_…` strings. A `config_location.*.type: '%env(…)%'` therefore never
    matches a `LOCATION_*` constant.

**Verify:** put `pimcore.config_location.image_thumbnails.write_target.type: settings-store` in
`config/packages/a.yaml` and any other `pimcore.config_location.*` key in `b.yaml`. Add a stale
`var/config/image_thumbnails/x.yaml`. Its definition still shows up in `debug:config pimcore assets.image.thumbnails`.

**Fix direction:** `return $resolvingBag->resolveValue($processor->processConfiguration($configuration,
$container->getExtensionConfig($nodeName)));`. Pass the list as-is, so the `Processor` does the deep merge. Add
`->sortByName()` to the Finder. Replace the phpstan-ignore with an `instanceof ConfigurationInterface` check that throws a
clear `LogicException`. De-duplicate the Kernel/repository loaders (I-63).

---

## [ ] B-56 – Workflow `placeholders` are kept on the `Configuration` instance: order-dependent, leak into other workflows, applied only partly

**File:** `src/DependencyInjection/Configuration.php:37-45, 1320-1338, 1365-1378, 1499-1510`,
`Config/Processor/PlaceholderProcessor.php:45-60`

- The `placeholders` node's `beforeNormalization` stores the value in `$this->placeholders`. `marking_store.arguments`
  and `places` read it later in their own `beforeNormalization`. `ArrayNode::normalizeValue()` processes children **in
  input order**, so:
  - `places:` written **before** `placeholders:` in YAML is never replaced;
  - a workflow **without** a `placeholders` key does not reset the property, so it gets the **previous workflow's**
    placeholders (`%%locale%%` of workflow A replaced inside workflow B);
  - each config file is normalised separately with the same instance, so state also crosses files.
- Only `marking_store.arguments` and `places` are processed. `transitions` (labels, guards, `notificationSettings`,
  `mailPath`), `globalActions`, `support_strategy.arguments` and `initial_markings` keep the raw `%%…%%` text. The node
  `info()` says "Placeholder values in this workflow configuration … will be replaced".
- `PlaceholderProcessor` also replaces inside **keys**. Two keys that become equal after replacement silently overwrite
  each other (`processArrayValue()`, line 56).

**Verify:** two workflows, the first with `placeholders: {'%%x%%': 'A'}`, the second using `%%x%%` in a place label
without `placeholders`. `debug:config pimcore workflows` shows `A` in the second one.

**Fix direction:** stop mutating `$this` during normalisation. Apply placeholders once per workflow in a
`->validate()->always()` (or `beforeNormalization`) closure on the **workflow prototype** node: read `$v['placeholders']`
and run `mergePlaceholders()` over the whole workflow array except `placeholders`. Then drop the
`$placeholders`/`$placeholderProcessor` properties. Detect key collisions and throw `InvalidConfigurationException`.

---

## [ ] B-57 – Workflow tree vs. `WorkflowPass`/runtime: optional `label` read as required, `numeric` field type missing for global actions, `~` values crash normalisation

**Files:** `src/DependencyInjection/Compiler/WorkflowPass.php:66`, `src/DependencyInjection/Configuration.php:1348-1350,
1499-1510, 1529-1548, 1601-1605, 1764-1767`, `lib/Workflow/WorkflowConfig.php:37-40`

- `label` is a `scalarNode` without a default. The `info()` says it is optional. Symfony **omits** such keys from the
  processed array (reproduced). `WorkflowPass` reads `$workflowConfig['label']` without `??`, which raises
  "Undefined array key "label"". With the Symfony ErrorHandler registered (debug / `bin/console`), warnings are thrown,
  so the **container build fails**. Without it, the warning is logged and `WorkflowConfig::getLabel()` falls back to
  the name.
- Transition `additionalFields.fieldType` allows `numeric` (1603). The copy for `globalActions` (1766) does not. The same
  `numeric` field is rejected for global actions. The two `notes` subtrees are hand-copied and have drifted (the global
  action copy also has no `info()` texts).
- `places: ~` / `transitions: ~` with placeholders configured: the `always()` closures `foreach` over `null`
  (warning). `transitions` returns early only for indexed arrays.

**Verify:** a workflow without `label`, then `APP_DEBUG=1 bin/console cache:clear`.

**Fix direction:** `->scalarNode('label')->defaultNull()` (or `$workflowConfig['label'] ?? $workflowName` in the pass).
Build the `notes` subtree once in a private method used by both transitions and global actions (I-60). Guard the
closures with `->ifArray()` instead of `->always()`.

---

## [x] B-58 – Two lock backends: `services.yaml` overrides `LockFactory` with a DBAL store and ignores `framework.lock`; `AssetUpdateTasksHandler` releases in the other store

**Files:** `config/services.yaml:120-128`, `config/message_handler.yaml:9-16`,
`lib/Messenger/Handler/AssetUpdateTasksHandler.php:37,63`, `models/Asset.php:1829-1840`,
`config/documents.yaml` (`StaticPageGenerator` → `@Symfony\Component\Lock\LockFactory`)

- The extension defines a service with the ID `Symfony\Component\Lock\LockFactory` (DBAL store). This replaces
  FrameworkBundle's autowiring alias `LockFactory → lock.factory`. Every autowired `LockFactory` and every
  `Pimcore::getContainer()->get(LockFactory::class)` (thumbnails, video transcoding, LibreOffice, parallelised
  commands, `Asset::triggerUpdateTask()`) **ignores `framework.lock`** (e.g. Redis), without any notice.
- `AssetUpdateTasksHandler` gets `@lock.factory` (FrameworkBundle: `flock` by default) and calls
  `createLock($asset->getUpdateQueueLockId())->release()`. The lock was acquired in the **DBAL** store in
  `Asset::triggerUpdateTask()`, so this release is a no-op. It would also be a no-op in the same store, because a fresh
  `Lock` has a different token. The code looks like it frees the queue lock, but it does not.
- Two lock backends in one application make cross-process locking hard to reason about. `flock` does not work across
  nodes.

**Verify:** `bin/console debug:container 'Symfony\Component\Lock\LockFactory'` → Pimcore's definition;
`debug:container lock.factory` → the framework one.

**Fix direction:** delete the `PersistingStoreInterface` / `LockFactory` definitions. Ship
`framework.lock: 'doctrine+dbal://default'` (or the DSN form that matches the installed symfony/lock) in
`config/pimcore/default.yaml`, so everything uses `lock.factory`. Keep the acquired `Lock` (or its `Key`) in the message
if the handler really must release it. Otherwise remove the `release()` call.

---

## [ ] B-59 – `symfony/web-link` is not required: `pimcore_head_link` / `pimcore_head_script` with `webLink` crash

**Files:** `config/aliases.yaml:8` (`WebLinkExtension: '@twig.extension.weblink'`),
`lib/Twig/Extension/Templating/Traits/WebLinksTrait.php:94`, `lib/Twig/Extension/Templating/HeadLink.php:111`,
`HeadScript.php:136`, root `composer.json`

- TwigBundle always defines `twig.extension.weblink` (it only *tags* it when web-link is enabled). The Pimcore alias
  therefore resolves. But `WebLinkExtension::link()` / `preload()` instantiate `Symfony\Component\WebLink\Link`.
- `symfony/web-link` is only a dev requirement of framework-bundle/twig-bridge/twig-bundle and is **not** in
  `composer.json` (not present in a 7.4 install). An item with the `webLink` option therefore fails with
  `Class "Symfony\Component\WebLink\Link" not found`. Even with the package installed, the `Link` header is only sent
  when `framework.web_link` is enabled (`AddLinkHeaderListener`).

**Verify:** `{{ pimcore_head_link().appendStylesheet('/a.css', 'screen', false, {webLink: true}) }}`, or call
`WebLinksTrait::handleWebLink()` in a test.

**Fix direction:** add `symfony/web-link: ^7.4` to `composer.json` (and enable `framework.web_link` in `default.yaml`),
or drop the `webLink` feature and the alias.

---

# Scope 5 – `Controller` / `OptionsProvider` / `Request` (+ `config/routing.yaml`, `config/services.yaml`)

Same rules: IDs continue from B-59 and are stable. Line numbers are from commit `1a059513b7`. Items are ordered by
impact. Related critical items: C-13 (WebDAV `MOVE` permission bypass), C-14 (WebDAV auth/CSRF), C-15 (thumbnail
variant exhaustion).

---

## [ ] B-60 – `SelectOptionsOptionsProvider` wipes the default value of every Multiselect field that uses it

**Files:** `src/OptionsProvider/SelectOptionsOptionsProvider.php:54-61`,
`models/DataObject/ClassDefinition/DynamicOptionsProvider/SelectionProviderTrait.php:56-57`,
`models/DataObject/ClassDefinition/Data/Multiselect.php:133-143`,
`models/DataObject/ClassDefinition/DynamicOptionsProvider/SelectOptionsProviderInterface.php:39`

- `getDefaultValue()` returns the field's default only for `Data\Select`. For `Data\Multiselect` it returns `null`.
- `SelectionProviderTrait::doEnrichDefinitionDefinition()` always calls
  `$this->setDefaultValue($optionsProvider->getDefaultValue(...))`. Every enrichment (field definition and layout,
  i.e. every object load in the admin and `enrichFieldDefinition()` calls) therefore **sets the Multiselect default to
  `null`**. The default configured in the class editor is never applied to new objects.
- The interface already allows `string|array|null`; the provider narrows it to `?string`, which is why the array case
  was dropped.

**Verify:** class with a Multiselect using "Select Options" + a default value → create a new object → the field is
empty. `$def->enrichFieldDefinition(); $def->getDefaultValue()` → `null`.

**Fix direction:** return `$fieldDefinition->getDefaultValue()` for both `Data\Select` and `Data\Multiselect` (or for
any field that has `getDefaultValue()`), widen the return type to `string|array|null`. Add a unit test (I-72).

---

## [ ] B-61 – `SelectOptionsOptionsProvider`: an unset select-options ID crashes with a `TypeError` (object editor fails to open)

**File:** `src/OptionsProvider/SelectOptionsOptionsProvider.php:34-38`

- `getOptionsProviderData()` is `?string`. `Config::getById(string $id)` is strict and the file declares
  `strict_types=1`, so a field with provider class `SelectOptionsOptionsProvider` but empty "options provider data"
  throws `TypeError: Argument #1 ($id) must be of type string, null given` instead of the intended
  "Missing select options configuration" exception.
- This happens during layout enrichment, so the object editor of every object of that class fails to load. The same
  applies to an ID with stray whitespace (the enum check in `SelectOptions\Config.php:331` trims, `getById()` does not).
- Generic `\Exception` with only the ID gives no hint which class/field is misconfigured.

**Fix direction:** `$id = trim((string) $fieldDefinition->getOptionsProviderData()); if ($id === '') { throw … }`.
Include the field name (and class via `$context['class']` when present) in the message and use a specific exception
type (I-70).

---

## [ ] B-62 – `DataObjectParamResolver` crashes for folder-typed arguments and ignores `AbstractObject`

**File:** `src/Request/ParamResolver/DataObjectParamResolver.php:41-69`

- `is_subclass_of($class, AbstractObject::class)` accepts `DataObject\Folder` and `DataObject` (the generic model class),
  so `getById()` may return a `Folder`. `Folder` has no `isPublished()` (only `Concrete` does), so
  `$object->isPublished()` → `Error: Call to undefined method` → HTTP 500 instead of resolving the folder.
- `is_subclass_of()` is false for the class itself, so an argument typed `AbstractObject` is not resolved at all and
  falls through to Symfony's resolvers ("could not resolve argument"), while the narrower `DataObject` type works.
- The `/** @var Concrete|null */` annotation hides both cases from static analysis.

**Verify:** `public function a(#[DataObjectParam] DataObject\Folder $folder)` on a route `/{folder}` → 500.

**Fix direction:** `is_a($class, AbstractObject::class, true)`; only apply the published check when
`$object instanceof Concrete`; drop the wrong `@var`. Add resolver tests (I-72).

---

## [ ] B-63 – `DataObjectParamResolver`: deprecation on every call, `DataObjectParam::$options` ignored, `class` override can mismatch the argument

**Files:** `src/Request/ParamResolver/DataObjectParamResolver.php:39-60`, `lib/Request/Attribute/DataObjectParam.php:28-33`,
`models/DataObject/AbstractObject.php:190-201`

- Route attributes are strings. `$class::getById($value)` with a string triggers
  `trigger_deprecation('Passing id as string … is deprecated')` on **every** resolved request (log noise; breaks once the
  string branch is removed). Non-numeric values are silently mapped to `0` → 404, which is fine, but should be explicit.
- `DataObjectParam` has a public `?array $options` ("options for the loader") that the resolver never reads. Users who
  pass e.g. `options: ['force' => true]` get no effect and no error.
- `#[DataObjectParam(class: Foo::class)]` is used **instead of** the argument type. If it is not a subtype of the
  declared type, Symfony fails later with a generic `TypeError` when calling the controller. If the attribute names a
  non-existent class, the argument is skipped silently.
- Nullable handling is half-done: only an empty value (`''`, `'0'`) resolves to `null`; a nullable argument whose ID
  **does not exist** still throws 404. Decide which is intended and document it on `DataObjectParam`.
- `Tool::isElementRequestByAdmin()` authenticates the admin session (and may start it) for every unpublished hit on a
  frontend request.

**Fix direction:** cast with `filter_var($value, FILTER_VALIDATE_INT)` and 404 on failure; pass
`$options[0]->options ?? []` as `$params` to `getById()`; validate `is_a($attrClass, $argument->getType(), true)` and
throw a `\LogicException` with the controller/argument name otherwise.

---

## [ ] B-64 – `WebDavController` ends with `exit` and swallows errors (worker runtimes, kernel events, empty 200 responses)

**File:** `src/Controller/WebDavController.php:34-61`

- `Server::start()` writes headers and body itself, then the action calls `exit`. The Symfony response cycle is skipped:
  no `kernel.response` (security headers, CORS, profiler), no `kernel.terminate` (e.g. `DumpTranslationEntriesListener`,
  profiler storage, Messenger "after response" work). The controller's declared `void` return is not valid for Symfony
  (only works because the process dies first).
- Under worker runtimes (FrankenPHP worker mode, RoadRunner, Swoole via symfony/runtime) `exit` terminates or restarts
  the worker on **every** WebDAV request. With C-08's shared state that is the least of the problems, but it is a hard
  incompatibility with how Symfony 7.4 apps are increasingly deployed.
- `catch (Exception $e)` covers only setup. When it triggers, nothing is sent and `exit` produces an **empty `200`**.
  Typical failures are `Error`s that are not caught at all:
  - `Asset::getById(1)` returns `null` (e.g. broken tree) → `TypeError` in `new Folder(null)`;
  - `getNativeConnection()` is a `PDO` only for `pdo_mysql`; with `mysqli` (a supported DBAL driver) it is a
    `mysqli` → `TypeError` in `new Locks\Backend\PDO()`. The `/** @var PDO */` hides this.
- `Logger::error((string)$e)` logs the full trace as the message (no context array).

**Fix direction:** build the Sabre server in a service (I-67) and return a `StreamedResponse` whose callback runs
`$server->start()` with `$server->sapi` replaced by a small Sapi that writes through the Symfony response, or at least
return a `Response` and let Symfony send it (`Server::$httpResponse` → `Response`). Check
`$pdo instanceof \PDO` and fail with a clear message; 500 + log with context on setup failure.

---

## [ ] B-65 – Thumbnail errors are turned into a `302` to an admin-bundle SVG (404s become redirects, `NotFoundHttpException` swallowed)

**Files:** `src/Controller/PublicServicesController.php:52-63`, `models/Asset/Service.php:362-365,436-443`

- The action throws a generic `Exception` when the service returns `null` and catches **every** `Exception`:
  - unknown asset ID, unknown thumbnail name, prefix that does not match the asset path → `null` → redirect;
  - `NotFoundHttpException('Requested thumbnail format is disabled')` thrown by the service on purpose → caught →
    redirect;
  - storage / Imagick / ffmpeg errors → redirect.
  All of them become `302 Found` to `/bundles/pimcoreadmin/img/filetype-not-supported.svg`. Crawlers see soft-404s,
  CDNs cache a redirect for an image URL, and real failures are indistinguishable from bad URLs.
- The target is an asset of `pimcore/admin-ui-classic-bundle`, which is optional (`suggest`). Without it (headless
  setups) the redirect points to a 404.
- No cache headers on the redirect → every hit of a broken thumbnail URL re-runs the lookup/generation.
- `Logger::error($e->getMessage())` drops the exception class, trace and the request path.
- `Error`s (e.g. `TypeError` from a storage adapter) are not caught at all, unlike the `Exception`s.

**Fix direction:** return 404 (`throw $this->createNotFoundException()`) when the asset/config does not exist or the
format is disabled; let `HttpException`s through; for generation errors log with context and return 500 (or the
placeholder **content** with `Cache-Control: no-store`, not a redirect). Make the placeholder path configurable / ship
it in CoreBundle `public/`.

---

## [ ] B-66 – `robotsTxtAction` sends raw headers and flushes every output buffer

**File:** `src/Controller/PublicServicesController.php:83-85`

```php
header('Content-Type: text/plain; charset=utf8');
while (@ob_end_flush()) ;
```

- `utf8` is not a valid charset label (`UTF-8`); the returned `Response` sets `text/plain` again, so two code paths
  disagree on the header.
- `ob_end_flush()` closes **all** output buffers, including the ones owned by symfony/runtime or
  PHPUnit/Codeception (the test is then marked risky). If any buffer holds output, headers are
  sent right there; `Response::sendHeaders()` then sees `headers_sent()` and silently drops every header set by
  listeners (cache, security, `Content-Language`, …).
- Under worker runtimes the raw `header()` call bypasses the response object entirely.
- `@` hides the notice that tells you there was no buffer.

**Fix direction:** delete both lines; return `new Response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8'])`.

---

## [ ] B-67 – `robotsTxtAction` resolves the site differently from the rest of the frontend

**Files:** `src/Controller/PublicServicesController.php:68-91`, `models/Site.php:109-134`,
`bundles/SeoBundle/src/Config.php:31-52`

- The site is looked up with `Site::getByDomain(Tool::getHostname())` instead of the site the routing layer already
  resolved for this request (`SiteResolver` / request attribute). The two can differ (e.g. `redirect_to_maindomain`,
  `SiteResolver` overrides in projects).
- Every distinct `Host` header produces a cache entry without lifetime (`site_domain_<md5>` = `'failed'`, tags
  `system`, `site`) – robots.txt is the endpoint bots hit most, often with odd hosts. (Only relevant when
  `framework.trusted_hosts` is not set.)
- `class_exists(SeoBundle\Config::class)` is true whenever the package is installed, even when the bundle is **not
  enabled**: robots content edited while the bundle was active is still served after it was disabled.
- Empty config value (`''`/`'0'`) falls back to "allow all" via `empty()`; a deliberately empty robots.txt cannot be
  served.

**Fix direction:** use the site resolved for the request (`Pimcore\Http\Request\Resolver\SiteResolver::getSite()`);
check the bundle via `kernel.bundles` (or move the action into SeoBundle, I-69); use `=== null` instead of
`empty()`.

---

## [ ] B-68 – `customAdminEntryPointAction` fails without the admin bundle and forwards all query parameters

**Files:** `src/Controller/PublicServicesController.php:108-126`

- The action is not routed anywhere in this repository; it is only reachable when pimcore/admin-ui-classic-bundle routes
  it. `getParameter('pimcore_admin.custom_admin_path_identifier')` is defined only by that bundle → without it a
  `ParameterNotFoundException` (500).
- The whole query string (`$request->query->all()`) is passed to `generateUrl()` for login routes. Arbitrary extra
  parameters (incl. nested arrays) end up in the login URL; whether that is harmless depends on the admin bundle's
  login handling (`_target_path`, `perspective`, …). Pass only the known keys (`token`, `deeplink`, `perspective`).
- `!=` comparison of the cookie value (`'0' == ''`-style surprises; use `!==`).
- `strtotime('+1 year')` returns `int|false`; with `false` the cookie becomes a session cookie.

**Fix direction:** move the action into the admin bundle, or guard with `$this->container->has('parameter_bag') &&
…->has(...)`; whitelist query keys; `Cookie::create(...)->withExpires(new \DateTimeImmutable('+1 year'))`.

---

## [ ] B-69 – Thumbnail path decoding uses `urldecode()` (a literal `+` becomes a space) and checks the wrong string length

**Files:** `src/Controller/PublicServicesController.php:53`, `models/Asset/Service.php:565,573`

- `getPathInfo()` is URL-encoded. `urldecode()` is the form-encoding decoder: `+` → space. An asset/thumbnail filename
  containing `+` (allowed by `Element\Service::getValidKey()`) never matches the stored file and is regenerated (and
  stored again) on every request, or fails.
- `strlen($uri) > 10` checks the raw URI, not the prefix-stripped `$storagePath` that is actually used.
- The controller passes `getPathInfo()` (no base URL) while `extractThumbnailInfoFromUri()` callers elsewhere pass full
  URIs; consistent only by accident.

**Fix direction:** `rawurldecode()`; check `$storagePath`; document that the argument is the path info.
