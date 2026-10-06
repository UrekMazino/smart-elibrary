<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off repair: records saved through Create / Edit / Batch Import before the
 * CatalogingController::resolveMaterial() fix stored the LDR/06 "type of record" (e.g.
 * "Language material") in catalogs.material_type_id, while view_catalogs (what the OPAC
 * shows) kept the Material Type the cataloger picked (e.g. "Thesis/Dissertations").
 * Record History, Reports and circulation read catalogs, so they showed the wrong type.
 *
 * Copies view_catalogs.material_type_id onto catalogs where they differ. Dry run by default.
 */
class SyncCatalogMaterialTypes extends Command
{
    protected $signature = 'catalog:sync-material-types
                            {--apply : Write the changes (without it, only reports what would change)}';

    protected $description = 'Make catalogs.material_type_id match the Material Type the OPAC shows (view_catalogs)';

    public function handle()
    {
        $mismatched = fn () => DB::table('catalogs')
            ->join('view_catalogs', 'view_catalogs.catalog_id', '=', 'catalogs.id')
            ->whereNotNull('view_catalogs.material_type_id')
            ->whereColumn('catalogs.material_type_id', '!=', 'view_catalogs.material_type_id');

        $total = $mismatched()->count();
        if ($total === 0) {
            $this->info('Every record already has the same Material Type in both places — nothing to do.');
            return self::SUCCESS;
        }

        $names = DB::table('materials')->pluck('name', 'id');
        $breakdown = $mismatched()
            ->select('catalogs.material_type_id as from_id', 'view_catalogs.material_type_id as to_id', DB::raw('count(*) as n'))
            ->groupBy('catalogs.material_type_id', 'view_catalogs.material_type_id')
            ->orderByDesc('n')
            ->get();

        $this->table(['Record History shows', 'OPAC shows (will become)', 'Records'], $breakdown->map(fn ($r) => [
            $names[$r->from_id] ?? "#{$r->from_id}",
            $names[$r->to_id] ?? "#{$r->to_id}",
            $r->n,
        ])->all());

        if (! $this->option('apply')) {
            $this->warn("Dry run: {$total} record(s) would be updated. Re-run with --apply to write.");
            return self::SUCCESS;
        }

        $updated = $mismatched()->update(['catalogs.material_type_id' => DB::raw('view_catalogs.material_type_id')]);
        $this->info("Updated {$updated} record(s).");

        return self::SUCCESS;
    }
}
