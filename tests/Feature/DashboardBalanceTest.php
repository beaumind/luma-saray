<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use App\Livewire\Dashboard\Index as Dashboard;
use App\Models\Building;
use App\Models\Payment;
use App\Models\Unit;
use App\Support\JDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class DashboardBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_balance_sheet_reconciles(): void
    {
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000001', 'secret123');
        $this->actingAs($admin);
        $b = Building::create(['name' => 'B', 'address' => 'x', 'city' => 'y', 'opening_balance' => 10_000_000]);
        $unit = Unit::create(['building_id' => $b->id, 'number' => '1']);

        $now = Jalalian::now();
        [$s, $e] = JDate::gregorianMonthRange((int) $now->getYear(), (int) $now->getMonth());
        $before = $s->copy()->subDays(5)->toDateString();
        $inPeriod = $s->copy()->addDays(5)->toDateString();

        $pay = fn (string $type, int $amount, string $date) => Payment::create([
            'unit_id' => $type === 'fund_cost' ? null : $unit->id, 'building_id' => $b->id,
            'type' => $type, 'amount' => $amount, 'payment_date' => $date, 'created_by' => $admin->id,
        ]);

        $pay('charge', 5_000_000, $before);     // before period → opening
        $pay('charge', 3_000_000, $inPeriod);   // received in period
        $pay('fund_cost', 2_000_000, $inPeriod); // paid from fund in period

        Livewire::test(Dashboard::class)
            ->set('from', JDate::toJalali($s))
            ->set('to', JDate::toJalali($e->copy()->subDay()))
            ->assertViewHas('opening', 15_000_000)   // 10M setting + 5M before
            ->assertViewHas('received', 3_000_000)
            ->assertViewHas('fundOut', 2_000_000)
            ->assertViewHas('ending', 16_000_000)    // 15 + 3 - 2
            ->assertViewHas('balance', 16_000_000);  // all-time cash
    }
}
