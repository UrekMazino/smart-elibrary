# OPAC Public-Launch SEO — Plan

**Status:** **All four workstreams are DONE** — A (metadata), B (JSON-LD), C (sitemap + robots),
D (URL slugs) — plus an admin **SEO settings card**. Remaining work is the pre-launch environment
checklist in §4 and `DEPLOYMENT_CHECKLIST.md`.
**Last updated:** 2026-07-28

> **Admin controls (added after D):** Settings gains a *Search engine visibility* switch
> (`seo_discourage_indexing`) that forces `noindex, nofollow` on every public page and **overrides**
> any page-level value — for staging/pre-launch. A forgotten switch would silently keep the whole
> catalogue out of Google, so every admin page shows a warning banner while it is on. Also a Google
> Search Console verification field (`seo_verification_google`) and a **Sitemap card** showing URL
> count / last build with a *Regenerate* button (no shell access needed) — including a warning when
> the sitemap's URLs don't match the current host, which happens if it was built before `APP_URL`
> was updated. The record list's Resource Filter gains **w/o Summary** (4,937 records today): those
> fall back to a generated meta description, so this is the editorial worklist for improving snippets.
>
> **Bug found while testing this:** `/` **302-redirects to `/home`**, but workstreams A and C had
> pointed the homepage canonical, the sitemap entry and the JSON-LD `WebSite.url` at `/`. A sitemap
> must list final URLs and a canonical must name the URL that actually renders — all now use `/home`.

> **D — implemented:** record URLs are now the ID-prefixed hybrid
> `/full-description/10435-fruit-color-as-an-indicator-…`. The leading integer is the lookup key
> (so `catalog_id` stays the RAG join key and a retitled record never breaks a link); the slug is
> decorative, capped at 80 chars and cut back to a whole word.
> `seoRecordSegment()` / `recordUrl()` in `app/Http/helper.php` are the single source of truth, used
> by every link, the canonical tag, the JSON-LD and the sitemap.
> **Canonicalisation:** a bare id (old/shared links) or a stale slug **301s** to the current URL —
> done before `saveView()`, so a redirect hop isn't counted as a view. The search results' POST to
> `/full-description` now redirects to the canonical URL instead of rendering at an id-less address,
> removing a duplicate-content path and giving users a shareable URL.
> Verified: 200 on the canonical URL, 301 from bare id and from a stale slug, 404 for unknown ids,
> non-numeric segments and unpublished records; canonical tag, JSON-LD `url` and sitemap all agree.

> **C — implemented:** `php artisan sitemap:generate` (in `app/Console/Commands/GenerateSitemap.php`)
> writes `public/sitemap.xml` (a sitemap **index**), `sitemap-pages.xml` and chunked
> `sitemap-records-N.xml` (25k URLs/file, spec max 50k) covering every published, non-deleted record
> with `<lastmod>` from `updated_at` — currently **7,641 URLs in one chunk**. It also regenerates
> `robots.txt` so the absolute `Sitemap:` line always matches `APP_URL` (which differs dev vs prod).
> Scheduled daily at 03:30 in `app/Console/Kernel.php`. Sitemap XML is gitignored (derived, ~1 MB,
> daily churn); `robots.txt` stays tracked so a fresh deploy has crawl rules before the first run.
>
> **Landmine avoided:** robots.txt matches by **prefix**, so a `Disallow: /full-description` rule
> would have blocked every record page and hidden the whole catalogue. The generator documents this
> and the verification asserts that no sitemap URL is blocked by any Disallow rule.
>
> **Deploy step:** run `php artisan sitemap:generate` after deploying (and after `APP_URL` is set to
> the real https domain), then submit `https://<domain>/sitemap.xml` in Google Search Console.

