<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            // Round each unit's cost share when a cost is split: off | up | down.
            $table->string('cost_rounding_mode', 10)->default('off')->after('opening_balance');
            // The rounding step, in RIAL (e.g. 100000 rial = 10000 toman). 0 = disabled.
            $table->unsignedBigInteger('cost_rounding_step')->default(0)->after('cost_rounding_mode');
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn(['cost_rounding_mode', 'cost_rounding_step']);
        });
    }
};
