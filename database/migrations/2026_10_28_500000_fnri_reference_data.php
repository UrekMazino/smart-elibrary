<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * This eLibrary is DOST-FNRI's (decided 2026-10-28): reference data that came from PCAARRD's installation goes.
 *  - The six seeded MARC templates are renamed *_LC_PCAARRD → *_LC_FNRI. Only templates still named as seeded;
 *    one a librarian renamed is left alone.
 *  - consortias, PCAARRD's 16 regional consortia, is dropped. Only Reports' "Source" filter read it, and that
 *    filter was broken (the records have no consortia column).
 */
return new class extends Migration
{
    private const TEMPLATES = ['BOOKS', 'CONTINUING RESOURCES', 'THESIS / DISSERTATION', 'TECHNICAL / TERMINAL REPORTS', 'VERTICAL FILES', 'NON-PRINTS'];

    private const CONSORTIA = ['CorCAARRD', 'MAARRDEC', 'WESMAARRDEC', 'ILAARRDEC', 'BCAARRD', 'NOMCAARRD', 'CVAARRD', 'WESVAARRDEC',
        'SMAARRDEC', 'CLAARRDEC', 'CVAARRDEC', 'SOXAARRDEC', 'STAARRDEC', 'ViCARP', 'CCAARRD', 'PCAARRD'];

    public function up(): void
    {
        foreach (self::TEMPLATES as $name) {
            DB::table('marc_templates')->where('name', $name . '_LC_PCAARRD')->update(['name' => $name . '_LC_FNRI']);
        }
        Schema::dropIfExists('consortias');
    }

    public function down(): void
    {
        foreach (self::TEMPLATES as $name) {
            DB::table('marc_templates')->where('name', $name . '_LC_FNRI')->update(['name' => $name . '_LC_PCAARRD']);
        }
        Schema::create('consortias', function (Blueprint $table) {
            $table->id();
            $table->string('name', 25)->nullable();
            $table->timestamps();
        });
        DB::table('consortias')->insert(array_map(fn ($name, $i) => ['id' => $i + 1, 'name' => $name], self::CONSORTIA, array_keys(self::CONSORTIA)));
    }
};
