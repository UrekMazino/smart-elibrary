# eLibrary — Deployment Checklist & Change Record

**What this is:** the ordered steps to deploy *this app*, the configuration it needs, and a record of
everything changed in the 2026-07 work cycle — **including the files outside this repo**, because the
AI features only work when both halves are deployed together.

> **Not to be confused with** `hermes-with-rag/DEPLOYMENT_RUNBOOK.md`, which rebuilds the whole stack
> from a bare machine (Hermes → llama.cpp → RAG corpus → this app). *That* is the from-scratch guide;
> **this** is the per-deploy checklist for the eLibrary plus the pieces it depends on.

---

## 1. The two halves

| Repo | Path (dev) | Role |
|---|---|---|
| **smart-elibrary** (this) | `C:\laragon\www\elibrary` | Laravel ILS/OPAC. Produces catalogue data + the RAG sync queue. |
| **hermes-with-rag** | `C:\Users\jcvia\PyCharmMiscProject\ProjectY` | AI stack: llama.cpp, RAG index (LanceDB), the sync worker, the AI gateway. |

They share **one MySQL database** (`elibrarydb`) and the eLibrary **`.env`** — the Python side reads
`HERMES_GATEWAY_TOKEN` and the DB credentials straight out of it.

---

## 2. Deploy steps (this app)

```bash
# 1. code + dependencies
git pull
composer install --no-dev --optimize-autoloader
npm install && npm run prod                           # there is no "build" script (Laravel Mix)
php artisan storage:link                              # public/thumb, pdf, news_cover, ... -> storage/app/public

# 2. database  — see the landmine note below before running this
php artisan migrate:status          # confirm the baseline migrations show "Ran"
php artisan migrate --force         # applies only NEW migrations

# 3. search indexes
php artisan scout:flush "App\Models\ViewCatalog"
php artisan scout:import "App\Models\ViewCatalog"     # Meilisearch (keyword search)

# 4. SEO artefacts  — must run AFTER APP_URL is the real https domain
php artisan sitemap:generate                          # writes sitemap*.xml + robots.txt

# 5. caches
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan permission:cache-reset                    # roles/permissions are cached for 24h
```

> ⚠️ **Migration landmine.** The migration history contains `2025_09_15_114348_drop_all_tables`.
> On an **existing** database it has already run and `migrate` will not re-run it — safe. But
> **never run `migrate:fresh` against production**, and on a fresh database seed from
> `database/sql/elib_new.sql` + `db:seed` rather than migrating from zero.

### Required `.env` for production
| Key | Value | Why |
|---|---|---|
| `APP_ENV` | `production` | — |
| `APP_DEBUG` | **`false`** | currently `true` in dev; a debug-enabled public site leaks stack traces |
| `APP_URL` | `https://<real-domain>` | canonical tags, sitemap URLs and OG tags are all built from it |
| `MEILISEARCH_*` | host/key | OPAC keyword search |
| `HERMES_GATEWAY_URL` | `http://127.0.0.1:8090` | AI mode gateway |
| `HERMES_GATEWAY_TOKEN` | shared secret | **must match** what `start-rag-api.ps1` uses |

Also: HTTPS + force-https, one canonical host (www **or** apex, 301 the other), and a cron entry for
the scheduler — `* * * * * php artisan schedule:run` — or the daily sitemap refresh never happens.

---

## 3. The AI half (hermes-with-rag) — required for AI mode

Nothing here auto-starts unless the logon tasks are registered.

```powershell
cd C:\Users\jcvia\PyCharmMiscProject\ProjectY
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass -Force

.\start-llama-server.ps1      # :8080  the model (AI mode + Discord bot both need it)
.\start-rag-api.ps1           # :8090  the AI-mode gateway (foreground; keep the window open)
.\start-sync-worker.ps1       # drains the sync queue every 30s
```

**One-time on a new machine:**
```powershell
cd rag
uv venv .venv
uv pip install --python .venv\Scripts\python.exe -r requirements.txt
.\.venv\Scripts\python.exe lance_add_catalog_id.py       # adds catalog_id to the LanceDB table
.\.venv\Scripts\python.exe rag_sync_worker.py --reconcile
.\.venv\Scripts\python.exe rag_sync_worker.py --once --limit 8000 --cpu   # initial backfill
```

**Make it survive reboots** (run **elevated**, once):
```powershell
powershell -ExecutionPolicy Bypass -File .\register-projecty-tasks.ps1
```

Details: `hermes-with-rag/CATALOG_SYNC_RUNBOOK.md`, `MANUAL_REINDEX_PROCEDURE.md`,
`STARTING_HERMES_GUIDE.md`.

---

## 4. State that lives OUTSIDE git

These are real changes that a `git pull` will **not** give you. They must be applied per environment.

