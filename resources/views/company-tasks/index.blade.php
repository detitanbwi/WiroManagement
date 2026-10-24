@extends('layouts.app')

@section('title', 'Aktivitas & Task Internal - Wirodayan Digital')

@section('content')
<div class="h-full flex flex-col bg-gray-50 overflow-y-auto" x-data="companyTasksApp()">
    <!-- Toast Notification -->
    <div x-show="toast.show" 
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         class="fixed bottom-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border text-sm font-medium"
         :class="toast.type === 'error' ? 'bg-red-50 text-red-800 border-red-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'">
        <svg x-show="toast.type === 'success'" class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <svg x-show="toast.type === 'error'" class="w-5 h-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span x-text="toast.message"></span>
    </div>

    <!-- Header Section -->
    <div class="px-6 py-5 bg-white border-b border-gray-200 shrink-0 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center justify-center p-2 rounded-lg bg-primary/10 text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                        </svg>
                    </span>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">Aktivitas & Task Internal</h1>
                        <p class="text-xs md:text-sm text-gray-500 mt-0.5">Manajemen jadwal pertemuan klien, draf proposal, laporan akhir bulan, dan operasional manajemen.</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <!-- View Mode Switcher: 3 Tabs (Kanban, List, Kalender) -->
                <div class="inline-flex rounded-lg border border-gray-200 bg-gray-100 p-0.5">
                    <button @click="viewMode = 'kanban'" 
                            :class="viewMode === 'kanban' ? 'bg-white text-gray-900 shadow-xs font-semibold' : 'text-gray-500 hover:text-gray-700'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                        </svg>
                        <span>Kanban</span>
                    </button>
                    <button @click="viewMode = 'list'" 
                            :class="viewMode === 'list' ? 'bg-white text-gray-900 shadow-xs font-semibold' : 'text-gray-500 hover:text-gray-700'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                        </svg>
                        <span>Daftar / Agenda</span>
                    </button>
                    <button @click="viewMode = 'calendar'" 
                            :class="viewMode === 'calendar' ? 'bg-white text-gray-900 shadow-xs font-semibold' : 'text-gray-500 hover:text-gray-700'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>Kalender & Tenggat</span>
                    </button>
                </div>

                @can('company_tasks.manage')
                <button @click="openCreateModal()" 
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-blue-800 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Task Internal Baru</span>
                </button>
                @endcan
            </div>
        </div>

        <!-- Metric Badges -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5">
            <div class="bg-gray-50/80 border border-gray-200/80 rounded-xl p-3 flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Total Agenda</p>
                    <p class="text-xl font-bold text-gray-900 mt-0.5" x-text="tasks.length"></p>
                </div>
                <div class="w-9 h-9 rounded-lg bg-blue-100/60 text-primary flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
            </div>

            <div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-3 flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-semibold text-indigo-700 uppercase tracking-wider">Pertemuan Klien</p>
                    <p class="text-xl font-bold text-indigo-900 mt-0.5" x-text="getTasksByType('meeting').length"></p>
                </div>
                <div class="w-9 h-9 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>

            <div class="bg-amber-50/50 border border-amber-100 rounded-xl p-3 flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-semibold text-amber-800 uppercase tracking-wider">Proses & Review</p>
                    <p class="text-xl font-bold text-amber-900 mt-0.5" x-text="tasks.filter(t => ['in_progress', 'waiting_client'].includes(t.status)).length"></p>
                </div>
                <div class="w-9 h-9 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>

            <div class="bg-emerald-50/50 border border-emerald-100 rounded-xl p-3 flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wider">Selesai</p>
                    <p class="text-xl font-bold text-emerald-900 mt-0.5" x-text="getTasksByColumn('done').length"></p>
                </div>
                <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="px-6 py-3.5 bg-white border-b border-gray-200/80 shrink-0">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <!-- Type Filters -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 scrollbar-none">
                <button @click="filterType = 'all'" 
                        :class="filterType === 'all' ? 'bg-primary text-white font-semibold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors shrink-0">
                    Semua Tipe (<span x-text="tasks.length"></span>)
                </button>
                <button @click="filterType = 'meeting'" 
                        :class="filterType === 'meeting' ? 'bg-indigo-600 text-white font-semibold' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors shrink-0 flex items-center gap-1.5">
                    <span>🤝 Meeting Klien</span>
                    <span class="text-[11px] opacity-80" x-text="getTasksByType('meeting').length"></span>
                </button>
                <button @click="filterType = 'proposal'" 
                        :class="filterType === 'proposal' ? 'bg-amber-600 text-white font-semibold' : 'bg-amber-50 text-amber-800 hover:bg-amber-100'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors shrink-0 flex items-center gap-1.5">
                    <span>📑 Proposal</span>
                    <span class="text-[11px] opacity-80" x-text="getTasksByType('proposal').length"></span>
                </button>
                <button @click="filterType = 'report'" 
                        :class="filterType === 'report' ? 'bg-emerald-600 text-white font-semibold' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors shrink-0 flex items-center gap-1.5">
                    <span>📊 Laporan Bulanan</span>
                    <span class="text-[11px] opacity-80" x-text="getTasksByType('report').length"></span>
                </button>
                <button @click="filterType = 'operational'" 
                        :class="filterType === 'operational' ? 'bg-purple-600 text-white font-semibold' : 'bg-purple-50 text-purple-700 hover:bg-purple-100'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors shrink-0 flex items-center gap-1.5">
                    <span>⚙️ Operasional</span>
                    <span class="text-[11px] opacity-80" x-text="getTasksByType('operational').length"></span>
                </button>
            </div>

            <!-- Search & Dropdown Filters -->
            <div class="flex items-center gap-2">
                <!-- Search Box -->
                <div class="relative w-full sm:w-64">
                    <input type="text" 
                           x-model="searchQuery" 
                           placeholder="Cari tugas, agenda, klien..." 
                           class="w-full pl-9 pr-3 py-1.5 text-xs bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Priority Filter -->
                <select x-model="filterPriority" class="text-xs bg-gray-50 border border-gray-300 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-primary">
                    <option value="all">Semua Prioritas</option>
                    <option value="urgent">🔴 Urgent</option>
                    <option value="high">🟠 Tinggi</option>
                    <option value="medium">🟡 Sedang</option>
                    <option value="low">⚪ Rendah</option>
                </select>

                <!-- Assignee Filter -->
                <select x-model="filterAssignee" class="text-xs bg-gray-50 border border-gray-300 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-primary">
                    <option value="all">Semua Petugas</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>

                <!-- Reset Button -->
                <button x-show="hasActiveFilters" 
                        @click="resetFilters()" 
                        class="text-xs text-red-600 hover:text-red-800 px-2 py-1 font-medium hover:underline">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- MAIN VIEW AREA -->
    <div class="flex-1 p-6">
        <!-- MODE 1: KANBAN BOARD -->
        <div x-show="viewMode === 'kanban'" class="h-full">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 items-start">
                <template x-for="column in columns" :key="column.id">
                    <div class="flex flex-col bg-gray-100/70 border border-gray-200/90 rounded-2xl p-3 min-h-[550px] shadow-xs"
                         @dragover.prevent="onDragOverColumn(column.id, $event)"
                         @dragleave="onDragLeaveColumn(column.id, $event)"
                         @drop="onDropTask(column.id, $event)">
                        
                        <!-- Column Header -->
                        <div class="flex items-center justify-between px-3 py-2.5 mb-3 bg-white rounded-xl border border-gray-200 shadow-2xs">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" :class="column.dotClass"></span>
                                <h2 class="text-xs font-bold text-gray-800 uppercase tracking-wide" x-text="column.title"></h2>
                            </div>
                            <span class="text-xs font-extrabold px-2 py-0.5 rounded-full bg-gray-100 text-gray-700" 
                                  x-text="getFilteredTasksByColumn(column.id).length"></span>
                        </div>

                        <!-- Drop Placeholder Area -->
                        <div x-show="dragOverColumnId === column.id && draggedTaskId" 
                             class="mb-3 p-3 border-2 border-dashed border-primary/50 bg-blue-50/70 rounded-xl text-center text-xs font-semibold text-primary transition-all">
                            Lepaskan untuk memindahkan ke <span x-text="column.title"></span>
                        </div>

                        <!-- Task Cards Container -->
                        <div class="space-y-3 flex-1 overflow-y-auto pr-0.5">
                            <template x-for="task in getFilteredTasksByColumn(column.id)" :key="task.id">
                                <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-2xs hover:shadow-md hover:border-gray-300 transition-all cursor-grab active:cursor-grabbing group relative"
                                     draggable="true"
                                     @dragstart="onDragStartTask(task, $event)"
                                     @dragend="onDragEndTask()"
                                     @click="openEditModal(task)">
                                    
                                    <!-- Card Header: Code & Type & Priority -->
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-[10px] font-bold font-mono px-2 py-0.5 rounded bg-gray-100 text-gray-600 border border-gray-200" x-text="task.code"></span>
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded border" :class="getTypeBadge(task.type).badge">
                                                <span x-text="getTypeBadge(task.type).label"></span>
                                            </span>
                                        </div>

                                        <!-- Priority Badge -->
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-1.5 py-0.5 rounded uppercase" :class="getPriorityBadge(task.priority).badge">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="getPriorityBadge(task.priority).dot"></span>
                                            <span x-text="getPriorityBadge(task.priority).label"></span>
                                        </span>
                                    </div>

                                    <!-- Task Title -->
                                    <h3 class="text-sm font-semibold text-gray-900 group-hover:text-primary transition-colors leading-snug mb-2" x-text="task.title"></h3>

                                    <!-- Description preview if any -->
                                    <template x-if="task.description">
                                        <p class="text-xs text-gray-500 line-clamp-2 mb-3 leading-relaxed" x-text="task.description"></p>
                                    </template>

                                    <!-- Meeting Special Info (if meeting) -->
                                    <template x-if="task.type === 'meeting' && (task.meeting_time || task.meeting_location)">
                                        <div class="mb-3 p-2.5 rounded-lg bg-indigo-50/60 border border-indigo-100/80 text-xs text-indigo-900 space-y-1">
                                            <template x-if="task.meeting_time">
                                                <div class="flex items-center gap-1.5 font-medium">
                                                    <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    <span x-text="formatDateTime(task.meeting_time)"></span>
                                                </div>
                                            </template>
                                            <template x-if="task.meeting_location">
                                                <div class="flex items-center gap-1.5 text-[11px] text-indigo-700 truncate" :title="task.meeting_location">
                                                    <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    <span class="truncate" x-text="task.meeting_location"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Client Association Badge -->
                                    <template x-if="task.client">
                                        <div class="flex items-center gap-1.5 text-xs text-gray-600 mb-3 bg-gray-50 px-2 py-1 rounded border border-gray-100">
                                            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            <span class="truncate font-medium text-gray-700" x-text="task.client.name + (task.client.company_name ? ' (' + task.client.company_name + ')' : '')"></span>
                                        </div>
                                    </template>

                                    <!-- Card Footer: Due Date & Multiple Assignees -->
                                    <div class="flex items-center justify-between pt-2.5 border-t border-gray-100 text-xs">
                                        <!-- Due Date -->
                                        <div>
                                            <template x-if="task.due_date">
                                                <span class="inline-flex items-center gap-1 font-medium text-[11px]" 
                                                      :class="isPastDue(task.due_date, task.status) ? 'text-red-600 font-bold' : 'text-gray-500'">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    <span x-text="formatDate(task.due_date)"></span>
                                                </span>
                                            </template>
                                            <template x-if="!task.due_date">
                                                <span class="text-gray-400 text-[11px]">Tanpa deadline</span>
                                            </template>
                                        </div>

                                        <!-- Multi-Assignee Avatars Stack -->
                                        <div class="flex items-center gap-1.5" :title="getAssigneesTooltip(task)">
                                            <template x-if="getTaskAssignees(task).length > 0">
                                                <div class="flex items-center -space-x-1.5 overflow-hidden">
                                                    <template x-for="(assignee, idx) in getTaskAssignees(task).slice(0, 3)" :key="assignee.id">
                                                        <div class="inline-block w-6 h-6 rounded-full ring-2 ring-white flex items-center justify-center text-[9px] font-bold text-white uppercase shadow-2xs shrink-0"
                                                             :class="getAvatarBgColor(idx)"
                                                             :title="assignee.name">
                                                            <span x-text="assignee.name.substring(0, 2)"></span>
                                                        </div>
                                                    </template>
                                                    <template x-if="getTaskAssignees(task).length > 3">
                                                        <div class="inline-block w-6 h-6 rounded-full ring-2 ring-white bg-gray-200 text-gray-700 font-bold text-[9px] flex items-center justify-center shrink-0">
                                                            <span x-text="'+' + (getTaskAssignees(task).length - 3)"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="getTaskAssignees(task).length === 1">
                                                <span class="text-[11px] font-medium text-gray-600 max-w-[85px] truncate" x-text="getTaskAssignees(task)[0].name"></span>
                                            </template>
                                            <template x-if="getTaskAssignees(task).length > 1">
                                                <span class="text-[11px] font-semibold text-gray-600" x-text="getTaskAssignees(task).length + ' Petugas'"></span>
                                            </template>
                                            <template x-if="getTaskAssignees(task).length === 0">
                                                <div class="flex items-center gap-1 text-gray-400">
                                                    <div class="w-5 h-5 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-[9px] font-bold">?</div>
                                                    <span class="text-[11px]">Unassigned</span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Quick Move Strip on hover for Mobile or quick click -->
                                    <div class="mt-2.5 pt-2 border-t border-gray-100 flex items-center justify-between text-[11px] opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
                                        <span class="text-gray-400 font-medium">Pindah ke:</span>
                                        <div class="flex items-center gap-1">
                                            <template x-for="c in columns" :key="c.id">
                                                <button x-show="c.id !== task.status" 
                                                        @click.stop="quickMoveTask(task, c.id)" 
                                                        class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors"
                                                        :title="'Pindah ke ' + c.title"
                                                        x-text="c.shortTitle">
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Empty Column State -->
                            <div x-show="getFilteredTasksByColumn(column.id).length === 0" 
                                 class="p-6 text-center border-2 border-dashed border-gray-200 rounded-xl bg-white/40">
                                <p class="text-xs text-gray-400 font-medium">Tidak ada tugas pada tahap ini</p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- MODE 2: LIST / AGENDA VIEW -->
        <div x-show="viewMode === 'list'" class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-[11px] font-bold uppercase tracking-wider text-gray-500">
                            <th class="py-3 px-4">Kode & Judul Task</th>
                            <th class="py-3 px-4">Tipe Agenda</th>
                            <th class="py-3 px-4">Klien Terkait</th>
                            <th class="py-3 px-4">Waktu / Jadwal</th>
                            <th class="py-3 px-4">Petugas / Tim</th>
                            <th class="py-3 px-4">Prioritas</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs text-gray-700">
                        <template x-for="task in filteredTasks" :key="task.id">
                            <tr class="hover:bg-blue-50/40 transition-colors cursor-pointer group" @click="openEditModal(task)">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 border" x-text="task.code"></span>
                                        <span class="font-semibold text-gray-900 group-hover:text-primary transition-colors" x-text="task.title"></span>
                                    </div>
                                    <template x-if="task.description">
                                        <p class="text-[11px] text-gray-400 mt-1 line-clamp-1" x-text="task.description"></p>
                                    </template>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded border" :class="getTypeBadge(task.type).badge" x-text="getTypeBadge(task.type).label"></span>
                                </td>
                                <td class="py-3.5 px-4 font-medium text-gray-700">
                                    <span x-text="task.client ? task.client.name : '-'"></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <template x-if="task.type === 'meeting' && task.meeting_time">
                                        <div>
                                            <span class="font-semibold text-indigo-800" x-text="formatDateTime(task.meeting_time)"></span>
                                            <template x-if="task.meeting_location">
                                                <p class="text-[10px] text-gray-500 truncate max-w-[150px]" x-text="task.meeting_location"></p>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="task.type !== 'meeting' || !task.meeting_time">
                                        <span :class="isPastDue(task.due_date, task.status) ? 'text-red-600 font-bold' : 'text-gray-600'" 
                                              x-text="task.due_date ? formatDate(task.due_date) : '-'"></span>
                                    </template>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2" :title="getAssigneesTooltip(task)">
                                        <template x-if="getTaskAssignees(task).length > 0">
                                            <div class="flex items-center -space-x-1.5 overflow-hidden shrink-0">
                                                <template x-for="(assignee, idx) in getTaskAssignees(task).slice(0, 3)" :key="assignee.id">
                                                    <div class="inline-block w-6 h-6 rounded-full ring-2 ring-white flex items-center justify-center text-[9px] font-bold text-white uppercase shrink-0"
                                                         :class="getAvatarBgColor(idx)"
                                                         :title="assignee.name">
                                                        <span x-text="assignee.name.substring(0, 2)"></span>
                                                    </div>
                                                </template>
                                                <template x-if="getTaskAssignees(task).length > 3">
                                                    <div class="inline-block w-6 h-6 rounded-full ring-2 ring-white bg-gray-200 text-gray-700 font-bold text-[9px] flex items-center justify-center shrink-0">
                                                        <span x-text="'+' + (getTaskAssignees(task).length - 3)"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        <div class="text-xs text-gray-700 truncate max-w-[150px]">
                                            <template x-if="getTaskAssignees(task).length === 0">
                                                <span class="text-gray-400 italic">Belum ditugaskan</span>
                                            </template>
                                            <template x-if="getTaskAssignees(task).length === 1">
                                                <span x-text="getTaskAssignees(task)[0].name"></span>
                                            </template>
                                            <template x-if="getTaskAssignees(task).length > 1">
                                                <span class="font-medium" x-text="getTaskAssignees(task).map(a => a.name).join(', ')"></span>
                                            </template>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded uppercase" :class="getPriorityBadge(task.priority).badge">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getPriorityBadge(task.priority).dot"></span>
                                        <span x-text="getPriorityBadge(task.priority).label"></span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <select @click.stop 
                                            @change="quickMoveTask(task, $event.target.value)" 
                                            class="text-xs border border-gray-200 rounded-lg px-2 py-1 font-semibold focus:outline-none"
                                            :class="getColumnBadgeClass(task.status)">
                                        <template x-for="c in columns" :key="c.id">
                                            <option :value="c.id" :selected="task.status === c.id" x-text="c.title"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1" @click.stop>
                                        <button @click="openEditModal(task)" class="p-1.5 text-gray-400 hover:text-primary rounded-md transition-colors" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        @can('company_tasks.manage')
                                        <button @click="deleteTask(task)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-md transition-colors" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template x-if="filteredTasks.length === 0">
                            <tr>
                                <td colspan="8" class="py-12 text-center text-gray-400">
                                    Tidak ada data task internal yang sesuai dengan filter.
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODE 3: CALENDAR & DEADLINES VIEW -->
        <div x-show="viewMode === 'calendar'" class="space-y-6">
            <div class="grid grid-cols-1 xl:grid-cols-4 gap-6 items-start">
                <!-- Left 3 Columns: Interactive Monthly Calendar -->
                <div class="xl:col-span-3 bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden flex flex-col">
                    <!-- Calendar Header & Navigation Controls -->
                    <div class="p-4 sm:p-5 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gray-50/50">
                        <!-- Month Navigation -->
                        <div class="flex items-center gap-3">
                            <div class="flex items-center bg-white border border-gray-200 rounded-xl shadow-2xs p-1">
                                <button @click="prevMonth()" class="p-1.5 hover:bg-gray-100 rounded-lg text-gray-600 transition-colors" title="Bulan Sebelumnya">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button @click="nextMonth()" class="p-1.5 hover:bg-gray-100 rounded-lg text-gray-600 transition-colors" title="Bulan Berikutnya">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900 tracking-tight" x-text="calendarTitle"></h2>
                            <button @click="goToToday()" class="px-2.5 py-1 text-xs font-semibold bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg shadow-2xs transition-colors">
                                Hari Ini
                            </button>
                        </div>

                        <!-- Calendar Filter: All vs Meetings vs Deadlines -->
                        <div class="flex items-center gap-1.5 overflow-x-auto text-xs">
                            <button @click="calItemFilter = 'all'" 
                                    :class="calItemFilter === 'all' ? 'bg-primary text-white font-semibold' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                                    class="px-2.5 py-1.5 rounded-lg transition-colors">
                                Semua Agenda
                            </button>
                            <button @click="calItemFilter = 'meeting'" 
                                    :class="calItemFilter === 'meeting' ? 'bg-indigo-600 text-white font-semibold' : 'bg-white text-indigo-700 hover:bg-indigo-50 border border-indigo-200'"
                                    class="px-2.5 py-1.5 rounded-lg transition-colors flex items-center gap-1">
                                <span>🤝 Pertemuan Klien</span>
                            </button>
                            <button @click="calItemFilter = 'deadline'" 
                                    :class="calItemFilter === 'deadline' ? 'bg-amber-600 text-white font-semibold' : 'bg-white text-amber-800 hover:bg-amber-50 border border-amber-200'"
                                    class="px-2.5 py-1.5 rounded-lg transition-colors flex items-center gap-1">
                                <span>⏰ Tenggat Tugas</span>
                            </button>
                        </div>
                    </div>

                    <!-- Days of Week Header -->
                    <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50 text-center py-2.5 text-xs font-bold uppercase tracking-wider text-gray-500">
                        <div>Sen</div>
                        <div>Sel</div>
                        <div>Rab</div>
                        <div>Kam</div>
                        <div>Jum</div>
                        <div class="text-blue-600">Sab</div>
                        <div class="text-red-500">Min</div>
                    </div>

                    <!-- Monthly Grid Cells -->
                    <div class="grid grid-cols-7 gap-px bg-gray-200 flex-1">
                        <template x-for="(cell, cIdx) in calendarDays" :key="cell.dateString + '-' + cIdx">
                            <div class="min-h-[115px] sm:min-h-[125px] p-1.5 sm:p-2 flex flex-col justify-between transition-colors relative group"
                                 :class="[
                                     cell.isCurrentMonth ? 'bg-white' : 'bg-gray-50/70 opacity-60',
                                     cell.isToday ? 'ring-2 ring-primary ring-inset z-10' : ''
                                 ]">
                                
                                <!-- Cell Header: Date Number & Add Button -->
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs font-semibold"
                                              :class="{
                                                  'w-6 h-6 rounded-full bg-primary text-white flex items-center justify-center font-bold shadow-2xs': cell.isToday,
                                                  'text-gray-800': cell.isCurrentMonth && !cell.isToday,
                                                  'text-gray-400': !cell.isCurrentMonth
                                              }"
                                              x-text="cell.day"></span>
                                        <template x-if="cell.isToday">
                                            <span class="text-[9px] font-bold text-primary uppercase hidden sm:inline">Hari Ini</span>
                                        </template>
                                    </div>

                                    <!-- Quick Add on this Date -->
                                    @can('company_tasks.manage')
                                    <button @click.stop="openCreateModalForDate(cell.dateString)" 
                                            class="opacity-0 group-hover:opacity-100 p-1 rounded-md text-gray-400 hover:text-primary hover:bg-blue-50 transition-all"
                                            title="Jadwalkan agenda di tanggal ini">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    </button>
                                    @endcan
                                </div>

                                <!-- Cell Items Container -->
                                <div class="space-y-1 overflow-y-auto max-h-[85px] scrollbar-none flex-1">
                                    <template x-for="(item, iIdx) in cell.items" :key="item.task.id + '-' + item.kind">
                                        <div @click.stop="openEditModal(item.task)"
                                             class="px-1.5 py-1 rounded-md text-[10px] leading-tight font-medium cursor-pointer transition-all truncate border flex items-center gap-1 shadow-2xs hover:scale-[1.02]"
                                             :class="{
                                                 'bg-indigo-50 text-indigo-900 border-indigo-200 hover:bg-indigo-100': item.kind === 'meeting' && item.task.status !== 'done',
                                                 'bg-amber-50 text-amber-900 border-amber-200 hover:bg-amber-100': item.kind === 'deadline' && item.task.status !== 'done',
                                                 'bg-emerald-50 text-emerald-800 border-emerald-200 opacity-60 line-through': item.task.status === 'done'
                                             }"
                                             :title="item.label + ' (' + item.task.code + ') - Klik untuk melihat detail'">
                                            
                                            <!-- Icon -->
                                            <template x-if="item.kind === 'meeting'">
                                                <span class="shrink-0 text-indigo-600 font-bold">🤝</span>
                                            </template>
                                            <template x-if="item.kind === 'deadline'">
                                                <span class="shrink-0 text-amber-600 font-bold">⏰</span>
                                            </template>

                                            <!-- Time / Label -->
                                            <span class="truncate" x-text="item.label"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Calendar Bottom Legend -->
                    <div class="p-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between flex-wrap gap-3 text-xs text-gray-500">
                        <div class="flex items-center gap-4 flex-wrap">
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-indigo-500"></span> <span>🤝 Pertemuan Klien</span></span>
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-amber-500"></span> <span>⏰ Tenggat Tugas (Deadline)</span></span>
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-emerald-500"></span> <span>✅ Selesai</span></span>
                        </div>
                        <span class="text-[11px] text-gray-400">💡 Klik pada tanggal atau kartu untuk melihat & mengedit tugas</span>
                    </div>
                </div>

                <!-- Right Column: Sidebar "Tenggat Waktu & Agenda Terdekat" -->
                <div class="xl:col-span-1 space-y-4">
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-4">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">Tenggat & Agenda Terdekat</h3>
                                <p class="text-[11px] text-gray-500 mt-0.5">Urutan batas waktu & jadwal mendatang</p>
                            </div>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-50 text-primary" 
                                  x-text="upcomingDeadlinesAndMeetings.length"></span>
                        </div>

                        <!-- Upcoming Items List -->
                        <div class="mt-3 space-y-2.5 max-h-[550px] overflow-y-auto pr-1">
                            <template x-for="item in upcomingDeadlinesAndMeetings" :key="'sidebar-' + item.task.id">
                                <div @click="openEditModal(item.task)"
                                     class="p-3 rounded-xl border transition-all cursor-pointer hover:shadow-xs group"
                                     :class="item.isOverdue ? 'bg-red-50/60 border-red-200 hover:border-red-300' : 'bg-gray-50/70 border-gray-200/80 hover:bg-white hover:border-gray-300'">
                                    
                                    <!-- Date & Badge Header -->
                                    <div class="flex items-center justify-between gap-2 mb-1.5">
                                        <div class="flex items-center gap-1.5">
                                            <template x-if="item.isOverdue">
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-red-100 text-red-700 uppercase animate-pulse">
                                                    Lewat Tenggat
                                                </span>
                                            </template>
                                            <span class="text-[10px] font-bold font-mono px-1.5 py-0.5 rounded bg-white text-gray-600 border border-gray-200" x-text="item.task.code"></span>
                                            <span class="inline-flex items-center text-[10px] font-bold px-1.5 py-0.5 rounded border" :class="getTypeBadge(item.task.type).badge" x-text="getTypeBadge(item.task.type).label"></span>
                                        </div>

                                        <!-- Date formatted -->
                                        <span class="text-[11px] font-bold"
                                              :class="item.isOverdue ? 'text-red-600' : (item.kind === 'meeting' ? 'text-indigo-700' : 'text-gray-700')"
                                              x-text="formatDate(item.date)"></span>
                                    </div>

                                    <!-- Task Title -->
                                    <h4 class="text-xs font-semibold text-gray-900 group-hover:text-primary transition-colors leading-snug mb-1" x-text="item.task.title"></h4>

                                    <!-- Meeting time / Client if any -->
                                    <template x-if="item.kind === 'meeting' && item.task.meeting_time">
                                        <div class="text-[11px] text-indigo-700 font-medium flex items-center gap-1 mb-1">
                                            <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span x-text="formatDateTime(item.task.meeting_time)"></span>
                                        </div>
                                    </template>

                                    <template x-if="item.task.client">
                                        <div class="text-[10px] text-gray-500 truncate mb-1.5 flex items-center gap-1">
                                            <span>🏢</span>
                                            <span class="truncate" x-text="item.task.client.name"></span>
                                        </div>
                                    </template>

                                    <!-- Footer: Assignees & Status -->
                                    <div class="flex items-center justify-between pt-2 border-t border-gray-200/50 text-[10px]">
                                        <!-- Assignees Avatars -->
                                        <div class="flex items-center -space-x-1.5 overflow-hidden">
                                            <template x-for="(assignee, idx) in getTaskAssignees(item.task).slice(0, 3)" :key="assignee.id">
                                                <div class="w-5 h-5 rounded-full ring-1 ring-white flex items-center justify-center text-[8px] font-bold text-white uppercase"
                                                     :class="getAvatarBgColor(idx)"
                                                     :title="assignee.name">
                                                    <span x-text="assignee.name.substring(0, 2)"></span>
                                                </div>
                                            </template>
                                            <template x-if="getTaskAssignees(item.task).length === 0">
                                                <span class="text-gray-400 italic">Unassigned</span>
                                            </template>
                                        </div>

                                        <!-- Status Badge -->
                                        <span class="px-2 py-0.5 rounded-full font-bold uppercase"
                                              :class="getColumnBadgeClass(item.task.status)"
                                              x-text="item.task.status.replace('_', ' ')"></span>
                                    </div>
                                </div>
                            </template>

                            <template x-if="upcomingDeadlinesAndMeetings.length === 0">
                                <div class="py-8 text-center text-gray-400 text-xs">
                                    Tidak ada jadwal agenda atau tenggat waktu tugas yang tercatat.
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CREATE / EDIT TASK -->
    <div x-show="modal.isOpen" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="modal.isOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" 
                 @click="closeModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Panel -->
            <div x-show="modal.isOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl w-full border border-gray-200">
                
                <form @submit.prevent="submitForm()">
                    <!-- Modal Header -->
                    <div class="bg-gray-50/80 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </span>
                            <h3 class="text-lg font-bold text-gray-900" x-text="modal.isEdit ? 'Edit Task Internal (' + modal.form.code + ')' : 'Buat Task Internal Baru'"></h3>
                        </div>
                        <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-200 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                        <!-- Title Field -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Judul Agenda / Task <span class="text-red-500">*</span></label>
                            <input type="text" 
                                   x-model="modal.form.title" 
                                   required 
                                   placeholder="Contoh: Pertemuan Diskusi MVP Klien PT ABC, Rekap Laporan Kas Bulan Ini..." 
                                   class="w-full px-3.5 py-2 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>

                        <!-- Type & Priority Row -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Kategori / Tipe <span class="text-red-500">*</span></label>
                                <select x-model="modal.form.type" required class="w-full px-3.5 py-2 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="meeting">🤝 Pertemuan Klien (Meeting)</option>
                                    <option value="proposal">📑 Proposal & Quotation</option>
                                    <option value="report">📊 Laporan Akhir Bulan</option>
                                    <option value="operational">⚙️ Operasional & IT</option>
                                    <option value="general">📌 Tugas Umum</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Prioritas <span class="text-red-500">*</span></label>
                                <select x-model="modal.form.priority" required class="w-full px-3.5 py-2 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="urgent">🔴 Urgent</option>
                                    <option value="high">🟠 Tinggi</option>
                                    <option value="medium">🟡 Sedang</option>
                                    <option value="low">⚪ Rendah</option>
                                </select>
                            </div>
                        </div>

                        <!-- Status Field -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Status Kolom <span class="text-red-500">*</span></label>
                            <select x-model="modal.form.status" required class="w-full px-3.5 py-2 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="todo">📌 To Do / Terjadwal</option>
                                <option value="in_progress">⏳ In Progress</option>
                                <option value="waiting_client">👥 Menunggu Klien / Review</option>
                                <option value="done">✅ Selesai</option>
                            </select>
                        </div>

                        <!-- Multi-Assignee Selection Section (Add -> Pop up -> Search Karyawan -> Add -> Save) -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Petugas / Penanggung Jawab
                                    </label>
                                    <p class="text-[11px] text-gray-500">Karyawan yang bertugas atau menghadiri agenda ini</p>
                                </div>
                                <button type="button" 
                                        @click="openAssigneeModal()" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition-all shadow-2xs">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                    <span>+ Tambah Petugas</span>
                                </button>
                            </div>

                            <!-- Selected Assignees Container -->
                            <div class="border border-gray-200 rounded-xl p-3 bg-gray-50/70 min-h-[76px] flex flex-col justify-center">
                                <!-- Empty State -->
                                <div x-show="modal.form.assignee_ids.length === 0" class="flex items-center justify-between py-1 px-1 text-gray-400">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-600">Belum ada petugas yang ditugaskan</p>
                                            <p class="text-[11px] text-gray-400">Klik tombol "+ Tambah Petugas" untuk mencari & menambahkan karyawan</p>
                                        </div>
                                    </div>
                                    <button type="button" @click="openAssigneeModal()" class="text-xs font-semibold text-primary hover:text-blue-700 bg-white border border-gray-200 px-2.5 py-1 rounded-lg shadow-2xs hover:bg-gray-50 transition-colors">
                                        Pilih
                                    </button>
                                </div>

                                <!-- Selected Assignees Cards/Chips -->
                                <div x-show="modal.form.assignee_ids.length > 0" class="flex flex-wrap gap-2">
                                    <template x-for="(user, uIdx) in getSelectedAssignees()" :key="user.id">
                                        <div class="inline-flex items-center gap-2 pl-2.5 pr-1.5 py-1.5 rounded-xl bg-white border border-gray-200/90 shadow-2xs group hover:border-gray-300 transition-all">
                                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0 shadow-2xs"
                                                 :class="getAvatarBgColor(uIdx)">
                                                <span x-text="user.name.substring(0, 2).toUpperCase()"></span>
                                            </div>
                                            <div class="text-left">
                                                <div class="flex items-center gap-1.5">
                                                    <p class="text-xs font-semibold text-gray-900 leading-tight" x-text="user.name"></p>
                                                    <template x-if="user.roles && user.roles.length > 0">
                                                        <span class="text-[9px] font-medium px-1.5 py-0.2 rounded bg-gray-100 text-gray-600 border border-gray-200"
                                                              x-text="user.roles[0].name"></span>
                                                    </template>
                                                </div>
                                                <p class="text-[10px] text-gray-400 leading-tight" x-text="user.email"></p>
                                            </div>
                                            <button type="button" 
                                                    @click="removeAssignee(user.id)"
                                                    class="p-1 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors ml-0.5"
                                                    title="Hapus petugas dari agenda ini">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Client Association (Optional) -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Terkait Klien Tertentu (Opsional)</label>
                            <select x-model="modal.form.client_id" class="w-full px-3.5 py-2 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">-- Tidak Terikat Klien / Internal Saja --</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} {{ $c->company_name ? ' - ' . $c->company_name : '' }}</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-gray-400 mt-1">Pilih klien jika tugas ini berupa jadwal pertemuan, proposal, atau penagihan khusus klien.</p>
                        </div>

                        <!-- Special Section: Meeting Details (Active if type === 'meeting') -->
                        <div x-show="modal.form.type === 'meeting'" 
                             x-transition
                             class="p-4 bg-indigo-50/70 border border-indigo-200 rounded-xl space-y-3">
                            <h4 class="text-xs font-bold text-indigo-900 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>Detail Jadwal Pertemuan Klien</span>
                            </h4>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-indigo-900 mb-1">Tanggal & Jam Meeting</label>
                                    <input type="datetime-local" 
                                           x-model="modal.form.meeting_time" 
                                           class="w-full px-3 py-1.5 text-xs bg-white border border-indigo-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-indigo-900 mb-1">Lokasi / Link Pertemuan</label>
                                    <input type="text" 
                                           x-model="modal.form.meeting_location" 
                                           placeholder="Google Meet, Zoom, atau Alamat Kantor" 
                                           class="w-full px-3 py-1.5 text-xs bg-white border border-indigo-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>

                        <!-- Due Date (Deadline) -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Batas Waktu (Deadline) Selesai</label>
                            <input type="date" 
                                   x-model="modal.form.due_date" 
                                   class="w-full sm:w-1/2 px-3.5 py-2 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>

                        <!-- Description & Agenda Notes -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Deskripsi / Catatan Agenda</label>
                            <textarea x-model="modal.form.description" 
                                      rows="3" 
                                      placeholder="Poin-poin pembahasan, catatan teknis, link draft dokumen, atau catatan penting lainnya..." 
                                      class="w-full px-3.5 py-2 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                        <div>
                            <template x-if="modal.isEdit">
                                @can('company_tasks.manage')
                                <button type="button" 
                                        @click="deleteTask(modal.form)" 
                                        class="text-xs font-semibold text-red-600 hover:text-red-800 hover:underline">
                                    Hapus Task Ini
                                </button>
                                @endcan
                            </template>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <button type="button" @click="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                                Batal
                            </button>
                            <button type="submit" 
                                    :disabled="modal.isSubmitting"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-primary hover:bg-blue-800 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors disabled:opacity-50">
                                <span x-show="!modal.isSubmitting" x-text="modal.isEdit ? 'Simpan Perubahan' : 'Buat Task'"></span>
                                <span x-show="modal.isSubmitting">Menyimpan...</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- POPUP MODAL: SEARCH & ADD KARYAWAN INTERNAL (ASSIGNEES) -->
    <div x-show="assigneeModal.isOpen" 
         class="fixed inset-0 z-[60] overflow-y-auto" 
         aria-labelledby="assignee-modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="assigneeModal.isOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" 
                 @click="closeAssigneeModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Panel -->
            <div x-show="assigneeModal.isOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-gray-200">
                
                <!-- Modal Header -->
                <div class="bg-gray-50/90 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </span>
                        <div>
                            <h3 id="assignee-modal-title" class="text-base font-bold text-gray-900 leading-tight">Pilih Karyawan / Petugas</h3>
                            <p class="text-xs text-gray-500">Cari dan tentukan karyawan yang bertanggung jawab</p>
                        </div>
                    </div>
                    <button type="button" @click="closeAssigneeModal()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Search Karyawan Bar -->
                <div class="p-4 border-b border-gray-100 bg-white">
                    <div class="relative">
                        <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text"
                               x-model="assigneeModal.searchQuery"
                               placeholder="Ketik untuk mencari nama, email, atau jabatan..."
                               class="w-full pl-10 pr-9 py-2.5 text-sm bg-gray-50 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white transition-all">
                        <button x-show="assigneeModal.searchQuery" 
                                @click="assigneeModal.searchQuery = ''" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-0.5 rounded-full hover:bg-gray-200 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="flex items-center justify-between mt-2 px-1 text-[11px] text-gray-500">
                        <span>Menampilkan <strong class="text-gray-800" x-text="filteredAssigneeUsers.length"></strong> karyawan</span>
                        <span class="font-semibold text-primary" x-text="assigneeModal.selectedIds.length + ' orang dipilih'"></span>
                    </div>
                </div>

                <!-- Employee List -->
                <div class="p-4 space-y-2 max-h-72 overflow-y-auto bg-gray-50/30">
                    <template x-for="(user, idx) in filteredAssigneeUsers" :key="user.id">
                        <div class="flex items-center justify-between p-2.5 rounded-xl border transition-all cursor-pointer group"
                             :class="isAssigneeModalSelected(user.id) ? 'bg-blue-50/80 border-blue-200 shadow-2xs' : 'bg-white border-gray-200/80 hover:bg-gray-50 hover:border-gray-300'"
                             @click="toggleAssigneeModalSelection(user.id)">
                            
                            <!-- Left Info: Avatar + Name + Email + Role -->
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0 shadow-2xs"
                                     :class="getAvatarBgColor(idx)">
                                    <span x-text="user.name.substring(0, 2).toUpperCase()"></span>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <p class="text-xs font-bold text-gray-900 group-hover:text-primary transition-colors truncate" x-text="user.name"></p>
                                        <template x-if="user.roles && user.roles.length > 0">
                                            <span class="text-[9px] font-semibold px-1.5 py-0.2 rounded bg-gray-100 text-gray-600 border border-gray-200"
                                                  x-text="user.roles.map(r => r.name).join(', ')"></span>
                                        </template>
                                    </div>
                                    <p class="text-[11px] text-gray-400 truncate" x-text="user.email"></p>
                                </div>
                            </div>

                            <!-- Right Action: Add / Terpilih button -->
                            <div class="shrink-0 ml-2">
                                <!-- Selected state -->
                                <template x-if="isAssigneeModalSelected(user.id)">
                                    <button type="button" 
                                            @click.stop="toggleAssigneeModalSelection(user.id)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-100/80 text-emerald-800 border border-emerald-300 hover:bg-red-50 hover:text-red-700 hover:border-red-300 transition-all shadow-2xs group/btn">
                                        <svg class="w-3.5 h-3.5 text-emerald-700 group-hover/btn:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <svg class="w-3.5 h-3.5 text-red-600 hidden group-hover/btn:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        <span class="group-hover/btn:hidden">Terpilih</span>
                                        <span class="hidden group-hover/btn:inline">Batal</span>
                                    </button>
                                </template>

                                <!-- Unselected state -->
                                <template x-if="!isAssigneeModalSelected(user.id)">
                                    <button type="button" 
                                            @click.stop="toggleAssigneeModalSelection(user.id)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold rounded-lg bg-white text-gray-700 border border-gray-200 hover:bg-primary hover:text-white hover:border-primary transition-all shadow-2xs">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                        <span>+ Tambah</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Empty Search Result -->
                    <template x-if="filteredAssigneeUsers.length === 0">
                        <div class="py-8 text-center text-gray-400">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <p class="text-xs font-medium text-gray-600">Tidak ada karyawan yang sesuai</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Coba kata kunci pencarian nama atau email lainnya</p>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer: Cancel & Save -->
                <div class="bg-gray-50 px-6 py-3.5 border-t border-gray-200 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-600" x-text="assigneeModal.selectedIds.length + ' karyawan dipilih'"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" 
                                @click="closeAssigneeModal()" 
                                class="px-4 py-2 bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-xs font-semibold shadow-2xs transition-colors">
                            Batal
                        </button>
                        <button type="button" 
                                @click="saveAssigneeModal()" 
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary hover:bg-blue-800 text-white rounded-lg text-xs font-bold shadow-sm transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span>Simpan Petugas</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function companyTasksApp() {
    return {
        viewMode: 'kanban', // 'kanban', 'list', or 'calendar'
        tasks: @json($tasks),
        internalUsers: @json($users),
        columns: [
            { id: 'todo', title: 'To Do / Terjadwal', shortTitle: 'To Do', dotClass: 'bg-gray-400' },
            { id: 'in_progress', title: 'Sedang Dikerjakan', shortTitle: 'In Progress', dotClass: 'bg-blue-500' },
            { id: 'waiting_client', title: 'Menunggu Klien / Review', shortTitle: 'Waiting', dotClass: 'bg-amber-500' },
            { id: 'done', title: 'Selesai', shortTitle: 'Selesai', dotClass: 'bg-emerald-500' }
        ],

        // Calendar States
        calYear: (new Date()).getFullYear(),
        calMonth: (new Date()).getMonth(), // 0 to 11
        calItemFilter: 'all', // 'all', 'meeting', 'deadline'
        monthNames: [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ],

        get calendarTitle() {
            return this.monthNames[this.calMonth] + ' ' + this.calYear;
        },

        nextMonth() {
            if (this.calMonth === 11) {
                this.calMonth = 0;
                this.calYear++;
            } else {
                this.calMonth++;
            }
        },

        prevMonth() {
            if (this.calMonth === 0) {
                this.calMonth = 11;
                this.calYear--;
            } else {
                this.calMonth--;
            }
        },

        goToToday() {
            const today = new Date();
            this.calYear = today.getFullYear();
            this.calMonth = today.getMonth();
        },

        formatDateYMD(d) {
            const pad = (n) => n < 10 ? '0' + n : n;
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        },

        get calendarDays() {
            const year = this.calYear;
            const month = this.calMonth;
            
            // First day of month (0=Sun, 1=Mon, ..., 6=Sat)
            // Adjust to Monday=0, Sunday=6
            const firstDay = new Date(year, month, 1);
            let startDayOfWeek = firstDay.getDay() - 1;
            if (startDayOfWeek < 0) startDayOfWeek = 6;
            
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();
            
            const todayStr = this.formatDateYMD(new Date());
            const days = [];
            
            // 1. Prev month trailing days
            for (let i = startDayOfWeek - 1; i >= 0; i--) {
                const d = daysInPrevMonth - i;
                const prevDate = new Date(year, month - 1, d);
                const dateStr = this.formatDateYMD(prevDate);
                days.push({
                    dateString: dateStr,
                    day: d,
                    isCurrentMonth: false,
                    isToday: dateStr === todayStr,
                    items: this.getItemsForDate(dateStr)
                });
            }
            
            // 2. Current month days
            for (let d = 1; d <= daysInMonth; d++) {
                const curDate = new Date(year, month, d);
                const dateStr = this.formatDateYMD(curDate);
                days.push({
                    dateString: dateStr,
                    day: d,
                    isCurrentMonth: true,
                    isToday: dateStr === todayStr,
                    items: this.getItemsForDate(dateStr)
                });
            }
            
            // 3. Next month trailing days to complete grid rows
            const remaining = (7 - (days.length % 7)) % 7;
            for (let d = 1; d <= remaining; d++) {
                const nextDate = new Date(year, month + 1, d);
                const dateStr = this.formatDateYMD(nextDate);
                days.push({
                    dateString: dateStr,
                    day: d,
                    isCurrentMonth: false,
                    isToday: dateStr === todayStr,
                    items: this.getItemsForDate(dateStr)
                });
            }
            
            return days;
        },

        getItemsForDate(dateString) {
            const items = [];
            const tasks = this.filteredTasks;
            
            for (const t of tasks) {
                // Meeting check
                if (t.type === 'meeting' && t.meeting_time) {
                    const mDate = t.meeting_time.substring(0, 10);
                    if (mDate === dateString && (this.calItemFilter === 'all' || this.calItemFilter === 'meeting')) {
                        let timeStr = '';
                        try {
                            const dt = new Date(t.meeting_time);
                            timeStr = (dt.getHours() < 10 ? '0' : '') + dt.getHours() + ':' + (dt.getMinutes() < 10 ? '0' : '') + dt.getMinutes();
                        } catch(e) {}
                        items.push({
                            kind: 'meeting',
                            label: (timeStr ? timeStr + ' ' : '') + t.title,
                            task: t,
                            time: timeStr
                        });
                    }
                }
                
                // Deadline check
                if (t.due_date) {
                    const dDate = t.due_date.substring(0, 10);
                    if (dDate === dateString && (this.calItemFilter === 'all' || this.calItemFilter === 'deadline')) {
                        // Prevent duplicate if meeting is already shown on same date
                        if (t.type !== 'meeting' || !t.meeting_time || t.meeting_time.substring(0, 10) !== dateString) {
                            items.push({
                                kind: 'deadline',
                                label: 'Tenggat: ' + t.title,
                                task: t,
                                time: null
                            });
                        }
                    }
                }
            }
            return items;
        },

        get upcomingDeadlinesAndMeetings() {
            const list = [];
            const todayStr = this.formatDateYMD(new Date());
            
            for (const t of this.filteredTasks) {
                let date = null;
                let kind = null;
                if (t.type === 'meeting' && t.meeting_time) {
                    date = t.meeting_time.substring(0, 10);
                    kind = 'meeting';
                } else if (t.due_date) {
                    date = t.due_date.substring(0, 10);
                    kind = 'deadline';
                }
                
                if (date) {
                    const isOverdue = date < todayStr && t.status !== 'done';
                    list.push({
                        task: t,
                        date: date,
                        kind: kind,
                        isOverdue: isOverdue
                    });
                }
            }
            
            // Sort chronologically (overdue first, then upcoming)
            list.sort((a, b) => {
                if (a.isOverdue && !b.isOverdue) return -1;
                if (!a.isOverdue && b.isOverdue) return 1;
                return a.date.localeCompare(b.date);
            });
            
            return list;
        },

        openCreateModalForDate(dateStr) {
            this.openCreateModal();
            this.modal.form.due_date = dateStr;
            this.modal.form.meeting_time = dateStr + 'T10:00';
        },

        // Filter States
        filterType: 'all',
        filterPriority: 'all',
        filterAssignee: 'all',
        searchQuery: '',

        // Drag & Drop States
        draggedTaskId: null,
        dragOverColumnId: null,

        // Toast State
        toast: {
            show: false,
            message: '',
            type: 'success'
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => {
                this.toast.show = false;
            }, 4000);
        },

        // Modal State
        modal: {
            isOpen: false,
            isEdit: false,
            isSubmitting: false,
            form: {
                id: null,
                code: '',
                title: '',
                description: '',
                type: 'meeting',
                status: 'todo',
                priority: 'medium',
                client_id: '',
                assignee_ids: [],
                due_date: '',
                meeting_time: '',
                meeting_location: ''
            }
        },

        // Assignee Picker Modal State (Add -> Pop up -> Search Karyawan -> Add -> Save)
        assigneeModal: {
            isOpen: false,
            searchQuery: '',
            selectedIds: [],
        },

        openAssigneeModal() {
            this.assigneeModal.selectedIds = (this.modal.form.assignee_ids || []).map(id => Number(id));
            this.assigneeModal.searchQuery = '';
            this.assigneeModal.isOpen = true;
        },

        closeAssigneeModal() {
            this.assigneeModal.isOpen = false;
        },

        isAssigneeModalSelected(userId) {
            return this.assigneeModal.selectedIds.includes(Number(userId));
        },

        toggleAssigneeModalSelection(userId) {
            const id = Number(userId);
            const idx = this.assigneeModal.selectedIds.indexOf(id);
            if (idx > -1) {
                this.assigneeModal.selectedIds.splice(idx, 1);
            } else {
                this.assigneeModal.selectedIds.push(id);
            }
        },

        saveAssigneeModal() {
            this.modal.form.assignee_ids = [...this.assigneeModal.selectedIds];
            this.assigneeModal.isOpen = false;
            this.showToast('Petugas berhasil disimpan (' + this.modal.form.assignee_ids.length + ' orang dipilih).');
        },

        getSelectedAssignees() {
            const ids = (this.modal.form.assignee_ids || []).map(id => Number(id));
            return this.internalUsers.filter(u => ids.includes(Number(u.id)));
        },

        removeAssignee(userId) {
            const id = Number(userId);
            this.modal.form.assignee_ids = this.modal.form.assignee_ids.filter(i => Number(i) !== id);
        },

        get filteredAssigneeUsers() {
            if (!this.assigneeModal.searchQuery.trim()) {
                return this.internalUsers;
            }
            const q = this.assigneeModal.searchQuery.toLowerCase().trim();
            return this.internalUsers.filter(u => {
                const inName = u.name ? u.name.toLowerCase().includes(q) : false;
                const inEmail = u.email ? u.email.toLowerCase().includes(q) : false;
                const inRole = u.roles && u.roles.some(r => r.name && r.name.toLowerCase().includes(q));
                return inName || inEmail || inRole;
            });
        },

        isAssigneeSelected(userId) {
            return this.modal.form.assignee_ids.some(id => Number(id) === Number(userId));
        },

        toggleAssignee(userId) {
            const id = Number(userId);
            const idx = this.modal.form.assignee_ids.findIndex(i => Number(i) === id);
            if (idx > -1) {
                this.modal.form.assignee_ids.splice(idx, 1);
            } else {
                this.modal.form.assignee_ids.push(id);
            }
        },

        getTaskAssignees(task) {
            if (task.assignees && task.assignees.length > 0) {
                return task.assignees;
            }
            if (task.assignee) {
                return [task.assignee];
            }
            return [];
        },

        getAssigneesTooltip(task) {
            const assignees = this.getTaskAssignees(task);
            if (assignees.length === 0) return 'Belum ditugaskan';
            return 'Petugas: ' + assignees.map(a => a.name).join(', ');
        },

        getAvatarBgColor(idx) {
            const colors = ['bg-primary', 'bg-indigo-600', 'bg-emerald-600', 'bg-amber-600', 'bg-purple-600', 'bg-rose-600'];
            return colors[idx % colors.length];
        },

        get hasActiveFilters() {
            return this.filterType !== 'all' || 
                   this.filterPriority !== 'all' || 
                   this.filterAssignee !== 'all' || 
                   this.searchQuery.trim() !== '';
        },

        resetFilters() {
            this.filterType = 'all';
            this.filterPriority = 'all';
            this.filterAssignee = 'all';
            this.searchQuery = '';
        },

        get filteredTasks() {
            return this.tasks.filter(t => {
                // Filter Type
                if (this.filterType !== 'all' && t.type !== this.filterType) {
                    return false;
                }
                // Filter Priority
                if (this.filterPriority !== 'all' && t.priority !== this.filterPriority) {
                    return false;
                }
                // Filter Assignee
                if (this.filterAssignee !== 'all') {
                    const targetId = Number(this.filterAssignee);
                    const assignees = this.getTaskAssignees(t);
                    const match = assignees.some(a => Number(a.id) === targetId) || Number(t.assignee_id) === targetId;
                    if (!match) return false;
                }
                // Filter Search
                if (this.searchQuery.trim()) {
                    const q = this.searchQuery.toLowerCase().trim();
                    const inTitle = t.title ? t.title.toLowerCase().includes(q) : false;
                    const inCode = t.code ? t.code.toLowerCase().includes(q) : false;
                    const inDesc = t.description ? t.description.toLowerCase().includes(q) : false;
                    const inClient = t.client ? (t.client.name.toLowerCase().includes(q) || (t.client.company_name && t.client.company_name.toLowerCase().includes(q))) : false;
                    if (!inTitle && !inCode && !inDesc && !inClient) {
                        return false;
                    }
                }
                return true;
            });
        },

        getTasksByColumn(columnId) {
            return this.tasks.filter(t => t.status === columnId);
        },

        getFilteredTasksByColumn(columnId) {
            return this.filteredTasks.filter(t => t.status === columnId);
        },

        getTasksByType(type) {
            return this.tasks.filter(t => t.type === type);
        },

        getTypeBadge(type) {
            switch(type) {
                case 'meeting':
                    return { label: '🤝 Meeting Klien', badge: 'bg-indigo-50 text-indigo-700 border-indigo-200' };
                case 'proposal':
                    return { label: '📑 Proposal', badge: 'bg-amber-50 text-amber-800 border-amber-200' };
                case 'report':
                    return { label: '📊 Laporan', badge: 'bg-emerald-50 text-emerald-800 border-emerald-200' };
                case 'operational':
                    return { label: '⚙️ Operasional', badge: 'bg-purple-50 text-purple-700 border-purple-200' };
                default:
                    return { label: '📌 Umum', badge: 'bg-gray-100 text-gray-700 border-gray-200' };
            }
        },

        getPriorityBadge(priority) {
            switch(priority) {
                case 'urgent':
                    return { label: 'Urgent', badge: 'bg-red-50 text-red-700 border-red-200', dot: 'bg-red-500 animate-pulse' };
                case 'high':
                    return { label: 'Tinggi', badge: 'bg-orange-50 text-orange-700 border-orange-200', dot: 'bg-orange-500' };
                case 'medium':
                    return { label: 'Sedang', badge: 'bg-amber-50 text-amber-700 border-amber-200', dot: 'bg-amber-500' };
                default:
                    return { label: 'Rendah', badge: 'bg-gray-100 text-gray-600 border-gray-200', dot: 'bg-gray-400' };
            }
        },

        getColumnBadgeClass(status) {
            switch(status) {
                case 'todo': return 'bg-gray-50 text-gray-700 border-gray-200';
                case 'in_progress': return 'bg-blue-50 text-blue-700 border-blue-200';
                case 'waiting_client': return 'bg-amber-50 text-amber-800 border-amber-200';
                case 'done': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
                default: return 'bg-gray-50 text-gray-700 border-gray-200';
            }
        },

        formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        formatDateTime(dateTimeString) {
            if (!dateTimeString) return '-';
            const date = new Date(dateTimeString);
            return date.toLocaleDateString('id-ID', { 
                weekday: 'short', 
                day: 'numeric', 
                month: 'short', 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        },

        isPastDue(dueDate, status) {
            if (!dueDate || status === 'done') return false;
            const due = new Date(dueDate);
            const today = new Date();
            today.setHours(0,0,0,0);
            return due < today;
        },

        // Drag & Drop Handlers
        onDragStartTask(task, event) {
            this.draggedTaskId = task.id;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', task.id);
        },

        onDragEndTask() {
            this.draggedTaskId = null;
            this.dragOverColumnId = null;
        },

        onDragOverColumn(columnId, event) {
            this.dragOverColumnId = columnId;
        },

        onDragLeaveColumn(columnId, event) {
            if (this.dragOverColumnId === columnId) {
                this.dragOverColumnId = null;
            }
        },

        async onDropTask(columnId, event) {
            const taskId = this.draggedTaskId;
            this.draggedTaskId = null;
            this.dragOverColumnId = null;

            if (!taskId) return;
            const task = this.tasks.find(t => t.id === taskId);
            if (!task || task.status === columnId) return;

            // Optimistic update
            const oldStatus = task.status;
            task.status = columnId;

            try {
                const response = await fetch(`/company-tasks/${taskId}/move`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ status: columnId })
                });

                if (response.ok) {
                    const data = await response.json();
                    this.showToast(data.message || 'Status tugas berhasil diperbarui.');
                } else {
                    task.status = oldStatus;
                    this.showToast('Gagal memindahkan tugas.', 'error');
                }
            } catch (error) {
                console.error(error);
                task.status = oldStatus;
                this.showToast('Terjadi kesalahan jaringan.', 'error');
            }
        },

        async quickMoveTask(task, newColumnId) {
            if (task.status === newColumnId) return;
            const oldStatus = task.status;
            task.status = newColumnId;

            try {
                const response = await fetch(`/company-tasks/${task.id}/move`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ status: newColumnId })
                });

                if (response.ok) {
                    this.showToast('Status tugas berhasil diperbarui.');
                } else {
                    task.status = oldStatus;
                    this.showToast('Gagal memindahkan status tugas.', 'error');
                }
            } catch (err) {
                task.status = oldStatus;
                this.showToast('Terjadi kesalahan jaringan.', 'error');
            }
        },

        // Modal Create / Edit
        openCreateModal() {
            this.modal.isEdit = false;
            this.modal.form = {
                id: null,
                code: '',
                title: '',
                description: '',
                type: 'meeting',
                status: 'todo',
                priority: 'medium',
                client_id: '',
                assignee_ids: [{{ auth()->id() ?? 'null' }}].filter(Boolean),
                due_date: '',
                meeting_time: '',
                meeting_location: ''
            };
            this.modal.isOpen = true;
        },

        openEditModal(task) {
            this.modal.isEdit = true;
            let ids = [];
            if (task.assignees && task.assignees.length > 0) {
                ids = task.assignees.map(a => Number(a.id));
            } else if (task.assignee_id) {
                ids = [Number(task.assignee_id)];
            }

            this.modal.form = {
                id: task.id,
                code: task.code,
                title: task.title,
                description: task.description || '',
                type: task.type,
                status: task.status,
                priority: task.priority,
                client_id: task.client_id || '',
                assignee_ids: ids,
                due_date: task.due_date ? task.due_date.substring(0, 10) : '',
                meeting_time: task.meeting_time ? this.formatDatetimeLocal(task.meeting_time) : '',
                meeting_location: task.meeting_location || ''
            };
            this.modal.isOpen = true;
        },

        formatDatetimeLocal(dt) {
            if (!dt) return '';
            const d = new Date(dt);
            // Pad to YYYY-MM-DDTHH:MM
            const pad = (n) => n < 10 ? '0' + n : n;
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        },

        closeModal() {
            this.modal.isOpen = false;
        },

        async submitForm() {
            if (this.modal.isSubmitting) return;
            this.modal.isSubmitting = true;

            const isEdit = this.modal.isEdit;
            const url = isEdit ? `/company-tasks/${this.modal.form.id}` : '/company-tasks';
            const method = isEdit ? 'PUT' : 'POST';

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.modal.form)
                });

                if (response.ok) {
                    const data = await response.json();
                    if (isEdit) {
                        const idx = this.tasks.findIndex(t => t.id === this.modal.form.id);
                        if (idx !== -1) {
                            this.tasks[idx] = data.task;
                        }
                    } else {
                        this.tasks.unshift(data.task);
                    }
                    this.showToast(data.message);
                    this.closeModal();
                } else {
                    const err = await response.json();
                    this.showToast(err.message || 'Gagal menyimpan task.', 'error');
                }
            } catch (error) {
                console.error(error);
                this.showToast('Terjadi kesalahan jaringan.', 'error');
            } finally {
                this.modal.isSubmitting = false;
            }
        },

        async deleteTask(task) {
            if (!confirm(`Apakah Anda yakin ingin menghapus task internal "${task.title}" (${task.code})?`)) {
                return;
            }

            try {
                const response = await fetch(`/company-tasks/${task.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                if (response.ok) {
                    this.tasks = this.tasks.filter(t => t.id !== task.id);
                    this.showToast('Task berhasil dihapus.');
                    if (this.modal.isOpen) {
                        this.closeModal();
                    }
                } else {
                    this.showToast('Gagal menghapus task.', 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Terjadi kesalahan saat menghapus.', 'error');
            }
        }
    };
}
</script>
@endsection
