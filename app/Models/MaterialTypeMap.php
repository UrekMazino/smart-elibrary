<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A saved Batch Import material-type mapping preset (see MarcMaterialMapper). */
class MaterialTypeMap extends Model
{
    protected $table = 'material_type_maps';

    protected $fillable = [
        'name',
        'source_field',
        'mappings',
        'default_material_type_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'mappings' => 'array',
    ];
}