> **B — implemented:** every record page emits a JSON-LD array (the work + a `BreadcrumbList`), and
> the homepage emits `WebSite` (with a `SearchAction` → `/results?query={search_term_string}`) plus
> `Organization`. Helpers: `seoSchemaType()`, `seoAuthorList()`, `seoJsonLdEncode()`,
> `seoRecordJsonLd()`, `seoSiteJsonLd()`.
> Type map (verified against the 9 material types actually in the catalogue): Books / Language
> material → `Book` · Thesis/Dissertations → `Thesis` · Serials → `Periodical` · Technical Reports →
> `Report` · Analytics / Investigatory Projects → `ScholarlyArticle` · Vertical Files / Non-Prints →
> `CreativeWork`. Only populated fields are emitted (no empty or invented properties); `pub_year`
> free text ("c2010.") is reduced to a bare year.
> **Blade gotcha found here:** `@yield` HTML-escapes section values, so JSON-LD is emitted through
> `@push`/`@stack` with `{!! !!}` (safe — `seoJsonLdEncode()` uses `JSON_HEX_TAG`), and the
> workstream-A meta sections must NOT wrap values in `e()` or they double-escape (`&amp;amp;`).

> **A — implemented:** `master.blade.php` now yields `meta_title` / `meta_description` / `robots` /
> `canonical` / OG + Twitter, with the old hardcoded strings as fallbacks. `full-description.blade.php`
> supplies a unique title, description, canonical and `og:image` per record; home and results pages
> get real titles (results are `noindex, follow`); private/auth pages are `noindex, nofollow`.
> Helpers in `app/Http/helper.php`: `seoTrimIsbd()`, `seoTitle()`, `seoPageTitle()`,
> `seoDescription()`, `seoRecordDescription()` (summary → general note → factual fallback, so records
> with an empty 520 still get a unique description). Verified: two records render distinct
> title+description; ISBD punctuation and `/`-separated author lists are cleaned.

**Goal:** when the OPAC goes public, make the ~7,600 published catalogue records **discoverable in
Google** — each record page a landing page for searches like *"jatropha curcas seed germination"*.

**Why now:** the site is still on `elibrary.test` in development. Nothing is indexed, there are no
external inbound links, and the only shared URLs are internal test ones. **Every change here is
near-free today and becomes a migration project after launch** (301 chains, re-crawling, ranking
dip). Do this work *before* the public launch.

---

## 1. Current state (audited 2026-07-20)

| Area | Finding | File |
|---|---|---|
| **Page `<title>`** | ❌ **Static and identical on every page** — `<title>DOST-PCAARRD eLibrary</title>`, no `@yield('title')` | `resources/views/opac/layout/master.blade.php:11` |
| **Meta description** | ❌ **Static on every page** — `"Find ANNR materials online"` (also a typo: AANR) | `…/master.blade.php:7` |
| **Canonical tag** | ❌ none | — |
| **Open Graph / Twitter** | ❌ none (link previews show nothing) | — |
| **Structured data (JSON-LD)** | ❌ none | — |
| **`sitemap.xml`** | ❌ does not exist | — |
| **`robots.txt`** | ⚠️ Laravel default — `User-agent: *` / `Disallow:` (allows everything, including private routes) | `public/robots.txt` |
| **Record URL** | `…/full-description/{catalog_id}` — numeric only | `routes/web.php:42` |
| **Record page hygiene** | ✅ already good: published-only + `abort_if(!$result, 404)` | `HoldingController::index2()` |
| **View counter** | ⚠️ `saveView()` fires on every detail-page hit — **crawlers will inflate view counts** | `HoldingController::index2():53` |
| **Environment** | `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://elibrary.test` | `.env` |

**Headline:** the biggest problem is *not* URLs. Right now **all ~7,600 record pages emit the
identical title and description**, which is the single most damaging on-page SEO issue — Google has
nothing to distinguish one record from another. Fixing that is also the cheapest change on this list.

---

## 2. Workstreams, in recommended order

### A. Per-page metadata *(highest value, lowest effort — do first)*
Make `master.blade.php` accept per-page values and give the record page real ones.

- Add `@yield('title')` / `@yield('meta_description')` / canonical / OG / Twitter to the OPAC layout,
  with the current static strings as **fallbacks** so other pages keep working.
- Record page (`opac/full-description.blade.php`) supplies:
  - **title:** `{title_statement} — DOST-PCAARRD eLibrary` (trim MARC/ISBD trailing punctuation, cap ~60 chars)
  - **description:** `summary` (or `general_note`) truncated to ~155 chars, HTML-stripped
  - **canonical:** the absolute record URL (one canonical form — see §3)
  - **og:image:** `getFrontpageLink($catalog_id)` (cover art already exists per record)
- Home / results / static pages get their own sensible titles.

