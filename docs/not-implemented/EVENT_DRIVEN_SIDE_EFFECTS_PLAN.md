# Event-Driven Side Effects — Plan

**Status:** Phase 0 done on 2026-10-19, and the search index has one owner (section 4.5). Phases 1-4
not started. The three live index bugs found while planning (section 3.1), and a record-page crash
found while testing them (section 3.7), were fixed separately on 2026-10-16. Regression tests:
`tests/Feature/SearchIndexSyncTest.php`, `tests/Feature/SearchIndexTest.php`.
**Last updated:** 2026-10-19. Re-verified against the code on that date; the 2026-10-16 counts are kept
where noted.

---

## 1. Goal
When a catalogue record changes, four things must follow: the OPAC search index (Meilisearch via
Scout), the AI index (the `rag_sync_queue` outbox read by ProjectY's `rag_sync_worker.py`), the
cached homepage lists, and, eventually, the sitemap. Today each write path calls these by hand, and
the paths disagree about which ones they call. The goal is for every write to announce **what
changed** once, and for each consumer to decide what it must do about it.

## 2. Where things stand

### 2.1 The manual calls
On 2026-10-16: 50 calls in 26 methods (55 if the News and Settings cache clears are counted). The
cache fixes of 3.1 added four, so 54 before phase 0.

| Where | Calls | Methods |
|---|---|---|
| `CatalogingController` | 35 (17 RAG, 8 home cache, 5 `searchable()`, 3 `unsearchable()`, 2 `makeAllSearchable()`) | 17 |
| `MaterialTypeController` | 5 | 3 |
| Commands: `catalog:import-corpus`, `catalog:rebuild-authors`, `catalog:rebuild-titles`, `catalog:rebuild-search-fields` | 8 | 4 |
| Models: `Frontpage`, `Multimedia` (Eloquent hooks that re-index on save/delete) | 2 | 2 |

**After 2026-10-19** every search-index update goes through `SearchIndex::refresh()` (section 4.5):
`CatalogingController` 15 RAG, 12 home cache, 9 refresh (16 methods); `MaterialTypeController` 3 home
cache, 1 refresh, no RAG; the commands and the two model hooks call `refresh()` too. Direct Scout calls
remain only in the two full rebuilds (the Settings re-index button and `catalog:rebuild-search-fields`).

### 2.2 What each consumer reads
This decides which changes concern which consumer (section 4.3).

| Consumer | Reads |
|---|---|
| Search index (`ViewCatalog::toSearchableArray`) | `view_catalogs` columns, **`has_fulltext`** (multimedia), `has_cover` (real covers only, not `gen-` placeholders), **`views`** (page-view count) |
| AI index (`rag_sync_worker.py`) | title, authors, publisher, `pub_year`, summary, general note, `is_published`, `deleted_at`, the PDF path. **Not** subjects, material type, cover or views. |
| Home caches (`homeCacheKeys()`) | news, latest materials, top downloaded/viewed/rated, shelves (titles + cover thumbnails), 10-minute TTL |
| Sitemap (`sitemap:generate`) | published records; scheduled daily at 03:30 |

### 2.3 How things run
- `QUEUE_CONNECTION=sync`, and Scout's own queue is `sync` too (`config/scout.php`), with
  `after_commit => true`. Scout's automatic model syncing is **off** for `ViewCatalog` since 2026-10-19;
  `SearchIndex` is the only writer (section 4.5).
- The RAG enqueue is only a database row; the embedding already happens in the Python worker. A
  single Meilisearch call takes milliseconds. Batch import used to index each record with its own call
  (about 11 ms a record); it now sends one call per 500 records (section 3.8).
- `jobs` and `failed_jobs` (with `uuid`) exist; `config/queue.php` has the `database` connection
  with `after_commit => false`.

## 3. Problems found

### 3.1 Live bugs (fixed 2026-10-16, before the refactor)
All three come from query-builder writes, which skip Eloquent model events:
1. **Removing a full text left `has_fulltext = true`** in the index (`destroyFullText` soft-deleted
   with `Multimedia::where()->update()`), so the OPAC's "Full text available" filter and its count
   were wrong. *Fix:* soft-delete through the model, so its hook re-indexes.
2. **Covers left `has_cover` wrong**: removing a cover (`destroyCoverPhoto`) and uploading a real
   cover over a generated placeholder (`uploadCoverPhoto` updates the `gen-` row in place) both
   bypassed the `Frontpage` hook. No search filter uses `has_cover` yet, so it was invisible.
   *Fix:* write through the model.
3. **Publishing or holding did not clear the home caches** (`publishToOpac`, `holdViewFromOpac`,
   `togglePublishHoldMaterial`): shelves could show a held record, or miss a newly published one,
   for up to 10 minutes. *Fix:* `clearHomeCache()` after the change commits.

### 3.2 Laravel's scheduler never runs
No Windows task and no Laragon (Cronical) job runs `php artisan schedule:run`. So the "daily"
sitemap never regenerates by itself (only the Settings button or a manual run do it), and there is
nowhere to run the nightly reconciles this plan relies on as a safety net (KI-03).
*Fixed 2026-10-19 (needs one run on the server):* `scripts\register-scheduler-task.ps1` registers the
`eLibrary-scheduler` logon task, which keeps `php artisan schedule:work` running; on Linux a cron entry
(`DEPLOYMENT_CHECKLIST.md`). The AI side's nightly reconcile is the `ProjectY-nightly-reconcile` task
(ProjectY `register-projecty-tasks.ps1`, 02:30).

### 3.3 "Most viewed" sorts on a stale count
The search page's *Most viewed* sort uses the `views` count stored in the index, which only changes
when a record happens to be re-indexed for another reason. Events cannot fix this: re-indexing on
every page view would be absurd. It needs a scheduled refresh (section 5, phase 0).
*Fixed 2026-10-19:* the nightly `search:reconcile` (03:45) re-sends every record viewed in the last
25 hours, so the sort is at most a day behind.

### 3.4 Needless AI re-indexing
The worker has no "skip if unchanged" check: every upsert re-reads the PDF and re-embeds every chunk
on CPU (about 52 records a minute). `MaterialTypeController::resync()` enqueues RAG upserts for a
rename or merge although the worker never reads material types; a type used by 2,000 records costs
about 40 minutes of re-embedding for unchanged text. Correct today: `catalog:rebuild-search-fields`
(no RAG; subjects are not read) and `catalog:sync-material-types` (no side effects: it only changes
`catalogs.material_type_id`, which nothing indexes).
*Fixed 2026-10-19:* `resync()` no longer queues RAG upserts (search and cache only). The worker's
"skip if unchanged" check is still open (ProjectY side).

### 3.5 Dead or wasteful code
- `POST /admin/hold-all-published` and `/admin/publish-all-onhold` are still registered with no UI
  (the bulk Actions menu replaced them), and each re-indexes the whole catalogue (`makeAllSearchable`).
- `togglePublishHoldMaterial` re-indexes a batch one record at a time (`->get()->each->searchable()`)
  instead of in chunks.
- `App\Jobs\ProcessSearch` is never dispatched.

*Fixed 2026-10-19:* the two routes, their methods and `ProcessSearch` are deleted;
`togglePublishHoldMaterial` re-indexes the batch in chunks through `SearchIndex`.

### 3.6 What events will not fix
Finding F18 of the database documentation (the `view_catalogs` projection is maintained only by
application code) is about `catalogs` -> `view_catalogs`, which the controllers write directly.
Events cover what sits downstream of `view_catalogs`. F18 needs its own step (section 5, phase 4).

### 3.7 Record page crash after cover, full-text and copy actions (fixed 2026-10-16)
Since a86c934 (2026-10-14, the Reviews tab) the record page needs `$reviews`, but only
`viewRecord()` passed it. Twelve other actions (upload or remove a cover or full text, add, edit or
remove a copy) rendered the same page with a partial set of variables: the change was saved, then
staff saw a 500 error. All of them now render through `renderRecordView()` with their result flags.
Checked after the fix: the live index matched the database (8,759 records with full text, 0 with a
real cover), so no re-index was needed.

### 3.8 Batch import spent half its time on MARC lookups (fixed 2026-10-19)
Measured on 300 realistic records (about 20 fields each) in the test database, per record: 329
queries, 296 of them `LIKE '%code%'` lookups of tag, subfield and indicator definitions in three small
tables that never change (about 47 ms of 80-100 ms); the Meilisearch call about 11 ms; a placeholder
cover about 10-15 ms. Queueing the search call (phase 3) would have saved only that 11 ms.
*Fix:* each lookup's answer is remembered for the rest of the request (`marcLookup()` in
`helper_joe.php`; 19,920 comparisons against the original functions, no difference), and the import's
search updates go out in chunks of 500. Result: 5 queries and about 4 ms a record (20-24 ms with
placeholder covers, now mostly the image), so a 2,000-record file takes seconds, not about 3 minutes,
and stays far inside a 30-second PHP time limit.

