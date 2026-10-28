<?php

namespace Tests\Feature\Auth;

use App\Rules\HumanCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Sign-up's "I'm human" check (Cloudflare Turnstile, App\Rules\HumanCheck), which stands in for email verification:
 * the account is made only when Cloudflare confirms the form's token. Cloudflare is faked; its test keys are used.
 */
class HumanCheckTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFY = HumanCheck::ORIGIN . '/turnstile/v0/siteverify';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.turnstile.site_key' => '1x00000000000000000000AA', 'services.turnstile.secret_key' => '1x0000000000000000000000000000000AA']);
    }

    private function signUp(array $extra = [])
    {
        return $this->post('/register', $extra + [
            'name' => 'Test User', 'email' => 'test@example.com', 'contactnum' => '09171234567',
            'password' => self::GOOD_PASSWORD, 'password_confirmation' => self::GOOD_PASSWORD,
        ]);
    }

    public function test_the_sign_up_page_alone_lets_cloudflare_in()
    {
        $page = $this->get('/register')->assertOk()
            ->assertSee('data-sitekey="1x00000000000000000000AA"', false)
            ->assertSee('data-action="register"', false)
            ->assertSee('src="https://challenges.cloudflare.com/turnstile/v0/api.js"', false);
        $policy = $page->headers->get('Content-Security-Policy') ?? $page->headers->get('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString("https://cdn.jsdelivr.net https://challenges.cloudflare.com;", $policy);
        $this->assertStringContainsString("frame-src 'self' https://challenges.cloudflare.com;", $policy);

        $home = $this->get('/login');
        $this->assertStringNotContainsString('challenges.cloudflare.com', $home->headers->get('Content-Security-Policy') . $home->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_cloudflare_confirms_the_token_before_the_account_is_made()
    {
        Http::fake([self::VERIFY => Http::response(['success' => true, 'action' => 'register', 'error-codes' => []])]);

        $this->signUp(['cf-turnstile-response' => 'token-from-the-widget'])->assertRedirect();
        $this->assertAuthenticated();
        Http::assertSent(fn (Request $r) => $r->url() === self::VERIFY && $r['secret'] === '1x0000000000000000000000000000000AA'
            && $r['response'] === 'token-from-the-widget' && ! isset($r['remoteip']));   // the visitor's address isn't sent
    }

    public function test_no_account_without_a_passed_check()
    {
        Http::fake([self::VERIFY => Http::sequence()
            ->push(['success' => false, 'error-codes' => ['timeout-or-duplicate']])
            ->push(['success' => true, 'action' => 'login', 'error-codes' => []])]);   // a token made for another form

        $this->signUp()->assertSessionHasErrors(['cf-turnstile-response' => 'Please wait for the “I’m human” check above the button to finish, then try again.']);
        $this->signUp(['cf-turnstile-response' => 'used-token'])->assertSessionHasErrors(['cf-turnstile-response' => 'The “I’m human” check didn’t pass. Please try it again.']);
        $this->signUp(['cf-turnstile-response' => 'login-token'])->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_when_cloudflare_cant_be_reached_the_sign_up_waits()
    {
        Http::fake([self::VERIFY => fn () => throw new ConnectionException('timed out')]);

        $this->signUp(['cf-turnstile-response' => 'token'])
            ->assertSessionHasErrors(['cf-turnstile-response' => 'The “I’m human” check couldn’t be completed just now. Please try again in a minute.']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_without_keys_there_is_no_check()
    {
        config(['services.turnstile.secret_key' => null]);
        Http::fake();

        $this->get('/register')->assertOk()->assertDontSee('cf-turnstile', false);
        $this->signUp()->assertRedirect();
        $this->assertAuthenticated();
        Http::assertNothingSent();
    }
}
