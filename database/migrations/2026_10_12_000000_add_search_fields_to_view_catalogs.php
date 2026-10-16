<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields for the OPAC search page (filters with counts, year range, saved records).
 *
 * view_catalogs:
 *  - pub_year_num : the year as a number, taken from pub_year, which is free text ("[1960]",
 *                   "c1978", "1978-1980"). Backs the Year filter, the year histogram and sorting.
 *  - subjects     : subject headings from MARC 600/610/650/651/653 $a as a JSON list, cleaned of
 *                   ISBD punctuation ("Nutrition," and "Nutrition" are one subject). Backs the
 *                   Subject filter and subject search.
 * Both are filled on save (see marcSubjects() / pubYearNumber()); existing records get them from
 * `php artisan catalog:rebuild-search-fields --apply`.
 *
 * saved_records: a signed-in patron's saved list (the Save button on search results).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('view_catalogs', function (Blueprint $table) {
            $table->unsignedSmallInteger('pub_year_num')->nullable()->after('pub_year')->index();
            $table->json('subjects')->nullable()->after('joint_authors');
        });

        Schema::create('saved_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('catalog_id');   // catalogs.id is INT UNSIGNED
            $table->timestamps();
            $table->unique(['user_id', 'catalog_id']);
            $table->foreign('catalog_id')->references('id')->on('catalogs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_records');

        Schema::table('view_catalogs', function (Blueprint $table) {
            $table->dropIndex(['pub_year_num']);
            $table->dropColumn(['pub_year_num', 'subjects']);
        });
    }
};
