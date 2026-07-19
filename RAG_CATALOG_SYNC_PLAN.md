# RAG ↔ Catalog Sync — Plan (feeding live catalog changes to Hermes)

**Status:** Functional end-to-end. Producer (Laravel) + worker (Python) + manual re-index UI +
**real LanceDB write/delete** + **reconciliation sweep** + **status badge** are all built and
verified against the live DB/index. Remaining is operational: scheduling the worker + nightly
reconcile, an optional processed-row purge policy, running the full initial backfill, and (later)
content-hash re-sync + a list-column badge.
**Last updated:** 2026-07-19

### Implemented so far
- **Producer (eLibrary, committed):** `rag_sync_queue` table (migration), `RagSyncItem` model,
  `App\Services\RagSync` (`enqueue` deduped / `enqueueMany` bulk, both never-throw). Enqueue wired
  into every catalog mutation (create/update/delete, publish/hold single+bulk+batch-toggle, import,
  batch-delete, full-text upload/remove).
- **Manual re-index UI (eLibrary):** `reindexRecord` / `reindexRecords` endpoints; a "Re-index for
  AI" button on the record view + a "Re-index AI" bulk action on the record list.
- **Worker scaffold (Project Y):** `rag/rag_sync_worker.py` — drains `rag_sync_queue`, is_published
  gate, resolves metadata (`view_catalogs`) + PDF (`multimedia` → `public/`), upsert/delete by
  catalog_id, marks done/error with bounded retries; `--once` / `--loop` / `--catalog-id` / `--stats`
  / `--dry-run`. Reuses the eLibrary `.env` for DB creds and the corpus BGE-M3 embedder. Verified in
  `--dry-run` against the live DB (enqueue → drain → done).
- **Real LanceDB write/delete (DONE):** `rag/lance_add_catalog_id.py` added a nullable `catalog_id`
  column to the 353,604-row `research` table (corpus rows → NULL; idempotent). Worker verified with a
  real BGE-M3 write + delete round-trip for one record (`--cpu` to coexist with llama-server) — rows
  land keyed by catalog_id and delete-by-catalog_id removes them, corpus intact. New deps: `pymysql`
  (uv-managed venv — `uv pip install pymysql`).
- **Reconciliation sweep (DONE):** `rag_sync_worker.py --reconcile` diffs published catalog_ids (DB)
  vs indexed (LanceDB, native filtered scan) and enqueues the drift (`reason=reconcile`); run a drain
  to apply. Presence-based; content-hash re-sync is a later refinement. Nightly = `--reconcile` then
  a drain, in the GPU window.
- **Status badge (DONE):** `App\Services\RagSync::statusFor()` (is_published + outbox state) →
  Hidden from AI / Queued / AI-indexed / AI index failed / Removed / Not queued; shown on the record
  View page. (A per-row list badge would need a recordListJson join — later.)

**Scope:** how newly **created / edited / deleted / imported / (un)published** catalog materials
reach the Project Y RAG index so the **Hermes agent** (`search_docs` MCP) can answer over them.
This is the *sync/feed pipeline* — distinct from `RAG_INTEGRATION_PLAN.md`, which is the one-time
build of the RAG over the ~8,823-PDF corpus. Both write to the **same index, keyed by `catalog_id`.**

**Systems involved:** eLibrary (Laravel, Windows/Laragon) · RAG pipeline (Python, WSL2, LanceDB +
BGE-M3) · shared **MySQL** · shared `public/` storage (PDFs, covers). Join key: **`catalog_id`**.

---

## 1. Core decision: an outbox table, NOT a synchronous push
Do **not** have Laravel call the RAG service directly on save.

- The RAG pipeline **goes offline during GPU work** (llama-server serving Qwen uses ~13.5 GB of
  16 GB; OCR/embedding needs it stopped) — a synchronous push would fail exactly when indexing runs.
- **Batch imports** (and the corpus backfill) would hammer any synchronous endpoint.

Instead, use a **shared-DB outbox**:

- **Laravel writes change events** to a `rag_sync_queue` table in the shared MySQL.
- **A Python worker (RAG venv, WSL2) polls** that table, processes each `catalog_id`, marks it done.

Why this fits *this* stack:
- Both sides already share MySQL → **zero new infrastructure** (no Redis, no new HTTP API), and it
  reuses the existing **`catalog_id` join-key** design.
- **Survives RAG downtime** — events queue while the GPU is busy and drain later.
- **Decouples request latency** — saving a record never waits on embeddings.
- Note: don't reuse Laravel's `jobs` table — those are consumed by PHP workers, not Python. A
  dedicated `rag_sync_queue` the Python side owns is the clean cross-language contract.

### Proposed `rag_sync_queue` shape
| column | purpose |
|---|---|
| `id` | pk |
| `catalog_id` | the material to (re)index or remove |
| `action` | `upsert` \| `delete` |
| `reason` | `created` \| `updated` \| `deleted` \| `imported` \| `published` \| `unpublished` \| `manual` |
| `content_hash` | hash of searchable content at enqueue time (skip no-op re-index) |
| `created_at` / `processed_at` | queue + completion timestamps |
| `attempts` / `error` | retry + failure surface |

## 2. Reconciliation backstop (non-negotiable)
Add a **nightly reconciliation sweep**: the Python side diffs `catalogs.updated_at` / `content_hash`
against what's indexed and fixes drift. This is the self-healing layer.

