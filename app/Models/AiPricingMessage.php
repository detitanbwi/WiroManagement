<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiPricingMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'role',
        'content',
        'extracted_params',
    ];

    protected $casts = [
        'extracted_params' => 'array',
    ];

    public function session()
    {
        return $this->belongsTo(AiPricingSession::class, 'session_id');
    }
}
