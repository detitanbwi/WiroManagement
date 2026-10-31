<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarketingCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => 'required|string|max:50',
            'campaign_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'ad_spend' => 'required|numeric|min:0',
            'views_count' => 'nullable|integer|min:0',
            'accounts_reached' => 'nullable|integer|min:0',
            'messages_count' => 'nullable|integer|min:0',
            'meetings_count' => 'nullable|integer|min:0',
            'proposals_count' => 'nullable|integer|min:0',
            'deals_count' => 'nullable|integer|min:0',
            'total_revenue_generated' => 'nullable|numeric|min:0',
            'roi_percentage' => 'nullable|numeric',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
