<?php

namespace Tests\Unit;

use App\Support\JDate;
use PHPUnit\Framework\TestCase;

class JDateTest extends TestCase
{
    public function test_converts_jalali_to_gregorian_with_latin_and_persian_digits(): void
    {
        $this->assertSame('2024-07-22', JDate::toGregorian('1403/05/01'));
        $this->assertSame('2024-07-22', JDate::toGregorian('۱۴۰۳/۰۵/۰۱'));
    }

    public function test_converts_gregorian_to_jalali_persian_digits(): void
    {
        $this->assertSame('۱۴۰۳/۰۵/۰۱', JDate::toJalali('2024-07-22'));
        $this->assertSame('1403/05/01', JDate::toJalali('2024-07-22', persianDigits: false));
    }

    public function test_invalid_and_empty_input_returns_null_or_empty(): void
    {
        $this->assertNull(JDate::toGregorian(''));
        $this->assertNull(JDate::toGregorian('not-a-date'));
        $this->assertNull(JDate::toGregorian(null));
        $this->assertSame('', JDate::toJalali(null));
        $this->assertSame('', JDate::toJalali(''));
    }

    public function test_gregorian_month_range_for_jalali_month(): void
    {
        [$start, $end] = JDate::gregorianMonthRange(1403, 5);
        $this->assertSame('2024-07-22', $start->format('Y-m-d'));
        $this->assertSame('2024-08-22', $end->format('Y-m-d'));
    }

    public function test_gregorian_year_range_for_jalali_year(): void
    {
        [$start, $end] = JDate::gregorianYearRange(1403);
        $this->assertSame('2024-03-20', $start->format('Y-m-d'));
        $this->assertSame('2025-03-21', $end->format('Y-m-d'));
    }
}
