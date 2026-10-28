<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The "I'm human" check on sign-up (Cloudflare Turnstile), in place of email verification: the widget on the form
 * gives the browser a token, and Cloudflare confirms it here (siteverify) before the account is made. A token is
 * good for one check within 5 minutes, and only for the form it was made for (its action). The visitor's address
 * isn't sent: their browser has already reached Cloudflare to get the token.
 *
 * Off while TURNSTILE_SITE_KEY or TURNSTILE_SECRET_KEY is unset. Cloudflare's test keys pass or fail every check
 * on any host (DEPLOYMENT_CHECKLIST.md); production uses the agency's own. When Cloudflare can't be reached, the
 * sign-up is refused, not let through: the patron can try again, or continue with Google.
 */
class HumanCheck implements ValidationRule
{
    /** Where the widget, its frame and the check come from (SecurityHeaders lets it in on the forms that use it). */
    public const ORIGIN = 'https://challenges.cloudflare.com';

    /** Runs when the token is missing too (a form sent before the widget finished, or by a script). */
    public bool $implicit = true;

    /** Cloudflare's answers that mean our keys are wrong, not the visitor: worth a line in the log. */
    private const SETUP_ERRORS = ['missing-input-secret', 'invalid-input-secret', 'bad-request'];

    public function __construct(private string $action)
    {
    }

    public static function enabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '' || strlen($value) > 2048) {
            $fail('Please wait for the “I’m human” check above the button to finish, then try again.');
            return;
        }

        try {
            $answer = Http::asForm()->timeout(5)->post(self::ORIGIN . '/turnstile/v0/siteverify', [
                'secret'   => config('services.turnstile.secret_key'),
                'response' => $value,
            ])->throw()->json();
        } catch (Throwable $e) {
            Log::warning('The “I’m human” check could not reach Cloudflare: ' . $e->getMessage());
            $fail('The “I’m human” check couldn’t be completed just now. Please try again in a minute.');
            return;
        }

        if ($codes = array_intersect((array) ($answer['error-codes'] ?? []), self::SETUP_ERRORS)) {
            Log::warning('The “I’m human” check is set up wrongly (TURNSTILE_SECRET_KEY?): ' . implode(', ', $codes));
        }
        // Cloudflare's test keys answer without an action; real keys always name the one the form set.
        $action = (string) ($answer['action'] ?? '');
        if (empty($answer['success']) || ($action !== '' && $action !== $this->action)) {
            $fail('The “I’m human” check didn’t pass. Please try it again.');
        }
    }
}
