# GenericExecutionEngineBundle – TODO 2: Bugs

Scope: `bundles/GenericExecutionEngineBundle/src/**` plus the dependent config (`config/services.yaml`,
`config/pimcore/*.yaml`), `translations/admin.en.yaml`, the root `composer.json` constraints and the GEE docs
(`doc/19_Development_Tools_and_Details/08_Generic_Execution_Engine/**`) where they define the contract
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

Functional defects that produce wrong states/output, crashes (`TypeError` / `Error` / `500`) in specific situations,
lost data or misleading logs. IDs are stable – refer to them as `B-xx` in later sessions. Line numbers are from commit
`bad4feee5b`.

> All files declare `strict_types=1`, so every type mismatch listed below is a hard `TypeError`, not a silent coercion.

---

## Job execution agent (`src/Agent/JobExecutionAgent.php`)

### [ ] B-01 – Completion state is computed from the wrong error-log set
**Files:** `src/Agent/JobExecutionAgent.php:213-216`, `:418-446`, `src/Repository/JobRunErrorLogRepository.php:59-78`
- `setCurrentStep(null)` (`:214`) is executed **before** `setCompletionState()`, which then passes
  `$jobRun->getCurrentStep()` (= `null`) → error logs of **all** steps are fetched, but compared with the element
  count of a single step: 5 elements, 2 errors in step 1 + 3 in step 2 → `5 === 5` → `failed` although every element
  succeeded at least once; 3 + 3 → `finished_with_errors` although all elements of step 2 failed.
- `getLogsByJobRunId()` has a default `limit = 100` → `count($logs)` is capped at 100 → a run with 500 elements that all
  failed is reported as `finished_with_errors`.
- `getLogsByJobRunId()` uses the criteria key `'step'` (`JobRunErrorLogRepository.php:69`) but the entity field is
  `stepNumber` → Doctrine throws "Unrecognized field: step" as soon as a step is passed (latent, hidden by the first
  bullet).
- `ONCE` steps with `totalElements > 0` compare one error against N elements.
- Loading full entities just to count them.

**Fix:** count in SQL (`COUNT(DISTINCT elementId)` per step via `getTotalCountByJobRunId()`-like method), evaluate the
state per step before resetting `currentStep`, fix the `stepNumber` criteria key, use `match (true)` for the state.

### [ ] B-02 – Wrong placeholder for `gee_error_no_job_definition` in `dispatchStepMessage()`
**File:** `src/Agent/JobExecutionAgent.php:371-375`, `translations/admin.en.yaml:3`
Passes `['%job_id%' => …]`, the translation expects `%job_run_id%` → message shows the literal `%job_run_id%`.
`continueJobStepExecution()` (`:189-193`) uses `getLogParams()` correctly. **Fix:** use `getLogParams($jobRun)`.

### [ ] B-03 – Log translation in `setJobRunError()` ignores the translation domain
**File:** `src/Agent/JobExecutionAgent.php:344-348`
`$this->translator->trans($errorMessage, $params)` uses the default `messages` domain, all `gee_*` keys live in
`admin` → the PSR log gets the raw key. **Fix:** pass `'admin'` (same domain as `updateLogLocalizedWithDomain()`).

### [ ] B-04 – `rerunJobRun()` does not reproduce the original run
**File:** `src/Agent/JobExecutionAgent.php:142-146`
- Execution context is lost – `startJobExecution($jobRun->getJob(), $ownerId)` falls back to `'default'` (other
  translation domain / error handling).
- The persisted `Job` was mutated by `updateSelectedElements()` (C-03) → the rerun starts with the selection produced
  by the **last** step, not the original selection.
- `getJob()` may be `null` → `TypeError` on `startJobExecution(Job $job, …)`.
- No guard: a `running` run can be "rerun" (two parallel runs on the same elements).
**Fix:** store the original selection separately (or the original serialized job), pass
`$jobRun->getExecutionContext()`, reject reruns of `running` runs.

### [ ] B-05 – `cancelJobRun()` overwrites terminal states
**File:** `src/Agent/JobExecutionAgent.php:127-137`
A `finished`, `failed` or already `cancelled` run is set to `cancelled` again and logs "cancelled". **Fix:** only
transition `running`/`not_started` → `cancelled` (see I-11).

