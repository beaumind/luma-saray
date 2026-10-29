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
        'opening_balance',
        'cost_rounding_mode',
        'cost_rounding_step',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_units' => 'integer',
        'floors' => 'integer',
        'opening_balance' => 'integer',
        'cost_rounding_step' => 'integer',
    ];

    /**
     * Round one unit's cost share (in rial) per this building's setting.
     * Rounds up/down to the configured step; returns the amount unchanged when
     * rounding is off or the step is zero.
     */
    public function roundShare(int|float $rial): int
    {
        $step = (int) $this->cost_rounding_step;
        if ($step <= 0 || ! in_array($this->cost_rounding_mode, ['up', 'down'], true)) {
            return (int) round($rial);
        }

        return (int) ($this->cost_rounding_mode === 'up'
            ? ceil($rial / $step) * $step
            : floor($rial / $step) * $step);
    }

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
