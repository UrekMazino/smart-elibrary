<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A record a signed-in patron saved from the search results (their "Saved" list). */
class SavedRecord extends Model
{
    protected $fillable = ['user_id', 'catalog_id'];

    /** @return int[] catalog ids saved by the user, newest first */
    public static function idsFor(?int $userId): array
    {
        return $userId ? static::where('user_id', $userId)->orderByDesc('id')->pluck('catalog_id')->map(fn ($id) => (int) $id)->all() : [];
    }
}
