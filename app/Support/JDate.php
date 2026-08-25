<?php

namespace App\Support;

use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

/**
 * Jalali (Persian) date helpers.
 *
 * The database always stores Gregorian dates (Y-m-d). The UI always shows
 * and accepts Jalali dates (Y/m/d, Persian digits). This class is the single
 * conversion boundary between the two.
 */
class JDate
{
    private const FA_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    private const AR_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    private const EN_DIGITS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /**
     * Convert a Jalali "Y/m/d" string (any digit set) to Gregorian "Y-m-d".
     */
    public static function toGregorian(?string $jalali): ?string
    {
        $jalali = self::toLatinDigits(trim((string) $jalali));

        if (! preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $jalali)) {
            return null;
        }

        try {
            return Jalalian::fromFormat('Y/m/d', $jalali)->toCarbon()->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Convert a Gregorian date to a Jalali "Y/m/d" string (Persian digits).
     */
    public static function toJalali(Carbon|string|null $gregorian, bool $persianDigits = true): string
    {
        if (empty($gregorian)) {
            return '';
        }

        try {
            $carbon = $gregorian instanceof Carbon ? $gregorian : Carbon::parse($gregorian);
            $value = Jalalian::fromCarbon($carbon)->format('Y/m/d');

            return $persianDigits ? self::toPersianDigits($value) : $value;
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Today in Jalali "Y/m/d" (Persian digits).
     */
    public static function today(bool $persianDigits = true): string
    {
        return self::toJalali(Carbon::now(), $persianDigits);
    }

    /**
     * Gregorian [start, end) Carbon range for a given Jalali year + month.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    /**
     * [firstDay, lastDay] of the current Jalali month as Jalali (Persian-digit)
     * strings — e.g. ['۱۴۰۵/۰۵/۰۱', '۱۴۰۵/۰۵/۳۱']. Used to default date filters.
     */
    public static function thisMonthJalaliRange(): array
    {
        $now = Jalalian::now();
        [$start, $end] = self::gregorianMonthRange((int) $now->getYear(), (int) $now->getMonth());

        return [self::toJalali($start), self::toJalali($end->copy()->subDay())];
    }

    public static function gregorianMonthRange(int $jYear, int $jMonth): array
    {
        $start = Jalalian::fromFormat('Y/m/d', sprintf('%d/%02d/01', $jYear, $jMonth))
            ->toCarbon()->startOfDay();

        $nextYear = $jMonth === 12 ? $jYear + 1 : $jYear;
        $nextMonth = $jMonth === 12 ? 1 : $jMonth + 1;

        $end = Jalalian::fromFormat('Y/m/d', sprintf('%d/%02d/01', $nextYear, $nextMonth))
            ->toCarbon()->startOfDay();

        return [$start, $end];
    }

    /**
     * Gregorian [start, end) Carbon range for a whole Jalali year.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function gregorianYearRange(int $jYear): array
    {
        $start = Jalalian::fromFormat('Y/m/d', sprintf('%d/01/01', $jYear))
            ->toCarbon()->startOfDay();

        $end = Jalalian::fromFormat('Y/m/d', sprintf('%d/01/01', $jYear + 1))
            ->toCarbon()->startOfDay();

        return [$start, $end];
    }

    public static function toPersianDigits(string $value): string
    {
        return str_replace(self::EN_DIGITS, self::FA_DIGITS, $value);
    }

    public static function toLatinDigits(string $value): string
    {
        return str_replace(
            array_merge(self::FA_DIGITS, self::AR_DIGITS),
            array_merge(self::EN_DIGITS, self::EN_DIGITS),
            $value
        );
    }
}
