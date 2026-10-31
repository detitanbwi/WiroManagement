<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreMarketingCampaignRequest;
use App\Http\Requests\Finance\UpdateMarketingCampaignRequest;
use App\Models\MarketingCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketingCampaignController extends Controller
{
    /**
     * Display marketing campaigns, funnel metrics, and ROI analysis.
     */
    public function index(Request $request): View
    {
        $query = MarketingCampaign::latest('start_date');

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_date', [$request->start_date, $request->end_date]);
        }

        // Funnel Aggregates across matching campaigns
        $aggregateQuery = clone $query;
        $totalSpend = (float)(clone $aggregateQuery)->sum('ad_spend');
        $totalViews = (int)(clone $aggregateQuery)->sum('views_count');
        $totalReach = (int)(clone $aggregateQuery)->sum('accounts_reached');
        $totalMessages = (int)(clone $aggregateQuery)->sum('messages_count');
        $totalMeetings = (int)(clone $aggregateQuery)->sum('meetings_count');
        $totalProposals = (int)(clone $aggregateQuery)->sum('proposals_count');
        $totalDeals = (int)(clone $aggregateQuery)->sum('deals_count');
        $totalRevenue = (float)(clone $aggregateQuery)->sum('total_revenue_generated');

        $blendedCostPerLead = $totalMessages > 0 ? round($totalSpend / $totalMessages, 2) : 0.0;
        $blendedCostPerDeal = $totalDeals > 0 ? round($totalSpend / $totalDeals, 2) : 0.0;
        $blendedConversionRate = $totalProposals > 0 ? round(($totalDeals / $totalProposals) * 100, 2) : 0.0;
        $blendedRoi = $totalSpend > 0 ? round((($totalRevenue - $totalSpend) / $totalSpend) * 100, 2) : 0.0;

        $campaigns = $query->paginate(15)->withQueryString();

        $availablePlatforms = MarketingCampaign::distinct()->pluck('platform')->filter()->values()->toArray();
        if (empty($availablePlatforms)) {
            $availablePlatforms = ['meta_ads', 'google_ads', 'tiktok_ads', 'linkedin_ads', 'instagram_ads'];
        }

        return view('finance.marketing.index', compact(
            'campaigns',
            'availablePlatforms',
            'totalSpend',
            'totalViews',
            'totalReach',
            'totalMessages',
            'totalMeetings',
            'totalProposals',
            'totalDeals',
            'totalRevenue',
            'blendedCostPerLead',
            'blendedCostPerDeal',
            'blendedConversionRate',
            'blendedRoi'
        ));
    }

    /**
     * Store a newly created marketing campaign.
     */
    public function store(StoreMarketingCampaignRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Calculate ROI if not manually specified
        if (!isset($data['roi_percentage']) || $data['roi_percentage'] === null) {
            $spend = (float)$data['ad_spend'];
            $rev = isset($data['total_revenue_generated']) ? (float)$data['total_revenue_generated'] : 0.0;
            $data['roi_percentage'] = $spend > 0 ? round((($rev - $spend) / $spend) * 100, 2) : 0.0;
        }

        MarketingCampaign::create($data);

        return redirect()->route('finance.marketing.index')
            ->with('success', 'Kampanye pemasaran berhasil ditambahkan.');
    }

    /**
     * Update an existing marketing campaign.
     */
    public function update(UpdateMarketingCampaignRequest $request, MarketingCampaign $marketing): RedirectResponse
    {
        $data = $request->validated();

        if (!isset($data['roi_percentage']) || $data['roi_percentage'] === null) {
            $spend = (float)$data['ad_spend'];
            $rev = isset($data['total_revenue_generated']) ? (float)$data['total_revenue_generated'] : 0.0;
            $data['roi_percentage'] = $spend > 0 ? round((($rev - $spend) / $spend) * 100, 2) : 0.0;
        }

        $marketing->update($data);

        return redirect()->route('finance.marketing.index')
            ->with('success', 'Kampanye pemasaran berhasil diperbarui.');
    }

    /**
     * Delete a marketing campaign.
     */
    public function destroy(MarketingCampaign $marketing): RedirectResponse
    {
        $marketing->delete();

        return redirect()->route('finance.marketing.index')
            ->with('success', 'Kampanye pemasaran berhasil dihapus.');
    }
}
