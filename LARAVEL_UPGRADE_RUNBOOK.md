# eLibrary — Laravel 8 → 13 Upgrade Runbook

**Target:** Laravel **13** (security-supported to **March 2028**). Fallback landing spot: Laravel **12** (to Feb 2027) if you choose to stop early.
**Verdict from the dependency dry-run:** ✅ **No hard blockers.** Every dependency has a compatible release or a drop-in replacement.
**Current state:** Laravel `v8.83.29`, PHP available in Laragon = `8.3.16` / `8.3.26`.

> Upgrades go **one major at a time**: `8 → 9 → 10 → 11 → 12 → 13`. Do **not** jump straight to 13.
> After each hop: run the app, smoke-test, commit. There is **no automated test suite**, so testing is manual.

---

## 0. Pre-flight (do once, before touching anything)

- [ ] **Backups confirmed** — code + `elibrarydb` dump. (User has confirmed backup exists.)
- [ ] Work on a **copy or branch**, never in place. If not a git repo yet: `git init && git add -A && git commit -m "pre-upgrade baseline"`.
- [ ] Take a fresh DB dump as a checkpoint:
  `C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe -u root elibrarydb > backup_elibrarydb_preupgrade.sql`
- [ ] Confirm PHP: Laravel 13 needs **PHP 8.3+** (you have it). Point Laragon at PHP 8.3 for the whole upgrade.
- [ ] Note the app has **no tests** → keep this manual **smoke-test checklist** handy for every hop (see §6).

---

## 1. Package changes (the whole picture)

### Swaps & removals (do these as you cross the hop that requires them)
| Action | Package | Replace with / note |
|---|---|---|
| ❌ **REMOVE** | `fruitcake/laravel-cors` | CORS is built into Laravel core since v9 (`Illuminate\Http\Middleware\HandleCors`). Do at **hop 8→9**. |
| 🔁 **SWAP** | `facade/ignition` (dev) | `spatie/laravel-ignition` `^2.12`. Do at **hop 8→9**. Also drop `facade/flare-client-php` (pulled automatically). |
| 🔁 **SWAP** | `laravelcollective/html` `^6.4` (abandoned) | `konekt/html` `^6.8` (drop-in, keeps `Collective\Html` namespace). **NOTE:** the dry-run found **zero** `Form::`/`Html::` calls in Blade — verify and consider **removing** it entirely instead. Do at **hop 10→11**. |

