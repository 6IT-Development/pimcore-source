# CoreBundle – TODO 3: Improvements

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

---

# Scope 3 – `EventListener` (+ `config/event_listeners.yaml`)

IDs continue from I-42. Line numbers are from commit `43948f45d7` (code identical to `7cb7841b76`). Grouped:
**J** Symfony 7.4 / configuration, **K** PHP 8.4 / code style, **L** logic / performance, **M** tests.

---

## J – Symfony 7.4 / configuration

### [ ] I-43 – Move listener wiring from YAML to attributes

**File:** `config/event_listeners.yaml`

- `FullPageCacheListener` (lines 114-119): three hand-written `kernel.event_listener` tags. Use
  `#[AsEventListener(KernelEvents::REQUEST, priority: 120)]` etc. on the methods, or make it a subscriber like the
  others.
- `setLogger` calls + `monolog.logger` channel tags (`RoutingListener` → `redirect`, `ElementListener` → `init`,
  `PimcoreContextListener`, `BlockStateListener`, `ResponseExceptionListener`, `EditmodeListener`): inject
  `LoggerInterface` in the constructor with `#[WithMonologChannel('redirect')]` (monolog ≥ 3.5, MonologBundle ≥ 3.10)
  or `#[Autowire(service: 'monolog.logger.redirect')]`, and drop `LoggerAwareTrait`.
  - `BlockStateListener` implements `LoggerAwareInterface`, so autoconfiguration **already** adds `setLogger`. The YAML
    call is a duplicate.
  - `BlockStateListener` and `GlobalTemplateVariablesListener` never use the logger. Remove it.
- `HardlinkCanonicalListener` (46-48): the explicit `kernel.event_subscriber` tag duplicates autoconfiguration.
- `ResponseStackListener` (99-101): `$responseStack: '@Pimcore\Http\ResponseStack'` is what autowiring resolves
  anyway.
- `MaintenancePageListener` (121-123): the `loadTemplateFromResource` call → constructor argument
  `#[Autowire('%kernel.project_dir%/…')]`, or a `maintenance_page.template` config option.
- `WebDebugToolbarListener` (127-130): `#[Autowire(param: 'pimcore.web_profiler.toolbar.excluded_routes')]` and
  `#[Autowire(service: 'web_profiler.debug_toolbar', onInvalid: …)]`.
- `Pimcore\Cache\FullPage\SessionStatus` (110-112) is not a listener. Move it to `cache.yaml`.
- `TranslationDebugListener`: `PimcoreCoreExtension::configureTranslations()` removes the definition when the
  parameter is empty, so the `empty($this->parameterName)` check in the listener (line 52) is dead.

### [ ] I-44 – Stop pulling listeners out of the container as public services

**Files:** `config/event_listeners.yaml:75-76, 114-115`, `lib/Tool/Frontend.php:103`,
`models/Document/Editable/Video.php:448`, `models/Document/Editable/Scheduledblock.php:112`

- `FullPageCacheListener` is `public: true` only so that models can call
  `Pimcore::getContainer()->get(FullPageCacheListener::class)->disable()` / `setLifetime()`.
- Extract a small `FullPageCacheState` (request-attribute based, see C-08) or a `FullPageCacheControl` service.
  Inject it where possible, and let the listener read it. The listener can then be private and `final`.
- `WorkflowManagementListener` `public: true` (see B-42).

### [ ] I-45 – Document and centralise listener priorities; fix stale comments

- Put the priority table (TODO-1 Scope 3 notes) into a `ListenerPriority` constants class, or at least into the
  class docblocks, with the Symfony listener each one must run before or after.
