# UuidBundle – TODO 3: Improvements

Scope: `bundles/UuidBundle/config`, `bundles/UuidBundle/src`
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-2-BUGS.md](TODO-2-BUGS.md)

Code quality, readability, modernisation (Symfony 7.4 / PHP 8.4 idioms) and logical clean-ups. Nothing here is
currently broken. IDs are stable – refer to them as `I-xx` in later sessions. Line numbers are from commit `849ad2480d`.

---

## Symfony 7.4 modernisation

### [ ] I-01 – Inject the instance identifier and the logger instead of reading the static container
**Files:** `src/EventListener/UUIDListener.php:83`, `src/Model/Tool/UUID.php:87`
Both read `Pimcore::getKernel()->getContainer()->getParameter(...)`. In the listener, use constructor injection with
`#[Autowire(param: 'pimcore_uuid.instance_identifier')] private readonly ?string $instanceIdentifier`. Also inject
`LoggerInterface` there (needed for C-03). The static model API can keep a fallback, but it should go through one
small service (for example `UuidGenerator`) rather than the kernel.

### [ ] I-02 – Attribute-based listener registration
**Files:** `src/EventListener/UUIDListener.php:39-52`, `config/services.yaml:19`
Replace `EventSubscriberInterface` with `#[AsEventListener(event: ..., method: ...)]` attributes, one per event, with
typed event parameters (`ElementEventInterface` / `ClassDefinitionEvent`). This removes `extractElement()` and the
`Event` base type.

### [ ] I-03 – Installer wiring without an expression-language argument
**File:** `config/services.yaml:10-13`
`$bundle: "@=service('kernel').getBundle('PimcoreUuidBundle')"` needs `symfony/expression-language` and is evaluated
at runtime. Other Pimcore bundles use the same pattern. Check whether core offers a cleaner factory, for example
`#[Autowire(expression: ...)]` on the constructor, or a bundle-provided factory. If neither exists, keep the current
wiring and note why.

