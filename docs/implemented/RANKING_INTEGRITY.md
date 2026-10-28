# Fair rankings: counting people, not requests

Goal: the home page's top-10 lists (Most Downloaded, Most Viewed, Highest Rating), the medals they award
and the counts on a record page can't be pushed up by reloading a page, a script, downloading a file again
or one hand-picked rating.

**Status:** built and enforced on `FNRI-Week-4` since 28 October 2026 (commit `6a3c4c3`); the 90-day window,
leaving banned accounts out and the statistics watch (section 4), the same day. What is still to come:
[`../not-implemented/RANKING_INTEGRITY_PLAN.md`](../not-implemented/RANKING_INTEGRITY_PLAN.md).

---

## 1. Where it stands

| Signal | Counted as | Guards |
|---|---|---|
| **Views** | one per viewer per material a day; rankings and the record page count *viewer-days* | crawlers (user agent) and staff not counted; at most 120 counted views an hour from one viewer |
| **Downloads** | the readers who downloaded a material, not the files sent | sign-in required, patrons only, banned accounts refused, staff not counted; 30 downloads a minute per account |
| **Ratings** | one per patron per material, from its readers only (read or downloaded it) | Highest Rating ranks by a weighted average, so one vote can't top it |

A *viewer* is the signed-in account, or for a guest a keyed hash of their IP address and browser (see
section 3 for what that hash is and where it lives).

All three lists:
- **Rank the last 90 days** (`rankingDays()`), so old activity, or old gaming, drops out by itself. Above the
  shelves, *Top 10 — Last 90 days · All time* switches all three to all time and back; each top-10 shelf names
  its window under its title. The page opens on the last 90 days. A list with nothing in the last 90 days
  says so ("No ratings in the last 90 days.") and offers *See all time*. Highest Rating's library average
  (`C`, below) is that of the same window.
- **Medals follow the last 90 days**, wherever they show; their tooltips and the record page say so.
- **Leave banned accounts out** (`rankingRows()`): a banned account's views, downloads and ratings stop
  counting as soon as the ban is set, and count again when it is lifted. Nothing is deleted. A guest's view
  (no account) counts as before. The same for an account **taken out of the rankings** on the statistics
  watch (section 4), until it is put back.
- The record page's "Views" and "Downloads" stay all time.

## 2. How each count works

### Views — `saveView()` (`app/Http/helper.php`)
Called by `HoldingController` when a record page opens and when its full text is read.

1. Crawlers (`isCrawler()`) and staff: not counted.
2. The viewer: `'u' . user id`, or for a guest `'g' . visitorHash()`.
3. Over 120 counted views in the last hour for that viewer (`RateLimiter`, key `views:{viewer}`): not counted.
4. `cache()->add('viewed:{catalog_id}:{viewer}', 1, now()->endOfDay())`: only the first view of the day
   gets in. The day ends at midnight in the app's timezone (`Asia/Manila`).
5. A row in `views` (`catalog_id`, `user_id` or `is_guest = 'YES'`); no address, no browser string.

Counting them (`viewerDaysSql()`): a signed-in reader counts once a day per material, **including rows saved
before 28 October**, when every reload was a row; a guest's rows count as they are (old guest rows can't be
told apart, new ones are already one a day). Used by `getMostViewed()` and the record page (`countNum('view')`).

### Downloads — `DownloadController::index()`
Every download still saves a row (statistics, the To Review list). `getMostDownloaded()` and the record page
(`countNum('download')`) count `DISTINCT user_id`; the shelf says "*2 readers downloaded*". The route has
`throttle:30,1` (per account; `App\Http\Middleware\ThrottleRequests` counts each route on its own).

### Ratings — `ReviewController`
- One rating per patron per material (unique key on `ratings`), as before.
- **Readers only:** `hasReadOrDownloaded()` must be true: a row in `downloads`, or in `reads` (written when a
  signed-in patron opens the full text in the reader). Otherwise `create()` sends the patron back to the
  record page with "Read or download this material first, then you can rate it." and `quickStore()` answers
  403. The record page greys out *Submit review* with the same words. The quick-review popup only offers
  itself after a read or download anyway.