### [ ] B-06 – `isInteractionAllowed()` ignores "see all" permission and owner-less runs
**File:** `src/Agent/JobExecutionAgent.php:117-122`
Only the owner may interact; administrators with `gee_see_all_job_runs_permission` (who can see the run) cannot cancel
or rerun it, and runs started without owner (`ownerId = null`, CLI) cannot be cancelled by anybody. **Fix:** voter
(I-22) that combines owner, "see all" and admin.

### [ ] B-07 – Continue-on-error path logs "Job execution failed" and overwrites the current message with raw text
**File:** `src/Agent/JobExecutionAgent.php:289-304`, `:354-361`
In `CONTINUE_ON_ERROR` mode every element error logs `… --> Job execution failed.` (info level) and sets
`currentMessage` to the raw exception text although the run continues; the raw text then goes through
`getMessageFromSerializedString()` (B-22). Error-log entry already exists (`:243-247`). **Fix:** separate
"element failed" from "run failed" messages, keep `currentMessage` as translation message.

### [ ] B-08 – Step messages are instantiated without type check; non-list `steps` break dispatch
**File:** `src/Agent/JobExecutionAgent.php:385-396`, `:460-475`, `src/Model/Job.php:29-39`
- Only `class_exists()` is checked; a class that does not implement `GenericExecutionEngineMessageInterface` or has a
  different constructor signature is instantiated (`new $messageString($jobRunId, $stepId, $element)`) → `TypeError` /
  `ArgumentCountError` inside the worker event (see TODO-1 notes) → run hangs instead of `failed` with
  `gee_error_missing_message_implementation`.
- `$job->getSteps()[$jobRun->getCurrentStep()]` assumes a 0-based list; `Job` accepts any array
  (`['import' => new JobStep(...)]`) → `null->getMessageFQCN()` → `Error`. After JSON round trip non-list arrays stay
  associative.
- Translation `gee_error_no_steps` exists but is never used.
**Fix:** `is_subclass_of($messageString, GenericExecutionEngineMessageInterface::class)`, `array_values()` + type
validation of steps in `Job::__construct()`.

### [ ] B-09 – Global `error_handling` setting is ignored for the `default` execution context
**Files:** `config/pimcore/default.yaml:1-6`, `src/Agent/JobExecutionAgent.php:269-284`,
`src/DependencyInjection/Configuration.php:46-57`
`default.yaml` sets `execution_context.default.error_handling: continue_on_error`; the context value has precedence over
the global value (`:278-281`) → `pimcore_generic_execution_engine.error_handling: stop_on_first_error` in the project
config has **no effect** for jobs started with the default context (the documented override chain is inverted).
**Fix:** remove `error_handling` from the `default` context in `default.yaml` (and the duplicated top-level default –
Configuration already defines it).

### [ ] B-10 – Error log creation crashes when `currentStep` is `null`
**Files:** `src/Repository/JobRunErrorLogRepository.php:39-44`, `src/Entity/JobRunErrorLog.php:50-55`
`JobRunErrorLog::__construct(int $stepNumber)` receives `$jobRun->getCurrentStep()` (`?int`). A failing message that
is processed after the run was completed/reset (left-over message, C-03/C-04) → `TypeError` in the failure path → the
original error is lost. **Fix:** use `$message->getCurrentJobStep()` as step number (it is what actually failed).

---

## DI / configuration

### [ ] B-11 – Execution context names are key-normalised (`-` → `_`)
**File:** `src/DependencyInjection/Configuration.php:39-41`
`execution_context` is a prototyped array without `->normalizeKeys(false)` / `useAttributeAsKey()`; Symfony's config
normaliser converts dashes in keys to underscores → a context configured as `my-context` is stored as `my_context` and
`startJobExecution($job, null, 'my-context')` later fails with `ExecutionContextNotDefinedException`.
**Fix:** `->useAttributeAsKey('name')->normalizeKeys(false)`.

### [ ] B-12 – Unknown / removed execution context breaks logging and the job run grid
**Files:** `src/Configuration/ExecutionContext.php:33-54`, `src/Repository/JobRunRepository.php:104`,
`src/Grid/JobRunGrid.php:48`, `src/Agent/JobExecutionAgent.php:67-89`
- `startJobExecution()` does not validate the context up front → the run is created and dispatched and only fails later
  inside the worker (`updateLogLocalized()` / `getErrorHandlingFromContext()` throw) → run stuck in `running`.
