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
        'status',
        'is_aanr',
        'material_type_id',
        'comment',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
    ];
}
