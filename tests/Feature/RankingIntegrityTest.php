<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ViewCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The top-10 lists count people, not requests: a reload or a script adds nothing, a reader who downloads a
 * file again counts once, one 5-star vote can't top Highest Rating, and only a material's readers rate it.
 */
class RankingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private int $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->type = DB::table('materials')->insertGetId(['name' => 'Books']);
    }

    private function record(string $title): int
    {
        $id = DB::table('catalogs')->insertGetId(['material_type_id' => $this->type, 'field_data' => '[]', 'is_published' => 1]);
        DB::table('view_catalogs')->insert(['catalog_id' => $id, 'title_statement' => $title, 'material_type_id' => $this->type,
            'is_published' => 1, 'created_at' => now()]);
        return $id;
    }

    private function page(int $id): string
    {
        return '/' . recordPath(ViewCatalog::where('catalog_id', $id)->first());
    }

    private function patron(): User
    {
        return User::factory()->create(['usertype' => 'Patron']);
    }

    public function test_a_reload_counts_one_view_a_day()
    {
        $id = $this->record('Rubber production');
        $patron = $this->patron();

        $this->actingAs($patron)->withHeader('User-Agent', 'Mozilla/5.0 Test')->get($this->page($id))->assertOk();
        $this->actingAs($patron)->withHeader('User-Agent', 'Mozilla/5.0 Test')->get($this->page($id))->assertOk();
        $this->assertSame(1, DB::table('views')->where('catalog_id', $id)->count());

        $this->travel(1)->days();
        $this->flushSession();   // a new visit tomorrow (yesterday's session has timed out)
        $this->actingAs($patron)->withHeader('User-Agent', 'Mozilla/5.0 Test')->get($this->page($id))->assertOk();
        $this->assertSame(2, DB::table('views')->where('catalog_id', $id)->count());
    }

    public function test_guests_are_told_apart_by_address_and_browser_without_storing_either()
    {
        $id = $this->record('Rubber production');
        $visit = fn (string $ip, string $agent) => $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeader('User-Agent', $agent)->get($this->page($id))->assertOk();

        $visit('203.0.113.5', 'Mozilla/5.0 A');
        $visit('203.0.113.5', 'Mozilla/5.0 A');    // the same visitor reloading
        $visit('203.0.113.9', 'Mozilla/5.0 A');    // another address
        $visit('203.0.113.5', 'Mozilla/5.0 B');    // another browser at the same address
        $this->assertSame(3, DB::table('views')->where('catalog_id', $id)->count());

        $row = (array) DB::table('views')->first();
        $this->assertSame(['id', 'is_guest', 'user_id', 'catalog_id', 'created_at', 'updated_at'], array_keys($row));
    }

    public function test_most_viewed_counts_old_reloads_once_a_day()
    {
        $id = $this->record('Rubber production');
        $patron = $this->patron();
        $rows = [];
        foreach ([0, 0, 0, 0, 0, 1, 1] as $daysAgo) $rows[] = ['catalog_id' => $id, 'user_id' => $patron->id, 'is_guest' => 'NO', 'created_at' => now()->subDays($daysAgo)];
        foreach (range(1, 3) as $i) $rows[] = ['catalog_id' => $id, 'user_id' => null, 'is_guest' => 'YES', 'created_at' => now()];
        DB::table('views')->insert($rows);   // saved before views were counted once a day: every reload a row

        $this->assertSame(5, countNum('view', $id));   // 2 days of the patron + 3 guest rows
        $this->get('/home')->assertSee('5 views');
    }

    public function test_most_downloaded_counts_readers_not_files()
    {
        $once = $this->record('Downloaded by two');
        $again = $this->record('Downloaded three times by one');
        [$ana, $ben] = [$this->patron(), $this->patron()];
        foreach (range(1, 3) as $i) DB::table('downloads')->insert(['catalog_id' => $again, 'user_id' => $ana->id, 'created_at' => now()]);
        foreach ([$ana, $ben] as $p) DB::table('downloads')->insert(['catalog_id' => $once, 'user_id' => $p->id, 'created_at' => now()]);

        $this->get('/home')->assertSeeInOrder(['Downloaded by two', '2 readers downloaded', 'Downloaded three times by one', '1 reader downloaded'], false);
        $this->assertSame(1, countNum('download', $again));
    }

    public function test_one_five_star_vote_does_not_top_highest_rating()
    {
        $single = $this->record('One five-star vote');
        $many = $this->record('Four votes, mostly fives');
        $low = $this->record('Three low votes');
        $vote = fn (int $id, int $stars) => DB::table('ratings')->insert(['catalog_id' => $id, 'user_id' => $this->patron()->id, 'rating' => $stars, 'created_at' => now()]);
        $vote($single, 5);
        foreach ([5, 5, 5, 4] as $s) $vote($many, $s);
        foreach ([2, 2, 2] as $s) $vote($low, $s);

        // Library average 3.75 (m = 3): 4 votes of 4.75 -> 4.32 beats 1 vote of 5 -> 4.06; the low one is last.
        $this->assertSame([$many, $single, $low], array_map(fn ($i) => (int) $i['catalog_id'], getHighestRated(10)));
        $this->get('/home')->assertSee('★ 5.0 · 1 rating');   // the shelf still shows the plain average
    }

    public function test_only_a_materials_readers_can_rate_it()
    {
        $id = $this->record('Rubber production');
        $patron = $this->patron();

        $this->actingAs($patron)->post('/review/submit', ['catalog_id' => $id, 'rating_input' => 5])
            ->assertRedirect()->assertSessionHas('error', 'Read or download this material first, then you can rate it.');
        $this->actingAs($patron)->postJson('/review/quick', ['catalog_id' => $id, 'rating' => 5])->assertForbidden();
        $this->actingAs($patron)->get($this->page($id))->assertSee('Read or download it first, then you can rate it.');
        $this->assertSame(0, DB::table('ratings')->count());

        DB::table('reads')->insert(['catalog_id' => $id, 'user_id' => $patron->id, 'created_at' => now()]);
        $this->actingAs($patron)->postJson('/review/quick', ['catalog_id' => $id, 'rating' => 5])->assertOk();
        $this->assertSame(1, DB::table('ratings')->count());
    }

    public function test_downloads_are_rate_limited()
    {
        $route = collect(Route::getRoutes())->first(fn ($r) => $r->uri() === 'download' && in_array('POST', $r->methods()));
        $this->assertContains('throttle:30,1', $route->gatherMiddleware());
    }
}