- Runs persisted with a context that was later removed from config make `convertJobRunToArray()` throw → the complete
  job run list returns `500`.
**Fix:** validate in `startJobExecution()`; fall back to the default context (with a warning) when reading old runs.

### [ ] B-13 – Suggested-only dependencies are hard requirements
**Files:** `src/Entity/JobRun.php:279-290`, root `composer.json:169-170`
`PhpDocExtractor` throws a `LogicException` when `phpdocumentor/reflection-docblock` is missing and
`symfony/property-info` is only *suggested* → every `JobRun` load (PostLoad) crashes, but the bundle installs and boots
fine. **Fix:** check `class_exists()` in the extension (`ContainerBuilder::willBeAvailable()`) or the installer and fail
with a clear message (or move to `require` – I-27).

### [ ] B-14 – `EntityRepository::count()` without criteria needs a newer ORM than allowed by composer
**Files:** `src/Repository/JobRunRepository.php:186`, `src/Repository/JobRunErrorLogRepository.php:82`, root
`composer.json:53` (`doctrine/orm: ^2.15 || ^3.0`)
`count()` without arguments is only valid on ORM versions where `$criteria` has a default (*verify the exact version*);
on older 2.x it is an `ArgumentCountError`. **Fix:** pass `[]` or raise the constraint.

---

## Entities / repositories

### [ ] B-15 – Uninitialised typed properties on `JobRun`
**File:** `src/Entity/JobRun.php:61`, `:64`, `:68`, `:71`, `:80`
`$currentMessage`, `$log`, `$job`, `$serializedJob`, `$modificationDate` are typed without default. On a new entity
`getCurrentMessage()`, `getLogs()`, `getJob()` throw "must not be accessed before initialization"; after `PostLoad`
with an empty `serializedJob` `$job` stays uninitialised → every `getJob()?->…` in the agent/grid/extractor throws
(the `?->` does not help). `getModificationDate()` works only because of `?? null`. **Fix:** initialise with `null`.

### [ ] B-16 – Entity mapping and installer schema disagree
**Files:** `src/Entity/JobRun.php:51-89`, `src/Entity/JobRunErrorLog.php:33-48`, `src/Installer.php:101-188`

| Column | Entity | Installer |
|---|---|---|
| `state` | `string(10)` | `string(100)` – `finished_with_errors` (20) and `not_started` (11) do not fit 10 |
| `currentMessage` | `string(300)` | `TEXT` |
| `serializedJob` | `TEXT NOT NULL` (but `serializeJob()` writes `null`) | `LONGTEXT NULL` |
| `context` | `JSON NOT NULL` | `LONGTEXT NULL` |
| `executionContext` | `NOT NULL` | `NULL DEFAULT 'default'` |
| `ownerId` FK → `users` | missing | present |
| `JobRunErrorLog.jobRunId` FK / index | missing | FK present |
| ids / counters | signed | unsigned |

Any `doctrine:schema:update` / `doctrine:migrations:diff --em=pimcore` will try to shrink `state` to `VARCHAR(10)` →
truncation / failure for 2 of 6 states; `doctrine:schema:validate` always fails. **Fix:** align the mapping with the
installer (length 20+, `Types::TEXT`, nullable flags, `#[ORM\JoinColumn]`/indexes) and move schema to migrations (I-16).

### [ ] B-17 – Updated selection is only persisted by accident
**File:** `src/Repository/JobRunRepository.php:225-242`, `src/Entity/JobRun.php:182-199`
`Job` is not a mapped field; `serializedJob` is only recomputed in `PrePersist`/`PreUpdate`, and `preUpdate` fires only
when a **mapped** field changed. `update($jobRun)` after `setSelectedElements()` is therefore a no-op; the selection is
persisted only because the following `updateLogLocalizedWithDomain()` changes `currentMessage`. When the new message is
identical to the current one (same from/to counts as the previous call) nothing is flushed and the subsequent
`refresh()` (`updateLog()`) **reverts** the job to the DB state → selection silently lost.
**Fix:** serialize explicitly (`$jobRun->serializeJob()` before flush) or use a custom Doctrine type (I-06).

