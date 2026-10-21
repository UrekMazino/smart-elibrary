<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Widen the stored record titles to TEXT.
 *
 * Titles are now the FULL MARC 245 (all subfields: title, subtitle, parts, statement of
 * responsibility — see marcTitle()), which routinely runs past 255 characters (e.g. seminar
 * series with two corporate bodies in $c). The server runs without strict SQL mode, so
 * varchar(255) / varchar(150) silently cut those titles mid-word. None of these columns is
 * indexed, so TEXT is a safe drop-in.
 */
return new class extends Migration
{
    public function up(): void
    {
        // No doctrine/dbal dependency — change the column type with raw DDL.
        DB::statement('ALTER TABLE `view_catalogs` MODIFY `title_statement` TEXT NULL');
        DB::statement('ALTER TABLE `copies` MODIFY `title` TEXT NULL');
        DB::statement('ALTER TABLE `circulation_reservations` MODIFY `title_statement` TEXT NULL');
    }

    public function down(): void
    {
        // Note: titles longer than the old limits would be cut again.
        DB::statement('ALTER TABLE `view_catalogs` MODIFY `title_statement` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `copies` MODIFY `title` VARCHAR(150) NULL');
        DB::statement('ALTER TABLE `circulation_reservations` MODIFY `title_statement` VARCHAR(255) NULL');
    }
};
