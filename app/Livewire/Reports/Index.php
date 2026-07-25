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

    public string $periodType = 'seasonal';

    public int $count = 4;

    /** @var array<int,string> selected optional column keys */
    public array $cols = [];

    public bool $showColumns = false;

    public bool $colsInit = false;

    /** Sensible default number of periods per type. */
    private const DEFAULT_COUNT = ['monthly' => 6, 'seasonal' => 4, 'yearly' => 2];

    public function mount(): void
    {
        $this->syncDefaultCols();
    }

    public function updatedPeriodType(): void
    {
        $this->count = self::DEFAULT_COUNT[$this->periodType] ?? 4;
        $this->syncDefaultCols(force: true);
    }

    public function updatedCount(): void
    {
        $this->syncDefaultCols(force: true);
    }

    private function syncDefaultCols(bool $force = false): void
    {
        $available = DebtMatrix::optionalColumnKeys($this->periodStub());

        if (! $this->colsInit || $force) {
            $this->cols = $available;
            $this->colsInit = true;
        } else {
            $this->cols = array_values(array_intersect($this->cols, $available));
            foreach ($available as $k) {
                if (str_starts_with($k, 'month_') && ! in_array($k, $this->cols)) {
                    $this->cols[] = $k;
                }
            }
        }
    }

    /** Placeholder periods (only the count matters for column keys). */
    private function periodStub(): array
    {
        return array_fill(0, max(1, min(24, $this->count)), ['label' => '']);
    }

    public function render()
    {
        $buildingId = $this->building_id ? (int) $this->building_id : null;
        $matrix = DebtMatrix::build($buildingId, $this->periodType, $this->count);

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
            'type' => $this->periodType,
            'count' => $this->count,
            'cols' => implode(',', $this->cols),
        ];

        return view('livewire.reports.index', [
            'buildings' => Building::where('is_active', true)->orderBy('name')->get(),
            'matrix' => $matrix,
            'summary' => $summary,
            'exportParams' => $exportParams,
            'periodTypes' => DebtMatrix::PERIOD_TYPES,
            'countOptions' => $this->countOptions(),
            'yearLabel' => JDate::toPersianDigits((string) Jalalian::now()->getYear()),
        ]);
    }

    /** Count choices offered per period type. */
    private function countOptions(): array
    {
        return match ($this->periodType) {
            'monthly' => [3 => '۳ ماه', 6 => '۶ ماه', 12 => '۱۲ ماه'],
            'yearly' => [1 => '۱ سال', 2 => '۲ سال', 3 => '۳ سال'],
            default => [2 => '۲ فصل', 4 => '۴ فصل', 8 => '۸ فصل'],
        };
    }
}
