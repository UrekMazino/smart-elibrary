# Ideas for Patrons — Backlog

**Status:** Ideas only; nothing built. Checked against the code on 2026-10-22: none of the 17 exists
yet. Search has no synonyms, there are no Google Scholar tags or citation exports, and searches are not
logged.
**Last updated:** 2026-10-22.

These are improvements that help people use the collection, beyond the accessibility work in
[ACCESSIBILITY_PLAN.md](ACCESSIBILITY_PLAN.md). Each one builds on something the eLibrary or ProjectY
already has. Pick one, then write it up as its own plan in this folder before building it. The
efforts are rough estimates for one developer working with AI assistance.

---

## 1. Summary

| # | Idea | Helps | Effort | Kind |
|---|---|---|---|---|
| 1 | Local and scientific names in search | Everyone | days | Quick win |
| 2 | "Cite this" downloads and Google Scholar tags | Students, researchers | ~1 week | Quick win |
| 3 | Saved-search alerts | Researchers, LGU staff | ~1 week | Quick win |
| 4 | Report of searches that found nothing | Library staff, FNRI | days | Quick win |
| 5 | Light, text-only version | Mobile users on slow or prepaid data | with accessibility phase 3 | Quick win |
| 6 | Plain-language summaries in English and Filipino | Frontline nutrition workers, students | 2–3 weeks | Bigger bet |
| 7 | Ask in Filipino or Taglish | Most Filipino users | 1–2 weeks | Bigger bet |
| 8 | Topic evidence pages | Policymakers, LGUs | 2–4 weeks | Bigger bet |
| 9 | Research map | Researchers, planners | 2–3 weeks | Bigger bet |
| 10 | Digitize on demand | Patrons wanting print-only items | 1–2 weeks (plus staff time) | Bigger bet |
| 11 | "Find it elsewhere" | Everyone | follows the SRU / API plans | Bigger bet |
| 12 | Nutrition myth checker | The public | 3–4 weeks | Wild |
| 13 | Research radio (audio summaries) | Frontline workers, low literacy, low vision | 2–3 weeks after #6 | Wild |
| 14 | Messenger or Viber bot | The public | 2–4 weeks | Wild |
| 15 | Documents into data | Researchers, FNRI | 1–2 months | Wild |
| 16 | Citation network | Researchers | 1 month | Wild |
| 17 | DOST research commons | All DOST libraries | months, plus agreements | Wild |

**Top three picks:** #1 (cheapest, with the biggest everyday effect), #6 (makes the collection usable
by frontline nutrition workers) and #12 (high public value, reusing the AI pipeline).

## 2. Rules for every AI-assisted idea
These apply to #6, #7, #8, #12, #13, #14 and #15:

1. **Label AI-written text** as AI-written, wherever it appears.
2. **Link every claim** to the record and the page it comes from.
3. **Let it say "the research doesn't say".** The AI answer pipeline already has this abstain path;
   keep it.
4. **Have a person review before publishing anything that stays up:** evidence pages, summaries shown
   on record pages, audio.
5. **Never present it as medical advice.** Nutrition questions come close to it.

## 3. Quick wins

### 1. Local and scientific names in search
- **What:** a synonyms list for Meilisearch, so a search for "malunggay" also finds studies that only
  say "Moringa" or "Moringa oleifera". More examples: kamote = sweet potato (*Ipomoea batatas*),
  tilapia = *Oreochromis*, dilis = anchovy, kangkong = water spinach.
- **Builds on:** `config/scout.php` → `index-settings` → `view_catalogs`. It has searchable,
  filterable and sortable attributes, but no `synonyms` yet. After changing it, run
  `php artisan scout:sync-index-settings`. Keep the list in a data file staff can extend.
- **Start with:** the 200 most common foods, crops and fish in the collection's subject headings,
  in English, Filipino and the main regional languages.

