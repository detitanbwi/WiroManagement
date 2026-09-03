<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskBug extends Model
{
    protected $guarded = [];

    public function projectTask()
    {
        return $this->belongsTo(ProjectTask::class);
    }

    public function testCase()
    {
        return $this->belongsTo(TestCase::class);
    }
}
