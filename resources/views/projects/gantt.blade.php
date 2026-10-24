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
        overflow-y: hidden !important;
        border-radius: 0.75rem;
        background: #ffffff;
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
        fill: #eff6ff;
        opacity: 0.7;
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

    /* Custom Tooltip Popup */
    .gantt-popup {
        background: #1e293b;
        color: #f8fafc;
        border-radius: 0.5rem;
        padding: 10px 14px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        line-height: 1.4;
        max-width: 280px;
        z-index: 1000;
    }
    .gantt-popup .title {
        font-weight: 700;
        font-size: 13px;
        color: #ffffff;
        margin-bottom: 4px;
    }
    .gantt-popup .subtitle {
        color: #94a3b8;
        font-size: 11px;
        margin-bottom: 6px;
    }
    .gantt-popup .badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }
</style>

<div class="space-y-6 pb-16" x-data="ganttManager()" x-init="initData()">
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
            <button @click="openCreateModal(null)" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-xs shadow-xs transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Tugas Utama
            </button>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-200 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Tugas (WBS)</p>
                <p class="text-2xl font-bold text-gray-900 mt-1" x-text="tasks.length">0</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-200 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Progres Proyek</p>
                <p class="text-2xl font-bold text-indigo-600 mt-1" x-text="project.progress_percentage + '%'">0%</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                %
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-200 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Selesai (Completed)</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1" x-text="tasks.filter(t => t.progress >= 100).length">0</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-200 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Sedang Berjalan</p>
                <p class="text-2xl font-bold text-amber-600 mt-1" x-text="tasks.filter(t => t.progress > 0 && t.progress < 100).length">0</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    <!-- Gantt Chart Timeline View Card -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-6 flex flex-col space-y-4">
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
            </div>
        </div>

        <!-- SVG Container for Frappe Gantt -->
        <div class="relative min-h-[300px]">
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
                <tbody class="divide-y divide-gray-100">
                    <template x-for="task in tasks" :key="task.id">
                        <tr x-show="isTaskVisible(task)"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            draggable="true"
                            @dragstart="startDragging(task, $event)"
                            @dragover="handleDragOver(task, $event)"
                            @dragleave="handleDragLeave(task, $event)"
                            @drop.prevent="dropOnTask(task)"
                            @dragend="resetDrag()"
                            :class="{
                                'bg-slate-50/70 font-semibold': task.is_parent,
                                'hover:bg-gray-50/60': !task.is_parent,
                                'opacity-40': draggedTask?.db_id === task.db_id,
                                'border-t-2 border-indigo-600 bg-indigo-50/50': dragOverTarget === task.db_id && dragOverPosition === 'before',
                                'border-b-2 border-indigo-600 bg-indigo-50/50': dragOverTarget === task.db_id && dragOverPosition === 'after',
                                'bg-indigo-100/70 ring-2 ring-indigo-500 ring-inset': dragOverTarget === task.db_id && dragOverPosition === 'inside'
                            }"
                            class="transition-colors">
                            
                            <!-- Drag Handle -->
                            <td class="py-3 px-2 text-center">
                                <div class="cursor-grab active:cursor-grabbing text-gray-300 hover:text-indigo-600 transition-colors inline-flex items-center justify-center p-1 rounded"
                                     title="Tahan & geser untuk mengubah urutan atau memasukkan ke tugas lain">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M7 4a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0zM7 16a2 2 0 11-4 0 2 2 0 014 0zM17 4a2 2 0 11-4 0 2 2 0 014 0zM17 10a2 2 0 11-4 0 2 2 0 014 0zM17 16a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                            </td>

                            <!-- WBS Code & Quick Order Selector -->
                            <td class="py-3 px-3">
                                <div class="inline-flex items-center gap-1.5 bg-gray-50 border border-gray-200/90 rounded-lg p-1 shadow-2xs">
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[11px] font-mono font-bold shrink-0"
                                          :class="task.is_parent ? 'bg-slate-200 text-slate-800' : 'bg-white text-gray-700 shadow-2xs'"
                                          x-text="task.wbs_code"></span>
                                    <select @change="reorderTask(task, $event.target.value)" 
                                            class="cursor-pointer text-[10px] font-bold py-0.5 px-1 rounded border border-gray-200 bg-white hover:border-indigo-500 focus:border-indigo-500 text-gray-700 hover:text-indigo-600 transition-colors shadow-2xs"
                                            :title="'Ubah urutan tugas (posisi saat ini: #' + (getTaskIndex(task) + 1) + ' dari ' + getSiblingCount(task) + ' tugas)'">
                                        <template x-for="n in getSiblingCount(task)" :key="n">
                                            <option :value="n" :selected="n === (getTaskIndex(task) + 1)" x-text="'#' + n"></option>
                                        </template>
                                    </select>
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

                                    <span :class="task.is_parent ? 'text-slate-900 font-bold' : 'text-gray-800'" x-text="task.title"></span>

                                    <template x-if="task.is_parent">
                                        <span class="ml-2 px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200" 
                                              x-text="task.children_count + ' sub'"></span>
                                    </template>
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
                            </td>
                        </tr>
                    </template>
                </tbody>
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
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                
                <form @submit.prevent="saveTask()">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                            <h3 class="text-lg font-bold text-gray-900" x-text="isEdit ? 'Edit Tugas WBS' : (formData.parent_id ? 'Tambah Sub-tugas' : 'Tambah Tugas Utama')"></h3>
                            <button type="button" @click="modalOpen = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="space-y-4 mt-4 text-xs">
                            <!-- Parent Task Selector -->
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Tugas Induk (Parent Task / WBS Level 1)</label>
                                <select x-model="formData.parent_id" class="w-full rounded-lg border-gray-300 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
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
                                    <label class="block font-semibold text-gray-700 mb-1">Nomor Urutan (Posisi di dalam Induk)</label>
                                    <select x-model="formData.sort_order" class="w-full rounded-lg border-gray-300 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                                        <template x-for="n in getFormSiblingCount()" :key="n">
                                            <option :value="n" x-text="'Urutan #' + n" :selected="n === formData.sort_order"></option>
                                        </template>
                                    </select>
                                    <p class="text-[11px] text-gray-500 mt-1">Mengubah urutan akan otomatis menggeser tugas lain di tingkat yang sama.</p>
                                </div>
                            </template>

                            <!-- Title -->
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Judul Tugas <span class="text-red-500">*</span></label>
                                <input type="text" x-model="formData.title" required placeholder="Contoh: Modul 1: Dashboard"
                                       class="w-full rounded-lg border-gray-300 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Assignee -->
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Penanggung Jawab (Assignee)</label>
                                <select x-model="formData.assignee_id" class="w-full rounded-lg border-gray-300 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Belum Ditentukan --</option>
                                    <template x-for="user in users" :key="user.id">
                                        <option :value="user.id" x-text="user.name"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Dates -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-1">Tanggal Mulai <span class="text-red-500">*</span></label>
                                    <input type="date" x-model="formData.start_date" required
                                           class="w-full rounded-lg border-gray-300 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-700 mb-1">Tenggat Waktu <span class="text-red-500">*</span></label>
                                    <input type="date" x-model="formData.due_date" required
                                           class="w-full rounded-lg border-gray-300 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <!-- Progress (Auto-calculated if has children) -->
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="font-semibold text-gray-700">Progres Pengerjaan (%)</label>
                                    <span class="font-mono font-bold text-indigo-600" x-text="formData.progress + '%'"></span>
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

                            <!-- Dependency -->
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Dependensi (Tugas Sebelumnya / Predecessor)</label>
                                <select x-model="formData.dependency_ids" multiple class="w-full rounded-lg border-gray-300 text-xs shadow-xs focus:border-indigo-500 focus:ring-indigo-500 h-24">
                                    <template x-for="t in potentialDependencies" :key="t.db_id">
                                        <option :value="t.db_id" x-text="(t.wbs_code ? t.wbs_code + ' ' : '') + t.title"></option>
                                    </template>
                                </select>
                                <p class="text-[10px] text-gray-400 mt-1">Tahan Ctrl/Command untuk memilih lebih dari satu tugas.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-2.5">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit" :disabled="saving" class="inline-flex items-center gap-1.5 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-xs shadow-xs transition-colors disabled:opacity-50">
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
</div>

