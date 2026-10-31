@extends('layouts.app')

@section('title', 'Log Kerja & Biaya Tenaga Kerja - Wirodayan Digital')

@section('content')
<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" x-data="workLogManager()">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between pb-6 border-b border-gray-200 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Rekapitulasi & Monitoring Biaya Tenaga Kerja
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Pusat agregasi KPI man-hour, monitoring status pembayaran upah proyek (unpaid, pending, paid), audit finansial, dan approval massal lintas proyek.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @can('finance.work_logs.manage')
            <a href="{{ route('finance.rates.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 font-medium rounded-lg text-sm hover:bg-gray-50 shadow-xs transition-colors">
                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Rate Card Master
            </a>
            <button @click="openCreateModal()" type="button" class="inline-flex items-center px-4 py-2 bg-primary text-white font-medium rounded-lg text-sm hover:bg-blue-800 shadow-sm transition-colors cursor-pointer">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                + Catat Log Kerja
            </button>
            @endcan
        </div>
    </div>

    <!-- Workflow Banner -->
    <div class="mb-6 p-4 rounded-xl bg-blue-50/80 border border-blue-200 text-blue-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
        <div class="flex items-start sm:items-center gap-2">
            <span class="text-blue-600 font-bold whitespace-nowrap">💡 Info Alur Kerja:</span>
            <span>Pencatatan jam kerja dapat dilakukan di menu <strong>Detail Proyek</strong> maupun di halaman ini untuk keseluruhan proyek. Halaman ini digunakan sebagai pusat monitoring lintas proyek, audit, dan persetujuan (approval) pembayaran massal.</span>
        </div>
        <a href="{{ route('projects.index') }}" class="font-bold underline text-blue-700 hover:text-blue-900 whitespace-nowrap self-end sm:self-center">Ke Halaman Proyek &rarr;</a>
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
        <button type="button" onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800 cursor-pointer">&times;</button>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800">
        <div class="font-bold text-xs uppercase mb-1">Terjadi kesalahan validasi:</div>
        <ul class="list-disc pl-5 text-xs space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Financial KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Jam Kerja</span>
                <span class="p-2 rounded-lg bg-blue-50 text-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-gray-900">{{ number_format($totalHours, 1, ',', '.') }} Jam</div>
                <div class="text-xs text-gray-500 mt-1">Akumulasi durasi tervalidasi</div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">HPP Belum Dibayar</span>
                <span class="p-2 rounded-lg bg-red-50 text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-red-600">Rp {{ number_format($totalUnpaidNominal, 0, ',', '.') }}</div>
                <div class="text-xs text-gray-500 mt-1">Pending reimbursement / payroll</div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">HPP Terbayar</span>
                <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-emerald-600">Rp {{ number_format($totalPaidNominal, 0, ',', '.') }}</div>
                <div class="text-xs text-gray-500 mt-1">Sudah terealisasi & lunas</div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Biaya Tenaga Kerja</span>
                <span class="p-2 rounded-lg bg-indigo-50 text-indigo-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-gray-900">Rp {{ number_format($grandTotalNominal, 0, ',', '.') }}</div>
                <div class="text-xs text-gray-500 mt-1">Total beban HPP man-hour</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-xs mb-6">
        <form method="GET" action="{{ route('finance.work-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Proyek</label>
                <select name="project_id" class="w-full text-xs rounded-lg border-gray-300 shadow-xs focus:ring-primary focus:border-primary">
                    <option value="">Semua Proyek</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->title }} {{ $p->project_code ? '('.$p->project_code.')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Anggota Tim</label>
                <select name="user_id" class="w-full text-xs rounded-lg border-gray-300 shadow-xs focus:ring-primary focus:border-primary">
                    <option value="">Semua Anggota</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ strtoupper($u->role ?? 'staff') }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Status Pembayaran</label>
                <select name="payment_status" class="w-full text-xs rounded-lg border-gray-300 shadow-xs focus:ring-primary focus:border-primary">
                    <option value="">Semua Status</option>
                    <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid (Belum Dibayar)</option>
                    <option value="pending_approval" {{ request('payment_status') == 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid (Terbayar)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full text-xs rounded-lg border-gray-300 shadow-xs focus:ring-primary focus:border-primary">
            </div>

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full text-xs rounded-lg border-gray-300 shadow-xs focus:ring-primary focus:border-primary">
                </div>
                <button type="submit" class="px-3 py-2 bg-primary text-white rounded-lg text-xs font-medium hover:bg-blue-800 transition-colors shadow-xs cursor-pointer">
                    Filter
                </button>
                <a href="{{ route('finance.work-logs.index') }}" class="px-3 py-2 bg-gray-100 text-gray-600 rounded-lg text-xs font-medium hover:bg-gray-200 transition-colors shadow-xs">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Batch Actions Floating Bar -->
    @can('finance.work_logs.manage')
    <div x-show="selectedIds.length > 0" x-cloak class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary text-white text-xs font-bold" x-text="selectedIds.length"></span>
            <span class="text-xs font-medium text-gray-700">log kerja terpilih</span>
        </div>
        <form method="POST" action="{{ route('finance.work-logs.batch-status') }}" class="flex items-center gap-2">
            @csrf
            <template x-for="id in selectedIds" :key="id">
                <input type="hidden" name="log_ids[]" :value="id">
            </template>
            <select name="payment_status" class="text-xs rounded-lg border-gray-300 shadow-xs focus:ring-primary">
                <option value="paid">Set ke PAID (Terbayar)</option>
                <option value="pending_approval">Set ke PENDING APPROVAL</option>
                <option value="unpaid">Set ke UNPAID (Belum Bayar)</option>
            </select>
            <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" class="text-xs rounded-lg border-gray-300 shadow-xs">
            <button type="submit" class="px-3 py-1.5 bg-primary text-white text-xs font-medium rounded-lg hover:bg-blue-800 transition-colors cursor-pointer">
                Terapkan Massal
            </button>
        </form>
    </div>
    @endcan

    <!-- Table of Work Logs -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-[10px] font-semibold tracking-wider">
                        @can('finance.work_logs.manage')
                        <th class="py-3 px-4 w-8 text-center">
                            <input type="checkbox" @change="toggleSelectAll($event)" class="rounded border-gray-300 text-primary focus:ring-primary">
                        </th>
                        @endcan
                        <th class="py-3 px-4">Tanggal & Proyek</th>
                        <th class="py-3 px-4">Anggota & Posisi</th>
                        <th class="py-3 px-4 text-center">Jam Kerja</th>
                        <th class="py-3 px-4">Rate & Multiplier</th>
                        <th class="py-3 px-4 text-right">Nominal HPP</th>
                        <th class="py-3 px-4">Sumber Kas</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        @can('finance.work_logs.manage')
                        <th class="py-3 px-4 text-center">Aksi</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($workLogs as $log)
                    <tr class="hover:bg-blue-50/40 transition-colors">
                        @can('finance.work_logs.manage')
                        <td class="py-3 px-4 text-center">
                            <input type="checkbox" value="{{ $log->id }}" x-model="selectedIds" class="rounded border-gray-300 text-primary focus:ring-primary">
                        </td>
                        @endcan
                        <td class="py-3 px-4">
                            @php
                                $rangesData = is_array($log->date_ranges_data) ? $log->date_ranges_data : (is_string($log->date_ranges_data) ? json_decode($log->date_ranges_data, true) : null);
                                $rangeCount = is_array($rangesData) ? count($rangesData) : 0;
                                $daysCount = $log->days_count ?? ($log->end_date ? $log->work_date->diffInDays($log->end_date) + 1 : 1);
                            @endphp
                            <div class="font-semibold text-gray-900">
                                {{ $log->formatted_date_range }}
                                @if($daysCount > 1 || ($log->end_date && $log->end_date->ne($log->work_date)))
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 ml-1">
                                        @if($rangeCount > 1)
                                            ({{ $daysCount }} hari, {{ $rangeCount }} periode)
                                        @else
                                            ({{ $daysCount }} hari)
                                        @endif
                                    </span>
                                @endif
                            </div>

                            @if($rangeCount > 1)
                                <div class="mt-1 space-y-0.5 text-[10px] text-gray-500 font-normal">
                                    @foreach($rangesData as $r)
                                        @php
                                            $rStart = !empty($r['start_date']) ? \Carbon\Carbon::parse($r['start_date'])->format('d/m') : '';
                                            $rEnd = !empty($r['end_date']) ? \Carbon\Carbon::parse($r['end_date'])->format('d/m') : $rStart;
                                            $rDays = $r['days_count'] ?? 1;
                                            $rHpd = $r['hours_per_day'] ?? 8;
                                            $rTags = [];
                                            if (!empty($r['is_weekend'])) $rTags[] = 'Weekend';
                                            if (!empty($r['is_overtime'])) $rTags[] = 'Lembur';
                                            $tagStr = !empty($rTags) ? ' - ' . implode(', ', $rTags) : '';
                                        @endphp
                                        <div>&bull; {{ $rStart }} - {{ $rEnd }} ({{ $rDays }}h @ {{ $rHpd }} jam{{ $tagStr }})</div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="text-[11px] text-primary font-medium mt-1 flex items-center gap-1.5">
                                <span>{{ $log->project?->title ?? '-' }}</span>
                                @if($log->project?->project_code)
                                    <span class="text-[10px] text-gray-400 font-mono">({{ $log->project->project_code }})</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-semibold text-gray-800">{{ $log->user?->name ?? 'User #' . $log->user_id }}</div>
                            <div class="mt-0.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    {{ $log->role_in_project ?: 'Staff' }}
                                </span>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full font-bold bg-blue-100 text-blue-800 text-[11px]">
                                {{ number_format($log->hours_worked, 1, ',', '.') }} jam
                            </span>
                            @if(($log->days_count ?? 1) > 1 || ($log->end_date && $log->end_date->ne($log->work_date)))
                                <div class="text-[10px] text-gray-500 font-medium mt-0.5">
                                    ({{ number_format($log->hours_per_day ?? ($log->hours_worked / max(1, $log->days_count ?? 1)), 1, ',', '.') }} jam/hari &times; {{ $log->days_count ?? ($log->work_date->diffInDays($log->end_date) + 1) }} hari)
                                </div>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <div class="text-gray-900 font-medium">Rp {{ number_format($log->base_rate, 0, ',', '.') }}/jam</div>
                            <div class="flex flex-wrap gap-1 mt-1">
                                @if($log->is_weekend)
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800">Weekend (1.1x)</span>
                                @endif
                                @if($log->is_overtime)
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-100 text-purple-800">Overtime (1.2x)</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="font-black text-gray-900 text-sm">
                                Rp {{ number_format($log->calculated_nominal, 0, ',', '.') }}
                            </div>
                            @if($log->payment_date)
                            <div class="text-[10px] text-emerald-600">Dibayar: {{ $log->payment_date->format('d/m/Y') }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($log->source_account_type === 'office_account')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-700">
                                Kas Kantor
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-orange-100 text-orange-800">
                                Reimbursement
                            </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($log->payment_status === 'paid')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-emerald-500"></span> PAID
                            </span>
                            @elseif($log->payment_status === 'pending_approval')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                                <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-amber-500"></span> PENDING
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-100 text-red-800">
                                <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-red-500"></span> UNPAID
                            </span>
                            @endif
                        </td>
                        @can('finance.work_logs.manage')
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button type="button" @click="openEditModal({{ json_encode($log) }})" class="p-1 text-gray-500 hover:text-primary rounded hover:bg-gray-100 cursor-pointer" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('finance.work-logs.destroy', $log) }}" onsubmit="return confirm('Hapus log kerja ini?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-gray-500 hover:text-red-600 rounded hover:bg-gray-100 cursor-pointer" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                        @endcan
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-gray-500">
                            Belum ada log jam kerja yang dicatat sesuai filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($workLogs->hasPages())
        <div class="p-4 border-t border-gray-200">
            {{ $workLogs->links() }}
        </div>
        @endif
    </div>

    <!-- Modal Form (Create & Edit) with Multiple Date Ranges Repeater -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form :action="isEditing ? editUrl : '{{ route('finance.work-logs.store') }}'" method="POST">
                    @csrf
                    <template x-if="isEditing">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <input type="hidden" name="redirect_to" value="{{ route('finance.work-logs.index') }}">

                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-gray-900" x-text="isEditing ? 'Edit Log Jam Kerja' : 'Catat Log Kerja & Upah Proyek'"></h3>
                            <p class="text-xs text-gray-500 mt-0.5">Pencatatan jam kerja otomatis menghitung upah sesuai tarif rate master card dan multiplier.</p>
                        </div>
                        <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">&times;</button>
                    </div>

                    <div class="p-6 space-y-4">
                        <!-- Project & Member Selection -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Proyek <span class="text-red-500">*</span></label>
                                <select name="project_id" x-model="form.project_id" required class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border">
                                    <option value="">-- Pilih Proyek --</option>
                                    @foreach($projects as $p)
                                    <option value="{{ $p->id }}">{{ $p->title }} {{ $p->project_code ? '('.$p->project_code.')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Anggota Tim <span class="text-red-500">*</span></label>
                                <select name="user_id" x-model="form.user_id" required class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border">
                                    <option value="">-- Pilih Anggota Tim --</option>
                                    @foreach($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ strtoupper($u->role ?? 'staff') }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Functional Role, Skill Level & Base Rate -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Posisi Fungsional <span class="text-red-500">*</span></label>
                                <select name="role_in_project" x-model="form.role_in_project" @change="onRoleOrSkillChange()" required class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border font-medium">
                                    <option value="">-- Pilih Posisi --</option>
                                    <template x-for="r in uniqueRoles" :key="r">
                                        <option :value="r" x-text="formatRoleName(r)"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Status Kompetensi <span class="text-red-500">*</span></label>
                                <select name="skill_level" x-model="form.skill_level" @change="onRoleOrSkillChange()" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border font-medium">
                                    <option value="entry">Junior (Entry)</option>
                                    <option value="middle">Middle</option>
                                    <option value="senior">Senior</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Rate / Jam (Rp) <span class="text-red-500">*</span></label>
                                <input type="number" step="1000" min="0" name="base_rate" x-model.number="form.base_rate" required class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border font-bold text-primary">
                            </div>
                        </div>

                        <!-- Date Ranges Repeater -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest">
                                    Rentang Tanggal & Jam Kerja <span class="text-red-500">*</span>
                                </label>
                                <span class="text-[11px] text-gray-500">Bisa menambahkan lebih dari 1 rentang</span>
                            </div>

                            <div class="space-y-2.5 max-h-64 overflow-y-auto pr-1">
                                <template x-for="(range, idx) in date_ranges" :key="idx">
                                    <div class="p-3 bg-gray-50 border border-gray-200 rounded-xl space-y-2">
                                        <div class="flex items-center justify-between text-xs font-bold text-gray-700">
                                            <span class="flex items-center gap-1.5 text-primary">
                                                <span class="w-2 h-2 rounded-full bg-primary inline-block"></span>
                                                <span x-text="'Rentang #' + (idx + 1)"></span>
                                            </span>
                                            <span class="text-indigo-600 font-semibold" x-text="getRangeDays(range) + ' hari (' + (getRangeDays(range) * (parseFloat(range.hours_per_day) || 0)).toFixed(1) + ' jam)'"></span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                                            <div class="sm:col-span-4">
                                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tanggal Mulai <span class="text-red-500">*</span></label>
                                                <input type="date" :name="'date_ranges[' + idx + '][start_date]'" x-model="range.start_date" required class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-xs p-2 border">
                                            </div>
                                            <div class="sm:col-span-4">
                                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tanggal Selesai <span class="text-red-500">*</span></label>
                                                <input type="date" :name="'date_ranges[' + idx + '][end_date]'" :min="range.start_date" x-model="range.end_date" required class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-xs p-2 border">
                                            </div>
                                            <div class="sm:col-span-3">
                                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Jam / Hari <span class="text-red-500">*</span></label>
                                                <input type="number" step="0.5" min="0.5" max="24" :name="'date_ranges[' + idx + '][hours_per_day]'" x-model.number="range.hours_per_day" required class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary focus:border-primary text-xs p-2 border font-bold text-center">
                                            </div>
                                            <div class="sm:col-span-1 flex items-end justify-center pt-2 sm:pt-4">
                                                <button type="button" x-show="date_ranges.length > 1" @click="removeDateRange(idx)" class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition cursor-pointer" title="Hapus rentang ini">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-5 pt-1.5 border-t border-gray-200/60 mt-2">
                                            <label class="flex items-center cursor-pointer">
                                                <input type="checkbox" :name="'date_ranges[' + idx + '][is_weekend]'" x-model="range.is_weekend" value="1" class="w-3.5 h-3.5 text-primary rounded border-gray-300">
                                                <span class="ml-1.5 text-[11px] font-semibold text-gray-700">Akhir Pekan (1.1x)</span>
                                            </label>
                                            <label class="flex items-center cursor-pointer">
                                                <input type="checkbox" :name="'date_ranges[' + idx + '][is_overtime]'" x-model="range.is_overtime" value="1" class="w-3.5 h-3.5 text-primary rounded border-gray-300">
                                                <span class="ml-1.5 text-[11px] font-semibold text-gray-700">Lembur (1.2x)</span>
                                            </label>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <button type="button" @click="addDateRange()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-primary bg-blue-50 hover:bg-blue-100 rounded-lg border border-blue-200 transition cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                + Tambah Rentang Tanggal
                            </button>
                        </div>

                        <!-- Source Account & Payment Status -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Sumber Dana <span class="text-red-500">*</span></label>
                                <select name="source_account_type" x-model="form.source_account_type" required class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border">
                                    <option value="office_account">Kas Kantor</option>
                                    <option value="personal_reimbursement">Reimbursement Pribadi</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Status Pembayaran <span class="text-red-500">*</span></label>
                                <select name="payment_status" x-model="form.payment_status" required class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border">
                                    <option value="unpaid">Unpaid (Belum Bayar)</option>
                                    <option value="pending_approval">Pending Approval</option>
                                    <option value="paid">Paid (Terbayar)</option>
                                </select>
                            </div>
                        </div>

                        <template x-if="form.payment_status === 'paid'">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Tanggal Pembayaran</label>
                                <input type="date" name="payment_date" x-model="form.payment_date" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border">
                            </div>
                        </template>

                        <!-- Total Jam & Upah Preview -->
                        <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-xl space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold uppercase text-indigo-700 tracking-wider">Estimasi Total Hari & Durasi</span>
                                <span class="font-bold text-gray-800">
                                    <span x-text="totalCalculatedDays"></span> Hari Kerja (<span x-text="totalCalculatedHours.toFixed(1)"></span> Jam)
                                </span>
                            </div>
                            <div class="flex items-center justify-between border-t border-indigo-100 pt-2">
                                <div>
                                    <span class="text-[11px] font-bold uppercase text-indigo-700 tracking-wider">Estimasi Upah Tervalidasi</span>
                                    <p class="text-[10px] text-gray-500">Total Jam &times; Tarif &times; Multiplier</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-xl font-black text-indigo-900">Rp <span x-text="formatRp(calculatedNominal)"></span></span>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-1.5">Catatan / Aktivitas (Opsional)</label>
                            <textarea name="notes" rows="2" x-model="form.notes" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-primary focus:border-primary text-xs p-2.5 border" placeholder="Deskripsi aktivitas atau modul yang dikerjakan..."></textarea>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-end gap-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-white transition-colors cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-blue-700 text-white rounded-xl font-bold text-xs uppercase tracking-wider shadow-md transition cursor-pointer">
                            Simpan Log Upah
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function workLogManager() {
    return {
        selectedIds: [],
        showModal: false,
        isEditing: false,
        editUrl: '',
        masterRates: {{ json_encode($masterRates) }},
        date_ranges: [
            { start_date: '{{ date('Y-m-d') }}', end_date: '{{ date('Y-m-d') }}', hours_per_day: 8, is_weekend: false, is_overtime: false }
        ],
        form: {
            project_id: '',
            user_id: '',
            role_in_project: '',
            skill_level: 'middle',
            base_rate: 0,
            source_account_type: 'office_account',
            payment_status: 'unpaid',
            payment_date: '{{ date('Y-m-d') }}',
            notes: '',
        },

        get uniqueRoles() {
            const roles = [];
            (this.masterRates || []).forEach(r => {
                if (r.role_slug && !roles.includes(r.role_slug)) {
                    roles.push(r.role_slug);
                }
            });
            if (this.form.role_in_project && !roles.includes(this.form.role_in_project)) {
                roles.push(this.form.role_in_project);
            }
            return roles;
        },

        formatRoleName(slug) {
            if (!slug) return '';
            const map = {
                'developer': 'Developer',
                'pm': 'Project Manager (PM)',
                'qc': 'Quality Control (QC)',
                'designer': 'UI/UX Designer',
                'devops': 'DevOps'
            };
            return map[slug.toLowerCase()] || slug.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },

        onRoleOrSkillChange() {
            const match = (this.masterRates || []).find(r =>
                (r.role_slug || '').toLowerCase() === (this.form.role_in_project || '').toLowerCase() &&
                (r.skill_level || '').toLowerCase() === (this.form.skill_level || 'middle').toLowerCase()
            );
            if (match) {
                this.form.base_rate = parseFloat(match.base_rate_per_hour || match.base_rate || 0);
            }
        },

        addDateRange() {
            const last = this.date_ranges[this.date_ranges.length - 1];
            const nextDate = last ? last.end_date : '{{ date('Y-m-d') }}';
            this.date_ranges.push({
                start_date: nextDate,
                end_date: nextDate,
                hours_per_day: last ? last.hours_per_day : 8,
                is_weekend: false,
                is_overtime: false
            });
        },

        removeDateRange(idx) {
            if (this.date_ranges.length > 1) {
                this.date_ranges.splice(idx, 1);
            }
        },

        getRangeDays(range) {
            if (!range.start_date || !range.end_date) return 0;
            const start = new Date(range.start_date + 'T00:00:00');
            const end = new Date(range.end_date + 'T00:00:00');
            if (isNaN(start.getTime()) || isNaN(end.getTime()) || end < start) return 0;
            return Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
        },

        get totalCalculatedDays() {
            return this.date_ranges.reduce((sum, r) => sum + this.getRangeDays(r), 0);
        },

        get totalCalculatedHours() {
            return this.date_ranges.reduce((sum, r) => sum + (this.getRangeDays(r) * (parseFloat(r.hours_per_day) || 0)), 0);
        },

        get calculatedNominal() {
            const rate = parseFloat(this.form.base_rate) || 0;
            let total = 0;
            this.date_ranges.forEach(r => {
                const days = this.getRangeDays(r);
                const hpd = parseFloat(r.hours_per_day) || 0;
                if (days > 0 && hpd > 0) {
                    const weekendMult = r.is_weekend ? 1.10 : 1.0;
                    const overtimeMult = r.is_overtime ? 1.20 : 1.0;
                    total += Math.round(days * hpd * rate * weekendMult * overtimeMult);
                }
            });
            return total;
        },

        toggleSelectAll(event) {
            if (event.target.checked) {
                this.selectedIds = @json($workLogs->pluck('id'));
            } else {
                this.selectedIds = [];
            }
        },

        formatRp(val) {
            return new Intl.NumberFormat('id-ID').format(Math.round(val || 0));
        },

        openCreateModal() {
            this.isEditing = false;
            this.editUrl = '';
            const defaultRole = this.uniqueRoles[0] || 'developer';
            this.form = {
                project_id: '{{ $projects->first()?->id ?? '' }}',
                user_id: '{{ $users->first()?->id ?? '' }}',
                role_in_project: defaultRole,
                skill_level: 'middle',
                base_rate: 0,
                source_account_type: 'office_account',
                payment_status: 'unpaid',
                payment_date: '{{ date('Y-m-d') }}',
                notes: '',
            };
            this.date_ranges = [
                { start_date: '{{ date('Y-m-d') }}', end_date: '{{ date('Y-m-d') }}', hours_per_day: 8, is_weekend: false, is_overtime: false }
            ];
            this.onRoleOrSkillChange();
            this.showModal = true;
        },

        openEditModal(log) {
            this.isEditing = true;
            this.editUrl = `/finance/work-logs/${log.id}`;
            this.form = {
                project_id: log.project_id,
                user_id: log.user_id,
                role_in_project: log.role_in_project || '',
                skill_level: 'middle',
                base_rate: parseFloat(log.base_rate) || 0,
                source_account_type: log.source_account_type || 'office_account',
                payment_status: log.payment_status || 'unpaid',
                payment_date: log.payment_date ? log.payment_date.substring(0, 10) : '{{ date('Y-m-d') }}',
                notes: log.notes || '',
            };

            let ranges = log.date_ranges_data;
            if (typeof ranges === 'string') {
                try { ranges = JSON.parse(ranges); } catch (e) { ranges = null; }
            }
            if (Array.isArray(ranges) && ranges.length > 0) {
                this.date_ranges = ranges.map(r => ({
                    start_date: (r.start_date || '').substring(0, 10),
                    end_date: (r.end_date || r.start_date || '').substring(0, 10),
                    hours_per_day: parseFloat(r.hours_per_day) || 8,
                    is_weekend: !!r.is_weekend,
                    is_overtime: !!r.is_overtime
                }));
            } else {
                const start = log.work_date ? log.work_date.substring(0, 10) : '{{ date('Y-m-d') }}';
                const end = log.end_date ? log.end_date.substring(0, 10) : start;
                const days = log.days_count || 1;
                const hpd = log.hours_per_day || (days > 0 ? (parseFloat(log.hours_worked) / days) : 8.0);
                this.date_ranges = [
                    {
                        start_date: start,
                        end_date: end,
                        hours_per_day: parseFloat(hpd) || 8,
                        is_weekend: !!log.is_weekend,
                        is_overtime: !!log.is_overtime
                    }
                ];
            }
            if (this.form.base_rate <= 0) {
                this.onRoleOrSkillChange();
            }
            this.showModal = true;
        }
    };
}
</script>
@endsection
