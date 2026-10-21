<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Circulation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'copy_id',
        'catalog_id',
        'name',
        'reservation_code',
        'transaction_code',
        'status',
        'due_date',
        'overdue_fine_rate',
        'user_library_code',
        'circulation_number',
        'approved_at',
        'approved_by',
        'declined_at',
        'declined_by',
        'released_at',
        'released_by',
        'received_at',
        'received_by',
    ];
}
