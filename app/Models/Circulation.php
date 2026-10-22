<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Circulation extends Model
{
    use HasFactory;

    /**
     * The statuses the admin Transactions page (/admin/reservations) lists: For Approval, Approved,
     * Open (checked out).
     * Its message badges count only these; a declined or closed reservation's conversation is
     * reached from Inquiries.
     */
    public const ON_RESERVATIONS_PAGE = [1, 2, 3];

    protected $fillable = [
        'user_id',
        'copy_id',
        'catalog_id',
        'name',
        'reservation_code',
        'transaction_code',
        'status',
        'patron_seen_status',   // the status as the patron last saw it (Transactions badge)
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
