<?php

namespace App\Livewire\Buildings;

use App\Models\Building;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'ساختمان‌ها'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $address = '';
    public string $city = 'تهران';
    public string $floors = '1';
    public string $total_units = '';
    public string $manager_name = '';
    public string $manager_mobile = '';
    public string $description = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $building = Building::findOrFail($id);
        $this->editingId = $id;
        $this->name = $building->name;
        $this->address = $building->address;
        $this->city = $building->city;
        $this->floors = (string) $building->floors;
        $this->total_units = (string) $building->total_units;
        $this->manager_name = $building->manager_name ?? '';
        $this->manager_mobile = $building->manager_mobile ?? '';
        $this->description = $building->description ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:200',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'floors' => 'required|integer|min:1|max:100',
            'total_units' => 'required|integer|min:1|max:1000',
            'manager_name' => 'nullable|string|max:200',
            'manager_mobile' => 'nullable|string|max:20',
        ]);

        $data = [
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'floors' => (int) $this->floors,
            'total_units' => (int) $this->total_units,
            'manager_name' => $this->manager_name ?: null,
            'manager_mobile' => $this->manager_mobile ?: null,
            'description' => $this->description ?: null,
        ];

        if ($this->editingId) {
            Building::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'ساختمان با موفقیت بروزرسانی شد.');
        } else {
            Building::create($data);
            session()->flash('success', 'ساختمان با موفقیت ثبت شد.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $building = Building::withCount('units')->findOrFail($id);
        if ($building->units_count > 0) {
            session()->flash('error', 'این ساختمان دارای واحد است و قابل حذف نیست.');
            return;
        }
        $building->delete();
        session()->flash('success', 'ساختمان حذف شد.');
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->address = '';
        $this->city = 'تهران';
        $this->floors = '1';
        $this->total_units = '';
        $this->manager_name = '';
        $this->manager_mobile = '';
        $this->description = '';
        $this->resetValidation();
    }

    public function render()
    {
        $buildings = Building::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('address', 'like', "%{$this->search}%"))
            ->withCount('units')
            ->latest()
            ->paginate(12);

        return view('livewire.buildings.index', compact('buildings'));
    }
}
