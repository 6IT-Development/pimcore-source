# CoreBundle – TODO 3: Improvements

Scope: **(1)** `bundles/CoreBundle/src/Command/**`, `bundles/CoreBundle/config/commands.yaml` (+ `config/extensions.yaml:33`)
**(2)** `src/DataCollector/**`, `src/PimcoreCoreBundle.php` and related: the compiler passes it registers
(`src/DependencyInjection/Compiler/*`), `src/DependencyInjection/PimcoreCoreExtension.php`, `config/profiler.yaml`,
`config/routing_dev.yaml`, `config/services_symfony_workflow.yaml`, `config/cache.yaml`, `templates/Profiler/*`
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-2-BUGS.md](TODO-2-BUGS.md)

Code quality, readability, logic simplification and Symfony 7.4 / PHP 8.4 modernisation. Nothing here is broken today.
IDs are stable – refer to them as `I-xx` in later sessions. Line numbers are from commit `7cb7841b76`.
Grouped: **A** Symfony 7.4 / configuration, **B** PHP 8.4 / code style, **C** shared logic / duplication,
**D** per-command logic, **E** related code outside scope.

---

## A – Symfony 7.4 / configuration

### [ ] I-01 – Move names/descriptions to `#[AsCommand]` everywhere (lazy loading)

**Files:** `Bundle/InstallCommand.php:41`, `Bundle/UninstallCommand.php:40`, `Bundle/ListCommand.php:35`,
`Bundle/AbstractBundleCommand.php:40-51,68-71`, `Definition/Import/AbstractStructureImportCommand.php:42-45`
(+ `ClassCommand`, `CustomLayoutCommand`, `FieldCollectionCommand`, `ObjectBrickCommand`)

- These commands set their name with `setName()` inside `configure()`. Without `#[AsCommand]` (or a `command:` tag)
  Symfony cannot lazy-load them, so **every** `bin/console` call constructs them and their dependencies
  (`PimcoreBundleManager`, `PostStateChange` → `CacheClearer`, `AssetsInstaller`).
- `Install`/`Uninstall` work around this with `command:` tags in `commands.yaml`. `ListCommand` and the four import
  commands have no tag.
- Symfony 7.3+ deprecates the `getDefaultName()`/`getDefaultDescription()` path. `#[AsCommand]` is the supported way.

**Direction:** add `#[AsCommand(name: 'pimcore:bundle:install', description: ..., help: ...)]` to every concrete
command (with `aliases: ['definition:import:class']` etc.). Drop `buildName()`, `configureDescriptionAndHelp()` and the
dynamic `setName()` in `AbstractStructureImportCommand`. Then remove the redundant `command:` tags (I-02).

### [ ] I-02 – Clean up `config/commands.yaml`

**Files:** `config/commands.yaml`, `config/extensions.yaml:33`

- The resource import sets `tags: ['console.command']` although `autoconfigure: true` already tags every `Command`
  subclass, so each service gets the tag twice.
- `PostStateChange` is defined twice: `commands.yaml` with explicit args and `extensions.yaml:33` with `~`. The later
  load (`commands.yaml`) wins silently. Keep one, preferably the autowired `~` with no args.
- Explicit `arguments` for `OptimizeImageThumbnailsCommand`, `MaintenanceCommand`, `InstallCommand`,
  `UninstallCommand`: these are autowirable. Use `#[Autowire(service: 'logger')]` or a typed `LoggerInterface`
  where needed.
- `ClassesRebuildCommand` is injected twice: `calls: setClassDefinitionManager` in YAML **and** `#[Required]` on the
  setter (`ClassesRebuildCommand.php:67`). Switch to constructor injection and remove both.
- `command:` tags for `OptimizeImageThumbnailsCommand`, `MaintenanceCommand` (plus a duplicate `maintenance` alias
  tag) and `ClassesRebuildCommand` duplicate their `#[AsCommand]` name/aliases.
- `Migrate\StorageCommand` `$locator: !tagged_locator` can become
  `#[AutowireLocator('flysystem.storage')] ContainerInterface $locator` in the constructor.
- After I-01/I-02 the whole file can shrink to the resource import plus `exclude`.

### [ ] I-03 – Return-code constants and `INVALID` for bad input

**Files:** almost all commands (`return 0;` / `return 1;`)

- Use `Command::SUCCESS`, `Command::FAILURE` and `Command::INVALID` (exit 2, for wrong options or arguments). A few
  commands already do this (`InstallCommand`, `Document/CleanupCommand`).

### [ ] I-04 – Correct option modes and add `suggestedValues` (shell completion)

