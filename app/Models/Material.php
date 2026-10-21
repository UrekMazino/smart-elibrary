<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A Material Type (Books, Serials, Thesis/Dissertations, ...). Managed in
 * Administration → Utilities → Material Types.
 *
 * is_marc_type rows are MARC "type of record" (LDR/06) codes kept for the importer's fallback;
 * is_active controls whether a type is offered in pickers and filters.
 */
class Material extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'tooltip', 'is_active', 'is_marc_type', 'created_by', 'updated_by'];

    protected $casts = [
        'is_active'    => 'boolean',
        'is_marc_type' => 'boolean',
    ];

    /**
     * Types to offer in a Material Type picker: the active ones, plus $includeId (a record's
     * current type) even if hidden, so editing never silently swaps it.
     */
    public static function forPicker($includeId = null)
    {
        return static::query()
            ->where(fn ($q) => $q->where('is_active', true)->when($includeId, fn ($q) => $q->orWhere('id', $includeId)))
            ->orderBy('is_marc_type')
            ->orderBy('name')
            ->get();
    }
}
