<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Appearance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * Appearance: each staff member's own colours, background, contrast, text size, motion and font, or
 * the library default set under Utilities → Theme; every admin page carries it (attributes on <html>,
 * the light and/or dark stylesheet, a block of variables).
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

    /** What an admin page puts down for a staff member: <html> attributes, variables, stylesheets, body class. */
    private function pageOf(User $user): array
    {
        $page = $this->actingAs($user)->get('/admin/profile')->assertOk()->getContent();
        preg_match('/<html lang="en" ([^>]*)>/', $page, $m);
        preg_match_all('/([\w-]+)="([^"]*)"/', $m[1], $attrs, PREG_SET_ORDER);
        preg_match('#<style id="appearanceVars">(.*?)</style>#s', $page, $vars);
        preg_match('#id="themeLight"[^>]*media="([^"]*)"#', $page, $light);
        preg_match('#id="themeDark"[^>]*media="([^"]*)"#', $page, $dark);
        preg_match('#<body class="([^"]*)"#', $page, $body);
        return ['attrs' => collect($attrs)->mapWithKeys(fn ($a) => [$a[1] => html_entity_decode($a[2])])->all(),
                'vars' => $vars[1], 'light' => $light[1], 'dark' => $dark[1], 'body' => $body[1]];
    }

    /** The variables a choice sets, for one scheme. */
    private function vars(array $choice, ?string $scheme = null): array
    {
        return Appearance::vars(Appearance::sanitize($choice), $scheme);
    }

    public function test_every_theme_but_the_original_keeps_text_readable()
    {
        foreach (array_keys(Appearance::ACCENTS) as $key) {
            $vars = $this->vars(['accent' => $key]);
            if ($key !== 'indigo') $this->assertGreaterThanOrEqual(4.5, Appearance::contrast($vars['--t-primary'], '#ffffff'), "$key with white text");
            $this->assertGreaterThanOrEqual(4.5, Appearance::contrast($vars['--t-primary-on-dark'], Appearance::SIDEBAR), "$key on the sidebar");
        }
        $vars = $this->vars(['status' => 'colorblind']);
        foreach (['success', 'danger', 'warning', 'info'] as $status) {
            $this->assertGreaterThanOrEqual(4.5, Appearance::contrast($vars["--t-$status"], '#ffffff'), "colour-blind $status");
            $this->assertGreaterThanOrEqual(4.5, Appearance::contrast($vars["--t-$status-on-dark"], Appearance::SIDEBAR), "colour-blind $status on the sidebar");
        }
        // The standard palette's sidebar variants are its own colours: the default look is unchanged.
        foreach (Appearance::STATUS['standard'] as $status => $colour) {
            $this->assertSame($colour, Appearance::statusVars('standard')["--t-$status-on-dark"]);
        }
    }

    public function test_the_standard_look_sets_no_roles()
    {
        // Nothing chosen: every --ui-* role is unset, so the template's and our own colours stay as they were.
        $this->assertSame([], Appearance::roles(Appearance::DEFAULTS, 'light'));
        $this->assertSame([], array_filter($this->vars([]), fn ($k) => str_starts_with($k, '--ui-'), ARRAY_FILTER_USE_KEY));
    }

    public function test_every_background_keeps_text_readable_with_and_without_high_contrast()
    {
        foreach (array_keys(Appearance::BACKGROUNDS) as $background) {
            foreach (['standard', 'high'] as $contrast) {
                $choice = ['background' => $background, 'contrast' => $contrast, 'status' => 'colorblind'];
                foreach (Appearance::mode(Appearance::sanitize($choice)) === 'auto' ? ['light', 'dark'] : [null] as $scheme) {
                    $v = $this->vars($choice, $scheme);
                    $dark = ($scheme ?? Appearance::mode(Appearance::sanitize($choice))) === 'dark';
                    $min = $contrast === 'high' ? 7.0 : 4.5;
                    $at = "$background, $contrast" . ($scheme ? ", $scheme" : '');
                    if (isset($v['--ui-text'])) {
                        foreach (['--ui-surface', '--ui-surface-2', '--ui-page'] as $bg) {
                            $this->assertGreaterThanOrEqual(7.0, Appearance::contrast($v['--ui-text'], $v[$bg]), "text on $bg ($at)");
                            $this->assertGreaterThanOrEqual($min, Appearance::contrast($v['--ui-muted'], $v[$bg]), "muted text on $bg ($at)");
                        }
                        $accentText = $dark ? $v['--t-primary-on-dark'] : $v['--t-primary'];
                        $this->assertGreaterThanOrEqual($min, Appearance::contrast($accentText, $v['--ui-page']), "links ($at)");
                    }
                    foreach (['success', 'danger', 'warning', 'info'] as $s) {
                        $this->assertGreaterThanOrEqual($min, Appearance::contrast($v["--ui-$s-ink"], $v["--ui-$s-soft"]), "$s chip ($at)");
                    }
                }
            }
        }
    }

    public function test_a_custom_colour_is_darkened_until_text_on_it_reads()
    {
        $vars = $this->vars(['accent' => 'custom', 'custom' => '#ffee00']);
        $this->assertNotSame('#ffee00', $vars['--t-primary']);
        $this->assertGreaterThanOrEqual(4.5, Appearance::contrast($vars['--t-primary'], '#ffffff'));
    }

    public function test_a_custom_background_is_made_light_or_dark_enough()
    {
        // A mid grey reads with neither dark nor light text: it is lightened until dark text reads at 7:1.
        $p = Appearance::sanitize(['background' => 'custom', 'background_custom' => '#808080']);
        $this->assertSame('light', Appearance::mode($p));
        $v = Appearance::vars($p);
        $this->assertNotSame('#808080', $v['--ui-page']);
        $this->assertGreaterThanOrEqual(7.0, Appearance::contrast($v['--ui-text'], $v['--ui-page']));
        // A deep navy becomes a dark scheme with light text.
        $p = Appearance::sanitize(['background' => 'custom', 'background_custom' => '#123456']);
        $this->assertSame('dark', Appearance::mode($p));
        $this->assertGreaterThanOrEqual(7.0, Appearance::contrast(Appearance::vars($p)['--ui-text'], '#123456'));
    }

    public function test_unknown_values_fall_back_to_the_default()
    {
        $this->assertSame(Appearance::DEFAULTS, Appearance::sanitize([
            'accent' => 'neon', 'custom' => 'red', 'status' => 'rainbow', 'text' => 400, 'motion' => 'fast', 'font' => 'comic',
            'background' => 'plaid', 'background_custom' => '#zzzzzz', 'sidebar' => 'left', 'contrast' => 'max',
        ]));
        $this->assertSame(Appearance::DEFAULTS, Appearance::sanitize(['accent' => 'custom', 'custom' => 'javascript:alert(1)']));
        $this->assertSame(Appearance::DEFAULTS, Appearance::sanitize(['background' => 'custom', 'background_custom' => '</style><script>']));
    }

    public function test_a_staff_member_saves_their_own_and_every_admin_page_shows_it()
    {
        $staff = $this->staff('cataloging_admin');
        $this->actingAs($staff)->post('/admin/appearance', ['accent' => 'teal', 'status' => 'colorblind', 'text' => 130, 'motion' => 'reduce', 'font' => 'readable'])
            ->assertRedirect('/admin/appearance');

        $page = $this->pageOf($staff);
        $this->assertSame(['130', 'reduce', 'readable', 'colorblind', 'light'],
            [$page['attrs']['data-text'], $page['attrs']['data-motion'], $page['attrs']['data-font'], $page['attrs']['data-status'], $page['attrs']['data-mode']]);
        $this->assertStringContainsString('--t-primary: #00857a', $page['vars']);
        $this->assertStringContainsString('--t-text: 1.3', $page['vars']);
        $this->assertSame(['all', 'not all', 'sidebar-dark'], [$page['light'], $page['dark'], $page['body']]);
    }

    public function test_a_dark_background_uses_the_dark_stylesheet_and_dark_colours()
    {
        $staff = $this->staff('cataloging_admin');
        $this->actingAs($staff)->post('/admin/appearance', ['background' => 'dark']);

        $page = $this->pageOf($staff);
        $this->assertSame(['not all', 'all', 'dark'], [$page['light'], $page['dark'], $page['attrs']['data-mode']]);
        $this->assertStringContainsString('--ui-surface: #0c1427', $page['vars']);   // NobleUI's dark panels
        $this->assertStringContainsString('--ui-accent-text: var(--t-primary-on-dark)', $page['vars']);
    }

    public function test_follow_my_device_switches_with_the_device()
    {
        $staff = $this->staff('cataloging_admin');
        $this->actingAs($staff)->post('/admin/appearance', ['background' => 'auto', 'sidebar' => 'light']);

        $page = $this->pageOf($staff);
        $this->assertSame(['(prefers-color-scheme: light)', '(prefers-color-scheme: dark)', 'auto'], [$page['light'], $page['dark'], $page['attrs']['data-mode']]);
        $this->assertStringContainsString('@media (prefers-color-scheme: dark)', $page['vars']);
        $this->assertSame('', $page['body']);   // the light sidebar (the dark stylesheet's is dark anyway)
    }

    public function test_the_preview_shows_a_choice_without_saving_it()
    {
        $staff = $this->staff('cataloging_admin');
        $res = $this->actingAs($staff)->getJson('/admin/appearance/preview?accent=teal&background=dim&contrast=high')->assertOk();

        $this->assertSame(['dark', 'high'], [$res->json('attributes.data-mode'), $res->json('attributes.data-contrast')]);
        $this->assertSame(['not all', 'all'], [$res->json('media.light'), $res->json('media.dark')]);
        $this->assertStringContainsString('--ui-text: #ffffff', $res->json('css'));   // high contrast: white on black
        $this->assertNull($staff->fresh()->appearance);
    }

    public function test_the_library_default_applies_to_staff_without_their_own()
    {
        $themeAdmin = $this->staff('system_admin');   // has manage.theme (and access.configuration)
        $follower = $this->staff('circulation_admin');
        $own = $this->staff('cataloging_admin');
        $own->forceFill(['appearance' => Appearance::sanitize(['accent' => 'purple'])])->save();

        $this->actingAs($themeAdmin)->post('/admin/utilities/theme', ['accent' => 'green', 'text' => 115, 'background' => 'cream'])->assertRedirect('/admin/utilities/theme');

        $page = $this->pageOf($follower);
        $this->assertSame('115', $page['attrs']['data-text']);
        $this->assertStringContainsString('--ui-page: #f6efdf', $page['vars']);
        $this->assertMatchesRegularExpression('/--t-primary: #[0-9a-f]{6}/', $page['vars']);
        $this->assertStringContainsString('--t-primary: #7e22ce', $this->pageOf($own)['vars']);   // their own choice stays
    }

    public function test_use_the_library_default_and_back_to_original()
    {
        $themeAdmin = $this->staff('system_admin');
        $staff = $this->staff('cataloging_admin');
        $this->actingAs($themeAdmin)->post('/admin/utilities/theme', ['accent' => 'slate']);
        $this->actingAs($staff)->post('/admin/appearance', ['accent' => 'crimson']);

        $this->actingAs($staff)->post('/admin/appearance/reset')->assertRedirect('/admin/appearance');
        $this->assertNull($staff->fresh()->appearance);
        $this->assertStringContainsString('--t-primary: #475569', $this->pageOf($staff)['vars']);   // the library's slate

        $this->actingAs($themeAdmin)->post('/admin/utilities/theme', ['reset' => 1]);
        $this->assertSame(Appearance::DEFAULTS, Appearance::siteDefault());
    }

    public function test_only_theme_admins_set_the_default_and_everyone_sets_their_own()
    {
        $staff = $this->staff('cataloging_admin');   // no manage.theme
        $this->actingAs($staff)->get('/admin/utilities/theme')->assertForbidden();
        $this->actingAs($staff)->post('/admin/utilities/theme', ['accent' => 'green'])->assertForbidden();
        $this->assertNull(DB::table('settings')->where('key', 'appearance_default')->value('value'));
        $this->actingAs($staff)->get('/admin/appearance')->assertOk()->assertSee('Colour theme')->assertSee('Background');
    }

    public function test_the_generated_stylesheets_start_from_the_same_colours()
    {
        // public/css/admin-theme(-dark).css (scripts/admin-theme.mjs) default to the template's colours,
        // which the service's original indigo and standard status colours must match.
        foreach (['admin-theme.css', 'admin-theme-dark.css'] as $file) {
            $css = file_get_contents(public_path("css/$file"));
            $this->assertStringContainsString('--t-primary: ' . Appearance::ACCENTS['indigo'][1] . ';', $css, $file);
            foreach (Appearance::STATUS['standard'] as $name => $colour) $this->assertStringContainsString("--t-$name: $colour;", $css, $file);
        }
        // The dark stylesheet's roles fall back to NobleUI's dark colours, card headers included (no navy
        // band on a Dim or Black page).
        $dark = file_get_contents(public_path('css/admin-theme-dark.css'));
        $this->assertStringContainsString('var(--ui-surface, #0c1427)', $dark);
        $this->assertMatchesRegularExpression('/\.card-header \{[^}]*background-color: var\(--ui-page, #0a1122\)/', $dark);
    }
}
