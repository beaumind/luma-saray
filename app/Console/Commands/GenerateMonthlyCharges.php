<?php

namespace App\Console\Commands;

use App\Models\Building;
use App\Models\ChargeTemplate;
use App\Models\LedgerTransaction;
use App\Services\LedgerService;
use App\Support\JDate;
use Illuminate\Console\Command;
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
        [$start, $end] = JDate::gregorianMonthRange($jy, $jm);
        $chargeDate = $start->format('Y-m-d');
        $label = self::MONTH_NAMES[$jm - 1].' '.$jy;

        $buildings = Building::query()
            ->where('is_active', true)
            ->when($this->option('building'), fn ($q, $b) => $q->where('id', $b))
            ->get();

        $total = 0;
        foreach ($buildings as $building) {
            $template = ChargeTemplate::where('building_id', $building->id)
                ->where('is_active', true)->where('period', 'monthly')
                ->latest('id')->first();

            if (! $template) {
                $this->warn("Building {$building->id} ({$building->name}): no active monthly charge template — skipped.");

                continue;
            }

            $created = 0;
            $units = $building->units()->where('is_active', true)->with('activeResidents')->get();
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

            $this->info("Building {$building->id} ({$building->name}): {$created} charge(s) for {$label}.");
            $total += $created;
        }

        $this->info("Done — {$total} charge(s) issued for {$label}.");

        return self::SUCCESS;
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
