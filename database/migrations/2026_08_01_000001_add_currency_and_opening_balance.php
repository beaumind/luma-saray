<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Display currency: 'toman' or 'rial'. Amounts are always STORED in rial.
            $table->string('currency', 10)->default('toman')->after('slug');
        });

        Schema::table('buildings', function (Blueprint $table) {
            // Opening fund balance in rial (the reserve at the start of records).
            $table->bigInteger('opening_balance')->default(0)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', fn (Blueprint $table) => $table->dropColumn('currency'));
        Schema::table('buildings', fn (Blueprint $table) => $table->dropColumn('opening_balance'));
    }
};
