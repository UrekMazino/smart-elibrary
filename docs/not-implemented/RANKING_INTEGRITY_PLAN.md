# Fair rankings — what is still to build

**Status:** planning only. What is built (views per viewer-day, downloads per reader, the weighted Highest
Rating, ratings from readers only, the download limit) is in
[`../implemented/RANKING_INTEGRITY.md`](../implemented/RANKING_INTEGRITY.md).
**Last updated:** 2026-10-28.

---

## 1. Goal
Close the gaps that remain after 28 October 2026, so the top-10 lists and their medals reflect real readers:

| Gap | Step |
|---|---|
| A banned account's past views, downloads and ratings still count | 2.1 |
| Old activity keeps a record on top forever | 2.2 |
| Throwaway accounts can download and rate | 2.3 |
| A guest switching address or browser is a new viewer; bots that load pages count | 2.4 |
| The search page's "Most viewed" sort still counts every old reload | 2.5 |
| Nobody sees sudden jumps; there is no way to cancel an account's counts | 2.6 |
| Fake sign-ups in bulk (only if it starts happening) | 2.7 |
| Old duplicate view rows still in the table | 2.8 |
| The Privacy notice covers only the statistics | 2.9 |

Order: 2.1 and 2.2 first (queries only, an hour each), then 2.3 and 2.4. The rest when needed.

## 2. Steps

### 2.1 A ban takes the account out of the rankings
- **Why:** banning stops new downloads and sign-ins, but the account's earlier rows still count.
- **How:** in `getMostDownloaded()`, `getMostViewed()` and `getHighestRated()`, leave out rows whose
  `user_id` belongs to a banned user (`users.is_banned = 1`): a `whereNotIn('user_id', …)` or a left join.
  Guest rows (no `user_id`) are unaffected. Lifting the ban brings the rows back; nothing is deleted.
- **Effort:** small; no migration. **Test:** ban a patron with the only download of a record: the record
  leaves Most Downloaded; lift the ban: it comes back.

### 2.2 A time window for the rankings
- **Why:** a record that was gamed (or popular) years ago stays on top; recent reading is more useful.
- **How:** `->where('created_at', '>=', now()->subDays(90))` on the three ranking queries (ratings: keep all
  time, or 365 days, since ratings are few). The record page's counts stay all time. Name it once (a
  constant) so the shelves and the medals use the same window.
- **Decide:** 90 days for views and downloads? All time or a year for ratings?
- **Effort:** small; no migration. **Test:** a download 100 days old doesn't count; one 10 days old does.

### 2.3 Verified email before downloads and ratings
- **Why:** sign-up needs no proof of a working address, so one person can open many accounts.
- **How:** `User implements MustVerifyEmail` (the interface is imported but not implemented); the `verified`
  middleware on `POST /download`, `POST /review/submit`, `POST /review/quick` (and reading the full text).
  Laravel sends the link; the verify routes already exist (`routes/auth.php`, signed, throttled).
- **Existing accounts:** mark them verified once (`email_verified_at = created_at`) so nobody is locked out,
  or ask them to verify at their next sign-in. A one-line migration or command either way.
- **Depends on:** outgoing mail working in production (see the SwiftMailer notes: the old `getSwiftMailer()`
  calls break mail flows); a clear "check your email" page in the OPAC's look.
- **Effort:** medium. **Test:** an unverified patron can't download or rate and is told why; a verified one
  can; a staff account is unaffected.

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

### 2.7 A challenge on sign-up, if fake accounts appear
- **When:** only if 2.3 isn't enough (sign-up is already limited to 10 a minute per address).
- **How:** Cloudflare Turnstile or hCaptcha on the registration form, checked on the server. Both are
  lighter on privacy than Google reCAPTCHA. The Content Security Policy needs their script and frame
  origins (`SecurityHeaders`); the Privacy notice must name the provider.
- **Effort:** medium.

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

## 3. Decisions needed

- The ranking window (2.2): 90 days for views and downloads; all time or a year for ratings?
- Existing accounts and verification (2.3): mark them verified, or ask them to verify at next sign-in?
- Whether staff should see guest address hashes at all (2.6), and for how long (at most 90 days).
