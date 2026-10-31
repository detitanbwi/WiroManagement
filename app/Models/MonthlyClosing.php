<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_month',
        'opening_cash_balance',
        'total_revenue',
        'total_cogs',
        'gross_profit',
        'total_opex',
        'net_profit',
        'net_profit_margin',
        'cash_inflow',
        'cash_outflow',
        'ending_cash_balance',
        'audit_status',
        'auditor_notes',
        'audited_by',
        'approved_by',
    ];

    protected $casts = [
        'opening_cash_balance' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'total_cogs' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'total_opex' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'net_profit_margin' => 'decimal:2',
        'cash_inflow' => 'decimal:2',
        'cash_outflow' => 'decimal:2',
        'ending_cash_balance' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MonthlyClosingItem::class, 'closing_id');
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'audited_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Virtual attribute: COGS Ratio % of Revenue.
     */
    protected function cogsRatio(): Attribute
    {
        return Attribute::make(
            get: function () {
                $rev = (float)$this->total_revenue;
                if ($rev > 0) {
                    return round(((float)$this->total_cogs / $rev) * 100, 2);
                }
                return 0.0;
            }
        );
    }

    /**
     * Virtual attribute: OPEX Ratio % of Revenue.
     */
    protected function opexRatio(): Attribute
    {
        return Attribute::make(
            get: function () {
                $rev = (float)$this->total_revenue;
                if ($rev > 0) {
                    return round(((float)$this->total_opex / $rev) * 100, 2);
                }
                return 0.0;
            }
        );
    }

    /**
     * Check if cash flow formula reconciles precisely.
     */
    public function isReconciled(): bool
    {
        $expectedEnding = (float)$this->opening_cash_balance + (float)$this->cash_inflow - (float)$this->cash_outflow;
        return abs($expectedEnding - (float)$this->ending_cash_balance) < 0.01;
    }
}