### [ ] B-18 – `updateLog()` refreshes the entity and discards pending changes
**File:** `src/Repository/JobRunRepository.php:119-135`
`$this->pimcoreEntityManager->refresh($jobRun)` after the raw `UPDATE` throws away all unflushed changes of the run
(e.g. a handler sets context and calls `logMessageToJobRun(..., updateCurrentMessage: false)`) and re-deserializes the
job (new `Job` object – references held by callers are stale). The SQL also uses a double-quoted literal `"\n"`, which
is an identifier under `ANSI_QUOTES` sql_mode. **Fix:** flush before the raw update (or append the log via DQL/entity),
use `'\n'`/parameter, refresh only the `log` field.

### [ ] B-19 – `log` column overflows (TEXT = 64 KB)
**Files:** `src/Repository/JobRunRepository.php:122-130`, `src/Installer.php:121`
Every start/step/skip/error/selection message is appended to one `TEXT` value; large jobs (skip messages per element,
dev stack traces – `JobExecutionAgent.php:239-241`) exceed 65 535 bytes → MySQL strict mode: "Data too long" exception
in the worker event → run stuck in `running`; non-strict: silently truncated. **Fix:** separate log table (I-18), or at
least `LONGTEXT` + size cap.

### [ ] B-20 – `getLastJobRunByName()` matches wrong runs
**File:** `src/Repository/JobRunRepository.php:204-220`
`LIKE '%name":"<name>"%'` on the serialized JSON: `%`/`_` in `$name` are not escaped; JSON escaping differs from the raw
value (`/` → `\/`, non-ASCII → `\uXXXX`, quotes); `"name":"…"` also matches **step names** → returns a run of another
job whose step has that name; ordering by `modificationDate` instead of creation; full table scan on LONGTEXT.
**Fix:** persist the job name in its own indexed column.

### [ ] B-21 – `getTotalCount()` ignores the owner filter of `getJobRunsByUserId()`
**File:** `src/Repository/JobRunRepository.php:164-187`
The list is restricted to the owner (no "see all" permission) but the total counts **all** runs → wrong pagination
(empty pages) and disclosure of the overall number of runs. **Fix:** `getTotalCountByUserId(?int $ownerId)` with the
same criteria.

---

## Current message / grid

### [ ] B-22 – Valid-JSON plain messages crash `getMessageFromSerializedString()`
**File:** `src/CurrentMessage/CurrentMessageProvider.php:45-60`
Raw exception messages are stored as plain `currentMessage` (B-07). If such a message is valid JSON but not a
translation object (`"404"`, `"true"`, `"null"`, `{"error":"…"}` from an HTTP API, `[1,2]`), `json_decode` succeeds,
the `isset` check fails and `InvalidJsonMessageException` is thrown (only `JsonException` is caught) → job run grid
`500`. Scalars also fail `isset()` on non-arrays. **Fix:** return `PlainMessage` for anything that is not a translation
object; store messages with a type discriminator instead of guessing.

### [ ] B-23 – `null` domain is accepted by the interface but rejected by `TranslationMessage`
**Files:** `src/CurrentMessage/CurrentMessageProviderInterface.php:25-29`, `src/CurrentMessage/TranslationMessage.php:28-33`
`getTranslationMessages(…, ?string $domain = null)` → `new TranslationMessage(…, string $domain, …)` → `TypeError`
when called with the default. **Fix:** make `$domain` `?string` in `TranslationMessage` (translator accepts `null`).

### [ ] B-24 – `TranslationMessage::getSerializedString()` silently returns `''`
**File:** `src/CurrentMessage/TranslationMessage.php:37-44`
Parameters with invalid UTF-8 (element keys/paths from legacy data, binary exception text) make `json_encode` fail → the
current message becomes empty without any log. **Fix:** `JSON_INVALID_UTF8_SUBSTITUTE`, log on failure.

### [ ] B-25 – `JobRunGrid::convertJobRunToArray()` crashes for runs without job
**File:** `src/Grid/JobRunGrid.php:52`, `:56`
`trans($jobRun->getJob()?->getName(), …)` passes `null` to `trans(string $id)` → `TypeError`; combined with B-15 the
whole list fails. `getState()->value` on a `?JobRunStates` getter. **Fix:** fallback name, non-null state getter.

---

## Extractor / handler base / middleware

### [ ] B-26 – `JobRunExtractor` null handling
**File:** `src/Extractor/JobRunExtractor.php:45-55`, `:138-162`
- `getJobStep()`: `$jobSteps` is `null` when the job is missing → `array_key_exists(…, null)` → `TypeError`; the
  exception text says "Job not found!" for a missing **step** and uses `Pimcore\Model\Exception\NotFoundException`
  instead of the bundle's `JobNotFoundException`.
