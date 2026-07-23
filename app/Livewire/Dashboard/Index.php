<?php

namespace App\Livewire\Dashboard;

use App\Models\Building;
use App\Models\Expense;
use App\Models\LedgerTransaction;
use App\Models\Payment;
use App\Models\Unit;
use Livewire\Attributes\Layout;
use Livewire\Component;

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

        $thisMonthPayments = Payment::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        $thisMonthExpenses = Expense::whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
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
