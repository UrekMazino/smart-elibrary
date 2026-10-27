# Accessibility for Screen Readers and Braille Displays — Plan

**Status:** Planning only; nothing built.
**Last updated:** 2026-10-22. The counts in section 3 were measured on `FNRI-Week-4` that day; section 11
has the commands to measure them again.

---

## 1. Goal
Blind and deafblind patrons can find, open and read the collection with a screen reader (NVDA, JAWS,
VoiceOver, TalkBack) and a refreshable braille display.

- **Standard:** WCAG 2.2 level AA.
- **Scope now:** the public side: search, results, record page, the reader, sign-in and account pages,
  inquiry and reservation chat, and the AI answer. Plus a readable text version of every full text.
- **Later (phase 5):** the staff side, MARC editor included. Only needed once staff who use a screen
  reader will work in it.

## 2. How a braille display sees the app
A braille display does not read the screen. It shows what the screen reader takes from the page's
structure: text, headings, labels, and each control's role and state. It shows 40–80 characters at a
time, and a deafblind reader has nothing else: no audio and no sight. Braille reading is also slower
than skimming a page by eye. So:

1. **Only real text exists.** A picture of text, or a meaning carried only by colour or position, is
   invisible to these readers.
2. **Anything timed can be lost.** A message that closes itself may be gone before it has been read.
3. **Too many announcements flood the display.** Every update to a live region replaces what the
   reader was reading.
4. **The page language matters.** It selects the braille table, and English and Filipino contracted
   braille differ.

## 3. Where things stand (2026-10-22)

### 3.1 Full texts: the largest barrier
- **Scans:** a sample of 400 of the 8,761 PDFs in `storage/app/private/pdf` found **80% are image-only
  scans** with no text layer. Only 20% contain real text. 79% carry PDF tags, but the tags only wrap the
  page images, so they don't help.
- **OCR output already exists (ProjectY):**
  - structured Markdown for **7,077** corpus files in `ProjectY/rag/ocr_out/<stem>.md`, from the
    Marker/Surya run of June–July 2026. It keeps headings, tables, and page separators (`{N}----`);
  - extracted text for the 1,741 born-digital files in `ProjectY/rag/stage2/extracted/<stem>.json`.
- **Join key:** `catalog:import-corpus` stores the corpus `stem` in `catalogs.accession_number`
  (`app/Console/Commands/ImportCorpusCatalog.php`). That links each record to its OCR file.
- **Gap:** full texts uploaded through the admin after the import have no stem and no OCR. The AI sync
  worker only reads a PDF's existing text layer, so a scanned upload gets no text.

### 3.2 Web pages

| Item | Count | Where |
|---|---|---|
| Skip-to-content link | **0** | — |
| `<main>` landmark | 4 views | `<nav>` is used in 44 |
| `lang` on `<html>` | all web layouts have it | Missing only in the 4 email templates, `admin/emails/*` |
| `<img>` without `alt` | **8** | `admin/catalog-view-record:12`, `admin/layouts/nav:5`, `admin/news-edit:71`, `layouts/nav:5`, `opac/index:344` and `:364`, `opac/partials/quick-view:5`, `opac/side:29` |
| Links that act as buttons (`href="#"` or `javascript:`) | **189** | admin 105, `layouts` 51, `opac/index` 24, other OPAC views 5, sign-in 3 |
| Inputs that may rely on a placeholder instead of a label (a heuristic, so check each by hand) | **89** | admin 68, OPAC 11, auth 7, sign-in 2, layouts 1 |
| `outline: none` / `outline: 0` (hides keyboard focus) | **280** | Mostly theme and vendor CSS: `public/opac/css` 75, `public/opac/DataTables` 64, `public/assets/css` 38, `public/opac/coming_soon` 26, `public/opac/sass` 14, the admin themes 26. Only 7 in our own views |
| Pop-ups and toasts that close themselves (`timer:`) | **24** | e.g. `admin/partials/catalog-bulk-js`, `admin/create-batch`, `admin/administrators`, `public/js/admin/check-out-in.js`, `opac/profile`, `opac/partials/quick-review`, `opac/index` |
| ARIA in use | 350 `aria-*` attributes, 132 `role` attributes, 13 `aria-live` regions | No positive `tabindex` anywhere, no CAPTCHA |
| Reader | pdf.js 3.11.174 (`public/pdfjs`) in an iframe | The iframes in `opac/full-description` and `opac/search-results` have titles |
| Session | 120 minutes | No warning before it expires |

**Already helps:** staff Appearance (`app/Services/Appearance.php`: contrast, text size, motion) serves
low-vision staff. The public side can reuse it later.

## 4. Rules for deafblind readers (apply everywhere)
1. **Nothing closes itself.** A message stays until the reader dismisses it. If it must close, it
   stays at least 20 seconds, pauses while it has focus or the pointer, and has a close button.
   (WCAG 2.2.1 and 2.2.2.)
