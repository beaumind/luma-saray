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

    /**
     * Record a cost. It is an obligation only — payments (fund pays it, a unit
     * pays it, or a unit fronts it as a creditor) are recorded separately in
     * the Payments flow.
     *
     * distribution:
     *  - fund:        borne by the building fund; no unit is charged.
     *  - all_units:   split equally across the building's active units.
     *  - single_unit: charged entirely to one unit (data['unit_ids'] = [id]).
     */
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
                'responsible' => $data['responsible'] ?? 'owner',
                'attachments' => $data['attachments'] ?? null,
            ]);

            if ($data['distribution'] === 'fund') {
                return $expense;
            }

            $units = $data['distribution'] === 'all_units'
                ? $building->units()->where('is_active', true)->get()
                : Unit::whereIn('id', $data['unit_ids'] ?? [])->get();

            if ($units->isEmpty()) {
                return $expense;
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

            return $expense;
        });
    }
}
