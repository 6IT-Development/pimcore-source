# ApplicationLoggerBundle – TODO 3: Improvements

Scope: `bundles/ApplicationLoggerBundle/config`, `bundles/ApplicationLoggerBundle/src`
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-2-BUGS.md](TODO-2-BUGS.md)

Code quality, readability, modernisation (Symfony 7.4 / PHP 8.4 idioms) and logical clean-ups. Nothing here is
currently broken. IDs are stable – refer to them as `I-xx` in later sessions. Line numbers are from commit `7162d16812`.

---

## Symfony 7.4 modernisation

### [ ] I-01 – Migrate bundle to `AbstractBundle`
**Files:** `src/PimcoreApplicationLoggerBundle.php`, `src/DependencyInjection/PimcoreApplicationLoggerExtension.php`
Use `loadExtension()` / `prependExtension()` (with `import(..., prepend: true)` – fixes B-22) and `configure()` instead
of a separate `Extension` class. Check compatibility with `AbstractPimcoreBundle` first (it extends `Bundle`); if not
possible, at least keep the separate extension but use the prepend-aware loader.

### [ ] I-02 – Own configuration tree instead of `pimcore.applicationlog` in CoreBundle
**Files:** `src/Maintenance/LogArchiveTask.php`, `src/Maintenance/LogMailMaintenanceTask.php`
Both tasks read `Pimcore\Config` (ArrayAccess over the whole system config, untyped `array|null`). Define a
`pimcore_application_logger` config tree in this bundle (with BC mapping from `pimcore.applicationlog`), inject typed
scalars via `%parameters%` / `#[Autowire(param: ...)]`. Fix the `archive_treshold` typo there with a BC alias.

### [ ] I-03 – Attribute-based service configuration
**File:** `config/services.yaml`
- Maintenance tasks: `#[AutoconfigureTag('pimcore.maintenance.task', ['type' => '...'])]` (or Pimcore attribute if one
  exists) instead of YAML tags; drop the positional `- '@doctrine.dbal.default_connection'` (autowiring resolves it).
- Processor: implement `Monolog\Processor\ProcessorInterface` and/or explicitly `autoconfigure: false` – the comment
  (lines 20-23) says autoconfigure is disabled, but it is only disabled for the introspection processor.
- Inject a dedicated channel logger into `LogArchiveTask` via `#[WithMonologChannel('maintenance')]` / `#[Target]`.

### [ ] I-04 – Named autowiring alias for the application logger
**File:** `config/services.yaml:48-59`
Add `Psr\Log\LoggerInterface $applicationLogger: '@pimcore.app_logger'` so services can inject it without referring to
the concrete class or the static `getInstance()`.

### [ ] I-05 – Request DTO for the log grid
**File:** `src/Controller/LogController.php:57-143`
Replace manual `$requestSource->get*()` juggling (GET vs POST bag) with a readonly DTO + `#[MapQueryString]` /
`#[MapRequestPayload]` and validator constraints (limits, enum priority, date format). Fixes B-05 cleanly.

### [ ] I-06 – Use `ClockInterface` instead of `time()` / `new DateTime('now')`
**Files:** `src/Maintenance/LogArchiveTask.php:49,58,108`
Makes archive/retention logic testable and removes mixing of `time()`, `DateTime`, `DateTimeImmutable` and `Carbon`.

### [ ] I-07 – `executeStatement()` for DDL/DML
**Files:** `src/Installer.php:49,75`, `src/Maintenance/LogArchiveTask.php:62,77,91`,
`src/Maintenance/LogMailMaintenanceTask.php:106`
`executeQuery()` is meant for result sets; use `executeStatement()` (returns affected rows) for `CREATE`, `DROP`,
`INSERT … SELECT`, `DELETE`, `UPDATE`.

### [ ] I-08 – `ResetInterface` for the shared logger service
**File:** `src/ApplicationLogger.php`
Implement `Symfony\Contracts\Service\ResetInterface` (`reset()` clears component override, fileObject, relatedObject),
tag `kernel.reset`. Required for correct behaviour in Messenger workers (complements B-08).

---

## PHP 8.4 modernisation

### [ ] I-09 – Enums for priority and element type
**Files:** `src/Handler/ApplicationLoggerDb.php:68-87`, `src/Maintenance/LogMailMaintenanceTask.php:46-60`,
`src/ApplicationLogger.php:44,94-107`, `src/Processor/ApplicationLoggerProcessor.php:73-84`
- Use `Monolog\Level` (already a dependency) for priorities instead of string arrays / parsing `SHOW COLUMNS`
  (`LogMailMaintenanceTask` currently scrapes the ENUM definition and embeds quoted values into SQL).
- Introduce a `RelatedObjectType: string` backed enum (`object|asset|document`) or reuse
  `Pimcore\Model\Element\Service::getElementType()` everywhere.

