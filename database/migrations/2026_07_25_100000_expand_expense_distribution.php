<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Widen the enum to a string so "fund" (paid from building fund) and
        // any future distribution modes are accepted.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE expenses MODIFY distribution VARCHAR(20) NOT NULL DEFAULT 'all_units'");
        } else {
            Schema::table('expenses', function (Blueprint $table) {
                $table->string('distribution', 20)->default('all_units')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE expenses MODIFY distribution ENUM('all_units','selected_units') NOT NULL DEFAULT 'all_units'");
        }
    }
};
