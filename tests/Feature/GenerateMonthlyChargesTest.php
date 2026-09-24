<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use App\Models\Building;
use App\Models\ChargeTemplate;
use App\Models\LedgerTransaction;
use App\Models\Resident;
use App\Models\Unit;
use App\Support\JDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class GenerateMonthlyChargesTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_current_month_charge_once_and_is_idempotent(): void
    {
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000001', 'secret123');
        $this->actingAs($admin);
        $b = Building::create(['name' => 'B', 'address' => 'x', 'city' => 'y']);
        $unit = Unit::create(['building_id' => $b->id, 'number' => '1']);
        Resident::create(['unit_id' => $unit->id, 'type' => 'owner', 'name' => 'X', 'resident_count' => 2, 'is_active' => true]);
        ChargeTemplate::create([
            'building_id' => $b->id, 'title' => 'شارژ ماهانه', 'type' => 'combined', 'period' => 'monthly',
            'fixed_amount' => 4_000_000, 'per_resident_amount' => 1_000_000, 'is_active' => true,
        ]);

        $now = Jalalian::now();
        [$start, $end] = JDate::gregorianMonthRange((int) $now->getYear(), (int) $now->getMonth());
        $inMonth = fn () => LedgerTransaction::where('unit_id', $unit->id)->where('type', 'charge')
            ->where('transaction_date', '>=', $start)->where('transaction_date', '<', $end)->count();

        $this->assertSame(0, $inMonth());

        $this->artisan('charges:generate')->assertSuccessful();
        $this->assertSame(1, $inMonth());
        // 4,000,000 base + 1,000,000 * 2 persons = 6,000,000
        $this->assertSame(6_000_000, (int) LedgerTransaction::where('unit_id', $unit->id)->where('type', 'charge')
            ->where('transaction_date', '>=', $start)->where('transaction_date', '<', $end)->value('amount'));

        // Running again must not double-charge.
        $this->artisan('charges:generate')->assertSuccessful();
        $this->assertSame(1, $inMonth());
    }

    public function test_seasonal_template_charges_the_whole_season(): void
    {
        $admin = app(CreateOrganization::class)->handle('Org2', 'Admin2', '09120000002', 'secret123');
        $this->actingAs($admin);
        $b = Building::create(['name' => 'B', 'address' => 'x', 'city' => 'y']);
        $unit = Unit::create(['building_id' => $b->id, 'number' => '1']);
        Resident::create(['unit_id' => $unit->id, 'type' => 'owner', 'name' => 'X', 'resident_count' => 2, 'is_active' => true]);
        ChargeTemplate::create([
            'building_id' => $b->id, 'title' => 'شارژ', 'type' => 'combined', 'period' => 'quarterly',
            'fixed_amount' => 4_000_000, 'per_resident_amount' => 1_000_000, 'is_active' => true,
        ]);

        $this->artisan('charges:generate')->assertSuccessful();

        // Every month of the current Jalali season must be charged.
        $now = Jalalian::now();
        $seasonStart = intdiv((int) $now->getMonth() - 1, 3) * 3 + 1;
        foreach (range($seasonStart, $seasonStart + 2) as $m) {
            [$start, $end] = JDate::gregorianMonthRange((int) $now->getYear(), $m);
            $count = LedgerTransaction::where('unit_id', $unit->id)->where('type', 'charge')
                ->where('transaction_date', '>=', $start)->where('transaction_date', '<', $end)->count();
            $this->assertSame(1, $count, "month {$m} should have exactly one charge");
        }
        $this->assertSame(3, LedgerTransaction::where('unit_id', $unit->id)->where('type', 'charge')->count());
    }
}