- Stale or wrong comments:
  - `LocaleListener.php:39-40`: "need to be after ElementListener". `ElementListener` listens on `kernel.controller`,
    not `kernel.request`.
  - `MaintenancePageListener.php:48`: "run after AdminSessionBagListener" (AdminBundle class, not referenced here).
    The relevant neighbour is `SessionListener` (128).
  - `PimcoreContextListener.php:51-52`: "TODO check if this is early enough". Listeners at 512 / 510 / 126 / 120
    already call the resolver before it, so they rely on guessing.
  - `DocumentRendererListener.php:25`: the docblock is copied from `BlockStateListener` ("Handles block state…").
  - `ElementListener.php:149-151, 165, 228`: old TODOs from the Pimcore 4→5 port.
  - `FullPageCacheListener.php:361-363`: the comment says the `"output"` tag, the code adds `output_inline`.
  - `RoutingListener.php:87`: "puroposes".
- `WebDebugToolbarListener::onKernelResponse()` is subscribed to **`kernel.request`** (line 51). Rename it to
  `onKernelRequest()`.

### [ ] I-46 – Decouple CoreBundle listeners from AdminBundle, StaticRoutesBundle and FOSJsRouting

- `EditmodeListener` hard-codes ~40 `/bundles/pimcoreadmin/...` asset paths and generates
  `pimcore_admin_misc_scriptproxy`, `pimcore_admin_misc_jsontranslationssystem` and `fos_js_routing_js`. Without
  AdminBundle / FOSJsRoutingBundle, `RouteNotFoundException` makes every editmode request a 500. Move the asset list
  and route generation into AdminBundle, behind an interface such as `EditmodeAssetProviderInterface`, and keep only
  the injection mechanism here.
- `ElementListener.php:73`: special case for the `fos_js_routing_js` route.
- `HardlinkCanonicalListener.php:66`: `class_exists(Staticroute::class)` + static `Staticroute::getCurrentRoute()`.
  Check a request attribute (`_route` / `pimcore_request_source`) instead.

---

## K – PHP 8.4 / code style

### [ ] I-47 – Modernise the listener classes

- `Frontend/ElementListener.php` is the only file without `declare(strict_types=1);`.
- All listeners are `@internal` but none is `final`. Make them `final` (except where tests or subclasses really need
  them) and use `private readonly` promoted properties instead of `protected` (`PimcoreContextListener`,
  `ResponseExceptionListener`, `WebDebugToolbarListener`, `EditmodeListener`, `ElementListener`,
  `RoutingListener`, …).
- Add `#[\Override]` to `getSubscribedEvents()`.
- PHP 8.4 property hooks / asymmetric visibility for flag-style state:
  - `FullPageCacheListener`: `public private(set) bool $enabled`, `?int $lifetime`; `disable()` / `enable()` always
    return `true` (useless `bool` return);
  - `MaintenancePageListener`: `public private(set) ?string $templateCode` (replaces `setTemplateCode` /
    `getTemplateCode`).
- `urldecode($request->getPathInfo())` (`RoutingListener:82`, `DocumentFallbackListener:106`,
  `ResponseExceptionListener:143`, `StaticPageGeneratorListener:96`) decodes `+` to a space. Path segments need
  `rawurldecode()`.
- Global constants: `PIMCORE_PROJECT_ROOT` (`MaintenancePageListener:65`), `PIMCORE_PATH` (`LocaleListener:63`) and
  `PIMCORE_WEB_ROOT` (`EditmodeListener:224`). Inject `%kernel.project_dir%` / a public-dir parameter.
- `ContentTemplateListener:77`: `interface_exists('Symfony\\Component\\Form\\FormInterface')`. Use
  `FormInterface::class` (already imported).
- `ResponseHeaderListener:69`: `$this->responseHeaderResolver::ATTRIBUTE_RESPONSE_HEADER`. Use
  `ResponseHeaderResolver::ATTRIBUTE_RESPONSE_HEADER`.
- `StaticPageGeneratorListener`: `$reponse` (line 111). `catch (Exception $e)` at line 169 shadows the
  `DocumentEvent $e` parameter.
- `PreviewRequestTrait`: read `$request->headers->get('X-Purpose')` / `Purpose` instead of
  `$request->server->get('HTTP_…')`.
