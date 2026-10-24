@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Selamat Datang, Admin</h1>
        <p class="text-gray-500">Ringkasan operasional Wirodev hari ini.</p>
    </div>

    <!-- Stats Grid -->
    @php
        $canSeeAnyStat = auth()->user()->canany(['clients.view', 'projects.view', 'finance.view', 'projects.view_financial', 'payments.manage']);
    @endphp

    @if($canSeeAnyStat)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        @can('clients.view')
        <div class="p-6 rounded-xl shadow-md bg-gradient-to-br from-blue-600 to-indigo-600 text-white">
            <div class="flex items-center">
                <div class="p-3 bg-white/20 rounded-lg text-white mr-4 backdrop-blur-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-blue-100">Total Client</p>
                    <p class="text-2xl font-bold text-white">{{ $totalClients }}</p>
                </div>
            </div>
        </div>
        @endcan

        @can('projects.view')
        <div class="p-6 rounded-xl shadow-md bg-gradient-to-br from-purple-600 to-fuchsia-500 text-white">
            <div class="flex items-center">
                <div class="p-3 bg-white/20 rounded-lg text-white mr-4 backdrop-blur-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-purple-100">Proyek Berjalan</p>
                    <p class="text-2xl font-bold text-white">{{ $activeProjects }}</p>
                </div>
            </div>
        </div>
        @endcan

        @canany(['finance.view', 'projects.view_financial'])
        <div class="p-6 rounded-xl shadow-md bg-gradient-to-br from-teal-500 to-emerald-500 text-white">
            <div class="flex items-center">
                <div class="p-3 bg-white/20 rounded-lg text-white mr-4 backdrop-blur-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-teal-100">Total Revenue</p>
                    <p class="text-xl font-bold text-white">Rp {{ number_format($totalContractValue, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
        @endcanany

        @canany(['finance.view', 'payments.manage'])
        <div class="p-6 rounded-xl shadow-md bg-gradient-to-br from-indigo-500 to-blue-600 text-white">
            <div class="flex items-center">
                <div class="p-3 bg-white/20 rounded-lg text-white mr-4 backdrop-blur-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-indigo-100">Collection (Paid)</p>
                    <p class="text-xl font-bold text-white">Rp {{ number_format($totalPaid, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
        @endcanany
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Projects -->
        @can('projects.view')
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-indigo-600 flex justify-between items-center bg-gradient-to-r from-indigo-600 to-blue-500 text-white">
                <h3 class="font-bold text-white shadow-sm">Proyek Terbaru</h3>
                <a href="{{ route('projects.index') }}" class="text-xs font-bold text-indigo-50 hover:text-white bg-white/20 px-3 py-1.5 rounded-lg backdrop-blur-sm transition-colors">Lihat Semua &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <tbody class="divide-y divide-gray-50">
                        @foreach($recentProjects as $p)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900">{{ $p->title }}</div>
                                <div class="text-xs text-gray-400">{{ $p->client->name }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="px-2 py-1 bg-blue-50 text-primary text-[10px] font-bold rounded-full uppercase">{{ str_replace('_', ' ', $p->status) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @can('projects.manage')
                                <a href="{{ route('projects.show', $p) }}" class="text-xs font-bold text-gray-400 hover:text-primary">DETAIL</a>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endcan

        <!-- Unpaid Invoices -->
        @canany(['invoices.manage', 'finance.view'])
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-600 flex justify-between items-center bg-gradient-to-r from-slate-600 to-gray-500 text-white">
                <h3 class="font-bold text-white shadow-sm">Invoice Belum Lunas</h3>
                <span class="text-xs font-bold text-slate-100 px-3 py-1.5">Monitoring Penagihan</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <tbody class="divide-y divide-gray-50">
                        @foreach($unpaidInvoices as $inv)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900">{{ $inv->invoice_number }}</div>
                                <div class="text-xs text-gray-400">{{ $inv->project->client->name }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="text-sm font-bold text-red-600">Rp {{ number_format($inv->balance_due, 0, ',', '.') }}</div>
                                <div class="text-[10px] text-gray-400 uppercase">Due: {{ $inv->due_date ? $inv->due_date->format('d/m/Y') : 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @can('invoices.manage')
                                <a href="{{ route('invoices.show', $inv) }}" class="text-xs font-bold text-gray-400 hover:text-primary">VIEW</a>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endcanany
    </div>

    <!-- Upcoming Internal Company Tasks Widget -->
    @can('company_tasks.view')
    @if(isset($upcomingCompanyTasks) && $upcomingCompanyTasks->count() > 0)
    <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-primary flex justify-between items-center bg-gradient-to-r from-primary to-blue-700 text-white">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                <h3 class="font-bold text-white shadow-sm">Agenda & Task Internal Mendatang</h3>
            </div>
            <a href="{{ route('company-tasks.index') }}" class="text-xs font-bold text-blue-50 hover:text-white bg-white/20 px-3 py-1.5 rounded-lg backdrop-blur-sm transition-colors">Buka Papan Kanban &rarr;</a>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($upcomingCompanyTasks as $ct)
            <div class="p-4 hover:bg-gray-50 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span class="font-mono text-[10px] font-bold px-2 py-0.5 rounded bg-gray-100 text-gray-700 border border-gray-200 shrink-0 mt-0.5">{{ $ct->code }}</span>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h4 class="text-sm font-semibold text-gray-900">{{ $ct->title }}</h4>
                            @if($ct->type === 'meeting')
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">🤝 Pertemuan Klien</span>
                            @elseif($ct->type === 'proposal')
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200">📑 Proposal</span>
                            @elseif($ct->type === 'report')
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200">📊 Laporan</span>
                            @elseif($ct->type === 'operational')
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200">⚙️ Operasional</span>
                            @endif

                            @if($ct->client)
                                <span class="text-[10px] font-medium text-gray-500">🏢 {{ $ct->client->name }}</span>
                            @endif
                        </div>
                        @if($ct->description)
                            <p class="text-xs text-gray-500 line-clamp-1 mt-0.5">{{ $ct->description }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0 text-xs">
                    @if($ct->type === 'meeting' && $ct->meeting_time)
                        <span class="text-indigo-800 font-semibold bg-indigo-50 px-2.5 py-1 rounded-md border border-indigo-100 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ \Carbon\Carbon::parse($ct->meeting_time)->translatedFormat('d M Y, H:i') }}
                        </span>
                    @elseif($ct->due_date)
                        <span class="text-gray-600 font-medium">Batas: {{ \Carbon\Carbon::parse($ct->due_date)->translatedFormat('d M Y') }}</span>
                    @endif

                    @php
                        $assigneesList = $ct->assignees->count() > 0 ? $ct->assignees : ($ct->assignee ? collect([$ct->assignee]) : collect());
                    @endphp
                    @if($assigneesList->count() > 0)
                        <div class="flex items-center -space-x-1.5 overflow-hidden" title="Petugas: {{ $assigneesList->pluck('name')->join(', ') }}">
                            @foreach($assigneesList->take(3) as $a)
                                <div class="inline-block w-5 h-5 rounded-full ring-2 ring-white bg-primary text-white text-[8px] font-bold flex items-center justify-center uppercase">
                                    {{ substr($a->name, 0, 2) }}
                                </div>
                            @endforeach
                            @if($assigneesList->count() > 3)
                                <div class="inline-block w-5 h-5 rounded-full ring-2 ring-white bg-gray-200 text-gray-700 text-[8px] font-bold flex items-center justify-center">
                                    +{{ $assigneesList->count() - 3 }}
                                </div>
                            @endif
                        </div>
                    @endif

                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                        {{ $ct->status === 'todo' ? 'bg-gray-100 text-gray-700' : '' }}
                        {{ $ct->status === 'in_progress' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $ct->status === 'waiting_client' ? 'bg-amber-100 text-amber-800' : '' }}
                        {{ $ct->status === 'done' ? 'bg-green-100 text-green-800' : '' }}">
                        {{ str_replace('_', ' ', $ct->status) }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endcan
</div>
@endsection
