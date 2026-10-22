<?php

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * The admin sidebar and the Accounts / Utilities cards against the routes behind them: for every
 * seeded role, each link it is shown opens, and what it can use under Administration is shown.
 */
class AdminNavigationTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    private const ADMINISTRATION = ['/admin/accounts', '/admin/settings', '/admin/utilities'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
        $this->seedInstallData();
        Http::fake();   // the dashboard's AI status check
    }

    private function staff(string $role): User
    {
        return tap(User::factory()->create(['usertype' => $role === 'developer' ? 'Developer' : 'Administrator']))->assignRole($role);
    }

    /** The links in the part of a page $xpath picks, as paths ("/admin/patron"). */
    private function links(string $html, string $xpath): array
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $paths = [];
        foreach ((new DOMXPath($doc))->query($xpath) as $a) {
            $href = $a->getAttribute('href');
            if ($href === '' || $href[0] === '#' || str_starts_with($href, 'javascript:')) continue;
            $paths[] = parse_url($href, PHP_URL_PATH) ?: '/';
        }
        return array_values(array_unique($paths));
    }

    private function sidebar(User $user): array
    {
        return $this->links($this->actingAs($user)->get('/admin/profile')->assertOk()->getContent(), '//div[contains(@class,"sidebar-body")]//a');
    }

    private function cards(string $html): array
    {
        return $this->links($html, '//a[contains(@class,"util-card")]');
    }

    public function test_every_link_and_card_a_role_is_shown_opens()
    {
        $broken = [];
        foreach (Role::pluck('name') as $role) {
            $user = $this->staff($role);
            foreach ($this->sidebar($user) as $path) {
                $res = $this->actingAs($user)->get($path);
                if ($res->getStatusCode() !== 200) {
                    $broken[] = "$role: sidebar $path -> {$res->getStatusCode()}";
                    continue;
                }
                foreach ($this->cards($res->getContent()) as $card) {
                    $status = $this->actingAs($user)->get($card)->getStatusCode();
                    if ($status !== 200) $broken[] = "$role: $path card $card -> $status";
                }
            }
        }
        $this->assertSame([], $broken);
    }

    public function test_administration_shows_what_the_role_can_use_and_nothing_else()
    {
        $wrong = [];
        foreach (Role::pluck('name') as $role) {
            $user = $this->staff($role);
            $sidebar = $this->sidebar($user);
            foreach (self::ADMINISTRATION as $page) {
                $res = $this->actingAs($user)->get($page);
                // A hub is worth a link when it has a card; with none it only says "nothing for your role".
                $usable = $res->getStatusCode() === 200 && ($page === '/admin/settings' || $this->cards($res->getContent()));
                if ($usable !== in_array($page, $sidebar, true)) $wrong[] = "$role: $page " . ($usable ? 'usable but not in the menu' : 'in the menu but not usable');
            }
            $heading = str_contains($this->actingAs($user)->get('/admin/profile')->getContent(), 'nav-category">Administration<');
            if ($heading !== (bool) array_intersect(self::ADMINISTRATION, $sidebar)) $wrong[] = "$role: Administration heading " . ($heading ? 'without a link' : 'missing');
        }
        $this->assertSame([], $wrong);
    }

    public function test_super_admins_reach_roles_even_without_the_accounts_permission()
    {
        Role::findByName('super_admin')->revokePermissionTo('access.accounts');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin = $this->staff('super_admin');

        $this->assertContains('/admin/accounts', $this->sidebar($admin));
        $cards = $this->cards($this->actingAs($admin)->get('/admin/accounts')->assertOk()->getContent());
        $this->assertContains('/admin/roles', $cards);
        $this->assertNotContains('/admin/patron', $cards);   // Patrons still needs access.accounts
        $this->actingAs($admin)->get('/admin/patron')->assertForbidden();
    }

    public function test_staff_land_on_a_page_they_can_open()
    {
        foreach (Role::pluck('name') as $role) {
            $user = $this->staff($role);
            $landing = HomeController::adminLandingUrl($user);
            $this->assertSame(200, $this->actingAs($user)->get($landing)->getStatusCode(), "$role lands on $landing");
        }

        // A role with only the Configuration module and one utility: not Settings, which it may not open.
        Role::create(['name' => 'templates_only', 'guard_name' => 'web'])->givePermissionTo(['access.configuration', 'view.config.template']);
        $user = $this->staff('templates_only');
        $this->assertSame(url('admin/utilities'), HomeController::adminLandingUrl($user));
        $this->actingAs($user)->get('/admin/utilities')->assertOk();
    }
}
