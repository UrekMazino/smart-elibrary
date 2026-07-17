<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Widen accession_number from varchar(50) to varchar(255) on catalogs and view_catalogs.
 *
 * The corpus import stores the RAG join key (the document "stem", a filename) in
 * accession_number. Stems run up to 155 chars, so varchar(50) silently truncated them —
 * which (a) broke the join back to the LanceDB index (chunks are keyed by the FULL stem),
 * and (b) made distinct documents sharing a 50-char prefix collide on the same
 * accession_number, so they looked like duplicate records.
 */
return new class extends Migration
{
    public function up(): void
    {
        // No doctrine/dbal dependency — change the column type with raw DDL.
        DB::statement('ALTER TABLE `catalogs` MODIFY `accession_number` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `view_catalogs` MODIFY `accession_number` VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `catalogs` MODIFY `accession_number` VARCHAR(50) NULL');
        DB::statement('ALTER TABLE `view_catalogs` MODIFY `accession_number` VARCHAR(50) NULL');
    }
};
