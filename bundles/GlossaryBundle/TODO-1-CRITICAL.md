# GlossaryBundle – TODO 1: Critical errors

Scope: `bundles/GlossaryBundle/**` (config, public/js, src, translations), plus the direct consumers where a defect
originates in the bundle interplay (`lib/Tool/DomCrawler.php`, `tests/Unit/Document/Glossary/GlossaryTest.php`,
`tests/Support/Helper/Unit.php`)
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-2-BUGS.md](TODO-2-BUGS.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

These items cause **security holes (stored XSS / un-escaping of escaped content) on every frontend page that uses the
`pimcore_glossary` filter, corrupted HTML/JS output, or an admin editor that cannot save entries**.
IDs are stable – refer to them as `C-xx` in later sessions. Line numbers are from commit `bcac4499f6`.

> Notes
> - All `Processor` findings (C-01 … C-04) were reproduced with a scratch copy of `Processor::parse()` against
>   `symfony/dom-crawler` **v7.4.17 on PHP 8.4** (native `\Dom\HTMLDocument` parser) and against v6.4 (masterminds) –
>   behaviour is identical on both.
> - C-01 … C-04 share one root cause: the engine runs `preg_replace` over **serialized HTML strings** (`innerHTML`) and
>   then round-trips the whole document through `htmlentities()` / `html_entity_decode()`. The sustainable fix is the
>   DOM-text-node rewrite described in **I-13**; fixing the items one-by-one on the current design is not recommended.
> - No hard PHP 8.4 / Symfony 7.4 incompatibility (removed API, implicit nullable param, …) was found in the bundle.
>   The behavioural change of the PHP 8.4 HTML5 parser is filed as **B-08**.

---

## [ ] C-01 – Stored XSS: `abbr`, `link` and `text` are concatenated into HTML without escaping

**File:** `src/Tool/Processor.php:218-240`

```php
$r = '<abbr class="pimcore_glossary" title="' . $d['abbr'] . '">' . $r . '</abbr>';
...
$r = '<a class="pimcore_glossary" href="' . $d['link'] . '">' . $r . '</a>';
```

- Any admin user with only the `glossary` permission can store e.g. `abbr = x" onmouseover="alert(1)` or
  `link = javascript:alert(document.cookie)` → the payload is injected into **every frontend page** (all visitors)
  that renders a matching term inside `{% apply pimcore_glossary %}`. Reproduced:
  `<abbr class="pimcore_glossary" title="x" onmouseover="alert(1)">PHP</abbr>`.
- `text` is also inserted raw (`$r = $d['text']`), so a term like `<img src=x onerror=…>` is emitted as markup.
- The Twig filter is registered with `is_safe => ['html']` (`src/Twig/Extension/GlossaryExtension.php:39`), so Twig
  never escapes the result.
- Even without malicious intent, a quote in an abbreviation (`5" display`) or `&` in a URL breaks the markup.

**Fix direction:** build the replacement with DOM APIs (`createElement` + `setAttribute`, see I-13) or at least
`htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8')` for `text`, `abbr` and `link`.
Whitelist link schemes (`http`, `https`, `mailto`, `tel`, relative paths) – reject `javascript:` / `data:` both on
save (controller/model validation) and on render.

---

## [ ] C-02 – `html_entity_decode()` on the whole output un-escapes all content → XSS / markup injection

**File:** `src/Tool/Processor.php:143-154`

```php
$fragment->appendXML(htmlentities($text, ENT_XML1));
...
$result = html_entity_decode($html->html(), ENT_XML1);
```

- The decode is applied to the **entire serialized document**, not only to the nodes that were modified. Whenever at
  least one glossary entry exists for the current locale/site (`getData()` not empty), every escaped
  `&lt;` / `&gt;` / `&amp;` / `&quot;` / `&apos;` in the content is turned back into a raw character.
- Reproduced: input `<p>Use &lt;script&gt;alert(1)&lt;/script&gt; here</p>` → output
  `<p>Use <script>alert(1)</script> here</p>`; `Tom &amp;amp; Jerry, a &lt; b` → `Tom &amp; Jerry, a < b`.
- Any escaped user-generated content (comments, form echoes, search terms, product descriptions from imports,
  `{{ var }}` output that Twig escaped) inside a glossary region becomes live HTML → reflected/stored XSS, and code
  samples (`&lt;div&gt;`) in WYSIWYG content are rendered as tags.

**Fix direction:** never decode the full output. Modify DOM nodes directly (I-13) and serialize once with
`$crawler->html()`. If the string approach is kept short-term: create the fragment with
`DOMDocumentFragment::appendXML($text)` on properly-formed XHTML or parse the replacement with a sub-crawler and
import the nodes – and drop the global `html_entity_decode()`.

---

## [ ] C-03 – Terms are replaced inside tag names, attribute values and previously inserted glossary markup

**Files:** `src/Tool/Processor.php:88`, `:124`, `:132`, `:137`, `:244-246`

- The regex runs over `$parentNode->html()` (inner **HTML** incl. child tags and attributes). The only protection is
  `<a.*?\/a>(*SKIP)(*FAIL)`, which does not cover tag names, attributes or `<abbr>` replacements. Reproduced:
  - term `span`: `<p>A <span class="x">time span</span></p>` →
    `<p>A <<a href="/s">span</a> class="x">time <a href="/s">span</a></<a href="/s">span</a>></p>`
  - term `Glossary` and `<img alt="Glossary">` → `<img alt="<a class=…>Glossary</a>" …>`
  - terms `PHP` (abbr "Hypertext Preprocessor") + `Hypertext` → the second term is injected into the
    `title="…"` attribute of the first one: `<abbr … title="<a href="/h">Hypertext</a> Preprocessor">`
- Result: broken DOM, broken attributes (`class`, `href`, `src`, `data-*`), and – combined with C-02 – unpredictable
  output. Common short terms (`class`, `div`, `span`, `strong`, `title`, `data`, `src`, `href`) destroy a whole page.

**Fix direction:** only touch `DOMText` nodes (I-13). Process all terms in **one** pass (single alternation regex,
longest first) so replacements of one term are never re-scanned by the next one.

---

## [ ] C-04 – `blocked_tags` only protects an element itself, not when it is a descendant of a processed element

**Files:** `src/Tool/Processor.php:88`, `:122-129`, `config/pimcore/default.yaml:2-6`

```php
$es = $html->filterXPath('//*[normalize-space(text())]');
...
!in_array($parentNode->nodeName(), $this->blockedTags)
```

- Every element with direct text is selected, and its **whole inner HTML** is processed. If a `<div>` has its own
  text, all of its descendants are processed too – including `<script>`, `<style>`, `<code>`, `<pre>`, `<textarea>`,
  `<h1>`-`<h6>`, `<option>`, `<abbr>`.
- Reproduced: `<div>Intro <h2>Glossary</h2><script>var Glossary=1;</script></div>` →
  `<div>Intro <h2><a …>Glossary</a></h2><script>var <a class="pimcore_glossary" href="/test">Glossary</a>=1;</script></div>`
  → **inline JavaScript / JSON-LD / CSS is corrupted** (syntax errors, broken structured data), headings get links.
- `<a>` is only "protected" by the regex skip (see B-05 for its gaps), not by `blocked_tags`.

**Fix direction:** select text nodes with an XPath that excludes blocked ancestors, e.g.
`//text()[normalize-space()][not(ancestor::a or ancestor::script or …)]` built from `blockedTags` (I-13).

---

## [ ] C-05 – Admin grid: editing an entry whose `link`, `abbr` or `text` is `NULL` fails with a `TypeError`

**Files:** `src/Controller/SettingsController.php:56-68`, `:79-93`, `src/Model/Glossary.php:131-171`,
admin store config (`pimcore.helpers.grid.buildDefaultStore()` → `writer.writeAllFields: true`)

```php
public function setLink(string $link): static   // property is ?string
public function setAbbr(string $abbr): static
public function setText(string $text): static
...
$glossary->setValues($data);                     // passes null through (ignoreEmptyValues = false)
```

- An entry is typically either a link **or** an abbreviation, so one of the two columns is `NULL` in the DB
  (`Dao::update()` writes the `null` property). The list action returns it as `null`, the ExtJS writer sends **all**
  fields back (`writeAllFields: true`), and `AbstractModel::setValue()` calls `setAbbr(null)` →
  `TypeError: Argument #1 ($abbr) must be of type string, null given` → HTTP 500, row stays dirty in the grid.
- Same for clearing a field in the editor if ExtJS sends `null`, and for API code calling `setValues()` with DB rows.
- Repro: create an entry with text + link only, reload the grid, change the language → save fails.

**Fix direction:** make the setters nullable (`?string`) to match the properties and the schema, and/or normalise
`''`/`null` in the controller (`$data['abbr'] ?: null`). Add a controller/functional test for update with
`null` values.
