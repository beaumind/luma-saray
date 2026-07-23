<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
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

    public function getBalanceAttribute(): int
    {
        $debits = $this->ledgerTransactions()->where('direction', 'debit')->sum('amount');
        $credits = $this->ledgerTransactions()->where('direction', 'credit')->sum('amount');

        return (int) ($debits - $credits);
    }

    public function getActiveResidentCountAttribute(): int
    {
        return $this->activeResidents()->sum('resident_count');
    }
}
