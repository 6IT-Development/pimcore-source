# models – TODO 1: Critical errors

Scope: **(1)** `models/Asset.php` and `models/Asset/**` (Dao, Listing, Service, Folder, Image, Image/Thumbnail/**, Thumbnail/**,
Video, Video/**, Document, Document/**, MetaData/**, WebDAV/**, Enum, Archive/Audio/Text/Unknown), plus the helpers they
directly call where a defect originates there (`lib/Video/Adapter/Ffmpeg.php`, `lib/Config/LocationAwareConfigRepository.php`,
`lib/Messenger/Handler/AssetUpdateTasksHandler.php`)
**(2)** `models/WebsiteSetting.php` and `models/WebsiteSetting/**` (Dao, Listing, Listing/Dao), plus the direct consumer
`lib/Config.php::getWebsiteConfig()` (`:173-262`), `lib/Twig/Extension/WebsiteConfigExtension.php`, the
`website_settings` schema (`bundles/InstallBundle/dump/install.sql:603-615`) and `tests/Model/WebsiteSetting/WebsiteSettingTest.php`
**(3)** `models/Dependency.php` and `models/Dependency/Dao.php`, plus the direct consumers where a defect originates
there (`lib/Messenger/Handler/ElementDependenciesHandler.php`, `models/Element/AbstractElement.php::getDependencies()`,
the dependency helpers in `models/Element/Service.php:163-263`), the `dependencies` schema
(`bundles/InstallBundle/dump/install.sql:63-73`) and `tests/Model/Element/DependenciesTest.php`
**(4)** `models/Translation.php` and `models/Translation/**` (Dao, Listing, Listing/Dao), plus the direct consumers where
a defect originates in the model interplay (`lib/Translation/TranslationEntriesDumper.php`,
`lib/Translation/Translator.php::lazyInitialize()` / `checkForEmptyTranslation()` (`:138-298`),
`models/DataObject/QuantityValue/Service.php::generateDefinitionJson()` (`:66-90`)), the `translations_messages` schema
(`bundles/InstallBundle/dump/install.sql:432-444`) and `tests/Unit/Translation/TranslatorTest.php`
**(5)** `models/Site.php` and `models/Site/**` (Dao, Listing, Listing/Dao), plus the direct consumers where a defect
originates in the model interplay (`lib/Tool/Frontend.php::getSiteMapping()` (`:70-95`),
`bundles/CoreBundle/src/EventListener/Frontend/RoutingListener.php::resolveSite()` (`:101-127`),
`lib/Document/Renderer/DocumentRenderer.php:95-103`, `models/Document.php:690-830` (site path rewriting)), the `sites`
schema (`bundles/InstallBundle/dump/install.sql:370-383`) and `tests/Model/Tool/FrontendTest.php`
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-2-BUGS.md](TODO-2-BUGS.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md),
[CoreBundle TODO-1](../bundles/CoreBundle/TODO-1-CRITICAL.md) (C-13, C-14, C-15 already cover WebDAV permissions/CSRF and
the public thumbnail route; they are not repeated here; C-08 covers request state on shared services in worker runtimes)

These items cause **data loss or corruption** (tree paths, binaries, versions), **source files deleted outside Pimcore**,
**open DB transactions in workers**, **crashes in core asset flows** (create, folder move, video/PDF processing, frontend
thumbnail rendering) and **path traversal / false security status**, and in Scope 2 **deleted or outdated website
settings served indefinitely**, and in Scope 3 **SQL injection in the dependency filter** and **silently lost
dependency rows** (editors are told an element is unused and delete it), and in Scope 4 **translation texts
overwritten or replaced by their key**, **site-wide 500s from one `NULL` row**, **a translation-language permission
bypass** and **SQL identifier injection through the domain name**, and in Scope 5 **a whole site answering 404 after
its root page is renamed or moved** and **site definitions permanently lost when the root page is deleted**.
IDs are stable – refer to them as `C-xx` in later sessions. Line numbers are from commit `1b0711d927` (Scope 2:
`7f0331cab9`, Scope 3: `2d382456c7`, Scope 4: `3e2bf1c4d8`, Scope 5: `0b659e9ce0`).

> Notes
> - `models/Asset.php`, `models/Asset/Dao.php`, `Asset/Listing/Dao.php`, `Asset/Image/Thumbnail.php` and both thumbnail
>   `Config/Dao.php` / `Config/Listing/Dao.php` files have **no** `declare(strict_types=1)`. Scalar coercion applies there,
>   but `null` to a non-nullable parameter still throws `TypeError`. The rest of `models/Asset/**` is strict.
> - No hard Symfony 7.4 / PHP 8.4 incompatibility (removed API, implicit nullable parameter) was found. The remaining
>   compatibility work is modernisation and is listed in TODO-3.

---

## [ ] C-01 – Folder rename/move rewrites every occurrence of the old path in DB (assets, workspaces, and *other element types'* properties)

**File:** `models/Asset/Dao.php:218`, `:221`, `:224`

```php
'update assets set `path` = replace(`path`,' . quote($oldPath . '/') . ',' . quote($new . '/') . ") ... where `path` like '<old>/%'"
```

- MySQL `REPLACE()` replaces **all** occurrences. The `WHERE … LIKE '<old>/%'` only selects rows; it does not anchor the
  replacement. Renaming `/a` → `/b` turns `/a/x/a/photo.jpg` into `/b/x/b/photo.jpg`, while `Asset::save()` moves the
  physical tree to `/b/x/a/…` (`Asset.php:553`). DB row and binary no longer match: the asset appears lost, thumbnails
  break, the next save writes to the wrong place.
- Same corruption for `users_workspaces_asset.cpath` (`:221`) → permissions move to the wrong folder.
- `properties` (`:224`) holds rows for documents, assets and objects (`ctype enum`, install.sql:302) but the UPDATE has
  no `AND ctype = 'asset'`. Renaming asset folder `/products` rewrites `cpath` of every document/object property under
  `/products/`. `cpath` is `utf8_general_ci`, so different casing matches too. Property inheritance (ordered by `cpath`)
  silently changes.
- `Document/Dao.php:214` and `DataObject/AbstractObject/Dao.php:173` use the same pattern (outside scope, same fix).

**Fix direction:** anchor the replacement with bound parameters:
`` SET `path` = CONCAT(:new, SUBSTRING(`path`, CHAR_LENGTH(:old) + 1)) WHERE `path` LIKE :oldLike ``. Same for both
`cpath` updates, and add `AND ctype = 'asset'` on `properties`.

---

## [ ] C-02 – `Asset::updateChildPaths()` (storage fallback) always fails: listing generator consumed by `iterator_count()`

**File:** `models/Asset.php:1709-1714` (called from `save()` `:556` on `UnableToMoveFile`, and `relocateThumbnails()` `:1788`)

```php
$children = $storage->listContents($oldPath, true);
$totalChildren = iterator_count($children);
...
foreach ($children as $child) {   // "Cannot traverse an already closed generator"
```

- Flysystem `listContents()` returns a `DirectoryListing` wrapping a single `Generator` (`pipeListing()`);
  `getIterator()` returns the same generator. `iterator_count()` exhausts it.
- This fallback is the normal path for folder moves on S3/GCS/Azure adapters (directory `move` of a non-existent key
  throws `UnableToMoveFile`). Every folder rename/move there throws; DB rolls back, but thumbnails already moved by
  `relocateThumbnails()` (`:547`) stay moved.
- Once the generator is fixed, `:1717` `str_replace($oldPath, $newPath, '/' . $src)` hits the same "all occurrences /
  prefix collision" problem as C-01 (`/foo/x/foo/y.jpg` → `/bar/x/bar/y.jpg`, `/foo/x/foobar` → `/bar/x/barbar`), so
  files are moved to wrong locations. Fix both together.

**Fix direction:** `$children = $storage->listContents($oldPath, true)->toArray();`, count/iterate that array. Build the
destination with `$newPath . substr('/' . $src, strlen($oldPath))` after asserting the `$oldPath . '/'` prefix.

---

## [ ] C-03 – `Asset::create()` with a rewindable non-local stream always crashes (temp file deleted before MIME guess)

**File:** `models/Asset.php:355-370`

```php
$mimeTypeGuessData = $tmpFile;
if (!$isRewindable) { $data['stream'] = $dest; } else { fclose($dest); unlink($tmpFile); }
...
$mimeType = MimeTypes::getDefault()->guessMimeType($mimeTypeGuessData);   // file no longer exists
```

- `FileinfoMimeTypeGuesser::guessMimeType()` throws `InvalidArgumentException` for a missing file.
- `Asset::create(1, ['filename' => 'a.jpg', 'stream' => fopen('php://temp', 'r+')])` (and `php://memory`, HTTP
  streams that are rewindable, PSR-7 stream wrappers) always fails. `checkMaxPixels()` (`:385`) would use the deleted
  file as well.

**Fix direction:** run the MIME guess and `checkMaxPixels()` before unlinking; clean up `$tmpFile` in a `finally` at the
end of `create()` (see also B-11).

---

## [ ] C-04 – `checkMaxPixels()` deletes the caller's original source file

**File:** `models/Asset.php:425-439` (`unlink($localPath)` at `:433`), inputs from `:354` and `:373`

- `$mimeTypeGuessData` is the caller's own file when `sourcePath` is used (`:373`) or when the passed stream is a local
  file (`:354`). For an image over `assets.image.max_pixels`, `:433` unlinks it.
- `Asset::create(1, ['filename' => 'x.jpg', 'sourcePath' => '/import/huge.jpg'])` permanently deletes
  `/import/huge.jpg` – data loss outside Pimcore storage (import directories, NFS mounts, user uploads kept elsewhere).
- The stream opened at `:375` is leaked on this path.
- The exception message embeds `{$data['filename']}` unescaped in HTML (`:435`) – see B-14.

**Fix direction:** `checkMaxPixels()` only validates; `create()` unlinks only files it created itself (`$tmpFile`), in a
`finally`.

---

## [ ] C-05 – `save()` / `delete()` catch only `Exception`: an `Error` leaves the DB transaction open (silent data loss in workers)

**File:** `models/Asset.php:520-575` (inner retry loop catches `Exception` at `:570`, `:573`), `:633`; `delete()` `:994-1047`
(`:1040`, `:1043`)

- Any `\Error` thrown between `beginTransaction()` and `commit()` skips `rollBack()` and the `POST_*_FAILURE` events.
  Reachable triggers inside the transaction:
  - `resolve($type)` returns `?string`, then `supports(null)` (`:781`) → `TypeError`;
  - `getDao()->getCurrentFullPath()` returns `null`/`''`, then `$storage->fileExists(null)` (`:758`) → `TypeError`
    (B-13);
  - `TypeError`s from listeners, metadata data types, `Video`/`Image` `update()` overrides.
- In PHP-FPM the connection is reset at request end. In messenger workers / FrankenPHP / RoadRunner the connection is
  long-lived, so every following `beginTransaction()` nests inside the dead transaction and **nothing is committed again**
  until the worker restarts.

**Fix direction:** catch `\Throwable` in the inner retry loop and the outer handlers (rethrow after rollback and failure
event). Same in `delete()`.

---

## [ ] C-06 – Binary replacement is non-atomic (delete, then move) – content lost if the move fails

**File:** `models/Asset.php:738-750`

```php
$storage->writeStream($tempFilePath, $src);
$storage->delete($path);
$storage->move($tempFilePath, $path);
```

- Between `delete` and `move` the binary does not exist; concurrent readers (thumbnail workers, downloads, WebDAV GET)
  get "not found".
- If `move()` fails, the original is already gone, the DB transaction rolls back to the old metadata, and an orphan
  `temp_xxxx.ext` is left **inside the asset's folder** (`$this->getRealPath() . uniqid('temp_')`), visible to storage
  listings and `updateChildPaths()`.
- The `delete()` is unnecessary: Flysystem local `move()` is `rename()`, which atomically replaces the directory entry
  and keeps the old inode for version hardlinks (the reason given in the comment at `:738-741`).

**Fix direction:** drop the `delete()` and rely on an overwriting move; write the temp file to a hidden/temp prefix, not
next to the asset; on failure remove the temp file.

---

## [ ] C-07 – `__clone()` closes the original asset's stream: pending new data is dropped and the version records the old binary

**File:** `models/Asset.php:1671-1679` (`closeStream()` at `:1678`)

- `clone` copies the resource handle; `closeStream()` on the clone `fclose`s the handle the original also holds.
- `Version::marshalData()` clones the element through DeepCopy. So
  `$asset->setStream($new); $asset->saveVersion();` (and `save()` with versioning) closes the pending stream;
  `getStream()` then reopens the **stored** file. The version stores the old binary/hash and the new data is silently
  lost (admin "upload new version" with versioning, API imports that clone before save).
- Any user-land `clone $asset` while data is pending has the same effect.

**Fix direction:** in `__clone()` set `$this->stream = null` without `fclose()` (or copy the stream into a fresh
`php://temp` when `dataChanged` is set).

---

## [ ] C-08 – WebDAV `MOVE` reports success after a failed move, and deletes the source before the target is saved

**File:** `models/Asset/WebDAV/Tree.php:39-87` (catch-all at `:84-86`; overwrite branches `:51-56`, `:59-66`)
(Related to CoreBundle **C-13** – that item is the missing permission check; this one is error handling / ordering.)

- Sabre `CorePlugin::httpMove()` deletes an existing destination first (`File::delete()`), then calls `Tree::move()`,
  then answers `201`/`204`. `Tree::move()` catches every `Exception` and only logs it (e.g.
  `DuplicateFullPathException`, storage errors) – the client is told the move succeeded after its destination was
  already deleted.
- In the same-directory overwrite branches, `$sourceAsset->delete()` runs **before** `$asset->save()`; if save throws,
  the source is gone and the target was not updated.
- `$sourceAsset` / `$parent` (`:53`, `:63`, `:75`) can be `null` (e.g. `getValidKey()` sanitised the name), giving an
  `Error` that `catch (Exception)` does not catch → 500 after part of the work is done.

**Fix direction:** run the move in a DB transaction; save the target before deleting the source; throw
`Sabre\DAV\Exception\NotFound` / `Conflict` for null lookups and rethrow failures as `Sabre\DAV\Exception`.

---

## [ ] C-09 – Thumbnail config name is unvalidated and goes into YAML file paths (path traversal on write and delete)

**Files:** `models/Asset/Image/Thumbnail/Config.php:391-396` (`setName()`), `Image/Thumbnail/Config/Dao.php:104`
(`saveData`), `:140` (`deleteData`) → `lib/Config/LocationAwareConfigRepository.php:236-240` (`$directory . '/' . $key . '.yaml'`,
`dumpFile`), `:255` (`unlink`). Same for `Video/Thumbnail/Config.php` / `Video/Thumbnail/Config/Dao.php`.

- Neither model nor DAO restricts the name. A config named `../../../config/packages/evil` writes a YAML file Symfony
  loads on next cache warmup (service/parameter injection); a delete with a crafted name unlinks any writable `*.yaml`.
- The same name is concatenated into the thumbnail storage directory (`Image/Thumbnail/Processor.php:154`) and into the
  `clearTempFiles()` regex.
- Exploitability depends on the admin UI (admin-ui-classic, not in this repo) validating names; if it does, this is
  defense-in-depth – but the model API (`Config::setName()->save()`, imports, migrations) is unguarded either way.

**Fix direction:** validate in `setName()` / `Dao::save()` (`^[A-Za-z0-9_\-]+$`); in
`LocationAwareConfigRepository::getVarConfigFile()` assert the resolved path stays inside the config directory.

---

## [ ] C-10 – Video thumbnail config without bitrates crashes with `TypeError` (frontend 500)

**Files:** `models/Asset/Video/Thumbnail/Processor.php:122-123`, `models/Asset/Video.php:115`;
`lib/Video/Adapter/Ffmpeg.php:350`, `:363`

```php
$converter->setAudioBitrate($config->getAudioBitrate());   // ?int, default null
$converter->setVideoBitrate($config->getVideoBitrate());   // Ffmpeg::set*Bitrate(int)
```

- Both bitrates are optional in the config tree (`Configuration.php:542-543`) and default to `null`
  (`Video/Thumbnail/Config.php:80`, `:86`). Processor is `strict_types` → `TypeError`.
- `Video::getThumbnail()` catches only `Exception` (`:115`), so the `Error` reaches frontend rendering
  (`Document/Editable/Video.php:430`) → page 500. Trigger: any YAML video thumbnail that only defines `items`.

**Fix direction:** call the setters only for non-null values (or make the adapter accept `?int`); catch `\Throwable` in
`Video::getThumbnail()`.

---

## [ ] C-11 – `Video\Thumbnail\Processor::execute()` crashes on a missing job or deleted asset; the global lock and TmpStore entry are never released

**File:** `models/Asset/Video/Thumbnail/Processor.php:200-221` (job-not-found branch), `:231-236`, `:288-293`

- Job-not-found branch: `$instance` is a fresh `new self()`; `$instance->getConfig()` reads the uninitialized typed
  property → `Error`. `Model\Asset::getById($assetId)` can be `null` → `getCustomSetting()` on null. The branch meant to
  mark the job as `error` can never work.
- Main path: the global `video-transcoding` lock is acquired (`:232-233`) **before** `getById()`; a deleted asset makes
  `$asset->getTemporaryFile()` (`:236`) fail on null. Neither lock nor TmpStore entry is released in a `finally`.
- The messenger handler fails, retries, and ends in the failure transport; until the lock TTL (7200 s) expires every
  other video conversion blocks (B-67).

**Fix direction:** load the job before using its config (or pass the config name in the message); return early on a
null asset; wrap lock and temp-file handling in `try/finally`.

---

## [ ] C-12 – Crafted/corrupt video aborts asset processing (spherical XMP reader: `TypeError` / `RuntimeException`)

**File:** `models/Asset/Video.php:270-325` (`:313`, `:320-321`); caller `lib/Messenger/Handler/AssetUpdateTasksHandler.php:125`

```php
$xml = @simplexml_load_string($buffer);
$data = object2array($xml);      // object2array(object $node): false → TypeError
```

- Malformed XML between `<rdf:SphericalVideo` and `</rdf:SphericalVideo>` → `TypeError`; a missing close tag →
  `RuntimeException` (`:313`), leaving the stream unclosed. Neither is caught in `processVideo()`.
- `handleEmbeddedMetaData()`, `saveAsset()` and preview generation in the handler are skipped: duration/dimensions are
  never persisted and the message retries. Any `.mp4`/`.webm` upload containing that byte sequence triggers it.

**Fix direction:** check `$xml instanceof \SimpleXMLElement`; catch, log and return `[]`; close the stream in `finally`.

---

## [ ] C-13 – Video image thumbnail crashes `getPath()` when frame extraction fails (frontend 500)

**Files:** `models/Asset/Video/ImageThumbnail.php:140-153`; `models/Asset/Thumbnail/ImageThumbnailTrait.php:292-315`

- The early `return`s exit `generate()` before the error-placeholder fallback (`:181-186`) and the event dispatch.
  `$this->pathReference` stays `[]`. `getPath()` then calls `convertToWebPath([], …)`: frontend →
  `urlencode_ignore_slash(null)` `TypeError`; backend → returns `null` from `getPath(): string` → `TypeError`. Other
  trait methods read the undefined `$pathReference['type']`.
- Trigger: corrupt video, or ffmpeg `saveImage()` returning `false`. Any template calling
  `$video->getImageThumbnail('x')` then returns 500. The log messages say "document" (copy-paste); the lock is released
  only by the destructor.

**Fix direction:** replace the `return`s with a flag / `try/finally` so control always reaches the placeholder fallback;
release the lock in `finally`.

---

## [ ] C-14 – PDF JavaScript scan is trivially bypassable and always returns `true` (false `SAFE` security status)

**File:** `models/Asset/Document.php:146-176`; caller `lib/Messenger/Handler/AssetUpdateTasksHandler.php:78`

- The file is scanned in independent 1024-byte chunks with **no overlap**: `/JS` or `/JavaScript` straddling a chunk
  boundary is never seen. PDF name escapes (`/J#53`) and compressed object streams also evade it. The loop also stops
  at the first chunk `<= 3` bytes. The asset is then marked `PdfScanStatus::SAFE`, which the admin UI uses to decide
  whether opening the PDF inline is safe.
- The method returns `true` for both UNSAFE (`:166`) and SAFE (`:175`). The name implies "contains JS"; any consumer
  reading it literally treats every PDF as unsafe.

**Fix direction:** keep an overlap buffer between chunks (as `EmbeddedMetaDataTrait::getXMPData` does) and treat the
result as `UNKNOWN`/unsafe unless the PDF is actually parsed; return the `PdfScanStatus` enum (or rename the method).

---

# Scope 2 – WebsiteSetting

> Notes
> - `WebsiteSetting.php` and `WebsiteSetting/Listing.php` are `strict_types`; `WebsiteSetting/Dao.php` and
>   `WebsiteSetting/Listing/Dao.php` are not. No hard Symfony 7.4 / PHP 8.4 incompatibility was found (no implicit
>   nullable parameters, no removed APIs). Modernisation is listed in TODO-3 (I-32–I-40).
> - DB hydration uses `setValues($data, true)` (`lib/Model/Dao/DaoTrait.php:42`), so `NULL` columns are skipped and
>   non-nullable setters (`setCreationDate(int)`) do not crash on legacy rows.

---

## [ ] C-15 – Website settings are cached by id/name and never invalidated: deleted or overridden settings keep being served (same request, messenger workers, and every later request in worker runtimes)

**Files:** `models/WebsiteSetting.php:59`, `:66-85`, `:95-126`, `:273-289`; `lib/Config.php:157-165`, `:173-262`, `:270-273`
(related: CoreBundle **C-08** – request state on shared services in worker runtimes)

- `getByName()` stores `name~~~siteId~~~language` (the **requested** site/language) → id in the static
  `$nameIdMappingCache`. `delete()` (`:275-280`) removes only the key built from the setting's **own**
  name/site/language. Example: `getByName('foo', 5, 'de')` resolves to the global neutral row 7 and caches
  `foo~~~5~~~de → 7`; deleting row 7 removes `foo~~~~~~` only.
- `delete()` never unregisters `RuntimeCache` key `website_setting_<id>` (`:68`, `:82`), so `getById(7)` and the stale
  name mapping keep returning the **deleted object** for the rest of the request.
- Creating a more specific override (site/language row) is invisible to any name already resolved: the mapping keeps
  pointing to the old id.
- `$nameIdMappingCache` is a plain static array that nothing ever clears – not `MessengerClearRuntimeCacheListener`, not
  `LongRunningHelper` (`RuntimeCache::clear()` only). In messenger consumers: once `RuntimeCache` is cleared, the stale id
  is reloaded from DB; if that row was deleted, `getById()` returns `null` and `getByName()` returns `null` even though
  another matching row (e.g. the neutral fallback) exists.
- `lib/Config.php`: `getWebsiteConfig()` stores the resolved config in `RuntimeCache` under a key built from
  **language only** (`getWebsiteConfigRuntimeCacheKey()`, `:157-165`), not the site, and `clearDependentCache()`
  (`WebsiteSetting.php:268-271`) clears only the persistent cache tag, not this runtime entry.
- `RuntimeCache` is a container service without `kernel.reset` / `ResetInterface`, so in FrankenPHP / RoadRunner /
  Swoole worker mode it survives across HTTP requests (no reset found in the code; not verified at runtime). Then:
  - the first site rendered by a worker fixes `pimcore_website_config()` for every later request in that worker,
    **including requests for other sites** (site A's settings served on site B);
  - admin edits and deletes never become visible in that worker until restart.
- Website settings are commonly used for feature switches, contact addresses, tracking IDs and legal texts, so stale or
  cross-site values are a correctness and compliance problem, not just cosmetic.

**Fix direction:**
- Replace the static mapping with a request-scoped service that implements `ResetInterface` (tag `kernel.reset`), or drop
  it and rely on the persistent cache.
- In `save()` / `delete()`: clear `website_setting_<id>`, flush the whole name mapping (mapping keys are by requested
  site/language, so per-key removal cannot be correct), and clear the `getWebsiteConfig` runtime entries.
- Include the site id in `getWebsiteConfigRuntimeCacheKey()`.
- Reset `RuntimeCache` per main request in worker runtimes (together with CoreBundle C-08).

---

# Scope 3 – Dependency

> Notes
> - `models/Dependency.php` and `ElementDependenciesHandler.php` are `strict_types`; `models/Dependency/Dao.php` is not.
>   No hard Symfony 7.4 / PHP 8.4 incompatibility was found (no implicit nullable parameters, no removed APIs; DBAL is
>   `^3.8`). DBAL 4 / modernisation items are in TODO-3 (I-41–I-50).
> - Dependency resolving is **synchronous** by default (`ElementDependenciesMessage` routing is commented out in
>   `bundles/CoreBundle/config/pimcore/default.yaml:38-51`). The handler runs inside `Asset/Document/DataObject::save()`,
>   **after** the element transaction is committed and **before** `POST_ADD` / `POST_UPDATE` (e.g. `Asset.php:611`).
> - The admin controllers that call `getFilterRequiresByPath()` / `getFilterRequiredByPath()` / `getRequiredByWithPath()`
>   live in `pimcore/admin-ui-classic-bundle` (not in this repo, `vendor/` not installed). How they sanitise the filter
>   value was **not verified**.

---

## [ ] C-16 – Dependency filter concatenates the raw search value into SQL (SQL injection; any `'` breaks the query)

**Files:** `models/Dependency/Dao.php:94`, `:99`, `:104` (`getFilterRequiresByPath()`), `:153`, `:158`, `:163`
(`getFilterRequiredByPath()`); public wrappers `models/Dependency.php:105-117`

```php
"... AND LOWER(CONCAT(o.path, o.key)) RLIKE '" . $value . "'"
```

- `$value` is put between single quotes with **no** escaping or binding, three times per query. Source type, id and
  `ORDER BY` are whitelisted/cast; the search value is not. `Dependency` is `@internal`, but it is returned from the
  public `ElementInterface::getDependencies()`, and the filter value comes straight from the admin "dependencies" search
  field.
- Unless every caller escapes the value (not verifiable here, see notes), an authenticated user can inject SQL
  (`x' UNION SELECT … -- `) and read any table (users, password hashes, API keys). This bypasses the per-element
  `isAllowed('list')` check that `Element\Service::getFilterRequiresForFrontend()` applies later.
- Even with honest input: a search for `o'brien` or `kid's` throws a DBAL syntax error (500 in the admin).
- The value is also passed through `sprintf()` (B-95) and interpreted as a regex (B-94).

**Fix direction:** bind the value as a parameter (`… RLIKE :value` / `LIKE :value`, three times or once in a CTE), and
decide literal vs. regex matching (B-94). Build the three near-identical queries in one place (I-43). Add a test with
`'` and `%` in the value (I-50).

---

## [ ] C-17 – `Dependency\Dao::save()` is not atomic: a failure mid-way silently drops dependency rows ("not used" → editor deletes a referenced element)

**Files:** `models/Dependency/Dao.php:222-283`; caller `lib/Messenger/Handler/ElementDependenciesHandler.php:73`;
`models/Asset.php:611`, `models/Document.php:386`, `models/DataObject/AbstractObject.php:609`

- `save()` runs a `DELETE … WHERE id IN (…)` (`:265-268`) and then one `INSERT` per new target (`:270-282`) without a
  transaction. The caller has already committed the element transaction (see notes), so nothing wraps it.
- Any error after the `DELETE` (deadlock on the `combi` unique index with a concurrent save, lost connection,
  `max_allowed_packet`, a `TypeError` for a bad target, B-99) leaves the source with **fewer** rows than it really
  requires. Only `UniqueConstraintViolationException` is caught (and ignored); everything else propagates.
- The `dependencies` table drives the admin "Required by" tab, `isRequired()` / `getRequiredByTotalCount()` and the
  "element is still used" warnings on delete. Missing rows make a referenced asset/document/object look unused. Editors
  delete it and break live pages and relations. Nothing repairs the rows until the source element is saved again.
- With the sync default, the exception also escapes from the element's `save()` after commit, see B-106.

**Fix direction:** wrap the delta in a transaction (`$this->db->transactional(...)`), retry on deadlock
(`RetryableException`), and insert in bulk (I-45). Optionally add a maintenance command that rebuilds dependencies
for all elements, so a damaged graph can be repaired.

---

# Scope 4 – Translation

> Notes
> - `models/Translation.php`, `Translation/Listing.php`, `TranslationEntriesDumper.php` and `Translator.php` are
>   `strict_types`; `models/Translation/Dao.php` and `Translation/Listing/Dao.php` are not. Even without strict types,
>   `null` to a non-nullable parameter throws a `TypeError`, and `Translation::getByKey()` catches only `Exception`.
> - No hard Symfony 7.4 / PHP 8.4 incompatibility was found (no implicit nullable parameters, no removed APIs; the
>   `fgetcsv()` `$escape` argument is passed explicitly, so the PHP 8.4 deprecation does not trigger). DBAL 4 readiness and
>   modernisation are in TODO-3 (I-51–I-63).
> - `Translation::getByKey()` keeps the loaded object in `RuntimeCache`. The **same instance** is returned to every
>   later caller in the request and is the one put into `TranslationEntriesDumper`'s save queue, which is written at
>   `kernel.terminate` / `console.terminate` (`bundles/CoreBundle/src/EventListener/DumpTranslationEntriesListener.php`).
> - The admin translation controllers (grid, import, export) live in `pimcore/admin-ui-classic-bundle` (not in this repo,
>   `vendor/` not installed). How they validate the domain or check permissions was **not verified**.

---

## [ ] C-18 – `getByKey(…, create: true, returnIdIfEmpty: true)` saves the key itself as the translation text in every language

**Files:** `models/Translation.php:262-294`; `lib/Translation/TranslationEntriesDumper.php:29-40`; caller
`models/DataObject/QuantityValue/Service.php:77-82`

- When the key does not exist and `$create` is set, `getByKey()` fills every language with `''` and puts the object into
  the dumper queue (`:273-280`). Then the `$returnIdIfEmpty` block (`:283-291`) replaces every empty language with
  `$idOriginal` **on the same object**. At `kernel.terminate` the dumper saves that object, so the key is written as
  the text of every language.
- This is reached in core: `QuantityValue\Service::generateDefinitionJson()` calls
  `getByKeyLocalized($abbreviation, 'admin', true, true)` for every unit. Each unit abbreviation and long name that has
  no admin translation yet is stored as "translated" with the key in all admin languages (~20 rows per key).
- Effects: these keys never show up as untranslated in the admin, the `debug_admin_translations` `+key+` marker is
  lost, and an editor who later translates one language does not see that the others are only the key. Because the
  object is also in `RuntimeCache`, every later `getByKey($id, 'admin')` in the request returns the same corrupted
  object (B-107).
- Same for any custom code that calls `getByKey()` / `getByKeyLocalized()` with both flags.

**Fix direction:** never mutate the queued model for presentation. Queue a separate object (or clone before the
`$returnIdIfEmpty` step), or better: let `getByKeyLocalized()` return `$translation?->getTranslation($lang) ?: $id`
and drop `$returnIdIfEmpty` from `getByKey()` (I-53). Add a cleanup migration for rows where `text = key` was written by
this path (only rows whose `creationDate = modificationDate` and `userModification = 0`, to keep real edits).

---

## [ ] C-19 – One `NULL` in `text` breaks every `|trans` for that domain and locale (frontend 500); `Dao::save()` can write the `NULL`

**Files:** `lib/Translation/Translator.php:158-161`; `models/Translation/Dao.php:64-66`, `:100-122`;
`models/Translation/Listing/Dao.php:79`; schema `install.sql:437` (`text` is `DEFAULT NULL`),
`Translation/Dao.php:203`

- `Translator::lazyInitialize()` builds the catalogue from `loadRaw()` and calls
  `Tool\Text::removeLineBreaks($translation['text'])`. That parameter is `string`, and `Translator.php` is strict. One
  row with `text IS NULL` throws a `TypeError`. The catalogue is not cached, so **every request** that translates
  anything in that domain/locale fails until the row is fixed. In practice that is the whole frontend of one language.
- `Translation\Dao::getByKey()` (`:66`) and `Listing\Dao::getAllTranslations()` (`:79`) call
  `addTranslation(string $language, string $text)` with the raw column. The `TypeError` is an `Error`, so the
  `catch (Exception)` in `Translation::getByKey()` does not catch it. The admin grid and `Listing::load()` crash for that key.
- The schema allows `NULL`, and `Listing\Dao::cleanup()` (`:149`) checks `text IS NULL`, so `NULL`s are expected.
  Pimcore itself can write one: `setTranslations(array)` does not type its values. `Dao::save()` then compares
  `$text != strip_tags($text)` (`null == ''`, no sanitising) and upserts `text = NULL`. Old data, migrations, direct
  SQL and integrations can also contain `NULL`s.

**Fix direction:** treat `NULL` as `''` when reading (`(string) $row['text']` / `?? ''`) in all three places. Validate
`setTranslations()` / `addTranslation()` values as `string` (I-51). Make the column `NOT NULL` after a data migration
(`UPDATE … SET text = '' WHERE text IS NULL`, I-62).

---

## [ ] C-20 – View-only translation users can save every language: `Dao::save()` treats "no edit languages" as "no restriction"

**Files:** `models/Translation/Dao.php:92-105`; `models/User.php:601-620`

```php
if ($user = User::getById($this->model->getUserModification())) {
    $editableLanguages = $user->getAllowedLanguagesForEditingWebsiteTranslations();
}
…
if (count($editableLanguages) && !in_array($language, $editableLanguages)) { continue; }
```

- `getAllowedLanguagesForEditingWebsiteTranslations()` says (docblock `User.php:602-603`): "If only edit languages are
  empty (but view languages not) **empty array is returned**". In that case the user may edit **no** language.
- The DAO uses `count($editableLanguages)` as "restriction active". `[]` turns the check off, so a user who may only
  **view** some languages can write **all** languages, including languages they cannot even view.
- The model layer is the last line of defence for every save path (admin grid, CSV import, REST/GraphQL bundles,
  custom code). Whether the admin controllers check again before calling `save()` was not verified (see notes).
- `in_array()` is loose, and the check runs only for non-admin domains (by design). Custom domains use the
  website-translation permissions, which is not documented.

**Fix direction:** separate "unrestricted" (`null`) from "nothing allowed" (`[]`). Change the user API to return
`null` for "all languages", or check `$user->isAdmin()` / the view list explicitly in the DAO. Reject disallowed
languages with an exception instead of skipping them silently (B-111). Move the check from the DAO into the model or
a voter (I-55).

---

## [ ] C-21 – The translation domain is never validated in the model and goes into SQL identifiers and DDL (`translations_<domain>`)

**Files:** `models/Translation.php:179-182`, `:246-263`, `:386-444`; `models/Translation/Dao.php:40-43`, `:53-56`,
`:132`, `:191-210`; `models/Translation/Listing/Dao.php:33-36`

- `Translation::setDomain()` accepts any string. `Dao::getDatabaseTableName()` concatenates it into the table name, and
  every query uses that name **unquoted** (`SELECT * FROM translations_<domain> WHERE …`, `:53`, `:56`, `:141`). Only
  `Listing::setDomain()` and the Translator check `isAValidDomain()` first. `Translation::getByKey()`,
  `getByKeyLocalized()`, `importTranslationsFromFile()`, `save()` and `delete()` do not.
- If a caller passes a request value as the domain (the admin import/grid controllers pass a `domain` request parameter;
  not verified, see notes), a value like `messages WHERE 1=0 UNION SELECT … -- ` injects SQL into the `SELECT`. The
  `catch (Exception)` in `getByKey()` hides the error, so blind injection works without visible errors.
- With `$create = true`, a `TableNotFoundException` for an **unregistered** domain queues the entry. `save()` then runs
  `CREATE TABLE IF NOT EXISTS \`translations_<domain>\`` (`:199`). Any string creates a new table, and a backtick in the
  domain breaks out of the quoted identifier. This also bypasses `pimcore.translations.domains`: the table exists,
  but `isAValidDomain()` still says `false` and the entries cannot be listed.
- `isAValidDomain()` (`:183`) uses `sprintf('… translations_%s …')` too. It is only safe because it checks the config
  list first.

**Fix direction:** validate the domain in one place (`setDomain()` and every static entry point): it must be in
`getRegisteredDomains()` and match `/^[a-z0-9_]+$/i` (also validate the config node in
`CoreBundle/DependencyInjection/Configuration.php:78-81`). Quote the table name with `quoteIdentifier()` everywhere.
Never create tables as a side effect of a lookup; create them for the configured domains at install/migration time
(I-62).

---

## [ ] C-22 – Missing-translation auto-create overwrites existing texts: a stale snapshot of all languages is saved at `kernel.terminate`, and after a swallowed DB error a blank one

**Files:** `lib/Translation/Translator.php:240-267`; `models/Translation.php:246-297`;
`models/Translation/Dao.php:99-123`; `lib/Translation/TranslationEntriesDumper.php:34-40`

- When a key is missing from the catalogue of the current locale, `checkForEmptyTranslation()` loads the key with
  `Translation::getByKey($id, $domain)` and queues it for saving. That is the normal case when the key exists in the DB
  but not for this locale (a language was added in the system settings, or the key was imported for only some
  languages).
- **Stale snapshot:** the dumper saves the object at `kernel.terminate`. `Dao::save()` upserts **every language** in
  the object, not just the new empty one. An editor's change to another language of that key between the load and
  `kernel.terminate` (or in a long CLI / worker run, much longer) is overwritten with the old text. Admin users get no
  warning.
- **Blank overwrite:** `getByKey()` returns `null` for **any** `Exception` (`:262-267`): lost connection ("MySQL server
  has gone away" in long-running workers), lock wait timeout, a missing column. The Translator then treats the key as
  new and queues `new Translation()` with `''` for **all** website languages (`:255-264`). If the connection works again
  at terminate (DBAL reconnects, or the error was transient), the upsert **blanks the existing texts in every
  language** of that key. Nothing is logged.
- The same applies to `importTranslationsFromFile()` (`getByKey($textKey, $domain, true)`, `:444`) after a transient
  error: the import continues with an empty object and writes only the CSV columns, but the dumper also writes the
  blank object for every language.

**Fix direction:** when auto-creating, **insert only the missing language rows** (`INSERT IGNORE` /
`INSERT … ON DUPLICATE KEY UPDATE key = key`), never upsert the full snapshot. Let `getByKey()` swallow only "not
found" (B-108) and rethrow everything else. Save only changed languages in `Dao::save()` (B-110, I-55).

---

# Scope 5 – Site

> Notes
> - `models/Site.php` and `Site/Listing.php` are `strict_types`; `Site/Dao.php` and `Site/Listing/Dao.php` are not.
>   Hydration goes through the non-strict `AbstractModel::setValue()`, so DB strings/ints are coerced into the typed
>   setters. No hard Symfony 7.4 / PHP 8.4 incompatibility was found (no implicit nullable parameters, no removed APIs,
>   DBAL is `^3.8`, `ext-intl` for `idn_to_ascii()` is required in `composer.json`). DBAL 4 readiness and modernisation
>   are in TODO-3 (I-64–I-72).
> - `Site::$currentSite` never being reset between HTTP requests in worker runtimes is already **CoreBundle C-08**; it
>   is not repeated here. The CLI / Messenger side is B-126.
> - The admin site editor (`DocumentController` "use as site" / "remove site") lives in
>   `pimcore/admin-ui-classic-bundle` (not in this repo, `vendor/` not installed). Whether it clears caches beyond
>   `Site::save()` / `delete()` was **not verified**.
> - No code in this repo listens to `SiteEvents::*`.

---

## [ ] C-23 – The persistent site cache stores a serialized copy of the root page and is only cleared by the `site` tag: renaming or moving a site root makes the whole site answer 404

**Files:** `models/Site.php:68-92`, `:109-134`, `:256-262`, `:285-292`, `:348-356`; `lib/Tool/Frontend.php:70-95`;
`bundles/CoreBundle/src/EventListener/Frontend/RoutingListener.php:111-124`; `models/Document.php:516-526`

```php
Cache::save($site, $cacheKey, ['system', 'site'], null, 999);   // $site->rootDocument is a full Document\Page
```

- Hydration calls `setRootId()` (`:256-262`), which loads `Document\Page::getById()` into `$rootDocument`. `Site` has
  no `__sleep()` / `__serialize()` of its own, so `AbstractModel::__sleep()` keeps `rootDocument`, and the cache entries
  `site_id_<id>` / `site_domain_<md5(host)>` contain a **frozen copy of the root page** (path, key, properties, …).
- `CoreCacheHandler::prepareCacheTags()` adds element tags only when the cached value itself is an element. The entry
  is tagged `system` + `site` only, so `Document::clearDependentCache()` (`document_<id>`, `document_properties`,
  `output`) never clears it.
- `getRootPath()` (`:285-292`) is derived from that copy. After the root page is renamed (`/en` → `/english`) or moved,
  `RoutingListener::resolveSite()` keeps prefixing the **old** root path to every request path on the site's domains,
  so **every page of the site returns 404**. `Document::getPath()` / `getFullPath()` (`Document.php:696-764`, `:812-822`)
  strip the old prefix, so links on other sites are wrong too. It only recovers after a full cache clear (or any other
  `site` / `system` tag clear).
- The same applies to every other root-page change that the site logic reads (published flag, properties, type
  conversion – B-134).
- `Frontend::getSiteMapping()` caches `root path → site id` under `sites_path_mapping` with tags `system`, `resource`.
  `Site::clearDependentCache()` clears only `site`, so **creating, deleting or re-rooting a site** leaves the mapping
  stale: `getSiteForDocument()` / `getSiteIdForDocument()` return no site or the wrong one (cross-site links, static
  page paths, preview host).
- The cached object is also big: every site lookup unserializes a full page document.

**Fix direction:**
- Never serialize the root document: add `__serialize()` / `__sleep()` that drops `rootDocument` (keep `rootId`), and
  load it lazily in `getRootDocument()` through `Document\Page::getById()`, which has its own correctly tagged cache.
  Compute `getRootPath()` from that lazy document.
- Tag the site entries with `document_<rootId>` as well (or clear `site` from a document save/move listener when the
  document is a site root), and tag `sites_path_mapping` with `site` so `clearDependentCache()` covers it.
- Long term: one cached "site registry" (I-67) instead of per-key entries.

---

## [ ] C-24 – Deleting a site's root page silently deletes the site through the FK cascade: the site definition is lost for good, the domain keeps routing to the deleted path

**Files:** `bundles/InstallBundle/dump/install.sql:381-382`; `models/Site.php:399-408`; `models/Site/Dao.php:149-159`;
`models/Document.php:648`; `models/Element/Recyclebin/**`

```sql
CONSTRAINT `fk_sites_documents` FOREIGN KEY (`rootId`) REFERENCES `documents` (`id`) ON DELETE CASCADE
```

- When the root page (or any ancestor folder) is deleted, MySQL removes the `sites` row. `Document::delete()` and
  `Document\Dao` know nothing about sites, so `Site::delete()` never runs:
  - no `SiteEvents::PRE_DELETE` / `POST_DELETE`;
  - `UrlSlug::handleSiteDeleted()` is skipped → `object_url_slugs` rows for that site id stay;
  - `Site::clearDependentCache()` is skipped → `site_domain_<md5>` / `site_id_<id>` keep returning the deleted site.
    `RoutingListener` still sets it as the current site and prefixes the deleted root path: the domain answers **404 for
    every URL** instead of falling back to the main site, until a full cache clear (C-23).
- **Data loss:** the recycle bin stores the document tree only. Restoring the page brings back the documents but **not
  the site** (domains, main domain, error pages, redirect flag). An accidental delete of a site root – or of a folder
  that contains one – loses the site configuration permanently; editors usually notice only when the domain is down.
- Admin UI behaviour (warning before deleting a site root) was not verified (see notes).

**Fix direction:**
- Change the FK to `ON DELETE RESTRICT` (or `SET NULL` with a disabled-site state) in a migration, and in
  `Document::delete()` (or a `DocumentEvents::PRE_DELETE` listener in the model layer) refuse to delete a site root
  unless the site is removed first, or call `Site::delete()` explicitly inside the same transaction.
- Store the site definition in the recycle bin item of its root page and restore it together with the page.
- Clear the site caches and run the slug cleanup on every deletion path (B-135).