### [ ] I-10 – `FileObject` as a proper immutable value object
**File:** `src/FileObject.php`
- `final class` with `protected` promoted properties is meaningless → `private readonly` or PHP 8.4 asymmetric
  visibility `public private(set) string $filename`.
- Constructor performs I/O (storage write) and uses the static `Storage::get()` service locator → move persisting into a
  `FileObjectWriter` service; keep `FileObject` a plain value object.
- `getSystemPath(): ?string` can never return `null` after construction; it also returns the same as `getFilename()`
  minus the root strip → deprecate one of them (`#[\Deprecated]`, PHP 8.4).
- `uniqid('fileobject_', true)` → `bin2hex(random_bytes(16))` or `Symfony\Component\Uid\Uuid::v7()`.
- `catch (FilesystemException|UnableToWriteFile)` – `UnableToWriteFile` already implements `FilesystemException`.
- `Logger::warn()` → `Logger::warning()` (or injected PSR logger).

### [ ] I-11 – Simplify `logException()` signature
**File:** `src/ApplicationLogger.php:280-301`
`?string $priority = 'alert'` + `if (is_null(...))` → non-nullable `string|Level $priority = Level::Alert`
(consistent with `logExceptionObject()`), rename to `$level`. Mark old parameter names via `#[\Deprecated]` if BC matters.

### [ ] I-12 – Use `#[\Override]` on overridden/implemented methods
**Files:** `ApplicationLogger` (PSR-3 methods), `ApplicationLoggerDb::write()`, `Installer::install()/uninstall()`,
`PimcoreApplicationLoggerBundle::getPath()/getInstaller()`, maintenance `execute()`.

### [ ] I-13 – Small idiom updates
- `LogMailMaintenanceTask:42-44` `array_walk` with by-ref closure → `array_filter(array_map(trim(...), ...))`.
- `LogMailMaintenanceTask:57-60` manual loop → `array_slice($levels, 0, $logLevel + 1)`.
- `LogMailMaintenanceTask:74-100` hand-rolled batching → `array_chunk($rows, 100)`.
- `ApplicationLogger:178` `in_array()` → strict `in_array(..., true)` or a `const array` + `isset`.
- `ApplicationLogger:177` `!empty($logCall['class']) && $logCall['class'] === __CLASS__` → `($logCall['class'] ?? null) === self::class`.
- `ApplicationLogger:131-137` → `$relatedObject = $context['relatedObject'] ?? $this->relatedObject;`.
- `LogArchiveTask:84-88` nested `if`s → `if ($filePath !== null && $storage->fileExists($filePath))`.
- `ApplicationLoggerProcessor:63-65` `if (isset(...)) unset(...)` → plain `unset()`.

---

## Architecture / logic

### [ ] I-14 – Remove static `Pimcore::getContainer()` / `Db::get()` / `Storage::get()` usage
**Files:** `ApplicationLogger::getInstance()`, `ApplicationLoggerDb::getComponents()/getPriorities()` (static,
use `Db::get()` even though the instance has an injected `$db`), `Installer`, `FileObject`, `LogArchiveTask:47`,
`LogController:177`.
Inject `Connection` / `FilesystemOperator` (`#[Autowire(service: 'pimcore.application_log.storage')]`). Move the read
queries out of the handler into a small `ApplicationLogRepository` used by the controller and tasks.
Mark `getInstance()` as `#[\Deprecated]` in favour of DI.

### [ ] I-15 – Deduplicate logic between `ApplicationLogger` and `ApplicationLoggerProcessor`
Both implement file-object normalisation, related-object resolution, and `normalizeFilename()` separately and
differently (`preg_replace` anchored vs `str_replace` anywhere; `Service::getElementType()` vs concrete `instanceof`
chain). Let `ApplicationLogger` only enrich context and rely on the processor (it is always attached to the DB handler),
or extract a shared `ContextNormalizer`.

### [ ] I-16 – Avoid double backtrace work
`ApplicationLogger::resolveLoggingSource()` runs `debug_backtrace()` and then `IntrospectionProcessor` runs another one
for every record (`config/services.yaml:32-46`), although `source` is already set. Either rely on the introspection
processor (skip partial already configured) or skip it when `source` exists. Also `debug_backtrace()` without a limit on
deep stacks is costly.

### [ ] I-17 – Document processor ordering
**File:** `config/services.yaml:43-46`
`pushProcessor()` prepends, so execution order is the *reverse* of the listed order (introspection → app processor →
psr message). This is correct but non-obvious; add a comment or use `priority` via `#[AsMonologProcessor]`.

### [ ] I-18 – Strict handling of `addWriter()`
**File:** `src/ApplicationLogger.php:71-82`
`object $writer` that is neither `HandlerInterface` nor `LoggerInterface` is silently ignored → type it as
`HandlerInterface|LoggerInterface`. The `instanceof LoggerInterface` check in `log()` (line 152) then becomes redundant.

