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
npm install && npm run build

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
- Submit `https://<domain>/sitemap.xml` in **Google Search Console**.

---

## 7. Known gaps / follow-ups

- **Logon scheduled tasks are not registered** — needs one elevated run of
  `register-projecty-tasks.ps1`. Until then AI mode is down after every reboot.
- ~~**SEO workstream D (URL slugs)**~~ — **done**: record URLs are now
  `/full-description/{id}-{title-slug}`, with bare-id and stale-slug URLs 301ing to the canonical
  form. **Re-run `php artisan sitemap:generate` after deploying** so the sitemap carries slug URLs.
- **Search results link to records by POST, not `<a href>`** (`submitFrm()` in the result views), so
  crawlers cannot reach record pages by following links — the sitemap is currently the only
  discovery path. Converting result items to real anchors would strengthen internal linking.
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
