<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** R15: personal data kept only where it is needed, and in one place. */
class PrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function catalog(): int
    {
        $material = DB::table('materials')->insertGetId(['name' => 'Book']);
        return DB::table('catalogs')->insertGetId(['material_type_id' => $material, 'field_data' => '[]']);
    }

    public function test_a_page_view_is_counted_without_the_visitors_ip_or_browser()
    {
        $this->assertFalse(Schema::hasColumn('views', 'ip_view'));
        $this->assertFalse(Schema::hasColumn('views', 'user_agent'));

        saveView($this->catalog());

        $this->assertSame(1, DB::table('views')->count());
    }

    public function test_only_signed_in_users_can_send_an_inquiry()
    {
        $catalogId = $this->catalog();

        $this->post('/inquiry/submit', ['catalog_id_inquiry' => $catalogId, 'inquiry_subject' => 'Copy?', 'inquiry_message' => 'Hello',
            'inquiry_fullname' => 'Guest', 'inquiry_email' => 'guest@example.org', 'inquiry_contactnum' => '09170000000'])
            ->assertRedirect('/login');

        $this->assertSame(0, DB::table('inquiries')->count());
    }

    public function test_an_inquiry_shows_the_senders_current_details_from_their_account()
    {
        $catalogId = $this->catalog();
        $patron = User::factory()->create(['usertype' => 'Patron', 'name' => 'Ana Cruz', 'email' => 'ana@example.org', 'contactnum' => '09171111111']);

        $this->actingAs($patron)->post('/inquiry/submit', ['catalog_id_inquiry' => $catalogId, 'inquiry_subject' => 'Copy?', 'inquiry_message' => 'Hello'])
            ->assertOk();
        $this->assertFalse(Schema::hasColumn('inquiries', 'email'));
        $this->assertSame($patron->id, (int) DB::table('inquiries')->value('user_id'));

        // A correction to the account is what staff see on the inquiry: there is no second copy.
        $patron->update(['name' => 'Ana C. Reyes', 'contactnum' => '09172222222']);
        $staff = User::factory()->create(['usertype' => 'Developer']);
        $staff->assignRole(Role::findOrCreate('developer'));

        $this->actingAs($staff)->get('/admin/inquiry')
            ->assertOk()->assertSee('Ana C. Reyes')->assertSee('09172222222')->assertSee('ana@example.org')->assertDontSee('Ana Cruz');
    }
}
