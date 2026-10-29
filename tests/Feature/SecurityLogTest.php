<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SecurityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * The security event log (App\Services\SecurityLog; SECURITY_NOTES.md section 2): what goes in, in UTC, one event a
 * line that a typed value can't break; the year it keeps, trimmed from the top of its one file; and Administration →
 * Accounts → Security Log, for developer and super admin. TestCase points the log at a file of the test's own. The log
 * stamps an entry with the real clock (Monolog's), not a test's travelled one.
 */
class SecurityLogTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    /** @return list<array{0: string, 1: string, 2: array}> each line as [time, event, details] */
    private function lines(): array
    {
        $path = SecurityLog::path();
        $lines = is_file($path) ? file($path, FILE_IGNORE_NEW_LINES) : [];

        return array_map(function ($line) {
            [$at, $event, $json] = explode(' ', $line, 3);
            return [$at, $event, json_decode($json, true)];
        }, $lines);
    }

    private function events(): array
    {
        return array_column($this->lines(), 1);
    }

    private function staff(string $role): User
    {
        return tap(User::factory()->create(['usertype' => $role === 'developer' ? 'Developer' : 'Administrator']))->assignRole($role);
    }

    public function test_sign_ins_failures_and_a_lockout_are_logged_in_utc_without_the_password()
    {
        $patron = User::factory()->create(['usertype' => 'Patron', 'email' => 'ana@example.org', 'password' => Hash::make(self::GOOD_PASSWORD)]);
        $try = fn (string $password) => $this->post('/login', ['email' => 'ANA@example.org', 'password' => $password]);

        foreach (range(1, 5) as $i) {
            $try('wrong-password-' . $i);
        }
        $try('wrong-password-6');   // locked: the lockout is logged once
        $try('wrong-password-7');
        $this->assertSame(array_merge(array_fill(0, 5, 'sign-in.failed'), ['sign-in.locked']), $this->events());

        [$at, , $failed] = $this->lines()[0];
        $this->assertMatchesRegularExpression('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d\.\d{6}\+00:00$/', $at);   // UTC, to the microsecond
        $this->assertEqualsWithDelta(time(), strtotime($at), 60);
        $this->assertSame(['user' => $patron->id, 'email' => 'ana@example.org', 'ip' => '127.0.0.1', 'agent' => 'Symfony', 'url' => 'POST /login'], $failed);
        $this->assertStringNotContainsString('wrong-password', file_get_contents(SecurityLog::path()));

        $this->travel(2)->minutes();   // the lockout is over
        $try(self::GOOD_PASSWORD);
        $this->post('/logout');
        $this->assertSame(['sign-in', 'sign-out'], array_slice($this->events(), -2));
        $this->assertSame('password', $this->lines()[6][2]['method']);
        $this->assertStringNotContainsString(self::GOOD_PASSWORD, file_get_contents(SecurityLog::path()));

        // "Keep me signed in": the cookie signs the patron back in once the session is gone.
        $cookie = $this->post('/login', ['email' => 'ana@example.org', 'password' => self::GOOD_PASSWORD, 'remember' => '1'])
            ->getCookie('__Host-remember_web')->getValue();
        $this->app['session.store']->flush();
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withCookie('__Host-remember_web', $cookie)->get('/patron/profile')->assertOk();
        $last = array_reverse($this->lines())[0];
        $this->assertSame(['sign-in', 'remembered'], [$last[1], $last[2]['method']]);
    }

    public function test_an_unknown_address_and_a_banned_account_are_logged_as_such()
    {
        $this->post('/login', ['email' => 'nobody@example.org', 'password' => 'whatever-it-is']);
        $banned = User::factory()->create(['usertype' => 'Patron', 'password' => Hash::make(self::GOOD_PASSWORD)]);
        $banned->is_banned = true;
        $banned->save();
        $this->post('/login', ['email' => $banned->email, 'password' => self::GOOD_PASSWORD]);

        $lines = $this->lines();
        $this->assertSame(['sign-in.failed', 'sign-in.refused'], $this->events());   // refused, not signed in and out
        $this->assertArrayNotHasKey('user', $lines[0][2]);
        $this->assertSame('no account with this address', SecurityLog::describe('sign-in.failed', $lines[0][2]));
        $this->assertSame([$banned->id, 'banned account'], [$lines[1][2]['user'], $lines[1][2]['reason']]);
    }

    public function test_refused_pages_and_role_permission_ban_and_password_changes_are_logged()
    {
        $admin = $this->staff('super_admin');
        $this->actingAs($this->staff('system_admin'))->get('/admin/sessions')->assertForbidden();

        $patron = User::factory()->create(['usertype' => 'Patron']);
        $librarian = $this->staff('cataloging_admin');
        $role = Role::findByName('circulation_admin');
        $permission = Permission::findByName('access.inventory');
        $this->actingAs($admin);
        $this->post('/admin/user/toggle-flag', ['user_id' => $patron->id, 'flag' => 'banned'])->assertOk();
        $this->post('/superadmin/admin-change-role', ['user_id' => $librarian->id, 'role_id' => $role->id])->assertRedirect();
        $this->postJson('/admin/role-permissions/toggle', ['role_id' => $role->id, 'permission_id' => $permission->id, 'granted' => true])->assertOk();
        $this->postJson('/admin/role-permissions/toggle', ['role_id' => $role->id, 'permission_id' => $permission->id, 'granted' => true])->assertOk();   // no change: not logged
        $this->postJson("/superadmin/admin/{$librarian->id}/reset-password")->assertOk();

        $lines = $this->lines();
        $this->assertSame(['access.denied', 'account.banned', 'role.changed', 'role.permission', 'password.changed'], $this->events());
        $this->assertSame('GET /admin/sessions', $lines[0][2]['url']);
        $this->assertSame([$patron->id, $admin->id], [$lines[1][2]['user'], $lines[1][2]['by']]);
        $this->assertSame('cataloging_admin → circulation_admin', SecurityLog::describe('role.changed', $lines[2][2]));
        $this->assertSame('gave access.inventory to circulation_admin', SecurityLog::describe('role.permission', $lines[3][2]));
        $this->assertSame([$librarian->id, 'reset by staff'], [$lines[4][2]['user'], $lines[4][2]['how']]);
    }

    public function test_a_typed_value_cannot_start_a_line_of_its_own()
    {
        SecurityLog::record('sign-in.failed', null, [], "x@example.org\n2026-01-01T00:00:00.000000+00:00 sign-in {\"user\":1}");

        $this->assertCount(1, $this->lines());
        $this->assertSame("x@example.org\n2026-01-01T00:00:00.000000+00:00 sign-in {\"user\":1}", $this->lines()[0][2]['email']);
    }

    public function test_the_year_is_trimmed_from_the_top_of_the_one_file_and_writing_goes_on()
    {
        $old = fn (int $days) => Carbon::now()->subDays($days)->utc()->format('Y-m-d\TH:i:s.uP');
        file_put_contents(SecurityLog::path(), implode('', array_map(fn ($days) => $old($days) . " sign-in {\"user\":$days}\n", [400, 366, 364, 30, 1])));
        $inode = fileinode(SecurityLog::path());

        $this->artisan('security-log:prune')->expectsOutput('Removed 2 security log entries older than a year.')->assertSuccessful();
        $this->assertSame([364, 30, 1], array_column(array_column($this->lines(), 2), 'user'));
        $this->assertSame($inode, fileinode(SecurityLog::path()));   // the same file, not a new one

        SecurityLog::record('sign-out', User::factory()->create());
        $this->assertSame(['sign-in', 'sign-in', 'sign-in', 'sign-out'], $this->events());
        $this->artisan('security-log:prune')->expectsOutput('Removed 0 security log entries older than a year.');
        $this->artisan('schedule:list')->expectsOutputToContain('security-log:prune')->assertSuccessful();
    }

    public function test_a_range_is_found_without_reading_the_whole_year()
    {
        $lines = '';
        for ($day = 300; $day >= 0; $day--) {
            foreach ([9, 15] as $hour) {
                $lines .= Carbon::now()->subDays($day)->setTime($hour, 0)->utc()->format('Y-m-d\TH:i:s.uP') . " sign-in {\"day\":$day}\n";
            }
        }
        file_put_contents(SecurityLog::path(), $lines);

        $range = SecurityLog::between(now()->subDays(10)->startOfDay(), now()->subDays(8)->endOfDay());
        $this->assertSame([10, 10, 9, 9, 8, 8], $range->pluck('data.day')->all());
        $this->assertTrue($range->first()['at']->isUtc());
        $this->assertCount(0, SecurityLog::between(now()->addDay(), now()->addDays(2)));
    }

    public function test_the_page_is_for_developer_and_super_admin_and_filters_the_range()
    {
        $admin = $this->staff('super_admin');
        $patron = User::factory()->create(['usertype' => 'Patron', 'name' => 'Ana Cruz', 'email' => 'ana@example.org']);
        $this->post('/login', ['email' => 'ana@example.org', 'password' => 'wrong-password-1']);
        $this->actingAs($admin)->post('/admin/user/toggle-flag', ['user_id' => $patron->id, 'flag' => 'blocked']);

        $failedAt = Carbon::parse($this->lines()[0][0]);
        $this->get('/admin/security-log')->assertOk()
            ->assertSeeInOrder(['SECURITY LOG', 'Blocked', 'Ana Cruz', $admin->name, 'Wrong email or password', 'ana@example.org', '127.0.0.1'])
            ->assertSee($failedAt->copy()->setTimezone('Asia/Manila')->format('M j, g:i:s A'))   // Philippine time on the page,
            ->assertSee($failedAt->copy()->utc()->format('Y-m-d H:i:s') . ' UTC');               // UTC on hover
        $this->get('/admin/security-log?type=Signing+in')->assertSee('Wrong email or password')->assertDontSee('>Blocked<', false);
        $this->get('/admin/security-log?q=nobody')->assertSee('Nothing matching from');
        $this->get('/admin/security-log?from=2026-10-01&to=2026-10-20')->assertSee('Nothing from Oct 1 to Oct 20, 2026');
        $this->get('/admin/accounts')->assertSee('Security Log');

        $this->actingAs($this->staff('developer'))->get('/admin/security-log')->assertOk();
        $this->actingAs($watcher = $this->staff('system_admin'))->get('/admin/security-log')->assertForbidden();
        $this->get('/admin/accounts')->assertDontSee('Security Log');
        $refused = array_reverse($this->lines())[0];   // the refusal itself is logged
        $this->assertSame(['access.denied', $watcher->id, 'GET /admin/security-log'], [$refused[1], $refused[2]['user'], $refused[2]['url']]);
    }
}
