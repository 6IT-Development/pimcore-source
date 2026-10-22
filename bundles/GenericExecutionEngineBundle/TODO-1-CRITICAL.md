# GenericExecutionEngineBundle – TODO 1: Critical errors

Scope: `bundles/GenericExecutionEngineBundle/src/**` plus the dependent config (`config/services.yaml`,
`config/pimcore/config.yaml`, `config/pimcore/default.yaml`, `config/pimcore/doctrine.yaml`,
`config/pimcore/doctrine_migrations.yaml`), `translations/admin.en.yaml`, the root `composer.json` constraints the bundle
relies on and the GEE docs (`doc/19_Development_Tools_and_Details/08_Generic_Execution_Engine/**`) where they define the
contract
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-2-BUGS.md](TODO-2-BUGS.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

These items make the engine **unable to run jobs at all, leave job runs stuck in `running` forever, keep executing
(data-modifying) steps after a job was cancelled / failed, or leak job runs across users**.
IDs are stable – refer to them as `C-xx` in later sessions. Line numbers are from commit `bad4feee5b`.

> Notes
> - No hard PHP 8.4 / Symfony 7.4 incompatibility (removed API, implicit nullable parameter, removed Symfony class, …)
>   was found. `new DateTimeImmutable()->format('c')` (`src/Repository/JobRunRepository.php:128`) already uses the PHP 8.4
>   "new without parentheses" syntax, so the bundle hard-requires PHP ≥ 8.4 (matches `"php": "~8.4.0"`).
> - All files declare `strict_types=1` – every type mismatch below is a hard `TypeError`.
> - The engine is driven by **worker events** (`JobExecutionSubscriber`): any exception thrown while continuing a job
>   (C-01, C-02, B-08, B-19, …) escapes from a `WorkerMessageHandledEvent` listener, is logged by the worker, and the job run
>   stays in `running` without anybody advancing it. There is no watchdog / timeout for such runs.
> - C-02, C-03, C-04 and C-05 all touch the "step progress" logic in `JobExecutionAgent`; fix them together
>   (see also **I-11** – explicit state machine).

---

## [ ] C-01 – `JobRunStatusChangeSubscriber` throws `TypeError` on every state change → job runs cannot change state

**Files:** `src/EventSubscriber/JobRunStatusChangeSubscriber.php:49-63`, `src/Event/JobRunStateChangedEvent.php:24-30`,
`src/Entity/JobRun.php:54-55`, `config/services.yaml:55-61`

```php
$oldStatus = $args->getOldValue(self::STATE_FIELD);   // JobRunStates enum (column mapped with enumType)
$newStatus = $args->getNewValue(self::STATE_FIELD);   // JobRunStates enum
...
new JobRunStateChangedEvent(
    jobRunId: $entity->getId(),
    jobName: $jobName,                    // ?string  -> string
    jobRunOwnerId: $jobRun->getOwnerId(), // ?int     -> int
    oldState: $oldStatus,                 // enum     -> string  => TypeError
    newState: $newStatus                  // enum     -> string  => TypeError
);
```

- `state` is mapped with `enumType: JobRunStates::class`, so the UnitOfWork change set contains **enum instances**, not
  strings. Enums are not `Stringable` → `TypeError` for `oldState`/`newState` on **every** flush that changes `state`.
- `jobRunOwnerId` is `int` but job runs started without owner (CLI, cron, `startJobExecution($job, null)` as shown in
  the docs) have `ownerId = null` → `TypeError`.
- `jobName` is `string` but `getJob()?->getName()` can be `null` → `TypeError`.

Flow: `startJobExecution()` persists the run as `not_started` (`JobRunRepository::createFromJob()`), then sets
`running` and flushes (`JobExecutionAgent.php:75-84`) → the first `preUpdate` already throws inside the flush → the
transaction is rolled back, the run stays `not_started`, the exception bubbles to the caller. The same happens on
`cancelled`, `failed`, `finished*` transitions (inside the worker → run stuck in `running`).

Additionally:
- `getJobRunById()` is called inside `preUpdate` (`:54`) although `$entity` already is the JobRun → extra query during
  flush, and listeners of the dispatched event that flush/query the EM re-enter a running flush (Doctrine forbids
  `flush()` inside `preUpdate`).
- The event is dispatched **before** the commit – listeners (e.g. the "send e-mail on failed" example in
  `doc/.../03_Extending_Generic_Execution_Engine.md`) react to states that may still be rolled back.

**Verify** with a functional test (no test exists – see I-26) – the code path is identical to upstream, so check whether
the listener is actually attached to the `pimcore` EM in the project setup; if it is, the engine cannot work at all.

**Fix direction:** pass `$oldStatus->value` / `$newStatus->value` (or change the event to carry `JobRunStates`, keep
string getters for BC – I-21), make `jobRunOwnerId` `?int` and `jobName` `?string`, use `$entity` instead of reloading,
collect the change in `preUpdate`/`onFlush` and dispatch the event in `postFlush` (after commit). Register via
`#[AsEntityListener(event: Events::preUpdate, entity: JobRun::class)]` (I-03).

---

## [ ] C-02 – Lost-update race on `processedElementsForStep` → steps never complete with more than one worker

**Files:** `src/Agent/JobExecutionAgent.php:94-115`, `:171-179`, `:324-330`, `src/Repository/JobRunRepository.php:62-69`

```php
$currentElementCount = $jobRun->getProcessedElementsForStep() + 1;   // read
$jobRun->setProcessedElementsForStep($currentElementCount);          // modify
$this->jobRunRepository->update($jobRun);                            // write (ORM flush, no lock, no version)
...
$jobRun->getProcessedElementsForStep() === $jobRun->getTotalElements()
```

Progress of a `FOR_EACH` step is a read-modify-write of one counter in the `generic_execution_engine_job_run` row,
executed by every worker that finishes a message. With ≥ 2 consumers on `pimcore_generic_execution_engine` (the normal
production setup) two workers read `N`, both write `N+1` → one increment is lost → the counter never reaches
`totalElements`, the strict `===` never matches → **the run stays `running` forever** and later steps are never
dispatched. The reverse (counter jumps past `totalElements` via double counting, see C-05) also hangs because of `===`.

Even with a single worker the value used for the comparison is the entity state of *this* EM, not the DB state.

**Fix direction:** increment atomically in SQL and evaluate the result in the same statement/transaction, e.g.
`UPDATE … SET processedElementsForStep = processedElementsForStep + 1 WHERE id = :id AND currentStep = :step` followed by
a `SELECT … FOR UPDATE` (or `SELECT` inside the same transaction), and advance the step only from the worker that moves
the counter to `>= totalElements` (guard the step transition with
`UPDATE … SET currentStep = :next WHERE id = :id AND currentStep = :step` and check the affected rows so only one worker
dispatches the next step). Ignore messages whose `getCurrentJobStep()` ≠ the run's `currentStep`.

---

## [ ] C-03 – `totalElements` is not updated when a step changes the selection → hang or premature step switch

**Files:** `src/Repository/JobRunRepository.php:225-242`, `src/Messenger/Handler/AbstractAutomationActionHandler.php:269-272`,
`src/Agent/JobExecutionAgent.php:77`, `:171-179`, `:398-404`

`setSelectedElementsForNextJobStep()` → `updateSelectedElements()` replaces `Job::$selectedElements`, but
`JobRun::$totalElements` stays the value computed once in `startJobExecution()` (`:77`). The next `FOR_EACH` step
dispatches one message per **new** element while the completion check still compares with the **old** total:

- new selection smaller than the original (e.g. a filter step) → counter never reaches `totalElements` → run hangs.
- new selection larger → the step is considered finished while messages are still queued → the next step runs in
  parallel with the current one, its counter is reset to 0 (`:220`) and then incremented by left-over messages of the
  previous step → wrong/duplicate processing order and again a hang or double step switch.
- new selection empty → one message without element is dispatched (`:459`), counter becomes 1 ≠ total → hang.

**Fix direction:** store the per-step expected message count on the run (set it in `dispatchStepMessage()` from the
number of messages actually dispatched: `1` for `ONCE`/empty, `count($selectedElements)` otherwise) and compare against
that instead of `totalElements`; keep `totalElements` only as informational value (or update it together with the
selection). Combine with C-02.

---

## [ ] C-04 – Cancelled / failed job runs keep executing all queued step messages; "stop" stops *every* worker of the app

**Files:** `src/Messenger/Middleware/StepConditionMiddleware.php:41-59`, `src/Agent/JobExecutionAgent.php:127-137`,
`:309-322`, `src/Messenger/Handler/AbstractAutomationActionHandler.php:237-242`

- Nothing on the consume path checks the run state before calling the handler. `StepConditionMiddleware` only checks
  the step condition. After `cancelJobRun()` or after `STOP_ON_FIRST_ERROR` set the run to `failed`, every message that
  is already in the queue (one per element) is **still handled** – i.e. the data-modifying action continues on all
  remaining elements. Only the bookkeeping afterwards is skipped (`continueJobMessageExecution()` returns early at
  `:100`). Handlers are expected to call `isRunning()` themselves, which is neither enforced nor documented.
- To compensate, `stopJobExecutionOnError()` calls `StopMessengerWorkersTrait::stopMessengerWorkers()` (`:314`): this
  signals **all** messenger workers of the installation (all transports – asset/thumbnail/search-index/… workers
  included) to stop. Without a process supervisor this halts the whole queue processing of the application; with a
  supervisor the workers restart and simply continue consuming the remaining messages of the failed job (see first
  bullet) – so the side effect is huge and the intended effect does not happen.

**Fix direction:** in `StepConditionMiddleware` (only when a `ReceivedStamp` is present, see C-05) load the run with a
fresh read and **skip the handler** when the run is not `running` or when `getCurrentJobStep()` ≠ `currentStep`; remove
the `stopMessengerWorkers()` call. Document that handlers do not need to check `isRunning()` any more.

---

## [ ] C-05 – `StepConditionMiddleware` runs on dispatch *and* consume and double-counts skipped messages

**Files:** `src/Messenger/Middleware/StepConditionMiddleware.php:41-59`, `config/pimcore/config.yaml:11-15`,
`src/EventSubscriber/JobExecutionSubscriber.php:63-71`, `src/Agent/JobExecutionAgent.php:451-477`

Bus middleware is executed for `dispatch()` (sending side) and again when the worker receives the message:

1. **Consume side:** condition false → the middleware calls `continueJobMessageExecution($message)` and returns
   normally; the worker then fires `WorkerMessageHandledEvent` → `JobExecutionSubscriber` calls
   `continueJobMessageExecution($message)` a **second time** → the element is counted twice → counter overshoots
   `totalElements` → `===` (C-02) never matches → run hangs (or, in `ONCE` mode, the next step is dispatched twice).
2. **Dispatch side:** the condition is evaluated synchronously inside `dispatchSelectedElements()` – i.e. inside
   `startJobExecution()` (HTTP request) or inside the previous step's worker event. If it is false the message is never
   sent and the agent recurses (`continueJobMessageExecution` → `continueJobStepExecution` → `dispatchStepMessage` → …)
   while the outer `foreach` is still dispatching → recursion depth = elements × skipped steps, and any exception from
   the expression (B-27) bubbles out of `startJobExecution()` leaving an orphan run in `running`.
3. The condition is evaluated twice (dispatch + consume) against possibly different `context` data.

**Fix direction:** act only on received envelopes (`$envelope->last(ReceivedStamp::class) !== null`), otherwise pass
through. When skipping, do **not** call the agent – let the worker event drive the continuation exactly once (e.g. add a
custom `StepSkippedStamp` and have `JobExecutionSubscriber` log the skip). Replace the recursion by queuing.

---

## [ ] C-06 – `PermissionService` caches the session user for the lifetime of the container

**File:** `src/Security/PermissionService.php:30-35`, used by `src/Repository/JobRunRepository.php:141`, `:172`

```php
public function __construct()
{
    $this->user = Authentication::authenticateSession();
}
```

The user is resolved once when the (shared) service is instantiated and never reset (no `ResetInterface`,
no `kernel.reset` tag):

- In persistent runtimes (FrankenPHP worker mode, RoadRunner, Swoole, `symfony/runtime` workers) the **first** request's
  user is reused for all following requests of that process → a user without `gee_see_all_job_runs` is evaluated with the
  permissions of whoever hit the process first (and vice versa) → **cross-user job run disclosure / denial**.
- If the service is created before the session is started (e.g. injected into a listener that runs early, or in the
  messenger worker) the user stays `null` for the whole process.

**Fix direction:** resolve the user per call (inject `Security` / `TokenStorageInterface`, or call
`Authentication::authenticateSession()` lazily) and implement `ResetInterface`; better: a voter (I-22).

---

## [ ] C-07 – Repository access checks are fail-open (`ownerId === null` ⇒ no restriction)

**Files:** `src/Repository/JobRunRepository.php:137-156`, `:164-182`, `:184-187`, `src/Agent/JobExecutionAgent.php:117-122`

```php
if ($ownerId !== null && !$this->permissionService->isAllowedToSeeAllJobRuns()) {
    $params['ownerId'] = $ownerId;
}
```

- `getJobRunById($id, …, null)` and `getJobRunsByUserId(null)` return **every** job run regardless of the current
  user's permissions. A caller that passes `$user?->getId()` (user not resolvable – C-06, expired session, API token
  without Pimcore user) gets all runs including `log`, `currentMessage` and the serialized job with
  `environmentData` (user input, possibly credentials/API keys passed to steps).
