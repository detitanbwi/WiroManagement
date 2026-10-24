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

    /**
     * Generate a unique sequential invoice number for the project.
     * Format: INV/WIRODEV/{YEAR}/{PROJECT_REF}/{SEQUENCE}
     */
    public static function generateNextNumber(Project $project): string
    {
        $projectYear = $project->created_at ? $project->created_at->format('Y') : date('Y');
        $projectRef = $project->getProjectRef();
        $prefix = "INV/WIRODEV/{$projectYear}/{$projectRef}/";

        // Query existing invoices belonging to this project
        $existing = $project->invoices()
            ->pluck('invoice_number')
            ->toArray();

        $maxSeq = 0;
        foreach ($existing as $num) {
            if (preg_match('/\/(\d+)$/', $num, $matches)) {
                $seq = (int) $matches[1];
                if ($seq > $maxSeq) {
                    $maxSeq = $seq;
                }
            }
        }

        $nextSeq = $maxSeq + 1;
        $candidate = $prefix . str_pad($nextSeq, 2, '0', STR_PAD_LEFT);

        // Ensure candidate is globally unique in the table
        while (self::where('invoice_number', $candidate)->exists()) {
            $nextSeq++;
            $candidate = $prefix . str_pad($nextSeq, 2, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }
}
