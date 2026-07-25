<?php

namespace App\Livewire\Payments;

use App\Models\Building;
use App\Models\Payment;
use App\Models\Unit;
use App\Rules\JalaliDate;
use App\Services\PaymentService;
use App\Support\JDate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'پرداخت‌ها'])]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    public string $building_id_filter = '';

    public string $search = '';

    public bool $showModal = false;

    public string $unit_id = '';

    public string $amount = '';

    public string $payment_date = '';

    public string $tracking_number = '';

    public string $notes = '';

    public $receipt;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->payment_date = JDate::today();
        $this->showModal = true;
    }

    public function save(PaymentService $service): void
    {
        $this->validate([
            'unit_id' => 'required|exists:units,id',
            'amount' => 'required|integer|min:1',
            'payment_date' => ['required', new JalaliDate],
            'tracking_number' => 'nullable|string|max:100',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $receiptPath = null;
        if ($this->receipt) {
            $receiptPath = $this->receipt->store('receipts', 'public');
        }

        $unit = Unit::findOrFail((int) $this->unit_id);
        $service->register($unit, [
            'amount' => (int) $this->amount,
            'payment_date' => JDate::toGregorian($this->payment_date),
            'tracking_number' => $this->tracking_number ?: null,
            'notes' => $this->notes ?: null,
            'receipt_path' => $receiptPath,
        ]);

        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', 'پرداخت با موفقیت ثبت شد.');
    }

    private function resetForm(): void
    {
        $this->unit_id = '';
        $this->amount = '';
        $this->payment_date = '';
        $this->tracking_number = '';
        $this->notes = '';
        $this->receipt = null;
        $this->resetValidation();
    }

    public function render()
    {
        $payments = Payment::with(['unit.building', 'creator'])
            ->when($this->search, fn ($q) => $q->whereHas('unit', fn ($uq) => $uq->where('number', 'like', "%{$this->search}%")))
            ->when($this->building_id_filter, fn ($q) => $q->where('building_id', $this->building_id_filter))
            ->orderByDesc('payment_date')
            ->paginate(15);

        $buildings = Building::where('is_active', true)->get();
        $units = Unit::with('building')->where('is_active', true)->orderBy('building_id')->orderByRaw('LENGTH(number)')->orderBy('number')->get();

        return view('livewire.payments.index', compact('payments', 'buildings', 'units'));
    }
}
