<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * A copy's status must follow its circulation ticket: Reserved only while an approved ticket
 * holds it, Borrowed while it is out, Available otherwise. Statuses — copies: 1 Available,
 * 2 Reserved, 3 Borrowed; circulations: 1 For approval, 2 Approved, 3 Checked out,
 * 4 Declined, 5 Returned.
 */
class CirculationIntegrityTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    private User $staff;
    private int $catalogId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData('copies_status', 'circulation_status');

        $this->staff = User::factory()->create(['usertype' => 'Developer']);
        $this->staff->assignRole(Role::findOrCreate('developer'));

        $material = DB::table('materials')->insertGetId(['name' => 'Book']);
        $this->catalogId = DB::table('catalogs')->insertGetId(['material_type_id' => $material, 'field_data' => '[]']);
    }

    private function copy(string $barcode, int $status): void
    {
        DB::table('copies')->insert(['catalog_id' => $this->catalogId, 'circulation_number' => $barcode, 'title' => 'A title', 'status' => $status]);
    }

    private function ticket(string $code, int $status = 1, ?string $barcode = null, ?int $userId = null): void
    {
        $userId ??= User::factory()->create(['usertype' => 'Patron'])->id;
        DB::table('circulations')->insert([
            'user_id' => $userId, 'catalog_id' => $this->catalogId, 'status' => $status, 'circulation_number' => $barcode,
            'reservation_code' => "R-$code", 'transaction_code' => "T-$code",
        ]);
        DB::table('circulation_fine_history')->insert(['transaction_code' => "T-$code"]);
    }

    private function copyStatus(string $barcode): int
    {
        return (int) DB::table('copies')->where('circulation_number', $barcode)->value('status');
    }

    private function ticketStatus(string $code): int
    {
        return (int) DB::table('circulations')->where('transaction_code', "T-$code")->value('status');
    }

    private function reservationAction(int $action, string $code, ?string $barcode = null)
    {
        return $this->actingAs($this->staff)->post('/admin/reservation/action', [
            'reservation_action' => $action, 'reservation_code' => "R-$code", 'circulation_number' => $barcode,
        ]);
    }

    public function test_a_copy_can_be_approved_for_only_one_reservation()
    {
        $this->copy('C1', 1);
        $this->ticket('A');
        $this->ticket('B');

        $this->reservationAction(1, 'A', 'C1')->assertSessionHas('header', 'Success');
        $this->assertSame(2, $this->ticketStatus('A'));
        $this->assertSame(2, $this->copyStatus('C1'));

        $this->reservationAction(1, 'B', 'C1')->assertSessionHas('header', 'Failed');
        $this->assertSame(1, $this->ticketStatus('B'));
        $this->assertSame('C1', DB::table('circulations')->where('transaction_code', 'T-A')->value('circulation_number'));
    }

    public function test_staff_are_recorded_by_account_and_shown_by_name()
    {
        $this->copy('C1', 1);
        $this->ticket('A');

        $this->reservationAction(1, 'A', 'C1');

        $this->assertSame($this->staff->id, (int) DB::table('circulations')->where('transaction_code', 'T-A')->value('approved_by'));
        $this->actingAs($this->staff)->getJson('/admin/reservation/transaction-json?transaction_code=T-A')
            ->assertJson(['approved_by' => $this->staff->name, 'declined_by' => 'Not found']);
    }

    public function test_declining_an_approved_reservation_returns_its_copy()
    {
        $this->copy('C1', 2);
        $this->ticket('A', 2, 'C1');

        $this->reservationAction(2, 'A');

        $this->assertSame(4, $this->ticketStatus('A'));
        $this->assertSame(1, $this->copyStatus('C1'));
    }

    public function test_reverting_an_approval_returns_the_copy_the_ticket_held()
    {
        $this->copy('C1', 2);
        $this->copy('C2', 2);
        $this->ticket('A', 2, 'C1');

        $this->reservationAction(4, 'A', 'C2');   // the form's barcode is ignored

        $this->assertSame(1, $this->ticketStatus('A'));
        $this->assertSame(1, $this->copyStatus('C1'));
        $this->assertSame(2, $this->copyStatus('C2'));
    }

    public function test_banning_a_patron_declines_their_tickets_and_frees_held_copies()
    {
        $patron = User::factory()->create(['usertype' => 'Patron']);
        $this->copy('C1', 2);
        $this->copy('C2', 3);
        $this->ticket('A', 2, 'C1', $patron->id);   // approved: holds C1
        $this->ticket('B', 1, null, $patron->id);   // pending
        $this->ticket('C', 3, 'C2', $patron->id);   // out on loan: untouched

        $this->actingAs($this->staff)->post('/admin/user/toggle-flag', ['user_id' => $patron->id, 'flag' => 'banned'])
            ->assertJson(['ok' => true, 'value' => true, 'declined' => 2]);

        $this->assertSame([4, 4, 3], [$this->ticketStatus('A'), $this->ticketStatus('B'), $this->ticketStatus('C')]);
        $this->assertSame(1, $this->copyStatus('C1'));
        $this->assertSame(3, $this->copyStatus('C2'));
    }

    public function test_a_reserved_copy_is_borrowed_when_checked_out_and_available_when_returned()
    {
        $this->copy('C1', 2);
        $this->ticket('A', 2, 'C1');

        $this->actingAs($this->staff)->post('/admin/check-out/update', [
            'transaction_code' => 'T-A', 'duedate' => now()->addWeek()->format('Y-m-d\TH:i'), 'overdue_fine_rate' => '5',
        ])->assertSessionHas('success');
        $this->assertSame(3, $this->ticketStatus('A'));
        $this->assertSame(3, $this->copyStatus('C1'));

        // What the check-in screen posts when the fine boxes are left empty and no reason is picked.
        $this->actingAs($this->staff)->post('/admin/check-in/store', [
            'transaction_code' => 'T-A', 'circulation_code' => 'C1', 'library_code' => 'N/A',
            'total_fine' => '12.5', 'overdue_fine' => '12.5', 'waive_amount' => 'NaN', 'other_fine' => 'NaN',
            'total_hrs_overdue' => '3', 'circulation_fine_id' => 'Select description...',
        ])->assertSessionHas('complete');

        $this->assertSame(5, $this->ticketStatus('A'));
        $this->assertSame(1, $this->copyStatus('C1'));
        $loan = DB::table('circulations')->where('transaction_code', 'T-A')->first();
        $this->assertSame(['12.50', '5.00', null], [$loan->total_fine_paid, $loan->overdue_fine_rate, $loan->waived_amount]);
        $fines = DB::table('circulation_fine_history')->where('transaction_code', 'T-A')->first();
        $this->assertSame([3, null, null], [(int) $fines->overdue_total_hours, $fines->other_fine_amount, $fines->circulation_fine_id]);
    }

    public function test_walk_in_check_out_takes_only_an_available_copy()
    {
        $this->copy('C1', 2);
        $this->copy('C2', 1);
        $form = ['patron_library_code' => 'N/A', 'user_id' => 'Patron not found', 'overdue_fine_rate' => '5',
                 'duedate' => now()->addWeek()->format('Y-m-d\TH:i')];

        $this->actingAs($this->staff)->post('/admin/check-out/store', $form + ['circulation_number' => 'C1'])->assertSessionHas('error');
        $this->assertSame(2, $this->copyStatus('C1'));
        $this->assertSame(0, DB::table('circulations')->count());

        $this->actingAs($this->staff)->post('/admin/check-out/store', $form + ['circulation_number' => 'C2'])->assertSessionHas('transactionCode');
        $this->assertSame(3, $this->copyStatus('C2'));
        $loan = DB::table('circulations')->first();
        $this->assertSame([$this->catalogId, 'C2', null], [(int) $loan->catalog_id, $loan->circulation_number, $loan->user_id]);
        $this->assertSame(1, DB::table('circulation_fine_history')->where('transaction_code', $loan->transaction_code)->count());
    }
}
