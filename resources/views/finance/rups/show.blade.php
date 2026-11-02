@extends('layouts.app')

@section('title', 'Evaluasi Kuartal ' . $closing->period_label . ' & Simulator RUPS - Wiro Management')

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6"
     x-data="{
         netProfit: {{ (float)$closing->net_profit }},
         endingCash: {{ (float)$closing->ending_cash_balance }},
         cashFloor: 15000000,
         retainedPercent: {{ $retainedPercent }},
         distributionDate: '{{ now()->toDateString() }}',
         distributionNotes: '{{ addslashes($closing->profitDistribution?->notes ?? 'Persetujuan pembagian dividen triwulanan hasil RUPS.') }}',
         documentNumber: '{{ addslashes($closing->profitDistribution?->document_number ?? ('BA-RUPS/WD/' . $closing->year . '/Q' . $closing->quarter . '/' . str_pad($closing->id, 3, '0', STR_PAD_LEFT))) }}',
         showDistributeModal: false,
         showUploadModal: false,
         activePayoutId: null,
         activeShareholderName: '',
         activePayoutAmount: 0,

         shareholders: [
             @foreach($shareholders as $sh)
             @php
                 $existingPayout = $closing->profitDistribution?->payouts->firstWhere('shareholder_id', $sh->id);
                 $existingReinvest = $existingPayout ? (float)$existingPayout->reinvest_percentage : 0;
             @endphp
             {
                 id: {{ $sh->id }},
                 name: '{{ addslashes($sh->name) }}',
                 shares_count: {{ (int)$sh->shares_count }},
                 ownership_percentage: {{ (float)$sh->ownership_percentage }},
                 capital_amount: {{ (float)$sh->capital_amount }},
                 reinvest_percentage: {{ $existingReinvest }}
             },
             @endforeach
         ],

         get safeCashCeiling() {
             return Math.max(0, this.endingCash - this.cashFloor);
         },
         get retainedAmount() {
             return this.netProfit > 0 ? (this.netProfit * (this.retainedPercent / 100)) : 0;
         },
         get availableProfit() {
             return Math.max(0, this.netProfit - this.retainedAmount);
         },
         get distributableProfit() {
             return Math.min(this.availableProfit, this.safeCashCeiling);
         },
         get isCappedByCash() {
             return this.distributableProfit < this.availableProfit && this.availableProfit > 0;
         },
         getPayout(sh) {
             if (this.distributableProfit <= 0) return 0;
             return Math.round(this.distributableProfit * (sh.ownership_percentage / 100));
         },
         getReinvestAmount(sh) {
             const payout = this.getPayout(sh);
             return Math.round(payout * ((sh.reinvest_percentage || 0) / 100));
         },
         getCashAmount(sh) {
             return Math.max(0, this.getPayout(sh) - this.getReinvestAmount(sh));
         },
         get totalCashOut() {
             return this.shareholders.reduce((sum, sh) => sum + this.getCashAmount(sh), 0);
         },
         get totalReinvested() {
             return this.shareholders.reduce((sum, sh) => sum + this.getReinvestAmount(sh), 0);
         },
         getNewCapital(sh) {
             return sh.capital_amount + this.getReinvestAmount(sh);
         },
         get totalNewCapital() {
             return this.shareholders.reduce((sum, sh) => sum + this.getNewCapital(sh), 0);
         },
         getNewOwnershipPercentage(sh) {
             if (this.totalNewCapital <= 0) return sh.ownership_percentage.toFixed(2);
             return ((this.getNewCapital(sh) / this.totalNewCapital) * 100).toFixed(2);
         },
         calculatePayout(pct) {
             if (this.distributableProfit <= 0) return 0;
             return Math.round(this.distributableProfit * (pct / 100));
         },
         formatRupiah(val) {
             return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(val || 0));
         },
         openUploadModal(id, name, amount) {
             this.activePayoutId = id;
             this.activeShareholderName = name;
             this.activePayoutAmount = amount;
             this.showUploadModal = true;
         }
     }">

    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between pb-4 border-b border-gray-200 gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <a href="{{ route('finance.overview') }}" class="hover:text-primary">Finance</a>
                <span>/</span>
                <a href="{{ route('finance.rups.index') }}" class="hover:text-primary">RUPS Kuartalan</a>
                <span>/</span>
                <span class="font-bold text-gray-700">{{ $closing->period_label }}</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2.5">
                Evaluasi Kuartal {{ $closing->period_label }}
                @if($closing->audit_status === 'approved')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Disetujui</span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase">{{ $closing->audit_status }}</span>
                @endif
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Konsolidasi periode: <span class="font-bold text-gray-700">{{ $closing->quarter_months[0] }}</span> s/d <span class="font-bold text-gray-700">{{ $closing->quarter_months[2] }}</span> (3 Bulan).
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('finance.rups.index') }}" class="inline-flex items-center px-3.5 py-2 border border-gray-300 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-50 transition-colors shadow-2xs">
                <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Daftar
            </a>

            @if($closing->profitDistribution && in_array($closing->profitDistribution->status, ['approved', 'distributed']))
            <a href="{{ route('finance.rups.pdf', $closing->id) }}" target="_blank"
               class="inline-flex items-center px-3.5 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg text-xs transition-colors shadow-sm gap-1.5">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Cetak Berita Acara RUPS (PDF)
            </a>
            @endif

            @can('finance.rups.manage')
            <button @click="showDistributeModal = true" type="button" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white font-bold rounded-lg text-xs hover:bg-emerald-700 transition-colors shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ $closing->profitDistribution ? 'Perbarui Keputusan RUPS' : 'Sahkan Keputusan Bagi Hasil RUPS' }}
            </button>
            @endcan
        </div>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-xs">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-emerald-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">&times;</button>
    </div>
    @endif

    <!-- 1. Card Indikator Proteksi Kas Statis (Safety Cash Floor Rp 15 Juta) -->
    <div class="bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-950 text-white rounded-2xl p-6 shadow-md border border-blue-900/60">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 pb-6 border-b border-blue-800/50">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-blue-500/20 text-blue-300 border border-blue-400/30">
                        Protokol Ketahanan Kas
                    </span>
                    <span class="text-xs text-blue-200">Audit Rule #3</span>
                </div>
                <h2 class="text-xl font-black mt-2 tracking-tight text-white flex items-center gap-2">
                    Indikator Proteksi Kas Statis (Safety Cash Floor)
                </h2>
                <p class="text-xs text-blue-200/90 mt-1 max-w-2xl leading-relaxed">
                    Dividen triwulanan hanya boleh dicairkan jika kas akhir bank tetap mencukupi batas aman minimal <span class="font-bold text-white">Rp 15.000.000</span> untuk melindungi biaya operasional rintisan.
                </p>
            </div>

            <!-- Status Meter Badge -->
            <div class="flex items-center gap-3">
                @if($closing->isCashFloorMet())
                <div class="bg-emerald-500/20 border border-emerald-400/40 px-4 py-2.5 rounded-xl flex items-center gap-3">
                    <div class="w-3.5 h-3.5 rounded-full bg-emerald-400 animate-pulse"></div>
                    <div>
                        <div class="text-[10px] uppercase font-bold text-emerald-300">Status Cadangan Kas</div>
                        <div class="text-sm font-black text-emerald-100">KAS AMAN TERLINDUNGI</div>
                    </div>
                </div>
                @else
                <div class="bg-red-500/20 border border-red-400/40 px-4 py-2.5 rounded-xl flex items-center gap-3">
                    <div class="w-3.5 h-3.5 rounded-full bg-red-400 animate-pulse"></div>
                    <div>
                        <div class="text-[10px] uppercase font-bold text-red-300">Status Cadangan Kas</div>
                        <div class="text-sm font-black text-red-100">DI BAWAH BATAS Rp 15JT</div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- 3 Metrics Columns -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-6">
            <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                <span class="text-[11px] font-bold text-blue-300 uppercase tracking-wider">1. Saldo Kas Bank Akhir Kuartal</span>
                <div class="text-2xl font-black text-white mt-1.5 font-mono">
                    Rp {{ number_format($closing->ending_cash_balance, 0, ',', '.') }}
                </div>
                <div class="text-[10px] text-blue-200/80 mt-1">Saldo akhir kas penutupan Bulan 3</div>
            </div>

            <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                <span class="text-[11px] font-bold text-amber-300 uppercase tracking-wider">2. Batas Aman Statis (Safety Floor)</span>
                <div class="text-2xl font-black text-amber-300 mt-1.5 font-mono">
                    Rp 15.000.000
                </div>
                <div class="text-[10px] text-amber-200/80 mt-1">Batas minimum wajib tersisa di bank</div>
            </div>

            <div class="bg-white/10 rounded-xl p-4 border border-emerald-400/30">
                <span class="text-[11px] font-bold text-emerald-300 uppercase tracking-wider">3. Plafon Maksimum Kas Aman Dibagi</span>
                <div class="text-2xl font-black text-emerald-300 mt-1.5 font-mono">
                    Rp {{ number_format($closing->cash_surplus_above_floor, 0, ',', '.') }}
                </div>
                <div class="text-[10px] text-emerald-200/90 mt-1">max(0, Kas Akhir - Rp 15.000.000)</div>
            </div>
        </div>
    </div>

    <!-- 2. Rekap Performa 3 Bulan (Konsolidasi Revenue, COGS, OPEX, Net Profit) -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-gray-200 bg-gray-50/70 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Rekap Konsolidasi Performa Keuangan (3 Bulan)
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">Dual-Role Compensation: Beban manajerial struktural di OPEX dan honor eksekusi teknis di COGS.</p>
            </div>
            <span class="text-xs font-mono font-bold text-gray-500 bg-gray-200/80 px-2.5 py-1 rounded-md">
                Kuartal {{ $closing->period_label }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-100/80 border-b border-gray-200 text-gray-700 uppercase text-[10px] font-bold tracking-wider">
                        <th class="py-3 px-4 w-1/3">Komponen Laporan Keuangan</th>
                        @foreach($closing->quarter_months as $index => $m)
                            <th class="py-3 px-4 text-right">
                                Bulan {{ $index + 1 }} ({{ $m }})
                            </th>
                        @endforeach
                        <th class="py-3 px-4 text-right bg-blue-50/80 text-primary font-black">
                            Total Kuartal (Konsolidasi)
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 font-mono">
                    <!-- 1. Revenue -->
                    <tr class="hover:bg-gray-50 font-sans">
                        <td class="py-3 px-4 font-bold text-gray-800 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            1. Total Pendapatan Proyek (Revenue)
                        </td>
                        @foreach($closing->quarter_months as $m)
                            @php $mc = $monthlyClosings->firstWhere('period_month', $m); @endphp
                            <td class="py-3 px-4 text-right font-medium text-gray-800">
                                {{ $mc ? 'Rp ' . number_format($mc->total_revenue, 0, ',', '.') : '-' }}
                            </td>
                        @endforeach
                        <td class="py-3 px-4 text-right bg-blue-50/50 font-black text-gray-900 text-sm">
                            Rp {{ number_format($closing->total_revenue, 0, ',', '.') }}
                        </td>
                    </tr>

                    <!-- 2. COGS -->
                    <tr class="hover:bg-gray-50 font-sans text-red-700 bg-red-50/30">
                        <td class="py-2.5 px-4 font-bold flex items-center gap-1.5 pl-6">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            2. Beban Pokok Pendapatan (HPP / COGS)
                        </td>
                        @foreach($closing->quarter_months as $m)
                            @php $mc = $monthlyClosings->firstWhere('period_month', $m); @endphp
                            <td class="py-2.5 px-4 text-right font-medium">
                                {{ $mc ? '(Rp ' . number_format($mc->total_cogs, 0, ',', '.') . ')' : '-' }}
                            </td>
                        @endforeach
                        <td class="py-2.5 px-4 text-right bg-red-100/40 font-black text-red-800">
                            (Rp {{ number_format($closing->total_cogs, 0, ',', '.') }})
                        </td>
                    </tr>

                    <!-- 3. Gross Profit -->
                    <tr class="hover:bg-gray-50 font-sans bg-indigo-50/40">
                        <td class="py-2.5 px-4 font-black text-indigo-900 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                            3. Laba Kotor (Gross Profit)
                        </td>
                        @foreach($closing->quarter_months as $m)
                            @php $mc = $monthlyClosings->firstWhere('period_month', $m); @endphp
                            <td class="py-2.5 px-4 text-right font-bold text-indigo-800">
                                {{ $mc ? 'Rp ' . number_format($mc->gross_profit, 0, ',', '.') : '-' }}
                            </td>
                        @endforeach
                        <td class="py-2.5 px-4 text-right bg-indigo-100/50 font-black text-indigo-950 text-sm">
                            Rp {{ number_format($closing->gross_profit, 0, ',', '.') }}
                        </td>
                    </tr>

                    <!-- 4. OPEX -->
                    <tr class="hover:bg-gray-50 font-sans text-amber-800 bg-amber-50/30">
                        <td class="py-2.5 px-4 font-bold flex items-center gap-1.5 pl-6">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            4. Beban Operasional & Payroll Manajerial (OPEX)
                        </td>
                        @foreach($closing->quarter_months as $m)
                            @php $mc = $monthlyClosings->firstWhere('period_month', $m); @endphp
                            <td class="py-2.5 px-4 text-right font-medium">
                                {{ $mc ? '(Rp ' . number_format($mc->total_opex, 0, ',', '.') . ')' : '-' }}
                            </td>
                        @endforeach
                        <td class="py-2.5 px-4 text-right bg-amber-100/40 font-black text-amber-900">
                            (Rp {{ number_format($closing->total_opex, 0, ',', '.') }})
                        </td>
                    </tr>

                    <!-- 5. Net Profit -->
                    <tr class="hover:bg-gray-50 font-sans {{ $closing->net_profit >= 0 ? 'bg-emerald-50/60' : 'bg-red-50/60' }}">
                        <td class="py-3 px-4 font-black {{ $closing->net_profit >= 0 ? 'text-emerald-900' : 'text-red-900' }} flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full {{ $closing->net_profit >= 0 ? 'bg-emerald-600' : 'bg-red-600' }}"></span>
                            5. Laba Bersih Kuartal (Net Profit)
                        </td>
                        @foreach($closing->quarter_months as $m)
                            @php $mc = $monthlyClosings->firstWhere('period_month', $m); @endphp
                            <td class="py-3 px-4 text-right font-bold {{ ($mc && $mc->net_profit >= 0) ? 'text-emerald-700' : 'text-red-600' }}">
                                {{ $mc ? 'Rp ' . number_format($mc->net_profit, 0, ',', '.') : '-' }}
                            </td>
                        @endforeach
                        <td class="py-3 px-4 text-right {{ $closing->net_profit >= 0 ? 'bg-emerald-100/60 text-emerald-950' : 'bg-red-100/60 text-red-950' }} font-black text-base">
                            Rp {{ number_format($closing->net_profit, 0, ',', '.') }}
                        </td>
                    </tr>

                    <!-- Net Margin -->
                    <tr class="hover:bg-gray-50 font-sans text-gray-600 text-[11px]">
                        <td class="py-2 px-4 pl-6 text-gray-500 italic">Net Profit Margin (%)</td>
                        @foreach($closing->quarter_months as $m)
                            @php $mc = $monthlyClosings->firstWhere('period_month', $m); @endphp
                            <td class="py-2 px-4 text-right">{{ $mc ? $mc->net_profit_margin . '%' : '-' }}</td>
                        @endforeach
                        <td class="py-2 px-4 text-right bg-blue-50/30 font-bold text-gray-800">{{ $closing->net_profit_margin }}%</td>
                    </tr>

                    <!-- Cash Reconciliation -->
                    <tr class="hover:bg-gray-50 font-sans bg-gray-50 text-[11px]">
                        <td class="py-2.5 px-4 font-bold text-gray-700">Saldo Kas Bank (Awal vs Akhir)</td>
                        @foreach($closing->quarter_months as $m)
                            @php $mc = $monthlyClosings->firstWhere('period_month', $m); @endphp
                            <td class="py-2.5 px-4 text-right text-gray-700">
                                {{ $mc ? 'Rp ' . number_format($mc->ending_cash_balance, 0, ',', '.') : '-' }}
                            </td>
                        @endforeach
                        <td class="py-2.5 px-4 text-right bg-blue-100/50 font-black text-blue-900 text-xs">
                            Rp {{ number_format($closing->ending_cash_balance, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. Simulator Pembagian Laba Interaktif (Real-Time Alpine.js Slider) -->
    <div class="bg-white rounded-2xl border-2 border-primary/20 shadow-sm p-6 space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-gray-200">
            <div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-primary/10 text-primary border border-primary/20">
                    Simulator Interaktif
                </span>
                <h3 class="text-lg font-black text-gray-900 mt-1 flex items-center gap-2">
                    Simulator Pembagian Laba Bersih & Dividen RUPS
                </h3>
                <p class="text-xs text-gray-500">
                    Sesuaikan persentase Laba Ditahan untuk melihat simulasi pencairan transfer ke rekening pendiri secara real-time.
                </p>
            </div>

            <!-- Distribution Status Badge -->
            <div>
                @if($closing->profitDistribution)
                    @if($closing->profitDistribution->status === 'distributed')
                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                            <svg class="w-4 h-4 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Keputusan Telah Ditransfer Penuh
                        </span>
                    @elseif($closing->profitDistribution->status === 'approved')
                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                            <svg class="w-4 h-4 mr-1 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Keputusan Disahkan (Menunggu Transfer)
                        </span>
                    @endif
                @endif
            </div>
        </div>

        <!-- Slider Control & Allocation Equation -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center bg-gray-50/80 p-5 rounded-xl border border-gray-200">
            <!-- Left Slider (5 Cols) -->
            <div class="lg:col-span-5 space-y-3">
                <div class="flex items-center justify-between">
                    <label for="retainedSlider" class="text-xs font-bold text-gray-800 uppercase tracking-wide">
                        Cadangan Laba Ditahan (Retained Earnings)
                    </label>
                    <div class="flex items-center gap-1 bg-white px-2.5 py-1 rounded-lg border border-gray-300 shadow-2xs font-mono font-black text-primary text-sm">
                        <span x-text="retainedPercent"></span>%
                    </div>
                </div>

                <input type="range" id="retainedSlider" min="0" max="100" step="5" x-model.number="retainedPercent"
                       class="w-full h-2.5 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-primary">

                <div class="flex justify-between text-[10px] text-gray-400 font-bold">
                    <span>0% (Bagi Semua)</span>
                    <span>20% (Standar Agency)</span>
                    <span>50%</span>
                    <span>100% (Ditahan Penuh)</span>
                </div>
            </div>

            <!-- Right Computed Allocation Summary (7 Cols) -->
            <div class="lg:col-span-7 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-2xs">
                    <span class="text-[10px] font-bold text-gray-500 uppercase block">Laba Bersih</span>
                    <span class="font-mono font-bold text-gray-900 block mt-1" x-text="formatRupiah(netProfit)"></span>
                </div>

                <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-2xs">
                    <span class="text-[10px] font-bold text-amber-700 uppercase block">Laba Ditahan</span>
                    <span class="font-mono font-bold text-amber-700 block mt-1" x-text="formatRupiah(retainedAmount)"></span>
                </div>

                <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-2xs">
                    <span class="text-[10px] font-bold text-blue-700 uppercase block">Plafon Kas Aman</span>
                    <span class="font-mono font-bold text-blue-700 block mt-1" x-text="formatRupiah(safeCashCeiling)"></span>
                </div>

                <div class="bg-emerald-50 p-3 rounded-lg border border-emerald-300 shadow-2xs">
                    <span class="text-[10px] font-bold text-emerald-800 uppercase block">Dividen Siap Bagi</span>
                    <span class="font-mono font-black text-emerald-700 block mt-1 text-sm" x-text="formatRupiah(distributableProfit)"></span>
                </div>
            </div>
        </div>

        <!-- Alert Notification if Capped by Cash Floor -->
        <div x-show="isCappedByCash" x-cloak class="p-3.5 bg-amber-50 rounded-xl border border-amber-300 text-xs text-amber-900 flex items-start gap-2.5">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <span class="font-bold">Proteksi Kas Statis Membatasi Dividen:</span>
                Sisa laba bersih (<span class="font-bold" x-text="formatRupiah(availableProfit)"></span>) melebihi plafon kas aman yang tersisa (<span class="font-bold" x-text="formatRupiah(safeCashCeiling)"></span>). Nominal dividen dibatasi agar saldo kas perusahaan di bank tidak turun di bawah Rp 15.000.000.
            </div>
        </div>

        <!-- Interactive Founder Allocation Table -->
        <div class="rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider">
                    Simulasi Rincian Payout Per Pendiri (Real-Time Proportional)
                </h4>
                <span class="text-[11px] text-gray-500 font-mono">Formula: Distributable Profit &times; % Kepemilikan</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-100/60 border-b border-gray-200 text-gray-600 uppercase text-[10px] font-bold">
                            <th class="py-3 px-4">Nama Pendiri</th>
                            <th class="py-3 px-4">Bank & Nomor Rekening</th>
                            <th class="py-3 px-4 text-right">Lembar Saham</th>
                            <th class="py-3 px-4 text-right">Porsi Saham</th>
                            <th class="py-3 px-4 text-right bg-emerald-50 text-emerald-900 font-bold">Estimasi Nominal Hak Bagi Hasil</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($shareholders as $sh)
                        <tr class="hover:bg-gray-50/50">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-gray-900">{{ $sh->name }}</div>
                                <div class="text-[11px] text-gray-400">{{ $sh->email }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-gray-700">
                                <div class="font-semibold">{{ $sh->bank_name }} - {{ $sh->bank_account_number }}</div>
                                <div class="text-[11px] text-gray-400">a.n. {{ $sh->bank_account_holder }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-medium text-gray-800">
                                {{ number_format($sh->shares_count, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-bold text-primary">
                                {{ number_format($sh->ownership_percentage, 2) }}%
                            </td>
                            <td class="py-3.5 px-4 text-right bg-emerald-50/50 font-mono font-black text-emerald-700 text-sm">
                                <span x-text="formatRupiah(calculatePayout({{ (float)$sh->ownership_percentage }}))"></span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 4. Tabel Hak Bagi Hasil Founder Resmi & Upload Bukti Transfer -->
    @if($closing->profitDistribution)
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-gray-200 bg-gray-50/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Daftar Pencairan Dividen Sah RUPS
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    Keputusan disahkan tanggal {{ $closing->profitDistribution->distribution_date ? $closing->profitDistribution->distribution_date->translatedFormat('d F Y') : '-' }} &bull; Laba Ditahan: {{ $closing->profitDistribution->retained_earnings_percentage }}%
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-gray-700">Total Dividen Disahkan:</span>
                <span class="px-3 py-1 bg-emerald-100 text-emerald-900 font-mono font-black text-sm rounded-lg border border-emerald-300">
                    Rp {{ number_format($closing->profitDistribution->distributable_profit, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-100/80 border-b border-gray-200 text-gray-600 uppercase text-[10px] font-bold tracking-wider">
                        <th class="py-3 px-4">Nama Pendiri</th>
                        <th class="py-3 px-4">Rekening Bank</th>
                        <th class="py-3 px-4 text-right">Lembar Saham (%)</th>
                        <th class="py-3 px-4 text-right">Total Hak Dividen</th>
                        <th class="py-3 px-4 text-right text-blue-700 bg-blue-50/50">Dividen Tunai</th>
                        <th class="py-3 px-4 text-right text-emerald-700 bg-emerald-50/50">Reinvest Modal</th>
                        <th class="py-3 px-4 text-center">Status Pembayaran</th>
                        <th class="py-3 px-4 text-center">Bukti Transfer</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($closing->profitDistribution->payouts as $payout)
                    <tr class="hover:bg-gray-50/50">
                        <td class="py-3.5 px-4 font-bold text-gray-900 text-sm">
                            {{ $payout->shareholder->name }}
                        </td>
                        <td class="py-3.5 px-4 text-gray-700">
                            <div class="font-semibold">{{ $payout->shareholder->bank_name }} - {{ $payout->shareholder->bank_account_number }}</div>
                            <div class="text-[11px] text-gray-400">a.n. {{ $payout->shareholder->bank_account_holder }}</div>
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono">
                            {{ number_format($payout->shares_count, 0, ',', '.') }}
                            <span class="text-gray-500 font-sans font-bold">({{ number_format($payout->ownership_percentage, 2) }}%)</span>
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono font-black text-gray-900 text-sm">
                            Rp {{ number_format($payout->payout_amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono font-bold text-blue-700 bg-blue-50/20">
                            Rp {{ number_format($payout->cash_amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-700 bg-emerald-50/20">
                            Rp {{ number_format($payout->reinvest_amount, 0, ',', '.') }}
                            <div class="text-[10px] text-gray-400 font-sans">({{ number_format($payout->reinvest_percentage, 2) }}%)</div>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($payout->payment_status === 'transferred')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                    Ditransfer ({{ $payout->payment_date ? $payout->payment_date->translatedFormat('d/m/Y') : '-' }})
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                    Pending Transfer
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($payout->transfer_receipt_url)
                                <a href="{{ $payout->transfer_receipt_url }}" target="_blank" class="inline-flex items-center text-xs font-semibold text-primary hover:underline gap-1">
                                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Lihat Bukti
                                </a>
                            @else
                                <span class="text-gray-400 text-[11px] italic">Belum Ada</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @can('finance.rups.manage')
                            <button type="button"
                                    @click="openUploadModal({{ $payout->id }}, '{{ addslashes($payout->shareholder->name) }}', {{ (float)$payout->payout_amount }})"
                                    class="inline-flex items-center px-3 py-1.5 {{ $payout->payment_status === 'transferred' ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-primary text-white hover:bg-blue-800' }} rounded-lg text-xs font-bold transition-colors shadow-2xs">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                {{ $payout->payment_status === 'transferred' ? 'Update Bukti' : 'Upload Bukti Transfer' }}
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- 5. Kartu Mutasi & Posisi Modal Kuartal Selanjutnya -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-gray-200 bg-gradient-to-r from-slate-50 to-blue-50/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    Proyeksi Struktur Modal Kuartal Selanjutnya
                    @php
                        $nextQuarterYear = $closing->quarter === 4 ? $closing->year + 1 : $closing->year;
                        $nextQuarterNum = $closing->quarter === 4 ? 1 : $closing->quarter + 1;
                        $nextQuarterLabel = sprintf('%04d-Q%d', $nextQuarterYear, $nextQuarterNum);
                    @endphp
                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-800">
                        Periode Efektif: {{ $nextQuarterLabel }}
                    </span>
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    Hasil roll-forward reinvestasi dividen ke dalam Buku Besar Modal Pemegang Saham (Capital Ledger).
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-gray-700">Total Modal Baru:</span>
                <span class="px-3 py-1 bg-blue-100 text-blue-900 font-mono font-black text-sm rounded-lg border border-blue-200">
                    @if($closing->profitDistribution && $closing->profitDistribution->next_quarter_total_capital > 0)
                        Rp {{ number_format($closing->profitDistribution->next_quarter_total_capital, 0, ',', '.') }}
                    @else
                        <span x-text="formatRupiah(totalNewCapital)"></span>
                    @endif
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-100/70 border-b border-gray-200 text-gray-600 uppercase text-[10px] font-bold tracking-wider">
                        <th class="py-3 px-4">Nama Pemegang Saham</th>
                        <th class="py-3 px-4 text-right">Modal Lama (Rp)</th>
                        <th class="py-3 px-4 text-right">Reinvestasi Dividen (Rp)</th>
                        <th class="py-3 px-4 text-right font-black text-gray-900">Modal Baru (Rp)</th>
                        <th class="py-3 px-4 text-center font-black text-primary">Porsi Kepemilikan Baru (%)</th>
                        <th class="py-3 px-4 text-center">Status Mutasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @if($closing->profitDistribution && in_array($closing->profitDistribution->status, ['approved', 'distributed']))
                        @foreach($shareholders as $sh)
                        @php
                            $payout = $closing->profitDistribution->payouts->firstWhere('shareholder_id', $sh->id);
                            $reinvest = (float)($payout?->reinvest_amount ?? 0);
                            $hist = $closing->capitalHistories->firstWhere('shareholder_id', $sh->id);
                            $openingCap = $hist ? (float)$hist->opening_capital : max(0, (float)$sh->capital_amount - $reinvest);
                            $closingCap = $hist ? (float)$hist->closing_capital : (float)$sh->capital_amount;
                            $closingPct = $hist ? (float)$hist->closing_ownership_percentage : (float)$sh->ownership_percentage;
                        @endphp
                        <tr class="hover:bg-blue-50/20">
                            <td class="py-3.5 px-4 font-bold text-gray-900 text-sm">
                                {{ $sh->name }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-gray-700">
                                Rp {{ number_format($openingCap, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-600">
                                + Rp {{ number_format($reinvest, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-black text-gray-900">
                                Rp {{ number_format($closingCap, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono font-black text-primary text-sm bg-blue-50/30">
                                {{ number_format($closingPct, 2) }}%
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($reinvest > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        Mutasi Tercatat
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600">
                                        Tetap (0% Reinvest)
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    @else
                        {{-- Live Alpine projection before approval --}}
                        <template x-for="sh in shareholders" :key="sh.id">
                            <tr class="hover:bg-blue-50/20">
                                <td class="py-3.5 px-4 font-bold text-gray-900 text-sm" x-text="sh.name"></td>
                                <td class="py-3.5 px-4 text-right font-mono text-gray-700" x-text="formatRupiah(sh.capital_amount)"></td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-600" x-text="'+ ' + formatRupiah(getReinvestAmount(sh))"></td>
                                <td class="py-3.5 px-4 text-right font-mono font-black text-gray-900" x-text="formatRupiah(getNewCapital(sh))"></td>
                                <td class="py-3.5 px-4 text-center font-mono font-black text-primary text-sm bg-blue-50/30" x-text="getNewOwnershipPercentage(sh) + '%'"></td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                        Simulasi Proyeksi
                                    </span>
                                </td>
                            </tr>
                        </template>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal 1: Sahkan Keputusan Bagi Hasil RUPS -->
    <div x-show="showDistributeModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showDistributeModal" @click="showDistributeModal = false" class="fixed inset-0 bg-gray-900/60 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showDistributeModal" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-200">
                <form action="{{ route('finance.rups.distribute', $closing->id) }}" method="POST">
                    @csrf
                    <div class="bg-white px-6 pt-6 pb-4 space-y-4 max-h-[80vh] overflow-y-auto">
                        <div class="flex items-center gap-3 pb-4 border-b border-gray-100">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Sahkan Keputusan Bagi Hasil RUPS</h3>
                                <p class="text-xs text-gray-500">Mengesahkan alokasi dividen, opsi reinvestasi modal, dan berita acara kuartal {{ $closing->period_label }}</p>
                            </div>
                        </div>

                        <!-- Summary of Approval -->
                        <div class="p-4 bg-emerald-50/80 rounded-xl border border-emerald-200 text-xs space-y-2">
                            <div class="flex justify-between text-emerald-950 font-bold">
                                <span>Cadangan Laba Ditahan:</span>
                                <span class="font-mono"><span x-text="retainedPercent"></span>% (<span x-text="formatRupiah(retainedAmount)"></span>)</span>
                            </div>
                            <div class="flex justify-between text-emerald-950 font-black text-sm pt-2 border-t border-emerald-200/80">
                                <span>Total Dividen yang Disahkan:</span>
                                <span class="font-mono text-emerald-700" x-text="formatRupiah(distributableProfit)"></span>
                            </div>
                        </div>

                        <!-- Live Calculation Transparency: Kas vs Reinvestasi Modal -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                            <div class="bg-white p-3 rounded-lg border border-blue-200">
                                <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider block">Total Kas Keluar (Dividen Tunai):</span>
                                <div class="text-lg font-black text-blue-800 font-mono mt-1" x-text="formatRupiah(totalCashOut)"></div>
                                <span class="text-[10px] text-gray-400">Ditransfer ke rekening bank masing-masing</span>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-emerald-200">
                                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Total Tambahan Modal Baru (Reinvestasi):</span>
                                <div class="text-lg font-black text-emerald-800 font-mono mt-1" x-text="formatRupiah(totalReinvested)"></div>
                                <span class="text-[10px] text-gray-400">Masuk ke Capital Ledger & struktur modal baru</span>
                            </div>
                        </div>

                        <!-- Opsi Reinvestasi Dividen Per Pemegang Saham -->
                        <div>
                            <label class="block text-xs font-bold text-gray-800 uppercase mb-2">
                                Opsi Alokasi Dividen Per Pemegang Saham (% Reinvestasi vs % Tunai)
                            </label>
                            <div class="space-y-3">
                                <template x-for="sh in shareholders" :key="sh.id">
                                    <div class="p-3.5 bg-gray-50/80 rounded-xl border border-gray-200 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="font-bold text-gray-900 text-sm" x-text="sh.name"></span>
                                                <span class="text-xs text-gray-500 ml-1 font-mono">(<span x-text="sh.ownership_percentage"></span>%)</span>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-xs text-gray-500 font-medium">Hak Dividen:</span>
                                                <span class="font-mono font-bold text-gray-900 text-xs ml-1" x-text="formatRupiah(getPayout(sh))"></span>
                                            </div>
                                        </div>

                                        <!-- Slider & Inputs -->
                                        <div class="flex items-center gap-3">
                                            <input type="range" min="0" max="100" step="5"
                                                   x-model.number="sh.reinvest_percentage"
                                                   class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-emerald-600">
                                            <div class="flex items-center gap-1 w-24">
                                                <input type="number" min="0" max="100"
                                                       x-model.number="sh.reinvest_percentage"
                                                       class="w-16 text-center text-xs font-mono font-bold rounded border-gray-300 py-1 focus:ring-emerald-500 focus:border-emerald-500">
                                                <span class="text-xs text-gray-500 font-bold">%</span>
                                            </div>
                                        </div>

                                        <input type="hidden" :name="'reinvest_percentages[' + sh.id + ']'" :value="sh.reinvest_percentage">

                                        <!-- Breakdown Detail -->
                                        <div class="flex items-center justify-between text-[11px] pt-1 border-t border-gray-200/60 font-mono">
                                            <span class="text-blue-700">
                                                Tunai (<span x-text="100 - (sh.reinvest_percentage || 0)"></span>%):
                                                <strong x-text="formatRupiah(getCashAmount(sh))"></strong>
                                            </span>
                                            <span class="text-emerald-700">
                                                Reinvest (<span x-text="sh.reinvest_percentage || 0"></span>%):
                                                <strong x-text="formatRupiah(getReinvestAmount(sh))"></strong>
                                            </span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <input type="hidden" name="retained_earnings_percentage" :value="retainedPercent">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="document_number" class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                    Nomor Surat Berita Acara RUPS
                                </label>
                                <input type="text" id="document_number" name="document_number" x-model="documentNumber" required
                                       class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs font-mono">
                            </div>

                            <div>
                                <label for="distribution_date" class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                    Tanggal Keputusan Pembagian
                                </label>
                                <input type="date" id="distribution_date" name="distribution_date" x-model="distributionDate" required
                                       class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs">
                            </div>
                        </div>

                        <div>
                            <label for="notes" class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                Risalah / Catatan Musyawarah RUPS
                            </label>
                            <textarea id="notes" name="notes" rows="3" x-model="distributionNotes"
                                      class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs"
                                      placeholder="Catatan persetujuan hasil rapat para pendiri..."></textarea>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3 rounded-b-2xl border-t border-gray-100">
                        <button type="button" @click="showDistributeModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-100 transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-emerald-700 transition-colors shadow-sm">
                            Konfirmasi & Sahkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
                            Konfirmasi & Sahkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal 2: Upload Bukti Transfer Dividen Founder -->
    <div x-show="showUploadModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="upload-modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showUploadModal" @click="showUploadModal = false" class="fixed inset-0 bg-gray-900/60 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showUploadModal" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-200">
                <form :action="'{{ url('finance/rups/payouts') }}/' + activePayoutId + '/mark-paid'" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="bg-white px-6 pt-6 pb-4 space-y-4">
                        <div class="flex items-center gap-3 pb-4 border-b border-gray-100">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-primary flex items-center justify-center font-bold">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900" id="upload-modal-title">Upload Bukti Transfer</h3>
                                <p class="text-xs text-gray-500">Pencairan dividen untuk <span class="font-bold text-gray-800" x-text="activeShareholderName"></span></p>
                            </div>
                        </div>

                        <div class="p-3 bg-blue-50 rounded-xl border border-blue-200 text-xs">
                            <span class="text-gray-500">Nominal Transfer:</span>
                            <div class="text-lg font-black text-primary font-mono mt-0.5" x-text="formatRupiah(activePayoutAmount)"></div>
                        </div>

                        <div>
                            <label for="payment_date" class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                Tanggal Transfer Bank
                            </label>
                            <input type="date" id="payment_date" name="payment_date" value="{{ date('Y-m-d') }}" required
                                   class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs">
                        </div>

                        <div>
                            <label for="transfer_receipt" class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                Berkas Bukti Transfer (JPG, PNG, PDF)
                            </label>
                            <input type="file" id="transfer_receipt" name="transfer_receipt" accept="image/*,application/pdf"
                                   class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-primary hover:file:bg-blue-100 cursor-pointer">
                        </div>

                        <div>
                            <label for="payout_notes" class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                Catatan Transfer (Opsional)
                            </label>
                            <input type="text" id="payout_notes" name="notes" placeholder="No. referensi transfer atau keterangan..."
                                   class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs">
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3 rounded-b-2xl border-t border-gray-100">
                        <button type="button" @click="showUploadModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-100 transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-primary text-white rounded-lg text-xs font-bold hover:bg-blue-800 transition-colors shadow-sm">
                            Simpan & Tandai Ditransfer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