- Loose comparisons: `$context == PimcoreContextResolver::CONTEXT_ADMIN` (`PimcoreContextListener:85`),
  `getStatusCode() == 200` (`FullPageCacheListener:323`), `!=` host compare (`RoutingListener:160`), `in_array()`
  without `strict` (`MaintenancePageListener:111`, `StaticPageGeneratorListener:182`).
- `ResponseExceptionListener::determineErrorPath()` has `@throws Exception` and an undefined-then-maybe-set `$locale`
  variable. Initialise it to `null`.

### [ ] I-48 – Remove dead code

- `Traits/PreviewRequestTrait.php`: no user in the repo.
- `Traits/ResponseInjectionTrait::injectBeforeHeadEnd()`: no caller. `MaintenancePageListener` uses the trait but none
  of its methods. Remove the `use`.
- `WorkflowManagementListener::enrichNotes()` and `::extractElementFromEvent()` (lines 104-134): private and never
  called (left over from the AdminBundle split). Also the unused imports `Exception`, `GenericEvent`, `DataObject`.
- `PimcoreContextListener`: the constructor argument `$requestStack` is unused. The `else` log branch (69-73) is dead
  because the guesser always returns a default.
- `Traits/StaticPageContextAwareTrait`: inconsistent with `PimcoreContextAwareTrait`. It has a nullable property but
  no null guard, so it fails with `Error` instead of a clear message when not wired. Pick one style; a
  `#[Required]` setter with a non-nullable property is enough. Also remove the empty docblock lines 38-41.
- `EditmodeListener::onKernelRequest()` (72-87): "TODO is this needed?". It warms the resolver cache and can go if
  nothing relies on the side effect.

---

## L – Logic / performance

### [ ] I-49 – Work done on every request

- `MaintenancePageListener` (REQUEST 126, **before** the full page cache):
  - `MaintenanceModeHelper::isActive()` connects to the DB and queries `TmpStore`;
  - it also `stat`s / `include`s the legacy `maintenance.php`;
  - so even a full-page-cache hit costs a DB round-trip.
  - Fix: cache the flag in `cache.app` / an APCu key with a short TTL, invalidated by `activate()` / `deactivate()`,
    or switch to a file flag that is checked first.
- `StaticPageGeneratorListener::onKernelRequest()`:
  - one `fileExists()` on the `document_static` Flysystem storage (possibly S3) for **every** GET HTML request when
    the router is enabled;
  - there is no Pimcore-context check, so admin paths are probed too;
  - `@preg_match` hides invalid `route_pattern`s.
- `InternalWysiwygHtmlAttributeFilterListener`:
  - runs a `preg_replace()` over the **complete HTML** of every frontend response to strip
    `pimcore_id/type/disable_thumbnail`;
  - the WYSIWYG editable could strip them at render time instead;
  - `isHtmlResponse()` itself does a `str_contains($content, '<html')` scan.
- `EditmodeListener` reads and concatenates ~35 JS files from disk on **every** editmode request (line 224) just to
  compute the minimised-script path. Compute it once per revision (cache key `Version::getRevision()`).
- `LocaleListener` `include`s the CLDR territory mapping file on every locale change (line 63). Load it once (static
  property or compiled PHP constant).

### [ ] I-50 – `FullPageCacheListener` structure

- `onKernelRequest()` is a 160-line method of `disable(); return;` blocks. Split it into `getDisableReason(Request):
  ?string` (pure) plus key building plus lookup. That makes the rules testable and keeps the reason in one place.
- Expire headers: `$response->setPublic(); $response->setMaxAge($lifetime); $response->setExpires(new
  DateTimeImmutable("+{$lifetime} seconds"));` instead of building headers by hand (lines 325-333).
- Parse `exclude_patterns` / `exclude_cookie` once. Better: make them proper `array` config nodes validated at compile
  time (B-44).
- `Logger::error()` static calls → injected logger.
- Return early instead of `if ($conf = $this->config['full_page_cache']) { … } else { disable }` (lines 172-222).

### [ ] I-51 – `ResponseExceptionListener` logic

