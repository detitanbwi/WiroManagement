<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfitDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'quarterly_closing_id',
        'document_number',
        'total_net_profit',
        'retained_earnings_amount',
        'retained_earnings_percentage',
        'distributable_profit',
        'total_cash_payout',
        'total_reinvested_capital',
        'next_quarter_total_capital',
        'dividend_per_share',
        'distribution_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_net_profit' => 'decimal:2',
        'retained_earnings_amount' => 'decimal:2',
        'retained_earnings_percentage' => 'decimal:2',
        'distributable_profit' => 'decimal:2',
        'total_cash_payout' => 'decimal:2',
        'total_reinvested_capital' => 'decimal:2',
        'next_quarter_total_capital' => 'decimal:2',
        'dividend_per_share' => 'decimal:2',
        'distribution_date' => 'date',
    ];

    /**
     * Parent Quarterly Closing statement.
     */
    public function quarterlyClosing(): BelongsTo
    {
        return $this->belongsTo(QuarterlyClosing::class);
    }

    /**
     * Individual shareholder dividend payouts.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(DividendPayout::class);
    }

    /**
     * Capital mutation histories resulting from this distribution.
     */
    public function capitalHistories(): HasMany
    {
        return $this->hasMany(ShareholderCapitalHistory::class);
    }

    /**
     * Total payout amount already transferred.
     */
    public function totalTransferred(): float
    {
        return (float)$this->payouts()->where('payment_status', 'transferred')->sum('payout_amount');
    }

    /**
     * Check if all payouts have been successfully transferred.
     */
    public function isFullyTransferred(): bool
    {
        $totalCount = $this->payouts()->count();
        if ($totalCount === 0) {
            return false;
        }
        return $this->payouts()->where('payment_status', 'transferred')->count() === $totalCount;
    }
}
