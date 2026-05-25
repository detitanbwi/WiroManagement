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
}
