# SeoBundle – TODO 2: Bugs

Scope: `bundles/SeoBundle/config`, `bundles/SeoBundle/src`
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

Functional defects: wrong results, wrong HTTP status codes, edge-case crashes, lost/duplicated/misattributed data.
IDs are stable – refer to them as `B-xx` in later sessions. Line numbers are from commit `7162d16812`.

---

## HTTP error log (`src/EventListener/ResponseExceptionListener.php`, `src/Controller/MiscController.php`)

### [ ] B-01 – Unprotected DB write inside `kernel.exception`; long URIs are truncated and never matched again
**Files:** `src/EventListener/ResponseExceptionListener.php:85-100`, `src/Resources/install/install.sql:3`
- `uri` is `varchar(1024) utf8` (utf8mb3). With Pimcore's default `sql_mode = ''` a longer URI (or one containing
  raw 4-byte UTF-8) is **silently truncated/mangled** on insert. The next hit does `SELECT … WHERE uri = <full uri>`,
  finds nothing and inserts **another** row → every hit of a long URL creates a new row with `count = 1`; the detail
  view (`WHERE uri = ?`) never finds them either.
- With strict SQL mode (if a project overrides the init command) the insert throws inside the exception listener and
  the original 404 becomes a 500 (→ would be critical, see note in TODO-1).
- Any DB error here (lock timeout, missing table after partial install) replaces the real error response.

**Fix direction:** `try/catch (\Throwable)` + log a warning; store a hash column (`uri_hash`, unique) and look up by
hash; truncate explicitly; `utf8mb4` (I-24).

### [ ] B-02 – Duplicate rows / race condition, `code` never updated, `$_GET` used instead of the Request
**File:** `src/EventListener/ResponseExceptionListener.php:87-99`
- `SELECT` then `INSERT` without a unique key on `uri` → concurrent 404s (crawler bursts) insert duplicates; the grid
  then shows the same URI several times with split counts.
- On update only `count`/`date` change – if the same URI first returned 500 and now returns 404, the stored `code`
  stays 500.
- `serialize($_GET)` reads the superglobal instead of `$request->query->all()` (wrong for sub-requests, functional
  tests, and long-running runtimes like FrankenPHP/RoadRunner where superglobals are not reset).

**Fix direction:** unique index (on the hash from B-01) + `INSERT … ON DUPLICATE KEY UPDATE count = count + 1,
date = VALUES(date), code = VALUES(code)`; use `$request->query->all()` (and `json_encode` instead of `serialize`,
see B-04).

### [ ] B-03 – 404s that are resolved by a redirect are still logged as HTTP errors
**Files:** `src/EventListener/ResponseExceptionListener.php:49`, `src/EventListener/RoutingListener.php:44`,
`config/event_listeners.yaml:6-11`
- Both listeners subscribe to `kernel.exception` with priority `64`. With equal priority the order is the service
  registration order, and `ResponseExceptionListener` is registered first → every `NotFoundHttpException` is written to
  `http_error_log` **before** `RoutingListener` turns it into a redirect. The error log fills with URLs that actually
  work (redirected) and hides the real 404s.

**Fix direction:** give `RoutingListener::onKernelException` a higher priority (e.g. 65) or let the log listener skip
when `$event->hasResponse()`/`$event->isPropagationStopped()`; verify with `debug:event-dispatcher kernel.exception`.

### [ ] B-04 – `httpErrorLogDetailAction`: unknown URI, `unserialize(null)`, unrestricted `unserialize()`
**File:** `src/Controller/MiscController.php:79-98`
- `fetchAssociative()` returns `false` for an unknown/flushed URI → `foreach (false as …)` warning, template rendered
  with `data = false`. Should be a 404.
- `parametersGet` is nullable → `unserialize(null)` is deprecated (PHP 8.1+) and returns `false`.
- `unserialize()` without `['allowed_classes' => false]` on DB content (hardening; the content originates from
  request data).

**Fix direction:** `if (!$data) throw $this->createNotFoundException()`; `unserialize($value ?? '', ['allowed_classes'
=> false])` or better switch the column to JSON (migration needed); drop the by-reference `foreach`.

### [ ] B-05 – `httpErrorLogAction`: negative/oversized paging values, malformed `sort` JSON
**File:** `src/Controller/MiscController.php:38-68`
- `limit`/`start` come from `getInt()` and can be negative → `LIMIT -5,20` → SQL syntax error (500). `limit` is
  unbounded (`limit=100000000`).
