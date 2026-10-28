<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\GoogleSignIn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

/**
 * "Continue with Google" for patrons (GoogleSignInController): a new Google account becomes a patron without a
 * password, an existing patron's account is linked by its address, and staff, banned and removed accounts are
 * refused. An account without a password confirms with Google where others type their password. Google's answer is
 * faked (Socialite), so no Google account or internet is needed.
 */
class GoogleSignInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'test-client.apps.googleusercontent.com', 'services.google.client_secret' => 'test-secret']);
    }

    /** What Google answers at the callback: its userinfo claims, or an exception. */
    private function googleSays(array|\Throwable $answer): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('enablePKCE')->andReturnSelf();
        if ($answer instanceof \Throwable) {
            $provider->shouldReceive('user')->andThrow($answer);
        } else {
            $answer += ['sub' => '1045', 'name' => 'Juan dela Cruz', 'email' => 'juan@gmail.com', 'email_verified' => true];
            $provider->shouldReceive('user')->andReturn((new GoogleUser)->setRaw($answer)
                ->map(['id' => $answer['sub'], 'name' => $answer['name'], 'email' => $answer['email']]));
        }
        $socialite = Mockery::mock(Factory::class);   // a new one each time: a test may ask Google more than once
        $socialite->shouldReceive('driver')->with('google')->andReturn($provider);
        Socialite::swap($socialite);
    }

    private function patron(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['usertype' => 'Patron', 'password' => Hash::make(self::GOOD_PASSWORD)]);
    }

    public function test_the_button_and_its_routes_are_there_only_when_set_up()
    {
        $this->get('/login')->assertSee('Continue with Google');
        $this->get('/register')->assertSee('Continue with Google');

        // The code flow with state and PKCE, asking only for the address and the name.
        $to = $this->get('/auth/google')->assertRedirect()->headers->get('Location');
        parse_str(parse_url($to, PHP_URL_QUERY), $query);
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/auth?', $to);
        $this->assertSame(['test-client.apps.googleusercontent.com', url('/auth/google/callback'), 'openid profile email', 'code', 'S256'],
            [$query['client_id'], $query['redirect_uri'], $query['scope'], $query['response_type'], $query['code_challenge_method']]);
        $this->assertNotEmpty($query['state']);

        config(['services.google.client_secret' => null]);
        $this->get('/login')->assertDontSee('Continue with Google');
        $this->get('/auth/google')->assertNotFound();
        $this->get('/auth/google/callback')->assertNotFound();
    }

    public function test_a_new_google_account_becomes_a_patron_without_a_password()
    {
        $this->googleSays(['email' => 'Juan@Gmail.com']);

        $this->get('/auth/google/callback?code=c&state=s')->assertRedirect();
        $user = User::where('email', 'juan@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(['Patron', 'Juan dela Cruz', '1045', false], [$user->usertype, $user->name, $user->google_id, $user->hasPassword()]);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotEmpty($user->library_code);
        $this->assertTrue(GoogleSignIn::recentlyConfirmed(session()->driver()));

        // Without a password, signing in with one fails.
        auth()->logout();
        $this->post('/login', ['email' => 'juan@gmail.com', 'password' => ''])->assertSessionHasErrors();
        $this->post('/login', ['email' => 'juan@gmail.com', 'password' => self::GOOD_PASSWORD])->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_an_existing_patron_is_linked_by_address_and_keeps_their_password()
    {
        $patron = $this->patron(['email' => 'juan@gmail.com']);
        $this->googleSays(['email' => 'JUAN@gmail.com']);

        $this->get('/auth/google/callback?code=c&state=s')->assertRedirect();
        $this->assertAuthenticatedAs($patron);
        $this->assertSame('1045', $patron->fresh()->google_id);
        $this->assertSame(1, User::count());

        auth()->logout();
        $this->post('/login', ['email' => 'juan@gmail.com', 'password' => self::GOOD_PASSWORD]);
        $this->assertAuthenticatedAs($patron);
    }

    public function test_staff_banned_removed_and_unconfirmed_accounts_are_refused()
    {
        $refused = function (array $google, string $message) {
            $this->googleSays($google);
            $this->get('/auth/google/callback?code=c&state=s')->assertRedirect('/login')->assertSessionHasErrors(['google' => $message]);
            $this->assertGuest();
        };

        $this->patron(['email' => 'staff@fnri.dost.gov.ph', 'usertype' => 'Administrator']);
        $refused(['email' => 'staff@fnri.dost.gov.ph'], 'Staff accounts sign in with their email and password, not with Google.');

        $refused(['email' => 'new@gmail.com', 'email_verified' => false], 'Google hasn’t confirmed this Google account’s email address, so it can’t be used here.');

        $this->patron(['email' => 'removed@gmail.com'])->delete();
        $refused(['email' => 'removed@gmail.com'], 'This account can’t be used. Please contact the library.');

        $this->patron(['email' => 'linked@gmail.com'])->forceFill(['google_id' => '999'])->save();
        $refused(['email' => 'linked@gmail.com'], 'The eLibrary account for this address is linked to another Google account. Continue with that one, or sign in with your password.');

        $banned = $this->patron(['email' => 'banned@gmail.com']);
        $banned->is_banned = true;
        $banned->save();
        $this->googleSays(['email' => 'banned@gmail.com']);
        $this->get('/auth/google/callback?code=c&state=s')->assertRedirect('/login')->assertSessionHas('account_banned', true)->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame(4, User::withTrashed()->count());   // the four above: nothing made
    }

    public function test_cancelling_or_an_old_return_from_google_signs_no_one_in()
    {
        $this->get('/auth/google/callback?error=access_denied&state=s')->assertRedirect('/login')->assertSessionHas('notice', 'Google sign-in was cancelled.');

        $this->googleSays(new InvalidStateException());
        $this->get('/auth/google/callback?code=c&state=old')->assertRedirect('/login')->assertSessionHasErrors(['google' => 'Google sign-in didn’t finish. Please try again.']);
        $this->assertGuest();
    }

    public function test_an_account_without_a_password_confirms_with_google_to_set_one_or_end_sessions()
    {
        $user = User::factory()->create(['usertype' => 'Patron', 'password' => null]);
        $user->forceFill(['google_id' => '1045'])->save();
        $setPassword = fn () => $this->actingAs($user)->from('/patron/profile')
            ->post('/patron/profile/update/password', ['password' => self::GOOD_PASSWORD, 'password_confirmation' => self::GOOD_PASSWORD]);

        // Long after signing in: confirm first. The profile offers it.
        $this->actingAs($user)->get('/patron/profile')->assertOk()->assertSee('Set a Password')->assertSee('Confirm with Google');
        $setPassword()->assertSessionHasErrorsIn('updatePassword', ['current_password' => 'Confirm with Google first.']);
        $this->actingAs($user)->from('/patron/profile')->delete('/sessions', ['end' => 'others'])
            ->assertSessionHasErrorsIn('endSessions', ['password' => 'Confirm with Google first.']);

        // "Confirm with Google": to Google for this account, and back where it started.
        $to = $this->actingAs($user)->from('/patron/profile')->get('/auth/google/confirm')->headers->get('Location');
        $this->assertStringContainsString('login_hint=' . urlencode($user->email), $to);
        $this->googleSays(['sub' => '777', 'email' => $user->email]);   // another Google account
        $this->actingAs($user)->get('/auth/google/callback?code=c&state=s')
            ->assertRedirect('/patron/profile')->assertSessionHasErrors('google');
        $this->assertFalse(GoogleSignIn::recentlyConfirmed(session()->driver()));

        $this->withSession(['google.confirm' => url('/patron/profile')]);
        $this->googleSays(['sub' => '1045', 'email' => $user->email]);
        $this->actingAs($user)->get('/auth/google/callback?code=c&state=s')
            ->assertRedirect('/patron/profile')->assertSessionHas('success', 'Confirmed. You can go ahead.');

        // Confirmed: it sets a password without a current one, and can sign out its other sessions.
        $this->actingAs($user)->delete('/sessions', ['end' => 'others'])->assertSessionHasNoErrors();
        $setPassword()->assertRedirect('/patron/profile')->assertSessionHas('success', 'Password set. You can now also sign in with your email and password.');
        $this->assertTrue(Hash::check(self::GOOD_PASSWORD, $user->fresh()->password));

        // Fifteen minutes on, the confirmation has run out.
        $this->travel(GoogleSignIn::CONFIRM_MINUTES + 1)->minutes();
        $this->assertFalse(GoogleSignIn::recentlyConfirmed(session()->driver()));
    }
}
