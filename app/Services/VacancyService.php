<?php

namespace App\Services;

use App\Models\ChargeTemplate;
use App\Models\LedgerTransaction;
use App\Models\Unit;
use App\Models\UnitVacancy;
use App\Support\JDate;
use Illuminate\Support\Facades\DB;

/**
 * Manages "unit is empty" periods. Marking a vacancy lowers that unit's charges
 * for the covered Jalali months to the base rate (the template's fixed part),
 * both going forward (charge generation is vacancy-aware) and retroactively for
 * charges already issued. Retro changes are snapshotted so they can be undone.
 */
class VacancyService
{
    /**
     * @param  array{0:int,1:int}  $start  [jYear, jMonth]
     * @param  array{0:int,1:int}  $end  [jYear, jMonth] inclusive
     */
    public function add(Unit $unit, array $start, array $end, ?string $note = null): UnitVacancy
    {
        [$startsOn] = JDate::gregorianMonthRange($start[0], $start[1]);
        [, $endsOn] = JDate::gregorianMonthRange($end[0], $end[1]); // exclusive upper bound
        $startsOn = $startsOn->toDateString();
        $endsOn = $endsOn->toDateString();

        return DB::transaction(function () use ($unit, $startsOn, $endsOn, $note) {
            $base = $this->baseRial($unit);
            $adjustments = [];

            if ($base !== null) {
                $charges = LedgerTransaction::where('unit_id', $unit->id)
                    ->where('type', 'charge')->where('direction', 'debit')
                    ->where('transaction_date', '>=', $startsOn)
                    ->where('transaction_date', '<', $endsOn)
                    ->get();

                foreach ($charges as $charge) {
                    // Only ever lower a charge to base — never raise one.
                    if ((int) $charge->amount > $base) {
                        $adjustments[] = [$charge->id, (int) $charge->amount];
                        $charge->update(['amount' => $base]);
                    }
                }
            }

            return UnitVacancy::create([
                'organization_id' => $unit->organization_id,
                'unit_id' => $unit->id,
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'adjustments' => $adjustments,
                'note' => $note,
            ]);
        });
    }

    public function remove(UnitVacancy $vacancy): void
    {
        DB::transaction(function () use ($vacancy) {
            foreach ($vacancy->adjustments ?? [] as [$ledgerId, $originalAmount]) {
                LedgerTransaction::where('id', $ledgerId)->update(['amount' => (int) $originalAmount]);
            }
            $vacancy->delete();
        });
    }

    /** Base charge (RIAL) for the unit's building, from its active template. */
    private function baseRial(Unit $unit): ?int
    {
        $template = ChargeTemplate::where('building_id', $unit->building_id)
            ->where('is_active', true)->latest('id')->first();

        return $template?->baseAmount();
    }
}