- `json_decode(...)[0]` on invalid JSON or an empty array → "Trying to access array offset on null/undefined key"
  warnings (exceptions in dev).

**Fix direction:** clamp: `$limit = max(1, min($limit ?: 20, 1000))`, `$offset = max(0, $offset)`; decode with
`JSON_THROW_ON_ERROR` inside try/catch or `$decoded[0] ?? []` with an `is_array` check. Use QueryBuilder (I-33).

---

## Redirect handling (`src/Redirect/*`, `src/EventListener/RoutingListener.php`, `src/Model/*`)

### [ ] B-06 – Unescaped site root path used as regex in `buildRedirectResponse()`
**File:** `src/Redirect/RedirectHandler.php:182`
```php
$url = preg_replace('@^' . $targetSite->getRootPath() . '/@', '/', $url);
```
- Root path is not `preg_quote`d. A root path containing `@` breaks the pattern (`preg_replace` returns `null` →
  redirect to `https://domain` + null → wrong URL), characters like `.`, `+`, `(` match wrongly.

**Fix direction:** `preg_quote($targetSite->getRootPath(), '@')`, or `str_starts_with()` + `substr()`.

### [ ] B-07 – Redirect cache lock not released on `\Error` / non-`Exception` throwables
**File:** `src/Redirect/RedirectHandler.php:245-268`
- `acquire(true)` … `release()` without `finally`; only `Exception` is caught. A `TypeError`/`Error` while loading
  (e.g. B-09, corrupted cache entry) leaves the lock held until the process ends → other PHP-FPM workers block on
  `acquire(true)` (blocking) for the lock TTL on every request needing regex redirects.

**Fix direction:** `try { … } finally { $this->lock->release(); }` and catch `\Throwable` for the warm-up.

### [ ] B-08 – Redirect lookup runs for sub-requests (error controller, ESI/fragments)
**File:** `src/EventListener/RoutingListener.php:48-59`
- `onKernelRequest` has no `$event->isMainRequest()` check. Symfony's error handling issues a sub-request to the
  error controller, fragments render sub-requests → each runs the exact-match DB query (+ isInstalled query, I-16)
  again, and a matching redirect would be returned as fragment content instead of a real redirect.

**Fix direction:** `if (!$event->isMainRequest()) return;` (same for `onKernelException` is not needed – exception
events are per request, but check `isMainRequest()` there as well for fragments).

### [ ] B-09 – `Redirect::setExpiry()` assigns `false` to `?int` → `TypeError`
**File:** `src/Model/Redirect.php:262-270`
- `strtotime()` returns `false` for unparseable strings; the result is assigned to `protected ?int $expiry` inside a
  `strict_types` file → `TypeError`. Reachable from the admin grid (`setValues($data)` with a bad date string) and
  from any API caller.

**Fix direction:** `$ts = strtotime($expiry); if ($ts === false) throw new InvalidArgumentException(...)`, or
accept only `int|DateTimeInterface|null`.

### [ ] B-10 – `Redirect::getTargetPath()` – `TypeError` for `null` target, deprecation for path targets
**File:** `src/Model/Redirect.php:157-169`
- `Document::getById($this->target)`: `target` is `?string` → `null` causes `TypeError` (`int|string` required);
  any string triggers Pimcore's "Passing id as string is deprecated"; a path target like `/foo` is looked up as id.

**Fix direction:** `is_numeric($this->target) ? Document::getById((int)$this->target) : null`.

### [ ] B-11 – `Listing\Dao::load()` returns unfiltered array; `getTotalCount()` hides SQL errors
**File:** `src/Model/Redirect/Listing/Dao.php:34-55`
- `setRedirects(array_filter($redirects))` but `return $redirects;` → `load()` can return `null` entries (row deleted
  between the two queries) although documented as `Redirect[]` → callers like `RedirectsController::cleanupAction()`
  (`$expiredRedirect->delete()`) crash.
- `getTotalCount()` catches every `Exception` and returns `0` → a broken condition (e.g. filter SQL) shows an empty
  grid instead of an error.

**Fix direction:** return the filtered array; let exceptions bubble (or log them).

