<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'building_id',
        'number',
        'floor',
        'area',
        'bedrooms',
        'parking_count',
        'storage_count',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'floor' => 'integer',
        'bedrooms' => 'integer',
        'parking_count' => 'integer',
        'storage_count' => 'integer',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class);
    }

    public function activeResidents(): HasMany
    {
        return $this->hasMany(Resident::class)->where('is_active', true);
    }

    public function owner(): HasMany
    {
        return $this->hasMany(Resident::class)->where('type', 'owner')->where('is_active', true);
    }

    public function tenant(): HasMany
    {
        return $this->hasMany(Resident::class)->where('type', 'tenant')->where('is_active', true);
    }

    public function ledgerTransactions(): HasMany
    {
        return $this->hasMany(LedgerTransaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Debt owed to the fund: charge/cost debits minus settlement payments.
     * Standalone creditor balances are tracked separately (see creditBalance)
     * and only reduce debt when explicitly applied.
     */
    public function getBalanceAttribute(): int
    {
        $debits = $this->ledgerTransactions()->where('direction', 'debit')
            ->whereIn('type', ['charge', 'cost', 'expense'])->sum('amount');
        $payments = $this->ledgerTransactions()->where('direction', 'credit')
            ->where('type', 'payment')->sum('amount');

        return (int) ($debits - $payments);
    }

    /**
     * Standing credit the fund owes this unit (money it fronted), not yet
     * applied to its debt.
     */
    public function getCreditBalanceAttribute(): int
    {
        $credits = $this->ledgerTransactions()->where('direction', 'credit')
            ->where('type', 'credit')->sum('amount');
        $used = $this->ledgerTransactions()->where('direction', 'debit')
            ->where('type', 'credit_used')->sum('amount');

        return (int) ($credits - $used);
    }

    public function getActiveResidentCountAttribute(): int
    {
        return $this->activeResidents()->sum('resident_count');
    }
}
