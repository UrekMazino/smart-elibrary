<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarcTemplate extends Model
{
    use HasFactory;
    protected $table = 'marc_templates';

    protected $fillable = [
        'name',
        'fields',
    ];
}