### [ ] B-12 – `Redirect` typed property `$type` is uninitialised; `Redirect::create()` persists an empty redirect
**File:** `src/Model/Redirect.php:59, 125-131, 182-185`
- `protected string $type;` has no default → `getType()` on a new, not-yet-typed instance throws
  "must not be accessed before initialization"; `getObjectVars()` silently omits it, so `save()` relies on the MySQL
  ENUM implicit default (`entire_uri`).
- `Redirect::create()` saves a redirect with no source/target/type → a garbage row that is active by default.

**Fix direction:** default `$type = self::TYPE_PATH` (or make it nullable and validate on save); remove or rework
`create()`; validate required fields in `save()`.

---

## Redirect admin controller (`src/Controller/RedirectsController.php`)

### [ ] B-13 – `update` branch reads keys without `isset` (create branch does)
**File:** `src/Controller/RedirectsController.php:71-85`
- `$data['id']`, `$data['target']`, `$data['regex']`, `$data['source']` are accessed directly. The ExtJS store sends
  all fields (`writeAllFields: true`), but any other client (API, tests, future UI) sending a partial payload gets
  "Undefined array key" (exception in dev) or `Redirect::getById(null)` → `TypeError`.

**Fix direction:** use the same `isset`/`??` handling as in `create`; extract shared create/update code (I-23).

### [ ] B-14 – Mass assignment: client can overwrite owner/creation metadata
**File:** `src/Controller/RedirectsController.php:87, 116`
- `$redirect->setValues($data)` with the raw JSON payload → a user with `redirects` permission can set
  `userOwner`, `userModification`, `creationDate`, `modificationDate` (setters exist). `updateModificationInfos()`
  overwrites `userModification`/`modificationDate` but **not** `userOwner`/`creationDate`.

**Fix direction:** whitelist the editable fields (same list as `Csv::$columns` minus `id`).

### [ ] B-15 – CSV import: no upload validation, `getRealPath()` may be `false`
**File:** `src/Controller/RedirectsController.php:218-225`
- No `$file->isValid()` check (upload errors such as `UPLOAD_ERR_INI_SIZE`); `getRealPath()` returns `false` for a
  failed upload → `Csv::import(false)` → `TypeError` (500) instead of a 400 with a message.
- No size/MIME check before reading the whole file into memory (`Csv::import()` reads it twice, I-21).

**Fix direction:** `if (!$file instanceof UploadedFile || !$file->isValid()) throw new BadRequestHttpException(
$file?->getErrorMessage() ?? 'Missing file');` + size/extension check.

### [ ] B-16 – Expired redirects: controller deletes, maintenance only deactivates → table grows forever
**Files:** `src/Controller/RedirectsController.php:233-254`, `src/Maintenance/RedirectCleanupTask.php:28-38`,
`src/EventListener/DocumentListener.php:154`, `src/EventListener/UrlSlugUpdateListener.php:72`
- Auto-created redirects are commented "removed automatically after 1 year", but the maintenance task only sets
  `active = 0`. Only the manual "cleanup" button deletes. On sites with frequent moves/slug changes the `redirects`
  table (and the admin grid) grows indefinitely with dead rows.
- Both paths load full models one by one and save/delete individually (each `save()` clears the `redirect` cache tag
  → N cache clears in one maintenance run).

**Fix direction:** decide on one semantic (delete expired `auto_create` redirects, deactivate manual ones?),
implement it once in a service used by both (I-22), and clear the cache tag once at the end.

---

## Document / slug listeners (`src/EventListener/DocumentListener.php`, `src/EventListener/UrlSlugUpdateListener.php`)

### [ ] B-17 – `onPostMoveAction`: null dereference of current user, evaluated even when the feature is disabled
**File:** `src/EventListener/DocumentListener.php:104-106`
```php
if (Pimcore\Tool\Admin::getCurrentUser()->isAllowed('redirects') && $pimcore_seo_redirects['auto_create_redirects']) {
```
- `Admin::getCurrentUser()` returns `?User`. When `POST_MOVE_ACTION` is dispatched without an authenticated admin
  (CLI command, Studio/API, functional tests, custom code) → "Call to a member function isAllowed() on null".
- The permission check runs **before** the config flag, so it crashes even with the default
  `auto_create_redirects: false`.

**Fix direction:** check the config flag first, then `Admin::getCurrentUser()?->isAllowed('redirects')` (decide whether
system moves without a user should create redirects).

