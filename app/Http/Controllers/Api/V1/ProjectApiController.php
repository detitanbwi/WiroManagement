<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectApiController extends Controller
{
    /**
     * Display a listing of projects with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Project::with([
            'client:id,name,company_name,phone,email',
            'pm:id,name,email',
        ]);

        if ($search = $request->input('search', $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('project_code', 'LIKE', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('name', 'LIKE', "%{$search}%")
                         ->orWhere('company_name', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->input('client_id')) {
            $query->where('client_id', $clientId);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $projects = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $projects,
        ]);
    }

    /**
     * Store a newly created project (Quick Project Creation).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'status' => 'required|in:draft,quotation_sent,approved,in_progress,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'pm_id' => 'nullable|exists:users,id',
        ]);

        $project = Project::create([
            'client_id' => $validated['client_id'],
            'title' => $validated['title'],
            'status' => $validated['status'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'pm_id' => $validated['pm_id'] ?? null,
        ]);

        $project->load('client:id,name,company_name');

        return response()->json([
            'status' => 'success',
            'message' => 'Proyek berhasil dibuat.',
            'data' => $project,
        ], 201);
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): JsonResponse
    {
        $project->load([
            'client',
            'pm:id,name,email',
            'invoices' => function ($q) {
                $q->latest();
            },
            'milestones',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $project,
        ]);
    }
}

