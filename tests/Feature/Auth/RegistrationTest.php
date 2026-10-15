<?php

namespace Tests\Feature\Auth;

use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'contactnum' => '09171234567',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
        $this->assertSame('Patron', auth()->user()->fresh()->usertype);
        $this->assertNotEmpty(auth()->user()->library_code);
    }

    public function test_registering_cannot_grant_staff_access_or_unban()
    {
        // usertype / is_banned are not mass-assignable (User::$fillable), so extra form fields are ignored.
        $this->post('/register', [
            'name' => 'Sneaky User',
            'email' => 'sneaky@example.com',
            'contactnum' => '09171234567',
            'password' => 'password',
            'password_confirmation' => 'password',
            'usertype' => 'Administrator',
            'is_banned' => 0,
        ]);

        $this->assertAuthenticated();
        $this->assertSame('Patron', auth()->user()->fresh()->usertype);
    }
}
