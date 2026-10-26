<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Refuses the most commonly used passwords (OWASP ASVS 6.2.4). The list is
 * resources/security/common-passwords.txt: 10,694 passwords of 12 or more characters (the policy's
 * minimum), lowercased, so the check ignores case. Its source and licence: resources/security/README.md.
 */
class NotCommonPassword implements ValidationRule
{
    /** The list as a lookup table, read once per process. */
    private static ?array $list = null;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && isset(self::list()[mb_strtolower($value)])) {
            $fail('This password is one of the most commonly used. Choose one that is harder to guess, such as a few unrelated words.');
        }
    }

    private static function list(): array
    {
        return self::$list ??= array_flip(
            file(resource_path('security/common-passwords.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []
        );
    }
}
