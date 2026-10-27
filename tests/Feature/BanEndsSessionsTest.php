<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * A banned account is signed out everywhere (OWASP ASVS 7.4.2; EndBannedSessions, AdminController::toggleUserFlag).
 * The ban used to be checked only when signing in with the password.
 */
class BanEndsSessionsTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    public function test_a_signed_in_patron_who_is_banned_is_signed_out_at_their_next_request()
    {
        $patron = User::factory()->create()->fresh();   // fresh: usertype is the database's default, Patron
        $this->actingAs($patron)->get('/patron/profile')->assertOk();

        $patron->forceFill(['is_banned' => true])->save();

        $this->get('/patron/profile')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get('/login')
            ->assertSee('You were signed out because this account has been banned.')
            ->assertSee('Account banned');   // the popup on how to have the ban lifted
    }

    public function test_keep_me_signed_in_no_longer_signs_a_banned_patron_back_in()
    {
        $patron = User::factory()->create();
        $cookie = $this->post('/login', ['email' => $patron->email, 'password' => 'password', 'remember' => '1'])
            ->getCookie('__Host-remember_web')->getValue();
        $patron->forceFill(['is_banned' => true])->save();
        $token = $patron->fresh()->remember_token;

        // Later: the session is gone, only the cookie is left.
        $this->app['session.store']->flush();
        Auth::forgetGuards();
        $this->withCookie('__Host-remember_web', $cookie)->get('/patron/profile')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNotSame($token, $patron->fresh()->remember_token);   // so that cookie works nowhere
    }

    public function test_banning_also_ends_keep_me_signed_in_at_once()
    {
        $this->seedAccess();
        // A developer passes every permission check: in tests the seeded manage.patron.status can lose its id to a
        // permission a migration added first (seedAccess keeps those), so a super_admin may not have it here.
        $admin = tap(User::factory()->create(['usertype' => 'Developer']))->assignRole('developer');
        $patron = User::factory()->create();
        $token = $patron->fresh()->remember_token;

        $this->actingAs($admin)->postJson('/admin/user/toggle-flag', ['user_id' => $patron->id, 'flag' => 'banned'])
            ->assertOk()->assertJson(['value' => true]);

        // Its sessions end at their next request (the tests above); its "Keep me signed in" cookies, now.
        $this->assertNotSame($token, $patron->fresh()->remember_token);
    }
}
