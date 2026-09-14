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
        $projectSeq = Project::whereYear('created_at', $projectYear)->where('id', '<=', $project->id)->count();
        $projectRef = str_pad($projectSeq > 0 ? $projectSeq : $project->id, 3, '0', STR_PAD_LEFT);
        $prefix = "INV/WIRODEV/{$projectYear}/{$projectRef}/";

        // Query all existing numbers matching this prefix or belonging to this project
        $existing = self::where('invoice_number', 'LIKE', "{$prefix}%")
            ->orWhere('project_id', $project->id)
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

        $nextSeq = max($maxSeq + 1, self::where('project_id', $project->id)->count() + 1);
        $candidate = $prefix . str_pad($nextSeq, 2, '0', STR_PAD_LEFT);

        // Ensure candidate is globally unique in the table
        while (self::where('invoice_number', $candidate)->exists()) {
            $nextSeq++;
            $candidate = $prefix . str_pad($nextSeq, 2, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }
}
