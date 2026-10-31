<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform',
        'campaign_name',
        'start_date',
        'end_date',
        'ad_spend',
        'views_count',
        'accounts_reached',
        'messages_count',
        'meetings_count',
        'proposals_count',
        'deals_count',
        'total_revenue_generated',
        'roi_percentage',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'ad_spend' => 'decimal:2',
        'views_count' => 'integer',
        'accounts_reached' => 'integer',
        'messages_count' => 'integer',
        'meetings_count' => 'integer',
        'proposals_count' => 'integer',
        'deals_count' => 'integer',
        'total_revenue_generated' => 'decimal:2',
        'roi_percentage' => 'decimal:2',
    ];

    /**
     * Virtual attribute: Cost per lead (Messages / Inquiries).
     */
    protected function costPerLead(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->messages_count > 0) {
                    return round((float)$this->ad_spend / $this->messages_count, 2);
                }
                return 0.0;
            }
        );
    }

    /**
     * Virtual attribute: Deal conversion rate (% of proposals won).
     */
    protected function dealConversionRate(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->proposals_count > 0) {
                    return round(((float)$this->deals_count / $this->proposals_count) * 100, 2);
                }
                return 0.0;
            }
        );
    }

    /**
     * Virtual attribute: Cost per closed deal.
     */
    protected function costPerDeal(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->deals_count > 0) {
                    return round((float)$this->ad_spend / $this->deals_count, 2);
                }
                return 0.0;
            }
        );
    }

    /**
     * Virtual attribute: Real-time calculated ROI percentage.
     */
    protected function calculatedRoi(): Attribute
    {
        return Attribute::make(
            get: function () {
                $spend = (float)$this->ad_spend;
                if ($spend > 0) {
                    $rev = (float)$this->total_revenue_generated;
                    return round((($rev - $spend) / $spend) * 100, 2);
                }
                return 0.0;
            }
        );
    }
}
