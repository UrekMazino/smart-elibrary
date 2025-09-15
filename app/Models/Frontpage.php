<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Frontpage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'catalog_id',
        'AcquisitionID',
        'FrontPageName',
        'FrontPageType',
        'FrontPageLocation',
        'FrontPageLocation-back',
        'deleted_at',
    ];
}
