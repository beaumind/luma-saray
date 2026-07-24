<?php

namespace App\Livewire\Expenses;

use App\Models\Building;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Unit;
use App\Rules\JalaliDate;
use App\Services\ExpenseService;
use App\Support\JDate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'هزینه‌ها'])]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    public string $building_id_filter = '';

    public string $search = '';

    public bool $showModal = false;

    public string $building_id = '';

    public string $expense_category_id = '';

    public string $title = '';

    public string $amount = '';

    public string $expense_date = '';

    public string $description = '';

    public string $distribution = 'all_units';

    public string $responsible = 'owner';

    public array $selected_unit_ids = [];

    public $attachments = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingBuildingIdFilter(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->expense_date = JDate::today();
        $this->showModal = true;
    }

    public function save(ExpenseService $service): void
    {
        $this->validate([
            'building_id' => 'required|exists:buildings,id',
            'title' => 'required|string|max:200',
            'amount' => 'required|integer|min:1',
            'expense_date' => ['required', new JalaliDate],
            'distribution' => 'required|in:all_units,selected_units',
            'responsible' => 'required|in:owner,tenant,both',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $attachmentPaths = [];
        foreach ($this->attachments as $file) {
            $attachmentPaths[] = $file->store('expenses', 'public');
        }

        $service->createAndDistribute([
            'expense_category_id' => $this->expense_category_id ?: null,
            'title' => $this->title,
            'amount' => (int) $this->amount,
            'expense_date' => JDate::toGregorian($this->expense_date),
            'description' => $this->description ?: null,
            'distribution' => $this->distribution,
            'responsible' => $this->responsible,
            'unit_ids' => $this->selected_unit_ids,
            'attachments' => $attachmentPaths ?: null,
        ], Building::find($this->building_id));

        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', 'هزینه ثبت و توزیع شد.');
    }

    private function resetForm(): void
    {
        $this->building_id = $this->building_id_filter;
        $this->expense_category_id = '';
        $this->title = '';
        $this->amount = '';
        $this->expense_date = '';
        $this->description = '';
        $this->distribution = 'all_units';
        $this->responsible = 'owner';
        $this->selected_unit_ids = [];
        $this->attachments = [];
        $this->resetValidation();
    }

    public function render()
    {
        $expenses = Expense::with(['building', 'category', 'creator'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->building_id_filter, fn ($q) => $q->where('building_id', $this->building_id_filter))
            ->orderByDesc('expense_date')
            ->paginate(15);

        $buildings = Building::where('is_active', true)->get();
        $categories = ExpenseCategory::where('is_active', true)->get();
        $units = $this->building_id
            ? Unit::where('building_id', $this->building_id)->where('is_active', true)->get()
            : collect();

        return view('livewire.expenses.index', compact('expenses', 'buildings', 'categories', 'units'));
    }
}
