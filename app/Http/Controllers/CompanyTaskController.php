<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CompanyTask;
use App\Models\User;
use Illuminate\Http\Request;

class CompanyTaskController extends Controller
{
    /**
     * Display a listing of company tasks / Kanban Board.
     */
    public function index(Request $request)
    {
        $query = CompanyTask::with(['client', 'assignees', 'assignee', 'creator'])
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc');

        // Optional server-side filtering
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('assignee_id')) {
            $assigneeId = $request->assignee_id;
            $query->where(function ($q) use ($assigneeId) {
                $q->where('assignee_id', $assigneeId)
                  ->orWhereHas('assignees', function ($aq) use ($assigneeId) {
                      $aq->where('users.id', $assigneeId);
                  });
            });
        }
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        $tasks = $query->get();

        if ($request->wantsJson()) {
            return response()->json($tasks);
        }

        $clients = Client::orderBy('name')->get(['id', 'name', 'company_name']);
        $users = User::where('is_active', true)
            ->whereNull('client_id')
            ->with('roles:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Stats calculation
        $allTasks = CompanyTask::all();
        $stats = [
            'total' => $allTasks->count(),
            'meetings' => $allTasks->where('type', 'meeting')->count(),
            'proposals' => $allTasks->where('type', 'proposal')->count(),
            'reports' => $allTasks->where('type', 'report')->count(),
            'in_progress' => $allTasks->whereIn('status', ['todo', 'in_progress', 'waiting_client'])->count(),
            'done' => $allTasks->where('status', 'done')->count(),
        ];

        $typeDefinitions = CompanyTask::typeDefinitions();
        $statusDefinitions = CompanyTask::statusDefinitions();
        $priorityDefinitions = CompanyTask::priorityDefinitions();

        return view('company-tasks.index', compact(
            'tasks',
            'clients',
            'users',
            'stats',
            'typeDefinitions',
            'statusDefinitions',
            'priorityDefinitions'
        ));
    }

    /**
     * Store a newly created company task in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:meeting,proposal,report,operational,general',
            'status' => 'required|string|in:todo,in_progress,waiting_client,done',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'client_id' => 'nullable|exists:clients,id',
            'assignee_id' => 'nullable|exists:users,id',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'exists:users,id',
            'due_date' => 'nullable|date',
            'meeting_time' => 'nullable|date',
            'meeting_location' => 'nullable|string|max:255',
        ]);

        $assigneeIds = $request->input('assignee_ids', []);
        if (empty($assigneeIds) && !empty($validated['assignee_id'])) {
            $assigneeIds = [$validated['assignee_id']];
        }

        $validated['code'] = CompanyTask::generateNextCode();
        $validated['created_by'] = auth()->id();
        $validated['assignee_id'] = !empty($assigneeIds) ? $assigneeIds[0] : null;

        // Calculate sort order
        $maxSort = CompanyTask::where('status', $validated['status'])->max('sort_order') ?? 0;
        $validated['sort_order'] = $maxSort + 1;

        unset($validated['assignee_ids']);
        $task = CompanyTask::create($validated);

        if (!empty($assigneeIds)) {
            $task->assignees()->sync($assigneeIds);
        }

        $task->load(['client', 'assignees', 'assignee', 'creator']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Task internal (' . $task->code . ') berhasil dibuat.',
                'task' => $task
            ], 201);
        }

        return redirect()->route('company-tasks.index')->with('success', 'Task internal (' . $task->code . ') berhasil dibuat.');
    }

    /**
     * Update the specified company task in storage.
     */
    public function update(Request $request, CompanyTask $companyTask)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:meeting,proposal,report,operational,general',
            'status' => 'required|string|in:todo,in_progress,waiting_client,done',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'client_id' => 'nullable|exists:clients,id',
            'assignee_id' => 'nullable|exists:users,id',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'exists:users,id',
            'due_date' => 'nullable|date',
            'meeting_time' => 'nullable|date',
            'meeting_location' => 'nullable|string|max:255',
        ]);

        $assigneeIds = $request->input('assignee_ids', []);
        if ($request->has('assignee_ids')) {
            $validated['assignee_id'] = !empty($assigneeIds) ? $assigneeIds[0] : null;
        } elseif (!empty($validated['assignee_id'])) {
            $assigneeIds = [$validated['assignee_id']];
        }

        unset($validated['assignee_ids']);
        $companyTask->update($validated);

        if ($request->has('assignee_ids') || !empty($assigneeIds)) {
            $companyTask->assignees()->sync($assigneeIds);
        }

        $companyTask->load(['client', 'assignees', 'assignee', 'creator']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Task (' . $companyTask->code . ') berhasil diperbarui.',
                'task' => $companyTask
            ]);
        }

        return redirect()->route('company-tasks.index')->with('success', 'Task (' . $companyTask->code . ') berhasil diperbarui.');
    }

    /**
     * Move task to another column or reorder (drag and drop).
     */
    public function move(Request $request, CompanyTask $companyTask)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:todo,in_progress,waiting_client,done',
            'sort_order' => 'nullable|integer',
        ]);

        $companyTask->status = $validated['status'];
        if (isset($validated['sort_order'])) {
            $companyTask->sort_order = $validated['sort_order'];
        }
        $companyTask->save();
        $companyTask->load(['client', 'assignees', 'assignee', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Status task berhasil diperbarui.',
            'task' => $companyTask
        ]);
    }

    /**
     * Remove the specified company task from storage.
     */
    public function destroy(Request $request, CompanyTask $companyTask)
    {
        $code = $companyTask->code;
        $companyTask->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Task (' . $code . ') berhasil dihapus.'
            ]);
        }

        return redirect()->route('company-tasks.index')->with('success', 'Task (' . $code . ') berhasil dihapus.');
    }
}
