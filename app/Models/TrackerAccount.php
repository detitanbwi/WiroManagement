<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TrackerAccount extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'type',
        'balance',
    ];

    public function expenses()
    {
        return $this->hasMany(TrackerExpense::class, 'account_id');
    }
}
