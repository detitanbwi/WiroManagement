@extends('layouts.app')

@section('title', 'QA/QC Dashboard - ' . $project->title)

@section('content')
<div class="h-full flex flex-col bg-gray-50" x-data="qcDashboard()">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200 bg-white flex justify-between items-center shrink-0">
        <div>
            <h1 class="text-xl font-bold text-gray-800">QA/QC Workflow</h1>
            <p class="text-sm text-gray-500 mt-1">Project: <span class="font-semibold">{{ $project->title }}</span></p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('projects.show', $project->id) }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 shadow-sm transition-colors">
                Back to Project
            </a>
            <button @click="openNewTaskModal()" class="px-4 py-2 bg-primary text-white rounded-md text-sm font-medium hover:bg-blue-800 shadow-sm transition-colors">
                New Task
            </button>
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
                <div class="w-80 flex flex-col max-h-full bg-gray-100/50 rounded-xl border border-gray-200 shrink-0">
                    <div class="px-4 py-3 border-b border-gray-200/80 bg-gray-100 rounded-t-xl shrink-0 flex justify-between items-center">
                        <h3 class="font-semibold text-gray-700 text-sm tracking-wide" x-text="column.title"></h3>
                        <span class="bg-gray-200 text-gray-600 text-xs px-2 py-0.5 rounded-full font-medium" x-text="getTasksByColumn(column.id).length"></span>
                    </div>
                    
                    <div class="p-3 flex-1 overflow-y-auto space-y-3">
                        <template x-for="task in getTasksByColumn(column.id)" :key="task.id">
                            <!-- Task Card -->
                            <div 
                                @click="openTaskModal(task)"
                                class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 hover:border-gray-300 hover:shadow transition-all group cursor-pointer"
                            >
                                <div class="flex justify-between items-start mb-1.5">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider font-mono" x-text="task.code"></span>
                                    <template x-if="task.hasActiveBug">
                                        <span class="inline-flex items-center gap-1 bg-red-50 text-red-700 text-[10px] font-bold px-2 py-0.5 rounded border border-red-200">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            BLOCKED
                                        </span>
                                    </template>
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

                                    <!-- Comment Counter Badge -->
                                    <template x-if="task.comments_count > 0">
                                        <div class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100" title="Komentar / Percakapan">
                                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                            <span x-text="task.comments_count"></span>
                                        </div>
                                    </template>
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
                    <button @click.stop="openNewTestCaseModal(null)" class="px-3 py-1.5 bg-white border border-gray-300 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50 shadow-sm transition-colors">
                        + Add Root Test Case
                    </button>
                    <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="{'rotate-180': !isTestCasesExpanded}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
            
            <div class="p-0" x-show="isTestCasesExpanded" x-collapse>

                <template x-for="tc in flatTestCases" :key="tc.id">
                    <div class="transition-all"
                         @dragover.prevent="handleDragOver(tc, $event)"
                         @dragleave="handleDragLeave(tc, $event)"
                         @drop="dropTestCase(tc.id)"
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
                             draggable="true"
                             @dragstart="startDragging(tc, $event)">
                        <div class="flex items-center gap-2">
                            <!-- Drag Handle -->
                            <div class="cursor-grab text-gray-300 hover:text-gray-500 mr-1" title="Drag to move">
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
                            <span class="text-sm font-medium text-gray-800" x-text="tc.title"></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <template x-if="tc.bug">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded border font-mono tracking-wide" 
                                      :class="tc.bug.status === 'resolved' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'"
                                      :title="'Bug: ' + tc.bug.code + ' (' + tc.bug.status + ')'">
                                    <svg class="w-3 h-3 shrink-0" :class="tc.bug.status === 'resolved' ? 'text-green-600' : 'text-red-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path x-show="tc.bug.status === 'resolved'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        <path x-show="tc.bug.status !== 'resolved'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <span x-text="tc.bug.code"></span>
                                </span>
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
                            <button @click.stop="openEditTestCaseModal(tc)" class="opacity-0 group-hover:opacity-100 text-blue-500 hover:text-blue-700 p-1 rounded-md transition-opacity" title="Edit Test Case">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                            <button @click.stop="duplicateTestCase(tc)" class="opacity-0 group-hover:opacity-100 text-yellow-500 hover:text-yellow-700 p-1 rounded-md transition-opacity" title="Duplicate Test Case">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
                            </button>
                            <button @click.stop="deleteTestCase(tc.id)" class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 p-1 rounded-md transition-opacity" title="Delete Test Case">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                            <button @click.stop="openRunTestModal(tc)" class="opacity-0 group-hover:opacity-100 text-green-600 hover:text-green-800 p-1 rounded-md transition-opacity" title="Run Test">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </button>
                            <button @click.stop="openNewTestCaseModal(tc)" class="opacity-0 group-hover:opacity-100 text-xs text-blue-600 hover:text-blue-800 font-medium transition-opacity" title="Add Sub Test">+ Sub Test</button>
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
                        <button @click="bugFilterTab = 'all'" 
                                :class="{'border-red-600 text-red-700 font-bold': bugFilterTab === 'all', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium': bugFilterTab !== 'all'}"
                                class="whitespace-nowrap pb-3 pt-2 px-1 border-b-2 text-sm transition-colors flex items-center gap-2">
                            Semua Bug
                            <span class="bg-gray-100 text-gray-700 py-0.5 px-2 rounded-full text-xs font-semibold" x-text="projectBugs.length"></span>
                        </button>
                        <button @click="bugFilterTab = 'active'" 
                                :class="{'border-amber-500 text-amber-800 font-bold': bugFilterTab === 'active', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium': bugFilterTab !== 'active'}"
                                class="whitespace-nowrap pb-3 pt-2 px-1 border-b-2 text-sm transition-colors flex items-center gap-2">
                            Bug Aktif (Open)
                            <span class="bg-amber-100 text-amber-800 py-0.5 px-2 rounded-full text-xs font-bold" x-text="projectBugs.filter(b => b.status !== 'resolved').length"></span>
                        </button>
                        <button @click="bugFilterTab = 'solved'" 
                                :class="{'border-green-600 text-green-700 font-bold': bugFilterTab === 'solved', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium': bugFilterTab !== 'solved'}"
                                class="whitespace-nowrap pb-3 pt-2 px-1 border-b-2 text-sm transition-colors flex items-center gap-2">
                            Bug Solved / Resolved
                            <span class="bg-green-100 text-green-800 py-0.5 px-2 rounded-full text-xs font-bold" x-text="projectBugs.filter(b => b.status === 'resolved').length"></span>
                        </button>
                    </nav>
                </div>

                <!-- Selection Action Toolbar -->
                <div x-show="selectedBugIds.length > 0" x-cloak class="px-6 py-2.5 bg-blue-50 border-b border-blue-200 flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center bg-blue-600 text-white text-xs font-bold px-2.5 py-0.5 rounded-full" x-text="selectedBugIds.length + ' Bug Terpilih'"></span>
                        <span class="text-xs text-blue-900 font-medium">Pilih beberapa bug untuk digabungkan menjadi satu Kanban Task baru.</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="openBulkConvertModal()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-xs font-bold shadow-xs transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            <span>Buat 1 Task Kanban (<span x-text="selectedBugIds.length"></span> Bug)</span>
                        </button>
                        <button type="button" @click="selectedBugIds = []" class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-md text-xs font-medium transition-colors">
                            Batal
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-center w-10">
                                    <input type="checkbox" 
                                           @change="toggleSelectAllBugs($event)" 
                                           :checked="isAllBugsSelected" 
                                           x-effect="$el.indeterminate = isSomeBugsSelected"
                                           title="Pilih Semua Bug"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4 cursor-pointer">
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bug Details</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity / Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Test Case</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kanban Task</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="bug in filteredProjectBugs" :key="bug.id">
                                <tr class="hover:bg-gray-50 transition-colors"
                                    :class="{
                                        'bg-blue-50/60': isBugSelected(bug.id),
                                        'bg-green-50/20': bug.status === 'resolved' && !isBugSelected(bug.id)
                                    }">
                                    <td class="px-4 py-4 text-center whitespace-nowrap">
                                        <input type="checkbox" 
                                               :checked="isBugSelected(bug.id)" 
                                               @change="toggleBugSelection(bug.id)" 
                                               @click.stop 
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4 cursor-pointer">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-bold px-2 py-0.5 rounded border w-max font-mono"
                                                      :class="bug.status === 'resolved' ? 'text-green-700 bg-green-50 border-green-200' : 'text-red-700 bg-red-50 border-red-100'"
                                                      x-text="bug.code"></span>
                                                <template x-if="bug.status === 'resolved'">
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-green-700 bg-green-100 px-1.5 py-0.5 rounded border border-green-200">
                                                        <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                        SOLVED
                                                    </span>
                                                </template>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900" x-text="bug.description"></span>
                                            <div class="flex items-center gap-3 text-xs text-gray-500 mt-1">
                                                <span x-text="'Dilaporkan: ' + (bug.created_at_human || bug.created_at)"></span>
                                                <template x-if="bug.status === 'resolved' && bug.updated_at">
                                                    <span class="text-green-700 font-medium" x-text="'Diselesaikan: ' + bug.updated_at"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col gap-1">
                                            <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded w-max" 
                                                  :class="{
                                                    'bg-red-100 text-red-700 border border-red-200': bug.severity === 'Critical' || bug.severity === 'High',
                                                    'bg-yellow-100 text-yellow-700 border border-yellow-200': bug.severity === 'Medium',
                                                    'bg-green-100 text-green-700 border border-green-200': bug.severity === 'Low'
                                                  }" x-text="bug.severity || 'Unknown'"></span>
                                            <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border w-max"
                                                  :class="{
                                                    'bg-green-100 text-green-700 border border-green-200': bug.status === 'resolved',
                                                    'bg-red-100 text-red-700 border border-red-200': bug.status === 'open',
                                                    'bg-amber-100 text-amber-700 border border-amber-200': bug.status === 'in_progress',
                                                    'bg-gray-100 text-gray-600 border border-gray-200': bug.status !== 'resolved' && bug.status !== 'open' && bug.status !== 'in_progress'
                                                  }"
                                                  x-text="bug.status"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <template x-if="bug.test_case">
                                            <div class="text-sm text-gray-600">
                                                <div class="flex items-center gap-1.5 mb-0.5">
                                                    <span class="font-mono text-xs text-blue-600 font-bold" x-text="bug.test_case.code"></span>
                                                    <span class="text-[9px] uppercase font-bold px-1.5 py-0.2 rounded border"
                                                          :class="bug.test_case.status === 'passed' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200'"
                                                          x-text="bug.test_case.status"></span>
                                                </div>
                                                <span class="text-xs text-gray-700" x-text="bug.test_case.title"></span>
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
                                        <template x-if="!bug.project_task && bug.status !== 'resolved'">
                                            <button @click="convertBugToTask(bug.id)" :disabled="convertingBugId === bug.id" :class="{'opacity-75 cursor-wait': convertingBugId === bug.id}" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none transition-colors">
                                                <svg x-show="convertingBugId === bug.id" class="animate-spin -ml-0.5 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                <svg x-show="convertingBugId !== bug.id" class="mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                                <span x-text="convertingBugId === bug.id ? 'Creating...' : 'Create Task'"></span>
                                            </button>
                                        </template>
                                        <button @click="deleteBug(bug.id)" class="text-gray-400 hover:text-red-600 p-1.5 rounded-md hover:bg-red-50 transition-colors" title="Delete Bug">
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
                        <template x-if="bugFilterTab === 'solved'">
                            <span>Belum ada bug yang berstatus Solved / Resolved.</span>
                        </template>
                        <template x-if="bugFilterTab === 'active'">
                            <span class="text-green-600 font-semibold">Tidak ada bug aktif saat ini! Semua bug telah terselesaikan.</span>
                        </template>
                        <template x-if="bugFilterTab === 'all'">
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
                            <div class="mt-3 flex gap-2">
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
                                        <button @click="updateTaskColumn(activeTask.id, 'qc_in_progress')" :disabled="isMovingTask" :class="{'opacity-75 cursor-wait': isMovingTask}" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded shadow-sm text-white bg-purple-600 hover:bg-purple-700 transition-colors">
                                            <svg x-show="movingToColumn === 'qc_in_progress'" class="animate-spin mr-1.5 h-3 w-3 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span x-text="movingToColumn === 'qc_in_progress' ? 'Moving...' : 'Start QC Process &rarr;'"></span>
                                        </button>
                                    </div>
                                </template>
                                
                                <template x-if="activeTask?.column_id === 'qc_in_progress'">
                                    <div class="flex gap-2">
                                        <button @click="updateTaskColumn(activeTask.id, 'ready_for_qc')" :disabled="isMovingTask" :class="{'opacity-75 cursor-wait': isMovingTask}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-semibold rounded shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                            <svg x-show="movingToColumn === 'ready_for_qc'" class="animate-spin mr-1.5 h-3 w-3 text-gray-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span x-text="movingToColumn === 'ready_for_qc' ? 'Moving...' : '&larr; Back to Ready for QC'"></span>
                                        </button>
                                        <button @click="updateTaskColumn(activeTask.id, 'in_progress')" :disabled="isMovingTask" :class="{'opacity-75 cursor-wait': isMovingTask}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-semibold rounded shadow-sm text-red-600 bg-red-50 hover:bg-red-100 transition-colors">
                                            <svg x-show="movingToColumn === 'in_progress'" class="animate-spin mr-1.5 h-3 w-3 text-red-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span x-text="movingToColumn === 'in_progress' ? 'Moving...' : '&#x21BA; Return to Developer'"></span>
                                        </button>
                                        <button @click="updateTaskColumn(activeTask.id, 'done')" :disabled="isMovingTask" :class="{'opacity-75 cursor-wait': isMovingTask}" class="inline-flex items-center px-3.5 py-1.5 border border-transparent text-xs font-bold rounded shadow-sm text-white bg-green-600 hover:bg-green-700 transition-colors">
                                            <svg x-show="movingToColumn === 'done'" class="animate-spin mr-1.5 h-3 w-3 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span x-text="movingToColumn === 'done' ? 'Passing QC...' : 'Pass QC & Mark Done &check;'"></span>
                                        </button>
                                    </div>
                                </template>
                                
                                <template x-if="activeTask?.column_id === 'done'">
                                    <button @click="updateTaskColumn(activeTask.id, 'qc_in_progress')" :disabled="isMovingTask" :class="{'opacity-75 cursor-wait': isMovingTask}" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-semibold rounded shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                        <svg x-show="movingToColumn === 'qc_in_progress'" class="animate-spin mr-1.5 h-3 w-3 text-gray-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <span x-text="movingToColumn === 'qc_in_progress' ? 'Moving...' : '&larr; Reopen (Back to QC)'"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="deleteTask(activeTask.id)" type="button" class="bg-red-50 p-1 rounded text-red-500 hover:text-red-700 hover:bg-red-100 transition-colors focus:outline-none" title="Delete Task">
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
                                        <span class="text-xs text-gray-500 font-medium" x-text="activeTask.testCases.length + ' Test Cases terkait task ini'"></span>
                                        <button type="button" 
                                                @click="passAllTaskTestCases(activeTask.id)" 
                                                :disabled="isPassingAllTests"
                                                class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 rounded-md text-xs font-semibold shadow-xs transition-colors disabled:opacity-50">
                                            <svg x-show="isPassingAllTests" class="animate-spin h-3 w-3 text-green-700" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <svg x-show="!isPassingAllTests" class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Pass Semua Test Case</span>
                                        </button>
                                    </div>
                                    <template x-for="tc in activeTask.testCases" :key="tc.id">
                                        <div class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg shadow-sm" :class="{'border-red-200 bg-red-50/20': tc.is_from_bug}">
                                            <div>
                                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                    <span class="text-xs font-bold text-primary bg-blue-50 px-2 py-0.5 rounded border border-blue-100" x-text="tc.code"></span>
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
                                                <button @click.stop="openRunTestModal(tc)" class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700 transition-colors shadow-sm flex items-center gap-1">
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
                                    <span>Diskusi langsung antar <strong>Programmer</strong> dan <strong>QC</strong> terkait pengerjaan dan verifikasi task ini.</span>
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
                                        <h4 class="text-sm font-semibold text-gray-800">Belum ada percakapan</h4>
                                        <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">Mulai diskusi atau koordinasi antara Programmer dan tim QC dengan menulis komentar pertama di bawah.</p>
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
                                                <template x-if="c.can_delete">
                                                    <button type="button" 
                                                            @click="deleteTaskComment(c.id)" 
                                                            :disabled="deletingCommentId === c.id"
                                                            class="text-gray-400 hover:text-red-600 p-1 rounded hover:bg-red-50 transition-colors" 
                                                            title="Hapus Komentar">
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
                                                                <img :src="'/storage/' + c.attachment_path" class="max-h-48 max-w-xs object-cover rounded-lg group-hover:scale-102 transition-transform duration-200" alt="Lampiran">
                                                                <span class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-semibold gap-1 transition-opacity">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                    Buka Gambar
                                                                </span>
                                                            </a>
                                                        </div>
                                                    </template>
                                                    <template x-if="!isImageAttachment(c.attachment_path)">
                                                        <a :href="'/storage/' + c.attachment_path" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-blue-50 text-gray-700 hover:text-blue-700 border border-gray-200 hover:border-blue-200 rounded-lg text-xs font-medium transition-colors shadow-xs">
                                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                            <span x-text="getFilename(c.attachment_path)"></span>
                                                            <span class="text-[10px] text-gray-400 font-normal">&darr; Unduh</span>
                                                        </a>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Input Form (Sticky Bottom) -->
                            <div class="border-t border-gray-200 pt-3 bg-white mt-auto">
                                <!-- Attached file badge if selected -->
                                <div x-show="newCommentFile" x-cloak class="mb-2 flex items-center justify-between p-2 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-900">
                                    <div class="flex items-center gap-2 truncate">
                                        <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <span class="font-medium truncate" x-text="newCommentFile?.name"></span>
                                        <span class="text-gray-500 text-[10px]" x-text="formatFileSize(newCommentFile?.size)"></span>
                                    </div>
                                    <button type="button" @click="removeCommentFile()" class="text-gray-400 hover:text-red-600 p-0.5 rounded transition-colors ml-2 shrink-0" title="Batal lampiran">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>

                                <div class="relative">
                                    <textarea x-model="newCommentText"
                                              @keydown.ctrl.enter="submitTaskComment()"
                                              @keydown.meta.enter="submitTaskComment()"
                                              placeholder="Tulis pesan atau komentar untuk programmer / tim QC... (Ctrl + Enter untuk kirim)"
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

                            <div class="grid grid-cols-2 gap-4">
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
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
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
                                    <option value="qc_in_progress">QC in Progress</option>
                                    <option value="done">Done</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Attachment (Optional)</label>
                                <input type="file" id="task_attachment" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded-md shadow-sm outline-none cursor-pointer">
                                <p class="text-xs text-gray-500 mt-1">PNG, JPG, PDF, DOCX up to 10MB</p>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" @click="closeNewTaskModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-800" :disabled="isSubmitting" :class="{'opacity-50 cursor-not-allowed': isSubmitting}">
                                <span x-show="!isSubmitting">Save Task</span>
                                <span x-show="isSubmitting">Saving...</span>
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
                            Buat 1 Task Kanban dari <span x-text="selectedBugIds.length"></span> Bug
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Semua bug terpilih akan digabungkan ke dalam 1 kartu task baru di Kanban Board.</p>
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
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Daftar Bug Terpilih (<span x-text="selectedBugIds.length"></span>)</label>
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
                                            <button type="button" @click="toggleBugSelection(b.id)" class="text-gray-400 hover:text-red-500 p-1 shrink-0 ml-2" title="Hapus dari pilihan">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Judul Task Kanban <span class="text-red-500">*</span></label>
                                <input type="text" x-model="bulkTask.title" required placeholder="Contoh: Perbaikan kumpulan bug ..." class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
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
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Kolom Awal</label>
                                    <select x-model="bulkTask.column_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                                        <option value="todo">To Do</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="ready_for_qc">Ready for QC</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Tambahan</label>
                                <textarea rows="3" x-model="bulkTask.description" placeholder="Catatan perbaikan / deskripsi gabungan..." class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none font-mono text-xs"></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" @click="isBulkTaskModalOpen = false" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Batal
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
                        <span x-show="editingTestCaseId">Edit Test Case</span>
                        <span x-show="!editingTestCaseId" x-text="parentTestCase ? 'Add Sub Test Case' : 'Add Root Test Case'"></span>
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
                            <span class="font-bold text-blue-800" x-text="parentTestCase.code + ' - ' + parentTestCase.title"></span>
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

                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <div class="flex justify-between items-center mb-3">
                                        <label class="block text-sm font-medium text-gray-700">Test Steps</label>
                                        <button type="button" @click="newTestCase.steps.push('')" class="text-xs text-primary hover:text-blue-800 font-bold px-2 py-1 bg-white border border-gray-300 rounded shadow-sm">+ Add Step</button>
                                    </div>
                                    <div class="space-y-2 max-h-48 overflow-y-auto pr-2">
                                        <template x-for="(step, index) in newTestCase.steps" :key="index">
                                            <div class="flex items-start gap-2 group">
                                                <span class="text-xs font-bold text-gray-400 mt-2 w-4 shrink-0 text-right" x-text="(index + 1) + '.'"></span>
                                                <input type="text" x-model="newTestCase.steps[index]" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-1.5 border outline-none" placeholder="Describe step...">
                                                <button type="button" @click="newTestCase.steps.splice(index, 1)" class="text-red-400 hover:text-red-600 mt-1 opacity-0 group-hover:opacity-100 transition-opacity" x-show="newTestCase.steps.length > 1" title="Remove Step">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
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
                                <span x-show="!isSubmittingTestCase" x-text="editingTestCaseId ? 'Update Test Case' : 'Save Test Case'"></span>
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
                                                <span class="font-mono font-bold px-2 py-0.5 rounded border"
                                                      :class="b.status === 'resolved' ? 'bg-green-100 text-green-800 border-green-300' : 'bg-red-100 text-red-800 border-red-300'"
                                                      x-text="b.code"></span>
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
                                            <template x-if="b.attachment_path">
                                                <a :href="'/storage/' + b.attachment_path" target="_blank" class="text-blue-600 hover:underline flex items-center gap-0.5">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                                    Lampiran
                                                </a>
                                            </template>
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
        bugFilterTab: 'all', // 'all', 'active', 'solved'
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
        createKanbanTask: false,
        isSubmittingTest: false,
        bugAssigneeId: '',

        projectId: '{{ $project->id }}',

        isNewTaskModalOpen: false,
        isSubmitting: false,
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
        parentTestCase: null,
        newTestCase: {
            title: '',
            preconditions: '',
            expected: '',
            steps: [''],
            payload: '',
            complexity: 'Low',
            priority: 'Medium',
            test_type: 'Functional',
            automation_status: 'Manual'
        },

        // View Test Case State
        isViewTestCaseModalOpen: false,
        viewingTestCase: null,
        
        // Error & Notification State
        errorMessage: '',
        successMessage: '',

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
            if (!this.draggedTestCase) return;
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
                    this.showError(data.message || 'Gagal memindahkan test case.');
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
            if (this.convertingBugId) return;
            this.convertingBugId = bugId;
            try {
                const response = await fetch(`/api/qc/bugs/${bugId}/convert`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({}) // Assignee empty for now, defaults to Todo
                });

                if (response.ok) {
                    const resData = await response.json();
                    await this.fetchTasks();
                    await this.fetchProjectBugs();
                    this.isKanbanExpanded = true;
                    this.showSuccess('Task baru (' + (resData.task?.code || '') + ') berhasil dibuat di Kanban Board!');
                } else {
                    const data = await response.json();
                    this.showError(data.message || 'Gagal mengubah bug menjadi task.');
                }
            } catch (error) {
                console.error("Error converting bug to task:", error);
                this.showError('Terjadi kesalahan saat memproses permintaan.');
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

        get filteredProjectBugs() {
            if (this.bugFilterTab === 'active') {
                return this.projectBugs.filter(b => b.status !== 'resolved');
            }
            if (this.bugFilterTab === 'solved') {
                return this.projectBugs.filter(b => b.status === 'resolved');
            }
            return this.projectBugs;
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
            if (this.selectedBugIds.length === 0) return;
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
            if (this.isSubmittingBulkConvert || this.selectedBugIds.length === 0) return;
            this.isSubmittingBulkConvert = true;

            try {
                const response = await fetch(`/api/projects/${this.projectId}/qc/bugs/bulk-convert`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
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
                    this.showSuccess(`Task baru (${data.task.code}) berhasil dibuat dari ${data.count} bug!`);
                } else {
                    const err = await response.json();
                    this.showError(err.message || 'Gagal membuat task dari bug terpilih.');
                }
            } catch (error) {
                console.error("Error bulk converting bugs to task:", error);
                this.showError('Terjadi kesalahan saat membuat task.');
            } finally {
                this.isSubmittingBulkConvert = false;
            }
        },

        async passAllTaskTestCases(taskId) {
            if (this.isPassingAllTests) return;
            if (!confirm('Apakah Anda yakin ingin menandai semua Test Case untuk task ini menjadi PASSED dan menyelesaikan bug terkait?')) return;
            
            this.isPassingAllTests = true;
            try {
                const response = await fetch(`/api/qc/tasks/${taskId}/pass-test-cases`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
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
                    this.showSuccess('Semua test case berhasil dinyatakan PASSED dan bug terkait telah terselesaikan!');
                } else {
                    this.showError('Gagal memperbarui status test cases.');
                }
            } catch (error) {
                console.error('Error passing all test cases:', error);
                this.showError('Terjadi kesalahan saat memproses permintaan.');
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

        openViewTestCaseModal(tc) {
            this.viewingTestCase = tc;
            this.isViewTestCaseModalOpen = true;
        },

        closeViewTestCaseModal() {
            this.isViewTestCaseModalOpen = false;
            setTimeout(() => {
                this.viewingTestCase = null;
            }, 300);
        },

        openNewTestCaseModal(parentTC = null) {
            this.parentTestCase = parentTC;
            this.editingTestCaseId = null;
            this.newTestCase = {
                title: '',
                preconditions: '',
                expected: '',
                steps: [''],
                payload: '',
                complexity: 'Low',
                priority: 'Medium',
                test_type: 'Functional',
                automation_status: 'Manual'
            };
            this.isNewTestCaseModalOpen = true;
        },

        openEditTestCaseModal(tc) {
            this.editingTestCaseId = tc.id;
            this.parentTestCase = null;
            this.newTestCase = {
                title: tc.title || '',
                preconditions: tc.preconditions || '',
                expected: tc.expected || '',
                steps: (tc.steps && tc.steps.length > 0) ? [...tc.steps] : [''],
                payload: tc.payload || '',
                complexity: tc.complexity || 'Low',
                priority: tc.priority || 'Medium',
                test_type: tc.test_type || 'Functional',
                automation_status: tc.automation_status || 'Manual'
            };
            this.isNewTestCaseModalOpen = true;
        },

        duplicateTestCase(tc) {
            this.editingTestCaseId = null;
            this.parentTestCase = tc.parent_id ? {id: tc.parent_id} : null; // Keep the same parent if it's a child
            this.newTestCase = {
                title: tc.title ? tc.title + ' (Copy)' : '',
                preconditions: tc.preconditions || '',
                expected: tc.expected || '',
                steps: (tc.steps && tc.steps.length > 0) ? [...tc.steps] : [''],
                payload: tc.payload || '',
                complexity: tc.complexity || 'Low',
                priority: tc.priority || 'Medium',
                test_type: tc.test_type || 'Functional',
                automation_status: tc.automation_status || 'Manual'
            };
            this.isNewTestCaseModalOpen = true;
        },

        closeNewTestCaseModal() {
            this.isNewTestCaseModalOpen = false;
            setTimeout(() => {
                this.editingTestCaseId = null;
            }, 300);
        },

        async submitNewTestCase() {
            if (this.isSubmittingTestCase) return;
            this.isSubmittingTestCase = true;

            const payload = {
                title: this.newTestCase.title,
                preconditions: this.newTestCase.preconditions,
                expected: this.newTestCase.expected,
                steps: this.newTestCase.steps.filter(s => s.trim() !== ''),
                payload: this.newTestCase.payload,
                complexity: this.newTestCase.complexity,
                priority: this.newTestCase.priority,
                test_type: this.newTestCase.test_type,
                automation_status: this.newTestCase.automation_status,
                parent_id: this.parentTestCase ? this.parentTestCase.id : null
            };

            try {
                let response;
                if (this.editingTestCaseId) {
                    response = await fetch(`/api/qc/test-cases/${this.editingTestCaseId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    });
                } else {
                    response = await fetch(`/api/projects/${this.projectId}/qc/test-cases`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    });
                }

                if (response.ok) {
                    // Expand parent automatically so the user sees the newly added child (only if new)
                    if (!this.editingTestCaseId && this.parentTestCase) {
                        this.expandTestCase(this.projectTestCases, this.parentTestCase.id);
                    }
                    await this.fetchProjectTestCases();
                    this.closeNewTestCaseModal();
                } else {
                    this.showError('Gagal menyimpan test case.');
                }
            } catch (error) {
                console.error('Error submitting new test case:', error);
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

        openTaskModal(task) {
            this.activeTask = task;
            this.activeTaskComments = task.comments || [];
            this.activeTab = 'test_cases';
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

        openTaskModalById(taskId) {
            const task = this.tasks.find(t => t.id === taskId);
            if (task) {
                this.openTaskModal(task);
            }
        },

        async deleteTask(taskId) {
            if (!confirm('Are you sure you want to delete this Kanban Task? This action cannot be undone.')) return;
            try {
                const response = await fetch(`/api/qc/tasks/${taskId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (response.ok) {
                    this.closeTaskModal();
                    await this.fetchTasks();
                    await this.fetchProjectBugs();
                }
            } catch (error) {
                console.error("Error deleting task:", error);
            }
        },

        async deleteTestCase(testCaseId) {
            if (!confirm('Are you sure you want to delete this Test Case? This will also remove associated bugs.')) return;
            try {
                const response = await fetch(`/api/qc/test-cases/${testCaseId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (response.ok) {
                    await this.fetchProjectTestCases();
                    await this.fetchProjectBugs();
                }
            } catch (error) {
                console.error("Error deleting test case:", error);
            }
        },

        async deleteBug(bugId) {
            if (!confirm('Are you sure you want to delete this Bug?')) return;
            try {
                const response = await fetch(`/api/qc/bugs/${bugId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (response.ok) {
                    await this.fetchProjectBugs();
                }
            } catch (error) {
                console.error("Error deleting bug:", error);
            }
        },

        openRunTestModal(testCase) {
            this.activeTest = testCase;
            this.isReportingBug = false;
            
            if (testCase.bug) {
                this.bugDescription = testCase.bug.description || '';
                this.bugActualResult = testCase.bug.actual_result || '';
                this.bugSeverity = testCase.bug.severity || 'Medium';
                this.bugEnvironment = testCase.bug.environment || '';
            } else {
                this.bugDescription = '';
                this.bugActualResult = '';
                this.bugSeverity = 'Medium';
                this.bugEnvironment = '';
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
            setTimeout(() => {
                this.activeTest = null;
            }, 300);
        },

        async submitTestResult(result) {
            if (this.activeTest) {
                this.isSubmittingTest = true;
                try {
                    const formData = new FormData();
                    formData.append('status', result);
                    formData.append('bug_description', this.bugDescription);
                    formData.append('steps_to_reproduce', this.stepsToReproduce);
                    formData.append('severity', this.bugSeverity);
                    formData.append('actual_result', this.bugActualResult);
                    formData.append('environment', this.bugEnvironment);
                    formData.append('create_task', this.createKanbanTask);
                    formData.append('assignee_id', this.bugAssigneeId);
                    
                    const fileInput = document.getElementById('bug_attachment');
                    if (fileInput && fileInput.files[0]) {
                        formData.append('attachment', fileInput.files[0]);
                    }

                    const response = await fetch(`/api/qc/test-cases/${this.activeTest.id}/result`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.activeTest.status = result;
                        
                        await this.fetchProjectTestCases();
                        await this.fetchTasks();
                        await this.fetchProjectBugs();
                        
                        // Resync activeTask if it is currently open
                        if (this.activeTask) {
                            const updatedTask = this.tasks.find(t => t.id === this.activeTask.id);
                            if (updatedTask) {
                                this.activeTask = updatedTask;
                            }
                        }

                        // Resync viewingTestCase if it is currently open
                        if (this.viewingTestCase && this.viewingTestCase.id === this.activeTest.id) {
                            this.viewingTestCase.status = result;
                            const findTc = (list) => {
                                for (let item of list) {
                                    if (item.id === this.activeTest.id) return item;
                                    if (item.children) {
                                        const found = findTc(item.children);
                                        if (found) return found;
                                    }
                                }
                                return null;
                            };
                            const refreshedTc = findTc(this.projectTestCases);
                            if (refreshedTc) {
                                this.viewingTestCase = refreshedTc;
                            }
                        }
                        
                        if (result === 'failed') {
                            const bugCode = data.bug ? data.bug.code : '';
                            this.showSuccess(`Test Case gagal. Bug tracker (${bugCode}) berhasil dihubungkan!`);
                        } else {
                            this.showSuccess('Test Case berhasil (Passed) dan bug terkait telah diselesaikan.');
                        }
                        
                        this.closeRunTestModal();
                    } else {
                        this.showError('Gagal memperbarui status test case.');
                    }
                } catch (error) {
                    console.error('Error submitting test result:', error);
                } finally {
                    this.isSubmittingTest = false;
                }
            }
        },

        updateTestCaseStatusInTree(cases, id, status) {
            for (let tc of cases) {
                if (tc.id === id) {
                    tc.status = status;
                    return true;
                }
                if (tc.children && tc.children.length > 0) {
                    if (this.updateTestCaseStatusInTree(tc.children, id, status)) return true;
                }
            }
            return false;
        },

        openNewTaskModal() {
            this.newTask = {
                title: '',
                description: '',
                assignee_id: '',
                column_id: 'todo'
            };
            this.isNewTaskModalOpen = true;
        },

        closeNewTaskModal() {
            this.isNewTaskModalOpen = false;
        },

        async submitNewTask() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;

            try {
                const formData = new FormData();
                formData.append('title', this.newTask.title);
                formData.append('description', this.newTask.description);
                formData.append('assignee_id', this.newTask.assignee_id);
                formData.append('column_id', this.newTask.column_id);
                
                const taskFileInput = document.getElementById('task_attachment');
                if (taskFileInput && taskFileInput.files[0]) {
                    formData.append('attachment', taskFileInput.files[0]);
                }

                const response = await fetch(`/api/projects/${this.projectId}/qc/tasks`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });

                if (response.ok) {
                    await this.fetchTasks();
                    this.closeNewTaskModal();
                } else {
                    this.showError('Gagal menyimpan task baru.');
                }
            } catch (error) {
                console.error('Error submitting new task:', error);
            } finally {
                this.isSubmitting = false;
            }
        },
        
        async updateTaskColumn(taskId, columnId) {
            if (this.isMovingTask) return;
            this.isMovingTask = true;
            this.movingToColumn = columnId;
            
            try {
                const response = await fetch(`/api/qc/tasks/${taskId}/move`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
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
                        this.showSuccess('Task selesai! Semua test case terkait telah dinyatakan PASSED dan bug terselesaikan.');
                    }
                }
            } catch (error) {
                console.error('Error updating task column:', error);
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
            if (!this.activeTask || this.isSubmittingComment) return;
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
                    this.showError(err.message || 'Gagal mengirim komentar.');
                }
            } catch (error) {
                console.error("Error submitting comment:", error);
                this.showError('Terjadi kesalahan saat mengirim komentar.');
            } finally {
                this.isSubmittingComment = false;
            }
        },

        async deleteTaskComment(commentId) {
            if (!confirm('Apakah Anda yakin ingin menghapus komentar ini?')) return;
            this.deletingCommentId = commentId;

            try {
                const response = await fetch(`/api/qc/comments/${commentId}`, {
                    method: 'DELETE',
                    headers: {
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
                    this.showSuccess('Komentar berhasil dihapus.');
                } else {
                    const err = await response.json();
                    this.showError(err.message || 'Gagal menghapus komentar.');
                }
            } catch (error) {
                console.error("Error deleting comment:", error);
                this.showError('Terjadi kesalahan saat menghapus komentar.');
            } finally {
                this.deletingCommentId = null;
            }
        }
    }
}
</script>
@endsection
