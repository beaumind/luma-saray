<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Multiple receipt files (images/PDF). The legacy single `receipt_path`
            // is kept for already-stored payments and shown as a fallback.
            $table->json('attachments')->nullable()->after('receipt_path');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });
    }
};
