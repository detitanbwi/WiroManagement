@extends('layouts.app')

@section('title', 'Gantt Chart & WBS - ' . $project->title)

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.css">
<script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Frappe Gantt Custom Modern Styling */
    .gantt-container {
        overflow-x: auto !important;
        overflow-y: visible !important;
        border-radius: 0.75rem;
        background: #ffffff;
        padding-bottom: 0 !important;
        margin-bottom: 0 !important;
        position: relative;
    }
    .gantt {
        display: block;
        margin-bottom: 0 !important;
    }
    .gantt .grid-header {
        fill: #f8fafc;
        stroke: #e2e8f0;
        stroke-width: 1;
    }
    .gantt .grid-row {
        fill: #ffffff;
    }
    .gantt .grid-row:nth-child(even) {
        fill: #fcfcfd;
    }
    .gantt .row-line {
        stroke: #f1f5f9;
    }
    .gantt .tick {
        stroke: #f1f5f9;
        stroke-width: 1;
    }
    .gantt .lower-text, .gantt .upper-text {
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        font-weight: 600;
        fill: #64748b;
    }
    .gantt .today-highlight {
        fill: #fef3c7 !important;
        opacity: 0.75 !important;
        stroke: #f59e0b !important;
        stroke-width: 1.5px !important;
        stroke-opacity: 0.45 !important;
        pointer-events: none !important;
    }
    .gantt .arrow {
        stroke: #6366f1;
        stroke-width: 1.8;
        fill: none;
    }

    /* Bar Styling */
    .gantt .bar {
        rx: 5px;
        ry: 5px;
        transition: transform 0.15s ease, filter 0.15s ease;
    }
    .gantt .bar:hover {
        filter: brightness(1.08);
        cursor: pointer;
    }
    .gantt .bar-label {
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        font-weight: 600;
        fill: #ffffff;
    }
    .gantt .handle {
        fill: #ffffff;
        stroke: #cbd5e1;
        stroke-width: 2;
        rx: 3px;
        ry: 3px;
    }

    /* Parent Task Bar */
    .bar-parent .bar {
        fill: #1e293b !important;
    }
    .bar-parent .bar-progress {
        fill: #475569 !important;
    }

    /* Completed Bar */
    .bar-completed .bar {
        fill: #059669 !important;
    }
    .bar-completed .bar-progress {
        fill: #10b981 !important;
    }

    /* In-Progress Bar */
    .bar-inprogress .bar {
        fill: #4f46e5 !important;
    }
    .bar-inprogress .bar-progress {
        fill: #6366f1 !important;
    }

    /* Default Bar */
    .bar-task .bar {
        fill: #94a3b8 !important;
    }
    .bar-task .bar-progress {
        fill: #cbd5e1 !important;
    }

    /* Custom Compact Light Gantt Popup Banner */
    .gantt-container .popup-wrapper {
        position: absolute;
        top: 0;
        left: 0;
        background: transparent !important;
        padding: 0 !important;
        border: none !important;
        z-index: 1000 !important;
        pointer-events: auto !important;
        filter: drop-shadow(0 10px 15px rgba(0, 0, 0, 0.08));
    }
    .gantt-container .popup-wrapper .pointer {
        display: none !important;
    }

    .gantt-popup {
        background: #ffffff;
        color: #0f172a;
        border-radius: 0.75rem;
        padding: 12px 14px;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        font-size: 11.5px;
        line-height: 1.4;
        width: 320px;
        max-width: calc(100vw - 24px);
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 25px -4px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
        z-index: 1000;
        transition: all 0.15s ease;
    }
    .gantt-popup .title {
        font-weight: 700;
        font-size: 13px;
        color: #0f172a;
        margin-top: 5px;
        margin-bottom: 5px;
        line-height: 1.35;
        word-break: break-word;
    }
    .gantt-popup .badge {
        display: inline-flex;
        align-items: center;
        padding: 1.5px 6px;
        border-radius: 4px;
        font-size: 9.5px;
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    .gantt-popup-desc {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 6px 8px;
        margin-top: 5px;
        margin-bottom: 7px;
    }
    .gantt-popup-desc .desc-label {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        margin-bottom: 2px;
        display: flex;
        align-items: center;
        gap: 3.5px;
    }
    .gantt-popup-desc .desc-content {
        font-size: 11px;
        line-height: 1.45;
        color: #334155;
        max-height: 80px;
        overflow-y: auto;
        white-space: pre-wrap;
        word-break: break-word;
    }
</style>

<div class="space-y-6 pb-16" x-data="ganttManager({{ auth()->user()->can('projects.gantt_manage') ? 'true' : 'false' }})" x-init="initData()">
    <!-- Top Header -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 px-6 py-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <nav class="flex text-xs text-gray-500 mb-1.5 items-center space-x-2">
                <a href="{{ route('projects.index') }}" class="hover:text-primary transition-colors">Proyek</a>
                <span>/</span>
                <a href="{{ route('projects.show', $project) }}" class="hover:text-primary transition-colors">{{ $project->title }}</a>
                <span>/</span>
                <span class="text-gray-800 font-semibold">Gantt Chart & WBS</span>
            </nav>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Gantt Chart & WBS Timeline</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    Auto-Rollup Engine
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                Proyek: <span class="font-semibold text-gray-700">{{ $project->title }}</span>
                &bull; Client: <span class="text-gray-700">{{ $project->client->name ?? '-' }}</span>
            </p>
        </div>

        <div class="flex items-center flex-wrap gap-2.5">
            <a href="{{ route('projects.show', $project) }}" class="px-3.5 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 shadow-xs transition-colors">
                &larr; Detail Proyek
            </a>
            @can('projects.gantt_manage')
            <button @click="openCreateModal(null)" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-xs shadow-xs transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Tugas Utama
            </button>
            @else
            <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                Mode Hanya Lihat (Read-only)
            </span>
            @endcan
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-200 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Tugas (WBS)</p>
                <p class="text-2xl font-bold text-gray-900 mt-1" x-text="leafTasks.length">0</p>
                <template x-if="parentTasks.length > 0">
                    <p class="text-[11px] text-gray-400 mt-0.5">
                        <span class="font-medium text-gray-600" x-text="parentTasks.length"></span> Induk &bull; <span class="font-medium text-gray-600" x-text="leafTasks.length"></span> Anak Tugas
                    </p>
                </template>
            </div>
            <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-200 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Progres Proyek</p>
                <p class="text-2xl font-bold text-indigo-600 mt-1" x-text="project.progress_percentage + '%'">0%</p>
                <p class="text-[11px] text-gray-400 mt-0.5">
                    Rata-rata dari <span class="font-medium text-gray-600" x-text="leafTasks.length"></span> tugas pengerjaan
                </p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                %
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-200 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Selesai (Completed)</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1" x-text="completedTasksCount">0</p>
                <p class="text-[11px] text-gray-400 mt-0.5">
                    <span class="font-medium text-emerald-600" x-text="(leafTasks.length > 0 ? Math.round((completedTasksCount / leafTasks.length) * 100) : 0) + '%'"></span> dari total tugas (<span x-text="completedTasksCount"></span>/<span x-text="leafTasks.length"></span> selesai)
                </p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-200 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Sedang Berjalan</p>
                <p class="text-2xl font-bold text-amber-600 mt-1" x-text="inProgressTasksCount">0</p>
                <p class="text-[11px] text-gray-400 mt-0.5">
                    <span class="font-medium text-amber-600" x-text="(leafTasks.length > 0 ? Math.round((inProgressTasksCount / leafTasks.length) * 100) : 0) + '%'"></span> berjalan &bull; <span x-text="todoTasksCount"></span> belum dimulai
                </p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    <!-- Gantt Chart Timeline View Card -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-6 pb-4 flex flex-col space-y-4">
        <!-- Gantt Controls Toolbar -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-gray-100">
            <!-- Mode Switcher -->
            <div class="flex items-center space-x-2">
                <span class="text-xs font-medium text-gray-500">Skala Waktu:</span>
                <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1" role="group">
                    <button type="button" 
                            @click="setViewMode('Day')" 
                            :class="viewMode === 'Day' ? 'bg-white shadow-xs text-indigo-600 font-bold' : 'text-gray-600 hover:text-gray-900'"
                            class="px-3 py-1 text-xs rounded-md transition-all">
                        Harian (Day)
                    </button>
                    <button type="button" 
                            @click="setViewMode('Week')" 
                            :class="viewMode === 'Week' ? 'bg-white shadow-xs text-indigo-600 font-bold' : 'text-gray-600 hover:text-gray-900'"
                            class="px-3 py-1 text-xs rounded-md transition-all">
                        Mingguan (Week)
                    </button>
                    <button type="button" 
                            @click="setViewMode('Month')" 
                            :class="viewMode === 'Month' ? 'bg-white shadow-xs text-indigo-600 font-bold' : 'text-gray-600 hover:text-gray-900'"
                            class="px-3 py-1 text-xs rounded-md transition-all">
                        Bulanan (Month)
                    </button>
                </div>
            </div>

            <!-- Legend Indicator -->
            <div class="flex items-center flex-wrap gap-4 text-xs text-gray-600">
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-xs bg-[#1e293b]"></span> 
                    <span class="font-medium">Tugas Utama (Auto-Rollup)</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-xs bg-[#059669]"></span> 
                    <span>Selesai (100%)</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-xs bg-[#4f46e5]"></span> 
                    <span>Sedang Berjalan</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-xs bg-[#94a3b8]"></span> 
                    <span>Belum Dimulai (0%)</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-xs bg-amber-100 border border-amber-400"></span> 
                    <span class="text-amber-800 font-medium">Hari Ini (Today)</span>
                </span>
            </div>
        </div>

        <!-- SVG Container for Frappe Gantt -->
        <div class="relative">
            <div x-show="loading" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-20">
                <div class="inline-flex items-center gap-2 text-sm text-indigo-600 font-semibold">
                    <svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Memuat data Gantt Chart...
                </div>
            </div>

            <div x-show="tasks.length === 0 && !loading" class="py-16 text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p class="font-medium text-gray-600">Belum ada tugas pada proyek ini.</p>
                <p class="text-xs text-gray-400 mt-1">Klik tombol "Tambah Tugas Utama" untuk mulai membuat WBS.</p>
            </div>

            <div class="w-full" x-show="tasks.length > 0">
                <svg id="gantt-target"></svg>
            </div>
        </div>
    </div>

    <!-- WBS Hierarchical Table Card -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-gray-50/50">
            <div>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Struktur Rincian Kerja (Work Breakdown Structure - WBS)</h3>
                <p class="text-xs text-gray-500 mt-0.5">Daftar hierarki tugas, penanggung jawab, durasi, dan progres pengerjaan.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="expandAll()" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 shadow-2xs transition-colors">
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    Buka Semua
                </button>
                <button type="button" @click="collapseAll()" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 shadow-2xs transition-colors">
                    <svg class="w-3.5 h-3.5 text-gray-500 -rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    Tutup Semua
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-100/75 border-b border-gray-200 text-gray-600 uppercase font-semibold select-none">
                        <th class="py-3 px-2 w-8 text-center" title="Geser / Drag"></th>
                        <th class="py-3 px-3 w-40">Kode WBS / Urutan</th>
                        <th class="py-3 px-4">Nama Tugas</th>
                        <th class="py-3 px-4 w-44">Penanggung Jawab</th>
                        <th class="py-3 px-4 w-32">Mulai</th>
                        <th class="py-3 px-4 w-32">Tenggat</th>
                        <th class="py-3 px-4 w-24">Durasi</th>
                        <th class="py-3 px-4 w-48">Progres Pengerjaan</th>
                        <th class="py-3 px-4 w-40 text-center">Aksi & Posisi</th>
                    </tr>
                </thead>
                <template x-for="task in tasks" :key="task.id">
                    <tbody class="border-b border-gray-100/80 transition-colors"
                           x-show="isTaskVisible(task)"
                           @dragleave="handleDragLeave(task, $event)">

                        <!-- Spacer Drop Area (Before Task) - Opens space between tasks -->
                        <tr x-show="draggedTask && draggedTask.db_id !== task.db_id && dragOverTarget === task.db_id && dragOverPosition === 'before'"
                            x-transition:enter="transition-all ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            @dragover.prevent.stop="dragOverTarget = task.db_id; dragOverPosition = 'before'"
                            @drop.prevent="dropOnTask(task)"
                            class="bg-indigo-50/40">
                            <td colspan="9" class="p-1.5">
                                <div :style="'padding-left: ' + (task.level * 1.5) + 'rem;'">
                                    <div class="w-full py-2.5 px-4 bg-gradient-to-r from-indigo-50 via-blue-50 to-indigo-50 border-2 border-dashed border-indigo-400 rounded-xl flex items-center justify-center gap-3 text-indigo-700 shadow-inner select-none cursor-pointer group hover:border-indigo-600 hover:bg-indigo-100/60 transition-all">
                                        <div class="w-6 h-6 rounded-lg bg-indigo-600 text-white flex items-center justify-center shadow-xs shrink-0 animate-bounce">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                            </svg>
                                        </div>
                                        <span class="font-extrabold text-xs text-indigo-900 tracking-wide">Taruh di sini</span>
                                        <span class="text-[11px] font-medium text-indigo-600">
                                            &bull; Sisipkan sebelum <span class="font-bold underline decoration-indigo-300" x-text="task.title"></span>
                                        </span>
                                        <span class="text-[10px] font-bold tracking-wider px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-700 border border-indigo-200">
                                            <span x-text="task.level === 0 ? 'Tugas Utama' : 'Sub-tugas Level ' + (task.level + 1)"></span>
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <!-- Main Task Row -->
                        <tr x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            :draggable="canManage"
                            @dragstart="canManage && startDragging(task, $event)"
                            @dragover="canManage && handleDragOver(task, $event)"
                            @drop.prevent="canManage && dropOnTask(task)"
                            @dragend="canManage && resetDrag()"
                            :class="{
                                'bg-slate-50/70 font-semibold': task.is_parent,
                                'hover:bg-gray-50/60': !task.is_parent,
                                'opacity-40': draggedTask?.db_id === task.db_id,
                                'bg-indigo-100/70 ring-2 ring-indigo-500 ring-inset': dragOverTarget === task.db_id && dragOverPosition === 'inside'
                            }"
                            class="transition-colors">
                            
                            <!-- Drag Handle -->
                            <td class="py-3 px-2 text-center">
                                <div x-show="canManage" class="cursor-grab active:cursor-grabbing text-gray-300 hover:text-indigo-600 transition-colors inline-flex items-center justify-center p-1 rounded"
                                     title="Tahan & geser untuk mengubah urutan atau memasukkan ke tugas lain">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M7 4a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0zM7 16a2 2 0 11-4 0 2 2 0 014 0zM17 4a2 2 0 11-4 0 2 2 0 014 0zM17 10a2 2 0 11-4 0 2 2 0 014 0zM17 16a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <span x-show="!canManage" class="text-gray-300 select-none">&bull;</span>
                            </td>

                            <!-- WBS Code & Quick Order Selector -->
                            <td class="py-3 px-3">
                                <div class="inline-flex items-center gap-1.5 bg-gray-50 border border-gray-200/90 rounded-lg p-1 shadow-2xs">
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[11px] font-mono font-bold shrink-0"
                                          :class="task.is_parent ? 'bg-slate-200 text-slate-800' : 'bg-white text-gray-700 shadow-2xs'"
                                          x-text="task.wbs_code"></span>
                                    <template x-if="canManage">
                                        <select @change="reorderTask(task, $event.target.value)" 
                                                class="cursor-pointer text-[10px] font-bold py-0.5 px-1 rounded border border-gray-200 bg-white hover:border-indigo-500 focus:border-indigo-500 text-gray-700 hover:text-indigo-600 transition-colors shadow-2xs"
                                                :title="'Ubah urutan tugas (posisi saat ini: #' + (getTaskIndex(task) + 1) + ' dari ' + getSiblingCount(task) + ' tugas)'">
                                            <template x-for="n in getSiblingCount(task)" :key="n">
                                                <option :value="n" :selected="n === (getTaskIndex(task) + 1)" x-text="'#' + n"></option>
                                            </template>
                                        </select>
                                    </template>
                                </div>
                            </td>

                            <!-- Task Title & Hierarchy Indentation -->
                            <td class="py-3 px-4">
                                <div class="flex items-center" :style="'padding-left: ' + (task.level * 1.5) + 'rem;'">
                                    <!-- Parent Task Dropdown Toggle (Collapse/Expand) -->
                                    <template x-if="task.is_parent">
                                        <button type="button" 
                                                @click.stop="toggleCollapse(task.db_id)" 
                                                class="p-1 -ml-1 mr-1 rounded hover:bg-slate-200 text-slate-600 focus:outline-none transition-transform"
                                                :title="isCollapsed(task.db_id) ? 'Klik untuk membuka sub-tugas' : 'Klik untuk menutup sub-tugas'">
                                            <svg class="w-3.5 h-3.5 transition-transform duration-200" 
                                                 :class="isCollapsed(task.db_id) ? '-rotate-90' : 'rotate-0'" 
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                    </template>

                                    <!-- Indent branch icon for child subtasks -->
                                    <template x-if="task.parent_id && !task.is_parent">
                                        <span class="text-gray-300 mr-2 font-mono text-sm select-none">&rdsh;</span>
                                    </template>

                                    <template x-if="task.is_parent">
                                        <svg class="w-4 h-4 text-slate-600 mr-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                        </svg>
                                    </template>

                                    <div class="flex flex-col min-w-0">
                                        <div class="flex items-center">
                                            <span :class="task.is_parent ? 'text-slate-900 font-bold' : 'text-gray-800'" x-text="task.title"></span>
                                            <template x-if="task.is_parent">
                                                <span class="ml-2 px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200" 
                                                      x-text="task.children_count + ' sub'"></span>
                                            </template>
                                            <template x-if="dragOverTarget === task.db_id && dragOverPosition === 'inside'">
                                                <span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-600 text-white shadow-xs animate-pulse">
                                                    📥 Jadikan Sub-tugas
                                                </span>
                                            </template>
                                        </div>
                                        <!-- Dependencies indicators -->
                                        <template x-if="task.dependency_details && task.dependency_details.length > 0">
                                            <div class="flex items-center gap-1 mt-1 flex-wrap">
                                                <template x-for="dep in task.dependency_details" :key="dep.id">
                                                    <span @click.stop="openLinkModal(task)"
                                                          class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 cursor-pointer transition-colors"
                                                          :title="'Garis hubung dari: [' + dep.wbs_code + '] ' + dep.title + ' (Klik untuk ubah)'">
                                                        <svg class="w-2.5 h-2.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                                        <span class="font-mono font-bold" x-text="dep.wbs_code"></span>
                                                        <span class="truncate max-w-[120px]" x-text="dep.title"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </td>

                            <!-- Assignee -->
                            <td class="py-3 px-4">
                                <template x-if="task.assignee">
                                    <div class="inline-flex items-center gap-1.5">
                                        <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 font-bold text-[10px] flex items-center justify-center shrink-0" 
                                             x-text="task.assignee.name.substring(0, 2).toUpperCase()"></div>
                                        <span class="text-gray-700 truncate max-w-[130px]" x-text="task.assignee.name"></span>
                                    </div>
                                </template>
                                <template x-if="!task.assignee">
                                    <span class="text-gray-400 italic">Belum di-assign</span>
                                </template>
                            </td>

                            <!-- Dates -->
                            <td class="py-3 px-4 text-gray-600" x-text="formatDate(task.start)"></td>
                            <td class="py-3 px-4 text-gray-600" x-text="formatDate(task.end)"></td>

                            <!-- Duration -->
                            <td class="py-3 px-4 text-gray-700 font-medium" x-text="task.duration_days + ' Hari'"></td>

                            <!-- Progress Bar + Rollup Tag -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                        <div class="h-2 rounded-full transition-all duration-300"
                                             :class="task.progress >= 100 ? 'bg-emerald-500' : (task.is_parent ? 'bg-slate-700' : 'bg-indigo-600')"
                                             :style="'width: ' + task.progress + '%'"></div>
                                    </div>
                                    <span class="font-bold text-[11px] shrink-0" 
                                          :class="task.progress >= 100 ? 'text-emerald-600' : (task.is_parent ? 'text-slate-800' : 'text-indigo-600')" 
                                          x-text="task.progress + '%'"></span>
                                </div>
                                <template x-if="task.is_parent">
                                    <span class="text-[10px] text-slate-500 font-medium block mt-0.5">
                                        Auto-Rollup (Rata-rata: <span x-text="task.children_count"></span> sub-tugas)
                                    </span>
                                </template>
                            </td>

                            <!-- Actions & Position Controls -->
                            <td class="py-3 px-4 text-center">
                                <template x-if="canManage">
                                    <div class="inline-flex items-center gap-1">
                                        <!-- Move Up button -->
                                        <button @click="moveUp(task)" 
                                                :disabled="!canMoveUp(task) || isMovingTask"
                                                :class="canMoveUp(task) ? 'text-gray-600 hover:text-indigo-600 hover:bg-indigo-50' : 'text-gray-300 cursor-not-allowed'"
                                                title="Geser ke atas"
                                                class="p-1 rounded transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                        </button>

                                        <!-- Move Down button -->
                                        <button @click="moveDown(task)" 
                                                :disabled="!canMoveDown(task) || isMovingTask"
                                                :class="canMoveDown(task) ? 'text-gray-600 hover:text-indigo-600 hover:bg-indigo-50' : 'text-gray-300 cursor-not-allowed'"
                                                title="Geser ke bawah"
                                                class="p-1 rounded transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                        </button>

                                        <!-- Move / Re-parent Dropdown Button -->
                                        <button @click="openMoveModal(task)" 
                                                title="Pindahkan ke induk lain / jadikan tugas utama"
                                                class="p-1 rounded text-indigo-600 hover:bg-indigo-50 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                        </button>

                                        <!-- Add Subtask button -->
                                        <button @click="openCreateModal(task.db_id)" 
                                                title="Tambah Sub-tugas di bawah ini"
                                                class="p-1 rounded text-emerald-600 hover:bg-emerald-50 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        </button>

                                        <!-- Manage Dependencies / Link Lines Button -->
                                        <button @click="openLinkModal(task)" 
                                                title="Atur Garis Hubung / Ketergantungan Tugas"
                                                class="p-1 rounded text-amber-600 hover:bg-amber-50 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                        </button>

                                        <!-- Edit Task -->
                                        <button @click="openEditModal(task)" 
                                                title="Edit Tugas"
                                                class="p-1 rounded text-gray-500 hover:bg-gray-100 hover:text-gray-800 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>

                                        <!-- Delete Task -->
                                        <button @click="deleteTask(task.db_id)" 
                                                title="Hapus Tugas"
                                                class="p-1 rounded text-red-500 hover:bg-red-50 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </template>
                                <template x-if="!canManage">
                                    <span class="text-[11px] font-semibold text-slate-400 italic">Hanya lihat</span>
                                </template>
                            </td>
                        </tr>

                        <!-- Spacer Drop Area (After Task) - Opens space between tasks -->
                        <tr x-show="draggedTask && draggedTask.db_id !== task.db_id && dragOverTarget === task.db_id && dragOverPosition === 'after'"
                            x-transition:enter="transition-all ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            @dragover.prevent.stop="dragOverTarget = task.db_id; dragOverPosition = 'after'"
                            @drop.prevent="dropOnTask(task)"
                            class="bg-indigo-50/40">
                            <td colspan="9" class="p-1.5">
                                <div :style="'padding-left: ' + (task.level * 1.5) + 'rem;'">
                                    <div class="w-full py-2.5 px-4 bg-gradient-to-r from-indigo-50 via-blue-50 to-indigo-50 border-2 border-dashed border-indigo-400 rounded-xl flex items-center justify-center gap-3 text-indigo-700 shadow-inner select-none cursor-pointer group hover:border-indigo-600 hover:bg-indigo-100/60 transition-all">
                                        <div class="w-6 h-6 rounded-lg bg-indigo-600 text-white flex items-center justify-center shadow-xs shrink-0 animate-bounce">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                            </svg>
                                        </div>
                                        <span class="font-extrabold text-xs text-indigo-900 tracking-wide">Taruh di sini</span>
                                        <span class="text-[11px] font-medium text-indigo-600">
                                            &bull; Sisipkan setelah <span class="font-bold underline decoration-indigo-300" x-text="task.title"></span>
                                        </span>
                                        <span class="text-[10px] font-bold tracking-wider px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-700 border border-indigo-200">
                                            <span x-text="task.level === 0 ? 'Tugas Utama' : 'Sub-tugas Level ' + (task.level + 1)"></span>
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </template>

                <template x-if="tasks.length === 0">
                    <tbody>
                        <tr>
                            <td colspan="9" class="p-8 text-center text-gray-400 text-sm">
                                Belum ada tugas dalam proyek ini.
                            </td>
                        </tr>
                    </tbody>
                </template>
            </table>
        </div>

        <!-- Drop target zone for promoting to root level (Level 1) -->
        <div x-show="draggedTask && draggedTask.parent_id" 
             x-cloak
             @dragover.prevent="dragOverTarget = 'root'" 
             @dragleave="if (dragOverTarget === 'root') dragOverTarget = null" 
             @drop.prevent="dropOnRoot()" 
             :class="dragOverTarget === 'root' ? 'bg-indigo-100 border-indigo-500 text-indigo-700 scale-[1.01]' : 'bg-gray-50 border-gray-300 text-gray-500 hover:border-indigo-400'"
             class="m-4 p-3 border-2 border-dashed rounded-xl text-center text-xs font-semibold transition-all cursor-pointer flex items-center justify-center gap-2">
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
            <span>Lepaskan di sini untuk menjadikan <strong>Tugas Utama (WBS Level 1)</strong></span>
        </div>
    </div>

    <!-- Modal Form (Tambah / Edit Tugas) -->
    <div x-show="modalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="modalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" 
                 @click="modalOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="modalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
                
                <form @submit.prevent="saveTask()">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                            <h3 class="text-lg font-bold text-gray-900" x-text="isEdit ? 'Edit Tugas WBS' : (formData.parent_id ? 'Tambah Sub-tugas' : 'Tambah Tugas Utama')"></h3>
                            <button type="button" @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="space-y-4 mt-4">
                            <!-- Parent Task Selector -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Tugas Induk (Parent Task / WBS Level 1)</label>
                                <select x-model="formData.parent_id" class="w-full rounded-lg border-gray-300 text-sm py-2 px-3 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-gray-800">
                                    <option value="">-- Tanpa Induk (Tugas Utama / WBS Level 1) --</option>
                                    <template x-for="p in potentialParents" :key="p.db_id">
                                        <option :value="p.db_id" x-text="(p.wbs_code ? p.wbs_code + ' - ' : '') + p.title"></option>
                                    </template>
                                </select>
                                <p class="text-[11px] text-gray-500 mt-1">Pilih tugas induk jika tugas ini adalah sub-tugas (Child task).</p>
                            </div>

                            <!-- Position / Sort Order (in edit mode) -->
                            <template x-if="isEdit">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Nomor Urutan (Posisi di dalam Induk)</label>
                                    <select x-model="formData.sort_order" class="w-full rounded-lg border-gray-300 text-sm py-2 px-3 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-gray-800">
                                        <template x-for="n in getFormSiblingCount()" :key="n">
                                            <option :value="n" x-text="'Urutan #' + n" :selected="n === formData.sort_order"></option>
                                        </template>
                                    </select>
                                    <p class="text-[11px] text-gray-500 mt-1">Mengubah urutan akan otomatis menggeser tugas lain di tingkat yang sama.</p>
                                </div>
                            </template>

                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Judul Tugas <span class="text-red-500">*</span></label>
                                <input type="text" x-model="formData.title" required placeholder="Contoh: Modul 1: Dashboard"
                                       class="w-full rounded-lg border-gray-300 text-sm py-2.5 px-3.5 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 placeholder:text-gray-400 text-gray-900 font-medium">
                            </div>

                            <!-- Description -->
                            <div>
                                <div class="flex justify-between items-center mb-1.5">
                                    <label class="block text-sm font-semibold text-gray-800">Deskripsi Tugas</label>
                                    <span class="text-[11px] text-gray-400 font-normal">Opsional</span>
                                </div>
                                <textarea x-model="formData.description" rows="5" placeholder="Tuliskan rincian atau catatan tugas ini..."
                                          class="w-full rounded-lg border-gray-300 text-sm leading-relaxed p-3.5 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 placeholder:text-gray-400 text-gray-900 font-normal resize-y min-h-[110px]"></textarea>
                                <p class="text-[11px] text-gray-500 mt-1">Rincian tugas untuk membantu tim memahami cakupan pekerjaan.</p>
                            </div>

                            <!-- Assignee -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Penanggung Jawab (Assignee)</label>
                                <select x-model="formData.assignee_id" class="w-full rounded-lg border-gray-300 text-sm py-2 px-3 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-gray-800">
                                    <option value="">-- Belum Ditentukan --</option>
                                    <template x-for="user in users" :key="user.id">
                                        <option :value="user.id" x-text="user.name"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Dates -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Tanggal Mulai <span class="text-red-500">*</span></label>
                                    <input type="date" x-model="formData.start_date" required
                                           class="w-full rounded-lg border-gray-300 text-sm py-2 px-3 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-gray-800">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Tenggat Waktu <span class="text-red-500">*</span></label>
                                    <input type="date" x-model="formData.due_date" required
                                           class="w-full rounded-lg border-gray-300 text-sm py-2 px-3 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-gray-800">
                                </div>
                            </div>

                            <!-- Progress (Auto-calculated if has children) -->
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="text-xs font-semibold text-gray-700">Progres Pengerjaan (%)</label>
                                    <span class="font-mono font-bold text-indigo-600 text-xs" x-text="formData.progress + '%'"></span>
                                </div>
                                <template x-if="editingTaskHasChildren">
                                    <div class="p-2.5 bg-amber-50 rounded-lg border border-amber-200 text-amber-800 text-[11px]">
                                        <span class="font-bold">Progres Terkunci:</span> Tugas utama ini memiliki sub-tugas. Nilai persentase dihitung secara otomatis dan dinamis dari rata-rata seluruh sub-tugasnya.
                                    </div>
                                </template>
                                <template x-if="!editingTaskHasChildren">
                                    <div>
                                        <input type="range" min="0" max="100" step="5" x-model="formData.progress"
                                               class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                                        <div class="flex justify-between text-[10px] text-gray-400 mt-1">
                                            <span>0% (Todo)</span>
                                            <span>50% (In Progress)</span>
                                            <span>100% (Done)</span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Garis Hubung / Dependensi (Predecessors) -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-semibold text-gray-700">
                                        <span class="inline-flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                            Garis Hubung / Tugas Prasyarat (Predecessors)
                                        </span>
                                    </label>
                                    <span class="text-[11px] font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full" 
                                          x-text="(formData.dependency_ids ? formData.dependency_ids.length : 0) + ' prasyarat terhubung'"></span>
                                </div>
                                <div class="max-h-36 overflow-y-auto border border-gray-200 rounded-lg p-2 space-y-1 bg-gray-50/50">
                                    <template x-for="t in potentialDependencies" :key="t.db_id">
                                        <label class="flex items-center gap-2.5 p-1.5 rounded hover:bg-white hover:shadow-2xs cursor-pointer transition-all border border-transparent hover:border-gray-200 select-none">
                                            <input type="checkbox" :value="t.db_id" x-model="formData.dependency_ids"
                                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-200 text-slate-700 shrink-0" 
                                                      x-text="t.wbs_code || '-'"></span>
                                                <span class="text-xs text-gray-800 font-medium truncate" x-text="t.title"></span>
                                            </div>
                                        </label>
                                    </template>
                                    <template x-if="potentialDependencies.length === 0">
                                        <p class="text-xs text-gray-400 italic py-2 text-center">Tidak ada tugas lain yang tersedia sebagai prasyarat.</p>
                                    </template>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-1">Centang tugas yang harus selesai terlebih dahulu agar garis panah penghubung muncul di diagram Gantt.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-2.5">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit" :disabled="saving" class="inline-flex items-center gap-1.5 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-sm shadow-xs transition-colors disabled:opacity-50">
                            <span x-show="!saving" x-text="isEdit ? 'Simpan Perubahan' : 'Buat Tugas'"></span>
                            <span x-show="saving">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Pindahkan Tugas (Mekanisme Dropdown Pemindahan Tugas) -->
    <div x-show="moveModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-move-title" 
         role="dialog" 
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="moveModalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" 
                 @click="moveModalOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="moveModalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            Pindahkan Posisi / Induk Tugas
                        </h3>
                        <button type="button" @click="moveModalOpen = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="mt-4 space-y-4 text-xs">
                        <div class="p-3 bg-indigo-50/60 rounded-xl border border-indigo-100">
                            <span class="text-indigo-600 block text-[11px] font-semibold mb-0.5">Tugas yang Dipindahkan:</span>
                            <span class="font-bold text-gray-900 text-sm" x-text="taskToMove ? ((taskToMove.wbs_code ? taskToMove.wbs_code + ' ' : '') + taskToMove.title) : ''"></span>
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Pilih Tujuan Tugas Induk (Parent Task):</label>
                            <select x-model="moveTargetParentId" class="w-full rounded-lg border-gray-300 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Jadikan Tugas Utama (WBS Level 1) --</option>
                                <template x-for="p in eligibleMoveParents" :key="p.db_id">
                                    <option :value="p.db_id" x-text="(p.wbs_code ? p.wbs_code + ' - ' : '') + p.title"></option>
                                </template>
                            </select>
                            <p class="text-[11px] text-gray-500 mt-1">Pilih tugas induk tempat tugas ini akan dimasukkan sebagai sub-tugas.</p>
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-700 mb-1">Nomor Urutan Posisi di Induk Tujuan:</label>
                            <select x-model="moveTargetOrder" class="w-full rounded-lg border-gray-300 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                                <template x-for="n in getTargetSiblingCount(moveTargetParentId)" :key="n">
                                    <option :value="n" x-text="'Urutan #' + n + (n === 1 ? ' (Paling Atas)' : (n === getTargetSiblingCount(moveTargetParentId) ? ' (Paling Bawah)' : ''))"></option>
                                </template>
                            </select>
                            <p class="text-[11px] text-gray-500 mt-1">Pilih urutan nomor di mana tugas ini akan ditempatkan.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-2.5">
                    <button type="button" @click="moveModalOpen = false" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="button" @click="confirmMove()" :disabled="isMovingTask" class="inline-flex items-center gap-1.5 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-xs shadow-xs transition-colors disabled:opacity-50">
                        <span x-show="!isMovingTask">Pindahkan Sekarang</span>
                        <span x-show="isMovingTask">Memindahkan...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Atur Garis Hubung / Dependensi Tugas (Quick Link Modal) -->
    <div x-show="linkModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-link-title" 
         role="dialog" 
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="linkModalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" 
                 @click="linkModalOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="linkModalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                
                <div class="bg-gradient-to-r from-amber-600 via-amber-700 to-indigo-700 px-6 py-4 text-white flex justify-between items-center">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-white/20 rounded-xl backdrop-blur-xs">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold leading-6">Atur Garis Hubung (Dependensi)</h3>
                            <p class="text-xs text-amber-100">Hubungkan tugas ini dengan tugas lain pada diagram Gantt</p>
                        </div>
                    </div>
                    <button type="button" @click="linkModalOpen = false" class="text-white/80 hover:text-white rounded-lg p-1 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4 text-xs">
                    <!-- Task Target Info -->
                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200/80">
                        <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider block mb-0.5">Tugas Sasaran (Successor):</span>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-white text-amber-900 border border-amber-200 shadow-2xs" x-text="taskToLink?.wbs_code || '-'"></span>
                            <span class="font-bold text-sm text-amber-950 truncate" x-text="taskToLink?.title"></span>
                        </div>
                        <p class="text-[11px] text-amber-700 mt-1.5 leading-relaxed">
                            Diagram Gantt akan otomatis menggambar <strong>garis panah</strong> dari tugas-tugas prasyarat yang dicentang di bawah menuju ke tugas ini.
                        </p>
                    </div>

                    <!-- Checklist -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="font-bold text-gray-700 text-xs">Pilih Tugas Prasyarat (Predecessors):</label>
                            <span class="text-[11px] font-bold text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-full" 
                                  x-text="selectedLinkIds.length + ' tugas terhubung'"></span>
                        </div>
                        <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-xl p-2 space-y-1.5 bg-gray-50/50">
                            <template x-for="t in eligibleLinkDependencies" :key="t.db_id">
                                <label class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-white hover:shadow-2xs cursor-pointer transition-all border border-transparent hover:border-gray-200 select-none">
                                    <input type="checkbox" :value="t.db_id" x-model="selectedLinkIds"
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-200 text-slate-700 shrink-0" 
                                              x-text="t.wbs_code || '-'"></span>
                                        <span class="text-xs text-gray-800 font-medium truncate" x-text="t.title"></span>
                                    </div>
                                </label>
                            </template>
                            <template x-if="eligibleLinkDependencies.length === 0">
                                <p class="text-xs text-gray-400 italic py-4 text-center">Tidak ada tugas lain yang tersedia sebagai prasyarat.</p>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex justify-between items-center">
                    <button type="button" 
                            @click="selectedLinkIds = []" 
                            class="text-xs text-red-600 hover:text-red-800 font-medium hover:underline">
                        Hapus Semua Garis Hubung
                    </button>
                    <div class="flex gap-2.5">
                        <button type="button" @click="linkModalOpen = false" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button type="button" @click="saveLinks()" :disabled="saving" class="inline-flex items-center gap-1.5 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-xs shadow-xs transition-colors disabled:opacity-50">
                            <span x-show="!saving">Simpan Hubungan</span>
                            <span x-show="saving">Menyimpan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function ganttManager(canManage = true) {
    return {
        canManage: canManage,
        projectId: {{ $project->id }},
        project: {
            title: '{{ addslashes($project->title) }}',
            progress_percentage: {{ (int) $project->progress_percentage }}
        },
        tasks: [],
        users: [],
        ganttInstance: null,
        viewMode: 'Day',
        loading: false,
        saving: false,
        modalOpen: false,
        isEdit: false,
        currentEditId: null,
        editingTaskHasChildren: false,
        interactiveTimers: {},
        formData: {
            title: '',
            description: '',
            parent_id: '',
            assignee_id: '',
            start_date: '',
            due_date: '',
            progress: 0,
            sort_order: 1,
            dependency_ids: []
        },

        // Leaf Tasks and Parent Tasks Computed Properties
        get leafTasks() {
            return this.tasks.filter(t => !t.is_parent);
        },

        get parentTasks() {
            return this.tasks.filter(t => t.is_parent);
        },

        get completedTasksCount() {
            return this.leafTasks.filter(t => t.progress >= 100).length;
        },

        get inProgressTasksCount() {
            return this.leafTasks.filter(t => t.progress > 0 && t.progress < 100).length;
        },

        get todoTasksCount() {
            return this.leafTasks.filter(t => !t.progress || t.progress === 0).length;
        },

        // WBS Hierarchy & Collapse State
        collapsedTaskIds: [],

        // Drag and Drop & Move State
        draggedTask: null,
        dragOverTarget: null,
        dragOverPosition: null, // 'before', 'after', 'inside'
        isMovingTask: false,
        moveModalOpen: false,
        taskToMove: null,
        moveTargetParentId: '',
        moveTargetOrder: 1,

        // Link Lines & Dependencies State
        linkModalOpen: false,
        taskToLink: null,
        selectedLinkIds: [],

        openLinkModal(task) {
            this.taskToLink = task;
            this.selectedLinkIds = (task.dependencies || []).map(id => parseInt(id.replace('task_', '')));
            this.linkModalOpen = true;
        },

        get eligibleLinkDependencies() {
            if (!this.taskToLink) return [];
            const sourceId = this.taskToLink.db_id;
            return this.tasks.filter(t => t.db_id !== sourceId);
        },

        async saveLinks() {
            if (!this.taskToLink) return;
            this.saving = true;
            try {
                const res = await fetch(`/projects/gantt/tasks/${this.taskToLink.db_id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        dependency_ids: this.selectedLinkIds
                    })
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Gagal menyimpan garis ketergantungan.');

                this.linkModalOpen = false;
                this.notifyToast('Garis Terhubung', 'Garis ketergantungan tugas berhasil diperbarui.');
                await this.initData();
            } catch (err) {
                console.error(err);
                Swal.fire('Error', err.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        toggleCollapse(taskId) {
            if (this.collapsedTaskIds.includes(taskId)) {
                this.collapsedTaskIds = this.collapsedTaskIds.filter(id => id !== taskId);
            } else {
                this.collapsedTaskIds.push(taskId);
            }
        },

        isCollapsed(taskId) {
            return this.collapsedTaskIds.includes(taskId);
        },

        isTaskVisible(task) {
            if (!task.parent_db_id) return true;
            let currParentId = task.parent_db_id;
            while (currParentId) {
                if (this.collapsedTaskIds.includes(currParentId)) {
                    return false;
                }
                const pTask = this.tasks.find(t => t.db_id === currParentId);
                currParentId = pTask ? pTask.parent_db_id : null;
            }
            return true;
        },

        expandAll() {
            this.collapsedTaskIds = [];
        },

        collapseAll() {
            this.collapsedTaskIds = this.tasks.filter(t => t.is_parent).map(t => t.db_id);
        },

        getSiblings(task) {
            return this.tasks.filter(t => t.parent_db_id === task.parent_db_id);
        },

        getSiblingCount(task) {
            return this.getSiblings(task).length;
        },

        getTaskIndex(task) {
            const siblings = this.getSiblings(task);
            return siblings.findIndex(t => t.db_id === task.db_id);
        },

        getFormSiblingCount() {
            if (!this.isEdit || !this.currentEditId) return 1;
            const current = this.tasks.find(t => t.db_id === this.currentEditId);
            return current ? this.getSiblingCount(current) : 1;
        },

        getTargetSiblingCount(targetParentId) {
            const pid = targetParentId ? parseInt(targetParentId) : null;
            const currentSiblings = this.tasks.filter(t => t.parent_db_id === pid && (!this.taskToMove || t.db_id !== this.taskToMove.db_id));
            return currentSiblings.length + 1;
        },

        notifyToast(title, text = '', icon = 'success') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                }
            });
            Toast.fire({
                icon: icon,
                title: title,
                text: text
            });
        },

        async reorderTask(task, newOrder) {
            newOrder = parseInt(newOrder);
            const currentIndex = this.getTaskIndex(task) + 1;
            if (newOrder === currentIndex) return;

            this.loading = true;
            try {
                const res = await fetch(`/projects/gantt/tasks/${task.db_id}/reorder`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ order: newOrder })
                });

                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Gagal mengubah urutan tugas.');
                }

                this.notifyToast('Urutan Diperbarui', `Tugas "${task.title}" kini di urutan #${newOrder}.`);

                await this.initData();
            } catch (err) {
                console.error(err);
                Swal.fire('Error', err.message, 'error');
                await this.initData();
            } finally {
                this.loading = false;
            }
        },

        canMoveUp(task) {
            const idx = this.getTaskIndex(task);
            return idx > 0;
        },

        canMoveDown(task) {
            const idx = this.getTaskIndex(task);
            return idx >= 0 && idx < this.getSiblingCount(task) - 1;
        },

        async moveUp(task) {
            const currIdx = this.getTaskIndex(task);
            if (currIdx > 0) {
                await this.reorderTask(task, currIdx); // currIdx in 0-based is 1 position earlier in 1-based index
            }
        },

        async moveDown(task) {
            const currIdx = this.getTaskIndex(task);
            const count = this.getSiblingCount(task);
            if (currIdx < count - 1) {
                await this.reorderTask(task, currIdx + 2); // 1-based index + 1
            }
        },

        openMoveModal(task) {
            this.taskToMove = task;
            this.moveTargetParentId = task.parent_db_id ? task.parent_db_id : '';
            this.moveTargetOrder = this.getTaskIndex(task) + 1;
            this.moveModalOpen = true;
        },

        get eligibleMoveParents() {
            if (!this.taskToMove) return [];
            const sourceId = this.taskToMove.db_id;
            return this.tasks.filter(t => t.db_id !== sourceId && !this.isDescendantOf(t.db_id, sourceId));
        },

        async confirmMove() {
            if (!this.taskToMove) return;
            const targetParentId = this.moveTargetParentId ? parseInt(this.moveTargetParentId) : null;
            const targetOrder = this.moveTargetOrder ? parseInt(this.moveTargetOrder) : null;
            this.moveModalOpen = false;

            if (targetParentId !== this.taskToMove.parent_db_id) {
                await this.executeMove(this.taskToMove.db_id, targetParentId, 'inside');
            }
            if (targetOrder) {
                await this.reorderTask(this.taskToMove, targetOrder);
            } else {
                await this.initData();
            }
        },

        startDragging(task, event) {
            this.draggedTask = task;
            event.dataTransfer.effectAllowed = 'move';
            try {
                event.dataTransfer.setData('text/plain', task.db_id);
            } catch (e) {}
            setTimeout(() => {
                this.dragOverTarget = null;
                this.dragOverPosition = null;
            }, 0);
        },

        handleDragOver(task, event) {
            if (!this.draggedTask || this.draggedTask.db_id === task.db_id) return;
            if (this.isDescendantOf(task.db_id, this.draggedTask.db_id)) return;

            event.preventDefault();
            this.dragOverTarget = task.db_id;

            const rowEl = event.currentTarget;
            const rect = rowEl.getBoundingClientRect();
            const y = event.clientY - rect.top;

            // When hovering over a parent task, middle area (between 35% and 65%) nests as subtask.
            // Top 35% is 'before' and bottom 35% is 'after' (opens space between tasks).
            // For leaf tasks, top 50% is 'before' and bottom 50% is 'after' (always insert between tasks).
            if (task.is_parent) {
                if (y < rect.height * 0.35) {
                    this.dragOverPosition = 'before';
                } else if (y > rect.height * 0.65) {
                    this.dragOverPosition = 'after';
                } else {
                    this.dragOverPosition = 'inside';
                }
            } else {
                if (y < rect.height * 0.5) {
                    this.dragOverPosition = 'before';
                } else {
                    this.dragOverPosition = 'after';
                }
            }
        },

        handleDragLeave(task, event) {
            // Only clear drag over state if leaving the task tbody group completely
            if (!event.currentTarget.contains(event.relatedTarget)) {
                if (this.dragOverTarget === task.db_id) {
                    this.dragOverTarget = null;
                    this.dragOverPosition = null;
                }
            }
        },

        async dropOnTask(targetTask) {
            if (!this.draggedTask) return;
            const targetId = targetTask ? targetTask.db_id : null;
            const position = this.dragOverPosition || 'before';
            await this.executeMove(this.draggedTask.db_id, targetId, position);
        },

        async dropOnRoot() {
            if (!this.draggedTask) return;
            await this.executeMove(this.draggedTask.db_id, null, 'inside');
        },

        isDescendantOf(candidateId, ancestorId) {
            let curr = this.tasks.find(t => t.db_id === candidateId);
            while (curr && curr.parent_db_id) {
                if (curr.parent_db_id === ancestorId) return true;
                curr = this.tasks.find(t => t.db_id === curr.parent_db_id);
            }
            return false;
        },

        async executeMove(sourceId, targetId, position) {
            if (sourceId === targetId) {
                this.resetDrag();
                return;
            }

            this.isMovingTask = true;
            try {
                const res = await fetch(`/projects/gantt/tasks/${sourceId}/move`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        target_id: targetId,
                        position: position
                    })
                });

                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Gagal memindahkan tugas.');
                }

                // Make sure parent is expanded so user sees the newly moved task!
                if (targetId && position === 'inside') {
                    this.collapsedTaskIds = this.collapsedTaskIds.filter(id => id !== targetId);
                }

                await this.initData();
                this.notifyToast('Posisi Diperbarui', data.message || 'Tugas berhasil dipindahkan.');
            } catch (err) {
                console.error(err);
                Swal.fire('Perhatian', err.message, 'warning');
            } finally {
                this.isMovingTask = false;
                this.resetDrag();
            }
        },

        resetDrag() {
            this.draggedTask = null;
            this.dragOverTarget = null;
            this.dragOverPosition = null;
        },

        get potentialParents() {
            if (!this.isEdit) {
                return this.tasks;
            }
            return this.tasks.filter(t => t.db_id !== this.currentEditId && !this.isDescendantOf(t.db_id, this.currentEditId));
        },

        get potentialDependencies() {
            if (!this.isEdit) {
                return this.tasks;
            }
            return this.tasks.filter(t => t.db_id !== this.currentEditId);
        },

        async initData() {
            this.loading = true;
            try {
                const res = await fetch(`{{ route('projects.gantt.data', $project) }}`);
                const data = await res.json();
                this.project = data.project;
                this.tasks = data.tasks;
                this.users = data.users;
                this.renderGantt();
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Gagal memuat data Gantt chart', 'error');
            } finally {
                this.loading = false;
            }
        },

        renderGantt() {
            const container = document.getElementById('gantt-target');
            container.innerHTML = '';

            if (this.tasks.length === 0) return;

            // Prepare tasks formatted for Frappe Gantt
            const frappeTasks = this.tasks.map(t => ({
                id: t.id,
                name: t.name,
                start: t.start,
                end: t.end,
                progress: t.progress,
                dependencies: t.dependencies || [],
                custom_class: t.custom_class || 'bar-task',
                _task_data: t
            }));

            const self = this;

            // Global helpers for Gantt banner/popup buttons
            window.ganttEditTask = (dbId) => {
                const task = self.tasks.find(t => t.db_id === dbId);
                if (task) {
                    if (self.ganttInstance && self.ganttInstance.popup) {
                        self.ganttInstance.popup.hide();
                    }
                    self.openEditModal(task);
                }
            };

            window.ganttClosePopup = () => {
                if (self.ganttInstance && self.ganttInstance.popup) {
                    self.ganttInstance.popup.hide();
                }
            };

            this.ganttInstance = new Gantt("#gantt-target", frappeTasks, {
                readonly: !self.canManage,
                header_height: 48,
                column_width: self.viewMode === 'Day' ? 32 : (self.viewMode === 'Week' ? 50 : 70),
                step: 24,
                view_modes: ['Day', 'Week', 'Month'],
                bar_height: 26,
                bar_corner_radius: 5,
                arrow_curve: 5,
                padding: 16,
                view_mode: self.viewMode,
                date_format: 'YYYY-MM-DD',

                custom_popup_html: function(task) {
                    const d = task._task_data;
                    const assigneeName = d.assignee ? d.assignee.name : 'Belum Ditentukan';
                    const isParent = d.is_parent;
                    const progress = Math.round(d.progress || 0);

                    // Status & Badge (Light Theme)
                    let statusBadge = '';
                    if (isParent) {
                        statusBadge = '<span class="badge bg-amber-50 text-amber-800 border border-amber-200">Induk (' + (d.children_count || 0) + ' Sub)</span>';
                    } else if (progress >= 100) {
                        statusBadge = '<span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">Selesai (100%)</span>';
                    } else if (progress > 0) {
                        statusBadge = '<span class="badge bg-blue-50 text-blue-700 border border-blue-200">Sedang Dikerjakan</span>';
                    } else {
                        statusBadge = '<span class="badge bg-slate-100 text-slate-600 border border-slate-200">Belum Mulai</span>';
                    }

                    // Description text (Compact)
                    const descHtml = d.description && d.description.trim() !== ''
                        ? `<div class="gantt-popup-desc">
                            <div class="desc-label">
                                <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                                </svg>
                                Deskripsi
                            </div>
                            <div class="desc-content">${self.escapeHtml(d.description)}</div>
                           </div>`
                        : `<div class="text-[10.5px] text-slate-400 italic py-0.5">Tidak ada deskripsi</div>`;

                    return `
                        <div class="gantt-popup">
                            <!-- Header with WBS & Close button -->
                            <div class="flex items-center justify-between gap-1.5 pb-1.5 border-b border-slate-100">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="badge bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        WBS ${d.wbs_code || '-'}
                                    </span>
                                    ${statusBadge}
                                </div>
                                <button type="button" 
                                        onclick="window.ganttClosePopup()" 
                                        class="text-slate-400 hover:text-slate-700 p-0.5 rounded hover:bg-slate-100 transition-colors cursor-pointer"
                                        title="Tutup banner">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Title -->
                            <div class="title">${self.escapeHtml(d.title)}</div>

                            <!-- Description Box -->
                            ${descHtml}

                            <!-- Concise Meta Rows -->
                            <div class="space-y-1.5 py-1.5 border-t border-slate-100 text-[11px]">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-slate-400 text-[10.5px] shrink-0">PIC:</span>
                                    <span class="font-medium text-slate-800 truncate">${self.escapeHtml(assigneeName)}</span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-slate-400 text-[10.5px] shrink-0">Jadwal:</span>
                                    <span class="font-medium text-slate-700">${d.start} &rarr; ${d.end} <span class="text-slate-400 font-normal">(${d.duration_days}h)</span></span>
                                </div>
                            </div>

                            <!-- Progress Track -->
                            <div class="pt-1 pb-1.5">
                                <div class="flex items-center justify-between text-[10.5px] mb-1">
                                    <span class="text-slate-400">Progres</span>
                                    <span class="font-bold text-slate-800">${progress}%</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden border border-slate-200/60">
                                    <div class="h-full rounded-full transition-all duration-300 ${progress >= 100 ? 'bg-emerald-500' : 'bg-indigo-600'}" 
                                         style="width: ${progress}%"></div>
                                </div>
                            </div>

                            ${self.canManage ? `
                            <!-- Footer Actions -->
                            <div class="flex items-center justify-end pt-1.5 border-t border-slate-100">
                                <button type="button" 
                                        onclick="window.ganttEditTask(${d.db_id})" 
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-[11px] border border-indigo-200 transition-colors cursor-pointer">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit Tugas
                                </button>
                            </div>` : ''}
                        </div>
                    `;
                },

                on_date_change: function(task, start, end) {
                    if (!self.canManage) return;
                    const startStr = self.formatYmd(start);
                    const endStr = self.formatYmd(end);
                    self.debounceInteractive(task._task_data.db_id, {
                        start_date: startStr,
                        due_date: endStr
                    });
                },

                on_progress_change: function(task, progress) {
                    if (!self.canManage) return;
                    if (task._task_data.is_parent) {
                        self.notifyToast('Progres Otomatis', 'Progres tugas utama dikalkulasi otomatis dari sub-tugasnya.', 'info');
                        task.progress = task._task_data.progress;
                        self.refreshFrappeGanttTasks();
                        return;
                    }
                    self.debounceInteractive(task._task_data.db_id, {
                        progress: progress
                    });
                },

                on_click: function(task) {
                    if (self.canManage) {
                        self.openEditModal(task._task_data);
                    }
                }
            });

            // Smart auto-repositioning for the banner/popup horizontally and vertically
            const originalShowPopup = this.ganttInstance.show_popup.bind(this.ganttInstance);
            this.ganttInstance.show_popup = function(options) {
                originalShowPopup(options);
                if (self.ganttInstance && self.ganttInstance.popup_wrapper) {
                    const popupEl = self.ganttInstance.popup_wrapper;
                    const targetEl = options.target_element;
                    if (targetEl && popupEl) {
                        const targetRect = targetEl.getBBox ? targetEl.getBBox() : targetEl.getBoundingClientRect();
                        const popupWidth = popupEl.offsetWidth || 320;
                        const popupHeight = popupEl.offsetHeight || 220;
                        const svgWidth = self.ganttInstance.$svg ? (self.ganttInstance.$svg.clientWidth || self.ganttInstance.$svg.getBoundingClientRect().width) : 2000;
                        
                        // Horizontal auto-positioning: keep within horizontal boundary
                        const currentLeft = parseFloat(popupEl.style.left) || 0;
                        if (currentLeft + popupWidth > svgWidth - 20) {
                            const newLeft = Math.max(10, targetRect.x - popupWidth - 12);
                            popupEl.style.left = newLeft + 'px';
                        }

                        // Vertical auto-positioning: flip above the bar if popup would overflow bottom edge
                        const containerEl = self.ganttInstance.$container || popupEl.parentElement;
                        const containerHeight = containerEl ? containerEl.scrollHeight : (self.ganttInstance.$svg ? parseFloat(self.ganttInstance.$svg.getAttribute('height')) : 1000);
                        const currentTop = parseFloat(popupEl.style.top) || targetRect.y;

                        if (currentTop + popupHeight > containerHeight - 10) {
                            const aboveTop = targetRect.y - popupHeight - 10;
                            if (aboveTop >= 10) {
                                popupEl.style.top = aboveTop + 'px';
                            } else {
                                const clampedTop = Math.max(10, containerHeight - popupHeight - 10);
                                popupEl.style.top = clampedTop + 'px';
                            }
                        }

                        // Smoothly scroll into view if the banner touches the viewport bottom
                        setTimeout(() => {
                            if (popupEl && typeof popupEl.scrollIntoView === 'function') {
                                const rect = popupEl.getBoundingClientRect();
                                if (rect.bottom > window.innerHeight - 20) {
                                    popupEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                }
                            }
                        }, 50);
                    }
                }
            };
        },

        refreshFrappeGanttTasks() {
            if (!this.ganttInstance || this.tasks.length === 0) return;
            const frappeTasks = this.tasks.map(t => ({
                id: t.id,
                name: t.name,
                start: t.start,
                end: t.end,
                progress: t.progress,
                dependencies: t.dependencies || [],
                custom_class: t.custom_class || 'bar-task',
                _task_data: t
            }));
            this.ganttInstance.refresh(frappeTasks);
        },

        setViewMode(mode) {
            this.viewMode = mode;
            if (this.ganttInstance) {
                this.ganttInstance.change_view_mode(mode);
            }
        },

        formatYmd(dateObj) {
            const d = new Date(dateObj);
            const month = '' + (d.getMonth() + 1);
            const day = '' + d.getDate();
            const year = d.getFullYear();
            return [year, month.padStart(2, '0'), day.padStart(2, '0')].join('-');
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const parts = dateStr.split('-');
            if (parts.length === 3) {
                return `${parts[2]}/${parts[1]}/${parts[0]}`;
            }
            return dateStr;
        },

        escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, m => map[m]);
        },

        openCreateModal(parentId = null) {
            this.isEdit = false;
            this.currentEditId = null;
            this.editingTaskHasChildren = false;

            // Make sure the parent is expanded so the newly created child will be visible right away!
            if (parentId) {
                this.collapsedTaskIds = this.collapsedTaskIds.filter(id => id !== parentId);
            }

            const today = new Date();
            const startStr = this.formatYmd(today);
            const dueStr = this.formatYmd(new Date(today.getTime() + (7 * 24 * 60 * 60 * 1000)));

            this.formData = {
                title: '',
                description: '',
                parent_id: parentId ? parentId : '',
                assignee_id: '',
                start_date: startStr,
                due_date: dueStr,
                progress: 0,
                dependency_ids: []
            };

            this.modalOpen = true;
        },

        openEditModal(task) {
            this.isEdit = true;
            this.currentEditId = task.db_id;
            this.editingTaskHasChildren = task.is_parent;

            this.formData = {
                title: task.title,
                description: task.description || '',
                parent_id: task.parent_db_id || '',
                assignee_id: task.assignee_id || '',
                start_date: task.start,
                due_date: task.end,
                progress: task.progress,
                sort_order: this.getTaskIndex(task) + 1,
                dependency_ids: (task.dependencies || []).map(id => parseInt(id.replace('task_', '')))
            };

            this.modalOpen = true;
        },

        async saveTask() {
            this.saving = true;
            const url = this.isEdit 
                ? `/projects/gantt/tasks/${this.currentEditId}`
                : `{{ route('projects.gantt.tasks.store', $project) }}`;
            const method = this.isEdit ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.formData)
                });

                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Gagal menyimpan data tugas');
                }

                // If parent_id was set, make sure it is uncollapsed so the user sees the newly created child
                if (this.formData.parent_id) {
                    this.collapsedTaskIds = this.collapsedTaskIds.filter(id => id !== parseInt(this.formData.parent_id));
                }

                this.modalOpen = false;
                this.notifyToast('Berhasil!', data.message);

                await this.initData();
            } catch (err) {
                console.error(err);
                Swal.fire('Perhatian', err.message, 'warning');
            } finally {
                this.saving = false;
            }
        },

        debounceInteractive(taskId, payload) {
            // 1. Optimistic in-place update for WBS table and local tasks array
            const localTask = this.tasks.find(t => t.db_id === taskId);
            if (localTask) {
                if (payload.start_date) localTask.start = payload.start_date;
                if (payload.due_date) localTask.end = payload.due_date;
                if (payload.start_date && payload.due_date) {
                    const s = new Date(payload.start_date);
                    const e = new Date(payload.due_date);
                    localTask.duration_days = Math.max(1, Math.round((e - s) / (1000 * 60 * 60 * 24)) + 1);
                }
                if (payload.progress !== undefined) {
                    localTask.progress = payload.progress;
                    if (localTask.progress >= 100) {
                        localTask.status = 'completed';
                        localTask.custom_class = 'bar-completed';
                    } else if (localTask.progress > 0) {
                        localTask.status = 'in_progress';
                        localTask.custom_class = 'bar-inprogress';
                    } else {
                        localTask.status = 'todo';
                        localTask.custom_class = 'bar-task';
                    }
                }
            }

            // 2. Debounce per task: cancel pending timer for this task if user drags again
            if (this.interactiveTimers[taskId]) {
                clearTimeout(this.interactiveTimers[taskId]);
            }

            // 3. Wait 350ms after user releases mouse before sending background sync
            this.interactiveTimers[taskId] = setTimeout(() => {
                this.sendInteractiveUpdate(taskId, payload);
                delete this.interactiveTimers[taskId];
            }, 350);
        },

        updateInteractive(taskId, payload) {
            this.debounceInteractive(taskId, payload);
        },

        async sendInteractiveUpdate(taskId, payload) {
            try {
                const res = await fetch(`/projects/gantt/tasks/${taskId}/interactive`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Gagal memperbarui timeline');
                }

                // Update project progress KPI card without full page refresh
                if (data.project && data.project.progress_percentage !== undefined) {
                    this.project.progress_percentage = data.project.progress_percentage;
                }

                // Sync exact task attributes confirmed by server
                if (data.task) {
                    const target = this.tasks.find(t => t.db_id === data.task.db_id);
                    if (target) {
                        Object.assign(target, data.task);
                    }
                }

                // Sync ancestor tasks that were updated by rollup
                let ancestorDatesChanged = false;
                if (data.ancestors && data.ancestors.length > 0) {
                    data.ancestors.forEach(anc => {
                        const parentTask = this.tasks.find(t => t.db_id === anc.db_id);
                        if (parentTask) {
                            if (parentTask.start !== anc.start || parentTask.end !== anc.end) {
                                ancestorDatesChanged = true;
                            }
                            Object.assign(parentTask, anc);
                        }
                    });
                }

                // Refresh Gantt bars only if an ancestor's bar dates expanded/shrank
                if (ancestorDatesChanged) {
                    this.refreshFrappeGanttTasks();
                }
            } catch (err) {
                console.error('Interactive sync error:', err);
                this.notifyToast('Gagal Sinkronisasi', err.message, 'warning');
                // Silently reload all data if sync fails
                await this.initData();
            }
        },

        async deleteTask(taskId) {
            const confirm = await Swal.fire({
                title: 'Hapus Tugas?',
                text: 'Jika ini tugas utama, seluruh sub-tugasnya juga akan dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            });

            if (!confirm.isConfirmed) return;

            try {
                const res = await fetch(`/projects/gantt/tasks/${taskId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (res.ok) {
                    this.notifyToast('Terhapus', data.message);
                    await this.initData();
                } else {
                    throw new Error(data.message || 'Gagal menghapus');
                }
            } catch (err) {
                Swal.fire('Error', err.message, 'error');
            }
        }
    };
}
</script>
@endsection
