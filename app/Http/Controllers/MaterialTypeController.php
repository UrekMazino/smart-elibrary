<?php

namespace App\Http\Controllers;

use App\Models\MarcTemplate;
use App\Models\Material;
use App\Models\MaterialTypeMap;
use App\Services\RagSync;
use App\Services\SearchIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Administration → Utilities.
 *
 * The hub lists the utilities this admin can use: Material Types (below), MARC Templates, the AI
 * Sync Monitor and Theme (their own controllers; the sidebar's Utilities link checks the same
 * permissions). Material
 * types are referenced by records (catalogs + the OPAC's view_catalogs, which also stores the
 * NAME), batches and mapping presets — so:
 *   - a type in use can't be deleted: merge it into another type (moves everything) or hide it;
 *   - a rename is pushed to every record's stored name + the search index;
 *   - MARC record-type entries (LDR/06 codes) are protected: never deleted; "Move records to…"
 *     re-types their records but keeps the entry.
 */
class MaterialTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /** Utilities hub: one card per utility the user may open. */
    public function utilities()
    {
        $user = auth()->user();
        $cards = [];
        if ($user->can('manage.material.types')) {
            $cards[] = [
                'title' => 'Material Types',
                'icon'  => 'layers',
                'url'   => route('utilities.material-types'),
                'text'  => 'Add, rename, hide, merge or delete the Material Types used by records, Batch Import and the OPAC filters.',
                'meta'  => Material::where('is_marc_type', false)->count() . ' types · ' . Material::where('is_active', true)->count() . ' shown in lists',
            ];
        }
        if ($user->can('view.config.template')) {
            $cards[] = [
                'title' => 'MARC Templates',
                'icon'  => 'layout',
                'url'   => route('marc-templates-index'),
                'text'  => 'The field templates Create New Record starts from: which MARC tags and subfields a new record shows.',
                'meta'  => MarcTemplate::count() . ' templates',
            ];
        }
        if ($user->can('access.ai.sync.monitor')) {
            $open = DB::table('rag_sync_queue')->whereNull('processed_at');
            $cards[] = [
                'title' => 'AI Sync Monitor',
                'icon'  => 'cpu',
                'url'   => route('ai-sync-monitor'),
                'text'  => 'Every catalog change and whether the AI search has picked it up; re-sync a record or run the sync now.',
                'meta'  => (clone $open)->where('attempts', '<', RagSync::MAX_ATTEMPTS)->count() . ' pending · '
                         . (clone $open)->where('attempts', '>=', RagSync::MAX_ATTEMPTS)->count() . ' failed',
            ];
        }

        if ($user->can('manage.theme')) {
            $cards[] = [
                'title' => 'Theme',
                'icon'  => 'droplet',
                'url'   => route('utilities.theme'),
                'text'  => 'The appearance every staff member starts with: colours, background, contrast, status colours, text size, motion, font. Each can still choose their own.',
                'meta'  => \App\Services\Appearance::describe(\App\Services\Appearance::siteDefault()),
            ];
        }

        return view('admin.hub', [
            'title' => 'Utilities',
            'intro' => "Tools for the library's reference data, the cataloguing templates, the AI search and the admin's appearance. You see the ones your role may use.",
            'cards' => $cards,
        ]);
    }

    public function index()
    {
        $usage = $this->usageCounts();
        $types = Material::orderBy('is_marc_type')->orderBy('name')->get()->map(function ($m) use ($usage) {
            $m->records = $usage['records'][$m->id] ?? 0;
            $m->in_use  = $this->totalUsage($m->id, $usage);
            return $m;
        });

        return view('admin.utilities.material-types', [
            'library' => $types->where('is_marc_type', false)->values(),
            'marc'    => $types->where('is_marc_type', true)->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100', Rule::unique('materials', 'name')],
            'tooltip'   => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ], ['name.unique' => 'A Material Type with this name already exists.']);

        $m = Material::create([
            'name'         => trim($data['name']),
            'code'         => '#',                // library type ('#' = not a MARC LDR/06 code)
            'tooltip'      => $data['tooltip'] ?? null,
            'is_active'    => $request->boolean('is_active', true),
            'is_marc_type' => false,
            'created_by'   => Auth::id(),
            'updated_by'   => Auth::id(),
        ]);

        return response()->json(['message' => '“' . $m->name . '” added.']);
    }

    public function update(Request $request, $id)
    {
        $m = Material::findOrFail($id);
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100', Rule::unique('materials', 'name')->ignore($m->id)],
            'tooltip' => ['nullable', 'string', 'max:500'],
        ], ['name.unique' => 'A Material Type with this name already exists.']);

        $oldName = $m->name;
        $m->fill(['name' => trim($data['name']), 'tooltip' => $data['tooltip'] ?? null, 'updated_by' => Auth::id()])->save();

        $touched = 0;
        if ($m->name !== $oldName) {
            // view_catalogs stores the name (OPAC, search, citations): push the rename everywhere.
            $touched = DB::table('view_catalogs')->where('material_type_id', $m->id)->update(['material_type' => $m->name]);
            $this->resync($m->id);
        }

        return response()->json(['message' => '“' . $m->name . '” saved' . ($touched ? " — {$touched} record(s) updated." : '.')]);
    }

    /** Show / hide in pickers and filters. Records keep their type either way. */
    public function toggle($id)
    {
        $m = Material::findOrFail($id);
        $m->forceFill(['is_active' => ! $m->is_active, 'updated_by' => Auth::id()])->save();
        clearHomeCache();

        return response()->json([
            'is_active' => $m->is_active,
            'message'   => '“' . $m->name . '” is now ' . ($m->is_active ? 'shown in lists.' : 'hidden from lists (records keep it).'),
        ]);
    }

    /**
     * Move everything that uses $id to the target type, then delete $id. A MARC record type is
     * only re-typed: its records move, but the entry stays (the importer's LDR/06 fallback).
     */
    public function merge(Request $request, $id)
    {
        $source = Material::findOrFail($id);
        $keepSource = $source->is_marc_type;
        $data = $request->validate(['target_id' => ['required', 'integer', 'exists:materials,id', Rule::notIn([$source->id])]],
            ['target_id.not_in' => 'Pick a different Material Type to merge into.']);
        $target = Material::findOrFail($data['target_id']);

        $moved = DB::transaction(function () use ($source, $target, $keepSource) {
            $moved = DB::table('catalogs')->where('material_type_id', $source->id)->update(['material_type_id' => $target->id]);
            DB::table('view_catalogs')->where('material_type_id', $source->id)
                ->update(['material_type_id' => $target->id, 'material_type' => $target->name]);
            DB::table('catalog_imports')->where('material_type_id', $source->id)->update(['material_type_id' => $target->id]);

            // Mapping presets: their default and every value mapped to the source.
            foreach (MaterialTypeMap::all() as $preset) {
                $map = collect($preset->mappings)->map(fn ($mid) => (int) $mid === $source->id ? $target->id : (int) $mid)->all();
                $preset->mappings = $map;
                if ((int) $preset->default_material_type_id === $source->id) $preset->default_material_type_id = $target->id;
                if ($preset->isDirty()) $preset->save();
            }

            if (! $keepSource) $source->delete();
            return $moved;
        });

        $this->resync($target->id);

        return response()->json(['message' => $keepSource
            ? $moved . ' record(s) moved from “' . $source->name . '” to “' . $target->name . '”.'
            : '“' . $source->name . '” merged into “' . $target->name . '” — ' . $moved . ' record(s) moved.']);
    }

    public function destroy($id)
    {
        $m = Material::findOrFail($id);
        abort_if($m->is_marc_type, 422, 'MARC record types can’t be deleted: the importer uses them for LDR/06. Hide it instead.');

        $inUse = $this->totalUsage($m->id, $this->usageCounts());
        if ($inUse > 0) {
            return response()->json(['message' => '“' . $m->name . '” is still used (' . $inUse . ' record/batch/preset reference(s)). Merge it into another type, or hide it.'], 422);
        }

        $m->delete();
        clearHomeCache();

        return response()->json(['message' => '“' . $m->name . '” deleted.']);
    }

    /**
     * Re-index the records of a type in OPAC search and refresh cached OPAC pages. The AI index is
     * left alone: it never reads material types, and queuing the type's records re-embedded every
     * one of them (about 40 minutes per 2,000 records) for unchanged text.
     */
    private function resync(int $materialId): void
    {
        SearchIndex::refresh(DB::table('view_catalogs')->where('material_type_id', $materialId)->pluck('catalog_id'));
        clearHomeCache();
    }

    /** References per material id, across every table that stores one. */
    private function usageCounts(): array
    {
        $count = fn ($table) => DB::table($table)->select('material_type_id', DB::raw('count(*) as n'))
            ->whereNotNull('material_type_id')->groupBy('material_type_id')->pluck('n', 'material_type_id')->all();

        $presets = [];
        foreach (MaterialTypeMap::all() as $p) {
            foreach (array_unique(array_merge(array_values((array) $p->mappings), [$p->default_material_type_id])) as $mid) {
                if ($mid) $presets[(int) $mid] = ($presets[(int) $mid] ?? 0) + 1;
            }
        }

        return [
            'records'  => $count('catalogs'),
            'opac'     => $count('view_catalogs'),
            'batches'  => $count('catalog_imports'),
            'presets'  => $presets,
        ];
    }

    private function totalUsage(int $id, array $usage): int
    {
        return array_sum(array_map(fn ($set) => (int) ($set[$id] ?? 0), $usage));
    }
}
