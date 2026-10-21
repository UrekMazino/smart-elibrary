<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A batch's subject (what the import is about, e.g. "FNRI nutrition publications 1978-2023").
 *
 * Shown in the Batch Import list and on the batch's page instead of its Material Type: since
 * Batch Import can map each record to its own type, the batch-level type is only the default for
 * unmapped records and no longer describes the batch. Nullable: older batches have none.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_imports', function (Blueprint $table) {
            $table->string('subject', 255)->nullable()->after('no_of_records');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_imports', function (Blueprint $table) {
            $table->dropColumn('subject');
        });
    }
};
