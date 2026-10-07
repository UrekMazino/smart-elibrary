<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch Import "material type mapping": map the values of one MARC field in an uploaded file
 * (e.g. Koha's 942$c item type: FN / TH / AR) to Material Types, so each imported record gets
 * its own type instead of one type for the whole batch.
 *
 * - material_type_maps: named, reusable presets ("Koha – FNRI").
 * - catalog_imports.material_map: the mapping actually used by a batch (audit trail).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_type_maps', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('source_field', 20);                       // "942$c", "LDR/06", ...
            $table->json('mappings');                                  // {"<normalised value>": material_id}
            $table->unsignedBigInteger('default_material_type_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::table('catalog_imports', function (Blueprint $table) {
            $table->json('material_map')->nullable()->after('material_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_imports', function (Blueprint $table) {
            $table->dropColumn('material_map');
        });
        Schema::dropIfExists('material_type_maps');
    }
};
