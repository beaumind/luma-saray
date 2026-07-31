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

    public const PERIOD_TYPES = ['monthly' => 'ماهانه', 'seasonal' => 'فصلی', 'yearly' => 'سالانه'];

    /**
     * @return array{title:string, periodType:string, periods:array, columns:array, rows:array}
     */
    public static function build(?int $buildingId = null, string $periodType = 'seasonal', int $count = 4): array
    {
        $periodType = array_key_exists($periodType, self::PERIOD_TYPES) ? $periodType : 'seasonal';
        $count = max(1, min(12, $count));
        $periods = self::periods($periodType, $count);
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
                // Only charge/cost debits and settlement payments affect debt;
                // standalone creditor entries are tracked separately.
                if ($t->direction === 'debit' && in_array($t->type, ['charge', 'cost', 'expense'])) {
                    $signed = $t->amount;
                } elseif ($t->direction === 'credit' && $t->type === 'payment') {
                    $signed = -$t->amount;
                } else {
                    $signed = 0;
                }
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
                        if ($t->direction === 'credit' && $t->type === 'payment') {
                            $buckets[$idx]['paid'] += $t->amount;
                        } elseif ($t->direction === 'debit' && in_array($t->type, ['charge', 'cost', 'expense'])) {
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
     * Build the list of month columns (oldest → newest), each with a label and
     * a Gregorian [start, end) Carbon range. Every period type renders month
     * columns; the type only chooses the window size and alignment:
     *  - monthly:  last N calendar months (rolling, up to the current month)
     *  - seasonal: last N Jalali seasons in full (N×3 months, season-aligned)
     *  - yearly:   last N Jalali years in full (N×12 months, year-aligned)
     */
    public static function periods(string $type, int $count): array
    {
        $count = max(1, min(12, $count));
        $now = Jalalian::now();
        $jy = (int) $now->getYear();
        $jm = (int) $now->getMonth();

        // Ordered list of [year, month] pairs, oldest first.
        $list = [];
        if ($type === 'yearly') {
            for ($k = $count - 1; $k >= 0; $k--) {
                for ($m = 1; $m <= 12; $m++) {
                    $list[] = [$jy - $k, $m];
                }
            }
        } elseif ($type === 'monthly') {
            $y = $jy;
            $m = $jm;
            for ($i = 0; $i < $count; $i++) {
                array_unshift($list, [$y, $m]);
                if (--$m < 1) {
                    $m = 12;
                    $y--;
                }
            }
        } else { // seasonal — full seasons of 3 months, aligned to season start
            $si = intdiv($jm - 1, 3);
            $sy = $jy;
            $seasons = [];
            for ($i = 0; $i < $count; $i++) {
                array_unshift($seasons, [$sy, $si]);
                if (--$si < 0) {
                    $si = 3;
                    $sy--;
                }
            }
            foreach ($seasons as [$syy, $sii]) {
                for ($m = $sii * 3 + 1; $m <= $sii * 3 + 3; $m++) {
                    $list[] = [$syy, $m];
                }
            }
        }

        // Include the year in labels only when the window spans several years.
        $showYear = count(array_unique(array_map(fn ($p) => $p[0], $list))) > 1;

        $periods = [];
        foreach ($list as [$y, $m]) {
            [$s, $e] = JDate::gregorianMonthRange($y, $m);
            $periods[] = [
                'label' => self::MONTH_NAMES[$m - 1].($showYear ? ' '.JDate::toPersianDigits((string) $y) : ''),
                'start' => $s, 'end' => $e,
            ];
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
