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
        'total_amount',
        'status',
        'attachment_pdf'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
