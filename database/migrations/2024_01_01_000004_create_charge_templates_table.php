<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charge_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['fixed', 'per_resident', 'combined'])->default('fixed');
            $table->enum('period', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->decimal('fixed_amount', 15, 0)->default(0);
            $table->decimal('per_resident_amount', 15, 0)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_templates');
    }
};