### [ ] I-19 – Never throw from the logger
**File:** `src/ApplicationLogger.php:126`
Throwing `Psr\Log\InvalidArgumentException` for an invalid `fileObject` makes a log call fatal. Prefer dropping the value
and logging a warning (related to C-05 / B-14).

### [ ] I-20 – Unused / misleading schema & naming
- Column `info` is never written nor read (`Installer`, archive schema, `ApplicationLoggerDb::write()`).
- `ApplicationLoggerDb::getPriorities()` loop variable `$priorityNumber` holds a priority *name*.
- `getComponents()` uses `GROUP BY` where `SELECT DISTINCT` states intent.
- Comment `// ClassName->methodName():line` does not match the produced format `Class::method:line`
  (`ApplicationLogger:192`, `ApplicationLoggerProcessor:109`).
- Snake-case local `$archive_threshold` (`LogArchiveTask:56`) vs camelCase elsewhere.
- `relatedObjectType` / `relatedObject` property type `AbstractObject|Document|int|Asset|null` → `ElementInterface|int|null`.

### [ ] I-21 – Single permission check in the controller
**File:** `src/Controller/LogController.php`
Permission is checked in `onKernelControllerEvent()` **and** again in every action. Keep one (the event, implemented
via `checkPermission()` – fixes B-03), remove the per-action calls.

### [ ] I-22 – Drop `QueryParams` (AdminBundle) dependency
**File:** `src/Controller/LogController.php:25,75-84`
Parse ExtJS `sort` JSON (`[{"property":..,"direction":..}]`) locally with a column whitelist (see B-06). Aligns with the
branch goal of removing admin-bundle coupling.

### [ ] I-23 – Hardening of the file-object response
**File:** `src/Controller/LogController.php:185-192`
Add `charset=UTF-8`, `X-Content-Type-Options: nosniff` and `Content-Disposition: inline; filename=...`. Consider
`HeaderUtils`/`BinaryFileResponse`-like semantics; the stream is opened before the response is sent – open it inside
the callback.

### [ ] I-24 – Escape LIKE wildcards in message search
**File:** `src/Controller/LogController.php:109-111`
`%` and `_` in the user's search text act as wildcards. Use `addcslashes($message, '%_\\')`.

### [ ] I-25 – Use immutable date types in the controller
**File:** `src/Controller/LogController.php:198-209`
`DateTime` + `Types::DATETIME_MUTABLE` → `DateTimeImmutable` + `Types::DATETIME_IMMUTABLE`. The `@throws
DateMalformedStringException` is unreachable after the regex check; use `DateTimeImmutable::createFromFormat()` and
return `null` on failure.

### [ ] I-26 – Count query cleanup
**File:** `src/Controller/LogController.php:117-122`
The cloned count query still carries `ORDER BY`; reset it (`resetOrderBy()` in DBAL ≥ 3.8) and reuse the base builder
before paging is applied instead of undoing `setMaxResults`/`setFirstResult`.

### [ ] I-27 – Memory-safe maintenance queries
**Files:** `src/Maintenance/LogArchiveTask.php:81`, `src/Maintenance/LogMailMaintenanceTask.php:70`
Use `iterateAssociative()` / chunking and `WHERE fileobject IS NOT NULL` instead of loading whole result sets.

### [ ] I-28 – ARCHIVE engine availability
**File:** `src/Maintenance/LogArchiveTask.php:62-75`
The `ARCHIVE` storage engine is not available on all MySQL/MariaDB setups (managed DBs, some Galera builds); with
`NO_ENGINE_SUBSTITUTION` the `CREATE TABLE` fails. Make the engine configurable or detect support
(`information_schema.ENGINES`).

### [ ] I-29 – Installer via Doctrine Schema / migrations
**File:** `src/Installer.php:45-69`
Replace raw `CREATE TABLE` SQL with a `Schema` definition (shared with the archive table – see C-02) so DBAL handles
platform differences and the two schemas cannot drift again. Use injected `Connection` instead of `Db::get()`.

### [ ] I-30 – Minor config file hygiene
- `config/admin-classic.yaml`, `config/pimcore/routing.yaml`: missing trailing newline.
- `config/pimcore/routing.yaml`: `options.expose: true` exposes **all** controller routes to FOSJsRouting; fine today,
  but prefer per-route `options: ['expose' => true]` in the `#[Route]` attributes.
- `config/services.yaml`: `ApplicationLoggerDb` is `public: true` – check whether anything still fetches it from the
  container directly (only `getInstance()` does); could be private after I-14.

### [ ] I-31 – Tests
No tests exist for this bundle. Minimum set: `LogArchiveTask` date/folder calculation (C-01), archive insert with long
messages (C-02), `ApplicationLogger` with `Stringable` (C-03), mail escaping (C-04), source resolution for closures
(B-10), controller filter/paging/sorting (B-01, B-02, B-05, B-06).
