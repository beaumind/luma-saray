<?php

namespace App\Console\Commands;

use App\Models\Building;
use App\Models\ChargeTemplate;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseUnit;
use App\Models\LedgerTransaction;
use App\Models\Payment;
use App\Models\Resident;
use App\Models\Unit;
use App\Models\User;
use App\Services\LedgerService;
use App\Services\PaymentService;
use App\Support\JDate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Reconstructs the full financial history of "ساختمان ۳۶ کمالی" from the
 * owner's seasonal spreadsheets: units, residents, 12 months of charges and
 * payments, and fund-paid expenses. Idempotent — resets this building's
 * financial data and rebuilds it so re-running always yields the same state.
 */
class ImportKamaliBuilding extends Command
{
    protected $signature = 'building:import-kamali
        {--mobile=09121714525 : Admin mobile of the target organization}
        {--building=ساختمان ۳۶ کمالی : Building name (created if missing)}';

    protected $description = 'Import building 36 Kamali: units, residents, charges, payments, expenses (idempotent).';

    /** Jalali year/month for each of the 12 history months (oldest → newest). */
    private const MONTHS = [
        [1404, 7], [1404, 8], [1404, 9], [1404, 10], [1404, 11], [1404, 12],
        [1405, 1], [1405, 2], [1405, 3], [1405, 4], [1405, 5], [1405, 6],
    ];

    private const MONTH_NAMES = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    /** number => [owner, resident(occupant), persons] */
    private const UNITS = [
        1 => ['آقای حامدین', 'آقای حامدین', 3],
        2 => ['آقای مزرعاوی', 'آقای حسنی', 4],
        3 => ['آقای صالحی', 'آقای صالحی', 4],
        4 => ['آقای ادیبان', 'آقای ادیبان', 2],
        5 => ['آقای شریفی', 'آقای شریفی', 4],
        6 => ['آقای بهجویی', 'آقای بهجویی', 2],
        7 => ['آقای نفتچی', 'آقای کهریزی', 4],
        8 => ['آقای طاهردوست', 'آقای طاهردوست', 2],
        9 => ['آقای حسن‌علی', 'آقای حسن‌علی', 4],
        10 => ['آقای بوربور', 'آقای حسن‌پور', 3],
    ];

    /** Monthly charge issued to each unit, per month index 0..11. */
    private const CHARGES = [
        1 => [700000, 700000, 700000, 700000, 700000, 700000, 700000, 700000, 700000, 700000, 700000, 700000],
        2 => [800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000],
        3 => [800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000],
        4 => [400000, 400000, 400000, 600000, 600000, 600000, 600000, 600000, 600000, 600000, 600000, 600000],
        5 => [800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000],
        6 => [600000, 600000, 600000, 600000, 600000, 600000, 600000, 600000, 0, 600000, 600000, 600000],
        7 => [800000, 800000, 800000, 800000, 800000, 800000, 400000, 400000, 400000, 800000, 800000, 800000],
        8 => [600000, 600000, 600000, 600000, 600000, 600000, 600000, 600000, 600000, 600000, 600000, 600000],
        9 => [800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000],
        10 => [700000, 700000, 700000, 700000, 700000, 700000, 700000, 700000, 700000, 700000, 700000, 700000],
    ];

    /** Payment made by each unit, per month index 0..11 (0 = none). */
    private const PAYMENTS = [
        1 => [700000, 700000, 700000, 700000, 700000, 700000, 700000, 700000, 700000, 0, 0, 0],
        2 => [800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000],
        3 => [800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 0, 0, 0],
        4 => [400000, 400000, 400000, 600000, 600000, 600000, 600000, 600000, 600000, 0, 0, 0],
        5 => [800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000],
        6 => [600000, 600000, 600000, 600000, 600000, 600000, 0, 0, 0, 0, 0, 0],
        7 => [800000, 800000, 800000, 800000, 800000, 800000, 400000, 400000, 400000, 800000, 400000, 0],
        8 => [600000, 600000, 600000, 600000, 600000, 600000, 600000, 600000, 600000, 600000, 518000, 0],
        9 => [800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 0, 0],
        10 => [700000, 700000, 700000, 700000, 700000, 700000, 700000, 700000, 700000, 0, 0, 0],
    ];

