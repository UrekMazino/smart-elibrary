<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Staff can turn the generated placeholder cover off for one record (record page, Resources tab).
 * catalogs.cover_placeholder = 1 (the default) shows a placeholder whenever the record has no real
 * cover; 0 shows no cover until one is uploaded. The generators (new records, batch and corpus
 * imports, `covers:placeholders`) skip records set to 0, so turning it off sticks. The global
 * switch in Settings (enable_placeholder_covers) still hides every placeholder.
 *
 * A record whose placeholder was removed earlier (a soft-deleted "gen-" cover and no active cover)
 * starts at 0, so the change keeps what staff did.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalogs', function (Blueprint $table) {
            $table->boolean('cover_placeholder')->default(true)->after('accession_number')
                ->comment('1: show a generated cover while no real cover is attached; 0: staff turned it off');
        });

        DB::statement("UPDATE `catalogs` c SET c.`cover_placeholder` = 0
            WHERE EXISTS (SELECT 1 FROM `frontpages` f WHERE f.`catalog_id` = c.`id` AND f.`FrontPageName` LIKE 'gen-%' AND f.`deleted_at` IS NOT NULL)
              AND NOT EXISTS (SELECT 1 FROM `frontpages` g WHERE g.`catalog_id` = c.`id` AND g.`deleted_at` IS NULL)");
    }

    public function down(): void
    {
        Schema::table('catalogs', function (Blueprint $table) {
            $table->dropColumn('cover_placeholder');
        });
    }
};
