# Free DOIs from Zenodo — Plan

**Status:** Not started. Planning only; no code has been written.
**Last updated:** 2026-10-28

---

## 1. Goal

Give FNRI's own materials a permanent DOI (Digital Object Identifier) at no cost, by depositing them in
**Zenodo**, and show that DOI everywhere the eLibrary describes the material: the OPAC record page, the
citations, the MARC export and the search engines' data.

A DOI looks like `10.5281/zenodo.1234567` and is cited as `https://doi.org/10.5281/zenodo.1234567`. It never
changes and never breaks, which is why journals, funders and Google Scholar prefer it to a web address.

**Out of scope:**
- DOIs that open the eLibrary itself. Those need FNRI to join Crossref or DataCite (paid: a yearly fee plus a fee
  per DOI). Section 11 notes the upgrade path.
- Materials FNRI does not hold the rights to (section 3).

## 2. How Zenodo works

Zenodo is a free research repository run by CERN. Anyone can deposit, and every published record gets a DOI
registered through DataCite under Zenodo's prefix `10.5281`.

| | |
|---|---|
| **One record** | One material: its files (the PDF) and its description (title, authors, date, summary, keywords, licence). |
| **The DOI** | Reserved while the record is a draft (so it can be printed in the PDF first), live once the record is published. |
| **Where it leads** | **The Zenodo record page**, not the eLibrary. Zenodo hosts and serves the file. |
| **Versions** | A corrected file is published as a new version with its own DOI. A "concept DOI" always leads to the newest version. |
| **After publishing** | The description can be edited at any time. The files cannot be replaced, only versioned. A record can't be deleted; withdrawal is "exceptional" and leaves a notice page at the DOI. |
| **Access** | Open, embargoed (opens on a date), restricted (on request) or closed. The description is public in every case. |
| **Licence** | Required for open and embargoed files (e.g. CC BY 4.0). Descriptions are released as CC0 and harvested by others (OAI-PMH). |
| **Limits** | 50 GB and 100 files per record. API: 100 requests a minute, 5,000 an hour. |
| **Communities** | A collection page (e.g. "DOST-FNRI") that groups the institute's records; its owner accepts each record. |
| **Testing** | `sandbox.zenodo.org`: a separate account and token, test DOIs (`10.5072/...`) that resolve nowhere, wiped at any time. |
| **Retention** | "For the lifetime of the repository", with a promise to move the content elsewhere if Zenodo ever closes. |

Zenodo's terms say free deposits are for those "without ready access to an organised data centre". Before a bulk
deposit of thousands of records, **write to Zenodo support** with the planned volume and get their agreement
(phase 0).

## 3. Which materials can go

Only materials **FNRI holds the rights to, or has written permission for**. A Zenodo deposit is permanent and
public, so this is decided per record, by a person, before anything is sent.

| Material type (records on 2026-10-28) | Likely answer |
|---|---|
| Technical Reports (4,991) | **Yes, mostly**: FNRI's own research reports. The first and largest group. |
| Thesis/Dissertations (1,463) | **Usually no**: the author (and university) hold the rights. Only with the author's written permission. |
| Books (921) | **FNRI's own publications only** (e.g. the PDRI, food composition tables). Not other publishers' books. |
| Serials (844) | **FNRI's own serials only**, issue by issue or article by article. Not other publishers' journals. |
| Non-Prints (531) | Case by case (FNRI-made videos, posters, presentations). |
| Language material (169), others | Case by case. |

**Never deposit:**
- A material that already has a DOI from its publisher. Zenodo invalidates its own DOI for those, and the material
  needs no second DOI. The eLibrary checks MARC 024 for an existing DOI and refuses.
- Anything confidential, under a non-disclosure agreement, or holding personal data beyond the authors' names
  (RA 10173).
- Drafts the institute hasn't released.

**For FNRI's legal or IP office to confirm (phase 0):**
- Republic Act 8293 §176 says no copyright subsists in works of the Philippine Government, but that profit-making
  use needs the agency's approval. This bears on which licence FNRI can and should choose: CC BY 4.0 (credit
  required), CC0, or a non-commercial licence.
- The decision should be written down, and the licence recorded in each record (MARC 540, section 5).

## 4. What staff will see

### One record: two steps, because publishing is permanent

On the admin record page, the **Action** menu gets **Zenodo DOI…**. It opens a page under the record
(Cataloging › Record List › *Title* › Zenodo DOI), in five steps.

