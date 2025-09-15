<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Multimedia extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'catalog_id',
        'FileName',
        'FileLocation',
        'FileType',
        'Extension',
        'Permission',
        'Description',
        'deleted_at',
    ];
}
