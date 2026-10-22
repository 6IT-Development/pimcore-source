# GlossaryBundle – TODO 3: Improvements

Scope: `bundles/GlossaryBundle/**` (config, public/js, src, translations), plus the related tests
(`tests/Unit/Document/Glossary/GlossaryTest.php`, `tests/Support/Helper/Unit.php`), docs
(`doc/18_Tools_and_Features/21_Glossary.md`) and `phpstan-baseline.neon`
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-2-BUGS.md](TODO-2-BUGS.md)

Code quality, readability, performance, modernisation (Symfony 7.4 / PHP 8.4 idioms) and logical clean-ups. Nothing
here is broken on its own. IDs are stable – refer to them as `I-xx` in later sessions. Line numbers are from commit
`bcac4499f6`.

---

## Symfony 7.4 modernisation

### [ ] I-01 – Migrate to `AbstractBundle` (or at least simplify the extension)
**Files:** `src/PimcoreGlossaryBundle.php`, `src/DependencyInjection/PimcoreGlossaryExtension.php`,
`src/DependencyInjection/Configuration.php`
`AbstractPimcoreBundle` extends `Bundle` (`lib/Extension/Bundle/AbstractPimcoreBundle.php:23`), so a direct switch is
not possible without a core change. Options: (a) introduce an `AbstractPimcoreBundle` variant based on
`AbstractBundle` in core and move `configure()` / `loadExtension()` / `prependExtension()` into the bundle class;
(b) keep the extension but extend `Extension` + `#[\Override]` and drop the duplicated `YamlFileLoader` setup
(`:30-35`, `:43-48`). `getPath()` override (`:50-53`) becomes unnecessary with `AbstractBundle`.

### [ ] I-02 – Define defaults in the config tree instead of `config/pimcore/default.yaml`
**Files:** `src/DependencyInjection/Configuration.php:24-39`, `config/pimcore/config.yaml`, `config/pimcore/default.yaml`
Root cause of B-23. Use `->arrayNode('blocked_tags')->scalarPrototype()->end()->defaultValue([...])`, add
`->info()` so `config:dump-reference pimcore_glossary` documents the option, remove the `@var ArrayNodeDefinition`
cast (`getRootNode()` is typed in 7.4), and delete the two YAML files (they only exist to inject defaults).
Consider an `enabled`/`limit` default option as well.

### [ ] I-03 – Attribute-based / leaner service configuration
**File:** `config/services.yaml`
- Controllers: `tags: ['controller.service_arguments']` and `public: true` are redundant with `autoconfigure` for
  `AbstractController` subclasses (verify `UserAwareController` is autoconfigured).
- `Processor`: `#[Autowire(param: 'pimcore_glossary.blocked_tags')]` on the constructor parameter instead of YAML
  arguments.
- `Installer`: `$bundle` via expression language – could be `#[Autowire(expression: "service('kernel').getBundle('PimcoreGlossaryBundle')")]`
  or a factory; `public: true` only needed because `PimcoreGlossaryBundle::getInstaller()` uses `$this->container->get()`.
- Single `Pimcore\Bundle\GlossaryBundle\: resource: '../src/'` with `exclude: [Model, DependencyInjection, …]`
  instead of listing services one by one; remove the empty comment blocks.

### [ ] I-04 – Twig: use `#[AsTwigFilter]` (Twig ≥ 3.21) instead of `AbstractExtension`
**File:** `src/Twig/Extension/GlossaryExtension.php`
`twig/twig` is `^3.21.1` (`composer.json:129`). The extension can become a plain service with
`#[AsTwigFilter('pimcore_glossary', isSafe: ['html'])]` on `applyGlossary()` (lazy-loaded, no `getFilters()`).
Keep the class name for BC or alias it. Validate `$options` (`limit` int ≥ -1) with `OptionsResolver` or typed
named arguments.

### [ ] I-05 – Split the "god action" of `SettingsController`
**File:** `src/Controller/SettingsController.php:39-143`
One method switches on `has('data')` + `xaction` query parameter and contains three copies of the link-resolution
logic. Proposal:
- private `list()`, `create()`, `update()`, `destroy()` methods dispatched with `match ($request->query->getString('xaction'))`
  (keeps the ExtJS URL contract), or separate routes with `methods:`;
