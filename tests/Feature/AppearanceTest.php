<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Appearance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * Appearance: each staff member's own colours, status colours, text size, motion and font, or the
 * library default set under Utilities → Theme; every admin page carries it on <html>.
 */
class AppearanceTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    private function staff(string $role): User
    {
        return tap(User::factory()->create(['usertype' => $role === 'developer' ? 'Developer' : 'Administrator']))->assignRole($role);
    }

    /** The appearance attributes on an admin page's <html>. */
    private function htmlOf(User $user): array
    {
        $page = $this->actingAs($user)->get('/admin/profile')->assertOk()->getContent();
        preg_match('/<html lang="en" ([^>]*)>/', $page, $m);
        preg_match_all('/([\w-]+)="([^"]*)"/', $m[1], $attrs, PREG_SET_ORDER);
        return collect($attrs)->mapWithKeys(fn ($a) => [$a[1] => html_entity_decode($a[2])])->all();
    }

    public function test_every_theme_but_the_original_keeps_text_readable()
    {
        foreach (array_keys(Appearance::ACCENTS) as $key) {
            $vars = Appearance::vars(Appearance::sanitize(['accent' => $key]));
            if ($key !== 'indigo') $this->assertGreaterThanOrEqual(4.5, Appearance::contrast($vars['--t-primary'], '#ffffff'), "$key with white text");
            $this->assertGreaterThanOrEqual(4.5, Appearance::contrast($vars['--t-primary-on-dark'], Appearance::SIDEBAR), "$key on the sidebar");
        }
        $vars = Appearance::vars(Appearance::sanitize(['status' => 'colorblind']));
        foreach (['success', 'danger', 'warning', 'info'] as $status) {
            $this->assertGreaterThanOrEqual(4.5, Appearance::contrast($vars["--t-$status"], '#ffffff'), "colour-blind $status");
            $this->assertGreaterThanOrEqual(4.5, Appearance::contrast($vars["--t-$status-on-dark"], Appearance::SIDEBAR), "colour-blind $status on the sidebar");
        }
        // The standard palette's sidebar variants are its own colours: the default look is unchanged.
        foreach (Appearance::STATUS['standard'] as $status => $colour) {
            $this->assertSame($colour, Appearance::statusVars('standard')["--t-$status-on-dark"]);
        }
    }

    public function test_a_custom_colour_is_darkened_until_text_on_it_reads()
    {
        $vars = Appearance::vars(Appearance::sanitize(['accent' => 'custom', 'custom' => '#ffee00']));
        $this->assertNotSame('#ffee00', $vars['--t-primary']);
        $this->assertGreaterThanOrEqual(4.5, Appearance::contrast($vars['--t-primary'], '#ffffff'));
    }

    public function test_unknown_values_fall_back_to_the_default()
    {
        $this->assertSame(Appearance::DEFAULTS, Appearance::sanitize([
            'accent' => 'neon', 'custom' => 'red', 'status' => 'rainbow', 'text' => 400, 'motion' => 'fast', 'font' => 'comic',
        ]));
        $this->assertSame(Appearance::DEFAULTS, Appearance::sanitize(['accent' => 'custom', 'custom' => 'javascript:alert(1)']));
    }

    public function test_a_staff_member_saves_their_own_and_every_admin_page_shows_it()
    {
        $staff = $this->staff('cataloging_admin');
        $this->actingAs($staff)->post('/admin/appearance', ['accent' => 'teal', 'status' => 'colorblind', 'text' => 130, 'motion' => 'reduce', 'font' => 'readable'])
            ->assertRedirect('/admin/appearance');

        $html = $this->htmlOf($staff);
        $this->assertSame(['130', 'reduce', 'readable', 'colorblind'], [$html['data-text'], $html['data-motion'], $html['data-font'], $html['data-status']]);
        $this->assertStringContainsString('--t-primary: #00857a', $html['style']);
        $this->assertStringContainsString('--t-text: 1.3', $html['style']);
    }

    public function test_the_library_default_applies_to_staff_without_their_own()
    {
        $themeAdmin = $this->staff('system_admin');   // has manage.theme (and access.configuration)
        $follower = $this->staff('circulation_admin');
        $own = $this->staff('cataloging_admin');
        $own->forceFill(['appearance' => Appearance::sanitize(['accent' => 'purple'])])->save();

        $this->actingAs($themeAdmin)->post('/admin/utilities/theme', ['accent' => 'green', 'text' => 115])->assertRedirect('/admin/utilities/theme');

        $html = $this->htmlOf($follower);
        $this->assertSame('115', $html['data-text']);
        $this->assertStringContainsString('--t-primary: #15803d', $html['style']);
        $this->assertStringContainsString('--t-primary: #7e22ce', $this->htmlOf($own)['style']);   // their own choice stays
    }

    public function test_use_the_library_default_and_back_to_original()
    {
        $themeAdmin = $this->staff('system_admin');
        $staff = $this->staff('cataloging_admin');
        $this->actingAs($themeAdmin)->post('/admin/utilities/theme', ['accent' => 'slate']);
        $this->actingAs($staff)->post('/admin/appearance', ['accent' => 'crimson']);

        $this->actingAs($staff)->post('/admin/appearance/reset')->assertRedirect('/admin/appearance');
        $this->assertNull($staff->fresh()->appearance);
        $this->assertStringContainsString('--t-primary: #475569', $this->htmlOf($staff)['style']);   // the library's slate

        $this->actingAs($themeAdmin)->post('/admin/utilities/theme', ['reset' => 1]);
        $this->assertSame(Appearance::DEFAULTS, Appearance::siteDefault());
    }

    public function test_only_theme_admins_set_the_default_and_everyone_sets_their_own()
    {
        $staff = $this->staff('cataloging_admin');   // no manage.theme
        $this->actingAs($staff)->get('/admin/utilities/theme')->assertForbidden();
        $this->actingAs($staff)->post('/admin/utilities/theme', ['accent' => 'green'])->assertForbidden();
        $this->assertNull(DB::table('settings')->where('key', 'appearance_default')->value('value'));
        $this->actingAs($staff)->get('/admin/appearance')->assertOk()->assertSee('Colour theme');
    }

    public function test_the_generated_stylesheet_starts_from_the_same_colours()
    {
        // public/css/admin-theme.css (scripts/admin-theme.mjs) defaults to the template's colours, which
        // the service's original indigo and standard status colours must match.
        $css = file_get_contents(public_path('css/admin-theme.css'));
        $this->assertStringContainsString('--t-primary: ' . Appearance::ACCENTS['indigo'][1] . ';', $css);
        foreach (Appearance::STATUS['standard'] as $name => $colour) $this->assertStringContainsString("--t-$name: $colour;", $css);
    }
}
