<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use App\Livewire\Payments\Index as PaymentsIndex;
use App\Models\Building;
use App\Models\Payment;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JalaliDateInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_saved_with_jalali_date_is_stored_as_gregorian(): void
    {
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000001', 'secret123');
        $this->actingAs($admin);

        $building = Building::create(['name' => 'Tower', 'address' => 'x', 'city' => 'y']);
        $unit = Unit::create(['building_id' => $building->id, 'number' => '101']);

        Livewire::test(PaymentsIndex::class)
            ->set('unit_id', (string) $unit->id)
            ->set('amount', '500000')
            ->set('payment_date', '۱۴۰۳/۰۵/۰۱')
            ->call('save')
            ->assertHasNoErrors();

        $payment = Payment::firstOrFail();
        $this->assertSame('2024-07-22', $payment->payment_date->format('Y-m-d'));
    }

    public function test_invalid_jalali_date_is_rejected(): void
    {
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000001', 'secret123');
        $this->actingAs($admin);

        $building = Building::create(['name' => 'Tower', 'address' => 'x', 'city' => 'y']);
        $unit = Unit::create(['building_id' => $building->id, 'number' => '101']);

        Livewire::test(PaymentsIndex::class)
            ->set('unit_id', (string) $unit->id)
            ->set('amount', '500000')
            ->set('payment_date', 'garbage')
            ->call('save')
            ->assertHasErrors('payment_date');
    }
}