- a single `toArray(Glossary): array` / `resolveLinkForDisplay()` helper (lines `:70-76`, `:95-101`, `:128-134`);
- a payload DTO via `#[MapRequestPayload]` is not usable with the ExtJS form-encoded `data=` JSON, but a small
  `GlossaryInput::fromArray()` value object gives whitelisting + validation (B-19);
- consistent responses (`success: false` + message / HTTP 4xx), consistent `===` (`:79` uses `==`).

### [ ] I-06 – Remove the runtime `class_exists(QueryParams::class)` check
**File:** `src/Controller/SettingsController.php:105-108`
The whole controller is only reachable from the classic admin UI. Either guard route loading/registration on the
admin bundle being present (as already done for `admin-classic.yaml` in `prepend()`), or inline the ~10 lines of
`QueryParams::extractSortingSettings()` and drop the dependency on an `@internal` helper of another package.

### [ ] I-07 – Permission check as attribute / listener
**File:** `src/Controller/SettingsController.php:42-43`
Use a reusable `#[IsPimcoreAllowed('glossary')]`-style attribute (if core offers one) or a `kernel.controller`
listener rather than the first-line imperative check; makes the permission visible in route debugging.

---

## PHP 8.4 idioms & type safety

### [ ] I-08 – Constructor promotion and `readonly`
**Files:** `src/Tool/Processor.php:34-53`, `src/Twig/Extension/GlossaryExtension.php:29-34`
`private readonly RequestHelper $requestHelper, …` via constructor promotion; fix the odd formatting
(`)` and `{` on separate lines, aligned parameter spacing at `:43-46`). Type `array $blockedTags` as `list<string>`
in a PHPDoc.

### [ ] I-09 – `declare(strict_types=1)` in all files; drop phpstan baseline entries
**Files:** `src/DependencyInjection/Configuration.php`, `src/Model/Glossary/Dao.php`,
`src/Model/Glossary/Listing/Dao.php`, `phpstan-baseline.neon:69-82`
Three files lack the declaration and are whitelisted in the baseline. Add it (check that `Dao::update()` still
passes correct types – `bool` → `int` conversion is already done) and remove the baseline entries.

### [ ] I-10 – Model: consistent nullability, no static/instance name clash, less doc noise
**File:** `src/Model/Glossary.php`
- Setters for `text`/`link`/`abbr` should accept `?string` like the properties (root of C-05);
  `setModificationDate()` / `setCreationDate()` could be `@internal`.
- Static `Glossary::create()` (`:105-111`) has the same name as `Dao::create()` reachable through `__call` →
  `$glossary->create()` silently creates a **new** entry. Remove it or rename (`createAndSave()`).
- Empty `@internal` docblocks on every property (`:32-90`) add 50 lines of noise – one class-level `@internal`
  note or nothing.
- Add typed helpers instead of string heuristics: `isInternalLink(): bool` (`ctype_digit`),
  `getDocumentId(): ?int`, `getLinkPath(): ?string` – used by controller and processor (B-15).
- Consider a `Glossary::getByText()` / `getByLanguage()` API if needed by projects.

### [ ] I-11 – DAO clean-up
**Files:** `src/Model/Glossary/Dao.php`, `src/Model/Glossary/Listing/Dao.php`
- `getById(?int $id = null)`: `$id != null` → `null !== $id` (`:38`).
- `update()`: `getValidTableColumns('glossary')` is evaluated inside the loop (`:82-83`) → hoist once,
  `array_intersect_key()`; `$type` is a misleading variable name for the object vars.
- `save()` / `create()`: single `INSERT` (B-16); `@throws Exception` is generic – document the DBAL exception.
- Listing `load()`: N+1 (`Glossary::getById()` per id, `:37-42`) → hydrate from one `fetchAllAssociative()`
  (`new Glossary()->setValues($row, true)`).
- Listing docblocks: "static routes", "specicifies", `$glossarysData` (`:31`, `:37`); stale
  `@todo: $amount could not be defined` (`:59`); `getTotalCount()` swallows **all** exceptions and returns 0
  (`:63-67`) – catch only what is expected or let it bubble.
- `SELECT *` everywhere → explicit column list.

