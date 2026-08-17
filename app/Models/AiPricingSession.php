<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiPricingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'client_name',
        'client_segment',
        'platform',
        'risk_buffer_percent',
        'rush_fee_percent',
        'selected_modules',
        'unlisted_features',
        'calculation_result',
        'status',
    ];

    protected $casts = [
        'selected_modules' => 'array',
        'unlisted_features' => 'array',
        'calculation_result' => 'array',
        'risk_buffer_percent' => 'integer',
        'rush_fee_percent' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(AiPricingMessage::class, 'session_id')->orderBy('created_at', 'asc');
    }
}
