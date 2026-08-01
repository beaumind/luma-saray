<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use App\Livewire\Expenses\Index as ExpensesIndex;
use App\Livewire\Payments\Index as PaymentsIndex;
use App\Models\Building;
use App\Models\Unit;
use App\Services\ExpenseService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DetailAndPrefillTest extends TestCase
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

    public function test_fund_cost_prefills_display_amount(): void
    {
        // 5,000,000 rial stored; toman display => 500,000
        $expense = app(ExpenseService::class)->createAndDistribute([
            'title' => 'elevator', 'amount' => 5_000_000, 'expense_date' => now()->format('Y-m-d'),
            'distribution' => 'fund', 'responsible' => 'both',
        ], $this->building);

        Livewire::test(PaymentsIndex::class)
            ->set('type', 'fund_cost')
            ->set('expense_id', (string) $expense->id)
            ->assertSet('amount', '500000');
    }

    public function test_payment_detail_opens(): void
    {
        $expense = app(ExpenseService::class)->createAndDistribute([
            'title' => 'elevator', 'amount' => 5_000_000, 'expense_date' => now()->format('Y-m-d'),
            'distribution' => 'fund', 'responsible' => 'both',
        ], $this->building);
        $payment = app(PaymentService::class)->registerFundCost($expense, [
            'amount' => 5_000_000, 'payment_date' => now()->format('Y-m-d'),
        ]);

        Livewire::test(PaymentsIndex::class)
            ->call('openDetail', $payment->id)
            ->assertSet('showDetail', true)
            ->assertSet('detailId', $payment->id)
            ->assertSee('جزئیات پرداخت');
    }

    public function test_cost_detail_opens_and_edit_from_detail(): void
    {
        $expense = app(ExpenseService::class)->createAndDistribute([
            'title' => 'broken door', 'amount' => 3_000_000, 'expense_date' => now()->format('Y-m-d'),
            'distribution' => 'single_unit', 'responsible' => 'owner', 'unit_ids' => [$this->unit->id],
        ], $this->building);

        Livewire::test(ExpensesIndex::class)
            ->call('openDetail', $expense->id)
            ->assertSet('showDetail', true)
            ->assertSee('جزئیات هزینه')
            ->call('editFromDetail')
            ->assertSet('showDetail', false)
            ->assertSet('showModal', true)
            ->assertSet('editingId', $expense->id)
            ->assertSet('title', 'broken door');
    }
}
