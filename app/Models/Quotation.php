<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $fillable = [
        'project_id',
        'quotation_number',
        'description',
        'warranty_days',
        'working_duration',
        'due_date',
        'total_amount',
        'status',
        'attachment_pdf'
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Generate a unique sequential quotation number for the project.
     * Format: QUO/WIRODEV/{YEAR}/{PROJECT_REF}/{SEQUENCE}
     */
    public static function generateNextNumber(Project $project): string
    {
        $projectYear = $project->created_at ? $project->created_at->format('Y') : date('Y');
        $projectSeq = Project::whereYear('created_at', $projectYear)->where('id', '<=', $project->id)->count();
        $projectRef = str_pad($projectSeq > 0 ? $projectSeq : $project->id, 3, '0', STR_PAD_LEFT);
        $prefix = "QUO/WIRODEV/{$projectYear}/{$projectRef}/";

        // Query all existing numbers matching this prefix or belonging to this project
        $existing = self::where('quotation_number', 'LIKE', "{$prefix}%")
            ->orWhere('project_id', $project->id)
            ->pluck('quotation_number')
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
        while (self::where('quotation_number', $candidate)->exists()) {
            $nextSeq++;
            $candidate = $prefix . str_pad($nextSeq, 2, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }
}
