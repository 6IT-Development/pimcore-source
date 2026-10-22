# GenericExecutionEngineBundle – TODO 3: Improvements

Scope: `bundles/GenericExecutionEngineBundle/src/**` plus the dependent config (`config/**`), translations, the GEE docs
(`doc/19_Development_Tools_and_Details/08_Generic_Execution_Engine/**`) and the root `composer.json`
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-2-BUGS.md](TODO-2-BUGS.md)

Code quality, readability, performance, modernisation (Symfony 7.4 / PHP 8.4 idioms) and logical clean-ups. Nothing
here is broken on its own. IDs are stable – refer to them as `I-xx` in later sessions. Line numbers are from commit
`bad4feee5b`.

---

## Symfony 7.4 modernisation

### [ ] I-01 – Simplify bundle / extension
**Files:** `src/PimcoreGenericExecutionEngineBundle.php`, `src/DependencyInjection/PimcoreGenericExecutionEngineExtension.php`
- `AbstractPimcoreBundle` extends `Bundle`, so a switch to `AbstractBundle` (config + services in one class, no
  `getPath()`) needs a core variant (same situation as GlossaryBundle / CustomReportsBundle I-01).
- `getInstaller()` (`:33-37`): the inline `/** @var InstallerInterface|null */` contradicts the non-null return type –
  inject/locate the installer properly.
- Extension: `?? []` / `?? ErrorHandlingMode::CONTINUE_ON_ERROR->value` (`:42`, `:47`) are dead – the config tree
  guarantees both values. Prefer `#[Autowire('%pimcore_generic_execution_engine.error_handling%')]` parameters over
  `setArgument()` on definitions looked up by interface id. Add `#[\Override]`.

### [ ] I-02 – Modernise the config tree
**File:** `src/DependencyInjection/Configuration.php`
- The builder chain lost its nesting indentation in `14a090c5ae` ("pre ai upg") – `:33-60` is now flat and very hard to
  review; restore indentation by nesting level.
- `prototype('array')` → `arrayPrototype()`; key handling (B-11).
- `enumNode()->values([...])` → `->enumFqcn(ErrorHandlingMode::class)` (available since Symfony 7.3) or
  `array_column(ErrorHandlingMode::cases(), 'value')`; the values list is duplicated (`:35`, `:47-52`).
- `default.yaml` duplicates the tree defaults (`error_handling`, `translations_domain: admin`) and causes B-09 – keep only
  the `default` context with its domain.
- Add `->info()` to `execution_context` so `config:dump-reference pimcore_generic_execution_engine` documents it.

### [ ] I-03 – Clean up `services.yaml`, use attributes
**File:** `config/services.yaml`
- `_instanceof` for `GenericExecutionEngineMessageInterface` tagged as `messenger.message_handler` (`:12-17`) is wrong
  (messages are not handlers) and dead (no service in this file implements it, there is no `resource:` import). Remove
  it or retarget to `AbstractAutomationActionHandler` (`messenger.message_handler` with `bus: generic.execution.engine.bus`)
  – that would also spare users the manual bus tag (docs never mention it).
- `messenger.middleware` tags (`:83-89`) do not exist in Symfony – middleware are referenced by id in the bus config.
  The `CollectGarbageMiddleware` definition belongs to core (`lib/Messenger/Middleware`), not to this bundle.
- `@=service('kernel').getBundle(...)` expression (`:25`) → plain argument / factory, avoids the ExpressionLanguage
  dependency at runtime.
- `JobRunStatusChangeSubscriber` → `#[AsEntityListener(event: Events::preUpdate, entity: JobRun::class)]` (or
  `#[AsDoctrineListener]`), `JobExecutionSubscriber` → `#[AsEventListener]`.
- `$executionEngineBus: '@generic.execution.engine.bus'` → `#[Target('generic.execution.engine.bus')]`;
  `$environment: '%kernel.environment%'` → `#[Autowire('%kernel.debug%')] bool $debug` (stack traces should follow the
  debug flag, not the env name – `test`/`staging` envs).
- Logger injection relies on the argument name `$genericExecutionEngineLogger` (monolog channel autowiring alias) –
  make it explicit with `#[Target('generic_execution_engine')]` / `#[WithMonologChannel]`.
