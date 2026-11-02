@extends('layouts.app')

@section('title', 'Manajemen Pemegang Saham (Shareholders) - Wiro Management')

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6"
     x-data="{
         showCreateModal: false,
         showEditModal: false,
         editForm: {
             id: '',
             name: '',
             email: '',
             phone: '',
             shares_count: 0,
             capital_amount: 0,
             ownership_percentage: 0,
             bank_name: '',
             bank_account_number: '',
             bank_account_holder: '',
             user_id: '',
             is_active: true
         },
         openEdit(sh) {
             this.editForm = {
                 id: sh.id,
                 name: sh.name,
                 email: sh.email,
                 phone: sh.phone || '',
                 shares_count: sh.shares_count,
                 capital_amount: sh.capital_amount || 0,
                 ownership_percentage: sh.ownership_percentage,
                 bank_name: sh.bank_name,
                 bank_account_number: sh.bank_account_number,
                 bank_account_holder: sh.bank_account_holder,
                 user_id: sh.user_id || '',
                 is_active: Boolean(sh.is_active)
             };
             this.showEditModal = true;
         }
     }">

    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between pb-4 border-b border-gray-200 gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <a href="{{ route('finance.overview') }}" class="hover:text-primary">Finance</a>
                <span>/</span>
                <a href="{{ route('finance.rups.index') }}" class="hover:text-primary">RUPS Triwulanan</a>
                <span>/</span>
                <span class="font-bold text-gray-700">Pemegang Saham</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Manajemen Pemegang Saham (Founders)
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Kelola data kepemilikan modal, persentase saham, dan rekening bank mitra pendiri untuk pembagian dividen RUPS.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('finance.rups.index') }}" class="inline-flex items-center px-3.5 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors shadow-2xs">
                <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke RUPS
            </a>
            @can('finance.rups.manage')
            <button @click="showCreateModal = true" type="button" class="inline-flex items-center px-4 py-2 bg-primary text-white font-bold rounded-lg text-xs hover:bg-blue-800 transition-colors shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Tambah Pemegang Saham
            </button>
            @endcan
        </div>
    </div>

    <!-- Flash Messages -->
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

    @if(session('error'))
    <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 flex items-center justify-between shadow-xs">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-red-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium">{{ session('error') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">&times;</button>
    </div>
    @endif

    @if($errors->any())
    <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 shadow-xs">
        <div class="font-bold text-xs uppercase mb-1">Terjadi kesalahan validasi:</div>
        <ul class="list-disc list-inside text-xs space-y-0.5">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Equity Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Modal Disetor (Rp)</span>
            <div class="text-2xl font-black text-gray-900 mt-2 font-mono">
                Rp {{ number_format($totalCapitalAmount, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-gray-400 mt-1">Akumulasi modal disetor & reinvestasi</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Lembar Saham</span>
            <div class="text-2xl font-black text-gray-900 mt-2 font-mono">
                {{ number_format($totalSharesCount, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-gray-400 mt-1">Total saham modal beredar</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Porsi Kepemilikan</span>
            <div class="text-2xl font-black text-primary mt-2 font-mono">
                {{ number_format($totalOwnershipPercentage, 2) }}%
            </div>
            <div class="text-[11px] mt-1 flex items-center gap-1">
                @if(abs($totalOwnershipPercentage - 100.0) < 0.01)
                    <span class="text-emerald-600 font-bold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Tepat 100% (Seimbang)
                    </span>
                @else
                    <span class="text-amber-600 font-bold">
                        Belum 100% (Selisih: {{ number_format(100 - $totalOwnershipPercentage, 2) }}%)
                    </span>
                @endif
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Pendiri Aktif</span>
            <div class="text-2xl font-black text-emerald-600 mt-2">
                {{ $totalActiveCount }} Pendiri
            </div>
            <div class="text-[11px] text-gray-400 mt-1">Berhak menerima dividen kuartal</div>
        </div>
    </div>

    <!-- Shareholders Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between bg-gray-50/50">
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Daftar Pemegang Saham Wirodev</h2>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                    {{ $shareholders->total() }} Terdaftar
                </span>
            </div>
            <span class="text-xs text-gray-500">Akun tujuan transfer dividen per RUPS</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-100/70 border-b border-gray-200 text-gray-600 uppercase text-[10px] font-bold tracking-wider">
                        <th class="py-3 px-4">Nama Pendiri</th>
                        <th class="py-3 px-4">Kontak (Email / Telp)</th>
                        <th class="py-3 px-4">Rekening Bank</th>
                        <th class="py-3 px-4 text-right">Lembar Saham</th>
                        <th class="py-3 px-4 text-right text-emerald-800 bg-emerald-50/40 font-bold">Modal Disetor (Rp)</th>
                        <th class="py-3 px-4 text-right bg-blue-50/50 text-primary font-black">Porsi Modal (%)</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($shareholders as $sh)
                    <tr class="hover:bg-blue-50/30 transition-colors">
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                {{ $sh->name }}
                                @if($sh->user)
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase bg-gray-100 text-gray-600 border border-gray-200">
                                        Akun: {{ $sh->user->role }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3.5 px-4 text-gray-600">
                            <div class="font-medium text-gray-800">{{ $sh->email }}</div>
                            <div class="text-[11px] text-gray-400">{{ $sh->phone ?? '-' }}</div>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-gray-800 font-mono">{{ $sh->bank_name }} - {{ $sh->bank_account_number }}</div>
                            <div class="text-[11px] text-gray-500">a.n. {{ $sh->bank_account_holder }}</div>
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono font-bold text-gray-800 text-xs">
                            {{ number_format($sh->shares_count, 0, ',', '.') }} lembar
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-700 bg-emerald-50/20 text-xs">
                            Rp {{ number_format($sh->capital_amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-right bg-blue-50/30 font-mono font-black text-primary text-sm">
                            {{ number_format($sh->ownership_percentage, 2) }}%
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($sh->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                    Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500 border border-gray-200">
                                    Non-Aktif
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @can('finance.rups.manage')
                                <button type="button" @click="openEdit({{ $sh->toJson() }})" class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors" title="Edit Data">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>

                                <form action="{{ route('finance.shareholders.destroy', $sh->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data pemegang saham {{ addslashes($sh->name) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-400">
                            Belum ada pemegang saham terdaftar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($shareholders->hasPages())
        <div class="p-4 border-t border-gray-200">
            {{ $shareholders->links() }}
        </div>
        @endif
    </div>

    <!-- Buku Besar Mutasi Modal (Capital Ledger) -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between bg-gradient-to-r from-slate-50 to-blue-50/30">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Buku Besar Mutasi Modal Pendiri (Capital Ledger)</h2>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                    {{ $recentCapitalHistories->count() }} Mutasi Terakhir
                </span>
            </div>
            <span class="text-xs text-gray-500">Rekam jejak injeksi modal & reinvestasi dividen RUPS</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-100/70 border-b border-gray-200 text-gray-600 uppercase text-[10px] font-bold tracking-wider">
                        <th class="py-3 px-4">Kuartal Efektif</th>
                        <th class="py-3 px-4">Nama Pendiri</th>
                        <th class="py-3 px-4">Jenis Transaksi</th>
                        <th class="py-3 px-4 text-right">Nominal Mutasi</th>
                        <th class="py-3 px-4 text-right">Modal Sebelum</th>
                        <th class="py-3 px-4 text-right font-black text-gray-900">Modal Setelah</th>
                        <th class="py-3 px-4 text-center font-bold text-primary">Porsi Baru (%)</th>
                        <th class="py-3 px-4">Keterangan / Dokumen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentCapitalHistories as $hist)
                    <tr class="hover:bg-blue-50/20 transition-colors">
                        <td class="py-3 px-4 font-mono font-bold text-gray-800">
                            <span class="px-2 py-0.5 rounded text-[10px] bg-slate-100 text-slate-800 border border-slate-200">
                                {{ $hist->effective_quarter_label }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-bold text-gray-900">
                            {{ $hist->shareholder->name }}
                        </td>
                        <td class="py-3 px-4">
                            @if($hist->transaction_type === 'dividend_reinvestment')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    Reinvestasi Dividen
                                </span>
                            @elseif($hist->transaction_type === 'initial_balance')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                    Saldo Awal
                                </span>
                            @elseif($hist->transaction_type === 'fresh_capital_injection')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                    Injeksi Modal Baru
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800 border border-red-200">
                                    Penarikan Modal
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">
                            + Rp {{ number_format($hist->amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-gray-600">
                            Rp {{ number_format($hist->opening_capital, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-black text-gray-900">
                            Rp {{ number_format($hist->closing_capital, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-center font-mono font-black text-primary bg-blue-50/30">
                            {{ number_format($hist->closing_ownership_percentage, 2) }}%
                        </td>
                        <td class="py-3 px-4 text-gray-500 text-[11px]">
                            <div>{{ $hist->notes ?? '-' }}</div>
                            @if($hist->quarterlyClosing)
                                <a href="{{ route('finance.rups.show', $hist->quarterlyClosing->id) }}" class="text-primary hover:underline text-[10px] font-bold">
                                    Lihat RUPS {{ $hist->quarterlyClosing->period_label }} &rarr;
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-6 text-center text-gray-400">
                            Belum ada riwayat mutasi modal founder tercatat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Create Shareholder -->
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showCreateModal" @click="showCreateModal = false" class="fixed inset-0 bg-gray-900/60 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showCreateModal" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200">
                <form action="{{ route('finance.shareholders.store') }}" method="POST">
                    @csrf
                    <div class="bg-white px-6 pt-6 pb-4 space-y-4">
                        <div class="flex items-center gap-3 pb-4 border-b border-gray-100">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-primary flex items-center justify-center font-bold">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Tambah Pemegang Saham Baru</h3>
                                <p class="text-xs text-gray-500">Daftarkan mitra pendiri ke sistem alokasi dividen.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label for="name" class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Lengkap Pendiri</label>
                                <input type="text" id="name" name="name" required class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs" placeholder="e.g. Titan Anggaraksa">
                            </div>

                            <div>
                                <label for="email" class="block text-xs font-bold text-gray-700 uppercase mb-1">Email</label>
                                <input type="email" id="email" name="email" required class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs" placeholder="titan@wirodev.com">
                            </div>

                            <div>
                                <label for="phone" class="block text-xs font-bold text-gray-700 uppercase mb-1">Nomor Telepon / WA</label>
                                <input type="text" id="phone" name="phone" class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs" placeholder="08123456789">
                            </div>

                            <div>
                                <label for="ownership_percentage" class="block text-xs font-bold text-gray-700 uppercase mb-1">Persentase Saham (%)</label>
                                <input type="number" step="0.01" min="0" max="100" id="ownership_percentage" name="ownership_percentage" required class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs" placeholder="95.00">
                            </div>

                            <div>
                                <label for="shares_count" class="block text-xs font-bold text-gray-700 uppercase mb-1">Jumlah Lembar Saham</label>
                                <input type="number" id="shares_count" name="shares_count" required min="0" class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs" placeholder="95000">
                            </div>

                            <div>
                                <label for="capital_amount" class="block text-xs font-bold text-gray-700 uppercase mb-1">Modal Disetor (Rp)</label>
                                <input type="number" step="0.01" min="0" id="capital_amount" name="capital_amount" class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs" placeholder="95000000">
                            </div>

                            <div>
                                <label for="bank_name" class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Bank</label>
                                <input type="text" id="bank_name" name="bank_name" required class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs" placeholder="BCA / Mandiri / BNI">
                            </div>

                            <div>
                                <label for="bank_account_number" class="block text-xs font-bold text-gray-700 uppercase mb-1">Nomor Rekening</label>
                                <input type="text" id="bank_account_number" name="bank_account_number" required class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs" placeholder="8830123456">
                            </div>

                            <div class="sm:col-span-2">
                                <label for="bank_account_holder" class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Pemilik Rekening</label>
                                <input type="text" id="bank_account_holder" name="bank_account_holder" required class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs" placeholder="Atas nama di buku rekening">
                            </div>

                            <div>
                                <label for="user_id" class="block text-xs font-bold text-gray-700 uppercase mb-1">Akun User Sistem (Opsional)</label>
                                <select id="user_id" name="user_id" class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs">
                                    <option value="">-- Tidak Terhubung --</option>
                                    @foreach($internalUsers as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-center pt-5">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-primary shadow-xs focus:ring-primary">
                                    <span class="ml-2 text-xs font-bold text-gray-700">Status Aktif</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3 rounded-b-2xl border-t border-gray-100">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-100 transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-primary text-white rounded-lg text-xs font-bold hover:bg-blue-800 transition-colors shadow-sm">
                            Simpan Pemegang Saham
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Shareholder -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="edit-modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showEditModal" @click="showEditModal = false" class="fixed inset-0 bg-gray-900/60 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showEditModal" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200">
                <form :action="'{{ url('finance/shareholders') }}/' + editForm.id" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="bg-white px-6 pt-6 pb-4 space-y-4">
                        <div class="flex items-center gap-3 pb-4 border-b border-gray-100">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-primary flex items-center justify-center font-bold">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900" id="edit-modal-title">Edit Data Pemegang Saham</h3>
                                <p class="text-xs text-gray-500">Perbarui nomor rekening atau porsi saham modal.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label for="edit_name" class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Lengkap Pendiri</label>
                                <input type="text" id="edit_name" name="name" x-model="editForm.name" required class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs">
                            </div>

                            <div>
                                <label for="edit_email" class="block text-xs font-bold text-gray-700 uppercase mb-1">Email</label>
                                <input type="email" id="edit_email" name="email" x-model="editForm.email" required class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs">
                            </div>

                            <div>
                                <label for="edit_phone" class="block text-xs font-bold text-gray-700 uppercase mb-1">Nomor Telepon / WA</label>
                                <input type="text" id="edit_phone" name="phone" x-model="editForm.phone" class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs">
                            </div>

                            <div>
                                <label for="edit_ownership_percentage" class="block text-xs font-bold text-gray-700 uppercase mb-1">Persentase Saham (%)</label>
                                <input type="number" step="0.01" min="0" max="100" id="edit_ownership_percentage" name="ownership_percentage" x-model="editForm.ownership_percentage" required class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs">
                            </div>

                            <div>
                                <label for="edit_shares_count" class="block text-xs font-bold text-gray-700 uppercase mb-1">Jumlah Lembar Saham</label>
                                <input type="number" id="edit_shares_count" name="shares_count" x-model="editForm.shares_count" required min="0" class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs">
                            </div>

                            <div>
                                <label for="edit_capital_amount" class="block text-xs font-bold text-gray-700 uppercase mb-1">Modal Disetor (Rp)</label>
                                <input type="number" step="0.01" min="0" id="edit_capital_amount" name="capital_amount" x-model="editForm.capital_amount" class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs">
                            </div>

                            <div>
                                <label for="edit_bank_name" class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Bank</label>
                                <input type="text" id="edit_bank_name" name="bank_name" x-model="editForm.bank_name" required class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs">
                            </div>

                            <div>
                                <label for="edit_bank_account_number" class="block text-xs font-bold text-gray-700 uppercase mb-1">Nomor Rekening</label>
                                <input type="text" id="edit_bank_account_number" name="bank_account_number" x-model="editForm.bank_account_number" required class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs">
                            </div>

                            <div class="sm:col-span-2">
                                <label for="edit_bank_account_holder" class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Pemilik Rekening</label>
                                <input type="text" id="edit_bank_account_holder" name="bank_account_holder" x-model="editForm.bank_account_holder" required class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs">
                            </div>

                            <div>
                                <label for="edit_user_id" class="block text-xs font-bold text-gray-700 uppercase mb-1">Akun User Sistem (Opsional)</label>
                                <select id="edit_user_id" name="user_id" x-model="editForm.user_id" class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary shadow-xs">
                                    <option value="">-- Tidak Terhubung --</option>
                                    @foreach($internalUsers as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-center pt-5">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" x-model="editForm.is_active" class="rounded border-gray-300 text-primary shadow-xs focus:ring-primary">
                                    <span class="ml-2 text-xs font-bold text-gray-700">Status Aktif</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3 rounded-b-2xl border-t border-gray-100">
                        <button type="button" @click="showEditModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-100 transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-primary text-white rounded-lg text-xs font-bold hover:bg-blue-800 transition-colors shadow-sm">
                            Perbarui Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
