<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resident extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'type',
        'name',
        'mobile',
        'national_code',
        'resident_count',
        'move_in_date',
        'move_out_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'resident_count' => 'integer',
        'move_in_date' => 'date',
        'move_out_date' => 'date',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'owner' => 'مالک',
            'tenant' => 'مستأجر',
            default => $this->type,
        };
    }
}
