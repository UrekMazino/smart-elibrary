<?php

namespace App\Models\OLD_SLIM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;
    protected $primaryKey = 'UserID';
    protected $connection = "old_slim";
    protected $table = "users";
}
