<?php

namespace App\Livewire\Dashboard;

use App\Models\Building;
use App\Models\Expense;
use App\Models\LedgerTransaction;
use App\Models\Payment;
use App\Models\Unit;
use App\Support\JDate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $from = '';

    public string $to = '';

    public string $building_id = '';

    private const MONTH_NAMES = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    public function mount(): void
    {
        // Default period: from the start of the current Jalali year to today.
        $now = Jalalian::now();
        [$yearStart] = JDate::gregorianMonthRange((int) $now->getYear(), 1);
        $this->from = JDate::toJalali($yearStart);
        $this->to = JDate::today();
    }

    /** Quick period presets. */
    public function setPreset(string $preset): void
    {
        $now = Jalalian::now();
        $jy = (int) $now->getYear();
        $jm = (int) $now->getMonth();

        [$from, $to] = match ($preset) {
            'month' => [[$jy, $jm], [$jy, $jm]],
            'season' => (function () use ($jy, $jm) {
                $s = intdiv($jm - 1, 3) * 3 + 1;

                return [[$jy, $s], [$jy, $s + 2]];
            })(),
            'h1' => [[$jy, 1], [$jy, 6]],
            'h2' => [[$jy, 7], [$jy, 12]],
            default => [[$jy, 1], [$jy, 12]], // year
        };

        [$gs] = JDate::gregorianMonthRange($from[0], $from[1]);
        [, $ge] = JDate::gregorianMonthRange($to[0], $to[1]);
        $this->from = JDate::toJalali($gs);
        $this->to = JDate::toJalali($ge->copy()->subDay());
    }

    public function render()
    {
        $now = Jalalian::now();
        $bid = $this->building_id ?: null;

        $gFrom = JDate::toGregorian($this->from) ?: JDate::gregorianMonthRange((int) $now->getYear(), 1)[0]->toDateString();
        $gTo = JDate::toGregorian($this->to) ?: now()->toDateString();

        // ---- Fund cash (تراز) for the selected period -------------------------
        $inTypes = ['charge', 'unit_cost', 'deposit'];
        $paid = fn (array|string $types, ?string $from, ?string $to) => (int) Payment::query()
            ->when($bid, fn ($q) => $q->where('building_id', $bid))
            ->whereIn('type', (array) $types)
            ->when($from, fn ($q) => $q->whereDate('payment_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('payment_date', '<=', $to))
            ->sum('amount');

        $openingBase = (int) Building::where('is_active', true)
            ->when($bid, fn ($q) => $q->where('id', $bid))->sum('opening_balance');

        $before = fn (array|string $types) => (int) Payment::query()
            ->when($bid, fn ($q) => $q->where('building_id', $bid))
            ->whereIn('type', (array) $types)
            ->whereDate('payment_date', '<', $gFrom)->sum('amount');

        $opening = $openingBase + $before($inTypes) - $before('fund_cost');
        $receivedCharge = $paid('charge', $gFrom, $gTo);
        $receivedOther = $paid(['unit_cost', 'deposit'], $gFrom, $gTo);
        $received = $receivedCharge + $receivedOther;
        $fundOut = $paid('fund_cost', $gFrom, $gTo);
        $ending = $opening + $received - $fundOut;

        // Current cash balance (all-time snapshot).
        $balanceNow = $openingBase + $paid($inTypes, null, null) - $paid('fund_cost', null, null);

        // ---- Period aggregates ----------------------------------------------
        $chargesIssued = (int) LedgerTransaction::query()
            ->when($bid, fn ($q) => $q->where('building_id', $bid))
            ->where('direction', 'debit')->where('type', 'charge')
            ->whereDate('transaction_date', '>=', $gFrom)->whereDate('transaction_date', '<=', $gTo)
            ->sum('amount');
        $collectionRate = $chargesIssued > 0 ? min(100, (int) round($receivedCharge / $chargesIssued * 100)) : 0;

        $expensesRecorded = (int) Expense::query()
            ->when($bid, fn ($q) => $q->where('building_id', $bid))
            ->whereDate('expense_date', '>=', $gFrom)->whereDate('expense_date', '<=', $gTo)->sum('amount');

        // ---- Snapshots (current) --------------------------------------------
        $units = Unit::where('is_active', true)
            ->when($bid, fn ($q) => $q->where('building_id', $bid))
            ->with(['building', 'activeResidents'])->get();
        $unpaid = (int) $units->sum(fn ($u) => max($u->balance, 0));
        $debtorCount = $units->filter(fn ($u) => $u->balance > 0)->count();
        $totalUnits = $units->count();
        $occupied = $units->filter(fn ($u) => $u->activeResidents->sum('resident_count') > 0)->count();
        $residentsTotal = (int) $units->sum(fn ($u) => $u->activeResidents->sum('resident_count'));

        // ---- Last 6 Jalali months: income vs expense ------------------------
        $jy = (int) $now->getYear();
        $jm = (int) $now->getMonth();
        $months = [];
        for ($i = 0; $i < 6; $i++) {
            array_unshift($months, [$jy, $jm]);
            if (--$jm < 1) {
                $jm = 12;
                $jy--;
            }
        }
        $bars = [];
        foreach ($months as [$my, $mm]) {
            [$s, $e] = JDate::gregorianMonthRange($my, $mm);
            $eIncl = $e->copy()->subDay()->toDateString();
            $sStr = $s->toDateString();
            $bars[] = [
                'm' => mb_substr(self::MONTH_NAMES[$mm - 1], 0, 4),
                'income' => $paid(['charge', 'unit_cost'], $sStr, $eIncl),
                'expense' => (int) Expense::query()->when($bid, fn ($q) => $q->where('building_id', $bid))
                    ->whereDate('expense_date', '>=', $sStr)->whereDate('expense_date', '<=', $eIncl)->sum('amount'),
            ];
        }

        $debtors = $units->filter(fn ($u) => $u->balance > 0)->sortByDesc('balance')->take(4)
            ->map(fn ($u) => [
                'id' => $u->id, 'no' => $u->number, 'floor' => $u->floor,
                'owner' => $u->activeResidents->first()?->name ?? 'واحد '.$u->number,
                'amount' => $u->balance,
            ])->values();

        $activity = LedgerTransaction::with(['unit'])
            ->when($bid, fn ($q) => $q->where('building_id', $bid))
            ->whereIn('type', ['payment', 'expense', 'charge', 'cost'])
            ->orderByDesc('transaction_date')->orderByDesc('id')->take(6)->get()
            ->map(fn ($t) => [
                'credit' => $t->direction === 'credit',
                'title' => $t->description ?: $t->type,
                'date' => JDate::toJalali($t->transaction_date),
                'amount' => $t->amount,
            ]);

        return view('livewire.dashboard.index', [
            'buildings' => Building::where('is_active', true)->orderBy('name')->get(),
            'balance' => $balanceNow,
            'opening' => $opening,
            'received' => $received,
            'receivedCharge' => $receivedCharge,
            'receivedOther' => $receivedOther,
            'fundOut' => $fundOut,
            'ending' => $ending,
            'chargesIssued' => $chargesIssued,
            'collectionRate' => $collectionRate,
            'expensesRecorded' => $expensesRecorded,
            'unpaid' => $unpaid,
            'debtorCount' => $debtorCount,
            'totalUnits' => $totalUnits,
            'occupied' => $occupied,
            'residentsTotal' => $residentsTotal,
            'bars' => $bars,
            'debtors' => $debtors,
            'activity' => $activity,
            'todayLabel' => JDate::toPersianDigits($now->format('l j F Y')),
        ]);
    }
}
