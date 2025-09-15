<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Attributes\SearchUsingPrefix;


class View_holding_uncatalog extends Model
{
    use HasFactory, Searchable;

    protected $table = 'view_holdings_uncatalog';

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'catalog_id' => $this->catalog_id,
            'title_statement' => $this->title_statement,
            'summary' => $this->summary,
            'material_type_id' => $this->material_type_id,
            'material' => $this->material,
            'series_statement_id' => $this->series_statement_id,
            'pub_year' => $this->pub_year,
            'consortia' => $this->consortia,
        ];
    }
}
