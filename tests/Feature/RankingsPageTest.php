<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Rankings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * Administration → Utilities → Rankings (RankingsController, App\Services\Rankings): the home page's three top-10
 * lists with the numbers behind each rank and the next ones down, for the last 90 days or all time, and
 * "Refresh the home page".
 */
class RankingsPageTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    private int $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
        $this->type = DB::table('materials')->insertGetId(['name' => 'Books']);
    }

    private function staff(string $role): User
    {
        return tap(User::factory()->create(['usertype' => $role === 'developer' ? 'Developer' : 'Administrator']))->assignRole($role);
    }

    private function record(string $title): int
    {
        $id = DB::table('catalogs')->insertGetId(['material_type_id' => $this->type, 'field_data' => '[]', 'is_published' => 1]);
        DB::table('view_catalogs')->insert(['catalog_id' => $id, 'title_statement' => $title, 'material_type_id' => $this->type,
            'is_published' => 1, 'created_at' => now()]);
        return $id;
    }

    public function test_super_admin_system_admin_and_developer_have_it_other_staff_do_not()
    {
        foreach (['super_admin', 'system_admin', 'developer'] as $role) {
            $this->actingAs($this->staff($role))->get('/admin/utilities/rankings')->assertOk()->assertSee('RANKINGS');
        }
        $this->get('/admin/utilities')->assertSee('Rankings');

        $this->actingAs($this->staff('cataloging_admin'))->get('/admin/utilities/rankings')->assertForbidden();
        $this->post('/admin/utilities/rankings/refresh')->assertForbidden();
    }

    public function test_each_list_shows_the_numbers_behind_every_rank_and_the_next_ones_down()
    {
        // Twelve materials: the first downloaded by 12 readers, the next by 11, and so on; the first reader twice.
        $readers = User::factory()->count(12)->create(['usertype' => 'Patron']);
        $records = collect(range(1, 12))->map(fn ($i) => $this->record("Material $i"));
        foreach ($records as $k => $id) {
            foreach ($readers->take(12 - $k) as $reader) {
                DB::table('downloads')->insert(['catalog_id' => $id, 'user_id' => $reader->id, 'created_at' => now()->subDay()]);
            }
        }
        DB::table('downloads')->insert(['catalog_id' => $records[0], 'user_id' => $readers[0]->id, 'created_at' => now()]);
        $old = $this->record('An old favourite');   // 13 readers, 100 days ago: all time only
        foreach (User::factory()->count(13)->create(['usertype' => 'Patron']) as $reader) {
            DB::table('downloads')->insert(['catalog_id' => $old, 'user_id' => $reader->id, 'created_at' => now()->subDays(100)]);
        }
        // Views: two readers and three guests.
        DB::table('views')->insert([
            ['catalog_id' => $records[4], 'user_id' => $readers[0]->id, 'is_guest' => 'NO', 'created_at' => now()],
            ['catalog_id' => $records[4], 'user_id' => $readers[1]->id, 'is_guest' => 'NO', 'created_at' => now()],
        ] + array_fill(2, 3, ['catalog_id' => $records[4], 'user_id' => null, 'is_guest' => 'YES', 'created_at' => now()]));
        // Ratings: five 5s beat a single 5.
        foreach ($readers->take(5) as $reader) {
            DB::table('ratings')->insert(['catalog_id' => $records[1], 'user_id' => $reader->id, 'rating' => 5, 'created_at' => now()]);
        }
        DB::table('ratings')->insert(['catalog_id' => $records[2], 'user_id' => $readers[0]->id, 'rating' => 5, 'created_at' => now()]);
        DB::table('ratings')->insert(['catalog_id' => $records[3], 'user_id' => $readers[0]->id, 'rating' => 2, 'created_at' => now()]);

        $this->actingAs($this->staff('super_admin'))->get('/admin/utilities/rankings')->assertOk()
            ->assertSeeInOrder(['Last 90 days', 'Views', '5', '3 by guests', 'Downloads', '78', 'readers', '79 files', 'Ratings', '7', 'average ★ 4.57'])
            ->assertSeeInOrder(['Most Downloaded', 'Material 1', '13 files', '12', 'readers', 'Material 10', 'Just outside the top 10', 'Material 11', 'Material 12', 'Most Viewed'])
            ->assertSeeInOrder(['Most Viewed', 'Material 5', '2 signed in · 3 guests', '5', 'views', 'Highest Rating'])
            ->assertSeeInOrder(['Highest Rating', 'Material 2', '★ 5.0 · 5 ratings', '4.84', 'score', 'Material 3', '★ 5.0 · 1 rating', 'C = 4.57'])
            ->assertDontSee('An old favourite');
        $this->get('/admin/utilities/rankings?window=all')->assertOk()
            ->assertSeeInOrder(['Most Downloaded', 'An old favourite', '13', 'Material 1', 'Most Viewed']);

        // The home page's lists are these lists' first ten.
        $this->assertSame(Rankings::downloaded('recent', Rankings::TOP)->pluck('catalog_id')->all(), array_column(getMostDownloaded(), 'catalog_id'));
        $this->assertSame(Rankings::rated('all', Rankings::TOP)->pluck('catalog_id')->all(), array_column(getHighestRated(10, 'all'), 'catalog_id'));
    }

    public function test_it_counts_who_is_left_out_and_refreshes_the_home_page()
    {
        $admin = $this->staff('super_admin');
        $banned = User::factory()->create(['usertype' => 'Patron']);
        $banned->is_banned = true;
        $banned->save();
        DB::table('ranking_exclusions')->insert(['user_id' => User::factory()->create(['usertype' => 'Patron'])->id, 'excluded_by' => $admin->id, 'created_at' => now()]);

        $this->actingAs($admin)->get('/admin/utilities/rankings')->assertOk()
            ->assertSeeInOrder(['Left out', '2', '1 banned · 1 taken out'])->assertSee('Nothing yet in the last 90 days.');

        cache()->put('home_top_downloaded_recent', ['stale'], 600);
        $this->from('/admin/utilities/rankings')->post('/admin/utilities/rankings/refresh')
            ->assertRedirect('/admin/utilities/rankings')->assertSessionHas('success', 'The home page now shows the lists as they are below.');
        $this->assertFalse(cache()->has('home_top_downloaded_recent'));
    }
}