| Change | Where | How to apply |
|---|---|---|
| `rag_sync_queue` table | `elibrarydb` | `php artisan migrate` (migration is in this repo) |
| **`catalog_id` column on the LanceDB `research` table** | `rag/lancedb/` | `python lance_add_catalog_id.py` (idempotent) |
| ~50,889 catalogue chunks indexed (7,640 records) | `rag/lancedb/` | `--reconcile` + drain (above) |
| `pymysql` in the RAG venv | `rag/.venv` | `uv pip install -r requirements.txt` |
| **MARC seed constants corrected** | `elibrarydb` | re-seed the 3 MARC tables from `database/seeders/data/*.json` |
| Generated `sitemap*.xml` | `public/` | `php artisan sitemap:generate` (gitignored on purpose) |
| Material Type repair for records saved via Create/Edit/Batch before the fix (Record History + Reports showed the LDR/06 type, e.g. "Language material") | `elibrarydb.catalogs` | `php artisan catalog:sync-material-types` (dry run, shows the changes) then `--apply`. Idempotent. |
| Full MARC 245 titles for existing records (lists/OPAC/search/covers showed only `245$a`, e.g. "… monograph series :") | `elibrarydb.view_catalogs`, `copies`, covers | `php artisan migrate` first (widens title columns to TEXT), then `php artisan catalog:rebuild-titles` (dry run) and `--apply` — also re-syncs search, queues RAG, regenerates placeholder covers. Idempotent. |
| `public/{thumb,pdf,fullpage,news_cover,marc_file,marc_file_uploaded}` links | `public/` → `storage/app/public/*` | `php artisan storage:link`. The link *targets* (uploaded covers, PDFs) live in `storage/app/public` and are per-server data — back them up. The app creates a missing target folder on first write. |

> The MARC fixes (mojibake in `marctag_indicators_loc` #243, malformed `388` indicator ref, `842 `
> trailing space, 25 duplicate subfields) were applied to **both** the seed JSON *and* the live dev
> database. A different environment gets them only by re-seeding those tables.

---

## 5. Changes in this work cycle

### smart-elibrary (14 commits, `37afbb8`…`8eb1a08`)

| Area | Files |
|---|---|
| **RAG sync producer** | `database/migrations/2026_07_19_000000_create_rag_sync_queue_table.php`, `app/Models/RagSyncItem.php`, `app/Services/RagSync.php`, `app/Http/Controllers/CatalogingController.php` (enqueue on every mutation) |
| **AI Sync Monitor** | `resources/views/admin/rag-sync-monitor.blade.php`, `routes/joe.php`, `resources/views/admin/layouts/master.blade.php` (nav) |
| **Run sync now / re-index UI** | `app/Services/AiSearchService.php` (`drainSyncQueue`), `resources/views/admin/catalog-view-record.blade.php`, `catalog-record-list.blade.php` |
| **SEO A — metadata** | `resources/views/opac/layout/master.blade.php`, `full-description.blade.php`, `index.blade.php`, + 11 views marked `noindex` |
| **SEO B — JSON-LD** | `app/Http/helper.php` (`seoRecordJsonLd`, `seoSiteJsonLd`, `seoSchemaType`, …) |
| **SEO C — sitemap/robots** | `app/Console/Commands/GenerateSitemap.php`, `app/Console/Kernel.php`, `public/robots.txt`, `.gitignore` |
| **Docs** | `RAG_CATALOG_SYNC_PLAN.md`, `OPAC_SEO_PLAN.md`, `Z3950_SRU_INTEGRATION_PLAN.md`, `README.md`, this file |

### hermes-with-rag (8 commits, `29b3f32`…`267d762`)

| Area | Files |
|---|---|
| **Sync worker** | `rag/rag_sync_worker.py` (poll → gate on `is_published` → upsert/delete; `--once/--loop/--reconcile/--cpu/--dry-run`) |
| **LanceDB migration** | `rag/lance_add_catalog_id.py` |
| **Freshness fix** | `rag/rag_mcp_server.py` — `_refresh_tbl()` / `checkout_latest()`; without it a long-running gateway never sees rows another process wrote |
| **Drain endpoint** | `rag/rag_api.py` — `POST /sync/drain` (reuses the loaded BGE-M3; no model reload) |
| **Ops scripts** | `start-sync-worker.ps1`, `register-projecty-tasks.ps1` |
| **Reproducibility** | `rag/requirements.txt` (170 pinned packages) |
| **Docs** | `DEPLOYMENT_RUNBOOK.md`, `CATALOG_SYNC_RUNBOOK.md`, `MANUAL_REINDEX_PROCEDURE.md`, `STARTING_HERMES_GUIDE.md` |

---

## 6. Post-deploy verification

