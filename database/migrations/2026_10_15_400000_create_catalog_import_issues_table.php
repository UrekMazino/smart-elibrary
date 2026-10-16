<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The batch import report: one row per record of a MARC file that had a problem.
 *
 *  - outcome 'skipped'  not imported: unreadable, no title, or only a title (see
 *                       CatalogRecordCheck). The original MARC is kept in raw_marc so staff can
 *                       download the skipped records, fix them in the source and import them again.
 *  - outcome 'imported' imported but flagged for review (no author, no year, possible duplicate...).
 *                       reviewed_at is set when staff mark it reviewed, or when an edit clears
 *                       every problem.
 *
 * problems is a JSON list of {code, level (error | warning), message}.
 * catalog_imports gains the uploaded file's name and the imported / skipped counts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_import_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('catalog_import_id');
            $table->unsignedInteger('catalog_id')->nullable();     // null when the record was skipped
            $table->unsignedInteger('record_no');                   // position in the file, from 1
            $table->string('control_number', 50)->nullable();      // MARC 001, to find it in the source system
            $table->string('title', 500)->nullable();
            $table->enum('outcome', ['imported', 'skipped']);
            $table->json('problems');
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();

            $table->unique(['catalog_import_id', 'record_no']);
            $table->index('catalog_id');
            $table->foreign('catalog_import_id')->references('id')->on('catalog_imports')->cascadeOnDelete();
            $table->foreign('catalog_id')->references('id')->on('catalogs')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        // A MARC record can be up to 99,999 bytes, more than a BLOB holds.
        DB::statement('ALTER TABLE `catalog_import_issues` ADD `raw_marc` MEDIUMBLOB NULL AFTER `problems`');

        Schema::table('catalog_imports', function (Blueprint $table) {
            $table->string('file_name')->nullable()->after('subject');
            $table->unsignedInteger('imported_count')->nullable()->after('no_of_records');
            $table->unsignedInteger('skipped_count')->nullable()->after('imported_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_import_issues');

        Schema::table('catalog_imports', fn (Blueprint $table) => $table->dropColumn(['file_name', 'imported_count', 'skipped_count']));
    }
};