    /** Expected final balance per unit (from summer 1405 مجموع بدهی). */
    private const TARGETS = [
        1 => 2100000, 2 => 0, 3 => 2400000, 4 => 1800000, 5 => 0,
        6 => 3000000, 7 => 1200000, 8 => 682000, 9 => 1600000, 10 => 2100000,
    ];

    /** [Jalali date, description, responsible, amount] — all paid from fund. */
    private const EXPENSES = [
        ['1404/08/26', 'قبض برق', 'آقای حسنی', 2672000],
        ['1404/08/26', 'قبض آب', 'آقای حسنی', 22309000],
        ['1404/08/28', 'خرید آرام بند', 'آقای حسنی', 17500000],
        ['1404/08/28', 'ارسال آرام بند', 'آقای حسنی', 1850000],
        ['1404/08/28', 'نصب آرام بند', 'آقای حسنی', 9000000],
        ['1404/08/28', 'خرید چکمه', 'آقای حسنی', 4310000],
        ['1404/08/28', 'خرید طی زمین شور', 'آقای حسنی', 6850000],
        ['1404/09/01', 'نظافت ساختمان', 'آقای کهریزی', 9000000],
        ['1404/09/04', 'خرید لوازم نظافت', 'آقای حسنی', 3458000],
        ['1404/09/05', 'نظافت ساختمان', 'آقای کهریزی', 12000000],
        ['1404/09/05', 'قبض آب', 'آقای حسنی', 18502000],
        ['1404/09/05', 'قبض برق', 'آقای حسنی', 2801000],
        ['1404/09/08', 'سرویس آسانسور', 'آقای حسنعلی', 13500000],
        ['1404/09/17', 'ساخت و نصب دستگیره', 'آقای حسنی', 7500000],
        ['1404/09/19', 'نظافت و خرید لوازم نظافت', 'آقای کهریزی', 13850000],
        ['1404/09/29', 'سرویس آسانسور (آذر)', 'آقای حسنعلی', 6000000],
        ['1404/10/15', 'قبض آب', 'آقای حسنی', 30749000],
        ['1404/10/15', 'خرید هرزگرد آسانسور', 'آقای حسنی', 45000000],
        ['1404/10/15', 'ارسال هرزگرد', 'آقای حسنی', 3500000],
        ['1404/10/16', 'خرید لوازم نظافت+ نظافت + عیدی', 'آقای طاهردوست', 20000000],
        ['1404/10/26', 'نصب هرزگرد', 'آقای حسنعلی', 75000000],
        ['1404/10/26', 'سرویس آسانسور دی ماه', 'آقای حسنعلی', 6000000],
        ['1404/11/04', 'قبض برق', 'آقای حسنی', 6752000],
        ['1404/11/29', 'خرید لوازم نظافت+ نظافت + عیدی', 'آقای طاهردوست', 16020000],
        ['1404/12/06', 'بازدید پمپ آب', 'آقای طاهردوست', 8000000],
        ['1404/12/06', 'خرید شیر آب', 'آقای طاهردوست', 4500000],
        ['1404/12/24', 'سرویس آسانسور بهمن ماه', 'آقای حسنعلی', 14000000],
        ['1404/12/28', 'نظافت + عیدی', 'آقای طاهردوست', 15000000],
        ['1405/01/12', 'آنتن مرکزی', 'آقای طاهردوست', 15000000],
        ['1405/01/15', 'خرید سنسور و لامپ', 'آقای طاهردوست', 10900000],
        ['1405/01/25', 'قبض آب', 'آقای حسنی', 36121000],
        ['1405/01/25', 'قبض برق', 'آقای حسنی', 8116000],
        ['1405/02/02', 'خرید شیر آب مدل گازی', 'آقای طاهردوست', 5850000],
        ['1405/02/02', 'سرویس آسانسور فروردین', 'آقای حسنعلی', 8600000],
        ['1405/02/08', 'قبض آب', 'آقای حسنی', 25924000],
        ['1405/02/13', 'نظافت ساختمان', 'آقای طاهردوست', 13000000],
        ['1405/02/25', 'خرید لوازم', 'آقای طاهردوست', 6650000],
        ['1405/02/30', 'سرویس آسانسور اردیبهشت و تعویض سنسور', 'آقای حسنعلی', 40000000],
        ['1405/02/14', 'خرید ابزار', 'آقای طاهردوست', 1950000],
        ['1405/02/24', 'قبض آب', 'آقای حسنی', 30570000],
        ['1405/02/24', 'نظافت ساختمان', 'آقای طاهردوست', 3000000],
        ['1405/02/24', 'قبض برق', 'آقای حسنی', 3701000],
        ['1405/02/25', 'تعویض توپی درب ورود', 'آقای طاهردوست', 55000000],
        ['1405/02/26', 'سرویس آسانسور خرداد', 'آقای طاهردوست', 12000000],
        ['1405/04/05', 'نظافت ساختمان', 'آقای طاهردوست', 13000000],
        ['1405/04/05', 'خرید جرم گیر', 'آقای طاهردوست', 2580000],
    ];