> MARC caveat: titles arrive with ISBD punctuation (`Title /`, `Title :`) and are often ALL CAPS.
> Normalise (trim punctuation, title-case if desired) in one helper so titles, slugs and structured
> data all agree.

### B. Structured data — JSON-LD *(highest "rich result" upside)*
Emit one `<script type="application/ld+json">` per record page. For a research catalogue this is
what earns enhanced Google results.

Map `materials.name` → schema.org type:

| Material type | schema.org `@type` |
|---|---|
| Books, Reprints, Analytics | `Book` |
| Thesis/Dissertations | `Thesis` |
| Serials | `Periodical` |
| Technical Reports, Investigatory Projects, Vertical Files | `ScholarlyArticle` (or `CreativeWork`) |
| Non-Prints | `CreativeWork` (or media-specific) |

Fields available on `view_catalogs` today — no schema change needed:
`title_statement` → `name` · `joint_authors` → `author` · `publisher` → `publisher` ·
`pub_year` → `datePublished` · `isbn` → `isbn` · `issn` → `issn` · `summary` → `description` ·
`call_number`/`accession_number` → `identifier` · cover → `image`.

Also add **`BreadcrumbList`** (Home → Results → Record) and, on the homepage, an
**`Organization`/`WebSite`** block (with `SearchAction` for a sitelinks search box).

> `buildCitations()` (`app/Http/helper.php:556`) already normalises author/publisher/year for
> APA/MLA/Chicago — reuse that normalisation rather than re-deriving it.

### C. Sitemap + robots *(discovery — without this, deep records may never be crawled)*
- **`sitemap.xml`** generated from `ViewCatalog where is_published = 1 and deleted_at is null`
  (~7,600 URLs). Use a **sitemap index + chunks of ≤50k URLs** (one chunk is enough now, but the
  structure avoids a rewrite later). Include `<lastmod>` from `updated_at`.
  - Build as an artisan command (`sitemap:generate`) writing a static file, scheduled daily —
    cheaper and more reliable than rendering 7.6k rows per request.
  - Regenerate on publish/hold, or just let the daily run cover it.
- **`robots.txt`** — replace the permissive default with explicit rules:
  - `Allow` the record pages and results
  - `Disallow: /read/`, `/profile`, `/transactions`, `/inquiries`, `/to-review`, `/download`,
    `/search/suggest`, `/search/ai-answer`, `/admin`
  - `Sitemap: https://<domain>/sitemap.xml`
- Submit to **Google Search Console** at launch.

### D. URL slugs *(nice-to-have — do last, but before launch)*
Recommended shape — **ID-prefixed hybrid**:

```
/full-description/10435-fruit-color-as-an-indicator-of-seed-germination
                  ^^^^^ lookup key          ^^^^ decorative (humans + Google)
```

Why this shape for *this* catalogue:
- **Titles get edited** (MARC `245$a` is editable in the record editor) — an ID prefix means links
  never break when a cataloguer fixes a typo.
- **Duplicate titles are normal** in a library (editions/copies) — no uniqueness/collision handling
  needed, unlike pure slugs.
