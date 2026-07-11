# Meilisearch setup (Scout) — remaining steps

Status as of the Laravel 13 upgrade: **offline prep done**, waiting on network + Docker to finish.
The app still searches via `SCOUT_DRIVER=database` and works normally until the final flip.

## Already done (committed)
- `docker-compose.yml` — Meilisearch v1.12, bound to `127.0.0.1:7700`, master key from `.env`, persistent volume.
- `config/scout.php` — `meilisearch.index-settings` for the `view_catalogs` index
  (filterable: `material_type_id, is_published, series_statement_id, pub_year, deleted_at`; sortable: `pub_year`).
- `.env` (not committed) — `MEILISEARCH_HOST`, `MEILISEARCH_KEY`, `MEILI_MASTER_KEY` set; `SCOUT_DRIVER` still `database`.
- `meilisearch/meilisearch-php` bumped to `^1.16` in `composer.json` (lock update pending — see step 1).

## Remaining steps (need network + Docker Desktop running)

**1. Update the client lock** (Scout 11's engine needs meilisearch-php 1.x):
```bash
composer update meilisearch/meilisearch-php -W
```

**2. Start the Meilisearch container:**
```bash
docker compose up -d
curl http://127.0.0.1:7700/health        # -> {"status":"available"}
```

**3. Apply the model change** — add the filter/sort fields to `app/Models/ViewCatalog.php`
`toSearchableArray()` (NOT done yet, because adding numeric fields also changes what the
`database` driver LIKE-matches — apply it only at the flip):
```php
public function toSearchableArray()
{
    return [
        'id' => $this->id,
        'isbn' => $this->isbn,
        'issn' => $this->issn,
        'accession_number' => $this->accession_number,
        'title_statement' => $this->title_statement,
        'material_type' => $this->material_type,
        'pub_year' => $this->pub_year,
        // filter/sort fields used by ResultController's Scout filters:
        'material_type_id'    => $this->material_type_id,
        'is_published'        => (int) $this->is_published,
        'series_statement_id' => $this->series_statement_id,
        'deleted_at'          => $this->deleted_at,   // -> "deleted_at IS NULL" filter
    ];
}
```

**4. Flip the driver** in `.env`:
```
SCOUT_DRIVER=meilisearch
```
then `php artisan config:clear`.

**5. Push index settings + import the data** (~158 rows, instant):
```bash
php artisan scout:sync-index-settings
php artisan scout:import "App\Models\ViewCatalog"
```

**6. Test:**
```bash
php artisan tinker --execute="echo App\Models\ViewCatalog::search('a')->take(3)->get()->count();"
# then in the browser: OPAC search (grid + list), material-type filter, year range, pub_year sort.
```

## Notes
- Controllers need **no changes** — `ViewCatalog::search()` routes to whatever `SCOUT_DRIVER` is set.
- Rollback: set `SCOUT_DRIVER=database` + `php artisan config:clear` (instant; DB search always works).
- The pre-existing `/results` 500 (`Undefined array key "series_statement_id"`) is a controller
  param-handling bug, independent of the search engine — worth fixing separately.
- Reboot: `docker compose up -d` (or Docker Desktop auto-starts it via `restart: unless-stopped`).
