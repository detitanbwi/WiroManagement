<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'description',
        'amount',
        'is_paid',
        'date'
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'date' => 'date'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