- `InputOption::VALUE_OPTIONAL` is used for options that make no sense without a value. `--parent` without a value
  becomes `null` and is silently ignored. Use `VALUE_REQUIRED` for:
  - `parent` (Asset/*, `LowQualityImagePreview`, `GeneratePagePreviews`, `ThumbnailsImage`, `ThumbnailsVideo`)
  - `path-pattern` / `pathPattern`
  - `skip-locales`, `urlPrefix`, `exclude-patterns`, `min-level`, `password`, `thumbnails`,
    `last-modified-since`, `generator`
  - `id` in `RemoveCustomSetting` / `ThumbnailsImage` (`VALUE_OPTIONAL | VALUE_IS_ARRAY`)
- Add `suggestedValues` for completion. For example: `CacheWarmingCommand` types, `MysqlToolsCommand --mode`,
  `ThumbnailsClearCommand --type` (+ closure listing config names), `RequirementsCheckCommand --min-level`,
  `MaintenanceCommand --job` (`$executor->getTaskNames()`), `WorkflowDumpCommand name`, `ResetPasswordCommand user`.

### [ ] I-05 – Consistent option naming (kebab-case)

- These options use camelCase while their siblings use kebab-case (`path-pattern`, `older-than-days`):
  - `pathPattern` (`LowQualityImagePreview`, `ThumbnailsImage`)
  - `urlPrefix` (`GeneratePagePreviews`)
  - `excludedJobs` (`MaintenanceCommand`)
  - `documentTypes`, `assetTypes`, `objectTypes` (`CacheWarmingCommand`)
- Option names are public API. Add the kebab-case name, keep the old one hidden for one release with a deprecation
  notice, then remove it.

### [ ] I-06 – Use `$this->io` (SymfonyStyle) consistently

- `CacheClearCommand.php:70` creates a second `SymfonyStyle` although `AbstractCommand` already provides
  `$this->io`.
- `QuestionHelper` / `getHelper('question')` (`AbstractStructureImportCommand:149-163`, `MigrateElementsCommand:66-79`,
  `ResetPasswordCommand:88-105`) → `$this->io->confirm()` / `$this->io->askHidden()`.
- `new ProgressBar(...)` (`GenerateStaticPages`, `LowQualityImagePreview`, `Migrate/*`) → `$this->io->createProgressBar()`
  or `$this->io->progressIterate()`.
- `echo` (`DeleteClassificationStoreCommand`) and the `writeError()`/`writeInfo()` helpers → `$this->io->error()` /
  `->note()` / `->text()`.
- In `AbstractBundleCommand::handlePrerequisiteError()`/`getBundle()`, use `$this->input` rather than
  `$this->io->getInput()`.

### [ ] I-07 – Invokable commands (Symfony 7.3/7.4) for new or rewritten commands

- Symfony 7.4 supports `__invoke()` with `#[Argument]`, `#[Option]`, `#[MapInput]`, `#[Ask]`, `#[Interact]`, which
  removes `configure()` boilerplate and gives typed input.
- `AbstractCommand` relies on `initialize()` + `execute()`. Do this gradually and only where a command is rewritten
  anyway. Good candidates: `CacheClearCommand`, `ThumbnailsClearCommand`, `ResetPasswordCommand` (`#[Ask(hidden: true)]`),
  `MaintenanceModeCommand`, `DeleteClassificationStoreCommand`, `EmailLogsCleanupCommand`, `RecyclebinCleanupCommand`.
- Evaluate whether `AbstractCommand` can offer the `dump()` helpers without forcing `execute()`, e.g. as a trait.

### [ ] I-08 – Move long multi-line descriptions into `help:`

**Files:** `ClassesDefinitionsBuildCommand.php:35-39`, `ClassesRebuildCommand.php:34-39`,
`Document/MigrateElementsCommand.php:29-33`, `Migrate/ThumbnailsFolderStructureCommand.php:32-38`

- Descriptions contain line breaks and source-code indentation, which looks broken in `bin/console list`.
  `#[AsCommand]` in Symfony 7.4 has `help:` and `usages:` parameters. Keep the description to one line and put the
  details (and the `<asset storage>/...` structure) into `help`.
- `WorkflowDumpCommand` sets its help in `configure()`. Move it to `help:`. Also drop the stray empty line inside the
  attribute (`WorkflowDumpCommand.php:35-37`).
- `MaintenanceCommand.php:45` duplicates the description string from the attribute.

---

## B – PHP 8.4 / code style

### [ ] I-09 – PHP 8.3/8.4 idioms

- `#[\Override]` on every overridden `configure()`, `execute()`, `initialize()`, `interact()`, `runSingleCommand()`,
  `fetchItems()`, `getItemName()`. It catches signature drift on the next Symfony upgrade.
- `json_validate()` (8.3) instead of `json_decode()` used only to validate (`AbstractStructureImportCommand:137`,
  `QuantityValueCommand:83`).
- `new Asset\Listing()->setCondition(...)` style (8.4, no extra parentheses) where it improves readability.
- `array_any()` / `array_find()` / `array_all()` (8.4) for loops such as `CleanupCommand` protected-type filtering
  (`:57-65`) or "any check failed" in `RequirementsCheckCommand`.
- `str_ends_with($script, '.php')` instead of `preg_match('/\.php$/', ...)` (`RunScriptCommand:54`).
- `mb_strimwidth()` instead of `substr()` + `strlen()` (`ListCommand:78`).
- `match` instead of string-built class names: `ThumbnailsClearCommand:68`
  (`'Pimcore\Model\Asset\\' . ucfirst(...)` → `match ($type) { 'image' => Image\Thumbnail\Config::class, ... }`).
- Typed constants instead of mutable `protected array` properties:
  - `AddToUpdateTaskQueueCommand::$types`
  - `CacheWarmingCommand::$validTypes` and the three `$valid*Types`
  - `RequirementsCheckCommand::$levelsToDisplay` → local variable
- `readonly` promoted constructor properties: `GenerateStaticPagesCommand:41`, `ClassesDefinitionsBuildCommand:42-48`,
  `MaintenanceModeCommand:38` (currently `protected` and mutable).
- `$row[] = $row[] = $row[] = $row[] = false;` (`ListCommand:91`) → `array_push($row, false, false, false, false)`, or
  build the row as an associative array keyed by header.

### [ ] I-10 – Docblock noise and copy-paste errors

- `Definition/Import/*` and `AbstractStructureImportCommand`: docblocks such as `/** Get type * * */` repeat the method
  name and contain empty lines. Remove them or turn them into real documentation.
- `FieldCollectionCommand:39` and `ObjectBrickCommand:39` still say `class_Customer_export.json` (copied from
  `ClassCommand`).
- Empty `/** * * @throws Exception */` blocks (`MaintenanceModeCommand:56-60`).
- `@internal` is missing on `QuantityValueCommand`, `Document/CleanupCommand` and `Document/MigrateElementsCommand`.
  Every other command has it.
- `GeneratePagePreviews` is the only command class without the `Command` suffix. Rename it to
  `GeneratePagePreviewsCommand`.
- Typo `occoured` (`Migrate/StorageCommand:108`). `writeLn` has the wrong case (`:109`).

---

## C – Shared logic / duplication

### [ ] I-11 – Extract the shared asset filter + batching logic

**Files:** `Asset/AddToUpdateTaskQueueCommand`, `Asset/GenerateChecksumCommand`, `Asset/RemoveCustomSettingCommand`,
`LowQualityImagePreviewCommand`, partly `ThumbnailsImageCommand` / `ThumbnailsVideoCommand`

- The same `--id` / `--parent` / `--path-pattern` option definitions, condition building and `ceil($total / 10)`
  OFFSET loop with `collectGarbage()` are copy-pasted 4–6 times. The critical bugs C-01, C-02, B-02 and B-03 have to
  be fixed in every copy.

**Direction:** an `AssetFilterOptions` trait (configure + build `Asset\Listing`), plus a keyset-pagination iterator
(`iterateAssets(Listing $list, int $batchSize = 100): iterable`) that calls `collectGarbage()` between batches. Use a
batch size larger than 10.

### [ ] I-12 – Extract the "older-than-days" cleanup pattern and delete in batches

**Files:** `EmailLogsCleanupCommand`, `RecyclebinCleanupCommand`

- Identical option, validation and loop. Share a small trait with int validation (B-18).
- Delete in batches (`LIMIT 500` loop with `iterateAssociative()`) instead of `load()`ing everything. For email logs,
  check whether the model's `delete()` does more than one SQL row and file cleanup, otherwise use a set-based delete.

### [ ] I-13 – Share CSV/array option parsing

**Files:** `CacheClearCommand::prepareTags()` (`:96-114`), `MaintenanceCommand::getArrayOptionValue()` (`:88-105`),
plus the inline `explode(',', ...)` in `DeleteUnusedLocaleData`, `ThumbnailsImage` and `ThumbnailsVideo` (some of them
without trim)

- These are the same "array option, also accept comma-separated values, trim, drop empties" helper written several
  times. Add one helper on `AbstractCommand` (or a small trait).

### [ ] I-14 – Remove duplicated JSON/path helpers in `QuantityValueCommand`

**File:** `Definition/Import/QuantityValueCommand.php:65-89`

- `getPath()`/`getJson()` duplicate `AbstractStructureImportCommand:119-142` with slightly different behaviour: one
  throws `JsonException`, the other `InvalidArgumentException` and treats a valid JSON `null` as invalid.
- Neither checks `file_get_contents() === false`. Use `is_file()` + `is_readable()` instead of `file_exists()`, because
  a directory passes `file_exists()`.

### [ ] I-15 – Remove repeated dry-run/execute blocks

**File:** `DeleteUnusedLocaleDataCommand.php:82-127`

- The same "build SQL → if not dry run: print + execute, else print with prefix" block appears three times. Extract
  `runOrPrint(string $sql)`.
- `dryRunMessage()` is called inside a branch where dry run is already known to be true. Use `prefixDryRun()` there,
  or let `dryRunMessage()` handle both branches.

---

## D – Per-command logic

### [ ] I-16 – Same safety rules for all destructive commands

- Destructive commands handle confirmation, `--force` and `--dry-run` differently:
  - `ClassesRebuildCommand --delete-classes` asks for confirmation (good).
  - `MigrateElementsCommand` asks in `interact()` only.
  - `DeleteUnusedLocaleData` has dry-run but no confirmation.
  - `Document/CleanupCommand`, `DeleteClassificationStoreCommand` and `ThumbnailsFolderStructureCommand` have neither.
- Standardise: interactive confirmation (skipped by `--force` or `-n`), a `--dry-run` that lists the planned actions,
  and a final summary with counts.

### [ ] I-17 – `DeleteClassificationStoreCommand`: rely on FK cascades or the model API

**File:** `src/Command/DeleteClassificationStoreCommand.php:55-85`

- `classificationstore_relations` / `collectionrelations` and the per-class `object_classificationstore_data_*` /
  `groups_*` tables have `ON DELETE CASCADE` FKs to groups and keys (`InstallBundle/dump/install.sql:670-692`,
  `models/DataObject/Classificationstore/Dao.php:285-304`). Deleting groups and keys is therefore enough. The loops
  over `SHOW TABLES LIKE 'object_classificationstore_data_%'` (unescaped `_` wildcards) are redundant.
- Check whether `StoreConfig::delete()` (`models/DataObject/Classificationstore/StoreConfig.php:115`) can be extended to
  do the full cascade. The command should not hand-write SQL.
- `Cache::clearAll()` is heavy. Clearing the classification-store tags is enough.

### [ ] I-18 – Remove or deprecate legacy/migration commands and options

- `Document/MigrateElementsCommand` is a one-time migration for pimcore/pimcore#7384 (Pimcore 6/7 era).
- `Migrate/ThumbnailsFolderStructureCommand` is a one-time migration for the Pimcore 10 thumbnail folder structure.
- `LowQualityImagePreviewCommand`: the `--generator` option has been deprecated since 11.2 "for removal in 12", and the
  `pimcore:image:svg-preview` alias is legacy.
- Decide for this fork whether they stay. If they stay, mark them deprecated (`hidden: true` + deprecation message).

### [ ] I-19 – `WorkflowDumpCommand`: inject workflows instead of pulling from the kernel container

**File:** `src/Command/WorkflowDumpCommand.php:57-80`

- `$this->getApplication()->getKernel()->getContainer()->get('workflow.' . $name)` only works because `WorkflowPass`
  and `services_workflow.yaml` make these services public.
- Inject `#[AutowireLocator('workflow', indexAttribute: 'name')]` (as Symfony's own `workflow:dump` does) and the two
  dumpers through the constructor.
- Validate the `marking` places against `$definition->getPlaces()`.
- Optionally add `--dump-format=dot|puml|mermaid` on par with Symfony's command.

### [ ] I-20 – `CacheWarmingCommand` simplifications

**File:** `src/Command/CacheWarmingCommand.php:102-210`

- `getArrayOption()` reads properties dynamically (`$this->$property`). Pass the allowed list directly
  (`getArrayOption('types', self::VALID_TYPES, ...)`).
- The return type is `mixed`; it should be `?array`.
- `null !== $types` in `writeWarmingMessage()` is always true because the parameter is a typed `array`.
- `--classes` is not validated against existing class definitions. Validate it and print unknown names.
- The `maintenance-mode` option comes from `Pimcore\Console\Application`. Comment that so readers do not think it is
  undefined.

### [ ] I-21 – `MaintenanceModeCommand` UX

**File:** `src/Command/MaintenanceModeCommand.php:43-84`

- `--enable` and `--disable` are not mutually exclusive (both → disable). `--enable` is never read.
- The result is printed only with `-v`. Always print a one-line status, and add `--status`.
- `'command-line-dummy-session-id'` is a magic string. Make it a class constant (and note that
  `Application.php:88` uses `'cache-warming-dummy-session-id'` for the same purpose).
- `getOption('disable') ?? false` → `(bool) $input->getOption('disable')`.

### [ ] I-22 – `ThumbnailsImageCommand` logic

**File:** `src/Command/ThumbnailsImageCommand.php`

- `--system` is exclusive. With it, **only** the system preview is generated; without it, the system preview is
  dropped (`:159-164`). The description "create system thumbnails" does not say this. Add `--system-only`, or make
  `--system` additive.
- `fetchThumbnailConfigs()` starts with `[$thumbnailConfig]` and then adds the `default` media at resolution 1
  again, so the base thumbnail is processed twice (`:227`, `:245-248`).
- The `skip-medias` check happens after `clone` + `selectMedia()`. Check it first (`:231-238`).
- `$list->escapeLike()` here vs `Helper::escapeLike()` elsewhere. Pick one.
- `configure()` reads `Config::getAutoFormats()` (config I/O) at command construction. It runs on every
  `bin/console list` and completion call.
- Items are encoded as the string `"id~~~name"` for Parallelization. Use a small value object or a JSON-encoded tuple
  instead of the magic separator.

### [ ] I-23 – `GenerateChecksumCommand`: filter in SQL

**File:** `src/Command/Asset/GenerateChecksumCommand.php:105-111`

- `--missing-only` and the folder skip happen in PHP after the full asset is loaded. Move both into the condition
  (`type != 'folder'`, `customSettings NOT LIKE '%"checksum"%'` or a JSON function). Add a progress bar and a summary.

### [ ] I-24 – `Document/CleanupCommand` internals

**File:** `src/Command/Document/CleanupCommand.php`

- `STANDARD_DOCUMENT_ENUM_TYPES` and `PROTECTED_DOCUMENT_TYPES` are the same list. Keep one `private const array`.
- Read the ENUM values from `information_schema.COLUMNS.COLUMN_TYPE` instead of regex-parsing `SHOW COLUMNS`.
- `ALTER TABLE ... ENUM(:enums)` with bound array params only works because pdo_mysql emulates prepares. Build the
  quoted list explicitly with `$db->quote()`. DDL should not rely on driver emulation.
- `$output->writeln(sprintf('...%s', $ex))` dumps the full trace. Print the message and show the trace only with
  `-vvv`.
- With no arguments the command silently does nothing. Make `documentTypes` `REQUIRED | IS_ARRAY`.

### [ ] I-25 – `AbstractBundleCommand` tidy-up

**File:** `src/Command/Bundle/AbstractBundleCommand.php`

- `setupInstaller()` (`:113-121`) only wraps `getInstaller()`. Remove it or make it actually wire the output
  (see B-05).
- `getShortClassName()` is declared `?string` but never returns `null`. Use `new \ReflectionClass($c)->getShortName()`
  or `substr(strrchr(...))`.
- `handlePrerequisiteError()` is also used for non-prerequisite errors (B-05). Rename it or split it.

### [ ] I-26 – `MaintenanceCommand` / `StorageCommand` / `OptimizeImageThumbnailsCommand` small items

- `MaintenanceCommand::configure()` calls `$this->maintenanceExecutor->getTaskNames()` to build the help. That is fine
  with lazy commands, but it should move to `getHelp()` or an `#[AsCommand(help:)]` placeholder plus a `getHelp()`
  override so it is built only when shown.
- `StorageCommand::getStorageName()` is `public` for no reason. Make it `private`.
- `OptimizeImageThumbnailsCommand`: `clearstatcache()` has no effect on Flysystem `fileSize()`. Remove it. One failing
  optimisation aborts the whole run; add per-file try/catch, a summary and `--dry-run`. Use `listContents()->filter()`
  for files.

### [ ] I-27 – Internal generator commands

**Files:** `InternalModelDaoMappingGeneratorCommand.php`, `InternalUnicodeCldrLanguageTerritoryGeneratorCommand.php`,
`InternalMigrationHelpersCommand.php`

- `Asset::locateDaoClass($className)` is a static call through an unrelated class. Use `AbstractModel::locateDaoClass()`.
- `class_parents()` + `in_array()` → `is_subclass_of($className, AbstractModel::class)`.
- `usort` comparator → `fn ($a, $b) => $b['population'] <=> $a['population']`.
- `InternalMigrationHelpersCommand`: `$this->metadataStorage->__invoke($this->dependencyFactory)` → `($this->metadataStorage)($this->dependencyFactory)`.
  The unused `?string $name` ctor parameter can go.

### [ ] I-28 – `MysqlToolsCommand` / `RequirementsCheckCommand` output

- `MysqlToolsCommand`: replace the `--mode` option with a required argument (`optimize|warmup`) with suggested values,
  and show console progress (see B-21).
- `RequirementsCheckCommand`: add `--format=json` for CI. `display()`/`displayState()` can be `private`.

---

## E – Related code outside scope (affects commands)

### [ ] I-29 – `lib/Console` base classes and traits

- `Traits/Parallelization.php:37-43`: `exit(1)` when the lock is held. Throw or return `FAILURE` instead (see C-06,
  B-26).
- `Traits/ParallelizationBase.php` (fallback without webmozarts): `execute()` is `public` and always returns `0`,
  even if items failed.
- `Traits/DryRun.php`: `@var Input $input` cast. Use `InputInterface`.
- `AbstractCommand::writeError()` & co. print `ERROR:`-prefixed raw tags. Deprecate them in favour of `$this->io`
  (I-06).
- `Style/PimcoreStyle.php` stores `$input`/`$output` again although `SymfonyStyle` already keeps them privately. That
  is acceptable, but the accessors could be `readonly` promoted properties.

### [ ] I-30 – Tests for commands

- `tests/` has no `CommandTester`-based tests for any CoreBundle command.
- Add at least smoke tests for argument/option parsing, which would have caught B-04, B-07, B-22 and B-25. Add
  functional tests for the data-changing commands: C-02, C-05, B-12, `ClassesRebuildCommand`.

---

# Scope 2 – `DataCollector` / `PimcoreCoreBundle` (+ compiler passes, extension, related config)

IDs continue from I-30. Line numbers are from commit `7cb7841b76`. Grouped: **F** Symfony 7.4 / configuration,
**G** PHP 8.4 / code style, **H** logic / structure, **I** tests.

---

## F – Symfony 7.4 / configuration

### [ ] I-31 – `routing_dev.yaml`: import the PHP profiler routes (XML routing is deprecated in 7.4)

**File:** `config/routing_dev.yaml:1-7`

- The file imports `@WebProfilerBundle/Resources/config/routing/wdt.xml` and `profiler.xml`. Symfony 7.4 deprecates
  the XML configuration format for routing (`symfony/routing` CHANGELOG 7.4). Since 7.3 those XML files are only
  wrappers that import `wdt.php` / `profiler.php`. The XML files go away in 8.0.
- Switch to `wdt.php` / `profiler.php` (`type: php`).
- The file is not referenced anywhere in this repo (the project skeleton imports it). Document it in the upgrade notes
  or drop it in favour of the Symfony recipe `config/routes/web_profiler.yaml`.

### [ ] I-32 – Register Pimcore workflows like FrameworkBundle 7.4 does

**File:** `src/DependencyInjection/Compiler/WorkflowPass.php:178-191`

FrameworkBundle 7.4 (`FrameworkExtension::registerWorkflowConfiguration`) does several things that `WorkflowPass`
leaves out:

- Tag `workflow` (`name`, `metadata`, `definition_validators`, `definition_id`) plus `workflow.workflow` /
  `workflow.state_machine`. Without it, Pimcore workflows:
  - are missing from the **Workflow profiler panel** (`data_collector.workflow` uses `tagged_iterator('workflow')`);
  - are not decorated by `TraceableWorkflow` in debug (`WorkflowDebugPass`);
  - are not validated at compile time by `WorkflowValidatorPass`, so invalid definitions only fail at runtime;
  - are missing from Symfony's `workflow:dump` (`tagged_locator('workflow', 'name')`).
- `registerAliasForArgument($workflowId, WorkflowInterface::class, …)`, so `WorkflowInterface $fooWorkflow` /
  `#[Target('foo')]` autowiring works.
- Hidden service IDs (`.<id>.listener.audit_trail`, `.<id>.listener.guard`).
- Pimcore's `pimcore:workflow:dump` (I-19) could then use the same tagged locator.

### [ ] I-33 – Collector service registration: let autoconfiguration do the work

**Files:** `config/profiler.yaml:1-16`, `src/DataCollector/PimcoreDataCollector.php`,
`src/DependencyInjection/PimcoreCoreExtension.php:119`

- With `autoconfigure: true`, FrameworkBundle's `registerForAutoconfiguration(DataCollectorInterface::class)` adds a
  **second**, empty `data_collector` tag next to the explicit one. It works only because `ProfilerPass` reads
  `$attributes[0]`.
- Extend `Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector` (implements
  `TemplateAwareDataCollectorInterface`) and add `public static function getTemplate(): ?string`. Keep only
  `priority` / `id` in the tag, or use `#[AutoconfigureTag('data_collector', ['id' => 'pimcore', 'priority' => 500])]`.
- `profiler.yaml` is loaded in every environment. It is harmless today because the unused private service is removed,
  but it could be loaded only when `kernel.debug` is true or when `WebProfilerBundle` is registered.

### [ ] I-34 – Replace hand-written "collect tagged services" passes with native DI features

- `NavigationRendererPass` (whole file): replace with
  `!tagged_locator { tag: pimcore.navigation.renderer, index_by: alias }` on
  `pimcore.templating.navigation.renderer_locator` (`config/templating.yaml:24-27`). This also fixes the typo
  "navig**t**ion" (line 46) and the redundant `isset && !empty` (line 40).
- `RegisterImageOptimizersPass`: inject `!tagged_iterator pimcore.image.optimizer` into `Optimizer`. That also gives
  `priority` support (today the order is undefined).
- `SerializerPass`:
  - could be `!tagged_iterator { tag: pimcore.serializer.normalizer }` / `encoder`, keeping the "at least one" check
    only if it is really needed;
  - uses the FQCN string `'Pimcore\\Serializer\\Serializer'` instead of `::class`.
- `RegisterMaintenanceTaskPass`:
  - only reads `$tags[0]`, so extra tags are silently ignored;
  - the error message has a typo: "needs to a `type` attribute" (line 41);
  - consider an `#[AsMaintenanceTask(type: …)]` attribute via `registerAttributeForAutoconfiguration()`, like
    `#[AsAreabrick]`.
- `ProfilerAliasPass`: no internal consumer. Keep it as an autowiring convenience for apps, or replace it with
  `#[Autowire(service: 'profiler')]` at call sites.

---

## G – PHP 8.4 / code style

### [ ] I-35 – `PimcoreCoreBundle::build()`: make pass ordering explicit and documented

**File:** `src/PimcoreCoreBundle.php:52-71`

- All 17 passes use the default type and priority 0, so they depend on bundle registration order. This caused C-07,
  B-29 and B-31.
- Pass `PassConfig::TYPE_*` and a priority explicitly, with a one-line comment naming the Symfony pass it must run
  before or after:
  - `CachePoolPass` (32);
  - `LoggerChannelPass` (0, MonologBundle);
  - `ResolveInstanceofConditionalsPass` (100);
  - `WorkflowGuardListenerPass`, `ProfilerPass`, `RegisterListenersPass` (BEFORE_REMOVING).
- `getContainerExtension()` builds a new extension on each call. Cache it: `return $this->extension ??= new
  PimcoreCoreExtension();` (property is `?ExtensionInterface` from the base class). The override itself is needed
  because the alias is `pimcore`, not `pimcore_core`.
- Migrating to `AbstractBundle` (`configure()` / `loadExtension()`) is **not** recommended. It would mean moving the
  2 000-line `Configuration` and gains little.

### [ ] I-36 – `PimcoreDataCollector` modernisation

**File:** `src/DataCollector/PimcoreDataCollector.php`

- Mark the class `final` (it is `@internal`). Use `private readonly PimcoreContextResolver $contextResolver`.
  Fix the constructor brace layout (lines 33-37).
- Drop `implements ResetInterface` and its import. `DataCollectorInterface` already extends it.
- Getters: `?? null` with nullable return types (B-34). Consider keeping the data in typed form via
  `$this->data = [...]` built with `array_filter()`, or a small value object if more fields are added.
- `collect()` calls `getPimcoreContext($request)`. The resolver **guesses and writes** the context attribute on the
  request if it is missing. Read `$request->attributes->get(PimcoreContextResolver::ATTRIBUTE_PIMCORE_CONTEXT)` first
  so the profiler does not mutate request state.
- Optional, more useful data: site, document ID/path, editmode, output-cache hit/miss, maintenance mode.

### [ ] I-37 – Profiler template clean-up

**Files:** `templates/Profiler/data_collector.html.twig`, `templates/Profiler/key_value_table.html.twig`

- `{% import _self as macros %}` is unused (lines 4 and 48).
- The comment at 33-35 says `link: false` hides the panel, while the code passes `true`. The inline
  `style="padding-top: 3px"` belongs in the toolbar CSS.
- Shorten the revision in the toolbar (`collector.revision|slice(0, 8)`), show the full SHA in a `title`, and add a
  null fallback (B-34). Show `~` for a null context.
- `key_value_table.html.twig` carries `{% deprecated … removed in pimcore/pimcore 12 %}` and has no users in this repo.
  Remove it, since this fork has its own versioning.

### [ ] I-38 – Docblock / copy-paste errors and small code-style fixes in compiler passes

- `LongRunningHelperPass.php:26`: docblock says "Adds tagged navigation renderers" (copied from `NavigationRendererPass`).
- `LongRunningHelperPass.php:38-39`: string class names. Use `BufferHandler::class` / `FingersCrossedHandler::class`.
  The `getClass()` of a `ChildDefinition` is `null` and is skipped silently.
- `RoutingLoaderPass.php:25-33`: docblocks still talk about the "annotation loader" and `pimcore_pimcoreadmin_`, while
  the loader replaces `pimcore_admin_admin_` (`lib/Routing/Loader/AttributeRouteControllerLoader.php:34-36`).
- `ServiceControllersPass.php:25-31`: the docblock mentions `Symfony\…\Controller\Controller` (removed in Symfony 5).
  The second loop (lines 51-68) only finds **public** `AbstractController` subclasses without the
  `controller.service_arguments` tag. Services are private by default and autoconfiguration tags controllers, so this
  is mostly dead code. Simplify to the tag loop and resolve class parameters with `resolveValue()`.
- `MonologPsrLogMessageProcessorPass.php:37-38`:
  - the `@phpstan-ignore-next-line` for a long-fixed phpstan-symfony issue is stale;
  - `@internal` is missing.
- `MonologPublicLoggerPass.php:31`: `static fn (string $id): bool => str_starts_with(...)`.
- `AreabrickPass.php:81,144`: comment "handle bricks implementing ContainerAwareInterface". The code handles
  `setEditableRenderer()`.
- `AreabrickPass.php:115`: `PIMCORE_PROJECT_ROOT` constant. Use `%kernel.project_dir%`
  (`$container->getParameter('kernel.project_dir')`).
- `AreabrickPass.php:202`: `in_array()` without `strict: true`.
- `AreabrickPass.php:244`: `str_replace('Bundle', '', …)` removes every occurrence (`BundleManagerBundle` → `Manager`).
  Use `preg_replace('/Bundle$/', '', …)`.

---

## H – Logic / structure

### [ ] I-39 – Merge the "make service public" passes and guard them

**Files:** `HtmlSanitizerPass.php`, `TranslationSanitizerPass.php`, `MessageBusPublicPass.php`, `MonologPublicLoggerPass.php`

- Three passes are one-liners around `getDefinition($id)->setPublic(true)`. `getDefinition()` throws
  `ServiceNotFoundException` and breaks the container build if an app renames or removes the sanitizer or bus.
- Merge them into one `PublicServicesPass` with a list of IDs and `has()` / `findDefinition()` guards. That works for
  aliases too.
- Long term: replace the `Pimcore::getContainer()->get(...)` callers so these services can stay private.

### [ ] I-40 – `WorkflowPass` readability

**File:** `src/DependencyInjection/Compiler/WorkflowPass.php`

- `process()` is a 240-line loop body. Split it into `registerTransitions()`, `createMarkingStore()`,
  `registerSupportStrategy()`, `registerAuditTrail()`, `registerGuard()`.
- `$markingStoreDefinition` is not reset per iteration (lines 152-183). It always gets set today, but `isset()` on a
  variable left over from the previous workflow is fragile. Initialise it to `null` and use a `match` on
  `$markingStoreType` for the extra arguments.
- Lines 42-51 load `services_symfony_workflow.yaml` **inside a compiler pass**. Autoconfiguration has already run, so
  `_defaults.autoconfigure` in that file is ineffective. `symfony/workflow` is a hard dependency, and FrameworkBundle
  enables workflows when it is installed, so this branch only runs with `framework.workflows: false`. Document that
  or drop the fallback.
- Line 258 checks `Security::class` (SecurityBundle). FrameworkBundle checks `AuthenticationEvents::class`
  (security-core), which is the component that is actually needed.

### [ ] I-41 – `PimcoreCoreExtension` tidy-up

**File:** `src/DependencyInjection/PimcoreCoreExtension.php`

- `prepend()` only holds commented-out code (lines 266-273). Remove it and `PrependExtensionInterface`.
- The 23 `$loader->load()` calls (lines 106-129) could be a loop over a constant list.
- `Pimcore::disableShutdown()` (line 53) is a static side effect during the DI build, and
  `.container.dumper.inline_class_loader` (55-58) is a kernel concern. Both belong in `Kernel`.
- `pimcore.config` (line 85) exposes the whole config tree as a parameter (two `TODO`s). AreabrickPass is one consumer.
  Expose only the needed sub-keys.
- Domain validation (lines 89-99): `idn_to_ascii()` needs ext-intl and returns `false` for invalid input. Use
  `$domain` instead of re-reading `$config['general']['domain']`.
- `configurePasswordHashers()` uses positional `replaceArgument(1, …)`. Use a named argument.

---

## I – Tests

### [ ] I-42 – Tests for compiler passes and the collector

- There are no `ContainerBuilder`-based tests for any pass in `src/DependencyInjection/Compiler`.
- Minimum set:
  - `CacheFallbackPass` with a custom child pool and an alias (C-07, B-32);
  - `WorkflowPass` with two transitions with and without `customHtml` (B-28) and audit trail channel (B-29);
  - `AreabrickPass` with tag + attribute on the same service (B-30);
  - `PimcoreDataCollector` with null version data / after `reset()` (B-34).
