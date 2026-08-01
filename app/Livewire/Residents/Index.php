<?php

namespace App\Livewire\Residents;

use App\Models\Building;
use App\Models\Resident;
use App\Models\Unit;
use App\Rules\JalaliDate;
use App\Support\JDate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'ساکنین'])]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $building_id = '';

    #[Url]
    public string $type_filter = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $unit_id = '';

    public string $type = 'owner';

    public string $name = '';

    public string $mobile = '';

    public string $national_code = '';

    public string $resident_count = '1';

    public string $move_in_date = '';

    public string $move_out_date = '';

    public string $notes = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingBuildingId(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->move_in_date = JDate::today();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $resident = Resident::findOrFail($id);
        $this->editingId = $id;
        $this->unit_id = (string) $resident->unit_id;
        $this->type = $resident->type;
        $this->name = $resident->name;
        $this->mobile = $resident->mobile ?? '';
        $this->national_code = $resident->national_code ?? '';
        $this->resident_count = (string) $resident->resident_count;
        $this->move_in_date = JDate::toJalali($resident->move_in_date);
        $this->move_out_date = JDate::toJalali($resident->move_out_date);
        $this->notes = $resident->notes ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'unit_id' => 'required|exists:units,id',
            'type' => 'required|in:owner,tenant',
            'name' => 'required|string|max:200',
            'mobile' => 'nullable|string|max:20',
            'national_code' => 'nullable|string|max:10',
            'resident_count' => 'required|integer|min:0|max:20',
            'move_in_date' => ['nullable', new JalaliDate],
            'move_out_date' => ['nullable', new JalaliDate],
        ]);

        $moveIn = JDate::toGregorian($this->move_in_date);
        $moveOut = JDate::toGregorian($this->move_out_date);

        if ($moveIn && $moveOut && $moveOut <= $moveIn) {
            $this->addError('move_out_date', 'تاریخ خروج باید بعد از تاریخ ورود باشد.');

            return;
        }

        $data = [
            'unit_id' => (int) $this->unit_id,
            'type' => $this->type,
            'name' => $this->name,
            'mobile' => $this->mobile ?: null,
            'national_code' => $this->national_code ?: null,
            'resident_count' => (int) $this->resident_count,
            'move_in_date' => $moveIn,
            'move_out_date' => $moveOut,
            'notes' => $this->notes ?: null,
        ];

        if ($this->editingId) {
            Resident::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'ساکن بروزرسانی شد.');
        } else {
            Resident::create($data);
            session()->flash('success', 'ساکن ثبت شد.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function deactivate(int $id): void
    {
        Resident::findOrFail($id)->update(['is_active' => false, 'move_out_date' => now()->format('Y-m-d')]);
        session()->flash('success', 'ساکن غیرفعال شد.');
    }

    private function resetForm(): void
    {
        $this->unit_id = '';
        $this->type = 'owner';
        $this->name = '';
        $this->mobile = '';
        $this->national_code = '';
        $this->resident_count = '1';
        $this->move_in_date = '';
        $this->move_out_date = '';
        $this->notes = '';
        $this->resetValidation();
    }

    public function render()
    {
        $residents = Resident::with(['unit.building'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('mobile', 'like', "%{$this->search}%"))
            ->when($this->type_filter, fn ($q) => $q->where('type', $this->type_filter))
            ->when($this->building_id, fn ($q) => $q->whereHas('unit', fn ($uq) => $uq->where('building_id', $this->building_id)))
            ->where('is_active', true)
            // Group by unit (natural number order) so a unit's owner + tenant sit together.
            ->orderBy(Unit::select('number')->whereColumn('units.id', 'residents.unit_id'))
            ->orderByRaw("CASE type WHEN 'owner' THEN 0 ELSE 1 END")
            ->paginate(15);

        $buildings = Building::where('is_active', true)->get();
        $units = Unit::with('building')->where('is_active', true)->orderBy('building_id')->orderByRaw('LENGTH(number)')->orderBy('number')->get();

        return view('livewire.residents.index', compact('residents', 'buildings', 'units'));
    }
}
