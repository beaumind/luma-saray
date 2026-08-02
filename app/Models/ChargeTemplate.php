<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargeTemplate extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'building_id',
        'title',
        'type',
        'period',
        'fixed_amount',
        'per_resident_amount',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'fixed_amount' => 'integer',
        'per_resident_amount' => 'integer',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * The charge for a unit. When $chargeDate falls inside a vacancy period the
     * unit is billed only the base (fixed) component — the per-resident part is
     * dropped, since no one is living there.
     */
    public function calculateForUnit(Unit $unit, ?string $chargeDate = null): int
    {
        if ($chargeDate !== null && $unit->isVacantOn($chargeDate)) {
            return $this->baseAmount();
        }

        $residentCount = $unit->active_resident_count ?: 1;

        return match ($this->type) {
            'fixed' => $this->fixed_amount,
            'per_resident' => $this->per_resident_amount * $residentCount,
            'combined' => $this->fixed_amount + ($this->per_resident_amount * $residentCount),
            default => $this->fixed_amount,
        };
    }

    /** The base charge a vacant unit pays (the fixed component). */
    public function baseAmount(): int
    {
        return (int) $this->fixed_amount;
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'fixed' => 'ثابت',
            'per_resident' => 'به ازای هر نفر',
            'combined' => 'ترکیبی',
            default => $this->type,
        };
    }

    public function getPeriodLabel(): string
    {
        return match ($this->period) {
            'monthly' => 'ماهانه',
            'quarterly' => 'فصلی',
            'yearly' => 'سالانه',
            default => $this->period,
        };
    }
}
