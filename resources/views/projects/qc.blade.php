@extends('layouts.app')

@section('title', 'QA/QC Dashboard - ' . $project->title)

@section('content')
@php
    $currentUser = auth()->user();
    $isProjectStaffOnly = !$currentUser->isSuperAdmin() 
        && !$currentUser->hasAnyRole(['superadmin', 'admin']) 
        && (
            ($currentUser->hasProjectRole($project, 'staff') && !$currentUser->hasAnyProjectRole($project, ['pm', 'qc']))
            || ($currentUser->hasRole('staff') && !$currentUser->hasAnyRole(['pm', 'qc', 'admin', 'superadmin']))
        );
@endphp
<div class="h-full flex flex-col bg-gray-50" x-data="qcDashboard()">
    <!-- Global Toast Notifications -->
    <div x-show="successMessage" x-transition.opacity.duration.300ms class="fixed top-5 right-5 z-50 flex items-center gap-2 bg-emerald-600 text-white px-4 py-3 rounded-lg shadow-lg border border-emerald-500 font-medium text-sm" style="display: none;">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span x-text="successMessage"></span>
    </div>
    <div x-show="errorMessage" x-transition.opacity.duration.300ms class="fixed top-5 right-5 z-50 flex items-center gap-2 bg-red-600 text-white px-4 py-3 rounded-lg shadow-lg border border-red-500 font-medium text-sm" style="display: none;">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        <span x-text="errorMessage"></span>
    </div>

    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200 bg-white flex justify-between items-center shrink-0">
        <div>
            <h1 class="text-xl font-bold text-gray-800">QA/QC Workflow</h1>
            <p class="text-sm text-gray-500 mt-1">Project: <span class="font-semibold">{{ $project->title }}</span></p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('projects.qc.export-excel', $project->id) }}" 
               class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-emerald-600 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-sm font-medium shadow-sm transition-colors"
               title="Export QA/QC ke Excel (.xlsx) dengan 3 Sheet: Kanban, Test Case, dan Bug">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Export Excel</span>
            </a>

            <!-- Send Summary Email Button -->
            <button type="button"
                    @click="sendSummaryEmail()"
                    :disabled="isSendingEmail"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-blue-600 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-md text-sm font-medium shadow-sm transition-colors"
                    title="Kirim ringkasan metrik QA/QC ke seluruh anggota proyek melalui email">
                <template x-if="!isSendingEmail">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </template>
                <template x-if="isSendingEmail">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </template>
                <span x-text="isSendingEmail ? 'Mengirim...' : 'Kirim Ringkasan Email'"></span>
            </button>
            @if(auth()->user()->can('projects.manage'))
            <a href="{{ route('projects.show', $project->id) }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 shadow-sm transition-colors">
                Back to Project
            </a>
            @else
            <a href="{{ route('projects.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 shadow-sm transition-colors">
                Back to Projects
            </a>
            @endif
            @can('qc.manage_tasks')
            <button @click="openNewTaskModal()" class="px-4 py-2 bg-primary text-white rounded-md text-sm font-medium hover:bg-blue-800 shadow-sm transition-colors">
                New Task
            </button>
            @endcan
        </div>
    </div>

    <!-- Kanban Board Box -->
    <div class="px-6 pt-6 pb-4 flex flex-col" :class="{'flex-1 min-h-[400px]': isKanbanExpanded}">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col" :class="{'flex-1': isKanbanExpanded}">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center cursor-pointer select-none shrink-0" @click="isKanbanExpanded = !isKanbanExpanded">
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                    Kanban Board
                </h2>
                <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="{'rotate-180': !isKanbanExpanded}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </div>

            <!-- Kanban Board -->
            <div class="flex-1 overflow-x-auto overflow-y-hidden p-6 bg-gray-50" x-show="isKanbanExpanded" x-collapse>
                <div class="flex h-full space-x-6 min-w-max pb-4">
                    <!-- Columns mapped via Alpine -->
            <template x-for="column in columns" :key="column.id">
                <div class="w-80 flex flex-col max-h-full bg-gray-100/50 rounded-xl border border-gray-200 shrink-0 transition-all duration-150"
                     :class="{
                         'ring-2 ring-blue-500 bg-blue-50/50 border-blue-400 shadow-md': dragOverColumn === column.id && draggedTask?.column_id !== column.id,
                         'opacity-90': dragOverColumn === column.id && draggedTask?.column_id === column.id
                     }"
                     @dragover.prevent="handleTaskDragOver(column.id, $event)"
                     @dragleave="handleTaskDragLeave(column.id, $event)"
                     @drop.prevent="dropTaskOnColumn(column.id)">
                    <div class="px-4 py-3 border-b border-gray-200/80 bg-gray-100 rounded-t-xl shrink-0 flex justify-between items-center">
                        <h3 class="font-semibold text-gray-700 text-sm tracking-wide" x-text="column.title"></h3>
                        <div class="flex items-center gap-1.5">
                            <span class="bg-gray-200 text-gray-600 text-xs px-2 py-0.5 rounded-full font-medium" x-text="getTasksByColumn(column.id).length"></span>
                            <button 
                                x-show="permissions.canManageTasks && (!permissions.isStaffOnly || (column.id !== 'qc_in_progress' && column.id !== 'done'))"
                                @click.stop="openNewTaskModal(column.id)"
                                type="button"
                                :title="'Add task to ' + column.title"
                                class="p-1 hover:bg-gray-200 text-gray-500 hover:text-gray-800 rounded transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="p-3 flex-1 overflow-y-auto space-y-3 min-h-[140px]">
                        <!-- Drop indicator placeholder when dragging over column -->
                        <div x-show="dragOverColumn === column.id && draggedTask?.column_id !== column.id"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="border-2 border-dashed border-blue-400 bg-blue-50/70 rounded-lg p-3 text-center text-xs font-semibold text-blue-600">
                            Drop to move to <span x-text="column.title"></span>
                        </div>

                        <template x-for="task in getTasksByColumn(column.id)" :key="task.id">
                            <!-- Task Card -->
                            <div 
                                :draggable="permissions.canManageTasks && (!permissions.isStaffOnly || (task.column_id !== 'qc_in_progress' && task.column_id !== 'done'))"
                                @dragstart="startTaskDrag(task, $event)"
                                @dragend="endTaskDrag()"
                                @click="openTaskModal(task)"
                                class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 hover:border-gray-300 hover:shadow transition-all group select-none"
                                :class="{
                                    'cursor-grab active:cursor-grabbing': permissions.canManageTasks && (!permissions.isStaffOnly || (task.column_id !== 'qc_in_progress' && task.column_id !== 'done')),
                                    'cursor-pointer': !permissions.canManageTasks || (permissions.isStaffOnly && (task.column_id === 'qc_in_progress' || task.column_id === 'done')),
                                    'opacity-40 scale-95 border-dashed border-blue-300': draggedTask?.id === task.id
                                }"
                            >
                                <div class="flex justify-between items-start mb-1.5">
                                    <div class="flex items-center gap-1.5">
                                        <svg x-show="permissions.canManageTasks" class="w-3.5 h-3.5 text-gray-300 group-hover:text-gray-500 cursor-grab shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Drag to move task">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                        </svg>
                                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider font-mono" x-text="task.code"></span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <button 
                                            x-show="permissions.canManageTasks && (!permissions.isStaffOnly || (task.column_id !== 'qc_in_progress' && task.column_id !== 'done'))"
                                            @click.stop="openEditTaskModal(task)"
                                            type="button"
                                            class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-blue-600 p-0.5 hover:bg-blue-50 rounded transition-all"
                                            title="Edit Task"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <template x-if="task.hasActiveBug">
                                            <span class="inline-flex items-center gap-1 bg-red-50 text-red-700 text-[10px] font-bold px-2 py-0.5 rounded border border-red-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                BLOCKED
                                            </span>
                                        </template>
                                    </div>
                                </div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-2 leading-snug line-clamp-2" x-text="task.title"></h4>
                                <template x-if="task.source_test_case">
                                    <div class="mb-2">
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200" title="Bug ditemukan pada Test Case ini">
                                            <span class="opacity-75 font-normal">Test Case:</span>
                                            <span class="font-mono" x-text="task.source_test_case.code"></span>
                                        </span>
                                    </div>
                                </template>
                                <div class="flex justify-between items-center mt-3 pt-2.5 border-t border-gray-100">
                                    <div class="flex items-center gap-2">
                                        <div class="inline-block h-6 w-6 rounded-full bg-blue-100 text-blue-600 ring-2 ring-white flex items-center justify-center text-[10px] font-bold uppercase shrink-0" x-text="task.assignee ? task.assignee.substring(0,2) : 'UN'" :title="task.assignee"></div>
                                        <span class="text-xs text-gray-500 font-medium truncate max-w-[110px]" x-text="task.assignee"></span>
                                    </div>

                                    <!-- Comment Counter & Discussion Button -->
                                    <button type="button" 
                                            @click.stop="openTaskModal(task, 'comments')"
                                            class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full border transition-all cursor-pointer"
                                            :class="task.comments_count > 0 ? 'text-blue-700 bg-blue-50 border-blue-200 hover:bg-blue-100' : 'text-gray-400 bg-gray-50 border-gray-200 hover:bg-gray-100 hover:text-gray-600'"
                                            title="Diskusi & Komentar">
                                        <svg class="w-3.5 h-3.5" :class="task.comments_count > 0 ? 'text-blue-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                        <span x-text="task.comments_count || 0"></span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
        </div>
    </div>

    <!-- Global Project Test Cases Box -->
    <div class="px-6 pb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center cursor-pointer select-none" @click="isTestCasesExpanded = !isTestCasesExpanded">
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    Project Test Cases (Global)
                </h2>
                <div class="flex items-center gap-3">
                    <button x-show="permissions.canManageTestCases" @click.stop="openNewTestCaseModal(null)" class="px-3 py-1.5 bg-white border border-gray-300 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50 shadow-sm transition-colors">
                        + Add Root Test Case
                    </button>
                    <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="{'rotate-180': !isTestCasesExpanded}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
            
            <div class="p-0" x-show="isTestCasesExpanded" x-collapse>

                <template x-for="tc in flatTestCases" :key="tc.id">
                    <div class="transition-all"
                         @dragover.prevent="if(permissions.canManageTestCases) handleDragOver(tc, $event)"
                         @dragleave="if(permissions.canManageTestCases) handleDragLeave(tc, $event)"
                         @drop="if(permissions.canManageTestCases) dropTestCase(tc.id)"
                         @dragend="draggedTestCase = null; dragOverTarget = null; dragOverPosition = null;">
                         
                        <!-- Before Spacer -->
                        <div class="drop-spacer overflow-hidden transition-all duration-200 ease-in-out flex items-end"
                             :class="(dragOverTarget === tc.id && dragOverPosition === 'before') ? 'h-14 opacity-100' : 'h-0 opacity-0'"
                             :style="`padding-left: ${tc.level * 2 + 1}rem`">
                            <div class="w-full h-12 mb-2 bg-blue-50 border-2 border-blue-400 border-dashed rounded-lg flex items-center justify-center text-blue-500 font-bold shadow-inner">
                                Pindahkan ke sini
                            </div>
                        </div>

                        <!-- Item Row -->
                        <div class="test-case-row flex justify-between items-center p-3 transition-all group border-b border-gray-100 bg-white relative z-10"
                             :class="{
                                 'hover:bg-gray-50': dragOverTarget !== tc.id || dragOverPosition !== 'inside',
                                 'bg-blue-50 ring-2 ring-inset ring-blue-400': dragOverTarget === tc.id && dragOverPosition === 'inside',
                                 'opacity-50': draggedTestCase?.id === tc.id
                             }"
                             :style="`padding-left: ${tc.level * 2 + 1}rem`"
                             :draggable="permissions.canManageTestCases"
                             @dragstart="if(permissions.canManageTestCases) startDragging(tc, $event)">
                        <div class="flex items-center gap-2">
                            <!-- Drag Handle -->
                            <div x-show="permissions.canManageTestCases" class="cursor-grab text-gray-300 hover:text-gray-500 mr-1" title="Drag to move">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 8v.01M10 12v.01M10 16v.01M14 8v.01M14 12v.01M14 16v.01"></path>
                                </svg>
                            </div>
                            <button x-show="tc.children && tc.children.length > 0" 
                                    @click="toggleTestCase(tc.id)"
                                    class="text-gray-400 hover:text-gray-600 focus:outline-none transition-transform" 
                                    :class="{'rotate-90': tc.is_expanded}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </button>
                            <span x-show="!tc.children || tc.children.length === 0" class="w-4 h-4 inline-block"></span>
                            
                            <span class="text-xs font-bold text-primary bg-blue-50 px-2 py-0.5 rounded border border-blue-100" x-text="tc.code"></span>
                            <template x-if="tc.app_version">
                                <span class="text-xs font-mono font-medium text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200" :title="'Versi: ' + tc.app_version" x-text="tc.app_version"></span>
                            </template>
                            <span class="text-sm font-medium text-gray-800" x-text="tc.title"></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <template x-if="tc.bug">
                                <button type="button" 
                                        @click.stop="openViewBugModal(tc.bug)"
                                        class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded border font-mono tracking-wide hover:opacity-80 transition-opacity cursor-pointer" 
                                        :class="tc.bug.status === 'resolved' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'"
                                        :title="'Lihat Detail Deskripsi Bug: ' + tc.bug.code">
                                    <svg class="w-3 h-3 shrink-0" :class="tc.bug.status === 'resolved' ? 'text-green-600' : 'text-red-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path x-show="tc.bug.status === 'resolved'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        <path x-show="tc.bug.status !== 'resolved'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <span x-text="tc.bug.code"></span>
                                </button>
                            </template>
                            <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded" 
                                  :class="{
                                    'bg-green-100 text-green-700 border border-green-200': tc.status === 'passed',
                                    'bg-red-100 text-red-700 border border-red-200': tc.status === 'failed',
                                    'bg-gray-100 text-gray-500 border border-gray-200': tc.status === 'pending'
                                  }" x-text="tc.status"></span>
                            <button @click.stop="openViewTestCaseModal(tc)" class="opacity-0 group-hover:opacity-100 text-gray-500 hover:text-gray-800 p-1 rounded-md transition-opacity" title="View Details">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </button>
                            <button x-show="permissions.canManageTestCases" @click.stop="openEditTestCaseModal(tc)" class="opacity-0 group-hover:opacity-100 text-blue-500 hover:text-blue-700 p-1 rounded-md transition-opacity" title="Edit Test Case">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                            <button x-show="permissions.canManageTestCases" @click.stop="duplicateTestCase(tc)" class="opacity-0 group-hover:opacity-100 text-yellow-500 hover:text-yellow-700 p-1 rounded-md transition-opacity" title="Duplicate Test Case">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
                            </button>
                            <button x-show="permissions.canManageTestCases" @click.stop="deleteTestCase(tc.id)" class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 p-1 rounded-md transition-opacity" title="Delete Test Case">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                            <button x-show="permissions.canExecuteTests" @click.stop="openRunTestModal(tc)" class="opacity-0 group-hover:opacity-100 text-green-600 hover:text-green-800 p-1 rounded-md transition-opacity" title="Run Test">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </button>
                            <button x-show="permissions.canManageTestCases" @click.stop="openNewTestCaseModal(tc)" class="opacity-0 group-hover:opacity-100 text-xs text-blue-600 hover:text-blue-800 font-medium transition-opacity" title="Add Sub Test">+ Sub Test</button>
                        </div>
                    </div>

                        <!-- After Spacer -->
                        <div class="drop-spacer overflow-hidden transition-all duration-200 ease-in-out flex items-start"
                             :class="(dragOverTarget === tc.id && dragOverPosition === 'after') ? 'h-14 opacity-100' : 'h-0 opacity-0'"
                             :style="`padding-left: ${tc.level * 2 + 1}rem`">
                            <div class="w-full h-12 mt-2 bg-blue-50 border-2 border-blue-400 border-dashed rounded-lg flex items-center justify-center text-blue-500 font-bold shadow-inner">
                                Pindahkan ke sini
                            </div>
                        </div>
                    </div>
                </template>
                
                <template x-if="projectTestCases.length === 0">
                    <div class="p-8 text-center text-gray-500 text-sm">
                        No test cases found for this project.
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Defects / Bug Tracker Box -->
    <div class="px-6 pb-6">
        <div class="bg-white rounded-xl shadow-sm border border-red-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-red-100 bg-red-50 flex justify-between items-center cursor-pointer select-none" @click="isBugsExpanded = !isBugsExpanded">
                <h2 class="text-lg font-bold text-red-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Defects / Bug Tracker
                </h2>
                <div class="flex items-center gap-3">
                    <span class="bg-red-200 text-red-800 py-0.5 px-2.5 rounded-full text-xs font-bold" x-text="projectBugs.length + ' Bugs'"></span>
                    <svg class="w-5 h-5 text-red-600 transition-transform duration-200" :class="{'rotate-180': !isBugsExpanded}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
            
            <div class="p-0 bg-white" x-show="isBugsExpanded" x-collapse>
                <!-- Bug Tracker Tabs -->
                <div class="border-b border-gray-200 bg-gray-50/75 px-6 pt-2">
                    <nav class="-mb-px flex space-x-6">
                        <button @click="bugFilterTab = 'active'" 
                                :class="{'border-amber-500 text-amber-800 font-bold': bugFilterTab === 'active', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium': bugFilterTab !== 'active'}"
                                class="whitespace-nowrap pb-3 pt-2 px-1 border-b-2 text-sm transition-colors flex items-center gap-2">
                            Active Bugs (Open)
                            <span class="bg-amber-100 text-amber-800 py-0.5 px-2 rounded-full text-xs font-bold" x-text="projectBugs.filter(b => b.status !== 'resolved').length"></span>
                        </button>
                        <button @click="bugFilterTab = 'solved'" 
                                :class="{'border-green-600 text-green-700 font-bold': bugFilterTab === 'solved', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium': bugFilterTab !== 'solved'}"
                                class="whitespace-nowrap pb-3 pt-2 px-1 border-b-2 text-sm transition-colors flex items-center gap-2">
                            Resolved Bugs
                            <span class="bg-green-100 text-green-800 py-0.5 px-2 rounded-full text-xs font-bold" x-text="projectBugs.filter(b => b.status === 'resolved').length"></span>
                        </button>
                        <button @click="bugFilterTab = 'all'" 
                                :class="{'border-red-600 text-red-700 font-bold': bugFilterTab === 'all', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium': bugFilterTab !== 'all'}"
                                class="whitespace-nowrap pb-3 pt-2 px-1 border-b-2 text-sm transition-colors flex items-center gap-2">
                            All Bugs
                            <span class="bg-gray-100 text-gray-700 py-0.5 px-2 rounded-full text-xs font-semibold" x-text="projectBugs.length"></span>
                        </button>
                    </nav>
                </div>

                <!-- Selection Action Toolbar -->
                <div x-show="selectedBugIds.length > 0 && permissions.canManageBugs" x-cloak class="px-6 py-2.5 bg-blue-50 border-b border-blue-200 flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center bg-blue-600 text-white text-xs font-bold px-2.5 py-0.5 rounded-full" x-text="selectedBugIds.length + ' Selected Bugs'"></span>
                        <span class="text-xs text-blue-900 font-medium">Select multiple bugs to merge into a single new Kanban Task.</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="openBulkConvertModal()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-xs font-bold shadow-xs transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            <span>Create 1 Kanban Task (<span x-text="selectedBugIds.length"></span> Bugs)</span>
                        </button>
                        <button type="button" @click="selectedBugIds = []" class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-md text-xs font-medium transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <!-- Main Column Headers -->
                            <tr>
                                <th scope="col" x-show="permissions.canManageBugs" class="px-4 py-3 text-center w-10">
                                    <input type="checkbox" 
                                           @change="toggleSelectAllBugs($event)" 
                                           :checked="isAllBugsSelected" 
                                           x-effect="$el.indeterminate = isSomeBugsSelected"
                                           title="Select All Bugs"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4 cursor-pointer">
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Bug Details</th>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-24">Versi</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-28">Severity</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-28">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Test Case</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kanban Task</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider w-36">Action</th>
                            </tr>
                            <!-- Per-Column Filters Row -->
                            <tr class="bg-gray-100/90 border-t border-gray-200 text-xs">
                                <th scope="col" x-show="permissions.canManageBugs" class="px-2 py-2 text-center">
                                    <button type="button" x-show="hasActiveBugFilters" @click="resetBugFilters()" class="text-gray-400 hover:text-red-600 transition-colors p-1" title="Reset All Filters">
                                        <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </th>
                                <th scope="col" class="px-6 py-2">
                                    <div class="relative">
                                        <input type="text" x-model="bugFilters.details" placeholder="Filter code / description..." class="w-full text-xs pl-7 pr-2 py-1 bg-white border border-gray-300 rounded-md focus:ring-1 focus:ring-primary focus:border-primary placeholder-gray-400 font-normal">
                                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2 top-2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </div>
                                </th>
                                <th scope="col" class="px-2 py-2">
                                    <div class="relative">
                                        <input type="text" x-model="bugFilters.version" placeholder="Filter versi..." class="w-full text-xs px-2 py-1 bg-white border border-gray-300 rounded-md focus:ring-1 focus:ring-primary focus:border-primary placeholder-gray-400 font-normal">
                                    </div>
                                </th>
                                <th scope="col" class="px-3 py-2">
                                    <select x-model="bugFilters.severity" class="w-full text-xs px-2 py-1 bg-white border border-gray-300 rounded-md focus:ring-1 focus:ring-primary focus:border-primary font-normal text-gray-700">
                                        <option value="">All</option>
                                        <option value="Critical">Critical</option>
                                        <option value="High">High</option>
                                        <option value="Medium">Medium</option>
                                        <option value="Low">Low</option>
                                    </select>
                                </th>
                                <th scope="col" class="px-3 py-2">
                                    <select x-model="bugFilters.status" class="w-full text-xs px-2 py-1 bg-white border border-gray-300 rounded-md focus:ring-1 focus:ring-primary focus:border-primary font-normal text-gray-700">
                                        <option value="">All</option>
                                        <option value="open">Open</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="resolved">Resolved</option>
                                    </select>
                                </th>
                                <th scope="col" class="px-6 py-2">
                                    <div class="relative">
                                        <input type="text" x-model="bugFilters.testCase" placeholder="Filter test case..." class="w-full text-xs pl-7 pr-2 py-1 bg-white border border-gray-300 rounded-md focus:ring-1 focus:ring-primary focus:border-primary placeholder-gray-400 font-normal">
                                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2 top-2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-2">
                                    <select x-model="bugFilters.task" class="w-full text-xs px-2 py-1 bg-white border border-gray-300 rounded-md focus:ring-1 focus:ring-primary focus:border-primary font-normal text-gray-700">
                                        <option value="">All Tasks</option>
                                        <option value="assigned">Assigned</option>
                                        <option value="unassigned">Unassigned</option>
                                    </select>
                                </th>
                                <th scope="col" class="px-6 py-2 text-right">
                                    <button type="button" x-show="hasActiveBugFilters" @click="resetBugFilters()" class="inline-flex items-center gap-1 text-[11px] text-red-600 hover:text-red-800 font-semibold px-2 py-1 rounded bg-red-50 hover:bg-red-100 border border-red-200 transition-colors" title="Reset All Column Filters">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span>Reset</span>
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="bug in filteredProjectBugs" :key="bug.id">
                                <tr class="hover:bg-gray-50 transition-colors"
                                    :class="{
                                        'bg-blue-50/60': isBugSelected(bug.id),
                                        'bg-green-50/20': bug.status === 'resolved' && !isBugSelected(bug.id)
                                    }">
                                    <td x-show="permissions.canManageBugs" class="px-4 py-4 text-center whitespace-nowrap">
                                        <input type="checkbox" 
                                               :checked="isBugSelected(bug.id)" 
                                               @change="toggleBugSelection(bug.id)" 
                                               @click.stop 
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4 cursor-pointer">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-2 mb-1">
                                                <button type="button"
                                                        @click="openViewBugModal(bug)"
                                                        class="text-xs font-bold px-2 py-0.5 rounded border w-max font-mono cursor-pointer transition-colors"
                                                        :class="bug.status === 'resolved' ? 'text-green-700 bg-green-50 border-green-200 hover:bg-green-100' : 'text-red-700 bg-red-50 border-red-100 hover:bg-red-100'"
                                                        title="Klik untuk melihat detail lengkap bug"
                                                        x-text="bug.code"></button>
                                                <template x-if="bug.status === 'resolved'">
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-green-700 bg-green-100 px-1.5 py-0.5 rounded border border-green-200">
                                                        <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                        SOLVED
                                                    </span>
                                                </template>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900 line-clamp-2 cursor-pointer hover:text-blue-600 transition-colors" 
                                                  @click="openViewBugModal(bug)" 
                                                  title="Click to view details" 
                                                  x-text="bug.description"></span>
                                            <div class="flex items-center gap-3 text-xs text-gray-500 mt-1.5 flex-wrap">
                                                <span x-text="'Reported: ' + (bug.created_at_human || bug.created_at)"></span>
                                                <template x-if="bug.status === 'resolved' && bug.updated_at">
                                                    <span class="text-green-700 font-medium" x-text="'Resolved: ' + bug.updated_at"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- Separate Versi Column -->
                                    <td class="px-3 py-4 whitespace-nowrap">
                                        <template x-if="bug.app_version">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-medium bg-purple-50 text-purple-700 border border-purple-200" x-text="bug.app_version"></span>
                                        </template>
                                        <template x-if="!bug.app_version">
                                            <span class="text-xs text-gray-400 italic">-</span>
                                        </template>
                                    </td>
                                    <!-- Separate Severity Column -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded w-max" 
                                              :class="{
                                                'bg-red-100 text-red-700 border border-red-200': bug.severity === 'Critical' || bug.severity === 'High',
                                                'bg-yellow-100 text-yellow-700 border border-yellow-200': bug.severity === 'Medium',
                                                'bg-green-100 text-green-700 border border-green-200': bug.severity === 'Low',
                                                'bg-gray-100 text-gray-700 border border-gray-200': !bug.severity
                                              }" x-text="bug.severity || 'Unknown'"></span>
                                    </td>
                                    <!-- Separate Status Column -->
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border w-max"
                                              :class="{
                                                'bg-green-100 text-green-700 border border-green-200': bug.status === 'resolved',
                                                'bg-red-100 text-red-700 border border-red-200': bug.status === 'open',
                                                'bg-amber-100 text-amber-700 border border-amber-200': bug.status === 'in_progress',
                                                'bg-gray-100 text-gray-600 border border-gray-200': bug.status !== 'resolved' && bug.status !== 'open' && bug.status !== 'in_progress'
                                              }"
                                              x-text="bug.status"></span>
                                    </td>
                                    <!-- Test Case Column with Clickable Code opening Modal -->
                                    <td class="px-6 py-4">
                                        <template x-if="bug.test_case">
                                            <div class="text-sm text-gray-600">
                                                <div class="flex items-center gap-1.5 mb-1 flex-wrap">
                                                    <button type="button" 
                                                            @click.stop="openViewTestCaseModal(bug.test_case)"
                                                            class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 hover:text-blue-900 border border-blue-200 hover:border-blue-300 transition-colors inline-flex items-center gap-1 cursor-pointer shadow-2xs"
                                                            title="Klik untuk melihat pop up detail Test Case">
                                                        <svg class="w-3 h-3 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                        <span x-text="bug.test_case.code"></span>
                                                    </button>
                                                    <span class="text-[9px] uppercase font-bold px-1.5 py-0.2 rounded border"
                                                          :class="bug.test_case.status === 'passed' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200'"
                                                          x-text="bug.test_case.status"></span>
                                                </div>
                                                <span class="text-xs text-gray-700 block line-clamp-1" :title="bug.test_case.title" x-text="bug.test_case.title"></span>
                                            </div>
                                        </template>
                                        <template x-if="!bug.test_case">
                                            <span class="text-xs text-gray-400 italic">No Test Case</span>
                                        </template>
                                    </td>
                                    <td class="px-6 py-4">
                                        <template x-if="bug.project_task">
                                            <div class="flex items-center gap-2 cursor-pointer group" @click="openTaskModalById(bug.project_task.id)">
                                                <span class="text-xs font-medium text-blue-600 group-hover:text-blue-800 underline decoration-blue-300 decoration-dotted font-mono" x-text="bug.project_task.code"></span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider"
                                                      :class="getColumnBadgeClass(bug.project_task.column_id)"
                                                      x-text="getColumnTitle(bug.project_task.column_id)"></span>
                                            </div>
                                        </template>
                                        <template x-if="!bug.project_task">
                                            <span class="inline-flex items-center text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-orange-100 text-orange-700 border border-orange-200">Unassigned</span>
                                        </template>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium flex items-center justify-end gap-2">
                                        <button type="button" 
                                                @click="openViewBugModal(bug)" 
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-700 border border-gray-300 hover:border-blue-300 rounded-md text-xs font-medium transition-colors shadow-xs" 
                                                title="Lihat Detail Deskripsi Bug">
                                            <svg class="w-3.5 h-3.5 text-gray-500 hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            <span>Detail</span>
                                        </button>
                                        <button x-show="permissions.canManageBugs" 
                                                type="button" 
                                                @click="openEditBugModal(bug)" 
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white hover:bg-amber-50 text-gray-700 hover:text-amber-700 border border-gray-300 hover:border-amber-300 rounded-md text-xs font-medium transition-colors shadow-xs" 
                                                title="Edit Bug Report">
                                            <svg class="w-3.5 h-3.5 text-gray-500 hover:text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            <span>Edit</span>
                                        </button>
                                        <template x-if="!bug.project_task && bug.status !== 'resolved' && permissions.canManageBugs">
                                            <button @click="convertBugToTask(bug.id)" :disabled="convertingBugId === bug.id" :class="{'opacity-75 cursor-wait': convertingBugId === bug.id}" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none transition-colors">
                                                <svg x-show="convertingBugId === bug.id" class="animate-spin -ml-0.5 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                <svg x-show="convertingBugId !== bug.id" class="mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                                <span x-text="convertingBugId === bug.id ? 'Creating...' : 'Create Task'"></span>
                                            </button>
                                        </template>
                                        <button x-show="permissions.canManageBugs" @click="deleteBug(bug.id)" class="text-gray-400 hover:text-red-600 p-1.5 rounded-md hover:bg-red-50 transition-colors" title="Delete Bug">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <template x-if="filteredProjectBugs.length === 0">
                    <div class="p-8 text-center text-gray-500 text-sm">
                        <template x-if="hasActiveBugFilters">
                            <div class="flex flex-col items-center justify-center gap-2 py-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                                <span class="font-medium text-gray-600">No bugs match the selected filter criteria.</span>
                                <button type="button" @click="resetBugFilters()" class="text-xs text-primary hover:underline font-bold mt-1">
                                    Reset All Column Filters
                                </button>
                            </div>
                        </template>
                        <template x-if="!hasActiveBugFilters && bugFilterTab === 'solved'">
                            <span>No resolved bugs yet.</span>
                        </template>
                        <template x-if="!hasActiveBugFilters && bugFilterTab === 'active'">
                            <span class="text-green-600 font-semibold">No active bugs right now! All bugs have been resolved.</span>
                        </template>
                        <template x-if="!hasActiveBugFilters && bugFilterTab === 'all'">
                            <span>No bugs reported yet. Great job!</span>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Floating Root Drop Zone -->
    <div x-show="draggedTestCase" x-transition.opacity.duration.300ms
         class="fixed bottom-8 left-1/2 transform -translate-x-1/2 z-50 px-8 py-4 rounded-full shadow-2xl border-2 border-dashed flex items-center gap-3 transition-all duration-200"
         :class="{'scale-110 bg-blue-600 border-blue-300 text-white': dragOverTarget === 'root', 'bg-gray-800 border-gray-500 text-gray-200': dragOverTarget !== 'root'}"
         @dragover.prevent="dragOverTarget = 'root'"
         @dragleave="if (dragOverTarget === 'root') dragOverTarget = null"
         @drop="dropTestCase(null)"
         x-cloak>
         <svg class="w-6 h-6 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
         <span class="font-bold tracking-wide">Drop here to move to Root Level</span>
    </div>

    <!-- Modals Overlay View -->
    
    <!-- Task Detail & Manual QA Modal -->
    <div x-show="isTaskModalOpen" 
         class="fixed inset-0 z-[60] overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            <!-- Backdrop -->
            <div x-show="isTaskModalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" 
                 @click="closeTaskModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <!-- Modal Panel -->
            <div x-show="isTaskModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl w-full border border-gray-200">
                
                <div class="flex flex-col max-h-[90vh]">
                    <!-- Modal Header -->
                    <div class="bg-white px-6 py-4 border-b border-gray-200 shrink-0 flex justify-between items-start">
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide" x-text="activeTask?.code"></span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider"
                                      :class="getColumnBadgeClass(activeTask?.column_id)"
                                      x-text="getColumnTitle(activeTask?.column_id)"></span>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900" id="modal-title" x-text="activeTask?.title"></h2>
                            <p class="text-sm text-gray-500 mt-1">Assignee: <span class="font-medium text-gray-700" x-text="activeTask?.assignee"></span></p>
                            
                            <!-- State Transition Buttons -->
                            <div class="mt-3 flex gap-2" x-show="permissions.canManageTasks">
                                <template x-if="activeTask?.column_id === 'todo'">
                                    <button @click="updateTaskColumn(activeTask.id, 'in_progress')" :disabled="isMovingTask" :class="{'opacity-75 cursor-wait': isMovingTask}" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded shadow-sm text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                                        <svg x-show="movingToColumn === 'in_progress'" class="animate-spin mr-1.5 h-3 w-3 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <span x-text="movingToColumn === 'in_progress' ? 'Moving...' : 'Move to In Progress &rarr;'"></span>
                                    </button>
                                </template>
                                
                                <template x-if="activeTask?.column_id === 'in_progress'">
                                    <div class="flex gap-2">
                                        <button @click="updateTaskColumn(activeTask.id, 'todo')" :disabled="isMovingTask" :class="{'opacity-75 cursor-wait': isMovingTask}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-semibold rounded shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                            <svg x-show="movingToColumn === 'todo'" class="animate-spin mr-1.5 h-3 w-3 text-gray-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span x-text="movingToColumn === 'todo' ? 'Moving...' : '&larr; Back to To Do'"></span>
                                        </button>
                                        <button @click="updateTaskColumn(activeTask.id, 'ready_for_qc')" :disabled="isMovingTask" :class="{'opacity-75 cursor-wait': isMovingTask}" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 transition-colors">
                                            <svg x-show="movingToColumn === 'ready_for_qc'" class="animate-spin mr-1.5 h-3 w-3 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span x-text="movingToColumn === 'ready_for_qc' ? 'Moving...' : 'Move to Ready for QC &rarr;'"></span>
                                        </button>
                                    </div>
                                </template>
                                
                                <template x-if="activeTask?.column_id === 'ready_for_qc'">
                                    <div class="flex gap-2">
                                        <button @click="updateTaskColumn(activeTask.id, 'in_progress')" :disabled="isMovingTask" :class="{'opacity-75 cursor-wait': isMovingTask}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-semibold rounded shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                            <svg x-show="movingToColumn === 'in_progress'" class="animate-spin mr-1.5 h-3 w-3 text-gray-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span x-text="movingToColumn === 'in_progress' ? 'Moving...' : '&larr; Back to In Progress'"></span>
                                        </button>
                                        <button x-show="!permissions.isStaffOnly" @click="updateTaskColumn(activeTask.id, 'qc_in_progress')" :disabled="isMovingTask" :class="{'opacity-75 cursor-wait': isMovingTask}" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded shadow-sm text-white bg-purple-600 hover:bg-purple-700 transition-colors">
                                            <svg x-show="movingToColumn === 'qc_in_progress'" class="animate-spin mr-1.5 h-3 w-3 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span x-text="movingToColumn === 'qc_in_progress' ? 'Moving...' : 'Start QC Process &rarr;'"></span>
                                        </button>
                                    </div>
                                </template>
                                
                                <template x-if="activeTask?.column_id === 'qc_in_progress' && !permissions.isStaffOnly">
                                    <div class="flex gap-2">
                                        <button @click="updateTaskColumn(activeTask.id, 'ready_for_qc')" :disabled="isMovingTask" :class="{'opacity-75 cursor-wait': isMovingTask}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-semibold rounded shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                            <svg x-show="movingToColumn === 'ready_for_qc'" class="animate-spin mr-1.5 h-3 w-3 text-gray-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span x-text="movingToColumn === 'ready_for_qc' ? 'Moving...' : '&larr; Back to Ready for QC'"></span>
                                        </button>
                                        <button @click="updateTaskColumn(activeTask.id, 'todo')" :disabled="isMovingTask" :class="{'opacity-75 cursor-wait': isMovingTask}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-semibold rounded shadow-sm text-red-600 bg-red-50 hover:bg-red-100 transition-colors">
                                            <svg x-show="movingToColumn === 'todo'" class="animate-spin mr-1.5 h-3 w-3 text-red-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span x-text="movingToColumn === 'todo' ? 'Moving...' : '&#x21BA; Return to Developer'"></span>
                                        </button>
                                        <button x-show="permissions.canExecuteTests" @click="updateTaskColumn(activeTask.id, 'done')" :disabled="isMovingTask" :class="{'opacity-75 cursor-wait': isMovingTask}" class="inline-flex items-center px-3.5 py-1.5 border border-transparent text-xs font-bold rounded shadow-sm text-white bg-green-600 hover:bg-green-700 transition-colors">
                                            <svg x-show="movingToColumn === 'done'" class="animate-spin mr-1.5 h-3 w-3 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span x-text="movingToColumn === 'done' ? 'Passing QC...' : 'Pass QC & Mark Done &check;'"></span>
                                        </button>
                                    </div>
                                </template>
                                
                                <template x-if="activeTask?.column_id === 'done' && !permissions.isStaffOnly">
                                    <button @click="updateTaskColumn(activeTask.id, 'qc_in_progress')" :disabled="isMovingTask" :class="{'opacity-75 cursor-wait': isMovingTask}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-semibold rounded shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                        <svg x-show="movingToColumn === 'qc_in_progress'" class="animate-spin mr-1.5 h-3 w-3 text-gray-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <span x-text="movingToColumn === 'qc_in_progress' ? 'Moving...' : '&larr; Reopen (Back to QC)'"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button 
                                x-show="permissions.canManageTasks && (!permissions.isStaffOnly || (activeTask?.column_id !== 'qc_in_progress' && activeTask?.column_id !== 'done'))"
                                @click="openEditTaskModal(activeTask)"
                                type="button" 
                                class="bg-blue-50 p-1.5 rounded-md text-blue-600 hover:text-blue-800 hover:bg-blue-100 transition-colors focus:outline-none" 
                                title="Edit Task"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button x-show="permissions.canManageTasks && !permissions.isStaffOnly" @click="deleteTask(activeTask.id)" type="button" class="bg-red-50 p-1 rounded text-red-500 hover:text-red-700 hover:bg-red-100 transition-colors focus:outline-none" title="Delete Task">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                            <button @click="closeTaskModal()" type="button" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Tabs Navigation -->
                    <div class="border-b border-gray-200 bg-gray-50/50 px-6 pt-2 shrink-0">
                        <nav class="-mb-px flex space-x-6">
                            <button @click="activeTab = 'details'" 
                                    :class="{'border-blue-500 text-blue-600': activeTab === 'details', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'details'}"
                                    class="whitespace-nowrap pb-3 pt-2 px-1 border-b-2 font-medium text-sm transition-colors">
                                Details
                            </button>
                            <button @click="activeTab = 'test_cases'" 
                                    :class="{'border-blue-500 text-blue-600': activeTab === 'test_cases', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'test_cases'}"
                                    class="whitespace-nowrap pb-3 pt-2 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                                Test Cases
                                <span class="bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs" x-text="activeTask?.testCases?.length || 0"></span>
                            </button>
                            <button @click="activeTab = 'bugs'" 
                                    x-show="activeTask?.bugs && activeTask.bugs.length > 0"
                                    :class="{'border-red-500 text-red-600': activeTab === 'bugs', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'bugs'}"
                                    class="whitespace-nowrap pb-3 pt-2 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                                Bugs
                                <span class="bg-red-100 text-red-600 py-0.5 px-2 rounded-full text-xs" x-text="activeTask?.bugs?.length || 0"></span>
                            </button>
                            <button @click="activeTab = 'comments'" 
                                    :class="{'border-blue-500 text-blue-600': activeTab === 'comments', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'comments'}"
                                    class="whitespace-nowrap pb-3 pt-2 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                                <span>Diskusi & Komentar</span>
                                <span class="py-0.5 px-2 rounded-full text-xs font-semibold"
                                      :class="activeTaskComments.length > 0 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'"
                                      x-text="activeTaskComments.length"></span>
                            </button>
                        </nav>
                    </div>

                    <!-- Modal Body (Scrollable) -->
                    <div class="px-6 py-6 overflow-y-auto flex-1 bg-white">
                        
                        <!-- Details Tab -->
                        <div x-show="activeTab === 'details'" class="prose prose-sm max-w-none text-gray-600">
                            <!-- Linked Test Case Banner if Task originates from a Bug -->
                            <template x-if="activeTask?.source_test_case">
                                <div class="mb-4 p-4 bg-amber-50/80 border border-amber-200 rounded-lg not-prose flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                                    <div class="flex items-start sm:items-center gap-3">
                                        <div class="p-2 bg-amber-100 text-amber-800 rounded-lg shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        </div>
                                        <div>
                                            <div class="text-xs text-amber-800 font-medium">Bug ini ditemukan pada Test Case:</div>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-xs font-bold text-primary bg-blue-50 px-2 py-0.5 rounded border border-blue-100 font-mono" x-text="activeTask.source_test_case.code"></span>
                                                <span class="text-sm font-semibold text-gray-900" x-text="activeTask.source_test_case.title"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" @click="activeTab = 'test_cases'" class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1 shrink-0 self-end sm:self-center">
                                        Buka Tab Test Case &rarr;
                                    </button>
                                </div>
                            </template>

                            <h4 class="text-gray-800 font-semibold mb-2">Description</h4>
                            <p x-text="activeTask?.description || 'No description provided.'"></p>
                            
                            <template x-if="activeTask?.attachment_path">
                                <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-md">
                                    <h4 class="text-gray-800 font-semibold mb-2 text-xs uppercase">Attachment</h4>
                                    <a :href="'/storage/' + activeTask.attachment_path" target="_blank" class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                        View Attachment
                                    </a>
                                </div>
                            </template>
                        </div>

                        <!-- Test Cases Tab -->
                        <div x-show="activeTab === 'test_cases'" x-cloak>
                            <!-- Alert banner stating which test case the bug was found in -->
                            <template x-if="activeTask?.source_test_case">
                                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg flex items-center justify-between shadow-xs">
                                    <div class="flex items-center gap-2.5">
                                        <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <div class="text-xs text-red-900">
                                            <span>Bug ini ditemukan pada Test Case:</span>
                                            <span class="font-bold font-mono px-1.5 py-0.5 bg-red-100 rounded text-red-800 ml-1" x-text="activeTask.source_test_case.code"></span>
                                            <span class="font-semibold text-gray-800 ml-1" x-text="activeTask.source_test_case.title"></span>
                                        </div>
                                    </div>
                                    <button type="button" @click.stop="openViewTestCaseModal(activeTask.source_test_case)" class="text-xs text-red-700 hover:text-red-900 font-bold underline shrink-0">
                                        Detail Skenario &rarr;
                                    </button>
                                </div>
                            </template>

                            <template x-if="activeTask?.testCases && activeTask.testCases.length > 0">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                                        <span class="text-xs text-gray-500 font-medium" x-text="activeTask.testCases.length + ' Test Cases linked to this task'"></span>
                                        <button x-show="permissions.canExecuteTests" type="button" 
                                                @click="passAllTaskTestCases(activeTask.id)" 
                                                :disabled="isPassingAllTests"
                                                class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 rounded-md text-xs font-semibold shadow-xs transition-colors disabled:opacity-50">
                                            <svg x-show="isPassingAllTests" class="animate-spin h-3 w-3 text-green-700" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <svg x-show="!isPassingAllTests" class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Pass All Test Cases</span>
                                        </button>
                                    </div>
                                    <template x-for="tc in activeTask.testCases" :key="tc.id">
                                        <div class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg shadow-sm" :class="{'border-red-200 bg-red-50/20': tc.is_from_bug}">
                                            <div>
                                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                    <span class="text-xs font-bold text-primary bg-blue-50 px-2 py-0.5 rounded border border-blue-100" x-text="tc.code"></span>
                                                    <template x-if="tc.app_version">
                                                        <span class="text-[10px] font-mono font-medium text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200" x-text="tc.app_version"></span>
                                                    </template>
                                                    <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded" 
                                                          :class="{
                                                            'bg-green-100 text-green-700 border border-green-200': tc.status === 'passed',
                                                            'bg-red-100 text-red-700 border border-red-200': tc.status === 'failed',
                                                            'bg-gray-100 text-gray-500 border border-gray-200': tc.status === 'pending'
                                                          }" x-text="tc.status"></span>
                                                    <template x-if="tc.is_from_bug">
                                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded bg-red-100 text-red-800 border border-red-200">
                                                            <svg class="w-3 h-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                            Bug Ditemukan di Kode: <span class="font-mono font-extrabold" x-text="tc.code"></span>
                                                        </span>
                                                    </template>
                                                </div>
                                                <h4 class="text-sm font-semibold text-gray-800" x-text="tc.title"></h4>
                                            </div>
                                            <div class="flex gap-2 shrink-0">
                                                <button @click.stop="openViewTestCaseModal(tc)" class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors">
                                                    View
                                                </button>
                                                <button x-show="permissions.canExecuteTests" @click.stop="openRunTestModal(tc)" class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700 transition-colors shadow-sm flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    Run
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!activeTask?.testCases || activeTask.testCases.length === 0">
                                <div class="text-center py-10">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                    <h3 class="text-sm font-medium text-gray-900">No Test Cases</h3>
                                    <p class="mt-1 text-sm text-gray-500">There are no test cases linked to this task yet.</p>
                                </div>
                            </template>
                        </div>

                        <!-- Bugs Tab -->
                        <div x-show="activeTab === 'bugs'" x-cloak>
                            <template x-if="activeTask?.bugs && activeTask.bugs.length > 0">
                                <div class="space-y-3">
                                    <template x-for="bug in activeTask.bugs" :key="bug.id">
                                        <div class="flex flex-col p-4 bg-white border border-red-200 rounded-lg shadow-sm">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="text-xs font-bold text-red-700 bg-red-50 px-2 py-0.5 rounded border border-red-100" x-text="bug.code"></span>
                                                <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-gray-100 text-gray-600 border border-gray-200" x-text="bug.status"></span>
                                                <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded" 
                                                      :class="{
                                                        'bg-red-100 text-red-700': bug.severity === 'Critical' || bug.severity === 'High',
                                                        'bg-yellow-100 text-yellow-700': bug.severity === 'Medium',
                                                        'bg-green-100 text-green-700': bug.severity === 'Low'
                                                      }" x-text="bug.severity || 'Unknown'"></span>
                                            </div>
                                            <h4 class="text-sm font-medium text-gray-800 whitespace-pre-wrap" x-text="bug.description"></h4>
                                            
                                            <template x-if="bug.test_case">
                                                <div class="mt-2 text-xs flex items-center gap-1.5 bg-gray-50 p-2 rounded border border-gray-100">
                                                    <span class="font-semibold text-gray-600">Ditemukan pada Test Case:</span>
                                                    <span class="font-mono font-bold text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100" x-text="bug.test_case.code"></span>
                                                    <span class="text-gray-800 font-medium" x-text="bug.test_case.title"></span>
                                                </div>
                                            </template>
                                            
                                            <template x-if="bug.actual_result || bug.environment">
                                                <div class="mt-3 text-xs text-gray-600 grid grid-cols-1 md:grid-cols-2 gap-2 bg-gray-50 p-2 rounded border border-gray-100">
                                                    <div x-show="bug.environment"><strong>Environment:</strong> <span x-text="bug.environment"></span></div>
                                                    <div x-show="bug.actual_result"><strong>Actual Result:</strong> <span x-text="bug.actual_result"></span></div>
                                                </div>
                                            </template>

                                            <div class="mt-3 pt-2 border-t border-gray-100 flex justify-end">
                                                <button type="button" @click="openViewBugModal(bug)" class="text-xs text-blue-600 hover:text-blue-800 font-semibold underline inline-flex items-center gap-1">
                                                    <span>View Bug Details &rarr;</span>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- Comments / Diskusi Tab -->
                        <div x-show="activeTab === 'comments'" x-cloak class="flex flex-col h-full min-h-[420px]">
                            <!-- Info Banner -->
                            <div class="mb-4 p-3 bg-blue-50/80 border border-blue-200 rounded-lg flex items-center justify-between text-xs text-blue-900">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                    <span>Direct discussion between <strong>Programmer</strong> and <strong>QC</strong> regarding task execution and verification.</span>
                                </div>
                                <button type="button" @click="fetchTaskComments(activeTask.id)" class="text-blue-700 hover:text-blue-900 font-semibold underline flex items-center gap-1 shrink-0">
                                    <svg class="w-3.5 h-3.5" :class="{'animate-spin': isLoadingComments}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span>Refresh</span>
                                </button>
                            </div>

                            <!-- Message Stream / Timeline -->
                            <div class="flex-1 overflow-y-auto space-y-4 mb-6 pr-1 max-h-[380px]" id="comments-container">
                                <template x-if="activeTaskComments.length === 0">
                                    <div class="text-center py-12 bg-gray-50/50 rounded-xl border border-dashed border-gray-200">
                                        <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-500 mx-auto flex items-center justify-center mb-3">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                        </div>
                                        <h4 class="text-sm font-semibold text-gray-800">No conversations yet</h4>
                                        <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">Start a discussion or coordination between the Programmer and QC team by writing the first comment below.</p>
                                    </div>
                                </template>

                                <template x-for="c in activeTaskComments" :key="c.id">
                                    <div class="flex items-start gap-3 p-4 rounded-xl border transition-all"
                                         :class="c.can_delete ? 'bg-white border-blue-100 shadow-xs' : 'bg-gray-50/60 border-gray-200'">
                                        <!-- User Initials Avatar -->
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 uppercase shadow-xs ring-2 ring-white"
                                             :class="{
                                                 'bg-purple-100 text-purple-700': c.user?.role === 'superadmin' || c.user?.role === 'admin',
                                                 'bg-emerald-100 text-emerald-700': c.user?.role === 'qc',
                                                 'bg-blue-100 text-blue-700': c.user?.role !== 'superadmin' && c.user?.role !== 'admin' && c.user?.role !== 'qc'
                                             }"
                                             x-text="c.user ? c.user.name.substring(0, 2) : '?'">
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <!-- Comment Meta Header -->
                                            <div class="flex items-center justify-between flex-wrap gap-2 mb-1.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-bold text-gray-900" x-text="c.user ? c.user.name : 'Unknown'"></span>
                                                    
                                                    <!-- Role Badge -->
                                                    <template x-if="c.user?.role">
                                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold uppercase tracking-wider border"
                                                              :class="{
                                                                  'bg-purple-50 text-purple-700 border-purple-200': c.user.role === 'superadmin' || c.user.role === 'admin',
                                                                  'bg-emerald-50 text-emerald-700 border-emerald-200': c.user.role === 'qc',
                                                                  'bg-blue-50 text-blue-700 border-blue-200': c.user.role !== 'superadmin' && c.user.role !== 'admin' && c.user.role !== 'qc'
                                                              }"
                                                              x-text="c.user.role"></span>
                                                    </template>

                                                    <span class="text-[11px] text-gray-400 font-medium" :title="c.created_at" x-text="c.created_at_human || c.created_at"></span>
                                                </div>

                                                <!-- Action Buttons (Delete) -->
                                                <template x-if="c.can_delete && permissions.canComment">
                                                    <button type="button" 
                                                            @click="deleteTaskComment(c.id)" 
                                                            :disabled="deletingCommentId === c.id"
                                                            class="text-gray-400 hover:text-red-600 p-1 rounded hover:bg-red-50 transition-colors" 
                                                            title="Delete Comment">
                                                        <svg x-show="deletingCommentId !== c.id" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        <svg x-show="deletingCommentId === c.id" class="w-3.5 h-3.5 animate-spin text-red-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                    </button>
                                                </template>
                                            </div>

                                            <!-- Comment Body Text -->
                                            <div class="text-sm text-gray-800 whitespace-pre-wrap leading-relaxed break-words" x-text="c.comment"></div>

                                            <!-- Attachment Preview -->
                                            <template x-if="c.attachment_path">
                                                <div class="mt-2.5">
                                                    <template x-if="isImageAttachment(c.attachment_path)">
                                                        <div class="mt-1">
                                                            <a :href="'/storage/' + c.attachment_path" target="_blank" class="inline-block group relative rounded-lg overflow-hidden border border-gray-200 hover:border-blue-400 transition-all shadow-xs">
                                                                <img :src="'/storage/' + c.attachment_path" class="max-h-48 max-w-xs object-cover rounded-lg group-hover:scale-102 transition-transform duration-200" alt="Attachment">
                                                                <span class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-semibold gap-1 transition-opacity">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                    View Image
                                                                </span>
                                                            </a>
                                                        </div>
                                                    </template>
                                                    <template x-if="!isImageAttachment(c.attachment_path)">
                                                        <a :href="'/storage/' + c.attachment_path" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-blue-50 text-gray-700 hover:text-blue-700 border border-gray-200 hover:border-blue-200 rounded-lg text-xs font-medium transition-colors shadow-xs">
                                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                            <span x-text="getFilename(c.attachment_path)"></span>
                                                            <span class="text-[10px] text-gray-400 font-normal">&darr; Download</span>
                                                        </a>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Input Form (Sticky Bottom) -->
                            <div x-show="permissions.canComment" class="border-t border-gray-200 pt-3 bg-white mt-auto">
                                <!-- Attached file badge if selected -->
                                <div x-show="newCommentFile" x-cloak class="mb-2 flex items-center justify-between p-2 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-900">
                                    <div class="flex items-center gap-2 truncate">
                                        <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <span class="font-medium truncate" x-text="newCommentFile?.name"></span>
                                        <span class="text-gray-500 text-[10px]" x-text="formatFileSize(newCommentFile?.size)"></span>
                                    </div>
                                    <button type="button" @click="removeCommentFile()" class="text-gray-400 hover:text-red-600 p-0.5 rounded transition-colors ml-2 shrink-0" title="Remove attachment">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>

                                <div class="relative">
                                    <textarea x-model="newCommentText"
                                              @keydown.ctrl.enter="submitTaskComment()"
                                              @keydown.meta.enter="submitTaskComment()"
                                              placeholder="Write a message or comment for developer / QC team... (Ctrl + Enter to send)"
                                              rows="3"
                                              class="w-full border border-gray-300 rounded-xl shadow-inner focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm px-3.5 py-2.5 outline-none resize-none pr-28 pb-10 transition-colors"></textarea>
                                    
                                    <div class="absolute bottom-2.5 left-3 flex items-center gap-2 text-xs text-gray-400">
                                        <label class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-xs font-medium transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            <span>Lampiran</span>
                                            <input type="file" id="comment_attachment_input" @change="handleCommentFileChange($event)" class="hidden" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt">
                                        </label>
                                        <span class="hidden sm:inline text-[11px] text-gray-400">Ctrl+Enter untuk kirim</span>
                                    </div>

                                    <div class="absolute bottom-2.5 right-3 flex items-center gap-2">
                                        <button type="button" 
                                                @click="submitTaskComment()" 
                                                :disabled="isSubmittingComment || (!newCommentText.trim() && !newCommentFile)"
                                                :class="{'opacity-50 cursor-not-allowed': !newCommentText.trim() && !newCommentFile, 'opacity-75 cursor-wait': isSubmittingComment}"
                                                class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-sm transition-colors">
                                            <svg x-show="isSubmittingComment" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <svg x-show="!isSubmittingComment" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                            <span x-text="isSubmittingComment ? 'Mengirim...' : 'Kirim'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inner Dialog: Run Test Flow -->
    <div x-show="isRunTestOpen" 
         class="fixed inset-0 z-[70] overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            <div x-show="isRunTestOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" 
                 @click="if(!isReportingBug) closeRunTestModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-show="isRunTestOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full border border-gray-200">
                
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <span class="text-xs font-bold text-primary uppercase tracking-wide">Test Execution</span>
                        <h2 class="text-lg font-bold text-gray-900 leading-tight" x-text="activeTest?.title"></h2>
                    </div>
                    <button @click="if(!isReportingBug) closeRunTestModal()" type="button" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="px-6 py-6" x-show="!isReportingBug">
                    <!-- Active Bug Notice -->
                    <template x-if="activeTest?.bug">
                        <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-center justify-between text-xs text-amber-900">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Test Case ini memiliki bug aktif: <strong class="font-mono text-amber-800" x-text="activeTest.bug.code"></strong>. Menekan <strong>PASS</strong> akan otomatis menyelesaikan bug tersebut.</span>
                            </div>
                        </div>
                    </template>

                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-6">
                        <h4 class="text-xs font-bold text-blue-800 uppercase mb-1">Expected Result</h4>
                        <p class="text-sm text-blue-900" x-text="activeTest?.expected"></p>
                    </div>

                    <div class="mb-8">
                        <h4 class="text-sm font-semibold text-gray-800 mb-2">Test Steps (Read-only)</h4>
                        <ol class="list-decimal pl-5 text-sm text-gray-600 space-y-2">
                            <template x-for="(step, index) in activeTest?.steps" :key="index">
                                <li x-text="step" class="pl-1"></li>
                            </template>
                        </ol>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <h4 class="text-center text-sm font-semibold text-gray-600 mb-4 uppercase tracking-wide">Did the actual result match the expected result?</h4>
                        <div class="flex justify-center space-x-6">
                            <button @click="submitTestResult('passed')" :disabled="isSubmittingTest" class="w-36 h-14 bg-green-500 hover:bg-green-600 text-white rounded-lg font-bold text-lg shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                                <svg x-show="!isSubmittingTest" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                <svg x-show="isSubmittingTest" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-show="!isSubmittingTest">PASS</span>
                                <span x-show="isSubmittingTest">SAVING...</span>
                            </button>
                            <button @click="isReportingBug = true" :disabled="isSubmittingTest" class="w-36 h-14 bg-red-500 hover:bg-red-600 text-white rounded-lg font-bold text-lg shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                FAIL
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Report Bug Form -->
                <div class="px-6 py-6" x-show="isReportingBug" x-cloak>
                    <div class="mb-5 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-red-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <h3 class="text-lg font-bold" x-text="activeTest?.bug ? 'Update Bug Report (' + activeTest.bug.code + ')' : 'Report Bug'"></h3>
                        </div>
                        <template x-if="activeTest?.bug">
                            <span class="text-xs font-mono font-bold text-red-700 bg-red-50 border border-red-200 px-2 py-0.5 rounded" x-text="'Linked: ' + activeTest.bug.code"></span>
                        </template>
                    </div>
                    
                    <form @submit.prevent="submitTestResult('failed')">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bug Description <span class="text-red-500">*</span></label>
                                <input type="text" x-model="bugDescription" required placeholder="E.g. Validation message is missing on empty submit" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Actual Result</label>
                                <textarea rows="2" x-model="bugActualResult" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none" placeholder="What actually happened?"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Steps to Reproduce</label>
                                <textarea rows="3" x-model="stepsToReproduce" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none font-mono text-xs"></textarea>
                                <p class="text-xs text-gray-500 mt-1">Pre-filled with test steps. Edit if necessary.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                                    <select x-model="bugSeverity" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                                        <option value="Low">Low</option>
                                        <option value="Medium">Medium</option>
                                        <option value="High">High</option>
                                        <option value="Critical">Critical</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Environment</label>
                                    <input type="text" x-model="bugEnvironment" placeholder="E.g. Chrome, Windows, Staging" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Versi Aplikasi</label>
                                    <input type="text" x-model="bugAppVersion" placeholder="E.g. v1.0.0" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none font-mono">
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 p-4 border border-gray-200 rounded-lg mt-4">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <div class="flex items-center h-5">
                                        <input type="checkbox" x-model="createKanbanTask" class="focus:ring-primary h-4 w-4 text-primary border-gray-300 rounded">
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-semibold text-gray-800">Create a Kanban Task for this Bug</span>
                                        <span class="text-xs text-gray-500">Automatically creates a new task in the 'To Do' column.</span>
                                    </div>
                                </label>
                                
                                <div x-show="createKanbanTask" class="mt-4" x-collapse>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Assign To (Optional)</label>
                                    <select x-model="bugAssigneeId" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                                        <option value="">-- Unassigned --</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Attachment (Optional)</label>
                                <input type="file" id="bug_attachment" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded-md shadow-sm outline-none cursor-pointer">
                                <p class="text-xs text-gray-500 mt-1">PNG, JPG, PDF, DOCX up to 10MB</p>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" @click="isReportingBug = false" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" :disabled="isSubmittingTest" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                                <svg x-show="isSubmittingTest" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="isSubmittingTest ? 'Submitting...' : 'Submit Bug & Fail Test'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- New Task Modal -->
    <div x-show="isNewTaskModalOpen" 
         class="fixed inset-0 z-[60] overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isNewTaskModalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" 
                 @click="closeNewTaskModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-show="isNewTaskModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-gray-200">
                
                <div class="bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title" x-text="editingTaskId ? 'Edit Task' : 'Create New Task'">
                        Create New Task
                    </h3>
                    <button @click="closeNewTaskModal()" type="button" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="px-6 py-4">
                    <form @submit.prevent="submitNewTask">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                                <input type="text" x-model="newTask.title" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea rows="3" x-model="newTask.description" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Assignee</label>
                                <select x-model="newTask.assignee_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                                    <option value="">-- Unassigned --</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select x-model="newTask.column_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                                    <option value="todo">To Do</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="ready_for_qc">Ready for QC</option>
                                    @if(!$isProjectStaffOnly)
                                    <option value="qc_in_progress">QC in Progress</option>
                                    <option value="done">Done</option>
                                    @endif
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Attachment (Optional)</label>
                                <template x-if="editingTaskId && existingAttachment">
                                    <div class="mb-2 flex items-center justify-between text-xs text-blue-700 bg-blue-50 px-2.5 py-1.5 rounded border border-blue-200">
                                        <div class="flex items-center gap-1.5 truncate">
                                            <svg class="w-3.5 h-3.5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            <span class="text-gray-600">Lampiran saat ini:</span>
                                            <a :href="'/storage/' + existingAttachment" target="_blank" class="font-semibold underline hover:text-blue-900 truncate" x-text="existingAttachment.split('/').pop()"></a>
                                        </div>
                                    </div>
                                </template>
                                <input type="file" id="task_attachment" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded-md shadow-sm outline-none cursor-pointer">
                                <p class="text-xs text-gray-500 mt-1">PNG, JPG, PDF, DOCX up to 10MB <span x-show="editingTaskId && existingAttachment" class="text-gray-400">(Kosongkan jika tidak ingin mengganti)</span></p>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" @click="closeNewTaskModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-800" :disabled="isSubmittingTask" :class="{'opacity-50 cursor-not-allowed': isSubmittingTask}">
                                <span x-show="!isSubmittingTask" x-text="editingTaskId ? 'Update Task' : 'Save Task'">Save Task</span>
                                <span x-show="isSubmittingTask" x-text="editingTaskId ? 'Updating...' : 'Saving...'">Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Convert Bugs to Task Modal -->
    <div x-show="isBulkTaskModalOpen" 
         class="fixed inset-0 z-[60] overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isBulkTaskModalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" 
                 @click="isBulkTaskModalOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-show="isBulkTaskModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl w-full border border-gray-200">
                
                <div class="bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg leading-6 font-bold text-gray-900">
                            Create 1 Kanban Task from <span x-text="selectedBugIds.length"></span> Bugs
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">All selected bugs will be merged into 1 new task card on the Kanban Board.</p>
                    </div>
                    <button @click="isBulkTaskModalOpen = false" type="button" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="px-6 py-4">
                    <form @submit.prevent="submitBulkConvert">
                        <div class="space-y-4">
                            <!-- Selected Bugs List Preview -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Selected Bugs List (<span x-text="selectedBugIds.length"></span>)</label>
                                <div class="max-h-36 overflow-y-auto space-y-1.5 p-2 bg-gray-50 rounded-lg border border-gray-200 text-xs">
                                    <template x-for="b in getSelectedBugs()" :key="b.id">
                                        <div class="flex items-center justify-between p-2 bg-white rounded border border-gray-200 shadow-2xs">
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                <span class="font-mono font-bold text-red-700 bg-red-50 border border-red-200 px-1.5 py-0.5 rounded text-[11px] shrink-0" x-text="b.code"></span>
                                                <template x-if="b.test_case">
                                                    <span class="font-mono text-[10px] text-blue-600 bg-blue-50 px-1 rounded shrink-0" x-text="b.test_case.code"></span>
                                                </template>
                                                <span class="text-gray-800 truncate" x-text="b.description"></span>
                                            </div>
                                            <button type="button" @click="toggleBugSelection(b.id)" class="text-gray-400 hover:text-red-500 p-1 shrink-0 ml-2" title="Remove from selection">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kanban Task Title <span class="text-red-500">*</span></label>
                                <input type="text" x-model="bulkTask.title" required placeholder="e.g. Bug fixes collection ..." class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Assignee</label>
                                    <select x-model="bulkTask.assignee_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                                        <option value="">-- Unassigned --</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Initial Column</label>
                                    <select x-model="bulkTask.column_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                                        <option value="todo">To Do</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="ready_for_qc">Ready for QC</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Additional Description</label>
                                <textarea rows="3" x-model="bulkTask.description" placeholder="Fix notes / merged description..." class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none font-mono text-xs"></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" @click="isBulkTaskModalOpen = false" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" :disabled="isSubmittingBulkConvert || selectedBugIds.length === 0" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                                <svg x-show="isSubmittingBulkConvert" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="isSubmittingBulkConvert ? 'Membuat Task...' : 'Buat Kanban Task'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- New Test Case Modal -->
    <div x-show="isNewTestCaseModalOpen" 
         class="fixed inset-0 z-[60] overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isNewTestCaseModalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" 
                 @click="closeNewTestCaseModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-show="isNewTestCaseModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl w-full border border-gray-200">
                
                <div class="bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        <span x-show="isDuplicatingTestCase">Duplicate Test Case</span>
                        <span x-show="!isDuplicatingTestCase && editingTestCaseId">Edit Test Case</span>
                        <span x-show="!isDuplicatingTestCase && !editingTestCaseId" x-text="parentTestCase ? 'Add Sub Test Case' : 'Add Root Test Case'"></span>
                    </h3>
                    <button @click="closeNewTestCaseModal()" type="button" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="px-6 py-4">
                    <template x-if="parentTestCase">
                        <div class="mb-4 p-3 bg-blue-50 border border-blue-100 rounded text-sm">
                            <span class="text-gray-500 font-medium">Parent:</span> 
                            <span class="font-bold text-blue-800" x-text="parentTestCase.code ? (parentTestCase.code + ' - ' + parentTestCase.title) : ('TC #' + parentTestCase.id)"></span>
                        </div>
                    </template>
                    <form @submit.prevent="submitNewTestCase">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="newTestCase.title" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Pre-conditions</label>
                                    <textarea rows="3" x-model="newTestCase.preconditions" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none" placeholder="Requirements before executing..."></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Expected Result</label>
                                    <textarea rows="3" x-model="newTestCase.expected" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none" placeholder="What is the expected outcome?"></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Payload</label>
                                    <textarea rows="4" x-model="newTestCase.payload" class="w-full font-mono border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none" placeholder="JSON or specific data needed for test..."></textarea>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Complexity</label>
                                        <select x-model="newTestCase.complexity" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                                            <option value="Low">Low</option>
                                            <option value="Medium">Medium</option>
                                            <option value="High">High</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                        <select x-model="newTestCase.priority" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                                            <option value="Low">Low</option>
                                            <option value="Medium">Medium</option>
                                            <option value="High">High</option>
                                            <option value="Critical">Critical</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Test Type</label>
                                        <select x-model="newTestCase.test_type" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                                            <option value="Functional">Functional</option>
                                            <option value="UI/UX">UI/UX</option>
                                            <option value="API">API</option>
                                            <option value="Security">Security</option>
                                            <option value="Performance">Performance</option>
                                            <option value="Edge Case">Edge Case</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Automation Status</label>
                                        <select x-model="newTestCase.automation_status" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                                            <option value="Manual">Manual</option>
                                            <option value="Automated">Automated</option>
                                            <option value="Not Automatable">Not Automatable</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Versi Aplikasi</label>
                                    <input type="text" x-model="newTestCase.app_version" placeholder="e.g. v1.0.0, v2.1.0-beta" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none font-mono">
                                </div>

                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <div class="flex justify-between items-center mb-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Test Steps</label>
                                            <p class="text-[11px] text-gray-500">Tarik ikon <span class="font-mono text-gray-600">⋮⋮</span> untuk mengatur ulang urutan langkah</p>
                                        </div>
                                        <button type="button" @click="addStep()" class="text-xs text-primary hover:text-blue-800 font-bold px-2.5 py-1 bg-white border border-gray-300 rounded shadow-sm hover:bg-gray-50 transition-colors">+ Add Step</button>
                                    </div>
                                    <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                                        <template x-for="(step, index) in newTestCase.steps" :key="step.id">
                                            <div class="relative flex items-center gap-2 p-1.5 rounded-lg border bg-white transition-all group"
                                                 :class="{
                                                     'opacity-40 border-dashed border-blue-400 bg-blue-50/50': draggedStepIndex === index,
                                                     'border-blue-500 ring-2 ring-blue-100 shadow-sm': dragOverStepIndex === index && draggedStepIndex !== index,
                                                     'border-gray-200 hover:border-gray-300': draggedStepIndex !== index && dragOverStepIndex !== index
                                                 }"
                                                 :draggable="canDragStep"
                                                 @dragstart="startStepDrag(index, $event)"
                                                 @dragover.prevent="handleStepDragOver(index, $event)"
                                                 @dragleave="handleStepDragLeave(index, $event)"
                                                 @drop="dropStep(index)"
                                                 @dragend="endStepDrag()">
                                                
                                                <!-- Drop line indicator (Top) -->
                                                <div x-show="dragOverStepIndex === index && dragOverStepPosition === 'before' && draggedStepIndex !== index" 
                                                     class="absolute -top-1 left-0 right-0 h-1 bg-blue-500 rounded-full z-20 pointer-events-none"></div>

                                                <!-- Drag Handle -->
                                                <div @mouseenter="canDragStep = true" 
                                                     @mouseleave="canDragStep = false"
                                                     class="cursor-grab active:cursor-grabbing p-1 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded transition-colors select-none shrink-0" 
                                                     title="Tarik untuk memindahkan urutan">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5h.01M9 12h.01M9 19h.01M15 5h.01M15 12h.01M15 19h.01"></path>
                                                    </svg>
                                                </div>

                                                <!-- Number Badge -->
                                                <span class="text-xs font-bold text-gray-500 w-5 shrink-0 text-center font-mono" x-text="(index + 1) + '.'"></span>

                                                <!-- Step Input -->
                                                <input type="text" 
                                                       x-model="step.text" 
                                                       draggable="false"
                                                       class="w-full border-gray-200 rounded-md focus:ring-primary focus:border-primary text-sm px-2.5 py-1.5 border outline-none bg-transparent hover:bg-gray-50/50 focus:bg-white transition-colors" 
                                                       placeholder="Deskripsikan langkah pengujian...">

                                                <!-- Up / Down Nudge Buttons -->
                                                <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                                    <button type="button" 
                                                            @click="moveStepUp(index)" 
                                                            :disabled="index === 0" 
                                                            :class="index === 0 ? 'text-gray-200 cursor-not-allowed' : 'text-gray-400 hover:text-blue-600 hover:bg-blue-50'"
                                                            class="p-1 rounded transition-colors" 
                                                            title="Pindah ke Atas">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"></path></svg>
                                                    </button>
                                                    <button type="button" 
                                                            @click="moveStepDown(index)" 
                                                            :disabled="index === newTestCase.steps.length - 1" 
                                                            :class="index === newTestCase.steps.length - 1 ? 'text-gray-200 cursor-not-allowed' : 'text-gray-400 hover:text-blue-600 hover:bg-blue-50'"
                                                            class="p-1 rounded transition-colors" 
                                                            title="Pindah ke Bawah">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                                                    </button>
                                                </div>

                                                <!-- Remove Step Button -->
                                                <button type="button" 
                                                        @click="removeStep(index)" 
                                                        class="text-gray-300 hover:text-red-600 p-1 hover:bg-red-50 rounded opacity-0 group-hover:opacity-100 transition-opacity shrink-0" 
                                                        x-show="newTestCase.steps.length > 1" 
                                                        title="Hapus Langkah">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>

                                                <!-- Drop line indicator (Bottom) -->
                                                <div x-show="dragOverStepIndex === index && dragOverStepPosition === 'after' && draggedStepIndex !== index" 
                                                     class="absolute -bottom-1 left-0 right-0 h-1 bg-blue-500 rounded-full z-20 pointer-events-none"></div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" @click="closeNewTestCaseModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-800" :disabled="isSubmittingTestCase" :class="{'opacity-50 cursor-not-allowed': isSubmittingTestCase}">
                                <span x-show="!isSubmittingTestCase" x-text="editingTestCaseId ? 'Update Test Case' : (isDuplicatingTestCase ? 'Duplicate Test Case' : 'Save Test Case')"></span>
                                <span x-show="isSubmittingTestCase">Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Test Case Details Modal -->
    <div x-show="isViewTestCaseModalOpen" 
         class="fixed inset-0 z-[60] overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isViewTestCaseModalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" 
                 @click="closeViewTestCaseModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-show="isViewTestCaseModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full border border-gray-200">
                
                <div class="bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-start">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold text-primary bg-blue-50 px-2 py-0.5 rounded border border-blue-100" x-text="viewingTestCase?.code"></span>
                            <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded" 
                                  :class="{
                                    'bg-green-100 text-green-700 border border-green-200': viewingTestCase?.status === 'passed',
                                    'bg-red-100 text-red-700 border border-red-200': viewingTestCase?.status === 'failed',
                                    'bg-gray-100 text-gray-500 border border-gray-200': viewingTestCase?.status === 'pending'
                                  }" x-text="viewingTestCase?.status"></span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mt-1" x-text="viewingTestCase?.title"></h3>
                    </div>
                    <button @click="closeViewTestCaseModal()" type="button" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none mt-1">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="px-6 py-6 space-y-6">
                    <!-- Riwayat Bug Tracker / Defect History -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Riwayat Bug Tracker
                            </h4>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full" 
                                  :class="(viewingTestCase?.bugs && viewingTestCase.bugs.length > 0) ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                                  x-text="(viewingTestCase?.bugs ? viewingTestCase.bugs.length : 0) + ' Bug Tercatat'"></span>
                        </div>

                        <template x-if="viewingTestCase?.bugs && viewingTestCase.bugs.length > 0">
                            <div class="space-y-2.5 max-h-60 overflow-y-auto pr-1">
                                <template x-for="b in viewingTestCase.bugs" :key="b.id">
                                    <div class="p-3 rounded-lg border text-xs flex flex-col gap-1.5 transition-all"
                                         :class="b.status === 'resolved' ? 'bg-green-50/50 border-green-200' : 'bg-red-50/60 border-red-200'">
                                        <div class="flex items-center justify-between flex-wrap gap-2">
                                            <div class="flex items-center gap-2">
                                                <button type="button"
                                                        @click.stop="openViewBugModal(b)"
                                                        class="font-mono font-bold px-2 py-0.5 rounded border cursor-pointer hover:opacity-80 transition-opacity"
                                                        :class="b.status === 'resolved' ? 'bg-green-100 text-green-800 border-green-300' : 'bg-red-100 text-red-800 border-red-300'"
                                                        title="Klik untuk melihat detail lengkap bug"
                                                        x-text="b.code"></button>
                                                <span class="inline-flex items-center text-[10px] font-bold uppercase px-1.5 py-0.2 rounded border"
                                                      :class="{
                                                        'bg-green-100 text-green-700 border-green-200': b.status === 'resolved',
                                                        'bg-red-100 text-red-700 border-red-200': b.status === 'open',
                                                        'bg-amber-100 text-amber-700 border-amber-200': b.status === 'in_progress'
                                                      }" x-text="b.status"></span>
                                                <span class="inline-flex items-center text-[10px] font-bold uppercase px-1.5 py-0.2 rounded"
                                                      :class="{
                                                        'bg-red-100 text-red-700': b.severity === 'Critical' || b.severity === 'High',
                                                        'bg-yellow-100 text-yellow-700': b.severity === 'Medium',
                                                        'bg-green-100 text-green-700': b.severity === 'Low'
                                                      }" x-text="b.severity || 'Medium'"></span>
                                            </div>
                                            <div class="text-[11px] text-gray-500">
                                                <span x-text="'Dilaporkan: ' + (b.created_at || 'Unknown')"></span>
                                                <template x-if="b.status === 'resolved' && b.updated_at">
                                                    <span class="text-green-700 font-semibold ml-1.5" x-text="'(Solved: ' + b.updated_at + ')'"></span>
                                                </template>
                                            </div>
                                        </div>
                                        <p class="text-gray-800 font-medium whitespace-pre-wrap" x-text="b.description"></p>
                                        <template x-if="b.actual_result">
                                            <div class="bg-white/80 p-2 rounded border border-gray-200 text-gray-700">
                                                <strong class="text-gray-900">Actual Result:</strong> <span x-text="b.actual_result"></span>
                                            </div>
                                        </template>
                                        <div class="flex items-center justify-between text-[11px] text-gray-500 pt-1">
                                            <template x-if="b.project_task">
                                                <div class="flex items-center gap-1 cursor-pointer text-blue-600 hover:text-blue-800" @click="openTaskModalById(b.project_task.id)">
                                                    <span>Terkait Task:</span>
                                                    <span class="font-mono font-bold underline" x-text="b.project_task.code"></span>
                                                    <span class="text-gray-500" x-text="'(' + b.project_task.title + ')'"></span>
                                                </div>
                                            </template>
                                            <template x-if="!b.project_task">
                                                <span class="italic text-gray-400">Belum ada Kanban task</span>
                                            </template>
                                            <div class="flex items-center gap-2">
                                                <template x-if="b.attachment_path">
                                                    <a :href="'/storage/' + b.attachment_path" target="_blank" class="text-blue-600 hover:underline flex items-center gap-0.5">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                                        Lampiran
                                                    </a>
                                                </template>
                                                <button type="button" @click.stop="openViewBugModal(b)" class="text-xs text-blue-600 hover:text-blue-800 font-semibold underline inline-flex items-center gap-0.5">
                                                    Lihat Detail &rarr;
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        
                        <template x-if="!viewingTestCase?.bugs || viewingTestCase.bugs.length === 0">
                            <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-xs text-green-800 flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Tidak ada riwayat bug pada Test Case ini (Clean).</span>
                            </div>
                        </template>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center text-xs font-semibold px-2 py-1 rounded bg-purple-50 text-purple-700 border-purple-200 border" x-show="viewingTestCase?.app_version">
                            <span class="mr-1 opacity-75">Versi:</span> <span class="font-mono" x-text="viewingTestCase?.app_version"></span>
                        </span>
                        <span class="inline-flex items-center text-xs font-semibold px-2 py-1 rounded bg-gray-100 text-gray-700" x-show="viewingTestCase?.test_type">
                            <span class="mr-1 font-normal text-gray-500">Type:</span> <span x-text="viewingTestCase?.test_type"></span>
                        </span>
                        <span class="inline-flex items-center text-xs font-semibold px-2 py-1 rounded" 
                              :class="{
                                'bg-red-50 text-red-700 border-red-200 border': viewingTestCase?.priority === 'Critical',
                                'bg-orange-50 text-orange-700 border-orange-200 border': viewingTestCase?.priority === 'High',
                                'bg-yellow-50 text-yellow-700 border-yellow-200 border': viewingTestCase?.priority === 'Medium',
                                'bg-green-50 text-green-700 border-green-200 border': viewingTestCase?.priority === 'Low'
                              }"
                              x-show="viewingTestCase?.priority">
                            <span class="mr-1 opacity-75">Priority:</span> <span x-text="viewingTestCase?.priority"></span>
                        </span>
                        <span class="inline-flex items-center text-xs font-semibold px-2 py-1 rounded bg-blue-50 text-blue-700 border-blue-200 border" x-show="viewingTestCase?.complexity">
                            <span class="mr-1 opacity-75">Complexity:</span> <span x-text="viewingTestCase?.complexity"></span>
                        </span>
                        <span class="inline-flex items-center text-xs font-semibold px-2 py-1 rounded bg-purple-50 text-purple-700 border-purple-200 border" x-show="viewingTestCase?.automation_status">
                            <span class="mr-1 opacity-75">Automation:</span> <span x-text="viewingTestCase?.automation_status"></span>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-800 mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                Pre-conditions
                            </h4>
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 text-sm text-gray-700 whitespace-pre-wrap" x-text="viewingTestCase?.preconditions || 'No pre-conditions specified.'"></div>
                        </div>

                        <div>
                            <h4 class="text-sm font-semibold text-gray-800 mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Expected Result
                            </h4>
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 text-sm text-blue-900 whitespace-pre-wrap" x-text="viewingTestCase?.expected || 'No expected result specified.'"></div>
                        </div>
                    </div>

                    <div x-show="viewingTestCase?.payload">
                        <h4 class="text-sm font-semibold text-gray-800 mb-2 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                            Data Payload
                        </h4>
                        <div class="bg-gray-900 p-4 rounded-lg border border-gray-700 text-sm text-green-400 font-mono whitespace-pre-wrap overflow-x-auto" x-text="viewingTestCase?.payload"></div>
                    </div>

                    <div x-show="viewingTestCase?.steps && viewingTestCase.steps.length > 0">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">Test Steps</h4>
                        <div class="space-y-3">
                            <template x-for="(step, index) in viewingTestCase?.steps" :key="index">
                                <div class="flex items-start gap-3 bg-white p-3 border border-gray-100 rounded-lg shadow-sm">
                                    <div class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-700 font-bold text-xs shrink-0" x-text="index + 1"></div>
                                    <div class="text-sm text-gray-700 mt-0.5" x-text="step"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
                    <button @click="closeViewTestCaseModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Bug Details Modal -->
    <div x-show="isViewBugModalOpen" 
         class="fixed inset-0 z-[70] overflow-y-auto" 
         aria-labelledby="bug-modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isViewBugModalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" 
                 @click="closeViewBugModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-show="isViewBugModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full border border-gray-200">
                
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-red-50/70 via-white to-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-start">
                    <div>
                        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                            <span class="text-xs font-mono font-bold px-2.5 py-0.5 rounded border"
                                  :class="viewingBug?.status === 'resolved' ? 'bg-green-100 text-green-800 border-green-300' : 'bg-red-100 text-red-800 border-red-300'"
                                  x-text="viewingBug?.code"></span>
                            
                            <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border"
                                  :class="{
                                      'bg-green-100 text-green-700 border-green-200': viewingBug?.status === 'resolved',
                                      'bg-red-100 text-red-700 border-red-200': viewingBug?.status === 'open',
                                      'bg-amber-100 text-amber-700 border-amber-200': viewingBug?.status === 'in_progress'
                                  }"
                                  x-text="viewingBug?.status"></span>

                            <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border border-purple-200 bg-purple-50 text-purple-700 font-mono" 
                                  x-show="viewingBug?.app_version"
                                  x-text="'Versi: ' + viewingBug.app_version"></span>

                            <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border" 
                                  :class="{
                                      'bg-red-50 text-red-700 border-red-200': viewingBug?.severity === 'Critical' || viewingBug?.severity === 'High',
                                      'bg-yellow-50 text-yellow-700 border-yellow-200': viewingBug?.severity === 'Medium',
                                      'bg-green-50 text-green-700 border-green-200': viewingBug?.severity === 'Low'
                                  }" 
                                  x-text="'Severity: ' + (viewingBug?.severity || 'Medium')"></span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900" id="bug-modal-title">Detail Deskripsi Bug</h3>
                    </div>
                    <button @click="closeViewBugModal()" type="button" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none p-1">
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-5 space-y-5 max-h-[75vh] overflow-y-auto">
                    <!-- Deskripsi Bug (Primary Focus) -->
                    <div>
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Deskripsi Masalah / Bug
                        </h4>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm text-gray-900 leading-relaxed whitespace-pre-wrap font-sans" x-text="viewingBug?.description || 'Tidak ada deskripsi.'"></div>
                    </div>

                    <!-- Steps to Reproduce (if any) -->
                    <div x-show="viewingBug?.steps_to_reproduce && (Array.isArray(viewingBug.steps_to_reproduce) ? viewingBug.steps_to_reproduce.length > 0 : (viewingBug.steps_to_reproduce + '').trim() !== '')">
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            Langkah untuk Mereproduksi (Steps to Reproduce)
                        </h4>
                        <template x-if="Array.isArray(viewingBug?.steps_to_reproduce)">
                            <div class="space-y-2">
                                <template x-for="(step, sIdx) in viewingBug.steps_to_reproduce" :key="sIdx">
                                    <div class="flex items-start gap-3 bg-amber-50/50 p-2.5 rounded-lg border border-amber-200/80">
                                        <div class="flex items-center justify-center w-5 h-5 rounded-full bg-amber-200 text-amber-800 font-bold text-xs shrink-0 font-mono" x-text="sIdx + 1"></div>
                                        <div class="text-xs text-amber-950 font-medium leading-relaxed mt-0.5" x-text="typeof step === 'object' && step !== null ? (step.text || '') : step"></div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!Array.isArray(viewingBug?.steps_to_reproduce)">
                            <div class="bg-amber-50/50 p-4 rounded-lg border border-amber-200/80 text-sm text-amber-950 whitespace-pre-wrap leading-relaxed" x-text="viewingBug?.steps_to_reproduce"></div>
                        </template>
                    </div>

                    <!-- Expected Result vs Actual Result -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div x-show="viewingBug?.test_case?.expected">
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Expected Result (Hasil yang Diharapkan)
                            </h4>
                            <div class="bg-emerald-50/60 p-3.5 rounded-lg border border-emerald-200 text-xs text-emerald-900 whitespace-pre-wrap" x-text="viewingBug?.test_case?.expected"></div>
                        </div>

                        <div :class="{'md:col-span-2': !viewingBug?.test_case?.expected}" x-show="viewingBug?.actual_result">
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Actual Result (Hasil yang Terjadi)
                            </h4>
                            <div class="bg-red-50/60 p-3.5 rounded-lg border border-red-200 text-xs text-red-900 whitespace-pre-wrap" x-text="viewingBug?.actual_result"></div>
                        </div>
                    </div>

                    <!-- Environment & Versi Aplikasi -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="viewingBug?.environment || viewingBug?.app_version">
                        <div x-show="viewingBug?.environment">
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Environment / Lingkungan Uji
                            </h4>
                            <div class="text-xs text-gray-700 bg-gray-100/80 px-3 py-2 rounded border border-gray-200 font-mono" x-text="viewingBug?.environment"></div>
                        </div>
                        <div x-show="viewingBug?.app_version">
                            <h4 class="text-xs font-bold text-purple-700 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                Versi Aplikasi
                            </h4>
                            <div class="text-xs text-purple-800 bg-purple-50 px-3 py-2 rounded border border-purple-200 font-mono font-semibold" x-text="viewingBug?.app_version"></div>
                        </div>
                    </div>

                    <!-- Relations: Test Case & Kanban Task -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-gray-100">
                        <!-- Test Case Relation -->
                        <div class="p-3 rounded-lg border bg-gray-50/80 border-gray-200">
                            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Sumber Test Case</span>
                            <template x-if="viewingBug?.test_case">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-mono text-xs font-bold text-blue-700" x-text="viewingBug.test_case.code"></span>
                                        <p class="text-xs text-gray-800 font-medium truncate max-w-[200px]" x-text="viewingBug.test_case.title"></p>
                                    </div>
                                    <button type="button" @click="closeViewBugModal(); openViewTestCaseModal(viewingBug.test_case)" class="text-xs text-blue-600 hover:text-blue-800 font-medium underline">
                                        Buka &rarr;
                                    </button>
                                </div>
                            </template>
                            <template x-if="!viewingBug?.test_case">
                                <span class="text-xs text-gray-400 italic">Tidak terhubung ke Test Case</span>
                            </template>
                        </div>

                        <!-- Kanban Task Relation -->
                        <div class="p-3 rounded-lg border bg-gray-50/80 border-gray-200">
                            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Status Kanban Task</span>
                            <template x-if="viewingBug?.project_task">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-mono text-xs font-bold text-blue-700" x-text="viewingBug.project_task.code"></span>
                                        <p class="text-xs text-gray-800 font-medium truncate max-w-[200px]" x-text="viewingBug.project_task.title"></p>
                                    </div>
                                    <button type="button" @click="closeViewBugModal(); openTaskModalById(viewingBug.project_task.id)" class="text-xs text-blue-600 hover:text-blue-800 font-medium underline">
                                        Buka &rarr;
                                    </button>
                                </div>
                            </template>
                            <template x-if="!viewingBug?.project_task">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-orange-600 font-medium">Belum ditugaskan ke task</span>
                                    <template x-if="viewingBug?.status !== 'resolved'">
                                        <button type="button" @click="convertBugToTask(viewingBug.id); closeViewBugModal();" class="text-xs bg-blue-600 hover:bg-blue-700 text-white font-medium px-2 py-1 rounded shadow-xs">
                                            + Buat Task
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Attachment / Lampiran -->
                    <template x-if="viewingBug?.attachment_path">
                        <div class="p-4 rounded-lg border border-blue-100 bg-blue-50/40">
                            <h4 class="text-xs font-bold text-blue-900 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                Lampiran Bukti Bug
                            </h4>
                            <!-- Image preview if image -->
                            <template x-if="isImageAttachment(viewingBug.attachment_path)">
                                <div class="space-y-2">
                                    <a :href="'/storage/' + viewingBug.attachment_path" target="_blank" title="Klik untuk memperbesar gambar">
                                        <img :src="'/storage/' + viewingBug.attachment_path" class="max-h-64 rounded border border-gray-200 shadow-xs hover:opacity-95 transition-opacity object-contain bg-white">
                                    </a>
                                    <a :href="'/storage/' + viewingBug.attachment_path" target="_blank" class="text-xs text-blue-600 hover:underline font-medium inline-flex items-center gap-1">
                                        <span>Buka gambar di tab baru &rarr;</span>
                                    </a>
                                </div>
                            </template>
                            <!-- Non-image download link -->
                            <template x-if="!isImageAttachment(viewingBug.attachment_path)">
                                <a :href="'/storage/' + viewingBug.attachment_path" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50 shadow-xs">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    <span x-text="getFilename(viewingBug.attachment_path)"></span>
                                </a>
                            </template>
                        </div>
                    </template>

                    <!-- Timestamp Footer Details -->
                    <div class="text-[11px] text-gray-500 flex justify-between items-center pt-2 border-t border-gray-100">
                        <span x-text="'Dilaporkan: ' + (viewingBug?.created_at_human || viewingBug?.created_at || 'Unknown')"></span>
                        <template x-if="viewingBug?.status === 'resolved' && viewingBug?.updated_at">
                            <span class="text-green-700 font-semibold" x-text="'Diselesaikan: ' + viewingBug.updated_at"></span>
                        </template>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-6 py-3.5 border-t border-gray-200 flex justify-between items-center">
                    <div>
                        <template x-if="viewingBug?.project_task">
                            <button type="button" @click="closeViewBugModal(); openTaskModalById(viewingBug.project_task.id)" class="px-3.5 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 hover:bg-blue-100 rounded-md transition-colors inline-flex items-center gap-1.5">
                                <span>Lihat Kanban Task</span>
                                <span class="font-mono text-[10px]" x-text="'(' + viewingBug.project_task.code + ')'"></span>
                            </button>
                        </template>
                        <template x-if="!viewingBug?.project_task && viewingBug?.status !== 'resolved' && permissions.canManageBugs">
                            <button type="button" @click="convertBugToTask(viewingBug.id); closeViewBugModal();" class="px-3.5 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-md shadow-xs transition-colors inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                <span>Buat Task Baru di Kanban</span>
                            </button>
                        </template>
                    </div>
                    <div class="flex items-center gap-2">
                        <template x-if="permissions.canManageBugs && viewingBug">
                            <button type="button" 
                                    @click="closeViewBugModal(); openEditBugModal(viewingBug);" 
                                    class="px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-md text-sm font-semibold transition-colors inline-flex items-center gap-1.5 shadow-xs">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span>Edit Bug</span>
                            </button>
                        </template>
                        <button @click="closeViewBugModal()" type="button" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition-colors">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Bug Modal -->
    <div x-show="isEditBugModalOpen" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="edit-bug-modal-title" 
         role="dialog" 
         aria-modal="true" 
         x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="isEditBugModalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 @click="closeEditBugModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Content -->
            <div x-show="isEditBugModalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                
                <form @submit.prevent="submitEditBug()">
                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-red-50 to-amber-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-red-100 border border-red-200 flex items-center justify-center text-red-600 shadow-xs shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-red-100 text-red-800 border border-red-200" x-text="editingBug?.code"></span>
                                    <template x-if="editingBug?.test_case">
                                        <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200" x-text="'TC: ' + editingBug.test_case.code"></span>
                                    </template>
                                </div>
                                <h3 class="text-base font-bold text-gray-900 mt-0.5" id="edit-bug-modal-title">Edit Bug Report</h3>
                            </div>
                        </div>
                        <button @click="closeEditBugModal()" type="button" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none p-1">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-5 space-y-4 max-h-[72vh] overflow-y-auto">
                        <!-- Alert notice about table isolation -->
                        <div class="bg-blue-50/60 p-3 rounded-lg border border-blue-200 flex items-start gap-2.5 text-xs text-blue-900">
                            <svg class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Perubahan pada bug report ini hanya disimpan di tabel defect/bug (<span class="font-mono font-semibold">task_bugs</span>) dan tidak akan mengubah langkah ataupun data pada test case asal.</span>
                        </div>

                        <!-- Bug Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Bug <span class="text-red-500">*</span></label>
                            <input type="text" 
                                   x-model="editingBug.description" 
                                   required 
                                   placeholder="Contoh: Error 500 saat klik tombol submit form checkout" 
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                        </div>

                        <!-- Actual Result -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Actual Result (Hasil yang Terjadi)</label>
                            <textarea rows="2" 
                                      x-model="editingBug.actual_result" 
                                      class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none" 
                                      placeholder="Apa yang sebenarnya terjadi di sistem?"></textarea>
                        </div>

                        <!-- Steps to Reproduce (Interactive dynamic steps like Test Case) -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <div class="flex justify-between items-center mb-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Langkah Reproduksi (Steps to Reproduce)</label>
                                    <p class="text-[11px] text-gray-500">Tarik ikon <span class="font-mono text-gray-600">⋮⋮</span> atau gunakan tombol panah untuk mengatur ulang urutan langkah</p>
                                </div>
                                <button type="button" 
                                        @click="addBugStep()" 
                                        class="text-xs text-primary hover:text-blue-800 font-bold px-2.5 py-1 bg-white border border-gray-300 rounded shadow-sm hover:bg-gray-50 transition-colors">
                                    + Add Step
                                </button>
                            </div>

                            <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                                <template x-for="(step, index) in editingBug?.steps_to_reproduce" :key="step.id">
                                    <div class="relative flex items-center gap-2 p-1.5 rounded-lg border bg-white transition-all group"
                                         :class="{
                                             'opacity-40 border-dashed border-red-400 bg-red-50/50': draggedBugStepIndex === index,
                                             'border-red-500 ring-2 ring-red-100 shadow-sm': dragOverBugStepIndex === index && draggedBugStepIndex !== index,
                                             'border-gray-200 hover:border-gray-300': draggedBugStepIndex !== index && dragOverBugStepIndex !== index
                                         }"
                                         :draggable="canDragBugStep"
                                         @dragstart="startBugStepDrag(index, $event)"
                                         @dragover.prevent="handleBugStepDragOver(index, $event)"
                                         @dragleave="handleBugStepDragLeave(index, $event)"
                                         @drop="dropBugStep(index)"
                                         @dragend="endBugStepDrag()">
                                        
                                        <!-- Drop line indicator (Top) -->
                                        <div x-show="dragOverBugStepIndex === index && dragOverBugStepPosition === 'before' && draggedBugStepIndex !== index" 
                                             class="absolute -top-1 left-0 right-0 h-1 bg-red-500 rounded-full z-20 pointer-events-none"></div>

                                        <!-- Drag Handle -->
                                        <div @mouseenter="canDragBugStep = true" 
                                             @mouseleave="canDragBugStep = false"
                                             class="cursor-grab active:cursor-grabbing p-1 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded transition-colors select-none shrink-0" 
                                             title="Tarik untuk memindahkan urutan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5h.01M9 12h.01M9 19h.01M15 5h.01M15 12h.01M15 19h.01"></path>
                                            </svg>
                                        </div>

                                        <!-- Number Badge -->
                                        <span class="text-xs font-bold text-gray-500 w-5 shrink-0 text-center font-mono" x-text="(index + 1) + '.'"></span>

                                        <!-- Step Input -->
                                        <input type="text" 
                                               x-model="step.text" 
                                               draggable="false"
                                               class="w-full border-gray-200 rounded-md focus:ring-primary focus:border-primary text-sm px-2.5 py-1.5 border outline-none bg-transparent hover:bg-gray-50/50 focus:bg-white transition-colors" 
                                               placeholder="Deskripsikan langkah reproduksi...">

                                        <!-- Up / Down Nudge Buttons -->
                                        <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                            <button type="button" 
                                                    @click="moveBugStepUp(index)" 
                                                    :disabled="index === 0" 
                                                    :class="index === 0 ? 'text-gray-200 cursor-not-allowed' : 'text-gray-400 hover:text-blue-600 hover:bg-blue-50'"
                                                    class="p-1 rounded transition-colors" 
                                                    title="Pindah ke Atas">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"></path></svg>
                                            </button>
                                            <button type="button" 
                                                    @click="moveBugStepDown(index)" 
                                                    :disabled="index === editingBug.steps_to_reproduce.length - 1" 
                                                    :class="index === editingBug.steps_to_reproduce.length - 1 ? 'text-gray-200 cursor-not-allowed' : 'text-gray-400 hover:text-blue-600 hover:bg-blue-50'"
                                                    class="p-1 rounded transition-colors" 
                                                    title="Pindah ke Bawah">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                                            </button>
                                        </div>

                                        <!-- Remove Step Button -->
                                        <button type="button" 
                                                @click="removeBugStep(index)" 
                                                class="text-gray-300 hover:text-red-600 p-1 hover:bg-red-50 rounded opacity-0 group-hover:opacity-100 transition-opacity shrink-0" 
                                                x-show="editingBug.steps_to_reproduce.length > 1" 
                                                title="Hapus Langkah">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>

                                        <!-- Drop line indicator (Bottom) -->
                                        <div x-show="dragOverBugStepIndex === index && dragOverBugStepPosition === 'after' && draggedBugStepIndex !== index" 
                                             class="absolute -bottom-1 left-0 right-0 h-1 bg-red-500 rounded-full z-20 pointer-events-none"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Severity, Environment, Versi, Status -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                                <select x-model="editingBug.severity" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                    <option value="Critical">Critical</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status Bug</label>
                                <select x-model="editingBug.status" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                                    <option value="open">Open</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Environment</label>
                                <input type="text" 
                                       x-model="editingBug.environment" 
                                       placeholder="Contoh: Chrome / Windows 11" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Versi Aplikasi</label>
                                <input type="text" 
                                       x-model="editingBug.app_version" 
                                       placeholder="Contoh: v1.0.0" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none font-mono">
                            </div>
                        </div>

                        <!-- Attachment / Lampiran -->
                        <div class="p-3.5 bg-gray-50 rounded-lg border border-gray-200">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Lampiran Bukti Masalah (Screenshot / File)</label>
                            
                            <template x-if="editingBug?.attachment_path && !editingBug.remove_attachment">
                                <div class="mb-3 p-2.5 bg-white border border-gray-200 rounded-md flex items-center justify-between">
                                    <div class="flex items-center gap-2 overflow-hidden">
                                        <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                        <a :href="'/storage/' + editingBug.attachment_path" target="_blank" class="text-xs text-blue-600 hover:underline font-mono truncate" x-text="getFilename(editingBug.attachment_path)"></a>
                                    </div>
                                    <label class="flex items-center gap-1.5 text-xs text-red-600 hover:text-red-800 cursor-pointer font-medium shrink-0 ml-2">
                                        <input type="checkbox" x-model="editingBug.remove_attachment" class="rounded border-gray-300 text-red-600 focus:ring-red-500 h-3.5 w-3.5">
                                        <span>Hapus file ini</span>
                                    </label>
                                </div>
                            </template>

                            <template x-if="editingBug?.remove_attachment">
                                <div class="mb-2 text-xs text-red-600 italic">Lampiran saat ini akan dihapus saat disimpan.</div>
                            </template>

                            <input type="file" 
                                   id="edit_bug_attachment" 
                                   class="block w-full text-xs text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer">
                            <p class="text-[11px] text-gray-500 mt-1">Format: JPG, PNG, GIF, PDF, DOCX, XLSX (Maks. 10MB). Unggah file baru untuk mengganti lampiran.</p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-gray-50 px-6 py-3.5 border-t border-gray-200 flex justify-end gap-2">
                        <button type="button" 
                                @click="closeEditBugModal()" 
                                class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition-colors">
                            Batal
                        </button>
                        <button type="submit" 
                                :disabled="isSubmittingBug" 
                                :class="{'opacity-75 cursor-wait': isSubmittingBug}" 
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium shadow-sm transition-colors inline-flex items-center gap-2">
                            <svg x-show="isSubmittingBug" class="animate-spin -ml-0.5 mr-1.5 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="isSubmittingBug ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div x-show="errorMessage" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-x-8"
         x-transition:enter-end="opacity-100 transform translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-x-0"
         x-transition:leave-end="opacity-0 transform translate-x-8"
         class="fixed top-4 right-4 z-[100] max-w-sm w-full bg-red-50 border-l-4 border-red-500 rounded-r shadow-lg flex items-start p-4" x-cloak>
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3 w-0 flex-1 pt-0.5">
            <p class="text-sm font-medium text-red-800">Error</p>
            <p class="mt-1 text-sm text-red-700" x-text="errorMessage"></p>
        </div>
        <div class="ml-4 flex-shrink-0 flex">
            <button @click="errorMessage = ''" class="bg-red-50 rounded-md inline-flex text-red-500 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                <span class="sr-only">Close</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Success Toast Notification -->
    <div x-show="successMessage" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-x-8"
         x-transition:enter-end="opacity-100 transform translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-x-0"
         x-transition:leave-end="opacity-0 transform translate-x-8"
         class="fixed top-4 right-4 z-[100] max-w-sm w-full bg-green-50 border-l-4 border-green-500 rounded-r shadow-lg flex items-start p-4" x-cloak>
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3 w-0 flex-1 pt-0.5">
            <p class="text-sm font-medium text-green-800">Berhasil</p>
            <p class="mt-1 text-sm text-green-700" x-text="successMessage"></p>
        </div>
        <div class="ml-4 flex-shrink-0 flex">
            <button @click="successMessage = ''" class="bg-green-50 rounded-md inline-flex text-green-500 hover:text-green-600 focus:outline-none">
                <span class="sr-only">Close</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
