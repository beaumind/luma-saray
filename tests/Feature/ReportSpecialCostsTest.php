<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use App\Models\Building;
use App\Models\Unit;
use App\Services\ExpenseService;
use App\Services\LedgerService;
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
}
