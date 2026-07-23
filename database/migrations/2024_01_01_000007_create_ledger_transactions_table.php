<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['charge', 'payment', 'expense', 'credit', 'debit', 'adjustment']);
            $table->decimal('amount', 15, 0);
            $table->enum('direction', ['debit', 'credit']); // debit = owes money, credit = paid money
            $table->date('transaction_date');
            $table->string('description');
            $table->string('reference_type')->nullable(); // morphable: Expense, Charge, Payment
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->string('tracking_number')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'transaction_date']);
            $table->index(['building_id', 'transaction_date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_transactions');
    }
};
