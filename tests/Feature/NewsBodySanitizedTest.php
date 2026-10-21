<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** A news body keeps the editor's formatting and loses anything that could run script. */
class NewsBodySanitizedTest extends TestCase
{
    use RefreshDatabase;

    private const BODY = '<h2>Seminar</h2><p style="text-align: center;">Join us <strong>Monday</strong></p>'
        . '<ul><li>Room 1</li></ul><p><a href="https://fnri.dost.gov.ph/">Details</a> <img src="https://example.org/a.jpg" alt="poster"></p>'
        . '<script>alert(1)</script><img src="x" onerror="alert(2)"><a href="javascript:alert(3)">x</a>'
        . '<iframe src="https://evil.example"></iframe><!--mce:protected %3Cscript%3Ealert(4)%3C/script%3E-->'
        . '<span data-mce-bogus="1" data-mce-html="%3Cimg%20src%3Dx%20onerror%3Dalert(5)%3E">y</span>';

    public function test_a_saved_news_body_keeps_formatting_and_drops_script()
    {
        $staff = User::factory()->create(['usertype' => 'Developer']);
        $staff->assignRole(Role::findOrCreate('developer'));

        $this->actingAs($staff)->post('/admin/news/create', ['news_title' => 'Seminar', 'news_body' => self::BODY])
            ->assertSessionHasNoErrors();
        $body = DB::table('news')->value('news_body');

        foreach (['<h2>Seminar</h2>', 'style="text-align: center;"', '<strong>Monday</strong>', '<li>Room 1</li>',
                  'href="https://fnri.dost.gov.ph/"', 'src="https://example.org/a.jpg"'] as $kept) {
            $this->assertStringContainsString($kept, $body);
        }
        foreach (['<script', 'onerror', 'javascript:', '<iframe', 'mce:protected', 'data-mce', 'alert('] as $dropped) {
            $this->assertStringNotContainsString($dropped, $body);
        }
    }

    public function test_a_body_stored_before_cleaning_is_cleaned_when_opened_in_the_editor()
    {
        $staff = User::factory()->create(['usertype' => 'Developer']);
        $staff->assignRole(Role::findOrCreate('developer'));
        $id = DB::table('news')->insertGetId(['news_title' => 'Old', 'news_body' => self::BODY, 'featured' => 'No']);

        $page = $this->actingAs($staff)->get("/admin/news/edit/$id")->assertOk()->getContent();

        $this->assertStringContainsString('&lt;h2&gt;Seminar&lt;/h2&gt;', $page);
        $this->assertStringNotContainsString('onerror', $page);
        $this->assertStringNotContainsString('mce:protected', $page);
    }
}
