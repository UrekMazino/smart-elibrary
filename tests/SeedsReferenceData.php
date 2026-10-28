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

    /**
     * The seeded roles and permissions, as a fresh install has them. Migrations create some permissions first, under
     * ids the seed files give to other permissions, so inserting over them left grants pointing at the wrong ones
     * (super admin lost manage.patron.status). The seeder truncates these tables; so does this.
     */
    protected function seedAccess(): void
    {
        DB::table('role_has_permissions')->delete();
        DB::table('model_has_permissions')->delete();
        DB::table('permissions')->delete();
        $this->seedReferenceData('permissions');
        $this->seedReferenceDataOver('roles');
        $this->seedReferenceData('role_has_permissions');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** The reference data a fresh install seeds (DatabaseSeeder), enough for every admin page to open. */
    protected function seedInstallData(): void
    {
        $this->seedReferenceDataOver('circulation_fines', 'circulation_status', 'copies_status', 'imprints',
            'type_of_imprints', 'marc_class_sys', 'materials', 'patrontypes', 'regions', 'settings', 'marctags_loc',
            'marctag_indicators_loc', 'marctag_subfields_loc', 'marc_templates');
    }
}
