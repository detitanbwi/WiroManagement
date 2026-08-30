@extends('layouts.app')

@section('title', 'AI Pricing Estimator - Wiro App')

@section('content')
<div x-data="aiPricingWorkspace({
    sessionId: {{ $session->id }},
    clientName: '{{ addslashes($session->client_name ?? '') }}',
    clientSegment: '{{ $session->client_segment ?? 'umkm' }}',
    platform: '{{ $session->platform ?? 'web' }}',
    novelty: '{{ $session->novelty ?? ($calculation['novelty']['value'] ?? 'from_scratch') }}',
    riskBufferPercent: {{ (float)($session->risk_buffer_percent ?? 0) }},
    rushFeePercent: {{ (int)($session->rush_fee_percent ?? 0) }},
    selectedModules: {{ json_encode($session->selected_modules ?? ['MOD-01', 'AUTH-01']) }},
    unlistedFeatures: {{ json_encode($session->unlisted_features ?? []) }},
    initialCalculation: {{ json_encode($calculation) }},
    rules: {{ json_encode($rules) }},
    chatUrl: '{{ route('ai-pricing.chat', $session->id) }}',
    updateUrl: '{{ route('ai-pricing.update-modules', $session->id) }}',
    testApiUrl: '{{ route('ai-pricing.test-api') }}',
    csrfToken: '{{ csrf_token() }}'
})" x-init="initComponent()" class="h-full flex flex-col -m-4 md:-m-6">

    <!-- Top Header Bar -->
    <div class="bg-white border-b border-gray-200 px-4 py-3 md:px-6 flex flex-wrap items-center justify-between gap-3 shadow-sm z-10">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-primary flex items-center justify-center text-white shadow-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h1 class="text-lg font-bold text-gray-900 leading-tight">AI Pricing Estimator</h1>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        v1.0 Hybrid Engine
                    </span>
                </div>
                <p class="text-xs text-gray-500">Konsultasi kebutuhan & kalkulasi deterministik berbasis Rate Card Resmi</p>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <!-- API Status Badge -->
            <button @click="testApiConnection()" 
                    :class="apiStatus === 'testing' ? 'opacity-75' : ''"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors {{ $isApiConfigured ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100' }}">
                <span class="w-2 h-2 rounded-full mr-2 {{ $isApiConfigured ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                <span x-text="apiStatusText">{{ $isApiConfigured ? 'Gemini AI Ready' : 'Lokal' }}</span>
            </button>

            <!-- Rate Card Settings Button -->
            <a href="{{ route('settings.index', ['tab' => 'rate-card']) }}" 
               class="px-3 py-1.5 bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 text-gray-700 rounded-lg text-xs font-medium border border-gray-200 transition-colors flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Rate Card
            </a>

            <!-- History Button -->
            <button @click="historyOpen = true" 
                    type="button"
                    class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-medium transition-colors flex items-center cursor-pointer">
                <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Riwayat ({{ count($recentSessions) }})
            </button>

            <!-- New Session Button -->
            <form action="{{ route('ai-pricing.new') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center px-3.5 py-1.5 bg-primary hover:bg-blue-800 text-white rounded-lg text-xs font-semibold shadow-sm transition-all">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Sesi Baru
                </button>
            </form>
        </div>
    </div>

    <!-- Mobile View Tab Switcher (Chat vs Modul & Estimasi) -->
    <div class="lg:hidden bg-slate-100 p-1.5 border-b border-gray-200 flex items-center justify-center space-x-1.5 z-10 flex-shrink-0">
        <button type="button" 
                @click="mobileTab = 'chat'; scrollToBottom()" 
                :class="mobileTab === 'chat' ? 'bg-primary text-white font-bold shadow-sm' : 'bg-white text-gray-600 font-semibold hover:bg-gray-50'"
                class="flex-1 py-2 px-3 rounded-lg text-xs flex items-center justify-center space-x-1.5 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
            <span>💬 Chat Brief AI</span>
        </button>
        <button type="button" 
                @click="mobileTab = 'canvas'" 
                :class="mobileTab === 'canvas' ? 'bg-primary text-white font-bold shadow-sm' : 'bg-white text-gray-600 font-semibold hover:bg-gray-50'"
                class="flex-1 py-2 px-3 rounded-lg text-xs flex items-center justify-center space-x-1.5 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            <span>📋 Modul &amp; Estimasi</span>
            <span class="ml-1 px-1.5 py-0.2 text-[10px] rounded-full bg-indigo-100 text-indigo-700 font-bold" x-text="selectedModules.length"></span>
        </button>
    </div>

    <!-- Main Workspace (Split-Screen on Desktop, Tabbed on Mobile) -->
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-0 overflow-hidden bg-gray-100 min-h-0">
        
        <!-- LEFT PANEL: Chat Consultant (Cols 6 on LG) -->
        <div :class="mobileTab === 'chat' ? 'flex' : 'hidden lg:flex'" 
             class="lg:col-span-6 flex-col bg-white border-b lg:border-b-0 lg:border-r border-gray-200 h-full overflow-hidden">
            
            <!-- Chat Header / Title Info -->
            <div class="p-3 bg-gradient-to-r from-gray-50 to-indigo-50/30 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-7 h-7 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold shadow-sm">
                        AI
                    </div>
                    <div>
                        <h2 class="text-xs font-bold text-gray-800" x-text="sessionTitle">{{ $session->title }}</h2>
                        <p class="text-[10px] text-gray-500">Tanyakan atau jelaskan brief proyek klien di bawah ini</p>
                    </div>
                </div>
                <div class="flex items-center space-x-1">
                    <span class="text-[11px] text-gray-400" x-text="calculation.items.length + ' modul terdeteksi'"></span>
                </div>
            </div>

            <!-- Chat Messages Area (Scrollable) -->
            <div id="chat-container" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50/50">
                <template x-for="(msg, idx) in messages" :key="idx">
                    <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start items-start space-x-2.5'">
                        
                        <!-- AI Avatar -->
                        <template x-if="msg.role !== 'user'">
                            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-600 to-primary text-white flex-shrink-0 flex items-center justify-center text-xs font-black shadow-sm mt-1">
                                AI
                            </div>
                        </template>

                        <!-- Bubble -->
                        <div :class="msg.role === 'user' 
                                ? 'bg-primary text-white rounded-2xl rounded-tr-sm px-4 py-3 max-w-[85%] shadow-sm text-sm' 
                                : 'bg-white text-gray-800 border border-gray-200 rounded-2xl rounded-tl-sm px-4 py-3 max-w-[90%] shadow-sm text-sm'">
                            
                            <!-- Message Content (Formatted simple markdown) -->
                            <div class="leading-relaxed whitespace-pre-line" x-html="formatMessage(msg.content)"></div>

                            <!-- Extracted Tags if Assistant -->
                            <template x-if="msg.role !== 'user' && msg.extracted_params && msg.extracted_params.detected_modules && msg.extracted_params.detected_modules.length > 0">
                                <div class="mt-3 pt-2.5 border-t border-gray-100 flex flex-wrap gap-1 items-center">
                                    <span class="text-[10px] uppercase font-bold text-gray-400 mr-1">Modul Teridentifikasi:</span>
                                    <template x-for="modCode in msg.extracted_params.detected_modules" :key="typeof modCode === 'object' ? modCode.code : modCode">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100"
                                              x-text="getModuleName(typeof modCode === 'object' ? modCode.code : modCode)"></span>
                                    </template>
                                </div>
                            </template>

                            <!-- Interactive Quick Options (One-click answer) -->
                            <template x-if="msg.role !== 'user' && msg.extracted_params && msg.extracted_params.quick_options && msg.extracted_params.quick_options.length > 0">
                                <div class="mt-3 pt-2.5 border-t border-dashed border-indigo-100">
                                    <p class="text-[10px] uppercase font-bold text-indigo-500 mb-1.5 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                                        Pilih Jawaban Cepat:
                                    </p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <template x-for="(opt, optIdx) in msg.extracted_params.quick_options" :key="optIdx">
                                            <button type="button" 
                                                    @click="sendQuickPrompt(opt)"
                                                    :disabled="isThinking"
                                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white shadow-2xs transition-all cursor-pointer">
                                                <span x-text="opt"></span>
                                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Typing / Loading Indicator -->
                <div x-show="isThinking" x-cloak class="flex items-start space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex-shrink-0 flex items-center justify-center text-xs font-bold animate-pulse">
                        AI
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm flex items-center space-x-1.5">
                        <span class="text-xs text-gray-500 mr-1 font-medium">Menganalisis kebutuhan...</span>
                        <div class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce"></div>
                        <div class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce [animation-delay:0.2s]"></div>
                        <div class="w-2 h-2 rounded-full bg-indigo-600 animate-bounce [animation-delay:0.4s]"></div>
                    </div>
                </div>
            </div>

            <!-- Suggestion Chips / Quick Prompts -->
            <div class="px-4 py-2 bg-gray-50 border-t border-gray-100 overflow-x-auto flex items-center space-x-2 no-scrollbar">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider flex-shrink-0">Contoh:</span>
                <button @click="sendQuickPrompt('Klien butuh aplikasi POS kasir toko, ada cetak struk printer thermal, laporan omset, dan hak akses 3 level')" 
                        class="text-xs bg-white hover:bg-indigo-50 hover:text-indigo-600 text-gray-600 px-2.5 py-1 rounded-full border border-gray-200 transition-colors whitespace-nowrap shadow-2xs">
                    🛒 Kasir POS + Printer Thermal
                </button>
                <button @click="sendQuickPrompt('Aplikasi manajemen keuangan UMKM: pencatatan kas masuk-keluar, running balance, laba rugi, dan export Excel')" 
                        class="text-xs bg-white hover:bg-indigo-50 hover:text-indigo-600 text-gray-600 px-2.5 py-1 rounded-full border border-gray-200 transition-colors whitespace-nowrap shadow-2xs">
                    💰 Keuangan & Laba Rugi
                </button>
                <button @click="sendQuickPrompt('Sistem monitoring multi cabang dengan Flutter Android, scan barcode, dan kirim notifikasi WhatsApp')" 
                        class="text-xs bg-white hover:bg-indigo-50 hover:text-indigo-600 text-gray-600 px-2.5 py-1 rounded-full border border-gray-200 transition-colors whitespace-nowrap shadow-2xs">
                    📱 Android + WhatsApp API
                </button>
            </div>

            <!-- Chat Input Box -->
            <div class="p-3 md:p-4 bg-white border-t border-gray-200">
                <form @submit.prevent="sendMessage()" class="flex items-end space-x-2">
                    <div class="flex-1 relative">
                        <textarea 
                            x-model="inputMessage" 
                            @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()"
                            placeholder="Ketik brief proyek calon klien atau jawab pertanyaan AI... (Tekan Enter)" 
                            rows="2"
                            :disabled="isThinking"
                            class="w-full resize-none rounded-xl border border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-2.5 text-sm outline-none transition disabled:bg-gray-100 disabled:opacity-60 leading-relaxed"></textarea>
                    </div>
                    <button 
                        type="submit" 
                        :disabled="!inputMessage.trim() || isThinking"
                        class="h-11 px-4 rounded-xl bg-primary hover:bg-blue-800 disabled:opacity-50 text-white font-semibold flex items-center justify-center transition-all shadow-md flex-shrink-0">
                        <svg class="w-5 h-5 transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </form>
                <div class="mt-1.5 flex justify-between items-center text-[10px] text-gray-400 px-1">
                    <span>Shift + Enter untuk baris baru</span>
                    <span class="italic text-gray-400">Arsitektur AI + Deterministik</span>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: Live Interactive Pricing Canvas (Cols 6 on LG) -->
        <div :class="mobileTab === 'canvas' ? 'flex' : 'hidden lg:flex'" 
             class="lg:col-span-6 flex-col bg-gray-50 h-full overflow-y-auto">
            
            <!-- Config Bar (Static on mobile, Sticky on LG desktop) -->
            <div class="static lg:sticky lg:top-0 bg-white/95 backdrop-blur border-b border-gray-200 p-3 sm:p-4 z-20 shadow-xs space-y-2.5">
                <div class="grid grid-cols-2 lg:grid-cols-6 gap-2 sm:gap-2.5 items-end">
                    <!-- Client Name (Full width on mobile 2-col, cols 2 on LG) -->
                    <div class="col-span-2 lg:col-span-2">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Nama Klien / Proyek</label>
                        <input type="text" 
                               x-model="clientName" 
                               @change="syncUpdate()"
                               placeholder="Misal: Toko Retail / PT Maju Jaya" 
                               class="w-full bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-gray-800 focus:bg-white focus:ring-1 focus:ring-indigo-500 outline-none transition">
                    </div>

                    <!-- Segment Selector -->
                    <div class="col-span-1">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Segmen Klien</label>
                        <select x-model="clientSegment" @change="syncUpdate()" 
                                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-2 py-1.5 text-xs font-semibold text-gray-800 focus:bg-white outline-none">
                            <template x-for="(s, key) in rules.segments" :key="key">
                                <option :value="key" x-text="s.name" :selected="key === clientSegment"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Platform Selector -->
                    <div class="col-span-1">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Platform</label>
                        <select x-model="platform" @change="syncUpdate()" 
                                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-2 py-1.5 text-xs font-semibold text-gray-800 focus:bg-white outline-none">
                            <template x-for="(p, key) in rules.platforms" :key="key">
                                <option :value="key" x-text="p.name + ' (' + p.multiplier + 'x)'" :selected="key === platform"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Novelty (Status Proyek) Selector -->
                    <div class="col-span-1">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Status Proyek</label>
                        <select x-model="novelty" @change="syncUpdate()"
                                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-2 py-1.5 text-xs font-semibold text-gray-800 focus:bg-white outline-none">
                            <option value="from_scratch">Bangun Baru (1.0x)</option>
                            <option value="existing_project">Lanjut Existing (1.2x)</option>
                        </select>
                    </div>

                    <!-- Kesiapan Brief / Risk Buffer Selector -->
                    <div class="col-span-1">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Kesiapan Brief</label>
                        <select x-model="riskBufferPercent" @change="syncUpdate()"
                                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-2 py-1.5 text-xs font-semibold text-gray-800 focus:bg-white outline-none">
                            <option value="-7.5">Matang (-7.5%)</option>
                            <option value="0">Normal (0%)</option>
                            <option value="7.5">Mentah (+7.5%)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Canvas Body -->
            <div class="p-4 md:p-6 space-y-6 flex-1">

                <!-- Module Selection Matrix by Category -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            Daftar Modul & Fitur (Klik untuk Toggle)
                        </h3>
                        <span class="text-xs text-indigo-600 font-semibold" x-text="selectedModules.length + ' dipilih'"></span>
                    </div>

                    <!-- Category Groups -->
                    <template x-for="cat in moduleCategories" :key="cat">
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-2xs">
                            <div class="bg-gray-50/80 px-3.5 py-2 border-b border-gray-100 flex justify-between items-center">
                                <span class="text-[11px] font-bold text-gray-600 uppercase" x-text="cat"></span>
                                <span class="text-[10px] text-gray-400" x-text="getCategoryCount(cat)"></span>
                            </div>

                            <div class="divide-y divide-gray-100">
                                <template x-for="mod in getModulesByCategory(cat)" :key="mod.code">
                                    <div class="p-3 hover:bg-indigo-50/40 transition-colors"
                                         :class="isModuleSelected(mod.code) ? 'bg-indigo-50/30' : ''">
                                        <div class="flex items-start">
                                            <div class="pt-0.5">
                                                <input type="checkbox" 
                                                       :value="mod.code" 
                                                       :checked="isModuleSelected(mod.code)"
                                                       @change="toggleModule(mod.code)"
                                                       class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-gray-300 cursor-pointer">
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-bold text-gray-900 cursor-pointer" @click="toggleModule(mod.code)" x-text="mod.name"></span>
                                                    <div class="text-right">
                                                        <span class="text-xs font-bold text-indigo-700 ml-2" 
                                                              x-text="mod.is_free ? 'GRATIS' : formatRupiah(mod.price_recommended * getModuleQty(mod.code))"></span>
                                                        <template x-if="getModuleQty(mod.code) > 1 && !mod.is_free">
                                                            <span class="block text-[9px] text-gray-400 font-normal" x-text="'(' + getModuleQty(mod.code) + 'x @ ' + formatRupiah(mod.price_recommended) + ')'"></span>
                                                        </template>
                                                    </div>
                                                </div>
                                                <p class="text-[11px] text-gray-500 mt-0.5 leading-snug" x-text="mod.spec"></p>
                                                
                                                <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                                    <div class="flex items-center space-x-2 text-[10px] text-gray-400">
                                                        <span class="font-mono bg-gray-100 px-1 py-0.2 rounded" x-text="mod.code"></span>
                                                        <template x-if="mod.guide">
                                                            <span class="italic" x-text="'• ' + mod.guide"></span>
                                                        </template>
                                                    </div>

                                                <!-- Sub-Items / Entitas List (When Module is Selected) -->
                                                <template x-if="isModuleSelected(mod.code)">
                                                    <div class="mt-2.5 pt-2 border-t border-indigo-100/60 space-y-2">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-[10px] font-bold text-indigo-900 uppercase tracking-wide flex items-center">
                                                                <svg class="w-3 h-3 mr-1 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                                </svg>
                                                                Rincian Sub-Item / Entitas (<span x-text="getModuleQty(mod.code)"></span>):
                                                            </span>
                                                            <div class="flex items-center space-x-1">
                                                                <button type="button" 
                                                                        @click.stop="changeModuleQty(mod.code, -1)" 
                                                                        class="w-4 h-4 flex items-center justify-center bg-gray-100 hover:bg-indigo-100 text-gray-700 hover:text-indigo-700 rounded font-bold text-[10px] transition-colors">-</button>
                                                                <span class="text-[10px] font-extrabold text-indigo-700 px-1" x-text="getModuleQty(mod.code) + 'x'"></span>
                                                                <button type="button" 
                                                                        @click.stop="changeModuleQty(mod.code, 1)" 
                                                                        class="w-4 h-4 flex items-center justify-center bg-gray-100 hover:bg-indigo-100 text-gray-700 hover:text-indigo-700 rounded font-bold text-[10px] transition-colors">+</button>
                                                            </div>
                                                        </div>

                                                        <!-- Sub-item tags -->
                                                        <div class="flex flex-wrap gap-1.5">
                                                            <template x-for="(sub, sIdx) in getModuleSubItems(mod.code)" :key="sIdx">
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-white text-indigo-900 border border-indigo-200 shadow-2xs">
                                                                    <span x-text="sub"></span>
                                                                    <button type="button" 
                                                                            @click.stop="removeSubItem(mod.code, sIdx)" 
                                                                            class="ml-1.5 text-indigo-400 hover:text-red-500 focus:outline-hidden">
                                                                        &times;
                                                                    </button>
                                                                </span>
                                                            </template>
                                                        </div>

                                                        <!-- Add Sub-Item Input -->
                                                        <div class="flex items-center space-x-1.5 pt-1" @click.stop>
                                                            <input type="text" 
                                                                   :id="'input-sub-' + mod.code"
                                                                   @keydown.enter.prevent="handleAddSubItem(mod.code)"
                                                                   placeholder="+ Ketik nama entitas/sub-item..." 
                                                                   class="flex-1 bg-white border border-gray-200 focus:border-indigo-400 rounded px-2 py-1 text-[11px] text-gray-800 outline-none">
                                                            <button type="button" 
                                                                    @click.stop="handleAddSubItem(mod.code)"
                                                                    class="px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-[10px] font-bold shadow-2xs">
                                                                + Tambah
                                                            </button>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Unlisted / Custom Feature Requests Card -->
                <div class="bg-white rounded-xl border border-amber-200 p-4 shadow-2xs space-y-3">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                        <div class="flex items-center space-x-2">
                            <span class="p-1 rounded-md bg-amber-100 text-amber-700 text-xs font-bold">📌 Custom</span>
                            <div>
                                <h4 class="text-xs font-bold text-gray-900">Fitur Khusus / Di Luar Modul Standar</h4>
                                <p class="text-[10px] text-gray-500">Kebutuhan spesifik klien yang belum ada di Rate Card resmi</p>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200"
                              x-text="unlistedFeatures.length + ' fitur'"></span>
                    </div>

                    <!-- List of Unlisted Features -->
                    <div class="space-y-2">
                        <template x-for="(feat, fIdx) in unlistedFeatures" :key="fIdx">
                            <div class="flex items-start justify-between p-2.5 bg-amber-50/50 rounded-lg border border-amber-100 text-xs">
                                <div class="flex-1 mr-2">
                                    <div class="flex items-center space-x-1.5">
                                        <span class="font-bold text-gray-900" x-text="typeof feat === 'object' ? feat.name : feat"></span>
                                        <span class="text-[9px] uppercase px-1.5 py-0.2 rounded bg-amber-200/60 text-amber-900 font-semibold">Custom R&D</span>
                                    </div>
                                    <template x-if="typeof feat === 'object' && feat.description">
                                        <p class="text-[11px] text-gray-600 mt-0.5" x-text="feat.description"></p>
                                    </template>
                                </div>
                                <button type="button" 
                                        @click="removeCustomFeature(fIdx)" 
                                        class="text-gray-400 hover:text-red-500 p-1 transition-colors" title="Hapus Fitur">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </template>

                        <template x-if="unlistedFeatures.length === 0">
                            <p class="text-xs text-gray-400 italic text-center py-2">
                                Belum ada fitur kustom terdeteksi. AI akan otomatis mencatatnya saat klien menyebut kebutuhan khusus.
                            </p>
                        </template>
                    </div>

                    <!-- Input to manually add custom feature -->
                    <div class="pt-2 border-t border-gray-100 flex items-center space-x-2">
                        <input type="text" 
                               x-model="newCustomFeatureInput" 
                               @keydown.enter.prevent="addCustomFeature()"
                               placeholder="Tambah catatan fitur custom manual..." 
                               class="flex-1 bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-gray-800 focus:bg-white outline-none">
                        <button type="button" 
                                @click="addCustomFeature()"
                                class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold transition-all shadow-2xs">
                            + Tambah
                        </button>
                    </div>
                </div>

                <!-- Price Summary & Termin Card -->
                <div class="bg-gradient-to-br from-indigo-900 via-primary to-slate-900 rounded-2xl p-5 text-white shadow-xl space-y-4">
                    <div class="flex justify-between items-start border-b border-white/10 pb-3">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-indigo-300 tracking-wider">Ringkasan Estimasi Investasi</span>
                            <h4 class="text-base font-bold text-white mt-0.5" x-text="clientName || 'Estimasi Proyek'"></h4>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-white/10 text-white border border-white/20"
                              x-text="calculation.platform.name + ' (' + calculation.platform.multiplier + 'x)'"></span>
                    </div>

                    <!-- Breakdown Numbers -->
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between text-white/70">
                            <span>Subtotal Modul Dasar:</span>
                            <span class="font-semibold text-white" x-text="formatRupiah(calculation.subtotal_base)"></span>
                        </div>
                        <div class="flex justify-between text-white/70">
                            <span>Platform (<span x-text="calculation.platform.name + ' ' + calculation.platform.multiplier + 'x'"></span>):</span>
                            <span class="font-semibold text-white" x-text="formatRupiah(calculation.subtotal_after_platform)"></span>
                        </div>

                        <template x-if="calculation.novelty && calculation.novelty.amount > 0">
                            <div class="flex justify-between text-amber-300">
                                <span>Faktor Proyek Existing (+20%):</span>
                                <span class="font-semibold" x-text="'+ ' + formatRupiah(calculation.novelty.amount)"></span>
                            </div>
                        </template>
                        
                        <template x-if="calculation.risk_buffer && calculation.risk_buffer.amount !== 0">
                            <div class="flex justify-between" :class="calculation.risk_buffer.amount < 0 ? 'text-emerald-300' : 'text-amber-300'">
                                <span x-text="calculation.risk_buffer.label"></span>
                                <span class="font-semibold" x-text="(calculation.risk_buffer.amount < 0 ? '- ' : '+ ') + formatRupiah(Math.abs(calculation.risk_buffer.amount))"></span>
                            </div>
                        </template>

                        <template x-if="calculation.rush_fee && calculation.rush_fee.amount > 0">
                            <div class="flex justify-between text-amber-300">
                                <span>Rush Fee (+<span x-text="calculation.rush_fee.percent + '%'"></span>):</span>
                                <span class="font-semibold" x-text="'+ ' + formatRupiah(calculation.rush_fee.amount)"></span>
                            </div>
                        </template>

                        <template x-if="calculation.is_floor_adjusted">
                            <div class="p-2 rounded-lg bg-white/10 text-amber-200 text-[11px] leading-tight">
                                ℹ️ <em>Penawaran disesuaikan dengan Floor Price minimal segmen <strong x-text="calculation.segment.name"></strong> (<span x-text="formatRupiah(calculation.floor_price)"></span>).</em>
                            </div>
                        </template>
                    </div>

                    <!-- Total Highlight -->
                    <div class="pt-3 border-t border-white/10 flex justify-between items-end">
                        <div>
                            <span class="text-[10px] text-white/60 uppercase font-bold">Total Nilai Penawaran</span>
                            <div class="text-2xl font-black text-white tracking-tight" x-text="formatRupiah(calculation.total_estimated)"></div>
                        </div>
                        <div class="text-right text-[11px] text-indigo-200">
                            <p>Rentang: <span x-text="formatRupiah(calculation.total_min) + ' - ' + formatRupiah(calculation.total_max)"></span></p>
                        </div>
                    </div>

                    <!-- Termin Box -->
                    <div class="bg-white/10 rounded-xl p-3 border border-white/10 grid grid-cols-2 gap-3">
                        <div>
                            <span class="text-[10px] font-bold text-amber-300 uppercase">Termin 1: Uang Muka (DP <span x-text="calculation.payment_terms.dp_percent + '%'"></span>)</span>
                            <p class="text-sm font-bold text-white mt-0.5" x-text="formatRupiah(calculation.payment_terms.dp_amount)"></p>
                            <p class="text-[9px] text-white/60">Sebelum pengerjaan dimulai</p>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-emerald-300 uppercase">Termin 2: Pelunasan (<span x-text="calculation.payment_terms.pelunasan_percent + '%'"></span>)</span>
                            <p class="text-sm font-bold text-white mt-0.5" x-text="formatRupiah(calculation.payment_terms.pelunasan_amount)"></p>
                            <p class="text-[9px] text-white/60">Pasca UAT & Siap Go-Live</p>
                        </div>
                    </div>

                    <!-- Extra Info -->
                    <div class="flex items-center justify-between text-[10px] text-white/60 pt-1">
                        <span x-text="'⏳ Durasi: ' + calculation.timeline.estimated_days_range"></span>
                        <span x-text="'🛡️ ' + calculation.warranty"></span>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="pt-2">
                    <button @click="openWaModal()" 
                            class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs shadow-lg flex items-center justify-center transition-all cursor-pointer">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                        </svg>
                        Format Teks Penawaran WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- WhatsApp Summary Modal -->
    <div x-show="waModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 text-gray-800" @click.away="waModalOpen = false">
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold">💬</span>
                    <h3 class="font-bold text-sm text-gray-900">Format Penawaran WhatsApp</h3>
                </div>
                <button @click="waModalOpen = false" class="text-gray-400 hover:text-gray-600 font-bold">&times;</button>
            </div>

            <textarea x-model="waText" rows="12" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-xs font-mono outline-none resize-none leading-relaxed"></textarea>

            <div class="flex space-x-2 pt-1">
                <button type="button" @click="copyWaText()" 
                        class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center justify-center cursor-pointer">
                    <span x-text="copyButtonText"></span>
                </button>
                <button type="button" @click="waModalOpen = false" 
                        class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-all cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Drawer History Sessions -->
    <div x-show="historyOpen" x-cloak class="fixed inset-0 z-50 overflow-hidden bg-black/50 backdrop-blur-xs flex justify-end">
        <div class="bg-white w-full max-w-md h-full shadow-2xl flex flex-col" @click.away="historyOpen = false">
            
            <!-- Drawer Header -->
            <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="font-bold text-sm text-gray-900">Riwayat Sesi Estimasi</h3>
                </div>
                <button @click="historyOpen = false" class="text-gray-400 hover:text-gray-600 text-lg font-bold">&times;</button>
            </div>

            <!-- Drawer Session List -->
            <div class="flex-1 overflow-y-auto p-4 space-y-2.5 divide-y-0">
                @forelse($recentSessions as $item)
                    <div class="p-3.5 rounded-xl border transition-all {{ $item->id == $session->id ? 'bg-indigo-50/70 border-indigo-300 shadow-xs' : 'bg-white hover:bg-gray-50 border-gray-200' }}">
                        <div class="flex justify-between items-start">
                            <a href="{{ route('ai-pricing.show', $item->id) }}" class="flex-1 mr-2">
                                <div class="flex items-center space-x-1.5">
                                    <h4 class="text-xs font-bold text-gray-900 hover:text-indigo-600 line-clamp-1">{{ $item->title }}</h4>
                                    @if($item->id == $session->id)
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-600 text-white uppercase">Aktif</span>
                                    @endif
                                </div>
                                <div class="mt-1 flex items-center space-x-2 text-[10px] text-gray-400">
                                    <span>📅 {{ $item->created_at->diffForHumans() }}</span>
                                    @if(!empty($item->client_name))
                                        <span>• 👤 {{ $item->client_name }}</span>
                                    @endif
                                </div>
                            </a>

                            @if($recentSessions->count() > 1)
                            <form action="{{ route('ai-pricing.delete', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus riwayat sesi ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600 p-1 rounded-md hover:bg-red-50 transition-colors cursor-pointer" title="Hapus Sesi">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 text-gray-400">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <p class="text-xs">Belum ada riwayat sesi estimasi.</p>
                    </div>
                @endforelse
            </div>

            <!-- Drawer Footer -->
            <div class="p-4 border-t border-gray-100 bg-gray-50 flex justify-end">
                <button @click="historyOpen = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-xs font-bold transition-colors cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function aiPricingWorkspace(config) {
    return {
        sessionId: config.sessionId,
        clientName: config.clientName,
        clientSegment: config.clientSegment,
        platform: config.platform,
        novelty: config.novelty || 'from_scratch',
        riskBufferPercent: config.riskBufferPercent,
        rushFeePercent: config.rushFeePercent,
        selectedModules: config.selectedModules,
        unlistedFeatures: config.unlistedFeatures || [],
        newCustomFeatureInput: '',
        calculation: config.initialCalculation,
        rules: config.rules,
        messages: @json($messages),
        inputMessage: '',
        isThinking: false,
        apiStatus: 'ready',
        apiStatusText: '{{ $isApiConfigured ? "Gemini AI Ready" : "Lokal" }}',
        mobileTab: 'canvas',
        sessionTitle: '{{ addslashes($session->title) }}',
        waModalOpen: false,
        historyOpen: false,
        waText: '',
        copyButtonText: 'Salin Teks WhatsApp',

        moduleCategories: [
            'Core System & Bisnis',
            'Keamanan & Hak Akses',
            'Tampilan & Desain UI',
            'Import, Export & Database',
            'Integrasi API & Gateway',
            'Hardware & POS'
        ],

        initComponent() {
            this.scrollToBottom();
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = document.getElementById('chat-container');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },

        getModulesByCategory(category) {
            return (this.rules.modules || []).filter(m => m.category === category);
        },

        getCategoryCount(category) {
            const list = this.getModulesByCategory(category);
            const selected = list.filter(m => this.isModuleSelected(m.code)).length;
            return `${selected} / ${list.length}`;
        },

        isModuleSelected(code) {
            return (this.selectedModules || []).some(m => (typeof m === 'object' ? m.code : m) === code);
        },

        getModuleQty(code) {
            const item = (this.selectedModules || []).find(m => (typeof m === 'object' ? m.code : m) === code);
            if (!item) return 1;
            if (typeof item === 'object') {
                if (Array.isArray(item.sub_items) && item.sub_items.length > 0) {
                    return item.sub_items.length;
                }
                return Math.max(1, parseInt(item.qty) || 1);
            }
            return 1;
        },

        getModuleSubItems(code) {
            const item = (this.selectedModules || []).find(m => (typeof m === 'object' ? m.code : m) === code);
            return (item && typeof item === 'object' && Array.isArray(item.sub_items)) ? item.sub_items : [];
        },

        toggleModule(code) {
            if (this.isModuleSelected(code)) {
                this.selectedModules = (this.selectedModules || []).filter(m => (typeof m === 'object' ? m.code : m) !== code);
            } else {
                this.selectedModules.push({ code: code, qty: 1, sub_items: [], custom_price: null });
            }
            this.syncUpdate();
        },

        changeModuleQty(code, delta) {
            const current = this.getModuleQty(code);
            const next = Math.max(1, current + delta);
            const idx = (this.selectedModules || []).findIndex(m => (typeof m === 'object' ? m.code : m) === code);
            if (idx !== -1) {
                if (typeof this.selectedModules[idx] === 'object') {
                    this.selectedModules[idx].qty = next;
                } else {
                    this.selectedModules[idx] = { code: code, qty: next, sub_items: [] };
                }
                this.syncUpdate();
            }
        },

        handleAddSubItem(code) {
            const input = document.getElementById('input-sub-' + code);
            if (!input) return;
            const val = input.value.trim();
            if (!val) return;
            this.addSubItem(code, val);
            input.value = '';
        },

        addSubItem(code, text) {
            let idx = (this.selectedModules || []).findIndex(m => (typeof m === 'object' ? m.code : m) === code);
            if (idx === -1) {
                this.selectedModules.push({ code: code, qty: 1, sub_items: [text] });
            } else {
                let item = this.selectedModules[idx];
                if (typeof item !== 'object') {
                    item = { code: code, qty: 1, sub_items: [] };
                    this.selectedModules[idx] = item;
                }
                if (!Array.isArray(item.sub_items)) {
                    item.sub_items = [];
                }
                item.sub_items.push(text);
                item.qty = item.sub_items.length;
            }
            this.syncUpdate();
        },

        removeSubItem(code, subIndex) {
            const idx = (this.selectedModules || []).findIndex(m => (typeof m === 'object' ? m.code : m) === code);
            if (idx !== -1 && typeof this.selectedModules[idx] === 'object') {
                const item = this.selectedModules[idx];
                if (Array.isArray(item.sub_items)) {
                    item.sub_items.splice(subIndex, 1);
                    item.qty = Math.max(1, item.sub_items.length);
                    this.syncUpdate();
                }
            }
        },

        addCustomFeature() {
            const text = this.newCustomFeatureInput.trim();
            if (!text) return;
            this.unlistedFeatures.push({
                name: text,
                description: 'Kebutuhan fitur kustom'
            });
            this.newCustomFeatureInput = '';
            this.syncUpdate();
        },

        removeCustomFeature(index) {
            this.unlistedFeatures.splice(index, 1);
            this.syncUpdate();
        },

        getModuleName(code) {
            const mod = (this.rules.modules || []).find(m => m.code === code);
            return mod ? `${mod.name} (${code})` : code;
        },

        formatRupiah(amount) {
            return 'Rp ' + Number(amount || 0).toLocaleString('id-ID');
        },

        formatMessage(text) {
            if (!text) return '';
            let escaped = text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;");
            
            escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');
            escaped = escaped.replace(/`([^`]+)`/g, '<code class="px-1 py-0.5 bg-gray-100 text-indigo-700 rounded font-mono text-[11px]">$1</code>');
            
            return escaped;
        },

        sendQuickPrompt(text) {
            this.inputMessage = text;
            this.sendMessage();
        },

        async sendMessage() {
            const text = this.inputMessage.trim();
            if (!text || this.isThinking) return;

            this.messages.push({
                role: 'user',
                content: text
            });
            this.inputMessage = '';
            this.isThinking = true;
            this.scrollToBottom();

            try {
                const response = await fetch(config.chatUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify({
                        message: text
                    })
                });

                const data = await response.json();
                if (data.status === 'success') {
                    this.messages.push({
                        role: 'assistant',
                        content: data.reply,
                        extracted_params: data.extracted_params
                    });

                    if (data.session) {
                        this.clientName = data.session.client_name || this.clientName;
                        this.clientSegment = data.session.client_segment || this.clientSegment;
                        this.platform = data.session.platform || this.platform;
                        this.novelty = data.session.novelty || this.novelty;
                        this.riskBufferPercent = data.session.risk_buffer_percent ?? this.riskBufferPercent;
                        this.rushFeePercent = data.session.rush_fee_percent ?? this.rushFeePercent;
                        this.selectedModules = data.session.selected_modules || this.selectedModules;
                        this.unlistedFeatures = data.session.unlisted_features || this.unlistedFeatures;
                        this.sessionTitle = data.session.title;
                    }

                    if (data.calculation) {
                        this.calculation = data.calculation;
                    }
                } else {
                    alert('Gagal memproses pesan: ' + (data.message || 'Terjadi kesalahan'));
                }
            } catch (err) {
                console.error(err);
                this.messages.push({
                    role: 'assistant',
                    content: 'Maaf, terjadi kendala saat menghubungi server. Mohon coba lagi.',
                    extracted_params: null
                });
            } finally {
                this.isThinking = false;
                this.scrollToBottom();
            }
        },

        async syncUpdate() {
            try {
                const response = await fetch(config.updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify({
                        selected_modules: this.selectedModules,
                        unlisted_features: this.unlistedFeatures,
                        platform: this.platform,
                        novelty: this.novelty,
                        risk_buffer_percent: parseFloat(this.riskBufferPercent),
                        rush_fee_percent: parseInt(this.rushFeePercent),
                        client_segment: this.clientSegment,
                        client_name: this.clientName
                    })
                });

                const data = await response.json();
                if (data.status === 'success' && data.calculation) {
                    this.calculation = data.calculation;
                }
            } catch (err) {
                console.error('Error updating modules:', err);
            }
        },

        async testApiConnection() {
            this.apiStatus = 'testing';
            this.apiStatusText = 'Memeriksa API...';
            try {
                const res = await fetch(config.testApiUrl);
                const data = await res.json();
                if (data.status === 'success') {
                    this.apiStatus = 'ready';
                    this.apiStatusText = 'Gemini AI Aktif!';
                    alert('✅ ' + data.message + '\n\nRespon Gemini:\n"' + data.response + '"');
                } else {
                    this.apiStatus = 'offline';
                    this.apiStatusText = 'Lokal';
                    alert('ℹ️ ' + data.message);
                }
            } catch (e) {
                this.apiStatus = 'offline';
                this.apiStatusText = 'Lokal';
                alert('⚠️ Mode Offline: ' + e.message);
            }
        },

        openWaModal() {
            let lines = [];
            lines.push(`*ESTIMASI PENAWARAN PENGEMBANGAN SISTEM*`);
            lines.push(`----------------------------------------`);
            lines.push(`*Proyek:* ${this.clientName || 'Pengembangan Sistem Informasi'}`);
            lines.push(`*Platform:* ${this.calculation.platform.name}`);
            lines.push(`*Status Proyek:* ${this.calculation.novelty.name}`);
            lines.push(`*Segmen:* ${this.calculation.segment.name}`);
            lines.push(`*Tanggal:* ${new Date().toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'})}`);
            lines.push(``);
            lines.push(`*Rincian Modul & Fitur:*`);
            
            (this.calculation.items || []).forEach((item, idx) => {
                const subDetails = (item.sub_items && item.sub_items.length > 0) ? ` [${item.qty} item: ${item.sub_items.join(', ')}]` : (item.qty > 1 ? ` [${item.qty}x]` : '');
                const qtyText = item.qty > 1 ? ` (${item.qty}x @ ${this.formatRupiah(item.unit_price)})` : '';
                lines.push(`${idx + 1}. *${item.name}* (${item.code})${subDetails}`);
                lines.push(`   - ${item.spec}`);
                lines.push(`   - Biaya: ${item.is_free ? 'GRATIS' : this.formatRupiah(item.subtotal) + qtyText}`);
            });

            if (this.unlistedFeatures && this.unlistedFeatures.length > 0) {
                lines.push(``);
                lines.push(`*Catatan Fitur Khusus:*`);
                this.unlistedFeatures.forEach((feat, idx) => {
                    const fName = typeof feat === 'object' ? feat.name : feat;
                    const fDesc = typeof feat === 'object' && feat.description ? ` - ${feat.description}` : '';
                    lines.push(`• *${fName}*${fDesc} _(Estimasi Custom R&D)_`);
                });
            }

            lines.push(``);
            lines.push(`----------------------------------------`);
            lines.push(`*Subtotal Modul Dasar:* ${this.formatRupiah(this.calculation.subtotal_base)}`);
            if (this.calculation.platform.multiplier !== 1) {
                lines.push(`*Penyesuaian Platform (${this.calculation.platform.multiplier}x):* ${this.formatRupiah(this.calculation.subtotal_after_platform)}`);
            }
            if (this.calculation.novelty && this.calculation.novelty.amount > 0) {
                lines.push(`*Faktor Proyek Existing (+20%):* +${this.formatRupiah(this.calculation.novelty.amount)}`);
            }
            if (this.calculation.risk_buffer && this.calculation.risk_buffer.amount !== 0) {
                lines.push(`*${this.calculation.risk_buffer.label}:* ${this.calculation.risk_buffer.amount < 0 ? '-' : '+'}${this.formatRupiah(Math.abs(this.calculation.risk_buffer.amount))}`);
            }
            if (this.calculation.rush_fee && this.calculation.rush_fee.amount > 0) {
                lines.push(`*Rush Fee (+${this.calculation.rush_fee.percent}%):* +${this.formatRupiah(this.calculation.rush_fee.amount)}`);
            }

            lines.push(`*TOTAL ESTIMASI INVESTASI:* ${this.formatRupiah(this.calculation.total_estimated)}`);
            lines.push(`----------------------------------------`);
            lines.push(``);
            lines.push(`*Skema Pembayaran (Termin):*`);
            lines.push(`1. *Termin 1 (DP ${this.calculation.payment_terms.dp_percent}%):* ${this.formatRupiah(this.calculation.payment_terms.dp_amount)} (Sebelum kick-off)`);
            lines.push(`2. *Termin 2 (Pelunasan ${this.calculation.payment_terms.pelunasan_percent}%):* ${this.formatRupiah(this.calculation.payment_terms.pelunasan_amount)} (Pasca UAT & Siap Pakai)`);
            lines.push(``);
            lines.push(`*Estimasi Waktu:* ${this.calculation.timeline.estimated_days_range}`);
            lines.push(`*Garansi:* ${this.calculation.warranty}`);
            lines.push(`*SLA Respon:* ${this.calculation.sla}`);
            lines.push(``);
            lines.push(`_Hak Kepemilikan: 100% Full Ownership (Source Code & Database Beli Putus)_`);

            this.waText = lines.join('\n');
            this.copyButtonText = 'Salin Teks WhatsApp';
            this.waModalOpen = true;
        },

        copyWaText() {
            navigator.clipboard.writeText(this.waText).then(() => {
                this.copyButtonText = '✅ Tersalin ke Clipboard!';
                setTimeout(() => {
                    this.copyButtonText = 'Salin Teks WhatsApp';
                    this.waModalOpen = false;
                }, 1500);
            }).catch(() => {
                alert('Gagal menyalin otomatis. Silakan salin teks secara manual.');
            });
        }
    };
}
</script>

<style>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
@endsection
