<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use App\Models\Building;
use App\Models\Resident;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RosterExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000001', 'secret123');
        $this->actingAs($admin);
        $b = Building::create(['name' => 'B', 'address' => 'x', 'city' => 'y']);
        $u = Unit::create(['building_id' => $b->id, 'number' => '1', 'floor' => 2]);
        Resident::create(['unit_id' => $u->id, 'type' => 'owner', 'name' => 'Ali', 'mobile' => '09120000002', 'resident_count' => 3, 'is_active' => true]);
    }

    public function test_units_exports(): void
    {
        $this->get(route('units.export.excel'))->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->get(route('units.export.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_residents_exports_with_type_filter(): void
    {
        $this->get(route('residents.export.excel', ['type' => 'owner']))->assertOk();
        $this->get(route('residents.export.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
    }
}
