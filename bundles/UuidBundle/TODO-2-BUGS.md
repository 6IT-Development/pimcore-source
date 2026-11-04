# UuidBundle – TODO 2: Bugs

Scope: `bundles/UuidBundle/config`, `bundles/UuidBundle/src`
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

Incorrect behaviour, broken edge cases, leftover data, and documented features that do not work.
IDs are stable – refer to them as `B-xx` in later sessions. Line numbers are from commit `849ad2480d`.

---

## [x] B-01 – `UUID\Listing` cannot be iterated or counted

**Files:** `src/Model/Tool/UUID/Listing/Dao.php:34-43`, `src/Model/Tool/UUID/Listing.php`

`load()` returns the array but never calls `$this->model->setData($result)`. `AbstractListing::getData()` calls
`getDao()->load()` and returns `$this->data`, which stays `null`. As a result:

- `foreach (new UUID\Listing() as $uuid)` → `rewind()` → `reset(null)` → `TypeError`.
- `getData()` always returns `null`, and every call runs the query again.
- `count($listing)` / `getItems()` also break or return nothing.

Only a direct `$listing->load()` works.

**Fix direction:** `$this->model->setData($result); return $result;`, as the other Pimcore listing DAOs do.

**Done:** `Listing\Dao::load()` stores its result with `$this->model->setData($result)`, as the other listing DAOs do,
so `foreach`, `getData()` and the other `Iterator` methods work and the query runs once until the data is reset.
`count()` already used `getTotalCount()` and was not affected; `AbstractListing` has no `getItems()`.

---

## [ ] B-02 – Documented "get element for given UUID" does not exist

**Files:** `src/Model/Tool/UUID.php:57-61, 162-175`, `doc/19_Development_Tools_and_Details/19_UUID_Support.md:23-24`

- The docs say `$document = Tool\UUID::getByUuid($uuid);` returns the element. It actually returns the `UUID` model.
- `UUID` has a protected `$item` property but **no `getItem()`**. `$item` is only set by `setItem()` and stays `null`
  for models loaded from the DB. The bundle therefore offers no way to resolve a UUID back to its element.

**Fix direction:** add `getItem(): ElementInterface|ClassDefinitionInterface|null`, which resolves lazily from
`type` + `itemId` (`Element\Service::getElementById()`, or `ClassDefinition::getById()` for `class`). Correct the docs.

---

## [ ] B-03 – Race between `exists()` and `create()` → duplicate-key exception

**Files:** `src/Model/Tool/UUID.php:142-144`, `src/Model/Tool/UUID/Dao.php:40-45`

`createUuid()` checks `exists()` and then runs a plain `INSERT`. Two concurrent `getByItem()` calls for an element that
has no row yet (for example parallel API requests, or a worker plus an admin request) both pass the check, and one of
them throws `UniqueConstraintViolationException`. Inside the listener this escalates to C-03.

**Fix direction:** use one idempotent statement (`INSERT IGNORE`, or `Helper::upsert()` / the existing `Dao::save()`)
and drop the `exists()` round trip.

---

## [ ] B-04 – `getByItem()` writes to the DB, and the delete path does INSERT-then-DELETE

**Files:** `src/Model/Tool/UUID.php:182-189`, `src/EventListener/UUIDListener.php:65-75`

- `getByItem()` is a getter, but it calls `createUuid()`, which INSERTs a row when none exists. Reads therefore have
  write side effects (and fail on read-only replicas or connections).
- `onPostDelete()` calls `getByItem()`. For an element without a row, that INSERTs a row and DELETEs it again right
  away.

**Fix direction:** split the API into `compute(item): string` (pure, deterministic v5), `getByItem()` (compute, plus
an optional lazy create made explicit with a flag or a separate `getOrCreate()`), and a delete that does not create
anything.

---

## [ ] B-05 – Changing `instance_identifier` leaves orphaned rows that are never deleted

**Files:** `src/EventListener/UUIDListener.php:71-72`, `src/Model/Tool/UUID/Dao.php:63-74`

The delete path computes the UUID from the **current** identifier and deletes `WHERE itemId AND type AND uuid`. Rows
written under a previous identifier (the config info text itself warns about changing it), or a row whose stored
`instanceIdentifier` differs, are never removed. `getByUuid()` then keeps resolving UUIDs of deleted elements.

**Fix direction:** on element delete, `DELETE FROM uuids WHERE itemId = ? AND type = ?`, which covers every UUID of
the item. Add an index on (`itemId`, `type`) for this (see I-11).

---

## [ ] B-06 – Re-installing silently wipes all UUIDs

**Files:** `src/Resources/install/install.sql:1`

`install.sql` starts with `DROP TABLE IF EXISTS uuids;`. Any re-install drops the existing table without a warning, for
example:

