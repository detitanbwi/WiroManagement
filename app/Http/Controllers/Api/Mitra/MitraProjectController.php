<?php

namespace App\Http\Controllers\Api\Mitra;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MitraProjectController extends Controller
{
    /**
     * Ambil daftar seluruh project yang dimiliki klien yang sedang login.
     */
    public function index(Request $request): JsonResponse
    {
        $clientId = $request->user()->client_id;

        $projects = Project::where('client_id', $clientId)
            ->with(['pm:id,name,email', 'milestones'])
            ->latest()
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'project_code' => $project->project_code ?? 'PRJ-' . str_pad($project->id, 3, '0', STR_PAD_LEFT),
                    'title' => $project->title,
                    'status' => $project->status,
                    'progress_percentage' => (int)$project->progress_percentage,
                    'start_date' => $project->start_date ? $project->start_date->format('Y-m-d') : null,
                    'end_date' => $project->end_date ? $project->end_date->format('Y-m-d') : null,
                    'pm' => $project->pm ? [
                        'name' => $project->pm->name,
                        'email' => $project->pm->email,
                    ] : null,
                    'milestones_count' => $project->milestones->count(),
                    'completed_milestones' => $project->milestones->where('status', 'completed')->count(),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $projects,
        ]);
    }

    /**
     * Ambil detail spesifik suatu project milik klien (Proteksi IDOR ketat).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $clientId = $request->user()->client_id;

        $project = Project::where('client_id', $clientId)
            ->with([
                'pm:id,name,email',
                'milestones' => fn($q) => $q->orderBy('order_index'),
                'updates' => fn($q) => $q->where('is_visible_to_client', true)->with('author:id,name')->latest(),
                'invoices' => fn($q) => $q->latest(),
            ])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $project->id,
                'project_code' => $project->project_code ?? 'PRJ-' . str_pad($project->id, 3, '0', STR_PAD_LEFT),
                'title' => $project->title,
                'description' => $project->description,
                'status' => $project->status,
                'progress_percentage' => (int)$project->progress_percentage,
                'start_date' => $project->start_date ? $project->start_date->format('Y-m-d') : null,
                'end_date' => $project->end_date ? $project->end_date->format('Y-m-d') : null,
                'pm' => $project->pm ? [
                    'name' => $project->pm->name,
                    'email' => $project->pm->email,
                ] : null,
                'milestones' => $project->milestones->map(function ($m) {
                    return [
                        'id' => $m->id,
                        'title' => $m->title,
                        'description' => $m->description,
                        'status' => $m->status,
                        'due_date' => $m->due_date ? $m->due_date->format('Y-m-d') : null,
                        'order_index' => $m->order_index,
                    ];
                }),
                'updates' => $project->updates->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'title' => $u->title,
                        'content' => $u->content,
                        'author_name' => $u->author->name ?? 'PM',
                        'created_at' => $u->created_at->toIso8601String(),
                    ];
                }),
                'invoices' => $project->invoices->map(function ($inv) {
                    return [
                        'id' => $inv->id,
                        'invoice_number' => $inv->invoice_number,
                        'type' => $inv->type,
                        'total_amount' => (float)$inv->total_amount,
                        'status' => $inv->status,
                        'due_date' => $inv->due_date ? $inv->due_date->format('Y-m-d') : null,
                        'issued_date' => $inv->issued_date ? $inv->issued_date->format('Y-m-d') : null,
                    ];
                }),
            ]
        ]);
    }
}
