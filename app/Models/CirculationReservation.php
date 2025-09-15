<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CirculationReservation extends Model
{
    use HasFactory;
    protected $table = 'circulation_reservations';

    protected $fillable = [
        'user_id',
        'location_id',
        'requested_by',
        'reservation_code',
        'visit_date',
        'visit_time',
        'title_statement',
    ];
}
