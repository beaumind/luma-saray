<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use App\Models\Building;
use App\Models\ChargeTemplate;
use App\Models\LedgerTransaction;
use App\Models\Resident;
use App\Models\Unit;
use App\Services\ChargeService;
use App\Services\LedgerService;
use App\Services\VacancyService;
use App\Support\JDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VacancyTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000001', 'secret123');
        $this->actingAs($admin);
        $this->building = Building::create(['name' => 'B', 'address' => 'x', 'city' => 'y']);
        $this->unit = Unit::create(['building_id' => $this->building->id, 'number' => '6']);
        Resident::create(['unit_id' => $this->unit->id, 'type' => 'owner', 'name' => 'X', 'resident_count' => 2, 'is_active' => true]);
        // base 400k + 100k/person; 2 persons => 600k normal (all in RIAL ×10).
        ChargeTemplate::create([
            'building_id' => $this->building->id, 'title' => 't', 'type' => 'combined', 'period' => 'monthly',
            'fixed_amount' => 4_000_000, 'per_resident_amount' => 1_000_000, 'is_active' => true,
        ]);
    }

    public function test_marking_vacant_lowers_existing_charges_to_base(): void
    {
        // Full 600k charges for فروردین/اردیبهشت/خرداد 1405.
        foreach ([[1405, 1], [1405, 2], [1405, 3]] as [$jy, $jm]) {
            app(LedgerService::class)->recordCharge($this->unit, 6_000_000, "charge $jm", JDate::toGregorian("$jy/0$jm/01"));
        }
        $this->assertSame(18_000_000, $this->unit->fresh()->balance);

        $vacancy = app(VacancyService::class)->add($this->unit, [1405, 1], [1405, 3], 'trip');

        // Each 600k charge dropped to 400k base => balance 1,200k toman = 12,000,000 rial.
        $this->assertSame(12_000_000, $this->unit->fresh()->balance);
        $this->assertCount(3, $vacancy->adjustments);

        // Removing restores the originals.
        app(VacancyService::class)->remove($vacancy->fresh());
        $this->assertSame(18_000_000, $this->unit->fresh()->balance);
    }

    public function test_future_charge_generation_uses_base_for_vacant_month(): void
    {
        app(VacancyService::class)->add($this->unit, [1405, 4], [1405, 4], null);

        $tpl = ChargeTemplate::first();
        app(ChargeService::class)->applyChargeToUnit($tpl, $this->unit, 'تیر', JDate::toGregorian('1405/04/01'));

        $charge = LedgerTransaction::where('unit_id', $this->unit->id)->where('type', 'charge')->first();
        $this->assertSame(4_000_000, (int) $charge->amount, 'vacant month should be billed base only');
    }

    public function test_charge_below_base_is_left_untouched(): void
    {
        // خرداد already waived to 0 in the source data.
        app(LedgerService::class)->recordCharge($this->unit, 0, 'khordad', JDate::toGregorian('1405/03/01'));
        $vacancy = app(VacancyService::class)->add($this->unit, [1405, 3], [1405, 3], null);

        $charge = LedgerTransaction::where('unit_id', $this->unit->id)->where('type', 'charge')->first();
        $this->assertSame(0, (int) $charge->amount);
        $this->assertCount(0, $vacancy->adjustments);
    }
}
