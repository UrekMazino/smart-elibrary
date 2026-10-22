<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/** Loads reference tables (statuses, MARC 21 tags...) from the seed data for a test. */
trait SeedsReferenceData
{
    protected function seedReferenceData(string ...$tables): void
    {
        foreach ($tables as $table) {
            $rows = json_decode(file_get_contents(database_path("seeders/data/$table.json")), true);
            foreach (array_chunk($rows, 500) as $chunk) DB::table($table)->insert($chunk);
        }
    }

    /** Like seedReferenceData, but keeps the rows a migration already added. */
    protected function seedReferenceDataOver(string ...$tables): void
    {
        foreach ($tables as $table) {
            $rows = json_decode(file_get_contents(database_path("seeders/data/$table.json")), true);
            foreach (array_chunk($rows, 500) as $chunk) DB::table($table)->insertOrIgnore($chunk);
        }
    }

    /** The seeded roles and permissions; migrations already add some of them. */
    protected function seedAccess(): void
    {
        $this->seedReferenceDataOver('permissions', 'roles', 'role_has_permissions');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** The reference data a fresh install seeds (DatabaseSeeder), enough for every admin page to open. */
    protected function seedInstallData(): void
    {
        $this->seedReferenceDataOver('circulation_fines', 'circulation_status', 'consortias', 'copies_status', 'imprints',
            'type_of_imprints', 'marc_class_sys', 'materials', 'patrontypes', 'regions', 'settings', 'marctags_loc',
            'marctag_indicators_loc', 'marctag_subfields_loc', 'marc_templates');
    }
}