## 4. Design

### 4.1 One event
`App\Events\CatalogRecordsChanged(array $catalogIds, Change $change, string $reason)`, where
`App\Enums\Change` is: `Created`, `Updated`, `Published`, `Held`, `Deleted`, `FullTextChanged`,
`CoverChanged`, `TypeChanged`, `SearchFieldsRebuilt`. Single and bulk writes dispatch the same event
with one id or many. `$reason` keeps the existing outbox reasons (`created`, `published`,
`fulltext_removed`, ...) so the AI Sync Monitor reads the same.

### 4.2 Listeners and when they run
| Listener | Runs | Does |
|---|---|---|
| `RecordAiSync` | **synchronously, inside the write's transaction** | `RagSync::enqueueMany` (DELETE or UPSERT) |
| `SyncSearchIndex` | after commit (synchronous, see 4.4) | `SearchIndex::refresh()` with the affected ids (section 4.5) |
| `ForgetHomeCache` | after commit, synchronous (cheap) | `clearHomeCache()` |

**The outbox rule:** `RecordAiSync` must never be queued. The outbox's guarantee is that the change
and its `rag_sync_queue` row commit together or not at all; a queued listener would reintroduce the
dual-write it exists to prevent. So the event is dispatched *inside* `DB::transaction`, and the
other two listeners implement `ShouldHandleEventsAfterCommit`, so a rolled-back write never reaches
Meilisearch or the cache.

