<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One pending/processed item in the RAG catalog-sync outbox (table: rag_sync_queue).
 * See RAG_CATALOG_SYNC_PLAN.md. Written by App\Services\RagSync, drained by the Python worker.
 */
class RagSyncItem extends Model
{
    protected $table = 'rag_sync_queue';

    public $timestamps = false;   // created_at + processed_at are managed explicitly

    protected $fillable = [
        'catalog_id', 'action', 'reason', 'content_hash', 'attempts', 'error', 'created_at', 'processed_at',
    ];

    protected $casts = [
        'catalog_id'   => 'integer',
        'attempts'     => 'integer',
        'created_at'   => 'datetime',
        'processed_at' => 'datetime',
    ];
}