function qcDashboard() {
    return {
        permissions: {
            canManageTasks: {{ auth()->user()->can('qc.manage_tasks', $project) ? 'true' : 'false' }},
            canManageTestCases: {{ auth()->user()->can('qc.manage_test_cases', $project) ? 'true' : 'false' }},
            canExecuteTests: {{ auth()->user()->can('qc.execute_tests', $project) ? 'true' : 'false' }},
            canManageBugs: {{ auth()->user()->can('qc.manage_bugs', $project) ? 'true' : 'false' }},
            canComment: {{ (auth()->user()->can('qc.comments', $project) || auth()->user()->can('qc.view', $project) || auth()->user()->can('projects.qc', $project) || auth()->user()->isInternal()) ? 'true' : 'false' }},
            isStaffOnly: {{ $isProjectStaffOnly ? 'true' : 'false' }},
        },
        columns: [
            { id: 'todo', title: 'To Do' },
            { id: 'in_progress', title: 'In Progress' },
            { id: 'ready_for_qc', title: 'Ready for QC' },
            { id: 'qc_in_progress', title: 'QC in Progress' },
            { id: 'done', title: 'Done' }
        ],
        tasks: [],
        
        isKanbanExpanded: true,
        isTestCasesExpanded: true,
        isBugsExpanded: true,
        
        projectBugs: [],
        bugFilterTab: 'active', // Default: 'active' (Bug Aktif), 'solved', 'all'
        bugFilters: {
            details: '',
            version: '',
            severity: '',
            status: '',
            testCase: '',
            task: '',
        },
        selectedBugIds: [],
        isBulkTaskModalOpen: false,
        isSubmittingBulkConvert: false,
        bulkTask: {
            title: '',
            description: '',
            assignee_id: '',
            column_id: 'todo'
        },
        isConvertingBug: false,
        convertingBugId: null,
        isPassingAllTests: false,
        
        isTaskModalOpen: false,
        activeTask: null,
        activeTab: 'details',
        activeTaskComments: [],
        isLoadingComments: false,
        newCommentText: '',
        newCommentFile: null,
        isSubmittingComment: false,
        deletingCommentId: null,
        
        isRunTestOpen: false,
        activeTest: null,
        isReportingBug: false,
        
        bugDescription: '',
        stepsToReproduce: '',
        bugSeverity: 'Medium',
        bugActualResult: '',
        bugEnvironment: '',
        bugAppVersion: '',
        createKanbanTask: false,
        isSubmittingTest: false,
        bugAssigneeId: '',

        projectId: '{{ $project->id }}',

        isNewTaskModalOpen: false,
        editingTaskId: null,
        existingAttachment: null,
        isSubmittingTask: false,
        isMovingTask: false,
        movingToColumn: null,
        newTask: {
            title: '',
            description: '',
            assignee_id: '',
            column_id: 'todo'
        },

        // Test Case Modal State
        isNewTestCaseModalOpen: false,
        isSubmittingTestCase: false,
        editingTestCaseId: null,
        isDuplicatingTestCase: false,
        parentTestCase: null,
        newTestCase: {
            title: '',
            preconditions: '',
            expected: '',
            steps: [{ id: 'step_init_1', text: '' }],
            payload: '',
            complexity: 'Low',
            priority: 'Medium',
            test_type: 'Functional',
            automation_status: 'Manual',
            app_version: ''
        },

        // Test Step Drag & Drop State
        draggedStepIndex: null,
        dragOverStepIndex: null,
        dragOverStepPosition: null,
        canDragStep: false,

        // View Test Case State
        isViewTestCaseModalOpen: false,
        viewingTestCase: null,

        // View Bug State
        isViewBugModalOpen: false,
        viewingBug: null,

        // Edit Bug State
        isEditBugModalOpen: false,
        isSubmittingBug: false,
        editingBug: null,
        draggedBugStepIndex: null,
        dragOverBugStepIndex: null,
        dragOverBugStepPosition: null,
        canDragBugStep: false,
        
        // Error & Notification State
        errorMessage: '',
        successMessage: '',
        isSendingEmail: false,

        async sendSummaryEmail() {
            if (!confirm('Apakah Anda yakin ingin mengirimkan ringkasan metrik QA/QC proyek ini ke seluruh anggota dan kolaborator melalui email?')) {
                return;
            }

            this.isSendingEmail = true;
            try {
                const response = await fetch(`/api/projects/${this.projectId}/qc/send-summary-email`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showSuccess(data.message || 'Ringkasan QA/QC berhasil dijadwalkan untuk dikirim.');
                } else {
                    this.showError(data.message || 'Gagal mengirimkan ringkasan email.');
                }
            } catch (error) {
                console.error('Error sending summary email:', error);
                this.showError('Terjadi kendala jaringan saat menghubungi server.');
            } finally {
                this.isSendingEmail = false;
            }
        },

        showError(msg) {
            this.errorMessage = msg;
            setTimeout(() => {
                if (this.errorMessage === msg) {
                    this.errorMessage = '';
                }
            }, 5000);
        },

        showSuccess(msg) {
            this.successMessage = msg;
            setTimeout(() => {
                if (this.successMessage === msg) {
                    this.successMessage = '';
                }
            }, 4000);
        },
        
        // Drag and Drop State
        draggedTestCase: null,
        dragOverTarget: null,
        dragOverPosition: null,
        isMovingTestCase: false,

        startDragging(tc, event) {
            this.draggedTestCase = tc;
            event.dataTransfer.effectAllowed = 'move';
            setTimeout(() => {
                this.dragOverTarget = null;
                this.dragOverPosition = null;
            }, 0);
        },

        handleDragOver(tc, event) {
            this.dragOverTarget = tc.id;
            
            const itemRow = event.currentTarget.querySelector('.test-case-row');
            if (!itemRow) return;

            const rect = itemRow.getBoundingClientRect();
            const y = event.clientY - rect.top;
            
            if (y < rect.height * 0.25) {
                this.dragOverPosition = 'before';
            } else if (y > rect.height * 0.75) {
                this.dragOverPosition = 'after';
            } else {
                this.dragOverPosition = 'inside';
            }
        },

        handleDragLeave(tc, event) {
            if (!event.currentTarget.contains(event.relatedTarget)) {
                if (this.dragOverTarget === tc.id) {
                    this.dragOverTarget = null;
                    this.dragOverPosition = null;
                }
            }
        },

        async dropTestCase(targetId) {
            if (!this.permissions.canManageTestCases || !this.draggedTestCase) return;
            const sourceId = this.draggedTestCase.id;
            const position = this.dragOverPosition || 'inside';
            
            // Cannot drop on itself
            if (sourceId === targetId) {
                this.draggedTestCase = null;
                this.dragOverTarget = null;
                this.dragOverPosition = null;
                return;
            }

            // Execute move
            this.isMovingTestCase = true;
            try {
                const response = await fetch(`/api/qc/test-cases/${sourceId}/move`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ target_id: targetId, position: targetId === null ? 'inside' : position })
                });

                if (response.ok) {
                    await this.fetchProjectTestCases();
                    // Keep the target expanded so the user sees the dropped item
                    if (targetId && position === 'inside') {
                        this.expandTestCase(this.projectTestCases, targetId);
                    }
                } else {
                    const data = await response.json();
                    this.showError(data.message || 'Failed to move test case.');
                }
            } catch (error) {
                console.error('Error moving test case:', error);
            } finally {
                this.isMovingTestCase = false;
                this.draggedTestCase = null;
                this.dragOverTarget = null;
                this.dragOverPosition = null;
            }
        },

        // Kanban Task Drag and Drop State & Methods
        draggedTask: null,
        dragOverColumn: null,

        startTaskDrag(task, event) {
            if (!this.permissions.canManageTasks) return;
            if (this.permissions.isStaffOnly && (task.column_id === 'qc_in_progress' || task.column_id === 'done')) {
                return;
            }
            this.draggedTask = task;
            event.dataTransfer.effectAllowed = 'move';
            try {
                event.dataTransfer.setData('text/plain', String(task.id));
            } catch (e) {}
        },

        handleTaskDragOver(columnId, event) {
            if (!this.permissions.canManageTasks || !this.draggedTask) return;
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            this.dragOverColumn = columnId;
        },

        handleTaskDragLeave(columnId, event) {
            if (!event.currentTarget.contains(event.relatedTarget)) {
                if (this.dragOverColumn === columnId) {
                    this.dragOverColumn = null;
                }
            }
        },

        endTaskDrag() {
            this.draggedTask = null;
            this.dragOverColumn = null;
        },

        async dropTaskOnColumn(targetColumnId) {
            if (!this.permissions.canManageTasks || !this.draggedTask) return;
            const taskToMove = this.draggedTask;
            const currentColumn = taskToMove.column_id;
            this.endTaskDrag();

            if (currentColumn === targetColumnId) {
                return;
            }

            if (this.permissions.isStaffOnly) {
                const allowedStaffColumns = ['todo', 'in_progress', 'ready_for_qc'];
                if (!allowedStaffColumns.includes(currentColumn) || !allowedStaffColumns.includes(targetColumnId)) {
                    this.showError('Akses ditolak. Role Staff hanya dapat mengubah posisi task hingga Ready for QC.');
                    return;
                }
            }

            if (targetColumnId === 'done' && !this.permissions.canExecuteTests) {
                this.showError('Akses ditolak. Anda tidak memiliki izin untuk menandai Pass QC.');
                return;
            }

            await this.updateTaskColumn(taskToMove.id, targetColumnId);
        },

        init() {
            this.fetchTasks();
            this.fetchProjectTestCases();
            this.fetchProjectBugs();
        },

        async fetchProjectBugs() {
            try {
                const response = await fetch(`/api/projects/${this.projectId}/qc/bugs`);
                if (response.ok) {
                    this.projectBugs = await response.json();
                }
            } catch (error) {
                console.error("Error fetching project bugs:", error);
            }
        },

        async convertBugToTask(bugId) {
            if (!this.permissions.canManageBugs || this.convertingBugId) return;
            this.convertingBugId = bugId;
            try {
                const response = await fetch(`/api/qc/bugs/${bugId}/convert`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({}) // Assignee empty for now, defaults to Todo
                });

                if (response.ok) {
                    const resData = await response.json();
                    await this.fetchTasks();
                    await this.fetchProjectBugs();
                    if (this.viewingBug && this.viewingBug.id === bugId) {
                        this.viewingBug = this.projectBugs.find(b => b.id === bugId) || this.viewingBug;
                    }
                    this.isKanbanExpanded = true;
                    this.showSuccess('New task (' + (resData.task?.code || '') + ') successfully created in Kanban Board!');
                } else {
                    const data = await response.json();
                    this.showError(data.message || 'Failed to convert bug to task.');
                }
            } catch (error) {
                console.error("Error converting bug to task:", error);
                this.showError('An error occurred while processing request.');
            } finally {
                this.convertingBugId = null;
            }
        },

        async fetchTasks() {
            try {
                const response = await fetch(`/api/projects/${this.projectId}/qc/tasks`);
                if (response.ok) {
                    this.tasks = await response.json();
                }
            } catch (error) {
                console.error("Error fetching tasks:", error);
            }
        },

        get hasActiveBugFilters() {
            return !!(
                (this.bugFilters.details && this.bugFilters.details.trim()) ||
                (this.bugFilters.version && this.bugFilters.version.trim()) ||
                this.bugFilters.severity ||
                this.bugFilters.status ||
                (this.bugFilters.testCase && this.bugFilters.testCase.trim()) ||
                this.bugFilters.task
            );
        },

        resetBugFilters() {
            this.bugFilters = {
                details: '',
                version: '',
                severity: '',
                status: '',
                testCase: '',
                task: '',
            };
        },

        get filteredProjectBugs() {
            let list = this.projectBugs;

            // 1. Tab Filter
            if (this.bugFilterTab === 'active') {
                list = list.filter(b => b.status !== 'resolved');
            } else if (this.bugFilterTab === 'solved') {
                list = list.filter(b => b.status === 'resolved');
            }

            // 2. Column: Details (Code or Description)
            if (this.bugFilters.details && this.bugFilters.details.trim()) {
                const q = this.bugFilters.details.toLowerCase().trim();
                list = list.filter(b => 
                    (b.code && b.code.toLowerCase().includes(q)) || 
                    (b.description && b.description.toLowerCase().includes(q))
                );
            }

            // 2.5 Column: Versi
            if (this.bugFilters.version && this.bugFilters.version.trim()) {
                const v = this.bugFilters.version.toLowerCase().trim();
                list = list.filter(b => b.app_version && b.app_version.toLowerCase().includes(v));
            }

            // 3. Column: Severity
            if (this.bugFilters.severity) {
                const sev = this.bugFilters.severity.toLowerCase();
                list = list.filter(b => b.severity && b.severity.toLowerCase() === sev);
            }

            // 4. Column: Status
            if (this.bugFilters.status) {
                const st = this.bugFilters.status.toLowerCase();
                list = list.filter(b => b.status && b.status.toLowerCase() === st);
            }

            // 5. Column: Test Case (Code or Title)
            if (this.bugFilters.testCase && this.bugFilters.testCase.trim()) {
                const q = this.bugFilters.testCase.toLowerCase().trim();
                list = list.filter(b => {
                    if (!b.test_case) return false;
                    return (b.test_case.code && b.test_case.code.toLowerCase().includes(q)) ||
                           (b.test_case.title && b.test_case.title.toLowerCase().includes(q));
                });
            }

            // 6. Column: Kanban Task
            if (this.bugFilters.task) {
                if (this.bugFilters.task === 'assigned') {
                    list = list.filter(b => !!b.project_task);
                } else if (this.bugFilters.task === 'unassigned') {
                    list = list.filter(b => !b.project_task);
                }
            }

            return list;
        },

        isBugSelected(id) {
            return this.selectedBugIds.some(sid => Number(sid) === Number(id));
        },

        get isAllBugsSelected() {
            const visible = this.filteredProjectBugs;
            return visible.length > 0 && visible.every(b => this.isBugSelected(b.id));
        },

        get isSomeBugsSelected() {
            return this.selectedBugIds.length > 0 && !this.isAllBugsSelected;
        },

        toggleSelectAllBugs(event) {
            const checked = event.target.checked;
            const visibleIds = this.filteredProjectBugs.map(b => Number(b.id));
            if (checked) {
                const currentSet = new Set(this.selectedBugIds.map(Number));
                visibleIds.forEach(id => currentSet.add(id));
                this.selectedBugIds = Array.from(currentSet);
            } else {
                this.selectedBugIds = this.selectedBugIds.filter(id => !visibleIds.includes(Number(id)));
            }
        },

        toggleBugSelection(id) {
            const numId = Number(id);
            const index = this.selectedBugIds.findIndex(sid => Number(sid) === numId);
            if (index > -1) {
                this.selectedBugIds.splice(index, 1);
            } else {
                this.selectedBugIds.push(numId);
            }
        },

        getSelectedBugs() {
            return this.projectBugs.filter(b => this.isBugSelected(b.id));
        },

        openBulkConvertModal() {
            if (!this.permissions.canManageBugs || this.selectedBugIds.length === 0) return;
            const selected = this.getSelectedBugs();
            const codes = selected.map(b => b.code).join(', ');
            
            this.bulkTask = {
                title: 'Perbaikan ' + selected.length + ' Bug (' + (selected.length <= 3 ? codes : selected.slice(0, 2).map(b => b.code).join(', ') + '...') + ')',
                description: "Kumpulan defect untuk diperbaiki:\n" + selected.map(b => `• [${b.code}] (${b.test_case ? b.test_case.code : 'No-TC'}): ${b.description}`).join("\n"),
                assignee_id: '',
                column_id: 'todo'
            };
            this.isBulkTaskModalOpen = true;
        },

        async submitBulkConvert() {
            if (!this.permissions.canManageBugs || this.isSubmittingBulkConvert || this.selectedBugIds.length === 0) return;
            this.isSubmittingBulkConvert = true;

            try {
                const response = await fetch(`/api/projects/${this.projectId}/qc/bugs/bulk-convert`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        bug_ids: this.selectedBugIds,
                        title: this.bulkTask.title,
                        description: this.bulkTask.description,
                        assignee_id: this.bulkTask.assignee_id,
                        column_id: this.bulkTask.column_id
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    await this.fetchTasks();
                    await this.fetchProjectBugs();
                    await this.fetchProjectTestCases();
                    
                    this.selectedBugIds = [];
                    this.isBulkTaskModalOpen = false;
                    this.isKanbanExpanded = true;
                    this.showSuccess(`New task (${data.task.code}) successfully created from ${data.count} bugs!`);
                } else {
                    const err = await response.json();
                    this.showError(err.message || 'Failed to create task from selected bugs.');
                }
            } catch (error) {
                console.error("Error bulk converting bugs to task:", error);
                this.showError('An error occurred while creating task.');
            } finally {
                this.isSubmittingBulkConvert = false;
            }
        },

        async passAllTaskTestCases(taskId) {
            if (!this.permissions.canExecuteTests || this.isPassingAllTests) return;
            if (!confirm('Are you sure you want to mark all Test Cases for this task as PASSED and resolve linked bugs?')) return;
            
            this.isPassingAllTests = true;
            try {
                const response = await fetch(`/api/qc/tasks/${taskId}/pass-test-cases`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                if (response.ok) {
                    await this.fetchTasks();
                    await this.fetchProjectTestCases();
                    await this.fetchProjectBugs();
                    
                    if (this.activeTask && this.activeTask.id === taskId) {
                        const updatedTask = this.tasks.find(t => t.id === taskId);
                        if (updatedTask) {
                            this.activeTask = updatedTask;
                        }
                    }
                    this.showSuccess('All test cases marked as PASSED and linked bugs have been resolved!');
                } else {
                    this.showError('Failed to update test cases status.');
                }
            } catch (error) {
                console.error('Error passing all test cases:', error);
                this.showError('An error occurred while processing request.');
            } finally {
                this.isPassingAllTests = false;
            }
        },

        projectTestCases: [],
        
        get flatTestCases() {
            return this.flattenTestCases(this.projectTestCases, 0);
        },

        flattenTestCases(cases, level = 0) {
            let flat = [];
            cases.forEach(tc => {
                tc.level = level;
                flat.push(tc);
                if (tc.is_expanded && tc.children && tc.children.length > 0) {
                    flat = flat.concat(this.flattenTestCases(tc.children, level + 1));
                }
            });
            return flat;
        },

        toggleTestCase(id) {
            const toggleInTree = (cases) => {
                for (let tc of cases) {
                    if (tc.id === id) {
                        tc.is_expanded = !tc.is_expanded;
                        return true;
                    }
                    if (tc.children && tc.children.length > 0) {
                        if (toggleInTree(tc.children)) return true;
                    }
                }
                return false;
            };
            toggleInTree(this.projectTestCases);
        },

        async fetchProjectTestCases() {
            try {
                const response = await fetch(`/api/projects/${this.projectId}/qc/test-cases`);
                if (response.ok) {
                    // Retain expanded state if possible
                    const prevExpandedIds = new Set(this.getExpandedIds(this.projectTestCases));
                    const newCases = await response.json();
                    this.restoreExpandedState(newCases, prevExpandedIds);
                    this.projectTestCases = newCases;
                }
            } catch (error) {
                console.error("Error fetching project test cases:", error);
            }
        },

        getExpandedIds(cases) {
            let ids = [];
            for (let tc of cases) {
                if (tc.is_expanded) ids.push(tc.id);
                if (tc.children && tc.children.length > 0) {
                    ids = ids.concat(this.getExpandedIds(tc.children));
                }
            }
            return ids;
        },

        restoreExpandedState(cases, expandedIds) {
            for (let tc of cases) {
                if (expandedIds.has(tc.id)) {
                    tc.is_expanded = true;
                }
                if (tc.children && tc.children.length > 0) {
                    this.restoreExpandedState(tc.children, expandedIds);
                }
            }
        },

        findTestCaseById(id, cases = this.projectTestCases) {
            if (!id) return null;
            for (let tc of cases) {
                if (tc.id === id) return tc;
                if (tc.children && tc.children.length > 0) {
                    const found = this.findTestCaseById(id, tc.children);
                    if (found) return found;
                }
            }
            return null;
        },

        findParentTestCase(childId, cases = this.projectTestCases) {
            if (!childId) return null;
            for (let tc of cases) {
                if (tc.children && tc.children.length > 0) {
                    if (tc.children.some(c => c.id === childId)) {
                        return tc;
                    }
                    const found = this.findParentTestCase(childId, tc.children);
                    if (found) return found;
                }
            }
            return null;
        },

        openViewTestCaseModal(tcOrId) {
            if (!tcOrId) return;
            const tcId = typeof tcOrId === 'object' ? tcOrId.id : tcOrId;
            const fullTc = this.findTestCaseById(tcId);
            this.viewingTestCase = fullTc || (typeof tcOrId === 'object' ? tcOrId : null);
            this.isViewTestCaseModalOpen = true;
        },

        closeViewTestCaseModal() {
            this.isViewTestCaseModalOpen = false;
            setTimeout(() => {
                this.viewingTestCase = null;
            }, 300);
        },

        openViewBugModal(bugOrId) {
            if (!bugOrId) return;
            const bugId = typeof bugOrId === 'object' ? bugOrId.id : bugOrId;
            const fullBug = this.projectBugs.find(b => b.id === bugId);
            this.viewingBug = fullBug || (typeof bugOrId === 'object' ? bugOrId : null);
            this.isViewBugModalOpen = true;
        },

        closeViewBugModal() {
            this.isViewBugModalOpen = false;
            setTimeout(() => {
                this.viewingBug = null;
            }, 300);
        },

        openEditBugModal(bugOrId) {
            if (!this.permissions.canManageBugs || !bugOrId) return;
            const bugId = typeof bugOrId === 'object' ? bugOrId.id : bugOrId;
            const bug = this.projectBugs.find(b => b.id === bugId) || (typeof bugOrId === 'object' ? bugOrId : null);
            if (!bug) return;

            let initialSteps = [{ id: 'bug_step_' + Date.now(), text: '' }];
            if (bug.steps_to_reproduce) {
                if (Array.isArray(bug.steps_to_reproduce) && bug.steps_to_reproduce.length > 0) {
                    initialSteps = bug.steps_to_reproduce.map((s, idx) => ({
                        id: 'bug_step_' + Date.now() + '_' + idx,
                        text: typeof s === 'object' && s !== null ? (s.text || '') : String(s)
                    }));
                } else if (typeof bug.steps_to_reproduce === 'string' && bug.steps_to_reproduce.trim() !== '') {
                    const lines = bug.steps_to_reproduce.split(/\r\n|\r|\n/).map(l => l.trim()).filter(l => l !== '');
                    if (lines.length > 0) {
                        initialSteps = lines.map((l, idx) => ({
                            id: 'bug_step_' + Date.now() + '_' + idx,
                            text: l.replace(/^(\d+[\.\)]\s*|[-*•]\s*)/, '')
                        }));
                    }
                }
            }

            this.editingBug = {
                id: bug.id,
                code: bug.code,
                description: bug.description || '',
                actual_result: bug.actual_result || '',
                severity: bug.severity || 'Medium',
                environment: bug.environment || '',
                app_version: bug.app_version || '',
                status: bug.status || 'open',
                steps_to_reproduce: initialSteps,
                attachment_path: bug.attachment_path || null,
                remove_attachment: false,
                test_case: bug.test_case || null,
                project_task: bug.project_task || null,
            };

            this.endBugStepDrag();
            this.isEditBugModalOpen = true;
        },

        closeEditBugModal() {
            this.isEditBugModalOpen = false;
            this.endBugStepDrag();
            setTimeout(() => {
                this.editingBug = null;
            }, 300);
        },

        addBugStep() {
            if (!this.editingBug) return;
            this.editingBug.steps_to_reproduce.push({
                id: 'bug_step_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6),
                text: ''
            });
        },

        removeBugStep(index) {
            if (!this.editingBug) return;
            if (this.editingBug.steps_to_reproduce.length > 1) {
                this.editingBug.steps_to_reproduce.splice(index, 1);
            }
        },

        startBugStepDrag(index, event) {
            this.draggedBugStepIndex = index;
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', index.toString());
            }
        },

        handleBugStepDragOver(index, event) {
            if (this.draggedBugStepIndex === null || this.draggedBugStepIndex === index) {
                this.dragOverBugStepIndex = null;
                this.dragOverBugStepPosition = null;
                return;
            }
            this.dragOverBugStepIndex = index;
            const rect = event.currentTarget.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;
            this.dragOverBugStepPosition = event.clientY < midY ? 'before' : 'after';
        },

        handleBugStepDragLeave(index, event) {
            if (!event.currentTarget.contains(event.relatedTarget)) {
                if (this.dragOverBugStepIndex === index) {
                    this.dragOverBugStepIndex = null;
                    this.dragOverBugStepPosition = null;
                }
            }
        },

        dropBugStep(targetIndex) {
            if (this.draggedBugStepIndex === null || !this.editingBug) return;
            const fromIndex = this.draggedBugStepIndex;
            const position = this.dragOverBugStepPosition || 'after';

            if (fromIndex !== targetIndex) {
                const steps = [...this.editingBug.steps_to_reproduce];
                const [movedItem] = steps.splice(fromIndex, 1);
                let insertIndex = targetIndex;
                if (fromIndex < targetIndex) {
                    insertIndex = insertIndex - 1;
                }
                if (position === 'after') {
                    insertIndex = insertIndex + 1;
                }
                insertIndex = Math.max(0, Math.min(insertIndex, steps.length));
                steps.splice(insertIndex, 0, movedItem);
                this.editingBug.steps_to_reproduce = steps;
            }

            this.endBugStepDrag();
        },

        endBugStepDrag() {
            this.draggedBugStepIndex = null;
            this.dragOverBugStepIndex = null;
            this.dragOverBugStepPosition = null;
            this.canDragBugStep = false;
        },

        moveBugStepUp(index) {
            if (!this.editingBug) return;
            if (index > 0) {
                const steps = [...this.editingBug.steps_to_reproduce];
                const temp = steps[index];
                steps[index] = steps[index - 1];
                steps[index - 1] = temp;
                this.editingBug.steps_to_reproduce = steps;
            }
        },

        moveBugStepDown(index) {
            if (!this.editingBug) return;
            if (index < this.editingBug.steps_to_reproduce.length - 1) {
                const steps = [...this.editingBug.steps_to_reproduce];
                const temp = steps[index];
                steps[index] = steps[index + 1];
                steps[index + 1] = temp;
                this.editingBug.steps_to_reproduce = steps;
            }
        },

        async submitEditBug() {
            if (!this.permissions.canManageBugs || this.isSubmittingBug || !this.editingBug) return;
            this.isSubmittingBug = true;

            try {
                const cleanedSteps = this.editingBug.steps_to_reproduce
                    .map(s => typeof s === 'object' && s !== null ? s.text : s)
                    .filter(s => typeof s === 'string' && s.trim() !== '');

                const formData = new FormData();
                formData.append('description', this.editingBug.description);
                formData.append('actual_result', this.editingBug.actual_result || '');
                formData.append('severity', this.editingBug.severity || 'Medium');
                formData.append('environment', this.editingBug.environment || '');
                formData.append('app_version', this.editingBug.app_version || '');
                formData.append('status', this.editingBug.status || 'open');
                formData.append('remove_attachment', this.editingBug.remove_attachment ? '1' : '0');

                cleanedSteps.forEach((step, idx) => {
                    formData.append(`steps_to_reproduce[${idx}]`, step);
                });

                const fileInput = document.getElementById('edit_bug_attachment');
                if (fileInput && fileInput.files[0]) {
                    formData.append('attachment', fileInput.files[0]);
                }

                const response = await fetch(`/api/qc/bugs/${this.editingBug.id}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    const updatedBug = data.bug;
                    const bugIndex = this.projectBugs.findIndex(b => b.id === updatedBug.id);
                    if (bugIndex !== -1) {
                        this.projectBugs[bugIndex] = { ...this.projectBugs[bugIndex], ...updatedBug };
                    }

                    if (this.viewingBug && this.viewingBug.id === updatedBug.id) {
                        this.viewingBug = { ...this.viewingBug, ...updatedBug };
                    }

                    this.fetchTasks();
                    this.fetchTestCases();

                    this.closeEditBugModal();
                    this.showSuccess('Bug report berhasil diperbarui.');
                } else {
                    this.showError(data.message || 'Gagal memperbarui bug report.');
                }
            } catch (error) {
                console.error('Error updating bug:', error);
                this.showError('Terjadi kesalahan saat menyimpan perubahan bug.');
            } finally {
                this.isSubmittingBug = false;
            }
        },

        openNewTestCaseModal(parentTC = null) {
            if (!this.permissions.canManageTestCases) return;
            this.parentTestCase = parentTC;
            this.editingTestCaseId = null;
            this.isDuplicatingTestCase = false;
            this.endStepDrag();
            this.newTestCase = {
                title: '',
                preconditions: '',
                expected: '',
                steps: [{ id: 'step_' + Date.now(), text: '' }],
                payload: '',
                complexity: 'Low',
                priority: 'Medium',
                test_type: 'Functional',
                automation_status: 'Manual',
                app_version: parentTC ? (parentTC.app_version || '') : ''
            };
            this.isNewTestCaseModalOpen = true;
        },

        openEditTestCaseModal(tc) {
            if (!this.permissions.canManageTestCases) return;
            this.editingTestCaseId = tc.id;
            this.parentTestCase = null;
            this.isDuplicatingTestCase = false;
            this.endStepDrag();

            let initialSteps = [{ id: 'step_' + Date.now(), text: '' }];
            if (tc.steps && Array.isArray(tc.steps) && tc.steps.length > 0) {
                initialSteps = tc.steps.map((s, idx) => ({
                    id: 'step_' + Date.now() + '_' + idx,
                    text: (typeof s === 'object' && s !== null ? (s.text || '') : (s || '')).toString()
                }));
            }

            this.newTestCase = {
                title: tc.title || '',
                preconditions: tc.preconditions || '',
                expected: tc.expected || '',
                steps: initialSteps,
                payload: tc.payload || '',
                complexity: tc.complexity || 'Low',
                priority: tc.priority || 'Medium',
                test_type: tc.test_type || 'Functional',
                automation_status: tc.automation_status || 'Manual',
                app_version: tc.app_version || ''
            };
            this.isNewTestCaseModalOpen = true;
        },

        duplicateTestCase(tc) {
            if (!this.permissions.canManageTestCases) return;
            this.editingTestCaseId = null;
            this.isDuplicatingTestCase = true;
            this.endStepDrag();
            
            // Resolve parent: first check tc.parent_id, then fallback to tree hierarchy search
            let parent = null;
            if (tc.parent_id) {
                parent = this.findTestCaseById(tc.parent_id);
            }
            if (!parent) {
                parent = this.findParentTestCase(tc.id);
            }
            if (!parent && tc.parent_id) {
                parent = { id: tc.parent_id, code: 'Parent', title: '' };
            }
            this.parentTestCase = parent;

            let initialSteps = [{ id: 'step_' + Date.now(), text: '' }];
            if (tc.steps && Array.isArray(tc.steps) && tc.steps.length > 0) {
                initialSteps = tc.steps.map((s, idx) => ({
                    id: 'step_' + Date.now() + '_' + idx,
                    text: (typeof s === 'object' && s !== null ? (s.text || '') : (s || '')).toString()
                }));
            }

            this.newTestCase = {
                title: tc.title ? tc.title + ' (Copy)' : '',
                preconditions: tc.preconditions || '',
                expected: tc.expected || '',
                steps: initialSteps,
                payload: tc.payload || '',
                complexity: tc.complexity || 'Low',
                priority: tc.priority || 'Medium',
                test_type: tc.test_type || 'Functional',
                automation_status: tc.automation_status || 'Manual',
                app_version: tc.app_version || ''
            };
            this.isNewTestCaseModalOpen = true;
        },

        closeNewTestCaseModal() {
            this.isNewTestCaseModalOpen = false;
            this.endStepDrag();
            setTimeout(() => {
                this.editingTestCaseId = null;
                this.isDuplicatingTestCase = false;
            }, 300);
        },

        addStep() {
            this.newTestCase.steps.push({
                id: 'step_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6),
                text: ''
            });
        },

        removeStep(index) {
            if (this.newTestCase.steps.length > 1) {
                this.newTestCase.steps.splice(index, 1);
            }
        },

        startStepDrag(index, event) {
            this.draggedStepIndex = index;
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', index.toString());
            }
        },

        handleStepDragOver(index, event) {
            if (this.draggedStepIndex === null || this.draggedStepIndex === index) {
                this.dragOverStepIndex = null;
                this.dragOverStepPosition = null;
                return;
            }
            this.dragOverStepIndex = index;
            const rect = event.currentTarget.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;
            this.dragOverStepPosition = event.clientY < midY ? 'before' : 'after';
        },

        handleStepDragLeave(index, event) {
            if (!event.currentTarget.contains(event.relatedTarget)) {
                if (this.dragOverStepIndex === index) {
                    this.dragOverStepIndex = null;
                    this.dragOverStepPosition = null;
                }
            }
        },

        dropStep(targetIndex) {
            if (this.draggedStepIndex === null) return;
            const fromIndex = this.draggedStepIndex;
            const position = this.dragOverStepPosition || 'after';

            if (fromIndex !== targetIndex) {
                const steps = [...this.newTestCase.steps];
                const [movedItem] = steps.splice(fromIndex, 1);
                let insertIndex = targetIndex;
                if (fromIndex < targetIndex) {
                    insertIndex = insertIndex - 1;
                }
                if (position === 'after') {
                    insertIndex = insertIndex + 1;
                }
                insertIndex = Math.max(0, Math.min(insertIndex, steps.length));
                steps.splice(insertIndex, 0, movedItem);
                this.newTestCase.steps = steps;
            }

            this.endStepDrag();
        },

        endStepDrag() {
            this.draggedStepIndex = null;
            this.dragOverStepIndex = null;
            this.dragOverStepPosition = null;
            this.canDragStep = false;
        },

        moveStepUp(index) {
            if (index > 0) {
                const steps = [...this.newTestCase.steps];
                const temp = steps[index];
                steps[index] = steps[index - 1];
                steps[index - 1] = temp;
                this.newTestCase.steps = steps;
            }
        },

        moveStepDown(index) {
            if (index < this.newTestCase.steps.length - 1) {
                const steps = [...this.newTestCase.steps];
                const temp = steps[index];
                steps[index] = steps[index + 1];
                steps[index + 1] = temp;
                this.newTestCase.steps = steps;
            }
        },

        async submitNewTestCase() {
            if (!this.permissions.canManageTestCases || this.isSubmittingTestCase) return;
            this.isSubmittingTestCase = true;

            const cleanedSteps = this.newTestCase.steps
                .map(s => typeof s === 'object' && s !== null ? s.text : s)
                .filter(s => typeof s === 'string' && s.trim() !== '');

            const payload = {
                title: this.newTestCase.title,
                preconditions: this.newTestCase.preconditions,
                expected: this.newTestCase.expected,
                steps: cleanedSteps,
                payload: this.newTestCase.payload,
                complexity: this.newTestCase.complexity,
                priority: this.newTestCase.priority,
                test_type: this.newTestCase.test_type,
                automation_status: this.newTestCase.automation_status,
                app_version: this.newTestCase.app_version,
                parent_id: this.parentTestCase ? this.parentTestCase.id : null
            };

            try {
                let response;
                if (this.editingTestCaseId) {
                    response = await fetch(`/api/qc/test-cases/${this.editingTestCaseId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    });
                } else {
                    response = await fetch(`/api/projects/${this.projectId}/qc/test-cases`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    });
                }

                if (response.ok) {
                    const parentIdToExpand = this.parentTestCase ? this.parentTestCase.id : null;
                    // Expand parent before fetching so that restoreExpandedState will keep it expanded
                    if (!this.editingTestCaseId && parentIdToExpand) {
                        this.expandTestCase(this.projectTestCases, parentIdToExpand);
                    }
                    await this.fetchProjectTestCases();
                    // Also ensure parent is expanded in the new tree
                    if (!this.editingTestCaseId && parentIdToExpand) {
                        this.expandTestCase(this.projectTestCases, parentIdToExpand);
                    }
                    this.closeNewTestCaseModal();
                } else {
                    let errorMsg = 'Gagal menyimpan test case.';
                    try {
                        const errorData = await response.json();
                        if (errorData.errors) {
                            errorMsg = Object.values(errorData.errors).flat().join(', ');
                        } else if (errorData.message) {
                            errorMsg = errorData.message;
                        }
                    } catch (e) {}
                    this.showError(errorMsg);
                }
            } catch (error) {
                console.error('Error submitting new test case:', error);
                this.showError('Terjadi kesalahan jaringan. Silakan coba lagi.');
            } finally {
                this.isSubmittingTestCase = false;
            }
        },

        expandTestCase(cases, id) {
            for (let tc of cases) {
                if (tc.id === id) {
                    tc.is_expanded = true;
                    return true;
                }
                if (tc.children && tc.children.length > 0) {
                    if (this.expandTestCase(tc.children, id)) {
                        tc.is_expanded = true; // also expand parents along the path
                        return true;
                    }
                }
            }
            return false;
        },

        getTasksByColumn(columnId) {
            return this.tasks.filter(t => t.column_id === columnId);
        },
        
        getColumnTitle(columnId) {
            const col = this.columns.find(c => c.id === columnId);
            return col ? col.title : '';
        },
        
        getColumnBadgeClass(columnId) {
            switch(columnId) {
                case 'todo': return 'bg-gray-100 text-gray-700';
                case 'in_progress': return 'bg-blue-100 text-blue-700';
                case 'ready_for_qc': return 'bg-yellow-100 text-yellow-800';
                case 'qc_in_progress': return 'bg-purple-100 text-purple-700';
                case 'done': return 'bg-green-100 text-green-700';
                default: return 'bg-gray-100 text-gray-700';
            }
        },

        openTaskModal(task, initialTab = null) {
            this.activeTask = task;
            this.activeTaskComments = task.comments || [];
            if (initialTab) {
                this.activeTab = initialTab;
            } else if (task.testCases && task.testCases.length > 0) {
                this.activeTab = 'test_cases';
            } else {
                this.activeTab = 'details';
            }
            this.isTaskModalOpen = true;
            this.newCommentText = '';
            this.newCommentFile = null;
            this.fetchTaskComments(task.id);
        },
        
        closeTaskModal() {
            this.isTaskModalOpen = false;
            setTimeout(() => {
                this.activeTask = null;
                this.activeTaskComments = [];
                this.newCommentText = '';
                this.newCommentFile = null;
            }, 300);
        },

        openTaskModalById(taskId, initialTab = null) {
            const task = this.tasks.find(t => t.id === taskId);
            if (task) {
                this.openTaskModal(task, initialTab);
            }
        },

        async deleteTask(taskId) {
            if (!this.permissions.canManageTasks) return;
            if (!confirm('Are you sure you want to delete this Kanban Task? This action cannot be undone.')) return;
            try {
                const response = await fetch(`/api/qc/tasks/${taskId}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await response.json().catch(() => ({}));
                if (response.ok && (data.success || data.status !== 'error')) {
                    this.closeTaskModal();
                    await this.fetchTasks();
                    await this.fetchProjectBugs();
                    this.showSuccess('Task successfully deleted.');
                } else {
                    this.showError(data.message || 'Failed to delete task.');
                }
            } catch (error) {
                console.error("Error deleting task:", error);
                this.showError('An error occurred while deleting the task.');
            }
        },

        async deleteTestCase(testCaseId) {
            if (!this.permissions.canManageTestCases) return;
            if (!confirm('Are you sure you want to delete this Test Case? This will also remove associated bugs.')) return;
            try {
                const response = await fetch(`/api/qc/test-cases/${testCaseId}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await response.json().catch(() => ({}));
                if (response.ok && (data.success || data.status !== 'error')) {
                    if (this.activeTest && this.activeTest.id === testCaseId) {
                        this.closeRunTestModal();
                    }
                    if (this.editingTestCase && this.editingTestCase.id === testCaseId) {
                        this.closeEditTestCaseModal();
                    }
                    await this.fetchProjectTestCases();
                    await this.fetchProjectBugs();
                    await this.fetchTasks();
                    this.showSuccess('Test case and its child items successfully deleted.');
                } else {
                    this.showError(data.message || 'Failed to delete test case.');
                }
            } catch (error) {
                console.error("Error deleting test case:", error);
                this.showError('An error occurred while deleting the test case.');
            }
        },

        async deleteBug(bugId) {
            if (!this.permissions.canManageBugs) return;
            if (!confirm('Are you sure you want to delete this Bug?')) return;
            try {
                const response = await fetch(`/api/qc/bugs/${bugId}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await response.json().catch(() => ({}));
                if (response.ok && (data.success || data.status !== 'error')) {
                    if (this.viewingBug && this.viewingBug.id === bugId) {
                        this.closeViewBugModal();
                    }
                    await this.fetchProjectBugs();
                    await this.fetchProjectTestCases();
                    await this.fetchTasks();
                    this.showSuccess('Bug successfully deleted.');
                } else {
                    this.showError(data.message || 'Failed to delete bug.');
                }
            } catch (error) {
                console.error("Error deleting bug:", error);
                this.showError('An error occurred while deleting the bug.');
            }
        },

        openRunTestModal(testCase) {
            if (!this.permissions.canExecuteTests) return;
            this.activeTest = testCase;
            this.isReportingBug = false;
            
            if (testCase.bug) {
                this.bugDescription = testCase.bug.description || '';
                this.bugActualResult = testCase.bug.actual_result || '';
                this.bugSeverity = testCase.bug.severity || 'Medium';
                this.bugEnvironment = testCase.bug.environment || '';
                this.bugAppVersion = testCase.bug.app_version || testCase.app_version || '';
            } else {
                this.bugDescription = '';
                this.bugActualResult = '';
                this.bugSeverity = 'Medium';
                this.bugEnvironment = '';
                this.bugAppVersion = testCase.app_version || '';
            }
            
            // Pre-fill steps to reproduce if test case has steps
            if (testCase.steps && testCase.steps.length > 0) {
                this.stepsToReproduce = testCase.steps.map((step, i) => `${i + 1}. ${step}`).join('\n');
            } else {
                this.stepsToReproduce = '';
            }
            
            this.createKanbanTask = false;
            this.bugAssigneeId = '';
            
            this.isRunTestOpen = true;
        },

        closeRunTestModal() {
            this.isRunTestOpen = false;
            this.isReportingBug = false;
            this.bugAppVersion = '';
            setTimeout(() => {
                this.activeTest = null;
            }, 300);
        },

        async submitTestResult(result) {
            if (!this.permissions.canExecuteTests) return;
            if (this.activeTest) {
                this.isSubmittingTest = true;
                try {
                    const formData = new FormData();
                    formData.append('status', result);
                    
                    if (result === 'failed') {
                        formData.append('bug_description', this.bugDescription);
                        formData.append('steps_to_reproduce', this.stepsToReproduce);
                        formData.append('severity', this.bugSeverity);
                        formData.append('actual_result', this.bugActualResult);
                        formData.append('environment', this.bugEnvironment);
                        formData.append('app_version', this.bugAppVersion);
                        formData.append('create_task', this.createKanbanTask);
                        formData.append('assignee_id', this.bugAssigneeId);
                        
                        const fileInput = document.getElementById('bug_attachment');
                        if (fileInput && fileInput.files[0]) {
                            formData.append('attachment', fileInput.files[0]);
                        }
                    }

                    const response = await fetch(`/api/qc/test-cases/${this.activeTest.id}/result`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.activeTest.status = result;
                        
                        // Immediately update status in projectTestCases tree so UI reflects change right away
                        this.updateTestCaseStatusInTree(this.projectTestCases, this.activeTest.id, result, data.bug);
                        this.projectTestCases = [...this.projectTestCases];

                        // Also update in activeTask if it contains this test case
                        if (this.activeTask && this.activeTask.testCases) {
                            const taskTc = this.activeTask.testCases.find(t => t.id === this.activeTest.id);
                            if (taskTc) {
                                taskTc.status = result;
                            }
                        }

                        // Resync viewingTestCase if it is currently open
                        if (this.viewingTestCase && this.viewingTestCase.id === this.activeTest.id) {
                            this.viewingTestCase.status = result;
                            if (result === 'passed' && this.viewingTestCase.bug) {
                                this.viewingTestCase.bug.status = 'resolved';
                            } else if (result === 'failed' && data.bug) {
                                this.viewingTestCase.bug = data.bug;
                            }
                        }
                        
                        if (result === 'failed') {
                            const bugCode = data.bug ? data.bug.code : '';
                            this.showSuccess(`Test Case failed. Bug tracker (${bugCode}) successfully linked!`);
                        } else {
                            this.showSuccess('Test Case passed and linked bug has been resolved.');
                        }
                        
                        this.closeRunTestModal();

                        // Sync in background from server
                        await Promise.all([
                            this.fetchProjectTestCases(),
                            this.fetchTasks(),
                            this.fetchProjectBugs()
                        ]);
                        
                        // Resync activeTask if it is currently open
                        if (this.activeTask) {
                            const updatedTask = this.tasks.find(t => t.id === this.activeTask.id);
                            if (updatedTask) {
                                this.activeTask = updatedTask;
                            }
                        }
                    } else {
                        const errorData = await response.json().catch(() => ({}));
                        this.showError(errorData.message || 'Failed to update test case status.');
                    }
                } catch (error) {
                    console.error('Error submitting test result:', error);
                    this.showError('An error occurred while processing test execution.');
                } finally {
                    this.isSubmittingTest = false;
                }
            }
        },

        updateTestCaseStatusInTree(cases, id, status, bug = null) {
            for (let tc of cases) {
                if (tc.id === id) {
                    tc.status = status;
                    if (status === 'passed' && tc.bug) {
                        tc.bug.status = 'resolved';
                    } else if (status === 'failed' && bug) {
                        tc.bug = bug;
                    }
                    return true;
                }
                if (tc.children && tc.children.length > 0) {
                    if (this.updateTestCaseStatusInTree(tc.children, id, status, bug)) return true;
                }
            }
            return false;
        },

        async openNewTaskModal(columnId = 'todo') {
            if (!this.permissions.canManageTasks) return;
            if (this.permissions.isStaffOnly && (columnId === 'qc_in_progress' || columnId === 'done')) {
                columnId = 'todo';
            }
            this.editingTaskId = null;
            this.existingAttachment = null;
            this.newTask = {
                title: '',
                description: '',
                assignee_id: '',
                column_id: columnId
            };
            const fileInput = document.getElementById('task_attachment');
            if (fileInput) fileInput.value = '';
            this.isNewTaskModalOpen = true;
        },

        openEditTaskModal(task) {
            if (!this.permissions.canManageTasks) return;
            if (this.permissions.isStaffOnly && (task.column_id === 'qc_in_progress' || task.column_id === 'done')) {
                this.showError('Role Staff tidak dapat mengedit task pada kolom QC in Progress atau Done.');
                return;
            }
            this.editingTaskId = task.id;
            this.existingAttachment = task.attachment_path || null;
            this.newTask = {
                title: task.title || '',
                description: task.description || '',
                assignee_id: task.assignee_id ? String(task.assignee_id) : '',
                column_id: task.column_id || 'todo'
            };
            const fileInput = document.getElementById('task_attachment');
            if (fileInput) fileInput.value = '';
            this.isNewTaskModalOpen = true;
        },

        closeNewTaskModal() {
            this.editingTaskId = null;
            this.existingAttachment = null;
            this.isNewTaskModalOpen = false;
        },

        async submitNewTask() {
            if (!this.permissions.canManageTasks || this.isSubmittingTask) return;
            if (!this.newTask.title || !this.newTask.title.trim()) {
                this.showError('Judul task wajib diisi.');
                return;
            }
            this.isSubmittingTask = true;
            try {
                const formData = new FormData();
                formData.append('title', this.newTask.title.trim());
                if (this.newTask.description) {
                    formData.append('description', this.newTask.description.trim());
                }
                if (this.newTask.assignee_id) {
                    formData.append('assignee_id', this.newTask.assignee_id);
                }
                formData.append('column_id', this.newTask.column_id || 'todo');

                const fileInput = document.getElementById('task_attachment');
                if (fileInput && fileInput.files[0]) {
                    formData.append('attachment', fileInput.files[0]);
                }

                const url = this.editingTaskId 
                    ? `/api/qc/tasks/${this.editingTaskId}` 
                    : `/api/projects/${this.projectId}/qc/tasks`;

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    await this.fetchTasks();
                    if (this.editingTaskId && this.activeTask && this.activeTask.id === this.editingTaskId) {
                        const updated = this.tasks.find(t => t.id === this.editingTaskId);
                        if (updated) {
                            this.activeTask = updated;
                        }
                    }
                    const msg = this.editingTaskId ? 'Task berhasil diperbarui.' : 'Task berhasil dibuat.';
                    this.closeNewTaskModal();
                    this.showSuccess(msg);
                } else {
                    this.showError(data.message || 'Gagal menyimpan task.');
                }
            } catch (error) {
                console.error('Error creating/updating task:', error);
                this.showError('Terjadi kesalahan saat menyimpan task.');
            } finally {
                this.isSubmittingTask = false;
            }
        },
        
        async updateTaskColumn(taskId, columnId) {
            if (!this.permissions.canManageTasks || this.isMovingTask) return;
            this.isMovingTask = true;
            this.movingToColumn = columnId;
            
            try {
                const response = await fetch(`/api/qc/tasks/${taskId}/move`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ column_id: columnId })
                });

                if (response.ok) {
                    await this.fetchTasks();
                    await this.fetchProjectTestCases();
                    await this.fetchProjectBugs();

                    if (this.activeTask && this.activeTask.id === taskId) {
                        this.activeTask.column_id = columnId;
                        
                        // Also update activeTask from the newly fetched array to maintain consistency
                        const updatedTask = this.tasks.find(t => t.id === taskId);
                        if (updatedTask) {
                            this.activeTask = updatedTask;
                        }
                    }

                    if (columnId === 'done') {
                        this.showSuccess('Task completed! All linked test cases marked as PASSED and bugs resolved.');
                    } else {
                        this.showSuccess(`Task successfully moved to ${this.getColumnTitle(columnId)}!`);
                    }
                } else {
                    const errorData = await response.json().catch(() => ({}));
                    this.showError(errorData.message || 'Failed to update task column.');
                }
            } catch (error) {
                console.error('Error updating task column:', error);
                this.showError('An error occurred while moving task.');
            } finally {
                this.isMovingTask = false;
                this.movingToColumn = null;
            }
        },

        async fetchTaskComments(taskId) {
            if (!taskId) return;
            this.isLoadingComments = true;
            try {
                const response = await fetch(`/api/qc/tasks/${taskId}/comments`);
                if (response.ok) {
                    this.activeTaskComments = await response.json();
                    if (this.activeTask && this.activeTask.id === taskId) {
                        this.activeTask.comments_count = this.activeTaskComments.length;
                    }
                    const cardTask = this.tasks.find(t => t.id === taskId);
                    if (cardTask) {
                        cardTask.comments_count = this.activeTaskComments.length;
                    }
                    this.$nextTick(() => {
                        this.scrollCommentsToBottom();
                    });
                }
            } catch (error) {
                console.error("Error fetching comments:", error);
            } finally {
                this.isLoadingComments = false;
            }
        },

        scrollCommentsToBottom() {
            const container = document.getElementById('comments-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        handleCommentFileChange(event) {
            if (event.target.files && event.target.files[0]) {
                this.newCommentFile = event.target.files[0];
            }
        },

        removeCommentFile() {
            this.newCommentFile = null;
            const input = document.getElementById('comment_attachment_input');
            if (input) input.value = '';
        },

        formatFileSize(bytes) {
            if (!bytes) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },

        isImageAttachment(path) {
            if (!path) return false;
            const ext = path.split('.').pop().toLowerCase();
            return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext);
        },

        getFilename(path) {
            if (!path) return '';
            return path.split('/').pop().replace(/^attachments\/task_comments\//, '');
        },

        async submitTaskComment() {
            if (!this.permissions.canComment || !this.activeTask || this.isSubmittingComment) return;
            const text = (this.newCommentText || '').trim();
            if (!text && !this.newCommentFile) return;

            this.isSubmittingComment = true;
            try {
                const formData = new FormData();
                if (text) {
                    formData.append('comment', text);
                }
                if (this.newCommentFile) {
                    formData.append('attachment', this.newCommentFile);
                }

                const response = await fetch(`/api/qc/tasks/${this.activeTask.id}/comments`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.comment) {
                        this.activeTaskComments.push(data.comment);
                    }
                    this.newCommentText = '';
                    this.removeCommentFile();
                    
                    if (this.activeTask) {
                        this.activeTask.comments_count = this.activeTaskComments.length;
                    }
                    const cardTask = this.tasks.find(t => t.id === this.activeTask.id);
                    if (cardTask) {
                        cardTask.comments_count = this.activeTaskComments.length;
                    }

                    this.$nextTick(() => {
                        this.scrollCommentsToBottom();
                    });
                } else {
                    const err = await response.json();
                    this.showError(err.message || 'Failed to send comment.');
                }
            } catch (error) {
                console.error("Error submitting comment:", error);
                this.showError('An error occurred while sending comment.');
            } finally {
                this.isSubmittingComment = false;
            }
        },

        async deleteTaskComment(commentId) {
            if (!this.permissions.canComment) return;
            if (!confirm('Are you sure you want to delete this comment?')) return;
            this.deletingCommentId = commentId;

            try {
                const response = await fetch(`/api/qc/comments/${commentId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                if (response.ok) {
                    this.activeTaskComments = this.activeTaskComments.filter(c => c.id !== commentId);
                    if (this.activeTask) {
                        this.activeTask.comments_count = this.activeTaskComments.length;
                    }
                    const cardTask = this.tasks.find(t => t.id === this.activeTask.id);
                    if (cardTask) {
                        cardTask.comments_count = this.activeTaskComments.length;
                    }
                    this.showSuccess('Comment successfully deleted.');
                } else {
                    const err = await response.json();
                    this.showError(err.message || 'Failed to delete comment.');
                }
            } catch (error) {
                console.error("Error deleting comment:", error);
                this.showError('An error occurred while deleting comment.');
            } finally {
                this.deletingCommentId = null;
            }
        }
    }
}
</script>
@endsection
