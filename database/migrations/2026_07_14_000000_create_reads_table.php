<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-user read log (one row per user + material). Reading a material adds it to
        // the patron's "To Review" list, the same as downloading — a single review of the
        // material then clears it (see the ratings-gated To Review query).
        if (! Schema::hasTable('reads')) {
            Schema::create('reads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('catalog_id');
                $table->timestamps();
                $table->unique(['user_id', 'catalog_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reads');
    }
};
