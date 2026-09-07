<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'author_id',
        'title',
        'content',
        'is_visible_to_client',
    ];

    protected $casts = [
        'is_visible_to_client' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
