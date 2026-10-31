@extends('layouts.app')

@section('title', 'Auditor Closing Finansial: ' . $closing->period_month . ' - Wirodayan Digital')

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" x-data="{ activeTab: 'statements', showReAuditModal: false }">
    <!-- Header with Action Buttons -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between pb-6 border-b border-gray-200 mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('finance.closings.index') }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Daftar Closing Keuangan
                </a>
            </div>
            <div class="flex items-center gap-3 mt-2">
                <h1 class="text-2xl font-bold text-gray-900">
                    Closing Periode {{ \Carbon\Carbon::parse($closing->period_month . '-01')->translatedFormat('F Y') }}
                </h1>
                @if($closing->audit_status === 'approved')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                    <span class="w-2 h-2 mr-1.5 rounded-full bg-emerald-500"></span> APPROVED
                </span>
                @elseif($closing->audit_status === 'audited')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-blue-100 text-blue-800 border border-blue-200">
                    <span class="w-2 h-2 mr-1.5 rounded-full bg-blue-500"></span> AUDITED
                </span>
                @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-200">
                    <span class="w-2 h-2 mr-1.5 rounded-full bg-amber-500"></span> DRAFT
                </span>
                @endif
            </div>
            <p class="text-xs text-gray-500 mt-1">
                Rekonsiliasi buku kas & perhitungan audit laba rugi internal Wirodayan Digital Ecosystem.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Recalculate button -->
            @can('finance.closings.generate')
            <form action="{{ route('finance.closings.generate') }}" method="POST" onsubmit="return confirm('Recalculate laporan periode ini? Ledger item akan diperbarui ulang.');">
                @csrf
                <input type="hidden" name="period_month" value="{{ $closing->period_month }}">
                <input type="hidden" name="opening_cash_balance" value="{{ $closing->opening_cash_balance }}">
                <input type="hidden" name="auditor_notes" value="{{ $closing->auditor_notes }}">
                <button type="submit" class="inline-flex items-center px-3.5 py-2 bg-white border border-gray-300 text-gray-700 font-medium rounded-lg text-xs hover:bg-gray-50 shadow-xs">
                    <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Recalculate Engine
                </button>
            </form>
            @endcan

            <!-- Audit status button -->
            @can('finance.closings.audit')
            <button @click="showReAuditModal = true" type="button" class="inline-flex items-center px-3.5 py-2 bg-indigo-50 border border-indigo-200 text-indigo-700 font-semibold rounded-lg text-xs hover:bg-indigo-100 shadow-xs">
                <svg class="w-4 h-4 mr-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Audit & Review Status
            </button>
            @endcan

            <!-- Export PDF button -->
            @can('finance.closings.export')
            <a href="{{ route('finance.closings.pdf', $closing) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-primary text-white font-semibold rounded-lg text-xs hover:bg-blue-800 shadow-sm transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Cetak PDF Resmi
            </a>
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

    <!-- Executive Summary Dashboard Cards (October 2026 Spreadsheet Layout) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase">Total Revenue</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">Invoiced</span>
            </div>
            <div class="text-2xl font-black text-gray-900 mt-2">Rp {{ number_format($closing->total_revenue, 0, ',', '.') }}</div>
            <div class="text-xs text-gray-500 mt-1">Pembayaran klien terverifikasi</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase">HPP Proyek (COGS)</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800">{{ $closing->cogs_ratio }}% Rasio</span>
            </div>
            <div class="text-2xl font-black text-red-600 mt-2">Rp {{ number_format($closing->total_cogs, 0, ',', '.') }}</div>
            <div class="text-xs text-gray-500 mt-1">Tenaga kerja + infrastruktur/server</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase">Gross Profit</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800">Laba Kotor</span>
            </div>
            <div class="text-2xl font-black text-indigo-700 mt-2">Rp {{ number_format($closing->gross_profit, 0, ',', '.') }}</div>
            <div class="text-xs text-gray-500 mt-1">Margin: {{ $closing->total_revenue > 0 ? round(($closing->gross_profit / $closing->total_revenue) * 100, 1) : 0 }}%</div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase">Net Profit (Laba Bersih)</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-black {{ $closing->net_profit >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                    {{ $closing->net_profit_margin }}% Margin
                </span>
            </div>
            <div class="text-2xl font-black {{ $closing->net_profit >= 0 ? 'text-emerald-600' : 'text-red-600' }} mt-2">
                Rp {{ number_format($closing->net_profit, 0, ',', '.') }}
            </div>
            <div class="text-xs text-gray-500 mt-1">Setelah beban OPEX: Rp {{ number_format($closing->total_opex, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-6">
            <button @click="activeTab = 'statements'" :class="activeTab === 'statements' ? 'border-primary text-primary font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 font-medium'" class="py-3 px-1 border-b-2 text-sm transition-colors">
                Laporan Laba Rugi & Arus Kas
            </button>
            <button @click="activeTab = 'ledger'" :class="activeTab === 'ledger' ? 'border-primary text-primary font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 font-medium'" class="py-3 px-1 border-b-2 text-sm transition-colors">
                Rincian Item Jurnal & Buku Besar ({{ $closing->items->count() }})
            </button>
            <button @click="activeTab = 'audit'" :class="activeTab === 'audit' ? 'border-primary text-primary font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 font-medium'" class="py-3 px-1 border-b-2 text-sm transition-colors">
                Catatan Auditor & Rekonsiliasi Kas
            </button>
        </nav>
    </div>

    <!-- TAB 1: Financial Statements (Income Statement & Cash Flow) -->
    <div x-show="activeTab === 'statements'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- 1. Income Statement Table (Laporan Laba Rugi) -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                <div class="bg-gray-50 px-5 py-3 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Laporan Laba Rugi (Income Statement)</h2>
                    <span class="text-[11px] text-gray-500 font-mono">{{ $closing->period_month }}</span>
                </div>
                <div class="p-5 space-y-4">
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 text-sm">
                        <span class="font-bold text-gray-800">1. PENDAPATAN (REVENUE)</span>
                        <span class="font-black text-gray-900">Rp {{ number_format($closing->total_revenue, 0, ',', '.') }}</span>
                    </div>

                    <div class="pl-4 space-y-2 text-xs">
                        <div class="flex items-center justify-between text-gray-600">
                            <span>Beban Tenaga Kerja Proyek (Labor)</span>
                            <span class="font-medium">Rp {{ number_format($cogsItems->where('account_code', '5000-01-01')->sum('debit'), 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-gray-600">
                            <span>Beban Server & Infrastruktur Proyek</span>
                            <span class="font-medium">Rp {{ number_format($cogsItems->where('account_code', '5000-02-01')->sum('debit'), 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between py-2 border-b border-gray-100 text-sm bg-red-50/50 px-3 rounded-lg">
                        <span class="font-bold text-red-800">2. TOTAL BEBAN POKOK (HPP / COGS)</span>
                        <span class="font-black text-red-600">(Rp {{ number_format($closing->total_cogs, 0, ',', '.') }})</span>
                    </div>

                    <div class="flex items-center justify-between py-2 border-b-2 border-gray-200 text-sm bg-indigo-50/50 px-3 rounded-lg">
                        <span class="font-black text-indigo-900">3. LABA KOTOR (GROSS PROFIT)</span>
                        <span class="font-black text-indigo-700">Rp {{ number_format($closing->gross_profit, 0, ',', '.') }}</span>
                    </div>

                    <div class="pl-4 space-y-2 text-xs">
                        <div class="flex items-center justify-between text-gray-600">
                            <span>Beban Pemasaran & Iklan Berbayar</span>
                            <span class="font-medium">Rp {{ number_format($opexItems->where('account_code', '6000-01-01')->sum('debit'), 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-gray-600">
                            <span>Beban Utilitas & Operasional Kantor</span>
                            <span class="font-medium">Rp {{ number_format($opexItems->where('account_code', '6000-02-01')->sum('debit'), 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between py-2 border-b border-gray-100 text-sm bg-amber-50/50 px-3 rounded-lg">
                        <span class="font-bold text-amber-800">4. TOTAL BEBAN OPERASIONAL (OPEX)</span>
                        <span class="font-black text-amber-700">(Rp {{ number_format($closing->total_opex, 0, ',', '.') }})</span>
                    </div>

                    <div class="flex items-center justify-between py-3 px-3 rounded-xl {{ $closing->net_profit >= 0 ? 'bg-emerald-100/70 border border-emerald-300' : 'bg-red-100/70 border border-red-300' }}">
                        <div>
                            <span class="font-black text-sm {{ $closing->net_profit >= 0 ? 'text-emerald-900' : 'text-red-900' }}">5. LABA BERSIH (NET PROFIT)</span>
                            <div class="text-[10px] text-gray-600">Net Profit Margin: {{ $closing->net_profit_margin }}%</div>
                        </div>
                        <span class="text-lg font-black {{ $closing->net_profit >= 0 ? 'text-emerald-800' : 'text-red-800' }}">
                            Rp {{ number_format($closing->net_profit, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- 2. Cash Flow Reconciliation Table (Rekonsiliasi Arus Kas) -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="bg-gray-50 px-5 py-3 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Rekonsiliasi Arus Kas (Cash Flow)</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $closing->isReconciled() ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                            {{ $closing->isReconciled() ? 'BALANCED' : 'UNBALANCED' }}
                        </span>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 text-sm">
                            <span class="font-semibold text-gray-700">Saldo Kas & Bank Awal</span>
                            <span class="font-black text-gray-900">Rp {{ number_format($closing->opening_cash_balance, 0, ',', '.') }}</span>
                        </div>

                        <div class="flex items-center justify-between py-2 border-b border-gray-100 text-sm text-emerald-700 bg-emerald-50/50 px-3 rounded-lg">
                            <span class="font-bold">(+) Total Arus Kas Masuk (Inflow)</span>
                            <span class="font-black">+ Rp {{ number_format($closing->cash_inflow, 0, ',', '.') }}</span>
                        </div>

                        <div class="flex items-center justify-between py-2 border-b border-gray-100 text-sm text-red-700 bg-red-50/50 px-3 rounded-lg">
                            <span class="font-bold">(-) Total Arus Kas Keluar (Outflow)</span>
                            <span class="font-black">- Rp {{ number_format($closing->cash_outflow, 0, ',', '.') }}</span>
                        </div>

                        <div class="flex items-center justify-between py-3 border-t-2 border-gray-300 text-sm bg-blue-50/70 px-3 rounded-xl border border-blue-200">
                            <div>
                                <span class="font-black text-primary text-base">(=) Saldo Kas & Bank Akhir</span>
                                <div class="text-[10px] text-gray-500">Saldo Awal + Inflow - Outflow</div>
                            </div>
                            <span class="text-lg font-black text-primary">
                                Rp {{ number_format($closing->ending_cash_balance, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Auditor Metadata Footer -->
                <div class="bg-gray-50 p-4 border-t border-gray-200 text-xs text-gray-600 space-y-1">
                    <div class="flex items-center justify-between">
                        <span>Diaudit oleh:</span>
                        <span class="font-semibold text-gray-800">{{ $closing->auditor?->name ?? 'Belum Ditentukan' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Disetujui oleh:</span>
                        <span class="font-semibold text-gray-800">{{ $closing->approver?->name ?? 'Belum Ditentukan' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Terakhir diperbarui:</span>
                        <span class="font-mono text-gray-500">{{ $closing->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: Ledger Items (Buku Besar) -->
    <div x-show="activeTab === 'ledger'" x-cloak class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-[10px] font-semibold tracking-wider">
                        <th class="py-3 px-4">Kode Akun</th>
                        <th class="py-3 px-4">Nama Akun & Kategori</th>
                        <th class="py-3 px-4">Terkait Proyek</th>
                        <th class="py-3 px-4 text-right">Debit (Rp)</th>
                        <th class="py-3 px-4 text-right">Kredit (Rp)</th>
                        <th class="py-3 px-4">Keterangan / Memo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($closing->items as $item)
                    <tr class="hover:bg-blue-50/40 transition-colors">
                        <td class="py-3 px-4 font-mono font-bold text-primary">
                            {{ $item->account_code }}
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-bold text-gray-900">{{ $item->account_name }}</div>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase mt-0.5 {{ $item->category === 'revenue' ? 'bg-blue-100 text-blue-800' : ($item->category === 'cogs' ? 'bg-red-100 text-red-800' : ($item->category === 'opex' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-800')) }}">
                                {{ $item->category }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-gray-600">
                            {{ $item->project?->title ?? '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-medium text-gray-800">
                            {{ $item->debit > 0 ? number_format($item->debit, 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-medium text-gray-800">
                            {{ $item->credit > 0 ? number_format($item->credit, 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-gray-500 max-w-sm truncate">
                            {{ $item->notes ?: '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-500">Belum ada item buku besar.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 border-t-2 border-gray-200 font-bold text-gray-900 text-xs">
                    <tr>
                        <td colspan="3" class="py-3 px-4 text-right uppercase">Total Jurnal Periode:</td>
                        <td class="py-3 px-4 text-right">Rp {{ number_format($closing->items->sum('debit'), 0, ',', '.') }}</td>
                        <td class="py-3 px-4 text-right">Rp {{ number_format($closing->items->sum('credit'), 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- TAB 3: Auditor Remarks & Review Block -->
    <div x-show="activeTab === 'audit'" x-cloak class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs space-y-6">
        <div>
            <h2 class="text-base font-bold text-gray-900 mb-2">Evaluasi & Catatan Auditor Keuangan</h2>
            <p class="text-xs text-gray-500">
                Pencatatan temuan rekonsiliasi perbankan, kepatuhan bukti bayar, dan persetujuan direksi atas laporan keuangan.
            </p>
        </div>

        <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
            <h3 class="text-xs font-bold text-gray-700 uppercase mb-2">Catatan Auditor Saat Ini:</h3>
            <div class="text-xs text-gray-800 whitespace-pre-line leading-relaxed">
                {{ $closing->auditor_notes ?: 'Belum ada catatan auditor untuk periode closing ini.' }}
            </div>
        </div>

        @can('finance.closings.audit')
        <form action="{{ route('finance.closings.audit', $closing) }}" method="POST" class="p-5 bg-blue-50/50 rounded-xl border border-blue-200 space-y-4">
            @csrf
            <h3 class="text-sm font-bold text-primary">Perbarui Status Audit & Catatan</h3>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Status Audit Closing <span class="text-red-500">*</span></label>
                <select name="audit_status" required class="w-full text-xs rounded-lg border-gray-300 focus:ring-primary">
                    <option value="draft" {{ $closing->audit_status === 'draft' ? 'selected' : '' }}>DRAFT (Dalam Penyiapan & Verifikasi)</option>
                    <option value="audited" {{ $closing->audit_status === 'audited' ? 'selected' : '' }}>AUDITED (Telah Direkonsiliasi Oleh Auditor Internal)</option>
                    <option value="approved" {{ $closing->audit_status === 'approved' ? 'selected' : '' }}>APPROVED (Disetujui Direksi & Final)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Catatan Tambahan Auditor</label>
                <textarea name="auditor_notes" rows="3" class="w-full text-xs rounded-lg border-gray-300 focus:ring-primary" placeholder="Tuliskan catatan rekonsiliasi atau disposisi persetujuan...">{{ $closing->auditor_notes }}</textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-semibold hover:bg-blue-800 shadow-xs">
                    Simpan & Update Status Audit
                </button>
            </div>
        </form>
        @endcan
    </div>

    <!-- Modal Review Status (from Top Header Button) -->
    <div x-show="showReAuditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showReAuditModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form action="{{ route('finance.closings.audit', $closing) }}" method="POST">
                    @csrf
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-base font-bold text-gray-900">Audit & Review Status Closing</h3>
                        <button type="button" @click="showReAuditModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Status Audit</label>
                            <select name="audit_status" class="w-full text-xs rounded-lg border-gray-300">
                                <option value="draft" {{ $closing->audit_status === 'draft' ? 'selected' : '' }}>DRAFT</option>
                                <option value="audited" {{ $closing->audit_status === 'audited' ? 'selected' : '' }}>AUDITED</option>
                                <option value="approved" {{ $closing->audit_status === 'approved' ? 'selected' : '' }}>APPROVED</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Catatan Auditor</label>
                            <textarea name="auditor_notes" rows="3" class="w-full text-xs rounded-lg border-gray-300">{{ $closing->auditor_notes }}</textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex justify-end gap-2">
                        <button type="button" @click="showReAuditModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-white">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-semibold hover:bg-blue-800">Terapkan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
