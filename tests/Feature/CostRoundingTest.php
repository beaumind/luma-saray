<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use App\Models\Building;
use App\Models\ExpenseUnit;
use App\Models\Unit;
use App\Services\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostRoundingTest extends TestCase
{
    use RefreshDatabase;

    public function test_round_share_up_down_and_off(): void
    {
        $b = new Building(['cost_rounding_mode' => 'up', 'cost_rounding_step' => 100000]);
        $this->assertSame(22_400_000, $b->roundShare(22_360_000));

        $b->cost_rounding_mode = 'down';
        $this->assertSame(22_300_000, $b->roundShare(22_360_000));

        $b->cost_rounding_mode = 'off';
        $this->assertSame(22_360_000, $b->roundShare(22_360_000));

        // A step of zero never rounds, whatever the mode says.
        $b->cost_rounding_mode = 'up';
        $b->cost_rounding_step = 0;
        $this->assertSame(22_360_000, $b->roundShare(22_360_000));
    }

    public function test_round_share_nearest_goes_up_or_down_by_amount(): void
    {
        $b = new Building(['cost_rounding_mode' => 'nearest', 'cost_rounding_step' => 100000]);

        // Past the midpoint of the step → rounds up.
        $this->assertSame(22_400_000, $b->roundShare(22_360_000));
        // Below the midpoint → rounds down.
        $this->assertSame(22_300_000, $b->roundShare(22_320_000));
    }

    public function test_distribute_rounds_each_unit_share_up(): void
    {
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000005', 'secret123');
        $this->actingAs($admin);
        $building = Building::create([
            'name' => 'B', 'address' => 'x', 'city' => 'y',
            'cost_rounding_mode' => 'up', 'cost_rounding_step' => 100000,
        ]);
        Unit::create(['building_id' => $building->id, 'number' => '1']);
        Unit::create(['building_id' => $building->id, 'number' => '2']);

        // 44,720,000 rial over 2 units = 22,360,000 each → rounds up to 22,400,000.
        app(ExpenseService::class)->createAndDistribute([
            'title' => 'بیمه', 'amount' => 44_720_000, 'expense_date' => '2026-07-15',
            'distribution' => 'all_units', 'responsible' => 'owner',
        ], $building);

        $this->assertEqualsCanonicalizing([22_400_000, 22_400_000], ExpenseUnit::pluck('amount')->all());
    }

    public function test_distribute_without_rounding_keeps_exact_split(): void
    {
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000006', 'secret123');
        $this->actingAs($admin);
        $building = Building::create(['name' => 'B', 'address' => 'x', 'city' => 'y']);
        Unit::create(['building_id' => $building->id, 'number' => '1']);
        Unit::create(['building_id' => $building->id, 'number' => '2']);

        // Odd total: first unit absorbs the 1-rial remainder so the sum reconciles.
        app(ExpenseService::class)->createAndDistribute([
            'title' => 'بیمه', 'amount' => 44_720_001, 'expense_date' => '2026-07-15',
            'distribution' => 'all_units', 'responsible' => 'owner',
        ], $building);

        $this->assertSame(44_720_001, ExpenseUnit::sum('amount'));
    }
}
