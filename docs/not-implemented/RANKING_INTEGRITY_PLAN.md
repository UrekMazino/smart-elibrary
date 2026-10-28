# Fair rankings — what is still to build

**Status:** planning; steps 2.1, 2.2, 2.6 and 2.7 built on 28 October 2026, and 2.3 replaced by them (section 3).
What is built (views per viewer-day, downloads per reader, the weighted Highest Rating, ratings from readers
only, the download limit, the 90-day window, banned accounts left out, the statistics watch) is in
[`../implemented/RANKING_INTEGRITY.md`](../implemented/RANKING_INTEGRITY.md).
**Last updated:** 2026-10-28 (decisions; 2.1, 2.2, 2.6 and 2.7 built).

---

## 1. Goal
Close the gaps that remain after 28 October 2026, so the top-10 lists and their medals reflect real readers:

| Gap | Step | Status |
|---|---|---|
| A banned account's past views, downloads and ratings still count | 2.1 | Built 2026-10-28 |
| Old activity keeps a record on top forever | 2.2 | Built 2026-10-28 |
| Throwaway accounts can download and rate | 2.3 | Replaced by 2.7 and Google sign-in |
| A guest switching address or browser is a new viewer; bots that load pages count | 2.4 | To do |
| The search page's "Most viewed" sort still counts every old reload | 2.5 | To do |
| Nobody sees sudden jumps or sign-up bursts; there is no way to cancel an account's counts | 2.6 | Built 2026-10-28 (guests can't be taken out) |
| Fake sign-ups in bulk | 2.7 | Built 2026-10-28 |
| Old duplicate view rows still in the table | 2.8 | Optional |
| The Privacy notice covers only the statistics and sign-in | 2.9 | Waiting for the DPO |

Next: 2.4, then 2.5. The rest when needed.

## 2. Steps

### 2.1 A ban takes the account out of the rankings — built
Built 28 October 2026: `rankingRows()` leaves out rows of banned accounts, and setting or lifting a ban empties
the lists' cache. See `RANKING_INTEGRITY.md`.

### 2.2 A time window for the rankings — built
Built 28 October 2026: the lists rank the last 90 days (`rankingDays()`), ratings too; the home page switches
them to all time and back; the medals follow the last 90 days. See `RANKING_INTEGRITY.md`.

### 2.3 Verified email before downloads and ratings — replaced
Decided 28 October 2026 not to build it: outgoing mail is off, and patrons would wait on messages. In its place
sign-up has an "I'm human" check (2.7), and patrons may continue with Google, which confirms the address
(`SECURITY_NOTES.md` section 2). Existing accounts need nothing. The verify routes (`routes/auth.php`) stay
unused; `User` doesn't implement `MustVerifyEmail`.

### 2.4 Count a guest's view only when someone is reading
- **Why:** the guest hash tells visitors apart, but a script that changes its address or browser string is a
  new viewer each time; link previews and bots that fetch pages count too.
- **How:** for guests, `saveView()` moves from the page load to a small script: after the page has been
  visible for about 10 seconds (Page Visibility API), it sends `POST /views/{catalog_id}` (CSRF token, nonce'd
  script, `throttle`); the endpoint calls `saveView()` with the same day-key and hourly cap. Signed-in readers
  can stay on page load (their account is already the key).
- **Trade-off:** a guest with scripts turned off isn't counted (rare).
- **Effort:** small to medium. **Test:** a guest page load alone adds no view; the beacon adds one; a second
  beacon the same day adds none.

### 2.5 The search page's "Most viewed" sort counts the same way
- **Why:** it sorts by the `views` field in the search index, a plain row count (`ViewCatalog::withCount('views')`
  / `toSearchableArray()`), so old reloads still count there.
- **How:** compute the indexed `views` with `viewerDaysSql()` (a subquery per record), and let the nightly
  reconcile refresh it (`ReconcileSearchIndex`, which already refreshes recently viewed records).
- **Effort:** small to medium; a full re-import once (`scout:import`, see `MEILISEARCH_SETUP.md`).

### 2.6 A statistics watch for staff — built
- **Why:** to see manipulation that gets past the rules, and undo it.
- **Built 28 October 2026** (Administration → Accounts → Statistics watch, permission `access.statistics.watch`;
  `RANKING_INTEGRITY.md` section 4): for a day, against the 30 days before it,
  - materials whose viewers or readers that day are far above their usual day (5× and at least 10);
  - accounts with unusually many materials viewed, downloads or ratings in the day;
  - **sign-up bursts** (added 28 October): hours with far more sign-ups than usual (at least 10, and 5× the
    usual hour), with their accounts;
  - taking accounts out of the rankings, one or a whole burst, reversibly, with who did it, when and why
    (`ranking_exclusions`); the dashboard's "Needs attention" counts what is still to look at.
- **Guests, built the same day:** `views.visitor_hash` (the keyed hash) on guests' views, and the address behind
  it in `visitor_addresses`, both deleted after 90 days by the nightly `visitors:forget`; unusual guests (100
  materials in a day, "At the cap"), and a views spike's guests by address. Addresses for **developer and super
  admin** only, by role. `SECURITY_NOTES.md` section 6 and the Privacy notice say so.
- **Not built:** taking a guest out of the rankings. A guest's exclusion would key on its hash, which goes after
  90 days, so its old views would count again in "All time"; 2.4 stops most of what it would catch.

### 2.7 A challenge on sign-up — built
Built 28 October 2026, at once rather than "if fake accounts appear", in place of 2.3: Cloudflare Turnstile on
the sign-up form, checked on the server (`App\Rules\HumanCheck`); the sign-up page alone lets Cloudflare into
its Content Security Policy; the Privacy notice names Cloudflare. Keys: `DEPLOYMENT_CHECKLIST.md`.

### 2.8 Clear out old duplicate views (optional)
- **Why:** the rankings and the record page already count old reloads once a day (`viewerDaysSql()`), but the
  table still holds them; the dashboard and the search index (until 2.5) see them.
- **How:** an artisan command, a dry run by default, that keeps the first row per (`user_id`, `catalog_id`,
  day) for signed-in rows and deletes the rest with `--apply`. Old guest rows can't be told apart: leave them.
- **Before:** a backup of `views`. **Effort:** small.

### 2.9 The full Privacy notice
- **Why:** the OPAC's notice now says only what the statistics use. The Data Privacy Act expects a notice of
  what the eLibrary collects for accounts, reservations, inquiries and reading, the purposes, how long it is
  kept, the rights of the data subject, and how to reach the Data Protection Officer.
- **Who:** the agency's DPO writes or approves the text; the developer puts it in `#modalPrivacy`
  (`opac/layout/master.blade.php`), next to the statistics section, and keeps `SECURITY_NOTES.md` section 6
  in step.
- **Effort:** small for the code; the text is the DPO's.

## 3. Decisions

Taken 28 October 2026:
- **Window (2.2):** the last 90 days for all three lists, ratings included, shown first; all time one click
  away on the home page. The medals follow the last 90 days.
- **Email verification (2.3):** not built. An "I'm human" check on sign-up (Cloudflare Turnstile) instead, and
  "Continue with Google" for patrons only; staff keep their passwords. Existing accounts: nothing to do.
- **Google Cloud project:** made and kept by the developer, preferably under an agency Google account so it
  outlives any one person's; one OAuth client for local and staging, one for production
  (`DEPLOYMENT_CHECKLIST.md`).
- **Statistics watch (2.6):** sign-up bursts added to it. Guests' addresses are for **developer and super
  admin** only, kept at most 90 days.

Still open: nothing.
