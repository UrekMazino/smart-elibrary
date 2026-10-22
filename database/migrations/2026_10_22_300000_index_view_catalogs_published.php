<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The sidebar's On-Hold badge counts unpublished, undeleted records on every admin page
 * (getOnHoldCount), and the Record List / On-Hold tables start from the same condition; without an
 * index each count reads the whole table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('view_catalogs', function (Blueprint $table) {
            $table->index(['is_published', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('view_catalogs', function (Blueprint $table) {
            $table->dropIndex(['is_published', 'deleted_at']);
        });
    }
};
