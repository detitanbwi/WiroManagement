<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareholderCapitalHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'shareholder_id',
        'quarterly_closing_id',
        'profit_distribution_id',
        'transaction_type',
        'effective_quarter_label',
        'amount',
        'opening_capital',
        'closing_capital',
        'opening_shares_count',
        'closing_shares_count',
        'opening_ownership_percentage',
        'closing_ownership_percentage',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'opening_capital' => 'decimal:2',
        'closing_capital' => 'decimal:2',
        'opening_shares_count' => 'integer',
        'closing_shares_count' => 'integer',
        'opening_ownership_percentage' => 'decimal:2',
        'closing_ownership_percentage' => 'decimal:2',
    ];

    /**
     * Shareholder associated with this ledger entry.
     */
    public function shareholder(): BelongsTo
    {
        return $this->belongsTo(Shareholder::class);
    }

    /**
     * Quarterly closing associated with this ledger entry (optional).
     */
    public function quarterlyClosing(): BelongsTo
    {
        return $this->belongsTo(QuarterlyClosing::class);
    }

    /**
     * Profit distribution / RUPS decision associated with this ledger entry (optional).
     */
    public function profitDistribution(): BelongsTo
    {
        return $this->belongsTo(ProfitDistribution::class);
    }
}
