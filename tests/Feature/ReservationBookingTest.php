<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/** A reservation's visit is a real date and time, on a weekday between 8 AM and 5 PM. */
class ReservationBookingTest extends TestCase
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
        $this->catalogId = DB::table('catalogs')->insertGetId(['material_type_id' => $material, 'field_data' => '[]']);
    }

    /** What the booking pickers post: "10/19/2026" and "9:00 am". */
    private function book(Carbon $day, string $time)
    {
        return $this->actingAs($this->patron)->post('/request-reservation', [
            'catalog_id' => $this->catalogId, 'title_statement' => 'A title',
            'visit_scheddate' => $day->format('m/d/Y'), 'visit_schedtime' => $time,
        ]);
    }

    public function test_a_booking_is_stored_as_a_date_and_a_time()
    {
        $monday = now()->next(Carbon::MONDAY);

        $this->book($monday, '9:15 am')->assertOk()->assertSessionHasNoErrors();

        $visit = DB::table('circulation_reservations')->first();
        $this->assertSame([$monday->toDateString(), '09:15:00'], [$visit->visit_date, $visit->visit_time]);
        $this->assertSame(1, DB::table('circulations')->where('status', 1)->count());
    }

    public function test_visits_are_on_weekdays_between_8_and_5_and_not_in_the_past()
    {
        $this->book(now()->next(Carbon::SATURDAY), '9:00 am')->assertSessionHasErrors(['visit_scheddate' => 'Visits are Monday to Friday. Please pick a weekday.']);
        $this->book(now()->subWeek()->startOfWeek(), '9:00 am')->assertSessionHasErrors(['visit_scheddate' => "The visit date can't be in the past."]);
        $this->book(now()->next(Carbon::MONDAY), '7:30 pm')->assertSessionHasErrors(['visit_schedtime' => 'Visits are between 8:00 AM and 5:00 PM.']);

        $this->assertSame(0, DB::table('circulations')->count());
    }
}