- Remove the boilerplate comments; register services via `resource:` with explicit aliases for the interfaces.

### [ ] I-04 – Depend on `TranslatorInterface`, not `Pimcore\Translation\Translator`
**Files:** `src/Agent/JobExecutionAgent.php:32`, `:58`, `src/Repository/JobRunRepository.php:33`, `:45`
Only `trans()` is used; the concrete class prevents decoration/mocking. Other classes of the bundle already use
`Symfony\Contracts\Translation\TranslatorInterface`.

---

## Domain model / persistence

### [ ] I-05 – Entity modernisation
**Files:** `src/Entity/JobRun.php`, `src/Entity/JobRunErrorLog.php`
- Use `Types::*` constants, explicit `length` matching the installer (B-16), indexes (`ownerId,state`,
  `executionContext`, error log `jobRunId,stepNumber`), `#[ORM\ManyToOne]`/FK for `JobRunErrorLog::jobRunId`.
- `getState(): ?JobRunStates` / `setState(?JobRunStates)` – the column is not nullable → non-null types.
- Remove `setId()` (both entities) – ids are generated.
- `JobRunErrorLog`: constructor promotion, `readonly` properties (immutable record), `getStepNumber(): int`;
  `#[HasLifecycleCallbacks]` is unused there.
- Consider `DateTimeImmutable` (`Types::DATETIME_IMMUTABLE`) instead of int timestamps for `creationDate` /
  `modificationDate` (BC-relevant for API consumers – keep int getters as wrappers) and PHP 8.4 asymmetric visibility
  (`public private(set)`) instead of trivial getter/setter pairs.
- `getCurrentMessage()` → `return $this->currentMessage ?? '';`.

### [ ] I-06 – Move job (de)serialization out of the entity
**File:** `src/Entity/JobRun.php:182-199`, `:273-298`
- A new `Serializer` + `PropertyInfoExtractor` + `PhpDocExtractor` graph is built **per entity instance** and the whole
  job is (de)serialized on every `PostLoad`/`refresh()` and every flush (`PreUpdate`) – i.e. for every processed element
  (selection with thousands of `ElementDescriptor`s → O(n²) CPU over a run).
- Replace with a custom Doctrine type (`gee_job`, JSON) or an embeddable – fixes B-17 (change detection), removes the
  lifecycle callbacks and the unmapped `$serializer` property; share one serializer instance.
- PropertyInfo `getTypes()` is deprecated in Symfony 7.3 in favour of TypeInfo `getType()` – when touching this, use
  `PropertyInfoExtractor` with TypeInfo-aware extractors / `ConstructorExtractor`.
- Deserialization always creates `JobStep` (concrete phpdoc `JobStep[]`), so custom `JobStepInterface` implementations
  are silently converted.

### [ ] I-07 – `Job` / `JobStep` value objects
**Files:** `src/Model/Job.php`, `src/Model/JobStep.php`, `src/Model/JobStepInterface.php`
- `Job`: normalise `steps` with `array_values()` and validate element types (`JobStepInterface`, `ElementDescriptor`)
  (B-08); phpdoc `JobStepInterface[]` / `list<…>`; make it `readonly` with `withSelectedElements()` instead of the
  mutating setter (fixes the "rerun uses mutated job" part of B-04).
- `JobStep`: validate `messageFQCN` (`class-string<GenericExecutionEngineMessageInterface>`), make `condition` default
  `''` and `config` default `[]` (order currently forces passing both – see docs example).
- `JobRunStates` → singular `JobRunState`; add helper `isTerminal()`; enums `ErrorHandlingMode`/
  `SelectionProcessingMode` live in `Utils\Enums` and `ErrorHandlingMode` is `@internal` although it is part of the
  config contract – move to `Model`.

### [ ] I-08 – Define the public API consistently (`@internal` markers)
`GenericExecutionEngineMessageInterface`, `ValidateStepConfigurationTrait`, `ExecutionContextInterface`,
`CurrentMessage*`, `PermissionServiceInterface`, `ErrorHandlingMode` are `@internal`, but users must implement /
extend them through the documented extension points (`AbstractExecutionEngineMessage`, `AbstractAutomationActionHandler`,
`JobRunRepositoryInterface::updateLogLocalizedWithDomain()` is `@internal` on a public interface). Decide what is API,
remove `@internal` there, mark the rest `final`/`@internal` consistently (e.g. `JobRunExtractor` is `final readonly` but
not internal, `PermissionConstants` is not final).

