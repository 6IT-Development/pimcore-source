# models – TODO 2: Bugs

Scope: **(1)** `models/Asset.php` and `models/Asset/**` (Dao, Listing, Service, Folder, Image, Image/Thumbnail/**, Thumbnail/**,
Video, Video/**, Document, Document/**, MetaData/**, WebDAV/**, Enum, Archive/Audio/Text/Unknown), plus the helpers they
directly call where a defect originates there (`lib/Video/Adapter/Ffmpeg.php`, `lib/Messenger/Handler/AssetUpdateTasksHandler.php`)
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md),
[CoreBundle TODO-2](../bundles/CoreBundle/TODO-2-BUGS.md) (B-58 lock backends, B-65/B-69 thumbnail route – not repeated)

Functional defects: wrong results, crashes on reachable input, swallowed errors, misleading output.
IDs are stable – refer to them as `B-xx` in later sessions. Line numbers are from commit `1b0711d927`.
Grouped by area, roughly ordered by impact inside each group: **Asset.php** B-01–B-19, **Dao / Listing / Service /
Folder / WebDAV** B-20–B-36, **Image thumbnails** B-37–B-60, **Video / Document / MetaData** B-61–B-82.

---

## Asset.php

## [ ] B-01 – Update-task lock is released immediately: every save enqueues another `AssetUpdateTasksMessage`

**File:** `models/Asset.php:1828-1840`; `lib/Messenger/Handler/AssetUpdateTasksHandler.php:63`

- `createLock()` defaults to `autoRelease: true`; `Lock::__destruct()` releases it when `$lock` goes out of scope at
  the end of `triggerUpdateTask()`. The lock deduplicates nothing; bulk imports enqueue one message per save.
- The handler's `createLock(...)->release()` uses a new key token, so it is a no-op on token-checked stores (see also
  CoreBundle B-58: two lock backends).

**Fix direction:** `createLock($id, $ttl, autoRelease: false)`; pass the serialized `Key` in the message, or let the TTL
expire instead of releasing from a different key.

## [ ] B-02 – `relocateThumbnails()` fallback works on the asset path instead of the thumbnail directory

**File:** `models/Asset.php:1788`

- For non-folder assets thumbnails live in `dirname($oldPath) . '/' . $id` (`:1763-1764`), but the fallback calls
  `updateChildPaths($storage, $oldPath, null, true)` with the asset file path. Nothing is found; thumbnails are orphaned
  and never cleaned up.

**Fix direction:** pass `$oldThumbnailsPath` / `$newThumbnailsPath`.

## [ ] B-03 – `updateChildPaths()` never deletes the old directory when it has subdirectories

**File:** `models/Asset.php:1710`, `:1723-1726`, `:1745-1746`

- `$totalChildren` counts `DirectoryAttributes` too, `$movedCount` only files → any subfolder makes them differ;
  `deleteDirectory($oldPath)` is skipped and empty trees remain, with a misleading log message. The rollback loop swaps
  `$src`/`$dest` naming relative to `:1719` (correct, but confusing). Fix together with C-02.

**Fix direction:** count only `FileAttributes`.

## [ ] B-04 – `PRE_GET_METADATA` listener result is written into the model and then persisted

**File:** `models/Asset.php:1489-1492`; `models/Asset/Dao.php:138`

- `$this->metadata = $preEvent->getArgument('metadata');` overwrites the model state. `Dao::update()` reads metadata
  through `getMetadata(null, null, false, true)`, which fires the event, so display-only metadata added by a listener is
  saved to `assets_metadata` and compounds on repeated calls.

**Fix direction:** apply the event result only to the returned value; let the Dao read raw `$this->metadata`.

## [ ] B-05 – `getMetadataByName()` returns the first match, not the localized one

**File:** `models/Asset.php:1563-1571`

- The loop breaks on the first entry whose language matches **or is empty**. With `[title(''), title('en')]`,
  `getMetadata('title', 'en')` returns the language-neutral value.

**Fix direction:** exact language match first, empty-language entry as fallback.

## [ ] B-06 – Metadata names containing `~` cannot be read back

**File:** `models/Asset.php:1430`, `:1471` vs `:1564`

- `addMetadata()` / `removeMetadata()` map `~` to `---`; `getMetadataByName()` compares the raw name, so
  `addMetadata('a~b', …)` → `getMetadata('a~b')` is `null`.

**Fix direction:** normalise the name in one helper used by all three methods.

## [ ] B-07 – `__sleep()` condition is inverted: every cached asset re-queries `customSettings`

**File:** `models/Asset.php:193-201`

- `parent::__sleep()` returns the vars that **will** be serialized (`AbstractElement.php:649`), not the blocked ones.
  `customSettingsNeedRefresh = true` is therefore set exactly when `customSettings` is cached, so every cache hit runs an
  extra `SELECT customSettings`. The blocked case is already handled in `__wakeup()` (`:1628`).

**Fix direction:** remove the override (or invert to `!in_array(...)`).

## [ ] B-08 – `CUSTOM_SETTING_PROCESSING_FAILED` is removed only in memory, after the DB write

**File:** `models/Asset.php:613-617`

- `removeCustomSetting()` runs after `Dao::update()` and `commit()`; the DB keeps the flag. Other processes
  (`Video::getDuration()` `Video.php:197/222`, `AddToUpdateTaskQueueCommand`) see a stale failure flag.

**Fix direction:** remove the flag before `update()`.

## [ ] B-09 – `getChecksum()` writes the whole in-memory `customSettings` to DB on a read

**File:** `models/Asset.php:1163-1189`

- `generateChecksum()` calls `Dao::updateCustomSettings()`, persisting unsaved caller changes; inside `update()` it also
  double-writes `customSettings`.

**Fix direction:** separate computing from persisting; inside `update()` only set the value in memory.

## [ ] B-10 – `delete()`: file-system errors after `commit()` roll back nothing and fire `POST_DELETE_FAILURE`

**File:** `models/Asset.php:1024-1053`

- Physical delete and `clearThumbnails()` run after `commit()` inside the same `try`. A `FilesystemException` calls
  `rollBack()` with no active transaction, dispatches the failure event and rethrows although the DB rows are gone.

**Fix direction:** move post-commit storage cleanup out of the transactional `try`; log its errors.

## [ ] B-11 – `Asset::create()` leaks its temp file when `save()` throws

**File:** `models/Asset.php:405-411`

- `unlink($tmpFile)` is skipped on exception; files accumulate in `PIMCORE_SYSTEM_TEMP_DIRECTORY`.

**Fix direction:** `try/finally` (together with C-03 / C-04).

## [ ] B-12 – `Asset::create()` passes `null`/`false` into typed functions

**File:** `models/Asset.php:358`, `:370`, `:375-377`, `:382`

- `guessMimeType()` returns `?string`; `null` reaches `getTypeFromMimeMapping(string …)` → `TypeError`.
- Unreadable `sourcePath`: `fopen()` → `false`; `setStream(false)` silently ignores it and the asset is saved without a
  binary.
- `fopen($tmpFile)` failing at `:358` → `stream_copy_to_stream(…, false)` `TypeError`.

**Fix direction:** validate each result and throw a meaningful exception.

## [ ] B-13 – Legacy-file deletion passes `null` from a failed DB lookup to Flysystem

**File:** `models/Asset.php:757-759`; `models/Asset/Dao.php:299-310`

- `getCurrentFullPath()` returns `null` on DB error (and `''` for a missing row, B-33). `fileExists(null)` throws
  `TypeError` inside the transaction (C-05).

**Fix direction:** `if ($dbPath && $dbPath !== $path && …)`.

## [ ] B-14 – `ValidationException` message embeds the raw upload filename in HTML

**File:** `models/Asset.php:435-437`

- `{$data['filename']}` is inserted into `<em>…</em>`. If the admin UI renders the message as HTML (it renders the other
  tags), a filename like `<img src=x onerror=…>.jpg` is reflected XSS. (Rendering side not verified – admin UI is
  outside this repo.)

**Fix direction:** `htmlspecialchars($data['filename'])`.

## [ ] B-15 – Storage writes / moves are not compensated when the transaction fails

**File:** `models/Asset.php:533-567`, TODO comment at `:634`

- Binary write (`update()`), thumbnail relocation and `storage->move()` run before `saveVersion()` / `commit()`. If a
  later step fails, the DB rolls back but storage keeps the new location/content; the DB path points to a missing file.

**Fix direction:** record storage operations and undo them in the catch, or perform moves after commit with
compensating handling.

## [ ] B-16 – `getStream()` silently substitutes an empty stream for a missing binary

**File:** `models/Asset.php:1142-1158`

- `catch (Exception) { $this->stream = tmpfile(); }` hides a missing/unreadable file. With `setDataChanged(true)` and no
  new stream, `update()` rewrites the asset as a 0-byte file. When rewind fails (`:1147`) the old handle is dropped
  without `fclose()`.

**Fix direction:** log (or rethrow outside read-only contexts) and close the old handle.

## [ ] B-17 – Version deletion dispatched inside the delete transaction

**File:** `models/Asset.php:1007-1009`

- `VersionDeleteMessage` is dispatched before `commit()`. With the default `doctrine://` transport it rolls back with the
  transaction; with AMQP/Redis/sync transports versions are deleted even when the asset delete rolls back. (Transport
  dependency unverified per project.)

**Fix direction:** dispatch after `commit()`.

## [ ] B-18 – `setParent()` keeps a stale `parentId` for `null` / non-asset parents

**File:** `models/Asset.php:1602-1611`

- Accepts any `ElementInterface`; passing `null` or a document leaves the old `parentId` while `$this->parent` changes,
  so `save()` uses the stale id.

**Fix direction:** narrow to `?Asset` and set `parentId` consistently.

## [ ] B-19 – `delete()` traversal guard is ineffective

**File:** `models/Asset.php:1029-1030`

- `!strpos($fullPath, '/../')` treats a match at position 0 as "not found". Keys are validated elsewhere, so impact is
  low, but the guard does not do what it claims.

**Fix direction:** `!str_contains(...)`, or remove the check and rely on key validation + Flysystem path normalisation.

---

## Dao / Listing / Service / Folder / WebDAV

## [ ] B-20 – WebDAV `MOVE` to another folder ignores the new filename

**File:** `models/Asset/WebDAV/Tree.php:73-79`

- The cross-directory branch only calls `setPath()` / `setParentId()`, never `setFilename(basename($destinationPath))`.
  `MOVE /a/x.jpg` → `Destination: /b/y.jpg` yields `/b/x.jpg`; a duplicate is swallowed (C-08). The override also skips
  Sabre's `markDirty()`, so the node cache is stale for the rest of the request.

**Fix direction:** set the filename in both branches; call `markDirty()` on source and destination directories.

## [ ] B-21 – `Service::getUniqueKey()` throws `TypeError` for names without an extension and mangles repeated stems

**File:** `models/Asset/Service.php:331-333` (`strict_types`)

```php
$keypart = substr($key, 0, strrpos($key, '.'));   // strrpos() === false → TypeError
$extension = str_replace($keypart, '', $key);     // 'data.data' → stem 'data', ext '.'
```

- Any name collision of `README`, `LICENSE`, … crashes; `data.data` becomes `data_1.`. Public API via
  `Element\Service::getUniqueKey()`.

**Fix direction:** `pathinfo()` with `PATHINFO_FILENAME` / `PATHINFO_EXTENSION`; append `.ext` only when present.

## [ ] B-22 – Public thumbnail route: type/asset mismatch → uncaught `TypeError` (anonymous 500)

**File:** `models/Asset/Service.php:373-374`, `:420`, `:431`, `:468`

- `$config['type']` comes from the URL and is never checked against the asset class.
  `/…/video-thumb__<imageId>__<videoThumbName>/x.jpg` makes `Image::getThumbnail(Video\Thumbnail\Config)` throw; a
  Document with type `video` fails at `:431`. The controller's `catch (Exception)` (CoreBundle B-65) does not catch it.

**Fix direction:** return `null` when `type === 'video'` and the asset is not `Asset\Video` (and vice versa).

## [ ] B-23 – Undefined `status_cache` key for video thumbnails

**File:** `models/Asset/Service.php:390`

- `…['assets'][$config['type']]['thumbnails']['status_cache']` exists only for images (`Configuration.php:510`). Every
  anonymous request for an unknown video thumbnail name raises "Undefined array key" (`ErrorException` in debug).

**Fix direction:** `?? false`, or only for `type === 'image'`.

## [ ] B-24 – Unknown thumbnail name → DB `DELETE` and status-cache wipe on every anonymous request

**File:** `models/Asset/Service.php:390-395`; `models/Asset/Dao.php:588`

- With no TmpStore item, each request runs `DELETE FROM assets_image_thumbnail_cache …` and drops the whole static
  status cache for that asset. Unauthenticated DB writes per request (separate cost from CoreBundle C-15).

**Fix direction:** delete only when a cache row exists (`getCachedThumbnail()`), or only for `auto_*` names.

## [ ] B-25 – Video branch of `getStreamedResponseFromImageThumbnail()`: undefined format key and metadata on missing file

**File:** `models/Asset/Service.php:511-517`

- `$thumbnail['formats'][$ext]` is undefined for a non-generated extension or while converting → `urldecode(null)`
  `TypeError`. `mimeType()` / `fileSize()` are called even when `fileExists()` was false → `UnableToRetrieveMetadata`.
- (unverified) `Video::enrichThumbnailPath()` adds the frontend prefix, so with a configured prefix `fileExists()` is
  always false.

**Fix direction:** return `null` on missing key/file; read metadata only for existing files; strip the prefix as at `:569`.

## [ ] B-26 – Thumbnail responses: `Expires` in local time, cache headers differ between paths, streams not closed

**File:** `models/Asset/Service.php:527`, `:532`, `:535`, `:578-585`

- `date('D, d M Y H:i:s T')` → e.g. `CEST`; RFC 9110 requires GMT.
- `NO_AUTO_CACHE_CONTROL_HEADER` is set only on the "generated" path; for an existing thumbnail Symfony's
  `AbstractSessionListener` rewrites the response to `private, no-cache` whenever a session is active.
- The `StreamedResponse` closures never `fclose()` the stream; `:504-508` leaves the original stream open; `:540`
  returns `$thumbnailStream` where `null` is meant.

**Fix direction:** one helper: `setPublic()->setMaxAge()->setExpires(new \DateTimeImmutable("+$lifetime seconds"))`,
header on both paths, `fclose` in the closures.

## [ ] B-27 – `Asset\Dao::$thumbnailStatusCache` is a never-reset static (unbounded growth, stale in workers)

**File:** `models/Asset/Dao.php:43`, `:549-570`; only reset in `Image/Thumbnail/Config/Dao.php:157`

- No `ResetInterface` / `kernel.reset`. In messenger consumers / FrankenPHP / RoadRunner it grows by every asset touched
  and goes stale: after `pimcore:thumbnails:clear` or a config edit in another process, `Processor.php:181-194` returns
  a path that no longer exists – image broken and never regenerated.

**Fix direction:** move it into a service implementing `ResetInterface`; reset in the long-running helper.

## [ ] B-28 – Listing permission SQL uses unanchored prefix matching (`/foo` matches `/foobar`)

**Files:** `models/Asset/Listing.php:71-85`, `models/Asset/Dao.php:337-351`, `:393-407`; root `models/Element/Dao.php:88`

- `LOCATE(CONCAT(path,filename), cpath)=1`: a user with a workspace on `/foobar` sees folder `/foo` listed, and
  `hasChildren()` / `getChildAmount()` report its children.
- `InheritingPermission()` uses `LOCATE(cpath, ?)=1` while workspace `cpath` is stored without trailing slash, so a
  workspace on `/foo` grants inherited `list` on children of `/foobar`.
- `isAllowed()` (`Dao.php:467`) is correct, so only listings leak (e.g. via WebDAV `getChildren()` after CoreBundle C-13).

**Fix direction:** compare with `'/'` appended on both sides, or use `cid IN (parentIds)`.

## [ ] B-29 – WebDAV upload writes the body to disk before the permission check and leaks temp files on errors

**Files:** `models/Asset/WebDAV/Folder.php:112-133`; `models/Asset/WebDAV/File.php:110-123`

- `createFile()` writes the whole body to `PIMCORE_SYSTEM_TEMP_DIRECTORY` **before** `isAllowed('create')` (`:120`).
- If `Asset::create()` / `save()` throws, `unlink()` is skipped; the files are not registered for shutdown cleanup. In
  `File::put()` the `fopen()` handle stays open. `file_put_contents()` / `fopen()` results are unchecked.

**Fix direction:** check permission first; `try/finally` with `fclose`/`unlink`, or pass a `php://temp` stream
directly to `setStream()`.

## [ ] B-30 – WebDAV delete log: lost entries, cross-user restore, unrestricted `unserialize()`

**Files:** `models/Asset/WebDAV/Service.php:36`, `:54`; `File.php:80-91`; `Tree.php:59-66`
(CoreBundle C-13 only mentions the inner `Serialize::unserialize`.)

- Read-modify-write of one shared file without lock: concurrent DELETEs lose entries; the editor "delete then move"
  restore then creates a new asset ID and references break.
- The log is global: user B's `MOVE` onto a path user A deleted within 30 s restores **A's** serialized asset.
- Outer `unserialize()` at `:36` has no `allowed_classes` and no error handling.

**Fix direction:** TmpStore / DB keyed by user + path; `allowed_classes` restricted; at minimum `LOCK_EX` / `flock`.

## [ ] B-31 – Folder preview collage caches partial images and fatals on corrupt tiles

**File:** `models/Asset/Folder.php:353-366`, `:375-390`

- One tile without stream/dimensions hits `break` (`:356`, `:362`), aborting the loop; the partial (black) collage is
  stored and served until a descendant changes.
- `imagecreatefromstring()` → `false` for a corrupt thumbnail; `imagecopyresampled($collage, false, …)` → `TypeError`,
  breaking the preview permanently. Tile streams (`:353`) are never closed.

**Fix direction:** `continue` instead of `break`; check `false`; size the canvas by tiles actually drawn.

## [ ] B-32 – Folder preview freshness check does not match the collage contents

**File:** `models/Asset/Folder.php:303` vs `:309-313`

- `SELECT MAX(modificationDate) … ORDER BY filename LIMIT 42` limits the single aggregate row, i.e. MAX over **all**
  descendants, while the collage uses the first 42 by `id`. Any change anywhere in a deep tree regenerates the collage.

**Fix direction:** `SELECT MAX(modificationDate) FROM (SELECT … ORDER BY id LIMIT 42) t`.

## [ ] B-33 – `Dao::getCurrentFullPath()` / `getCustomSettings()` return `''` instead of `null` for a missing row

**File:** `models/Asset/Dao.php:299-310`, `:509-512`

- `fetchOne()` → `false`; without `strict_types` it is coerced to `''` for `?string`. `Asset.php:682` then builds
  `'' . '/'` for a concurrently deleted parent.

**Fix direction:** `return $v === false ? null : $v;` and add `strict_types`.

## [ ] B-34 – `__isBasedOnLatestData()` reads offsets on `false`

**File:** `models/Asset/Dao.php:516-517`

- For a deleted/unsaved asset `fetchAssociative()` is `false` → warning (`ErrorException` in debug).

**Fix direction:** `if (!$data) { return false; }`.

## [ ] B-35 – WebDAV `getContentType()` / `getLastModified()` are non-nullable, model getters are nullable

**File:** `models/Asset/WebDAV/File.php:97-100`, `:158-161`

- `getMimeType(): ?string`, `getModificationDate(): ?int`; returning `null` → `TypeError` that breaks PROPFIND for the
  whole folder (assets created via API without data).

**Fix direction:** return `?string` (Sabre allows null) / fall back to `0`.

## [ ] B-36 – `Dao::unlockPropagate()` builds invalid SQL for unsaved assets

**File:** `models/Asset/Dao.php:433-434`

- With `getId() === null` the SQL ends in `OR id = ` (syntax error); large trees build huge `IN()` lists.

**Fix direction:** guard on null id; single `DELETE tl FROM tree_locks tl JOIN assets a …`.

---

## Image thumbnails (Image, Image/Thumbnail/**, Thumbnail/**)

## [ ] B-37 – Uppercase file extensions break the `source` format (JPEG bytes in `.png`, or full PNG of a photo)

**File:** `models/Asset/Image/Thumbnail/Processor.php:114`, `:119`, `:129`, `:338`; `models/Asset/Service.php:399-405`

- `pathinfo()` without lowercasing (regression from commit `85d6471de3`, which replaced `File::getFileExtension()`).
  `IMG_1234.JPG` misses the mapping and falls back to `png`. Eager path writes optimised `pjpeg` content into `….png`
  (wrong MIME); deferred path writes an unoptimised full PNG to the **same** path (hash ignores format, B-38). First
  writer wins. Same for `.TIF` (the `tifforiginal` bypass never triggers).

**Fix direction:** `strtolower()` the extension; include the resolved format in filename/hash.

## [ ] B-38 – `Config::getHash()` omits output-affecting fields

**File:** `models/Asset/Image/Thumbnail/Config.php:836-846`

- Not hashed: `format`, `forceProcessICCProfiles`, `rasterizeSVG`, `medias`. Different outputs collide on one path;
  changing `forceProcessICCProfiles` keeps serving stale files unless `auto_clear_temp_files` runs.
- Related: `Service.php:444-446` overrides `quality` for non-jpg formats, changing the hash so the file is written to a
  different path than the requested URL – every request for that URL goes through PHP.

**Fix direction:** hash every output-affecting property.

## [ ] B-39 – Runtime-modified named configs are not stored for deferred generation

**File:** `models/Asset/Image/Thumbnail/Processor.php:224`; `models/Asset/Thumbnail/ImageThumbnailTrait.php:423-434`

- `if (!Config::exists($config->getName())) TmpStore::add(...)`: a cloned & modified named config (`getAsFormat('webp')`,
  user `setItems()`) is not stored; the deferred controller rebuilds it from YAML and writes `…jpg` to a different
  path, so the `.webp` URL never resolves.

**Fix direction:** store when the config differs from the persisted definition (compare hashes), not by name.

## [ ] B-40 – `getAsFormat()` returns a clone with a stale MIME type

**File:** `models/Asset/Thumbnail/ImageThumbnailTrait.php:122-129`, `:425-431`

- `reset()` does not clear `$mimetype`: `$t->getMimeType(); $t->getAsFormat('webp')->getMimeType()` → `image/jpeg`.

**Fix direction:** `$this->mimetype = null` in `reset()`.

## [ ] B-41 – `existsOnStorage()` without argument always returns `false`

**File:** `models/Asset/Thumbnail/ImageThumbnailTrait.php:371-373`

- Default is `[]`, so `$pathReference ??= …` never fires.

**Fix direction:** default to `null` (update `ImageThumbnailInterface` too).

## [ ] B-42 – Empty / `'0'` thumbnail name → `TypeError` instead of fallback

**File:** `models/Asset/Image/Thumbnail.php:430-438`

- `getByName('')` → `null`; `empty('')` skips the `NotFoundException`; `return null` violates the return type. Twig
  `image.thumbnail(emptyField)` → 500.

**Fix direction:** treat `''` like `null`, or throw `NotFoundException` for any null result.

## [ ] B-43 – `Thumbnail::generate()` catches only `Exception`

**File:** `models/Asset/Image/Thumbnail.php:129-134`; `Processor.php:343-344`

- `TypeError` / `DivisionByZeroError` crash page rendering, e.g. legacy `['cover' => true, 'width' => 300]`
  (`Config.php:518-524` reads missing `height` → `null` → `Adapter::cover(int …)`), or `fopen()` → `false` after a
  failed save → `writeStream(false)`.

**Fix direction:** catch `\Throwable`; check `fopen` / save results in the Processor.

## [ ] B-44 – `DivisionByZeroError` in `getEstimatedDimensions()` during HTML rendering

**File:** `models/Asset/Image/Thumbnail/Config.php:619`, `:624`, `:628-629`

- `getHtml()` → … → `getEstimatedDimensions()`; legacy `['contain' => true, 'width' => 300]` → `$h / null`.

**Fix direction:** guard missing/zero arguments (skip the step or return `[]`).

## [ ] B-45 – Hi-res width/height attributes wrong when the source is smaller than requested

**File:** `models/Asset/Thumbnail/ImageThumbnailTrait.php:239-245`; `Processor.php:438-454`; `Config.php:669-670`

- Processor lowers the factor (500 px source, `scaleByWidth 400 @2` → effective 1.25), trait divides by the configured 2
  → reports 250 instead of 400; attributes change after generation. At @1.5, cast-before-multiply gives off-by-one.

**Fix direction:** cache the effective factor or derive CSS size from the estimate; round after multiplying.

## [ ] B-46 – Hi-res `goto` restart applies the factor inconsistently across steps

**File:** `models/Asset/Image/Thumbnail/Processor.php:417-454`

- When a later step lowers `$highResFactor`, already-applied steps keep the old factor (`resize` at 2×, `crop` at 1.67×
  → misaligned crop). The size check compares against the original source, not the current image.

**Fix direction:** compute the effective factor in a first pass, apply in a second.

## [ ] B-47 – Unknown transformation method → warning / `TypeError`

**File:** `models/Asset/Image/Thumbnail/Processor.php:423`, `:427`

- `self::$argumentMapping[$method]` undefined (e.g. `tifforiginal` on a non-TIFF) → warning; with arguments
  `array_search($key, null)` → `TypeError`.

**Fix direction:** `?? null` and `continue` with a log entry.

## [ ] B-48 – Valid thumbnails < 50 bytes are deleted and regenerated forever

**File:** `models/Asset/Image/Thumbnail/Processor.php:378-386`

- The "corrupt PNG" workaround deletes 1×1 GIFs / tiny WebPs; it also runs for existing files (2 extra remote calls) and
  may delete a file another process is still writing (unverified).

**Fix direction:** only check PNGs generated in this call; write to a temp key and move.

## [ ] B-49 – Focal point at `0` ignored; `null` → `0` does not clear thumbnails

**File:** `models/Asset/Image/Thumbnail/Processor.php:461`; `models/Asset/Image.php:335`

- Truthiness check / loose `!=` (`null != 0` is false).

**Fix direction:** `!== null` and strict comparison.

## [ ] B-50 – Case-sensitive SVG / vector detection

**File:** `models/Asset/Image.php:346`; `models/Asset/Image/Thumbnail.php:105`

- `LOGO.SVG` is rasterised despite `rasterizeSVG = false`; `isVectorGraphic()` false → wrong estimates.

**Fix direction:** add the `i` flag (or compare lowercased MIME type).

## [ ] B-51 – Stale thumbnail config in the same process after save/delete

**File:** `models/Asset/Image/Thumbnail/Config/Dao.php:84-114`, `:138-149`; `Config.php:200-212`, `:227-233`

- RuntimeCache key `imagethumb_<crc32>` is never invalidated: `getByName()` returns the old definition after `save()`,
  `exists()` is true after `delete()` (affects Processor TmpStore decisions `:224`, `:354`). Worker runtimes keep it
  until reset.

**Fix direction:** remove the key in `Dao::save()` / `delete()`.

## [ ] B-52 – `Image\Thumbnail\Config\Dao::exists(string $name)` ignores its argument

**File:** `models/Asset/Image/Thumbnail/Config/Dao.php:74-77`

- Uses `$this->model->getName()`.

**Fix direction:** use `$name` (or remove – no callers found).

## [ ] B-53 – `clearTempFiles()` deletes the other config type's directories with the same name; full recursive listing

**File:** `models/Asset/Thumbnail/ClearTempFilesTrait.php:30-37` (regex at `:32`)

- `(image|video|pdf)-thumb__…__<name>`: clearing image config `foo` also deletes `video-thumb__*__foo` and vice versa.
  Lists the entire thumbnail storage recursively (very slow on S3).

**Fix direction:** pass the type prefix per config class; list only the relevant subtree or keep an index.

## [ ] B-54 – Image config listing can contain `null`s and non-list arrays

**File:** `models/Asset/Image/Thumbnail/Config/Listing/Dao.php:33`, `:36`

- `getByName()` can return `null` → typed filter/order callbacks throw; `array_filter` keeps keys → JSON object.

**Fix direction:** drop nulls and `array_values()`.

## [ ] B-55 – `getImageTag()` cannot remove or customise `srcset`

**File:** `models/Asset/Image/Thumbnail.php:372-385`

- `srcset` / `data-srcset` is added after `$removeAttributes` and `imgCallback` run.

**Fix direction:** add `srcset` before remove/callback.

## [ ] B-56 – Cache buster corrupts data URIs and protocol-relative URLs

**File:** `models/Asset/Image/Thumbnail.php:162-171`, `:461`

- `'/cache-buster-N' . $path` is prefixed to `data:image/gif…` (`1x1_pixel`) and `//cdn…`.

**Fix direction:** skip for `^(data:|//|https?:)`.

## [ ] B-57 – Numeric media keys break the default `<source>` in `<picture>`

**File:** `models/Asset/Image/Thumbnail.php:267-272`

- Key `"1024"` becomes int; the appended default gets key `1025` → `media="1025"`. Works only because the file lacks
  `strict_types` (int passed to `string $mediaQuery`). (Admin UI allowing numeric keys unverified.)

**Fix direction:** keep the default config out of the media loop.

## [ ] B-58 – Throwaway orphan thumbnail generated per media query during HTML rendering

**File:** `models/Asset/Image/Thumbnail.php:177-197`; `ImageThumbnailTrait.php:231`

- `$thumb` uses media items without `filenameSuffix` → path matches neither variant; for `rotate`/`trim` configs
  `getWidth()` synchronously generates that orphan file. `$thumb->reset()` at `:181` does nothing.

**Fix direction:** build `$thumb` via `selectMedia()` like `getSrcset()`.

## [ ] B-59 – Media overlays keep stale paths after clone

**File:** `models/Asset/Image/Thumbnail/Config.php:778-787`

- `__clone` rebuilds `addOverlay` paths only in `$this->items`, not `$this->medias`.

**Fix direction:** iterate media item lists too.

## [ ] B-60 – Infinite recursion when `assets.preview_image_thumbnail` = `pimcore-system-treepreview`; inconsistent status-cache writes

**Files:** `models/Asset/Image/Thumbnail/Config.php:195-196`, `:253-254`; `Processor.php:349` vs `ImageThumbnailTrait.php:182`

- `getPreviewConfig()` ↔ `getByName()` recurse until stack overflow.
- Status-cache rows: Processor writes only for `Asset\Image`; the trait writes for any asset even when `status_cache` is
  disabled (impact unverified).

**Fix direction:** skip custom lookup when equal to the system name; one guarded helper for status-cache writes.

---

## Video / Document / MetaData

## [ ] B-61 – Video `ImageThumbnail::getMedia()` throws `TypeError` for normal videos and ignores frame / `imageAsset`

**File:** `models/Asset/Video/ImageThumbnail.php:233-240`

- `getById($this->asset->getCustomSetting('image_thumbnail_asset'))` with `null` → `TypeError` (strict). Ignores
  `$this->imageAsset` and the time offset, so media thumbnails never match `getPath()`; `$this->asset` may be null
  (`Video.php:147`).

**Fix direction:** reuse `generate()`'s source resolution; null-guard.

## [ ] B-62 – Exceptions during video conversion leave status `finished` → endless re-conversion on page views

**File:** `models/Asset/Video/Thumbnail/Processor.php:288-290`, `:93-106`

- `catch (Exception)` only logs; status stays `finished` with the format missing; `process()` dispatches a new job on
  every page view. `Error`s are not caught; lock not released in `finally` (see C-11).

**Fix direction:** set `error` in the catch, catch `\Throwable`, release lock in `finally`.

## [ ] B-63 – Temp files leak in the video conversion worker

**File:** `models/Asset/Video/Thumbnail/Processor.php:119`, `:259`, `:262-274`, `:283-284`

- `File::getLocalTempFilePath()` registers cleanup in the **web** request that called `process()`, not in the worker.
  Destination is unlinked only on success; DASH segments (`*-stream*.mp4`) are uploaded but never unlinked;
  `fopen()` → `false` passed to `writeStream()`.

**Fix direction:** unlink in `finally`; check `fopen`.

## [ ] B-64 – DASH (`mpd`) conversion drops main-config transformations/bitrates and mangles media options

**File:** `lib/Video/Adapter/Ffmpeg.php:89-93`, `:123`, `:136-143`

- `$command = [];` discards `$this->arguments` (`-vb`, `-ab`, `-t`, `-vf`); sub-converters contribute only `arguments`,
  never `videoFilter`; a single-element `-an` breaks the `% 2` pairing; `:137` duplicates `:136`.

**Fix direction:** build mpd arguments explicitly per sub-converter (`-filter:v:N`), keep global args, add flags without
pairing.

## [ ] B-65 – `cut` start time is silently reset

**File:** `lib/Video/Adapter/Ffmpeg.php:167-170`

- `$this->getDuration() * 100`: `null * 100 = 0` resets every offset; the `*100` factor is wrong (seconds vs. 100×).

**Fix direction:** compare with the real duration, only when non-null.

## [ ] B-66 – Concurrent `process()` calls start duplicate conversions; frontend GETs write to DB

**File:** `models/Asset/Video/Thumbnail/Processor.php:86-165`

- Check-then-act on the `thumbnails` custom setting without lock: two renders both save the asset and dispatch; the
  second overwrites `processId`.

**Fix direction:** per-(asset, config) lock around check, save and dispatch.

## [ ] B-67 – Global blocking `video-transcoding` lock with fixed 2 h TTL

**File:** `models/Asset/Video/Thumbnail/Processor.php:232-233`

- `acquire(true)` serialises all conversions system-wide and blocks every worker that picks up a video message (starving
  other `pimcore-core` messages). Conversions > 2 h lose the lock (no `refresh()`).

**Fix direction:** per-asset / semaphore lock or dedicated transport; `refresh()` during long runs.

## [ ] B-68 – Video getters save the asset and permanently flag processing as failed when ffmpeg is unavailable on the web node

**File:** `models/Asset/Video.php:197-205`, `:222-233`

- `getDuration()` / `getDimensions()` / `getWidth()` / `getHeight()` call `$this->save()` – persisting unsaved caller
  changes and firing save events during frontend GETs.
- If ffmpeg is missing on that host, `CUSTOM_SETTING_PROCESSING_FAILED` is saved permanently, disabling later processing
  on workers that do have ffmpeg. `getDuration()` never sets the flag, so broken videos spawn ffmpeg on every call.

**Fix direction:** no saves in getters; set the failed flag only when the backend actually ran and failed.

## [ ] B-69 – Document thumbnail returns the error placeholder under concurrency

**File:** `models/Asset/Document/ImageThumbnail.php:141-142`, `:164-166`; `models/Asset/Document.php:106`

- Non-blocking `$lock->acquire()` with `deferred = false` → concurrent requests get the "filetype-not-supported"
  placeholder. Video blocks (`Video/ImageThumbnail.php:135-136`) – inconsistent.

**Fix direction:** blocking acquire with timeout, or re-check `fileExists` after a wait.

## [ ] B-70 – Video image thumbnail with format `original`/`source` writes PNG data under `.mp4`

**File:** `models/Asset/Video/ImageThumbnail.php:94`; `ImageThumbnailTrait.php:436-460`; `Image/Thumbnail/Processor.php:166-168`

- `checkAllowedFormats` accepts `original`, `source` and `mp4`; `fileExtension` becomes `mp4` while PNG is saved → wrong
  MIME type. `Document\ImageThumbnail::createConfig` (`:193-198`) normalises to PNG; Video does not. (Inferred, not run.)

**Fix direction:** map these to PNG in Video `createConfig()`.

## [ ] B-71 – Video thumbnail filename regex is unanchored and case-sensitive

**File:** `models/Asset/Video/Thumbnail/Processor.php:117`

- Removes every `.mp4` occurrence (`a.mp4.final.mp4` → `a.final`); `foo.` strips all dots.

**Fix direction:** `pathinfo($filename, PATHINFO_FILENAME)`.

## [ ] B-72 – Video config listing ignores filter and order

**File:** `models/Asset/Video/Thumbnail/Config/Listing/Dao.php:28-36`

- `FilterListingTrait` / `OrderListingTrait` are never applied; `getTotalCount()` counts unfiltered ids.

**Fix direction:** mirror `Image/Thumbnail/Config/Listing/Dao.php`.

## [ ] B-73 – `Video\Thumbnail\Config::getByName()` returns the shared cached instance

**File:** `models/Asset/Video/Thumbnail/Config.php:113-132`

- Caller mutations (`setItems`, `selectMedia`) leak into the RuntimeCache; missing configs are not negatively cached.

**Fix direction:** `return clone $thumbnail;` (as the Image config does).

## [ ] B-74 – Listfolder-grid unmarshal of relation metadata throws `TypeError` on `null`/numeric data

**File:** `models/Asset/MetaData/ClassDefinition/Data/Asset.php:133`, `Document.php:133`, `DataObject.php:133`; caller
`models/Asset/Service.php:274`

- `getByPath(string)` receives `$item['data'] ?? null`; clearing a cell or sending an id crashes.

**Fix direction:** `is_string($data) && $data !== ''`; accept numeric ids.

## [ ] B-75 – Relation metadata passes string ids to `Element\Service::getElementById` (deprecation on every load)

**File:** `MetaData/ClassDefinition/Data/Asset.php:42`, `:91`; `Document.php:42`, `:91`; `DataObject.php:42`, `:91`

- `is_numeric("123")` is true; `getElementById` triggers a deprecation for string ids (`models/Element/Service.php:442-447`).

**Fix direction:** `(int)$value`.

## [ ] B-76 – Inconsistent "empty" values in relation metadata

**File:** `MetaData/ClassDefinition/Data/Asset.php:76`, `Document.php:76`, `DataObject.php:76`

- `getDataFromEditMode()` returns `''`, `normalize()` returns `null`; affects `isEmpty` and the search index.

**Fix direction:** return `null`.

## [ ] B-77 – Document/DataObject metadata grid round-trip uses `getFullPath()`

**File:** `MetaData/ClassDefinition/Data/Document.php:107`, `DataObject.php:107`

- Asset uses `getRealFullPath()`; a site-relative `getFullPath()` would not resolve in `getByPath()` (unverified in admin
  context).

**Fix direction:** `getRealFullPath()`.

## [ ] B-78 – Invalid date metadata stored as `false`

**File:** `MetaData/ClassDefinition/Data/Date.php:32-34`

- `strtotime()` → `false` is persisted.

**Fix direction:** return `null` on `false` / validate in `checkValidity()`.

## [ ] B-79 – Embedded metadata marked as extracted even when extraction failed

**File:** `models/Asset/MetaData/EmbeddedMetaDataTrait.php:66-98`

- exiftool `Process` uses the default 60 s timeout, exit code ignored; on any exception the flag is set with `[]` and
  never retried (`:48`).

**Fix direction:** set the flag only on success; explicit timeout; `isSuccessful()`.

## [ ] B-80 – `Video::getThumbnail()` enriches a never-written `medias` key; by-reference loop leaks reference

**File:** `models/Asset/Video.php:101-111`

- Processor writes only `formats[...]` (`Processor.php:277`); the `medias` branch is dead; `foreach (&$path)` leaves a
  dangling reference; missing `formats` key → warning.

**Fix direction:** drop `medias`, `array_map` without references, `?? []`.

## [ ] B-81 – Spherical metadata reader closes the asset's shared stream; extension check is case-sensitive

**File:** `models/Asset/Video.php:276`, `:278`, `:324`

- `fclose($this->getStream())` invalidates the cached stream; `in_array(ext, ['mp4','webm'])` skips `.MP4`.

**Fix direction:** open a dedicated stream via `Storage::readStream`; `strtolower()`.

## [ ] B-82 – `Video\Thumbnail\Processor::applyTransformations()` crashes on an unknown method

**File:** `models/Asset/Video/Thumbnail/Processor.php:175`

- `self::$argumentMapping[$method]` → `null` → `array_search` / `count` `TypeError`.

**Fix direction:** validate the method and skip with a log entry (same as B-47 for images).
