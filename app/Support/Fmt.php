<?php

namespace App\Support;

/**
 * Display formatting helpers.
 *
 * Money is ALWAYS stored in rial. The organization's `currency` setting only
 * controls how it is displayed and how user input is interpreted:
 *  - toman:  displayed value = rial / 10; input is multiplied by 10 to store.
 *  - rial:   displayed value = rial as-is.
 */
class Fmt
{
    private const EN = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    private const FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    /** Optional per-request override (e.g. for exports); falls back to the org setting. */
    public static ?string $override = null;

    public static function fa(string|int|null $value): string
    {
        return str_replace(self::EN, self::FA, (string) $value);
    }

    public static function currencyCode(): string
    {
        if (self::$override !== null) {
            return self::$override;
        }

        return auth()->user()?->organization?->currency ?? 'toman';
    }

    public static function currency(): string
    {
        return self::currencyCode() === 'rial' ? 'ریال' : 'تومان';
    }

    /**
     * Format a rial amount in the display currency, with Persian digits.
     */
    public static function money(int|float|null $rial): string
    {
        $value = self::display((int) round((float) $rial));

        return self::fa(number_format(abs($value), 0, '.', '٬'));
    }

    /** Rial → displayed numeric value (no formatting). */
    public static function display(int $rial): int
    {
        return self::currencyCode() === 'toman' ? intdiv($rial, 10) : $rial;
    }

    /** A user-entered amount (in the display currency) → rial for storage. */
    public static function toRial(int|float|null $input): int
    {
        $n = (int) round((float) $input);

        return self::currencyCode() === 'toman' ? $n * 10 : $n;
    }
}
