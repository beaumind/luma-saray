<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Expense;
use App\Models\ExpenseUnit;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(private LedgerService $ledger) {}

    public function createAndDistribute(array $data, Building $building): Expense
    {
        return DB::transaction(function () use ($data, $building) {
            $expense = Expense::create([
                'building_id' => $building->id,
                'expense_category_id' => $data['expense_category_id'] ?? null,
                'created_by' => auth()->id(),
                'title' => $data['title'],
                'amount' => $data['amount'],
                'expense_date' => $data['expense_date'],
                'description' => $data['description'] ?? null,
                'distribution' => $data['distribution'],
                'responsible' => $data['responsible'],
                'attachments' => $data['attachments'] ?? null,
            ]);

            // "fund" expenses are paid from the building fund and are NOT
            // charged back to units — no per-unit ledger allocation.
            if ($data['distribution'] === 'fund') {
                return $expense;
            }

            $units = $data['distribution'] === 'all_units'
                ? $building->units()->where('is_active', true)->get()
                : Unit::whereIn('id', $data['unit_ids'] ?? [])->get();

            if ($units->count() === 0) {
                return $expense;
            }

            $perUnit = (int) floor($expense->amount / $units->count());
            $remainder = $expense->amount - ($perUnit * $units->count());

            foreach ($units as $index => $unit) {
                $unitAmount = $index === 0 ? $perUnit + $remainder : $perUnit;

                ExpenseUnit::create([
                    'expense_id' => $expense->id,
                    'unit_id' => $unit->id,
                    'amount' => $unitAmount,
                ]);

                $this->ledger->recordExpenseAllocation(
                    $unit,
                    $unitAmount,
                    "هزینه: {$expense->title}",
                    $expense->expense_date->format('Y-m-d'),
                    $expense->id
                );
            }

            return $expense;
        });
    }
}
