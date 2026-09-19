<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use App\Models\Building;
use App\Models\Unit;
use App\Services\ExpenseService;
use App\Services\LedgerService;
use App\Services\PaymentService;
use App\Support\DebtMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSpecialCostsTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrg(): array
    {
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000001', 'secret123');
        $this->actingAs($admin);
        $b = Building::create(['name' => 'B', 'address' => 'x', 'city' => 'y']);

        return [$admin, $b];
    }

    public function test_paid_charge_stays_green_when_an_older_cost_is_unpaid(): void
    {
        [$admin, $b] = $this->makeOrg();
        $unit = Unit::create(['building_id' => $b->id, 'number' => '1']);

        // A cost dated EARLIER in the month than the charge (would steal coverage
        // under an oldest-first waterfall), and a payment that exactly covers the charge.
        $exp = app(ExpenseService::class)->createAndDistribute([
            'title' => 'insurance', 'amount' => 2_000_000, 'expense_date' => now()->startOfMonth()->format('Y-m-d'),
            'distribution' => 'single_unit', 'responsible' => 'owner', 'unit_ids' => [$unit->id],
        ], $b);
        app(LedgerService::class)->recordCharge($unit, 8_000_000, 'charge', now()->startOfMonth()->addDays(14)->format('Y-m-d'));
        app(LedgerService::class)->recordPayment($unit, 8_000_000, 'pay', now()->format('Y-m-d'));

        $m = DebtMatrix::build($b->id, 'monthly', 1);
        $row = collect($m['rows'])->firstWhere('number', '1');

        // The month's charge is fully covered → paid (green), not partial.
        $this->assertSame('paid', $row['months'][0]['state']);
        $this->assertSame(8_000_000, $row['months'][0]['value']);
        // The insurance share is the unpaid special cost.
        $this->assertSame('unpaid', $row['special_costs']['state']);
        $this->assertSame(2_000_000, $row['special_costs']['value']);
        // Total debt = just the insurance.
        $this->assertSame(2_000_000, $row['total_debt']);
        // Description lists the special cost with the responsible party.
        $this->assertStringContainsString('insurance', $row['notes']);
        $this->assertStringContainsString('مالک', $row['notes']);
    }

    public function test_columns_include_special_and_credit(): void
    {
        [$admin, $b] = $this->makeOrg();
        Unit::create(['building_id' => $b->id, 'number' => '1']);
        $keys = collect(DebtMatrix::build($b->id, 'monthly', 1)['columns'])->pluck('key');
        $this->assertTrue($keys->contains('special_costs'));
        $this->assertTrue($keys->contains('credit'));
    }

    public function test_paid_cost_stays_paid_and_unpaid_newest_charge_shows_in_its_month(): void
    {
        [$admin, $b] = $this->makeOrg();
        $unit = Unit::create(['building_id' => $b->id, 'number' => '9']);

        $m2 = now()->subMonthsNoOverflow(2)->startOfMonth()->addDays(9)->format('Y-m-d');
        $m1 = now()->subMonthNoOverflow()->startOfMonth()->addDays(9)->format('Y-m-d');
        $m0 = now()->startOfMonth()->addDays(9)->format('Y-m-d');

        // Three monthly charges.
        app(LedgerService::class)->recordCharge($unit, 8_000_000, 'charge', $m2);
        app(LedgerService::class)->recordCharge($unit, 8_000_000, 'charge', $m1);
        app(LedgerService::class)->recordCharge($unit, 8_000_000, 'charge', $m0);

        // An OLD cost, paid via a unit_cost payment (so it must stay "paid").
        $old = app(ExpenseService::class)->createAndDistribute([
            'title' => 'oldcost', 'amount' => 5_000_000, 'expense_date' => $m2,
            'distribution' => 'single_unit', 'responsible' => 'owner', 'unit_ids' => [$unit->id],
        ], $b);
        app(PaymentService::class)->registerUnitCost($unit, $old, ['amount' => 5_000_000, 'payment_date' => $m2]);

        // Charge payments for the first two months only (newest charge unpaid).
        app(PaymentService::class)->register($unit, ['amount' => 8_000_000, 'payment_date' => $m2]);
        app(PaymentService::class)->register($unit, ['amount' => 8_000_000, 'payment_date' => $m1]);

        // A NEW unpaid cost this month.
        app(ExpenseService::class)->createAndDistribute([
            'title' => 'newcost', 'amount' => 3_000_000, 'expense_date' => $m0,
            'distribution' => 'single_unit', 'responsible' => 'owner', 'unit_ids' => [$unit->id],
        ], $b);

        $m = DebtMatrix::build($b->id, 'monthly', 3);
        $row = collect($m['rows'])->firstWhere('number', '9');

        $this->assertSame('paid', $row['months'][0]['state']);   // 2 months ago: paid
        $this->assertSame('paid', $row['months'][1]['state']);   // last month: paid
        $this->assertSame('unpaid', $row['months'][2]['state']); // this month: unpaid charge
        // The old cost stayed paid; the new cost is unpaid — special is partial.
        $this->assertSame('partial', $row['special_costs']['state']);
        // The unpaid charge stays in its month, NOT dumped into past debt.
        $this->assertSame(0, $row['past_debt']);
        $this->assertSame(11_000_000, $row['total_debt']); // 8,000,000 charge + 3,000,000 new cost
    }
}
