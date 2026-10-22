<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * What a patron writes under Messages on their Reservations page is listed in Administration →
 * Inquiries as "Reservation RSV-… · status", one row per reservation, and answered there in the same
 * thread the Reservations pages use, by staff who answer inquiries.
 */
class InquiryReservationThreadTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    private User $patron;
    private User $desk;
    private int $catalogId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
        $this->seedReferenceData('circulation_status');

        // Inquiries only: no circulation permissions.
        Role::create(['name' => 'inquiry_desk', 'guard_name' => 'web'])->givePermissionTo('access.inquiry');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->desk = tap(User::factory()->create(['usertype' => 'Administrator']))->assignRole('inquiry_desk');
        $this->patron = User::factory()->create(['usertype' => 'Patron', 'name' => 'Ana Reyes']);

        $material = DB::table('materials')->insertGetId(['name' => 'Book']);
        $this->catalogId = DB::table('catalogs')->insertGetId(['material_type_id' => $material, 'field_data' => '[]', 'is_published' => 1]);
        DB::table('view_catalogs')->insert(['catalog_id' => $this->catalogId, 'title_statement' => 'Philippine food composition tables',
            'material_type_id' => $material, 'is_published' => 1]);
    }

    private function reserve(): string
    {
        $this->actingAs($this->patron)->post('/request-reservation', [
            'catalog_id' => $this->catalogId, 'title_statement' => 'Philippine food composition tables',
            'visit_scheddate' => now()->next(Carbon::MONDAY)->format('m/d/Y'), 'visit_schedtime' => '9:00 am',
        ])->assertSessionHasNoErrors();

        return DB::table('circulations')->value('reservation_code');
    }

    private function patronWrites(string $code, string $message): void
    {
        $this->actingAs($this->patron)->post('/patron/reservation/chat/reply', ['reservation_code' => $code, 'message' => $message])->assertOk();
    }

    public function test_a_patrons_reservation_message_is_listed_under_inquiries()
    {
        $code = $this->reserve();
        $this->patronWrites($code, 'Can I come on Tuesday instead?');

        $page = $this->actingAs($this->desk)->get('/admin/inquiry')->assertOk();
        $thread = $page->viewData('threads')->firstWhere('reservation_code', $code);
        $this->assertSame([1, 'Ana Reyes'], [(int) $thread->unread, $thread->fullname]);
        $page->assertSee("Reservation $code · For Approval")->assertSee('Philippine food composition tables');
    }

    public function test_a_thread_only_the_library_wrote_in_is_not_listed()
    {
        $code = $this->reserve();
        DB::table('reservation_messages')->insert(['reservation_code' => $code, 'user_id' => $this->patron->id, 'sender_id' => $this->desk->id,
            'sender_role' => 'admin', 'message' => 'Your copy is ready.', 'created_at' => now(), 'updated_at' => now()]);

        $this->assertCount(0, $this->actingAs($this->desk)->get('/admin/inquiry')->viewData('threads'));
    }

    public function test_inquiry_staff_read_and_answer_it_and_the_patron_gets_the_reply()
    {
        $code = $this->reserve();
        $this->patronWrites($code, 'Can I come on Tuesday instead?');
        $this->assertSame(1, NotificationService::for($this->desk)['inquiries']);   // sidebar badge

        $this->actingAs($this->desk)->getJson("/admin/inquiry/reservation/$code")->assertOk()
            ->assertJsonPath('0.message', 'Can I come on Tuesday instead?');
        $this->assertSame(0, NotificationService::for($this->desk)['inquiries']);   // opened: seen

        $this->actingAs($this->desk)->post('/admin/inquiry/reservation/reply', ['reservation_code' => $code, 'message' => 'Yes, Tuesday is fine.'])->assertOk();

        $this->actingAs($this->patron)->getJson("/patron/reservation/chat-json/$code")->assertOk()
            ->assertJsonPath('1.sender_role', 'admin')->assertJsonPath('1.message', 'Yes, Tuesday is fine.');
    }

    public function test_a_message_on_a_closed_reservation_counts_under_inquiries_not_reservations()
    {
        $code = $this->reserve();
        DB::table('circulations')->where('reservation_code', $code)->update(['status' => 5]);   // returned
        $this->patronWrites($code, 'Can I borrow this material again?');

        // The Reservations page doesn't list a closed reservation, so its badge doesn't count it.
        $this->assertSame(0, getReservationUnseenMsgCount());
        $bell = NotificationService::for($this->desk);
        $this->assertSame(1, $bell['inquiries']);
        $this->assertSame(['1 unread inquiry'], array_column($bell['items'], 'title'));
    }

    public function test_a_message_on_an_open_reservation_counts_on_the_reservation_badge()
    {
        $code = $this->reserve();
        DB::table('circulations')->where('reservation_code', $code)->update(['status' => 2]);   // approved
        $this->patronWrites($code, 'What time do you open?');

        $this->assertSame(1, getReservationUnseenMsgCount());
        $bell = NotificationService::for($this->desk);
        $this->assertSame(1, $bell['inquiries']);   // listed in Inquiries as well
        $this->assertSame(['1 new reservation message'], array_column($bell['items'], 'title'));   // announced once
    }

    public function test_a_reply_to_a_reservation_that_does_not_exist_is_refused()
    {
        $this->actingAs($this->desk)->post('/admin/inquiry/reservation/reply', ['reservation_code' => 'RSV-NONE', 'message' => 'Hello'])->assertNotFound();
        $this->assertSame(0, DB::table('reservation_messages')->count());
    }
}
