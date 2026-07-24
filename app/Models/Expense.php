<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'building_id',
        'expense_category_id',
        'created_by',
        'title',
        'amount',
        'expense_date',
        'description',
        'distribution',
        'responsible',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
        'expense_date' => 'date',
        'amount' => 'integer',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'expense_units')->withPivot('amount')->withTimestamps();
    }

    public function expenseUnits(): HasMany
    {
        return $this->hasMany(ExpenseUnit::class);
    }
}
