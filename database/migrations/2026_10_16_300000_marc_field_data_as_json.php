<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * catalogs.field_data, the MARC record as a JSON document, becomes MySQL's JSON type (R14), so the
 * database rejects a malformed record instead of storing it.
 *
 * MySQL stores JSON in a binary form and returns it re-serialised: the keys inside each object come
 * back in its own order (shortest first) and spaces follow ':' and ','. Arrays keep their order, so
 * the MARC fields, subfields and indicators stay in record order; the code reads the document by
 * key name, so nothing that uses it changes. Generated columns over MARC paths are not added: the
 * search projection (view_catalogs) already serves every query.
 *
 * An empty string means "no record yet" and becomes NULL. Any other value that is not valid JSON
 * stops the migration before the column is changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $bad = DB::table('catalogs')->whereNotNull('field_data')->where('field_data', '<>', '')
            ->whereRaw('JSON_VALID(field_data) = 0')->pluck('id');
        if ($bad->isNotEmpty()) {
            throw new RuntimeException('Not changed: these catalogs hold field_data that is not valid JSON: '
                . $bad->take(20)->implode(', ') . ($bad->count() > 20 ? ' (and ' . ($bad->count() - 20) . ' more)' : '')
                . '. Correct those records, then migrate again.');
        }

        DB::table('catalogs')->where('field_data', '')->update(['field_data' => null]);
        DB::statement('ALTER TABLE `catalogs` MODIFY `field_data` JSON NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `catalogs` MODIFY `field_data` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');
    }
};
