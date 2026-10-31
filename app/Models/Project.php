<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'pm_id',
        'project_code',
        'title',
        'status',
        'progress_percentage',
        'start_date',
        'end_date',
        'demo_url',
        'repository_url',
        'production_url',
        'hosting_provider',
        'hosting_credentials',
        'is_credentials_visible_to_client',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'progress_percentage' => 'integer',
        'is_credentials_visible_to_client' => 'boolean',
        'hosting_credentials' => 'encrypted:array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function pm()
    {
        return $this->belongsTo(User::class, 'pm_id');
    }

    public function milestones()
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('order_index');
    }

    public function updates()
    {
        return $this->hasMany(ProjectUpdate::class)->latest();
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function changeRequests()
    {
        return $this->hasMany(ChangeRequest::class);
    }

    /**
     * Scope a query to only include projects visible to the given user.
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->whereHas('members', function ($sub) use ($user) {
                $sub->where('user_id', $user->id);
            })->orWhere('pm_id', $user->id);
        });
    }

    /**
     * Check if a specific user has permission to access the QC module of this project.
     */
    public function canUserAccessQc(User $user): bool
    {
        return $user->canAccessProjectQc($this);
    }

    public function expenses()
    {
        return $this->hasMany(ProjectExpense::class);
    }

    public function workLogs()
    {
        return $this->hasMany(ProjectWorkLog::class);
    }

    public function memberRates()
    {
        return $this->hasMany(ProjectMemberRate::class);
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function members()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user')->withTimestamps();
    }

    /**
     * Assign a user to this project with optional roles, functional position, and hourly rate.
     *
     * @param User|int $user
     * @param array<string|Role> $roles
     * @param string|null $roleInProject
     * @param float|null $ratePerHour
     * @param float|null $weekendMultiplier
     * @param float|null $overtimeMultiplier
     */
    public function assignMember(
        User|int $user,
        array $roles = [],
        ?string $roleInProject = null,
        ?float $ratePerHour = null,
        ?float $weekendMultiplier = null,
        ?float $overtimeMultiplier = null
    ): ProjectMember {
        $userId = $user instanceof User ? $user->id : $user;

        $member = ProjectMember::firstOrCreate([
            'project_id' => $this->id,
            'user_id' => $userId,
        ]);

        if ($roleInProject !== null) {
            $member->role_in_project = $roleInProject;
            $member->save();
        }

        if (!empty($roles)) {
            $member->syncRoles($roles);
        }

        if ($ratePerHour !== null && $ratePerHour > 0) {
            ProjectMemberRate::updateOrCreate(
                ['project_id' => $this->id, 'user_id' => $userId],
                [
                    'role_slug' => $roleInProject,
                    'rate_per_hour' => $ratePerHour,
                    'weekend_multiplier' => $weekendMultiplier ?: 1.10,
                    'overtime_multiplier' => $overtimeMultiplier ?: 1.20,
                ]
            );
        }

        return $member->fresh(['roles', 'memberRate', 'user']);
    }

    /**
     * Remove a member from this project.
     */
    public function removeMember(User|int $user): void
    {
        $userId = $user instanceof User ? $user->id : $user;
        $this->members()->where('user_id', $userId)->delete();
        ProjectMemberRate::where('project_id', $this->id)->where('user_id', $userId)->delete();
    }

    /**
     * Get the project member record for a given user.
     */
    public function getMember(User|int $user): ?ProjectMember
    {
        $userId = $user instanceof User ? $user->id : $user;

        if ($this->relationLoaded('members')) {
            return $this->members->firstWhere('user_id', $userId);
        }

        return $this->members()->where('user_id', $userId)->first();
    }

    /**
     * Check if a user is assigned as a member of this project.
     */
    public function hasMember(User|int $user): bool
    {
        return $this->getMember($user) !== null;
    }

    // Ledger Logic
    public function getContractValueAttribute()
    {
        $invoiceTotal = $this->invoices()->where('type', '!=', 'change_request')->sum('total_amount');
        
        if ($invoiceTotal > 0) {
            return $invoiceTotal;
        }

        return $this->quotations()->where('status', 'approved')->sum('total_amount');
    }

    public function getTotalCrValueAttribute()
    {
        return $this->invoices()->where('type', 'change_request')->sum('total_amount');
    }

    public function getGrandTotalAttribute()
    {
        return $this->contract_value + $this->total_cr_value;
    }

    public function getPaidAmountAttribute()
    {
        return Payment::whereIn('invoice_id', $this->invoices()->pluck('id'))->sum('amount');
    }

    public function getBalanceDueAttribute()
    {
        return $this->grand_total - $this->paid_amount;
    }

    public function getTotalExpensesAttribute()
    {
        return $this->expenses()->sum('amount');
    }

    public function getNettAttribute()
    {
        return $this->grand_total - $this->total_expenses;
    }

    /**
     * Total Labor Cost (sum of work logs nominal).
     */
    public function totalLaborCost(bool $paidOnly = true): float
    {
        $query = $this->workLogs();
        if ($paidOnly) {
            $query->where('payment_status', 'paid');
        }
        return (float) $query->sum('calculated_nominal');
    }

    /**
     * Total Infrastructure and direct operational cost (from project_expenses).
     */
    public function totalInfraCost(bool $paidOnly = true): float
    {
        $query = $this->expenses();
        if ($paidOnly) {
            $query->where('is_paid', true);
        }
        return (float) $query->sum('amount');
    }

    /**
     * Cost of Goods Sold (COGS) = Labor + Infrastructure/Direct Expenses.
     */
    public function cogs(bool $paidOnly = true): float
    {
        return $this->totalLaborCost($paidOnly) + $this->totalInfraCost($paidOnly);
    }

    /**
     * Gross Margin Percentage based on Grand Total.
     */
    public function grossMargin(bool $paidOnly = true): float
    {
        $revenue = (float) $this->grand_total;
        if ($revenue <= 0) {
            return 0.0;
        }

        $cogs = $this->cogs($paidOnly);
        return round((($revenue - $cogs) / $revenue) * 100, 2);
    }

    /**
     * Get or determine the unique 3-digit project reference code for this project.
     * e.g. '001', '012', '013'
     */
    public function getProjectRef(): string
    {
        $year = $this->created_at ? $this->created_at->format('Y') : date('Y');

        // 1. If project has existing quotation with standard format, reuse that ref
        $quo = $this->quotations()->where('quotation_number', 'LIKE', "%/WIRODEV/{$year}/%")->first();
        if ($quo && preg_match('/\/WIRODEV\/\d{4}\/(\d{3})\//', $quo->quotation_number, $m)) {
            return $m[1];
        }

        // 2. If project has existing invoice with standard format, reuse that ref
        $inv = $this->invoices()->where('invoice_number', 'LIKE', "%/WIRODEV/{$year}/%")->first();
        if ($inv && preg_match('/\/WIRODEV\/\d{4}\/(\d{3})\//', $inv->invoice_number, $m)) {
            return $m[1];
        }

        // 3. Find the highest project ref allocated for this year across all existing docs
        $maxRef = 0;
        $lastDocProjectId = 0;

        $allQuos = Quotation::where('quotation_number', 'LIKE', "%/WIRODEV/{$year}/%")->get();
        foreach ($allQuos as $q) {
            if (preg_match('/\/WIRODEV\/\d{4}\/(\d{3})\//', $q->quotation_number, $m)) {
                $ref = (int)$m[1];
                if ($ref > $maxRef) {
                    $maxRef = $ref;
                }
                if ($q->project_id > $lastDocProjectId) {
                    $lastDocProjectId = $q->project_id;
                }
            }
        }

        $allInvs = Invoice::where('invoice_number', 'LIKE', "%/WIRODEV/{$year}/%")->get();
        foreach ($allInvs as $i) {
            if (preg_match('/\/WIRODEV\/\d{4}\/(\d{3})\//', $i->invoice_number, $m)) {
                $ref = (int)$m[1];
                if ($ref > $maxRef) {
                    $maxRef = $ref;
                }
                if ($i->project_id > $lastDocProjectId) {
                    $lastDocProjectId = $i->project_id;
                }
            }
        }

        if ($maxRef > 0 && $lastDocProjectId > 0 && $this->id > $lastDocProjectId) {
            $offset = self::whereYear('created_at', $year)
                ->where('id', '>', $lastDocProjectId)
                ->where('id', '<=', $this->id)
                ->count();
            $candidateRef = $maxRef + $offset;
        } else {
            $candidateRef = self::whereYear('created_at', $year)
                ->where('id', '<=', $this->id)
                ->count();
        }

        return str_pad(max(1, $candidateRef), 3, '0', STR_PAD_LEFT);
    }
}
