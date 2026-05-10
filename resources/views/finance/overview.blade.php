@extends('layouts.app')

@section('title', 'Finance Overview - Wiro App')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Finance Overview</h1>
            <p class="text-sm text-gray-500">Monitoring pengeluaran personal dan perusahaan.</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Balance</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format(\App\Models\TrackerAccount::sum('balance'), 0, ',', '.') }}</h3>
                </div>
                <div class="p-3 bg-blue-50 rounded-lg">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Expense This Month (Personal)</p>
                    <h3 class="text-2xl font-bold text-orange-600 mt-1">Rp {{ number_format(\App\Models\TrackerExpense::where('type', 'personal')->whereMonth('expense_date', now()->month)->sum('amount'), 0, ',', '.') }}</h3>
                </div>
                <div class="p-3 bg-orange-50 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Expense This Month (Company)</p>
                    <h3 class="text-2xl font-bold text-teal-600 mt-1">Rp {{ number_format(\App\Models\TrackerExpense::where('type', 'company')->whereMonth('expense_date', now()->month)->sum('amount'), 0, ',', '.') }}</h3>
                </div>
                <div class="p-3 bg-teal-50 rounded-lg">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart / Recent Activity Placeholder -->
    <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Financial Overview</h2>
        <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
            <p class="text-gray-400 italic">Visualisasi data pengeluaran (Chart) akan muncul di sini saat data tersedia.</p>
        </div>
    </div>
</div>
@endsection
