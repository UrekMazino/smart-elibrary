<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Attributes\SearchUsingPrefix;

use Illuminate\Database\Eloquent\SoftDeletes;

class Holding extends Model
{
    use HasFactory,Searchable,SoftDeletes;

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title_statement' => $this->title_statement,
            'summary' => $this->summary,
            'material_type_id' => $this->material_type_id,
        ];
    }
}
