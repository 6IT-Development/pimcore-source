# models – TODO 1: Critical errors

Scope: **(1)** `models/Asset.php` and `models/Asset/**` (Dao, Listing, Service, Folder, Image, Image/Thumbnail/**, Thumbnail/**,
Video, Video/**, Document, Document/**, MetaData/**, WebDAV/**, Enum, Archive/Audio/Text/Unknown), plus the helpers they
directly call where a defect originates there (`lib/Video/Adapter/Ffmpeg.php`, `lib/Config/LocationAwareConfigRepository.php`,
`lib/Messenger/Handler/AssetUpdateTasksHandler.php`)
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-2-BUGS.md](TODO-2-BUGS.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md),
[CoreBundle TODO-1](../bundles/CoreBundle/TODO-1-CRITICAL.md) (C-13, C-14, C-15 already cover WebDAV permissions/CSRF and
the public thumbnail route; they are not repeated here)

These items cause **data loss or corruption** (tree paths, binaries, versions), **source files deleted outside Pimcore**,
**open DB transactions in workers**, **crashes in core asset flows** (create, folder move, video/PDF processing, frontend
thumbnail rendering) and **path traversal / false security status**.
IDs are stable – refer to them as `C-xx` in later sessions. Line numbers are from commit `1b0711d927`.

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
