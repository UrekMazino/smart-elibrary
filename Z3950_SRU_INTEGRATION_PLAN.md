# Z39.50 / SRU Copy-Cataloging Integration — Plan

**Status:** Not started — planning / reference only. Captured from a design discussion so it can be
revisited before any implementation. No code has been written for this yet.
**Last updated:** 2026-07-19

---

## 1. Goal
Let catalogers pull existing MARC records from external libraries (Library of Congress, OCLC
WorldCat, peer libraries) instead of keying every record by hand — i.e. **copy cataloging**. A
retrieved record should land **pre-filled in the existing create-record MARC editor** for review,
edit, and save — reusing the machinery we already have.

## 2. Direction: client, not server
"Z39.50 gateway" can mean two very different efforts:

- **Client (this plan):** *we* search other libraries and import their records. Plugs into the
  existing MARC editor + importer.
- **Server:** exposing *our* catalog to be searched by others. Much heavier (needs a Z39.50 server
  such as YAZ GFS or Zebra), rarely worth it for a single research library. **Out of scope.**

## 3. Protocol strategy — SRU-first, tiered
Do **not** default to raw Z39.50.

1. **SRU (Search/Retrieve via URL)** — *primary path*. HTTP + CQL query + MARCXML response. Pure PHP
   via Guzzle, **no C extension**, firewall-friendly. LC and most national libraries offer it.
2. **Z39.50 proper** — only for targets without SRU (notably **OCLC WorldCat**, some ILSs). Binary,
   stateful, TCP port 210, BER/ASN.1 encoded. Never hand-rolled — go through YAZ.
3. **OCLC WorldCat Search API (REST/JSON)** — if WorldCat is the main target and there's a
   subscription, this is often simpler than Z39.50. Evaluate before building Z39.50 solely for OCLC.

The "gateway" is really **one abstraction over SRU + Z39.50 (+ maybe OCLC REST)**.

## 4. Tooling
- **SRU:** Guzzle (already in the stack) → GET request → parse MARCXML. No new dependency.
- **Z39.50:** **YAZ** (IndexData C toolkit) + the **`php-yaz`** PECL extension, using the **ZOOM** API
  (`yaz_connect`, `yaz_search`, `yaz_wait`, `yaz_record`, `yaz_ccl_parse`). ZOOM speaks Z39.50 **and**
  SRU through one uniform API.
- ⚠️ **Environment dependency — the single biggest risk.** `php-yaz` needs the YAZ library plus a
  matching PHP extension binary. Fiddly on **Laragon/Windows** dev; on Linux prod it's
  `apt install yaz` + `pecl install yaz`. **Verify it installs in BOTH dev and prod before designing
  around it.** SRU-via-Guzzle needs none of this — another reason to build SRU first and treat YAZ as
  an optional add-on for OCLC.

## 5. Architecture (how it fits THIS codebase)
The payoff is that a retrieved record flows into the **same MARC editor** we already have.

- **One gateway service** — e.g. `App\Services\CopyCatalogGateway` — with a clean interface:
  `search(target, query): candidate[]` and `fetch(candidate): string` (ISO 2709 or MARCXML). Hides
  whether a target is SRU or Z39.50.
- **Targets in config/DB**, not hardcoded: `(host, port, database, syntax, charset, auth?)`. Seed LC;
  discover more via IndexData **IRSpy**. OCLC needs auth (user/group/password or API key).
- **Query building:** CCL (`yaz_ccl_parse`) for Z39.50, CQL for SRU; map friendly fields
  (ISBN / title / author) to **Bib-1 use attributes** (7 = ISBN, 4 = author, 1 = title). Don't
  hand-build Type-1 RPN queries.
- **Reuse the MARC pipeline:** a result (ISO 2709 / MARCXML) → parse with the **same
  `scriptotek/marc` + `File_MARC`** already used by the file importer and the new exporter → map to
  `field_data` → open in the create-record editor pre-filled → review → save.

