<?php

namespace App\Livewire\Units;

use App\Models\Building;
use App\Models\Unit;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'واحدها'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $building_id = '';

    #[Url]
    public string $filter = 'all';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $unit_building_id = '';

    public string $number = '';

    public string $floor = '1';

    public string $area = '';

    public string $bedrooms = '2';

    public string $parking_count = '1';

    public string $storage_count = '0';

    public string $notes = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingBuildingId(): void
    {
        $this->resetPage();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
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
        $unit = Unit::findOrFail($id);
        $this->editingId = $id;
        $this->unit_building_id = (string) $unit->building_id;
        $this->number = $unit->number;
        $this->floor = (string) $unit->floor;
        $this->area = (string) ($unit->area ?? '');
        $this->bedrooms = (string) $unit->bedrooms;
        $this->parking_count = (string) $unit->parking_count;
        $this->storage_count = (string) $unit->storage_count;
        $this->notes = $unit->notes ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'unit_building_id' => 'required|exists:buildings,id',
            'number' => 'required|string|max:50',
            'floor' => 'required|integer|min:1',
            'area' => 'nullable|numeric|min:1',
            'bedrooms' => 'required|integer|min:0|max:20',
            'parking_count' => 'required|integer|min:0',
            'storage_count' => 'required|integer|min:0',
        ]);

        $data = [
            'building_id' => (int) $this->unit_building_id,
            'number' => $this->number,
            'floor' => (int) $this->floor,
            'area' => $this->area ?: null,
            'bedrooms' => (int) $this->bedrooms,
            'parking_count' => (int) $this->parking_count,
            'storage_count' => (int) $this->storage_count,
            'notes' => $this->notes ?: null,
        ];

        if ($this->editingId) {
            Unit::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'واحد بروزرسانی شد.');
        } else {
            Unit::create($data);
            session()->flash('success', 'واحد ثبت شد.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        Unit::findOrFail($id)->delete();
        session()->flash('success', 'واحد حذف شد.');
    }

    private function resetForm(): void
    {
        $this->unit_building_id = $this->building_id;
        $this->number = '';
        $this->floor = '1';
        $this->area = '';
        $this->bedrooms = '2';
        $this->parking_count = '1';
        $this->storage_count = '0';
        $this->notes = '';
        $this->resetValidation();
    }

    public function render()
    {
        $units = Unit::query()
            ->with(['building', 'activeResidents'])
            ->where('is_active', true)
            ->when($this->search, fn ($q) => $q->where('number', 'like', "%{$this->search}%"))
            ->when($this->building_id, fn ($q) => $q->where('building_id', $this->building_id))
            ->when($this->filter === 'occupied', fn ($q) => $q->whereHas('activeResidents', fn ($r) => $r->where('resident_count', '>', 0)))
            ->when($this->filter === 'empty', fn ($q) => $q->whereDoesntHave('activeResidents', fn ($r) => $r->where('resident_count', '>', 0)))
            ->orderBy('building_id')
            ->orderByRaw('LENGTH(number)')
            ->orderBy('number')
            ->paginate(30);

        $buildings = Building::where('is_active', true)->orderBy('name')->get();

        return view('livewire.units.index', compact('units', 'buildings'));
    }
}
