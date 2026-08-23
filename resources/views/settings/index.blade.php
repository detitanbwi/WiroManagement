@extends('layouts.app')

@section('title', 'System & Rate Card Settings')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="settingsPage({
    activeTab: '{{ $activeTab ?? "rate-card" }}'
})">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Pengaturan Sistem & Rate Card</h1>
            <p class="text-sm text-gray-500">Kelola harga satuan dasar modul AI Pricing, parameter multiplier, dan template dokumen legal.</p>
        </div>

        <a href="{{ route('ai-pricing.index') }}" 
           class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition-colors shadow-2xs">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke AI Pricing
        </a>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center shadow-xs">
            <svg class="w-5 h-5 mr-2 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <!-- Tab Navigation -->
    <div class="flex border-b border-gray-200 space-x-2">
        <button type="button"
                @click="activeTab = 'rate-card'"
                :class="activeTab === 'rate-card' ? 'border-primary text-primary font-bold bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium'"
                class="py-3 px-5 border-b-2 text-sm rounded-t-lg transition-all flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            <span>Rate Card & Harga Satuan AI</span>
        </button>

        <button type="button"
                @click="activeTab = 'document'"
                :class="activeTab === 'document' ? 'border-primary text-primary font-bold bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium'"
                class="py-3 px-5 border-b-2 text-sm rounded-t-lg transition-all flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span>Template Dokumen Legal</span>
        </button>
    </div>

    <!-- TAB 1: Rate Card & Module Pricing -->
    <div x-show="activeTab === 'rate-card'" x-cloak class="space-y-6">
        <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="active_tab" value="rate-card">

            <!-- Top Notice -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl p-4 md:p-5 flex items-start space-x-3.5 shadow-2xs">
                <div class="p-2 rounded-xl bg-blue-600 text-white shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex-1 text-xs leading-relaxed text-gray-700">
                    <h3 class="font-bold text-gray-900 text-sm mb-0.5">Master Rate Card & Harga Satuan Resmi</h3>
                    <p>Harga satuan dasar di bawah ini digunakan oleh AI Pricing Engine untuk menghitung estimasi penawaran secara deterministik. Input nominal otomatis dilengkapi <strong>pemisah ribuan (thousand separator)</strong> untuk kenyamanan pengetikan.</p>
                </div>
            </div>

            <!-- Categories of Modules -->
            @php
                $modules = $rules['modules'] ?? [];
                $grouped = [];
                foreach ($modules as $m) {
                    $cat = $m['category'] ?? 'Core System';
                    $grouped[$cat][] = $m;
                }
            @endphp

            <div class="space-y-6">
                @foreach($grouped as $category => $catModules)
                    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs">
                        <div class="bg-gray-50/80 px-5 py-3 border-b border-gray-100 flex justify-between items-center">
                            <div class="flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">{{ $category }}</h3>
                            </div>
                            <span class="text-xs font-semibold text-gray-500">{{ count($catModules) }} Modul</span>
                        </div>

                        <div class="divide-y divide-gray-100">
                            @foreach($catModules as $mod)
                                <div class="p-4 md:p-5 hover:bg-gray-50/50 transition-colors">
                                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-center">
                                        <!-- Module Info (Cols 5) -->
                                        <div class="lg:col-span-5 space-y-1">
                                            <div class="flex items-center space-x-2">
                                                <span class="font-mono text-xs font-black bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded border border-indigo-200">
                                                    {{ $mod['code'] }}
                                                </span>
                                                <h4 class="text-sm font-bold text-gray-900">{{ $mod['name'] }}</h4>
                                                @if(!empty($mod['is_free']))
                                                    <span class="text-[10px] font-extrabold uppercase px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800">Gratis</span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-600 leading-relaxed">{{ $mod['spec'] }}</p>
                                            @if(!empty($mod['guide']))
                                                <p class="text-[11px] text-gray-400 italic">{{ $mod['guide'] }}</p>
                                            @endif
                                        </div>

                                        <!-- Price Inputs (Cols 7) -->
                                        <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <!-- Harga Min -->
                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">
                                                    Harga Min
                                                </label>
                                                <div class="relative">
                                                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400 pointer-events-none">Rp</span>
                                                    <input type="text" 
                                                           name="rate_card_modules[{{ $mod['code'] }}][price_min]"
                                                           value="{{ number_format($mod['price_min'], 0, ',', '.') }}"
                                                           @input="formatCurrency($event)"
                                                           class="w-full bg-gray-50 focus:bg-white border border-gray-300 focus:border-indigo-500 rounded-xl pl-8 pr-3 py-2 text-xs font-bold text-gray-800 outline-none transition text-right">
                                                </div>
                                            </div>

                                            <!-- Harga Rekomendasi (Satuan Dasar Utama) -->
                                            <div>
                                                <label class="block text-[10px] font-extrabold text-indigo-700 uppercase tracking-wider mb-1 flex items-center">
                                                    <span>Harga Standar</span>
                                                    <span class="ml-1 px-1 py-0.2 rounded text-[9px] bg-indigo-100 text-indigo-800">Utama</span>
                                                </label>
                                                <div class="relative">
                                                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-bold text-indigo-600 pointer-events-none">Rp</span>
                                                    <input type="text" 
                                                           name="rate_card_modules[{{ $mod['code'] }}][price_recommended]"
                                                           value="{{ number_format($mod['price_recommended'], 0, ',', '.') }}"
                                                           @input="formatCurrency($event)"
                                                           class="w-full bg-indigo-50/50 focus:bg-white border-2 border-indigo-400 focus:border-indigo-600 rounded-xl pl-8 pr-3 py-2 text-xs font-black text-indigo-900 outline-none transition text-right shadow-2xs">
                                                </div>
                                            </div>

                                            <!-- Harga Max -->
                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">
                                                    Harga Max
                                                </label>
                                                <div class="relative">
                                                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400 pointer-events-none">Rp</span>
                                                    <input type="text" 
                                                           name="rate_card_modules[{{ $mod['code'] }}][price_max]"
                                                           value="{{ number_format($mod['price_max'], 0, ',', '.') }}"
                                                           @input="formatCurrency($event)"
                                                           class="w-full bg-gray-50 focus:bg-white border border-gray-300 focus:border-indigo-500 rounded-xl pl-8 pr-3 py-2 text-xs font-bold text-gray-800 outline-none transition text-right">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Multipliers, Novelty & Segments Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Platform Multipliers -->
                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs space-y-4">
                    <div class="flex items-center space-x-2 border-b border-gray-100 pb-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold">
                            📱
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900">Platform Multipliers</h4>
                            <p class="text-[11px] text-gray-500">Pengali berdasarkan arsitektur aplikasi</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach(($rules['platforms'] ?? []) as $pKey => $pInfo)
                            <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-xl border border-gray-100">
                                <div>
                                    <span class="text-xs font-bold text-gray-800">{{ $pInfo['name'] }}</span>
                                    <p class="text-[10px] text-gray-400 uppercase font-mono">{{ $pKey }}</p>
                                </div>
                                <div class="flex items-center space-x-1.5">
                                    <input type="number" 
                                           name="platforms[{{ $pKey }}]" 
                                           value="{{ $pInfo['multiplier'] }}" 
                                           step="0.1" 
                                           min="0.5" 
                                           max="5.0"
                                           class="w-20 bg-white border border-gray-300 focus:border-indigo-500 rounded-lg px-2.5 py-1 text-xs font-bold text-right outline-none">
                                    <span class="text-xs font-bold text-gray-500">x</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Novelty Multipliers (Status Proyek) -->
                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs space-y-4">
                    <div class="flex items-center space-x-2 border-b border-gray-100 pb-3">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center font-bold">
                            🏗️
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900">Status Proyek (Novelty)</h4>
                            <p class="text-[11px] text-gray-500">Markup adaptasi & refactoring kode existing</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach(($rules['novelty_options'] ?? []) as $nov)
                            <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-xl border border-gray-100">
                                <div>
                                    <span class="text-xs font-bold text-gray-800">{{ $nov['name'] }}</span>
                                    <p class="text-[10px] text-gray-400 uppercase font-mono">{{ $nov['code'] }}</p>
                                </div>
                                <div class="flex items-center space-x-1.5">
                                    <input type="number" 
                                           name="novelty_options[{{ $nov['code'] }}]" 
                                           value="{{ $nov['multiplier'] }}" 
                                           step="0.05" 
                                           min="0.5" 
                                           max="3.0"
                                           class="w-20 bg-white border border-gray-300 focus:border-purple-500 rounded-lg px-2.5 py-1 text-xs font-bold text-right outline-none">
                                    <span class="text-xs font-bold text-gray-500">x</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- DP & Termin Rules -->
                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs space-y-4">
                    <div class="flex items-center space-x-2 border-b border-gray-100 pb-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                            💳
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900">DP & Termin Pembayaran</h4>
                            <p class="text-[11px] text-gray-500">Ketentuan DP berdasar nilai proyek</p>
                        </div>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Ambang Batas Nilai Proyek</label>
                            <div class="relative">
                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400 pointer-events-none">Rp</span>
                                <input type="text" 
                                       name="dp_rules[threshold]" 
                                       value="{{ number_format($rules['dp_rules']['threshold'] ?? 5000000, 0, ',', '.') }}"
                                       @input="formatCurrency($event)"
                                       class="w-full bg-gray-50 focus:bg-white border border-gray-300 focus:border-indigo-500 rounded-xl pl-8 pr-3 py-2 text-xs font-bold text-gray-800 outline-none text-right">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-1">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">&lt; Batas</label>
                                <div class="flex items-center space-x-1">
                                    <input type="number" 
                                           name="dp_rules[below_threshold_dp_pct]" 
                                           value="{{ $rules['dp_rules']['below_threshold_dp_pct'] ?? 30 }}"
                                           min="0" max="100"
                                           class="w-full bg-gray-50 focus:bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs font-bold text-right outline-none">
                                    <span class="text-xs font-bold text-gray-500">%</span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">&ge; Batas</label>
                                <div class="flex items-center space-x-1">
                                    <input type="number" 
                                           name="dp_rules[above_threshold_dp_pct]" 
                                           value="{{ $rules['dp_rules']['above_threshold_dp_pct'] ?? 20 }}"
                                           min="0" max="100"
                                           class="w-full bg-gray-50 focus:bg-white border border-gray-300 rounded-xl px-3 py-2 text-xs font-bold text-right outline-none">
                                    <span class="text-xs font-bold text-gray-500">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Segments Floor Price & Operational SOP Card -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Segment Floor Prices -->
                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs space-y-4">
                    <div class="flex items-center space-x-2 border-b border-gray-100 pb-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                            🏢
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900">Batas Floor Price per Segmen</h4>
                            <p class="text-[11px] text-gray-500">Nilai penawaran minimal proyek sistem utuh</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach(($rules['segments'] ?? []) as $sKey => $sVal)
                            <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-xl border border-gray-100">
                                <div>
                                    <span class="text-xs font-bold text-gray-800">{{ $sVal['name'] }}</span>
                                    <p class="text-[10px] text-gray-400">{{ $sVal['description'] ?? '' }}</p>
                                </div>
                                <div class="relative w-36">
                                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400 pointer-events-none">Rp</span>
                                    <input type="text" 
                                           name="segments[{{ $sKey }}][floor_price_system]" 
                                           value="{{ number_format($sVal['floor_price_system'] ?? 0, 0, ',', '.') }}" 
                                           @input="formatCurrency($event)"
                                           class="w-full bg-white border border-gray-300 focus:border-emerald-500 rounded-lg pl-7 pr-2.5 py-1 text-xs font-bold text-right outline-none">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Operational SOP Baseline -->
                <div class="bg-gradient-to-br from-slate-900 to-indigo-950 text-white rounded-2xl p-5 shadow-xs space-y-4">
                    <div class="flex items-center space-x-2 border-b border-white/10 pb-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-xs">
                            SOP
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white">Standar Operasional & SLA</h4>
                            <p class="text-[11px] text-white/60">Ketentuan baku penawaran & kontrak WiroDev</p>
                        </div>
                    </div>

                    <div class="space-y-2.5 text-xs text-white/80">
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span>Man-day Reference Rate:</span>
                            <span class="font-bold text-white">Rp 250.000 / man-day</span>
                        </div>
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span>Masa Garansi Bug Standar:</span>
                            <span class="font-bold text-emerald-300">30 Hari Kalender Pasca UAT</span>
                        </div>
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span>Retainer Baseline (Opsional):</span>
                            <span class="font-bold text-amber-300">Rp 500.000 / bulan (5 jam SLA 4-8 jam)</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Domain & Hosting (Dewaweb):</span>
                            <span class="font-bold text-indigo-200">At-Cost / Tagihan Terpisah</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky Save Bar -->
            <div class="sticky bottom-4 bg-white/95 backdrop-blur border border-gray-200 rounded-2xl p-4 shadow-xl flex items-center justify-between z-10">
                <div class="text-xs text-gray-500">
                    Perubahan Rate Card akan langsung berlaku pada sesi kalkulasi estimasi baru maupun yang sedang dibuka.
                </div>

                <button type="submit" 
                        class="px-6 py-2.5 bg-primary hover:bg-blue-800 text-white rounded-xl font-bold text-xs shadow-md transition-all flex items-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Simpan Perubahan Rate Card
                </button>
            </div>
        </form>
    </div>

    <!-- TAB 2: Document Templates -->
    <div x-show="activeTab === 'document'" x-cloak class="space-y-6">
        <div class="bg-white shadow-sm rounded-2xl border border-gray-200 overflow-hidden">
            <form action="{{ route('settings.update') }}" method="POST" id="settings-form" class="p-6 space-y-8">
                @csrf
                <input type="hidden" name="active_tab" value="document">
                
                <!-- Section: Document Terms -->
                <div class="space-y-6">
                    <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 border-b pb-2">Terms & Conditions (Invoice)</h3>
                        <label for="terms_conditions" class="block text-xs font-medium text-gray-600 mb-2">Garis besar syarat pembayaran dan ketentuan teknis invoice.</label>
                        <div class="bg-white">
                            <textarea name="settings[terms_conditions]" id="editor-invoice-terms" style="height: 150px;">{!! $settings['terms_conditions'] ?? "" !!}</textarea>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 border-b pb-2">Quotation Notes (Penawaran)</h3>
                        <label for="quotation_notes" class="block text-xs font-medium text-gray-600 mb-2">Catatan standar yang muncul di bagian bawah penawaran harga.</label>
                        <div class="bg-white">
                            <textarea name="settings[quotation_notes]" id="editor-quotation-notes" style="height: 150px;">{!! $settings['quotation_notes'] ?? "" !!}</textarea>
                        </div>
                        <p class="mt-2 text-[10px] text-gray-400">Contoh: Masa berlaku penawaran, kebijakan DP, dan lingkup maintenance.</p>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 border-b pb-2">Syarat & Ketentuan (Quotation)</h3>
                        <label for="quotation_terms" class="block text-xs font-medium text-gray-600 mb-2">Halaman khusus Syarat & Ketentuan yang akan muncul di halaman baru pada PDF Quotation.</label>
                        <div class="bg-white">
                            <textarea name="settings[quotation_terms]" id="editor-quotation-terms" style="height: 250px;">{!! $settings['quotation_terms'] ?? "" !!}</textarea>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-100 flex justify-end">
                    <button type="submit" id="submit-btn" class="inline-flex items-center px-6 py-2.5 bg-primary border border-transparent rounded-xl font-bold text-xs text-white uppercase tracking-wider hover:bg-blue-800 transition shadow-md">
                        Simpan Konfigurasi Dokumen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- TinyMCE Styles & Scripts -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>
<script>
function settingsPage(initialConfig) {
    return {
        activeTab: initialConfig.activeTab || 'rate-card',

        formatCurrency(event) {
            let input = event.target;
            let rawValue = input.value.replace(/\D/g, '');
            if (!rawValue) {
                input.value = '0';
                return;
            }
            // Format with Indonesian thousand separator (dot)
            input.value = Number(rawValue).toLocaleString('id-ID');
        }
    };
}

function setupTinyMCE(selector, height) {
    tinymce.init({
        selector: selector,
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
        toolbar: 'undo redo | blocks | ' +
        'bold italic underline | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'table image charmap | removeformat | help',
        menubar: false,
        height: height,
        setup: function (editor) {
            editor.on('change', function () {
                tinymce.triggerSave();
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    setupTinyMCE('#editor-invoice-terms', 200);
    setupTinyMCE('#editor-quotation-notes', 200);
    setupTinyMCE('#editor-quotation-terms', 350);
});
</script>
@endsection
