<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Administration → Utilities → Material Types: merging one type into another. */
class MaterialTypeMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_merging_a_material_type_moves_its_records_and_removes_it()
    {
        $staff = User::factory()->create(['usertype' => 'Developer']);
        $staff->assignRole(Role::findOrCreate('developer'));
        $from = DB::table('materials')->insertGetId(['name' => 'Pamphlet']);
        $into = DB::table('materials')->insertGetId(['name' => 'Book']);
        $catalogId = DB::table('catalogs')->insertGetId(['material_type_id' => $from, 'field_data' => '[]']);
        DB::table('view_catalogs')->insert(['catalog_id' => $catalogId, 'material_type_id' => $from, 'material_type' => 'Pamphlet']);

        $this->actingAs($staff)->postJson("/admin/utilities/material-types/$from/merge", ['target_id' => $into])->assertOk();

        $this->assertSame($into, (int) DB::table('catalogs')->where('id', $catalogId)->value('material_type_id'));
        $this->assertSame('Book', DB::table('view_catalogs')->where('catalog_id', $catalogId)->value('material_type'));
        $this->assertFalse(DB::table('materials')->where('id', $from)->exists());
        // The AI index never reads material types: a merge must not queue its records for re-embedding.
        $this->assertSame(0, DB::table('rag_sync_queue')->count());
    }
}
