<?php

namespace App\Services;

use App\Models\ChargeTemplate;
use App\Models\Unit;

class ChargeService
{
    public function __construct(private LedgerService $ledger) {}

    public function applyChargeToBuilding(ChargeTemplate $template, string $period, string $date): int
    {
        $units = $template->building->units()->where('is_active', true)->with('activeResidents')->get();
        $count = 0;

        foreach ($units as $unit) {
            $amount = $template->calculateForUnit($unit);
            if ($amount > 0) {
                $this->ledger->recordCharge(
                    $unit,
                    $amount,
                    "شارژ {$template->title} - دوره {$period}",
                    $date
                );
                $count++;
            }
        }

        return $count;
    }

    public function applyChargeToUnit(ChargeTemplate $template, Unit $unit, string $period, string $date): void
    {
        $amount = $template->calculateForUnit($unit);
        if ($amount > 0) {
            $this->ledger->recordCharge(
                $unit,
                $amount,
                "شارژ {$template->title} - دوره {$period}",
                $date
            );
        }
    }
}
