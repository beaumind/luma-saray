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

    /** Rounding modes that actually snap the share to the step. */
    public const ROUNDING_MODES = ['nearest', 'up', 'down'];

    /**
     * Round one unit's cost share (in rial) to the configured step:
     *  - nearest: to the closest step (up or down depending on the amount)
     *  - up / down: always toward the next / previous step
     * Returns the amount unchanged when rounding is off or the step is zero.
     */
    public function roundShare(int|float $rial): int
    {
        $step = (int) $this->cost_rounding_step;
        if ($step <= 0 || ! in_array($this->cost_rounding_mode, self::ROUNDING_MODES, true)) {
            return (int) round($rial);
        }

        $steps = match ($this->cost_rounding_mode) {
            'up' => ceil($rial / $step),
            'down' => floor($rial / $step),
            default => round($rial / $step), // nearest
        };

        return (int) ($steps * $step);
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
