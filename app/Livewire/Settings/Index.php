<?php

namespace App\Livewire\Settings;

use App\Models\Building;
use App\Models\ExpenseCategory;
use App\Support\Fmt;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'تنظیمات'])]
class Index extends Component
{
    public string $currency = 'toman';

    /** building_id => opening balance in the display currency */
    public array $opening = [];

    // Category form
    public bool $showCategoryModal = false;

    public ?int $editingCategoryId = null;

    public string $cat_name = '';

    public string $cat_color = '#6366f1';

    public function mount(): void
    {
        $this->currency = auth()->user()->organization->currency ?? 'toman';
        foreach (Building::all() as $b) {
            $this->opening[$b->id] = (string) Fmt::display((int) $b->opening_balance);
        }
    }

    public function setCurrency(string $currency): void
    {
        if (! in_array($currency, ['toman', 'rial'])) {
            return;
        }
        auth()->user()->organization->update(['currency' => $currency]);
        $this->currency = $currency;
        Fmt::$override = $currency;
        foreach (Building::all() as $b) {
            $this->opening[$b->id] = (string) Fmt::display((int) $b->opening_balance);
        }
        session()->flash('success', 'واحد پول تغییر کرد.');
    }

    public function saveOpening(int $buildingId): void
    {
        Fmt::$override = $this->currency;
        $building = Building::findOrFail($buildingId);
        $building->update(['opening_balance' => Fmt::toRial($this->opening[$buildingId] ?? 0)]);
        session()->flash('success', 'موجودی اولیهٔ صندوق ذخیره شد.');
    }

    public function openCategoryCreate(): void
    {
        $this->editingCategoryId = null;
        $this->cat_name = '';
        $this->cat_color = '#6366f1';
        $this->showCategoryModal = true;
    }

    public function openCategoryEdit(int $id): void
    {
        $cat = ExpenseCategory::findOrFail($id);
        $this->editingCategoryId = $id;
        $this->cat_name = $cat->name;
        $this->cat_color = $cat->color;
        $this->showCategoryModal = true;
    }

    public function saveCategory(): void
    {
        $this->validate([
            'cat_name' => 'required|string|max:100',
            'cat_color' => 'required|string|max:7',
        ]);

        $data = ['name' => $this->cat_name, 'color' => $this->cat_color];

        if ($this->editingCategoryId) {
            ExpenseCategory::findOrFail($this->editingCategoryId)->update($data);
        } else {
            ExpenseCategory::create($data);
        }

        $this->showCategoryModal = false;
        session()->flash('success', 'دسته‌بندی ذخیره شد.');
    }

    public function deleteCategory(int $id): void
    {
        ExpenseCategory::findOrFail($id)->delete();
        session()->flash('success', 'دسته‌بندی حذف شد.');
    }

    public function render()
    {
        return view('livewire.settings.index', [
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'buildings' => Building::orderBy('name')->get(),
        ]);
    }
}
