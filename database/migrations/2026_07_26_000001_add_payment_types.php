<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // charge | fund_cost | unit_cost | unit_credit
            $table->string('type', 20)->default('charge')->after('building_id');
            // The cost this payment settles (for fund_cost / unit_cost / unit_credit).
            $table->foreignId('expense_id')->nullable()->after('type')->constrained()->nullOnDelete();
        });

        // fund_cost payments have no unit — make unit_id nullable.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE payments MODIFY unit_id BIGINT UNSIGNED NULL');
        } else {
            Schema::table('payments', function (Blueprint $table) {
                $table->unsignedBigInteger('unit_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['expense_id']);
            $table->dropColumn(['type', 'expense_id']);
        });
    }
};
