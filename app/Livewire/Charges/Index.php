<?php

namespace App\Livewire\Charges;

use App\Models\Building;
use App\Models\ChargeTemplate;
use App\Rules\JalaliDate;
use App\Services\ChargeService;
use App\Support\Fmt;
use App\Support\JDate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

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

    public string $from_date = '';

    public string $to_date = '';

    public string $description = '';

    public function updatingBuildingId(): void
    {
        $this->resetPage();
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function openCreate(): void
    {
        $keepType = $this->type;
        $this->resetForm();
        $this->type = $keepType;
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
        $this->fixed_amount = (string) Fmt::display($tpl->fixed_amount);
        $this->per_resident_amount = (string) Fmt::display($tpl->per_resident_amount);
        $this->from_date = $tpl->starts_on ? JDate::toJalali($tpl->starts_on) : '';
        $this->to_date = $tpl->ends_on ? JDate::toJalali($tpl->ends_on) : '';
        $this->description = $tpl->description ?? '';
        $this->showModal = true;
    }

    public function openApply(int $id): void
    {
        $this->applyTemplateId = $id;
        $this->apply_date = JDate::today();
        $this->apply_period = JDate::toPersianDigits(Jalalian::now()->format('Y/m'));
        $this->showApplyModal = true;
    }

    public function applyCharge(ChargeService $service): void
    {
        $this->validate([
            'apply_date' => ['required', new JalaliDate],
            'apply_period' => 'required|string|max:20',
        ]);

        $template = ChargeTemplate::findOrFail($this->applyTemplateId);
        $count = $service->applyChargeToBuilding($template, $this->apply_period, JDate::toGregorian($this->apply_date));

        $this->showApplyModal = false;
        session()->flash('success', "شارژ برای {$count} واحد اعمال شد.");
    }

    public function save(): void
    {
        $this->validate([
            'tpl_building_id' => 'required|exists:buildings,id',
            'title' => 'required|string|max:200',
            'type' => 'required|in:fixed,per_resident,combined',
            'from_date' => ['required', new JalaliDate],
            'to_date' => ['required', new JalaliDate],
            'fixed_amount' => 'nullable|integer|min:0',
            'per_resident_amount' => 'nullable|integer|min:0',
        ]);

        // Month-align the effective window: first day of the "from" Jalali month
        // to the last day of the "to" Jalali month.
        [$fy, $fm] = $this->jaliMonth($this->from_date);
        [$ty, $tm] = $this->jaliMonth($this->to_date);
        if ($ty * 12 + $tm < $fy * 12 + $fm) {
            $this->addError('to_date', 'تاریخ پایان باید بعد از تاریخ شروع باشد.');

            return;
        }
        [$startsOn] = JDate::gregorianMonthRange($fy, $fm);
        [, $endExclusive] = JDate::gregorianMonthRange($ty, $tm);
        $startsOn = $startsOn->toDateString();
        $endsOn = $endExclusive->copy()->subDay()->toDateString();

        // No two active templates for a building may cover overlapping months.
        $overlap = ChargeTemplate::where('building_id', (int) $this->tpl_building_id)
            ->where('is_active', true)
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->whereNotNull('starts_on')->whereNotNull('ends_on')
            ->whereDate('starts_on', '<=', $endsOn)
            ->whereDate('ends_on', '>=', $startsOn)
            ->first();
        if ($overlap) {
            $this->addError('to_date', 'این بازه با قالب «'.$overlap->title.'» همپوشانی دارد. بازه‌ها نباید با هم تداخل داشته باشند.');

            return;
        }

        $months = ($ty * 12 + $tm) - ($fy * 12 + $fm) + 1;
        $period = $months <= 1 ? 'monthly' : ($months === 3 ? 'quarterly' : ($months === 12 ? 'yearly' : 'monthly'));

        $data = [
            'building_id' => (int) $this->tpl_building_id,
            'title' => $this->title,
            'type' => $this->type,
            'period' => $period,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'fixed_amount' => Fmt::toRial($this->fixed_amount ?: 0),
            'per_resident_amount' => Fmt::toRial($this->per_resident_amount ?: 0),
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
        [$this->from_date, $this->to_date] = JDate::thisMonthJalaliRange();
        $this->description = '';
        $this->resetValidation();
    }

    /** @return array{0:int,1:int} [jYear, jMonth] from a Jalali date string */
    private function jaliMonth(string $jalali): array
    {
        $parts = explode('/', JDate::toLatinDigits(trim($jalali)));

        return [(int) ($parts[0] ?? 0), (int) ($parts[1] ?? 0)];
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
