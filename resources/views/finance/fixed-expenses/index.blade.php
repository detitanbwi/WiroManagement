@extends('layouts.app')

@section('title', 'Pengeluaran Tetap Bulanan - Wirodayan Digital')

@section('content')
@php
    $prevMonth = \Carbon\Carbon::parse($month . '-01')->subMonth()->format('Y-m');
    $nextMonth = \Carbon\Carbon::parse($month . '-01')->addMonth()->format('Y-m');
    $monthLabel = \Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y');
@endphp

<div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto"
     x-data="{
        activeTab: 'checklist',
        showExpenseModal: false,
        isEditMode: false,
        expenseFormAction: '{{ route('finance.fixed-expenses.store') }}',
        expenseForm: {
            name: '',
            category: 'OPEX',
            amount: '',
            due_day_of_month: 1,
            payment_method: 'Bank Transfer',
            vendor: '',
            is_active: true,
            notes: ''
        },
        showPayModal: false,
        payFormAction: '',
        payData: {
            expenseName: '',
            dueDay: '',
            estimatedAmount: 0,
            amount: 0,
            payment_date: '{{ date('Y-m-d') }}',
            receipt_url: '',
            notes: ''
        },
        showReceiptModal: false,
        receiptData: {
            url: '',
            title: ''
        },
        openCreateExpense() {
            this.isEditMode = false;
            this.expenseFormAction = '{{ route('finance.fixed-expenses.store') }}';
            this.expenseForm = {
                name: '',
                category: 'OPEX',
                amount: '',
                due_day_of_month: 1,
                payment_method: 'Bank Transfer',
                vendor: '',
                is_active: true,
                notes: ''
            };
            this.showExpenseModal = true;
        },
        openEditExpense(item) {
            this.isEditMode = true;
            this.expenseFormAction = '/finance/fixed-expenses/' + item.id;
            this.expenseForm = {
                name: item.name,
                category: item.category,
                amount: item.amount,
                due_day_of_month: item.due_day_of_month,
                payment_method: item.payment_method,
                vendor: item.vendor || '',
                is_active: item.is_active ? true : false,
                notes: item.notes || ''
            };
            this.showExpenseModal = true;
        },
        openPayModal(realization) {
            this.payFormAction = '/finance/fixed-expenses/realizations/' + realization.id + '/mark-paid';
            var expense = realization.fixed_expense || {};
            this.payData = {
                expenseName: expense.name || 'Pengeluaran',
                dueDay: expense.due_day_of_month || '-',
                estimatedAmount: expense.amount || 0,
                amount: parseFloat(realization.paid_amount) > 0 ? realization.paid_amount : (expense.amount || 0),
                payment_date: realization.payment_date ? realization.payment_date.substring(0, 10) : '{{ date('Y-m-d') }}',
                receipt_url: realization.receipt_url || realization.proof_of_payment || '',
                notes: realization.notes || ''
            };
            this.showPayModal = true;
        },
        viewReceipt(url, title) {
            this.receiptData = { url: url, title: title };
            this.showReceiptModal = true;
        }
     }">

    <!-- Page Header & Month Selector -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between pb-6 border-b border-gray-200 mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </span>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Pengeluaran Tetap Bulanan</h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Kelola overhead operasional berulang (OPEX, utilitas, SaaS/cloud, sewa & payroll) dan pantau realisasi tagihannya.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Month Picker Navigation -->
            <div class="inline-flex items-center bg-white border border-gray-200 rounded-lg shadow-xs p-1">
                <a href="{{ route('finance.fixed-expenses.index', ['month' => $prevMonth]) }}" 
                   title="Bulan Sebelumnya ({{ $prevMonth }})"
                   class="p-1.5 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>

                <div class="px-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <input type="month" 
                           value="{{ $month }}" 
                           onchange="window.location.href = '{{ route('finance.fixed-expenses.index') }}?month=' + this.value"
                           class="border-0 p-0 text-sm font-semibold text-gray-800 focus:ring-0 cursor-pointer bg-transparent" />
                </div>

                <a href="{{ route('finance.fixed-expenses.index', ['month' => $nextMonth]) }}" 
                   title="Bulan Berikutnya ({{ $nextMonth }})"
                   class="p-1.5 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            @can('finance.fixed_expenses.manage')
            <!-- Inisialisasi Siklus Tagihan -->
            <form method="POST" action="{{ route('finance.fixed-expenses.generate-cycle') }}">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <button type="submit" 
                        class="inline-flex items-center px-3.5 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-medium rounded-lg text-sm transition-colors border border-indigo-200 shadow-xs"
                        title="Generate checklist tagihan otomatis untuk semua pos aktif di periode ini">
                    <svg class="w-4 h-4 mr-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Inisialisasi Tagihan
                </button>
            </form>

            <!-- Tambah Pos Baru -->
            <button @click="openCreateExpense()" 
                    type="button" 
                    class="inline-flex items-center px-4 py-2 bg-primary text-white font-medium rounded-lg text-sm hover:bg-blue-800 shadow-sm transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Tambah Pos Biaya
            </button>
            @endcan
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 flex items-center justify-between shadow-xs">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-green-500 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-semibold">{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800 text-xl font-bold leading-none">&times;</button>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 shadow-xs">
        <div class="flex items-center gap-2 mb-1">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-bold">Terjadi kesalahan input:</span>
        </div>
        <ul class="list-disc pl-5 text-xs space-y-0.5 text-red-700">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Projected Budget -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Anggaran Proyeksi</span>
                <span class="p-1.5 rounded-lg bg-blue-50 text-blue-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </span>
            </div>
            <div class="text-2xl font-black text-gray-900 mt-2">
                Rp {{ number_format($summary['projected_budget'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="text-xs text-gray-400 mt-1 flex items-center justify-between">
                <span>{{ $fixedExpenses->where('is_active', true)->count() }} pos aktif berjalan</span>
                <span class="text-[10px] text-gray-500 font-mono">{{ $month }}</span>
            </div>
        </div>

        <!-- Card 2: Realized Expenditure -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Realisasi Terbayar</span>
                <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="text-2xl font-black text-emerald-600 mt-2">
                Rp {{ number_format($summary['realized_expenditure'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="text-xs text-emerald-700 mt-1 flex items-center justify-between font-medium">
                <span>{{ $summary['paid_count'] ?? 0 }} tagihan telah lunas</span>
                <span class="text-[10px] text-emerald-600">Aktual Kas</span>
            </div>
        </div>

        <!-- Card 3: Pending & Overdue Amount -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Sisa Belum Bayar</span>
                <span class="p-1.5 rounded-lg {{ ($summary['overdue_count'] ?? 0) > 0 ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="text-2xl font-black {{ ($summary['overdue_count'] ?? 0) > 0 ? 'text-rose-600' : 'text-amber-600' }} mt-2">
                Rp {{ number_format($summary['pending_or_overdue_amount'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="text-xs mt-1 flex items-center justify-between text-gray-500 font-medium">
                <span>
                    @if(($summary['overdue_count'] ?? 0) > 0)
                        <span class="text-rose-600 font-bold">{{ $summary['overdue_count'] }} Jatuh Tempo!</span>,
                    @endif
                    {{ $summary['pending_count'] ?? 0 }} Pending
                </span>
                <span class="text-[10px] text-gray-400">Tersisa</span>
            </div>
        </div>

        <!-- Card 4: Completion Rate -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Penyelesaian Siklus</span>
                <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                </span>
            </div>
            <div class="text-2xl font-black text-indigo-600 mt-2">
                {{ $summary['completion_rate'] ?? 0 }}%
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2 mt-2 overflow-hidden">
                <div class="bg-indigo-600 h-2 rounded-full transition-all duration-500" 
                     style="width: {{ min(100, (float)($summary['completion_rate'] ?? 0)) }}%"></div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center border-b border-gray-200 mb-6 space-x-8">
        <button @click="activeTab = 'checklist'" 
                type="button"
                class="pb-3 text-sm font-bold flex items-center gap-2 border-b-2 transition-colors"
                :class="activeTab === 'checklist' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            Checklist Realisasi Tagihan Bulan Ini
            <span class="ml-1.5 px-2 py-0.5 text-xs rounded-full font-semibold"
                  :class="activeTab === 'checklist' ? 'bg-blue-100 text-primary' : 'bg-gray-100 text-gray-600'">
                {{ $realizations->count() }}
            </span>
        </button>

        <button @click="activeTab = 'master'" 
                type="button"
                class="pb-3 text-sm font-bold flex items-center gap-2 border-b-2 transition-colors"
                :class="activeTab === 'master' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            Master Pos Biaya Tetap (Kelola Pos)
            <span class="ml-1.5 px-2 py-0.5 text-xs rounded-full font-semibold"
                  :class="activeTab === 'master' ? 'bg-blue-100 text-primary' : 'bg-gray-100 text-gray-600'">
                {{ $fixedExpenses->count() }}
            </span>
        </button>
    </div>

    <!-- TAB 1: Realization Checklist Table -->
    <div x-show="activeTab === 'checklist'" x-cloak>
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-gray-50/50">
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Checklist Pembayaran: Periode {{ $monthLabel }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Pantau realisasi jatuh tempo pembayaran bulanan serta lampiran kuitansi.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">
                        {{ $realizations->where('payment_status', 'PAID')->count() }} dari {{ $realizations->count() }} Lunas
                    </span>
                </div>
            </div>

            @if($realizations->isEmpty())
            <div class="py-12 px-4 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-between p-4">
                    <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Belum Ada Siklus Tagihan untuk {{ $monthLabel }}</h3>
                <p class="text-xs text-gray-500 max-w-md mx-auto mb-5">
                    Siklus checklist tagihan periode ini belum digenerate. Inisialisasi sekarang untuk membuat daftar pembayaran otomatis dari semua pos aktif.
                </p>
                @can('finance.fixed_expenses.manage')
                <form method="POST" action="{{ route('finance.fixed-expenses.generate-cycle') }}">
                    @csrf
                    <input type="hidden" name="month" value="{{ $month }}">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary text-white font-semibold rounded-lg text-sm hover:bg-blue-800 shadow-sm transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Inisialisasi Tagihan {{ $monthLabel }}
                    </button>
                </form>
                @endcan
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-[10px] font-bold tracking-wider">
                            <th class="py-3 px-4 text-center">Jatuh Tempo</th>
                            <th class="py-3 px-4">Nama Pos Pengeluaran & Vendor</th>
                            <th class="py-3 px-4 text-center">Kategori</th>
                            <th class="py-3 px-4 text-right">Estimasi Anggaran</th>
                            <th class="py-3 px-4 text-right">Realisasi Dibayar</th>
                            <th class="py-3 px-4">Metode Bayar</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($realizations as $item)
                        @php
                            $expense = $item->fixedExpense;
                            $status = $item->payment_status;
                            $category = $expense ? strtoupper($expense->category) : 'OPEX';
                        @endphp
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <!-- Due Day -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <span class="inline-block px-2.5 py-1 rounded-md text-xs font-mono font-bold
                                      {{ $status === 'OVERDUE' ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-gray-100 text-gray-700' }}">
                                    Tgl {{ $expense ? $expense->due_day_of_month : '-' }}
                                </span>
                            </td>

                            <!-- Expense Name & Vendor -->
                            <td class="py-3 px-4">
                                <div class="font-bold text-gray-900 text-sm">
                                    {{ $expense ? $expense->name : 'Pengeluaran Terhapus' }}
                                </div>
                                <div class="text-[11px] text-gray-500 flex items-center gap-1.5 mt-0.5">
                                    @if($expense && $expense->vendor)
                                        <span class="inline-flex items-center text-gray-600 font-medium">
                                            <svg class="w-3 h-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                            {{ $expense->vendor }}
                                        </span>
                                    @endif
                                    @if($item->notes)
                                        <span class="text-gray-400">&bull; {{ Str::limit($item->notes, 30) }}</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Category Badge -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @switch($category)
                                    @case('UTILITY')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-100 text-cyan-800 border border-cyan-200">UTILITY</span>
                                        @break
                                    @case('SUBSCRIPTION')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">SUBSCRIPTION</span>
                                        @break
                                    @case('PAYROLL')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">PAYROLL</span>
                                        @break
                                    @case('INFRASTRUCTURE')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">INFRASTRUCTURE</span>
                                        @break
                                    @default
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">OPEX</span>
                                @endswitch
                            </td>

                            <!-- Estimated Amount -->
                            <td class="py-3 px-4 text-right font-medium text-gray-600 whitespace-nowrap">
                                Rp {{ number_format($expense ? $expense->amount : 0, 0, ',', '.') }}
                            </td>

                            <!-- Paid Amount -->
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                @if($status === 'PAID')
                                    <div class="font-bold text-emerald-600 text-sm">
                                        Rp {{ number_format($item->paid_amount, 0, ',', '.') }}
                                    </div>
                                    @if($item->payment_date)
                                        <div class="text-[10px] text-gray-400">
                                            {{ \Carbon\Carbon::parse($item->payment_date)->format('d/m/Y') }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-gray-400 font-mono">-</span>
                                @endif
                            </td>

                            <!-- Payment Method -->
                            <td class="py-3 px-4 whitespace-nowrap text-gray-600">
                                <span class="inline-flex items-center gap-1 font-medium text-[11px]">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    {{ $expense ? $expense->payment_method : '-' }}
                                </span>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @switch($status)
                                    @case('PAID')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            PAID
                                        </span>
                                        @break
                                    @case('OVERDUE')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                            <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            OVERDUE
                                        </span>
                                        @break
                                    @case('SKIPPED')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700 border border-gray-200">
                                            SKIPPED
                                        </span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            PENDING
                                        </span>
                                @endswitch
                            </td>

                            <!-- Action Buttons -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    @if($item->receipt_url || $item->proof_of_payment)
                                        <button @click="viewReceipt('{{ $item->receipt_url ?? $item->proof_of_payment }}', '{{ $expense?->name }}')" 
                                                type="button" 
                                                class="p-1 text-gray-500 hover:text-indigo-600 rounded-md hover:bg-gray-100 transition-colors"
                                                title="Lihat Bukti Bayar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    @endif

                                    @can('finance.fixed_expenses.manage')
                                        @if($status !== 'PAID')
                                            <button @click="openPayModal({{ json_encode($item) }})" 
                                                    type="button" 
                                                    class="inline-flex items-center px-2.5 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 font-bold rounded-lg text-xs transition-colors shadow-2xs">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                Bayar
                                            </button>
                                        @else
                                            <button @click="openPayModal({{ json_encode($item) }})" 
                                                    type="button" 
                                                    class="text-gray-400 hover:text-gray-600 text-[11px] underline ml-1"
                                                    title="Edit Nominal / Bukti Pembayaran">
                                                Edit
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    <!-- TAB 2: Master Pos Pengeluaran Tetap Table -->
    <div x-show="activeTab === 'master'" x-cloak>
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-gray-50/50">
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Daftar Master Pos Pengeluaran Tetap (Recurring OPEX)</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Master tagihan berulang yang secara otomatis digenerate ke dalam siklus tagihan bulanan.</p>
                </div>
                <div>
                    @can('finance.fixed_expenses.manage')
                    <button @click="openCreateExpense()" 
                            type="button" 
                            class="inline-flex items-center px-3 py-1.5 bg-primary text-white font-medium rounded-lg text-xs hover:bg-blue-800 transition">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        + Pos Baru
                    </button>
                    @endcan
                </div>
            </div>

            @if($fixedExpenses->isEmpty())
            <div class="py-12 px-4 text-center">
                <p class="text-xs text-gray-500 mb-3">Belum ada pos pengeluaran tetap yang didaftarkan.</p>
                @can('finance.fixed_expenses.manage')
                <button @click="openCreateExpense()" 
                        type="button" 
                        class="inline-flex items-center px-4 py-2 bg-primary text-white font-semibold rounded-lg text-sm hover:bg-blue-800 transition">
                    Tambah Pos Biaya Pertama
                </button>
                @endcan
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-[10px] font-bold tracking-wider">
                            <th class="py-3 px-4">Nama Pos Pengeluaran</th>
                            <th class="py-3 px-4 text-center">Kategori</th>
                            <th class="py-3 px-4 text-center">Jatuh Tempo</th>
                            <th class="py-3 px-4 text-right">Nominal Rutin</th>
                            <th class="py-3 px-4">Metode Bayar</th>
                            <th class="py-3 px-4 text-center">Status Pos</th>
                            <th class="py-3 px-4">Catatan</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($fixedExpenses as $expense)
                        <tr class="hover:bg-gray-50/80 transition-colors {{ !$expense->is_active ? 'opacity-60 bg-gray-50/40' : '' }}">
                            <td class="py-3 px-4">
                                <div class="font-bold text-gray-900 text-sm">{{ $expense->name }}</div>
                                @if($expense->vendor)
                                    <div class="text-[11px] text-gray-500 font-medium">Vendor: {{ $expense->vendor }}</div>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @switch(strtoupper($expense->category))
                                    @case('UTILITY')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-100 text-cyan-800 border border-cyan-200">UTILITY</span>
                                        @break
                                    @case('SUBSCRIPTION')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">SUBSCRIPTION</span>
                                        @break
                                    @case('PAYROLL')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">PAYROLL</span>
                                        @break
                                    @case('INFRASTRUCTURE')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">INFRASTRUCTURE</span>
                                        @break
                                    @default
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">OPEX</span>
                                @endswitch
                            </td>

                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded font-mono font-bold text-xs bg-gray-100 text-gray-700">
                                    Tgl {{ $expense->due_day_of_month }}
                                </span>
                            </td>

                            <td class="py-3 px-4 text-right font-bold text-gray-900 whitespace-nowrap">
                                Rp {{ number_format($expense->amount, 0, ',', '.') }}
                            </td>

                            <td class="py-3 px-4 whitespace-nowrap text-gray-600">
                                {{ $expense->payment_method }}
                            </td>

                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($expense->is_active)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800 border border-green-200">
                                        AKTIF
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-200 text-gray-600 border border-gray-300">
                                        NONAKTIF
                                    </span>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-gray-500 max-w-xs truncate text-[11px]">
                                {{ $expense->notes ?? '-' }}
                            </td>

                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @can('finance.fixed_expenses.manage')
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openEditExpense({{ json_encode($expense) }})" 
                                            type="button" 
                                            class="p-1 text-gray-500 hover:text-blue-600 rounded hover:bg-gray-100 transition"
                                            title="Edit Pos Pengeluaran">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <form method="POST" action="{{ route('finance.fixed-expenses.destroy', $expense->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pos pengeluaran {{ $expense->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="p-1 text-gray-400 hover:text-red-600 rounded hover:bg-gray-100 transition"
                                                title="Hapus Pos Pengeluaran">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    <!-- MODAL 1: Tambah / Edit Master Pos Pengeluaran Tetap -->
    <div x-show="showExpenseModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs transition-opacity"
         @keydown.escape.window="showExpenseModal = false">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 max-w-lg w-full overflow-hidden transform transition-all"
             @click.away="showExpenseModal = false">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-gray-50">
                <h3 class="text-base font-bold text-gray-900" x-text="isEditMode ? 'Edit Pos Pengeluaran Tetap' : 'Tambah Pos Pengeluaran Tetap'"></h3>
                <button type="button" @click="showExpenseModal = false" class="text-gray-400 hover:text-gray-600 text-xl font-bold leading-none">&times;</button>
            </div>

            <form :action="expenseFormAction" method="POST" class="p-6 space-y-4">
                @csrf
                <template x-if="isEditMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nama Pos Biaya *</label>
                    <input type="text" 
                           name="name" 
                           x-model="expenseForm.name" 
                           required 
                           placeholder="Contoh: Server AWS Cloud / Sewa Kantor"
                           class="w-full text-sm border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Kategori *</label>
                        <select name="category" 
                                x-model="expenseForm.category" 
                                required 
                                class="w-full text-sm border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Jatuh Tempo (Tgl 1-31) *</label>
                        <input type="number" 
                               name="due_day_of_month" 
                               min="1" 
                               max="31" 
                               x-model="expenseForm.due_day_of_month" 
                               required 
                               class="w-full text-sm border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nominal Anggaran (Rp) *</label>
                        <input type="number" 
                               step="0.01" 
                               min="1" 
                               name="amount" 
                               x-model="expenseForm.amount" 
                               required 
                               placeholder="2500000"
                               class="w-full text-sm border-gray-300 rounded-lg focus:ring-primary focus:border-primary font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Metode Pembayaran *</label>
                        <select name="payment_method" 
                                x-model="expenseForm.payment_method" 
                                required 
                                class="w-full text-sm border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method }}">{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Vendor / Penyedia Layanan</label>
                    <input type="text" 
                           name="vendor" 
                           x-model="expenseForm.vendor" 
                           placeholder="Contoh: Amazon Web Services / PLN / Biznet"
                           class="w-full text-sm border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Catatan Tambahan</label>
                    <textarea name="notes" 
                              rows="2" 
                              x-model="expenseForm.notes" 
                              placeholder="Detail nomor akun pelanggan, termin langganan tahunan, dll"
                              class="w-full text-sm border-gray-300 rounded-lg focus:ring-primary focus:border-primary"></textarea>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" 
                           id="is_active" 
                           name="is_active" 
                           value="1" 
                           x-model="expenseForm.is_active" 
                           class="rounded text-primary focus:ring-primary border-gray-300 h-4 w-4">
                    <label for="is_active" class="ml-2 block text-xs font-semibold text-gray-700">
                        Status Pos Aktif (diikutsertakan dalam generator siklus tagihan bulanan)
                    </label>
                </div>

                <div class="pt-4 border-t border-gray-200 flex items-center justify-end gap-2">
                    <button type="button" 
                            @click="showExpenseModal = false" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-blue-800 shadow-sm transition">
                        Simpan Pos Biaya
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Catat Realisasi Pembayaran / Mark as Paid -->
    <div x-show="showPayModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs transition-opacity"
         @keydown.escape.window="showPayModal = false">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 max-w-md w-full overflow-hidden transform transition-all"
             @click.away="showPayModal = false">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-emerald-50">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-emerald-100 text-emerald-700 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <h3 class="text-base font-bold text-gray-900">Catat Pembayaran Tagihan</h3>
                </div>
                <button type="button" @click="showPayModal = false" class="text-gray-400 hover:text-gray-600 text-xl font-bold leading-none">&times;</button>
            </div>

            <form :action="payFormAction" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf

                <!-- Bill Overview Box -->
                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nama Pos:</span>
                        <span class="font-bold text-gray-800" x-text="payData.expenseName"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Jatuh Tempo:</span>
                        <span class="font-semibold text-gray-700" x-text="'Tanggal ' + payData.dueDay"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Anggaran Estimasi:</span>
                        <span class="font-mono font-bold text-gray-900" x-text="'Rp ' + Number(payData.estimatedAmount).toLocaleString('id-ID')"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nominal Terbayar (Rp) *</label>
                    <input type="number" 
                           step="0.01" 
                           min="1" 
                           name="amount" 
                           x-model="payData.amount" 
                           required 
                           class="w-full text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 font-mono font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tanggal Pembayaran *</label>
                    <input type="date" 
                           name="payment_date" 
                           x-model="payData.payment_date" 
                           required 
                           class="w-full text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Unggah Bukti Transfer / Invoice (File)</label>
                    <input type="file" 
                           name="receipt_file" 
                           accept="image/*,.pdf" 
                           class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                    <p class="text-[10px] text-gray-400 mt-1">Format PDF, JPG, PNG maksimal 5 MB.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Atau URL Bukti Bayar Eksternal</label>
                    <input type="url" 
                           name="receipt_url" 
                           x-model="payData.receipt_url" 
                           placeholder="https://drive.google.com/..." 
                           class="w-full text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Catatan Pembayaran</label>
                    <textarea name="notes" 
                              rows="2" 
                              x-model="payData.notes" 
                              placeholder="Misal: Nomor referensi mutasi BCA #9812498"
                              class="w-full text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                </div>

                <div class="pt-4 border-t border-gray-200 flex items-center justify-end gap-2">
                    <button type="button" 
                            @click="showPayModal = false" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 shadow-sm transition">
                        Konfirmasi Lunas
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: View Receipt Modal -->
    <div x-show="showReceiptModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs transition-opacity"
         @keydown.escape.window="showReceiptModal = false">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 max-w-2xl w-full overflow-hidden"
             @click.away="showReceiptModal = false">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-gray-50">
                <h3 class="text-sm font-bold text-gray-900" x-text="'Bukti Pembayaran: ' + receiptData.title"></h3>
                <button type="button" @click="showReceiptModal = false" class="text-gray-400 hover:text-gray-600 text-xl font-bold leading-none">&times;</button>
            </div>
            <div class="p-6 text-center">
                <template x-if="receiptData.url && receiptData.url.toLowerCase().endsWith('.pdf')">
                    <div class="space-y-4">
                        <div class="p-8 bg-gray-50 rounded-xl border border-gray-200">
                            <svg class="w-16 h-16 text-rose-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <p class="text-sm font-medium text-gray-700">Berkas Bukti Transaksi berbentuk PDF</p>
                        </div>
                        <a :href="receiptData.url" 
                           target="_blank" 
                           class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-blue-800 transition">
                            Buka Berkas PDF di Tab Baru &rarr;
                        </a>
                    </div>
                </template>
                <template x-if="receiptData.url && !receiptData.url.toLowerCase().endsWith('.pdf')">
                    <div class="space-y-3">
                        <img :src="receiptData.url" alt="Bukti Pembayaran" class="max-h-96 mx-auto rounded-lg shadow-xs border border-gray-200 object-contain">
                        <div>
                            <a :href="receiptData.url" 
                               target="_blank" 
                               class="text-xs text-primary hover:underline font-semibold">
                                Buka Gambar Ukuran Penuh &rarr;
                            </a>
                        </div>
                    </div>
                </template>
            </div>
            <div class="px-6 py-3 border-t border-gray-100 flex justify-end bg-gray-50">
                <button type="button" 
                        @click="showReceiptModal = false" 
                        class="px-4 py-1.5 text-xs bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
