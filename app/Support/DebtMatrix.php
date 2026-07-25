<?php

namespace App\Support;

use App\Models\Building;
use App\Models\LedgerTransaction;
use App\Models\Unit;
use Morilog\Jalali\Jalalian;

/**
 * Builds the building debt matrix (units × time periods) shown on the Reports
 * screen and used by the Excel/PDF/image exports.
 *
 * Periods can be monthly, seasonal (Jalali فصل), or yearly. Each period cell
 * shows the amount PAID in that period, coloured by how it compares to what
 * was charged — green = fully paid, yellow = partial, red = unpaid.
 */
class DebtMatrix
{
    private const MONTH_NAMES = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    private const SEASON_NAMES = ['بهار', 'تابستان', 'پاییز', 'زمستان'];

    public const PERIOD_TYPES = ['monthly' => 'ماهانه', 'seasonal' => 'فصلی', 'yearly' => 'سالانه'];

    /**
     * @return array{title:string, periodType:string, periods:array, columns:array, rows:array}
     */
    public static function build(?int $buildingId = null, string $periodType = 'seasonal', int $count = 4): array
    {
        $periodType = array_key_exists($periodType, self::PERIOD_TYPES) ? $periodType : 'seasonal';
        $count = max(1, min(24, $count));
        $periods = self::makePeriods($periodType, $count);
        $windowStart = $periods[0]['start'];

        $units = Unit::query()
            ->where('is_active', true)
            ->when($buildingId, fn ($q) => $q->where('building_id', $buildingId))
            ->with(['activeResidents', 'building'])
            ->orderBy('building_id')->orderByRaw('LENGTH(number)')->orderBy('number')
            ->get();

        $txByUnit = LedgerTransaction::query()
            ->whereIn('unit_id', $units->pluck('id'))
            ->get(['unit_id', 'direction', 'type', 'amount', 'transaction_date'])
            ->groupBy('unit_id');

        $rows = [];
        foreach ($units as $unit) {
            $txs = $txByUnit->get($unit->id, collect());
            $owner = $unit->activeResidents->firstWhere('type', 'owner');
            $resident = $unit->activeResidents->first();

            $pastDebt = 0;
            $totalDebt = 0;
            $latestCharge = ['date' => null, 'amount' => 0];
            $buckets = array_fill(0, count($periods), ['paid' => 0, 'charged' => 0]);

            foreach ($txs as $t) {
                $signed = $t->direction === 'debit' ? $t->amount : -$t->amount;
                $totalDebt += $signed;
                $date = $t->transaction_date;

                // Track the latest single monthly charge for the "شارژ ماهانه" column.
                if ($t->type === 'charge' && $t->direction === 'debit'
                    && ($latestCharge['date'] === null || $date >= $latestCharge['date'])) {
                    $latestCharge = ['date' => $date, 'amount' => $t->amount];
                }

                if ($date < $windowStart) {
                    $pastDebt += $signed;

                    continue;
                }
                foreach ($periods as $idx => $p) {
                    if ($date >= $p['start'] && $date < $p['end']) {
                        if ($t->direction === 'credit') {
                            $buckets[$idx]['paid'] += $t->amount;
                        } else {
                            $buckets[$idx]['charged'] += $t->amount;
                        }
                        break;
                    }
                }
            }

            $cells = [];
            foreach ($buckets as $b) {
                $charged = $b['charged'];
                $paid = $b['paid'];
                if ($charged <= 0 && $paid <= 0) {
                    $state = 'neutral';
                } elseif ($paid >= $charged && $charged > 0) {
                    $state = 'paid';
                } elseif ($paid <= 0) {
                    $state = 'unpaid';
                } else {
                    $state = 'partial';
                }
                $cells[] = ['value' => $paid, 'state' => $state];
            }

            $rows[] = [
                'number' => $unit->number,
                'resident' => $resident?->name ?? '—',
                'owner' => $owner?->name ?? ($resident?->name ?? '—'),
                'count' => (int) $unit->activeResidents->sum('resident_count'),
                'monthly_charge' => $latestCharge['amount'],
                'past_debt' => max($pastDebt, 0),
                'months' => $cells,
                'total_debt' => max($totalDebt, 0),
                'notes' => $unit->notes ?? '',
            ];
        }

        $building = $buildingId ? Building::find($buildingId) : null;
        $title = 'گزارش بدهی واحدها'.($building ? ' — '.$building->name : '');

        return [
            'title' => $title,
            'periodType' => $periodType,
            'periods' => $periods,
            'columns' => self::columns($periods),
            'rows' => $rows,
        ];
    }

    /**
     * Build the list of periods (oldest → newest), each with a label and a
     * Gregorian [start, end) Carbon range.
     */
    private static function makePeriods(string $type, int $count): array
    {
        $now = Jalalian::now();
        $jy = (int) $now->getYear();
        $jm = (int) $now->getMonth();
        $periods = [];

        if ($type === 'yearly') {
            for ($i = 0; $i < $count; $i++) {
                $y = $jy - $i;
                [$s, $e] = JDate::gregorianYearRange($y);
                array_unshift($periods, [
                    'label' => 'سال '.JDate::toPersianDigits((string) $y),
                    'start' => $s, 'end' => $e,
                ]);
            }
        } elseif ($type === 'monthly') {
            for ($i = 0; $i < $count; $i++) {
                [$s, $e] = JDate::gregorianMonthRange($jy, $jm);
                array_unshift($periods, [
                    'label' => self::MONTH_NAMES[$jm - 1].' '.JDate::toPersianDigits((string) $jy),
                    'start' => $s, 'end' => $e,
                ]);
                if (--$jm < 1) {
                    $jm = 12;
                    $jy--;
                }
            }
        } else { // seasonal
            $si = intdiv($jm - 1, 3); // 0..3
            for ($i = 0; $i < $count; $i++) {
                [$s] = JDate::gregorianMonthRange($jy, $si * 3 + 1);
                [, $e] = JDate::gregorianMonthRange($jy, $si * 3 + 3);
                array_unshift($periods, [
                    'label' => self::SEASON_NAMES[$si].' '.JDate::toPersianDigits((string) $jy),
                    'start' => $s, 'end' => $e,
                ]);
                if (--$si < 0) {
                    $si = 3;
                    $jy--;
                }
            }
        }

        return $periods;
    }

    /**
     * All available columns with keys + labels (period columns expanded).
     */
    public static function columns(array $periods): array
    {
        $cols = [
            ['key' => 'number', 'label' => 'واحد'],
            ['key' => 'resident', 'label' => 'ساکن'],
            ['key' => 'owner', 'label' => 'مالک'],
            ['key' => 'count', 'label' => 'تعداد نفرات'],
            ['key' => 'monthly_charge', 'label' => 'شارژ ماهانه'],
            ['key' => 'past_debt', 'label' => 'بدهی از گذشته'],
        ];
        foreach ($periods as $i => $p) {
            $cols[] = ['key' => 'month_'.$i, 'label' => $p['label'] ?? '', 'month' => $i];
        }
        $cols[] = ['key' => 'total_debt', 'label' => 'مجموع بدهی'];
        $cols[] = ['key' => 'notes', 'label' => 'توضیحات'];

        return $cols;
    }

    /**
     * Column keys selectable by the user (number + resident are always shown).
     */
    public static function optionalColumnKeys(array $periods): array
    {
        return collect(self::columns($periods))
            ->pluck('key')
            ->reject(fn ($k) => in_array($k, ['number', 'resident']))
            ->values()->all();
    }
}