## 6. Prerequisite refactor (do this first — it shrinks the whole integration)
- **Extract the "File_MARC record → `field_data`" mapping out of `singleImportMarcFile()`** into a
  shared service (e.g. `App\Services\MarcToFieldData`). Today that logic is buried inside the
  single-file-import controller method. Once it's shared, **file-upload import, SRU, and Z39.50 all
  feed the exact same `field_data` → editor path.** This is the highest-leverage refactor.
- **(Recommended) store `field_data` by CODE, not by local DB id.** Subfields/indicators currently
  carry `marctag_subfields_loc` / `marctag_indicators_loc` ids. The tag/subfield/indicator **code**
  is the universal MARC identifier; the **id is a local artifact** that drifts on re-seed/dedup.
  Storing codes decouples stored records from the reference tables and makes ingesting arbitrary
  external MARC cleaner. (The indicator work already moved this way — storing the indicator *char*,
  not the id.)

## 7. Gotchas that actually bite
- **MARC-8 → UTF-8:** many Z39.50 targets return **MARC-8**–encoded records; our DB is UTF-8. Must
  convert (YAZ can negotiate/convert charset; SRU is usually UTF-8 already). Getting this wrong =
  mojibake — the exact class of bug that was cleaned out of the seed data.
- **Targets are flaky and slow:** per-target **timeouts**; degrade gracefully; don't block a web
  request on N serial targets — multiplex (YAZ `yaz_wait` over several connections) or run federated
  search in a **queued job / AJAX** with a loading state.
- **Caching:** cache result sets briefly; never re-hit a paid target (OCLC) for the same query.
- **Security:** if targets are ever user-supplied → **SSRF** vector, whitelist them. Gate the whole
  feature behind `can:access.cataloging`; extra care for authenticated/paid targets.
- **Record syntax:** request MARC21 / USMARC explicitly; some targets default to GRS-1 or MARCXML.
- **Duplicate detection on import:** incoming records may duplicate existing holdings — match on
  ISBN / ISSN / LCCN / OCLC# before creating a new catalog row.

## 8. Recommended phased rollout
1. **Prereq refactor:** extract `MarcToFieldData` from `singleImportMarcFile`.
2. **SRU + Guzzle against LC** — proves the whole flow (search → MARCXML → `field_data` → editor)
   with **zero new extensions**.
3. **Copy-catalog UX:** an ISBN/title search box → candidate list → preview → pick → editor
   pre-filled → save (with the duplicate check).
4. **Add `php-yaz` (ZOOM)** *only if* OCLC or a Z39.50-only target is needed — and only after
   verifying the extension installs in both dev and prod.
5. Keep targets, auth, and CCL/CQL field maps in **config**.

## 9. Open decisions (to think about later)
- **Which targets matter?** LC only? OCLC? peer libraries? — decides SRU-only vs SRU + Z39.50.
- **OCLC subscription?** Changes the OCLC approach entirely (Z39.50 vs WorldCat REST API).
- **Federated** (many targets at once) vs **one target at a time?** — drives the async/queue design.
- **Commit to `field_data`-by-code decoupling first**, or bolt the integration on and map ids at
  import time?
- **Where does the search UI live** — inside the create-record page, or a separate "Search & Import"
  screen?

## 10. References
- Library of Congress Z39.50 / SRU services (Z39.50 at `lx2.loc.gov:210`; SRU under `loc.gov`).
- IndexData **YAZ** + **ZOOM** (`php-yaz`) — the standard Z39.50/SRU toolkit.
- IndexData **IRSpy** — registry of Z39.50 / SRU targets.
- **Bib-1 attribute set** — use attributes for query building.
- **MARCXML slim** schema (`http://www.loc.gov/MARC21/slim`) — already emitted by the exporter.
- **OCLC WorldCat Search API** — if subscribed.
- Related in-repo: `app/Services/MarcExportService.php` (MARC exporter),
  `singleImportMarcFile()` in `app/Http/Controllers/CatalogingController.php` (single-file importer —
  source of the mapping to extract), `public/js/admin/marc-record-editor.js` (the MARC editor the
  imported record should land in).
