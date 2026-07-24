<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseUnit extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'expense_id', 'unit_id', 'amount'];

    protected $casts = ['amount' => 'integer'];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