- after the settings-store flag was lost or reset,
- after `pimcore:bundle:uninstall --keep-data`-style manual flag changes,
- in environments where core migration `Version20230113165612` toggled the flag.

UUIDs that other instances imported, or that were stored under an older identifier, cannot be regenerated.

**Fix direction:** `CREATE TABLE IF NOT EXISTS`. Only `uninstall.sql` should drop the table.

---

## [ ] B-07 – `Version20230203160742::down()` corrupts or fails on class-definition rows

**Files:** `src/Migrations/Version20230203160742.php:33-36`

`down()` changes `itemId` back to `INT(11) UNSIGNED`. Class-definition rows (`type = 'class'`) store string IDs (for
example `CAR`, `news`):

- In strict mode the ALTER fails.
- In non-strict mode the IDs are truncated to `0`. That causes PK collisions, and the class UUID mapping is
  irreversibly lost.

**Fix direction:** make `down()` throw `IrreversibleMigration` (`$this->throwIrreversibleMigrationException()`), or
delete or abort on non-numeric `itemId` rows first.

---

## [ ] B-08 – `setItem()` / `getByItem()` accept anything and fail with engine errors

**Files:** `src/Model/Tool/UUID.php:162-175, 182, 203`

- The parameter is `mixed $item`. A non-object fails with *"Call to a member function getId() on …"*.
- An object that is neither `ElementInterface` nor `ClassDefinitionInterface` leaves `$type` uninitialised.
  `createUuid()` then throws *"must not be accessed before initialization"*.
- `Element\Service::getElementType()` returns `?string`. For an unknown `ElementInterface` implementation,
  `setType(null)` throws `TypeError` (`strict_types=1`).
- An unsaved element or class has `getId() === null`, so `setItemId(int|string)` throws `TypeError`.

**Fix direction:** type the parameter as `ElementInterface|ClassDefinitionInterface`, and throw an
`InvalidArgumentException` with a clear message for a `null` id or an unknown type.

---

## [ ] B-09 – `instance_identifier` is not validated

**Files:** `src/DependencyInjection/Configuration.php:28-31`, `src/Resources/install/install.sql:6`

- `scalarNode` accepts ints, bools and floats, and has no length check. The DB column is `VARCHAR(50)`, so a longer
  value → C-03.
- An empty string `''` is accepted and silently disables the feature (`!empty()` in `isEnabled()`), while
  `setSystemInstanceIdentifier()` throws for it.
- The value is used as the v5 name `identifier~type~id`, so an identifier containing `~` could, in theory, produce the
  same name string for different inputs.

**Fix direction:** use `stringNode()` (Symfony 7.2+) with `->cannotBeEmpty()`, plus a `validate()` that checks a length
of 50 or less and a safe character set. Keep `null` as the "disabled" default.

---

## [ ] B-10 – Guard checks are dead code because typed properties are uninitialised

**Files:** `src/Model/Tool/UUID.php:37-55, 73-76, 133-135, 149-152`, `src/Model/Tool/UUID/Dao.php:65-68`

`$itemId`, `$type`, `$uuid` and `$instanceIdentifier` are typed and have no default. On a fresh `new UUID()`:

- `createUuid()`'s `if (!$this->getInstanceIdentifier())` cannot run, because the getter throws `Error` first.
- `Dao::delete()`'s `if (!$uuid)` cannot run, because `getUuid()` throws `Error`.

The intended domain exceptions are never raised.

**Fix direction:** make these properties nullable with a `null` default and getters returning `?string` (or use
`isset()` checks), so the guards work as written.

---

## [ ] B-11 – SQL install/uninstall runner is fragile

**Files:** `src/Installer.php:35-44`

- `file_get_contents()` returning `false` is not checked. Passing `false` to `executeQuery()` gives a `TypeError`.
- `executeQuery()` is used for DDL, which returns no result set. `executeStatement()` is the correct DBAL call.
- `install.sql` holds two statements in one string. That only works with `pdo_mysql` emulated prepares and
  multi-statements enabled, and breaks with the `mysqli` driver or with `PDO::MYSQL_ATTR_MULTI_STATEMENTS => false`.

**Fix direction:** run the schema through Doctrine `Schema`/`addSql` or explicit single statements in PHP (see I-14),
and drop the `.sql` files.

---

## [ ] B-12 – `Listing\Dao::getTotalCount()` hides every error as `0`

**Files:** `src/Model/Tool/UUID/Listing/Dao.php:49-56`

`catch (Exception) { return 0; }` swallows SQL errors in user-supplied conditions, a missing table and connection
errors. Callers then see "no UUIDs" instead of the error.

**Fix direction:** remove the try/catch, or catch only the specific DBAL exception and log it.
