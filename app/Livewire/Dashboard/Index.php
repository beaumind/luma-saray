<?php

namespace App\Livewire\Dashboard;

use App\Models\Building;
use App\Models\Expense;
use App\Models\ExpenseCategory;
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
        $now = Jalalian::now();
        [$yearStart] = JDate::gregorianMonthRange((int) $now->getYear(), 1);
        $this->from = JDate::toJalali($yearStart);
        $this->to = JDate::today();
    }

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
            default => [[$jy, 1], [$jy, 12]],
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

        $money = fn (string $label, int $value, string $color = '#18181b') => compact('label', 'value', 'color');

        // ---- Fund cash (تراز) ------------------------------------------------
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
            ->whereIn('type', (array) $types)->whereDate('payment_date', '<', $gFrom)->sum('amount');

        $inTypes = ['charge', 'unit_cost', 'deposit'];
        $opening = $openingBase + $before($inTypes) - $before('fund_cost');

        // ---- Payments by type in the period ---------------------------------
        $payByType = Payment::query()
            ->when($bid, fn ($q) => $q->where('building_id', $bid))
            ->whereDate('payment_date', '>=', $gFrom)->whereDate('payment_date', '<=', $gTo)
            ->selectRaw('type, sum(amount) as s')->groupBy('type')->pluck('s', 'type');
        $pt = fn (string $t) => (int) ($payByType[$t] ?? 0);

        $received = $pt('charge') + $pt('unit_cost') + $pt('deposit');
        $fundOut = $pt('fund_cost');
        $ending = $opening + $received - $fundOut;
        $balanceNow = $openingBase + $paid($inTypes, null, null) - $paid('fund_cost', null, null);

        // ---- Charges (issued vs collected) ----------------------------------
        $chargesIssued = (int) LedgerTransaction::query()
            ->when($bid, fn ($q) => $q->where('building_id', $bid))
            ->where('direction', 'debit')->where('type', 'charge')
            ->whereDate('transaction_date', '>=', $gFrom)->whereDate('transaction_date', '<=', $gTo)->sum('amount');
        $collectionRate = $chargesIssued > 0 ? min(100, (int) round($pt('charge') / $chargesIssued * 100)) : 0;

        // ---- Costs breakdown ------------------------------------------------
        $expScope = fn () => Expense::query()->when($bid, fn ($q) => $q->where('building_id', $bid))
            ->whereDate('expense_date', '>=', $gFrom)->whereDate('expense_date', '<=', $gTo);
        $expensesTotal = (int) $expScope()->sum('amount');

        $byDist = $expScope()->selectRaw('distribution, sum(amount) as s')->groupBy('distribution')->pluck('s', 'distribution');
        $d = fn (string $k) => (int) ($byDist[$k] ?? 0);
        $costsFromFund = $d('fund');
        $costsUnpredicted = $d('all_units') + $d('single_unit') + $d('selected_units'); // borne by units
        $costsByOwners = (int) $expScope()->where('distribution', '!=', 'fund')->whereIn('responsible', ['owner', 'both'])->sum('amount');

        // By category (top categories by amount).
        $catRows = $expScope()->selectRaw('expense_category_id, sum(amount) as s')->groupBy('expense_category_id')
            ->orderByDesc('s')->get();
        $catNames = ExpenseCategory::whereIn('id', $catRows->pluck('expense_category_id')->filter())->pluck('name', 'id');
        $byCategory = $catRows->map(fn ($r) => [
            'name' => $r->expense_category_id ? ($catNames[$r->expense_category_id] ?? 'سایر') : 'بدون دسته',
            'amount' => (int) $r->s,
            'pct' => $expensesTotal > 0 ? (int) round($r->s / $expensesTotal * 100) : 0,
        ])->values();

        // ---- Receivables / creditors (snapshot) -----------------------------
        $units = Unit::where('is_active', true)->when($bid, fn ($q) => $q->where('building_id', $bid))
            ->with(['building', 'activeResidents'])->get();
        $unpaid = (int) $units->sum(fn ($u) => max($u->balance, 0));
        $debtorCount = $units->filter(fn ($u) => $u->balance > 0)->count();
        $creditTotal = (int) $units->sum(fn ($u) => $u->creditBalance + max(-$u->balance, 0));
        $creditorCount = $units->filter(fn ($u) => $u->creditBalance > 0 || $u->balance < 0)->count();
        $totalUnits = $units->count();
        $occupied = $units->filter(fn ($u) => $u->activeResidents->sum('resident_count') > 0)->count();
        $residentsTotal = (int) $units->sum(fn ($u) => $u->activeResidents->sum('resident_count'));

        // ---- 6-month income vs expense --------------------------------------
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
            $sStr = $s->toDateString();
            $eIncl = $e->copy()->subDay()->toDateString();
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
                'owner' => $u->activeResidents->first()?->name ?? 'واحد '.$u->number, 'amount' => $u->balance,
            ])->values();

        $activity = LedgerTransaction::with(['unit'])
            ->when($bid, fn ($q) => $q->where('building_id', $bid))
            ->whereIn('type', ['payment', 'expense', 'charge', 'cost'])
            ->orderByDesc('transaction_date')->orderByDesc('id')->take(6)->get()
            ->map(fn ($t) => [
                'credit' => $t->direction === 'credit', 'title' => $t->description ?: $t->type,
                'date' => JDate::toJalali($t->transaction_date), 'amount' => $t->amount,
            ]);

        return view('livewire.dashboard.index', [
            'buildings' => Building::where('is_active', true)->orderBy('name')->get(),
            'balance' => $balanceNow,
            'opening' => $opening, 'received' => $received, 'fundOut' => $fundOut, 'ending' => $ending,
            'expensesTotal' => $expensesTotal,
            // payments in/out breakdown
            'inflowRows' => [
                $money('شارژ', $pt('charge'), '#16a34a'),
                $money('هزینهٔ واحد', $pt('unit_cost'), '#16a34a'),
                $money('واریز/سایر', $pt('deposit'), '#16a34a'),
                $money('بستانکاری واحد', $pt('unit_credit'), '#5b5bd6'),
            ],
            'fundOutRow' => $money('پرداخت از صندوق', $fundOut, '#dc2626'),
            // cost breakdown
            'costsFromFund' => $costsFromFund,
            'costsUnpredicted' => $costsUnpredicted,
            'costsByOwners' => $costsByOwners,
            'byCategory' => $byCategory,
            // charges
            'chargesIssued' => $chargesIssued, 'chargeCollected' => $pt('charge'), 'collectionRate' => $collectionRate,
            // receivables
            'unpaid' => $unpaid, 'debtorCount' => $debtorCount, 'creditTotal' => $creditTotal, 'creditorCount' => $creditorCount,
            'totalUnits' => $totalUnits, 'occupied' => $occupied, 'residentsTotal' => $residentsTotal,
            'bars' => $bars, 'debtors' => $debtors, 'activity' => $activity,
            'todayLabel' => JDate::toPersianDigits($now->format('l j F Y')),
        ]);
    }
}
