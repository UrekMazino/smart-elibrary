<?php

namespace App\Http\Controllers;
use Auth;
use App\Http\Requests\BatchUploadRequest;
use App\Http\Requests\MarcFileUploadRequest;
use App\Models\Catalog;
use App\Models\CatalogImport;
use App\Models\Download;
// use App\Models\CatalogImportProgress;
use App\Models\Frontpage;
use App\Models\Holdingcopy;
use App\Models\MarctagIndicator;
use App\Models\MarctagsLoc;
use App\Models\MarctagSubfield;
use App\Models\MarcTemplate;
use App\Models\Material;
use App\Models\Multimedia;
use App\Models\ViewCatalog;
use App\Services\AiSearchService;
use App\Services\MarcExportService;
use App\Services\MarcMaterialMapper;
use App\Models\MaterialTypeMap;
use App\Services\RagSync;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Scriptotek\Marc\Collection;
use Scriptotek\Marc\Record;
use ReflectionClass; 

use Illuminate\Http\Request;

class CatalogingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','admin']);
    }

    public function createRecord()
    {
        $marcTemplates = MarcTemplate::get();

        $materials = Material::forPicker();   // active types only (Utilities → Material Types)

        return view('admin.create-catalog-record', [
            'marcTemplates' => $marcTemplates,
            'materials'     => $materials,
            // Core bibliographic tags pre-loaded on a fresh form (LDR/008 are generated
            // client-side). See defaultCreateFields().
            'defaultFields' => $this->defaultCreateFields(),
        ]);
    }

    /**
     * The MARC tags whose subfields map to view_catalogs columns (the ones storeRecord
     * parses: 245->title, 100->joint_authors, 260->publisher/pub_year, 520->summary,
     * 500->general_note, 050->call_number, 020->isbn, 022->issn). Returned in the
     * source shape the create page's MarcEditor.loadFromSource() consumes, so the form
     * opens with these fields ready to fill instead of blank. LDR/008 are NOT here —
     * MarcEditor.ensureFixed() generates those (with LDR/06='a', books, by default).
     */
    private function defaultCreateFields(): array
    {
        $map = [
            '020' => ['020$a'],           // ISBN
            '022' => ['022$a'],           // ISSN
            '050' => ['050$a'],           // Call number
            '100' => ['100$a'],           // Author -> joint_authors
            '245' => ['245$a', '245$c'],  // Title (+ statement of responsibility)
            '260' => ['260$b', '260$c'],  // Publisher, date -> publisher / pub_year
            '500' => ['500$a'],           // General note
            '520' => ['520$a'],           // Summary
        ];

        // Indicator options per tag (first + second), so the indicator dropdowns are choosable.
        $tagOptions = app(\App\Services\MarcTemplateFields::class)->tagOptions(
            DB::table('marctags_loc')->whereIn('code', array_map('strval', array_keys($map)))->pluck('id')->all()
        );

        $fields = [];
        foreach ($map as $tagCode => $subCodes) {
            $tag = DB::table('marctags_loc')->where('code', $tagCode)->first();
            if (! $tag) {
                continue;
            }

            // The subfields actually pre-loaded as rows (with their values to fill).
            $subfields = [];
            foreach ($subCodes as $sc) {
                $s = DB::table('marctag_subfields_loc')->where('marctags_loc_id', $tag->id)->where('code', $sc)->first();
                if ($s) {
                    $subfields[] = ['id' => $s->id, 'code' => $s->code, 'description' => $s->description, 'default' => 'Enter value'];
                }
            }

            // Every subfield this tag allows — the picker for existing rows and Add Subfield.
            $allSubfields = DB::table('marctag_subfields_loc')->where('marctags_loc_id', $tag->id)
                ->orderBy('code')->get()
                ->map(fn ($s) => ['id' => $s->id, 'code' => $s->code, 'description' => $s->description])->all();

            $fields[] = [
                // Note: PHP casts numeric array keys to int, so compare the DB code string,
                // not $tagCode (which is int 245 here, and 245 === '245' is false).
                'tag'          => ['id' => $tag->id, 'code' => $tag->code, 'description' => $tag->description, 'isRequired' => $tag->code === '245' ? 1 : 0],
                'subfields'    => $subfields,
                'allSubfields' => $allSubfields,   // -> select options (shows "245$a - Title")
                'allIndicators' => $tagOptions[$tag->id]['indicators'] ?? [[], []],
                'editable'     => true,            // pre-loaded tags get add/remove subfield + delete tag
                'indicators'   => [['id' => 0, 'indicator' => 'None'], ['id' => 0, 'indicator' => 'None']],
            ];
        }

        return $fields;
    }

    /**
     * Convert a stored record's field_data (FINAL shape — what MarcEditor.serialize() emits
     * and updateRecord parses) into the SOURCE shape MarcEditor.loadFromSource() consumes, so
     * the edit page renders through the same shared editor the create page uses.
     *
     * The stored subfield 'value' becomes the pre-filled 'default'. Non-control tags are marked
     * editable (add/remove subfield + delete tag) and carry their full subfield picker; 245 is
     * flagged required (can't be deleted). LDR/008 stay non-editable — they render in the guided
     * Fixed Fields panel and are completed/normalised client-side by MarcEditor.ensureFixed().
     */
    private function recordSourceFields($fieldDataJson): array
    {
        $fieldData = is_array($fieldDataJson) ? $fieldDataJson : json_decode($fieldDataJson ?: '[]', true);
        if (! is_array($fieldData)) {
            return [];
        }

        // Indicator options per tag, loaded once for the whole record.
        $tagOptions = app(\App\Services\MarcTemplateFields::class)->tagOptions(
            array_map(fn ($item) => $item['marctag']['id'] ?? null, $fieldData)
        );

        $fields = [];
        foreach ($fieldData as $item) {
            if (! isset($item['marctag']['code'])) {
                continue;
            }
            $tag     = $item['marctag'];
            $code    = (string) $tag['code'];
            $isFixed = ($code === 'LDR' || $code === '008');

            // Subfield rows, carrying the stored value across as the pre-filled 'default'.
            $subfields = [];
            foreach ($item['subfield'] ?? [] as $s) {
                $subfields[] = [
                    'id'          => $s['id'] ?? null,
                    'code'        => $s['code'] ?? null,
                    'description' => $s['description'] ?? null,
                    'default'     => $s['value'] ?? '',
                    'align'       => ($s['align'] ?? null) === 'justify' ? 'justify' : null,   // Justify button
                ];
            }

            // Every subfield this tag allows — powers the row picker + Add Subfield. Skipped for
            // the fixed control fields (edited through the guided panel, not the generic card UI).
            $allSubfields = [];
            if (! $isFixed && isset($tag['id'])) {
                $allSubfields = DB::table('marctag_subfields_loc')->where('marctags_loc_id', $tag['id'])
                    ->orderBy('code')->get()
                    ->map(fn ($s) => ['id' => $s->id, 'code' => $s->code, 'description' => $s->description])->all();
            }

            $indicators = [];
            foreach ($item['indicator'] ?? [] as $ind) {
                $indicators[] = ['id' => $ind['id'] ?? 0, 'indicator' => $ind['indicator'] ?? 'None'];
            }
            if (count($indicators) < 2) {
                $indicators = [['id' => 0, 'indicator' => 'None'], ['id' => 0, 'indicator' => 'None']];
            }

            $fields[] = [
                'tag'          => [
                    'id'          => $tag['id'] ?? null,
                    'code'        => $code,
                    'description' => $tag['description'] ?? $code,
                    'isRequired'  => $code === '245' ? 1 : 0,   // 245 can't be deleted
                ],
                'subfields'    => $subfields,
                'allSubfields' => $allSubfields,
                'allIndicators' => $isFixed ? [[], []] : ($tagOptions[$tag['id'] ?? 0]['indicators'] ?? [[], []]),
                'editable'     => ! $isFixed,   // LDR/008 -> guided fixed-field panel
                'indicators'   => $indicators,
            ];
        }

        return $fields;
    }

    public function viewRecord(Request $request)
    {
        // POST entry used by the record/on-hold/batch lists (title comes from the clicked row).
        return $this->renderRecordView($request->catalog_id, $request->title);
    }

    public function showRecord($id)
    {
        // GET entry (e.g. the redirect right after creating a record). No POSTed title here,
        // so derive it from the catalog's ViewCatalog row (its 245 title_statement).
        $title = optional(ViewCatalog::where('catalog_id', $id)->first())->title_statement;
        return $this->renderRecordView($id, $title);
    }

    private function renderRecordView($catalogId, $title)
    {
        $record = Catalog::select('catalogs.*')
            ->where('catalogs.id', $catalogId)
            ->first();

        $totalCopies = Holdingcopy::where('catalog_id', $catalogId)->count();

        $coverPage = Frontpage::where('catalog_id', $catalogId)->whereNull('deleted_at')->first();

        $fullText = Multimedia::where('catalog_id', $catalogId)->whereNull('deleted_at')->first();

        $citations = buildCitations(ViewCatalog::where('catalog_id', $catalogId)->first());

        // Patron reviews for the Reviews tab (admin.partials.record-reviews), newest first.
        $reviews = DB::table('ratings as r')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->where('r.catalog_id', $catalogId)
            ->orderByDesc('r.created_at')->orderByDesc('r.id')
            ->get(['r.id', 'r.rating', 'r.remarks', 'r.created_at', 'u.name', 'u.email', 'u.is_banned', 'u.is_blocked']);

        return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $title, 'totalCopies' => $totalCopies, 'citations' => $citations, 'reviews' => $reviews]);
    }

    /**
     * Export one record as MARC21 binary (.mrc) or MARCXML (.xml). Indicators are normalised at
     * export time by MarcExportService (blank -> space) — see [[elibrary-marc-indicators]].
     */
    public function exportRecord($id, $format, MarcExportService $marc)
    {
        // File_MARC (the underlying lib) emits PHP 8.x deprecation notices; keep them out of the
        // binary/XML body so the downloaded file isn't corrupted.
        error_reporting(error_reporting() & ~E_DEPRECATED);

        $catalog = Catalog::whereNull('deleted_at')->findOrFail($id);
        $record  = $marc->buildRecord($catalog);

        return $format === 'xml'
            ? response($marc->toXml([$record]), 200, [
                'Content-Type'        => 'application/xml; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="record-' . $catalog->id . '.xml"',
            ])
            : response($marc->toBinary([$record]), 200, [
                'Content-Type'        => 'application/marc',
                'Content-Disposition' => 'attachment; filename="record-' . $catalog->id . '.mrc"',
            ]);
    }

    /**
     * Batch-export the catalog as one .mrc or .xml file, streamed + chunked so a large catalog
     * doesn't exhaust memory. Optional ?status=published|onhold scopes it to one list.
     */
    public function exportRecords(Request $request, $format, MarcExportService $marc)
    {
        $status = $request->query('status');
        $build  = fn () => Catalog::whereNull('deleted_at')
            ->when($status === 'published', fn ($q) => $q->where('is_published', 1))
            ->when($status === 'onhold', fn ($q) => $q->where('is_published', 0))
            ->orderBy('id');

        $name = 'catalog' . ($status ? '-' . $status : '') . '-' . now()->format('Ymd-His');

        return $this->streamMarcExport($build, $format, $name, $marc);
    }

    /** Stream the records from $build() (a query factory) as MARC21 binary or MARCXML, 200 at a time. */
    private function streamMarcExport(\Closure $build, string $format, string $name, MarcExportService $marc)
    {
        error_reporting(error_reporting() & ~E_DEPRECATED);

        if ($format === 'xml') {
            return response()->stream(function () use ($build, $marc) {
                $w = new \XMLWriter();
                $w->openUri('php://output');
                $w->setIndent(true);
                $marc->xmlCollectionStart($w);
                $build()->chunk(200, function ($chunk) use ($w, $marc) {
                    foreach ($chunk as $catalog) {
                        $marc->writeRecordXml($w, $marc->buildRecord($catalog));
                    }
                    $w->flush();
                });
                $marc->xmlCollectionEnd($w);
                $w->flush();
            }, 200, [
                'Content-Type'        => 'application/xml; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $name . '.xml"',
            ]);
        }

        return response()->stream(function () use ($build, $marc) {
            $build()->chunk(200, function ($chunk) use ($marc) {
                foreach ($chunk as $catalog) {
                    echo $marc->toBinary([$marc->buildRecord($catalog)]);
                }
                flush();
            });
        }, 200, [
            'Content-Type'        => 'application/marc',
            'Content-Disposition' => 'attachment; filename="' . $name . '.mrc"',
        ]);
    }

    /**
     * Manually queue one record for RAG re-indexing (the human override for drift — e.g. after
     * fixing OCR). Just enqueues; the worker gates on is_published as usual.
     */
    public function reindexRecord($id)
    {
        RagSync::enqueue((int) $id, RagSync::UPSERT, 'manual');
        return back()->with('rag_reindexed', 1);
    }

    /** Bulk manual re-index — all non-deleted records, optionally scoped by ?status=published|onhold. */
    public function reindexRecords(Request $request)
    {
        $status = $request->input('status', $request->query('status'));
        $ids = Catalog::whereNull('deleted_at')
            ->when($status === 'published', fn ($q) => $q->where('is_published', 1))
            ->when($status === 'onhold', fn ($q) => $q->where('is_published', 0))
            ->pluck('id');

        RagSync::enqueueMany($ids, RagSync::UPSERT, 'manual');

        return response()->json(['success' => true, 'queued' => $ids->count()]);
    }

    /**
     * AI Sync Monitor — an audit trail over the rag_sync_queue outbox: which catalogs changed
     * (added / PDF changed / edited / removed / (un)published) and whether the AI index is in sync,
     * so an admin can decide what to re-sync. See RAG_CATALOG_SYNC_PLAN.md.
     */
    public function aiSyncMonitor()
    {
        $counts = [
            'pending' => DB::table('rag_sync_queue')->whereNull('processed_at')->where('attempts', '<', RagSync::MAX_ATTEMPTS)->count(),
            'failed'  => DB::table('rag_sync_queue')->whereNull('processed_at')->where('attempts', '>=', RagSync::MAX_ATTEMPTS)->count(),
            'synced'  => DB::table('rag_sync_queue')->whereNotNull('processed_at')->count(),
        ];

        return view('admin.rag-sync-monitor', compact('counts'));
    }

    /**
     * "Run sync now" — ask the RAG gateway to apply pending queue items, so an admin can index
     * from the app instead of running the worker by hand. Bounded per call (see drainSyncQueue),
     * so the UI repeats while `remaining > 0`.
     */
    public function runSyncNow(Request $request, AiSearchService $ai)
    {
        $res = $ai->drainSyncQueue((int) $request->input('limit', 25));

        return response()->json($res, $res['ok'] ? 200 : 503);
    }

    /** Server-side data for the monitor table (DataTables shape). */
    public function aiSyncMonitorJson(Request $request)
    {
        $draw   = (int) $request->get('draw');
        $start  = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 25);
        $search = trim((string) $request->input('search.value', ''));
        $change = $request->get('change');       // reason group filter
        $status = $request->get('sync_status');  // pending|failed|synced|removed

        $q = DB::table('rag_sync_queue as q')
            ->leftJoin('view_catalogs as vc', 'vc.catalog_id', '=', 'q.catalog_id')
            ->select('q.id', 'q.catalog_id', 'q.action', 'q.reason', 'q.attempts', 'q.error',
                'q.created_at', 'q.processed_at', 'vc.title_statement');

        // Change-type filter (grouped for convenience).
        if ($change === 'pdf') {
            $q->whereIn('q.reason', ['fulltext_added', 'fulltext_updated', 'fulltext_removed']);
        } elseif ($change === 'added') {
            $q->whereIn('q.reason', ['created', 'imported']);
        } elseif ($change) {
            $q->where('q.reason', $change);
        }

        // Sync-status filter (derived from processed_at + attempts + action).
        if ($status === 'pending') {
            $q->whereNull('q.processed_at')->where('q.attempts', '<', RagSync::MAX_ATTEMPTS);
        } elseif ($status === 'failed') {
            $q->whereNull('q.processed_at')->where('q.attempts', '>=', RagSync::MAX_ATTEMPTS);
        } elseif ($status === 'synced') {
            $q->whereNotNull('q.processed_at')->where('q.action', RagSync::UPSERT);
        } elseif ($status === 'removed') {
            $q->whereNotNull('q.processed_at')->where('q.action', RagSync::DELETE);
        }

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('vc.title_statement', 'like', "%{$search}%")
                    ->orWhere('q.catalog_id', 'like', "%{$search}%");
            });
        }

        $total = (clone $q)->count();

        $rows = $q->orderByDesc('q.created_at')->orderByDesc('q.id')
            ->skip($start)->take($length)->get();

        $data = [];
        foreach ($rows as $r) {
            $changeLbl = RagSync::changeLabel($r->reason);
            $stat      = RagSync::rowStatusLabel($r->processed_at, (int) $r->attempts, $r->action);
            $errIcon = $r->error
                ? ' <i data-feather="alert-triangle" class="text-danger icon-sm" title="' . htmlspecialchars($r->error, ENT_QUOTES, 'UTF-8') . '"></i>'
                : '';
            $data[] = [
                'catalog_id' => $r->catalog_id,
                'title'      => htmlspecialchars($r->title_statement ?? '(deleted / unknown)', ENT_QUOTES, 'UTF-8'),
                'change'     => '<span class="badge ' . $changeLbl['class'] . '">' . $changeLbl['label'] . '</span>',
                'when'       => $r->created_at,
                'synced'     => $r->processed_at ?? '<span class="text-muted">—</span>',
                'status'     => '<span class="badge ' . $stat['class'] . '">' . $stat['label'] . '</span>' . $errIcon,
                'resync'     => $r->catalog_id,
            ];
        }

        return response()->json([
            'draw'                 => $draw,
            'iTotalRecords'        => $total,
            'iTotalDisplayRecords' => $total,
            'aaData'               => $data,
        ]);
    }

    public function viewBatch(Request $request)
    {
        $batch = CatalogImport::select('*', 'catalog_imports.id as batch_id')
        ->where('catalog_imports.id', $request->batch_id)
        ->where('catalog_imports.deleted_at', null)
        ->first();

       return view('admin.batch-view', ['batch' => $batch]);
    }

    /** A batch's records: the same filters / table as the Record List, scoped to the batch. */
    public function viewBatchRecords($id)
    {
        $batch = CatalogImport::whereNull('deleted_at')->findOrFail($id);

        return view('admin.batch-records', [
            'id'      => $batch->id,
            'batch'   => $batch,
            'filters' => $this->catalogListFilterOptions(null, $batch->id),
        ]);
    }

    public function viewBatchRecordsJson(Request $request, $id)
    {
        return $this->catalogListJson($request, null, (int) $id);
    }

    public function storeBatch(BatchUploadRequest $request, MarcMaterialMapper $mapper)
    {
        $records = Collection::fromFile($request->file('batch_file'));

        // Optional per-record material mapping: values of one MARC field (e.g. 942$c) ->
        // Material Type. Unmapped values / records without the field use the default type.
        [$mapField, $materialMap] = $this->batchMaterialMap($request);
        $materialCounts = [];   // material name => records imported under it
        
        $user = Auth::user();

        $files = [];
        $fields = [];
        $leaders = [];
        $r = [];

        foreach($records as $record){
            $a = $record->record;
            $files[] = $a;     
            $r[] = $record;
        }
        
        for($b = 0; $b < count($files); $b++) {
            $fields[] = $files[$b]->getFields();
            $leaders[] = $files[$b]->getLeader();
        }
        
        $formattedRecords = [];

        $totalRecords = count($fields);

        $catalogImport = CatalogImport::create([
            'no_of_records' => $totalRecords,
            'subject' => trim((string) $request->subject),
            'is_aanr' => isset($request->is_aanr) ? 1 : 0,
            'comment' => $request->comment,
            'material_type_id' => $request->material_type_id,   // the default type
            'material_map' => $mapField ? ['field' => $mapField, 'map' => $materialMap] : null,
            'status' => 'imported',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // $task = new CatalogImportProgress();
        // $task->total_records = $totalRecords;
        // $task->save();

        foreach($fields as $index => $f) {
            $marcTags = [];

            foreach($f as $c) {
                $marcTags[] = $c->getTag();
            }

            try{
                // LDR - LEADER
                $fa = []; 
    
                $subfieldArr = array();
                $defaultArr = array();
                $indicatorArr = array();
                
                $indicatorArr[0] = [
                    "id" => 0,
                    "indicator" => "N"
                ];
                $indicatorArr[1] = [
                    "id" => 0,
                    "indicator" => "N"
                ];
    
                $l = str_split($leaders[$index]);
    
                $subfieldArr[0] = [
                    "sub-index" => 1,
                    "id" => 2617,
                    "code" => "LDR$00-04",
                    "description" => "Record length",
                    "value" => $l[0].$l[1].$l[2].$l[3].$l[4]
                ];
                
                $subfieldArr[1] = [
                    "sub-index" => 2,
                    "id" => 2618,
                    "code" => "LDR$05",
                    "description" => "Record status",
                    "value" => $l[5]
                ];
    
                $subfieldArr[2] = [
                    "sub-index" => 3,
                    "id" => 2619,
                    "code" => "LDR$06",
                    "description" => "Type of record",
                    "value" => $l[6]
                ];
    
                $subfieldArr[3] = [
                    "sub-index" => 4,
                    "id" => 2620,
                    "code" => "LDR$07",
                    "description" => "Bibliographic level",
                    "value" => $l[7]
                ];
    
                $subfieldArr[4] = [
                    "sub-index" => 5,
                    "id" => 2621,
                    "code" => "LDR$08",
                    "description" => "Type of control",
                    "value" => $l[8]
                ];
    
                $subfieldArr[5] = [
                    "sub-index" => 6,
                    "id" => 2622,
                    "code" => "LDR$09",
                    "description" => "Character coding scheme",
                    "value" => $l[9]
                ];
    
                $subfieldArr[6] = [
                    "sub-index" => 7,
                    "id" => 2623,
                    "code" => "LDR$10",
                    "description" => "Indicator count",
                    "value" => $l[10]
                ];
    
                $subfieldArr[7] = [
                    "sub-index" => 8,
                    "id" => 2624,
                    "code" => "LDR$10",
                    "description" => "Subfield code count",
                    "value" => $l[11]
                ];
    
                $subfieldArr[8] = [
                    "sub-index" => 9,
                    "id" => 2625,
                    "code" => "LDR$12-16",
                    "description" => "Base address of data",
                    "value" => $l[12].$l[13].$l[14].$l[15].$l[16]
                ];
            
    
                $subfieldArr[9] = [
                    "sub-index" => 10,
                    "id" => 2626,
                    "code" => "LDR$17",
                    "description" => "Encoding level",
                    "value" => $l[17]
                ];
    
                $subfieldArr[10] = [
                    "sub-index" => 11,
                    "id" => 2627,
                    "code" => "LDR$18",
                    "description" => "Descriptive cataloging form",
                    "value" => $l[18]
                ];
    
                $subfieldArr[11] = [
                    "sub-index" => 12,
                    "id" => 2628,
                    "code" => "LDR$19",
                    "description" => "Multipart resource record level",
                    "value" => $l[19]
                ];
    
                $subfieldArr[12] = [
                    "sub-index" => 13,
                    "id" => 2629,
                    "code" => "LDR$20",
                    "description" => "Length of the length-of-field portion",
                    "value" => $l[20]
                ];
    
                $subfieldArr[13] = [
                    "sub-index" => 14,
                    "id" => 2630,
                    "code" => "LDR$21",
                    "description" => "Length of the starting-character-position portion",
                    "value" => $l[21]
                ];
    
                $subfieldArr[14] = [
                    "sub-index" => 15,
                    "id" => 2631,
                    "code" => "LDR$22",
                    "description" => "Length of the implementation-defined portion",
                    "value" => $l[22]
                ];
    
                $subfieldArr[15] = [
                    "sub-index" => 16,
                    "id" => 2632,
                    "code" => "LDR$23",
                    "description" => "Undefined",
                    "value" => $l[23]
                ];
                
                $fa[] = array(
                    'marctag' => [
                        "index" => (int)$index+1, 
                        "id" => "2501", 
                        "code" => "LDR",
                        "description" => "Leader",
                     ],
                    'subfield' => $subfieldArr,
                    'indicator' => $indicatorArr,
    
                );
                // End of Leader

                $repeatedMarctags = [];

                for($w = 0; $w< count($marcTags); $w++) {
                    
                    // $fa = []; 

                    $subfieldArr = array();
    
                    $defaultArr = array();
    
                    $indicatorArr = array();
    
                    $required = 0;
    
                    foreach ($r[$index]->query($marcTags[$w]) as $field) {
                        
                        $marcTagid = getTagDetails(0,$marcTags[$w]);
                        
                        $marcTagCode = $marcTags[$w];

                        $marcTagDescription = getTagDetails(0,$marcTags[$w], 'description');

                        if(method_exists($field->field, 'getSubfields')) {

                            $indicatorArr1[$w] = $field->getIndicator(1);
                            $indicatorArr2[$w] = $field->getIndicator(2);
                      
                            foreach($field->getSubfields() as $i => $y) {
                                $sfCode = $marcTags[$w]."$".$y->getCode();
                                $subfieldArr[] = [
                                    "sub-index" => (int)$i+1,
                                    "id" => getSubfieldId($sfCode),
                                    "code" => $sfCode,
                                    "description" => getSubfield($sfCode, 'description'),
                                    "value" => $y->getData()
                                ];
                                $indicatorArr[0] = [
                                    "id" => getFirstIndicatorId($marcTagid, $indicatorArr1[$w]),
                                    "indicator" => $indicatorArr1[$w]
                                ];
                                $indicatorArr[1] = [
                                    "id" => getSecondIndicatorId($marcTagid, $indicatorArr2[$w]),
                                    "indicator" => $indicatorArr2[$w]
                                ];
                            }
                        } else {
                            $string = $field->getData();
                            $c = str_split($string);
                            // getSubfield('001$#', 'description')
                            if($marcTags[$w] == '001'){
                                $subfieldArr[0] = [
                                    "sub-index" => 1,
                                    "id" => 2613,
                                    "code" => "001$#",
                                    "description" => "Control Number",
                                    "value" => $string
                                ];
                            }
    
                            if($marcTags[$w] == '003'){
                                $subfieldArr[0] = [
                                    "sub-index" => 1,
                                    "id" => 2614,
                                    "code" => "003$#",
                                    "description" => "Control Number Identifier",
                                    "value" => $string
                                ];
                            }
    
                            if($marcTags[$w] == '005'){
                                $subfieldArr[0] = [
                                    "sub-index" => 1,
                                    "id" => 2612,
                                    "code" => "005$#",
                                    "description" => "Date and Time",
                                    "value" => $string
                                ];
                            }
    
                            if($marcTags[$w] == '006'){
                                $subfieldArr[0] = [
                                    "sub-index" => 1,
                                    "id" => 2615,
                                    "code" => "006$#",
                                    "description" => "Fixed-Length Data",
                                    "value" => $string
                                ];
                            }
    
                            if($marcTags[$w] == '007'){
                                $subfieldArr[0] = [
                                    "sub-index" => 1,
                                    "id" => 2616,
                                    "code" => "007$#",
                                    "description" => "Physical Description Fixed Field",
                                    "value" => $string
                                ];
                            }
    
    
                            if($marcTags[$w] == '008') {
                            
                                $subfieldArr[0] = [
                                    "sub-index" => 1,
                                    "id" => 2603,
                                    "code" => "008$00-05",
                                    "description" => "Date entered on file",
                                    "value" => $c[0].$c[1].$c[2].$c[3].$c[4].$c[5]
                                ];
    
                                $subfieldArr[1] = [
                                    "sub-index" => 2,
                                    "id" => 2604,
                                    "code" => "008$06",
                                    "description" => "Type of date/Publication status",
                                    "value" => $c[6]
                                ];
    
                                $subfieldArr[2] = [
                                    "sub-index" => 3,
                                    "id" => 2605,
                                    "code" => "008$07-10",
                                    "description" => "Date 1",
                                    "value" => $c[7].$c[8].$c[9].$c[10]
                                ];
    
                                $subfieldArr[3] = [
                                    "sub-index" => 4,
                                    "id" => 2606,
                                    "code" => "008$11-14",
                                    "description" => "Date2",
                                    "value" => $c[11].$c[12].$c[13].$c[14]
                                ];
    
                                $subfieldArr[4] = [
                                    "sub-index" => 5,
                                    "id" => 2607,
                                    "code" => "008$15-17",
                                    "description" => "Place of publication, production, or execution",
                                    "value" => $c[15].$c[16].$c[17]
                                ];
                                
                                $subfieldArr[5] = [
                                    "sub-index" => 6,
                                    "id" => 2608,
                                    "code" => "008$18-34",
                                    "description" => "Material specific coded elements",
                                    "value" => $c[18].$c[19].$c[20].$c[21].$c[22].$c[23].$c[24].$c[25].$c[26].$c[27].$c[28].$c[29].$c[30].$c[31].$c[32].$c[33].$c[34]
                                ];
                                
                                    if(isset($c[35])) {
                                        $subfieldArr[6] = [
                                            "sub-index" => 7,
                                            "id" => 2609,
                                            "code" => "008$35-37",
                                            "description" => "Language",
                                            "value" => $c[35].$c[36].$c[37]
                                        ];
                                        
                                        $subfieldArr[7] = [
                                            "sub-index" => 8,
                                            "id" => 2610,
                                            "code" => "008$38",
                                            "description" => "Modified record",
                                            "value" => $c[38]
                                        ];
    
                                        $subfieldArr[8] = [
                                            "sub-index" => 9,
                                            "id" => 2611,
                                            "code" => "008$39",
                                            "description" => "Cataloging source",
                                            "value" => $c[39]
                                        ];
                                    }
                                
                            } 
                            $indicatorArr[0] = [
                                "id" => 0,
                                "indicator" => "N"
                            ];
                            $indicatorArr[1] = [
                                "id" => 0,
                                "indicator" => "N"
                            ];
    
                           
                        }
                    }
                    
                    if(!in_array($marcTagid, $repeatedMarctags)) {
                        $fa[] = array(
                            'marctag' => [
                                "index" => $w+1, 
                                "id" => $marcTagid, 
                                "code" => $marcTagCode,
                                "description" => $marcTagDescription
                            ],
                            'subfield' => $subfieldArr,
                            'indicator' => $indicatorArr,
            
                        );
                        $repeatedMarctags[] = $marcTagid;
                    }
                   
                    
                }
                $formattedRecords[] = $fa;
                $fieldDataString = json_encode($fa);    
        



                $titleTagCode = "245"; // Marctag for Title Statemet 
                $titleCode =  "245\$a"; // Subfield code for Title
                
                $generalNoteTagCode = "500";
                $generalNoteCode =  "500\$a";
        
                $summaryTagCode = "520";
                $summaryCode = "520\$a";
        
                $publisherTagCode = "260";
                $publishersCode = "260\$b";
                $publicationDatesCode = "260\$c";
        
                $isbnTagCode = "020";
                $isbnCode = "020\$a";

                $issnTagCode = "022";
                $issnCode = "022\$a";

                $callNumberTagCode = "050";
                $callNumberCode =  "050\$a";

                $recordTypeTagCode = "LDR";
                $recordTypeCode = "LDR\$06";
        
                    $fieldData = json_decode($fieldDataString, true);
                    // Getting the title 
                    $matchingTitles = collect($fieldData)->filter(function ($item) use ($titleTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $titleTagCode;
                    })->flatMap(function ($item) use ($titleCode) {
                        $titles = []; 
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($titleCode, &$titles) {
                                if ($subfield['code'] == $titleCode) {
                                    $titles['title'] = $subfield['value'];
                                }
                            });
                        }            
                        return $titles;
                    })->toArray();

                    // Getting the recordtype 
                    $matchingRecordType = collect($fieldData)->filter(function ($item) use ($recordTypeTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $recordTypeTagCode;
                    })->flatMap(function ($item) use ($recordTypeCode) {
                        $recordType = []; 
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($recordTypeCode, &$recordType) {
                                if ($subfield['code'] == $recordTypeCode) {
                                    $recordType['recordType'] = $subfield['value'];
                                }
                            });
                        }            
                        return $recordType;
                    })->toArray();
        
                    // Getting the ISBN 
                    $matchingISBN = collect($fieldData)->filter(function ($item) use ($isbnTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $isbnTagCode;
                    })->flatMap(function ($item) use ($isbnCode) {
                        $isbn = []; 
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($isbnCode, &$isbn) {
                                if ($subfield['code'] == $isbnCode) {
                                    $isbn['isbn'] = $subfield['value'];
                                }
                            });
                        }            
                        return $isbn;
                    })->toArray();

                    // Getting the ISSN 
                    $matchingISSN = collect($fieldData)->filter(function ($item) use ($issnTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $issnTagCode;
                    })->flatMap(function ($item) use ($issnCode) {
                        $issn = []; 
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($issnCode, &$issn) {
                                if ($subfield['code'] == $issnCode) {
                                    $issn['issn'] = $subfield['value'];
                                }
                            });
                        }            
                        return $issn;
                    })->toArray();
        
        
                    // Getting the general note
                    $matchingGeneralNote = collect($fieldData)->filter(function ($item) use ($generalNoteTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $generalNoteTagCode;
                    })->flatMap(function ($item) use ($generalNoteCode) {
                        $generalNote = [];
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($generalNoteCode, &$generalNote) {
                                if ($subfield['code'] == $generalNoteCode) {
                                    $generalNote['general_note'] = $subfield['value'];
                                }
                            });
                        }            
                        return $generalNote;
                    })->toArray();
                    

                    // Getting the call number
                    $matchingCallNumber = collect($fieldData)->filter(function ($item) use ($callNumberTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $callNumberTagCode;
                    })->flatMap(function ($item) use ($callNumberCode) {
                        $callNumber = [];
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($callNumberCode, &$callNumber) {
                                if ($subfield['code'] == $callNumberCode) {
                                    $callNumber['call_number'] = $subfield['value'];
                                }
                            });
                        }            
                        return $callNumber;
                    })->toArray();
                    
        
                    // Getting the Summary
                    $matchingSummary = collect($fieldData)->filter(function ($item) use ($summaryTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $summaryTagCode;
                    })->flatMap(function ($item) use ($summaryCode) {
                        $summary = [];
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($summaryCode, &$summary) {
                                if ($subfield['code'] == $summaryCode) {
                                    $summary['summary'] = $subfield['value'];
                                }
                            });
                        }            
                        return $summary;
                    })->toArray();
        
                    // Getting the Publisher
                    $matchingPublisher = collect($fieldData)->filter(function ($item) use ($publisherTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $publisherTagCode;
                    })->flatMap(function ($item) use ($publishersCode) {
                        $publisher = [];
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($publishersCode, &$publisher) {
                                if ($subfield['code'] == $publishersCode) {
                                    $publisher['publisher'] = $subfield['value'];
                                }
                            });
                        }            
                        return $publisher;
                    })->toArray();
        
        
                    // Getting the Date of Publication
                    $matchingDateOfPublication = collect($fieldData)->filter(function ($item) use ($publisherTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $publisherTagCode;
                    })->flatMap(function ($item) use ($publicationDatesCode) {
                        $dateOfPublication = [];
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($publicationDatesCode, &$dateOfPublication) {
                                if ($subfield['code'] == $publicationDatesCode) {
                                    $dateOfPublication['date_of_pub'] = $subfield['value'];
                                }
                            });
                        }            
                        return $dateOfPublication;
                    })->toArray();
        

                    // One material for the catalog row AND the OPAC row (see resolveMaterial()):
                    // the mapped type for this record's field value, else the batch default.
                    $mappedId = $mapper->materialFor($files[$index], $mapField, $materialMap);
                    $material = $this->resolveMaterial($mappedId ?? $request->material_type_id, $matchingRecordType['recordType'] ?? null);
                    $materialCounts[$material->name] = ($materialCounts[$material->name] ?? 0) + 1;

                    $material_type = $material->name;
                    $material_type_id = $material->id;

                    $viewCatMattype = $material;


                    // $task->progress = $index+1;
                    // $task->save();

                $catalogRow = [
                    'material_type_id' => $material_type_id,
                    'field_data' => $fieldDataString,
                    'catalog_import_id' => $catalogImport->id,
                    'is_aanr' => $request->is_aanr ? 1 : 0,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ];

                $opacRow = [
                    'isbn' => isset($matchingISBN['isbn']) ? $matchingISBN['isbn'] : NULL,
                    'issn' => isset($matchingISSN['issn']) ? $matchingISSN['issn'] : NULL,
                    'material_type' => $viewCatMattype->name,
                    'material_type_id'=> $viewCatMattype->id,
                    'catalog_import_id' => $catalogImport->id,
                    'title_statement' => marcTitle($fieldData) ?? ($matchingTitles['title'] ?? NULL),   // full 245 ($a $b $n $p $c ...), not just $a
                    'summary' => isset($matchingSummary['summary']) ? $matchingSummary['summary'] : NULL,
                    'general_note' => isset($matchingGeneralNote['general_note']) ? $matchingGeneralNote['general_note'] : NULL,
                    'publisher' => isset($matchingPublisher['publisher']) ? $matchingPublisher['publisher'] : NULL,
                    'pub_year' => isset($matchingDateOfPublication['date_of_pub']) ? $matchingDateOfPublication['date_of_pub'] : NULL,
                    'joint_authors' => marcAuthors($fieldData),   // 100 + 110, else 700 + 710; " / "-separated
                    'subjects' => marcSubjects($fieldData),        // 600/610/650/651/653 $a (Subject filter)
                    'call_number' => isset($matchingCallNumber['call_number']) ? $matchingCallNumber['call_number'] : NULL,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ];

                // The record and its OPAC row are written together (one transaction per record,
                // so the import progress stays visible while the batch runs).
                $catalog = $this->createWithOpacRow($catalogRow, $opacRow);
                ensurePlaceholderCover($catalog->id);

                $formattedRecords = [];
            } catch (Exception $e) {
                dump("Error: $e->getMessage()");
            }
          
        }
        // CatalogImportProgress::truncate();

        clearHomeCache();

        // Queue the imported batch for RAG sync (records start on-hold; the worker gates on
        // is_published, so nothing is indexed until the batch is published).
        RagSync::enqueueMany(
            Catalog::where('catalog_import_id', $catalogImport->id)->pluck('id'),
            RagSync::UPSERT,
            'imported'
        );

        // Post/Redirect/Get, with what was imported under which type.
        arsort($materialCounts);
        $breakdown = collect($materialCounts)->map(fn ($n, $name) => "{$n} {$name}")->implode(', ');

        return redirect('/admin/batch-list')->with('success',
            'Imported ' . array_sum($materialCounts) . ' record(s)' . ($breakdown ? ': ' . $breakdown . '.' : '.'));
    }

    /**
     * The validated material map posted with a batch: [field, [normalised value => material id]].
     * Values mapped to a material that doesn't exist are dropped (they fall back to the default).
     */
    private function batchMaterialMap(Request $request): array
    {
        $field = (string) $request->input('material_map_field', '');
        if (! preg_match('#^(\d{3}\$[a-z0-9]|LDR/\d{2})$#i', $field)) return [null, []];

        $raw = json_decode((string) $request->input('material_map', '{}'), true);
        if (! is_array($raw)) return [null, []];

        $valid = Material::pluck('id')->map(fn ($id) => (int) $id)->all();
        $map = [];
        foreach ($raw as $value => $materialId) {
            $key = MarcMaterialMapper::normalize($value);
            if ($key !== '' && in_array((int) $materialId, $valid, true)) $map[$key] = (int) $materialId;
        }

        return $map ? [$field, $map] : [null, []];
    }

    /** Scan an uploaded MARC file (nothing is imported) for fields that can identify material type. */
    public function analyzeBatchFile(Request $request, MarcMaterialMapper $mapper)
    {
        $request->validate(['batch_file' => 'required|file|mimes:mrc|max:40960']);

        try {
            return response()->json($mapper->analyze($request->file('batch_file')->getRealPath()));
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'This file could not be read as MARC records.'], 422);
        }
    }

    /** Save (create or overwrite, by name) a material-type mapping preset. */
    public function saveMaterialMap(Request $request)
    {
        $data = $request->validate([
            'name'                     => ['required', 'string', 'max:100'],
            'source_field'             => ['required', 'string', 'regex:#^(\d{3}\$[a-z0-9]|LDR/\d{2})$#i'],
            'mappings'                 => ['required', 'json'],   // {"value": material_id} — JSON so any value text survives
            'default_material_type_id' => ['nullable', 'integer', 'exists:materials,id'],
        ], ['source_field.regex' => 'Choose a field such as 942$c or LDR/06.']);

        $valid = Material::pluck('id')->map(fn ($id) => (int) $id)->all();
        $mappings = [];
        foreach ((array) json_decode($data['mappings'], true) as $value => $id) {
            $key = MarcMaterialMapper::normalize($value);
            if ($key !== '' && in_array((int) $id, $valid, true)) $mappings[$key] = (int) $id;
        }
        if (! $mappings) {
            throw \Illuminate\Validation\ValidationException::withMessages(['mappings' => 'Map at least one value to a Material Type.']);
        }

        $preset = MaterialTypeMap::firstOrNew(['name' => trim($data['name'])]);
        $preset->fill([
            'source_field'             => $data['source_field'],
            'mappings'                 => $mappings,
            'default_material_type_id' => $data['default_material_type_id'] ?? null,
            'updated_by'               => Auth::id(),
        ]);
        if (! $preset->exists) $preset->created_by = Auth::id();
        $preset->save();

        return response()->json(['preset' => $preset->only(['id', 'name', 'source_field', 'mappings', 'default_material_type_id'])]);
    }

    public function destroyMaterialMap($id)
    {
        MaterialTypeMap::findOrFail($id)->delete();

        return response()->json(['message' => 'Preset deleted.']);
    }

    public function batchList()
    {
        return view('admin.batch-list');
    }

    public function createBatch()
    {
        $materials = Material::forPicker();
        $presets = MaterialTypeMap::orderBy('name')->get(['id', 'name', 'source_field', 'mappings', 'default_material_type_id']);

        return view('admin.create-batch', ['materials' => $materials, 'presets' => $presets]);
    }

    public function destroyBatch(Request $request)
    {
        $user = Auth::user();

        // Capture the batch's catalog ids before the soft-delete so we can drop them from RAG.
        $batchIds = Catalog::where('catalog_import_id', $request->id)->pluck('id');

        // The batch, its records, their OPAC rows and the RAG removal queue change together.
        DB::transaction(function () use ($request, $user, $batchIds) {
            CatalogImport::where('id',$request->id)
                ->update([
                        'deleted_at' => now(),
                        'deleted_by' => $user->id
                    ]);

            Catalog::where('catalog_import_id',$request->id)
                ->update([
                        'deleted_at' => now(),
                        'deleted_by' => $user->id
                    ]);

            ViewCatalog::where('catalog_import_id',$request->id)
                ->update([
                        'deleted_at' => now(),
                        'deleted_by' => $user->id
                    ]);

            RagSync::enqueueMany($batchIds, RagSync::DELETE, 'deleted');
        });

        // Same as destroyRecord: the query-builder update bypasses Scout, so remove the
        // whole batch from the search index (else the docs linger with deleted_at = null).
        ViewCatalog::where('catalog_import_id', $request->id)->get()->unsearchable();

        // Deleting a batch changes the collection -> bust the home caches (destroyRecord
        // already does this; batch delete was missing it and left a stale homepage).
        clearHomeCache();

        return redirect()->back();
    }

    public function batchListJson(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowPerPage = $request->get("length"); // Rows display per page

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        // Whitelisted sortable columns; default = most recent import first.
        $sortable = [
            'id'            => 'catalog_imports.id',
            'subject'       => 'catalog_imports.subject',
            'comment'       => 'catalog_imports.comment',
            'created_at'    => 'catalog_imports.created_at',
        ];
        $columnIndex     = $columnIndex_arr[0]['column'] ?? 0;
        $requestedData   = $columnName_arr[$columnIndex]['data'] ?? 'created_at';
        $orderColumn     = $sortable[$requestedData] ?? 'catalog_imports.created_at';
        $columnSortOrder = (isset($order_arr[0]['dir']) && strtolower($order_arr[0]['dir']) === 'asc') ? 'asc' : 'desc';
        $searchValue     = $search_arr['value'] ?? '';

         // Total records
        $totalRecords = CatalogImport::where('deleted_at', null)->count();

        

        $totalRecordswithFilter = CatalogImport::where(function ($query) use ($searchValue) {
                $query->where('catalog_imports.no_of_records', 'like', '%' . $searchValue . '%')
                    ->orWhere('catalog_imports.comment', 'like', '%' . $searchValue . '%')
                    ->orWhere('catalog_imports.subject', 'like', '%' . $searchValue . '%')
                    ->orWhere('catalog_imports.created_at', 'like', '%' . $searchValue . '%');
            })->where('catalog_imports.deleted_at', null)->count();

        // Fetch records (count live catalog rows). The batch shows its subject, not a material
        // type: records can each have their own type (Batch Import mapping).
        $records = CatalogImport::select(
                'catalog_imports.id as batch_id',
                'catalog_imports.no_of_records',
                'catalog_imports.subject',
                'catalog_imports.comment',
                'catalog_imports.created_at',
                \DB::raw('count(case when catalog_imports.id = catalogs.catalog_import_id and catalogs.deleted_at is null then 1 else null end) as current_no_of_records')
            )
            ->join('catalogs', 'catalogs.catalog_import_id', '=', 'catalog_imports.id')
            ->where(function ($query) use ($searchValue) {
                $query->where('catalog_imports.no_of_records', 'like', '%' . $searchValue . '%')
                    ->orWhere('catalog_imports.comment', 'like', '%' . $searchValue . '%')
                    ->orWhere('catalog_imports.subject', 'like', '%' . $searchValue . '%')
                    ->orWhere('catalog_imports.created_at', 'like', '%' . $searchValue . '%');
            })
            ->where('catalog_imports.deleted_at', null)
            ->groupBy('catalog_imports.id', 'catalog_imports.no_of_records', 'catalog_imports.subject', 'catalog_imports.comment', 'catalog_imports.created_at')
            ->orderBy($orderColumn, $columnSortOrder)
            ->skip($start)
            ->take($rowPerPage)
            ->get();

        $data_arr = array();

        foreach($records as $record){
            $data_arr[] = array(
                "id" => $record->batch_id,
                "init_no_of_records" => $record->no_of_records,
                "current_no_of_records" => $record->current_no_of_records,
                "subject" => $record->subject,
                "comment" => $record->comment,
                "created_at" => $record->created_at,
            );
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr
        );
 
        return response()->json($response);


    }

    public function singleImportMarcFile(MarcFileUploadRequest $request)
    {
        //$marcTagsFields = MarctagSubfield::get();

        $record = Record::fromFile($request->file('marcFile'));

        $a = $record->record;
        
        

        foreach ($a->getFields() as $f) {
            
            $tags = $f->getTag();

            $marcTags[] = $tags;
        
        }
        try{
            $fa = [];

            $subfieldArr = array();
            $defaultArr = array();
            $indicatorArr = array();
            
            $indicatorArr[0] = null;
            $indicatorArr[1] = null;

            // LDR - LEADER
            $l = str_split($a->getLeader());

            $subfieldArr[0] = getSubfieldId('LDR$00-04');
            $defaultArr[0] = $l[0].$l[1].$l[2].$l[3].$l[4];
            
            $subfieldArr[1] = getSubfieldId('LDR$05');
            $defaultArr[1] = $l[5];

            $subfieldArr[2] = getSubfieldId('LDR$06');
            $defaultArr[2] = $l[6];

            $subfieldArr[3] = getSubfieldId('LDR$07');
            $defaultArr[3] = $l[7];

            $subfieldArr[4] = getSubfieldId('LDR$08');
            $defaultArr[4] = $l[8];

            $subfieldArr[5] = getSubfieldId('LDR$09');
            $defaultArr[5] = $l[9];

            $subfieldArr[6] = getSubfieldId('LDR$10');
            $defaultArr[6] = $l[10];

            $subfieldArr[7] = getSubfieldId('LDR$11');
            $defaultArr[7] = $l[11];

            $subfieldArr[8] = getSubfieldId('LDR$12-16');
            $defaultArr[8] = $l[12].$l[13].$l[14].$l[15].$l[16];

            $subfieldArr[9] = getSubfieldId('LDR$17');
            $defaultArr[9] = $l[17];

            $subfieldArr[10] = getSubfieldId('LDR$18');
            $defaultArr[10] = $l[18];

            $subfieldArr[11] = getSubfieldId('LDR$19');
            $defaultArr[11] = $l[19];

            $subfieldArr[12] = getSubfieldId('LDR$20');
            $defaultArr[12] = $l[20];

            $subfieldArr[13] = getSubfieldId('LDR$21');
            $defaultArr[13] = $l[21];

            $subfieldArr[14] = getSubfieldId('LDR$22');
            $defaultArr[14] = $l[22];

            $subfieldArr[15] = getSubfieldId('LDR$23');
            $defaultArr[15] = $l[23];
            
            $fa[] = array(
                'id' => "2501",
                'required' =>  0,
                'subfield' => $subfieldArr,
                'default' => $defaultArr,
                'indicator' => $indicatorArr,

            );

            $repeatedMarctags = [];

            for($w = 0; $w< count($marcTags); $w++) {
                
                $subfieldArr = array();

                $defaultArr = array();

                $indicatorArr = array();

                $required = 0;

                foreach ($record->query($marcTags[$w]) as $field) {
                    
                    $marcTagid = getTagDetails(0,$marcTags[$w]);
                    
                    if(method_exists($field->field, 'getSubfields')) {
                        $reflectionClass = new ReflectionClass($field->field);
                        $methods = $reflectionClass->getMethods();
                    
                        $indicatorArr1[$w] = $field->getIndicator(1);
                        $indicatorArr2[$w] = $field->getIndicator(2);
                        
                        foreach($field->getSubfields() as $y) {
                            $sfCode = $marcTags[$w]."$".$y->getCode();
                            $subfieldArr[] = getSubfieldId($sfCode);
                            $defaultArr[] = $y->getData();
                            $indicatorArr[0] = getFirstIndicatorId($marcTagid, $indicatorArr1[$w]);
                            $indicatorArr[1] = getSecondIndicatorId($marcTagid, $indicatorArr2[$w]);
                        }
                    } else {
                        $string = $field->getData();
                        $c = str_split($string);


                        if($marcTags[$w] == '001'){

                            $subfieldArr[0] = getSubfieldId('001$#');
                            $defaultArr[0] = $string;
                        }

                        if($marcTags[$w] == '003'){

                            $subfieldArr[0] = getSubfieldId('003$#');
                            $defaultArr[0] = $string;
                        }

                        if($marcTags[$w] == '005'){

                            $subfieldArr[0] = getSubfieldId('005$#');
                            $defaultArr[0] = $string;
                        }

                        if($marcTags[$w] == '006'){

                            $subfieldArr[0] = getSubfieldId('006$#');
                            $defaultArr[0] = $string;
                        }

                        if($marcTags[$w] == '007'){

                            $subfieldArr[0] = getSubfieldId('007$#');
                            $defaultArr[0] = $string;
                        }


                        if($marcTags[$w] == '008') {
                        
                            $subfieldArr[0] = getSubfieldId('008$00-05');
                            $defaultArr[0] = $c[0].$c[1].$c[2].$c[3].$c[4].$c[5];

                            $subfieldArr[1] = getSubfieldId('008$06');
                            $defaultArr[1] = $c[6];

                            $subfieldArr[2] = getSubfieldId('008$07-10');
                            $defaultArr[2] = $c[7].$c[8].$c[9].$c[10];

                            $subfieldArr[3] = getSubfieldId('008$11-14');
                            $defaultArr[3] = $c[11].$c[12].$c[13].$c[14];

                            $subfieldArr[4] = getSubfieldId('008$15-17');
                            $defaultArr[4] = $c[15].$c[16].$c[17];
                            
                            $subfieldArr[5] = getSubfieldId('008$18-34');
                            $defaultArr[5] = $c[18].$c[19].$c[20].$c[21].$c[22].$c[23].$c[24].$c[25].$c[26].$c[27].$c[28].$c[29].$c[30].$c[31].$c[32].$c[33].$c[34];
                            
                                if(isset($c[35])) {
                                    $subfieldArr[6] = getSubfieldId('008$35-37');
                                    $defaultArr[6] = $c[35].$c[36].$c[37];
                                    
                                    $subfieldArr[7] = getSubfieldId('008$38');
                                    $defaultArr[7] = $c[38];

                                    $subfieldArr[8] = getSubfieldId('008$39');
                                    $defaultArr[8] = $c[39];
                                }
                            
                        } 
                        $indicatorArr[0] = null;
                        $indicatorArr[1] = null;

                        
                    }
                }
                
                if(!in_array($marcTagid, $repeatedMarctags)) {
                    $fa[] = array(
                        'id' => $marcTagid,
                        'required' =>  $required,
                        'subfield' => $subfieldArr,
                        'default' => $defaultArr,
                        'indicator' => $indicatorArr,
    
                    );
                    $repeatedMarctags[] = $marcTagid;
                }

                // dump($fa)
                $faString = json_encode($fa);
            }

        } catch (Exception $e) {
            dump("Error: $e->getMessage()");
        }

        // dd($fa);
        $fieldArray = json_decode($faString);
    
        $detailsArr = array();
        
        $indicatorArr = array();
        
        foreach($fieldArray as $index => $tag) {
            // dump($tag);
            $tagDetail = getTagDetails($tag->id);

            $subfieldArr = array();
            if(count($tag->subfield) > 0) {
                foreach($tag->subfield as $i => $id)
                {
                    // dd($id);
                    $subFieldDetails = getSubfieldDetails($id);

                    $subfieldArr[$i] = ['id' => $subFieldDetails->id, 'code' => $subFieldDetails->code, 'description' => $subFieldDetails->description, 'default' => count($tag->default) > 0 ? $tag->default[$i] : "Enter value" ];
                }
            } 

            $ind1_id = is_null(getIndicatorDetails($tag->indicator[0])) ? 0 : getIndicatorDetails($tag->indicator[0])->id;
            $ind2_id = is_null(getIndicatorDetails($tag->indicator[1])) ? 0 : getIndicatorDetails($tag->indicator[1])->id;
            $ind1_text = is_null(getIndicatorDetails($tag->indicator[0])) ? 'N' : getIndicatorDetails($tag->indicator[0])->indicator;
            $ind2_text = is_null(getIndicatorDetails($tag->indicator[1])) ? 'N' : getIndicatorDetails($tag->indicator[1])->indicator;
            $indicatorArr[0] = ['id' => $ind1_id, 'indicator' => $ind1_text];
            $indicatorArr[1] = ['id' => $ind2_id, 'indicator' => $ind2_text];

            $detailsArr[$index] = 
                [
                    'tag' => ['id' => $tagDetail->id, 'code' => $tagDetail->code, 'description' => $tagDetail->description, 'isRequired' => $tag->required], 
                    'subfields' =>  $subfieldArr,
                    'indicators' => $indicatorArr,
                ];
        }
        $response = array(  
            "fields" => $detailsArr,
        );


        $jsonOutput = json_encode($response);

        $marcTemplates = MarcTemplate::get();

        $materials = Material::forPicker();

        if ($request->action  == "edit") {

            $record = Catalog::where('id', $request->record_id)->first();
            $materials = Material::forPicker($this->recordMaterialId($record));
            
            return view('admin.catalog-edit-record', ['marcTemplates' => $marcTemplates, 'materials' => $materials, 'fieldData' => $jsonOutput, 'record' => $record, 'recordMaterialId' => $this->recordMaterialId($record)]);            
        }

        return view('admin.create-catalog-record', ['marcTemplates' => $marcTemplates, 'materials' => $materials, 'fieldData' => $jsonOutput]);
    }
    
    /**
     * The material a record is saved under — the SAME value for catalogs.material_type_id
     * (Record History, Reports, circulation) and view_catalogs (OPAC, search filters).
     *
     * It is the Material Type the cataloger picked. LDR/06 ("type of record") is a different,
     * MARC-level axis — a thesis or a book is LDR/06 'a' (language material) — and stays in
     * field_data; it is only used when no Material Type was picked (e.g. a MARC import without
     * one). Previously LDR/06 always won for the catalog row, so a Thesis was recorded as
     * "Language material" there while the OPAC showed "Thesis/Dissertations".
     */
    private function resolveMaterial($selectedId, $recordTypeCode = null)
    {
        $material = $selectedId ? getMaterialData($selectedId) : null;

        if (! $material && $recordTypeCode) {
            $material = getMaterialDataCUsingCode($recordTypeCode);
        }

        if (! $material) {
            throw \Illuminate\Validation\ValidationException::withMessages(['material_type' => 'Select a Material Type.']);
        }

        return $material;
    }

    /**
     * Material to pre-select on the Edit page: the one the OPAC shows (view_catalogs). Records
     * saved before resolveMaterial() can still carry an LDR/06-derived id in catalogs.
     */
    private function recordMaterialId($record)
    {
        return optional(ViewCatalog::where('catalog_id', $record->id)->first())->material_type_id
            ?? $record->material_type_id;
    }

    /**
     * Create a catalogue record and its OPAC row (view_catalogs) in one transaction: either both
     * exist or neither does. Scout indexes the OPAC row after the commit (scout.after_commit).
     */
    private function createWithOpacRow(array $catalogRow, array $opacRow): Catalog
    {
        return DB::transaction(function () use ($catalogRow, $opacRow) {
            $catalog = Catalog::create($catalogRow);
            ViewCatalog::create(['catalog_id' => $catalog->id] + $opacRow);
            return $catalog;
        });
    }

    public function storeRecord(Request $request)
    {
        $user = Auth::user();

        $titleTagCode = "245"; // Marctag for Title Statemet 
        $titleCode =  "245\$a"; // Subfield code for Title
        
        $generalNoteTagCode = "500";
        $generalNoteCode =  "500\$a";

        $summaryTagCode = "520";
        $summaryCode = "520\$a";

        $publisherTagCode = "260";
        $publishersCode = "260\$b";
        $publicationDatesCode = "260\$c";

        $isbnTagCode = "020";
        $isbnCode = "020\$a";

        $issnTagCode = "022";
        $issnCode = "022\$a";

        $callNumberTagCode = "050";
        $callNumberCode =  "050\$a";

        $recordTypeTagCode = "LDR";
        $recordTypeCode = "LDR\$06";

            $fieldData = json_decode($request->field_data, true);
            // Getting the title 
            $matchingTitles = collect($fieldData)->filter(function ($item) use ($titleTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $titleTagCode;
            })->flatMap(function ($item) use ($titleCode) {
                $titles = []; 
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($titleCode, &$titles) {
                        if ($subfield['code'] == $titleCode) {
                            $titles['title'] = $subfield['value'];
                        }
                    });
                }            
                return $titles;
            })->toArray();

            // Getting the recordtype 
            $matchingRecordType = collect($fieldData)->filter(function ($item) use ($recordTypeTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $recordTypeTagCode;
            })->flatMap(function ($item) use ($recordTypeCode) {
                $recordType = []; 
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($recordTypeCode, &$recordType) {
                        if ($subfield['code'] == $recordTypeCode) {
                            $recordType['recordType'] = $subfield['value'];
                        }
                    });
                }            
                return $recordType;
            })->toArray();
            

            // Getting the ISBN 
            $matchingISBN = collect($fieldData)->filter(function ($item) use ($isbnTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $isbnTagCode;
            })->flatMap(function ($item) use ($isbnCode) {
                $isbn = []; 
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($isbnCode, &$isbn) {
                        if ($subfield['code'] == $isbnCode) {
                            $isbn['isbn'] = $subfield['value'];
                        }
                    });
                }            
                return $isbn;
            })->toArray();

            // Getting the ISSN 
            $matchingISSN = collect($fieldData)->filter(function ($item) use ($issnTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $issnTagCode;
            })->flatMap(function ($item) use ($issnCode) {
                $issn = []; 
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($issnCode, &$issn) {
                        if ($subfield['code'] == $issnCode) {
                            $issn['issn'] = $subfield['value'];
                        }
                    });
                }            
                return $issn;
            })->toArray();


            // Getting the general note
            $matchingGeneralNote = collect($fieldData)->filter(function ($item) use ($generalNoteTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $generalNoteTagCode;
            })->flatMap(function ($item) use ($generalNoteCode) {
                $generalNote = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($generalNoteCode, &$generalNote) {
                        if ($subfield['code'] == $generalNoteCode) {
                            $generalNote['general_note'] = $subfield['value'];
                        }
                    });
                }            
                return $generalNote;
            })->toArray();


            // Getting the call number
            $matchingCallNumber = collect($fieldData)->filter(function ($item) use ($callNumberTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $callNumberTagCode;
            })->flatMap(function ($item) use ($callNumberCode) {
                $callNumber = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($callNumberCode, &$callNumber) {
                        if ($subfield['code'] == $callNumberCode) {
                            $callNumber['call_number'] = $subfield['value'];
                        }
                    });
                }            
                return $callNumber;
            })->toArray();
            

            // Getting the Summary
            $matchingSummary = collect($fieldData)->filter(function ($item) use ($summaryTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $summaryTagCode;
            })->flatMap(function ($item) use ($summaryCode) {
                $summary = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($summaryCode, &$summary) {
                        if ($subfield['code'] == $summaryCode) {
                            $summary['summary'] = $subfield['value'];
                        }
                    });
                }            
                return $summary;
            })->toArray();

            // Getting the Publisher
            $matchingPublisher = collect($fieldData)->filter(function ($item) use ($publisherTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $publisherTagCode;
            })->flatMap(function ($item) use ($publishersCode) {
                $publisher = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($publishersCode, &$publisher) {
                        if ($subfield['code'] == $publishersCode) {
                            $publisher['publisher'] = $subfield['value'];
                        }
                    });
                }            
                return $publisher;
            })->toArray();


            // Getting the Date of Publication
            $matchingDateOfPublication = collect($fieldData)->filter(function ($item) use ($publisherTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $publisherTagCode;
            })->flatMap(function ($item) use ($publicationDatesCode) {
                $dateOfPublication = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($publicationDatesCode, &$dateOfPublication) {
                        if ($subfield['code'] == $publicationDatesCode) {
                            $dateOfPublication['date_of_pub'] = $subfield['value'];
                        }
                    });
                }            
                return $dateOfPublication;
            })->toArray();


            // One material for the catalog row AND the OPAC row (see resolveMaterial()).
            $material = $this->resolveMaterial($request->material_type, $matchingRecordType['recordType'] ?? null);

            $material_type = $material->name;
            $material_type_id = $material->id;

            $viewCatMatType = $material;

        $catalogRow = [
            'material_type_id' => $material_type_id,
            'is_aanr' => isset($request->is_aanr) ? 1 : 0,
            'marc_class_sys_id' => 1,
            'accession_number'=> $request->accession_number,
            'field_data' => $request->field_data,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];

        $opacRow = [
            'isbn' => isset($matchingISBN['isbn']) ? $matchingISBN['isbn'] : NULL,
            'issn' => isset($matchingISSN['issn']) ? $matchingISSN['issn'] : NULL,
            'accession_number' => $request->accession_number,
            'material_type' => $viewCatMatType->name,
            'material_type_id'=> $viewCatMatType->id,
            'title_statement' => marcTitle($fieldData) ?? ($matchingTitles['title'] ?? NULL),   // full 245 ($a $b $n $p $c ...), not just $a
            'summary' => isset($matchingSummary['summary']) ? $matchingSummary['summary'] : NULL,
            'general_note' => isset($matchingGeneralNote['general_note']) ? $matchingGeneralNote['general_note'] : NULL,
            'call_number' => isset($matchingCallNumber['call_number']) ? $matchingCallNumber['call_number'] : NULL,
            'publisher' => isset($matchingPublisher['publisher']) ? $matchingPublisher['publisher'] : NULL,
            'pub_year' => isset($matchingDateOfPublication['date_of_pub']) ? $matchingDateOfPublication['date_of_pub'] : NULL,
            'joint_authors' => marcAuthors($fieldData),   // 100 + 110, else 700 + 710; " / "-separated
            'subjects' => marcSubjects($fieldData),        // 600/610/650/651/653 $a (Subject filter)
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];

        // The record, its OPAC row and its RAG outbox entry are written together. Queued for RAG
        // sync because new records default to on-hold; the worker gates on is_published.
        $catalog = DB::transaction(function () use ($catalogRow, $opacRow) {
            $catalog = $this->createWithOpacRow($catalogRow, $opacRow);
            RagSync::enqueue($catalog->id, RagSync::UPSERT, 'created');
            return $catalog;
        });

        ensurePlaceholderCover($catalog->id);

        clearHomeCache();

        // Post/Redirect/Get: land on the new record's view page (a redirect, so a refresh
        // won't re-submit the form and create a duplicate).
        return redirect()->route('show-record', ['id' => $catalog->id]);
    }

    public function destroyRecord(Request $request)
    {
        $user = Auth::user();

        // Record, OPAC row and RAG outbox entry change together. The outbox entry removes this
        // record's chunks from the AI index.
        DB::transaction(function () use ($request, $user) {
            Catalog::where('id',$request->id)
                ->update([
                        'deleted_at' => now(),
                        'deleted_by' => $user->id
                    ]);

            ViewCatalog::where('catalog_id',$request->id)
                ->update([
                    'deleted_at' => now(),
                    'deleted_by' => $user->id
                ]);

            RagSync::enqueue((int) $request->id, RagSync::DELETE, 'deleted');
        });

        // Query-builder updates bypass Scout, so drop the record from the search index.
        // Otherwise its Meilisearch doc keeps deleted_at = null, still matches the OPAC's
        // "deleted_at IS NULL" filter, and inflates the result count vs what's shown.
        optional(ViewCatalog::where('catalog_id', $request->id)->first())->unsearchable();

        clearHomeCache();
        return redirect()->back();
    }

    public function editRecord($id)
    {
        $record = Catalog::where('id', $id)->first();

        $marcTemplates = MarcTemplate::get();

        $materials = Material::forPicker($this->recordMaterialId($record));   // + the record's own type, even if hidden

        return view('admin.catalog-edit-record', [
            'marcTemplates'    => $marcTemplates,
            'materials'        => $materials,
            'record'           => $record,
            'recordMaterialId' => $this->recordMaterialId($record),
            // The record in SOURCE shape for the shared MarcEditor (create + edit share one
            // module). No 'fieldData' key here — that is set ONLY on a Parse-a-Record round-trip
            // (singleImportMarcFile action=edit), which flips the page's import flag so
            // single-import.js repopulates the editor instead of this initial record load.
            'recordMarcFields' => $this->recordSourceFields($record->field_data),
        ]);
    }

    public function updateRecord(Request $request, $id)
    {
        $user = Auth::user();

        $titleTagCode = "245"; // Marctag for Title Statemet 
        $titleCode =  "245\$a"; // Subfield code for Title
        
        $generalNoteTagCode = "500";
        $generalNoteCode =  "500\$a";

        $summaryTagCode = "520";
        $summaryCode = "520\$a";

        $publisherTagCode = "260";
        $publishersCode = "260\$b";
        $publicationDatesCode = "260\$c";

        $isbnTagCode = "020";
        $isbnCode = "020\$a";

        $issnTagCode = "022";
        $issnCode = "022\$a";

        $callNumberTagCode = "050";
        $callNumberCode =  "050\$a";

        $recordTypeTagCode = "LDR";
        $recordTypeCode = "LDR\$06";

            $fieldData = json_decode($request->field_data, true);
            // Getting the title 
            $matchingTitles = collect($fieldData)->filter(function ($item) use ($titleTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $titleTagCode;
            })->flatMap(function ($item) use ($titleCode) {
                $titles = []; 
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($titleCode, &$titles) {
                        if ($subfield['code'] == $titleCode) {
                            $titles['title'] = $subfield['value'];
                        }
                    });
                }            
                return $titles;
            })->toArray();

            // Getting the recordtype 
            $matchingRecordType = collect($fieldData)->filter(function ($item) use ($recordTypeTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $recordTypeTagCode;
            })->flatMap(function ($item) use ($recordTypeCode) {
                $recordType = []; 
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($recordTypeCode, &$recordType) {
                        if ($subfield['code'] == $recordTypeCode) {
                            $recordType['recordType'] = $subfield['value'];
                        }
                    });
                }            
                return $recordType;
            })->toArray();
            

            // Getting the ISBN 
            $matchingISBN = collect($fieldData)->filter(function ($item) use ($isbnTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $isbnTagCode;
            })->flatMap(function ($item) use ($isbnCode) {
                $isbn = []; 
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($isbnCode, &$isbn) {
                        if ($subfield['code'] == $isbnCode) {
                            $isbn['isbn'] = $subfield['value'];
                        }
                    });
                }            
                return $isbn;
            })->toArray();

            // Getting the ISSN 
            $matchingISSN = collect($fieldData)->filter(function ($item) use ($issnTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $issnTagCode;
            })->flatMap(function ($item) use ($issnCode) {
                $issn = []; 
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($issnCode, &$issn) {
                        if ($subfield['code'] == $issnCode) {
                            $issn['issn'] = $subfield['value'];
                        }
                    });
                }            
                return $issn;
            })->toArray();


            // Getting the general note
            $matchingGeneralNote = collect($fieldData)->filter(function ($item) use ($generalNoteTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $generalNoteTagCode;
            })->flatMap(function ($item) use ($generalNoteCode) {
                $generalNote = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($generalNoteCode, &$generalNote) {
                        if ($subfield['code'] == $generalNoteCode) {
                            $generalNote['general_note'] = $subfield['value'];
                        }
                    });
                }            
                return $generalNote;
            })->toArray();


            // Getting the call number
            $matchingCallNumber = collect($fieldData)->filter(function ($item) use ($callNumberTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $callNumberTagCode;
            })->flatMap(function ($item) use ($callNumberCode) {
                $callNumber = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($callNumberCode, &$callNumber) {
                        if ($subfield['code'] == $callNumberCode) {
                            $callNumber['call_number'] = $subfield['value'];
                        }
                    });
                }            
                return $callNumber;
            })->toArray();
            

            // Getting the Summary
            $matchingSummary = collect($fieldData)->filter(function ($item) use ($summaryTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $summaryTagCode;
            })->flatMap(function ($item) use ($summaryCode) {
                $summary = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($summaryCode, &$summary) {
                        if ($subfield['code'] == $summaryCode) {
                            $summary['summary'] = $subfield['value'];
                        }
                    });
                }            
                return $summary;
            })->toArray();

            // Getting the Publisher
            $matchingPublisher = collect($fieldData)->filter(function ($item) use ($publisherTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $publisherTagCode;
            })->flatMap(function ($item) use ($publishersCode) {
                $publisher = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($publishersCode, &$publisher) {
                        if ($subfield['code'] == $publishersCode) {
                            $publisher['publisher'] = $subfield['value'];
                        }
                    });
                }            
                return $publisher;
            })->toArray();


            // Getting the Date of Publication
            $matchingDateOfPublication = collect($fieldData)->filter(function ($item) use ($publisherTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $publisherTagCode;
            })->flatMap(function ($item) use ($publicationDatesCode) {
                $dateOfPublication = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($publicationDatesCode, &$dateOfPublication) {
                        if ($subfield['code'] == $publicationDatesCode) {
                            $dateOfPublication['date_of_pub'] = $subfield['value'];
                        }
                    });
                }            
                return $dateOfPublication;
            })->toArray();


            // One material for the catalog row AND the OPAC row (see resolveMaterial()).
            $material = $this->resolveMaterial($request->material_type, $matchingRecordType['recordType'] ?? null);

            $material_type = $material->name;
            $material_type_id = $material->id;

            $viewCatMatType = $material;
            
            
            $catalogRow = [
                'material_type_id' => $material_type_id,
                'is_aanr' => isset($request->is_aanr) ? 1 : 0,
                'marc_class_sys_id' => 1,
                'accession_number'=> $request->accession_number,
                'field_data' => $request->field_data,
                'updated_by' => $user->id,
            ];

            $opacRow = [
                'isbn' => isset($matchingISBN['isbn']) ? $matchingISBN['isbn'] : NULL,
                'issn' => isset($matchingISSN['issn']) ? $matchingISSN['issn'] : NULL,
                'accession_number' => $request->accession_number,
                'material_type' => $viewCatMatType->name,
                'material_type_id'=> $viewCatMatType->id,
                'title_statement' => marcTitle($fieldData) ?? ($matchingTitles['title'] ?? NULL),   // full 245 ($a $b $n $p $c ...), not just $a
                'summary' => isset($matchingSummary['summary']) ? $matchingSummary['summary'] : NULL,
                'general_note' => isset($matchingGeneralNote['general_note']) ? $matchingGeneralNote['general_note'] : NULL,
                'call_number' => isset($matchingCallNumber['call_number']) ? $matchingCallNumber['call_number'] : NULL,
                'publisher' => isset($matchingPublisher['publisher']) ? $matchingPublisher['publisher'] : NULL,
                'pub_year' => isset($matchingDateOfPublication['date_of_pub']) ? $matchingDateOfPublication['date_of_pub'] : NULL,
                'joint_authors' => marcAuthors($fieldData),   // 100 + 110, else 700 + 710; " / "-separated
                'subjects' => json_encode(marcSubjects($fieldData), JSON_UNESCAPED_UNICODE),   // query-builder update: no casts
                'pub_year_num' => pubYearNumber($matchingDateOfPublication['date_of_pub'] ?? null),   // ...and no model hook
                'updated_by' => $user->id,
            ];

        // Record, OPAC row and RAG outbox entry change together (the outbox entry because
        // metadata and/or full text may have changed).
        $catalog = DB::transaction(function () use ($id, $catalogRow, $opacRow) {
            $changed = Catalog::where('id', $id)->update($catalogRow);
            if ($changed) ViewCatalog::where('catalog_id', $id)->update($opacRow);
            RagSync::enqueue((int) $id, RagSync::UPSERT, 'updated');
            return $changed;
        });

        if($catalog) {
            // A query-builder update doesn't reach the search index: refresh this record's entry,
            // or the OPAC keeps finding it by its old title, authors and subjects.
            try {
                ViewCatalog::where('catalog_id', $id)->searchable();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        clearHomeCache();

        // Post/Redirect/Get: land on the record's view page after saving (same as create).
        return redirect()->route('show-record', ['id' => $id]);
    }

    public function recordList(Request $request)
    {
        return view('admin.catalog-record-list', ['filters' => $this->catalogListFilterOptions(true)]);
    }

    public function onholdList(Request $request)
    {
        return view('admin.catalog-onhold-list', ['filters' => $this->catalogListFilterOptions(false)]);
    }

    public function recordListJson(Request $request)
    {
        return $this->catalogListJson($request, true);
    }

    public function onholdListJson(Request $request)
    {
        return $this->catalogListJson($request, false);
    }

    /**
     * Record List (published) / On-Hold (not published) table data — one implementation for both.
     * Server-side paging, sorting, search and the toolbar filters: type, author, year, resources.
     */
    private function catalogListJson(Request $request, ?bool $published, ?int $batchId = null)
    {
        // Whitelist of sortable columns (DataTables column `data` name => real DB column). Anything
        // else falls back to Title, so the list stays alphabetical unless the admin sorts another column.
        $sortMap = [
            'id'              => 'view_catalogs.catalog_id',
            'title_statement' => 'view_catalogs.title_statement',
            'material_type'   => 'view_catalogs.material_type',
            'authors'         => 'view_catalogs.joint_authors',
            'pub_year'        => 'view_catalogs.pub_year',
        ];
        $start  = max(0, (int) $request->get('start', 0));
        $length = (int) $request->get('length', 25);
        if ($length <= 0 || $length > 500) $length = 25;
        $colIdx      = (int) $request->input('order.0.column', 1);
        $columnName  = $sortMap[$request->input("columns.$colIdx.data", 'title_statement')] ?? 'view_catalogs.title_statement';
        $sortDir     = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $searchValue = trim((string) $request->input('search.value', ''));

        $totalRecords = $this->catalogListBase($published, $batchId)->count();   // the whole list, before filters / search

        $query = $this->catalogListQuery($request, $published, $batchId, $searchValue);
        $totalRecordswithFilter = (clone $query)->count();

        $records = $query->select('view_catalogs.*')
            ->selectRaw('EXISTS (SELECT 1 FROM frontpages WHERE frontpages.catalog_id = view_catalogs.catalog_id AND frontpages.deleted_at IS NULL) AS has_coverpage')
            ->selectRaw('EXISTS (SELECT 1 FROM multimedia WHERE multimedia.catalog_id = view_catalogs.catalog_id AND multimedia.deleted_at IS NULL) AS has_multimedia')
            ->orderBy($columnName, $sortDir)
            ->orderBy('view_catalogs.catalog_id')
            ->skip($start)
            ->take($length)
            ->get();

        $resultArr = [];
        foreach ($records as $record) {
            $resultArr[] = [
                'id'              => $record->catalog_id,
                'material_type'   => $record->material_type,
                'pub_year'        => htmlspecialchars((string) $record->pub_year, ENT_QUOTES, 'UTF-8'),
                'publisher'       => htmlspecialchars((string) $record->publisher, ENT_QUOTES, 'UTF-8'),
                'authors'         => htmlspecialchars((string) $record->joint_authors, ENT_QUOTES, 'UTF-8'),
                'title_statement' => htmlspecialchars((string) $record->title_statement, ENT_QUOTES, 'UTF-8'),
                'has_multimedia'  => (int) $record->has_multimedia,
                'has_coverpage'   => (int) $record->has_coverpage,
                'is_published'    => (int) $record->is_published,
            ];
        }

        return response()->json([
            'draw'                 => (int) $request->get('draw'),
            'iTotalRecords'        => $totalRecords,
            'iTotalDisplayRecords' => $totalRecordswithFilter,
            'aaData'               => $resultArr,
        ]);
    }

    /** Record List = published, On-Hold = not published, a batch = both (filter by Status). */
    private function catalogListBase(?bool $published, ?int $batchId = null)
    {
        return ViewCatalog::query()
            ->whereNull('view_catalogs.deleted_at')
            ->when($published !== null, fn ($q) => $q->where('view_catalogs.is_published', $published ? 1 : 0))
            ->when($batchId !== null, fn ($q) => $q->where('view_catalogs.catalog_import_id', $batchId));
    }

    /**
     * The rows a list shows, before paging: its scope + the toolbar filters + the search box.
     * Shared by the table and by the bulk actions' "select all matching", so both act on the
     * exact same set of records.
     */
    private function catalogListQuery(Request $request, ?bool $published, ?int $batchId, string $searchValue)
    {
        $query = $this->catalogListBase($published, $batchId);
        $this->applyCatalogListFilters($query, $request);
        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $like = '%' . $searchValue . '%';
                $q->where('view_catalogs.material_type', 'like', $like)
                    ->orWhere('view_catalogs.catalog_id', 'like', $like)
                    ->orWhere('view_catalogs.accession_number', 'like', $like)
                    ->orWhere('view_catalogs.title_statement', 'like', $like)
                    ->orWhere('view_catalogs.isbn', 'like', $like)
                    ->orWhere('view_catalogs.issn', 'like', $like)
                    ->orWhere('view_catalogs.pub_year', 'like', $like)
                    ->orWhere('view_catalogs.publisher', 'like', $like)
                    ->orWhere('view_catalogs.joint_authors', 'like', $like)
                    ->orWhere(DB::raw("DATE_FORMAT(view_catalogs.created_at, '%m/%d/%y')"), 'like', $like);
            });
        }
        return $query;
    }

    /**
     * The catalog ids a bulk action applies to. The client sends which list it is on
     * (published / onhold / batch) and either explicit `ids[]` or scope=all (= every record
     * matching the current filters + search, minus `excluded[]`). Explicit ids are re-checked
     * against the list's scope, so a stale selection can't reach records outside that list.
     */
    private function bulkSelectionIds(Request $request): \Illuminate\Support\Collection
    {
        $request->validate([
            'list'       => 'required|in:published,onhold,batch',
            'scope'      => 'required|in:ids,all',
            'batch_id'   => 'required_if:list,batch|integer|min:1',
            'ids'        => 'required_if:scope,ids|array|max:20000',
            'ids.*'      => 'integer|min:1',
            'excluded'   => 'array',
            'excluded.*' => 'integer|min:1',
        ]);

        $list    = $request->input('list');
        $batchId = $list === 'batch' ? (int) $request->input('batch_id') : null;
        $published = ['published' => true, 'onhold' => false, 'batch' => null][$list];

        // Same view permission as the list page itself.
        abort_unless(Auth::user()->can($list === 'batch' ? 'view.catalog.importlist' : 'view.catalog.list'), 403);

        if ($request->input('scope') === 'all') {
            $query = $this->catalogListQuery($request, $published, $batchId, trim((string) $request->input('search', '')));
            $excluded = array_map('intval', (array) $request->input('excluded', []));
            if ($excluded) $query->whereNotIn('view_catalogs.catalog_id', $excluded);
        } else {
            $query = $this->catalogListBase($published, $batchId)
                ->whereIn('view_catalogs.catalog_id', array_map('intval', (array) $request->input('ids', [])));
        }

        return $query->orderBy('view_catalogs.catalog_id')->pluck('view_catalogs.catalog_id')->map(fn ($id) => (int) $id)->unique()->values();
    }

    /**
     * Bulk action on the selected rows of the Record List / On-Hold / batch records tables:
     * publish, hold, delete or re-index for AI. Each action needs the same permission as its
     * single-record counterpart, and keeps Meilisearch, the RAG outbox and the home cache in step.
     */
    public function bulkRecords(Request $request)
    {
        $request->validate(['action' => 'required|in:publish,hold,delete,reindex']);
        $action = $request->input('action');
        $permission = ['publish' => 'publish-hold.catalog', 'hold' => 'publish-hold.catalog',
                       'delete' => 'delete.catalog', 'reindex' => 'access.cataloging'][$action];
        abort_unless(Auth::user()->can($permission), 403);

        $ids     = $this->bulkSelectionIds($request);
        $userId  = Auth::id();
        $changed = 0;

        foreach ($ids->chunk(500) as $chunk) {
            $chunk = $chunk->values()->all();

            if ($action === 'publish' || $action === 'hold') {
                $to = $action === 'publish' ? 1 : 0;
                // Only records whose state actually changes (a batch mixes published and on-hold).
                $target = ViewCatalog::whereIn('catalog_id', $chunk)->where('is_published', '!=', $to)->pluck('catalog_id')->all();
                if (! $target) continue;
                $stamp = ['is_published' => $to, 'updated_at' => now(), 'updated_by' => $userId];
                DB::transaction(function () use ($target, $stamp, $to) {
                    Catalog::whereIn('id', $target)->update($stamp);
                    ViewCatalog::whereIn('catalog_id', $target)->update($stamp);
                    RagSync::enqueueMany($target, $to ? RagSync::UPSERT : RagSync::DELETE, $to ? 'published' : 'unpublished');
                });
                // Query-builder updates bypass Scout: re-index so OPAC search sees the new is_published.
                ViewCatalog::whereIn('catalog_id', $target)->searchable();
                $changed += count($target);
            } elseif ($action === 'delete') {
                // Drop from the search index first (same reason as destroyRecord), then soft-delete.
                ViewCatalog::whereIn('catalog_id', $chunk)->get()->unsearchable();
                $stamp = ['deleted_at' => now(), 'deleted_by' => $userId];
                DB::transaction(function () use ($chunk, $stamp) {
                    Catalog::whereIn('id', $chunk)->update($stamp);
                    ViewCatalog::whereIn('catalog_id', $chunk)->update($stamp);
                    RagSync::enqueueMany($chunk, RagSync::DELETE, 'deleted');
                });
                $changed += count($chunk);
            } else {   // reindex: just queue; the worker gates on is_published as usual
                RagSync::enqueueMany($chunk, RagSync::UPSERT, 'manual');
                $changed += count($chunk);
            }
        }

        if ($action !== 'reindex' && $changed) clearHomeCache();

        return response()->json(['success' => true, 'action' => $action, 'selected' => $ids->count(), 'changed' => $changed]);
    }

    /**
     * Step 1 of exporting the selected rows: resolve the selection (same rules as bulkRecords) and
     * park the ids under a short-lived ticket. The download itself is a plain GET (step 2), because
     * download managers (IDM etc.) and some browsers re-request a download URL with HEAD/GET — a
     * POST-only download then fails with 405 even though the first response was fine.
     */
    public function bulkExportTicket(Request $request)
    {
        $ids = $this->bulkSelectionIds($request)->all();
        abort_if(! $ids, 422, 'No records selected.');

        $token = Str::random(40);
        cache()->put('bulk-export:' . $token, ['user' => Auth::id(), 'ids' => $ids], now()->addMinutes(15));

        return response()->json([
            'count' => count($ids),
            'urls'  => [
                'mrc' => route('records-bulk-export', ['token' => $token, 'format' => 'mrc']),
                'xml' => route('records-bulk-export', ['token' => $token, 'format' => 'xml']),
            ],
        ]);
    }

    /** Step 2: stream the ticket's records as MARC21 or MARCXML. Re-requestable until it expires. */
    public function bulkExport($token, $format, MarcExportService $marc)
    {
        $ticket = cache()->get('bulk-export:' . $token);
        abort_unless($ticket && (int) $ticket['user'] === (int) Auth::id(), 404, 'This export link has expired. Export the selection again.');

        $ids   = $ticket['ids'];
        $build = fn () => Catalog::whereNull('deleted_at')->whereIn('id', $ids)->orderBy('id');

        return $this->streamMarcExport($build, $format, 'catalog-selection-' . count($ids) . '-' . now()->format('Ymd-His'), $marc);
    }

    /** Toolbar filters: status, type, author, year (each also "__none__" = missing) and resources. */
    private function applyCatalogListFilters($query, Request $request): void
    {
        $none = '__none__';

        $status = (string) $request->input('status', '');   // batch page: published / on hold
        if ($status === 'published') {
            $query->where('view_catalogs.is_published', 1);
        } elseif ($status === 'onhold') {
            $query->where('view_catalogs.is_published', 0);
        }

        $type = (string) $request->input('type', '');
        if ($type === $none) {
            $query->whereNull('view_catalogs.material_type_id');
        } elseif (ctype_digit($type)) {
            $query->where('view_catalogs.material_type_id', (int) $type);
        }

        $author = trim((string) $request->input('author', ''));
        if ($author === $none) {
            $query->where(fn ($q) => $q->whereNull('view_catalogs.joint_authors')->orWhere('view_catalogs.joint_authors', ''));
        } elseif ($author !== '') {
            // joint_authors lists names separated by "/" (with or without spaces): match one whole name.
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $author);
            $query->whereRaw(
                "CONCAT('/', REGEXP_REPLACE(COALESCE(view_catalogs.joint_authors, ''), '[[:space:]]*/[[:space:]]*', '/'), '/') LIKE ?",
                ['%/' . $escaped . '/%']
            );
        }

        $year = (string) $request->input('year', '');
        if ($year === $none) {
            $query->where(fn ($q) => $q->whereNull('view_catalogs.pub_year')->orWhere('view_catalogs.pub_year', 'not regexp', '[0-9]{4}'));
        } elseif (preg_match('/^\d{4}$/', $year)) {
            $query->where('view_catalogs.pub_year', 'regexp', '(^|[^0-9])' . $year . '([^0-9]|$)');
        }

        $hasFile  = fn ($q) => $q->select(DB::raw(1))->from('multimedia')->whereColumn('multimedia.catalog_id', 'view_catalogs.catalog_id')->whereNull('multimedia.deleted_at');
        $hasCover = fn ($q) => $q->select(DB::raw(1))->from('frontpages')->whereColumn('frontpages.catalog_id', 'view_catalogs.catalog_id')->whereNull('frontpages.deleted_at');
        switch ((string) $request->input('resource', '')) {
            case 'fulltext-only':      $query->whereExists($hasFile); break;
            case 'coverphoto-only':    $query->whereExists($hasCover); break;
            case 'both':               $query->whereExists($hasFile)->whereExists($hasCover); break;
            case 'without-fulltext':   $query->whereNotExists($hasFile); break;
            case 'without-coverphoto': $query->whereNotExists($hasCover); break;
            case 'without-both':       $query->whereNotExists($hasFile)->whereNotExists($hasCover); break;
            // No 520 summary: the OPAC falls back to a generated meta description (OPAC_SEO_PLAN.md).
            case 'without-summary':
                $query->where(fn ($q) => $q->whereNull('view_catalogs.summary')->orWhere('view_catalogs.summary', ''));
                break;
        }
    }

    /**
     * Filter options built from the list's own records (published or on-hold), with counts:
     * types, individual authors (joint_authors split on "/") and years (any 4-digit year in pub_year).
     */
    private function catalogListFilterOptions(?bool $published, ?int $batchId = null): array
    {
        $rows = ViewCatalog::whereNull('deleted_at')
            ->when($published !== null, fn ($q) => $q->where('is_published', $published ? 1 : 0))
            ->when($batchId !== null, fn ($q) => $q->where('catalog_import_id', $batchId))
            ->get(['material_type_id', 'material_type', 'joint_authors', 'pub_year', 'is_published']);
        $names = DB::table('materials')->pluck('name', 'id');

        $types = []; $authors = []; $years = []; $noType = 0; $noAuthor = 0; $noYear = 0;
        foreach ($rows as $r) {
            if ($r->material_type_id) {
                $id = (int) $r->material_type_id;
                $types[$id] ??= ['id' => $id, 'name' => $names[$id] ?? ($r->material_type ?: 'Unknown'), 'count' => 0];
                $types[$id]['count']++;
            } else {
                $noType++;
            }

            $seen = [];
            foreach (preg_split('#\s*/\s*#u', (string) $r->joint_authors) as $name) {
                $name = trim(preg_replace('/\s+/u', ' ', $name));
                $key = mb_strtolower($name);
                if ($name === '' || isset($seen[$key])) continue;
                $seen[$key] = true;
                $authors[$key] ??= ['name' => $name, 'count' => 0];
                $authors[$key]['count']++;
            }
            if (! $seen) $noAuthor++;

            preg_match_all('/(?<!\d)(1[5-9]\d\d|20\d\d)(?!\d)/', (string) $r->pub_year, $m);
            $ys = array_unique($m[1]);
            foreach ($ys as $y) $years[$y] = ($years[$y] ?? 0) + 1;
            if (! $ys) $noYear++;
        }

        usort($types, fn ($a, $b) => strcasecmp($a['name'], $b['name']));
        $authors = array_values($authors);
        usort($authors, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));
        krsort($years);

        return [
            'total'    => $rows->count(),
            // Only when the list mixes both (a batch): offers the Status filter.
            'statuses' => $published === null
                ? ['published' => $rows->where('is_published', 1)->count(), 'onhold' => $rows->where('is_published', 0)->count()]
                : null,
            'types'    => array_values($types),
            'authors'  => $authors,
            'years'    => array_map(fn ($y, $c) => ['year' => (string) $y, 'count' => $c], array_keys($years), $years),
            'noType'   => $noType,
            'noAuthor' => $noAuthor,
            'noYear'   => $noYear,
        ];
    }

    public function holdAllPublished()
    {
        $user = Auth::user();

        // Record flags, OPAC flags and the RAG outbox change together. All held -> queue for
        // RAG sync to remove them from the index.
        DB::transaction(function () use ($user) {
            // Capture the affected ids BEFORE the flip (they won't match is_published=1 after).
            $affected = Catalog::where('is_published', 1)->lockForUpdate()->pluck('id');

            Catalog::where('is_published', 1)
            ->update([
                'is_published' => 0,
                'updated_at' => now(),
                'updated_by' => $user->id,
            ]);

            ViewCatalog::where('is_published', 1)
            ->update([
                'is_published' => 0,
                'updated_at' => now(),
                'updated_by' => $user->id,
            ]);

            RagSync::enqueueMany($affected, RagSync::DELETE, 'unpublished');
        });

        // Bulk change -> re-index so Meilisearch reflects the new is_published values.
        ViewCatalog::makeAllSearchable();

        return response()->json(['success' => true]);
    }

    public function publishAllOnhold(Request $request)
    {
        $user = Auth::user();

        // Record flags, OPAC flags and the RAG outbox change together. All published -> queue
        // for RAG sync to index them.
        DB::transaction(function () use ($user) {
            // Capture the affected ids BEFORE the flip (they won't match is_published=0 after).
            $affected = Catalog::where('is_published', 0)->lockForUpdate()->pluck('id');

            Catalog::where('is_published', 0)
            ->update([
                'is_published' => 1,
                'updated_at' => now(),
                'updated_by' => $user->id,
            ]);

            ViewCatalog::where('is_published', 0)
            ->update([
                'is_published' => 1,
                'updated_at' => now(),
                'updated_by' => $user->id,
            ]);

            RagSync::enqueueMany($affected, RagSync::UPSERT, 'published');
        });

        // Bulk change -> re-index so Meilisearch reflects the new is_published values.
        ViewCatalog::makeAllSearchable();

        return response()->json(['success' => true]);
    }

    public function publishToOpac(Request $request)
    {
        $user = Auth::user();

        // Record flag, OPAC flag and the RAG outbox change together. Now published -> queue for
        // RAG sync (worker will index it).
        DB::transaction(function () use ($request, $user) {
            Catalog::where('id', $request->catalog_id)
            ->update([
                'is_published' => 1,
                'updated_at' => now(),
                'updated_by' => $user->id,
            ]);

            ViewCatalog::where('catalog_id', $request->catalog_id)
            ->update([
                'is_published' => 1,
                'updated_at' => now(),
                'updated_by' => $user->id,
            ]);

            RagSync::enqueue((int) $request->catalog_id, RagSync::UPSERT, 'published');
        });

        // Query-builder updates bypass Scout's observers, so re-index the record — else
        // Meilisearch keeps the stale is_published and the OPAC search filter hides it.
        optional(ViewCatalog::where('catalog_id', $request->catalog_id)->first())->searchable();

        return response()->json(['success' => true]);
    }

    public function holdViewFromOpac(Request $request)
    {
        $user = Auth::user();

        // Record flag, OPAC flag and the RAG outbox change together. Held -> queue for RAG sync
        // (worker removes it from the index).
        DB::transaction(function () use ($request, $user) {
            Catalog::where('id', $request->catalog_id)
            ->update([
                'is_published' => 0,
                'updated_at' => now(),
                'updated_by' => $user->id,
            ]);

            ViewCatalog::where('catalog_id', $request->catalog_id)
            ->update([
                'is_published' => 0,
                'updated_at' => now(),
                'updated_by' => $user->id,
            ]);

            RagSync::enqueue((int) $request->catalog_id, RagSync::DELETE, 'unpublished');
        });

        // Re-index so Meilisearch reflects is_published=0 (record hidden from OPAC search).
        optional(ViewCatalog::where('catalog_id', $request->catalog_id)->first())->searchable();

        return response()->json(['success' => true]);
    }

    public function togglePublishHoldMaterial(Request $request)
    {
        $user = Auth::user();

        // Record flags, OPAC flags and the RAG outbox change together: the whole batch is queued
        // for RAG sync — publish -> index, hold -> remove.
        DB::transaction(function () use ($request, $user) {
            Catalog::where('catalog_import_id', $request->catalog_import_id)
            ->update([
                'is_published' => $request->action == 1 ? 1 : 0,
                'updated_at' => now(),
                'updated_by' => $user->id,
            ]);

            ViewCatalog::where('catalog_import_id', $request->catalog_import_id)
            ->update([
                'is_published' => $request->action == 1 ? 1 : 0,
                'updated_at' => now(),
                'updated_by' => $user->id,
            ]);

            $batchIds = Catalog::where('catalog_import_id', $request->catalog_import_id)->pluck('id');
            RagSync::enqueueMany(
                $batchIds,
                $request->action == 1 ? RagSync::UPSERT : RagSync::DELETE,
                $request->action == 1 ? 'published' : 'unpublished'
            );
        });

        // Re-index the batch so Meilisearch reflects the new is_published values.
        ViewCatalog::where('catalog_import_id', $request->catalog_import_id)->get()->each(fn($m) => $m->searchable());

        return view('admin.batch-list');
    }

    public function uploadCoverPhoto(Request $request)
    {
        if($request->hasFile('coverImage')) {

            $uploadedFile = $request->file('coverImage');

            // Cover photo limit: JPG/PNG/WEBP image, max 10 MB.
            if(!in_array(strtolower($uploadedFile->getClientOriginalExtension()), ['jpg','jpeg','png','webp']) || $uploadedFile->getSize() > 10 * 1024 * 1024) {
                $record = Catalog::select('catalogs.*')->where('catalogs.id',$request->catalog_id)->first();
                $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
                $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
                return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title, 'uploadStatus' => true, 'uploadError' => 'The cover photo must be a JPG, PNG, or WEBP image no larger than 10 MB.']);
            }

            $fileType = $uploadedFile->getClientOriginalExtension();
            $newFileName = 'cp-'. uniqid() . '.' . $fileType;
            $filePath = 'frontpages/' . $newFileName;

            // Store directly under public/frontpages (where getFrontpageResult serves from).
            ensurePublicDir(public_path('frontpages'));   // link target may not exist yet (fresh checkout)
            $uploadedFile->move(public_path('frontpages'), $newFileName);

            // Also generate a thumbnail copy under public/thumb (same filename).
            makeThumbnail(public_path('frontpages/'.$newFileName), public_path('thumb/'.$newFileName));

            $coverPage = Frontpage::where('catalog_id', $request->catalog_id)->where('deleted_at', null)->first();

            if(is_null($coverPage)) {
                Frontpage::create([
                    'catalog_id' =>  $request->catalog_id,
                    'FrontPageLocation' => $filePath,
                    'FrontPageName' => $newFileName,
                    'FrontPageType' => $fileType
                ]);
            } else {
                Frontpage::where('id', $coverPage->id)
                    ->update([
                        'FrontPageLocation' => $filePath,
                        'FrontPageName' => $newFileName,
                        'FrontPageType' => $fileType,
                        'updated_at' => now(),
                    ]);
            }

            // Bust the cached homepage carousels/shelves (they bake in cover thumbnails) so
            // the new cover shows immediately instead of after the 10-min TTL.
            clearHomeCache();

            $record = Catalog::select('catalogs.*')
            ->where('catalogs.id',$request->catalog_id)
            ->first(); 

            $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
            $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();

        return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title, 'uploadStatus' => true]);

        }
        $record = Catalog::select('catalogs.*')
        ->where('catalogs.id',$request->catalog_id)
        ->first(); 

        $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();

        return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title]);
          
    }

    public function destroyCoverPhoto(Request $request)
    {
        
        $coverPage = Frontpage::where('catalog_id', $request->catalog_id)->whereNull('deleted_at')->first();
        Frontpage::where('id', $coverPage->id)
            ->update([
            'deleted_at' => now()
        ]);

        // Removed cover must drop from the cached homepage carousels/shelves immediately.
        clearHomeCache();

        $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $record = Catalog::select('catalogs.*')
        ->where('catalogs.id',$request->catalog_id)
        ->first(); 

        return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title, 'uploadStatus' => true]);
    }

    public function uploadFullText(Request $request)
    {
        if($request->hasFile('fullText')) {

            $uploadedFile = $request->file('fullText');

            // Full-text upload limit: PDF only, max 100 MB.
            if(strtolower($uploadedFile->getClientOriginalExtension()) !== 'pdf' || $uploadedFile->getSize() > 100 * 1024 * 1024) {
                $record = Catalog::select('catalogs.*')->where('catalogs.id',$request->catalog_id)->first();
                $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
                $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
                return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title, 'uploadStatus' => true, 'uploadError' => 'The full-text file must be a PDF no larger than 100 MB.']);
            }

            $fileType = $uploadedFile->getClientOriginalExtension();
            $newFileName = 'ft-'. uniqid() . '.' . $fileType;
            $filePath = 'pdf/' . $newFileName;

            // Store directly under public/pdf (where DownloadController's public_path() serves from).
            ensurePublicDir(public_path('pdf'));   // link target may not exist yet (fresh checkout)
            $uploadedFile->move(public_path('pdf'), $newFileName);

            $coverPage = Multimedia::where('catalog_id', $request->catalog_id)->where('deleted_at', null)->first();

            if(is_null($coverPage)) {
                Multimedia::create([
                    'catalog_id' =>  $request->catalog_id,
                    'FileLocation' => $filePath,
                    'FileName' => $newFileName,
                    'FileType' => $fileType,
                    'Extension' => ".$fileType"
                ]);
            } else {
                Multimedia::where('id', $coverPage->id)
                    ->update([
                        'FileLocation' => $filePath,
                        'FileName' => $newFileName,
                        'FileType' => $fileType,
                        'Extension' => ".$fileType",
                        'updated_at' => now(),
                    ]);
            }

            // Full-text PDF added/replaced -> queue for RAG sync (worker re-OCRs + re-embeds).
            // Distinct reason so the AI Sync Monitor can flag PDF changes (heavy) vs metadata edits.
            RagSync::enqueue((int) $request->catalog_id, RagSync::UPSERT, is_null($coverPage) ? 'fulltext_added' : 'fulltext_updated');

            $record = Catalog::select('catalogs.*')
                ->where('catalogs.id',$request->catalog_id)
                ->first();

            $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
            $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();

        return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title, 'uploadStatus' => true]);

        }
        $record = Catalog::select('catalogs.*')
            ->where('catalogs.id',$request->catalog_id)
            ->first(); 
        $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        
        return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title]);

    }

    public function destroyFullText(Request $request)
    {
        $ft = Multimedia::where('catalog_id', $request->catalog_id)->whereNull('deleted_at')->first();
        Multimedia::where('id', $ft->id)
            ->update([
            'deleted_at' => now()
        ]);

        // Full text removed -> queue for RAG sync (record reverts to metadata-only).
        RagSync::enqueue((int) $request->catalog_id, RagSync::UPSERT, 'fulltext_removed');
        $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $record = Catalog::select('catalogs.*')
            ->where('catalogs.id',$request->catalog_id)
            ->first(); 

        return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title, 'uploadStatus' => true]);
    }

    public function addCopy(Request $request)
    {

        if(count($request->circulation_number) > 0) {
            for($z=0; $z < count($request->circulation_number); $z++) {
                $checkIfAlreadyAdded = Holdingcopy::where('circulation_number', $request->circulation_number[$z])->first();
                if(is_null($checkIfAlreadyAdded)) {
                
                    Holdingcopy::create([
                        'catalog_id' => $request->catalog_id,
                        'title' => $request->title,
                        'circulation_number' => $request->circulation_number[$z],
                        'source' => $request->source[$z],
                        'status' => $request->status[$z],
                        'location' => $request->location[$z],
                    ]);
                }
            }
            
        }

        $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $record = Catalog::select('catalogs.*')
            ->where('catalogs.id',$request->catalog_id)
            ->first(); 
        $totalCopies = Holdingcopy::where('catalog_id', $request->catalog_id)->count();

        return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title, 'totalCopies' => $totalCopies, 'copyUpdate' => true]);

    }

    public function copyListJson(Request $request)
    {

        $searchValue = is_null($request->search_value) ? "" : $request->search_value; // Search value
        $totalRecords = Holdingcopy::where('copies.catalog_id', $request->catalog_id)->count();

        // dd($totalRecords);
        $totalRecordswithFilter = Holdingcopy::select('copies.*', 'copies_status.name as status_name', 'copies_status.order_no as status_order_no')
        ->join('copies_status', 'copies_status.order_no', '=', 'copies.status')
        ->where(function ($query) use ($searchValue) {
            $query->where('circulation_number', 'like', '%' . $searchValue . '%')
                ->orWhere('copies_status.name', 'like', '%' . $searchValue . '%')
                ->orWhere('location', 'like', '%' . $searchValue . '%')
                ->orWhere('source', 'like', '%' . $searchValue . '%')
                ->orWhere('copies.created_at', 'like', '%' . $searchValue . '%');
        })
        ->where('copies.catalog_id', $request->catalog_id)
        ->count();

        // Fetch records
        $records = Holdingcopy::select('copies.*', 'copies_status.name as status_name', 'copies_status.order_no as status_order_no')
        ->join('copies_status', 'copies_status.order_no', '=', 'copies.status')
        ->where(function ($query) use ($searchValue) {
            $query->where('circulation_number', 'like', '%' . $searchValue . '%')
                ->orWhere('copies_status.name', 'like', '%' . $searchValue . '%')
                ->orWhere('location', 'like', '%' . $searchValue . '%')
                ->orWhere('source', 'like', '%' . $searchValue . '%')
                ->orWhere('copies.created_at', 'like', '%' . $searchValue . '%');
        })
        ->where('copies.catalog_id', $request->catalog_id)
        ->orderBy('copies.id','desc')
        ->skip(0)
        ->take(10)
        ->get();
    
        $data_arr = array();

        foreach($records as $index => $record){
            $data_arr[] = array(
                "id" =>  $record->id,
                "circulation_number" => $record->circulation_number,
                "status_name" => $record->status_name,
                "source" => $record->source,
                "location" => $record->location,
                "created_at" => $record->created_at,
            );
        }

        $response = array(
            "draw" => intval(1),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr
        );
 
        return response()->json($response);


    }

    public function getCopyDetailsJson($id)
    {

        $copy = Holdingcopy::where('id', $id)->first();
        $response = array(
            "copy_id" => $copy->id,
            "circulation_number" => $copy->circulation_number,
            "status" => $copy->status,
            "source" => $copy->source,
            "location" => $copy->location,
         );
 
         return response()->json($response); 
    }

    public function updateCopy(Request $request)
    {
        Holdingcopy::where('id',$request->copy_id)
            ->update([
                'circulation_number' => $request->circulation_number,
                'source' => $request->source,
                'location' => $request->location,
                'status' => $request->status,
            ]);
        $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $record = Catalog::select('catalogs.*')
            ->where('catalogs.id',$request->catalog_id)
            ->first();
        $totalCopies = Holdingcopy::where('catalog_id', $request->catalog_id)->count();

        return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title, 'totalCopies' => $totalCopies, 'copyUpdate' => true]);
    }

    public function destroyCopy($id) 
    {
        $coverPage = Frontpage::where('catalog_id',request('catalog_id'))->whereNull('deleted_at')->first();
        $fullText = Multimedia::where('catalog_id',request('catalog_id'))->whereNull('deleted_at')->first();
        $record = Catalog::select('catalogs.*')
            ->where('catalogs.id',request('catalog_id'))
            ->first();

        try {
            Holdingcopy::destroy($id);
            $totalCopies = Holdingcopy::where('catalog_id', request('catalog_id'))->count();
            return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => request('title'), 'totalCopies' => $totalCopies, 'copyUpdate' => true]);
        } catch (\Exception $e) {
            return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => request('title'), 'totalCopies' => $totalCopies, 'copyUpdate' => true]);
        }

    }

    public function getTaskProgress()
    {
        // Get the current progress from the database
        $task = CatalogImportProgress::where('id', 1)->first(); // Find the appropriate task
        // dd($task);
        if ($task) {
            return response()->json(['progress' => $task->progress, 'total_records' => $task->total_records]);
        }
        
        return response()->json(['progress' => 1, 'total_records' => 1]); // Default to 0 if no task found
    }
}