- It mixes `$this->siteResolver->isSiteRequest($request)` (line 140) with static `Site::isSiteRequest()` /
  `Site::getCurrentSite()` (163-167). Use the resolver throughout. This also removes a C-08 dependency.
- Locale fallback: `$request->getLanguages()` yields `de_AT`, `en_US`. A config keyed by `de` / `en` never matches,
  so add a primary-language fallback (`Locale::getPrimaryLanguage()`).
- Logging: non-HTTP exceptions are logged with `(string)$exception` as the message (no context, no channel). Use
  `$this->logger->error($e->getMessage(), ['exception' => $e])`. Symfony's `ErrorListener::logKernelException`
  (also priority 0) logs the same exception, so check for duplicates.
- Consider rendering error pages in debug mode too when `?pimcore_show_error_page=1` is set, to make them testable.

### [ ] I-52 – Messenger / console workers: reset all static frontend state, flush translation entries

**Files:** `src/EventListener/MessengerClearRuntimeCacheListener.php`, `src/EventListener/DumpTranslationEntriesListener.php`

- On `WorkerMessageReceivedEvent`, only `RuntimeCache::clear()` runs. Static flags set by one handler stay set for
  the next message:
  - `Pimcore::setAdminMode()`;
  - `Document::setHideUnpublished()` / `DataObject::setHideUnpublished()`;
  - `DataObject::setGetInheritedValues()`, `Localizedfield::setGetFallbackValues()`;
  - `Site::$currentSite`.
  Reset them to the CLI defaults, or use `kernel.reset` (C-08), which Messenger already triggers between messages via
  `ResetServicesListener`.
- `DumpTranslationEntriesListener` only dumps on `kernel.terminate` / `console.terminate`. A Messenger worker that
  runs for hours keeps collecting missing-translation entries in memory and writes them only on exit (or never, if it
  is killed). Also dump on `WorkerMessageHandledEvent`, or every N messages.

### [ ] I-53 – Response headers

- `PimcoreHeaderListener`: `X-Powered-By: pimcore` on every main response reveals the platform. Make it configurable
  (default off) or remove it.
- `EditmodeListener:122`: `X-Frame-Options: SAMEORIGIN` is legacy. Also set `Content-Security-Policy:
  frame-ancestors 'self'` (merge with an existing CSP).
- `MaintenancePageListener`: add `Retry-After` and `Cache-Control: no-store` to the 503.
- `StaticPageGeneratorListener`: serve with `Last-Modified` (it already has the timestamp) and an `ETag`, and answer
  `304` via `$response->isNotModified($request)`. Today it uses the custom `X-Pimcore-Static-Page-Last-Modified`.

---

## M – Tests

### [ ] I-54 – Tests for listeners

There are no tests for `src/EventListener`. Minimum set:
- **Two-requests-one-kernel** functional test (C-08): FPC hit followed by a POST and a miss; an areabrick response
  followed by a normal page; a site host followed by the main host.
- `StaticPageGeneratorListener`: 500 / session / `Set-Cookie` responses must not be written (C-09); rename must remove
  the old file (B-38).
- `RoutingListener`: `/app.php/%5Cexample.com` must not redirect off-site (C-10); mixed-case domain must not loop
  (B-41).
- `ElementListener`: `?v=` with a foreign / object version gives 404 (B-35).
- `FullPageCacheListener::responseCanBeCached()` with `Set-Cookie` / `Vary` (B-36).
- `ContentTemplateListener` with `vars`, `block`, `null` result and a duplicate argument/result key (B-40).
- `ResponseExceptionListener` with a non-`Page` root and a failing fragment in the error page layout (B-37).

---

# Scope 4 – `DependencyInjection` (+ `config/pimcore/*.yaml`, extension-loaded `config/*.yaml`)

IDs continue from I-54. Line numbers are from commit `402145a525`. Grouped: **N** Symfony 7.4 / configuration,
**O** PHP 8.4 / code style, **P** logic / structure, **Q** tests. Items that only complement Scope 2 (I-35, I-38, I-41)
say so.

---

## N – Symfony 7.4 / configuration

