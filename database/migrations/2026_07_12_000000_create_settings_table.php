<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        DB::table('settings')->updateOrInsert(
            ['key' => 'enable_placeholder_covers'],
            ['value' => '1', 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
