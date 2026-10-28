<?php

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use App\Models\Multimedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * Staff open the OPAC from the admin navbar: signed in, they can search, read, download, share and
 * save, but they are not patrons: no bell, no patron menu (a "Return to admin" button instead), no
 * inquiries, reservations or reviews, and their views, reads and downloads are not counted.
 */
class StaffOpacTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    private User $staff;
    private User $patron;
    private int $catalogId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData('copies_status');

        $this->staff = User::factory()->create(['usertype' => 'Developer']);
        $this->staff->assignRole(Role::findOrCreate('developer'));
        $this->patron = User::factory()->create(['usertype' => 'Patron']);

        $material = DB::table('materials')->insertGetId(['name' => 'Book']);
        $this->catalogId = DB::table('catalogs')->insertGetId(['material_type_id' => $material, 'field_data' => '[]', 'is_published' => 1]);
        DB::table('view_catalogs')->insert(['catalog_id' => $this->catalogId, 'title_statement' => 'Rubber production',
            'material_type_id' => $material, 'is_published' => 1]);
        DB::table('copies')->insert(['catalog_id' => $this->catalogId, 'circulation_number' => 'C-1', 'status' => 1]);

        // The full text, on a private disk of the test's own.
        Storage::fake('private')->put('pdf/ft.pdf', "%PDF-1.4\n%%EOF\n");
        Multimedia::create(['catalog_id' => $this->catalogId, 'FileName' => 'ft.pdf', 'FileLocation' => 'pdf/ft.pdf', 'FileType' => 'pdf']);
    }

    private function recordPage(User $user)
    {
        $page = $this->actingAs($user)->get("/full-description/{$this->catalogId}");
        return $page->isRedirect() ? $this->actingAs($user)->get($page->headers->get('Location')) : $page;   // canonical slug URL
    }

    public function test_the_admin_navbar_opens_the_opac_and_return_to_admin_comes_back()
    {
        $this->actingAs($this->staff)->get('/admin/profile')->assertOk()->assertSee(route('admin.opac'));

        $this->actingAs($this->staff)->from(url('/admin/record-list'))->get('/admin/opac')->assertRedirect('/home');

        $this->actingAs($this->staff)->get('/home')->assertOk()
            ->assertSee('Return to admin')
            ->assertDontSee('Welcome, ' . $this->staff->name)
            ->assertDontSee('nfy-bell-toggle', false)
            ->assertDontSee("$('#csfModal').modal('show')", false);

        $this->actingAs($this->staff)->get('/admin/opac/return')->assertRedirect(url('/admin/record-list'));
    }

    public function test_return_to_admin_falls_back_to_the_admin_landing_page()
    {
        // A page shown by a POST (here a batch's details) can't be returned to with a link.
        $this->actingAs($this->staff)->from(url('/admin/view-batch'))->get('/admin/opac');

        $this->actingAs($this->staff)->get('/admin/opac/return')->assertRedirect(HomeController::adminLandingUrl($this->staff));
    }

    public function test_staff_still_land_in_the_admin_and_patron_pages_stay_closed()
    {
        $this->post('/login', ['email' => $this->staff->email, 'password' => 'password'])
            ->assertRedirect(HomeController::adminLandingUrl($this->staff));
        $this->actingAs($this->staff)->get('/login')->assertRedirect(HomeController::adminLandingUrl($this->staff));

        $this->actingAs($this->staff)->get('/patron/profile')->assertRedirect('/home');
        $this->actingAs($this->patron)->get('/admin/opac')->assertRedirect('/');   // staff only
    }

    public function test_staff_read_and_download_but_are_not_counted()
    {
        $this->recordPage($this->staff)->assertOk();
        $this->actingAs($this->staff)->get("/read/{$this->catalogId}")->assertOk();
        $this->actingAs($this->staff)->post('/download', ['holdings_download_id' => $this->catalogId])->assertDownload();

        $this->assertSame([0, 0, 0], [DB::table('views')->count(), DB::table('reads')->count(), DB::table('downloads')->count()]);

        // A patron doing the same is counted.
        $this->recordPage($this->patron)->assertOk();
        $this->actingAs($this->patron)->get("/read/{$this->catalogId}")->assertOk();
        $this->actingAs($this->patron)->post('/download', ['holdings_download_id' => $this->catalogId])->assertDownload();

        $this->assertSame([1, 1, 1], [DB::table('views')->count(), DB::table('reads')->count(), DB::table('downloads')->count()]);
    }

    public function test_staff_can_save_but_not_inquire_reserve_or_review()
    {
        $this->actingAs($this->staff)->postJson('/search/saved', ['catalog_id' => $this->catalogId])->assertOk();
        $this->assertSame(1, DB::table('saved_records')->where('user_id', $this->staff->id)->count());

        $this->recordPage($this->staff)
            ->assertSee('Inquiries are sent by patrons. You are signed in as staff.')
            ->assertDontSee('data-fd="inquire"', false)
            ->assertSee('Reservations are made by patrons.')
            ->assertSee('<fieldset disabled>', false)
            ->assertSee('Reviews are written by patrons. You are signed in as staff.')
            ->assertDontSee('data-fd="submit-review"', false);

        $this->actingAs($this->staff)->post('/inquiry/submit', [
            'inquiry_subject' => 'Copies', 'inquiry_message' => 'Is it available?', 'catalog_id_inquiry' => $this->catalogId,
        ])->assertSessionHas('error');
        $this->actingAs($this->staff)->post('/request-reservation', [
            'catalog_id' => $this->catalogId, 'visit_scheddate' => now()->next('Monday')->format('m/d/Y'), 'visit_schedtime' => '9:00 am',
        ]);

        $this->assertSame([0, 0], [DB::table('inquiries')->count(), DB::table('circulations')->count()]);

        // Patrons keep both (reviewing once they have read the material: only its readers rate it).
        DB::table('reads')->insert(['catalog_id' => $this->catalogId, 'user_id' => $this->patron->id, 'created_at' => now()]);
        $this->recordPage($this->patron)->assertSee('data-fd="inquire" data-id="' . $this->catalogId . '"', false)->assertDontSee('<fieldset disabled>', false)
            ->assertSee('data-fd="submit-review"', false);
    }
}