### [ ] I-55 – Give config nodes real types and ranges (`scalarNode` used for numbers, booleans and enums)

**File:** `src/DependencyInjection/Configuration.php`

- Numbers as `scalarNode`, so `"abc"` passes and consumers cast with `(int)`:
  - `applicationlog.archive_treshold` (342, also misspelt; consumer `LogArchiveTask.php:56`). Add `archive_threshold`
    and deprecate the old key with `setDeprecated()` + a `beforeNormalization` copy.
  - `applicationlog.delete_archive_threshold` (350, default is the **string** `'6'`);
    `applicationlog.mail_notification.filter_priority` (333, `integerNode()->min(0)->max(7)`).
  - `assets|objects|documents.versions.days/steps` (614-619, 743-744, 877-882): `integerNode()->min(0)->defaultNull()`.
  - `full_page_cache.lifetime` (1250); `httpclient.proxy_port` (300).
- Booleans as `scalarNode`: `dependency.enabled` (2025, `defaultValue(true)`), `email.usespecific` (1304, see I-57),
  `custom_views.definitions.*.showroot` (1948).
- Enums as `scalarNode`: `documents.allow_trailing_slash` (915, only `'no'` is checked in
  `DocumentRouteHandler.php:282`), `httpclient.adapter` (293, `Socket`/`Proxy`).
- Add ranges: `tree_paging_limit` ×3 (`min(1)`), `auto_save_interval` ×2 and the `maintenance.housekeeping.*` seconds
  (`min(0)`), `assets.thumbnails.max_scaling_factor` (`min(1)`), `assets.image.max_pixels` (`min(1)`).
- `security.password.algorithm` (1127-1137): `PASSWORD_DEFAULT` and `PASSWORD_BCRYPT` are both `'2y'`, and the
  `defined()` checks are dead because the constants are imported with `use const`. Build the list once:
  `array_values(array_unique([PASSWORD_BCRYPT, …defined argon]))`.

### [ ] I-56 – Make the tree self-contained: `addDefaultsIfNotSet()` where the extension/consumers read unguarded

- Without `addDefaultsIfNotSet()` the tree depends on `config/pimcore/default.yaml` to supply the structure. A project
  that loads CoreBundle without that file gets "Undefined array key" from the extension:
  - `assets.metadata.class_definitions(.data)` (697-711), read at `PimcoreCoreExtension.php:182`;
  - `templating_engine.twig.sandbox_security_policy` (1983-1997), read at `PimcoreCoreExtension.php:78-80`.
- Consumers work around missing defaults with `?? null` / `?? false`: `objects.versions` (741), `documents.versions`
  (875), `documents.error_pages` (897), `applicationlog.mail_notification` (321). Compare `models/Asset.php:864-868`,
  `VersionsCleanupTask.php:67-69`, `ResponseExceptionListener.php:160-161`, `LogMailMaintenanceTask.php:39-55`.
  `assets.versions` already has defaults, so the three `versions` nodes are inconsistent.
- After adding defaults, remove the `??` fallbacks and the duplicate defaults from `default.yaml`
  (e.g. `gotenberg.base_url`, `dependency.enabled`, which repeat the tree defaults).

### [ ] I-57 – Remove dead / deprecated config and stale texts

- Dead keys (no consumer in core or `admin-ui-classic-bundle`):
  - `flags` (71-74);
  - `email.usespecific` (1304);
  - `security.factory_type` and `security.encoder_factories` (1147-1168). The encoder API was removed in Symfony 6, and
    only `password_hasher_factories` is wired (`PimcoreCoreExtension.php:245-249`). `config/pimcore/security.yaml`
    still sets `factory_type: password_hasher`.
- Deprecated since 11.2: `general.language` (217-220), `chromium` (2033-2050, consumer `lib/Image/HtmlToImage.php`).
  Schedule the removal together with the Chromium adapter.
- `config/pimcore/default.yaml:279,284`: legacy prefixes `\Object_Class_Data_` / `\Object_Class_Layout_` (Pimcore ≤ 4
  class names).
