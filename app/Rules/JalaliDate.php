<?php

namespace App\Rules;

use App\Support\JDate;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a value is a parseable Jalali date (Y/m/d).
 * Empty values pass — combine with `required` to enforce presence.
 */
class JalaliDate implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (trim((string) $value) === '') {
            return;
        }

        if (JDate::toGregorian($value) === null) {
            $fail('تاریخ واردشده معتبر نیست.');
        }
    }
}
