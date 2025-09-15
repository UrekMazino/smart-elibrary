<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holdingcopy extends Model
{
    use HasFactory;
    protected $table = 'copies';

    protected $fillable = [
        'catalog_id',
        'title',
        'circulation_number',
        'acquisition_mode',
        'status',
        'source',
        'location',
        'series_statement_id',
        'presented',
    ];
}
