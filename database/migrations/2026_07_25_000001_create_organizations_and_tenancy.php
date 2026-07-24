<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that belong to a tenant (organization) and need an organization_id.
     */
    private array $tenantTables = [
        'users',
        'buildings',
        'units',
        'residents',
        'charge_templates',
        'expense_categories',
        'expenses',
        'expense_units',
        'ledger_transactions',
        'payments',
    ];

    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        foreach ($this->tenantTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('organization_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('organizations')
                    ->cascadeOnDelete();
            });
        }

        $this->backfillExistingData();
    }

    /**
     * Adopt any pre-existing rows into a single default organization so the
     * tenant global scope does not orphan legacy data on live databases.
     */
    private function backfillExistingData(): void
    {
        if (DB::table('users')->count() === 0) {
            return;
        }

        $organizationId = DB::table('organizations')->insertGetId([
            'name' => 'مجموعه اصلی',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($this->tenantTables as $tableName) {
            DB::table($tableName)
                ->whereNull('organization_id')
                ->update(['organization_id' => $organizationId]);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tenantTables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['organization_id']);
                $table->dropColumn('organization_id');
            });
        }

        Schema::dropIfExists('organizations');
    }
};
