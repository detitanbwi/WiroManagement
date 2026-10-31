<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMemberRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'role_slug',
        'rate_per_hour',
        'weekend_multiplier',
        'overtime_multiplier',
        'notes',
    ];

    protected $casts = [
        'rate_per_hour' => 'decimal:2',
        'weekend_multiplier' => 'decimal:2',
        'overtime_multiplier' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
