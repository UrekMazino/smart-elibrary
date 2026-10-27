<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_a_failed_sign_in_says_why_and_keeps_the_email()
    {
        $user = User::factory()->create();

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertRedirect('/login');

        $this->get('/login')
            ->assertSee('These credentials do not match our records.')
            ->assertSee('value="' . $user->email . '"', false);
    }

    public function test_the_sign_in_page_confirms_a_password_reset()
    {
        $this->withSession(['status' => 'Your password has been reset.'])->get('/login')
            ->assertSee('Your password has been reset.');
    }

    public function test_banned_users_can_not_authenticate_and_see_the_banned_popup()
    {
        $user = User::factory()->create();
        $user->is_banned = true;
        $user->save();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('account_banned', true);

        $this->get('/login')
            ->assertSee('Account banned')
            ->assertSee(config('app.admin_email'));
    }
}
