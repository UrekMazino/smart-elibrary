<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One record of a batch import that had problems: skipped (not imported) or imported and flagged
 * for review. See the create_catalog_import_issues_table migration and CatalogRecordCheck.
 */
class CatalogImportIssue extends Model
{
    protected $fillable = [
        'catalog_import_id', 'catalog_id', 'record_no', 'control_number', 'title',
        'outcome', 'problems', 'raw_marc', 'reviewed_at', 'reviewed_by',
    ];

    protected $casts = [
        'problems'    => 'array',
        'reviewed_at' => 'datetime',
    ];

    // The original MARC bytes of a skipped record: downloaded, never serialised.
    protected $hidden = ['raw_marc'];

    public function isOpen(): bool
    {
        return $this->outcome === 'imported' && $this->reviewed_at === null;
    }
}
