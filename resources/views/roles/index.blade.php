@extends('layouts.app')

@section('title', 'Manajemen Peran & Hak Akses (RBAC)')

@section('content')
<div class="max-w-7xl mx-auto" x-data="{
    activeTab: 'matrix',
    searchQuery: '',
    selectedGroup: 'all',
    isExpanded: false,
    canScrollLeft: false,
    canScrollRight: true,
    tableScrollWidth: 0,
    tableClientWidth: 0,

    // Drag-to-scroll state
    isDragging: false,
    dragStartX: 0,
    dragScrollLeft: 0,
    hasDragged: false,

    init() {
        this.$nextTick(() => {
            this.updateScrollMetrics();
        });
        window.addEventListener('resize', () => this.updateScrollMetrics());
    },

    updateScrollMetrics() {
        const el = this.$refs.tableWrapper;
        if (!el) return;
        this.tableScrollWidth = el.scrollWidth;
        this.tableClientWidth = el.clientWidth;
        this.canScrollLeft = el.scrollLeft > 10;
        this.canScrollRight = el.scrollLeft < (el.scrollWidth - el.clientWidth - 15);
    },

    syncScroll(source) {
        const table = this.$refs.tableWrapper;
        const top = this.$refs.topScrollBar;
        if (!table || !top) return;

        if (source === 'top') {
            if (Math.abs(table.scrollLeft - top.scrollLeft) > 1) {
                table.scrollLeft = top.scrollLeft;
            }
        } else if (source === 'table') {
            if (Math.abs(top.scrollLeft - table.scrollLeft) > 1) {
                top.scrollLeft = table.scrollLeft;
            }
        }
        this.updateScrollMetrics();
    },

    handleHeaderWheel(e) {
        const table = this.$refs.tableWrapper;
        if (!table) return;
        // Convert vertical mousewheel to smooth horizontal pan
        if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
            e.preventDefault();
            table.scrollLeft += (e.deltaY * 1.2);
            this.syncScroll('table');
        }
    },

    startDrag(e) {
        if (e.button !== 0) return;
        if (e.target.closest('a, button, input, select, label, .no-drag')) return;
        const table = this.$refs.tableWrapper;
        if (!table) return;

        this.isDragging = true;
        this.hasDragged = false;
        this.dragStartX = e.pageX;
        this.dragScrollLeft = table.scrollLeft;
    },

    onDrag(e) {
        if (!this.isDragging) return;
        const table = this.$refs.tableWrapper;
        if (!table) return;

        const delta = e.pageX - this.dragStartX;
        if (Math.abs(delta) > 3) {
            this.hasDragged = true;
        }
        table.scrollLeft = this.dragScrollLeft - delta;
        this.syncScroll('table');
    },

    stopDrag() {
        this.isDragging = false;
    },

    scrollTable(direction) {
        const el = this.$refs.tableWrapper;
        if (!el) return;
        const amount = direction === 'left' ? -320 : 320;
        el.scrollBy({ left: amount, behavior: 'smooth' });
    },

    jumpToRole(slug) {
        if (!slug) return;
        const col = document.getElementById('role-col-' + slug);
        const wrapper = this.$refs.tableWrapper;
        if (col && wrapper) {
            // Offset for the sticky first column (~320px)
            const targetLeft = Math.max(0, col.offsetLeft - 320);
            wrapper.scrollTo({ left: targetLeft, behavior: 'smooth' });
        }
    },

    matchesSearch(label, name, group) {
        if (this.selectedGroup !== 'all' && group !== this.selectedGroup) {
            return false;
        }
        if (!this.searchQuery) return true;
        const q = this.searchQuery.toLowerCase();
        return label.toLowerCase().includes(q) || name.toLowerCase().includes(q) || group.toLowerCase().includes(q);
    }
}" @mousemove.window="onDrag($event)" @mouseup.window="stopDrag()">
    <!-- Header -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('users.index') }}" class="text-xs font-bold text-gray-400 hover:text-primary uppercase tracking-wider transition">
                    Manajemen Pengguna
                </a>
                <span class="text-gray-300">&bull;</span>
                <span class="text-xs font-bold text-primary uppercase tracking-wider">Peran & Izin</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-black text-gray-800 tracking-tight">Peran & Matriks Hak Akses (RBAC)</h1>
            <p class="text-gray-500 text-sm mt-0.5">Kelola peran dinamis dan konfigurasikan matriks hak akses (*granular permissions*) untuk seluruh modul sistem.</p>
        </div>
        <div class="flex items-center flex-wrap gap-2.5">
            <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-xl font-bold uppercase tracking-wider text-xs transition shadow-xs">
                <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Daftar Pengguna
            </a>
            @can('roles.manage')
            <a href="{{ route('roles.create') }}" class="inline-flex items-center px-5 py-2.5 bg-primary hover:bg-blue-800 text-white rounded-xl font-bold uppercase tracking-wider text-xs shadow-lg shadow-blue-100 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Tambah Peran Baru
            </a>
            @endcan
        </div>
    </div>

    <!-- Quick Stat Overview Bar -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Peran</p>
                <p class="text-2xl font-black text-gray-800 mt-0.5">{{ $roles->count() }} Peran</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Hak Akses</p>
                <p class="text-2xl font-black text-primary mt-0.5">{{ \App\Models\Permission::count() }} Izin</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-primary flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Modul Sistem</p>
                <p class="text-2xl font-black text-emerald-600 mt-0.5">{{ $permissionGroups->count() }} Modul</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Fitur Baru Aktif</p>
                <div class="flex items-center gap-1.5 mt-1">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">Gantt</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Task Internal</span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
        </div>
    </div>

    <!-- Tab Navigation & Controls Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 bg-white p-2 rounded-2xl border border-gray-200 shadow-xs">
        <div class="inline-flex rounded-xl bg-gray-100 p-1">
            <button type="button" @click="activeTab = 'matrix'; $nextTick(() => updateScrollMetrics())"
                    :class="activeTab === 'matrix' ? 'bg-white text-gray-900 shadow-xs font-black' : 'text-gray-500 hover:text-gray-800 font-bold'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs transition">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <span>Matriks Hak Akses (Tabel Lengkap)</span>
                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-50 text-primary">Live</span>
            </button>
            <button type="button" @click="activeTab = 'cards'"
                    :class="activeTab === 'cards' ? 'bg-white text-gray-900 shadow-xs font-black' : 'text-gray-500 hover:text-gray-800 font-bold'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs transition">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                <span>Kartu Profil Peran</span>
                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-gray-200 text-gray-700">{{ $roles->count() }}</span>
            </button>
        </div>

        <!-- Filter & Search (Active on Matrix view) -->
        <div x-show="activeTab === 'matrix'" class="flex items-center flex-wrap gap-2">
            <div class="relative">
                <input type="text" x-model="searchQuery" placeholder="Cari izin, modul, kode..."
                       class="w-full sm:w-56 pl-8 pr-3 py-1.5 text-xs rounded-xl border-gray-200 focus:border-primary focus:ring-primary shadow-2xs">
                <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <button type="button" x-show="searchQuery" @click="searchQuery = ''" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs font-bold">&times;</button>
            </div>

            <select x-model="selectedGroup" class="text-xs rounded-xl border-gray-200 py-1.5 focus:border-primary focus:ring-primary shadow-2xs text-gray-700">
                <option value="all">Semua Modul ({{ $permissionGroups->count() }})</option>
                @foreach($permissionGroups as $groupName => $perms)
                    <option value="{{ $groupName }}">{{ $groupName }} ({{ $perms->count() }})</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- TAB 1: TABEL MATRIKS HAK AKSES LENGKAP -->
    <div x-show="activeTab === 'matrix'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden flex flex-col">

            <!-- Horizontal Scroll Navigation & Toolbar Header -->
            <div class="px-5 py-2.5 bg-gradient-to-r from-gray-50 via-blue-50/20 to-gray-50 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2.5 text-gray-600">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-blue-100 text-primary font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </span>
                    <div>
                        <span class="font-black text-gray-800">Navigasi Kolom Peran:</span>
                        <span class="text-gray-500 text-[11px] ml-1">Total <strong class="text-gray-800">{{ $roles->count() }} Peran</strong>. Geser horizontal dengan tombol panah atau bilah scroll di bawah ini.</span>
                    </div>
                </div>

                <!-- Horizontal Controls: Left/Right Buttons & Jump Dropdown & Height Toggle -->
                <div class="flex items-center gap-2">
                    <!-- Quick Jump to Role Dropdown -->
                    <div class="flex items-center gap-1.5">
                        <label class="text-[11px] font-bold text-gray-500 hidden md:inline">Lompat:</label>
                        <select @change="jumpToRole($event.target.value); $event.target.value = ''"
                                class="text-xs py-1.5 px-3 rounded-xl border-gray-200 bg-white font-semibold text-gray-700 shadow-2xs focus:border-primary focus:ring-primary cursor-pointer hover:border-blue-300 transition">
                            <option value="">Lompat ke Peran...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->slug }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Gesture / Scroll hints -->
                    <span class="hidden xl:inline-flex items-center gap-1.5 text-[11px] text-gray-500 bg-white border border-gray-200 px-2.5 py-1 rounded-xl font-medium shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"/></svg>
                        <span>Tarik tabel atau putar roda mouse pada header</span>
                    </span>

                    <!-- Scroll Buttons Left / Right -->
                    <div class="inline-flex rounded-xl border border-gray-200 bg-white p-0.5 shadow-2xs">
                        <button type="button" @click="scrollTable('left')" :disabled="!canScrollLeft"
                                :class="canScrollLeft ? 'text-gray-700 hover:bg-blue-50 hover:text-primary cursor-pointer' : 'text-gray-300 cursor-not-allowed'"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold transition"
                                title="Geser ke kiri (Peran sebelumnya)">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                            <span class="hidden sm:inline">Kiri</span>
                        </button>
                        <button type="button" @click="scrollTable('right')" :disabled="!canScrollRight"
                                :class="canScrollRight ? 'text-gray-700 hover:bg-blue-50 hover:text-primary cursor-pointer' : 'text-gray-300 cursor-not-allowed'"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold transition"
                                title="Geser ke kanan (Peran selanjutnya)">
                            <span class="hidden sm:inline">Kanan</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>

                    <!-- Height Toggle Button -->
                    <button type="button" @click="isExpanded = !isExpanded; $nextTick(() => updateScrollMetrics())"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 text-xs font-bold transition shadow-2xs cursor-pointer"
                            :title="isExpanded ? 'Kunci scrollbar di dalam layar' : 'Buka tinggi penuh tabel'">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!isExpanded" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                            <path x-show="isExpanded" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span x-text="isExpanded ? 'Tinggi Standar' : 'Layar Penuh'"></span>
                    </button>
                </div>
            </div>

            <!-- Synchronized Top Scrollbar (Directly Above Table Header) -->
            <div class="relative bg-slate-100 border-b border-gray-200">
                <div x-ref="topScrollBar"
                     @scroll="syncScroll('top')"
                     @wheel="handleHeaderWheel($event)"
                     class="overflow-x-auto overflow-y-hidden h-3 w-full cursor-ew-resize custom-scrollbar"
                     title="Bilah scroll horizontal atas (Tarik ke samping atau putar roda mouse untuk menggeser peran)">
                    <div :style="'width: ' + tableScrollWidth + 'px; height: 1px;'"></div>
                </div>
            </div>

            <!-- Main Table Scroll Container (Fluid 60fps/120fps with drag-to-scroll) -->
            <div x-ref="tableWrapper"
                 @scroll="syncScroll('table')"
                 @mousedown="startDrag($event)"
                 :class="{
                     'max-h-none overflow-x-auto': isExpanded,
                     'max-h-[calc(100vh-270px)] min-h-[460px] overflow-auto': !isExpanded,
                     'cursor-grab': !isDragging,
                     'cursor-grabbing select-none': isDragging
                 }"
                 class="w-full relative focus:outline-none custom-scrollbar overscroll-x-contain">
                <table class="w-full text-left border-collapse min-w-full">
                    <!-- Sticky Header Row -->
                    <thead class="sticky top-0 z-30 bg-gray-100 shadow-xs select-none" @wheel="handleHeaderWheel($event)">
                        <tr class="border-b border-gray-200">
                            <!-- Corner Anchor Header Cell (Sticky top & left) -->
                            <th class="py-3.5 px-5 text-xs font-black text-gray-700 uppercase tracking-wider w-80 min-w-[320px] max-w-[320px] sticky top-0 left-0 bg-gray-100 z-40 border-r border-gray-200 shadow-[2px_0_4px_-1px_rgba(0,0,0,0.06)]">
                                <div class="flex items-center justify-between">
                                    <span>Modul & Hak Akses</span>
                                    <span class="text-[10px] font-mono text-gray-400">Key</span>
                                </div>
                            </th>
                            <!-- Role Columns Headers -->
                            @foreach($roles as $role)
                            <th id="role-col-{{ $role->slug }}" class="py-3.5 px-3 text-center min-w-[145px] border-l border-gray-100 bg-gray-100">
                                <div class="flex flex-col items-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[11px] font-black uppercase tracking-wider border shadow-2xs mb-1 {{ $role->badge_classes }}">
                                        {{ $role->name }}
                                    </span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-medium text-gray-400">
                                            @if($role->slug === 'superadmin')
                                                Bypass Mutlak
                                            @else
                                                {{ $role->permissions_count }} Izin
                                            @endif
                                        </span>
                                        @can('roles.manage')
                                        <a href="{{ route('roles.edit', $role) }}" class="text-[10px] font-bold text-primary hover:underline" title="Konfigurasi Izin {{ $role->name }}">
                                            Edit
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs">
                        @foreach($permissionGroups as $groupName => $groupPermissions)
                        <!-- Group Header Row -->
                        <tr class="bg-slate-50 font-bold border-t border-b border-gray-200"
                            x-show="{{ json_encode($groupPermissions->map(fn($p) => ['label' => $p->label, 'name' => $p->name, 'group' => $p->group])) }}.some(p => matchesSearch(p.label, p.name, p.group))">
                            <!-- Fixed Sticky First Column: Module Group Title -->
                            <td class="py-2.5 px-5 sticky left-0 bg-slate-100 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] border-r border-gray-200 w-80 min-w-[320px] max-w-[320px]">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="w-2.5 h-2.5 rounded-full bg-primary shrink-0"></span>
                                        <span class="text-xs font-black text-gray-800 uppercase tracking-wider truncate">{{ $groupName }}</span>
                                    </div>
                                    <span class="text-[10px] text-gray-500 font-bold bg-white px-1.5 py-0.5 rounded border border-gray-200 shrink-0">
                                        {{ $groupPermissions->count() }} Izin
                                    </span>
                                </div>
                            </td>
                            <!-- Remaining Role Columns Span -->
                            <td colspan="{{ $roles->count() }}" class="py-2.5 px-4 bg-slate-50 border-t border-b border-gray-200">
                                @if(in_array($groupName, ['Operasional Internal', 'Manajemen Proyek']))
                                <span class="inline-flex items-center text-[10px] font-bold uppercase px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    Termasuk Fitur Baru (Gantt & Task Internal)
                                </span>
                                @endif
                            </td>
                        </tr>

                        <!-- Permission Items Rows -->
                        @foreach($groupPermissions as $permission)
                        @php
                            $isNewFeature = in_array($permission->name, ['projects.gantt', 'projects.gantt_manage', 'company_tasks.view', 'company_tasks.manage']);
                        @endphp
                        <tr class="hover:bg-blue-50/20 transition-colors group"
                            x-show="matchesSearch('{{ addslashes($permission->label) }}', '{{ $permission->name }}', '{{ addslashes($permission->group) }}')">
                            <!-- Sticky First Column: Permission info cell -->
                            <td class="py-3 px-5 sticky left-0 bg-white group-hover:bg-blue-50/40 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] border-r border-gray-200 transition-colors w-80 min-w-[320px] max-w-[320px]">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="font-bold text-gray-900">{{ $permission->label }}</span>
                                    @if($isNewFeature)
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300">
                                        Baru
                                    </span>
                                    @endif
                                </div>
                                <div class="font-mono text-[10.5px] text-gray-400 mt-0.5">{{ $permission->name }}</div>
                                <p class="text-[11px] text-gray-500 mt-0.5 leading-snug">{{ $permission->description }}</p>
                            </td>

                            <!-- Role check columns -->
                            @foreach($roles as $role)
                            @php
                                $hasPerm = ($role->slug === 'superadmin') || $role->permissions->contains('name', $permission->name);
                            @endphp
                            <td class="py-3 px-3 text-center border-l border-gray-100 align-middle">
                                @if($role->slug === 'superadmin')
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded text-[10px] font-black bg-purple-50 text-purple-700 border border-purple-200" title="Super Admin memiliki akses mutlak tanpa batas">
                                        Bypass
                                    </span>
                                @elseif($hasPerm)
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200 shadow-2xs mx-auto" title="Peran {{ $role->name }} memiliki izin {{ $permission->label }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                @else
                                    <span class="text-gray-300 font-bold text-sm select-none" title="Peran {{ $role->name }} tidak memiliki izin ini">&minus;</span>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Footer Help Note & Legend -->
            <div class="p-4 bg-gray-50/90 border-t border-gray-200 text-xs text-gray-500 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shrink-0">
                <div class="flex items-center flex-wrap gap-4">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>
                        <span class="font-medium text-gray-700">Diberikan (Granted)</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="text-gray-300 font-bold">&minus;</span>
                        <span class="font-medium text-gray-500">Tidak Diberikan</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-purple-50 text-purple-700 border border-purple-200">Bypass</span>
                        <span class="font-medium text-purple-700">Super Admin (Full Mutlak)</span>
                    </span>
                </div>
                <div class="text-[11px] text-gray-400">
                    💡 <strong>Tips:</strong> Tekan <strong>Shift + Scroll Mouse</strong> atau gunakan tombol <strong>Kiri / Kanan</strong> di atas untuk menggeser kolom peran dengan cepat.
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: KARTU PROFIL PERAN -->
    <div x-show="activeTab === 'cards'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
            @foreach($roles as $role)
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs hover:shadow-md transition flex flex-col justify-between overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-black uppercase tracking-wider border shadow-xs {{ $role->badge_classes }}">
                                {{ $role->name }}
                            </span>
                            @if($role->is_system)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-gray-100 text-gray-600 border border-gray-200" title="Peran inti sistem bawaan">
                                    Sistem
                                </span>
                            @endif
                        </div>
                        <span class="text-xs font-mono text-gray-400 bg-gray-50 px-2 py-0.5 rounded border border-gray-100">
                            {{ $role->slug }}
                        </span>
                    </div>

                    <p class="text-xs text-gray-600 leading-relaxed mb-4 min-h-[32px]">
                        {{ $role->description ?: 'Tidak ada deskripsi peran.' }}
                    </p>

                    <!-- Statistics & Badges -->
                    <div class="grid grid-cols-2 gap-2 p-3 rounded-xl bg-gray-50/80 border border-gray-100 text-center mb-4">
                        <div>
                            <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Pengguna</div>
                            <div class="text-base font-black text-gray-800">{{ $role->users_count }} Akun</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Hak Akses</div>
                            <div class="text-base font-black text-primary">
                                @if($role->slug === 'superadmin')
                                    Semua (Full)
                                @else
                                    {{ $role->permissions_count }} Izin
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Preview Active Permissions -->
                    <div>
                        <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Cakupan Izin Utama:</div>
                        <div class="flex flex-wrap gap-1">
                            @if($role->slug === 'superadmin')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                    Bypass Mutlak Seluruh Modul & Fitur
                                </span>
                            @elseif($role->permissions->isEmpty())
                                <span class="text-xs text-gray-400 italic">Belum ada izin yang dikonfigurasi.</span>
                            @else
                                @foreach($role->permissions->take(7) as $perm)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                        {{ $perm->label }}
                                    </span>
                                @endforeach
                                @if($role->permissions->count() > 7)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600">
                                        +{{ $role->permissions->count() - 7 }} lainnya
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Card Actions Footer -->
                <div class="px-6 py-3.5 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                    @can('roles.manage')
                    <a href="{{ route('roles.edit', $role) }}" class="inline-flex items-center text-xs font-bold text-primary hover:text-blue-800 transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Konfigurasi Izin & Edit
                    </a>

                    @if(!$role->is_system && $role->users_count === 0)
                    <form action="{{ route('roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus peran {{ $role->name }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-bold text-rose-500 hover:text-rose-700 transition">
                            Hapus
                        </button>
                    </form>
                    @elseif(!$role->is_system)
                        <span class="text-[10px] font-medium text-gray-400" title="Tidak dapat dihapus karena masih digunakan oleh pengguna">Digunakan</span>
                    @endif
                    @else
                    <span class="text-xs text-gray-400 italic">Hanya lihat</span>
                    @endcan
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<style>
    /* Sleek Fluid Scrollbars */
    .custom-scrollbar::-webkit-scrollbar {
        height: 7px;
        width: 7px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 9999px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 9999px;
        transition: background 0.15s ease;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .overscroll-x-contain {
        overscroll-behavior-x: contain;
    }
    .cursor-grab {
        cursor: grab;
    }
    .cursor-grabbing {
        cursor: grabbing !important;
    }
</style>
@endsection
