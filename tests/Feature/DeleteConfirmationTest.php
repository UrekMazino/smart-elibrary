<?php

namespace Tests\Feature;

use App\Models\Frontpage;
use App\Models\Multimedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Removing a record's full text or cover asks first (data-confirm, admin.partials.confirm-js). */
class DeleteConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_removing_a_full_text_or_a_cover_asks_first()
    {
        $staff = User::factory()->create(['usertype' => 'Developer']);
        $staff->assignRole(Role::findOrCreate('developer'));
        $material = DB::table('materials')->insertGetId(['name' => 'Book']);
        $id = DB::table('catalogs')->insertGetId(['material_type_id' => $material, 'field_data' => '[]', 'is_published' => 1]);
        DB::table('view_catalogs')->insert(['catalog_id' => $id, 'title_statement' => 'Balut vending machine', 'material_type_id' => $material, 'is_published' => 1]);
        Multimedia::create(['catalog_id' => $id, 'FileName' => 'ft.pdf', 'FileLocation' => 'pdf/ft.pdf', 'FileType' => 'pdf']);
        Frontpage::create(['catalog_id' => $id, 'FrontPageName' => 'cp-real.jpg', 'FrontPageLocation' => 'frontpages/cp-real.jpg', 'FrontPageType' => 'jpg']);

        $page = $this->actingAs($staff)->get("/admin/view-record/$id")->assertOk();

        $page->assertSee('data-confirm-title="Remove the full text?"', false)
            ->assertSee('data-confirm-title="Remove the cover photo?"', false)
            ->assertSee("form.hasAttribute('data-confirm')", false);   // the handler is on the page
    }
}