    public function handle(LedgerService $ledger, PaymentService $payments): int
    {
        $admin = User::withoutGlobalScopes()->where('mobile', (string) $this->option('mobile'))->first();
        if (! $admin) {
            $this->error('Admin user not found.');

            return self::FAILURE;
        }
        Auth::login($admin);
        $orgId = $admin->organization_id;
        $buildingName = (string) $this->option('building');

        DB::transaction(function () use ($ledger, $payments, $admin, $orgId, $buildingName) {
            $building = Building::firstOrCreate(
                ['organization_id' => $orgId, 'name' => $buildingName],
                ['address' => 'تهران', 'city' => 'تهران', 'floors' => 5, 'total_units' => 10, 'is_active' => true]
            );

            $this->resetBuilding($building->id);

            // Charge template (combined: 400k fixed + 100k/person) for future use.
            ChargeTemplate::create([
                'organization_id' => $orgId, 'building_id' => $building->id,
                'title' => 'شارژ ماهانه', 'type' => 'combined', 'period' => 'monthly',
                'fixed_amount' => 400000, 'per_resident_amount' => 100000, 'is_active' => true,
            ]);

            // Units + residents.
            $unitModels = [];
            foreach (self::UNITS as $no => [$owner, $occupant, $persons]) {
                $unit = Unit::create([
                    'organization_id' => $orgId, 'building_id' => $building->id,
                    'number' => (string) $no, 'floor' => (int) ceil($no / 2),
                    'bedrooms' => 2, 'parking_count' => 1, 'storage_count' => 1, 'is_active' => true,
                ]);
                $unitModels[$no] = $unit;
                $this->makeResidents($unit, $orgId, $owner, $occupant, $persons, $no);
            }

            // Charges + payments per month.
            foreach (self::MONTHS as $mi => [$jy, $jm]) {
                $chargeDate = JDate::toGregorian(sprintf('%d/%02d/01', $jy, $jm));
                $payDate = JDate::toGregorian(sprintf('%d/%02d/15', $jy, $jm));
                $label = self::MONTH_NAMES[$jm - 1].' '.$jy;

                foreach ($unitModels as $no => $unit) {
                    $charge = self::CHARGES[$no][$mi];
                    if ($charge > 0) {
                        $ledger->recordCharge($unit, $charge, "شارژ ماهانه {$label}", $chargeDate);
                    }
                    $pay = self::PAYMENTS[$no][$mi];
                    if ($pay > 0) {
                        $payments->register($unit, [
                            'amount' => $pay,
                            'payment_date' => $payDate,
                            'tracking_number' => null,
                            'notes' => "شارژ {$label}",
                        ]);
                    }
                }
            }

            $this->importExpenses($building->id, $orgId, $admin->id, $payments);
        });

        return $this->verify($orgId, $buildingName);
    }

    private function resetBuilding(int $buildingId): void
    {
        $unitIds = Unit::where('building_id', $buildingId)->pluck('id');
        $expenseIds = Expense::where('building_id', $buildingId)->pluck('id');
        ExpenseUnit::whereIn('expense_id', $expenseIds)->delete();
        Expense::where('building_id', $buildingId)->delete();
        LedgerTransaction::where('building_id', $buildingId)->delete();
        Payment::where('building_id', $buildingId)->delete();
        ChargeTemplate::where('building_id', $buildingId)->delete();
        Resident::whereIn('unit_id', $unitIds)->delete();
        Unit::where('building_id', $buildingId)->delete();
    }

