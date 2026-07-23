<?php

namespace App\Services;

use App\Models\LedgerTransaction;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;

class LedgerService
{
    public function record(
        Unit $unit,
        string $type,
        int $amount,
        string $direction,
        string $description,
        string $date,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $trackingNumber = null,
    ): LedgerTransaction {
        return LedgerTransaction::create([
            'unit_id' => $unit->id,
            'building_id' => $unit->building_id,
            'type' => $type,
            'amount' => $amount,
            'direction' => $direction,
            'transaction_date' => $date,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => Auth::id(),
            'tracking_number' => $trackingNumber,
        ]);
    }

    public function getUnitBalance(Unit $unit): int
    {
        $debits = LedgerTransaction::where('unit_id', $unit->id)
            ->where('direction', 'debit')
            ->sum('amount');

        $credits = LedgerTransaction::where('unit_id', $unit->id)
            ->where('direction', 'credit')
            ->sum('amount');

        return (int) ($debits - $credits);
    }

    public function recordCharge(Unit $unit, int $amount, string $description, string $date): LedgerTransaction
    {
        return $this->record($unit, 'charge', $amount, 'debit', $description, $date);
    }

    public function recordPayment(Unit $unit, int $amount, string $description, string $date, ?string $trackingNumber = null, ?int $paymentId = null): LedgerTransaction
    {
        return $this->record($unit, 'payment', $amount, 'credit', $description, $date, 'payment', $paymentId, $trackingNumber);
    }

    public function recordExpenseAllocation(Unit $unit, int $amount, string $description, string $date, int $expenseId): LedgerTransaction
    {
        return $this->record($unit, 'expense', $amount, 'debit', $description, $date, 'expense', $expenseId);
    }
}