### 2. "Cite this" downloads and Google Scholar tags
- **What:**
  - RIS and BibTeX downloads on the record page, for Zotero, Mendeley and EndNote;
  - Highwire `citation_*` meta tags (`citation_title`, `citation_author`, `citation_publication_date`,
    `citation_pdf_url`), so Google Scholar can index the records.
- **Builds on:** `buildCitations()` in `app/Http/helper.php` (APA, MLA and Chicago already), and the
  page metadata from [OPAC_SEO_PLAN.md](../implemented/OPAC_SEO_PLAN.md).
- **Note:** the PDFs need sign-in, so decide what Scholar may see: the abstract only, or an open PDF
  for public-domain government works.

### 3. Saved-search alerts
- **What:** "Tell me when new research on stunting arrives". Save a search, then get notified when a
  newly published record matches it.
- **Builds on:** `app/Services/NotificationService.php`, the saved list (`/search/saved`), and the
  "catalog changed" event in [EVENT_DRIVEN_SIDE_EFFECTS_PLAN.md](EVENT_DRIVEN_SIDE_EFFECTS_PLAN.md).
  The alert is one more consumer of that event.

### 4. Report of searches that found nothing
- **What:** log each search and its result count, anonymously and without the IP address (as in the
  2026 privacy work). Staff see the top searches that returned nothing, or very little. That's
  evidence for what to acquire and digitize, and for FNRI's research priorities.
- **Builds on:** `app/Services/CatalogSearch.php`, which runs every search. It needs a new
  `search_log` table holding the query, filters, result count and date: no user, no IP.

### 5. Light, text-only version
- **What:** read a document as text instead of a 2.4 MB PDF (the average across the 8,761 full texts),
  for patrons on slow or prepaid mobile data.
- **Builds on:** it is the "Accessible text" view from phase 3 of the accessibility plan. Offer it to
  everyone and label it "Light version".

## 4. Bigger bets

### 6. Plain-language summaries in English and Filipino
- **What:** "What this study found, in three sentences", with a Filipino version, on each record page.
  It's for barangay nutrition scholars, LGU nutrition officers, teachers and students.
- **Builds on:**
  - the OCR text (ProjectY `rag/ocr_out/<stem>.md`, matched through `catalogs.accession_number`);
  - the local LLM (llama-server on `:8080`);
  - the batch pattern of ProjectY `rag/catalog_extract.py`: resumable, one JSON line per document, then
    imported by an artisan command.
- **Rules:** section 2. Staff approve a summary before it shows, at least for the first few hundred,
  until its quality is known.

### 7. Ask in Filipino or Taglish
- **What:** the OPAC's AI answer takes questions in Filipino or Taglish, and answers in the language
  it was asked in.
- **Builds on:** BGE-M3, the embedding model, is already multilingual, so retrieval mostly works. The
  work is in the answer prompt (`app/Services/AiSearchService.php` and the ProjectY gateway), and in a
  set of Filipino test questions alongside `rag/RAG_TEST_QUESTIONS.md`.

### 8. Topic evidence pages
- **What:** a page per topic, such as "What does Philippine research say about iron deficiency in
  children?":
  - the key findings across studies, each cited;
  - a timeline;
  - the open questions.
- **How:** the AI drafts the page from retrieved passages, and a librarian or nutritionist edits and
  approves it. Public pages are also good for SEO.
- **Builds on:** the AI index (hybrid search and reranking), and the CMS patterns of the News pages.

### 9. Research map
- **What:** a map of the Philippines showing studies by region and province. It shows where research
  was done, and where it's missing.
- **How:** take place names from the records (MARC 651 and 043 where present) and from the text, with
  an LLM pass over the opening chunks like `catalog_extract.py`. Map them to PSGC codes, the official
  Philippine geographic codes.

### 10. Digitize on demand
- **What:** a patron requests a print-only item. Staff scan it, the OCR pipeline processes it, it
  joins the AI index, and the patron is notified.
