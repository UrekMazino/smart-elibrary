<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Attributes\SearchUsingPrefix;


class ViewCatalog extends Model
{
    use HasFactory, Searchable;

    protected $table = 'view_catalogs';

    protected $fillable = [
        'catalog_id',
        'isbn',
        'issn',
        'accession_number',
        'material_type_id',
        'catalog_import_id',
        'material_type',
        'series_statement_id',
        'series_description',
        'title_statement',
        'summary',
        'general_note',
        'call_number',
        'catalog_date',
        'catalog_status',
        'date_acquired',
        'edition',
        'joint_authors',
        'pub_url',
        'pub_year',
        'publisher',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
    ];

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'isbn' => $this->isbn,
            'issn' => $this->issn,
            'accession_number' => $this->accession_number,
            'title_statement' => $this->title_statement,
            'material_type' => $this->material_type,
            'pub_year' => $this->pub_year,
            // 'publisher' => $this->publisher,
            // 'joint_authors' => $this->joint_authors,
        ];
    }
}
