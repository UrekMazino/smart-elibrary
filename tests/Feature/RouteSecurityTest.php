<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * Every route, checked against who may use it. The routes are read from the app, so a route added
 * later is checked too: one that opens without signing in, or an admin route without a permission,
 * fails here until it is added to the reviewed lists below.
 */
class RouteSecurityTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    /** Routes anyone may open without signing in (reviewed 2026-10-21; inquiry/general, the chat button, 2026-10-26;
     *  news/{id}, a featured article for the home page's NEWS section, 2026-10-27). */
    private const PUBLIC = [
        '/', 'home', 'news/{id}', 'results', 'full-description', 'full-description/{holdingsid}', 'read/view',
        'search/suggest', 'search/facet', 'search/cite/{catalogId}', 'search/quick/{catalogId}', 'search/ai-answer',
        'inquiry/general',
        'login', 'register', 'forgot-password', 'reset-password', 'reset-password/{token}',
    ];

    /** Admin routes any staff account may use: its own profile and appearance, the OPAC button, MARC lookups for the editor. */
    private const ANY_STAFF = [
        'admin/opac', 'admin/opac/return', 'admin/profile', 'admin/profile/update', 'admin/profile/update/password',
        'admin/appearance', 'admin/appearance/reset', 'admin/appearance/preview',
        'admin/profile/validate-email', 'admin/get-marc-tag-details', 'admin/get-marc-template-details', 'admin/marc-tags-list/json',
    ];

    /** Pages that talk to an outside service (Meilisearch) rather than the database: left out of the crash check. */
    private const EXTERNAL = ['admin/settings/scan-search'];

    /** @return list<array{method:string, uri:string, path:string, mw:array}> */
    private function routes(): array
    {
        $out = [];
        foreach (app('router')->getRoutes() as $route) {
            $uri = $route->uri();
            if (preg_match('#^(_ignition|sanctum|api/)#', $uri)) continue;   // dev tool (off without APP_DEBUG), Sanctum, the API stub
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $out[] = ['method' => $method, 'uri' => $uri, 'path' => $this->path($uri), 'mw' => $route->gatherMiddleware()];
            }
        }
        return $out;
    }

    /** An address for the route: a number that matches no record, a code that matches nothing. */
    private function path(string $uri): string
    {
        $filled = preg_replace_callback('/\{(\w+)\??\}/', fn ($m) => match ($m[1]) {
            'format' => 'mrc', 'token' => str_repeat('a', 40), 'hash' => 'x', 'reservation_code' => 'RSV-NONE', 'cirno' => 'NONE',
            default => '999999',
        }, $uri);
        return '/' . ltrim($filled, '/');
    }

    private function has(array $mw, string $name): bool
    {
        return in_array($name, $mw, true) || (bool) preg_grep('/^' . preg_quote($name, '/') . ':/', $mw);
    }

    /** Permission or role checks on the route: can:x, permission:a|b, role:a|b, role_or_permission:a|b. */
    private function guards(array $mw): array
    {
        return array_values(preg_grep('/^(can|permission|role|role_or_permission):/', $mw));
    }

    private function staff(string $role, string $usertype = 'Administrator'): User
    {
        return tap(User::factory()->create(['usertype' => $usertype]))->assignRole($role);
    }

    public function test_only_reviewed_routes_open_without_signing_in()
    {
        $open = collect($this->routes())->reject(fn ($r) => $this->has($r['mw'], 'auth'))->pluck('uri')->unique()->values()->all();

        $this->assertSame([], array_values(array_diff($open, self::PUBLIC)),
            'These routes open without signing in but are not on the reviewed PUBLIC list.');
    }

    public function test_admin_routes_are_for_staff_and_name_a_permission()
    {
        $problems = [];
        foreach ($this->routes() as $r) {
            if (!preg_match('#^(admin|superadmin)/#', $r['uri'])) continue;
            if (!$this->has($r['mw'], 'auth') || !$this->has($r['mw'], 'admin')) $problems[] = "$r[method] $r[uri]: not staff-only";
            if (!$this->guards($r['mw']) && !in_array($r['uri'], self::ANY_STAFF, true)) $problems[] = "$r[method] $r[uri]: no permission check";
        }
        $this->assertSame([], $problems);
    }

    public function test_guests_are_sent_to_sign_in()
    {
        $wrong = [];
        foreach ($this->routes() as $r) {
            if (!$this->has($r['mw'], 'auth')) continue;
            $res = $this->call($r['method'], $r['path']);
            if (!($res->isRedirect() && $res->headers->get('Location') === url('/login'))) $wrong[] = "$r[method] $r[path] -> {$res->getStatusCode()}";
        }
        $this->assertSame([], $wrong);
    }

    public function test_patrons_cannot_use_staff_routes()
    {
        $this->seedAccess();
        $patron = User::factory()->create(['usertype' => 'Patron']);

        $wrong = [];
        foreach ($this->routes() as $r) {
            if (!$this->has($r['mw'], 'admin')) continue;
            $res = $this->actingAs($patron)->call($r['method'], $r['path']);
            // 403, or the redirect to the OPAC. A route that looks its record up first ({issue}) answers 404
            // for a record that does not exist, before the permission check: nothing is processed either way.
            $refused = $res->getStatusCode() === 403 || ($res->isRedirect() && $res->headers->get('Location') === url('/'))
                || ($res->getStatusCode() === 404 && str_contains($r['uri'], '{issue}'));
            if (!$refused) $wrong[] = "$r[method] $r[path] -> {$res->getStatusCode()}";
        }
        $this->assertSame([], $wrong);
    }

    public function test_staff_are_refused_what_their_role_lacks()
    {
        $this->seedAccess();
        $staff = $this->staff('serial_admin');   // dashboard and notifications only

        $wrong = [];
        $checked = 0;
        foreach ($this->routes() as $r) {
            $guards = $this->guards($r['mw']);
            $allowed = collect($guards)->every(function ($g) use ($staff) {
                [$kind, $names] = explode(':', $g, 2);
                $names = explode('|', $names);
                return match ($kind) {
                    'role'               => $staff->hasAnyRole($names),
                    'role_or_permission' => $staff->hasAnyRole($names) || $staff->canAny($names),
                    default              => collect($names)->contains(fn ($p) => $staff->can($p)),
                };
            });
            if (!$guards || $allowed) continue;

            $checked++;
            $res = $this->actingAs($staff)->call($r['method'], $r['path']);
            $refused = $res->getStatusCode() === 403 || ($res->getStatusCode() === 404 && str_contains($r['uri'], '{issue}'));   // see the patron test
            if (!$refused) $wrong[] = "$r[method] $r[path] -> {$res->getStatusCode()}";
        }
        $this->assertGreaterThan(120, $checked, 'Expected most admin routes to be refused to a dashboard-only role.');
        $this->assertSame([], $wrong);
    }

    public function test_staff_are_sent_away_from_patron_pages()
    {
        $this->seedAccess();
        $staff = $this->staff('developer', 'Developer');

        $wrong = [];
        foreach ($this->routes() as $r) {
            if (!$this->has($r['mw'], 'patron')) continue;
            $res = $this->actingAs($staff)->call($r['method'], $r['path']);
            if (!($res->isRedirect() && $res->headers->get('Location') === url('/home'))) $wrong[] = "$r[method] $r[path] -> {$res->getStatusCode()}";
        }
        $this->assertSame([], $wrong);
    }

    public function test_every_form_submission_needs_its_csrf_token()
    {
        // Laravel skips the token check in tests; this test turns it back on.
        $this->app->instance(VerifyCsrfToken::class, new class($this->app, $this->app['encrypter']) extends VerifyCsrfToken {
            protected function runningUnitTests() { return false; }
        });

        $wrong = [];
        foreach ($this->routes() as $r) {
            if ($r['method'] === 'GET') continue;
            $res = $this->call($r['method'], $r['path']);
            if ($res->getStatusCode() !== 419) $wrong[] = "$r[method] $r[path] -> {$res->getStatusCode()}";
        }
        $this->assertSame([], $wrong);
    }

    public function test_pages_answer_without_server_errors_even_for_missing_records()
    {
        $this->seedAccess();
        $this->seedInstallData();
        Http::fake();   // the AI status check and anything else that would call out
        $staff = $this->staff('developer', 'Developer');
        $patron = User::factory()->create(['usertype' => 'Patron']);

        $errors = [];
        foreach ($this->routes() as $r) {
            if ($r['method'] !== 'GET' || in_array($r['uri'], self::EXTERNAL, true)) continue;
            $as = $this->has($r['mw'], 'patron') ? $patron : ($this->has($r['mw'], 'auth') ? $staff : null);
            $res = $as ? $this->actingAs($as)->get($r['path']) : $this->get($r['path']);
            if ($res->getStatusCode() >= 500) {
                $errors[] = "GET $r[path] -> {$res->getStatusCode()}" . (isset($res->exception) ? ': ' . substr($res->exception->getMessage(), 0, 120) : '');
            }
            auth()->forgetGuards();
        }
        $this->assertSame([], $errors);
    }

    public function test_each_rate_limit_counts_its_own_route()
    {
        $patron = User::factory()->create(['usertype' => 'Patron']);

        // Quick views (60 a minute) used to spend the inquiry form's 5 a minute: one count per person.
        foreach (range(1, 6) as $i) $this->actingAs($patron)->get('/search/quick/999999');
        $this->actingAs($patron)->post('/inquiry/submit', [])->assertStatus(302);   // validation, not 429

        // The form's own limit still holds.
        foreach (range(1, 4) as $i) $this->actingAs($patron)->post('/inquiry/submit', []);
        $this->actingAs($patron)->post('/inquiry/submit', [])->assertStatus(429);
    }
}
