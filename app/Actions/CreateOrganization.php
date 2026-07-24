<?php

namespace App\Actions;

use App\Models\ExpenseCategory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CreateOrganization
{
    /**
     * Default expense categories seeded for every new organization.
     */
    private const DEFAULT_CATEGORIES = [
        ['name' => 'نگهداری و تعمیرات', 'icon' => 'wrench', 'color' => '#f59e0b'],
        ['name' => 'نظافت', 'icon' => 'sparkles', 'color' => '#10b981'],
        ['name' => 'برق و روشنایی', 'icon' => 'bolt', 'color' => '#3b82f6'],
        ['name' => 'آب', 'icon' => 'droplets', 'color' => '#06b6d4'],
        ['name' => 'گاز', 'icon' => 'flame', 'color' => '#f97316'],
        ['name' => 'آسانسور', 'icon' => 'arrow-up-down', 'color' => '#8b5cf6'],
        ['name' => 'بیمه', 'icon' => 'shield', 'color' => '#ec4899'],
        ['name' => 'سایر', 'icon' => 'more-horizontal', 'color' => '#6b7280'],
    ];

    /**
     * Create an organization with its first admin user and default data.
     */
    public function handle(string $organizationName, string $adminName, string $adminMobile, string $adminPassword): User
    {
        return DB::transaction(function () use ($organizationName, $adminName, $adminMobile, $adminPassword) {
            $this->ensureRolesExist();

            $organization = Organization::create([
                'name' => $organizationName,
                'is_active' => true,
            ]);

            $admin = User::create([
                'organization_id' => $organization->id,
                'name' => $adminName,
                'mobile' => $adminMobile,
                'password' => bcrypt($adminPassword),
                'is_active' => true,
            ]);

            $admin->assignRole('admin');

            foreach (self::DEFAULT_CATEGORIES as $category) {
                ExpenseCategory::create([
                    'organization_id' => $organization->id,
                    'name' => $category['name'],
                    'icon' => $category['icon'],
                    'color' => $category['color'],
                    'is_active' => true,
                ]);
            }

            return $admin;
        });
    }

    private function ensureRolesExist(): void
    {
        foreach (['admin', 'manager', 'accountant'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