2. **Never by sound or colour alone.** Every sound (a chat notification, say) and every colour-coded
   state also has text.
3. **Announce once, briefly, and politely.**
   - Filter counts: announce the final count once the reader stops changing filters (wait about a
     second), not on every change.
   - AI answers: never stream into a live region token by token. When the answer is complete,
     announce "Answer ready" and give a way to jump to it.
4. **Put the meaning first in labels.** The first 40 characters must identify the control: "Download
   PDF: Balut' Vending Machine", not "Click here to download the full text of this material".
5. **Warn before the session expires,** and let the reader extend it.
6. **Dialogs behave the same way every time:**
   - focus moves into the dialog;
   - Tab stays inside it;
   - Esc closes it;
   - focus returns to the control that opened it.

## 5. Phases

### Phase 1: Page structure (1–2 weeks, estimate)
- **Skip link:** a "Skip to content" link as the first element of the OPAC layout (and later the admin
  layout), pointing at `<main id="content">`.
- **Landmarks:** one `<main>` per page, and header, nav and footer landmarks in the layouts.
- **Language:**
  - `lang="en"` on the 4 email templates;
  - a `lang` attribute on titles and summaries that are not in English, taken from the record's
    language code (MARC 008/35-37 or 041).
- **Images:** `alt` on the 8 images listed in 3.2. A purely decorative image gets `alt=""`.
- **Keyboard focus:** one global `:focus-visible` rule, loaded after the theme and vendor CSS. That's
  simpler than editing 280 vendor rules, and mouse users don't see focus rings.
- **Real buttons:** turn the links that act as buttons into `<button type="button">`. Do the public
  side now (layouts 51, OPAC 29, sign-in 3) and the admin 105 in phase 5.
- **Labels:** check the 21 public-side placeholder inputs. Give each a `<label>`, visually hidden if
  the design needs it.
- **Timers:** remove `timer:` from the Swal and toast calls, or make "stay until closed" the default in
  the shared Swal mixin.
- **Headings:** one `h1` per page, with no skipped levels. Check the record page and the results page
  especially.

**Done when:** axe-core reports no serious or critical issues on the pages in section 7, and a
keyboard-only pass can complete every task listed there.

### Phase 2: Interactive parts (1–2 weeks, estimate)
- **Search filters with live counts** (`opac/search-results`):
  - group each set of filters in a `fieldset` with a `legend`;
  - include the count in each checkbox's label;
  - announce the result count once, as in rule 3.
- **Quick view** (`opac/partials/quick-view.blade.php`): follow the dialog behaviour in rule 6.
- **Guest sign-in prompt and other Swal dialogs** (`opac/partials/guest-login-js.blade.php`): Swal
  handles most of rule 6. Check that focus returns to the control that opened the dialog.
- **Record page:** the action menu and the Read, Download and Save buttons. Each button's name includes
  the record title.
- **Shelf browser:**
  - mark it up as lists;
  - let arrow keys move between books;
  - give it a text summary, such as "Shelf 3 of 12, 24 books".
- **Inquiry and reservation chat:** announce each new message once, with sender and text. No
  sound-only alerts.
- **AI answer (OPAC):** follow rule 3. Show citations as a list of links.
- **Reader:** upgrade pdf.js (section 8) and keep its text layer. Add "Accessible text" next to Read.

**Done when:** each component passes with NVDA and its Braille Viewer, and Tab, Shift+Tab and Esc
behave as rule 6 describes.

### Phase 3: Accessible text of the full texts (1–2 weeks, estimate)
1. **Import** with a command, `fulltext:import-text --dry-run`:
   - for each record whose `accession_number` is a corpus stem, take `ocr_out/<stem>.md`, or the
     stage-2 extracted text for born-digital files;
   - store it as `storage/app/private/text/<catalog_id>.md`, on the private disk like the PDFs, and
     record where it came from and when.
2. **View** at a route `/read/{id}/text`. It runs the same checks as Read: signed in, record published,
   patron not banned. The page:
   - renders the Markdown to HTML with `league/commonmark`, already in `composer.lock`, with raw HTML
     stripped;
   - marks each page as a heading or landmark ("Page 12");
   - keeps tables with their header rows;
   - shows figures as "Figure (no description)" unless the OCR kept a caption.
3. **Notice** at the top of the text: "This text was produced by OCR from a scanned document and may
   contain errors. The original PDF is still available."
4. **Downloads** as `.txt` or `.html`. Optionally a braille-ready file (BRF) through liblouis, but
   check which liblouis tables cover Filipino before promising it.
