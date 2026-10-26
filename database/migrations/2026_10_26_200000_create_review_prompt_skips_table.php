<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The quick review popup's "Not now": which material a patron asked not to be prompted about again.
 * It used to be kept in the browser's localStorage, where it outlived sign-out and showed the next
 * person on a shared computer what the patron had read (OWASP ASVS 14.3.1, 14.3.3). Kept here, it
 * also holds on every device, and goes with the account or the record when either is deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_prompt_skips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('catalog_id');
            $table->foreign('catalog_id')->references('id')->on('catalogs')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->unique(['user_id', 'catalog_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_prompt_skips');
    }
};
