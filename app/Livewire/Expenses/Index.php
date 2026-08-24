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
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'هزینه‌ها'])]
class Index extends Component
{
    use WithPagination;

    public string $building_id_filter = '';

    public string $from = '';

    public string $to = '';

    public string $search = '';

    public bool $showModal = false;

    public bool $showDetail = false;

    public ?int $detailId = null;

    public ?int $editingId = null;

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

    public ?string $image_path = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingBuildingIdFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedTo(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->expense_date = JDate::today();
        $this->showModal = true;
    }

    public function openDetail(int $id): void
    {
        $this->detailId = $id;
        $this->showDetail = true;
    }

    public function editFromDetail(): void
    {
        $id = $this->detailId;
        $this->showDetail = false;
        if ($id) {
            $this->openEdit($id);
        }
    }

    public function openEdit(int $id): void
    {
        $this->resetForm();
        $e = Expense::with('expenseUnits')->findOrFail($id);
        $this->editingId = $e->id;
        $this->building_id = (string) $e->building_id;
        $this->expense_category_id = (string) ($e->expense_category_id ?? '');
        $this->title = $e->title;
        $this->amount = (string) Fmt::display((int) $e->amount);
        $this->expense_date = JDate::toJalali($e->expense_date);
        $this->description = $e->description ?? '';
        $this->distribution = in_array($e->distribution, ['fund', 'all_units', 'single_unit']) ? $e->distribution : 'fund';
        $this->single_unit_id = (string) ($e->expenseUnits->first()->unit_id ?? '');
        $this->responsible = $e->responsible ?? 'owner';
        $this->image_path = $e->attachments[0] ?? null;
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
            'image_path' => 'nullable|string|max:255',
        ]);

        $building = Building::find($this->building_id);

        $attachments = $this->image_path ? [$this->image_path] : null;

        $data = [
            'expense_category_id' => $this->expense_category_id ?: null,
            'title' => $this->title,
            'amount' => Fmt::toRial($this->amount),
            'expense_date' => JDate::toGregorian($this->expense_date),
            'description' => $this->description ?: null,
            'distribution' => $this->distribution,
            'responsible' => $this->responsible,
            'unit_ids' => $this->distribution === 'single_unit' ? [(int) $this->single_unit_id] : [],
            'attachments' => $attachments,
        ];

        if ($this->editingId) {
            $service->updateAndRedistribute(Expense::findOrFail($this->editingId), $data, $building);
        } else {
            $service->createAndDistribute($data, $building);
        }

        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', 'هزینه ذخیره شد.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->building_id = $this->building_id_filter;
        $this->expense_category_id = '';
        $this->title = '';
        $this->amount = '';
        $this->expense_date = '';
        $this->description = '';
        $this->distribution = 'fund';
        $this->single_unit_id = '';
        $this->responsible = 'owner';
        $this->image_path = null;
        $this->resetValidation();
    }

    public function render()
    {
        $gFrom = JDate::toGregorian($this->from);
        $gTo = JDate::toGregorian($this->to);

        $base = Expense::query()
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->building_id_filter, fn ($q) => $q->where('building_id', $this->building_id_filter))
            ->when($gFrom, fn ($q) => $q->whereDate('expense_date', '>=', $gFrom))
            ->when($gTo, fn ($q) => $q->whereDate('expense_date', '<=', $gTo))
            ->orderByDesc('expense_date')->orderByDesc('id');

        $expenses = (clone $base)->with(['building', 'category', 'creator'])->paginate(15);
        // Full filtered set (for the image export snapshot).
        $printRows = (clone $base)->with(['building', 'category'])->get();

        $buildings = Building::where('is_active', true)->get();
        $categories = ExpenseCategory::where('is_active', true)->get();
        $units = $this->building_id
            ? Unit::where('building_id', $this->building_id)->where('is_active', true)
                ->orderByRaw('LENGTH(number)')->orderBy('number')->get()
            : collect();

        $detailExpense = $this->detailId ? Expense::with(['building', 'category', 'creator', 'expenseUnits.unit'])->find($this->detailId) : null;

        $exportParams = array_filter([
            'building' => $this->building_id_filter ?: null,
            'from' => $this->from ?: null,
            'to' => $this->to ?: null,
        ]);

        return view('livewire.expenses.index', compact('expenses', 'printRows', 'buildings', 'categories', 'units', 'detailExpense', 'exportParams'));
    }
}
