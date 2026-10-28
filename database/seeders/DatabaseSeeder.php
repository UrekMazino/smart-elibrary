<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Constant / reference tables to seed, in FK-safe order. Each is loaded from
     * database/seeders/data/{table}.json (a snapshot of the reference data). Run with:
     *   php artisan migrate:fresh --seed
     *
     * Note: `users` is intentionally limited to the working accounts in the JSON
     * (developer, super admin, one patron), and `model_has_roles` to their role links.
     * Re-snapshot both from the live DB whenever an account you rely on changes —
     * seedTable() TRUNCATES, so anything absent from the JSON is gone after a reseed.
     * Strip the `password` field when you do: seeded accounts get SEED_USER_PASSWORD instead.
     *
     * The seed runs AFTER every migration, so a migration that adds rows to one of these tables
     * (a new permission and its role grants, flags on materials, ...) must add the same rows to the
     * JSON too — otherwise `migrate:fresh --seed` wipes them (e.g. manage.material.types).
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

        if ($table === 'users') {
            $rows = $this->withSeedPassword($rows);
        }

        DB::table($table)->truncate();

        // Chunked insert keeps the large MARC tables (2k+ rows) within packet limits.
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($table)->insert($chunk);
        }

        $this->command->info("  ✓ {$table}: " . count($rows) . " rows");
    }

    /**
     * users.json carries no password hashes (real accounts' hashes in git can be cracked offline).
     * Every seeded account gets SEED_USER_PASSWORD from .env, or a random password printed once —
     * change it after logging in.
     */
    private function withSeedPassword(array $rows): array
    {
        $password = env('SEED_USER_PASSWORD');
        if (! $password) {
            $password = Str::password(16, symbols: false);
            $this->command->warn("  ! users: no SEED_USER_PASSWORD in .env — seeded accounts' password is: {$password}");
        }
        $hash = Hash::make($password);

        return array_map(fn ($row) => array_merge($row, ['password' => $hash, 'remember_token' => null]), $rows);
    }
}