### [ ] B-18 – Pretty-URL auto redirects: no site, no duplicate check, also on unpublish, no expiry
**File:** `src/EventListener/DocumentListener.php:62-88`
- `sourceSite`/`targetSite` are never set → for pages inside a site the redirect is created with `sourceSite = NULL`,
  but `Dao::getByExactMatch()` requires `sourceSite = <siteId>` for site requests → **the redirect never matches**
  on the site's domain.
- No check for an existing redirect with the same source → saving/publishing repeatedly while toggling pretty URLs
  creates duplicates; an older redirect with the same source but stale target can win (both priority 1).
- Also triggered on `unpublish` (redirecting the old pretty URL to the new pretty URL of an unpublished page).
- No `setRegex(false)`/`setExpiry()` – inconsistent with the move/slug auto redirects (1 year).

**Fix direction:** reuse a shared auto-redirect service (I-23) that resolves sites (`Frontend::getSiteForDocument()`),
upserts by `(source, sourceSite, type=auto_create)`, and applies the same expiry policy.

### [ ] B-19 – Move auto redirects: created for every descendant type, duplicates, possible `TypeError`
**File:** `src/EventListener/DocumentListener.php:116-140, 146-165`
- `loadIdPathList()` has no type filter → redirects are created for folders, snippets, emails, newsletters, links
  … (snippets/emails are not routable; the target resolution in `RedirectHandler` then logs
  "Target of redirect … not found" for folders/snippets).
- No duplicate check → moving a tree back and forth multiplies rows (N descendants per move).
- `preg_replace()` can return `null` (PCRE error, e.g. backtrack limit on long paths) → `doCreateRedirectForFormerPath(
  string $source …)` `TypeError` in a `strict_types` file. `str_starts_with` + `substr` avoids regex entirely.
- All inserts happen after the move is already committed and without a transaction → a failure halfway leaves a
  partial redirect set and an error toast for an otherwise successful move.

**Fix direction:** filter `type IN ('page','hardlink','link')` in the listing condition; upsert instead of insert;
string-prefix replacement; wrap in a transaction or catch + log.

### [ ] B-20 – `redirects.source`/`target` are `varchar(255)` → long paths silently truncated
**Files:** `src/Resources/install/install.sql:18-20`, `src/EventListener/DocumentListener.php`,
`src/EventListener/UrlSlugUpdateListener.php`, `src/Controller/RedirectsController.php`
- Document paths/pretty URLs/slugs can exceed 255 chars (document paths up to 765). With `sql_mode = ''` the value is
  truncated silently → the auto/manual redirect source never matches, or a **truncated target** redirects to a wrong
  URL.

**Fix direction:** enlarge (`varchar(1024)`/`TEXT` + hash index) via migration, and validate length on save.

### [ ] B-21 – URL slug redirects: stale existing redirect is kept → redirect loop possible
**File:** `src/EventListener/UrlSlugUpdateListener.php:57-80`
- The existence check only looks at `source + type auto_create` and **skips** creation if one exists, without updating
  its target. Scenario: slug `/a`→`/b` (creates `/a → /b`), `/b`→`/a` (creates `/b → /a`), `/a`→`/c`: existing
  `/a → /b` is kept, no `/a → /c` is created → `/a` → `/b` → `/a` … redirect loop (both are 404 now).
- Expired/inactive auto redirects also count as "existing" (no `active`/`expiry` condition).

**Fix direction:** upsert: update target + expiry + `active = 1` of the existing auto redirect; also deactivate/delete
auto redirects whose **source** equals the new slug (the new slug is live again).

---

## Sitemaps (`src/Sitemap/*`)

### [ ] B-22 – `ModificationDateProcessor`: `null` modification date → `setLastmod(false)` `TypeError`
**File:** `src/Sitemap/Element/Processor/ModificationDateProcessor.php:38`
- `DateTime::createFromFormat('U', '')` returns `false` → `UrlConcrete::setLastmod(?DateTimeInterface)` `TypeError`
  → whole sitemap fails (same blast radius as C-01). Elements without modification date exist (e.g. freshly imported,
  wrapped hardlink children depending on source).

**Fix direction:** `$ts = $element->getModificationDate(); if ($ts) { $url->setLastmod(new DateTimeImmutable('@' .
$ts)); }`.