1. **Check.** The page lists what will be sent, mapped from the record (section 6), and what blocks a deposit:
   - no full text;
   - an existing DOI in 024;
   - no summary (520): staff can type one here, and it is saved into the record's 520 so the eLibrary and Zenodo
     say the same thing;
   - no creator or no publication year;
   - an unpublished or held (On-Hold) record.
2. **Rights.** A required tick: *"FNRI holds the rights to this material, or has written permission to publish it
   openly"*, a licence (default from settings) and an access level (default open). The name and time of the
   person who ticked it are kept.
3. **Reserve DOI** (permission `prepare.zenodo`). This creates the Zenodo draft, reserves the DOI, uploads the PDF
   and checks Zenodo's checksum against the file. The page then shows the reserved DOI and a link to the draft on
   Zenodo. Nothing is public yet; the draft can still be changed or discarded.
4. **Publish** (permission `publish.zenodo`, ideally a different person: the librarian in charge). A confirmation
   says plainly that the record and file become public and permanent. On success:
   - the DOI is written into the record's MARC 024, and the licence into 540;
   - the search index and the AI index are refreshed, like any record edit;
   - the record page shows a "DOI" badge linking to `https://doi.org/...`.
5. **Afterwards** the page shows the Zenodo status and these actions:
   - **Update on Zenodo**: the eLibrary record changed (title, summary, authors…); this sends the new description,
     and the DOI stays the same.
   - **Publish new version**: the full text was replaced; this deposits the new file, which gets a new version DOI
     (section 10, decision 5).
   - **Discard draft**: before publishing only.

### Many records: Utilities › Zenodo DOIs

A new Utilities card and page lists every deposit:
- each one's status (draft, published, failed, out of date) and DOI;
- who prepared and published it;
- the last error;
- buttons to retry a failed deposit or update an out-of-date one.

Bulk work runs from the command line (section 8, phase 4), so a long upload never hits a web timeout.

### The public side

The OPAC record page and the citations show the DOI. Section 7 has the details.

## 5. Data

### New table `zenodo_deposits`

One row per record and environment. The row is the source of truth for the Zenodo state.

| Column | |
|---|---|
| `catalog_id` | The record. Unique with `environment`, which stops a double click from making two deposits. |
| `environment` | `sandbox` or `production`. Sandbox DOIs are **never** written into a record. |
| `deposition_id`, `record_id` | Zenodo's ids (draft and published record). Saved **immediately** after each call, so every step can be resumed. |
| `status` | `preparing` → `draft` → `published`; or `failed` (with `last_error`); or `discarded`. |
| `doi`, `concept_doi`, `record_url` | As Zenodo returns them. |
| `license`, `access_right`, `embargo_date` | As chosen. |
| `rights_confirmed_by`, `rights_confirmed_at` | The person who ticked the rights box. |
| `prepared_by`, `published_by`, `published_at` | Who did what. |
| `file_md5`, `metadata_hash` | What was sent: the file's checksum and a hash of the description. When the record later differs, the deposit shows "out of date". |
| `last_synced_at`, `last_error`, timestamps | |

### In the record (MARC), so exports and other library systems carry it

- `024 7_ $a 10.5281/zenodo.1234567 $2 doi`: the standard place for a DOI in MARC 21.
- `540 ## $f CC BY 4.0 $u https://creativecommons.org/licenses/by/4.0/ $2 cc`: the licence.

### Prerequisite: one way to write `field_data`

Writing 024 and 540 from code needs **one shared "save a record's `field_data`" step**. Today that logic lives
inside `CatalogingController::updateRecord()`, which rebuilds `view_catalogs`, the search index and the AI sync
from the posted form.

Extract it into a service (e.g. `App\Services\RecordWriter`) that the edit form, the Zenodo publish step and later
features all call. Build the 024/540 fields in the same shape the MARC editor saves (marctag and subfield ids from
`marctags_loc` / `marctag_subfields_loc`, indicators as single characters).

This matches the "catalog changed" event in [EVENT_DRIVEN_SIDE_EFFECTS_PLAN.md](EVENT_DRIVEN_SIDE_EFFECTS_PLAN.md)
and the `MarcToFieldData` extraction in [Z3950_SRU_INTEGRATION_PLAN.md](Z3950_SRU_INTEGRATION_PLAN.md).

## 6. From the record to Zenodo

Built by one mapper (`App\Services\ZenodoMetadata`), shown to staff before anything is sent, and covered by tests.

