<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DividendPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'profit_distribution_id',
        'shareholder_id',
        'shares_count',
        'ownership_percentage',
        'payout_amount',
        'cash_amount',
        'reinvest_amount',
        'reinvest_percentage',
        'payment_status',
        'payment_date',
        'transfer_receipt_url',
        'notes',
    ];

    protected $casts = [
        'shares_count' => 'integer',
        'ownership_percentage' => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'cash_amount' => 'decimal:2',
        'reinvest_amount' => 'decimal:2',
        'reinvest_percentage' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Parent Profit Distribution.
     */
    public function profitDistribution(): BelongsTo
    {
        return $this->belongsTo(ProfitDistribution::class);
    }

    /**
     * Shareholder receiving this payout.
     */
    public function shareholder(): BelongsTo
    {
        return $this->belongsTo(Shareholder::class);
    }
}
