<?php

namespace App\Livewire\Units;

use App\Models\LedgerTransaction;
use App\Models\Unit;
use App\Services\PaymentService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Show extends Component
{
    use WithFileUploads;

    public Unit $unit;

    // Payment modal
    public bool $showPaymentModal = false;

    public string $pay_amount = '';

    public string $pay_date = '';

    public string $pay_tracking = '';

    public string $pay_notes = '';

    public $receipt;

    public function mount(Unit $unit): void
    {
        $this->unit = $unit->load(['building', 'activeResidents']);
        $this->pay_date = now()->format('Y-m-d');
    }

    public function openPayment(): void
    {
        $this->pay_amount = '';
        $this->pay_date = now()->format('Y-m-d');
        $this->pay_tracking = '';
        $this->pay_notes = '';
        $this->receipt = null;
        $this->showPaymentModal = true;
    }

    public function savePayment(PaymentService $service): void
    {
        $this->validate([
            'pay_amount' => 'required|integer|min:1',
            'pay_date' => 'required|date',
            'pay_tracking' => 'nullable|string|max:100',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $receiptPath = null;
        if ($this->receipt) {
            $receiptPath = $this->receipt->store('receipts', 'public');
        }

        $service->register($this->unit, [
            'amount' => (int) $this->pay_amount,
            'payment_date' => $this->pay_date,
            'tracking_number' => $this->pay_tracking ?: null,
            'notes' => $this->pay_notes ?: null,
            'receipt_path' => $receiptPath,
        ]);

        $this->showPaymentModal = false;
        $this->unit->refresh();
        session()->flash('success', 'پرداخت با موفقیت ثبت شد.');
    }

    public function render()
    {
        $transactions = LedgerTransaction::where('unit_id', $this->unit->id)
            ->with('creator')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(20);

        $balance = $this->unit->balance;

        return view('livewire.units.show', compact('transactions', 'balance'))
            ->title($this->unit->number.' - '.$this->unit->building->name);
    }
}