### [ ] B-23 – `DocumentTreeGenerator::populate()`: section filter ignored for current site, null root document
**File:** `src/Sitemap/Document/DocumentTreeGenerator.php:76-109`
- `handleCurrentSite` branch ignores `$section` → when Presta requests section `default` (or another site), the
  current site's URLs are added to `site_<id>` anyway; with `handleSites` also enabled the current site is generated
  twice.
- `$site->getRootDocument()` is `?Document`; a site whose root document is missing → `TypeError` in
  `populateCollection(Document …)` → whole sitemap fails.
- `Site::getCurrentSite()` exceptions are logged via static `Logger::error` without the exception.

**Fix direction:** apply the same `null === $section || $section === $siteSection` check; skip sites with `null` root
and log a warning; inject a logger (I-04).

### [ ] B-24 – `UrlGenerator` snapshots `RequestContext` scheme/host/base URL at construction time
**File:** `src/Sitemap/UrlGenerator.php:35-49`
- Defaults for `scheme`, `host`, `base_url` are read once in the constructor (`setDefaults([...])` with values),
  while `port` is a lazy closure. If the router context changes after the service was created (CLI
  `presta:sitemaps:dump --base-url=…`, Messenger workers / long-running runtimes, multiple dumps in one process) the
  generated URLs use stale scheme/host but the current port → mixed/wrong absolute URLs.

**Fix direction:** make all defaults lazy closures (`fn () => $this->requestContext->getHost()`) or build the
`OptionsResolver` per call.

---

## Configuration / installation

### [ ] B-25 – Sitemap generator config without `generator_id` passes validation and breaks container compile
**Files:** `src/DependencyInjection/Configuration.php:62-64`, `src/DependencyInjection/PimcoreSeoExtension.php:73`
- Verified: `pimcore_seo.sitemaps.generators.x: { priority: 5 }` is accepted (`cannotBeEmpty()` does not imply
  required); the processed config has no `generator_id` key → "Undefined array key" + `new Reference(null)`
  `TypeError` while compiling the container (cryptic error instead of a config validation message).

**Fix direction:** `->scalarNode('generator_id')->isRequired()->cannotBeEmpty()`.

### [ ] B-26 – Installer is not idempotent; permissions are not cleaned from users/roles
**File:** `src/Installer.php:33-92`
- `addUserPermission()` uses plain `insert` → re-running `install` after a partially failed install (tables created,
  settings-store flag not written) fails with a duplicate key on `users_permission_definitions`.
- No transaction around SQL + permissions + settings-store flag.
- `uninstall()` removes the permission definitions but the permission keys stay in `users.permissions`/role strings.
- Multi-statement SQL file executed with a single `executeQuery()` – works only with emulated prepares; `executeQuery`
  is meant for result-returning statements.

**Fix direction:** `INSERT IGNORE`/upsert (or check before insert); split statements and use `executeStatement()` (or a
Doctrine `Schema`, I-24); cleanup of user/role permission strings on uninstall.

### [ ] B-27 – `robots.txt` save: unvalidated site keys / content type, stale runtime cache
**File:** `src/Controller/SettingsController.php:46-60`
- Keys of `data[...]` are used as-is in the SettingsStore id (`robots.txt-<anything>`): no check that the key is
  `default` or an existing site id → orphan/garbage entries that `getRobotsConfig()` later returns.
- Values are not checked to be strings (`data[1][]=x` → array passed to `SettingsStore::set(..., TYPE_STRING)` →
  error/500).
- `Config::setRobotsConfig()` runtime cache is not invalidated after saving (same-request reads return stale data);
  entries for deleted sites are never removed.

**Fix direction:** validate keys against `Site\Listing` + `'default'`, `is_string()` on values, reset the runtime cache
after save.

### [ ] B-28 – `seopanelTreeAction`: missing/invalid `node` → fatal error; parent not permission-checked
**File:** `src/Controller/Document/DocumentController.php:62-129` (also `:48-50`)
- `$allParams['node']` without `??` → "Undefined array key"; `Document::getById(0)` → `null` →
  `$document->hasChildren()` on null → `Error` (500).
- The parent document itself is not checked with `isAllowed('list')` (children are).
- `seopanelTreeRootAction`: `Page::getById(1)` documented as non-null via `@var` – `null`/non-Page root → `Error`.

**Fix direction:** `$node = (int)($allParams['node'] ?? 0)`; `if (!$document || !$document->isAllowed('list')) throw
$this->createNotFoundException()/createAccessDeniedHttpException()`; same null check for the root.
