<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->unsignedSmallInteger('floor')->default(1);
            $table->decimal('area', 8, 2)->nullable();
            $table->unsignedTinyInteger('bedrooms')->default(1);
            $table->unsignedTinyInteger('parking_count')->default(0);
            $table->unsignedTinyInteger('storage_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['building_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
