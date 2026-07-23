<?php

namespace App\Livewire\Charges;

use App\Models\Building;
use App\Models\ChargeTemplate;
use App\Services\ChargeService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'شارژها'])]
class Index extends Component
{
    use WithPagination;

    public string $building_id = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public bool $showApplyModal = false;

    public ?int $applyTemplateId = null;

    public string $apply_date = '';

    public string $apply_period = '';

    public string $tpl_building_id = '';

    public string $title = '';

    public string $type = 'fixed';

    public string $period = 'monthly';

    public string $fixed_amount = '';

    public string $per_resident_amount = '';

    public string $description = '';

    public function updatingBuildingId(): void
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
        $tpl = ChargeTemplate::findOrFail($id);
        $this->editingId = $id;
        $this->tpl_building_id = (string) $tpl->building_id;
        $this->title = $tpl->title;
        $this->type = $tpl->type;
        $this->period = $tpl->period;
        $this->fixed_amount = (string) $tpl->fixed_amount;
        $this->per_resident_amount = (string) $tpl->per_resident_amount;
        $this->description = $tpl->description ?? '';
        $this->showModal = true;
    }

    public function openApply(int $id): void
    {
        $this->applyTemplateId = $id;
        $this->apply_date = now()->format('Y-m-d');
        $this->apply_period = now()->format('Y/m');
        $this->showApplyModal = true;
    }

    public function applyCharge(ChargeService $service): void
    {
        $this->validate([
            'apply_date' => 'required|date',
            'apply_period' => 'required|string|max:20',
        ]);

        $template = ChargeTemplate::findOrFail($this->applyTemplateId);
        $count = $service->applyChargeToBuilding($template, $this->apply_period, $this->apply_date);

        $this->showApplyModal = false;
        session()->flash('success', "شارژ برای {$count} واحد اعمال شد.");
    }

    public function save(): void
    {
        $this->validate([
            'tpl_building_id' => 'required|exists:buildings,id',
            'title' => 'required|string|max:200',
            'type' => 'required|in:fixed,per_resident,combined',
            'period' => 'required|in:monthly,quarterly,yearly',
            'fixed_amount' => 'nullable|integer|min:0',
            'per_resident_amount' => 'nullable|integer|min:0',
        ]);

        $data = [
            'building_id' => (int) $this->tpl_building_id,
            'title' => $this->title,
            'type' => $this->type,
            'period' => $this->period,
            'fixed_amount' => (int) ($this->fixed_amount ?: 0),
            'per_resident_amount' => (int) ($this->per_resident_amount ?: 0),
            'description' => $this->description ?: null,
        ];

        if ($this->editingId) {
            ChargeTemplate::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'قالب شارژ بروزرسانی شد.');
        } else {
            ChargeTemplate::create($data);
            session()->flash('success', 'قالب شارژ ثبت شد.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        ChargeTemplate::findOrFail($id)->delete();
        session()->flash('success', 'قالب شارژ حذف شد.');
    }

    private function resetForm(): void
    {
        $this->tpl_building_id = $this->building_id;
        $this->title = '';
        $this->type = 'fixed';
        $this->period = 'monthly';
        $this->fixed_amount = '';
        $this->per_resident_amount = '';
        $this->description = '';
        $this->resetValidation();
    }

    public function render()
    {
        $templates = ChargeTemplate::with('building')
            ->when($this->building_id, fn ($q) => $q->where('building_id', $this->building_id))
            ->orderBy('building_id')
            ->orderBy('title')
            ->paginate(15);

        $buildings = Building::where('is_active', true)->get();

        return view('livewire.charges.index', compact('templates', 'buildings'));
    }
}
