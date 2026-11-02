<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class QuarterlyClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'quarter',
        'period_label',
        'total_revenue',
        'total_cogs',
        'gross_profit',
        'total_opex',
        'net_profit',
        'net_profit_margin',
        'opening_cash_balance',
        'ending_cash_balance',
        'cash_floor_target',
        'cash_surplus_above_floor',
        'audit_status',
        'auditor_notes',
        'approved_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'quarter' => 'integer',
        'total_revenue' => 'decimal:2',
        'total_cogs' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'total_opex' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'net_profit_margin' => 'decimal:2',
        'opening_cash_balance' => 'decimal:2',
        'ending_cash_balance' => 'decimal:2',
        'cash_floor_target' => 'decimal:2',
        'cash_surplus_above_floor' => 'decimal:2',
    ];

    /**
     * Approver / Auditor User.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Associated RUPS Profit Distribution record.
     */
    public function profitDistribution(): HasOne
    {
        return $this->hasOne(ProfitDistribution::class);
    }

    /**
     * Capital mutation histories tied to this quarterly closing.
     */
    public function capitalHistories(): HasMany
    {
        return $this->hasMany(ShareholderCapitalHistory::class);
    }

    /**
     * Get list of month strings for this quarter (e.g. ['2026-01', '2026-02', '2026-03']).
     *
     * @return array<int, string>
     */
    public function getQuarterMonthsAttribute(): array
    {
        $startMonth = ($this->quarter - 1) * 3 + 1;
        return [
            sprintf('%04d-%02d', $this->year, $startMonth),
            sprintf('%04d-%02d', $this->year, $startMonth + 1),
            sprintf('%04d-%02d', $this->year, $startMonth + 2),
        ];
    }

    /**
     * Check if ending cash meets the required safety cash floor (Rp 15.000.000).
     */
    public function isCashFloorMet(): bool
    {
        return (float)$this->ending_cash_balance >= (float)$this->cash_floor_target;
    }

    /**
     * Safe cash ceiling available above the safety floor.
     */
    public function safeDistributableCeiling(): float
    {
        return max(0.0, (float)$this->ending_cash_balance - (float)$this->cash_floor_target);
    }

    /**
     * Get underlying MonthlyClosing records for this quarter.
     */
    public function monthlyClosings(): Collection
    {
        return MonthlyClosing::whereIn('period_month', $this->quarter_months)
            ->orderBy('period_month', 'asc')
            ->get();
    }
}