---

## Execution engine logic

### [ ] I-09 – `JobExecutionAgent` readability
**File:** `src/Agent/JobExecutionAgent.php`
- `use Doctrine\DBAL\Exception;` makes all `@throws Exception` look like `\Exception` – alias as `DBALException` or drop
  the docs where the method does not actually throw it.
- The `match` in `handleJobExecutionError()` (`:249-266`) is formatted unreadably – extract `$mode` first.
- `setJobRunError(…, bool $translate, ?JobRunStates $status)` is a flag-argument method doing two different things –
  split into `failWithTranslation()` / `logElementError()`.
- Repeated `"[JobRun {$jobRun->getId()}]: …"` prefixes → PSR-3 context (`['jobRunId' => …]`) and a small private
  `log()` helper (I-17).
- `getSelectionProcessingModeFromJobRun()` → `$jobRun->getJob()?->getSteps()[$step] ?? null` one-liner on the job.
- `dispatchSelectedElements()` default `array $selectedElements = []` is never used.

### [ ] I-10 – Too many queries per processed message
**Files:** `src/Agent/JobExecutionAgent.php:94-225`, `src/Repository/JobRunRepository.php:137-156`
Per message: `getJobRunById` in `continueJobMessageExecution` + `isRunning` (with `refresh`) + `handleNextMessage` +
`continueJobStepExecution`, each a `findOneBy`; plus `updateLog()` does another `refresh()` (→ re-deserialization, I-06).
Load once with a fresh read and pass the entity along (part of the C-02 rewrite).

### [ ] I-11 – Explicit state machine for job runs
States are changed in 8 places without transition rules (B-05, terminal → running via `continueJobExecutionOnError`
passing `RUNNING`). Introduce a small `JobRunStateMachine` (or Symfony Workflow) with allowed transitions
(`not_started → running → finished|finished_with_errors|failed|cancelled`), use it in agent and repository, and emit
`JobRunStateChangedEvent` from there (I-21) instead of a Doctrine listener.

### [ ] I-12 – Message base class
**Files:** `src/Messenger/Messages/AbstractExecutionEngineMessage.php`, `GenericExecutionEngineMessageInterface.php`
- `$elements` (`:31`) has no getter and is never set by the agent – remove or expose.
- Make the properties `readonly` (messages are immutable once dispatched).
- Add `#[\Override]` on the interface methods.

### [ ] I-13 – Handler base class (`AbstractAutomationActionHandler`)
**File:** `src/Messenger/Handler/AbstractAutomationActionHandler.php`
- Five `#[Required]` setters with `if (!$this->x)` guards and nullable properties → typed non-null via a
  `ServiceSubscriberTrait`/`#[SubscribedService]` locator, or document constructor injection; the null guards make
  overriding in tests impossible.
- `abortAction(): never`, `throwUnRecoverableException(): never` (B-30).
- Doc comment references a non-existent `pimcore_job_execution.en.yaml` (`:118`).
- `getSubjectFromMessage()` / `getSubjectsFromMessage()` cast `ElementInterface` to `AbstractElement` via `@var` – return
  `ElementInterface` or check with `instanceof`.
- Default `$types`: handler uses `[object, asset]`, extractor `[asset]` – document/unify; consider an element-type enum.
- `getEnvironmentVariables()` reloads the JobRun for each string in a recursive config (`:206-210`) – resolve once.
- `updateJobRunContext()` – read-modify-write of the whole JSON context (same race as C-02 when several elements write
  context concurrently); offer an atomic `JSON_SET` variant or document it.

### [ ] I-14 – `JobRunExtractor` cleanup
**File:** `src/Extractor/JobRunExtractor.php`
- `getElementFromMessage()` (`:131-135`): `if (!$element) return null; return $element;` → `return $element;`.
- Type constants on the interface (`ASSET_TYPE`, …) duplicate core element type names – use the core constants/enum.
- Folder exclusion (`getElement()`) is implicit and undocumented – make it a parameter.
- `getElementsFromMessage()` loads elements one by one (`Service::getElementById` per descriptor) – batch/lazy iterate
  (generator) for large selections.

