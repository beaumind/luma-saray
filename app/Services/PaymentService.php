<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private LedgerService $ledger) {}

    /**
     * Scenario 1 — a unit pays toward its monthly charge / debt to the fund.
     * Money enters the fund; the unit's debt is reduced.
     */
    public function register(Unit $unit, array $data): Payment
    {
        return DB::transaction(function () use ($unit, $data) {
            $payment = $this->createPayment($unit, 'charge', null, $data);

            $this->ledger->recordPayment(
                $unit,
                (int) $payment->amount,
                $data['notes'] ?? "پرداخت واحد {$unit->number}",
                $payment->payment_date->format('Y-m-d'),
                $payment->tracking_number,
                $payment->id
            );

            return $payment;
        });
    }

    /**
     * Scenario 2 — the fund pays a defined cost from its bank account.
     * Records the disbursement (with receipt) against the cost; no unit ledger.
     */
    public function registerFundCost(Expense $expense, array $data): Payment
    {
        return DB::transaction(fn () => $this->createPayment(null, 'fund_cost', $expense, $data, $expense->building_id));
    }

    /**
     * Scenario 3 — a unit pays its share of a defined cost.
     * Money enters the fund; the unit's debt (its cost share) is reduced.
     */
    public function registerUnitCost(Unit $unit, Expense $expense, array $data): Payment
    {
        return DB::transaction(function () use ($unit, $expense, $data) {
            $payment = $this->createPayment($unit, 'unit_cost', $expense, $data);

            $this->ledger->recordPayment(
                $unit,
                (int) $payment->amount,
                $data['notes'] ?? "پرداخت هزینه: {$expense->title}",
                $payment->payment_date->format('Y-m-d'),
                $payment->tracking_number,
                $payment->id
            );

            return $payment;
        });
    }

    /**
     * Scenario 4 — a unit pays a cost the fund / other units should bear,
     * becoming a creditor. Records a standing credit that does NOT reduce the
     * unit's debt until it is explicitly applied.
     */
    public function registerUnitCredit(Unit $unit, Expense $expense, array $data): Payment
    {
        return DB::transaction(function () use ($unit, $expense, $data) {
            $payment = $this->createPayment($unit, 'unit_credit', $expense, $data);

            $this->ledger->recordCredit(
                $unit,
                (int) $payment->amount,
                $data['notes'] ?? "بستانکاری بابت هزینه: {$expense->title}",
                $payment->payment_date->format('Y-m-d'),
                $expense->id
            );

            return $payment;
        });
    }

    /**
     * Manually apply a unit's standing credit against its debt.
     * Returns the amount actually applied.
     */
    public function applyCredit(Unit $unit, int $amount, string $date): int
    {
        $apply = min($amount, $unit->creditBalance, max($unit->balance, 0));
        if ($apply <= 0) {
            return 0;
        }

        DB::transaction(function () use ($unit, $apply, $date) {
            $this->ledger->recordCreditUsed($unit, $apply, 'اعمال بستانکاری بر بدهی', $date);
            $this->ledger->recordPayment($unit, $apply, 'تهاتر از محل بستانکاری', $date);
        });

        return $apply;
    }

    private function createPayment(?Unit $unit, string $type, ?Expense $expense, array $data, ?int $buildingId = null): Payment
    {
        return Payment::create([
            'unit_id' => $unit?->id,
            'building_id' => $unit?->building_id ?? $buildingId,
            'type' => $type,
            'expense_id' => $expense?->id,
            'created_by' => auth()->id(),
            'amount' => (int) $data['amount'],
            'payment_date' => $data['payment_date'],
            'tracking_number' => $data['tracking_number'] ?? null,
            'receipt_path' => $data['receipt_path'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
