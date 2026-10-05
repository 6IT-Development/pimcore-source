# SeoBundle

`Pimcore\Bundle\SeoBundle\PimcoreSeoBundle`, shipped as part of `six-it/pimcore-source`.

The bundle keeps the original `Pimcore\Bundle\SeoBundle` namespace so that Pimcore 11 code keeps working with it.
The code itself is maintained in this fork and is no longer tied to upstream Pimcore.

## Current features

- **Redirects**: the model, handler, CSV import/export, and redirects created automatically when a document is
  moved or renamed or a URL slug changes (`pimcore_seo.redirects.auto_create_redirects`), plus a maintenance cleanup task.
  Clean-admin's *Tools > Redirects* page is built on this bundle. It checks `PimcoreSeoBundle::isInstalled()` and
  hides the page when the bundle isn't installed.
- **robots.txt**: set per site.
- **SEO document editor**: titles and descriptions for many documents at once.

Redirects stay in this bundle on purpose. They were not moved into Pimcore core.

## Roadmap: SEO rework (planned, not started)

Here "SEO" means everything that helps a page rank higher and get found. That covers classic search engines,
AI answer engines and tracking. All of it should live in this bundle.

### GEO (Generative Engine Optimization)

The goal is to make pages easy for AI systems (ChatGPT, Perplexity, Google AI Overviews and others) to read, index
and cite. It's also called AEO (Answer Engine Optimization).

- [ ] `llms.txt` per site, handled the same way as robots.txt (a markdown summary of the site for AI crawlers)
- [ ] AI crawler rules in robots.txt, allowed or blocked per site: GPTBot, ClaudeBot, PerplexityBot, Google-Extended, etc.
- [ ] JSON-LD / schema.org structured data built from documents and data objects (Article, FAQPage,
      Organization, BreadcrumbList, ...)
- [ ] Clean markdown versions of pages, without the layout, for AI fetchers
- [ ] Clear content metadata: author, published date, last-updated date

### GTM (Google Tag Manager)

- [ ] Better GTM handling inside this bundle

**Open decision:** `six-it/google-marketing-bundle` already handles GA/GTM. Before starting, choose one of these:
merge it into this bundle and retire it, or move only its GTM part over. Don't build a second GTM implementation
next to it.

### Packaging

- [ ] Add `"replace": { "pimcore/pimcore": "..." }` to `six-it/pimcore-source`, so Composer never installs the
      original Pimcore next to the fork. SeoBundle isn't a separate package, upstream or here, so the `replace`
      goes on the package that contains it.
