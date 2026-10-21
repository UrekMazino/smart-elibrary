<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as BaseCollection;
use Laravel\Scout\Searchable;


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
        'subjects',
        'pub_url',
        'pub_year',
        'pub_year_num',
        'publisher',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
    ];

    protected $casts = [
        'subjects' => 'array',
    ];

    protected static function booted(): void
    {
        // pub_year is free text ("[1960]", "c1978"); keep its numeric year in step on every save.
        static::saving(function (ViewCatalog $vc) {
            if (! $vc->exists || $vc->isDirty('pub_year')) {
                $vc->pub_year_num = pubYearNumber($vc->pub_year);
            }
        });
    }

    /** Full text (PDF) rows. */
    public function multimedia(): HasMany
    {
        return $this->hasMany(Multimedia::class, 'catalog_id', 'catalog_id');
    }

    public function frontpages(): HasMany
    {
        return $this->hasMany(Frontpage::class, 'catalog_id', 'catalog_id');
    }

    public function views(): HasMany
    {
        return $this->hasMany(View::class, 'catalog_id', 'catalog_id');
    }

    /** A real cover: an uploaded one, not a generated "gen-" placeholder. */
    public static function realCover(Builder $q): Builder
    {
        return $q->where(fn ($w) => $w->whereNull('FrontPageName')->orWhere('FrontPageName', 'not like', 'gen-%'));
    }

    /** scout:import / makeAllSearchable(): load the index-only values in the same query. */
    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->withExists(['multimedia as has_fulltext'])
            ->withExists(['frontpages as has_cover' => fn ($q) => self::realCover($q)])
            ->withCount('views');
    }

    /** ->searchable() on a query or collection: same values, loaded for the whole batch at once. */
    public function makeSearchableUsing(BaseCollection $models)
    {
        if ($models instanceof EloquentCollection && $models->isNotEmpty()) {
            $models->loadExists(['multimedia as has_fulltext', 'frontpages as has_cover' => fn ($q) => self::realCover($q)])
                ->loadCount('views');
        }
        return $models;
    }

    /** Authors as separate names (joint_authors is " / "-separated). */
    public function authorList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('#\s*/\s*#u', (string) $this->joint_authors))));
    }

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'catalog_id' => $this->catalog_id,
            'isbn' => $this->isbn,
            'issn' => $this->issn,
            'accession_number' => $this->accession_number,
            'title_statement' => $this->title_statement,
            'joint_authors' => $this->joint_authors,
            'summary' => $this->summary,
            'call_number' => $this->call_number,
            'material_type' => $this->material_type,
            'pub_year' => $this->pub_year,
            'publisher' => seoTrimIsbd($this->publisher ?? '') ?: null,
            // OPAC search page: filters (with counts), sorting and field search.
            'authors'      => $this->authorList(),
            'subjects'     => array_values($this->subjects ?? []),
            'year'         => $this->pub_year_num,
            'has_fulltext' => (bool) ($this->has_fulltext ?? $this->multimedia()->exists()),
            'has_cover'    => (bool) ($this->has_cover ?? self::realCover($this->frontpages()->getQuery())->exists()),
            'views'        => (int) ($this->views_count ?? $this->views()->count()),
            'title_sort'   => searchTitleSort($this->title_statement),
            // filter fields used by ResultController's Scout filters:
            'material_type_id'    => $this->material_type_id,
            'is_published'        => (int) $this->is_published,
            'series_statement_id' => $this->series_statement_id,
            'deleted_at'          => $this->deleted_at,   // -> "deleted_at IS NULL" filter
        ];
    }
}
