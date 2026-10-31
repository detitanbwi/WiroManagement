<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectWorkLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'work_date',
        'end_date',
        'hours_per_day',
        'days_count',
        'date_ranges_data',
        'hours_worked',
        'role_in_project',
        'source_account_type',
        'base_rate',
        'calculated_nominal',
        'is_weekend',
        'is_overtime',
        'payment_status',
        'payment_date',
        'notes',
        'audit_notes',
    ];

    protected $casts = [
        'work_date' => 'date',
        'end_date' => 'date',
        'hours_per_day' => 'decimal:2',
        'days_count' => 'integer',
        'date_ranges_data' => 'array',
        'payment_date' => 'date',
        'hours_worked' => 'decimal:2',
        'base_rate' => 'decimal:2',
        'calculated_nominal' => 'decimal:2',
        'is_weekend' => 'boolean',
        'is_overtime' => 'boolean',
    ];

    /**
     * Get formatted date range string for display.
     */
    public function getFormattedDateRangeAttribute(): string
    {
        if ($this->end_date && $this->end_date->ne($this->work_date)) {
            return $this->work_date->format('d/m/Y') . ' - ' . $this->end_date->format('d/m/Y');
        }
        return $this->work_date ? $this->work_date->format('d/m/Y') : '-';
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to paid logs.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope to unpaid logs.
     */
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'unpaid');
    }

    /**
     * Scope to pending approval logs.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('payment_status', 'pending_approval');
    }

    /**
     * Scope by project.
     */
    public function scopeByProject(Builder $query, int|Project $project): Builder
    {
        $id = $project instanceof Project ? $project->id : $project;
        return $query->where('project_id', $id);
    }

    /**
     * Scope by period range.
     */
    public function scopePeriod(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('work_date', [$startDate, $endDate]);
    }
}
