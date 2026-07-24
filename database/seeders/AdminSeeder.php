<?php

namespace Database\Seeders;

use App\Actions\CreateOrganization;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $mobile = env('ADMIN_MOBILE', '09121714525');

        if (User::withoutGlobalScopes()->where('mobile', $mobile)->exists()) {
            return;
        }

        app(CreateOrganization::class)->handle(
            organizationName: env('ADMIN_ORG_NAME', 'مجموعه اصلی'),
            adminName: env('ADMIN_NAME', 'مدیر سیستم'),
            adminMobile: $mobile,
            adminPassword: env('ADMIN_PASSWORD', 'changeme'),
        );
    }
}