- `config/paginator.yaml` is **never loaded** (not in `PimcoreCoreExtension::loadInternal()`, not imported anywhere). Its
  values equal the KnpPaginator defaults. Delete it.
- Stale texts:
  - `initial_markings` info "Symfony 4.3+ only" (1459);
  - `marking_store` info "Take a look at @TODO" (1387);
  - `debugging` link to 6.x docs (`config/pimcore/dev.yaml:41`);
  - `services.yaml:10-15`: "Only use with Symfony ver 5.3 or higher", "UserAwareEncoderFactory";
  - `PimcoreCoreExtension::prepend()` (see I-41).
- `ConfigurationHelper::getSymfonyConfigFiles()` option `relativePath` has no caller.

### [ ] I-58 – `ignoreExtraKeys()` hides typos in six sections

**File:** `src/DependencyInjection/Configuration.php:723, 840, 1246, 1841, 1886, 1935`

- `objects`, `documents`, `full_page_cache`, `properties`, `perspectives`, `custom_views` use `ignoreExtraKeys()`
  (default `remove = true`). A typo such as `documents.allow_trailing_slahs` or `full_page_cache.exclude_cookies` is
  **silently dropped** with no "Unrecognized option" error.
- Find out which keys this protects. Probably keys written by the admin UI or legacy bundles. Declare them explicitly or
  move them under a dedicated `variableNode`, then remove `ignoreExtraKeys()`.

### [ ] I-59 – Service YAML clean-up (extension-loaded `config/*.yaml`)

- `aliases.yaml`:
  - `_defaults: public: true` makes every alias public;
  - `TranslatorInterface → Pimcore\Translation\Translator` duplicates FrameworkBundle's `TranslatorInterface →
    translator`, which is already decorated (`l10n.yaml`);
  - `Doctrine\Persistence\ConnectionRegistry` is provided by DoctrineBundle;
  - `SessionListener: '@session_listener'` has no autowiring user;
  - the three 11.2-deprecated aliases are candidates for removal.
- `templating_twig.yaml:59-65`: the **abstract** classes `Placeholder\AbstractExtension` and `CacheBusterAware`, and the
  `ArrayObject` subclass `Placeholder\Container`, are registered as services. Autoconfiguration tags them
  `twig.runtime`. They only work because nobody fetches them. Remove the definitions (or `exclude` them).
  `class_resolvers.yaml`: the generic `Pimcore\Resolver\ClassResolver: ~` cannot be autowired (`array $map`) and is
  unused.
- Placeholder arguments `'@?'` (`services.yaml:21`, `serializer.yaml:19`) are optional references to an empty ID,
  replaced later by the extension/pass. Use `!abstract 'set by PimcoreCoreExtension'` so a missing replacement fails
  loudly. Use `'@.inner'` instead of `'@Pimcore\Mail\Mailer.inner'` / `'@Pimcore\Translation\Translator.inner'`.
- `message_handler.yaml`: no `_defaults`, so every handler is wired by hand. Use `#[AsMessageHandler]` with
  autowire/autoconfigure and drop the file.
- `services_workflow.yaml:43-49`: a second `AuthorizationChecker` instance. Inject `@security.authorization_checker`.
- `routing.yaml:28` imports FOSJsRouting's `routing-sf4.xml`. XML route loading is deprecated in Symfony 7.4 (see
  I-31). The bundle ships `routing.php`; use it. Also see I-46 (FOSJsRouting coupling).
- Leading backslashes in class names: `services.yaml` (`class: \GuzzleHttp\Client`), `maintenance.yaml`
  (`messengerMessageClass: '\Pimcore\Messenger\ScheduledTaskMessage'`), every class map in `default.yaml`.
- `maintenance.yaml`: task `type` names mix styles (`scheduledtasks`, `cleanupBrickTables`,
  `VersionsCleanupStackTraceDb`, `low_quality_image_preview`). Settle on snake_case with BC aliases, or add the
  `#[AsMaintenanceTask]` attribute (I-34).
