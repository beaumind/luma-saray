<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Widen the enum to a string so cost / credit_used / future ledger
        // types are accepted (previously enum without them).
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE ledger_transactions MODIFY type VARCHAR(30) NOT NULL');
        } else {
            Schema::table('ledger_transactions', function (Blueprint $table) {
                $table->string('type', 30)->change();
            });
        }
    }

    public function down(): void
    {
        // No-op: reverting to the narrow enum would lose data.
    }
};
