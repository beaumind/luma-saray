<?php

namespace App\Livewire\Units;

use App\Models\LedgerTransaction;
use App\Models\Unit;
use App\Rules\JalaliDate;
use App\Services\PaymentService;
use App\Support\JDate;
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
        $this->pay_date = JDate::today();
    }

    public function openPayment(): void
    {
        $this->pay_amount = '';
        $this->pay_date = JDate::today();
        $this->pay_tracking = '';
        $this->pay_notes = '';
        $this->receipt = null;
        $this->showPaymentModal = true;
    }

    public function savePayment(PaymentService $service): void
    {
        $this->validate([
            'pay_amount' => 'required|integer|min:1',
            'pay_date' => ['required', new JalaliDate],
            'pay_tracking' => 'nullable|string|max:100',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $receiptPath = null;
        if ($this->receipt) {
            $receiptPath = $this->receipt->store('receipts', 'public');
        }

        $service->register($this->unit, [
            'amount' => (int) $this->pay_amount,
            'payment_date' => JDate::toGregorian($this->pay_date),
            'tracking_number' => $this->pay_tracking ?: null,
            'notes' => $this->pay_notes ?: null,
            'receipt_path' => $receiptPath,
        ]);

        $this->showPaymentModal = false;
        $this->unit->refresh();
        session()->flash('success', 'پرداخت با موفقیت ثبت شد.');
    }

    public function applyCredit(PaymentService $service): void
    {
        $applied = $service->applyCredit($this->unit, $this->unit->creditBalance, JDate::toGregorian(JDate::today()));
        $this->unit->refresh();
        session()->flash('success', $applied > 0
            ? 'بستانکاری بر بدهی اعمال شد.'
            : 'بستانکاری قابل اعمالی وجود ندارد.');
    }

    public function render()
    {
        // Load full ledger ascending to build a running DEBT balance, then show newest first.
        $asc = LedgerTransaction::where('unit_id', $this->unit->id)
            ->orderBy('transaction_date')->orderBy('id')
            ->get();

        $debtTypes = ['charge', 'cost', 'expense'];
        $run = 0;
        $ledger = [];
        foreach ($asc as $t) {
            if ($t->direction === 'debit' && in_array($t->type, $debtTypes)) {
                $run += $t->amount;
            } elseif ($t->direction === 'credit' && $t->type === 'payment') {
                $run -= $t->amount;
            }
            $ledger[] = [
                'title' => $t->description ?: $this->typeLabel($t->type),
                'date' => JDate::toJalali($t->transaction_date),
                'credit' => $t->direction === 'credit',
                'type' => $t->type,
                'amount' => $t->amount,
                'run' => $run,
            ];
        }
        $ledger = array_reverse($ledger);

        return view('livewire.units.show', [
            'ledger' => $ledger,
            'balance' => $this->unit->balance,
            'creditBalance' => $this->unit->creditBalance,
            'residents' => $this->unit->activeResidents()->get(),
        ])->title('واحد '.$this->unit->number);
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'payment' => 'پرداخت',
            'cost', 'expense' => 'هزینه',
            'credit' => 'بستانکاری',
            'credit_used' => 'اعمال بستانکاری',
            default => 'شارژ',
        };
    }
}
