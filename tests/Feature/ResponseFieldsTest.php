<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** JSON answers carry only what their page shows (OWASP ASVS 15.3.1). */
class ResponseFieldsTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        $staff = User::factory()->create(['usertype' => 'Developer', 'name' => 'Maria Staff']);
        $staff->assignRole(Role::findOrCreate('developer'));
        return $staff;
    }

    public function test_a_patron_reads_the_library_not_the_staff_members_name_in_their_inquiry()
    {
        $patron = User::factory()->create(['usertype' => 'Patron', 'name' => 'Ana Cruz']);
        $staff = $this->staff();
        $inquiry = DB::table('inquiries')->insertGetId(['kind' => 'general', 'user_id' => $patron->id, 'subject' => 'Hours', 'message' => 'Open on Saturday?', 'created_at' => now()]);
        DB::table('reply_inquiries')->insert([
            ['inquiry_id' => $inquiry, 'user_id' => $staff->id, 'message' => 'Yes, until noon.', 'created_at' => now()],
            ['inquiry_id' => $inquiry, 'user_id' => $patron->id, 'message' => 'Thank you!', 'created_at' => now()->addMinute()],
        ]);

        $forPatron = $this->actingAs($patron)->getJson("/inquiry/json/$inquiry")->assertOk()->json();
        $this->assertEqualsCanonicalizing(
            [['Library', null, 'Yes, until noon.'], ['Ana Cruz', $patron->id, 'Thank you!']],
            array_map(fn ($m) => [$m['replied_by_name'], $m['replied_by'], $m['replied_msg']], $forPatron));
        $this->assertStringNotContainsString('Maria Staff', json_encode($forPatron));
        $this->assertSame(['replied_by', 'replied_by_name', 'replied_msg', 'thread_date'], array_keys($forPatron[0]));

        // Staff still see who answered.
        $this->actingAs($staff)->getJson("/inquiry/json/$inquiry")->assertOk()->assertJsonFragment(['replied_by_name' => 'Maria Staff']);
    }

    public function test_the_patron_and_administrator_lists_leave_out_fields_they_dont_show()
    {
        User::factory()->create(['usertype' => 'Patron', 'username' => 'ana.cruz']);
        User::factory()->create(['usertype' => 'Administrator', 'username' => 'desk.admin']);
        $staff = $this->staff();

        $patrons = $this->actingAs($staff)->getJson('/admin/patron/json')->assertOk()->json('aaData');
        $this->assertNotEmpty($patrons);
        $this->assertArrayNotHasKey('username', $patrons[0]);

        $admins = $this->actingAs($staff)->getJson('/superadmin/administrators/json')->assertOk()->json('aaData');
        $this->assertNotEmpty($admins);
        $this->assertArrayNotHasKey('username', $admins[0]);
    }

    public function test_unused_endpoints_that_returned_whole_records_are_gone()
    {
        $staff = $this->staff();

        $this->actingAs($staff)->getJson('/admin/inquiry/json')->assertNotFound();
        $this->actingAs($staff)->getJson('/api/user')->assertNotFound();
    }
}
