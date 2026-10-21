<?php

namespace App\Models\OLD_SLIM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holding extends Model
{
    use HasFactory;
    protected $primaryKey = 'TitleStatement';
    protected $connection = "old_slim";
    protected $table = "tblholdings";
}
