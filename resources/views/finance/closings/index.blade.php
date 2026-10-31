@extends('layouts.app')

@section('title', 'Closing Laporan Keuangan Bulanan - Wirodayan Digital')

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" x-data="{ showGenModal: false, genForm: { period_month: '{{ date('Y-m') }}', opening_cash_balance: '{{ $latestClosing ? $latestClosing->ending_cash_balance : 0 }}', auditor_notes: '' } }">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between pb-6 border-b border-gray-200 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Laporan Closing Keuangan Bulanan
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Kompilasi otomatis laporan Laba Rugi, beban COGS man-hour & infrastruktur, OPEX, serta rekonsiliasi arus kas bulanan.
            </p>
        </div>
        <div class="flex items-center gap-3">
            @can('finance.closings.generate')
            <button @click="showGenModal = true" type="button" class="inline-flex items-center px-4 py-2 bg-primary text-white font-medium rounded-lg text-sm hover:bg-blue-800 shadow-sm transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
                Generate Closing Bulanan
            </button>
            @endcan
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

    <!-- Financial KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <span class="text-xs font-semibold text-gray-500 uppercase">Total Revenue</span>
            <div class="text-2xl font-black text-gray-900 mt-2">Rp {{ number_format($totalRevenueAll, 0, ',', '.') }}</div>
            <div class="text-[10px] text-gray-400 mt-1">Akumulasi pendapatan closing</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <span class="text-xs font-semibold text-gray-500 uppercase">Total HPP / COGS</span>
            <div class="text-2xl font-black text-red-600 mt-2">Rp {{ number_format($totalCogsAll, 0, ',', '.') }}</div>
            <div class="text-[10px] text-gray-400 mt-1">Beban langsung proyek</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <span class="text-xs font-semibold text-gray-500 uppercase">Gross Profit</span>
            <div class="text-2xl font-black text-indigo-700 mt-2">Rp {{ number_format($totalGrossProfitAll, 0, ',', '.') }}</div>
            <div class="text-[10px] text-gray-400 mt-1">Revenue dikurangi COGS</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <span class="text-xs font-semibold text-gray-500 uppercase">Net Profit</span>
            <div class="text-2xl font-black {{ $totalNetProfitAll >= 0 ? 'text-emerald-600' : 'text-red-600' }} mt-2">
                Rp {{ number_format($totalNetProfitAll, 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-gray-400 mt-1">Setelah dikurangi OPEX</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <span class="text-xs font-semibold text-gray-500 uppercase">Ending Cash Terakhir</span>
            <div class="text-2xl font-black text-primary mt-2">
                Rp {{ number_format($latestClosing ? $latestClosing->ending_cash_balance : 0, 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-gray-400 mt-1">Saldo akhir kas berjalan</div>
        </div>
    </div>

    <!-- Closings Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-sm font-bold text-gray-800">Riwayat Buku Closing Laporan Keuangan</h2>
            <span class="text-xs text-gray-500">{{ $closings->total() }} Periode Tercatat</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-[10px] font-semibold tracking-wider">
                        <th class="py-3 px-4">Periode</th>
                        <th class="py-3 px-4 text-right">Saldo Kas Awal</th>
                        <th class="py-3 px-4 text-right">Pendapatan</th>
                        <th class="py-3 px-4 text-right">HPP (COGS)</th>
                        <th class="py-3 px-4 text-right">Gross Profit</th>
                        <th class="py-3 px-4 text-right">OPEX</th>
                        <th class="py-3 px-4 text-right">Net Profit</th>
                        <th class="py-3 px-4 text-center">Net Margin</th>
                        <th class="py-3 px-4 text-right">Saldo Kas Akhir</th>
                        <th class="py-3 px-4 text-center">Status Audit</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($closings as $closing)
                    <tr class="hover:bg-blue-50/40 transition-colors">
                        <td class="py-3 px-4">
                            <a href="{{ route('finance.closings.show', $closing) }}" class="font-extrabold text-primary hover:underline text-sm">
                                {{ \Carbon\Carbon::parse($closing->period_month . '-01')->translatedFormat('F Y') }}
                            </a>
                            <div class="text-[10px] text-gray-400 font-mono">{{ $closing->period_month }}</div>
                        </td>
                        <td class="py-3 px-4 text-right font-medium text-gray-700">
                            Rp {{ number_format($closing->opening_cash_balance, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-gray-900">
                            Rp {{ number_format($closing->total_revenue, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right text-red-600 font-medium">
                            Rp {{ number_format($closing->total_cogs, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-indigo-700">
                            Rp {{ number_format($closing->gross_profit, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right text-gray-700">
                            Rp {{ number_format($closing->total_opex, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-black {{ $closing->net_profit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            Rp {{ number_format($closing->net_profit, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $closing->net_profit_margin >= 20 ? 'bg-emerald-100 text-emerald-800' : ($closing->net_profit_margin >= 0 ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                                {{ $closing->net_profit_margin }}%
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right font-black text-gray-900">
                            Rp {{ number_format($closing->ending_cash_balance, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($closing->audit_status === 'approved')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                <span class="w-1.5 h-1.5 mr-1 rounded-full bg-emerald-500"></span> APPROVED
                            </span>
                            @elseif($closing->audit_status === 'audited')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                <span class="w-1.5 h-1.5 mr-1 rounded-full bg-blue-500"></span> AUDITED
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                <span class="w-1.5 h-1.5 mr-1 rounded-full bg-amber-500"></span> DRAFT
                            </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('finance.closings.show', $closing) }}" class="inline-flex items-center px-2.5 py-1 bg-white border border-gray-300 rounded text-[11px] font-semibold text-gray-700 hover:bg-gray-50 shadow-xs">
                                    Auditor
                                </a>
                                @can('finance.closings.export')
                                <a href="{{ route('finance.closings.pdf', $closing) }}" target="_blank" class="p-1 text-gray-500 hover:text-red-600 rounded hover:bg-gray-100" title="Cetak PDF">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="py-8 text-center text-gray-500">
                            Belum ada laporan closing bulanan yang dibuat. Klik tombol di atas untuk generate periode baru.
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

    <!-- Generator Modal -->
    <div x-show="showGenModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showGenModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form action="{{ route('finance.closings.generate') }}" method="POST">
                    @csrf
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-base font-bold text-gray-900">Generate Closing Keuangan Bulanan</h3>
                        <button type="button" @click="showGenModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Pilih Periode Bulan (YYYY-MM) <span class="text-red-500">*</span></label>
                            <input type="month" name="period_month" x-model="genForm.period_month" required class="w-full text-xs rounded-lg border-gray-300 focus:ring-primary">
                            <span class="text-[10px] text-gray-500 mt-1 block">Engine akan otomatis mengagregasi seluruh pembayaran invoice, beban kerja proyek, pengeluaran server, dan iklan pada rentang bulan ini.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Saldo Kas Awal Periode (Rp) <span class="text-red-500">*</span></label>
                            <input type="number" step="1000" min="0" name="opening_cash_balance" x-model="genForm.opening_cash_balance" required class="w-full text-xs rounded-lg border-gray-300 focus:ring-primary">
                            <span class="text-[10px] text-gray-500 mt-1 block">Diambil dari saldo kas akhir periode sebelumnya atau setoran awal rekening.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Catatan Inisiasi Auditor</label>
                            <textarea name="auditor_notes" x-model="genForm.auditor_notes" rows="2" placeholder="Catatan rekonsiliasi awal..." class="w-full text-xs rounded-lg border-gray-300 focus:ring-primary"></textarea>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex justify-end gap-2">
                        <button type="button" @click="showGenModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-white">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-semibold hover:bg-blue-800">Mulai Generate Engine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
