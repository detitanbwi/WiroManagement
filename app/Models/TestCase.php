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

    public function bugs()
    {
        return $this->hasMany(TaskBug::class);
    }
}
