@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Selamat Datang, Admin</h1>
        <p class="text-gray-500">Ringkasan operasional Wirodev hari ini.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
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
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Projects -->
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
                                <a href="{{ route('projects.show', $p) }}" class="text-xs font-bold text-gray-400 hover:text-primary">DETAIL</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Unpaid Invoices -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-600 flex justify-between items-center bg-gradient-to-r from-slate-600 to-gray-500 text-white">
                <h3 class="font-bold text-white shadow-sm">Invoice Belum Lunas</h3>
                <a href="#" class="text-xs font-bold text-slate-50 hover:text-white bg-white/20 px-3 py-1.5 rounded-lg backdrop-blur-sm transition-colors">Lihat Semua &rarr;</a>
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
                                <div class="text-[10px] text-gray-400 uppercase">Due: {{ $inv->due_date->format('d/m/Y') }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('invoices.show', $inv) }}" class="text-xs font-bold text-gray-400 hover:text-primary">VIEW</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
