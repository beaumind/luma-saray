<?php

namespace App\Livewire\Reports;

use App\Models\Building;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Unit;
use App\Support\JDate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Layout('layouts.app', ['title' => 'گزارش‌ها'])]
class Index extends Component
{
    public string $building_id = '';

    public string $year = '';

    public string $month = '';

    public function mount(): void
    {
        $this->year = (string) Jalalian::now()->getYear();
        $this->month = str_pad((string) Jalalian::now()->getMonth(), 2, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        $buildings = Building::where('is_active', true)->get();

        $paymentsQuery = Payment::query()
            ->when($this->building_id, fn ($q) => $q->where('building_id', $this->building_id));

        $expensesQuery = Expense::query()
            ->when($this->building_id, fn ($q) => $q->where('building_id', $this->building_id));

        // Selected Jalali month / year, as Gregorian ranges for querying.
        [$monthStart, $monthEnd] = JDate::gregorianMonthRange((int) $this->year, (int) $this->month);
        [$yearStart, $yearEnd] = JDate::gregorianYearRange((int) $this->year);

        $monthlyPayments = (clone $paymentsQuery)
            ->whereBetween('payment_date', [$monthStart, $monthEnd->copy()->subDay()])
            ->sum('amount');

        $monthlyExpenses = (clone $expensesQuery)
            ->whereBetween('expense_date', [$monthStart, $monthEnd->copy()->subDay()])
            ->sum('amount');

        $yearlyPayments = (clone $paymentsQuery)
            ->whereBetween('payment_date', [$yearStart, $yearEnd->copy()->subDay()])
            ->sum('amount');

        $yearlyExpenses = (clone $expensesQuery)
            ->whereBetween('expense_date', [$yearStart, $yearEnd->copy()->subDay()])
            ->sum('amount');

        // Monthly chart data — last 12 Jalali months, oldest first.
        $jYear = (int) Jalalian::now()->getYear();
        $jMonth = (int) Jalalian::now()->getMonth();
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            array_unshift($months, [$jYear, $jMonth]);
            if (--$jMonth < 1) {
                $jMonth = 12;
                $jYear--;
            }
        }

        $monthlyData = collect($months)->map(function ($ym) use ($paymentsQuery, $expensesQuery) {
            [$jy, $jm] = $ym;
            [$start, $end] = JDate::gregorianMonthRange($jy, $jm);

            $payments = (clone $paymentsQuery)
                ->whereBetween('payment_date', [$start, $end->copy()->subDay()])
                ->sum('amount');
            $expenses = (clone $expensesQuery)
                ->whereBetween('expense_date', [$start, $end->copy()->subDay()])
                ->sum('amount');

            return [
                'label' => JDate::toPersianDigits(sprintf('%d/%02d', $jy, $jm)),
                'payments' => $payments,
                'expenses' => $expenses,
            ];
        });

        // Units with highest debt
        $debtorUnits = Unit::query()
            ->with(['building', 'activeResidents'])
            ->when($this->building_id, fn ($q) => $q->where('building_id', $this->building_id))
            ->where('is_active', true)
            ->get()
            ->map(fn ($u) => array_merge($u->toArray(), ['balance' => $u->balance]))
            ->filter(fn ($u) => $u['balance'] > 0)
            ->sortByDesc('balance')
            ->take(10)
            ->values();

        return view('livewire.reports.index', compact(
            'buildings', 'monthlyPayments', 'monthlyExpenses',
            'yearlyPayments', 'yearlyExpenses', 'monthlyData', 'debtorUnits'
        ));
    }
}
