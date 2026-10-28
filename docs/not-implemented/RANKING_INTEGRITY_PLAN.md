# Fair rankings — what is still to build

**Status:** planning; steps 2.1, 2.2 and 2.7 built on 28 October 2026, and 2.3 replaced by them (section 3).
What is built (views per viewer-day, downloads per reader, the weighted Highest Rating, ratings from readers
only, the download limit, the 90-day window, banned accounts left out) is in
[`../implemented/RANKING_INTEGRITY.md`](../implemented/RANKING_INTEGRITY.md).
**Last updated:** 2026-10-28 (decisions, and 2.1, 2.2, 2.7 built).

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
| Nobody sees sudden jumps; there is no way to cancel an account's counts | 2.6 | To do |
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

### 2.6 A statistics watch for staff
- **Why:** to see manipulation that gets past the rules, and undo it.
- **What the page shows** (new permission, e.g. `manage.statistics`):
  - records whose views or downloads today are far above their usual day (e.g. 5× their 30-day daily average);
  - accounts with unusually many downloads or ratings in a day;
  - guests hitting the hourly view cap (needs the hash stored, below).
- **Actions:** exclude an account's views, downloads and ratings from the rankings (a `ranking_exclusions`
  table, or an `excluded_from_rankings_at` column on `users`), reversible, with who did it and when.
- **Storing addresses**, under `SECURITY_NOTES.md` section 6 (write the row in its table first):
  a `visitor_hash` column (the keyed hash, not the address) on `views` for guests; the address itself only if
  investigations need it, in its own table, deleted after at most 90 days by a scheduled job; shown only on
  this page; and the Privacy notice updated to match.
- **Depends on:** migrations. Rehearse them on a restored copy of the database before the live run, as for
  the database-documentation migrations (`DEPLOYMENT_CHECKLIST.md`).
- **Effort:** large. **Test:** a spike is listed; excluding an account removes its rows from the three lists
  and the medals; undoing brings them back; the purge job deletes rows past 90 days.

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

Still open:
- Whether staff should see guest address hashes at all (2.6), and for how long (at most 90 days).
