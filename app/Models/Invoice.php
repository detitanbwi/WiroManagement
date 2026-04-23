<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'change_request_id',
        'invoice_number',
        'type',
        'subtotal',
        'discount',
        'tax',
        'total_amount',
        'due_date',
        'issued_date',
        'status',
        'notes',
        'attachment_pdf'
    ];

    protected $casts = [
        'due_date' => 'date',
        'issued_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getPaidAmountAttribute()
    {
        return $this->payments()->sum('amount');
    }

    public function getBalanceDueAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }
}
