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

    <!-- Kanban Board -->
    <div class="flex-1 overflow-x-auto overflow-y-hidden p-6">
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
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider" x-text="task.code"></span>
                                    <template x-if="task.hasActiveBug">
                                        <span class="inline-flex items-center gap-1 bg-red-50 text-red-700 text-[10px] font-bold px-2 py-0.5 rounded border border-red-200">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            BLOCKED
                                        </span>
                                    </template>
                                </div>
                                <h4 class="text-sm font-medium text-gray-800 leading-snug mb-3" x-text="task.title"></h4>
                                <div class="flex justify-between items-center mt-auto">
                                    <div class="flex -space-x-1 overflow-hidden">
                                        <div class="inline-block h-6 w-6 rounded-full bg-blue-100 text-blue-600 ring-2 ring-white flex items-center justify-center text-[10px] font-bold uppercase" x-text="task.assignee.substring(0,2)"></div>
                                    </div>
                                    <template x-if="task.testCasesCount > 0">
                                        <div class="flex items-center text-xs text-gray-500" title="Test Cases">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                            <span x-text="task.passedTests + '/' + task.testCasesCount"></span>
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

    <!-- Global Project Test Cases Box -->
    <div class="px-6 pb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    Project Test Cases (Global)
                </h2>
                <button @click="openNewTestCaseModal(null)" class="px-3 py-1.5 bg-white border border-gray-300 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50 shadow-sm transition-colors">
                    + Add Root Test Case
                </button>
            </div>
            
            <div class="p-0">
                <template x-for="tc in flatTestCases" :key="tc.id">
                    <div class="flex justify-between items-center p-3 border-b border-gray-100 hover:bg-gray-50 transition-colors group"
                         :style="`padding-left: ${tc.level * 2 + 1}rem`">
                        <div class="flex items-center gap-3">
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
                            <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded" 
                                  :class="{
                                    'bg-green-100 text-green-700 border border-green-200': tc.status === 'passed',
                                    'bg-red-100 text-red-700 border border-red-200': tc.status === 'failed',
                                    'bg-gray-100 text-gray-500 border border-gray-200': tc.status === 'pending'
                                  }" x-text="tc.status"></span>
                            <button @click.stop="openViewTestCaseModal(tc)" class="opacity-0 group-hover:opacity-100 text-gray-500 hover:text-gray-800 p-1 rounded-md transition-opacity" title="View Details">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </button>
                            <button @click.stop="openNewTestCaseModal(tc)" class="opacity-0 group-hover:opacity-100 text-xs text-blue-600 hover:text-blue-800 font-medium transition-opacity" title="Add Sub Test">+ Sub Test</button>
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
                                        <button @click="updateTaskColumn(activeTask.id, 'done')" :disabled="isMovingTask" :class="{'opacity-75 cursor-wait': isMovingTask}" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded shadow-sm text-white bg-green-600 hover:bg-green-700 transition-colors">
                                            <svg x-show="movingToColumn === 'done'" class="animate-spin mr-1.5 h-3 w-3 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span x-text="movingToColumn === 'done' ? 'Moving...' : 'Mark as Done &check;'"></span>
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
                        <button @click="closeTaskModal()" type="button" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            <span class="sr-only">Close</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <!-- Tabs Navigation -->
                    <div class="bg-gray-50 border-b border-gray-200 shrink-0 px-6">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button @click="activeTab = 'details'"
                                    :class="activeTab === 'details' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                                Task Details
                            </button>
                            <button @click="activeTab = 'test_cases'"
                                    :class="activeTab === 'test_cases' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                                Test Cases
                                <span class="bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs font-semibold" x-text="activeTask?.testCasesCount"></span>
                            </button>
                            <button @click="activeTab = 'bugs'"
                                    :class="activeTab === 'bugs' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                                Bugs
                                <span x-show="activeTask?.hasActiveBug" class="bg-red-100 text-red-600 py-0.5 px-2 rounded-full text-xs font-semibold">1+</span>
                            </button>
                        </nav>
                    </div>

                    <!-- Modal Body (Scrollable) -->
                    <div class="px-6 py-6 overflow-y-auto flex-1 bg-white">
                        
                        <!-- Tab Content: Details -->
                        <div x-show="activeTab === 'details'">
                            <div class="prose prose-sm max-w-none text-gray-600">
                                <h4 class="text-gray-800 font-semibold mb-2">Description</h4>
                                <p x-text="activeTask?.description || 'No description provided.'"></p>
                            </div>
                        </div>

                        <!-- Tab Content: Test Cases -->
                        <div x-show="activeTab === 'test_cases'">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">Manual QA Executions</h3>
                                <button class="text-sm text-primary hover:text-blue-800 font-medium">+ Add Test Case</button>
                            </div>
                            
                            <div class="overflow-x-auto border border-gray-200 rounded-lg shadow-sm">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">ID</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">Title</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">Pre-conditions</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expected Result</th>
                                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-28">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <template x-for="tc in activeTask?.testCases" :key="tc.id">
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900" x-text="tc.code"></td>
                                                <td class="px-4 py-3 text-sm text-gray-700 font-medium" x-text="tc.title"></td>
                                                <td class="px-4 py-3 text-sm text-gray-600" x-text="tc.preconditions"></td>
                                                <td class="px-4 py-3 text-sm text-gray-600" x-text="tc.expected"></td>
                                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                                    <template x-if="tc.status === 'passed'">
                                                        <div class="flex items-center justify-end gap-2">
                                                            <span class="inline-flex items-center text-green-600 bg-green-50 px-2.5 py-1 rounded-md text-xs font-semibold border border-green-200">
                                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                                Passed
                                                            </span>
                                                            <template x-if="activeTask?.column_id === 'qc_in_progress'">
                                                                <button @click="openRunTestModal(tc)" title="Retest / Change Status" class="text-gray-400 hover:text-primary transition-colors p-1 rounded hover:bg-gray-100">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <template x-if="tc.status === 'failed'">
                                                        <div class="flex items-center justify-end gap-2">
                                                            <span class="inline-flex items-center text-red-600 bg-red-50 px-2.5 py-1 rounded-md text-xs font-semibold border border-red-200">
                                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                                Failed
                                                            </span>
                                                            <template x-if="activeTask?.column_id === 'qc_in_progress'">
                                                                <button @click="openRunTestModal(tc)" title="Retest / Change Status" class="text-gray-400 hover:text-primary transition-colors p-1 rounded hover:bg-gray-100">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <template x-if="tc.status === 'pending'">
                                                        <button @click="openRunTestModal(tc)" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 hover:text-primary focus:outline-none shadow-sm transition-colors">
                                                            <svg class="w-3 h-3 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                            Run Test
                                                        </button>
                                                    </template>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="!activeTask?.testCases || activeTask.testCases.length === 0">
                                            <tr>
                                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                                                    No test cases defined for this task.
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab Content: Bugs -->
                        <div x-show="activeTab === 'bugs'">
                             <template x-if="activeTask?.hasActiveBug">
                                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-md mb-6 shadow-sm">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-red-800">Active Blocker</h3>
                                            <div class="mt-2 text-sm text-red-700">
                                                <p>A recent test failed. The development team has been notified.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div class="space-y-4">
                                <!-- Dummy bugs list based on status -->
                                <template x-if="!activeTask?.hasActiveBug">
                                    <div class="text-center py-10">
                                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">No Bugs Found</h3>
                                        <p class="mt-1 text-sm text-gray-500">All tests have passed or no bugs have been reported yet.</p>
                                    </div>
                                </template>
                                <template x-if="activeTask?.hasActiveBug">
                                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
                                        <div class="flex justify-between items-start mb-2">
                                            <span class="text-xs font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded">BUG-101</span>
                                            <span class="text-xs text-gray-500">Reported recently</span>
                                        </div>
                                        <h4 class="text-sm font-semibold text-gray-800 mb-1">Failed Test Execution</h4>
                                        <p class="text-sm text-gray-600 mb-3">A manual QA test case failed its expected output. Details are logged in the test run history.</p>
                                    </div>
                                </template>
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
                            <button @click="submitTestResult('passed')" class="w-36 h-14 bg-green-500 hover:bg-green-600 text-white rounded-lg font-bold text-lg shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                PASS
                            </button>
                            <button @click="isReportingBug = true" class="w-36 h-14 bg-red-500 hover:bg-red-600 text-white rounded-lg font-bold text-lg shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                FAIL
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Report Bug Form -->
                <div class="px-6 py-6" x-show="isReportingBug" x-cloak>
                    <div class="mb-5 flex items-center gap-2 text-red-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <h3 class="text-lg font-bold">Report Bug</h3>
                    </div>
                    
                    <form @submit.prevent="submitTestResult('failed')">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bug Description <span class="text-red-500">*</span></label>
                                <input type="text" x-model="bugDescription" required placeholder="E.g. Validation message is missing on empty submit" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Steps to Reproduce (Optional)</label>
                                <textarea rows="3" x-model="stepsToReproduce" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none" placeholder="If different from test steps..."></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Attachment (Screenshot/Video)</label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:bg-gray-50 transition-colors cursor-pointer">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600 justify-center">
                                            <span class="relative cursor-pointer bg-white rounded-md font-medium text-primary hover:text-blue-700 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary">
                                                <span>Upload a file</span>
                                            </span>
                                            <p class="pl-1">or drag and drop</p>
                                        </div>
                                        <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" @click="isReportingBug = false" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                                Submit Bug & Fail Test
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
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-gray-200">
                
                <div class="bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        <span x-text="parentTestCase ? 'Add Sub Test Case' : 'Add Root Test Case'"></span>
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
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                                <input type="text" x-model="newTestCase.title" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pre-conditions</label>
                                <textarea rows="2" x-model="newTestCase.preconditions" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none" placeholder="Requirements before executing..."></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Expected Result</label>
                                <textarea rows="2" x-model="newTestCase.expected" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm px-3 py-2 border outline-none" placeholder="What is the expected outcome?"></textarea>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" @click="closeNewTestCaseModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-800" :disabled="isSubmittingTestCase" :class="{'opacity-50 cursor-not-allowed': isSubmittingTestCase}">
                                <span x-show="!isSubmittingTestCase">Save Test Case</span>
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

                    <div x-show="viewingTestCase?.steps && viewingTestCase.steps.length > 0">
                        <h4 class="text-sm font-semibold text-gray-800 mb-2">Test Steps</h4>
                        <ol class="list-decimal pl-5 text-sm text-gray-600 space-y-2">
                            <template x-for="(step, index) in viewingTestCase?.steps" :key="index">
                                <li x-text="step" class="pl-1"></li>
                            </template>
                        </ol>
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
        
        isTaskModalOpen: false,
        activeTask: null,
        activeTab: 'details',
        
        isRunTestOpen: false,
        activeTest: null,
        isReportingBug: false,
        
        bugDescription: '',
        stepsToReproduce: '',

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
        parentTestCase: null,
        newTestCase: {
            title: '',
            preconditions: '',
            expected: ''
        },

        // View Test Case State
        isViewTestCaseModalOpen: false,
        viewingTestCase: null,

        init() {
            this.fetchTasks();
            this.fetchProjectTestCases();
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
            this.newTestCase = {
                title: '',
                preconditions: '',
                expected: ''
            };
            this.isNewTestCaseModalOpen = true;
        },

        closeNewTestCaseModal() {
            this.isNewTestCaseModalOpen = false;
        },

        async submitNewTestCase() {
            if (this.isSubmittingTestCase) return;
            this.isSubmittingTestCase = true;

            const payload = {
                title: this.newTestCase.title,
                preconditions: this.newTestCase.preconditions,
                expected: this.newTestCase.expected,
                parent_id: this.parentTestCase ? this.parentTestCase.id : null
            };

            try {
                const response = await fetch(`/api/projects/${this.projectId}/qc/test-cases`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                if (response.ok) {
                    // Expand parent automatically so the user sees the newly added child
                    if (this.parentTestCase) {
                        this.expandTestCase(this.projectTestCases, this.parentTestCase.id);
                    }
                    await this.fetchProjectTestCases();
                    this.closeNewTestCaseModal();
                } else {
                    alert('Gagal menyimpan test case.');
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
            this.activeTab = 'test_cases';
            this.isTaskModalOpen = true;
        },
        
        closeTaskModal() {
            this.isTaskModalOpen = false;
            setTimeout(() => {
                this.activeTask = null;
            }, 300);
        },

        openRunTestModal(testCase) {
            this.activeTest = testCase;
            this.isReportingBug = false;
            this.bugDescription = '';
            this.stepsToReproduce = '';
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
            if (this.activeTest && this.activeTask) {
                
                try {
                    const response = await fetch(`/api/qc/test-cases/${this.activeTest.id}/result`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            status: result,
                            bug_description: this.bugDescription,
                            steps_to_reproduce: this.stepsToReproduce
                        })
                    });

                    if (response.ok) {
                        this.activeTest.status = result;
                        
                        let passed = 0;
                        let hasBug = false;
                        
                        this.activeTask.testCases.forEach(tc => {
                            if (tc.status === 'passed') passed++;
                            if (tc.status === 'failed') hasBug = true;
                        });
                        
                        this.activeTask.passedTests = passed;
                        this.activeTask.hasActiveBug = hasBug;
                        
                        let newColumn = this.activeTask.column_id;
                        if (hasBug) {
                            newColumn = 'qc_in_progress';
                        } else if (passed === this.activeTask.testCasesCount && passed > 0) {
                            newColumn = 'done';
                        } else {
                            newColumn = 'qc_in_progress';
                        }
                        
                        if (newColumn !== this.activeTask.column_id) {
                            await this.updateTaskColumn(this.activeTask.id, newColumn);
                            this.activeTask.column_id = newColumn;
                        }

                        this.closeRunTestModal();
                    } else {
                        alert('Gagal memperbarui status test case.');
                    }
                } catch (error) {
                    console.error('Error submitting test result:', error);
                }
            }
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
                const response = await fetch(`/api/projects/${this.projectId}/qc/tasks`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.newTask)
                });

                if (response.ok) {
                    await this.fetchTasks();
                    this.closeNewTaskModal();
                } else {
                    alert('Gagal menyimpan task baru.');
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
                    if (this.activeTask && this.activeTask.id === taskId) {
                        this.activeTask.column_id = columnId;
                        
                        // Also update activeTask from the newly fetched array to maintain consistency
                        const updatedTask = this.tasks.find(t => t.id === taskId);
                        if (updatedTask) {
                            this.activeTask = updatedTask;
                        }
                    }
                }
            } catch (error) {
                console.error('Error updating task column:', error);
            } finally {
                this.isMovingTask = false;
                this.movingToColumn = null;
            }
        }
    }
}
</script>
@endsection
