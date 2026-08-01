<?php

namespace App\Livewire\Payments;

use App\Models\Building;
use App\Models\Expense;
use App\Models\ExpenseUnit;
use App\Models\Payment;
use App\Models\Unit;
use App\Rules\JalaliDate;
use App\Services\PaymentService;
use App\Support\Fmt;
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

    public bool $showDetail = false;

    public ?int $detailId = null;

    /** charge | fund_cost | unit_cost | unit_credit */
    public string $type = 'charge';

    public string $unit_id = '';

    public string $expense_id = '';

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

    public function openDetail(int $id): void
    {
        $this->detailId = $id;
        $this->showDetail = true;
    }

    /** Prefill the amount from the selected cost. */
    public function updatedExpenseId($value): void
    {
        $this->prefillAmount();
    }

    public function updatedUnitId(): void
    {
        if ($this->type === 'unit_cost') {
            $this->prefillAmount();
        }
    }

    private function prefillAmount(): void
    {
        $expense = $this->expense_id ? Expense::find((int) $this->expense_id) : null;
        if (! $expense) {
            return;
        }

        if ($this->type === 'fund_cost') {
            $this->amount = (string) Fmt::display((int) $expense->amount);
        } elseif ($this->type === 'unit_cost' && $this->unit_id) {
            $share = ExpenseUnit::where('expense_id', $expense->id)
                ->where('unit_id', (int) $this->unit_id)->value('amount');
            $this->amount = (string) Fmt::display((int) ($share ?: $expense->amount));
        }
    }

    public function save(PaymentService $service): void
    {
        $rules = [
            'type' => 'required|in:charge,fund_cost,unit_cost,unit_credit',
            'amount' => 'required|integer|min:1',
            'payment_date' => ['required', new JalaliDate],
            'tracking_number' => 'nullable|string|max:100',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'unit_id' => 'nullable|exists:units,id',
            'expense_id' => 'nullable|exists:expenses,id',
        ];
        if (in_array($this->type, ['charge', 'unit_cost', 'unit_credit'])) {
            $rules['unit_id'] = 'required|exists:units,id';
        }
        if (in_array($this->type, ['fund_cost', 'unit_cost', 'unit_credit'])) {
            $rules['expense_id'] = 'required|exists:expenses,id';
        }
        $this->validate($rules);

        $data = [
            'amount' => Fmt::toRial($this->amount),
            'payment_date' => JDate::toGregorian($this->payment_date),
            'tracking_number' => $this->tracking_number ?: null,
            'notes' => $this->notes ?: null,
            'receipt_path' => $this->receipt ? $this->receipt->store('receipts', 'public') : null,
        ];

        $unit = $this->unit_id ? Unit::findOrFail((int) $this->unit_id) : null;
        $expense = $this->expense_id ? Expense::findOrFail((int) $this->expense_id) : null;

        match ($this->type) {
            'fund_cost' => $service->registerFundCost($expense, $data),
            'unit_cost' => $service->registerUnitCost($unit, $expense, $data),
            'unit_credit' => $service->registerUnitCredit($unit, $expense, $data),
            default => $service->register($unit, $data),
        };

        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', 'پرداخت با موفقیت ثبت شد.');
    }

    private function resetForm(): void
    {
        $this->type = 'charge';
        $this->unit_id = '';
        $this->expense_id = '';
        $this->amount = '';
        $this->payment_date = '';
        $this->tracking_number = '';
        $this->notes = '';
        $this->receipt = null;
        $this->resetValidation();
    }

    public function render()
    {
        $payments = Payment::with(['unit.building', 'expense', 'creator'])
            ->when($this->search, fn ($q) => $q->whereHas('unit', fn ($uq) => $uq->where('number', 'like', "%{$this->search}%")))
            ->when($this->building_id_filter, fn ($q) => $q->where('building_id', $this->building_id_filter))
            ->orderByDesc('payment_date')->orderByDesc('id')
            ->paginate(15);

        $buildings = Building::where('is_active', true)->get();
        $units = Unit::with('building')->where('is_active', true)
            ->orderBy('building_id')->orderByRaw('LENGTH(number)')->orderBy('number')->get();
        $expenses = Expense::with('building')->orderByDesc('expense_date')->orderByDesc('id')->limit(200)->get();
        $detailPayment = $this->detailId ? Payment::with(['unit.building', 'expense', 'creator'])->find($this->detailId) : null;

        return view('livewire.payments.index', compact('payments', 'buildings', 'units', 'expenses', 'detailPayment'));
    }
}