### [ ] I-04 – Use `stringNode()` and validation in `Configuration`
**File:** `src/DependencyInjection/Configuration.php`
Symfony 7.2+ `stringNode()`, `->cannotBeEmpty()`, `->validate()` (B-09). Also fix the info text grammar ("a Instance
identifier" → "an instance identifier") and split the long line.

### [ ] I-05 – Consider `AbstractBundle` (`loadExtension()` / `configure()`)
**Files:** `src/PimcoreUuidBundle.php`, `src/DependencyInjection/*`
Like ApplicationLoggerBundle I-01, this is probably blocked because `AbstractPimcoreBundle` extends `Bundle`. Check
first. If it is blocked, keep the extension and document the reason.

### [ ] I-06 – `PimcoreUuidBundle::isInstalled()` on every element event
**File:** `src/EventListener/UUIDListener.php:77-89`
Each add or delete event does a `PimcoreBundleManager` lookup plus a `SettingsStore` DB read, and bulk imports multiply
that. Check the cheap `instance_identifier` first (it is constant per container), and memoise the installed state per
request (`ResetInterface` for long-running workers).

---

## PHP 8.4 / typing

### [ ] I-07 – `declare(strict_types=1)` everywhere
**Files:** `Configuration.php`, `PimcoreUuidExtension.php`, `Installer.php`, `Migrations/Version20230203160742.php`,
`Model/Tool/UUID/Dao.php`, `Model/Tool/UUID/Listing/Dao.php`, `PimcoreUuidBundle.php`
Only `UUIDListener`, `UUID` and `Listing` declare it.

### [ ] I-08 – Model typing and return types
**File:** `src/Model/Tool/UUID.php`
- Replace `mixed $item` with `ElementInterface|ClassDefinitionInterface` (B-08).
- Return `static` instead of `UUID`, and drop the redundant `@return $this` docblocks.
- `setUuid()` returns `void`, while all other setters are fluent. Make it consistent.
- Remove the empty `/** * * @throws */` docblock stubs (lines 177-181, 198-202), and the empty `@internal` blocks with
  stray blank lines.
- Use a `private const string NAMESPACE_DNS = '6ba7b810-…'`, or `Uuid::NAMESPACE_DNS` from `symfony/uid`, which defines
  it, instead of the inline literal and comment (line 137-138).
- Consider `itemId` as `string` only. The DB column is `VARCHAR(50)`, and values loaded from the DB are already
  strings.

### [ ] I-09 – DAO fills `$this->model` instead of creating a second instance
**Files:** `src/Model/Tool/UUID/Dao.php:89-92`, `src/Model/Tool/UUID.php:191-196`
`UUID::getByUuid()` creates `$self` only to reach the DAO, and the DAO then builds another `new UUID()`. Use the
standard Pimcore pattern: `$this->assignVariablesToModel($data)`, and return the populated `$self`.

---

## Logic / data model

### [ ] I-10 – Remove the N+1 query in `Listing\Dao::load()`
**File:** `src/Model/Tool/UUID/Listing/Dao.php:36-40`
Currently it selects `uuid` and then calls `getByUuid()` for each row. Select `*` once and hydrate the models directly.
This also removes the C-01 race. Use the query builder instead of string concatenation, and add the `TABLE_NAME`
quoting.

### [ ] I-11 – Rethink the table keys
**Files:** `src/Resources/install/install.sql:7`, `src/Migrations`
- PK (`uuid`, `itemId`, `type`): `uuid` is derived from the other columns plus the identifier, so `uuid` alone should
  be unique, and `getByUuid()` already assumes it is. Use `PRIMARY KEY (uuid)` plus `INDEX (itemId, type)` for item
  lookups and deletes (B-05).
- `uuid CHAR(36)` could be `BINARY(16)`, but that is a breaking change and needs a migration plus a BC decision.
  Document it, and only do it if wanted.
- `DEFAULT CHARSET=utf8mb4` with no collation: pin `utf8mb4_bin` or `ascii_bin` for `uuid` so that comparisons do not
  depend on the server default.
These changes need a new migration (and C-02 style guards).

### [ ] I-12 – Merge the duplicated `create()` / `getByItem()` bodies
**File:** `src/Model/Tool/UUID.php:182-210`
Both do `new` → `setSystemInstanceIdentifier()` → `setItem()` → `setUuid(createUuid())`. `setUuid(createUuid())` is
redundant, because `createUuid()` already assigns `$this->uuid`. Extract one private factory. After B-04, the names
should say clearly which call writes to the DB.

### [ ] I-13 – Optional backfill console command
Elements created before the identifier was configured have no rows until `getByItem()` creates them lazily. A
`pimcore:uuid:rebuild` command (batched, idempotent, `#[AsCommand]`) would make the table complete, and could also
clean up orphans (B-05).

### [ ] I-14 – Installer: schema in PHP, migrations marked
**File:** `src/Installer.php`
Replace the `.sql` files and `runSqlQueries()` with a Doctrine `Schema` definition, or explicit
`executeStatement()` calls (B-11, B-06). Override `getLastMigrationVersionClassName()` (C-02). `runSqlQueries()` /
`installDatabaseTable()` can become private and typed, or disappear.

### [ ] I-15 – Migration hygiene
**File:** `src/Migrations/Version20230203160742.php`
Add `declare(strict_types=1)` and `isTransactional(): false` (DDL commits implicitly on MySQL), and use
`$schema->hasTable()` guards (C-02) and an irreversible `down()` (B-07).

---

## Readability / docs

### [ ] I-16 – Listener readability
**File:** `src/EventListener/UUIDListener.php`
Use early returns (`if (!$this->isEnabled()) { return; }`). `isEnabled()` can be
`return PimcoreUuidBundle::isInstalled() && $this->instanceIdentifier !== null;`. Make the methods `private` (the class
is `@internal`), and make the class `final`.

### [ ] I-17 – Update `doc/19_Development_Tools_and_Details/19_UUID_Support.md`
Correct the `getByUuid()` description (B-02) and document the not-found behaviour (C-01). Explain what happens when
the identifier changes, and that the length limit is 50 characters.

### [ ] I-18 – Add tests
There are no tests for this bundle. Minimum coverage:
- deterministic v5 generation (a known identifier/type/id gives a known UUID, which guards the namespace constant),
- `getByUuid()` for an unknown UUID,
- listing iteration (B-01),
- idempotent create (B-03),
- delete removes all rows of an item (B-05),
- listener swallows and logs errors (C-03).
