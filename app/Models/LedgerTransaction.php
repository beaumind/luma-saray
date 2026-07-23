<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LedgerTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'building_id',
        'type',
        'amount',
        'direction',
        'transaction_date',
        'description',
        'reference_type',
        'reference_id',
        'created_by',
        'tracking_number',
    ];

    protected $casts = [
        'amount' => 'integer',
        'transaction_date' => 'date',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'charge' => 'شارژ',
            'payment' => 'پرداخت',
            'expense' => 'هزینه',
            'credit' => 'بستانکار',
            'debit' => 'بدهکار',
            'adjustment' => 'تعدیل',
            default => $this->type,
        };
    }
}
