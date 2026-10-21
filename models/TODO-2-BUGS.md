# models – TODO 2: Bugs

Scope: **(1)** `models/Asset.php` and `models/Asset/**` (Dao, Listing, Service, Folder, Image, Image/Thumbnail/**, Thumbnail/**,
Video, Video/**, Document, Document/**, MetaData/**, WebDAV/**, Enum, Archive/Audio/Text/Unknown), plus the helpers they
directly call where a defect originates there (`lib/Video/Adapter/Ffmpeg.php`, `lib/Messenger/Handler/AssetUpdateTasksHandler.php`)
**(2)** `models/WebsiteSetting.php` and `models/WebsiteSetting/**` (Dao, Listing, Listing/Dao), plus the direct consumer
`lib/Config.php::getWebsiteConfig()` (`:173-262`), the `website_settings` schema (`bundles/InstallBundle/dump/install.sql:603-615`)
and `tests/Model/WebsiteSetting/WebsiteSettingTest.php`
**(3)** `models/Dependency.php` and `models/Dependency/Dao.php`, plus the direct consumers where a defect originates
there (`lib/Messenger/Handler/ElementDependenciesHandler.php`, `models/Element/AbstractElement.php::getDependencies()`,
the dependency helpers in `models/Element/Service.php:163-263`), the `dependencies` schema
(`bundles/InstallBundle/dump/install.sql:63-73`) and `tests/Model/Element/DependenciesTest.php`
**(4)** `models/Translation.php` and `models/Translation/**` (Dao, Listing, Listing/Dao), plus the direct consumers where
a defect originates in the model interplay (`lib/Translation/TranslationEntriesDumper.php`,
`lib/Translation/Translator.php::lazyInitialize()` / `checkForEmptyTranslation()` (`:138-298`),
`models/DataObject/QuantityValue/Service.php::generateDefinitionJson()` (`:66-90`)), the `translations_messages` schema
(`bundles/InstallBundle/dump/install.sql:432-444`) and `tests/Unit/Translation/TranslatorTest.php`
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md),
[CoreBundle TODO-2](../bundles/CoreBundle/TODO-2-BUGS.md) (B-58 lock backends, B-65/B-69 thumbnail route – not repeated)

Functional defects: wrong results, crashes on reachable input, swallowed errors, misleading output.
IDs are stable – refer to them as `B-xx` in later sessions. Line numbers are from commit `1b0711d927` (Scope 2:
`7f0331cab9`, Scope 3: `2d382456c7`, Scope 4: `3e2bf1c4d8`).
Grouped by area, roughly ordered by impact inside each group: **Asset.php** B-01–B-19, **Dao / Listing / Service /
Folder / WebDAV** B-20–B-36, **Image thumbnails** B-37–B-60, **Video / Document / MetaData** B-61–B-82,
**WebsiteSetting** B-83–B-91, **Dependency** B-92–B-106, **Translation** B-107–B-125.

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

---

## WebsiteSetting (Scope 2)

## [ ] B-83 – `pimcore_website_config()` and `WebsiteSetting::getByName()` resolve overrides differently; result depends on row id order

**Files:** `lib/Config.php:207-237` vs `models/WebsiteSetting/Dao.php:50-78`; `models/WebsiteSetting/Listing/Dao.php:32`

- `getWebsiteConfig()` walks **all** rows in unspecified order (the listing has no `ORDER BY`, so it is effectively
  primary-key order) and:
  - skips a row without language if the key is already set (`:225`);
  - lets any language row overwrite.
- Consequences (`a` = global neutral, `b` = site 1 neutral, `c` = global `en`; site 1, language `en`):
  - `getByName('x', 1, 'en')` → `b` (site beats language, `ORDER BY CONCAT(siteId, language) DESC`).
  - `pimcore_website_config('x')` → `a` is set, `b` is **skipped** because `a` already set the key, then `c` overwrites
    → `c`.
  - If `b` had a lower id than `a`, the result would be `b`. The value shown on the frontend depends on creation order.
- A site-specific neutral override is ignored by `pimcore_website_config()` whenever a global neutral row with a lower id
  exists – the most common use case of site overrides.

**Fix direction:** implement one precedence rule (site+language > site > language > global, matching
`WebsiteSettingTest`) in a shared resolver used by both APIs; order the listing explicitly. Extend the test for
`getWebsiteConfig()` parity (I-40).

## [ ] B-84 – `siteId = 0` and `siteId IS NULL` are ranked differently in `getByName()`

**File:** `models/WebsiteSetting/Dao.php:59-69` (`ORDER BY CONCAT(siteId, language) DESC, siteId DESC, language DESC`)

- Both values mean "all sites" (the `WHERE` accepts both, `lib/Config.php:213` treats `0` as global), but
  `CONCAT(NULL, …)` is `NULL` and sorts last, while `CONCAT(0, '')` is `'0'`.
- With a legacy row (`siteId = 0`, neutral; migrated by `Version20210323082921`) and a newer global language row
  (`siteId NULL`, `en`): `getByName('x', null, 'en')` returns the **neutral** row, although the language-specific one
  should win (it wins when both have `siteId NULL`).
- Ranking is also done by **string** comparison of `CONCAT(siteId, language)`. It happens to work because only one real
  site id can match, but the intent is opaque.

**Fix direction:** explicit ranking, e.g.
`ORDER BY (siteId = :site) DESC, (language = :lang) DESC, id ASC`, and normalise `0` → `NULL` on save (plus a data
migration).

## [ ] B-85 – `$fallbackLanguage` is never used when a language-neutral row exists

**File:** `models/WebsiteSetting.php:95-115`; `models/WebsiteSetting/Dao.php:64-68`

- The query for `$language` already matches neutral rows (`language = ''`), so `NotFoundException` – the only path to
  the fallback – is thrown only when **no** neutral row exists either.
- Rows `neutral = 'x'`, `en = 'y'`: `getByName('n', null, 'de', 'en')` → `'x'`, not `'y'`. The docblock ("if property
  cannot be found the value of property without language is returned") documents neither order clearly; callers who pass
  a fallback language expect `de → en → neutral`.

**Fix direction:** decide and document the order. If it is `language → fallback → neutral`, query with
`language IN (:lang, :fallback, '')` and rank in that order.

## [ ] B-86 – No uniqueness for `(name, siteId, language)`; empty names accepted

**Files:** `bundles/InstallBundle/dump/install.sql:603-615` (only non-unique `INDEX name`, `INDEX siteId`);
`models/WebsiteSetting.php:291-301`

- Duplicate rows for the same name/site/language are allowed (API, imports, double-click in admin). `getByName()` then
  returns an arbitrary one (no id tie-breaker in `ORDER BY`); `getWebsiteConfig()` returns the last or first by id
  (B-83). Editing one of them in the admin may have no visible effect.
- `save()` does not validate that `name` is non-empty (default `''`).

**Fix direction:** validate in `save()` (non-empty name, no duplicate for the same site/language) and add a unique key.
`siteId NULL` defeats a plain unique index, so normalise to `0` first or use a generated column.

## [ ] B-87 – `type` is not validated; an unknown type silently makes the setting disappear

**Files:** `models/WebsiteSetting.php:228-248`; `install.sql:606` (`ENUM('text','document','asset','object','bool')`);
`lib/Config.php:229-234`

- `setType('number')` (or a typo) is stored as `''` without error under Pimcore's `sql_mode = ''` (see CoreBundle
  TODO-1 notes). `getWebsiteConfig()` then maps it to `default => null`, and `isset($data)` drops the key, so the setting
  silently vanishes from `pimcore_website_config()`. `getData()` returns the raw value instead of lazy-loading an element.
- `setData(ElementInterface)` sets the type automatically, but `setData()` with a scalar never resets a previous element
  type, so `setType('document')->setData('hello')` stores text under an element type (`getData()` returns `'hello'`
  because it is not numeric, while `getWebsiteConfig()` returns it as an "element").

**Fix direction:** backed enum for the type (I-33); reject unknown values in `setType()`; validate data against the
type in `save()`.

## [ ] B-88 – `save()` with a manually set id that does not exist silently does nothing; add/update detection differs from the DAO

**Files:** `models/WebsiteSetting.php:136-141`, `:291-301`; `models/WebsiteSetting/Dao.php:80-87`, `:95-102`

- `(new WebsiteSetting())->setId(999)->setName('x')->save()`: the DAO sees a truthy id → `update()` → 0 rows affected,
  no error. `PRE_UPDATE`/`POST_UPDATE` are dispatched for a write that never happened.
- `save()` decides "add" by `$this->id === null`, the DAO by `if ($this->model->getId())`. With id `0` the model fires
  `PRE_UPDATE`/`POST_UPDATE` while the DAO inserts.

**Fix direction:** use one `isNew()` check in both places; make `update()` throw `NotFoundException` when no row is
affected (or make `setId()` `@internal`).

## [ ] B-89 – `Dao::create()` is a non-atomic INSERT + UPDATE

**File:** `models/WebsiteSetting/Dao.php:104-115`

- The INSERT writes only `name` and `siteId`. If the following `update()` fails (DB error, invalid data), a half row
  remains with `type NULL`, `data NULL`, `language ''`, `creationDate 0`. That row is a valid match for `getByName()` (and
  shadows lower-ranked rows), and its value is `null`.
- No transaction; `POST_ADD` is not dispatched and the caller gets an exception, so nobody cleans up.

**Fix direction:** single `INSERT` with all valid columns (see I-36), or wrap both statements in a transaction.

## [ ] B-90 – Website setting listing can contain `null` entries (concurrent delete)

**Files:** `models/WebsiteSetting/Listing/Dao.php:32-43`; consumer `lib/Config.php:210-212`

- `load()` selects ids and then calls `WebsiteSetting::getById()` per id. A row deleted between both steps yields
  `null` in the `WebsiteSetting[]` array. `getWebsiteConfig()` then calls `$item->getSiteId()` on `null` → `Error`. This
  breaks every page that uses `pimcore_website_config()` until the cache is rebuilt.

**Fix direction:** skip `null`s (or hydrate from a single `SELECT *`, see I-35).

## [ ] B-91 – `clearDependentCache()` does not invalidate the full-page output cache

**File:** `models/WebsiteSetting.php:268-271`; `lib/Config.php:205`

- Only the tag `website_config` is cleared. Full-page cache entries (`FullPageCacheListener`, tag `output`) that rendered
  the old value stay cached until their lifetime ends, so changes to texts, tracking IDs or feature switches stored as
  website settings do not reach cached pages. (The reverse works: clearing `output` drops the config cache, because the
  config is tagged `output`.) Needs a decision on whether this is intended; document it if so.

**Fix direction:** clear `output` as well (as document saves do), or document the expected delay in the admin UI.

---

## Dependency (Scope 3)

## [ ] B-92 – `ElementDependenciesHandler` leaks `hideUnpublished = false` / `getInheritedValues = false` into the rest of the process on any exception

**File:** `lib/Messenger/Handler/ElementDependenciesHandler.php:47-75`

- `showUnpublished()` (`:49`) and `setGetInheritedValues(false)` (`:51`) change process-wide statics. They are restored
  at `:70-71` with no `try/finally`. An exception from `resolveDependencies()` (broken relation, missing class
  definition, a field getter throwing) skips the restore.
- The handler runs **synchronously inside the web request / CLI command** by default (see TODO-1 Scope 3 notes). If the
  caller catches the save exception (importers, REST APIs, `try { $obj->save(); } catch …`), every later listing and getter
  in that process sees **unpublished documents/objects** and **no inherited values**. In messenger workers and
  long-running imports this lasts until restart. Unpublished content can end up in frontend output, feeds, sitemaps or
  search indexes.
- `Dao::save()` (`:73`) runs after the restore, so its errors do not leak state. The resolve step is the risk.

**Fix direction:** wrap the resolve loop in `try { … } finally { restore }`, or run it through the existing scoped helper
`DataObject\Service::useInheritedValues(false, fn () => …)` (`models/DataObject/Service.php:1962`) plus an equivalent
helper for `hideUnpublished`.

## [ ] B-93 – `getFilterRequiredByPath()` joins on `targettype` instead of `sourcetype`: wrong or missing "required by" results

**File:** `models/Dependency/Dao.php:152`, `:157`, `:162`

```sql
INNER JOIN objects o ON o.id = d.sourceid AND d.targettype = 'object'
WHERE d.targettype = '<targetType>' …
```

- The join condition should restrict `d.sourcetype`. Combined with `WHERE d.targettype = :targetType`, only the branch
  whose literal equals the **target** type can return rows. That branch joins **every** source id (any type) against
  that one table.
- Example: asset 10 is required by object 5 and document 7. Filtering the asset's "required by" list only runs the
  `assets` branch. It matches `assets.id = 5` and `assets.id = 7` (unrelated assets, or none), filters on **their**
  paths and returns `{id: 5, type: 'object'}` when the unrelated asset 5's path matches. Real sources whose own path
  matches are never found.
- `getRequiredByWithPath()` (`:343`, `:348`, `:353`) has the correct condition (`d.sourceType = …`). Compare with it.

**Fix direction:** `AND d.sourcetype = 'object'` / `'document'` / `'asset'` in the three joins. Add a test with mixed
source types (I-50).

## [ ] B-94 – Filter value is an unescaped, case-sensitive regex: uppercase never matches, `.`/`(`/`[` misbehave, empty value errors

**File:** `models/Dependency/Dao.php:94-104`, `:153-163`; schema `install.sql` (`objects`/`documents`/`assets`
`path`, `key`, `filename` are `utf8_bin`)

- `LOWER(CONCAT(path, key)) RLIKE '<value>'` lowercases only the column side. The columns are `utf8_bin`, so the regex
  comparison is case-sensitive: searching `Logo` never matches `/Logo.png`, unless the caller lowercases the value.
- The value is a regex. `image.jpg` also matches `imageXjpg`. `file(1)` matches `file1` but not `file(1)`. An
  unbalanced `[`, `(` or a trailing `\` raises a MySQL regex error → 500. Editors type file names, not regexes.
- `$value` defaults to `null` (`Dependency.php:105`, `:112`) → `RLIKE ''`. MySQL 8 (ICU) rejects an empty pattern
  ("Illegal argument to a regular expression", not verified at runtime); MariaDB matches everything.

**Fix direction:** treat the value as a literal: `LIKE :value` with `'%' . Helper::escapeLike(mb_strtolower($value)) . '%'`
(and `LOWER()` on both sides, or a `_ci` collation in the expression). Reject or short-circuit an empty value.

## [ ] B-95 – Search value goes through `sprintf()`: `%` in the filter text throws or rewrites the query

**File:** `models/Dependency/Dao.php:108-110`, `:167-169`

```php
$query = sprintf($query . ' LIMIT %d,%d', $offset, $limit);
```

- `$query` already contains the raw `$value` (C-16). Any `%` in it is parsed as a format specifier: `100%` makes the
  following characters into a specifier (`%'`), `50%s` consumes an argument. Results are `ValueError` /
  `ArgumentCountError` (PHP 8) or a silently changed pattern / `LIMIT`. Searching for `%` only works without pagination.
- `getRequiredBy()` (`:302`) uses the same pattern. It is safe today (no user input in the query) but fragile.

**Fix direction:** append `' LIMIT ' . (int)$offset . ', ' . (int)$limit` (as `getRequiredByWithPath()` does,
`:357-359`), or use DBAL `setFirstResult()` / `setMaxResults()` (I-43).

## [ ] B-96 – `getFilterRequires*ByPath()` whitelist `orderBy = 'path'`, but the queries select no `path` column

**File:** `models/Dependency/Dao.php:79`, `:88-106`, `:138`, `:147-165`

- Both filter queries return `SELECT id, type` only. `orderBy: 'path'` passes the whitelist and fails with
  "Unknown column 'path' in 'order clause'".
- Not reachable through the model wrappers today (`Dependency.php:105-117` do not forward `$orderBy` /
  `$orderDirection`), so sorting a filtered list by path is impossible and calling the DAO directly crashes.

**Fix direction:** select `CONCAT(…) AS path` in each `UNION` branch (as in `getRequiredByWithPath()`) and forward
`$orderBy` / `$orderDirection` from the model (I-43).

## [ ] B-97 – Rows pointing to deleted elements are never removed for several element types; counts and `isRequired()` include them

**Files:** `models/Dependency/Dao.php:185-203`, `:368-371`; `models/Dependency.php:160-177`;
`lib/Messenger/Handler/SanityCheckHandler.php` (`performSanityCheck()`)

- On delete, `cleanAllForElement()` removes only rows where the element is the **source**. Rows where it is the
  **target** are expected to vanish when the sanity check re-saves each source. `performSanityCheck()` returns early
  for everything that is not `PageSnippet`, `Concrete` or `Asset`: document folders, links and hardlinks, and data object
  folders (e.g. a property on a folder, or a link target). Their rows to the deleted element stay forever. The same
  happens for all elements while `pimcore.dependency.enabled` is `false`.
- Effects:
  - `getRequiresTotalCount()` (in-memory list) and `getRequiredByTotalCount()` / `isRequired()` (SQL `COUNT(*)`) count
    rows whose element no longer exists.
  - `Element\Service::get*DependenciesForFrontend()` silently drops them (`getDependedElement()` → `null`), so pages are
    shorter than `$limit` and the total does not match the rows shown.
  - `getBySourceId()` / `getRequiredBy()` `LEFT JOIN` and keep orphans; `getRequiredByWithPath()` / filter queries use
    `INNER JOIN` and hide them. The same element shows different counts in different tabs.

**Fix direction:** in `cleanAllForElement()` also delete rows where the element is the **target**. The sanity check is
still needed to clean the sources' data, but the graph must not depend on it. Count with the same join the list uses.

## [ ] B-98 – `cleanAllForElement()` dispatches sanity checks before deleting and swallows DB errors inside the element's delete transaction

**File:** `models/Dependency/Dao.php:185-203`; callers `models/Asset.php:1018`, `models/Document.php:640`,
`models/DataObject/AbstractObject.php:438` (all inside `beginTransaction()` … `commit()`)

- `SanityCheckMessage`s are dispatched **before** the rows are deleted and before the element delete is committed.
  This is safe with the default `pimcore_core` transport (`doctrine://default`, same connection, enqueued inside the
  transaction). With a `sync://`, AMQP or Redis transport (a common production change) the message is handled before
  commit. The source is re-saved while the target still exists, its dependency rows to the target are recreated, and
  they stay (B-97). If the delete is rolled back, the sources still get a needless "Sanity Check" version.
- `catch (Exception $e) { Logger::error(...) }` (`:200-202`) hides DB failures. The element delete then **commits**
  with its dependency rows intact. No caller or `POST_DELETE_FAILURE` listener is told.

**Fix direction:** delete first, dispatch after commit (collect messages and dispatch them from the caller, or use
`DispatchAfterCurrentBusStamp` / a post-commit hook). Let DB exceptions propagate so the element transaction rolls
back.

## [ ] B-99 – Uninitialized typed properties and `null` ids crash `Dependency` (`Error` / `TypeError`)

**Files:** `models/Dependency.php:32`, `:38`, `:51-57`, `:91-94`, `:148-151`; `models/Dependency/Dao.php:41-46`;
`models/Element/AbstractElement.php:613-620`

- `protected int $sourceId; protected string $sourceType;` have no default. `new Dependency()` followed by any getter
  or DAO method except `cleanAllForElement()` throws "must not be accessed before initialization".
- `Dependency::getBySourceId(0, 'asset')`: `Dao::getBySourceId()` skips the setters when `$id` is falsy
  (`if ($id && $type)`, `:43`) and then reads `getSourceId()` → `Error`.
- `AbstractElement::getDependencies()` on an **unsaved** element passes `getId()` (`null`) to
  `getBySourceId(int $id, …)` → `TypeError`.
- `addRequirement(int $id, …)` (`Dependency.php:63`) receives the ids from `resolveDependencies()`. A relation to an
  unsaved element (`getId()` `null`, e.g. `Property::resolveDependencies()`, `models/Property.php:270`) throws
  `TypeError` in the strict handler. Combined with B-92 and C-17 this leaks state and leaves the graph incomplete.

**Fix direction:** constructor with required `int $sourceId, string|ElementType $sourceType` (I-41/I-42), or nullable
properties with explicit checks. Return an empty `Dependency` for unsaved elements in `getDependencies()`. Skip
requirements with a `null`/`0` id in the handler.

## [ ] B-100 – `AbstractElement::getDependencies()` is cached on the element and never reset after save or delete

**File:** `models/Element/AbstractElement.php:47`, `:613-620` (reset only in `__clone()`, `:675`)

- Elements are shared through `RuntimeCache`. After `$element->save()` the sync handler rewrites the rows, but
  `$element->getDependencies()` keeps returning the `requires` list loaded before the save. This lasts for the rest of the request, or for the whole worker in messenger consumers and
  long-running imports.
- `DependenciesTest` reloads with `['force' => true]` before asserting, so the test hides this.
- The cached object also keeps the full `requires` array (I-44) in memory for the element's lifetime.

**Fix direction:** set `$this->dependencies = null` in `save()` / `delete()` (or in `addToDependenciesQueue()`), or do not
cache at all (the getter is cheap once I-44 lands).

## [ ] B-101 – Pagination: `$limit` without `$offset` is ignored in `getRequires()` and the SQL getters

**Files:** `models/Dependency.php:96-103`; `models/Dependency/Dao.php:108`, `:167`, `:301`, `:357`

- `getRequires(null, 25)` returns **all** rows. `array_slice()` runs only when `$offset !== null`. The DAO getters
  apply `LIMIT` only when both are non-null. Callers that pass only a limit ("first 25") get everything.

**Fix direction:** treat `null` offset as `0` when a limit is given, in all getters.

## [ ] B-102 – No unique tie-breaker in `ORDER BY`: pages overlap or skip rows

**File:** `models/Dependency/Dao.php:55`, `:106`, `:165`, `:298`, `:355`

- `getRequiredByWithPath()` / filter queries sort by `type` or `path` only. Many rows share a type, and `id` is not
  unique across types (object 5 and asset 5). MySQL may return ties in a different order per query, so `LIMIT`-based
  paging in the admin shows duplicates and misses rows.
- `getBySourceId()` / `getRequiredBy()` sort by the `LEFT JOIN`ed path columns. `NULL`s sort first, so the result is
  "all assets, then documents, then objects" by accident, with no final tie-breaker (e.g. two rows with the same
  `path`/`key` after a broken move).

**Fix direction:** always append `, type, id` (or `d.id`) to the `ORDER BY`. Sort the mixed list by one computed `path`
expression if a global path order is wanted.

## [ ] B-103 – Concurrent / out-of-order dependency saves for the same source: the older state can win

**Files:** `models/Dependency/Dao.php:222-283`; `lib/Messenger/Handler/ElementDependenciesHandler.php:39-45`

- The handler re-loads the element and recomputes the rows. With an async `pimcore_dependencies` transport and several
  consumers, or two web requests saving the same element, two `save()` calls interleave (read existing → delete →
  insert). The one that **finishes** last wins even if it computed from an older element state. Rows the newer state
  needs can be deleted.
- Duplicate entries in `requires` (two properties pointing to the same element) are inserted twice. The second insert
  hits the `combi` unique key, and the exception is swallowed (`:279-280`).

**Fix direction:** serialise per source (row lock on the element with `SELECT … FOR UPDATE` inside the C-17
transaction, or a `LockFactory` lock keyed by `type-id`). Deduplicate `requires` before the delta (I-45).

## [ ] B-104 – Invalid source types: HTTP exception from the model layer, silently stored elsewhere

**Files:** `models/Dependency/Dao.php:73-77`, `:132-136`, `:323-327`; `models/Dependency.php:153-158`;
`install.sql:66`, `:68` (`ENUM('document','asset','object')`)

- Only the three path getters validate the type, and they throw `Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException`.
  Symfony turns it into an HTTP **400** and logs it as a security event. In CLI / messenger it is an unexpected HTTP type.
- `setSourceType()`, `addRequirement()`, `getBySourceId()`, `getRequiredBy()` and `save()` accept any string.
  `addRequirement(5, 'Object')` or `'dataobject'` is stored as `''` (empty enum value) under Pimcore's `sql_mode = ''`
  (see B-87). The row is never found again and blocks the slot in the `combi` key.

**Fix direction:** validate once in the setters / `addRequirement()` with an `ElementType` enum (I-42) and throw
`\InvalidArgumentException` (or `\ValueError` from `ElementType::from()`).

## [ ] B-105 – Filter helpers in `Element\Service` omit the `requires` / `requiredBy` key when nothing matches

**File:** `models/Element/Service.php:207-242`

- `getRequiredByDependenciesForFrontend()` / `getRequiresDependenciesForFrontend()` initialise the list key to `[]`.
  The filter variants `getFilterRequiresForFrontend()` / `getFilterRequiredByPathForFrontend()` do not. An empty search
  returns `{hasHidden: false}` without `requires` / `requiredBy`. The admin store then has to handle a missing root, and
  `$dependencies[...]` in PHP consumers raises "Undefined array key".

**Fix direction:** initialise the key in both, or merge the four helpers into one (I-47).

## [ ] B-106 – Sync dependency handler errors make a committed element save look failed and skip `POST_*` events

**Files:** `models/Asset.php:611` (before `POST_UPDATE`/`POST_ADD` at `:622-629`, failure events `:633-644`), `models/Document.php:386`,
`models/DataObject/AbstractObject.php:609`; `lib/Messenger/Handler/ElementDependenciesHandler.php`

- With the default sync routing, `addToDependenciesQueue()` runs the handler immediately. Any exception from it
  (C-17, B-92, B-99, B-103) is wrapped in `HandlerFailedException` and escapes from `save()` **after** the element
  transaction was committed.
- The caller (admin UI, importer) reports "save failed" although the data is stored. `Asset::save()` catches it and
  dispatches `POST_UPDATE_FAILURE` / `POST_ADD_FAILURE` for a committed write, while `POST_ADD` / `POST_UPDATE`
  listeners (search index, cache warmers, workflows) never run. Retrying creates another version.

**Fix direction:** catch and log dependency failures in `addToDependenciesQueue()` (the graph can be rebuilt), or move
the dispatch after the `POST_*` event. Long term, route `ElementDependenciesMessage` async by default.

---

## Translation (Scope 4)

## [ ] B-107 – `getByKey()` runtime cache: the key ignores `$create` / `$returnIdIfEmpty`, one shared mutable instance, never invalidated

**File:** `models/Translation.php:248-255`, `:283-294`, `:354-373`

- Cache key is `translation_<id>_<domain>[_<languages>]`. A call with `$returnIdIfEmpty = true` stores an object whose
  empty languages were replaced by the key. A later plain `getByKey($id, $domain)` in the same request gets that
  object, so the caller sees the key as the "translation", and saving it persists that (see C-18).
- The cached object is returned **by reference**. Any caller that changes it (`addTranslation()`, import, Translator)
  changes it for all later callers, including the copy in the dumper queue.
- `save()` / `delete()` never update or remove the entry. Within the request (and in worker runtimes, where
  `RuntimeCache` survives, see C-15) `getByKey()` keeps returning a deleted translation or outdated texts.
- `$returnIdIfEmpty` uses `empty()` (`:286`): a real translation `"0"` is replaced by the key.
- The key format is ambiguous: id `a_b` + domain `c` and id `a` + domain `b_c` share a key. Domains are config values, so
  this is unlikely, but the separator should not be a valid character.

**Fix direction:** cache the persisted state only (no presentation flags), return a clone or treat the object as
immutable, and update/remove the entry in `save()` / `delete()`. Use `=== ''` instead of `empty()`.

## [ ] B-108 – `getByKey()` swallows every `Exception`, but not `TypeError`; DB failures look like "not found"

**File:** `models/Translation.php:262-281`; `models/Translation/Listing/Dao.php:117-123`

- `catch (Exception $e)` turns connection loss, lock timeouts, unknown columns and SQL syntax errors into `null`, or into
  a fake translation made from the key when `$returnIdIfEmpty` is set. Nothing is logged. The data-loss path is C-22.
- `TypeError` from hydration (`NULL` text/dates, C-19, B-109) is an `Error` and is **not** caught, so the method can
  crash and silently return `null` for the same broken key, depending on which column is broken.
- `NotFoundResourceException` is a Symfony **translation loader** exception ("resource file not found"), used here as a
  "row not found" signal. `@throws Exception` is documented but almost never thrown.
- `Listing\Dao::load()` drops keys for which `getByKey()` returns `null` (e.g. no row in the listing's `languages`
  subset). Pages are shorter than `limit`, and `getTotalCount()` does not match (B-118).

**Fix direction:** catch only the "not found" case (an own `NotFoundException` from the DAO, plus
`TableNotFoundException` during the transition). Let other DB errors propagate. Log the rest.

## [ ] B-109 – Hydration keeps the newest `creationDate` and the last row's owner/type; `save()` writes them to every language; `NULL` dates crash

**Files:** `models/Translation/Dao.php:53-73`, `:112-122`; `models/Translation/Listing/Dao.php:71-88`; `lib/Db/Helper.php:38-53`

- `getByKey()` orders rows by `creationDate` ascending and calls `setCreationDate()`, `setModificationDate()`,
  `setType()`, `setUserOwner()`, `setUserModification()` for **each** row. The last row wins: the model gets the
  **newest** creation date and the owner of the most recently created language, not the oldest one.
- `Dao::save()` writes these values into **every** language row (`Helper::upsert()` updates all columns). Each save
  moves the creation date and owner of older languages forward. The history of who created what, and when, is lost.
- `creationDate` / `modificationDate` are `DEFAULT NULL`. A `NULL` reaches `setCreationDate(int)` /
  `setModificationDate(int)` and throws a `TypeError` (not caught, see B-108). `Listing\Dao::getAllTranslations()` has
  the same problem and also calls `setDate(null)` through the "legacy support" branch (`:82-84`), which is dead code
  anyway: `setModificationDate()` overwrites it two lines later.
- Different `type` values per language (possible through direct SQL or old data) are flattened to the last row's type
  on the next save.

**Fix direction:** hydrate key-level fields once with `MIN(creationDate)`, `MAX(modificationDate)`, and the owner of the
first row. Do not update `creationDate` / `userOwner` on existing rows (`ON DUPLICATE KEY UPDATE` only `text`, `type`,
`modificationDate`, `userModification`). Treat `NULL` dates as `null` (make the setters nullable).

## [ ] B-110 – `Dao::save()`: rewrites every language on every save, is not atomic, and does not validate key or type

**File:** `models/Translation/Dao.php:82-125`; `models/Translation.php:84-112`

- Every save upserts **all** languages in the model, with a new `modificationDate` and `userModification`, even if
  only one language changed. The modification info shown in the admin is wrong for the untouched languages, and
  concurrent editors of different languages overwrite each other (see C-22).
- One `insert`/`update` round trip per language, without a transaction. A failure in the middle leaves the key
  partially saved, and `POST_SAVE` is not dispatched.
- `if ($this->model->getKey() !== '')` lets `null` through (a `new Translation()` without `setKey()`). The insert then
  fails with "Column 'key' cannot be null", or writes `''` in non-strict SQL mode. An empty key is skipped silently and
  still dispatches `POST_SAVE`.
- No length check: keys longer than 190 characters fail with "Data too long" (strict mode), or are **silently
  truncated** in non-strict mode and then collide with, and overwrite, another key with the same 190-character prefix.
  Only the Translator checks the length (B-123).
- `type` is not validated: any string is accepted. Longer than 10 characters fails or is truncated. Anything other
  than `simple` is treated as ICU by the Translator (`Translator.php:172-176`). A typo makes the message go through
  `MessageFormatter`, so plain texts with `{`/`}` throw at render time.

**Fix direction:** validate key (non-empty, ≤ 190 **characters**, `mb_strlen`) and type (`TranslationType` enum,
I-52) in the model. Save only changed languages, in one transaction and with one multi-row statement (I-55).

## [ ] B-111 – Disallowed languages are skipped silently: `save()` reports success, `POST_SAVE` fires, the in-memory object keeps the rejected text

**File:** `models/Translation/Dao.php:92-105`; `models/Translation.php:354-363`

- Languages the user may not edit are skipped with a `Logger::warning` (`:102`). The caller (admin grid, import)
  is not told: `save()` returns normally, `POST_SAVE` listeners (search index, sync to external systems) receive the
  model **with** the rejected texts, and the `RuntimeCache` copy (B-107) keeps them for the rest of the request.
- `importTranslationsFromFile()` counts those rows as imported and does not add them to `$delta`.
- For a user with language restrictions who browses the frontend, the Translator's auto-create (C-22) writes only
  the allowed languages. The other locales keep missing the key, so it is queued and saved again on **every**
  request.
- The permission is checked against the user resolved by `updateModificationInfos()`, i.e. whoever is logged in
  when the dumper runs at `kernel.terminate`, not the code that queued the entry.

**Fix direction:** throw an access-denied exception for disallowed languages (or return the skipped list), and do not
dispatch `POST_SAVE` for data that was not written. Auto-create entries (system writes) should bypass the editor
permission check explicitly. See C-20 for the bypass in the same check.

## [ ] B-112 – `createOrUpdateTable()` runs DDL on every save: implicit commit of the caller's transaction

**File:** `models/Translation/Dao.php:82-86`, `:191-211`

- `save()` always runs `CREATE TABLE IF NOT EXISTS …`. In MySQL/MariaDB, `CREATE TABLE` causes an **implicit commit**,
  also when the table already exists. If `Translation::save()` is called inside a transaction (installers, migrations,
  element save listeners, imports wrapped in `transactional()`), everything before it is committed, and a later
  `rollBack()` cannot undo it. DBAL still thinks a transaction is open and fails with "There is no active transaction".
- It takes a metadata lock on every save. Under load, or while a long query or `ALTER` runs on that table, saves wait.
- The name says "update", but the method never updates an existing table. Schema changes (e.g. a new column) are not
  applied to existing domain tables.
- This DDL is a second copy of the schema (`install.sql:432-444`); the copies already differ (`ENGINE=InnoDB` only here).

**Fix direction:** create the domain tables at install/migration time, or when the domain list changes, plus a
console command (I-62). If lazy creation must stay, run it only after `TableNotFoundException`, never inside an open
transaction.

## [ ] B-113 – `delete()`: stale runtime cache, a queued auto-create re-creates the key, inconsistent cache/event order

**File:** `models/Translation.php:354-373`; `models/Translation/Dao.php:130-133`; `TranslationEntriesDumper.php:29-40`

- `delete()` does not remove the `RuntimeCache` entry (B-107), so `getByKey()` keeps returning the deleted key in the
  same request.
- If the key is also in the dumper queue (Translator saw it missing earlier in the request, or `getByKey(create: true)`
  queued it), `kernel.terminate` saves it again: the delete is undone.
- `save()` clears the translator cache **after** `POST_SAVE`, `delete()` clears it **before** `POST_DELETE`. Listeners
  on `POST_SAVE` that render or translate get the old texts.
- Deleting a key in a domain whose table does not exist yet throws a `TableNotFoundException`.

**Fix direction:** remove the key from `RuntimeCache` and from the dumper queue in `delete()`. Clear caches before the
`POST_*` event in both methods.

## [ ] B-114 – `getByKeyLocalized()`: explicit `$language` is ignored for the admin domain; session lookup in the model; unknown language returns `null`

**File:** `models/Translation.php:313-345`

- For the admin domain, a logged-in user's language **overwrites** the `$language` argument (`:316-320`). Code that
  asks for a specific admin language (e.g. to send a notification in the recipient's language) gets the current user's
  language instead.
- The fallback `Tool\Authentication::authenticateSession()` reads the session and unserialises the security token on
  every call when no token user is set (CLI, frontend requests). It also calls `setLastLoginDate()` on the user object
  as a side effect. Model code should not depend on the HTTP session.
- If the resolved language is not one of `getValidLanguages($domain)` (e.g. `de_AT` with only `de` configured), the
  method returns `null`, even with `$returnIdIfEmpty = true`, because that flag fills only the valid languages.
- `SystemSettingsConfig::get()['general']` without `?? []` emits a warning if the key is missing.

**Fix direction:** only resolve a language when `$language === null`. Get the user from the token storage only.
Return `$id` when `$returnIdIfEmpty` is set and the language has no row.

## [ ] B-115 – `TranslationEntriesDumper`: queue keyed by key only (domains overwrite each other); one failing save aborts the dump and keeps a poison queue

**File:** `lib/Translation/TranslationEntriesDumper.php:27-40`

- `self::$translations[$translation->getKey()]`: the same key missing in `messages` and in `admin` (or a custom domain)
  in one request keeps only the last one. The other domain never gets the entry.
- `dumpToDb()` saves in a loop and resets the queue only after the loop. If one save throws (key > 190 chars, B-110;
  table missing; permission exception after B-111), the rest of the queue is lost for this request. In long-running
  processes (worker runtimes) the static queue is kept, so the same failing entry is retried, and throws, on every
  later `kernel.terminate`.
- The exception is thrown from a `kernel.terminate` listener, after the response was sent. It is only visible in logs.

**Fix direction:** key by `domain . '|' . key`. Save each entry in its own `try/catch` (log and drop failures) and
clear the queue first. See CoreBundle I-52 for flushing in Messenger workers.

## [ ] B-116 – CSV import: shared fixed temp files (concurrent imports mix data), temp files never removed, silent no-ops, lossy comparisons

**File:** `models/Translation.php:386-496`

- Temp files are always `PIMCORE_SYSTEM_TEMP_DIRECTORY/import_translations` and `…_original`. Two imports at the same
  time (two admins, or two domains) overwrite each other's file between `dumpFile()` and `fopen()`. One import then
  writes **the other user's CSV into its own domain**. The files are never deleted, so the translation export remains
  in the temp directory.
- Both files have the same content; the second one is only used for dialect detection.
- `file_get_contents()` returning `false` is not checked (`TypeError` in `str_replace` under strict types).
- No `key` column (wrong delimiter guess, renamed header) → every row is skipped, nothing is reported, and `[]` is
  returned as if the file matched the DB.
- A row with more columns than the header raises "Undefined array key" warnings and maps values to key `''`.
  `str_replace('&quot;', '"', …)` changes texts that really contain the entity.
- Loose comparisons: `$currentTranslation != $value` treats `"1.0"` and `"1"`, or `"1e3"` and `"1000"`, as equal → the
  change is not imported. `if (!$currentTranslation)` treats `"0"` as empty → overwritten when
  `$replaceExistingTranslations` is `false`.
- `getByKey($textKey, $domain, true)` queues every **new** key for the dumper with empty texts, even if the row is not
  `$dirty` (e.g. only empty CSV values). The import then saves it (`$t->save()`) and the dumper saves it again at
  terminate.
- `trim($value, '﻿"')` uses a byte-wise character list that contains the three UTF-8 BOM bytes. Header names that end
  in one of those bytes (any non-ASCII header ending in U+xxBB/BF/EF) are cut.

**Fix direction:** read the uploaded file directly as a stream (no temp copy), or use `tempnam()` and remove the file
in `finally`. Fail on a missing `key` column. Use strict comparisons (`!==`, `=== ''`). See I-57.

## [ ] B-117 – Listing persistent cache key ignores bound condition values and `languages`: wrong results

**File:** `models/Translation/Listing/Dao.php:62-96`, `:107-133`

- `load()` and `getAllTranslations()` cache by `md5((string) $queryBuilder)`. That string contains the **placeholders**
  (`key LIKE ?`), not the values. The cache is skipped only when `addConditionParam()` was used. A listing built with
  `setCondition('`key` LIKE ?', ['%foo%'])` caches its result, and the next search with `['%bar%']` returns the
  **foo** results.
- `load()` also ignores `$this->model->getLanguages()` in the key. A listing for `['de']` and one for `['en', 'fr']`
  share the cache and return objects with the wrong set of languages.
- The cached value is an array of `Translation` objects. They carry the HTML sanitizer service (if
  `getTranslationSanitizer()` was called) and the event-recursion state (B-125) into the persistent cache.
- `Listing::$cacheLimit` / `Listing\Dao::isCacheable()` are never called in this repo, so the 5000-row limit has no
  effect, and huge domains are cached as one item.

**Fix direction:** include the bound values, types and languages in the cache key (or do not cache filtered listings
at all), and cache rows (arrays), not model objects. Either use `isCacheable()` or remove it.

## [ ] B-118 – Listing counts: `getTotalCount()` changes after `load()`, counts rows instead of keys, and does not match the loaded page

**File:** `models/Translation/Listing/Dao.php:38-60`, `:107-133`; `lib/Model/Listing/AbstractListing.php:467-470`

- `load()` calls `$this->model->setGroupBy(<table>.key)`, i.e. it **changes the listing**. Before `load()`,
  `getTotalCount()` / `count($listing)` counts **rows** (keys × languages). After `load()`, it counts **keys**. The same
  listing returns e.g. 600 before and 100 after.
- `load()` drops keys that have no row in the listing's `languages` (B-108), but the SQL count includes them.
- `getCount()` calls `load()` twice when the result is not empty (two full loads with N+1 queries without cache).

**Fix direction:** always count `DISTINCT key` (and apply the `languages` filter in SQL, I-58). Do not mutate the
model in `load()`; build the grouped query locally.

## [ ] B-119 – `Listing::isValidOrderKey()`: `TypeError` without languages, real columns rejected, `type` breaks under `ONLY_FULL_GROUP_BY`

**File:** `models/Translation/Listing.php:56-59`; `models/Translation/Listing/Dao.php:109-111`

- `in_array($key, $this->getLanguages())` with `languages = null` (the default) throws a `TypeError`. So
  `setOrderKey('creationDate')` crashes instead of throwing the documented `InvalidArgumentException`.
- Real columns (`creationDate`, `modificationDate`, `language`, `text`) are rejected. Language codes are accepted,
  but there is no column with that name unless the caller adds one through `onCreateQueryBuilder()` → "Unknown column".
- `load()` groups by `key` only. `ORDER BY type` (accepted) is not functionally dependent on `key` (the PK is
  `key, language`), so MySQL with the default `ONLY_FULL_GROUP_BY` fails.

**Fix direction:** `in_array($key, [...columns], true) || in_array($key, $this->languages ?? [], true)`; order by
`MIN(type)` / `MAX(modificationDate)` in the grouped query.

## [ ] B-120 – Domain checks: `isAValidDomain()` swallows DB errors; the `admin` table is missing on a fresh install; swapped message arguments

**Files:** `models/Translation/Dao.php:175-189`; `models/Translation/Listing.php:66-79`; `install.sql:432-444`

- `isAValidDomain()` returns `false` on **any** exception. During a DB outage, the Translator silently stops loading
  DB translations (frontend shows keys, and the empty result may be cached), and `Listing::setDomain()` throws
  "domain is not registered" – a misleading error.
- `install.sql` creates only `translations_messages`. `admin` is a default domain (`default.yaml:329-331`), but its table
  is created only by the first `save()` (B-112). Until then `isAValidDomain('admin')` is `false`: the Translator never
  records missing admin keys (`Translator.php:240`), and `new Listing()->setDomain('admin')` throws.
- The `NotFoundException` message swaps its arguments: it says domain `translations_<domain>` / table `<domain>`
  (`Listing.php:71-73`).

**Fix direction:** check the table with `createSchemaManager()->tablesExist()` (or `SHOW TABLES LIKE ?`) and let
connection errors propagate. Create tables for all configured domains at install/migration time (I-62). Fix the
message.

## [ ] B-121 – `Dao::getAvailableLanguages()` fails under `ONLY_FULL_GROUP_BY`; `getAvailableDomains()` uses `_` as a LIKE wildcard

**File:** `models/Translation/Dao.php:139-168`

- `SELECT * FROM … GROUP BY language` selects non-aggregated columns. MySQL ≥ 5.7 with the default `sql_mode` fails
  with "Expression #1 of SELECT list is not in GROUP BY clause". No caller in this repo; the admin bundle may use it.
- `SHOW TABLES LIKE 'translations_%'`: `_` matches any character (`translationsX…`). `str_replace('translations_', '', …)`
  removes **every** occurrence, not only the prefix. Both are harmless today only because `isAValidDomain()` filters
  the result afterwards.

**Fix direction:** `SELECT DISTINCT language`; `LIKE 'translations\_%'` and strip the prefix with `substr()`. Better:
derive available domains from the config plus a table-exists check.

## [ ] B-122 – `Listing::cleanup()` does not invalidate caches and fires no events

**File:** `models/Translation/Listing/Dao.php:146-160`

- The deleted keys stay in the cached `load()` / `getAllTranslations()` results (tag `translator`) and in the
  `RuntimeCache` until something else clears the tags. The admin grid keeps showing them.
- No `PRE_DELETE` / `POST_DELETE` events, so listeners that sync translations (search, external TMS) miss the deletes.
- The keys are put into one `IN (…)` list with `quote()`. Thousands of empty keys can exceed `max_allowed_packet`.

**Fix direction:** call `Translation::clearDependentCache()` after the delete; dispatch one bulk event (or document
that cleanup is silent); delete in chunks with bound parameters (I-56).

## [ ] B-123 – Translator auto-create: wrong language list for non-default domains; the 190 check counts bytes and throws during rendering

**File:** `lib/Translation/Translator.php:240-267`

- A new key gets `Translation::getValidLanguages()` **without** the domain (`:261`), i.e. website languages. For the
  `admin` domain (and any domain whose languages differ) rows are created for the wrong languages, and the admin
  languages stay missing.
- `strlen($id) > 190` counts **bytes**; the column is `varchar(190)` in **characters** (utf8mb4). Valid keys with
  non-ASCII characters are rejected although they fit (100 Cyrillic characters = 200 bytes).
- The check **throws** `Exception` from `trans()`. One long text used as a key in a template turns the page into a
  500, but only while the key is missing from the catalogue, so it depends on DB state.
- `Translation::IsAValidLanguage()` uses loose `in_array()`.

**Fix direction:** `getValidLanguages($domain)`; `mb_strlen()`; log and return the id instead of throwing.

## [ ] B-124 – Translator DB catalogues never refresh in long-running processes (Messenger workers, worker runtimes)

**File:** `lib/Translation/Translator.php:138-212`

- `lazyInitialize()` adds the DB catalogue to the decorated translator's in-memory catalogue once per process and
  remembers it in `$initializedCatalogues`. `resetCache()` / `resetInitialization()` exist, but nothing calls them, and
  the class does not implement `ResetInterface`.
- After an editor changes a translation, the persistent cache is cleared (`clearDependentCache()`), but a Messenger
  worker (mails, PDFs, exports rendered in the worker) or a FrankenPHP/RoadRunner worker keeps the old texts until it
  is restarted. Keys added to the catalogue at runtime (`:271`, `:290`) also stay.
- Same pattern as C-15 (website settings) and CoreBundle C-08.

**Fix direction:** implement `ResetInterface` (tag `kernel.reset`) and reset the in-memory catalogues. Messenger
already calls `kernel.reset` between messages via `ResetServicesListener`.

## [ ] B-125 – Event recursion guard stays set after a listener exception: later saves of the same instance skip events silently

**File:** `lib/Event/Traits/RecursionBlockingEventDispatchHelperTrait.php:34-42`; `models/Translation.php:354-373`

- `dispatchEvent()` sets `$activeDispatchingEvents[$name] = true`, dispatches, then unsets it, with no `try/finally`. If
  a `PRE_SAVE` listener throws, the flag stays set on the object.
- Translations are shared through `RuntimeCache` (B-107). A caller that catches the exception and saves again (import
  loop, retry) saves **without** `PRE_SAVE` (validation, normalisation listeners) and `POST_SAVE` events, without any
  error.
- The flag is a model property, so it is also stored in the persistent listing cache (B-117).

**Fix direction:** `try { … } finally { unset(...) }` in the trait (shared helper, also affects other models that use it).
