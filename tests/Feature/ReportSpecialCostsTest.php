<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use App\Models\Building;
use App\Models\Unit;
use App\Services\ExpenseService;
use App\Services\LedgerService;
use App\Services\PaymentService;
use App\Support\DebtMatrix;
use App\Support\JDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Morilog\Jalali\Jalalian;
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

    /** A Gregorian Y-m-d on the given day of the Jalali month $monthsAgo before now. */
    private function jDate(int $monthsAgo, int $day = 10): string
    {
        $j = $monthsAgo > 0 ? Jalalian::now()->subMonths($monthsAgo) : Jalalian::now();
        [$start] = JDate::gregorianMonthRange((int) $j->getYear(), (int) $j->getMonth());

        return $start->copy()->addDays($day - 1)->format('Y-m-d');
    }

    public function test_paid_charge_stays_green_when_an_older_cost_is_unpaid(): void
    {
        [$admin, $b] = $this->makeOrg();
        $unit = Unit::create(['building_id' => $b->id, 'number' => '1']);

        // A cost dated EARLIER in the (Jalali) month than the charge, and a
        // payment that exactly covers the charge.
        app(ExpenseService::class)->createAndDistribute([
            'title' => 'insurance', 'amount' => 2_000_000, 'expense_date' => $this->jDate(0, 2),
            'distribution' => 'single_unit', 'responsible' => 'owner', 'unit_ids' => [$unit->id],
        ], $b);
        app(LedgerService::class)->recordCharge($unit, 8_000_000, 'charge', $this->jDate(0, 15));
        app(LedgerService::class)->recordPayment($unit, 8_000_000, 'pay', $this->jDate(0, 16));

        $m = DebtMatrix::build($b->id, 'monthly', 1);
        $row = collect($m['rows'])->firstWhere('number', '1');

        $this->assertSame('paid', $row['months'][0]['state']);
        $this->assertSame(8_000_000, $row['months'][0]['value']);
        $this->assertSame('unpaid', $row['special_costs']['state']);
        $this->assertSame(2_000_000, $row['special_costs']['value']);
        $this->assertSame(2_000_000, $row['total_debt']);
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

        $m2 = $this->jDate(2);
        $m1 = $this->jDate(1);
        $m0 = $this->jDate(0);

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
        $this->assertSame('partial', $row['special_costs']['state']);
        $this->assertSame(0, $row['past_debt']);
        $this->assertSame(11_000_000, $row['total_debt']);
    }

    public function test_past_debt_is_itemised_in_the_description(): void
    {
        [$admin, $b] = $this->makeOrg();
        $unit = Unit::create(['building_id' => $b->id, 'number' => '1']);

        // An unpaid charge 3 Jalali months ago — before a 1-month window → past debt.
        app(LedgerService::class)->recordCharge($unit, 6_000_000, 'charge', $this->jDate(3));

        $row = collect(DebtMatrix::build($b->id, 'monthly', 1)['rows'])->firstWhere('number', '1');
        $this->assertSame(6_000_000, $row['past_debt']);
        $this->assertStringContainsString('بدهی شارژ', $row['notes']);
    }

    public function test_overpayment_shows_as_credit_balance(): void
    {
        [$admin, $b] = $this->makeOrg();
        $unit = Unit::create(['building_id' => $b->id, 'number' => '8']);

        app(LedgerService::class)->recordCharge($unit, 6_000_000, 'charge', $this->jDate(0, 5));
        app(LedgerService::class)->recordPayment($unit, 8_000_000, 'pay', $this->jDate(0, 6));

        $row = collect(DebtMatrix::build($b->id, 'monthly', 1)['rows'])->firstWhere('number', '8');
        $this->assertSame(0, $row['total_debt']);
        $this->assertSame(2_000_000, $row['credit_balance']); // overpaid 8M - 6M
    }
}
