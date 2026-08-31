<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use App\Livewire\Payments\Index as PaymentsIndex;
use App\Models\Building;
use App\Models\Expense;
use App\Models\Unit;
use App\Services\LedgerService;
use App\Support\JDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreditApplyOnRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_credit_with_checkbox_offsets_debt_immediately(): void
    {
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000001', 'secret123');
        $this->actingAs($admin);
        $building = Building::create(['name' => 'B', 'address' => 'x', 'city' => 'y']);
        $unit = Unit::create(['building_id' => $building->id, 'number' => '8']);
        // 600k toman debt (rial).
        app(LedgerService::class)->recordCharge($unit, 6_000_000, 'charge', now()->format('Y-m-d'));
        $expense = Expense::create([
            'building_id' => $building->id, 'created_by' => $admin->id, 'title' => 'lamps',
            'amount' => 4_000_000, 'expense_date' => now()->format('Y-m-d'), 'distribution' => 'fund', 'responsible' => 'both',
        ]);

        // 400k toman credit, apply immediately.
        Livewire::test(PaymentsIndex::class)
            ->set('type', 'unit_credit')
            ->set('unit_id', (string) $unit->id)
            ->set('expense_id', (string) $expense->id)
            ->set('amount', '400000')
            ->set('payment_date', JDate::today())
            ->set('apply_credit_now', true)
            ->call('save')
            ->assertHasNoErrors();

        $unit->refresh();
        $this->assertSame(2_000_000, $unit->balance, 'debt should drop by the applied credit');
        $this->assertSame(0, $unit->creditBalance, 'credit fully applied');
    }

    public function test_unit_credit_without_checkbox_leaves_debt_and_keeps_credit(): void
    {
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000002', 'secret123');
        $this->actingAs($admin);
        $building = Building::create(['name' => 'B', 'address' => 'x', 'city' => 'y']);
        $unit = Unit::create(['building_id' => $building->id, 'number' => '8']);
        app(LedgerService::class)->recordCharge($unit, 6_000_000, 'charge', now()->format('Y-m-d'));
        $expense = Expense::create([
            'building_id' => $building->id, 'created_by' => $admin->id, 'title' => 'lamps',
            'amount' => 4_000_000, 'expense_date' => now()->format('Y-m-d'), 'distribution' => 'fund', 'responsible' => 'both',
        ]);

        Livewire::test(PaymentsIndex::class)
            ->set('type', 'unit_credit')->set('unit_id', (string) $unit->id)->set('expense_id', (string) $expense->id)
            ->set('amount', '400000')->set('payment_date', JDate::today())
            ->set('apply_credit_now', false)
            ->call('save')->assertHasNoErrors();

        $unit->refresh();
        $this->assertSame(6_000_000, $unit->balance, 'debt untouched');
        $this->assertSame(4_000_000, $unit->creditBalance, 'credit stands');
    }
}