- **Highest Rating** (`getHighestRated()`) orders by a weighted average, the formula IMDb's charts use:

  `score = v / (v + m) × R + m / (v + m) × C`

  `v` = the record's votes, `R` = its average, `C` = the average of all ratings of published records,
  `m = 3`. A record's score is pulled toward the library's average until it has a few votes. Example
  (`C = 3.75`): 4 votes averaging 4.75 score 4.32; a single 5-star vote scores 4.06. The shelf still shows
  the plain average and the number of ratings ("★ 5.0 · 1 rating").

### Where the counts show
- The three top-10 shelves on the home page, and the medals they award (shelves, search results, Quick view,
  record page): `getMostDownloaded()`, `getMostViewed()`, `getHighestRated()`, each taking a window
  (`'recent'`, the default, or `'all'`) and each cached 10 minutes per window (`home_top_downloaded_recent`,
  `home_top_downloaded_all` and so on; `clearHomeCache()` empties them). The rows that count: `rankingRows()`.
  Setting or lifting a ban empties them at once (`AdminController::toggleUserFlag`), and so does taking an
  account out of the rankings or putting it back (`StatisticsWatch`).
- The record page's "Views" and "Downloads": `countNum()`.
- **Not changed:** the search page's "Most viewed" sort reads the view count stored in the search index
  (`ViewCatalog::toSearchableArray()`, plain row count). New rows are already one a day, so only old
  duplicates differ. The admin dashboard's activity figures count every row, as activity.

## 3. Privacy (Data Privacy Act, RA 10173)

The rule is `SECURITY_NOTES.md` section 6 (it replaced R15's "no visitor IP addresses" on 28 October 2026).
What this feature uses, on those terms:

- **Guest viewers:** `visitorHash()` = the first 32 hex characters of HMAC-SHA256 of `IP address | browser`,
  keyed with `APP_KEY`. It lives only in the cache (`viewed:…`, `views:…`) until the day ends or the hour
  passes; it is never written to the database. The `views` table has no address or browser columns
  (`PrivacyTest`, `RankingIntegrityTest`).
- **Notice:** the OPAC's Privacy notice (footer link → `#modalPrivacy` in `opac/layout/master.blade.php`)
  says what the statistics use and why. The rest of the agency's notice is to be written with its Data
  Protection Officer (see the plan).

## 4. The statistics watch

Administration → Accounts → Statistics watch (`/admin/statistics-watch`; permission `access.statistics.watch`,
given to super admin and developer; `StatisticsWatchController`, `App\Services\StatisticsWatch`). For one day,
today unless another is picked, against the 30 days before it:

- **Materials with a spike:** viewers (viewer-days) or readers that day at least 5 times their usual day, and at
  least 10. Counted as the lists count them, so accounts already out of the rankings, or banned, are left out:
  taking out the accounts behind a spike clears it. Each spike shows how many accounts and guests made it, and
  lists the accounts, with "Take all out".
- **Unusual accounts:** patrons who in the day viewed 100 materials or more, downloaded 20 files or more, or
  gave 10 ratings or more. Accounts made in the week up to the day are marked "New", those made or linked
  with Google "Google". Banned and taken-out accounts stay listed, with their status.
- **Sign-ups:** the day's patron sign-ups against a usual day, and each **burst**: an hour with at least 10
  sign-ups and 5 times the usual hour. Its accounts are listed with what each has done since, and "Take all
  out" takes the whole hour's accounts out at once.
- **Out of the rankings now:** every account taken out, since when, by whom and why, with "Put back".

**Taking out** (one account, a spike's or a burst's): a row in `ranking_exclusions` (who, when, the optional
reason); `rankingRows()` leaves the account's views, downloads and ratings out of the three lists and the
medals until it is put back, which fills `lifted_by` and `lifted_at` and keeps the row. Patrons only; the
account can still sign in and use the library (to stop it altogether, ban it). Neither asks for the
password: both can be undone. 30 a minute each.

**Where it shows:** a card on Administration → Accounts ("N things to look at today"), and for staff who
may open it, a "Statistics watch" tile in the dashboard's Needs attention: the day's spikes, plus the
unusual accounts and bursts with someone not yet dealt with (`StatisticsWatch::toCheck()`).

**Not yet:** guests (a guest at the hourly view cap, who made a spike). That needs the guest's hash stored,
and the address for developer and super admin only: the plan's step 2.6.

## 5. Deployment and operations

- **Behind a proxy or load balancer, trust it** (`app/Http/Middleware/TrustProxies.php`, `$proxies`, also in
  `DEPLOYMENT_CHECKLIST.md`). Otherwise every guest has the proxy's address: guests using the same browser
  look like one viewer, and the 120-an-hour cap is shared by all of them.
- **One cache for all web servers.** The day's "already counted" keys are in the cache (`CACHE_DRIVER`,
  `file` now). With several servers, use a shared store (Redis, database), or a viewer is counted once per
  server.
