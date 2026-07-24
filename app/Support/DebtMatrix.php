<?php

namespace App\Support;

use App\Models\Building;
use App\Models\LedgerTransaction;
use App\Models\Unit;
use Morilog\Jalali\Jalalian;

/**
 * Builds the building debt matrix (units × Jalali months) shown on the Reports
 * screen and used by the Excel/PDF/image exports.
 *
 * Cell semantics per month: the amount PAID that month, coloured by how it
 * compares to what was charged — green = fully paid, yellow = partial,
 * red = unpaid, neutral = nothing charged.
 */
class DebtMatrix
{
    private const MONTH_NAMES = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    /**
     * @return array{title:string, periods:array, columns:array, rows:array}
     */
    public static function build(?int $buildingId = null, int $monthsBack = 3): array
    {
        // Build the list of Jalali periods (oldest → newest), each with a Gregorian range.
        $now = Jalalian::now();
        $jy = (int) $now->getYear();
        $jm = (int) $now->getMonth();
        $periods = [];
        for ($i = 0; $i < $monthsBack; $i++) {
            [$s, $e] = JDate::gregorianMonthRange($jy, $jm);
            array_unshift($periods, [
                'jy' => $jy, 'jm' => $jm,
                'label' => self::MONTH_NAMES[$jm - 1],
                'start' => $s, 'end' => $e,
            ]);
            if (--$jm < 1) {
                $jm = 12;
                $jy--;
            }
        }
        $windowStart = $periods[0]['start'];

        $units = Unit::query()
            ->where('is_active', true)
            ->when($buildingId, fn ($q) => $q->where('building_id', $buildingId))
            ->with(['activeResidents', 'building'])
            ->orderBy('building_id')->orderBy('floor')->orderBy('number')
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
            $months = [];
            foreach ($periods as $p) {
                $months[] = ['paid' => 0, 'charged' => 0];
            }

            foreach ($txs as $t) {
                $signed = $t->direction === 'debit' ? $t->amount : -$t->amount;
                $totalDebt += $signed;
                $date = $t->transaction_date;
                if ($date < $windowStart) {
                    $pastDebt += $signed;

                    continue;
                }
                foreach ($periods as $idx => $p) {
                    if ($date >= $p['start'] && $date < $p['end']) {
                        if ($t->direction === 'credit') {
                            $months[$idx]['paid'] += $t->amount;
                        } else {
                            $months[$idx]['charged'] += $t->amount;
                        }
                        break;
                    }
                }
            }

            // Monthly charge = charges in the most recent period (fallback: any period).
            $monthlyCharge = 0;
            for ($i = count($months) - 1; $i >= 0; $i--) {
                if ($months[$i]['charged'] > 0) {
                    $monthlyCharge = $months[$i]['charged'];
                    break;
                }
            }

            $monthCells = [];
            foreach ($months as $m) {
                $charged = $m['charged'];
                $paid = $m['paid'];
                if ($charged <= 0 && $paid <= 0) {
                    $state = 'neutral';
                } elseif ($paid >= $charged && $charged > 0) {
                    $state = 'paid';
                } elseif ($paid <= 0) {
                    $state = 'unpaid';
                } else {
                    $state = 'partial';
                }
                $monthCells[] = ['value' => $paid, 'state' => $state];
            }

            $rows[] = [
                'number' => $unit->number,
                'resident' => $resident?->name ?? '—',
                'owner' => $owner?->name ?? ($resident?->name ?? '—'),
                'count' => (int) $unit->activeResidents->sum('resident_count'),
                'monthly_charge' => $monthlyCharge,
                'past_debt' => max($pastDebt, 0),
                'months' => $monthCells,
                'total_debt' => max($totalDebt, 0),
                'notes' => $unit->notes ?? '',
            ];
        }

        $building = $buildingId ? Building::find($buildingId) : null;
        $title = 'گزارش بدهی واحدها'.($building ? ' — '.$building->name : '');

        return [
            'title' => $title,
            'periods' => $periods,
            'columns' => self::columns($periods),
            'rows' => $rows,
        ];
    }

    /**
     * All available columns with keys + labels (month columns expanded).
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
            $label = $p['label'] ?? (self::MONTH_NAMES[($p['jm'] ?? 1) - 1] ?? '');
            $cols[] = ['key' => 'month_'.$i, 'label' => $label, 'month' => $i];
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
