<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use App\Models\Building;
use App\Models\Unit;
use App\Services\ExpenseService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostPaymentTest extends TestCase
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
        $this->unit = Unit::create(['building_id' => $this->building->id, 'number' => '1']);
    }

    public function test_single_unit_cost_charges_only_that_unit(): void
    {
        $other = Unit::create(['building_id' => $this->building->id, 'number' => '2']);

        app(ExpenseService::class)->createAndDistribute([
            'title' => 'broken door', 'amount' => 3_000_000, 'expense_date' => now()->format('Y-m-d'),
            'distribution' => 'single_unit', 'responsible' => 'owner', 'unit_ids' => [$this->unit->id],
        ], $this->building);

        $this->assertSame(3_000_000, $this->unit->fresh()->balance);
        $this->assertSame(0, $other->fresh()->balance);
    }

    public function test_fund_cost_does_not_charge_units_and_leaves_a_payment(): void
    {
        $expense = app(ExpenseService::class)->createAndDistribute([
            'title' => 'elevator', 'amount' => 5_000_000, 'expense_date' => now()->format('Y-m-d'),
            'distribution' => 'fund', 'responsible' => 'both',
        ], $this->building);

        $this->assertSame(0, $this->unit->fresh()->balance);

        $payment = app(PaymentService::class)->registerFundCost($expense, [
            'amount' => 5_000_000, 'payment_date' => now()->format('Y-m-d'),
        ]);

        $this->assertSame('fund_cost', $payment->type);
        $this->assertNull($payment->unit_id);
        $this->assertSame($expense->id, $payment->expense_id);
    }

    public function test_unit_credit_is_standalone_until_applied(): void
    {
        // Give the unit a 2M debt first.
        app(ExpenseService::class)->createAndDistribute([
            'title' => 'share', 'amount' => 2_000_000, 'expense_date' => now()->format('Y-m-d'),
            'distribution' => 'single_unit', 'responsible' => 'owner', 'unit_ids' => [$this->unit->id],
        ], $this->building);

        $fund = app(ExpenseService::class)->createAndDistribute([
            'title' => 'plumbing', 'amount' => 5_000_000, 'expense_date' => now()->format('Y-m-d'),
            'distribution' => 'fund', 'responsible' => 'both',
        ], $this->building);

        app(PaymentService::class)->registerUnitCredit($this->unit, $fund, [
            'amount' => 5_000_000, 'payment_date' => now()->format('Y-m-d'),
        ]);

        // Debt unchanged, credit standing.
        $this->assertSame(2_000_000, $this->unit->fresh()->balance);
        $this->assertSame(5_000_000, $this->unit->fresh()->creditBalance);

        // Apply: reduces debt to 0, credit keeps the remainder.
        $applied = app(PaymentService::class)->applyCredit($this->unit->fresh(), 5_000_000, now()->format('Y-m-d'));
        $this->assertSame(2_000_000, $applied);
        $this->assertSame(0, $this->unit->fresh()->balance);
        $this->assertSame(3_000_000, $this->unit->fresh()->creditBalance);
    }
}
