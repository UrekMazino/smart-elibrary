<?php

namespace Tests\Feature;

use App\Models\Multimedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guests who press Read, Download or Save get a sign-in popup first, on the search results (list
 * and Quick view) as on the record page: one shared prompt, opac.partials.guest-login-js.
 */
class GuestSignInPromptTest extends TestCase
{
    use RefreshDatabase;

    private int $catalogId;

    protected function setUp(): void
    {
        parent::setUp();
        $material = DB::table('materials')->insertGetId(['name' => 'Book']);
        $this->catalogId = DB::table('catalogs')->insertGetId(['material_type_id' => $material, 'field_data' => '[]', 'is_published' => 1]);
        DB::table('view_catalogs')->insert(['catalog_id' => $this->catalogId, 'title_statement' => 'Rubber production', 'material_type_id' => $material, 'is_published' => 1]);
        Multimedia::create(['catalog_id' => $this->catalogId, 'FileName' => 'ft.pdf', 'FileLocation' => 'pdf/ft.pdf', 'FileType' => 'pdf']);
    }

    public function test_the_search_results_ask_a_guest_to_sign_in_before_download_and_save()
    {
        $this->get('/results?query=rubber')->assertOk()
            ->assertSee('window.promptGuestLogin = function', false)
            ->assertSee("promptLogin('download')", false)
            ->assertSee("promptLogin('read')", false)
            ->assertSee('[data-login-prompt]', false);
    }

    public function test_quick_view_gives_a_guest_the_save_prompt()
    {
        $this->get("/search/quick/{$this->catalogId}")->assertOk()
            ->assertSee('data-login-prompt="save"', false);
    }

    public function test_the_record_page_uses_the_same_prompt()
    {
        $page = $this->get("/full-description/{$this->catalogId}");
        if ($page->isRedirect()) $page = $this->get($page->headers->get('Location'));   // canonical slug URL

        $page->assertOk()->assertSee('window.promptGuestLogin = function', false)->assertSee("promptGuestLogin('save')", false);
    }
}
