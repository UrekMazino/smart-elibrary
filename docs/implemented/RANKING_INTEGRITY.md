# Fair rankings: counting people, not requests

Goal: the home page's top-10 lists (Most Downloaded, Most Viewed, Highest Rating), the medals they award
and the counts on a record page can't be pushed up by reloading a page, a script, downloading a file again
or one hand-picked rating.

**Status:** built and enforced on `FNRI-Week-4` since 28 October 2026 (commit `6a3c4c3`). What is still to
come: [`../not-implemented/RANKING_INTEGRITY_PLAN.md`](../not-implemented/RANKING_INTEGRITY_PLAN.md).

---

## 1. Where it stands

| Signal | Counted as | Guards |
|---|---|---|
| **Views** | one per viewer per material a day; rankings and the record page count *viewer-days* | crawlers (user agent) and staff not counted; at most 120 counted views an hour from one viewer |
| **Downloads** | the readers who downloaded a material, not the files sent | sign-in required, patrons only, banned accounts refused, staff not counted; 30 downloads a minute per account |
| **Ratings** | one per patron per material, from its readers only (read or downloaded it) | Highest Rating ranks by a weighted average, so one vote can't top it |

A *viewer* is the signed-in account, or for a guest a keyed hash of their IP address and browser (see
section 3 for what that hash is and where it lives).

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
  record page): `getMostDownloaded()`, `getMostViewed()`, `getHighestRated()`, each cached 10 minutes
  (`home_top_downloaded`, `home_top_viewed`, `home_top_rated`; `clearHomeCache()` empties them).
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

## 4. Deployment and operations

- **Behind a proxy or load balancer, trust it** (`app/Http/Middleware/TrustProxies.php`, `$proxies`, also in
  `DEPLOYMENT_CHECKLIST.md`). Otherwise every guest has the proxy's address: guests using the same browser
  look like one viewer, and the 120-an-hour cap is shared by all of them.
- **One cache for all web servers.** The day's "already counted" keys are in the cache (`CACHE_DRIVER`,
  `file` now). With several servers, use a shared store (Redis, database), or a viewer is counted once per
  server.
- **Clearing the cache** (`php artisan cache:clear`) forgets the day's keys: a viewer who comes back that
  day counts once more. Harmless.
- **Changing `APP_KEY`** changes every guest's hash; the same as clearing the cache.
- Nothing to migrate, nothing to backfill: the rankings read the existing tables.

## 5. Tuning

| What | Where | Now |
|---|---|---|
| Counted views per viewer an hour | `saveView()`, `RateLimiter::tooManyAttempts(…, 120)` and `hit(…, 3600)` | 120 |
| How long a view blocks the next | `saveView()`, `now()->endOfDay()` | until midnight |
| Votes needed before a record's own average weighs as much as the library's | `getHighestRated()`, `$m` | 3 |
| Downloads a minute per account | `routes/web.php`, `throttle:30,1` on `POST /download` | 30 |

After a change, `clearHomeCache()` (or wait 10 minutes) to see it in the shelves.

## 6. Tests

`tests/Feature/RankingIntegrityTest.php`:
- a reload counts one view a day (and the next day counts again);
- guests are told apart by address and browser, and `views` stores neither;
- Most Viewed counts old reloads once a day;
- Most Downloaded counts readers, not files;
- one 5-star vote does not top Highest Rating (and the shelf shows the plain average);
- only a material's readers can rate it (submit and quick review), and the record page says why;
- `POST /download` carries `throttle:30,1`.

Also following these rules: `HomeShelvesTest` ("1 reader downloaded"), `SecurityFixesTest` and
`StaffOpacTest` (the patron reads or downloads before rating).

## 7. What it does not stop yet

- **Many accounts.** Sign-up has no email verification, so someone can make accounts to download and rate.
- **Many addresses.** A guest switching IP address or browser string is a new viewer each time; the hourly cap
  is per viewer.
- **Old guest views** saved before 28 October still count one each.
- **Nobody is watching.** There is no report of sudden jumps, and no way to cancel an account's counts.

Each of these has a step in [`RANKING_INTEGRITY_PLAN.md`](../not-implemented/RANKING_INTEGRITY_PLAN.md).
