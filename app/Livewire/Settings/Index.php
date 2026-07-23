<?php

namespace App\Livewire\Settings;

use App\Models\ExpenseCategory;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'تنظیمات'])]
class Index extends Component
{
    public string $tab = 'categories';

    // Category form
    public bool $showCategoryModal = false;
    public ?int $editingCategoryId = null;
    public string $cat_name = '';
    public string $cat_color = '#6366f1';

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
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('livewire.settings.index', compact('categories'));
    }
}
