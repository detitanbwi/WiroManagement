<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shareholder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'shares_count',
        'capital_amount',
        'ownership_percentage',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'is_active',
    ];

    protected $casts = [
        'shares_count' => 'integer',
        'capital_amount' => 'decimal:2',
        'ownership_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Linked internal user account (optional).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Dividend payouts received by this shareholder.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(DividendPayout::class);
    }

    /**
     * Capital mutation histories (buku besar mutasi modal).
     */
    public function capitalHistories(): HasMany
    {
        return $this->hasMany(ShareholderCapitalHistory::class)->orderBy('id', 'desc');
    }
}