- `getElementsFromMessage()`: `foreach` over `null` (warning + empty result) when the job is missing.

### [ ] B-27 – Step conditions referencing `context` / `environmentData` fail when those are empty
**File:** `src/Extractor/JobRunExtractor.php:84-113`
`extractContentVariables()` only defines `context` / `environmentData` when they are non-empty. A condition such as
`context['imported'] == true` on a fresh run (context `null`) → ExpressionLanguage `SyntaxError: Variable "context" is not
valid` → exception in the middleware (on dispatch: out of `startJobExecution()` – C-05). Also all variables are built
before the cheap `'' === condition` check. **Fix:** always pass both variables (default `[]`), check empty condition
first.

### [ ] B-28 – `configurationIsValid()` validates against an unconfigured resolver; `configureStep()` runs per message
**Files:** `src/Configuration/ValidateStepConfigurationTrait.php:30-52`,
`src/Messenger/Handler/AbstractAutomationActionHandler.php:140-154`
- `configurationIsValid()` never calls `configureStep()` → the resolver has no defined options → every non-empty config
  is "invalid" (`UndefinedOptionsException`).
- `getCurrentJobStepConfig()` calls `configureStep()` on the **same** `OptionsResolver` for every message of a
  long-running worker → `addAllowedValues()` / `addNormalizer()` / `addAllowedTypes()` accumulate and normalizers run
  multiple times.
**Fix:** configure the resolver once (lazy `getStepConfigurationResolver()`), use it in both methods.

### [ ] B-29 – `job_env()` replacement drops surrounding text and ignores additional placeholders
**File:** `src/Messenger/Handler/AbstractAutomationActionHandler.php:166-183`
`"prefix-job_env('a')-suffix"` returns only the value of `a`; `"job_env('a')/job_env('b')"` returns only `a`. The docs
(`04_Step_Configuration.md`) describe placeholders inside configuration values. **Fix:** return the raw value only when
the whole string is a single placeholder, otherwise `preg_replace_callback()` all occurrences.

### [ ] B-30 – `abortAction()` uses the non-existent translation domain `'default'`
**File:** `src/Messenger/Handler/AbstractAutomationActionHandler.php:104-114`
Default `$translationDomain = 'default'` → never translated (Pimcore uses `admin` / `messages`). Return type `void`
although it always throws; `$exceptionClassName` is not validated (non-`Throwable` class → `Error`). **Fix:** default to
the run's execution-context domain (or `'admin'`), `: never`, `is_a($exceptionClassName, Throwable::class, true)`.

### [ ] B-31 – `StepConditionMiddleware::logToJobRun()` null-safe chain is broken
**File:** `src/Messenger/Middleware/StepConditionMiddleware.php:65-82`
`$jobRun->getJob()?->getSteps()[$stepId]->getName()` – the null-safe operator does not protect the array access and
`->getName()` → `Error` for a missing job/step. The PSR log message contains a hard newline + indentation (multi-line
string literal). **Fix:** use `JobRunExtractor::getJobStep()`, single-line log with context array.

---

## Installer

### [ ] B-32 – Re-install fails on existing permission definitions
**File:** `src/Installer.php:213-232`
Plain `INSERT` into `users_permission_definitions` → duplicate-key error when the permissions already exist (previous
failed install, manual creation, `pimcore:bundle:install` after a DB restore) → install aborts after the tables were
created, the settings store does not mark the bundle installed. **Fix:** `INSERT … ON DUPLICATE KEY UPDATE` /
existence check (same issue as CustomReportsBundle B-27 – shared trait, I-16).

### [ ] B-33 – Schema changes are never applied to existing installations
**Files:** `src/Installer.php:75-96`, `:255-270`, `src/Migrations/.empty`, `config/pimcore/doctrine_migrations.yaml`
Tables are only created when they do not exist; columns added later (`executionContext`, `totalElements`,
`processedElementsForStep`, …) are never added to existing tables and the migrations folder is empty. `uninstallBundle()`
calls `executeDiffSql()` with the just-introspected schema (always an empty diff). `executeDiffSql()` executes all DDL as
one `implode(';')` string (needs multi-statement support; one failing statement leaves a half-applied diff).
**Fix:** Doctrine migrations for schema evolution; execute statements one by one.
