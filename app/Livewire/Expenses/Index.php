<?php

namespace App\Livewire\Expenses;

use App\Models\Building;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Unit;
use App\Rules\JalaliDate;
use App\Services\ExpenseService;
use App\Support\Fmt;
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

    /** fund | all_units | single_unit */
    public string $distribution = 'fund';

    public string $single_unit_id = '';

    public string $responsible = 'owner';

    public $image;

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
            'distribution' => 'required|in:fund,all_units,single_unit',
            'single_unit_id' => 'required_if:distribution,single_unit|nullable|exists:units,id',
            'responsible' => 'required|in:owner,tenant,both',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $attachments = null;
        if ($this->image) {
            $attachments = [$this->image->store('expenses', 'public')];
        }

        $service->createAndDistribute([
            'expense_category_id' => $this->expense_category_id ?: null,
            'title' => $this->title,
            'amount' => Fmt::toRial($this->amount),
            'expense_date' => JDate::toGregorian($this->expense_date),
            'description' => $this->description ?: null,
            'distribution' => $this->distribution,
            'responsible' => $this->responsible,
            'unit_ids' => $this->distribution === 'single_unit' ? [(int) $this->single_unit_id] : [],
            'attachments' => $attachments,
        ], Building::find($this->building_id));

        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', 'هزینه ثبت شد.');
    }

    private function resetForm(): void
    {
        $this->building_id = $this->building_id_filter;
        $this->expense_category_id = '';
        $this->title = '';
        $this->amount = '';
        $this->expense_date = '';
        $this->description = '';
        $this->distribution = 'fund';
        $this->single_unit_id = '';
        $this->responsible = 'owner';
        $this->image = null;
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
            ? Unit::where('building_id', $this->building_id)->where('is_active', true)
                ->orderByRaw('LENGTH(number)')->orderBy('number')->get()
            : collect();

        return view('livewire.expenses.index', compact('expenses', 'buildings', 'categories', 'units'));
    }
}