### [ ] I-12 – Listing: real order-key whitelist and typed accessors
**File:** `src/Model/Glossary/Listing.php`
Override `isValidOrderKey()` with the table columns (B-22). `@method Glossary|false current()` – check that the
annotations still match `AbstractListing` in this branch. `setGlossary(?array)` → `list<Glossary>|null`.

---

## Replacement engine (`src/Tool/Processor.php`)

### [ ] I-13 – Rewrite: operate on DOM text nodes, not on serialized HTML
**File:** `src/Tool/Processor.php:73-159`
Fixes C-01 … C-04 and B-01 … B-07 in one go:
1. Parse once with `DomCrawler`; select `//text()[normalize-space()]` and skip nodes with a blocked ancestor
   (`ancestor::*[contains($blocked, concat('|', local-name(), '|'))]` or a PHP ancestor walk).
2. Build **one** regex alternation per entry set (longest first, `u` + `i` per entry via inline `(?i:…)`), use
   `preg_split(..., PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE)` or `preg_match_all` with offsets.
3. Split the `DOMText` node and insert real elements (`createElement('a')`, `setAttribute('href', …)`,
   `appendChild(createTextNode($match))`) – escaping is done by the DOM, matched casing is preserved, no
   back-references, no re-scanning of inserted markup, no `htmlentities`/`html_entity_decode` round-trip.
4. Count matches per entry across the whole document for `limit` (B-03).
5. Serialize fragments via `html()`, full documents via `saveHTML()` (B-06).
Add tests for every C-/B- item before switching.

### [ ] I-14 – Pre-compile and cache the prepared entries, not only the raw rows
**File:** `src/Tool/Processor.php:161-262`
`prepareData()` (document lookups, regex construction) runs on every cache miss; with I-13 the cached value can be
the compiled pattern + replacement metadata. Store document ids instead of paths and add their cache tags (B-13).
Extract the cache key building (`'glossary_' . $locale . '_' . $siteId`) into a method/constant.

### [ ] I-15 – Remove the `htmlentities()` "variant" duplication
**File:** `src/Tool/Processor.php:194-210`
`htmlentities()` creates a duplicate entry for **every** term with non-ASCII characters (`Über` → `&Uuml;ber`),
although `Crawler::html()` outputs raw UTF-8 – the variant can never match but doubles the regex count. With
I-13 (text nodes are already decoded) the whole block can be removed; otherwise use `htmlspecialchars()` only.

### [ ] I-16 – Smaller logic improvements
**File:** `src/Tool/Processor.php`
- Language fallback: entries for `de` are not used on `de_AT` pages – use `Tool::getFallbackLanguagesFor()` / the
  base language (`:176`).
- `LENGTH(\`text\`)` sorts by **bytes** – use `CHAR_LENGTH()` (`:177`).
- `$siteId = ''` / `site = ''` against an `int unsigned` column – use `null` and `site IS NULL` only (`:163-176`).
- Unused closure parameter `$i`, misleading `/** @var DomCrawler|null */` with the null check **after** the first
  call (`:122-128`); `unset($html)` after `clear()` is pointless (`:155-156`).
- `array_merge(['limit' => -1], $options)` → `$options += ['limit' => -1]` with int cast.
- Skip parsing entirely when `$content` has no text (`trim(strip_tags($content)) === ''`) or no entry text occurs in
  `$content` (`stripos` pre-check) – avoids a full DOM parse on most snippets.
- Rename `parse()` / `process()` to reflect "request-aware" vs "pure" API (B-10).

---

## Installer & schema

### [ ] I-17 – Installer clean-up and migrations
**Files:** `src/Installer.php:71-93`, `src/Resources/install/install.sql`, `src/Resources/uninstall/uninstall.sql`
- `executeQuery()` for DDL → `executeStatement()`; the single-element `$sqlFileNames` loops are unnecessary.
- `SettingsStoreAwareInstaller` supports bundle migrations – ship schema changes as migrations instead of raw SQL
  (the existing core migrations `Version20201012154224` / `Version20221215071650` / `Version20230110130748` about
  the glossary table live in **CoreBundle**; consider moving glossary-specific ones into the bundle).
- Use `Db::get()` once / inject `Connection`.