- **Preserves the architectural invariant:** lookup stays on numeric `catalog_id`, the join key the
  whole RAG stack depends on (LanceDB `catalog_id` predicates, `rag_sync_queue.catalog_id`, the
  Python worker's SQL). See `RAG_CATALOG_SYNC_PLAN.md`.
- **Possibly zero schema change:** derive the slug from `title_statement` at render time and parse
  the leading integer on lookup — no `slug` column, no 7.6k-row backfill.

Implementation notes:
- Keep `/full-description/{id}` working and **301 → the canonical slug URL** (protects the existing
  Share-button links and any bookmarks).
- If the slug portion doesn't match the current title, 301 to the correct one (self-healing canonical).
- Laravel's `#[RouteKey]` / `getRouteKeyName()` only affects **implicit route-model binding**, which
  this codebase does not use anywhere today (every route is `{id}` + a manual `where('id',…)->first()`).
  For the ID-prefixed shape you'd want `resolveRouteBinding()` rather than `#[RouteKey]`, and either
  way it requires adopting implicit binding first — a separate refactor with its own benefits
  (automatic 404s, cleaner controllers). **Not a prerequisite for A–C.**

---

## 3. Crawl hygiene / gotchas (found during the audit)

| Issue | Why it matters | Fix |
|---|---|---|
| ~~**`saveView()` on every detail hit**~~ **FIXED** | Googlebot would have added one view per record per crawl (~7,641 rows/sweep vs 70 rows of real data), swamping the homepage "Most Viewed" carousel and every per-record counter — and unrecoverably, since bot and human rows were indistinguishable | `isCrawler()` guard in `saveView()` (`app/Http/helper.php`); `views.user_agent` is now stored so any mis-classification stays auditable |
| **`/full-description` reachable 3 ways** (`POST`, `GET` no-id, `GET /{id}`) | Duplicate-content + wasted crawl budget | Canonical tag on the record page; the no-id GET already redirects to `/home` |
| **`/results` is faceted** (query, material type, year range, sort) | Faceted search = near-infinite URL space, thin/duplicate pages | `noindex, follow` on results pages (keep them crawlable for link discovery, out of the index) |
| **`/search/suggest`, `/search/ai-answer`** | JSON endpoints; the AI one is throttled and costs GPU | `Disallow` in robots.txt |
| **`/read/{id}`** | Gated content (patron-only, ban checks) | `Disallow`; never expose full text to crawlers |
| **AI mode answers** | Generated text on a crawlable page could read as thin/duplicate content | Keep AI answers out of server-rendered HTML (they're already an AJAX endpoint) |

---

## 4. Pre-launch checklist (environment)

- [ ] `APP_ENV=production`, **`APP_DEBUG=false`** (currently `local`/`true` — a debug-enabled public site leaks stack traces)
- [ ] `APP_URL` = the real **https://** domain (canonical/sitemap/OG URLs are all built from it)
- [ ] HTTPS + force-https redirect; one canonical host (www vs apex — pick one, 301 the other)
- [ ] `sitemap.xml` generated and reachable
- [ ] `robots.txt` updated with real rules + `Sitemap:` line
- [ ] Google Search Console verified; sitemap submitted
      (step-by-step, incl. where the verification code comes from: `DEPLOYMENT_CHECKLIST.md` D8 —
      the code is generated by Google, never invented, and is public rather than secret)
- [ ] Spot-check 5–10 record pages: unique title, unique description, valid JSON-LD
  (Google Rich Results Test), correct canonical
- [ ] Confirm unpublished/on-hold records 404 publicly (already enforced — re-verify after any URL change)

---

## 5. Rough effort

| Workstream | Effort | Value |
|---|---|---|
| A. Per-page metadata | Small (1 layout + 1 view + a helper) | **Highest** |
| B. JSON-LD structured data | Small–medium (1 partial + a type map) | High |
| C. Sitemap + robots | Small (artisan command + scheduled task) | High (discovery) |
| D. URL slugs | Medium (route + redirect + canonical + link updates) | Moderate |

A + B + C together are a modest change and cover most of the realistic gain.

---

## 6. Open decisions

- **Index `/results` at all?** Recommend `noindex, follow`. Confirm.
- **Slugs: worth it?** They're the smallest-value item here. Fine to ship A–C first and decide later —
  but if the answer is "yes", do it **before** launch, not after.
- **Adopt implicit route-model binding?** Independent refactor; only needed for the tidiest slug
  implementation, but has its own upside (automatic 404s, less boilerplate across ~48 manual lookups
  in `CatalogingController` alone).
- **Bot-exclusion for `saveView()`** — user-agent list, or only count logged-in views?
- **Domain shape** — apex vs `www`; final production hostname.
- **Multilingual?** If Filipino/English variants are ever served, `hreflang` enters the picture.

---

## 7. References / related

- `resources/views/opac/layout/master.blade.php` — the OPAC `<head>` (where A lands)
- `app/Http/Controllers/HoldingController.php::index2()` — record page controller
- `app/Http/helper.php` — `buildCitations()`, `getFrontpageLink()`, `getPDFLink()`, `saveView()`
- `RAG_CATALOG_SYNC_PLAN.md` — why `catalog_id` must stay numeric
- `MEILISEARCH_SETUP.md` — OPAC search (separate from crawler discovery)
- schema.org `Book` / `Thesis` / `Periodical` / `ScholarlyArticle`; Google Search Central docs on
  structured data, canonical URLs, and sitemaps