| Zenodo field | From the record |
|---|---|
| `title` | 245 $a $b (`marcTitle($fields, false)`), ISBD punctuation trimmed |
| `creators` (required) | 100/700 persons as "Family, Given" (already stored that way); 110/710 organisations as their name. Same parsing as the citations (`citeName()`). |
| `description` (required) | 520 $a. Section 4: staff add one when it's missing. |
| `publication_date` | 260/264 $c (the year). **Check in the sandbox** whether a year alone is accepted; the API documents `YYYY-MM-DD`. If not, decide (section 10, decision 6). |
| `upload_type` / `publication_type` | From the material type, through a table in `config/zenodo.php`: Technical Reports → publication/report, Thesis → publication/thesis, Books → publication/book, Serials → publication/other (or article), Non-Prints → video / image / presentation / other per record. |
| `language` | 008/35-37 (`eng`, `fil`…), else 041 $a |
| `keywords` | 650/651/653 $a |
| `version` | 250 $a (edition) |
| `imprint_publisher`, `imprint_place`, `imprint_isbn` | 260/264 $b, $a; 020 $a |
| `related_identifiers` | The eLibrary record page (`recordUrl()`), relation `isIdenticalTo` (confirm the relation in the sandbox) |
| `notes` | "Also in the DOST-FNRI eLibrary: <record URL>. Call number …; accession number …." |
| `communities` | `ZENODO_COMMUNITY` (e.g. `dost-fnri`) |
| `license`, `access_right`, `embargo_date` | As chosen in step 2 |
| `prereserve_doi` | `true` |
| File | The record's full text (`fullTextFile()`), uploaded as `FNRI-<accession>-<title-slug>.pdf` |

## 7. Showing the DOI in the eLibrary

Phase 1. This is useful even before Zenodo, for records that already carry a publisher's DOI in 024.

| Where | What changes |
|---|---|
| OPAC record page | A "DOI" line linking to `https://doi.org/...` |
| Admin record page | A DOI badge and the Zenodo status |
| Citations (`buildCitations()`) | APA 7, MLA 9 and Chicago end with `https://doi.org/...`, as each style asks |
| Search engines (`seoRecordJsonLd()`) | `identifier` as a DOI `PropertyValue`, and the DOI URL in `sameAs` |
| MARC export (`MarcExportService`) | 024 goes out with the rest of the record; nothing to change |
| OPAC search | A DOI typed in the search box finds the record (add 024 to the searchable attributes) |

## 8. Phases

**Phase 0: decisions and accounts (no code).**
- FNRI's legal or IP office decides the rights rule and the licence (section 3); write it down.
- Choose the first collection: FNRI technical reports.
- Create an institutional Zenodo account, on a shared library mailbox rather than a person's, and a "DOST-FNRI"
  community with a short curation policy.
- Write to Zenodo support about the planned volume (several thousand records) and get a yes.
- Create a sandbox account and a sandbox token.

**Phase 1: DOIs in the catalogue.** Section 7, plus the `RecordWriter` extraction (section 5).

**Phase 2: one record, in the sandbox.**
- `config/zenodo.php` and the `.env` settings (section 9).
- The client: `App\Services\Zenodo` (create, upload, metadata, publish, edit, new version, discard).
- The mapper, the `zenodo_deposits` table, and the two permissions (migration plus regenerated seeder JSON, the
  usual way).
- The record page flow (section 4), and tests with `Http::fake()`.
- In the sandbox, check what the API documents but this plan can't settle: year-only dates, the
  `related_identifiers` relation, the community request, the HTML allowed in the description.

**Phase 3: pilot in production.**
- Ten FNRI technical reports, one by one.
- Check each landing page, that the DOI resolves, the community, the citations, and how Google Scholar picks
  them up.
- The librarians review the descriptions, and the mapping is adjusted.

**Phase 4: bulk.**
- `php artisan zenodo:prepare` picks records (by material type, a list of ids, or a saved "FNRI-owned" list) and
  makes drafts, with `--dry-run` first: what would go, and what is blocked and why.
- After review, `php artisan zenodo:publish` publishes the reviewed drafts.
- Both commands:
  - read Zenodo's `X-RateLimit-Remaining` header and slow down before the limit;
  - resume where they stopped;
  - write a report to `storage/logs/zenodo.log`.
- The rights confirmation for a bulk run is one recorded decision (who, when, which list), not a tick per record.

**Phase 5: keeping in step.**
- A record edited after publishing shows "out of date" (its `metadata_hash` differs) and gets **Update on Zenodo**.
- A replaced full text gets **Publish new version**.
- Deleting a record that has a DOI warns first: the Zenodo record and DOI stay public forever.
- A nightly `zenodo:check` (in `app/Console/Kernel.php`, like `search:reconcile`) lists out-of-date and failed
  deposits on the Utilities page and in the notification bell.

