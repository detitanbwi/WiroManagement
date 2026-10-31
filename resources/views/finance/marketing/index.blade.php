@extends('layouts.app')

@section('title', 'Marketing Campaigns & Funnel Analytics - Wirodayan Digital')

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" x-data="marketingManager()">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between pb-6 border-b border-gray-200 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                </svg>
                Marketing & Funnel Analytics
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Pelacakan performa iklan digital dan rasio konversi funnel (leads & deals).
            </p>
        </div>
        <div>
            <button @click="openCreateModal()" type="button" class="inline-flex items-center px-4 py-2 bg-primary text-white font-medium rounded-lg text-sm hover:bg-blue-800 shadow-sm transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Kampanye Baru
            </button>
        </div>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
    <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800 flex items-center justify-between">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800">&times;</button>
    </div>
    @endif

    <!-- Visual Conversion Funnel Progress Bar / Steps -->
    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs mb-6">
        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">Pipeline Funnel Konversi Terpadu</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
            <div class="p-3 bg-blue-50/70 rounded-xl border border-blue-100 text-center">
                <div class="text-[10px] font-bold text-gray-500 uppercase">1. Ad Spend</div>
                <div class="text-sm font-black text-primary mt-1">Rp {{ number_format($totalSpend, 0, ',', '.') }}</div>
                <div class="text-[10px] text-gray-400 mt-0.5">Biaya Iklan</div>
            </div>

            <div class="p-3 bg-indigo-50/70 rounded-xl border border-indigo-100 text-center">
                <div class="text-[10px] font-bold text-gray-500 uppercase">2. Accounts Reach</div>
                <div class="text-sm font-black text-indigo-700 mt-1">{{ number_format($totalReach, 0, ',', '.') }}</div>
                <div class="text-[10px] text-gray-400 mt-0.5">Jangkauan Audiens</div>
            </div>

            <div class="p-3 bg-cyan-50/70 rounded-xl border border-cyan-100 text-center">
                <div class="text-[10px] font-bold text-gray-500 uppercase">3. Leads / Messages</div>
                <div class="text-sm font-black text-cyan-800 mt-1">{{ number_format($totalMessages, 0, ',', '.') }}</div>
                <div class="text-[10px] text-cyan-600 mt-0.5">CPL: Rp {{ number_format($blendedCostPerLead, 0, ',', '.') }}</div>
            </div>

            <div class="p-3 bg-amber-50/70 rounded-xl border border-amber-100 text-center">
                <div class="text-[10px] font-bold text-gray-500 uppercase">4. Meetings</div>
                <div class="text-sm font-black text-amber-700 mt-1">{{ number_format($totalMeetings, 0, ',', '.') }}</div>
                <div class="text-[10px] text-gray-400 mt-0.5">Pertemuan Presentasi</div>
            </div>

            <div class="p-3 bg-purple-50/70 rounded-xl border border-purple-100 text-center">
                <div class="text-[10px] font-bold text-gray-500 uppercase">5. Proposals</div>
                <div class="text-sm font-black text-purple-700 mt-1">{{ number_format($totalProposals, 0, ',', '.') }}</div>
                <div class="text-[10px] text-gray-400 mt-0.5">Proposal Terkirim</div>
            </div>

            <div class="p-3 bg-emerald-50/70 rounded-xl border border-emerald-100 text-center">
                <div class="text-[10px] font-bold text-gray-500 uppercase">6. Deals Won</div>
                <div class="text-sm font-black text-emerald-700 mt-1">{{ number_format($totalDeals, 0, ',', '.') }}</div>
                <div class="text-[10px] text-emerald-600 mt-0.5">Win: {{ $blendedConversionRate }}%</div>
            </div>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <span class="text-xs font-semibold text-gray-500 uppercase">Cost Per Lead (CPL)</span>
            <div class="text-2xl font-black text-cyan-800 mt-2">Rp {{ number_format($blendedCostPerLead, 0, ',', '.') }}</div>
            <div class="text-xs text-gray-500 mt-1">Biaya akuisisi per pesan/prospek masuk</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <span class="text-xs font-semibold text-gray-500 uppercase">Cost Per Closed Deal</span>
            <div class="text-2xl font-black text-gray-900 mt-2">Rp {{ number_format($blendedCostPerDeal, 0, ',', '.') }}</div>
            <div class="text-xs text-gray-500 mt-1">Biaya akuisisi per kontrak deal</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <span class="text-xs font-semibold text-gray-500 uppercase">Proposal Win Rate</span>
            <div class="text-2xl font-black text-primary mt-2">{{ $blendedConversionRate }}%</div>
            <div class="text-xs text-gray-500 mt-1">Rasio proposal deal disetujui</div>
        </div>
    </div>

    <!-- Campaigns Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('finance.marketing.index') }}" class="flex flex-wrap items-center gap-2">
                <select name="platform" class="text-xs rounded-lg border-gray-300 shadow-xs focus:ring-primary">
                    <option value="">Semua Platform</option>
                    @foreach($availablePlatforms as $plt)
                        <option value="{{ $plt }}" {{ request('platform') == $plt ? 'selected' : '' }}>
                            {{ strtoupper(str_replace('_', ' ', $plt)) }}
                        </option>
                    @endforeach
                </select>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="text-xs rounded-lg border-gray-300 shadow-xs">
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="text-xs rounded-lg border-gray-300 shadow-xs">
                <button type="submit" class="px-3 py-1.5 bg-primary text-white text-xs font-medium rounded-lg">Filter</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-[10px] font-semibold tracking-wider">
                        <th class="py-3 px-4">Kampanye & Platform</th>
                        <th class="py-3 px-4">Periode</th>
                        <th class="py-3 px-4 text-right">Ad Spend</th>
                        <th class="py-3 px-4 text-center">Reach / Leads</th>
                        <th class="py-3 px-4 text-center">Deals Won</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($campaigns as $camp)
                    <tr class="hover:bg-blue-50/40 transition-colors">
                        <td class="py-3 px-4">
                            <div class="font-bold text-gray-900">{{ $camp->campaign_name }}</div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 uppercase mt-0.5">
                                {{ str_replace('_', ' ', $camp->platform) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-gray-600">
                            <div>{{ $camp->start_date->format('d/m/Y') }}</div>
                            <div class="text-[10px] text-gray-400">s/d {{ $camp->end_date ? $camp->end_date->format('d/m/Y') : 'Aktif' }}</div>
                        </td>
                        <td class="py-3 px-4 text-right font-black text-gray-900">
                            Rp {{ number_format($camp->ad_spend, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="font-medium text-gray-800">{{ number_format($camp->accounts_reached, 0, ',', '.') }} reach</div>
                            <div class="text-[10px] text-cyan-700 font-semibold mt-0.5">{{ $camp->messages_count }} leads (CPL Rp {{ number_format($camp->cost_per_lead, 0, ',', '.') }})</div>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full font-bold bg-emerald-100 text-emerald-800 text-[11px]">
                                {{ $camp->deals_count }} deals
                            </span>
                            <div class="text-[10px] text-gray-500 mt-0.5">dari {{ $camp->proposals_count }} proposal</div>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button type="button" @click="openEditModal({{ json_encode($camp) }})" class="p-1 text-gray-500 hover:text-primary rounded hover:bg-gray-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('finance.marketing.destroy', $camp) }}" onsubmit="return confirm('Hapus kampanye ini?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-gray-500 hover:text-red-600 rounded hover:bg-gray-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-500">Belum ada kampanye pemasaran yang tercatat.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($campaigns->hasPages())
        <div class="p-4 border-t border-gray-200">
            {{ $campaigns->links() }}
        </div>
        @endif
    </div>

    <!-- Modal Form (Create & Edit) -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
                <form :action="isEditing ? editUrl : '{{ route('finance.marketing.store') }}'" method="POST">
                    @csrf
                    <template x-if="isEditing">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-base font-bold text-gray-900" x-text="isEditing ? 'Edit Kampanye Pemasaran' : 'Tambah Kampanye Pemasaran Baru'"></h3>
                        <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Platform Iklan <span class="text-red-500">*</span></label>
                                <select name="platform" x-model="form.platform" required class="w-full text-xs rounded-lg border-gray-300 focus:ring-primary">
                                    <option value="meta_ads">Meta Ads (FB/IG)</option>
                                    <option value="google_ads">Google Ads</option>
                                    <option value="tiktok_ads">TikTok Ads</option>
                                    <option value="linkedin_ads">LinkedIn Ads</option>
                                    <option value="offline">Offline / Sponsorship</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Kampanye <span class="text-red-500">*</span></label>
                                <input type="text" name="campaign_name" x-model="form.campaign_name" required placeholder="e.g. Lead Gen Web App Q4" class="w-full text-xs rounded-lg border-gray-300 focus:ring-primary">
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Mulai <span class="text-red-500">*</span></label>
                                <input type="date" name="start_date" x-model="form.start_date" required class="w-full text-xs rounded-lg border-gray-300">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Berakhir</label>
                                <input type="date" name="end_date" x-model="form.end_date" class="w-full text-xs rounded-lg border-gray-300">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Ad Spend (Rp) <span class="text-red-500">*</span></label>
                                <input type="number" step="1000" min="0" name="ad_spend" x-model="form.ad_spend" required class="w-full text-xs rounded-lg border-gray-300">
                            </div>
                        </div>

                        <!-- Funnel Metrics -->
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
                            <span class="text-[11px] font-bold text-gray-600 uppercase tracking-wide block mb-2">Metriks Funnel Konversi</span>
                            <div class="grid grid-cols-3 gap-2 mb-2">
                                <div>
                                    <label class="block text-[10px] text-gray-500">Views</label>
                                    <input type="number" min="0" name="views_count" x-model="form.views_count" class="w-full text-xs rounded border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-500">Reach</label>
                                    <input type="number" min="0" name="accounts_reached" x-model="form.accounts_reached" class="w-full text-xs rounded border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-500">Inquiries (Messages)</label>
                                    <input type="number" min="0" name="messages_count" x-model="form.messages_count" class="w-full text-xs rounded border-gray-300">
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[10px] text-gray-500">Meetings</label>
                                    <input type="number" min="0" name="meetings_count" x-model="form.meetings_count" class="w-full text-xs rounded border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-500">Proposals</label>
                                    <input type="number" min="0" name="proposals_count" x-model="form.proposals_count" class="w-full text-xs rounded border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-500">Deals Won</label>
                                    <input type="number" min="0" name="deals_count" x-model="form.deals_count" class="w-full text-xs rounded border-gray-300">
                                </div>
                            </div>
                        </div>


                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Catatan Kampanye</label>
                            <textarea name="notes" x-model="form.notes" rows="2" placeholder="Detail targeting, link creative, atau evaluasi..." class="w-full text-xs rounded-lg border-gray-300"></textarea>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex justify-end gap-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-white">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-semibold hover:bg-blue-800">Simpan Kampanye</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function marketingManager() {
    return {
        showModal: false,
        isEditing: false,
        editUrl: '',
        form: {
            platform: 'meta_ads',
            campaign_name: '',
            start_date: '{{ date('Y-m-d') }}',
            end_date: '',
            ad_spend: 0,
            views_count: 0,
            accounts_reached: 0,
            messages_count: 0,
            meetings_count: 0,
            proposals_count: 0,
            deals_count: 0,
            total_revenue_generated: 0,
            roi_percentage: '',
            notes: ''
        },
        openCreateModal() {
            this.isEditing = false;
            this.editUrl = '';
            this.form = {
                platform: 'meta_ads',
                campaign_name: '',
                start_date: '{{ date('Y-m-d') }}',
                end_date: '',
                ad_spend: 0,
                views_count: 0,
                accounts_reached: 0,
                messages_count: 0,
                meetings_count: 0,
                proposals_count: 0,
                deals_count: 0,
                total_revenue_generated: 0,
                roi_percentage: '',
                notes: ''
            };
            this.showModal = true;
        },
        openEditModal(camp) {
            this.isEditing = true;
            this.editUrl = `/finance/marketing/${camp.id}`;
            this.form = {
                platform: camp.platform,
                campaign_name: camp.campaign_name,
                start_date: camp.start_date ? camp.start_date.substring(0, 10) : '',
                end_date: camp.end_date ? camp.end_date.substring(0, 10) : '',
                ad_spend: camp.ad_spend,
                views_count: camp.views_count,
                accounts_reached: camp.accounts_reached,
                messages_count: camp.messages_count,
                meetings_count: camp.meetings_count,
                proposals_count: camp.proposals_count,
                deals_count: camp.deals_count,
                total_revenue_generated: camp.total_revenue_generated,
                roi_percentage: camp.roi_percentage,
                notes: camp.notes || ''
            };
            this.showModal = true;
        }
    }
}
</script>
@endsection
