<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use App\Models\Building;
use App\Models\Unit;
use App\Services\LedgerService;
use App\Services\PaymentService;
use App\Support\DebtMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DebtMatrixWaterfallTest extends TestCase
{
    use RefreshDatabase;

    public function test_paying_tir_charge_a_day_early_marks_tir_as_paid(): void
    {
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000001', 'secret123');
        $this->actingAs($admin);
        $building = Building::create(['name' => 'B', 'address' => 'x', 'city' => 'y']);
        $unit = Unit::create(['building_id' => $building->id, 'number' => '4']);

        // Charge for تیر ۱۴۰۵ is dated ۱۴۰۵/۰۴/۰۱ = 2026-06-22.
        app(LedgerService::class)->recordCharge($unit, 6_000_000, 'شارژ تیر', '2026-06-22');

        // Owner pays it one day early, on ۳۱ خرداد = 2026-06-21.
        app(PaymentService::class)->register($unit, [
            'amount' => 6_000_000,
            'payment_date' => '2026-06-21',
        ]);

        // View the summer season (تیر/مرداد/شهریور).
        $this->travelTo(Carbon::parse('2026-08-02'));
        $matrix = DebtMatrix::build($building->id, 'seasonal', 1);
        $this->travelBack();

        $row = collect($matrix['rows'])->firstWhere('number', '4');
        $tirIdx = collect($matrix['periods'])->search(fn ($p) => str_starts_with($p['label'], 'تیر'));

        $this->assertNotFalse($tirIdx, 'تیر column should be present in the summer season');
        $this->assertSame('paid', $row['months'][$tirIdx]['state'], 'تیر must show as paid even though the payment date is in خرداد');
        $this->assertSame(0, $row['past_debt']);
        $this->assertSame(0, $row['total_debt']);
    }

    public function test_unpaid_next_month_stays_red(): void
    {
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000002', 'secret123');
        $this->actingAs($admin);
        $building = Building::create(['name' => 'B', 'address' => 'x', 'city' => 'y']);
        $unit = Unit::create(['building_id' => $building->id, 'number' => '4']);

        // تیر paid (early), مرداد charged but unpaid.
        app(LedgerService::class)->recordCharge($unit, 6_000_000, 'شارژ تیر', '2026-06-22');
        app(LedgerService::class)->recordCharge($unit, 6_000_000, 'شارژ مرداد', '2026-07-23');
        app(PaymentService::class)->register($unit, ['amount' => 6_000_000, 'payment_date' => '2026-06-21']);

        $this->travelTo(Carbon::parse('2026-08-02'));
        $matrix = DebtMatrix::build($building->id, 'seasonal', 1);
        $this->travelBack();

        $row = collect($matrix['rows'])->firstWhere('number', '4');
        $tirIdx = collect($matrix['periods'])->search(fn ($p) => str_starts_with($p['label'], 'تیر'));
        $mordadIdx = collect($matrix['periods'])->search(fn ($p) => str_starts_with($p['label'], 'مرداد'));

        $this->assertSame('paid', $row['months'][$tirIdx]['state']);
        $this->assertSame('unpaid', $row['months'][$mordadIdx]['state']);
        $this->assertSame(6_000_000, $row['total_debt']);
    }
}
