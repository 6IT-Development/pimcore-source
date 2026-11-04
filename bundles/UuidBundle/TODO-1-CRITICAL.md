# UuidBundle – TODO 1: Critical errors

Scope: `bundles/UuidBundle/config`, `bundles/UuidBundle/src`
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-2-BUGS.md](TODO-2-BUGS.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

These items **crash public API calls, block deployments, or make element saves and deletes fail for every element**.
IDs are stable – refer to them as `C-xx` in later sessions. Line numbers are from commit `849ad2480d`.

---

## [x] C-01 – `UUID::getByUuid()` throws `TypeError` for any unknown UUID

**Files:** `src/Model/Tool/UUID/Dao.php:76-93`, `src/Model/Tool/UUID.php:191-196`

```php
$data = $queryBuilder->executeQuery()->fetchAssociative();   // false when no row
$model = new UUID();
$model->setValues($data);                                     // setValues(array $data = [])
```

- `fetchAssociative()` returns `false` when no row matches. `AbstractModel::setValues()` is typed `array`, so a lookup
  of an unknown, deleted or mistyped UUID ends in an uncatchable-by-contract `TypeError` rather than `null` or a
  domain exception.
- `getByUuid()` is the main read API in the docs (`doc/19_Development_Tools_and_Details/19_UUID_Support.md`). Callers
  get UUIDs from other instances (that is what the bundle is for), so unknown UUIDs are a normal input.
- `Listing\Dao::load()` (`src/Model/Tool/UUID/Listing/Dao.php:39`) calls `getByUuid()` for each row it found. A row
  deleted between the two queries crashes the whole listing.

**Fix direction:** return `?UUID` (`null` when there is no row), or throw a documented `NotFoundException`. Fill
`$this->model` in the DAO instead of building a second instance (see I-09). Change `Listing\Dao` to load full rows in
one query (see I-10).

**Done:** `UUID::getByUuid()` now returns `?UUID` and gives `null` for an unknown UUID. The internal
`Dao::getByUuid()` fills `$this->model` and returns `false` when no row matches, instead of building a second instance
and passing `false` to `setValues()` (part of I-09). `Listing\Dao::load()` skips rows deleted between its two queries.
Not addressed: the N+1 query in `Listing\Dao::load()` (I-10) and the docs (B-02 / I-17).

---

## [x] C-02 – Migration `Version20230203160742` fails when the bundle is registered but not installed

**Files:** `src/Migrations/Version20230203160742.php:28-31`, `src/Installer.php`, `config/pimcore/doctrine_migrations.yaml`

- The migration path is always registered (`config/pimcore/config.yaml` → `doctrine_migrations.yaml`), even while the
  bundle is not installed.
- `up()` runs `ALTER TABLE uuids ...` without checking that the table exists. If the bundle is in `config/bundles.php`
  but `uuids` does not exist yet (fresh project, or the bundle was uninstalled), `doctrine:migrations:migrate` stops
  with *"Table 'uuids' doesn't exist"*. That blocks every later migration in the same run, core migrations included.
- `Installer` does not override `getLastMigrationVersionClassName()`. `SettingsStoreAwareInstaller::markInstalled()`
  therefore never marks the bundle's migrations as executed, so the migration stays pending after
  `pimcore:bundle:install` even though `install.sql` already creates the final schema.

**Fix direction:** guard `up()`/`down()` with `$schema->hasTable('uuids')` (or `skipIf()`), and override
`getLastMigrationVersionClassName()` in `Installer` to return `Version20230203160742::class`, so that installing marks
the migrations as done. Mark the migration `isTransactional(): false`, because MySQL DDL commits implicitly (see I-15).

**Done:** `up()` and `down()` of `Version20230203160742` return without SQL when the `uuids` table does not exist, the
same guard style as the core migrations, so a registered but not installed bundle no longer stops
`doctrine:migrations:migrate`. The migration is `isTransactional(): false`.
`Installer::getLastMigrationVersionClassName()` returns `Version20230203160742::class`, so `pimcore:bundle:install`
marks the bundle migrations as executed and uninstalling unmarks them. Not addressed: `declare(strict_types=1)` (I-15)
and the irreversible `down()` (B-07).

---

## [ ] C-03 – Listener exceptions make already-committed element saves and deletes fail

**Files:** `src/EventListener/UUIDListener.php:54-75`, `src/Model/Tool/UUID.php:131-147`, `src/Model/Tool/UUID/Dao.php:40-45`

`onPostAdd()` / `onPostDelete()` run inside the `try` block of `AbstractObject::save()`, `Document::save()` and
`Asset::save()` (for example `models/DataObject/AbstractObject.php:623`) after the DB transaction has committed. Any
exception from the listener:

- dispatches `POST_ADD_FAILURE` and is re-thrown to the caller (admin UI, importer, API), although the element **is
  saved**. Callers retry and create duplicate elements, or abort a batch halfway through.
- Any of these triggers it, for **every** asset/document/object/class:
  - `instance_identifier` longer than 50 characters: the `instanceIdentifier VARCHAR(50)` column rejects it in strict
    SQL mode. The config is not validated (B-09).
  - the `uuids` table is missing while the bundle counts as installed. Core migration
    `bundles/CoreBundle/src/Migrations/Version20230113165612.php` sets `BUNDLE_INSTALLED__…PimcoreUuidBundle = true`
    without creating the table.
  - duplicate-key race in `exists()` → `create()` (B-03).
  - any DB hiccup during the extra INSERT/DELETE.

**Fix direction:** wrap the listener body in `try/catch (\Throwable)` and log the error through an injected
`LoggerInterface`. UUID bookkeeping must never fail a committed element operation, because missing rows are created
lazily by `getByItem()` anyway. In addition, make the writes idempotent (B-03) and validate the config (B-09).
