# SeoBundle – TODO 1: Critical errors

Scope: `bundles/SeoBundle/config`, `bundles/SeoBundle/src`
Target: Symfony 7.4, PHP 8.4 (branch `s74-p84`)
Related: [TODO-2-BUGS.md](TODO-2-BUGS.md), [TODO-3-IMPROVEMENTS.md](TODO-3-IMPROVEMENTS.md)

These items cause **a whole public feature to fail because of a single bad value, or redirects that are silently
broken for visitors/crawlers**.
IDs are stable – refer to them as `C-xx` in later sessions. Line numbers are from commit `7162d16812`.

> Notes
> - No PHP 8.4 / Symfony 7.4 hard incompatibility was found (no implicit-nullable params, no removed APIs, attribute
>   routing already in place, `array_all()` / `new X()->method()` already PHP 8.4 style). The remaining compatibility
>   work is modernisation and is listed in TODO-3.
> - Pimcore's default DBAL config runs `SET sql_mode = ''` (`bundles/CoreBundle/config/pimcore/default.yaml:148`).
>   Items that would only crash under strict SQL mode are therefore filed as bugs (B-01, B-20), not critical. If a
>   project enables strict mode, re-classify B-01 as critical (exception thrown inside `kernel.exception`).

---

## [x] C-01 – One invalid `sitemaps_priority` / `sitemaps_changefreq` property aborts the whole sitemap

**File:** `src/Sitemap/Element/Processor/PropertiesProcessor.php:35-52`

```php
$changeFreq = $element->getProperty(self::PROPERTY_CHANGE_FREQUENCY);
if (!empty($changeFreq)) { $url->setChangefreq($changeFreq); }
$priority = $element->getProperty(self::PROPERTY_PRIORITY);
if (!empty($priority)) { $url->setPriority($priority); }
```

- Properties are free-text values maintained by editors. Presta's `UrlConcrete::setChangefreq()` throws
  `InvalidArgumentException` for anything not in `always|hourly|daily|weekly|monthly|yearly|never`
  (e.g. `Weekly`, `week`); `setPriority()` throws for values outside `0..1` or non-numeric strings (`"0,8"`, `"high"`).
- Nothing catches the exception → `/sitemap.xml` (and `presta:sitemaps:dump`) fails **completely** because of a typo
  on one document. Search engines get a 500 for the sitemap.
- Additionally `!empty("0")` silently ignores a legitimate priority of `0`.

**Fix direction:** validate/normalise before calling Presta (lower-case + `in_array` against
`UrlConcrete::CHANGEFREQ_*`; `is_numeric` after `str_replace(',', '.')` + range check), log invalid values with the
document id/path and skip only that attribute. Use `null !== $priority && '' !== $priority` instead of `empty()`.

---

## [x] C-02 – Default status code `300 Multiple Choices` produces a "redirect" without `Location` header

**Files:** `config/pimcore/default.yaml:10-15`, `src/Redirect/RedirectHandler.php:219-226`

```php
$response = new Response(null, $statusCode);
if ($response->isRedirect()) {
    $response = new RedirectResponse($url, $statusCode);
}
```

- `Response::isRedirect()` is only true for `201, 301, 302, 303, 307, 308`. `300` is offered by default in the admin
  status-code dropdown, but a redirect saved with `300` returns an **empty 300 response without `Location`** – the
  redirect is dead for visitors and crawlers, while the `X-Pimcore-Redirect-ID` header and the redirect log make it
  look like it worked.
- `308 Permanent Redirect` (method-preserving permanent redirect) is missing from the defaults.

**Fix direction:** remove `300` from `default.yaml` (or treat every `3xx` via `$response->isRedirection()` and set the
`Location` header explicitly), add `308: Permanent Redirect`. If non-redirect codes (e.g. `410 Gone`) are intended to
be supported, handle them explicitly and document it. Data check/migration for existing rows with
`statusCode = '300'`.
