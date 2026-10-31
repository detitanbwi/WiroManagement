<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTask extends Model
{
    protected $guarded = [];

    protected $attributes = [
        'type' => 'gantt',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
        'progress' => 'float',
        'duration_days' => 'integer',
        'sort_order' => 'integer',
    ];

    public function scopeGantt($query)
    {
        return $query->where('type', 'gantt');
    }

    public function scopeQc($query)
    {
        return $query->where('type', 'qc');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function parent()
    {
        return $this->belongsTo(ProjectTask::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProjectTask::class, 'parent_id')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc');
    }

    public function dependencies()
    {
        return $this->belongsToMany(
            ProjectTask::class,
            'project_task_dependencies',
            'successor_id',
            'predecessor_id'
        )->withPivot('project_id', 'type', 'lag_days')->withTimestamps();
    }

    public function successors()
    {
        return $this->belongsToMany(
            ProjectTask::class,
            'project_task_dependencies',
            'predecessor_id',
            'successor_id'
        )->withPivot('project_id', 'type', 'lag_days')->withTimestamps();
    }

    public function isParent(): bool
    {
        return $this->children()->exists();
    }

    public function testCases()
    {
        return $this->hasMany(TestCase::class);
    }

    public function bugs()
    {
        return $this->hasMany(TaskBug::class);
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class)->orderBy('created_at', 'asc');
    }
}