### Version bumps (land these at the hop shown; final target constraints listed)
| Package | Current | Final constraint (L13) | Bump at hop |
|---|---|---|---|
| `laravel/framework` | `^8.75` | `^13.0` | each hop |
| `laravel/sanctum` | `^2.11` | `^4.0` | 10→11 |
| `laravel/scout` | `^9.4` | `^11.0` | as needed |
| `laravel/tinker` | `^2.5` | `^3.0` | 10→11 |
| `laravel/breeze` (dev) | `^1.9` | `^2.4` | 10→11 |
| `laravel/sail` (dev) | `^1.0` | `^1.63` | anytime |
| `nunomaduro/collision` (dev) | `^5.10` | `^8.0` | 10→11 |
| `phpunit/phpunit` (dev) | `^9.5` | `^11.0` ⚠️ **not 12/13** (those need PHP 8.4) | 10→11 |
| `spatie/laravel-permission` | `^6.1` | `^8.0` | 12→13 |
| `barryvdh/laravel-dompdf` | `^1.0` | `^3.1` | 9→10 |
| `milon/barcode` | `^10.0` | `^13.0` | 12→13 |
| `symfony/http-client` | `^5.4` | `^7.0` | 10→11 |
| `scriptotek/marc` | `^2.2` | `^3.0` | anytime (agnostic) |
| `meilisearch/meilisearch-php` | `^0.23` | `^1.16` (pulled by Scout 11) | **see §2 — NO code refactor; enabled post-upgrade** |
| guzzle, nyholm/psr7, php-http/*, faker, mockery | — | current | auto-resolve, no action |

---

## 2. Meilisearch — a POST-upgrade config task (NOT a code refactor)

**Investigated 2026-07-11 — the earlier "client refactor" premise was wrong.** The four
`use MeiliSearch\Client;` lines are the **only** references to the client; there is **no**
`new Client(...)` and **no** `->search()`/`->index()` call anywhere — all raw-client code is
commented out. Search is done the idiomatic Scout way: `App\Models\ViewCatalog::search($q)->...`,
which is **engine-agnostic**. The app currently runs `SCOUT_DRIVER=database`.

**Consequence:** switching to Meilisearch requires **no controller changes**. It's a config +
index-settings + import task, and it should be done **AFTER** the Laravel upgrade, on the modern
stack (Meilisearch server **v1.x** + Scout **11** + client **1.16**). Doing it on Laravel 8 would
pin you to the legacy Meilisearch server **v0.27.0** (early 2022) that the upgrade then replaces.

**Decision (with the user, 2026-07-11): defer Meilisearch until after the upgrade.**

### Post-upgrade enablement steps (do these once on Laravel 13 + Scout 11)
1. **Run the server:** download Meilisearch **v1.x** for Windows, start with a master key, e.g.
   `meilisearch.exe --master-key <KEY>` (listens on `127.0.0.1:7700`).
2. **Model prep** — `app/Models/ViewCatalog.php` `toSearchableArray()` currently indexes only
   `id, isbn, issn, accession_number, title_statement, material_type, pub_year`. The controllers
   filter/sort on fields that must ALSO be indexed and declared filterable/sortable:
   add **`material_type_id`**, **`is_published`**, **`series_statement_id`** (and keep `pub_year`).
   > ⚠️ Do this only when flipping to Meilisearch — adding numeric fields to `toSearchableArray()`
   > also changes what the **database** driver LIKE-matches, so don't apply it while on the DB driver.
   > For the `->where('deleted_at', null)` filter, prefer **not indexing** soft-deleted/unpublished
   > rows via `shouldBeSearchable()` rather than a Meili `null` filter (Meili null-filtering is awkward).
3. **Index settings** — add to `config/scout.php` under `meilisearch` an `index-settings` block for
   the `view_catalogs` index: `filterableAttributes => ['material_type_id','is_published','series_statement_id','pub_year']`,
   `sortableAttributes => ['pub_year']`. Apply with `php artisan scout:sync-index-settings`.
4. **.env** — set `SCOUT_DRIVER=meilisearch`, `MEILISEARCH_HOST=http://127.0.0.1:7700`,
   `MEILISEARCH_KEY=<KEY>` (these keys are already present but commented out).
5. **Import** — `php artisan scout:import "App\Models\ViewCatalog"` (only ~158 rows; instant).
6. **Test** — OPAC search (grid + list layouts), material-type facet filters, year range, sort by
   `pub_year`. Confirm results match the DB-driver behaviour, plus Meili's typo-tolerance.
   No controller edits needed — `ViewCatalog::search()` now routes to Meilisearch automatically.

---

## 3. The hop-by-hop framework upgrade

For **each hop**: consult the official upgrade guide, edit `composer.json`, run `composer update`, fix breakages, run the app, smoke-test (§6), commit. Official guides:
`https://laravel.com/docs/{9,10,11,12,13}.x/upgrade`

### Hop 1 — Laravel 8 → 9
- [ ] `composer remove fruitcake/laravel-cors` and remove its entry from `app/Http/Kernel.php` middleware; add core `\Illuminate\Http\Middleware\HandleCors::class` to the global middleware (it's usually already referenced in fresh L9 — check).
- [ ] Dev tooling swap: `composer remove facade/ignition` then `composer require --dev spatie/laravel-ignition:^1` *(v1 is the last for L9; you'll bump it to ^2 at the L10 hop)*.
- [ ] Set `"laravel/framework": "^9.0"` and run `composer update`.
- [ ] **Key breakages for this app:** Flysystem **1 → 3** (file/storage APIs — check the download/file code), Symfony 5 → 6, `$dates` property removed (use `$casts` with `datetime`), custom pagination views. Carbon stays 2.
- [ ] Smoke-test → commit.

### Hop 2 — Laravel 9 → 10
- [ ] `composer require --dev spatie/laravel-ignition:^2` (now L10-compatible).
- [ ] `composer require barryvdh/laravel-dompdf:^2` (interim; bump to ^3 later or jump straight to ^3 if it resolves).
- [ ] Set `"laravel/framework": "^10.0"`, `"laravel/sanctum": "^3.2"` (interim), run `composer update`.
- [ ] PHP floor becomes **8.1+** (you're fine). Dropped: PHP 8.0.
- [ ] **Key breakages:** method signatures now have native types (custom overrides of framework methods may need type hints); `dispatchNow` removed → `dispatchSync`; Redis/predis changes (n/a here). Minimal for this app.
- [ ] Smoke-test → commit.

### Hop 3 — Laravel 10 → 11  *(the structural one)*
- [ ] Bump the L11 cluster together:
  `composer require laravel/framework:^11.0 laravel/sanctum:^4.0 laravel/tinker:^3.0 laravel/scout:^11.0 symfony/http-client:^7.0`
  `composer require --dev laravel/breeze:^2.0 nunomaduro/collision:^8.0 phpunit/phpunit:^11.0`
- [ ] **Package swap:** `composer remove laravelcollective/html` → **first confirm** it's unused (0 Blade `Form::`/`Html::` found). If anything uses it: `composer require konekt/html:^6.8` (keeps `Collective\Html` namespace, no Blade changes). Otherwise leave it removed.
- [ ] **Structural change (important):** L11 introduced a slimmed skeleton (no `app/Http/Kernel.php`, no `app/Console/Kernel.php`, middleware/exceptions configured in `bootstrap/app.php`, consolidated `config/`). **You do NOT have to adopt it** — an in-place upgrade keeps your classic structure working. Recommended: **keep the classic structure** to minimize churn; only migrate to the new skeleton later if you want.
- [ ] PHP floor becomes **8.2+** (you're fine).
- [ ] **Key breakages:** Carbon **2 → 3** (date diff/format edge cases — check date handling in circulation/fines/reports), `Str`/`Stringable` tweaks, per-second rate limiting, `password` default hash changes. Sanctum 4 config/migration review (low usage here — just `HasApiTokens`).
- [ ] Smoke-test **hard** (this is the riskiest hop) → commit.

### Hop 4 — Laravel 11 → 12
- [ ] Set `"laravel/framework": "^12.0"`, run `composer update`.
- [ ] L12 is intentionally a **low-breakage** release (mostly continues L11). PHP floor stays 8.2+.
- [ ] Bump any package still pinned below 12 (most already resolve).
- [ ] Smoke-test → commit. **Decision point:** you could stop here (supported to Feb 2027) or continue to 13.

### Hop 5 — Laravel 12 → 13
- [ ] Bump the final cluster:
  `composer require laravel/framework:^13.0 spatie/laravel-permission:^8.0 milon/barcode:^13.0 barryvdh/laravel-dompdf:^3.1 scriptotek/marc:^3.0`
- [ ] Keep `phpunit/phpunit:^11` (do **not** move to 12/13 — those need PHP 8.4; L13 accepts PHPUnit 11).
- [ ] PHP floor becomes **8.3+** (you're on 8.3 ✓). Symfony under the hood → 7.x.
- [ ] `composer update`, resolve, run.
- [ ] **Key breakages:** review the [L13 upgrade guide](https://laravel.com/docs/13.x/upgrade) for the final deltas; generally modest coming from 12.
- [ ] Full smoke-test → commit → **done.**

---

## 4. After the final hop
- [ ] `php artisan config:clear && php artisan cache:clear && php artisan view:clear && php artisan route:clear`
- [ ] `composer dump-autoload -o`
- [ ] Re-run the **security quick-wins** from the audit while you're in here: set `APP_DEBUG=false` for any non-local use, remove the exposed `public/*.zip` archives, add auth to the public Inquiry/Migrate/Sync routes, and move downloadable materials out of `public/` (see the security audit).
- [ ] Update `composer.json` `"php"` constraint from `^7.3|^8.0` to `^8.3`.

---

## 5. Rollback (per hop)
Because each hop is its own commit + the DB may get migration changes:
- Code: `git reset --hard <previous-hop-commit>` (or restore the code copy).
- DB: `mysql -u root elibrarydb < backup_elibrarydb_preupgrade.sql` (only if a hop ran migrations you need to undo).
- `composer install` to restore the exact `vendor/` for that commit.

---

## 6. Smoke-test checklist (run after EVERY hop)
No automated tests exist, so manually exercise each area:

- [ ] App boots — `http://elibrary.test` loads `/home` without a 500.
- [ ] **Login** works (admin `admin@email.com`); login throttling still triggers after 5 bad tries.
- [ ] **OPAC search** returns results (Meilisearch path).
- [ ] **Admin dashboard** loads (stats/`DB::raw` aggregate queries).
- [ ] **Cataloging / MARC import** — import a record; MARC libs (`scriptotek/marc`, `pear/file_marc`) still parse.
- [ ] **Circulation** — borrow/return a copy; check dates/fines (Carbon 3 risk).
- [ ] **Barcode / QR generation** (`milon/barcode`).
- [ ] **PDF generation** (`dompdf` — reports/receipts).
- [ ] **File download** flow (the patron download path).
- [ ] **Acquisition** create/edit (uses Meilisearch client directly).
- [ ] No new entries in `storage/logs/laravel.log`.

---

## Quick reference — is 13 reachable? (dry-run result)
**Yes.** 2 swaps (`laravelcollective/html`→`konekt/html` — possibly removable; `facade/ignition`→`spatie/laravel-ignition`), 1 removal (`fruitcake/laravel-cors`→core), ~9 version bumps, and **1 real code refactor (Meilisearch 0.23→1.16 in 4 controllers)**. Everything else auto-resolves. PHP 8.3 (which you have) satisfies Laravel 13; just keep PHPUnit at 11.
