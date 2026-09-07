<?php

namespace App\Http\Controllers\Api\Mitra;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MitraDashboardController extends Controller
{
    /**
     * Mengambil ringkasan dashboard eksekutif untuk klien Wiromitra.
     * Otomatis mengisolasi data berdasarkan client_id user yang login.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->load('client');
        $clientId = $user->client_id;

        if (!$clientId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda tidak terhubung dengan profil klien.',
            ], 403);
        }

        // Ambil semua project milik klien
        $projects = Project::where('client_id', $clientId)
            ->with(['pm:id,name,email', 'milestones'])
            ->latest()
            ->get();

        // Hitung statistik
        $totalProjects = $projects->count();
        $inProgressProjects = $projects->where('status', 'in_progress')->count();
        $completedProjects = $projects->where('status', 'completed')->count();
        $averageProgress = $totalProjects > 0 ? round($projects->avg('progress_percentage')) : 0;

        // Ambil update perkembangan terbaru yang boleh dilihat klien
        $recentUpdates = ProjectUpdate::whereIn('project_id', $projects->pluck('id'))
            ->where('is_visible_to_client', true)
            ->with(['project:id,title,project_code', 'author:id,name'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($update) {
                return [
                    'id' => $update->id,
                    'project_id' => $update->project_id,
                    'project_title' => $update->project->title ?? 'Project',
                    'project_code' => $update->project->project_code ?? null,
                    'title' => $update->title,
                    'content' => $update->content,
                    'author_name' => $update->author->name ?? 'PM',
                    'created_at' => $update->created_at->toIso8601String(),
                ];
            });

        // Ringkasan Invoice Klien
        $invoices = Invoice::whereIn('project_id', $projects->pluck('id'))
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'project_id' => $inv->project_id,
                    'total_amount' => (float)$inv->total_amount,
                    'status' => $inv->status,
                    'due_date' => $inv->due_date ? $inv->due_date->format('Y-m-d') : null,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'client' => [
                    'id' => $user->client->id ?? null,
                    'name' => $user->client->name ?? $user->name,
                    'company_name' => $user->client->company_name ?? null,
                    'email' => $user->email,
                ],
                'summary' => [
                    'total_projects' => $totalProjects,
                    'in_progress_projects' => $inProgressProjects,
                    'completed_projects' => $completedProjects,
                    'average_progress' => $averageProgress,
                ],
                'projects' => $projects->map(function ($project) {
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
                        'completed_milestones_count' => $project->milestones->where('status', 'completed')->count(),
                    ];
                }),
                'recent_updates' => $recentUpdates,
                'invoices' => $invoices,
            ]
        ]);
    }
}
