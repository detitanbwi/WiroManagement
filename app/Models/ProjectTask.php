<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTask extends Model
{
    protected $guarded = [];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function testCases()
    {
        return $this->hasMany(TestCase::class);
    }

    public function bugs()
    {
        return $this->hasMany(TaskBug::class);
    }
}