- `config/pimcore/default.yaml`:
  - `twig.globals.container` (remove, C-12);
  - `doctrine.dbal…logging/profiling` keys: check them against the installed DoctrineBundle (`profiling` is
    the 2.x option, `logging` is middleware-based with DBAL 4);
  - the `redirect` Monolog handler listens on a channel that is not in `monolog.channels`. It is created only by a
    `monolog.logger` tag elsewhere, so declare it.

---

## O – PHP 8.4 / code style

### [ ] I-60 – `Configuration.php` readability (2 051 lines, one flat fluent chain per section)

- Every chain is written with zero nesting indentation (`->children()` … `->end()` all at the same level). Mistakes are
  only visible by counting `end()`s. Line 1829 calls `->addDefaultsIfNotSet()` on the root node a second time. Line
  1897-1902 opens `children()` twice in `perspectives.dashboards`. Re-indent per nesting level (php-cs-fixer
  `method_chaining_indentation` cannot do it. Use manual blocks or one private method per subtree).
- Split per section: `addWorkflowNode()` alone is 520 lines. Extract `addWorkflowPlacesNode()`,
  `addWorkflowTransitionsNode()`, a shared `addNotesNode(bool $globalAction)` (fixes the drift in B-57) and a shared
  `addThumbnailDefinitionsNode()` (image and video `definitions/items/medias` are near-identical, 433-477 / 534-571).
- `prototype('scalar'|'array'|'variable')` → `scalarPrototype()` / `arrayPrototype()` / `variablePrototype()`. Also
  fix `arrayProtoType()` / `protoType()` casing (465, 560, 1957).
- Closures: `function ($v) { return (bool)$v; }` ×16 → removed (B-54); remaining closures → `static fn`.
- No-op closures: `fallback_languages` (239-245) and `documents.error_pages.localized` (903-909) have
  `beforeNormalization()->ifArray()->then(fn ($v) => $v)`. Delete them.
- `routing.static.locale_params` (1093-1095): `defaultValue([])` on the **prototype**. Put it on the array node or drop
  it (prototyped arrays already default to `[]`). Typo "explicitely".
- `Config/Processor/PlaceholderProcessor`: mark it `final` and stateless. After B-56 it is only needed by the workflow
  node. Fix the "an an" docblock typo. The unit test lives in `tests/Unit/Tool/` and should move next to the
  DependencyInjection tests.

### [ ] I-61 – `ConfigurationHelper`, `PimcoreCoreExtension` and compiler passes: smaller items (complements I-38, I-41)

- `ConfigurationHelper` (used by CustomReports/StaticRoutes bundles, so it is public API):
  - document it as `@api` or move it to `lib/Config`;
  - `ConfigurationHelper::` self-calls → `self::`;
  - `addConfigLocationTargetNode()` duplicates the whole `write_target` block in both branches (44-101). Build it once
    and append `read_target` when requested;
  - `in_array(…, true)`;
  - `defaultValue('symfony-config')` → `LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG`;
  - `getSymfonyConfigFiles()`: the `$dirs` array and `empty($dirs)` are pointless; `->name(['*.yml', '*.yaml'])`.
- `PimcoreCoreExtension`:
  - `setPublic(false)` on new `Definition`s is the default (155, 194, 204);
  - `$config['general']['domain'] ?? ''`: the key always exists (89);
  - re-reads `$config['general']['domain']` (98);
  - `flattenConfigurationForClassResolver()` → `array_filter(array_map(fn (array $c) => $c['class'] ?? null, $configuration['map'] ?? []))`;
  - the one-line `configureRouting()` / `configureClassResolvers()` wrappers can be inlined;
  - `array_map(fn …)` → `static fn` (248).
  - Document that `ImplementationLoader` resolves loaders **last-added first** (`array_reverse`), which is why the class
    map loader is added after the prefix loader (189-211) and the model class-map loader after `FallbackBuilder`.