Rationale from hard experience: the OPAC already drifts when **bulk ops bypass model events**
("bulk ops bypass Scout → stale index → fix = `scout:flush` + `scout:import`"). Same trap here —
so **bulk/import/publish paths must enqueue to the outbox explicitly**, and the reconciliation
catches anything still missed.

## 3. How each change type flows
- **Create** → observer enqueues `upsert`. Worker reads metadata from `view_catalogs` + the PDF from
  `public/` storage → extract / chunk / embed → LanceDB.
- **Edit** → enqueue `upsert` with a fresh `content_hash`. Worker **skips if the hash is unchanged**
  (title + summary + subjects + PDF hash) so a copy-edit doesn't burn GPU. If the PDF changed →
  re-OCR + re-embed.
- **Delete (soft)** → enqueue `delete`. Worker removes that `catalog_id`'s chunks (LanceDB
  delete-by-predicate `where catalog_id = X`). Mirrors the OPAC's `unsearchable()` logic.
- **Import (batch)** → the importer writes outbox rows in **bulk** for all new `catalog_id`s (this is
  the path that would otherwise silently drift). The ~8,823-PDF corpus is a one-time bulk backfill
  into the same index.
- **Publish / hold toggle** → treat like upsert / delete for *visibility*. **Only `is_published`
  materials should be searchable by Hermes** (mirror OPAC visibility) — otherwise the agent cites
  records the public can't see. Ideally the publish action *is* what flips RAG visibility.

## 4. Two content tiers — index both
- **Metadata (always):** title, author, 520 summary, 650 subjects, publisher, year. Cheap, no OCR,
  lets Hermes answer "what do you have on rubber tapping?" even for records with no PDF.
- **Full text (when a PDF exists):** the heavy OCR → chunk → embed pipeline from `RAG_INTEGRATION_PLAN.md`.

Keep **one index keyed by `catalog_id`**; each chunk carries enough metadata to run
`buildCitations()` and link back to the record's view page — so Hermes cites *[Title, Author, Year]*
with a working link. Closes the provenance loop.

## 5. GPU-contention cadence
- **Small metadata edits** → embed on **CPU** (BGE-M3 on one short doc is sub-second) → process
  anytime, even while llama-server serves.
- **New / changed PDFs (OCR + full embed)** → **defer to a scheduled batch** (overnight,
  llama-server stopped). The outbox holds these until the window.
- **Stable, deterministic chunk IDs** (`catalog_id:doc_hash:chunk_idx`) → re-index = delete that
  catalog's chunks then insert. Idempotent, no duplicates, no stale chunks when a doc shrinks.

## 6. UI / UX
Subtle for catalogers, richer for the Project Y admin.

- **Per-record "AI index" status badge** (record view + list), reusing the publish/Scout mental
  model: `Indexed` · `Queued` · `Failed` · `— no full text` · `Not published (hidden from AI)`. The
  single most useful bit of feedback.
- **Manual "Re-index for AI" button** on the record view (after fixing OCR or a missed sync), plus a
  **bulk "re-index selected/all"** on the list — the human override for drift, mirroring the
  `scout:import` workflow.
- **Set async expectations, don't block:** a toast like "Queued for AI indexing — full-text may take
  until the next indexing run," not a blocking spinner. Indexing is eventual; say so.
- **Make publish → searchable explicit** so nobody wonders why a held record isn't answerable.
- **Show content tier:** "searchable by full text" vs "metadata only" — sets expectations for what
  Hermes can answer.
- **Small admin RAG dashboard:** index counts, queue depth, last reconcile time, failures — ties to
  the RAG plan's SQLite manifest. Keep it off the everyday cataloger's screen.

## 7. Recommended concrete shape
1. `rag_sync_queue` table + Eloquent observers on `Catalog` / `ViewCatalog` / `Multimedia`; the
   import + publish paths enqueue **explicitly** (don't trust events alone).
2. Python worker: poll queue → per `catalog_id` upsert / delete into LanceDB; CPU for metadata,
   batched GPU window for OCR / full-text; **content-hash skip**.
3. **Nightly reconciliation sweep** as the backstop.
4. **`is_published` gate** + **`catalog_id` provenance** on every chunk.
5. UI: status badge + manual re-index (single & bulk); publish drives visibility.

## 8. Open decisions (for later)
- One shared index for corpus + catalog (recommended), or keep the legacy corpus separate?
- Poll interval + who runs the worker (systemd/cron in WSL2 vs. Windows Task Scheduler triggering it).
- Do metadata-only records go in the same LanceDB table as full-text chunks, or a sibling table?
- Real-time-ish (short poll + CPU metadata) vs. purely nightly batch — depends on how fresh Hermes
  answers need to be.
- Hard-delete chunks vs. a query-time `is_published` / `deleted` filter in LanceDB.

## 9. References / related
- `RAG_INTEGRATION_PLAN.md` — the corpus build (OCR → chunk → embed → LanceDB, `search_docs` MCP).
- eLibrary RAG-readiness: `catalog_id` join key, `public/` storage, `buildCitations()`.
- Scout/Meilisearch drift lesson (bulk ops bypass model events) — the reason reconciliation + explicit
  bulk enqueue are required.
- GPU contention: llama-server ~13.5 GB of 16 GB; indexing/OCR runs with it stopped.
- Hermes `search_docs` MCP + the locked `locked-rag` profile (corpus-search only).
