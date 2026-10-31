<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedExpense extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'fixed_expenses';

    protected $fillable = [
        'name',
        'category',
        'amount',
        'due_day_of_month',
        'payment_method',
        'vendor',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_day_of_month' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get all realizations for this fixed expense.
     */
    public function realizations(): HasMany
    {
        return $this->hasMany(FixedExpenseRealization::class, 'fixed_expense_id');
    }
}