There is **no sitemap listener**: regenerating every URL on each edit is waste. The sitemap stays
scheduled (once the scheduler runs, section 3.2).

### 4.3 Which change concerns which consumer
| Change | Search index | AI index | Home cache |
|---|---|---|---|
| `Created`, `Updated`, `Published`, `Held` | yes | UPSERT (or DELETE for `Held`) | yes |
| `Deleted` | `unsearchable()` | DELETE | yes |
| `FullTextChanged` | yes (`has_fulltext`) | UPSERT | no |
| `CoverChanged` | yes (`has_cover`) | no | yes |
| `TypeChanged` | yes | **no** | yes |
| `SearchFieldsRebuilt` | yes | **no** | no |

### 4.4 Queue
*Re-assessed 2026-10-19:* with the import's lookups remembered and its search updates sent in chunks
(3.8), the one thing a queue would have taken off the request is a few milliseconds per change.
Recommendation: keep `SyncSearchIndex` synchronous (open decision 2) and drop phase 3 unless a
measured need appears. The design below stays valid if one does.

- `QUEUE_CONNECTION=database`; `after_commit => true` on the `database` connection; Scout's queue
  connection moves from `sync` to `database` (otherwise batch import keeps indexing inline).
- Worker: `php artisan queue:work --queue=scout,default --tries=3 --backoff=10 --max-time=3600`,
  started by a PowerShell wrapper that loops and restarts it, registered as a logon task. The
  wrapper matters: `ProjectY-sync-worker` exits when MySQL is not up at logon, and `queue:work`
  stops on database errors in the same way. A dead worker would silently recreate the stale index.
- Visibility: pending and failed job counts on the AI Sync Monitor, with a *Retry failed* button
  (`queue:retry all`).
- Mail is unaffected: the mail classes import `ShouldQueue` but do not implement it.

### 4.5 Who updates the search index (decided 2026-10-19)
**One explicit owner: `App\Services\SearchIndex`.** Scout's automatic model syncing is turned off
for `ViewCatalog` (`disableSearchSyncing()` in its `booted()`), and every change calls
`SearchIndex::refresh($catalogIds)`. In the event design the `SyncSearchIndex` listener is that caller.

Why not Scout's observer: most writes here use the query builder, which it never sees (10 explicit
calls already existed against 2 paths that relied on it); a search document is built from four tables
(`view_catalogs`, `multimedia`, `frontpages`, `views`) and the observer watches one; and an observer
cannot batch, so an import paid one call per record. Keeping both would index the same change twice.

How it behaves:
- **State-based:** the index is made to match the database when the update is sent (current records
  indexed, deleted ones removed), so a repeated or out-of-order call is harmless.
- **After commit:** sent when the current transaction commits, at once when there is none; a rolled-back
  change sends nothing it should not. Ids are de-duplicated until then.
- **Bulk:** between `SearchIndex::hold()` and `release()` updates are sent in chunks of 500 (batch
  import, corpus import).
- **Never throws:** a failed call is reported; the change stands, and `search:reconcile` (nightly)
  repairs the index. Anything still pending when the request or command ends is sent then.
- **Exception:** the two full rebuilds (the Settings re-index button, `catalog:rebuild-search-fields`)
  still call Scout's `makeAllSearchable()`.