- **Builds on:** reservations and inquiries (the request), ProjectY's Stage 3 OCR (`rag/ocr_run.py`),
  the sync worker (indexing), and `NotificationService` (telling the patron).

### 11. "Find it elsewhere"
- **What:** when the library doesn't hold something, search partner catalogues: other DOST libraries,
  and the eLibrary instances at FPRDI and others.
- **Follows:** [Z3950_SRU_INTEGRATION_PLAN.md](Z3950_SRU_INTEGRATION_PLAN.md) and
  [API_INTEGRATION_PLAN.md](API_INTEGRATION_PLAN.md).

## 5. Wild ideas

### 12. Nutrition myth checker
- **What:** paste a claim seen on social media ("malunggay cures diabetes") and see what the research
  in the collection says, with citations. It says plainly when the collection has no evidence either
  way.
- **Builds on:** the AI answer pipeline and its abstain path.
- **Care:**
  - frame it as "what the research says", never as medical advice (section 2);
  - have a nutritionist review the instructions it follows, and a sample of its answers;
  - log the claims people check, which is useful to FNRI's communication work.

### 13. Research radio
- **What:** 1–2 minute audio summaries in Filipino, made from #6 with local text-to-speech, to play or
  download. They're for frontline workers on the move, and for listeners with low literacy or low
  vision.
- **Depends on:** #6, and a Filipino text-to-speech voice that sounds natural. Test with listeners
  before scaling.

### 14. Messenger or Viber bot
- **What:** the Discord bot's sibling, on the apps Filipinos already use.
- **Builds on:** the locked-down Hermes profile (`locked-rag`: corpus search only), after the
  prompt-extraction attacks on the Discord bot.
- **Care:** Meta's or Viber's API terms, privacy (messages pass through their servers), moderation,
  and rate limits. Remember the current capacity: one local LLM slot, about 2.6 questions a minute.

### 15. Documents into data
- **What:** pull structured facts out of the OCR text into a dataset you can query: nutrient tables,
  sample sizes, study locations, years, study designs. For example: "all studies measuring iron
  intake in children, 1990–2020". Wilder: link each food mentioned to its entry in FNRI's Philippine
  Food Composition Tables.
- **Builds on:** Marker keeps tables as Markdown, plus the `catalog_extract.py` batch pattern.
- **Care:** extraction errors. Show each value with its source page, and let staff correct values.

### 16. Citation network
- **What:** extract each document's reference list from the OCR text and match it against the
  catalogue. Show "this study builds on…" and "…was followed by…", and rank the most cited Philippine
  studies in the collection.
- **Care:** references in scanned documents are messy. Match on title, year and first author, and show
  only the confident matches.

### 17. DOST research commons
- **What:** one search, AI answers included, across every DOST agency library running this platform
  (FNRI, FPRDI, …).
- **Needs:** #11, an agreement between agencies, a shared or federated index, and a decision about
  where the LLM runs (see ProjectY `SERVER_SPEC.md`). It's the largest idea here, and as much policy as
  code.

## 6. Related documents
- [ACCESSIBILITY_PLAN.md](ACCESSIBILITY_PLAN.md): accessible text (needed for #5) and the testing
  approach.
- [EVENT_DRIVEN_SIDE_EFFECTS_PLAN.md](EVENT_DRIVEN_SIDE_EFFECTS_PLAN.md): the "catalog changed" event
  (needed for #3).
- [RAG_INTEGRATION_PLAN.md](RAG_INTEGRATION_PLAN.md): the AI answer and the planned "Ask the Library"
  chat (#7, #12, #14).
- [Z3950_SRU_INTEGRATION_PLAN.md](Z3950_SRU_INTEGRATION_PLAN.md) and
  [API_INTEGRATION_PLAN.md](API_INTEGRATION_PLAN.md) (#11, #17).
- [OPAC_SEO_PLAN.md](../implemented/OPAC_SEO_PLAN.md) (#2, #8).
