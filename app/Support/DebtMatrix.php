<?php

namespace App\Support;

use App\Models\Building;
use App\Models\Expense;
use App\Models\LedgerTransaction;
use App\Models\Payment;
use App\Models\Unit;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

/**
 * Builds the building debt matrix (units × time periods) shown on the Reports
 * screen and used by the Excel/PDF/image exports.
 *
 * Periods can be monthly, seasonal (Jalali فصل), or yearly. Each period cell
 * shows how much of that period's charge has been COVERED by payments
 * (allocated oldest-first across all obligations), coloured by how it compares
 * to what was charged — green = fully paid, yellow = partial, red = unpaid.
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
            ->get(['unit_id', 'direction', 'type', 'amount', 'transaction_date', 'reference_id'])
            ->groupBy('unit_id');

        // Titles/responsible party of the expenses behind the cost debits, for
        // the "special costs" notes (e.g. «… بدهی بابت بیمه ساختمان (مالک)»).
        $expenseIds = $txByUnit->flatten(1)
            ->filter(fn ($t) => $t->direction === 'debit' && in_array($t->type, ['cost', 'expense']) && $t->reference_id)
            ->pluck('reference_id')->unique()->values();
        $expenseMap = $expenseIds->isEmpty()
            ? collect()
            : Expense::whereIn('id', $expenseIds)->get(['id', 'title', 'responsible'])->keyBy('id');
        $respLabel = ['owner' => 'مالک واحد', 'tenant' => 'مستأجر واحد', 'both' => 'مالک/مستأجر'];

        // Payment credits reference a Payment row; its type tells us whether the
        // money settled a specific COST (unit_cost, for an expense) or a charge.
        // This lets cost-payments settle their cost and charge-payments settle
        // charges — instead of one blind pool that mis-attributes debt.
        $paymentIds = $txByUnit->flatten(1)
            ->filter(fn ($t) => $t->type === 'payment' && $t->direction === 'credit' && $t->reference_id)
            ->pluck('reference_id')->unique()->values();
        $paymentMap = $paymentIds->isEmpty()
            ? collect()
            : Payment::whereIn('id', $paymentIds)->get(['id', 'type', 'expense_id'])->keyBy('id');

        $rows = [];
        foreach ($units as $unit) {
            $txs = $txByUnit->get($unit->id, collect());
            $owner = $unit->activeResidents->firstWhere('type', 'owner');
            $resident = $unit->activeResidents->first();

            $latestCharge = ['date' => null, 'amount' => 0];
            $buckets = array_fill(0, count($periods), ['paid' => 0, 'charged' => 0]);

            // Obligations split into monthly charges and one-off costs; the
            // settlement pool split into money that paid a specific cost
            // (unit_cost payments, keyed by expense) vs. everything else (charge
            // payments + manual/credit settlements), which forms the charge pool.
            $charges = [];
            $costs = [];
            $chargePool = 0;
            $costPool = []; // expense_id => amount that paid that cost
            $creditStanding = 0; // standing creditor balance (fronted money not yet applied)
            foreach ($txs as $t) {
                if ($t->direction === 'debit' && $t->type === 'charge') {
                    $charges[] = ['date' => $t->transaction_date, 'amount' => (int) $t->amount, 'covered' => 0];
                    if ($latestCharge['date'] === null || $t->transaction_date >= $latestCharge['date']) {
                        $latestCharge = ['date' => $t->transaction_date, 'amount' => (int) $t->amount];
                    }
                } elseif ($t->direction === 'debit' && in_array($t->type, ['cost', 'expense'])) {
                    $costs[] = ['date' => $t->transaction_date, 'amount' => (int) $t->amount, 'ref' => $t->reference_id, 'covered' => 0];
                } elseif ($t->direction === 'credit' && $t->type === 'payment') {
                    $p = $t->reference_id ? $paymentMap->get($t->reference_id) : null;
                    if ($p && $p->type === 'unit_cost' && $p->expense_id) {
                        $costPool[$p->expense_id] = ($costPool[$p->expense_id] ?? 0) + (int) $t->amount;
                    } else {
                        $chargePool += (int) $t->amount;
                    }
                } elseif ($t->direction === 'credit' && $t->type === 'credit') {
                    $creditStanding += (int) $t->amount;
                } elseif ($t->direction === 'debit' && $t->type === 'credit_used') {
                    $creditStanding -= (int) $t->amount;
                }
            }

            usort($charges, fn ($a, $b) => $a['date'] <=> $b['date']);
            usort($costs, fn ($a, $b) => $a['date'] <=> $b['date']);

            // Phase 1 — each cost is settled by the payments made specifically for it.
            foreach ($costs as &$c) {
                $avail = $costPool[$c['ref']] ?? 0;
                $cov = min($avail, $c['amount']);
                $costPool[$c['ref']] = $avail - $cov;
                $c['covered'] = $cov;
            }
            unset($c);

            // Phase 2 — charge payments cover charges oldest-first.
            foreach ($charges as &$ch) {
                $cov = min($chargePool, $ch['amount']);
                $chargePool -= $cov;
                $ch['covered'] = $cov;
            }
            unset($ch);

            // Phase 3 — genuine leftovers (over-paid charges/costs) spill onto any
            // still-unpaid obligation, oldest-first, so totals still reconcile.
            $general = $chargePool + array_sum($costPool);
            if ($general > 0) {
                $remaining = [];
                foreach ($charges as $i => $ch) {
                    $remaining[] = ['kind' => 'charge', 'i' => $i, 'date' => $ch['date'], 'left' => $ch['amount'] - $ch['covered']];
                }
                foreach ($costs as $i => $c) {
                    $remaining[] = ['kind' => 'cost', 'i' => $i, 'date' => $c['date'], 'left' => $c['amount'] - $c['covered']];
                }
                usort($remaining, fn ($a, $b) => $a['date'] <=> $b['date']);
                foreach ($remaining as $r) {
                    if ($general <= 0 || $r['left'] <= 0) {
                        continue;
                    }
                    $cov = min($general, $r['left']);
                    $general -= $cov;
                    if ($r['kind'] === 'charge') {
                        $charges[$r['i']]['covered'] += $cov;
                    } else {
                        $costs[$r['i']]['covered'] += $cov;
                    }
                }
            }
            // Whatever's still unspent is an over-payment — the fund owes it back,
            // so it counts toward the unit's creditor balance.
            $overpaid = max(0, $general);

            $pastDebt = 0;
            $totalDebt = 0;
            $pastChargeMonths = []; // Jalali month labels of unpaid pre-window charges
            $pastCostNotes = [];    // unpaid pre-window cost shares
            foreach ($charges as $d) {
                $uncovered = $d['amount'] - $d['covered'];
                $totalDebt += $uncovered;
                if ($d['date'] < $windowStart) {
                    $pastDebt += $uncovered;
                    if ($uncovered > 0) {
                        $j = Jalalian::fromCarbon(Carbon::parse($d['date']));
                        $pastChargeMonths[] = self::MONTH_NAMES[$j->getMonth() - 1].' '.JDate::toPersianDigits((string) $j->getYear());
                    }

                    continue;
                }
                foreach ($periods as $idx => $p) {
                    if ($d['date'] >= $p['start'] && $d['date'] < $p['end']) {
                        $buckets[$idx]['charged'] += $d['amount'];
                        $buckets[$idx]['paid'] += $d['covered'];
                        break;
                    }
                }
            }

            // Non-charge costs — kept out of the month columns, shown separately,
            // and any still-owed share is listed in the description column.
            $special = ['charged' => 0, 'paid' => 0];
            $specialNotes = [];
            foreach ($costs as $d) {
                $uncovered = $d['amount'] - $d['covered'];
                $totalDebt += $uncovered;
                $exp = $expenseMap->get($d['ref']);
                $who = $respLabel[$exp?->responsible] ?? 'مالک واحد';
                $note = fn () => Fmt::money($uncovered).' '.Fmt::currency().' بدهی بابت '.($exp?->title ?? 'هزینهٔ ویژه').' ('.$who.')';
                if ($d['date'] < $windowStart) {
                    $pastDebt += $uncovered;
                    if ($uncovered > 0) {
                        $pastCostNotes[] = $note();
                    }

                    continue;
                }
                $special['charged'] += $d['amount'];
                $special['paid'] += $d['covered'];

                if ($uncovered > 0) {
                    $specialNotes[] = $note();
                }
            }

            $scCharged = $special['charged'];
            $scPaid = $special['paid'];
            $scState = $scCharged <= 0 ? 'neutral' : ($scPaid >= $scCharged ? 'paid' : ($scPaid <= 0 ? 'unpaid' : 'partial'));

            // Description: spell out what the debt is for — past unpaid charge
            // months, then past unpaid cost shares, then in-window cost shares.
            $noteParts = [];
            if ($pastChargeMonths) {
                $noteParts[] = 'بدهی شارژ: '.implode('، ', $pastChargeMonths);
            }
            $noteParts = array_merge($noteParts, $pastCostNotes, $specialNotes);
            if ($unit->notes) {
                $noteParts[] = $unit->notes;
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
                'special_costs' => ['value' => $scCharged, 'state' => $scState],
                'total_debt' => max($totalDebt, 0),
                'credit_balance' => max($creditStanding, 0) + $overpaid,
                'notes' => implode("\n", $noteParts),
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
        $cols[] = ['key' => 'special_costs', 'label' => 'هزینه‌های ویژه'];
        $cols[] = ['key' => 'total_debt', 'label' => 'مجموع بدهی'];
        $cols[] = ['key' => 'credit', 'label' => 'بستانکاری'];
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
