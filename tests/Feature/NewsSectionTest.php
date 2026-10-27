<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\NewsSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The NEWS section of the OPAC home: featured articles as newspaper pages, latest first; "Read more"
 * shows one in a dialog with its external link opening in a new tab; an uploaded cover gets a
 * newspaper-size WebP copy so the section never loads the full upload.
 */
class NewsSectionTest extends TestCase
{
    use RefreshDatabase;

    private string $public;

    protected function setUp(): void
    {
        parent::setUp();
        // Uploads write files: a public folder of the test's own.
        $this->public = sys_get_temp_dir() . '/news-section-' . uniqid();
        foreach (['news_cover', 'thumb'] as $dir) File::ensureDirectoryExists("{$this->public}/$dir");
        $this->app->usePublicPath($this->public);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->public);
        parent::tearDown();
    }

    private function news(string $title, array $attrs = []): int
    {
        return DB::table('news')->insertGetId($attrs + [
            'news_title' => $title, 'news_body' => "<p>The story of $title.</p>", 'featured' => 'Yes',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_the_home_page_shows_featured_news_only_latest_first()
    {
        $this->news('Older story', ['created_at' => now()->subDays(3)]);
        $this->news('Newest story');
        $this->news('Not featured story', ['featured' => 'No']);

        $this->get('/home')->assertOk()
            ->assertSee('id="sec-news"', false)
            ->assertSee('href="#sec-news"', false)
            ->assertSeeInOrder(['Newest story', 'Older story'])
            ->assertDontSee('Not featured story')
            ->assertDontSee('Related News');
    }

    public function test_without_featured_news_the_section_and_its_menu_dot_are_left_out()
    {
        $this->news('Not featured story', ['featured' => 'No']);

        $this->get('/home')->assertOk()
            ->assertDontSee('id="sec-news"', false)
            ->assertDontSee('href="#sec-news"', false);
    }

    public function test_read_more_shows_the_article_with_its_link_opening_in_a_new_tab()
    {
        $id = $this->news('Seminar', ['news_body' => '<p>Join us <strong>Monday</strong>.</p>', 'redirect_url' => 'https://example.org/seminar']);

        $this->get("/news/$id")->assertOk()
            ->assertSee('Seminar')
            ->assertSee('Join us <strong>Monday</strong>.', false)
            ->assertSee('href="https://example.org/seminar" target="_blank" rel="noopener noreferrer"', false);
    }

    public function test_read_more_refuses_articles_that_are_not_featured_or_were_deleted()
    {
        $hidden = $this->news('Not featured', ['featured' => 'No']);
        $deleted = $this->news('Deleted', ['deleted_at' => now()]);

        $this->get("/news/$hidden")->assertNotFound();
        $this->get("/news/$deleted")->assertNotFound();
        $this->get('/news/999999')->assertNotFound();
    }

    public function test_read_more_cleans_articles_saved_before_bodies_were_cleaned()
    {
        // Written straight to the table, as rows saved before cleanNewsHtml were.
        $id = $this->news('Old row', [
            'news_body' => '<p>Text</p><script>alert(1)</script><img src="x" onerror="alert(2)">',
            'redirect_url' => 'javascript:alert(3)',
        ]);

        $page = $this->get("/news/$id")->assertOk()->getContent();
        $this->assertStringContainsString('<p>Text</p>', $page);
        $this->assertStringNotContainsString('<script>alert(1)', $page);
        $this->assertStringNotContainsString('onerror', $page);
        $this->assertStringNotContainsString('javascript:', $page);
    }

    public function test_an_uploaded_cover_gets_a_newspaper_size_webp_copy()
    {
        $staff = User::factory()->create(['usertype' => 'Developer']);
        $staff->assignRole(Role::findOrCreate('developer'));

        // A 2000 x 1500 photo, as a phone would send it.
        $path = "{$this->public}/upload-" . uniqid();
        $img = imagecreatetruecolor(2000, 1500);
        imagefilledrectangle($img, 0, 0, 999, 1499, imagecolorallocate($img, 120, 90, 40));
        imagejpeg($img, $path, 95);
        $cover = new UploadedFile($path, 'photo.jpg', null, null, true);

        $this->actingAs($staff)->post('/admin/news/create', [
            'news_title' => 'Seminar', 'news_body' => '<p>Body</p>', 'is_featured' => 1, 'news_cover' => $cover,
        ])->assertSessionHasNoErrors();

        $name = pathinfo(DB::table('news')->value('news_cover'), PATHINFO_FILENAME);
        $copy = "{$this->public}/news_print/$name.webp";
        $this->assertFileExists($copy);
        [$width, , , ] = $info = getimagesize($copy);
        $this->assertSame(800, $width);
        $this->assertSame('image/webp', $info['mime']);

        // The home page shows the copy, not the upload.
        $this->get('/home')->assertSee("news_print/$name.webp", false);
    }

    public function test_the_sample_seeder_adds_its_posts_once_and_never_in_production()
    {
        File::ensureDirectoryExists("{$this->public}/opac/img");
        File::copy(base_path('public/opac/img/about_1.jpg'), "{$this->public}/opac/img/about_1.jpg");

        // Run directly: `db:seed` would stop to ask before running in production at all.
        $seeder = fn () => $this->app->make(NewsSampleSeeder::class)->run();
        $this->app['env'] = 'production';
        $seeder();
        $this->assertSame(0, DB::table('news')->count());

        $this->app['env'] = 'testing';
        $seeder();
        $seeder();
        $this->assertSame(6, DB::table('news')->where('featured', 'Yes')->count());

        // The photo in the repo went through the upload steps: cover, thumbnail, newspaper copy.
        $cover = DB::table('news')->where('news_title', 'like', 'Ask the library%')->value('news_cover');
        $name = pathinfo($cover, PATHINFO_FILENAME);
        $this->assertFileExists("{$this->public}/$cover");
        $this->assertFileExists("{$this->public}/thumb/$name.jpg");
        $this->assertFileExists("{$this->public}/news_print/$name.webp");
    }
}
