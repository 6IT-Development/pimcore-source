# CoreBundle – TODO 1: Critical errors

Scope: **(1)** `bundles/CoreBundle/src/Command/**`, `bundles/CoreBundle/config/commands.yaml` (+ `config/extensions.yaml:33`)
**(2)** `src/DataCollector/**`, `src/PimcoreCoreBundle.php` and related: the compiler passes it registers
(`src/DependencyInjection/Compiler/*`), `src/DependencyInjection/PimcoreCoreExtension.php`, `config/profiler.yaml`,
`config/routing_dev.yaml`, `config/services_symfony_workflow.yaml`, `config/cache.yaml`, `templates/Profiler/*`
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-2-BUGS.md](TODO-2-BUGS.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

These items cause **data loss or corruption, SQL injection, a command that cannot do its one job, or wrong exit codes
that hide failures from cron/CI**.
IDs are stable – refer to them as `C-xx` in later sessions. Line numbers are from commit `7cb7841b76`.

> Notes
> - No hard Symfony 7.4 / PHP 8.4 incompatibility was found in the commands. None of them override the deprecated
>   `Command::getDefaultName()` / `getDefaultDescription()` and none call `Application::add()`. All files already
>   declare `strict_types`. The remaining compatibility work is modernisation and is listed in TODO-3.
> - Pimcore's default DBAL config runs `SET sql_mode = ''` (`config/pimcore/default.yaml:148`). Invalid ENUM values are
>   therefore **silently stored as `''`** and do not throw (relevant for C-05).

---

## [ ] C-01 – Asset folder path concatenated into raw SQL (stored SQL injection / SQL error)

**Files:**
- `src/Command/Asset/AddToUpdateTaskQueueCommand.php:81`
- `src/Command/Asset/GenerateChecksumCommand.php:78`
- `src/Command/Asset/RemoveCustomSettingCommand.php:70`
- `src/Command/LowQualityImagePreviewCommand.php:85`
- `src/Command/ThumbnailsImageCommand.php:132`
- `src/Command/ThumbnailsVideoCommand.php:77`

```php
$conditions[] = "path LIKE '" . Helper::escapeLike($parent->getRealFullPath()) . "/%'";
```

- `escapeLike()` only escapes `_` and `%`. It does **not** escape quotes.
- Asset keys may contain `'`. `Element\Service::getValidKey()` only strips `#?*:\<>|"%+` for assets
  (`models/Element/Service.php:963`).
- A folder named `Photos O'Neil` makes every one of these commands fail with a SQL syntax error when it is passed as
  `--parent`.
- A backend user who can rename folders can inject SQL into the `WHERE` clause. Example: `x' OR '1'='1`. The injected
  SQL runs with the operator's DB credentials. With `RemoveCustomSettingCommand` it widens a **write** operation to
  every asset.

**Fix direction:** bind the value: `$conditions[] = 'path LIKE ?'; $conditionVariables[] = Helper::escapeLike($path) . '/%';`.
Do it once in the shared filter helper (see I-11). In `ThumbnailsVideoCommand` also start passing
`$conditionVariables` to `setCondition()`.

---

## [ ] C-02 – `RemoveCustomSettingCommand` skips assets (OFFSET pagination over a shrinking result set)

**File:** `src/Command/Asset/RemoveCustomSettingCommand.php:64-102`

- The listing filters on `customSettings LIKE '%"<name>"%'`. Each processed asset loses the setting and so drops out
  of the result set.
- The loop then advances `OFFSET` by 10 anyway (`setOffset($i * $perLoop)`). With 25 matching assets it processes
  1–10, then rows 11–20 of the **remaining** 15 (i.e. original 21–25). It never processes the original 11–20.
- The command reports nothing and returns `0`. About half of the assets keep the setting the operator meant to remove.
- There is no `ORDER BY` either (see B-02), so even the order is undefined.

**Fix direction:** keyset pagination (`id > :lastId ORDER BY id LIMIT n`), or always read `OFFSET 0` while the
condition still matches. Add a final count/summary.

---

## [ ] C-03 – `pimcore:maintenance-mode --disable` cannot run while maintenance mode is active

**File:** `src/Command/MaintenanceModeCommand.php:50-54`, related `lib/Console/Application.php:69-75`

```php
protected function initialize(InputInterface $input, OutputInterface $output): void
{
    $input->setOption('ignore-maintenance-mode', true);
```

- The maintenance check runs in the `ConsoleEvents::COMMAND` listener. Symfony 7.4 dispatches that event in
  `Application::doRunCommand()` **before** `$command->run()`, and only `run()` calls `initialize()`.
- The option is therefore set too late. While maintenance mode is on, the command throws
  `In maintenance mode - set the flag --ignore-maintenance-mode to force execution!`.
- The one command meant to turn maintenance mode off fails in exactly the situation it exists for. The only
  workaround is to pass `--ignore-maintenance-mode` by hand.

**Fix direction:** let the Application listener skip the check for this command, e.g.
`$event->getCommand() instanceof MaintenanceModeCommand` or a marker interface/attribute such as
`#[IgnoresMaintenanceMode]`. Then remove the `initialize()` override.

---

## [ ] C-04 – `DeleteClassificationStoreCommand`: irreversible mass delete with no confirmation, no existence check and no transaction

**File:** `src/Command/DeleteClassificationStoreCommand.php:45-90`

- One mistyped ID immediately deletes all keys, groups, collections and object data of that store. There is no
  confirmation prompt, no `--force` and no `--dry-run`.
- It never checks that the store exists. `is_numeric()` also accepts `1.0`, `1e0` and ` 1`.
- There is no transaction. If any statement fails half way (lock wait timeout, FK error), the store is left
  partially deleted.
- Classes whose classification-store field still references the deleted store are not checked or reported.
- It prints SQL with `echo` instead of the console output. That bypasses verbosity and `--quiet`, and breaks output
  decorators.

**Fix direction:** load `StoreConfig::getById()` (fail with `INVALID` if missing). Ask for confirmation (skip it only
with `--force` / `-n`). Wrap the DML in `$db->transactional()`. List the affected classes. Most manual deletes are
redundant because of `ON DELETE CASCADE` FKs (see I-17).

---

## [ ] C-05 – `pimcore:documents:cleanup` corrupts the document tree

**File:** `src/Command/Document/CleanupCommand.php:53-100`

- It deletes from `documents` with raw SQL. Children of the deleted documents, properties, dependencies, versions,
  `tree_locks`, `documents_translations`, search/notes rows etc. are left as orphans. No `Document::delete()`, no
  events, no cache invalidation.
- If the `DELETE` fails, the exception is swallowed (`:77-79`) and the command **still shrinks the `type` ENUM**
  (`:85`). With Pimcore's `sql_mode = ''`, every remaining row of a removed type silently gets `type = ''`. Those
  documents can no longer be loaded and the corruption is not reported.
- The DDL/DML is not confirmed, not transactional (MySQL DDL cannot be) and always returns `Command::SUCCESS`.

**Fix direction:** abort (return `FAILURE`) when the delete fails, and never alter the ENUM while rows of the type
still exist. Delete through the model API (or at least cascade children by path), clear the cache, ask for
confirmation, and return a proper exit code.

---

## [ ] C-06 – Error paths call `exit` (exit code 0, skipped TERMINATE events, maintenance mode left on)

**Files:**
- `exit;` (exit code **0**): `Asset/GenerateChecksumCommand.php:81`, `Asset/RemoveCustomSettingCommand.php:73`,
  `LowQualityImagePreviewCommand.php:88`, `MysqlToolsCommand.php:55`, `ResetPasswordCommand.php:70`
- `exit(1);`: `Document/GeneratePagePreviews.php:78`, `ThumbnailsImageCommand.php:135`, `ThumbnailsVideoCommand.php:80`

- A bare `exit;` ends the process with status **0**. Cron, CI and deployment scripts see success for an invalid
  folder ID, an unknown user, a missing mode, etc.
- `exit` bypasses `Application::doRunCommand()`, so `ConsoleEvents::TERMINATE` / `ERROR` never fire. When the command
  runs with the global `--maintenance-mode` flag, the TERMINATE listener (`lib/Console/Application.php:101-114`) that
  **deactivates maintenance mode never runs**, and the whole installation stays in maintenance mode.
- In `ThumbnailsImageCommand` / `ThumbnailsVideoCommand` the exit happens inside `fetchItems()`, after the
  Parallelization lock was acquired.

**Fix direction:** return `Command::FAILURE` / `Command::INVALID` from `execute()`. From helpers like `fetchItems()`,
throw an exception (e.g. `Symfony\Component\Console\Exception\InvalidArgumentException`) and let the Application
handle it. Add a grep/PHPStan rule that bans `exit`/`die` in `src/Command`.

---

# Scope 2 – `DataCollector` / `PimcoreCoreBundle` (+ compiler passes, extension, related config)

> Notes
> - No hard Symfony 7.4 / PHP 8.4 incompatibility was found. `PimcoreDataCollector` does not override the
>   `__sleep()`/`__wakeup()` methods that Symfony 7.4 deprecated. The routing loader override still extends the 7.4
>   `AttributeRouteControllerLoader`. The `GuardListener`, `Workflow` and `Definition` constructor arguments built in
>   `WorkflowPass` match 7.4.
> - Every pass in `PimcoreCoreBundle::build()` uses the default `TYPE_BEFORE_OPTIMIZATION`, priority `0`. So they run
>   **after** all Symfony/Monolog passes of the same type and **after** autoconfiguration (priority 100). Several
>   findings below come from this implicit ordering (C-07, B-29, B-31). The cross-cutting fix is I-35.
> - Symfony behaviour was checked against a Symfony 7.4 vendor tree (FrameworkBundle, Cache, Workflow, MonologBundle).
>   It was not checked at runtime with `debug:container`. Each item says what to run to confirm it.

---

## [ ] C-07 – `CacheFallbackPass` drops namespace and `default_lifetime` of every pool derived from `cache.app`

**Files:** `src/DependencyInjection/Compiler/CacheFallbackPass.php:37-56`, `config/cache.yaml:31-35`,
`src/PimcoreCoreBundle.php:65`

```php
foreach ($container->findTaggedServiceIds('cache.pool') as $id => $arguments) {
    $cacheDef = $container->findDefinition($id);
    if ($cacheDef instanceof ChildDefinition && $cacheDef->getParent() === 'cache.app') {
        $cacheDef->setArguments([]);                 // wipes what CachePoolPass just set
        $cacheDef->setParent('pimcore.cache.pool.app'); // ProxyAdapter(pimcore.cache.pool, 'app')
    }
}
```

- FrameworkBundle registers `CachePoolPass` with priority **32**, so it runs **before** this pass (priority 0).
  `CachePoolPass` has already written each pool's own namespace (a hash of the pool name) and its
  `default_lifetime` into the arguments. `setArguments([])` throws them away.
- Every child pool then inherits `pimcore.cache.pool.app`'s arguments: namespace `'app'`, lifetime `0`.
  This hits every `framework.cache.pools.*` with `adapter: cache.app` (the default adapter) and Symfony's own children
  of `cache.app`, e.g. `cache.messenger.restart_workers_signal`. It only runs when `cache.app` is still the default
  filesystem adapter, which is the default setup.
- Consequences:
  - **All of these pools share one key space.** Two pools that store the same key (`product_123`, `config`, …)
    overwrite and read each other's values, so cached data is silently wrong.
  - `default_lifetime` is ignored. Items never expire unless the code sets a TTL, so stale data stays cached.
  - `cache:pool:clear <one pool>` clears `cache.app` and **every** sibling pool, because they all share namespace
    `app`.

**Verify:** define `framework.cache.pools.foo: { default_lifetime: 60 }` and run
`bin/console debug:container foo --show-arguments`. Expected: namespace hash and `60`. Actual (per code): only
`@pimcore.cache.pool`, `'app'`.

**Fix direction:**
- Keep the per-pool arguments. Run the pass **before** `CachePoolPass` (priority > 32) and model
  `pimcore.cache.pool.app` like `cache.adapter.psr6`: a `ProxyAdapter` with a `provider` pointing at `pimcore.cache.pool`.
  `CachePoolPass` then fills in the namespace and `default_lifetime` per pool.
- Alternatively, rebuild each child as `ProxyAdapter(pimcore.cache.pool, <pool namespace>, <default_lifetime>)`.
- Add a ContainerBuilder unit test for both pools (I-42).
- Also fix B-32 (alias check) in the same pass.
