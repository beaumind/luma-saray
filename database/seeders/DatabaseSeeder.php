<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\ChargeTemplate;
use App\Models\ExpenseCategory;
use App\Models\Resident;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminSeeder::class);

        // Demo manager
        $manager1 = User::create([
            'name' => 'احمد رضایی',
            'mobile' => '09351234567',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $manager1->assignRole('manager');

        // Expense categories
        $categories = [
            ['name' => 'نگهداری و تعمیرات', 'icon' => 'wrench', 'color' => '#f59e0b'],
            ['name' => 'نظافت', 'icon' => 'sparkles', 'color' => '#10b981'],
            ['name' => 'برق و روشنایی', 'icon' => 'bolt', 'color' => '#3b82f6'],
            ['name' => 'آب', 'icon' => 'droplets', 'color' => '#06b6d4'],
            ['name' => 'گاز', 'icon' => 'flame', 'color' => '#f97316'],
            ['name' => 'آسانسور', 'icon' => 'arrow-up-down', 'color' => '#8b5cf6'],
            ['name' => 'بیمه', 'icon' => 'shield', 'color' => '#ec4899'],
            ['name' => 'سایر', 'icon' => 'more-horizontal', 'color' => '#6b7280'],
        ];
        foreach ($categories as $cat) {
            ExpenseCategory::create($cat);
        }

        // Sample building
        $building = Building::create([
            'name' => 'برج سپهر',
            'address' => 'تهران، ولیعصر، خیابان شهید بهشتی',
            'city' => 'تهران',
            'total_units' => 12,
            'floors' => 4,
            'manager_name' => 'احمد رضایی',
            'manager_mobile' => '09351234567',
            'is_active' => true,
        ]);

        // Units + residents
        $residents = [
            ['واحد ۱۰۱', 'محمد احمدی', '09121111111', 'owner', 3],
            ['واحد ۱۰۲', 'فاطمه کریمی', '09122222222', 'owner', 2],
            ['واحد ۱۰۳', 'علی محمدی', '09123333333', 'tenant', 4],
            ['واحد ۲۰۱', 'زهرا حسینی', '09124444444', 'owner', 2],
            ['واحد ۲۰۲', 'رضا نجفی', '09125555555', 'tenant', 3],
            ['واحد ۲۰۳', 'مریم صادقی', '09126666666', 'owner', 1],
            ['واحد ۳۰۱', 'حسن موسوی', '09127777777', 'owner', 5],
            ['واحد ۳۰۲', 'نرگس رحیمی', '09128888888', 'tenant', 2],
            ['واحد ۳۰۳', 'امیر جعفری', '09129999999', 'owner', 3],
            ['واحد ۴۰۱', 'سارا ابراهیمی', '09120000001', 'owner', 2],
            ['واحد ۴۰۲', 'کامران شریفی', '09120000002', 'tenant', 4],
            ['واحد ۴۰۳', 'لیلا قاسمی', '09120000003', 'owner', 2],
        ];

        foreach ($residents as $index => [$number, $name, $mobile, $type, $count]) {
            $unit = Unit::create([
                'building_id' => $building->id,
                'number' => $number,
                'floor' => (int) ceil(($index + 1) / 3),
                'area' => rand(80, 150),
                'bedrooms' => rand(1, 3),
                'parking_count' => 1,
                'storage_count' => 1,
                'is_active' => true,
            ]);

            Resident::create([
                'unit_id' => $unit->id,
                'type' => $type,
                'name' => $name,
                'mobile' => $mobile,
                'resident_count' => $count,
                'move_in_date' => now()->subMonths(rand(6, 24))->format('Y-m-d'),
                'is_active' => true,
            ]);
        }

        // Charge templates
        ChargeTemplate::create([
            'building_id' => $building->id,
            'title' => 'شارژ ماهانه',
            'type' => 'fixed',
            'period' => 'monthly',
            'fixed_amount' => 500000,
            'per_resident_amount' => 0,
            'is_active' => true,
        ]);

        ChargeTemplate::create([
            'building_id' => $building->id,
            'title' => 'شارژ سرانه',
            'type' => 'per_resident',
            'period' => 'monthly',
            'fixed_amount' => 0,
            'per_resident_amount' => 100000,
            'is_active' => true,
        ]);
    }
}
