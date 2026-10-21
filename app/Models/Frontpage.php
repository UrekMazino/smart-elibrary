<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Frontpage extends Model
{
    use SoftDeletes;

    /** The search index records whether a catalog has a cover: refresh its entry on any change. */
    protected static function booted(): void
    {
        foreach (['saved', 'deleted', 'restored', 'forceDeleted'] as $event) {
            static::$event(fn (self $m) => $m->syncSearchIndex());
        }
    }

    public function syncSearchIndex(): void
    {
        // Generated placeholder covers ("gen-") never count as a cover, so they need no refresh.
        if (str_starts_with((string) $this->FrontPageName, 'gen-')) return;
        if (! $this->catalog_id) return;
        try {
            ViewCatalog::where('catalog_id', $this->catalog_id)->searchable();
        } catch (\Throwable $e) {
            report($e);   // search engine down: the upload itself must still succeed
        }
    }

    protected $fillable = [
        'catalog_id',
        'AcquisitionID',
        'FrontPageName',
        'FrontPageType',
        'FrontPageLocation',
        'FrontPageLocation-back',
        'deleted_at',
    ];
}
