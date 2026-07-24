<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'address',
        'city',
        'total_units',
        'floors',
        'description',
        'manager_name',
        'manager_mobile',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_units' => 'integer',
        'floors' => 'integer',
    ];

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function chargeTemplates(): HasMany
    {
        return $this->hasMany(ChargeTemplate::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function ledgerTransactions(): HasMany
    {
        return $this->hasMany(LedgerTransaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
