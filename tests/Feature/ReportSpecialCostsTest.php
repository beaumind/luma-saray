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

    public function test_costs_go_to_special_column_not_months_and_credit_shows(): void
    {
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000001', 'secret123');
        $this->actingAs($admin);
        $b = Building::create(['name' => 'B', 'address' => 'x', 'city' => 'y']);
        $payer = Unit::create(['building_id' => $b->id, 'number' => '1']);
        $other = Unit::create(['building_id' => $b->id, 'number' => '2']);

        // A monthly charge this month for both.
        $today = now()->format('Y-m-d');
        app(LedgerService::class)->recordCharge($payer, 6_000_000, 'charge', $today);
        app(LedgerService::class)->recordCharge($other, 6_000_000, 'charge', $today);

        // Insurance: all_units/owner → per-unit cost debits; payer fronts full amount as a credit.
        $exp = app(ExpenseService::class)->createAndDistribute([
            'title' => 'insurance', 'amount' => 2_000_000, 'expense_date' => $today,
            'distribution' => 'all_units', 'responsible' => 'owner',
        ], $b);
        app(PaymentService::class)->registerUnitCredit($payer, $exp, ['amount' => 2_000_000, 'payment_date' => $today]);

        $m = DebtMatrix::build($b->id, 'monthly', 1);
        $rowPayer = collect($m['rows'])->firstWhere('number', '1');

        // The month cell shows only charge activity (no payment yet → 0 paid),
        // and is not polluted by the 1,000,000 cost share.
        $this->assertSame(0, $rowPayer['months'][0]['value']);
        // special_costs holds the 1,000,000 insurance share.
        $this->assertSame(1_000_000, $rowPayer['special_costs']['value']);
        // Payer is a standing creditor for the full fronted amount.
        $this->assertSame(2_000_000, $rowPayer['credit_balance']);
        // Column list includes the two new columns.
        $keys = collect($m['columns'])->pluck('key');
        $this->assertTrue($keys->contains('special_costs'));
        $this->assertTrue($keys->contains('credit'));
    }
}
