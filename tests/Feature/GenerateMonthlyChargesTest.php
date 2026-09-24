<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use App\Livewire\Charges\Index as ChargesIndex;
use App\Models\Building;
use App\Models\ChargeTemplate;
use App\Models\LedgerTransaction;
use App\Models\Resident;
use App\Models\Unit;
use App\Support\JDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class GenerateMonthlyChargesTest extends TestCase
{
    use RefreshDatabase;

    /** first Gregorian day of the Jalali month $offset months from now */
    private function jStart(int $offset): string
    {
        $j = $offset > 0 ? Jalalian::now()->addMonths($offset) : Jalalian::now();
        [$s] = JDate::gregorianMonthRange((int) $j->getYear(), (int) $j->getMonth());

        return $s->toDateString();
    }

    private function jEnd(int $offset): string
    {
        $j = $offset > 0 ? Jalalian::now()->addMonths($offset) : Jalalian::now();
        [, $e] = JDate::gregorianMonthRange((int) $j->getYear(), (int) $j->getMonth());

        return $e->copy()->subDay()->toDateString();
    }

    public function test_bills_every_month_in_the_template_range_once(): void
    {
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000001', 'secret123');
        $this->actingAs($admin);
        $b = Building::create(['name' => 'B', 'address' => 'x', 'city' => 'y']);
        $unit = Unit::create(['building_id' => $b->id, 'number' => '1']);
        Resident::create(['unit_id' => $unit->id, 'type' => 'owner', 'name' => 'X', 'resident_count' => 2, 'is_active' => true]);
        ChargeTemplate::create([
            'building_id' => $b->id, 'title' => 'پاییز', 'type' => 'combined', 'period' => 'quarterly',
            'fixed_amount' => 4_000_000, 'per_resident_amount' => 1_000_000, 'is_active' => true,
            'starts_on' => $this->jStart(0), 'ends_on' => $this->jEnd(2), // a 3-month range
        ]);

        $this->artisan('charges:generate')->assertSuccessful();

        $charges = LedgerTransaction::where('unit_id', $unit->id)->where('type', 'charge')->get();
        $this->assertCount(3, $charges);
        $this->assertTrue($charges->every(fn ($c) => (int) $c->amount === 6_000_000)); // 4M + 1M*2

        // Idempotent.
        $this->artisan('charges:generate')->assertSuccessful();
        $this->assertSame(3, LedgerTransaction::where('unit_id', $unit->id)->where('type', 'charge')->count());
    }

    public function test_overlapping_active_templates_are_rejected(): void
    {
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000001', 'secret123');
        $this->actingAs($admin);
        $b = Building::create(['name' => 'B', 'address' => 'x', 'city' => 'y']);
        ChargeTemplate::create([
            'building_id' => $b->id, 'title' => 'A', 'type' => 'fixed', 'period' => 'quarterly',
            'fixed_amount' => 6_000_000, 'per_resident_amount' => 0, 'is_active' => true,
            'starts_on' => $this->jStart(0), 'ends_on' => $this->jEnd(2),
        ]);

        $now = Jalalian::now();
        Livewire::test(ChargesIndex::class)
            ->set('tpl_building_id', (string) $b->id)
            ->set('title', 'B')
            ->set('type', 'fixed')
            ->set('fixed_amount', '600000')
            // overlaps the existing range (same current month)
            ->set('from_date', JDate::toJalali($this->jStart(0)))
            ->set('to_date', JDate::toJalali($this->jEnd(0)))
            ->call('save')
            ->assertHasErrors('to_date');

        $this->assertSame(1, ChargeTemplate::where('building_id', $b->id)->count());
    }
}
