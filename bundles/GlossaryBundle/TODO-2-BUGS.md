# GlossaryBundle – TODO 2: Bugs

Scope: `bundles/GlossaryBundle/**` (config, public/js, src, translations), plus the direct consumers where a defect
originates in the bundle interplay (`lib/Tool/DomCrawler.php`, `models/Document.php::getById()`,
`tests/Unit/Document/Glossary/GlossaryTest.php`, `tests/Support/Helper/Unit.php`)
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

Functional defects that produce wrong output, stale data, crashes in specific situations or broken admin UX.
IDs are stable – refer to them as `B-xx` in later sessions. Line numbers are from commit `bcac4499f6`.
Items marked *(reproduced)* were verified with a scratch copy of the code on Symfony 7.4.17 / PHP 8.4.

> Many `Processor` items (B-01 … B-08) disappear automatically with the DOM-based rewrite proposed in **I-13**.
> They are listed separately so each behaviour gets a regression test.

---

## Replacement engine (`src/Tool/Processor.php`)

### [ ] B-01 – Case-insensitive match replaces the visitor-visible word with the stored casing *(reproduced)*
**File:** `src/Tool/Processor.php:218`, `:249-251`
The replacement is always the stored `text`, never the matched text: entry `Glossary` (not case-sensitive) turns
`the glossary` into `the <a …>Glossary</a>`; a sentence-initial `Huber` becomes `huber`. Content is silently
altered. `GlossaryTest::testGlossaryWithLowerThenAndGreaterThenHtml()` (`tests/Unit/Document/Glossary/GlossaryTest.php:152-170`)
even asserts this wrong behaviour (`Huber` → `huber`).
**Fix:** use the match (`$0` / callback with `$m[0]`) as link/abbr content; update the test.