```bash
php artisan sitemap:generate                 # expect: "Wrote N record URLs ... + sitemap.xml"
curl -s https://<domain>/robots.txt          # rules + a Sitemap: line on the real domain
curl -sI https://<domain>/sitemap.xml        # 200
curl http://127.0.0.1:8090/healthz           # AI gateway alive
```
- Open a record page → **view source**: unique `<title>`, unique `<meta name="description">`,
  `<link rel="canonical">`, and one `application/ld+json` block. Paste it into Google's
  **Rich Results Test**.
- Admin → **AI Sync Monitor**: pending trends to 0; a record's badge reads **AI-indexed**.
- OPAC → **AI mode**: ask something answerable only from a catalogue record; expect a cited answer.
- Confirm an **on-hold** record 404s publicly and is absent from `sitemap-records-*.xml`.
- Verify the site in **Google Search Console** and submit the sitemap — see §6.1.

### 6.1 Google Search Console verification (launch step)

Search Console is where you confirm the SEO work actually landed: index coverage across the ~7,600
records, which queries bring people in, whether the JSON-LD is read as rich results, and crawl
errors. **You cannot submit the sitemap until the site is verified.**

Verification is a **proof of ownership**. Google will not show a site's data to just anyone, so it
asks you to place a code it generated on the site — only someone with access could do that.

> **The code comes from Google. Do not invent one.** An arbitrary string simply fails verification
> (nothing breaks, it just never verifies). It is also **not a secret** — it is public in the page
> source by design, so it is safe to commit and share.

1. Sign in to <https://search.google.com/search-console> with an **institutional** Google account,
   not a personal one — the account that adds the property owns it, and you want that to survive
   staff changes. Add colleagues as users afterwards.
2. **Add property → URL prefix**, and enter the real public address
   (`https://<domain>`, not `elibrary.test`).
3. Choose the **HTML tag** method. Google shows a full tag:
   ```html
   <meta name="google-site-verification" content="ZxY9_aBc123dEfGhIjK4LmNoPqRsTuVwXyZ0" />
   ```
4. Copy **only the value inside `content="…"`** — not the whole tag.
5. Paste it into **Admin → Settings → Google Search Console verification code**, then Save. It is
   rendered into every public page's `<head>` (`opac/layout/master.blade.php`; blank = tag omitted).
6. Back in Search Console, click **Verify**.
7. Then **Sitemaps → submit** `https://<domain>/sitemap.xml`.

**Timing:** steps 1–5 can be done any time, but **step 6 only works once the OPAC is publicly
reachable at that domain** — Google verifies by fetching the homepage, and it cannot reach a local
host. Leave the code in place permanently: Google re-checks periodically and un-verifies if it
disappears.

> Alternative: a **DNS TXT record** verifies the whole domain including subdomains and does not
> depend on the app. Use that instead if you control DNS and would rather not tie verification to
> a Settings field.

---

## 7. Known gaps / follow-ups

- **Logon scheduled tasks are not registered** — needs one elevated run of
  `register-projecty-tasks.ps1`. Until then AI mode is down after every reboot.
- ~~**SEO workstream D (URL slugs)**~~ — **done**: record URLs are now
  `/full-description/{id}-{title-slug}`, with bare-id and stale-slug URLs 301ing to the canonical
  form. **Re-run `php artisan sitemap:generate` after deploying** so the sitemap carries slug URLs.
- ~~**Search results link to records by POST, not `<a href>`**~~ — **fixed**: result items (grid and
  list) are now real `<a href>` links to the canonical slug URL, so crawlers can reach record pages
  by following links instead of relying on the sitemap alone. The dead `submitFrm()`/`sendFrm()`
  helpers and their POST forms were removed. `POST /full-description` is kept as a graceful
  fallback — it redirects to the canonical URL.
- ~~**`saveView()` counts crawler hits**~~ — **fixed**: `isCrawler()` guards `saveView()` and
  `views.user_agent` is now recorded (migration `2026_07_28_000000_add_user_agent_to_views_table`).
- **Full-text coverage is effectively complete — no OCR backlog.** Measured 2026-07-28: **7,479 of
  7,641 published records (97.9%)** already have OCR'd full text in the index, via the Stage-3
  corpus, which resolves to catalogue records through `accession_number` ↔ corpus `stem`
  (`AiSearchService::resolveSources()`). Only **2** records have a PDF with no coverage; 160 have no
  PDF at all, so nothing to OCR.
  > The sync worker's `[metadata-only]` log line means *this sync wrote metadata chunks* — **not**
  > that the record lacks full text. The index has two complementary layers: OCR'd page text
  > (corpus, keyed by `stem`) and clean bibliographic metadata (keyed by `catalog_id`).
- **Z39.50 / SRU copy cataloguing** — planned only (`Z3950_SRU_INTEGRATION_PLAN.md`).
