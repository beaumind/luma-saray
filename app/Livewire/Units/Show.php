<?php

namespace App\Livewire\Units;

use App\Models\ChargeTemplate;
use App\Models\LedgerTransaction;
use App\Models\Unit;
use App\Models\UnitVacancy;
use App\Rules\JalaliDate;
use App\Services\PaymentService;
use App\Services\VacancyService;
use App\Support\Fmt;
use App\Support\JDate;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Layout('layouts.app')]
class Show extends Component
{
    private const MONTH_NAMES = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    public Unit $unit;

    // Payment modal
    public bool $showPaymentModal = false;

    public string $pay_amount = '';

    public string $pay_date = '';

    public string $pay_tracking = '';

    public string $pay_notes = '';

    public ?string $receipt_path = null;

    // Vacancy modal
    public bool $showVacancyModal = false;

    public int $vac_start_jy;

    public int $vac_start_jm = 1;

    public int $vac_end_jy;

    public int $vac_end_jm = 3;

    public string $vac_note = '';

    public function mount(Unit $unit): void
    {
        $this->unit = $unit->load(['building', 'activeResidents']);
        $this->pay_date = JDate::today();
        $this->vac_start_jy = $this->vac_end_jy = (int) Jalalian::now()->getYear();
    }

    public function openVacancy(): void
    {
        $this->resetValidation();
        $this->vac_start_jy = $this->vac_end_jy = (int) Jalalian::now()->getYear();
        $this->vac_start_jm = 1;
        $this->vac_end_jm = 3;
        $this->vac_note = '';
        $this->showVacancyModal = true;
    }

    public function saveVacancy(VacancyService $service): void
    {
        $this->validate([
            'vac_start_jy' => 'required|integer|min:1390|max:1450',
            'vac_start_jm' => 'required|integer|min:1|max:12',
            'vac_end_jy' => 'required|integer|min:1390|max:1450',
            'vac_end_jm' => 'required|integer|min:1|max:12',
            'vac_note' => 'nullable|string|max:200',
        ]);

        if ($this->vac_end_jy * 12 + $this->vac_end_jm < $this->vac_start_jy * 12 + $this->vac_start_jm) {
            $this->addError('vac_end_jm', 'ماه پایان باید بعد از ماه شروع باشد.');

            return;
        }

        $service->add(
            $this->unit,
            [$this->vac_start_jy, $this->vac_start_jm],
            [$this->vac_end_jy, $this->vac_end_jm],
            $this->vac_note ?: null,
        );

        $this->showVacancyModal = false;
        $this->unit->refresh();
        session()->flash('success', 'دورهٔ عدم سکونت ثبت شد و شارژ آن ماه‌ها به نرخ پایه اصلاح شد.');
    }

    public function removeVacancy(int $id, VacancyService $service): void
    {
        $vacancy = UnitVacancy::where('unit_id', $this->unit->id)->findOrFail($id);
        $service->remove($vacancy);
        $this->unit->refresh();
        session()->flash('success', 'دورهٔ عدم سکونت حذف شد و شارژ ماه‌ها به حالت اول بازگشت.');
    }

    public function openPayment(): void
    {
        $this->pay_amount = '';
        $this->pay_date = JDate::today();
        $this->pay_tracking = '';
        $this->pay_notes = '';
        $this->receipt_path = null;
        $this->showPaymentModal = true;
    }

    public function savePayment(PaymentService $service): void
    {
        $this->validate([
            'pay_amount' => 'required|integer|min:1',
            'pay_date' => ['required', new JalaliDate],
            'pay_tracking' => 'nullable|string|max:100',
            'receipt_path' => 'nullable|string|max:255',
        ]);

        $service->register($this->unit, [
            'amount' => Fmt::toRial($this->pay_amount),
            'payment_date' => JDate::toGregorian($this->pay_date),
            'tracking_number' => $this->pay_tracking ?: null,
            'notes' => $this->pay_notes ?: null,
            'receipt_path' => $this->receipt_path ?: null,
        ]);

        $this->showPaymentModal = false;
        $this->unit->refresh();
        session()->flash('success', 'پرداخت با موفقیت ثبت شد.');
    }

    public function applyCredit(PaymentService $service): void
    {
        $applied = $service->applyCredit($this->unit, $this->unit->creditBalance, JDate::toGregorian(JDate::today()));
        $this->unit->refresh();
        session()->flash('success', $applied > 0
            ? 'بستانکاری بر بدهی اعمال شد.'
            : 'بستانکاری قابل اعمالی وجود ندارد.');
    }

    public function render()
    {
        // Load full ledger ascending to build a running DEBT balance, then show newest first.
        $asc = LedgerTransaction::where('unit_id', $this->unit->id)
            ->orderBy('transaction_date')->orderBy('id')
            ->get();

        $debtTypes = ['charge', 'cost', 'expense'];
        $run = 0;
        $ledger = [];
        foreach ($asc as $t) {
            if ($t->direction === 'debit' && in_array($t->type, $debtTypes)) {
                $run += $t->amount;
            } elseif ($t->direction === 'credit' && $t->type === 'payment') {
                $run -= $t->amount;
            }
            $ledger[] = [
                'title' => $t->description ?: $this->typeLabel($t->type),
                'date' => JDate::toJalali($t->transaction_date),
                'credit' => $t->direction === 'credit',
                'type' => $t->type,
                'amount' => $t->amount,
                'run' => $run,
            ];
        }
        $ledger = array_reverse($ledger);

        $vacancies = $this->unit->vacancies()->orderByDesc('starts_on')->get()->map(function ($v) {
            // ends_on is the exclusive first-of-next-month; step back a day for the display month.
            $endInclusive = $v->ends_on->copy()->subDay();

            return [
                'id' => $v->id,
                'from' => self::monthLabel($v->starts_on),
                'to' => self::monthLabel($endInclusive),
                'note' => $v->note,
                'saved' => (int) collect($v->adjustments ?? [])->sum(fn ($a) => $a[1] - $this->baseGuess()),
            ];
        })->all();

        return view('livewire.units.show', [
            'ledger' => $ledger,
            'balance' => $this->unit->balance,
            'creditBalance' => $this->unit->creditBalance,
            'residents' => $this->unit->activeResidents()->get(),
            'vacancies' => $vacancies,
            'months' => self::MONTH_NAMES,
        ])->title('واحد '.$this->unit->number);
    }

    private function baseGuess(): int
    {
        return (int) (ChargeTemplate::where('building_id', $this->unit->building_id)
            ->where('is_active', true)->latest('id')->value('fixed_amount') ?? 0);
    }

    private static function monthLabel(Carbon $date): string
    {
        $j = Jalalian::fromCarbon($date);

        return self::MONTH_NAMES[$j->getMonth() - 1].' '.JDate::toPersianDigits((string) $j->getYear());
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'payment' => 'پرداخت',
            'cost', 'expense' => 'هزینه',
            'credit' => 'بستانکاری',
            'credit_used' => 'اعمال بستانکاری',
            default => 'شارژ',
        };
    }
}
