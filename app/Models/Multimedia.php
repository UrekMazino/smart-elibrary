<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Multimedia extends Model
{
    use SoftDeletes;

    /** The search index records whether a catalog has full text: refresh its entry on any change. */
    protected static function booted(): void
    {
        foreach (['saved', 'deleted', 'restored', 'forceDeleted'] as $event) {
            static::$event(fn (self $m) => $m->syncSearchIndex());
        }
    }

    public function syncSearchIndex(): void
    {
        if (! $this->catalog_id) return;
        try {
            ViewCatalog::where('catalog_id', $this->catalog_id)->searchable();
        } catch (\Throwable $e) {
            report($e);   // search engine down: the upload itself must still succeed
        }
    }

    protected $fillable = [
        'catalog_id',
        'FileName',
        'FileLocation',
        'FileType',
        'Extension',
        'Permission',
        'Description',
        'deleted_at',
    ];
}