<script>
function ganttManager() {
    return {
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
        formData: {
            title: '',
            parent_id: '',
            assignee_id: '',
            start_date: '',
            due_date: '',
            progress: 0,
            sort_order: 1,
            dependency_ids: []
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

            if (y < rect.height * 0.25) {
                this.dragOverPosition = 'before';
            } else if (y > rect.height * 0.75) {
                this.dragOverPosition = 'after';
            } else {
                this.dragOverPosition = 'inside';
            }
        },

        handleDragLeave(task, event) {
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
            const position = this.dragOverPosition || 'inside';
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
            this.ganttInstance = new Gantt("#gantt-target", frappeTasks, {
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
                    const assigneeName = d.assignee ? d.assignee.name : 'Belum di-assign';
                    const parentTag = d.is_parent ? '<span class="badge bg-slate-700 text-white ml-2">Auto-Rollup</span>' : '';
                    return `
                        <div class="gantt-popup">
                            <div class="title">${d.name} ${parentTag}</div>
                            <div class="subtitle">Assignee: ${assigneeName}</div>
                            <div style="font-size: 11px; margin-bottom: 4px;">📅 ${d.start} &rarr; ${d.end} (${d.duration_days} Hari)</div>
                            <div style="font-size: 11px; font-weight: bold; color: #818cf8;">Progres: ${d.progress}%</div>
                        </div>
                    `;
                },

                on_date_change: function(task, start, end) {
                    const startStr = self.formatYmd(start);
                    const endStr = self.formatYmd(end);
                    self.updateInteractive(task._task_data.db_id, {
                        start_date: startStr,
                        due_date: endStr
                    });
                },

                on_progress_change: function(task, progress) {
                    if (task._task_data.is_parent) {
                        self.notifyToast('Progres Otomatis', 'Progres tugas utama dikalkulasi otomatis dari sub-tugasnya.', 'info');
                        self.initData();
                        return;
                    }
                    self.updateInteractive(task._task_data.db_id, {
                        progress: progress
                    });
                },

                on_click: function(task) {
                    self.openEditModal(task._task_data);
                }
            });
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

        async updateInteractive(taskId, payload) {
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
                if (res.ok) {
                    await this.initData();
                }
            } catch (err) {
                console.error(err);
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
