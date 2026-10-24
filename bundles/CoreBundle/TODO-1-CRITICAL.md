# CoreBundle – TODO 1: Critical errors

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
`lib/Config/LocationAwareConfigRepository.php:289-329`, `lib/Twig/Sandbox/SecurityPolicy.php`)
**(5)** `src/Controller/**`, `src/OptionsProvider/**`, `src/Request/**` and related config: `config/routing.yaml`,
`config/services.yaml:58-62,116-118`, plus the model code they drive (`models/Asset/WebDAV/*`,
`models/Asset/Service.php` thumbnail delivery, `SelectionProviderTrait`, `AbstractObject::getById()`)
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-2-BUGS.md](TODO-2-BUGS.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

These items cause **data loss or corruption, SQL injection, a command that cannot do its one job, or wrong exit codes
that hide failures from cron/CI**, in Scope 3 **content leaking between visitors, access-control/maintenance
bypass and an open redirect**, in Scope 4 **lost configuration data and a Twig sandbox escape**, and in Scope 5 **asset permission bypass and
CSRF via WebDAV, and anonymous disk/CPU exhaustion through the thumbnail route**.
IDs are stable – refer to them as `C-xx` in later sessions. Line numbers are from commit `7cb7841b76` (Scope 3:
`43948f45d7`, Scope 4: `402145a525`, Scope 5: `1a059513b7`, same code).

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

---

# Scope 3 – `EventListener` (+ `config/event_listeners.yaml`)

> Notes
> - No hard Symfony 7.4 / PHP 8.4 incompatibility was found. Every listener uses the 7.x event classes
>   (`RequestEvent`, `ResponseEvent`, `ControllerEvent`, `ViewEvent::$controllerArgumentsEvent`, …), `isMainRequest()`
>   and typed class constants. There is no `implicit nullable` parameter (deprecated in 8.4). The only file without
>   `declare(strict_types=1)` is `Frontend/ElementListener.php` (I-47).
> - Line numbers are from commit `43948f45d7`. The scanned code is identical to `7cb7841b76`.
> - Symfony behaviour was checked against a `symfony/http-kernel` / `twig-bridge` **v7.4.21** vendor tree. It was not
>   run against a live app. Each item says what to check.
> - **Long-running runtimes:** `composer.json` requires `symfony/runtime ^7.4`, so the app can run under
>   FrankenPHP worker mode, RoadRunner or Swoole. There, one kernel and its services handle many requests. **No service
>   in this scope implements `ResetInterface` or carries a `kernel.reset` tag** (grep: no `kernel.reset` in the repo).
>   C-08 lists what breaks. Under classic PHP-FPM / mod_php these items are harmless.
> - Request listener order (REQUEST priority, high → low; Symfony core in *italics*): `RoutingListener` 512,
>   `StaticPageGeneratorListener` 510, *SessionListener 128*, `MaintenancePageListener` 126, `FullPageCacheListener` 120,
>   *RouterListener 32*, `PimcoreContextListener` 24, `DocumentFallbackListener` 20, *LocaleListener 16*,
>   *Firewall 8*, `LocaleListener` 1, `EditmodeListener` / `BlockStateListener` / `TranslationDebugListener` /
>   `OutputTimestampListener` 0, `WebDebugToolbarListener` -118. Several items come from this order (C-09, B-39; RESPONSE order: B-46).

---

## [ ] C-08 – Request state lives on shared services and is never reset (worker runtimes: stale responses, broken sessions, disabled cache)

**Files:**
- `src/EventListener/Frontend/FullPageCacheListener.php:50-60, 70-91, 279, 283-288`
- `src/EventListener/ResponseStackListener.php:41-50` + `lib/Http/ResponseStack.php:58-74`
- `src/EventListener/WebDebugToolbarListener.php:87-92`
- `src/EventListener/TranslationDebugListener.php:56-60`
- `src/EventListener/Frontend/RoutingListener.php:121` + `models/Site.php:166-193` (no way to unset `$currentSite`)
- `src/EventListener/Frontend/DocumentFallbackListener.php:48, 111, 145-153`
- `src/EventListener/Frontend/GlobalTemplateVariablesListener.php:41, 71-73`
- `src/EventListener/Frontend/LocaleListener.php:34, 50-51, 78-80`

What breaks when one worker process serves many requests:
- **`ResponseStack` is never emptied.** `getLastResponse()` reads with `end()` and does not pop. After an areabrick
  `action()` pushes one response (e.g. a redirect after a form post), `ResponseStackListener` **replaces the response of
  every later main request** in that worker with it. Every visitor then gets the same redirect or another user's
  page.
- **`FullPageCacheListener`:**
  - `$stopResponsePropagation` is set to `true` on the first cache hit and never cleared. From then on,
    `stopPropagationCheck()` (RESPONSE priority 100) stops **every** later response before priority 100. Symfony's
    `SessionListener` (-1000) and the security `ContextListener` then never run. The session is not saved and no session
    cookie is sent, so **frontend logins and flash messages silently stop working**. Editmode injection, the
    `ResponseHeader` attribute, `Content-Language` and the cache save itself are skipped as well.
  - `$enabled` is cleared by `disable()` (any POST, `Authorization` header, cookie, session …). `onKernelRequest()`
    returns at once when `!$this->isEnabled()` (line 120), so after one POST **the full page cache is off for the rest
    of the worker's life**.
  - `$defaultCacheKey`, `$lifetime`, `$disableReason` and `$addExpireHeader` leak too. A request that returns before
    line 258 (e.g. `pimcore_outputfilters_disabled` in debug mode) is saved under the **previous request's key**:
    cache poisoning.
- **`WebDebugToolbarListener`** calls `$eventDispatcher->removeSubscriber()` and never adds it back, so the toolbar is
  gone for all later requests (dev only).
- **`TranslationDebugListener`** calls `Translator::setDisableTranslations(true)` on the shared translator. One
  `?pimcore_debug_translations=1` request in debug mode, or from a logged-in admin, **disables translations for every
  later visitor** in that worker.
- **`Site::$currentSite`** is static and is only ever set. A request to the main domain after a request to a site host
  still sees `Site::isSiteRequest() === true`, which gives a wrong site root, wrong main-domain redirect
  (`RoutingListener::handleMainDomainRedirect()`), wrong error page and wrong static-page path.
- `DocumentFallbackListener::$fallbackDocument` is only cleared when the controller event is reached. A main request
  that ends earlier (exception, response from a request listener, `PublicServicesController` early return at line 147)
  leaves it set for the next request. `GlobalTemplateVariablesListener` leaves the Twig `document` / `editmode` globals
  of the last request in place. `LocaleListener::$lastLocale` skips `setlocale()` and is used for `Content-Language`.

**Verify:** run two requests through one kernel (`$kernel->handle()` twice, or FrankenPHP worker): first an areabrick
form post or a full page cache hit, then a plain page. Expected: independent responses. Actual (per code): the second
gets the stale response / no session cookie.

**Fix direction:**
- Prefer **request attributes** over service properties for per-request state: the fallback document, FPC
  enabled/key/reason/stop flag, last locale.
- Where state must stay on the service, implement `Symfony\Contracts\Service\ResetInterface` and let autoconfiguration
  add `kernel.reset` (`FullPageCacheListener`, `ResponseStack`, `GlobalTemplateVariablesListener`, `LocaleListener`,
  `DocumentFallbackListener`).
- `ResponseStackListener`: `pop()` the response it sends.
- `WebDebugToolbarListener`: do not remove the subscriber. Use the public
  `SymfonyWebDebugToolbarListener::setMode(DISABLED)` and restore the previous mode on `kernel.finish_request` /
  `reset()`.
- Add `Site::resetCurrentSite()` and call it at the start of `RoutingListener::onKernelRequest()`.
- `TranslationDebugListener`: reset the flag on `kernel.finish_request` / reset.
- Add a functional test that sends two requests through one kernel (I-54).

---

## [ ] C-09 – `StaticPageGeneratorListener` persists any response as a public static file and serves it ahead of maintenance mode and the firewall

**Files:** `src/EventListener/Frontend/StaticPageGeneratorListener.php:66-67, 71-123, 125-153, 177-187`

Writing (`onKernelResponse`, RESPONSE -120): the first dynamic render of a page with "static generator enabled" is
written to `document_static` storage. There is **no check of the response itself**:
- **No status check.** If the controller throws, `ResponseExceptionListener` renders the error page into the response
  (status 404/500), and the document attribute still points to the requested page. **The error page is saved as that
  page's static file** and then served with status **200** to everybody until the document is saved again.
- **No session / user / cookie / `no-store` check** (compare `FullPageCacheListener::responseCanBeCached()` +
  `SessionStatus`). Content for the current visitor is written into the shared file: logged-in frontend user name,
  CSRF tokens, flash messages, `Set-Cookie`. A logged-in admin's
  `?pimcore_override_output_timestamp=<future>` (`OutputTimestampListener`) is not caught by `checkValidRequest()`
  either, so **future/unpublished scheduled content is frozen into the public file**.
- `getContent()` of a `StreamedResponse` is `false` and is passed on as the page content.

Serving (`onKernelRequest`, REQUEST **510**):
- It runs **before** `SessionListener` (128), `MaintenancePageListener` (126) and the security firewall (8).
  - Static pages are served **while maintenance mode is on**.
  - **`access_control` rules on those paths are bypassed.** A page that was generated while a user was logged in
    (see above) is served to anonymous visitors.
- It does not check the Pimcore context (only `checkValidRequest()`).

**Fix direction:**
- When writing, only accept `200`, no `Set-Cookie`, no `no-store`/`private` set by the app, no started session with
  data (reuse `SessionStatus::isDisabledBySession()`), no authenticated user, and no output timestamp override. Put
  these checks in a shared "is publicly cacheable" helper used by FPC and the static generator.
- When serving, run after the firewall and maintenance listener (priority < 8), or at least after
  `MaintenancePageListener`. Document that static pages must not sit behind `access_control`.
- Add tests (I-54).

---

## [x] C-10 – `RoutingListener` legacy `/app.php` redirect is an open redirect

**File:** `src/EventListener/Frontend/RoutingListener.php:82, 130-143`

```php
if (preg_match('@^/app\.php(.*)@', $path, $matches) && $request->getMethod() === 'GET') {
    $redirectUrl = '/' . ltrim($matches[1], '/');
    $event->setResponse(new RedirectResponse($redirectUrl, 301));
```

- `$path` is `urldecode($request->getPathInfo())`. `GET /app.php/%5Cevil.example` becomes `/app.php/\evil.example`,
  which redirects to `Location: /\evil.example`. Browsers (WHATWG URL parser) treat `\` as `/` for http(s), so this is
  `//evil.example`, an **off-site redirect from your domain** (phishing, OAuth `redirect_uri` chains). Tab/newline
  variants (`%09`) work the same way, because `ltrim` only strips `/`.
- The redirect is `301`, so browsers and proxies cache it.
- `app.php` is the Symfony ≤3 front controller and has no meaning in a Symfony 7.4 app.

**Verify:** whether the web server forwards `/app.php/...` to `index.php`. Apache with the usual
`RewriteCond %{REQUEST_FILENAME} !-f` does. The recommended nginx config (`location ~ \.php$ { return 404; }`) does
not. `curl -sI 'https://host/app.php/%5Cexample.com'`.

**Fix direction:** remove `handleFrontControllerRedirect()`. If it must stay: build the target from
`$request->getBaseUrl()` + a path that is normalised and checked to start with exactly one `/` and contain no `\`, tab
or newline. Use 308/302.

---

# Scope 4 – `DependencyInjection` (+ `config/pimcore/*.yaml`, extension-loaded `config/*.yaml`)

> Notes
> - No hard Symfony 7.4 / PHP 8.4 incompatibility was found. The tree uses no removed builder API, and
>   `ConfigurableExtension`, `PrependExtensionInterface` and `registerAttributeForAutoconfiguration()` are all current
>   in 7.4. All files declare `strict_types`. One PHP 8.4 function is already used (`array_any()`,
>   `Configuration.php:1813`).
> - The compiler passes were re-scanned. Their findings from Scope 2 (C-07, B-28 … B-33, I-31 … I-42) still hold and are
>   not repeated. New pass findings are B-57 and I-61.
> - Symfony behaviour was checked against a **symfony/config v7.4.17** / framework-bundle v7.4.21 vendor tree. B-54,
>   B-55 and the omitted-key part of B-57 were reproduced with a small `Processor` script (no Pimcore kernel). Each item
>   says what to run to confirm it in the app.
> - Line numbers are from commit `402145a525`.

---

## [ ] C-11 – `ConfigurationHelper` prefixes the project root to an absolute config directory (admin-saved configuration written to the wrong place)

**Files:** `src/DependencyInjection/ConfigurationHelper.php:44-49`, `src/DependencyInjection/Configuration.php:137-160`,
`lib/Bootstrap.php:183-201,219`, `lib/Kernel.php:87-131`, `lib/Config/LocationAwareConfigRepository.php:238`
(+ same helper in `CustomReportsBundle` and `StaticRoutesBundle` `Configuration.php`)

```php
//BC reasons: Remove this check in Pimcore 12
if (!str_starts_with($folder, PIMCORE_PROJECT_ROOT)) {
    $folder = PIMCORE_PROJECT_ROOT . $folder;
}
```

- Every caller passes `PIMCORE_CONFIGURATION_DIRECTORY . '/<key>'`, which is **already absolute**
  (`PIMCORE_PRIVATE_VAR . '/config'`). `Bootstrap` lets you override `PIMCORE_PRIVATE_VAR` /
  `PIMCORE_CONFIGURATION_DIRECTORY` through `$_SERVER`/env or `config/pimcore/constants.php`. This is the usual way to
  put `var/` on a persistent or shared volume (containers, multi-node).
- When that directory is **outside** the project root, e.g. `/mnt/shared/var`, the default `write_target` directory
  becomes `/var/www/html/mnt/shared/var/config/image_thumbnails`, which is inside the code directory:
  - the admin UI writes image/video thumbnail definitions, document types, predefined properties/metadata,
    perspectives, custom views, custom layouts, select options and **system settings** (plus custom reports and static
    routes) into that ephemeral path (`LocationAwareConfigRepository.php:238`);
  - `Kernel::registerContainerConfiguration()` reads them back from the same path, so everything **looks fine until
    the next deploy, image rebuild or other node**. Then all of it is gone, and the files in the real persistent
    directory are never read;
  - on a read-only code mount, every save fails instead.
- `str_starts_with` without a trailing separator also treats `/app-shared/...` as inside `/app`. That case is
  harmless, but it shows the check is a string test, not a path test.

**Verify:** `PIMCORE_PRIVATE_VAR=/tmp/pvar bin/console debug:config pimcore config_location.image_thumbnails`. Expected:
`/tmp/pvar/config/image_thumbnails`. Actual (per code): `<project>/tmp/pvar/config/image_thumbnails`.

**Fix direction:** only prefix **relative** paths (`Symfony\Component\Filesystem\Path::isAbsolute()` /
`Path::makeAbsolute($folder, PIMCORE_PROJECT_ROOT)`). Drop the "BC" branch. Add an upgrade note: projects that already
have data under the wrongly prefixed directory must move it. Cover the helper with a unit test (I-64).

---

## [x] C-12 – Twig sandbox allows every method/property, and `default.yaml` exposes the service container to all templates (sandbox escape from an email subject)

**Files:** `lib/Twig/Sandbox/SecurityPolicy.php:93-105`, `config/pimcore/default.yaml:125-131` (`twig.globals.container`),
`config/templating_twig.yaml:75-83`, `src/DependencyInjection/Configuration.php:1973-2001`,
`src/DependencyInjection/PimcoreCoreExtension.php:77-80`, `lib/Mail.php:593-624,642,671,694`,
`models/DataObject/ClassDefinition/Layout/Text.php:126`

```php
public function checkMethodAllowed($obj, $method): void   { /* do not perform any checks */ }
public function checkPropertyAllowed($obj, $property): void { /* do not perform any checks */ }
```
```yaml
twig:
    globals:
        container: '@service_container'
