<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CirculationFineHistory extends Model
{
    use HasFactory;

    protected $table = 'circulation_fine_history';

    protected $fillable = [
        'transaction_code',
        'overdue_fine_amount',
        'overdue_total_hours',
        'other_fine_amount',
        'other_fine_description',
        'due_date',
        'overdue_fine_rate',
        'fine_note',
    ];
}
