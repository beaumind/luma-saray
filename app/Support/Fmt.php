<?php

namespace App\Support;

/**
 * Display formatting helpers — Persian digits and money, matching the design
 * (Latin thousands grouping shown with the Persian thousands separator ٬).
 */
class Fmt
{
    private const EN = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    private const FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    public static function fa(string|int|null $value): string
    {
        return str_replace(self::EN, self::FA, (string) $value);
    }

    /**
     * Format an integer amount with ٬ grouping and Persian digits.
     */
    public static function money(int|float|null $amount): string
    {
        $n = (int) round((float) $amount);

        return self::fa(number_format(abs($n), 0, '.', '٬'));
    }
}