### [ ] I-15 – Repository API cleanup
**Files:** `src/Repository/JobRunRepository.php`, `src/Repository/JobRunErrorLogRepository.php`, interfaces
- `getJobRunById()` throws `Pimcore\Model\Exception\NotFoundException`, `updateSelectedElements()` the bundle's
  `JobNotFoundException`, the interface documents neither consistently – pick the bundle exception (and add
  `JobRunNotFoundException`).
- `$orderBy` arrays are passed straight to `findBy()` – whitelist sortable fields (an unknown field from a grid request
  is a 500).
- `getRunningJobsByUserId()` has no `@return JobRun[]`; `update()` both persists and flushes – name it `save()`.
- Empty lines at the start of method bodies (`:64`, `:121`, `:139`), trailing blank line in `getJobRunById()`.
- `updateLogLocalized()` vs `updateLogLocalizedWithDomain()` – collapse into one method with `?string $domain = null`
  (null = execution-context domain); `$defaultLocale = 'en'` hard-coded – inject the admin default locale.

### [ ] I-16 – Installer
**File:** `src/Installer.php`
- Use Doctrine migrations (configured in `doctrine_migrations.yaml`, folder empty) for schema creation/evolution (B-33).
- `new Comparator($platform)` → `$schemaManager->createComparator()`; execute generated statements one by one.
- `$dbPlatform instanceof AbstractPlatform` (`:261`) is always true – remove.
- `addUserPermission()` / `removeUserPermission()` receive `Schema` only for `hasTable()`; the permission
  insert/delete is not part of the schema diff – extract a core trait shared with Seo/Glossary/StaticRoutes/
  WordExport/CustomReports installers (idempotent upsert, B-32).
- Constants are declared after the constructor; trailing empty line in the constructor parameter list (`:41`).

---

## Observability / UI

### [ ] I-17 – Logging
Use PSR-3 context arrays instead of interpolated strings; log errors with `['exception' => $throwable]` instead of
string concatenation (`JobExecutionAgent.php:237`); `info` level is used for failures (`:346`, `:355`) – use
`warning`/`error`.

### [ ] I-18 – Store run log lines in a dedicated table
Replace the `CONCAT` on one `TEXT` column (`JobRunRepository::updateLog()`) by a `generic_execution_engine_log` table
(`jobRunId`, `createdAt`, `message`, `level`) – removes the size limit (B-19), the `refresh()` (B-18), the LogLine
re-parsing and allows pagination in the UI.

### [ ] I-19 – `LogLine` parsing
**File:** `src/Utils/ValueObjects/LogLine.php:53-66`
Magic offsets `25`/`27` tied to `format('c')` in the repository – use one shared format constant and a regex
(`/^(\S+): (.*)$/s`); appended lines use `PHP_EOL` while storage uses `"\n"`; make it `readonly` with a named
constructor `fromString()`.

### [ ] I-20 – `ThrowableChainTrait`
**File:** `src/Utils/Traits/ThrowableChainTrait.php`
`getFirstThrowable()` returns the **innermost** previous exception (root cause) – rename to `getRootThrowable()`; the
root (e.g. a `PDOException`) often hides the meaningful wrapper message – consider unwrapping only
`HandlerFailedException` (`getWrappedExceptions()`) and keeping the handler's own exception.

### [ ] I-21 – `JobRunStateChangedEvent`
**File:** `src/Event/JobRunStateChangedEvent.php`
Carry `JobRunStates` enums (keep string getters for BC), `?int` owner, execution context and the run id; dispatch after
commit (C-01); empty constructor body with blank line; class can be `final readonly`-style with promoted readonly props.

### [ ] I-22 – Authorization via a voter
**Files:** `src/Security/PermissionService.php`, `src/Agent/JobExecutionAgent.php:117-122`
Replace `PermissionService` (session lookup in constructor – C-06) and `isInteractionAllowed()` by a `JobRunVoter`
(`VIEW`, `CANCEL`, `RERUN`) using `gee_job_run_permission`, `gee_see_all_job_runs_permission`, ownership and admin flag
(B-06); repositories then only filter, controllers call `isGranted()`.

