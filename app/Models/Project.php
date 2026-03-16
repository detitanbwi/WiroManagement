<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'title',
        'status',
        'start_date',
        'end_date'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function changeRequests()
    {
        return $this->hasMany(ChangeRequest::class);
    }

    // Ledger Logic
    public function getContractValueAttribute()
    {
        return $this->quotations()->where('status', 'approved')->sum('total_amount');
    }

    public function getTotalCrValueAttribute()
    {
        return $this->invoices()->where('type', 'change_request')->sum('total_amount');
    }

    public function getGrandTotalAttribute()
    {
        return $this->contract_value + $this->total_cr_value;
    }

    public function getPaidAmountAttribute()
    {
        return Payment::whereIn('invoice_id', $this->invoices()->pluck('id'))->sum('amount');
    }

    public function getBalanceDueAttribute()
    {
        return $this->grand_total - $this->paid_amount;
    }
}