## 9. Settings, security and failure handling

### Settings in `.env`

None are in the database; they also go in `.env.example` and DEPLOYMENT_CHECKLIST.md.

| Setting | Meaning |
|---|---|
| `ZENODO_URL` | `https://sandbox.zenodo.org` everywhere except production, which uses `https://zenodo.org`. |
| `ZENODO_TOKEN` | Scopes `deposit:write` and `deposit:actions` only. Never stored in the database, never logged, never sent to the browser. A separate token for the sandbox. |
| `ZENODO_COMMUNITY` | The community id. |
| `ZENODO_LICENSE` | The default licence (from phase 0). |

### Security

- **Server and permissions:** The Zenodo address comes only from config, never from a request (no SSRF). Every
  route checks its permission, and `RouteSecurityTest` covers the new routes automatically.
- **Two permissions:** `prepare.zenodo` and `publish.zenodo`, so the permanent step can belong to a different role
  than the preparing one.
- **The Zenodo account:** sign in through the institutional account; turn on two-factor sign-in if Zenodo offers
  it. Revoke and replace the token when staff with access leave.
- **Privacy:** only bibliographic data and the full text are sent. Never patron data; no staff email addresses in
  the metadata (descriptions are public and CC0).

### Failure handling

- **One step at a time.** Each step saves its result before the next, so a failure leaves a row that says exactly
  where it stopped. **Retry** continues from there instead of starting over, so no duplicate drafts.
- **Publish timeouts.** On a timeout during publish, the deposition is read back before anything is retried.
- **409 "still integrating".** After a publish, retry about five minutes later.
- **Large files.** PDFs are streamed from disk with a long timeout. The busy modal (`appBusy`) covers the single
  record; the command line covers bulk (`QUEUE_CONNECTION` is `sync`, so there is no queue worker).

### Tests

- The mapper, record by record.
- Refusals: an existing DOI, no rights tick, no full text, held records.
- Idempotency: a double submit makes one deposit.
- Sandbox DOIs are never written to 024.
- Permissions.
- The DOI in the citations and the JSON-LD.
- The new pages' breadcrumbs (`AdminNavigationTest`).
- QA_REFERENCE.md gets the new pages.

## 10. Open decisions

1. **Rights and licence:** CC BY 4.0, CC0 or non-commercial? Decided by FNRI's legal or IP office (RA 8293 §176).
2. **Which collections, in what order?** Technical reports first is the proposal.
3. **Open, or embargoed or restricted for some?** Zenodo allows each; the description is public in every case.
4. **Who may publish:** the same role that prepares, or a librarian in charge?
5. **Which DOI the catalogue shows:** the version DOI (what is printed in the PDF; changes with a new version) or
   the concept DOI (always the newest version). Proposal: the first version's DOI, since new versions will be rare
   corrections.
6. **Year-only publication dates,** if Zenodo insists on a full date: January 1 of the year, or the date of
   deposit with the year in the notes?
7. **Stamp the DOI into the PDF** (a cover note) before publishing, or leave the files as scanned? Stamping needs
   a PDF library and changes the file the eLibrary serves.

## 11. Later: DOIs that open the eLibrary

If FNRI later wants DOIs that lead to the eLibrary's own record pages, it can join **Crossref** (common for
publishers of reports and journals) or **DataCite** as a member, for a yearly fee plus a fee per DOI.

The work in this plan carries over:
- DOIs in 024;
- the display and citations;
- the metadata mapping, which becomes a Crossref or DataCite deposit.

Zenodo DOIs already issued stay valid alongside them.

## 12. References

- Zenodo developer documentation (REST deposit API, sandbox, rate limits): https://developers.zenodo.org/
- Zenodo general policies (content, access, retention, size): https://about.zenodo.org/policies
- Zenodo terms of use: https://about.zenodo.org/terms
- Reserving a DOI: https://help.zenodo.org/docs/deposit/describe-records/reserve-doi
- MARC 21 bibliographic: 024 (Other Standard Identifier), 540 (Terms Governing Use and Reproduction).
- In this repo:
  - `buildCitations()`, `seoRecordJsonLd()`, `fullTextFile()`, `recordUrl()` (app/Http/helper.php)
  - `CatalogingController::updateRecord()`: the save logic to extract
  - app/Services/MarcExportService.php
  - [EVENT_DRIVEN_SIDE_EFFECTS_PLAN.md](EVENT_DRIVEN_SIDE_EFFECTS_PLAN.md)
  - [Z3950_SRU_INTEGRATION_PLAN.md](Z3950_SRU_INTEGRATION_PLAN.md)
