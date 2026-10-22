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
**(6)** `models/DataObject.php`, `models/Document.php`, `models/Document/**` (Dao, Service, PageSnippet, Page, Snippet,
Email, Link, Hardlink/**, Folder, DocType/**, Listing/**, Editable/**), `models/Element/**` (AbstractElement, Dao,
Service, Recyclebin/**, Editlock/**, Note/**, Tag/**, WorkflowState/**, PermissionChecker, AdminStyle, DeepCopy/**,
Traits/**, Data/**) and `models/DataObject/**` (AbstractObject/**, Concrete/** incl. InheritanceHelper,
Localizedfield/**, Fieldcollection/**, Objectbrick/**, Classificationstore/**, ClassDefinition/** incl. Data/**,
Data/**, Listing/**, QuantityValue/**, SelectOptions/**, Service), plus the direct consumers where a defect
originates in the model interplay (`bundles/CoreBundle/config/documents.yaml:49-50`,
`lib/Routing/Dynamic/DataObjectRouteHandler.php:91`) and the related schema
(`bundles/InstallBundle/dump/install.sql` – `documents*`, `edit_lock`, `tags`, `recyclebin`)
**(7)** `models/Metadata/**` (Predefined, Predefined/Dao, Predefined/Listing, Predefined/Listing/Dao), `models/Notification.php`
and `models/Notification/**` (Dao, Listing, Listing/Dao, Service/NotificationService, Service/NotificationServiceFilterParser,
Service/UserService), plus the direct consumers where a defect originates in the model interplay
(`lib/Workflow/Notification/PimcoreNotificationService.php`, `lib/Workflow/Manager.php::applyWithAdditionalData()`
(`:222-250`), `models/Asset/Service.php::expandMetadataForEditmode()` (`:291-316`),
`lib/Model/Dao/PimcoreLocationAwareConfigDao.php`, the `assets.metadata.predefined` config node
(`bundles/CoreBundle/src/DependencyInjection/Configuration.php:667-695`)), the `notifications` schema
(`bundles/InstallBundle/dump/install.sql:801-817`, migration `Version20240813085200`) and
`tests/Unit/Notification/Service/NotificationServiceTest.php`
**(8)** `models/Property.php`, `models/Property/**` (Dao, Predefined, Predefined/Dao, Predefined/Listing,
Predefined/Listing/Dao) and `models/Schedule/**` (Task, Task/Dao, Task/Listing, Task/Listing/Dao), plus the direct
consumers where a defect originates in the model interplay (`lib/Maintenance/Tasks/ScheduledTasksTask.php`,
`lib/Maintenance/Executor.php::executeTask()` (`:48-70`), `models/Element/Traits/ScheduledTasksTrait.php`,
`models/Element/Traits/ScheduledTasksDaoTrait.php`, the `getProperties()` hydration in `Asset/Dao.php`,
`DataObject/AbstractObject/Dao.php` and `Document/Dao.php`, `models/Element/Service.php::minimizePropertiesForEditmode()`
(`:516-560`), `models/Element/Recyclebin/Item.php`, the `properties.predefined` config node
(`bundles/CoreBundle/src/DependencyInjection/Configuration.php:1844-1873`)), the `properties` and `schedule_tasks`
schemas (`bundles/InstallBundle/dump/install.sql:300-310`, `:326-340`) and `tests/Support/Test/AbstractPropertiesTest.php`
**(9)** `models/Version.php` and `models/Version/**` (Dao, Listing, Listing/Dao, SetDumpStateFilter,
Adapter/VersionStorageAdapterInterface, Adapter/FileSystemVersionStorageAdapter, Adapter/DatabaseVersionStorageAdapter,
Adapter/DelegateVersionStorageAdapter, Adapter/ProxyVersionStorageAdapter), plus the direct consumers where a defect
originates in the model interplay (`lib/Maintenance/Tasks/VersionsCleanupTask.php`, `lib/Messenger/Handler/VersionDeleteHandler.php`,
`lib/Messenger/Handler/SanityCheckHandler.php::performSanityCheck()` (`:69-95`),
`models/Element/AbstractElement.php::doSaveVersion()` (`:573-611`), `models/Asset.php::saveVersion()` (`:839-890`), the
`Version::disable()` / `enable()` callers, `lib/Helper/LongRunningHelper.php::cleanUp()`), the `versions` schema
(`bundles/InstallBundle/dump/install.sql:576-600`), the `versionsData` table from
`doc/18_Tools_and_Features/01_Versioning.md:79-87` and `tests/Service/Element/VersionTest.php`
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
its root page is renamed or moved** and **site definitions permanently lost when the root page is deleted**, and in
Scope 6 **document / object / tag tree paths corrupted on move**, **open DB transactions after an `Error` in document
and object saves**, **frontend 500s from scheduled blocks**, **a path-less nearest-document cache serving the wrong
document**, **encrypted data and URL slugs silently destroyed**, **code / SQL injection through class definitions**
and **other classes' tables dropped on class delete**, and in Scope 7 **element / workflow writes silently lost
after a notification error leaves a DB transaction open** and **SQL injection in the notification filter exposing every
user's notifications**, and in Scope 8 **all scheduled publishing / unpublishing / deletion stalled by one
failing task** and **scheduled tasks executed without a permission check (deleted users, versions of other
elements)**, and in Scope 9 **asset versions unreadable and their shared binaries deleted with the database version
storage** and **asset version binaries silently replaced by a 1-byte placeholder after an aborted write**.
IDs are stable – refer to them as `C-xx` in later sessions. Line numbers are from commit `1b0711d927` (Scope 2:
`7f0331cab9`, Scope 3: `2d382456c7`, Scope 4: `3e2bf1c4d8`, Scope 5: `0b659e9ce0`, Scope 6: `990568d287`,
Scope 7: `7d51be3297`, Scope 8: `d527536468`, Scope 9: `718bf5020c`).

> Notes
> - `models/Asset.php`, `models/Asset/Dao.php`, `Asset/Listing/Dao.php`, `Asset/Image/Thumbnail.php` and both thumbnail
>   `Config/Dao.php` / `Config/Listing/Dao.php` files have **no** `declare(strict_types=1)`. Scalar coercion applies there,
>   but `null` to a non-nullable parameter still throws `TypeError`. The rest of `models/Asset/**` is strict.
> - No hard Symfony 7.4 / PHP 8.4 incompatibility (removed API, implicit nullable parameter) was found. The remaining
>   compatibility work is modernisation and is listed in TODO-3.
> - Scope 6: 65 files have **no** `declare(strict_types=1)` – nearly all DAOs, plus `Document/Editable/Areablock.php`,
>   `DataObject/ClassDefinition/Data.php`, `ClassDefinition/Data/Input.php`, `Consent.php`, `QuantityValue/Unit.php`
>   (I-73). No implicit nullable parameter was found; DBAL 4 blockers (`QueryBuilder::getQueryPart()` / `add()`) are in
>   I-76. The project pins `doctrine/dbal: ^3.8`, so they are not critical today.

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
- `Document/Dao.php:214` and `DataObject/AbstractObject/Dao.php:173` use the same pattern (Scope 6: **C-25**).

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

---

## Document / Element / DataObject (Scope 6)

## [ ] C-25 – Document and object move/rename rewrites every occurrence of the old path in DB (children, workspaces, and *other element types'* properties)

**Files:** `models/Document/Dao.php:196-217` (`:208`, `:211`, `:214`); `models/DataObject/AbstractObject/Dao.php:154-179`
(`:167`, `:170`, `:173`); callers `models/Document.php:328-341`, `models/DataObject/AbstractObject.php:540-548`

- Same pattern as C-01: `` `path` = replace(`path`, '<old>/', '<new>/') `` with a `WHERE … LIKE '<old>/%'`. MySQL
  `REPLACE()` replaces **all** occurrences; the `WHERE` only selects the rows.
  - Renaming document `/en` → `/de` turns `/en/products/en/teaser` into `/de/products/de/teaser`. The child row now
    points to a parent path that does not exist: `getByPath()` fails, the `(path, key)` unique index can collide, and
    the next save of the child (`correctPath()` re-reads the parent path) moves it somewhere else again.
  - Same for objects (`/products/shoes/products/x`).
- `users_workspaces_document.cpath` / `users_workspaces_object.cpath` (`:211`, `:170`) get the same corruption →
  permissions move to the wrong subtree.
- `properties` (`:214`, `:173`) has no `AND ctype = 'document'` / `'object'`: moving document folder `/products`
  rewrites the `cpath` of **asset and object** properties below `/products/…` (and with `utf8_general_ci`, below
  `/Products/…`). Property inheritance (sorted by `cpath`) silently changes for the other element types.
- C-01 already noted these two places as "outside scope, same fix"; they are tracked here.

**Fix direction:** one helper in `Element\Dao` used by all three element types:
`` SET `path` = CONCAT(:new, SUBSTRING(`path`, CHAR_LENGTH(:old) + 1)) WHERE `path` LIKE :oldLike `` with bound
parameters; the same for both `cpath` updates, plus `AND ctype = :ctype` on `properties`.

---

## [ ] C-26 – Document / DataObject `save()` and `delete()` catch only `Exception`: an `Error` leaves the DB transaction open (and global flags changed) in workers

**Files:** `models/Document.php:310-366` (inner retry `catch (Exception $e)` at `:346`), `:399`, `delete()` `:648-690`
(`:672`); `models/DataObject/AbstractObject.php:518-590` (`:557`), `:626`, `delete()` `:448-492` (`:468`);
`models/Document/PageSnippet.php:208`; `models/DataObject/Concrete.php:235-286` (`:277`)

- Same defect as C-05 (Asset). Any `\Error` between `beginTransaction()` and `commit()` skips `rollBack()` and the
  `POST_*_FAILURE` events. Reachable `Error`s inside the transaction (the files are `strict_types`):
  - `Classificationstore\Dao::save()`: `reset([])->getKeyId()` for an empty group, `DefinitionCache::get()` `null` for a
    deleted key (B-181);
  - `InheritanceHelper::doUpdate()`: `$result['id']` on `false`, `Concrete::getById()` `null` (B-186);
  - `PageSnippet::update()` → `Editable::save()` / `getDataForResource()` with typed-property `TypeError`s (B-163);
  - listeners of version events (`saveVersion()` runs inside the transaction).
- `AbstractObject::save()` additionally sets `hideUnpublished = false` before `beginTransaction()` (`:520-521`) and
  restores it only on success and in `catch (Exception)` (`:552`, `:566`). After an `Error` the flag stays `false`
  for the process: frontend listings and relation getters start returning unpublished objects. Dirty detection stays
  disabled for new objects as well (B-175).
- In PHP-FPM the connection is reset at request end. In Messenger workers / FrankenPHP / RoadRunner the connection is
  long-lived: every later `beginTransaction()` nests inside the dead transaction and **nothing is committed again**
  until the worker restarts (silent data loss).
- `Document::delete()` calls `$this->rollBack()` unguarded in its `catch` (`:673`): a failing rollback replaces the
  original exception (AbstractObject guards it).

**Fix direction:** catch `\Throwable` in the inner retry loop and in the outer handlers of `save()`, `delete()` and
`saveVersion()` (rethrow after rollback + failure event); restore `hideUnpublished` / dirty detection in `finally`.

---

## [ ] C-27 – `Document\Service::getNearestDocumentByPath()` caches its result without the path: the shared service returns the first document for every later lookup

**Files:** `models/Document/Service.php:461-526` (`:467`, `:470-471`, `:488`, `:502`); service definition
`bundles/CoreBundle/config/documents.yaml:49-50` (shared, public, no reset); callers
`bundles/CoreBundle/src/EventListener/Frontend/DocumentFallbackListener.php:109`,
`bundles/CoreBundle/src/EventListener/ResponseExceptionListener.php:147`,
`lib/Routing/Dynamic/DocumentRouteHandler.php:138`

```php
$cacheKey = $ignoreHardlinks . implode('-', $types);   // path and site are not part of the key
if (isset($this->nearestPathCache[$cacheKey])) { $document = $this->nearestPathCache[$cacheKey]; }
```

- `nearestPathCache` lives on the container service. The key contains only the hardlink flag and the type list.
- PHP-FPM: within one request every further call with a different path (sub-requests, hardlink resolution in
  `DocumentRouteHandler`, the 404 handler after a fallback lookup for another path) gets the first result.
- Worker runtimes (FrankenPHP, RoadRunner, Swoole): the container survives the request, so **every following request**
  gets the nearest document of the first request. `DocumentFallbackListener` sets `_locale`, the content document and
  the site context from it → wrong language, wrong site, wrong error page and wrong hardlink target for all visitors
  until the worker restarts.
- A result found through the site pretty-URL branch (`:492-505`) is reused for requests of other sites.

**Fix direction:** key by `[path, site id, ignoreHardlinks, types]` (small bounded array) or drop the cache;
implement `ResetInterface` and tag the service with `kernel.reset`.

---

## [ ] C-28 – Scheduled block: frontend 500 as soon as no entry lies in the future (`updateOutputCacheLifetime()` receives `null`)

**File:** `models/Document/Editable/Scheduledblock.php:70-122` (`:83`, `:87-89`, `:95`, `:110`)

```php
$nextElement = null;
foreach ($this->indices as $element) { if ($element['date'] <= $outputTimestamp) { $currentElement = $element; } elseif (empty($nextElement)) { $nextElement = $element; } … }
$this->updateOutputCacheLifetime($outputTimestamp, $nextElement);   // private function …(int $outputTimestamp, array $nextElement)
```

- The file is `strict_types`. When every entry date is in the past, `$nextElement` stays `null` → `TypeError`.
- This is the normal state of every scheduled block once its last scheduled change has passed. A new block gets one
  default entry with `date => time()` (`setDefault()`, `:58-68`), which is in the past on the next request.
- The call happens before the full-page-cache check, so it fails with or without output cache. Editmode returns all
  entries (`:72-73`) and never reaches the call, so editors do not see the error; only frontend visitors do (Twig
  `pimcore_iterate_block` → page 500).
- Logic error behind it: "no future entry" must leave the cache lifetime alone; the old untyped code computed a negative
  lifetime from `null['date']`.

**Fix direction:** `?array $nextElement`, return early for `null`, only lower the lifetime for a positive value.

---

## [ ] C-29 – Moving a root-level tag corrupts the `idPath` of its whole subtree

**Files:** `models/Element/Tag/Dao.php:51-87` (`:62-65`, `:75-77`); `models/Element/Tag.php:238-245` (`setParentId()`),
`:325-339` (`correctPath()`)

```php
// tag 5 at root: idPath '/', child 7: '/5/'. Move 5 under 3 → new idPath '/3/'
UPDATE tags SET idPath = REPLACE(idPath, '/', '/3/') WHERE idPath LIKE '/5/%'   -- '/5/' → '/3/5/3/', '/5/7/' → '/3/5/3/7/3/'
```

- The original `idPath` of a top-level tag is `'/'` (truthy), so `REPLACE()` rewrites **every slash** of every
  descendant path. Deeper moves are only safe by accident (ids are unique path segments).
- Consequences: descendants get paths that do not match their parents. As soon as any tag in the chain is saved again
  (rename, move), `correctPath()` recomputes the correct prefix, the subtree `REPLACE`/`LIKE` no longer matches, and:
  - `getElementsForTag(…, considerChildTags: true)` misses child tags and their elements;
  - `Tag::delete()` (`LIKE '<fullIdPath>%'`, `:99`) leaves orphaned child tags and their `tags_assignment` rows;
  - `idPath` grows three times per move → `varchar(190)` overflow → save errors in deeper trees; the
    `(idPath, name)` unique index can collide.
- Dragging a root tag under another tag is a normal admin action.

**Fix direction:** anchored `CONCAT(:new, SUBSTRING(idPath, CHAR_LENGTH(:old) + 1))` with bound parameters; a repair
migration that recomputes every `idPath` from `parentId`.

---

## [ ] C-30 – Encrypted fields: a decryption failure loads `null`, and the next save overwrites the ciphertext

**Files:** `models/DataObject/ClassDefinition/Data/EncryptedField.php:112-150` (`:119-125`, `:139-144`), `:66-86`,
strict-mode default `:42-50`, `:315-323`; write path `models/DataObject/Concrete/Dao.php:230-259`

- In the default non-strict mode a key that cannot be loaded (missing / rotated / different
  `pimcore.encryption.secret`, e.g. after a deploy or on a copied database) and any `Crypto::decrypt()` failure only log
  and return `null`.
- The object loads with the field empty. Store-table columns are written on **every** save (they are never
  "untouchable"), so the next save by an editor, workflow, import, scheduled task or maintenance job writes
  `getDataForResource(null)` → `NULL`: the encrypted value is destroyed permanently, with only a log line.
- Same in localized fields, bricks and field collections.

**Fix direction:** never turn a failed decryption into `null`. Return a value object that keeps the raw ciphertext and
is written back unchanged (or skip the column on save), and refuse edits of that field; consider making strict mode
the default.

---

## [ ] C-31 – Frontend slug resolution deletes slug rows when the field definition lookup fails; the field-collection branch always inspects item 0

**Files:** `models/DataObject/Data/UrlSlug.php:249-346` (`:318-336`, `:340-344`); callers
`lib/Routing/Dynamic/DataObjectRouteHandler.php:91`, `models/DataObject/ClassDefinition/Data/UrlSlug.php:207-212`

- `getAction()` runs on every slug route match (anonymous public requests). If it cannot find a `UrlSlug` field
  definition it calls `$this->delete()` and throws.
- Field collections: the slug row stores the item index in `position` (`ClassDefinition/Data/UrlSlug.php:275`), but
  `getAction()` reads `$fcValue->get(0)` (`:330`). In a collection with mixed types, where item 0 is a type without
  this slug field, **the first request for the slug of item n deletes it** → the URL answers 404 for good, until the
  object is saved again.
- `ClassDefinition::getById()` returns `null` for any include error of the definition file (B-185): during a deploy
  window or a broken definition every requested slug of that class is deleted.
- `ClassDefinition\Data\UrlSlug::checkValidity()` calls `getAction()` for an existing slug while saving another
  object (`:207-212`), so the same deletion happens from admin saves.
- Deleting data from a GET handler is also racy (two requests, cache entries keep the deleted slug, `:345`).

**Fix direction:** resolve with `get((int)$this->getPosition())`; never delete during request handling (log and
answer 404); move the cleanup to class save / a maintenance task; cache the resolved action per
`(classId, ownertype, ownername, fieldname)`.

---

## [ ] C-32 – Class names, class ids, field names and select-option ids are not validated with anchored patterns: they reach generated PHP code, file paths and DDL

**Files:** `models/DataObject/ClassDefinition.php:331-367` (`:350`, `:355`, `:359`), `getDefinitionFile()` `:576-579`,
`getPhpClassFile()` `:584-587`; `models/DataObject/ClassDefinition/Data.php:1297-1312` (`isForbiddenName()`, no
character check); `models/DataObject/ClassDefinition/Dao.php:96-222` (name and id inside DDL strings, `:117-118`);
`models/DataObject/ClassDefinition/Helper/Dao.php:25-110` (field names in backticks, not escaped);
`models/DataObject/SelectOptions/Config.php:78-91`, `:372-375`; generators in `lib/DataObject/ClassBuilder/**`

```php
if (!preg_match('/[a-zA-Z]\w+/', $this->getName())) …                 // matches anywhere in the string
if (!preg_match('/[a-zA-Z0-9]([a-zA-Z0-9_]+)?/', $this->getId())) …     // same
```

- `Foo-Bar`, `../../public/ab`, `X extends Concrete {} … class Y` all pass.
  - The name becomes the PHP file path `DataObject/<Name>.php` / `definition_<name>.php` (path traversal → a PHP file
    written outside `var/classes`, e.g. into the web root) and the class name in generated code (code injection, or a
    syntax error that fatals every request autoloading the class).
  - The id becomes the table names `object_store_<id>`, `object_query_<id>`, `object_relations_<id>` that are
    concatenated into DDL and DML across the model (`ClassDefinition/Dao.php:104-150`, InheritanceHelper, listings), and is written as
    `DEFAULT '<id>'` / `DEFAULT '<name>'` into DDL → SQL injection / broken schema.
- Field names are only checked against the forbidden-name list: any character is accepted and ends up in generated
  getters/setters (`get<Name>`) and in backtick-quoted column names without escaping (a backtick breaks out).
- Select-options ids are only checked against reserved words and become enum class names and file names.
- Field-collection and object-brick keys are already validated with `^[a-zA-Z]\w*$`
  (`Fieldcollection/Definition.php:308`, inherited by bricks); the gap is classes, fields and select options.
- Who can trigger it: users with the `classes` permission (not necessarily admins), definition imports
  (`pimcore:definition:import:class`, admin JSON import), custom tooling. Validation in the admin UI bundle was not
  checked here; the model accepts everything.

**Fix direction:** anchored patterns (`^[A-Za-z][A-Za-z0-9_]*$` plus length limits and PHP reserved words) for class
name, class id, field names and select-option ids, checked in the setters and in `save()`; quote identifiers with
`quoteIdentifier()` everywhere; generate code with a code generator instead of string concatenation (I-83).

---

## [ ] C-33 – Deleting a class drops tables of other classes: unescaped `SHOW TABLES LIKE` patterns

**File:** `models/DataObject/ClassDefinition/Dao.php:240-296` (`:260`, `:267`, `:273`, `:282`)

```php
"SHOW TABLES LIKE 'object\_collection\_%\_" . $id . "'"      // suffix match
"SHOW TABLES LIKE 'object\_brick\_%\_" . $id . "'"           // suffix match
"SHOW TABLES LIKE 'object\_localized\_query\_" . $id . "\_%'" // prefix match
```

- The class id is not escaped (an `_` in it is a wildcard) and `%` spans `_`. Custom ids (`[A-Za-z0-9_]` is allowed):
  - deleting class `archive` drops `object_brick_store_<key>_news_archive` and `object_collection_<key>_news_archive`,
    i.e. the brick and field-collection **store** tables of class `news_archive` → data loss;
  - deleting class `news` drops `object_localized_query_news_archive_<lang>` and the `object_localized_news_archive_*`
    views.
- The numeric default ids are not affected; projects with readable class ids are.

**Fix direction:** build the exact table names from the known brick / field-collection keys and valid languages (or
query `information_schema.tables` with escaped `_` / `%` and the exact `_<key>_<id>` structure); better, track the
tables owned by a class.

---

# Scope 7 – Metadata / Notification

> Notes
> - `models/Notification.php`, `Notification/Listing.php` and `Notification/Service/**` are `strict_types`;
>   `Notification/Dao.php`, `Notification/Listing/Dao.php`, `Metadata/Predefined/Dao.php` and
>   `Metadata/Predefined/Listing/Dao.php` are not (I-86). `Metadata/Predefined.php` and `Predefined/Listing.php` are strict.
> - No hard Symfony 7.4 / PHP 8.4 incompatibility was found (no implicit nullable parameters, no removed APIs; Carbon is
>   `^3.0`, DBAL `^3.8`). Modernisation is in TODO-3 (I-86–I-92).
> - The admin controllers (`NotificationController`, predefined-metadata settings) live in
>   `pimcore/admin-ui-classic-bundle`, which is not in this repo (`vendor/` is not installed). Statements about how they
>   call the services are based on the upstream bundle source and were **not verified locally**.
> - `bundles/CoreBundle/config/pimcore/default.yaml:148` sets `sql_mode = ''`: over-long values are truncated silently
>   instead of failing (relevant for B-199).
> - No Scope 7 metadata item is critical. The predefined-metadata id goes into a YAML file path like the thumbnail
>   config name in C-09; it is only reachable through code and imports (the admin creates UUIDs), so it is listed as B-191
>   and covered by the repository-level fix of C-09.

---

## [ ] C-34 – `NotificationService` opens DB transactions and never rolls them back: after any error, later writes in the same request / worker are silently lost

**Files:** `models/Notification/Service/NotificationService.php:59-84` (`sendToUser()`), `:155-171`
(`findAndMarkAsRead()`), `:216-223` (`findAll()`), `:245-252` (`findLastUnread()`), `:303-314` (`delete()`), `:324-330`
(`deleteAll()`), `:336-347`; `lib/Workflow/Notification/PimcoreNotificationService.php:72-78`;
`lib/Workflow/Manager.php:232-243`

```php
$this->beginTransaction();
$sender = User::getById($fromUser);
$recipient = User::getById($userId);
if (!$recipient instanceof User) {
    throw new UnexpectedValueException(...);   // transaction stays open
}
```

- Every public method calls `Db::getConnection()->beginTransaction()` and `commit()` with no `try` / `rollBack()`. Exits
  that skip `commit()`:
  - `sendToUser()`: unknown recipient, empty title or message (validated **after** the transaction is opened), any DB
    error, any exception from a `NotificationEvents::PRE_SAVE` / `POST_SAVE` listener;
  - `findAndMarkAsRead()`: unknown id (`find()` throws), foreign notification (`AccessDeniedHttpException`), and without an
    exception when `$recipientId` is `0` (the second `if` is falsy, nothing commits);
  - `delete()`: unknown id; `findAll()` / `findLastUnread()` / `deleteAll()`: DB or hydration errors (B-193).
- The shared DBAL connection then stays at nesting level ≥ 1. Later `beginTransaction()` / `commit()` pairs (element
  saves, versions, notes, dependencies) only change the nesting level, so nothing is committed. When the request ends
  the connection closes and MySQL **rolls back everything written after the failure**, although every save reported
  success.
- Concrete path: a workflow transition with `notificationSettings` → `Manager::applyWithAdditionalData()` →
  `$workflow->apply()` → `NotificationSubscriber` → `PimcoreNotificationService::sendPimcoreNotification()` →
  `sendToUser()` throws → caught and only logged (`PimcoreNotificationService.php:76-78`) → `$subject->save()`
  (`Manager.php:243`) runs inside the orphaned transaction → the element data, new workflow place, version and note are
  lost, and the UI shows success.
- Worker runtimes (CoreBundle C-08) and long CLI processes keep the connection: every later request in that process
  writes into the never-committed transaction and holds row locks, so other processes hit lock wait timeouts. When
  the process ends, all of it is rolled back. A single "mark as read" on a notification that was already deleted
  elsewhere (not found) is enough to trigger it from the admin UI.
- `tests/Unit/Notification/Service/NotificationServiceTest.php:53-66` (`testSendToNoExistUser`) leaves the transaction
  open, so the following tests in the same process run inside it (B-200).
- The transactions protect nothing: each method writes a single row (or deletes rows one by one). Only `findAll()` gains
  a consistent `count` / `select` snapshot.

**Fix direction:** remove the transactions from single-statement operations; where one is needed use
`$db->transactional(fn () => …)` (it rolls back on any `Throwable`); validate before opening a transaction; never catch
and continue around a failed transaction (`PimcoreNotificationService`). Add a test that asserts
`getTransactionNestingLevel() === 0` after every failing service call (I-92).

---

## [ ] C-35 – Notification filter: the request `property` (and `findAll()` filter keys) go into SQL unvalidated (SQL injection, every user's notifications readable)

**Files:** `models/Notification/Service/NotificationServiceFilterParser.php:66-96`, `:103-123`, `:130-174`, `:176-181`;
`models/Notification/Service/NotificationService.php:181-200`

```php
$property = $this->properties[$property] ?? $property;          // raw request value
"$property LIKE :$key"                                           // $key = $property . '_like'
$condition = implode(' AND ', $conditions);                      // findAll(): no parentheses
```

- `getDbProperty()` maps `title` and `timestamp` and returns every other value unchanged. That value becomes both the
  SQL expression and the placeholder name in `parseString()` / `parseDate()` (`LIKE`, `BETWEEN`, `>`, `<`).
  `findAll()` joins all conditions with a bare `' AND '` and, in the `else` branch, writes the filter keys unquoted
  (`$key . ' = :' . $key`).
- The parser reads the raw admin request (`$request->request->get('filter')`). Upstream admin-ui-classic
  `NotificationController::findAllAction()` merges `parse()` into `['recipient' => <current user id>]` and passes it to
  `findAll()` (not verified locally, see notes).
- Example: `filter=[{"type":"string","operator":"like","property":"isStudio OR 1=1 OR @a","value":"x"}]` produces
  `isStudio OR 1=1 OR @a LIKE :isStudio OR 1=1 OR @a_like`. The only placeholder, `:isStudio`, is bound by `findAll()`'s
  own `isStudio` filter; the unused named parameter is ignored (DBAL 3 expands only the placeholders found in the SQL –
  not verified locally, `vendor/` missing); `@a` / `@a_like` are MySQL user variables. Because `AND` binds tighter than
  `OR`, the `WHERE` is true for every row: the caller gets **every user's notifications** (title, message, payload,
  linked elements). With a subquery instead of `1=1`, it becomes a boolean-blind read of the whole database
  (for example `users.password`).
- Any other non-identifier property causes an SQL error (500). Who can trigger it: every backend user with the
  `notifications` permission.

**Fix direction:** allowlist the filterable properties (`title`, `timestamp` → `creationDate`, …) and reject everything
else with a 400; use fixed placeholder names; quote identifiers; wrap each condition in parentheses; replace the
`condition` strings with a typed filter object that the DAO turns into SQL (I-88). Related: B-195 (`findAll()` keys),
B-196 (parser robustness).

---

# Scope 8 – Property / Schedule

> Notes
> - `models/Property.php`, `Property/Predefined.php`, `Property/Predefined/Listing.php`, `Schedule/Task.php` and
>   `Schedule/Task/Listing.php` are `strict_types`; `Property/Dao.php`, `Property/Predefined/Dao.php`,
>   `Property/Predefined/Listing/Dao.php`, `Schedule/Task/Dao.php` and `Schedule/Task/Listing/Dao.php` are not (I-93).
>   `lib/Maintenance/Tasks/ScheduledTasksTask.php` is strict, so `null` to an `int` parameter throws there.
> - No hard Symfony 7.4 / PHP 8.4 incompatibility was found (no implicit nullable parameters, no removed APIs;
>   `Predefined\Dao` already uses a PHP 8.3 typed class constant). Modernisation is in TODO-3 (I-93–I-100).
> - The admin controllers that create scheduled tasks and predefined properties (`ElementControllerBase` /
>   `DocumentController` task saving, `SettingsController` predefined properties) live in `pimcore/admin-ui-classic-bundle`,
>   which is not in this repo (`vendor/` is not installed). Statements about client-supplied task data are based on
>   the upstream bundle source and were **not verified locally**.
> - Maintenance runs in the CLI (`pimcore:maintenance` cron and the `pimcore_scheduled_tasks` Messenger consumer,
>   `bundles/CoreBundle/config/pimcore/default.yaml:50`), so `php_sapi_name() === 'cli'` is always true for
>   `ScheduledTasksTask` (relevant for C-37).
> - `bundles/CoreBundle/config/pimcore/default.yaml:148` sets `sql_mode = ''`: invalid enum values (`properties.type`,
>   `schedule_tasks.action`) are stored as `''` and over-long names are truncated silently (B-201, B-205, B-211).
> - Already covered elsewhere and not repeated: C-25 (move rewrites `properties.cpath` of other element types), B-165
>   (properties of unsaved elements cached under a shared key), B-182 (`copyContents()` moves the source's scheduled
>   tasks), I-75 (`properties.data` PHP serialization), I-76 (`ScheduledTasksDaoTrait::deleteAllTasks()` concatenated SQL).

---

## [ ] C-36 – One failing scheduled task stalls all scheduled publishing, unpublishing and deletion, permanently

**Files:** `lib/Maintenance/Tasks/ScheduledTasksTask.php:43-135` (`:45-47`, `:50`, `:52`, `:74-76`, `:130`);
`lib/Maintenance/Executor.php:48-70` (`:65`); `models/Schedule/Task/Listing/Dao.php:33-45` (`:39`);
`models/Schedule/Task.php:47`, `:190-193`

```php
foreach ($tasks as $task) {
    $taskUser = User::getById($task->getUserId());   // outside the try, strict_types
    try {
        ...
    } catch (Exception $e) {                         // \Error / \TypeError not caught
```

- `User::getById(int $id)` receives `$task->getUserId()`, which is `?int` (column `userId` nullable, default `NULL`).
  A task created through the API without `setUserId()` (imports, custom code, `new Task([...])`) gives a `TypeError`
  **before** the `try`. It leaves `execute()`, and `Executor::executeTask()` catches only `Exception` (`:65`), so the
  Messenger message fails, is retried and ends in the failed transport. Nothing is written to the admin.
- `Task\Listing\Dao::load()` runs `SELECT id` and then `Task::getById()` per row. A row deleted in between (an editor
  saves the element and `deleteAllTasks()` removes the task, or the element is deleted) puts `null` into the list →
  `$task->getUserId()` on `null` → `Error`, same exit.
- Inside the `try`, only `Exception` is caught. A `TypeError` / `Error` from `save()` / `delete()` (event listeners,
  typed-property errors while restoring version data, B-163) aborts the loop the same way.
- The listing has no `ORDER BY`, so the same broken task is reached first in every run (every maintenance interval,
  default every 5 minutes). Every task behind it – timed publication, embargo unpublishing, "delete at" for legal
  takedowns – **never runs**, and the editor sees the tasks still listed as pending.
- A task that fails with an `Exception` stays `active = 1` and is retried in every run forever. For `delete`,
  `Recyclebin\Item::create()` (`:75`, `:95`, `:122`) runs **before** `delete()`: every failed run writes another full
  recycle-bin dump of the element tree (DB row + storage file), so the recycle bin and its storage grow without bound.

**Fix direction:** resolve the user inside a per-task `try { … } catch (\Throwable)`; skip `null` list entries; record
failures (attempt counter or `lastError` column, deactivate after N attempts, log with task id and element);
`ORDER BY date, id`; create the recycle-bin item and delete the element in one transaction; dispatch one Messenger
message per due task so one failure cannot block the queue (I-98). Related: C-37, B-210, B-213.

---

## [ ] C-37 – Scheduled tasks run without a permission check for deleted users, and `publish-version` saves any version id (other elements overwritten)

**Files:** `lib/Maintenance/Tasks/ScheduledTasksTask.php:50-126` (`:50`, `:56-58`, `:83-85`, `:103-105`);
`models/Element/AbstractElement.php:480-492`; `models/User/AbstractUser.php:44-48`;
`models/Schedule/Task.php:43`, `:110-113`, `:168-173`

```php
$taskUser = User::getById($task->getUserId());   // null for a deleted user
$document->isAllowed('publish', $taskUser)       // null → Admin::getCurrentUser() → null → CLI → true
if ($version = Version::getById($task->getVersion())) {
    $document = $version->getData();              // any element of that type
    $document->setPublished(true);
    $document->save();
```

- `isAllowed($type, null)` falls back to `Admin::getCurrentUser()`. There is no admin session in maintenance, and
  `php_sapi_name() === 'cli'` returns `true` (`AbstractElement.php:486-489`). Tasks of a **deleted** user therefore run
  with full rights: deleting or offboarding an editor does not cancel their pending publish / unpublish / delete tasks,
  and the per-element permission check is skipped completely. Deactivated users (`active = 0`) are not checked
  either; `isAllowed()` never looks at `isActive()`.
- `publish-version` loads `Version::getById($task->getVersion())` and saves `$version->getData()` without checking
  `$version->getCid() === $task->getCid()` and the version's element type. The permission check ran on the task's
  element, but the element that is saved is the one inside the version. With the version id of **another** document /
  object / asset of the same type, that other element is overwritten with the old version and published. The
  permission check never sees it. The `version` id of a task comes from the admin request (upstream task saving
  passes the client's task data to `new Task($data)`; not verified locally, see notes). The model accepts any id.
**Fix direction:** load the user. If they are missing or inactive, deactivate the task with a log entry / element note
and never call `isAllowed()` with `null`. Pass the user explicitly through a permission check that has no CLI
fallback. Verify that the version belongs to the task's element (`cid` + `ctype`) both on save (validate in
`Task\Dao::save()`) and on execution. Related: C-36, B-211, B-214.

---

# Scope 9 – Version

> Notes
> - `models/Version.php`, `Version/Listing.php`, `Version/SetDumpStateFilter.php` and all `Version/Adapter/*.php` are
>   `strict_types`; `Version/Dao.php` and `Version/Listing/Dao.php` are not (I-101). `lib/Model/AbstractModel.php`
>   (which calls the setters during hydration) is not strict either.
> - One hard PHP 8.x type defect was found: `DatabaseVersionStorageAdapter::getStream(): ?false` (C-38). It was
>   reproduced with the local PHP 8.4.26 CLI (`TypeError: … Return value must be of type ?false, resource returned`).
>   `tests/Service/Element/VersionTest.php` uses `MockBuilder::setMethods()`, which PHPUnit 10 removed (`composer.json`
>   requires `^10.0 || ^11.0`). `vendor/` is not installed, so the suite was **not run locally** (B-225).
> - The default adapter is `FileSystemVersionStorageAdapter` (`bundles/CoreBundle/config/services.yaml:150-154`). The
>   DB and Delegate adapters are opt-in, but `doc/18_Tools_and_Features/01_Versioning.md:61-114` documents them as
>   supported. The `versionsData` table exists only in that doc and in the test; no installer or migration creates it.
> - `assets.versions.use_hardlinks` defaults to `true` (`Configuration.php:620-627`): FS version binaries are hardlinks of
>   the live asset file whenever both storages are local.
> - The admin version endpoints (preview, diff, "publish this version", public flag and note update) live in
>   `pimcore/admin-ui-classic-bundle`, which is not in this repo. Statements about them are **not verified locally**.
> - Already covered elsewhere and not repeated: C-07 (`__clone()` closes the pending asset stream during
>   `marshalData()`), C-37 (`publish-version` tasks with a version of another element), CoreBundle B-35 (`?v=` /
>   `pimcore_version` load any version without an ownership check), CoreBundle B-23 (`MigrateElementsCommand` null
>   version), B-17 (`VersionDeleteMessage` dispatched inside the delete transaction), TODO-2 `Recyclebin\Item::restore()`
>   `Version::disable()` without `finally` (Scope 6).

---

## [ ] C-38 – `DatabaseVersionStorageAdapter`: every asset version is unreadable (`TypeError`), and deleting the oldest version deletes the binary that all later versions share

**Files:** `models/Version/Adapter/DatabaseVersionStorageAdapter.php:35-57`, `:64-77`, `:79-82`, `:84-94`, `:96-101`,
`:103-110`, `:112-122`; `models/Version.php:171-189`, `:297-302`; `models/Version/Dao.php:96-110`;
`lib/Messenger/Handler/SanityCheckHandler.php:85-94`; `doc/18_Tools_and_Features/01_Versioning.md:61-114`

```php
protected function getStream(string $data): ?false   // null|false only
{
    if ($data) {
        $fileName = tmpfile();
        fwrite($fileName, $data);
        return $fileName;                             // resource → TypeError
```

- `getStream()` is declared `?false` but returns a `tmpfile()` resource, so every call with data throws `TypeError`.
  `loadBinaryData()`, `getBinaryFileStream()` and `getFileStream()` all go through it. `Version::loadData()` calls
  `loadBinaryData()` for **every** `Asset`, so no asset version stored in the DB can be loaded at all: no preview, no
  diff, no restore, no scheduled `publish-version` (the `TypeError` is not caught there either, C-36), no sanity check.
  Document / object versions break only on `getFileStream()`.
- The tests save through the DB adapter but never load (B-225), so this was not caught.
- If the return type is fixed, the stream is still at EOF after `fwrite()` (no `rewind()`). `Asset::getStream()` rewinds,
  but every other caller of `getFileStream()` / `getBinaryFileStream()` reads `''`.
- `fetchOne()` returns `false` for a missing row and `null` for `binaryData = NULL`. `getStream(string)` then throws
  `TypeError` under `strict_types`, and `loadMetaData(): ?string` throws on `false`. The intended "cannot read version
  data" path in `Version::loadData()` (`:273-278`) is never reached.
- **Shared binaries are deleted:** `Version::save()` sets `binaryFileId` to the oldest version of the same asset with
  the same hash (`Dao::getBinaryFileIdForHash()`). The DB adapter then saves `binaryData = NULL`, and later reads go to
  the row of `binaryFileId`. `delete()` ignores `$isBinaryHashInUse` and deletes that whole `versionsData` row, binary
  included. The FS adapter keeps the `.bin` in this case (`FileSystemVersionStorageAdapter.php:124`). Maintenance
  cleanup deletes the oldest versions first. So once an asset has more than `steps + 1` versions with an unchanged
  binary (every metadata-only edit), **every remaining version references a deleted binary**.
- **Re-saving a version wipes its own binary:** `SanityCheckHandler` (`:89-94`) and every `setNote()` / `setPublic()` +
  `save()` re-run the full `save()` (B-219). `getBinaryFileIdForHash()` now finds the version itself (the oldest with
  that hash), so `binaryFileId` = its own id, and `ON DUPLICATE KEY UPDATE binaryData = NULL` deletes its binary and
  the binary of every version that references it. Today the `TypeError` in `getData()` hits first; fixing only the type
  exposes this.
- `save()` runs `executeQuery()` for an `INSERT` (should be `executeStatement()`) and repeats the parameters for the
  `ON DUPLICATE KEY UPDATE` part instead of using `VALUES()` / an alias.

**Fix direction:** return `resource|null` (native `mixed` plus phpdoc) and `rewind()` the stream; treat `false` / `null`
as "not found" with a dedicated exception. In `delete()`, keep `binaryData` while the hash is in use. Better: store
binaries in a content-addressed table keyed by `(cid, hash)` with a reference count. Never overwrite `binaryData` with
`NULL` on update (`COALESCE(VALUES(binaryData), binaryData)`). Ship a migration or command for `versionsData`, and add
load / restore / delete tests for every adapter (I-109). Related: C-39, B-219, B-222, B-223.

---

## [ ] C-39 – Version storage writes are not atomic: an aborted binary write leaves a 1-byte placeholder `.bin` that later versions reuse

**Files:** `models/Version/Adapter/FileSystemVersionStorageAdapter.php:85-110` (`:91-92`, `:99`, `:102-107`);
`models/Version.php:171-189`; `models/Version/Dao.php:96-104`; `models/Asset.php:839-890`

```php
$this->storage->write($binaryStoragePath, '1'); // temp file to determine if stream is local or not
if ($useHardlinks && stream_is_local(...) && stream_is_local($binaryDataStream)) { ... delete(); @link(...); }
if (!$linked) {
    $this->storage->writeStream($binaryStoragePath, $binaryDataStream);   // may throw / be aborted
}
```

- `save()` first writes the string `'1'` to the version's `.bin` path (only to probe whether the stream is local),
  then hardlinks or copies the real binary. The placeholder stays if `writeStream()` throws (S3 / network error,
  disk full, quota) or the process dies while copying. Example: `max_execution_time` while copying a multi-GB video in
  a web request, OOM, a worker killed on deploy.
- `Version::save()` has already inserted the `versions` row with `binaryFileHash` (`Version.php:186`). Without a
  surrounding transaction the row stays committed. That is the case for `Asset::saveVersion()` (`:839-890`, no
  transaction), for re-saved versions (B-219) and for custom code.
- The next version with an unchanged binary (typical: a metadata edit) gets that row from `getBinaryFileIdForHash()`.
  FS `save()` sees that the `.bin` exists (`:91-92`) and skips the write. From then on **every** version with that
  content points to the placeholder. Restoring or publishing any of them writes a 1-byte file containing `1` as the
  asset binary. The stored hash says otherwise, and nothing checks it.
- In the hardlink branch the placeholder is deleted before `link()`. If `link()` fails and `writeStream()` then throws,
  no `.bin` exists at all. `loadData()` then returns the asset without a stream, and restoring it keeps the **current**
  binary next to the old metadata, without an error (B-218).
- Inside the element transaction (`Asset::save()`) the row is rolled back, but the files stay (ids are not reused, so
  this only leaks storage; nothing cleans up orphaned version files).

**Fix direction:** decide on hardlinks from the adapter configuration (both storages are `LocalFilesystemAdapter`)
instead of writing a probe file. Write to a temporary name and `move()` it into place. Write storage before the
`versions` row, or insert the row in a transaction and delete the files when the storage write fails. Before reusing
an existing `.bin` / binary row, check at least its size, and verify `binaryFileHash` on restore. Related: C-07,
C-38, B-218.
