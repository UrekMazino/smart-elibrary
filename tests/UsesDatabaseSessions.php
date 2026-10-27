<?php

namespace Tests;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sessions kept in the database, as on the server (phpunit.xml keeps them in memory), and sessions on other devices
 * written straight into the table, as Laravel keeps them. For the pages that read the sessions table.
 */
trait UsesDatabaseSessions
{
    protected const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';
    protected const FIREFOX = 'Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0';
    protected const EDGE = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0';

    protected function useDatabaseSessions(): void
    {
        config(['session.driver' => 'database']);
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
        Auth::forgetGuards();
    }

    /** The next request comes from this browser: it sends the session cookie back, as a browser would. */
    protected function here(): static
    {
        return $this->withCookie(config('session.cookie'), session()->getId());
    }

    /** A session of the user on another device. */
    protected function elsewhere(User $user, array $clocks = [], string $agent = self::IPHONE, int $minutesAgo = 10, array $data = []): string
    {
        $lastActive = now()->subMinutes($minutesAgo)->getTimestamp();

        return $this->sessionRow($user->id, $agent, $lastActive,
            $data + ['sign_in' => $clocks + ['at' => $lastActive, 'active_at' => $lastActive, 'remember' => false]]);
    }

    /** A visitor's session: not signed in. */
    protected function visitor(string $agent = self::FIREFOX, int $minutesAgo = 5): string
    {
        return $this->sessionRow(null, $agent, now()->subMinutes($minutesAgo)->getTimestamp(), ['_token' => Str::random(40)]);
    }

    private function sessionRow(?int $userId, string $agent, int $lastActive, array $data): string
    {
        $id = Str::random(40);
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '203.0.113.7',
            'user_agent' => $agent,
            'payload' => base64_encode(serialize($data)),
            'last_activity' => $lastActive,
        ]);

        return $id;
    }
}
