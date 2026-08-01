<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Expense;
use App\Models\ExpenseUnit;
use App\Models\LedgerTransaction;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(private LedgerService $ledger) {}

    /**
     * Record a cost (obligation only). Distributed costs create per-unit cost
     * debits; fund costs charge no one. Payments are recorded separately.
     */
    public function createAndDistribute(array $data, Building $building): Expense
    {
        return DB::transaction(function () use ($data, $building) {
            $expense = Expense::create($this->fields($data, $building));
            $this->distribute($expense, $data, $building);

            return $expense;
        });
    }

    /**
     * Edit a cost: remove its old per-unit allocation and rebuild it from the
     * new values. Linked payments (fund_cost / unit_cost) are left untouched.
     */
    public function updateAndRedistribute(Expense $expense, array $data, Building $building): Expense
    {
        return DB::transaction(function () use ($expense, $data, $building) {
            LedgerTransaction::where('reference_type', 'expense')
                ->where('reference_id', $expense->id)->where('type', 'cost')->delete();
            ExpenseUnit::where('expense_id', $expense->id)->delete();

            $expense->update($this->fields($data, $building));
            $this->distribute($expense, $data, $building);

            return $expense->refresh();
        });
    }

    private function fields(array $data, Building $building): array
    {
        return [
            'building_id' => $building->id,
            'expense_category_id' => $data['expense_category_id'] ?? null,
            'created_by' => auth()->id(),
            'title' => $data['title'],
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'description' => $data['description'] ?? null,
            'distribution' => $data['distribution'],
            'responsible' => $data['responsible'] ?? 'owner',
            'attachments' => $data['attachments'] ?? null,
        ];
    }

    private function distribute(Expense $expense, array $data, Building $building): void
    {
        if ($data['distribution'] === 'fund') {
            return;
        }

        $units = $data['distribution'] === 'all_units'
            ? $building->units()->where('is_active', true)->get()
            : Unit::whereIn('id', $data['unit_ids'] ?? [])->get();

        if ($units->isEmpty()) {
            return;
        }

        $perUnit = intdiv((int) $expense->amount, $units->count());
        $remainder = (int) $expense->amount - ($perUnit * $units->count());

        foreach ($units as $index => $unit) {
            $unitAmount = $index === 0 ? $perUnit + $remainder : $perUnit;

            ExpenseUnit::create([
                'expense_id' => $expense->id,
                'unit_id' => $unit->id,
                'amount' => $unitAmount,
            ]);

            $this->ledger->recordCost(
                $unit,
                $unitAmount,
                "سهم هزینه: {$expense->title}",
                $expense->expense_date->format('Y-m-d'),
                $expense->id
            );
        }
    }
}
