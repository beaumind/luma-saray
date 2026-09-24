<?php

namespace App\Console\Commands;

use App\Models\Building;
use App\Models\ChargeTemplate;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Services\LedgerService;
use App\Support\JDate;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;

/**
 * Issues monthly charges from each building's active charge templates. A template
 * bills every Jalali month within its effective range (starts_on..ends_on), one
 * charge per unit per month. Idempotent: a unit already charged for a month is
 * skipped, so it's safe to run daily (the scheduler does) — a new template's
 * months appear on the next run, without ever double-charging. Vacant units are
 * billed the base rate (calculateForUnit honours vacancy periods).
 */
class GenerateMonthlyCharges extends Command
{
    protected $signature = 'charges:generate {--building= : Only this building id}';

    protected $description = "Issue charges for every month within each building's active charge-template date ranges.";

    private const MONTH_NAMES = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    public function handle(LedgerService $ledger): int
    {
        $buildings = Building::query()
            ->where('is_active', true)
            ->when($this->option('building'), fn ($q, $b) => $q->where('id', $b))
            ->get();

        $total = 0;
        foreach ($buildings as $building) {
            // Act as a user of this building's org so ledger rows get the right
            // organization_id / created_by (the trait fills them from auth).
            $orgUser = User::withoutGlobalScopes()
                ->where('organization_id', $building->organization_id)->orderBy('id')->first();
            if (! $orgUser) {
                $this->warn("Building {$building->id} ({$building->name}): no user in its organization — skipped.");

                continue;
            }
            Auth::login($orgUser);

            $templates = ChargeTemplate::where('building_id', $building->id)
                ->where('is_active', true)
                ->whereNotNull('starts_on')->whereNotNull('ends_on')
                ->get();

            if ($templates->isEmpty()) {
                continue;
            }

            $units = $building->units()->where('is_active', true)->with('activeResidents')->get();
            $created = 0;

            foreach ($templates as $template) {
                foreach ($this->monthsInRange($template->starts_on, $template->ends_on) as [$my, $mm]) {
                    [$start, $end] = JDate::gregorianMonthRange($my, $mm);
                    $chargeDate = $start->format('Y-m-d');
                    $label = self::MONTH_NAMES[$mm - 1].' '.$my;

                    foreach ($units as $unit) {
                        $already = LedgerTransaction::where('unit_id', $unit->id)
                            ->where('type', 'charge')
                            ->where('transaction_date', '>=', $start)
                            ->where('transaction_date', '<', $end)
                            ->exists();
                        if ($already) {
                            continue;
                        }

                        $amount = $template->calculateForUnit($unit, $chargeDate);
                        if ($amount > 0) {
                            $ledger->recordCharge($unit, $amount, "شارژ ماهانه {$label}", $chargeDate);
                            $created++;
                        }
                    }
                }
            }

            $this->info("Building {$building->id} ({$building->name}): {$created} charge(s) issued.");
            $total += $created;
        }

        Auth::logout();
        $this->info("Done — {$total} charge(s) issued.");

        return self::SUCCESS;
    }

    /**
     * All Jalali [year, month] pairs from the start month to the end month, inclusive.
     *
     * @return array<int,array{0:int,1:int}>
     */
    private function monthsInRange(Carbon $startsOn, Carbon $endsOn): array
    {
        $s = Jalalian::fromCarbon($startsOn);
        $e = Jalalian::fromCarbon($endsOn);
        $from = (int) $s->getYear() * 12 + ((int) $s->getMonth() - 1);
        $to = (int) $e->getYear() * 12 + ((int) $e->getMonth() - 1);

        $out = [];
        for ($i = $from; $i <= $to; $i++) {
            $out[] = [intdiv($i, 12), $i % 12 + 1];
        }

        return $out;
    }
}
