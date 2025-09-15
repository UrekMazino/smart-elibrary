<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogImportProgress extends Model
{
    use HasFactory;

    protected $table = 'catalog_import_progress';

    protected $fillable = [
        'progress',
        'total_records',
    ];
}
