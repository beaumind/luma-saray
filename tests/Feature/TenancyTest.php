<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use App\Livewire\Auth\Register;
use App\Models\Building;
use App\Models\ExpenseCategory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TenancyTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrg(string $orgName, string $mobile): User
    {
        return app(CreateOrganization::class)->handle(
            organizationName: $orgName,
            adminName: 'Admin '.$orgName,
            adminMobile: $mobile,
            adminPassword: 'secret123',
        );
    }

    public function test_creating_an_organization_provisions_admin_and_categories(): void
    {
        $admin = $this->makeOrg('Org A', '09120000001');

        $this->assertNotNull($admin->organization_id);
        $this->assertTrue($admin->hasRole('admin'));

        $this->actingAs($admin);
        $this->assertSame(8, ExpenseCategory::count());
    }

    public function test_data_is_isolated_between_organizations(): void
    {
        $adminA = $this->makeOrg('Org A', '09120000001');
        $adminB = $this->makeOrg('Org B', '09120000002');

        $this->actingAs($adminA);
        Building::create(['name' => 'Tower A', 'address' => 'x', 'city' => 'y']);

        $this->actingAs($adminB);
        Building::create(['name' => 'Tower B', 'address' => 'x', 'city' => 'y']);

        // Each admin sees only their own building.
        $this->actingAs($adminA);
        $this->assertSame(1, Building::count());
        $this->assertSame('Tower A', Building::first()->name);
        $this->assertSame(8, ExpenseCategory::count());

        $this->actingAs($adminB);
        $this->assertSame(1, Building::count());
        $this->assertSame('Tower B', Building::first()->name);
    }

    public function test_cross_organization_record_is_not_findable(): void
    {
        $adminA = $this->makeOrg('Org A', '09120000001');
        $this->actingAs($adminA);
        $buildingA = Building::create(['name' => 'Tower A', 'address' => 'x', 'city' => 'y']);

        $adminB = $this->makeOrg('Org B', '09120000002');
        $this->actingAs($adminB);

        $this->assertNull(Building::find($buildingA->id));
    }

    public function test_register_component_creates_org_and_authenticates(): void
    {
        Livewire::test(Register::class)
            ->set('organization_name', 'برج نور')
            ->set('name', 'مدیر نور')
            ->set('mobile', '09121234567')
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('organizations', ['name' => 'برج نور']);
        $this->assertDatabaseHas('users', ['mobile' => '09121234567']);

        $org = Organization::where('name', 'برج نور')->first();
        $this->assertNotNull($org);
        $this->assertTrue(auth()->check());
        $this->assertSame($org->id, auth()->user()->organization_id);
    }

    public function test_register_rejects_duplicate_mobile(): void
    {
        $this->makeOrg('Org A', '09121234567');

        Livewire::test(Register::class)
            ->set('organization_name', 'Org B')
            ->set('name', 'Someone')
            ->set('mobile', '09121234567')
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->call('register')
            ->assertHasErrors('mobile');
    }
}