    private function makeResidents(Unit $unit, int $orgId, string $owner, string $occupant, int $persons, int $no): void
    {
        $moveIn = JDate::toGregorian('1404/07/01');

        if ($owner === $occupant) {
            Resident::create([
                'organization_id' => $orgId, 'unit_id' => $unit->id, 'type' => 'owner',
                'name' => $owner, 'resident_count' => $persons, 'move_in_date' => $moveIn, 'is_active' => true,
            ]);
        } else {
            // occupant first so it is the "resident" of record; owner separately.
            Resident::create([
                'organization_id' => $orgId, 'unit_id' => $unit->id, 'type' => 'tenant',
                'name' => $occupant, 'resident_count' => $persons, 'move_in_date' => $moveIn, 'is_active' => true,
            ]);
            Resident::create([
                'organization_id' => $orgId, 'unit_id' => $unit->id, 'type' => 'owner',
                'name' => $owner, 'resident_count' => 0, 'move_in_date' => $moveIn, 'is_active' => true,
            ]);
        }

        // Unit 4: previous resident آقای انصاری moved out at the end of autumn 1404.
        if ($no === 4) {
            Resident::create([
                'organization_id' => $orgId, 'unit_id' => $unit->id, 'type' => 'owner',
                'name' => 'آقای انصاری', 'resident_count' => 0,
                'move_in_date' => $moveIn, 'move_out_date' => JDate::toGregorian('1404/09/30'), 'is_active' => false,
            ]);
        }
    }

    private function importExpenses(int $buildingId, int $orgId, int $adminId, PaymentService $payments): void
    {
        $categories = ExpenseCategory::pluck('id', 'name');
        foreach (self::EXPENSES as [$jdate, $title, $person, $amount]) {
            $expense = Expense::create([
                'organization_id' => $orgId, 'building_id' => $buildingId,
                'expense_category_id' => $categories[$this->categoryFor($title)] ?? null,
                'created_by' => $adminId, 'title' => $title, 'amount' => $amount,
                'expense_date' => JDate::toGregorian($jdate),
                'description' => 'مسئول: '.$person, 'distribution' => 'fund', 'responsible' => 'both',
            ]);

            // These fund costs were historically paid from the fund — record the disbursement.
            $payments->registerFundCost($expense, [
                'amount' => $amount,
                'payment_date' => JDate::toGregorian($jdate),
                'notes' => 'پرداخت از صندوق — مسئول: '.$person,
            ]);
        }
    }

    private function categoryFor(string $title): string
    {
        return match (true) {
            str_contains($title, 'برق'), str_contains($title, 'لامپ'), str_contains($title, 'سنسور') => 'برق و روشنایی',
            str_contains($title, 'آب') => 'آب',
            str_contains($title, 'آسانسور'), str_contains($title, 'هرزگرد') => 'آسانسور',
            str_contains($title, 'نظافت'), str_contains($title, 'عیدی'), str_contains($title, 'چکمه'), str_contains($title, 'طی'), str_contains($title, 'جرم گیر') => 'نظافت',
            default => 'نگهداری و تعمیرات',
        };
    }

    private function verify(int $orgId, string $buildingName): int
    {
        $building = Building::where('organization_id', $orgId)->where('name', $buildingName)->first();
        $ok = true;
        $rows = [];
        foreach (Unit::where('building_id', $building->id)->orderByRaw('LENGTH(number)')->orderBy('number')->get() as $unit) {
            $bal = $unit->balance;
            $target = self::TARGETS[(int) $unit->number] ?? null;
            $match = $bal === $target;
            $ok = $ok && $match;
            $rows[] = [$unit->number, number_format($target), number_format($bal), $match ? 'OK' : 'MISMATCH'];
        }
        $this->table(['واحد', 'هدف', 'مانده', 'وضعیت'], $rows);
        $this->info('هزینه‌ها: '.Expense::where('building_id', $building->id)->count().' مورد — '.number_format((int) Expense::where('building_id', $building->id)->sum('amount')).' ریال');

        if (! $ok) {
            $this->error('برخی مانده‌ها با فایل مطابقت ندارند.');

            return self::FAILURE;
        }
        $this->info('✓ همهٔ مانده‌ها با فایل‌های اکسل مطابقت دارند.');

        return self::SUCCESS;
    }
}
