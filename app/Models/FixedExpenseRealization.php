<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedExpenseRealization extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'fixed_expense_realizations';

    protected $fillable = [
        'fixed_expense_id',
        'month_period',
        'paid_amount',
        'payment_date',
        'payment_status',
        'receipt_url',
        'proof_of_payment',
        'notes',
    ];

    protected $casts = [
        'paid_amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    /**
     * Parent fixed expense definition.
     */
    public function fixedExpense(): BelongsTo
    {
        return $this->belongsTo(FixedExpense::class, 'fixed_expense_id');
    }

    /**
     * Getter fallback for proof_of_payment.
     */
    public function getProofOfPaymentAttribute($value): ?string
    {
        return $value ?? $this->attributes['receipt_url'] ?? null;
    }

    /**
     * Getter fallback for receipt_url.
     */
    public function getReceiptUrlAttribute($value): ?string
    {
        return $value ?? $this->attributes['proof_of_payment'] ?? null;
    }

    /**
     * Setter for receipt_url (syncs proof_of_payment).
     */
    public function setReceiptUrlAttribute($value): void
    {
        $this->attributes['receipt_url'] = $value;
        if (!isset($this->attributes['proof_of_payment']) || $this->attributes['proof_of_payment'] === null) {
            $this->attributes['proof_of_payment'] = $value;
        }
    }

    /**
     * Setter for proof_of_payment (syncs receipt_url).
     */
    public function setProofOfPaymentAttribute($value): void
    {
        $this->attributes['proof_of_payment'] = $value;
        if (!isset($this->attributes['receipt_url']) || $this->attributes['receipt_url'] === null) {
            $this->attributes['receipt_url'] = $value;
        }
    }
}
