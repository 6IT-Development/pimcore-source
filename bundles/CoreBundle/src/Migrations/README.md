# CoreBundle migrations

## Supported upgrade baseline: Pimcore 11.5.x

- **Fresh installs never run these migrations.** `Installer::markMigrationsAsDone()`
  (`bundles/InstallBundle/src/Installer.php`) records all of them as executed. The schema comes from
  `bundles/InstallBundle/dump/install.sql`.
- **Existing databases** are only supported when upgrading from Pimcore 11.5.x. Such a database already has
  every migration up to and including `Version20241114142759` applied.

## Live migrations

These may still be pending on an 11.5.x database installed from an earlier patch release, and are maintained:

| Migration | Notes |
|---|---|
| `Version20250416120333` | Index on `versions.public`. Looks the index up by column, because earlier runs created it with a generated `IDX_…` name. |
| `Version20251217000100` | Drops sensitive columns from `http_error_log`; only drops the columns that still exist. |
| `Version20261029000100` | Recomputes `tags.idPath` from `parentId` (moving a root-level tag corrupted its subtree, models C-29). Data only; leaves orphans, cycles and rows that would violate `idPath_name` unchanged with a warning. |

Any schema change made in this fork needs a **new** migration **and** the matching change in `install.sql`.

## Frozen migrations

Everything older than `Version20250416120333` is frozen:

- Do not edit them (other than mechanical code-style/PHP-version changes) and do not run their `down()`.
- They are kept so `doctrine:migrations:status` stays clean on old databases and the history stays readable.
  Deleting them would show them as "executed but unavailable" on every existing database.
- They target schemas older than 11.5 (e.g. `objects.o_id`) and are not expected to work against the current
  schema.

Known defects, deliberately left unfixed (relevant only for upgrades from 10.x / early 11.x or rollbacks):

| Migration | Defect |
|---|---|
| `Version20211103055110` | `up()`: `` `type` = NULL `` never matches, so the following `NOT NULL` change can fail. `down()`: `fetchAssociative` only processes the first class. |
| `Version20220120121803` | `up()`: `documents_editables` FK existence is checked on the wrong table (leftover loop variable). |
| `Version20221003115124` | `INFORMATION_SCHEMA.COLUMNS` lookups lack a `TABLE_SCHEMA` filter. |
| `Version20221222181745` | Adds foreign keys without existence checks (not re-runnable). |
| `Version20220908113752` | Unchecked `preg_match` result; already-renamed tables would be renamed to `…archive__`. |
| `Version20220809164000` | Undefined-key access on `staticGeneratorEnabled`. |
| `Version20230424084415` | `unserialize()` without `allowed_classes => false`. |
| `Version20230321133700` | PHP fallback is O(n²) over distinct timestamps. |
| `Version20221116115427`, `Version20230107224432`, `Version20230111074323`, `Version20230222075502`, `Version20230406113010` | `down()`: permission `REGEXP_REPLACE` removes both surrounding commas (`a,X,b` → `ab`) and misses a trailing entry. |
| `Version20220419120333` | `down()`: inverted `hasIndex` check, always throws. |
| `Version20220614115124` | `down()`: inverted `hasColumn` checks, never drops anything. |
| `Version20220119082511` | `down()`: checks the `favourites` FK name on `gridconfig_shares`. |
| `Version20210630062445`, `Version20230202152342` | `down()`: method call on a possibly-null `SettingsStore::get()` result. |
| `Version20230110130748`, `Version20221220152444`, `Version20230125164101`, `Version20230222075502`, `Version20230406113010` | Mark bundles as installed that this fork does not ship (TinyMCE, WebToPrint, Personalization, GoogleMarketing, Newsletter). |
