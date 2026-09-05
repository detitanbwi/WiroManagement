<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestCase extends Model
{
    protected $guarded = [];

    protected $casts = [
        'steps' => 'array',
    ];

    public function projectTask()
    {
        return $this->belongsTo(ProjectTask::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function bugs()
    {
        return $this->hasMany(TaskBug::class);
    }

    public function parent()
    {
        return $this->belongsTo(TestCase::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(TestCase::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }
}
