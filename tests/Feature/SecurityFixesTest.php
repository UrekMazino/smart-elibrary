<?php

namespace Tests\Feature;

use App\Models\Multimedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * The security review of 2026-10-20: patrons only get public records' full texts, reservations
 * and reviews; patron-written text is shown as text; dead endpoints that leaked data or accepted
 * any upload are gone; uploads are named by what they are; pages send browser protections.
 */
class SecurityFixesTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    private User $patron;
    private User $staff;
    private int $material;
    private string $public;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData('copies_status', 'circulation_status');

        $this->patron = User::factory()->create(['usertype' => 'Patron']);
        $this->staff = User::factory()->create(['usertype' => 'Developer']);
        $this->staff->assignRole(Role::findOrCreate('developer'));
        $this->material = DB::table('materials')->insertGetId(['name' => 'Book']);

        // Downloads and uploads read and write files: a public folder and a private disk of the test's own.
        Storage::fake('private');
        $this->public = sys_get_temp_dir() . '/security-fixes-' . uniqid();
        foreach (['news_cover', 'thumb', 'frontpages', 'js/admin'] as $dir) File::ensureDirectoryExists("{$this->public}/$dir");
        File::copy(public_path('js/admin/catalog-view-record.js'), "{$this->public}/js/admin/catalog-view-record.js");
        $this->app->usePublicPath($this->public);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->public);
        parent::tearDown();
    }

    /** A record with a full text; published or held. */
    private function record(string $title, bool $published = true): int
    {
        $id = DB::table('catalogs')->insertGetId(['material_type_id' => $this->material, 'field_data' => '[]', 'is_published' => (int) $published]);
        DB::table('view_catalogs')->insert(['catalog_id' => $id, 'title_statement' => $title,
            'material_type_id' => $this->material, 'is_published' => (int) $published]);
        Storage::disk('private')->put("pdf/ft-$id.pdf", "%PDF-1.4\n%%EOF\n");
        Multimedia::create(['catalog_id' => $id, 'FileName' => "ft-$id.pdf", 'FileLocation' => "pdf/ft-$id.pdf", 'FileType' => 'pdf']);
        return $id;
    }

    /** A real file sent as $sentAs: its type is read from the content (a fake file reports the name's). */
    private function upload(string $content, string $sentAs): UploadedFile
    {
        $path = "{$this->public}/upload-" . uniqid();
        file_put_contents($path, $content);
        return new UploadedFile($path, $sentAs, null, null, true);
    }

    private function realImage(string $sentAs): UploadedFile
    {
        ob_start();
        imagejpeg(imagecreatetruecolor(20, 20));
        return $this->upload(ob_get_clean(), $sentAs);
    }

    public function test_a_held_record_cannot_be_downloaded()
    {
        $public = $this->record('Public record');
        $held = $this->record('Held record', false);

        $this->actingAs($this->patron)->post('/download', ['holdings_download_id' => $public])->assertDownload();
        $this->actingAs($this->patron)->post('/download', ['holdings_download_id' => $held])->assertNotFound();

        $this->assertSame([$public], DB::table('downloads')->pluck('catalog_id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_a_reservation_keeps_the_catalogue_title_and_held_records_cannot_be_reserved()
    {
        $public = $this->record('Rubber production');
        $held = $this->record('Held record', false);
        $visit = ['visit_scheddate' => now()->next('Monday')->format('m/d/Y'), 'visit_schedtime' => '9:00 am'];

        $this->actingAs($this->patron)->post('/request-reservation', $visit + [
            'catalog_id' => $public, 'title_statement' => '<img src=x onerror=alert(1)>',
        ])->assertOk()->assertSee('Thank You!', false);
        $this->actingAs($this->patron)->post('/request-reservation', $visit + ['catalog_id' => $held])->assertNotFound();

        $this->assertSame(['Rubber production'], DB::table('circulation_reservations')->pluck('title_statement')->all());
        $this->assertSame(1, DB::table('circulations')->count());
    }

    public function test_a_review_rating_is_one_to_five_stars_of_a_public_record()
    {
        $public = $this->record('Rubber production');
        $held = $this->record('Held record', false);

        $this->actingAs($this->patron)->post('/review/submit', ['catalog_id' => $public, 'rating_input' => 1000])
            ->assertSessionHasErrors('rating_input');
        $this->actingAs($this->patron)->post('/review/submit', ['catalog_id' => $held, 'rating_input' => 4])->assertNotFound();
        $this->actingAs($this->patron)->post('/review/submit', ['catalog_id' => $public, 'rating_input' => 4, 'remarks' => 'Useful.'])
            ->assertRedirect();

        $this->assertSame([[$public, 4]], DB::table('ratings')->get(['catalog_id', 'rating'])->map(fn ($r) => [(int) $r->catalog_id, (int) $r->rating])->all());
    }

    public function test_patron_names_and_record_titles_are_shown_as_text()
    {
        $id = $this->record('<b>Bold</b> title');
        $this->patron->forceFill(['name' => '<script>alert(1)</script>'])->save();
        DB::table('ratings')->insert(['catalog_id' => $id, 'user_id' => $this->patron->id, 'rating' => 5, 'remarks' => 'Good', 'created_at' => now()]);

        $page = $this->get("/full-description/$id");
        $page = $this->get($page->headers->get('Location'))->assertOk();   // canonical slug URL

        $page->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
             ->assertDontSee('<b>Bold</b>', false)->assertSee('&lt;b&gt;Bold&lt;/b&gt; title', false);
    }

    public function test_removed_endpoints_no_longer_answer()
    {
        // Took any file into a public folder (a .php upload would have run); unused.
        $this->actingAs($this->staff)->post('/marcfile/upload', [
            'marc_file_for_upload' => UploadedFile::fake()->createWithContent('x.php', '<?php echo 1;'),
        ])->assertNotFound();
        // Let any signed-in user read every patron's survey comments (?rating=comment1); unused.
        $this->actingAs($this->patron)->get('/csf/get-ratings/json?rating=comment1')->assertNotFound();
    }

    public function test_pages_send_browser_protections()
    {
        $this->get('/home')->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_https_pages_tell_browsers_to_keep_to_https()
    {
        $this->get('https://localhost/home')->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // Over plain HTTP the header means nothing to browsers, and isn't sent (local development).
        $this->get('http://localhost/home')->assertOk()->assertHeaderMissing('Strict-Transport-Security');

        // The way back: browsers are told to forget it.
        config(['app.hsts_max_age' => 0]);
        $this->get('https://localhost/home')->assertHeader('Strict-Transport-Security', 'max-age=0');
    }

    public function test_pages_enforce_a_content_security_policy_whose_nonce_their_scripts_carry()
    {
        $response = $this->get('/home')->assertOk()->assertHeaderMissing('Content-Security-Policy-Report-Only');

        $policy = $response->headers->get('Content-Security-Policy');
        foreach (["default-src 'self'", "object-src 'none'", "base-uri 'none'", "form-action 'self'", "frame-ancestors 'self'"] as $part) {
            $this->assertStringContainsString($part, $policy);
        }
        $this->assertStringNotContainsString("'unsafe-inline' https://cdn", $policy);   // no inline scripts without the nonce
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-([A-Za-z0-9+\\/=]{24})'/", $policy);
        preg_match("/'nonce-([^']+)'/", $policy, $m);
        preg_match_all('/<script nonce="([^"]+)"/', $response->getContent(), $tags);
        $this->assertNotEmpty($tags[1]);
        $this->assertSame([$m[1]], array_values(array_unique($tags[1])), 'every inline script carries this response\'s nonce');

        // A new nonce for every response.
        $this->assertNotSame($policy, $this->get('/home')->headers->get('Content-Security-Policy'));
    }

    public function test_the_emergency_switch_only_reports_and_debug_error_pages_keep_their_scripts()
    {
        config(['app.csp_enforce' => false]);
        $response = $this->get('/home');
        $this->assertSame("frame-ancestors 'self'; object-src 'none'; base-uri 'none'; form-action 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("script-src 'self' 'nonce-", $response->headers->get('Content-Security-Policy-Report-Only'));

        // Laravel's debug error page (APP_DEBUG, a server error) runs inline scripts of its own.
        config(['app.csp_enforce' => true, 'app.debug' => true]);
        \Illuminate\Support\Facades\Route::get('/__csp-test-error', fn () => throw new \RuntimeException('boom'))->middleware('web');
        $error = $this->get('/__csp-test-error')->assertStatus(500);
        $this->assertSame("frame-ancestors 'self'; object-src 'none'; base-uri 'none'; form-action 'self'", $error->headers->get('Content-Security-Policy'));
    }

    public function test_the_policy_is_only_sent_with_pages()
    {
        $this->getJson('/search/quick/999999')->assertHeaderMissing('Content-Security-Policy')->assertHeaderMissing('Content-Security-Policy-Report-Only');
    }

    public function test_the_session_cookie_is_secure_and_host_prefixed_and_there_is_no_xsrf_cookie()
    {
        config(['session.driver' => 'cookie']);   // a driver that sends the cookie (the tests' array driver doesn't)

        $cookies = collect($this->get('/home')->assertOk()->headers->getCookies())->keyBy(fn ($c) => $c->getName());

        $this->assertStringStartsWith('__Host-', config('session.cookie'));
        $session = $cookies->get(config('session.cookie'));
        $this->assertNotNull($session, 'no session cookie was sent');
        $this->assertSame([true, true, '/', null, 'lax'],
            [$session->isSecure(), $session->isHttpOnly(), $session->getPath(), $session->getDomain(), $session->getSameSite()]);
        $this->assertFalse($cookies->has('XSRF-TOKEN'));
    }

    public function test_the_search_view_is_read_from_the_https_cookie_name_too()
    {
        $view = fn (array $cookies) => \App\Services\CatalogSearchParams::fromRequest(\Illuminate\Http\Request::create('/results', 'GET', [], $cookies))->view;

        $this->assertSame('grid', $view(['__Host-sr_view' => 'grid']));
        $this->assertSame('grid', $view(['sr_view' => 'grid']));
        $this->assertSame('list', $view([]));
    }

    public function test_a_news_link_must_be_a_web_address_and_a_cover_is_named_by_what_it_is()
    {
        $news = ['news_title' => 'Seminar', 'news_body' => '<p>Body</p>'];

        $this->actingAs($this->staff)->post('/admin/news/create', $news + ['news_url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('news_url');
        $this->assertSame(0, DB::table('news')->count());

        // A real JPEG sent with an .html name: stored as .jpg, so it can't be served as a page.
        $this->actingAs($this->staff)->post('/admin/news/create', $news + [
            'news_url' => 'https://www.fnri.dost.gov.ph/', 'news_cover' => $this->realImage('cover.html'),
        ])->assertSessionHasNoErrors();

        $cover = DB::table('news')->value('news_cover');
        $this->assertMatchesRegularExpression('#^news_cover/news-[0-9a-f]+\.jpg$#', $cover);
        $this->assertFileExists("{$this->public}/$cover");
    }

    public function test_a_cover_photo_must_really_be_an_image()
    {
        $id = $this->record('Rubber production');
        $page = $this->upload('<html><script>alert(1)</script></html>', 'cover.jpg');

        $this->actingAs($this->staff)->post('/admin/upload/cover-photo', ['catalog_id' => $id, 'coverImage' => $page])
            ->assertOk()->assertSee('The cover photo must be a JPG, PNG, or WEBP image');

        $this->assertSame(0, DB::table('frontpages')->where('catalog_id', $id)->count());
        $this->assertSame([], glob("{$this->public}/frontpages/cp-*"));
    }
}