5. **New uploads:**
   - queue a scanned upload for OCR in ProjectY, on the GPU, run while llama-server can pause, as in
     Stage 3;
   - until then, show "Accessible text not available yet";
   - born-digital uploads already have their text read by the sync worker.
6. **Coverage report:** how many records with a full text have accessible text, and a list of those
   that don't.

**Done when:** at least 95% of records with a full text have accessible text, and 20 random samples
have been checked against their scans.

### Phase 4: Testing, and keeping it working (about 1 week, then ongoing)
- **Automated:** axe-core in CI (the GitHub Actions workflows already run the test suite) against the
  pages in section 7.
- **Manual:**
  - NVDA (free) with NVDA's Braille Viewer, which shows what a braille display would show;
  - a keyboard-only pass;
  - VoiceOver on iPhone for mobile.
- **With real readers:** a session with blind and deafblind readers through a Philippine organization
  of blind or deafblind persons.
- **QA:** add the accessibility checks to `docs/implemented/QA_REFERENCE.md`.

### Phase 5: Staff side (later, when needed)
The admin layouts (105 links acting as buttons, 68 placeholder inputs), the DataTables lists, and the
MARC editor (`marc-record-editor.js`). This is a larger job. Do it when staff who use a screen reader
will catalogue.

## 6. Effort (estimates, one developer working with AI assistance)

| Phase | Effort |
|---|---|
| 1. Page structure | 1–2 weeks |
| 2. Interactive parts | 1–2 weeks |
| 3. Accessible text of the full texts | 1–2 weeks |
| 4. Testing | about 1 week, then ongoing |
| **Public side in total** | **4–7 weeks** |
| 5. Staff side | to estimate when it's needed |

## 7. Pages and tasks to test
Each task should work with the keyboard only, and with NVDA and its Braille Viewer:
1. Search from the home page.
2. Narrow the results with filters.
3. Open a record.
4. Read the PDF.
5. Open its accessible text.
6. Download.
7. Sign in.
8. Register.
9. Manage the profile and saved list.
10. Send an inquiry.
11. Reserve.
12. Ask the AI a question and read the answer and its citations.

## 8. Related: pdf.js security (do it with phase 2)
pdf.js 3.11.174 in `public/pdfjs` is affected by **CVE-2024-4367**: a crafted PDF can run JavaScript
in the viewer. It's fixed in 4.2.67 and later. Upgrade the viewer, or as a stopgap set
`isEvalSupported: false`. This matters because the corpus PDFs came from outside sources.

## 9. Effect on regular users

| Change | Regular users | Blind and deafblind users |
|---|---|---|
| ARIA, landmarks, page language, hidden labels, announcements | Invisible | Essential |
| Skip link | Appears only when Tab is pressed | Saves going through the header on every page |
| Focus ring through `:focus-visible` | Mouse users don't see it | Required for keyboard and low-vision users |
| Real buttons instead of links | No visible change | Controls are announced and work correctly |
| Messages stay until closed | Sometimes one more click | Critical: braille reading is slower |
| Small contrast adjustments | Slightly darker teal and pink in places | Helps low-vision readers |
| "Accessible text" on each record | An extra option, also handy on phones and for copying | Makes about 80% of the collection readable |
| Warning before the session expires | A rare prompt | Readers don't lose their place mid-document |

## 10. Legal basis for accessible copies
RA 10372, which amended the Intellectual Property Code, and the Marrakesh Treaty, in force for the
Philippines since 2019, let libraries make accessible-format copies for print-disabled readers. Most
FNRI research is a government work in any case.

## 11. Measuring again
From the eLibrary root (Git Bash):

```bash
V=resources/views
grep -rliE 'skip (to|navigation)|skip-link' $V | wc -l                         # skip links
grep -rnoE '<img\b[^>]*>' $V | grep -v 'alt=' | cut -d: -f1,2                     # images without alt
grep -roE 'href="(#|javascript:[^"]*)"' $V | wc -l                                # links acting as buttons
grep -roE '<input\b[^>]*placeholder=[^>]*>' $V | grep -v -E 'aria-label|type="hidden"' | wc -l
grep -roE 'outline:\s*(none|0)' $V public/css public/opac public/assets/css | wc -l
grep -rnoE 'timer:\s*[0-9]+' $V public/js public/opac resources/js | wc -l         # self-closing pop-ups
```

PDF text layers, with ProjectY's `rag/.venv` (it has PyMuPDF):

```python
import fitz, random, glob
files = glob.glob(r'C:/laragon/www/elibrary/storage/app/private/pdf/*.pdf')
random.seed(7)
sample = random.sample(files, 400)
text = 0
for f in sample:
    d = fitz.open(f)
    n = min(3, d.page_count)
    if sum(len(d[i].get_text().strip()) for i in range(n)) > 200 * n:
        text += 1
print(f'{text}/{len(sample)} have a real text layer')
```
