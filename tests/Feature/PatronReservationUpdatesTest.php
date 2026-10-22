<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * The account menu's Reservations badge counts what the library changed on a patron's reservations
 * (approved, declined, checked out, returned) until the patron opens their Reservations page, which
 * marks each changed card "Updated" on that visit.
 */
class PatronReservationUpdatesTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    private User $patron;
    private int $catalogId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData('circulation_status');
        $this->patron = User::factory()->create(['usertype' => 'Patron']);
        $material = DB::table('materials')->insertGetId(['name' => 'Book']);
        $this->catalogId = DB::table('catalogs')->insertGetId(['material_type_id' => $material, 'field_data' => '[]', 'is_published' => 1]);
        DB::table('view_catalogs')->insert(['catalog_id' => $this->catalogId, 'title_statement' => 'A title',
            'material_type_id' => $material, 'is_published' => 1]);
    }

    /** The patron's own request, made the way the OPAC makes it. */
    private function reserve(): int
    {
        $this->actingAs($this->patron)->post('/request-reservation', [
            'catalog_id' => $this->catalogId, 'title_statement' => 'A title',
            'visit_scheddate' => now()->next(Carbon::MONDAY)->format('m/d/Y'), 'visit_schedtime' => '9:00 am',
        ])->assertSessionHasNoErrors();

        return (int) DB::table('circulations')->value('id');
    }

    /** The account menu's Reservations entry on an OPAC page: its badge count, or 0. */
    private function menuBadge(): int
    {
        $html = $this->actingAs($this->patron)->get('/patron/to-review')->assertOk()->getContent();
        preg_match('#Reservations <span class="badge[^"]*">(\d+)</span>#', $html, $m);
        return (int) ($m[1] ?? 0);
    }

    private function bell(): array
    {
        return array_column(NotificationService::for($this->patron->fresh())['items'], 'text', 'title');
    }

    public function test_the_patrons_own_request_is_not_an_update()
    {
        $this->reserve();

        $this->assertSame(0, $this->menuBadge());
        $this->assertSame([], $this->bell());
    }

    public function test_a_change_by_the_library_shows_until_the_patron_opens_reservations()
    {
        $id = $this->reserve();
        DB::table('circulations')->where('id', $id)->update(['status' => 4]);   // declined by staff

        $this->assertSame(1, $this->menuBadge());
        $this->assertSame(['1 reservation updated' => 'Declined'], $this->bell());

        $page = $this->actingAs($this->patron)->get('/patron/transactions')->assertOk();
        $this->assertSame([$id], $page->viewData('updated'));
        $page->assertSee('<span class="tx-updated">Updated</span>', false);

        // Seen: no badge, and the card is no longer marked.
        $this->assertSame(0, $this->menuBadge());
        $this->assertSame([], $this->bell());
        $this->actingAs($this->patron)->get('/patron/transactions')->assertDontSee('<span class="tx-updated">', false);
    }

    public function test_an_approval_counts_once_in_the_bell_as_ready_for_pickup()
    {
        $id = $this->reserve();
        DB::table('circulations')->where('id', $id)->update(['status' => 2]);

        $this->assertSame(1, $this->menuBadge());
        $this->assertSame(['1 reservation ready for pickup'], array_keys($this->bell()));
    }

    public function test_a_loan_the_library_records_is_an_update_too()
    {
        DB::table('circulations')->insert(['user_id' => $this->patron->id, 'catalog_id' => $this->catalogId,
            'transaction_code' => 'TRN-WALKIN', 'status' => 3, 'created_at' => now(), 'updated_at' => now()]);

        $this->assertSame(1, $this->menuBadge());
        $this->assertSame(['1 reservation updated' => 'Checked out'], $this->bell());
    }
}
