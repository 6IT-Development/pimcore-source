# models – TODO 3: Improvements

Scope: **(1)** `models/Asset.php` and `models/Asset/**` (Dao, Listing, Service, Folder, Image, Image/Thumbnail/**, Thumbnail/**,
Video, Video/**, Document, Document/**, MetaData/**, WebDAV/**, Enum, Archive/Audio/Text/Unknown)
**(2)** `models/WebsiteSetting.php` and `models/WebsiteSetting/**` (Dao, Listing, Listing/Dao), plus the direct consumer
`lib/Config.php::getWebsiteConfig()` (`:173-262`) and `tests/Model/WebsiteSetting/WebsiteSettingTest.php`
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
Related: [TODO-1-CRITICAL.md](TODO-1-CRITICAL.md), [TODO-2-BUGS.md](TODO-2-BUGS.md),
[CoreBundle TODO-3](../bundles/CoreBundle/TODO-3-IMPROVEMENTS.md) (I-67 WebDAV server service, I-71 WebDAV/thumbnail security model)

Code quality, readability, logic simplification and Symfony 7.4 / PHP 8.4 modernisation. Nothing here is broken today.
IDs are stable – refer to them as `I-xx` in later sessions. Line numbers are from commit `1b0711d927` (Scope 2:
`7f0331cab9`, Scope 3: `2d382456c7`, Scope 4: `3e2bf1c4d8`, Scope 5: `0b659e9ce0`).
Grouped: **A** PHP 8.4 / types / strictness, **B** Symfony 7.4 / DBAL / runtime, **C** shared logic / duplication,
**D** per-file logic & readability, **E** tests; Scope 2 is in its own section **F** WebsiteSetting (I-32–I-40), Scope 3 in **G** Dependency (I-41–I-50),
Scope 4 in **H** Translation (I-51–I-63), Scope 5 in **I** Site (I-64–I-72).

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

---

## F – WebsiteSetting (Scope 2)

### [ ] I-32 – Strictness and native types

- Add `declare(strict_types=1)` to `models/WebsiteSetting/Dao.php` and `WebsiteSetting/Listing/Dao.php`.
- `Dao::getById(?int $id = null)` / `getByName(?string $name = null, …)` (`Dao.php:32-34`, `:50-54`): the optional
  parameters plus loose `!= null` checks make `0` / `''` behave like "use the model value". Callers always pass the
  value, so make them required and drop the checks.
- `setData(mixed $data)` (`WebsiteSetting.php:176`) → `string|int|float|bool|ElementInterface|null`. Arrays and objects
  currently reach DBAL and fail there, or are converted to `"Array"`.
- `setId(int)` / `setCreationDate(int)` / `setModificationDate(int)` against `?int` properties: mark the setters
  `@internal`, or use PHP 8.4 asymmetric visibility (`public private(set) ?int $id`) for DB-managed fields once the DAO
  hydrates through a dedicated method.

### [ ] I-33 – `WebsiteSettingType` backed enum and one typed value accessor

- The type set is documented three times (`WebsiteSetting.php:229`, `:242`, `install.sql:606`) and switched on in
  `lib/Config.php:229-234` and `WebsiteSetting::getData()` (`:191`).
- Introduce `enum WebsiteSettingType: string { case Text = 'text'; case Document = 'document'; case Asset = 'asset';
  case Object = 'object'; case Bool = 'bool'; }` with an `isElement()` helper. Accept `WebsiteSettingType|string` in
  `setType()` for BC.
- Add `getValue(): mixed`, which performs the same casting as `getWebsiteConfig()` (bool → `bool`, text → `string`,
  element → `?ElementInterface`). Today `getByName('flag')->getData()` returns the raw DB string `'1'`/`''` while
  `pimcore_website_config('flag')` returns `bool`. Use `getValue()` in `lib/Config.php` so the cast logic lives in one place.
- `setData()` with an unsaved element stores a `null` id while changing the type; reject it with an exception.

### [ ] I-34 – Class-level clean-up in `WebsiteSetting.php`

- `final class` with `protected` properties and `protected static` methods → `private`.
- Fluent setters return `WebsiteSetting` with `@return $this` docblocks → `: static` and drop the docblocks.
- Mixed `WebsiteSetting::getCacheKey()` (`:97`) and `self::getCacheKey()` (`:275`) → `self::`.
- Mixed `Service::getElementType()` (`:179`, imported alias) and `Element\Service::getElementById()` (`:192`) → one style.
- `getByName()` `@throws Exception` (`:93`) is never thrown; the comment "which setting is which is" (`:55`) has a
  doubled phrase; the empty docblock lines at `:228-244` can go.
- `getByName()` `if ($setting->getId() > 0) … return $setting;` (`:118-125`): the `return $setting` branch is
  unreachable (rows always have an auto-increment id).
- `getById()` returns the **shared** RuntimeCache instance. Unsaved in-memory changes by one caller leak into
  `getByName()` / `getWebsiteConfig()` for the rest of the request; document this or return clones for read paths.
- `delete()` on an unsaved setting dispatches events and clears the cache tag for nothing; return early when `id === null`.
- `#[\Override]` wherever a parent method is overridden (none today; add it if `save()` / `delete()` move into an
  interface).

### [ ] I-35 – Query and caching efficiency

- `getByName()` does not cache negative results or fallback-language results (the mapping key for the requested
  language is stored only on a direct hit, `:118-122`). The common Twig/PHP pattern
  `WebsiteSetting::getByName('optional')?->getData()` runs a DB query on every call. Cache misses too, and invalidate
  them together with C-15.
- `getWebsiteConfig()` loads **all** settings of all sites and languages through `Listing::load()`, which runs one
  `getById()` query per row (`Listing/Dao.php:38-40`, N+1). Hydrate from a single
  `SELECT * … WHERE (siteId IS NULL OR siteId IN (0, :site)) AND language IN ('', :lang)`.
- `Config::getWebsiteConfig()` treats an empty config `[]` as a cache miss (`if (!$config)`, `lib/Config.php:203`), so
  installations without website settings rebuild it on every request. Compare against `false`.

### [ ] I-36 – DAO simplification

- `Dao::create()` + `update()` (`Dao.php:95-115`): one `INSERT` with all valid columns (also fixes B-89). Compute the
  column whitelist once with `array_intersect_key($vars, array_flip($this->getValidTableColumns(...)))` instead of calling
  `getValidTableColumns()` inside the `array_filter` callback for every key (`:99`).
- Rewrite the `getByName()` ranking as an explicit priority expression with an `id` tie-breaker (see B-84). This also
  lets MySQL use a composite `(name, siteId, language)` index (I-37).
- `Listing/Dao.php:49`: `'SELECT COUNT(*) as amount FROM website_settings ' . getCondition()` – stray space and unused
  alias; align with `load()`.

### [ ] I-37 – Schema

- Add a composite (unique, see B-86) index on `(name, siteId, language)`. Today the query uses `INDEX name` only and
  filters/sorts the rest.
- `creationDate` / `modificationDate` are nullable with default `0`, and `siteId` mixes `NULL` and `0` (B-84). Normalise
  in a migration.

### [ ] I-38 – `Listing`

- `getSettings()` (`Listing.php:48-55`) relies on `load()` having set `$settings`; add `@return list<WebsiteSetting>` and
  a non-null guarantee (`?? []`).
- `setSettings()` → fluent `: static` for consistency with other listings.
- Default order by `id` (makes `getWebsiteConfig()` deterministic until B-83 is fixed).

### [ ] I-39 – Site detection parity and events

- `getByName()` requires the caller to pass `$siteId`, while `getWebsiteConfig()` detects the current site (including
  admin preview via the main request document, `lib/Config.php:181-196`). Offer the same detection (for example
  `getByNameForCurrentSite()`), or document the difference. Templates using `getByName()` silently ignore site overrides.
- `save()` / `delete()` have no transaction and no failure events. `PRE_*` listeners cannot veto cleanly, and a failing
  DAO call leaves listeners without a `POST_*` or `*_FAILURE` signal. Align with the element models
  (`POST_ADD_FAILURE` etc.) if listeners need it.

### [ ] I-40 – Tests

Extend `tests/Model/WebsiteSetting/WebsiteSettingTest.php`. Today it covers only `getByName()` precedence. Add cases for:
- delete then `getByName()` / `getById()` in the same request, and an override created after a lookup (C-15);
- `getWebsiteConfig()` parity with `getByName()` for every case already in the test, including reversed insert order
  (B-83);
- `siteId = 0` vs `NULL` (B-84);
- fallback language with and without a neutral row (B-85);
- duplicate rows, empty name, invalid type (B-86, B-87);
- `save()` with a non-existent id (B-88);
- listing with a concurrently deleted row (B-90).

---

## G – Dependency (Scope 3)

### [ ] I-41 – Strictness, native types and a typed target value object

- Add `declare(strict_types=1)` to `models/Dependency/Dao.php`. It currently coerces DB strings into
  `addRequirement(int …)` (`Dao.php:59`). Once strict, cast explicitly, or rely on PDO native ints (PHP 8.1+).
- `protected array $requires` and all `array` returns are untyped shapes. Introduce
  `final readonly class DependencyTarget { public function __construct(public ElementType $type, public int $id) {} }`
  (or at least `@return list<array{id: int, type: string}>`) for `getRequires()`, `getRequiredBy()`, `addRequirement()`
  and `setRequires()`. `getRequiredByWithPath()` / filters return a different shape (`id`, `type`, `path`); document both.
- `Dao::getBySourceId(?int $id = null, ?string $type = null)` (`Dao.php:41`): the model always passes both. Make them
  required and drop the falsy check (see B-99).
- Constructor promotion for `sourceId` / `sourceType` (fixes the uninitialized state, B-99). `getBySourceId()` →
  `: static` with `new static()`.
- PHP 8.4: `getSourceId()` / `getSourceType()` + setters could become `public private(set)` properties once callers
  stop using the setters. Keep the setters as deprecated BC shims.

### [ ] I-42 – `ElementType` enum for `sourceType` / `targetType`

- `'object' | 'document' | 'asset'` is hard-coded in `Dao.php:73`, `:132`, `:323`, every SQL branch, the schema enum,
  `Element\Service::getDependedElement()` (`Service.php:257-262`) and `Element\Service::getElementType()`. No shared enum
  exists in `models/Element`.
- Add `enum ElementType: string { case Asset = 'asset'; case Document = 'document'; case DataObject = 'object'; }` (shared
  with the rest of `models/`). Accept `ElementType|string` in `setSourceType()` / `addRequirement()` for BC and validate
  there. This replaces the three `in_array(…) / SuspiciousOperationException` blocks (B-104).

### [ ] I-43 – One query builder for the three path queries; bound parameters; consistent API

- `getFilterRequiresByPath()`, `getFilterRequiredByPath()` and `getRequiredByWithPath()` (`Dao.php:63-119`, `:121-178`,
  `:319-362`) are about 90 % identical: the same validation, the same `ORDER BY` whitelist, three `UNION` branches that
  differ only by table / path expression, and three different `LIMIT` styles (`sprintf`, `sprintf`, concatenation).
  Extract one private method `fetchWithPath(direction: requires|requiredBy, ?string $filter, …)` that builds the branches
  from a map `['object' => ['objects', 'CONCAT(o.path, o.key)'], …]`. This also fixes C-16, B-93, B-95 and B-96
  structurally.
- Bind every value (`:sourceId`, `:sourceType`, `:value`) – DBAL 4 readiness, same as I-08. Use
  `setFirstResult()` / `setMaxResults()` (DBAL QueryBuilder) instead of string `LIMIT`.
- Use single quotes for SQL string literals: `dependencies.targettype="object"` (`Dao.php:51-53`, `:294-296`) breaks
  under `ANSI_QUOTES` (double quotes become identifiers).
- `if (count($x) > 0) return $x; else return [];` (`:114-118`, `:173-177`) → `return $this->db->fetchAllAssociative(...)`.
- Model wrappers: `getFilterRequiresByPath()` / `getFilterRequiredByPath()` (`Dependency.php:105-117`) do not forward
  `$orderBy` / `$orderDirection`, while `getRequiredByWithPath()` does. There is no `getRequiresWithPath()` counterpart at
  all. Make the four public methods symmetrical, and give the filter queries a matching `…TotalCount()`; the admin
  cannot page a filtered list correctly without it.

### [ ] I-44 – Efficiency

- `Asset::delete()` (`Asset.php:1018`) and `Document::doDelete()` (`Document.php:640`) call `$this->getDependencies()`
  only to call `cleanAllForElement()`. That loads **all** `requires` rows with three `LEFT JOIN`s and a filesort first.
  Use `new Dependency()` like `AbstractObject::doDelete()` (`:438`), or a static `Dependency::cleanAllForElement()`.
- `getBySourceId()` always loads and path-sorts the full `requires` list, then `getRequires($offset, $limit)` slices it in
  PHP (`Dependency.php:96-103`) and `getRequiresTotalCount()` counts it in PHP. Objects with thousands of relations pay
  this on every tab open. Make `requires` lazy and add SQL-side `getRequires($offset, $limit)` and `COUNT(*)`, mirroring
  `getRequiredBy()` / `getRequiredByTotalCount()`. Keep the in-memory list only for building a new set in `save()`.
- `getRequiredBy()` / `getBySourceId()` sort on columns of three `LEFT JOIN`ed tables, so MySQL always filesorts the
  whole result before `LIMIT`. Sorting by `(type, id)` for paging, or by one computed path in a derived table, is
  cheaper.
- `Element\Service::get*ForFrontend()` loads every listed element fully (`getById()` per row, N+1) just to read id,
  path, type, subtype and the `list` permission. Use `getRequiredByWithPath()`-style rows plus a batch permission check.
- `cleanAllForElement()` / `clear()`: `Helper::selectAndDeleteWhere()` with `quoteInto()` string building. For one
  `(sourcetype, sourceid)` prefix of the unique `combi` index a plain
  `$this->db->delete('dependencies', ['sourcetype' => $type, 'sourceid' => $id])` is enough. If the select-then-delete
  is there on purpose (avoids gap locks), document the reason.

### [ ] I-45 – `Dao::save()` simplification

- Typo `$existingDepencies` (`Dao.php:228-236`, `:252`, `:259`).
- Deduplicate `requires` by `type . '_' . id` before computing the delta (B-103). Then the
  `catch (UniqueConstraintViolationException) {}` (`:279`) is only needed for concurrency and should log, not hide.
- `if ($targetType && $targetId)` (`:246`) drops invalid entries silently. Validate in `addRequirement()` instead
  (I-42, B-99).
- Bulk statements: one multi-row `INSERT` (or `INSERT IGNORE`) instead of one round trip per target, and the delete via
  `executeStatement('DELETE … WHERE id IN (?)', [$ids], [ArrayParameterType::INTEGER])` instead of `implode()` into SQL
  (`:265-268`).
- Wrap in a transaction (C-17).
- `Helper::quoteInto()` passes a second `$type` argument to `Connection::quote()` (`lib/Db/Helper.php:106`). DBAL 4
  removes that parameter and accepts only `string`. `clear()` / `cleanAllForElement()` pass int ids. Bound parameters
  avoid the issue (DBAL 4 readiness).

### [ ] I-46 – Docblocks and readability in `Dependency.php` / `Dao.php`

- Wrong docs:
  - `Dependency::cleanAllForElement()` (`:71-75`) says "schedules a sanity check for the affected **targets**". It
    schedules the **sources** that require the element.
  - `Dao::cleanAllForElement()` (`Dao.php:180-184`) says "Clear all relations in the database". It clears only rows
    where the element is the source (see B-97).
  - `Dao::getRequiredBy()` "Loads the relations that need the given source element" – the term "source" is used for
    the element in both directions throughout the class. Rename the model fields (`elementId` / `elementType`) or add
    one class docblock that explains requires vs. required-by.
- Empty docblock lines (`Dependency.php:28-45`, `:47-50`, `:59-62`, `:170-173`; `Dao.php:36-40`, `:180-184`, `:218-221`,
  `:285-289`, `:364-367`), the blank lines inside `getFilterRequiresByPath()` / `getFilterRequiredByPath()`
  (`Dependency.php:106-109`, `:113-116`; `Dao.php:69`, `:128`), and the column-aligned parameter style
  (`?int    $offset`, `Dependency.php:125-128`; `Dao.php:64-68`, `:122-126`) differ from the rest of `models/`.
- `@method void save()` (`Dependency.php:24`): document that it writes the delta for `requires` only. `clean()`
  (`:85-89`) has no caller in this repo; deprecate or document it.
- The class is `@internal`, but `ElementInterface::getDependencies(): Dependency` exposes it publicly. Either drop
  `@internal` from the read API or return an interface / read-only view.
- `isRequired()` docblock: "an other" → "another".

### [ ] I-47 – `Element\Service` dependency helpers: one implementation

- `getRequiredByDependenciesForFrontend()`, `getRequiresDependenciesForFrontend()`, `getFilterRequiresForFrontend()` and
  `getFilterRequiredByPathForFrontend()` (`Service.php:163-242`) are four copies of the same loop. They differ only in
  the result key and in whether the rows come from the model or from an array. Extract one
  `buildDependenciesForFrontend(iterable $rows, string $key): array` (fixes B-105).
- `getDependedElement()` (`:255-263`) duplicates `Service::getElementById()`; use it.

### [ ] I-48 – `ElementDependenciesHandler` clean-up

- `try/finally` for the global state (B-92), using the existing `DataObject\Service::useInheritedValues()`.
- `showUnpublished()` returns the previous value and has the opposite meaning of its name. Rename to
  `disableHideUnpublished()` and `restoreHideUnpublished()`. `$hideUnpublished` holds the **previous** state.
- Self-reference skip uses loose `==` (`:63`). With typed ids (I-41) use `===`.
- `Service::getElementById($type, $id)` (`:41`) uses the cached instance. For async transports, pass `['force' => true]`
  so the dependencies are computed from the committed state, not a stale cached element.
- Skip the work when `pimcore.dependency.enabled` was switched off after the message was queued.
- `#[\Override]` does not apply; mark the class `final` (it is `@internal`).

### [ ] I-49 – Schema and maintenance

- `dependencies.id` is `BIGINT` while `sourceid` / `targetid` are `INT UNSIGNED`. The surrogate key is only used for
  the delta delete in `save()`. The unique `combi` key could be the primary key, making `id` unnecessary and deletes
  simpler. Check `install.sql:64-73` and the migration impact first.
- There is no command that rebuilds the dependency graph for all elements, or one that purges rows whose source or
  target no longer exists (B-97, C-17). Add a maintenance task or a console command
  (`pimcore:dependencies:rebuild` / `:cleanup`).

### [ ] I-50 – Tests

Extend `tests/Model/Element/DependenciesTest.php`. Today it covers only basic `getRequires()` / `getRequiredBy()` after a
forced reload. Add cases for:
- filter values with `'`, `%`, uppercase letters, regex metacharacters and an empty value (C-16, B-94, B-95);
- `getFilterRequiredByPath()` with sources of mixed types (B-93);
- `orderBy: 'path'` on all path queries (B-96);
- deleting a target whose source is a folder / link, then checking counts and `isRequired()` (B-97);
- an exception in `resolveDependencies()` leaving `hideUnpublished` / `getInheritedValues` unchanged (B-92);
- `getDependencies()` on an unsaved element and after a save without forced reload (B-99, B-100);
- `limit` without `offset`, and stable paging with ties (B-101, B-102);
- the filter helpers returning the list key when nothing matches (B-105).

---

## H – Translation (Scope 4)

### [ ] I-51 – Strictness and native types

- Add `declare(strict_types=1)` to `models/Translation/Dao.php` and `Translation/Listing/Dao.php`. Before that, cast the
  DB values explicitly in hydration (`(int)` dates, `(string)` text, see C-19 / B-109).
- `Translation::$translations` is `string[]` only in the docblock. Use `array<string, string>`, and validate in
  `setTranslations()` (today any value, including `null` and arrays, can be stored and saved).
- `protected ?string $type = 'simple'` with `getType(): string` and `setType(?string)`: make it non-nullable
  (`TranslationType`, I-52). `setCreationDate(int)` / `setModificationDate(int)` vs `?int` getters: make the setters
  nullable or the properties non-nullable with a default.
- Fluent setters are typed `: Translation` with `@return $this`. Use `: static`. `setDomain()`, `setType()`,
  `setUserOwner()`, `setUserModification()` return `void` – the API is inconsistent; pick one style.
- `Translation` is `final`, so `protected` members can be `private`.
- `Listing\Dao::getQueryBuilder(...$columns)` → `getQueryBuilder(string ...$columns)`. `getByKey(?array $languages)` →
  `@param list<string>|null`. `getAvailableLanguages()` / `getAvailableDomains()` → `@return list<string>`.
- `/** @var string */` on the typed `public const string TABLE_PREFIX` (`Dao.php:35-38`) is redundant.
- `Listing::getTranslations(): array` returns `getData(): ?array`; add the `@return Translation[]` guarantee or a
  `?? []`.

### [ ] I-52 – `TranslationType` enum and one domain validator

- `'simple'` (and "everything else is ICU") is hard-coded in `Translation.php:65`, `:86`, `Translator.php:172`. Add
  `enum TranslationType: string { case Simple = 'simple'; case Custom = 'custom'; }` (check the admin bundle for the real
  second value first). `setType(TranslationType|string)` for BC, validated with `TranslationType::from()` (fixes the
  type part of B-110).
- Domain validation exists in three variants (`Listing::setDomain()`, `Translator::lazyInitialize()`, none in
  `Translation`). Add one `TranslationDomain` check (config list + identifier pattern, C-21), used by all entry points,
  and memoised (I-56).
- `DOMAIN_DEFAULT` / `DOMAIN_ADMIN` and the hard-coded `'messages'` in `Translator.php:276`: use the constant.

### [ ] I-53 – API naming and deprecations (PHP 8.4 `#[\Deprecated]`)

- `IsAValidLanguage()` → `isValidLanguage()`; `isAValidDomain()` → `isValidDomain()`. Keep the old names as
  `#[\Deprecated(since: '…')]` aliases (PHP 8.4 attribute; triggers `E_USER_DEPRECATED` natively).
- `setDate()` is only used by the dead legacy branch in `Listing\Dao::getAllTranslations()` (B-109). Deprecate.
- `Dao::createOrUpdateTable()` never updates → `createTableIfNotExists()` (and move it out of `save()`, B-112).
- `getByKey()` mixes three jobs: find, create-if-missing, and "fallback to the key" presentation (C-18). Split into
  `find(string $key, string $domain, ?array $languages = null): ?self`, `findOrCreate(...)`, and let
  `getByKeyLocalized()` handle the key fallback. Keep `getByKey()` as a deprecated wrapper.
- The `translations_` prefix is hard-coded in `Dao.php:157`, `:161`, `:183` and `Listing.php:72`; use `TABLE_PREFIX`.

### [ ] I-54 – Runtime caching through a service instead of `RuntimeCache` + statics

- The per-request translation cache (`RuntimeCache`, B-107), the save queue (`TranslationEntriesDumper::$translations`,
  static) and the Translator's `$initializedCatalogues` (B-124) are three separate pieces of process state with no reset.
  Put them in one `TranslationRepository` service that implements `ResetInterface` (`kernel.reset`). It clears the
  cache on `save()` / `delete()` and returns immutable values or clones.
- Make the dumper queue an instance property of the (shared) dumper service instead of a static array.

### [ ] I-55 – `Dao::save()` simplification

- One `INSERT … VALUES (…), (…) ON DUPLICATE KEY UPDATE text = VALUES(text), …` for all **changed** languages, inside
  `$this->db->transactional()`. Do not update `creationDate` / `userOwner` (B-109). Track dirty languages in the model
  (`addTranslation()` marks the language dirty).
- Move the editor permission check (C-20, B-111) out of the DAO into the model or a security voter, and throw on
  violation. The DAO should not call `User::getById()`.
- Move the HTML sanitising into the model, **before** `PRE_SAVE`, so listeners see the final text. Today the DAO
  mutates the model in the middle of the save (`Dao.php:107-110`), and only for texts that contain tags.
- `Logger::warning(sprintf(...))` with `@phpstan-ignore-line` → PSR-3 placeholders and a typed `$user`.
- `Helper::upsert()` (insert, catch duplicate, update) costs two round trips per existing row. A real upsert costs one.

### [ ] I-56 – Efficiency

- `isAValidDomain()` runs `SELECT * FROM translations_<domain> LIMIT 1` on **every** call. The Translator calls it for
  every missing key on every request (`Translator.php:240`), `Listing::setDomain()` for every listing. Memoise per
  request (I-54), and use `SELECT 1`.
- `Listing\Dao::load()` runs one `getByKey()` query **per key** (N+1). `getAllTranslations()` already loads everything
  with one query and groups in PHP. Do the same for the page of keys: one query for the keys, one
  `WHERE key IN (:keys) AND language IN (:languages)` for the texts.
- `getCount()` loads the list twice (B-118).
- `cleanup()`: the correlated subqueries are O(n²). Use
  `SELECT key FROM t GROUP BY key HAVING SUM(COALESCE(text, '') <> '') = 0`, then delete in chunks with
  `ArrayParameterType::STRING`.
- CSV import: every `$t->save()` calls `clearDependentCache()` (`Cache::clearTags()`) – 5000 rows = 5000 tag clears plus
  cache stampedes on the frontend. Clear once at the end (the method already does), and skip it inside the loop.
- `getByKeyLocalized()` loads all languages of the key to return one. Pass `[$language]`.
- `Admin::determineCsvDialect()` (`lib/Tool/Admin.php:104-106`) calls `file($file)` **ten times** to build the sample
  (helper outside scope, called by the import).

### [ ] I-57 – CSV import as a service

- Move `importTranslationsFromFile()` (110 lines, 7 nesting levels, `@internal`) to a
  `Pimcore\Translation\Import\CsvTranslationImporter` service. Return a typed result
  (`final readonly class TranslationImportResult { public function __construct(public int $imported, public array $conflicts, public array $skipped) {} }`)
  instead of the untyped `$delta` array.
- Use `league/csv` (already a dependency, `^9.11`, used for `EscapeFormula`) with `Reader::createFromPath()`, header
  offset 0 and `getRecords()`. That streams the file, removes the BOM (`skipInputBOM()`), maps columns to the header,
  and replaces the temp-file copies and the manual `array_slice` / `for` loop.
- PHP 8.4: `mb_trim($value, "\u{FEFF}\"")` instead of the byte-wise `trim()` with an invisible BOM in the source
  (B-116).
- Guard clauses instead of `if (is_readable) { … if (count > 1) { … } else throw } else throw`.
- Use `===` / `!==` (B-116). Validate the header (`key` column required, unknown language columns reported).
- Wrap the import in a transaction or batch it (e.g. 500 keys per transaction) so a failure does not leave half a file
  imported.

### [ ] I-58 – Listing modernisation and DBAL 4 readiness

- Apply the `languages` filter in SQL (`language IN (:languages)`), so count and page agree (B-118).
- `$queryBuilder->executeQuery()->fetchAllAssociative()` instead of
  `fetchAllAssociative($queryBuilder->getSql(), getParameters(), getParameterTypes())` (`Listing/Dao.php:69`, `:104`,
  `:116`).
- `sprintf('SELECT COUNT(*) … FROM (%s) AS a', $queryBuilder)` relies on `QueryBuilder::__toString()`; use
  `getSQL()` explicitly. `QueryBuilderHelperTrait::prepareQueryBuilderForTotalCount()` (shared) uses `getQueryPart()` /
  `resetQueryParts()`, which DBAL 4 removes; the Translation listing does not use it, so keep it that way.
- `Dao::getByKey()` builds two SQL strings for `languages` / no `languages`, but `Translation::getByKey()` always passes
  an array. Drop the `null` branch, and avoid passing an unused `languages` parameter to DBAL.
- `Dao::delete()` passes `quoteIdentifier('key')` as the criteria key. Fine in DBAL 3/4, but document it (or use
  `executeStatement('DELETE FROM … WHERE `key` = ?', [$key])`).
- Remove `$cacheLimit` / `isCacheable()` or use them (B-117).

### [ ] I-59 – Symfony 7.4: dependency injection instead of the service locator

- `Translation::getTranslationSanitizer()` (`Pimcore::getContainer()->get(...)`), `getByKeyLocalized()`
  (`LocaleServiceInterface`, `Admin::getCurrentUser()`, `authenticateSession()`), `Dao::save()` (`User`, `Admin`) all pull
  services from the container. The sanitizer is also stored on the model and ends up in the cache (B-117).
- With the service from I-54, inject `HtmlSanitizerInterface` (`#[Autowire(service: Text::PIMCORE_TRANSLATION_SANITIZER_ID)]`),
  `LocaleServiceInterface`, `Security` and the DBAL connection. The model stays a plain data object; keep the static
  methods as thin deprecated wrappers.
- `Translator` / dumper: implement `ResetInterface` (B-124, B-115).

### [ ] I-60 – Docblocks and readability

- Empty or broken docblocks: `Translation.php:204-208` (`@internal` after `@return` with an empty line), `:241-245`;
  `Dao.php:45-49`, `:135-138`, `:151-154`, `:170-174`; `Listing.php:24-33`, `:43-46`; `Listing/Dao.php:162-165`.
- Wrong or outdated texts: `Dao::getAvailableLanguages()` "a array"; comment "make" → "made" (`Translation.php:73`);
  `TranslationEvent` constructor says "AssetEvent constructor" and mentions a "versionNote"
  (`lib/Event/Model/TranslationEvent.php:30-34`); "Imports translations from a csv file … an Pimcore".
- `Translation::` and `self::` are mixed inside the class (`Translation.php:96`, `:260`, `:392`, `:444`, `:487`). Use
  `self::`. `$this->model::getRegisteredDomains()` (`Dao.php:178`) → `Translation::getRegisteredDomains()`.
- One-letter / cryptic names: `$t`, `$rd`, `$l`, `$d`, `$transObj`, `$idOriginal`.
- `Dao::getByKey()`: `if (!empty($data)) { … } else { throw }` → guard clause; set key-level fields once, outside the
  loop (B-109).
- `getRegisteredDomains()` indexes `['domains']` without a fallback.
- `importTranslationsFromFile()` takes an untyped `stdClass $dialect`. Use a small readonly `CsvDialect` value object.

### [ ] I-61 – Events and model lifecycle

- `save()` and `delete()` clear the cache at different points relative to the `POST_*` event (B-113). Use one order.
- Add events (or one bulk event) for `Listing::cleanup()` and the import (B-122), so integrations can react.
- `PRE_SAVE` listeners cannot cancel cleanly. Document "throw to cancel", or add a `stopPropagation`-based veto.
- Keep the recursion guard state (`RecursionBlockingEventDispatchHelperTrait`) out of serialised model data (`__serialize()`
  or a `WeakMap` in the dispatcher helper), see B-117 / B-125.

### [ ] I-62 – Schema and table lifecycle

- One schema definition for domain tables, used by the installer, the migrations and the lazy creation (today
  `install.sql:432-444` and `Dao.php:199-210` differ). Create tables for **all configured domains** (incl. `admin`) at
  install time, in a migration, and through a console command (`pimcore:translations:create-domain-tables`); remove DDL
  from `save()` (B-112, B-120).
- `text` nullable → after `UPDATE … SET text = '' WHERE text IS NULL`, make it `NOT NULL` (C-19). `creationDate` /
  `modificationDate` likewise (B-109).
- `language` uses the table's default collation (case-insensitive) while `key` is `utf8mb4_bin`. `de` and `DE` are the
  same PK. Use `ascii_bin` or `utf8mb4_bin` for `language`.
- `type varchar(10)` → `ENUM` or a check constraint that matches `TranslationType` (I-52).

### [ ] I-63 – Tests

`tests/Unit/Translation/TranslatorTest.php` covers basic translation, parameters, plurals, case sensitivity, a list load,
cache invalidation on save and sanitising. There is no model-level test for `Translation`, its DAO or the listing.
Add cases for:
- `getByKeyLocalized(…, create: true, returnIdIfEmpty: true)` followed by `TranslationEntriesDumper::dumpToDb()` – the stored
  text must be `''` (C-18);
- a row with `text = NULL` / `creationDate = NULL` in the catalogue build, `getByKey()` and `getAllTranslations()` (C-19, B-109);
- `save()` as a user with view-only languages (C-20) and with restricted edit languages (B-111);
- a domain value that is not registered / contains a backtick (C-21);
- auto-create for a key that exists in other languages: the other languages must be unchanged (C-22);
- `getByKey()` with and without `$returnIdIfEmpty` in the same request, and after `delete()` (B-107, B-113);
- the same key missing in two domains in one request (B-115);
- import: missing `key` column, `"1"` → `"1.0"`, `"0"`, two imports with different files (B-116);
- two listings with the same `setCondition()` SQL and different values, and with different `languages` (B-117);
- `count()` before and after `load()`, and `setOrderKey('creationDate')` (B-118, B-119);
- `getAvailableLanguages()` under `ONLY_FULL_GROUP_BY` (B-121); `cleanup()` followed by a cached listing (B-122);
- a `PRE_SAVE` listener that throws once, followed by a second save (B-125).

---

## I – Site (Scope 5)

### [ ] I-64 – Strictness and native types (PHP 8.4)

- Add `declare(strict_types=1)` to `models/Site/Dao.php` and `Site/Listing/Dao.php`. Before that, make the hydration
  casts explicit (`(int)` ids/dates, `(bool)` `redirectToMainDomain`), because hydration relies on the non-strict
  `AbstractModel::setValue()` today.
- Fluent API is inconsistent: `setId()`, `setDomains()`, `setRootId()`, … return `Site` (`@return $this`), while
  `setErrorDocument()`, `setMainDomain()`, `setRedirectToMainDomain()` return `void`. Use `: static` everywhere.
- `Site` is `final`: `protected static $currentSite` and the `protected` properties can be `private`.
- Precise array types: `@var list<string>` for `$domains`, `@var array<string, string>` (locale → path/id) for
  `$localizedErrorDocuments`; `Listing::getSites()` → `@return list<Site>` and `?? []` (`getData()` is nullable).
- `setCreationDate(int)` / `setModificationDate(int)` vs `?int` getters: pick one (non-nullable with `0` default matches
  the schema).
- PHP 8.4 asymmetric visibility fits the read-mostly fields: `public private(set) ?int $id`, and a property hook on
  `$domains` / `$mainDomain` can hold the normalisation from B-128 instead of setter logic.
- `getBy(mixed $mixed)` → `getBy(int|string|Site $site)` (B-127).

### [ ] I-65 – Storage format and DBAL 4 readiness

- `domains` and `localizedErrorDocuments` are PHP-`serialize()`d text. Use JSON columns (`json_encode` / `json_decode`
  with `JSON_THROW_ON_ERROR`) in a migration. Until then, `unserialize(..., ['allowed_classes' => false])` everywhere
  (`Site/Dao.php:70` calls raw `unserialize()`, `Serialize::unserialize()` too).
- Better: move domains to a `sites_domains (domain PK, siteId, isWildcard)` table. That gives an indexed exact lookup,
  a unique constraint across sites (B-130) and a simple wildcard query, and removes the `LIKE` on serialized text.
- `Dao::update()` writes every property that happens to match a column name (`getObjectVars()` + `in_array()`
  filter), including `id`. Use an explicit column map.
- DBAL 4: `lastInsertId()` returns `int|string`; use `Types::*` parameter types for the `IN` / int parameters; avoid
  `SELECT *`.

### [ ] I-66 – Domain resolution as one service

- Extract `getByDomain()` matching into a `SiteDomainResolver` (or `SiteRepository::findByHost()`): normalise input
  (B-128), exact match first, then wildcards ordered by specificity, `str_contains()` instead of `strpos()` as a boolean,
  no regex rebuilding per request (compile once and cache), resolve the `@TODO: refactor this to be more clear`
  (`Site/Dao.php:65`).
- Use it from `RoutingListener`, `PublicServicesController`, `RedirectHandler`, `RedirectsController` and
  `Document\Service::getByUrl()` (`models/Document/Service.php:404-421`, which duplicates the domain check and
  concatenates a `Document` object into a path string).
- Fix the exception text `` `…´ `` (acute accent instead of a backtick, `Site/Dao.php:91`).

### [ ] I-67 – Caching: one site registry instead of `RuntimeCache` + per-key entries + a sentinel string

- `getById()` and `getByDomain()` duplicate the same 20 lines; the `RuntimeCache::set()` after a runtime hit is
  redundant; the `'failed'` string sentinel mixes types in one variable.
- The `sites` table is tiny. Load **all** sites once into one cached registry (id → data, domain → id, root path → id),
  tagged `site` + the root documents' tags, reset via `ResetInterface` / `kernel.reset`. This replaces
  `site_id_*`, `site_domain_*`, `sites_path_mapping` (`lib/Tool/Frontend.php:70-95`) and the uncached
  `getByRootId()` (called twice per page node by `models/Element/AdminStyle.php:87`, `:101`, each a query plus a root
  page load), and fixes C-23, B-131, B-132 in one place.
- Do not cache objects with embedded documents (C-23); cache plain data and hydrate.
- `Listing\Dao::load()` is N+1 (`SELECT id` then `getById()` per row, each loading the root page on a cold cache). Fetch
  the rows in one query and hydrate them, with the root page loaded lazily.

### [ ] I-68 – Current site: request state instead of a static

- Deprecate `Site::setCurrentSite()` / `getCurrentSite()` / `isSiteRequest()` with PHP 8.4 `#[\Deprecated]` in favour
  of `Pimcore\Http\Request\Resolver\SiteResolver` (request attribute), as CoreBundle TODO-3 already suggests for the
  listeners. For CLI rendering, pass the site explicitly (B-126).
- `getCurrentSite()` throws a generic `\Exception` for a normal condition; callers wrap it in `try/catch` and check
  `instanceof Site` on a non-nullable return. Offer `?Site` (`findCurrentSite()`) or a dedicated exception.
- `isSiteRequest()` → `return self::$currentSite !== null;`.

### [ ] I-69 – Validation and lifecycle

- One `validate()` called by `save()` before `PRE_SAVE`: root id set and a `Document\Page`, root not used by another
  site, domains valid and unique across sites, main domain valid (and optionally part of the domains), error documents
  exist (B-129, B-130, B-133, B-134, B-136). Throw a `ValidationException`, not `InvalidArgumentException` from setters
  during hydration.
- Wrap `Dao::save()` / `delete()` in a transaction; dispatch `PRE_ADD` / `POST_ADD` for new sites (B-133).
- Error documents by id and part of the dependency graph (B-136).
- `create(array $data)` returns an unsaved site; name it clearly or add `createAndSave()`; document that `domains`
  must be a list of strings.
- Event dispatch uses `RecursionBlockingEventDispatchHelperTrait` without `try/finally` (shared helper, see B-125).

### [ ] I-70 – Readability and dead code

- `setDomains()` uses `array_map()` for its side effect (throwing). Use `foreach`, or PHP 8.4
  `array_any($domains, fn (string $d): bool => !self::isValidDomain($d))` and one shared `isValidDomain()` for
  `setDomains()` and `setMainDomain()` (the `idn_to_ascii()` + `FILTER_VALIDATE_DOMAIN` logic is duplicated, and the
  `'anystring'` placeholder trick deserves a named constant/comment).
- `array_filter($domains)` keeps the keys → a non-list array is serialized; add `array_values()`.
- Remove or deprecate `$rootPath` / `setRootPath()` (no caller in the repo, never persisted, B-137).
- `getRootDocument()` returns a possibly stale embedded object; with lazy loading (C-23) the `$rootDocument` property
  can go.
- Docblocks: `Listing\Dao::load()` "Loads a list of thumanils for the specicifies parameters, returns an array of
  Thumbnail elements"; `@method Model\Site|false current()` on the listing; `Dao` comments ("it's a good idea to use
  save() instead") on `@internal` methods; empty `/** * @throws */` blocks. `Site::clearDependentCache()` comment "this
  is mostly called in Site\Dao not here" describes nothing useful.
- `getById()` / `getByDomain()` declare `@throws Exception` but only rethrow DB errors; document the real contract.

### [ ] I-71 – Schema

- `redirectToMainDomain tinyint(1) DEFAULT NULL` → `NOT NULL DEFAULT 0`; `mainDomain` / `errorDocument` `NULL` vs `''`
  (the model writes `''`, the schema defaults to `NULL`): pick one (`NOT NULL DEFAULT ''` matches the model).
- `creationDate` / `modificationDate` are `int(11) unsigned` (2106 limit); fine, but align with the other models
  (`bigint` / `datetime` if those move).
- Index (or unique) on `mainDomain`, or the domain table from I-65.
- Reconsider `fk_sites_documents … ON DELETE CASCADE` (C-24).
- `domains` / `localizedErrorDocuments` `text` → `json` (I-65).

### [ ] I-72 – Tests

There is no model test for `Site`; `tests/Model/Tool/FrontendTest.php` only covers `isDocumentInSite()`. Add cases for:
- renaming / moving the root page, then `getByDomain()` + `getRootPath()` with a warm persistent cache (C-23);
- deleting the root page, then `getByDomain()`, slugs and recycle-bin restore (C-24);
- rendering two documents of different sites in one CLI process (B-126);
- `getBy('5')`, `getBy(5)`, `getBy('example.com')` (B-127);
- `bücher.de`, `Example.com`, `*.Example.com` against lower-case / punycode hosts (B-128);
- loading a row with an underscore domain and with corrupt serialized data (B-129);
- `exampl_.com`, an empty host, two sites with the same domain, overlapping wildcards (B-130);
- `getById()` before create / after delete in the same process, listing after delete (B-132);
- save with a missing / non-page / already-used root, save of a deleted site (B-133, B-134);
- delete with dependent website settings and slugs (B-135).
