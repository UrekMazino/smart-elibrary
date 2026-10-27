# Meilisearch (OPAC search)

The OPAC search page (`/results`) runs on **Meilisearch v1.12** through Laravel Scout: one index,
`view_catalogs`, owned by the eLibrary. The page's engine is `App\Services\CatalogSearch` (one
multi-search per page view: results, filter counts, year histogram) and the search box's suggestions.

The AI side (`hermes-with-rag`) does **not** use Meilisearch — it has its own LanceDB index and reads
the eLibrary's MySQL directly. Run one Meilisearch, for the eLibrary.

## Where it runs

| Machine | How | Start |
|---|---|---|
| Production / Windows | Docker Desktop, `docker-compose.yml` (v1.12, `127.0.0.1:7700`, master key from `.env` `MEILI_MASTER_KEY`, persistent volume, `restart: unless-stopped`) | `docker compose up -d` |
| Linux without Docker (dev laptop) | the standalone program, same version: `meilisearch-linux-amd64` from the [v1.12.8 release](https://github.com/meilisearch/meilisearch/releases/tag/v1.12.8), kept in `~/meilisearch` with a `meili.sh` script (reads `MEILI_MASTER_KEY` from the project's `.env`; data in `~/meilisearch/data.ms`), run as a systemd user service (`~/.config/systemd/user/meilisearch.service`: `ExecStart=%h/meilisearch/meili.sh run`, `Restart=on-failure`, `WantedBy=default.target`) | starts at login; `systemctl --user enable --now meilisearch` once; `meili.sh status` / `restart` / `stop` |

Health check: `curl http://127.0.0.1:7700/health` → `{"status":"available"}`.

Stay on **v1.12 or newer**: the page uses facet search (v1.3+), `IN [...]` and `IS NULL` filters,
`sortFacetValuesBy` and `attributesToSearchOn`.

## `.env`

```
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=<the master key>
MEILI_MASTER_KEY=<the same master key>      # read by docker-compose.yml / meili.sh
```

`SCOUT_DRIVER` must be `meilisearch`: Scout then updates the index whenever a record is saved.
Then `php artisan config:clear` (or `config:cache` in production).

## First setup, and after deploying a search change

```bash
php artisan migrate                                   # view_catalogs.pub_year_num + .subjects, saved_records
php artisan catalog:rebuild-search-fields             # dry run: what would change
php artisan catalog:rebuild-search-fields --apply     # fills subjects + numeric years, syncs the index
                                                      # settings, then re-indexes every record
```

The same by hand: `php artisan scout:sync-index-settings`, then `scout:flush` and
`scout:import "App\Models\ViewCatalog"`.

**Order matters:** sync the settings before people search. Until the filterable / sortable
attributes are registered, Meilisearch rejects the page's queries and it shows "Search is
temporarily unavailable".

## What the index holds

From `ViewCatalog::toSearchableArray()`; settings in `config/scout.php` → `meilisearch.index-settings`.

| Field | From | Used for |
|---|---|---|
| `title_statement`, `joint_authors`, `summary`, `publisher`, `call_number`, `isbn`, `issn`, `accession_number`, `material_type` | `view_catalogs` | search (in this priority order), search in one field, highlighting |
| `subjects` | `view_catalogs.subjects` (MARC 600/610/650/651/653 `$a`, punctuation cleaned) | Subject filter, subject search |
| `authors` | `joint_authors` split into names | Author filter (+ "Find an author") |
| `year` | `view_catalogs.pub_year_num` (the year in `pub_year`'s text) | year range, histogram, Newest / Oldest |
| `has_fulltext`, `has_cover` | a PDF / a real (not generated) cover exists | "Full text available" filter |
| `views` | count of `views` rows | Most viewed |
| `title_sort` | title without a leading "The / A / An" | Title A–Z |
| `material_type_id`, `is_published`, `deleted_at`, `series_statement_id`, `catalog_id` | `view_catalogs` | type filter, published only, series links, a patron's saved list |

Settings: `faceting.maxValuesPerFacet = 500` (every publication year, long author lists),
`sortFacetValuesBy = count`, `pagination.maxTotalHits = 20000` (exact result counts).
After changing them: `php artisan scout:sync-index-settings`.

## Keeping it in sync

- **Automatic:** New Record, Edit Record, Batch Import, publish / hold, Material Type rename or
  merge, and adding or removing a cover or PDF (`Frontpage` / `Multimedia` model events).
- **Not automatic:** view counts. "Most viewed" catches up when that record is re-indexed, or on a
  full re-index (Admin › Settings › Tools › **Rebuild search index**, or `scout:import`).
- Admin › Settings › Tools › **Check search** compares the index with the database.

## When Meilisearch is down

- The search page says "Search is temporarily unavailable"; suggestions stay empty.
- Saving a catalog record can fail: Scout updates the index on save, synchronously
  (`config/scout.php` queue connection `sync`). Cover / PDF changes and the Edit Record refresh are
  guarded and only log the error. Start Meilisearch before cataloguing.
