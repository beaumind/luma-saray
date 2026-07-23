<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargeTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
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

    public function calculateForUnit(Unit $unit): int
    {
        $residentCount = $unit->active_resident_count ?: 1;

        return match ($this->type) {
            'fixed' => $this->fixed_amount,
            'per_resident' => $this->per_resident_amount * $residentCount,
            'combined' => $this->fixed_amount + ($this->per_resident_amount * $residentCount),
            default => $this->fixed_amount,
        };
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
