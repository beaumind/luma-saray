<?php

namespace App\Livewire\Reports;

use App\Models\Building;
use App\Models\Expense;
use App\Models\LedgerTransaction;
use App\Models\Payment;
use App\Models\Unit;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'گزارش‌ها'])]
class Index extends Component
{
    public string $building_id = '';
    public string $year = '';
    public string $month = '';

    public function mount(): void
    {
        $this->year = now()->format('Y');
        $this->month = now()->format('m');
    }

    public function render()
    {
        $buildings = Building::where('is_active', true)->get();

        $paymentsQuery = Payment::query()
            ->when($this->building_id, fn($q) => $q->where('building_id', $this->building_id));

        $expensesQuery = Expense::query()
            ->when($this->building_id, fn($q) => $q->where('building_id', $this->building_id));

        $monthlyPayments = (clone $paymentsQuery)
            ->whereYear('payment_date', $this->year)
            ->whereMonth('payment_date', $this->month)
            ->sum('amount');

        $monthlyExpenses = (clone $expensesQuery)
            ->whereYear('expense_date', $this->year)
            ->whereMonth('expense_date', $this->month)
            ->sum('amount');

        $yearlyPayments = (clone $paymentsQuery)
            ->whereYear('payment_date', $this->year)
            ->sum('amount');

        $yearlyExpenses = (clone $expensesQuery)
            ->whereYear('expense_date', $this->year)
            ->sum('amount');

        // Monthly chart data (last 12 months)
        $monthlyData = collect(range(11, 0))->map(function ($monthsAgo) use ($paymentsQuery, $expensesQuery) {
            $date = now()->subMonths($monthsAgo);
            $payments = (clone $paymentsQuery)
                ->whereYear('payment_date', $date->year)
                ->whereMonth('payment_date', $date->month)
                ->sum('amount');
            $expenses = (clone $expensesQuery)
                ->whereYear('expense_date', $date->year)
                ->whereMonth('expense_date', $date->month)
                ->sum('amount');
            return [
                'label' => \Morilog\Jalali\Jalalian::fromCarbon($date)->format('Y/m'),
                'payments' => $payments,
                'expenses' => $expenses,
            ];
        });

        // Units with highest debt
        $debtorUnits = Unit::query()
            ->with(['building', 'activeResidents'])
            ->when($this->building_id, fn($q) => $q->where('building_id', $this->building_id))
            ->where('is_active', true)
            ->get()
            ->map(fn($u) => array_merge($u->toArray(), ['balance' => $u->balance]))
            ->filter(fn($u) => $u['balance'] > 0)
            ->sortByDesc('balance')
            ->take(10)
            ->values();

        return view('livewire.reports.index', compact(
            'buildings', 'monthlyPayments', 'monthlyExpenses',
            'yearlyPayments', 'yearlyExpenses', 'monthlyData', 'debtorUnits'
        ));
    }
}
