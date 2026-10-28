<?php

namespace Tests\Feature;

use App\Http\Controllers\HomeController;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * The admin sidebar and the Accounts / Utilities cards against the routes behind them: for every
 * seeded role, each link it is shown opens, and what it can use under Administration is shown.
 * And the breadcrumbs (admin.partials.breadcrumb): the same trail on every page, linking only what opens.
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

    /** A page's breadcrumbs: [label, link path or null] each, or null when it has none or more than one. */
    private function crumbs(string $html): ?array
    {
        $doc = new DOMDocument();
        @$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xp = new DOMXPath($doc);
        $navs = $xp->query('//nav[contains(@class,"page-breadcrumb")]');
        if ($navs->length !== 1) return null;
        $crumbs = [];
        foreach ($xp->query('.//li', $navs->item(0)) as $li) {
            $a = $xp->query('.//a', $li)->item(0);
            $crumbs[] = [trim(preg_replace('/\s+/', ' ', $li->textContent)), $a ? parse_url($a->getAttribute('href'), PHP_URL_PATH) : null,
                $li->getAttribute('aria-current') === 'page'];
        }
        return $crumbs;
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

    public function test_every_page_has_one_trail_from_its_menu_heading_and_each_crumb_link_opens()
    {
        $headings = ['Overview', 'Cataloging', 'Circulation', 'Inventory', 'Administration', 'Your Account'];
        $wrong = [];
        foreach (Role::pluck('name') as $role) {
            $user = $this->staff($role);
            $pages = ['/admin/profile', '/admin/appearance'];
            foreach ($this->sidebar($user) as $path) {
                $pages[] = $path;
                array_push($pages, ...$this->cards($this->actingAs($user)->get($path)->getContent()));
            }
            foreach (array_unique($pages) as $page) {
                $crumbs = $this->crumbs($this->actingAs($user)->get($page)->assertOk()->getContent());
                if ($crumbs === null || count($crumbs) < 2) { $wrong[] = "$role: $page has no breadcrumbs"; continue; }
                [$first, $last] = [$crumbs[0], end($crumbs)];
                if (! in_array($first[0], $headings, true) || $first[1] !== null) $wrong[] = "$role: $page starts at " . json_encode($first);
                if (! $last[2] || $last[1] !== null) $wrong[] = "$role: $page doesn't end at itself";
                foreach ($crumbs as [$label, $link]) {
                    if ($link !== null && $this->actingAs($user)->get($link)->getStatusCode() !== 200) $wrong[] = "$role: $page crumb $label -> $link doesn't open";
                }
            }
        }
        $this->assertSame([], $wrong);
    }

    public function test_crumbs_name_pages_as_the_menu_does_and_link_only_what_opens()
    {
        $admin = $this->staff('super_admin');
        $this->assertSame([['Administration', null, false], ['Accounts', '/admin/accounts', false], ['Signed In Now', null, true]],
            $this->crumbs($this->actingAs($admin)->get('/admin/sessions')->getContent()));
        $this->assertSame([['Overview', null, false], ['News Articles', '/admin/news', false], ['New Article', null, true]],
            $this->crumbs($this->get('/admin/news/add')->getContent()));

        // Signed In Now alone: the Accounts hub opens (its one card), so it links; with no hub card it couldn't.
        Role::create(['name' => 'sessions_only', 'guard_name' => 'web'])->givePermissionTo('access.sessions');
        $this->assertSame('/admin/accounts', $this->crumbs($this->actingAs($this->staff('sessions_only'))->get('/admin/sessions')->getContent())[1][1]);

        // Editing a record without the lists: the list crumb is plain text, the record links to its page.
        Role::create(['name' => 'editor_only', 'guard_name' => 'web'])->givePermissionTo(['access.cataloging', 'edit.catalog']);
        $type = DB::table('materials')->value('id');
        $id = DB::table('catalogs')->insertGetId(['material_type_id' => $type, 'is_published' => 0,
            'field_data' => json_encode([['tag' => '245', 'ind1' => '1', 'ind2' => '0', 'subfields' => [['code' => 'a', 'value' => 'Rice and health']]]])]);
        $crumbs = $this->crumbs($this->actingAs($this->staff('editor_only'))->get("/admin/edit-record/$id")->assertOk()->getContent());
        $this->assertSame(['Cataloging', null, false], $crumbs[0]);
        $this->assertSame(['On-Hold', null, false], $crumbs[1]);
        $this->assertSame("/admin/view-record/$id", $crumbs[2][1]);
        $this->assertSame(['Edit Record', null, true], $crumbs[3]);
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

        // A role with only the Utilities module and one utility: not Settings, which it may not open.
        Role::create(['name' => 'templates_only', 'guard_name' => 'web'])->givePermissionTo(['access.utilities', 'view.config.template']);
        $user = $this->staff('templates_only');
        $this->assertSame(url('admin/utilities'), HomeController::adminLandingUrl($user));
        $this->actingAs($user)->get('/admin/utilities')->assertOk();
    }

    public function test_utilities_take_their_own_module_permission_not_the_settings_one()
    {
        // A utility without "Utilities Module Access": no Utilities link, and its pages stay shut.
        Role::create(['name' => 'templates_no_module', 'guard_name' => 'web'])->givePermissionTo('view.config.template');
        $user = $this->staff('templates_no_module');
        $this->assertNotContains('/admin/utilities', $this->sidebar($user));
        $this->actingAs($user)->get('/admin/utilities')->assertForbidden();
        $this->actingAs($user)->get('/admin/marc-templates')->assertForbidden();

        // With it, both open; Settings still needs access.configuration and its own permission.
        Role::findByName('templates_no_module')->givePermissionTo('access.utilities');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = $user->fresh();
        $this->assertContains('/admin/utilities', $this->sidebar($user));
        $this->actingAs($user)->get('/admin/utilities')->assertOk();
        $this->actingAs($user)->get('/admin/marc-templates')->assertOk();
        $this->actingAs($user)->get('/admin/settings')->assertForbidden();
    }
}
