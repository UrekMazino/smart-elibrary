<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Outbox for RAG catalog sync (see RAG_CATALOG_SYNC_PLAN.md). Laravel enqueues a row whenever a
 * material is created/edited/deleted/imported/(un)published; a Python worker (Project Y, WSL2) polls
 * unprocessed rows and upserts/deletes that catalog's chunks in LanceDB. Deliberately NOT the Laravel
 * `jobs` table (that is consumed by PHP workers) — this is the cross-language contract, in the shared
 * MySQL, keyed by catalog_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rag_sync_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('catalog_id')->index();
            $table->string('action', 16);                 // upsert | delete
            $table->string('reason', 32);                 // created|updated|deleted|imported|published|unpublished|manual
            $table->string('content_hash', 64)->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('processed_at')->nullable()->index();   // NULL = pending (worker polls this)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rag_sync_queue');
    }
};
