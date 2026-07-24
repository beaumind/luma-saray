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

#[Layout('layouts.app', ['title' => 'داشبورد'])]
class Index extends Component
{
    public function render()
    {
        $buildings = Building::where('is_active', true)->withCount('units')->get();
        $totalUnits = Unit::where('is_active', true)->count();

        $totalDebits = LedgerTransaction::where('direction', 'debit')->sum('amount');
        $totalCredits = LedgerTransaction::where('direction', 'credit')->sum('amount');
        $totalBalance = $totalDebits - $totalCredits; // positive = owed to building

        // "This month" means the current Jalali month.
        [$monthStart, $monthEnd] = JDate::gregorianMonthRange(
            (int) Jalalian::now()->getYear(),
            (int) Jalalian::now()->getMonth()
        );

        $thisMonthPayments = Payment::whereBetween('payment_date', [$monthStart, $monthEnd->copy()->subDay()])
            ->sum('amount');

        $thisMonthExpenses = Expense::whereBetween('expense_date', [$monthStart, $monthEnd->copy()->subDay()])
            ->sum('amount');

        $recentTransactions = LedgerTransaction::with(['unit.building', 'creator'])
            ->latest()
            ->take(8)
            ->get();

        $debtorUnits = Unit::where('is_active', true)
            ->with(['building', 'activeResidents'])
            ->get()
            ->filter(fn ($u) => $u->balance > 0)
            ->sortByDesc('balance')
            ->take(5);

        return view('livewire.dashboard.index', compact(
            'buildings', 'totalUnits', 'totalBalance',
            'thisMonthPayments', 'thisMonthExpenses',
            'recentTransactions', 'debtorUnits'
        ));
    }
}
