# eLibrary × Project Y RAG — Integration Plan

**Purpose:** a living blueprint for connecting this eLibrary (Laravel catalog + OPAC) with the
**Project Y** local RAG stack, and a set of principles so every feature we build stays
"RAG-ready". Read this before adding features that touch full-text, search, or metadata.

---

## 1. The two systems (as of 2026-07)

**eLibrary (this repo)** — Laravel 13 app on Laragon/Apache. Catalog records (MARC + aggregated
`view_catalogs`), per-record **full-text PDFs** (`storage/app/private/pdf`, private since Oct 2026), cover photos + thumbnails,
APA/MLA/Chicago **citations** (`buildCitations()`), and an OPAC with **Meilisearch** instant
(keyword) search. Domain: Philippine agriculture/forestry research (FPRDI/DOST).

**Project Y RAG** — fully-local AI stack on the same machine (Windows 11, RTX 4080 Super):
- **llama.cpp** serving **Qwen3-30B** (OpenAI-compatible, `127.0.0.1:8080`, behind an nginx
  basic-auth proxy on `:80`, realm "Project Y").
- A **`docs` MCP server** (`rag/rag_mcp_server.py`) exposing **`search_docs`** over a **LanceDB**
  vector store (~353k chunks from 8,823 OCR'd PDFs) with **BGE-M3** embeddings + **bge-reranker-v2-m3**,
  hybrid dense+FTS RRF. Consumed today by a locked-down Hermes Discord bot.

**Key gap for integration:** the RAG's retrieval is exposed as an **MCP tool (stdio)**, not HTTP.
A web app can't call stdio. The integration seam is a **small HTTP API in front of the RAG's
existing search function**.

---

## 2. Vision

Turn the eLibrary from a keyword catalog into a **semantic, cited, AI-augmented digital library**:
a patron can *ask a question* and get an answer **synthesized from the library's own full-text**,
with each claim **cited back to a real catalog record** (APA/MLA/Chicago). Keyword search
(Meilisearch) and semantic search (RAG) coexist — one for known-item lookup, the other for
"what does the collection say about X".

---

## 3. Integration architecture

```
  Browser (OPAC / Admin)
        │  HTTPS (elibrary.test)
        ▼
  Laravel (eLibrary)  ──Guzzle, server-side──►  RAG HTTP API  ──►  LanceDB + BGE-M3 + reranker
        │                                        (new: FastAPI      (existing rag_mcp_server
        │                                         wrapper, 127.0.0.1:8090)   search internals)
        │                                              │
        └── citation bridge: RAG hit.catalog_id ──────┘──►  llama-server (8080) for answer synthesis
             → view_catalogs → buildCitations()
```

**The seam — a thin RAG HTTP service (new, ~1 file):**
- Wrap the existing retrieval in `rag/rag_mcp_server.py` (`_search_sync`) in a FastAPI app:
  - `POST /rag/search  {query, k, filters?}` → `{hits:[{catalog_id, doc, page, score, snippet}], took_ms}`
  - `POST /rag/answer  {query, k}` → retrieves, prompts llama-server, returns `{answer, sources:[…]}`
- Bind **`127.0.0.1:8090`**, localhost-only (like the rest of Project Y). Optionally token-guarded.
- **Reuse, don't fork:** it imports the same LanceDB/BGE-M3/reranker code the MCP server uses, so
  Hermes (Discord) and eLibrary hit one retrieval implementation.

**Why not call llama-server directly from Laravel?** Retrieval needs the vector store (LanceDB +
GPU embeddings); Laravel can't do that. The RAG service owns retrieval; Laravel just calls it.

**GPU contention (important):** llama-server uses ~13.5 GB VRAM. Query-time embeddings + rerank of a
handful of candidates run on CPU (sub-2s) so they coexist with generation. Heavy re-indexing jobs
must stop llama-server first (schedule off-hours). This matches the Project Y ops rules.

---

## 4. Integration points (features), in priority order

1. **"Ask the Library" (RAG Q&A)** — an OPAC box → `POST /rag/answer` → a synthesized, **cited**
   answer + source cards linking to `/full-description/{catalog_id}`. The flagship.
2. **Semantic search** alongside Meilisearch results — `POST /rag/search`; show a "related passages"
   panel on the results page. Keyword stays primary for known-item lookup.
3. **Citation bridge** — every RAG hit carries `catalog_id`; Laravel maps it via `view_catalogs`
   → `buildCitations()` so answers cite **real bibliographic records**, not filenames.
4. **Corpus sync** — when a full-text PDF is added/updated in the eLibrary, enqueue it for RAG
   ingestion (OCR if scanned → chunk → embed → upsert into LanceDB, tagged with `catalog_id`).
   The eLibrary becomes the **management UI / source of truth** for the corpus.
5. **Admin: "find duplicates / related works"** — semantic similarity over the catalog for
   cataloguers.

---

## 5. Data flow

**Ingestion (write path):** PDF uploaded in `/admin/view-record` → stored `storage/app/private/pdf/ft-*.pdf`
(done) → *(new)* job posts `{catalog_id, path, metadata}` to the RAG ingest endpoint → OCR/extract
→ chunk → BGE-M3 embed → **upsert into LanceDB with `catalog_id` + page in each chunk's metadata**.

**Query (read path):** patron question → Laravel → `POST /rag/answer` → RAG retrieves top-k chunks
(each has `catalog_id`) → llama-server synthesizes an answer → Laravel resolves each source
`catalog_id` → `view_catalogs` → `buildCitations()` → renders answer + cited source cards.

---

## 6. RAG-readiness principles (apply to ALL new eLibrary work)

Build every feature so it *feeds* or *consumes* the RAG cleanly:

1. **Stable `catalog_id` correlation everywhere.** PDFs, covers, thumbnails, and (future) RAG chunks
   all key off `catalog_id`. Never invent per-feature IDs. (Already the pattern for cover/pdf/thumb.)
2. **Full-text must be extractable.** Prefer text-layer PDFs; scanned uploads need OCR before they're
   useful to the RAG. Consider capturing/flagging "scanned vs text" at upload time.
3. **Clean bibliographic metadata is the citation + RAG-metadata source.** `title_statement`,
   `joint_authors`, `publisher`, `pub_year`, `edition` drive `buildCitations()` **and** the
   `catalog_id`→citation bridge. Keep cataloguing tight.
4. **`buildCitations()` is the single citation authority.** Any RAG answer that cites a work uses it.
   Plan a `GET /api/citation/{catalog_id}` endpoint (thin wrapper) for the RAG service to call.
5. **Serve everything the RAG needs over HTTP/JSON.** We already have `/search/suggest`; RAG adds
   `/rag/*` and `/api/citation/*`. Keep them localhost-callable and stateless.
6. **Store uploads under `public/` predictably** (done for covers/PDF/news) so an ingest job can
   read them by path without symlink surprises.
7. **Keyword (Meilisearch) and semantic (RAG) are complementary, not competing** — don't collapse
   one into the other; route the query to the right engine.

---

## 7. Phased roadmap

- **Phase 0 — Foundations (in progress / done):** predictable `public/` storage, `catalog_id`
  correlation, `buildCitations()`, Meilisearch instant search, JSON endpoints. ✅ mostly done.
- **Phase 1 — RAG HTTP service:** wrap the existing LanceDB retrieval in a FastAPI app on
  `127.0.0.1:8090` (`/rag/search`, `/rag/answer`). No eLibrary changes yet; test with curl.
- **Phase 2 — Citation bridge + read path:** ensure RAG chunks carry `catalog_id`; add
  `/api/citation/{catalog_id}`; build the Laravel client (Guzzle) + "Ask the Library" OPAC UI.
- **Phase 3 — Corpus sync (write path):** on PDF upload, enqueue RAG ingestion (OCR→chunk→embed→
  upsert with `catalog_id`). Backfill existing full-text.
- **Phase 4 — Polish:** semantic "related passages" on results, admin similarity tools, caching,
  answer-quality tuning (chunk size, k, rerank).

---

## 8. Open questions / decisions to make

- **Corpus identity:** is the eLibrary's full-text a *new* corpus, or does it merge with the
  existing 8,823-PDF LanceDB? (Recommend: a dedicated `elibrary` LanceDB table/namespace, keyed by
  `catalog_id`, kept separate from the research-dump corpus.)
- **Mapping existing RAG chunks → catalog_id:** the current corpus was ingested by filename; if we
  want those cite-able, we need a filename→catalog_id map (or re-ingest via the eLibrary).
- **Auth between Laravel and the RAG service:** localhost-only may suffice; if not, a shared token.
- **Sync trigger:** synchronous on upload (slow) vs a queued job (recommended — `QUEUE_CONNECTION`).
- **Answer trust/safety:** the RAG must **abstain** when the corpus lacks an answer (Project Y
  already tunes for anti-fabrication + citation-first); surface "no supported answer" in the UI.

---

## 9. What we've already built with this in mind
- **Predictable full-text storage** (`storage/app/private/pdf`, located by `multimedia.FileLocation`) — directly ingestible.
- **`buildCitations()`** — dependency-free, HTML-escaped, ready to back `/api/citation`.
- **`catalog_id` correlation** across covers/thumbnails/PDF — the join key the RAG will use.
- **Meilisearch instant search** — the keyword half of the hybrid search story.
- **JSON endpoint pattern** (`/search/suggest`) — the shape RAG endpoints will follow.

## 10. Conversational "Ask the Library" via Hermes — endpoint contract (Discord-parity)

**Principle (copied from the Discord bot):** **Hermes is the brain.** It runs the agent loop, owns
`search_docs`, and enforces the `locked-rag` guardrails (corpus-only, anti-fabrication,
prompt-extraction hardening). The eLibrary chat is just **another transport** in front of the same
Hermes — exactly like the Discord adapter. Laravel ships a message in and renders the reply out; it
does **not** re-implement orchestration or safety.

This *refines* §3: there are **two seams**, for two different jobs:
- **Retrieval-only** — `POST /rag/search` (§3): the non-conversational "related passages" panel next
  to Meilisearch. Stateless, fast, no LLM, no agent.
- **Conversational agent** — this section: the flagship "Ask the Library" chat. Routes **through
  Hermes**, inheriting the hardened agent. **Do not** build a second answer-orchestrator in Laravel
  or in the FastAPI wrapper.

Both seams can live in **one HTTP service** (extend the §3 Phase-1 FastAPI app with an `/agent/*`
route family that drives Hermes) — one process, two seams.

### 10.1 Topology
```
Browser chat (OPAC) ── HTTPS, patron-authenticated
   ▼
Laravel (eLibrary)              owns: auth, conversation store, UI, citation bridge
   │  server-side POST · localhost · + shared token
   ▼
Hermes HTTP gateway (new: 127.0.0.1, private)   ── a web transport beside the Discord one
   ▼
Hermes agent ─► search_docs ─► LanceDB (BGE-M3 + reranker)
            └─► Qwen (llama-server :8080) for synthesis
```
The gateway is a thin HTTP entry to the **existing** Hermes agent — not a new brain.

### 10.2 Endpoint contract — `POST /agent/chat` (localhost-only, token-guarded)
Request (Laravel → Hermes):
```
{ "conversation_id": "<uuid, Laravel-owned>",
  "message": "<user question>",
  "history": [ {"role":"user|assistant","content":"…"}, … ],  // recent turns, Laravel-supplied
  "profile": "elibrary-rag",        // locked-rag profile for the web surface
  "user_ref": "patron:{id}|guest",  // opaque, for rate-limit/abuse only — no PII
  "stream": true }
```
Response (final JSON, or terminal SSE event):
```
{ "answer": "<synthesized, corpus-grounded text>",
  "sources": [ {"catalog_id": 123, "page": 7, "score": 0.82, "snippet": "…"}, … ],
  "abstained": false,               // true when the corpus can't support an answer
  "usage": {"tokens": …, "took_ms": …},
  "conversation_id": "…" }
```
- **`sources[].catalog_id` is mandatory** — the citation join key. A source with no catalog_id is
  returned flagged so Laravel hides/down-ranks it (never cite a filename).
- **`abstained`** surfaces the anti-fabrication behaviour as a UI empty-state.

### 10.3 Auth & exposure
- Hermes gateway binds **`127.0.0.1` only** (never public), same host as eLibrary + llama-server.
- Laravel is the **only** internet-facing surface; it calls Hermes server-side (Guzzle) with a
  **shared secret** (`HERMES_GATEWAY_TOKEN` in `.env`, sent as a header; rotatable).
- The browser never talks to Hermes; the token never reaches the client.

### 10.4 Session / conversation ownership
- **Laravel owns conversations** (it has auth + DB + chat UIs already). Store turns in a
  `rag_conversations`/`rag_messages` pair (or reuse a chat table); the UI shows + revisits history.
- Laravel sends `conversation_id` + the **last N turns** as `history`; **Hermes stays request-scoped**
  — no server-side memory to sync, no cross-user leakage. N is a token-budgeted window.
- Benefit: history, search, moderation, deletion all live where auth + audit already are.

### 10.5 Concurrency & the single GPU (the real new constraint vs Discord)
- Discord traffic is bursty/low; a public web chat can have many people press "send" at once, and
  **each request is a full Qwen generation on one 4080**.
- Put a **concurrency limit + queue in front of Hermes**: max **N in-flight** generations (start
  N=1–2, measure), queue the rest with a "thinking…/waiting…" UI, and shed load past a max queue
  depth rather than thrash the box.
- **Rate-limit at the Laravel edge too** (per patron/IP — reuse the `throttle` pattern from the
  inquiry/reservation chat).
- Keep the §3 rule: heavy re-indexing stops llama-server → chat shows "temporarily unavailable".

### 10.6 Streaming UX
- Local Qwen isn't instant → **stream tokens** (feels responsive). Transport: **SSE**
  Hermes → Laravel → browser (simplest, one-way); the **final SSE event carries `sources[]` +
  `abstained`** so citations render after the text.
- Fallback: `stream:false` → a single JSON response (same data, slower feel).

### 10.7 Health & graceful degradation
- Hermes gateway exposes `GET /healthz` (model loaded? LanceDB open? queue depth). Laravel caches it.
- Unhealthy (re-index running / GPU busy / gateway down) → chat shows a calm **"AI is temporarily
  unavailable"**; Meilisearch keyword search + the normal catalog stay fully usable.
- **Never hang** a web request on a stuck generation — enforce a gateway **and** client timeout.

### 10.8 Citation mapping (the §3/§5 bridge, per answer)
- Hermes `sources[].catalog_id` → Laravel `view_catalogs` → **`buildCitations()`** → source cards
  linking `/full-description/{catalog_id}` (APA/MLA/Chicago). Same bridge; chat consumes it per turn.

### 10.9 What changes vs the Discord bot
| | Discord | eLibrary chat |
|---|---|---|
| Brain | Hermes agent | **same Hermes agent** |
| Guardrails | `locked-rag` | **same profile** (web is *more* hostile → keep it strict) |
| Transport | Discord adapter | **new HTTP gateway** (127.0.0.1) |
| Memory | Hermes/Discord-side | **Laravel-owned** (history sent per request) |
| Concurrency | low, bursty | **higher → queue + edge rate-limit** |
| Identity | Discord user | **patron auth** (opaque `user_ref` to Hermes) |

### 10.10 Build order (slots into §7)
- **Phase 1.5** (after §7 Phase-1 retrieval service): stand up the **Hermes HTTP gateway**
  (`/agent/chat`, `/healthz`) wrapping the existing agent + `locked-rag`; test with **curl** (token +
  a known question, check `sources[].catalog_id` + `abstained`) before any Laravel code.
- **Phase 2** gains the Laravel side: conversation store, Guzzle client (+ shared token), SSE
  streaming, and the "Ask the Library" chat UI (reuse existing chat components) rendering `answer`
  + `buildCitations()` source cards + the `abstained` empty-state.

---

> Cross-reference: Project Y specifics (llama-server, docs MCP, LanceDB, nginx proxy, GPU rules)
> live in the ProjectY repo docs (`rag/RAG_PLAN_AND_PROGRESS.md`, `HERMES_SECURITY_LOCKDOWN…md`,
> `STARTING_HERMES_GUIDE.md`). This file is the **eLibrary side** of that integration.
