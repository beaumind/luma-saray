<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // When a unit pays a fund cost directly, this records who paid so
            // their debt to the fund is reduced by the cost amount.
            $table->foreignId('paid_by_unit_id')->nullable()->after('distribution')
                ->constrained('units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['paid_by_unit_id']);
            $table->dropColumn('paid_by_unit_id');
        });
    }
};
