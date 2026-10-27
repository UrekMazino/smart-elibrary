<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\SeedsReferenceData;
use Tests\TestCase;
use Tests\UsesDatabaseSessions;

/**
 * Administration → Accounts → Signed in now (OWASP ASVS 7.4.5; SignedInNowController): who is signed in, signing one
 * person out everywhere or everyone, each after your password. docs/implemented/SECURITY_NOTES.md section 5.
 */
class SignedInNowTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData, UsesDatabaseSessions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useDatabaseSessions();
        $this->seedAccess();
    }

    private function staff(string $role, array $attributes = []): User
    {
        return tap(User::factory()->create($attributes + ['usertype' => $role === 'developer' ? 'Developer' : 'Administrator']))->assignRole($role);
    }

    public function test_super_admins_and_developers_have_it_other_staff_do_not()
    {
        $this->actingAs($this->staff('super_admin'))->get('/admin/sessions')->assertOk()->assertSee('SIGNED IN NOW');
        $this->get('/admin/accounts')->assertSee('Signed in now');
        $this->actingAs($this->staff('developer'))->get('/admin/sessions')->assertOk();

        $this->actingAs($this->staff('system_admin'))->get('/admin/sessions')->assertForbidden();
        $this->get('/admin/accounts')->assertOk()->assertDontSee('Signed in now');
    }

    public function test_it_lists_who_is_signed_in_and_on_which_browsers_but_not_where()
    {
        $admin = $this->staff('super_admin', ['name' => 'Ada Admin']);
        $phone = $this->elsewhere(User::factory()->create(['name' => 'Ana Reyes']), ['remember' => true]);
        $this->elsewhere($this->staff('cataloging_admin', ['name' => 'Ben Cruz']), agent: self::EDGE);
        $this->elsewhere(User::factory()->create(['name' => 'Ida Idle']), minutesAgo: 130);         // past the 2-hour idle limit
        $this->elsewhere(User::factory()->create(['name' => 'Bo Banned', 'is_banned' => true]));
        $this->elsewhere(tap(User::factory()->create(['name' => 'Del Eted']))->delete());          // a deleted account
        $this->visitor();
        $this->visitor(self::EDGE);
        $this->visitor('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');

        $this->actingAs($admin)->here()->get('/admin/sessions')->assertOk()
            ->assertSee('data-count="staff">2<', false)      // you, and Ben
            ->assertSee('data-count="patrons">1<', false)
            ->assertSee('data-count="visitors">2<', false)   // not the crawler
            ->assertSeeInOrder(['Ada Admin', 'You', 'Ana Reyes', 'Safari on iPhone', 'Kept signed in'])
            ->assertSee('Ben Cruz')->assertSee('Edge on Windows')
            ->assertDontSee('Ida Idle')->assertDontSee('Bo Banned')->assertDontSee('Del Eted')
            ->assertDontSee('203.0.113.7')->assertDontSee($phone);
    }

    public function test_it_shows_staff_or_patrons_and_finds_a_name_or_email()
    {
        $admin = $this->staff('super_admin');
        $this->elsewhere(User::factory()->create(['name' => 'Ana Reyes', 'email' => 'ana.reyes@example.test']));
        $this->elsewhere($this->staff('cataloging_admin', ['name' => 'Ben Cruz']));

        $this->actingAs($admin)->here()->get('/admin/sessions?type=patrons')->assertSee('Ana Reyes')->assertDontSee('Ben Cruz');
        $this->here()->get('/admin/sessions?type=staff')->assertSee('Ben Cruz')->assertDontSee('Ana Reyes');
        $this->here()->get('/admin/sessions?q=ANA.REYES@')->assertSee('Ana Reyes')->assertDontSee('Ben Cruz');
    }

    public function test_signing_one_person_out_takes_your_password_and_ends_their_sessions_and_cookie()
    {
        $admin = $this->staff('super_admin');
        $patron = User::factory()->create(['name' => 'Ana Reyes']);
        $this->elsewhere($patron);
        $this->elsewhere($patron, ['remember' => true], self::FIREFOX);
        $someoneElse = $this->elsewhere(User::factory()->create());
        $token = $patron->fresh()->remember_token;

        $this->actingAs($admin)->here()->from('/admin/sessions')
            ->delete('/admin/sessions', ['target' => $patron->id, 'password' => 'wrong-password'])
            ->assertRedirect('/admin/sessions')->assertSessionHasErrors('password', null, 'signOut');
        $this->assertSame(2, DB::table('sessions')->where('user_id', $patron->id)->count());

        $this->here()->from('/admin/sessions')->delete('/admin/sessions', ['target' => $patron->id, 'password' => 'password'])
            ->assertRedirect('/admin/sessions')->assertSessionHas('success', 'Ana Reyes is signed out everywhere.');
        $this->assertSame(0, DB::table('sessions')->where('user_id', $patron->id)->count());
        $this->assertNotSame($token, $patron->fresh()->remember_token);   // "Keep me signed in" ends too
        $this->assertDatabaseHas('sessions', ['id' => $someoneElse]);
    }

    public function test_your_own_account_and_a_developers_are_not_signed_out_here()
    {
        $admin = $this->staff('super_admin');
        $developer = $this->staff('developer');
        $this->elsewhere($developer);

        $this->actingAs($admin)->here()->delete('/admin/sessions', ['target' => $admin->id, 'password' => 'password'])->assertForbidden();
        $this->here()->delete('/admin/sessions', ['target' => $developer->id, 'password' => 'password'])->assertForbidden();
        $this->assertSame(1, DB::table('sessions')->where('user_id', $developer->id)->count());

        $this->here()->get('/admin/sessions')->assertOk()   // nor are they offered
            ->assertDontSee('data-sign-out="' . $admin->id . '"', false)
            ->assertDontSee('data-sign-out="' . $developer->id . '"', false);
    }

    public function test_sign_out_everyone_leaves_you_and_visitors()
    {
        $admin = $this->staff('super_admin');
        $patron = User::factory()->create(['remember_token' => 'kept-signed-in']);
        $gone = [$this->elsewhere($patron), $this->elsewhere($this->staff('cataloging_admin')), $this->elsewhere($admin, agent: self::EDGE)];
        $developer = $this->elsewhere($this->staff('developer'));
        $visitor = $this->visitor();
        $this->actingAs($admin)->here()->get('/admin/sessions')->assertOk();

        $this->here()->from('/admin/sessions')->delete('/admin/sessions', ['target' => 'everyone', 'password' => 'password'])
            ->assertRedirect('/admin/sessions')
            ->assertSessionHas('success', 'Signed out 2 people. “Keep me signed in” no longer works on their devices.');

        foreach ($gone as $session) {
            $this->assertDatabaseMissing('sessions', ['id' => $session]);   // including your other device
        }
        $this->assertDatabaseHas('sessions', ['id' => $developer]);       // only a developer signs out developers
        $this->assertDatabaseHas('sessions', ['id' => $visitor]);
        $this->assertNull($patron->fresh()->remember_token);
        $this->here()->get('/admin/sessions')->assertOk();                // still signed in here
    }
}
