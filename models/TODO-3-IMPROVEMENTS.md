# models – TODO 3: Improvements

Scope: **(1)** `models/Asset.php` and `models/Asset/**` (Dao, Listing, Service, Folder, Image, Image/Thumbnail/**, Thumbnail/**,
Video, Video/**, Document, Document/**, MetaData/**, WebDAV/**, Enum, Archive/Audio/Text/Unknown)
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-2-BUGS.md](TODO-2-BUGS.md),
[CoreBundle TODO-3](../bundles/CoreBundle/TODO-3-IMPROVEMENTS.md) (I-67 WebDAV server service, I-71 WebDAV/thumbnail security model)

Code quality, readability, logic simplification and Symfony 7.4 / PHP 8.4 modernisation. Nothing here is broken today.
IDs are stable – refer to them as `I-xx` in later sessions. Line numbers are from commit `1b0711d927`.
Grouped: **A** PHP 8.4 / types / strictness, **B** Symfony 7.4 / DBAL / runtime, **C** shared logic / duplication,
**D** per-file logic & readability, **E** tests.

---

## A – PHP 8.4 / types / strictness

### [ ] I-01 – Add `declare(strict_types=1)` where missing

- `models/Asset.php`, `models/Asset/Dao.php`, `Asset/Listing/Dao.php`, `Asset/Image/Thumbnail.php`,
  `Asset/Image/Thumbnail/Config/Dao.php`, `Config/Listing/Dao.php`, `Asset/Video/Thumbnail/Config/Dao.php`,
  `Config/Listing/Dao.php`.
- Prerequisite fixes that currently rely on coercion: B-33 (`false` → `''`), B-57 (int media key),
  `Image\Thumbnail\Config::getByArrayConfig()` (`Config.php:483`, `:490`, `:575` – string `quality`/`highResolution`
  from YAML/legacy arrays). Cast/validate in the factories first.

### [ ] I-02 – Add native types to untyped members

- `Asset.php:104` `protected $stream` → `protected mixed $stream = null` (resource|null); `getStream()` (`:1142`) return
  type, `setStream($stream)` (`:1196`) parameter type.
- `Asset::setData(mixed $data)` (`:1130`) → `string` (arrays give a `TypeError` in `fwrite`); check `tmpfile()` for `false`.
- `Asset::getData(): bool|string` (`:1117`) → `string` and throw, or `string|false`.
- `Asset::setCustomSettings(mixed)` (`:1331`) → `array|string|null` with explicit handling.
- `Asset::getByPath()` (`:218`) `static|null` → `?static`; `@method getChildAmount($user = null)` (`:70`) typed.
- `Image::getImageTransformInstance(): ?Adapter` (`Image.php:200`) never returns null → `Adapter`.
- WebDAV `File::getContentType()` / `getLastModified()` → nullable (B-35).

### [ ] I-03 – `#[\Override]` on overridden / interface methods

- `Asset.php`: `save`, `delete`, `getById`, `getFullPath`, `__clone`, `__wakeup`, `__sleep`, `resolveDependencies`,
  `getParent`, `setParent`, `getChildren`, `hasChildren`, …
- `Video.php` (`update`, `clearThumbnails`), `Document.php` (`update`), `Image.php`, `Folder.php`.
- `MetaData/ClassDefinition/Data/*` subclasses.
- WebDAV `Tree::move`, `File::put/get/delete/getSize/…`, `Folder::getChildren/getChild/createFile/…`.
- Thumbnail interface implementations in `Image/Thumbnail.php`, `Video/ImageThumbnail.php`, `Document/ImageThumbnail.php`.

### [ ] I-04 – Enums and constants for string literals

- Asset types (`'folder'`, `'image'`, `'video'`, `'document'`, …) → backed enum or class constants; replaces the
  hard-coded list at `Asset.php:615` (`in_array($this->getType(), ['image','video','document'])`) with a polymorphic
  `supportsUpdateTasks()`.
- Video conversion status `'inprogress'` / `'finished'` / `'error'` (`Video/Thumbnail/Processor.php:89-110`, `:151`,
  `:229`, `:284`) → backed enum.
- Thumbnail formats (`'source'`, `'pjpeg'`, `'webp'`, `'avif'`, …) and transformation method names
  (`Image/Thumbnail/Config.php:609-647` `in_array` chains) → enums.
- Custom-setting keys repeated as literals (`'duration'`, `'videoWidth'`, `'videoHeight'`, `'thumbnails'`,
  `'image_thumbnail_time'`, `'image_thumbnail_asset'`, `'document_page_count'`) → class constants.
- `customSettingsCanBeCached` limit `10e6` (`Asset.php:1334`, = 10 000 000; reads like a typo for `1e6`) → named constant.
- `Image\Thumbnail\Processor::$argumentMapping` and the video counterpart → `private const`.

### [ ] I-05 – Constructor promotion, `readonly`, visibility

- WebDAV `File` / `Folder`: `public function __construct(private readonly Asset $asset)`.
- `Asset\Service::$_user` promoted + readonly; `$_copyRecursiveIds` → `private`.
- `Video\Thumbnail\Processor`: `processId`, `assetId`, `config` readonly; unused `$status` (`:58`) removed.
- `Video\Thumbnail\Config`: `public $medias`, `public $filenameSuffix` → `private` (getters exist).
- `Asset\Dao::$thumbnailStatusCache` `public static` → private + `resetThumbnailStatusCache()` (see B-27).
- `Image\Thumbnail::$hasListenersCache` is `protected static` in a `final` class → `private`.

### [ ] I-06 – PHP 8.4 functions / idioms

- `Asset::getTypeFromMimeMapping()` (`:472-489`): `array_find_key()` + `array_any()` instead of nested loops.
- `Asset::typeMatch()` (`:244-254`): `return static::class === Asset::class || $asset instanceof static;`.
- Replace `rand()` (`Asset.php:581`) with `random_int()`.
- Replace by-reference `foreach` (`Asset.php:1519-1528`, `Service.php:252`, `Video.php:101-111`) with `array_map` /
  `array_values()`.
- Strict comparisons `===`/`!==` for ids, types and paths (`Asset.php:538`, `:563`, `:656`, `:729`, `:773`, `:979`;
  `Video/Thumbnail/Config/Dao.php:52`, `:58` `$id != null` treats `"0"` as null).
- Replace `goto` in `Image/Thumbnail/Processor::applyTransformations()` (two-pass, see B-46).

### [ ] I-07 – Serialization API

- `Asset::__sleep()` / `__wakeup()` → `__serialize()` / `__unserialize()` (soft-deprecated direction in PHP 8.5; do it
  together with B-07). Same in `AbstractElement` (outside scope).

---

## B – Symfony 7.4 / DBAL / runtime

### [ ] I-08 – Bound parameters instead of concatenated SQL (DBAL 4 readiness)

- `Asset/Dao.php:209-224`, `:422`, `:433-434`, `:467`; `Listing.php:71-85`; `Dao.php:337-351`, `:393-407`.
- Use `ArrayParameterType::INTEGER` for id lists; one shared helper for the workspace permission fragment (also fixes
  the duplication in I-14 and the anchoring in B-28).

### [ ] I-09 – `ResetInterface` for static caches (worker runtimes)

- `Asset\Dao::$thumbnailStatusCache`, `Image\Thumbnail::$hasListenersCache`, Image/Video config RuntimeCache entries.
- Register in a `kernel.reset`-tagged service or clear in `LongRunningHelper` (see B-27, B-51).

### [ ] I-10 – Temp-file lifecycle

- `File::getLocalTempFilePath()` registers one shutdown closure per file (`lib/File.php:87`) – grows in workers and
  cleans up in the wrong process (B-63).
- Unlink promptly with `try/finally` in `Image/Thumbnail/Processor.php:272`, `:341`, `Image.php:94`, WebDAV (B-29),
  `Asset::create()` (B-11).
- Explicit `$lock->release()` in `finally` in all thumbnail generators.

### [ ] I-11 – Lock handling

- `Image/Thumbnail/Processor.php:257-259`: default 300 s TTL without `refresh()` → slow TIFF/PDF generations can be
  duplicated. Pass a TTL or refresh.
- Document a single lock strategy for image/video/document thumbnails (blocking vs. non-blocking, see B-69).

### [ ] I-12 – Process / external tools

- `EmbeddedMetaDataTrait::readEmbeddedMetaData()` (`:58`) resolves the `exiftool` executable even when `$useExifTool`
  is false – resolve lazily.
- `lib/Video/Adapter/Ffmpeg.php:105-112` runs `ffmpeg -codecs` and discards the result – remove.
- `Ffmpeg::colorChannelMixer()` (`:422-426`) appends a config string verbatim into `-vf` (admin-defined
  `x,movie=/etc/passwd` injects filters). Validate against `^[0-9.:=a-z]+$`.

### [ ] I-13 – Remote-storage efficiency

- `Asset.php:733`: `stream_is_local($storage->readStream($path))` opens a full read stream (HTTP GET on S3) just to test
  locality and never closes it – decide by adapter/config, cache the result.
- `ClearTempFilesTrait` lists the whole thumbnail storage recursively (B-53).
- `Image/Thumbnail/Processor.php:241-253`: redundant second staleness check (extra remote `lastModified`, and uses
  `getModificationDate()` while `:194` uses `getDataModificationDate()`) – remove or unify.
- `Image::isAnimated*()` (`:385`, `:411`) loads the full file via `getData()` – read a stream prefix.
- Folder preview (`Folder.php:388`): `file_get_contents()` + `write()` → `writeStream()`.
- Deferred path (`Processor.php:153`, `:224-225`): adapter created even on early return; `Config::exists()` and
  `TmpStore::add()` hit config repo / DB on every render – memoise per request.

---

## C – Shared logic / duplication

### [ ] I-14 – One permission-SQL builder for `hasChildren()` / `getChildAmount()` / `Listing`

- `Asset/Dao.php:331-410` (two near-identical methods, `LIMIT 1` vs `COUNT(*)`) and `Listing.php:71-85`.
- `Dao::isAllowed()` (`:450-461`) re-implements `getParentIds()` without the loop guard and loads every ancestor;
  `empty($permissionsParent)` at `:474` is always true there.

### [ ] I-15 – Merge `Video\ImageThumbnail` and `Document\ImageThumbnail` into the trait / an abstract base

- Duplicated: `getPath()`, `__toString()`, `createConfig()` (three variants with different nullability, incl.
  `Image/Thumbnail.php:430`), lock + cache-file generation, error-placeholder block
  (Video `:64-80`/`:108-114`/`:131-193`/`:203-219` vs Document `:59-75`/`:112-124`/`:129-171`/`:173-201`).
- A `generateSourceFrame()` hook is enough. The drift has already produced C-13, B-69, B-70; Video also skips
  `checkMaxScalingFactor` and ignores `$deferred` (`Video/ImageThumbnail.php:125-161` runs ffmpeg synchronously).

### [ ] I-16 – One XMP/XML block scanner

- `Video.php:272-343` and `EmbeddedMetaDataTrait.php:139-228` contain near-identical chunked tag scanners, prefix
  stripping and namespace cleanup. Extract `extractXmlBlock($stream, $openTag, $closeTag)` (and reuse the overlap logic
  for C-14).

### [ ] I-17 – Merge `Service::copyRecursive()` / `copyAsChild()`

- `Asset/Service.php:72-172` (~40 duplicated lines; `setObjectVar('id', null)` vs `setId(null)`).
- `copyContents()` (`:177-201`) copies stream, custom settings and properties but not **metadata** – verify intent.

### [ ] I-18 – Image / Video thumbnail config & listing parity

- Video listing Dao lacks filter/order (B-72); Video `getByName()` lacks `clone` (B-73); both use `crc32($name)`
  RuntimeCache keys (`Image/Thumbnail/Config.php:227`, `Video/Thumbnail/Config.php:113`) – collisions return another
  config. Use the name or `xxh3`.
- `Image/Thumbnail/Config/Listing/Dao.php:26` extends `Config\Dao` and so inherits `save()`/`delete()`;
  `getTotalCount()` (`:47-50`) reloads and mutates the listing. Extend the location-aware DAO directly.
- Shared base for both listing DAOs and both config DAOs.

### [ ] I-19 – One `addMetadata`/`getMetadata` name normaliser and one status-cache writer

- See B-06 (`~` → `---`) and B-60 (status-cache writes in Processor vs. trait).

---

## D – Per-file logic & readability

### [ ] I-20 – `Asset.php`: dead and redundant code

- `:343-345` `str_starts_with($tmpFile, PIMCORE_SYSTEM_TEMP_DIRECTORY)` is always true.
- `:669-671` and the `.htaccess` check at `:692` are unreachable (`getValidKey(…, 'asset')` trims dots, `isValidKey`
  at `:657` already rejects).
- `:683` `elseif ($this->getId() == 1)` → `else`.
- `:595` `isset($updatedChildren)` always true – initialise before the loop.
- `:1498` `getObjectVar('metadata')` = `$this->metadata`.
- `:894-898` `getFullPath()` builds `$path` before the frontend early return.
- `:230-233` `prepareGetByIdParams` applied twice.

### [ ] I-21 – `Asset::create()` writes `data` payloads twice

- `:347-349` dumps to `$tmpFile`, then `setData()` copies into `tmpfile()` again. Open `$tmpFile` and pass it as stream.

### [ ] I-22 – `Asset.php` smaller issues

- `:1808` `getRealPath() . '/' . getId()` yields `//` (unlike `clearThumbnails()` `:1686`) and swallows every exception.
- `setStream()` (`:1210-1214`) leaks the original non-rewindable stream; `fopen` result unchecked.
- `self::` vs `static::` for temp-file helpers (`:1211` vs `:1275`/`:1285`) – pick one.
- `customSettingsCanBeCached` (`:1334`) never resets to `true` once false.
- `POST_LOAD` fires on every `getById()` incl. runtime-cache hits, and mid-transaction from `update()` (`:820`) –
  document or guard (`:311-315`).
- Misleading docblock `:1653` (`$elementData` annotated as `ElementInterface`).

### [ ] I-23 – `Asset/Dao.php` & `Listing/Dao.php`

- `:132-135` `SELECT … LIMIT 1` before `DELETE` – just `DELETE`.
- `:123` `getValidTableColumns()` per property – `array_intersect_key` once; `:65`, `:147` (and `Service.php:267`,
  `:296`) fetch the metadata loader from the container inside a loop – hoist.
- `:209` `LIKE '<old>%'` (no slash) over-selects siblings `/foobar` for cache clearing – use `'<old>/%'`.
- `Listing/Dao.php:25-38` N+1 `Asset::getById()` per row; `if ($assetData['type'])` always true; `getCount()`
  (`:72-81`) loads all ids instead of `COUNT(*)`.

### [ ] I-24 – `Asset/Service.php` smaller issues

- `pathExists()` (`:203`) unused `$type` parameter.
- `minimizeMetadata()` / `expandMetadataForEditmode()` read `$item['type']` / `$item['language']` without `??`.
- `getStreamedResponseByUri()` (`:550`) `if ($config)` is dead.
- `:519` says "see logs" but nothing is logged; `@throws` lists only `FilesystemException`.

### [ ] I-25 – WebDAV readability / efficiency

- `File::setName()` / `Folder::setName()` (`File.php:53-66`, `Folder.php:180-190`) are never reached because
  `Tree::move()` overrides Sabre's base implementation – the `rename` permission check is dead (root of CoreBundle C-13).
  `Folder::setName()` does not set `userModification`. Route `Tree::move()` through a shared guarded helper.
- `Tree.php:54`, `:64` `setData($sourceAsset->getData())` reads whole files into memory → `setStream()`.
- `Folder::getChildren()` (`:54-58`) dead try/catch; loads every child fully (N+1) – `loadIdList()` with lazy nodes.
- `WebDAV/Service.php:42`, `:54` duplicate the 30 s filter → `pruneLog()`; fixed shared temp path (see B-30).

### [ ] I-26 – `Folder.php` preview

- `:324` `$colums` typo; tile streams (`:353`) never closed; see B-31/B-32 for logic.

### [ ] I-27 – Image thumbnail dead code

- `Image/Thumbnail.php:121-127`, `:480`: `$this->config` is never null → "use original image" branch unreachable;
  `$this->asset` checks at `:121`, `:480`; `$thumb->reset()` at `:181`.
- `Image.php:221-225` `'unknown'` return in `getFormat()`.
- `ImageThumbnailTrait.php:358-360`, `:427` `new Config()` fallbacks.
- Make `getConfig()` non-nullable for image thumbnails.

### [ ] I-28 – Image thumbnail smaller issues

- `Image.php:96` `generateLowQualityPreview`: `file_get_contents()` → `false` unchecked.
- `Image.php:237-241` `getDimensions()` returns stored settings without casting (possible `TypeError` in `getWidth(): int`).
- `Image/Thumbnail.php:461`: commas encoded only in srcset; admin paths don't encode spaces → `rawurlencode` segments.
- `Config.php:643-644` `cropPercent` estimate uses `ceil` while `Adapter::cropPercent(int …)` truncates – align
  rounding (accept floats in the adapter).
- Remove empty `@internal` docblock boilerplate in `Image/Thumbnail/Config.php`.

### [ ] I-29 – Video / Document smaller issues

- `Document/ImageThumbnail.php:93` calls `$config->setFilenameSuffix()` before the null check at `:102`, which can never
  fail (`createConfig()` non-nullable) – remove the check.
- `Document/ImageThumbnail.php:43` uses `TemporaryFileHelperTrait`, already used by `ImageThumbnailTrait` (`:33`).
- `Video/Thumbnail/Config.php:210-229` `selectMedia()` never called for video configs; `filenameSuffix` (`:104`,
  `:279-287`) unused by the video processor.
- `Document::processPageCount(?string $path)` (`Document.php:52`) – unused `$path`.
- `Document::getText()` (`:130`) `!$text = Cache::load(...)` never serves empty text from cache – compare `=== false`
  and store `''` on failure.
- Fix empty docblocks / stray `*` lines (`Video.php:85-89`, `:153-157`; `Video/Thumbnail/Processor.php:60-64`) and the
  "backwards compatility" typo in both thumbnail `__toString()` docblocks.
- Log messages in `Video/ImageThumbnail.php:144`, `:150` say "document".

### [ ] I-30 – `Archive` / `Audio` / `Text` / `Unknown` / `Enum\PdfScanStatus`

- Trivial and correct. Optional: `PdfScanStatus` gains an `UNKNOWN` case for C-14; type classes could become a single
  generic class keyed by type (low value – only if the enum from I-04 lands).

---

## E – Tests

### [ ] I-31 – Unit / integration tests for the fixed defects

- `Asset\Dao::updateChildPaths()` with repeated segments and mixed element types (C-01).
- Folder move on a non-local Flysystem adapter (in-memory adapter) – C-02, B-02, B-03.
- `Asset::create()` with `php://temp`, `sourcePath`, oversize images (C-03, C-04, B-11, B-12).
- `__clone()` + `saveVersion()` with pending stream (C-07).
- `Service::getUniqueKey()` edge cases (`README`, `data.data`) – B-21.
- WebDAV MOVE across folders with rename / overwrite / failure (C-08, B-20).
- Thumbnail config name validation (C-09); `getHash()` coverage (B-38); uppercase extensions (B-37).
- Video processor with missing bitrates, missing job, deleted asset (C-10, C-11); malformed spherical XMP (C-12).
- PDF scan with boundary-straddling `/JS` and `/J#53` (C-14).
- Metadata `getMetadataByName()` localisation and `~` names (B-05, B-06).
