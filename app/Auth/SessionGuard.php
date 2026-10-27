<?php

namespace App\Auth;

use Illuminate\Auth\SessionGuard as BaseSessionGuard;

/**
 * Laravel's session guard, with the "Keep me signed in" cookie named like the session cookie:
 * __Host-remember_web (__Secure- with SESSION_DOMAIN set, no prefix on plain http:// in development), so plain
 * HTTP or another subdomain can't plant one (OWASP ASVS 3.3.1). Laravel would name it remember_web_<sha1>.
 * AuthServiceProvider builds it in place of Laravel's.
 */
class SessionGuard extends BaseSessionGuard
{
    public function getRecallerName()
    {
        return config('session.cookie_prefix') . 'remember_' . $this->name;
    }

    /**
     * A new remember token, so no "Keep me signed in" cookie made before it works any more; this device's cookie, if
     * it has one, is made again. For signing out another device that was kept signed in (App\Services\BrowserSessions).
     */
    public function renewRememberToken(): void
    {
        $user = $this->user();
        $this->cycleRememberToken($user);
        if ($this->recaller() || $this->getCookieJar()->hasQueued($this->getRecallerName())) {
            $this->queueRecallerCookie($user);
        }
    }

    /** The session key stays Laravel's, which is named after the class too, so switching guards signs nobody out. */
    public function getName()
    {
        return 'login_' . $this->name . '_' . sha1(BaseSessionGuard::class);
    }
}
