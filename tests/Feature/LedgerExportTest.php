<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use App\Models\Building;
use App\Models\Unit;
use App\Services\ExpenseService;
use App\Services\PaymentService;
use App\Support\JDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000001', 'secret123');
        $this->actingAs($admin);
        $b = Building::create(['name' => 'B', 'address' => 'x', 'city' => 'y']);
        $unit = Unit::create(['building_id' => $b->id, 'number' => '1']);
        $expense = app(ExpenseService::class)->createAndDistribute([
            'title' => 'elevator', 'amount' => 5_000_000, 'expense_date' => now()->format('Y-m-d'),
            'distribution' => 'fund', 'responsible' => 'both',
        ], $b);
        app(PaymentService::class)->registerFundCost($expense, [
            'amount' => 5_000_000, 'payment_date' => now()->format('Y-m-d'),
        ]);
    }

    public function test_expense_exports(): void
    {
        $this->get(route('expenses.export.excel'))->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->get(route('expenses.export.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_payment_exports_with_date_filter(): void
    {
        $from = JDate::today();
        $this->get(route('payments.export.excel', ['from' => $from]))->assertOk();
        $this->get(route('payments.export.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
    }
}
