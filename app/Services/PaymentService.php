<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private LedgerService $ledger) {}

    public function register(Unit $unit, array $data): Payment
    {
        return DB::transaction(function () use ($unit, $data) {
            $payment = Payment::create([
                'unit_id' => $unit->id,
                'building_id' => $unit->building_id,
                'created_by' => auth()->id(),
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'tracking_number' => $data['tracking_number'] ?? null,
                'receipt_path' => $data['receipt_path'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->ledger->recordPayment(
                $unit,
                $payment->amount,
                "پرداخت واحد {$unit->number}" . ($payment->tracking_number ? " - کد پیگیری: {$payment->tracking_number}" : ''),
                $payment->payment_date->format('Y-m-d'),
                $payment->tracking_number,
                $payment->id
            );

            return $payment;
        });
    }
}
