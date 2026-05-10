<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TrackerCategory extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'type',
        'icon',
    ];

    public function expenses()
    {
        return $this->hasMany(TrackerExpense::class, 'category_id');
    }
}