### [ ] I-23 – `JobRunGrid` performance / output
**File:** `src/Grid/JobRunGrid.php:42-68`
`User::getById()` per row (N+1) – preload owner names for the page; full log is parsed and returned for every row of
the list – return the log only in a detail call (paired with I-18); `canCancel` should use the voter (I-22).

---

## Translations / constants / docs / tooling

### [ ] I-24 – Translations
**File:** `translations/admin.en.yaml`
Only English exists; `gee_error_no_steps` is unused (B-08); placeholder styles are mixed (`%job_run_id%` vs
`%stepName%`, `%fromCount%`); `gee_job_cancelled` lacks the trailing period the other job messages have;
`gee_middleware_step_condition_not_met` uses "in %jobName%" while the PSR log says "of Job". Unify naming
(`%job_run_name%`, `%step_name%`, `%step_id%`).

### [ ] I-25 – Constants → enums / final classes
**Files:** `src/Utils/Constants/PermissionConstants.php`, `TableConstants.php`
`PermissionConstants` is not `final`; both could be backed enums (`GeePermission::JobRun`). The comment "Permission have
a max length of 50 chars!" belongs to the core permission definition, not here.

### [ ] I-26 – Tests
No tests exist for the bundle. Minimum set:
- Unit: `ExecutionContext`, `CurrentMessageProvider` (B-22/B-23), `LogLine`, `Configuration` (B-09/B-11),
  `replaceConfigValueWithEnvVariable()` (B-29), `ValidateStepConfigurationTrait` (B-28).
- Functional (in-memory/sync transport + `messenger:consume` with 2 workers): full run with FOR_EACH/ONCE steps,
  skipped condition (C-05), selection change (C-03), cancel (C-04), stop-on-first-error, state-change event (C-01),
  concurrent counters (C-02), completion states (B-01).

### [ ] I-27 – Composer / package metadata
The bundle has no own `composer.json`; its hard runtime requirements (`phpdocumentor/reflection-docblock`,
`symfony/property-info`, `symfony/serializer`, `symfony/options-resolver`, `symfony/expression-language`) are only
partially in the root `require` and two are only `suggest`ed (B-13). Either require them or guard the bundle boot.

### [ ] I-28 – Documentation
**Directory:** `doc/19_Development_Tools_and_Details/08_Generic_Execution_Engine/`
- Routing requirement missing (C-08); handler bus tag missing (I-03).
- `01_Jobs.md`: example uses `StepSelectionMode::FOR_EACH` (class does not exist – `SelectionProcessingMode`); the
  "retry" YAML block shows the transport config under `pimcore_generic_execution_engine:` root key, which is invalid
  bundle config.
- `04_Step_Configuration.md`: documents `SelectionProcessingMode::FOR_ALL` – the case is `ONCE`; `condition` property
  is not documented; `messageClass`/`configuration` names differ from code (`messageFQCN`, `config`).
- `03_Extending…`: event example compares strings – update with I-21; mention that handlers must not rely on
  `isRunning()` after C-04.
- Document the security model (who may see/cancel/rerun runs, `environmentData` is visible to viewers).

### [ ] I-29 – PHP 8.4 idioms
- `#[\Override]` on all interface implementations.
- `JobRunErrorLog`, `JobRun` constructor: constructor promotion.
- `setCompletionState()` → `match (true)`; `empty($logs)` after `count($logs) > 0` → single `match`.
- `array_any()` / `array_find()` where loops search (e.g. middleware step lookup).
- `readonly` on `Job` (I-07), `LogLine` (I-19), messages (I-12).
- Array-shape phpdoc for `ExecutionContext::$contexts` (`array<string, array{translations_domain: string,
  error_handling: ?string}>`).

### [ ] I-30 – Naming
- Config key `translations_domain` vs Symfony convention `translation_domain` (keep alias for BC).
- `getJobRunsByUserId()` filters by *owner* – `getJobRunsByOwnerId()`; `JobRunStates` (plural) – see I-07.
- `continueJobMessageExecution()` / `continueJobStepExecution()` / `handleNextMessage()` – names do not tell which one
  advances the step; rename to `onMessageProcessed()` / `advanceToNextStep()` / `isStepComplete()`.
- `AbstractAutomationActionHandler` – "automation action" is a studio term; `AbstractJobStepHandler` would match the GEE
  terminology (BC alias).