- `pimcore.config` (85) also puts **secrets** into the compiled container's parameters (`encryption.secret`,
  `httpclient.proxy_pass`, `email.*`). They are visible through `debug:container --parameters` and in the
  `SystemEvents::SAVE_ACTION_SYSTEM_SETTINGS` event (`lib/Config.php:141`). Same point as the I-41 "expose only needed
  sub-keys" item, with a security angle. `pimcore.encryption.secret` (64-66) silently prefers an existing parameter over
  the config value. Document this or log when both are set.
- `MonologPsrLogMessageProcessorPass` exists only for `ApplicationLoggerBundle/config/services.yaml:44`. MonologBundle
  defines `monolog.processor.psr_log_message` only when a handler uses `process_psr_3_messages`. Move the pass (or a
  private `PsrLogMessageProcessor` definition) into ApplicationLoggerBundle.

---

## P – Logic / structure

### [ ] I-62 – Workflow config: missing Symfony options and validation

**Files:** `src/DependencyInjection/Configuration.php:1312-1831`, `src/DependencyInjection/Compiler/WorkflowPass.php:187`

- `WorkflowPass` passes `$workflowConfig['events_to_dispatch'] ?? null` to the workflow, but the tree has no
  `events_to_dispatch` node. Setting it fails with "Unrecognized option", so the code path is dead. Add the node (list of
  `WorkflowEvents::*` names, `null` = all), as FrameworkBundle does.
- Validate references at config time:
  - `transitions.*.from/to`, `globalActions.*.to` and `initial_markings` must name an existing place;
  - `marking_store.type` `single_state`/`multiple_state` must not be combined with `data_object_*` arguments.
  Today these errors surface at runtime (or via `WorkflowValidatorPass` once I-32 is done).
- `supports` + `support_strategy` validation: `$v['supports'] && isset(...)` relies on `supports` defaulting to `[]`.
  Make it explicit (`!empty()`).
- `places` permissions: `modify` is documented as a shorthand for five flags but is expanded at runtime (`lib/Workflow/Place/PlaceConfig.php:125`). Consider
  normalising it in the tree.

### [ ] I-63 – One loader for `config_location` YAML imports

**Files:** `lib/Kernel.php:87-131`, `lib/Config/LocationAwareConfigRepository.php:289-329`,
`src/DependencyInjection/Configuration.php:137-160`

- The Kernel closure and `LocationAwareConfigRepository::loadSymfonyConfigFiles()` implement the same read/write-target
  decision twice, and they already differ:
  - the Kernel checks `options.directory !== null` and `rtrim`s; the repository does neither;
  - the Kernel uses `$loader->import($dir)` (sorted, recursive); the repository uses an unsorted Finder (B-55).
- The Kernel hard-codes the 10 config keys that `Configuration::getConfigTreeBuilder()` also lists. Expose them as a
  constant on `Configuration` (or `LocationAwareConfigRepository`) and loop over it in both places.
- Move the decision into one static helper (`LocationAwareConfigRepository::resolveSymfonyConfigDirectory(array
  $containerConfig, string $key): ?string`) used by the Kernel and by the bundles.

---

## Q – Tests

### [ ] I-64 – Tests for the configuration tree and the helper

- There are no tests for `Configuration`, `ConfigurationHelper` or `PimcoreCoreExtension` (only
  `tests/Unit/Tool/PlaceholderProcessorTest.php`). Minimum set (plain `Processor` / `ContainerBuilder`, no kernel):
  - boolean nodes with `%env(bool:X)%` placeholders and `"false"` strings (B-54);
  - `getConfigNodeFromSymfonyTree()` with `config_location` split across two configs (B-55);
  - two workflows with and without `placeholders`, `places` before `placeholders` (B-56);
  - a workflow without `label`, a global action with `fieldType: numeric` (B-57);
  - `addConfigLocationTargetNode()` with an absolute folder outside `PIMCORE_PROJECT_ROOT` (C-11);
  - `SecurityPolicy` denies method/property access on non-allowlisted objects (C-12);
  - `PimcoreCoreExtension::load()` with an empty config: compiles without notices (I-56).