## 5. Phases
0. **Prerequisites: done 2026-10-19**
   - The scheduler: `scripts\register-scheduler-task.ps1` (Windows) or cron (Linux). **To do once on
     the server:** run the script, and ProjectY's `register-projecty-tasks.ps1` again for the AI task.
   - Scheduled: `sitemap:generate` (03:30), `search:reconcile` (03:45: missing and deleted records, and
     the `views` count of records viewed in the last 25 hours), and on the AI side
     `rag_sync_worker.py --reconcile` (02:30, `ProjectY-nightly-reconcile`).
   - Deleted the two dead routes and methods and `ProcessSearch`.
   - Also done: the RAG fix of 3.4, the import speed-up of 3.8, and the search-index owner of 4.5, so
     phases 1-2 now only move the AI outbox and the home cache onto the event.
1. **Event and synchronous listeners** (no infrastructure change). Add the event, the enum and the
   three listeners, all synchronous for now. Convert the single-record paths: create, update,
   delete, publish, hold, full text, cover.
2. **Bulk and batch paths**: `bulkRecords`, batch import and delete, `togglePublishHoldMaterial`
   (chunked), `MaterialTypeController` (`TypeChanged`: no RAG), the three `catalog:rebuild-*`
   commands, `catalog:import-corpus`. The `Frontpage` / `Multimedia` hooks become event dispatches.
3. **Queued**: database driver, Scout queue, worker task and wrapper, pending/failed counts.
   *Not recommended now* (4.4): the work it would move off the request is a few milliseconds.
4. **Make it stick**
   - Guard test: fails if `app/Http` or `app/Console` calls `RagSync::`, `clearHomeCache()`,
     `->searchable()` or `->unsearchable()` directly (the full rebuilds of 4.5 excepted).
   - Optional, for F18: one `CatalogProjector` that builds the `view_catalogs` row from `field_data`,
     used by the controllers and the rebuild commands.
   - Deployment checklist and system documentation (an ADR).

Effort: about 1-1.5 days for phases 1, 2 and 4 (phase 0 done, phase 3 not recommended).

## 6. Tests
- Each converted path dispatches the right event and change (`Event::fake`).
- A transaction that rolls back after dispatching leaves no outbox row and no search update.
- Each listener honours the table in 4.3 (a recording Scout engine captures the documents sent).
- The guard test in phase 4.
- Regression tests for the three bugs in 3.1 (`tests/Feature/SearchIndexSyncTest.php`).
- `tests/Feature/SearchIndexTest.php` (2026-10-19): one owner, after-commit sending, rollback, chunks
  of 500, removal of deleted records, and the nightly reconcile. `BatchImportReportTest` checks that
  ten records ask the MARC tables no more than one does; `MaterialTypeMergeTest` that a merge queues
  no AI re-index.

## 7. Risks and gotchas
- **Search delay:** with a queued `SyncSearchIndex`, a record appears in OPAC search a second or two
  after staff publish it. Acceptable for a library; the record page itself is never delayed.
- **Worker down = stale index:** mitigated by the wrapper, the visible counts and the nightly reconcile.
- **Query-builder writes stay invisible to model events.** The event must be dispatched explicitly
  wherever the query builder writes; the guard test keeps new code from going back to direct calls.
- **Tests run inside a transaction** (RefreshDatabase); after-commit listeners still fire because
  Laravel runs them in test-transaction-aware mode.

## 8. Open decisions
1. Queue driver: database (recommended: no new service, tiny volume) or Laragon's Redis.
2. Keep `SyncSearchIndex` synchronous instead of queued, if instant OPAC visibility after publishing matters more.
   **Recommended 2026-10-19: synchronous** (see 4.4).
3. Whether to do the F18 projector (phase 4) now or later.
4. ~~Admin cover filters count generated `gen-` placeholders as covers, while the search index does
   not.~~ **Decided 2026-10-16:** in the admin a placeholder counts as a cover, tagged *Placeholder*
   in the lists and *Placeholder on* on the record page, where a per-record switch
   (`catalogs.cover_placeholder`) turns it on or off. The search index keeps `has_cover` for real
   covers only.
5. ~~Who updates the search index: Scout's model observer or an explicit call.~~ **Decided
   2026-10-19:** one explicit owner, `SearchIndex` (section 4.5).

## 9. References
- `app/Http/Controllers/CatalogingController.php`, `app/Http/Controllers/MaterialTypeController.php`
- `app/Services/RagSync.php`, `app/Services/SearchIndex.php`, `app/Models/ViewCatalog.php`, `app/Models/Frontpage.php`, `app/Models/Multimedia.php`
- `app/Console/Commands/ReconcileSearchIndex.php`, `scripts/register-scheduler-task.ps1`, `scripts/start-scheduler.ps1`
- `app/Http/helper.php` (`clearHomeCache`, `homeCacheKeys`, `saveView`)
- `config/scout.php`, `config/queue.php`, `app/Console/Kernel.php`
- ProjectY `rag/rag_sync_worker.py`; `RAG_CATALOG_SYNC_PLAN.md`; database documentation finding F18
