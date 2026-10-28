<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Sessions (docs/implemented/SECURITY_NOTES.md section 5): a password change ends the account's other sessions
 * (OWASP ASVS 7.4.3); idle and absolute limits, staff 30 minutes and 8 hours, patrons 2 hours and 12 hours
 * (7.3.1, 7.3.2); "Keep me signed in", staff and patrons; the staff warning's time check and "Stay signed in".
 */
class SessionLimitsTest extends TestCase
{
    use RefreshDatabase;

    /** phpunit.xml keeps cookies Secure, so the remember cookie has the __Host- prefix (App\Auth\SessionGuard). */
    private const REMEMBER_COOKIE = '__Host-remember_web';

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeSecond();   // whole seconds, so the time left compares exactly
    }

    private function staff(): User
    {
        return User::factory()->create(['usertype' => 'Administrator']);
    }

    private function signIn(User $user, bool $remember = false)
    {
        return $this->post('/login', ['email' => $user->email, 'password' => 'password'] + ($remember ? ['remember' => '1'] : []));
    }

    /** The next request reads the user from the session again, as a new request from the browser would. */
    private function nextRequest(): static
    {
        Auth::forgetGuards();
        return $this;
    }

    /** The session is gone (unused longer than SESSION_LIFETIME): the browser has only its cookies. */
    private function sessionEnded(): void
    {
        $this->app['session.store']->flush();
        Auth::forgetGuards();
    }

    public function test_a_password_change_elsewhere_ends_this_session()
    {
        $patron = User::factory()->create();
        $this->signIn($patron);
        $this->get('/patron/profile')->assertOk();

        // As a reset link (NewPasswordController) or a librarian's reset (AdminController::resetPasswordOf) does it.
        $patron->forceFill(['password' => Hash::make(self::GOOD_PASSWORD)])->save();

        $this->nextRequest()->get('/patron/profile')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get('/login')->assertSee('You were signed out because the password of this account was changed.');
    }

    public function test_changing_your_own_password_keeps_you_signed_in_on_that_device()
    {
        $staff = $this->staff();
        $this->signIn($staff);
        $this->post('/admin/profile/update/password', [
            'current_password' => 'password',
            'password' => self::GOOD_PASSWORD,
            'password_confirmation' => self::GOOD_PASSWORD,
        ])->assertRedirect('/admin/profile');

        $this->nextRequest()->get('/admin/profile')->assertOk();
        $this->assertAuthenticatedAs($staff);
    }

    public function test_a_password_change_also_ends_keep_me_signed_in()
    {
        $patron = User::factory()->create();
        $cookie = $this->signIn($patron, remember: true)->getCookie(self::REMEMBER_COOKIE)->getValue();
        $patron->forceFill(['password' => Hash::make(self::GOOD_PASSWORD)])->save();

        $this->sessionEnded();
        $this->withCookie(self::REMEMBER_COOKIE, $cookie)->get('/patron/profile')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_staff_are_signed_out_after_30_minutes_without_activity_and_return_to_their_page()
    {
        $staff = $this->staff();
        $this->signIn($staff);
        $this->travel(29)->minutes();
        $this->get('/admin/profile')->assertOk();   // activity: the 30 minutes start again
        $this->travel(29)->minutes();
        $this->get('/admin/profile')->assertOk();

        $this->travel(31)->minutes();
        $this->get('/admin/profile')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get('/login')->assertSee('You were signed out after 30 minutes without activity. Sign in again to continue.');
        $this->signIn($staff)->assertRedirect('/admin/profile');
    }

    public function test_staff_are_signed_out_8_hours_after_signing_in_however_active()
    {
        $this->signIn($this->staff());
        for ($minutes = 25; $minutes < 8 * 60; $minutes += 25) {
            $this->travel(25)->minutes();
            $this->get('/admin/profile')->assertOk();
        }

        $this->travel(25)->minutes();   // 8 hours 20 minutes
        $this->get('/admin/profile')->assertRedirect(route('login'));
        $this->get('/login')->assertSee('You were signed out 8 hours after signing in.');
    }

    public function test_patrons_are_signed_out_after_2_hours_without_activity()
    {
        $this->signIn(User::factory()->create());
        $this->travel(119)->minutes();
        $this->get('/patron/profile')->assertOk();

        $this->travel(121)->minutes();
        $this->get('/patron/profile')->assertRedirect(route('login'));
        $this->get('/login')->assertSee('You were signed out after 2 hours without activity.');
    }

    public function test_patrons_are_signed_out_12_hours_after_signing_in_however_active()
    {
        $this->signIn(User::factory()->create());
        for ($minutes = 110; $minutes < 12 * 60; $minutes += 110) {
            $this->travel(110)->minutes();
            $this->get('/patron/profile')->assertOk();
        }

        $this->travel(110)->minutes();   // 12 hours 50 minutes
        $this->get('/patron/profile')->assertRedirect(route('login'));
        $this->get('/login')->assertSee('You were signed out 12 hours after signing in.');
    }

    public function test_keep_me_signed_in_keeps_a_patron_signed_in_on_that_device_for_30_days()
    {
        $cookie = $this->signIn(User::factory()->create(), remember: true)->getCookie(self::REMEMBER_COOKIE, false);
        $this->assertSame(now()->addDays(30)->getTimestamp(), $cookie->getExpiresTime());
        $this->assertTrue($cookie->isSecure() && $cookie->isHttpOnly());

        $this->travel(5)->hours();   // no idle limit
        $this->get('/patron/profile')->assertOk();

        $this->travel(30)->days();
        $this->get('/patron/profile')->assertRedirect(route('login'));
        $this->get('/login')->assertSee('You were signed out 30 days after signing in.');
    }

    public function test_the_cookie_signs_the_patron_back_in_once_the_session_is_gone()
    {
        $patron = User::factory()->create();
        $cookie = $this->signIn($patron, remember: true)->getCookie(self::REMEMBER_COOKIE)->getValue();

        $this->travel(4)->hours();
        $this->sessionEnded();
        $this->withCookie(self::REMEMBER_COOKIE, $cookie)->get('/patron/profile')->assertOk();
        $this->assertAuthenticatedAs($patron);
    }

    public function test_keep_me_signed_in_works_for_staff_too_without_the_warning()
    {
        $staff = $this->staff();
        $response = $this->signIn($staff, remember: true);
        $this->assertSame(now()->addDays(30)->getTimestamp(), $response->getCookie(self::REMEMBER_COOKIE, false)->getExpiresTime());

        $this->travel(10)->hours();   // past both staff limits: on this device they don't apply
        $this->get('/admin/profile')->assertOk()->assertDontSee('id="session-timeout"', false);
        $this->assertAuthenticatedAs($staff);

        $this->sessionEnded();   // the cookie signs them back in
        $this->withCookie(self::REMEMBER_COOKIE, $response->getCookie(self::REMEMBER_COOKIE)->getValue())->get('/admin/profile')->assertOk();
        $this->assertAuthenticatedAs($staff);
    }

    public function test_the_staff_warning_asks_the_time_left_without_it_counting_as_activity()
    {
        $this->signIn($this->staff());
        $this->travel(10)->minutes();
        $this->getJson('/session/status')->assertOk()->assertExactJson(['idle' => 20 * 60, 'absolute' => 470 * 60]);

        $this->travel(10)->minutes();
        $this->getJson('/session/status')->assertJson(['idle' => 10 * 60]);   // asking didn't count
        $this->postJson('/session/stay')->assertOk()->assertJson(['idle' => 30 * 60]);   // "Stay signed in" did

        $this->travel(31)->minutes();
        $this->getJson('/session/status')->assertStatus(401)
            ->assertJson(['message' => 'You were signed out after 30 minutes without activity. Sign in again to continue.']);
        $this->assertGuest();
    }

    public function test_staff_pages_carry_the_warning_and_patron_pages_do_not()
    {
        $this->signIn($this->staff());
        $this->get('/admin/profile')->assertOk()->assertSee('id="session-timeout"', false)->assertSee('Stay signed in');
        $this->post('/logout');

        $this->signIn(User::factory()->create());
        $this->get('/patron/profile')->assertOk()->assertDontSee('id="session-timeout"', false);
    }

    public function test_the_sign_in_page_offers_keep_me_signed_in()
    {
        $this->get('/login')->assertOk()
            ->assertSee('Keep me signed in</label>', false)->assertDontSee('Staff accounts')
            ->assertSee('name="remember"', false);
    }
}
