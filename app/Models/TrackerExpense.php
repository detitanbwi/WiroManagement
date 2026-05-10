<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TrackerExpense extends Model
{
    use HasUuids;

    protected $fillable = [
        'id', // ID set from frontend UUID
        'account_id',
        'category_id',
        'type',
        'amount',
        'description',
        'expense_date',
    ];

    public function account()
    {
        return $this->belongsTo(TrackerAccount::class, 'account_id');
    }

    public function category()
    {
        return $this->belongsTo(TrackerCategory::class, 'category_id');
    }
}
