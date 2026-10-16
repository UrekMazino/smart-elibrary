<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/** Rules the database itself now enforces, and the inventory reports that depended on it. */
class SchemaIntegrityTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData('copies_status', 'circulation_status');
    }

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

    public function test_a_barcode_belongs_to_one_copy_and_a_patron_reviews_a_record_once()
    {
        $catalogId = $this->catalog();
        $user = User::factory()->create();
        DB::table('copies')->insert(['catalog_id' => $catalogId, 'circulation_number' => 'B-1']);
        DB::table('ratings')->insert(['catalog_id' => $catalogId, 'user_id' => $user->id, 'rating' => 5]);

        foreach ([fn () => DB::table('copies')->insert(['catalog_id' => $catalogId, 'circulation_number' => 'B-1']),
                  fn () => DB::table('ratings')->insert(['catalog_id' => $catalogId, 'user_id' => $user->id, 'rating' => 4])] as $duplicate) {
            try {
                $duplicate();
                $this->fail('A duplicate was accepted.');
            } catch (QueryException $e) {
                $this->assertSame('23000', $e->getCode());
            }
        }
    }

    public function test_a_status_must_be_in_its_vocabulary()
    {
        $catalogId = $this->catalog();
        DB::table('copies')->insert(['catalog_id' => $catalogId, 'circulation_number' => 'B-1']);
        $this->assertSame(4, (int) DB::table('copies')->value('status'));   // default: Not Available

        $this->expectException(QueryException::class);
        DB::table('copies')->insert(['catalog_id' => $catalogId, 'circulation_number' => 'B-2', 'status' => 9]);
    }

    public function test_a_birthdate_is_stored_as_a_date_whichever_form_sent_it()
    {
        $this->seedReferenceData('patrontypes');
        $staff = User::factory()->create(['usertype' => 'Developer']);
        $staff->assignRole(Role::findOrCreate('developer'));
        $form = ['name' => $staff->name, 'email' => $staff->email, 'contactnum' => '09170000000', 'patron_type_id' => 1];

        // The admin profile's date picker posts mm/dd/yyyy.
        $this->actingAs($staff)->post('/admin/profile/update', $form + ['birthdate' => '12/06/1990'])->assertSessionHasNoErrors();
        $this->assertSame('1990-12-06', DB::table('users')->where('id', $staff->id)->value('birthdate'));
        $this->actingAs($staff)->get('/admin/profile')->assertSee('value="12/06/1990"', false);

        $this->actingAs($staff)->post('/admin/profile/update', $form + ['birthdate' => 'someday'])->assertSessionHasErrors('birthdate');
    }

    public function test_inquiries_show_the_title_of_the_record_asked_about()
    {
        $catalogId = $this->catalog();
        DB::table('view_catalogs')->insert(['catalog_id' => $catalogId, 'title_statement' => 'Rice farming']);
        $patron = User::factory()->create();
        DB::table('inquiries')->insert(['catalog_id' => $catalogId, 'user_id' => $patron->id, 'message' => 'Is it available?']);

        $this->assertSame('Rice farming', DB::table('view_inquiries')->value('title_statement'));
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

    public function test_a_patron_region_is_stored_as_the_region_id()
    {
        $this->seedReferenceData('patrontypes', 'regions');
        $ncr = (int) DB::table('regions')->where('regCode', '13')->value('id');   // id 14: not the code
        $patron = User::factory()->create(['usertype' => 'Patron']);
        $form = ['name' => $patron->name, 'email' => $patron->email, 'contactnum' => '09170000000',
                 'birthdate' => '1990-12-06', 'patron_type_id' => 1];

        $this->actingAs($patron)->post("/patron/profile/update/{$patron->id}", $form + ['region_id' => $ncr])->assertSessionHasNoErrors();
        $this->assertSame($ncr, (int) DB::table('users')->where('id', $patron->id)->value('region_id'));
        $this->actingAs($patron)->get('/patron/profile')->assertSee('value="' . $ncr . '" selected', false);

        $this->actingAs($patron)->post("/patron/profile/update/{$patron->id}", $form + ['region_id' => 999])->assertSessionHasErrors('region_id');
        $this->expectException(QueryException::class);
        DB::table('users')->where('id', $patron->id)->update(['region_id' => 999]);
    }

    public function test_every_table_is_innodb_with_one_collation()
    {
        $odd = DB::table('information_schema.tables')->where('table_schema', DB::getDatabaseName())
            ->where('table_type', 'BASE TABLE')
            ->where(fn ($q) => $q->where('engine', '<>', 'InnoDB')->orWhere('table_collation', '<>', 'utf8mb4_unicode_ci'))
            ->pluck('table_name');
        $this->assertSame([], $odd->all());

        $columns = DB::table('information_schema.columns')->where('table_schema', DB::getDatabaseName())
            ->whereNotNull('collation_name')->where('collation_name', '<>', 'utf8mb4_unicode_ci')
            ->whereNotIn('table_name', DB::table('information_schema.views')->where('table_schema', DB::getDatabaseName())->select('table_name'))
            ->pluck('column_name');
        $this->assertSame([], $columns->all());
    }

    public function test_formerly_latin1_tables_keep_text_in_any_script()
    {
        $id = DB::table('materials')->insertGetId(['name' => 'Aklat — 本 — Книга']);

        $this->assertSame('Aklat — 本 — Книга', DB::table('materials')->where('id', $id)->value('name'));
    }

    public function test_a_malformed_marc_record_is_refused()
    {
        $catalogId = $this->catalog();
        DB::table('catalogs')->where('id', $catalogId)->update(['field_data' => '[{"marctag":{"code":"245"}}]']);

        $this->expectException(QueryException::class);
        DB::table('catalogs')->where('id', $catalogId)->update(['field_data' => '[{"marctag":']);
    }

    public function test_the_pre_marc_tables_and_their_screens_are_gone()
    {
        foreach (['holdings', 'authors', 'corporate_authors', 'barrowed_books', 'view_holdings', 'view_borrowed_books'] as $legacy) {
            $this->assertFalse(Schema::hasTable($legacy), "$legacy still exists");
        }

        $staff = User::factory()->create(['usertype' => 'Developer']);
        $staff->assignRole(Role::findOrCreate('developer'));
        foreach (['/admin/catalog', '/admin/uncat', '/admin/new-acquisition'] as $url) {
            $this->actingAs($staff)->get($url)->assertNotFound();
        }
    }
}
