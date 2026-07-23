<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('title');
            $table->decimal('amount', 15, 0);
            $table->date('expense_date');
            $table->text('description')->nullable();
            $table->enum('distribution', ['all_units', 'selected_units'])->default('all_units');
            $table->enum('responsible', ['owner', 'tenant', 'both'])->default('owner');
            $table->json('attachments')->nullable();
            $table->timestamps();
        });

        Schema::create('expense_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_units');
        Schema::dropIfExists('expenses');
    }
};
