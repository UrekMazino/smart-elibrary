# Documentation

Sorted by whether the work a document describes exists in the code (checked 2026-10-21).
Move a document across when that changes.

## [implemented/](implemented/)

| Document | What it is | Still open |
|---|---|---|
| [DEPLOYMENT_CHECKLIST.md](implemented/DEPLOYMENT_CHECKLIST.md) | Installing and updating the app on a developer's PC, on staging and on production, step by step; `.env` per environment, web server, PHP, scheduler, backups, moving a database forward, troubleshooting. Also as a PDF: [DEPLOYMENT_CHECKLIST.pdf](implemented/DEPLOYMENT_CHECKLIST.pdf) | The staging and production deployments themselves (none yet) |
| [LARAVEL_UPGRADE_RUNBOOK.md](implemented/LARAVEL_UPGRADE_RUNBOOK.md) | Laravel 8 → 13 upgrade, hop by hop | — (the app runs Laravel 13) |
| [MEILISEARCH_SETUP.md](implemented/MEILISEARCH_SETUP.md) | Running and syncing the OPAC search index | — |
| [OPAC_SEO_PLAN.md](implemented/OPAC_SEO_PLAN.md) | Page metadata, JSON-LD, sitemap, record URL slugs (all four workstreams) | §4 pre-launch steps: real domain, HTTPS, Search Console |
| [RAG_CATALOG_SYNC_PLAN.md](implemented/RAG_CATALOG_SYNC_PLAN.md) | Catalog changes → AI index: outbox, worker, reconcile, AI Sync Monitor | Optional: content-hash re-sync, record-list badge, purge policy |
| [QA_REFERENCE.md](implemented/QA_REFERENCE.md) | Every page and action for QA testing: address, who may use it, what to check, and the security checks to repeat. Also as a PDF to send: [QA_REFERENCE.pdf](implemented/QA_REFERENCE.pdf) | — (kept current with the app; `tests/Feature/RouteSecurityTest.php` checks every route automatically) |
| [database/](implemented/database/) | Database documentation; its findings R1–R16 are all fixed | Re-export the PDF from Word (the .docx is newer) |
| [SECURITY_NOTES.md](implemented/SECURITY_NOTES.md) | The security rules in force: input rules, sign-in and rate limits, who may do what (roles), deadlines for patching vulnerable packages (OWASP ASVS 2.1.1, 6.1.1, 8.1.1, 15.1.1) | — (update it in the same commit as a rule change) |
| [CSP_ROLLOUT.md](implemented/CSP_ROLLOUT.md) | The Content Security Policy (OWASP ASVS 3.4.3): what it allows, how pages run scripts under it (nonces, `data-call`), the emergency switch, and how it was rolled out | Later: tighter `style-src`; a policy for the static PDF reader |

## [not-implemented/](not-implemented/)

| Document | What it is | Done so far |
|---|---|---|
| [EVENT_DRIVEN_SIDE_EFFECTS_PLAN.md](not-implemented/EVENT_DRIVEN_SIDE_EFFECTS_PLAN.md) | One "catalog changed" event replacing scattered manual calls | Phase 0 (scheduler, one search-index owner, nightly reconcile). Phases 1–4 not started |
| [RAG_INTEGRATION_PLAN.md](not-implemented/RAG_INTEGRATION_PLAN.md) | Connecting the eLibrary to the local AI (Project Y) | One-question AI answer in the OPAC and the AI index sync work. Not built: the conversational "Ask the Library" chat (§10, saved conversations), "related passages" and admin similarity tools (phase 4) |
| [API_INTEGRATION_PLAN.md](not-implemented/API_INTEGRATION_PLAN.md) | An API for other web apps: options (REST + tokens, OAuth2, OAI-PMH/SRU, webhooks) and a recommended v1 | Nothing yet (planning only) |
| [Z3950_SRU_INTEGRATION_PLAN.md](not-implemented/Z3950_SRU_INTEGRATION_PLAN.md) | Copy cataloguing from other libraries over Z39.50 / SRU | Nothing (planning only) |
| [ZENODO_DOI_PLAN.md](not-implemented/ZENODO_DOI_PLAN.md) | Free DOIs for FNRI's own materials by depositing them in Zenodo: which materials may go, the two-step reserve/publish flow, the record → Zenodo mapping, DOIs in the catalogue (MARC 024, citations, OPAC), bulk deposits | Nothing (planning only) |
| [PATRON_IMPROVEMENT_IDEAS.md](not-implemented/PATRON_IMPROVEMENT_IDEAS.md) | 17 ideas to help people use the collection, from quick wins (local names in search, "cite this", search alerts) to wild ones (myth checker, research radio, DOST research commons), each with what it builds on | Nothing (ideas only; write a plan before building one) |
| [ACCESSIBILITY_PLAN.md](not-implemented/ACCESSIBILITY_PLAN.md) | Screen readers and braille displays (WCAG 2.2 AA): the 2026-10-22 audit, rules for deafblind readers, 4 phases for the public side plus accessible text of the scanned full texts (from ProjectY's OCR), and the pdf.js security upgrade | Nothing (planning only) |
| [WINDOWS_UPDATE_2026-10-20.md](not-implemented/WINDOWS_UPDATE_2026-10-20.md) | Steps to bring the Windows machine up to date (private PDF storage) | The code is done; run the steps on Windows, then delete the file |
