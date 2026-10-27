<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

/** The password policy (OWASP ASVS 6.2): AppServiceProvider sets Password::defaults(). */
class PasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function register(string $password)
    {
        return $this->post('/register', [
            'name' => 'Ana Cruz', 'email' => 'ana@example.org', 'contactnum' => '09171234567',
            'password' => $password, 'password_confirmation' => $password,
        ]);
    }

    public function test_a_password_needs_12_characters_and_no_particular_characters()
    {
        $this->register('Short-pass1')->assertSessionHasErrors('password');      // 11 characters
        $this->assertGuest();

        $this->register('river lantern mango')->assertSessionHasNoErrors();      // only letters and spaces
        $this->assertAuthenticated();
    }

    public function test_long_passphrases_are_allowed_up_to_128_characters()
    {
        $this->register(str_repeat('ab3-', 16))->assertSessionHasNoErrors();      // 64 characters
        auth()->logout();

        $this->post('/register', ['name' => 'Ben', 'email' => 'ben@example.org', 'contactnum' => '09171234568',
            'password' => str_repeat('x', 129), 'password_confirmation' => str_repeat('x', 129)])
            ->assertSessionHasErrors('password');
    }

    public function test_the_most_common_passwords_are_refused_whatever_their_case()
    {
        foreach (['qwerty123456', 'Q1W2E3R4T5Y6', '1qaz2wsx3edc'] as $common) {
            $this->register($common)->assertSessionHasErrors(['password' => 'This password is one of the most commonly used. Choose one that is harder to guess, such as a few unrelated words.']);
        }
        $this->assertGuest();
    }

    public function test_a_password_known_from_a_data_breach_is_refused()
    {
        config(['auth.breached_password_check' => true]);
        $hash = strtoupper(sha1('lantern-river-mango-2026'));
        Http::fake(['api.pwnedpasswords.com/range/' . substr($hash, 0, 5) => Http::response(substr($hash, 5) . ":42\r\nABCDEF0123456789ABCDEF0123456789ABC:3")]);

        $this->register('lantern-river-mango-2026')
            ->assertSessionHasErrors(['password' => 'This password has appeared in a data breach, so attackers try it first. Choose a different one.']);

        // Only the first 5 characters of the hash were sent.
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/range/' . substr($hash, 0, 5)));
    }

    public function test_the_breach_check_lets_a_password_through_when_the_service_cannot_be_reached()
    {
        config(['auth.breached_password_check' => true]);
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 503)]);

        $this->register('lantern-river-mango-2026')->assertSessionHasNoErrors();
        $this->assertAuthenticated();
    }

    public function test_a_patron_changing_their_password_gets_the_same_policy()
    {
        $patron = User::factory()->create(['usertype' => 'Patron']);   // the factory's password is "password"

        $this->actingAs($patron)->post('/patron/profile/update/password', [
            'current_password' => 'password', 'password' => 'qwerty123456', 'password_confirmation' => 'qwerty123456', // gitleaks:allow (a common password, refused)
        ])->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->actingAs($patron)->post('/patron/profile/update/password', [
            'current_password' => 'password', 'password' => self::GOOD_PASSWORD, 'password_confirmation' => self::GOOD_PASSWORD,
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check(self::GOOD_PASSWORD, $patron->fresh()->password));
    }

    public function test_passwords_the_system_gives_out_follow_the_policy()
    {
        $password = generateRandomPassword();

        $this->assertMatchesRegularExpression('/^[A-HJ-KM-NP-Z2-9]{4}(-[A-HJ-KM-NP-Z2-9]{4}){3}$/', $password);
        $this->assertFalse(validator(['password' => $password], ['password' => [Password::defaults()]])->fails());
        $this->assertNotSame($password, generateRandomPassword());
    }
}