```

- The sandbox (`getTwigEnvironment(true)`) is used for **email subjects and bodies** (`Mail::renderParams()`) and for
  the data-object *Text* layout. Email subjects and email-document content can be edited by any user who may edit an
  email document. They do not need admin rights.
- The config tree (`templating_engine.twig.sandbox_security_policy`) only restricts **tags, filters and functions**.
  `{{ … }}` with a method call needs none of them. The policy permits every method and property, and every Twig
  global is visible in sandboxed templates.
- With the global `container` (a compiled `Container`), an editor can write the subject
  `{{ container.getParameter('kernel.secret') }}` and read the app secret. With
  `{{ container.get('doctrine.dbal.default_connection').executeStatement('…') }}` they can run any SQL (e.g. make
  themselves admin), and the same works for any other public service (filesystem storages, `kernel`, …). Even without
  `container`, the `app` global (`app.request.server…`, `app.session`) is fully reachable.
- In effect the sandbox is not a sandbox. It blocks `{% for %}`, but it does not block arbitrary method calls on any
  object that reaches the template.

**Verify:** create an email document with subject `{{ container.getParameter('kernel.secret') }}` and send a test mail
(or call `Mail::getSubjectRendered()` in a test). The secret appears in the subject.

**Fix direction:**
- Remove `twig.globals.container` from `default.yaml` (its own comment says "only for compatibility/dev reasons").
  Provide narrowly scoped Twig functions instead where templates still need services.
- Implement `checkMethodAllowed()` / `checkPropertyAllowed()` as an allowlist (class → methods / properties, e.g. via
  `Twig\Sandbox\SecurityPolicy`'s `$allowedMethods` / `$allowedProperties`). Extend the config tree with
  `sandbox_security_policy.methods` / `.properties` (`useAttributeAsKey('class')`) and wire them through the extension.
  Always allow `__toString`, as Twig's own policy does.
- Do not expose the global `app` and other globals to sandboxed templates. Render them with an `Environment` that has
  only the sandbox, or check `$obj instanceof AppVariable` / `ContainerInterface` and deny it.
- Add a test that renders `{{ container.… }}`, `{{ app.request… }}` and a method call on a mail param in the sandbox and
  expects `SecurityError`.

---

# Scope 5 – `Controller` / `OptionsProvider` / `Request` (+ `config/routing.yaml`, `config/services.yaml`)

> Notes
> - Files: `src/Controller/PublicServicesController.php`, `src/Controller/WebDavController.php`,
>   `src/OptionsProvider/SelectOptionsOptionsProvider.php`, `src/Request/ParamResolver/DataObjectParamResolver.php`,
>   `config/routing.yaml`, `config/services.yaml:58-62,116-118`. The model code these four classes drive was read too
>   (`models/Asset/WebDAV/*`, `models/Asset/Service.php` thumbnail delivery, `SelectionProviderTrait`,
>   `AbstractObject::getById()`), because most of the real risk sits there.
> - No hard Symfony 7.4 / PHP 8.4 incompatibility was found. `ValueResolverInterface` is the current resolver API,
>   `AbstractController` is used as intended, all files declare `strict_types`. The one hard break is a runtime one:
>   `WebDavController` ends with `exit` (B-64). Modernisation is in TODO-3 (I-65 … I-72).
> - No `vendor/` tree was available in this session, so Symfony/Sabre behaviour is quoted from their public APIs
>   (sabre/dav 4.x `Browser\Plugin::__construct($enablePost = true)`, `Server::start()`). Each item says how to confirm.
> - Line numbers are from commit `1a059513b7`.

---

## [x] C-13 – WebDAV `MOVE` renames, moves, overwrites and deletes assets without any permission check

**Files:** `src/Controller/WebDavController.php:39-40` (wires the tree), `models/Asset/WebDAV/Tree.php:39-91`,
`models/Asset/WebDAV/Folder.php:69-95`

- Every other WebDAV write path checks the element permission (`Folder::createFile()` → `isAllowed('create')`,
  `File::delete()` → `isAllowed('delete')`, `setName()` → `isAllowed('rename')`, `put()` → `isAllowed('publish')`).
  `Tree::move()` overrides Sabre's default move (which would go through those node methods) and checks **nothing**:
  - same directory → `setFilename()` (rename without `rename` permission);
  - other directory → `setPath()` / `setParentId()` into any folder (no `create` on the target, no `delete`/`rename` on
    the source);
  - destination exists → `$asset->setData($sourceAsset->getData()); $sourceAsset->delete();` (lines ~51-56): the target
    asset is **overwritten** and the source **deleted**, again without `publish`/`delete` permission.
- `Folder::getChild()` resolves any path by name and does not check `view`/`list`. A user can address assets outside
  their workspace directly by path (not only via the filtered `getChildren()`), and use them as `MOVE` source.
- A Pimcore user with only "view" on a folder can therefore rename, relocate, overwrite or delete any asset whose path
  they know. Asset versions keep the data, but references (`setData` keeps the target's ID) silently change content.
- `Admin::getCurrentUser()` is only read **after** the overwrite/delete (line ~81). If the `/asset/webdav` firewall is
  missing or misconfigured (see C-14), an **anonymous** `MOVE` onto an existing name deletes the source asset before
  the `null->getId()` error aborts the request.
- `Serialize::unserialize()` of `webdav-delete.dat` (lines ~58-67) restores whole asset objects from a temp file. That
  file is only as trustworthy as `PIMCORE_SYSTEM_TEMP_DIRECTORY` (shared `/tmp` on some hosts).

**Verify:** create a non-admin user with only `view`+`list` on `/test`, then
`curl -u user:pass -X MOVE -H 'Destination: /asset/webdav/test/b.jpg' https://host/asset/webdav/test/a.jpg` → the asset
is renamed. Repeat with an existing `b.jpg` → `b.jpg` gets `a.jpg`'s data and `a.jpg` is deleted.

**Fix direction:** in `Tree::move()` resolve the user first (fail with `Sabre\DAV\Exception\Forbidden` when there is
none) and check `rename` (same folder) or `delete`+`create` on source/target parent, `publish` on an overwritten target
and `delete` on the removed source. Check `view` in `Folder::getChild()`. Replace the serialised delete log with a DB
table or the TmpStore. Add a functional test per write verb (I-72).

---

## [x] C-14 – WebDAV endpoint relies entirely on the project firewall, and the Sabre browser plugin accepts cross-site POSTs (CSRF with cached Basic credentials)

**Files:** `src/Controller/WebDavController.php:34-61` (esp. `:54`), `config/routing.yaml:35-39`,
`.github/ci/files/config/packages/security.yaml:11-15`, `doc/04_Assets/05_Accessing_Assets_via_WebDAV.md`

- The controller has no `#[IsGranted]`, no `denyAccessUnlessGranted()`, no user check. Authentication exists only if the
  project's `security.yaml` contains the documented `pimcore_webdav` firewall (`http_basic`). Pimcore's skeleton has it,
  but nothing in the bundle enforces or verifies it. Without it, reads fail only by accident (`filterAccessibleByUser(null)`
  → `TypeError`) and C-13's anonymous delete path is open.
- `new Plugin()` (`Sabre\DAV\Browser\Plugin`) is created with `$enablePost = true`. The HTML browser then accepts
  `POST` form actions (`mkcol`, file upload) on `/asset/webdav/*`.
- Browsers re-send cached **HTTP Basic** credentials automatically, also on cross-site top-level form POSTs; `SameSite`
  does not apply to Basic auth. Any page an editor visits after using WebDAV in the browser can upload files (e.g. an
  SVG/HTML with script, then served from the site's own origin under `/var/assets`) or create folders in their name.
  There is no CSRF token on these forms.
- `http_basic` on the `pimcore_admin` provider authenticates with username + password only. If the project enforces
  2FA for the admin UI, WebDAV bypasses it (confirm against the project's 2FA setup).

**Verify:** log in once via the browser at `/asset/webdav/`, then from another origin submit
`<form method=post action="https://host/asset/webdav/" enctype=multipart/form-data><input type=hidden name=sabreAction value=mkcol><input name=name value=csrf>` → folder `csrf` is created.

**Fix direction:** `new Plugin(false)` (read-only HTML browser), or register the browser plugin only when
`kernel.debug` is on. Add `#[IsGranted('ROLE_PIMCORE_USER')]` (or an explicit `getUser()` check returning 401 with
`WWW-Authenticate`) to the action, so a missing firewall fails closed. Document that WebDAV bypasses 2FA, or add a
`pimcore.assets.webdav.enabled` switch (default off) and route only when enabled.

---

## [ ] C-15 – Public thumbnail route lets anonymous clients generate and store unbounded thumbnail variants (disk / CPU exhaustion)

**Files:** `src/Controller/PublicServicesController.php:38-64`, `config/routing.yaml:17-25`,
`models/Asset/Service.php:359-469,562-594`, `models/Asset/Image/Thumbnail/Processor.php:162-163`

- `_pimcore_service_thumbnail` is public by design (deferred thumbnail generation on first request). Any asset ID and any
  **named** thumbnail config can be requested, which is fine. But the URL also carries free parameters that each produce
  a **new file that is stored permanently** in the thumbnail storage:
  - `@<float>x` high-res factor (`Service.php:452-461`): `@1.0001x`, `@1.0002x`, … each give a different filename
    (`Processor.php:163`). The factor is capped only inside the transformation math, not in the filename;
  - `~-~media--<name>--query` (`:464-466`), `~-~time-<n>` for video frames (`:416-418`, one ffmpeg run per `n`),
    `~-~page-<n>` for document pages (`:424-426`, one Ghostscript/LibreOffice run per `n`);
  - the requested extension when it differs from the generated one (`:496-505`): a copy is written per extension.
- Each request is unauthenticated, cannot be rate-limited by the cache (every URL is new), and leaves a file behind.
  A simple loop over `@1.000Nx` fills the thumbnail volume; the video/page variants burn CPU.
- The controller adds nothing on top: it passes `filename`/`prefix` straight through and has no allowlist, limit or
  signature.

**Verify:** `for i in $(seq 1 50); do curl -so /dev/null "https://host/<path>/image-thumb__<id>__<name>/x@1.00$i""x.jpg"; done`
then count new files in the thumbnail storage folder for that asset.

**Fix direction:** in the controller (before calling the service) validate the variant parameters: high-res factor
from an allowlist (e.g. the configured `high_resolution` values / `1, 1.5, 2, 3`), media names from the thumbnail config,
time/page within the asset's duration/page count, extension from the enabled auto formats. Otherwise return 404 without
generating. Optionally sign deferred URLs (HMAC of the path with `kernel.secret`) the way Symfony's `UriSigner` does,
and only generate for signed URLs. Add a functional test (I-72).
