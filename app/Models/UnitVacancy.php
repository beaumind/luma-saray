<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A period during which a unit is empty. While vacant, the unit is billed only
 * the base charge (the template's fixed component), not the per-resident part.
 * The range is [starts_on, ends_on) in Gregorian, aligned to whole Jalali months.
 */
class UnitVacancy extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'unit_id',
        'starts_on',
        'ends_on',
        'adjustments',
        'note',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'adjustments' => 'array',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
