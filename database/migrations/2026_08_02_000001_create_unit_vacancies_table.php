<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_vacancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            // Inclusive-start, exclusive-end Gregorian range covering whole Jalali months.
            $table->date('starts_on');
            $table->date('ends_on');
            // Snapshot of the charge debits lowered to base, so removal can restore them:
            // [[ledger_transaction_id, original_amount], ...]
            $table->json('adjustments')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_vacancies');
    }
};
