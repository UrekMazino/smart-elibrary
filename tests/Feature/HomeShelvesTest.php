<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The home page's shelf browser holds what was the Highlights section: Most Downloaded, Most Viewed and
 * Highest Rating come first (the shelves in view when the page opens), then Recent Uploads, then a shelf
 * per material type.
 */
class HomeShelvesTest extends TestCase
{
    use RefreshDatabase;

    private const HEAD = '<div class="shelf-col-head">';

    /** Three published records of one type, newest first; returns their ids. */
    private function records(): array
    {
        $type = DB::table('materials')->insertGetId(['name' => 'Books']);
        $ids = [];
        foreach (['Alpha report', 'Beta thesis', 'Gamma manual'] as $i => $title) {
            $id = DB::table('catalogs')->insertGetId(['material_type_id' => $type, 'field_data' => '[]', 'is_published' => 1]);
            DB::table('view_catalogs')->insert(['catalog_id' => $id, 'title_statement' => $title, 'material_type_id' => $type,
                'is_published' => 1, 'created_at' => now()->subDays($i)]);
            $ids[] = $id;
        }
        return $ids;
    }

    public function test_the_highlight_shelves_come_first_and_the_highlights_section_is_gone()
    {
        [$alpha, $beta, $gamma] = $this->records();
        $user = User::factory()->create();
        DB::table('downloads')->insert(['catalog_id' => $beta, 'user_id' => $user->id, 'created_at' => now()]);
        DB::table('views')->insert(['catalog_id' => $gamma, 'user_id' => $user->id, 'created_at' => now()]);
        DB::table('ratings')->insert(['catalog_id' => $alpha, 'user_id' => $user->id, 'rating' => 5, 'created_at' => now()]);

        $this->get('/home')->assertOk()
            ->assertSeeInOrder([self::HEAD . 'Most Downloaded', self::HEAD . 'Most Viewed', self::HEAD . 'Highest Rating',
                self::HEAD . 'Recent Uploads', self::HEAD . 'Books'], false)
            ->assertSee('class="col-rank r1" aria-hidden="true"><i class="rk-crown"></i><b>1</b>', false)   // the #1 crest wears a crown
            ->assertSee('1 download')
            ->assertSee('★ 5.0 · 1 rating')
            ->assertDontSee('id="sec-misc"', false)
            ->assertDontSee('data-label="Highlights"', false);
    }

    public function test_ranks_read_left_to_right_type_shelves_go_by_title_and_carry_medals()
    {
        $type = DB::table('materials')->insertGetId(['name' => 'Books']);
        $user = User::factory()->create();
        $id = [];
        // Added in this order (newest first), so neither the date nor the views put them in title order.
        foreach (['Delta', 'alpha', 'Charlie', 'Bravo', 'echo'] as $i => $title) {
            $id[$title] = DB::table('catalogs')->insertGetId(['material_type_id' => $type, 'field_data' => '[]', 'is_published' => 1]);
            DB::table('view_catalogs')->insert(['catalog_id' => $id[$title], 'title_statement' => $title, 'material_type_id' => $type,
                'is_published' => 1, 'created_at' => now()->subDays($i)]);
        }
        // Most Viewed: Delta #1, alpha #2, Charlie #3, Bravo #4, echo #5. Most Downloaded: alpha #1.
        foreach (['Delta' => 5, 'alpha' => 4, 'Charlie' => 3, 'Bravo' => 2, 'echo' => 1] as $title => $views) {
            for ($v = 0; $v < $views; $v++) DB::table('views')->insert(['catalog_id' => $id[$title], 'user_id' => $user->id, 'created_at' => now()]);
        }
        DB::table('downloads')->insert(['catalog_id' => $id['alpha'], 'user_id' => $user->id, 'created_at' => now()]);

        $page = $this->get('/home')->assertOk()
            // A ranking reads left to right, then down: 1 2 / 3 4 / 5, so in the page 1..5 in order.
            ->assertSeeInOrder([self::HEAD . 'Most Viewed', 'col-rank r1', '<b>1</b>', 'col-rank r2', '<b>2</b>', 'col-rank r3', '<b>3</b>',
                'col-rank rn', '<b>4</b>', 'col-rank rn', '<b>5</b>', self::HEAD . 'Recent Uploads'], false)
            // The type shelf: by title A-Z (case doesn't matter), and alpha's medals: gold for #1, silver for #2.
            ->assertSeeInOrder([self::HEAD . 'Books', 'class="medal m-gold" title="#1 Most Downloaded">1<',
                'class="medal m-silver" title="#2 Most Viewed">2<', 'alt="alpha"',
                '#1 Most Downloaded<br>#2 Most Viewed', 'alt="Bravo"', 'alt="Charlie"', 'alt="Delta"', 'alt="echo"'], false)
            ->getContent();

        // The highlight shelves show ranks, not medals.
        $highlights = substr($page, strpos($page, self::HEAD . 'Most Downloaded'), strpos($page, self::HEAD . 'Books') - strpos($page, self::HEAD . 'Most Downloaded'));
        $this->assertStringNotContainsString('class="medal ', $highlights);
    }

    public function test_the_record_page_and_quick_view_show_its_medals()
    {
        [$alpha, $beta] = $this->records();
        $user = User::factory()->create();
        DB::table('downloads')->insert(['catalog_id' => $beta, 'user_id' => $user->id, 'created_at' => now()]);
        DB::table('ratings')->insert(['catalog_id' => $beta, 'user_id' => $user->id, 'rating' => 4, 'created_at' => now()]);

        $record = '/' . recordPath(\App\Models\ViewCatalog::where('catalog_id', $beta)->first());
        $this->get($record)->assertOk()
            ->assertSeeInOrder(['class="medal m-gold" title="#1 Most Downloaded">1<', 'class="medal m-gold" title="#1 Highest Rating">1<'], false)
            ->assertSeeInOrder(['<li>#1 in Most Downloaded</li>', '<li>#1 in Highest Rating</li>'], false);
        $this->get("/search/quick/$beta")->assertOk()->assertSee('#1 Most Downloaded')->assertSee('#1 Highest Rating');

        // A record in none of the lists: no medals, and no medal styles on its page.
        $this->get('/' . recordPath(\App\Models\ViewCatalog::where('catalog_id', $alpha)->first()))->assertOk()
            ->assertDontSee('class="rank-medals', false)->assertDontSee('.rank-medals {', false);
    }

    public function test_a_highlight_list_with_nothing_in_it_gets_no_shelf()
    {
        $this->records();   // no downloads, views or ratings yet

        $this->get('/home')->assertOk()
            ->assertDontSee(self::HEAD . 'Most Downloaded', false)
            ->assertDontSee(self::HEAD . 'Highest Rating', false)
            ->assertSeeInOrder([self::HEAD . 'Recent Uploads', self::HEAD . 'Books'], false);
    }
}