- The permission `gee_job_run_permission` ("may see job runs at all") is never checked by the repository.
- `isInteractionAllowed()` exists, but `cancelJobRun()` / `rerunJobRun()` do not enforce it – authorization is entirely
  left to external controllers.

Controllers live outside this bundle (studio backend / admin UI), so the impact depends on them – but the API invites
the mistake. **Fix direction:** split into explicit methods (`findForUser(UserInterface $user, …)` that applies
`gee_job_run_permission` + "see all" and `findAllUnrestricted()` for system code), never treat `null` as "no filter";
enforce interaction rights in the agent or via a voter (I-22).

---

## [ ] C-08 – No messenger routing for GEE messages → jobs run synchronously and never get past the first message

**Files:** `config/pimcore/config.yaml:9-21`, `src/EventSubscriber/JobExecutionSubscriber.php:34-40`,
`doc/19_Development_Tools_and_Details/08_Generic_Execution_Engine/README.md:19-21`

The bundle defines the `pimcore_generic_execution_engine` transport and the `generic.execution.engine.bus`, but no
`framework.messenger.routing` entry. The README states "Messages are dispatched via `pimcore_generic_execution_engine`
transport", yet nothing routes them there (and the docs never tell the user to add routing).

Without routing, `dispatch()` handles the message **synchronously** inside `startJobExecution()` (HTTP request /
console command). Progress is driven exclusively by `WorkerMessageHandledEvent` / `WorkerMessageFailedEvent`
(`JobExecutionSubscriber`), which are only dispatched by a messenger **worker** → after the first message nothing ever
continues the run → stays `running` forever; a handler exception is thrown straight into the caller (and no error log
is written).

**Fix direction:** add
`routing: { 'Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface': pimcore_generic_execution_engine }`
to `config/pimcore/config.yaml` (routing by interface is supported), and/or detect sync handling (no `SentStamp` on the
returned envelope) in `dispatchSelectedElements()` and fail loudly. Document the routing + `messenger:consume
pimcore_generic_execution_engine` requirement.
