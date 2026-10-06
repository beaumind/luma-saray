<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use App\Models\Building;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Unit;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_refund_is_a_fund_outflow_and_reduces_credit_only(): void
    {
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000001', 'secret123');
        $this->actingAs($admin);
        $b = Building::create(['name' => 'B', 'address' => 'x', 'city' => 'y']);
        $unit = Unit::create(['building_id' => $b->id, 'number' => '8']);
        $exp = Expense::create([
            'building_id' => $b->id, 'created_by' => $admin->id, 'title' => 'battery', 'amount' => 10_000_000,
            'expense_date' => now()->format('Y-m-d'), 'distribution' => 'fund', 'responsible' => 'both',
        ]);

        // Unit fronts a 10M cost → becomes a creditor (no fund cash movement).
        app(PaymentService::class)->registerUnitCredit($unit, $exp, ['amount' => 10_000_000, 'payment_date' => now()->format('Y-m-d')]);
        $this->assertSame(10_000_000, $unit->fresh()->creditBalance);
        $debtBefore = $unit->fresh()->balance;

        // Fund repays 4M to the unit.
        app(PaymentService::class)->registerCreditRefund($unit, null, ['amount' => 4_000_000, 'payment_date' => now()->format('Y-m-d')]);

        $unit->refresh();
        $this->assertSame(6_000_000, $unit->creditBalance, 'credit reduced by the refund');
        $this->assertSame($debtBefore, $unit->balance, 'the unit debt is untouched by a refund');
        $this->assertDatabaseHas('payments', ['unit_id' => $unit->id, 'type' => 'credit_refund', 'amount' => 4_000_000]);

        // It counts as a fund outflow (same bucket as fund_cost).
        $fundOut = (int) Payment::whereIn('type', ['fund_cost', 'credit_refund'])->sum('amount');
        $this->assertSame(4_000_000, $fundOut);
    }
}
