<?php

namespace App\Livewire\Dashboard;

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
    public function render()
    {
        $now = Jalalian::now();

        $totalPaid = (int) Payment::sum('amount');
        $totalExpense = (int) Expense::sum('amount');
        $balance = $totalPaid - $totalExpense;

        [$monthStart, $monthEnd] = JDate::gregorianMonthRange((int) $now->getYear(), (int) $now->getMonth());
        $monthEndInclusive = $monthEnd->copy()->subDay();

        $monthIncome = (int) Payment::whereBetween('payment_date', [$monthStart, $monthEndInclusive])->sum('amount');
        $monthCharges = (int) LedgerTransaction::where('direction', 'debit')->where('type', 'charge')
            ->whereBetween('transaction_date', [$monthStart, $monthEndInclusive])->sum('amount');

        // Unit balances (positive = owed to building).
        $units = Unit::where('is_active', true)->with(['building', 'activeResidents'])->get();
        $unpaid = (int) $units->sum(fn ($u) => max($u->balance, 0));
        $debtorCount = $units->filter(fn ($u) => $u->balance > 0)->count();

        $totalUnits = $units->count();
        $occupied = $units->filter(fn ($u) => $u->activeResidents->isNotEmpty())->count();
        $residentsTotal = (int) $units->sum(fn ($u) => $u->activeResidents->sum('resident_count'));
        $collectionRate = $monthCharges > 0 ? (int) round($monthIncome / $monthCharges * 100) : 0;

        // Last 6 Jalali months: income (payments) vs expense, and cash-balance trend.
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
        $monthNames = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

        $bars = [];
        $trend = [];
        foreach ($months as [$my, $mm]) {
            [$s, $e] = JDate::gregorianMonthRange($my, $mm);
            $eIncl = $e->copy()->subDay();
            $inc = (int) Payment::whereBetween('payment_date', [$s, $eIncl])->sum('amount');
            $exp = (int) Expense::whereBetween('expense_date', [$s, $eIncl])->sum('amount');
            $bars[] = ['m' => mb_substr($monthNames[$mm - 1], 0, 4), 'income' => $inc, 'expense' => $exp];

            $paidUpTo = (int) Payment::where('payment_date', '<', $e)->sum('amount');
            $expUpTo = (int) Expense::where('expense_date', '<', $e)->sum('amount');
            $trend[] = $paidUpTo - $expUpTo;
        }

        $debtors = $units->filter(fn ($u) => $u->balance > 0)
            ->sortByDesc('balance')
            ->take(3)
            ->map(fn ($u) => [
                'id' => $u->id,
                'no' => $u->number,
                'floor' => $u->floor,
                'owner' => $u->activeResidents->first()?->name ?? 'واحد '.$u->number,
                'amount' => $u->balance,
            ])->values();

        $activity = LedgerTransaction::with(['unit'])
            ->whereIn('type', ['payment', 'expense', 'charge'])
            ->orderByDesc('transaction_date')->orderByDesc('id')
            ->take(5)->get()
            ->map(fn ($t) => [
                'credit' => $t->direction === 'credit',
                'title' => $t->description ?: ($t->type === 'payment' ? 'پرداخت' : ($t->type === 'expense' ? 'هزینه' : 'شارژ')),
                'date' => JDate::toJalali($t->transaction_date),
                'amount' => $t->amount,
            ]);

        return view('livewire.dashboard.index', [
            'balance' => $balance,
            'unpaid' => $unpaid,
            'monthIncome' => $monthIncome,
            'totalUnits' => $totalUnits,
            'occupied' => $occupied,
            'residentsTotal' => $residentsTotal,
            'debtorCount' => $debtorCount,
            'collectionRate' => $collectionRate,
            'bars' => $bars,
            'trend' => $trend,
            'debtors' => $debtors,
            'activity' => $activity,
            'todayLabel' => JDate::toPersianDigits($now->format('l j F Y')),
        ]);
    }
}
