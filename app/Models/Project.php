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
        'end_date'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'progress_percentage' => 'integer',
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
     * Assign a user to this project with one or more roles.
     *
     * @param User|int $user
     * @param array<string|Role> $roles
     */
    public function assignMember(User|int $user, array $roles): ProjectMember
    {
        $userId = $user instanceof User ? $user->id : $user;

        $member = ProjectMember::firstOrCreate([
            'project_id' => $this->id,
            'user_id' => $userId,
        ]);

        $member->syncRoles($roles);
        return $member;
    }

    /**
     * Remove a member from this project.
     */
    public function removeMember(User|int $user): void
    {
        $userId = $user instanceof User ? $user->id : $user;
        $this->members()->where('user_id', $userId)->delete();
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
