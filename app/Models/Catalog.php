<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Catalog extends Model
{
    use HasFactory;
    protected $fillable = [
        'material_type_id',
        'is_aanr',
        'is_published',
        'marc_class_sys_id',
        'catalog_import_id',
        'accession_number',
        'field_data',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at',
        'deleted_by',
    ];
}