### [ ] B-02 – `$n`, `${n}` and `\n` in text/abbr/link are interpreted as regex back-references *(reproduced)*
**File:** `src/Tool/Processor.php:132`, `:137`, `:253-255`
`preg_replace()` treats the replacement string specially. Abbreviation `costs $1 \0` renders as `title="costs  Plan"`;
URLs with `$1` or backslashes are corrupted.
**Fix:** `preg_replace_callback()` returning the literal replacement, or escape `\` and `$` in `replace`.

### [ ] B-03 – `limit` option is applied per element, not per document *(reproduced)*
**File:** `src/Tool/Processor.php:120`, `:122`, `:135-138`
The closure captures `$data` **by value** (`use ($options, $data)`), so `$data['count']` is reset for every element.
`{% apply pimcore_glossary({limit: 1}) %}` with three paragraphs links all three occurrences.
**Fix:** `use ($options, &$data)` (or a counter property / `\SplObjectStorage`); add a test.

### [ ] B-04 – Exact match and case-insensitive matching are not Unicode-aware *(reproduced)*
**File:** `src/Tool/Processor.php:243-251`
No `u` modifier: `\w` is ASCII-only and `i` folds only ASCII. Entry `Glossar` with *exact match* links the prefix of
`Glossarübersicht` (`<a>Glossar</a>übersicht`); `ÄRZTE` vs `ärzte` never match case-insensitively.
**Fix:** add `u` (and use `(?<![\p{L}\p{N}_])…(?![\p{L}\p{N}_])` boundaries); guard against invalid UTF-8 input
(`preg_*` returns `null` with `u`, see B-07).

### [ ] B-05 – The `<a…/a>` skip pattern is wrong: no `s` flag, matches `<abbr>`/`<aside>`/`<audio>`… *(reproduced)*
**File:** `src/Tool/Processor.php:244`, `:246`
- Without `s`, `.` does not match newlines → an existing multi-line link is not skipped:
  `<a href="/q">The\nGlossary</a>` → nested `<a>` inside `<a>` (invalid HTML).
- `<a` also matches `<abbr`, `<aside`, `<audio`, `<article`, `<area` → the skip runs from e.g. `<aside>` to the next
  `/a>` anywhere later in the element and **suppresses all replacements in between**.
- Existing `<abbr>` elements (incl. glossary-generated ones) are not skipped at all.
**Fix:** obsolete with I-13 (ancestor check). Short-term: `~<a\b[^>]*>.*?</a>(*SKIP)(*FAIL)|…~is`.

### [ ] B-06 – Passing a full HTML document drops `<!DOCTYPE>` and the `<html>` element *(reproduced)*
**Files:** `src/Tool/Processor.php:87`, `:154`, `lib/Tool/DomCrawler.php:33-52`
`DomCrawler` only wraps fragments; for input containing `</body>`/`</html>` `Crawler::html()` returns the
**inner** HTML of `<html>`. `<!DOCTYPE html><html lang="de"><head>…</head><body>…</body></html>` comes back as
`<head>…</head><body>…</body>` – the doctype (→ quirks mode) and `lang`/`dir`/`class` attributes are lost when the
filter is applied around a whole layout.
**Fix:** for full documents serialize via `ownerDocument->saveHTML()` (or `outerHtml()` + doctype), add a test.

### [ ] B-07 – PCRE failure or invalid fragment XML loses content or throws `TypeError`
**File:** `src/Tool/Processor.php:132`, `:137`, `:143-150`
- `preg_replace()` returns `null` on errors (backtrack limit with the lazy `.*?` on large elements, invalid UTF-8
  once `u` is added) → `$originalText !== $text` → `htmlentities(null, …)` → `TypeError` under `strict_types`
  → the whole page 500s.
- `appendXML()` returns `false` (warning) for content that is not well-formed XML (e.g. control characters, HTML-only
  void elements re-parsed as XML) → the element is replaced by an **empty clone**, the text disappears.
**Fix:** check `preg_last_error()` / `null` and fall back to the original text; check the `appendXML()` result before
replacing the node (or avoid string round-trips entirely, I-13).

### [ ] B-08 – PHP 8.4 native HTML5 parser changes output; `GlossaryTest` fails on the target stack *(reproduced)*
**Files:** `src/Tool/Processor.php:87`, `:154`, `lib/Tool/DomCrawler.php:31`,
`tests/Unit/Document/Glossary/GlossaryTest.php:88-130`
On Symfony 7.4 + PHP ≥ 8.4 `Crawler` uses `\Dom\HTMLDocument` (no opt-out without deprecation, mandatory in
Symfony 8). Named entities in **unmodified** nodes are now serialized as raw characters: `Seit&nbsp; 1909` →
`Seit  1909` (Symfony 6.4 kept `&nbsp;`). `testGlossaryWithHtml()` expects the literal `&nbsp;` and will fail;
any project snapshot/diff tooling sees changed markup on every page with glossary data.
**Fix:** decide on the expected serialization (raw UTF-8 is fine for browsers), adapt the tests
(`html_entity_decode($expect)` like the other tests do), and run `GlossaryTest` on PHP 8.4 in CI. Do **not** pass
`useHtml5Parser: false` (deprecated in 7.4).

### [ ] B-09 – Self-link suppression compares against the raw request URI (query string, trailing slash, sub-requests)
**File:** `src/Tool/Processor.php:66-68`, `:96-113`
- `getRequestUri()` includes the query string (`/page?utm_source=x`) and is not trimmed → a page links to itself as
  soon as a query parameter or a trailing slash is present, while `$linkTargetTrimmed` is trimmed.
- Locale and URI are taken from the **main** request, the document from the **current** request
  (`DocumentResolver::getDocument()` default) → inside snippets / `render(controller())` / `pimcore_inc` the
  combination is inconsistent (wrong locale for a snippet of another language, self-link check against the
  wrong page).
- `$document->getId() == $linkTarget` / `getFullPath() == …` use loose comparison.
**Fix:** compare `getPathInfo()` (normalised, trailing slash trimmed) and use one consistent request
(`getCurrentRequest()` or explicit argument); strict comparisons.

### [ ] B-10 – Filter crashes without an HTTP request (CLI, messenger workers, email rendering)
**Files:** `src/Tool/Processor.php:62`, `:66-68`, `lib/Http/RequestHelper.php:71-79`
`EditmodeResolver::isEditmode()` / `DocumentResolver::getDocument()` call `getCurrentRequest()` and
`RequestHelper::getMainRequest()` throws `LogicException('There is no main request available.')`. Templates using
`pimcore_glossary` therefore fail when rendered from a command, a messenger handler (newsletter/email sending,
static page generation, PDF rendering) or a test without a pushed request.
**Fix:** `if (!$this->requestHelper->hasMainRequest()) { return $content; }` (or use the default locale and no
document), and make `parse()` the public, request-independent API.

### [ ] B-11 – Bundle enabled but not installed → every page using the filter returns 500
**Files:** `src/Tool/Processor.php:174-180`, `src/Model/Glossary/Listing/Dao.php:54`
The listing query throws `TableNotFoundException` when the `glossary` table does not exist (bundle registered in
`bundles.php` but `pimcore:bundle:install` not run, or after uninstall while templates still use the filter).
**Fix:** catch `TableNotFoundException` in `getData()` (log once, return `[]`) or check the installer state.

---

## Cache and data consistency

### [ ] B-12 – Cache is only invalidated by the admin controller (and before the write)
**Files:** `src/Controller/SettingsController.php:48`, `src/Model/Glossary/Dao.php:54-69`, `src/Tool/Processor.php:183`
- `Glossary::save()` / `delete()` do not clear the `glossary` cache tag → entries created via API, imports,
  migrations, fixtures or `tests/Unit/Document/Glossary/GlossaryTest.php` stay invisible on the frontend until the
  cache expires (`GlossaryTest` saves entries in each test and relies on the cache not serving the previous test's
  `glossary_en_` result – verify the test-env cache config).
- The controller clears the tag **before** the change is persisted: a concurrent frontend request can re-populate
  the cache with the old data between `clearTag()` and `save()`/`delete()` → stale until the next edit.
**Fix:** clear the tag in `Dao::save()` / `Dao::delete()` (after the write), remove it from the controller.

### [ ] B-13 – Cached entries embed resolved document paths; not invalidated on document move/rename/delete/unpublish
**File:** `src/Tool/Processor.php:183`, `:226-240`
`prepareData()` resolves internal links to `$doc->getFullPath()` and the result is cached with tag `glossary` only.
Renaming/moving a target document leaves broken links on all pages (cache priority 995, no lifetime). Unpublished
documents are linked too (no `isPublished()` check) → links to 404 pages.
**Fix:** add the document cache tags (`$doc->getCacheTag()`) of every resolved target to `Cache::save()`, skip
unpublished targets (or resolve paths at render time from a cached id→path map).

### [ ] B-14 – Deleted target document → raw numeric id rendered as `href`
**File:** `src/Tool/Processor.php:226-239`
If `link` contains a document id and `Document::getById()` returns `null`, `$d['link']` stays `"123"` and is emitted as
`<a href="123">` (relative URL → 404) with `linkType = 'external'`.
**Fix:** skip the link (keep abbr only) and log; optionally clean up dangling ids on `document.postDelete`.

### [ ] B-15 – Admin heuristic `(int)$link > 0` corrupts links that start with a digit
**Files:** `src/Controller/SettingsController.php:70-76`, `:95-101`, `:128-134`, `src/Tool/Processor.php:230-231`
`(int)'3dprint.example.com' === 3`, `(int)'2024-sale' === 2024`. The grid displays the path of **document 3** instead
of the stored external link; the next edit of that row sends the path back, `Document::getByPath()` resolves it and
the stored link is **overwritten with id 3**. The frontend (`Document::getById('3dprint…')` → `is_numeric` false)
still renders the external link, so the corruption is only noticed after saving.
`Processor` also passes the string id to `Document::getById()`, which triggers a deprecation per entry and request
(`models/Document.php:189-195`, see I-10).
**Fix:** use `ctype_digit($link)` (or a separate `linkType`/`documentId` column) in both places; cast to `int` before
calling `Document::getById()`.

### [ ] B-16 – `Dao::save()` for new entries is not atomic (empty `INSERT` + `UPDATE`)
**File:** `src/Model/Glossary/Dao.php:54-61`, `:97-106`
`create()` inserts an empty row, then `update()` writes the data. If `update()` fails (e.g. value too long for
`varchar(255)` under strict SQL mode, see I-12), an orphan row with all columns `NULL` remains. Two queries instead
of one; concurrent readers can see the empty row.
**Fix:** single `INSERT` with the data (or wrap both in a transaction); `lastInsertId()` afterwards.

### [ ] B-17 – Empty glossary result is never cached → one DB query per request
**File:** `src/Tool/Processor.php:170-187`
`if (!$data = Cache::load($cacheKey))` treats a cached `[]` as a miss; for locales/sites without entries the listing
query runs on every request. A persistent-cache **hit** is also not put into `RuntimeCache`, so multiple filter
blocks per page call `Cache::load()` repeatedly.
**Fix:** `$data = Cache::load($cacheKey); if ($data === false) { … }` and `RuntimeCache::set()` in both paths.

---

## Admin controller (`src/Controller/SettingsController.php`)

### [ ] B-18 – Deleting/updating an entry that no longer exists → fatal error on `null`
**File:** `src/Controller/SettingsController.php:50-58`
`Glossary::getById()` returns `?Glossary`; `->delete()` / `->setValues()` are called unchecked → `Error: Call to a
member function … on null` (HTTP 500) when two admins edit the grid or a row was deleted in another tab. An `id`
that is not an `int` in the JSON payload (`"5"`) raises a `TypeError` under `strict_types`.
**Fix:** cast/validate the id, return a 404 JSON response (`success: false`) for missing entries.

### [ ] B-19 – Mass assignment through `setValues($data)` with the raw client payload
**File:** `src/Controller/SettingsController.php:66`, `:91`, `lib/Model/AbstractModel.php:163-190`
Every key is mapped to a setter: the client can overwrite `creationDate` / `modificationDate` (modification date is
re-set by the DAO, creation date is not), and unexpected keys like `dao` or `values` call `setDao()` /
`setValues()` → `TypeError` 500. No validation at all (empty `text`, overlong values, invalid `site` id, invalid
`language`).
**Fix:** whitelist `text, link, abbr, language, casesensitive, exactmatch, site`; validate (required text, max
lengths, existing site, language in `Tool::getValidLanguages()`, URL scheme – see C-01).

### [ ] B-20 – `update` response serializes the model object → empty `data`
**File:** `src/Controller/SettingsController.php:78` (vs. `:103`)
`jsonResponse(['data' => $glossary])` goes through `ReferenceLoopNormalizer`, which uses `get_object_vars()` from
outside the class → protected properties are invisible → `data: []`. The create action correctly uses
`getObjectVars()`. The grid record is never refreshed with server values (`modificationDate`, normalised link).
**Fix:** `'data' => $glossary->getObjectVars()` (ideally a dedicated normaliser/array shape shared by all actions).

### [ ] B-21 – Search filter does not escape `LIKE` wildcards
**File:** `src/Controller/SettingsController.php:120-122`
`%` and `_` in the search field act as wildcards (`_` matches every one-char term, searching for `100%` matches
everything starting with `100`). Not an injection (value is quoted).
**Fix:** `$list->escapeLike($filter)` and a bound parameter instead of `quote()`.

### [ ] B-22 – Unknown sort property from the grid → SQL error instead of a validation error
**Files:** `src/Controller/SettingsController.php:114-118`, `lib/Model/Listing/AbstractListing.php:78-81`
`isValidOrderKey()` returns `true` for anything, the key is only identifier-quoted → `?sort=[{"property":"foo"}]`
produces `Unknown column 'foo'`, which `getTotalCount()` swallows (`total: 0`) while `load()` throws → 500.
**Fix:** override `isValidOrderKey()` in `Glossary\Listing` with the table columns.

---

## Configuration / installer

### [ ] B-23 – `blocked_tags` config merges by index: user config overwrites defaults positionally *(reproduced)*
**Files:** `src/DependencyInjection/Configuration.php:32-36`, `config/pimcore/default.yaml:2-6`
`useAttributeAsKey('name')` on a plain list keeps numeric keys, and the bundle defaults are merged with the project
config. Project config `pimcore_glossary: { blocked_tags: ['nav'] }` results in
`['nav', 'script', 'style', …]` – i.e. **`a` silently becomes un-blocked** (nested links) and the project cannot
remove or replace the defaults (`blocked_tags: []` keeps all defaults). Tag names are also compared
case-sensitively (`'H1'` never matches).
**Fix:** drop `useAttributeAsKey`, use `->scalarPrototype()->end()->defaultValue([...])` (+ `performNoDeepMerging()`
if replacing is intended, or document additive semantics with a separate `additional_blocked_tags`), lowercase
values in `beforeNormalization()`; move defaults from `default.yaml` into the tree (I-02).

### [ ] B-24 – Installer is not re-runnable; uninstall leaves stale permissions
**File:** `src/Installer.php:34-69`
- `addUserPermission()` blindly `INSERT`s into `users_permission_definitions` (PK on `key`) → re-install after a
  partial uninstall / manual table drop / environments that kept the Pimcore 10 `glossary` permission fails with
  `UniqueConstraintViolationException` **after** the table was created → bundle half installed.
- `removeUserPermission()` only deletes the definition; the `glossary` entry stays in `users.permissions` /
  roles (harmless but confusing, re-appears as granted on re-install).
**Fix:** `INSERT IGNORE`/upsert (or check existence), wrap install steps so a failure leaves a consistent state.

---

## Twig extension

### [ ] B-25 – `pimcore_glossary` filter: `null` input → `TypeError`; output marked safe without pre-escaping
**File:** `src/Twig/Extension/GlossaryExtension.php:39`, `:43-50`
- `applyGlossary(string $string, …)` with `strict_types`: `{{ product.description|pimcore_glossary }}` where the
  getter returns `null` → `TypeError` → template error.
- `is_safe => ['html']` without `pre_escape => 'html'`: when used as `{{ var|pimcore_glossary }}` (instead of the
  documented `{% apply %}` block) the variable is output **unescaped** → XSS for user-supplied values.
**Fix:** accept `?string` (return `''`), and either add `'pre_escape' => 'html'` or document clearly that only
trusted HTML may be passed (and add `is_safe_callback`/`Markup` handling).

---

## Admin UI (`public/js/settings.js`)

### [ ] B-26 – Opening the editor mutates the global `pimcore.settings.websiteLanguages` array
**File:** `public/js/settings.js:21-22`
`this.languages = pimcore.settings.websiteLanguages; this.languages.splice(0,0,"")` prepends `""` to the **shared**
array every time the glossary tab is opened → all other language selectors in the admin (document settings,
translations, object localized fields, …) get one more empty entry per open/close cycle.
**Fix:** `this.languages = ['', ...pimcore.settings.websiteLanguages];`

### [ ] B-27 – Drop zones are re-created on every refresh/update/edit → leak and multiplied handlers
**File:** `public/js/settings.js:184-215`, `:273-312`, `:265-266`, `:259-262`, `:172`, `:319`
`updateRows()` creates a new `Ext.dd.DropZone` for every row on each `refresh`, `rowupdated`, store `update`,
`viewready`, add and delete; `onNodeDrop` calls `updateRows()` again and `beforeedit` adds another zone on each edit.
Zones are never destroyed → memory grows with every interaction, a drop fires the handler once per stacked zone
(N× `rec.set()` → N× autosync requests).
**Fix:** one `DropZone` on the grid view (`getTargetFromEvent` → row) created once; destroy it on panel destroy.

### [ ] B-28 – Drag & drop accepts assets and data objects as link targets
**File:** `public/js/settings.js:195-210`, `:292-305`
Any element (`ddGroup: "element"`) is accepted; an object path (`/Products/Shoe`) is stored as an "external" link
(`Document::getByPath()` fails) and rendered as a broken relative URL. Assets work only by accident (path is not
the full public URL when a CDN/thumbnail prefix is used).
**Fix:** accept only `elementType === 'document'` (or resolve asset URLs explicitly on the server).

### [ ] B-29 – Site cannot be reset to "all sites"; several small grid defects
**File:** `public/js/settings.js:126-138`, `:139-160`, `:76-82`, `:170`, `:314-317`
- Site combobox has no empty option → once assigned, the entry can never be made global again (only by
  deleting/recreating it).
- Date renderers check `d !== undefined` only → `null` dates render `1970-01-01 01:00:00`.
- Filter (ENTER) calls `store.load()` without resetting the page → on page 5 a narrower search shows an empty page.
- Delete confirmation shows the numeric id instead of the term (`data.data.id`).
- `onAdd()` inserts `{name: t('/')}` – a non-existent field, translated `/` – leftover from another grid; the new
  row is not opened in the row editor.
**Fix:** add a `null`/"all sites" record, `d ? … : ''`, `store.loadPage(1)`, show `data.data.text`, insert `{}` and
`this.rowEditing.startEdit(0, 0)`.
