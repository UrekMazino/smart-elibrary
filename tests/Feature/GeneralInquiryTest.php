<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/** The OPAC chat button: General inquiries from patrons (answered in the app) and guests (answered by email). */
class GeneralInquiryTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    private const GUEST = ['subject' => 'Opening hours', 'message' => 'Are you open on Saturdays?',
        'fullname' => 'Maria Santos', 'contactnum' => '+63 917 123 4567', 'email' => 'maria@example.org'];

    private function staff(): User
    {
        $staff = User::factory()->create(['usertype' => 'Developer', 'name' => 'Library Staff']);
        $staff->assignRole(Role::findOrCreate('developer'));
        return $staff;
    }

    public function test_a_guest_sends_a_general_inquiry_with_their_details()
    {
        $this->postJson('/inquiry/general', self::GUEST)->assertOk()->assertJson(['ok' => true, 'guest' => true]);

        $row = DB::table('view_inquiries')->first();
        $this->assertSame(['general', null, 'Maria Santos', '+63 917 123 4567', 'maria@example.org', null],
            [$row->kind, $row->user_id, $row->fullname, $row->contactnum, $row->email, $row->catalog_id]);
    }

    public function test_a_guest_must_give_a_name_a_contact_number_and_a_valid_email()
    {
        $this->postJson('/inquiry/general', ['subject' => 'Hi', 'message' => 'Hello'])
            ->assertStatus(422)->assertJsonValidationErrors(['fullname', 'contactnum', 'email']);

        $this->postJson('/inquiry/general', ['contactnum' => 'call me', 'email' => 'not-an-email'] + self::GUEST)
            ->assertStatus(422)->assertJsonValidationErrors(['contactnum', 'email']);

        $this->assertSame(0, DB::table('inquiries')->count());
    }

    public function test_the_hidden_spam_field_saves_nothing()
    {
        $this->postJson('/inquiry/general', self::GUEST + ['website' => 'http://spam.example'])->assertOk();
        $this->assertSame(0, DB::table('inquiries')->count());
    }

    public function test_a_patron_sends_one_with_subject_and_message_and_sees_it_in_their_inquiries()
    {
        $patron = User::factory()->create(['usertype' => 'Patron']);

        $this->actingAs($patron)->postJson('/inquiry/general', ['subject' => 'Thesis donation', 'message' => 'Can I donate my thesis?'])
            ->assertOk()->assertJson(['ok' => true, 'guest' => false]);

        $row = DB::table('inquiries')->first();
        $this->assertSame(['general', $patron->id, null, null], [$row->kind, (int) $row->user_id, $row->guest_name, $row->guest_email]);

        $this->actingAs($patron)->get('/patron/inquiries')->assertOk()->assertSee('General')->assertSee('Thesis donation');
    }

    public function test_staff_and_blocked_patrons_cannot_send_one()
    {
        $this->actingAs($this->staff())->postJson('/inquiry/general', ['subject' => 'x', 'message' => 'y'])->assertStatus(403);
        $blocked = User::factory()->create(['usertype' => 'Patron', 'is_blocked' => 1]);
        $this->actingAs($blocked)->postJson('/inquiry/general', ['subject' => 'x', 'message' => 'y'])->assertStatus(403);

        $this->assertSame(0, DB::table('inquiries')->count());
    }

    public function test_the_chat_button_shows_for_guests_and_patrons_but_not_staff()
    {
        $this->get('/home')->assertOk()->assertSee('id="lc-chat"', false)->assertSee('name="fullname"', false);
        $patron = User::factory()->create(['usertype' => 'Patron']);
        $this->actingAs($patron)->get('/home')->assertOk()->assertSee('id="lc-chat"', false)->assertDontSee('name="fullname"', false);
        $this->actingAs($this->staff())->get('/home')->assertDontSee('id="lc-chat"', false);
    }

    public function test_admin_inquiries_show_the_user_type_and_answer_guests_by_email()
    {
        $this->seedReferenceData('patrontypes');
        $student = User::factory()->create(['usertype' => 'Patron', 'name' => 'Juan Student', 'patrontypes_id' => 7]);
        $this->actingAs($student)->postJson('/inquiry/general', ['subject' => 'Wi-Fi', 'message' => 'Is there Wi-Fi?'])->assertOk();
        auth()->logout();
        $this->postJson('/inquiry/general', self::GUEST)->assertOk();

        $staff = $this->staff();
        $this->actingAs($staff)->get('/admin/inquiry')->assertOk()
            ->assertSee('User type')->assertDontSee('Material Title')
            ->assertSee('Student')->assertSee('Guest')->assertSee('General')
            ->assertSee('data-email="maria@example.org"', false)->assertSee('Not replied yet');

        // Mark the guest's inquiry as answered by email, then undo it.
        $guestId = DB::table('inquiries')->whereNull('user_id')->value('id');
        $this->actingAs($staff)->postJson("/admin/inquiry/$guestId/emailed")->assertOk()->assertJsonStructure(['replied_at']);
        $this->assertNotNull(DB::table('inquiries')->where('id', $guestId)->value('guest_replied_at'));
        $this->actingAs($staff)->get('/admin/inquiry')->assertSee('Replied by email');
        $this->actingAs($staff)->postJson("/admin/inquiry/$guestId/emailed", ['undo' => 1])->assertOk()->assertJson(['replied_at' => null]);
        $this->assertNull(DB::table('inquiries')->where('id', $guestId)->value('guest_replied_at'));

        // Only a guest's inquiry is answered by email; a patron's is answered in the app.
        $patronId = DB::table('inquiries')->whereNotNull('user_id')->value('id');
        $this->actingAs($staff)->postJson("/admin/inquiry/$patronId/emailed")->assertNotFound();
        $this->actingAs($staff)->post('/inquiry/reply', ['catalog_id_inquiry_reply' => $guestId, 'inquiry_message_reply' => 'Hi'])
            ->assertSessionHas('error');
        $this->assertSame(0, DB::table('reply_inquiries')->count());

        // Patrons can't use the staff action (sent away like any admin page).
        $this->actingAs($student)->postJson("/admin/inquiry/$guestId/emailed")->assertRedirect();
        $this->assertNull(DB::table('inquiries')->where('id', $guestId)->value('guest_replied_at'));
    }
}
