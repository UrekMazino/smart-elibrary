<?php

namespace App\Models;

use App\Services\SearchIndex;
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
        // Sent after the change commits; a search engine that is down never fails the upload.
        SearchIndex::refresh($this->catalog_id);
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
