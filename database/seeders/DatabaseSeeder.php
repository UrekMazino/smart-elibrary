<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Constant / reference tables to seed, in FK-safe order. Each is loaded from
     * database/seeders/data/{table}.json (a snapshot of the reference data). Run with:
     *   php artisan migrate:fresh --seed
     *
     * Note: `users` is intentionally limited to the two admin/patron accounts in the JSON,
     * and `model_has_roles` to their role links.
     */
    private array $tables = [
        // Access control (roles/permissions before the pivots that reference them)
        'permissions',
        'roles',
        'role_has_permissions',
        // Accounts + their role links
        'users',
        'model_has_roles',
        // Library reference data
        'circulation_fines',
        'circulation_status',
        'consortias',
        'copies_status',
        'imprints',
        'type_of_imprints',
        'marc_class_sys',
        'materials',
        'patrontypes',
        'regions',
        'settings',
        // MARC reference data
        'marctags_loc',
        'marctag_indicators_loc',
        'marctag_subfields_loc',
        'marc_templates',
    ];

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($this->tables as $table) {
            $this->seedTable($table);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function seedTable(string $table): void
    {
        $path = database_path("seeders/data/{$table}.json");

        if (! is_file($path)) {
            $this->command->warn("  ~ {$table}: no data file, skipped");
            return;
        }

        if (! Schema::hasTable($table)) {
            $this->command->warn("  ~ {$table}: table missing (run migrations first), skipped");
            return;
        }

        $rows = json_decode(file_get_contents($path), true) ?: [];

        DB::table($table)->truncate();

        // Chunked insert keeps the large MARC tables (2k+ rows) within packet limits.
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($table)->insert($chunk);
        }

        $this->command->info("  ✓ {$table}: " . count($rows) . " rows");
    }
}
