<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin', 'manager', 'accountant'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $mobile = env('ADMIN_MOBILE', '09121714525');

        if (User::where('mobile', $mobile)->exists()) {
            return;
        }

        $user = User::create([
            'name' => env('ADMIN_NAME', 'مدیر سیستم'),
            'mobile' => $mobile,
            'password' => bcrypt(env('ADMIN_PASSWORD', 'changeme')),
            'is_active' => true,
        ]);

        $user->assignRole('admin');
    }
}