- **Clearing the cache** (`php artisan cache:clear`) forgets the day's keys: a viewer who comes back that
  day counts once more. Harmless.
- **Changing `APP_KEY`** changes every guest's hash; the same as clearing the cache.
- Nothing to backfill: the rankings read the existing tables. One migration, for the statistics watch:
  `ranking_exclusions` and the `access.statistics.watch` permission (`2026_10_28_200000_statistics_watch`).

## 6. Tuning

| What | Where | Now |
|---|---|---|
| Counted views per viewer an hour | `saveView()`, `RateLimiter::tooManyAttempts(…, 120)` and `hit(…, 3600)` | 120 |
| How long a view blocks the next | `saveView()`, `now()->endOfDay()` | until midnight |
| Votes needed before a record's own average weighs as much as the library's | `getHighestRated()`, `$m` | 3 |
| The lists' default window (and the medals') | `rankingDays()` in `app/Http/helper.php` | 90 days |
| Downloads a minute per account | `routes/web.php`, `throttle:30,1` on `POST /download` | 30 |
| Statistics watch: the usual day | `StatisticsWatch::BASELINE_DAYS` | the 30 days before |
| A material's spike | `StatisticsWatch::SPIKE_TIMES`, `SPIKE_AT_LEAST` | 5× its usual day, and at least 10 |
| An unusual account, in a day | `StatisticsWatch::ACCOUNT_VIEWS`, `ACCOUNT_DOWNLOADS`, `ACCOUNT_RATINGS` | 100 materials viewed, 20 downloads, 10 ratings |
| A sign-up burst, in an hour | `StatisticsWatch::BURST_AT_LEAST`, `BURST_TIMES` | 10 sign-ups, and 5× the usual hour |

After a change, `clearHomeCache()` (or wait 10 minutes) to see it in the shelves.

## 7. Tests

`tests/Feature/RankingIntegrityTest.php`:
- a reload counts one view a day (and the next day counts again);
- guests are told apart by address and browser, and `views` stores neither;
- Most Viewed counts old reloads once a day;
- Most Downloaded counts readers, not files;
- one 5-star vote does not top Highest Rating (and the shelf shows the plain average);
- only a material's readers can rate it (submit and quick review), and the record page says why;
- the lists rank the last 90 days, the home page switches to all time, the medals stay on 90 days, and a list
  empty in the last 90 days says so;
- a ban takes the account out of the lists at once, and lifting it brings the account back (a guest's view
  still counts);
- `POST /download` carries `throttle:30,1`.

`tests/Feature/StatisticsWatchTest.php`: super admin and developer have the watch, other staff don't; a material
far above its usual day is a spike, one at its usual level or with too few is not, and taking out its accounts
clears a downloads spike; accounts over a threshold are flagged, those just under are not; an hour of 12 sign-ups
is a burst, with its accounts; taking accounts out takes their downloads out of Most Downloaded at once (staff
skipped), and putting one back brings it back, with who and when kept; the dashboard and Accounts say what to
look at until it is dealt with; another day can be watched, not a day to come.

Also following these rules: `HomeShelvesTest` ("1 reader downloaded"), `SecurityFixesTest` and
`StaffOpacTest` (the patron reads or downloads before rating).

## 8. What it does not stop yet

- **Many accounts by hand.** Sign-up has an "I'm human" check (Cloudflare Turnstile), which stops scripts
  making accounts in bulk, and "Continue with Google" accounts come with an address Google has confirmed. A
  person can still make a few accounts by hand with different addresses; the one-per-account rules and the
  statistics watch (section 4) are what limit them. Email verification was decided against (28 October 2026).
- **Many addresses.** A guest switching IP address or browser string is a new viewer each time; the hourly cap
  is per viewer.
- **Old guest views** saved before 28 October still count one each.
- **Guests on the statistics watch.** It shows spikes and accounts, and takes accounts out of the rankings, but
  can't name a guest: a spike made by guests stays, with nothing to take out.

Each of these has a step in [`RANKING_INTEGRITY_PLAN.md`](../not-implemented/RANKING_INTEGRITY_PLAN.md).
