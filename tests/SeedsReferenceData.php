<?php

namespace Tests;

use Illuminate\Support\Facades\DB;

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
}
