<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BrowserSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tests\UsesDatabaseSessions;

/**
 * "Where you're signed in" on the profile pages (OWASP ASVS 7.5.2; docs/implemented/SECURITY_NOTES.md section 5):
 * the account's sessions, signing out one or all the others, each after the current password.
 */
class BrowserSessionsTest extends TestCase
{
    use RefreshDatabase, UsesDatabaseSessions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useDatabaseSessions();   // the list reads sessions from the database, as on the server
    }

    private function patron(): User
    {
        return User::factory()->create()->fresh();   // fresh: usertype is the database's default, Patron
    }

    public function test_the_profile_lists_where_you_are_signed_in_without_showing_session_ids()
    {
        $patron = $this->patron();
        $phone = $this->elsewhere($patron);

        $this->actingAs($patron)->here()->get('/patron/profile')->assertOk()
            ->assertSeeInOrder(['Where you’re signed in', 'This device', 'Active now', 'Safari on iPhone', 'Last active 10 minutes ago · 203.0.113.7'])
            ->assertSee('Sign out all other sessions')
            ->assertDontSee($phone)
            ->assertDontSee(session()->getId());
    }

    public function test_ended_sessions_and_other_peoples_are_not_listed()
    {
        $patron = $this->patron();
        $this->elsewhere($this->patron(), agent: self::FIREFOX);                           // someone else's
        $this->elsewhere($patron, agent: self::EDGE, minutesAgo: 130);                       // past the 2-hour idle limit
        $this->elsewhere($patron, data: ['password_hash_web' => hash_hmac('sha256', 'an old hash', 'key')]);   // before a password change

        $this->actingAs($patron)->here()->get('/patron/profile')->assertOk()
            ->assertDontSee('Firefox')->assertDontSee('Edge on Windows')->assertDontSee('Safari on iPhone');
    }

    public function test_staff_see_the_list_and_sign_out_all_only_when_there_are_other_sessions()
    {
        $staff = User::factory()->create(['usertype' => 'Administrator']);

        $this->actingAs($staff)->here()->get('/admin/profile')->assertOk()
            ->assertSee('Where you’re signed in')->assertDontSee('Sign out all other sessions');
        $this->elsewhere($staff, agent: self::EDGE);
        $this->here()->get('/admin/profile')->assertSee('Edge on Windows')->assertSee('Sign out all other sessions');
    }

    public function test_signing_out_another_session_takes_the_password()
    {
        $patron = $this->patron();
        $phone = $this->elsewhere($patron);
        $this->actingAs($patron)->here()->get('/patron/profile');

        $this->here()->from('/patron/profile')->delete('/sessions', ['end' => BrowserSessions::ref($phone), 'password' => 'wrong-password'])
            ->assertRedirect('/patron/profile')->assertSessionHasErrors('password', null, 'endSessions');
        $this->assertDatabaseHas('sessions', ['id' => $phone]);

        $this->here()->from('/patron/profile')->delete('/sessions', ['end' => BrowserSessions::ref($phone), 'password' => 'password'])
            ->assertRedirect('/patron/profile')->assertSessionHas('success', 'That session is signed out.');
        $this->assertDatabaseMissing('sessions', ['id' => $phone]);
        $this->here()->get('/patron/profile')->assertOk()->assertSee('This device');   // still signed in here
    }

    public function test_signing_out_a_device_kept_signed_in_also_stops_its_cookie()
    {
        $patron = $this->patron();
        $tablet = $this->elsewhere($patron, ['remember' => true]);
        $phone = $this->elsewhere($patron);
        $this->actingAs($patron)->here()->get('/patron/profile');
        $token = $patron->fresh()->remember_token;

        $this->here()->delete('/sessions', ['end' => BrowserSessions::ref($phone), 'password' => 'password']);
        $this->assertSame($token, $patron->fresh()->remember_token);      // not kept signed in: nothing more to do

        $this->here()->delete('/sessions', ['end' => BrowserSessions::ref($tablet), 'password' => 'password']);
        $this->assertNotSame($token, $patron->fresh()->remember_token);   // its "Keep me signed in" cookie no longer works
    }

    public function test_sign_out_all_other_sessions()
    {
        $staff = User::factory()->create(['usertype' => 'Administrator']);
        $laptop = $this->elsewhere($staff, agent: self::EDGE);
        $desk = $this->elsewhere($staff, agent: self::FIREFOX);
        $theirs = $this->elsewhere($this->patron());
        $this->actingAs($staff)->here()->get('/admin/profile')->assertOk();
        $hash = $staff->fresh()->password;

        $this->here()->from('/admin/profile')->delete('/sessions', ['end' => 'others', 'password' => 'password'])
            ->assertRedirect('/admin/profile')->assertSessionHas('success', 'Your other sessions are signed out.');

        $this->assertDatabaseMissing('sessions', ['id' => $laptop]);
        $this->assertDatabaseMissing('sessions', ['id' => $desk]);
        $this->assertDatabaseHas('sessions', ['id' => $theirs]);
        $this->assertDatabaseHas('sessions', ['id' => session()->getId()]);
        // A new hash of the same password: remember cookies and sessions made before it stop working.
        $this->assertNotSame($hash, $staff->fresh()->password);
        $this->assertTrue(Hash::check('password', $staff->fresh()->password));
        $this->here()->get('/admin/profile')->assertOk();   // still signed in here
    }

    public function test_this_session_and_someone_elses_cannot_be_signed_out_here()
    {
        $patron = $this->patron();
        $theirs = $this->elsewhere($this->patron());
        $this->actingAs($patron)->here()->get('/patron/profile');

        $this->here()->delete('/sessions', ['end' => BrowserSessions::ref($theirs), 'password' => 'password'])
            ->assertSessionHas('success', 'That session had already ended.');
        $this->assertDatabaseHas('sessions', ['id' => $theirs]);

        $this->here()->delete('/sessions', ['end' => BrowserSessions::ref(session()->getId()), 'password' => 'password'])
            ->assertSessionHas('success', 'That session had already ended.');
        $this->here()->get('/patron/profile')->assertOk();
    }

    public function test_browsers_are_named_from_their_user_agent()
    {
        $this->assertSame('Safari on iPhone', BrowserSessions::describe(self::IPHONE));
        $this->assertSame('Firefox on Linux', BrowserSessions::describe(self::FIREFOX));
        $this->assertSame('Edge on Windows', BrowserSessions::describe(self::EDGE));
        $this->assertSame('Chrome on Android', BrowserSessions::describe('Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36'));
        $this->assertSame('Samsung Internet on Android', BrowserSessions::describe('Mozilla/5.0 (Linux; Android 14; SM-S921B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/25.0 Chrome/121.0.0.0 Mobile Safari/537.36'));
        $this->assertSame('Chrome on Mac', BrowserSessions::describe('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36'));
        $this->assertSame('Unknown browser', BrowserSessions::describe(null));
    }
}
