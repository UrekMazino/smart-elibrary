<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Laravel\Scout\Searchable;

use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Attributes\SearchUsingPrefix;

class View_inquiry_thread extends Model
{
    use HasFactory, Searchable;

    public function toSearchableArray()
    {
        return [
            'user_id' => $this->user_id,
            'id' => $this->inquiry_id
        ];
    }
}