### [ ] I-18 – Schema tidy-up
**File:** `src/Resources/install/install.sql:1-15`
- `int(11)` display width is deprecated in MySQL 8 → `int unsigned`.
- `casesensitive` / `exactmatch` `tinyint(1) DEFAULT NULL` → `NOT NULL DEFAULT 0` (model is non-nullable bool).
- `creationDate` / `modificationDate` `DEFAULT '0'` (string literal for int).
- `link varchar(255)` is short for real URLs (tracking parameters) → `varchar(1000)`/`text` or validate length in
  the controller (B-19); `text varchar(255)` likewise.
- Explicit `ENGINE=InnoDB` and collation (`utf8mb4_unicode_ci` / `utf8mb4_0900_ai_ci`) – the case-insensitive
  `LIKE` filter and the language index depend on it.
- Optional index on (`language`, `site`) for the processor query.

---

## Admin UI (`public/js/*.js`)

### [ ] I-19 – Modernise and de-duplicate `settings.js`
**File:** `public/js/settings.js`
- ExtJS 3 leftovers that are ignored by ExtJS 7: `forceFit`, `trackMouseOver`, `stripeRows`, `autoScroll`,
  `mode: "local"`, `triggerAction: "all"` (`:119-123`, `:126-132`, `:237-263`).
- `var` → `const`/`let`; duplicated date renderer (`:139-160`) and duplicated drop-zone code (`:184-215` vs
  `:273-312`) → shared helpers (B-27).
- Global component id `linkEditor` (`:112`) → `itemId` / reference via `this.rowEditing.editor.down(...)`.
- Custom renderers bypass the default `htmlEncode` renderer (site domain `:132-137`) – encode explicitly.
- Language combo shows only codes – use `pimcore.available_languages` display names.
- Shadowed variables (`var data = record.data` inside `onNodeDrop(…, data)`, `:204`, `:296`), `console.log` in
  catch blocks, unused `dd` variables and bound `i`/`myRowIndex` in `beforeedit` (`:213`).

### [ ] I-20 – `startup.js`: avoid the global `const pimcoreBundleGlossary`
**File:** `public/js/startup.js:41-47`, `:59`
Use `pimcore.globalmanager` or a namespaced instance (`pimcore.bundle.glossary.instance`); `try { get().activate() }
catch` (`:41-47`) for control flow → explicit `pimcore.globalmanager.exists('bundle_glossary')`.

---

## Tests, docs, translations

### [ ] I-21 – Test coverage and test isolation
**Files:** `tests/Unit/Document/Glossary/GlossaryTest.php`, `tests/Support/Helper/Unit.php:45-60`
- Entries saved in one test stay in the DB for all following tests (no cleanup/transaction) and results depend on
  cache state – add `tearDown()` cleanup + `Cache::clearTag('glossary')`.
- `testGlossaryWithLowerThenAndGreaterThenHtml` asserts the casing bug (B-01) – fix the expectation.
- Missing tests: XSS escaping (C-01, C-02), markup safety (C-03, C-04), `limit` (B-03), exact match/Unicode (B-04),
  full document (B-06), blocked tag config merge (B-23), controller CRUD incl. `null` fields (C-05, B-18 … B-22),
  installer re-run (B-24).
- `tests/Support/Helper/Unit.php:53` comment "install ecommerce framework" is a copy-paste leftover.

### [ ] I-22 – Documentation
**File:** `doc/18_Tools_and_Features/21_Glossary.md`
Document the `limit` option of the filter, the `pimcore_glossary.blocked_tags` configuration and its merge
semantics, that only trusted HTML must be passed (`{% apply %}` vs `{{ var|pimcore_glossary }}`, B-25), and the
`glossary` user permission. The HTML example uses a ```` ```php ```` fence for HTML.

### [ ] I-23 – Translations
**Directory:** `translations/`
All non-English files except `de`, `fr`, `sv` contain the English text; `keybinding_glossary` is English in every
locale. `admin.en.yaml` lacks the `---`/`...` document markers used by the other files and has no trailing newline.
Either translate or drop the copied files (Symfony falls back to `en`).

### [ ] I-24 – Events for glossary changes
**Files:** `src/Model/Glossary.php`, `src/Model/Glossary/Dao.php`
There are no pre/post save/delete events. Dispatching `GlossaryEvent` (like other Pimcore models) allows projects to
hook in (search index, CDN purge) and is the natural place for the cache invalidation from B-12.
