<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Rules the database itself now enforces, and the inventory reports that depended on it. */
class SchemaIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function catalog(): int
    {
        $material = DB::table('materials')->insertGetId(['name' => 'Book']);
        return DB::table('catalogs')->insertGetId(['material_type_id' => $material, 'field_data' => '[]']);
    }

    public function test_an_email_address_belongs_to_one_account_whatever_its_case()
    {
        User::factory()->create(['email' => 'juan@example.org']);

        $this->expectException(QueryException::class);
        User::factory()->create(['email' => 'Juan@Example.org']);
    }

    public function test_rows_can_not_point_at_a_missing_record_or_account()
    {
        $catalogId = $this->catalog();
        $user = User::factory()->create();

        DB::table('ratings')->insert(['catalog_id' => $catalogId, 'user_id' => $user->id, 'rating' => 5]);

        $this->expectException(QueryException::class);
        DB::table('ratings')->insert(['catalog_id' => $catalogId + 1000, 'user_id' => $user->id, 'rating' => 5]);
    }

    public function test_page_views_survive_their_account_anonymised()
    {
        $catalogId = $this->catalog();
        $user = User::factory()->create();
        DB::table('views')->insert(['catalog_id' => $catalogId, 'user_id' => $user->id, 'is_guest' => 'NO']);

        $user->forceDelete();

        $this->assertNull(DB::table('views')->value('user_id'));
        $this->assertSame(1, DB::table('views')->count());
    }

    public function test_import_batches_beyond_127_keep_their_number()
    {
        $catalogId = $this->catalog();
        $material = DB::table('catalogs')->where('id', $catalogId)->value('material_type_id');
        DB::table('catalog_imports')->insert(['id' => 300, 'no_of_records' => 1, 'material_type_id' => $material]);

        DB::table('catalogs')->where('id', $catalogId)->update(['catalog_import_id' => 300]);

        $this->assertSame(300, (int) DB::table('catalogs')->where('id', $catalogId)->value('catalog_import_id'));
    }

    public function test_self_registered_patrons_have_no_patron_type_rather_than_zero()
    {
        $this->post('/register', [
            'name' => 'Test Patron', 'email' => 'patron@example.org', 'contactnum' => '09170000000',
            'password' => 'password', 'password_confirmation' => 'password',
        ]);

        $this->assertNull(User::where('email', 'patron@example.org')->value('patrontypes_id'));
    }

    public function test_inventory_reports_list_scanned_and_missing_copies()
    {
        $staff = User::factory()->create(['usertype' => 'Developer']);
        $staff->assignRole(Role::findOrCreate('developer'));
        $catalogId = $this->catalog();
        DB::table('catalogs')->where('id', $catalogId)->update(['accession_number' => 'ACC-0001']);
        foreach (['SCANNED-1', 'MISSING-1'] as $barcode) {
            DB::table('copies')->insert(['catalog_id' => $catalogId, 'circulation_number' => $barcode, 'title' => "Title of $barcode", 'status' => 1]);
        }
        DB::table('holdingscopypresenteds')->insert(['catalog_id' => $catalogId, 'circulation_number' => 'SCANNED-1', 'created_at' => now()]);

        $this->actingAs($staff)->get('/admin/inventory/reportpresented')
            ->assertOk()->assertSee('SCANNED-1')->assertSee('ACC-0001')->assertDontSee('MISSING-1');
        $this->actingAs($staff)->get('/admin/inventory/reportmissing')
            ->assertOk()->assertSee('MISSING-1')->assertSee('Title of MISSING-1')->assertDontSee('SCANNED-1');
    }
}
