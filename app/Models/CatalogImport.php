<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogImport extends Model
{
    use HasFactory;

    protected $table = 'catalog_imports';

    protected $fillable = [
        'no_of_records',
        'subject',          // what the batch is about (shown in the Batch Import list)
        'file_name',        // the uploaded file's name (import report)
        'imported_count',   // records imported / skipped (import report; null = before reports)
        'skipped_count',
        'status',
        'is_aanr',
        'material_type_id',
        'material_map',     // the mapping used for per-record material types (audit)
        'comment',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
    ];

    protected $casts = [
        'material_map' => 'array',
    ];
}
