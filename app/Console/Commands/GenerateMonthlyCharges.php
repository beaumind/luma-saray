<?php

namespace App\Console\Commands;

use App\Models\Building;
use App\Models\ChargeTemplate;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Services\LedgerService;
use App\Support\JDate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;

/**
 * Issues each building's monthly charge for a Jalali month, from its active
 * monthly charge template. Idempotent: a unit already charged for that month is
 * skipped, so it's safe to run daily (the scheduler does) — the charge appears
 * once, on the first run after a new Jalali month begins. Vacant units are
 * billed the base rate (calculateForUnit honours vacancy periods).
 */
class GenerateMonthlyCharges extends Command
{
    protected $signature = 'charges:generate {--building= : Only this building id} {--month= : Jalali YYYY/MM (default: current month)}';

    protected $description = 'Issue monthly charges for the current (or given) Jalali month from each building\'s charge template.';

    private const MONTH_NAMES = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    public function handle(LedgerService $ledger): int
    {
        [$jy, $jm] = $this->targetMonth();

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

            $template = ChargeTemplate::where('building_id', $building->id)
                ->where('is_active', true)
                ->latest('id')->first();

            if (! $template) {
                $this->warn("Building {$building->id} ({$building->name}): no active charge template — skipped.");

                continue;
            }

            // A monthly template charges the current month; a seasonal (quarterly)
            // one charges the whole current Jalali season up front; a yearly one
            // the whole year — so the debt reflects the configured billing period.
            $months = $this->monthsForPeriod($template->period, $jy, $jm);
            $units = $building->units()->where('is_active', true)->with('activeResidents')->get();

            $created = 0;
            foreach ($months as [$my, $mm]) {
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

            $this->info("Building {$building->id} ({$building->name}) [{$template->getPeriodLabel()}]: {$created} charge(s) issued.");
            $total += $created;
        }

        Auth::logout();
        $this->info("Done — {$total} charge(s) issued.");

        return self::SUCCESS;
    }

    /**
     * The Jalali months to charge for the given reference month, per period:
     * monthly → that month; quarterly → its whole season; yearly → its whole year.
     *
     * @return array<int,array{0:int,1:int}> list of [jYear, jMonth]
     */
    private function monthsForPeriod(string $period, int $jy, int $jm): array
    {
        if ($period === 'yearly') {
            return array_map(fn ($m) => [$jy, $m], range(1, 12));
        }
        if ($period === 'quarterly') {
            $seasonStart = intdiv($jm - 1, 3) * 3 + 1;

            return array_map(fn ($m) => [$jy, $m], range($seasonStart, $seasonStart + 2));
        }

        return [[$jy, $jm]];
    }

    /** @return array{0:int,1:int} [jYear, jMonth] */
    private function targetMonth(): array
    {
        if ($opt = $this->option('month')) {
            $opt = JDate::toLatinDigits(trim((string) $opt));
            if (preg_match('/^(\d{4})\/(\d{1,2})$/', $opt, $m)) {
                return [(int) $m[1], (int) $m[2]];
            }
            $this->warn("Invalid --month '{$opt}', using current month.");
        }

        $now = Jalalian::now();

        return [(int) $now->getYear(), (int) $now->getMonth()];
    }
}
