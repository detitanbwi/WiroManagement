<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyClosingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'closing_id',
        'account_code',
        'account_name',
        'category', // revenue, cogs, opex, cash_mutation
        'debit',
        'credit',
        'project_id',
        'notes',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function closing(): BelongsTo
    {
        return $this->belongsTo(MonthlyClosing::class, 'closing_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
