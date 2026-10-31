<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManHourRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_slug',
        'skill_level',
        'base_rate_per_hour',
        'weekend_multiplier',
        'overtime_multiplier',
        'description',
    ];

    protected $casts = [
        'base_rate_per_hour' => 'decimal:2',
        'weekend_multiplier' => 'decimal:2',
        'overtime_multiplier' => 'decimal:2',
    ];
}
