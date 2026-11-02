@extends('layouts.app')

@section('title', 'RUPS Triwulanan & Pembagian Dividen - Wiro Management')

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" x-data="{ showGenModal: false, genForm: { year: '{{ date('Y') }}', quarter: '{{ ceil(date('n') / 3) }}' } }">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between pb-6 border-b border-gray-200 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                RUPS Triwulanan & Dividen Pendiri
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Konsolidasi laporan keuangan 3 bulanan, proteksi kas statis (Safety Cash Floor Rp 15 Juta), dan simulator bagi hasil kemitraan pendiri.
            </p>
        </div>
        <div class="flex items-center gap-3">
            @can('finance.rups.manage')
            <button @click="showGenModal = true" type="button" class="inline-flex items-center px-4 py-2 bg-primary text-white font-semibold rounded-lg text-sm hover:bg-blue-800 shadow-sm transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Konsolidasi Kuartal Baru
            </button>
            @endcan
        </div>
    </div>

    <!-- Alert Flash -->
    @if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-xs">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-emerald-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">&times;</button>
    </div>
    @endif

    <!-- Financial KPI Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Safety Cash Floor Card -->
        <div class="bg-gradient-to-br from-blue-900 to-indigo-900 text-white p-5 rounded-xl border border-blue-800 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-200">Proteksi Kas Statis</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-700/60 text-blue-100 border border-blue-500/30">
                        Floor Wajib
                    </span>
                </div>
                <div class="text-2xl font-black mt-2">Rp 15.000.000</div>
                <div class="text-xs text-blue-200/90 mt-1">Safety Cash Floor Wirodev</div>
            </div>
            <div class="mt-4 pt-3 border-t border-blue-700/50 text-[11px] text-blue-100/80 leading-relaxed">
                Dividen hanya dapat dicairkan dari surplus kas di atas Rp 15 Juta.
            </div>
        </div>

        <!-- Total Kuartal Tertutup -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs flex flex-col justify-between">
            <div>
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Kuartal Terdaftar</span>
                <div class="text-2xl font-black text-gray-900 mt-2">{{ $closings->total() }} Kuartal</div>
                <div class="text-xs text-gray-500 mt-1">Konsolidasi penutupan triwulanan</div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 text-[11px] text-gray-500">
                Terakhir: <span class="font-bold text-gray-800">{{ $latestClosing ? $latestClosing->period_label : 'Belum Ada' }}</span>
            </div>
        </div>

        <!-- Akumulasi Laba Bersih -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs flex flex-col justify-between">
            <div>
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Akumulasi Laba Bersih</span>
                <div class="text-2xl font-black {{ $totalNetProfitAll >= 0 ? 'text-emerald-600' : 'text-red-600' }} mt-2">
                    Rp {{ number_format($totalNetProfitAll, 0, ',', '.') }}
                </div>
                <div class="text-xs text-gray-500 mt-1">Total laba bersih seluruh kuartal</div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 text-[11px] text-gray-500">
                Total Omzet: <span class="font-bold text-gray-800">Rp {{ number_format($totalRevenueAll, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Total Dividen Ditransfer -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs flex flex-col justify-between">
            <div>
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Dividen Telah Ditransfer</span>
                <div class="text-2xl font-black text-indigo-700 mt-2">
                    Rp {{ number_format($totalPaidDividends, 0, ',', '.') }}
                </div>
                <div class="text-xs text-gray-500 mt-1">Hak bagi hasil yang telah dicairkan</div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 text-[11px] text-gray-500 flex items-center justify-between">
                <span>Pending Transfer:</span>
                <span class="font-bold text-amber-700">Rp {{ number_format($totalPendingDividends, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Main Content: Closings Table & Shareholders List -->
    <div class="space-y-6">
        <!-- Quarterly Closings Table -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between bg-gray-50/50">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Daftar Konsolidasi Kuartal (RUPS)</h2>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                        {{ $closings->total() }} Periode
                    </span>
                </div>
                <span class="text-xs text-gray-500">Dual-Role Compensation & Cash Floor Protected</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-[10px] font-bold tracking-wider">
                            <th class="py-3 px-4">Kuartal</th>
                            <th class="py-3 px-4">Rentang Bulan</th>
                            <th class="py-3 px-4 text-right">Pendapatan</th>
                            <th class="py-3 px-4 text-right">Laba Bersih</th>
                            <th class="py-3 px-4 text-right">Saldo Kas Akhir</th>
                            <th class="py-3 px-4 text-center">Proteksi Kas (15 Juta)</th>
                            <th class="py-3 px-4 text-center">Status Dividen RUPS</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($closings as $closing)
                        @php
                            $monthsLabel = \Carbon\Carbon::parse($closing->quarter_months[0] . '-01')->translatedFormat('M Y') . ' - ' . \Carbon\Carbon::parse($closing->quarter_months[2] . '-01')->translatedFormat('M Y');
                            $distribution = $closing->profitDistribution;
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition-colors">
                            <td class="py-3.5 px-4 font-mono font-bold text-gray-900 text-sm">
                                {{ $closing->period_label }}
                            </td>
                            <td class="py-3.5 px-4 text-gray-600 font-medium">
                                {{ $monthsLabel }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-medium text-gray-900">
                                Rp {{ number_format($closing->total_revenue, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <span class="font-bold {{ $closing->net_profit >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                                    Rp {{ number_format($closing->net_profit, 0, ',', '.') }}
                                </span>
                                <div class="text-[10px] text-gray-500">Margin: {{ $closing->net_profit_margin }}%</div>
                            </td>
                            <td class="py-3.5 px-4 text-right font-bold text-gray-900">
                                Rp {{ number_format($closing->ending_cash_balance, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($closing->isCashFloorMet())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                        Aman (+Rp {{ number_format($closing->cash_surplus_above_floor, 0, ',', '.') }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800 border border-red-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>
                                        Di Bawah Batas Rp 15Jt
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if(!$distribution)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-700">
                                        Belum Disahkan
                                    </span>
                                @elseif($distribution->status === 'distributed')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        Tuntas Ditransfer (Rp {{ number_format($distribution->distributable_profit, 0, ',', '.') }})
                                    </span>
                                @elseif($distribution->status === 'approved')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        Disahkan (Rp {{ number_format($distribution->distributable_profit, 0, ',', '.') }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                        Draft RUPS
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <a href="{{ route('finance.rups.show', $closing->id) }}" class="inline-flex items-center px-3 py-1 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-blue-800 transition-colors shadow-2xs">
                                    <span>Evaluasi & RUPS</span>
                                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-500">Belum ada konsolidasi closing kuartalan.</p>
                                <p class="text-xs text-gray-400 mt-1">Klik tombol "Konsolidasi Kuartal Baru" di atas untuk membuat evaluasi triwulanan.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($closings->hasPages())
            <div class="p-4 border-t border-gray-200">
                {{ $closings->links() }}
            </div>
            @endif
        </div>

        <!-- Master Shareholders / Founder Directory -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between bg-gray-50/50">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Bagan Kepemilikan Saham & Rekening Mitra Pendiri</h2>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                        {{ $shareholders->count() }} Pendiri
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('finance.shareholders.index') }}" class="inline-flex items-center px-3 py-1 bg-white border border-gray-300 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-50 shadow-2xs transition-colors">
                        <svg class="w-3.5 h-3.5 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        </svg>
                        Kelola Data Pemegang Saham (CRUD)
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-[10px] font-bold tracking-wider">
                            <th class="py-3 px-4">Nama Pendiri / Shareholder</th>
                            <th class="py-3 px-4">Kontak</th>
                            <th class="py-3 px-4">Bank & Nomor Rekening</th>
                            <th class="py-3 px-4 text-right">Lembar Saham</th>
                            <th class="py-3 px-4 text-right">Porsi Kepemilikan</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($shareholders as $sh)
                        <tr class="hover:bg-gray-50/50">
                            <td class="py-3 px-4">
                                <div class="font-bold text-gray-900 text-sm">{{ $sh->name }}</div>
                                <div class="text-[11px] text-gray-500">{{ $sh->user ? $sh->user->role : 'Mitra Pendiri' }}</div>
                            </td>
                            <td class="py-3 px-4 text-gray-600">
                                <div>{{ $sh->email }}</div>
                                <div class="text-[11px] text-gray-400">{{ $sh->phone ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-gray-800">{{ $sh->bank_name }} - {{ $sh->bank_account_number }}</div>
                                <div class="text-[11px] text-gray-500">a.n. {{ $sh->bank_account_holder }}</div>
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-gray-900">
                                {{ number_format($sh->shares_count, 0, ',', '.') }} lembar
                            </td>
                            <td class="py-3 px-4 text-right">
                                <span class="font-black text-primary text-sm">{{ number_format($sh->ownership_percentage, 2) }}%</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($sh->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800">Aktif</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-500">Non-Aktif</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Generate Konsolidasi Kuartal -->
    <div x-show="showGenModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showGenModal" @click="showGenModal = false" class="fixed inset-0 bg-gray-900/60 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showGenModal" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200">
                <form action="{{ route('finance.rups.generate') }}" method="POST">
                    @csrf
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-center gap-3 pb-4 border-b border-gray-100">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-primary flex items-center justify-center font-bold">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900" id="modal-title">Konsolidasi Closing Kuartal</h3>
                                <p class="text-xs text-gray-500">Agregasi 3 bulan laporan keuangan untuk evaluasi RUPS.</p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="year" class="block text-xs font-bold text-gray-700 uppercase mb-1">Tahun Kalender</label>
                                <select name="year" id="year" x-model="genForm.year" required class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs">
                                    @for($y = (int)date('Y') + 1; $y >= 2024; $y--)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>

                            <div>
                                <label for="quarter" class="block text-xs font-bold text-gray-700 uppercase mb-1">Kuartal (Triwulan)</label>
                                <select name="quarter" id="quarter" x-model="genForm.quarter" required class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs">
                                    <option value="1">Q1 (Januari - Maret)</option>
                                    <option value="2">Q2 (April - Juni)</option>
                                    <option value="3">Q3 (Juli - September)</option>
                                    <option value="4">Q4 (Oktober - Desember)</option>
                                </select>
                            </div>

                            <div class="p-3 bg-blue-50/80 rounded-xl border border-blue-200 text-xs text-blue-900 space-y-1">
                                <div class="font-bold flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Otomatisasi Engine
                                </div>
                                <p class="text-blue-800 text-[11px] leading-relaxed">
                                    Sistem akan otomatis menghitung total omzet, HPP man-hour teknis, OPEX manajerial & operasional, serta saldo kas awal & akhir dari 3 bulan terkait.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3 rounded-b-2xl border-t border-gray-100">
                        <button type="button" @click="showGenModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-100 transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-primary text-white rounded-lg text-xs font-bold hover:bg-blue-800 transition-colors shadow-sm">
                            Kompilasi Kuartal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
