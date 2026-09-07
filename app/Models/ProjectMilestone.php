<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'status',
        'due_date',
        'order_index',
    ];

    protected $casts = [
        'due_date' => 'date',
        'order_index' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
