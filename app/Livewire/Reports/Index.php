<?php

namespace App\Livewire\Reports;

use App\Models\Building;
use App\Models\Expense;
use App\Models\LedgerTransaction;
use App\Models\Payment;
use App\Models\Unit;
use App\Support\DebtMatrix;
use App\Support\JDate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $building_id = '';

    public int $monthsBack = 3;

    /** @var array<int,string> selected optional column keys */
    public array $cols = [];

    public bool $showColumns = false;

    public bool $colsInit = false;

    public function mount(): void
    {
        $this->syncDefaultCols();
    }

    public function updatedMonthsBack(): void
    {
        $this->syncDefaultCols(force: true);
    }

    private function syncDefaultCols(bool $force = false): void
    {
        $periods = $this->periodsStub();
        $available = DebtMatrix::optionalColumnKeys($periods);

        if (! $this->colsInit || $force) {
            $this->cols = $available;
            $this->colsInit = true;
        } else {
            // keep only still-valid keys after month count changes
            $this->cols = array_values(array_intersect($this->cols, $available));
            foreach ($available as $k) {
                if (str_starts_with($k, 'month_') && ! in_array($k, $this->cols)) {
                    $this->cols[] = $k;
                }
            }
        }
    }

    private function periodsStub(): array
    {
        $now = Jalalian::now();
        $jy = (int) $now->getYear();
        $jm = (int) $now->getMonth();
        $periods = [];
        for ($i = 0; $i < $this->monthsBack; $i++) {
            array_unshift($periods, ['jy' => $jy, 'jm' => $jm]);
            if (--$jm < 1) {
                $jm = 12;
                $jy--;
            }
        }

        return $periods;
    }

    public function render()
    {
        $buildingId = $this->building_id ? (int) $this->building_id : null;
        $matrix = DebtMatrix::build($buildingId, $this->monthsBack);

        // Yearly summary (current Jalali year) for the report card.
        [$yStart, $yEnd] = JDate::gregorianYearRange((int) Jalalian::now()->getYear());
        $yEndIncl = $yEnd->copy()->subDay();

        $charged = (int) LedgerTransaction::where('direction', 'debit')->where('type', 'charge')
            ->whereBetween('transaction_date', [$yStart, $yEndIncl])->sum('amount');
        $collected = (int) Payment::whereBetween('payment_date', [$yStart, $yEndIncl])->sum('amount');
        $expenses = (int) Expense::whereBetween('expense_date', [$yStart, $yEndIncl])->sum('amount');
        $outstanding = (int) Unit::where('is_active', true)->get()->sum(fn ($u) => max($u->balance, 0));
        $base = max(1, $charged);

        $summary = [
            ['label' => 'کل شارژ صادر شده', 'value' => $charged, 'pct' => 100, 'color' => '#5b5bd6'],
            ['label' => 'مبلغ وصول شده', 'value' => $collected, 'pct' => min(100, (int) round($collected / $base * 100)), 'color' => '#16a34a'],
            ['label' => 'مطالبات معوق', 'value' => $outstanding, 'pct' => min(100, (int) round($outstanding / $base * 100)), 'color' => '#dc2626'],
            ['label' => 'کل هزینه‌ها', 'value' => $expenses, 'pct' => min(100, (int) round($expenses / $base * 100)), 'color' => '#d97706'],
        ];

        $exportParams = [
            'building' => $this->building_id ?: null,
            'months' => $this->monthsBack,
            'cols' => implode(',', $this->cols),
        ];

        return view('livewire.reports.index', [
            'buildings' => Building::where('is_active', true)->orderBy('name')->get(),
            'matrix' => $matrix,
            'summary' => $summary,
            'exportParams' => $exportParams,
            'yearLabel' => JDate::toPersianDigits((string) Jalalian::now()->getYear()),
        ]);
    }
}
