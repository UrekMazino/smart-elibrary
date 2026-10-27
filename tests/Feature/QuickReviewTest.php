<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuickReviewTest extends TestCase
{
    use RefreshDatabase;

    /** A record; published (shown in the OPAC) or held. */
    private function record(bool $published = true): int
    {
        $material = DB::table('materials')->insertGetId(['name' => 'Book']);
        $id = DB::table('catalogs')->insertGetId(['material_type_id' => $material, 'field_data' => '[]', 'is_published' => (int) $published]);
        DB::table('view_catalogs')->insert(['catalog_id' => $id, 'title_statement' => 'Rice farming', 'material_type_id' => $material, 'is_published' => (int) $published]);
        return $id;
    }

    public function test_guests_can_not_use_the_quick_review_endpoints()
    {
        $this->getJson('/review/quick/1')->assertUnauthorized();
        $this->postJson('/review/quick', ['catalog_id' => 1, 'rating' => 5])->assertUnauthorized();
        $this->postJson('/review/quick/1/skip')->assertUnauthorized();
    }

    public function test_not_now_is_kept_on_the_server_for_that_patron_only()
    {
        $record = $this->record();
        $ana = User::factory()->create(['usertype' => 'Patron']);
        $ben = User::factory()->create(['usertype' => 'Patron']);

        $this->actingAs($ana)->getJson("/review/quick/$record")->assertExactJson(['reviewed' => false, 'skipped' => false]);
        $this->actingAs($ana)->postJson("/review/quick/$record/skip")->assertOk();
        $this->actingAs($ana)->postJson("/review/quick/$record/skip")->assertOk();   // twice is fine

        $this->actingAs($ana)->getJson("/review/quick/$record")->assertJson(['skipped' => true]);
        $this->actingAs($ben)->getJson("/review/quick/$record")->assertJson(['skipped' => false]);
        $this->assertSame(1, DB::table('review_prompt_skips')->count());
    }

    public function test_not_now_needs_a_published_record()
    {
        $patron = User::factory()->create(['usertype' => 'Patron']);

        $this->actingAs($patron)->postJson('/review/quick/' . $this->record(false) . '/skip')->assertNotFound();
        $this->actingAs($patron)->postJson('/review/quick/999999/skip')->assertNotFound();
        $this->assertSame(0, DB::table('review_prompt_skips')->count());
    }

    public function test_not_now_goes_with_the_account()
    {
        $record = $this->record();
        $patron = User::factory()->create(['usertype' => 'Patron']);
        $this->actingAs($patron)->postJson("/review/quick/$record/skip")->assertOk();

        $patron->forceDelete();

        $this->assertSame(0, DB::table('review_prompt_skips')->count());
    }

    public function test_the_popup_keeps_nothing_about_the_patrons_reading_in_the_browser()
    {
        $patron = User::factory()->create(['usertype' => 'Patron']);

        $this->actingAs($patron)->get('/home')->assertOk()
            ->assertSee("/skip'", false)                                  // "Not now" goes to the server
            ->assertDontSee('localStorage.setItem', false)
            ->assertSee("k.indexOf('qr-skip:') === 0", false);            // and old browser copies are removed
    }
}
